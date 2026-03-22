<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Traits\HasChildren;

class InsightDTO extends JsonSerializableDTO {
	use HasChildren;

	/** @var int|null */
	protected $id;

	/** @var int|null */
	protected $report_id;

	/** @var string */
	protected $type;

	/** @var Insight\DataDTO */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @param int|null        $id
	 * @param int|null        $report_id
	 * @param string          $type
	 * @param Insight\DataDTO $data
	 */
	public function __construct( $id, $report_id, $type, $data ) {
		$this->id        = $id;
		$this->report_id = $report_id;
		$this->type      = $type;
		$this->data      = $data;
	}

	/**
	 * @return int|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return int|null
	 */
	public function getReportId() {
		return $this->report_id;
	}

	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return Insight\DataDTO
	 */
	public function getData() {
		return $this->data;
	}
}
