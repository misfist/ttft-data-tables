<?php
/**
 * Render class for rendering data tables.
 */
namespace Ttft\Data_Tables;

class Render {

	/**
	 * Data class instance.
	 *
	 * @var Data
	 */
	protected $data;

	/**
	 * Constructor to instantiate the Data class
	 */
	public function __construct() {
		$this->data = new Data();
	}

	/**
	 * Generate table for top ten
	 *
	 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
	 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
	 * @param int    $number_of_items Optional. The number of items to return. Default 10.
	 * @return string HTML table markup.
	 */
	public function generate_top_ten_table( string $donor_type = '', string $donation_year = '', int $number_of_items = 10 ): string {
		// Fetch the top ten data using Data
		$data = $this->data->get_top_ten_data( $donor_type, $donation_year, $number_of_items );

		ob_start();
		if ( $data ) :
			?>
			<table 
				id="table-<?php echo sanitize_title( $donor_type ); ?>" 
				class="top-ten-recipients dataTable" 
				data-total-rows="<?php echo intval( count( $data ) ); ?>"
				data-donor-type="<?php echo esc_attr( $donor_type ); ?>"
				data-donation-year="<?php echo esc_attr( $donation_year ); ?>"
				data-number="<?php echo esc_attr( $number_of_items ); ?>"
			>
				<thead>
					<tr>
						<th class="column-think-tank" scope="col"><?php esc_html_e( 'Think Tank', 'data-tables' ); ?></th>
						<th class="column-min-amount column-numeric" data-summed="true" scope="col"><?php esc_html_e( 'Min Amount', 'data-tables' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $data as $row ) : ?>
						<tr>
							<td class="column-think-tank" data-heading="<?php esc_attr_e( 'Think Tank', 'data-tables' ); ?>">
								<a href="<?php echo esc_url( get_term_link( $row['think_tank'], 'think_tank' ) ); ?>"><?php echo esc_html( $row['think_tank'] ); ?></a>
							</td>
							<td class="column-min-amount column-numeric" data-heading="<?php esc_attr_e( 'Min Amount', 'data-tables' ); ?>"><?php echo number_format( $row['total_amount'], 0 ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		endif;

		return ob_get_clean();
	}

	/**
	 * Generates the top portion of the table HTML.
	 *
	 * @param string      $table_type The CSS class to apply to the table, determining its styling.
	 * @param string|null $donation_year Optional. The year of the donations to be displayed in the caption. Default is null.
	 * @return string The generated HTML string for the top portion of the table.
	 */
	public function generate_table_top( string $table_type, ?string $donation_year = null, $count = 0 ): string {
		ob_start();
		$settings      = get_option( 'site_settings' );
		$rows_per_page = ( isset( $settings['rows_per_page'] ) && ! empty( $settings['rows_per_page'] ) ) ? (int) $settings['rows_per_page'] : 50;
		?>
		<table
			id="<?php echo TTFT_TABLE_ID . '-' . $table_type; ?>"
			data-wp-interactive="<?php echo TTFT_APP_NAMESPACE; ?>"
			class="<?php echo $table_type; ?> display dataTable"
			data-wp-bind--id='state.tableId'
			data-wp-bind--table-type='state.tableType'
			data-wp-bind--think-tank='state.thinkTank'
			data-wp-bind--year='state.donationYear'
			data-wp-bind--type='state.donorType'
			data-wp-bind--data-search-label='state.searchLabel'
			data-page-length='<?php echo esc_attr( $rows_per_page ); ?>'
			data-wp-context='<?php echo json_encode( array( 'pageLength' => $rows_per_page, 'foundRecords' => $count ) ); ?>'
		>
		<?php
		if ( $donation_year ) :
			?>
			<caption><?php printf( 'Donations in <span class="donation-year" data-wp-text="state.donationYear">%s</span>...', $donation_year ); ?></caption>
			<?php
		endif;

		return ob_get_clean();
	}

	/**
	 * Renders the top portion of the table HTML.
	 *
	 * @param string      $table_type The CSS class to apply to the table.
	 * @param string|null $donation_year Optional. The year of the donations to be displayed in the caption.
	 * @return void
	 */
	public function render_table_top( string $table_type, ?string $donation_year = null, $count = 0 ): void {
		echo $this->generate_table_top( $table_type, $donation_year, $count );
	}

	/**
	 * Return the appropriate table based on the table_type.
	 *
	 * @param string $table_type The type of table to generate.
	 * @param array  $args Parameters required for table generation.
	 * @return string The generated table HTML markup.
	 */
	public function generate_data_table( string $table_type, array $args ): string {
		$args = $this->convert_camel_to_snake_keys( $args );

		$donation_year = $args['donation_year'] ?? '';
		$donor_type    = $args['donor_type'] ?? '';
		$search        = $args['search'] ?? '';

		if ( $donation_year === 'all' ) {
			$donation_year = '';
		}

		if ( $donor_type === 'all' ) {
			$donor_type = '';
		}

		switch ( $table_type ) {
			case 'think-tank-archive':
				return $this->generate_think_tank_archive_table( $donation_year, $search );
			case 'single-think-tank':
				if ( empty( $args['think_tank'] ) ) {
					return __( 'Think tank is required for single-think-tank.', 'data-tables' );
				}
				return $this->generate_single_think_tank_table(
					$args['think_tank'],
					$donation_year,
					$donor_type
				);
			case 'donor-archive':
				return $this->generate_donor_archive_table(
					$donation_year,
					$donor_type,
					$search
				);
			case 'single-donor':
				if ( empty( $args['donor'] ) ) {
					return __( 'Donor is required for single-donor.', 'data-tables' );
				}
				return $this->generate_single_donor_table(
					$args['donor'],
					$donation_year,
					$donor_type
				);
			default:
				return __( 'Invalid table type.', 'data-tables' );
		}
	}

	/**
	 * Generate table for think tanks archive.
	 *
	 * @param string $donation_year The donation year to filter by.
	 * @param string $search Optional search query.
	 * @return string HTML table markup.
	 */
	public function generate_think_tank_archive_table( string $donation_year = '', string $search = '' ): string {
		$data = $this->data->get_think_tank_archive_data( $donation_year, $search );

		ob_start();
		if ( $data ) :
            $count = count( $data );
			$this->render_table_top( 'think-tank-archive', $donation_year, $count );
			?>
				<thead>
					<tr>
						<th class="column-think-tank" scope="col"><?php esc_html_e( 'Think Tank', 'data-tables' ); ?></th>
						<?php
						if ( ! empty( $data ) ) :
							$first_entry = reset( $data );
							$donor_types = array_keys( $first_entry['donor_types'] );
							foreach ( $donor_types as $donor_type ) :
								?>
								<th class="column-numeric column-min-amount" data-type="currency" data-summed="true" scope="col"><?php echo esc_html( $donor_type ); ?></th>
							<?php endforeach; ?>
						<?php endif; ?>
						<th class="column-numeric column-transparency-score" scope="col"><?php esc_html_e( 'Score', 'data-tables' ); ?></th>
					</tr>
				</thead>
				<tbody
                    data-found="<?php echo intval( $count ); ?>"
                    data-wp-bind--records-found='state.recordsFound'
                >
					<?php
					foreach ( $data as $think_tank_slug => $row ) :
						?>
						<tr data-think-tank="<?php echo esc_attr( $think_tank_slug ); ?>">
							<td class="column-think-tank" data-heading="<?php esc_attr_e( 'Think Tank', 'data-tables' ); ?>"><a href="<?php echo esc_url( get_term_link( $think_tank_slug, 'think_tank' ) ); ?>"><?php echo esc_html( $row['think_tank'] ); ?></a></td>
							<?php
							foreach ( $row['donor_types'] as $donor_type => $amount ) :
								/**
								 * Check:
								 * - Think tank didn't accept funding from donor type - display $this->data->settings['not_accepted']
								 * - Think tank has limited info - display $this->data->settings['no_data']
								 * - Think tank has disclosed === 'no' - display $this->data->settings['unknown_amount']
								 */
								$attrs = get_label_and_class_archive_think_tank( $row, $donor_type, $this->data->settings );
                                ?>
								<td class="column-numeric column-min-amount" data-heading="<?php echo esc_attr( $donor_type ); ?>" >
									<span class="<?php echo $attrs['class']; ?>" data-label="<?php echo $attrs['label']; ?>"><span><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?></span></span>
								</td>
								<?php
							endforeach;
							?>
							<td class="column-numeric column-transparency-score" data-heading="<?php esc_attr_e( 'Score', 'data-tables' ); ?>"><span class="screen-reader-text"><?php echo intval( $row['transparency_score'] ); ?></span><?php echo generate_star_rating( intval( $row['transparency_score'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		else :
			?>
			<div class="no-data">
				<p><?php esc_html_e( 'No data found.', 'data-tables' ); ?></p>
			</div>
			<?php
		endif;

		return ob_get_clean();
	}

	/**
	 * Generate table for individual think tank.
	 *
	 * @param string $think_tank The slug of the think tank.
	 * @param string $donation_year The slug of the donation year.
	 * @param string $donor_type The slug of the donor type.
	 * @return string HTML table markup.
	 */
	public function generate_single_think_tank_table( string $think_tank = '', string $donation_year = '', string $donor_type = '' ): string {
		$data = $this->data->get_single_think_tank_data( $think_tank, $donation_year, $donor_type );

		ob_start();
		if ( $data ) :
            $count = count( $data );
			$this->render_table_top( 'single-think-tank', $donation_year, $count );
			?>
				<thead>
					<tr>
						<th class="column-donor" scope="col"><?php esc_html_e( 'Donor', 'data-tables' ); ?></th>
						<th class="column-numeric column-min-amount" scope="col"><?php esc_html_e( 'Min Amount', 'data-tables' ); ?></th>
						<th class="column-source" scope="col"><?php esc_html_e( 'Source', 'data-tables' ); ?></th>
						<th class="column-type" scope="col"><?php esc_html_e( 'Type', 'data-tables' ); ?></th>
					</tr>
				</thead>
				<tbody
                    data-found="<?php echo intval( $count ); ?>"
                    data-wp-bind--records-found='state.recordsFound'
                >
					<?php
					foreach ( $data as $row ) :
						$amount = $row['amount_calc'];
                        $attrs = get_label_and_class_disclosed( $row, $this->data->settings );
                        /**
                         * Check:
                         * - Think tank has disclosed == 'no' - display $this->data->settings['unknown_amount']
                         */
						?>
						<tr data-think-tank="<?php echo esc_attr( $row['donor_slug'] ); ?>">
							<td class="column-donor" data-heading="<?php esc_attr_e( 'Donor', 'data-tables' ); ?>"><a href="<?php echo esc_url( $row['donor_link'] ); ?>"><?php echo esc_html( $row['donor'] ); ?></a></td>
							<td class="column-numeric column-min-amount" data-heading="<?php esc_attr_e( 'Min. Amount', 'data-tables' ); ?>">
                                <span class="<?php echo $attrs['class']; ?>" data-label="<?php echo $attrs['label']; ?>"><span><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?></span></span>
							</td>
							<td class="column-source"><?php echo ( $row['source'] ) ? sprintf( '<a href="%1$s" aria-label="%2$s" target="_blank"><span class="material-symbols-outlined" style="font-family:var(--wp--preset--font-family--icon);" role="img" aria-label="%2$s">link</span></a>', esc_url( $row['source'] ), esc_attr__( 'Link to source', 'data-tables' ) ) : ''; ?></td>
							<td class="column-donor-type" data-heading="<?php esc_attr_e( 'Type', 'data-tables' ); ?>"><?php echo $row['donor_type']; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		else :
			?>
			<div class="no-data">
				<p><?php esc_html_e( 'No data found.', 'data-tables' ); ?></p>
			</div>
			<?php
		endif;

		return ob_get_clean();
	}

	/**
	 * Generate table for donors archive.
	 *
	 * @param string $donation_year The donation year.
	 * @param string $donor_type The donor type.
	 * @param string $search Optional search term.
	 * @return string HTML table markup.
	 */
	public function generate_donor_archive_table( string $donation_year = '', string $donor_type = '', string $search = '' ): string {
		$data = $this->data->get_donor_archive_data( $donation_year, $donor_type, $search );

		ob_start();
		if ( $data ) :
            $count = count( $data );
			$this->render_table_top( 'donor-archive', $donation_year, $count );
			?>
				<thead>
					<tr>
						<th class="column-donor" scope="col"><?php esc_html_e( 'Donor', 'data-tables' ); ?></th>
						<th class="column-numeric column-min-amount" scope="col"><?php esc_html_e( 'Min Amount', 'data-tables' ); ?></th>
						<th class="column-donor-type" scope="col"><?php esc_html_e( 'Type', 'data-tables' ); ?></th>
					</tr>
				</thead>
				<tbody
                    data-found="<?php echo intval( $count ); ?>"
                    data-wp-bind--records-found='state.recordsFound'
                >
					<?php
					foreach ( $data as $row ) :
						$amount = $row['amount_calc'];
                        $attrs = get_label_and_class_disclosed( $row, $this->data->settings );
                        /**
                         * Check:
                         * - Has disclosed == 'no' - display $this->data->settings['unknown_amount']
                         */
						?>
						<tr data-think-tank="<?php echo esc_attr( $row['donor_slug'] ); ?>">
							<td class="column-donor" data-heading="<?php esc_attr_e( 'Donor', 'data-tables' ); ?>"><a href="<?php echo esc_url( $row['donor_link'] ); ?>"><?php echo esc_html( $row['donor'] ); ?></a></td>
							<td class="column-numeric column-min-amount" data-heading="<?php esc_attr_e( 'Min. Amount', 'data-tables' ); ?>">
                                <span class="<?php echo $attrs['class']; ?>" data-label="<?php echo $attrs['label']; ?>"><span><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?></span></span>
							</td>
							<td class="column-donor-type" data-heading="<?php esc_attr_e( 'Type', 'data-tables' ); ?>"><?php echo $row['donor_type']; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		else :
			?>
			<div class="no-data">
				<p><?php esc_html_e( 'No data found.', 'data-tables' ); ?></p>
			</div>
			<?php
		endif;

		return ob_get_clean();
	}

	/**
	 * Generate table for individual donor.
	 *
	 * @param string $donor The slug of the donor.
	 * @param string $donation_year The slug of the donation year.
	 * @param string $donor_type The slug of the donor type.
	 * @return string HTML table markup.
	 */
	public function generate_single_donor_table( string $donor = '', string $donation_year = '', string $donor_type = '' ): string {
		$data = $this->data->get_single_donor_data( $donor, $donation_year, $donor_type );

		ob_start();
		if ( $data ) :
            $count = count( $data );
			$this->render_table_top( 'single-donor', $donation_year, $count );
			?>
				<thead>
					<tr>
						<th class="column-think-tank" scope="col"><?php esc_html_e( 'Think Tank', 'data-tables' ); ?></th>
						<th class="column-donor" scope="col"><?php esc_html_e( 'Donor', 'data-tables' ); ?></th>
						<th class="column-numeric column-min-amount" scope="col"><?php esc_html_e( 'Min Amount', 'data-tables' ); ?></th>
						<th class="column-source" scope="col"><?php esc_html_e( 'Source', 'data-tables' ); ?></th>
					</tr>
				</thead>
				<tbody
                    data-found="<?php echo intval( $count ); ?>"
                    data-wp-bind--records-found='state.recordsFound'
                >
					<?php
					foreach ( $data as $row ) :
						$amount = $row['amount_calc'];
                        $attrs = get_label_and_class_disclosed( $row, $this->data->settings );
                        /**
                         * Check:
                         * - Has disclosed == 'no' - display $this->data->settings['unknown_amount']
                         */
						?>
                        <tr data-think-tank="<?php echo esc_attr( $row['think_tank_slug'] ); ?>">
							<td class="column-think-tank" data-heading="<?php esc_attr_e( 'Think Tank', 'data-tables' ); ?>"><a href="<?php echo get_term_link( $row['think_tank_slug'], 'think_tank' ); ?>"><?php echo esc_html( $row['think_tank'] ); ?></a></td>
							<td class="column-donor" data-heading="<?php esc_attr_e( 'Donor', 'data-tables' ); ?>"><?php echo esc_html( $row['donor'] ); ?></td>
							<td class="column-numeric column-min-amount" data-heading="<?php esc_attr_e( 'Min. Amount', 'data-tables' ); ?>">
                                <span class="<?php echo $attrs['class']; ?>" data-label="<?php echo $attrs['label']; ?>"><span><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?></span></span>
							</td>
							<td class="column-source" data-heading="<?php esc_attr_e( 'Source', 'data-tables' ); ?>"><?php echo ( $row['source'] ) ? sprintf( '<a href="%1$s" aria-label="%2$s" target="_blank"><span class="material-symbols-outlined" style="font-family:var(--wp--preset--font-family--icon);" role="img" aria-label="%2$s">link</span></a>', esc_url( $row['source'] ), esc_attr__( 'Link to source', 'data-tables' ) ) : ''; ?></td>
						</tr>
						
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		else :
			?>
			<div class="no-data">
				<p><?php esc_html_e( 'No data found.', 'data-tables' ); ?></p>
			</div>
			<?php
		endif;

		return ob_get_clean();
	}

	/**
	 * Render table for top ten
	 *
	 * @param  string  $donor_type
	 * @param  string  $donation_year
	 * @param  integer $number_of_items
	 * @return void
	 */
	public function render_top_ten_table( $donor_type = '', $donation_year = '', $number_of_items = 10 ): void {
		echo $this->generate_top_ten_table( $donor_type, $donation_year, $number_of_items );
	}


	/**
	 * Convert a transparency score to a star rating
	 *
	 * @param int $score The transparency score to convert.
	 * @return string HTML for the star rating.
	 */
	public function generate_star_rating( int $score ): string {
		$star_rating = '';

		for ( $i = 1; $i <= 5; $i++ ) {
			if ( $i <= $score ) {
				$star_rating .= '<span class="star filled">&#9733;</span>'; // filled star
			} else {
				$star_rating .= '<span class="star">&#9734;</span>'; // empty star
			}
		}

		return $star_rating;
	}

	/**
	 * Convert camelCase keys to snake_case keys.
	 *
	 * @param array $args The array with camelCase keys.
	 * @return array The array with keys converted to snake_case.
	 */
	private function convert_camel_to_snake_keys( array $args ): array {
		$converted_args = array();
		foreach ( $args as $key => $value ) {
			$new_key                    = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $key ) );
			$converted_args[ $new_key ] = $value;
		}
		return $converted_args;
	}
}
