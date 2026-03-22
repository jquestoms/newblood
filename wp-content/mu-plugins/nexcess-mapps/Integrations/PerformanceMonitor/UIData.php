<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor;

use Nexcess\MAPPS\Integrations\PerformanceMonitor;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\Data\VariableDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\LargeFileDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\MetricDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\PageDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChangeDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SourceDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerator;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\SiteChangeGenerator;
use Nexcess\MAPPS\Routes\PerformanceMonitorDataRoute;
use Nexcess\MAPPS\Routes\PerformanceMonitorMuteRoute;
use Nexcess\MAPPS\Services\Managers\RouteManager;

/**
 * The `UIData` class is responsible for generating structured
 * data necessary to initialize the React application.
 */
class UIData {

	/**
	 * Number of reports displayed per page of timeline.
	 */
	const POSTS_PER_PAGE = 10;

	/**
	 * Performance Monitor instance.
	 *
	 * @var PerformanceMonitor
	 */
	protected $performanceMonitor;

	/**
	 * RouteManager instance.
	 *
	 * @var RouteManager
	 */
	protected $routeManager;

	/**
	 * GlobalPerformance helper instance.
	 *
	 * @var GlobalPerformance
	 */
	protected $globalPerformance;

	/**
	 * Constructor.
	 *
	 * @param PerformanceMonitor $performance_monitor
	 * @param RouteManager       $route_manager
	 * @param GlobalPerformance  $global_performance
	 */
	public function __construct(
		PerformanceMonitor $performance_monitor,
		RouteManager $route_manager,
		GlobalPerformance $global_performance
	) {
		$this->performanceMonitor = $performance_monitor;
		$this->routeManager       = $route_manager;
		$this->globalPerformance  = $global_performance;
	}

	/**
	 * Gets all data necessary to initialize the React application.
	 *
	 * @param int $page The page number of results to return.
	 *
	 * @return Array<mixed>
	 */
	public function getAll( $page = 1 ) {
		if ( $this->performanceMonitor->isMigrationNeeded() ) {
			return [
				'migration' => true,
				'config'    => [],
			];
		}

		$db            = $this->performanceMonitor->getDb();
		$reports_count = $db->getCount( 'reports' );
		$reports       = $db->getReportsPage( $page, self::POSTS_PER_PAGE );

		return [
			'overview'   => $this->performanceMonitor->getOverviewData(),
			'reports'    => $this->getReportsData( $reports ),
			'pagination' => [
				'currentPage'  => $page,
				'postsPerPage' => self::POSTS_PER_PAGE,
				'totalPages'   => max(
					(int) ceil( $reports_count / self::POSTS_PER_PAGE ),
					1
				),
			],
			'config'     => [
				'api'                => $this->getRestRouteInfo(),
				'siteChangeTypes'    => SiteChangeGenerator::getSiteChangeTypes(),
				'insightTypes'       => InsightGenerator::getInsightTypes( $this->performanceMonitor->isRegionalApiEnabled() ),
				'muted'              => InsightGenerator::getMutedInsightTypes(),
				'urlNamesMap'        => $this->performanceMonitor->getUrlNamesMap(),
				'regionNamesMap'     => GlobalPerformance::getRegionNamesMap(),
				'host'               => $this->performanceMonitor->getDataCenterNameAndLocation(),
				'regionalApiEnabled' => $this->performanceMonitor->isRegionalApiEnabled(),
			],
		];
	}

	/**
	 * @param Array[] $reports_db_row DB rows from the `reports` table.
	 *
	 * @return Array<mixed>
	 */
	protected function getReportsData( array $reports_db_row ) {
		$reports_data = [];
		$db           = $this->performanceMonitor->getDb();

		foreach ( $reports_db_row as $report_db_row ) {
			/** @var ReportDTO */
			$report_dto = $db->loadDTO( 'reports', $report_db_row['id'] );

			$single_report_data = [
				'date'         => $report_dto->getTimestamp(),
				'alert'        => intval( $report_dto->getSummary()->getInsights() ) > 0,
				'summary'      => [
					'insights'           => $report_dto->getSummary()->getInsights(),
					'changes'            => $report_dto->getSummary()->getChanges(),
					'average_score'      => $report_dto->getSummary()->getAverageScore(),
					'average_score_diff' => $report_dto->getSummary()->getAverageScoreDiff(),
					'global_performance' => $this->globalPerformance->getGlobalPerformanceFromReport( $report_dto ),
				],
				'site_changes' => $this->getSiteChangesMeta( $report_dto->getChildren( SiteChangeDTO::class ) ),
				'insights'     => $this->getInsightsMeta( $report_dto->getChildren( InsightDTO::class ) ),
				'pages'        => $this->getPagesMeta( $report_dto->getChildren( PageDTO::class ) ),
			];

			$reports_data[] = $single_report_data;
		}

		return $reports_data;
	}

