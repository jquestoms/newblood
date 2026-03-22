<?php
namespace Nexcess\MAPPS\Integrations\PerformanceMonitor;

/**
 * Represents a 2D matrix of performance data values.
 */
class MetricMatrix {
	/** @var string */
	protected $metric = 'score';

	/** @var (int|null)[][] */
	protected $data;

	/** @var string[] */
	protected $rowHeaders = [];

	/** @var string[] */
	protected $colHeaders = [];

	/** @var int */
	protected $rowsCount = 0;

	/** @var int */
	protected $colsCount = 0;

	/**
	 * Constructor.
	 *
	 * @param Array<Array<int|null>> $data        Matrix data.
	 * @param Array<string>          $row_headers Row headers.
	 * @param Array<string>          $col_headers Column headers.
	 */
	public function __construct( array $data, array $row_headers, array $col_headers ) {
		$this->data       = $data;
		$this->rowHeaders = $row_headers;
		$this->colHeaders = $col_headers;
		$this->rowsCount  = count( $row_headers );
		$this->colsCount  = count( $col_headers );
	}

	/**
	 * Returns a single cell value based on row and column headers.
	 *
	 * @param string $row_header Row header.
	 * @param string $col_header Column header.
	 *
	 * @return int|null Data corresponding with with the provided headers.
	 */
	public function getCellByHeaders( $row_header, $col_header ) {
		$row_index = array_search( $row_header, $this->rowHeaders, true );
		$col_index = array_search( $col_header, $this->colHeaders, true );

		if ( is_int( $row_index ) && is_int( $col_index ) ) {
			return $this->getCellByIndex( $row_index, $col_index );
		}
		return null;
	}

	/**
	 * Returns a single cell value based on coordinates.
	 *
	 * @param int $row_index Row index.
	 * @param int $col_index Column index.
	 *
	 * @return int|null Data corresponding with with the provided indexes.
	 */
	public function getCellByIndex( $row_index, $col_index ) {
		return isset( $this->data[ $row_index ][ $col_index ] )
			? $this->data[ $row_index ][ $col_index ]
			: null;
	}

	/**
	 * Returns all cell values in a given row.
	 *
	 * @param int $row_index Row index.
	 *
	 * @return (int|null)[]
	 */
	public function getRowCells( $row_index ) {
		return isset( $this->data[ $row_index ] )
			? $this->data[ $row_index ]
			: [];
	}

	/**
	 * Returns all cell values in a given row.
	 *
	 * @param int $col_index Column index.
	 *
	 * @return (int|null)[]
	 */
	public function getColCells( $col_index ) {
		return array_column( $this->data, $col_index );
	}

	/**
	 * Returns an average of values from a given row.
	 *
	 * @param int $row_index Row index.
	 *
	 * @return float
	 */
	public function getRowCellsAverage( $row_index ) {
		if ( 0 === $this->getRowsCount() ) {
			return 0;
		}
		$non_null_values = array_filter(
			$this->getRowCells( $row_index ),
			function ( $value ) {
				return null !== $value;
			}
		);
		$non_null_count  = count( $non_null_values );

		return 0 === $non_null_count ? 0 : array_sum( $non_null_values ) / $non_null_count;
	}

	/**
	 * Returns an average of values from a given column.
	 *
	 * @param int $col_index Column index.
	 *
	 * @return float
	 */
	public function getColCellsAverage( $col_index ) {
		if ( 0 === $this->getColsCount() ) {
			return 0;
		}
		$non_null_values = array_filter(
			$this->getColCells( $col_index ),
			function ( $value ) {
				return null !== $value;
			}
		);
		$non_null_count  = count( $non_null_values );

		return 0 === $non_null_count ? 0 : array_sum( $non_null_values ) / $non_null_count;
	}

