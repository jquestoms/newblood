# Discovery form — intake refinement (segmented controls, clusters, vision-first, baseline)

**Date:** 2026-06-22
**Status:** Approved design, ready for plan
**Context:** The `/discovery/{slug}` intake form (first instance: `overhead-door`) is being sent to a warm client (Chase Cummings, Overhead Door Company of Baltimore) who asked for "the maximum scope you'd recommend." Positioning goal: read as a **small business with enterprise-grade capability** — personal and considered, not a faceless template mill, but visibly expert. This refinement removes UX friction that undercut the premium feel and adds one substantive question that lets the eventual plan do ROI math.

Related: delivery email at `docs/clients/ohdbalt-discovery-email-DRAFT.md`. Original form spec: `docs/superpowers/specs/2026-06-19-newblood-discovery-form-design.md`.

## Goals

1. Replace fiddly 0–10 / −50…50 **sliders** with **segmented buttons** — fixes both the "everything defaults to 0 / reads as nothing matters" problem and mobile thumb-precision in one move.
2. **Reorder** so the client opens on a warm, easy section, not the heaviest lift.
3. **Cluster** the 12 capabilities into 4 named groups so a long list reads as a framework (an expertise signal) and feels shorter.
4. Add **one quantitative baseline** (web leads/month) so the comeback plan can model leads→revenue.
5. Add a **skip-permission line** and a **progress sense** to lower friction and signal confidence.

Non-goals: no budget question (client explicitly asked for max scope — anchoring to budget would contradict it); no decision-maker/stakeholder question this round; no change to the report/email aggregation logic beyond passing through the new field.

## Decisions

### Segmented controls map onto the existing 0–10 scale

The storage, aggregation, report (`mean_importance * 10` bar widths), and email all assume a 0–10 integer importance scale and a 0–10 handling scale. To keep that pipeline intact, the segmented buttons **store representative 0–10 values** rather than introducing a new scale:

**Importance** (was `<input type="range" 0–10>`, default 0):

| Button label    | Stored value |
|-----------------|--------------|
| Not a priority  | 0            |
| Nice to have    | 3            |
| Important       | 7            |
| Critical        | 10           |

No button is pre-selected → "untouched" is genuinely distinct from "not a priority." If a row is left untouched, `discovery.js` **omits that service entirely from the submitted `services` array** — so `aggregate.php` finds no data for it (`imp === null`) and skips it as not-rated. (This differs from today, where the default-0 slider always submits importance 0 and every service is included. "Not a priority" is now an explicit choice that stores 0; leaving the row untouched stores nothing.)

**Handling — "How well is this handled today?"** (was `<input type="range" 0–10>`):

| Button label | Stored value |
|--------------|--------------|
| Poorly       | 2            |
| OK           | 5            |
| Well         | 7            |
| Very well    | 10           |

**Handling follow-up reveals on Important *and* Critical** — i.e. `threshold = 7` stays exactly as it is in `submission.php` (`NB_DISCOVERY_THRESHOLD`), `discovery.js`, and the `view.php` config. This is truer to the old slider (7–10 revealed it), yields a richer gap map, and requires **zero changes** to the threshold semantics in PHP/JS. (Decided over Critical-only, which would have required bumping the threshold to 10.)

**Goal vectors + fix/invest** (was `<input type="range" -50–50>`, default 0) → **5-point segmented spectrum**:

| Position             | Stored value |
|----------------------|--------------|
| Strongly {left}      | −50          |
| Lean {left}          | −25          |
| No preference        | 0 (default)  |
| Lean {right}         | +25          |
| Strongly {right}     | +50          |

Default selection = "No preference" (0) — a legitimate neutral for a true spectrum, so there is no "untouched looks like an answer" problem here. The report's position math (`(mean + 50) / 100 * 100`) and split thresholds (`NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD = 40`) are unaffected.

**Net effect on the data model:** none. Stored payload shapes, value ranges, clamps, gap computation, aggregation, email, and report rendering are all unchanged. Only the *input widget* changes. Tests that assert specific slider-derived values get updated to the segmented values.

### Section order — vision first

New order (was: priorities, goals, systems, direction, open):

