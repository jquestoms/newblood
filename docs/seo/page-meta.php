<?php
/**
 * New Blood — Yoast SEO page meta (reproducible record).
 * SEO title / meta description / focus keyphrase live in postmeta (no version
 * control), so this script is the source of truth. Apply with:
 *   wp eval-file docs/seo/page-meta.php   (then optionally: wp yoast index)
 * Set on production 2026-06-09. Yoast Free — no redirect manager.
 */
// Set Yoast SEO title + meta description (+ focus keyphrase) per page, by slug.
// Run via wp eval-file, then `wp yoast index --reindex`.
$pages = array(
  "home" => array(
    "title" => "Modern Websites, Built & Managed — New Blood",
    "desc"  => "We pair 25+ years of craft with modern AI workflows to build sites measured in range, rhythm, and restraint — more considered and ambitious than the budget should allow.",
    "focus" => "modern web design studio",
  ),
  "services" => array(
    "title" => "Web Design Services — Build · Tune · Manage — New Blood",
    "desc"  => "Four ways we work: Build new sites, Tune existing ones for speed and SEO, Manage hosting and security, and Empower your team to run it all.",
    "focus" => "web design services",
  ),
  "work" => array(
    "title" => "Selected Work & Case Studies — New Blood",
    "desc"  => "Real projects from New Blood — modern sites and custom platforms built and tuned for institutions, e-commerce, and AI-native products.",
    "focus" => "web design case studies",
  ),
  "pricing" => array(
    "title" => "Website Pricing — Build & Care Plans — New Blood",
    "desc"  => "Transparent pricing: Starter, Business, and Reach build tiers, plus Tune and Tune Plus to refresh a site you already have.",
    "focus" => "website design pricing",
  ),
  "about" => array(
    "title" => "About New Blood — AI-Augmented Web Studio",
    "desc"  => "A web studio pairing decades of hands-on craft with modern AI workflows. We build deliberately, and we steward the work long after launch.",
    "focus" => "about new blood",
  ),
  "contact" => array(
    "title" => "Contact — Start Your Project — New Blood",
    "desc"  => "Tell us about your business and what you need. We'll reply within 24 hours with a plan and a timeline.",
    "focus" => "",
  ),
  "case-study-rtz-audio-visual" => array(
    "title" => "RTZ Audio Visual — Custom CMS Case Study — New Blood",
    "desc"  => "How we rebuilt a 35-year Maryland AV integrator on Next.js with a custom Postgres CMS — 231 brands, a federal client roster, editorial control end-to-end.",
    "focus" => "custom cms case study",
  ),
  "case-study-newfoodcenter" => array(
    "title" => "newfoodcenter.com — AI Grocery Platform — New Blood",
    "desc"  => "A multilingual, AI-native weekly grocery-deals platform for Southern California's Inland Empire, built from scratch on Next.js and Postgres.",
    "focus" => "ai grocery deals platform",
  ),
  "case-study-mikes-master-classes" => array(
    "title" => "Mike's Master Classes — E-Commerce Build — New Blood",
    "desc"  => "A premium jazz-guitar education platform with integrated e-commerce, built with a modern dark theme and a seamless learning experience.",
    "focus" => "ecommerce education platform",
  ),
  "case-study-ca-lindman" => array(
    "title" => "C.A. Lindman — WordPress Care — New Blood",
    "desc"  => "A 36-year restoration firm publishes its own content while we look after everything underneath — hosting, security, and stewardship.",
    "focus" => "wordpress care plan",
  ),
  "case-study-57cards" => array(
    "title" => "57Cards — WooCommerce Performance Tune — New Blood",
    "desc"  => "A working WooCommerce store made fast without a rebuild — a Tune engagement focused on performance and SEO on an existing site.",
    "focus" => "woocommerce performance",
  ),
);

foreach ( $pages as $slug => $m ) {
  $p = get_page_by_path( $slug );
  if ( ! $p ) { echo "MISSING: {$slug}\n"; continue; }
  update_post_meta( $p->ID, "_yoast_wpseo_title", $m["title"] );
  update_post_meta( $p->ID, "_yoast_wpseo_metadesc", $m["desc"] );
  if ( ! empty( $m["focus"] ) ) {
    update_post_meta( $p->ID, "_yoast_wpseo_focuskw", $m["focus"] );
  }
  echo "set {$slug} (#{$p->ID})\n";
}
