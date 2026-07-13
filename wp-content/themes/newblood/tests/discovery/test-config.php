<?php
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/config.php';

$inst = nb_discovery_get_instance( 'overhead-door' );
assert( is_array( $inst ), 'overhead-door instance exists' );
assert( $inst['client_name'] === 'Overhead Door Company of Baltimore', 'client name set' );
assert( $inst['recipient'] === 'joms@newblood.com', 'recipient set' );
assert( count( $inst['services'] ) === 12, '12 service rows' );

$keys = array_column( $inst['services'], 'key' );
$expected = array( 'website','seo_aeo','brand_creative','lead_capture','reviews','content','hosting_security','crm','customer_comms','automation_ai','lead_gen','reporting' );
assert( $keys === $expected, 'service keys match clustered order' );

$groups = nb_discovery_service_groups();
assert( array_keys( $groups ) === array( 'get_found','convert','operate','grow' ), 'four ordered groups' );
$valid_groups = array_keys( $groups );
foreach ( $inst['services'] as $s ) {
    assert( ! empty( $s['label'] ) && ! empty( $s['hint'] ), "service {$s['key']} has label + hint" );
    assert( isset( $s['group'] ) && in_array( $s['group'], $valid_groups, true ), "service {$s['key']} has a valid group" );
}
assert( count( $inst['goal_vectors'] ) === 5, '5 goal vectors' );
$vkeys = array_column( $inst['goal_vectors'], 'key' );
assert( $vkeys === array( 'residential_commercial','leads_volume_quality','topline_lean','defend_expand','handson_managed' ), 'vector keys match' );

assert( nb_discovery_get_instance( 'nope' ) === null, 'unknown slug returns null' );

// --- systems_questions: every instance must define a valid question list ---
foreach ( nb_discovery_instances() as $slug => $i ) {
    assert( ! empty( $i['systems_questions'] ) && is_array( $i['systems_questions'] ), "$slug has systems_questions" );
    $qkeys = array_column( $i['systems_questions'], 'key' );
    assert( count( $qkeys ) === count( array_unique( $qkeys ) ), "$slug systems question keys unique" );
    foreach ( $i['systems_questions'] as $q ) {
        assert( ! empty( $q['key'] ) && ! empty( $q['label'] ) && ! empty( $q['short'] ), "{$slug}:{$q['key']} has key/label/short" );
        assert( in_array( $q['type'], array( 'text', 'textarea', 'radio' ), true ), "{$slug}:{$q['key']} valid type" );
        if ( $q['type'] === 'radio' ) {
            assert( ! empty( $q['options'] ) && is_array( $q['options'] ), "{$slug}:{$q['key']} radio has options" );
            assert( isset( $q['default'] ) && isset( $q['options'][ $q['default'] ] ), "{$slug}:{$q['key']} radio default is an option" );
        }
    }
}
// OHDBalt keys locked to the historical payload shape (form order).
$sq = array_column( nb_discovery_get_instance( 'overhead-door' )['systems_questions'], 'key' );
assert( $sq === array( 'crm', 'lead_handling', 'leads_per_month', 'reviews_system', 'call_tracking', 'gbp_access', 'territories' ), 'overhead-door systems keys in form order' );

// --- calindman instance ---
$cal = nb_discovery_get_instance( 'calindman' );
assert( is_array( $cal ), 'calindman instance exists' );
assert( $cal['client_name'] === 'C.A. Lindman, Inc.', 'calindman client name' );
assert( $cal['recipient'] === 'joms@newblood.com', 'calindman recipient' );
assert( count( $cal['services'] ) === 12, 'calindman 12 service rows' );
assert( array_column( $cal['services'], 'key' ) === array( 'website','seo_aeo','brand_creative','portfolio','lead_capture','reviews','hosting_security','crm','customer_comms','recruiting','lead_gen','reporting' ), 'calindman service keys in clustered order' );
assert( array_column( $cal['goal_vectors'], 'key' ) === array( 'volume_fit','deepen_expand','cal_crw','topline_lean','handson_managed' ), 'calindman vector keys' );
assert( array_column( $cal['systems_questions'], 'key' ) === array( 'pipeline_tracking','lead_handling','lead_sources','photo_library','gbp_access','coverage' ), 'calindman systems keys' );

echo "test-config: PASS\n";
