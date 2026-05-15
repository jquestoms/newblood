<?php
/**
 * Title: Notes Index
 * Slug: newblood/notes-index
 * Categories: newblood
 * Description: Card grid of all published Notes posts (reverse chronological)
 *
 * Card markup comes from the [nb_note_card] shortcode (functions.php), which runs
 * inside wp:post-template's loop context.
 */
?>
<!-- wp:group {"align":"full","className":"nb-gradient-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","contentSize":"1200px"}} -->
<div class="wp-block-group alignfull nb-gradient-section">

  <!-- wp:query {"queryId":1,"query":{"perPage":12,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true},"className":"nb-notes-query"} -->
  <div class="wp-block-query nb-notes-query">

    <!-- wp:post-template {"className":"nb-notes-grid nb-stagger"} -->
      <!-- wp:shortcode -->[nb_note_card]<!-- /wp:shortcode -->
    <!-- /wp:post-template -->

    <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}}} -->
      <!-- wp:query-pagination-previous /-->
      <!-- wp:query-pagination-numbers /-->
      <!-- wp:query-pagination-next /-->
    <!-- /wp:query-pagination -->

    <!-- wp:query-no-results -->
      <!-- wp:paragraph {"align":"center","textColor":"text-muted"} -->
      <p class="has-text-align-center has-text-muted-color">No notes yet — first one is coming.</p>
      <!-- /wp:paragraph -->
    <!-- /wp:query-no-results -->

  </div>
  <!-- /wp:query -->

</div>
<!-- /wp:group -->
