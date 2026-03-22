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
