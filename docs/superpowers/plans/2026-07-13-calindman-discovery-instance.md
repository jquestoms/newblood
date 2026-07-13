# Calindman Discovery Instance + Config-Driven Refactor — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the discovery module's "systems" questions and goal-vector keys per-instance config, then add the C.A. Lindman instance at `newblood.com/discovery/calindman` — with the live OHDBalt instance provably unchanged.

**Architecture:** The module lives in `wp-content/themes/newblood/inc/discovery/` (config → view/JS → REST controller → sanitize → DB + email → admin report/aggregate). Instances are entries in `nb_discovery_instances()`. This plan adds a `systems_questions` array per instance and makes the five consumers (view, JS, sanitize, email, aggregate/report) iterate config instead of hardcoded field lists. OHDBalt's entry is migrated to config expressing its exact current questions.

**Tech Stack:** WordPress theme PHP (no framework), vanilla JS, plain-PHP CLI tests (`assert()`-based, no PHPUnit). Local site: `newblood.test` (Laravel Herd). Spec: `docs/superpowers/specs/2026-07-13-calindman-discovery-instance-design.md`.

## Global Constraints

- **Repo/branch:** work in `~/Herd/newblood` on branch `feature/discovery-calindman`, cut from `feature/redesign` (the live line — the discovery module does NOT exist on `main`). The tree has unrelated dirty files (`.gitignore`, `TASKS.md`, `docs/clients/ohdbalt-discovery-email-DRAFT.md`, `signal-logs/signal.py`) — never `git add -A`; stage exact paths only.
- **Theme root** (all relative paths below): `~/Herd/newblood/wp-content/themes/newblood/`.
- **Tests run with:** `php -d zend.assertions=1 -d assert.exception=1 tests/discovery/<file>.php` from the theme root. All 5 existing files pass today; they must pass at every commit.
- **OHDBalt compatibility bar** (spec §OHDBalt compatibility): form normalized-identical · sanitize fixture value-identical (canonical key sort) · aggregate JSON identical · report labels verbatim (qualitative heading order may follow form order) · email adopts `short` labels.
- **Payload keys are API:** OHDBalt keys `crm, lead_handling, leads_per_month, reviews_system, call_tracking, gbp_access, territories` and vector keys `residential_commercial, leads_volume_quality, topline_lean, defend_expand, handson_managed` must not change.
- **New-instance slug:** `calindman` (URL `newblood.com/discovery/calindman`). Recipient: `joms@newblood.com`.
- Copy for the calindman instance is specified verbatim in the spec (§Part 2) — use it exactly; curly apostrophes (`’`) in PHP strings, not `'`, matching existing config style.
- **Baseline dir:** `~/Herd/newblood/.discovery-baseline/` (untracked; deleted in Task 7 — never committed).
- Commit messages: end with `Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>`.

---

### Task 1: Branch + baseline snapshots of the live OHDBalt behavior

**Files:**
- Create: `~/Herd/newblood/.discovery-baseline/form.html`, `.../form.normalized.html`, `.../aggregate.json`, `.../dump-aggregate.php`, `.../sanitize-fixture.json`
- No source changes.

**Interfaces:**
- Produces: baseline artifacts that Tasks 4, 5 diff against; the raw+expected sanitize fixture Task 3's test uses.

- [ ] **Step 1: Create the branch**

```bash
cd ~/Herd/newblood
git switch feature/redesign
git switch -c feature/discovery-calindman
mkdir -p .discovery-baseline
```

- [ ] **Step 2: Snapshot the rendered OHDBalt form (raw + normalized)**

```bash
cd ~/Herd/newblood
curl -s http://newblood.test/discovery/overhead-door > .discovery-baseline/form.html
# Normalize: drop the per-request nonce line, canonicalize &#8217; to ’, squeeze whitespace.
sed -e '/window.nbDiscovery/d' -e 's/&#8217;/’/g' .discovery-baseline/form.html \
  | sed -e 's/[[:space:]]\+/ /g' > .discovery-baseline/form.normalized.html
grep -c 'nb-d-service' .discovery-baseline/form.html
```

Expected: file non-empty; grep prints `13` or similar (12 service rows + CSS class refs — any stable nonzero count; record it). If curl returns an empty file or an error page, STOP — newblood.test must be serving before this plan proceeds.

