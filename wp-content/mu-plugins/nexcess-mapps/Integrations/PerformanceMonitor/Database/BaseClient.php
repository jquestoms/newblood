<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Database;

/**
 * Base class all Database client classes extend.
 */
abstract class BaseClient {

	/**
	 * Current database schema version.
	 *
	 * @var int
	 */
	const VERSION = 0;

	/**
	 * Data version key. Used to store the current data version in the database.
	 *
	 * @var string
	 */
	const DATA_VERSION_KEY = 'data_version';

	/**
	 * @var string
	 */
	protected $table_prefix;

	/**
	 * List of entities that maps to database tables
	 * created and managed by the client.
	 *
	 * @var string[]
	 */
	protected $entities;

	/**
	 * Constructor.
	 *
	 * @param string   $table_prefix
	 * @param string[] $entities
	 */
	public function __construct(
		$table_prefix = 'pm_',
		array $entities = []
	) {
		$this->table_prefix = $table_prefix;
		$this->entities     = $entities;
	}

	/**
	 * Returns the database client version.
	 *
	 * @return int
	 */
	abstract public function getClientVersion();

	/**
	 * Returns the PM tables schema.
	 */
	abstract public function getTableSchema();

	/**
	 * Returns the real data schema version, i.e. when the data is fully
	 * migrated, data version will be equal to the client version.
	 *
	 * If data is not fully migrated yet, the data version will be lower than
	 * the client version.
	 *
	 * @return int
	 */
	abstract public function getDataVersion();

	/**
	 * Sets the real data version.
	 *
	 * @param int $version
	 */
	abstract public function setDataVersion( $version );

	/**
	 * Checks if the database table exist.
	 *
	 * @param string $entity
	 *
	 * @return bool
	 */
	public function tableExists( $entity ) {
		global $wpdb;

		$table_name = $this->getTableName( $entity );

		return $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$table_name
			)
		) === $table_name;
	}

	/**
	 * Returns a single row from the database.
	 *
	 * @param string     $entity
	 * @param string     $column
	 * @param int|string $value
	 *
	 * @return array|null
	 */
	public function getOne( $entity, $column, $value ) {
		global $wpdb;

		$table_name     = $this->getTableName( $entity );
		$query_template = sprintf(
			'SELECT * FROM %s WHERE %s = %s',
			$table_name,
			$column,
			is_string( $value ) ? '%s' : '%d'
		);

		return $wpdb->get_row(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( $query_template, $value ),
			ARRAY_A
		);
	}

	/**
	 * Returns all matching rows from the database.
	 *
	 * @param string     $entity
	 * @param string     $column
	 * @param int|string $value
	 * @param mixed      $default
	 *
	 * @return array|null
	 */
	public function getAll( $entity, $column, $value, $default = null ) {
		global $wpdb;

		$table_name     = $this->getTableName( $entity );
		$query_template = sprintf(
			'SELECT * FROM %s WHERE %s = %s',
			$table_name,
			$column,
			is_string( $value ) ? '%s' : '%d'
		);

		$value = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( $query_template, $value ),
			ARRAY_A
		);

		if ( null === $value ) {
			return $default;
		}
		return $value;
	}

	/**
	 * Returns a page worth of reports.
	 *
	 * @param int $page
	 * @param int $per_page
	 *
	 * @return array
	 */
	public function getReportsPage( $page = 1, $per_page = 10 ) {
		global $wpdb;

		$table_name     = $this->getTableName( 'reports' );
		$query_template = sprintf(
			'SELECT * FROM %s ORDER BY `timestamp` DESC LIMIT %%d, %%d',
			$table_name
		);

		$result = $wpdb->get_results(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->prepare( $query_template, ( $page - 1 ) * $per_page, $per_page ),
			ARRAY_A
		);

		if ( ! is_array( $result ) ) {
			return [];
		}
		return $result;
	}

	/**
	 * Returns the number of table rows for a given entity.
	 *
	 * @param string $entity
	 *
	 * @return int
	 */
	public function getCount( $entity ) {
		global $wpdb;

		$table_name = $this->getTableName( $entity );
		$query      = sprintf(
			'SELECT COUNT(*) FROM %s',
			$table_name
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return intval( $wpdb->get_var( $query ) );
	}

	/**
	 * Returns settings value.
	 *
	 * @param string $name    Settings name.
	 * @param mixed  $default Default value.
	 *
	 * @return mixed
	 */
	public function getSetting( $name, $default = null ) {
		$setting_row = $this->getOne( 'settings', 'name', $name );

		if ( isset( $setting_row['value'] ) ) {
			return json_decode( $setting_row['value'], true );
		}
		return $default;
	}

	/**
	 * Inserts or updates a single row in the database.
	 *
	 * @param string $entity
	 * @param array  $data
	 * @param array  $formats
	 *
	 * @return int|null ID of the inserted row, or null on failure.
	 *
	 * Note that wpdb->replace is not used because it does not update the row
	 * in place, instead it removes the row and re-inserts it with updated data.
	 */
	public function upsertOne( $entity, $data, $formats ) {
		global $wpdb;

		$table_name = $this->getTableName( $entity );
		$columns    = array_keys( $data );

		$update_pairs = array_map(
			function ( $column_name, $format ) {
				return sprintf( '%s = %s', $column_name, $format );
			},
			$columns,
			$formats
		);

		$query_template = sprintf(
			'INSERT INTO %s (%s) VALUES(%s) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id), %s',
			$table_name,
			join( ', ', $columns ),
			join( ', ', $formats ),
			join( ', ', $update_pairs )
		);

		$prepare_args = array_merge( [ $query_template ], array_values( $data ), array_values( $data ) );

		$prepare_method = [ $wpdb, 'prepare' ];
		$is_inserted    = is_callable( $prepare_method )
			&& boolval(
				$wpdb->query(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					call_user_func_array( $prepare_method, $prepare_args )
				)
			);

		if ( false === $is_inserted ) {
			return null;
		}
		return $wpdb->insert_id;
	}

	/**
	 * Returns the Performance Monitor tables' prefix.
	 */
	protected function getTablePrefix() {
		global $wpdb;

		return sprintf( '%s%sv%d_', $wpdb->prefix, $this->table_prefix, $this->getClientVersion() );
	}

	/**
	 * Returns the table name for the given entity.
	 *
	 * @param string $entity
	 *
	 * @return string The table name for the given entity.
	 */
	protected function getTableName( $entity ) {
		return $this->getTablePrefix() . $entity;
	}

	/**
	 * CREATE or ALTER the PM tables.
	 */
	public function createTables() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$schema = $this->getTableSchema();
		dbDelta( $schema );
	}

	/**
	 * Drops all tables managed by the client.
	 */
	public function dropTables() {
		global $wpdb;

		foreach ( $this->entities as $entity ) {
			$table_name = $this->getTableName( $entity );
			$query      = sprintf( 'DROP TABLE IF EXISTS %s', $table_name );

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->query( $query );
		}
	}
}