	/**
	 * Returns meta information associated with `SiteChange` objects
	 * that belong under this `Report`.
	 *
	 * @param SiteChangeDTO[] $site_change_dtos SiteChangeDTO instances.
	 *
	 * @return Array<mixed>
	 */
	protected function getSiteChangesMeta( array $site_change_dtos ) {
		return array_map( function ( SiteChangeDTO $site_change_dto ) {
			$meta = [
				'action'      => $site_change_dto->getAction(),
				'object_type' => $site_change_dto->getObjectMeta()->getType(),
				'object_name' => $site_change_dto->getObjectMeta()->getName(),
			];

			if ( $site_change_dto->getObjectVersion() ) {
				$meta['object_version']       = $site_change_dto->getObjectVersion()->getVersion();
				$meta['object_version_major'] = $site_change_dto->getObjectVersion()->getMajor();
				$meta['object_version_minor'] = $site_change_dto->getObjectVersion()->getMinor();
				$meta['object_version_patch'] = $site_change_dto->getObjectVersion()->getPatch();
			}

			if ( $site_change_dto->getPreviousObjectMeta() ) {
				$meta['previous_object_type'] = $site_change_dto->getPreviousObjectMeta()->getType();
				$meta['previous_object_name'] = $site_change_dto->getPreviousObjectMeta()->getName();
			}

			if ( $site_change_dto->getPreviousObjectVersion() ) {
				$meta['previous_object_version']       = $site_change_dto->getPreviousObjectVersion()->getVersion();
				$meta['previous_object_version_major'] = $site_change_dto->getPreviousObjectVersion()->getMajor();
				$meta['previous_object_version_minor'] = $site_change_dto->getPreviousObjectVersion()->getMinor();
				$meta['previous_object_version_patch'] = $site_change_dto->getPreviousObjectVersion()->getPatch();
			}

			return $meta;
		}, $site_change_dtos );
	}

	/**
	 * Returns meta information associated with `Insight` objects
	 * that belong under this `Report`.
	 *
	 * @param InsightDTO[] $insight_dtos InsightDTO instances.
	 *
	 * @return Array<mixed>
	 */
	protected function getInsightsMeta( array $insight_dtos ) {
		return array_map( function ( InsightDTO $insight_dto ) {
			$source_dtos = $insight_dto->getChildren( SourceDTO::class );

			return [
				'type'      => $insight_dto->getType(),
				'sources'   => array_map( function ( SourceDTO $source ) {
					return [
						'type'      => $source->getType(),
						'name'      => $source->getName(),
						'timestamp' => $source->getTimestamp(),
					];
				}, $source_dtos ),
				'variables' => array_map( function ( VariableDTO $variable ) {
					return [
						'variable' => $variable->getVariable(),
						'value'    => $variable->getValue(),
					];
				}, $insight_dto->getData()->getVariables() ),
			];
		}, $insight_dtos );
	}

	/**
	 * Returns meta information associated with `Page` objects
	 * that belong under this `Report`.
	 *
	 * @param PageDTO[] $page_dtos PageDTO instances.
	 *
	 * @return Array<mixed>
	 */
	protected function getPagesMeta( array $page_dtos ) {
		return array_map( function ( PageDTO $page_dto ) {
			$meta = [
				'name'        => $page_dto->getName(),
				'url'         => $page_dto->getUrl(),
				'large_files' => [],
			];

			$metric_data_dtos         = $page_dto->getChildren( MetricDataDTO::class );
			$default_metric_data_dtos = array_filter( $metric_data_dtos, function ( MetricDataDTO $metric_data_dto ) {
				return 1 === intval( $metric_data_dto->getRegionDefault() );
			} );

			foreach ( $default_metric_data_dtos as $default_metric_data_dto ) {
				$meta[ $default_metric_data_dto->getMetricName() ] = $default_metric_data_dto->getMetricValue();
			}

			$large_file_dtos = $page_dto->getChildren( LargeFileDTO::class );

			$meta['large_files'] = array_map( function ( LargeFileDTO $large_file_dto ) {
				$meta = [
					'type'     => $large_file_dto->getType(),
					'weight'   => $large_file_dto->getWeight(),
					'url'      => $large_file_dto->getData()->getUrl(),
					'filename' => $large_file_dto->getData()->getFilename(),
					'old'      => $large_file_dto->getData()->getOld(),
				];

				$source_dtos = $large_file_dto->getChildren( SourceDTO::class );

				if ( isset( $source_dtos[0] ) ) {
					/** @var SourceDTO */
					$source_dto = $source_dtos[0];

					$meta['source'] = [
						'type'      => $source_dto->getType(),
						'name'      => $source_dto->getName(),
						'timestamp' => $source_dto->getTimestamp(),
					];
				}

				return $meta;
			}, $large_file_dtos );

			return $meta;
		}, $page_dtos );
	}

	/**
	 * Get the REST route information.
	 *
	 * @return mixed[] The REST route information.
	 */
	protected function getRestRouteInfo() {
		$routes = $this->routeManager->getRoutes();
		$info   = [
			'nonce' => wp_create_nonce( 'wp_rest' ),
		];

		foreach ( $routes as $route ) {
			if ( $route instanceof PerformanceMonitorDataRoute ) {
				$info['dataRouteFormat'] = $route->getRouteFormat();
			}
			if ( $route instanceof PerformanceMonitorMuteRoute ) {
				$info['muteRouteFormat'] = $route->getRouteFormat();
			}
		}

		return $info;
	}
}
