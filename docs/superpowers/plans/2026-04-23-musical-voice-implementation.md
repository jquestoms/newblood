# Musical / Compositional Voice Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Apply the approved musical/compositional voice from the design spec to newblood.com by rewriting copy in three WordPress pattern files: hero, about-story, and how-it-works.

**Architecture:** This is a copy/markup change in WordPress block theme pattern files. Each file contains PHP with Gutenberg block comments (`<!-- wp:... -->`) wrapping HTML. No application logic changes. The how-it-works pattern gets a structural change (3 columns → 4 columns) in addition to copy.

**Tech Stack:** WordPress block theme (FSE), PHP pattern files, Gutenberg block markup. Local dev via Laravel Herd (typical URL: `http://newblood.test`).

**Spec:** `docs/superpowers/specs/2026-04-23-musical-voice-design.md`

---

## Context the Engineer Needs

**What a WP block pattern file looks like.** A `.php` file under `patterns/` with a docblock header (Title, Slug, Categories, Description) followed by HTML with Gutenberg block comments. The block comments are meaningful — they're how WordPress parses the file into editable blocks. Do not strip them. Pairs of `<!-- wp:foo {...} -->` and `<!-- /wp:foo -->` must stay balanced. Self-closing blocks use `<!-- wp:foo /-->`.

**Existing design accents.** The h1 in hero.php uses a green period via `<span style="color:#4ade80">.</span>`. H2 in about-story.php uses the same accent. H3 subheadings do not. Preserve this convention in new copy.

**Verification strategy.** There are no unit tests for copy changes in a WP theme. Verification is (a) `php -l` for syntax, (b) load the page in a browser and read the copy, (c) for layout changes, check desktop + mobile breakpoints.

**Finding which URL renders each pattern.** `front-page.html` references the hero pattern directly. `about-story` and `how-it-works` are not referenced by any template file — they are inserted into specific Page post_content via the WordPress admin. Before each verification step, check the WP admin (Pages) to confirm which page embeds the pattern. Typical placements: `about-story` → `/about`, `how-it-works` → homepage or `/services`, but **verify, don't assume**.

---

## File Structure

Files modified (no new files created):

1. `wp-content/themes/newblood/patterns/hero.php` — label, headline, subtitle copy
2. `wp-content/themes/newblood/patterns/about-story.php` — final section only (heading + 2 paragraphs + 1 new paragraph)
3. `wp-content/themes/newblood/patterns/how-it-works.php` — full section rewrite, 3 columns → 4 columns

Files untouched:
- `templates/*.html` — no template changes needed (patterns are referenced by slug)
- `assets/css/*` — existing column styles handle 4 columns without CSS changes (wp-block-columns auto-distributes; stacks at mobile breakpoints)
- Other pattern files — medium/low-concentration spots stay in current voice per the spec

---

## Task 1: Hero Copy Rewrite

**Files:**
- Modify: `wp-content/themes/newblood/patterns/hero.php:12-18`

The label paragraph, h1, and subtitle paragraph get new copy. The CTA group, gradient classes, structural blocks, and the green period span stay exactly as they are.

- [ ] **Step 1: Edit the label paragraph**

In `wp-content/themes/newblood/patterns/hero.php`, change line 12 from:

```html
  <p class="nb-label nb-hero-label">25+ years of web expertise, accelerated by AI</p>
```

to:

```html
  <p class="nb-label nb-hero-label">25+ years of craft, tuned by modern AI</p>
```

- [ ] **Step 2: Edit the headline**

In the same file, change line 15 from:

```html
  <h1 class="nb-hero-headline">Websites that reach further<span style="color:#4ade80">.</span></h1>
```

to:

```html
  <h1 class="nb-hero-headline">Websites with rhythm, range, and restraint<span style="color:#4ade80">.</span></h1>
```

- [ ] **Step 3: Edit the subtitle paragraph**

In the same file, change line 18 from:

```html
  <p class="nb-hero-body has-text-secondary-color">We combine decades of hands-on craft with cutting-edge AI to design and build websites that punch well above their weight — more creative, more technically advanced, and more memorable than the budget should allow.</p>
```

to:

```html
  <p class="nb-hero-body has-text-secondary-color">We pair decades of hands-on craft with modern AI workflows to build sites that are measured in every sense — more considered, more technically ambitious, and more memorable than the budget should allow.</p>
```

- [ ] **Step 4: PHP syntax check**

Run: `php -l wp-content/themes/newblood/patterns/hero.php`
Expected: `No syntax errors detected in wp-content/themes/newblood/patterns/hero.php`

- [ ] **Step 5: Visual verification in browser**

