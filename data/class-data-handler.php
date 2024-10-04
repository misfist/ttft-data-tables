<?php
/**
 * Data Tables AJAX Handler.
 *
 * Handles AJAX requests for data tables, fetching and processing the required data based on provided parameters.
 *
 * @package TTFT\Data_Tables
 */
namespace TTFT\Data_Tables;

use function TTFT\Data_Tables\Data\generate_data_table;

class Data_Handler {


	const TABLE_ID = 'funding-data';

	const APP_ID = 'think-tank-funding';

	const APP_NAMESPACE = 'ttft/data-tables';

	/**
	 * The action name for the AJAX request.
	 *
	 * @var string
	 */
	public $action_name = 'do_get_data_table';

	/**
	 * The nonce name for verifying the AJAX request.
	 *
	 * @var string
	 */
	public $nonce_name = 'ttft/data-tables_nonce';

	/**
	 * Constructor to register AJAX actions.
	 */
	public function __construct() {
		add_action( 'wp_ajax_' . $this->action_name, array( $this, 'handle_request' ) );
		add_action( 'wp_ajax_nopriv_' . $this->action_name, array( $this, 'handle_request' ) );

		// \add_filter( 'pre_get_posts', array( $this, 'modify_think_tank_archive_query' ) );

		$this->dependencies( TTFT_PATH . 'data/functions' );
	}

	/**
	 * Automatically include any .php file in a given directory.
	 *
	 * @param ?string $directory The directory to include PHP files from. If no directory is provided, defaults to the current directory.
	 * @return void
	 */
	public function dependencies( ?string $directory = null ): void {
		if ( is_null( $directory ) ) {
			$directory = __DIR__;
		}

		$directory = rtrim( $directory, '/' ) . '/';

		if ( is_dir( $directory ) ) {
			$files = glob( $directory . '*.php' );

			foreach ( $files as $file ) {
				include_once $file;
			}
		} else {
			error_log( "Directory not found: $directory" );
		}
	}

	/**
	 * Handle the AJAX request for fetching table data.
	 *
	 * Verifies the nonce and processes the request by passing the parameters to the appropriate data-fetching method.
	 *
	 * @return void
	 */
	public function handle_request() {
		check_ajax_referer( $this->nonce_name, 'nonce' );

		$args = array(
			'table_type'    => isset( $_POST['table_type'] ) ? sanitize_text_field( wp_unslash( $_POST['table_type'] ) ) : '',
			'think_tank'    => isset( $_POST['think_tank'] ) ? sanitize_text_field( wp_unslash( $_POST['think_tank'] ) ) : '',
			'donor'         => isset( $_POST['donor'] ) ? sanitize_text_field( wp_unslash( $_POST['donor'] ) ) : '',
			'donation_year' => isset( $_POST['donation_year'] ) ? sanitize_text_field( wp_unslash( $_POST['donation_year'] ) ) : '',
			'donor_type'    => isset( $_POST['donor_type'] ) ? sanitize_text_field( wp_unslash( $_POST['donor_type'] ) ) : '',
		);

		// $data = $this->get_data( $args );
		$data = $this->get_table( $args );

		if ( ! empty( $data ) ) {
			wp_send_json_success( $data );
		} else {
			wp_send_json_error(
				array(
					'message' => __( 'No data found.', 'ttft-data-tables' ),
				)
			);
		}
	}

