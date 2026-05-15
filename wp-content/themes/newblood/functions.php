<?php
/**
 * New Blood Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NEWBLOOD_VERSION', '1.0.0' );

/**
 * Enqueue theme styles and scripts
 */
function newblood_asset_version( $relative_path ) {
    $file = get_template_directory() . $relative_path;
    return file_exists( $file ) ? filemtime( $file ) : NEWBLOOD_VERSION;
}

function newblood_enqueue_assets() {
    wp_enqueue_style(
        'newblood-animations',
        get_template_directory_uri() . '/assets/css/animations.css',
        array(),
        newblood_asset_version( '/assets/css/animations.css' )
    );
    wp_enqueue_style(
        'newblood-patterns',
        get_template_directory_uri() . '/assets/css/patterns.css',
        array(),
        newblood_asset_version( '/assets/css/patterns.css' )
    );
    wp_enqueue_style(
        'newblood-utilities',
        get_template_directory_uri() . '/assets/css/utilities.css',
        array(),
        newblood_asset_version( '/assets/css/utilities.css' )
    );
    wp_enqueue_script(
        'newblood-scroll-reveal',
        get_template_directory_uri() . '/assets/js/scroll-reveal.js',
        array(),
        newblood_asset_version( '/assets/js/scroll-reveal.js' ),
        true
    );
    wp_enqueue_script(
        'newblood-gradient-mesh',
        get_template_directory_uri() . '/assets/js/gradient-mesh.js',
        array(),
        newblood_asset_version( '/assets/js/gradient-mesh.js' ),
        true
    );
    wp_enqueue_script(
        'newblood-interactive-cards',
        get_template_directory_uri() . '/assets/js/interactive-cards.js',
        array(),
        newblood_asset_version( '/assets/js/interactive-cards.js' ),
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
    add_theme_support( 'automatic-feed-links' );
}
add_action( 'after_setup_theme', 'newblood_setup' );

/**
 * ============================================================
 * Notes section — helpers, launch gate, admin notices, SEO head
 * Spec: docs/superpowers/specs/2026-05-15-notes-section-design.md
 * ============================================================
 */

// Launch gate. Flip to true once 3 real posts are published.
if ( ! defined( 'NB_NOTES_PUBLIC' ) ) {
    define( 'NB_NOTES_PUBLIC', false );
}

/**
 * Add `is-prelaunch` to body when the Notes section isn't yet public.
 */
function newblood_notes_body_class( $classes ) {
    if ( ! NB_NOTES_PUBLIC ) {
        $classes[] = 'is-prelaunch';
    }
    return $classes;
}
add_filter( 'body_class', 'newblood_notes_body_class' );

// Note: the site uses block-based navigation (wp:navigation refs in header/footer
// templates), not classic menus. The "menu-item--notes" class is added directly
// in the wp:navigation-link block's `className` attribute on the Notes link.
// No wp_nav_menu_objects filter needed — that hook only fires for classic menus.

/**
 * Reading time in whole minutes (250 wpm). Returns int.
 */
function newblood_reading_time( $post_id ) {
    $content = get_post_field( 'post_content', $post_id );
    $words   = str_word_count( wp_strip_all_tags( $content ) );
    return max( 1, (int) ceil( $words / 250 ) );
}

/**
 * Primary category for a post. Returns WP_Term or null.
 * Convention: first category by term_id when a post has multiple.
 */
function newblood_primary_category( $post_id ) {
    $cats = get_the_category( $post_id );
    if ( empty( $cats ) ) {
        return null;
    }
    usort( $cats, function( $a, $b ) { return $a->term_id - $b->term_id; } );
    return $cats[0];
}

/**
 * Admin notice on the post-edit screen when a published post has no featured image.
 * Soft warning — does not block publish.
 */
function newblood_notes_featured_image_notice() {
    $screen = get_current_screen();
    if ( ! $screen || $screen->base !== 'post' || $screen->post_type !== 'post' ) {
        return;
    }
    global $post;
    if ( ! $post || $post->post_status !== 'publish' ) {
        return;
    }
    if ( has_post_thumbnail( $post->ID ) ) {
        return;
    }
    echo '<div class="notice notice-warning"><p><strong>Notes:</strong> this published post has no featured image. It will render with a fallback gradient on /notes/ until one is added.</p></div>';
}
add_action( 'admin_notices', 'newblood_notes_featured_image_notice' );

/**
 * Shortcode: [nb_note_card]
 * Renders ONE Notes index card using the current post context (must be inside a loop,
 * typically wp:post-template). Block patterns execute their PHP at registration time,
 * not render time — so per-post dynamic markup goes through a shortcode that runs
 * with proper loop context on each request.
 */
function newblood_shortcode_note_card() {
    $post_id   = get_the_ID();
    if ( ! $post_id ) {
        return '';
    }
    $primary   = newblood_primary_category( $post_id );
    $permalink = get_permalink( $post_id );
    $title     = get_the_title( $post_id );
    $excerpt   = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), 28, '…' );
    $date      = get_the_date( 'F j, Y', $post_id );
    $reading   = newblood_reading_time( $post_id );
    $thumb     = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, 'large' ) : '';

    ob_start();
    ?>
    <a class="nb-note-card" href="<?php echo esc_url( $permalink ); ?>">
      <div class="nb-note-card-image"><?php echo $thumb; ?></div>
      <div class="nb-note-card-body">
        <?php if ( $primary ) : ?>
          <span class="nb-note-badge"><?php echo esc_html( $primary->name ); ?></span>
        <?php endif; ?>
        <h2 class="nb-note-card-title"><?php echo esc_html( $title ); ?></h2>
        <p class="nb-note-card-dek"><?php echo esc_html( $excerpt ); ?></p>
        <p class="nb-note-card-meta"><?php echo esc_html( $date ); ?> &middot; <?php echo esc_html( $reading ); ?> min read</p>
      </div>
    </a>
    <?php
    return ob_get_clean();
}
add_shortcode( 'nb_note_card', 'newblood_shortcode_note_card' );

