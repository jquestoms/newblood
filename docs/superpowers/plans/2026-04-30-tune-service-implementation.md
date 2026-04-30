# Tune Service — Sales Surface Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the Tune service to newblood.com as the fourth service line by editing three WordPress block-pattern files (services-cards, services-detail, pricing-table) and adding one scoped CSS rule for 4-column tablet layout.

**Architecture:** Three pattern PHP files get additive edits — a new card on services-cards, a new detail block on services-detail (inserted between Build and Manage to match lineup order), and a new "Already have a site?" pricing block at the bottom of pricing-table. One CSS addition handles 4-column tablet wrapping, scoped via a new `nb-services-cards` class so other 3-column patterns are unaffected.

**Tech Stack:** WordPress block theme (FSE), PHP pattern files with Gutenberg block markup, vanilla CSS in `assets/css/patterns.css`. Local dev via Laravel Herd (`http://newblood.test`).

**Spec:** `docs/superpowers/specs/2026-04-30-tune-service-design.md`

---

## Context the Engineer Needs

### What a WP block pattern file looks like

A `.php` file under `wp-content/themes/newblood/patterns/` with a docblock header (Title, Slug, Categories, Description) followed by HTML wrapped in Gutenberg block comments (`<!-- wp:foo {...} --> ... <!-- /wp:foo -->`). The block comments are meaningful — WordPress parses the file into editable blocks at render time. Pairs of `<!-- wp:X -->` and `<!-- /wp:X -->` must stay balanced. Self-closing blocks use `<!-- wp:X /-->` (single tag, no closing partner).

### How the existing service patterns relate

- `services-cards.php` — three "card" columns visible on the homepage and the /services page. Currently Build / Manage / Empower.
- `services-detail.php` — three larger detail blocks visible on the /services page (below or instead of the cards depending on page composition). Currently Build / Manage / Empower in that order, separated by `wp:separator` blocks.
- `pricing-table.php` — three pricing tier cards (Starter / Business / Reach) on the /pricing page.

After this plan: a fourth card lands in services-cards (between Build and Manage), a fourth detail block lands in services-detail (also between Build and Manage), and a new two-card "Already have a site?" section lands at the bottom of pricing-table.

### Lineup order

**Build · Tune · Manage · Empower.** This is the spec's canonical order. Both services-cards and services-detail must match this order. The earlier draft of the spec said "after Empower" for services-detail; ignore that in favor of the canonical lineup order. A user reading services-cards and then scrolling to services-detail should see the same sequence.

### Voice rules for the new copy (already approved in spec)

- Service name: **Tune** — Tier 1 musical word, double-meaning works as plain English and as a quiet musical signal.
- Earned phrases: *"up to speed"*, *"the band Google rewards"*, *"sharper performance"*. All Tier 1.
- No Tier 3 musical jargon (no "remix," "drop," "groove," etc.).
- Pricing reads as plain numbers — $2,000 and $4,500. No psychological-pricing markup, no urgency.

### Verification approach

Copy/markup changes in WP block theme have no traditional unit tests. Verification is:

- `php -l <file>` — syntax check
- Block-comment balance check via `grep -c` for paired `<!-- wp:X -->` / `<!-- /wp:X -->`
- Visual verification at `http://newblood.test/services` and `http://newblood.test/pricing` (filemtime cache busting is already wired up; a normal browser reload is enough)
- Responsive verification at desktop (≥1024px), tablet (768-1024px), mobile (<768px)

### Pages that render these patterns

- **services-cards** is referenced from `templates/front-page.html` (homepage) and likely embedded via post_content on `/services` (verify in WP admin).
- **services-detail** is embedded via post_content on `/services` (verify in WP admin).
- **pricing-table** is embedded via post_content on `/pricing` (verify in WP admin).

The new fourth card auto-appears anywhere services-cards is referenced — no template edits needed.

### CSS file: where to add the new rule

