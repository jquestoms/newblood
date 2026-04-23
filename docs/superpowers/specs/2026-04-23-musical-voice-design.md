# Musical / Compositional Voice for newblood.com

**Status:** Approved (brainstorm), pending implementation plan
**Date:** 2026-04-23
**Scope:** Positioning and copy voice for newblood.com. Visual/motion execution is out of scope for this spec (may be picked up in a follow-up).

## Summary

Give newblood.com a distinct, cross-disciplinary voice by threading a *musical / compositional* vocabulary through the site's high-visibility copy. The studio speaks with the taste of a team that works across forms — composition, phrasing, pacing — without ever turning itself into a music bit or a founder biography.

The goal is **positioning and voice (Option B)**, dressed by a small amount of **creative execution (Option C)** in the form of rewritten section labels and process framing. Founder-as-auteur narrative (Option D) is explicitly out.

## Voice Principle

> The musical reading must work as **normal English first**, and carry a quiet second meaning for readers who notice.

If a phrase only makes sense as a music pun ("let's cut a demo," "riff on your brand"), it's out. Double-meaning words that read correctly on both tracks — *arrange, compose, phrasing, rhythm, tune, measure* — are in.

## Palette

**Tier 1 — Natural English on both tracks. Safe to use.**
arrange / arrangement · compose / composition · phrasing · pacing · rhythm · measure · tune / tuning · range · key · note · ensemble

**Tier 2 — Musical-leaning but still readable. Use sparingly, in the right spot.**
score · harmony · refrain · movement

**Tier 3 — Out. Reads as costume.**
cut a demo · drop · beat · groove · riff · jam · hook · bars · remix · stems · tracks · mix · verse · chorus · bridge · encore · gig · set · liner notes · B-side

## Concentration Map

The governing principle: **more voice at the top of the funnel, more clarity at the bottom.** The further a reader is from a decision to act, the more room there is for taste. The closer they are to completing a task (picking a price, filling out a form), the more the copy gets out of their way.

**High concentration — voice is clearly felt.** One strong moment per page.
- Hero (homepage)
- About Story — the "Where creativity meets code" section
- Services / "How we work" — relabeled as *How we arrange a project*, with the process framed as four movements

**Medium concentration — one earned phrase, no more.**
- Case study intros and project cards
- Services page descriptions
- Contact page lead-in

**Low / none — stay neutral, business-clear.**
- Pricing page body copy
- Contact form field labels, button labels, validation and errors
- Navigation, footer, legal, meta descriptions / SEO copy

## Concrete Copy

### 1. Hero — `patterns/hero.php`

Replaces the label, headline, and subtitle (hero.php:12–18).

- **Label:** 25+ years of craft, tuned by modern AI
- **Headline:** Websites with rhythm, range, and restraint.
- **Subtitle:** We pair decades of hands-on craft with modern AI workflows to build sites that are measured in every sense — more considered, more technically ambitious, and more memorable than the budget should allow.

The CTAs ("See Our Work" / "View Pricing") stay unchanged — they sit at the bottom of the funnel on this section.

### 2. About Story — `patterns/about-story.php`

Replaces the "Where creativity meets code." section (about-story.php:35–42). The 25+ years / Fortune 500 / intro paragraphs above it stay unchanged.

- **Heading:** Composition, not configuration.
- **Paragraph 1:** A good website behaves a little like a good piece of music — proportion, phrasing, and restraint on the details that don't matter so the ones that do can land. It's not an accident, and it's not a template. It's arranged.
- **Paragraph 2:** That kind of care used to be a premium. Twenty-five years of engineering, paired with modern AI workflows, is what makes it reachable again — even on a small-business budget. Interactive 3D, generative visuals, physics-driven animation, custom integrations: tools we reach for whenever a project calls for them, not features you're upsold.
- **Paragraph 3:** We build with the ear of a studio trained across more than one form. Not because a website is a song, but because the things that make one work are the same things that make the other work: pacing, proportion, and nothing unearned.

Paragraph 3 is the one place on the site where the voice is explicitly named. It stops short of founder bio by attributing the range to "the studio," not a person.

### 3. Services / Process — "Four movements" (`patterns/how-it-works.php`)

Replaces the entire contents of `patterns/how-it-works.php`. The existing pattern has three steps ("Tell Us Your Vision" / "We Build It Fast" / "You Own It") — the new version has **four movements** and drops the speed emphasis.

Two structural notes for the implementation:
- **3 columns → 4 columns.** Layout changes; the numbered accent treatment (1/2/3 in accent color) can continue to 4.
- **Speed language removed intentionally.** The current step 2 ("We Build It Fast … in days") contradicts the brand direction of creative ambition over quick turnaround. The rewrite drops it deliberately, not by oversight.
- **"You Own It" content is folded into Launch.** Ownership and handoff (current step 3) is subsumed into Movement 4, where the existing content control / hosting / updates message should be preserved in one earned sentence.

Copy:

- **Label:** How it works (unchanged, or "The arrangement" as a Tier 2 alternative — see open question below)
- **Section heading:** How we arrange a project
- **Section lede:** Four movements, one score. Every project is different, but the shape is always the same — listen first, settle on the arrangement, build it well, send it out to meet real people.

1. **Discovery.** We listen first. Who you serve, what they need, where you're trying to go — this is the tuning step, and skipping it is how sites end up sounding like everyone else's.
2. **Composition.** With the brief in hand, we design the arrangement — structure, voice, visual system, key moments. Nothing loud without a reason, nothing quiet without intent.
3. **Build.** The arrangement becomes a real, fast, accessible site. Clean code, considered performance, content you can actually manage. This is where craft shows.
4. **Launch.** Launch is the downbeat — not the final note. We stay on to refine, watch how the site meets real traffic, and keep it in tune. You own the site and your content from day one; we handle hosting, security, and updates behind the scenes.

Only "Composition" is a swap from standard agency vocabulary (it replaces "Design"). Discovery / Build / Launch are the normal industry words, so the list reads cleanly even when the musical frame is invisible.

**Open question for implementation:** Keep the section label `How it works` (clean, zero-risk) or change it to something like `The arrangement` (Tier 2, more voice, slight risk of feeling precious). Default to the safer choice; revisit after seeing the page live.

## Out of Scope (Explicit)

- **New service offerings** (audio branding, illustration, video) — these were flagged as possible but not part of this design.
- **Visual or motion execution** (musical phrasing in animation timing, typography with compositional rhythm, audio elements) — not in this spec.
- **Founder-as-auteur narrative** — explicitly excluded.
- **Pricing, contact, nav, footer, legal, form microcopy** — stays in the current neutral voice.

## Editorial Rules for Future Pages

When new pages or patterns are added, apply these rules to keep the voice consistent without spreading it thin:

1. One earned musical phrase per high-funnel page, max. Two is already too many.
2. Never use a Tier 3 word. If tempted, rewrite.
3. Read every musical phrase aloud with the music meaning stripped. If it stops being English, rewrite.
4. At the bottom of the funnel (pricing, forms, nav), default to neutral clarity. Taste is not helpful when someone is trying to buy or contact.

## Success Criteria

- A reader who never notices the musical frame reads clean, confident agency copy.
- A reader who *does* notice it finds a consistent ear across the hero, about, and process sections — enough to feel that the studio has a point of view.
- No page reads as a bit, a gimmick, or a founder's resume.
