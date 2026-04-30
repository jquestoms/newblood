# 57Cards Case Study + Portfolio Update — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Publish a 57Cards case study at `/case-study-57cards/`, surface it from the existing portfolio showcase as a fourth card, and update the showcase H2 to accommodate Tune engagements alongside Build engagements.

**Architecture:** Three coordinated changes. Two are file edits to the WordPress theme repo (`patterns/portfolio-grid.php` for the showcase update, plus a new committed body-markup file at `docs/case-studies/57cards.html`). The third is a WP-CLI runtime operation that uploads the screenshot to the WP media library and creates the case-study page using the committed body markup as `post_content`. The case-study WordPress pattern (`patterns/case-study.php`) is reused unchanged — it provides the page-header at the top of every case study page.

**Tech Stack:** WordPress block theme (FSE), Gutenberg block markup, WP-CLI (with `php -d memory_limit=512M` per prior session's discovery). Local dev via Laravel Herd.

**Spec:** `docs/superpowers/specs/2026-04-30-57cards-case-study-design.md`

---

## Context the Engineer Needs

### How a WordPress case-study page is structured here

Every case study page in this theme has the same shape:

1. The page's WP `post_title` (e.g., "57Cards") drives the visible H1, rendered by the `newblood/page-header` pattern via `wp:post-title`.
2. The page's WP `post_excerpt` (e.g., "Performance · Tune Engagement") drives the small green label above the H1, rendered by `wp:post-excerpt`.
3. Everything below the page-header lives in `post_content` as Gutenberg block markup — headings, paragraphs, lists, tables, images, separators.
4. The `newblood/case-study` pattern is referenced at the top of `post_content` as `<!-- wp:pattern {"slug":"newblood/case-study"} /-->` to render the page-header section.

This plan creates one such page for 57Cards, populating both `post_excerpt` and `post_content` programmatically via WP-CLI.

### How images are referenced in this theme

Images are uploaded to the WP media library, get an integer attachment ID, and are referenced from block markup using both the rendered URL and the ID:

```html
<!-- wp:image {"id":1234,"sizeSlug":"large","linkDestination":"none","className":"nb-showcase-img"} -->
<figure class="wp-block-image size-large nb-showcase-img"><img src="https://newblood.test/wp-content/uploads/2026/04/57cards-website-scan.png" alt="..." class="wp-image-1234"/></figure>
<!-- /wp:image -->
```

The 57Cards screenshot is uploaded once and referenced in two places: the portfolio-grid card and the case study body. Same attachment ID in both.

### WP-CLI invocation

A previous session discovered that this WP install needs an explicit memory bump for WP-CLI:

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood <subcommand>
```

This wrapper is required for every WP-CLI command in the plan. Treat it as the canonical invocation.

### Verification approach

There are no traditional unit tests for content-only changes. Verification per task:

- `php -l <file>` for any PHP file edits
- `grep -c` for Gutenberg block-comment balance on edited PHP files
- `wp post list` / `wp post get` for WP-side verification of the created page
- `curl -s` of the rendered page URL to confirm content lands as expected
- Browser visual verification (deferred to the user)

### Date-stamped uploads directory

WordPress saves uploads under `wp-content/uploads/<year>/<month>/`. At the time of writing this plan, that's likely `wp-content/uploads/2026/04/`, but WP determines the path at upload time — the implementer should rely on the URL returned by `wp media import`, not assume a path.

---

## File Structure

| File | What changes |
|---|---|
| `wp-content/themes/newblood/patterns/portfolio-grid.php` | Update H2 to "Built and tuned with New Blood"; add a 4th secondary card for 57Cards |
| `docs/case-studies/57cards.html` *(NEW)* | Committed Gutenberg block markup for the case study `post_content`. Source of truth for what gets pushed to the DB. |
| `wp-content/uploads/<year>/<month>/57cards-website-scan.png` | Image uploaded by WP-CLI (not version-controlled — uploads directory is typically gitignored) |
| WP DB row in `wp_posts` for the case study page | Created by WP-CLI; not a file change |

`patterns/case-study.php` is not modified — it's reused unchanged as the page-header for the new page.

---

## Task 1: Upload image, update portfolio-grid

**Files:**
- Read: `/Users/jeremyoms/Herd/57cardsdev/57cards-website-scan.png` (source image, on disk)
- Modify: `wp-content/themes/newblood/patterns/portfolio-grid.php` (H2 at line 17 and add 4th card after line 114)

This task does the image upload and the portfolio-grid edit together because the portfolio-grid card markup needs the attachment ID + URL returned by the upload step. Ship as one commit.

- [ ] **Step 1: Upload the screenshot to the WP media library**

Run:

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood media import \
  /Users/jeremyoms/Herd/57cardsdev/57cards-website-scan.png \
  --title="57Cards homepage" \
  --alt="57Cards homepage with playing cards in a warm-lit setting" \
  --porcelain
```

Expected: a single integer (the attachment ID) printed to stdout. Capture it — it's needed in Step 3 below and in Task 2.

For example, if the command returns `6750`:

```bash
ATTACHMENT_ID=6750
```

- [ ] **Step 2: Capture the attachment URL**

Run:

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post get $ATTACHMENT_ID --field=guid
```

Expected: a URL like `http://newblood.test/wp-content/uploads/2026/04/57cards-website-scan.png`. Capture it:

```bash
ATTACHMENT_URL=http://newblood.test/wp-content/uploads/2026/04/57cards-website-scan.png
```

The exact year/month folder depends on WP's upload date logic at runtime; rely on what `--field=guid` returns.

- [ ] **Step 3: Update portfolio-grid.php H2 and add 4th card**

In `wp-content/themes/newblood/patterns/portfolio-grid.php`:

**Sub-edit A:** Line 17 — change the H2 from:

```html
    <h2>Built with New Blood</h2>
```

to:

```html
    <h2>Built and tuned with New Blood</h2>
```

**Sub-edit B:** Add a fourth card. The existing C.A. Lindman card closes at line 114 with:

```html
    </div>
    <!-- /wp:group -->

  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->
```

The first `</div>` and `<!-- /wp:group -->` close the C.A. Lindman card. The second `</div>` and `<!-- /wp:group -->` close the `nb-showcase-grid` container. The third `</div>` and `<!-- /wp:group -->` close the outer `nb-gradient-section`. Insert the new 57Cards card BETWEEN the close of the C.A. Lindman card and the close of `nb-showcase-grid`.

Replace those lines with (substituting `<ATTACHMENT_ID>` and `<ATTACHMENT_URL>` with the values captured in Steps 1-2):

```html
    </div>
    <!-- /wp:group -->

    <!-- wp:group {"className":"nb-showcase-card","layout":{"type":"constrained"}} -->
    <div class="wp-block-group nb-showcase-card">
      <!-- wp:group {"className":"nb-showcase-image","style":{"color":{"background":"#1f1814"}}} -->
      <div class="wp-block-group nb-showcase-image">
        <!-- wp:image {"id":<ATTACHMENT_ID>,"sizeSlug":"large","linkDestination":"none","className":"nb-showcase-img"} -->
        <figure class="wp-block-image size-large nb-showcase-img"><img src="<ATTACHMENT_URL>" alt="57Cards website homepage" class="wp-image-<ATTACHMENT_ID>"/></figure>
        <!-- /wp:image -->
      </div>
      <!-- /wp:group -->
      <!-- wp:group {"className":"nb-showcase-info","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}}} -->
      <div class="wp-block-group nb-showcase-info">
        <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1rem"}}} -->
        <h3>57Cards</h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"className":"nb-showcase-badge"} -->
        <p class="nb-showcase-badge">Performance · Tune Engagement</p>
        <!-- /wp:paragraph -->
        <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.8125rem"}}} -->
        <p class="has-text-muted-color">A working WooCommerce store, made fast without a rebuild.</p>
        <!-- /wp:paragraph -->
        <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|20"}}}} -->
        <p><a href="/case-study-57cards/" style="color:#4ade80;font-size:0.75rem;text-decoration:none">View Case Study →</a></p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:group -->

  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 4: PHP syntax check**

Run: `php -l wp-content/themes/newblood/patterns/portfolio-grid.php`
Expected: `No syntax errors detected in wp-content/themes/newblood/patterns/portfolio-grid.php`

- [ ] **Step 5: Block-comment balance check**

Run:

```bash
grep -c '<!-- wp:group ' wp-content/themes/newblood/patterns/portfolio-grid.php
grep -c '<!-- /wp:group -->' wp-content/themes/newblood/patterns/portfolio-grid.php
```

Expected: both numbers equal. The new 57Cards card adds 3 group opens (outer card, inner image, inner info) and 3 closes — still balanced.

- [ ] **Step 6: Verify the image is reachable via the WP frontend**

Run:

```bash
curl -sI "$ATTACHMENT_URL" | head -3
```

Expected: `HTTP/1.1 200 OK` (or `HTTP/2 200` depending on local server config) with `Content-Type: image/png`.

- [ ] **Step 7: Commit**

```bash
git add wp-content/themes/newblood/patterns/portfolio-grid.php
git commit -m "feat(portfolio): add 57Cards card and update showcase H2 for Tune engagements"
```

The image upload itself is not committed — `wp-content/uploads/` is typically gitignored. The reproducibility path for future re-runs is documented in the spec.

---

## Task 2: Generate the case study body markup file

**Files:**
- Create: `docs/case-studies/57cards.html` (new — committed source-of-truth for the WP page's `post_content`)

This file is the canonical version of what gets pushed to the WP database. If the database is ever wiped, this file plus Task 3's WP-CLI commands let the page be recreated faithfully. The file uses the attachment ID and URL captured in Task 1.

- [ ] **Step 1: Create the docs directory**

Run:

```bash
mkdir -p docs/case-studies
```

(If the directory already exists, this is a no-op.)

- [ ] **Step 2: Create `docs/case-studies/57cards.html` with the full Gutenberg block markup**

Write the file with the content below, **substituting `<ATTACHMENT_ID>` (e.g., `6750`) and `<ATTACHMENT_URL>` (e.g., `http://newblood.test/wp-content/uploads/2026/04/57cards-website-scan.png`) with the values captured in Task 1.**

```html
<!-- wp:pattern {"slug":"newblood/case-study"} /-->

<!-- wp:group {"align":"full","className":"nb-gradient-section nb-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"800px"}} -->
<div class="wp-block-group alignfull nb-gradient-section nb-reveal">

  <!-- wp:paragraph {"textColor":"text-secondary","style":{"typography":{"fontSize":"0.9375rem","lineHeight":"1.7"}}} -->
  <p class="has-text-secondary-color"><strong>Stack:</strong> WordPress · Porto child theme · WooCommerce · 50+ active plugins<br><strong>Engagement:</strong> Performance and SEO refresh — no platform migration</p>
  <!-- /wp:paragraph -->

  <!-- wp:image {"id":<ATTACHMENT_ID>,"sizeSlug":"large","linkDestination":"none","className":"nb-case-screenshot"} -->
  <figure class="wp-block-image size-large nb-case-screenshot"><img src="<ATTACHMENT_URL>" alt="57Cards homepage with playing cards in a warm-lit setting" class="wp-image-<ATTACHMENT_ID>"/></figure>
  <!-- /wp:image -->

  <!-- wp:heading -->
  <h2>What we were asked to do</h2>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>57Cards.com is a working e-commerce site with years of content, a thousand-plus product variants, real customer accounts, and an active order pipeline. The owner didn't want a rebuild. He wanted modern performance and a refreshed front-end without throwing away a stack that was working — just slower than it should be.</p>
  <!-- /wp:paragraph -->

  <!-- wp:paragraph -->
  <p>This is the situation most established WordPress sites land in eventually. A premium theme bundled with a page builder, a payment plugin here, a security plugin there, three commercial plugins for things the theme already does, and over time the site is shipping seventy-plus render-blocking assets to render a homepage. The instinct is "we need to redo this." The right move, almost always, is surgical.</p>
  <!-- /wp:paragraph -->

  <!-- wp:heading -->
  <h2>What changed</h2>
  <!-- /wp:heading -->

  <!-- wp:table -->
  <figure class="wp-block-table"><table><thead><tr><th>Metric</th><th>Before</th><th>After</th></tr></thead><tbody><tr><td>Mobile Performance</td><td>61</td><td><strong>72</strong></td></tr><tr><td>Desktop Performance</td><td>72</td><td><strong>96</strong></td></tr><tr><td>SEO</td><td>100</td><td>100</td></tr><tr><td>Mobile LCP</td><td>7.2s</td><td>5.1s</td></tr><tr><td>Render-blocking requests (homepage)</td><td>~71</td><td>~38</td></tr></tbody></table></figure>
  <!-- /wp:table -->

  <!-- wp:paragraph -->
  <p>About four hours of investigation, implementation, four PageSpeed measurement rounds, and production deploys. No theme rewrite. No new plugins. The same content, the same admin, the same SEO history.</p>
  <!-- /wp:paragraph -->

  <!-- wp:heading -->
  <h2>What we actually did</h2>
  <!-- /wp:heading -->

  <!-- wp:heading {"level":3} -->
  <h3>1. Conditional asset enqueue, per page</h3>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>WordPress's biggest performance liability is global plugin enqueue: every plugin loads its CSS and JS on every page, "just in case." We built seven conditional templates — homepage, about, games, FAQ, care, blog, single product, shop archive — and gave each one a dedicated <code>*-enqueue.php</code> file that runs at priority 9999, after Porto, WPBakery, and Slider Revolution have finished registering, and dequeues anything that isn't relevant.</p>
  <!-- /wp:paragraph -->

  <!-- wp:paragraph -->
  <p>A single dequeue file (<code>perf-dequeue.php</code>) strips ~40-50 render-blocking requests from the homepage alone:</p>
  <!-- /wp:paragraph -->

  <!-- wp:list -->
  <ul><li>WPBakery Page Builder front-end — we render with our own templates</li><li>Slider Revolution — not used outside the legacy homepage</li><li>WooCommerce front-end bundle on non-shop pages (cart, checkout, fragments, attribution, sourcebuster)</li><li>CleanTalk anti-spam, Mailchimp form CSS, fresh-framework, go_pricing, ShareThis — none present on the page they were loading on</li><li>Stripe Blocks checkout CSS, PayPal smart-button gateway CSS — gated to actual checkout pages</li></ul>
  <!-- /wp:list -->

  <!-- wp:heading {"level":3} -->
  <h3>2. Late-stage suppression for plugins that cheat</h3>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>Some plugins re-enqueue assets after <code>wp_enqueue_scripts</code> is over. WooCommerce Blocks' Stripe support and Slider Revolution's global handler both do this during <code>the_content</code>; standard dequeue doesn't catch them. We added a <code>style_loader_tag</code> filter that runs at print time and drops the <code>&lt;link&gt;</code> tag entirely. Belt and suspenders.</p>
  <!-- /wp:paragraph -->

  <!-- wp:heading {"level":3} -->
  <h3>3. Deferred non-critical CSS</h3>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>For stylesheets we couldn't fully drop — Porto's footer styles, contact widget, plugin styles bundled into the theme — we used the <code>media="print" onload="this.media='all'"</code> pattern to fetch them after first paint, with a <code>&lt;noscript&gt;</code> fallback for correctness. The browser stops blocking on them; the user gets pixels sooner.</p>
  <!-- /wp:paragraph -->

  <!-- wp:heading {"level":3} -->
  <h3>4. Honest font loading</h3>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>We trimmed the preloaded font set down to exactly what's used above the fold: the Porto icon font, Font Awesome solid, Font Awesome brands. We added preconnect hints for <code>fonts.googleapis.com</code> and <code>fonts.gstatic.com</code> so the Playfair Display + Inter request starts earlier. We tried deferring Google Fonts and immediately reverted it when CLS regressed — performance work without measurement is just guessing.</p>
  <!-- /wp:paragraph -->

  <!-- wp:heading {"level":3} -->
  <h3>5. Native LCP optimization</h3>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>The homepage hero is now a real <code>&lt;img fetchpriority="high"&gt;</code> with native <code>srcset</code> and <code>sizes</code>, replacing an earlier <code>&lt;link rel="preload"&gt;</code>. This lets the browser prioritize the image directly and lets WP Smush's CDN rewrite the URL consistently — preload hints don't always pick up CDN-rewritten URLs, which silently doubles the LCP fetch.</p>
  <!-- /wp:paragraph -->

  <!-- wp:heading {"level":3} -->
  <h3>6. The admin side: blocking outbound calls</h3>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>A separate <code>57cards-perf</code> plugin blocks eight known-slow outbound HTTP calls during <code>wp-admin</code> requests, identified via Query Monitor. The worst offenders were Porto's license check (60-second timeout), WPMUDEV Hub analytics (15s), and fresh-framework's plain-HTTP phone-home over three retries — about thirty seconds of cumulative dashboard hang. Editors get a snappy admin without losing license functionality; those endpoints are the banner endpoints, not the update endpoints.</p>
  <!-- /wp:paragraph -->

  <!-- wp:heading {"level":3} -->
  <h3>7. Surgical fixes for the side effects</h3>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>Aggressive dequeue creates new bugs. When we stripped <code>porto-header-shop</code> CSS on non-shop pages, the always-visible mini-cart icon broke because the <code>display:block</code> rule for <code>.minicart-icon</code> lived in that file. The fix wasn't to re-enqueue 20 KB of CSS — it was to inline the ~600 bytes of layout rules the cart icon actually needs. Net: still saving 95 percent of the bytes, no broken pixels.</p>
  <!-- /wp:paragraph -->

  <!-- wp:heading -->
  <h2>The philosophy</h2>
  <!-- /wp:heading -->

  <!-- wp:paragraph -->
  <p>You can ship modern performance from an aging WordPress install. You don't need a rebuild, you don't need a framework migration, you don't need to throw away a working WooCommerce store and the SEO history that's tied to it. What you need is:</p>
  <!-- /wp:paragraph -->

  <!-- wp:list -->
  <ul><li>A real measurement loop (Lighthouse, Query Monitor, Web Vitals) to find the actual costs</li><li>Conditional, scoped enqueue instead of global plugin sprawl</li><li>Inline critical CSS for what's above the fold; defer or drop the rest</li><li>A willingness to write 600 bytes of CSS instead of accepting 20 KB</li><li>Discipline about what you remove, with a verification step on the live site every time</li></ul>
  <!-- /wp:list -->

  <!-- wp:paragraph -->
  <p>This is how we keep proven WordPress sites fast, modern, and intact — without the rebuild.</p>
  <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
```

- [ ] **Step 3: Verify the markup is well-formed**

Run:

```bash
grep -c '<!-- wp:' docs/case-studies/57cards.html
grep -c '<!-- /wp:' docs/case-studies/57cards.html
```

The first count includes both opens and self-closing tags (e.g., `<!-- wp:pattern .../-->`). The second count is closes only. Specifically expected:

- `wp:pattern` appears once (self-closing — the `newblood/case-study` reference at the top)
- `wp:group` opens: 1 (the outer body wrapper)
- `wp:group` closes: 1
- `wp:paragraph` opens: ~16, closes: ~16 (all paragraphs balanced)
- `wp:heading` opens: ~12, closes: ~12 (mix of h2 and h3, balanced)
- `wp:list` opens: 2, closes: 2
- `wp:image` opens: 1, closes: 1
- `wp:table` opens: 1, closes: 1

Run a precise sanity check:

```bash
grep -c '<!-- wp:group' docs/case-studies/57cards.html  # Should be 1
grep -c '<!-- /wp:group -->' docs/case-studies/57cards.html  # Should be 1
grep -c '<!-- wp:paragraph' docs/case-studies/57cards.html  # Should equal closes count below
grep -c '<!-- /wp:paragraph -->' docs/case-studies/57cards.html
grep -c '<!-- wp:heading' docs/case-studies/57cards.html  # Should equal closes count below
grep -c '<!-- /wp:heading -->' docs/case-studies/57cards.html
grep -c '<!-- wp:list' docs/case-studies/57cards.html  # Should be 2
grep -c '<!-- /wp:list -->' docs/case-studies/57cards.html  # Should be 2
```

If any open/close pair mismatches, fix the markup before proceeding.

- [ ] **Step 4: Verify ATTACHMENT_ID and ATTACHMENT_URL are substituted**

Run:

```bash
grep -c '<ATTACHMENT_ID>' docs/case-studies/57cards.html
grep -c '<ATTACHMENT_URL>' docs/case-studies/57cards.html
```

Both should return `0`. If they return non-zero, the placeholders weren't substituted — substitute them with the actual values from Task 1 before committing.

- [ ] **Step 5: Commit**

```bash
git add docs/case-studies/57cards.html
git commit -m "feat(case-study): add 57Cards case study body markup"
```

---

## Task 3: Create the WP page via WP-CLI

**Files:** none modified. This task creates a row in the WP `wp_posts` table; nothing on disk changes.

This task is a runtime operation only. It uses the body file from Task 2 as `--post_content`, the page title and excerpt as plain WP-CLI arguments. There's nothing to commit at the end.

- [ ] **Step 1: Sanity-check the slug isn't already taken**

Run:

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post list --post_type=page --name=case-study-57cards --format=count
```

Expected: `0`. If the count is non-zero, a page with that slug already exists. Either delete it first (`wp post delete <ID> --force`) or update it via `wp post update`. For a clean v1 run, delete-and-recreate is simpler.

- [ ] **Step 2: Create the page**

Run (substituting the actual file path):

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post create \
  --post_type=page \
  --post_title="57Cards" \
  --post_name=case-study-57cards \
  --post_status=publish \
  --post_excerpt="Performance · Tune Engagement" \
  --post_content="$(cat /Users/jeremyoms/Herd/newblood/docs/case-studies/57cards.html)" \
  --porcelain
```

Expected: a single integer (the new page's post ID) printed to stdout. Capture it.

```bash
PAGE_ID=<returned-id>
```

- [ ] **Step 3: Verify the page is live**

Run:

```bash
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post get $PAGE_ID --field=post_status
```

Expected: `publish`.

Then verify the URL is reachable:

```bash
curl -sI "http://newblood.test/case-study-57cards/" | head -3
```

Expected: `HTTP/1.1 200 OK` (or `HTTP/2 200`) with `Content-Type: text/html`.

- [ ] **Step 4: Verify the rendered content includes key strings**

Run:

```bash
curl -s "http://newblood.test/case-study-57cards/" | grep -c "Modernizing 57Cards.com"
curl -s "http://newblood.test/case-study-57cards/" | grep -c "What we were asked to do"
curl -s "http://newblood.test/case-study-57cards/" | grep -c "Performance · Tune Engagement"
curl -s "http://newblood.test/case-study-57cards/" | grep -c "57cards-website-scan.png"
```

Expected:
- The title-area H1 string is "57Cards" (the page title), but the body has "Modernizing 57Cards.com" implicitly via the page title context. Actually — the spec frames the headline as `# Modernizing 57Cards.com without a rebuild`, but in this implementation the page-header pattern renders the page title ("57Cards") and the `post_excerpt` ("Performance · Tune Engagement") as the label. The "Modernizing 57Cards.com without a rebuild" framing isn't in the body — it's an editorial framing in the spec. **Don't grep for it — the body's first content is the Stack/Engagement meta paragraph.**
- "What we were asked to do" — should appear once (h2)
- "Performance · Tune Engagement" — should appear at least once (the post-excerpt label)
- "57cards-website-scan.png" — should appear once (the body image src)

- [ ] **Step 5: Final cross-link verification**

Run:

```bash
curl -s "http://newblood.test/work/" | grep -c "case-study-57cards"
```

Or if the portfolio-grid is rendered on a different page, substitute that URL. Expected: at least `1` — the 57Cards card's "View Case Study →" link points at `/case-study-57cards/`.

If portfolio-grid is rendered on the homepage (it's referenced from `front-page.html`), also run:

```bash
curl -s "http://newblood.test/" | grep -c "case-study-57cards"
```

Expected: at least `1`.

There's no final commit for this task — the WP page exists in the DB, not on disk.

---

## Final Verification Checklist

After all three tasks:

- [ ] `wp-content/themes/newblood/patterns/portfolio-grid.php` passes `php -l` and has balanced block comments.
- [ ] `docs/case-studies/57cards.html` is committed and contains the substituted attachment ID and URL.
- [ ] The 57Cards image is reachable at the WP-served URL.
- [ ] `/case-study-57cards/` returns 200 OK and renders the case study content with image, headings, table, and lists.
- [ ] The portfolio-grid shows four cards in order: Mike's Master Classes (featured) → Overhead Door → C.A. Lindman → 57Cards. Showcase H2 reads "Built and tuned with New Blood."
- [ ] Clicking "View Case Study →" on the 57Cards card navigates to the case study page.
- [ ] Two commits exist on `feature/redesign`:
  - `feat(portfolio): add 57Cards card and update showcase H2 for Tune engagements`
  - `feat(case-study): add 57Cards case study body markup`

---

## Notes for the Executing Engineer

- **Capture and reuse the attachment ID and URL.** They're needed in three places (portfolio-grid card, case-study body image, post markup). One upload, three references. If you re-run Task 1, you'll get a *different* attachment ID — make sure the body file and portfolio-grid use the same one.
- **WP-CLI memory limit.** Always invoke as `php -d memory_limit=512M /opt/homebrew/bin/wp ...`. The default 128M will fail with a fatal error in `class-wp-site-health.php`.
- **Don't commit the uploaded image file.** WordPress's `wp-content/uploads/` is typically gitignored and the image lives there. If you want the image in source control as a backup, the source already lives at `/Users/jeremyoms/Herd/57cardsdev/57cards-website-scan.png` — that's outside this repo and serves as the durable copy.
- **`<ATTACHMENT_URL>` formatting.** WP returns the URL with `http://newblood.test/...` on the local Herd setup. When this work is replayed on production (Nexcess), the URL will be `https://newblood.com/...`. The body file commits the local URL because that's what's true on Jeremy's dev environment. Production deploy will need a database search-replace (`wp search-replace`) — but that's a deploy-time concern, not a v1 implementation concern.
- **Auto mode is on.** Subagent-driven execution is the recommended path. Each task is bounded enough to fit cleanly into a single subagent's context. Don't parallelize Tasks 1, 2, 3 — they're tightly sequenced (Task 2 needs Task 1's IDs; Task 3 needs Task 2's file).
