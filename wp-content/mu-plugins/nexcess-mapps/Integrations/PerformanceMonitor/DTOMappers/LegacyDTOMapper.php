<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOMappers;

// DTOs.
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\Data\VariableDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\DataDTO as InsightDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\LargeFile\DataDTO as LargeFileDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\LargeFileDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\MetricDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\PageDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report\SummaryDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report\WPEnvironment\PluginDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report\WPEnvironment\ThemeDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report\WPEnvironmentDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChange\ObjectMetaDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChange\ObjectVersionDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChangeDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SourceDTO;

// Legacy models.
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Model\Insight;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Model\Page;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Model\Report;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Model\SiteChange;

// Legacy query helpers.
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Query\InsightQuery;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Query\PageQuery;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Query\SiteChangeQuery;

/**
 * The LegacyDTOMapper class is a helper class for converting legacy,
 * WordPress native tables stored performance data to DTOs.
 */
class LegacyDTOMapper {
	/** @var PageQuery */
	protected $pageQuery;

	/** @var InsightQuery */
	protected $insightQuery;

	/** @var SiteChangeQuery */
	protected $siteChangeQuery;

	/**
	 * Constructor.
	 *
	 * @param PageQuery|null       $page_query
	 * @param InsightQuery|null    $insight_query
	 * @param SiteChangeQuery|null $site_change_query
	 */
	public function __construct(
		PageQuery $page_query = null,
		InsightQuery $insight_query = null,
		SiteChangeQuery $site_change_query = null
	) {
		if ( null === $page_query ) {
			$page_query = new PageQuery();
		}
		if ( null === $insight_query ) {
			$insight_query = new InsightQuery();
		}
		if ( null === $site_change_query ) {
			$site_change_query = new SiteChangeQuery();
		}

		$this->pageQuery       = $page_query;
		$this->insightQuery    = $insight_query;
		$this->siteChangeQuery = $site_change_query;
	}

	/**
	 * Converts a Legacy Report to a ReportDTO.
	 *
	 * The returned ReportDTO is hierarchical, i.e. it has children,
	 * which can be retrieved by calling getChildren().
	 *
	 * @param Report $report
	 *
	 * @return ReportDTO
	 */
	public function reportToDTO( Report $report ) {
		/**
		 * Summary DTO.
		 */
		$report_meta = $report->getMeta();

		$summary_dto = new SummaryDTO(
			empty( $report_meta['average_score'] ) ? 0 : intval( $report_meta['average_score'] ),
			empty( $report_meta['average_score_diff'] ) ? 0 : intval( $report_meta['average_score_diff'] ),
			empty( $report_meta['changes'] ) ? 0 : intval( $report_meta['changes'] ),
			empty( $report_meta['insights'] ) ? 0 : intval( $report_meta['insights'] )
		);

		/**
		 * WPEnvironment DTO.
		 */
		$parent_theme_dto = null;
		if ( ! empty( $report_meta['wp_environment']['parent_theme'] ) ) {
			$parent_theme_dto = new ThemeDTO(
				empty( $report_meta['wp_environment']['parent_theme']['name'] ) ? '' : $report_meta['wp_environment']['parent_theme']['name'],
				empty( $report_meta['wp_environment']['parent_theme']['version'] ) ? '' : $report_meta['wp_environment']['parent_theme']['version']
			);
		}

		$active_theme_dto = new ThemeDTO(
			empty( $report_meta['wp_environment']['active_theme']['name'] ) ? '' : $report_meta['wp_environment']['active_theme']['name'],
			empty( $report_meta['wp_environment']['active_theme']['version'] ) ? '' : $report_meta['wp_environment']['active_theme']['version']
		);

		$plugin_dtos = [];
		if (
			isset( $report_meta['wp_environment']['active_plugins'] )
			&& is_array( $report_meta['wp_environment']['active_plugins'] )
		) {
			foreach ( $report_meta['wp_environment']['active_plugins'] as $plugin ) {
				$plugin_dtos[] = new PluginDTO(
					empty( $plugin['name'] ) ? '' : $plugin['name'],
					empty( $plugin['version'] ) ? '' : $plugin['version']
				);
			}
		}

		$wp_environment_dto = new WPEnvironmentDTO(
			empty( $report_meta['wp_environment']['core_version'] ) ? '' : $report_meta['wp_environment']['core_version'],
			$parent_theme_dto,
			$active_theme_dto,
			$plugin_dtos
		);

		/**
		 * Report DTO.
		 */
		$report_dto = new ReportDTO(
			null,
			$report->getDate(),
			$summary_dto,
			$wp_environment_dto
		);

		/**
		 * Assign Page DTOs as children.
		 */
		$report_id     = $report->getAssociatedPostId();
		$pages         = $this->pageQuery->getByParent( $report_id );
		$page_dtos_map = [];

		foreach ( $pages as $page ) {
			$page_dto                             = $this->pageToDTO( $page );
			$page_dtos_map[ $page_dto->getUrl() ] = $page_dto;

			$report_dto->addChild( $page_dto );
		}

		/**
		 * Extract Regional Performance metrics.
		 */
		$global_entries       = $report->getMeta( 'global_performance', [] );
		$known_global_metrics = [
			'score',
			'load_time',
			'lcp_time',
			'max_fid',
			'render_blocking_time',
			'total_blocking_time',
			'bootup_time',
			'weight',
		];

		foreach ( $global_entries as $global_entry ) {
			if ( ! isset( $page_dtos_map[ $global_entry['url'] ] ) ) {
				continue;
			}

			/** @var PageDTO */
			$page_dto = $page_dtos_map[ $global_entry['url'] ];

			foreach ( $known_global_metrics as $metric_name ) {
				if ( isset( $global_entry[ $metric_name ] ) ) {
					$page_dto->addChild(
						new MetricDataDTO(
							null,
							null,
							$metric_name,
							$global_entry[ $metric_name ],
							empty( $global_entry['region'] ) ? null : $global_entry['region'],
							false
						)
					);
				}
			}
		}

		/**
		 * Assign Insight DTOs as children.
		 */
		$insights = $this->insightQuery->getByParent( $report_id );

		foreach ( $insights as $insight ) {
			$report_dto->addChild(
				$this->insightToDTO( $insight )
			);
		}

		/**
		 * Assign Site Change DTOs as children.
		 */
		$site_changes = $this->siteChangeQuery->getByParent( $report_id );

		foreach ( $site_changes as $site_change ) {
			$report_dto->addChild(
				$this->siteChangeToDTO( $site_change )
			);
		}

		return $report_dto;
	}

