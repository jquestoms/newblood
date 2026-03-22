<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\Data\VariableDTO;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\DataDTO as InsightDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\MetricMatrix;

/**
 * The `GlobalPerformancePlunge` generator generates insights when the
 * overall performance of pages in one or more regions is good (score >90)
 * for 5 days straight and then the score drops to bad (< 50) suddenly.
 */
class GlobalPerformancePlunge extends BaseGlobalInsightTypeGenerator {

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

		// We need data from at least 6 last days.
		if ( count( $this->globalDataMatrices ) < 6 ) {
			return [];
		}

		/** @var MetricMatrix[] */
		$past_matrices = array_slice( $this->globalDataMatrices, 0, 6 );

		/** @var MetricMatrix */
		$current_matrix   = array_shift( $past_matrices );
		$good_region_sets = [];

		/**
		 * First we identify where the scores were consistently good
		 * in the last 5 days.
		 */
		foreach ( $past_matrices as $matrix ) {
			$regions_count = $matrix->getColsCount();
			$good_regions  = [];

			for ( $region_index = 0; $region_index < $regions_count; $region_index++ ) {
				$region_label   = $matrix->getColHeader( $region_index );
				$region_average = $matrix->getColCellsAverage( $region_index );

				if ( $region_average > 90 ) {
					$good_regions[] = $region_label;
				}
			}
			$good_region_sets[] = $good_regions;
		}
		$good_regions_in_the_past = array_intersect( ...$good_region_sets );

		/**
		 * Second we identify which regions are red today.
		 */
		$regions_count   = $current_matrix->getColsCount();
		$bad_regions_now = [];

		for ( $region_index = 0; $region_index < $regions_count; $region_index++ ) {
			$region_label   = $current_matrix->getColHeader( $region_index );
			$region_average = $current_matrix->getColCellsAverage( $region_index );

			if ( $region_average < 50 ) {
				$bad_regions_now[] = $region_label;
			}
		}

		/**
		 * Third we generate a list of previously green regions
		 * where the performance dropped today.
		 */
		$dropped_performance_regions = array_intersect( $good_regions_in_the_past, $bad_regions_now );

		if ( 0 === count( $dropped_performance_regions ) ) {
			return [];
		}

		$variable_dtos    = [
			new VariableDTO(
				'location',
				$this->getLocationVariableValue( $dropped_performance_regions, $regions_count )
			),
		];
		$insight_data_dto = new InsightDataDTO( $variable_dtos );

		return [ $this->insightFromData( $insight_data_dto ) ];
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	public static function getInsightType() {
		return 'performance-drop-global';
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
			'Scores will fluctuate from day to day. This result may be a one-off; but keep an eye on it, in case a problem persists.',
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
			'Performance of your site was unusually poor from <%- location %>',
			'nexcess-mapps'
		);
	}
}
