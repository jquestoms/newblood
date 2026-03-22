<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Database\V1;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\Database\BaseClient;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Database\SaveDTOException;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOMappers\DatabaseDTOMapper;

// DTOs.
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\LargeFileDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\MetricDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\PageDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChangeDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SourceDTO;

/**
 * Database client for the Performance Monitor plugin.
 *
 * Only for version 1 of the database schema.
 */
class Client extends BaseClient {

	/**
	 * Current database schema version.
	 *
	 * @var int
	 */
	const VERSION = 1;

	/**
	 * List of entities that maps to database tables
	 * created and managed by this client.
	 */
	const ENTITIES = [
		'reports',
		'pages',
		'metrics_data',
		'large_files',
		'sources',
		'insights',
		'site_changes',
		'settings',
	];

	/**
	 * Hierarchy map between entities.
	 */
	const ENTITY_CHILDREN_MAP = [
		'reports'     => [ 'pages', 'insights', 'site_changes' ],
		'pages'       => [ 'metrics_data', 'large_files' ],
		'insights'    => [ 'sources' ],
		'large_files' => [ 'sources' ],
	];

	/**
	 * @var string
	 */
	protected $table_prefix;

	/**
	 * @var DatabaseDTOMapper
	 */
	protected $databaseDTOMapper;

	/**
	 * Constructor.
	 *
	 * @param string            $table_prefix
	 * @param DatabaseDTOMapper $database_dto_mapper
	 */
	public function __construct(
		$table_prefix = 'pm_',
		DatabaseDTOMapper $database_dto_mapper = null
	) {
		parent::__construct(
			$table_prefix,
			self::ENTITIES
		);

		if ( ! $database_dto_mapper ) {
			$database_dto_mapper = new DatabaseDTOMapper();
		}
		$this->databaseDTOMapper = $database_dto_mapper;
	}

	/**
	 * Returns the database client version.
	 *
	 * @return int
	 */
	public function getClientVersion() {
		return self::VERSION;
	}

	/**
	 * Returns the current data version.
	 *
	 * @return int
	 */
	public function getDataVersion() {
		if ( ! $this->tableExists( 'settings' ) ) {
			return 0;
		}
		return intval( $this->getOne( 'settings', 'name', parent::DATA_VERSION_KEY ) );
	}

	/**
	 * Sets the data version.
	 *
	 * @param int $version
	 */
	public function setDataVersion( $version ) {
		$this->upsertOne(
			'settings',
			[
				'name'  => parent::DATA_VERSION_KEY,
				'value' => $version,
			],
			[ '%s', '%s' ]
		);
	}

	/**
	 * Loads a DTO from the database by its ID.
	 *
	 * Optionally also loads the child DTOs of the given entity
	 * when the options `deep` parameter is true.
	 *
	 * @param string $entity
	 * @param int    $id
	 * @param array  $options
	 *
	 * @return object
	 */
	public function loadDTO( $entity, $id, $options = [
		'column_id' => 'id',
		'deep'      => true,
	] ) {
		$dto             = null;
		$db_row          = $this->getOne( $entity, $options['column_id'], $id );
		$singular_entity = (string) preg_replace( '~s$~', '', $entity );
		$mapper_method   = sprintf(
			'%sToDTO',
			lcfirst(
				join( '', array_map( 'ucfirst', explode( '_', $singular_entity ) ) )
			)
		);

		if ( $db_row && method_exists( $this->databaseDTOMapper, $mapper_method ) ) {
			$dto = $this->databaseDTOMapper->{$mapper_method}( $db_row );

			if ( array_key_exists( $entity, self::ENTITY_CHILDREN_MAP ) ) {
				foreach ( self::ENTITY_CHILDREN_MAP[ $entity ] as $child_entity ) {
					$parent_column_id = sprintf( '%s_id', $singular_entity );
					$all_entities     = $this->getAll( $child_entity, $parent_column_id, $id );

					if ( is_array( $all_entities ) ) {
						foreach ( $all_entities as $child_db_row ) {
							$dto->addChild(
								$this->loadDTO( $child_entity, $child_db_row['id'] )
							);
						}
					}
				}
			}
		}
		return $dto;
	}