	/**
	 * Fetch data based on the provided arguments.
	 *
	 * Determines the data source and format based on the table type and other parameters.
	 *
	 * @param array $args The array of arguments including table_type, think_tank, donor, donation_year, and donor_type.
	 * @return array The array of data including column definitions and table rows.
	 */
	public function get_data( $args ) {
		$args = $this->convert_camel_to_snake_keys( $args );
		
		switch ( $args['table_type'] ) {
			case 'think-tank-archive':
				$columns = $this->get_think_tank_archive_columns();
				$data    = $this->get_think_tank_archive_data( $args['think_tank'], $args['donation_year'], $args['donor_type'] );
				break;
			case 'single-think-tank':
				$columns = $this->get_single_think_tank_columns();
				$data    = $this->get_single_think_tank_data( $args['think_tank'], $args['donation_year'], $args['donor_type'] );
				break;
			case 'donor-archive':
				$columns = $this->get_donor_archive_columns();
				$data    = $this->get_donor_archive_data( $args['donor'], $args['donation_year'], $args['donor_type'] );
				break;
			case 'single-donor':
				$columns = $this->get_single_donor_columns();
				$data    = $this->get_single_donor_data( $args['donor'], $args['donation_year'], $args['donor_type'] );
				break;
			default:
				$columns = array();
				$data    = array();
				break;
		}

		return array(
			'columns' => $columns,
			'data'    => $data,
		);
	}

	/**
	 * Fetch table content based on the provided arguments.
	 *
	 * Determines the data source and format based on the table type and other parameters.
	 *
	 * @param array $args The array of arguments including tableType, thinkTank, donor, donationYear, and donorType.
	 * @return string string.
	 */
	public function get_table( $args ) {
		$args = $this->convert_camel_to_snake_keys( $args );

		if ( empty( $args['table_type'] ) ) {
			return __( 'Table Type attribute is required.', 'ttft-data-tables' );
		}

		$output = generate_data_table( $args['table_type'], $args );

		return $output;
	}

	/**
	 * Retrieves the column data for the Think Tank Archive.
	 *
	 * @return array Column data for the Think Tank Archive.
	 */
	public function get_think_tank_archive_columns() {
		return array(
			array(
				'title'     => 'Think Tank',
				'data'      => 'thinkTank',
				'className' => 'column-think-tank',
				'orderable' => true,
			),
			array(
				'title'     => 'Pentagon Contractor',
				'data'      => 'pentagonContractor',
				'className' => 'column-numeric column-min-amount',
				'orderable' => true,
			),
			array(
				'title'     => 'U.S. Government',
				'data'      => 'usGovernment',
				'className' => 'column-numeric column-min-amount',
				'orderable' => true,
			),
			array(
				'title'     => 'Foreign Government',
				'data'      => 'foreignGovernment',
				'className' => 'column-numeric column-min-amount',
				'orderable' => true,
			),
			array(
				'title'     => 'Score',
				'data'      => 'score',
				'className' => 'column-numeric column-transparency-score',
				'orderable' => true,
			),
		);
	}

	/**
	 * Retrieves the table data for the Think Tank Archive.
	 *
	 * @return array Table data for the Think Tank Archive.
	 */
	public function get_think_tank_archive_data() {
		return array(
			array(
				'title'     => 'Think Tank',
				'data'      => 'thinkTank',
				'className' => 'column-think-tank',
				'orderable' => true,
			),
			array(
				'title'     => 'Pentagon Contractor',
				'data'      => 'pentagonContractor',
				'className' => 'column-numeric column-min-amount',
				'orderable' => true,
			),
			array(
				'title'     => 'U.S. Government',
				'data'      => 'usGovernment',
				'className' => 'column-numeric column-min-amount',
				'orderable' => true,
			),
			array(
				'title'     => 'Foreign Government',
				'data'      => 'foreignGovernment',
				'className' => 'column-numeric column-min-amount',
				'orderable' => true,
			),
			array(
				'title'     => 'Score',
				'data'      => 'score',
				'className' => 'column-numeric column-transparency-score',
				'orderable' => true,
			),
		);
	}

