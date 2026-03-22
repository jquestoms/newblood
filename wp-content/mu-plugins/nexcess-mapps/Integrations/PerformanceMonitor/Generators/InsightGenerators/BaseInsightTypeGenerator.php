<?php

namespace Nexcess\MAPPS\Integrations\PerformanceMonitor\Generators\InsightGenerators;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\Data\VariableDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\Insight\DataDTO as InsightDataDTO;

use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\InsightDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\MetricDataDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\PageDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\ReportDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\DTOs\SourceDTO;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\FileSource;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\GlobalPerformance;
use Nexcess\MAPPS\Integrations\PerformanceMonitor\LighthouseReport;

/**
 * The `BaseInsightGenerator` defines a structure of all the individual `InsightGenerator`s.
 */
abstract class BaseInsightTypeGenerator {

	/** @var LighthouseReport[] */
	protected $lighthouseReports;

	/** @var ReportDTO */
	protected $reportDTO;

	/** @var ReportDTO[] */
	protected $latestReportDTOs;

	/** @var GlobalPerformance */
	protected $globalPerformance;

	/** @var PageDTO[] */
	protected $pageDTOs;

	/** @var PageDTO[] */
	protected $previousPageDTOs;

	/** @var Array<string, array{page: PageDTO, report: LighthouseReport}> */
	protected $urlMap;

	/** @var Array<string, Array<string, PageDTO>> */
	protected $previousPageMap;

	/**
	 * Constructor.
	 *
	 * @param LighthouseReport[] $lighthouse_reports `LighthouseReport` objects for a given day.
	 * @param ReportDTO          $report_dto         `ReportDTO` generated today.
	 * @param ReportDTO[]        $latest_report_dtos Latest 7 `ReportDTO` objects.
	 * @param GlobalPerformance  $global_performance `GlobalPerformance` helper instance.
	 */
	public function __construct(
		array $lighthouse_reports,
		ReportDTO $report_dto,
		array $latest_report_dtos,
		GlobalPerformance $global_performance
	) {
		$this->lighthouseReports = $lighthouse_reports;
		$this->reportDTO         = $report_dto;
		$this->latestReportDTOs  = $latest_report_dtos;
		$this->globalPerformance = $global_performance;

		/**
		 * Insight generators use individual PageDTOs frequently,
		 * so we extract them into separate arrays for convenience.
		 *
		 * @var PageDTO[]
		 */
		$page_dtos      = $this->reportDTO->getChildren( PageDTO::class );
		$this->pageDTOs = $page_dtos;

		/** @var PageDTO[] */
		$previous_page_dtos = [];

		if ( isset( $this->latestReportDTOs[0] ) ) {
			$previous_page_dtos = $this->latestReportDTOs[0]->getChildren( PageDTO::class );
		}
		$this->previousPageDTOs = $previous_page_dtos;

		/**
		 * There is 1:1 correspondence between Lighthouse reports and PageDTOs,
		 * the `urlMap` is used to map Lighthouse reports to PageDTOs.
		 */
		$this->urlMap = array_reduce(
			$this->pageDTOs,
			function ( $map, PageDTO $page_dto ) {
				foreach ( $this->lighthouseReports as $report ) {
					if ( $report->getUrl() === $page_dto->getUrl() ) {
						$map[ $report->getUrl() ] = [
							'page'   => $page_dto,
							'report' => $report,
						];
					}
				}
				return $map;
			},
			[]
		);

		/**
		 * There often is 1:1 correspondence between today's and yesterday's PageDTOs,
		 * the `previousPageMap` is used to map previous PageDTOs to current PageDTOs.
		 */
		$this->previousPageMap = array_reduce(
			$this->pageDTOs,
			function ( $map, PageDTO $page_dto ) {
				foreach ( $this->previousPageDTOs as $previous_page_dto ) {
					if ( $previous_page_dto->getUrl() === $page_dto->getUrl() ) {
						$map[ $page_dto->getUrl() ] = [
							'page'          => $page_dto,
							'previous_page' => $previous_page_dto,
						];
					}
				}
				return $map;
			},
			[]
		);
	}

	/**
	 * Generate InsightDTO objects representing insights
	 * for the given Lighthouse reports.
	 *
	 * @return InsightDTO[]
	 */
	abstract public function generate();

	/**
	 * Note: The following static methods returning empty strings
	 *       only exist because in PHP 5.6 strict mode it is not possible
	 *       to declare an abstract static method.
	 */

	/**
	 * Returns the insight category.
	 *
	 * @return string
	 */
	public static function getCategory() {
		return '';
	}

	/**
	 * Returns the insight title.
	 *
	 * E.g. "This may be a result of code or content changes you have made;
	 *       or external factors outside your control." (for Overall Performance)
	 *
	 * @return string
	 */
	public static function getDescriptionText() {
		return '';
	}

	/**
	 * Returns a contextual "more info" URL displayed with the insight.
	 *
	 * @return string
	 */
	public static function getDescriptionURL() {
		return '';
	}

