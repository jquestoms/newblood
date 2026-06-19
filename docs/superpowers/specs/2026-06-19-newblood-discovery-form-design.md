# New Blood Discovery Form — Design Spec

**Date:** 2026-06-19
**Status:** Approved design (pre-implementation)
**Author:** Jeremy Oms + Claude (brainstormed)
**First use:** Overhead Door Company of Baltimore (OHDBalt) — proposal expansion

---

## Background

OHDBalt was sent a focused proposal on 2026-06-12 ($5,000 Foundation Overhaul + $950/mo Growth). Chase Cummings replied a week later asking us to **stop tailoring to the prior conversation and instead recommend the most comprehensive solution we'd offer** — pricing and scope for a "fully optimized package" across website, SEO, hosting, maintenance, content, automation, lead generation, customer communication, reporting, AI, CRM, and more.

Read of the client: methodical, legacy-minded (78 years in business), took a full week to study a 5-page PDF, thinking long-term. They are not expecting a same-day quote. The winning move is to **match their pace deliberately** and lead with an enterprise-grade intake process rather than either re-interviewing them in prose or rushing a guess.

This spec covers the **first deliverable only**: a self-serve discovery form on newblood.com plus the email that delivers it. The comprehensive proposal itself is a later, separate deliverable that consumes this form's output.

## Concept

