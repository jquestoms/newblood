# Territory Repositioning, Phase One

**Status:** Approved (brainstorm), pending implementation plan
**Date:** 2026-09-02
**Scope:** The first four moves of `docs/newblood-territory-repositioning-2026-09.md` (v2): fix the live price-modesty copy, add the Territory flagship page, rewrite the pricing page as two doors, add the two-door section to the homepage. Everything else in that plan (Four Gaps Audit page, Operations shape doc, vertical page, case study, Notes) is later phases.

## Summary

newblood.com sells well below the work New Blood just signed. Phase one removes the four lines that say "small budget," adds a second door for established operators, and puts a $35,000 platform with a $3,200/mo program on the pricing page with a named tier above it. The craft positioning stays in the lead: the hero, the About story, and the process section keep their musical voice untouched. The new surfaces speak in a plain authority register.

## Decisions carried in from the plan

- Craft leads. Territory is the second door, placed directly below the hero, never in it.
- Doors are named by buyer situation, not size: **A site that represents you** / **A platform that wins your territory**. Business keeps "Popular."
- Territory is Build + Manage + the content program, packaged. The four verbs remain "what we do."
- The Operations Platform prints no price in phase one. It shows its shape with "scoped per operator."
- No client is named on any new surface in phase one.
- No em dashes in new copy. Existing copy is left alone unless the line is being rewritten anyway.
- Musical voice: the new pattern copy stays at "low / none" concentration per the voice spec (pricing and the flagship page are bottom-of-funnel). One earned Tier 1 word per page at most; none is fine.

## Step 1: Fix the live contradictions

Four edits in three pattern files. Each replaces the whole line.

**`patterns/hero.php:18`** (subtitle). Replace the sentence ending with:

> We pair decades of hands-on craft with modern AI workflows to build sites that are measured in every sense: more considered, more technically ambitious, and more memorable than the brief called for.

The headline and CTAs do not change.

**`patterns/about-story.php:25`** (h3):

> Fortune 500 craft. At the scale of your business.

**`patterns/about-story.php:31`**:

> What's changed is the reach. By pairing decades of hands-on craft with modern AI workflows, we take on creative, technically ambitious projects that used to require a much bigger team. The strategic thinking and creative polish that high-end agencies reserve for their largest accounts now goes into every project we take on, whatever its size.

**`patterns/about-story.php:41`**:

> That kind of care used to be reserved for the largest accounts. Twenty-five years of engineering, paired with modern AI workflows, is what lets us bring it to every project, from a five-page site to a platform that runs a company's front door. Interactive 3D, generative visuals, physics-driven animation, custom integrations: tools we reach for whenever a project calls for them, not features you're upsold.

**`patterns/faq.php:19`** ("How long does it take to build a website?"):

> It depends on the shape of the project. A focused site takes a few weeks from discovery to launch. A platform build runs about ninety days: foundations first, then build and prove, then launch and watch. You get a timeline at the end of discovery, and we don't rush the parts that decide whether the site works.

## Step 2: The Territory flagship page

### Where it lives
- A WordPress page, slug `territory`, title **The Territory Platform**, excerpt **For established operators** (the excerpt renders as the label in `page-header`). Uses the default `templates/page.html`, so it gets the page header above and the site CTA below automatically.
- Body is one new pattern, `newblood/territory`, at `patterns/territory.php`, inserted into the page content as `<!-- wp:pattern {"slug":"newblood/territory"} /-->`. Same convention as `/pricing` and `/services`.
- Added to the primary block navigation (wp_navigation 6737) as **Territory**, `/territory/`, placed between Services and Work. Verify the header and footer navigations (6738, 6739) and mirror only where the other main pages appear.
- New pattern files do not register until the `wp_theme_files_patterns` transients are deleted from `wp_options` (see the block-pattern-cache note in memory).

### Sections and copy

The page is seven sections. Each is a `wp:group` with the pattern's own `contentSize` (760px for prose, 1200px for the card rows), following the existing pattern conventions. Use `nb-reveal` per section, never on the outer wrapper (tall bodies stay invisible otherwise).

**1. Opening** (prose, 760px)

