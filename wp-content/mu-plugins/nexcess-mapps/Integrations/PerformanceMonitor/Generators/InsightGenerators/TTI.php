<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `TTI` generator generates insights when the time-to-interactive
 * metric value is over 5000 miliseconds on a page.
 */
class TTI extends BaseInsightTypeGenerator {

	/**
	 * A TTI threshold beyond which a page is considered slow to load.
	 *
	 * @var int
	 */
	const TTI_THRESHOLD = 5000;

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			'failed' => function ( $value ) {
				return intval( $value ) > self::TTI_THRESHOLD;
			},
		];

		$buckets       = $this->groupPagesByMetric(
			'load_time',
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
		return 'time-to-interactive-low';
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
			'Google advises that pages should be interactive on average mobile hardware in less than 5 seconds.',
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
			'<%- where %> taking too long to load',
			'nexcess-mapps'
		);
	}
}
