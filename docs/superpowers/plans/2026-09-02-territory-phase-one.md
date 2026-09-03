# Territory Repositioning, Phase One: Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Remove the four price-modesty lines and the "1-2 weeks" promise from newblood.com, add a `/territory` flagship page, rewrite `/pricing` as two doors, and add a two-door section directly below the homepage hero.

**Architecture:** Everything is WordPress block-pattern PHP under `wp-content/themes/newblood/patterns/`, plus small additions to `assets/css/patterns.css`, one template edit, and two database records (the Territory page and its nav item) created with WP-CLI. There is no test suite; every task verifies with `php -l`, a block-comment balance check, and `curl` assertions against `http://newblood.test`, and each task ends in a commit.

**Tech Stack:** WordPress block theme (`newblood`), PHP block patterns, plain CSS, WP-CLI, Laravel Herd local server, `deploy.sh` (rsync over SSH) for production.

**Spec:** `docs/superpowers/specs/2026-09-02-territory-phase-one-design.md`. Read it first; the copy in this plan is copied from it verbatim.

## Global Constraints

- WP-CLI must always be invoked as `php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood <subcommand>`. Define `W="php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood"` in each shell and use `$W`. It prints a harmless PHP deprecation notice on stderr; append `2>/dev/null` when capturing output.
- Run `php -l <file>` after every pattern edit. Block-comment pairs must balance. Balance check for a file `f`: `o=$(grep -o '<!-- wp:' f | wc -l); s=$(grep -o '/-->' f | wc -l); c=$(grep -o '<!-- /wp:' f | wc -l); echo "$((o-s)) $c"` must print two equal numbers.
- New files under `patterns/` do not register until the pattern transients are deleted: `$W db query "DELETE FROM wp_options WHERE option_name LIKE '_site_transient_wp_theme_files_patterns%' OR option_name LIKE '_site_transient_timeout_wp_theme_files_patterns%'"`. Editing an existing pattern file needs no such step.
- No em dashes (—) in any new copy. Existing lines stay untouched unless the spec rewrites them.
- No client names or client figures on any new surface. `grep -n -i -E 'overhead|ohdbalt|baltimore|1947|3,000|nine years' <new files>` must return nothing.
- No musical-voice phrasing on the new surfaces (pricing, territory, two-doors). The word "tune" may appear only in the existing Tune product names.
- Never change an existing pattern slug. `statement.php` stays on disk.
- Image URLs, if any, root-relative. None are planned.
- CSS and JS are cache-busted by `filemtime()`; never add `?ver=` strings.
- Commits go on the current branch `feature/redesign`. Commit message trailer (exactly):
  ```
  Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
  Claude-Session: https://claude.ai/code/session_01VshhFDSHXK2LDLG1kbBVVU
  ```
- Production deploy changes shared state. Task 7 runs `./deploy.sh --dry-run` and then stops for Jeremy's confirmation before the real run.

---

## File map

| File | Responsibility | Task |
|---|---|---|
| `wp-content/themes/newblood/patterns/hero.php` | line 18 subtitle rewrite | 1 |
| `wp-content/themes/newblood/patterns/about-story.php` | lines 25, 31, 41 rewrites | 1 |
| `wp-content/themes/newblood/patterns/faq.php` | line 19 rewrite | 1 |
| `wp-content/themes/newblood/patterns/two-doors.php` | **new** homepage two-door section | 2 |
| `wp-content/themes/newblood/templates/front-page.html` | swap `statement` slot for `two-doors` | 2 |
| `wp-content/themes/newblood/assets/css/patterns.css` | `.nb-two-doors`, `.nb-pricing` door-one width, `.nb-territory-*` | 2, 3, 4 |
| `wp-content/themes/newblood/patterns/pricing-table.php` | full rewrite as two doors | 3 |
| `wp-content/themes/newblood/patterns/territory.php` | **new** flagship page body | 4 |
| WordPress DB: page `territory`, nav post 6737 | page record + nav item (local, then prod) | 5, 7 |
| `CLAUDE.md`, `TASKS.md`, `~/Herd/_hub/PROJECTS.md` | lineup note + hub protocol | 7 |

---

### Task 1: Rewrite the five contradicting lines

**Files:**
- Modify: `wp-content/themes/newblood/patterns/hero.php:18`
- Modify: `wp-content/themes/newblood/patterns/about-story.php:25,31,41`
- Modify: `wp-content/themes/newblood/patterns/faq.php:19`

**Interfaces:**
- Consumes: nothing.
- Produces: nothing other tasks depend on.

- [ ] **Step 1: Confirm the current lines are where the spec says**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood
sed -n '18p' patterns/hero.php | grep -c 'budget should allow'
sed -n '25p' patterns/about-story.php | grep -c 'Right-sized budgets'
sed -n '31p' patterns/about-story.php | grep -c 'much bigger budget'
sed -n '41p' patterns/about-story.php | grep -c 'small-business budget'
sed -n '19p' patterns/faq.php | grep -c '1-2 weeks'
```
Expected: five lines each printing `1`. If any prints `0`, locate the line with `grep -n` and use that line number below.

- [ ] **Step 2: Replace hero.php line 18**

Replace the whole line with:
```html
  <p class="nb-hero-body has-text-secondary-color">We pair decades of hands-on craft with modern AI workflows to build sites that are measured in every sense: more considered, more technically ambitious, and more memorable than the brief called for.</p>
```

- [ ] **Step 3: Replace about-story.php line 25**

```html
  <h3>Fortune 500 craft. At the scale of your business.</h3>
```

- [ ] **Step 4: Replace about-story.php line 31**

```html
  <p class="has-text-body-color">What's changed is the reach. By pairing decades of hands-on craft with modern AI workflows, we take on creative, technically ambitious projects that used to require a much bigger team. The strategic thinking and creative polish that high-end agencies reserve for their largest accounts now goes into every project we take on, whatever its size.</p>
```

- [ ] **Step 5: Replace about-story.php line 41**

```html
  <p class="has-text-body-color">That kind of care used to be reserved for the largest accounts. Twenty-five years of engineering, paired with modern AI workflows, is what lets us bring it to every project, from a five-page site to a platform that runs a company's front door. Interactive 3D, generative visuals, physics-driven animation, custom integrations: tools we reach for whenever a project calls for them, not features you're upsold.</p>
