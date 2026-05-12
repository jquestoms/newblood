# Services-page TL;DR card — design

**Date:** 2026-05-11
**Author:** Jeremy + Claude
**Scope:** `/services` page (`services-detail.php` pattern only)

## Problem

The four service sections on `/services` are still text-heavy after the marks-and-motion treatment landed (commit `9ba23753`). Each section opens with an emotional one-liner ("Your website is the first impression…", "Existing site, sharper performance.", "A website isn't a one-time project…", "Your website. Your content. Your control.") followed by two paragraphs and an eight-bullet list. That lede sentence carries some warmth but conveys almost no concrete information. A visitor who scans the page — likely the majority — leaves without a structured grasp of what each service actually is, who it's for, or what's included.

A scan-friendly element is missing. Charts and process diagrams don't fit (three of four services have no sequence to chart). A structured TL;DR card, repeated as a parallel pattern across all four services, would let a scanner self-identify, see the outcome, and read three concrete deliverables in two seconds.

## Solution

A small structured card — the **TL;DR card** — replaces the existing emotional lede paragraph under each service `<h2>`. The mark + h2 stay as the section header; the card occupies the real estate the lede used to use. Body paragraphs and bullet lists continue below as deep-dive content.

The card is the same shape across all four services:

- **For** — audience self-identification (one short phrase, starts with the audience type)
- **Get** — outcome with warmth (one sentence, designed to retain the voice the lede was carrying)
- **Includes** — three concrete items separated by middle dots

Visual treatment is "meta-style split" (Treatment C in the brainstorm): top-and-bottom-bordered band, two-column grid for For + Get side by side, full-width Includes row beneath. Echoes the existing `.nb-case-meta` pattern on case-study pages so the card feels like part of the established design vocabulary, not a new chrome layer.

The card is information-dense and scannable, but stays restrained: no card frame, no pills, no extra color. Labels are small-caps green at 75% opacity; values are body-text white. Get gets a slight type-size bump to anchor the eye there.

## Per-service copy

| Service | For | Get | Includes |
|---|---|---|---|
| **Build** | businesses that want a website made for them, not chosen from a template | a custom, modern website tuned to your brand and built to perform | Custom design · Modern code · Performance from day one |
| **Tune** | an existing site losing visitors and ad spend to slow mobile load times | a measurably faster site — without rebuilding anything | PageSpeed audit · Surgical fixes · Before-and-after proof |
| **Manage** | owners who don't want to think about updates, backups, or whether the site is up at 2 AM | a site that just runs, looked after in the background | Managed hosting · Security & updates · Backups & monitoring |
| **Empower** | teams that want to publish their own content without breaking the design | editorial control of your own site, with the structural side held safely by us | Block editor · Hands-on training · A real point of contact |

Voice notes on the locked copy:

- **For** lines start with the audience type (businesses / an existing site / owners / teams) so a scanner can self-identify in the first three words.
- **Get** lines do the emotional work the existing ledes were doing. They're designed to read with warmth without forced metaphor. The Tune line was iterated — an earlier draft used "the page-speed band Google rewards" which Jeremy flagged as trying too hard to reuse the musical vocabulary; the locked version drops the "band" reuse and lets "measurably" and "without rebuilding anything" carry the weight.
- **Includes** are three short noun-phrases separated by middle dots — parallel to the case-study meta block style and the existing portfolio-grid badge convention.

## Visual treatment

### Layout

```
┌─────────────────────────────────────────────────────────────┐
│ FOR                          GET                            │
│ businesses that want a       a custom, modern website       │
│ website made for them…       tuned to your brand…           │
├─────────────────────────────────────────────────────────────┤
│ INCLUDES                                                    │
│ Custom design · Modern code · Performance from day one      │
└─────────────────────────────────────────────────────────────┘
```

Top and bottom borders use `rgba(255,255,255,0.06)` — the same hairline opacity already used on the case-study `.is-style-wide` separator. No left/right borders. No background fill. Padding ~20px top/bottom internal, no horizontal padding (the card spans the constrained width of the section).

### Typography

