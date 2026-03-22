<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `CLS` generator generates insights when the layout on a page
 * is unstable during page load phase.
 */
class CLS extends BaseInsightTypeGenerator {

	/**
	 * Generate the corresponding with `InsightDTO` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			_x(
				'unstable',
				'Substituted as \'qualifier\' into this sentence: <%- qualifier %> page layout on <%- where %>',
				'nexcess-mapps'
			) => function ( $value ) {
				return intval( $value ) > 10 && intval( $value ) <= 25;
			},
			_x(
				'very unstable',
				'Substituted as \'qualifier\' into this sentence: <%- qualifier %> page layout on <%- where %>',
				'nexcess-mapps'
			) => function ( $value ) {
				return intval( $value ) > 25;
			},
		];

		$buckets = $this->groupPagesByMetric(
			'cls',
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
		return 'cls';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Cumulative layout shift',
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
			'Elements may be moving around the page unexpectedly as they load. Google penalizes sites which rate poorly for this.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/cls/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'<%- qualifier %> page layout on <%- where %>',
			'nexcess-mapps'
		);
	}
}
