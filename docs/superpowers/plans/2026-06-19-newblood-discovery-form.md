# New Blood Discovery Form Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a branded, config-driven self-serve discovery form at `/discovery/overhead-door` on newblood.com that captures a prospect's service priorities (dual-axis gap map), forward goals, systems inventory, and posture, stores each submission in a custom DB table, and emails a formatted summary to New Blood.

**Architecture:** Theme-resident module under `wp-content/themes/newblood/inc/discovery/`. A `template_redirect` front-controller serves a **standalone full-page HTML document** (no site header/footer) for the rewrite route `/discovery/{client}`, rendering all form fields server-side from a per-client PHP config. The browser collects answers and POSTs JSON to a REST endpoint (`newblood/v1/discovery`), which sanitizes, computes importance-vs-current **gap scores** server-side, inserts a row into `{$prefix}nb_discovery_responses`, and sends a formatted summary email. OHDBalt is configured instance #1; new clients are config-only additions.

**Tech Stack:** PHP (WordPress, no framework), vanilla JS (IIFE, fetch, IntersectionObserver), CSS custom properties reusing the theme palette. No build step. dbDelta for schema. WP-CLI for verification. Standalone PHP CLI scripts (with WP-function stubs) for pure-logic unit tests — the codebase has no PHPUnit harness and we will not add one.

## Global Constraints

- **WP-CLI invocation:** always `php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood <subcommand>` (the install fatals at the default 128M).
- **Local dev URL:** `http://newblood.test` (Laravel Herd). Production: Nexcess SFTP, no CI.
- **Active branch:** `feature/redesign`.
- **Cache-busting is automatic:** asset versions use `filemtime()` via `newblood_asset_version()`. Never add `?ver=` strings or bump constants.
- **Image / asset URLs in rendered output MUST be root-relative** (`/wp-content/...`), never absolute (`http://newblood.test/...`) — absolute URLs break on production.
- **Run `php -l <file>` after every PHP edit** — theme PHP is parsed at request time.
- **Palette (from `theme.json`):** base `#0f1117`, accent green `#22c55e`, accent-light `#4ade80`, text-primary `#ffffff`, text-secondary `#adb8c8`, border `rgba(255,255,255,0.08)`. Primary gradient `linear-gradient(160deg,#0f1117 0%,#111827 50%,#0f2218 100%)`.
- **Importance threshold that reveals the "handled today?" slider = 7** (scores are 0–10).
- **Bipolar / vector sliders range −50..+50, default 0.**
- **Voice:** deliberative, not fast. Never frame work as quick/in-days. Musical second-meanings only where they read as natural English first.
- **Recipient email for submissions:** `joms@newblood.com`.
- **Email subject format:** `New Blood Discovery — {client_name} ({respondent_name})`.
- **OHDBalt is intentionally unhighlighted on the public site** — this route is unlinked from nav; reachable only by the direct URL Jeremy emails.

---

## File Structure

**New files (all under `wp-content/themes/newblood/`):**

- `inc/discovery/index.php` — module bootstrap; `require_once`s the other module files. One line added to `functions.php` loads this.
- `inc/discovery/config.php` — `nb_discovery_instances()` returns the per-client config array; `nb_discovery_get_instance( $slug )` looks one up. Holds OHDBalt's branding, 12 service rows + one-liners, bipolar pairs, section copy, recipient.
- `inc/discovery/db.php` — `nb_discovery_table_name()`, `nb_discovery_install_table()` (dbDelta), version-guarded migration runner on `after_setup_theme`.
- `inc/discovery/routing.php` — rewrite rule + query var for `/discovery/{slug}`; version-guarded `flush_rewrite_rules`.
- `inc/discovery/controller.php` — `template_redirect` handler: validates slug → renders standalone page via the view, or lets WP 404.
- `inc/discovery/view.php` — `nb_discovery_render_page( $instance )` emits the full `<!doctype html>` document and all form fields from config; enqueues + localizes assets.
- `inc/discovery/submission.php` — pure helpers `nb_discovery_sanitize_payload()`, `nb_discovery_compute_gaps()`; REST route registration + handler `nb_discovery_handle_submit()`.
- `inc/discovery/email.php` — `nb_discovery_format_email( $record, $instance )` builds the plain-text summary; `nb_discovery_send_email()`.
- `assets/css/discovery.css` — focused-canvas layout, sliders, sections, branding, thank-you, mobile.
- `assets/js/discovery.js` — slider readouts, progressive reveal (threshold 7), payload assembly, fetch POST, thank-you swap, reduced-motion.
- `tests/discovery/bootstrap.php` — WP-function stubs so pure-logic files run under plain `php` CLI.
- `tests/discovery/test-config.php`, `test-gaps.php`, `test-sanitize.php`, `test-email.php` — standalone assertion scripts.

**Modified files:**

- `functions.php` — one `require_once` of `inc/discovery/index.php` (near the top, after `newblood_asset_version()`).

**Stable keys (used across config, JS payload, gap computation, email — do not rename between tasks):**

- Service keys (in display order): `website`, `seo_aeo`, `hosting_security`, `content`, `reviews`, `lead_gen`, `lead_capture`, `customer_comms`, `crm`, `automation_ai`, `reporting`, `brand_creative`.
- Goal-vector keys: `residential_commercial`, `leads_volume_quality`, `topline_lean`, `defend_expand`, `handson_managed`.
- Posture keys: `fix_invest` (slider −50..50), `timeline` (select).
- Systems keys: `crm`, `lead_handling`, `reviews_system`, `call_tracking`, `gbp_access` (`yes|no|unsure`), `territories`.

**Canonical payload shape (what `discovery.js` POSTs and what is stored under `payload`):**

```json
{
  "instance": "overhead-door",
  "respondent": { "name": "string", "email": "string" },
  "services": [ { "key": "website", "importance": 9, "handling": 4 } ],
  "vision": "string",
  "goal_vectors": { "residential_commercial": 0, "leads_volume_quality": 0, "topline_lean": 0, "defend_expand": 0, "handson_managed": 0 },
  "systems": { "crm": "", "lead_handling": "", "reviews_system": "", "call_tracking": "", "gbp_access": "unsure", "territories": "" },
  "posture": { "fix_invest": 0, "timeline": "" },
  "open": "string"
}
```

`handling` is omitted/`null` for services rated below threshold. `gap` is **never trusted from the client** — it is computed server-side as `importance - handling` when `handling` is present, else `null`.

---

### Task 1: Module bootstrap + per-client config

**Files:**
- Create: `wp-content/themes/newblood/inc/discovery/index.php`
- Create: `wp-content/themes/newblood/inc/discovery/config.php`
- Create: `wp-content/themes/newblood/tests/discovery/bootstrap.php`
- Create: `wp-content/themes/newblood/tests/discovery/test-config.php`
- Modify: `wp-content/themes/newblood/functions.php:16` (add require after `newblood_asset_version()`)

**Interfaces:**
- Produces: `nb_discovery_instances(): array` (keyed by slug); `nb_discovery_get_instance( string $slug ): ?array` (returns config or `null`). Each instance array has keys: `slug`, `client_name`, `logo` (root-relative path or `''`), `recipient`, `welcome` (`title`, `intro`), `services` (ordered list of `[key,label,hint]`), `goal_vectors` (ordered list of `[key,left,right]`), `timeline_options` (list of strings), `section_copy` (map of section → `[title, subtitle]`).
- Produces: `inc/discovery/index.php` is the single module entrypoint; later tasks append `require_once` lines to it.

- [ ] **Step 1: Write the failing test**

Create `wp-content/themes/newblood/tests/discovery/bootstrap.php`:

```php
<?php
// Minimal WP-function stubs so pure-logic module files run under plain PHP CLI.
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ . '/' );
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t]+/', ' ', strip_tags( (string) $s ) ) ); }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }
}
if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $s ) { return filter_var( trim( (string) $s ), FILTER_SANITIZE_EMAIL ); }
}
if ( ! function_exists( 'esc_html' ) ) { function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); } }
if ( ! function_exists( 'esc_attr' ) ) { function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); } }
if ( ! function_exists( 'absint' ) ) { function absint( $n ) { return abs( (int) $n ); } }
```

Create `wp-content/themes/newblood/tests/discovery/test-config.php`:

