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

/**
 * The DatabaseDTOMapper class is a helper class for converting data
 * from the Performance Monitor custom tables into DTOs.
 */
class DatabaseDTOMapper {

	/**
	 * Given a report DB row, return the corresponding DTO.
	 *
	 * @param array $db_row
	 *
	 * @return ReportDTO
	 */
	public function reportToDTO( array $db_row ) {
		$wp_environment = json_decode( $db_row['wp_environment'], true );
		$summary        = json_decode( $db_row['summary'], true );

		$summary_dto = new SummaryDTO(
			isset( $summary['average_score'] ) ? intval( $summary['average_score'] ) : 0,
			isset( $summary['average_score_diff'] ) ? intval( $summary['average_score_diff'] ) : 0,
			isset( $summary['changes'] ) ? intval( $summary['changes'] ) : 0,
			isset( $summary['insights'] ) ? intval( $summary['insights'] ) : 0
		);

		$parent_theme_dto = null;
		if ( ! empty( $wp_environment['parent_theme'] ) ) {
			$parent_theme_dto = new ThemeDTO(
				isset( $wp_environment['parent_theme']['name'] ) ? $wp_environment['parent_theme']['name'] : '',
				isset( $wp_environment['parent_theme']['version'] ) ? $wp_environment['parent_theme']['version'] : ''
			);
		}

		$active_theme_dto = new ThemeDTO(
			isset( $wp_environment['active_theme']['name'] ) ? $wp_environment['active_theme']['name'] : '',
			isset( $wp_environment['active_theme']['version'] ) ? $wp_environment['active_theme']['version'] : ''
		);

		$active_plugins_dtos = [];

		if ( ! empty( $wp_environment['active_plugins'] ) && is_array( $wp_environment['active_plugins'] ) ) {
			$active_plugins_dtos = array_map( function ( $plugin ) {
				return new PluginDTO(
					isset( $plugin['name'] ) ? $plugin['name'] : '',
					isset( $plugin['version'] ) ? $plugin['version'] : ''
				);
			}, $wp_environment['active_plugins'] );
		}

		$wp_environment_dto = new WPEnvironmentDTO(
			isset( $wp_environment['core_version'] ) ? $wp_environment['core_version'] : '',
			$parent_theme_dto,
			$active_theme_dto,
			$active_plugins_dtos
		);

		return new ReportDTO(
			$db_row['id'],
			$db_row['timestamp'],
			$summary_dto,
			$wp_environment_dto
		);
	}

	/**
	 * Given a source DB row, return the corresponding DTO.
	 *
	 * @param array $db_row
	 *
	 * @return PageDTO
	 */
	public function pageToDTO( array $db_row ) {
		return new PageDTO(
			$db_row['id'],
			$db_row['report_id'],
			$db_row['name'],
			$db_row['url']
		);
	}

