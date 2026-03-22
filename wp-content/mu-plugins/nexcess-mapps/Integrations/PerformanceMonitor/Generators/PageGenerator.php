<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\LargeFile\DataDTO as LargeFileDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\LargeFileDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\MetricDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\PageDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SourceDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\FileSource;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\LighthouseReport;

/**
 * The `PageGenerator` is responsible for generating post meta for a `Report`
 * instance once all Lighthouse reports are available for a given day.
 */
class PageGenerator extends BaseGenerator {

	/**
	 * @var LighthouseReport
	 */
	protected $lighthouseReport;

	/**
	 * @var LighthouseReport[]
	 */
	protected $regionalReports;

	/**
	 * Page description, i.e. 'Home page'.
	 *
	 * @var string
	 */
	protected $pageDescription;

	/** @var ReportDTO[] */
	protected $latestReportDTOs;

	/**
	 * Constructor.
	 *
	 * @param string             $page_description   Description of the page.
	 * @param LighthouseReport   $lighthouse_report  Lighthouse report object containing performance metrics
	 *                                               for this page.
	 * @param LighthouseReport[] $regional_reports   Lighthouse report objects coming from additional regions.
	 * @param ReportDTO[]        $latest_report_dtos
	 */
	public function __construct(
		$page_description,
		LighthouseReport $lighthouse_report,
		array $regional_reports,
		array $latest_report_dtos = []
	) {
		$this->pageDescription  = $page_description;
		$this->lighthouseReport = $lighthouse_report;
		$this->regionalReports  = $regional_reports;
		$this->latestReportDTOs = $latest_report_dtos;
	}

	/**
	 * Generate a post meta array corresponding with a `Page` object.
	 *
	 * @return PageDTO
	 */
	public function generate() {
		$page_dto          = new PageDTO(
			null,
			null,
			$this->pageDescription,
			$this->lighthouseReport->getUrl()
		);
		$previous_page_dto = $this->getPreviousPageDTO( $page_dto );

		$metrics_data = array_merge(
			$this->lighthouseReport->getSummary(),
			$this->lighthouseReport->getAssetsData()
		);

		$large_file_dtos = $this->getLargeFilesDTOs();

		/**
		 * Add large files to the page.
		 */
		foreach ( $large_file_dtos as $large_file_dto ) {
			$page_dto->addChild( $large_file_dto );
		}

		/**
		 * Add metrics to the page.
		 */
		$current_metrics = [];
		foreach ( $metrics_data as $metric_name => $metric_value ) {
			$current_metrics[ $metric_name ] = $metric_value;

			$page_dto->addChild(
				new MetricDataDTO(
					null,
					null,
					$metric_name,
					$metric_value,
					null,
					true // <-- Default region.
				)
			);
		}

		/**
		 * Add metrics from non-default regions.
		 */
		foreach ( $this->regionalReports as $region_name => $regional_report ) {
			$metrics_data = $regional_report->getSummary();

			foreach ( $metrics_data as $metric_name => $metric_value ) {
				$page_dto->addChild(
					new MetricDataDTO(
						null,
						null,
						$metric_name,
						$metric_value,
						$region_name,
						false // <-- Default region.
					)
				);
			}
		}

		if ( $previous_page_dto instanceof PageDTO ) {
			/** @var MetricDataDTO[] */
			$previous_metrics_dtos = $previous_page_dto->getChildren( MetricDataDTO::class );
			$current_metric_names  = array_keys( $current_metrics );

			foreach ( $previous_metrics_dtos as $previous_metrics_dto ) {
				if ( in_array( $previous_metrics_dto->getMetricName(), $current_metric_names, true ) ) {
					$diff_metric_name  = sprintf( '%s_diff', $previous_metrics_dto->getMetricName() );
					$diff_metric_value = $current_metrics[ $previous_metrics_dto->getMetricName() ] - $previous_metrics_dto->getMetricValue();

					$page_dto->addChild(
						new MetricDataDTO(
							null,
							null,
							$diff_metric_name,
							$diff_metric_value,
							null,
							true // <-- Default region.
						)
					);
				}
			}

			/**
			 * Check whether the current large files are different from the previous large files.
			 *
			 * If they are not different, mark the large files as unchanged (= old).
			 */

			/** @var LargeFileDTO[] */
			$previous_large_files_dtos = $previous_page_dto->getChildren( LargeFileDTO::class );

			$previous_large_files_urls = array_map( function ( $large_file_dto ) {
				return $large_file_dto->getData()->getUrl();
			}, $previous_large_files_dtos );

			foreach ( $large_file_dtos as $large_file_dto ) {
				if ( ! in_array( $large_file_dto->getData()->getUrl(), $previous_large_files_urls, true ) ) {
					$large_file_dto->getData()->setOld( true );
				}
			}
		}

		return $page_dto;
	}

	/**
	 * Generates the large file DTOs for the page.
	 *
	 * @return LargeFileDTO[]
	 */
	protected function getLargeFilesDTOs() {
		$large_files = $this->lighthouseReport->getLargeFiles();
		return array_map( function ( $large_file ) {
			$large_file_data_dto = new LargeFileDataDTO(
				isset( $large_file['url'] ) ? $large_file['url'] : '',
				isset( $large_file['filename'] ) ? $large_file['filename'] : '',
				false
			);

			$large_file_dto = new LargeFileDTO(
				null,
				null,
				isset( $large_file['type'] ) ? $large_file['type'] : '',
				isset( $large_file['weight'] ) ? intval( $large_file['weight'] ) : 0,
				$large_file_data_dto
			);

			$file_source = new FileSource();
			$source_data = $file_source->getSource( $large_file['url'] );

			$source_dto = new SourceDTO(
				null,
				null,
				isset( $source_data['type'] ) ? $source_data['type'] : null,
				isset( $source_data['name'] ) ? $source_data['name'] : null,
				isset( $source_data['timestamp'] ) ? $source_data['timestamp'] : null
			);

			$large_file_dto->addChild( $source_dto );

			return $large_file_dto;
		}, $large_files );
	}

	/**
	 * Returns the corresponding page DTO from the previous report.
	 *
	 * @param PageDTO $current_page_dto
	 *
	 * @return PageDTO|null
	 */
	protected function getPreviousPageDTO( PageDTO $current_page_dto ) {
		if ( isset( $this->latestReportDTOs[0] ) ) {
			$previous_report_dto = $this->latestReportDTOs[0];

			/** @var PageDTO[] */
			$previous_page_dtos = $previous_report_dto->getChildren( PageDTO::class );

			foreach ( $previous_page_dtos as $previous_page_dto ) {
				if ( $previous_page_dto->getUrl() === $current_page_dto->getUrl() ) {
					return $previous_page_dto;
				}
			}
		}
		return null;
	}
}