	/**
	 * Saves the given DTO to the database.
	 *
	 * If $deep is true, the DTO's children will be saved to the database as well.
	 *
	 * @param object $dto
	 * @param array  $options
	 *
	 * @throws SaveDTOException When DTO cannot be saved.
	 *
	 * @return int
	 */
	public function saveDTO( $dto, $options = [
		'deep'      => true,
		'parent'    => null,
		'parent_id' => null,
	] ) {
		$id = null;

		if ( $dto instanceof ReportDTO ) {
			$upsert = [
				'data'   => [
					'timestamp'      => $dto->getTimestamp(),
					'summary'        => wp_json_encode( $dto->getSummary() ),
					'wp_environment' => wp_json_encode( $dto->getWpEnvironment() ),
				],
				'format' => [ '%s', '%s', '%s' ],
			];

			if ( $dto->getId() ) {
				$upsert['data']['id'] = $dto->getId();
				$upsert['format'][]   = '%d';
			}

			$id = $this->upsertOne( 'reports', $upsert['data'], $upsert['format'] );
		}

		if ( $dto instanceof PageDTO ) {
			$upsert = [
				'data'   => [
					'url'  => $dto->getUrl(),
					'name' => $dto->getName(),
				],
				'format' => [ '%s', '%s' ],
			];

			if ( $dto->getId() ) {
				$upsert['data']['id'] = $dto->getId();
				$upsert['format'][]   = '%d';
			}

			if ( $options['parent_id'] ) {
				$upsert['data']['report_id'] = $options['parent_id'];
				$upsert['format'][]          = '%d';
			}

			$id = $this->upsertOne( 'pages', $upsert['data'], $upsert['format'] );

		}

		if ( $dto instanceof MetricDataDTO ) {
			$upsert = [
				'data'   => [
					'metric_name'    => $dto->getMetricName(),
					'metric_value'   => $dto->getMetricValue(),
					'region'         => $dto->getRegion(),
					'region_default' => $dto->getRegionDefault(),
				],
				'format' => [ '%s', '%s', '%s', '%s' ],
			];

			if ( $dto->getId() ) {
				$upsert['data']['id'] = $dto->getId();
				$upsert['format'][]   = '%d';
			}

			if ( $options['parent_id'] ) {
				$upsert['data']['page_id'] = $options['parent_id'];
				$upsert['format'][]        = '%d';
			}

			$id = $this->upsertOne( 'metrics_data', $upsert['data'], $upsert['format'] );
		}

		if ( $dto instanceof LargeFileDTO ) {
			$upsert = [
				'data'   => [
					'type'   => $dto->getType(),
					'weight' => $dto->getWeight(),
					'data'   => wp_json_encode( $dto->getData() ),
				],
				'format' => [ '%s', '%d', '%s' ],
			];

			if ( $dto->getId() ) {
				$upsert['data']['id'] = $dto->getId();
				$upsert['format'][]   = '%d';
			}

			if ( $options['parent_id'] ) {
				$upsert['data']['page_id'] = $options['parent_id'];
				$upsert['format'][]        = '%d';
			}

			$id = $this->upsertOne( 'large_files', $upsert['data'], $upsert['format'] );
		}

		if ( $dto instanceof InsightDTO ) {
			$upsert = [
				'data'   => [
					'type' => $dto->getType(),
					'data' => wp_json_encode( $dto->getData() ),
				],
				'format' => [ '%s', '%s' ],
			];

			if ( $dto->getId() ) {
				$upsert['data']['id'] = $dto->getId();
				$upsert['format'][]   = '%d';
			}

			if ( $options['parent_id'] ) {
				$upsert['data']['report_id'] = $options['parent_id'];
				$upsert['format'][]          = '%d';
			}

			$id = $this->upsertOne( 'insights', $upsert['data'], $upsert['format'] );
		}

		if ( $dto instanceof SiteChangeDTO ) {
			$upsert = [
				'data'   => [
					'action'                  => $dto->getAction(),
					'object_meta'             => wp_json_encode( $dto->getObjectMeta() ),
					'object_version'          => wp_json_encode( $dto->getObjectVersion() ),
					'previous_object_meta'    => wp_json_encode( $dto->getPreviousObjectMeta() ),
					'previous_object_version' => wp_json_encode( $dto->getPreviousObjectVersion() ),
				],
				'format' => [ '%s', '%s', '%s', '%s', '%s' ],
			];

			if ( $dto->getId() ) {
				$upsert['data']['id'] = $dto->getId();
				$upsert['format'][]   = '%d';
			}

			if ( $options['parent_id'] ) {
				$upsert['data']['report_id'] = $options['parent_id'];
				$upsert['format'][]          = '%d';
			}

			$id = $this->upsertOne( 'site_changes', $upsert['data'], $upsert['format'] );
		}

		if ( $dto instanceof SourceDTO ) {
			$upsert = [
				'data'   => [
					'name'      => $dto->getName(),
					'type'      => $dto->getType(),
					'timestamp' => $dto->getTimestamp(),
				],
				'format' => [ '%s', '%s', '%s' ],
			];

			if ( $options['parent'] instanceof LargeFileDTO ) {
				$upsert['data']['large_file_id'] = $options['parent_id'];
				$upsert['format'][]              = '%d';
			} elseif ( $dto->getLargeFileId() ) {
				$upsert['data']['large_file_id'] = $dto->getLargeFileId();
				$upsert['format'][]              = '%d';
			}

			if ( $options['parent'] instanceof InsightDTO ) {
				$upsert['data']['insight_id'] = $options['parent_id'];
				$upsert['format'][]           = '%d';
			} elseif ( $dto->getInsightId() ) {
				$upsert['data']['insight_id'] = $dto->getInsightId();
				$upsert['format'][]           = '%d';
			}

			$id = $this->upsertOne( 'sources', $upsert['data'], $upsert['format'] );
		}

		if ( null === $id ) {
			throw new SaveDTOException( sprintf(
				// translators: %s is the name of the entity DTO class.
				__(
					'Failed to save DTO: %s',
					'nexcess-mapps'
				),
				is_object( $dto ) ? get_class( $dto ) : gettype( $dto )
			) );
		}

		if ( $options['deep'] && method_exists( $dto, 'getChildren' ) ) {
			foreach ( $dto->getChildren() as $child_dto ) {
				$this->saveDTO( $child_dto, [
					'deep'      => $options['deep'],
					'parent'    => $dto,
					'parent_id' => $id,
				] );
			}
		}

		return $id;
	}

