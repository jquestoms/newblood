<?php
/**
 * Plugin Name: New Blood QBO Callback Relay
 * Description: Relays the Intuit OAuth callback for New Blood's internal QuickBooks tool (qbo-cli) from https://newblood.com/qbo-callback/ to the tool listening on localhost. Intuit requires an https redirect URI served from a web server for production apps; the tool itself runs on Jeremy's Mac.
 * Author: New Blood
 * Version: 1.0.0
 *
 * Passes through only the three OAuth parameters (code, state, realmId). The code is
 * single-use and short-lived, and the tool rejects any callback whose state does not
 * match the one it generated, so a stray visit to this URL cannot connect anything.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', function () {
    $uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path = (string) parse_url( $uri, PHP_URL_PATH );
    if ( rtrim( $path, '/' ) !== '/qbo-callback' ) {
        return;
    }

    nocache_headers();
    header( 'X-Robots-Tag: noindex, nofollow', true );

    $params = array();
    foreach ( array( 'code', 'state', 'realmId' ) as $key ) {
        if ( isset( $_GET[ $key ] ) && is_string( $_GET[ $key ] ) && $_GET[ $key ] !== '' ) {
            $params[ $key ] = $_GET[ $key ];
        }
    }

    if ( count( $params ) !== 3 ) {
        status_header( 400 );
        header( 'Content-Type: text/plain; charset=utf-8' );
        echo "This address only relays a QuickBooks connection for New Blood's internal tools. Nothing to see here.\n";
        exit;
    }

    $target = 'http://localhost:8123/callback?' . http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );
    header( 'Location: ' . $target, true, 302 );
    exit;
}, 0 );
