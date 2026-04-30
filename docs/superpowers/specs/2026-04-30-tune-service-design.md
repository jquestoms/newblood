# Tune — Fourth Service Line + Sales Surface Design

**Status:** Brainstorm complete; pending Jeremy's review and implementation
**Date:** 2026-04-30
**Scope:** Productize the WordPress Performance + SEO Quick-Wins playbook (validated on the 57cards.com engagement, April 2026) as a public service line at NewBlood named *Tune*. Add it to the public services lineup as the fourth offering alongside Build / Manage / Empower. Define the v1 sales surface on newblood.com.

This spec is **about the sales surface only** — the patterns, copy, and pricing changes on newblood.com that introduce and sell Tune. The delivery methodology lives in `/Users/jeremyoms/Herd/57cardsdev/docs/wordpress-perf-seo-quick-wins.md` and is treated as the source of truth for how engagements are actually executed.

---

## Strategic Position

**Center of gravity:** standalone productized service (option A from the brainstorm) + entry-level wedge framing (option B baked into the messaging) + agency partner offering as a future roadmap item (option D, deferred).

Tune is a proof-of-thesis for NewBlood's broader positioning ("AI-augmented modern web dev agency"): the 57cards engagement delivered Fortune-500-style perf consulting in 4 hours, in a way that would have required a much larger team and budget to produce a few years ago. Every Tune engagement quietly validates the brand thesis that AI-augmented craft makes premium work accessible at small-business prices.

## Service Lineup After This Change

The public services lineup becomes:

**Build · Tune · Manage · Empower.**

Reads as a lifecycle: make new (Build) or improve existing (Tune), then run it (Manage), then hand it over (Empower). Build keeps the headliner spot. Tune sits in the second slot — prominent enough to function as the entry-level wedge, not dethroning Build.

Signal (the AI Visibility Audit, specced 2026-04-26) remains by-appointment on the About page only. Tune does not reference Signal and vice versa. Two distinct tracks: public services (Build/Tune/Manage/Empower) and invitation-only product line (Signal).

## Customer

**Primary buyer:** small-business owner running WordPress on a heavy commercial theme (Porto, Avada, Divi, Enfold, OceanWP, Astra Pro), typically with WooCommerce, with a mobile PageSpeed score in the 50-70 range.

**Buying triggers:**
- An SEO consultant flagged Core Web Vitals
- Lost rankings recently and trying to understand why
- Got a $20-30K redesign quote and looking for a cheaper alternative that preserves content and admin workflow
- Site got slow over years of plugin accumulation

**Distribution v1:** existing NewBlood network, outbound referrals, and the public Tune service page itself doing the qualifying work. No paid acquisition.

## Pricing

| Offer | Price | What it is |
|---|---|---|
| **Tune** (standard engagement) | **$2,000 fixed** | 5-7 hours of work across the playbook's five phases: diagnostic SEO + PageSpeed audit, SEO foundation, LCP image conversion, plugin asset cleanup, CSS deferral, JS audit. 3 measurement rounds. Per-phase before/after PageSpeed screenshots. Site-specific dequeue file the client can extend. Search Console + sitemap submission. Handover doc. |
| **Tune Plus** (with critical CSS) | **$4,500 fixed** | Tune + Phase 6 critical-CSS extraction for clients targeting Mobile 90+. Quoted as a stretch engagement because above-the-fold visual-regression risk needs explicit sign-off before scoping. |
| **Watch** (continuous monitoring) | *Deferred to v1.5* | ~$99/mo CWV monitoring with quarterly mini-reports. Productize after Tune has 5-10 customers. Out of scope for v1 launch. |

Pricing rationale for $2,000:
- Above hourly-billed boutique work, so it reads as a productized engagement
- Well below a typical $20-30K redesign quote, so it's a credible alternative
- ~$300/hr-effective for 5-7 hours of specialist work, fair to both sides
- Round number, easy to remember, no psychological-pricing markup

## Voice and Copy

The Tune copy uses the established musical/compositional voice (`docs/superpowers/specs/2026-04-23-musical-voice-design.md`). The service name itself is a Tier 1 word that does double-duty as plain English and as a quiet musical signal. The earned phrases in the copy:

- *"up to speed"* — load-bearing pun; reads as idiom *and* as literal performance
- *"the band Google rewards"* — Tier 1 word, plain English meaning, secondary musical reading
- *"Existing site, sharper performance."* — sub-headline; "sharper" matches the deliberative voice
- *"Kept in tune."* — the existing services-cards H2 phrase now gains a second resonance with the new service named Tune

No Tier 3 musical jargon (no "remix," "drop," "groove," etc.).

## Sales Surface Changes (v1)

### Change 1 — `patterns/services-cards.php`

Add a fourth card (Tune) between Build and Manage. Layout becomes 4-up on desktop, 2×2 on tablet, 1-col on mobile. The existing H2 *"Built to last. Kept in tune. Yours to run."* stays unchanged — it now maps to the four cards even though it has three phrases (Build = "Built to last," Tune + Manage together = "Kept in tune," Empower = "Yours to run").

**Tune card content:**

- *Icon:* 🎛️ (control knobs — visually evokes tuning, distinct from existing icons)
- *H3:* Tune
- *Body:* Bring your existing site up to speed. A fixed-price tune-up that gets your performance and SEO into the band Google rewards — without rebuilding anything.

