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

use TTFT\Data_Tables\Data_Handler as Data_Handler;
use function TTFT\Data_Tables\Data\generate_data_table;

global $wp_query;

$app_namespace = Data_Handler::APP_NAMESPACE;
$table_id      = Data_Handler::TABLE_ID;

$unique_id = wp_unique_id( 'p-' );

$table_type           = sanitize_text_field( get_query_var( 'table_type', $attributes['tableType'] ) );
$selectedThinkTank    = sanitize_text_field( get_query_var( 'think_type', $attributes['thinkTank'] ) );
$selectedDonor        = sanitize_text_field( get_query_var( 'donor', $attributes['donor'] ) );
$selectedDonationYear = sanitize_text_field( get_query_var( 'donation_year', $attributes['donationYear'] ) ) ?? 'all';
$selectedDonorType    = sanitize_text_field( get_query_var( 'donor_type', $attributes['donorType'] ) ) ?? 'all';
$search_label = ( strpos( $table_type, 'donor' ) !== false ) ? __( 'Filter by specific donor' ) : __( 'Filter by specific think tank' );

$args = array(
	'tableId'      => $table_id,
	'tableType'    => $table_type,
	'searchLabel'  => $search_label,
	'donor'        => $selectedDonor,
	'thinkTank'    => $selectedThinkTank,
	'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
	'action'       => 'do_get_data_table',
	'nonce'        => wp_create_nonce( "{$app_namespace}_nonce" ),
	'elementId'    => 'data-table-container',
	'tableData'    => '',
);

if ( is_tax( 'donation_year' ) ) {
	$args['donationYear'] = get_queried_object()->slug;
} elseif ( is_tax( 'donor_type' ) ) {
	$args['donorType'] = get_queried_object()->slug;
}

wp_interactivity_state(
	$app_namespace,
	$args
);

$context = array(
	'isLoaded' => true,
);

ob_start();
?>

<div
	<?php echo get_block_wrapper_attributes(); ?>
	data-wp-interactive="<?php echo $app_namespace; ?>"
	<?php echo wp_interactivity_data_wp_context( $context ); ?>
	data-wp-run="callbacks.renderTable"
	data-wp-run--animate="callbacks.loadAnimation"
	data-wp-bind--table_type='state.tableType'
	data-wp-bind--think_tank='state.thinkTank'
	data-wp-bind--donor='state.donor'
	data-wp-bind--year='state.donationYear'
	data-wp-bind--type='state.donorType'
	data-wp-init="actions.initTable"
	data-wp-init--initLog="actions.initLog"
	data-wp-class--is-loading="state.isLoading"
	data-wp-class--is-loaded="context.isLoaded"
	data-wp-watch--log-loading="callbacks.logLoading"
	data-wp-watch--log-table="callbacks.logTable"
	data-wp-run="callbacks.loadAnimation"
>

	<div data-wp-bind--id="state.elementId">
		<?php echo generate_data_table( $table_type, $args ); ?>
	</div>

</div>

<?php
$output = ob_get_clean();
echo wp_interactivity_process_directives( $output );
