# Services-page TL;DR card Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a scan-friendly three-field TL;DR card (For / Get / Includes) under each of the four service headings in `services-detail.php`, replacing the existing one-line emotional lede paragraph. The card spans full width below the mark + h2 header row, not nested next to the h2.

**Architecture:** Pure CSS + inline HTML. A new `.nb-service-tldr` component lives in `assets/css/patterns.css` alongside `.nb-service-mark` from the prior engagement. CSS grid handles the For/Get side-by-side layout with a full-width Includes row beneath. At narrow widths the grid collapses to a single column. The card itself sits inside a `<!-- wp:html -->` block in each service section, so Gutenberg treats it as opaque and won't re-parse on save.

**Tech Stack:** WordPress block-theme PHP patterns, vanilla CSS (`grid-template-areas`, simple media query), inline HTML. No build step — `filemtime()` cache-busting in `functions.php` handles asset invalidation. No JS additions.

**Spec:** `docs/superpowers/specs/2026-05-11-services-tldr-card-design.md`

---

## File map

| File | Action | Why |
|---|---|---|
| `wp-content/themes/newblood/assets/css/patterns.css` | Modify (append) | Add the `.nb-service-tldr` component CSS at the end of the file, next to `.nb-service-mark` from the prior engagement. |
| `wp-content/themes/newblood/patterns/services-detail.php` | Modify | For each of the four service sections, delete the existing one-line lede `wp:paragraph` block from inside the h2-wrapper group, and insert a `<!-- wp:html -->` block containing the TL;DR card markup as a new sibling of the flex-header `wp:group`. Single contiguous edit per service. |

No new files. No JS changes. `services-cards.php` (homepage card row) is untouched — explicitly out of scope per the spec.

---

### Task 1: Add `.nb-service-tldr` CSS to patterns.css

**Files:**
- Modify: `wp-content/themes/newblood/assets/css/patterns.css` (append at end of file)

- [ ] **Step 1: Append the TL;DR card CSS block to the end of `patterns.css`**

Open `wp-content/themes/newblood/assets/css/patterns.css` and append the following block at the end of the file (it will sit immediately after the service-mark CSS that already lives there):

```css

/* ---- Service TL;DR card ----
 * Three-field scan-friendly summary (For / Get / Includes) that replaces the
 * lede paragraph under each service h2 in services-detail.php.
 * Visual treatment echoes the .nb-case-meta pattern from case-study pages.
 * Spec: docs/superpowers/specs/2026-05-11-services-tldr-card-design.md
 */

.nb-service-tldr {
  display: grid;
  grid-template-columns: 1fr 1fr;
  grid-template-areas:
    "for get"
    "includes includes";
  gap: 22px 36px;
  padding: 22px 0;
  margin: 18px 0 26px;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.nb-service-tldr__item--for { grid-area: for; }
.nb-service-tldr__item--get { grid-area: get; }
.nb-service-tldr__item--includes { grid-area: includes; }

.nb-service-tldr__label {
  display: block;
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: 0.16em;
  color: rgba(74, 222, 128, 0.75);
  font-weight: 600;
  margin-bottom: 6px;
}

.nb-service-tldr__value {
  font-size: 14px;
  line-height: 1.55;
  color: rgba(255, 255, 255, 0.85);
  margin: 0;
}

.nb-service-tldr__value--get {
  font-size: 16px;
  line-height: 1.5;
  color: #fff;
}

.nb-service-tldr__value--includes {
  font-size: 13.5px;
  letter-spacing: 0.02em;
  color: rgba(255, 255, 255, 0.7);
}

@media (max-width: 640px) {
  .nb-service-tldr {
    grid-template-columns: 1fr;
    grid-template-areas:
      "for"
      "get"
      "includes";
    gap: 18px;
  }
}
```

- [ ] **Step 2: Verify the file appended cleanly**

Run:
```bash
tail -5 /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/assets/css/patterns.css
```

Expected: the last lines should be the closing brace of the `@media (max-width: 640px)` block. No truncation, no missing braces.

- [ ] **Step 3: Confirm filemtime cache-bust will fire**

Run:
```bash
ls -la /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/assets/css/patterns.css
```

