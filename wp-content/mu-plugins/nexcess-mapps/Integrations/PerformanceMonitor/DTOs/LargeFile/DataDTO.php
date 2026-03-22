<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\LargeFile;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\JsonSerializableDTO;

class DataDTO extends JsonSerializableDTO {
	/** @var string|null */
	protected $url;

	/** @var string|null */
	protected $filename;

	/** @var bool|null */
	protected $old;

	/**
	 * Constructor.
	 *
	 * @param string|null $url
	 * @param string|null $filename
	 * @param bool|null   $old
	 */
	public function __construct( $url = null, $filename = null, $old = null ) {
		$this->url      = $url;
		$this->filename = $filename;
		$this->old      = $old;
	}

	/**
	 * @return string|null
	 */
	public function getUrl() {
		return $this->url;
	}

	/**
	 * @return string|null
	 */
	public function getFilename() {
		return $this->filename;
	}

	/**
	 * @return bool|null
	 */
	public function getOld() {
		return $this->old;
	}

	/**
	 * @param bool $old
	 */
	public function setOld( $old ) {
		$this->old = $old;
	}
}