> Label: The Territory Platform
> H2: Own the searches your name already earned<span green>.</span>
>
> This is for companies that built their reputation over decades: trades, distributors, commercial service operators. You are the name people ask for. And every year more of the searches for that name are bought by younger competitors and lead-gen middlemen who never earned it.
>
> The Territory Platform is a website, a content program, and a measurement system built to take those searches back and keep them. Everything it earns is yours: domains, numbers, accounts, data, content.

**2. The four gaps** (four short cards, 1200px, `nb-stagger` columns wrapping 4 -> 2x2 like `nb-services-cards`)

> Label: What we usually find
> H2: Four gaps, read from your own numbers<span green>.</span>
>
> **The website.** Built years ago, rarely touched, and not the reason anyone calls. It has to become the place a job starts.
> **The inquiry.** Calls and forms that arrive, get handled, and leave no record you can learn from. Nothing is saved before it is sent.
> **The territory.** The searches in your trade and your counties, in the words your customers type. Someone is ranking for them. It should be you.
> **The proof.** Reviews, projects, and specifications that live in filing cabinets and inboxes, invisible where customers actually look.

**3. What the platform includes** (two columns, 1200px: "The build, once" and "The program, monthly")

> Label: What you get
> H2: The build once. The program every month<span green>.</span>
>
> **The build, once.** A new site on a platform built to run a company's front door. Save-first forms that record every inquiry before they notify anyone. An AI assistant that types, never talks: website chat, after-hours cover, missed-call text-back, on a script you approve. A reviews engine. A resource desk for the specifications, drawings, and documents your buyers ask for. Project highlights that prove the work. Tracked numbers and call recording, every account in your name.
>
> **The program, monthly.** Hosting and operations, lead capture and routing, the assistant, reviews, and the monthly report as one system. Alongside it, the content program: the projects engine, the specification desk, and the service-by-county pages that earn the territory back. The content line is separable. It can be taken elsewhere and the platform keeps running.

**4. The first ninety days** (three numbered columns, reuse the `how-it-works` number treatment)

> Label: How it starts
> H2: Live and producing in ninety days<span green>.</span>
>
> **Days 1 to 30. Foundations.** Design approved by you. Content drafted from your records and your customers' own words. Numbers provisioned in your name.
> **Days 31 to 60. Build and prove.** Site assembled. Forms wired to save before they notify. Assistant trained on approved content. Controlled test calls and submissions.
> **Days 61 to 90. Launch and watch.** Parallel run beside the old site. Launch when the counts match. Daily monitoring through launch week. First monthly report.
>
> Closing line under the columns: While this happens, nothing you rely on changes. Your phones, your email, your current advertising, and the old website all stay exactly as they are until you approve each switch.

**5. The commitments** (four short items, prose list or 2x2 cards)

> Label: What we hold to
> H2: Four commitments<span green>.</span>
>
> **Measurement is foundation first.** Search and page reporting come with the build. Lead outcomes and job values are added later, only when you choose. No report will look complete while it is not.
> **Your systems are read only.** We pull numbers out of the tools you already run. We never write in. Stopping the read leaves them untouched.
> **The assistant types, it never talks.** No automated voice calls, no automated email. Text and chat, on a script you approve.
> **Everything is in your name.** Domains, hosting, numbers, accounts, data, content. If we ever parted ways, all of it stays with you and keeps working.

**6. One operator per market** (prose, 760px)

> Label: Exclusivity
> H2: One operator per trade, per market<span green>.</span>
>
> We build one Territory Platform per trade in a given market. Once it is yours, we do not build the same platform for a competitor in your counties. The searches we win for you are not for sale to the company down the road.

**7. Pricing and the next step** (one featured card, 760px, then CTA)

> Label: The plan
> H2: Two numbers, no surprises<span green>.</span>
>
> **The build, once: $35,000.** The new site and the full platform behind it, built in the first ninety days.
> **The program: $3,200 a month.** In two lines: the platform at $1,950 and the content program at $1,250. The content line is the separable one.
>
> Paid search management is available as an added line if your advertising moves to us, priced when you decide. Our recommendation is always a gradual taper, with nothing cut for the first six months.
>
> Every engagement starts with the Four Gaps Audit: we read your ad platform, your inquiry database, and your search territory, and show you the four gaps on your own numbers. Its fee credits in full toward the build.
>
> Motto line, set as a pull quote: The content earns the visit. The website earns the call. And everything that earns is yours.
>
> Button: **Ask for the Four Gaps Audit** -> `/contact/` (repointed to the audit page in phase two).

