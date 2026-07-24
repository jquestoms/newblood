<?php
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/config.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/aggregate.php';

$instance = nb_discovery_get_instance( 'overhead-door' );

// Two stakeholders. Only the services we assert on are included in payloads;
// the engine simply has no data for the others and omits them.
$chase = array( 'id' => 1, 'name' => 'Chase', 'email' => 'chase@x.com', 'payload' => array(
    'services' => array(
        array( 'key' => 'website',  'importance' => 9,  'handling' => 4 ),
        array( 'key' => 'lead_gen', 'importance' => 10, 'handling' => 2 ),
        array( 'key' => 'content',  'importance' => 3,  'handling' => null ),
    ),
    'goal_vectors' => array( 'defend_expand' => 30 ),
    'posture' => array( 'fix_invest' => 40, 'timeline' => 'ASAP' ),
    'vision' => 'Own commercial.', 'open' => '',
) );
$paul = array( 'id' => 2, 'name' => 'Paul', 'email' => 'paul@x.com', 'payload' => array(
    'services' => array(
        array( 'key' => 'website',  'importance' => 9, 'handling' => 2 ),
        array( 'key' => 'lead_gen', 'importance' => 4, 'handling' => null ),
        array( 'key' => 'content',  'importance' => 2, 'handling' => null ),
    ),
    'goal_vectors' => array( 'defend_expand' => -20 ),
    'posture' => array( 'fix_invest' => 0, 'timeline' => '3-6 months' ),
    'vision' => 'Steady.', 'open' => '',
) );

$agg = nb_discovery_aggregate( array( $chase, $paul ), $instance );

assert( $agg['count'] === 2, 'count 2' );
assert( count( $agg['respondents'] ) === 2 && $agg['respondents'][0]['id'] === 1, 'roster carries id' );

// Ranking: website (gap 9-3=6) before lead_gen (gap 7-2=5); content (null gap) last.
$keys = array_column( $agg['services'], 'key' );
assert( $keys[0] === 'website', 'website ranks first by gap' );
assert( $keys[1] === 'lead_gen', 'lead_gen second' );
assert( end( $keys ) === 'content', 'null-gap content sinks last' );

$byKey = array();
foreach ( $agg['services'] as $s ) { $byKey[ $s['key'] ] = $s; }

// website: imp [9,9] mean 9; handling [4,2] mean 3; gap 6; spread 0; not split.
assert( $byKey['website']['mean_importance'] === 9.0, 'website mean imp 9' );
assert( $byKey['website']['mean_handling'] === 3.0, 'website mean handling 3' );
assert( $byKey['website']['mean_gap'] === 6.0, 'website mean gap 6' );
assert( $byKey['website']['importance_spread'] === 0, 'website spread 0' );
assert( $byKey['website']['split'] === false, 'website not split' );

// lead_gen: imp [10,4] mean 7; handling only Chase rated [2] mean 2; gap 5; spread 6 -> split.
assert( $byKey['lead_gen']['mean_importance'] === 7.0, 'lead_gen mean imp 7' );
assert( $byKey['lead_gen']['mean_handling'] === 2.0, 'lead_gen mean handling over raters' );
assert( $byKey['lead_gen']['mean_gap'] === 5.0, 'lead_gen gap 5' );
assert( $byKey['lead_gen']['importance_spread'] === 6, 'lead_gen spread 6' );
assert( $byKey['lead_gen']['split'] === true, 'lead_gen split at spread>=4' );
assert( $byKey['lead_gen']['high']['name'] === 'Chase' && $byKey['lead_gen']['high']['score'] === 10, 'high Chase 10' );
assert( $byKey['lead_gen']['low']['name'] === 'Paul' && $byKey['lead_gen']['low']['score'] === 4, 'low Paul 4' );

// content: nobody rated handling -> mean_handling null, mean_gap null.
assert( $byKey['content']['mean_handling'] === null, 'content mean handling null' );
assert( $byKey['content']['mean_gap'] === null, 'content mean gap null' );

