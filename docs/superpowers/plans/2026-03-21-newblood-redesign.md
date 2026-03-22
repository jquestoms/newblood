# New Blood Website Redesign — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild newblood.com as a custom WordPress block theme with a dark gradient visual identity, scroll animations, and asymmetric layouts — replacing the outdated Grand Portfolio theme.

**Architecture:** Custom WordPress block theme built from scratch using `theme.json` for design tokens, block patterns for reusable page sections, CSS animations for motion, and minimal vanilla JS for Intersection Observer scroll reveals. The existing WordPress 6.9.4 install and plugin infrastructure (WooCommerce, Redis, etc.) remain in place.

**Tech Stack:** WordPress 6.9.4, PHP 8.x, CSS (custom properties, @keyframes, scroll-driven animations), vanilla JavaScript, WordPress Block Editor / FSE

**Spec:** `docs/superpowers/specs/2026-03-21-newblood-redesign-design.md`

---

## File Structure

```
wp-content/themes/newblood/
├── style.css                          # Theme header (required by WP)
├── theme.json                         # Design tokens: colors, typography, spacing, layout
├── functions.php                      # Enqueue scripts/styles, theme support, register patterns
├── templates/
│   ├── index.html                     # Default template (required)
│   ├── front-page.html                # Homepage template
│   ├── page.html                      # Generic page template
│   ├── single.html                    # Single post template
│   └── 404.html                       # 404 page
├── parts/
│   ├── header.html                    # Site header with nav
│   └── footer.html                    # Site footer
├── patterns/
│   ├── hero.php                       # Hero section with gradient bg + dot grid
│   ├── social-proof.php               # Client logos strip
│   ├── services-cards.php             # Build/Manage/Empower cards
│   ├── portfolio-grid.php             # Asymmetric portfolio grid
│   ├── testimonial.php                # Client testimonial block
│   ├── cta.php                        # Call-to-action section
│   ├── page-header.php                # Interior page header
│   ├── pricing-table.php              # Pricing tiers
│   ├── how-it-works.php               # 3-step process section
│   ├── contact-form.php               # Contact form section
│   ├── about-story.php                # Company story section
│   └── faq.php                        # FAQ accordion section
├── assets/
│   ├── css/
│   │   ├── animations.css             # Scroll animations, keyframes, hover effects
│   │   ├── patterns.css               # Pattern-specific layout styles
│   │   └── utilities.css              # Frosted glass, dot-grid, gradients
│   └── js/
│       └── scroll-reveal.js           # Intersection Observer for scroll animations
└── screenshot.png                     # Theme screenshot for WP admin
```

---

## Task 1: Create Base Theme Scaffold

**Files:**
- Create: `wp-content/themes/newblood/style.css`
- Create: `wp-content/themes/newblood/theme.json`
- Create: `wp-content/themes/newblood/functions.php`
- Create: `wp-content/themes/newblood/templates/index.html`

- [ ] **Step 1: Create `style.css` with theme header**

```css
/*
Theme Name: New Blood
Theme URI: https://newblood.com
Description: Custom block theme for New Blood - modern web agency
Author: New Blood, Inc.
Version: 1.0.0
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.0
License: GNU General Public License v2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Text Domain: newblood
*/
```

- [ ] **Step 2: Create `theme.json` with complete design tokens**

This is the core design system. It defines:
- Color palette (dark backgrounds, green accents, text hierarchy)
- Typography (font families, sizes, weights)
- Spacing scale
- Layout settings (content width, wide width)
- Block-level style defaults

```json
{
  "$schema": "https://schemas.wp.org/wp/6.5/theme.json",
  "version": 3,
  "settings": {
    "appearanceTools": true,
    "color": {
      "palette": [
        { "slug": "base", "color": "#0f1117", "name": "Base" },
        { "slug": "base-mid", "color": "#111827", "name": "Base Mid" },
        { "slug": "base-green", "color": "#0f2218", "name": "Base Green" },
        { "slug": "surface", "color": "#181c25", "name": "Surface" },
        { "slug": "accent", "color": "#22c55e", "name": "Accent" },
        { "slug": "accent-light", "color": "#4ade80", "name": "Accent Light" },
        { "slug": "text-primary", "color": "#ffffff", "name": "Text Primary" },
        { "slug": "text-body", "color": "#cbd5e1", "name": "Text Body" },
        { "slug": "text-secondary", "color": "#94a3b8", "name": "Text Secondary" },
        { "slug": "text-muted", "color": "#64748b", "name": "Text Muted" },
        { "slug": "border", "color": "rgba(255,255,255,0.08)", "name": "Border" },
        { "slug": "border-subtle", "color": "rgba(255,255,255,0.05)", "name": "Border Subtle" }
      ],
      "gradients": [
        {
          "slug": "primary-bg",
          "gradient": "linear-gradient(160deg, #0f1117 0%, #111827 50%, #0f2218 100%)",
          "name": "Primary Background"
        },
        {
          "slug": "accent-btn",
          "gradient": "linear-gradient(135deg, #22c55e, #16a34a)",
          "name": "Accent Button"
        }
      ]
    },
    "typography": {
      "fontFamilies": [
        {
          "fontFamily": "Inter, system-ui, -apple-system, sans-serif",
          "slug": "primary",
          "name": "Primary"
        }
      ],
      "fontSizes": [
        { "slug": "xs", "size": "0.75rem", "name": "Extra Small" },
        { "slug": "sm", "size": "0.875rem", "name": "Small" },
        { "slug": "md", "size": "1rem", "name": "Medium" },
        { "slug": "lg", "size": "1.125rem", "name": "Large" },
        { "slug": "xl", "size": "1.5rem", "name": "Extra Large" },
        { "slug": "2xl", "size": "2rem", "name": "2X Large" },
        { "slug": "3xl", "size": "2.5rem", "name": "3X Large" },
        { "slug": "4xl", "size": "3.5rem", "name": "4X Large" }
      ]
    },
    "spacing": {
      "units": ["px", "rem", "%", "vw"],
      "spacingSizes": [
        { "slug": "10", "size": "0.25rem", "name": "Tiny" },
        { "slug": "20", "size": "0.5rem", "name": "X-Small" },
        { "slug": "30", "size": "1rem", "name": "Small" },
        { "slug": "40", "size": "1.5rem", "name": "Medium" },
        { "slug": "50", "size": "2rem", "name": "Large" },
        { "slug": "60", "size": "3rem", "name": "X-Large" },
        { "slug": "70", "size": "5rem", "name": "XX-Large" },
        { "slug": "80", "size": "8rem", "name": "Huge" }
      ]
    },
    "layout": {
      "contentSize": "800px",
      "wideSize": "1200px"
    }
  },
  "styles": {
    "color": {
      "background": "#0f1117",
      "text": "#cbd5e1"
    },
    "typography": {
      "fontFamily": "var(--wp--preset--font-family--primary)",
      "fontSize": "var(--wp--preset--font-size--md)",
      "lineHeight": "1.6"
    },
    "elements": {
      "heading": {
        "color": { "text": "#ffffff" },
        "typography": {
          "fontWeight": "800",
          "letterSpacing": "-0.02em"
        }
      },
      "link": {
        "color": { "text": "#22c55e" },
        ":hover": { "color": { "text": "#4ade80" } }
      },
      "button": {
        "color": {
          "background": "#22c55e",
          "text": "#ffffff"
        },
        "border": { "radius": "8px" },
        "typography": { "fontWeight": "700" }
      }
    },
    "blocks": {}
  }
}
```

