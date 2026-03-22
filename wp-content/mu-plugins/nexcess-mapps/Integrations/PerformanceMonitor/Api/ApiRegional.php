<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Api;

use Nexcess\MAPPS\Concerns\HasCronEvents;
use Nexcess\MAPPS\Integrations\PerformanceMonitor;
use Nexcess\MAPPS\Routes\PerformanceMonitorAuthRoute;
use Nexcess\MAPPS\Routes\PerformanceMonitorReportRoute;
use Nexcess\MAPPS\Routes\RestRoute;
use Nexcess\MAPPS\Services\Managers\RouteManager;
use StellarWP\PluginFramework\Support\VisualRegressionUrl;

class ApiRegional extends BaseApi {
	use HasCronEvents;

	/**
	 * Action called when all Lighthouse reports are delivered.
	 */
	const CRON_DONE_HOOK = PerformanceMonitor::DATA_PREFIX . 'reports_received';

	/**
	 * Whenever a new batch of pages to sent to the API, we keep a list
	 * of pages in WordPress options and as Lighthouse Reports come back,
	 * we store them in this option until all are available or until
	 * the request times out.
	 *
	 * @var string
	 */
	const PENDING_REQUESTS_OPTION_KEY = PerformanceMonitor::DATA_PREFIX . 'pending_requests';

	/**
	 * A site token seed is a random hexadecimal string that is used to
	 * generate short lived tokens unique to the current site.
	 *
	 * When the API endpoint calls back with the generated Lighthouse report,
	 * it sends the token back to prove it can "write" to the current site.
	 *
	 * @var string
	 */
	const SITE_TOKEN_SEED_OPTION_KEY = PerformanceMonitor::DATA_PREFIX . 'site_token';

	/**
	 * The minimal token time-to-live. The maximum time is a double of this value.
	 *
	 * @var int
	 */
	const TOKEN_TTL_IN_MINUTES = 30 * MINUTE_IN_SECONDS;

	/**
	 * Performance Monitor instance.
	 *
	 * @var PerformanceMonitor
	 */
	protected $performanceMonitor;

	/**
	 * RouteManager instance.
	 *
	 * @var RouteManager
	 */
	protected $routeManager;

	/**
	 * Endpoint URL for requesting Lighthouse reports to be generated.
	 *
	 * @var string
	 */
	protected $endpoint = '';

	/**
	 * Constructor.
	 *
	 * @param PerformanceMonitor $performance_monitor `PerformanceMonitor` instance.
	 * @param RouteManager       $route_manager       `RouteManager` instance.
	 * @param string             $api_endpoint        URL of the API endpoint used to request
	 *                                                Lighthouse reports.
	 */
	public function __construct(
		PerformanceMonitor $performance_monitor,
		RouteManager $route_manager,
		$api_endpoint
	) {
		$this->performanceMonitor = $performance_monitor;
		$this->routeManager       = $route_manager;
		$this->endpoint           = $api_endpoint . '/api/v1/lighthouse/queue';

		/**
		 * This is not an integration on its own, we need to call `addHooks` explicitly.
		 */
		$this->addHooks();
	}

	/**
	 * Retrieve all actions for the integration.
	 *
	 * @return array[]
	 */
	protected function getActions() {
		return [
			[
				self::CRON_DONE_HOOK,
				[ $this, 'done' ],
				10,
			],
		];
	}