/**
 * Shortcode: [nb_note_hero_image]
 * Renders the featured image inside the single-post hero, wrapped in .nb-note-hero-image.
 */
function newblood_shortcode_note_hero_image() {
    $post_id = get_the_ID();
    if ( ! $post_id || ! has_post_thumbnail( $post_id ) ) {
        return '';
    }
    return '<figure class="nb-note-hero-image">' . get_the_post_thumbnail( $post_id, 'large' ) . '</figure>';
}
add_shortcode( 'nb_note_hero_image', 'newblood_shortcode_note_hero_image' );

/**
 * Shortcode: [nb_note_hero_meta]
 * Renders the meta row on a single Notes post: category badge + date + reading time.
 */
function newblood_shortcode_note_hero_meta() {
    $post_id = get_the_ID();
    if ( ! $post_id ) {
        return '';
    }
    $primary = newblood_primary_category( $post_id );
    $date    = get_the_date( 'F j, Y', $post_id );
    $reading = newblood_reading_time( $post_id );

    ob_start();
    ?>
    <p class="nb-note-hero-meta">
      <?php if ( $primary ) : ?>
        <a class="nb-note-badge" href="<?php echo esc_url( get_category_link( $primary->term_id ) ); ?>"><?php echo esc_html( $primary->name ); ?></a>
        &nbsp;&middot;&nbsp;
      <?php endif; ?>
      <?php echo esc_html( $date ); ?>
      &nbsp;&middot;&nbsp;
      <?php echo esc_html( $reading ); ?> min read
    </p>
    <?php
    return ob_get_clean();
}
add_shortcode( 'nb_note_hero_meta', 'newblood_shortcode_note_hero_meta' );

/**
 * Shortcode: [nb_more_notes]
 * Renders the "More notes" rail on single posts — up to 3 recent posts excluding current.
 * Outputs nothing when no other posts exist.
 */
function newblood_shortcode_more_notes() {
    $current_id = get_the_ID();
    $recent = get_posts( array(
        'numberposts'      => 3,
        'post_status'      => 'publish',
        'exclude'          => $current_id ? array( $current_id ) : array(),
        'suppress_filters' => false,
    ) );

    if ( empty( $recent ) ) {
        return '';
    }

    ob_start();
    ?>
    <div class="wp-block-group alignfull nb-gradient-section" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);">
      <div style="max-width:1200px;margin:0 auto;">
        <h2 class="wp-block-heading" style="font-size:1.25rem;margin-bottom:var(--wp--preset--spacing--50);">More notes<span style="color:#4ade80">.</span></h2>
        <div class="nb-more-notes nb-stagger">
          <?php foreach ( $recent as $rp ) :
              $primary = newblood_primary_category( $rp->ID );
              $permalink = get_permalink( $rp->ID );
              $thumb     = has_post_thumbnail( $rp->ID ) ? get_the_post_thumbnail( $rp->ID, 'medium' ) : '';
              $date      = get_the_date( 'F j, Y', $rp->ID );
              $reading   = newblood_reading_time( $rp->ID );
          ?>
            <a class="nb-note-card" href="<?php echo esc_url( $permalink ); ?>">
              <div class="nb-note-card-image"><?php echo $thumb; ?></div>
              <div class="nb-note-card-body">
                <?php if ( $primary ) : ?>
                  <span class="nb-note-badge"><?php echo esc_html( $primary->name ); ?></span>
                <?php endif; ?>
                <h3 class="nb-note-card-title"><?php echo esc_html( get_the_title( $rp->ID ) ); ?></h3>
                <p class="nb-note-card-meta"><?php echo esc_html( $date ); ?> &middot; <?php echo esc_html( $reading ); ?> min read</p>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'nb_more_notes', 'newblood_shortcode_more_notes' );

