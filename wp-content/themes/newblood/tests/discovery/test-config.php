<?php
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/config.php';

$inst = nb_discovery_get_instance( 'overhead-door' );
assert( is_array( $inst ), 'overhead-door instance exists' );
assert( $inst['client_name'] === 'Overhead Door Company of Baltimore', 'client name set' );
assert( $inst['recipient'] === 'joms@newblood.com', 'recipient set' );
assert( count( $inst['services'] ) === 12, '12 service rows' );

$keys = array_column( $inst['services'], 'key' );
$expected = array( 'website','seo_aeo','hosting_security','content','reviews','lead_gen','lead_capture','customer_comms','crm','automation_ai','reporting','brand_creative' );
assert( $keys === $expected, 'service keys match canonical order' );
foreach ( $inst['services'] as $s ) {
    assert( ! empty( $s['label'] ) && ! empty( $s['hint'] ), "service {$s['key']} has label + hint" );
}
assert( count( $inst['goal_vectors'] ) === 5, '5 goal vectors' );
$vkeys = array_column( $inst['goal_vectors'], 'key' );
assert( $vkeys === array( 'residential_commercial','leads_volume_quality','topline_lean','defend_expand','handson_managed' ), 'vector keys match' );

assert( nb_discovery_get_instance( 'nope' ) === null, 'unknown slug returns null' );
echo "test-config: PASS\n";
