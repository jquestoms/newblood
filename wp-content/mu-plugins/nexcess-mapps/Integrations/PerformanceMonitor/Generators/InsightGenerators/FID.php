<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `FID` generator generates insights when the First Input Delay
 * metric on a page exceeds 100 ms.
 */
class FID extends BaseInsightTypeGenerator {

	/**
	 * Generate the corresponding with `InsightDTO` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$filters = [
			_x(
				'slow',
				'Substituted as \'qualifier\' into this sentence: <%- where %> may be <%- qualifier %> to become interactive',
				'nexcess-mapps'
			) => function ( $value ) {
				return intval( $value ) > 100 && intval( $value ) <= 300;
			},
			_x(
				'very slow',
				'Substituted as \'qualifier\' into this sentence: <%- where %> may be <%- qualifier %> to become interactive',
				'nexcess-mapps'
			) => function ( $value ) {
				return intval( $value ) > 300;
			},
		];

		$buckets = $this->groupPagesByMetric(
			'max_fid',
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
		return 'fid-slow';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Max Potential FID',
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
			'Large or inefficient script files can take time to process, making your site feel unresponsive.',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://web.dev/fid/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'<%- where %> may be <%- qualifier %> to become interactive',
			'nexcess-mapps'
		);
	}
}
