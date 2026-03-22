<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\Data;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\JsonSerializableDTO;

class VariableDTO extends JsonSerializableDTO {
	/** @var string */
	protected $variable;

	/** @var string */
	protected $value;

	/**
	 * Constructor.
	 *
	 * @param string $variable
	 * @param string $value
	 */
	public function __construct( $variable, $value ) {
		$this->variable = $variable;
		$this->value    = $value;
	}

	/**
	 * @return string
	 */
	public function getVariable() {
		return $this->variable;
	}

	/**
	 * @return string
	 */
	public function getValue() {
		return $this->value;
	}
}
