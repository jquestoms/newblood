<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `FIDChange` generates insights for when the FID metric
 * gets worse on a page.
 */
class FIDChange extends BaseInsightTypeGenerator {

	/**
	 * Insight will be generated only when the LCP metric rises by at least
	 * this amount of miliseconds.
	 */
	const SENSITIVITY = 30;

	/**
	 * Generate the corresponding with `InsightDTO` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			_x(
				'decrease',
				'Substituted as \'qualifier\' into this sentence: Significant <%- qualifier %> in responsiveness on <%- where %>',
				'nexcess-mapps'
			) => function ( $value, $previous_value ) {
				return intval( $value ) > intval( $previous_value ) + self::SENSITIVITY;
			},
			_x(
				'increase',
				'Substituted as \'qualifier\' into this sentence: Significant <%- qualifier %> in responsiveness on <%- where %>',
				'nexcess-mapps'
			) => function ( $value, $previous_value ) {
				return intval( $value ) < intval( $previous_value ) - self::SENSITIVITY;
			},
		];

		$buckets = $this->groupPagesByMetric(
			'max_fid',
			$filters
		);

		return $this->bucketsIntoInsights( $buckets );
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	public static function getInsightType() {
		return 'fid-change';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Max Potential FID',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionText() {
		return __(
			'Developers should seek to minimize any delay between a user\'s action and the browser beginning its response.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/fid/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'Significant <%- qualifier %> in responsiveness on <%- where %>',
			'nexcess-mapps'
		);
	}
}
