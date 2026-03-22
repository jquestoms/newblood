<?php

/**
 * Performance Monitor API UI and insights engine.
 */

namespace Nexcess\MAPPS\Integrations;

use Nexcess\MAPPS\Concerns\HasAdminPages;
use Nexcess\MAPPS\Concerns\HasAssets;
use Nexcess\MAPPS\Concerns\HasCronEvents;
use Nexcess\MAPPS\Concerns\InvokesCli;
use Nexcess\MAPPS\Concerns\ManagesGroupedOptions;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Api\Api;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Api\ApiRegional;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DashboardWidget;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Database\SaveDTOException;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Database\V1\Client as DatabaseClient;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOMappers\LighthouseDTOMapper;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\GlobalPerformance;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Legacy\CustomPostTypes;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\LighthouseReport;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\UIData;
use Nexcess\MAPPS\Modules\Telemetry;
use Nexcess\MAPPS\Modules\VisualComparison;
use Nexcess\MAPPS\Routes\PerformanceMonitorAuthRoute;
use Nexcess\MAPPS\Routes\PerformanceMonitorDataRoute;
use Nexcess\MAPPS\Routes\PerformanceMonitorMuteRoute;
use Nexcess\MAPPS\Routes\PerformanceMonitorReportRoute;
use Nexcess\MAPPS\Services\Logger;
use Nexcess\MAPPS\Services\Managers\RouteManager;
use Nexcess\MAPPS\Services\Options;
use Nexcess\MAPPS\Settings;
use StellarWP\PluginFramework\Services\FeatureFlags;

class PerformanceMonitor extends Integration {
	use HasAdminPages;
	use HasAssets;
	use HasCronEvents;
	use InvokesCli;
	use ManagesGroupedOptions;

	/**
	 * Prefix used to 'namespace' all data items stored in the database, i.e.
	 * custom post types, meta items, options, ...
	 */
	const DATA_PREFIX = 'pm_';

	/**
	 * Daily database migration cron.
	 */
	const DAILY_PPM_DB_MIGRATION_CRON_ACTION = self::DATA_PREFIX . 'daily_db_migration';

	/**
	 * Overview data contain average performance scores, load times
	 * and the number of site changes.
	 */
	const OVERVIEW_OPTION_KEY = self::DATA_PREFIX . 'overview';

	/**
	 * The integration caches a hash of modified times of files that
	 * affect permalinks, i.e. files that register routes and CPTs.
	 *
	 * @var string
	 */
	const REWRITES_TRANSIENT_KEY = self::DATA_PREFIX . 'rewrites_hash';

	/**
	 * Action called daily by Cron.
	 *
	 * @var string
	 */
	const CRON_HOOK = self::DATA_PREFIX . 'request_lighthouse_reports';

	/**
	 * Action called when the API was unreachable on a first try.
	 *
	 * @var string
	 */
	const CRON_RETRY_HOOK = self::DATA_PREFIX . 'retry_request_lighthouse_reports';

	/**
	 * Action called when the request for Lighthouse reports times out.
	 *
	 * @var string
	 */
	const CRON_CANCEL_HOOK = self::DATA_PREFIX . 'cancel_request_lighthouse_reports';

	/**
	 * The option for disabling the performance monitor.
	 */
	const OPTION_NAME = 'nexcess_mapps_performance_monitor';

	/**
	 * The key used in the telemetry report which contains the relevant integration info.
	 */
	const TELEMETRY_FEATURE_KEY = 'performance_monitor';

	/**
	 * @var mixed
	 */
	protected $api;

	/**
	 * @var \StellarWP\PluginFramework\Services\FeatureFlags
	 */
	public $featureFlags;

	/**
	 * @var \Nexcess\MAPPS\Services\Logger
	 */
	protected $logger;

	/**
	 * @var \Nexcess\MAPPS\Services\Options
	 */
	protected $options;

	/**
	 * @var \Nexcess\MAPPS\Services\Managers\RouteManager
	 */
	protected $routeManager;

	/**
	 * @var \Nexcess\MAPPS\Settings
	 */
	protected $settings;

	/**
	 * @var \Nexcess\MAPPS\Integrations\PerformanceMonitor\UIData
	 */
	protected $uiData;

	/**
	 * @var \Nexcess\MAPPS\Modules\VisualComparison
	 */
	protected $visualComparison;

