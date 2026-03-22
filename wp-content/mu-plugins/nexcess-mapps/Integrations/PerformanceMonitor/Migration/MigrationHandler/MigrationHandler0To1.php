<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Migration\MigrationHandler;

use Nexcess\MAPPS\Integrations\PerformanceMonitor;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Database\SaveDTOException;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Database\V1\Client as DatabaseClientV1;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOMappers\LegacyDTOMapper;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\CustomPostTypes;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Model\Report;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\Query\ReportQuery;

/**
 * Class that encapsulates logic needed to migrate
 * performance monitor data from version 0 to 1.
 */
class MigrationHandler0To1 extends MigrationHandlerBase {

	/**
	 * Progress data settings key.
	 *
	 * @var string
	 */
	const PROGRESS_DATA_KEY = 'migration_in_progress_0_to_1';

	/**
	 * Post batch size for migrations.
	 *
	 * @var int
	 */
	const MIGRATION_BATCH_SIZE = 1;

	/**
	 * Batch size for cleanup.
	 *
	 * @var int
	 */
	const CLEANUP_BATCH_SIZE = 10;

	/** @var PerformanceMonitor */
	protected $performanceMonitor;

	/** @var Array */
	protected $progressData;

	/** @var LegacyDTOMapper */
	protected $legacyDtoMapper;

	/** @var DatabaseClientV1 */
	protected $db;

	/** @var ReportQuery */
	protected $reportQuery;

	/**
	 * Constructor.
	 *
	 * @param PerformanceMonitor $performance_monitor
	 * @param LegacyDTOMapper    $legacy_dto_mapper
	 * @param ReportQuery        $report_query
	 */
	public function __construct(
		PerformanceMonitor $performance_monitor,
		LegacyDTOMapper $legacy_dto_mapper,
		ReportQuery $report_query = null
	) {
		parent::__construct( $performance_monitor );

		if ( ! $report_query ) {
			$report_query = new ReportQuery();
		}

		$this->performanceMonitor = $performance_monitor;
		$this->legacyDtoMapper    = $legacy_dto_mapper;
		$this->reportQuery        = $report_query;
		$this->progressData       = $this->getDefaultProgressData();

		CustomPostTypes::registerPostTypes();
	}

	/**
	 * Migrate Performance Monitor database from version 0 to version 1.
	 *
	 * The method is expected to be called repeatedly until it returns
	 * an associative array with the following key:
	 *
	 * 'done' => true
	 *
	 * @return Array
	 */
	public function step() {
		if ( $this->db->tableExists( 'settings' ) ) {
			$progress_data_row = $this->db->getOne( 'settings', 'name', self::PROGRESS_DATA_KEY );
			$progress_data     = $progress_data_row ? json_decode( $progress_data_row['value'], true ) : [];

			if ( ! empty( $progress_data ) ) {
				$this->progressData = $progress_data;
			}
		}

		// `null` means that the migration is not yet started.
		if ( null === $this->progressData['context']['report_ids'] ) {
			$this->firstRun();
		}

		// Empty `report_ids` array means that the migration is done.
		if ( [] === $this->progressData['context']['report_ids'] ) {
			$this->progressData['migration_done'] = true;
		}

		// Empty `remove_posts_ids` array means that the migration is done.
		if ( [] === $this->progressData['context']['remove_posts_ids'] ) {
			$this->progressData['cleanup_done'] = true;
		}

		// Both migration and cleanup are done.
		if ( $this->progressData['migration_done'] && $this->progressData['cleanup_done'] ) {
			$this->progressData['done'] = true;
			$this->db->setDataVersion( $this->db::VERSION );
		} else {
			if ( ! $this->progressData['migration_done'] ) {
				for ( $i = 0; $i < self::MIGRATION_BATCH_SIZE; $i++ ) {
					$report_id = array_shift( $this->progressData['context']['report_ids'] );
					if ( null === $report_id ) {
						break;
					}

					$report = $this->reportQuery->get( $report_id );
					if ( $report ) {
						$this->migrateReport( $report );
					}
				}
			} elseif ( ! $this->progressData['cleanup_done'] ) {
				for ( $i = 0; $i < self::CLEANUP_BATCH_SIZE; $i++ ) {
					$post_id = array_shift( $this->progressData['context']['remove_posts_ids'] );
					if ( $post_id ) {
						wp_delete_post( $post_id, true );
					}
				}
			}

			$this->progressData['current_step']++;
		}

		$this->db->upsertOne( 'settings', [
			'name'  => self::PROGRESS_DATA_KEY,
			'value' => wp_json_encode( $this->progressData ),
		], [ '%s', '%s' ] );

		return $this->progressData;
	}

