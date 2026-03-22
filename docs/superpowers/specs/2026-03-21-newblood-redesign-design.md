# New Blood Website Redesign — Design Spec

## Overview

Redesign newblood.com from an outdated WordPress maintenance shop into a modern web agency site that showcases AI-augmented development on the WordPress platform. The site should feel alive, bold, and distinct from typical WordPress agency sites.

## Target Audience

Small business owners who need a professional web presence and don't want to deal with tech. They care about speed, quality, and being able to manage their own content — not what tools were used to build it.

## Positioning

- **Lead with outcomes:** "Modern websites, delivered fast" — speed, quality, affordability
- **AI as a supporting proof point:** It's how we deliver, not the headline
- **Own WordPress as a modern platform:** 15+ years of expertise paired with modern AI tools, building on the most powerful CMS in the world
- **Empower the client:** They get the keys to manage their own content

## Core Services

1. **Build** — Custom-designed, fast-loading websites delivered in days, not months
2. **Manage** — Hosting, security, updates, backups — ongoing management plans (includes hosting on Nexcess infrastructure)
3. **Empower** — Clients can edit their own content through the WordPress block editor

## Existing WordPress Clients

Grandfathered in. Existing WP maintenance clients continue to be serviced but WordPress maintenance is not marketed to new clients.

## Site Architecture

### Pages

| Page | Purpose |
|------|---------|
| Home | Hero + value prop, benefit cards, social proof, portfolio preview, testimonial, CTA |
| Services | Expanded Build/Manage/Empower details, "How it works" process, CTA to pricing |
| Pricing | Build packages (2-3 tiers) + monthly management plans (hosting included), FAQ |
| Work | Portfolio grid — starts small, grows over time. Each card → case study |
| About | Company story, 15+ year evolution, how AI tools accelerate delivery |
| Contact | Form + optional Calendly embed, 24-hour response promise |

### Removed Content

- Soccer photography pages
- Blog/News section (can add later)
- Discovery Workshop evaluation form
- Cart/Checkout/My Account pages (unless needed for WooCommerce billing)

## Visual Identity

### Color Direction: Dark with Subtle Gradient

- **Primary background:** Linear gradient from `#0f1117` through `#111827` to `#0f2218`
- **Accent:** Green (`#22c55e` / `#4ade80`) used for highlights, CTAs, labels
- **Card surfaces:** Frosted glass effect — `rgba(255,255,255,0.03-0.04)` background with `rgba(255,255,255,0.07-0.08)` borders
- **Text hierarchy:** White (`#ffffff`) for headlines, slate (`#94a3b8` / `#cbd5e1`) for body, dark slate (`#64748b`) for secondary
- **Subtle texture:** Dot grid pattern using radial-gradient at low opacity in hero and CTA sections

### Typography

- Sans-serif system font stack (`system-ui, -apple-system, sans-serif`) or a clean Google Font (Inter, Plus Jakarta Sans, or similar)
- Bold, large headlines (800 weight, tight letter-spacing)
- Clean, readable body text

### Logo

- Existing "newblood" wordmark retained but updated: white text with green accent on "blood" (or vice versa)

## Differentiation Strategy

Three combined approaches to stand out from template-based WordPress sites:

### A. Motion & Micro-Interactions

- **Page load:** Logo/nav fade in → green subtitle types/fades up → headline slides up → CTAs fade in
- **Scroll:** Content cards fade up with stagger, section headings animate in first, portfolio cards scale from 95% to 100%
- **Hover:** Cards lift with subtle glow, buttons transition smoothly, nav links get green underline slide-in
- **Implementation:** CSS `@keyframes` + `animation-timeline: scroll()`, Intersection Observer fallback, `prefers-reduced-motion` respected
- **No heavy libraries** — pure CSS + minimal vanilla JS

### B. Bold Visual Identity

