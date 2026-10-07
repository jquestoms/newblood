# Inland Trades Prospecting — session playbook

Spec: `docs/superpowers/specs/2026-10-07-inland-trades-prospecting-design.md`. One session = one category. Claude works as Jeremy's assistant; Jeremy sends every email.

## Before the sweep
1. Read `tracker.md`; take the next category in Jeremy's order unless he names one.
2. List live same-trade deals now (`cd ~/Herd/customer-relationship-builder && npm run --silent crm -- find <trade>` and `crm -- pipeline`) and write them into the batch JSON's `conflict_deals`.
3. Load Chrome tools in one ToolSearch call, call `tabs_context_mcp`, open a NEW tab.

## Sweep (max ~40 businesses)
4. Maps search: `<category> near Loma Linda, CA`. Scroll the list. Record name, city, rating, reviews, website, phone, Maps category. Keep ~15 miles (Redlands, San Bernardino, Colton, Highland, Grand Terrace, Yucaipa, Mentone, Bloomington, Rialto edge).
5. Mark skips before deeper work: in CRM (`crm -- find "<name>"`), current/past client (CRM or `cd ~/Herd/qbo-cli && npm run --silent qbo -- customers "<name>"`), franchise/national chain.
6. For each remaining business, visit the site (~2-3 min): owner + founding year; contact options and any email; community signals (church, schools, youth sports, Chamber, faith language, "family-owned since"); brand/dealer affiliations; web read (dated/template, broken links, mobile, target cities). Record each tie with the URL it came from.
7. Email search order: site → contact page → Facebook About → BBB → Google Business Profile. Record `email_source`. None found → leave email null (form/phone only). NEVER guess an address.
8. Stop immediately on a captcha, "unusual traffic" page or block; tell Jeremy. No logins, no form submissions, no messages.

## Score and write up
9. Save observations to `batches/<YYYY-MM-DD>-<category-slug>.json`. Batch fields: `category`, `search`, `date`, `conflict_deals` [{trade, deal}], `businesses`. Business fields: `name`, `city`, `maps_category`, `rating`, `reviews`, `website`, `phone`, `skip` (null | in_crm | client | franchise), `owner_run`, `years`, `high_job_value`, `commercial`, `web_gaps` [str], `community_ties` / `brand_peers` / `timely` [{text, source}], `owner`, `email`, `email_kind` (person | generic), `email_source`, `notes`.
10. Run `cd ~/Herd/newblood/prospects/inland-trades && python3 tools/score.py batches/<file>.json --top 8`. Fix any `ERROR:` it prints; never silence one by inventing data.
11. Build `<YYYY-MM-DD>-<category-slug>.md` from `batch-template.md`: paste the scorer output, then for each top card add Contact, Way in (one sentence + source link), Internal notes (web gaps, never in the email) and the draft email per `email-rules.md`.
12. Hand Jeremy the batch: top cards in chat, file path, and the question "Which of these should go into the CRM?"

## After Jeremy picks
13. Per yes: propose `crm -- company add` (if new) and `crm -- deal add "<Business> Territory" --company "<Business>" --stage LEAD --next <send date> --note "<score>/100 · way in: ... · contact: ..."`; run on his yes; report the output lines.
14. Update `tracker.md` (status, date, file, CRM count, re-sweep = date + 6 months), then the hub per `~/Herd/CLAUDE.md` (newblood `TASKS.md`, `_hub/PROJECTS.md`, `_hub/TODAY.md` if listed).
15. Follow-up rhythm: one nudge ~7 days after Jeremy confirms a send; still quiet → NURTURE with a low-key date.

Tests: `cd tools && python3 -m unittest test_score`
