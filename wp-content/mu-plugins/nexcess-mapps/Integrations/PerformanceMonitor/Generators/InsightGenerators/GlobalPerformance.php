<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\Data\VariableDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\DataDTO as InsightDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;

/**
 * The `GlobalPerformance` generator generates insights when the
 * overall performance of pages in one or more regions is poor.
 */
class GlobalPerformance extends BaseGlobalInsightTypeGenerator {

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

		$insight_types = array_reduce(
			$this->latestReportDTOs,
			function ( array $carry_types, ReportDTO $report_dto ) {
				$insight_dtos = $report_dto->getChildren( InsightDTO::class );
				foreach ( $insight_dtos as $insight_dto ) {
					$carry_types[ $insight_dto->getType() ] = true;
				}
				return $carry_types;
			},
			[]
		);

		// If we've emitted this insight in the last week, don't emit it again.
		if ( isset( $insight_types[ static::getInsightType() ] ) ) {
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

		$variable_dtos    = [
			new VariableDTO(
				'location',
				$this->getLocationVariableValue( $offending_regions, $regions_count )
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
		return 'low-performance-global';
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
			'This is likely to have a negative effect on user experience, and may also lead to a lower ranking in search results. If you have users in affected regions, you may be losing traffic due to poor user experience.',
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
			'Performance of your site is consistently poor from <%- location %>',
			'nexcess-mapps'
		);
	}
}
