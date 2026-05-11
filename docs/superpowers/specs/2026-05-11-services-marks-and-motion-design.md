# Services-page marks and motion — design

**Date:** 2026-05-11
**Author:** Jeremy + Claude
**Scope:** `/services` page (`services-detail` pattern) and the homepage services card row (`services-cards` pattern)

## Problem

The `/services` page is text-heavy. Each of the four service blocks is two paragraphs of body copy plus eight bullets. The four service identifiers today are emoji icon-badges (`⚡ 🎛️ 🛡️ 🤝`), which read as placeholder content rather than considered design. Scan-readers — likely a clear majority of visitors — get no visual anchor per service and bounce off the wall of text.

The page needs imagery that solves the actual scan problem (anchors per service) without competing with the deliberative voice or introducing brand-risk dissonance (AI-generated photography).

## Solution

A small set of custom geometric SVG marks — one per service — paired with a restrained scroll-triggered motion treatment. No AI imagery, no photographic textures, no third-party particle libraries. Mono-green, on-brand, deliberate.

Each mark is a 64px (presentation size) inline SVG with line-stroke geometry. When a service section enters the viewport, the SVG path strokes draw themselves in over ~1.1s while a soft green radial bloom blooms behind the mark and recedes. After that, the mark sits quietly.

The marks replace the existing emoji icon-badges entirely in both contexts (homepage card row and `/services` detail page). One mark, two contexts.

## The four marks

