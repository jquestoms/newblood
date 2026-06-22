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
echo "test-config: PASS\n";
