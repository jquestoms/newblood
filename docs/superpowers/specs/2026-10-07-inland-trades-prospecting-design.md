# Inland Trades Prospecting — Design

_Brainstormed with Jeremy 2026-10-07. Status: spec, awaiting review._

## Purpose

Give New Blood a steady, systematic source of Territory prospects without Jeremy doing the legwork. Claude works as Jeremy's assistant: it browses Google Maps one trade at a time, visits each business's website, qualifies and ranks what it finds, gathers contact details, and hands Jeremy a short, ranked list with a familiar way in for each and a draft first-touch email. Jeremy decides who gets contacted and sends every email himself.

**Success looks like:** one session produces 5 to 8 prospects Jeremy would actually email, each with a real contact route and an opening that does not depend on criticizing their website. Over time, the tracker shows which trades are swept and when each is due again.

## What Jeremy said (decisions)

- **Vertical:** trades and field services first. Health-adjacent practices and established owner-run businesses are the next fits (Jeremy has health-adjacent connections); same process, different category list, not in scope yet.
- **Territory:** 15 miles around Loma Linda (Redlands, San Bernardino, Colton, Highland, Grand Terrace, Yucaipa, Mentone, Bloomington, Rialto edge).
- **Familiar entry points, in order of weight:** (3) community ties, (5) brand/dealer peers, (4) a timely moment. Jeremy adds personal ties (customer history, his client book) himself when he sees them; he will not preload contacts or email history.
- **Discovery method:** Claude browses Google Maps in Jeremy's Chrome as an assistant would. No Places API, no bulk scraping.
- **Batch shape:** one category per session; top 5 to 8 written up; the rest in a table with a one-line reason.
- **Website problems qualify the lead; they never lead the first message.**

## Non-goals

- No API keys, scrapers, paid email-finding tools, or automated re-sweeps.
- No matching prospects against private rosters (church directory, soccer league families). Community ties come only from public signals; Jeremy supplies personal ties.
- No sending, form submissions, logins, or social messages by Claude.
- No health-adjacent or general owner-run lists yet.

## The process (one session = one category)

1. **Pick the category.** Default order (by Territory fit: high job value, owner-run, local): HVAC → garage doors → roofing → plumbing → electrical → solar → pest control → pool/landscape. Jeremy can reorder in the tracker.
2. **Sweep Maps.** In a new Chrome tab, search `<category> near Loma Linda, CA`, scroll the results, and note each listing within ~15 miles: name, address, rating, review count, website, phone, Maps category. Cap the session at ~40 businesses.
3. **Skip** before any deeper work: businesses already in the CRM (`crm find <name>`), current or past clients (QBO customer list / CRM), franchises and national chains with corporate sites.
4. **Visit each website** (~2 to 3 minutes each): owner name and founding year; contact options and any published email; community signals (church, schools, youth sports, Chamber membership, faith language, "family-owned since"); brand/dealer affiliations; quick website read (dated/template, broken links, mobile, which cities it targets). Optional for top candidates: CSLB license lookup for license age.
5. **Find an email**, in order: website → contact page → Facebook "About" → BBB → Google Business Profile. Record where it came from. If none, record "form/phone only". **Never guess an address.**
6. **Score, rank, write up** the top 5 to 8 (see below); everyone else goes in the table.
7. **Hand off.** Jeremy picks. For each yes, Claude proposes the exact `crm deal add` command, runs it on Jeremy's yes, and reports the output line.

## Scoring (out of 100)

**A. Fit — 35:** owner-run and local, not franchise (10) · established 10+ years (10) · high job value: installs, replacements, commercial (10) · commercial as well as residential (5).

**B. Opportunity — 30 (internal only):** strong reputation the web presence undersells, e.g. 4.7+ stars and 100+ reviews on a dated or template site (15) · visible web gaps: broken links, weak mobile, wrong cities, thin contact path (10) · no website or Facebook only (5; needs it most, usually buys less).

**C. Way in — 25:** community tie (12) · brand or dealer peer (8) · timely moment: move, new location, anniversary, award, new owner (5).

**D. Reachability — 10:** named person's email (10) · generic info@ (6) · form or phone only (2).

**Automatic skips:** in the CRM, current/past client, franchise or national chain.
**Exclusivity flag:** if a live CRM deal exists in the same trade (today: Daniels HVAC, NURTURE), same-trade prospects are flagged "conflicts with <deal> Territory", not dropped. Jeremy decides.

## Deliverables and where they live

All under `~/Herd/newblood/prospects/inland-trades/` (local only; prospect names never go to artifacts or external services):

- `<YYYY-MM-DD>-<category>.md` — the batch:
  - Header: category, search used, date, counts (seen / skipped and why / scored).
  - **Top 5 to 8 cards**, each with: name, city, trade, rating and reviews, years in business, owner if public · contact (email + source, phone, site) · score with a one-line reason per part · **Way in** in one sentence with the source link · **Internal notes** (web gaps for a future Four Gaps Audit; never in the email) · **draft first-touch email** in a plain fenced code block.
  - **Everyone else:** a table of name, city, score, one-line reason.
- `tracker.md` — one row per category: status, last swept, batch file, prospects added to the CRM, re-sweep due (6 months after the sweep), and Jeremy's category order.

## The first-touch email

- Opens on the familiar connection, not on a problem.
- One short paragraph on who Jeremy is: local, New Blood, a relevant peer (e.g. the Overhead Door Baltimore site) where it fits.
- One soft yes/no ask, e.g. a free look at how customers find them (the Four Gaps Audit, not named as such).
- No em dashes, no pricing, nothing negative about their site. Voice checked against `docs/voice-samples/` and the contact's tone if they are in the CRM.
- Signature carries New Blood's business address and an easy out ("If this isn't useful, just say so and I won't follow up") for CAN-SPAM.
- Printed in a plain fenced code block (hub rule).

## CRM handoff and follow-up

- Approved prospects: `crm deal add "<Business> Territory" --company "<Business>" --stage LEAD --next <send date> --note "<score> · way in: ... · contact: ..."` (company/contact added first if missing). Propose, confirm, run, report.
- Claude never logs a send until Jeremy says it went.
- Follow-up rhythm (Daniels pattern): one nudge after ~7 days; if still quiet, move to NURTURE with a low-key date.

## Guardrails

- ~40 businesses per session max, human browsing pace, a new Chrome tab (never Jeremy's existing tabs).
- No logins, no form submissions, no messages anywhere.
- If Maps shows a captcha, block, or unusual-traffic page: stop, tell Jeremy, do not work around it.
- Unverified numbers (review counts, years) are labelled with their source; nothing is quoted to a prospect unless verified on their own listing or site.

## Session hooks

- Hub: a session that finishes a batch updates `newblood/TASKS.md`, `_hub/PROJECTS.md` and, if listed, `_hub/TODAY.md` per the hub protocol.
- First run: **HVAC, batch 1** (Daniels skipped as an existing deal; exercises the exclusivity flag).

## Later (not now)

Health-adjacent and established owner-run category lists; a reusable script for the tracker/CRM cross-check; re-sweep reminders in `TODAY.md`.