1. **Where you're headed** — 3-year vision textarea + goal-vector spectrums
2. **What matters most** — the 12 priorities (now clustered)
3. **What's in place today** — systems questions + new leads-baseline question
4. **Direction & timing** — fix/invest spectrum + timeline select
5. **Anything else** — open textarea + name/email

### Capability clusters

Rendered as labelled subheads within the priorities section. Implemented by adding a `group` key to each service entry in `config.php` and grouping at render time in `view.php`.

| Cluster        | Capabilities (keys)                                              |
|----------------|-----------------------------------------------------------------|
| **Get found**  | `website`, `seo_aeo`, `brand_creative`                          |
| **Convert**    | `lead_capture`, `reviews`, `content`                           |
| **Operate**    | `hosting_security`, `crm`, `customer_comms`, `automation_ai`    |
| **Grow**       | `lead_gen`, `reporting`                                          |

Grouping changes display order in the email and report too (they iterate `instance['services']` order) — acceptable and arguably better.

### Baseline question — web leads/month (free-text)

A single free-text field in "What's in place today," placed near the call-tracking question:

> **Roughly how many web leads a month right now?**
> Ballpark is fine — and if you're not tracking this yet, just say so.

Free-text (not a number input) on purpose: per the discovery call, the honest answer is likely "we don't track that," and *that answer is itself a finding* — it flags the attribution/reporting gap the plan will close. Stored as `systems.leads_per_month`, sanitized as text, surfaced in the email "SYSTEMS TODAY" block and the report "In their words" section like the other systems fields.

### Skip permission + progress sense

- **Skip line** in the welcome intro: *"Answer what you can — skip anything that doesn't apply, and we'll fill the gaps when we talk."*
- **Slim sticky scroll-progress bar** at the top of the form (CSS bar, width driven by scroll position in `discovery.js`).
- **"Step N of 5"** eyebrow on each section header.

## File-by-file impact

- **`inc/discovery/config.php`** — add `group` to each service; add `leads_per_month` to a fields list / section copy as needed; cluster label copy.
- **`inc/discovery/view.php`** — segmented-button markup for importance, handling, vectors, fix/invest; reorder sections; render cluster subheads; baseline field; intro skip line; progress bar element + "Step N of 5" eyebrows.
- **`assets/js/discovery.js`** — read selected segmented buttons (via `data-val`) instead of slider `.value`; reveal handling row when importance ≥ 7; collect `leads_per_month`; drive the scroll-progress bar; keep submit/honeypot/error flow unchanged.
- **`assets/css/discovery.css`** — segmented-button component (`.nb-d-seg` / selected state, touch-friendly sizing), progress bar, cluster subhead styles; retire the slider-specific rules that are no longer used.
- **`inc/discovery/submission.php`** — sanitize/passthrough `systems.leads_per_month` (text). Threshold + clamps unchanged.
- **`inc/discovery/aggregate.php`** — add `leads_per_month` to `sys_fields` so it appears in qualitative output.
- **`inc/discovery/email.php`** — add a "Web leads/month" line to the SYSTEMS TODAY block.
- **`inc/discovery/report.php`** — add `leads_per_month` to `$qual_labels`.
- **`tests/discovery/*`** — update value-based assertions to the segmented mapping (0/3/7/10 importance; 2/5/7/10 handling; ±25/±50 vectors); add coverage for `leads_per_month` passthrough. Suite must stay green.

## Testing

- Run the existing `tests/discovery/` suite; update assertions for the new stored values; keep it green.
- `php -l` on every edited PHP file (block/standalone PHP is parsed at request time).
- Manual: load `/discovery/overhead-door` in a desktop browser **and a phone** — confirm segmented buttons are thumb-friendly, the handling row reveals on Important/Critical, the progress bar tracks scroll, and a full submission produces a correct admin email + `/discovery/overhead-door/report`.

## Risks

- **Tests assert slider values:** expected; update them as part of the change, don't skip.
- **Cache:** `discovery.css` / `discovery.js` are cache-busted by `filemtime()` already — no manual version bump.
- **Report shows discrete values (e.g. importance 7):** acceptable — reads naturally as "7/10" and Jeremy is the only report reader.
