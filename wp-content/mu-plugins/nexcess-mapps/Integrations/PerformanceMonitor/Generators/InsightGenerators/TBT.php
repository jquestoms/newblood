<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `TBT` generator generates insights when the Total Blocking Time
 * metric on a page exceeds 300 ms.
 */
class TBT extends BaseInsightTypeGenerator {

	/**
	 * A TBT threshold beyond which a page is considered slow to load.
	 *
	 * @var int
	 */
	const TBT_THRESHOLD = 300;

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			'failed' => function ( $value ) {
				return intval( $value ) > self::TBT_THRESHOLD;
			},
		];

		$buckets       = $this->groupPagesByMetric(
			'total_blocking_time',
			$filters
		);
		$failing_pages = isset( $buckets['failed'] ) ? $buckets['failed'] : [];

		return count( $failing_pages ) > 0
			? [ $this->pagesIntoInsight( $failing_pages ) ]
			: [];
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	public static function getInsightType() {
		return 'total-blocking-time';
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
			'Long or slow scripts can result in frustrating delays between a user action and the browser\'s response.',
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
			'<%- where %> may feel unresponsive',
			'nexcess-mapps'
		);
	}
}