	/**
	 * Retrieves the column data for the Donor Archive.
	 *
	 * @return array Column data for the Donor Archive.
	 */
	public function get_donor_archive_columns() {
		return array(
			array(
				'title'     => 'Donor',
				'data'      => 'donor',
				'className' => 'column-donor',
				'orderable' => true,
			),
			array(
				'title'     => 'Min Amount',
				'data'      => 'minAmount',
				'className' => 'column-numeric column-min-amount',
				'orderable' => true,
			),
			array(
				'title'     => 'Type',
				'data'      => 'type',
				'className' => 'column-type',
				'orderable' => true,
			),
		);
	}

	/**
	 * Retrieves the table data for the Donor Archive.
	 *
	 * @return array Table data for the Donor Archive.
	 */
	public function get_donor_archive_data() {
		return array(
			array(
				'donor'     => 'Accenture',
				'minAmount' => '325,000',
				'type'      => 'Pentagon Contractor',
			),
			array(
				'donor'     => 'Accenture > Accenture (via Accenture Federal Services)',
				'minAmount' => '850,000',
				'type'      => 'Pentagon Contractor',
			),
			array(
				'donor'     => 'Airbus',
				'minAmount' => '1,350,000',
				'type'      => 'Pentagon Contractor',
			),
			array(
				'donor'     => 'Australia > Department of Foreign Affairs and Trade, Australia',
				'minAmount' => '810,001',
				'type'      => 'Foreign Government',
			),
		);
	}

	/**
	 * Retrieves the column data for a single Think Tank.
	 *
	 * @return array Column data for a single Think Tank.
	 */
	public function get_single_think_tank_columns() {
		return array(
			array(
				'title'     => 'Donor',
				'data'      => 'donor',
				'className' => 'column-donor',
				'orderable' => true,
			),
			array(
				'title'     => 'Min Amount',
				'data'      => 'minAmount',
				'className' => 'column-min-amount',
				'orderable' => true,
			),
			array(
				'title'     => 'Source',
				'data'      => 'source',
				'className' => 'column-source',
				'orderable' => false,
			),
			array(
				'title'     => 'Year',
				'data'      => 'year',
				'className' => 'column-year',
				'orderable' => true,
			),
			array(
				'title'     => 'Type',
				'data'      => 'type',
				'className' => 'column-type',
				'orderable' => true,
			),
		);
	}

	/**
	 * Retrieves the table data for a single Think Tank.
	 *
	 * @return array Table data for a single Think Tank.
	 */
	public function get_single_think_tank_data() {
		return array(
			array(
				'donor'     => 'Accenture',
				'minAmount' => '25,000',
				'source'    => 'https://www.atlanticcouncil.org/in-depth-research-reports/report/2021-annual-report-honor-roll-of-contributors/',
				'year'      => '2021',
				'type'      => 'Pentagon Contractor',
			),
			array(
				'donor'     => 'Accenture',
				'minAmount' => '250,000',
				'source'    => 'https://www.atlanticcouncil.org/in-depth-research-reports/report/2021-annual-report-honor-roll-of-contributors/',
				'year'      => '2021',
				'type'      => 'Pentagon Contractor',
			),
			array(
				'donor'     => 'Accenture > Accenture (via Accenture Federal Services)',
				'minAmount' => '100,000',
				'source'    => 'https://www.atlanticcouncil.org/wp-content/uploads/2020/11/Annual-Report-2019_20.pdf',
				'year'      => '2019',
				'type'      => 'Pentagon Contractor',
			),
			array(
				'donor'     => 'Accenture > Accenture (via Accenture Federal Services)',
				'minAmount' => '250,000',
				'source'    => 'https://www.atlanticcouncil.org/wp-content/uploads/2021/11/2021-annual-report.pdf',
				'year'      => '2020',
				'type'      => 'Pentagon Contractor',
			),
			array(
				'donor'     => 'Accenture > Accenture (via Accenture Federal Services)',
				'minAmount' => '250,000',
				'source'    => 'https://www.atlanticcouncil.org/in-depth-research-reports/report/2022-honor-roll-of-contributors/',
				'year'      => '2022',
				'type'      => 'Pentagon Contractor',
			),
		);
	}

