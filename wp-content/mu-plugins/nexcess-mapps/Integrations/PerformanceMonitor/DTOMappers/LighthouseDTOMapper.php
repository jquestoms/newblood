<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOMappers;

use Nexcess\MAPPS\Integrations\PerformanceMonitor;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;

// DTOs.
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerator;

// Generators.
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\PageGenerator;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\ReportGenerator;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\SiteChangeGenerator;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\GlobalPerformance;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\LighthouseReport;

/**
 * The LighthouseDTOMapper class is a helper class for converting data
 * from a set of raw Lighthouse reports into the corresponding set of DTOs.
 */
class LighthouseDTOMapper {

	/** @var LighthouseReport[] */
	protected $lighthouseReports;

	/** @var Array<string, LighthouseReport[]> */
	protected $regionalLighthouseReports;

	/** @var ReportDTO[] */
	protected $latestReportDTOs;

	/** @var PerformanceMonitor */
	protected $performanceMonitor;

	/**
	 * Constructor.
	 *
	 * @param LighthouseReport[]                $lighthouse_reports
	 * @param PerformanceMonitor                $performance_monitor
	 * @param Array<string, LighthouseReport[]> $regional_lighthouse_reports
	 * @param ReportDTO[]                       $latest_report_dtos
	 */
	public function __construct(
		array $lighthouse_reports,
		PerformanceMonitor $performance_monitor,
		array $regional_lighthouse_reports = [],
		array $latest_report_dtos = []
	) {
		$this->lighthouseReports         = $lighthouse_reports;
		$this->performanceMonitor        = $performance_monitor;
		$this->regionalLighthouseReports = $regional_lighthouse_reports;
		$this->latestReportDTOs          = $latest_report_dtos;
	}

	/**
	 * Converts the raw Lighthouse reports into a hierarchical Report DTO.
	 *
	 * @param Array<string, string> $page_names_map URL name map.
	 *
	 * @return ReportDTO
	 */
	public function getReportDTO( array $page_names_map = [] ) {
		$previous_report_dto = isset( $this->latestReportDTOs[0] ) ? $this->latestReportDTOs[0] : null;
		$report_generator    = new ReportGenerator(
			$this->lighthouseReports,
			$previous_report_dto
		);
		$report_dto          = $report_generator->generate();

		/**
		 * Generate the Page DTOs and assign them to the Report DTO.
		 */
		foreach ( $this->lighthouseReports as $lighthouse_report ) {
			$page_name = isset( $page_names_map[ $lighthouse_report->getUrl() ] )
				? $page_names_map[ $lighthouse_report->getUrl() ]
				: $lighthouse_report->getUrl();

			$regional_reports_for_current_page = [];

			foreach ( $this->regionalLighthouseReports as $region_name => $regional_reports ) {
				foreach ( $regional_reports as $regional_report ) {
					if ( $regional_report->getUrl() === $lighthouse_report->getUrl() ) {
						$regional_reports_for_current_page[ $region_name ] = $regional_report;
					}
				}
			}

			$page_generator = new PageGenerator(
				$page_name,
				$lighthouse_report,
				$regional_reports_for_current_page,
				$this->latestReportDTOs
			);

			$report_dto->addChild(
				$page_generator->generate()
			);
		}

		/**
		 * Generate the Site Change DTOs and assign them to the Report DTO.
		 */
		if ( $previous_report_dto ) {
			$site_change_generator = new SiteChangeGenerator(
				$report_dto,
				$previous_report_dto
			);
			$site_change_dtos      = $site_change_generator->generate();
			$site_changes_count    = count( $site_change_dtos );

			$report_dto->getSummary()->setChanges( $site_changes_count );

			foreach ( $site_change_dtos  as $site_change_dto ) {
				$report_dto->addChild( $site_change_dto );
			}
		}

		/**
		 * Generate the Insight DTOs and assign them to the Report DTO.
		 */
		$insight_generator = new InsightGenerator(
			$this->lighthouseReports,
			$report_dto,
			$this->latestReportDTOs,
			new GlobalPerformance(),
			$this->performanceMonitor
		);
		$insight_dtos      = $insight_generator->generate();
		$insight_count     = count( $insight_dtos );

		$report_dto->getSummary()->setInsights( $insight_count );

		foreach ( $insight_dtos as $insight_dto ) {
			$report_dto->addChild( $insight_dto );
		}

		return $report_dto;
	}
}
