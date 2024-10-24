<?php
/**
 * Shortcode Functions
 */
namespace Ttft\Data_Tables\Data;

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
function think_tank_archive_table_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'year' => '',
		),
		$atts,
		'think_tank_archive_table'
	);

	return shortcode_unautop(
		generate_think_tank_archive_table(
			sanitize_text_field( $atts['year'] )
		)
	);
}
add_shortcode( 'think_tank_archive_table', __NAMESPACE__ . '\think_tank_archive_table_shortcode' );

/**
 * Shortcode to display the individual think tank table.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML table markup.
 */
function single_think_tank_table_shortcode( $atts ): string {
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

	$output = generate_single_think_tank_table(
		sanitize_text_field( $atts['think_tank'] ),
		sanitize_text_field( $atts['year'] ),
		sanitize_text_field( $atts['type'] )
	);

	add_filter( 'the_content', 'wpautop' );
	add_filter( 'the_content', 'wptexturize' );

	return shortcode_unautop( trim( $output ) );
}
add_shortcode( 'think_tank_table', __NAMESPACE__ . '\single_think_tank_table_shortcode' );

/**
 * Shortcode to display the individual think tank table.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML table markup.
 */
function single_donor_table_shortcode( $atts ): string {
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
		generate_single_donor_table(
			sanitize_text_field( $atts['donor'] ),
			sanitize_text_field( $atts['year'] ),
			sanitize_text_field( $atts['type'] )
		)
	);
}
add_shortcode( 'donor_table', __NAMESPACE__ . '\single_donor_table_shortcode' );

/**
 * Shortcode to display donors table.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML table markup.
 */
function donor_archive_table_shortcode( $atts ): string {
	$atts = shortcode_atts(
		array(
			'year' => '',
			'type' => '',
		),
		$atts,
		'donor_archive_table'
	);

	return shortcode_unautop(
		generate_donor_archive_table(
			sanitize_text_field( $atts['year'] ),
			sanitize_text_field( $atts['type'] )
		)
	);
}
add_shortcode( 'donor_archive_table', __NAMESPACE__ . '\donor_archive_table_shortcode' );

/**
 * Shortcode handler for generating various data tables.
 *
 * @param array $atts The shortcode attributes.
 * @return string The generated table output or an error message.
 */
function data_table( $atts ): string {
    $atts = shortcode_atts(
        array(
            'table_type' => '',
            'think_tank' => '',
            'donor'      => '',
            'type'       => '',
            'year'       => '',
        ),
        $atts,
        'ttft_data_table'
    );

    if ( empty( $atts['table_type'] ) ) {
        return __( 'Table Type attribute is required.', 'data-tables' );
    }

    $output = generate_data_table( $atts['table_type'], $atts );

    return $output;
}
add_shortcode( 'ttft_data_table', __NAMESPACE__ . '\data_table' );
