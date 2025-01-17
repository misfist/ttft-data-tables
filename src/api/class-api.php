<?php
/**
 * REST API
 *
 * @package site-functionality
 */

namespace Ttft\Data_Tables\API;

use Ttft\Data_Tables\Data;

use function Ttft\Data_Tables\get_think_tank_archive_data;
use function Ttft\Data_Tables\get_single_think_tank_data;
use function Ttft\Data_Tables\get_donor_archive_data;
use function Ttft\Data_Tables\get_single_donor_data;
use function Ttft\Data_Tables\generate_star_rating;

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
			),
			'cache_key'   => 'transaction_dataset_',
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
						'validate_callback' => function ( $param ) {
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
				'args'                => array(
					'think_tank' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'donor'      => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'year'       => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'donor_type' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			$this->settings['namespace'],
			'transaction-data/export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'export_transaction_dataset' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'think_tank' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'donor'      => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'year'       => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'donor_type' => array(
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Delete the transaction cache after an import.
	 *
	 * @link https://www.wpallimport.com/documentation/developers/action-reference/pmxi_after_xml_import/
	 *
	 * @param  int $import_id The id of the import.
	 * @param  obj $import_settings The import settings object.
	 * @return void
	 */
	public function after_import( $import_id, $import_settings ): void {
		clear_cache();
	}

	/**
	 * Clear all cached transients created by this class.
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		global $wpdb;

		$cache_key_prefix = $this->settings['cache_key'];

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( '_transient_' . $cache_key_prefix ) . '%'
			)
		);

		foreach ( $results as $option_name ) {
			$transient_name = str_replace( '_transient_', '', $option_name );
			delete_transient( $transient_name );
		}
	}

	/**
	 * Build cache key for the query.
	 *
	 * @param  array $args Arguments from which to derive cache key.
	 * @return string
	 */
	public function get_cache_key( array $args = array() ): string {
		ksort( $args );

		$params = http_build_query( $args, '', '&' );

		return $this->settings['cache_key'] . md5( $params );
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

		$cache_key = $this->data->get_cache_key();

		delete_option( $cache_key );
	}

	/**
	 * Get transaction data.
	 *
	 * @param \WP_REST_Request $request The REST API request.
	 * @return array The transaction data.
	 */
	public function get_transaction_dataset( \WP_REST_Request $request ): array {
		$think_tank    = $this->get_term_from_param( $request->get_param( 'think_tank' ), 'think_tank' );
		$donor         = $this->get_term_from_param( $request->get_param( 'donor' ), 'donor' );
		$donation_year = $this->get_term_from_param( $request->get_param( 'donation_year' ), 'donation_year' );
		$donor_type    = $this->get_term_from_param( $request->get_param( 'donor_type' ), 'donor_type' );

		$args = array(
			'think_tank'    => $think_tank,
			'donor'         => $donor,
			'donation_year' => $donation_year,
			'donor_type'    => $donor_type,
		);

		$this->data->set_args( $args );

		$cache_key = $this->get_cache_key( $args );

		$cached_data = get_option( $cache_key );
		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$query = $this->data->generate_query();

		if ( empty( $query->posts ) ) {
			return array();
		}

		$data = array();

		$transactions = $query->posts;

		if ( ! empty( $transactions ) && ! is_wp_error( $transactions ) ) {
			foreach ( $transactions as $transaction_id ) {
				$data[] = $this->process_transaction( $transaction_id );
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
		$data = $this->get_transaction_dataset( $request );

		if ( empty( $data ) ) {
			return new \WP_REST_Response( array( 'error' => 'No data available' ), 404 );
		}

		$response = new \WP_REST_Response( $data, 200 );
		$response->header( 'X-WP-Total', count( $data ) );

		return $response;
	}

	/**
	 * Export transaction data as CSV.
	 *
	 * @param \WP_REST_Request $request The REST API request.
	 */
	public function export_transaction_dataset( \WP_REST_Request $request ) {
		$data = $this->get_transaction_dataset( $request );

		if ( empty( $data ) ) {
			wp_send_json_error( __( 'No data available for CSV export.', 'data-tables' ), 404 );
			exit;
		}

		$data = array_map(
			function ( $row ) {
				unset( $row['ID'] );
				return $row;
			},
			$data
		);

		$filename = $this->generate_filename( $request );

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );

		$output = fopen( 'php://output', 'w' );

		fputcsv( $output, array_keys( $data[0] ) );

		foreach ( $data as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output );

		exit;
	}

	/**
	 * Get the data for each transaction.
	 *
	 * @param  integer $transaction_id The post ID for transaction.
	 * @return array
	 */
	public function process_transaction( int $transaction_id ): array {
		$donors     = wp_get_post_terms(
			$transaction_id,
			'donor',
			array(
				'orderby' => 'parent',
			)
		);
		$think_tank = get_the_terms( $transaction_id, 'think_tank' );
		$year       = get_the_terms( $transaction_id, 'donation_year' );
		$donor_type = get_the_terms( $transaction_id, 'donor_type' );

		$specific_donor = '';
		$parent_id      = null;

		if ( $donors && ! is_wp_error( $donors ) ) {
			if ( count( $donors ) === 1 ) {
				$specific_donor = $donors[0]->name;
			} else {
				$specific_donor = end( $donors )->name;
				$parent_id      = current( $donors )->parent ?: null;
			}
		}

		return array(
			'id'                                        => $transaction_id,
			'Specific Donor'                            => $specific_donor,
			'Parent Organization/Country'               => $parent_id ? get_term( $parent_id )->name : '',
			'Recipient Think Tank'                      => ( $think_tank && ! is_wp_error( $think_tank ) ) ? $think_tank[0]->name : '',
			'Year'                                      => ( $year && ! is_wp_error( $year ) ) ? $year[0]->name : '',
			'Donor Type'                                => ( $donor_type && ! is_wp_error( $donor_type ) ) ? $donor_type[0]->name : '',
			'Exact Amount (if provided)'                => (int) get_post_meta( $transaction_id, 'amount', true ) ?: 0,
			'Minimum Donation (if range provided)'      => (int) get_post_meta( $transaction_id, 'amount_min', true ) ?: 0,
			'Maximum Donation (if range provided)'      => (int) get_post_meta( $transaction_id, 'amount_max', true ) ?: 0,
			'Minimum + Exact Donation'                  => (int) get_post_meta( $transaction_id, 'amount_calc', true ) ?: 0,
			'Think Tank Disclosed Funding Amount/Range' => (bool) get_post_meta( $transaction_id, 'disclosed', true ) ? true : false,
			'Source'                                    => get_post_meta( $transaction_id, 'source', true ) ?: '',
		);
	}

	/**
	 * Handles the request to retrieve data.
	 *
	 * @param \WP_REST_Request $request The REST API request.
	 * @return \WP_REST_Response The response containing JSON data.
	 */
	public function get_data( \WP_REST_Request $request ): \WP_REST_Response {
		$table_type    = sanitize_text_field( $request->get_param( 'table_type' ) );
		$think_tank    = $this->get_term_from_param( $request->get_param( 'think_tank' ), 'think_tank' );
		$donor         = $this->get_term_from_param( $request->get_param( 'donor' ), 'donor' );
		$donation_year = $this->get_term_from_param( $request->get_param( 'donation_year' ), 'donation_year' );
		$donor_type    = $this->get_term_from_param( $request->get_param( 'donor_type' ), 'donor_type' );
		$search        = sanitize_text_field( $request->get_param( 'search' ) );
		$draw          = intval( $request->get_param( 'draw' ) ); // Retrieve the draw parameter from the request.
		$start         = intval( $request->get_param( 'start' ) ) ?? 0;
		$length        = intval( $request->get_param( 'length' ) ) ?? 10;

		switch ( $table_type ) {
			case 'think-tank-archive':
				$data = $this->get_think_tank_archive_json( $donation_year, $search );
				break;

			case 'donor-archive':
				$data = $this->get_donor_archive_json( $donation_year, $donor_type, $search );
				break;

			case 'single-think-tank':
				if ( ! $think_tank ) {
					return new \WP_REST_Response( array( 'error' => esc_attr__( 'Think tank is required', 'data-tables' ) ), 400 );
				}
				$data = $this->get_single_think_tank_json( $think_tank, $donation_year, $donor_type );
				break;

			case 'single-donor':
				if ( ! $donor ) {
					return new \WP_REST_Response( array( 'error' => esc_attr__( 'Donor is required', 'data-tables' ) ), 400 );
				}
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

	/**
	 * Generate a filename with a hash based on request parameters.
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @param string           $filename The filename to use for export.
	 * @param string           $extension The extension used for file.
	 * @return string The generated filename.
	 */
	public function generate_filename( \WP_REST_Request $request, string $filename = 'think-tank-funding-dataset', string $extension = 'csv' ): string {
		$expected_params = array( 'think_tank', 'donor', 'year', 'donor_type' );

		$params = array_map(
			'sanitize_text_field',
			array_intersect_key( $request->get_params(), array_flip( $expected_params ) )
		);

		$params = array_filter( $params );

		if ( empty( $params ) ) {
			return sprintf( '%s.%s', $filename, $extension );
		}

		ksort( $params );

		$hash = md5( json_encode( $params ) );

		return sprintf( '%s-%s.%s', $filename, $hash, $extension );
	}
}
