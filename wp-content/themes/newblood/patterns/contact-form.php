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
