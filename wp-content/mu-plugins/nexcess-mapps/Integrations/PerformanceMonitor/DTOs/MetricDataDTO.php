<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs;

class MetricDataDTO extends JsonSerializableDTO {
	/** @var int|null */
	protected $id;

	/** @var int|null */
	protected $page_id;

	/** @var string */
	protected $metric_name;

	/** @var int */
	protected $metric_value;

	/** @var string|null */
	protected $region;

	/** @var bool|null */
	protected $region_default;

	/**
	 * Constructor.
	 *
	 * @param int|null $id
	 * @param int|null $page_id
	 * @param string   $metric_name
	 * @param int      $metric_value
	 * @param string   $region
	 * @param bool     $region_default
	 */
	public function __construct(
		$id,
		$page_id,
		$metric_name,
		$metric_value,
		$region = null,
		$region_default = null
	) {
		$this->id             = $id;
		$this->page_id        = $page_id;
		$this->metric_name    = $metric_name;
		$this->metric_value   = $metric_value;
		$this->region         = $region;
		$this->region_default = $region_default;
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
	public function getPageId() {
		return $this->page_id;
	}

	/**
	 * @return string
	 */
	public function getMetricName() {
		return $this->metric_name;
	}

	/**
	 * @return int
	 */
	public function getMetricValue() {
		return $this->metric_value;
	}

	/**
	 * @return string|null
	 */
	public function getRegion() {
		return $this->region;
	}

	/**
	 * @return bool|null
	 */
	public function getRegionDefault() {
		return $this->region_default;
	}
}
