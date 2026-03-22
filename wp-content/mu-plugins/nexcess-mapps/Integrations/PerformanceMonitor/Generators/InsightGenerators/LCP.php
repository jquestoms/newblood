<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `LCP` generator generates insights when the largest element
 * above the fold renders in 2500+ miliseconds.
 */
class LCP extends BaseInsightTypeGenerator {

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			_x(
				'slow',
				'Substituted as \'qualifier\' into this sentence: Key element is <%- qualifier %> to display on <%- where %>',
				'nexcess-mapps'
			) => function ( $value ) {
				return intval( $value ) > 2500 && intval( $value ) <= 4000;
			},
			_x(
				'very slow',
				'Substituted as \'qualifier\' into this sentence: Key element is <%- qualifier %> to display on <%- where %>',
				'nexcess-mapps'
			) => function ( $value ) {
				return intval( $value ) > 4000;
			},
		];

		$buckets = $this->groupPagesByMetric(
			'lcp_time',
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
		return 'lcp-slow';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Largest Contentful Paint',
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
			'This is one of Google\'s Core Web Vital metrics: poor performance will affect your ranking in search results.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/lcp/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'Key element is <%- qualifier %> to display on <%- where %>',
			'nexcess-mapps'
		);
	}
}
