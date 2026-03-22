<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Traits\HasChildren;

class PageDTO extends JsonSerializableDTO {
	use HasChildren;

	/** @var int|null */
	protected $id;

	/** @var int|null */
	protected $report_id;

	/** @var string */
	protected $name;

	/** @var string */
	protected $url;

	/**
	 * Constructor.
	 *
	 * @param int|null $id
	 * @param int|null $report_id
	 * @param string   $name
	 * @param string   $url
	 */
	public function __construct( $id, $report_id, $name, $url ) {
		$this->id        = $id;
		$this->report_id = $report_id;
		$this->name      = $name;
		$this->url       = $url;
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
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getUrl() {
		return $this->url;
	}
}