```php
<?php
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/config.php';

$inst = nb_discovery_get_instance( 'overhead-door' );
assert( is_array( $inst ), 'overhead-door instance exists' );
assert( $inst['client_name'] === 'Overhead Door Company of Baltimore', 'client name set' );
assert( $inst['recipient'] === 'joms@newblood.com', 'recipient set' );
assert( count( $inst['services'] ) === 12, '12 service rows' );

$keys = array_column( $inst['services'], 'key' );
$expected = array( 'website','seo_aeo','hosting_security','content','reviews','lead_gen','lead_capture','customer_comms','crm','automation_ai','reporting','brand_creative' );
assert( $keys === $expected, 'service keys match canonical order' );
foreach ( $inst['services'] as $s ) {
    assert( ! empty( $s['label'] ) && ! empty( $s['hint'] ), "service {$s['key']} has label + hint" );
}
assert( count( $inst['goal_vectors'] ) === 5, '5 goal vectors' );
$vkeys = array_column( $inst['goal_vectors'], 'key' );
assert( $vkeys === array( 'residential_commercial','leads_volume_quality','topline_lean','defend_expand','handson_managed' ), 'vector keys match' );

assert( nb_discovery_get_instance( 'nope' ) === null, 'unknown slug returns null' );
echo "test-config: PASS\n";
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php wp-content/themes/newblood/tests/discovery/test-config.php`
Expected: FAIL — fatal `Call to undefined function nb_discovery_get_instance()` (config.php does not exist yet).

- [ ] **Step 3: Write minimal implementation**

Create `wp-content/themes/newblood/inc/discovery/config.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * All configured discovery instances, keyed by URL slug.
 * A new client = a new entry here. OHDBalt is instance #1.
 */
function nb_discovery_instances() {
    return array(
        'overhead-door' => array(
            'slug'        => 'overhead-door',
            'client_name' => 'Overhead Door Company of Baltimore',
            'logo'        => '', // root-relative path once a logo asset is placed; '' hides it
            'recipient'   => 'joms@newblood.com',
            'welcome'     => array(
                'title' => 'Let’s build your plan around you.',
                'intro' => 'Thank you for the chance to put together the full picture. The questions below take about 10 minutes — your answers shape a plan built around Overhead Door, not a template.',
            ),
            'services' => array(
                array( 'key' => 'website',          'label' => 'Website design & user experience',        'hint' => 'How the site looks, feels, and guides visitors.' ),
                array( 'key' => 'seo_aeo',          'label' => 'Search & AI-answer visibility (SEO/AEO)', 'hint' => 'Being found in Google search and in AI answers like ChatGPT.' ),
                array( 'key' => 'hosting_security', 'label' => 'Hosting, security & maintenance',         'hint' => 'Keeping the site fast, online, secure, and up to date.' ),
                array( 'key' => 'content',          'label' => 'Content',                                 'hint' => 'Service pages, FAQs, and fresh content over time.' ),
                array( 'key' => 'reviews',          'label' => 'Reviews & online reputation',             'hint' => 'Earning, showcasing, and responding to reviews.' ),
                array( 'key' => 'lead_gen',         'label' => 'Lead generation',                         'hint' => 'Driving new prospects through paid search and social ads.' ),
                array( 'key' => 'lead_capture',     'label' => 'Lead capture & conversion',               'hint' => 'Turning visitors into inquiries — forms, funnels, calls-to-action.' ),
                array( 'key' => 'customer_comms',   'label' => 'Customer communication',                  'hint' => 'Following up with leads and customers by email and text.' ),
                array( 'key' => 'crm',              'label' => 'CRM / customer & job pipeline',           'hint' => 'One place to track customers and jobs from inquiry to close.' ),
                array( 'key' => 'automation_ai',    'label' => 'Automation & AI assistants',              'hint' => 'Automated routing and on-site AI chat that answers and books.' ),
                array( 'key' => 'reporting',        'label' => 'Reporting & analytics',                   'hint' => 'Clear reporting on what’s working and what it’s producing.' ),
                array( 'key' => 'brand_creative',   'label' => 'Brand & creative',                        'hint' => 'Logo, photography, and video that present the brand well.' ),
            ),
            'goal_vectors' => array(
                array( 'key' => 'residential_commercial', 'left' => 'More residential',     'right' => 'More commercial' ),
                array( 'key' => 'leads_volume_quality',   'left' => 'More leads (volume)',  'right' => 'Better leads (quality)' ),
                array( 'key' => 'topline_lean',           'left' => 'Grow the top line',    'right' => 'Run leaner' ),
                array( 'key' => 'defend_expand',          'left' => 'Defend our territory', 'right' => 'Expand into new areas' ),
                array( 'key' => 'handson_managed',        'left' => 'We stay hands-on',     'right' => 'Fully managed for us' ),
            ),
            'timeline_options' => array( 'As soon as possible', 'Within 1–3 months', '3–6 months', 'Just exploring' ),
            'section_copy' => array(
                'priorities' => array( 'What matters most', 'Rate how important each capability is to you. Where it’s critical, we’ll ask how well it’s handled today.' ),
                'goals'      => array( 'Where you’re headed', 'A few questions about the direction of the business.' ),
                'systems'    => array( 'What’s in place today', 'Light context on your current systems — a sentence each is plenty.' ),
                'direction'  => array( 'Direction & timing', 'How you’re thinking about this work.' ),
                'open'       => array( 'Anything else', 'The floor is yours.' ),
            ),
        ),
    );
}

/**
 * Look up one instance by slug. Returns null if unknown.
 */
function nb_discovery_get_instance( $slug ) {
    $all = nb_discovery_instances();
    return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
}
```

> Note: in the code above, replace each `…` / `–` / `—` / `’` placeholder with the literal UTF-8 character (`…`, `–`, `—`, `’`). They are written as escapes here only to survive plan transport; the file must contain real glyphs.

Create `wp-content/themes/newblood/inc/discovery/index.php`:

```php
<?php
/**
 * New Blood Discovery module bootstrap.
 * Self-serve, config-driven client discovery form. See
 * docs/superpowers/specs/2026-06-19-newblood-discovery-form-design.md
 */
if ( ! defined( 'ABSPATH' ) ) exit;

require_once __DIR__ . '/config.php';
// Later tasks append: db.php, routing.php, controller.php, submission.php, email.php
```

- [ ] **Step 4: Wire the module into functions.php**

In `wp-content/themes/newblood/functions.php`, immediately after line 16 (the closing `}` of `newblood_asset_version()`), add:

```php

// Discovery form module (self-serve client intake).
require_once get_template_directory() . '/inc/discovery/index.php';
```

- [ ] **Step 5: Run test + lint to verify they pass**

Run: `php wp-content/themes/newblood/tests/discovery/test-config.php`
Expected: `test-config: PASS`

Run: `php -l wp-content/themes/newblood/inc/discovery/config.php && php -l wp-content/themes/newblood/inc/discovery/index.php && php -l wp-content/themes/newblood/functions.php`
Expected: `No syntax errors detected` for all three.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/index.php wp-content/themes/newblood/inc/discovery/config.php wp-content/themes/newblood/tests/discovery/ wp-content/themes/newblood/functions.php
git commit -m "feat(discovery): module bootstrap + OHDBalt config"
```

---

### Task 2: Database table + version-guarded migration

**Files:**
- Create: `wp-content/themes/newblood/inc/discovery/db.php`
- Modify: `wp-content/themes/newblood/inc/discovery/index.php` (append require)

**Interfaces:**
- Consumes: nothing from other tasks.
- Produces: `nb_discovery_table_name(): string` (returns `{$wpdb->prefix}nb_discovery_responses`); `nb_discovery_install_table(): void` (idempotent dbDelta). Table columns: `id` (BIGINT UNSIGNED PK AI), `instance` (VARCHAR 64), `respondent_name` (VARCHAR 191), `respondent_email` (VARCHAR 191), `payload` (LONGTEXT, JSON), `created_at` (DATETIME), `ip` (VARCHAR 45). Migration version constant `NB_DISCOVERY_DB_VERSION = '1'` stored in option `nb_discovery_db_version`.

- [ ] **Step 1: Write the implementation**

This task has no pure-logic unit test (dbDelta needs a live WP DB); verification is via WP-CLI in Step 3.

Create `wp-content/themes/newblood/inc/discovery/db.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NB_DISCOVERY_DB_VERSION', '1' );

function nb_discovery_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'nb_discovery_responses';
}

/**
 * Create/upgrade the responses table. Idempotent (dbDelta).
 */
function nb_discovery_install_table() {
    global $wpdb;
    $table   = nb_discovery_table_name();
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        instance VARCHAR(64) NOT NULL DEFAULT '',
        respondent_name VARCHAR(191) NOT NULL DEFAULT '',
        respondent_email VARCHAR(191) NOT NULL DEFAULT '',
        payload LONGTEXT NOT NULL,
        created_at DATETIME NOT NULL,
        ip VARCHAR(45) NOT NULL DEFAULT '',
        PRIMARY KEY (id),
        KEY instance (instance),
        KEY created_at (created_at)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );
    update_option( 'nb_discovery_db_version', NB_DISCOVERY_DB_VERSION );
}

/**
 * Run the migration only when the stored version is behind.
 */
