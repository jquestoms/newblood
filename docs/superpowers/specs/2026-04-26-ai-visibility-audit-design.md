# AI Visibility Audit — Design Spec

**Status:** Brainstorm complete; pending Jeremy's review
**Date:** 2026-04-26
**Scope:** v1 of a new NewBlood service line — a tool that produces personalized AI-search-visibility audits for thought-leadership professionals, sold as a one-time audit with optional monthly retainer.

---

## Problem

Small-business discovery is shifting from blue-link Google search to AI answer engines (ChatGPT, Claude, Perplexity, Gemini, Google AI Overviews, Microsoft Copilot). When a buyer asks an AI engine "best fractional CMO for early-stage SaaS" or "who do I hire to ghostwrite a CEO's book," the engine names two or three people and the buyer typically goes no further. Most independent professionals have no idea:

- whether they appear in those answers at all
- what the engines say about them
- whether the information is correct
- which competitors are appearing instead
- what to do to change any of it

Existing tooling for this problem is aimed at enterprise (Profound, Otterly.ai, Athena HQ, Brandwell). No credible product targets the **independent thought-leadership professional**, who has both high willingness-to-pay and a direct revenue link to AI visibility (their business depends on being recommended).

## Solution Summary

A **NewBlood-branded service line** that delivers a personalized, deeply detailed AI Visibility Audit — a 12-15 page PDF report — backed by a small custom-built monitoring tool. The audit identifies the client's current visibility across six AI engines, reverse-engineers the citation footprints of competitors that beat them, and produces a prioritized punch list of actions to take.

A monthly retainer re-runs the audit and ships a delta report each month, plus a 30-minute call.

The product is **human-in-the-loop**, not self-serve. The tool exists to make Jeremy ~5x faster at producing audits; Jeremy stays in the loop for narrative interpretation and recommendation tailoring. This is consulting with a tool as the backend, not SaaS with a person bolted on.

## Target Customer

**Independent thought-leadership professionals** whose business depends on being recommended:

- Independent consultants (strategy, brand, ops, etc.)
- Fractional executives (CMO, CFO, COO, CTO)
- Executive coaches
- Indie creative directors / boutique-studio principals
- Niche analysts and indie experts
- Authors and professional speakers
- Specialist agencies (small, named-founder shops)

**Why this slice (and not local services or e-commerce):**
- Direct revenue link to AI visibility — being recommended *is* their funnel
- High willingness to pay ($300+/hr coaching rate world)
- Lower volume sale required ($1-2M ARR achievable at 250-500 customers, not thousands)
- Matches the kind of clients Jeremy enjoys working with (depth, story, willing to invest in craft)
- Sales motion is relationship-driven and repeatable through Jeremy's existing network

## Branding & Positioning Decision

**Recommendation: NewBlood-branded service line, with a distinctive sub-brand for the audit itself.** Working name: *NewBlood Signal* (placeholder; can be renamed). The service lives under newblood.com but has its own product identity — its own page, its own visual language, its own pricing.

**Rationale:** leverages 25+ years of NewBlood trust, no need to build a new brand from zero, faster path to first sale, sales conversations don't require relationship-cold-starts. Trade-off: caps perceived market because some buyers won't see a "web agency's audit product" the same way as a dedicated AI-visibility company. Acceptable for v1; revisit at scale if friction shows up.

**This decision is not load-bearing for v1 implementation** — the tool is the same regardless of brand. Jeremy can override after reading this spec.

## Pricing (v1)

| Offer | Price | What's included |
|---|---|---|
| **One-time AI Visibility Audit** | **$2,500** | 12-15 page PDF report, 60-min walkthrough call |
| **Monthly Retainer** | **$400/mo** | Monthly re-run, delta digest email, 30-min call |
| **Audit + 6mo retainer prepaid** | **$4,500** | Both, ~$500 savings, encourages commitment |

Cost basis: per-audit third-party API + scraping spend is roughly $5-15. Per-retainer-customer monthly API spend is similar. Gross margin on tooling is ~98%; the cost line that matters is Jeremy's time, not the APIs.

## v1 Product Shape

### Engines monitored (six)

1. **ChatGPT** (OpenAI API)
2. **Claude** (Anthropic API)
3. **Perplexity** (Sonar API — returns citations natively)
4. **Gemini** (Google API)
5. **Google AI Overviews** (via SerpApi — Google has no official API for this)
6. **Microsoft Copilot** (best-effort; consumer Copilot API is partial — may slip to v1.5 if access is constrained)

