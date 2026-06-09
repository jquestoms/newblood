# Redesign → Production Deploy Runbook

**Date:** 2026-06-08
**Target:** newblood.com on Nexcess (SSH + SFTP available; WP-CLI on server)
**Source:** local Herd clone at newblood.test, branch `feature/redesign`

## ✅ LAUNCHED 2026-06-08 — all steps complete & verified

newblood theme activated on production; all pages render on the new theme; nav,
images, and product styling confirmed live. **Commerce intact** (997 orders, 22
subscriptions, latest order 6844 untouched). New prod page IDs (all > 6844, no
order collision): services **6846**, work **6847**, case studies **6848**
(mikes), **6849** (ca-lindman), **6850** (57cards), **6851** (newfoodcenter);
product 5454 featured image = attachment **6852**. about/contact/pricing updated
in place (4529/4466/5142). Cutover ran via `nbdeploy/cutover.sh` on the server.

**Post-launch fix (done):** site logo didn't migrate — it lives in the
`site_logo` option + `custom_logo` theme_mod (DB, attachment 6715 locally), not
in theme files or page content. Re-registered the uploaded logo file as prod
attachment **6853** and set both options. This also fixed nav alignment (header
is space-between: no logo → nav collapsed left). *Gotcha for future block-theme
deploys: migrate `site_logo`/`custom_logo` explicitly.*

**Post-launch review fixes (done 2026-06-09):**
- **Contact form** — `contact-form.php` hardcoded `[wpforms id="6746"]` (6746 = an
  order on prod). Added a render-time `[nb_contact_form]` shortcode that resolves
  the "Contact Form" by **title** (portable), and migrated the form to prod
  (new id 6854). *Two gotchas hit: (a) prod WPForms (1.9.8.2) ≠ local (1.9.3.2),
  and (b) the form JSON dumped via `wp post get … > file` was contaminated —
  on this install WP-CLI deprecation notices print to **stdout**, so `2>/dev/null`
  doesn't strip them; dump form/option JSON via PHP `file_put_contents` instead.*
- **Stale homepage "From Notes"** — legacy 2013–2018 **posts** (not just pages)
  were still published, feeding `[nb_latest_note]`. Unpublished all 7; the
  section now collapses (shortcode returns '' when no published post).
- **Footer contact info** — restored mail@newblood.com + 410-685-2314 + Loma
  Linda, CA into the footer Connect column (was a classic-theme text widget).
- **Footer legibility** — site-title + column headings rendered dark. *Real cause
  (confirmed via headless render, not just CSS inspection): the text was already
  white but COVERED by the `.nb-footer::before` blend gradient (z-index:0), which
  overlaps the footer's top row.* Fix = `.nb-footer > *{position:relative;z-index:1}`
  (plus kept the explicit white title / brighter headings). **Lesson: a decorative
  `::before`/`::after` blend at a section's top edge will overlay any content that
  sits there unless that content is z-lifted — same root cause as the case-study
  faded line and the footer. When something "looks wrong," render it headless and
  read computed styles AND pixels; don't trust CSS-file inspection alone.**
- **Case-study green blob** — `page-header.php` renders `post_excerpt` as the
  green eyebrow (excerptLength 100). `wp post create` didn't carry `post_excerpt`,
  so prod auto-generated the eyebrow from body content (giant green run-on).
  Migrated all page excerpts. *Gotcha: `post_excerpt` is separate from
  `post_content` — migrate it explicitly.*
