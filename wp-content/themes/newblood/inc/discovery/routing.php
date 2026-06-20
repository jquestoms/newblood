<?php
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NB_DISCOVERY_REWRITE_VERSION', '2' );

function nb_discovery_register_query_var( $vars ) {
    $vars[] = 'nb_discovery';
    $vars[] = 'nb_discovery_report';
    return $vars;
}
add_filter( 'query_vars', 'nb_discovery_register_query_var' );

function nb_discovery_register_rewrite() {
    add_rewrite_rule( '^discovery/([^/]+)/report/?$', 'index.php?nb_discovery=$matches[1]&nb_discovery_report=1', 'top' );
    add_rewrite_rule( '^discovery/([^/]+)/?$', 'index.php?nb_discovery=$matches[1]', 'top' );
}
add_action( 'init', 'nb_discovery_register_rewrite' );

/**
 * Flush rewrites once per rewrite-rule version change (themes have no
 * activation hook, so we self-heal on the next request after a deploy).
 */
function nb_discovery_maybe_flush_rewrites() {
    if ( get_option( 'nb_discovery_rewrite_version' ) !== NB_DISCOVERY_REWRITE_VERSION ) {
        nb_discovery_register_rewrite();
        flush_rewrite_rules( false );
        update_option( 'nb_discovery_rewrite_version', NB_DISCOVERY_REWRITE_VERSION );
    }
}
add_action( 'init', 'nb_discovery_maybe_flush_rewrites', 20 );
