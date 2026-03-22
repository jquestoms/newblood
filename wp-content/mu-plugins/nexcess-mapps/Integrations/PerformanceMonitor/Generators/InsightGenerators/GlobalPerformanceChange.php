<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\Data\VariableDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\DataDTO as InsightDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

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
	 * @return InsightDTO[]
	 */
	public function generate() {
		$this->globalDataMatrices = [
			$this->globalPerformance->getMetricMatrixFromReport( $this->reportDTO, 'score' ),
		];
		foreach ( $this->latestReportDTOs as $report_dto ) {
			$this->globalDataMatrices[] = $this->globalPerformance->getMetricMatrixFromReport( $report_dto, 'score' );
		}

		// We need data from at least 2 last days.
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
			$variable_dtos    = [
				new VariableDTO(
					'location',
					$this->getLocationVariableValue( $improvements, count( $all_regions ) )
				),
				new VariableDTO(
					'qualifier',
					_x(
						'improved',
						'Substituted as \'qualifier\' into this sentence: Performance of your site <%- qualifier %> significantly from <%- location %>',
						'nexcess-mapps'
					)
				),
			];
			$insight_data_dto = new InsightDataDTO( $variable_dtos );
			$insights[]       = $this->insightFromData( $insight_data_dto );
		}

		if ( $declines ) {
			$variable_dtos    = [
				new VariableDTO(
					'location',
					$this->getLocationVariableValue( $declines, count( $all_regions ) )
				),
				new VariableDTO(
					'qualifier',
					_x(
						'declined',
						'Substituted as \'qualifier\' into this sentence: Performance of your site <%- qualifier %> significantly from <%- location %>',
						'nexcess-mapps'
					)
				),
			];
			$insight_data_dto = new InsightDataDTO( $variable_dtos );
			$insights[]       = $this->insightFromData( $insight_data_dto );
		}

		return $insights;
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	public static function getInsightType() {
		return 'performance-change-global';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
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
	public static function getDescriptionText() {
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
	public static function getDescriptionURL() {
		return 'https://web.dev/performance-scoring/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'Performance of your site <%- qualifier %> significantly from <%- location %>',
			'nexcess-mapps'
		);
	}
}
