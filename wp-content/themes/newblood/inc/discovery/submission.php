<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'NB_DISCOVERY_THRESHOLD' ) ) define( 'NB_DISCOVERY_THRESHOLD', 7 );

function nb_discovery_clamp( $n, $min, $max ) {
    $n = (int) $n;
    if ( $n < $min ) return $min;
    if ( $n > $max ) return $max;
    return $n;
}

/**
 * Validate + sanitize a raw decoded payload against an instance config.
 */
function nb_discovery_sanitize_payload( $raw, $instance ) {
    $valid_keys = array_column( $instance['services'], 'key' );

    $name  = isset( $raw['respondent']['name'] ) ? sanitize_text_field( $raw['respondent']['name'] ) : '';
    $email = isset( $raw['respondent']['email'] ) ? sanitize_email( $raw['respondent']['email'] ) : '';

    $services = array();
    if ( ! empty( $raw['services'] ) && is_array( $raw['services'] ) ) {
        foreach ( $raw['services'] as $s ) {
            if ( empty( $s['key'] ) || ! in_array( $s['key'], $valid_keys, true ) ) continue;
            $imp = nb_discovery_clamp( isset( $s['importance'] ) ? $s['importance'] : 0, 0, 10 );
            $handling = null;
            if ( $imp >= NB_DISCOVERY_THRESHOLD && isset( $s['handling'] ) && $s['handling'] !== null && $s['handling'] !== '' ) {
                $handling = nb_discovery_clamp( $s['handling'], 0, 10 );
            }
            $services[] = array( 'key' => $s['key'], 'importance' => $imp, 'handling' => $handling );
        }
    }

    $vec = function ( $key ) use ( $raw ) {
        return isset( $raw['goal_vectors'][ $key ] ) ? nb_discovery_clamp( $raw['goal_vectors'][ $key ], -50, 50 ) : 0;
    };
    $gbp = isset( $raw['systems']['gbp_access'] ) ? $raw['systems']['gbp_access'] : 'unsure';
    if ( ! in_array( $gbp, array( 'yes', 'no', 'unsure' ), true ) ) $gbp = 'unsure';

    $txt  = function ( $v ) { return sanitize_text_field( (string) $v ); };
    $area = function ( $v ) { return sanitize_textarea_field( (string) $v ); };

    return array(
        'instance'   => $instance['slug'],
        'respondent' => array( 'name' => $name, 'email' => $email ),
        'services'   => $services,
        'vision'     => $area( isset( $raw['vision'] ) ? $raw['vision'] : '' ),
        'goal_vectors' => array(
            'residential_commercial' => $vec( 'residential_commercial' ),
            'leads_volume_quality'   => $vec( 'leads_volume_quality' ),
            'topline_lean'           => $vec( 'topline_lean' ),
            'defend_expand'          => $vec( 'defend_expand' ),
            'handson_managed'        => $vec( 'handson_managed' ),
        ),
        'systems' => array(
            'crm'            => $txt( isset( $raw['systems']['crm'] ) ? $raw['systems']['crm'] : '' ),
            'lead_handling'  => $area( isset( $raw['systems']['lead_handling'] ) ? $raw['systems']['lead_handling'] : '' ),
            'reviews_system' => $txt( isset( $raw['systems']['reviews_system'] ) ? $raw['systems']['reviews_system'] : '' ),
            'call_tracking'  => $txt( isset( $raw['systems']['call_tracking'] ) ? $raw['systems']['call_tracking'] : '' ),
            'gbp_access'     => $gbp,
            'territories'    => $area( isset( $raw['systems']['territories'] ) ? $raw['systems']['territories'] : '' ),
        ),
        'posture' => array(
            'fix_invest' => isset( $raw['posture']['fix_invest'] ) ? nb_discovery_clamp( $raw['posture']['fix_invest'], -50, 50 ) : 0,
            'timeline'   => $txt( isset( $raw['posture']['timeline'] ) ? $raw['posture']['timeline'] : '' ),
        ),
        'open' => $area( isset( $raw['open'] ) ? $raw['open'] : '' ),
    );
}

/**
 * Add server-computed gap scores (importance - handling) to each service.
 */
function nb_discovery_compute_gaps( $services ) {
    foreach ( $services as &$s ) {
        if ( isset( $s['importance'] ) && isset( $s['handling'] ) && $s['handling'] !== null ) {
            $s['gap'] = (int) $s['importance'] - (int) $s['handling'];
        } else {
            $s['gap'] = null;
        }
    }
    unset( $s );
    return $services;
}

/**
 * REST: receive a submission, store it, email the summary.
 */
function nb_discovery_handle_submit( $req ) {
    $raw  = $req->get_json_params();
    $slug = isset( $raw['instance'] ) ? sanitize_title( $raw['instance'] ) : '';
    $instance = nb_discovery_get_instance( $slug );
    if ( ! $instance ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'unknown_instance' ), 400 );
    }

    $clean = nb_discovery_sanitize_payload( $raw, $instance );
    if ( ! is_email( $clean['respondent']['email'] ) || $clean['respondent']['name'] === '' ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'missing_identity' ), 422 );
    }
    $clean['services'] = nb_discovery_compute_gaps( $clean['services'] );

    global $wpdb;
    $inserted = $wpdb->insert(
        nb_discovery_table_name(),
        array(
            'instance'         => $clean['instance'],
            'respondent_name'  => $clean['respondent']['name'],
            'respondent_email' => $clean['respondent']['email'],
            'payload'          => wp_json_encode( $clean ),
            'created_at'       => current_time( 'mysql' ),
            'ip'               => isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( $_SERVER['REMOTE_ADDR'] ) : '',
        ),
        array( '%s', '%s', '%s', '%s', '%s', '%s' )
    );
    if ( false === $inserted ) {
        return new WP_REST_Response( array( 'ok' => false, 'error' => 'db_error' ), 500 );
    }

    if ( function_exists( 'nb_discovery_send_email' ) ) {
        nb_discovery_send_email( $clean, $instance );
    }

    return new WP_REST_Response( array( 'ok' => true ), 200 );
}

function nb_discovery_register_rest() {
    register_rest_route( 'newblood/v1', '/discovery', array(
        'methods'             => 'POST',
        'callback'            => 'nb_discovery_handle_submit',
        'permission_callback' => function () {
            // Public discovery form — no auth required.
            return true;
        },
    ) );
}
add_action( 'rest_api_init', 'nb_discovery_register_rest' );
