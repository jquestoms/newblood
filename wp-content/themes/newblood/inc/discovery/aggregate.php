<?php
if ( ! defined( 'ABSPATH' ) ) exit; // bootstrap.php defines ABSPATH for the CLI tests, so this is safe under both WP and the harness
if ( ! defined( 'NB_DISCOVERY_SPLIT_THRESHOLD' ) )        define( 'NB_DISCOVERY_SPLIT_THRESHOLD', 4 );
if ( ! defined( 'NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD' ) ) define( 'NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD', 40 );

/**
 * Aggregate active discovery submissions into one combined summary.
 *
 * @param array $submissions Active records: [ {id, name, email, payload(decoded array)} ]
 * @param array $instance     Instance config from nb_discovery_get_instance().
 * @return array              Canonical aggregate shape (see plan File Structure).
 */
function nb_discovery_aggregate( $submissions, $instance ) {
    $count = count( $submissions );

    $respondents = array();
    foreach ( $submissions as $sub ) {
        $respondents[] = array( 'id' => $sub['id'], 'name' => $sub['name'], 'email' => $sub['email'] );
    }

    // ---- Services ----
    $services = array();
    foreach ( $instance['services'] as $svc ) {
        $key  = $svc['key'];
        $imps = array();
        $hand = array();
        $per  = array();
        foreach ( $submissions as $sub ) {
            $imp = null; $h = null;
            if ( ! empty( $sub['payload']['services'] ) ) {
                foreach ( $sub['payload']['services'] as $ps ) {
                    if ( isset( $ps['key'] ) && $ps['key'] === $key ) {
                        $imp = (int) $ps['importance'];
                        $h   = ( isset( $ps['handling'] ) && $ps['handling'] !== null ) ? (int) $ps['handling'] : null;
                        break;
                    }
                }
            }
            if ( $imp === null ) { continue; } // submission has no data for this service
            $imps[] = $imp;
            if ( $h !== null ) { $hand[] = $h; }
            $per[] = array( 'name' => $sub['name'], 'importance' => $imp, 'handling' => $h );
        }
        if ( empty( $imps ) ) { continue; } // no respondent covered this service

        $mean_importance = round( array_sum( $imps ) / count( $imps ), 1 );
        $mean_handling   = count( $hand ) ? round( array_sum( $hand ) / count( $hand ), 1 ) : null;
        $mean_gap        = ( $mean_handling !== null ) ? round( $mean_importance - $mean_handling, 1 ) : null;
        $importance_spread = max( $imps ) - min( $imps );
        $handling_spread   = ( count( $hand ) >= 2 ) ? max( $hand ) - min( $hand ) : null;

        $high = null; $low = null;
        foreach ( $per as $p ) {
            if ( $high === null || $p['importance'] > $high['score'] ) { $high = array( 'name' => $p['name'], 'score' => $p['importance'] ); }
            if ( $low === null  || $p['importance'] < $low['score'] )  { $low  = array( 'name' => $p['name'], 'score' => $p['importance'] ); }
        }

        $services[] = array(
            'key' => $key,
            'label' => isset( $svc['label'] ) ? $svc['label'] : $key,
            'mean_importance' => $mean_importance,
            'mean_handling'   => $mean_handling,
            'mean_gap'        => $mean_gap,
            'importance_spread' => $importance_spread,
            'handling_spread'   => $handling_spread,
            'split' => $importance_spread >= NB_DISCOVERY_SPLIT_THRESHOLD,
            'high' => $high,
            'low'  => $low,
            'per_respondent' => $per,
        );
    }
    // Rank by mean_gap desc; null gaps (service not rated by anyone) sink to the bottom; tie-break mean_importance desc.
    usort( $services, function ( $a, $b ) {
        $an = $a['mean_gap'] === null;
        $bn = $b['mean_gap'] === null;
        if ( $an && $bn ) { return $b['mean_importance'] <=> $a['mean_importance']; }
        if ( $an ) { return 1; }
        if ( $bn ) { return -1; }
        if ( $a['mean_gap'] === $b['mean_gap'] ) { return $b['mean_importance'] <=> $a['mean_importance']; }
        return $b['mean_gap'] <=> $a['mean_gap'];
    } );

    // ---- Goal vectors ----
    $goal_vectors = array();
    foreach ( $instance['goal_vectors'] as $v ) {
        $key = $v['key'];
        $pos = array();
        $per = array();
        foreach ( $submissions as $sub ) {
            $p = isset( $sub['payload']['goal_vectors'][ $key ] ) ? (int) $sub['payload']['goal_vectors'][ $key ] : 0;
            $pos[] = $p;
            $per[] = array( 'name' => $sub['name'], 'position' => $p );
        }
        $goal_vectors[] = array(
            'key' => $key, 'left' => $v['left'], 'right' => $v['right'],
            'mean'   => $count ? round( array_sum( $pos ) / $count, 1 ) : 0,
            'spread' => $count ? max( $pos ) - min( $pos ) : 0,
            'split'  => $count ? ( ( max( $pos ) - min( $pos ) ) >= NB_DISCOVERY_VECTOR_SPLIT_THRESHOLD ) : false,
            'per_respondent' => $per,
        );
    }

    // ---- Posture ----
    $fix = array(); $fix_per = array(); $timelines = array();
    foreach ( $submissions as $sub ) {
        $f = isset( $sub['payload']['posture']['fix_invest'] ) ? (int) $sub['payload']['posture']['fix_invest'] : 0;
        $fix[] = $f;
        $fix_per[] = array( 'name' => $sub['name'], 'position' => $f );
        $timelines[] = array( 'name' => $sub['name'], 'timeline' => isset( $sub['payload']['posture']['timeline'] ) ? $sub['payload']['posture']['timeline'] : '' );
    }
    $posture = array(
        'fix_invest' => array(
            'mean'   => $count ? round( array_sum( $fix ) / $count, 1 ) : 0,
            'spread' => $count ? max( $fix ) - min( $fix ) : 0,
            'per_respondent' => $fix_per,
        ),
        'timelines' => $timelines,
    );

    // ---- Qualitative (verbatim per respondent) ----
    $qual_fields = array( 'vision', 'open' );
    $sys_fields  = array( 'crm', 'leads_per_month', 'lead_handling', 'reviews_system', 'call_tracking', 'territories', 'gbp_access' );
    $qualitative = array();
    foreach ( array_merge( $qual_fields, $sys_fields ) as $f ) { $qualitative[ $f ] = array(); }
    foreach ( $submissions as $sub ) {
        foreach ( $qual_fields as $f ) {
            $qualitative[ $f ][] = array( 'name' => $sub['name'], 'value' => isset( $sub['payload'][ $f ] ) ? $sub['payload'][ $f ] : '' );
        }
        foreach ( $sys_fields as $f ) {
            $qualitative[ $f ][] = array( 'name' => $sub['name'], 'value' => isset( $sub['payload']['systems'][ $f ] ) ? $sub['payload']['systems'][ $f ] : '' );
        }
    }

    return array(
        'count' => $count,
        'respondents' => $respondents,
        'services' => $services,
        'goal_vectors' => $goal_vectors,
        'posture' => $posture,
        'qualitative' => $qualitative,
    );
}