- [ ] **Step 3: Snapshot the aggregate JSON for overhead-door (real submissions incl. Chase's)**

Write `.discovery-baseline/dump-aggregate.php`:

```php
<?php
// Dump the overhead-door aggregate as stable JSON (run via: wp eval-file <this file>)
$instance = nb_discovery_get_instance( 'overhead-door' );
global $wpdb;
$table = nb_discovery_table_name();
$rows  = $wpdb->get_results( $wpdb->prepare(
    "SELECT id, respondent_name, respondent_email, payload, excluded FROM {$table} WHERE instance = %s ORDER BY id ASC",
    'overhead-door' ), ARRAY_A );
$subs = array();
foreach ( $rows as $r ) {
    if ( (int) $r['excluded'] === 1 ) continue;
    $subs[] = array( 'id' => (int) $r['id'], 'name' => $r['respondent_name'], 'email' => $r['respondent_email'], 'payload' => json_decode( $r['payload'], true ) );
}
$agg = nb_discovery_aggregate( $subs, $instance );
ksort( $agg['qualitative'] ); // key-order-insensitive comparison per spec
echo json_encode( $agg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ), "\n";
```

Run:

```bash
cd ~/Herd/newblood
wp eval-file .discovery-baseline/dump-aggregate.php --skip-themes= > .discovery-baseline/aggregate.json 2>/dev/null \
  || wp eval-file .discovery-baseline/dump-aggregate.php > .discovery-baseline/aggregate.json
head -5 .discovery-baseline/aggregate.json
```

Expected: JSON starting `{ "count": …` with `"count"` ≥ 1 (Chase's submission exists in the local dev DB). Note: `wp` against newblood may need no flags here — the discovery module loads with the theme; if a theme file fatals under CLI, run `wp eval-file` without skip flags (the newblood theme is CLI-clean, unlike calindman's).

- [ ] **Step 4: Capture the sanitize fixture (pre-refactor truth)**

Write a raw payload and today's sanitized result to `.discovery-baseline/sanitize-fixture.json`:

```bash
cd ~/Herd/newblood/wp-content/themes/newblood
php -d zend.assertions=1 -r '
require "tests/discovery/bootstrap.php";
require "inc/discovery/config.php";
require "inc/discovery/submission.php";
$raw = array(
  "instance" => "overhead-door",
  "respondent" => array( "name" => "Fixture Person", "email" => "fixture@example.com" ),
  "services" => array(
    array( "key" => "website", "importance" => 9, "handling" => 3 ),
    array( "key" => "content", "importance" => 4, "handling" => 9 ),
  ),
  "vision" => "Grow steadily.",
  "goal_vectors" => array( "residential_commercial" => 25, "leads_volume_quality" => -25, "topline_lean" => 0, "defend_expand" => 50, "handson_managed" => -50 ),
  "systems" => array( "crm" => "None", "lead_handling" => "Front desk emails it around", "leads_per_month" => "about 10", "reviews_system" => "internal", "call_tracking" => "Enspire", "gbp_access" => "yes", "territories" => "Maryland region" ),
  "posture" => array( "fix_invest" => 30, "timeline" => "Within 1–3 months" ),
  "open" => "Nothing else.",
);
$clean = nb_discovery_sanitize_payload( $raw, nb_discovery_get_instance( "overhead-door" ) );
echo json_encode( array( "raw" => $raw, "expected" => $clean ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ), "\n";
' > ../../../.discovery-baseline/sanitize-fixture.json
head -3 ../../../.discovery-baseline/sanitize-fixture.json
```

Expected: JSON with `"raw"` and `"expected"` keys.

- [ ] **Step 5: Verify tests green at baseline**

```bash
cd ~/Herd/newblood/wp-content/themes/newblood
for f in tests/discovery/test-*.php; do php -d zend.assertions=1 -d assert.exception=1 "$f"; done
```

Expected: five `PASS` lines. No commit in this task (nothing tracked changed); the baseline dir stays untracked.

---

### Task 2: Config — `systems_questions` for overhead-door + `short` labels

**Files:**
- Modify: `inc/discovery/config.php` (the `overhead-door` entry, after `timeline_options`)
- Test: `tests/discovery/test-config.php`

**Interfaces:**
- Produces: `$instance['systems_questions']` — ordered list of `array( 'key', 'label', 'short', 'type' [, 'hint', 'rows', 'options', 'default' ] )`; `type` ∈ `text|textarea|radio`; `radio` entries always have `options` (ordered `value => label`) and `default` (a key of `options`). All later tasks consume exactly this shape.

- [ ] **Step 1: Extend test-config.php with failing assertions**

Append to `tests/discovery/test-config.php` (before the final `echo`):

```php
// --- systems_questions: every instance must define a valid question list ---
foreach ( nb_discovery_instances() as $slug => $i ) {
    assert( ! empty( $i['systems_questions'] ) && is_array( $i['systems_questions'] ), "$slug has systems_questions" );
    $qkeys = array_column( $i['systems_questions'], 'key' );
    assert( count( $qkeys ) === count( array_unique( $qkeys ) ), "$slug systems question keys unique" );
    foreach ( $i['systems_questions'] as $q ) {
        assert( ! empty( $q['key'] ) && ! empty( $q['label'] ) && ! empty( $q['short'] ), "{$slug}:{$q['key']} has key/label/short" );
        assert( in_array( $q['type'], array( 'text', 'textarea', 'radio' ), true ), "{$slug}:{$q['key']} valid type" );
        if ( $q['type'] === 'radio' ) {
            assert( ! empty( $q['options'] ) && is_array( $q['options'] ), "{$slug}:{$q['key']} radio has options" );
            assert( isset( $q['default'] ) && isset( $q['options'][ $q['default'] ] ), "{$slug}:{$q['key']} radio default is an option" );
        }
    }
}
// OHDBalt keys locked to the historical payload shape (form order).
$sq = array_column( nb_discovery_get_instance( 'overhead-door' )['systems_questions'], 'key' );
assert( $sq === array( 'crm', 'lead_handling', 'leads_per_month', 'reviews_system', 'call_tracking', 'gbp_access', 'territories' ), 'overhead-door systems keys in form order' );
```

- [ ] **Step 2: Run to verify it fails**

```bash
cd ~/Herd/newblood/wp-content/themes/newblood
php -d zend.assertions=1 -d assert.exception=1 tests/discovery/test-config.php
```

Expected: FAIL — `AssertionError: overhead-door has systems_questions`.

- [ ] **Step 3: Add `systems_questions` to the overhead-door entry**

In `inc/discovery/config.php`, after the `'timeline_options' => …` line of the `overhead-door` entry, insert (labels are verbatim from the current `view.php`; shorts are verbatim from the current `report.php` `$qual_labels`):

```php
            'systems_questions' => array(
                array( 'key' => 'crm',             'label' => 'Do you use a CRM today? If so, which one?',            'short' => 'CRM today',            'type' => 'text' ),
                array( 'key' => 'lead_handling',   'label' => 'When a web lead comes in today, what happens?',        'short' => 'Lead handling today',  'type' => 'textarea', 'rows' => 3 ),
                array( 'key' => 'leads_per_month', 'label' => 'Roughly how many web leads a month right now?',        'short' => 'Web leads / month',    'type' => 'text',
                       'hint' => 'Ballpark is fine — and if you’re not tracking this yet, just say so.' ),
                array( 'key' => 'reviews_system',  'label' => 'Your reviews live in which system?',                   'short' => 'Reviews system',       'type' => 'text' ),
                array( 'key' => 'call_tracking',   'label' => 'Any call-tracking / attribution in place? (e.g., Enspire)', 'short' => 'Call tracking',   'type' => 'text' ),
                array( 'key' => 'gbp_access',      'label' => 'Can you grant manager access to your Google Business Profile?', 'short' => 'Google Business Profile access', 'type' => 'radio',
                       'options' => array( 'yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not sure' ), 'default' => 'unsure' ),
                array( 'key' => 'territories',     'label' => 'Which locations / territories should the plan cover?', 'short' => 'Territories',          'type' => 'textarea', 'rows' => 2 ),
            ),
```

- [ ] **Step 4: Run tests to verify pass**

```bash
for f in tests/discovery/test-*.php; do php -d zend.assertions=1 -d assert.exception=1 "$f"; done
```

Expected: five `PASS` lines.

- [ ] **Step 5: Commit**

```bash
cd ~/Herd/newblood
git add wp-content/themes/newblood/inc/discovery/config.php wp-content/themes/newblood/tests/discovery/test-config.php
git commit -m "feat(discovery): express OHDBalt systems questions as instance config

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 3: submission.php — sanitize systems + goal vectors from config

**Files:**
- Modify: `inc/discovery/submission.php` (`nb_discovery_sanitize_payload`, currently lines ~35–72: the `$vec` usage block, the `$gbp` block, and the fixed `'goal_vectors'`/`'systems'` arrays)
- Test: `tests/discovery/test-sanitize.php`

**Interfaces:**
- Consumes: `$instance['systems_questions']`, `$instance['goal_vectors']` (Task 2 shape).
- Produces: `nb_discovery_sanitize_payload( $raw, $instance )` — same signature; `systems` keys now exactly the instance's question keys (in config order), `goal_vectors` keys exactly the instance's vector keys. For OHDBalt input/output is value-identical to pre-refactor after canonical key sort (fixture-locked).

- [ ] **Step 1: Add failing tests**

Append to `tests/discovery/test-sanitize.php` (before the final `echo`):

```php
// --- Fixture lock: OHDBalt sanitize output must be value-identical to the pre-refactor capture ---
// (strict equality after canonical key sort: assoc key order is not API — every consumer reads by
// key name — and the old payload order differed from the form order; decided with Jeremy 2026-07-13)
function nb_test_canon( $a ) {
    if ( is_array( $a ) ) { ksort( $a ); foreach ( $a as &$v ) { $v = nb_test_canon( $v ); } }
    return $a;
}
$fixture_file = dirname( __DIR__, 5 ) . '/.discovery-baseline/sanitize-fixture.json'; // repo root
if ( file_exists( $fixture_file ) ) {
    $fixture = json_decode( file_get_contents( $fixture_file ), true );
    $got = nb_discovery_sanitize_payload( $fixture['raw'], nb_discovery_get_instance( 'overhead-door' ) );
    assert( nb_test_canon( $got ) === nb_test_canon( $fixture['expected'] ), 'OHDBalt sanitize output value-identical to pre-refactor fixture' );
    echo "fixture-lock: RAN\n"; // visible proof the gate executed (baseline dir exists only on this branch)
}

// --- Config-driven behavior with a synthetic instance ---
$fake = array(
    'slug' => 'fake', 'client_name' => 'Fake Co', 'recipient' => 'x@x.com',
    'services' => array( array( 'key' => 'website', 'group' => 'get_found', 'label' => 'W', 'hint' => 'h' ) ),
    'goal_vectors' => array( array( 'key' => 'alpha_beta', 'left' => 'Alpha', 'right' => 'Beta' ) ),
    'timeline_options' => array( 'ASAP' ),
    'section_copy' => array(),
    'welcome' => array( 'title' => '', 'intro' => '' ),
    'logo' => '',
    'systems_questions' => array(
        array( 'key' => 'pipeline', 'label' => 'P?', 'short' => 'P', 'type' => 'text' ),
        array( 'key' => 'photos',   'label' => 'F?', 'short' => 'F', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'access',   'label' => 'A?', 'short' => 'A', 'type' => 'radio',
               'options' => array( 'yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not sure' ), 'default' => 'unsure' ),
    ),
);
$rawf = array(
    'respondent' => array( 'name' => 'X', 'email' => 'x@x.com' ),
    'goal_vectors' => array( 'alpha_beta' => 999, 'residential_commercial' => 40 ),   // unknown key must be dropped
    'systems' => array( 'pipeline' => ' Trello <b>boards</b> ', 'photos' => "line1\nline2", 'access' => 'maybe', 'crm' => 'smuggled' ),
);
$cleanf = nb_discovery_sanitize_payload( $rawf, $fake );
assert( array_keys( $cleanf['goal_vectors'] ) === array( 'alpha_beta' ), 'vector keys come from instance config' );
assert( $cleanf['goal_vectors']['alpha_beta'] === 50, 'custom vector clamped' );
assert( array_keys( $cleanf['systems'] ) === array( 'pipeline', 'photos', 'access' ), 'systems keys come from config, unknown keys dropped' );
assert( $cleanf['systems']['pipeline'] === 'Trello boards', 'text sanitized' );
assert( strpos( $cleanf['systems']['photos'], "\n" ) !== false, 'textarea keeps newlines' );
assert( $cleanf['systems']['access'] === 'unsure', 'invalid radio value falls back to config default' );
```

- [ ] **Step 2: Run to verify failure**

```bash
cd ~/Herd/newblood/wp-content/themes/newblood
php -d zend.assertions=1 -d assert.exception=1 tests/discovery/test-sanitize.php
```

Expected: FAIL — `vector keys come from instance config` (old code emits the five fixed OHDBalt keys for the fake instance).

- [ ] **Step 3: Rewrite the hardcoded blocks in `nb_discovery_sanitize_payload`**

In `inc/discovery/submission.php`, delete the `$gbp` block (the two lines computing/validating `$gbp`) and replace the fixed `'goal_vectors' => array( … five keys … )` and `'systems' => array( … seven keys … )` entries of the return array. The `$vec`, `$txt`, `$area` closures stay. New code:

```php
    $goal_vectors = array();
    foreach ( $instance['goal_vectors'] as $v ) {
        $goal_vectors[ $v['key'] ] = $vec( $v['key'] );
    }

    $systems = array();
    foreach ( $instance['systems_questions'] as $q ) {
        $k   = $q['key'];
        $val = isset( $raw['systems'][ $k ] ) ? $raw['systems'][ $k ] : '';
        if ( $q['type'] === 'radio' ) {
            $systems[ $k ] = ( is_string( $val ) && isset( $q['options'][ $val ] ) ) ? $val : $q['default'];
        } elseif ( $q['type'] === 'textarea' ) {
            $systems[ $k ] = $area( $val );
        } else {
            $systems[ $k ] = $txt( $val );
        }
    }
```

…and in the return array: `'goal_vectors' => $goal_vectors,` and `'systems' => $systems,`.

- [ ] **Step 4: Run all tests**

```bash
for f in tests/discovery/test-*.php; do php -d zend.assertions=1 -d assert.exception=1 "$f"; done
```

Expected: five `PASS` lines (the fixture assertion now exercises the new path and must match the pre-refactor capture exactly — this is the payload-compatibility gate).

- [ ] **Step 5: Commit**

```bash
cd ~/Herd/newblood
git add wp-content/themes/newblood/inc/discovery/submission.php wp-content/themes/newblood/tests/discovery/test-sanitize.php
git commit -m "refactor(discovery): sanitize systems + goal vectors from instance config

OHDBalt payload shape locked by pre-refactor fixture test.

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 4: view.php + discovery.js — render and collect the systems section from config

**Files:**
- Modify: `inc/discovery/view.php` (step-3 section, lines ~141–158)
- Modify: `assets/js/discovery.js` (the `collect()` payload, lines ~83–115)
- Verify against: `.discovery-baseline/form.normalized.html`

**Interfaces:**
- Consumes: `$instance['systems_questions']` (Task 2 shape).
- Produces: step-3 `<section>` carries `id="nb-d-systems"`; every question input's `name` is its config `key`; radio default option rendered `checked`. JS builds `systems` from all named fields inside `#nb-d-systems` and `goal_vectors` from every `.nb-d-vector` group's `data-key` except `fix_invest`.

- [ ] **Step 1: Replace the hardcoded step-3 markup in view.php**

Replace everything between `<p class="nb-d-sub"><?php echo esc_html( $sc['systems'][1] ); ?></p>` and the section's closing `</section>` (the seven hardcoded fields) with:

```php
      <?php foreach ( $instance['systems_questions'] as $q ) : ?>
        <?php if ( $q['type'] === 'radio' ) : ?>
      <fieldset class="nb-d-field nb-d-radios">
        <legend><?php echo esc_html( $q['label'] ); ?></legend>
        <?php foreach ( $q['options'] as $oval => $olabel ) : ?>
        <label><input type="radio" name="<?php echo esc_attr( $q['key'] ); ?>" value="<?php echo esc_attr( $oval ); ?>"<?php echo ( $q['default'] === $oval ) ? ' checked' : ''; ?>> <?php echo esc_html( $olabel ); ?></label>
        <?php endforeach; ?>
      </fieldset>
        <?php elseif ( $q['type'] === 'textarea' ) : ?>
      <label class="nb-d-field"><span><?php echo esc_html( $q['label'] ); ?></span><textarea name="<?php echo esc_attr( $q['key'] ); ?>" rows="<?php echo (int) ( isset( $q['rows'] ) ? $q['rows'] : 3 ); ?>"></textarea></label>
        <?php else : ?>
      <label class="nb-d-field"><span><?php echo esc_html( $q['label'] ); ?></span><input type="text" name="<?php echo esc_attr( $q['key'] ); ?>"><?php if ( ! empty( $q['hint'] ) ) : ?><span class="nb-d-field-hint"><?php echo esc_html( $q['hint'] ); ?></span><?php endif; ?></label>
        <?php endif; ?>
      <?php endforeach; ?>
```

And change the section's opening tag from `<section class="nb-d-section">` (the step-3 one only — the comment `<!-- 3. What's in place today` sits right above it) to:

```php
    <section class="nb-d-section" id="nb-d-systems">
```

- [ ] **Step 2: Make discovery.js collection generic**

In `assets/js/discovery.js` `collect()`, replace the `var gbp = …` line and the `goal_vectors:` / `systems:` members of the returned object with:

```js
    var goal_vectors = {};
    Object.keys(vectors).forEach(function (k) {
      if (k !== 'fix_invest') goal_vectors[k] = vectors[k] || 0;
    });

    var systems = {};
    var sysSection = document.getElementById('nb-d-systems');
    if (sysSection) {
      Array.prototype.forEach.call(sysSection.querySelectorAll('input[type="text"], textarea'), function (el) {
        if (el.name) systems[el.name] = el.value.trim();
      });
      var radioNames = {};
      Array.prototype.forEach.call(sysSection.querySelectorAll('input[type="radio"]'), function (el) {
        if (el.name) radioNames[el.name] = true;
      });
      Object.keys(radioNames).forEach(function (n) {
        var checked = sysSection.querySelector('input[name="' + n + '"]:checked');
        systems[n] = checked ? checked.value : '';
      });
    }
```

…and in the returned object literal use `goal_vectors: goal_vectors,` and `systems: systems,`.

- [ ] **Step 3: Diff the rendered OHDBalt form against baseline**

```bash
cd ~/Herd/newblood
curl -s http://newblood.test/discovery/overhead-door \
  | sed -e '/window.nbDiscovery/d' -e 's/&#8217;/’/g' -e 's/ id="nb-d-systems"//' \
  | sed -e 's/[[:space:]]\+/ /g' > .discovery-baseline/form.after.normalized.html
diff .discovery-baseline/form.normalized.html .discovery-baseline/form.after.normalized.html && echo "FORM IDENTICAL"
```

Expected: `FORM IDENTICAL` (the sed strips the one intentionally-added id; everything else must match after whitespace/entity normalization). Any other diff is a regression — fix before proceeding.

- [ ] **Step 4: Browser sanity check of the OHDBalt form**

Open `http://newblood.test/discovery/overhead-door`: step-3 shows all 7 fields in order, "Not sure" pre-selected on the GBP radio, the leads-per-month hint present. Fill nothing; open devtools console and confirm no JS errors on load.

- [ ] **Step 5: Run tests, commit**

```bash
cd ~/Herd/newblood/wp-content/themes/newblood
for f in tests/discovery/test-*.php; do php -d zend.assertions=1 -d assert.exception=1 "$f"; done
cd ~/Herd/newblood
git add wp-content/themes/newblood/inc/discovery/view.php wp-content/themes/newblood/assets/js/discovery.js
git commit -m "refactor(discovery): render + collect systems section from instance config

OHDBalt form verified normalized-identical to pre-refactor snapshot.

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 5: email.php + aggregate.php + report.php — config-driven labels and field lists

**Files:**
- Modify: `inc/discovery/email.php` (SYSTEMS TODAY block, lines ~51–58)
- Modify: `inc/discovery/aggregate.php` (`$sys_fields`, line ~122)
- Modify: `inc/discovery/report.php` (`$qual_labels`, lines ~169–172)
- Test: `tests/discovery/test-email.php`, `tests/discovery/test-aggregate.php`
- Verify against: `.discovery-baseline/aggregate.json`

**Interfaces:**
- Consumes: `$instance['systems_questions']` (`key`, `short`).
- Produces: email/report system labels come from `short`; aggregate `qualitative` keys come from config. No signature changes.

- [ ] **Step 1: Add failing tests**

Append to `tests/discovery/test-email.php` (before final `echo`) — the fixed instance already reaches this via Task 2's config:

```php
// Systems lines must use the config 'short' labels.
assert( strpos( $mail['body'], 'CRM today: None' ) !== false, 'systems line uses short label' );
assert( strpos( $mail['body'], 'Google Business Profile access: yes' ) !== false, 'gbp line uses short label' );
```

Append to `tests/discovery/test-aggregate.php` (before final `echo`):

```php
// Qualitative field list must follow the instance's systems_questions config.
$fake_sys = $instance; // overhead-door base
$fake_sys['systems_questions'] = array(
    array( 'key' => 'photos', 'label' => 'F?', 'short' => 'Photos', 'type' => 'text' ),
);
$chase2 = $chase; $chase2['payload']['systems'] = array( 'photos' => 'Dropbox' );
$agg2 = nb_discovery_aggregate( array( $chase2 ), $fake_sys );
assert( isset( $agg2['qualitative']['photos'] ), 'config-driven qualitative key present' );
assert( ! isset( $agg2['qualitative']['crm'] ), 'non-config qualitative key absent' );
assert( $agg2['qualitative']['photos'][0]['value'] === 'Dropbox', 'qualitative value carried' );
```

Run both; expected failures: `systems line uses short label` (email currently says `CRM: None`) and `non-config qualitative key absent`.

- [ ] **Step 2: email.php — generate systems lines from config**

Replace the seven fixed `$lines[] = 'CRM: …'` … `'Territories: …'` lines with:

```php
    foreach ( $instance['systems_questions'] as $q ) {
        $val = isset( $record['systems'][ $q['key'] ] ) ? $record['systems'][ $q['key'] ] : '';
        $lines[] = $q['short'] . ': ' . $val;
    }
```

- [ ] **Step 3: aggregate.php — field list from config**

Replace `$sys_fields  = array( 'crm', 'leads_per_month', … );` with:

```php
    $sys_fields  = array_column( $instance['systems_questions'], 'key' );
```

- [ ] **Step 4: report.php — qualitative labels from config**

Replace the fixed `$qual_labels = array( 'vision' => …, …, 'gbp_access' => … );` with:

```php
    $qual_labels = array( 'vision' => '3-year vision', 'open' => 'Anything else' );
    foreach ( $instance['systems_questions'] as $q ) {
        $qual_labels[ $q['key'] ] = $q['short'];
    }
```

- [ ] **Step 5: Run all tests**

```bash
cd ~/Herd/newblood/wp-content/themes/newblood
for f in tests/discovery/test-*.php; do php -d zend.assertions=1 -d assert.exception=1 "$f"; done
```

Expected: five `PASS` lines.

- [ ] **Step 6: Diff the aggregate JSON against baseline (real Chase data)**

```bash
cd ~/Herd/newblood
wp eval-file .discovery-baseline/dump-aggregate.php > .discovery-baseline/aggregate.after.json
diff .discovery-baseline/aggregate.json .discovery-baseline/aggregate.after.json && echo "AGGREGATE IDENTICAL"
```

Expected: `AGGREGATE IDENTICAL` (the dump script ksorts `qualitative`, so the allowed heading reorder doesn't appear as a diff; values and every other section must match exactly).

- [ ] **Step 7: Eyeball the live report**

Log into `newblood.test/wp-admin`, open `http://newblood.test/discovery/overhead-door/report`: all seven "In their words" headings present with the same labels as before (order: CRM today → Lead handling today → Web leads / month → Reviews system → Call tracking → Google Business Profile access → Territories), Chase's answers intact, gap map unchanged.

- [ ] **Step 8: Commit**

```bash
cd ~/Herd/newblood
git add wp-content/themes/newblood/inc/discovery/email.php wp-content/themes/newblood/inc/discovery/aggregate.php wp-content/themes/newblood/inc/discovery/report.php wp-content/themes/newblood/tests/discovery/test-email.php wp-content/themes/newblood/tests/discovery/test-aggregate.php
git commit -m "refactor(discovery): email/report/aggregate labels + field lists from config

Aggregate over real OHDBalt submissions verified identical to baseline.

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 6: The calindman instance — config, logo, tests, local E2E

**Files:**
- Modify: `inc/discovery/config.php` (new `calindman` entry after `overhead-door`)
- Create: `assets/img/discovery-calindman.png` (copied from the calindman site)
- Test: `tests/discovery/test-config.php`

**Interfaces:**
- Consumes: everything above.
- Produces: `nb_discovery_get_instance( 'calindman' )` — live form at `/discovery/calindman`.

- [ ] **Step 1: Add failing config assertions**

Append to `tests/discovery/test-config.php` (before final `echo`):

```php
// --- calindman instance ---
$cal = nb_discovery_get_instance( 'calindman' );
assert( is_array( $cal ), 'calindman instance exists' );
assert( $cal['client_name'] === 'C.A. Lindman, Inc.', 'calindman client name' );
assert( $cal['recipient'] === 'joms@newblood.com', 'calindman recipient' );
assert( count( $cal['services'] ) === 12, 'calindman 12 service rows' );
assert( array_column( $cal['services'], 'key' ) === array( 'website','seo_aeo','brand_creative','portfolio','lead_capture','reviews','hosting_security','crm','customer_comms','recruiting','lead_gen','reporting' ), 'calindman service keys in clustered order' );
assert( array_column( $cal['goal_vectors'], 'key' ) === array( 'volume_fit','deepen_expand','cal_crw','topline_lean','handson_managed' ), 'calindman vector keys' );
assert( array_column( $cal['systems_questions'], 'key' ) === array( 'pipeline_tracking','lead_handling','lead_sources','photo_library','gbp_access','coverage' ), 'calindman systems keys' );
```

Run `php -d zend.assertions=1 -d assert.exception=1 tests/discovery/test-config.php` — expected FAIL: `calindman instance exists`.

- [ ] **Step 2: Copy the logo asset**

```bash
cp ~/Herd/calindman/wp-content/uploads/2019/05/logo-green.png \
   ~/Herd/newblood/wp-content/themes/newblood/assets/img/discovery-calindman.png
ls -la ~/Herd/newblood/wp-content/themes/newblood/assets/img/discovery-calindman.png
```

(If `assets/img/` doesn't exist, `mkdir -p` it first. If this exact source file is missing, find one: `ls ~/Herd/calindman/wp-content/uploads/2019/05/ | grep -i logo` and pick the green logo.)

- [ ] **Step 3: Add the calindman entry to `nb_discovery_instances()`**

Insert after the `overhead-door` entry's closing `),`:

```php
        'calindman' => array(
            'slug'        => 'calindman',
            'client_name' => 'C.A. Lindman, Inc.',
            'logo'        => '/wp-content/themes/newblood/assets/img/discovery-calindman.png',
            'recipient'   => 'joms@newblood.com',
            'welcome'     => array(
                'title' => 'Let’s build this around C.A. Lindman',
                'intro' => 'Thank you for the chance to help shape what’s next for C.A. Lindman online. The questions below take about 10 minutes — your answers shape a plan built around CAL and CRW, not a template.',
            ),
            'services' => array(
                array( 'key' => 'website',          'group' => 'get_found', 'label' => 'Website design & user experience',        'hint' => 'How the site looks, feels, and guides visitors.' ),
                array( 'key' => 'seo_aeo',          'group' => 'get_found', 'label' => 'Search & AI-answer visibility (SEO/AEO)', 'hint' => 'Being found in Google search and in AI answers like ChatGPT.' ),
                array( 'key' => 'brand_creative',   'group' => 'get_found', 'label' => 'Brand & creative',                        'hint' => 'Project photography and video that present the company well.' ),
                array( 'key' => 'portfolio',        'group' => 'convert',   'label' => 'Project portfolio & case studies',        'hint' => 'Showcasing completed work — galleries, before/after, project stories.' ),
                array( 'key' => 'lead_capture',     'group' => 'convert',   'label' => 'Lead capture & conversion',               'hint' => 'Turning visitors into project inquiries and RFPs.' ),
                array( 'key' => 'reviews',          'group' => 'convert',   'label' => 'Reviews, reputation & references',        'hint' => 'Earning and showcasing reviews and client references.' ),
                array( 'key' => 'hosting_security', 'group' => 'operate',   'label' => 'Hosting, security & maintenance',         'hint' => 'Keeping the site fast, online, secure, and up to date.' ),
                array( 'key' => 'crm',              'group' => 'operate',   'label' => 'CRM / bid & project pipeline',            'hint' => 'One place to track opportunities from inquiry to close.' ),
                array( 'key' => 'customer_comms',   'group' => 'operate',   'label' => 'Client communication',                    'hint' => 'Following up with prospects and clients by email and text.' ),
                array( 'key' => 'recruiting',       'group' => 'grow',      'label' => 'Recruiting & careers',                    'hint' => 'Attracting field talent and making hiring easier.' ),
                array( 'key' => 'lead_gen',         'group' => 'grow',      'label' => 'Lead generation',                         'hint' => 'Driving new prospects through paid search and social ads.' ),
                array( 'key' => 'reporting',        'group' => 'grow',      'label' => 'Reporting & analytics',                   'hint' => 'Clear reporting on what’s working and what it’s producing.' ),
            ),
            'goal_vectors' => array(
                array( 'key' => 'volume_fit',      'left' => 'More project volume',     'right' => 'Better-fit projects' ),
                array( 'key' => 'deepen_expand',   'left' => 'Deepen current markets',  'right' => 'Expand into new regions' ),
                array( 'key' => 'cal_crw',         'left' => 'Focus on CAL',            'right' => 'One plan across CAL + CRW' ),
                array( 'key' => 'topline_lean',    'left' => 'Grow the top line',       'right' => 'Run leaner' ),
                array( 'key' => 'handson_managed', 'left' => 'We stay hands-on',        'right' => 'Fully managed for us' ),
            ),
            'timeline_options' => array( 'As soon as possible', 'Within 1–3 months', '3–6 months', 'Just exploring' ),
            'systems_questions' => array(
                array( 'key' => 'pipeline_tracking', 'label' => 'How do you track leads, bids, and projects today? (CRM, spreadsheets, something else)', 'short' => 'Pipeline tracking today', 'type' => 'text' ),
                array( 'key' => 'lead_handling',     'label' => 'When a project inquiry comes in through the website today, what happens?',              'short' => 'Inquiry handling today',  'type' => 'textarea', 'rows' => 3 ),
                array( 'key' => 'lead_sources',      'label' => 'Where do most new project opportunities come from today? (referrals, repeat clients, search, bid lists…)', 'short' => 'Where opportunities come from', 'type' => 'text' ),
                array( 'key' => 'photo_library',     'label' => 'Where do project photos and job documentation live today?',                             'short' => 'Project photo library',   'type' => 'text' ),
                array( 'key' => 'gbp_access',        'label' => 'Can you grant manager access to your Google Business Profile(s)?',                      'short' => 'Google Business Profile access', 'type' => 'radio',
                       'options' => array( 'yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not sure' ), 'default' => 'unsure' ),
                array( 'key' => 'coverage',          'label' => 'Which companies and locations should this plan cover? (C.A. Lindman, CRW, both)',       'short' => 'Coverage (CAL / CRW)',    'type' => 'textarea', 'rows' => 2 ),
            ),
            'section_copy' => array(
                'priorities' => array( 'What matters most', 'Rate how important each capability is to you. Where it’s critical, we’ll ask how well it’s handled today.' ),
                'goals'      => array( 'Where you’re headed', 'A few questions about the direction of the business.' ),
                'systems'    => array( 'What’s in place today', 'Light context on your current systems — a sentence each is plenty.' ),
                'direction'  => array( 'Direction & timing', 'How you’re thinking about this work.' ),
                'open'       => array( 'Anything else', 'The floor is yours.' ),
            ),
        ),
