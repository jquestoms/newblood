# Discovery Combined Report — Design Spec

**Date:** 2026-06-19
**Status:** Approved design (pre-implementation)
**Author:** Jeremy Oms + Claude (brainstormed)
**Builds on:** New Blood Discovery form (`docs/superpowers/specs/2026-06-19-newblood-discovery-form-design.md`, implemented as the `inc/discovery/` module). First use: Overhead Door Company of Baltimore (OHDBalt).

---

## Background

The Discovery form captures one prospect's priorities as structured data (per-service importance/handling/gap, goal-vector positions, systems inventory, posture, open text). For an enterprise client like OHDBalt, the right move is to ask **all key stakeholders** (Chase, Paul, ops/marketing/CRM owners) to each fill out the form individually, then synthesize their responses into one picture that informs the comprehensive proposal.

The same client URL already supports this: each submission is a separate row in `wp_nb_discovery_responses`, attributed by respondent name/email under the same instance slug. What's missing is the synthesis step — a way to combine those responses into a single report.

This is the modern, async successor to New Blood's past half-day discovery workshop: less of the client's time, but arguably richer, because it **quantifies where the team aligns and where it diverges** — and stakeholder disagreement is often the most decision-useful signal of all.

## Concept

A **combined stakeholder report** for a discovery instance: a branded HTML page, behind a login gate, that aggregates all active submissions for the instance into a single ranked gap map, surfaces where stakeholders disagree, shows whether the team is pulling the same strategic direction, and preserves the qualitative texture verbatim. Internal-first; architected so a cleaned-up client-facing version is a later re-skin, not a rebuild.

## Audience

**Internal first, client-presentable later.** Build the synthesis engine and an internal-faced report now. The engine/renderer split is the seam: the future client-facing report swaps the chrome and (optionally) anonymizes/role-labels respondents, while reusing the same aggregation math and data.

## Goals

- Turn N individual stakeholder responses into one **combined gap map** that informs how New Blood scopes and prices the comprehensive proposal.
- Make stakeholder **disagreement** obvious and named — the highest-value discovery insight.
- Show whether the team shares a **strategic direction** (goal-vector alignment).
- Preserve qualitative answers verbatim per respondent (the texture the half-day session captured).
- Let New Blood **remove an accidental/duplicate submission** non-destructively.
- Be a **reusable** asset: any discovery instance gets a report with no per-client code.

## Non-goals (this round)

- No client-facing branding/delivery of the report yet (internal-first; re-skin is a later deliverable).
- No automatic statistical modeling beyond mean + range (no std-dev/variance surfaced; range + a plain-language flag was chosen for intuitiveness and future client-readability).
- No editing of submission content — only include/exclude.
- No live email of the report; it is a pull (view the gated URL).
- No charts library / heavy JS — server-rendered HTML + CSS bars.

## Architecture

Three new units in the existing `inc/discovery/` module, plus a CSS file and a schema bump. The **aggregation engine is pure** (no DB, no HTML) so it is fully unit-testable; the **controller** does data access + access control; the **renderer** produces HTML.

1. **Aggregation engine** — `inc/discovery/aggregate.php`: `nb_discovery_aggregate( array $submissions, array $instance ): array`. Input: the **active** (non-excluded) submissions, each a record `{ id, name, email, payload }` (`payload` already decoded), + the instance config. Threading `id` and `name` through the engine lets the roster carry the submission id needed for the exclude buttons. Output: one structured summary object (below). Pure function (no DB, no HTML).
2. **Report renderer** — `inc/discovery/report.php`: `nb_discovery_render_report( array $instance, array $aggregate, array $excluded_rows, string $nonce ): void`. Emits a complete branded standalone HTML document (reusing the discovery green/black tokens), including the roster with exclude controls and the excluded list with re-include controls.
3. **Report route + controller** — extend `inc/discovery/routing.php` and `controller.php`: a rewrite for `/discovery/{slug}/report` that, on `current_user_can('manage_options')`, queries the instance's rows (partitioned into active/excluded), runs the engine on active rows, and renders. Non-admins get a 404 (indistinguishable from a missing page).
4. **Exclude toggle** — an `admin_post` action (`admin_post_nb_discovery_exclude`): nonce + capability checked; flips the `excluded` flag for one submission id; redirects back to the report. No JS.
5. **CSS** — `assets/css/discovery-report.css`: report-specific layout (gap-map bars, split callouts, vector strip, roster).
6. **Schema bump** — add an `excluded` column; `NB_DISCOVERY_DB_VERSION` → `2`; the existing version-guarded `dbDelta` migration adds the column on the next request after deploy.