/**
 * Shortcode: [nb_latest_note]
 * Renders the homepage "Latest from Notes" hook — single card with image left, text right.
 * Outputs nothing when no posts are published (independent guard from NB_NOTES_PUBLIC).
 */
function newblood_shortcode_latest_note() {
    $latest = get_posts( array(
        'numberposts' => 1,
        'post_status' => 'publish',
    ) );

    if ( empty( $latest ) ) {
        return '';
    }

    $post_obj  = $latest[0];
    $primary   = newblood_primary_category( $post_obj->ID );
    $permalink = get_permalink( $post_obj->ID );
    $thumb     = has_post_thumbnail( $post_obj->ID ) ? get_the_post_thumbnail( $post_obj->ID, 'large' ) : '';
    $date      = get_the_date( 'F j, Y', $post_obj->ID );
    $reading   = newblood_reading_time( $post_obj->ID );
    $excerpt   = wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_obj ) ), 36, '…' );

    ob_start();
    ?>
    <div class="wp-block-group alignfull nb-gradient-section nb-latest-note-section" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);">
      <div style="max-width:1100px;margin:0 auto;">
        <div class="nb-latest-note-section-label">
          <span class="nb-label">From Notes<span style="color:#4ade80">.</span></span>
          <a class="nb-view-all" href="<?php echo esc_url( home_url( '/notes/' ) ); ?>">View all &rarr;</a>
        </div>
        <a class="nb-latest-note-card" href="<?php echo esc_url( $permalink ); ?>">
          <div class="nb-latest-note-card-image"><?php echo $thumb; ?></div>
          <div class="nb-latest-note-card-body">
            <?php if ( $primary ) : ?>
              <span class="nb-note-badge"><?php echo esc_html( $primary->name ); ?></span>
            <?php endif; ?>
            <h2 class="nb-latest-note-card-title"><?php echo esc_html( get_the_title( $post_obj->ID ) ); ?></h2>
            <p class="nb-latest-note-card-dek"><?php echo esc_html( $excerpt ); ?></p>
            <p class="nb-latest-note-card-meta"><?php echo esc_html( $date ); ?> &middot; <?php echo esc_html( $reading ); ?> min read</p>
          </div>
        </a>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'nb_latest_note', 'newblood_shortcode_latest_note' );

/**
 * Shortcode: [nb_related_notes]
 * Renders the compact two-card "Related notes" rail at the bottom of each /services/ block.
 * v1: latest 2 published posts overall (no category filter). Per-service category mapping
 * is a follow-up once the real category taxonomy is finalized — spec open item.
 * Returns empty when fewer than 1 post exists.
 */
function newblood_shortcode_related_notes() {
    $related = get_posts( array(
        'numberposts' => 2,
        'post_status' => 'publish',
    ) );

    if ( empty( $related ) ) {
        return '';
    }

    ob_start();
    ?>
    <div style="margin-top:var(--wp--preset--spacing--50);">
      <div class="nb-related-notes">
        <p class="nb-related-notes-label">Related notes<span style="color:#4ade80">.</span></p>
        <?php foreach ( $related as $rp ) :
            $permalink = get_permalink( $rp->ID );
            $thumb     = has_post_thumbnail( $rp->ID ) ? get_the_post_thumbnail( $rp->ID, 'thumbnail' ) : '';
            $date      = get_the_date( 'F Y', $rp->ID );
            $reading   = newblood_reading_time( $rp->ID );
        ?>
          <a class="nb-note-card-mini" href="<?php echo esc_url( $permalink ); ?>">
            <div class="nb-note-card-mini-image"><?php echo $thumb; ?></div>
            <div>
              <h4 class="nb-note-card-mini-title"><?php echo esc_html( get_the_title( $rp->ID ) ); ?></h4>
              <p class="nb-note-card-mini-meta"><?php echo esc_html( $date ); ?> &middot; <?php echo esc_html( $reading ); ?> min read</p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'nb_related_notes', 'newblood_shortcode_related_notes' );
