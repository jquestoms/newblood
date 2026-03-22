<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor;

use Nexcess\MAPPS\Concerns\HasAdminPages;
use Nexcess\MAPPS\Integrations\PerformanceMonitor;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerator;

/**
 * The `DashboardWidget` class is responsible for rendering a simple dashboard
 * widget in WordPress Admin.
 */
class DashboardWidget {
	use HasAdminPages;

	/**
	 * Maximum number of insights to display in the widget.
	 */
	const INSIGHT_LIMIT = 5;

	/**
	 * @var PerformanceMonitor
	 */
	protected $performanceMonitor;

	/**
	 * @var ReportDTO
	 */
	protected $reportDto;

	/**
	 * @var Array
	 */
	protected $insightTypes = [];

	/**
	 * @var string
	 */
	protected $classPrefix = '';

	/**
	 * Constructor.
	 *
	 * @param PerformanceMonitor $performance_monitor
	 */
	public function __construct( PerformanceMonitor $performance_monitor ) {
		$this->performanceMonitor = $performance_monitor;

		// Class name prefix used in HTML.
		$this->classPrefix = PerformanceMonitor::DATA_PREFIX . 'widget';

		$db          = $this->performanceMonitor->getDb();
		$report_rows = $db->getReportsPage( 1, 1 );

		if ( isset( $report_rows[0] ) ) {
			/** @var ReportDTO */
			$report_dto      = $db->loadDTO( 'reports', $report_rows[0]['id'] );
			$this->reportDto = $report_dto;

			wp_add_dashboard_widget(
				PerformanceMonitor::DATA_PREFIX . 'dashboard',
				esc_html__( 'Site Performance by Nexcess', 'nexcess-mapps' ),
				[ $this, 'render' ]
			);
		}
	}

	/**
	 * Renders the dashboard widget.
	 */
	public function render() {
		$this->inlineCss();
		$this->renderTemplate(
			'widgets/performance-monitor',
			array_merge(
				$this->getOverviewVars(),
				$this->getInsightsVars()
			)
		);
	}

	/**
	 * Renders inline CSS block.
	 */
	protected function inlineCss() {
		?>
		<style>
		.pm_widget_overview_items {
			max-width: 400px;
			display: grid;
			column-gap: 24px;
			grid-auto-flow: column;
			margin-bottom: 16px;
		}
		.pm_widget_overview_title,
		.pm_widget_insights_title {
			font-weight: bold;
			margin-bottom: 12px;
		}
		.pm_widget_overview_item_value {
			font-size: 32px;
			display: flex;
			align-items: center;
		}
		.pm_widget_overview_item_value svg {
			margin-left: 12px;
		}
		.pm_widget_overview_item_value svg:first-child {
			margin-left: 0;
			margin-right: 8px;
		}
		.pm_widget_insight,
		.pm_widget_no_insights {
			margin-bottom: 12px;
		}
		.pm_widget_insight_date {
			color: #757575;
			font-size: 11px;
		}
		.pm_widget_progress_ring {
			transform: rotate(-90deg);
		}
		</style>
		<?php
	}

	/**
	 * Returns variables needed to render the overview section of the widget.
	 *
	 * @return mixed[]
	 */
	protected function getOverviewVars() {
		$overview_data = $this->performanceMonitor->getOverviewData();

		return [
			'overview_performance_progress_ring' => $this->getProgressRing( $overview_data['score'] ),
			'overview_performance_warning'       => $this->getWarningIcon( $overview_data['score'] ),
			'overview_performance_score'         => intval( $overview_data['score'] ),
			'overview_load_score'                => intval( $overview_data['load_time'] ),
		];
	}

	/**
	 * Renders a warning icon when overall performance is not good.
	 *
	 * @param int $score Overall score.
	 *
	 * @return string
	 */
	protected function getWarningIcon( $score ) {
		if ( $score < 90 ) {
			return '<svg class="icon--warning-triangle" width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fillRule="evenodd" clipRule="evenodd" d="M2.653 23h18.788c1.488-.08 2.632-1.369 2.555-2.883a2.792 2.792 0 00-.195-.89l-9.44-16.81a2.672 2.672 0 00-3.667-1.073c-.444.249-.81.62-1.055 1.072L.2 19.226c-.56 1.405.104 3.005 1.483 3.576.28.115.576.18.877.198" fill="#F0C33C" /><path d="M11 17.747a.98.98 0 01.973-.98c.536 0 .972.43.972.966v.014a.973.973 0 01-1.945 0z" fill="#200E32" /><path fillRule="evenodd" clipRule="evenodd" d="M11.959 9a1 1 0 011 1v4.02a1 1 0 11-2 0V10a1 1 0 011-1z" fill="#200E32" /></svg>';
		}
		return '';
	}

