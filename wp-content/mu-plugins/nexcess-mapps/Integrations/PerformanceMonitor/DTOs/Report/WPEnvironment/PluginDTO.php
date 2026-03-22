<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report\WPEnvironment;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\JsonSerializableDTO;

class PluginDTO extends JsonSerializableDTO {
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * @param string $name
	 * @param string $version
	 */
	public function __construct( $name, $version ) {
		$this->name    = $name;
		$this->version = $version;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}
}
