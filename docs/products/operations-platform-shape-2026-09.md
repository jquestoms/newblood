# The Operations Platform: shape

**Status:** shape doc, v1, 2026-09-03. Written so the price can be printed. Brainstormed with Jeremy the same day; the positions below are his, the wording is a draft for his review.
**Sits above:** the Territory Platform ($35,000 + $3,200/mo). **Framing role:** the Operations Platform is the tier that makes Territory the middle door, not the top. It has to be real enough to sell on its own, and it will be, but its first job is to give Territory its proportion.
**Site surfaces:** `/pricing` door two, third card. No page of its own yet.

---

## 1. The problem it exists to solve

Territory wins the front door: the searches, the site, the inquiry, saved before anyone is notified. Then the story breaks.

The lead goes to whichever phone or inbox caught it. The quote lives in one salesperson's head. The job is in dispatch, the invoice is in accounting, and the only place all of it meets is a spreadsheet somebody keeps for the owner. When the owner asks which search made money this quarter, the honest answer takes a week and is still a guess.

The Operations Platform puts the whole business on one record. A lead, the quote it became, the job that followed, and the dollars it earned are one story, readable by the people who need it, without anyone retyping anything.

## 2. Who it is for

Established operators who already run Territory, or are buying it now, and who have:

- a sales team of more than one person, each with their own way of working
- an office that is the bottleneck at nine at night and when two lines are busy
- existing systems for dispatch, accounting, and some form of customer list that they are not ready to leave

It is not for a company whose problem is still the front door. That is Territory, and the Four Gaps Audit will say so.

## 3. Two modes for the record

The record is the spine. Everything else attaches to it. There are two ways to have one, and the doc says both plainly because the choice is the operator's and it usually changes over time.

**Connected.** Their existing system stays the system of record. We wire the front door, the agents, and the reporting into it through its API or its exports, and we build the reporting on top. Their people keep the screens they know. This is the usual start, and it is the easier sell: nobody breaks from what they run today for a company they have known for ninety days.

Limits, stated up front: the platform is only as good as their system's API, and the day-to-day experience inside the record is someone else's product. We name the systems we have connected before and we scope the connection after we have seen theirs, in the audit.

**Owned.** We build the record: leads, quotes, outcomes, customers, and the history between them, built around how their people already sell (the way the RTZ CMS and the New Blood CRM were built). Their dispatch and accounting stay where they are, and we read from them to attach jobs and dollars. The record becomes the place where the story is whole.

This is where an operator ends up once the connection has earned it, typically years into the relationship, not months. Nothing in the connected mode is wasted when they move: the front door, the agents, and the attribution carry over. Only the spine changes.

## 4. Four pillars

### The record and attribution

Every inquiry has a unique ID, a timestamp, a channel, and its original source, the search or campaign where it can be identified (this part already exists in Territory). Operations adds the rest of the chain: which person owns it, what was quoted, what was won, what was invoiced. Revenue by source becomes a report, not a project. In connected mode the chain is read from their systems; in owned mode it lives in ours.

### The agents

Four, each with a person in the loop and an approval rule, added one at a time:

- **Voice.** Answers the phone on a script the operator approves, in their voice, takes the job in, and escalates to a person. It exists for nine at night, the third caller when two lines are busy, and the thirty seconds before a human can get there. Voice carries its own risk rules: it never quotes a price, never commits a crew, never promises a time, and every call is recorded and readable. It is a separate line in the price and it is never switched on in a first phase.
- **Quoting assistant.** Drafts the estimate from the inquiry plus the operator's own price book and past jobs. A person approves and sends. Turns "the quote went quiet" into a same-day quote.
- **Specifications assistant.** The spec desk with their phone number on it. Answers the team's product, specification, and drawing questions from the operator's own documents, indexed (the RTZ knowledge-base pattern: thousands of source documents, tens of thousands of indexed chunks).
- **Dispatch triage.** Reads the inquiry, classifies urgency and service type, and routes it to the right branch or person before anyone touches it. In connected mode it writes into their dispatch system; the operator decides whether it writes at all.

The typing assistant, missed-call text-back, and drafted follow-up on stalled quotes remain part of Territory and keep running underneath.

### The portal

The customer's side of the counter, for the commercial buyer, the fleet manager, the architect, and the contractor:

- **Quotes and orders.** See the quote, approve it, see the order's status.
- **Service history and assets.** Every unit installed or serviced for that customer, with dates and what was done.
- **Specs and drawings.** The specification desk turned outward: pull the documents for your own project without calling.
- **Service requests and scheduling.** Raise a call, pick a window, see who is coming. Feeds dispatch triage directly.

The portal core is quotes, orders, and history. Specs and scheduling are added when the assistant and the triage that feed them are live.

### Reporting

Territory's monthly report grows into the owner's report: revenue by source, quote-to-win by person and by branch, response times by channel, what the agents handled and what they escalated. Same rule as Territory: no report looks complete while it is not.

## 5. How it lands

Phases on top of a live Territory build. Territory goes live first, in its ninety days. Operations then lands one capability at a time, each proven on the operator's own numbers before the next starts:

1. **The record and attribution.** Connected mode unless the operator has chosen owned. Revenue by source is the proof.
2. **The agents.** Quoting and specifications first, dispatch triage next, voice last and only when chosen. Each runs shadowed before it runs live.
3. **The portal.** Core first, then specs and scheduling.

Six to nine months after Territory goes live, for a single-location operator. Nothing is switched on unproven, and nothing the operator relies on changes until they approve the switch. Additional branches or brands are added as further locations on the same record, each scoped as a phase of its own.

## 6. The number

**From $90,000, the build.** The base buys: the connected-mode record and attribution, two agents (usually quoting and specifications), and the portal core.

**What moves it up:** voice, the owned-mode record, dispatch triage that writes into their system, the full portal, and each additional location.

**The monthly.** Above Territory's $3,200, scoped with the build, because it carries the agents' usage, the connections, and the reporting. Telephone and text usage beyond a generous included pool is passed through at cost.

**The ladder holds.** The audit fee credits in full toward the Territory build, and the Territory build credits in full toward Operations, within twelve months. No first dollar is spent twice.

Nothing above is a quote. The first Operations engagement is scoped after Territory is live and the audit has read the operator's systems.

## 7. Commitments, restated for this level

- **Measurement is foundation first.** Revenue by source is the first thing built, not the last.
- **Their systems, on their terms.** Connected mode reads; it writes only where the operator says so. Switching a connection off leaves their tools as they were.
- **The assistant types.** Unless voice is chosen, scripted, and approved. Then it speaks on that script and nothing else.
- **Everything is in their name.** Domains, numbers, accounts, data, content, and in owned mode, the record itself.
- **Sized for the problem in front of us.** One of a few engagements, someone on it weekly, a direct line to the person doing the work.

## 8. What the site says

Pricing page, door two, third card:

> **Operations Platform.** For established operators who want the whole business, not just the front door, on one record.
> **from $90,000** build, plus a monthly scoped with it.
> - Everything in Territory
> - One record: lead, quote, job, dollars
> - Agents for the work behind the phone: quoting, specifications, dispatch, and voice when you choose it
> - A customer portal: quotes, orders, history
> - Connected to the systems you run, or built as your own

The "Scoped per operator" line becomes "from $90,000". The credit line under door two is unchanged.

## Open questions, held here rather than decided

- The named list of systems we have connected or will connect first. The first Operations engagement decides it.
- The monthly figure. Named after the first scope, when the agents' usage is known.
- Whether a `/operations/` page is warranted before the first engagement, or whether the pricing card and this doc carry it until then.