```

- [ ] **Step 4: Run all tests**

```bash
cd ~/Herd/newblood/wp-content/themes/newblood
for f in tests/discovery/test-*.php; do php -d zend.assertions=1 -d assert.exception=1 "$f"; done
```

Expected: five `PASS` lines (test-config's per-instance loop validates the new entry's shape automatically).

- [ ] **Step 5: Local E2E — render, submit, store, report**

```bash
# Form renders with CAL branding
curl -s http://newblood.test/discovery/calindman | grep -c 'C.A. Lindman'          # expect >= 2
curl -s http://newblood.test/discovery/calindman | grep -c 'Project portfolio'     # expect >= 1
curl -s -o /dev/null -w "logo: HTTP %{http_code}\n" http://newblood.test/wp-content/themes/newblood/assets/img/discovery-calindman.png  # expect 200

# Submit a test payload through the real REST endpoint
NONCE_PAGE=$(curl -s -c /tmp/nbcookies http://newblood.test/discovery/calindman)
NONCE=$(echo "$NONCE_PAGE" | grep -o '"nonce":"[^"]*"' | cut -d'"' -f4)
curl -s -b /tmp/nbcookies -X POST http://newblood.test/wp-json/newblood/v1/discovery \
  -H "Content-Type: application/json" -H "X-WP-Nonce: $NONCE" \
  -d '{"instance":"calindman","hp":"","respondent":{"name":"Local Test","email":"test@example.com"},
       "services":[{"key":"website","importance":10,"handling":3},{"key":"portfolio","importance":9,"handling":2}],
       "vision":"Modern site that wins bids.",
       "goal_vectors":{"volume_fit":25,"deepen_expand":-25,"cal_crw":50,"topline_lean":0,"handson_managed":25},
       "systems":{"pipeline_tracking":"Spreadsheets","lead_handling":"Email to front office","lead_sources":"Referrals","photo_library":"Shared drive","gbp_access":"unsure","coverage":"Both CAL and CRW"},
       "posture":{"fix_invest":40,"timeline":"Within 1–3 months"},"open":"Test row - exclude me"}'
