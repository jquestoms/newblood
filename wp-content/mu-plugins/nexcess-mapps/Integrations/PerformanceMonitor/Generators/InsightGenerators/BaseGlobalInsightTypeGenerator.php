<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\GlobalPerformance;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\MetricMatrix;

/**
 * The `BaseGlobalInsightTypeGenerator` defines a structure of all the individual
 * `InsightGenerator`s that generate insights based on global performance data.
 */
abstract class BaseGlobalInsightTypeGenerator extends BaseInsightTypeGenerator {

	/** @var MetricMatrix[] */
	protected $globalDataMatrices;

	/**
	 * Generates the `location` variable value to be inserted in a new Insight.
	 *
	 * @param string[] $regions           Regions to generate the variable value for.
	 * @param int      $all_regions_count How many regions are there in total.
	 *
	 * @return string The `location` variable value.
	 */
	protected function getLocationVariableValue( $regions, $all_regions_count ) {
		$regions_count    = count( $regions );
		$region_names_map = GlobalPerformance::getRegionNamesMap();

		if ( $regions_count === $all_regions_count ) {
			$location = __( 'all test locations', 'nexcess-mapps' );
		} elseif ( $regions_count > 2 ) {
			$location = __( 'some test locations', 'nexcess-mapps' );
		} elseif ( 2 === $regions_count ) {
			$location = sprintf(
				// translators: Placeholders are for region names, e.g. Performance is slow from Sydney and US West.
				__(
					'%1$s and %2$s',
					'nexcess-mapps'
				),
				isset( $region_names_map[ $regions[0] ] ) ? $region_names_map[ $regions[0] ] : $regions[0],
				isset( $region_names_map[ $regions[1] ] ) ? $region_names_map[ $regions[1] ] : $regions[1]
			);
		} else {
			$location = sprintf(
				'%s',
				isset( $region_names_map[ $regions[0] ] ) ? $region_names_map[ $regions[0] ] : $regions[0]
			);
		}

		return $location;
	}
}