function nb_discovery_maybe_migrate() {
    if ( get_option( 'nb_discovery_db_version' ) !== NB_DISCOVERY_DB_VERSION ) {
        nb_discovery_install_table();
    }
}
add_action( 'after_setup_theme', 'nb_discovery_maybe_migrate' );
```

- [ ] **Step 2: Append require to the module bootstrap**

In `wp-content/themes/newblood/inc/discovery/index.php`, replace the trailing comment line with:

```php
require_once __DIR__ . '/db.php';
// Later tasks append: routing.php, controller.php, submission.php, email.php
```

- [ ] **Step 3: Verify the table is created**

Run: `php -l wp-content/themes/newblood/inc/discovery/db.php`
Expected: `No syntax errors detected`

Trigger the migration (loading any front-end page fires `after_setup_theme`), then inspect:

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood eval 'nb_discovery_install_table(); echo "installed\n";'
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood db query "DESCRIBE $(php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood eval 'echo nb_discovery_table_name();');"
```

Expected: a 7-column description (`id`, `instance`, `respondent_name`, `respondent_email`, `payload`, `created_at`, `ip`).
Run: `php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood option get nb_discovery_db_version`
Expected: `1`

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/db.php wp-content/themes/newblood/inc/discovery/index.php
git commit -m "feat(discovery): responses table + version-guarded migration"
```

---

### Task 3: Rewrite route for /discovery/{slug}

**Files:**
- Create: `wp-content/themes/newblood/inc/discovery/routing.php`
- Modify: `wp-content/themes/newblood/inc/discovery/index.php` (append require)

**Interfaces:**
- Consumes: `nb_discovery_get_instance()` from Task 1 (used by the controller in Task 4; routing only exposes the query var).
- Produces: query var `nb_discovery` holding the requested client slug. Rewrite tag `/discovery/{slug}/?` → `index.php?nb_discovery=$matches[1]`. Rewrite version option `nb_discovery_rewrite_version`.

- [ ] **Step 1: Write the implementation**

Create `wp-content/themes/newblood/inc/discovery/routing.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NB_DISCOVERY_REWRITE_VERSION', '1' );

function nb_discovery_register_query_var( $vars ) {
    $vars[] = 'nb_discovery';
    return $vars;
}
add_filter( 'query_vars', 'nb_discovery_register_query_var' );

function nb_discovery_register_rewrite() {
    add_rewrite_rule( '^discovery/([^/]+)/?$', 'index.php?nb_discovery=$matches[1]', 'top' );
}
add_action( 'init', 'nb_discovery_register_rewrite' );

/**
 * Flush rewrites once per rewrite-rule version change (themes have no
 * activation hook, so we self-heal on the next request after a deploy).
 */
function nb_discovery_maybe_flush_rewrites() {
    if ( get_option( 'nb_discovery_rewrite_version' ) !== NB_DISCOVERY_REWRITE_VERSION ) {
        nb_discovery_register_rewrite();
        flush_rewrite_rules( false );
        update_option( 'nb_discovery_rewrite_version', NB_DISCOVERY_REWRITE_VERSION );
    }
}
add_action( 'init', 'nb_discovery_maybe_flush_rewrites', 20 );
```

- [ ] **Step 2: Append require to the module bootstrap**

In `wp-content/themes/newblood/inc/discovery/index.php`, update the require block:

```php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/routing.php';
// Later tasks append: controller.php, submission.php, email.php
```

- [ ] **Step 3: Verify the rewrite resolves**

Run: `php -l wp-content/themes/newblood/inc/discovery/routing.php`
Expected: `No syntax errors detected`

Force a flush + confirm the rule exists:

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood rewrite flush
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood rewrite list --format=csv | grep discovery
```

Expected: a row matching `^discovery/([^/]+)/?$` → `index.php?nb_discovery=$matches[1]`.

Run: `curl -s -o /dev/null -w "%{http_code}\n" http://newblood.test/discovery/overhead-door`
Expected: `200` (note: until Task 4 adds the controller, this returns 200 but renders the normal theme 404/index body — that's fine for this task; the route just needs to resolve without a server-level 404).

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/routing.php wp-content/themes/newblood/inc/discovery/index.php
git commit -m "feat(discovery): /discovery/{slug} rewrite route"
```

---

### Task 4: Front-controller + standalone page view (server-rendered form)

**Files:**
- Create: `wp-content/themes/newblood/inc/discovery/controller.php`
- Create: `wp-content/themes/newblood/inc/discovery/view.php`
- Modify: `wp-content/themes/newblood/inc/discovery/index.php` (append require)

**Interfaces:**
- Consumes: `nb_discovery_get_instance()` (Task 1); query var `nb_discovery` (Task 3); `assets/css/discovery.css` + `assets/js/discovery.js` (Tasks 5–6, created later — enqueue references are safe before the files exist, they just 404 in the browser until then).
- Produces: `nb_discovery_render_page( array $instance ): void` (echoes a complete HTML document and `exit`s). Localized JS global `nbDiscovery = { endpoint, nonce, threshold, instance }`. Stable DOM contract consumed by `discovery.js` in Task 6:
  - Root form: `<form id="nb-discovery-form" data-instance="{slug}">`
  - Per service row: `<div class="nb-d-service" data-key="{key}">` containing importance `<input type="range" class="nb-d-importance" min="0" max="10" data-key="{key}">`, its readout `<output class="nb-d-importance-out">`, and a hidden handling block `<div class="nb-d-handling" hidden>` with `<input type="range" class="nb-d-handling" min="0" max="10" data-key="{key}">` + `<output class="nb-d-handling-out">`.
  - Vector inputs: `<input type="range" class="nb-d-vector" data-key="{key}" min="-50" max="50" value="0">`.
  - Posture: `<input type="range" class="nb-d-vector" data-key="fix_invest" min="-50" max="50" value="0">`, `<select name="timeline">`.
  - Text fields by `name`: `respondent_name`, `respondent_email`, `vision`, `crm`, `lead_handling`, `reviews_system`, `call_tracking`, `territories`, `open`; radios `name="gbp_access"` values `yes|no|unsure`.
  - Submit: `<button type="submit" id="nb-d-submit">`. Thank-you target: `<div id="nb-d-thankyou" hidden>`.

- [ ] **Step 1: Write the controller**

Create `wp-content/themes/newblood/inc/discovery/controller.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Intercept /discovery/{slug}. Valid slug → standalone page. Unknown slug → 404.
 */
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
    nb_discovery_render_page( $instance );
    exit;
}
add_action( 'template_redirect', 'nb_discovery_template_redirect' );
```

- [ ] **Step 2: Write the view**

Create `wp-content/themes/newblood/inc/discovery/view.php`. This emits the full document; it bypasses the theme header/footer entirely (focused canvas). Enqueue + localize happen inline because we are not in the normal template lifecycle.

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function nb_discovery_render_page( $instance ) {
    $ver_css = newblood_asset_version( '/assets/css/discovery.css' );
    $ver_js  = newblood_asset_version( '/assets/js/discovery.js' );
    $css_uri = get_template_directory_uri() . '/assets/css/discovery.css?v=' . $ver_css;
    $js_uri  = get_template_directory_uri() . '/assets/js/discovery.js?v=' . $ver_js;

    $cfg = wp_json_encode( array(
        'endpoint'  => esc_url_raw( rest_url( 'newblood/v1/discovery' ) ),
        'nonce'     => wp_create_nonce( 'wp_rest' ),
        'threshold' => 7,
        'instance'  => $instance['slug'],
    ) );

    $sc = $instance['section_copy'];
    ?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( 'Discovery — ' . $instance['client_name'] ); ?></title>
<link rel="stylesheet" href="<?php echo esc_url( $css_uri ); ?>">
</head>
<body class="nb-d-body">
<main class="nb-d-shell">

  <header class="nb-d-welcome nb-d-section">
    <?php if ( $instance['logo'] ) : ?>
      <img class="nb-d-logo" src="<?php echo esc_url( $instance['logo'] ); ?>" alt="<?php echo esc_attr( $instance['client_name'] ); ?>">
    <?php endif; ?>
    <p class="nb-d-eyebrow">New Blood × <?php echo esc_html( $instance['client_name'] ); ?></p>
    <h1><?php echo esc_html( $instance['welcome']['title'] ); ?><span class="nb-d-dot">.</span></h1>
    <p class="nb-d-lede"><?php echo esc_html( $instance['welcome']['intro'] ); ?></p>
  </header>

  <form id="nb-discovery-form" data-instance="<?php echo esc_attr( $instance['slug'] ); ?>" novalidate>

    <section class="nb-d-section">
      <h2><?php echo esc_html( $sc['priorities'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['priorities'][1] ); ?></p>
      <div class="nb-d-services">
        <?php foreach ( $instance['services'] as $s ) :
            $k = $s['key']; ?>
        <div class="nb-d-service" data-key="<?php echo esc_attr( $k ); ?>">
          <div class="nb-d-service-head">
            <span class="nb-d-service-label"><?php echo esc_html( $s['label'] ); ?></span>
            <span class="nb-d-service-hint"><?php echo esc_html( $s['hint'] ); ?></span>
          </div>
          <label class="nb-d-slider-row">
            <span class="nb-d-slider-cap">Not a priority</span>
            <input type="range" class="nb-d-importance" data-key="<?php echo esc_attr( $k ); ?>" min="0" max="10" value="0" step="1" aria-label="<?php echo esc_attr( $s['label'] . ' — importance' ); ?>">
            <span class="nb-d-slider-cap">Critical</span>
            <output class="nb-d-importance-out">0</output>
          </label>
          <div class="nb-d-handling" hidden>
            <label class="nb-d-slider-row">
              <span class="nb-d-slider-cap">Poorly</span>
              <input type="range" class="nb-d-handling-input" data-key="<?php echo esc_attr( $k ); ?>" min="0" max="10" value="0" step="1" aria-label="<?php echo esc_attr( $s['label'] . ' — handled today' ); ?>">
              <span class="nb-d-slider-cap">Very well</span>
              <output class="nb-d-handling-out">0</output>
            </label>
            <p class="nb-d-handling-q">How well is this handled today?</p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="nb-d-section">
      <h2><?php echo esc_html( $sc['goals'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['goals'][1] ); ?></p>
      <label class="nb-d-field">
        <span>In 3 years, what does winning look like?</span>
        <textarea name="vision" rows="4"></textarea>
      </label>
      <?php foreach ( $instance['goal_vectors'] as $v ) : ?>
      <div class="nb-d-vector-row">
        <span class="nb-d-vector-cap"><?php echo esc_html( $v['left'] ); ?></span>
        <input type="range" class="nb-d-vector" data-key="<?php echo esc_attr( $v['key'] ); ?>" min="-50" max="50" value="0" step="1" aria-label="<?php echo esc_attr( $v['left'] . ' versus ' . $v['right'] ); ?>">
        <span class="nb-d-vector-cap"><?php echo esc_html( $v['right'] ); ?></span>
      </div>
      <?php endforeach; ?>
    </section>

    <section class="nb-d-section">
      <h2><?php echo esc_html( $sc['systems'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['systems'][1] ); ?></p>
      <label class="nb-d-field"><span>Do you use a CRM today? If so, which one?</span><input type="text" name="crm"></label>
      <label class="nb-d-field"><span>When a web lead comes in today, what happens?</span><textarea name="lead_handling" rows="3"></textarea></label>
      <label class="nb-d-field"><span>Your reviews live in which system?</span><input type="text" name="reviews_system"></label>
      <label class="nb-d-field"><span>Any call-tracking / attribution in place? (e.g., Enspire)</span><input type="text" name="call_tracking"></label>
      <fieldset class="nb-d-field nb-d-radios">
        <legend>Can you grant manager access to your Google Business Profile?</legend>
        <label><input type="radio" name="gbp_access" value="yes"> Yes</label>
        <label><input type="radio" name="gbp_access" value="no"> No</label>
        <label><input type="radio" name="gbp_access" value="unsure" checked> Not sure</label>
      </fieldset>
      <label class="nb-d-field"><span>Which locations / territories should the plan cover?</span><textarea name="territories" rows="2"></textarea></label>
    </section>

    <section class="nb-d-section">
      <h2><?php echo esc_html( $sc['direction'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['direction'][1] ); ?></p>
      <div class="nb-d-vector-row">
        <span class="nb-d-vector-cap">Fix what’s urgent</span>
        <input type="range" class="nb-d-vector" data-key="fix_invest" min="-50" max="50" value="0" step="1" aria-label="Fix what is urgent versus invest for long-term growth">
        <span class="nb-d-vector-cap">Invest for long-term growth</span>
      </div>
      <label class="nb-d-field">
        <span>Ideal timeline to begin?</span>
        <select name="timeline">
          <option value="">Select…</option>
          <?php foreach ( $instance['timeline_options'] as $opt ) : ?>
          <option value="<?php echo esc_attr( $opt ); ?>"><?php echo esc_html( $opt ); ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </section>

    <section class="nb-d-section">
      <h2><?php echo esc_html( $sc['open'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['open'][1] ); ?></p>
      <label class="nb-d-field"><span>Anything we haven’t asked?</span><textarea name="open" rows="4"></textarea></label>
      <label class="nb-d-field"><span>Your name</span><input type="text" name="respondent_name"></label>
      <label class="nb-d-field"><span>Your email</span><input type="email" name="respondent_email"></label>
    </section>

    <div class="nb-d-actions">
      <button type="submit" id="nb-d-submit">Send to New Blood</button>
      <p class="nb-d-error" id="nb-d-error" hidden></p>
    </div>
  </form>

  <div id="nb-d-thankyou" class="nb-d-section" hidden>
    <h2>Thank you<span class="nb-d-dot">.</span></h2>
    <p class="nb-d-lede">We’ll review your answers and prepare a plan built around your priorities. Jeremy will be in touch to walk through it with you.</p>
  </div>

</main>
<script>window.nbDiscovery = <?php echo $cfg; ?>;</script>
<script src="<?php echo esc_url( $js_uri ); ?>" defer></script>
</body>
</html><?php
}
```

