<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report\SummaryDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report\WPEnvironment\PluginDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report\WPEnvironment\ThemeDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report\WPEnvironmentDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\LighthouseReport;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\WPEnvironment;
use Nexcess\MAPPS\Support\Helpers;

/**
 * The `ReportGenerator` is responsible for generating post meta for a `Report`
 * instance once all Lighthouse reports are available for a given day.
 */
class ReportGenerator extends BaseGenerator {

	/**
	 * @var LighthouseReport[]
	 */
	protected $lighthouseReports;

	/**
	 * @var ReportDTO|null
	 */
	protected $previousReport;

	/**
	 * Constructor.
	 *
	 * @param LighthouseReport[] $lighthouse_reports Array of all `LighthouseReport` objects for a given day.
	 * @param ReportDTO          $previous_report    Report object to compare today's results to.
	 */
	public function __construct(
		array $lighthouse_reports,
		ReportDTO $previous_report = null
	) {
		$this->lighthouseReports = $lighthouse_reports;
		$this->previousReport    = $previous_report;
	}

	/**
	 * Generate a post meta array corresponding with a `Report` object.
	 *
	 * @return ReportDTO
	 */
	public function generate() {
		$scores = [];

		foreach ( $this->lighthouseReports as $report ) {
			$summary  = $report->getSummary();
			$scores[] = $summary['score'];
		}

		$average_score      = Helpers::calculateIntegerAverage( $scores );
		$average_score_diff = 0;

		if ( $this->previousReport instanceof ReportDTO ) {
			$previous_average_score = $this->previousReport->getSummary()->getAverageScore();
			$average_score_diff     = $average_score - $previous_average_score;
		}

		$parent_theme_dto = null;
		$wp_environment   = new WPEnvironment();

		if ( $wp_environment->getParentTheme() ) {
			$parent_theme_dto = new ThemeDTO(
				$wp_environment->getParentTheme()['name'],
				$wp_environment->getParentTheme()['version']
			);
		}
		$child_theme_dto = new ThemeDTO(
			$wp_environment->getCurrentTheme()['name'],
			$wp_environment->getCurrentTheme()['version']
		);
		$plugin_dtos     = array_map(
			function ( $plugin ) {
				return new PluginDTO(
					$plugin['name'],
					$plugin['version']
				);
			},
			$wp_environment->getActivePlugins()
		);

		$wp_environment_dto = new WPEnvironmentDTO(
			$wp_environment->getCoreVersion(),
			$parent_theme_dto,
			$child_theme_dto,
			$plugin_dtos
		);

		return new ReportDTO(
			null,
			null,
			new SummaryDTO(
				$average_score,
				$average_score_diff,
				0,
				0
			),
			$wp_environment_dto
		);
	}
}
