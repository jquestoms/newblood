# Discovery Form Intake Refinement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the discovery form's sliders with segmented buttons, cluster the 12 capabilities into 4 named groups, lead with the vision section, and add a free-text "web leads/month" baseline question — without changing the stored 0–10 data model.

**Architecture:** The form is a standalone PHP-rendered page (`inc/discovery/view.php`) posting JSON to a REST endpoint that sanitizes (`submission.php`), stores, aggregates (`aggregate.php`), and renders an admin report (`report.php`) + email (`email.php`). Segmented buttons store the same 0–10 / −50…50 integer values the sliders did, so the entire storage/aggregate/report/email pipeline is unchanged except for one new pass-through text field. Pure-logic PHP is unit-tested under plain CLI via `tests/discovery/`; the view/JS/CSS layers are verified by lint + live render + manual QA (no DOM harness exists in this repo).

**Tech Stack:** WordPress block theme, vanilla PHP (no framework in the discovery module), vanilla ES5-style JS, hand-written CSS. Tests are standalone `assert()` scripts run with `php`.

## Global Constraints

- WP-CLI (if needed) must be invoked as: `php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood <subcommand>`
- Run the PHP test suite with assertions forced on: `php -d zend.assertions=1 -d assert.exception=1 <file>`
- `php -l <file>` after every PHP edit — discovery PHP is parsed at request time.
- Image/asset URLs in markup must be root-relative, never absolute.
- CSS/JS under `assets/` is cache-busted by `filemtime()` — never add `?ver=` or bump a constant.
- The 0–10 importance scale and `NB_DISCOVERY_THRESHOLD = 7` are unchanged. Importance buttons store `0 / 3 / 7 / 10`; handling buttons store `2 / 5 / 7 / 10`; vector buttons store `−50 / −25 / 0 / +25 / +50`.
- Handling follow-up reveals when importance ≥ 7 (i.e. Important **and** Critical).
- An untouched importance row is **omitted** from the submitted `services` array (not submitted as 0).
- Voice: deliberative, not fast. No "quick/fast turnaround" framing in any copy.
- Local dev URL: `http://newblood.test/discovery/overhead-door`. Report (admin-only): `…/overhead-door/report`.

---

### Task 1: Config — cluster groups + reordered services

**Files:**
- Modify: `wp-content/themes/newblood/inc/discovery/config.php`
- Test: `wp-content/themes/newblood/tests/discovery/test-config.php`

**Interfaces:**
- Produces: `nb_discovery_service_groups(): array` returning ordered `group_key => label`. Each entry in the instance's `services` array gains a `'group'` key whose value is one of the group keys. Service display order is regrouped: `get_found, convert, operate, grow`.

- [ ] **Step 1: Update the failing test first**

In `tests/discovery/test-config.php`, replace the `$expected` order assertion block (lines ~11–16) with the new clustered order and a group-key check:

```php
$keys = array_column( $inst['services'], 'key' );
$expected = array( 'website','seo_aeo','brand_creative','lead_capture','reviews','content','hosting_security','crm','customer_comms','automation_ai','lead_gen','reporting' );
assert( $keys === $expected, 'service keys match clustered order' );

$groups = nb_discovery_service_groups();
assert( array_keys( $groups ) === array( 'get_found','convert','operate','grow' ), 'four ordered groups' );
$valid_groups = array_keys( $groups );
foreach ( $inst['services'] as $s ) {
    assert( ! empty( $s['label'] ) && ! empty( $s['hint'] ), "service {$s['key']} has label + hint" );
    assert( isset( $s['group'] ) && in_array( $s['group'], $valid_groups, true ), "service {$s['key']} has a valid group" );
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php -d zend.assertions=1 -d assert.exception=1 wp-content/themes/newblood/tests/discovery/test-config.php`
Expected: FAIL — `Call to undefined function nb_discovery_service_groups()` (or an assertion failure on order).

- [ ] **Step 3: Add the groups helper to config.php**

In `inc/discovery/config.php`, after the `nb_discovery_get_instance()` function, add:

```php
/**
 * Ordered capability clusters for the priorities section. Key => display label.
 */
function nb_discovery_service_groups(): array {
    return array(
        'get_found' => 'Get found',
        'convert'   => 'Convert',
        'operate'   => 'Operate',
        'grow'      => 'Grow',
    );
}
```

- [ ] **Step 4: Reorder services and add `group` keys**

