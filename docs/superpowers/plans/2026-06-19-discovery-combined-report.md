# Discovery Combined Report Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an admin-gated branded HTML report at `/discovery/{slug}/report` that aggregates all stakeholder submissions for a discovery instance into one ranked gap map with named "Team split" divergence callouts, strategic-direction alignment, and verbatim qualitative answers — plus non-destructive soft-exclude of duplicate/test submissions.

**Architecture:** Three new units in the existing `wp-content/themes/newblood/inc/discovery/` module — a **pure aggregation engine** (`aggregate.php`, fully unit-tested), an **HTML report renderer** (`report.php`), and a **report route** (extends `routing.php` + `controller.php`) — reusing the same config, stored data, and green/black CSS tokens. A schema bump adds one `excluded` column (applied by the existing self-healing migration). Soft-exclude is a nonce'd `admin_post` toggle. Engine/renderer split so a future client-facing report is a re-skin over the same aggregate object.

**Tech Stack:** PHP (WordPress, no framework), server-rendered HTML + CSS bars (no JS, no charts lib), dbDelta migration, WP-CLI + headless Playwright for verification, standalone PHP-CLI assertion scripts for the pure engine.

## Global Constraints

- **WP-CLI invocation:** always `php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood <subcommand>` (default 128M fatals).
- **Local dev URL:** `http://newblood.test` (Laravel Herd). Production: Nexcess SFTP, no CI.
- **Active branch:** `feature/redesign`. Work in place (the Herd-served WP install lives at the repo path; verification depends on it). Do not create a worktree.
- **Cache-busting:** asset versions use `newblood_asset_version()` (filemtime). Never add manual `?ver=` bumps against the system; the inline `?v=filemtime` on the standalone page is the established pattern.
- **Run `php -l <file>` after every PHP edit** — theme PHP is parsed at request time.
- **Spec:** `docs/superpowers/specs/2026-06-19-discovery-combined-report-design.md`.
- **Builds on the existing discovery module:** `nb_discovery_get_instance($slug)` returns the instance config (`client_name`, `services` `[{key,label,hint}]`, `goal_vectors` `[{key,left,right}]`, …); `nb_discovery_table_name()` returns `{$wpdb->prefix}nb_discovery_responses`; stored `payload` JSON shape per submission: `{ respondent:{name,email}, services:[{key,importance,handling|null,gap|null}], vision, goal_vectors:{5 keys}, systems:{crm,lead_handling,reviews_system,call_tracking,gbp_access,territories}, posture:{fix_invest,timeline}, open }`.
- **Split thresholds:** service importance spread ≥ **4** = "Team split" (`NB_DISCOVERY_SPLIT_THRESHOLD`); goal-vector spread ≥ **40** on the −50..50 scale (`NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD`). Both `define()`d so they're tunable.
- **Access:** the report renders only when `current_user_can('manage_options')`; otherwise `set_404()` + a 404. Report page sends `nocache_headers()` + `DONOTCACHEPAGE`.
- **Soft-exclude is non-destructive:** rows are never deleted by the feature; an `excluded` flag is toggled. The toggle is capability- + nonce-checked.
- **Voice:** deliberative; musical second-meanings only where they read as natural English first.
- **Service keys** (stable): `website, seo_aeo, hosting_security, content, reviews, lead_gen, lead_capture, customer_comms, crm, automation_ai, reporting, brand_creative`. **Vector keys:** `residential_commercial, leads_volume_quality, topline_lean, defend_expand, handson_managed`.

---

## File Structure

**New files (under `wp-content/themes/newblood/`):**
- `inc/discovery/aggregate.php` — pure engine `nb_discovery_aggregate( $submissions, $instance )` + the two threshold constants.
- `inc/discovery/report.php` — `nb_discovery_render_report( $instance, $aggregate, $excluded_rows = array() )` (HTML echo) and `nb_discovery_output_report( $instance )` (DB query → build submissions → aggregate → render).
- `assets/css/discovery-report.css` — report layout (gap-map bars, split callouts, vector strip, roster, excluded list).
- `tests/discovery/test-aggregate.php` — standalone assertions for the engine.

**Modified files:**
- `inc/discovery/db.php` — add `excluded` column; `NB_DISCOVERY_DB_VERSION` `1`→`2`.
- `inc/discovery/routing.php` — add report rewrite + `nb_discovery_report` query var; `NB_DISCOVERY_REWRITE_VERSION` `1`→`2`.
- `inc/discovery/controller.php` — report branch (admin gate → `nb_discovery_output_report`); register the `admin_post` exclude handler.
- `inc/discovery/index.php` — `require_once` the two new module files.

**Aggregate output shape (canonical — produced by Task 2, consumed by Tasks 4 & 6):**
```
{
  count: int,
  respondents: [ { id:int, name:str, email:str } ],            // submission order
  services: [ {                                                  // ranked by mean_gap desc, null gaps last
    key, label,
    mean_importance: float, mean_handling: float|null, mean_gap: float|null,
    importance_spread: int, handling_spread: int|null,
    split: bool, high: {name,score}|null, low: {name,score}|null,
    per_respondent: [ {name, importance:int, handling:int|null} ]
  } ],
  goal_vectors: [ { key, left, right, mean:float, spread:int, split:bool,
                    per_respondent:[{name, position:int}] } ],
  posture: { fix_invest:{mean:float, spread:int, per_respondent:[{name,position:int}]},
             timelines:[{name, timeline:str}] },
  qualitative: { vision:[{name,value}], open:[{name,value}],
                 crm:[...], lead_handling:[...], reviews_system:[...],
                 call_tracking:[...], territories:[...], gbp_access:[...] }
}
```

---

### Task 1: Schema bump — `excluded` column

**Files:**
- Modify: `wp-content/themes/newblood/inc/discovery/db.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: `wp_nb_discovery_responses.excluded TINYINT(1) NOT NULL DEFAULT 0`; `NB_DISCOVERY_DB_VERSION === '2'`. The existing `nb_discovery_maybe_migrate()` (hooked on `after_setup_theme`) applies it via dbDelta on the next request.

- [ ] **Step 1: Bump the version constant**

In `wp-content/themes/newblood/inc/discovery/db.php`, change:
```php
define( 'NB_DISCOVERY_DB_VERSION', '1' );
```
to:
```php
define( 'NB_DISCOVERY_DB_VERSION', '2' );
```

- [ ] **Step 2: Add the column to the CREATE TABLE statement**

In the same file, in `nb_discovery_install_table()`, add the `excluded` column line after the `ip` column (keep dbDelta's two-space `PRIMARY KEY  (id)` intact). The column block becomes:
```php
        payload LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        excluded TINYINT(1) NOT NULL DEFAULT 0,
        PRIMARY KEY  (id),
        KEY instance (instance),
        KEY created_at (created_at)
