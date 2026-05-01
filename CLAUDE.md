# newblood.com — codebase notes

Auto-loaded into every Claude Code session for this project. Captures durable codebase-level facts that any session needs to know up front.

## Project shape

- WordPress block theme at `wp-content/themes/newblood/` (slug: `newblood`)
- Local dev: Laravel Herd, served at `http://newblood.test`
- Production: Nexcess (SFTP). Deploys are SFTP-only — no CI/CD wired
- Git: `feature/redesign` is the active branch. `main` is stable
- Specs and plans live at `docs/superpowers/{specs,plans}/`
- Case study body markup lives at `docs/case-studies/<slug>.html` — see `docs/case-studies/README.md` for the workflow

## WP-CLI invocation

WP-CLI requires an explicit memory bump on this install — the default 128M throws a fatal in `class-wp-site-health.php`. Always invoke as:

```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood <subcommand>
```

## Cache-busting (CSS / JS)

`functions.php` uses `filemtime()` for all enqueued asset versions. Every edit to a CSS or JS file under `assets/` auto-busts the browser cache — no manual version bumping, no hard-refresh needed in dev. If you find yourself adding `?ver=` query strings or bumping a constant, you're working against the system.

## Service lineup

The public services are **Build · Tune · Manage · Empower** (4 cards in services-cards, 4 detail blocks in services-detail, in that order).

- Build = new sites
- **Tune = performance + SEO refresh of an existing site** (added 2026-04-30; fixed-price $2,000 / Tune Plus $4,500)
- Manage = hosting, security, monitoring
- Empower = client content control + training

The `/pricing` page has 3 build tiers (Starter $3,500 / Business $5,000 / Reach "Let's Talk") plus a separate "Already have a site?" section with Tune / Tune Plus.

The Signal AI Visibility Audit is a separate by-appointment service line that lives quietly on the About page only — see `docs/superpowers/specs/2026-04-26-signal-sales-surface-design.md`. Its product (the audit tool itself) is in a separate repo at `/Users/jeremyoms/newblood-signal/`.

## Voice direction

newblood.com uses a musical/compositional voice — Tier 1 words like *tune, compose, phrasing, range, rhythm, restraint* are in; Tier 3 words like *remix, drop, riff, jam, gig* are out. Full palette and concentration map: `docs/superpowers/specs/2026-04-23-musical-voice-design.md`. The principle: a phrase must read as natural English first and carry the musical second meaning quietly.

The brand is **deliberative, not fast.** Don't frame work as "in days," "quick turnaround," "build it fast" — the value prop is creative ambition, not speed. "Fast" is fine when it refers to site performance (load times); never when it refers to project turnaround.

## Pattern conventions

- Pattern files live at `wp-content/themes/newblood/patterns/*.php` (13 total)
- Each pattern's outer `wp:group` defines its own width via `contentSize` and uses `align:"full"` if the gradient background needs to extend edge-to-edge
- `templates/page.html` does NOT wrap post-content in a constrained group — patterns control their own widths
- The h2 elements in section headers use a green-period accent: `<h2>Headline<span style="color:#4ade80">.</span></h2>` — the h3 sub-headers do not
- The `nb-stagger` class on `wp-block-columns` triggers the staggered scroll-reveal animation
- The `nb-services-cards` class on the services-cards outer group triggers a 4-column → 2×2 wrap at tablet width

## When editing patterns

- Always run `php -l <file>` after edits — block-pattern PHP is parsed at request time
- Block-comment pairs (`<!-- wp:foo -->` / `<!-- /wp:foo -->`) must stay balanced. Use `grep -c` to verify after non-trivial edits
- Image URLs in pattern files MUST be root-relative (`/wp-content/uploads/...`), never absolute (`http://newblood.test/...`) — absolute URLs break on production
- Don't change pattern slugs (`newblood/<slug>`) once they're referenced from templates or pages — it orphans content

## Auto mode

Jeremy typically runs in auto mode and expects continuous execution. Make reasonable assumptions, prefer action over confirmation for low-risk work, and surface course-corrections for anything that affects shared state (deploys, GitHub repos, external publishing).