`wp-content/themes/newblood/assets/css/patterns.css` is the patterns/components stylesheet. The relevant section for column-related rules is around line 134 (the `.nb-stagger.wp-block-columns` block) and the existing tablet/mobile rule is at line 191-195 (`@media (max-width: 768px) { .nb-stagger > .wp-block-column { flex-basis: 100% !important; } }`). Add the new tablet rule just below the existing 768px rule.

---

## File Structure

Files modified (no new files created):

| File | What changes |
|---|---|
| `wp-content/themes/newblood/patterns/services-cards.php` | Add `nb-services-cards` class to outer group; insert Tune column between Build (lines 23-33) and Manage (lines 34-44) |
| `wp-content/themes/newblood/patterns/services-detail.php` | Insert a Tune detail section + a separator after the Build section's closing `</div><!-- /wp:group -->` (around line 72) and before the existing separator that introduces the Manage section |
| `wp-content/themes/newblood/patterns/pricing-table.php` | Insert a separator + "Already have a site?" header group + 2-column Tune/Tune Plus block at the bottom of the outer group, just before `</div><!-- /wp:group -->` (the close of the alignfull pricing wrapper, around line 104) |
| `wp-content/themes/newblood/assets/css/patterns.css` | Add a new `@media (max-width: 1024px) and (min-width: 769px)` rule scoped to `.nb-services-cards` for 2×2 tablet wrapping. Add a `.nb-tune-pricing` width-cap rule. |

---

## Task 1: services-cards — add Tune card

**Files:**
- Modify: `wp-content/themes/newblood/patterns/services-cards.php` (line 9 outer group className; insert new column between current lines 33 and 34)
- Modify: `wp-content/themes/newblood/assets/css/patterns.css` (insert new rule below current line ~195)

This task does two things together because they're tightly coupled — adding a fourth card to a 3-column layout would cram the cards on tablet without the matching CSS, and adding the CSS without the fourth card has no effect. Ship as one commit.

- [ ] **Step 1: Add `nb-services-cards` class to the outer group**

In `wp-content/themes/newblood/patterns/services-cards.php`, change line 9-10 from:

```html
<!-- wp:group {"className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group nb-gradient-section">
```

to:

```html
<!-- wp:group {"className":"nb-gradient-section nb-services-cards","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group nb-gradient-section nb-services-cards">
```

(Two changes: `"className"` block attribute gains `" nb-services-cards"` and the rendered `<div class>` gains `" nb-services-cards"`. Both must change — the block attribute is what the editor reads, the rendered class is what CSS targets.)

- [ ] **Step 2: Insert the Tune column between Build and Manage**