	/**
	 * Adds a list of URLs to the queue of URLs to generate Lighthouse reports for.
	 *
	 * @param Array<VisualRegressionUrl> $urls List of URLs to generate Lighthouse reports for.
	 *
	 * @throws \Exception If the API endpoint is not reachable.
	 */
	public function subscribe( array $urls ) {
		$route_urls = $this->getRouteUrls();

		// 1. Create an entry in options to track pending requests.
		$pending = [];
		foreach ( $urls as $url ) {
			$pending[ $url->getPermalink() ] = [];
		}
		update_option( self::PENDING_REQUESTS_OPTION_KEY, $pending, false );

		// 2. Make HTTP calls to request LH reports to be generated.
		foreach ( $urls as $url ) {
			$headers     = [
				'Content-Type' => 'application/json',
			];
			$body_params = array_merge(
				[
					'url'        => $url->getPermalink(),
					'token'      => $this->getCurrentToken(),
					'allRegions' => true,
				],
				$route_urls
			);

			$response = wp_remote_post(
				$this->endpoint,
				[
					'headers' => $headers,
					'body'    => wp_json_encode( $body_params ),
					'timeout' => 20,
				]
			);

			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				throw new \Exception(
					sprintf(
						'Unable to subscribe a URL: %s',
						$url->getPermalink()
					)
				);
			}
		}
	}

	/**
	 * Determine whether all reports have been generated.
	 *
	 * @return bool Returns `true` when all expected LH reports were returned.
	 */
	protected function isDone() {
		$is_done     = true;
		$option_keys = $this->getPendingOptionKeys();

		foreach ( $option_keys as $option_key ) {
			$value   = get_option( $option_key, [] );
			$is_done = $is_done && ! empty( $value['report'] );
		}
		return $is_done;
	}

	/**
	 * Saves a received report in WP options until all reports are in.
	 *
	 * @param string $received_url  The URL the report was generated for.
	 * @param string $report_string The raw Lighthouse report as string.
	 * @param object $queue_info    The global performance queue information.
	 */
	public function saveReport( $received_url, $report_string, $queue_info ) {
		$all_done = true;
		$pending  = get_option( self::PENDING_REQUESTS_OPTION_KEY, [] );

		if (
			! isset( $queue_info->allRegions )
			|| ! isset( $queue_info->region )
			|| ! isset( $queue_info->default )
		) {
			return;
		}

		foreach ( array_keys( $pending ) as $url ) {
			/**
			 * Store each incoming performance report in a separate option to avoid
			 * race conditions when updating the large array and also to improve
			 * cacheability.
			 *
			 * We need to first build a map of region-url pairs and the option names.
			 */
			if ( empty( $pending[ $url ] ) ) {
				$pending[ $url ] = [];
				foreach ( $queue_info->allRegions as $region ) {
					$pending[ $url ][ $region ] = sprintf(
						'%s_%s_%s',
						self::PENDING_REQUESTS_OPTION_KEY,
						$region,
						md5( (string) $url )
					);
				}
				update_option( self::PENDING_REQUESTS_OPTION_KEY, $pending, false );
			}

			if ( $url === $received_url ) {
				$option_name = $pending[ $url ][ $queue_info->region ];
				update_option(
					$option_name,
					[
						'report'  => $report_string,
						'default' => $queue_info->default,
					],
					false
				);
			}
			$all_done = $this->isDone();
		}

		if ( $all_done ) {
			/**
			 * Run the `done` handler in a separate request.
			 *
			 * Any errors related to the process of creating the performance objects in the DB
			 * should not make the current API request fail.
			 *
			 * The PubSub API engine would then retry the API request over and over again,
			 * which may lead to duplicate entries in the timeline.
			 */
			$this->registerCronEvent(
				self::CRON_DONE_HOOK,
				null,
				new \DateTime()
			)->scheduleEvents();

			/**
			 * Spawn cron right away.
			 */
			spawn_cron();
		}
	}

	/**
	 * Removes the pending requests entries and sends all Lighthouse reports
	 * that have arrived for processing.
	 */
	public function done() {
		$pending                        = get_option( self::PENDING_REQUESTS_OPTION_KEY, [] );
		$lighthouse_reports_raw_strings = [];
		$global_performance_reports     = [];

		foreach ( $pending as $url => $regions_data ) {
			foreach ( $regions_data as $region => $option_key ) {
				if ( empty( $global_performance_reports[ $region ] ) ) {
					$global_performance_reports[ $region ] = [];
				}

				$report_data = get_option( $option_key, [] );

				if ( ! empty( $report_data['report'] ) ) {
					$global_performance_reports[ $region ][] = $report_data['report'];

					if ( isset( $report_data['default'] ) && true === $report_data['default'] ) {
						$lighthouse_reports_raw_strings[] = $report_data['report'];
					}
				}
			}
		}

		if ( $lighthouse_reports_raw_strings ) {
			$this->performanceMonitor->processLighthouseReports(
				$lighthouse_reports_raw_strings,
				$global_performance_reports
			);
		}
		$this->deletePendingData();
	}

	/**
	 * Returns a list of option names used to cache incoming LH reports.
	 *
	 * @return string[] List of option names used to cache incoming LH reports.
	 */
	protected function getPendingOptionKeys() {
		$pending_map = get_option( self::PENDING_REQUESTS_OPTION_KEY, [] );
		$option_keys = [];

		foreach ( $pending_map as $url => $region_data ) {
			foreach ( $region_data as $region => $option_key ) {
				$option_keys[] = $option_key;
			}
		}

		return $option_keys;
	}

	/**
	 * Deletes all pending data holding the incoming LH reports.
	 */
	protected function deletePendingData() {
		array_map( 'delete_option', $this->getPendingOptionKeys() );
		delete_option( self::PENDING_REQUESTS_OPTION_KEY );
	}

	/**
	 * Returns a token unique to the site that is valid for a certain time.
	 *
	 * Uses `wp_nonce_tick` internally to retrieve an index of a timeslot
	 * since the Unix epoch. Unlike nonces, however, it doesn't take the currently
	 * logged in user into account, only the token seed string unique to each site.
	 *
	 * @param bool     $previous     Whether to generate the previously valid token.
	 * @param int|null $time_ordinal Integer representing a time interval. In normal operation
	 *                               this value is generated by `wp_nonce_tick`. Useful for tests.
	 *
	 * @return string
	 */
	public function getCurrentToken( $previous = false, $time_ordinal = null ) {
		$token_seed = get_option( self::SITE_TOKEN_SEED_OPTION_KEY, '' );

		if ( ! $token_seed ) {
			$token_seed = $this->generateTokenSeed();
		}

		if ( ! $time_ordinal ) {
			/**
			 * Set the token lifetime to a maximum of `TTL` minutes.
			 */
			$returns_token_ttl = function () {
				return self::TOKEN_TTL_IN_MINUTES;
			};

			add_filter( 'nonce_life', $returns_token_ttl, 10 );
			$time_ordinal = wp_nonce_tick();
			remove_filter( 'nonce_life', $returns_token_ttl, 10 );

			if ( $previous ) {
				$time_ordinal--;
			}
		}

		return wp_hash( sprintf( '%s:%s', $token_seed, $time_ordinal ) );
	}

	/**
	 * Returns the previously valid token.
	 *
	 * @param int|null $time_ordinal Integer representing a time interval. In normal operation
	 *                               this value is generated by `wp_nonce_tick`. Useful for tests.
	 *
	 * @return string
	 */
	public function getPreviousToken( $time_ordinal = null ) {
		return $this->getCurrentToken( true );
	}

	/**
	 * A token is valid when it matches the current token
	 * or a token from previous period.
	 *
	 * Note: WordPress nonces use the same mechanism.
	 *
	 * @param string $token Token to be verified.
	 *
	 * @return bool
	 */
	public function verifyToken( $token ) {
		$valid_tokens = [
			$this->getCurrentToken(),
			$this->getPreviousToken(),
		];

		return in_array( $token, $valid_tokens, true );
	}

	/**
	 * Generates and stores a new site token seed string.
	 *
	 * @return string Generated seed.
	 */
	protected function generateTokenSeed() {
		$random_hex_string = bin2hex( random_bytes( 16 ) );

		update_option( self::SITE_TOKEN_SEED_OPTION_KEY, $random_hex_string, false );

		return $random_hex_string;
	}

	/**
	 * API requests require a couple of endpoint URLs to be provided
	 * with each request. This methods retrieves the relevant
	 * route instances using the `RouteManager` and constructs their URLs.
	 *
	 * @throws \Exception When one of the required routes is not registered.
	 *
	 * @return Array<string> Array of URLs.
	 */
	public function getRouteUrls() {
		$all_routes          = $this->routeManager->getRoutes();
		$required_route_urls = [];

		$required_routes = [
			PerformanceMonitorAuthRoute::class,
			PerformanceMonitorReportRoute::class,
		];

		$required_route_to_callback_key_map = [
			PerformanceMonitorAuthRoute::class   => 'authCallback',
			PerformanceMonitorReportRoute::class => 'reportCallback',
		];

		/**
		 * Retrieve the necessary route URLs.
		 */
		foreach ( $all_routes as $route ) {
			if ( ! $route instanceof RestRoute ) {
				continue;
			}

			$route_classname = get_class( $route );

			if ( in_array( $route_classname, $required_routes, true ) ) {
				$key = $required_route_to_callback_key_map[ $route_classname ];

				$required_route_urls[ $key ] = get_rest_url(
					null,
					$route->getNamespace() . $route->getRoute()
				);
			}
		}

		/**
		 * Error if the resulting number of URLs doesn't match the number of
		 * the originally required routes.
		 */
		if ( count( $required_routes ) !== count( $required_route_urls ) ) {
			throw new \Exception(
				sprintf(
					'Performance Monitor couldn\'t request Lighthouse reports, because one of these required REST API endpoints is not registered: %s',
					join( ', ', array_keys( $required_routes ) )
				)
			);
		}

		return $required_route_urls;
	}
}