### Register rules for this page
- Plain authority. Short sentences. The one defensive line on this page is the opening paragraph's "bought by younger competitors and lead-gen middlemen who never earned it." No second one.
- No musical phrasing. "Tune" may not appear.
- No client name, no client figures (no year counts, no review counts, no ad-spend history).
- No em dashes.

### Styling
- Reuse `nb-glass` cards, `nb-label`, the green-period h2 accent, `nb-gradient-section` on alternating sections, and `nb-stagger` on card rows. Only new CSS should be a `.nb-territory` scope in `patterns.css` for the featured pricing card and the ninety-day numbering. If a card gets a stretched link, add the link class to the `nb-glass > *:not(...)` exclusion in `utilities.css`.

## Step 3: Pricing page as two doors

Rewrite `patterns/pricing-table.php`. The page content (`/pricing`, ID 5142) keeps its two pattern references (`pricing-table`, `faq`); nothing changes in the page record.

### Structure

```
group.nb-pricing (full, gradient)
  intro: "Every plan includes hosting on our managed infrastructure." (unchanged)

  door one header
    label: Door one
    h2: A site that represents you<span green>.</span>
    lede: Custom design and clean code for businesses that want a website made for them.
  columns.nb-stagger (2 cols)
    Starter  (unchanged copy, $3,500)
    Business (unchanged copy, "Popular" badge, $5,000)
  sub-header + columns.nb-tune-pricing (unchanged: "Already have a site? Tune it instead." Tune $2,000 / Tune Plus $4,500)

  door two header
    label: Door two
    h2: A platform that wins your territory<span green>.</span>
    lede: For established operators: trades, distributors, commercial service companies. Read your own numbers, then build the platform that takes the searches back.
  columns.nb-stagger (3 cols)
    Four Gaps Audit
    Territory Platform ("Flagship" badge, same treatment as "Popular", green border)
    Operations Platform
  credit line (paragraph, centered, small):
    "No first dollar is spent twice. The audit fee credits in full toward the Territory build, and the Territory build credits in full toward Operations, within twelve months."
```

The Reach column is removed. Its bullets (custom development, advanced integrations, AI features, complex workflows, dedicated collaboration) are absorbed into the two platform cards below.

### Door two card copy

**Four Gaps Audit**
> For an established operator who wants to see the gaps before committing to anything.
> **$1,500 to $2,500**
> - We read your ad platform, inquiry database, and search territory
> - The four gaps, shown on your own numbers
> - Where you appear in search and AI answers today, and who appears instead
> - A written plan you keep, whoever builds it
> - Credits in full toward the Territory build
> Button: Ask for the audit -> /contact/

**Territory Platform** (Flagship)
> A website, a content program, and a measurement system, with everything in your name.
> **$35,000** build, then **$3,200 / mo**
> - Platform build, live in ninety days
> - Save-first forms, tracked numbers, call recording
> - AI intake that types, never talks
> - Reviews engine, resource desk, project highlights
> - Monthly content program, separable line
> - One operator per trade, per market
> Button: See the platform -> /territory/

**Operations Platform**
> For multi-location operators who want the business, not just the front door, on one system.
> **Scoped per operator**
> - Everything in Territory
> - CRM backbone and revenue attribution
> - AI agents for the work behind the phone
> - Customer portal
> - Multi-location build-out
> Button: Let's talk -> /contact/

The price line changes to "from $90,000" only after the Operations shape doc exists (phase-one does not print it).

### Layout notes
- A two-column `wp:columns` for door one at the same `blockGap` as today. On tablet the door-two row wraps 3 -> 1 like the current row; check `nb-tune-pricing` for the existing breakpoint and reuse it.
- Musical voice: none. Pricing is bottom-of-funnel.

## Step 4: Homepage two-door section