## The aggregation engine — output shape

`nb_discovery_aggregate( $submissions, $instance )` returns:

- `count` — number of active respondents.
- `respondents` — list of `{ id, name, email }` in submission order (the roster; `id` powers the per-row Exclude button).
- `services` — list, **ranked by `mean_gap` descending** (services not rated for handling by anyone sink to the bottom, consistent with the single-response email), each:
  - `key`, `label`
  - `mean_importance` (mean over all respondents, 0–10, one decimal)
  - `mean_handling` (mean over **only** respondents who rated handling — i.e. who set importance ≥ threshold-on-the-form so handling was captured; `null` if nobody rated it)
  - `mean_gap` (`mean_importance − mean_handling`; `null` when `mean_handling` is null)
  - `importance_spread` (max − min importance across respondents)
  - `handling_spread` (max − min handling across those who rated it; `null` if < 2 rated)
  - `split` (bool: `importance_spread >= NB_DISCOVERY_SPLIT_THRESHOLD`, default 4)
  - `high` / `low` — `{ name, score }` for the importance max / min (for the split callout; on ties, first respondent)
  - `per_respondent` — `[ { name, importance, handling|null } ]`
- `goal_vectors` — list (instance order), each: `key`, `left`, `right`, `mean` (−50..50), `spread`, `split` (bool: `spread >= 40`, a wider band since vectors are −50..50; configurable constant `NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD`), `per_respondent` `[ { name, position } ]`.
- `posture` — `fix_invest`: `mean`, `spread`, `per_respondent`; `timelines`: `[ { name, timeline } ]`.
- `qualitative` — `vision`, `open`, and each systems free-text field (`crm`, `lead_handling`, `reviews_system`, `call_tracking`, `territories`) plus `gbp_access`, each as `[ { name, value } ]` (verbatim, blanks shown as "(blank)").

The engine is given only active submissions; it never sees excluded rows. `per_respondent` entries are keyed by `name` for display; the roster (`respondents`) carries the `id` for exclude actions.

## Divergence definition

