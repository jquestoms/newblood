# 57Cards Case Study + Portfolio Update — Design Spec

**Status:** Brainstorm complete; pending Jeremy's review and implementation
**Date:** 2026-04-30
**Scope:** Publish a case study for the 57Cards.com performance + SEO engagement on newblood.com, surfaced from the existing portfolio showcase. Three coordinated changes: an image upload to the WP media library, an additional card in `patterns/portfolio-grid.php` plus a small revision to the showcase H2, and a new WP Page (`/case-study-57cards/`) created and populated programmatically via WP-CLI rather than the block editor.

Approval already on record:
- 57Cards has authorized public reference (the case study names them and ships specific PageSpeed numbers).
- Showcase H2 changes from *"Built with New Blood"* → *"Built and tuned with New Blood"* — accommodates Tune engagements alongside Build engagements without inventing a new section.
- Body copy lives in `post_content` (consistent with the three existing case studies: Mike's Master Classes #6740, Overhead Door #6741, C.A. Lindman #6742).
- Implementation uses WP-CLI to upload the image, create the page, and write `post_content` directly. No paste-into-block-editor step for Jeremy.

---

## Why this is worth shipping

The 57Cards engagement is the proof point for the new Tune service line (specced 2026-04-30, in the same wave). A live case study with a real client name, real PageSpeed numbers, and concrete technical detail does work the services-detail page can't: it shows the studio's craft on a real site, not a prose claim. It's the page that closes the next Tune buyer.

It also fits the redesign's broader thesis ("AI-augmented modern web dev agency") — the engagement was four hours of work that delivered Fortune-500-style perf consulting at small-business prices. Every Tune case study like this one quietly validates the brand thesis.

## Approved Body Copy (Source of Truth)

The full case study copy has been agreed in voice and is reproduced below verbatim. The implementation must match this exactly. Future copy edits should update this spec first, then the live `post_content`.

> # Modernizing 57Cards.com without a rebuild
>
> *Stack:* WordPress · Porto child theme · WooCommerce · 50+ active plugins
> *Engagement:* Performance and SEO refresh — no platform migration
>
> ## What we were asked to do
>
> 57Cards.com is a working e-commerce site with years of content, a thousand-plus product variants, real customer accounts, and an active order pipeline. The owner didn't want a rebuild. He wanted modern performance and a refreshed front-end without throwing away a stack that was working — just slower than it should be.
>
> This is the situation most established WordPress sites land in eventually. A premium theme bundled with a page builder, a payment plugin here, a security plugin there, three commercial plugins for things the theme already does, and over time the site is shipping seventy-plus render-blocking assets to render a homepage. The instinct is "we need to redo this." The right move, almost always, is surgical.
>
> ## What changed
>
> | Metric | Before | After |
> |---|---|---|
> | Mobile Performance | 61 | **72** |
> | Desktop Performance | 72 | **96** |
> | SEO | 100 | 100 |
> | Mobile LCP | 7.2s | 5.1s |
> | Render-blocking requests (homepage) | ~71 | ~38 |
>
> About four hours of investigation, implementation, four PageSpeed measurement rounds, and production deploys. No theme rewrite. No new plugins. The same content, the same admin, the same SEO history.
>
> ## What we actually did
>
> ### 1. Conditional asset enqueue, per page
>
> WordPress's biggest performance liability is global plugin enqueue: every plugin loads its CSS and JS on every page, "just in case." We built seven conditional templates — homepage, about, games, FAQ, care, blog, single product, shop archive — and gave each one a dedicated `*-enqueue.php` file that runs at priority 9999, after Porto, WPBakery, and Slider Revolution have finished registering, and dequeues anything that isn't relevant.
>
> A single dequeue file (`perf-dequeue.php`) strips ~40-50 render-blocking requests from the homepage alone:
>
> - WPBakery Page Builder front-end — we render with our own templates
> - Slider Revolution — not used outside the legacy homepage
> - WooCommerce front-end bundle on non-shop pages (cart, checkout, fragments, attribution, sourcebuster)
> - CleanTalk anti-spam, Mailchimp form CSS, fresh-framework, go_pricing, ShareThis — none present on the page they were loading on
> - Stripe Blocks checkout CSS, PayPal smart-button gateway CSS — gated to actual checkout pages
>
> ### 2. Late-stage suppression for plugins that cheat
>
> Some plugins re-enqueue assets after `wp_enqueue_scripts` is over. WooCommerce Blocks' Stripe support and Slider Revolution's global handler both do this during `the_content`; standard dequeue doesn't catch them. We added a `style_loader_tag` filter that runs at print time and drops the `<link>` tag entirely. Belt and suspenders.
>
> ### 3. Deferred non-critical CSS
>
> For stylesheets we couldn't fully drop — Porto's footer styles, contact widget, plugin styles bundled into the theme — we used the `media="print" onload="this.media='all'"` pattern to fetch them after first paint, with a `<noscript>` fallback for correctness. The browser stops blocking on them; the user gets pixels sooner.
>
> ### 4. Honest font loading
>
> We trimmed the preloaded font set down to exactly what's used above the fold: the Porto icon font, Font Awesome solid, Font Awesome brands. We added preconnect hints for `fonts.googleapis.com` and `fonts.gstatic.com` so the Playfair Display + Inter request starts earlier. We tried deferring Google Fonts and immediately reverted it when CLS regressed — performance work without measurement is just guessing.
>
> ### 5. Native LCP optimization
>
> The homepage hero is now a real `<img fetchpriority="high">` with native `srcset` and `sizes`, replacing an earlier `<link rel="preload">`. This lets the browser prioritize the image directly and lets WP Smush's CDN rewrite the URL consistently — preload hints don't always pick up CDN-rewritten URLs, which silently doubles the LCP fetch.
>
> ### 6. The admin side: blocking outbound calls
>
> A separate `57cards-perf` plugin blocks eight known-slow outbound HTTP calls during `wp-admin` requests, identified via Query Monitor. The worst offenders were Porto's license check (60-second timeout), WPMUDEV Hub analytics (15s), and fresh-framework's plain-HTTP phone-home over three retries — about thirty seconds of cumulative dashboard hang. Editors get a snappy admin without losing license functionality; those endpoints are the banner endpoints, not the update endpoints.
>
> ### 7. Surgical fixes for the side effects
>
> Aggressive dequeue creates new bugs. When we stripped `porto-header-shop` CSS on non-shop pages, the always-visible mini-cart icon broke because the `display:block` rule for `.minicart-icon` lived in that file. The fix wasn't to re-enqueue 20 KB of CSS — it was to inline the ~600 bytes of layout rules the cart icon actually needs. Net: still saving 95 percent of the bytes, no broken pixels.
>
> ## The philosophy
>
> You can ship modern performance from an aging WordPress install. You don't need a rebuild, you don't need a framework migration, you don't need to throw away a working WooCommerce store and the SEO history that's tied to it. What you need is:
>
> - A real measurement loop (Lighthouse, Query Monitor, Web Vitals) to find the actual costs
> - Conditional, scoped enqueue instead of global plugin sprawl
> - Inline critical CSS for what's above the fold; defer or drop the rest
> - A willingness to write 600 bytes of CSS instead of accepting 20 KB
> - Discipline about what you remove, with a verification step on the live site every time
>
> This is how we keep proven WordPress sites fast, modern, and intact — without the rebuild.

## Sales Surface Changes

### Change 1 — `patterns/portfolio-grid.php`

Two updates in one commit:

**1a. Showcase H2 rewrite.** Line 17 changes from:

> `<h2>Built with New Blood</h2>`

to:

> `<h2>Built and tuned with New Blood</h2>`

Sub-line at line 20 (*"Real projects. Modern design. Blazing performance."*) stays unchanged. The H2 change accommodates Tune engagements without inventing a separate section or relabeling the page.

**1b. Add the 57Cards card** as a new secondary card alongside Overhead Door and C.A. Lindman. The featured slot stays Mike's Master Classes. The grid becomes:

- Featured: Mike's Master Classes (E-Commerce · Education) — unchanged
- Secondary: Overhead Door Co. (Commercial · Service) — unchanged
- Secondary: C.A. Lindman (Construction · Restoration) — unchanged
- **Secondary: 57Cards (Performance · Tune Engagement)** ← new

The new card structure mirrors the existing Overhead Door / Lindman cards exactly (`nb-showcase-card` group → `nb-showcase-image` with the screenshot → `nb-showcase-info` with H3 + badge + tagline + "View Case Study →" link).

**Card content:**

- *Image:* `57cards-website-scan.png` (uploaded via WP-CLI; final src will be the WP-rendered uploads URL)
- *Image background color* (the placeholder color while the image loads): `#1f1814` (warm dark amber matching the photograph's tone, distinct from the existing card placeholders)
- *H3:* `57Cards`
- *Badge (className `nb-showcase-badge`):* `Performance · Tune Engagement`
- *Tagline (text-muted, 0.8125rem):* `A working WooCommerce store, made fast without a rebuild.`
- *Link:* `<a href="/case-study-57cards/" style="color:#4ade80;font-size:0.75rem;text-decoration:none">View Case Study →</a>`

The "Tune Engagement" badge is the visual signal that this card is a Tune project rather than a Build. Subtle but readable to anyone scanning the showcase.

### Change 2 — Image upload

Upload `/Users/jeremyoms/Herd/57cardsdev/57cards-website-scan.png` to the WP media library via WP-CLI. The image becomes available at `/wp-content/uploads/<year>/<month>/57cards-website-scan.png` with an attached attachment ID. The portfolio-grid card references the URL and ID using the same `wp:image` block format as the existing showcase cards.

The image is also displayed inside the case study body itself (see Change 3) using the same attachment ID so WP doesn't store two copies.

### Change 3 — `/case-study-57cards/` Page

A new WP Page is created via WP-CLI with:

- *Title:* `57Cards`
- *Excerpt* (used by the page-header pattern's post-excerpt label): `Performance · Tune Engagement`
- *Slug:* `case-study-57cards`
- *Status:* `publish`
- *Post content:* Two top-level patterns + the body content as Gutenberg block markup:
  1. The existing `<!-- wp:pattern {"slug":"newblood/page-header"} /-->` reference (renders the title bar at the top of the page using the page's title and excerpt)
  2. The body content — the case study sections from the approved copy above, rendered as native Gutenberg blocks (headings, paragraphs, lists, table, image, separator)

The body content is generated programmatically — no human paste step. The exact block markup is the implementation plan's responsibility, not this spec's. Authoring rules:

- Top-level body wraps in a `wp:group` with `align:"full"`, `nb-gradient-section`, `contentSize:"800px"` (matching the body width of other long-form prose patterns like about-story)
- Use `wp:heading` for `##` and `###` markdown levels (level 2 and 3 respectively); promote the page title to the page-header (handled by `wp:post-title` in the page-header pattern, not duplicated in body)
- Use `wp:paragraph` for prose
- Use `wp:list` for bullet lists
- Use `wp:table` for the **What changed** results table
- Render the screenshot once near the top, immediately after the *Stack/Engagement* meta lines, using `wp:image` referencing the uploaded attachment ID with `nb-case-screenshot` class for consistent border-radius / margin treatment (matching the existing case-study CSS at `patterns.css:320-331`)
- Code identifiers in body copy (filenames, function names, CSS values) use `<code>` inline
- Use `wp:separator` between major sections sparingly — the section headings carry enough hierarchy on their own

The case-study pattern (`patterns/case-study.php`) is **not modified** by this work — it already provides the page-header + body wrapper structure all four case studies use.

## Implementation Approach: WP-CLI

Per Jeremy's direction, all WP-side work happens via WP-CLI. Steps the implementation plan must cover:

1. `wp media import /Users/jeremyoms/Herd/57cardsdev/57cards-website-scan.png --title="57Cards homepage" --alt="57Cards website homepage with playing cards in warm lit setting" --porcelain` — returns the attachment ID. Capture this ID; it's used in both the portfolio-grid card and the case study body.
2. `wp post create --post_type=page --post_title="57Cards" --post_name=case-study-57cards --post_status=publish --post_excerpt="Performance · Tune Engagement" --post_content=<Gutenberg block markup>` — creates the page. Use a temporary file for `--post_content` since the markup is multi-line.
3. Edit `patterns/portfolio-grid.php` to add the new card and update the H2 (file edits, not WP-CLI).

WP-CLI memory limit needs `php -d memory_limit=512M` per the prior session's discovery, e.g.:

```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood <subcommand>
```

If the page already exists from a prior run (slug collision), the implementation should `wp post update` instead of `wp post create`. Idempotent re-runs are not required for v1, but a sanity check before `wp post create` (e.g., `wp post list --name=case-study-57cards`) is worth a step.

## Out of Scope for v1 (Explicit)

Deferred so they don't quietly creep in:

| Deferred | Earliest revisit |
|---|---|
| A separate "Tune work" subsection on the portfolio page | If/when there are 3+ Tune case studies to group |
| Linking from the services-detail Tune block to this case study | Worth doing in a follow-up commit; small change but should not bundle into this work |
| Case study image gallery (screenshots of admin tooling, before/after Lighthouse, etc.) | v2; one hero image is enough for v1 |
| Tune engagement badge styling at the showcase level (e.g., different border color) | v2 — the text badge is enough until we have multiple Tune cases |
| Authoring tooling for future case studies (e.g., a markdown-to-Gutenberg converter) | Premature; revisit when there are 5+ case studies |

## Editorial Rules for Future Case Studies

When future Tune (or Build) case studies are added:

1. The musical/compositional voice direction continues. New case study copy uses the Tier 1/2/3 palette from `2026-04-23-musical-voice-design.md`.
2. Always include a **What changed** results table near the top. Specific numbers are the headline proof point; their absence makes a case study feel like a brochure.
3. Honest "what we don't promise" framing belongs in services-detail, not in case studies. Case studies are about what *did* happen.
4. Always name the client by name (with permission) and link to a real URL. Anonymous case studies read as fabricated.
5. Use technical detail liberally in the body. Buyers reading a case study are pre-qualified to want depth; the services-detail page handles the simpler version.
6. Keep the philosophy section short and opinionated. It's the part that makes the studio's POV legible to a buyer who's deciding between vendors.

## Success Criteria

- The image is uploaded once to WP, referenced by attachment ID in two places.
- A new page exists at `/case-study-57cards/` with the approved copy rendered correctly via Gutenberg blocks.
- `/work` (or wherever portfolio-grid is embedded) shows four cards with the showcase H2 reading *"Built and tuned with New Blood."*
- Clicking "View Case Study →" on the 57Cards card lands on the new case study page with no broken layout, broken images, or missing sections.
- The case-study page renders the page-header pattern at top with title *"57Cards"* and excerpt label *"PERFORMANCE · TUNE ENGAGEMENT"* — matching the page-header pattern's existing behavior on the other three case studies.
- Removing the 57Cards card from portfolio-grid is a one-block deletion. Removing the case study page is a single `wp post delete` call. Reversibility is clean.