In `inc/discovery/config.php`, replace the entire `'services' => array( ... ),` block with this regrouped, `group`-tagged version (same labels/hints, new order):

```php
            'services' => array(
                array( 'key' => 'website',          'group' => 'get_found', 'label' => 'Website design & user experience',        'hint' => 'How the site looks, feels, and guides visitors.' ),
                array( 'key' => 'seo_aeo',          'group' => 'get_found', 'label' => 'Search & AI-answer visibility (SEO/AEO)', 'hint' => 'Being found in Google search and in AI answers like ChatGPT.' ),
                array( 'key' => 'brand_creative',   'group' => 'get_found', 'label' => 'Brand & creative',                        'hint' => 'Logo, photography, and video that present the brand well.' ),
                array( 'key' => 'lead_capture',     'group' => 'convert',   'label' => 'Lead capture & conversion',               'hint' => 'Turning visitors into inquiries — forms, funnels, calls-to-action.' ),
                array( 'key' => 'reviews',          'group' => 'convert',   'label' => 'Reviews & online reputation',             'hint' => 'Earning, showcasing, and responding to reviews.' ),
                array( 'key' => 'content',          'group' => 'convert',   'label' => 'Content',                                 'hint' => 'Service pages, FAQs, and fresh content over time.' ),
                array( 'key' => 'hosting_security', 'group' => 'operate',   'label' => 'Hosting, security & maintenance',         'hint' => 'Keeping the site fast, online, secure, and up to date.' ),
                array( 'key' => 'crm',              'group' => 'operate',   'label' => 'CRM / customer & job pipeline',           'hint' => 'One place to track customers and jobs from inquiry to close.' ),
                array( 'key' => 'customer_comms',   'group' => 'operate',   'label' => 'Customer communication',                  'hint' => 'Following up with leads and customers by email and text.' ),
                array( 'key' => 'automation_ai',    'group' => 'operate',   'label' => 'Automation & AI assistants',              'hint' => 'Automated routing and on-site AI chat that answers and books.' ),
                array( 'key' => 'lead_gen',         'group' => 'grow',      'label' => 'Lead generation',                         'hint' => 'Driving new prospects through paid search and social ads.' ),
                array( 'key' => 'reporting',        'group' => 'grow',      'label' => 'Reporting & analytics',                   'hint' => 'Clear reporting on what’s working and what it’s producing.' ),
            ),
```

- [ ] **Step 5: Lint + run the test to verify it passes**

Run: `php -l wp-content/themes/newblood/inc/discovery/config.php`
Expected: `No syntax errors detected`
Run: `php -d zend.assertions=1 -d assert.exception=1 wp-content/themes/newblood/tests/discovery/test-config.php`
Expected: `test-config: PASS`

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/config.php wp-content/themes/newblood/tests/discovery/test-config.php
git commit -m "feat(discovery): cluster capabilities into 4 ordered groups"
```

---

### Task 2: `leads_per_month` pass-through (sanitize → aggregate → email → report)

**Files:**
- Modify: `wp-content/themes/newblood/inc/discovery/submission.php` (systems sanitize block, ~lines 56–63)
- Modify: `wp-content/themes/newblood/inc/discovery/aggregate.php` (`$sys_fields`, ~line 122)
- Modify: `wp-content/themes/newblood/inc/discovery/email.php` (SYSTEMS TODAY block, ~lines 51–57)
- Modify: `wp-content/themes/newblood/inc/discovery/report.php` (`$qual_labels`, ~lines 168–172)
- Test: `wp-content/themes/newblood/tests/discovery/test-sanitize.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: the cleaned payload's `systems` array now carries `leads_per_month` (sanitized text). The aggregate exposes it under `qualitative['leads_per_month']`.

- [ ] **Step 1: Add the failing assertion to test-sanitize.php**

In `tests/discovery/test-sanitize.php`, add `'leads_per_month' => '  about 5 a month <b>maybe</b> '` into the `$raw['systems']` array, then add before the final `echo`:

```php
assert( $clean['systems']['leads_per_month'] === 'about 5 a month maybe', 'leads_per_month sanitized + passed through' );
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php -d zend.assertions=1 -d assert.exception=1 wp-content/themes/newblood/tests/discovery/test-sanitize.php`
Expected: FAIL — `leads_per_month sanitized + passed through` (key is currently undefined → null).

- [ ] **Step 3: Sanitize the field in submission.php**