### Template change
`templates/front-page.html`: replace `<!-- wp:pattern {"slug":"newblood/statement"} /-->` with `<!-- wp:pattern {"slug":"newblood/two-doors"} /-->`. The `statement.php` file stays on disk (not deleted; slug unchanged) so it can return elsewhere.

### New pattern `patterns/two-doors.php` (`newblood/two-doors`)

Full-width group, 1200px content, two `nb-glass` cards in a `wp:columns` with `nb-stagger`, stacking at tablet width.

> Label (centered above the cards): Two ways in
>
> **Card one**
> H2: A site that represents you<span green>.</span>
> Custom design and clean code for businesses that want a website made for them, not chosen from a template. Build, Tune, Manage, Empower, with pricing you can read before you call.
> Links: See pricing -> /pricing/ · See our work -> /work/
>
> **Card two**
> H2: A platform that wins your territory<span green>.</span>
> For companies that built their name over decades, and refuse to rent it back one click at a time. A website, a content program, and a measurement system, with every account in your name.
> Link: The Territory Platform -> /territory/

Card two's line is the homepage's one defensive line. The hero above keeps its musical register; this section has none.

### Styling
- New `.nb-two-doors` scope in `patterns.css`: equal-height cards, the h2 at `clamp(1.5rem, 3vw, 2rem)`, links as `nb-btn-primary` (card two) and text links (card one). The section sits on the plain background so the hero gradient and the social-proof strip still read as separate bands.
- If the whole card becomes a stretched link, apply the `utilities.css` exclusion rule.

### About page
The Signal intro on `/about` does **not** change in phase one. Its CTA repoints to the Four Gaps Audit page in phase two, when that page exists. (The plan lists "About repoint" under step 4; it moves to phase two because there is nothing to point at yet.)

## Verification

For every pattern edit:
- `php -l` on each edited pattern file.
- Block comment balance: `grep -c '<!-- wp:' file` equals `grep -c '<!-- /wp:' file` plus the count of self-closing `/-->` blocks.
- No em dashes in new copy: `grep -n '—' patterns/territory.php patterns/two-doors.php patterns/pricing-table.php` returns only lines that pre-existed (pricing-table's untouched Starter/Business/Tune copy).
- No client names or figures on new surfaces: `grep -n -i -E 'overhead|ohdbalt|baltimore|1947|3,000|nine years' patterns/territory.php patterns/two-doors.php patterns/pricing-table.php` returns nothing.

Rendering, on `http://newblood.test`:
- `/` shows hero, then the two-door section, then social proof. Card links resolve.
- `/territory/` renders all seven sections, header label reads "For established operators," nav shows Territory.
- `/pricing/` shows both doors, Business still "Popular," Territory "Flagship," no Reach, credit line present.
- `/about/` and the FAQ show the rewritten lines.
- Responsive check at 375, 768, and 1280 wide: card rows wrap, no horizontal scroll, no `nb-reveal` section stuck invisible at scroll position zero.

Deploy:
- `./deploy.sh --dry-run`, then `./deploy.sh` (theme files only). Confirm with Jeremy before the real run; it changes production.
- The Territory page and the navigation item live in the database, so `deploy.sh` does not carry them. Create them on production with WP-CLI over SSH (same commands as local) after the theme deploy, then delete the pattern transients on production.
- Live-verify the four URLs above on `https://newblood.com`.

## Out of scope for phase one

- Four Gaps Audit page and the discovery-form wiring (phase two).
- Operations Platform shape doc and its printed price.
- The distributor vertical page.
- Any case study or Notes article from the OHDBalt engagement.
- Contact-form changes (a subject preselect for the audit can come with phase two).
- Retiring the Search & AI Visibility sales doc: it simply stops being sent; nothing on the site references it.

## Success criteria

- A reader on the homepage can tell within one scroll that New Blood does two kinds of work and which one is theirs.
- An established operator lands on `/territory/` and finds the engagement, the ninety days, the commitments, the exclusivity, and both prices, without a client name doing the work.
- `/pricing/` reads $35,000 as the sensible middle, not the maximum.
- Nothing on the site says "budget," "small-business budget," or "1-2 weeks."
- The hero, About story, and process section read exactly as they did, minus the four rewritten lines.