```

- [ ] **Step 6: Replace faq.php line 19**

```html
  <p class="has-text-body-color">It depends on the shape of the project. A focused site takes a few weeks from discovery to launch. A platform build runs about ninety days: foundations first, then build and prove, then launch and watch. You get a timeline at the end of discovery, and we don't rush the parts that decide whether the site works.</p>
```

- [ ] **Step 7: Lint and verify the old strings are gone and the new ones render**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood
php -l patterns/hero.php && php -l patterns/about-story.php && php -l patterns/faq.php
grep -c -E 'budget should allow|Right-sized budgets|much bigger budget|small-business budget|1-2 weeks' patterns/hero.php patterns/about-story.php patterns/faq.php
curl -s http://newblood.test/ | grep -c 'than the brief called for'
curl -s http://newblood.test/about/ | grep -c -E 'At the scale of your business|whatever its size|runs a company.s front door'
curl -s http://newblood.test/pricing/ | grep -c 'about ninety days'
```
Expected: three `No syntax errors`; the grep -c line prints `0` for each of the three files; the three curl lines print `1`, `3`, `1`.

- [ ] **Step 8: Commit**

```bash
cd /Users/jeremyoms/Herd/newblood
git add wp-content/themes/newblood/patterns/hero.php wp-content/themes/newblood/patterns/about-story.php wp-content/themes/newblood/patterns/faq.php
git commit -m "copy: remove price-modesty lines and the 1-2 weeks promise

Hero subtitle, three About lines, and the FAQ timeline answer now match
the deliberative brand rule and the platform-scale work. Spec step 1 of
docs/superpowers/specs/2026-09-02-territory-phase-one-design.md.

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01VshhFDSHXK2LDLG1kbBVVU"
```

---

### Task 2: Homepage two-door section

**Files:**
- Create: `wp-content/themes/newblood/patterns/two-doors.php`
- Modify: `wp-content/themes/newblood/templates/front-page.html` (line 5)
- Modify: `wp-content/themes/newblood/assets/css/patterns.css` (append)

**Interfaces:**
- Consumes: existing CSS classes `nb-glass`, `nb-reveal`, `nb-stagger`, `nb-label`, `nb-btn-primary`, `nb-btn-secondary`.
- Produces: pattern slug `newblood/two-doors`; CSS scope `.nb-two-doors` with `.nb-door`, `.nb-door-title`, `.nb-door-links`, `.nb-door-textlink`. Card two links to `/territory/`, which Task 5 creates.

- [ ] **Step 1: Create the pattern file**

Write `wp-content/themes/newblood/patterns/two-doors.php` with exactly:

```php
<?php
/**
 * Title: Two Doors
 * Slug: newblood/two-doors
 * Categories: newblood
 * Description: Homepage section directly below the hero. Two ways in: a site that represents you, or a platform that wins your territory.
 */
?>
<!-- wp:group {"className":"nb-two-doors","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group nb-two-doors">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">Two ways in</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"className":"nb-glass nb-reveal nb-door"} -->
    <div class="wp-block-column nb-glass nb-reveal nb-door" style="padding:2.5rem">
      <!-- wp:heading {"level":2,"className":"nb-door-title"} -->
      <h2 class="nb-door-title">A site that represents you<span style="color:#4ade80">.</span></h2>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"1rem","lineHeight":"1.7"}}} -->
      <p class="has-text-muted-color">Custom design and clean code for businesses that want a website made for them, not chosen from a template. Build, Tune, Manage, Empower, with pricing you can read before you call.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"className":"nb-door-links"} -->
      <p class="nb-door-links"><a class="nb-btn-secondary" href="/pricing/">See pricing</a><a class="nb-door-textlink" href="/work/">See our work</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal nb-door nb-door--territory","style":{"border":{"color":"rgba(74,222,128,0.3)","width":"1px"}}} -->
    <div class="wp-block-column nb-glass nb-reveal nb-door nb-door--territory" style="padding:2.5rem;border-color:rgba(74,222,128,0.3)">
      <!-- wp:heading {"level":2,"className":"nb-door-title"} -->
      <h2 class="nb-door-title">A platform that wins your territory<span style="color:#4ade80">.</span></h2>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"1rem","lineHeight":"1.7"}}} -->
      <p class="has-text-muted-color">For companies that built their name over decades, and refuse to rent it back one click at a time. A website, a content program, and a measurement system, with every account in your name.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"className":"nb-door-links"} -->
      <p class="nb-door-links"><a class="nb-btn-primary" href="/territory/">The Territory Platform</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 2: Lint and balance-check the new pattern**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood
php -l patterns/two-doors.php
f=patterns/two-doors.php; o=$(grep -o '<!-- wp:' $f | wc -l); s=$(grep -o '/-->' $f | wc -l); c=$(grep -o '<!-- /wp:' $f | wc -l); echo "$((o-s)) $c"
grep -c '—' patterns/two-doors.php
```
Expected: `No syntax errors`; two equal numbers (`10 10`); `0`.

- [ ] **Step 3: Append the CSS**

Append to the end of `wp-content/themes/newblood/assets/css/patterns.css`:

```css

/* ===== Homepage Two Doors (below the hero) ===== */
.nb-two-doors .wp-block-columns.nb-stagger {
  gap: 2.5rem;
  align-items: stretch;
}

.nb-two-doors .nb-door {
  display: flex;
  flex-direction: column;
}

.nb-two-doors .nb-door-title {
  font-size: clamp(1.5rem, 3vw, 2rem);
  line-height: 1.2;
  letter-spacing: -0.01em;
  margin-bottom: 1rem;
}

.nb-two-doors .nb-door-links {
  margin-top: auto;
  padding-top: 1.5rem;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem 1.5rem;
}

.nb-two-doors .nb-door-textlink {
  color: #4ade80;
  font-weight: 600;
  text-decoration: none;
}

.nb-two-doors .nb-door-textlink:hover,
.nb-two-doors .nb-door-textlink:focus-visible {
  text-decoration: underline;
}
```

- [ ] **Step 4: Swap the template slot**

In `wp-content/themes/newblood/templates/front-page.html`, replace the line
```html
  <!-- wp:pattern {"slug":"newblood/statement"} /-->
```
with
```html
  <!-- wp:pattern {"slug":"newblood/two-doors"} /-->
```
Do not delete `patterns/statement.php`.