	/**
	 * Returns a template string to be interpolated by variables.
	 *
	 * @return string
	 */
	public static function getTemplate() {
		return '';
	}

	/**
	 * Returns an insight type, i.e. an "insight ID string".
	 *
	 * @return string
	 */
	public static function getInsightType() {
		return '';
	}

	/**
	 * Returns those `PageDTO` objects that contain a metric with a given value.
	 *
	 * @param string $metric       Page metric key.
	 * @param mixed  $filter_value Pages with this value of the metric will be returned.
	 *
	 * @return PageDTO[]
	 */
	protected function filterPagesByLighthouseMetric( $metric, $filter_value ) {
		$pages = [];

		foreach ( $this->lighthouseReports as $report ) {
			$page = $this->getPageByUrl( $report->getUrl() );

			if ( $page ) {
				$value = $report->getValue( $metric );
				if ( $value === $filter_value ) {
					$pages[] = $page;
				}
			}
		}

		return $pages;
	}

	/**
	 * Groups the `PageDTO` objects into buckets based on provided filter functions.
	 *
	 * @param string                  $metric  Lighthouse metric key.
	 * @param Array<string, callable> $filters Filter methods to be applied with each metric value.
	 *
	 * @return Array<string, PageDTO[]>
	 */
	protected function groupPagesByMetric( $metric, $filters ) {
		$buckets = [];

		foreach ( $this->pageDTOs as $page_dto ) {
			$metric_value = $this->getPageMetric( $page_dto, $metric );

			$previous_page_dto     = $this->getPreviousPage( $page_dto->getUrl() );
			$previous_metric_value = $previous_page_dto ? $this->getPageMetric( $previous_page_dto, $metric ) : null;

			foreach ( $filters as $label => $filter ) {
				if ( call_user_func( $filter, $metric_value, $previous_metric_value ) ) {
					if ( ! isset( $buckets[ $label ] ) ) {
						$buckets[ $label ] = [];
					}
					$buckets[ $label ][] = $page_dto;
				}
			}
		}

		return $buckets;
	}

	/**
	 * Returns a `PageDTO` object for a given URL or null if no page is found.
	 *
	 * @param string $url URL to search for.
	 *
	 * @return PageDTO|null
	 */
	protected function getPageByUrl( $url ) {
		return isset( $this->urlMap[ $url ] )
			? $this->urlMap[ $url ]['page']
			: null;
	}

	/**
	 * Returns a `PageDTO` object for a given URL or null if no page is found.
	 *
	 * @param string $url URL to search for.
	 *
	 * @return PageDTO|null
	 */
	protected function getPreviousPage( $url ) {
		return isset( $this->previousPageMap[ $url ] )
			? $this->previousPageMap[ $url ]['previous_page']
			: null;
	}

	/**
	 * Return the `where` and `tooltip` variables from a list
	 * of pages. The variable value are used on the front end
	 * in the insight there.
	 *
	 * Example: Performance is down significantly on (where).
	 *
	 * @param PageDTO[] $page_dtos `PageDTO` objects.
	 *
	 * @return Array<int,Array<string,string>> List of `where` and `tooltip` variables.
	 */
	protected function getWhereVariables( array $page_dtos ) {
		$where      = __( 'some pages', 'nexcess-mapps' );
		$page_names = array_map( function ( PageDTO $page ) {
			return $page->getName();
		}, $page_dtos );
		$tooltip    = join( ', ', $page_names );

		if ( 1 === count( $page_dtos ) ) {
			$tooltip = '';
			$page    = current( $page_dtos );
			$where   = $page->getName();
		} elseif ( count( $page_dtos ) === count( $this->pageDTOs ) ) {
			$where = __( 'all pages', 'nexcess-mapps' );
		} elseif ( count( $page_dtos ) / count( $this->pageDTOs ) > 0.66 ) {
			$where = __( 'most pages', 'nexcess-mapps' );
		}

		return [
			[
				'variable' => 'where',
				'value'    => (string) $where,
			],
			[
				'variable' => 'tooltip',
				'value'    => (string) $tooltip,
			],
		];
	}

	/**
	 * Return the `type`, `description`, `category`, `url` and `template`
	 * variables filled with relevant values.
	 *
	 * @return Array<string, string> List of `type`, `description`, `category`, `url` and `template` variables.
	 */
	public static function getTemplateData() {
		return [
			'type'        => static::getInsightType(),
			'category'    => static::getCategory(),
			'description' => static::getDescriptionText(),
			'url'         => static::getDescriptionURL(),
			'template'    => static::getTemplate(),
		];
	}

	/**
	 * Generate insights meta given multiple sets of pages that fall
	 * into one of the buckets we want to generate insights for.
	 *
	 * @param Array<string, PageDTO[]> $bucketed_values Buckets of pages.
	 *
	 * @return InsightDTO[] InsightDTO instances.
	 */
	protected function bucketsIntoInsights( array $bucketed_values ) {
		$insights_dtos = [];

		foreach ( $bucketed_values as $bucket_label => $pages ) {
			$extra_variables = [
				[
					'variable' => 'qualifier',
					'value'    => $bucket_label,
				],
			];
			$insights_dtos[] = $this->pagesIntoInsight( $pages, $extra_variables );
		}

		return $insights_dtos;
	}

