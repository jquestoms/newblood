<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `ResourceTotalWeightChange` generator generates insights related to the
 * increase of the total size of the page and its assets.
 */
class ResourceTotalWeightChange extends BaseInsightTypeGenerator {

	/**
	 * Only report on increase in size that is larger than 300 kB.
	 *
	 * @var int
	 */
	const SENSITIVITY = 300000;

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			'failed' => function ( $value ) {
				return intval( $value ) > self::SENSITIVITY;
			},
		];

		$buckets       = $this->groupPagesByMetric(
			'weight_diff',
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
		return 'resource-total-weight';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Resource Summary',
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
			'Be sure that you\'re getting sufficient benefit from the extra load time resulting from your changes.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/resource-summary/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'Significant increase in total weight of <%- where %>',
			'nexcess-mapps'
		);
	}
}
