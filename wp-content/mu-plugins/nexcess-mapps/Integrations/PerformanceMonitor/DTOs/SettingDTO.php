<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs;

class SettingDTO extends JsonSerializableDTO {
	/** @var int */
	protected $id;

	/** @var mixed|null */
	protected $value;

	/**
	 * Constructor.
	 *
	 * @param int   $id
	 * @param mixed $value
	 */
	public function __construct( $id, $value = null ) {
		$this->id    = $id;
		$this->value = $value;
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed|null
	 */
	public function getValue() {
		return $this->value;
	}
}
