<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `TBTChange` generator generates insights when the Total Blocking Time
 * metric on a page exceeds 300 ms.
 */
class TBTChange extends BaseInsightTypeGenerator {

	/**
	 * Only TBT changes larger than this number are considered
	 * significant TBT changes.
	 *
	 * @var int
	 */
	const SENSITIVITY = 50;

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			_x(
				'deterioration',
				'Substituted as \'qualifier\' into this sentence: Significant <%- qualifier %> in responsiveness of <%- where %>',
				'nexcess-mapps'
			) => function ( $value, $previous_value ) {
				return intval( $value ) > intval( $previous_value ) + self::SENSITIVITY;
			},
			_x(
				'improvement',
				'Substituted as \'qualifier\' into this sentence: Significant <%- qualifier %> in responsiveness of <%- where %>',
				'nexcess-mapps'
			) => function ( $value, $previous_value ) {
				return intval( $value ) < intval( $previous_value ) - self::SENSITIVITY;
			},
		];

		$buckets = $this->groupPagesByMetric(
			'total_blocking_time',
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
		return 'total-blocking-time-change';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Total Blocking Time',
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
			'Developers should minimize thread-blocking scripts; as little as a third of a second\'s delay can harm user experience.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/tbt/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'Significant <%- qualifier %> in responsiveness of <%- where %>',
			'nexcess-mapps'
		);
	}
}