	/**
	 * Retrieves the column data for a single Donor.
	 *
	 * @return array Column data for a single Donor.
	 */
	public function get_single_donor_columns() {
		return array(
			array(
				'title'     => 'Think Tank',
				'data'      => 'thinkTank',
				'className' => 'column-think-tank',
				'orderable' => true,
			),
			array(
				'title'     => 'Donor',
				'data'      => 'donor',
				'className' => 'column-donor',
				'orderable' => true,
			),
			array(
				'title'     => 'Min Amount',
				'data'      => 'minAmount',
				'className' => 'column-numeric column-min-amount',
				'orderable' => true,
			),
			array(
				'title'     => 'Source',
				'data'      => 'source',
				'className' => 'column-source',
				'orderable' => false,
			),
		);
	}

	/**
	 * Retrieves the table data for a single Donor.
	 *
	 * @return array Table data for a single Donor.
	 */
	public function get_single_donor_data() {
		return array(
			array(
				'thinkTank' => 'Aspen Institute',
				'donor'     => 'Accenture',
				'minAmount' => '50,000',
				'source'    => 'https://www.aspeninstitute.org/wp-content/uploads/2022/09/impact-report-2022.pdf',
			),
			array(
				'thinkTank' => 'Atlantic Council',
				'donor'     => 'Accenture (via Accenture Federal Services)',
				'minAmount' => '1,125,000',
				'source'    => 'https://www.atlanticcouncil.org/wp-content/uploads/2024/05/Atlantic-Council-2023-2024-Annual-Report.pdf',
			),
		);
	}

	/**
	 * Convert camelCase keys to lowercase separated by underscores.
	 *
	 * @param array $args The array with camelCase keys.
	 * @return array The array with keys converted to lowercase separated by underscores.
	 */
	public function convert_camel_to_snake_keys( array $args ): array {
		$converted_args = array();
		foreach ( $args as $key => $value ) {
			$new_key                    = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $key ) );
			$converted_args[ $new_key ] = $value;
		}
		return $converted_args;
	}

	/**
	 * Convert camelCase keys to lowercase separated by underscores.
	 *
	 * @param array $args The array with camelCase keys.
	 * @return array The array with keys converted to lowercase separated by underscores.
	 */
	public function convert_camel_to_snake_key( $key ): string {
		return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $key ) );
	}

	/**
	 * Modify the query for the Think Tank Archive.
	 *
	 * @param  object $query
	 * @return void
	 */
	public function modify_think_tank_archive_query( $query ) : void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if( ! $query->is_post_type_archive( 'think_tank' ) ) {
			return;
		}

		$query->set( 'post_type', 'transaction' );
		$query->set( 'posts_per_page', -1 );

		$term_query = array();

		$donation_year = get_query_var( 'donation_year' );
		$donor_type = get_query_var( 'donor_type' );
		
		if ( is_tax( 'donation_year' ) || isset( $_GET['donation_year'] ) || isset( $_POST['donation_year'] ) ) {

			$var = isset( $_GET['donation_year'] ) ? $_GET['donation_year'] : $_POST['donation_year'];

			$term = $donation_year ? $donation_year : $var;


			if( $term ) {
				$term_query[] = array(
					'taxonomy' => 'donation_year',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $term ),
				);
			}
		}

		if ( is_tax( 'donor_type' ) || isset( $_GET['donor_type'] ) || isset( $_POST['donor_type'] ) ) {

			$var = isset( $_GET['donor_type'] ) ? $_GET['donor_type'] : $_POST['donor_type'];

			$term = $donor_type ? $donor_type : $var;


			if( $term ) {
				$term_query[] = array(
					'taxonomy' => 'donor_type',
					'field'    => 'slug',
					'terms'    => sanitize_text_field( $term ),
				);
			}
		}

		$query->set( 'term_query', $term_query );
	}

}

new Data_Handler();