- [ ] **Step 3: Create `functions.php`**

```php
<?php
/**
 * New Blood Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NEWBLOOD_VERSION', '1.0.0' );

/**
 * Enqueue theme styles and scripts
 */
function newblood_enqueue_assets() {
    wp_enqueue_style(
        'newblood-animations',
        get_template_directory_uri() . '/assets/css/animations.css',
        array(),
        NEWBLOOD_VERSION
    );
    wp_enqueue_style(
        'newblood-patterns',
        get_template_directory_uri() . '/assets/css/patterns.css',
        array(),
        NEWBLOOD_VERSION
    );
    wp_enqueue_style(
        'newblood-utilities',
        get_template_directory_uri() . '/assets/css/utilities.css',
        array(),
        NEWBLOOD_VERSION
    );
    wp_enqueue_script(
        'newblood-scroll-reveal',
        get_template_directory_uri() . '/assets/js/scroll-reveal.js',
        array(),
        NEWBLOOD_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'newblood_enqueue_assets' );

/**
 * Register block patterns
 */
function newblood_register_pattern_categories() {
    register_block_pattern_category( 'newblood', array(
        'label' => __( 'New Blood', 'newblood' ),
    ) );
    register_block_pattern_category( 'newblood-pages', array(
        'label' => __( 'New Blood Pages', 'newblood' ),
    ) );
}
add_action( 'init', 'newblood_register_pattern_categories' );

/**
 * Theme setup
 */
function newblood_setup() {
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'newblood_setup' );
```

- [ ] **Step 4: Create minimal `templates/index.html`**

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">
  <!-- wp:post-content /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

- [ ] **Step 5: Create empty asset files**

Create placeholder files so the theme doesn't error on enqueue:
- `assets/css/animations.css` — empty
- `assets/css/patterns.css` — empty
- `assets/css/utilities.css` — empty
- `assets/js/scroll-reveal.js` — empty

- [ ] **Step 6: Verify theme appears in WP admin**

Open WordPress admin at `http://newblood.test/wp-admin/themes.php` (or your local Herd URL) and confirm "New Blood" theme appears in the theme list. Do NOT activate yet.

- [ ] **Step 7: Commit**

```bash
git add wp-content/themes/newblood/
git commit -m "feat: create base New Blood block theme scaffold"
```

---

## Task 2: Build Header and Footer Parts

**Files:**
- Create: `wp-content/themes/newblood/parts/header.html`
- Create: `wp-content/themes/newblood/parts/footer.html`
- Modify: `wp-content/themes/newblood/assets/css/patterns.css`

- [ ] **Step 1: Create `parts/header.html`**

The header uses the dark gradient background with the newblood logo (text-based) and nav menu. The "Get Started" button is a styled nav item linking to the contact page.

```html
<!-- wp:group {"tagName":"header","className":"nb-header","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap"}} -->
<header class="wp-block-group nb-header">
  <!-- wp:site-title {"style":{"typography":{"fontSize":"1.25rem","fontWeight":"800","letterSpacing":"-0.5px"},"elements":{"link":{"color":{"text":"#ffffff"},":hover":{"color":{"text":"#ffffff"}}}}},"className":"nb-logo"} /-->
  <!-- wp:navigation {"overlayMenu":"mobile","style":{"typography":{"fontSize":"0.875rem","fontWeight":"500"},"spacing":{"blockGap":"var:preset|spacing|40"}},"textColor":"text-secondary","layout":{"type":"flex","justifyContent":"right"}} /-->
</header>
<!-- /wp:group -->
```

- [ ] **Step 2: Create `parts/footer.html`**