	/**
	 * @var Array<string, string>
	 */
	protected $urlNamesMap = [];

	/**
	 * @var DatabaseClient
	 */
	protected $db;

	/**
	 * @param \Nexcess\MAPPS\Settings                          $settings
	 * @param \Nexcess\MAPPS\Modules\VisualComparison          $visual_comparison
	 * @param \Nexcess\MAPPS\Services\Managers\RouteManager    $route_manager
	 * @param \Nexcess\MAPPS\Services\Logger                   $logger
	 * @param \StellarWP\PluginFramework\Services\FeatureFlags $feature_flags
	 * @param \Nexcess\MAPPS\Services\Options                  $options
	 */
	public function __construct(
		Settings $settings,
		VisualComparison $visual_comparison,
		RouteManager $route_manager,
		Logger $logger,
		FeatureFlags $feature_flags,
		Options $options
	) {
		$this->settings         = $settings;
		$this->visualComparison = $visual_comparison;
		$this->routeManager     = $route_manager;
		$this->logger           = $logger;
		$this->featureFlags     = $feature_flags;
		$this->options          = $options;
	}

	/**
	 * Determine whether or not this integration should be loaded.
	 *
	 * @return bool Whether or not this integration be loaded in this environment.
	 */
	public function shouldLoadIntegration() {
		return ( $this->settings->is_production_site
				&& (bool) $this->settings->performance_monitor_endpoint
				&& ! $this->settings->is_temp_domain
				) || ( $this->settings->is_qa_environment && (bool) $this->settings->performance_monitor_endpoint );
	}

	/**
	 * Set up the performance monitor integration.
	 */
	public function setup() {
		if ( $this->getPerformanceMonitorSetting() ) {
			$this->addHooks();
		}

		// Manually adding our action outstside of the addHooks() method, as we
		// need it to always be hooked in, no matter what the setting, so we can
		// trigger things when the setting is changed.
		add_action( 'Nexcess\MAPPS\Options\Update', [ $this, 'maybeClearCronEvents' ], 10, 3 );

		$this->registerOption();
	}

	/**
	 * Retrieve all filters for the integration.
	 *
	 * @return array[]
	 */
	protected function getFilters() {
		return [
			[ Telemetry::REPORT_DATA_FILTER, [ $this, 'addFeatureToTelemetry' ] ],
		];
	}

	/**
	 * Retrieve all actions for the integration.
	 *
	 * @return array[]
	 */
	protected function getActions() {
		return [
			[
				'init',
				[ $this, 'initializeIntegration' ],
				11,
			],
			[
				self::CRON_HOOK,
				[ $this, 'requestLighthouseReports' ],
				10,
			],
			[
				self::CRON_RETRY_HOOK,
				[ $this, 'requestLighthouseReports' ],
				10,
				1,
			],
			[
				self::CRON_CANCEL_HOOK,
				[ $this, 'cancelLighthouseReportsRequest' ],
				10,
			],
			[
				self::DAILY_PPM_DB_MIGRATION_CRON_ACTION,
				[ $this, 'maybeRunDbMigration' ],
				10,
			],
			[
				'wp_dashboard_setup',
				[ $this, 'registerDashboardWidget' ],
				10,
			],
		];
	}

	/**
	 * Adds feature integration information to the telemetry report.
	 *
	 * @param array[] $report The gathered report data.
	 *
	 * @return array[] The $report array.
	 */
	public function addFeatureToTelemetry( array $report ) {
		$report['features'][ self::TELEMETRY_FEATURE_KEY ] = $this->getPerformanceMonitorSetting();

		return $report;
	}

	/**
	 * Enable performance monitoring.
	 */
	public function enablePerformanceMonitor() {
		$this->getOption()->set( 'performance_monitor_is_enabled', true )->save();
	}

	/**
	 * Disable performance monitoring.
	 */
	public function disablePerformanceMonitor() {
		$this->getOption()->set( 'performance_monitor_is_enabled', false )->save();
		$this->clearCronEvents();
	}

	/**
	 * Clears scheduled hook for performance monitor.
	 */
	public function clearCronEvents() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Clear the cron events when our option changes from enabled to disabled.
	 * Hooked into Nexcess\MAPPS\Options\Update.
	 *
	 * @param array $key   The key of the option that was updated.
	 * @param mixed $value The old value of the option.
	 * @param mixed $prev  The new value of the option.
	 */
	public function maybeClearCronEvents( $key, $value, $prev ) {
		if (
			is_array( $key )
			&& isset( $key[0] )
			&& 'performance_monitor_is_enabled' === $key[1]
			&& false === $value
			&& $value !== $prev
		) {
			$this->clearCronEvents();
		}
	}