- Dark gradient theme with vibrant green accents
- High contrast, confident typography
- Signature dot-grid texture pattern
- Frosted glass card effects

### C. Creative Layout & Grid Breaking

- Asymmetric portfolio grid (large feature + smaller cards)
- Sections flow into each other with gradient transitions rather than hard breaks
- Mix of full-width and contained content
- Angled or organic section dividers where appropriate

## Homepage Content Flow

1. **Hero** — Value proposition headline, subtitle, two CTAs (See Our Work / View Pricing), dot-grid texture background
2. **Social proof strip** — "Trusted by" + client logos
3. **What we do** — Build / Manage / Empower cards with icon badges, scroll-revealed
4. **Our work** — Asymmetric portfolio grid, "View all" link
5. **Testimonial** — Centered client quote with green quotation mark accent
6. **CTA** — "Ready to get started?" with dot-grid texture, primary button
7. **Footer** — Logo, tagline, Company links, Connect links, copyright

## Technical Implementation

### Approach: Custom WordPress Block Theme

A custom block theme built from scratch (no commercial theme like Kadence or GeneratePress). Full control over design, no fighting theme opinions, lightest possible output.

### Theme Structure

- **`theme.json`** — Design tokens: dark color palette, typography scale, spacing system, global styles
- **Custom CSS** — Scroll animations, asymmetric layouts, frosted glass effects, gradient backgrounds
- **Vanilla JS** — Intersection Observer for scroll reveals, typed headline effect (only where CSS alone can't achieve the effect)
- **Block patterns** — Reusable sections: hero, service cards, testimonial, CTA, portfolio grid, footer

### Plugin Inventory

**Keep:**

| Plugin | Purpose |
|--------|---------|
| WooCommerce | Billing platform |
| WooCommerce Subscriptions | Recurring management plans |
| WooCommerce Gateway Stripe | Payment processing |
| Yoast SEO | SEO |
| WP Mail SMTP Pro | Email delivery |
| Redis Cache | Object caching (already configured) |
| WP Defender | Security |
| Hummingbird | Performance/page caching |
| WP Smush Pro | Image optimization |
| CleanTalk | Spam protection |
| Imsanity | Image resize on upload |
| WPMU DEV Updates | License management |

**Remove:**

| Plugin | Reason |
|--------|--------|
| RevSlider | Old theme dependency, heavy |
| Essential Grid | Old theme dependency |
| Go Pricing | Replaced by custom block patterns |
| Grand Portfolio Custom Post | Theme-specific |
| TinyMCE Advanced | Not needed with block editor |
| Classic Editor | Blocks are the future |
| Facebook Pagelike Widget | Outdated |
| Envato Market | No longer buying ThemeForest themes |
| WP Crontrol | Dev tool |
| User Switching | Dev tool |
| Post Types Order | Unnecessary |
| Forms Contact | Replace with WPForms or simple custom form |
| WooCommerce Legacy REST API | Deprecated |

### Theme to Remove

- Grand Portfolio (parent theme)
- Newblood Child (child theme)
- Default themes not in use (Twenty Fifteen through Twenty Twenty-Five)

### Infrastructure

- **Hosting:** Nexcess (existing, WordPress-optimized)
- **Caching:** Redis object cache (existing) + Hummingbird page cache
- **CDN:** No CDN at this time
- **Version control:** GitHub (`git@github.com:jquestoms/newblood.git`)

### Development Workflow

1. Build and test locally on Laravel Herd
2. Commit changes to Git / push to GitHub
3. Deploy to Nexcess production via SFTP when ready on approval by user only

## Success Criteria

- Site loads in under 2 seconds
- Scores 90+ on Google PageSpeed (mobile and desktop)
- All pages responsive and functional on mobile
- Client can log in and edit page content via block editor
- WooCommerce billing flow works for plan signups
- Animations respect `prefers-reduced-motion`
- The site looks and feels distinctly different from template-based WordPress sites