	/**
	 * Returns the PM tables schema.
	 */
	public function getTableSchema() {
		$prefix = $this->getTablePrefix();

		return <<<SCHEMA
			CREATE TABLE `{$prefix}reports` (
				`id` BIGINT NOT NULL AUTO_INCREMENT,
				`timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
				`summary` JSON NOT NULL,
				`wp_environment` JSON NOT NULL,
				PRIMARY KEY  (`id`),
				UNIQUE INDEX `id_UNIQUE` (`id` ASC)
			);

			CREATE TABLE `{$prefix}settings` (
				`id` BIGINT NOT NULL AUTO_INCREMENT,
				`name` VARCHAR(60) NULL,
				`value` JSON NULL,
				PRIMARY KEY  (`id`),
				UNIQUE INDEX `id_UNIQUE` (`id` ASC),
				UNIQUE INDEX `name_UNIQUE` (`name` ASC)
			);

			CREATE TABLE `{$prefix}pages` (
				`id` BIGINT NOT NULL AUTO_INCREMENT,
				`report_id` BIGINT NOT NULL,
				`name` VARCHAR(191) NOT NULL,
				`url` VARCHAR(191) NOT NULL,
				PRIMARY KEY  (`id`),
				UNIQUE INDEX `id_UNIQUE` (`id` ASC),
				UNIQUE INDEX `report_id_url_UNIQUE` (`report_id` ASC, `url` ASC),
				INDEX `pages_to_reports_idx` (`report_id` ASC)
			);

			CREATE TABLE `{$prefix}insights` (
				`id` BIGINT NOT NULL AUTO_INCREMENT,
				`report_id` BIGINT NOT NULL,
				`type` VARCHAR(45) NOT NULL,
				`data` JSON NOT NULL,
				PRIMARY KEY  (`id`),
				UNIQUE INDEX `id_UNIQUE` (`id` ASC),
				INDEX `insights_to_reports_idx` (`report_id` ASC),
				INDEX `type_idx` (`type` ASC)
			);

			CREATE TABLE `{$prefix}site_changes` (
				`id` BIGINT NOT NULL AUTO_INCREMENT,
				`report_id` BIGINT NOT NULL,
				`action` VARCHAR(45) NOT NULL,
				`object_meta` JSON NOT NULL,
				`object_version` JSON NULL,
				`previous_object_meta` JSON NULL,
				`previous_object_version` JSON NULL,
				PRIMARY KEY  (`id`),
				UNIQUE INDEX `id_UNIQUE` (`id` ASC),
				INDEX `site_changes_to_reports_idx` (`report_id` ASC)
			);

			CREATE TABLE `{$prefix}metrics_data` (
				`id` BIGINT NOT NULL AUTO_INCREMENT,
				`page_id` BIGINT NOT NULL,
				`metric_name` VARCHAR(45) NOT NULL,
				`metric_value` BIGINT NOT NULL,
				`region` VARCHAR(45) NULL,
				`region_default` TINYINT NULL DEFAULT 1,
				PRIMARY KEY  (`id`),
				UNIQUE INDEX `id_UNIQUE` (`id` ASC),
				INDEX `metrics_to_pages_idx` (`page_id` ASC),
				INDEX `metric_name_idx` (`metric_name` ASC)
			);

			CREATE TABLE `{$prefix}large_files` (
				`id` BIGINT NOT NULL AUTO_INCREMENT,
				`page_id` BIGINT NOT NULL,
				`type` VARCHAR(45) NOT NULL,
				`weight` BIGINT NOT NULL,
				`data` JSON NOT NULL,
				PRIMARY KEY  (`id`),
				UNIQUE INDEX `id_UNIQUE` (`id` ASC),
				INDEX `large_files_to_pages_idx` (`page_id` ASC)
			);

			CREATE TABLE `{$prefix}sources` (
				`id` BIGINT NOT NULL AUTO_INCREMENT,
				`large_file_id` BIGINT NULL,
				`insight_id` BIGINT NULL,
				`name` VARCHAR(191) NULL,
				`type` VARCHAR(45) NULL,
				`timestamp` TIMESTAMP NULL,
				PRIMARY KEY  (`id`),
				UNIQUE INDEX `id_UNIQUE` (`id` ASC),
				INDEX `sources_to_insights_idx` (`insight_id` ASC),
				INDEX `sources_to_large_files_idx` (`large_file_id` ASC)
			);
SCHEMA;
	}
}