	/**
	 * Get current setting for performance monitoring.
	 *
	 * @return bool
	 */
	public function getPerformanceMonitorSetting() {
		return $this->getOption()->performance_monitor_is_enabled;
	}

	/**
	 * Add a toggle to the settings page.
	 */
	public function registerOption() {
		$this->options->addOption(
			[ self::OPTION_NAME, 'performance_monitor_is_enabled' ],
			'checkbox',
			__( 'Enable Performance Monitoring', 'nexcess-mapps' ),
			[
				'description' => __( 'The Nexcess Plugin Performance Monitor watches your site every day so you don’t just see the problem - you know how to fix it.', 'nexcess-mapps' ),
				'default'     => true,
			]
		);
	}

	/**
	 * Initialize the whole integration.
	 */
	public function initializeIntegration() {
		$this->initializeDatabase();
		$this->maybeScheduleDbMigration();

		/**
		 * Always register routes that might be called by the Lighthouse API.
		 */
		$this->initializeApiRoutes();

		if ( current_user_can( 'manage_options' ) || wp_doing_cron() ) {
			CustomPostTypes::registerPostTypes();

			// Retrieve user provided names for URLs. Defined in the Visual Comparison tool.
			$this->urlNamesMap = array_reduce(
				$this->visualComparison->getUrls(),
				function ( $urls, $url ) {
					$permalink_https          = preg_replace( '~^http\:~', 'https:', $url->getPermalink() );
					$urls[ $permalink_https ] = $url->getDescription();
					return $urls;
				},
				[]
			);

			$this->initializeCronTask();
			$this->initializeUIRoutes();
			$this->registerReportsPage();
			$this->maybeFlushPermalinks();

			/**
			 * @todo Remove after QA phase is over.
			 *
			 * This is for testing only. When a `pm-force-request` query parameter is
			 * present in the current URL, we hit the performance monitor
			 * endpoint and request a performance data for the current site.
			 */
			if ( filter_input( INPUT_GET, 'pm-force-request' ) ) {
				$this->api->done();
				$this->requestLighthouseReports();
			}
		}
	}

	/**
	 * Registers a cron task to retrieve performance reports
	 * from the API daily.
	 */
	protected function initializeCronTask() {
		if ( $this->getPerformanceMonitorSetting() ) {
			$this->registerCronEvent(
				self::CRON_HOOK,
				'daily',
				$this->getCronTime()
			)->scheduleEvents();
		}
	}

	/**
	 * Requests Lighthouse reports from the API.
	 *
	 * @param bool $is_retry Whether this is a retry.
	 */
	public function requestLighthouseReports( $is_retry = false ) {
		if ( ! $this->getPerformanceMonitorSetting() ) {
			return;
		}

		if ( $this->isMigrationNeeded() ) {
			/**
			 * If data migration is needed, we want to delay the request until
			 * it's done.
			 *
			 * We are using the CRON_RETRY_HOOK name to schedule a one-off later attempt,
			 * but we don't mark it as a retry, because it's not a retry.
			 */
			$this->registerCronEvent(
				self::CRON_RETRY_HOOK,
				null, // No interval = One time event.
				new \DateTime( '+ 60 minutes', wp_timezone() ) // Try in 60 minutes.
			)->scheduleEvents();

			return;
		}

		/**
		 * It's possible that due to network conditions the API is not going
		 * to provide all the requested Lighthouse Reports.
		 *
		 * In that case we want to stop waiting and process all the data
		 * that did arrive.
		 */
		$this->registerCronEvent(
			self::CRON_CANCEL_HOOK,
			null, // No interval = One time event.
			new \DateTime( '+ 30 minutes', wp_timezone() ) // Cancel in 30 minutes.
		)->scheduleEvents();

		try {
			$this->api->subscribe( $this->visualComparison->getUrls() );
		} catch ( \Exception $e ) {
			if ( ! $is_retry ) {
				$message = sprintf(
					'%s. Will re-attempt to make the API request in 5 minutes.',
					$e->getMessage()
				);
				$this->logger->warning( $message );

				$this->registerCronEvent(
					self::CRON_RETRY_HOOK,
					null, // No interval = One time event.
					new \DateTime( '+ 5 minutes', wp_timezone() ),
					[ true ] // Indicates that this is a retry.
				)->scheduleEvents();
			} else {
				$this->logger->error( $e->getMessage() );
				return;
			}
		}
	}

