# Voice samples — archived blog posts

This directory holds the body markup of seven blog posts originally published on newblood.com between 2013 and 2018. They are kept here as **voice-reference material**, not as content for re-publication.

## Why this exists

The current redesign retired the legacy blog. The posts were authored by hand (not AI-generated), so they're useful as a snapshot of New Blood's older voice — the small-business SEO consultancy era — for reference when training prompts, briefing writers, or comparing tone against the current musical/deliberative voice direction (see `docs/superpowers/specs/2026-04-23-musical-voice-design.md`).

The posts themselves were retired because:

- Most have dated tactics (link-building era SEO advice), broken assets (LeadPages embeds, dead PDF links, raw AdSense `<script>` tags), or off-topic subject matter (hard drive repair, reading habits) that don't match the current agency positioning
- A few are 100–200-word stubs wrapping a third-party video — not real articles
- The "human-authored beats AI" SEO benefit only applies to genuinely useful content; thin/dated/promotional content actively hurts E-E-A-T signals

## What was retired

| Original post ID | Slug | Original date |
|---|---|---|
| 5324 | `reading-1-hour-a-day-how-to-start-the-5-min-approach` | 2018-12-05 |
| 5171 | `cleanest-wordpress-management-system-to-date-managewp-now-updated-to-orion` | 2016-07-13 |
| 5107 | `quick-wordpress-backup-and-plugin-update` | 2016-06-04 |
| 4904 | `hard-drive-failed-hear-a-clicking-sound-and-beep` | 2016-04-15 |
| 4428 | `budgeting-for-seo-costs-how-to-make-it-work` | 2015-12-04 |
| 4895 | `top-5-takeaways-from-smx-west-2014-great-tips-for-seos-and-web-publishers` | 2014-03-14 |
| 4899 | `seo-guest-lecture-at-bis-in-pasadena-ca` | 2013-11-04 |

All seven were flipped from `publish` to `draft` in WordPress on 2026-05-04. They remain in the WP database but are excluded from the public site, the blog index, the RSS feed, and (for plugins that respect post status) the sitemap.

## File format

Each file has a YAML frontmatter block with `original_post_id`, `original_slug`, `title`, `original_publish_date`, `archived_status`, and `purpose`, followed by the original WordPress `post_content` HTML verbatim. The HTML is preserved exactly as it was on the live site, including legacy embeds and any typos — that's part of the voice snapshot.

## Don't republish from this directory

These are reference material. If a piece of writing here ever inspires a new article, write a fresh version in line with the current voice direction — don't copy the body across, and don't re-publish under the old URLs.