```

Expected: JSON success response. Then verify storage and report:

```bash
cd ~/Herd/newblood
wp db query "SELECT id, instance, respondent_name FROM wp_nb_discovery_responses WHERE instance='calindman' ORDER BY id DESC LIMIT 1"
```

Expected: one row, `Local Test`. In a logged-in browser open `http://newblood.test/discovery/calindman/report` — gap map shows website/portfolio, vectors show the CAL pairs, "In their words" shows the six CAL shorts. **Then exclude the test row** via the report's Exclude button (keeps local data clean; it stays in the table, flagged).

- [ ] **Step 6: Verify overhead-door is still intact (regression re-check)**

```bash
cd ~/Herd/newblood
curl -s http://newblood.test/discovery/overhead-door | sed -e '/window.nbDiscovery/d' -e 's/&#8217;/’/g' -e 's/ id="nb-d-systems"//' | sed -e 's/[[:space:]]\+/ /g' | diff .discovery-baseline/form.normalized.html - && echo "OHD FORM STILL IDENTICAL"
```

Expected: `OHD FORM STILL IDENTICAL`.

- [ ] **Step 7: Commit**

```bash
cd ~/Herd/newblood
git add wp-content/themes/newblood/inc/discovery/config.php wp-content/themes/newblood/tests/discovery/test-config.php wp-content/themes/newblood/assets/img/discovery-calindman.png
git commit -m "feat(discovery): add C.A. Lindman instance at /discovery/calindman

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
```