	/**
	 * Renders a warning icon when overall performance is not good.
	 *
	 * @param int $score Overall score.
	 *
	 * @return string
	 */
	protected function getProgressRing( $score ) {
		$colors = [
			'good'     => '#00ba37',
			'adequate' => '#f0c33c',
			'bad'      => '#cc1818',
		];

		if ( $score >= 90 ) {
			$current_color = $colors['good'];
		} elseif ( $score >= 50 ) {
			$current_color = $colors['adequate'];
		} else {
			$current_color = $colors['bad'];
		}

		/**
		 * Rendering a circular progress bar is possible in SVG using a trick.
		 *
		 * @link https://css-tricks.com/building-progress-ring-quickly/
		 *
		 * The radius of the circle(s) used to render this element is 13, that means its
		 * circumference is 13 * 2 * π = 81.68140899333463
		 *
		 * This circumference is used to set the length of the initial (and only) dash
		 * using the `dasharray` property. Using the `dash-offset` property we then shift the
		 * start of the dash by the required percentage of the total dash length.
		 *
		 * That will apply stroke only on the required portion of the full circle.
		 */
		$dash_offset = 81.68140899333463 - ( 81.68140899333463 * ( $score / 100 ) );

		return sprintf(
			'<svg width="32" height="32" class="%s_progress_ring">
				<circle cx="16" cy="16" r="13" fill="transparent" stroke="#ddd" stroke-width="6"></circle>
				<circle cx="16" cy="16" r="13" fill="transparent" stroke="%s" stroke-width="6" stroke-dasharray="81.68140899333463" stroke-dashoffset="%f"></circle>
			</svg>',
			esc_attr( $this->classPrefix ),
			esc_attr( $current_color ),
			floatval( $dash_offset )
		);
	}

	/**
	 * Returns the data needed to render the Recent Insights section.
	 *
	 * @return mixed[]
	 */
	protected function getInsightsVars() {
		/** @var InsightDTO[] */
		$insight_dtos = $this->reportDto->getChildren( InsightDTO::class );

		if ( ! $insight_dtos ) {
			return [];
		}

		$insights           = [];
		$this->insightTypes = InsightGenerator::getInsightTypes( $this->performanceMonitor->isRegionalApiEnabled() );

		/**
		 * Render the latest insights.
		 */
		$rendered_insights = 0;
		foreach ( $this->insightTypes as $insight_type ) {
			foreach ( $insight_dtos as $insight_dto ) {
				if ( $insight_dto->getType() === $insight_type['type'] ) {
					$insights[] = $this->getInsightData( $insight_dto, $insight_type['template'] );
					$rendered_insights++;
				}

				if ( static::INSIGHT_LIMIT === $rendered_insights ) {
					break 2;
				}
			}
		}

		return [ 'insights' => $insights ];
	}

	/**
	 * Interpolates the insight title and generates a datetime string.
	 *
	 * @param InsightDTO $insight_dto Insight DTO to retrieve data from.
	 * @param string     $template    Template string.
	 *
	 * @return Array<string,string>
	 */
	protected function getInsightData( InsightDTO $insight_dto, $template ) {
		$variable_dtos       = $insight_dto->getData()->getVariables();
		$variables_key_value = [];

		foreach ( $variable_dtos as $variable_dto ) {
			$variables_key_value[ $variable_dto->getVariable() ] = $variable_dto->getValue();
		}

		/**
		 * Interpolate the variables into insight titles.
		 */
		$title = preg_replace_callback(
			'~<%-\s*([a-z_-]+)\s*%>~',
			function ( $matches ) use ( $variables_key_value ) {
				return isset( $variables_key_value[ $matches[1] ] )
					? $variables_key_value[ $matches[1] ]
					: '';
			},
			$template
		);

		$datetime_format = sprintf(
			// translators: Datetime string. Replaced by the default date and time format.
			__(
				'%1$s %2$s',
				'nexcess-mapps'
			),
			get_option( 'date_format', 'M j, Y' ),
			get_option( 'time_format', 'g:ia' )
		);

		$datetime = new \DateTime( $this->reportDto->getTimestamp(), wp_timezone() );
		$date     = $datetime->format( $datetime_format );

		return [
			'title' => (string) $title,
			'date'  => $date,
		];
	}
}
