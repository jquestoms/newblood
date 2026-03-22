<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `PerformanceDrop` generates insights for when the overall performance
 * of a page or multiple pages drops.
 */
class PerformanceChange extends BaseInsightTypeGenerator {

	/**
	 * Only score changes larger than this number are considered
	 * significant performance changes.
	 */
	const SENSITIVITY = 5;

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			_x(
				'up',
				'Substituted as \'qualifier\' into this sentence: Performance is <%- qualifier %> significantly on <%- where %>',
				'nexcess-mapps'
			) => function ( $value, $previous_value ) {
				return intval( $value ) > intval( $previous_value ) + self::SENSITIVITY;
			},
			_x(
				'down',
				'Substituted as \'qualifier\' into this sentence: Performance is <%- qualifier %> significantly on <%- where %>',
				'nexcess-mapps'
			) => function ( $value, $previous_value ) {
				return intval( $value ) < intval( $previous_value ) - self::SENSITIVITY;
			},
		];

		$buckets = $this->groupPagesByMetric(
			'score',
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
		return 'performance-change';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Performance',
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
			'This may be a result of code or content changes you have made; or external factors outside your control.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/performance-scoring/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'Performance is <%- qualifier %> significantly on <%- where %>',
			'nexcess-mapps'
		);
	}
}