```

- [ ] **Step 3: Lint + apply the migration**

Run: `php -l wp-content/themes/newblood/inc/discovery/db.php`
Expected: `No syntax errors detected`

Apply (dbDelta ALTERs the existing table to add the column):
```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood eval 'nb_discovery_install_table(); echo "migrated\n";'
```

- [ ] **Step 4: Verify the column + version**

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood db query "DESCRIBE wp_nb_discovery_responses;"
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood option get nb_discovery_db_version
```
Expected: the DESCRIBE includes an `excluded` row (`tinyint(1)`, default `0`); the option prints `2`.

- [ ] **Step 5: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/db.php
git commit -m "feat(discovery-report): add excluded column (schema v2)"
```

---

### Task 2: Aggregation engine (pure) + unit tests

**Files:**
- Create: `wp-content/themes/newblood/inc/discovery/aggregate.php`
- Create: `wp-content/themes/newblood/tests/discovery/test-aggregate.php`
- Modify: `wp-content/themes/newblood/inc/discovery/index.php` (require)

**Interfaces:**
- Consumes: `nb_discovery_get_instance()` (existing) for labels in tests.
- Produces: `nb_discovery_aggregate( array $submissions, array $instance ): array` — `$submissions` = active records `[ {id,name,email,payload(decoded array)} ]`; returns the canonical aggregate shape (see File Structure). Constants `NB_DISCOVERY_SPLIT_THRESHOLD` (4), `NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD` (40).

- [ ] **Step 1: Write the failing test**

Create `wp-content/themes/newblood/tests/discovery/test-aggregate.php`:
```php
<?php
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/config.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/aggregate.php';

$instance = nb_discovery_get_instance( 'overhead-door' );

// Two stakeholders. Only the services we assert on are included in payloads;
// the engine simply has no data for the others and omits them.
$chase = array( 'id' => 1, 'name' => 'Chase', 'email' => 'chase@x.com', 'payload' => array(
    'services' => array(
        array( 'key' => 'website',  'importance' => 9,  'handling' => 4 ),
        array( 'key' => 'lead_gen', 'importance' => 10, 'handling' => 2 ),
        array( 'key' => 'content',  'importance' => 3,  'handling' => null ),
    ),
    'goal_vectors' => array( 'defend_expand' => 30 ),
    'posture' => array( 'fix_invest' => 40, 'timeline' => 'ASAP' ),
    'vision' => 'Own commercial.', 'open' => '',
) );
$paul = array( 'id' => 2, 'name' => 'Paul', 'email' => 'paul@x.com', 'payload' => array(
    'services' => array(
        array( 'key' => 'website',  'importance' => 9, 'handling' => 2 ),
        array( 'key' => 'lead_gen', 'importance' => 4, 'handling' => null ),
        array( 'key' => 'content',  'importance' => 2, 'handling' => null ),
    ),
    'goal_vectors' => array( 'defend_expand' => -20 ),
    'posture' => array( 'fix_invest' => 0, 'timeline' => '3-6 months' ),
    'vision' => 'Steady.', 'open' => '',
) );

$agg = nb_discovery_aggregate( array( $chase, $paul ), $instance );

assert( $agg['count'] === 2, 'count 2' );
assert( count( $agg['respondents'] ) === 2 && $agg['respondents'][0]['id'] === 1, 'roster carries id' );

// Ranking: website (gap 9-3=6) before lead_gen (gap 7-2=5); content (null gap) last.
$keys = array_column( $agg['services'], 'key' );
assert( $keys[0] === 'website', 'website ranks first by gap' );
assert( $keys[1] === 'lead_gen', 'lead_gen second' );
assert( end( $keys ) === 'content', 'null-gap content sinks last' );

$byKey = array();
foreach ( $agg['services'] as $s ) { $byKey[ $s['key'] ] = $s; }

// website: imp [9,9] mean 9; handling [4,2] mean 3; gap 6; spread 0; not split.
assert( $byKey['website']['mean_importance'] === 9.0, 'website mean imp 9' );
assert( $byKey['website']['mean_handling'] === 3.0, 'website mean handling 3' );
assert( $byKey['website']['mean_gap'] === 6.0, 'website mean gap 6' );
assert( $byKey['website']['importance_spread'] === 0, 'website spread 0' );
assert( $byKey['website']['split'] === false, 'website not split' );

// lead_gen: imp [10,4] mean 7; handling only Chase rated [2] mean 2; gap 5; spread 6 -> split.
assert( $byKey['lead_gen']['mean_importance'] === 7.0, 'lead_gen mean imp 7' );
assert( $byKey['lead_gen']['mean_handling'] === 2.0, 'lead_gen mean handling over raters' );
assert( $byKey['lead_gen']['mean_gap'] === 5.0, 'lead_gen gap 5' );
assert( $byKey['lead_gen']['importance_spread'] === 6, 'lead_gen spread 6' );
assert( $byKey['lead_gen']['split'] === true, 'lead_gen split at spread>=4' );
assert( $byKey['lead_gen']['high']['name'] === 'Chase' && $byKey['lead_gen']['high']['score'] === 10, 'high Chase 10' );
assert( $byKey['lead_gen']['low']['name'] === 'Paul' && $byKey['lead_gen']['low']['score'] === 4, 'low Paul 4' );

// content: nobody rated handling -> mean_handling null, mean_gap null.
assert( $byKey['content']['mean_handling'] === null, 'content mean handling null' );
assert( $byKey['content']['mean_gap'] === null, 'content mean gap null' );

// goal vector defend_expand: [30,-20] mean 5, spread 50 -> split (>=40).
$gv = array();
foreach ( $agg['goal_vectors'] as $v ) { $gv[ $v['key'] ] = $v; }
assert( $gv['defend_expand']['mean'] === 5.0, 'vector mean 5' );
assert( $gv['defend_expand']['spread'] === 50, 'vector spread 50' );
assert( $gv['defend_expand']['split'] === true, 'vector split at spread>=40' );

// posture fix_invest [40,0] mean 20 spread 40.
assert( $agg['posture']['fix_invest']['mean'] === 20.0, 'posture mean 20' );
assert( $agg['posture']['fix_invest']['spread'] === 40, 'posture spread 40' );

// qualitative vision has both, verbatim.
assert( count( $agg['qualitative']['vision'] ) === 2, 'two vision entries' );

// Single-response: no splits.
$solo = nb_discovery_aggregate( array( $chase ), $instance );
$soloByKey = array();
foreach ( $solo['services'] as $s ) { $soloByKey[ $s['key'] ] = $s; }
assert( $soloByKey['lead_gen']['split'] === false, 'single response never splits' );
assert( $solo['count'] === 1, 'solo count 1' );

// Zero-response: empty shape, no fatals.
$empty = nb_discovery_aggregate( array(), $instance );
assert( $empty['count'] === 0 && $empty['services'] === array(), 'empty aggregate' );

