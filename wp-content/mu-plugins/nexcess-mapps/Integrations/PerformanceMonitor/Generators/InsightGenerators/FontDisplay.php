<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `FontDisplay` generator generates insights when the layout on a page
 * is unstable during page load phase.
 */
class FontDisplay extends BaseInsightTypeGenerator {

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$failing_pages = $this->filterPagesByLighthouseMetric( 'audits/font-display/score', 0 );

		return count( $failing_pages ) > 0
			? [ $this->pagesIntoInsight( $failing_pages ) ]
			: [];
	}

	/**
	 * Returns a path to a key in the Lighthouse report JSON that contains
	 * a list of items (assets, files) that impacted the current metric.
	 *
	 * @return string
	 */
	protected function getLighthouseItemsKeyPath() {
		return 'audits/font-display/details/items';
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	public static function getInsightType() {
		return 'font-display';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Font display',
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
			'Text may be invisible on some browsers and devices until the appropriate font files are loaded.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/font-display/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'Font display problem detected on <%- where %>',
			'nexcess-mapps'
		);
	}
}
