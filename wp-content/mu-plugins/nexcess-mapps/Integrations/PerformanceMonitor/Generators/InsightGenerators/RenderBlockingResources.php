<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `RenderBlockingResources` generator generates insights when there are
 * render blocking resources on the page.
 */
class RenderBlockingResources extends BaseInsightTypeGenerator {

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			'failed' => function ( $value ) {
				return intval( $value ) > 0;
			},
		];

		$buckets       = $this->groupPagesByMetric(
			'render_blocking_time',
			$filters
		);
		$failing_pages = isset( $buckets['failed'] ) ? $buckets['failed'] : [];

		$qualifier = $this->isRepeated()
			? _x(
				'detected on',
				'Substituted as \'qualifier\' into this sentence: Render-blocking resource calls <%- qualifier %> <%- where %>',
				'nexcess-mapps'
			)
			: _x(
				'added to',
				'Substituted as \'qualifier\' into this sentence: Render-blocking resource calls <%- qualifier %> <%- where %>',
				'nexcess-mapps'
			);

		$extra_variables = [
			[
				'variable' => 'qualifier',
				'value'    => $qualifier,
			],
		];

		return count( $failing_pages ) > 0
			? [ $this->pagesIntoInsight( $failing_pages, $extra_variables ) ]
			: [];
	}

	/**
	 * Returns a path to a key in the Lighthouse report JSON that contains
	 * a list of items (assets, files) that impacted the current metric.
	 *
	 * @return string
	 */
	protected function getLighthouseItemsKeyPath() {
		return 'audits/render-blocking-resources/details/items';
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	public static function getInsightType() {
		return 'render-blocking-resources';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Render Blocking Resources',
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
			'Pages will not appear until requests for these scripts or stylesheets have been completed.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/render-blocking-resources/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'Render-blocking resource calls <%- qualifier %> <%- where %>',
			'nexcess-mapps'
		);
	}
}
