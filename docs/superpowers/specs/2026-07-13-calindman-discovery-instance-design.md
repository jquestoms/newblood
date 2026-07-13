# Discovery Form — C.A. Lindman Instance + Config-Driven Refactor

**Date:** 2026-07-13
**Status:** Approved design (pre-implementation)
**Author:** Jeremy Oms + Claude (brainstormed)
**Client:** C.A. Lindman, Inc. (CAL) + Carolina Restoration & Waterproofing (CRW) — calindman.com

---

## Background

C.A. Lindman (client since ~2020; New Blood built and hosts calindman.com on Nexcess) has new leadership: Scott Carter is now President, Craig Abell VP of Corporate Operations. Stephanie Cary (Operations) emailed 2026-07-13 requesting About Us rewrites for both CAL and CRW — and noted "Scott is currently focusing on updating CAL website" over the next few months. Jeremy has a longstanding relationship with Scott.

The play: deliver the About Us updates, and in the follow-up email include a **discovery form link** so Scott (and Stephanie) can signal priorities for the broader site update — feeding a tailored quote, exactly as done for OHDBalt.

This is **instance #2** of the New Blood Discovery module (`newblood/wp-content/themes/newblood/inc/discovery/`, spec `2026-06-19-newblood-discovery-form-design.md`). Instance #1 (OHDBalt, slug `overhead-door`) is live with real submission data that must not break.

## Goals

1. A CAL-tailored discovery form at `newblood.com/discovery/calindman`.
2. Finish the module's reusability promise: the two remaining hardcoded areas (the "systems" question set and the goal-vector keys) become per-instance config, so instance #3+ is pure config.
3. Zero behavior change for the OHDBalt instance — form, stored data, report.

## Non-goals

- No pricing/proposal content in the form (unchanged from module spec).
- No changes to visual design, flow (5 steps), storage schema, or the admin report layout.
- The About Us content updates themselves are tracked in `calindman/TASKS.md`, not this spec.

---

## Part 1 — Reusability refactor (config-driven systems + vectors)

### Current hardcodes

1. **Systems section** (`view.php` step 3): seven fixed fields — `crm`, `lead_handling`, `leads_per_month`, `reviews_system`, `call_tracking`, `gbp_access` (radio), `territories` — with OHDBalt-flavored labels. Mirrored in `submission.php` (sanitize), `email.php` (notification lines), `aggregate.php` (`$sys_fields`), `report.php`.
2. **Goal-vector keys**: `config.php` defines vectors per instance and `view.php` renders from config, but `submission.php` sanitizes a fixed key list (`residential_commercial`, …) and aggregation assumes them. Different keys for a new instance would be silently dropped.

### Change

`config.php` — each instance gains:

```php
'systems_questions' => array(
    array( 'key' => 'crm', 'label' => '…', 'type' => 'text' ),                // types: text | textarea | radio
    array( 'key' => 'gbp_access', 'label' => '…', 'type' => 'radio',
           'options' => array( 'yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not sure' ),
           'default' => 'unsure' ),
    // optional per-question: 'hint' => '…', 'rows' => 3 (textarea)
),
```

Consumers switch to iterating config:

- `view.php` — renders step 3 from `$instance['systems_questions']`.
- `submission.php` — sanitizes `systems` by walking the instance's question list (`text` → `sanitize_text_field`, `textarea` → `sanitize_textarea_field`, `radio` → whitelist against `options`, fall back to `default`). Goal vectors sanitize by walking `$instance['goal_vectors']` keys instead of the fixed five.
- `email.php` — notification lines generated from config labels.
- `aggregate.php` / `report.php` — `$sys_fields` and vector keys come from the instance config.

### OHDBalt compatibility

