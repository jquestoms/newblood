<?php
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/config.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/submission.php';

$instance = nb_discovery_get_instance( 'overhead-door' );
$raw = array(
    'instance'   => 'overhead-door',
    'respondent' => array( 'name' => '  Chase <b>C</b> ', 'email' => 'chase@example.com ' ),
    'services'   => array(
        array( 'key' => 'website', 'importance' => 99, 'handling' => 4 ),     // imp clamps to 10, keeps handling
        array( 'key' => 'content', 'importance' => 3,  'handling' => 8 ),     // below threshold → handling null
        array( 'key' => 'bogus_key', 'importance' => 5 ),                      // dropped
    ),
    'vision'       => "  Big <script>x</script>growth  ",
    'goal_vectors' => array( 'defend_expand' => 999, 'topline_lean' => -999 ),
    'systems'      => array( 'gbp_access' => 'maybe', 'crm' => 'HubSpot', 'leads_per_month' => '  about 5 a month <b>maybe</b> ' ),
    'posture'      => array( 'fix_invest' => 12, 'timeline' => 'Within 1–3 months' ),
    'open'         => 'thanks',
);
$clean = nb_discovery_sanitize_payload( $raw, $instance );

assert( $clean['respondent']['name'] === 'Chase C', 'name stripped of tags/space' );
assert( $clean['respondent']['email'] === 'chase@example.com', 'email sanitized' );
assert( count( $clean['services'] ) === 2, 'bogus key dropped' );
$byKey = array();
foreach ( $clean['services'] as $s ) { $byKey[ $s['key'] ] = $s; }
assert( $byKey['website']['importance'] === 10, 'importance clamped to 10' );
assert( $byKey['website']['handling'] === 4, 'handling kept when above threshold' );
assert( $byKey['content']['handling'] === null, 'handling nulled below threshold' );
assert( strpos( $clean['vision'], 'script' ) === false, 'vision stripped of tags' );
assert( $clean['goal_vectors']['defend_expand'] === 50, 'vector clamped high' );
assert( $clean['goal_vectors']['topline_lean'] === -50, 'vector clamped low' );
assert( $clean['systems']['gbp_access'] === 'unsure', 'invalid gbp coerced to unsure' );
assert( $clean['posture']['fix_invest'] === 12, 'posture vector kept' );
assert( $clean['systems']['leads_per_month'] === 'about 5 a month maybe', 'leads_per_month sanitized + passed through' );
echo "test-sanitize: PASS\n";