In `inc/discovery/submission.php`, inside the returned `'systems' => array( ... )`, add after the `'crm'` line:

```php
            'leads_per_month' => $txt( isset( $raw['systems']['leads_per_month'] ) ? $raw['systems']['leads_per_month'] : '' ),
```

- [ ] **Step 4: Run the test to verify it passes**

Run: `php -d zend.assertions=1 -d assert.exception=1 wp-content/themes/newblood/tests/discovery/test-sanitize.php`
Expected: `test-sanitize: PASS`

- [ ] **Step 5: Surface it in aggregate, email, and report**

In `inc/discovery/aggregate.php`, add `'leads_per_month'` to the `$sys_fields` array:

```php
    $sys_fields  = array( 'crm', 'leads_per_month', 'lead_handling', 'reviews_system', 'call_tracking', 'territories', 'gbp_access' );
```

In `inc/discovery/email.php`, in the SYSTEMS TODAY block, add after the `CRM:` line:

```php
    $lines[] = 'Web leads/month: ' . ( isset( $record['systems']['leads_per_month'] ) ? $record['systems']['leads_per_month'] : '' );
```

In `inc/discovery/report.php`, add to the `$qual_labels` array (after `'crm'`):

```php
      'leads_per_month' => 'Web leads / month',
```

- [ ] **Step 6: Lint all four files + run the full suite**

Run: `for f in submission aggregate email report; do php -l wp-content/themes/newblood/inc/discovery/$f.php; done`
Expected: `No syntax errors detected` ×4
Run: `for f in wp-content/themes/newblood/tests/discovery/test-*.php; do php -d zend.assertions=1 -d assert.exception=1 "$f"; done`
Expected: all five print `PASS`.

