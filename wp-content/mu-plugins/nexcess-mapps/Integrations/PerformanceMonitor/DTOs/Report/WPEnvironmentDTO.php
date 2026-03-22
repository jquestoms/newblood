<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\JsonSerializableDTO;

/**
 * Class WPEnvironment.
 */
class WPEnvironmentDTO extends JsonSerializableDTO {
	/**
	 * @var string
	 */
	protected $core_version;

	/**
	 * @var WPEnvironment\ThemeDTO|null
	 */
	protected $parent_theme;

	/**
	 * @var WPEnvironment\ThemeDTO
	 */
	protected $active_theme;

	/**
	 * @var Array<WPEnvironment\PluginDTO>
	 */
	protected $active_plugins = [];

	/**
	 * WPEnvironment constructor.
	 *
	 * @param string                         $core_version
	 * @param WPEnvironment\ThemeDTO         $parent_theme
	 * @param WPEnvironment\ThemeDTO         $active_theme
	 * @param Array<WPEnvironment\PluginDTO> $active_plugins
	 */
	public function __construct(
		$core_version,
		WPEnvironment\ThemeDTO $parent_theme = null,
		WPEnvironment\ThemeDTO $active_theme,
		array $active_plugins
	) {
		$this->core_version   = $core_version;
		$this->parent_theme   = $parent_theme;
		$this->active_theme   = $active_theme;
		$this->active_plugins = $active_plugins;
	}

	/**
	 * @return string
	 */
	public function getCoreVersion() {
		return $this->core_version;
	}

	/**
	 * @return WPEnvironment\ThemeDTO|null
	 */
	public function getParentTheme() {
		return $this->parent_theme;
	}

	/**
	 * @return WPEnvironment\ThemeDTO
	 */
	public function getActiveTheme() {
		return $this->active_theme;
	}

	/**
	 * @return Array<WPEnvironment\PluginDTO>
	 */
	public function getActivePlugins() {
		return $this->active_plugins;
	}
}