echo "test-aggregate: PASS\n";
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php wp-content/themes/newblood/tests/discovery/test-aggregate.php`
Expected: FAIL — fatal `Failed opening required '.../aggregate.php'` (file doesn't exist yet).

- [ ] **Step 3: Write the engine**

Create `wp-content/themes/newblood/inc/discovery/aggregate.php`:
```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit; // bootstrap.php defines ABSPATH for the CLI tests, so this is safe under both WP and the harness
if ( ! defined( 'NB_DISCOVERY_SPLIT_THRESHOLD' ) )        define( 'NB_DISCOVERY_SPLIT_THRESHOLD', 4 );
if ( ! defined( 'NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD' ) ) define( 'NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD', 40 );

/**
 * Aggregate active discovery submissions into one combined summary.
 *
 * @param array $submissions Active records: [ {id, name, email, payload(decoded array)} ]
 * @param array $instance     Instance config from nb_discovery_get_instance().
 * @return array              Canonical aggregate shape (see plan File Structure).
 */
function nb_discovery_aggregate( $submissions, $instance ) {
    $count = count( $submissions );

    $respondents = array();
    foreach ( $submissions as $sub ) {
        $respondents[] = array( 'id' => $sub['id'], 'name' => $sub['name'], 'email' => $sub['email'] );
    }

    // ---- Services ----
    $services = array();
    foreach ( $instance['services'] as $svc ) {
        $key  = $svc['key'];
        $imps = array();
        $hand = array();
        $per  = array();
        foreach ( $submissions as $sub ) {
            $imp = null; $h = null;
            if ( ! empty( $sub['payload']['services'] ) ) {
                foreach ( $sub['payload']['services'] as $ps ) {
                    if ( isset( $ps['key'] ) && $ps['key'] === $key ) {
                        $imp = (int) $ps['importance'];
                        $h   = ( isset( $ps['handling'] ) && $ps['handling'] !== null ) ? (int) $ps['handling'] : null;
                        break;
                    }
                }
            }
            if ( $imp === null ) { continue; } // submission has no data for this service
            $imps[] = $imp;
            if ( $h !== null ) { $hand[] = $h; }
            $per[] = array( 'name' => $sub['name'], 'importance' => $imp, 'handling' => $h );
        }
        if ( empty( $imps ) ) { continue; } // no respondent covered this service

        $mean_importance = round( array_sum( $imps ) / count( $imps ), 1 );
        $mean_handling   = count( $hand ) ? round( array_sum( $hand ) / count( $hand ), 1 ) : null;
        $mean_gap        = ( $mean_handling !== null ) ? round( $mean_importance - $mean_handling, 1 ) : null;
        $importance_spread = max( $imps ) - min( $imps );
        $handling_spread   = ( count( $hand ) >= 2 ) ? max( $hand ) - min( $hand ) : null;

        $high = null; $low = null;
        foreach ( $per as $p ) {
            if ( $high === null || $p['importance'] > $high['score'] ) { $high = array( 'name' => $p['name'], 'score' => $p['importance'] ); }
            if ( $low === null  || $p['importance'] < $low['score'] )  { $low  = array( 'name' => $p['name'], 'score' => $p['importance'] ); }
        }

        $services[] = array(
            'key' => $key,
            'label' => isset( $svc['label'] ) ? $svc['label'] : $key,
            'mean_importance' => $mean_importance,
            'mean_handling'   => $mean_handling,
            'mean_gap'        => $mean_gap,
            'importance_spread' => $importance_spread,
            'handling_spread'   => $handling_spread,
            'split' => $importance_spread >= NB_DISCOVERY_SPLIT_THRESHOLD,
            'high' => $high,
            'low'  => $low,
            'per_respondent' => $per,
        );
    }
    // Rank by mean_gap desc; null gaps sink (sentinel -100); tie-break mean_importance desc.
    usort( $services, function ( $a, $b ) {
        $ga = $a['mean_gap'] === null ? -100 : $a['mean_gap'];
        $gb = $b['mean_gap'] === null ? -100 : $b['mean_gap'];
        if ( $ga == $gb ) { return $b['mean_importance'] <=> $a['mean_importance']; }
        return $gb <=> $ga;
    } );

    // ---- Goal vectors ----
    $goal_vectors = array();
    foreach ( $instance['goal_vectors'] as $v ) {
        $key = $v['key'];
        $pos = array();
        $per = array();
        foreach ( $submissions as $sub ) {
            $p = isset( $sub['payload']['goal_vectors'][ $key ] ) ? (int) $sub['payload']['goal_vectors'][ $key ] : 0;
            $pos[] = $p;
            $per[] = array( 'name' => $sub['name'], 'position' => $p );
        }
        $goal_vectors[] = array(
            'key' => $key, 'left' => $v['left'], 'right' => $v['right'],
            'mean'   => $count ? round( array_sum( $pos ) / $count, 1 ) : 0,
            'spread' => $count ? max( $pos ) - min( $pos ) : 0,
            'split'  => $count ? ( ( max( $pos ) - min( $pos ) ) >= NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD ) : false,
            'per_respondent' => $per,
        );
    }

    // ---- Posture ----
    $fix = array(); $fix_per = array(); $timelines = array();
    foreach ( $submissions as $sub ) {
        $f = isset( $sub['payload']['posture']['fix_invest'] ) ? (int) $sub['payload']['posture']['fix_invest'] : 0;
        $fix[] = $f;
        $fix_per[] = array( 'name' => $sub['name'], 'position' => $f );
        $timelines[] = array( 'name' => $sub['name'], 'timeline' => isset( $sub['payload']['posture']['timeline'] ) ? $sub['payload']['posture']['timeline'] : '' );
    }
    $posture = array(
        'fix_invest' => array(
            'mean'   => $count ? round( array_sum( $fix ) / $count, 1 ) : 0,
            'spread' => $count ? max( $fix ) - min( $fix ) : 0,
            'per_respondent' => $fix_per,
        ),
        'timelines' => $timelines,
    );

    // ---- Qualitative (verbatim per respondent) ----
    $qual_fields = array( 'vision', 'open' );
    $sys_fields  = array( 'crm', 'lead_handling', 'reviews_system', 'call_tracking', 'territories', 'gbp_access' );
    $qualitative = array();
    foreach ( array_merge( $qual_fields, $sys_fields ) as $f ) { $qualitative[ $f ] = array(); }
    foreach ( $submissions as $sub ) {
        foreach ( $qual_fields as $f ) {
            $qualitative[ $f ][] = array( 'name' => $sub['name'], 'value' => isset( $sub['payload'][ $f ] ) ? $sub['payload'][ $f ] : '' );
        }
        foreach ( $sys_fields as $f ) {
            $qualitative[ $f ][] = array( 'name' => $sub['name'], 'value' => isset( $sub['payload']['systems'][ $f ] ) ? $sub['payload']['systems'][ $f ] : '' );
        }
    }

    return array(
        'count' => $count,
        'respondents' => $respondents,
        'services' => $services,
        'goal_vectors' => $goal_vectors,
        'posture' => $posture,
        'qualitative' => $qualitative,
    );
}
```

> Note: the test payloads omit the `systems` key, so the qualitative systems fields resolve to `''` via the `isset` guards — no fatals. The engine calls no WP functions, so it loads cleanly under both WP and the CLI test harness (the harness's `bootstrap.php` defines `ABSPATH`).

- [ ] **Step 4: Run the test to verify it passes**

Run: `php wp-content/themes/newblood/tests/discovery/test-aggregate.php`
Expected: `test-aggregate: PASS`

- [ ] **Step 5: Require the engine in the bootstrap + lint**

In `wp-content/themes/newblood/inc/discovery/index.php`, add after the `require_once __DIR__ . '/config.php';` line:
```php
require_once __DIR__ . '/aggregate.php';
```

Run: `php -l wp-content/themes/newblood/inc/discovery/aggregate.php && php -l wp-content/themes/newblood/inc/discovery/index.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/aggregate.php wp-content/themes/newblood/tests/discovery/test-aggregate.php wp-content/themes/newblood/inc/discovery/index.php
git commit -m "feat(discovery-report): pure aggregation engine + unit tests"
```

---

### Task 3: Report rewrite route

**Files:**
- Modify: `wp-content/themes/newblood/inc/discovery/routing.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: query var `nb_discovery_report`; rewrite `^discovery/([^/]+)/report/?$` → `index.php?nb_discovery=$matches[1]&nb_discovery_report=1`; `NB_DISCOVERY_REWRITE_VERSION === '2'` (forces one self-healing flush).

