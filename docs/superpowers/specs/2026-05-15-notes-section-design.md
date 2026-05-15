# Notes section — design

**Date:** 2026-05-15
**Author:** Jeremy + Claude
**Scope:** New `/notes/` section (index + single-post template + homepage hook + nav/footer integration + services cross-linking)

## Problem

The legacy WP blog was retired on 2026-05-04 — seven 2013–2018 posts flipped to draft and archived under `docs/voice-samples/` as voice references. The site currently has no public articles surface. `/blog/` returns 404 by design, no "Notes/Blog/Articles" link exists in primary nav or footer, and no archive template exists in the theme.

Going forward, Jeremy intends to publish short essays on modern web application development theory — a practice he is actively developing — roughly monthly. The opportunity is twofold: (1) a thought-leadership surface that warm prospects can use to validate that the agency thinks deeply about modern web work, and (2) a long-tail SEO asset that earns search traffic on topics adjacent to the services lineup. The challenge is preserving the deliberative, musical brand voice while supporting a real publishing cadence, and giving the section enough visual investment that it amplifies rather than dilutes the redesigned site's typographic ambition.

## Solution

A new section called **Notes** at `/notes/`, designed as a small editorial publication rather than a conventional agency blog. Card-grid index with custom imagery on every post, refined single-post template, a "Latest from Notes" hook on the homepage, primary-nav inclusion between Work and Pricing, and contextual cross-links from `/services/`. Single author, no comments, no tags, categories only, no bylines (the brand is the author).

The section is built in development with placeholder draft posts immediately, but the public-facing nav and footer links and the homepage hook stay hidden behind a `NB_NOTES_PUBLIC` constant in `functions.php` until Jeremy has published three real posts. This protects launch credibility — a thin index hurts more than no index — without slowing implementation.

## Decisions locked during brainstorming

| Decision | Choice |
|---|---|
| **Purpose** | Credibility-first, SEO-aware. Voice stays fully musical/deliberative; SEO is a topic-selection tiebreaker, not a master |
| **Cadence** | Roughly monthly |
| **Name / URL** | `Notes` / `/notes/` |
| **Index visual style** | Approach B — image-led card grid with custom AI-assisted imagery per post |
| **Primary nav placement** | Between Work and Pricing: `Services / Work / Notes / Pricing / About / Get Started` |
| **Homepage hook** | Single-card "Latest from Notes" section, between testimonial and CTA |
| **Launch gating** | Public flip gated on three published posts; controlled by `NB_NOTES_PUBLIC` constant |
| **Taxonomy** | Categories only (no tags). 3–5 initial categories. One primary per post, shown as a badge on cards |
| **Bylines / comments / share buttons** | None of the above |

## Information architecture

### URLs

- `/notes/` — index, card grid of all published posts, reverse-chronological, 12 per page
- `/notes/<slug>/` — single post
- `/notes/category/<slug>/` — category archive (free from WP; not promoted in nav, just available). **Note:** without a dedicated `category.html` or `archive.html` template, category archives fall back to the minimal `index.html`. They'll work as URLs and stay indexable, but won't carry the Notes-index card grid styling. Acceptable for launch since these pages aren't promoted in nav; revisit if category traffic warrants a styled archive.
- `/notes/feed/` — RSS feed (free from WP)

A WordPress page titled "Notes" with slug `notes` is created and assigned via Settings → Reading → "Posts page". This gives the archive a real URL, a friendly title for nav/breadcrumbs, and a `post_excerpt` field we can populate as the index's intro dek.

### Templates

