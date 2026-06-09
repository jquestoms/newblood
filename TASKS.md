# 📋 Tasks — New Blood

**Status:** NOW (priority 1)
**Goal (one sentence):** Launch the redesigned New Blood site — new theme + reworked content — live and clean.

---

## ▶️ Doing now
- [ ] Post-launch polish (launch done & on `main`; Yoast meta + /case-studies/ 301 done): mobile/cross-browser pass, test a WooCommerce checkout, confirm footer email/phone are current. Swap RTZ status line to a live link when the RTZ site launches Jul 1.

## 🔜 Up next
> _Launch checklist — done items checked off this activity._
- [x] Full backup before go-live (Nexcess, downloaded)
- [x] Cutover to production (theme activated, content migrated, commerce intact)
- [x] Post-launch smoke test on the live URL (pages 200, footer/case-study fixes, contact form, commerce untouched)
- [x] Pushed to GitHub + merged `feature/redesign` → `main`
- [ ] Proofread all reworked content (typos, headings, calls-to-action)
- [ ] Mobile + cross-browser check (Safari, Chrome, mobile widths)
- [ ] Test every link, form, and button (contact form verified; checkout still to test)
- [ ] SEO: page titles, meta descriptions, sitemap; 301s for legacy URLs (/case-studies/ → /work/)
- [ ] Analytics installed and firing

## ✅ Done (newest first)
- [x] 2026-06-09 — Added theme-level 301 redirect /case-studies/ → /work/ (verified live; Yoast Free has no redirect manager). Orphaned legacy URLs left as clean 404s by design.
- [x] 2026-06-09 — Set Yoast SEO titles + meta descriptions + focus keyphrases for all 11 redesign pages (live + verified); reproducible record at docs/seo/page-meta.php
- [x] 2026-06-09 — Pushed feature/redesign to GitHub + fast-forward merged → main (99 commits; redesign now version-controlled & backed up on origin/main)
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
