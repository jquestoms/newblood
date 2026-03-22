<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Generator\InsightGenerator;

/**
 * The `GlobalPerformanceChange` generator generates insights when the
 * overall performance of pages in one or more regions changes
 * by 20 points or more in any region compared to yesterday's numbers.
 */
class GlobalPerformanceChange extends BaseGlobalInsightTypeGenerator {

	/**
	 * How many score points are considered a significant change.
	 */
	const SIGNIFICANT_DIFF = 20;

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return Array<Array> Insights data.
	 */
	public function generate() {
		$this->globalDataMatrices = $this->generateGlobalDataMatrices( 'score' );

		// We need data from at least 6 last days.
		if ( count( $this->globalDataMatrices ) < 2 ) {
			return [];
		}

		$current_matrix             = $this->globalDataMatrices[0];
		$yesterdays_matrix          = $this->globalDataMatrices[1];
		$current_region_count       = $current_matrix->getColsCount();
		$yesterdays_region_count    = $yesterdays_matrix->getColsCount();
		$current_region_averages    = [];
		$yesterdays_region_averages = [];

		for ( $i = 0; $i < $current_region_count; $i++ ) {
			$region_label   = $current_matrix->getColHeader( $i );
			$region_average = $current_matrix->getColCellsAverage( $i );

			$current_region_averages[ $region_label ] = $region_average;
		}

		for ( $i = 0; $i < $yesterdays_region_count; $i++ ) {
			$region_label   = $yesterdays_matrix->getColHeader( $i );
			$region_average = $yesterdays_matrix->getColCellsAverage( $i );

			$yesterdays_region_averages[ $region_label ] = $region_average;
		}

		$all_regions = array_unique( array_merge(
			array_keys( $current_region_averages ),
			array_keys( $yesterdays_region_averages )
		) );

		$improvements = [];
		$declines     = [];

		foreach ( $all_regions as $region_label ) {
			if (
				! isset( $current_region_averages[ $region_label ] )
				|| ! isset( $yesterdays_region_averages[ $region_label ] )
			) {
				continue;
			}

			$diff = $current_region_averages[ $region_label ] - $yesterdays_region_averages[ $region_label ];

			if ( $diff > self::SIGNIFICANT_DIFF ) {
				$improvements[] = $region_label;
			}
			if ( $diff < -self::SIGNIFICANT_DIFF ) {
				$declines[] = $region_label;
			}
		}

		$insights = [];

		if ( $improvements ) {
			$insights[] = [
				'type'      => static::getInsightType(),
				'variables' => [
					[
						'variable' => 'location',
						'value'    => $this->getLocationVariableValue( $improvements, count( $all_regions ) ),
					],
					[
						'variable' => 'qualifier',
						'value'    => _x(
							'improved',
							'Substituted as \'qualifier\' into this sentence: Performance of your site <%- qualifier %> significantly from <%- location %>',
							'nexcess-mapps'
						),
					],
				],
			];
		}

		if ( $declines ) {
			$insights[] = [
				'type'      => static::getInsightType(),
				'variables' => [
					[
						'variable' => 'location',
						'value'    => $this->getLocationVariableValue( $declines, count( $all_regions ) ),
					],
					[
						'variable' => 'qualifier',
						'value'    => _x(
							'declined',
							'Substituted as \'qualifier\' into this sentence: Performance of your site <%- qualifier %> significantly from <%- location %>',
							'nexcess-mapps'
						),
					],
				],
			];
		}

		return $insights;
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	protected static function getInsightType() {
		return 'performance-change-global';
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
			'Your site analytics may show an impact on traffic from the reported regions as a result.',
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
			'Performance of your site <%- qualifier %> significantly from <%- location %>',
			'nexcess-mapps'
		);
	}
}
