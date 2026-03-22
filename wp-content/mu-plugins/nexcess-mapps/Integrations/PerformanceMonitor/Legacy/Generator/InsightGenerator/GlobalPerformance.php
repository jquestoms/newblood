<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Generator\InsightGenerator;

/**
 * The `GlobalPerformance` generator generates insights when the
 * overall performance of pages in one or more regions is poor.
 */
class GlobalPerformance extends BaseGlobalInsightTypeGenerator {

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return Array<Array> Insights data.
	 */
	public function generate() {
		$this->globalDataMatrices = $this->generateGlobalDataMatrices( 'score' );

		$insight_types = array_map(
			function ( $insight ) {
				return $insight->getMeta( 'type', '' );
			},
			$this->previousInsights
		);

		// If we've emitted this insight in the last week, don't emit it again.
		if ( in_array( static::getInsightType(), $insight_types, true ) ) {
			return [];
		}

		if ( count( $this->globalDataMatrices ) < 3 ) {
			return [];
		}

		$matrices              = array_slice( $this->globalDataMatrices, 0, 3 );
		$offending_region_sets = [];
		$regions_count         = $matrices[0]->getColsCount();

		foreach ( $matrices as $matrix ) {
			$regions_count     = $matrix->getColsCount();
			$offending_regions = [];

			for ( $region_index = 0; $region_index < $regions_count; $region_index++ ) {
				$region_label   = $matrix->getColHeader( $region_index );
				$region_average = $matrix->getColCellsAverage( $region_index );

				if ( $region_average < 50 ) {
					$offending_regions[] = $region_label;
				}
			}
			$offending_region_sets[] = $offending_regions;
		}
		$offending_regions       = array_intersect( ...$offending_region_sets );
		$offending_regions_count = count( $offending_regions );

		if ( 0 === $offending_regions_count ) {
			return [];
		}

		return [
			[
				'type'      => static::getInsightType(),
				'variables' => [
					[
						'variable' => 'location',
						'value'    => $this->getLocationVariableValue( $offending_regions, $regions_count ),
					],
				],
			],
		];
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	protected static function getInsightType() {
		return 'low-performance-global';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	protected static function getCategory() {
		return __(
			'Global Performance',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	protected static function getDescriptionText() {
		return __(
			'This is likely to have a negative effect on user experience, and may also lead to a lower ranking in search results. If you have users in affected regions, you may be losing traffic due to poor user experience.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	protected static function getDescriptionURL() {
		return 'https://web.dev/performance-scoring/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	protected static function getTemplate() {
		return __(
			'Performance of your site is consistently poor from <%- location %>',
			'nexcess-mapps'
		);
	}
}