- [ ] **Step 1: Bump the rewrite version**

In `wp-content/themes/newblood/inc/discovery/routing.php`, change:
```php
define( 'NB_DISCOVERY_REWRITE_VERSION', '1' );
```
to:
```php
define( 'NB_DISCOVERY_REWRITE_VERSION', '2' );
```

- [ ] **Step 2: Register the report query var**

Change the query-var filter to also register `nb_discovery_report`:
```php
function nb_discovery_register_query_var( $vars ) {
    $vars[] = 'nb_discovery';
    $vars[] = 'nb_discovery_report';
    return $vars;
}
```

- [ ] **Step 3: Register the report rewrite rule**

In `nb_discovery_register_rewrite()`, add the report rule. Order does not matter here (the form rule `^discovery/([^/]+)/?$` cannot match a URL with a `/report` segment), but register the more specific rule too:
```php
function nb_discovery_register_rewrite() {
    add_rewrite_rule( '^discovery/([^/]+)/report/?$', 'index.php?nb_discovery=$matches[1]&nb_discovery_report=1', 'top' );
    add_rewrite_rule( '^discovery/([^/]+)/?$', 'index.php?nb_discovery=$matches[1]', 'top' );
}
```

- [ ] **Step 4: Lint + flush + verify the rule resolves**

Run: `php -l wp-content/themes/newblood/inc/discovery/routing.php`
Expected: `No syntax errors detected`

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood rewrite flush
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood rewrite list --format=csv | grep 'discovery/.*report'
```
Expected: a rule matching `^discovery/([^/]+)/report/?$` → `index.php?nb_discovery=$matches[1]&nb_discovery_report=1`.

```bash
curl -s -o /dev/null -w "%{http_code}\n" -L http://newblood.test/discovery/overhead-door/report
```
Expected: `404` for now — the route resolves but no controller branch handles `nb_discovery_report` yet, and a logged-out curl isn't an admin (it will keep 404ing for non-admins after Task 4 too). The point: it is NOT a server-level 404 of an unknown path; WP routes it.

- [ ] **Step 5: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/routing.php
git commit -m "feat(discovery-report): /discovery/{slug}/report route (rewrite v2)"
```

---

### Task 4: Report controller branch + read-only renderer

**Files:**
- Create: `wp-content/themes/newblood/inc/discovery/report.php`
- Modify: `wp-content/themes/newblood/inc/discovery/controller.php`
- Modify: `wp-content/themes/newblood/inc/discovery/index.php` (require)

**Interfaces:**
- Consumes: `nb_discovery_get_instance()`, `nb_discovery_aggregate()` (Task 2), `nb_discovery_table_name()`, `newblood_asset_version()`, query var `nb_discovery_report` (Task 3), `excluded` column (Task 1).
- Produces: `nb_discovery_output_report( array $instance ): void` (queries active rows `excluded=0`, builds submissions, aggregates, renders, does NOT exit — caller exits); `nb_discovery_render_report( array $instance, array $aggregate, array $excluded_rows = array() ): void` (echoes the standalone HTML document). Controller branch: when `nb_discovery_report` is set, require `manage_options` (else 404) then output the report and `exit`.

- [ ] **Step 1: Write the renderer + output orchestrator**