---

### Task 7: Merge to the live line, deploy, prod verify (JEREMY CHECKPOINT)

**Files:** none new — merge + deploy.

- [ ] **Step 1: Final full check + cleanup**

```bash
cd ~/Herd/newblood/wp-content/themes/newblood
for f in tests/discovery/test-*.php; do php -d zend.assertions=1 -d assert.exception=1 "$f"; done
cd ~/Herd/newblood && rm -rf .discovery-baseline
```

Note: test-sanitize's fixture assertion is written to skip silently when `.discovery-baseline/` is absent, so deleting it is safe.

- [ ] **Step 2: CHECKPOINT — show Jeremy the local form**

Pause here. Jeremy reviews `http://newblood.test/discovery/calindman` (copy, rows, sliders, logo) before anything ships. Do not proceed without his OK.

- [ ] **Step 3: Merge into feature/redesign**

```bash
cd ~/Herd/newblood
git switch feature/redesign
git merge --no-ff feature/discovery-calindman -m "Merge discovery config refactor + calindman instance

Co-Authored-By: Claude Fable 5 <noreply@anthropic.com>"
git push
```

- [ ] **Step 4: Deploy the theme**

```bash
cd ~/Herd/newblood && ./deploy.sh --dry-run   # review: only theme files, incl. inc/discovery/* + assets
./deploy.sh                                    # confirm when prompted
```

