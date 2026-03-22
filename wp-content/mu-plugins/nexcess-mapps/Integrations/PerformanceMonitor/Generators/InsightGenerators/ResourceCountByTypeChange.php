<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;

/**
 * The `ResourceCountByTypeChange` generator generates insights related to the
 * increase of the number of assets per type.
 */
class ResourceCountByTypeChange extends BaseInsightTypeGenerator {

	/**
	 * Only create insights when the resource type number of assets
	 * increase is over its sensitivity value.
	 *
	 * Note: The numbers hover around ~10 % threshold for an average
	 *       weight of that resource type, but are also manually adjusted.
	 *
	 * @link https://httparchive.org/reports/page-weight
	 *
	 * @var Array<string, int>
	 */
	const SENSITIVITY_BY_RESOURCE_TYPE = [
		'script'      => 1,
		'stylesheet'  => 2,
		'image'       => 2,
		'media'       => 1,
		'third-party' => 2,
	];

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$insight_dtos           = [];
		$resource_types_plurals = [
			'script'      => _x(
				'scripts',
				'Substituted as \'resource_type\' into this sentence: Significant increase in number of <%- resource_type %> on <%- where %>',
				'nexcess-mapps'
			),
			'stylesheet'  => _x(
				'stylesheets',
				'Substituted as \'resource_type\' into this sentence: Significant increase in number of <%- resource_type %> on <%- where %>',
				'nexcess-mapps'
			),
			'image'       => _x(
				'images',
				'Substituted as \'resource_type\' into this sentence: Significant increase in number of <%- resource_type %> on <%- where %>',
				'nexcess-mapps'
			),
			'media'       => _x(
				'media files',
				'Substituted as \'resource_type\' into this sentence: Significant increase in number of <%- resource_type %> on <%- where %>',
				'nexcess-mapps'
			),
			'third-party' => _x(
				'third party assets',
				'Substituted as \'resource_type\' into this sentence: Significant increase in number of <%- resource_type %> on <%- where %>',
				'nexcess-mapps'
			),
		];

		foreach ( array_keys( self::SENSITIVITY_BY_RESOURCE_TYPE ) as $resource_type ) {
			$metric_name = sprintf( 'number_files_%s_diff', $resource_type );
			$buckets     = $this->groupPagesByMetric(
				$metric_name,
				[
					'exceeded' => function ( $value ) use ( $resource_type ) {
						return intval( $value ) > self::SENSITIVITY_BY_RESOURCE_TYPE[ $resource_type ];
					},
				]
			);

			if ( isset( $buckets['exceeded'] ) ) {
				$extra_variables = [
					[
						'variable' => 'resource_type',
						'value'    => $resource_types_plurals[ $resource_type ],
					],
				];
				$insight_dtos[]  = $this->pagesIntoInsight( $buckets['exceeded'], $extra_variables );
			}
		}
		return $insight_dtos;
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	public static function getInsightType() {
		return 'resource-type-number-files';
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
			'Page speed may be affected by the additional network requests resulting from your changes.',
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
			'Significant increase in number of <%- resource_type %> on <%- where %>',
			'nexcess-mapps'
		);
	}
}