- The `overhead-door` entry gets `systems_questions` expressing its **exact current seven questions** (same keys, same labels, same radio options/default) and keeps its five vector keys. Stored payload shape is unchanged, so existing submissions (incl. Chase's real response) render identically.
- Verification: snapshot the rendered `/discovery/overhead-door` form HTML and the admin report HTML before the refactor; diff after — the bar is byte-identical (allowing only nonce/timestamp churn). Existing test suite (`tests/discovery/`) stays green; new tests cover config-driven sanitize (unknown keys dropped, radio whitelisting, per-instance vector keys).

---

## Part 2 — The `calindman` instance

New entry in `nb_discovery_instances()`:

- **slug:** `calindman` · **client_name:** C.A. Lindman, Inc. · **recipient:** joms@newblood.com
- **logo:** CAL logo (green) — copy `logo-green.png` from calindman.com uploads into the newblood theme's discovery assets; `''` fallback hides it if the asset step is skipped.

### Welcome

- **Title:** "Let's build this around C.A. Lindman"
- **Intro:** "Thank you for the chance to help shape what's next for C.A. Lindman online. The questions below take about 10 minutes — your answers shape a plan built around CAL and CRW, not a template."

### Service rows (12, existing four clusters)

| Group | Key | Label | Hint |
|---|---|---|---|
| get_found | `website` | Website design & user experience | How the site looks, feels, and guides visitors. |
| get_found | `seo_aeo` | Search & AI-answer visibility (SEO/AEO) | Being found in Google search and in AI answers like ChatGPT. |
| get_found | `brand_creative` | Brand & creative | Project photography and video that present the company well. |
| convert | `portfolio` | Project portfolio & case studies | Showcasing completed work — galleries, before/after, project stories. |
| convert | `lead_capture` | Lead capture & conversion | Turning visitors into project inquiries and RFPs. |
| convert | `reviews` | Reviews, reputation & references | Earning and showcasing reviews and client references. |
| operate | `hosting_security` | Hosting, security & maintenance | Keeping the site fast, online, secure, and up to date. |
| operate | `crm` | CRM / bid & project pipeline | One place to track opportunities from inquiry to close. |
| operate | `customer_comms` | Client communication | Following up with prospects and clients by email and text. |
| grow | `recruiting` | Recruiting & careers | Attracting field talent and making hiring easier. |
| grow | `lead_gen` | Lead generation | Driving new prospects through paid search and social ads. |
| grow | `reporting` | Reporting & analytics | Clear reporting on what's working and what it's producing. |

### Goal vectors

| Key | Left | Right |
|---|---|---|
| `volume_fit` | More project volume | Better-fit projects |
| `deepen_expand` | Deepen current markets | Expand into new regions |
| `cal_crw` | Focus on CAL | One plan across CAL + CRW |
| `topline_lean` | Grow the top line | Run leaner |
| `handson_managed` | We stay hands-on | Fully managed for us |

(`cal_crw` doubles as the brand-scope signal per the "cover CAL as a whole" decision.)

### Systems questions (step 3)

| Key | Label | Type |
|---|---|---|
| `pipeline_tracking` | How do you track leads, bids, and projects today? (CRM, spreadsheets, something else) | text |
| `lead_handling` | When a project inquiry comes in through the website today, what happens? | textarea |
| `lead_sources` | Where do most new project opportunities come from today? (referrals, repeat clients, search, bid lists…) | text |
| `photo_library` | Where do project photos and job documentation live today? | text |
| `gbp_access` | Can you grant manager access to your Google Business Profile(s)? | radio yes/no/unsure (default unsure) |
| `coverage` | Which companies and locations should this plan cover? (C.A. Lindman, CRW, both) | textarea |

### Unchanged from module defaults

Timeline options (`As soon as possible` / `Within 1–3 months` / `3–6 months` / `Just exploring`), fix-urgent ↔ invest-long-term posture slider, vision prompt, open question, thank-you screen. The existing `section_copy` strings are already client-neutral and are reused verbatim.

---

## Delivery email (draft — Jeremy sends, alongside the About Us completion note)

> **Subject:** About Us updates are live — and a quick way to shape what's next
>
> Hi Stephanie (and Scott),
>
> The About Us revisions for both C.A. Lindman and CRW are live — links below. Great to hear Scott is taking the site forward; congratulations to him and Craig on the new roles.
>
> Since Scott is planning updates over the coming months, I put together a short discovery step — about 10 minutes: **newblood.com/discovery/calindman**. It captures what matters most to you both, so the plan I bring back reflects CAL's priorities, not my guesses. Feel free to each fill it out separately — differing perspectives are exactly the point.
>
> — Jeremy

## Rollout

1. Refactor + OHDBalt config migration, tests green, snapshot diff clean.
2. Add `calindman` instance + logo asset; verify at `newblood.test/discovery/calindman` (form, submit, email record, admin report).
3. Deploy via newblood `deploy.sh` (theme-only); verify live form + a test submission on prod.
4. Send delivery email (Jeremy) after the About Us updates ship.

## Success criteria

- `/discovery/calindman` live and on-brand; test submission stores, emails, and renders in the admin report (single + aggregate).
- `/discovery/overhead-door` form and report byte-identical pre/post refactor; existing submissions untouched.
- Instance #3 requires config only — no PHP edits outside `config.php`.
