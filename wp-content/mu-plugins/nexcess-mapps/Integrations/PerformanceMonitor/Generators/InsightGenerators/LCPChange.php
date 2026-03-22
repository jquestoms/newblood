<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `LCPChange` generates insights for when the LCP metric
 * of a page or multiple pages gets worse.
 */
class LCPChange extends BaseInsightTypeGenerator {

	/**
	 * Insight will be generated only when the LCP metric rises by at least
	 * this amount of miliseconds.
	 */
	const SENSITIVITY = 650;

	/**
	 * Issue an insight only when the current LCP time is at least
	 * this amount of miliseconds.
	 */
	const LCP_HEALTHY_THRESHOLD = 2500;

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			'failed' => function ( $value, $previous_value ) {
				return intval( $value ) > self::LCP_HEALTHY_THRESHOLD
					&& intval( $value ) > intval( $previous_value ) + self::SENSITIVITY;
			},
		];

		$buckets       = $this->groupPagesByMetric(
			'lcp_time',
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
		return 'lcp-change';
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
			'Research shows this is an important factor in how users perceive the overall speed of your site.',
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
			'Key element is significantly slower to display on <%- where %>',
			'nexcess-mapps'
		);
	}
}