- [ ] **Step 5: Prod verification**

```bash
curl -s -o /dev/null -w "calindman form: HTTP %{http_code}\n" https://newblood.com/discovery/calindman        # expect 200
curl -s https://newblood.com/discovery/calindman | grep -c 'C.A. Lindman'                                     # expect >= 2
curl -s -o /dev/null -w "OHD form: HTTP %{http_code}\n" https://newblood.com/discovery/overhead-door           # expect 200
curl -s -o /dev/null -w "logo: HTTP %{http_code}\n" https://newblood.com/wp-content/themes/newblood/assets/img/discovery-calindman.png  # expect 200
curl -s -o /dev/null -w "unknown slug: HTTP %{http_code}\n" https://newblood.com/discovery/nope                # expect 404
```

Then one real prod test submission (name "Jeremy Test") via the browser; confirm the notification email arrives at joms@newblood.com with the CAL short labels; open `https://newblood.com/discovery/calindman/report` (admin) and exclude the test row.

- [ ] **Step 6: Update tracking (per ~/Herd CLAUDE.md protocol)**

`newblood/TASKS.md` + `calindman/TASKS.md` + `_hub/PROJECTS.md`: discovery form live at newblood.com/discovery/calindman, awaiting Jeremy to send the delivery email (draft in spec) — alongside the About Us updates. Propose a CRM `deal next`/`log` update for the C.A. Lindman deal (propose → confirm → run).
