<?php
require __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'wp_mail' ) ) { function wp_mail( $to, $subj, $body, $headers = array() ) { return true; } }
require dirname( __DIR__, 2 ) . '/inc/discovery/config.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/email.php';

$instance = nb_discovery_get_instance( 'overhead-door' );
$record = array(
    'instance'   => 'overhead-door',
    'respondent' => array( 'name' => 'Chase Cummings', 'email' => 'chase@example.com' ),
    'services'   => array(
        array( 'key' => 'website',  'importance' => 6, 'handling' => 5, 'gap' => 1 ),
        array( 'key' => 'lead_gen', 'importance' => 10, 'handling' => 2, 'gap' => 8 ),
        array( 'key' => 'content',  'importance' => 4, 'handling' => null, 'gap' => null ),
    ),
    'vision'       => 'Own the commercial market.',
    'goal_vectors' => array( 'residential_commercial' => 30, 'leads_volume_quality' => -10, 'topline_lean' => 0, 'defend_expand' => 25, 'handson_managed' => 40 ),
    'systems'      => array( 'crm' => 'None', 'lead_handling' => 'Email to office', 'reviews_system' => 'Google', 'call_tracking' => 'Enspire', 'gbp_access' => 'yes', 'territories' => 'Baltimore metro' ),
    'posture'      => array( 'fix_invest' => 35, 'timeline' => 'Within 1-3 months' ),
    'open'         => 'Looking forward to it.',
);
$mail = nb_discovery_format_email( $record, $instance );

assert( strpos( $mail['subject'], 'Overhead Door Company of Baltimore' ) !== false, 'subject has client' );
assert( strpos( $mail['subject'], 'Chase Cummings' ) !== false, 'subject has respondent' );
// Highest-gap service (lead_gen, gap 8) must appear before the lower-gap one (website).
$posLead = strpos( $mail['body'], 'Lead generation' );
$posWeb  = strpos( $mail['body'], 'Website design' );
assert( $posLead !== false && $posWeb !== false && $posLead < $posWeb, 'gap-descending order' );
assert( strpos( $mail['body'], 'Own the commercial market.' ) !== false, 'vision included' );
assert( strpos( $mail['body'], 'Enspire' ) !== false, 'systems included' );
assert( strpos( $mail['subject'], '—' ) !== false, 'subject uses em dash' );
$posContent = strpos( $mail['body'], 'Content' );
assert( $posContent !== false && $posContent > $posWeb, 'null-gap service sinks below rated services' );

// Systems lines must use the config 'short' labels.
assert( strpos( $mail['body'], 'CRM today: None' ) !== false, 'systems line uses short label' );
assert( strpos( $mail['body'], 'Google Business Profile access: yes' ) !== false, 'gbp line uses short label' );

echo "test-email: PASS\n";