	/**
	 * Stops waiting for any additional responses from the API and
	 * processes the data that has arrived so far.
	 */
	public function cancelLighthouseReportsRequest() {
		$this->api->done();
	}

	/**
	 * Get a random time during the night when the daily cron event
	 * should be executed.
	 *
	 * Assigns the time based on the current site's hostname.
	 *
	 * @return \DateTimeInterface
	 */
	protected function getCronTime() {
		$site_url      = get_home_url();
		$site_hostname = wp_parse_url( $site_url, PHP_URL_HOST );
		$site_hash     = md5( $site_hostname );

		/**
		 * We are converting the last 4 digits of a MD5 hash string to
		 * a decimal number, e.g. af01 -> 44801.
		 *
		 * We then divide this number by 5, which yields a number
		 * between 0 and 13107. Now, 13107 seconds ~= 3.5 hours,
		 * which gives us a cron start time between 1am and 4.30am.
		 */
		$seconds_delta_hex    = substr( $site_hash, -4 );
		$seconds_delta        = base_convert( $seconds_delta_hex, 16, 10 );
		$seconds_delta_scaled = round( $seconds_delta / 5 );

		$cron_datetime = new \DateTime( 'tomorrow 1am', wp_timezone() );
		$cron_datetime->modify( sprintf( '+ %d seconds', $seconds_delta_scaled ) );

		return $cron_datetime;
	}

	/**
	 * Flush permalinks once when any of the files that define custom
	 * endpoints change.
	 */
	protected function maybeFlushPermalinks() {
		$observed_files = [
			__FILE__,
			__DIR__ . '/PerformanceMonitor/CustomPostTypes.php',
		];

		$file_mtimes = [];
		foreach ( $observed_files as $filename ) {
			if ( ! file_exists( $filename ) ) {
				continue;
			}
			$file_mtimes[] = filemtime( $filename );
		}

		$previous_hash = (string) get_transient( self::REWRITES_TRANSIENT_KEY );
		$current_hash  = md5( join( '-', $file_mtimes ) );

		if ( $previous_hash !== $current_hash ) {
			set_transient( self::REWRITES_TRANSIENT_KEY, $current_hash );
			flush_rewrite_rules();
		}
	}

	/**
	 * Schedule a cron to upgrade the database when needed.
	 */
	public function maybeScheduleDbMigration() {
		if ( ! $this->getPerformanceMonitorSetting() ) {
			return;
		}

		// Make sure the feature is enabled for this site.
		if ( ! $this->featureFlags->enabled( 'plugin-performance-monitor-db-migrate' ) ) {
			return;
		}

		// No migration needed at this time.
		if ( ! $this->isMigrationNeeded() ) {
			return;
		}

		$this->registerCronEvent(
			self::DAILY_PPM_DB_MIGRATION_CRON_ACTION,
			'daily'
		)->scheduleEvents();
	}

