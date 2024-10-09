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
$unique_id     = wp_unique_id( 'p-' );
$taxonomy      = 'donation_year';
$state_key     = 'donationYear';

$selected  = sanitize_text_field( get_query_var( $taxonomy, 'all' ) );
$terms     = get_terms(
	array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => false,
		'order'      => 'DESC',
	)
);
$term_list = wp_list_pluck( $terms, 'name', 'slug' );
$all       = array( 'all' => sprintf( __( 'All <span class="mobile-only">%s</span>', 'ttft-data-tables' ), __( 'Years', 'ttft-data-tables' ) ) );
$options   = $all + $term_list;

wp_interactivity_state(
	$app_namespace,
	array(
		$state_key => $selected ?? 'all',
	),
);

$context = array(
	$state_key => $selected ?? 'all',
	'options'  => array_map( function( $value ) { return wp_strip_all_tags( $value ); }, $options ),
);

ob_start();
?>

<div
	<?php echo get_block_wrapper_attributes( array( 'class' => 'data-filter data-filter--donation-year' ) ); ?>
	data-wp-interactive="<?php echo $app_namespace; ?>"
	<?php echo wp_interactivity_data_wp_context( $context ); ?>
	data-wp-watch="callbacks.logYear"
	data-wp-bind--year='state.donationYear'
	tab-index="1"
>

	<?php
	$input_type = 'radio';
	$input_name = 'year-filter';
	$selected   = $context[ $state_key ];
	$options    = $options;
	foreach ( $options as $value => $label ) :
		$input_id    = "{$input_name}-{$value}";
		$input_attrs = array(
			'id'                      => $input_id,
			'name'                    => $input_name,
			'type'                    => $input_type,
			'value'                   => $value,
			'data-state-key'          => $state_key,
			'data-wp-href'            => add_query_arg( $taxonomy, $value ),
			'data-wp-on-async--click' => 'actions.updateYear',
		);
		if ( (int) $selected === (int) $value ) {
			$input_attrs['checked'] = true;
		}

		$normalized_input_attrs = array();
		foreach ( $input_attrs as $attr_key => $attr_value ) {
			$normalized_input_attrs[] = $attr_key . '="' . esc_attr( $attr_value ) . '"';
		}
		$normalized_input_attrs = implode( ' ', $normalized_input_attrs );
		?>

			<input <?php echo $normalized_input_attrs; ?>>
			<label 
				for="<?php echo $input_id; ?>" 
				class="option" 
				aria-label="<?php printf( esc_attr( 'Filter by %s', 'ttft-data-tables' ), esc_attr( wp_strip_all_tags( $label ) ) ); ?>"
			><?php echo $label; ?></label>

		<?php
	endforeach;
	?>
	
</div>

<?php
$output = ob_get_clean();
echo wp_interactivity_process_directives( $output );
