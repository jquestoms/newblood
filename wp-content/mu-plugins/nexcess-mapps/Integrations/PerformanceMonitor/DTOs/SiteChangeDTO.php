<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs;

class SiteChangeDTO extends JsonSerializableDTO {
	/** @var int|null */
	protected $id;

	/** @var int|null */
	protected $report_id;

	/** @var string */
	protected $action;

	/** @var SiteChange\ObjectMetaDTO */
	protected $object_meta;

	/** @var SiteChange\ObjectVersionDTO|null */
	protected $object_version;

	/** @var SiteChange\ObjectMetaDTO|null */
	protected $previous_object_meta;

	/** @var SiteChange\ObjectVersionDTO|null */
	protected $previous_object_version;

	/**
	 * Constructor.
	 *
	 * @param int|null                         $id
	 * @param int|null                         $report_id
	 * @param string                           $action
	 * @param SiteChange\ObjectMetaDTO         $object_meta
	 * @param SiteChange\ObjectVersionDTO|null $object_version
	 * @param SiteChange\ObjectMetaDTO|null    $previous_object_meta
	 * @param SiteChange\ObjectVersionDTO|null $previous_object_version
	 */
	public function __construct(
		$id,
		$report_id,
		$action,
		$object_meta,
		$object_version = null,
		$previous_object_meta = null,
		$previous_object_version = null
	) {
		$this->id                      = $id;
		$this->report_id               = $report_id;
		$this->action                  = $action;
		$this->object_meta             = $object_meta;
		$this->object_version          = $object_version;
		$this->previous_object_meta    = $previous_object_meta;
		$this->previous_object_version = $previous_object_version;
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
	public function getAction() {
		return $this->action;
	}

	/**
	 * @return SiteChange\ObjectMetaDTO
	 */
	public function getObjectMeta() {
		return $this->object_meta;
	}

	/**
	 * @return SiteChange\ObjectVersionDTO|null
	 */
	public function getObjectVersion() {
		return $this->object_version;
	}

	/**
	 * @return SiteChange\ObjectMetaDTO|null
	 */
	public function getPreviousObjectMeta() {
		return $this->previous_object_meta;
	}

	/**
	 * @return SiteChange\ObjectVersionDTO|null
	 */
	public function getPreviousObjectVersion() {
		return $this->previous_object_version;
	}
}
