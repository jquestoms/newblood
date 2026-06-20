# 📋 Tasks — New Blood

**Status:** ✅ LAUNCHED — 2026-06-09 (live on prod, version-controlled on `main`). Optional polish deferred.
**Goal (one sentence):** Launch the redesigned New Blood site — new theme + reworked content — live and clean. — **Achieved.**

---

## ▶️ Doing now
- [ ] **New Blood Discovery form + combined report (OHDBalt edition)** — ✅ BUILT & VERIFIED & **🚀 DEPLOYED TO PROD (2026-06-19)**. Form LIVE at https://newblood.com/discovery/overhead-door (200, renders 12 service rows); admin-gated combined report LIVE at /discovery/overhead-door/report (404 logged-out / 200+renders for admin); `wp_nb_discovery_responses` table created on prod (schema v2), rewrites flushed. Deployed via new hardened **`deploy.sh`** (theme-only allowlist, true dry-run, 0 deletions — additive). Per-task + final whole-feature reviews passed; hardened (nocache, honeypot+throttle, mail logging, admin gate). **▶️ Next: (1) confirm prod SMTP so the form's summary email delivers (DB persistence works regardless); (2) merge PR #2 → main so git matches prod; (3) ask Chase + Paul + ops/CRM to each fill the form → view the combined report. Draft email: `docs/clients/ohdbalt-discovery-email-DRAFT.md`.** _(feeds the OHDBalt comprehensive proposal)_
  - ✅ 2026-06-19 — **Combined stakeholder report built + verified** (`inc/discovery/{aggregate,report}.php` + `assets/css/discovery-report.css`): aggregation engine (pure — means/gaps/divergence/qualitative), admin-gated `/discovery/{slug}/report` (404 logged-out), ranked gap map with Team-split divergence callouts (threshold ≥4/vector ≥40), non-destructive soft-exclude (reversible toggle, Excluded roster), 5/5 unit tests PASS, multi-stakeholder render confirmed SPLIT_SHOWN/ROSTER/EXCLUDE_FORMS; reusable per-instance (no OHDBalt hardcoding).
  - 📌 **MONDAY 6/22 PICKUP** (in priority order):
    1. **Test prod SMTP** — submit a throwaway entry at https://newblood.com/discovery/overhead-door, check whether `joms@newblood.com` gets the summary email. If nothing arrives, install **WP Mail SMTP** on prod (Nexcess) and reconfigure. _DB persistence already works, so no submissions are lost in the meantime — this only gates the notification email._
    2. **Merge PR #2** (`feature/redesign` → main) so git matches what's live on prod (`gh pr view 2`). Prod runs the feature/redesign theme; main is still pre-discovery.
    3. **Send Chase the link** — draft ready at `docs/clients/ohdbalt-discovery-email-DRAFT.md`. Ask him to loop in **Paul + whoever owns ops/CRM** so each fills it individually; then view synthesized results at `/discovery/overhead-door/report` (logged into wp-admin).
    - Redeploy if needed: `./deploy.sh` (dry-runs first; theme-only, additive). Prod report shows the empty state until real stakeholders submit (the earlier test row was on the LOCAL install, not prod).
