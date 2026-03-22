<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Generator;

use Nexcess\MAPPS\Integrations\PerformanceMonitor;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Generator\InsightGenerator\BaseInsightTypeGenerator;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Model\Insight;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Model\Page;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Model\SiteChange;
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
		InsightGenerator\ExtrapolationRevenue::class      => [
			'priority'   => 2,
			'isRegional' => false,
		],
		InsightGenerator\ExtrapolationVisitors::class     => [
			'priority'   => 2,
			'isRegional' => false,
		],
		InsightGenerator\GlobalPerformance::class         => [
			'priority'   => 3,
			'isRegional' => true,
		],
		InsightGenerator\Performance::class               => [
			'priority'   => 3,
			'isRegional' => false,
		],
		InsightGenerator\PerformanceChange::class         => [
			'priority'   => 3,
			'isRegional' => false,
		],
		InsightGenerator\CLS::class                       => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerator\FID::class                       => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerator\FIDChange::class                 => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerator\GlobalPerformanceChange::class   => [
			'priority'   => 4,
			'isRegional' => true,
		],
		InsightGenerator\GlobalPerformancePlunge::class   => [
			'priority'   => 4,
			'isRegional' => true,
		],
		InsightGenerator\LCP::class                       => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerator\LCPChange::class                 => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerator\TTI::class                       => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerator\TTIChange::class                 => [
			'priority'   => 4,
			'isRegional' => false,
		],
		InsightGenerator\ResourceCountByTypeChange::class => [
			'priority'   => 5,
			'isRegional' => false,
		],
		InsightGenerator\ResourceTotalWeightChange::class => [
			'priority'   => 5,
			'isRegional' => false,
		],
		InsightGenerator\ResourceWeightByType::class      => [
			'priority'   => 5,
			'isRegional' => false,
		],
		InsightGenerator\BootupTime::class                => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerator\RenderBlockingResources::class   => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerator\FontDisplay::class               => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerator\TBT::class                       => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerator\TBTChange::class                 => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerator\ThirdPartyBlockingTime::class    => [
			'priority'   => 6,
			'isRegional' => false,
		],
		InsightGenerator\NoDocumentWrite::class           => [
			'priority'   => 7,
			'isRegional' => false,
		],
		InsightGenerator\Redirects::class                 => [
			'priority'   => 7,
			'isRegional' => false,
		],
	];

	/**
	 * @var LighthouseReport[]
	 */
	protected $lighthouseReports;

	/**
	 * @var Page[]
	 */
	protected $currentPages;

	/**
	 * @var Page[]
	 */
	protected $previousPages;

	/**
	 * @var Insight[]
	 */
	protected $previousInsights;

	/**
	 * @var SiteChange[]
	 */
	protected $siteChanges;

	/**
	 * @var array[][]
	 */
	protected $globalData;

	/**
	 * @var PerformanceMonitor
	 */
	protected $performanceMonitor;

	/**
	 * Constructor.
	 *
	 * @param LighthouseReport[] $lighthouse_reports  `LighthouseReport` objects for a given day.
	 * @param Page[]             $current_pages       `Page` objects generated today.
	 * @param PerformanceMonitor $performance_monitor A `PerformanceMonitor` instance.
	 * @param Page[]             $previous_pages      `Page` objects to compare today's results to.
	 * @param Insight[]          $previous_insights   `Insight` objects generated in the last week.
	 * @param SiteChange[]       $site_changes        `SiteChange` objects generated today.
	 * @param array[][]          $global_data         Global performance data generated in the last 7 days.
	 */
	public function __construct(
		array $lighthouse_reports,
		array $current_pages,
		$performance_monitor,
		array $previous_pages = [],
		array $previous_insights = [],
		array $site_changes = [],
		array $global_data = []
	) {
		$this->lighthouseReports  = $lighthouse_reports;
		$this->currentPages       = $current_pages;
		$this->performanceMonitor = $performance_monitor;
		$this->previousPages      = $previous_pages;
		$this->previousInsights   = $previous_insights;
		$this->siteChanges        = $site_changes;
		$this->globalData         = $global_data;
	}

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return Array<Array>
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

			/**
			 * @var InsightGenerator\BaseInsightTypeGenerator
			 */
			$generator_instance = new $generator(
				$this->lighthouseReports,
				$this->currentPages,
				$this->performanceMonitor,
				$this->previousPages,
				$this->previousInsights,
				$this->siteChanges,
				$muted_insight_types,
				$this->globalData
			);

			if ( ! $generator_instance->isMuted() ) {
				$insights = array_merge( $insights, $generator_instance->generate() );
			}
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