- [ ] **Step 5: Register the new pattern and verify the homepage renders it**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood
W="php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood"
$W db query "DELETE FROM wp_options WHERE option_name LIKE '_site_transient_wp_theme_files_patterns%' OR option_name LIKE '_site_transient_timeout_wp_theme_files_patterns%'" 2>/dev/null
curl -s http://newblood.test/ > /tmp/nb-home.html
grep -c 'nb-two-doors' /tmp/nb-home.html
grep -c 'A site that represents you' /tmp/nb-home.html
grep -c 'A platform that wins your territory' /tmp/nb-home.html
grep -c 'nb-statement' /tmp/nb-home.html
grep -o 'href="/territory/"' /tmp/nb-home.html | wc -l
```
Expected: `1` or more, `1`, `1`, `0`, `1`. (If the first is `0`, the transient delete did not run; rerun it and curl again.)

Note: `/tmp` here is fine for a throwaway curl target; use the session scratchpad directory if one is set.

- [ ] **Step 6: Commit**

```bash
cd /Users/jeremyoms/Herd/newblood
git add wp-content/themes/newblood/patterns/two-doors.php wp-content/themes/newblood/templates/front-page.html wp-content/themes/newblood/assets/css/patterns.css
git commit -m "home: two-door section below the hero replaces the statement slot

Adds newblood/two-doors (a site that represents you / a platform that
wins your territory). statement.php stays on disk. Spec step 4.

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01VshhFDSHXK2LDLG1kbBVVU"
```

---

### Task 3: Pricing page as two doors

**Files:**
- Modify: `wp-content/themes/newblood/patterns/pricing-table.php` (full rewrite)
- Modify: `wp-content/themes/newblood/assets/css/patterns.css` (append)

**Interfaces:**
- Consumes: existing `.nb-pricing` rules (patterns.css lines 154-172), `.nb-tune-pricing` width rule.
- Produces: new columns class `nb-door-one-pricing` (2-card row, capped width) and `nb-door-two-pricing` (3-card row at the existing 1500px). Territory card links to `/territory/` (Task 5).

- [ ] **Step 1: Rewrite the pattern file**

Replace the entire contents of `wp-content/themes/newblood/patterns/pricing-table.php` with:

```php
<?php
/**
 * Title: Pricing Table
 * Slug: newblood/pricing-table
 * Categories: newblood
 * Description: Two doors: build tiers + Tune for a site that represents you; audit, Territory Platform and Operations Platform for a platform that wins your territory
 */
