<?php
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__, 2 ) . '/inc/discovery/submission.php';

$services = array(
    array( 'key' => 'website', 'importance' => 9, 'handling' => 4 ),
    array( 'key' => 'seo_aeo', 'importance' => 6, 'handling' => null ),
);
$out = nb_discovery_compute_gaps( $services );
assert( $out[0]['gap'] === 5, 'gap = importance - handling' );
assert( $out[1]['gap'] === null, 'no gap when handling null' );
echo "test-gaps: PASS\n";