- **Case-study faded first line** — the `.nb-gradient-section::before` blend
  (z-index:0) painted over the body's first line because body sections (unlike
  `.nb-dot-grid`) didn't lift content above it, and the block's inline top padding
  wasn't emitting. Fixed with `.entry-content .nb-gradient-section > *{position:
  relative;z-index:1}` + top padding on the first body section.

**Classic→block migration gotcha checklist (what selective migration strands):**
global options/theme-mods (logo `site_logo`/`custom_logo`, favicon `site_icon`),
hardcoded IDs in patterns (nav refs, WPForms form ids → resolve by title),
widget-area content, dynamic blocks pulling stale data (latest-note), and
per-theme custom CSS. ✅ all accounted for.

**Remaining follow-ups:** mobile/cross-browser QA · test a WooCommerce checkout ·
301 `/case-studies/` → `/work/` (+ other legacy URLs) via Yoast · Yoast
titles/meta for new pages · **confirm footer email/phone are current** · remove
server staging dir `/home/aaa2f02d/nbdeploy/`.

## Context & constraints

We're launching the `feature/redesign` block theme to a **live store**. Decisions made:

- **Production has ongoing commerce** (subscription renewals firing via Action
  Scheduler, plus historical orders/customers). These must be preserved.
- **No full-DB push.** Content and commerce share one database; overwriting it
  would destroy live orders/subscriptions/customers and the renewal scheduler.
- **Stay live** — no maintenance window (only renewal orders land, no
  interactive checkout traffic to freeze).
- **Straight to production** (no staging rehearsal) → a **full backup first** is
  the non-negotiable safety net, and the theme switch is reversible.

## What moves, and how

| Layer | Source of truth | Method | Touches commerce? |
|---|---|---|---|
| Theme (templates, patterns, parts, assets, theme.json, functions.php) | local files | `rsync` | No |
| New image **files** | local files | `rsync` (additive, no `--delete`) | No |
| Page/post **content** + new attachments + nav | local DB | selective WP-CLI over SSH | No |
| Orders, subscriptions, customers, payment tokens, Action Scheduler, WC sessions, stock | **production DB** | **left untouched** | — |

The entire *visual* redesign is file-based (`wp_template`/`wp_template_part`/
`wp_block` in DB = 0), so rsync + theme activation delivers the look. Only page
**content** (patterns embedded in `post_content`) and **new pages** need DB work.

## ⚠️ Two critical gotchas (the whole reason this is a runbook)

WordPress shares one ID counter across all post types (orders, pages,
attachments are all `wp_posts` rows). The local clone stopped at **order 6726**;
everything the redesign created since sits at IDs that on production are **live
orders**:

1. **New content collides with production orders.** Local page `6761`
   (`case-study-newfoodcenter`) = production **order #6761** (Paul Cummings, Jul
   2025). Same for pages 6712/6713/6740/6741/6742/6748/6754 and attachments
   6747–6762. → **Never migrate these by ID.** Create them fresh on production
   (new IDs) and re-point references.
2. **Nav refs are hardcoded and collide.** `parts/header.html` references
   `wp:navigation {"ref":6737}`; `footer.html` references `6738`/`6739`. On
   production those IDs are orders. → Nav must be made portable (see Step 5).

### Production state (confirmed 2026-06-08 via WP-CLI)

- **Active theme is `grandportfolio` (a CLASSIC theme), not a block theme.** This
  cutover is classic → block. Implications: prod page content was authored for a
  classic theme; non-front pages need their content replaced with the redesign's
  block content. Classic **menus** are in use (no `wp_navigation` posts on prod) —
  our inlined nav (header/footer parts) fully covers this, so **no nav migration
  needed**. Confirm `custom_logo` + site title survive the switch (used by
  `wp:site-logo`/`wp:site-title`).
- **Latest prod order ID = 6844** → local redesign IDs 6712–6762 are live orders
  on prod. Collision confirmed; create new content fresh.
- **Product 5454 exists on prod** (QB Migration) → featured-image update by ID safe.
- **Prod-only legacy pages** exist that local doesn't (LLU soccer 2017–2021,
  photography, `discovery-workshop-evaluation-form`, old `case-studies` (4610),
  `read-our-recent-updates-and-ideas`, etc.). Under the block theme they'll render
  via `page.html` with old classic content (unstyled, possibly ugly). **Decide at
  launch:** unpublish, redirect, or leave. Not blockers; flag for a cleanup pass.

### Page classification

- **Pre-existing (confirmed on prod, same IDs — update in place by *slug*):**
  `home` (4647), `about` (4529), `contact` (4466), `pricing` (5142).
  WC pages `cart`/`checkout`/`my-account` (4999–5001): **leave alone**.
- Note: prod has **no `services`/`work`/`notes`** pages → those are create-fresh.
- **New (6700s, collide — create fresh on prod):** `services`, `work`, and case
  studies `case-study-mikes-master-classes`, `case-study-ca-lindman`,
  `case-study-57cards`, `case-study-newfoodcenter`.
- **Withheld (do NOT create/feature on prod):** `case-study-overhead-door`
  (page 6741, set to draft locally) — Overhead Door / ohdbolt.com is in active
  sales discussion; intentionally unhighlighted. Already removed from
  `portfolio-grid.php` and `social-proof.php`. Republish + re-add the card when
  the relationship firms up.
- **Notes** (`/notes` page + 10 posts): launch-gated, posts are drafts —
  **defer** to a later pass unless launching Notes now.

---

## Pre-flight

> `PROD` = `ssh -p 22 aaa2f02d_1@588b7956f6.nxcli.net` (creds in memory
> `reference_production_server`). `SITE` = `/home/aaa2f02d/public_html`.
> WP-CLI confirmed at `/usr/local/bin/wp`.

0. **Confirm access & tooling** — ✅ DONE 2026-06-08. SSH reachable, WP-CLI
   present, WP root = `/home/aaa2f02d/public_html`.
1. **Full backup (files + DB).** ✅ DONE 2026-06-08 — Nexcess full backup
   completed (files + DB) and **downloaded** locally. Restore path: Nexcess
   Client Portal → Backups → restore. (Optional later: independent
   `wp db export` once SSH is confirmed, as a second portable copy.)
2. **Inventory production** (so we map slugs→prod IDs and see what already exists):
   ```
   ssh PROD 'cd SITE && wp post list --post_type=page --fields=ID,post_name,post_status --posts_per_page=50'
   ssh PROD 'cd SITE && wp theme list && wp option get template'
   ssh PROD 'cd SITE && wp post list --post_type=wp_navigation --fields=ID,post_title'
   ```

---

## Deploy steps

### Step 1 — rsync the theme (files only; not active yet) — ✅ DONE 2026-06-08
Theme mirrored to `SITE/wp-content/themes/newblood/` (inactive; grandportfolio
still live). Command used (sshpass auth, no key on this host):
```
SSHPASS=… sshpass -e rsync -az --delete --exclude='.DS_Store' \
  -e "ssh -p 22" \
  /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/ \
  aaa2f02d_1@588b7956f6.nxcli.net:/home/aaa2f02d/public_html/wp-content/themes/newblood/
