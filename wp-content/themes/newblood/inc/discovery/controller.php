<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Intercept /discovery/{slug}. Valid slug → standalone page. Unknown slug → 404.
 */
function nb_discovery_template_redirect() {
    $slug = get_query_var( 'nb_discovery' );
    if ( ! $slug ) {
        return; // not our route
    }
    $instance = nb_discovery_get_instance( sanitize_title( $slug ) );
    if ( ! $instance ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        return;
    }
    if ( get_query_var( 'nb_discovery_report' ) ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            global $wp_query;
            $wp_query->set_404();
            status_header( 404 );
            return; // do not reveal the report to non-admins
        }
        if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
        nocache_headers();
        nb_discovery_output_report( $instance );
        exit;
    }
    nb_discovery_render_page( $instance );
    exit;
}
add_action( 'template_redirect', 'nb_discovery_template_redirect' );

/**
 * Toggle a submission's excluded flag (admin-only, nonce-checked). Non-destructive.
 */
function nb_discovery_handle_exclude() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Not allowed', '', array( 'response' => 403 ) );
    }
    check_admin_referer( 'nb_discovery_exclude' );

    $id       = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
    $excluded = ( isset( $_POST['excluded'] ) && $_POST['excluded'] === '1' ) ? 1 : 0;
    $instance = isset( $_POST['instance'] ) ? sanitize_title( wp_unslash( $_POST['instance'] ) ) : '';

    if ( $id && nb_discovery_get_instance( $instance ) ) {
        global $wpdb;
        $wpdb->update(
            nb_discovery_table_name(),
            array( 'excluded' => $excluded ),
            array( 'id' => $id, 'instance' => $instance ),
            array( '%d' ),
            array( '%d', '%s' )
        );
    }

    $redirect = nb_discovery_get_instance( $instance ) ? home_url( '/discovery/' . $instance . '/report' ) : home_url( '/' );
    wp_safe_redirect( $redirect );
    exit;
}
add_action( 'admin_post_nb_discovery_exclude', 'nb_discovery_handle_exclude' );
