<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Migration\MigrationHandler;

use Nexcess\MAPPS\Integrations\PerformanceMonitor;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Database\BaseClient;
use Nexcess\MAPPS\Services\Logger;

/**
 * A base class for migration handlers.
 */
abstract class MigrationHandlerBase {

	/** @var BaseClient */
	protected $db;

	/** @var PerformanceMonitor */
	protected $performance_monitor;

	/** @var Logger */
	protected $logger;

	/**
	 * Constructor.
	 *
	 * @param PerformanceMonitor $performance_monitor
	 * @param Logger             $logger
	 */
	public function __construct(
		PerformanceMonitor $performance_monitor,
		Logger $logger = null
	) {
		$this->performance_monitor = $performance_monitor;
		$this->db                  = $performance_monitor->getDb();

		if ( null === $logger ) {
			$logger = new Logger();
		}
		$this->logger = $logger;
	}

	/**
	 * Performs one step of the migration.
	 *
	 * The method is expected to be called repeatedly until it returns
	 * an associative array with the following key:
	 *
	 * 'done' => true
	 *
	 * @return Array
	 */
	abstract public function step();
}
