<?php
/**
 * Helper class to simplify manipulation with regional performance metrics.
 */

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\MetricDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\PageDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;

class GlobalPerformance {

	/**
	 * Returns global performance data associated with this `Report`.
	 *
	 * @param ReportDTO $report_dto Report DTO.
	 *
	 * @return Array<mixed>
	 */
	public function getGlobalPerformanceFromReport( ReportDTO $report_dto ) {
		$global_data = [];
		$pages_dtos  = $report_dto->getChildren( PageDTO::class );

		foreach ( $pages_dtos as $page_dto ) {
			$metric_data_dtos = $page_dto->getChildren( MetricDataDTO::class );
			$metric_data_dtos = array_filter( $metric_data_dtos, function ( MetricDataDTO $metric_data_dto ) {
				return true !== boolval( $metric_data_dto->getRegionDefault() );
			} );

			foreach ( $metric_data_dtos as $metric_data_dto ) {
				$page_id         = $page_dto->getId();
				$region          = $metric_data_dto->getRegion();
				$global_data_key = sprintf( '%s_%s', $page_id, $region );

				if ( ! isset( $global_data[ $global_data_key ] ) ) {
					$global_data[ $global_data_key ] = [
						'region' => $metric_data_dto->getRegion(),
						'url'    => $page_dto->getUrl(),
					];
				}

				$global_data[ $global_data_key ][ $metric_data_dto->getMetricName() ] = $metric_data_dto->getMetricValue();
			}
		}
		return array_values( $global_data );
	}

	/**
	 * Extracts regional performance data from a `Report` and turns the data into
	 * a `MetricMatrix` instance plucking the `$metric` from the report.
	 *
	 * @param ReportDTO $report_dto Report DTO.
	 * @param string    $metric     Metric type to be used as cell values in the matrix.
	 *
	 * @return MetricMatrix
	 */
	public function getMetricMatrixFromReport( ReportDTO $report_dto, $metric ) {
		$global_data = $this->getGlobalPerformanceFromReport( $report_dto );
		return MetricMatrix::fromGlobalPerformanceData( $global_data, $metric );
	}

	/**
	 * Returns a map between region IDs and their names.
	 *
	 * @return Array<string, string> Region name map.
	 */
	public static function getRegionNamesMap() {
		return [
			'us-central1'          => 'US Central',
			'us-west2'             => 'US West',
			'us-east1'             => 'US East',
			'europe-west2'         => 'London',
			'europe-west4'         => 'Amsterdam',
			'asia-southeast1'      => 'Singapore',
			'asia-northeast1'      => 'Tokyo',
			'australia-southeast1' => 'Sydney',
		];
	}
}
