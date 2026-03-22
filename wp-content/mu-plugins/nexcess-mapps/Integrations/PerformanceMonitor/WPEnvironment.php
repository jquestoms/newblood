<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor;

/**
 * The `WPEnvironment` class provides information about the WordPress environment.
 */
class WPEnvironment {

	/** @var string */
	protected $coreVersion;

	/** @var array{name: string, version: string}[] */
	protected $activePlugins = [];

	/** @var array{name: string, version: string}|null */
	protected $parentTheme;

	/** @var array{name: string, version: string} */
	protected $currentTheme;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->coreVersion = get_bloginfo( 'version' );

		/**
		 * Plugins.
		 */
		$all_plugins    = get_plugins();
		$active_plugins = get_option( 'active_plugins', [] );

		foreach ( $all_plugins as $plugin_file_path => $plugin_meta ) {
			if ( in_array( $plugin_file_path, $active_plugins, true ) ) {
				$this->activePlugins[] = [
					'name'    => $plugin_meta['Name'],
					'version' => isset( $plugin_meta['Version'] ) ? $plugin_meta['Version'] : '',
				];
			}
		}

		/**
		 * Themes.
		 */
		$parent_theme = get_template();
		$child_theme  = get_stylesheet();

		if ( $parent_theme !== $child_theme ) {
			$this->parentTheme = $this->getThemeInfo( $parent_theme );
		}
		$this->currentTheme = $this->getThemeInfo( $child_theme );
	}

	/**
	 * Get WP core version.
	 *
	 * @return string
	 */
	public function getCoreVersion() {
		return $this->coreVersion;
	}

	/**
	 * Get active plugins.
	 *
	 * @return array{name: string, version: string}[]
	 */
	public function getActivePlugins() {
		return $this->activePlugins;
	}

	/**
	 * Get parent theme.
	 *
	 * @return array{name: string, version: string}|null
	 */
	public function getParentTheme() {
		return $this->parentTheme;
	}

	/**
	 * Get current theme.
	 *
	 * @return array{name: string, version: string}
	 */
	public function getCurrentTheme() {
		return $this->currentTheme;
	}

	/**
	 * @param string $theme_dir_name Name of the theme to retieve information for.
	 *
	 * @return array{name: string, version: string} Theme information.
	 */
	protected function getThemeInfo( $theme_dir_name ) {
		$theme         = wp_get_theme( $theme_dir_name );
		$theme_name    = '';
		$theme_version = '';

		if ( $theme->exists() ) {
			$theme_name = is_string( $theme->get( 'Name' ) )
				? $theme->get( 'Name' )
				: '<Unnamed theme>';

			$theme_version = is_string( $theme->get( 'Version' ) )
				? $theme->get( 'Version' )
				: '';
		}

		return [
			'name'    => $theme_name,
			'version' => $theme_version,
		];
	}
}
