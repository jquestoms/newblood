<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Report;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\JsonSerializableDTO;

class SummaryDTO extends JsonSerializableDTO {
	/**
	 * @var int
	 */
	protected $average_score;

	/**
	 * @var int
	 */
	protected $average_score_diff;

	/**
	 * @var int
	 */
	protected $changes;

	/**
	 * @var int
	 */
	protected $insights;

	/**
	 * Constructor.
	 *
	 * @param int $average_score      Average performance score.
	 * @param int $average_score_diff Average performance score difference.
	 * @param int $changes            Number of site changes.
	 * @param int $insights           Number of insights generated.
	 */
	public function __construct(
		$average_score = 0,
		$average_score_diff = 0,
		$changes = 0,
		$insights = 0
	) {
		$this->average_score      = $average_score;
		$this->average_score_diff = $average_score_diff;
		$this->changes            = $changes;
		$this->insights           = $insights;
	}

	/**
	 * @return int
	 */
	public function getAverageScore() {
		return $this->average_score;
	}

	/**
	 * @return int
	 */
	public function getAverageScoreDiff() {
		return $this->average_score_diff;
	}

	/**
	 * @return int
	 */
	public function getChanges() {
		return $this->changes;
	}

	/**
	 * @return int
	 */
	public function getInsights() {
		return $this->insights;
	}

	/**
	 * @param int $changes Number of site changes.
	 */
	public function setChanges( $changes ) {
		$this->changes = $changes;
	}

	/**
	 * @param int $insights Number of insights generated.
	 */
	public function setInsights( $insights ) {
		$this->insights = $insights;
	}
}
