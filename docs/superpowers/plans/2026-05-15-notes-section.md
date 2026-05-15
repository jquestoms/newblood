# Notes Section Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a `/notes/` editorial publication for newblood.com — image-led card grid index, refined single-post template, homepage hook, primary-nav integration, and services cross-linking — with a public launch gated behind a `NB_NOTES_PUBLIC` constant flip.

**Architecture:** WordPress block-theme additions on the existing `feature/redesign` branch. New `home.html` template renders the posts archive at `/notes/`. Four new block patterns (`notes-index`, `latest-note`, `more-notes`, `related-notes`) plus inline edits to `single.html`, `front-page.html`, `services-detail.php`, and `functions.php`. CSS additions to `patterns.css`. Launch gating via a PHP constant plus a body class plus CSS hide rules. SEO plumbing (meta description, Open Graph, Twitter Card, JSON-LD, RSS auto-discovery) added to `functions.php` since no SEO plugin is installed.

**Tech Stack:** WordPress 6.x block themes, PHP, HTML block markup, CSS. WP-CLI for content seeding and menu management. Verification via `php -l`, `grep -c` for block-comment balance, browser smoke checks at `http://newblood.test`.

**Spec:** `docs/superpowers/specs/2026-05-15-notes-section-design.md`

---

## Conventions used throughout this plan

- **WP-CLI invocation** always uses the project's required memory bump:
  ```
  php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood <subcommand>
  ```
- **Block-comment balance check** after editing pattern/template files:
  ```
  grep -c "<!-- wp:" <file>    # should equal
  grep -c "<!-- /wp:" <file>   # this number
  ```
  Note: self-closing blocks (`<!-- wp:foo /-->`) count toward the opening total but have no closing tag. Adjust expectations: `opens (including self-closing) === closes + self-closing`. The simplest reliable check is: count `<!-- wp:` occurrences that do NOT end with `/-->` and confirm that number equals the `<!-- /wp:` count.
- **PHP syntax check** after editing any `.php`:
  ```
  php -l <file>
  ```
- **Cache-busting** is automatic via `filemtime()` in `functions.php`. Never add `?ver=` query strings or version constants.
- **Browser checks** are listed as explicit steps with the URL to visit and what to look for. Run them at `http://newblood.test`.
- **Commits** happen at the end of each task. Repo convention from recent history: `<type>(<scope>): <subject>` (e.g., `feat(notes): add card grid pattern`). Always include the Co-Authored-By footer.

---

## File Structure

**New files**

| Path | Responsibility |
|---|---|
| `wp-content/themes/newblood/templates/home.html` | Renders the `/notes/` archive — inline header + `notes-index` pattern + `cta` pattern |
| `wp-content/themes/newblood/patterns/notes-index.php` | Card grid of all published posts (query loop + `.nb-note-card` markup) |
| `wp-content/themes/newblood/patterns/latest-note.php` | Single-card homepage hook ("Latest from Notes") |
| `wp-content/themes/newblood/patterns/more-notes.php` | Three-card footer rail on single posts |
| `wp-content/themes/newblood/patterns/related-notes.php` | Two-card horizontal rail keyed by category, for `/services/` |

**Modified files**

| Path | Change |
|---|---|
| `wp-content/themes/newblood/templates/single.html` | Featured-image hero, category badge, reading-time meta, "More notes" rail before CTA |
| `wp-content/themes/newblood/templates/front-page.html` | Insert `latest-note` pattern between testimonial and CTA |
| `wp-content/themes/newblood/patterns/services-detail.php` | Insert `related-notes` slot at the end of each of the four service blocks |
| `wp-content/themes/newblood/functions.php` | `NB_NOTES_PUBLIC` constant, helper functions (reading time, primary category, body class filter, menu item class filter), admin notices for missing featured images, SEO `wp_head` injection (meta description + OG + Twitter Card + JSON-LD + RSS link) |
| `wp-content/themes/newblood/assets/css/patterns.css` | New `===== Notes =====` section with `.nb-note-card`, `.nb-note-card-mini`, `.nb-latest-note-card`, category badge, "More notes" rail layout, and pre-launch hide rules |

**WordPress content (database)**

- New page "Notes" (slug `notes`) — host for the archive URL
- `Settings → Reading → Posts page` set to the Notes page
- Three placeholder draft posts with assigned categories and featured images
- Initial categories created: `Practice`, `Theory`, `Tools`
- "Notes" menu item added to the **Primary** menu (between Work and Pricing) and to the **Footer Company** menu

---

## Task 1: WordPress plumbing — create the Notes page, set page_for_posts, create initial categories

**Files:**
- WordPress database only (no theme file changes)

- [ ] **Step 1: Verify clean baseline**

Run:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post list --post_type=page --fields=ID,post_title,post_status --post_status=any 2>&1 | grep -i notes
```
Expected: no rows (no existing Notes page). If a Notes page already exists, capture its ID and skip Step 2.

- [ ] **Step 2: Create the Notes page**

Run:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post create --post_type=page --post_title="Notes" --post_name=notes --post_status=publish --post_excerpt="Field thoughts on modern web work — what's actually working, what's hype, and what's worth your attention." --porcelain
```
Expected: a single number (the new page ID) on stdout, e.g. `7012`. Note that ID — referred to below as `<NOTES_PAGE_ID>`.

- [ ] **Step 3: Assign the page as the posts archive**

Run:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood option update show_on_front page
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood option update page_for_posts <NOTES_PAGE_ID>
```
(Substitute `<NOTES_PAGE_ID>` with the actual ID from Step 2.)
Expected: `Success: Updated 'show_on_front' option.` and `Success: Updated 'page_for_posts' option.`

- [ ] **Step 4: Create the initial three categories**

Run (one at a time so each ID is visible):
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood term create category Practice --slug=practice --porcelain
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood term create category Theory --slug=theory --porcelain
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood term create category Tools --slug=tools --porcelain
```
Expected: three numeric IDs printed. Note them as `<CAT_PRACTICE>`, `<CAT_THEORY>`, `<CAT_TOOLS>` for Task 6.

- [ ] **Step 5: Browser check — `/notes/` URL resolves**

Visit `http://newblood.test/notes/` in a browser.
Expected: a page renders (it will look minimal/empty because no `home.html` template exists yet — WP falls back to `index.html` which is the current near-empty placeholder). The point of this check is that the URL doesn't 404 and the page-for-posts wiring works. If `/notes/` 404s, re-run `wp rewrite flush`:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood rewrite flush
```

- [ ] **Step 6: Commit**

This task has no theme-file changes, so there is nothing to commit. Move to Task 2.

---

## Task 2: functions.php — add helpers, launch-gate constant, and admin notices

**Files:**
- Modify: `wp-content/themes/newblood/functions.php` (append new code after the existing setup function)

- [ ] **Step 1: Append helper functions and launch gate to functions.php**

Open `wp-content/themes/newblood/functions.php`. Append the following block at the end of the file:

```php

/**
 * ============================================================
 * Notes section — helpers, launch gate, admin notices, SEO head
 * Spec: docs/superpowers/specs/2026-05-15-notes-section-design.md
 * ============================================================
 */

// Launch gate. Flip to true once 3 real posts are published.
if ( ! defined( 'NB_NOTES_PUBLIC' ) ) {
    define( 'NB_NOTES_PUBLIC', false );
}