	/**
	 * Given a site change DB row, return the corresponding DTO.
	 *
	 * @param array $db_row
	 *
	 * @return SiteChangeDTO
	 */
	public function siteChangeToDTO( array $db_row ) {
		$object_meta             = json_decode( $db_row['object_meta'], true );
		$object_version          = empty( $db_row['object_version'] ) ? null : json_decode( $db_row['object_version'], true );
		$previous_object_meta    = empty( $db_row['previous_object_meta'] ) ? null : json_decode( $db_row['previous_object_meta'], true );
		$previous_object_version = empty( $db_row['previous_object_version'] ) ? null : json_decode( $db_row['previous_object_version'], true );

		$object_meta_dto = new ObjectMetaDTO(
			$object_meta['type'],
			isset( $object_meta['name'] ) ? $object_meta['name'] : ''
		);

		if ( $object_version ) {
			$object_version_dto = new ObjectVersionDTO(
				isset( $object_version['version'] ) ? $object_version['version'] : '',
				isset( $object_version['major'] ) ? $object_version['major'] : null,
				isset( $object_version['minor'] ) ? $object_version['minor'] : null,
				isset( $object_version['patch'] ) ? $object_version['patch'] : null
			);
		} else {
			$object_version_dto = null;
		}

		if ( $previous_object_meta ) {
			$previous_object_meta_dto = new ObjectMetaDTO(
				$previous_object_meta['type'],
				isset( $previous_object_meta['name'] ) ? $previous_object_meta['name'] : ''
			);
		} else {
			$previous_object_meta_dto = null;
		}

		if ( $previous_object_version ) {
			$previous_object_version_dto = new ObjectVersionDTO(
				isset( $previous_object_version['version'] ) ? $previous_object_version['version'] : '',
				isset( $previous_object_version['major'] ) ? $previous_object_version['major'] : null,
				isset( $previous_object_version['minor'] ) ? $previous_object_version['minor'] : null,
				isset( $previous_object_version['patch'] ) ? $previous_object_version['patch'] : null
			);
		} else {
			$previous_object_version_dto = null;
		}

		return new SiteChangeDTO(
			$db_row['id'],
			$db_row['report_id'],
			$db_row['action'],
			$object_meta_dto,
			$object_version_dto,
			$previous_object_meta_dto,
			$previous_object_version_dto
		);
	}

	/**
	 * Given an insight DB row, return the corresponding DTO.
	 *
	 * @param array $db_row
	 *
	 * @return InsightDTO
	 */
	public function insightToDTO( array $db_row ) {
		$insight_data  = json_decode( $db_row['data'], true );
		$variable_dtos = empty( $insight_data['variables'] )
			? []
			: array_map( function ( $variable ) {
				return new VariableDTO(
					$variable['variable'],
					$variable['value']
				);
			}, $insight_data['variables'] );

		$insight_data_dto = new InsightDataDTO(
			$variable_dtos
		);

		return new InsightDTO(
			$db_row['id'],
			$db_row['report_id'],
			$db_row['type'],
			$insight_data_dto
		);
	}

	/**
	 * Given a source DB row, return the corresponding DTO.
	 *
	 * @param array $db_row
	 *
	 * @return SourceDTO
	 */
	public function sourceToDTO( array $db_row ) {
		return new SourceDTO(
			isset( $db_row['large_file_id'] ) ? $db_row['large_file_id'] : null,
			isset( $db_row['insight_id'] ) ? $db_row['insight_id'] : null,
			isset( $db_row['type'] ) ? $db_row['type'] : null,
			isset( $db_row['name'] ) ? $db_row['name'] : null,
			isset( $db_row['timestamp'] ) ? $db_row['timestamp'] : null
		);
	}

	/**
	 * Given a source DB row, return the corresponding DTO.
	 *
	 * @param array $db_row
	 *
	 * @return LargeFileDTO
	 */
	public function largeFileToDTO( array $db_row ) {
		$large_file_data     = empty( $db_row['data'] ) ? [] : json_decode( $db_row['data'], true );
		$large_file_data_dto = new LargeFileDataDTO(
			isset( $large_file_data['url'] ) ? $large_file_data['url'] : null,
			isset( $large_file_data['filename'] ) ? $large_file_data['filename'] : null,
			isset( $large_file_data['old'] ) ? $large_file_data['old'] : null
		);

		return new LargeFileDTO(
			$db_row['id'],
			$db_row['page_id'],
			$db_row['type'],
			$db_row['weight'],
			$large_file_data_dto
		);
	}

	/**
	 * Given a source DB row, return the corresponding DTO.
	 *
	 * @param array $db_row
	 *
	 * @return MetricDataDTO
	 */
	public function metricsDataToDTO( array $db_row ) {
		return new MetricDataDTO(
			$db_row['id'],
			$db_row['page_id'],
			$db_row['metric_name'],
			$db_row['metric_value'],
			isset( $db_row['region'] ) ? $db_row['region'] : null,
			isset( $db_row['region_default'] ) ? $db_row['region_default'] : null
		);
	}
}
