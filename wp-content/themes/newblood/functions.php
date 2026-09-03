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

// Discovery form module (self-serve client intake).
require_once get_template_directory() . '/inc/discovery/index.php';

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
    // Commerce surfaces only — keeps WooCommerce overrides off the ~95% of
    // pages that aren't commerce, and out of the global cascade.
    if ( function_exists( 'is_woocommerce' )
        && ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) ) {
        wp_enqueue_style(
            'newblood-woocommerce',
            get_template_directory_uri() . '/assets/css/woocommerce.css',
            array( 'newblood-utilities' ),
            newblood_asset_version( '/assets/css/woocommerce.css' )
        );
    }
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
 * Process shortcodes inside core/shortcode blocks during block rendering.
 *
 * WP core's render_block_core_shortcode only calls wpautop(); shortcode expansion
 * normally happens later via the `the_content` filter. When patterns/templates
 * are rendered server-side (outside post content), `the_content` doesn't run, so
 * shortcodes embedded in wp:shortcode blocks render as literal `[name]` text.
 * This filter intercepts the rendered block content for core/shortcode and runs
 * do_shortcode on it, restoring expected behavior in any context.
 */
function newblood_process_shortcodes_in_blocks( $block_content, $block ) {
    if ( isset( $block['blockName'] ) && $block['blockName'] === 'core/shortcode' ) {
        // wpautop has already wrapped the bare shortcode in <p> tags.
        // shortcode_unautop strips those so block-level expansion isn't trapped
        // inside an invalid <p> wrapper.
        return do_shortcode( shortcode_unautop( $block_content ) );
    }
    return $block_content;
}
add_filter( 'render_block', 'newblood_process_shortcodes_in_blocks', 10, 2 );

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
 * Shortcode: [nb_contact_form]
 * Renders the WPForms contact form resolved by TITLE (not a hardcoded id), so it
 * stays correct across environments — WPForms post ids differ between local and
 * prod. The form's title is the binding contract; change it in one place below.
 * If the form can't be found, degrades gracefully to a mailto link rather than
 * silently dropping the page's contact path.
 */
if ( ! defined( 'NB_CONTACT_FORM_TITLE' ) ) {
    define( 'NB_CONTACT_FORM_TITLE', 'Contact Form' );
}

function newblood_shortcode_contact_form() {
    $forms = get_posts( array(
        'post_type'   => 'wpforms',
        'title'       => NB_CONTACT_FORM_TITLE,
        'post_status' => 'publish',
        'numberposts' => 1,
        'fields'      => 'ids',
    ) );
    if ( ! empty( $forms ) ) {
        return newblood_contact_subject_lead() . do_shortcode( '[wpforms id="' . (int) $forms[0] . '"]' ) . newblood_contact_subject_script();
    }

    // Fallback: never silently lose the contact path if the form is missing.
    $email = sanitize_email( get_option( 'admin_email' ) );
    if ( ! $email ) {
        return '';
    }
    return '<p class="nb-contact-fallback" style="font-size:1rem">Reach us directly at <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>.</p>';
}
add_shortcode( 'nb_contact_form', 'newblood_shortcode_contact_form' );

/**
 * Contact-form subject preselect. Offer pages link to /contact/?subject=<key>;
 * a known key adds a lead-in above the form and pre-fills the message field.
 * Theme-owned on purpose: the WPForms config lives in the DB and differs
 * between local and prod, so nothing here depends on field ids or form settings.
 */
function newblood_contact_subjects() {
    return array(
        'four-gaps-audit' => array(
            'lead'    => 'You are asking about the <strong>Four Gaps Audit</strong>. Tell us a little about the business and which systems you run, and we will set up the short call.',
            'message' => "I'd like to talk about the Four Gaps Audit.",
        ),
    );
}

function newblood_contact_current_subject() {
    if ( empty( $_GET['subject'] ) ) {
        return null;
    }
    $key      = sanitize_key( wp_unslash( $_GET['subject'] ) );
    $subjects = newblood_contact_subjects();
    return isset( $subjects[ $key ] ) ? $subjects[ $key ] : null;
}