A branded, self-serve **discovery experience** on newblood.com that intakes a prospect's priorities and goals and produces structured data that feeds a *tailored* proposal. It is the modern, AI-era successor to New Blood's past discovery-workshop process (cf. the Night Owl Bay "Workshop Summary," which used brand-attribute "Top 5" tables and bipolar "Design Scale" sliders, and fed New Blood's 2018 OHDBalt proposal).

**Build approach: hybrid** — tailored to OHDBalt for this send, but architected as a reusable "New Blood Discovery" module so future clients are a re-skin + a tweaked service list. OHDBalt is instance #1.

## Goals

- Give Chase/Paul a short (~10 min), low-friction way to signal what matters to *them*, so the eventual proposal is **provably tailored**, not a template.
- Position New Blood as an enterprise shop with a real **intake system** (differentiator).
- Capture structured data we can turn into a persuasive proposal — especially a **priority-vs-current gap map**.
- Produce a **reusable asset** for future client discovery.

## Non-goals (this round)

- No pricing or proposal content in the form.
- No lane/honesty scoping (what falls outside our expertise) — that belongs in the proposal, not this probing step.
- No live results/gap-map reveal on submit — the gap map stays **our** dramatic move inside the proposal.
- No budget *number* requested (appetite is captured indirectly; see §Form, Section 5).

## Flow

1. Personal email from Jeremy → unique link to the form.
2. Branded form on newblood.com (~10 min, mobile-friendly).
3. On submit: a **gracious thank-you screen** ("Thank you — we'll review and prepare your custom plan").
4. Responses are stored and emailed to Jeremy.
5. Jeremy/New Blood builds the comprehensive proposal, using the gap map as the reveal.

## The form — section by section

### Section 1 — Welcome / context
- OHDBalt logo + New Blood branding.
- 2-sentence frame from Jeremy; ~10-minute estimate.
- Message: "Your answers shape a plan built around you, not a template."

### Section 2 — Service priorities (the gap map)
Dual-axis, **progressive**: each service shows an *importance* slider ("Not a priority ↔ Critical"). When a service is rated high importance (threshold TBD, e.g. ≥7/10), a second slider appears: *"How well is this handled today?"* ("Poorly ↔ Very well"). The gap between importance and current-handling is the core insight.

The 12 service rows (each with a plain-language one-liner in the final copy):
1. Website design & user experience
2. Search visibility (SEO) + AI-answer visibility (AEO)
3. Hosting, security & maintenance
4. Content (service pages, FAQ, ongoing)
5. Reviews & online reputation
6. Lead generation (paid search / social ads)
7. Lead capture & conversion (forms, funnels, CTAs)
8. Customer communication (email / text follow-up)
9. CRM / customer & job pipeline
10. Automation & AI assistants (routing, on-site chat)
11. Reporting & analytics
12. Brand & creative (logo, photography, video)

### Section 3 — Forward goals
- One open prompt: "In 3 years, what does winning look like?"
- Business bipolar sliders (Design-Scale style):
  - More residential ↔ More commercial
  - More leads (volume) ↔ Better leads (quality)
  - Grow the top line ↔ Run leaner
  - Defend our territory ↔ Expand into new areas
  - We stay hands-on ↔ Fully managed for us

### Section 4 — Current state / systems (light, structured)
- CRM today? (No / Yes — which)
- When a web lead comes in today, what happens?
- Your ~3,000 reviews live in which system?
- Call-tracking / attribution in place? (e.g., Enspire)
- Can you grant manager access to your Google Business Profile?
- Which locations/territories should the plan cover?

### Section 5 — Direction & logistics (budget asked indirectly)
- Is this more "fix what's urgent" or "invest for long-term growth"? (slider/choice)
- Ideal timeline to begin?

### Section 6 — Open
- "Anything we haven't asked?"

## Data model (captured for the proposal)

- Per-service: importance score (0–10) and, when applicable, current-handling score (0–10) → derived **gap score**.
- Goal vectors: position of each bipolar slider.
- Open text: 3-year vision, lead-handling description, review-system name, GBP access answer, territory, free-form.
- Systems inventory: CRM, call-tracking/attribution, GBP access (yes/no/unsure).
- Posture: fix-urgent ↔ invest-long-term; timeline.
- Respondent metadata: name, email, timestamp, client instance (OHDBalt).

## Tone & brand

Modern, SaaS-grade, clean, confident. New Blood green/black palette. Mobile-friendly (Chase or Paul may complete it on a phone). **Deliberately NOT** the 2018 quotes-and-clipart proposal aesthetic.

## Reusability architecture

Sections, service rows, bipolar pairs, and copy are **content/config-driven**, not hard-coded per client. A new client instance = new branding skin + adjusted service list + adjusted copy. OHDBalt is the first configured instance.

## Delivery email (draft — Jeremy sends)

> **Subject:** A quick step before your comprehensive plan
>
> Hi Chase,
>
> Thank you — "show us the maximum scope you'd recommend" is exactly the conversation I was hoping for, and I want to get it right rather than fast.
>
> To build something genuinely tailored to Overhead Door (not a template), I've put together a short discovery step — about 10 minutes. It captures where you want to take the business and how much each capability matters to *you*, so the plan I bring back reflects your priorities, not my guesses:
>
> **[ Start your discovery → newblood.com/… ]**
>
> Once you've gone through it, I'll prepare the comprehensive scope and pricing, and we can walk through it together with Paul whenever it suits you.
>
> Best,
> Jeremy

## Success criteria

- Chase completes the form (or asks to do it with Paul) — engagement, not silence.
- We come away with a clear, quantified priority/gap picture + systems inventory + posture/timeline.
- The form is reusable for the next client with config-only changes.
- New Blood looks more enterprise, not less, for having asked.

## Implementation context (for the build plan, not decided here)

- newblood.com is **WordPress** (confirmed: wp-config.php present); repo on branch `feature/redesign`.
- The dual-axis progressive sliders + custom branded UX + structured storage imply a **custom build** (custom plugin or custom page template + JS), not an off-the-shelf basic form plugin. To be settled in the implementation plan.
- Storage + notification mechanism (DB table / email / both) to be decided in the plan.
- Unique-link/instance handling and where the thank-you + data routing live: plan-stage decisions.

## Open questions for the plan

- Exact importance threshold that reveals the "handled today?" slider.
- Whether responses persist in a custom DB table vs. a forms plugin vs. emailed-only.
- URL/slug for the OHDBalt instance.
- Final microcopy for each of the 12 service one-liners and the welcome frame.