- `templates/home.html` — **new file.** WP template hierarchy renders the posts page (the page assigned to Settings → Reading → "Posts page") via `home.html` first, falling back to `index.html`. Using `home.html` specifically targets `/notes/` and leaves `index.html` as a true catch-all fallback for any future archive surfaces (search, category archives, etc.) without coupling them to the Notes design.
- `templates/index.html` — left as-is (current minimal post-content renderer; it's the deep fallback)
- `templates/single.html` — refined to add featured-image hero, category badge, reading time, and a "More notes" rail before the CTA. Existing structure is mostly retained.

### Navigation

**Primary nav** — final order:
```
Services / Work / Notes / Pricing / About / Get Started
```

**Footer "Company" column** — add "Notes" link between Work and any existing items.

Both menu items receive a `menu-item--notes` class (added via a `wp_nav_menu_objects` filter in `functions.php`) so CSS can hide them when pre-launch.

## Index page layout (`/notes/`)

### Structure (top to bottom)

1. **Page header** — reuses `newblood/page-header` pattern. Pulls title "Notes" from the WP page and a one-sentence dek from the page's excerpt field. Same gradient + dot-grid + green-period accent as other interior page headers.
2. **Card grid** — new pattern `newblood/notes-index`, contains the post loop
3. **CTA** — `newblood/cta` pattern at the bottom

### Card composition (`.nb-note-card`)

Top to bottom inside each card:

- **Featured image** — 3:2 aspect ratio (`object-fit: cover`), fills the top of the card edge-to-edge. Hard-required (see Data model below).
- **Category badge** — small green-tinted pill, between image and title. Single category per post (the primary/first one). Text uppercase, letter-spaced ~0.08em, small (~0.7rem). Background `rgba(74, 222, 128, 0.12)`, text `#4ade80`. Sits inside ~16px horizontal padding on the card body.
- **Title** — h2-scale, bold, musical voice. Sits below the badge with generous breathing room.
- **Excerpt / dek** — 1–2 lines max. Sourced from `post_excerpt`; falls back to WP's auto-generated first-paragraph excerpt when blank.
- **Meta row** — small, muted, monospace-feeling tracking: `May 15, 2026 · 4 min read`. Reading time = `ceil(str_word_count(strip_tags($post->post_content)) / 200)`.

The entire card is wrapped in an `<a>` to the post permalink. Hover treatment:
- Card lifts ~4px (`transform: translateY(-4px)`, `transition: transform 280ms ease`)
- Image scales subtly (`transform: scale(1.03)`, `transition: transform 600ms ease`)
- Title color shifts to brand green (`#4ade80`)
- Matches the existing `interactive-cards.js` visual language; no new JS needed if pure CSS hover works (it does)

### Grid behavior

- Desktop (≥960px): 2 columns, ~48px column gap, ~64px row gap
- Tablet (640–959px): 2 columns, ~32px gaps
- Mobile (<640px): 1 column, ~48px row gap, cards full-width
- Cards revealed on scroll using existing `nb-stagger` class (animations.css)

### Pagination

WordPress's built-in pagination (`<!-- wp:query-pagination -->`) at 12 posts per page. Styled to match the rest of the site's button conventions but quiet — pagination only appears once posts exceed 12 (roughly year two at the planned cadence).

## Single post template (`single.html`)

The existing template is the starting point. Changes:

### Hero block (refined)

Order inside the gradient hero group:

1. **Featured image** — full-width inside the constrained 720px column, 3:2 aspect ratio, rounded corners (~12px), matches the index card's image treatment so the transition from index → single feels continuous.
2. **Post title** — existing treatment retained (`nb-hero-headline`, `clamp(2rem, 4vw, 3rem)`)
3. **Meta row** — replaces the existing standalone date. New format: `Practice · May 15, 2026 · 4 min read`. Category name comes from the primary category; date and reading time as in the index.

### Body content

Unchanged — post content in the `nb-gradient-section` group, constrained to 720px. Proven readable measure.

### "More notes" rail

New pattern `newblood/more-notes`. Inserted between the body content and the CTA. Layout:

- Small section label "More notes" with green-period accent
- 3-card mini-grid using the same `.nb-note-card` component as the index
- Query: 3 most recent published posts excluding the current one
- Falls back gracefully — renders nothing when fewer than 1 other post exists, renders 1–2 cards when 1–2 are available

### CTA

Unchanged — `newblood/cta` pattern at the bottom of the page.

## Homepage hook (`newblood/latest-note`)

### Placement

On the homepage flow, inserted **between testimonial and CTA**. Final homepage order:

```
hero → statement → social-proof → services-cards → portfolio-grid →
testimonial → latest-note → cta
```

This placement is deliberate: client voice (social proof) → agency voice (latest note) → conversion (CTA). Inserting the hook before testimonial would weaken the conversion arc; placing it after the CTA would bury it.

### Layout

A single full-width card showing the most recent published post.

- **Section label** above the card: small "From Notes" text with green-period accent, plus a right-aligned "View all →" link to `/notes/`
- **Card** — densified version of `.nb-note-card`, NOT a duplicate of the index treatment:
  - Desktop: featured image left (~40% width), content right (~60%). Generous internal padding.
  - Mobile: stacks vertically, image on top
  - Content side: category badge, title (slightly larger than index), excerpt (up to 3 lines), meta row
- New CSS component `.nb-latest-note-card` for the densified layout
- Whole-card link to the post

### Graceful empty state

The pattern queries `wp_get_recent_posts(['post_status' => 'publish', 'numberposts' => 1])`. If the result is empty, the pattern renders nothing — no header, no card, no empty slot on the homepage. This is the second guard alongside the `NB_NOTES_PUBLIC` constant; it means even if the constant were forgotten, the homepage wouldn't show a broken slot.

## Cross-linking with /services/

Each service section in `services-detail.php` may optionally receive a "Related notes" rail at the bottom of its block, between the bullet list and the next service h2.

- 1–2 post cards from a matching category (configured per service via a small mapping table in `functions.php`: e.g., Build → `practice`, Tune → `tools`, etc. — final mapping set when categories are finalized)
- Uses a compact horizontal `.nb-note-card-mini` variant (smaller image, single-row layout) — separate CSS rule, same DOM shape
- Pattern: `newblood/related-notes` (accepts a `category` attribute)
- **Renders nothing when fewer than 1 matching post exists** — so /services/ shows no Related notes rails on launch day, then they fill in organically as posts publish

This is the meaningful SEO play in the design: every Note page accumulates internal links from the high-authority /services/ page over time, lifting ranking signal.

Inline links inside service body copy ("more on this in *[note title]*") are explicitly **NOT** infrastructure — Jeremy adds them by hand to the pattern files as relevant posts publish.

## Data model

### Post type

Standard WordPress `post` post type. No custom post type needed — categories + standard fields are sufficient.

### Featured image

Hard-required for the design. Enforcement:

- `admin_notices` hook in `functions.php` shows a yellow warning banner on the post-edit screen when a published post has no featured image: "Notes posts require a featured image. This post will appear with a broken card on the index until one is added."
- Not a hard block (don't prevent publish) — soft warning, since you'll be the only author and won't fight your own UI

### Excerpt

Use `post_excerpt` field. Populating it manually is recommended for tight, hand-written deks on the index and homepage hook. WP's auto-fallback (first paragraph trimmed) is the safety net.

### Categories

- Initial taxonomy: **3–5 categories**, finalized when seed content takes shape. Working placeholders during development: `Practice / Theory / Tools`.
- Exactly one primary category per post. WP doesn't enforce primary-category natively without a plugin, so the convention is "first category alphabetically by ID" — and the badge logic picks `get_the_category($post->ID)[0]`. Jeremy assigns one category per post; if a post needs two, the first-by-ID rule still gives a deterministic primary.
- Category names appear in URLs (`/notes/category/<slug>/`) and badges. Worth picking carefully.

### Bylines

None displayed. Single author; the brand is the author.

## SEO plumbing

### Meta tags

- Per-post `<meta name="description">` from `post_excerpt` (or auto-generated fallback)
- Per-page descriptions:
  - Homepage: site tagline
  - `/notes/` index: from the WP page's excerpt
  - Single posts: from `post_excerpt`
- Open Graph + Twitter Card tags on every post — `og:type` = `article`, `og:image` = featured image (which is already the strongest visual for the post)

These are added via a small function in `functions.php` hooked into `wp_head`, since no SEO plugin is currently installed on the site.

### Structured data

JSON-LD `Article` schema injected into single-post `<head>`:

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "<post title>",
  "image": "<featured image URL>",
  "datePublished": "<ISO 8601>",
  "dateModified": "<ISO 8601>",
  "author": { "@type": "Organization", "name": "New Blood, Inc." },
  "publisher": { "@type": "Organization", "name": "New Blood, Inc." },
  "description": "<post excerpt>"
}
```

### Feeds and sitemap

- RSS feeds at `/feed/` and `/notes/feed/` come free from WP core
- `<link rel="alternate" type="application/rss+xml">` auto-discovery added to `<head>`
- Core WP sitemap at `/wp-sitemap.xml` verified to include posts (WP 5.5+ default behavior)

### Indexing during pre-launch

- Placeholder/seed posts stay as **drafts** during development → not public, not indexed
- `/notes/` URL stays reachable from day one — search engines crawl what they crawl, but the empty archive returns the page template normally
- No `noindex` flag needed; the draft-only content is the actual safety mechanism

## Launch gating

Two independent guards:

### Guard 1 — Posts stay drafts

Placeholder/seed posts are created during development with `post_status = 'draft'`. They render for Jeremy when logged in (so the index can be designed against real-looking content) but are invisible to guests.

When ready to launch, Jeremy publishes the three real posts via the WP editor — standard publish flow.

### Guard 2 — `NB_NOTES_PUBLIC` constant

In `functions.php`:

```php
define('NB_NOTES_PUBLIC', false);
```

Behavior when `false`:

- `body_class` filter adds `is-prelaunch` to `<body>`
- CSS rule: `body.is-prelaunch .menu-item--notes, body.is-prelaunch .footer-notes-link { display: none; }`
- `latest-note` pattern's render check is independent (it returns nothing if no published posts exist regardless of the constant)

Flip sequence at launch:

1. Publish posts #1, #2, #3 in the WP editor
2. Change `false` → `true` in `functions.php`
3. Deploy

No menu surgery, no nav reordering, no broken intermediate state.

## Explicitly out of scope

- Comments / Disqus / any discussion system
- Author bylines, author archives, date archives (`/2026/05/` etc.) — disabled or 301'd to `/notes/`
- Tags (categories only)
- Social share buttons (performance cost, low return on a credibility-first surface)
- Email subscribe / newsletter form (separate decision; revisit after launch if warranted)
- In-Notes search (12 posts/year doesn't need it)
- Multi-part series / collections (revisit when a series concept actually exists)
- Pagination format beyond WP's built-in 12-per-page (revisit only if archive ever grows large enough to feel cramped)
- Automatic inline cross-links from /services/ body copy (manual edits to pattern files, not infrastructure)

## Files added or changed

### Added

- `templates/home.html` — new posts-page template (renders `/notes/`)
- `patterns/notes-index.php` — index card grid pattern (slug `newblood/notes-index`)
- `patterns/latest-note.php` — homepage hook pattern (slug `newblood/latest-note`)
- `patterns/more-notes.php` — single-post footer rail (slug `newblood/more-notes`)
- `patterns/related-notes.php` — services-page rail (slug `newblood/related-notes`, accepts `category` attribute)

### Changed

- `templates/single.html` — featured-image hero, category badge, reading time, "More notes" rail
- `patterns/services-detail.php` — adds `related-notes` slot under each service
- `functions.php` — `NB_NOTES_PUBLIC` constant, `body_class` filter, `admin_notices` for missing featured images, `wp_head` meta + JSON-LD injection, `wp_nav_menu_objects` filter to tag menu items, primary-category helper
- `assets/css/patterns.css` — `.nb-note-card`, `.nb-note-card-mini`, `.nb-latest-note-card`, category badge, "More notes" rail, pre-launch hide rules
- WordPress: new page "Notes" assigned as Settings → Reading → Posts page; primary-nav menu and Footer Company menu both get a "Notes" item

### WP content

- 3 placeholder draft posts during development with realistic word counts so reading-time math renders correctly. Bodies can be Lorem ipsum but featured images should be real (so the visual treatment can be evaluated for real, not against gray boxes).

## Open items (Jeremy's call, not blockers)

- **Final category taxonomy** — `Practice / Theory / Tools` are working placeholders. Jeremy locks the real set when the first seed pieces take shape; the implementation handles 3–5 categories without changes.
- **Index dek copy** — placeholder *"Field thoughts on modern web work — what's actually working, what's hype, and what's worth your attention."* This needs a pass against the musical-voice spec before launch; the implementation doesn't depend on it.
- **Category-to-service mapping for related-notes** — finalized when categories are locked.