> Replace `’` / `…` placeholders with real `’` / `…` glyphs in the actual file.

- [ ] **Step 3: Append requires to the module bootstrap**

In `wp-content/themes/newblood/inc/discovery/index.php`, update:

```php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/routing.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/controller.php';
// Later tasks append: submission.php, email.php
```

- [ ] **Step 4: Verify the page renders server-side**

Run: `php -l wp-content/themes/newblood/inc/discovery/controller.php && php -l wp-content/themes/newblood/inc/discovery/view.php`
Expected: `No syntax errors detected` for both.

```bash
curl -s http://newblood.test/discovery/overhead-door | grep -c 'nb-d-service'
```
Expected: `13` (12 service rows each emit `nb-d-service` on the wrapper + the container class `nb-d-services` matches the grep once = 12 + 1).

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://newblood.test/discovery/nope
```
Expected: `404`.

Confirm no theme chrome leaked in:
```bash
curl -s http://newblood.test/discovery/overhead-door | grep -c 'wp-block-template-part'
```
Expected: `0`.

- [ ] **Step 5: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/controller.php wp-content/themes/newblood/inc/discovery/view.php wp-content/themes/newblood/inc/discovery/index.php
git commit -m "feat(discovery): standalone front-controller + server-rendered form view"
```

---

### Task 5: Discovery CSS (focused canvas, sliders, sections, mobile)

**Files:**
- Create: `wp-content/themes/newblood/assets/css/discovery.css`

**Interfaces:**
- Consumes: the DOM contract / class names from Task 4's view.
- Produces: visual styling only; no JS-facing API.

- [ ] **Step 1: Write the stylesheet**

Create `wp-content/themes/newblood/assets/css/discovery.css`:

```css
:root {
  --nb-base: #0f1117;
  --nb-panel: #161a23;
  --nb-accent: #22c55e;
  --nb-accent-light: #4ade80;
  --nb-text: #ffffff;
  --nb-text-dim: #adb8c8;
  --nb-border: rgba(255,255,255,0.08);
}
* { box-sizing: border-box; }
.nb-d-body {
  margin: 0;
  font-family: "Inter", system-ui, -apple-system, sans-serif;
  color: var(--nb-text);
  background: linear-gradient(160deg,#0f1117 0%,#111827 50%,#0f2218 100%);
  background-attachment: fixed;
  line-height: 1.6;
  -webkit-font-smoothing: antialiased;
}
.nb-d-shell { max-width: 760px; margin: 0 auto; padding: 3rem 1.25rem 6rem; }
.nb-d-dot { color: var(--nb-accent-light); }
.nb-d-eyebrow { text-transform: uppercase; letter-spacing: .12em; font-size: .75rem; color: var(--nb-accent-light); margin: 0 0 .75rem; }
.nb-d-logo { max-height: 56px; width: auto; margin-bottom: 1.5rem; }
.nb-d-welcome h1 { font-size: clamp(1.9rem, 5vw, 2.75rem); line-height: 1.1; margin: 0 0 1rem; }
.nb-d-lede { font-size: 1.125rem; color: var(--nb-text-dim); max-width: 60ch; }
.nb-d-section {
  background: rgba(255,255,255,0.02);
  border: 1px solid var(--nb-border);
  border-radius: 16px;
  padding: 2rem 1.75rem;
  margin-top: 1.5rem;
}
.nb-d-welcome { background: none; border: none; padding: 0; margin-top: 0; }
.nb-d-section h2 { font-size: 1.5rem; margin: 0 0 .35rem; }
.nb-d-sub { color: var(--nb-text-dim); margin: 0 0 1.5rem; }

/* Service rows */
.nb-d-service { padding: 1.1rem 0; border-top: 1px solid var(--nb-border); }
.nb-d-service:first-child { border-top: none; }
.nb-d-service-head { display: flex; flex-direction: column; margin-bottom: .6rem; }
.nb-d-service-label { font-weight: 600; }
.nb-d-service-hint { font-size: .85rem; color: var(--nb-text-dim); }
.nb-d-slider-row { display: grid; grid-template-columns: auto 1fr auto auto; align-items: center; gap: .6rem; }
.nb-d-slider-cap { font-size: .7rem; color: var(--nb-text-dim); white-space: nowrap; }
.nb-d-importance-out, .nb-d-handling-out {
  min-width: 1.6rem; text-align: center; font-variant-numeric: tabular-nums;
  font-weight: 700; color: var(--nb-accent-light);
}
.nb-d-handling { margin-top: .75rem; padding: .85rem; background: rgba(74,222,128,0.06); border-radius: 10px; }
.nb-d-handling-q { font-size: .8rem; color: var(--nb-text-dim); margin: .4rem 0 0; }

/* Vector rows */
.nb-d-vector-row { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: .75rem; margin: 1rem 0; }
.nb-d-vector-cap { font-size: .82rem; color: var(--nb-text-dim); }
.nb-d-vector-row .nb-d-vector-cap:first-child { text-align: right; }

/* Range input styling */
input[type="range"] { -webkit-appearance: none; appearance: none; height: 4px; border-radius: 4px; background: var(--nb-border); outline: none; }
input[type="range"]::-webkit-slider-thumb { -webkit-appearance: none; appearance: none; width: 20px; height: 20px; border-radius: 50%; background: var(--nb-accent); cursor: pointer; border: 3px solid #0f1117; box-shadow: 0 0 0 1px var(--nb-accent); }
input[type="range"]::-moz-range-thumb { width: 20px; height: 20px; border-radius: 50%; background: var(--nb-accent); cursor: pointer; border: 3px solid #0f1117; }

/* Text fields */
.nb-d-field { display: block; margin: 1.1rem 0; }
.nb-d-field > span, .nb-d-field > legend { display: block; font-weight: 600; margin-bottom: .4rem; }
.nb-d-field input[type="text"], .nb-d-field input[type="email"], .nb-d-field textarea, .nb-d-field select {
  width: 100%; padding: .7rem .85rem; background: rgba(0,0,0,0.25);
  border: 1px solid var(--nb-border); border-radius: 10px; color: var(--nb-text);
  font: inherit;
}
.nb-d-field input:focus, .nb-d-field textarea:focus, .nb-d-field select:focus { border-color: var(--nb-accent); outline: none; }
.nb-d-radios { border: none; padding: 0; margin: 1.1rem 0; }
.nb-d-radios label { display: inline-flex; align-items: center; gap: .4rem; margin-right: 1.25rem; font-weight: 400; }

/* Actions + thank-you */
.nb-d-actions { margin-top: 2rem; text-align: center; }
#nb-d-submit {
  background: linear-gradient(135deg,#22c55e,#16a34a); color: #06140b; font-weight: 700;
  border: none; border-radius: 12px; padding: .9rem 2.25rem; font-size: 1rem; cursor: pointer;
}
#nb-d-submit:disabled { opacity: .5; cursor: progress; }
.nb-d-error { color: #fca5a5; margin-top: .75rem; }
#nb-d-thankyou { text-align: center; }

@media (max-width: 560px) {
  .nb-d-section { padding: 1.5rem 1.1rem; }
  .nb-d-slider-row { grid-template-columns: 1fr auto; }
  .nb-d-slider-cap { display: none; }
  .nb-d-vector-row { grid-template-columns: 1fr; text-align: left; }
  .nb-d-vector-row .nb-d-vector-cap:first-child { text-align: left; }
}
```

- [ ] **Step 2: Verify visually**

Use the `run` skill (or open directly) to load `http://newblood.test/discovery/overhead-door` in a browser. Confirm: focused dark page, no site nav/footer, 12 service rows with importance sliders, readouts update position, sections styled as cards, mobile width collapses captions. (Slider readouts won't move yet — that's Task 6.)

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/newblood/assets/css/discovery.css
git commit -m "feat(discovery): focused-canvas stylesheet"
```

---

### Task 6: Discovery JS (readouts, progressive reveal, submit, thank-you)

**Files:**
- Create: `wp-content/themes/newblood/assets/js/discovery.js`

**Interfaces:**
- Consumes: `window.nbDiscovery` (`endpoint`, `nonce`, `threshold`, `instance`) from Task 4; the DOM contract from Task 4; the REST endpoint from Task 7 (created next — JS handles its absence gracefully via the error path).
- Produces: a POST to `nbDiscovery.endpoint` with header `X-WP-Nonce` and a JSON body matching the **canonical payload shape** in File Structure. On 2xx, reveals `#nb-d-thankyou` and hides the form.

- [ ] **Step 1: Write the script**

Create `wp-content/themes/newblood/assets/js/discovery.js`:

```js
(function () {
  'use strict';
  var cfg = window.nbDiscovery || {};
  var threshold = typeof cfg.threshold === 'number' ? cfg.threshold : 7;
  var form = document.getElementById('nb-discovery-form');
  if (!form) return;

  // Live slider readouts + progressive reveal of the "handled today?" slider.
  function bindServiceRow(row) {
    var imp = row.querySelector('.nb-d-importance');
    var impOut = row.querySelector('.nb-d-importance-out');
    var handling = row.querySelector('.nb-d-handling');
    var handlingInput = row.querySelector('.nb-d-handling-input');
    var handlingOut = row.querySelector('.nb-d-handling-out');
    function syncImp() {
      impOut.textContent = imp.value;
      if (parseInt(imp.value, 10) >= threshold) {
        handling.hidden = false;
      } else {
        handling.hidden = true;
      }
    }
    imp.addEventListener('input', syncImp);
    handlingInput.addEventListener('input', function () { handlingOut.textContent = handlingInput.value; });
    syncImp();
  }
  Array.prototype.forEach.call(document.querySelectorAll('.nb-d-service'), bindServiceRow);

  function collect() {
    var get = function (name) { var el = form.querySelector('[name="' + name + '"]'); return el ? el.value.trim() : ''; };
    var services = [];
    Array.prototype.forEach.call(document.querySelectorAll('.nb-d-service'), function (row) {
      var key = row.getAttribute('data-key');
      var imp = parseInt(row.querySelector('.nb-d-importance').value, 10);
      var obj = { key: key, importance: imp };
      if (imp >= threshold) {
        obj.handling = parseInt(row.querySelector('.nb-d-handling-input').value, 10);
      } else {
        obj.handling = null;
      }
      services.push(obj);
    });
    var vectors = {};
    Array.prototype.forEach.call(document.querySelectorAll('.nb-d-vector'), function (v) {
      vectors[v.getAttribute('data-key')] = parseInt(v.value, 10);
    });
    var gbp = form.querySelector('input[name="gbp_access"]:checked');
    return {
      instance: cfg.instance || form.getAttribute('data-instance'),
      respondent: { name: get('respondent_name'), email: get('respondent_email') },
      services: services,
      vision: get('vision'),
      goal_vectors: {
        residential_commercial: vectors.residential_commercial || 0,
        leads_volume_quality: vectors.leads_volume_quality || 0,
        topline_lean: vectors.topline_lean || 0,
        defend_expand: vectors.defend_expand || 0,
        handson_managed: vectors.handson_managed || 0
      },
      systems: {
        crm: get('crm'),
        lead_handling: get('lead_handling'),
        reviews_system: get('reviews_system'),
        call_tracking: get('call_tracking'),
        gbp_access: gbp ? gbp.value : 'unsure',
        territories: get('territories')
      },
      posture: { fix_invest: vectors.fix_invest || 0, timeline: get('timeline') },
      open: get('open')
    };
  }

  var errEl = document.getElementById('nb-d-error');
  var btn = document.getElementById('nb-d-submit');

  function showError(msg) { if (errEl) { errEl.textContent = msg; errEl.hidden = false; } }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (errEl) errEl.hidden = true;
    var payload = collect();
    if (!payload.respondent.name || !payload.respondent.email) {
      showError('Please add your name and email so we know who this is from.');
      return;
    }
    btn.disabled = true;
    btn.textContent = 'Sending…';
    fetch(cfg.endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce || '' },
      body: JSON.stringify(payload)
    }).then(function (res) {
      if (!res.ok) throw new Error('bad status ' + res.status);
      return res.json();
    }).then(function () {
      form.hidden = true;
      var ty = document.getElementById('nb-d-thankyou');
      if (ty) { ty.hidden = false; ty.scrollIntoView({ behavior: 'smooth' }); }
    }).catch(function () {
      btn.disabled = false;
      btn.textContent = 'Send to New Blood';
      showError('Something went wrong sending your answers. Please try again, or reply to Jeremy’s email.');
    });
  });
})();
```

- [ ] **Step 2: Verify behavior in browser**

