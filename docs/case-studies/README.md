# Case study body markup

This directory holds the canonical Gutenberg block markup for each published case study on newblood.com. The WordPress database stores `post_content` for the corresponding `/case-study-<slug>/` page, but the source of truth lives here.

## Why this exists

The WP database has no version control, no diff history, and gets wiped on local resets. Committing the body markup to the repo means a case study page can always be reproduced from `wp media import` (for the screenshot) plus `wp post create` / `wp post update` (using the file in this directory as `post_content`). It also lets copy edits go through normal git review.

## Naming

One file per case study. Filename matches the WP `post_name` (slug):

- `/case-study-57cards/` ← `57cards.html`
- `/case-study-mikes-master-classes/` ← `mikes-master-classes.html` (if/when migrated)

The `.html` extension is technically Gutenberg-flavored HTML with embedded `<!-- wp:* -->` block comments. Most HTML linters will trip on it. Don't run `prettier`, `tidy`, or similar tools on these files — they'll mangle the block-comment delimiters that WordPress depends on.

## Image URLs — root-relative, never absolute

Every `<img src="...">` in these files must use a root-relative path:

```
src="/wp-content/uploads/2026/04/57cards-website-scan.png"
```

NOT:

```
src="http://newblood.test/wp-content/uploads/2026/04/57cards-website-scan.png"
```

Reason: `newblood.test` is the local Herd hostname. Production is `newblood.com` (Nexcess). Committing the absolute local URL guarantees a broken image on production. Root-relative paths work in any environment.

## Workflow for a new case study

1. Place the screenshot somewhere accessible (e.g., a sibling project directory).
2. Upload to WP via:
   ```
   php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood media import \
     /path/to/screenshot.png \
     --title="<descriptive title>" \
     --alt="<descriptive alt>" \
     --porcelain
   ```
   Capture the integer attachment ID. Capture the URL via `wp post get <id> --field=guid`, then strip the `http://newblood.test` prefix to make it root-relative.
3. Create a new file in this directory named `<slug>.html`. Use `57cards.html` as a template.
4. Push to the WP DB:
   ```
   php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post create \
     --post_type=page \
     --post_title="<title>" \
     --post_name=case-study-<slug> \
     --post_status=publish \
     --post_excerpt="<small label, e.g. 'Performance · Tune Engagement'>" \
     --post_content="$(cat docs/case-studies/<slug>.html)" \
     --porcelain
   ```
5. Add a card to `wp-content/themes/newblood/patterns/portfolio-grid.php` linking to `/case-study-<slug>/`.

## Updating an existing case study

```
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post list \
  --post_type=page --name=case-study-<slug> --field=ID
# capture the page ID, then:
php -d memory_limit=512M /opt/homebrew/bin/wp --path=/Users/jeremyoms/Herd/newblood post update <id> \
  --post_content="$(cat docs/case-studies/<slug>.html)"
```

## ⚠️ Don't edit case studies via WP-Admin

If a case study is opened in the Gutenberg editor and saved, WordPress's serializer will:
- Collapse pretty-printed `wp:table` and `wp:list` blocks back to single lines (cosmetic only — the markup still works)
- Potentially canonicalize image URLs to absolute (`http://newblood.test/...`) which is the production-breaking issue from Task 1
- Diverge from this file silently

Treat this directory as the editing surface, push to the DB via `wp post update`, and avoid touching the page through WP-Admin once it's published.

## Production deploy

When the site deploys to production (Nexcess), the WordPress media URLs change from `newblood.test` to `newblood.com`. Because the markup files in this directory use root-relative paths, no search-replace is needed for these specific case-study pages. Other content (legacy posts, theme patterns) may still need a `wp search-replace` pass — that's a separate concern.
