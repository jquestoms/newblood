<?php

/**
 * The `PeformanceMonitorRoute` returns data used to initialize
 * the UI React App and fill the timeline with a page of results.
 */

namespace Nexcess\MAPPS\Routes;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\Api\BaseApi;
use stdClass;
use WP_Error;

class PerformanceMonitorReportRoute extends RestRoute {

	/**
	 * Supported HTTP methods for this route.
	 *
	 * @var string[]
	 */
	protected $methods = [
		'POST',
	];

	/**
	 * The REST route.
	 *
	 * @var string
	 */
	protected $route = '/performance-monitor/report';

	/**
	 * @var \Nexcess\MAPPS\Integrations\PerformanceMonitor\Api\BaseApi
	 */
	protected $api;

	/**
	 * Flag used to determine the set of checks needed to performed
	 * to ensure validity of the data being sent to the API.
	 *
	 * @var bool
	 */
	protected $isRegionsApi = false;

	/**
	 * Constructor.
	 *
	 * @param BaseApi $api            Instance of the Api class.
	 * @param bool    $is_regions_api Flag used to determine whether a Regions API call is being made.
	 */
	public function __construct( BaseApi $api, $is_regions_api = false ) {
		$this->api          = $api;
		$this->isRegionsApi = $is_regions_api;
	}

	/**
	 * Determine whether or not the current request is authorized.
	 *
	 * This corresponds to the "permission_callback" argument within the WP REST API.
	 *
	 * @link https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/#permissions-callback
	 *
	 * @param \WP_REST_Request $request The REST request.
	 *
	 * @return bool|\WP_Error
	 */
	public function authorizeRequest( \WP_REST_Request $request ) {
		$bearer_token = self::getBearerToken( $request );

		if ( is_string( $bearer_token ) ) {
			return $this->api->verifyToken( $bearer_token );
		}
		return $bearer_token;
	}

	/**
	 * The primary callback to execute for the route.
	 *
	 * @param \WP_REST_Request $request The request object.
	 */
	public function handleRequest( \WP_REST_Request $request ) {
		$report_string = $request->get_body();
		$report_json   = json_decode( $report_string );

		if ( $this->isRegionsApi ) {
			if ( empty( $report_json->queue ) ) {
				return new WP_Error(
					'missing_queue_information',
					'Missing Queue Information',
					[ 'status' => 401 ]
				);
			}
		} else {
			/**
			 * To satisfy the `saveReport` method signature, we need to ensure that a `queue`
			 * property is present in the JSON object even if it is only used when
			 * regional performance monitoring is enabled.
			 */
			$report_json->queue = new stdClass();
		}

		if ( empty( $report_json->requestedUrl ) ) {
			return new WP_Error(
				'incorrect_lighthouse_report_format',
				'Incorrect Lighthouse Report Format',
				[ 'status' => 400 ]
			);
		}

		$this->api->saveReport( $report_json->requestedUrl, $report_string, $report_json->queue );
		return true;
	}
}