- [ ] **Step 7: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/submission.php wp-content/themes/newblood/inc/discovery/aggregate.php wp-content/themes/newblood/inc/discovery/email.php wp-content/themes/newblood/inc/discovery/report.php wp-content/themes/newblood/tests/discovery/test-sanitize.php
git commit -m "feat(discovery): capture web leads/month baseline through the pipeline"
```

---

### Task 3: View markup — vision-first order, segmented controls, clusters, baseline, progress, skip line

**Files:**
- Modify: `wp-content/themes/newblood/inc/discovery/view.php` (the `<header>` + entire `<form>` body)

**Interfaces:**
- Produces the DOM contract Task 4's JS consumes:
  - Importance group per service: `<div class="nb-d-seg nb-d-importance" data-key="…">` (the `data-key` is emitted by `nb_discovery_seg()`) containing `<button type="button" class="nb-d-seg-btn" data-val="0|3|7|10">`.
  - Handling group (inside `.nb-d-handling[hidden]`): `<div class="nb-d-seg nb-d-handling-seg">` with `data-val="2|5|7|10"` buttons (no `data-key`).
  - Vector group: `<div class="nb-d-seg nb-d-vector" data-key="…">` with `data-val="-50|-25|0|25|50"` buttons; the `0` button carries `class="… is-selected" aria-checked="true"` by default.
  - Selected state class: `is-selected` (+ `aria-checked="true"`).
  - Progress bar: `<div class="nb-d-progress"><span class="nb-d-progress-fill"></span></div>` as the first child of `<main>`.
  - New field: `<input type="text" name="leads_per_month">`.

  The group's own `data-key` carries the service/vector key — there are **no** hidden `data-key` inputs. The `.nb-d-service` wrapper also still has a `data-key` attribute, but JS reads the key from the importance group.

This task is verified by `php -l`, block-comment balance, and a live render check — there is no DOM unit test.

- [ ] **Step 1: Add a segmented-group render helper at the top of view.php**

In `inc/discovery/view.php`, immediately after `function nb_discovery_render_page( $instance ) {`'s opening (before the `$ver_css` line is fine, but place it as a separate function above `nb_discovery_render_page`), add this helper so markup stays DRY:

```php
/**
 * Render one segmented button group. $opts is an ordered array of [ value => label ].
 * $selected (string|null) pre-selects a button by value. $data_key (string|null),
 * when set, is emitted as data-key on the group so JS can identify it.
 */
function nb_discovery_seg( array $classes, array $opts, $aria_label, $selected = null, $data_key = null ) {
    $cls = implode( ' ', array_map( 'sanitize_html_class', $classes ) );
    $key_attr = ( $data_key !== null ) ? ' data-key="' . esc_attr( $data_key ) . '"' : '';
    echo '<div class="nb-d-seg ' . esc_attr( $cls ) . '"' . $key_attr . ' role="radiogroup" aria-label="' . esc_attr( $aria_label ) . '">';
    foreach ( $opts as $val => $label ) {
        $is = ( (string) $val === (string) $selected );
        echo '<button type="button" class="nb-d-seg-btn' . ( $is ? ' is-selected' : '' ) . '" role="radio" aria-checked="' . ( $is ? 'true' : 'false' ) . '" data-val="' . esc_attr( (string) $val ) . '">' . esc_html( $label ) . '</button>';
    }
    echo '</div>';
}
```

- [ ] **Step 2: Add the progress bar + skip line, and replace the `<header>` intro**

In `nb_discovery_render_page()`, inside `<main class="nb-d-shell">`, make the progress bar the first element:

```php
<main class="nb-d-shell">
  <div class="nb-d-progress" aria-hidden="true"><span class="nb-d-progress-fill"></span></div>
```

Keep the existing `<header class="nb-d-welcome …">` block, but append a skip line after the lede paragraph:

```php
    <p class="nb-d-lede"><?php echo esc_html( $instance['welcome']['intro'] ); ?></p>
    <p class="nb-d-skip">Answer what you can — skip anything that doesn’t apply, and we’ll fill the gaps when we talk.</p>
  </header>
```

- [ ] **Step 3: Replace the form body — section order, segmented controls, clusters, baseline**

Replace everything inside `<form id="nb-discovery-form" …>` (keep the opening `<form>` tag and the honeypot `<div class="nb-d-hp">` exactly as they are; replace the five `<section>` blocks) with the following. Section eyebrows now read "Step N of 5" and the order leads with vision:

```php
    <!-- 1. Where you're headed (vision + direction vectors) -->
    <section class="nb-d-section">
      <p class="nb-d-step">Step 1 of 5</p>
      <h2><?php echo esc_html( $sc['goals'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['goals'][1] ); ?></p>
      <label class="nb-d-field">
        <span>In 3 years, what does winning look like?</span>
        <textarea name="vision" rows="4"></textarea>
      </label>
      <?php foreach ( $instance['goal_vectors'] as $v ) : ?>
      <div class="nb-d-vector-row">
        <span class="nb-d-vector-cap"><?php echo esc_html( $v['left'] ); ?></span>
        <?php nb_discovery_seg(
            array( 'nb-d-vector' ),
            array( '-50' => 'Strongly', '-25' => 'Lean', '0' => 'No pref', '25' => 'Lean', '50' => 'Strongly' ),
            $v['left'] . ' versus ' . $v['right'],
            '0',
            $v['key']
        ); ?>
        <span class="nb-d-vector-cap nb-d-vector-cap-r"><?php echo esc_html( $v['right'] ); ?></span>
      </div>
      <?php endforeach; ?>
    </section>

    <!-- 2. What matters most (clustered priorities) -->
    <section class="nb-d-section">
      <p class="nb-d-step">Step 2 of 5</p>
      <h2><?php echo esc_html( $sc['priorities'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['priorities'][1] ); ?></p>
      <?php
      $groups = nb_discovery_service_groups();
      foreach ( $groups as $gkey => $glabel ) : ?>
      <div class="nb-d-cluster">
        <h3 class="nb-d-cluster-label"><?php echo esc_html( $glabel ); ?></h3>
        <div class="nb-d-services">
          <?php foreach ( $instance['services'] as $s ) :
              if ( $s['group'] !== $gkey ) continue;
              $k = $s['key']; ?>
          <div class="nb-d-service" data-key="<?php echo esc_attr( $k ); ?>">
            <div class="nb-d-service-head">
              <span class="nb-d-service-label"><?php echo esc_html( $s['label'] ); ?></span>
              <span class="nb-d-service-hint"><?php echo esc_html( $s['hint'] ); ?></span>
            </div>
            <?php nb_discovery_seg(
                array( 'nb-d-importance' ),
                array( '0' => 'Not a priority', '3' => 'Nice to have', '7' => 'Important', '10' => 'Critical' ),
                $s['label'] . ' — importance',
                null,
                $k
            ); ?>
            <div class="nb-d-handling" hidden>
              <p class="nb-d-handling-q">How well is this handled today?</p>
              <?php nb_discovery_seg(
                  array( 'nb-d-handling-seg' ),
                  array( '2' => 'Poorly', '5' => 'OK', '7' => 'Well', '10' => 'Very well' ),
                  $s['label'] . ' — handled today'
              ); ?>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </section>

    <!-- 3. What's in place today (systems + leads baseline) -->
    <section class="nb-d-section">
      <p class="nb-d-step">Step 3 of 5</p>
      <h2><?php echo esc_html( $sc['systems'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['systems'][1] ); ?></p>
      <label class="nb-d-field"><span>Do you use a CRM today? If so, which one?</span><input type="text" name="crm"></label>
      <label class="nb-d-field"><span>When a web lead comes in today, what happens?</span><textarea name="lead_handling" rows="3"></textarea></label>
      <label class="nb-d-field"><span>Roughly how many web leads a month right now?</span><input type="text" name="leads_per_month"><span class="nb-d-field-hint">Ballpark is fine — and if you’re not tracking this yet, just say so.</span></label>
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

    <!-- 4. Direction & timing -->
    <section class="nb-d-section">
      <p class="nb-d-step">Step 4 of 5</p>
      <h2><?php echo esc_html( $sc['direction'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['direction'][1] ); ?></p>
      <div class="nb-d-vector-row">
        <span class="nb-d-vector-cap">Fix what’s urgent</span>
        <?php nb_discovery_seg(
            array( 'nb-d-vector' ),
            array( '-50' => 'Strongly', '-25' => 'Lean', '0' => 'No pref', '25' => 'Lean', '50' => 'Strongly' ),
            'Fix what is urgent versus invest for long-term growth',
            '0',
            'fix_invest'
        ); ?>
        <span class="nb-d-vector-cap nb-d-vector-cap-r">Invest for long-term growth</span>
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

    <!-- 5. Anything else -->
    <section class="nb-d-section">
      <p class="nb-d-step">Step 5 of 5</p>
      <h2><?php echo esc_html( $sc['open'][0] ); ?><span class="nb-d-dot">.</span></h2>
      <p class="nb-d-sub"><?php echo esc_html( $sc['open'][1] ); ?></p>
      <label class="nb-d-field"><span>Anything we haven’t asked?</span><textarea name="open" rows="4"></textarea></label>
      <label class="nb-d-field"><span>Your name</span><input type="text" name="respondent_name"></label>
      <label class="nb-d-field"><span>Your email</span><input type="email" name="respondent_email"></label>
    </section>
```

Leave the `<div class="nb-d-actions">…</div>`, the `#nb-d-thankyou` block, and the two `<script>` tags below the form exactly as they are.

> Note: the `data-key` for importance and vectors now lives on a sibling `<input type="hidden" class="nb-d-importance-key|nb-d-vector-key">`, because `nb_discovery_seg()` renders a generic group. Task 4's JS reads the key from that sibling.

- [ ] **Step 4: Lint and check block balance**

Run: `php -l wp-content/themes/newblood/inc/discovery/view.php`
Expected: `No syntax errors detected`
Run: `grep -c 'nb_discovery_seg(' wp-content/themes/newblood/inc/discovery/view.php`
Expected: `5` — one function definition + four call sites (importance, handling, goal-vector, direction-vector).
Also confirm the `<!-- wp:` block comments elsewhere in the theme are untouched; this standalone page uses no block comments, so no balance check is needed here.

- [ ] **Step 5: Live render check (no fatal, markup present)**

Run: `curl -s http://newblood.test/discovery/overhead-door | grep -c 'nb-d-seg-btn'`
Expected: a number well above 0 (12 services × 4 + handling groups hidden-but-rendered + 6 vectors × 5 ≈ 100+). A `0` or a PHP fatal in the output means the markup or helper is broken.
Run: `curl -s http://newblood.test/discovery/overhead-door | grep -c 'nb-d-cluster-label'`
Expected: `4`

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/newblood/inc/discovery/view.php
git commit -m "feat(discovery): segmented controls, vision-first order, clusters, baseline, progress"
```

---

### Task 4: JS — read segmented selections, reveal logic, progress bar

**Files:**
- Modify (full rewrite): `wp-content/themes/newblood/assets/js/discovery.js`

**Interfaces:**
- Consumes the DOM contract from Task 3 (`.nb-d-seg`, `.nb-d-seg-btn[data-val]`, `.is-selected`, `.nb-d-importance-key`, `.nb-d-vector-key`, `.nb-d-handling`, `.nb-d-progress-fill`, `input[name="leads_per_month"]`).
- Produces the same JSON payload shape `submission.php` already expects, with `systems.leads_per_month` added and untouched services omitted.

Verified by `node --check` (syntax) + manual browser QA — no JS unit harness in this repo.

- [ ] **Step 1: Replace discovery.js entirely**

Overwrite `wp-content/themes/newblood/assets/js/discovery.js` with:

```javascript
(function () {
  'use strict';
  var cfg = window.nbDiscovery || {};
  var threshold = typeof cfg.threshold === 'number' ? cfg.threshold : 7;
  var form = document.getElementById('nb-discovery-form');
  if (!form) return;

  // --- Segmented buttons: single-select within each .nb-d-seg group ---
  function selectedVal(group) {
    if (!group) return null;
    var btn = group.querySelector('.nb-d-seg-btn.is-selected');
    return btn ? parseInt(btn.getAttribute('data-val'), 10) : null;
  }
  function bindSeg(group, onChange) {
    Array.prototype.forEach.call(group.querySelectorAll('.nb-d-seg-btn'), function (btn) {
      btn.addEventListener('click', function () {
        Array.prototype.forEach.call(group.querySelectorAll('.nb-d-seg-btn'), function (b) {
          b.classList.remove('is-selected');
          b.setAttribute('aria-checked', 'false');
        });
        btn.classList.add('is-selected');
        btn.setAttribute('aria-checked', 'true');
        if (onChange) onChange(selectedVal(group));
      });
    });
  }

  // Importance groups reveal/hide the sibling "handled today?" group.
  Array.prototype.forEach.call(document.querySelectorAll('.nb-d-service'), function (row) {
    var impGroup = row.querySelector('.nb-d-importance');
    var handling = row.querySelector('.nb-d-handling');
    var handGroup = row.querySelector('.nb-d-handling-seg');
    bindSeg(impGroup, function (val) {
      if (val !== null && val >= threshold) {
        handling.hidden = false;
      } else {
        handling.hidden = true;
        // clear any handling selection when it no longer applies
        Array.prototype.forEach.call(handGroup.querySelectorAll('.nb-d-seg-btn'), function (b) {
          b.classList.remove('is-selected');
          b.setAttribute('aria-checked', 'false');
        });
      }
    });
    bindSeg(handGroup, null);
  });

  // Vector groups (goals + fix_invest) — default "No pref" (0) already selected in markup.
  Array.prototype.forEach.call(document.querySelectorAll('.nb-d-vector'), function (g) { bindSeg(g, null); });

  // --- Scroll progress bar ---
  var fill = document.querySelector('.nb-d-progress-fill');
  if (fill) {
    var onScroll = function () {
      var doc = document.documentElement;
      var max = doc.scrollHeight - doc.clientHeight;
      var pct = max > 0 ? Math.min(100, Math.max(0, (doc.scrollTop || window.pageYOffset) / max * 100)) : 0;
      fill.style.width = pct + '%';
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onScroll);
    onScroll();
  }

  function collect() {
    var get = function (name) { var el = form.querySelector('[name="' + name + '"]'); return el ? el.value.trim() : ''; };

    var services = [];
    Array.prototype.forEach.call(document.querySelectorAll('.nb-d-service'), function (row) {
      var impGroup = row.querySelector('.nb-d-importance');
      var key = impGroup ? impGroup.getAttribute('data-key') : row.getAttribute('data-key');
      var imp = selectedVal(impGroup);
      if (imp === null) return; // untouched → omit (treated as not-rated)
      var obj = { key: key, importance: imp };
      if (imp >= threshold) {
        obj.handling = selectedVal(row.querySelector('.nb-d-handling-seg'));
      } else {
        obj.handling = null;
      }
      services.push(obj);
    });

    var vectors = {};
    Array.prototype.forEach.call(document.querySelectorAll('.nb-d-vector'), function (g) {
      var vkey = g.getAttribute('data-key');
      if (!vkey) return;
      vectors[vkey] = selectedVal(g) || 0;
    });

    var gbp = form.querySelector('input[name="gbp_access"]:checked');
    return {
      instance: cfg.instance || form.getAttribute('data-instance'),
      hp: get('hp_company'),
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
        leads_per_month: get('leads_per_month'),
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
      showError('Something went wrong sending your answers. Please try again, or reply to Jeremy\'s email.');
    });
  });
})();
```

- [ ] **Step 2: Syntax check**

Run: `node --check wp-content/themes/newblood/assets/js/discovery.js`
Expected: no output (exit 0).

- [ ] **Step 3: Commit**

```bash
git add wp-content/themes/newblood/assets/js/discovery.js
git commit -m "feat(discovery): read segmented selections + drive scroll progress"
```

---

### Task 5: CSS — segmented component, progress bar, cluster + skip styles

**Files:**
- Modify: `wp-content/themes/newblood/assets/css/discovery.css`

**Interfaces:** styles the classes Tasks 3–4 introduced. Verified visually (manual QA in Task 6).

- [ ] **Step 1: Read the current slider-related CSS to know what to retire**

Run: `grep -n 'nb-d-slider\|nb-d-importance\|nb-d-handling\|nb-d-vector\|type="range"\|input\[type=range\]\|nb-d-section\|:root' wp-content/themes/newblood/assets/css/discovery.css`
Expected: a list of line ranges. Note the slider rule blocks (`.nb-d-slider-row`, `.nb-d-importance` as a range input, `.nb-d-vector` as a range input, any `input[type="range"]` styling) — these are superseded.

- [ ] **Step 2: Append the segmented + progress + cluster styles**

Append to `wp-content/themes/newblood/assets/css/discovery.css` (uses the file's existing CSS custom properties — confirm variable names against `:root` from Step 1 and adjust if they differ, e.g. `--nb-accent`, `--nb-border`, `--nb-text-dim`):

```css
/* --- Segmented button groups (replaces sliders) --- */
.nb-d-seg { display: flex; flex-wrap: wrap; gap: .4rem; margin: .2rem 0 .4rem; }
.nb-d-seg-btn {
  flex: 1 1 auto; min-width: 0; min-height: 44px; /* thumb-friendly */
  padding: .55rem .7rem; font: inherit; font-size: .9rem; line-height: 1.15;
  color: var(--nb-text, #e8e8ea); background: rgba(255,255,255,.04);
  border: 1px solid var(--nb-border, rgba(255,255,255,.16)); border-radius: 10px;
  cursor: pointer; transition: background .12s ease, border-color .12s ease, color .12s ease;
  -webkit-tap-highlight-color: transparent;
}
.nb-d-seg-btn:hover { border-color: var(--nb-accent, #4ade80); }
.nb-d-seg-btn.is-selected {
  background: var(--nb-accent, #4ade80); border-color: var(--nb-accent, #4ade80);
  color: #06210f; font-weight: 600;
}
.nb-d-seg-btn:focus-visible { outline: 2px solid var(--nb-accent, #4ade80); outline-offset: 2px; }

/* Vector rows: caps flank a 5-point segmented group */
.nb-d-vector-row { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: .6rem; margin: .5rem 0; }
.nb-d-vector-row .nb-d-seg { min-width: 240px; }
.nb-d-vector-cap { font-size: .85rem; color: var(--nb-text-dim, #a8a8ad); }
.nb-d-vector-cap-r { text-align: right; }

/* Handling sub-question */
.nb-d-handling { margin-top: .5rem; padding-left: .8rem; border-left: 2px solid var(--nb-border, rgba(255,255,255,.16)); }
.nb-d-handling-q { font-size: .85rem; color: var(--nb-text-dim, #a8a8ad); margin: 0 0 .3rem; }

/* Capability clusters */
.nb-d-cluster { margin: 1.4rem 0; }
.nb-d-cluster-label {
  font-size: .8rem; text-transform: uppercase; letter-spacing: .08em;
  color: var(--nb-accent, #4ade80); margin: 0 0 .4rem;
}

/* Skip line + field hint + step eyebrow */
.nb-d-skip { font-size: .9rem; color: var(--nb-text-dim, #a8a8ad); margin-top: .6rem; }
.nb-d-field-hint { display: block; font-size: .8rem; color: var(--nb-text-dim, #a8a8ad); margin-top: .25rem; }
.nb-d-step { font-size: .75rem; text-transform: uppercase; letter-spacing: .1em; color: var(--nb-text-dim, #a8a8ad); margin: 0 0 .3rem; }

/* Sticky scroll progress bar */
.nb-d-progress {
  position: sticky; top: 0; z-index: 10; height: 3px;
  background: var(--nb-border, rgba(255,255,255,.16)); margin: 0 0 1.5rem;
}
.nb-d-progress-fill { display: block; height: 100%; width: 0; background: var(--nb-accent, #4ade80); transition: width .1s linear; }

/* Mobile: stack vector caps above/below the segmented group */
@media (max-width: 560px) {
  .nb-d-vector-row { grid-template-columns: 1fr; gap: .25rem; }
  .nb-d-vector-cap-r { text-align: left; }
  .nb-d-vector-row .nb-d-seg { min-width: 0; }
}
```

- [ ] **Step 3: Remove the now-dead slider rules**

Using the line ranges from Step 1, delete the slider-only blocks (`.nb-d-slider-row`, `.nb-d-slider-cap`, `.nb-d-importance-out`, `.nb-d-handling-out`, and any `input[type="range"]` / range-thumb styling). Do **not** delete `.nb-d-service`, `.nb-d-service-head`, `.nb-d-field`, `.nb-d-section`, or `.nb-d-radios` — those are still used. If unsure whether a rule is still referenced, grep the class against `view.php`:
Run: `grep -o 'nb-d-[a-z-]*' wp-content/themes/newblood/inc/discovery/view.php | sort -u`
Keep any CSS rule whose class appears in that list; remove slider rules whose classes do not.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/newblood/assets/css/discovery.css
git commit -m "style(discovery): segmented controls, progress bar, cluster + skip styling"
```

---

### Task 6: Full-suite + end-to-end verification (desktop + phone)

**Files:** none (verification only).

- [ ] **Step 1: Run the entire PHP test suite**

Run: `for f in wp-content/themes/newblood/tests/discovery/test-*.php; do php -d zend.assertions=1 -d assert.exception=1 "$f"; done`
Expected: five `PASS` lines, no `AssertionError`.

- [ ] **Step 2: Lint every touched PHP file**

Run: `for f in config submission aggregate email report view; do php -l wp-content/themes/newblood/inc/discovery/$f.php; done`
Expected: `No syntax errors detected` ×6.

- [ ] **Step 3: Desktop browser pass at `http://newblood.test/discovery/overhead-door`**

Confirm by observation:
- Section order is Vision → Priorities → Systems → Direction → Anything else, eyebrows read "Step 1–5 of 5".
- Priorities show four cluster subheads (Get found / Convert / Operate / Grow).
- Tapping "Important" or "Critical" on a capability reveals "How well is this handled today?"; tapping "Nice to have"/"Not a priority" hides it and clears any handling pick.
- Goal-vector and fix/invest rows default to "No pref"; selecting a side highlights it.
- The progress bar fills as you scroll.
- The "Roughly how many web leads a month right now?" field with its skip-friendly hint is present in the Systems section.

- [ ] **Step 4: Phone pass (real device or DevTools device emulation, ≤560px)**

Confirm segmented buttons are comfortably tappable (44px min height), vector caps stack above/below the group, and no horizontal scroll. This was the top pre-send risk.

- [ ] **Step 5: End-to-end submission**

Fill the form with a couple of priorities (one Critical + handling), a vision sentence, a few vectors, the leads field ("not tracking yet"), and name/email. Submit; confirm the thank-you panel appears. Then as an admin load `http://newblood.test/discovery/overhead-door/report` and confirm the submission appears with the gap map, the strategic-direction vectors, and "Web leads / month" under "In their words". Confirm the admin email arrived (or `wp_mail` was invoked without error in the log).

- [ ] **Step 6: Final commit (if any QA fixes were needed)**

```bash
git add -A wp-content/themes/newblood/
git commit -m "test(discovery): verify intake refinement end-to-end"
```

---

## Notes for the implementer

- **Do not change** `NB_DISCOVERY_THRESHOLD`, the `0–10`/`−50…50` clamps, or the payload shape. The whole point of the value mapping is that the storage/aggregate/report/email layers don't change.
- The `nb_discovery_seg()` helper centralizes button markup — if you find yourself hand-writing `<button class="nb-d-seg-btn">`, use the helper instead.
- `filemtime()` cache-busting means CSS/JS edits show up on reload with no version bump.
- If `sanitize_html_class` is unavailable in the standalone-page context, the class list passed to `nb_discovery_seg()` is developer-controlled (never user input), so a plain `implode(' ', $classes)` is acceptable — but prefer the WP helper since the page runs inside WordPress.