	/**
	 * Returns two lists.
	 *
	 * List of row headers for rows of data cells whose values all evaluate
	 * as `true` when the `$matcher` function is applied.
	 *
	 * List of column headers for columns of data cells whose values all evaluate
	 * as `true` when the `$matcher` function is applied.
	 *
	 * @param callable $matcher Matcher function.
	 *
	 * @return array
	 */
	public function getMatchedAxes( $matcher ) {
		$matched = [
			'rows' => [],
			'cols' => [],
		];

		for ( $row_index = 0; $row_index < $this->rowsCount; $row_index++ ) {
			$row_data = $this->getRowCells( $row_index );
			$matches  = array_reduce(
				$row_data,
				function ( $carry, $value ) use ( $matcher ) {
					$matcher_result = call_user_func( $matcher, $value );
					return null === $matcher_result ? $carry : $carry && $matcher_result;
				},
				true
			);
			if ( $matches && ! empty( $this->rowHeaders[ $row_index ] ) ) {
				$matched['rows'][] = $this->rowHeaders[ $row_index ];
			}
		}

		for ( $col_index = 0; $col_index < $this->colsCount; $col_index++ ) {
			$col_data = $this->getColCells( $col_index );
			$matches  = array_reduce(
				$col_data,
				function ( $carry, $value ) use ( $matcher ) {
					$matcher_result = call_user_func( $matcher, $value );
					return null === $matcher_result ? $carry : $carry && $matcher_result;
				},
				true
			);
			if ( $matches && ! empty( $this->colHeaders[ $col_index ] ) ) {
				$matched['cols'][] = $this->colHeaders[ $col_index ];
			}
		}

		return $matched;
	}

	/**
	 * @param array[] $global_performance_data Global performance entries.
	 * @param string  $metric                  Label of the metric to be used as the matrix data.
	 * @param mixed   $default                 Default value to use when initializing the matrix.
	 *
	 * @return MetricMatrix `MetricMatrix` instance.
	 */
	public static function fromGlobalPerformanceData(
		array $global_performance_data,
		$metric = 'score',
		$default = null
	) {
		$col_headers = array_values( array_unique( array_column( $global_performance_data, 'region' ) ) );
		$row_headers = array_values( array_unique( array_column( $global_performance_data, 'url' ) ) );
		$data        = array_fill(
			0,
			count( $row_headers ),
			array_fill( 0, count( $col_headers ), $default )
		);

		foreach ( $global_performance_data as $data_item ) {
			$row_index = array_search( $data_item['url'], $row_headers, true );
			$col_index = array_search( $data_item['region'], $col_headers, true );

			if (
				false !== $row_index
				&& false !== $col_index
				&& isset( $data_item[ $metric ] )
			) {
				$data[ $row_index ][ $col_index ] = $data_item[ $metric ];
			}
		}

		return new MetricMatrix( $data, $row_headers, $col_headers );
	}

	/**
	 * Returns the number of rows.
	 *
	 * @return int Rows count.
	 */
	public function getRowsCount() {
		return $this->rowsCount;
	}

	/**
	 * Returns the number of columns.
	 *
	 * @return int Columns count.
	 */
	public function getColsCount() {
		return $this->colsCount;
	}

	/**
	 * Returns a row header.
	 *
	 * @param int $index Row header index.
	 *
	 * @return string Row header.
	 */
	public function getRowHeader( $index ) {
		return isset( $this->rowHeaders[ $index ] ) ? $this->rowHeaders[ $index ] : '';
	}

	/**
	 * Returns a col header.
	 *
	 * @param int $index Column header index.
	 *
	 * @return string Column header.
	 */
	public function getColHeader( $index ) {
		return isset( $this->colHeaders[ $index ] ) ? $this->colHeaders[ $index ] : '';
	}

	/**
	 * Returns all row headers.
	 *
	 * @return string[] Row headers.
	 */
	public function getRowHeaders() {
		return $this->rowHeaders;
	}

	/**
	 * Returns all column headers.
	 *
	 * @return string[] Column headers.
	 */
	public function getColHeaders() {
		return $this->colHeaders;
	}
}
