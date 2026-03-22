<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\Data\VariableDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\DataDTO as InsightDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SiteChangeDTO;

/**
 * The `ObservationTheme` generator produces a notice in a situation
 * when a theme change happens.
 */
class ObservationTheme extends BaseInsightTypeGenerator {

	/**
	 * Generate a post meta array corresponding with `Insights` objects.
	 *
	 * @return InsightDTO[]
	 */
	public function generate() {
		$target_actions      = [ 'update', 'change' ];
		$target_object_types = [ 'theme', 'parent_theme', 'child_theme' ];

		/** @var SiteChangeDTO[] */
		$site_change_dtos = $this->reportDTO->getChildren( SiteChangeDTO::class );

		foreach ( $site_change_dtos as $site_change_dto ) {
			$action      = $site_change_dto->getAction();
			$object_type = $site_change_dto->getObjectMeta()->getType();

			if ( in_array( $action, $target_actions, true ) && in_array( $object_type, $target_object_types, true ) ) {
				$parent_variable      = 'parent_theme' === $object_type ? 'parent ' : '';
				$change_type_variable = 'change' === $action
					? _x(
						'changed',
						'Substituted as \'change_type\' into this sentence: Your site\'s<%- parent %> theme was <%- change_type %>',
						'nexcess-mapps'
					)
					: _x(
						'updated',
						'Substituted as \'change_type\' into this sentence: Your site\'s<%- parent %> theme was <%- change_type %>',
						'nexcess-mapps'
					);

				$variable_dtos    = [
					new VariableDTO( 'parent', $parent_variable ),
					new VariableDTO( 'change_type', $change_type_variable ),
				];
				$insight_data_dto = new InsightDataDTO( $variable_dtos );

				return [
					new InsightDTO(
						null,
						null,
						self::getInsightType(),
						$insight_data_dto
					),
				];
			}
		}

		return [];
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	public static function getInsightType() {
		return 'observation-theme';
	}

	/**
	 * Returns a text that provides more context around the insight.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return __(
			'Observation: theme',
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
			'This can affect not only the appearance, but also the performance of all pages on your site. Keep an eye on it!',
			'nexcess-mapps'
		);
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return 'https://developer.wordpress.org/themes/getting-started/what-is-a-theme/';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return __(
			'Your site\'s<%- parent %> theme was <%- change_type %>',
			'nexcess-mapps'
		);
	}
}
