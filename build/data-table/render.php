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
$id            = wp_unique_id( 'ttft-data-table-' );
$taxonomy      = 'donation_year';
$selected      = sanitize_text_field( get_query_var( $taxonomy, 'all' ) );
$table_type    = ( isset( $attributes['tableType'] ) ) ? esc_attr( $attributes['tableType'] ) : 'think-tank-archive';
$search_label  = ( isset( $attributes['searchLabel'] ) ) ? esc_attr( $attributes['searchLabel'] ) : esc_attr( 'Filter by specific think tank', 'data-tables' );

$context = array(
	'tableId'     => $id,
	'tableType'   => $table_type,
	'searchLabel' => $search_label,
);

wp_interactivity_state(
	$app_namespace,
	array(
		'tableId'  => $id,
	),
);

ob_start();
?>

<div
	<?php echo get_block_wrapper_attributes(); ?>
	data-wp-interactive="<?php echo $app_namespace; ?>"
	<?php echo wp_interactivity_data_wp_context( $context ); ?>
	data-wp-interactive-value="#funding-data"
	data-wp-watch="callbacks.log"
	data-wp-bind--donor='context.donorType'
	data-wp-bind--year='context.donationYear'
>	

	<h3>State</h3>
	<div data-wp-text="state.donationYear"></div>
	<div data-wp-text="state.donorType"></div>

	<table 
		id="funding-data" 
		class="<?php echo $table_type; ?> dataTable" 
		data-search-label="Filter by specific think tank"
		data-wp-bind--data-table-type='context.tableType'
		data-wp-bind--data-search-label='context.searchLabel'
	>
		<caption><span data-wp-text="state.donationYear">2022</span> donations received from…</caption>
		<thead>
			<tr role="row">
				<th class="column-think-tank">Think Tank</th>
				<th class="column-numeric column-min-amount">Foreign Government</th>
				<th class="column-numeric column-min-amount">Pentagon Contractor</th>
				<th class="column-numeric column-min-amount">U.S. Government</th>
				<th class="column-numeric column-transparency-score">Score</th>
			</tr>
		</thead>
		<tbody data-wp-bind--data-body='context.tableId'>
		</tbody>
	</table>

</div>

<?php
$output = ob_get_clean();
echo wp_interactivity_process_directives( $output );