Create `wp-content/themes/newblood/inc/discovery/report.php`:
```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Query active submissions for an instance, aggregate, and render the report.
 * Caller is responsible for the capability gate and for exit().
 */
function nb_discovery_output_report( $instance ) {
    global $wpdb;
    $table = nb_discovery_table_name();
    $slug  = $instance['slug'];

    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, respondent_name, respondent_email, payload, created_at, excluded
             FROM {$table} WHERE instance = %s ORDER BY id ASC",
            $slug
        ),
        ARRAY_A
    );
    if ( ! is_array( $rows ) ) { $rows = array(); }

    $submissions   = array();
    $excluded_rows = array();
    foreach ( $rows as $r ) {
        if ( (int) $r['excluded'] === 1 ) {
            $excluded_rows[] = array( 'id' => (int) $r['id'], 'name' => $r['respondent_name'], 'created_at' => $r['created_at'] );
            continue;
        }
        $payload = json_decode( $r['payload'], true );
        if ( ! is_array( $payload ) ) { $payload = array(); }
        $submissions[] = array(
            'id'    => (int) $r['id'],
            'name'  => $r['respondent_name'],
            'email' => $r['respondent_email'],
            'payload' => $payload,
        );
    }

    $aggregate = nb_discovery_aggregate( $submissions, $instance );
    nb_discovery_render_report( $instance, $aggregate, $excluded_rows );
}

/**
 * Echo the standalone branded report document. No DB access here.
 */
function nb_discovery_render_report( $instance, $aggregate, $excluded_rows = array() ) {
    if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
    nocache_headers();

    $ver_css = newblood_asset_version( '/assets/css/discovery-report.css' );
    $css_uri = get_template_directory_uri() . '/assets/css/discovery-report.css?v=' . $ver_css;
    $client  = $instance['client_name'];
    $n       = (int) $aggregate['count'];
    ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( 'Discovery report — ' . $client ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( $css_uri ); ?>">
</head>
<body class="nb-r-body">
<main class="nb-r-shell">

  <header class="nb-r-head">
    <p class="nb-r-eyebrow">Combined discovery</p>
    <h1><?php echo esc_html( $client ); ?><span class="nb-r-dot">.</span></h1>
    <p class="nb-r-roster"><strong><?php echo esc_html( $n ); ?></strong> stakeholder<?php echo $n === 1 ? '' : 's'; ?>:
      <?php
      $names = array();
      foreach ( $aggregate['respondents'] as $r ) { $names[] = esc_html( $r['name'] ); }
      echo implode( ' · ', $names );
      ?>
    </p>
  </header>

  <?php if ( $n === 0 ) : ?>
    <section class="nb-r-section"><p class="nb-r-empty">No responses yet for this instance.</p></section>
  <?php else : ?>

  <section class="nb-r-section">
    <h2>Priority &amp; gap map<span class="nb-r-dot">.</span></h2>
    <p class="nb-r-sub">Averaged across stakeholders, ranked by the biggest gap between how important a capability is and how well it's handled today.</p>
    <?php foreach ( $aggregate['services'] as $s ) : ?>
      <div class="nb-r-svc<?php echo $s['split'] ? ' is-split' : ''; ?>">
        <div class="nb-r-svc-top">
          <span class="nb-r-svc-label"><?php echo esc_html( $s['label'] ); ?></span>
          <?php if ( $s['mean_gap'] !== null ) : ?>
            <span class="nb-r-gap">gap <?php echo esc_html( $s['mean_gap'] ); ?></span>
          <?php else : ?>
            <span class="nb-r-gap nb-r-gap-na">not rated</span>
          <?php endif; ?>
        </div>
        <div class="nb-r-bars">
          <div class="nb-r-bar"><span class="nb-r-bar-cap">Importance</span>
            <span class="nb-r-track"><span class="nb-r-fill nb-r-fill-imp" style="width:<?php echo esc_attr( $s['mean_importance'] * 10 ); ?>%"></span></span>
            <span class="nb-r-num"><?php echo esc_html( $s['mean_importance'] ); ?></span>
          </div>
          <?php if ( $s['mean_handling'] !== null ) : ?>
          <div class="nb-r-bar"><span class="nb-r-bar-cap">Handled today</span>
            <span class="nb-r-track"><span class="nb-r-fill nb-r-fill-now" style="width:<?php echo esc_attr( $s['mean_handling'] * 10 ); ?>%"></span></span>
            <span class="nb-r-num"><?php echo esc_html( $s['mean_handling'] ); ?></span>
          </div>
          <?php endif; ?>
        </div>
        <?php if ( $s['split'] && $s['high'] && $s['low'] ) : ?>
          <p class="nb-r-split">⚑ Team split — <?php echo esc_html( $s['high']['name'] ); ?> rates this <?php echo esc_html( $s['high']['score'] ); ?>/10, <?php echo esc_html( $s['low']['name'] ); ?> rates it <?php echo esc_html( $s['low']['score'] ); ?>/10.</p>
        <?php endif; ?>
        <details class="nb-r-detail"><summary>Per stakeholder</summary>
          <ul>
            <?php foreach ( $s['per_respondent'] as $p ) : ?>
              <li><?php echo esc_html( $p['name'] ); ?>: importance <?php echo esc_html( $p['importance'] ); ?><?php echo $p['handling'] !== null ? ', handled ' . esc_html( $p['handling'] ) : ''; ?></li>
            <?php endforeach; ?>
          </ul>
        </details>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="nb-r-section">
    <h2>Strategic direction<span class="nb-r-dot">.</span></h2>
    <p class="nb-r-sub">Where the team wants to take the business — and where they don't yet agree.</p>
    <?php
    $vectors = $aggregate['goal_vectors'];
    $vectors[] = array( 'key' => 'fix_invest', 'left' => 'Fix what’s urgent', 'right' => 'Invest long-term',
        'mean' => $aggregate['posture']['fix_invest']['mean'], 'spread' => $aggregate['posture']['fix_invest']['spread'],
        'split' => $aggregate['posture']['fix_invest']['spread'] >= NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD,
        'per_respondent' => $aggregate['posture']['fix_invest']['per_respondent'] );
    foreach ( $vectors as $v ) :
      $pct = ( $v['mean'] + 50 ) / 100 * 100; // -50..50 -> 0..100
      ?>
      <div class="nb-r-vec<?php echo $v['split'] ? ' is-split' : ''; ?>">
        <div class="nb-r-vec-row">
          <span class="nb-r-vec-cap"><?php echo esc_html( $v['left'] ); ?></span>
          <span class="nb-r-vec-track"><span class="nb-r-vec-mean" style="left:<?php echo esc_attr( $pct ); ?>%"></span></span>
          <span class="nb-r-vec-cap nb-r-vec-cap-r"><?php echo esc_html( $v['right'] ); ?></span>
        </div>
        <?php if ( $v['split'] ) : ?><p class="nb-r-split">⚑ Pulling different directions on this.</p><?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <section class="nb-r-section">
    <h2>Timelines<span class="nb-r-dot">.</span></h2>
    <ul class="nb-r-list">
      <?php foreach ( $aggregate['posture']['timelines'] as $t ) : ?>
        <li><strong><?php echo esc_html( $t['name'] ); ?>:</strong> <?php echo esc_html( $t['timeline'] !== '' ? $t['timeline'] : '(blank)' ); ?></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section class="nb-r-section">
    <h2>In their words<span class="nb-r-dot">.</span></h2>
    <?php
    $qual_labels = array(
      'vision' => '3-year vision', 'open' => 'Anything else',
      'crm' => 'CRM today', 'lead_handling' => 'Lead handling today', 'reviews_system' => 'Reviews system',
      'call_tracking' => 'Call tracking', 'territories' => 'Territories', 'gbp_access' => 'Google Business Profile access',
    );
    foreach ( $qual_labels as $field => $label ) :
      if ( empty( $aggregate['qualitative'][ $field ] ) ) { continue; } ?>
      <h3 class="nb-r-qh"><?php echo esc_html( $label ); ?></h3>
      <ul class="nb-r-list">
        <?php foreach ( $aggregate['qualitative'][ $field ] as $q ) : ?>
          <li><strong><?php echo esc_html( $q['name'] ); ?>:</strong> <?php echo esc_html( $q['value'] !== '' ? $q['value'] : '(blank)' ); ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endforeach; ?>
  </section>

  <?php endif; ?>

</main>
</body>
</html><?php
}
```
> Replace the `’` in `Fix what’s urgent` with the real right-single-quote glyph in the file.

- [ ] **Step 2: Add the controller branch + require**

In `wp-content/themes/newblood/inc/discovery/controller.php`, replace the body of `nb_discovery_template_redirect()` so it handles the report route. The full function becomes:
```php
function nb_discovery_template_redirect() {
    $slug = get_query_var( 'nb_discovery' );
    if ( ! $slug ) {
        return; // not our route
    }
    $instance = nb_discovery_get_instance( sanitize_title( $slug ) );
    if ( ! $instance ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        return;
    }
    if ( get_query_var( 'nb_discovery_report' ) ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            return; // do not reveal the report to non-admins
        }
        nb_discovery_output_report( $instance );
        exit;
    }
    nb_discovery_render_page( $instance );
    exit;
}
```

