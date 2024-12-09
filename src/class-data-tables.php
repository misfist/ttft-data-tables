<?php
/**
 * Data Tables AJAX Handler.
 *
 * Handles AJAX requests for data tables, fetching and processing the required data based on provided parameters.
 *
 * @package Ttft\Data_Tables
 */
namespace Ttft\Data_Tables;

use Ttft\Data_Tables\Data;
use Ttft\Data_Tables\Data_Aggregator;
use Ttft\Data_Tables\Render;
use Ttft\Data_Tables\Filters;
use Ttft\Data_Tables\API\API;
use Ttft\Data_Tables\AJAX;
use Ttft\Data_Tables\Blocks\Blocks;

class Data_Tables {

	const TABLE_ID = 'funding-data';

	const APP_ID = 'think-tank-funding';

	const TTFT_APP_NAMESPACE = 'ttft/data-tables';

	/**
	 * Constructor to register AJAX actions.
	 */
	public function __construct() {
		$this->dependencies();

		\add_filter( 'query_vars', array( $this, 'register_query_vars' ) );
	}

	/**
	 * Load dependencies.
	 *
	 * @return void
	 */
	public function dependencies(): void {
		$data            = new Data();
		$data_aggregator = new Data_Aggregator();
		$render          = new Render();
		$filters         = new Filters();
		$api             = new API();
		$blocks          = new Blocks();
		$ajax            = new AJAX();
	}

	/**
	 * Register query vars
	 *
	 * @link https://developer.wordpress.org/reference/hooks/query_vars/
	 *
	 * @param  array $query_vars
	 * @return array
	 */
	function register_query_vars( array $query_vars ) : array {
		$query_vars[] = 'table_type';
		$query_vars[] = 'entity_type';
		return $query_vars;
	}


}
