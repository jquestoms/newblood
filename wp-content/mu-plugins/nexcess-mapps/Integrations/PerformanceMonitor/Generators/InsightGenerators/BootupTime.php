<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `BootupTime` generator generates insights when JavaScript
 * on a page takes long to execute.
 */
class BootupTime extends BaseInsightTypeGenerator {

	/**
	 * Generate the corresponding with `InsightDTO` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			_x(
				'Slow',
				'Substituted as \'qualifier\' into this sentence: <%- qualifier %> script execution detected on <%- where %>',
				'nexcess-mapps'
			) => function ( $value ) {
				return intval( $value ) > 2000 && intval( $value ) <= 3500;
			},
			_x(
				'Very slow',
				'Substituted as \'qualifier\' into this sentence: <%- qualifier %> script execution detected on <%- where %>',
				'nexcess-mapps'
			) => function ( $value ) {
				return intval( $value ) > 3500;
			},
		];

		$bucketed_values = $this->groupPagesByMetric(
			'bootup_time',
			$filters
		);

		return $this->bucketsIntoInsights( $bucketed_values );
	}

	/**
	 * Returns a path to a key in the Lighthouse report JSON that contains
	 * a list of items (assets, files) that impacted the current metric.
	 *
	 * @return string
	 */
	protected function getLighthouseItemsKeyPath() {
		return 'audits/bootup-time/details/items';
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	public static function getInsightType() {
		return 'bootup-time';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Bootup time',
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
			'Large and inefficient script files can unnecessarily increase the time taken for a page to become interactive.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/bootup-time/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'<%- qualifier %> script execution detected on <%- where %>',
			'nexcess-mapps'
		);
	}
}