Load `http://newblood.test/discovery/overhead-door`. Confirm:
- Dragging an importance slider updates its number.
- Raising importance to 7+ reveals the "How well is this handled today?" slider; dropping below 7 hides it again.
- Submitting with empty name/email shows the inline error.
- (Full submit success is verified in Task 7 once the endpoint exists; for now an enabled endpoint-less submit should surface the catch-path error — acceptable here.)

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/newblood/assets/js/discovery.js
git commit -m "feat(discovery): client interactions — readouts, progressive reveal, submit"
```

---

### Task 7: Submission pipeline — sanitize, gap compute, REST handler, DB insert

**Files:**
- Create: `wp-content/themes/newblood/inc/discovery/submission.php`
- Create: `wp-content/themes/newblood/tests/discovery/test-sanitize.php`
- Create: `wp-content/themes/newblood/tests/discovery/test-gaps.php`
- Modify: `wp-content/themes/newblood/inc/discovery/index.php` (append require)

**Interfaces:**
- Consumes: `nb_discovery_get_instance()` (Task 1); `nb_discovery_table_name()` (Task 2); `nb_discovery_send_email()` (Task 8 — registered after; guard with `function_exists`).
- Produces:
  - `nb_discovery_sanitize_payload( array $raw, array $instance ): array` — returns a clean payload: clamps each score to 0–10 (drops services whose `key` is not in the instance config), `handling` set to `null` when below threshold (7) or absent, clamps vectors/posture to −50..50, `sanitize_text_field`/`sanitize_textarea_field` on all text, `gbp_access` coerced to `yes|no|unsure`, email via `sanitize_email`.
  - `nb_discovery_compute_gaps( array $services ): array` — returns the services list with a `gap` int added when both `importance` and `handling !== null` (`gap = importance - handling`), else `gap = null`.
  - REST route `POST newblood/v1/discovery` → `nb_discovery_handle_submit( WP_REST_Request $req ): WP_REST_Response`.

- [ ] **Step 1: Write the failing tests**

Create `wp-content/themes/newblood/tests/discovery/test-sanitize.php`:

```php
<?php
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/config.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/submission.php';

$instance = nb_discovery_get_instance( 'overhead-door' );
$raw = array(
    'instance'   => 'overhead-door',
    'respondent' => array( 'name' => '  Chase <b>C</b> ', 'email' => 'chase@example.com ' ),
    'services'   => array(
        array( 'key' => 'website', 'importance' => 99, 'handling' => 4 ),     // imp clamps to 10, keeps handling
        array( 'key' => 'content', 'importance' => 3,  'handling' => 8 ),     // below threshold → handling null
        array( 'key' => 'bogus_key', 'importance' => 5 ),                      // dropped
    ),
    'vision'       => "  Big <script>x</script>growth  ",
    'goal_vectors' => array( 'defend_expand' => 999, 'topline_lean' => -999 ),
    'systems'      => array( 'gbp_access' => 'maybe', 'crm' => 'HubSpot' ),
    'posture'      => array( 'fix_invest' => 12, 'timeline' => 'Within 1–3 months' ),
    'open'         => 'thanks',
);
$clean = nb_discovery_sanitize_payload( $raw, $instance );

assert( $clean['respondent']['name'] === 'Chase C', 'name stripped of tags/space' );
assert( $clean['respondent']['email'] === 'chase@example.com', 'email sanitized' );
assert( count( $clean['services'] ) === 2, 'bogus key dropped' );
$byKey = array();
foreach ( $clean['services'] as $s ) { $byKey[ $s['key'] ] = $s; }
assert( $byKey['website']['importance'] === 10, 'importance clamped to 10' );
assert( $byKey['website']['handling'] === 4, 'handling kept when above threshold' );
assert( $byKey['content']['handling'] === null, 'handling nulled below threshold' );
assert( strpos( $clean['vision'], 'script' ) === false, 'vision stripped of tags' );
assert( $clean['goal_vectors']['defend_expand'] === 50, 'vector clamped high' );
assert( $clean['goal_vectors']['topline_lean'] === -50, 'vector clamped low' );
assert( $clean['systems']['gbp_access'] === 'unsure', 'invalid gbp coerced to unsure' );
assert( $clean['posture']['fix_invest'] === 12, 'posture vector kept' );
echo "test-sanitize: PASS\n";
```

Create `wp-content/themes/newblood/tests/discovery/test-gaps.php`:

```php
<?php
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/submission.php';

$services = array(
    array( 'key' => 'website', 'importance' => 9, 'handling' => 4 ),
    array( 'key' => 'seo_aeo', 'importance' => 6, 'handling' => null ),
);
$out = nb_discovery_compute_gaps( $services );
assert( $out[0]['gap'] === 5, 'gap = importance - handling' );
assert( $out[1]['gap'] === null, 'no gap when handling null' );
echo "test-gaps: PASS\n";
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php wp-content/themes/newblood/tests/discovery/test-gaps.php`
Expected: FAIL — fatal `Call to undefined function nb_discovery_compute_gaps()`.

- [ ] **Step 3: Write the implementation**

Create `wp-content/themes/newblood/inc/discovery/submission.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'NB_DISCOVERY_THRESHOLD' ) ) define( 'NB_DISCOVERY_THRESHOLD', 7 );

function nb_discovery_clamp( $n, $min, $max ) {
    $n = (int) $n;
    if ( $n < $min ) return $min;
    if ( $n > $max ) return $max;
    return $n;
}

/**
 * Validate + sanitize a raw decoded payload against an instance config.
 */
function nb_discovery_sanitize_payload( $raw, $instance ) {
    $valid_keys = array_column( $instance['services'], 'key' );

    $name  = isset( $raw['respondent']['name'] ) ? sanitize_text_field( $raw['respondent']['name'] ) : '';
    $email = isset( $raw['respondent']['email'] ) ? sanitize_email( $raw['respondent']['email'] ) : '';

    $services = array();
    if ( ! empty( $raw['services'] ) && is_array( $raw['services'] ) ) {
        foreach ( $raw['services'] as $s ) {
            if ( empty( $s['key'] ) || ! in_array( $s['key'], $valid_keys, true ) ) continue;
            $imp = nb_discovery_clamp( isset( $s['importance'] ) ? $s['importance'] : 0, 0, 10 );
            $handling = null;
            if ( $imp >= NB_DISCOVERY_THRESHOLD && isset( $s['handling'] ) && $s['handling'] !== null && $s['handling'] !== '' ) {
                $handling = nb_discovery_clamp( $s['handling'], 0, 10 );
            }
            $services[] = array( 'key' => $s['key'], 'importance' => $imp, 'handling' => $handling );
        }
    }

    $vec = function ( $key ) use ( $raw ) {
        return isset( $raw['goal_vectors'][ $key ] ) ? nb_discovery_clamp( $raw['goal_vectors'][ $key ], -50, 50 ) : 0;
    };
    $gbp = isset( $raw['systems']['gbp_access'] ) ? $raw['systems']['gbp_access'] : 'unsure';
    if ( ! in_array( $gbp, array( 'yes', 'no', 'unsure' ), true ) ) $gbp = 'unsure';

    $txt  = function ( $v ) { return isset( $v ) ? sanitize_text_field( $v ) : ''; };
    $area = function ( $v ) { return isset( $v ) ? sanitize_textarea_field( $v ) : ''; };

    return array(
        'instance'   => $instance['slug'],
        'respondent' => array( 'name' => $name, 'email' => $email ),
        'services'   => $services,
        'vision'     => $area( isset( $raw['vision'] ) ? $raw['vision'] : '' ),
        'goal_vectors' => array(
            'residential_commercial' => $vec( 'residential_commercial' ),
            'leads_volume_quality'   => $vec( 'leads_volume_quality' ),
            'topline_lean'           => $vec( 'topline_lean' ),
            'defend_expand'          => $vec( 'defend_expand' ),
            'handson_managed'        => $vec( 'handson_managed' ),
        ),
        'systems' => array(
            'crm'            => $txt( isset( $raw['systems']['crm'] ) ? $raw['systems']['crm'] : '' ),
            'lead_handling'  => $area( isset( $raw['systems']['lead_handling'] ) ? $raw['systems']['lead_handling'] : '' ),
            'reviews_system' => $txt( isset( $raw['systems']['reviews_system'] ) ? $raw['systems']['reviews_system'] : '' ),
            'call_tracking'  => $txt( isset( $raw['systems']['call_tracking'] ) ? $raw['systems']['call_tracking'] : '' ),
            'gbp_access'     => $gbp,
            'territories'    => $area( isset( $raw['systems']['territories'] ) ? $raw['systems']['territories'] : '' ),
        ),
        'posture' => array(
            'fix_invest' => isset( $raw['posture']['fix_invest'] ) ? nb_discovery_clamp( $raw['posture']['fix_invest'], -50, 50 ) : 0,
            'timeline'   => $txt( isset( $raw['posture']['timeline'] ) ? $raw['posture']['timeline'] : '' ),
        ),
        'open' => $area( isset( $raw['open'] ) ? $raw['open'] : '' ),
    );
}