Each mark is mono-color (#4ade80, the existing theme green) on a transparent background. Stroke width 2.5, round line caps, round joins. Baseline elements (where present) use stroke-width 1.5 and 35% opacity.

### Build — rising bars on a baseline

Four vertical bars at increasing heights (12, 22, 34, 46 px), evenly spaced left-to-right, anchored to a faint horizontal baseline.

Reads as foundation / construction first. Carries quiet musical resonance — notes ascending on a staff.

### Tune — oscillating sine wave on a baseline

A continuous sine wave (Q-curve approximation, three half-cycles) drawn across the SVG width, anchored to a faint horizontal baseline.

Reads as frequency / tuning / adjustment. The most literal musical reference of the four — a sound wave is universally understood and on-brand for the Tune service.

### Manage — concentric rings with a center dot

Outer circle (radius 22), inner dashed ring (radius 14, dasharray 2 3, stroke-width 1.5), center filled dot (radius 3).

Reads as shield / care / containment. The dashed inner ring suggests active monitoring without being literal. The center dot is the held tone — care concentrated on a single point.

### Empower — ring with outward arrow

A ring (radius 14) on the left, a horizontal line extending out to the right, terminating in a small arrowhead.

Reads as hand-off / passing control outward. The client's hands now on the wheel. This is the mark I was least settled on during brainstorming; flag it for revisit if it doesn't hold up at full size.

## Motion treatment

### Draw-in stroke (primary motion)

When a mark's parent service section enters the viewport (via the existing `.nb-reveal` infrastructure), each `<path>`, `<line>`, `<circle>`, or `<polyline>` element within the SVG animates its `stroke-dashoffset` from a full-length value to `0` over 1100ms with cubic-bezier easing (0.65, 0, 0.35, 1). Secondary elements within a mark are staggered with 200ms increments so the primary shape arrives first.

The "full-length value" can be approximated with a generous flat constant (200 worked for the brainstorm demo because every shape is under that length), or set per-path via `path.getTotalLength()` in JS for pixel-perfect draw-in. The plan phase picks one — flat constant is simpler and visually indistinguishable here; per-path is more robust if mark shapes are ever revised.

For the filled dot in the Manage mark, animate `transform: scale(0)` to `scale(1)` with a slight overshoot, starting at 900ms (after the strokes have largely drawn in).

### Gradient bloom (secondary motion)

Behind each mark, a 140×140px radial-gradient bloom (rgba(74,222,128,0.18) → transparent at 60%) sits at `opacity: 0`. As the section enters the viewport, this transitions to `opacity: 1` over 1400ms via CSS opacity transition. The bloom stays at full opacity once revealed — it does not pulse or loop.

### Trigger and cadence

Animations fire exactly once per page load, when the service block enters the viewport. The existing IntersectionObserver in `assets/js/scroll-reveal.js` already handles this for `.nb-reveal` (calls `observer.unobserve()` after first intersection). No new JS infrastructure required — extend the same hook.

### Reduced-motion behavior

Users with `prefers-reduced-motion: reduce` set:
- All marks render in their final state (no `stroke-dashoffset` animation).
- The gradient bloom renders at full opacity immediately.
- The Manage center dot is visible at scale 1.

`scroll-reveal.js` already short-circuits to add `.is-visible` immediately under reduced-motion preferences; the new mark CSS must follow the same pattern.

## Scope

### In scope

- Replace the emoji icon-badges in `wp-content/themes/newblood/patterns/services-cards.php` (homepage 4-card row) with the new SVG marks
- Replace the emoji icon-badges in `wp-content/themes/newblood/patterns/services-detail.php` (`/services` page) with the new SVG marks
- Add the SVG marks, animation CSS, and any required tweaks to existing CSS files (`assets/css/animations.css`, possibly `patterns.css`)
- Verify reduced-motion behavior renders the final state without animation

### Out of scope (for this engagement)

- AI-generated imagery, photographic textures, or third-party particle libraries
- Per-service color tinting (mono-green is the decision; color tinting can be revisited later if desired)
- Marks for any service beyond Build / Tune / Manage / Empower (no Signal, no internal/admin pages)
- Replacing any other emoji elsewhere in the site (this is a services-page-only effort)
- Lottie or video-based motion (CSS + SVG only)

## Visual and brand alignment

The marks are deliberately universal-first with quiet musical resonance, matching the principle from the musical voice spec (`2026-04-23-musical-voice-design.md`): "a phrase must read as natural English first and carry the musical second meaning quietly." The same rule applies to the visual language — every mark is universally readable first; the music is in the resonance.

- Build (rising bars) — universally reads as foundation / construction; musically a chord ascending
- Tune (sine wave) — universally reads as oscillation / frequency; musically a sound wave
- Manage (rings) — universally reads as shield / care; musically a held tone
- Empower (ring + arrow) — universally reads as outward handoff; musically the close of one section and the opening of another

The choice of mono-green (#4ade80) reinforces the deliberative voice. A four-color palette would have made the section scan faster but at the cost of looking like a generic tech-startup feature grid. Cohesion wins.

## Accessibility

- Each mark must have an accessible name. Where the mark sits next to a heading like `<h2>Build</h2>`, the SVG should be marked `aria-hidden="true"` and rely on the heading for accessible labeling. Decorative marks should not duplicate the service name to screen readers.
- Stroke contrast against the dark `nb-gradient-section` background: the theme green #4ade80 is the same color already used for case-study CTAs and the green-period H2 accent, so contrast is consistent with existing UI. Verify WCAG AA non-text contrast (3:1) against the actual section background during implementation; if it falls short, bump stroke weight from 2.5 to 3px before changing color.
- Reduced-motion behavior described above is mandatory, not a nice-to-have.

## Performance

- Each SVG is inline in the pattern PHP, no extra HTTP request. Marks total ~30 lines of SVG markup each; trivial weight contribution.
- No JS additions — the existing `scroll-reveal.js` IntersectionObserver handles the trigger. The animation itself is pure CSS.
- No new font, image, or asset dependencies.
- `filemtime()` cache-busting in `functions.php` already handles asset invalidation; pattern PHP changes don't need cache-busting because PHP isn't enqueued.

## Implementation outline (for the plan phase)

A separate writing-plans pass will detail the implementation. Headline items expected:

1. **Add mark SVGs to both patterns** — replace `<div class="nb-icon-badge">EMOJI</div>` blocks with the corresponding SVG, wrapped in a class hook like `.nb-service-mark` for styling
2. **Add animation CSS to `animations.css`** — `.nb-service-mark` base styles, `.nb-draw` class with `stroke-dasharray`/`stroke-dashoffset`, keyframes for draw-in, gradient-bloom CSS, reduced-motion overrides
3. **Verify trigger** — confirm the existing `.nb-reveal` parent triggers `.is-visible` on the service block, and ensure the CSS selectors target `.is-visible .nb-service-mark .nb-draw`
4. **Manual QA** — load `/services`, scroll each section into view, confirm draw-in + bloom on first reveal only; toggle macOS Reduce Motion and confirm no animation; check homepage card row scan-reveals correctly
5. **Lighthouse pass** — confirm no regression in Performance, Accessibility, or Best Practices scores

## Open questions for the implementation plan

- Should the marks be slightly smaller on the homepage card row (48px) than on the detail page (64px)? Current emoji badges are different sizes between the two contexts, so a size variant is natural.
- Should the gradient bloom be tied to the existing `nb-gradient-section` background, or a self-contained `::before` pseudo on `.nb-service-mark`? The latter is simpler; the former might layer more elegantly. Implementation plan to pick one.
- Empower mark: revisit at full size and confirm it holds up. If not, explore alternatives (interlocking rings, key-and-loop, simple unlock glyph) before locking in.

## Future considerations (not for this engagement)

- If the per-service color decision is revisited, the path is straightforward: change a single `stroke` color per mark + adjust the gradient bloom hue per mark. The structural design holds.
- A fifth mark for Signal (AI Visibility Audit) when that service surface gets a public card. The mark family established here (rising bars / wave / rings / handoff) sets a vocabulary the Signal mark can extend.
- Lottie/video-based motion remains available later if the team wants more expressive movement — the current treatment establishes the baseline that future motion would have to match in restraint.
