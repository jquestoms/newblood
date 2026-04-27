# NewBlood Signal — v1 Sales Surface on newblood.com

**Status:** Approved (brainstorm), pending implementation in this session
**Date:** 2026-04-26
**Scope:** A subtle, "by appointment" introduction to the AI Visibility Audit service on newblood.com. v1 — pre-public-launch posture while Jeremy validates with the first 5-10 clients.

## Summary

Add a single new WordPress pattern, `newblood/signal-intro`, that introduces the AI Visibility Audit on the **About page only**. No dedicated page, no public pricing, no homepage promotion — the audit exists for visitors who care enough to read the about page through to the end. When the model is proven (after 5-10 paying audits), this pattern's content moves up to a homepage moment and a dedicated `/signal` page.

## Why "by appointment, About-only" for v1

- Matches the **deliberative, considered** brand voice — premium services don't shout.
- Avoids publishing pricing or methodology before the tool exists.
- Pre-qualifies inquiries: anyone who finds and reads it has already shown the right kind of attention.
- Easy to evolve to a public launch later — same copy lives on a dedicated page when ready.
- Preserves option to walk it back if Jeremy decides not to ship the audit product after all.

## What lives in this repo vs the audit repo

This repo (newblood.com) holds:
- The `signal-intro` pattern.
- A historical snapshot of the audit's design spec at `docs/superpowers/specs/2026-04-26-ai-visibility-audit-design.md`.
- This spec.

The audit product itself — code, future plans, evolving spec — lives in a separate repo at `/Users/jeremyoms/newblood-signal/`.

## Pattern: `newblood/signal-intro`

**Location on the site:** Bottom of the About page (`/about`), inserted manually via the WordPress block editor by Jeremy after the existing `about-story` pattern. Not added to any template by default — page-level placement preserves Jeremy's editorial control over when and where it appears.

**Visual treatment:**
- Same `nb-gradient-section` background as `about-story` for visual continuity.
- Same `contentSize: 720px` to match the about-story prose width.
- `align:"full"` so the gradient extends edge-to-edge.
- `nb-reveal` for scroll-in animation, consistent with other sections.
- No image, no glass card, no decorative elements. Restrained.

**Voice:** Musical/compositional voice in Tier 1 vocabulary. One earned phrase: *"tune the methodology"* — reads as native English ("refine, calibrate") with a quiet musical second meaning. No green period accent on the h2 (this section is not asking for the same emphasis as the main about-story headings).

Wait — correction: per existing pattern conventions, h2s on this site DO get the green period accent. The `signal-intro` h2 will follow that convention.

**Content (final, copy-locked):**

- *Label:* AI Visibility Audit
- *H2:* Beyond the website.
- *Paragraph 1:* When a buyer asks ChatGPT, Claude, Perplexity, or Gemini to recommend an expert in your field, do you appear? Most independent professionals don't know — and most never will, until a client they should have won mentions, offhand, that they hired someone else.
- *Paragraph 2:* We built a small tool that runs the queries that matter for your category across the major AI engines, scores how you appear, surfaces who's being recommended instead, and reverse-engineers why. The result is a personal report — in your voice, with a prioritized punch list of what to fix and a clear sense of where you stand.
- *Paragraph 3:* By appointment for now. We're working with a small group of clients while we tune the methodology. If you'd like to know where you stand, write to us.
- *CTA:* "Begin a conversation" → `/contact` (uses existing `.nb-btn-primary` styling)

## Out of Scope for v1

Explicitly deferred so they don't quietly creep into this work:

| Deferred | Earliest revisit |
|---|---|
| Dedicated `/signal` page with full positioning + pricing | After 5-10 paying audits |
| Homepage mention of the audit | Same as above |
| Services-page placement (alongside Build/Manage/Empower) | Same as above |
| Footer link to the audit | Probably never until launched publicly |
| Contact form field for "interested in audit" | Optional; Jeremy can add via WPForms admin if useful for routing |
| Any image, illustration, or visual identity for "Signal" | When a public launch is planned |
| New nav menu entry | Public launch |

## Editorial Rules (For Future Edits)

When the audit goes public and the sales surface expands, hold these rules:

1. The musical voice direction from `2026-04-23-musical-voice-design.md` continues to apply. New audit copy uses the same Tier 1/2/3 palette.
2. Pricing on public pages stays measured — present the $2,500 audit and $400/mo retainer plainly, not with discount-driven urgency.
3. Avoid "AI search is changing! Don't get left behind!" panic copy. The voice is *deliberate*, not alarmed.
4. Any new public surface still emphasizes the human-in-the-loop nature — Jeremy is part of what's being bought, not just the tool.

## Success Criteria

- The pattern is available in the WP block-pattern inserter under "New Blood."
- Jeremy can drop it onto the About page in a single click and see it render correctly with no styling regressions.
- The pattern reads cleanly in the existing musical voice without contradicting earlier decisions.
- Removing the pattern from the About page is one click — no ripple effects elsewhere on the site.
