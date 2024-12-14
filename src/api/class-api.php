<?php
/**
 * REST API
 *
 * @package site-functionality
 */

namespace Ttft\Data_Tables\API;

use Ttft\Data_Tables\Data;

use function \Ttft\Data_Tables\get_think_tank_archive_data;
use function \Ttft\Data_Tables\get_single_think_tank_data;
use function \Ttft\Data_Tables\get_donor_archive_data;
use function \Ttft\Data_Tables\get_single_donor_data;
use function \Ttft\Data_Tables\generate_star_rating;

/**
 * Class API
 *
 * Handles REST API interactions for transaction data.
 */
class API {

	/**
	 * Array of settings and configuration values.
	 *
	 * @var array
	 */
	private $settings;

	/**
	 * Data object.
	 *
	 * @var Data
	 */
	protected $data;

	/**
	 * API constructor.
	 */
	public function __construct() {
		$this->settings = array(
			'namespace'   => 'data-tables/v1',
			'endpoint'    => '/table-data',
			'table_types' => array(
				'think-tank-archive',
				'donor-archive',
				'single-think-tank',
				'single-donor',
				'full-data',
			),
			'cache_key'   => 'transaction_dataset',
		);

		$this->data = new Data();

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );

		add_action( 'save_post_transaction', array( $this, 'clear_transaction_cache' ) );
		add_action( 'deleted_post', array( $this, 'clear_transaction_cache' ) );
		add_action( 'edit_post', array( $this, 'clear_transaction_cache' ) );
		add_action( 'pmxi_after_xml_import', array( $this, 'after_import' ), 10, 2 );
	}

	/**
	 * Registers the REST API route.
	 */
	public function register_routes() {
		register_rest_route(
			$this->settings['namespace'],
			$this->settings['endpoint'],
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_data' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'table_type'    => array(
						'required'          => true,
						'type'              => 'string',
						'enum'              => $this->settings['table_types'],
						'validate_callback' => function( $param ) {
							return in_array( $param, $this->settings['table_types'], true );
						},
					),
					'think_tank'    => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'donor'         => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'donation_year' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'donor_type'    => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'search'        => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			$this->settings['namespace'],
			'transaction-data',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'serve_transaction_dataset' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->settings['namespace'],
			'transaction-data/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_transaction_dataset' ),
				'permission_callback' => '__return_true',
			)
		);

	}

	/**
	 * Delete the transaction cache after an import.
	 * 
	 * @link https://www.wpallimport.com/documentation/developers/action-reference/pmxi_after_xml_import/
	 *
	 * @param  int $import_id
	 * @param  obj $import_settings
	 * @return void
	 */
	public function after_import( $import_id, $import_settings ): void {
		delete_option( $this->settings['cache_key'] );
	}

	/**
	 * Clear the transaction cache.
	 *
	 * @param int $post_id The post ID.
	 */
	public function clear_transaction_cache( $post_id ) {
		if ( get_post_type( $post_id ) !== 'transaction' ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		delete_option( $this->settings['cache_key'] );
	}

	/**
	 * Get transaction data.
	 */
	public function get_transaction_dataset(): array {
		$cache_key = $this->settings['cache_key'];

		// Check if the data is cached in options.
		$cached_data = get_option( $cache_key, false );

		if ( $cached_data !== false ) {
			return $cached_data;
		}

		$post_type = 'transaction';
		$args      = array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);

		$transactions = get_posts( $args );

		$data = array();

		if ( ! empty( $transactions ) && ! is_wp_error( $transactions ) ) {
			foreach ( $transactions as $transaction_id ) {
				$donor      = get_the_terms( $transaction_id, 'donor' );
				$think_tank = get_the_terms( $transaction_id, 'think_tank' );
				$year       = get_the_terms( $transaction_id, 'donation_year' );
				$donor_type = get_the_terms( $transaction_id, 'donor_type' );
				$parent_id  = ( $donor && ! is_wp_error( $donor ) && 0 !== $donor[0]->parent ) ? $donor[0]->parent : null;

				$data[] = array(
					'Specific Donor'                       => ( $donor && ! is_wp_error( $donor ) ) ? $donor[0]->name : '',
					'Parent Organization/Country'          => ( $parent_id ) ? get_term( $parent_id, 'donor' )->name : '',
					'Recipient Think Tank'                 => ( $think_tank && ! is_wp_error( $think_tank ) ) ? $think_tank[0]->name : '',
					'Year'                                 => ( $year && ! is_wp_error( $year ) ) ? $year[0]->name : '',
					'Donor Type'                           => ( $donor_type && ! is_wp_error( $donor_type ) ) ? $donor_type[0]->name : '',
					'Exact Amount (if provided)'           => (int) get_post_meta( $transaction_id, 'amount', true ) ?: (int) '0',
					'Minimum Donation (if range provided)' => (int) get_post_meta( $transaction_id, 'amount_min', true ) ?: (int) '0',
					'Maximum Donation (if range provided)' => (int) get_post_meta( $transaction_id, 'amount_max', true ) ?: (int) '0',
					'Minimum + Exact Donation'             => (int) get_post_meta( $transaction_id, 'amount_calc', true ) ?: (int) '0',
					'Think Tank Disclosed Funding Amount/Range' => get_post_meta( $transaction_id, 'disclosed', true ) ? true : false,
					'Source'                               => get_post_meta( $transaction_id, 'source', true ) ?: '',
				);
			}
		}

		// Persist the data using options.
		update_option( $cache_key, $data );

		return $data;
	}

	/**
	 * Get transaction data.
	 *
	 * @param \WP_REST_Request $request The REST API request.
	 * @return \WP_REST_Response The response containing JSON data.
	 */
	public function serve_transaction_dataset( \WP_REST_Request $request ): \WP_REST_Response {
		$data = $this->get_transaction_dataset();

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Export transaction data as CSV.
	 *
	 * @param \WP_REST_Request $request The REST API request.
	 */
	public function export_transaction_dataset( \WP_REST_Request $request ) {
		$data = $this->get_transaction_dataset();

		if ( empty( $data ) ) {
			wp_send_json_error( __( 'No data available for CSV export.', 'data-tables' ), 404 );
			exit;
		}

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=think-tank-funding-dataset.csv' );

		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, array_keys( $data[0] ) );

		foreach ( $data as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output );

		exit;
	}

	/**
	 * Handles the request to retrieve data.
	 *
	 * @param \WP_REST_Request $request The REST API request.
	 * @return \WP_REST_Response The response containing JSON data.
	 */
	public function get_data( \WP_REST_Request $request ): \WP_REST_Response {
		$table_type    = $request->get_param( 'table_type' );
		$think_tank    = $this->get_term_from_param( $request->get_param( 'think_tank' ), 'think_tank' );
		$donor         = $this->get_term_from_param( $request->get_param( 'donor' ), 'donor' );
		$donation_year = $this->get_term_from_param( $request->get_param( 'donation_year' ), 'donation_year' );
		$donor_type    = $this->get_term_from_param( $request->get_param( 'donor_type' ), 'donor_type' );
		$search        = $request->get_param( 'search' );

		switch ( $table_type ) {
			case 'think-tank-archive':
				$data = $this->get_think_tank_archive_json( $donation_year, $search );
				break;

			case 'donor-archive':
				$data = $this->get_donor_archive_json( $donation_year, $donor_type, $search );
				break;

			case 'single-think-tank':
				$data = $this->get_single_think_tank_json( $think_tank, $donation_year, $donor_type );
				break;

			case 'single-donor':
				$data = $this->get_single_donor_json( $donor, $donation_year, $donor_type );
				break;

			default:
				return new \WP_REST_Response( array( 'error' => 'Invalid table_type' ), 400 );
		}

		return new \WP_REST_Response( json_decode( $data, true ), 200 );
	}

	/**
	 * Get term by ID or slug from parameter.
	 *
	 * @param mixed  $param  The parameter which can be a slug or ID.
	 * @param string $taxonomy The taxonomy to look up.
	 * @return string|null The term slug, or null if not found.
	 */
	private function get_term_from_param( $param, $taxonomy ): ?string {
		if ( empty( $param ) ) {
			return null;
		}

		if ( is_numeric( $param ) ) {
			$term = get_term_by( 'id', intval( $param ), $taxonomy );
		} else {
			$term = get_term_by( 'slug', sanitize_text_field( $param ), $taxonomy );
		}

		return $term ? $term->slug : null;
	}

	/**
	 * Retrieve term names for a specific taxonomy by post ID.
	 *
	 * @param int    $post_id    The post ID.
	 * @param string $taxonomy   The taxonomy name.
	 *
	 * @return array An array of term names.
	 */
	public function get_term_ids_by_post_id( $post_id, $taxonomy ) {
		return wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
	}

	/**
	 * Get JSON data for think tank archive table.
	 *
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $search Optional. Search query.
	 * @return string JSON-encoded data for DataTables.
	 */
	public function get_think_tank_archive_json( $donation_year = '', $search = '' ): string {
		$donation_year = sanitize_text_field( $donation_year );
		$search        = sanitize_text_field( $search );

		// Custom function call to get think tank archive data.
		$data = $this->data->get_think_tank_archive_data( $donation_year, $search );

		$response = array(
			'columns' => array(
				array(
					'data'      => 'think_tank',
					'title'     => 'Think Tank',
					'className' => 'column-think-tank',
					'type'      => 'html',
				),
				// Generate columns based on donor types.
			),
			'data'    => array(),
		);

		if ( ! empty( $data ) ) {
			$first_entry = reset( $data );
			foreach ( $first_entry['donor_types'] as $donor_type => $amount ) {
				$response['columns'][] = array(
					'data'      => sanitize_title( $donor_type ),
					'title'     => esc_html( $donor_type ),
					'className' => 'column-numeric column-min-amount',
					'type'      => 'currency',
				);
			}
			$response['columns'][] = array(
				'data'      => 'transparency_score',
				'title'     => 'Score',
				'className' => 'column-numeric column-transparency-score noExport',
				'type'      => 'html',
			);

			foreach ( $data as $think_tank_slug => $think_tank_data ) {
				$row = array(
					'think_tank' => sprintf(
						'<a href="%s">%s</a>',
						esc_url( get_term_link( $think_tank_slug, 'think_tank' ) ),
						esc_html( $think_tank_data['think_tank'] )
					),
				);

				foreach ( $think_tank_data['donor_types'] as $donor_type => $amount ) {
					$row[ sanitize_title( $donor_type ) ] = number_format( $amount, 0, '.', ',' );
				}

				// Custom function call to convert transparency score.
				$row['transparency_score'] = generate_star_rating( intval( $think_tank_data['transparency_score'] ) );

				$response['data'][] = $row;
			}
		}

		return wp_json_encode( $response );
	}

	/**
	 * Get JSON data for a single think tank table.
	 *
	 * @param string $think_tank Optional. Slug of the think tank.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type Optional. Slug of the donor type.
	 * @return string JSON-encoded data for DataTables.
	 */
	public function get_single_think_tank_json( $think_tank = '', $donation_year = '', $donor_type = '' ): string {
		$think_tank    = sanitize_text_field( $think_tank );
		$donation_year = sanitize_text_field( $donation_year );
		$donor_type    = sanitize_text_field( $donor_type );

		// Custom function call to get data for a single think tank.
		$data = $this->data->get_single_think_tank_data( $think_tank, $donation_year, $donor_type );

		$response = array(
			'columns' => array(
				array(
					'data'      => 'donor',
					'title'     => __( 'Donor', 'data-tables' ),
					'className' => 'column-donor',
					'type'      => 'html',
				),
				array(
					'data'      => 'amount',
					'title'     => __( 'Min Amount', 'data-tables' ),
					'className' => 'column-numeric column-min-amount',
					'type'      => 'currency',
				),
				array(
					'data'      => 'source',
					'title'     => __( 'Source', 'data-tables' ),
					'className' => 'column-source',
					'type'      => 'html',
				),
				array(
					'data'      => 'donor_type',
					'title'     => __( 'Type', 'data-tables' ),
					'className' => 'column-type',
					'type'      => 'html',
				),
			),
			'data'    => array(),
		);

		foreach ( $data as $row ) {
			$response['data'][] = array(
				'donor'      => sprintf(
					'<a href="%s">%s</a>',
					esc_url( $row['donor_link'] ),
					esc_html( $row['donor'] )
				),
				'amount'     => number_format( $row['amount_calc'], 0, '.', ',' ),
				'source'     => $row['source'] ? sprintf(
					'<a href="%s" class="source-link" target="_blank">%s</a>',
					esc_url( $row['source'] ),
					esc_html( $row['source'] )
				) : '',
				'donor_type' => esc_html( $row['donor_type'] ),
			);
		}

		return wp_json_encode( $response );
	}

	/**
	 * Get JSON data for donor archive table.
	 *
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type Optional. Slug of the donor type.
	 * @param string $search Optional. Search query.
	 * @return string JSON-encoded data for DataTables.
	 */
	public function get_donor_archive_json( $donation_year = '', $donor_type = '', $search = '' ): string {
		$donation_year = sanitize_text_field( $donation_year );
		$donor_type    = sanitize_text_field( $donor_type );
		$search        = sanitize_text_field( $search );

		// Custom function call to get donor archive data.
		$data = $this->data->get_donor_archive_data( $donation_year, $donor_type, $search );

		$response = array(
			'columns' => array(
				array(
					'data'      => 'donor',
					'title'     => __( 'Donor', 'data-tables' ),
					'className' => 'column-donor',
					'type'      => 'html',
				),
				array(
					'data'      => 'amount',
					'title'     => __( 'Min Amount', 'data-tables' ),
					'className' => 'column-numeric column-min-amount',
					'type'      => 'currency',
				),
				array(
					'data'      => 'donor_type',
					'title'     => __( 'Type', 'data-tables' ),
					'className' => 'column-donor-type',
					'type'      => 'html',
				),
			),
			'data'    => array(),
		);

		foreach ( $data as $row ) {
			$response['data'][] = array(
				'donor'      => sprintf(
					'<a href="%s">%s</a>',
					esc_url( $row['donor_link'] ),
					esc_html( $row['donor'] )
				),
				'amount'     => number_format( $row['amount_calc'], 0, '.', ',' ),
				'donor_type' => esc_html( $row['donor_type'] ),
			);
		}

		return wp_json_encode( $response );
	}

	/**
	 * Get JSON data for a single donor table.
	 *
	 * @param string $donor Optional. Slug of the donor.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type Optional. Slug of the donor type.
	 * @return string JSON-encoded data for DataTables.
	 */
	public function get_single_donor_json( $donor = '', $donation_year = '', $donor_type = '' ): string {
		$donor         = sanitize_text_field( $donor );
		$donation_year = sanitize_text_field( $donation_year );
		$donor_type    = sanitize_text_field( $donor_type );

		// Custom function call to get data for a single donor.
		$data = $this->data->get_single_donor_data( $donor, $donation_year, $donor_type );

		$response = array(
			'columns' => array(
				array(
					'data'      => 'think_tank',
					'title'     => __( 'Think Tank', 'data-tables' ),
					'className' => 'column-think-tank',
					'type'      => 'html',
				),
				array(
					'data'      => 'donor',
					'title'     => __( 'Donor', 'data-tables' ),
					'className' => 'column-donor',
					'type'      => 'html',
				),
				array(
					'data'      => 'amount',
					'title'     => __( 'Min Amount', 'data-tables' ),
					'className' => 'column-numeric column-min-amount',
					'type'      => 'currency',
				),
				array(
					'data'      => 'source',
					'title'     => __( 'Source', 'data-tables' ),
					'className' => 'column-source',
					'type'      => 'html',
				),
			),
			'data'    => array(),
		);

		foreach ( $data as $row ) {
			$response['data'][] = array(
				'think_tank' => sprintf(
					'<a href="%s">%s</a>',
					esc_url( get_term_link( $row['think_tank_slug'], 'think_tank' ) ),
					esc_html( $row['think_tank'] )
				),
				'donor'      => esc_html( $row['donor'] ),
				'amount'     => number_format( $row['amount_calc'], 0, '.', ',' ),
				'source'     => $row['source'] ? sprintf(
					'<a href="%s" class="source-link" target="_blank">%s</a>',
					esc_url( $row['source'] ),
					esc_html( $row['source'] )
				) : '',
			);
		}

		return wp_json_encode( $response );
	}

}
