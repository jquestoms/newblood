<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators\BaseInsightTypeGenerator;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\GlobalPerformance;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\LighthouseReport;

/**
 * The `InsightGenerator` is responsible for generating post meta for a `Insight`
 * instance once all Lighthouse reports are available for a given day.
 */
class InsightGenerator extends BaseGenerator {

	/**
	 * Insights can be muted. We store a list insight type IDs in WP options
	 * along with a timestamp when they were muted.
	 *
	 * @var string
	 */
	const MUTED_INSIGHTS_OPTION_KEY = PerformanceMonitor::DATA_PREFIX . 'muted_insights';

	/**
	 * Duration for how long insights should remain muted.
	 *
	 * @var int
	 */
	const MUTED_INSIGHTS_DURATION = WEEK_IN_SECONDS;

	/**
	 * The individual generators responsible for generating
	 * different types of insights.
	 *
	 * @var Array<class-string, array{"priority": int, "isRegional": bool}>
	 */
	public static $generators = [
		InsightGenerators\ExtrapolationRevenue::class      => [
			'priority'   => 2,
			'isRegional' => false,
		],
		InsightGenerators\ExtrapolationVisitors::class     => [
			'priority'   => 2,
			'isRegional' => false,
		],
		InsightGenerators\GlobalPerformance::class         => [
			'priority'   => 3,
			'isRegional' => true,
		],
		InsightGenerators\Performance::class               => [
			'priority'   => 3,
			'isRegional' => false,
		],
		InsightGenerators\PerformanceChange::class         => [
			'priority'   => 3,
			'isRegional' => false,
		],
		InsightGenerators\CLS::class                       => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerators\FID::class                       => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerators\FIDChange::class                 => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerators\GlobalPerformanceChange::class   => [
			'priority'   => 4,
			'isRegional' => true,
		],
		InsightGenerators\GlobalPerformancePlunge::class   => [
			'priority'   => 4,
			'isRegional' => true,
		],
		InsightGenerators\LCP::class                       => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerators\LCPChange::class                 => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerators\TTI::class                       => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerators\TTIChange::class                 => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerators\ResourceCountByTypeChange::class => [
			'priority'   => 5,
			'isRegional' => false,
		],
		InsightGenerators\ResourceTotalWeightChange::class => [
			'priority'   => 5,
			'isRegional' => false,
		],
		InsightGenerators\ResourceWeightByType::class      => [
			'priority'   => 5,
			'isRegional' => false,
		],
		InsightGenerators\BootupTime::class                => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerators\RenderBlockingResources::class   => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerators\FontDisplay::class               => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerators\TBT::class                       => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerators\TBTChange::class                 => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerators\ThirdPartyBlockingTime::class    => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerators\NoDocumentWrite::class           => [
			'priority'   => 7,
			'isRegional' => false,
		],
		InsightGenerators\Redirects::class                 => [
			'priority'   => 7,
			'isRegional' => false,
		],
	];

	/** @var LighthouseReport[] */
	protected $lighthouseReports;

	/** @var ReportDTO */
	protected $reportDTO;

	/** @var ReportDTO[] */
	protected $latestReportDTOs;

	/** @var GlobalPerformance */
	protected $globalPerformance;

	/** @var PerformanceMonitor */
	protected $performanceMonitor;

	/**
	 * Constructor.
	 *
	 * @param LighthouseReport[] $lighthouse_reports  `LighthouseReport` objects for a given day.
	 * @param ReportDTO          $report_dto          `ReportDTO` generated today.
	 * @param ReportDTO[]        $latest_report_dtos  Latest 7 `ReportDTO` objects.
	 * @param GlobalPerformance  $global_performance  `GlobalPerformance` helper instance.
	 * @param PerformanceMonitor $performance_monitor `PerformanceMonitor` instance.
	 */
	public function __construct(
		array $lighthouse_reports,
		ReportDTO $report_dto,
		array $latest_report_dtos,
		GlobalPerformance $global_performance,
		PerformanceMonitor $performance_monitor
	) {
		$this->lighthouseReports  = $lighthouse_reports;
		$this->reportDTO          = $report_dto;
		$this->latestReportDTOs   = $latest_report_dtos;
		$this->globalPerformance  = $global_performance;
		$this->performanceMonitor = $performance_monitor;
	}

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$insights            = [];
		$muted_insight_types = self::getMutedInsightTypes();

		foreach ( self::$generators as $generator => $generator_config ) {
			/**
			 * Skip regional generators if the Performance Monitor is not
			 * using the regional API.
			 */
			if ( ! $this->performanceMonitor->isRegionalApiEnabled() ) {
				if ( $generator_config['isRegional'] ) {
					continue;
				}
			}

			$get_type_method = [ $generator, 'getInsightType' ];

			if ( ! is_callable( $get_type_method ) ) {
				continue;
			}

			$insight_type = call_user_func( $get_type_method );

			if ( in_array( $insight_type, $muted_insight_types, true ) ) {
				continue;
			}

			/**
			 * @var InsightGenerators\BaseInsightTypeGenerator
			 */
			$generator_instance = new $generator(
				$this->lighthouseReports,
				$this->reportDTO,
				$this->latestReportDTOs,
				$this->globalPerformance
			);

			$insights = array_merge( $insights, $generator_instance->generate() );
		}

		return $insights;
	}

	/**
	 * Returns the description of all the different insight types.
	 *
	 * @param bool $regional_api_enabled Whether to include regional insight types.
	 *
	 * @return Array<Array>
	 */
	public static function getInsightTypes( $regional_api_enabled ) {
		$insight_types = [];

		/**
		 * @var BaseInsightTypeGenerator                   $generator
		 * @var array{"priority": int, "isRegional": bool} $generator_config
		 */
		foreach ( self::$generators as $generator => $generator_config ) {
			/**
			 * Skip regional generators if the Performance Monitor is not
			 * using the regional API.
			 */
			if ( ! $regional_api_enabled && $generator_config['isRegional'] ) {
				continue;
			}

			$insight_types[] = array_merge(
				$generator::getTemplateData(),
				[ 'priority' => $generator_config['priority'] ]
			);
		}

		/**
		 * Make sure the types are sorted by priority.
		 */
		usort(
			$insight_types,
			function ( $type_1, $type_2 ) {
				return $type_1['priority'] > $type_2['priority'] ? 1 : -1;
			}
		);

		return $insight_types;
	}

	/**
	 * Returns the list of currently muted insight types.
	 *
	 * @return Array<Array>
	 */
	public static function getMutedInsightTypes() {
		$muted_insights             = get_option( self::MUTED_INSIGHTS_OPTION_KEY, [] );
		$non_expired_muted_insights = [];

		foreach ( $muted_insights as $type => $muted_time ) {
			if ( time() - $muted_time < self::MUTED_INSIGHTS_DURATION ) {
				$non_expired_muted_insights[] = $type;
			}
		}

		return $non_expired_muted_insights;
	}
}
