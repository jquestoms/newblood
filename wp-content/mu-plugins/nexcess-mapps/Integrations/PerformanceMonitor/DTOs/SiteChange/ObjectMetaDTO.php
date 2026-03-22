<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChange;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\JsonSerializableDTO;

class ObjectMetaDTO extends JsonSerializableDTO {
	/** @var string */
	protected $type;

	/** @var string */
	protected $name = '';

	/**
	 * Constructor.
	 *
	 * @param string $type
	 * @param string $name
	 */
	public function __construct( $type, $name = '' ) {
		$this->type = $type;
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->name;
	}
}
