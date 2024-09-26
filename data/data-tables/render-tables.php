<?php
/**
 * Render Tables Functions
 */
namespace TTFT\Data_Tables;

/**
 * Generate table for top ten
 *
 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
 * @param int    $number_of_items Optional. The number of items to return. Default 10.
 * @return string HTML table markup.
 */
function generate_top_ten_table( $donor_type = '', $donation_year = '', $number_of_items = 10 ): string {
	$data = get_top_ten_data( $donor_type, $donation_year, $number_of_items );

	ob_start();
	if ( $data ) :
		?>

		<table id="table-<?php echo sanitize_title( $donor_type ); ?>" class="top-ten-recipients dataTable" data-total-rows="<?php echo intval( count( $data ) ); ?>">
			<thead>
				<tr>
					<th class="column-think-tank"><?php esc_html_e( 'Think Tank', 'ttt' ); ?></th>
					<th class="column-min-amount column-numeric"><?php esc_html_e( 'Min Amount', 'ttt' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $data as $row ) : ?>
					<tr>
						<td class="column-think-tank" data-heading="<?php esc_attr_e( 'Think Tank', 'ttt' ); ?>">
							<a href="<?php echo esc_url( get_term_link( $row['think_tank'], 'think_tank' ) ); ?>"><?php echo esc_html( $row['think_tank'] ); ?></a>
						</td>
						<td class="column-min-amount column-numeric" data-heading="<?php esc_attr_e( 'Min Amount', 'ttt' ); ?>"><?php echo number_format( $row['total_amount'], 0 ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<?php
	endif;

	return ob_get_clean();
}

/**
 * Generate table for think tanks
 *
 * @param  string $donation_year
 * @return string HTML table markup.
 */
function generate_think_tanks_table( $donation_year = '' ): string {
	$year_var      = $_GET['donation_year'];
	$donation_year = $year_var ? $year_var : $donation_year;
	$donation_year = sanitize_text_field( $donation_year );

	$data = get_think_tanks_data( $donation_year );

	ob_start();
	if ( $data ) :
		?>
		<div 
			data-wp-interactive="thinkTanksArchiveTable" 
			data-wp-interactive-action="setTableElement" 
			data-wp-interactive-value="#<?php echo TABLE_ID; ?>"
		>
			<table 
				id="<?php echo TABLE_ID; ?>" 
				class="think-tank-archive dataTable" 
				data-total-rows="<?php echo intval( count( $data ) ); ?>" 
				data-search-label="<?php esc_attr_e( 'Filter by specific think tank', 'ttt' ); ?>"
				data-wp-interactive-action="updateTable"
				data-table-type="archive-think-tank"
				data-wp-context='{"table-type":"thinkTankArchive","donationYear":"<?php echo $donation_year; ?>"}'
			>
				<?php
				if ( $donation_year ) :
					?>
					<caption><?php printf( 'Donations in <span class="donation-year">%s</span> received from…', $donation_year ); ?></caption>
					<?php
				endif;
				?>
				<thead>
					<tr>
						<th class="column-think-tank"><?php esc_html_e( 'Think Tank', 'ttt' ); ?></th>
						<?php if ( ! empty( $data ) ) : ?>
							<?php
							$first_entry = reset( $data );
							foreach ( $first_entry['donor_types'] as $donor_type => $amount ) :
								?>
								<th class="column-numeric column-min-amount"><?php echo esc_html( $donor_type ); ?></th>
							<?php endforeach; ?>
						<?php endif; ?>
						<th class="column-numeric column-transparency-score"><?php esc_html_e( 'Score', 'ttt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $data as $think_tank_slug => $data ) : ?>
						<tr data-think-tank="<?php echo esc_attr( $think_tank_slug ); ?>">
							<td class="column-think-tank" data-heading="<?php esc_attr_e( 'Think Tank', 'ttt' ); ?>"><a href="<?php echo esc_url( get_term_link( $think_tank_slug, 'think_tank' ) ); ?>"><?php echo esc_html( $data['think_tank'] ); ?></a></td>
							<?php foreach ( $data['donor_types'] as $donor_type => $amount ) : ?>
								<td class="column-numeric column-min-amount" data-heading="<?php echo esc_attr( $donor_type ); ?>"><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?></td>
							<?php endforeach; ?>
							<td class="column-numeric column-transparency-score" data-heading="<?php esc_attr_e( 'Transparency Score', 'ttt' ); ?>"><?php echo esc_html( $data['transparency_score'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	endif;

	$output = ob_get_clean();

	return $output;
}

/**
 * Generate table for individual think tank
 *
 * @param string $think_tank    Optional. Slug of the think tank.
 * @param string $donation_year Optional. Slug of the donation year.
 * @param string $donor_type    Optional. Slug of the donor type.
 */
function generate_think_tank_donor_table( $think_tank = '', $donation_year = '', $donor_type = '' ): string {
	$queried_obj   = get_queried_object();
	$think_tank    = ( $think_tank ) ? sanitize_text_field( $think_tank ) : $queried_obj->post_name;
	$year_var      = $_GET['donation_year'];
	$donor_var     = $_GET['donor_type'];
	$donation_year = $year_var ? $year_var : $donation_year;
	$donation_year = sanitize_text_field( $donation_year );
	$donor_type    = $donor_var ? $donor_var : $donor_type;
	$donor_type    = sanitize_text_field( $donor_type );

	$data = get_think_tank_donor_data( $think_tank, $donation_year, $donor_type );

	ob_start();
	if ( $data ) :
		?>
		<div 
			data-wp-interactive="thinkTankTable" 
			data-wp-interactive-action="setTableElement" 
			data-wp-interactive-value="#<?php echo TABLE_ID; ?>"
		>
			<table 
				id="<?php echo TABLE_ID; ?>" 
				class="think-tank dataTable" 
				data-total-rows="<?php echo intval( count( $data ) ); ?>" 
				data-think-tank="<?php echo $think_tank; ?>" 
				data-search-label="<?php esc_attr_e( 'Filter by specific donor', 'ttt' ); ?>"
				data-wp-interactive-action="updateTable"
				data-table-type="single-think-tank"
				data-wp-context='{"table-type":"singleThinkTank","thinkTank":"<?php echo $think_tank; ?>","donationYear":"<?php echo $donation_year; ?>","donorType":"<?php echo $donor_type; ?>"}'
			>
				<?php
				if ( $donation_year ) :
					?>
					<caption><?php esc_html_e( 'Donations received in', 'ttt' ); ?> <span class="donation-year" data-wp-text="context.donationYear"></span></caption>
					<?php
				endif;
				?>
				<thead>
					<tr>
						<th class="column-donor"><?php esc_html_e( 'Donor', 'ttt' ); ?></th>
						<th class="column-numeric column-min-amount"><?php esc_html_e( 'Min Amount', 'ttt' ); ?></th>
						<th class="column-source"><?php esc_html_e( 'Source', 'ttt' ); ?></th>
						<th class="column-type"><?php esc_html_e( 'Type', 'ttt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data as $key => $row ) :
						$amount           = $row['amount_calc'];
						$formatted_source = sprintf( '<a href="%1$s" class="source-link" target="_blank"><span class="screen-reader-text">%1$s</span><span class="icon material-symbols-outlined" aria-hidden="true">link</span></a>', esc_url( $row['source'] ) );
						?>
						<tr data-think-tank="<?php echo esc_attr( $row['donor_slug'] ); ?>">
							<td class="column-donor" data-heading="<?php esc_attr_e( 'Donor', 'ttt' ); ?>"><a href="<?php echo esc_url( $row['donor_link'] ); ?>"><?php echo esc_html( $row['donor'] ); ?></a></td>
							<td class="column-numeric column-min-amount" data-heading="<?php esc_attr_e( 'Min Amount', 'ttt' ); ?>"><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?>
							<td class="column-source" data-heading="<?php esc_attr_e( 'Source', 'ttt' ); ?>"><?php echo ( $row['source'] ) ? $formatted_source : ''; ?></td>
							<td class="column-donor-type" data-heading="<?php esc_attr_e( 'Type', 'ttt' ); ?>"><?php echo $row['donor_type']; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	endif;

	$output = ob_get_clean();

	return $output;
}

/**
 * Generate table for individual donor
 *
 * @param string $donor    Optional. Slug of the donor.
 * @param string $donation_year Optional. Slug of the donation year.
 * @param string $donor_type    Optional. Slug of the donor type.
 */
function generate_donor_think_tank_table( $donor = '', $donation_year = '', $donor_type = '' ): string {
	$donor         = sanitize_text_field( $donor );
	$donation_year = sanitize_text_field( $donation_year );
	$donor_type    = sanitize_text_field( $donor_type );

	$data = get_donor_think_tank_data( $donor, $donation_year, $donor_type );

	ob_start();
	if ( $data ) :
		?>
		<div 
			data-wp-interactive="donorTable" 
			data-wp-interactive-action="setTableElement" 
			data-wp-interactive-value="#<?php echo TABLE_ID; ?>"
		>
			<table 
				id="<?php echo TABLE_ID; ?>" 
				class="donor dataTable" 
				data-total-rows="<?php echo intval( count( $data ) ); ?>" 
				data-donor="<?php echo sanitize_text_field( $donor ); ?>" 
				data-search-label="<?php esc_attr_e( 'Filter by specific think tank', 'ttt' ); ?>"
				data-wp-interactive-action="updateTable"
				data-table-type="single-donor"
				data-wp-context='{"table-type":"singleDonor","donor":"<?php echo $donor; ?>","donationYear":"<?php echo $donation_yar; ?>","donorType":"<?php echo $donor_type; ?>"}'
			>
				<?php
				if ( $donation_year ) :
					?>
					<caption data-wp-text="context.donationYear"><?php printf( 'Donations given in <span class="donation-year">%s</span>…', intval( $donation_year ) ); ?></caption>
					<?php
				endif;
				?>
				<thead>
					<tr>
						<th class="column-think-tank"><?php esc_html_e( 'Think Tank', 'ttt' ); ?></th>
						<th class="column-donor"><?php esc_html_e( 'Donor', 'ttt' ); ?></th>
						<th class="column-numeric column-min-amount"><?php esc_html_e( 'Min Amount', 'ttt' ); ?></th>
						<th class="column-source"><?php esc_html_e( 'Source', 'ttt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data as $key => $row ) :
						$amount           = $row['amount_calc'];
						$formatted_source = sprintf( '<a href="%1$s" class="source-link" target="_blank"><span class="screen-reader-text">%1$s</span><span class="icon material-symbols-outlined" aria-hidden="true">link</span></a>', esc_url( $row['source'] ) );
						?>
						<tr data-think-tank="<?php echo esc_attr( $row['think_tank_slug'] ); ?>">
							<td class="column-think-tank" data-heading="<?php esc_attr_e( 'Think Tank', 'ttt' ); ?>"><a href="<?php echo esc_url( get_term_link( $row['think_tank_slug'], 'think_tank' ) ); ?>"><?php echo esc_html( $row['think_tank'] ); ?></a></td>
							<td class="column-donor" data-heading="<?php esc_attr_e( 'Donor', 'ttt' ); ?>"><?php echo esc_html( $row['donor'] ); ?></td>
							<td class="column-numeric column-min-amount" data-heading="<?php esc_attr_e( 'Min Amount', 'ttt' ); ?>"><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?>
							<td class="column-source" data-heading="<?php esc_attr_e( 'Source', 'ttt' ); ?>"><?php echo ( $row['source'] ) ? $formatted_source : ''; ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	endif;

	$output = ob_get_clean();

	return $output;
}

/**
 * Generate table for donors
 *
 * @param  string $donation_year
 * @return string HTML table markup.
 */
function generate_donors_table( $donation_year = '', $donor_type = '' ): string {
	$donation_year = sanitize_text_field( $donation_year );
	$donor_type    = sanitize_text_field( $donor_type );

	$data = get_donors_data( $donation_year, $donor_type );

	ob_start();
	if ( $data ) :
		?>
		<div 
			data-wp-interactive="donorsArchiveTable" 
			data-wp-interactive-action="setTableElement" 
			data-wp-interactive-value="#<?php echo TABLE_ID; ?>"
		>
			<table 
				id="<?php echo TABLE_ID; ?>" 
				class="donor-archive dataTable" 
				data-total-rows="<?php echo intval( count( $data ) ); ?>" 
				data-search-label="<?php esc_attr_e( 'Filter by specific donor', 'ttt' ); ?>"
				data-wp-interactive-action="updateTable"
				data-table-type="archive-donor"
				data-wp-context='{"tableType":"donorArchive","donationYear":"<?php echo $donation_year; ?>","donorType":"<?php echo $donor_type; ?>"}'
			>
				<?php
				if ( $donation_year ) :
					?>
					<caption data-wp-text="context.donationYear"><?php printf( 'Donations given in <span class="donation-year">%s</span>…', $donation_year ); ?></caption>
					<?php
				endif;
				?>
				<thead>
					<tr>
						<th class="column-donor"><?php esc_html_e( 'Donor', 'ttt' ); ?></th>
						<th class="column-numeric column-min-amount"><?php esc_html_e( 'Min Amount', 'ttt' ); ?></th>
						<th class="column-type"><?php esc_html_e( 'Type', 'ttt' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $data as $key => $row ) :
						$amount = $row['amount_calc'];
						?>
						<tr data-think-tank="<?php echo esc_attr( $row['donor_slug'] ); ?>">
							<td class="column-donor" data-heading="<?php esc_attr_e( 'Donor', 'ttt' ); ?>"><a href="<?php echo esc_url( $row['donor_link'] ); ?>"><?php echo esc_html( $row['donor'] ); ?></a></td>
							<td class="column-numeric column-min-amount" data-heading="<?php esc_attr_e( 'Min Amount', 'ttt' ); ?>"><?php echo esc_html( number_format( $amount, 0, '.', ',' ) ); ?>
							<td class="column-donor-type" data-heading="<?php esc_attr_e( 'Type', 'ttt' ); ?>"><?php echo $row['donor_type']; ?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	endif;

	$output = ob_get_clean();

	return $output;
}

/**
 * Render table for top ten
 *
 * @param  string  $donor_type
 * @param  string  $donation_year
 * @param  integer $number_of_items
 * @return void
 */
function render_top_ten_table( $donor_type = '', $donation_year = '', $number_of_items = 10 ): void {
	echo generate_top_ten_table( $donor_type, $donation_year, $number_of_items );
}

/**
 * Render table for think tanks
 *
 * @param  string  $donor_type
 * @param  string  $donation_year
 * @param  integer $number_of_items
 * @return void
 */
function render_think_tanks_table( $donation_year = '' ): void {
	echo generate_think_tanks_table( $donation_year );
}

/**
 * Render table for individual think tank
 *
 * @param string $think_tank    Optional. Slug of the think tank.
 * @param string $donation_year Optional. Slug of the donation year.
 * @param string $donor_type    Optional. Slug of the donor type.
 * @return void
 */
function render_think_tank_donor_table( $think_tank = '', $donation_year = '', $donor_type = '' ): void {
	echo generate_think_tank_donor_table( $think_tank, $donation_year, $donor_type );
}

/**
 * Render table for individual donor
 *
 * @param string $donor    Optional. Slug of the donor.
 * @param string $donation_year Optional. Slug of the donation year.
 * @param string $donor_type    Optional. Slug of the donor type.
 * @return void
 */
function render_donor_think_tank_table( $donor = '', $donation_year = '', $donor_type = '' ): void {
	echo generate_donor_think_tank_table( $donor, $donation_year, $donor_type );
}

/**
 * Render table for donors
 *
 * @param  string $donation_year
 * @return void
 */
function render_donors_table( $donation_year = '' ) {
	echo generate_donors_table( $donation_year );
}

/**
 * Shortcode to display the top ten table.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML table markup.
 */
function top_ten_table_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'type'  => '',
			'year'  => '',
			'limit' => 10,
		),
		$atts,
		'top_ten_table'
	);

	return shortcode_unautop(
		generate_top_ten_table(
			sanitize_text_field( $atts['type'] ),
			sanitize_text_field( $atts['year'] ),
			intval( $atts['limit'] )
		)
	);
}
add_shortcode( 'top_ten_table', __NAMESPACE__ . '\top_ten_table_shortcode' );

/**
 * Shortcode to display think tanks table.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML table markup.
 */
function think_tanks_table_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'year' => '',
		),
		$atts,
		'think_tanks_table'
	);

	return shortcode_unautop(
		generate_think_tanks_table(
			sanitize_text_field( $atts['year'] )
		)
	);
}
add_shortcode( 'think_tanks_table', __NAMESPACE__ . '\think_tanks_table_shortcode' );