function newblood_contact_subject_lead() {
    $subject = newblood_contact_current_subject();
    if ( ! $subject ) {
        return '';
    }
    return '<p class="nb-contact-lead">' . wp_kses( $subject['lead'], array( 'strong' => array() ) ) . '</p>';
}

function newblood_contact_subject_script() {
    $subject = newblood_contact_current_subject();
    if ( ! $subject ) {
        return '';
    }
    $message = wp_json_encode( $subject['message'] );
    return '<script>(function(){var t=document.querySelector(".wpforms-form textarea");if(t&&!t.value){t.value=' . $message . ';}})();</script>';
}

/**
 * Legacy URL redirects (301).
 *
 * Yoast Free has no redirect manager, so retired classic-theme URLs are mapped
 * to their new homes here. Keys are the request path with no leading/trailing
 * slashes; values are the destination path. Add a line per URL as needed.
 *
 * Intentionally NOT redirecting orphaned, off-topic legacy pages (old soccer/
 * photography galleries, old blog posts) — sending unrelated URLs to the
 * homepage reads as a soft-404 to search engines. A clean 404 is better.
 */
function newblood_legacy_redirects() {
    if ( is_admin() ) {
        return;
    }
    $path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
    if ( '' === $path ) {
        return;
    }

    $redirects = array(
        'case-studies'                     => '/work/',
        // iOS/browser blind requests for root icons. The real
        // <link rel="apple-touch-icon"> tag (site-icon crop) covers clients
        // that read the HTML; these cover the ones that don't.
        'apple-touch-icon.png'             => '/wp-content/uploads/2016/04/cropped-favicon-180x180.png',
        'apple-touch-icon-precomposed.png' => '/wp-content/uploads/2016/04/cropped-favicon-180x180.png',
    );
    if ( isset( $redirects[ $path ] ) ) {
        wp_safe_redirect( home_url( $redirects[ $path ] ), 301 );
        exit;
    }

    // Prefix matches — old indexed URLs arrive with suffix/no-slash variants.
    $prefix_redirects = array(
        'seo-guest-lecture-at-bis-in-pasadena-ca' => '/about/',
    );
    foreach ( $prefix_redirects as $prefix => $dest ) {
        if ( 0 === strpos( $path, $prefix ) ) {
            wp_safe_redirect( home_url( $dest ), 301 );
            exit;
        }
    }

    // The old site lived under /blog/ — its media is still hotlinked with
    // that prefix. The upload files themselves were migrated, so map the
    // whole class onto the current uploads tree (404s stay 404s either way).
    if ( 0 === strpos( $path, 'blog/wp-content/uploads/' ) ) {
        wp_safe_redirect( home_url( '/' . substr( $path, strlen( 'blog/' ) ) ), 301 );
        exit;
    }

    // /notes/ is the future Notes index (launch-gated via NB_NOTES_PUBLIC).
    // Temporary redirect only: a cached 301 would keep sending repeat
    // visitors to the homepage after the section launches at this URL.
    // Drops out automatically when the gate flips.
    if ( 'notes' === $path && ! NB_NOTES_PUBLIC ) {
        wp_safe_redirect( home_url( '/' ), 302 );
        exit;
    }
}
// Priority 1: must beat core's redirect_canonical (priority 10), which
// mis-canonicalizes the legacy /blog/wp-content/uploads/* paths to the
// homepage before these rules would otherwise run.
add_action( 'template_redirect', 'newblood_legacy_redirects', 1 );

/**
 * XML-RPC lockdown.
 *
 * Nothing uses XML-RPC here (no Jetpack, no mobile apps) and bots probe it
 * tens of thousands of times a month. Refuse the request at theme load,
 * before any XML-RPC method can execute, and disable the API for anything
 * that slips through. Nexcess Managed WordPress is nginx-only (.htaccess is
 * not honored), so a true pre-PHP deny needs an nginx rule via host support.
 */
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
    status_header( 403 );
    nocache_headers();
    exit( 'XML-RPC services are disabled on this site.' );
}
add_filter( 'xmlrpc_enabled', '__return_false' );

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
