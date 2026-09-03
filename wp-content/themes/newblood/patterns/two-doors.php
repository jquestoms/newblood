<?php
/**
 * Title: Two Doors
 * Slug: newblood/two-doors
 * Categories: newblood
 * Description: Homepage section directly below the hero. Two ways in: a site that represents you, or a platform that wins your territory.
 */
?>
<!-- wp:group {"className":"nb-two-doors","style":{"spacing":{"padding":{"top":"var:preset|spacing|80","bottom":"var:preset|spacing|80","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group nb-two-doors">
  <!-- wp:group {"className":"nb-reveal","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
  <div class="wp-block-group nb-reveal" style="text-align:center">
    <!-- wp:paragraph {"className":"nb-label"} -->
    <p class="nb-label">Two ways in</p>
    <!-- /wp:paragraph -->
  </div>
  <!-- /wp:group -->
  <!-- wp:columns {"className":"nb-stagger","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
  <div class="wp-block-columns nb-stagger">
    <!-- wp:column {"className":"nb-glass nb-reveal nb-door"} -->
    <div class="wp-block-column nb-glass nb-reveal nb-door" style="padding:2.5rem">
      <div class="nb-service-mark">
        <svg viewBox="0 0 80 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
          <path class="stroke draw" d="M14 66 L52 28 l10 10 L24 76z"/>
          <line class="stroke draw draw--2" x1="46" y1="34" x2="56" y2="44"/>
          <path class="stroke draw draw--3" d="M56 14 l10 10 -8 8 -10-10z"/>
          <circle class="dot" cx="18" cy="72" r="2.2"/>
          <line class="baseline" x1="8" y1="78" x2="72" y2="78"/>
        </svg>
      </div>
      <!-- wp:heading {"level":2,"className":"nb-door-title"} -->
      <h2 class="nb-door-title">A site that represents you<span style="color:#4ade80">.</span></h2>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"1rem","lineHeight":"1.7"}}} -->
      <p class="has-text-muted-color">Custom design and clean code for businesses that want a website made for them, not chosen from a template. Build, Tune, Manage, Empower, with pricing you can read before you call.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"className":"nb-door-links"} -->
      <p class="nb-door-links"><a class="nb-btn-secondary" href="/pricing/">See pricing</a> <a class="nb-door-textlink" href="/work/">See our work</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column {"className":"nb-glass nb-reveal nb-door nb-door--territory","style":{"border":{"color":"rgba(74,222,128,0.3)","width":"1px"}}} -->
    <div class="wp-block-column nb-glass nb-reveal nb-door nb-door--territory" style="padding:2.5rem;border-color:rgba(74,222,128,0.3)">
      <div class="nb-service-mark">
        <svg viewBox="0 0 80 80" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
          <rect class="stroke draw" x="10" y="12" width="22" height="16" rx="3"/>
          <rect class="stroke draw draw--2" x="48" y="12" width="22" height="16" rx="3"/>
          <rect class="stroke draw draw--2" x="48" y="48" width="22" height="16" rx="3"/>
          <rect class="stroke draw draw--3" x="10" y="48" width="22" height="16" rx="3"/>
          <path class="stroke draw draw--2" d="M32 20 H48 M59 28 V48 M48 56 H32"/>
          <path class="stroke draw draw--3" d="M21 48 V34"/>
          <path class="dot" d="M21 30 l-5 8 h10z"/>
          <circle class="dot" cx="59" cy="56" r="2.2"/>
        </svg>
      </div>
      <!-- wp:heading {"level":2,"className":"nb-door-title"} -->
      <h2 class="nb-door-title">A platform that wins your territory<span style="color:#4ade80">.</span></h2>
      <!-- /wp:heading -->
      <!-- wp:paragraph {"textColor":"text-muted","style":{"typography":{"fontSize":"1rem","lineHeight":"1.7"}}} -->
      <p class="has-text-muted-color">For companies that built their name over decades, and refuse to rent it back one click at a time. A website, a content program, and a measurement system, with every account in your name.</p>
      <!-- /wp:paragraph -->
      <!-- wp:paragraph {"className":"nb-door-links"} -->
      <p class="nb-door-links"><a class="nb-btn-primary" href="/territory/">The Territory Platform</a></p>
      <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
  </div>
  <!-- /wp:columns -->
</div>
<!-- /wp:group -->
