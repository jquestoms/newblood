<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Traits\HasChildren;

class LargeFileDTO extends JsonSerializableDTO {
	use HasChildren;

	/** @var int|null */
	protected $id;

	/** @var int|null */
	protected $page_id;

	/** @var string */
	protected $type;

	/** @var int */
	protected $weight;

	/** @var LargeFile\DataDTO */
	protected $data;

	/**
	 * Constructor.
	 *
	 * @param int|null          $id
	 * @param int|null          $page_id
	 * @param string            $type
	 * @param int               $weight
	 * @param LargeFile\DataDTO $data
	 */
	public function __construct( $id, $page_id, $type, $weight, $data ) {
		$this->id      = $id;
		$this->page_id = $page_id;
		$this->type    = $type;
		$this->weight  = $weight;
		$this->data    = $data;
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
	public function getType() {
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getWeight() {
		return $this->weight;
	}

	/**
	 * @return LargeFile\DataDTO
	 */
	public function getData() {
		return $this->data;
	}
}