### Per-audit workflow

1. **Profile in.** Operator (Jeremy) creates a customer profile: legal/working name, niche, target audience, ideal positioning sentence, geographic scope (if relevant), 3-5 named competitors. Selects a persona template from the query library (e.g., "Fractional CMO," "Executive Coach," "Boutique Brand Strategist") which seeds the query set.
2. **Customize query set.** Operator reviews and edits the 30-60 buyer-intent queries — adds bespoke ones, removes irrelevant ones.
3. **Run audit.** Tool fires every (query × engine) pair in parallel, with retries and rate-limit handling. Stores raw responses, parsed mentions, citations, screenshots (where available).
4. **Score and classify.** Tool runs the scoring model and a second LLM pass to classify each response (which experts named, sentiment, factual claims).
5. **Reverse-engineer competitors.** For each competitor surfaced, tool runs the 6-step citation-footprint pipeline (see below).
6. **Generate draft PDF.** Tool produces a structured PDF with all auto-fillable sections populated and templated language for the rest.
7. **Operator finalizes.** Jeremy writes the executive summary and tailors the narrative for the top-5 findings and recommendations roadmap. Target: 60-90 minutes of writing per audit.
8. **Deliver.** PDF emailed to client, with optional walkthrough call booked.

### Per-retainer workflow (monthly)

1. **Re-run** same query set as the original audit (with allowance for adding/removing queries based on monthly call discussion).
2. **Diff results** — what changed: new appearances, lost mentions, new competitors, score deltas per engine.
3. **Generate delta digest** — a 3-5 page email/PDF summary of what moved this month and why.
4. **30-min call** with Jeremy to discuss + advise. (No tool work for the call itself; tool prepares the talk track.)

## Scoring Model

**Six observable signals per (query × engine):**

| Signal | What it captures | Range / formula contribution |
|---|---|---|
| Appearance | Did the client get named? | 50 × {0,1} |
| Position | First, third, fifth in the response? | 25 × {0.3-1.0} |
| Framing | Recommended? Neutral? Hedged? | 15 × {0.3-1.0} |
| Citation | Was the mention sourced? | 10 × {0,1} |
| Accuracy | Were the facts correct? | up to −25 if wrong |
| Competitive context | Did competitors appear more prominently? | up to −10 |

**Per (query × engine) score:** sum the six contributions, clamp to 0-100.

**Per-engine score:** average across that engine's queries.

**Overall Visibility Score:** weighted average across engines. Weights configurable per persona template (e.g., for a B2B fractional CMO, ChatGPT and Perplexity get higher weight than Microsoft Copilot).

**Interpretation bands:**

| Score | Reading |
|---|---|
| 80-100 | You own this category |
| 60-79 | Strong signal — placement/framing wins available |
| 40-59 | Mixed visibility — significant gaps |
| 20-39 | Weak signal — missing key queries |
| 0-19 | Invisible — competitors are having the conversation without you |

**Defensibility properties (the moat against "what am I paying for?" pushback):**

1. Every score traces to evidence — the appendix shows every query and color-codes results against the six signals.
2. Weights are stated, not hidden — published on the cover page and in the methodology section. Most enterprise tools won't do this.
3. Signals match how engines actually decide (appearance, position, citation, sentiment). Not invented metrics.
4. Scoring is *descriptive*, not predictive — "your visibility today is X," not "you will get 12% more leads if you reach 70+." Easier to defend, harder to over-promise.
5. Same inputs always produce the same outputs (deterministic given a fixed engine version and query set).

**Versioning:** the methodology is fixed; the weights and engines are pluggable. When GPT-5 or Claude 5 ships and changes how they cite, weights are re-tuned, the product is not rewritten. Methodology version is stamped on every audit (e.g., "Methodology v1.2") so historical scores remain comparable.

## Reverse-Engineering Pipeline (per competitor)

For each competitor surfaced in the audit, run six steps to map their citation footprint:

1. **Read the engine's own citations** — Perplexity, Gemini, Google AI Overviews, ChatGPT/Claude with web search return source URLs in the API response. Extract and tag by type (directory, media, podcast, industry blog, social, review platform).
2. **Probe LLM training data** — query each engine *without* web search: "Tell me about [competitor]." Detailed accurate response → strong training-data presence. "I don't have specific information" → live-search-only visibility, fragile. Hallucinated facts → mid-tier presence, possibly stale.
3. **Plain Google search** for the competitor name via SerpApi. Top 20 organic results, classified into the same buckets as step 1.
4. **Fetch the competitor's primary website** — extract title, meta description, schema.org / JSON-LD markup, About page positioning, content pillars.
5. **Targeted platform lookups:**
   - Wikipedia / Wikidata (entity presence)
   - Crunchbase basic (profile presence)
   - Reddit search API (mention surface area)
   - Listen Notes (podcast guest appearances)
   - LinkedIn (manual or low-cost tooling)