	/**
	 * Runs the database migration when called via cron.
	 */
	public function maybeRunDbMigration() {
		$this->makeCommand( 'wp nxmapps performance-monitor migrate' )
			->setPriority( 10 )
			->setTimeout( HOUR_IN_SECONDS )
			->execute()
			->getOutput();

		// Clear the cron upon successful migration.
		$timestamp = wp_next_scheduled( self::DAILY_PPM_DB_MIGRATION_CRON_ACTION );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::DAILY_PPM_DB_MIGRATION_CRON_ACTION );
		}
	}

	/**
	 * Registers custom routes used by the UI app.
	 */
	protected function initializeUIRoutes() {
		$this->uiData = new UIData(
			$this,
			$this->routeManager,
			new GlobalPerformance()
		);

		$routes = [
			new PerformanceMonitorDataRoute( $this->uiData ),
			new PerformanceMonitorMuteRoute(),
		];

		foreach ( $routes as $route ) {
			$this->routeManager->addRoute( $route );
		}
	}

	/**
	 * Initializes database.
	 */
	protected function initializeDatabase() {
		$this->db = new DatabaseClient();
		$this->maybeCreateTables();
	}

	/**
	 * Create the database tables if not already done.
	 */
	protected function maybeCreateTables() {
		if ( get_option( 'nexcess_ppm_should_install_tables', false ) ) {
			$this->db->createTables();
			delete_option( 'nexcess_ppm_should_install_tables' );
		}
	}

	/**
	 * Registers custom routes that provide data to and receive
	 * data from the Lighthouse API.
	 */
	protected function initializeApiRoutes() {
		if ( $this->isRegionalApiEnabled() ) {
			$this->api = new ApiRegional(
				$this,
				$this->routeManager,
				$this->settings->performance_monitor_endpoint
			);
		} else {
			$this->api = new Api(
				$this,
				$this->routeManager,
				$this->settings->performance_monitor_endpoint
			);
		}

		$routes = [
			new PerformanceMonitorAuthRoute( $this->api ),
			new PerformanceMonitorReportRoute( $this->api, $this->isRegionalApiEnabled() ),
		];

		foreach ( $routes as $route ) {
			$this->routeManager->addRoute( $route );
		}
	}

	/**
	 * Adds the Performance Monitor page to the submenu of Nexcess.
	 */
	public function registerReportsPage() {
		add_action( 'admin_menu', function () {
			add_submenu_page(
				Dashboard::ADMIN_MENU_SLUG,
				_x( 'Performance Monitor', 'page title', 'nexcess-mapps' ),
				_x( 'Performance Monitor', 'menu item title', 'nexcess-mapps' ),
				'manage_options',
				'mapps-performance-monitor',
				[ $this, 'renderMenuPage' ]
			);
		} );
	}

	/**
	 * Renders the Performance Monitor template that contains
	 * the root element for React application that displays
	 * performance reports data.
	 */
	public function renderMenuPage() {
		// Load Nexcess admin script and the Performance Monitor React app.
		wp_enqueue_script( 'nexcess-mapps-admin' );

		$this->enqueueScript( 'nexcess-mapps-performance-monitor', 'performance-monitor.js', [ 'wp-element', 'underscore' ] );
		$this->enqueueStyle( 'nexcess-mapps-performance-monitor', 'performance-monitor.css', [], 'screen' );

		$this->injectScriptData(
			'nexcess-mapps-performance-monitor',
			'performanceMonitor',
			$this->uiData->getAll()
		);

		$this->renderTemplate( 'performancemonitor' );
	}

	/**
	 * Receive an array of all daily reports (one per each observed page)
	 * and extract and save all relevant data from them.
	 *
	 * @param string[] $lighthouse_reports_raw_strings Array of strings.
	 * @param Array[]  $global_performance_reports     Array of global performance reports.
	 */
	public function processLighthouseReports(
		array $lighthouse_reports_raw_strings,
		array $global_performance_reports
	) {
		/**
		 * @var LighthouseReport[]
		 */
		$lighthouse_reports = [];

		/**
		 * @var Array<string, array>
		 */
		$global_data_lighthouse_reports = array_combine(
			array_keys( $global_performance_reports ),
			array_fill( 0, count( $global_performance_reports ), [] )
		);

		/**
		 * @var Array<string, int>
		 */
		$overview_data = [];

		/**
		 * Generate `LighthouseReport` instances.
		 */
		foreach ( $lighthouse_reports_raw_strings as $report_json_string ) {
			$lighthouse_reports[] = new LighthouseReport( $report_json_string );
		}

		/**
		 * Generate `LighthouseReport` instances for global data.
		 */
		foreach ( $global_performance_reports as $region => $raw_reports ) {
			foreach ( $raw_reports as $raw_report ) {
				$global_data_lighthouse_reports[ $region ][] = new LighthouseReport( (string) $raw_report );
			}
		}

		/**
		 * Retrieve all report DTOs from the last 7 days.
		 *
		 * @var ReportDTO[]
		 */
		$latest_report_dtos = array_map(
			function ( $db_row ) {
				return $this->db->loadDTO( 'reports', $db_row['id'] );
			},
			$this->db->getReportsPage( 1, 7 )
		);

		/**
		 * Initialize the DTO mapper.
		 */
		$lighthouse_dto_mapper = new LighthouseDTOMapper(
			$lighthouse_reports,
			$this,
			$global_data_lighthouse_reports,
			$latest_report_dtos
		);
		$new_report_dto        = $lighthouse_dto_mapper->getReportDTO( $this->urlNamesMap );

		try {
			$this->db->saveDTO( $new_report_dto );
		} catch ( SaveDTOException $e ) {
			$this->logger->error( $e->getMessage() );
		}
	}

	/**
	 * Saves the overview data as site options.
	 *
	 * @param int[] $overview_data Overview data to be stored in options.
	 */
	protected function setOverviewData( array $overview_data ) {
		update_option( self::OVERVIEW_OPTION_KEY, $overview_data, false );
	}

	/**
	 * Returns the overview data displayed in the plugin dashboard.
	 *
	 * @return int[]
	 */
	public function getOverviewData() {
		$default_overview_data = [
			'changes'   => 0,
			'insights'  => 0,
			'score'     => 0,
			'load_time' => 0,
		];
		$overview_data         = $this->db->getSetting( self::OVERVIEW_OPTION_KEY, $default_overview_data );

		$active_plugins           = get_option( 'active_plugins', [] );
		$overview_data['plugins'] = count( $active_plugins );

		return $overview_data;
	}

	/**
	 * Instantiates the dashboard widget.
	 */
	public function registerDashboardWidget() {
		if ( current_user_can( 'manage_options' ) ) {
			new DashboardWidget( $this );
		}
	}

	/**
	 * Returns a map between site URLs and their user provided descriptions (names).
	 *
	 * @return Array<string, string> URL name map.
	 */
	public function getUrlNamesMap() {
		return $this->urlNamesMap;
	}

	/**
	 * Get the host location.
	 *
	 * @return string Data center name & location.
	 */
	public function getHostLocation() {
		$hostname = gethostname();
		if ( ! $hostname ) {
			return 'unknown';
		}

		$hostname = explode( '.', $hostname );

		return isset( $hostname[1] ) ? $hostname[1] : 'unknown';
	}

	/**
	 * Get the data center name.
	 *
	 * @param string $location The location of the host, e.g. 'us-west-1'.
	 *
	 * @return string Host name.
	 */
	public function getDataCenterName( $location ) {
		$datacenter_names = [
			'us‐midwest‐1' => 'US Midwest Southfield, MI',
			'us‐west‐1'    => 'US West San Jose, CA',
			'us‐south‐1'   => 'US South Miami, FL',
			'uk‐south‐1'   => 'UK South Surrey, UK',
			'uk‐south‐2'   => 'UK South West Sussex, UK',
			'nl‐west‐1'    => 'The Netherlands West Amsterdam, NL',
			'au‐south‐1'   => 'Australia South Sydney, NSW',
		];

		return isset( $datacenter_names[ $location ] ) ? $datacenter_names[ $location ] : 'Unknown';
	}

	/**
	 * Get the data center name and location.
	 *
	 * @return array Data center name & location.
	 */
	public function getDataCenterNameAndLocation() {
		$location = $this->getHostLocation();
		$name     = $this->getDataCenterName( $location );

		return [
			'name'     => $name,
			'location' => $location,
		];
	}

	/**
	 * Returns `true` if the Performance Monitor uses the Regional Performance
	 * feature.
	 *
	 * I.e. If it uses the API endpoint that returns performance data from multiple
	 * locations around the world as opposed to fetching performance data from a single
	 * location (default mode).
	 */
	public function isRegionalApiEnabled() {
		return false;
	}

	/**
	 * Returns Performance Monitor settings.
	 */
	public static function getSettings() {
		return [
			'database' => [
				'version' => null,
			],
		];
	}

	/**
	 * Returns the DB client.
	 *
	 * @return DatabaseClient
	 */
	public function getDb() {
		return $this->db;
	}

	/**
	 * Returns `true` when the stored migration data needs to be migrated
	 * to the newest version, `false` otherwise.
	 *
	 * @return bool
	 */
	public function isMigrationNeeded() {
		// No migration is needed if the flag is not enabled on this site.
		if ( ! $this->featureFlags->enabled( 'plugin-performance-monitor-db-migrate' ) ) {
			return false;
		}

		$reports = get_posts( [
			'post_type'        => self::DATA_PREFIX . 'report',
			'suppress_filters' => true,
			'posts_per_page'   => 1,
		] );
		return ! empty( $reports ) && $this->db->getClientVersion() !== $this->db->getDataVersion();
	}
}