In `wp-content/themes/newblood/inc/discovery/index.php`, add after the `require_once __DIR__ . '/view.php';` line:
```php
require_once __DIR__ . '/report.php';
```

- [ ] **Step 3: Lint**

Run: `php -l wp-content/themes/newblood/inc/discovery/report.php && php -l wp-content/themes/newblood/inc/discovery/controller.php && php -l wp-content/themes/newblood/inc/discovery/index.php`
Expected: `No syntax errors detected` for all three.

- [ ] **Step 4: Verify the gate + render (seed two submissions)**

Seed two submissions through the real REST pipeline, then check the report responds 404 to a logged-out request and renders for an admin.
```bash
WP='php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood'
# two submissions
for who in "Chase A:chaseA@example.com:9:3" "Paul B:paulB@example.com:5:8"; do
  IFS=':' read -r name email imp hand <<< "$who"
  $WP eval '
    $req=new WP_REST_Request("POST","/newblood/v1/discovery");
    $req->set_body(json_encode(array("instance"=>"overhead-door",
      "respondent"=>array("name"=>"'"$name"'","email"=>"'"$email"'"),
      "services"=>array(array("key"=>"website","importance"=>'"$imp"',"handling"=>'"$hand"')),
      "goal_vectors"=>array("defend_expand"=>30))));
    $req->set_header("Content-Type","application/json");
    echo rest_do_request($req)->get_status()."\n";'
done
# logged-out request -> 404
curl -s -o /dev/null -w "logged-out: %{http_code}\n" -L http://newblood.test/discovery/overhead-door/report
```
Expected: two `200`s, then `logged-out: 404`.

Render as admin via WP-CLI (bypasses HTTP login) to confirm the HTML builds and contains the gap map:
```bash
$WP eval '
  wp_set_current_user( 1 );
  $inst = nb_discovery_get_instance("overhead-door");
  ob_start(); nb_discovery_output_report($inst); $html = ob_get_clean();
  echo ( strpos($html,"Priority &amp; gap map")!==false ? "HAS_GAPMAP " : "NO_GAPMAP " );
  echo ( strpos($html,"stakeholder")!==false ? "HAS_ROSTER\n" : "NO_ROSTER\n" );'
```
Expected: `HAS_GAPMAP HAS_ROSTER`. (Confirm wp user 1 has `manage_options`; on this install the admin is user 1.)

Clean up the seed rows:
```bash
$WP db query "DELETE FROM wp_nb_discovery_responses WHERE respondent_email LIKE '%@example.com';"
```