Find the closing of the Build column (currently line 33 — the `<!-- /wp:column -->` immediately after the `</div>` that closes the Build column's div). Immediately after that line, and before the line `<!-- wp:column {"className":"nb-glass nb-reveal"} -->` that opens the Manage column, insert this block:

```html
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <div class="nb-icon-badge">🎛️</div>
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"},"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
      <h3>Tune</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Bring your existing site up to speed. A fixed-price tune-up that gets your performance and SEO into the band Google rewards — without rebuilding anything.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
```

Indent matches the surrounding columns (4 spaces).

- [ ] **Step 3: Add the tablet 4-column-wrap CSS rule**

In `wp-content/themes/newblood/assets/css/patterns.css`, find the existing rule at approximately lines 190-195:

```css
/* Portfolio grid asymmetric layout */
@media (max-width: 768px) {
  .nb-stagger > .wp-block-column {
    flex-basis: 100% !important;
  }
}
```

Immediately after that closing brace (so before the `/* ===== Showcase Portfolio Grid ===== */` block comment), insert:

```css
/* Services cards — wrap to 2x2 at tablet width when there are 4 cards */
@media (max-width: 1024px) and (min-width: 769px) {
  .nb-services-cards .wp-block-columns.nb-stagger {
    flex-wrap: wrap !important;
  }
  .nb-services-cards .wp-block-columns.nb-stagger > .wp-block-column {
    flex-basis: calc(50% - 0.75rem) !important;
    flex-grow: 0 !important;
  }
}
```

This is scoped to `.nb-services-cards` so it only affects the four-card layout. Other patterns using `.nb-stagger` (about-story stats, pricing-table tiers, how-it-works movements) keep their existing behavior at tablet width.

- [ ] **Step 4: PHP syntax check**

Run: `php -l wp-content/themes/newblood/patterns/services-cards.php`
Expected: `No syntax errors detected in wp-content/themes/newblood/patterns/services-cards.php`

- [ ] **Step 5: Block-comment balance check**

Run:
```bash
grep -c '<!-- wp:column ' wp-content/themes/newblood/patterns/services-cards.php
grep -c '<!-- /wp:column -->' wp-content/themes/newblood/patterns/services-cards.php
```

Expected: first command returns `8` (4 opens × 2, since `wp:column` substring matches both opens and closes when grepping with the trailing space pattern — see how-it-works's similar verification earlier in project); second command returns `4` (4 closes). The math: 8 total occurrences − 4 closes = 4 opens.

Also run:
```bash
grep -c 'wp:columns' wp-content/themes/newblood/patterns/services-cards.php
```

Expected: `2` (one open, one close — the outer columns block is unchanged).

- [ ] **Step 6: Visual verification — desktop**

Load `http://newblood.test/` in a browser at desktop width (≥1024px wide).

Verify:
- Four cards visible in `services-cards` section: Build (⚡), Tune (🎛️), Manage (🛡️), Empower (🤝) in that order, left to right
- Each card's icon, heading, and body text render correctly
- Cards are roughly equal width
- Existing scroll-reveal / stagger animation runs across all four cards on first paint
- No visual regressions on the surrounding sections (hero above, social-proof below, etc.)

- [ ] **Step 7: Visual verification — tablet and mobile**

Resize the browser to:
- **Tablet width (~900px):** four cards should wrap to **2×2** (two cards per row, two rows). Cards are wider than at desktop. No orphan card on a row of its own.
- **Mobile width (~375px):** four cards stack to **single column**, one card per row. Existing 768px rule handles this.

Also verify on the same widths that:
- `pricing-table` (Starter / Business / Reach — three tiers) still displays correctly at all widths and was NOT affected by the new CSS rule (the rule is scoped to `.nb-services-cards`, but verify).
- `how-it-works` (four movements) still displays as before — unchanged behavior.

- [ ] **Step 8: Commit**

```bash
git add wp-content/themes/newblood/patterns/services-cards.php wp-content/themes/newblood/assets/css/patterns.css
git commit -m "feat(services): add Tune card to services-cards with tablet 2x2 wrapping"
```

---

## Task 2: services-detail — add Tune detail section between Build and Manage

**Files:**
- Modify: `wp-content/themes/newblood/patterns/services-detail.php` (insert new content after the Build section's closing `</div><!-- /wp:group -->` around line 72, and before the existing `<!-- wp:separator -->` that introduces the Manage section around line 74)

The existing structure is:

```
[outer alignfull group open]
  [Build section]
  [separator]
  [Manage section]
  [separator]
  [Empower section]
[outer alignfull group close]
```

After this task:

```
[outer alignfull group open]
  [Build section]
  [separator]                        ← existing
  [Tune section]                     ← NEW
  [separator]                        ← NEW
  [Manage section]
  [separator]
  [Empower section]
[outer alignfull group close]
```

The new Tune section reuses the *existing separator* between Build and Manage (no need to add a separator after Build). A *new* separator goes after the Tune section to maintain the visual rhythm before Manage.

- [ ] **Step 1: Read the file to confirm current line numbers**

Run: `grep -n 'wp:separator\|<h2>' wp-content/themes/newblood/patterns/services-detail.php`

Expected output (line numbers may shift slightly):
```
24:      <h2>Build</h2>
74:  <!-- wp:separator ...
75:  <hr ...
76:  <!-- /wp:separator -->
90:      <h2>Manage</h2>
140:  <!-- wp:separator ...
141:  <hr ...
142:  <!-- /wp:separator -->
156:      <h2>Empower</h2>
```

The first separator (lines 74-76) sits between Build (ends ~72) and Manage (begins ~78). Insert the Tune section + a new separator immediately after that first separator (so after line 76) and before the Manage section opens.

- [ ] **Step 2: Insert the Tune section + a new separator**

Find this block in `wp-content/themes/newblood/patterns/services-detail.php`:

```html
  <!-- wp:separator {"opacity":"css","style":{"color":{"background":"rgba(255,255,255,0.06)"}}} -->
  <hr class="wp-block-separator has-css-opacity has-background"/>
  <!-- /wp:separator -->

  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}}} -->
  <div class="wp-block-group nb-reveal">
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
    <div class="wp-block-group">
      <!-- wp:group {"style":{"spacing":{"margin":{"right":"var:preset|spacing|40"}}}} -->
      <div class="wp-block-group">
        <div class="nb-icon-badge" style="width:56px;height:56px;font-size:1.75rem">🛡️</div>
```

(This is the separator between Build and Manage, immediately followed by the Manage section's opening.)

Replace it with:

```html
  <!-- wp:separator {"opacity":"css","style":{"color":{"background":"rgba(255,255,255,0.06)"}}} -->
  <hr class="wp-block-separator has-css-opacity has-background"/>
  <!-- /wp:separator -->

  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}}} -->
  <div class="wp-block-group nb-reveal">
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
    <div class="wp-block-group">
      <!-- wp:group {"style":{"spacing":{"margin":{"right":"var:preset|spacing|40"}}}} -->
      <div class="wp-block-group">
        <div class="nb-icon-badge" style="width:56px;height:56px;font-size:1.75rem">🎛️</div>
      </div>
      <!-- /wp:group -->
      <!-- wp:group {"layout":{"type":"constrained"}} -->
      <div class="wp-block-group">
        <!-- wp:heading {"style":{"typography":{"fontSize":"1.5rem"}}} -->
        <h2>Tune</h2>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"textColor":"text-secondary","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
        <p class="has-text-secondary-color">Existing site, sharper performance.</p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">Most WordPress sites running heavy commercial themes ship 200-400 KB of CSS and 100-200 KB of JS site-wide, even on pages that need almost none of it. The result is mobile load times Google notices — and so do your visitors. We tune what's there: diagnostic first, then a focused 5-7 hour engagement against the small set of well-known offenders that produce the biggest gains.</p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">We don't rebuild your theme. We don't add more plugins. We don't change your editorial workflow. Every change ships as one focused pull request, organized by phase, with before-and-after PageSpeed screenshots so you can see the move.</p>
    <!-- /wp:paragraph -->
    <!-- wp:group {"className":"nb-case-highlight","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
    <div class="wp-block-group nb-case-highlight">
      <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
      <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
          <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem","lineHeight":"2"}}} -->
          <ul class="has-text-body-color">
            <li>Diagnostic SEO + PageSpeed audit</li>
            <li>LCP image conversion (fetchpriority, srcset)</li>
            <li>Plugin asset cleanup (conditional dequeue)</li>
            <li>Selective CSS deferral</li>
          </ul>
          <!-- /wp:list -->
        </div>
        <!-- /wp:column -->
        <!-- wp:column -->
        <div class="wp-block-column">
          <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem","lineHeight":"2"}}} -->
          <ul class="has-text-body-color">
            <li>JavaScript audit + targeted fixes</li>
            <li>Search Console + sitemap submission</li>
            <li>Per-phase before/after PageSpeed screenshots</li>
            <li>Handover doc with extensible kill-list</li>
          </ul>
          <!-- /wp:list -->
        </div>
        <!-- /wp:column -->
      </div>
      <!-- /wp:columns -->
    </div>
    <!-- /wp:group -->
    <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.8125rem","fontStyle":"italic","lineHeight":"1.7"},"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
    <p class="has-text-muted-color"><em>What we don't promise: a specific PageSpeed score. Real-world variance is ±5 points run-to-run. Mobile 70+ is reasonable; Mobile 90+ needs the Tune Plus engagement.</em></p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->

  <!-- wp:separator {"opacity":"css","style":{"color":{"background":"rgba(255,255,255,0.06)"}}} -->
  <hr class="wp-block-separator has-css-opacity has-background"/>
  <!-- /wp:separator -->

  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}}} -->
  <div class="wp-block-group nb-reveal">
    <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
    <div class="wp-block-group">
      <!-- wp:group {"style":{"spacing":{"margin":{"right":"var:preset|spacing|40"}}}} -->
      <div class="wp-block-group">
        <div class="nb-icon-badge" style="width:56px;height:56px;font-size:1.75rem">🛡️</div>
```

The replacement preserves the existing separator (unchanged), adds the entire Tune section, adds a new separator, then opens the existing Manage section starting at the icon-badge for 🛡️ (everything from there to the end of the file is unchanged).

The Tune section structure mirrors Build/Manage/Empower exactly: outer `nb-reveal` group → inner flex group with icon-on-left + heading-on-right → two body paragraphs → `nb-case-highlight` group with two-column list → italic "What we don't promise" line.

- [ ] **Step 3: PHP syntax check**

Run: `php -l wp-content/themes/newblood/patterns/services-detail.php`
Expected: `No syntax errors detected in wp-content/themes/newblood/patterns/services-detail.php`

- [ ] **Step 4: Block-comment balance check**

Run:
```bash
grep -c '<!-- wp:group ' wp-content/themes/newblood/patterns/services-detail.php
grep -c '<!-- /wp:group -->' wp-content/themes/newblood/patterns/services-detail.php
```

Expected: both numbers equal. Before this change, the file had a balanced count for `wp:group`; the Tune section adds 4 opens and 4 closes (outer `nb-reveal`, flex group, icon group, heading group) — still balanced.

Also run:
```bash
grep -c '<!-- wp:separator ' wp-content/themes/newblood/patterns/services-detail.php
grep -c '<!-- /wp:separator -->' wp-content/themes/newblood/patterns/services-detail.php
```

Expected: both numbers equal. Before: 2 separators. After: 3 separators. So both should return `3`.

If any of these counts mismatch, an edit broke a block-comment pair. Investigate before proceeding.

- [ ] **Step 5: Visual verification**

Load the page that embeds `services-detail` (find via WP admin → Pages → Services). Likely URL: `http://newblood.test/services`.

Verify:
- Four detail blocks render in order: **Build → Tune → Manage → Empower**
- The Tune block shows: 🎛️ icon at left, "Tune" h2 + "Existing site, sharper performance." sub-headline at right, two body paragraphs, two-column "what's included" list, italic "What we don't promise" line at bottom
- Separators between each section (3 separators total: Build|Tune, Tune|Manage, Manage|Empower)
- The two-column list inside Tune renders side-by-side at desktop width
- No visual regressions on Build, Manage, or Empower blocks

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/newblood/patterns/services-detail.php
git commit -m "feat(services): add Tune detail block between Build and Manage"
```

---

## Task 3: pricing-table — add "Already have a site?" Tune section

**Files:**
- Modify: `wp-content/themes/newblood/patterns/pricing-table.php` (insert new content immediately before the closing `</div>` that closes the outer `nb-pricing` group, around line 104)
- Modify: `wp-content/themes/newblood/assets/css/patterns.css` (add a `.nb-tune-pricing` width-cap rule near the existing `.nb-pricing` rules)

The existing structure is:

```
[outer alignfull nb-pricing group open]
  [header group with lede]
  [columns block: 3 tier cards]
[outer group close]
```

After this task:

```
[outer alignfull nb-pricing group open]
  [header group with lede]
  [columns block: 3 tier cards]
  [separator]                              ← NEW
  [Tune mini-header group]                 ← NEW
  [columns block: 2 Tune cards]            ← NEW
[outer group close]
```

The new content lives *inside* the existing `nb-pricing` outer group so it inherits the same alignfull treatment and the 1500px max-width. The two Tune cards get an explicit width cap via a `.nb-tune-pricing` class so they don't stretch as wide as the three-tier row above.

- [ ] **Step 1: Insert the new section before the outer group close**

In `wp-content/themes/newblood/patterns/pricing-table.php`, find the closing of the outer pricing group at the end of the file:

```html
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
```

The first `</div>` closes the three-tier `wp-block-columns`. The `<!-- /wp:columns -->` closes the columns block-comment. The second `</div>` closes the outer `nb-pricing` group. The final `<!-- /wp:group -->` closes the outer group's block-comment.

Replace those four lines with:

```html
  </div>
  <!-- /wp:columns -->

  <!-- wp:separator {"opacity":"css","style":{"color":{"background":"rgba(255,255,255,0.06)"},"spacing":{"margin":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|60"}}}} -->
  <hr class="wp-block-separator has-css-opacity has-background"/>
  <!-- /wp:separator -->

  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
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
      <p class="has-text-muted-color">Tune plus critical-CSS extraction for clients targeting Mobile 90+. Stretch engagement.</p>
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
</div>
<!-- /wp:group -->
```

The structure: separator → centered "Already have a site?" mini-header → 2-column row with Tune and Tune Plus cards using the same `.nb-glass.nb-reveal` styling as the build tiers above. The `nb-tune-pricing` class on the columns lets CSS cap the row's width.

- [ ] **Step 2: Add CSS width-cap for the Tune pricing row**

In `wp-content/themes/newblood/assets/css/patterns.css`, find the existing `.nb-pricing` rules (currently lines 141-153, ending with the `.nb-pricing .wp-block-column.nb-glass { padding: 2.5rem !important; }` rule).

Immediately after the closing brace of that block (so before the `/* Testimonial section */` comment), insert:

```css
/* Tune pricing row — narrower than the 1500px three-tier row */
.nb-pricing .nb-tune-pricing.wp-block-columns {
  max-width: 900px !important;
  margin-left: auto;
  margin-right: auto;
}
```

The `!important` is needed to override the broader `.nb-pricing .nb-stagger.wp-block-columns { max-width: 1500px !important }` rule a few lines above. With this cap, the two Tune cards each render at roughly 420px wide on desktop — visual proportion that matches the build-tier cards above instead of stretching to the full 720px each.

- [ ] **Step 3: PHP syntax check**

Run: `php -l wp-content/themes/newblood/patterns/pricing-table.php`
Expected: `No syntax errors detected in wp-content/themes/newblood/patterns/pricing-table.php`

- [ ] **Step 4: Block-comment balance check**

Run:
```bash
grep -c '<!-- wp:column ' wp-content/themes/newblood/patterns/pricing-table.php
grep -c '<!-- /wp:column -->' wp-content/themes/newblood/patterns/pricing-table.php
```

Expected: first command returns `10` (5 opens × 2 = 10 total occurrences of substring); second command returns `5` (closes). Math: 5 opens + 5 closes = balanced.

(Before this change, the file had 3 column opens / 3 closes for Starter/Business/Reach. After: 5 opens / 5 closes — added Tune and Tune Plus.)

Also run:
```bash
grep -c 'wp:columns' wp-content/themes/newblood/patterns/pricing-table.php
```

Expected: `4` (2 opens + 2 closes — the original three-tier columns block plus the new two-card columns block).

- [ ] **Step 5: Visual verification — desktop**

Load `http://newblood.test/pricing` in a browser at desktop width.

Verify:
- The existing three-tier row renders unchanged at the top (Starter / Business / Reach with $3,500 / $5,000 / "Let's Talk").
- A separator line appears below the three-tier row.
- A centered "Already have a site?" / "Tune it instead." section follows.
- Two side-by-side cards render below: **Tune** ($2,000) and **Tune Plus** ($4,500) — both styled identically to the build-tier cards (glass effect, 2rem padding).
- The Tune cards row is **narrower** than the build-tier row above (capped at 900px vs 1500px) — visually distinct.
- "Get in touch" button on each card links to `/contact`.
- No visual regressions on the existing three-tier row.

- [ ] **Step 6: Visual verification — mobile**

Resize the browser to mobile width (~375px).

Verify:
- The three build tiers stack to single column.
- The "Already have a site?" header reads cleanly.
- The two Tune cards stack to single column below the build tiers.
- No horizontal scroll, no cramped text, no overflowing buttons.

- [ ] **Step 7: Commit**

```bash
git add wp-content/themes/newblood/patterns/pricing-table.php wp-content/themes/newblood/assets/css/patterns.css
git commit -m "feat(pricing): add Already have a site? Tune section to pricing-table"
```

---

## Task 4: Cross-page QA + final verification

**Files:** none unless a regression is found.

After the three pattern commits land, run a focused QA pass to make sure nothing else regressed.

- [ ] **Step 1: Browse all key pages**

Visit each URL on the local site and skim for issues:

- `http://newblood.test/` (homepage — services-cards now has 4 cards)
- `http://newblood.test/about`
- `http://newblood.test/services` (services-detail now has 4 detail blocks; services-cards may also appear here depending on page composition)
- `http://newblood.test/work` (or `/portfolio` — verify the portfolio-grid pattern still renders)
- `http://newblood.test/pricing` (pricing-table now has the Tune section appended)
- `http://newblood.test/contact`
- One blog post or case study (any single-post template)

For each, look for:
- Any broken layout, overflowing content, or missing elements
- Any spacing regressions around the modified sections
- Scroll-reveal animations still firing on the modified patterns

- [ ] **Step 2: Check responsive breakpoints on the homepage and pricing page**

Resize to:
- **Tablet (~900px width):** services-cards should be 2×2; pricing-table three tiers should display side-by-side or wrap depending on existing CSS; Tune two-card row should display side-by-side or wrap.
- **Mobile (~375px width):** all multi-column sections should collapse to single column.

- [ ] **Step 3: Final commit if any small fixes were needed**

If any small CSS or markup adjustments are needed (e.g., a spacing tweak, a missing class), commit them as a single follow-up:

```bash
git add wp-content/themes/newblood/...
git commit -m "fix(services|pricing): post-Tune QA cleanup"
```

If nothing needs fixing, skip this step.

---

## Final Verification Checklist

After all tasks, confirm before declaring the plan complete:

- [ ] All three pattern files pass `php -l`.
- [ ] `services-cards.php`, `services-detail.php`, and `pricing-table.php` have balanced Gutenberg block comments.
- [ ] Four cards render in services-cards in order: Build → Tune → Manage → Empower.
- [ ] Four detail blocks render in services-detail in the same order.
- [ ] `/pricing` shows three build tiers + the new "Already have a site?" Tune section with two cards.
- [ ] All four icons render correctly (⚡ 🎛️ 🛡️ 🤝).
- [ ] At tablet width, services-cards wraps cleanly to 2×2 (no orphan cards).
- [ ] At mobile width, all multi-column sections stack to single column.
- [ ] No regressions on portfolio-grid, how-it-works, or any other section.
- [ ] Three commits exist on `feature/redesign`, one per task (Task 4 may produce a fourth small commit if QA found anything).

---

## Notes for the Executing Engineer

- **Lineup order is canonical.** Build → Tune → Manage → Empower must match across services-cards and services-detail. If you find yourself inserting Tune in a different position (e.g., after Empower because the existing structure made it easier), stop and re-read the spec.
- **Don't change the services-cards H2** — *"Built to last. Kept in tune. Yours to run."* — the spec deliberately leaves it alone. With the new Tune card, "Kept in tune" gains a second meaning that ties the headline to the new service.
- **Class-based scoping.** The new tablet CSS rule is scoped to `.nb-services-cards`. Don't accidentally drop the scope and apply it to `.nb-stagger` globally — other patterns rely on the existing 3-column behavior.
- **Pricing CSS uses `!important` deliberately.** The base `.nb-pricing .nb-stagger.wp-block-columns { max-width: 1500px !important }` rule will fight any non-!important override. Keep `!important` on the new `.nb-tune-pricing` rule.
- **Cache-busting is already wired.** filemtime-based versioning was added in commit `4b1fdd5e`, so a normal browser reload picks up CSS changes — no hard-refresh needed.
- **Auto mode is on.** The user expects continuous execution. After each task commit, move to the next task. Pause only if a verification fails repeatedly or you encounter a true ambiguity not covered in this plan.