6. **Cross-reference into a gap report** — `client_sources ∩ competitor_sources` (common ground), `competitor_sources \ client_sources` (gaps to close, prioritized by which engine cited them).

The recommendation language in the PDF reads like:
> *"[Competitor] appears for this query primarily because Perplexity is pulling from a 2025 IndieHackers feature, a Crunchbase profile, and a guest spot on the SaaStr podcast. None of these surfaces have you. Highest-leverage to acquire: (1) Crunchbase profile — 30 minutes of work, durable training-data signal. (2) Pitch SaaStr or one of three equivalent podcasts — listed below."*

## Recommendation Library

A curated mapping `{finding type, persona type} → action`, stored as structured data with templated language. Examples (real, in voice):

| Finding | Recommendation pattern |
|---|---|
| Engine doesn't mention you | Publish on platforms this engine cites (Reddit, Substack, named industry blogs). Top 5 citation sources for your category listed. |
| Mentioned but with wrong information | LLMs parrot what they find. Update three highest-authority public pages (LinkedIn About, your firm's About, top industry directory). Engines catch up in 60-90 days. |
| Competitor outranks you | Reverse-engineer their citation footprint (step 5 of pipeline). Acquire equivalent sources. |
| Vague positioning — engines describe you generically | Sharpen the one-line positioning. Make it impossible for an LLM to call you "a brand consultant" instead of "a brand consultant for B2B SaaS post-Series-A." |
| No citations alongside your name | Earn 1-2 high-credibility mentions from sources LLMs trust (industry awards, podcast features, named pieces in trade publications). |

The library lives in the tool as data; v1 ships with ~25-40 patterns. New patterns are added as Jeremy encounters new finding types in real audits.

## PDF Report Structure

Sections, in order, with effort split:

| # | Section | Tool | Operator |
|---|---|---|---|
| 1 | Cover + headline score (single number, single-sentence verdict) | ✓ | — |
| 2 | Executive summary (~half page in operator's voice) | — | ✓ |
| 3 | Score breakdown by engine (chart + per-engine commentary) | ✓ | — |
| 4 | Top 5 highest-impact findings (the page they'll photocopy) | drafts | finalizes |
| 5 | Full punch list (every finding, prioritized by leverage) | ✓ | — |
| 6 | Recommendations roadmap: Quick wins (this month) / Medium-term (this quarter) / Strategic (this year) | drafts | finalizes |
| 7 | Competitive context (who's recommended instead, gap analysis) | ✓ | — |
| 8 | Methodology + scoring formula (transparency) | ✓ | — |
| 9 | Appendix (every query, every engine response, color-coded) | ✓ | — |

**Visual quality target:** Kinfolk-magazine version of a McKinsey deck. The PDF is the visible artifact the customer remembers; layout craft is part of the moat. Most competitors ship a Yoast-export-quality PDF.

## Tech Approach

### Stack (proposed; plan-writer can adjust)

- **App:** Next.js (App Router) + TypeScript, OR Python/FastAPI. Choice deferred to plan; pick whichever Jeremy is most fluent in or can ramp fastest.
- **Database:** Postgres (managed — Supabase, Neon, or Render).
- **Hosting:** Vercel for Next.js, OR Render/Fly.io for Python.
- **PDF generation:** Puppeteer/Playwright for HTML → PDF. The PDF is generated from a styled HTML template — same skills as building a website.
- **Background jobs:** for long-running query batches — Inngest, Trigger.dev, or simple cron + queue.
- **Secrets management:** environment variables via host's secret manager.

### External services

| Service | Purpose | Monthly cost (v1) |
|---|---|---|
| OpenAI API | ChatGPT queries | $20-50 |
| Anthropic API | Claude queries | $20-50 |
| Perplexity API (Sonar) | Perplexity queries + citations | $20-50 |
| Google Gemini API | Gemini queries | $10-30 |
| SerpApi | Google AI Overviews + Google search | $50-100 |
| Listen Notes API | Podcast presence checks | Free tier |
| Wikipedia / Wikidata | Entity lookups | Free |
| Reddit API | Mention checks | Free |
| Crunchbase basic | Profile lookups | Free tier |
| Hosting + Postgres | App + DB | $20-30 |
| **Total flat infra** | | **~$140-310/mo** |

Per-audit variable cost: $5-15.

### What we are *not* using in v1

- **Ahrefs / SEMrush / Moz** — wrong shape of data for AI visibility; expensive. Revisit at scale if specific audits demand it.
- **SimilarWeb / BuzzSumo** — nice-to-have, unnecessary.
- **Custom scraping infrastructure** — SerpApi covers what we need; building scrapers is the wrong fight.
- **Vector DB / embeddings** — premature optimization for v1.

### Architectural notes

- **Pluggable engines:** Each AI engine integration lives behind a common interface (`runQuery(profile, query) → standardized response`). Adding a seventh engine is one new module, not a rewrite.
- **Pluggable scoring weights:** Weights live in config keyed by methodology version. Re-tuning is a config change, not a code change.
- **Reproducibility:** A given audit run is identified by `(profile_id, query_set_version, methodology_version, run_timestamp)`. Re-running with the same inputs produces the same outputs (with engine non-determinism noted in methodology).
- **Idempotent retries:** Engine API calls are wrapped in retry-with-backoff for transient errors (rate limits, 5xx). Failed queries are flagged in the audit; the report is delivered with whatever succeeded plus an explicit note about what didn't.

## Definition of "v1 Shipped"

Six concrete success criteria:

1. Jeremy can complete an end-to-end audit for a real client (profile in → PDF out) in **under 4 hours of his time** (vs ~12+ hours pure manual). The tool is the leverage; he is the judgment.
2. The tool reliably runs all 6 engines (with Microsoft Copilot acceptable as best-effort) with retries on transient errors.
3. The Visibility Score is **deterministic given fixed engine outputs** — same inputs, same outputs, same explanation.
4. Jeremy has **sold and delivered at least 3 audits to real paying customers** using the tool.
5. Per-audit margin (excluding Jeremy's time) is **≥ 95%**.
6. **Retainer mode works**: re-running queries on a stored profile produces a delta report comparing this month to the prior baseline.

## Out of Scope for v1 (Explicit Roadmap)

These are intentionally deferred. Listed here so they don't quietly creep into v1.

| Deferred item | Earliest revisit |
|---|---|
| Customer-facing dashboard (vs PDF-only) | v1.5 |
| Self-serve audit tier (lower price, no Jeremy in loop) | v2 |
| Reputation / review monitoring + automation | v2 (was "Tangent 3" in brainstorm) |
| Agency white-label / partner program | v3 |
| Marketing site for the audit product | After 10 paying customers |
| Multi-tenant / multi-operator (someone other than Jeremy delivering) | After 50 paying customers |
| Ahrefs/SEMrush-tier backlink integration | If a specific audit needs it |
| Predictive scoring ("you'll get N% more leads") | Probably never — undermines defensibility |

## First-Customer Path (Distribution Sketch)

Out of scope for the implementation plan, but worth noting so the product is shaped to support it:

- **Audits #1-3 (free or steeply discounted):** Run on three real businesses Jeremy knows personally — ideally one consultant, one fractional exec, one boutique studio. Goal: validate the methodology, refine the query library, prove the report quality. Document each as a case study (with permission).
- **Audits #4-10 (full price, network sale):** Pitch the audit through Jeremy's existing professional network — past clients, referrals, LinkedIn first-degree connections. Sales motion: warm intro → 20-min call where Jeremy runs a live partial audit during the call → propose audit + retainer.
- **Audits #11+:** Content marketing — case studies, a small set of public reports for well-known professionals (with permission, or anonymized), a NewBlood blog series on AI visibility. Slow funnel, high-quality leads.

The product spec doesn't depend on this path, but the v1 shape (PDF-only, human-in-the-loop, premium pricing) is matched to it.

## Open Decisions (For Jeremy's Review)

1. **Brand and naming** — confirm NewBlood-branded service line; decide on a working sub-brand name. *Default in spec: "NewBlood Signal" as a placeholder.*
2. **Tech stack choice** — Next.js/TS or Python/FastAPI? *Default: defer to plan-writing phase based on Jeremy's preference.*
3. **Microsoft Copilot inclusion in v1** — depends on API access feasibility. *Default: include if straightforward, slip to v1.5 otherwise.*
4. **Repository location** — does the new web app live in a new repo, or in a monorepo alongside newblood.com? *Default: new repo. The product is operationally and commercially separate.*