/**
 * Add server-computed gap scores (importance - handling) to each service.
 */
function nb_discovery_compute_gaps( $services ) {
    foreach ( $services as &$s ) {
        if ( isset( $s['importance'] ) && isset( $s['handling'] ) && $s['handling'] !== null ) {
            $s['gap'] = (int) $s['importance'] - (int) $s['handling'];
        } else {
            $s['gap'] = null;
        }
    }
    unset( $s );
    return $services;
}

/**
 * REST: receive a submission, store it, email the summary.
 */
function nb_discovery_handle_submit( $req ) {
    $raw  = $req->get_json_params();
    $slug = isset( $raw['instance'] ) ? sanitize_title( $raw['instance'] ) : '';
    $instance = nb_discovery_get_instance( $slug );
    if ( ! $instance ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'unknown_instance' ), 400 );
    }

    $clean = nb_discovery_sanitize_payload( $raw, $instance );
    if ( ! is_email( $clean['respondent']['email'] ) || $clean['respondent']['name'] === '' ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'missing_identity' ), 422 );
    }
    $clean['services'] = nb_discovery_compute_gaps( $clean['services'] );

    global $wpdb;
    $wpdb->insert(
        nb_discovery_table_name(),
        array(
            'instance'         => $clean['instance'],
            'respondent_name'  => $clean['respondent']['name'],
            'respondent_email' => $clean['respondent']['email'],
            'payload'          => wp_json_encode( $clean ),
            'created_at'       => current_time( 'mysql' ),
            'ip'               => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '',
        ),
        array( '%s', '%s', '%s', '%s', '%s', '%s' )
    );

    if ( function_exists( 'nb_discovery_send_email' ) ) {
        nb_discovery_send_email( $clean, $instance );
    }

    return new WP_REST_Response( array( 'ok' => true ), 200 );
}

function nb_discovery_register_rest() {
    register_rest_route( 'newblood/v1', '/discovery', array(
        'methods'             => 'POST',
        'callback'            => 'nb_discovery_handle_submit',
        'permission_callback' => function () {
            // Public form; nonce checked by WP because route is under wp-json
            // and JS sends X-WP-Nonce. Accept without login.
            return true;
        },
    ) );
}
add_action( 'rest_api_init', 'nb_discovery_register_rest' );
```

- [ ] **Step 4: Run unit tests to verify they pass**

Run: `php wp-content/themes/newblood/tests/discovery/test-gaps.php`
Expected: `test-gaps: PASS`
Run: `php wp-content/themes/newblood/tests/discovery/test-sanitize.php`
Expected: `test-sanitize: PASS`

- [ ] **Step 5: Append require to module bootstrap + lint**

In `wp-content/themes/newblood/inc/discovery/index.php`:

```php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/routing.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/submission.php';
// Later tasks append: email.php
```

Run: `php -l wp-content/themes/newblood/inc/discovery/submission.php`
Expected: `No syntax errors detected`

- [ ] **Step 6: Integration check — POST a submission**

Get a nonce and POST a real payload via WP-CLI (avoids nonce friction by generating one server-side):

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood eval '
$req = new WP_REST_Request("POST", "/newblood/v1/discovery");
$req->set_body(json_encode(array(
  "instance" => "overhead-door",
  "respondent" => array("name"=>"Test Chase","email"=>"chase@example.com"),
  "services" => array(array("key"=>"website","importance"=>9,"handling"=>3)),
  "goal_vectors" => array("defend_expand"=>20),
  "systems" => array("gbp_access"=>"yes"),
  "posture" => array("fix_invest"=>30,"timeline"=>"Within 1-3 months"),
)));
$req->set_header("Content-Type","application/json");
$res = rest_do_request($req);
echo $res->get_status() . " " . json_encode($res->get_data()) . "\n";
'
```

Expected: `200 {"ok":true}`

Confirm the row landed (gap computed = 9−3 = 6):

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood db query "SELECT respondent_name, JSON_EXTRACT(payload,'$.services[0].gap') AS gap FROM $(php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood eval 'echo nb_discovery_table_name();') ORDER BY id DESC LIMIT 1;"
```

Expected: `Test Chase | 6`. (Then delete the test row: `... db query "DELETE FROM <table> WHERE respondent_email='chase@example.com';"`.)

- [ ] **Step 7: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/submission.php wp-content/themes/newblood/inc/discovery/index.php wp-content/themes/newblood/tests/discovery/test-sanitize.php wp-content/themes/newblood/tests/discovery/test-gaps.php
git commit -m "feat(discovery): REST submission — sanitize, gap compute, DB insert"
```

---

### Task 8: Summary email (gap map first)

**Files:**
- Create: `wp-content/themes/newblood/inc/discovery/email.php`
- Create: `wp-content/themes/newblood/tests/discovery/test-email.php`
- Modify: `wp-content/themes/newblood/inc/discovery/index.php` (append require)

**Interfaces:**
- Consumes: the clean+gapped payload from Task 7; the instance config (for `client_name`, service labels, vector labels, recipient).
- Produces: `nb_discovery_format_email( array $record, array $instance ): array` returning `[ 'subject' => string, 'body' => string ]`. Services are listed **gap-descending** (largest importance-minus-handling first) so the priority/gap picture leads. `nb_discovery_send_email( array $record, array $instance ): bool` wraps `wp_mail`.

- [ ] **Step 1: Write the failing test**

Create `wp-content/themes/newblood/tests/discovery/test-email.php`:

```php
<?php
require __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'wp_mail' ) ) { function wp_mail( $to, $subj, $body, $headers = array() ) { return true; } }
require dirname( __DIR__, 2 ) . '/inc/discovery/config.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/email.php';

$instance = nb_discovery_get_instance( 'overhead-door' );
$record = array(
    'instance'   => 'overhead-door',
    'respondent' => array( 'name' => 'Chase Cummings', 'email' => 'chase@example.com' ),
    'services'   => array(
        array( 'key' => 'website',  'importance' => 6, 'handling' => 5, 'gap' => 1 ),
        array( 'key' => 'lead_gen', 'importance' => 10, 'handling' => 2, 'gap' => 8 ),
        array( 'key' => 'content',  'importance' => 4, 'handling' => null, 'gap' => null ),
    ),
    'vision'       => 'Own the commercial market.',
    'goal_vectors' => array( 'residential_commercial' => 30, 'leads_volume_quality' => -10, 'topline_lean' => 0, 'defend_expand' => 25, 'handson_managed' => 40 ),
    'systems'      => array( 'crm' => 'None', 'lead_handling' => 'Email to office', 'reviews_system' => 'Google', 'call_tracking' => 'Enspire', 'gbp_access' => 'yes', 'territories' => 'Baltimore metro' ),
    'posture'      => array( 'fix_invest' => 35, 'timeline' => 'Within 1-3 months' ),
    'open'         => 'Looking forward to it.',
);
$mail = nb_discovery_format_email( $record, $instance );

assert( strpos( $mail['subject'], 'Overhead Door Company of Baltimore' ) !== false, 'subject has client' );
assert( strpos( $mail['subject'], 'Chase Cummings' ) !== false, 'subject has respondent' );
// Highest-gap service (lead_gen, gap 8) must appear before the lower-gap one (website).
$posLead = strpos( $mail['body'], 'Lead generation' );
$posWeb  = strpos( $mail['body'], 'Website design' );
assert( $posLead !== false && $posWeb !== false && $posLead < $posWeb, 'gap-descending order' );
assert( strpos( $mail['body'], 'Own the commercial market.' ) !== false, 'vision included' );
assert( strpos( $mail['body'], 'Enspire' ) !== false, 'systems included' );
echo "test-email: PASS\n";
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php wp-content/themes/newblood/tests/discovery/test-email.php`
Expected: FAIL — fatal `Call to undefined function nb_discovery_format_email()`.

- [ ] **Step 3: Write the implementation**

Create `wp-content/themes/newblood/inc/discovery/email.php`:

```php
<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Build the plain-text summary email. Gap map leads (largest gap first).
 */
function nb_discovery_format_email( $record, $instance ) {
    $labels = array();
    foreach ( $instance['services'] as $s ) { $labels[ $s['key'] ] = $s['label']; }
    $vlabels = array();
    foreach ( $instance['goal_vectors'] as $v ) { $vlabels[ $v['key'] ] = $v['left'] . ' ↔ ' . $v['right']; }

    $subject = 'New Blood Discovery — ' . $instance['client_name'] . ' (' . $record['respondent']['name'] . ')';

    // Sort services by gap desc; null gaps sink to the bottom.
    $services = $record['services'];
    usort( $services, function ( $a, $b ) {
        $ga = is_null( $a['gap'] ) ? -100 : $a['gap'];
        $gb = is_null( $b['gap'] ) ? -100 : $b['gap'];
        if ( $ga === $gb ) return $b['importance'] - $a['importance'];
        return $gb - $ga;
    } );

    $lines = array();
    $lines[] = 'Respondent: ' . $record['respondent']['name'] . ' <' . $record['respondent']['email'] . '>';
    $lines[] = 'Client: ' . $instance['client_name'];
    $lines[] = '';
    $lines[] = '== PRIORITY / GAP MAP (importance · handled today · gap) ==';
    foreach ( $services as $s ) {
        $label = isset( $labels[ $s['key'] ] ) ? $labels[ $s['key'] ] : $s['key'];
        if ( is_null( $s['handling'] ) ) {
            $lines[] = sprintf( '%-44s imp %2d · (not rated — below priority threshold)', $label, $s['importance'] );
        } else {
            $lines[] = sprintf( '%-44s imp %2d · now %2d · GAP %2d', $label, $s['importance'], $s['handling'], $s['gap'] );
        }
    }
    $lines[] = '';
    $lines[] = '== 3-YEAR VISION ==';
    $lines[] = $record['vision'] !== '' ? $record['vision'] : '(blank)';
    $lines[] = '';
    $lines[] = '== DIRECTION (−50 … +50) ==';
    foreach ( $record['goal_vectors'] as $k => $val ) {
        $lab = isset( $vlabels[ $k ] ) ? $vlabels[ $k ] : $k;
        $lines[] = sprintf( '%-44s %+d', $lab, $val );
    }
    $lines[] = sprintf( '%-44s %+d', 'Fix what’s urgent ↔ Invest long-term', $record['posture']['fix_invest'] );
    $lines[] = 'Timeline: ' . ( $record['posture']['timeline'] !== '' ? $record['posture']['timeline'] : '(blank)' );
    $lines[] = '';
    $lines[] = '== SYSTEMS TODAY ==';
    $lines[] = 'CRM: ' . $record['systems']['crm'];
    $lines[] = 'Lead handling: ' . $record['systems']['lead_handling'];
    $lines[] = 'Reviews system: ' . $record['systems']['reviews_system'];
    $lines[] = 'Call tracking: ' . $record['systems']['call_tracking'];
    $lines[] = 'GBP manager access: ' . $record['systems']['gbp_access'];
    $lines[] = 'Territories: ' . $record['systems']['territories'];
    $lines[] = '';
    $lines[] = '== ANYTHING ELSE ==';
    $lines[] = $record['open'] !== '' ? $record['open'] : '(blank)';

    return array( 'subject' => $subject, 'body' => implode( "\n", $lines ) );
}

function nb_discovery_send_email( $record, $instance ) {
    $mail = nb_discovery_format_email( $record, $instance );
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $record['respondent']['name'] . ' <' . $record['respondent']['email'] . '>',
    );
    return wp_mail( $instance['recipient'], $mail['subject'], $mail['body'], $headers );
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php wp-content/themes/newblood/tests/discovery/test-email.php`
Expected: `test-email: PASS`

- [ ] **Step 5: Append require + lint**

In `wp-content/themes/newblood/inc/discovery/index.php`:

```php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/routing.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/controller.php';
require_once __DIR__ . '/submission.php';
require_once __DIR__ . '/email.php';
```

Run: `php -l wp-content/themes/newblood/inc/discovery/email.php`
Expected: `No syntax errors detected`

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/email.php wp-content/themes/newblood/inc/discovery/index.php wp-content/themes/newblood/tests/discovery/test-email.php
git commit -m "feat(discovery): gap-map-first summary email"
```

---

### Task 9: End-to-end verification + delivery email draft

**Files:**
- Create: `docs/clients/ohdbalt-discovery-email-DRAFT.md`

**Interfaces:**
- Consumes: the full running feature (all prior tasks).
- Produces: the email draft Jeremy sends, with the live URL.

- [ ] **Step 1: Run all unit tests together**

```bash
for t in config gaps sanitize email; do php wp-content/themes/newblood/tests/discovery/test-$t.php; done
```
Expected: four `... PASS` lines, no PHP warnings/fatals.

- [ ] **Step 2: Full browser run via the `run` skill**

Load `http://newblood.test/discovery/overhead-door`. Complete the form end-to-end on desktop width and a mobile viewport (~390px):
- Set several services to ≥7, confirm handling sliders appear and accept values.
- Fill vision, vectors, systems, posture, open, name, email.
- Submit. Confirm the form is replaced by the thank-you screen.
Then confirm storage + mail attempt:
```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood db query "SELECT id, respondent_name, created_at FROM $(php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood eval 'echo nb_discovery_table_name();') ORDER BY id DESC LIMIT 3;"
```
Expected: your test submission present. (Local `wp_mail` may not deliver without SMTP — that's expected in dev; the row + a no-error submit confirm the pipeline. Delete test rows afterward.)

- [ ] **Step 3: Write the delivery email draft**

Create `docs/clients/ohdbalt-discovery-email-DRAFT.md` with the spec's email copy and the live URL `https://newblood.com/discovery/overhead-door`:

```markdown
# OHDBalt — Discovery delivery email (DRAFT for Jeremy to send)

**To:** Chase Cummings
**Subject:** A quick step before your comprehensive plan

Hi Chase,

Thank you — "show us the maximum scope you'd recommend" is exactly the conversation I was hoping for, and I want to get it right rather than fast.

To build something genuinely tailored to Overhead Door (not a template), I've put together a short discovery step — about 10 minutes. It captures where you want to take the business and how much each capability matters to *you*, so the plan I bring back reflects your priorities, not my guesses:

**Start your discovery → https://newblood.com/discovery/overhead-door**

Once you've gone through it, I'll prepare the comprehensive scope and pricing, and we can walk through it together with Paul whenever it suits you.

Best,
Jeremy
```

- [ ] **Step 4: Commit**

```bash
git add docs/clients/ohdbalt-discovery-email-DRAFT.md
git commit -m "docs(discovery): OHDBalt delivery email draft + e2e verification"
```

---

## Deploy note (post-merge, manual — not a task step here)

Production is Nexcess SFTP with no CI. On deploy, upload the new `inc/discovery/` tree, the two `assets/` files, and the modified `functions.php`. The DB table and rewrite rules **self-heal on the first request** (version-guarded `after_setup_theme` migration + `init` rewrite flush) — no manual SQL or permalink re-save needed. Verify `https://newblood.com/discovery/overhead-door` returns 200 and a test submission lands before sending Chase the link. Confirm production `wp_mail` delivers (SMTP configured) — unlike local dev.

---

## Self-Review

**Spec coverage:**
- §Flow personal email → Task 9 draft. Branded form → Tasks 4–6. Thank-you screen → Tasks 4/6. Stored + emailed → Tasks 7/8. ✓
- §Section 1 welcome/logo/frame/10-min → view (Task 4) + config (Task 1). ✓
- §Section 2 dual-axis progressive sliders, 12 rows + one-liners, threshold → config (Task 1), view (Task 4), JS reveal (Task 6), threshold=7 global constraint. ✓
- §Section 3 vision + 5 bipolar sliders → view (Task 4), config vectors (Task 1). ✓
- §Section 4 six systems fields incl. GBP yes/no/unsure → view (Task 4), sanitize (Task 7). ✓
- §Section 5 fix↔invest + timeline → view (Task 4). ✓
- §Section 6 open → view (Task 4). ✓
- §Data model: per-service importance/handling/gap, vectors, open text, systems, posture, metadata → payload shape + DB (Tasks 2/7). ✓
- §Tone/brand focused canvas, green/black, mobile, NOT 2018 aesthetic → CSS (Task 5). ✓
- §Reusability config-driven → config (Task 1), slug-keyed instances, controller resolves any slug. ✓
- §Non-goals: no pricing/proposal content; no gap reveal on submit (thank-you only); no budget number (posture slider only) → honored. ✓
- Open questions resolved: threshold=7; storage=custom DB table + email; slug=/discovery/overhead-door; microcopy drafted in config. ✓

**Placeholder scan:** No "TBD"/"handle edge cases"/"similar to" — every code step shows full code. The only intentional markers are the `\uXXXX` typographic-glyph notes, flagged explicitly with replacement instructions.

**Type consistency:** Service keys, vector keys, systems keys, and posture keys are identical across config (T1), view DOM contract (T4), JS payload (T6), sanitize/gaps (T7), and email (T8). Function names — `nb_discovery_get_instance`, `nb_discovery_table_name`, `nb_discovery_sanitize_payload`, `nb_discovery_compute_gaps`, `nb_discovery_format_email`, `nb_discovery_send_email`, `nb_discovery_render_page` — are used identically wherever referenced. Payload shape matches between JS producer and PHP consumer.