Load `http://newblood.test/` in a browser (or the user's Herd-configured local URL for the project).

Verify:
- Label reads: `25+ years of craft, tuned by modern AI`
- Headline reads: `Websites with rhythm, range, and restraint.` with green period
- Subtitle reads as rewritten
- CTA buttons ("See Our Work" / "View Pricing") still render and link correctly
- No visual regressions: gradient background, dot grid, and hero spacing look the same as before

Check both desktop and a narrow window (mobile width ~375px).

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/newblood/patterns/hero.php
git commit -m "feat(hero): apply musical voice to hero label, headline, subtitle"
```

---

## Task 2: About Story — "Composition, not configuration." Section

**Files:**
- Modify: `wp-content/themes/newblood/patterns/about-story.php:34-42`

Only the final section gets rewritten. The "25+ years of pushing what's possible on the web." h2, intro paragraphs, the "Fortune 500 craft. Right-sized budgets." h3 section, and the stats columns at the bottom all stay exactly as they are.

The existing section currently has an h3 and 2 paragraphs. The new version has an h3 and **3 paragraphs** — paragraph 3 is new.

- [ ] **Step 1: Replace the section heading**

In `wp-content/themes/newblood/patterns/about-story.php`, change line 35 from:

```html
  <h3>Where creativity meets code.</h3>
```

to:

```html
  <h3>Composition, not configuration.</h3>
```

- [ ] **Step 2: Replace the first paragraph of this section**

In the same file, change line 38 from:

```html
  <p class="has-text-body-color">Modern web development is at an exciting crossroads. The proven foundations of solid engineering — clean code, fast performance, accessible design — are now supercharged by AI-assisted workflows and creative coding techniques that were once out of reach for all but the largest agencies. Interactive 3D, generative visuals, physics-driven animation, custom integrations, bespoke tooling — these aren't premium upsells anymore. They're tools we reach for whenever a project calls for them.</p>
```

to:

```html
  <p class="has-text-body-color">A good website behaves a little like a good piece of music — proportion, phrasing, and restraint on the details that don't matter so the ones that do can land. It's not an accident, and it's not a template. It's arranged.</p>
```

- [ ] **Step 3: Replace the second paragraph of this section**

In the same file, change line 41 from:

```html
  <p class="has-text-body-color">We thrive at the intersection of engineering and creative ambition. Every project we take on is an opportunity to push what a business website can be — and deliver work that genuinely stands out in a sea of templates.</p>
```

to:

```html
  <p class="has-text-body-color">That kind of care used to be a premium. Twenty-five years of engineering, paired with modern AI workflows, is what makes it reachable again — even on a small-business budget. Interactive 3D, generative visuals, physics-driven animation, custom integrations: tools we reach for whenever a project calls for them, not features you're upsold.</p>
```

- [ ] **Step 4: Add a new third paragraph after the second**

Immediately after the closing `<!-- /wp:paragraph -->` that follows the second paragraph (and before the `<!-- wp:columns ... -->` block that opens the stats columns at line 44), insert this new block:

```html
  <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
  <p class="has-text-body-color">We build with the ear of a studio trained across more than one form. Not because a website is a song, but because the things that make one work are the same things that make the other work: pacing, proportion, and nothing unearned.</p>
  <!-- /wp:paragraph -->

```

(Note the trailing blank line — keep one blank line before `<!-- wp:columns ... -->` to match existing file formatting.)

- [ ] **Step 5: PHP syntax check**

Run: `php -l wp-content/themes/newblood/patterns/about-story.php`
Expected: `No syntax errors detected in wp-content/themes/newblood/patterns/about-story.php`

- [ ] **Step 6: Block comment balance check**

Run: `grep -cE '<!-- wp:paragraph' wp-content/themes/newblood/patterns/about-story.php; grep -cE '<!-- /wp:paragraph -->' wp-content/themes/newblood/patterns/about-story.php`

Expected: both numbers are equal (opening self-closing count matches closing count for paragraph blocks) — if they differ, an edit broke the block pairing. Same check applies to the `wp:heading` and `wp:group` blocks, though those weren't modified in this task.

- [ ] **Step 7: Visual verification in browser**

First, find which page embeds the `newblood/about-story` pattern:
- Open WP admin → Pages, look for a page with "About" in the title and check its block editor for the About Story pattern.
- If present, note its URL (commonly `/about`).

Load that URL in the browser and verify:
- The h3 reads: `Composition, not configuration.`
- Paragraph 1 reads as rewritten (begins "A good website behaves a little like a good piece of music…")
- Paragraph 2 reads as rewritten (begins "That kind of care used to be a premium.")
- Paragraph 3 reads as rewritten (begins "We build with the ear of a studio trained across more than one form.")
- The "25+ years of pushing what's possible on the web." h2 and the "Fortune 500 craft. Right-sized budgets." h3 section above are unchanged.
- The stats row below (25+ / Custom / 100%) still renders correctly.
- No visual regressions in spacing between paragraphs or into the stats row.

- [ ] **Step 8: Commit**

```bash
git add wp-content/themes/newblood/patterns/about-story.php
git commit -m "feat(about): reframe creative section as 'Composition, not configuration'"
```

---

## Task 3: How It Works — Four Movements

**Files:**
- Modify: `wp-content/themes/newblood/patterns/how-it-works.php` (full replace of body)

This is the largest change: section heading, lede, and three columns become four. The outer `wp-block-group` with `nb-gradient-section` wrapper stays, and the `nb-stagger` / `nb-reveal` classes are preserved so existing scroll-reveal animations still apply.

- [ ] **Step 1: Replace the header group (label + heading, add lede)**

In `wp-content/themes/newblood/patterns/how-it-works.php`, the current header group spans lines 11-20:

```html
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">How it works</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>Three simple steps</h2>
    <!-- /wp:heading -->
  </div>
  <!-- /wp:group -->
```

Replace it with (label unchanged, h2 rewritten, new lede paragraph added):

```html
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">How it works</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>How we arrange a project</h2>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
    <p class="has-text-body-color">Four movements, one score. Every project is different, but the shape is always the same — listen first, settle on the arrangement, build it well, send it out to meet real people.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
```

Two things changed besides copy: (a) a `contentSize` of `720px` is added to the constrained layout so the lede paragraph has the same readable measure as the about-story body, and (b) a new paragraph block for the lede was inserted after the h2.

- [ ] **Step 2: Replace the three-column `wp:columns` block with four movements**

The current columns block spans lines 21-63. Replace the entire `<!-- wp:columns ... -->` through matching `<!-- /wp:columns -->` (inclusive) with the four-movement version below.

Key differences from the current 3-column structure:
- Four columns instead of three.
- Each column keeps the accent-colored number (1/2/3/4 instead of 1/2/3).
- Each column keeps `nb-reveal` and sits inside `nb-stagger` so existing staggered animations continue to work on four items.
- Movement titles replace step titles.

Replacement block:

```html
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"className":"nb-reveal"} -->
    <div class="wp-block-column nb-reveal" style="text-align:center">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","fontWeight":"800"}},"textColor":"accent"} -->
      <p class="has-accent-color">1</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Discovery</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">We listen first. Who you serve, what they need, where you're trying to go — this is the tuning step, and skipping it is how sites end up sounding like everyone else's.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-reveal"} -->
    <div class="wp-block-column nb-reveal" style="text-align:center">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","fontWeight":"800"}},"textColor":"accent"} -->
      <p class="has-accent-color">2</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Composition</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">With the brief in hand, we design the arrangement — structure, voice, visual system, key moments. Nothing loud without a reason, nothing quiet without intent.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-reveal"} -->
    <div class="wp-block-column nb-reveal" style="text-align:center">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","fontWeight":"800"}},"textColor":"accent"} -->
      <p class="has-accent-color">3</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Build</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">The arrangement becomes a real, fast, accessible site. Clean code, considered performance, content you can actually manage. This is where craft shows.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-reveal"} -->
    <div class="wp-block-column nb-reveal" style="text-align:center">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","fontWeight":"800"}},"textColor":"accent"} -->
      <p class="has-accent-color">4</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Launch</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Launch is the downbeat — not the final note. We stay on to refine, watch how the site meets real traffic, and keep it in tune. You own the site and your content from day one; we handle hosting, security, and updates behind the scenes.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