```

**Also ✅ DONE: legacy pages unpublished** — 6196, 5643, 5250, 5215, 5205, 2,
4641, 4610 (old `case-studies`), 4462 set to `draft`. Remaining published:
pricing, cart, checkout, my-account, home, about, contact. *(Consider 301s for
any legacy URL with backlinks — esp. `/case-studies/` → `/work/` — via Yoast.)*

### Step 2 — rsync new image files — ✅ DONE 2026-06-08
172 new image files pushed (additive, all brand-new — zero existing files
overwritten). Excluded `wpforms/` (stale local plugin cache) and `.DS_Store`.
Dry-run reviewed first. Command:
```
SSHPASS=… sshpass -e rsync -az --exclude='.DS_Store' --exclude='wpforms/' \
  -e "ssh -p 22" \
  /Users/jeremyoms/Herd/newblood/wp-content/uploads/ \
  aaa2f02d_1@588b7956f6.nxcli.net:/home/aaa2f02d/public_html/wp-content/uploads/
```
Note: in-content/pattern images reference files by root-relative URL, so the
files alone make them render. Only **featured images** (Step 6) additionally
need attachment records on prod.

### Step 3 — update pre-existing pages by slug
For each of `home`, `about`, `contact`, `pricing`: export local content, copy
up, update the **prod** page resolved by slug (not ID):
```
# local: dump content
wp post get 4647 --field=content > /tmp/home.html      # repeat per page
rsync /tmp/home.html PROD:SITE/tmp/

