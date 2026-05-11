# Services-page marks-and-motion Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the placeholder emoji icon-badges in `services-cards.php` (homepage) and `services-detail.php` (`/services` page) with four custom geometric SVG marks (Build / Tune / Manage / Empower), add a scroll-triggered draw-in stroke animation plus soft gradient bloom, and respect `prefers-reduced-motion`.

**Architecture:** All four marks are inline SVG (no extra HTTP requests). Animation triggers off the existing `.nb-reveal` / `.is-visible` IntersectionObserver in `assets/js/scroll-reveal.js` — no new JS. Mark CSS lives in `assets/css/patterns.css` next to the (soon-to-be-removed) `.nb-icon-badge` styles. Mono-green theme color (#4ade80) for all marks; per-service color differentiation comes from shape, not hue.

**Tech Stack:** WordPress block-theme PHP patterns, vanilla CSS (with `@keyframes`, `stroke-dasharray`, `stroke-dashoffset`, `prefers-reduced-motion` media query), inline SVG with CSS-targetable class hooks. No build step — `filemtime()` cache-busting in `functions.php` handles asset invalidation.

**Spec:** `docs/superpowers/specs/2026-05-11-services-marks-and-motion-design.md`

---

## File map

| File | Action | Why |
|---|---|---|
| `wp-content/themes/newblood/assets/css/patterns.css` | Modify | Add `.nb-service-mark` styles, draw-in animation keyframes, gradient bloom, reduced-motion overrides. Remove the now-unused `.nb-icon-badge` rule. |
| `wp-content/themes/newblood/assets/css/utilities.css` | Modify | Remove the now-unused duplicate `.nb-icon-badge` rule. |
| `wp-content/themes/newblood/patterns/services-cards.php` | Modify | Replace 4 emoji icon-badges with inline SVG marks (48px). |
| `wp-content/themes/newblood/patterns/services-detail.php` | Modify | Replace 4 emoji icon-badges with inline SVG marks (64px). |

No new files. No JS changes. No new asset dependencies.

---

### Task 1: Add `.nb-service-mark` CSS to patterns.css

**Files:**
- Modify: `wp-content/themes/newblood/assets/css/patterns.css` (append at end of file)

- [ ] **Step 1: Append the service-mark CSS block to the end of `patterns.css`**

Open `wp-content/themes/newblood/assets/css/patterns.css` and append the following block at the end of the file:

```css

/* ---- Service marks ----
 * Geometric SVG anchors for Build / Tune / Manage / Empower in
 * services-cards.php (48px) and services-detail.php (64px).
 * Animation triggers off the parent .nb-reveal becoming .is-visible
 * (existing IntersectionObserver hook in assets/js/scroll-reveal.js).
 * Spec: docs/superpowers/specs/2026-05-11-services-marks-and-motion-design.md
 */

.nb-service-mark {
  position: relative;
  display: inline-block;
  width: 48px;
  height: 48px;
  flex-shrink: 0;
  margin-bottom: 0.5rem;
}

.nb-service-mark--lg {
  width: 64px;
  height: 64px;
}

.nb-service-mark svg {
  position: relative;
  display: block;
  width: 100%;
  height: 100%;
  overflow: visible;
  z-index: 1;
}

.nb-service-mark svg .stroke {
  stroke: #4ade80;
  fill: none;
  stroke-width: 2.5;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.nb-service-mark svg .baseline {
  stroke: #4ade80;
  fill: none;
  stroke-width: 1.5;
  stroke-linecap: round;
  opacity: 0.35;
}

.nb-service-mark svg .inner-ring {
  stroke: #4ade80;
  fill: none;
  stroke-width: 1.5;
  stroke-dasharray: 2 3;
  stroke-linecap: round;
  opacity: 0;
  transition: opacity 600ms ease-out 700ms;
}

.nb-service-mark svg .dot {
  fill: #4ade80;
  stroke: none;
  transform-box: fill-box;
  transform-origin: center;
  transform: scale(0);
}

/* Gradient bloom behind the mark */
.nb-service-mark::before {
  content: '';
  position: absolute;
  inset: -28px;
  background: radial-gradient(circle, rgba(74, 222, 128, 0.18) 0%, transparent 60%);
  border-radius: 50%;
  opacity: 0;
  pointer-events: none;
  transition: opacity 1.4s ease-out;
  z-index: 0;
}

/* Draw-in state — paths start hidden */
.nb-service-mark svg .draw {
  stroke-dasharray: 200;
  stroke-dashoffset: 200;
}

/* Reveal — triggered by .nb-reveal parent becoming .is-visible */
.nb-reveal.is-visible .nb-service-mark::before {
  opacity: 1;
}

.nb-reveal.is-visible .nb-service-mark svg .inner-ring {
  opacity: 1;
}

.nb-reveal.is-visible .nb-service-mark svg .draw {
  animation: nb-mark-draw 1100ms cubic-bezier(0.65, 0, 0.35, 1) forwards;
}

.nb-reveal.is-visible .nb-service-mark svg .draw--2 {
  animation-delay: 200ms;
}

.nb-reveal.is-visible .nb-service-mark svg .draw--3 {
  animation-delay: 400ms;
}

.nb-reveal.is-visible .nb-service-mark svg .dot {
  animation: nb-mark-dot 500ms ease-out 900ms forwards;
}

@keyframes nb-mark-draw {
  to { stroke-dashoffset: 0; }
}

@keyframes nb-mark-dot {
  to { transform: scale(1); }
}

/* Reduced-motion: render final state, skip animation */
@media (prefers-reduced-motion: reduce) {
  .nb-service-mark svg .draw {
    stroke-dasharray: none;
    stroke-dashoffset: 0;
  }
  .nb-service-mark svg .inner-ring {
    opacity: 1;
    transition: none;
  }
  .nb-service-mark svg .dot {
    transform: scale(1);
  }
  .nb-service-mark::before {
    opacity: 1;
    transition: none;
  }
}
```

- [ ] **Step 2: Verify the file appended cleanly**

Run:
```bash
tail -10 /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/assets/css/patterns.css
```

Expected: the last lines should be the closing brace of the `@media (prefers-reduced-motion: reduce)` block. No truncation, no syntax oddities.

- [ ] **Step 3: Confirm filemtime cache-bust will fire**

Run:
```bash
ls -la /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/assets/css/patterns.css
```

Expected: modification time matches the moment of the edit (within seconds). This is what `filemtime()` in `functions.php` reads to cache-bust — no version constant to bump.

---

### Task 2: Remove now-unused `.nb-icon-badge` CSS from both files

**Files:**
- Modify: `wp-content/themes/newblood/assets/css/patterns.css:467-470`
- Modify: `wp-content/themes/newblood/assets/css/utilities.css:83-93`

Background: `.nb-icon-badge` is defined in TWO places (duplicated). After Task 3 and Task 4 swap the patterns to use `.nb-service-mark`, no markup will reference `.nb-icon-badge` anymore. Both rules become dead code.

- [ ] **Step 1: Confirm `.nb-icon-badge` has no other usages**

Run:
```bash
grep -rn 'nb-icon-badge' /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/
```

Expected output BEFORE Task 3/4: the 8 occurrences in `services-cards.php` and `services-detail.php` (which we will replace), plus the 2 CSS definitions in `patterns.css` and `utilities.css` (which this task removes). No other matches anywhere.

If matches appear in unexpected files, STOP. Do not remove the CSS until the unexpected usage is addressed.

- [ ] **Step 2: Remove `.nb-icon-badge` block from `patterns.css`**

Open `wp-content/themes/newblood/assets/css/patterns.css` and delete these 4 lines (currently at line 467-470):

```css
/* Icon badge in service cards */
.nb-icon-badge {
  margin-bottom: 0.5rem;
}
```

- [ ] **Step 3: Remove `.nb-icon-badge` block from `utilities.css`**

Open `wp-content/themes/newblood/assets/css/utilities.css` and delete these 11 lines (currently at line 83-93):

```css
/* Accent icon badge */
.nb-icon-badge {
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, rgba(34, 197, 94, 0.15), rgba(34, 197, 94, 0.05));
  border-radius: 10px;
  font-size: 1.25rem;
}
```

- [ ] **Step 4: Verify both definitions are gone**

Run:
```bash
grep -n 'nb-icon-badge' /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/assets/css/patterns.css /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/assets/css/utilities.css
```

Expected: no output (zero matches across both files).

---

### Task 3: Replace emoji badges in `services-cards.php` with SVG marks

**Files:**
- Modify: `wp-content/themes/newblood/patterns/services-cards.php:25` (Build)
- Modify: `wp-content/themes/newblood/patterns/services-cards.php:36` (Tune)
- Modify: `wp-content/themes/newblood/patterns/services-cards.php:47` (Manage)
- Modify: `wp-content/themes/newblood/patterns/services-cards.php:58` (Empower)

All four replacements use the 48px default size (no `--lg` modifier).

- [ ] **Step 1: Replace Build emoji**

In `wp-content/themes/newblood/patterns/services-cards.php`, find this line (currently line 25):

```php
      <div class="nb-icon-badge">⚡</div>
```

Replace with:

```php
      <div class="nb-service-mark">
        <svg viewBox="0 0 80 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
          <line class="stroke draw" x1="14" y1="62" x2="14" y2="50"/>
          <line class="stroke draw draw--2" x1="28" y1="62" x2="28" y2="40"/>
          <line class="stroke draw draw--2" x1="42" y1="62" x2="42" y2="28"/>
          <line class="stroke draw draw--3" x1="56" y1="62" x2="56" y2="16"/>
          <line class="baseline" x1="6" y1="68" x2="74" y2="68"/>
        </svg>
      </div>
```

- [ ] **Step 2: Replace Tune emoji**

In the same file, find this line (currently line 36):

```php
      <div class="nb-icon-badge">🎛️</div>
```

Replace with:

```php
      <div class="nb-service-mark">
        <svg viewBox="0 0 80 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
          <path class="stroke draw" d="M8 40 Q 20 18, 32 40 T 56 40 T 72 40"/>
          <line class="baseline" x1="6" y1="68" x2="74" y2="68"/>
        </svg>
      </div>
```

- [ ] **Step 3: Replace Manage emoji**

Find this line (currently line 47):

```php
      <div class="nb-icon-badge">🛡️</div>
```

Replace with:

```php
      <div class="nb-service-mark">
        <svg viewBox="0 0 80 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
          <circle class="stroke draw" cx="40" cy="40" r="22"/>
          <circle class="inner-ring" cx="40" cy="40" r="14"/>
          <circle class="dot" cx="40" cy="40" r="3"/>
        </svg>
      </div>
```

The `.inner-ring` element intentionally has no `.draw` class — its dashed `2 3` pattern conflicts with the draw-in animation's `dasharray: 200`. Instead, the inner ring uses an opacity fade-in with a 700ms delay (defined in Task 1 CSS) so it appears just behind the outer circle.

- [ ] **Step 4: Replace Empower emoji**

Find this line (currently line 58):

```php
      <div class="nb-icon-badge">🤝</div>
```

Replace with:

```php
      <div class="nb-service-mark">
        <svg viewBox="0 0 80 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
          <circle class="stroke draw" cx="28" cy="40" r="14"/>
          <line class="stroke draw draw--2" x1="42" y1="40" x2="64" y2="40"/>
          <polyline class="stroke draw draw--3" points="56,32 64,40 56,48" fill="none"/>
        </svg>
      </div>
```

- [ ] **Step 5: Lint the file**

Run:
```bash
php -l /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/patterns/services-cards.php
```

Expected: `No syntax errors detected in ...`

- [ ] **Step 6: Verify rendered output on the homepage**

Run:
```bash
curl -s http://newblood.test/ -o /tmp/home-after.html && \
python3 -c "
h = open('/tmp/home-after.html').read()
print('emoji-badge refs remaining:', h.count('nb-icon-badge'))
print('service-mark refs:', h.count('nb-service-mark'))
print('has Build viewBox path:', 'M8 40 Q 20 18' not in h, '(should be False — Build doesn\'t use that path; Tune does)')
print('has Tune sine path:', 'M8 40 Q 20 18' in h)
print('has Manage circles:', 'cx=\"40\" cy=\"40\" r=\"22\"' in h)
print('emoji chars in services region:', sum(h.count(e) for e in ['⚡','🎛','🛡','🤝']))
"
```

Expected:
- `emoji-badge refs remaining: 0`
- `service-mark refs: 4` (one per service card)
- `has Tune sine path: True`
- `has Manage circles: True`
- `emoji chars in services region: 0`

---

### Task 4: Replace emoji badges in `services-detail.php` with SVG marks (large variant)

**Files:**
- Modify: `wp-content/themes/newblood/patterns/services-detail.php:18` (Build)
- Modify: `wp-content/themes/newblood/patterns/services-detail.php:84` (Tune)
- Modify: `wp-content/themes/newblood/patterns/services-detail.php:153` (Manage)
- Modify: `wp-content/themes/newblood/patterns/services-detail.php:219` (Empower)

All four replacements use the `--lg` size variant (64px) and the same SVG shapes as Task 3.

- [ ] **Step 1: Replace Build emoji**

In `wp-content/themes/newblood/patterns/services-detail.php`, find this line (currently line 18):

```php
        <div class="nb-icon-badge" style="width:56px;height:56px;font-size:1.75rem">⚡</div>
```

Replace with:

```php
        <div class="nb-service-mark nb-service-mark--lg">
          <svg viewBox="0 0 80 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
            <line class="stroke draw" x1="14" y1="62" x2="14" y2="50"/>
            <line class="stroke draw draw--2" x1="28" y1="62" x2="28" y2="40"/>
            <line class="stroke draw draw--2" x1="42" y1="62" x2="42" y2="28"/>
            <line class="stroke draw draw--3" x1="56" y1="62" x2="56" y2="16"/>
            <line class="baseline" x1="6" y1="68" x2="74" y2="68"/>
          </svg>
        </div>
```

- [ ] **Step 2: Replace Tune emoji**

Find this line (currently line 84):

```php
        <div class="nb-icon-badge" style="width:56px;height:56px;font-size:1.75rem">🎛️</div>
```

Replace with:

```php
        <div class="nb-service-mark nb-service-mark--lg">
          <svg viewBox="0 0 80 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
            <path class="stroke draw" d="M8 40 Q 20 18, 32 40 T 56 40 T 72 40"/>
            <line class="baseline" x1="6" y1="68" x2="74" y2="68"/>
          </svg>
        </div>
```

- [ ] **Step 3: Replace Manage emoji**

Find this line (currently line 153):

```php
        <div class="nb-icon-badge" style="width:56px;height:56px;font-size:1.75rem">🛡️</div>
```

Replace with:

```php
        <div class="nb-service-mark nb-service-mark--lg">
          <svg viewBox="0 0 80 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
            <circle class="stroke draw" cx="40" cy="40" r="22"/>
            <circle class="inner-ring" cx="40" cy="40" r="14"/>
            <circle class="dot" cx="40" cy="40" r="3"/>
          </svg>
        </div>
```

- [ ] **Step 4: Replace Empower emoji**

Find this line (currently line 219):

```php
        <div class="nb-icon-badge" style="width:56px;height:56px;font-size:1.75rem">🤝</div>
```

Replace with:

```php
        <div class="nb-service-mark nb-service-mark--lg">
          <svg viewBox="0 0 80 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
            <circle class="stroke draw" cx="28" cy="40" r="14"/>
            <line class="stroke draw draw--2" x1="42" y1="40" x2="64" y2="40"/>
            <polyline class="stroke draw draw--3" points="56,32 64,40 56,48" fill="none"/>
          </svg>
        </div>
```

- [ ] **Step 5: Lint the file**

Run:
```bash
php -l /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/patterns/services-detail.php
```

Expected: `No syntax errors detected in ...`

- [ ] **Step 6: Verify rendered output on /services**

Run:
```bash
curl -s http://newblood.test/services/ -o /tmp/services-after.html && \
python3 -c "
s = open('/tmp/services-after.html').read()
print('emoji-badge refs remaining:', s.count('nb-icon-badge'))
print('service-mark refs (large):', s.count('nb-service-mark--lg'))
print('service-mark refs (total):', s.count('nb-service-mark'))
print('emoji chars on page:', sum(s.count(e) for e in ['⚡','🎛','🛡','🤝']))
print('has draw-in SVG class on stroke:', 'class=\"stroke draw\"' in s)
print('has reduced-motion media in CSS request? (sanity)', 'prefers-reduced-motion' in s)  # CSS is external, so False expected
"
```

Expected:
- `emoji-badge refs remaining: 0`
- `service-mark refs (large): 4`
- `service-mark refs (total): 8` (the --lg class is the second class on each .nb-service-mark, so it counts as 4 .nb-service-mark plus 4 .nb-service-mark--lg = 8)
- `emoji chars on page: 0`
- `has draw-in SVG class on stroke: True`

---

### Task 5: Visual QA — confirm marks render and animate correctly

**Files:** None modified. This task is verification only.

- [ ] **Step 1: Open the services page in a browser**

Open `http://newblood.test/services/` in Chrome/Safari. Hard-reload (Cmd+Shift+R) is NOT required — `filemtime()` cache-busting handles the CSS invalidation automatically. If the page appears unchanged, check DevTools Network tab to confirm the new `patterns.css` URL has a fresh `?ver=` query string.

- [ ] **Step 2: Verify each service section animates correctly**

Scroll slowly so each service block enters the viewport. For each of the four sections, you should see:

| Service | Animation observed |
|---|---|
| Build | Four green vertical bars draw upward in sequence, on a faint baseline. Green bloom behind. |
| Tune | A green sine wave draws left-to-right on a faint baseline. Green bloom behind. |
| Manage | Outer circle draws, then dashed inner ring draws, then center dot pops in. Green bloom behind. |
| Empower | Left circle draws, horizontal line extends rightward, arrow head appears at end. Green bloom behind. |

Each animation should fire exactly once when the section enters the viewport. Scrolling back up and down should NOT re-trigger the animation.

- [ ] **Step 3: Verify homepage card row**

Open `http://newblood.test/`. Scroll to the "What we do" section (services-cards block). Expected: all four marks render at 48px (smaller than the detail page), each animating in as the row scroll-reveals.

- [ ] **Step 4: Verify reduced-motion behavior**

Open macOS System Settings → Accessibility → Display → toggle "Reduce motion" ON.

Hard-reload `http://newblood.test/services/`. Expected: all four marks render in their FINAL state (fully drawn, dot visible on Manage, gradient bloom at full opacity) with NO animation playing on scroll.

Toggle "Reduce motion" OFF afterward to restore default.

- [ ] **Step 5: Spot-check accessibility**

Inspect one of the marks in DevTools. Confirm the SVG has `aria-hidden="true"`. Confirm the parent `<div class="nb-service-mark...">` has no accessible name attribute (the adjacent `<h2>` or `<h3>` provides the label).

Use the browser's accessibility tree viewer (Chrome DevTools → Elements → Accessibility tab) to confirm the service heading (e.g., "Build") is announced WITHOUT a preceding "graphic" or duplicated label.

- [ ] **Step 6: Quick contrast eyeball**

Compare the green stroke against the dark gradient background. The marks should be clearly visible without straining. If any mark feels low-contrast, note it — the spec allows bumping stroke-width from 2.5 to 3 as a fallback before changing color.

---

### Task 6: Commit

**Files:** None modified beyond what's already staged from prior tasks.

- [ ] **Step 1: Stage all changes**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood
git add wp-content/themes/newblood/assets/css/patterns.css \
        wp-content/themes/newblood/assets/css/utilities.css \
        wp-content/themes/newblood/patterns/services-cards.php \
        wp-content/themes/newblood/patterns/services-detail.php
git status --short
```

Expected: 4 files staged, no untracked files left over from this work.

- [ ] **Step 2: Create the commit**

Run:
```bash
cd /Users/jeremyoms/Herd/newblood
cat > /tmp/commit-msg.txt << 'COMMITEOF'
feat(services): replace emoji icon-badges with custom SVG marks + scroll-in motion

Adds four custom geometric SVG marks (rising bars / sine wave / concentric
rings / ring-and-arrow) for Build / Tune / Manage / Empower in both the
homepage services-cards row (48px) and the /services detail page (64px).
Each mark animates its strokes drawing in over 1.1s when its parent
.nb-reveal section enters the viewport, with a soft green gradient bloom
behind. Honors prefers-reduced-motion by rendering the final state with
no animation. Uses the existing scroll-reveal IntersectionObserver — no
new JS. Removes the now-unused .nb-icon-badge CSS rules (previously
duplicated in patterns.css and utilities.css).

Spec: docs/superpowers/specs/2026-05-11-services-marks-and-motion-design.md

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
COMMITEOF
git commit -F /tmp/commit-msg.txt
git log --oneline -3
```

Expected: commit succeeds, hash returned, top of `git log --oneline` shows the new commit followed by `6284b721 docs(spec): add services-page marks-and-motion design`.

---

## Self-review checklist (run before handing back to user)

- [ ] `.nb-icon-badge` references — none remaining in any theme file. Verify:
  ```bash
  grep -rn 'nb-icon-badge' /Users/jeremyoms/Herd/newblood/wp-content/themes/newblood/
  ```
  Expected: zero matches.

- [ ] Emoji service-glyphs — none remaining on rendered services or homepage. Verify with the curl scripts in Tasks 3.6 and 4.6.

- [ ] Lighthouse spot-check (optional but recommended for confidence):
  ```bash
  # Run from any machine with the Lighthouse CLI installed, against the local site
  npx lighthouse http://newblood.test/services/ --only-categories=performance,accessibility --quiet --chrome-flags="--headless"
  ```
  Expected: no regression vs. pre-change baseline (no new accessibility violations from the SVGs; performance score unchanged within ±2 points of run-to-run variance).

- [ ] Reduced-motion media query honored. Verify either visually (Task 5 Step 4) or by inspecting computed styles in DevTools with the user-agent forced to `prefers-reduced-motion: reduce`.

- [ ] The Empower mark holds up at 64px. The spec flagged this as the least-settled mark; if it reads weak at full size on the detail page, log a follow-up note rather than blocking the commit — the iteration is cheap and can happen as its own pass.
