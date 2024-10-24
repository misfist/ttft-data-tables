<?php
/**
 * AJAX class
 */
namespace Ttft\Data_Tables;

class AJAX {
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
					'message' => __( 'No data found.', 'data-tables' ),
				)
			);
		}
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
			return __( 'Table Type attribute is required.', 'data-tables' );
		}

		$data = new Data();

		$output = $data->generate_data_table( $args['table_type'], $args );

		return $output;
	}
}