```

- [ ] **Step 3: PHP syntax check**

Run: `php -l wp-content/themes/newblood/patterns/how-it-works.php`
Expected: `No syntax errors detected in wp-content/themes/newblood/patterns/how-it-works.php`

- [ ] **Step 4: Block comment balance check**

Run:
```bash
grep -cE '<!-- wp:column ' wp-content/themes/newblood/patterns/how-it-works.php
grep -cE '<!-- /wp:column -->' wp-content/themes/newblood/patterns/how-it-works.php
```

Expected: both numbers are `4` (four `wp:column` opens, four `wp:column` closes). If they differ, an edit broke pairing.

Also run:
```bash
grep -cE '<!-- wp:columns ' wp-content/themes/newblood/patterns/how-it-works.php
grep -cE '<!-- /wp:columns -->' wp-content/themes/newblood/patterns/how-it-works.php
```

Expected: both numbers are `1`.

- [ ] **Step 5: Visual verification — desktop**

First, find which page embeds the `newblood/how-it-works` pattern:
- Check WP admin → Pages for a page that embeds it (commonly the homepage or a services-adjacent page).
- If the homepage's `front-page.html` does not reference it (confirmed: it does not, as of this spec), the pattern is embedded as a Page block somewhere — find the URL.

Load that URL on desktop (≥1024px wide) and verify:
- Label: `How it works`
- H2: `How we arrange a project`
- Lede paragraph reads as rewritten.
- Four columns visible side by side, numbered 1 through 4.
- Movement titles: `Discovery`, `Composition`, `Build`, `Launch`.
- Each movement body reads as drafted.
- Columns are evenly distributed; spacing between columns looks right (no crowding, no empty gaps).
- The scroll-reveal / stagger animation runs across all four columns on first paint.

- [ ] **Step 6: Visual verification — mobile and tablet**

Resize the browser to tablet width (~768px) and mobile width (~375px).

Verify:
- At tablet width, columns either stay in 4-across or wrap to 2-across × 2-rows cleanly — whichever the existing column styles produce for four children. Either is acceptable as long as nothing overflows or breaks alignment.
- At mobile width, columns stack vertically (single column), one movement per row.
- Text wraps cleanly; no horizontal scroll.
- The numbered accents still render in the accent color.

If column wrapping looks wrong at tablet width (e.g., 3 + 1 orphan), note it but do not fix in this task — flag for follow-up CSS tuning.

- [ ] **Step 7: Commit**

```bash
git add wp-content/themes/newblood/patterns/how-it-works.php
git commit -m "feat(process): restructure as four movements with musical voice"
```

---

## Task 4: Full-Site QA Read-Through

**Files:** none edited in this task unless specific conflicts require a fix. This task is a bounded review pass, not open-ended copy editing.

- [ ] **Step 1: Load each public page and skim for tone conflicts**

Visit the homepage, About, Services, Pricing, Work/Portfolio, and Contact pages at the local URL. For each page, skim for:
- Phrases that now feel out of tune with the new voice (most likely: any remaining "fast," "in days," "quick turnaround" language that contradicts the creative-ambition-over-speed direction).
- Section labels that duplicate the new voice awkwardly (e.g., two sections on the same page both using "arrange").
- Orphaned references to phrases we replaced (e.g., internal links or CTAs that still say "reach further").

- [ ] **Step 2: Produce a conflict list, do NOT auto-fix**

Write the list to the terminal or a scratch note. For each item, include:
- File and line (use `grep -rn "<phrase>" wp-content/themes/newblood/patterns/`)
- Current copy
- Why it conflicts (one sentence)

Do not edit these files in this task. The spec's concentration map keeps medium-concentration spots in neutral voice; only flag items that actively contradict the new voice, not items that could theoretically be more musical.

- [ ] **Step 3: Present findings to the user**

Report the list and ask whether to handle any of them now, defer to a follow-up, or leave as-is. This is the only task in the plan that ends with a user decision rather than a commit.

---

## Final Verification Checklist

After Task 4, confirm the following before declaring the plan complete:

- [ ] All three pattern files pass `php -l`.
- [ ] All three pattern files have balanced Gutenberg block comments (opens = closes for every block type modified).
- [ ] Hero, about-story final section, and how-it-works render correctly at desktop and mobile widths on the local site.
- [ ] Scroll-reveal / stagger animations still fire on modified sections.
- [ ] Three commits exist on the current branch, one per file modified, with messages matching the Task 1/2/3 commit steps.
- [ ] The green period accent still appears on the hero h1 and on the "25+ years of pushing what's possible on the web." h2 (unchanged).
- [ ] No regressions on pages that were not touched by this plan.

---

## Notes for the Executing Engineer

- **Do not rename pattern slugs.** The slugs (`newblood/hero`, `newblood/about-story`, `newblood/how-it-works`) are referenced by other parts of WordPress and breaking them will orphan content. Only body content changes.
- **Preserve existing classNames on blocks.** `nb-gradient-primary`, `nb-dot-grid`, `nb-hero-gradient`, `nb-reveal`, `nb-stagger`, `nb-label`, `nb-hero-headline`, `nb-hero-body`, `nb-hero-label`, `nb-hero-cta` are all wired up to CSS and scroll-reveal JS. Do not remove them.
- **The green-period span pattern is `<span style="color:#4ade80">.</span>`.** Preserve it on h1 and h2 only; new h3 headings do not get it (consistent with existing convention).
- **Cache caveat.** If changes don't appear in the browser, WordPress pattern files are read on each request, but any page caching plugin or CDN can hold stale output. If in doubt, hard-reload, and if the page still shows old content, check WP admin → Pages to ensure the pattern is actually embedded on the page you're viewing.
- **Auto mode is on.** The user expects continuous execution. After each task commit, move to the next task. Only pause for Task 4 Step 3 (user decision on the conflict list).
