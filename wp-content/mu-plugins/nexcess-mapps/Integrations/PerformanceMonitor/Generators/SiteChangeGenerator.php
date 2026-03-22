<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report\WPEnvironment\PluginDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChange\ObjectMetaDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChange\ObjectVersionDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChangeDTO;

/**
 * The `SiteChangeGenerator` is responsible for generating `SiteChangeDTO`
 * objects by comparing the current state of WP Environment to the previous one.
 */
class SiteChangeGenerator extends BaseGenerator {

	/** @var ReportDTO */
	protected $previousReportDTO;

	/** @var ReportDTO */
	protected $currentReportDTO;

	/**
	 * Constructor.
	 *
	 * @param ReportDTO $current_report_dto  Current report DTO instance.
	 * @param ReportDTO $previous_report_dto Previous report DTO instance.
	 */
	public function __construct(
		ReportDTO $current_report_dto,
		ReportDTO $previous_report_dto
	) {
		$this->currentReportDTO  = $current_report_dto;
		$this->previousReportDTO = $previous_report_dto;
	}

	/**
	 * Uses the context provided by the two `ReportDTO` objects to generate
	 * `SiteChangeDTO` objects representing changes to the WP Environment
	 * between the two reports.
	 *
	 * @return SiteChangeDTO[] Array of SiteChange DTOs.
	 */
	public function generate() {
		return array_merge(
			$this->getPluginChangesDTOs(),
			$this->getCoreUpdatesDTOs(),
			$this->getThemeChangesDTOs(),
			$this->getThemeUpdatesDTOs()
		);
	}

	/**
	 * Returns site changes metadata associated with plugin activations.
	 *
	 * @return SiteChangeDTO[] Site change DTOs.
	 */
	protected function getPluginChangesDTOs() {
		$current_plugins  = $this->currentReportDTO->getWpEnvironment()->getActivePlugins();
		$previous_plugins = $this->previousReportDTO->getWpEnvironment()->getActivePlugins();

		/** @var PluginDTO[] */
		$activations_map = [];

		/** @var PluginDTO[] */
		$deactivations_map = [];

		/** @var PluginDTO[][] */
		$version_changes_map = [];

		foreach ( $current_plugins as $plugin ) {
			$activations_map[ $plugin->getName() ] = $plugin;
		}

		foreach ( $previous_plugins as $plugin ) {
			if ( isset( $activations_map[ $plugin->getName() ] ) ) {
				if ( $activations_map[ $plugin->getName() ]->getVersion() !== $plugin->getVersion() ) {
					$version_changes_map[] = [ $plugin, $activations_map[ $plugin->getName() ] ];
				}
				unset( $activations_map[ $plugin->getName() ] );
			} else {
				$deactivations_map[ $plugin->getName() ] = $plugin;
			}
		}

		$activations_dtos = array_map( function ( PluginDTO $activated_plugin ) {
			$plugin_version_parsed = $this->parseVersionString( $activated_plugin->getVersion() );
			$object_meta           = new ObjectMetaDTO( 'plugin', $activated_plugin->getName() );
			$object_version        = new ObjectVersionDTO(
				$activated_plugin->getVersion(),
				isset( $plugin_version_parsed[0] ) ? intval( $plugin_version_parsed[0] ) : null,
				isset( $plugin_version_parsed[1] ) ? intval( $plugin_version_parsed[1] ) : null,
				isset( $plugin_version_parsed[2] ) ? intval( $plugin_version_parsed[2] ) : null
			);

			return new SiteChangeDTO(
				null,
				null,
				'activate',
				$object_meta,
				$object_version,
				null,
				null
			);
		}, $activations_map );

		$deactivations_dtos = array_map( function ( PluginDTO $activated_plugin ) {
			$plugin_version_parsed = $this->parseVersionString( $activated_plugin->getVersion() );
			$object_meta           = new ObjectMetaDTO( 'plugin', $activated_plugin->getName() );
			$object_version        = new ObjectVersionDTO(
				$activated_plugin->getVersion(),
				isset( $plugin_version_parsed[0] ) ? intval( $plugin_version_parsed[0] ) : null,
				isset( $plugin_version_parsed[1] ) ? intval( $plugin_version_parsed[1] ) : null,
				isset( $plugin_version_parsed[2] ) ? intval( $plugin_version_parsed[2] ) : null
			);

			return new SiteChangeDTO(
				null,
				null,
				'deactivate',
				$object_meta,
				$object_version,
				null,
				null
			);
		}, $deactivations_map );

		$version_changes_dtos = array_map( function ( array $plugin_tuple ) {
			/** @var PluginDTO */
			$previous_plugin = $plugin_tuple[0];

			/** @var PluginDTO */
			$current_plugin = $plugin_tuple[1];

			$plugin_version_parsed = $this->parseVersionString( $current_plugin->getVersion() );
			$object_meta           = new ObjectMetaDTO( 'plugin', $current_plugin->getName() );
			$object_version        = new ObjectVersionDTO(
				$current_plugin->getVersion(),
				isset( $plugin_version_parsed[0] ) ? intval( $plugin_version_parsed[0] ) : null,
				isset( $plugin_version_parsed[1] ) ? intval( $plugin_version_parsed[1] ) : null,
				isset( $plugin_version_parsed[2] ) ? intval( $plugin_version_parsed[2] ) : null
			);

			$previous_plugin_version_parsed = $this->parseVersionString( $previous_plugin->getVersion() );
			$previous_object_meta           = new ObjectMetaDTO( 'plugin', $previous_plugin->getName() );
			$previous_object_version        = new ObjectVersionDTO(
				$previous_plugin->getVersion(),
				isset( $previous_plugin_version_parsed[0] ) ? intval( $previous_plugin_version_parsed[0] ) : null,
				isset( $previous_plugin_version_parsed[1] ) ? intval( $previous_plugin_version_parsed[1] ) : null,
				isset( $previous_plugin_version_parsed[2] ) ? intval( $previous_plugin_version_parsed[2] ) : null
			);

			return new SiteChangeDTO(
				null,
				null,
				version_compare(
					$current_plugin->getVersion(),
					$previous_plugin->getVersion(),
					'>'
				) ? 'update' : 'downgrade',
				$object_meta,
				$object_version,
				$previous_object_meta,
				$previous_object_version
			);
		}, $version_changes_map );

		return array_merge(
			array_values( $activations_dtos ),
			array_values( $deactivations_dtos ),
			array_values( $version_changes_dtos )
		);
	}

