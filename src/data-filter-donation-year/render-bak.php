<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
namespace TTFT\Data_Tables\Blocks;

$app_namespace = \TTFT\Data_Tables\APP_NAMESPACE;
$taxonomy      = 'donation_year';

$terms = get_terms(
	array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
	)
);

$donation_year_var = sanitize_text_field( get_query_var( 'donation_year', 'all' ) );

$list          = wp_list_pluck( $terms, 'name', 'slug' );
$all           = array( 'all' => __( 'All', 'data-tables' ) );
$input_options = $all + $list;
// $input_options = array_merge( $all, $list );

$input_type = 'radio';

$selected   = array_key_exists( 'defaultChecked', $attributes ) ? $attributes['defaultChecked'] : $checked_var;
$input_name = 'donationYear';
$input_type = 'radio';

$context = array(
	'donationYears' => $input_options,
	'donationYear'  => $selected, /* Currently Selected */
	'selectedYear'  => $selected, /* Initial selected value */
);

wp_interactivity_state(
	$app_namespace,
	array(
		'donationYears' => $input_options,
		'donationYear'  => $selected, /* Context value */
		'selectedYear'  => $selected, /* Initial selected value */
	)
);

// Generate unique id for aria-controls.
$unique_id = wp_unique_id( 'p-' );

ob_start();

?>

<div
	data-wp-interactive="<?php echo $app_namespace; ?>"
	<?php echo get_block_wrapper_attributes(); ?>
	<?php echo wp_interactivity_data_wp_context( $context ); ?>
	data-wp-watch="callbacks.log"
>
	<?php


	foreach ( $input_options as $value => $label ) :
		$input_id    = "{$input_type}-{$value}";
		$input_attrs = array(
			'id'                     => $input_id,
			'name'                   => $input_name,
			'type'                   => $input_type,
			'value'                  => $value,
			'data-wp-href'           => '',
			'data-key'               => 'selectedYear',
			// 'data-wp-bind--value'   => $value,
			// 'data-wp-bind--checked' => "state.selectedYear === donationYear",
			'data-wp-action--click'  => 'actions.selectYear',
			'data-wp-action--change' => 'actions.selectYear',
		);

		// Normalize input attributes as a html string.
		$normalized_input_attrs = array();
		foreach ( $input_attrs as $attr_key => $attr_value ) {
			$normalized_input_attrs[] = $attr_key . '="' . esc_attr( $attr_value ) . '"';
		}
		$normalized_input_attrs = implode( ' ', $normalized_input_attrs );
		?>

			<input <?php echo $normalized_input_attrs; ?>>
			<label for="<?php echo $input_id; ?>"><?php echo esc_html( $label ); ?></label>

		<?php
	endforeach;
	?>

	<div data-wp-text="context.selectedYear"></div>
	<div data-wp-text="state.selectedYear"></div>

</div>

<?php
$output = ob_get_clean();
echo wp_interactivity_process_directives( $output );
