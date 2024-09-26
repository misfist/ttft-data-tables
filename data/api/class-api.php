<?php
/**
 * REST API
 *
 * @package site-functionality
 */

namespace TTFT\Data_Tables\API;

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
			'namespace'              => 'site-functionality/v1',
			'endpoint'               => '/transaction-data',
			'taxonomies'             => array(
				'donors',
				'think-tanks',
				'years',
				'donor-types',
			),
			'meta_keys'              => array(
				'donor_parent_name',
				'donor_parent_id',
				'amount',
				'amount_min',
				'amount_max',
				'amount_calc',
				'source',
				'source_notes',
				'think_tank',
			),
			'default_posts_per_page' => 200,
			'table_types'            => array(
				'single-think-tank',
				'single-donor',
				'think-tank-archive',
				'donor-archive',
				'top-10',
			),
			'default_table_type'     => 'single-think-tank',
		);

		add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
	}

	/**
	 * Registers the REST route for transactions.
	 */
	public function register_rest_route() {
		register_rest_route(
			$this->settings['namespace'],
			$this->settings['endpoint'],
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_rest_request' ),
				'args'                => $this->get_rest_route_args(),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->settings['namespace'],
			'data-table',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle_rest_request' ),
				'args'                => $this->get_rest_route_args(),
				'permission_callback' => '__return_true',
			)
		);

	}

	/**
	 * Retrieves arguments for the REST route.
	 *
	 * @return array Array of arguments for the REST route.
	 */
	private function get_rest_route_args() {
		$args = array();
		foreach ( $this->settings['taxonomies'] as $taxonomy ) {
			$args[ "{$taxonomy}" ]      = array(
				'required'          => false,
				'validate_callback' => function( $param, $request, $key ) {
					return is_string( $param ) || is_array( $param );
				},
			);
			$args[ "{$taxonomy}_name" ] = array(
				'required'          => false,
				'validate_callback' => function( $param, $request, $key ) {
					return is_string( $param ) || is_array( $param );
				},
			);
			$args[ "{$taxonomy}_id" ]   = array(
				'required'          => false,
				'validate_callback' => function( $param, $request, $key ) {
					return is_numeric( $param );
				},
			);
		}
		foreach ( $this->settings['meta_keys'] as $meta_key ) {
			$args[ $meta_key ] = array(
				'required'          => false,
				'validate_callback' => function( $param, $request, $key ) {
					return is_string( $param ) || is_numeric( $param );
				},
			);
		}
		$args['per_page'] = array(
			'required'          => false,
			'default'           => $this->settings['default_posts_per_page'],
			'validate_callback' => function( $param, $request, $key ) {
				return is_numeric( $param ) && $param > 0;
			},
		);

		$args['table_type'] = array(
			'required'          => false,
			'default'           => $this->settings['default_table_type'],
			'validate_callback' => function( $param, $request, $key ) {
				return is_string( $param );
			},
		);
		return $args;
	}

	/**
	 * Handles the REST request and retrieves post data.
	 *
	 * @param \WP_REST_Request $request The REST request object.
	 * @return \WP_REST_Response The response containing the post data.
	 */
	public function handle_rest_request( \WP_REST_Request $request ) {
		$params = array();

		$params['table_type'] = ( $request->get_param( 'table_type' ) ) ? sanitize_text_field( $request->get_param( 'table_type' ) ) : $this->settings['default_table_type'];
		$params['donor'] = ( $request->get_param( 'donors' ) ) ? sanitize_text_field( $request->get_param( 'donors' ) ) : '';
		$params['think_tank'] = ( $request->get_param( 'think-tanks' ) ) ? sanitize_text_field( $request->get_param( 'think-tanks' ) ) : '';
		$params['donation_year'] = ( $request->get_param( 'years' ) ) ? sanitize_text_field( $request->get_param( 'years' ) ) : '';
		$params['donor_type'] = ( $request->get_param( 'donor-types' ) ) ? sanitize_text_field( $request->get_param( 'donor-types' ) ) : '';
		$data = array();
		
		$route = $request->get_route();
		$route_array = explode( '/', $route );

		if( 'transaction-data' === end( $route_array ) ) {
			// $data = $this->get_table_data( $params );
		} elseif( 'data-table' === end( $route_array ) ) {
			// $data = $this->get_data_table( $params );
		}

		return new \WP_REST_Response( $data, 200 );
	}

		/**
	 * Get data table
	 *
	 * @param  array $params
	 * @return array
	 */
	public function get_table_data( $params = array() ) : array {
		$table_type = ( isset( $params['table_type'] ) ) ? sanitize_text_field( $params['table_type'] ) : sanitize_text_field( $this->settings['default_table_type'] );
		$donor = ( isset( $params['donor'] ) ) ? sanitize_text_field( $params['donor'] ) : '';
		$think_tank = ( isset( $params['think_tank'] ) ) ? sanitize_text_field( $params['think_tank'] ) : '';
		$donation_year = ( isset( $params['donation_year'] ) ) ? sanitize_text_field( $params['donation_year'] ) : '';
		$donor_type = ( isset( $params['donor_type'] ) ) ? sanitize_text_field( $params['donor_type'] ) : '';

		$data = array();

		if( $table_type ) {
			$data['details']['table_type'] = $table_type;

			switch ( $table_type ) {
				case 'single-think-tank':
					$data = \Site_Functionality\Integrations\Data_Tables\Think_Tank::get_single_think_tank_data( $think_tank, $donation_year, $donor_type );

					break;
				case 'single-donor':
					$data = \Site_Functionality\Integrations\Data_Tables\Donor::get_single_donor_data( $donor, $donation_year, $donor_type );

					break;
				case 'think-tank-archive':
					$data = \Site_Functionality\Integrations\Data_Tables\Think_Tank::get_think_tank_archive_data( $donation_year );
					
					break;
				case 'donor-archive':
					$data = \Site_Functionality\Integrations\Data_Tables\Donor::get_donor_archive_data( $donation_year, $donor_type );

					break;
				case 'top-10':
					break;
		
				default:
					$data = \Site_Functionality\Integrations\Data_Tables\Think_Tank::generate_think_tank_table( $think_tank, $donation_year, $donor_type );

					break;
			}

		}
		
		return $data;
	}

	/**
	 * Get data table
	 *
	 * @param  array $params
	 * @return array
	 */
	public function get_data_table( $params = array() ) : array {
		$table_type = ( isset( $params['table_type'] ) ) ? sanitize_text_field( $params['table_type'] ) : sanitize_text_field( $this->settings['default_table_type'] );
		$donor = ( isset( $params['donor'] ) ) ? sanitize_text_field( $params['donor'] ) : '';
		$think_tank = ( isset( $params['think_tank'] ) ) ? sanitize_text_field( $params['think_tank'] ) : '';
		$donation_year = ( isset( $params['donation_year'] ) ) ? sanitize_text_field( $params['donation_year'] ) : '';
		$donor_type = ( isset( $params['donor_type'] ) ) ? sanitize_text_field( $params['donor_type'] ) : '';

		$data = array(
			'details' => array()
		);

		if( $table_type ) {
			$data['details']['table_type'] = $table_type;

			switch ( $table_type ) {
				case 'single-think-tank':
					$data['content'] = \Site_Functionality\Integrations\Data_Tables\Think_Tank::generate_single_think_tank_table( $think_tank, $donation_year, $donor_type );

					break;
				case 'single-donor':
					$data['content'] = \Site_Functionality\Integrations\Data_Tables\Donor::generate_single_donor( $donor, $donation_year, $donor_type );

					break;
				case 'think-tank-archive':
					$data['content'] = \Site_Functionality\Integrations\Data_Tables\Think_Tank::generate_think_tank_archive_table( $donation_year );
					
					break;
				case 'donor-archive':
					$data['content'] = \Site_Functionality\Integrations\Data_Tables\Donor::generate_donor_archive( $donation_year, $donor_type );

					break;
				case 'top-10':
					break;
		
				default:
					$data['content'] = \Site_Functionality\Integrations\Data_Tables\Think_Tank::generate_think_tank_table( $think_tank, $donation_year, $donor_type );

					break;
			}

			return $data;
		}

		$data['details']['table_type'] = esc_attr__( 'No table type provided.', 'data-tables' );
	}

	/**
	 * Retrieves taxonomy terms for a given post.
	 *
	 * @param int    $post_id    The post ID.
	 * @param string $taxonomy   The taxonomy name.
	 * @return array|string     Array of term names, a single term name, or an empty string.
	 */
	private function get_taxonomy_terms( $post_id, $taxonomy, $args = array() ) {
		$defaults = array(
			'taxonomy'   => $taxonomy,
			'object_ids' => array( $post_id ),
			'fields'     => 'names',
			'orderby'    => 'term_order',
		);

		$args = wp_parse_args( $args, $defaults );

		$terms = get_terms( $args );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return '';
		}

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			return $terms;
		} else {
			return $terms[0];
		}

		if ( is_taxonomy_hierarchical( $taxonomy ) ) {
			$defaults = array(
				'taxonomy'   => $taxonomy,
				'object_ids' => array( $post_id ),
				'fields'     => 'names',
				'orderby'    => 'term_order',
			);

			if ( is_wp_error( $terms ) ) {
				return '';
			}

			return $terms;
		}
	}

	/**
	 * Retrieves records whose 'think_tank' taxonomy term matches the current post.
	 *
	 * @param int $think_tank_ The slug of the 'think_tank' taxonomy term.
	 * @return array Array of matching records.
	 */
	public function get_records_by_think_tank( $think_tank ) {
		return $this->get_data( array( 'think_tank' => $think_tank ) );
	}

	/**
	 * Retrieves records whose 'think_tank' taxonomy term matches the current post.
	 *
	 * @param string $think_tank_name The name of the 'think_tank' taxonomy term.
	 * @return array Array of matching records.
	 */
	public function get_records_by_think_tank_name( $think_tank_name ) {
		return $this->get_data( array( 'think_tank_name' => $think_tank_name ) );
	}

	/**
	 * Retrieves records whose 'donor' taxonomy term matches the current post.
	 *
	 * @param string $donor_name The name of the 'donor' taxonomy term.
	 * @return array Array of matching records.
	 */
	public function get_records_by_donor_name( $donor_name ) {
		return $this->get_data( array( 'donor_name' => $donor_name ) );
	}

	/**
	 * Retrieves records whose 'donor' taxonomy term matches the current post.
	 *
	 * @param int $donor The slug of the 'donor' taxonomy term.
	 * @return array Array of matching records.
	 */
	public function get_records_by_donor( $donor ) {
		return $this->get_data( array( 'donor' => $donor ) );
	}

	/**
	 * Retrieves records whose 'donor' taxonomy term matches the given name.
	 *
	 * @param string $donor_name The name of the 'donor' taxonomy term.
	 * @return array Array of matching records.
	 */
	public static function get_donors_by_name( $donor_name ) {
		$instance = new self();
		return $instance->get_data( array( 'donor_name' => $donor_name ) );
	}

	/**
	 * Retrieves records whose 'donor' taxonomy term matches the given name.
	 *
	 * @param string $donor The slug of the 'donor' taxonomy term.
	 * @return array Array of matching records.
	 */
	public static function get_donors_by_slug( $donor_slug ) {
		$instance = new self();
		return $instance->get_data( array( 'donor' => $donor_slug ) );
	}

	/**
	 * Retrieves records whose 'think_tank' taxonomy term matches the given name.
	 *
	 * @param string $think_tank_slug The slug of the 'think_tank' taxonomy term.
	 * @return array Array of matching records.
	 */
	public static function get_think_tanks_by_slug( $think_tank_slug ) {
		$instance = new self();
		return $instance->get_data( array( 'think_tank' => $think_tank_slug ) );
	}

	/**
	 * Retrieves records whose 'think_tank' taxonomy term matches the given name.
	 *
	 * @param string $think_tank_name The name of the 'think_tank' taxonomy term.
	 * @return array Array of matching records.
	 */
	public static function get_think_tanks_by_name( $think_tank_name ) {
		$instance = new self();
		return $instance->get_data( array( 'think_tank_name' => $think_tank_name ) );
	}

	/**
	 * Retrieves the cumulative value of 'amount_calc' based on the given taxonomy filters.
	 *
	 * @param array $args Array of arguments for filtering the data.
	 *                    Refer to https://developer.wordpress.org/reference/classes/wp_query/ for possible arguments.
	 * @return float Cumulative value of 'amount_calc'.
	 */
	public function get_cumulative_amount_calc( $args = array() ) {
		$data = $this->get_data( $args );
		return array_sum( wp_list_pluck( $data, 'amount_calc' ) );
	}

	/**
	 * Display data as an HTML table.
	 *
	 * @param array $data Array of data to display.
	 * @return string HTML table of the data.
	 */
	public static function display_table_data_think_tank( $data ) {
		if ( empty( $data ) ) {
			return '<p>' . esc_html__( 'No data available.', 'site-functionality' ) . '</p>';
		}

		usort(
			$data,
			function( $a, $b ) {
				return strcmp( $a['donor_name'] ?? '', $b['donor_name'] ?? '' );
			}
		);

		usort(
			$data,
			function( $a, $b ) {
				$year_a = isset( $a['donation_year'] ) ? (int) $a['donation_year'] : 0;
				$year_b = isset( $b['donation_year'] ) ? (int) $b['donation_year'] : 0;
				return $year_b - $year_a;
			}
		);

		$years       = array();
		$donor_types = array();
		foreach ( $data as $row ) {
			if ( ! empty( $row['donation_year'] ) ) {
				$years[ $row['donation_year'] ] = $row['donation_year'];
			}
			if ( ! empty( $row['donor_type'] ) ) {
				$donor_types[ $row['donor_type'] ] = $row['donor_type'];
			}
		}

		ob_start();
		?>
		<div class="filter-options">
			<fieldset>
				<legend><?php esc_html_e( 'Filter by Year', 'site-functionality' ); ?></legend>
				<?php foreach ( $years as $year ) : ?>
					<label>
						<input type="radio" name="year_filter" value="<?php echo esc_attr( $year ); ?>">
						<?php echo esc_html( $year ); ?>
					</label><br>
				<?php endforeach; ?>
			</fieldset>

			<!-- Donor Type Filter -->
			<fieldset>
				<legend><?php esc_html_e( 'Filter by Donor Type', 'site-functionality' ); ?></legend>
				<?php foreach ( $donor_types as $donor_type ) : ?>
					<label>
						<input type="radio" name="donor_type_filter" value="<?php echo esc_attr( $donor_type ); ?>">
						<?php echo esc_html( $donor_type ); ?>
					</label><br>
				<?php endforeach; ?>
			</fieldset>
		</div>
		<figure class="data-table">
			<table id="data-table-think-tank" class="hover stripe">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Donor', 'site-functionality' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Min Amount', 'site-functionality' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Source', 'site-functionality' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Donor Type', 'site-functionality' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Year', 'site-functionality' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $data as $row ) : ?>
						<tr>
							<td>
								<?php if ( ! empty( $row['donor_name'] ) ) : ?>
									<?php
										$donor_post = get_page_by_title( $row['donor_name'], OBJECT, 'donor' );
										$donor_url  = ! is_wp_error( $donor_post ) && $donor_post ? get_permalink( $donor_post->ID ) : '#';
									?>
									<a href="<?php echo esc_url( $donor_url ); ?>">
										<?php echo esc_html( $row['donor_name'] ); ?>
									</a>
								<?php endif; ?>
							</td>
							<td>
								<?php
								if ( isset( $row['amount_calc'] ) ) {
									echo esc_html( self::format_usd( $row['amount_calc'] ) );
								}
								?>
							</td>
							<td>
								<?php if ( ! empty( $row['source'] ) ) : ?>
									<a href="<?php echo esc_url( $row['source'] ); ?>" target="_blank" rel="noopener noreferrer">
										<span class="dashicons dashicons-admin-links"></span>
									</a>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $row['donor_type'] ) ) : ?>
									<?php
										$donor_type_term = get_term_by( 'name', $row['donor_type'], 'donor_type' );
										$donor_type_url  = ! is_wp_error( $donor_type_term ) && $donor_type_term ? get_term_link( $donor_type_term ) : '#';
									?>
									<a href="<?php echo esc_url( $donor_type_url ); ?>">
										<?php echo esc_html( $row['donor_type'] ); ?>
									</a>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $row['donation_year'] ) ) : ?>
									<?php
										$donation_year_term = get_term_by( 'name', $row['donation_year'], 'donation_year' );
										$donation_year_url  = ! is_wp_error( $donation_year_term ) && $donation_year_term ? get_term_link( $donation_year_term ) : '#';
									?>
									<a href="<?php echo esc_url( $donation_year_url ); ?>">
										<?php echo esc_html( $row['donation_year'] ); ?>
									</a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</figure>
		<?php
		return ob_get_clean(); // Get the buffer content and clean the buffer
	}

	/**
	 * Display donor data as a WordPress block-style table with links.
	 *
	 * @param array $data Array of data to display.
	 * @return string HTML table of the data.
	 */
	public static function display_table_data_donor( $data ) {
		if ( empty( $data ) ) {
			return '<p>' . esc_html__( 'No data available.', 'site-functionality' ) . '</p>';
		}

		// Sort the data array by donor_name in ascending order
		usort(
			$data,
			function( $a, $b ) {
				return strcmp( $a['donor_name'] ?? '', $b['donor_name'] ?? '' );
			}
		);

		ob_start(); // Start output buffering
		?>
		<figure class="data-table">
			<table id="data-table-donor">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Think Tank', 'site-functionality' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Donor', 'site-functionality' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Min Amount', 'site-functionality' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Source', 'site-functionality' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $data as $row ) : ?>
						<tr>
							<td>
								<?php if ( ! empty( $row['think_tank'] ) ) : ?>
									<?php
										$think_tank_post = get_page_by_title( $row['think_tank'], OBJECT, 'think_tank' );
										$think_tank_url  = ! is_wp_error( $think_tank_post ) && $think_tank_post ? get_permalink( $think_tank_post->ID ) : '#';
									?>
									<a href="<?php echo esc_url( $think_tank_url ); ?>">
										<?php echo esc_html( $row['think_tank'] ); ?>
									</a>
								<?php endif; ?>
							</td>
							<td>
								<?php if ( ! empty( $row['donor_name'] ) ) : ?>
									<?php
										$donor_term = get_term_by( 'name', $row['donor_name'], 'donor' );
										$donor_url  = ! is_wp_error( $donor_term ) && $donor_term ? get_term_link( $donor_term ) : '#';
									?>
									<a href="<?php echo esc_url( $donor_url ); ?>">
										<?php echo esc_html( $row['donor_name'] ); ?>
									</a>
								<?php endif; ?>
							</td>
							<td>
								<?php
								if ( isset( $row['amount_calc'] ) ) {
									echo esc_html( self::format_usd( $row['amount_calc'] ) );
								}
								?>
							</td>
							<td>
								<?php if ( ! empty( $row['think_tank'] ) ) : ?>
									<a href="<?php echo esc_url( $think_tank_url ); ?>" target="_blank" rel="noopener noreferrer">
										<span class="dashicons dashicons-admin-links"></span>
									</a>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</figure>
		<?php
		return ob_get_clean(); // Get the buffer content and clean the buffer
	}

	/**
	 * Format a number as US Dollar currency.
	 *
	 * @param float $amount The amount to format.
	 * @return string Formatted amount in US Dollars.
	 */
	private static function format_usd( $amount ) {
		return '$' . number_format( floatval( $amount ), 0, '.', ',' );
	}

	/**
	 * Retrieves data from REST API route based on provided arguments.
	 *
	 * @link https://developer.wordpress.org/reference/functions/wp_remote_get/
	 * @link https://developer.wordpress.org/reference/functions/wp_remote_retrieve_body/
	 * @link https://developer.wordpress.org/reference/functions/json_decode/
	 * @param array $args Arguments for REST API request.
	 * @return array Array of retrieved data.
	 */
	private function get_data( $args = array() ) {
		$response = wp_remote_get(
			rest_url( $this->settings['namespace'] . $this->settings['endpoint'] ),
			array(
				'timeout' => 30,
				'body'    => $args,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		return json_decode( $body, true );
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

}