	/**
	 * Returns site change DTOs corresponding with theme changes.
	 *
	 * @return SiteChangeDTO[] Site change DTOs.
	 */
	protected function getThemeChangesDTOs() {
		$previous_theme = $this->previousReportDTO->getWpEnvironment()->getActiveTheme();
		$current_theme  = $this->currentReportDTO->getWpEnvironment()->getActiveTheme();

		if ( $previous_theme->getName() === $current_theme->getName() ) {
			return [];
		}

		return [
			new SiteChangeDTO(
				null,
				null,
				'change',
				new ObjectMetaDTO( 'theme', $current_theme->getName() ),
				new ObjectVersionDTO( $current_theme->getVersion() ),
				new ObjectMetaDTO( 'theme', $previous_theme->getName() ),
				new ObjectVersionDTO( $previous_theme->getVersion() )
			),
		];
	}

	/**
	 * Returns site change DTOs corresponding with theme updates.
	 *
	 * @return SiteChangeDTO[] Site change DTOs.
	 */
	protected function getThemeUpdatesDTOs() {
		$site_change_dtos = [];

		$previous_theme = $this->previousReportDTO->getWpEnvironment()->getActiveTheme();
		$current_theme  = $this->currentReportDTO->getWpEnvironment()->getActiveTheme();

		$previous_parent_theme = $this->previousReportDTO->getWpEnvironment()->getParentTheme();
		$current_parent_theme  = $this->currentReportDTO->getWpEnvironment()->getParentTheme();

		if (
			$previous_parent_theme
			&& $current_parent_theme
			&& $previous_parent_theme->getName() === $current_parent_theme->getName()
			&& version_compare(
				$current_parent_theme->getVersion(),
				$previous_parent_theme->getVersion(),
				'<>'
			)
		) {
			$comparison_result = version_compare( $current_parent_theme->getVersion(), $previous_parent_theme->getVersion() );
			$action            = $comparison_result > 0 ? 'update' : 'downgrade';

			$current_parent_theme_version_parsed = $this->parseVersionString( $current_parent_theme->getVersion() );

			$object_meta    = new ObjectMetaDTO( 'parent_theme', $current_parent_theme->getName() );
			$object_version = new ObjectVersionDTO(
				$current_parent_theme->getVersion(),
				isset( $current_parent_theme_version_parsed[0] ) ? intval( $current_parent_theme_version_parsed[0] ) : null,
				isset( $current_parent_theme_version_parsed[1] ) ? intval( $current_parent_theme_version_parsed[1] ) : null,
				isset( $current_parent_theme_version_parsed[2] ) ? intval( $current_parent_theme_version_parsed[2] ) : null
			);

			$previous_parent_theme_version_parsed = $this->parseVersionString( $previous_parent_theme->getVersion() );

			$previous_object_meta    = new ObjectMetaDTO( 'parent_theme', $previous_parent_theme->getName() );
			$previous_object_version = new ObjectVersionDTO(
				$previous_parent_theme->getVersion(),
				isset( $previous_parent_theme_version_parsed[0] ) ? intval( $previous_parent_theme_version_parsed[0] ) : null,
				isset( $previous_parent_theme_version_parsed[1] ) ? intval( $previous_parent_theme_version_parsed[1] ) : null,
				isset( $previous_parent_theme_version_parsed[2] ) ? intval( $previous_parent_theme_version_parsed[2] ) : null
			);

			$site_change_dtos[] = new SiteChangeDTO(
				null,
				null,
				$action,
				$object_meta,
				$object_version,
				$previous_object_meta,
				$previous_object_version
			);
		}

		$active_theme_object_type = ( $previous_parent_theme && $current_parent_theme )
			? 'child_theme'
			: 'theme';

		$comparison_result = version_compare( $current_theme->getVersion(), $previous_theme->getVersion() );

		if (
			0 === $comparison_result
			|| $current_theme->getName() !== $previous_theme->getName()
		) {
			return $site_change_dtos;
		}

		$action                       = $comparison_result > 0 ? 'update' : 'downgrade';
		$current_theme_version_parsed = $this->parseVersionString( $current_theme->getVersion() );

		$object_meta    = new ObjectMetaDTO( $active_theme_object_type, $current_theme->getName() );
		$object_version = new ObjectVersionDTO(
			$current_theme->getVersion(),
			isset( $current_theme_version_parsed[0] ) ? intval( $current_theme_version_parsed[0] ) : null,
			isset( $current_theme_version_parsed[1] ) ? intval( $current_theme_version_parsed[1] ) : null,
			isset( $current_theme_version_parsed[2] ) ? intval( $current_theme_version_parsed[2] ) : null
		);

		$previous_theme_version_parsed = $this->parseVersionString( $previous_theme->getVersion() );

		$previous_object_meta    = new ObjectMetaDTO( $active_theme_object_type, $previous_theme->getName() );
		$previous_object_version = new ObjectVersionDTO(
			$previous_theme->getVersion(),
			isset( $previous_theme_version_parsed[0] ) ? intval( $previous_theme_version_parsed[0] ) : null,
			isset( $previous_theme_version_parsed[1] ) ? intval( $previous_theme_version_parsed[1] ) : null,
			isset( $previous_theme_version_parsed[2] ) ? intval( $previous_theme_version_parsed[2] ) : null
		);

		$site_change_dtos[] = new SiteChangeDTO(
			null,
			null,
			$action,
			$object_meta,
			$object_version,
			$previous_object_meta,
			$previous_object_version
		);

		return $site_change_dtos;
	}