Expected: modification time matches the moment of the edit (within seconds). `filemtime()` in `functions.php` reads this and emits a fresh `?ver=` query string on the next request.

---

### Task 2: Replace each service's lede paragraph with a TL;DR card in `services-detail.php`

**Files:**
- Modify: `wp-content/themes/newblood/patterns/services-detail.php` (four lede replacements)

Each replacement is a single string substitution that:
- Removes the existing one-line `wp:paragraph` block (the emotional lede) from inside the inner h2-wrapper `wp:group`
- Inserts a `<!-- wp:html -->` block containing the TL;DR card as a new sibling of the flex-header `wp:group`, before the next body paragraph

The anchor string for each substitution extends from the start of the lede `wp:paragraph` block through the start of the first body paragraph that follows, so the replacement is unique per service.

- [ ] **Step 1: Replace Build lede with Build TL;DR card**

In `wp-content/themes/newblood/patterns/services-detail.php`, replace the following block:

```
        <!-- wp:paragraph {"textColor":"text-secondary","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
        <p class="has-text-secondary-color">Your website is the first impression most customers will have of your business. We make it count.</p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">We design and develop custom websites using AI-accelerated workflows
```

with:

```
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->

    <!-- wp:html -->
    <div class="nb-service-tldr">
      <div class="nb-service-tldr__item nb-service-tldr__item--for">
        <span class="nb-service-tldr__label">For</span>
        <p class="nb-service-tldr__value">businesses that want a website made for them, not chosen from a template</p>
      </div>
      <div class="nb-service-tldr__item nb-service-tldr__item--get">
        <span class="nb-service-tldr__label">Get</span>
        <p class="nb-service-tldr__value nb-service-tldr__value--get">a custom, modern website tuned to your brand and built to perform</p>
      </div>
      <div class="nb-service-tldr__item nb-service-tldr__item--includes">
        <span class="nb-service-tldr__label">Includes</span>
        <p class="nb-service-tldr__value nb-service-tldr__value--includes">Custom design · Modern code · Performance from day one</p>
      </div>
    </div>
    <!-- /wp:html -->

    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">We design and develop custom websites using AI-accelerated workflows
```

- [ ] **Step 2: Replace Tune lede with Tune TL;DR card**

In the same file, replace the following block:

```
        <!-- wp:paragraph {"textColor":"text-secondary","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
        <p class="has-text-secondary-color">Existing site, sharper performance.</p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">Most WordPress sites running heavy commercial themes
```

with:

```
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->

    <!-- wp:html -->
    <div class="nb-service-tldr">
      <div class="nb-service-tldr__item nb-service-tldr__item--for">
        <span class="nb-service-tldr__label">For</span>
        <p class="nb-service-tldr__value">an existing site losing visitors and ad spend to slow mobile load times</p>
      </div>
      <div class="nb-service-tldr__item nb-service-tldr__item--get">
        <span class="nb-service-tldr__label">Get</span>
        <p class="nb-service-tldr__value nb-service-tldr__value--get">a measurably faster site — without rebuilding anything</p>
      </div>
      <div class="nb-service-tldr__item nb-service-tldr__item--includes">
        <span class="nb-service-tldr__label">Includes</span>
        <p class="nb-service-tldr__value nb-service-tldr__value--includes">PageSpeed audit · Surgical fixes · Before-and-after proof</p>
      </div>
    </div>
    <!-- /wp:html -->

    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">Most WordPress sites running heavy commercial themes
```

- [ ] **Step 3: Replace Manage lede with Manage TL;DR card**

In the same file, replace the following block:

```
        <!-- wp:paragraph {"textColor":"text-secondary","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
        <p class="has-text-secondary-color">A website isn't a one-time project — it's a living asset that needs care.</p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">Every site we build comes with the option to join our managed hosting
```

with:

```
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->

    <!-- wp:html -->
    <div class="nb-service-tldr">
      <div class="nb-service-tldr__item nb-service-tldr__item--for">
        <span class="nb-service-tldr__label">For</span>
        <p class="nb-service-tldr__value">owners who don't want to think about updates, backups, or whether the site is up at 2 AM</p>
      </div>
      <div class="nb-service-tldr__item nb-service-tldr__item--get">
        <span class="nb-service-tldr__label">Get</span>
        <p class="nb-service-tldr__value nb-service-tldr__value--get">a site that just runs, looked after in the background</p>
      </div>
      <div class="nb-service-tldr__item nb-service-tldr__item--includes">
        <span class="nb-service-tldr__label">Includes</span>
        <p class="nb-service-tldr__value nb-service-tldr__value--includes">Managed hosting · Security &amp; updates · Backups &amp; monitoring</p>
      </div>
    </div>
    <!-- /wp:html -->

    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">Every site we build comes with the option to join our managed hosting
```

- [ ] **Step 4: Replace Empower lede with Empower TL;DR card**

In the same file, replace the following block:

```
        <!-- wp:paragraph {"textColor":"text-secondary","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
        <p class="has-text-secondary-color">Your website. Your content. Your control.</p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">We believe you shouldn't need to call a developer
```

with:

```
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->

    <!-- wp:html -->
    <div class="nb-service-tldr">
      <div class="nb-service-tldr__item nb-service-tldr__item--for">
        <span class="nb-service-tldr__label">For</span>
        <p class="nb-service-tldr__value">teams that want to publish their own content without breaking the design</p>
      </div>
      <div class="nb-service-tldr__item nb-service-tldr__item--get">
        <span class="nb-service-tldr__label">Get</span>
        <p class="nb-service-tldr__value nb-service-tldr__value--get">editorial control of your own site, with the structural side held safely by us</p>
      </div>
      <div class="nb-service-tldr__item nb-service-tldr__item--includes">
        <span class="nb-service-tldr__label">Includes</span>
        <p class="nb-service-tldr__value nb-service-tldr__value--includes">Block editor · Hands-on training · A real point of contact</p>
      </div>
    </div>
    <!-- /wp:html -->

    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">We believe you shouldn't need to call a developer
```

- [ ] **Step 5: Lint the file**

Run:
```bash
php -l /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/patterns/services-detail.php
```

Expected: `No syntax errors detected in ...`

- [ ] **Step 6: Verify block-comment balance**

Run:
```bash
opens=$(grep -c '<!-- wp:' /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/patterns/services-detail.php)
closes=$(grep -c '<!-- /wp:' /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/patterns/services-detail.php)
echo "opens=$opens closes=$closes"
```

Expected: `opens` and `closes` are equal. Before this task they were balanced at the same value; each lede-removal eliminates one `wp:paragraph` open + one close (net 0); each card-insertion adds one `wp:html` open + one close (net 0). Balance is preserved.

---

### Task 3: Render verification + contrast spot-check

**Files:** None modified. Verification only.

- [ ] **Step 1: Curl the /services page and confirm card markup is present**

Run:
```bash
curl -s http://newblood.test/services/ -o /tmp/services-after.html && \
python3 -c "
s = open('/tmp/services-after.html').read()
print('TL;DR card divs (.nb-service-tldr):', s.count('class=\"nb-service-tldr\"'))
print('For labels:', s.count('>For<'))
print('Get labels:', s.count('>Get<'))
print('Includes labels:', s.count('>Includes<'))
print()
print('Old Build lede present (should be False):', 'Your website is the first impression' in s)
print('Old Tune lede present (should be False):', 'Existing site, sharper performance' in s)
print('Old Manage lede present (should be False):', 'living asset that needs care' in s)
print('Old Empower lede present (should be False):', 'Your website. Your content. Your control' in s)
print()
print('New Build Get text present:', 'tuned to your brand and built to perform' in s)
print('New Tune Get text present:', 'measurably faster site' in s)
print('New Manage Get text present:', 'looked after in the background' in s)
print('New Empower Get text present:', 'editorial control of your own site' in s)
"
```

Expected output:
- `TL;DR card divs (.nb-service-tldr): 4`
- `For labels: 4`
- `Get labels: 4`
- `Includes labels: 4`
- All four "Old lede present" lines: `False`
- All four "New Get text present" lines: `True`

- [ ] **Step 2: Confirm patterns.css cache-bust query is fresh**

Run:
```bash
python3 -c "
import re
s = open('/tmp/services-after.html').read()
m = re.search(r'patterns\.css\?ver=(\d+)', s)
print('patterns.css ver:', m.group(1) if m else 'MISSING')
"
```