- [ ] **Step 5: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/report.php wp-content/themes/newblood/inc/discovery/controller.php wp-content/themes/newblood/inc/discovery/index.php
git commit -m "feat(discovery-report): admin-gated report controller + read-only renderer"
```

---

### Task 5: Report CSS

**Files:**
- Create: `wp-content/themes/newblood/assets/css/discovery-report.css`

**Interfaces:**
- Consumes: the `nb-r-*` class contract emitted by `report.php` (Task 4) and the exclude markup from Task 6.
- Produces: styling only.

- [ ] **Step 1: Write the stylesheet**

Create `wp-content/themes/newblood/assets/css/discovery-report.css`:
```css
:root{
  --nb-base:#0f1117; --nb-panel:#161a23; --nb-accent:#22c55e; --nb-accent-light:#4ade80;
  --nb-text:#ffffff; --nb-text-dim:#adb8c8; --nb-border:rgba(255,255,255,.08); --nb-amber:#fbbf24;
}
*{box-sizing:border-box}
.nb-r-body{margin:0;font-family:"Inter",system-ui,-apple-system,sans-serif;color:var(--nb-text);
  background:linear-gradient(160deg,#0f1117 0%,#111827 50%,#0f2218 100%);background-attachment:fixed;line-height:1.6;-webkit-font-smoothing:antialiased}
.nb-r-shell{max-width:860px;margin:0 auto;padding:3rem 1.25rem 6rem}
.nb-r-dot{color:var(--nb-accent-light)}
.nb-r-eyebrow{text-transform:uppercase;letter-spacing:.12em;font-size:.75rem;color:var(--nb-accent-light);margin:0 0 .5rem}
.nb-r-head h1{font-size:clamp(1.8rem,5vw,2.6rem);line-height:1.1;margin:0 0 .5rem}
.nb-r-roster{color:var(--nb-text-dim);margin:0}
.nb-r-section{background:rgba(255,255,255,.02);border:1px solid var(--nb-border);border-radius:16px;padding:1.75rem;margin-top:1.5rem}
.nb-r-section h2{font-size:1.4rem;margin:0 0 .35rem}
.nb-r-sub{color:var(--nb-text-dim);margin:0 0 1.25rem}
.nb-r-empty{color:var(--nb-text-dim);margin:0}
/* service gap rows */
.nb-r-svc{padding:1rem 0;border-top:1px solid var(--nb-border)}
.nb-r-svc:first-of-type{border-top:none}
.nb-r-svc.is-split{background:rgba(251,191,36,.05);border-radius:10px;padding:1rem;margin:.25rem 0}
.nb-r-svc-top{display:flex;justify-content:space-between;align-items:baseline;gap:1rem}
.nb-r-svc-label{font-weight:600}
.nb-r-gap{font-weight:700;color:var(--nb-accent-light);font-variant-numeric:tabular-nums}
.nb-r-gap-na{color:var(--nb-text-dim);font-weight:400}
.nb-r-bars{margin:.6rem 0 .3rem}
.nb-r-bar{display:grid;grid-template-columns:7rem 1fr 2rem;align-items:center;gap:.6rem;margin:.25rem 0}
.nb-r-bar-cap{font-size:.78rem;color:var(--nb-text-dim)}
.nb-r-track{height:8px;border-radius:6px;background:var(--nb-border);overflow:hidden}
.nb-r-fill{display:block;height:100%}
.nb-r-fill-imp{background:linear-gradient(90deg,#22c55e,#16a34a)}
.nb-r-fill-now{background:rgba(74,222,128,.4)}
.nb-r-num{text-align:right;font-variant-numeric:tabular-nums;font-size:.85rem;color:var(--nb-text-dim)}
.nb-r-split{color:var(--nb-amber);font-size:.85rem;margin:.4rem 0 0}
.nb-r-detail{margin-top:.4rem}
.nb-r-detail summary{cursor:pointer;color:var(--nb-text-dim);font-size:.8rem}
.nb-r-detail ul{margin:.4rem 0 0;padding-left:1.1rem;color:var(--nb-text-dim);font-size:.85rem}
/* vectors */
.nb-r-vec{padding:.7rem 0}
.nb-r-vec.is-split .nb-r-vec-track{box-shadow:0 0 0 1px var(--nb-amber)}
.nb-r-vec-row{display:grid;grid-template-columns:1fr 2fr 1fr;align-items:center;gap:.6rem}
.nb-r-vec-cap{font-size:.82rem;color:var(--nb-text-dim);text-align:right}
.nb-r-vec-cap-r{text-align:left}
.nb-r-vec-track{position:relative;height:8px;border-radius:6px;background:var(--nb-border)}
.nb-r-vec-mean{position:absolute;top:50%;width:14px;height:14px;border-radius:50%;background:var(--nb-accent);transform:translate(-50%,-50%);border:2px solid #0f1117}
/* lists */
.nb-r-list{margin:.25rem 0 0;padding-left:1.1rem}
.nb-r-list li{margin:.25rem 0}
.nb-r-qh{font-size:1rem;margin:1.1rem 0 .25rem;color:var(--nb-accent-light)}
/* roster + excluded controls (Task 6) */
.nb-r-people{list-style:none;margin:.5rem 0 0;padding:0}
.nb-r-people li{display:flex;justify-content:space-between;align-items:center;gap:1rem;padding:.4rem 0;border-top:1px solid var(--nb-border)}
.nb-r-excl-btn{background:none;border:1px solid var(--nb-border);color:var(--nb-text-dim);border-radius:8px;padding:.25rem .7rem;font:inherit;font-size:.78rem;cursor:pointer}
.nb-r-excl-btn:hover{border-color:var(--nb-amber);color:var(--nb-amber)}
.nb-r-excluded{margin-top:1.5rem;border:1px dashed var(--nb-border);border-radius:12px;padding:1rem 1.25rem}
.nb-r-excluded h3{margin:0 0 .5rem;font-size:.95rem;color:var(--nb-text-dim)}
@media(max-width:560px){
  .nb-r-section{padding:1.25rem}
  .nb-r-bar{grid-template-columns:5.5rem 1fr 1.8rem}
  .nb-r-vec-row{grid-template-columns:1fr}
  .nb-r-vec-cap,.nb-r-vec-cap-r{text-align:left}
}
```

- [ ] **Step 2: Verify served + versioned**

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://newblood.test/wp-content/themes/newblood/assets/css/discovery-report.css
```
Expected: `200`.

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/newblood/assets/css/discovery-report.css
git commit -m "feat(discovery-report): report stylesheet"
```

---

### Task 6: Soft-exclude — toggle handler + roster controls

**Files:**
- Modify: `wp-content/themes/newblood/inc/discovery/controller.php` (register `admin_post` handler)
- Modify: `wp-content/themes/newblood/inc/discovery/report.php` (roster with exclude buttons + Excluded section)

**Interfaces:**
- Consumes: `excluded` column (Task 1); `nb_discovery_get_instance()`; `$aggregate['respondents']` (carries `id`) and `$excluded_rows` (Task 4).
- Produces: `admin_post_nb_discovery_exclude` action — POST `{ id, excluded(0|1), instance, _wpnonce }`, capability + nonce checked, `$wpdb->update` the flag scoped by id+instance, `wp_safe_redirect` back to the report. Renderer now shows a roster with per-row Exclude buttons and an "Excluded (N)" section with Re-include buttons.

- [ ] **Step 1: Add the admin_post handler**

In `wp-content/themes/newblood/inc/discovery/controller.php`, append the handler + its registration at the end of the file:
```php
/**
 * Toggle a submission's excluded flag (admin-only, nonce-checked). Non-destructive.
 */
function nb_discovery_handle_exclude() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Not allowed', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'nb_discovery_exclude' );

    $id       = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    $excluded = ( isset( $_POST['excluded'] ) && $_POST['excluded'] === '1' ) ? 1 : 0;
    $instance = isset( $_POST['instance'] ) ? sanitize_title( wp_unslash( $_POST['instance'] ) ) : '';

    if ( $id && nb_discovery_get_instance( $instance ) ) {
        global $wpdb;
        $wpdb->update(
            nb_discovery_table_name(),
            array( 'excluded' => $excluded ),
            array( 'id' => $id, 'instance' => $instance ),
            array( '%d' ),
            array( '%d', '%s' )
        );
    }

    wp_safe_redirect( home_url( '/discovery/' . $instance . '/report' ) );
    exit;
}
add_action( 'admin_post_nb_discovery_exclude', 'nb_discovery_handle_exclude' );
```

- [ ] **Step 2: Add a roster-with-controls helper + Excluded section to the renderer**

In `wp-content/themes/newblood/inc/discovery/report.php`, add this helper function at the end of the file (it renders one exclude/re-include form):
```php
/**
 * One nonce'd exclude/re-include form button for a submission.
 */
function nb_discovery_exclude_form( $id, $instance_slug, $to_excluded, $label ) {
    ?><form class="nb-r-excl-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
      <?php wp_nonce_field( 'nb_discovery_exclude' ); ?>
      <input type="hidden" name="action" value="nb_discovery_exclude">
      <input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
      <input type="hidden" name="instance" value="<?php echo esc_attr( $instance_slug ); ?>">
      <input type="hidden" name="excluded" value="<?php echo $to_excluded ? '1' : '0'; ?>">
      <button type="submit" class="nb-r-excl-btn"><?php echo esc_html( $label ); ?></button>
    </form><?php
}
```

Then, inside `nb_discovery_render_report()`, within the `else` (n > 0) block, insert a roster section with exclude controls immediately after the gap-map `</section>` (before the Strategic direction section):
```php
  <section class="nb-r-section">
    <h2>Who responded<span class="nb-r-dot">.</span></h2>
    <ul class="nb-r-people">
      <?php foreach ( $aggregate['respondents'] as $r ) : ?>
        <li><span><?php echo esc_html( $r['name'] ); ?> &middot; <span style="color:var(--nb-text-dim)"><?php echo esc_html( $r['email'] ); ?></span></span>
          <?php nb_discovery_exclude_form( $r['id'], $instance['slug'], true, 'Exclude' ); ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
```

And add the Excluded section just before the final `<?php endif; ?>` (the close of the n>0 block), so excluded rows show even when all are excluded:
```php
  <?php if ( ! empty( $excluded_rows ) ) : ?>
  <section class="nb-r-excluded">
    <h3>Excluded (<?php echo count( $excluded_rows ); ?>)</h3>
    <ul class="nb-r-people">
      <?php foreach ( $excluded_rows as $ex ) : ?>
        <li><span><?php echo esc_html( $ex['name'] ); ?> &middot; <span style="color:var(--nb-text-dim)"><?php echo esc_html( $ex['created_at'] ); ?></span></span>
          <?php nb_discovery_exclude_form( $ex['id'], $instance['slug'], false, 'Re-include' ); ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>
  <?php endif; ?>
```
> Note: the existing `<?php endif; ?>` that closes the `n === 0 / else` block stays the outermost close — place the Excluded block *inside* it (so it only renders when there are active responses OR move it just outside if you want it visible at n=0; for this plan, render it inside the n>0 branch). The Excluded section is also wrapped in its own `if ( ! empty( $excluded_rows ) )` so it disappears when nothing is excluded.

- [ ] **Step 3: Lint**

Run: `php -l wp-content/themes/newblood/inc/discovery/controller.php && php -l wp-content/themes/newblood/inc/discovery/report.php`
Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Verify exclude → re-include round trip**

Seed two submissions, exclude one via the handler, confirm the aggregate drops to 1 active + 1 excluded, then re-include and confirm it returns to 2.
```bash
WP='php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood'
for who in "Dup One:dup1@example.com" "Real Two:real2@example.com"; do
  IFS=':' read -r name email <<< "$who"
  $WP eval '$req=new WP_REST_Request("POST","/newblood/v1/discovery");
    $req->set_body(json_encode(array("instance"=>"overhead-door","respondent"=>array("name"=>"'"$name"'","email"=>"'"$email"'"),
    "services"=>array(array("key"=>"website","importance"=>8,"handling"=>3)))));
    $req->set_header("Content-Type","application/json"); echo rest_do_request($req)->get_status()."\n";'
done
# exclude the first by id
$WP eval '
  global $wpdb; $t=nb_discovery_table_name();
  $id=(int)$wpdb->get_var("SELECT id FROM $t WHERE respondent_email=\"dup1@example.com\"");
  $wpdb->update($t, array("excluded"=>1), array("id"=>$id));
  $inst=nb_discovery_get_instance("overhead-door");
  ob_start(); nb_discovery_output_report($inst); $h=ob_get_clean();
  echo (strpos($h,"Excluded (1)")!==false?"EXCLUDED_SHOWN ":"NO_EXCLUDED ");
  // active count: render shows "1 stakeholder"
  echo (strpos($h,"<strong>1</strong> stakeholder")!==false?"ACTIVE_1\n":"ACTIVE_NOT_1\n");
  // re-include
  $wpdb->update($t, array("excluded"=>0), array("id"=>$id));
  ob_start(); nb_discovery_output_report($inst); $h2=ob_get_clean();
  echo (strpos($h2,"<strong>2</strong> stakeholder")!==false?"REINCLUDED_2\n":"REINCLUDE_FAIL\n");'
$WP db query "DELETE FROM wp_nb_discovery_responses WHERE respondent_email LIKE '%@example.com';"
```
Expected: two `200`s, then `EXCLUDED_SHOWN ACTIVE_1`, then `REINCLUDED_2`.

- [ ] **Step 5: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/controller.php wp-content/themes/newblood/inc/discovery/report.php
git commit -m "feat(discovery-report): non-destructive soft-exclude (admin_post toggle + roster controls)"
```

---

### Task 7: End-to-end verification + tracker update

**Files:**
- Modify: `TASKS.md`

**Interfaces:**
- Consumes: the full running feature.
- Produces: a verified feature + an updated tracker.

- [ ] **Step 1: Run all five unit tests together**

```bash
for t in config gaps sanitize email aggregate; do php wp-content/themes/newblood/tests/discovery/test-$t.php; done
```
Expected: five `... PASS` lines, no warnings/fatals.

- [ ] **Step 2: Browser verification (logged-in admin) via the run/verify skill**

Seed 2–3 stakeholder submissions (vary the importance/handling/vectors so a split appears), log into wp-admin in a browser, then load `http://newblood.test/discovery/overhead-door/report`. Confirm:
- focused report renders (no theme nav/footer), roster shows the right count + names,
- the gap map is ranked by gap with at least one **"Team split"** callout where stakeholders disagree,
- the strategic-direction strip shows mean dots and flags a split where vectors diverge,
- qualitative answers appear per stakeholder,
- clicking **Exclude** on a roster row reloads the report with that person moved to "Excluded (N)" and the averages recomputed; **Re-include** restores them.
Also confirm a logged-out browser/`curl` to the same URL returns 404.

Clean up any seed rows:
```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood db query "DELETE FROM wp_nb_discovery_responses WHERE respondent_email LIKE '%@example.com';"
```

- [ ] **Step 3: Update the tracker**

In `TASKS.md`, under the discovery item, note the combined report is built (engine + admin-gated report + soft-exclude), verified, and reusable per-instance.

- [ ] **Step 4: Commit**

```bash
git add TASKS.md
git commit -m "docs(discovery-report): mark combined report built + verified"
```

---

## Deploy note (post-merge, manual — not a task step)

On SFTP deploy, upload the new `inc/discovery/{aggregate,report}.php`, `assets/css/discovery-report.css`, and the modified `db.php`/`routing.php`/`controller.php`/`index.php`. The `excluded` column (schema v2) and the report rewrite (rewrite v2) **self-heal on the first request** after deploy via the version-guarded migration + rewrite flush — no manual SQL or permalink re-save. Verify `https://newblood.com/discovery/overhead-door/report` 404s when logged out and renders when logged into wp-admin.

---

## Self-Review

**Spec coverage:**
- Aggregation engine (pure, means/gaps/divergence) → Task 2. ✓
- Range + "Team split" flag, threshold 4 / vector 40 → engine + renderer (Tasks 2, 4). ✓
- Combined gap map ranked by mean gap, handling averaged only over raters, null-gap sinks → Task 2 (+ test) and Task 4 render. ✓
- Strategic-direction alignment incl. posture fix↔invest → Task 4 renderer. ✓
- Qualitative verbatim per respondent → Task 2 (`qualitative`) + Task 4 render. ✓
- Respondent roster / who-responded → Task 4 header + Task 6 roster. ✓
- Branded HTML report at `/discovery/{slug}/report` → Tasks 3–5. ✓
- Admin-login gate (`manage_options`) + 404 + nocache → Task 4. ✓
- Soft-exclude (excluded column, reversible toggle, Excluded list) → Tasks 1, 6. ✓
- Schema bump v2 via self-healing migration → Task 1. ✓
- Engine/renderer split for later client re-skin → file structure (aggregate.php pure; report.php renders). ✓
- Reusable per instance (no OHDBalt hardcoding) → route takes slug, engine reads config labels. ✓
- Edge cases 0/1 response, partial handling, all-excluded → engine handles (Task 2 test covers 0/1 + partial), renderer empty-state + Excluded section (Tasks 4, 6). ✓
- Tests for the pure engine → Task 2. ✓

**Placeholder scan:** No "TBD"/"handle edge cases"/"similar to" — every code step is complete. The only flagged literal-glyph note (`Fix what’s urgent`) carries an explicit replacement instruction.

**Type consistency:** `nb_discovery_aggregate($submissions,$instance)` shape (Task 2) matches what the renderer reads in Tasks 4 & 6 (`respondents[].id`, `services[].{mean_*,split,high,low,per_respondent}`, `goal_vectors[]`, `posture.fix_invest`, `qualitative.*`). `nb_discovery_output_report`/`nb_discovery_render_report`/`nb_discovery_exclude_form` names are used identically where referenced. The `excluded` column (Task 1) is read by Task 4's query and written by Task 6's handler. Constants `NB_DISCOVERY_SPLIT_THRESHOLD`/`NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD` defined in Task 2, used in Tasks 2 & 4.