	/**
	 * Returns SiteChangeDTO instances representing changes in WordPress core version.
	 *
	 * @return SiteChangeDTO[] Site Change DTOs.
	 */
	protected function getCoreUpdatesDTOs() {
		$current_core_version  = $this->currentReportDTO->getWpEnvironment()->getCoreVersion();
		$previous_core_version = $this->previousReportDTO->getWpEnvironment()->getCoreVersion();
		$comparison_result     = version_compare( $current_core_version, $previous_core_version );

		if ( 0 === $comparison_result ) {
			return [];
		}
		$action = $comparison_result > 0 ? 'update' : 'downgrade';

		$current_core_version_parsed = $this->parseVersionString( $current_core_version );
		$object_meta                 = new ObjectMetaDTO( 'core', 'WordPress Core' );
		$object_version              = new ObjectVersionDTO(
			$current_core_version,
			isset( $current_core_version_parsed[0] ) ? intval( $current_core_version_parsed[0] ) : null,
			isset( $current_core_version_parsed[1] ) ? intval( $current_core_version_parsed[1] ) : null,
			isset( $current_core_version_parsed[2] ) ? intval( $current_core_version_parsed[2] ) : null
		);

		$previous_core_version_parsed = $this->parseVersionString( $previous_core_version );
		$previous_object_meta         = new ObjectMetaDTO( 'core', 'WordPress Core' );
		$previous_object_version      = new ObjectVersionDTO(
			$previous_core_version,
			isset( $previous_core_version_parsed[0] ) ? intval( $previous_core_version_parsed[0] ) : null,
			isset( $previous_core_version_parsed[1] ) ? intval( $previous_core_version_parsed[1] ) : null,
			isset( $previous_core_version_parsed[2] ) ? intval( $previous_core_version_parsed[2] ) : null
		);

		return [
			new SiteChangeDTO(
				null,
				null,
				$action,
				$object_meta,
				$object_version,
				$previous_object_meta,
				$previous_object_version
			),
		];
	}