	/**
	 * Any logic that needs to be run once at the beginning of the migration.
	 */
	protected function firstRun() {
		$this->db->createTables();

		$report_ids   = $this->getReportIds();
		$all_post_ids = $this->getAllPMPostsIds();

		if ( null === $report_ids || null === $all_post_ids ) {
			$this->addError(__(
				'Failed to retrieve performance posts IDs.',
				'nexcess-mapps'
			));
		} else {
			$migration_steps = ceil( count( $report_ids ) / self::MIGRATION_BATCH_SIZE );
			$cleanup_steps   = ceil( count( $all_post_ids ) / self::CLEANUP_BATCH_SIZE );

			$this->progressData['context']['report_ids']       = $report_ids;
			$this->progressData['context']['remove_posts_ids'] = $all_post_ids;
			$this->progressData['total_steps']                 = $migration_steps + $cleanup_steps;

			$rewrites_hash = (string) get_transient( PerformanceMonitor::REWRITES_TRANSIENT_KEY );
			$overview_data = get_option( PerformanceMonitor::OVERVIEW_OPTION_KEY, [] );

			if ( ! empty( $rewrites_hash ) ) {
				$this->db->upsertOne( 'settings', [
					'name'  => PerformanceMonitor::REWRITES_TRANSIENT_KEY,
					'value' => wp_json_encode( $rewrites_hash ),
				], [ '%s', '%s' ] );
			}

			if ( ! empty( $overview_data ) ) {
				$this->db->upsertOne( 'settings', [
					'name'  => PerformanceMonitor::OVERVIEW_OPTION_KEY,
					'value' => wp_json_encode( $overview_data ),
				], [ '%s', '%s' ] );
			}
		}
	}

	/**
	 * Migrates a full report entry along with all its associated data,
	 * i.e. pages, site changes, insights etc.
	 *
	 * @param Report $report
	 */
	protected function migrateReport( Report $report ) {
		$report_dto = $this->legacyDtoMapper->reportToDTO( $report );

		try {
			$this->db->saveDTO( $report_dto );
		} catch ( SaveDTOException $e ) {
			$this->addError( $e->getMessage() );
		}
	}

	/**
	 * Return all custom post type report IDs stored in WordPress tables.
	 *
	 * @return array|null
	 */
	protected function getReportIds() {
		global $wpdb;

		$posts_table_name = $wpdb->prefix . 'posts';
		$query_template   = sprintf(
			'SELECT ID FROM %s WHERE post_type = %%s',
			$posts_table_name
		);

		$report_ids = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( $query_template, [ CustomPostTypes::POST_TYPES['report'] ] ),
			ARRAY_A
		);

		if ( is_array( $report_ids ) ) {
			$report_ids = array_map(
				'intval',
				wp_list_pluck( $report_ids, 'ID' )
			);
		}

		return empty( $report_ids ) ? null : $report_ids;
	}

	/**
	 * Return all custom post IDs related to the Performance Monitor.
	 *
	 * @return array|null
	 */
	protected function getAllPMPostsIds() {
		global $wpdb;

		$posts_table_name = $wpdb->prefix . 'posts';
		$query            = sprintf(
			'SELECT ID FROM %s WHERE post_type IN ( "%s" )',
			$posts_table_name,
			join( '", "', CustomPostTypes::POST_TYPES )
		);

		$post_ids = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$query,
			ARRAY_A
		);

		if ( is_array( $post_ids ) ) {
			$post_ids = array_map(
				'intval',
				wp_list_pluck( $post_ids, 'ID' )
			);
		}

		return empty( $post_ids ) ? null : $post_ids;
	}

	/**
	 * Handle migration errors.
	 *
	 * @param string $error_message Error message.
	 */
	protected function addError( $error_message ) {
		$this->logger->error( $error_message );
		$this->progressData['errors'][] = $error_message;
	}

	/**
	 * Return default progress data.
	 */
	protected function getDefaultProgressData() {
		return [
			'current_step'   => 0,
			'total_steps'    => null,
			'migration_done' => false,
			'cleanup_done'   => false,
			'done'           => false,
			'errors'         => [],
			'context'        => [
				// List of report IDs to be migrated.
				'report_ids'       => null,

				// List of posts to be removed.
				'remove_posts_ids' => null,
			],
		];
	}
}