- [ ] **Jeremy: call with Chase (OHDBolt) 6/11** — discovery kit at docs/clients/seo-discovery-call-kit.md; after the call, review the Search & AI Visibility doc (docs/clients/search-ai-visibility-program.pdf) and send. Optional: publish as unlisted URL on newblood.com (awaiting Jeremy's content approval).
- [x] ✅ **Launch complete & signed off (2026-06-09)** — site live on prod, merged to `main`, commerce intact, SEO meta + /case-studies/ 301 done.
- [ ] _Deferred (optional polish, no rush):_ mobile/cross-browser pass · test a WooCommerce checkout · confirm footer email/phone are current · swap RTZ status line to a live link when the RTZ site launches Jul 1.

## 🔜 Up next
- [ ] **Signal Logs**: cron the collectors (Nexcess pull + `ingest-vercel` daily) so windows accrue hands-free · move `signal_events` off newfoodcenter's Neon into its own DB before v1 · monthly report regeneration.
- [ ] **Signal Logs v1** — multi-tenant web UI + client login + scheduled collection (cron). Jeremy decides: pricing/bundling, UI home (signal.newblood.com?), stack (note: ingest endpoint seeds whatever stack v1 uses). v0 live in `signal-logs/` (4 sites; starsvolleyball pending pubkey install).
- [ ] **🧭 New Blood 5-year brainstorm** (pinned 6/12) — block a few hours; seed notes in `docs/playbooks/trusted-client-seo-playbook.md` §"Beyond the window" (owned properties as income generators · service→product · the 3–5 yr trust window).
> _Launch checklist — done items checked off this activity._
- [x] Full backup before go-live (Nexcess, downloaded)
- [x] Cutover to production (theme activated, content migrated, commerce intact)
- [x] Post-launch smoke test on the live URL (pages 200, footer/case-study fixes, contact form, commerce untouched)
- [x] Pushed to GitHub + merged `feature/redesign` → `main`
- [ ] Proofread all reworked content (typos, headings, calls-to-action)
- [ ] Mobile + cross-browser check (Safari, Chrome, mobile widths)
- [ ] Test every link, form, and button (contact form verified; checkout still to test)
- [ ] SEO: page titles, meta descriptions, sitemap; 301s for legacy URLs (/case-studies/ → /work/)
- [ ] Analytics installed and firing

## ✅ Done (newest first)
- [x] 2026-06-19 — **Discovery form Task 9: e2e verification + delivery email draft** — 4/4 unit tests PASS; 200 REST submit verified; email rendered (gap-map descending, glyphs correct); test rows cleaned; ohdbalt-discovery-email-DRAFT.md written; committed `462a40de`.
- [x] 2026-06-19 — **Discovery form Task 8: gap-map-first summary email** — `email.php` created; `nb_discovery_format_email` (gap-descending sort, UTF-8 glyphs) + `nb_discovery_send_email`; TDD RED→GREEN; `index.php` bootstrap finalized; committed `89b7c23f`.
- [x] 2026-06-19 — **Discovery form Task 7: REST submission pipeline** — `submission.php` created; sanitize/gap-compute helpers TDD RED→GREEN; REST `POST newblood/v1/discovery` inserts row w/ server-computed gap; integration verified (200 `{"ok":true}`, gap=6); committed `8f83f8f7`.
- [x] 2026-06-19 — **Discovery form Task 2: DB table + version-guarded migration** — `inc/discovery/db.php` created; `wp_nb_discovery_responses` (7 cols) verified via DESCRIBE; `nb_discovery_db_version` option = 1; committed `33debd0d`.
- [x] 2026-06-19 — **Discovery form Task 1: module bootstrap + OHDBalt config** — `inc/discovery/index.php` + `config.php` created, test suite RED→GREEN, wired into `functions.php`; committed `0d2e65f3`.
- [x] 2026-06-19 — **Discovery-form design brainstormed + spec written** — New Blood Discovery form (OHDBalt edition): hybrid build (tailored now, reusable later), progressive dual-axis importance/current "gap map" sliders, forward-goal bipolar sliders, indirect budget, gracious thank-you. Born from Chase's "show us the most comprehensive solution" reply. Spec committed on `feature/redesign`: `docs/superpowers/specs/2026-06-19-newblood-discovery-form-design.md`.
- [x] 2026-06-12 — **Signal Logs v0.5 — VERCEL ADAPTER LIVE end-to-end**: new `~/Herd/signal-ingest` Vercel project (drain receiver w/ secret auth + NDJSON/gzip + export API; SSO protection kept ON via automation-bypass header) · `signal_events` table in Neon · **team drain `drn_THbDAoGRuvlCaEWm` enabled over 5 projects** (newfoodcenter-claude, mmc-claude1, mmc-nextjs, rtzav-2026, rtzav-website) · `signal.py ingest-vercel` merges into local reports (cursor-based). Verified live: Bingbot/PetalBot events from mikesmasterclasses flowing within minutes. Forward-only accrual started 6/12 — newfoodcenter day-one launch data now recording.
- [x] 2026-06-12 — 📤 **OHDBalt proposal SENT** + **playbook distilled** (`docs/playbooks/trusted-client-seo-playbook.md`): the repeatable trusted-client sequence (call prep → Q&A drill → audit → Signal Logs exhibit → owner-targeted proposal → champion sheet → send email), tone rules, pricing posture, reusable templates, candidate roster. Strategic frame: short trust-window before SEO work is automated away — pipeline = the ~15 hosted clients.
- [x] 2026-06-12 — **Signal Logs v0 BUILT & RUN** (`signal-logs/signal.py`: ingest → IP-verify → report): 948k log events from 4 Nexcess sites (30-day window), verified against OpenAI/Google/Perplexity published ranges + Bing/Apple rDNS. Headlines: **ohdbalt — 263 verified ChatGPT-User live fetches + 274 ChatGPT-referred human visits/mo** (incl. dock-equipment pages) · akta — 867 ChatGPT referrals/mo · caught impostor "Perplexity-User" traffic · corrected audit (wp-sitemap.xml exists). Reports: `signal-logs/reports/`. Raw logs git-ignored.
- [x] 2026-06-12 — Nexcess credential roster completed: `.nexcess-credentials` in 5 project folders (newblood created, dadabilities extended w/ key, starsvolleyball + ohdbalt new), all chmod 600 + git-leak check clean; mmc/newblood .gitignore hardened. Starsvolleyball SSH key not yet authorized server-side (Jeremy: install pubkey via Nexcess portal).
- [x] 2026-06-11 — **Chase call Q&A cheat sheet built** (`docs/clients/ohdbalt-call-cheatsheet.md`) — 7 mock Q&As drilled with Jeremy + refined (pricing w/ wince playbook, staged timeline, word-of-mouth counter, AI/Enspire "twist" moment), corrected co-op vs Red Ribbon Rewards distinction (verified: OHD corporate does offer distributor co-op programs), close-of-call asks.
- [x] 2026-06-11 — **Signal Logs concept scoped + doc written** (`docs/products/signal-logs-concept.md`): self-serve log-analytics dashboard for the ~15 Nexcess + Vercel hosted clients — AI-crawler visibility (verified-bot tables), AI-referral clicks, mini-GA traffic/health views; adapter architecture (Nexcess SFTP, Vercel Log Drains) → one multi-tenant UI; $/mo add-on. Born from OHDBalt call prep.
- [x] 2026-06-10 — Brainstormed + built the "Search & AI Visibility" program doc (branded HTML + 5-page PDF): $3,500 foundation sweep (Tune-family) + from-$950/mo growth retainer, Signal AI-answer monitoring headlined, 90-day build / no lock-in terms. For Chase (OHDBolt) and future SEO prospects.
- [x] 2026-06-10 — Updated RTZ case study for the admin redesign (PRs #41–43): AI paste-to-draft intake section, task-first admin bullets, TipTap + real-page preview + activity-log details, new pull quote. Committed, pushed to prod (page 6855, Jeremy-approved), verified live; old sidebar copy confirmed gone.
- [x] 2026-06-09 — ✅ REDESIGN LAUNCH SIGNED OFF as complete — live on prod, version-controlled on `main`, commerce intact, SEO meta + legacy redirect done. Remaining items are optional polish.
- [x] 2026-06-09 — Added theme-level 301 redirect /case-studies/ → /work/ (verified live; Yoast Free has no redirect manager). Orphaned legacy URLs left as clean 404s by design.
- [x] 2026-06-09 — Set Yoast SEO titles + meta descriptions + focus keyphrases for all 11 redesign pages (live + verified); reproducible record at docs/seo/page-meta.php
- [x] 2026-06-09 — Pushed feature/redesign to GitHub + fast-forward merged → main (99 commits; redesign now version-controlled & backed up on origin/main)
- [x] 2026-06-09 — Added RTZ Audio Visual case study (Build engagement, launching Jul 1): body markup, page (prod id 6855), showcase card + trusted-by entry; live on prod for prospect review
- [x] 2026-06-09 — Footer "dark header" real fix: text was white but covered by the .nb-footer::before blend; z-lifted footer content above it (verified headless). Same root cause as case-study faded line.
- [x] 2026-06-09 — Case-study fixes: migrated page excerpts (killed the green run-on "blob" eyebrow on prod) + fixed faded first body line (section-blend z-index/padding)
- [x] 2026-06-09 — Footer legibility fix (white site-title, brighter column headings) + contact-form hardening (title-constant binding + mailto fallback); deployed to prod
- [x] 2026-06-09 — Post-launch classic→block review: fixed missing logo (site_logo), broken contact form (WPForms migrated + resolve-by-title shortcode), unpublished stale 2013–2018 posts (collapsed homepage "From Notes"), restored footer email/phone
- [x] 2026-06-08 — 🚀 LAUNCHED: activated newblood theme on production; migrated content (about/contact/pricing updated, services/work/4 case studies created fresh at IDs 6846–6851), product image set; verified live — all pages 200, commerce intact (997 orders / 22 subs untouched)
- [x] 2026-06-08 — Pushed 172 new images to prod (additive); deployed newblood theme (inactive) + unpublished 9 legacy pages
- [x] 2026-06-08 — Pre-flight: full Nexcess backup taken + downloaded; SSH+WP-CLI confirmed; prod inventory mapped (prod still on classic grandportfolio theme)
- [x] 2026-06-08 — Authored production deploy runbook (selective content migration; commerce tables untouched)
- [x] 2026-06-08 — Made navigation portable (inlined into header/footer parts; dropped fragile ref IDs)
- [x] 2026-06-08 — Withheld Overhead Door from showcase/proof bar + drafted its case study (active sales talks)
- [x] 2026-06-08 — Styled WooCommerce pages to the redesign + swapped in new product image (text-forward billing pages)

## 🧱 Blocked / waiting
- [ ] _(nothing yet)_

## 💭 Notes & ideas
- Deploy runbook: docs/superpowers/plans/2026-06-08-redesign-production-deploy-runbook.md
- Prod SSH is password-based (no key); using sshpass. Consider key auth for smoother future deploys.