Expected: a 10-digit Unix timestamp matching the modification time of `patterns.css` (set in Task 1).

- [ ] **Step 3: Manual contrast spot-check**

Open `http://newblood.test/services/` in a browser. Scroll to any service section. The "FOR", "GET", "INCLUDES" labels render in small green caps against the dark gradient background.

Visually confirm:
- Labels are readable without straining
- Get values feel slightly heavier than For values (the 16px vs 14px size bump)
- The card sits cleanly between the mark+h2 header and the body paragraphs

If the green label feels too dim, the spec authorizes bumping the label opacity from 0.75 to 0.85 before changing color. Note this as a follow-up rather than blocking the commit.

- [ ] **Step 4: Responsive spot-check**

Resize the browser window to ~600px wide. The For/Get two-column row should collapse to a single stacked column. Includes row stays full-width.

If the breakpoint feels off (e.g., the columns get too cramped before 640px or stay two-column too long after), adjust the `@media (max-width: 640px)` value in `patterns.css` and re-verify.

---

### Task 4: Commit

**Files:** None modified beyond what's already staged from prior tasks.

- [ ] **Step 1: Stage all changes**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood
git add wp-content/themes/newblood/assets/css/patterns.css \
        wp-content/themes/newblood/patterns/services-detail.php
git status --short
```

Expected: 2 files staged (`M` for modified), nothing else.

- [ ] **Step 2: Create the commit**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood
cat > /tmp/commit-msg.txt << 'COMMITEOF'
feat(services): add scan-friendly TL;DR cards under each service h2

Replaces the existing one-line emotional lede paragraph under each of
the four service sections on /services with a three-field structured
summary card (For / Get / Includes). Visual treatment is the meta-
style split — top/bottom-bordered band, For + Get side by side, full-
width Includes row beneath. Echoes the .nb-case-meta pattern from
case-study pages so the card feels like part of the established
visual vocabulary rather than a new chrome layer.

Per-service copy: Build / Tune / Manage / Empower each get a distinct
audience identifier (For), outcome line (Get), and three-item
deliverable list (Includes). Lets scan-readers self-identify and grasp
each service in two seconds without reading the deep-dive paragraphs
or eight-bullet list that follow.

Scope: services-detail.php only. The homepage services-cards row
stays as-is — already compact, would just duplicate. Card itself uses
plain CSS grid with a single-column responsive collapse at 640px. No
JS additions, no new assets.

Spec: docs/superpowers/specs/2026-05-11-services-tldr-card-design.md
Plan: docs/superpowers/plans/2026-05-12-services-tldr-card.md

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
COMMITEOF
git commit -F /tmp/commit-msg.txt
git log --oneline -3
```

Expected: commit succeeds, hash returned, top of `git log --oneline` shows the new commit followed by `d802c487 docs(spec): add services-page TL;DR card design`.

---

## Self-review checklist (controller-side, before handing back to user)

- [ ] **Spec coverage:** every per-service copy row in the spec table is present in Task 2 (Build/Tune/Manage/Empower). Verify each Get line matches the locked copy exactly, including the em-dash in Tune's Get and the `&amp;` HTML-entity for ampersand in Manage's Includes row.

- [ ] **Placeholder scan:** no `TBD`/`TODO`/`fill in details`/`similar to above` anywhere in this plan. Each Task 2 step contains the complete before/after block.

- [ ] **Type consistency:** the CSS class names (`.nb-service-tldr`, `.nb-service-tldr__item`, `.nb-service-tldr__item--for`, `.nb-service-tldr__label`, `.nb-service-tldr__value`, `.nb-service-tldr__value--get`, `.nb-service-tldr__value--includes`) are used identically across Task 1 (CSS) and Task 2 (HTML). The grid-template-areas names (`for`, `get`, `includes`) match the modifier classes.

- [ ] **HTML-entity check:** Manage's "Security & updates · Backups & monitoring" must be encoded as `Security &amp; updates · Backups &amp; monitoring` in the HTML source per Task 2 Step 3, otherwise WordPress/Gutenberg may complain or browsers may render variably. The other three services have no ampersands and are unaffected.
