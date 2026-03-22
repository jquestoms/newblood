<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\Data\VariableDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\JsonSerializableDTO;

class DataDTO extends JsonSerializableDTO {

	/** @var VariableDTO[] */
	protected $variables = [];

	/**
	 * Constructor.
	 *
	 * @param VariableDTO[] $variables
	 */
	public function __construct( array $variables = [] ) {
		$this->variables = $variables;
	}

	/**
	 * @return VariableDTO[]
	 */
	public function getVariables() {
		return $this->variables;
	}
}
