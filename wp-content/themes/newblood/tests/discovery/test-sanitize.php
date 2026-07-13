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

// --- Fixture lock: OHDBalt sanitize output must be value-identical to the pre-refactor capture ---
// (strict equality after canonical key sort: assoc key order is not API — every consumer reads by
// key name — and the old payload order differed from the form order; decided with Jeremy 2026-07-13)
function nb_test_canon( $a ) {
    if ( is_array( $a ) ) { ksort( $a ); foreach ( $a as &$v ) { $v = nb_test_canon( $v ); } }
    return $a;
}
$fixture_file = dirname( __DIR__, 5 ) . '/.discovery-baseline/sanitize-fixture.json'; // repo root
if ( file_exists( $fixture_file ) ) {
    $fixture = json_decode( file_get_contents( $fixture_file ), true );
    $got = nb_discovery_sanitize_payload( $fixture['raw'], nb_discovery_get_instance( 'overhead-door' ) );
    assert( nb_test_canon( $got ) === nb_test_canon( $fixture['expected'] ), 'OHDBalt sanitize output value-identical to pre-refactor fixture' );
    echo "fixture-lock: RAN\n"; // visible proof the gate executed (baseline dir exists only on this branch)
}

// --- Config-driven behavior with a synthetic instance ---
$fake = array(
    'slug' => 'fake', 'client_name' => 'Fake Co', 'recipient' => 'x@x.com',
    'services' => array( array( 'key' => 'website', 'group' => 'get_found', 'label' => 'W', 'hint' => 'h' ) ),
    'goal_vectors' => array( array( 'key' => 'alpha_beta', 'left' => 'Alpha', 'right' => 'Beta' ) ),
    'timeline_options' => array( 'ASAP' ),
    'section_copy' => array(),
    'welcome' => array( 'title' => '', 'intro' => '' ),
    'logo' => '',
    'systems_questions' => array(
        array( 'key' => 'pipeline', 'label' => 'P?', 'short' => 'P', 'type' => 'text' ),
        array( 'key' => 'photos',   'label' => 'F?', 'short' => 'F', 'type' => 'textarea', 'rows' => 2 ),
        array( 'key' => 'access',   'label' => 'A?', 'short' => 'A', 'type' => 'radio',
               'options' => array( 'yes' => 'Yes', 'no' => 'No', 'unsure' => 'Not sure' ), 'default' => 'unsure' ),
    ),
);
$rawf = array(
    'respondent' => array( 'name' => 'X', 'email' => 'x@x.com' ),
    'goal_vectors' => array( 'alpha_beta' => 999, 'residential_commercial' => 40 ),   // unknown key must be dropped
    'systems' => array( 'pipeline' => ' Trello <b>boards</b> ', 'photos' => "line1\nline2", 'access' => 'maybe', 'crm' => 'smuggled' ),
);
$cleanf = nb_discovery_sanitize_payload( $rawf, $fake );
assert( array_keys( $cleanf['goal_vectors'] ) === array( 'alpha_beta' ), 'vector keys come from instance config' );
assert( $cleanf['goal_vectors']['alpha_beta'] === 50, 'custom vector clamped' );
assert( array_keys( $cleanf['systems'] ) === array( 'pipeline', 'photos', 'access' ), 'systems keys come from config, unknown keys dropped' );
assert( $cleanf['systems']['pipeline'] === 'Trello boards', 'text sanitized' );
assert( strpos( $cleanf['systems']['photos'], "\n" ) !== false, 'textarea keeps newlines' );
assert( $cleanf['systems']['access'] === 'unsure', 'invalid radio value falls back to config default' );

echo "test-sanitize: PASS\n";