	/**
	 * Returns insight metadata that also references pages that
	 * should be mentioned by the insight.
	 *
	 * @param PageDTO[]    $page_dtos       Pages that should be mentioned by the insight.
	 * @param Array<Array> $extra_variables Extra variables to be added to the insight.
	 *
	 * @return InsightDTO InsightDTO instance.
	 */
	protected function pagesIntoInsight( array $page_dtos, $extra_variables = [] ) {
		$variables     = array_merge( $extra_variables, $this->getWhereVariables( $page_dtos ) );
		$variable_dtos = array_map( function ( $variable ) {
			return new VariableDTO( $variable['variable'], $variable['value'] );
		}, $variables );
		$data_dto      = new InsightDataDTO( $variable_dtos );

		return $this->insightFromData( $data_dto );
	}

	/**
	 * Returns the list of sources that should be mentioned by the insight.
	 *
	 * @param PageDTO[] $page_dtos `PageDTO` instances.
	 *
	 * @return SourceDTO[] List of SourceDTO instances.
	 */
	protected function getSources( $page_dtos ) {
		$sources   = [];
		$items_key = $this->getLighthouseItemsKeyPath();

		if ( ! $items_key ) {
			return $sources;
		}

		$lighthouse_reports = [];
		foreach ( $page_dtos as $page_dto ) {
			if ( isset( $this->urlMap[ $page_dto->getUrl() ]['report'] ) ) {
				$lighthouse_reports[] = $this->urlMap[ $page_dto->getUrl() ]['report'];
			}
		}

		$file_urls = [];
		foreach ( $lighthouse_reports as $lighthouse_report ) {
			$items = $lighthouse_report->getValue( $items_key, [] );
			if ( is_array( $items ) ) {
				foreach ( $items as $item ) {
					if ( isset( $item->url ) && 0 === strpos( $item->url, 'http' ) ) {
						$file_urls[] = $item->url;
					}
				}
			}
		}

		$file_source = new FileSource();
		$sources     = array_map( [ $file_source, 'getSource' ], $file_urls );
		$sources     = array_filter( $sources );
		$sources     = array_unique( $sources, SORT_REGULAR );

		usort(
			$sources,
			function ( $source_1, $source_2 ) {
				return $source_1['type'] < $source_2['type'] ? -1 : 1;
			}
		);

		return array_map( function ( $source_data ) {
			return new SourceDTO(
				null,
				null,
				isset( $source_data['type'] ) ? $source_data['type'] : null,
				isset( $source_data['name'] ) ? $source_data['name'] : null,
				isset( $source_data['timestamp'] ) ? $source_data['timestamp'] : null
			);
		}, $sources );
	}

	/**
	 * Returns a metric value for the given page DTO object.
	 *
	 * @param PageDTO $page_dto      Page DTO object.
	 * @param string  $metric_name   Metric name.
	 * @param mixed   $default_value Default value.
	 *
	 * @return mixed Metric value.
	 */
	protected function getPageMetric( $page_dto, $metric_name, $default_value = null ) {
		/**
		 * @var MetricDataDTO[] $metric_dtos
		 */
		$metric_dtos = $page_dto->getChildren( MetricDataDTO::class );

		foreach ( $metric_dtos as $metric_dto ) {
			if ( $metric_dto->getMetricName() === $metric_name ) {
				return $metric_dto->getMetricValue();
			}
		}
		return $default_value;
	}

	/**
	 * Returns a new `InsightDTO` object given its data only.
	 *
	 * @param InsightDataDTO $data_dto Insight data.
	 *
	 * @return InsightDTO Insight DTO object.
	 */
	protected function insightFromData( InsightDataDTO $data_dto ) {
		return new InsightDTO(
			null,
			null,
			static::getInsightType(),
			$data_dto
		);
	}

	/**
	 * Returns `true` if the same insight type has been also
	 * generated yesterday.
	 *
	 * @return bool
	 */
	protected function isRepeated() {
		$previous_report_dto = isset( $this->latestReportDTOs[0] )
			? $this->latestReportDTOs[0]
			: null;

		if ( ! $previous_report_dto ) {
			return false;
		}

		/** @var InsightDTO[] */
		$previous_insight_dtos = $previous_report_dto->getChildren( InsightDTO::class );

		foreach ( $previous_insight_dtos as $insight_dto ) {
			if ( $insight_dto->getType() === static::getInsightType() ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Returns a path to a key in the Lighthouse report JSON that contains
	 * a list of items (assets, files) that impacted the current metric.
	 *
	 * Overridden by individual generators where applicable.
	 *
	 * @return string Path to the key in the Lighthouse report JSON.
	 */
	protected function getLighthouseItemsKeyPath() {
		return '';
	}
}