?>
<!-- wp:group {"align":"full","className":"nb-gradient-section nb-pricing","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1500px"}} -->
<div class="wp-block-group alignfull nb-gradient-section nb-pricing">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"textColor":"text-secondary"} -->
    <p class="has-text-secondary-color">Every plan includes hosting on our managed infrastructure.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->

  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">Door one</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>A site that represents you<span style="color:#4ade80">.</span></h2>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"textColor":"text-secondary"} -->
    <p class="has-text-secondary-color">Custom design and clean code for businesses that want a website made for them.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->

  <!-- wp:columns {"className":"nb-stagger nb-door-one-pricing","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger nb-door-one-pricing">
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
      <h3>Starter</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Perfect for small businesses getting started online.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
      <p><span style="color:#4ade80">$3,500</span></p>
      <!-- /wp:paragraph -->
      <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
      <ul class="has-text-body-color">
        <li>Up to 5 pages</li>
        <li>Mobile responsive design</li>
        <li>Content management training</li>
        <li>2 rounds of revisions</li>
      </ul>
      <!-- /wp:list -->
      <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
      <p><a class="nb-btn-primary" href="/contact" style="display:block;text-align:center">Get Started</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal","style":{"border":{"color":"rgba(74,222,128,0.3)","width":"1px"}}} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem;border-color:rgba(74,222,128,0.3)">
      <!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between"}} -->
      <div class="wp-block-group">
        <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
        <h3>Business</h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.625rem","textTransform":"uppercase","letterSpacing":"1px","fontWeight":"700"},"color":{"background":"rgba(34,197,94,0.15)","text":"#4ade80"},"spacing":{"padding":{"top":"0.25rem","bottom":"0.25rem","left":"0.5rem","right":"0.5rem"}},"border":{"radius":"4px"}}} -->
        <p>Popular</p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">For businesses that need more functionality and a custom look.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
      <p><span style="color:#4ade80">$5,000</span></p>
      <!-- /wp:paragraph -->
      <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
      <ul class="has-text-body-color">
        <li>Up to 10 pages</li>
        <li>Custom design + animations</li>
        <li>E-commerce ready</li>
        <li>SEO optimization</li>
        <li>3 rounds of revisions</li>
      </ul>
      <!-- /wp:list -->
      <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
      <p><a class="nb-btn-primary" href="/contact" style="display:block;text-align:center">Get Started</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->

  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">Already have a site?</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>Tune it instead<span style="color:#4ade80">.</span></h2>
    <!-- /wp:heading -->
  </div>
  <!-- /wp:group -->

  <!-- wp:columns {"className":"nb-stagger nb-tune-pricing","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger nb-tune-pricing">
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
      <h3>Tune</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">A fixed-price 5-7 hour engagement that brings your existing WordPress site up to speed.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
      <p><span style="color:#4ade80">$2,000</span></p>
      <!-- /wp:paragraph -->
      <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
      <ul class="has-text-body-color">
        <li>Diagnostic SEO + PageSpeed audit</li>
        <li>Performance + SEO fixes across 5 phases</li>
        <li>Per-phase before/after screenshots</li>
        <li>Handover doc + extensible dequeue file</li>
      </ul>
      <!-- /wp:list -->
      <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
      <p><a class="nb-btn-primary" href="/contact" style="display:block;text-align:center">Get in touch</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
      <h3>Tune Plus</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Adds critical-CSS extraction for clients targeting Mobile 90+. Stretch engagement.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
      <p><span style="color:#4ade80">$4,500</span></p>
      <!-- /wp:paragraph -->
      <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
      <ul class="has-text-body-color">
        <li>Everything in Tune</li>
        <li>Above-the-fold critical CSS extraction</li>
        <li>Per-archetype critical-CSS templates</li>
        <li>Visual-regression sign-off included</li>
      </ul>
      <!-- /wp:list -->
      <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
      <p><a class="nb-btn-primary" href="/contact" style="display:block;text-align:center">Get in touch</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->

  <!-- wp:separator {"opacity":"css","style":{"color":{"background":"rgba(255,255,255,0.06)"},"spacing":{"margin":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60"}}}} -->
  <hr class="wp-block-separator has-css-opacity has-background"/>
  <!-- /wp:separator -->

  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"760px"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">Door two</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>A platform that wins your territory<span style="color:#4ade80">.</span></h2>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"textColor":"text-secondary"} -->
    <p class="has-text-secondary-color">For established operators: trades, distributors, commercial service companies. Read your own numbers, then build the platform that takes the searches back.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->

  <!-- wp:columns {"className":"nb-stagger nb-door-two-pricing","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger nb-door-two-pricing">
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
      <h3>Four Gaps Audit</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">For an established operator who wants to see the gaps before committing to anything.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
      <p><span style="color:#4ade80">$1,500</span><span class="nb-price-note"> to $2,500</span></p>
      <!-- /wp:paragraph -->
      <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
      <ul class="has-text-body-color">
        <li>We read your ad platform, inquiry database, and search territory</li>
        <li>The four gaps, shown on your own numbers</li>
        <li>Where you appear in search and AI answers today, and who appears instead</li>
        <li>A written plan you keep, whoever builds it</li>
        <li>Credits in full toward the Territory build</li>
      </ul>
      <!-- /wp:list -->
      <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
      <p><a class="nb-btn-primary" href="/contact" style="display:block;text-align:center">Ask for the audit</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal","style":{"border":{"color":"rgba(74,222,128,0.3)","width":"1px"}}} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem;border-color:rgba(74,222,128,0.3)">
      <!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between"}} -->
      <div class="wp-block-group">
        <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
        <h3>Territory Platform</h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.625rem","textTransform":"uppercase","letterSpacing":"1px","fontWeight":"700"},"color":{"background":"rgba(34,197,94,0.15)","text":"#4ade80"},"spacing":{"padding":{"top":"0.25rem","bottom":"0.25rem","left":"0.5rem","right":"0.5rem"}},"border":{"radius":"4px"}}} -->
        <p>Flagship</p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">A website, a content program, and a measurement system, with everything in your name.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
      <p><span style="color:#4ade80">$35,000</span><span class="nb-price-note"> build, then $3,200 / mo</span></p>
      <!-- /wp:paragraph -->
      <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
      <ul class="has-text-body-color">
        <li>Platform build, live in ninety days</li>
        <li>Save-first forms, tracked numbers, call recording</li>
        <li>AI intake that types, never talks</li>
        <li>Reviews engine, resource desk, project highlights</li>
        <li>Monthly content program, separable line</li>
        <li>One operator per trade, per market</li>
      </ul>
      <!-- /wp:list -->
      <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
      <p><a class="nb-btn-primary" href="/territory/" style="display:block;text-align:center">See the platform</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
      <h3>Operations Platform</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">For multi-location operators who want the business, not just the front door, on one system.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
      <p><span style="color:#4ade80">Scoped per operator</span></p>
      <!-- /wp:paragraph -->
      <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
      <ul class="has-text-body-color">
        <li>Everything in Territory</li>
        <li>CRM backbone and revenue attribution</li>
        <li>AI agents for the work behind the phone</li>
        <li>Customer portal</li>
        <li>Multi-location build-out</li>
      </ul>
      <!-- /wp:list -->
      <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
      <p><a class="nb-btn-primary" href="/contact" style="display:block;text-align:center">Let's talk</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->

  <!-- wp:group {"className":"nb-reveal nb-credit-line","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"760px"}} -->
  <div class="wp-block-group nb-reveal nb-credit-line" style="text-align:center">
    <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.9375rem"}}} -->
    <p class="has-text-muted-color">No first dollar is spent twice. The audit fee credits in full toward the Territory build, and the Territory build credits in full toward Operations, within twelve months.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 2: Append the CSS**

Append to `wp-content/themes/newblood/assets/css/patterns.css`:

```css

/* ===== Pricing: two doors ===== */
/* Door one is two cards; cap it so they don't stretch across the 1500px row */
.nb-pricing .nb-door-one-pricing.wp-block-columns {
  max-width: 1000px !important;
  margin-left: auto;
  margin-right: auto;
}

/* Secondary price text ("to $2,500", "build, then $3,200 / mo") */
.nb-pricing .nb-price-note {
  display: block;
  font-size: 1rem;
  font-weight: 600;
  color: var(--wp--preset--color--text-secondary, #9ca3af);
  margin-top: 0.25rem;
}
```

- [ ] **Step 3: Lint, balance, and content checks**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood
php -l patterns/pricing-table.php
f=patterns/pricing-table.php; o=$(grep -o '<!-- wp:' $f | wc -l); s=$(grep -o '/-->' $f | wc -l); c=$(grep -o '<!-- /wp:' $f | wc -l); echo "$((o-s)) $c"
grep -c '—' patterns/pricing-table.php
grep -c -E '>Reach<|Let.s Talk<' patterns/pricing-table.php
grep -n -i -E 'overhead|ohdbalt|baltimore|1947|3,000|nine years' patterns/pricing-table.php | wc -l
```
Expected: `No syntax errors`; two equal numbers; `0`; `0`; `0`.

- [ ] **Step 4: Verify the page renders both doors**

Run:
```bash
curl -s http://newblood.test/pricing/ > /tmp/nb-pricing.html
grep -c 'Door one' /tmp/nb-pricing.html
grep -c 'Door two' /tmp/nb-pricing.html
grep -c '>Popular<' /tmp/nb-pricing.html
grep -c '>Flagship<' /tmp/nb-pricing.html
grep -c 'Scoped per operator' /tmp/nb-pricing.html
grep -c 'No first dollar is spent twice' /tmp/nb-pricing.html
grep -c '>Reach<' /tmp/nb-pricing.html
grep -c 'Tune it instead' /tmp/nb-pricing.html
```
Expected: `1 1 1 1 1 1 0 1`.

- [ ] **Step 5: Commit**

```bash
cd /Users/jeremyoms/Herd/newblood
git add wp-content/themes/newblood/patterns/pricing-table.php wp-content/themes/newblood/assets/css/patterns.css
git commit -m "pricing: two doors, Territory Platform featured, Reach retired

Door one keeps Starter/Business (Popular) and the Tune row. Door two adds
Four Gaps Audit, Territory Platform (Flagship) and Operations Platform
(unpriced until the shape doc exists), plus the credit line. Spec step 3.

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01VshhFDSHXK2LDLG1kbBVVU"
```

---

### Task 4: The Territory page body pattern

**Files:**
- Create: `wp-content/themes/newblood/patterns/territory.php`
- Modify: `wp-content/themes/newblood/assets/css/patterns.css` (append)

**Interfaces:**
- Consumes: `nb-glass`, `nb-reveal`, `nb-stagger`, `nb-label`, `nb-gradient-section`, `nb-btn-primary`, the accent number treatment from `how-it-works.php` (`textColor:"accent"` paragraph).
- Produces: pattern slug `newblood/territory` (Task 5 puts it in a page). CSS scope `.nb-territory-*`.

- [ ] **Step 1: Create the pattern file**

Write `wp-content/themes/newblood/patterns/territory.php` with exactly:

```php
<?php
/**
 * Title: Territory Platform
 * Slug: newblood/territory
 * Categories: newblood-pages
 * Description: Body of the /territory flagship page: opening, four gaps, what the platform includes, ninety days, commitments, exclusivity, pricing
 */
?>
<!-- wp:group {"className":"nb-territory-opening","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"760px"}} -->
<div class="wp-block-group nb-territory-opening">
  <!-- wp:group {"className":"nb-reveal","layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">The Territory Platform</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.75rem, 3.5vw, 2.5rem)","lineHeight":"1.15","letterSpacing":"-0.02em"}}} -->
    <h2>Own the searches your name already earned<span style="color:#4ade80">.</span></h2>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">This is for companies that built their reputation over decades: trades, distributors, commercial service operators. You are the name people ask for. And every year more of the searches for that name are bought by younger competitors and lead-gen middlemen who never earned it.</p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">The Territory Platform is a website, a content program, and a measurement system built to take those searches back and keep them. Everything it earns is yours: domains, numbers, accounts, data, content.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"nb-gradient-section nb-territory-gaps","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull nb-gradient-section nb-territory-gaps">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">What we usually find</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>Four gaps, read from your own numbers<span style="color:#4ade80">.</span></h2>
    <!-- /wp:heading -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>The website</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Built years ago, rarely touched, and not the reason anyone calls. It has to become the place a job starts.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>The inquiry</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Calls and forms that arrive, get handled, and leave no record you can learn from. Nothing is saved before it is sent.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>The territory</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">The searches in your trade and your counties, in the words your customers type. Someone is ranking for them. It should be you.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>The proof</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Reviews, projects, and specifications that live in filing cabinets and inboxes, invisible where customers actually look.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"nb-territory-includes","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group nb-territory-includes">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">What you get</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>The build once. The program every month<span style="color:#4ade80">.</span></h2>
    <!-- /wp:heading -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
      <h3>The build, once</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1rem","lineHeight":"1.75"}}} -->
      <p class="has-text-body-color">A new site on a platform built to run a company's front door. Save-first forms that record every inquiry before they notify anyone. An AI assistant that types, never talks: website chat, after-hours cover, missed-call text-back, on a script you approve. A reviews engine. A resource desk for the specifications, drawings, and documents your buyers ask for. Project highlights that prove the work. Tracked numbers and call recording, every account in your name.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
      <h3>The program, monthly</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1rem","lineHeight":"1.75"}}} -->
      <p class="has-text-body-color">Hosting and operations, lead capture and routing, the assistant, reviews, and the monthly report as one system. Alongside it, the content program: the projects engine, the specification desk, and the service-by-county pages that earn the territory back. The content line is separable. It can be taken elsewhere and the platform keeps running.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"nb-gradient-section nb-territory-ninety","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull nb-gradient-section nb-territory-ninety">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">How it starts</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>Live and producing in ninety days<span style="color:#4ade80">.</span></h2>
    <!-- /wp:heading -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"className":"nb-reveal"} -->
    <div class="wp-block-column nb-reveal" style="text-align:center">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","fontWeight":"800"}},"textColor":"accent"} -->
      <p class="has-accent-color">1</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Days 1 to 30. Foundations.</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Design approved by you. Content drafted from your records and your customers' own words. Numbers provisioned in your name.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-reveal"} -->
    <div class="wp-block-column nb-reveal" style="text-align:center">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","fontWeight":"800"}},"textColor":"accent"} -->
      <p class="has-accent-color">2</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Days 31 to 60. Build and prove.</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Site assembled. Forms wired to save before they notify. Assistant trained on approved content. Controlled test calls and submissions.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-reveal"} -->
    <div class="wp-block-column nb-reveal" style="text-align:center">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","fontWeight":"800"}},"textColor":"accent"} -->
      <p class="has-accent-color">3</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Days 61 to 90. Launch and watch.</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Parallel run beside the old site. Launch when the counts match. Daily monitoring through launch week. First monthly report.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"760px"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1rem","lineHeight":"1.75"}}} -->
    <p class="has-text-body-color">While this happens, nothing you rely on changes. Your phones, your email, your current advertising, and the old website all stay exactly as they are until you approve each switch.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"nb-territory-commitments","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group nb-territory-commitments">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">What we hold to</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>Four commitments<span style="color:#4ade80">.</span></h2>
    <!-- /wp:heading -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Measurement is foundation first</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Search and page reporting come with the build. Lead outcomes and job values are added later, only when you choose. No report will look complete while it is not.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Your systems are read only</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">We pull numbers out of the tools you already run. We never write in. Stopping the read leaves them untouched.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>The assistant types, it never talks</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">No automated voice calls, no automated email. Text and chat, on a script you approve.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Everything is in your name</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Domains, hosting, numbers, accounts, data, content. If we ever parted ways, all of it stays with you and keeps working.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","className":"nb-gradient-section nb-territory-exclusive","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"760px"}} -->
<div class="wp-block-group alignfull nb-gradient-section nb-territory-exclusive">
  <!-- wp:group {"className":"nb-reveal","layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">Exclusivity</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>One operator per trade, per market<span style="color:#4ade80">.</span></h2>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">We build one Territory Platform per trade in a given market. Once it is yours, we do not build the same platform for a competitor in your counties. The searches we win for you are not for sale to the company down the road.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"nb-territory-plan","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"760px"}} -->
<div class="wp-block-group nb-territory-plan">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">The plan</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>Two numbers, no surprises<span style="color:#4ade80">.</span></h2>
    <!-- /wp:heading -->
  </div>
  <!-- /wp:group -->
  <!-- wp:group {"className":"nb-glass nb-reveal nb-territory-plan-card","style":{"border":{"color":"rgba(74,222,128,0.3)","width":"1px"},"spacing":{"padding":{"top":"2.5rem","bottom":"2.5rem","left":"2.5rem","right":"2.5rem"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-glass nb-reveal nb-territory-plan-card" style="border-color:rgba(74,222,128,0.3);border-width:1px;padding-top:2.5rem;padding-right:2.5rem;padding-bottom:2.5rem;padding-left:2.5rem">
    <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
    <h3>The build, once</h3>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <p><span style="color:#4ade80">$35,000</span></p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.9375rem"}}} -->
    <p class="has-text-muted-color">The new site and the full platform behind it, built in the first ninety days.</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"},"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
    <h3 style="margin-top:var(--wp--preset--spacing--50)">The program</h3>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}}} -->
    <p><span style="color:#4ade80">$3,200</span><span class="nb-price-note"> a month</span></p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.9375rem"}}} -->
    <p class="has-text-muted-color">In two lines: the platform at $1,950 and the content program at $1,250. The content line is the separable one.</p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.7"},"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
    <p class="has-text-body-color">Paid search management is available as an added line if your advertising moves to us, priced when you decide. Our recommendation is always a gradual taper, with nothing cut for the first six months.</p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.7"}}} -->
    <p class="has-text-body-color">Every engagement starts with the Four Gaps Audit: we read your ad platform, your inquiry database, and your search territory, and show you the four gaps on your own numbers. Its fee credits in full toward the build.</p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
    <p><a class="nb-btn-primary" href="/contact/" style="display:block;text-align:center">Ask for the Four Gaps Audit</a></p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-territory-motto","style":{"typography":{"fontSize":"clamp(1.25rem, 2.5vw, 1.75rem)","lineHeight":"1.4","fontWeight":"500","letterSpacing":"-0.01em"}}} -->
    <p class="nb-territory-motto">The content earns the visit. The website earns the call. And everything that earns is yours.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 2: Append the CSS**

Append to `wp-content/themes/newblood/assets/css/patterns.css`:

```css

/* ===== Territory flagship page ===== */
/* Four-card rows (gaps, commitments) wrap 2x2 at tablet width, like the services cards */
@media (max-width: 1024px) and (min-width: 769px) {
  .nb-territory-gaps .wp-block-columns.nb-stagger,
  .nb-territory-commitments .wp-block-columns.nb-stagger {
    flex-wrap: wrap !important;
  }
  .nb-territory-gaps .wp-block-columns.nb-stagger > .wp-block-column,
  .nb-territory-commitments .wp-block-columns.nb-stagger > .wp-block-column {
    flex-basis: calc(50% - 0.75rem) !important;
    flex-grow: 0 !important;
  }
}

.nb-territory-plan-card .nb-price-note {
  display: inline;
  font-size: 1rem;
  font-weight: 600;
  color: var(--wp--preset--color--text-secondary, #9ca3af);
  margin-left: 0.25rem;
}

.nb-territory-motto {
  color: var(--wp--preset--color--text-secondary, #d1d5db);
  max-width: 34ch;
  margin-left: auto;
  margin-right: auto;
}
```

- [ ] **Step 3: Lint, balance, register, and content checks**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood
W="php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood"
cd wp-content/themes/newblood
php -l patterns/territory.php
f=patterns/territory.php; o=$(grep -o '<!-- wp:' $f | wc -l); s=$(grep -o '/-->' $f | wc -l); c=$(grep -o '<!-- /wp:' $f | wc -l); echo "$((o-s)) $c"
grep -c '—' patterns/territory.php
grep -n -i -E 'overhead|ohdbalt|baltimore|1947|3,000|nine years' patterns/territory.php | wc -l
grep -c -i -w 'tune' patterns/territory.php
head -1 patterns/territory.php | grep -c nb-reveal
$W db query "DELETE FROM wp_options WHERE option_name LIKE '_site_transient_wp_theme_files_patterns%' OR option_name LIKE '_site_transient_timeout_wp_theme_files_patterns%'" 2>/dev/null
$W eval 'echo WP_Block_Patterns_Registry::get_instance()->is_registered("newblood/territory") ? 1 : 0, "\n";' 2>/dev/null
```
Expected: `No syntax errors`; two equal numbers; `0`; `0`; `0`; `0`; and finally `1` (the pattern is registered). If the last line prints `0`, check the header comment block: `Slug: newblood/territory` must be exact.

- [ ] **Step 4: Commit**

```bash
cd /Users/jeremyoms/Herd/newblood
git add wp-content/themes/newblood/patterns/territory.php wp-content/themes/newblood/assets/css/patterns.css
git commit -m "territory: flagship page body pattern (newblood/territory)

Seven sections: opening, four gaps, what you get, ninety days, four
commitments, exclusivity, the plan. No client named. Spec step 2.

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01VshhFDSHXK2LDLG1kbBVVU"
```

---

### Task 5: Create the /territory page and the nav item (local)

**Files:**
- WordPress DB (local): new page `territory`; nav post 6737 content.
- No repo files change. (The commands are recorded here and re-run on production in Task 7.)

**Interfaces:**
- Consumes: pattern slug `newblood/territory` (Task 4).
- Produces: `http://newblood.test/territory/` (200) and the "Territory" nav link. Task 2's and Task 3's `/territory/` links now resolve.

- [ ] **Step 1: Confirm no page with that slug exists**

Run:
```bash
W="php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood"
$W post list --post_type=page --name=territory --field=ID 2>/dev/null | wc -l
```
Expected: `0`. If `1`, the page exists; use `post update <ID>` with the same fields in Step 2 instead of `post create`.

- [ ] **Step 2: Create the page**

Run:
```bash
W="php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood"
$W post create --post_type=page --post_title="The Territory Platform" --post_name=territory --post_status=publish --post_excerpt="For established operators" --post_content='<!-- wp:pattern {"slug":"newblood/territory"} /-->' --porcelain 2>/dev/null
```
Expected: an integer page ID. Note it.

- [ ] **Step 3: Verify the page renders every section**

Run:
```bash
curl -s -o /tmp/nb-territory.html -w '%{http_code}\n' http://newblood.test/territory/
grep -c 'For established operators' /tmp/nb-territory.html
grep -c 'Own the searches your name already earned' /tmp/nb-territory.html
grep -c 'Four gaps, read from your own numbers' /tmp/nb-territory.html
grep -c 'The build once. The program every month' /tmp/nb-territory.html
grep -c 'Live and producing in ninety days' /tmp/nb-territory.html
grep -c 'Four commitments' /tmp/nb-territory.html
grep -c 'One operator per trade, per market' /tmp/nb-territory.html
grep -c 'Two numbers, no surprises' /tmp/nb-territory.html
grep -c 'everything that earns is yours' /tmp/nb-territory.html
grep -c 'Ask for the Four Gaps Audit' /tmp/nb-territory.html
```
Expected: `200`, then ten lines of `1`. If the section counts are `0` but the status is 200, the pattern is not registered: rerun the transient delete from Task 4 Step 3.

- [ ] **Step 4: Add "Territory" to the primary navigation**

The current content of nav post 6737 is one line of six `wp:navigation-link` blocks. Insert the Territory link between Services and Work:

```bash
W="php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood"
$W post update 6737 --post_content='<!-- wp:navigation-link {"label":"Services","url":"/services/","kind":"post-type","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"Territory","url":"/territory/","kind":"post-type","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"Work","url":"/work/","kind":"post-type","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"Notes","url":"/notes/","kind":"post-type","isTopLevelLink":true,"className":"menu-item--notes"} /--><!-- wp:navigation-link {"label":"Pricing","url":"/pricing/","kind":"post-type","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"About","url":"/about/","kind":"post-type","isTopLevelLink":true} /--><!-- wp:navigation-link {"label":"Get Started","url":"/contact/","kind":"post-type","isTopLevelLink":true,"className":"nb-nav-cta"} /-->' 2>/dev/null
```
Expected: `Success: Updated post 6737.`

Footer navigations 6738 ("Footer Company": About, Services, Work, Notes) and 6739 ("Footer Connect": Contact, Pricing) do not list every main page, so they are left unchanged.

- [ ] **Step 5: Verify the nav renders and the header is not crowded**

Run:
```bash
curl -s http://newblood.test/ | grep -o '<a[^>]*href="/territory/"[^>]*>[^<]*' | head -3
```
Expected: at least one match whose text is `Territory` (the header nav), plus the two-doors button. Then open `http://newblood.test/` in a browser at 1280 and 1024 wide and confirm the seven header items fit on one line without wrapping; if they wrap at 1024, note it for Task 6 rather than fixing here.

- [ ] **Step 6: Record the commands (no commit)**

Nothing in the repo changed. Paste the two commands from Steps 2 and 4 into the session notes for Task 7, where they are re-run on production.

---

### Task 6: Responsive and visual QA

**Files:**
- Possibly modify: `wp-content/themes/newblood/assets/css/patterns.css` (only if a check fails)

**Interfaces:**
- Consumes: all of Tasks 1 through 5 rendered on `http://newblood.test`.
- Produces: a clean bill for the four URLs, or targeted CSS fixes committed.

- [ ] **Step 1: Screenshot the four pages at three widths**

Use the browser tools (claude-in-chrome) or open manually. For each of `/`, `/territory/`, `/pricing/`, `/about/` at 375, 768, and 1280 wide, check:
- No horizontal scrollbar (body never scrolls sideways).
- Card rows stack (375), wrap 2x2 where planned (768: two-doors stacks; territory gaps and commitments 2x2 only between 769 and 1024, single column at 768), and sit in a row at 1280.
- Every `nb-reveal` section becomes visible when scrolled into view; nothing stays at opacity 0 (the JS threshold is 0.15, so a section taller than the viewport that never reaches 15% visible would stay hidden; none of the new sections are that tall, but confirm on `/territory/` at 375).
- The header nav fits on one line at 1280 and 1024.

- [ ] **Step 2: Fix any failure with the smallest CSS change, in the matching scope**

Rules: keep fixes inside `.nb-two-doors`, `.nb-pricing`, or `.nb-territory-*` scopes; never edit `utilities.css` unless a stretched link was added (none is planned). Re-run Step 1 for the affected page after each fix.

- [ ] **Step 3: Re-run the content assertions from Tasks 1, 2, 3, and 5 Step 3**

All counts as previously expected.

- [ ] **Step 4: Commit any CSS fixes**

```bash
cd /Users/jeremyoms/Herd/newblood
git add wp-content/themes/newblood/assets/css/patterns.css
git commit -m "css: responsive fixes from territory phase-one QA

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01VshhFDSHXK2LDLG1kbBVVU"
```
Skip the commit if nothing changed.

---

### Task 7: Docs, hub, and production deploy

**Files:**
- Modify: `CLAUDE.md` (Service lineup section)
- Modify: `TASKS.md`, `~/Herd/_hub/PROJECTS.md`
- Production: theme via `deploy.sh`; page + nav via WP-CLI over SSH.

**Interfaces:**
- Consumes: everything above, committed on `feature/redesign`.
- Produces: the four URLs live on `https://newblood.com`.

- [ ] **Step 1: Update the CLAUDE.md service-lineup note**

In `CLAUDE.md`, replace the paragraph beginning `The `/pricing` page has 3 build tiers` with:

```markdown
The `/pricing` page is two doors. Door one: Starter $3,500 / Business $5,000 plus the "Already have a site?" Tune / Tune Plus row. Door two (added 2026-09-02): Four Gaps Audit $1,500-$2,500 / Territory Platform $35,000 + $3,200/mo (Flagship) / Operations Platform ("Scoped per operator" until a shape doc exists; then "from $90,000"). The `/territory` page (pattern `newblood/territory`) is the flagship offer page; the homepage has a `newblood/two-doors` section directly below the hero (the `statement` pattern is no longer in `front-page.html` but stays on disk). Territory is Build + Manage + the content program packaged for established operators, not a fifth service. Plan: `docs/newblood-territory-repositioning-2026-09.md`.
```

Also update the Signal sentence that follows to end with: `Its About-page intro will point to the Four Gaps Audit page once that page exists (phase two).`

- [ ] **Step 2: Commit the docs**

```bash
cd /Users/jeremyoms/Herd/newblood
git add CLAUDE.md
git commit -m "docs: CLAUDE.md service lineup reflects the two-door pricing and /territory

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01VshhFDSHXK2LDLG1kbBVVU"
```

- [ ] **Step 3: Deploy dry run, then STOP for confirmation**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood
./deploy.sh --dry-run
```
Expected: the changed files listed are exactly `patterns/hero.php`, `patterns/about-story.php`, `patterns/faq.php`, `patterns/two-doors.php`, `patterns/pricing-table.php`, `patterns/territory.php`, `templates/front-page.html`, `assets/css/patterns.css`, and zero deletions. If the list includes anything else, stop and investigate before continuing.

**Show the dry-run output to Jeremy and wait for an explicit go.** Production is shared state.

- [ ] **Step 4: Real deploy**

Run after confirmation:
```bash
cd /Users/jeremyoms/Herd/newblood
./deploy.sh
```
Expected: the same file list transferred, zero deletions, exit 0.

- [ ] **Step 5: Create the page and nav item on production**

Credentials are in `.nexcess-credentials` (see the production-server memory). Use password auth explicitly to avoid the MaxAuthTries quirk. The site root is `/home/aaa2f02d/public_html`.

```bash
ssh -p 22 -o PubkeyAuthentication=no -o PreferredAuthentications=password aaa2f02d_1@588b7956f6.nxcli.net 'cd /home/aaa2f02d/public_html && wp post list --post_type=page --name=territory --field=ID | wc -l'
```
Expected: `0`. Then:

```bash
ssh -p 22 -o PubkeyAuthentication=no -o PreferredAuthentications=password aaa2f02d_1@588b7956f6.nxcli.net 'cd /home/aaa2f02d/public_html && wp post create --post_type=page --post_title="The Territory Platform" --post_name=territory --post_status=publish --post_excerpt="For established operators" --post_content='"'"'<!-- wp:pattern {"slug":"newblood/territory"} /-->'"'"' --porcelain'
```
Expected: an integer ID.

Then the nav. First confirm the production nav post ID matches local (6737) by label:
```bash
ssh -p 22 -o PubkeyAuthentication=no -o PreferredAuthentications=password aaa2f02d_1@588b7956f6.nxcli.net 'cd /home/aaa2f02d/public_html && wp post list --post_type=wp_navigation --fields=ID,post_title --format=csv'
```
Use the ID whose content contains `"label":"Get Started"` (check with `wp post get <ID> --field=post_content | grep -c "Get Started"`). Then run the same `post update <ID> --post_content='...'` command as Task 5 Step 4, with that ID.

Then clear the pattern transients on production:
```bash
ssh -p 22 -o PubkeyAuthentication=no -o PreferredAuthentications=password aaa2f02d_1@588b7956f6.nxcli.net 'cd /home/aaa2f02d/public_html && wp db query "DELETE FROM wp_options WHERE option_name LIKE '"'"'_site_transient_wp_theme_files_patterns%'"'"' OR option_name LIKE '"'"'_site_transient_timeout_wp_theme_files_patterns%'"'"'" && wp cache flush'
```

- [ ] **Step 6: Live-verify**

Run:
```bash
for u in / /territory/ /pricing/ /about/; do printf '%s ' "$u"; curl -s -o /tmp/nb-live.html -w '%{http_code} ' "https://newblood.com$u"; case $u in
  /) grep -c 'A platform that wins your territory' /tmp/nb-live.html;;
  /territory/) grep -c 'One operator per trade, per market' /tmp/nb-live.html;;
  /pricing/) grep -c '>Flagship<' /tmp/nb-live.html;;
  /about/) grep -c 'At the scale of your business' /tmp/nb-live.html;;
esac; done
```
Expected: each line `<url> 200 1`. If a page returns 200 with `0`, production is serving a cached page: purge the Nexcess/CDN cache (as done for the 8/19 case-study deploy) and re-run.

- [ ] **Step 7: Hub updates**

- `TASKS.md`: move the repositioning item's phase-one portion to ✅ Done with today's date and one line ("phase one live: modesty copy fixed, /territory, two-door /pricing, two-door homepage"); under ▶️ Doing now, surface the next step: "Phase two: Four Gaps Audit page wired to the discovery form; About Signal intro repoint."
- `~/Herd/_hub/PROJECTS.md`: set New Blood's `⟳` to today's date and refresh the one-line status.
- `~/Herd/_hub/TODAY.md`: check off the item only if it appears there.

- [ ] **Step 8: Commit the hub file in this repo**

```bash
cd /Users/jeremyoms/Herd/newblood
git add TASKS.md
git commit -m "TASKS: territory phase one deployed

Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
Claude-Session: https://claude.ai/code/session_01VshhFDSHXK2LDLG1kbBVVU"
```

---

## Self-review notes

- **Spec coverage.** Step 1 (five copy lines) = Task 1. Step 2 (page, pattern, nav, transients, seven sections, register rules) = Tasks 4 and 5. Step 3 (pricing two doors, card copy, credit line, Reach removed, door-one width) = Task 3. Step 4 (two-doors pattern, template swap, statement kept on disk, About unchanged) = Task 2. Spec verification list = Tasks 1 to 6 assertions. Deploy and the DB-carried page/nav = Task 7.
- **Naming consistency.** Pattern slugs: `newblood/two-doors`, `newblood/territory`. CSS scopes: `.nb-two-doors` (`.nb-door`, `.nb-door-title`, `.nb-door-links`, `.nb-door-textlink`, `.nb-door--territory`), `.nb-pricing .nb-door-one-pricing`, `.nb-door-two-pricing` (markup only, no rule needed), `.nb-price-note`, `.nb-territory-gaps`, `.nb-territory-commitments`, `.nb-territory-plan-card`, `.nb-territory-motto`. Page slug `territory`, excerpt "For established operators". Nav post 6737 locally; production ID confirmed by label in Task 7.
- **Known judgment calls.** The two-doors group is not `align:full` (it mirrors `services-cards`, which sits on the plain background). The territory pattern's gradient sections are `align:full` so they run edge to edge inside `page.html`, which has no wrapper. `.nb-price-note` is `display:block` on pricing cards and `inline` on the territory plan card; the two rules are scoped so they do not collide.
