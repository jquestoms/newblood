<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Build the plain-text summary email. Gap map leads (largest gap first).
 */
function nb_discovery_format_email( $record, $instance ) {
    $labels = array();
    foreach ( $instance['services'] as $s ) { $labels[ $s['key'] ] = $s['label']; }
    $vlabels = array();
    foreach ( $instance['goal_vectors'] as $v ) { $vlabels[ $v['key'] ] = $v['left'] . ' ↔ ' . $v['right']; }

    $subject = 'New Blood Discovery — ' . $instance['client_name'] . ' (' . $record['respondent']['name'] . ')';

    // Sort services by gap desc; null gaps sink to the bottom.
    $services = $record['services'];
    usort( $services, function ( $a, $b ) {
        // Null gaps (service not rated) use a -100 sentinel to sort below all real gaps.
        $ga = is_null( $a['gap'] ) ? -100 : $a['gap'];
        $gb = is_null( $b['gap'] ) ? -100 : $b['gap'];
        if ( $ga === $gb ) return $b['importance'] - $a['importance'];
        return $gb - $ga;
    } );

    $lines = array();
    $lines[] = 'Respondent: ' . $record['respondent']['name'] . ' <' . $record['respondent']['email'] . '>';
    $lines[] = 'Client: ' . $instance['client_name'];
    $lines[] = '';
    $lines[] = '== PRIORITY / GAP MAP (importance · handled today · gap) ==';
    foreach ( $services as $s ) {
        $label = isset( $labels[ $s['key'] ] ) ? $labels[ $s['key'] ] : $s['key'];
        if ( is_null( $s['handling'] ) ) {
            $lines[] = sprintf( '%-44s imp %2d · (not rated — below priority threshold)', $label, $s['importance'] );
        } else {
            $lines[] = sprintf( '%-44s imp %2d · now %2d · GAP %2d', $label, $s['importance'], $s['handling'], $s['gap'] );
        }
    }
    $lines[] = '';
    $lines[] = '== 3-YEAR VISION ==';
    $lines[] = ! empty( $record['vision'] ) ? $record['vision'] : '(blank)';
    $lines[] = '';
    $lines[] = '== DIRECTION (−50 … +50) ==';
    foreach ( $record['goal_vectors'] as $k => $val ) {
        $lab = isset( $vlabels[ $k ] ) ? $vlabels[ $k ] : $k;
        $lines[] = sprintf( '%-44s %+d', $lab, $val );
    }
    $fix_label = "Fix what\u{2019}s urgent \u{2194} Invest long-term";
    $lines[] = sprintf( '%-44s %+d', $fix_label, $record['posture']['fix_invest'] ?? 0 );
    $lines[] = 'Timeline: ' . ( ! empty( $record['posture']['timeline'] ) ? $record['posture']['timeline'] : '(blank)' );
    $lines[] = '';
    $lines[] = '== SYSTEMS TODAY ==';
    foreach ( $instance['systems_questions'] as $q ) {
        $val = isset( $record['systems'][ $q['key'] ] ) ? $record['systems'][ $q['key'] ] : '';
        $lines[] = $q['short'] . ': ' . $val;
    }
    $lines[] = '';
    $lines[] = '== ANYTHING ELSE ==';
    $lines[] = ! empty( $record['open'] ) ? $record['open'] : '(blank)';

    return array( 'subject' => $subject, 'body' => implode( "\n", $lines ) );
}

function nb_discovery_send_email( $record, $instance ) {
    $mail = nb_discovery_format_email( $record, $instance );
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $record['respondent']['name'] . ' <' . $record['respondent']['email'] . '>',
    );
    return wp_mail( $instance['recipient'], $mail['subject'], $mail['body'], $headers );
}
