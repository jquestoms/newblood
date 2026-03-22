<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `Performance` generator generates insights when the
 * overall performance of a page or multiple pages is poor or in need of improvement.
 */
class Performance extends BaseInsightTypeGenerator {

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			_x(
				'is poor',
				'Substituted as \'qualifier\' into this sentence: Performance on <%- where %> <%- qualifier %>',
				'nexcess-mapps'
			) => function ( $value ) {
				return is_numeric( $value ) && intval( $value ) < 50;
			},
			_x(
				'needs improvement',
				'Substituted as \'qualifier\' into this sentence: Performance on <%- where %> <%- qualifier %>',
				'nexcess-mapps'
			) => function ( $value ) {
				return intval( $value ) >= 50 && intval( $value ) < 90;
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
		return 'low-performance';
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
			'This is likely to have a negative effect on user experience, and may also lead to a lower ranking in search results.',
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
			'Performance on <%- where %> <%- qualifier %>',
			'nexcess-mapps'
		);
	}
}
