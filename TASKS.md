# 📋 Tasks — New Blood

**Status:** NOW (priority 1)
**Goal (one sentence):** Launch the redesigned New Blood site — new theme + reworked content — live and clean.

---

## ▶️ Doing now
- [ ] Post-launch QA on live newblood.com: mobile/cross-browser, test contact form + WooCommerce checkout, 301 legacy URLs (/case-studies/ → /work/), Yoast titles/meta for new pages, confirm homepage latest-note isn't stale.

## 🔜 Up next
> _Suggested launch checklist — edit to match reality, delete what doesn't apply._
- [ ] Proofread all reworked content (typos, headings, calls-to-action)
- [ ] Mobile + cross-browser check (Safari, Chrome, mobile widths)
- [ ] Test every link, form, and button
- [ ] SEO basics: page titles, meta descriptions, sitemap, favicon; 301s for legacy URLs (/case-studies/ → /work/)
- [ ] Analytics installed and firing
- [ ] Post-launch smoke test on the live URL (incl. WooCommerce checkout + subscription renewal scheduler intact)

## ✅ Done (newest first)
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