# prod: update the same-slug page in place
ssh PROD 'cd SITE && PID=$(wp post list --post_type=page --name=home --field=ID) \
  && wp post update $PID --post_content="$(cat tmp/home.html)"'
```
> Home is template-driven (`front-page.html`), so its content may be minimal —
> verify whether it even needs updating.

### Step 4 — create new pages on production (fresh IDs)
For `services`, `work`, and each case study — create on prod, capturing the new
ID. If a slug already exists on prod, `update` instead of `create`:
```
ssh PROD 'cd SITE && wp post create \
  --post_type=page --post_status=publish \
  --post_title="Services" --post_name="services" \
  --post_content="$(cat tmp/services.html)" --porcelain'   # prints new ID
```
Record the new prod IDs — Steps 5–6 reference them.

### Step 5 — navigation ✅ DONE (2026-06-08)
The nav was inlined into `parts/header.html` and `parts/footer.html` as
URL/slug-based `wp:navigation-link` blocks with **no `ref`**, so it's portable.
Nothing to do at deploy time beyond the Step 1 theme rsync — the menus render
from the files. (The orphaned local `wp_navigation` posts 6737–6739 can be
ignored.) Links resolve by **permalink/slug**, stable across environments.

### Step 6 — reconcile featured images (by slug → new attachment ID)
Featured images are stored as `_thumbnail_id` (an ID), so they must point at the
freshly-imported prod attachments:
```
# product 5454 exists on prod (old ID, safe):
ssh PROD 'cd SITE && wp media import tmp/web-hosting-business-class.png \
  --post_id=5454 --featured_image --title="Web Hosting: Business Class"'

# case-study pages: target the NEW prod page IDs from Step 4
ssh PROD 'cd SITE && wp media import tmp/newfoodcenter.png \
  --post_id=<new_prod_casestudy_id> --featured_image'
```

### Step 7 — set front page & flush
```
ssh PROD 'cd SITE && wp option update show_on_front page'
ssh PROD 'cd SITE && wp option update page_on_front $(wp post list --post_type=page --name=home --field=ID)'
```

### Step 8 — THE CUTOVER: activate the theme
This is the visible switch. Everything above is staged invisibly first.
```
ssh PROD 'cd SITE && wp theme activate newblood'
ssh PROD 'cd SITE && wp rewrite flush'
ssh PROD 'cd SITE && wp cache flush'        # Redis object cache
# clear Hummingbird page cache (plugin UI or: wp hummingbird cache clear all)
```

---

## Verification (immediately after Step 8)

- Front page, Services, Work, Pricing, About, Contact, each case study render
  with the redesign and correct images.
- **Header + footer navigation render and link correctly** (the gotcha-2 check).
- A **WooCommerce single product** (e.g. the Web Care Plan / QB product) renders
  with the new styling and new image.
- **Commerce intact:** `wp post list --post_type=shop_subscription` count
  unchanged; a known recent order still opens in wp-admin; Action Scheduler has
  pending renewal actions (`wp action-scheduler list --status=pending`).
- No `newblood.test` strings leaked: `wp search-replace 'newblood.test'
  'newblood.com' --dry-run` reports the count (run for real only if >0 and
  confined to content, never the commerce tables).
- Spot-check on mobile width.

## Rollback

- **Visual only:** `wp theme activate <old-theme>` instantly reverts the look;
  content changes remain but are harmless under the old theme.
- **Full:** restore `backup-pre-redesign-*.sql` (Step 1). Because we never
  touched commerce tables, a content-only mistake rarely needs this — but it's
  there.

## Notes / deferred

- Notes section (page + 10 draft posts) is launch-gated — separate pass.
- Yoast `og:image` on products still points at legacy graphics (per prior
  decision, left as-is).
- Consider permanently inlining nav (Step 5) to remove cross-environment
  `ref`-ID fragility for all future deploys.
```