| Element | Treatment |
|---|---|
| Label (For / Get / Includes) | 10px, uppercase, `letter-spacing: 0.16em`, color `rgba(74, 222, 128, 0.75)`, weight 600 |
| For value | 14px, `line-height: 1.55`, color `rgba(255, 255, 255, 0.85)` |
| Get value | 16px, `line-height: 1.5`, color `#fff` (full white — the visual anchor) |
| Includes value | 13.5px, `letter-spacing: 0.02em`, color `rgba(255, 255, 255, 0.7)` |

The Get-value type bump (16 vs 14) is intentional — it carries the outcome and should feel slightly heavier than the audience identifier.

### Responsive

At widths below ~640px (roughly mobile + small tablet), the For/Get two-column grid collapses to a single stacked column. Includes row stays full-width regardless.

### Animation

None on the card itself. The `.nb-reveal` parent already handles the fade-in for the whole service section. No draw-in animation on typography. Reduced-motion: nothing to override (no animation defined).

## Scope

### In scope

- New CSS block for `.nb-service-tldr` and its child elements, appended to `wp-content/themes/newblood/assets/css/patterns.css`
- Four `services-detail.php` sections updated: each existing one-line `.has-text-secondary-color` lede paragraph block replaced with the TL;DR card markup carrying that service's locked copy
- Responsive collapse at narrow widths

### Out of scope

- `services-cards.php` (homepage card row) — the homepage cards are already compact 3-line summaries; adding the TL;DR pattern there would duplicate without adding value
- Any change to the body paragraphs, bullet list, or italic footnote on `/services`
- Any new image, icon, or pill element
- Animation on the card itself
- Changes to the marks-and-motion treatment that landed in `9ba23753`

## Accessibility

- The card uses semantic paragraph markup with no new ARIA roles. The label spans (For / Get / Includes) and value spans are plain text; screen readers will read them in source order.
- Color contrast: green labels at `rgba(74, 222, 128, 0.75)` on the `nb-gradient-section` dark background should reach WCAG AA non-text contrast; verify during implementation. If it falls short, raise to 0.85 alpha or change to white.
- No new keyboard interactions, no new focus states.

## Performance

Pure CSS + HTML. No new JS, no new assets, no extra HTTP request. The CSS block is ~30 lines. Inline addition to the existing patterns.css; `filemtime()` cache-busting handles invalidation.

## Implementation outline (for the plan phase)

A separate writing-plans pass will detail the implementation. Headline items expected:

1. **Add `.nb-service-tldr` CSS to `patterns.css`** — base layout, label/value typography, responsive single-column collapse
2. **Replace lede paragraph in each of the four service sections** in `services-detail.php` — same outer wp:group structure, label + value spans per field
3. **Manual visual QA** — confirm card renders at expected size on desktop and mobile, no overflow, labels and values align correctly
4. **Contrast check** — verify the 0.75-opacity green label color passes WCAG AA against the section background
5. **Commit** — single commit covering CSS + pattern edits

## Open questions for the plan phase

- Confirm exact spacing/margins between the new card and the section's body paragraphs below it. The current lede has standard paragraph spacing; the new card may need slightly more breathing room above the first body paragraph because it has structural weight. Plan to verify visually.
- Should the Includes middle-dot separator be a real `·` character or rendered via `::before` content? Real character is simpler, less brittle, lets the copy live in `post_content` as plain text. Plan recommendation: real character (matches existing portfolio-grid badge convention).
- Decide whether `.nb-service-tldr` lives in `patterns.css` (component-level, matches where `.nb-service-mark` ended up) or `case-meta.css` etc. Plan recommendation: `patterns.css` — same file as the service marks.

## Future considerations (not for this engagement)

- A fifth TL;DR card for Signal (AI Visibility Audit) when that service surface gets a public page. The pattern established here extends cleanly.
- If the brand later wants more visual punch, the Includes row could be promoted to three pills (Treatment B from the brainstorm). The structure is stable; the visual treatment is the cheapest dimension to revise.
- If usability research shows scan-readers want stats over deliverables, the Includes row could become three big numbers ("9,000 jobs/year · 99.95% uptime · 7-day response"). Out of scope for now — the deliverable-noun structure is the safer first cut.
