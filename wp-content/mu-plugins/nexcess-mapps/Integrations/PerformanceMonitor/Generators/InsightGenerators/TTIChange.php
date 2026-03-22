<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `TTIChange` generates insights for when the overall performance
 * of a page or multiple pages drops.
 */
class TTIChange extends BaseInsightTypeGenerator {

	/**
	 * Only TTI changes larger than this number are considered
	 * significant TTI changes.
	 */
	const SENSITIVITY = 1000;

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			_x(
				'increase',
				'Substituted as \'qualifier\' into this sentence: Significant <%- qualifier %> in load time on <%- where %>',
				'nexcess-mapps'
			) => function ( $value, $previous_value ) {
				return intval( $value ) > intval( $previous_value ) + self::SENSITIVITY;
			},
			_x(
				'decrease',
				'Substituted as \'qualifier\' into this sentence: Significant <%- qualifier %> in load time on <%- where %>',
				'nexcess-mapps'
			) => function ( $value, $previous_value ) {
				return intval( $value ) < intval( $previous_value ) - self::SENSITIVITY;
			},
		];

		$buckets = $this->groupPagesByMetric(
			'load_time',
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
		return 'time-to-interactive-change';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Time To Interactive',
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
			'It\'s essential for good user experience that a page is quick to become both visible and usable.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/tti/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'Significant <%- qualifier %> in load time on <%- where %>',
			'nexcess-mapps'
		);
	}
}