/**
 * Shortcode to display the individual think tank table.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML table markup.
 */
function think_tank_donor_table_shortcode( $atts ): string {
	remove_filter( 'the_content', 'wpautop' );
	remove_filter( 'the_content', 'wptexturize' );

	$atts = shortcode_atts(
		array(
			'think_tank' => '',
			'year'       => '',
			'type'       => '',
		),
		$atts,
		'think_tank_table'
	);

	$output = generate_think_tank_donor_table(
		sanitize_text_field( $atts['think_tank'] ),
		sanitize_text_field( $atts['year'] ),
		sanitize_text_field( $atts['type'] )
	);

	add_filter( 'the_content', 'wpautop' );
	add_filter( 'the_content', 'wptexturize' );

	return shortcode_unautop( trim( $output ) );
}
add_shortcode( 'think_tank_table', __NAMESPACE__ . '\think_tank_donor_table_shortcode' );

/**
 * Shortcode to display the individual think tank table.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML table markup.
 */
function donor_think_tanks_table_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'donor' => '',
			'year'  => '',
			'type'  => '',
		),
		$atts,
		'donor_table'
	);

	return shortcode_unautop(
		generate_donor_think_tank_table(
			sanitize_text_field( $atts['donor'] ),
			sanitize_text_field( $atts['year'] ),
			sanitize_text_field( $atts['type'] )
		)
	);
}
add_shortcode( 'donor_table', __NAMESPACE__ . '\donor_think_tanks_table_shortcode' );

/**
 * Shortcode to display donors table.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML table markup.
 */
function donors_table_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'year' => '',
			'type' => '',
		),
		$atts,
		'donors_table'
	);

	return shortcode_unautop(
		generate_donors_table(
			sanitize_text_field( $atts['year'] ),
			sanitize_text_field( $atts['type'] )
		)
	);
}
add_shortcode( 'donors_table', __NAMESPACE__ . '\donors_table_shortcode' );
