<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Migration;

use Nexcess\MAPPS\Integrations\PerformanceMonitor;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Migration\MigrationHandler\MigrationHandlerBase;

class MigrationManager {

	/** @var int */
	protected $from_version;

	/** @var int */
	protected $to_version;

	/**
	 * Constructor.
	 *
	 * @param int $from_version
	 * @param int $to_version
	 */
	public function __construct( $from_version, $to_version ) {
		$this->from_version = $from_version;
		$this->to_version   = $to_version;
	}

	/**
	 * Returns a migration handler instance.
	 *
	 * @param PerformanceMonitor $performance_monitor
	 *
	 * @return MigrationHandlerBase|\WP_Error
	 */
	public function getMigrationHandler( PerformanceMonitor $performance_monitor ) {
		if ( $this->to_version <= $this->from_version ) {
			return new \WP_Error( 'invalid_version_range', 'Invalid version range.' );
		}

		$pm_namespace            = 'Nexcess\\MAPPS\\Integrations\\PerformanceMonitor';
		$migration_handler_class = sprintf(
			'%s\\Migration\\MigrationHandler\\MigrationHandler%dTo%d',
			$pm_namespace,
			$this->from_version,
			$this->to_version
		);

		if ( ! class_exists( $migration_handler_class ) ) {
			return new \WP_Error( 'missing_migration_handler', 'Missing migration handler.' );
		}

		$legacy_dto_mapper_class = sprintf(
			'%s\\DTOMappers\\LegacyDTOMapper',
			$pm_namespace
		);

		if ( ! class_exists( $legacy_dto_mapper_class ) ) {
			return new \WP_Error( 'missing_dto_model_mapper', 'Missing DTO model mapper.' );
		}

		/** @var MigrationHandlerBase $migration_handler */
		$migration_handler = new $migration_handler_class(
			$performance_monitor,
			new $legacy_dto_mapper_class()
		);

		return $migration_handler;
	}
}