```html
<!-- wp:group {"className":"nb-footer","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#0a0c10"},"border":{"top":{"color":"rgba(255,255,255,0.05)","width":"1px"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group nb-footer">
  <!-- wp:columns -->
  <div class="wp-block-columns">
    <!-- wp:column {"width":"50%"} -->
    <div class="wp-block-column" style="flex-basis:50%">
      <!-- wp:site-title {"style":{"typography":{"fontSize":"1.125rem","fontWeight":"800"},"elements":{"link":{"color":{"text":"#ffffff"},":hover":{"color":{"text":"#ffffff"}}}}}} /-->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.875rem"}},"textColor":"text-muted"} -->
      <p class="has-text-muted-color">Modern websites, expertly managed.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.75rem"},"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"textColor":"text-muted"} -->
      <p class="has-text-muted-color">&copy; 2026 New Blood, Inc.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"width":"25%"} -->
    <div class="wp-block-column" style="flex-basis:25%">
      <!-- wp:heading {"level":6,"style":{"typography":{"fontSize":"0.6875rem","textTransform":"uppercase","letterSpacing":"2px"}},"textColor":"text-muted"} -->
      <h6 class="has-text-muted-color">Company</h6>
      <!-- /wp:heading -->
      <!-- wp:navigation {"overlayMenu":"never","style":{"typography":{"fontSize":"0.8125rem","lineHeight":"2.2"},"spacing":{"blockGap":"0"}},"textColor":"text-muted","layout":{"type":"flex","orientation":"vertical"}} /-->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"width":"25%"} -->
    <div class="wp-block-column" style="flex-basis:25%">
      <!-- wp:heading {"level":6,"style":{"typography":{"fontSize":"0.6875rem","textTransform":"uppercase","letterSpacing":"2px"}},"textColor":"text-muted"} -->
      <h6 class="has-text-muted-color">Connect</h6>
      <!-- /wp:heading -->
      <!-- wp:navigation {"overlayMenu":"never","style":{"typography":{"fontSize":"0.8125rem","lineHeight":"2.2"},"spacing":{"blockGap":"0"}},"textColor":"text-muted","layout":{"type":"flex","orientation":"vertical"}} /-->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 3: Add header/footer CSS to `assets/css/patterns.css`**

```css
/* Header */
.nb-header {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(15, 17, 23, 0.85);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.nb-logo a {
  color: #ffffff !important;
  text-decoration: none !important;
}

/* Footer */
.nb-footer a:hover {
  color: var(--wp--preset--color--accent-light) !important;
}
```

- [ ] **Step 4: Verify header and footer render**

Activate the New Blood theme in WP admin. Visit the site and confirm the header (with nav) and footer render with the dark background. The nav menus will need to be assigned in Appearance > Menus or the Site Editor.

- [ ] **Step 5: Commit**

```bash
git add wp-content/themes/newblood/
git commit -m "feat: add header and footer template parts"
```

---

## Task 3: Build Utility CSS and Animation System

**Files:**
- Modify: `wp-content/themes/newblood/assets/css/utilities.css`
- Modify: `wp-content/themes/newblood/assets/css/animations.css`
- Create: `wp-content/themes/newblood/assets/js/scroll-reveal.js`

- [ ] **Step 1: Write `assets/css/utilities.css`**

Reusable visual utilities used across all patterns.

```css
/* Frosted glass card */
.nb-glass {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 12px;
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
}

.nb-glass:hover {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.12);
  transform: translateY(-2px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.nb-glass {
  transition: all 0.3s ease;
}

/* Dot grid texture */
.nb-dot-grid {
  position: relative;
}

.nb-dot-grid::before {
  content: "";
  position: absolute;
  inset: 0;
  background-image: radial-gradient(
    circle at 1px 1px,
    rgba(74, 222, 128, 0.04) 1px,
    transparent 0
  );
  background-size: 32px 32px;
  pointer-events: none;
}

/* Gradient backgrounds */
.nb-gradient-primary {
  background: linear-gradient(160deg, #0f1117 0%, #111827 50%, #0f2218 100%);
}

.nb-gradient-section {
  background: linear-gradient(180deg, #111827 0%, #0f1a15 100%);
}

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

/* Green label (uppercase small text) */
.nb-label {
  font-size: 0.625rem;
  text-transform: uppercase;
  letter-spacing: 3px;
  font-weight: 600;
  color: var(--wp--preset--color--accent-light);
}

/* Primary CTA button */
.nb-btn-primary {
  display: inline-block;
  background: linear-gradient(135deg, #22c55e, #16a34a);
  color: #ffffff;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 700;
  text-decoration: none;
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.nb-btn-primary:hover {
  opacity: 0.9;
  transform: translateY(-1px);
  color: #ffffff;
}

/* Secondary (ghost) CTA button */
.nb-btn-secondary {
  display: inline-block;
  border: 1.5px solid #334155;
  color: #cbd5e1;
  padding: 0.625rem 1.25rem;
  border-radius: 8px;
  font-size: 0.875rem;
  text-decoration: none;
  transition: border-color 0.2s ease, color 0.2s ease;
}

.nb-btn-secondary:hover {
  border-color: #4ade80;
  color: #ffffff;
}

/* Section divider (subtle top border) */
.nb-divider-top {
  border-top: 1px solid rgba(255, 255, 255, 0.05);
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
  .nb-glass,
  .nb-btn-primary,
  .nb-btn-secondary {
    transition: none;
  }

  .nb-glass:hover {
    transform: none;
  }
}
```

- [ ] **Step 2: Write `assets/css/animations.css`**

```css
/* Scroll-reveal base: hidden by default, revealed by JS */
.nb-reveal {
  opacity: 0;
  transform: translateY(24px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}

.nb-reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

/* Staggered children */
.nb-stagger > .nb-reveal:nth-child(1) { transition-delay: 0s; }
.nb-stagger > .nb-reveal:nth-child(2) { transition-delay: 0.1s; }
.nb-stagger > .nb-reveal:nth-child(3) { transition-delay: 0.2s; }
.nb-stagger > .nb-reveal:nth-child(4) { transition-delay: 0.3s; }

/* Scale reveal (for portfolio cards) */
.nb-reveal-scale {
  opacity: 0;
  transform: scale(0.95);
  transition: opacity 0.6s ease, transform 0.6s ease;
}

.nb-reveal-scale.is-visible {
  opacity: 1;
  transform: scale(1);
}

/* Hero entrance animation */
@keyframes fadeUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.nb-hero-label {
  animation: fadeUp 0.6s ease forwards;
  animation-delay: 0.1s;
  opacity: 0;
}

.nb-hero-headline {
  animation: fadeUp 0.6s ease forwards;
  animation-delay: 0.25s;
  opacity: 0;
}

.nb-hero-body {
  animation: fadeUp 0.6s ease forwards;
  animation-delay: 0.4s;
  opacity: 0;
}

.nb-hero-cta {
  animation: fadeUp 0.6s ease forwards;
  animation-delay: 0.55s;
  opacity: 0;
}

/* Nav link underline hover */
.nb-header .wp-block-navigation-item a::after {
  content: "";
  display: block;
  height: 2px;
  background: var(--wp--preset--color--accent);
  transform: scaleX(0);
  transition: transform 0.25s ease;
  transform-origin: left;
}

.nb-header .wp-block-navigation-item a:hover::after {
  transform: scaleX(1);
}

/* Reduced motion: disable all animations */
@media (prefers-reduced-motion: reduce) {
  .nb-reveal,
  .nb-reveal-scale {
    opacity: 1;
    transform: none;
    transition: none;
  }

  .nb-hero-label,
  .nb-hero-headline,
  .nb-hero-body,
  .nb-hero-cta {
    animation: none;
    opacity: 1;
  }
}
```

- [ ] **Step 3: Write `assets/js/scroll-reveal.js`**

```javascript
/**
 * Intersection Observer for scroll-reveal animations.
 * Adds 'is-visible' class when elements enter the viewport.
 */
(function () {
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('.nb-reveal, .nb-reveal-scale').forEach(function (el) {
      el.classList.add('is-visible');
    });
    return;
  }

  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
  );

  document.querySelectorAll('.nb-reveal, .nb-reveal-scale').forEach(function (el) {
    observer.observe(el);
  });
})();
```

- [ ] **Step 4: Verify animations work**

Visit the local site. Add a test page with a few `<div class="nb-reveal">Test</div>` blocks and scroll. Verify elements fade in as they enter the viewport.

- [ ] **Step 5: Commit**

```bash
git add wp-content/themes/newblood/assets/
git commit -m "feat: add utility CSS, animation system, and scroll-reveal JS"
```

---

## Task 4: Build Homepage Patterns

**Files:**
- Create: `wp-content/themes/newblood/patterns/hero.php`
- Create: `wp-content/themes/newblood/patterns/social-proof.php`
- Create: `wp-content/themes/newblood/patterns/services-cards.php`
- Create: `wp-content/themes/newblood/patterns/portfolio-grid.php`
- Create: `wp-content/themes/newblood/patterns/testimonial.php`
- Create: `wp-content/themes/newblood/patterns/cta.php`
- Modify: `wp-content/themes/newblood/assets/css/patterns.css`

Each pattern file uses the WordPress `register_block_pattern` registration via the file header comment format.

- [ ] **Step 1: Create `patterns/hero.php`**

The hero section: gradient background, dot grid texture, animated headline, two CTA buttons.

```php
<?php
/**
 * Title: Hero
 * Slug: newblood/hero
 * Categories: newblood
 * Description: Homepage hero section with headline, subtitle, and CTAs
 */
?>
<!-- wp:group {"className":"nb-gradient-primary nb-dot-grid","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group nb-gradient-primary nb-dot-grid">
  <!-- wp:paragraph {"className":"nb-label nb-hero-label"} -->
  <p class="nb-label nb-hero-label">Modern websites, delivered fast</p>
  <!-- /wp:paragraph -->
  <!-- wp:heading {"level":1,"className":"nb-hero-headline","style":{"typography":{"fontSize":"clamp(2.5rem, 5vw, 3.5rem)","lineHeight":"1.1","letterSpacing":"-0.03em"}}} -->
  <h1 class="nb-hero-headline">Your business deserves a website that works<span style="color:#4ade80">.</span></h1>
  <!-- /wp:heading -->
  <!-- wp:paragraph {"className":"nb-hero-body","style":{"typography":{"fontSize":"1.125rem"}},"textColor":"text-secondary"} -->
  <p class="nb-hero-body has-text-secondary-color">We combine 15+ years of expertise with modern tools to build beautiful, blazing-fast websites — and hand you the keys to manage it yourself.</p>
  <!-- /wp:paragraph -->
  <!-- wp:group {"className":"nb-hero-cta","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"nowrap"}} -->
  <div class="wp-block-group nb-hero-cta">
    <!-- wp:paragraph -->
    <p><a class="nb-btn-primary" href="/work">See Our Work</a></p>
    <!-- /wp:paragraph -->
    <!-- wp:paragraph -->
    <p><a class="nb-btn-secondary" href="/pricing">View Pricing</a></p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 2: Create `patterns/social-proof.php`**

```php
<?php
/**
 * Title: Social Proof
 * Slug: newblood/social-proof
 * Categories: newblood
 * Description: Trusted-by client logos strip
 */
?>
<!-- wp:group {"className":"nb-divider-top","style":{"color":{"background":"#0d0f14"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"flex","justifyContent":"center","flexWrap":"nowrap"}} -->
<div class="wp-block-group nb-divider-top">
  <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.625rem","textTransform":"uppercase","letterSpacing":"2px","fontWeight":"600"}},"textColor":"text-muted"} -->
  <p class="has-text-muted-color">Trusted by</p>
  <!-- /wp:paragraph -->
  <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.875rem","fontWeight":"700","letterSpacing":"1px"}},"textColor":"text-muted"} -->
  <p class="has-text-muted-color">&nbsp;&nbsp;&nbsp;ACME CO&nbsp;&nbsp;&nbsp;&nbsp;SUMMIT&nbsp;&nbsp;&nbsp;&nbsp;VERDE&nbsp;&nbsp;&nbsp;&nbsp;ATLAS&nbsp;&nbsp;&nbsp;&nbsp;NORTH</p>
  <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 3: Create `patterns/services-cards.php`**

Three frosted-glass cards for Build / Manage / Empower with staggered scroll reveal.

```php
<?php
/**
 * Title: Services Cards
 * Slug: newblood/services-cards
 * Categories: newblood
 * Description: Build, Manage, Empower service cards
 */
?>
<!-- wp:group {"className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group nb-gradient-section">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">What we do</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>Everything your website needs</h2>
    <!-- /wp:heading -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <div class="nb-icon-badge">⚡</div>
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"},"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
      <h3>Build</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Custom-designed, fast-loading websites built with modern tools. Delivered in days, not months.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <div class="nb-icon-badge">🛡️</div>
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"},"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
      <h3>Manage</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Hosting, security, updates, backups — we keep your site running so you don't have to think about it.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:1.5rem">
      <div class="nb-icon-badge">🤝</div>
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"},"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
      <h3>Empower</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">You get the keys — edit your own content anytime through an intuitive dashboard. We're here when you need us.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 4: Create `patterns/portfolio-grid.php`**

Asymmetric grid with placeholder project cards.

```php
<?php
/**
 * Title: Portfolio Grid
 * Slug: newblood/portfolio-grid
 * Categories: newblood
 * Description: Asymmetric portfolio grid with featured project
 */
?>
<!-- wp:group {"className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|80","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group nb-gradient-section">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"flex","justifyContent":"space-between"}} -->
  <div class="wp-block-group nb-reveal">
    <!-- wp:group {"layout":{"type":"constrained"}} -->
    <div class="wp-block-group">
      <!-- wp:paragraph {"className":"nb-label"} -->
      <p class="nb-label">Our work</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
      <h2>Recent projects</h2>
      <!-- /wp:heading -->
    </div>
    <!-- /wp:group -->
    <!-- wp:paragraph {"textColor":"accent"} -->
    <p class="has-accent-color"><a href="/work" style="color:inherit;text-decoration:none">View all →</a></p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"width":"58%","className":"nb-reveal-scale"} -->
    <div class="wp-block-column nb-reveal-scale" style="flex-basis:58%">
      <!-- wp:group {"className":"nb-glass","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|40"}}}} -->
      <div class="wp-block-group nb-glass">
        <!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"dimensions":{"minHeight":"200px"},"color":{"background":"#1a2332"}},"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-group" style="min-height:200px;border-radius:12px 12px 0 0">
          <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem","fontWeight":"600"}}} -->
          <p class="has-text-muted-color">[ Project Screenshot ]</p>
          <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->
        <!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
        <div class="wp-block-group">
          <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
          <h3>Coming Soon</h3>
          <!-- /wp:heading -->
          <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.8125rem"}}} -->
          <p class="has-text-muted-color">Our first modern project showcase</p>
          <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"width":"42%","className":"nb-reveal-scale"} -->
    <div class="wp-block-column nb-reveal-scale" style="flex-basis:42%">
      <!-- wp:group {"className":"nb-glass","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|30"}},"dimensions":{"minHeight":"100%"}}} -->
      <div class="wp-block-group nb-glass" style="min-height:100%">
        <!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"dimensions":{"minHeight":"120px"},"color":{"background":"#1e1a2e"}},"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-group" style="min-height:120px;border-radius:12px 12px 0 0">
          <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.75rem","fontWeight":"600"}}} -->
          <p class="has-text-muted-color">[ Screenshot ]</p>
          <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->
        <!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}}} -->
        <div class="wp-block-group">
          <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1rem"}}} -->
          <h3>Your Project Here</h3>
          <!-- /wp:heading -->
          <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.75rem"}}} -->
          <p class="has-text-muted-color">Let's build something great together</p>
          <!-- /wp:paragraph -->
        </div>
        <!-- /wp:group -->
      </div>
      <!-- /wp:group -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 5: Create `patterns/testimonial.php`**

```php
<?php
/**
 * Title: Testimonial
 * Slug: newblood/testimonial
 * Categories: newblood
 * Description: Client testimonial quote
 */
?>
<!-- wp:group {"className":"nb-divider-top nb-reveal","style":{"color":{"background":"#0d0f14"},"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group nb-divider-top nb-reveal" style="text-align:center">
  <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem"}},"textColor":"accent"} -->
  <p class="has-accent-color">"</p>
  <!-- /wp:paragraph -->
  <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1.125rem","lineHeight":"1.7","fontStyle":"italic"}}} -->
  <p class="has-text-body-color">They rebuilt our entire website in a week. It loads instantly, looks incredible, and I can update it myself. Best investment we've made.</p>
  <!-- /wp:paragraph -->
  <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.8125rem"},"spacing":{"margin":{"top":"var:preset|spacing|40"}}},"textColor":"text-muted"} -->
  <p class="has-text-muted-color"><strong style="color:#94a3b8">Sarah M.</strong> — Local Business Owner</p>
  <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 6: Create `patterns/cta.php`**

```php
<?php
/**
 * Title: Call to Action
 * Slug: newblood/cta
 * Categories: newblood
 * Description: Bottom CTA section with gradient and dot grid
 */
?>
<!-- wp:group {"className":"nb-gradient-primary nb-dot-grid nb-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group nb-gradient-primary nb-dot-grid nb-reveal" style="text-align:center">
  <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
  <h2>Ready to get started?</h2>
  <!-- /wp:heading -->
  <!-- wp:paragraph {"textColor":"text-secondary","style":{"typography":{"fontSize":"1rem"}}} -->
  <p class="has-text-secondary-color">Tell us about your project. We'll get back to you within 24 hours.</p>
  <!-- /wp:paragraph -->
  <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
  <p><a class="nb-btn-primary" href="/contact" style="padding:0.75rem 2rem;font-size:1rem">Start Your Project</a></p>
  <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 7: Add pattern-specific layout CSS to `assets/css/patterns.css`**

Append to the existing patterns.css:

```css
/* Portfolio grid asymmetric layout */
@media (max-width: 768px) {
  .nb-stagger > .wp-block-column {
    flex-basis: 100% !important;
  }
}

/* Icon badge in service cards */
.nb-icon-badge {
  margin-bottom: 0.5rem;
}

/* Testimonial quote mark */
.nb-divider-top [class*="has-accent-color"] {
  line-height: 1;
  margin-bottom: -0.5rem;
}

/* Ensure dot-grid sits behind content */
.nb-dot-grid > * {
  position: relative;
  z-index: 1;
}
```

- [ ] **Step 8: Verify all patterns appear in block editor**

In WP admin, create a new page. Open the block inserter and check under "Patterns" → "New Blood" category. All 6 patterns should be listed: Hero, Social Proof, Services Cards, Portfolio Grid, Testimonial, Call to Action.

- [ ] **Step 9: Commit**

```bash
git add wp-content/themes/newblood/
git commit -m "feat: add homepage block patterns (hero, services, portfolio, testimonial, CTA)"
```

---

## Task 5: Assemble Homepage Template

**Files:**
- Create: `wp-content/themes/newblood/templates/front-page.html`

- [ ] **Step 1: Create `templates/front-page.html`**

Assembles all homepage patterns in the correct scroll order.

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
  <!-- wp:pattern {"slug":"newblood/hero"} /-->
  <!-- wp:pattern {"slug":"newblood/social-proof"} /-->
  <!-- wp:pattern {"slug":"newblood/services-cards"} /-->
  <!-- wp:pattern {"slug":"newblood/portfolio-grid"} /-->
  <!-- wp:pattern {"slug":"newblood/testimonial"} /-->
  <!-- wp:pattern {"slug":"newblood/cta"} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

- [ ] **Step 2: Set homepage in WordPress settings**

In WP admin: Settings → Reading → "Your homepage displays" → select "A static page" → set to the "Home" page. Or use WP-CLI:

```bash
wp option update show_on_front page
wp option update page_on_front $(wp post list --post_type=page --name=home --field=ID)
```

- [ ] **Step 3: Verify homepage renders**

Visit the local site. The homepage should display all sections in order: header → hero → social proof → services → portfolio → testimonial → CTA → footer. All with the dark gradient theme, scroll animations, and frosted glass effects.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/newblood/
git commit -m "feat: assemble homepage template from block patterns"
```

---

## Task 6: Build Interior Page Patterns and Templates

**Files:**
- Create: `wp-content/themes/newblood/patterns/page-header.php`
- Create: `wp-content/themes/newblood/patterns/how-it-works.php`
- Create: `wp-content/themes/newblood/patterns/pricing-table.php`
- Create: `wp-content/themes/newblood/patterns/contact-form.php`
- Create: `wp-content/themes/newblood/patterns/about-story.php`
- Create: `wp-content/themes/newblood/patterns/faq.php`
- Create: `wp-content/themes/newblood/templates/page.html`

- [ ] **Step 1: Create `patterns/page-header.php`**

Reusable dark header for interior pages with a title and subtitle.

```php
<?php
/**
 * Title: Page Header
 * Slug: newblood/page-header
 * Categories: newblood
 * Description: Interior page header with dark background
 */
?>
<!-- wp:group {"className":"nb-gradient-primary nb-dot-grid","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group nb-gradient-primary nb-dot-grid" style="text-align:center">
  <!-- wp:paragraph {"className":"nb-label nb-hero-label"} -->
  <p class="nb-label nb-hero-label">Page subtitle here</p>
  <!-- /wp:paragraph -->
  <!-- wp:post-title {"level":1,"className":"nb-hero-headline","style":{"typography":{"fontSize":"clamp(2rem, 4vw, 3rem)","lineHeight":"1.15","letterSpacing":"-0.02em"}}} /-->
</div>
<!-- /wp:group -->
```

- [ ] **Step 2: Create `patterns/how-it-works.php`**

Three-step process: Tell us → We build → You manage.

```php
<?php
/**
 * Title: How It Works
 * Slug: newblood/how-it-works
 * Categories: newblood
 * Description: 3-step process section
 */
?>
<!-- wp:group {"className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group nb-gradient-section">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">How it works</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>Three simple steps</h2>
    <!-- /wp:heading -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"className":"nb-reveal"} -->
    <div class="wp-block-column nb-reveal" style="text-align:center">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","fontWeight":"800"}},"textColor":"accent"} -->
      <p class="has-accent-color">1</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>Tell Us Your Vision</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Share your goals, brand, and what you need. We'll put together a plan and timeline.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-reveal"} -->
    <div class="wp-block-column nb-reveal" style="text-align:center">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","fontWeight":"800"}},"textColor":"accent"} -->
      <p class="has-accent-color">2</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>We Build It Fast</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Using AI-augmented development, we design and build your site in days — with your feedback along the way.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-reveal"} -->
    <div class="wp-block-column nb-reveal" style="text-align:center">
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2rem","fontWeight":"800"}},"textColor":"accent"} -->
      <p class="has-accent-color">3</p>
      <!-- /wp:paragraph -->
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.125rem"}}} -->
      <h3>You Own It</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">We hand you the keys. Update your content anytime. We handle hosting, security, and updates behind the scenes.</p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 3: Create `patterns/pricing-table.php`**

Two pricing tiers — Build packages. Management plans shown below.

```php
<?php
/**
 * Title: Pricing Table
 * Slug: newblood/pricing-table
 * Categories: newblood
 * Description: Pricing tiers for build packages and management plans
 */
?>
<!-- wp:group {"className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1000px"}} -->
<div class="wp-block-group nb-gradient-section">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">Pricing</p>
    <!-- /wp:paragraph -->
    <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
    <h2>Simple, transparent pricing</h2>
    <!-- /wp:heading -->
    <!-- wp:paragraph {"textColor":"text-secondary"} -->
    <p class="has-text-secondary-color">Every plan includes hosting on our managed infrastructure.</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"className":"nb-glass nb-reveal"} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem">
      <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
      <h3>Starter</h3>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">Perfect for small businesses getting started online.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
      <p><span style="color:#4ade80">$X,XXX</span></p>
      <!-- /wp:paragraph -->
      <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
      <ul class="has-text-body-color">
        <li>Up to 5 pages</li>
        <li>Mobile responsive design</li>
        <li>Content management training</li>
        <li>2 rounds of revisions</li>
      </ul>
      <!-- /wp:list -->
      <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
      <p><a class="nb-btn-primary" href="/contact" style="display:block;text-align:center">Get Started</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal","style":{"border":{"color":"rgba(74,222,128,0.3)","width":"1px"}}} -->
    <div class="wp-block-column nb-glass nb-reveal" style="padding:2rem;border-color:rgba(74,222,128,0.3)">
      <!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between"}} -->
      <div class="wp-block-group">
        <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"1.25rem"}}} -->
        <h3>Business</h3>
        <!-- /wp:heading -->
        <!-- wp:paragraph {"style":{"typography":{"fontSize":"0.625rem","textTransform":"uppercase","letterSpacing":"1px","fontWeight":"700"},"color":{"background":"rgba(34,197,94,0.15)","text":"#4ade80"},"spacing":{"padding":{"top":"0.25rem","bottom":"0.25rem","left":"0.5rem","right":"0.5rem"}},"border":{"radius":"4px"}}} -->
        <p>Popular</p>
        <!-- /wp:paragraph -->
      </div>
      <!-- /wp:group -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"0.875rem"}}} -->
      <p class="has-text-muted-color">For businesses that need more functionality and a custom look.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"style":{"typography":{"fontSize":"2.5rem","fontWeight":"800"},"spacing":{"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}}} -->
      <p><span style="color:#4ade80">$X,XXX</span></p>
      <!-- /wp:paragraph -->
      <!-- wp:list {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"},"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
      <ul class="has-text-body-color">
        <li>Up to 10 pages</li>
        <li>Custom design + animations</li>
        <li>E-commerce ready</li>
        <li>SEO optimization</li>
        <li>3 rounds of revisions</li>
      </ul>
      <!-- /wp:list -->
      <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
      <p><a class="nb-btn-primary" href="/contact" style="display:block;text-align:center">Get Started</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 4: Create `patterns/contact-form.php`**

Simple contact section — uses WPForms shortcode or a basic HTML form as placeholder.

```php
<?php
/**
 * Title: Contact Form
 * Slug: newblood/contact-form
 * Categories: newblood
 * Description: Contact form section
 */
?>
<!-- wp:group {"className":"nb-gradient-section nb-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<div class="wp-block-group nb-gradient-section nb-reveal" style="text-align:center">
  <!-- wp:paragraph {"className":"nb-label"} -->
  <p class="nb-label">Get in touch</p>
  <!-- /wp:paragraph -->
  <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
  <h2>Start your project</h2>
  <!-- /wp:heading -->
  <!-- wp:paragraph {"textColor":"text-secondary","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} -->
  <p class="has-text-secondary-color">Tell us about your business and what you need. We'll get back to you within 24 hours.</p>
  <!-- /wp:paragraph -->
  <!-- wp:shortcode -->
  [wpforms id="FORM_ID"]
  <!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
```

Note: Replace `FORM_ID` with the actual WPForms form ID after creating the contact form in WPForms.

- [ ] **Step 5: Create `patterns/about-story.php`**

```php
<?php
/**
 * Title: About Story
 * Slug: newblood/about-story
 * Categories: newblood
 * Description: Company story section for the About page
 */
?>
<!-- wp:group {"className":"nb-gradient-section nb-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group nb-gradient-section nb-reveal">
  <!-- wp:paragraph {"className":"nb-label"} -->
  <p class="nb-label">Our story</p>
  <!-- /wp:paragraph -->
  <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"}}} -->
  <h2>15+ years building for the web</h2>
  <!-- /wp:heading -->
  <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
  <p class="has-text-body-color">New Blood started in 2010 with a simple mission: help businesses thrive online. For over a decade, we've built and managed WordPress websites for clients across industries.</p>
  <!-- /wp:paragraph -->
  <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
  <p class="has-text-body-color">Today, the web has evolved — and so have we. We pair our deep technical expertise with modern AI-powered development tools to deliver faster, better results than ever before. WordPress itself has transformed into a modern, powerful platform — and we're at the forefront of that evolution.</p>
  <!-- /wp:paragraph -->
  <!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"1.0625rem","lineHeight":"1.8"}}} -->
  <p class="has-text-body-color">The result? Beautiful, blazing-fast websites delivered in days — that you can actually manage yourself.</p>
  <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 6: Create `patterns/faq.php`**

```php
<?php
/**
 * Title: FAQ
 * Slug: newblood/faq
 * Categories: newblood
 * Description: Frequently asked questions accordion
 */
?>
<!-- wp:group {"className":"nb-gradient-section nb-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
<div class="wp-block-group nb-gradient-section nb-reveal">
  <!-- wp:paragraph {"className":"nb-label"} -->
  <p class="nb-label">FAQ</p>
  <!-- /wp:paragraph -->
  <!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.5rem, 3vw, 2rem)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}}} -->
  <h2>Common questions</h2>
  <!-- /wp:heading -->
  <!-- wp:details -->
  <details><summary>How long does it take to build a website?</summary><!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"}}} -->
  <p class="has-text-body-color">Most projects are delivered in 1-2 weeks. Our AI-augmented development process dramatically accelerates the build timeline without sacrificing quality.</p>
  <!-- /wp:paragraph --></details>
  <!-- /wp:details -->
  <!-- wp:details -->
  <details><summary>Can I update the website myself?</summary><!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"}}} -->
  <p class="has-text-body-color">Absolutely. Every site we build uses WordPress's modern block editor, which lets you edit pages visually — no coding required. We provide training to get you comfortable.</p>
  <!-- /wp:paragraph --></details>
  <!-- /wp:details -->
  <!-- wp:details -->
  <details><summary>What's included in the management plan?</summary><!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"}}} -->
  <p class="has-text-body-color">Hosting on our managed infrastructure, security monitoring, WordPress updates, daily backups, uptime monitoring, and priority support. Everything you need to keep your site running.</p>
  <!-- /wp:paragraph --></details>
  <!-- /wp:details -->
  <!-- wp:details -->
  <details><summary>What if I already have a website?</summary><!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"}}} -->
  <p class="has-text-body-color">We can redesign and migrate your existing site. We'll preserve your content and SEO while giving you a completely fresh, modern design.</p>
  <!-- /wp:paragraph --></details>
  <!-- /wp:details -->
  <!-- wp:details -->
  <details><summary>Do you build on platforms other than WordPress?</summary><!-- wp:paragraph {"textColor":"text-body","style":{"typography":{"fontSize":"0.875rem"}}} -->
  <p class="has-text-body-color">We specialize in WordPress because it's the most powerful, flexible CMS available — and it puts you in control of your own content. For most businesses, it's the best choice.</p>
  <!-- /wp:paragraph --></details>
  <!-- /wp:details -->
</div>
<!-- /wp:group -->
```

- [ ] **Step 7: Create `templates/page.html`**

Generic page template for interior pages.

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
  <!-- wp:pattern {"slug":"newblood/page-header"} /-->
  <!-- wp:group {"className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"800px"}} -->
  <div class="wp-block-group nb-gradient-section">
    <!-- wp:post-content /-->
  </div>
  <!-- /wp:group -->
  <!-- wp:pattern {"slug":"newblood/cta"} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

- [ ] **Step 8: Add FAQ details styling to `assets/css/patterns.css`**

```css
/* FAQ details/summary styling */
details {
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
  padding: 1rem 0;
}

details summary {
  cursor: pointer;
  font-weight: 600;
  color: #e2e8f0;
  font-size: 1rem;
  list-style: none;
}

details summary::-webkit-details-marker {
  display: none;
}

details summary::before {
  content: "+";
  color: var(--wp--preset--color--accent);
  font-weight: 700;
  margin-right: 0.75rem;
}

details[open] summary::before {
  content: "−";
}

details p {
  margin-top: 0.75rem;
}
```

- [ ] **Step 9: Commit**

```bash
git add wp-content/themes/newblood/
git commit -m "feat: add interior page patterns (page-header, how-it-works, pricing, contact, about, FAQ)"
```

---

## Task 7: Create WordPress Pages and Menus

**No theme files modified — WordPress admin/WP-CLI operations only.**

- [ ] **Step 1: Create/update pages via WP-CLI**

```bash
# Update existing Home page content (or create if needed)
wp post list --post_type=page --name=home --field=ID

# Create new pages
wp post create --post_type=page --post_title='Services' --post_name='services' --post_status=publish
wp post create --post_type=page --post_title='Pricing' --post_name='pricing' --post_status=publish
wp post create --post_type=page --post_title='Work' --post_name='work' --post_status=publish
wp post create --post_type=page --post_title='About' --post_name='about' --post_status=publish
wp post create --post_type=page --post_title='Contact' --post_name='contact' --post_status=publish
```

Note: If pages with these slugs already exist, update them instead of creating duplicates. Check with `wp post list --post_type=page --post_status=any --fields=ID,post_title,post_name`.

- [ ] **Step 2: Set up navigation menus**

Create primary header menu and footer menus via WP admin (Appearance → Editor → Navigation) or WP-CLI. The header menu should include: Services, Work, Pricing, About, and a "Get Started" link to Contact.

- [ ] **Step 3: Add block patterns to page content**

For each page, edit in the block editor and insert the appropriate patterns:
- **Services page:** page-header + services-cards + how-it-works + CTA
- **Pricing page:** page-header + pricing-table + FAQ + CTA
- **Work page:** page-header + portfolio-grid + testimonial + CTA
- **About page:** page-header + about-story + CTA
- **Contact page:** page-header + contact-form

- [ ] **Step 4: Delete old/unused pages**

Remove pages that are no longer needed:
- Soccer photography pages (IDs: 6196, 5643, 5250, 5215)
- Discovery Workshop evaluation form (ID: 5205)
- Page Preview pages
- Old "Read Our Recent Updates and Ideas" (news listing)

```bash
wp post delete 6196 5643 5250 5215 5205 --force
```

- [ ] **Step 5: Verify all pages render correctly**

Visit each page locally and confirm patterns render with correct styling, animations work, and navigation is functional.

- [ ] **Step 6: Commit any theme changes and note page setup**

```bash
git add -A
git commit -m "feat: set up WordPress pages, navigation menus, and page content"
```

---

## Task 8: Plugin Cleanup

**No theme files — WordPress admin/WP-CLI operations.**

- [ ] **Step 1: Deactivate plugins to remove**

```bash
wp plugin deactivate revslider essential-grid go_pricing grandportfolio-custom-post tinymce-advanced classic-editor facebook-pagelike-widget envato-market wp-crontrol user-switching post-types-order forms-contact woocommerce-legacy-rest-api
```

- [ ] **Step 2: Verify site still works after deactivation**

Visit the local site. Check homepage, interior pages, and WP admin. Confirm nothing is broken.

- [ ] **Step 3: Delete deactivated plugins**

```bash
wp plugin delete revslider essential-grid go_pricing grandportfolio-custom-post tinymce-advanced classic-editor facebook-pagelike-widget envato-market wp-crontrol user-switching post-types-order forms-contact woocommerce-legacy-rest-api
```

- [ ] **Step 4: Install WPForms Lite (if not already present)**

```bash
wp plugin install wpforms-lite --activate
```

Then create a contact form in WPForms admin and update the `contact-form.php` pattern with the correct form ID.

- [ ] **Step 5: Delete old themes**

```bash
wp theme delete grandportfolio newbloodchildtheme twentyfifteen twentytwentyone twentytwentytwo twentytwentythree twentytwentyfour twentytwentyfive
```

Keep only the new `newblood` theme. Note: WordPress requires at least one default theme as fallback — keep `twentytwentyfive` if WP complains.

- [ ] **Step 6: Verify plugin list is clean**

```bash
wp plugin list --status=active
```

Should show only the ~12 plugins listed in the spec's "Keep" table plus WPForms Lite.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "chore: remove unused plugins and old themes"
```

---

## Task 9: Create 404 and Single Post Templates

**Files:**
- Create: `wp-content/themes/newblood/templates/404.html`
- Create: `wp-content/themes/newblood/templates/single.html`

- [ ] **Step 1: Create `templates/404.html`**

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main","className":"nb-gradient-primary nb-dot-grid","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<main class="wp-block-group nb-gradient-primary nb-dot-grid" style="text-align:center">
  <!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(3rem, 8vw, 6rem)","fontWeight":"800"}},"textColor":"accent"} -->
  <h1 class="has-accent-color">404</h1>
  <!-- /wp:heading -->
  <!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"1.5rem"}}} -->
  <h2>Page not found</h2>
  <!-- /wp:heading -->
  <!-- wp:paragraph {"textColor":"text-secondary"} -->
  <p class="has-text-secondary-color">The page you're looking for doesn't exist or has been moved.</p>
  <!-- /wp:paragraph -->
  <!-- wp:paragraph {"style":{"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
  <p><a class="nb-btn-primary" href="/">Back to Home</a></p>
  <!-- /wp:paragraph -->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

- [ ] **Step 2: Create `templates/single.html`**

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:group {"tagName":"main"} -->
<main class="wp-block-group">
  <!-- wp:group {"className":"nb-gradient-primary nb-dot-grid","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
  <div class="wp-block-group nb-gradient-primary nb-dot-grid" style="text-align:center">
    <!-- wp:post-title {"level":1,"className":"nb-hero-headline","style":{"typography":{"fontSize":"clamp(2rem, 4vw, 3rem)","lineHeight":"1.15","letterSpacing":"-0.02em"}}} /-->
    <!-- wp:post-date {"textColor":"text-muted","style":{"typography":{"fontSize":"0.8125rem"}}} /-->
  </div>
  <!-- /wp:group -->
  <!-- wp:group {"className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
  <div class="wp-block-group nb-gradient-section">
    <!-- wp:post-content /-->
  </div>
  <!-- /wp:group -->
  <!-- wp:pattern {"slug":"newblood/cta"} /-->
</main>
<!-- /wp:group -->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

- [ ] **Step 3: Verify both templates**

Visit a non-existent URL to test the 404. If there are published blog posts, visit one to test the single template.

- [ ] **Step 4: Commit**

```bash
git add wp-content/themes/newblood/templates/
git commit -m "feat: add 404 and single post templates"
```

---

## Task 10: Final Polish and QA

- [ ] **Step 1: Test responsive design**

Open browser DevTools, test at these breakpoints:
- Mobile: 375px
- Tablet: 768px
- Desktop: 1280px
- Large: 1440px

Check: hero text sizing, card stacking, navigation hamburger menu, footer columns collapsing.

- [ ] **Step 2: Test scroll animations**

Scroll through every page. Verify:
- Hero elements animate on load
- Cards fade in on scroll with stagger
- Portfolio cards scale in
- Animations only fire once (observer unobserves after trigger)
- `prefers-reduced-motion` disables all animations

- [ ] **Step 3: Test WooCommerce integration**

If WooCommerce billing is set up, verify:
- Cart/Checkout pages render with the dark theme
- Stripe payment flow works
- Subscription signup works

- [ ] **Step 4: Run Lighthouse audit**

```bash
# Using Chrome DevTools Lighthouse tab, or:
npx lighthouse http://newblood.test --view
```

Target: 90+ Performance, 90+ Accessibility, 90+ Best Practices, 90+ SEO.

- [ ] **Step 5: Fix any issues found in steps 1-4**

Address responsive bugs, animation glitches, or performance issues.

- [ ] **Step 6: Create theme screenshot**

Take a 1200×900 screenshot of the homepage and save as `wp-content/themes/newblood/screenshot.png`.

- [ ] **Step 7: Final commit and push**

```bash
git add -A
git commit -m "feat: final polish, responsive fixes, and theme screenshot"
git push origin main
```

- [ ] **Step 8: Request user approval for production deploy**

Present the completed local site to the user. Only deploy to Nexcess production after explicit approval.