- **Per service:** spread = max − min of importance across respondents. **`split` when spread ≥ 4** (`NB_DISCOVERY_SPLIT_THRESHOLD`, a `define()` so it's tunable after real data). The report renders a plain-language callout, e.g. *"Team split — {high.name} rates this {high.score}/10, {low.name} rates it {low.score}/10."* No statistics jargon.
- **Per goal vector:** spread = max − min position (−50..50 scale). `split` when spread ≥ 40 (`NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD`). Renders as "team pulling different directions" on that axis.
- With **1 active response**, all spreads are 0 → no split flags (the report still renders that single response's gap map).

## Report sections (renderer)

1. **Header** — client name, "Combined discovery — N stakeholders," active roster (names). The roster doubles as a who's-responded tracker.
2. **Combined gap map** — services ranked by mean gap, each a row with: label, mean-importance bar, mean-handling bar (or "not rated by anyone"), the gap number, a **split callout** when flagged, and a collapsible per-respondent line. Largest gaps lead.
3. **Strategic direction** — the five goal vectors + posture fix↔invest, each as a left↔right strip with each respondent's dot and a split note where flagged.
4. **Timelines** — each respondent's timeline answer.
5. **In their words** — vision and open-text per respondent, then the systems inventory answers per respondent, verbatim.
6. **Excluded (N)** — at the bottom: any excluded submissions with name/date and a **Re-include** button. Hidden entirely when none.

Every roster row in §1 carries an **Exclude** button (nonce'd `admin_post` form). Re-include buttons live in §6.

## Soft-exclude — non-destructive submission removal

- **DB:** new column `excluded TINYINT(1) NOT NULL DEFAULT 0` on `wp_nb_discovery_responses`. `NB_DISCOVERY_DB_VERSION` bumps `1` → `2`; `nb_discovery_install_table()`'s `dbDelta` `CREATE TABLE` statement gains the column (dbDelta ALTERs the existing table to add it). The version-guarded `nb_discovery_maybe_migrate()` applies it automatically on the first request after deploy.
- **Controller:** the report query partitions rows: active (`excluded = 0`) → decoded payloads → engine; excluded (`excluded = 1`) → passed to the renderer for the §6 list only.
- **Toggle:** `admin_post_nb_discovery_exclude` handler — verifies `manage_options` and a nonce, sets `excluded` to 0/1 for the posted `id` (validated as belonging to the instance), redirects (303) back to the report URL. Reversible; the row is never deleted.
- **Hard delete** remains available as the WP-CLI escape hatch but is not part of normal flow.

## Access control

- The report route renders **only** when `current_user_can('manage_options')`; otherwise `set_404()` + a 404 (indistinguishable from a non-existent page — does not advertise the report's existence).
- The report page sends `nocache_headers()` + `DONOTCACHEPAGE` (consistent with the form page) so the gated content and the per-action nonce are never page-cached.
- The exclude `admin_post` action independently enforces capability + nonce (defense in depth — not reliant on the page gate alone).

## Data model additions

- `wp_nb_discovery_responses.excluded TINYINT(1) NOT NULL DEFAULT 0` (the only schema change). All existing columns unchanged. No new tables.

## Data flow (one report request)

`GET /discovery/overhead-door/report` → controller checks `manage_options` (else 404) → query rows for instance `overhead-door`, split by `excluded` → build active submission records `{ id, name, email, payload(decoded) }` → `nb_discovery_aggregate(active_submissions, instance)` → `nb_discovery_render_report(instance, aggregate, excluded_rows, nonce)` → echo HTML, `exit`. The `aggregate.respondents` (with `id`) drives the active roster + exclude buttons; `excluded_rows` (with name + `created_at`) drives the §6 re-include list.

## Edge cases

- **0 active responses** → friendly "No responses yet for this instance." (Excluded list may still render if all were excluded.)
- **1 active response** → renders its gap map; no split flags (spreads are 0).
- **A service rated for handling by only some respondents** → `mean_handling` averages just those; `per_respondent` shows `null`/"—" for the others; `mean_gap` uses `mean_handling`.
- **Duplicate / test submission** → exclude it (reversible); the roster makes dupes visible.
- **All responses excluded** → gap map shows the empty state; §6 lists the excluded with re-include.

## Reusability

The report is **config-driven and instance-agnostic**: the route takes any instance slug, the engine reads service/vector labels from `nb_discovery_get_instance()`, and the renderer has no OHDBalt-specific content. A new client gets a report for free. The internal→client re-skin later is a second renderer over the same `aggregate` object.

## Testing

- `tests/discovery/test-aggregate.php` (pure, standalone PHP-CLI, following the existing harness): construct several synthetic payloads and assert — mean importance/handling/gap; gap-descending ranking; `handling` averaged only over raters; `mean_handling`/`mean_gap` null when none rated; importance `spread` + `split` at the threshold boundary (3 → no split, 4 → split); `high`/`low` identification; goal-vector mean/spread/split; the **1-response** case (no splits) and the **0-response** case (empty shape).
- Integration (WP-CLI / browser): excluded rows drop out of the aggregate and re-include restores them; the report route 404s for a logged-out request and renders for an admin; the `admin_post` toggle rejects a missing/invalid nonce.

## Success criteria

- From multiple stakeholder submissions, New Blood gets one ranked gap map + named divergence + direction alignment + verbatim qualitative — enough to scope the comprehensive proposal with confidence.
- An accidental duplicate is removed in two clicks, reversibly, with no data loss.
- The report works for the next client with config only.
- The aggregation engine is pure and unit-tested; the report is admin-gated and uncacheable.

## Open questions for the plan

- Exact rewrite handling for `/discovery/{slug}/report` alongside the existing `/discovery/{slug}` rule (precedence/order), and the report query-var name.
- Whether the per-respondent service detail is always shown or collapsed by default (presentation polish, decide in the plan).
- Final microcopy for the split callouts and section headers.
- Bar rendering approach in CSS (e.g. width:%; flex) — settle in the plan.