	/**
	 * Converts a Page to a PageDTO.
	 *
	 * @param Page $page
	 *
	 * @return PageDTO
	 */
	public function pageToDTO( Page $page ) {

		/**
		 * Page DTO.
		 */
		$page_dto = new PageDTO(
			null,
			null,
			$page->getMeta( 'name', '' ),
			$page->getMeta( 'url', '' )
		);

		/**
		 * MetricData DTOs.
		 */
		$page_meta = $page->getMeta();

		$known_metric_names = [
			'score',
			'load_time',
			'load_time_diff',
			'bootup_time',
			'lcp_time',
			'max_fid',
			'render_blocking_time',
			'total_blocking_time',
			'weight',
			'weight_diff',
			'weight_document',
			'weight_document_diff',
			'weight_script',
			'weight_script_diff',
			'number_files_script',
			'number_files_script_diff',
			'weight_stylesheet',
			'weight_stylesheet_diff',
			'number_files_stylesheet',
			'number_files_stylesheet_diff',
			'weight_image',
			'weight_image_diff',
			'number_files_image',
			'number_files_image_diff',
			'weight_media',
			'weight_media_diff',
			'number_files_media',
			'number_files_media_diff',
			'weight_third-party',
			'weight_third-party_diff',
			'number_files_third-party',
			'number_files_third-party_diff',
		];

		$metric_values = [];

		foreach ( $known_metric_names as $metric_name ) {
			if ( isset( $page_meta[ $metric_name ] ) ) {
				$metric_values[ $metric_name ] = $page_meta[ $metric_name ];
			}
		}

		foreach ( $metric_values as $metric_name => $metric_value ) {
			$page_dto->addChild(
				new MetricDataDTO(
					null,
					null,
					$metric_name,
					$metric_value,
					null,
					true // <-- Signifies metric data is from the default region.
				)
			);
		}

		/**
		 * LargeFile DTOs.
		 */
		if ( empty( $page_meta['large_files'] ) || ! is_array( $page_meta['large_files'] ) ) {
			return $page_dto;
		}

		foreach ( $page_meta['large_files'] as $large_file ) {
			$large_file_data_dto = new LargeFileDataDTO(
				empty( $large_file['url'] ) ? '' : $large_file['url'],
				empty( $large_file['filename'] ) ? '' : $large_file['filename']
			);
			$large_file_dto      = new LargeFileDTO(
				null,
				null,
				empty( $large_file['type'] ) ? '' : $large_file['type'],
				empty( $large_file['weight'] ) ? 0 : intval( $large_file['weight'] ),
				$large_file_data_dto
			);

			if (
				isset( $large_file['source'] )
				&& is_array( $large_file['source'] )
				&& ! empty( $large_file['source'] )
			) {
				$source = $large_file['source'];
				$large_file_dto->addChild(
					new SourceDTO(
						null,
						null,
						empty( $source['type'] ) ? null : $source['type'],
						empty( $source['name'] ) ? null : $source['name'],
						empty( $source['date'] ) ? null : $source['date']
					)
				);
			}

			$page_dto->addChild( $large_file_dto );
		}

		return $page_dto;
	}

