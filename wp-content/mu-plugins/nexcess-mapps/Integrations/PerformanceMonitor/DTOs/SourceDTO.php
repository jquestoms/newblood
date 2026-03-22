<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs;

class SourceDTO extends JsonSerializableDTO {
	/** @var int|null */
	protected $large_file_id;

	/** @var int|null */
	protected $insight_id;

	/** @var string|null */
	protected $type;

	/** @var string|null */
	protected $name;

	/** @var string|null */
	protected $timestamp;

	/**
	 * Constructor.
	 *
	 * @param int|null    $large_file_id
	 * @param int|null    $insight_id
	 * @param string|null $type
	 * @param string|null $name
	 * @param string|null $timestamp
	 */
	public function __construct(
		$large_file_id = null,
		$insight_id = null,
		$type = null,
		$name = null,
		$timestamp = null
	) {
		$this->large_file_id = $large_file_id;
		$this->insight_id    = $insight_id;
		$this->type          = $type;
		$this->name          = $name;
		$this->timestamp     = $timestamp;
	}

	/**
	 * @return int|null
	 */
	public function getLargeFileId() {
		return $this->large_file_id;
	}

	/**
	 * @return int|null
	 */
	public function getInsightId() {
		return $this->insight_id;
	}

	/**
	 * @return string|null
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @return string|null
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string|null
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}
}