### Change 2 — `patterns/services-detail.php`

Add a fourth detail section after Empower, structured identically to the Build/Manage/Empower blocks (icon at left, H2 + sub-headline at right, two body paragraphs, two-column "what's included" list, optional honest "what we don't promise" line).

**Tune detail content:**

- *Icon:* 🎛️ (matching the card)
- *H2:* Tune
- *Sub-headline:* Existing site, sharper performance.
- *Body paragraph 1:* Most WordPress sites running heavy commercial themes ship 200-400 KB of CSS and 100-200 KB of JS site-wide, even on pages that need almost none of it. The result is mobile load times Google notices — and so do your visitors. We tune what's there: diagnostic first, then a focused 5-7 hour engagement against the small set of well-known offenders that produce the biggest gains.
- *Body paragraph 2:* We don't rebuild your theme. We don't add more plugins. We don't change your editorial workflow. Every change ships as one focused pull request, organized by phase, with before-and-after PageSpeed screenshots so you can see the move.
- *"What's included" list (two columns):*
  - Left column:
    - Diagnostic SEO + PageSpeed audit
    - LCP image conversion (fetchpriority, srcset)
    - Plugin asset cleanup (conditional dequeue)
    - Selective CSS deferral
  - Right column:
    - JavaScript audit + targeted fixes
    - Search Console + sitemap submission
    - Per-phase before/after PageSpeed screenshots
    - Handover doc with extensible kill-list
- *"What we don't promise" line:* A specific PageSpeed score. Real-world variance is ±5 points run-to-run. Mobile 70+ is reasonable; Mobile 90+ needs the Tune Plus engagement.

### Change 3 — `patterns/pricing-table.php`

Add a *fourth section* below the existing three website-build tiers (Starter / Business / Reach). Visually distinct from the rebuild tiers — a single full-width row or paired card showing Tune + Tune Plus side by side. Reads as: *"Already have a site? Tune it instead."*

**Pricing-page Tune block content:**

- *Section heading:* Already have a site?
- *Sub-headline:* Tune it instead.
- *Pair of cards (or two-column row):*
  - **Tune** — $2,000 — Fixed-price 5-7 hour engagement. Performance + SEO tune-up for existing WordPress sites.
  - **Tune Plus** — $4,500 — Tune + critical-CSS extraction for clients targeting Mobile 90+.
- *CTA:* Get in touch → /contact

### Change 4 — Homepage

No standalone change required. The homepage already includes `services-cards`, which gets the new fourth card automatically via Change 1.

### Change 5 — Navigation

No change. The existing "Services" nav entry already routes to the services page; the fourth detail block lives there alongside the other three.

## Out of Scope for v1 (Explicit)

These are deferred so they don't quietly creep into the v1 sales-surface work:

| Deferred | Earliest revisit |
|---|---|
| Watch (CWV monitoring retainer) | After Tune has 5-10 paying customers |
| Agency partner / white-label program | After 12 months of direct-sale Tune engagements |
| Dedicated `/tune` landing page | Only if conversion data shows the services-detail block is insufficient |
| Self-serve audit checkout | Probably never — Tune always starts with a call to scope |
| Tune integrated with Signal in a bundle | After Signal goes public |
| Tune mention in the homepage hero | Tune is one of four equal services; no headline promotion |
| Custom intake form for Tune leads | Use the existing contact form for v1; revisit if the contact-form-driven motion produces too many unqualified leads |

## Success Criteria

- Tune appears as the fourth card in `services-cards` on the homepage and the services page.
- Tune has its own detail section in `services-detail` matching the existing Build/Manage/Empower structure.
- Pricing page includes the Tune block with both engagement tiers visible.
- All copy reads in the established musical/compositional voice without contradicting the existing about-story or hero direction.
- Removing the Tune additions from any single pattern is a clean, isolated change — no ripple effects elsewhere on the site.
- The full set of changes ships as one logical PR / commit so the launch can be reverted in one move if Jeremy decides to delay.

## Editorial Rules for Future Work

When the Tune surface evolves (and especially when Watch + agency partner programs land):

1. The musical voice direction continues. New Tune copy uses the Tier 1/2/3 palette.
2. Tune's pricing stays measured — present the $2,000 / $4,500 plainly, no urgency or discount theatrics.
3. Keep the "we don't promise a specific score" line. It's the single most important credibility hook on the page.
4. Avoid Core Web Vitals jargon outside the services-detail page. Cards and pricing speak in plain English.
5. When Watch lands, it goes alongside Manage (it's a monitoring product), not as a fifth service. Keep the lineup at four.

## Open Decisions (For Jeremy's Review)

1. **Heading for the pricing-page Tune block.** I drafted *"Already have a site?"* — direct, frames the choice. Alternatives: "Existing site?", "Skip the rebuild." Default in spec: "Already have a site?"
2. **Visual treatment of the Tune block on pricing-table.** Side-by-side cards (matching the build-tiers layout) vs. single full-width row. Default: side-by-side cards for visual consistency.
3. **Whether to include a customer-friendly summary of the 57cards outcome** somewhere on the services-detail page (e.g., "On a recent engagement we moved a client from mobile 61 to 72, desktop 72 to 96, and held SEO at 100/100"). Strong proof point, but introducing specific numbers requires the client's blessing. *Default: omit for v1; add as a case-study link if/when 57cards permits public reference.*