	/**
	 * Returns the description of all the different site change types.
	 *
	 * @return Array<Array>
	 */
	public static function getSiteChangeTypes() {
		return [
			[
				'type'     => 'plugin_update',
				'title'    => __( 'Updated plugin', 'nexcess-mapps' ),
				'template' => __( '<%- object_name %> <%- previous_object_version %> to <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'plug',
			],
			[
				'type'     => 'plugin_downgrade',
				'title'    => __( 'Downgraded plugin', 'nexcess-mapps' ),
				'template' => __( '<%- object_name %> <%- previous_object_version %> to <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'plug',
			],
			[
				'type'     => 'plugin_activate',
				'title'    => __( 'Activated plugin', 'nexcess-mapps' ),
				'template' => __( '<%- object_name %> <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'plug',
			],
			[
				'type'     => 'plugin_deactivate',
				'title'    => __( 'Deactivated plugin', 'nexcess-mapps' ),
				'template' => __( '<%- object_name %> <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'plug',
			],
			[
				'type'     => 'core_update',
				'title'    => __( 'WordPress Core update', 'nexcess-mapps' ),
				'template' => __( '<%- previous_object_version %> to <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'refresh',
			],
			[
				'type'     => 'core_downgrade',
				'title'    => __( 'WordPress Core downgrade', 'nexcess-mapps' ),
				'template' => __( '<%- previous_object_version %> to <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'refresh',
			],
			[
				'type'     => 'parent_theme_update',
				'title'    => __( 'Parent theme update', 'nexcess-mapps' ),
				'template' => __( '<%- object_name %> <%- previous_object_version %> to <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'paintbrush',
			],
			[
				'type'     => 'child_theme_update',
				'title'    => __( 'Child theme update', 'nexcess-mapps' ),
				'template' => __( '<%- object_name %> <%- previous_object_version %> to <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'paintbrush',
			],
			[
				'type'     => 'theme_update',
				'title'    => __( 'Theme update', 'nexcess-mapps' ),
				'template' => __( '<%- object_name %> <%- previous_object_version %> to <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'paintbrush',
			],
			[
				'type'     => 'parent_theme_downgrade',
				'title'    => __( 'Parent theme downgrade', 'nexcess-mapps' ),
				'template' => __( '<%- object_name %> <%- previous_object_version %> to <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'paintbrush',
			],
			[
				'type'     => 'child_theme_downgrade',
				'title'    => __( 'Child theme downgrade', 'nexcess-mapps' ),
				'template' => __( '<%- object_name %> <%- previous_object_version %> to <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'paintbrush',
			],
			[
				'type'     => 'theme_downgrade',
				'title'    => __( 'Theme downgrade', 'nexcess-mapps' ),
				'template' => __( '<%- object_name %> <%- previous_object_version %> to <%- object_version %>', 'nexcess-mapps' ),
				'icon'     => 'paintbrush',
			],
			[
				'type'     => 'theme_change',
				'title'    => __( 'Theme change', 'nexcess-mapps' ),
				'template' => __( '<%- previous_object_name %> to <%- object_name %>', 'nexcess-mapps' ),
				'icon'     => 'paintbrush',
			],
		];
	}
}