/**
 * Add `is-prelaunch` to body when the Notes section isn't yet public.
 */
function newblood_notes_body_class( $classes ) {
    if ( ! NB_NOTES_PUBLIC ) {
        $classes[] = 'is-prelaunch';
    }
    return $classes;
}
add_filter( 'body_class', 'newblood_notes_body_class' );

/**
 * Tag the "Notes" menu items so CSS can hide them while pre-launch.
 * Matches by URL ending in /notes/ or /notes.
 */
function newblood_tag_notes_menu_items( $items ) {
    foreach ( $items as $item ) {
        if ( preg_match( '#/notes/?$#', $item->url ) ) {
            $item->classes[] = 'menu-item--notes';
        }
    }
    return $items;
}
add_filter( 'wp_nav_menu_objects', 'newblood_tag_notes_menu_items' );

/**
 * Reading time in whole minutes (250 wpm). Returns int.
 */
function newblood_reading_time( $post_id ) {
    $content = get_post_field( 'post_content', $post_id );
    $words   = str_word_count( wp_strip_all_tags( $content ) );
    return max( 1, (int) ceil( $words / 250 ) );
}

/**
 * Primary category for a post. Returns WP_Term or null.
 * Convention: first category by term_id when a post has multiple.
 */
function newblood_primary_category( $post_id ) {
    $cats = get_the_category( $post_id );
    if ( empty( $cats ) ) {
        return null;
    }
    usort( $cats, function( $a, $b ) { return $a->term_id - $b->term_id; } );
    return $cats[0];
}

/**
 * Admin notice on the post-edit screen when a published post has no featured image.
 * Soft warning — does not block publish.
 */
function newblood_notes_featured_image_notice() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->base !== 'post' || $screen->post_type !== 'post' ) {
        return;
    }
    global $post;
    if ( ! $post || $post->post_status !== 'publish' ) {
        return;
    }
    if ( has_post_thumbnail( $post->ID ) ) {
        return;
    }
    echo '<div class="notice notice-warning"><p><strong>Notes:</strong> this published post has no featured image. It will render with a fallback gradient on /notes/ until one is added.</p></div>';
}
add_action( 'admin_notices', 'newblood_notes_featured_image_notice' );
```

- [ ] **Step 2: PHP syntax check**

Run:
```
php -l wp-content/themes/newblood/functions.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Browser smoke check**

Visit `http://newblood.test/`.
Expected: site renders normally (no 500 errors, layout unchanged). View source and confirm `<body>` includes `is-prelaunch` class.

- [ ] **Step 4: Commit**

```
git add wp-content/themes/newblood/functions.php
git commit -m "$(cat <<'EOF'
feat(notes): add helpers, launch gate, and admin notice

NB_NOTES_PUBLIC constant, body-class filter, menu-item tagging
filter, reading-time + primary-category helpers, and a soft
admin warning when a published post lacks a featured image.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 3: CSS foundation — `.nb-note-card`, badge, latest-note card, mini card, pre-launch hide rules

**Files:**
- Modify: `wp-content/themes/newblood/assets/css/patterns.css` (append a new `===== Notes =====` section at end of file)

- [ ] **Step 1: Append Notes CSS at end of patterns.css**

Open `wp-content/themes/newblood/assets/css/patterns.css`. Append:

```css

/* ===== Notes ===== */

/* Pre-launch: hide nav and footer links to /notes/ */
body.is-prelaunch .menu-item--notes,
body.is-prelaunch .nb-latest-note-card,
body.is-prelaunch .nb-latest-note-section {
  display: none;
}

/* Notes index header (inline in home.html) */
.nb-notes-index-header {
  text-align: center;
}
.nb-notes-index-header h1 {
  font-size: clamp(2rem, 4vw, 3rem);
  line-height: 1.15;
  letter-spacing: -0.02em;
  font-weight: 700;
}
.nb-notes-index-header .nb-notes-dek {
  font-size: 1rem;
  line-height: 1.7;
  color: rgba(255, 255, 255, 0.7);
  max-width: 560px;
  margin: 16px auto 0;
}

/* Category badge — reused across .nb-note-card and .nb-latest-note-card */
.nb-note-badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 999px;
  background: rgba(74, 222, 128, 0.12);
  color: #4ade80;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  text-decoration: none;
}

/* Index card */
.nb-note-card {
  display: flex;
  flex-direction: column;
  background: transparent;
  border-radius: 12px;
  overflow: hidden;
  text-decoration: none;
  color: inherit;
  transition: transform 280ms ease;
}
.nb-note-card:hover {
  transform: translateY(-4px);
}
.nb-note-card-image {
  width: 100%;
  aspect-ratio: 3 / 2;
  overflow: hidden;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}
.nb-note-card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 600ms ease;
}
.nb-note-card:hover .nb-note-card-image img {
  transform: scale(1.03);
}
.nb-note-card-body {
  padding: 24px 0 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.nb-note-card-title {
  font-size: 1.5rem;
  line-height: 1.25;
  font-weight: 700;
  letter-spacing: -0.01em;
  color: #ffffff;
  margin: 0;
  transition: color 200ms ease;
}
.nb-note-card:hover .nb-note-card-title {
  color: #4ade80;
}
.nb-note-card-dek {
  font-size: 0.9375rem;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.7);
  margin: 0;
}
.nb-note-card-meta {
  font-size: 0.75rem;
  letter-spacing: 0.04em;
  color: rgba(255, 255, 255, 0.5);
  margin: 0;
}

/* Index grid */
.nb-notes-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 48px;
}
@media (min-width: 640px) {
  .nb-notes-grid {
    grid-template-columns: repeat(2, 1fr);
    column-gap: 32px;
    row-gap: 48px;
  }
}
@media (min-width: 960px) {
  .nb-notes-grid {
    column-gap: 48px;
    row-gap: 64px;
  }
}

/* Single post — featured image hero */
.nb-note-hero-image {
  width: 100%;
  aspect-ratio: 3 / 2;
  border-radius: 12px;
  overflow: hidden;
  margin: 0 0 32px;
}
.nb-note-hero-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.nb-note-hero-meta {
  font-size: 0.8125rem;
  color: rgba(255, 255, 255, 0.6);
}

/* "More notes" rail on single posts */
.nb-more-notes {
  display: grid;
  grid-template-columns: 1fr;
  gap: 48px;
}
@media (min-width: 720px) {
  .nb-more-notes {
    grid-template-columns: repeat(3, 1fr);
    gap: 32px;
  }
}
.nb-more-notes .nb-note-card-title {
  font-size: 1.125rem;
}
.nb-more-notes .nb-note-card-dek {
  display: none;
}

/* Homepage hook — "Latest from Notes" */
.nb-latest-note-section-label {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin: 0 0 24px;
}
.nb-latest-note-section-label .nb-label {
  font-size: 0.8125rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.7);
}
.nb-latest-note-section-label .nb-view-all {
  font-size: 0.875rem;
  color: #4ade80;
  text-decoration: none;
}
.nb-latest-note-card {
  display: grid;
  grid-template-columns: 1fr;
  gap: 24px;
  text-decoration: none;
  color: inherit;
  border-radius: 12px;
  overflow: hidden;
  transition: transform 280ms ease;
}
.nb-latest-note-card:hover {
  transform: translateY(-4px);
}
@media (min-width: 720px) {
  .nb-latest-note-card {
    grid-template-columns: 2fr 3fr;
    gap: 40px;
    align-items: center;
  }
}
.nb-latest-note-card-image {
  width: 100%;
  aspect-ratio: 3 / 2;
  overflow: hidden;
  border-radius: 12px;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}