// per_respondent carries null handling for a non-rater (Paul didn't rate lead_gen handling).
$lead_pr = $byKey['lead_gen']['per_respondent'];
$paul_lead = null;
foreach ( $lead_pr as $r ) { if ( $r['name'] === 'Paul' ) { $paul_lead = $r; } }
assert( $paul_lead !== null && $paul_lead['handling'] === null, 'Paul lead_gen per_respondent handling is null' );
// handling_spread over raters: website handling [4,2] -> spread 2.
assert( $byKey['website']['handling_spread'] === 2, 'website handling_spread is 2' );

// goal vector defend_expand: [30,-20] mean 5, spread 50 -> split (>=40).
$gv = array();
foreach ( $agg['goal_vectors'] as $v ) { $gv[ $v['key'] ] = $v; }
assert( $gv['defend_expand']['mean'] === 5.0, 'vector mean 5' );
assert( $gv['defend_expand']['spread'] === 50, 'vector spread 50' );
assert( $gv['defend_expand']['split'] === true, 'vector split at spread>=40' );

// posture fix_invest [40,0] mean 20 spread 40.
assert( $agg['posture']['fix_invest']['mean'] === 20.0, 'posture mean 20' );
assert( $agg['posture']['fix_invest']['spread'] === 40, 'posture spread 40' );

// qualitative vision has both, verbatim.
assert( count( $agg['qualitative']['vision'] ) === 2, 'two vision entries' );

// Single-response: no splits.
$solo = nb_discovery_aggregate( array( $chase ), $instance );
$soloByKey = array();
foreach ( $solo['services'] as $s ) { $soloByKey[ $s['key'] ] = $s; }
assert( $soloByKey['lead_gen']['split'] === false, 'single response never splits' );
assert( $solo['count'] === 1, 'solo count 1' );

// Zero-response: empty shape, no fatals.
$empty = nb_discovery_aggregate( array(), $instance );
assert( $empty['count'] === 0 && $empty['services'] === array(), 'empty aggregate' );

// Split boundary: importance spread of exactly 4 -> split; 3 -> no split.
$ba = array( 'id' => 1, 'name' => 'A', 'email' => 'a@x', 'payload' => array( 'services' => array(
    array( 'key' => 'website', 'importance' => 8, 'handling' => null ),
    array( 'key' => 'content', 'importance' => 7, 'handling' => null ),
) ) );
$bb = array( 'id' => 2, 'name' => 'B', 'email' => 'b@x', 'payload' => array( 'services' => array(
    array( 'key' => 'website', 'importance' => 4, 'handling' => null ),
    array( 'key' => 'content', 'importance' => 4, 'handling' => null ),
) ) );
$bagg = nb_discovery_aggregate( array( $ba, $bb ), $instance );
$bk = array();
foreach ( $bagg['services'] as $s ) { $bk[ $s['key'] ] = $s; }
assert( $bk['website']['importance_spread'] === 4 && $bk['website']['split'] === true, 'spread 4 -> split' );
assert( $bk['content']['importance_spread'] === 3 && $bk['content']['split'] === false, 'spread 3 -> no split' );

// Qualitative field list must follow the instance's systems_questions config.
$fake_sys = $instance; // overhead-door base
$fake_sys['systems_questions'] = array(
    array( 'key' => 'photos', 'label' => 'F?', 'short' => 'Photos', 'type' => 'text' ),
);
$chase2 = $chase; $chase2['payload']['systems'] = array( 'photos' => 'Dropbox' );
$agg2 = nb_discovery_aggregate( array( $chase2 ), $fake_sys );
assert( isset( $agg2['qualitative']['photos'] ), 'config-driven qualitative key present' );
assert( ! isset( $agg2['qualitative']['crm'] ), 'non-config qualitative key absent' );
assert( $agg2['qualitative']['photos'][0]['value'] === 'Dropbox', 'qualitative value carried' );

echo "test-aggregate: PASS\n";
