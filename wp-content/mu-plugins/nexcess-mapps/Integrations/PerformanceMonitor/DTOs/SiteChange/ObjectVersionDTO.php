<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChange;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\JsonSerializableDTO;

class ObjectVersionDTO extends JsonSerializableDTO {
	/** @var string */
	protected $version = '';

	/** @var int|null */
	protected $major;

	/** @var int|null */
	protected $minor;

	/** @var int|null */
	protected $patch;

	/**
	 * Constructor.
	 *
	 * @param string   $version
	 * @param int|null $major
	 * @param int|null $minor
	 * @param int|null $patch
	 */
	public function __construct( $version = '', $major = null, $minor = null, $patch = null ) {
		$this->version = $version;
		$this->major   = $major;
		$this->minor   = $minor;
		$this->patch   = $patch;
	}

	/**
	 * @return string
	 */
	public function getVersion() {
		return $this->version;
	}

	/**
	 * @return int|null
	 */
	public function getMajor() {
		return $this->major;
	}

	/**
	 * @return int|null
	 */
	public function getMinor() {
		return $this->minor;
	}

	/**
	 * @return int|null
	 */
	public function getPatch() {
		return $this->patch;
	}
}