.nb-latest-note-card-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 600ms ease;
}
.nb-latest-note-card:hover .nb-latest-note-card-image img {
  transform: scale(1.03);
}
.nb-latest-note-card-body {
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.nb-latest-note-card-title {
  font-size: 1.75rem;
  line-height: 1.2;
  font-weight: 700;
  letter-spacing: -0.01em;
  color: #ffffff;
  margin: 0;
  transition: color 200ms ease;
}
.nb-latest-note-card:hover .nb-latest-note-card-title {
  color: #4ade80;
}
.nb-latest-note-card-dek {
  font-size: 1rem;
  line-height: 1.6;
  color: rgba(255, 255, 255, 0.7);
  margin: 0;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.nb-latest-note-card-meta {
  font-size: 0.75rem;
  letter-spacing: 0.04em;
  color: rgba(255, 255, 255, 0.5);
  margin: 0;
}

/* Mini card — services-page "Related notes" rail */
.nb-note-card-mini {
  display: grid;
  grid-template-columns: 96px 1fr;
  gap: 16px;
  align-items: center;
  text-decoration: none;
  color: inherit;
  transition: transform 280ms ease;
}
.nb-note-card-mini:hover {
  transform: translateY(-2px);
}
.nb-note-card-mini-image {
  width: 96px;
  height: 64px;
  border-radius: 8px;
  overflow: hidden;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
}
.nb-note-card-mini-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.nb-note-card-mini-title {
  font-size: 0.9375rem;
  font-weight: 600;
  line-height: 1.35;
  color: #ffffff;
  margin: 0;
  transition: color 200ms ease;
}
.nb-note-card-mini:hover .nb-note-card-mini-title {
  color: #4ade80;
}
.nb-note-card-mini-meta {
  font-size: 0.7rem;
  letter-spacing: 0.04em;
  color: rgba(255, 255, 255, 0.5);
  margin: 4px 0 0;
}
.nb-related-notes {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  margin-top: 24px;
}
@media (min-width: 720px) {
  .nb-related-notes {
    grid-template-columns: repeat(2, 1fr);
    gap: 32px;
  }
}
.nb-related-notes-label {
  font-size: 0.75rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(255, 255, 255, 0.5);
  grid-column: 1 / -1;
  margin: 0;
}
```

- [ ] **Step 2: Browser smoke check**

Visit `http://newblood.test/`.
Expected: homepage renders normally — no visual regressions. The new CSS adds rules but no DOM uses them yet, so nothing visible changes. View page source and confirm `patterns.css` loads with a fresh `?ver=` filemtime number.

- [ ] **Step 3: Commit**

```
git add wp-content/themes/newblood/assets/css/patterns.css
git commit -m "$(cat <<'EOF'
feat(notes): add CSS foundation

.nb-note-card, .nb-note-card-mini, .nb-latest-note-card,
category badge, more-notes rail, related-notes rail, index
grid, single-post hero image, and pre-launch hide rules.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 4: Create the `notes-index` pattern

**Files:**
- Create: `wp-content/themes/newblood/patterns/notes-index.php`

- [ ] **Step 1: Create the pattern file**

Create `wp-content/themes/newblood/patterns/notes-index.php` with the following content:

```php
<?php
/**
 * Title: Notes Index
 * Slug: newblood/notes-index
 * Categories: newblood
 * Description: Card grid of all published Notes posts (reverse chronological)
 */
?>
<!-- wp:group {"align":"full","className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull nb-gradient-section">

  <!-- wp:query {"queryId":1,"query":{"perPage":12,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"className":"nb-notes-query"} -->
  <div class="wp-block-query nb-notes-query">

    <!-- wp:post-template {"className":"nb-notes-grid nb-stagger"} -->

      <!-- wp:html -->
      <a class="nb-note-card" href="<?php the_permalink(); ?>">
        <div class="nb-note-card-image">
          <?php
          if ( has_post_thumbnail() ) {
              the_post_thumbnail( 'large' );
          }
          ?>
        </div>
        <div class="nb-note-card-body">
          <?php
          $primary = function_exists( 'newblood_primary_category' ) ? newblood_primary_category( get_the_ID() ) : null;
          if ( $primary ) :
          ?>
            <span class="nb-note-badge"><?php echo esc_html( $primary->name ); ?></span>
          <?php endif; ?>
          <h2 class="nb-note-card-title"><?php the_title(); ?></h2>
          <p class="nb-note-card-dek"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28, '…' ) ); ?></p>
          <p class="nb-note-card-meta">
            <?php echo esc_html( get_the_date( 'F j, Y' ) ); ?>
            ·
            <?php echo esc_html( newblood_reading_time( get_the_ID() ) ); ?> min read
          </p>
        </div>
      </a>
      <!-- /wp:html -->

    <!-- /wp:post-template -->

    <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
      <!-- wp:query-pagination-previous /-->
      <!-- wp:query-pagination-numbers /-->
      <!-- wp:query-pagination-next /-->
    <!-- /wp:query-pagination -->

    <!-- wp:query-no-results -->
      <!-- wp:paragraph {"align":"center","textColor":"text-muted"} -->
      <p class="has-text-align-center has-text-muted-color">No notes yet — first one is coming.</p>
      <!-- /wp:paragraph -->
    <!-- /wp:query-no-results -->

  </div>
  <!-- /wp:query -->

</div>
<!-- /wp:group -->
```

Note: this pattern uses a `wp:html` block containing raw PHP. WordPress block patterns are PHP files, so `<?php ?>` tags execute during pattern registration when the file is read. The `wp:html` block then passes the rendered HTML through as-is. The `the_post_thumbnail`, `the_permalink`, `get_the_ID`, etc., calls inside the loop work because they execute inside the `wp:post-template` block's loop context.

- [ ] **Step 2: PHP syntax check**

Run:
```
php -l wp-content/themes/newblood/patterns/notes-index.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Block-comment balance check**

Run:
```
grep -c '<!-- /wp:' wp-content/themes/newblood/patterns/notes-index.php
```
Expected: **7** — matching the 7 paired opening blocks (`wp:group`, `wp:query`, `wp:post-template`, `wp:html`, `wp:query-pagination`, `wp:query-no-results`, `wp:paragraph`). The 3 self-closing blocks (`wp:query-pagination-previous`, `wp:query-pagination-numbers`, `wp:query-pagination-next`) end in `/-->` and don't need a closer. If the count differs from 7, the markup is unbalanced — open the file and verify each `<!-- wp:foo -->` has a corresponding `<!-- /wp:foo -->`.

- [ ] **Step 4: Commit**

```
git add wp-content/themes/newblood/patterns/notes-index.php
git commit -m "$(cat <<'EOF'
feat(notes): add notes-index card grid pattern

Card grid of published posts with featured image, category
badge, title, excerpt, and date + reading-time meta. Uses
wp:query + wp:post-template + wp:html with PHP escape hatch
to wrap each card in an <a> permalink (block syntax can't
wrap arbitrary blocks in an anchor cleanly).

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 5: Create the `home.html` template

**Files:**
- Create: `wp-content/themes/newblood/templates/home.html`

- [ ] **Step 1: Create the template file**

Create `wp-content/themes/newblood/templates/home.html` with:

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">

  <!-- wp:group {"align":"full","className":"nb-gradient-primary nb-dot-grid nb-notes-index-header","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
  <div class="wp-block-group alignfull nb-gradient-primary nb-dot-grid nb-notes-index-header">
    <!-- wp:heading {"level":1,"className":"nb-hero-headline"} -->
    <h1 class="wp-block-heading nb-hero-headline">Notes<span style="color:#4ade80">.</span></h1>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"className":"nb-notes-dek"} -->
    <p class="nb-notes-dek">Field thoughts on modern web work — what's actually working, what's hype, and what's worth your attention.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->

  <!-- wp:pattern {"slug":"newblood/notes-index"} /-->

  <!-- wp:pattern {"slug":"newblood/cta"} /-->

</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

- [ ] **Step 2: Block-comment balance check**

Read the file. Confirm:
- 3 self-closing `wp:` (header template-part, notes-index pattern, cta pattern, footer template-part = **4** self-closing)
- 3 paired `wp:` opens (`wp:group main`, `wp:group inner`, `wp:heading`, `wp:paragraph` = **4** paired opens)

Run:
```
grep -c '<!-- /wp:' wp-content/themes/newblood/templates/home.html
```
Expected: **4** (matching the 4 paired opens).

- [ ] **Step 3: Browser check — /notes/ now renders with the new template**

Visit `http://newblood.test/notes/`.
Expected: the new header ("Notes." with green period + dek) shows at the top, then the empty/no-results state from `notes-index` (since no posts are published yet), then the CTA at the bottom. View source: header bar at top, footer at bottom, no missing chrome.

If `/notes/` still shows the bare old layout, run `wp rewrite flush` and reload.

- [ ] **Step 4: Commit**

```
git add wp-content/themes/newblood/templates/home.html
git commit -m "$(cat <<'EOF'
feat(notes): add home.html template for the /notes/ archive

Inlines a Notes header (heading + dek) instead of reusing the
page-header pattern, because the page-header pattern relies on
the global post context which on the posts archive becomes the
first post in the loop, not the Notes page itself.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 6: Create three placeholder draft posts for development

**Files:**
- WordPress content only

- [ ] **Step 1: Identify featured-image attachment IDs to reuse**

Run:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post list --post_type=attachment --posts_per_page=10 --fields=ID,post_title 2>&1 | grep -i nb-services
```
Expected: a list of attachment rows including the four service illustrations (`nb-services-build.jpg`, `nb-services-tune.jpg`, etc.). Note the IDs of three of them — referred to below as `<IMG_1>`, `<IMG_2>`, `<IMG_3>`. (Reusing existing media as quick stand-ins; Jeremy will swap in real custom imagery before public launch.)

- [ ] **Step 2: Create three draft posts with categories assigned**

Run (substitute `<CAT_PRACTICE>`, `<CAT_THEORY>`, `<CAT_TOOLS>` from Task 1, and `<IMG_1/2/3>` from Step 1):

```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post create \
  --post_type=post --post_status=draft \
  --post_title="Placeholder: practice note" \
  --post_excerpt="A short dek about what this note covers. Written in deliberate, considered tones to set up the reading experience." \
  --post_content="<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p><p>Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum. Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p><p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.</p><p>Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur.</p>" \
  --post_category=<CAT_PRACTICE> \
  --porcelain
```

Take the returned ID and attach the featured image:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post meta update <NEW_POST_ID> _thumbnail_id <IMG_1>
```

Repeat for two more posts with titles "Placeholder: theory note" and "Placeholder: tools note", categories `<CAT_THEORY>` and `<CAT_TOOLS>`, and featured images `<IMG_2>` and `<IMG_3>`.

- [ ] **Step 3: Verify the posts exist and are drafts**

Run:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post list --post_type=post --post_status=draft --fields=ID,post_title,post_status 2>&1 | grep -i placeholder
```
Expected: three rows, all `draft`.

- [ ] **Step 4: Browser check — drafts visible to logged-in user**

While logged in to `/wp-admin/`, visit `http://newblood.test/notes/`.
Expected: three placeholder cards in the grid, each with featured image, badge (`Practice` / `Theory` / `Tools`), title, dek, date + reading time.

In an **incognito window** (logged out), visit `http://newblood.test/notes/`.
Expected: empty state ("No notes yet — first one is coming."). Draft posts must not be visible to guests.

- [ ] **Step 5: Commit**

No theme-file changes — placeholder posts live in the WP database. Move to Task 7.

---

## Task 7: Refine `templates/single.html` — featured image hero, category badge, reading time

**Files:**
- Modify: `wp-content/themes/newblood/templates/single.html`

- [ ] **Step 1: Replace the hero block with the refined version**

Open `wp-content/themes/newblood/templates/single.html`. Replace the entire current content with:

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">

  <!-- wp:group {"align":"full","className":"nb-gradient-primary nb-dot-grid","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
  <div class="wp-block-group alignfull nb-gradient-primary nb-dot-grid" style="text-align:center">

    <!-- wp:html -->
    <?php if ( has_post_thumbnail() ) : ?>
      <figure class="nb-note-hero-image">
        <?php the_post_thumbnail( 'large' ); ?>
      </figure>
    <?php endif; ?>
    <!-- /wp:html -->

    <!-- wp:post-title {"level":1,"className":"nb-hero-headline","style":{"typography":{"fontSize":"clamp(2rem, 4vw, 3rem)","lineHeight":"1.15","letterSpacing":"-0.02em"}}} /-->

    <!-- wp:html -->
    <p class="nb-note-hero-meta">
      <?php
      $primary = function_exists( 'newblood_primary_category' ) ? newblood_primary_category( get_the_ID() ) : null;
      if ( $primary ) {
          echo '<a class="nb-note-badge" href="' . esc_url( get_category_link( $primary->term_id ) ) . '">' . esc_html( $primary->name ) . '</a> &nbsp;&middot;&nbsp; ';
      }
      echo esc_html( get_the_date( 'F j, Y' ) );
      echo ' &nbsp;&middot;&nbsp; ';
      echo esc_html( newblood_reading_time( get_the_ID() ) ) . ' min read';
      ?>
    </p>
    <!-- /wp:html -->

  </div>
  <!-- /wp:group -->

  <!-- wp:group {"className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
  <div class="wp-block-group nb-gradient-section">
    <!-- wp:post-content /-->
  </div>
  <!-- /wp:group -->

  <!-- wp:pattern {"slug":"newblood/more-notes"} /-->

  <!-- wp:pattern {"slug":"newblood/cta"} /-->

</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

Note: the `more-notes` pattern doesn't exist yet — it's referenced here and built in Task 8. Until then, this block-pattern reference renders as nothing (WP silently skips unknown pattern slugs in templates).

- [ ] **Step 2: Block-comment balance check**

Run:
```
grep -c '<!-- /wp:' wp-content/themes/newblood/templates/single.html
```
Expected: **5** — matching the 5 paired opening blocks (outer `wp:group main`, hero `wp:group`, body `wp:group`, and 2 `wp:html` blocks). Self-closing blocks (`wp:template-part` ×2, `wp:post-title`, `wp:post-content`, 2 `wp:pattern` references) end in `/-->` and don't need a closer.

- [ ] **Step 3: Browser check — single post renders**

Visit `http://newblood.test/?p=<NEW_POST_ID>` (one of the placeholder post IDs from Task 6) while logged in.
Expected: hero gradient with featured image at top, then post title, then meta row `Practice · Month Day, Year · X min read`, then the lorem-ipsum body, then the CTA. The "More notes" pattern slot is empty until Task 8.

- [ ] **Step 4: Commit**

```
git add wp-content/themes/newblood/templates/single.html
git commit -m "$(cat <<'EOF'
feat(notes): refine single.html with featured image, badge, reading time

Hero block now includes the featured image above the title and
a meta row with primary-category badge, date, and reading time.
A more-notes pattern slot is reserved before the CTA (pattern
itself is built in the next task).

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 8: Create the `more-notes` pattern (single-post footer rail)

**Files:**
- Create: `wp-content/themes/newblood/patterns/more-notes.php`

- [ ] **Step 1: Create the pattern file**

Create `wp-content/themes/newblood/patterns/more-notes.php` with:

```php
<?php
/**
 * Title: More Notes
 * Slug: newblood/more-notes
 * Categories: newblood
 * Description: Three-card rail of recent posts shown at the bottom of a single Notes post.
 */

$current_id = get_the_ID();
$recent = get_posts( array(
    'numberposts'      => 3,
    'post_status'      => 'publish',
    'exclude'          => array( $current_id ),
    'suppress_filters' => false,
) );

if ( empty( $recent ) ) {
    return;
}
?>
<!-- wp:group {"align":"full","className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull nb-gradient-section">

  <!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"1.25rem"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} -->
  <h2 class="wp-block-heading">More notes<span style="color:#4ade80">.</span></h2>
  <!-- /wp:heading -->

  <!-- wp:html -->
  <div class="nb-more-notes nb-stagger">
    <?php foreach ( $recent as $post ) :
        setup_postdata( $post );
        $primary = function_exists( 'newblood_primary_category' ) ? newblood_primary_category( $post->ID ) : null;
    ?>
      <a class="nb-note-card" href="<?php the_permalink(); ?>">
        <div class="nb-note-card-image">
          <?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium' ); } ?>
        </div>
        <div class="nb-note-card-body">
          <?php if ( $primary ) : ?>
            <span class="nb-note-badge"><?php echo esc_html( $primary->name ); ?></span>
          <?php endif; ?>
          <h3 class="nb-note-card-title"><?php the_title(); ?></h3>
          <p class="nb-note-card-meta">
            <?php echo esc_html( get_the_date( 'F j, Y' ) ); ?>
            ·
            <?php echo esc_html( newblood_reading_time( $post->ID ) ); ?> min read
          </p>
        </div>
      </a>
    <?php endforeach;
    wp_reset_postdata();
    ?>
  </div>
  <!-- /wp:html -->

</div>
<!-- /wp:group -->
```

- [ ] **Step 2: PHP syntax check**

Run:
```
php -l wp-content/themes/newblood/patterns/more-notes.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Browser check — More notes rail appears on single post**

Visit `http://newblood.test/?p=<FIRST_PLACEHOLDER_POST_ID>` while logged in.
Expected: below the post body, a "More notes." heading and a row of 2 cards (the two OTHER placeholder posts; current is excluded). Cards have featured image, badge, title, date · reading time.

If you only see 1 card or no cards, verify three placeholders exist as drafts and the current post is excluded.

- [ ] **Step 4: Commit**

```
git add wp-content/themes/newblood/patterns/more-notes.php
git commit -m "$(cat <<'EOF'
feat(notes): add more-notes rail pattern for single posts

Three-card rail of recent posts (current excluded), shown at
the bottom of single posts above the CTA. Renders nothing
when no other posts exist.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 9: Create the `latest-note` homepage hook pattern

**Files:**
- Create: `wp-content/themes/newblood/patterns/latest-note.php`

- [ ] **Step 1: Create the pattern file**

Create `wp-content/themes/newblood/patterns/latest-note.php` with:

```php
<?php
/**
 * Title: Latest from Notes
 * Slug: newblood/latest-note
 * Categories: newblood
 * Description: Single-card homepage hook showing the most recent Notes post.
 */

$latest = get_posts( array(
    'numberposts' => 1,
    'post_status' => 'publish',
) );

if ( empty( $latest ) ) {
    return;
}

$post = $latest[0];
setup_postdata( $post );
$primary = function_exists( 'newblood_primary_category' ) ? newblood_primary_category( $post->ID ) : null;
$permalink = get_permalink( $post->ID );
$date      = get_the_date( 'F j, Y', $post->ID );
$reading   = function_exists( 'newblood_reading_time' ) ? newblood_reading_time( $post->ID ) : 0;
$excerpt   = wp_trim_words( get_the_excerpt( $post ), 36, '…' );
?>
<!-- wp:group {"align":"full","className":"nb-gradient-section nb-latest-note-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1100px"}} -->
<div class="wp-block-group alignfull nb-gradient-section nb-latest-note-section">

  <!-- wp:html -->
  <div class="nb-latest-note-section-label">
    <span class="nb-label">From Notes<span style="color:#4ade80">.</span></span>
    <a class="nb-view-all" href="<?php echo esc_url( home_url( '/notes/' ) ); ?>">View all &rarr;</a>
  </div>

  <a class="nb-latest-note-card" href="<?php echo esc_url( $permalink ); ?>">
    <div class="nb-latest-note-card-image">
      <?php if ( has_post_thumbnail( $post->ID ) ) { echo get_the_post_thumbnail( $post->ID, 'large' ); } ?>
    </div>
    <div class="nb-latest-note-card-body">
      <?php if ( $primary ) : ?>
        <span class="nb-note-badge"><?php echo esc_html( $primary->name ); ?></span>
      <?php endif; ?>
      <h2 class="nb-latest-note-card-title"><?php echo esc_html( get_the_title( $post->ID ) ); ?></h2>
      <p class="nb-latest-note-card-dek"><?php echo esc_html( $excerpt ); ?></p>
      <p class="nb-latest-note-card-meta">
        <?php echo esc_html( $date ); ?>
        ·
        <?php echo esc_html( $reading ); ?> min read
      </p>
    </div>
  </a>
  <!-- /wp:html -->

</div>
<!-- /wp:group -->
<?php wp_reset_postdata(); ?>
```

- [ ] **Step 2: PHP syntax check**

Run:
```
php -l wp-content/themes/newblood/patterns/latest-note.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit (pattern alone — homepage hook insertion is the next task)**

```
git add wp-content/themes/newblood/patterns/latest-note.php
git commit -m "$(cat <<'EOF'
feat(notes): add latest-note pattern for homepage hook

Single-card homepage hook with image-left/text-right layout
showing the most recently published post. Renders nothing
when no posts are published (independent of NB_NOTES_PUBLIC).

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 10: Insert `latest-note` into the homepage flow

**Files:**
- Modify: `wp-content/themes/newblood/templates/front-page.html`

- [ ] **Step 1: Insert the pattern between testimonial and CTA**

Open `wp-content/themes/newblood/templates/front-page.html`. The current content is:

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
  <!-- wp:pattern {"slug":"newblood/hero"} /-->
  <!-- wp:pattern {"slug":"newblood/statement"} /-->
  <!-- wp:pattern {"slug":"newblood/social-proof"} /-->
  <!-- wp:pattern {"slug":"newblood/services-cards"} /-->
  <!-- wp:pattern {"slug":"newblood/portfolio-grid"} /-->
  <!-- wp:pattern {"slug":"newblood/testimonial"} /-->
  <!-- wp:pattern {"slug":"newblood/cta"} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

Use Edit to insert exactly one new line between testimonial and CTA so the file becomes:

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
  <!-- wp:pattern {"slug":"newblood/hero"} /-->
  <!-- wp:pattern {"slug":"newblood/statement"} /-->
  <!-- wp:pattern {"slug":"newblood/social-proof"} /-->
  <!-- wp:pattern {"slug":"newblood/services-cards"} /-->
  <!-- wp:pattern {"slug":"newblood/portfolio-grid"} /-->
  <!-- wp:pattern {"slug":"newblood/testimonial"} /-->
  <!-- wp:pattern {"slug":"newblood/latest-note"} /-->
  <!-- wp:pattern {"slug":"newblood/cta"} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

- [ ] **Step 2: Browser check — homepage hook hidden by pre-launch CSS**

Visit `http://newblood.test/` (logged in or out).
Expected: homepage looks unchanged. The `latest-note` pattern DOES render (because draft posts exist but no publishes — actually because of `post_status => publish`, the pattern returns early and renders nothing). Even if it did render, the CSS rule `body.is-prelaunch .nb-latest-note-section { display: none; }` hides it.

To verify the slot will work post-launch:
1. Temporarily flip one placeholder post from draft to publish:
   ```
   php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post update <PLACEHOLDER_ID> --post_status=publish
   ```
2. Temporarily edit `functions.php` and change `define( 'NB_NOTES_PUBLIC', false );` to `true`.
3. Reload `http://newblood.test/` — confirm "From Notes." section appears between testimonial and CTA, with image left, title/dek right, "View all →" link top-right.
4. Revert both: set `NB_NOTES_PUBLIC` back to `false`; flip the post back to `draft`.

- [ ] **Step 3: Commit**

```
git add wp-content/themes/newblood/templates/front-page.html
git commit -m "$(cat <<'EOF'
feat(notes): insert latest-note hook in homepage between testimonial and CTA

Placement reasoning: client voice (social proof + testimonial)
→ agency voice (latest note) → conversion (CTA). Hook is
hidden by CSS until NB_NOTES_PUBLIC flips to true.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 11: Create the `related-notes` pattern

**Files:**
- Create: `wp-content/themes/newblood/patterns/related-notes.php`

- [ ] **Step 1: Create the pattern file**

Create `wp-content/themes/newblood/patterns/related-notes.php` with:

```php
<?php
/**
 * Title: Related Notes
 * Slug: newblood/related-notes
 * Categories: newblood
 * Description: Compact two-card rail of recent posts in a given category. Used at the bottom of each /services/ block.
 *
 * The category slug is encoded in a wrapper class on the consuming pattern reference
 * (e.g. `<!-- wp:pattern {"slug":"newblood/related-notes"} /-->` followed by a
 * surrounding group with a specific class). To keep this simple, this pattern
 * fetches the LATEST 2 posts overall when no category context is available, and
 * relies on the category-specific mapping being passed in by editing the pattern
 * file directly for each service slot. See Task 12 for how each service inserts
 * a category-filtered variant inline.
 *
 * This file is the GENERIC fallback; the per-service inline copies use the same
 * markup but with a category-specific query.
 */

$related = get_posts( array(
    'numberposts' => 2,
    'post_status' => 'publish',
) );

if ( empty( $related ) ) {
    return;
}
?>
<!-- wp:group {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
<div class="wp-block-group">

  <!-- wp:html -->
  <div class="nb-related-notes">
    <p class="nb-related-notes-label">Related notes<span style="color:#4ade80">.</span></p>
    <?php foreach ( $related as $post ) :
        setup_postdata( $post );
    ?>
      <a class="nb-note-card-mini" href="<?php the_permalink(); ?>">
        <div class="nb-note-card-mini-image">
          <?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'thumbnail' ); } ?>
        </div>
        <div>
          <h4 class="nb-note-card-mini-title"><?php the_title(); ?></h4>
          <p class="nb-note-card-mini-meta">
            <?php echo esc_html( get_the_date( 'F Y' ) ); ?>
            ·
            <?php echo esc_html( newblood_reading_time( $post->ID ) ); ?> min read
          </p>
        </div>
      </a>
    <?php endforeach;
    wp_reset_postdata();
    ?>
  </div>
  <!-- /wp:html -->

</div>
<!-- /wp:group -->
```

- [ ] **Step 2: PHP syntax check**

Run:
```
php -l wp-content/themes/newblood/patterns/related-notes.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit (insertion into services-detail is the next task)**

```
git add wp-content/themes/newblood/patterns/related-notes.php
git commit -m "$(cat <<'EOF'
feat(notes): add related-notes rail pattern for /services/

Compact two-card horizontal rail for the bottom of each service
block. Returns early when no posts exist, so /services/ shows
no rails on launch day and they fill in organically.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 12: Insert `related-notes` slots into `services-detail.php`

**Files:**
- Modify: `wp-content/themes/newblood/patterns/services-detail.php`

The services-detail pattern has four service blocks. Each is wrapped in a `<!-- wp:group {"className":"nb-reveal", ...} -->` … `<!-- /wp:group -->`. Inside each, after the `.nb-case-highlight` bullet block closes, we insert the related-notes pattern reference. The pattern itself returns nothing when no matching posts exist, so this is safe to ship empty.

- [ ] **Step 1: Insert related-notes after the Build service bullet list**

In `wp-content/themes/newblood/patterns/services-detail.php`, find the Build block's closing `<!-- /wp:group -->` for `.nb-case-highlight` (around line 97 in current content — `</div>` then `<!-- /wp:group -->`). Insert immediately after that group's close and before the close of the surrounding `nb-reveal` group:

```html

    <!-- wp:pattern {"slug":"newblood/related-notes"} /-->
```

The Build section's structure after edit looks like:

```html
    <!-- wp:group {"className":"nb-case-highlight",...} -->
    <div class="wp-block-group nb-case-highlight">
      [columns + lists]
    </div>
    <!-- /wp:group -->

    <!-- wp:pattern {"slug":"newblood/related-notes"} /-->

  </div>
  <!-- /wp:group -->  (this closes the nb-reveal wrapper for Build)
```

- [ ] **Step 2: Repeat for Tune block**

After Tune's `.nb-case-highlight` `<!-- /wp:group -->` close (around line 187), and BEFORE Tune's "What we don't promise" `<!-- wp:paragraph -->` block (around line 188), insert the same pattern reference:

```html

    <!-- wp:pattern {"slug":"newblood/related-notes"} /-->
```

Tune's structure (key portion):

```html
    <!-- wp:group {"className":"nb-case-highlight",...} -->
    [columns + lists]
    <!-- /wp:group -->

    <!-- wp:pattern {"slug":"newblood/related-notes"} /-->

    <!-- wp:paragraph {"textColor":"text-muted",...} -->
    <p>What we don't promise:…</p>
    <!-- /wp:paragraph -->
```

- [ ] **Step 3: Repeat for Manage block**

After Manage's `.nb-case-highlight` close (around line 281), insert:

```html

    <!-- wp:pattern {"slug":"newblood/related-notes"} /-->
```

- [ ] **Step 4: Repeat for Empower block**

After Empower's `.nb-case-highlight` close (around line 370), insert:

```html

    <!-- wp:pattern {"slug":"newblood/related-notes"} /-->
```

- [ ] **Step 5: PHP syntax check**

Run:
```
php -l wp-content/themes/newblood/patterns/services-detail.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 6: Block-comment balance check**

Run:
```
grep -c '<!-- /wp:' wp-content/themes/newblood/patterns/services-detail.php
```
Compare to the prior count (capture before edit using `git show HEAD:wp-content/themes/newblood/patterns/services-detail.php | grep -c '<!-- /wp:'`). Inserting four self-closing pattern references should NOT change the count of closing comments. If it differs, the insertion broke balance.

- [ ] **Step 7: Browser check — /services/ still renders cleanly**

Visit `http://newblood.test/services/`.
Expected: all four service blocks render unchanged visually. Because `related-notes` returns early on empty results AND because no posts are published yet, no rails appear. View source for each service block — the pattern reference should expand to nothing.

To verify the slot WILL work post-launch, temporarily publish one placeholder post (same dance as Task 10's verify), reload `/services/`, confirm the "Related notes." rail appears in the Build (or whichever matched the post's category) section's footer. Revert.

- [ ] **Step 8: Commit**

```
git add wp-content/themes/newblood/patterns/services-detail.php
git commit -m "$(cat <<'EOF'
feat(notes): add related-notes slot to each /services/ block

Four slots, one per service. Each renders a compact two-card
rail of recent posts when posts exist, and renders nothing
otherwise. Internal links from high-authority /services/
page lift Notes posts' SEO ranking over time.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 13: Add Notes menu items to Primary and Footer Company menus

**Files:**
- WordPress menus only

- [ ] **Step 1: Find the URL for the Notes page**

The page was created in Task 1. The URL should be `http://newblood.test/notes/`. Verify:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post list --post_type=page --name=notes --fields=ID,post_title,guid 2>&1
```

- [ ] **Step 2: Add Notes to the Primary menu, positioned between Work and Pricing**

First find the position of the existing items:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood menu item list Primary --fields=db_id,title,menu_order 2>&1
```
Expected output (example):
```
db_id   title       menu_order
123     Services    1
124     Work        2
125     Pricing     3
126     About       4
127     Get Started 5
```

Add Notes after Work:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood menu item add-post Primary <NOTES_PAGE_ID> --title="Notes" --position=3
```

Then bump Pricing/About/Get Started:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood menu item update <Pricing_db_id> --position=4
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood menu item update <About_db_id> --position=5
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood menu item update <GetStarted_db_id> --position=6
```

(Alternative: do the reorder by hand via `/wp-admin/nav-menus.php`. Either method works; CLI is reproducible.)

Verify:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood menu item list Primary --fields=title,menu_order 2>&1
```
Expected: `Services, Work, Notes, Pricing, About, Get Started` in that menu_order.

- [ ] **Step 3: Add Notes to the Footer Company menu**

```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood menu item add-post "Footer Company" <NOTES_PAGE_ID> --title="Notes"
```

Verify:
```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood menu item list "Footer Company" --fields=title,menu_order 2>&1
```
Expected: includes "Notes" row.

- [ ] **Step 4: Browser check — menu items hidden during pre-launch, visible when flipped**

Logged out, visit `http://newblood.test/`. Inspect the primary nav and footer.
Expected: NO "Notes" link visible. Inspect the menu's HTML in DevTools — the menu items should be present in the DOM but their parent `<li>` carries class `menu-item--notes` and is hidden by `body.is-prelaunch .menu-item--notes { display: none; }`.

Temporarily flip `NB_NOTES_PUBLIC` to `true` in `functions.php`, reload. Notes links should appear in both primary nav and footer. Revert to `false`.

- [ ] **Step 5: Commit**

No theme-file changes for this task — menus are in the WP database. Move to Task 14.

---

## Task 14: SEO plumbing — meta description, Open Graph, Twitter Card, JSON-LD, RSS auto-discovery

**Files:**
- Modify: `wp-content/themes/newblood/functions.php` (append the SEO head injection)

- [ ] **Step 1: Append SEO head injection to functions.php**

Open `functions.php`. Append at the end of the file (after the helpers added in Task 2):

```php

/**
 * SEO head injection — meta description, Open Graph, Twitter Card, JSON-LD.
 * Runs on every page. Notes posts get full Article schema; other pages get
 * basic OG tags.
 */
function newblood_seo_head() {
    if ( is_admin() ) {
        return;
    }

    // Resolve description, title, image, URL based on context.
    $title = '';
    $description = '';
    $image = '';
    $url = '';
    $type = 'website';

    if ( is_singular() ) {
        global $post;
        $title = wp_strip_all_tags( get_the_title( $post ) );
        $excerpt = $post->post_excerpt ? $post->post_excerpt : wp_trim_words( wp_strip_all_tags( $post->post_content ), 32, '…' );
        $description = $excerpt;
        $url = get_permalink( $post );

        if ( has_post_thumbnail( $post ) ) {
            $image_id = get_post_thumbnail_id( $post );
            $image_src = wp_get_attachment_image_src( $image_id, 'large' );
            if ( $image_src ) {
                $image = $image_src[0];
            }
        }

        if ( $post->post_type === 'post' ) {
            $type = 'article';
        }
    } elseif ( is_home() ) {
        // Posts archive (/notes/).
        $posts_page_id = (int) get_option( 'page_for_posts' );
        if ( $posts_page_id ) {
            $posts_page = get_post( $posts_page_id );
            $title = wp_strip_all_tags( $posts_page->post_title ) . ' — ' . get_bloginfo( 'name' );
            $description = $posts_page->post_excerpt ?: 'Field thoughts on modern web work from New Blood.';
            $url = get_permalink( $posts_page_id );
        }
    } elseif ( is_front_page() ) {
        $title = get_bloginfo( 'name' ) . ' — ' . get_bloginfo( 'description' );
        $description = get_bloginfo( 'description' );
        $url = home_url( '/' );
    } else {
        $title = wp_title( '', false );
        $description = get_bloginfo( 'description' );
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $url = home_url( $request_uri );
    }

    // Output tags.
    if ( $description ) {
        echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
    }
    if ( $title && ! is_front_page() ) {
        // Don't override <title> tag from theme; just OG title.
    }
    echo '<meta property="og:type" content="' . esc_attr( $type ) . '" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
    if ( $description ) {
        echo '<meta property="og:description" content="' . esc_attr( $description ) . '" />' . "\n";
    }
    if ( $url ) {
        echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
    }
    if ( $image ) {
        echo '<meta property="og:image" content="' . esc_url( $image ) . '" />' . "\n";
    }
    echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '" />' . "\n";
    if ( $description ) {
        echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '" />' . "\n";
    }
    if ( $image ) {
        echo '<meta name="twitter:image" content="' . esc_url( $image ) . '" />' . "\n";
    }

    // JSON-LD Article schema on single Notes posts.
    if ( is_singular( 'post' ) ) {
        global $post;
        $published = get_the_date( 'c', $post );
        $modified  = get_the_modified_date( 'c', $post );
        $ld = array(
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => $title,
            'description'   => $description,
            'datePublished' => $published,
            'dateModified'  => $modified,
            'author'        => array( '@type' => 'Organization', 'name' => 'New Blood, Inc.' ),
            'publisher'     => array( '@type' => 'Organization', 'name' => 'New Blood, Inc.' ),
            'mainEntityOfPage' => array( '@type' => 'WebPage', '@id' => $url ),
        );
        if ( $image ) {
            $ld['image'] = $image;
        }
        echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }
}
add_action( 'wp_head', 'newblood_seo_head', 5 );
```

Note: WP core auto-discovers and emits the RSS `<link rel="alternate">` tag in `<head>` via `feed_links()` — this is enabled by `add_theme_support( 'automatic-feed-links' )`. Confirm whether it's already enabled (it's part of WP's default theme support but verify):

- [ ] **Step 2: Confirm automatic-feed-links theme support**

Edit `functions.php` and add `add_theme_support( 'automatic-feed-links' );` inside the existing `newblood_setup()` function (around the current `add_theme_support` calls). Final state:

```php
function newblood_setup() {
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'automatic-feed-links' );
}
```

- [ ] **Step 3: PHP syntax check**

```
php -l wp-content/themes/newblood/functions.php
```
Expected: `No syntax errors detected`.

- [ ] **Step 4: Browser check — head tags output**

Visit `http://newblood.test/` and view source. Confirm `<head>` contains:
- `<meta name="description" content="…" />`
- `<meta property="og:type" content="website" />`
- `<meta property="og:title" …>`
- `<meta name="twitter:card" …>`
- `<link rel="alternate" type="application/rss+xml" …>` (from `automatic-feed-links`)

Visit `http://newblood.test/?p=<PLACEHOLDER_POST_ID>` (logged in to see drafts) and view source. Additionally confirm:
- `<meta property="og:type" content="article" />`
- `<meta property="og:image" content="…featured image URL…" />`
- `<script type="application/ld+json">…@type Article…</script>` block

- [ ] **Step 5: Commit**

```
git add wp-content/themes/newblood/functions.php
git commit -m "$(cat <<'EOF'
feat(notes): add SEO head — meta, OG, Twitter Card, JSON-LD

Per-page meta description and Open Graph tags. Notes posts
emit full Article JSON-LD with Organization as author. RSS
auto-discovery via core's automatic-feed-links support.

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
EOF
)"
```

---

## Task 15: Pre-launch QA — exercise the full launch-gate flip and revert

**Files:**
- No file changes during QA. May briefly toggle `NB_NOTES_PUBLIC` for verification (revert before finishing the task).

This task validates that the whole system behaves correctly in both the pre-launch state and the post-launch state.

- [ ] **Step 1: Pre-launch state — confirm everything is hidden from guests**

Ensure `NB_NOTES_PUBLIC = false` in `functions.php` and all three placeholder posts are drafts.

In an **incognito window** (logged out), visit each:

| URL | Expected |
|---|---|
| `http://newblood.test/` | Homepage renders normally. NO "From Notes" section between testimonial and CTA. NO "Notes" link in primary nav or footer. |
| `http://newblood.test/notes/` | Renders the Notes index header, then the empty-state paragraph ("No notes yet — first one is coming."), then the CTA. No card grid (no published posts). |
| `http://newblood.test/services/` | Renders normally. No "Related notes" rails appear in any service block. |
| `http://newblood.test/about/` | Renders normally, no regressions. |

- [ ] **Step 2: Pre-launch state — confirm logged-in admin can see drafts**

In a regular (logged-in) window, visit `http://newblood.test/notes/`.
Expected: card grid shows the three placeholder posts (they're drafts but visible to logged-in admins via WP's default behavior).

- [ ] **Step 3: Simulate launch — publish one placeholder, flip the constant**

```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post update <PLACEHOLDER_ID_1> --post_status=publish
```

Edit `functions.php`, change `define( 'NB_NOTES_PUBLIC', false );` → `true`. Save (filemtime cache-busts CSS automatically).

- [ ] **Step 4: Post-launch state — verify all surfaces light up**

In an **incognito window** (logged out):

| URL | Expected |
|---|---|
| `http://newblood.test/` | "From Notes." section appears between testimonial and CTA, with the published post as a single full-width card. "Notes" link appears in primary nav (between Work and Pricing) and in footer. |
| `http://newblood.test/notes/` | Card grid shows ONE published post card. No drafts visible. Header and CTA in place. |
| `http://newblood.test/notes/<post-slug>/` | Single post page: hero with featured image, title, badge + date + reading time meta, body, "More notes" rail (renders nothing or 0 cards because no OTHER published posts exist), then CTA. |
| `http://newblood.test/services/` | Build (or whichever service's category matched the published post) shows a "Related notes" rail at the bottom of its block. Other services show no rail. |

- [ ] **Step 5: Revert to pre-launch state**

```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post update <PLACEHOLDER_ID_1> --post_status=draft
```

Edit `functions.php`, change `define( 'NB_NOTES_PUBLIC', true );` → `false`. Save.

Reload the homepage in incognito — confirm everything is hidden again.

- [ ] **Step 6: Final commit (if any uncommitted state)**

The QA toggle of `NB_NOTES_PUBLIC` in `functions.php` should already be reverted to `false` and the file should match the committed state from Task 14. Confirm with:
```
git status
git diff
```
Expected: clean working tree, no diff. If there's a diff, restore the `false` state and verify.

If everything is clean, the implementation is complete. No commit needed for this task.

- [ ] **Step 7: Update redesign status memory**

After all tasks land, update `~/.claude/projects/-Users-jeremyoms-Herd-newblood/memory/project_redesign_status.md` to note: Notes section infrastructure complete, public flip gated on 3 real posts via `NB_NOTES_PUBLIC` constant in `functions.php`. This is a memory update, not a code commit.

---

## Public launch checklist (for Jeremy, post-implementation)

Once three real Notes posts are written and ready:

1. Publish the three real posts via the WP block editor — each must have:
   - A custom (non-stock-feeling) featured image
   - A populated `post_excerpt` (1–2 line dek for the card)
   - One assigned category from the finalized taxonomy

2. Optionally finalize the category taxonomy — adjust `Practice / Theory / Tools` if real content suggests different cuts. Add/rename via WP admin or WP-CLI.

3. Optionally finalize the index dek copy in `templates/home.html` (replace placeholder dek).

4. Optionally configure the category-to-service mapping in `patterns/related-notes.php` if you want each service to surface a specific category instead of the latest overall. (Current implementation is generic-latest; spec allows for the per-service mapping as a follow-up.)

5. Flip `NB_NOTES_PUBLIC` in `functions.php` from `false` to `true`.

6. Deploy via SFTP to Nexcess (per `~/.claude/projects/-Users-jeremyoms-Herd-newblood/memory/reference_production_server.md`).

7. Verify production: visit `/notes/` and the homepage — confirm Notes is live and the section looks healthy with three published posts.
