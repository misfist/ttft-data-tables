<?php
/**
 * REST API
 *
 * @package site-functionality
 */

namespace Ttft\Data_Tables\API;

use Ttft\Data_Tables\Data\Data;

use function \Ttft\Data_Tables\Data\get_think_tank_archive_data;
use function \Ttft\Data_Tables\Data\get_single_think_tank_data;
use function \Ttft\Data_Tables\Data\get_donor_archive_data;
use function \Ttft\Data_Tables\Data\get_single_donor_data;
use function \Ttft\Data_Tables\Data\generate_star_rating;

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
	 * API constructor.
	 */
	public function __construct() {
		$this->settings = array(
			'namespace'   => 'data-tables/v1',
			'endpoint'    => '/table-data',
			'table_types' => array(
				'think_tank_archive',
				'donor_archive',
				'single_think_tank',
				'single_donor',
			),
		);

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
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
						'validate_callback' => function( $param ) {
							return in_array( $param, $this->settings['table_types'], true );
						},
					),
					'think_tank'    => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'donor'         => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'donation_year' => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'donor_type'    => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'search'        => array(
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
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
			case 'think_tank_archive':
				$data = $this->get_think_tank_archive_json( $donation_year, $search );
				break;

			case 'donor_archive':
				$data = $this->get_donor_archive_json( $donation_year, $donor_type, $search );
				break;

			case 'single_think_tank':
				$data = $this->get_single_think_tank_json( $think_tank, $donation_year, $donor_type );
				break;

			case 'single_donor':
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
		$data = get_think_tank_archive_data( $donation_year, $search );

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
		$data = get_single_think_tank_data( $think_tank, $donation_year, $donor_type );

		$response = array(
			'columns' => array(
				array(
					'data'      => 'donor',
					'title'     => __( 'Donor', 'ttft-data-tables' ),
					'className' => 'column-donor',
					'type'      => 'html',
				),
				array(
					'data'      => 'amount',
					'title'     => __( 'Min Amount', 'ttft-data-tables' ),
					'className' => 'column-numeric column-min-amount',
					'type'      => 'currency',
				),
				array(
					'data'      => 'source',
					'title'     => __( 'Source', 'ttft-data-tables' ),
					'className' => 'column-source',
					'type'      => 'html',
				),
				array(
					'data'      => 'donor_type',
					'title'     => __( 'Type', 'ttft-data-tables' ),
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
		$data = get_donor_archive_data( $donation_year, $donor_type, $search );

		$response = array(
			'columns' => array(
				array(
					'data'      => 'donor',
					'title'     => __( 'Donor', 'ttft-data-tables' ),
					'className' => 'column-donor',
					'type'      => 'html',
				),
				array(
					'data'      => 'amount',
					'title'     => __( 'Min Amount', 'ttft-data-tables' ),
					'className' => 'column-numeric column-min-amount',
					'type'      => 'currency',
				),
				array(
					'data'      => 'donor_type',
					'title'     => __( 'Type', 'ttft-data-tables' ),
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
		$data = get_single_donor_data( $donor, $donation_year, $donor_type );

		$response = array(
			'columns' => array(
				array(
					'data'      => 'think_tank',
					'title'     => __( 'Think Tank', 'ttft-data-tables' ),
					'className' => 'column-think-tank',
					'type'      => 'html',
				),
				array(
					'data'      => 'donor',
					'title'     => __( 'Donor', 'ttft-data-tables' ),
					'className' => 'column-donor',
					'type'      => 'html',
				),
				array(
					'data'      => 'amount',
					'title'     => __( 'Min Amount', 'ttft-data-tables' ),
					'className' => 'column-numeric column-min-amount',
					'type'      => 'currency',
				),
				array(
					'data'      => 'source',
					'title'     => __( 'Source', 'ttft-data-tables' ),
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