	/**
	 * Converts an Insight to a InsightDTO.
	 *
	 * @param Insight $insight
	 *
	 * @return InsightDTO
	 */
	public function insightToDTO( Insight $insight ) {
		$variables     = $insight->getMeta( 'variables', [] );
		$variable_dtos = [];

		foreach ( $variables as $variable_item ) {
			$variable_dtos[] = new VariableDTO( $variable_item['variable'], $variable_item['value'] );
		}
		$insight_data_dto = new InsightDataDTO( $variable_dtos );
		$insight_dto      = new InsightDTO(
			null,
			null,
			$insight->getMeta( 'type', '' ),
			$insight_data_dto
		);

		$sources = $insight->getMeta( 'sources', [] );

		foreach ( $sources as $source ) {
			if ( is_array( $source ) && ! empty( $source ) ) {
				$insight_dto->addChild(
					new SourceDTO(
						null,
						null,
						empty( $source['type'] ) ? null : $source['type'],
						empty( $source['name'] ) ? null : $source['name'],
						empty( $source['date'] ) ? null : $source['date']
					)
				);
			}
		}

		return $insight_dto;
	}

	/**
	 * Converts a Site Change to a SiteChangeDTO.
	 *
	 * @param SiteChange $site_change
	 *
	 * @return SiteChangeDTO
	 */
	public function siteChangeToDTO( SiteChange $site_change ) {
		$object_meta             = new ObjectMetaDTO(
			$site_change->getMeta( 'type', '' ),
			$site_change->getMeta( 'name', '' )
		);
		$object_version          = null;
		$previous_object_meta    = null;
		$previous_object_version = null;

		if ( $site_change->getMeta( 'object_version' ) ) {
			$major = $site_change->getMeta( 'object_version_major', null );
			$minor = $site_change->getMeta( 'object_version_minor', null );
			$patch = $site_change->getMeta( 'object_version_patch', null );

			$object_version = new ObjectVersionDTO(
				$site_change->getMeta( 'object_version', '' ),
				null === $major ? null : (int) $major,
				null === $minor ? null : (int) $minor,
				null === $patch ? null : (int) $patch
			);
		}

		if ( $site_change->getMeta( 'previous_object_type' ) ) {
			$previous_object_meta = new ObjectMetaDTO(
				$site_change->getMeta( 'previous_object_type', '' ),
				$site_change->getMeta( 'previous_object_name', '' )
			);
		}

		if ( $site_change->getMeta( 'previous_object_version' ) ) {
			$major = $site_change->getMeta( 'previous_object_version_major', null );
			$minor = $site_change->getMeta( 'previous_object_version_minor', null );
			$patch = $site_change->getMeta( 'previous_object_version_patch', null );

			$previous_object_version = new ObjectVersionDTO(
				$site_change->getMeta( 'previous_object_version', '' ),
				null === $major ? null : (int) $major,
				null === $minor ? null : (int) $minor,
				null === $patch ? null : (int) $patch
			);
		}

		return new SiteChangeDTO(
			null,
			null,
			$site_change->getMeta( 'action', '' ),
			$object_meta,
			$object_version,
			$previous_object_meta,
			$previous_object_version
		);
	}
}
