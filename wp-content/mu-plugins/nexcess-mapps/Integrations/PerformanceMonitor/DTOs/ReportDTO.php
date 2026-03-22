<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Traits\HasChildren;

class ReportDTO extends JsonSerializableDTO {
	use HasChildren;

	/** @var int|null */
	protected $id;

	/** @var string */
	protected $timestamp;

	/** @var Report\SummaryDTO */
	protected $summary;

	/** @var Report\WPEnvironmentDTO */
	protected $wpEnvironment;

	/**
	 * Constructor.
	 *
	 * @param int|null                $id
	 * @param string|null             $timestamp
	 * @param Report\SummaryDTO       $summary
	 * @param Report\WPEnvironmentDTO $wp_environment
	 */
	public function __construct( $id, $timestamp, $summary, $wp_environment ) {
		$default_timestamp = function_exists( 'wp_date' ) ? wp_date( 'Y-m-d H:i:s' ) : gmdate( 'Y-m-d H:i:s' );

		$this->id            = $id;
		$this->timestamp     = empty( $timestamp ) ? (string) $default_timestamp : $timestamp;
		$this->summary       = $summary;
		$this->wpEnvironment = $wp_environment;
	}

	/**
	 * @return int|null
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getTimestamp() {
		return $this->timestamp;
	}

	/**
	 * @return Report\SummaryDTO
	 */
	public function getSummary() {
		return $this->summary;
	}

	/**
	 * @return Report\WPEnvironmentDTO
	 */
	public function getWpEnvironment() {
		return $this->wpEnvironment;
	}
}
