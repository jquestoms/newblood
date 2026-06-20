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
        nb_discovery_output_report( $instance );
        exit;
    }
    nb_discovery_render_page( $instance );
    exit;
}
add_action( 'template_redirect', 'nb_discovery_template_redirect' );
