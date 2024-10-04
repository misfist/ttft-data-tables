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

$unique_id = wp_unique_id( 'p-' );

$table_type           = sanitize_text_field( get_query_var( 'table_type', $attributes['tableType'] ?? '' ) );
$table_id             = Data_Handler::TABLE_ID . '-' . $table_type;
$selectedThinkTank    = sanitize_text_field( get_query_var( 'think_type', $attributes['thinkTank'] ?? '' ) );
$selectedDonor        = sanitize_text_field( get_query_var( 'donor', $attributes['donor'] ?? '' ) );
$selectedDonationYear = sanitize_text_field( get_query_var( 'donation_year', $attributes['donationYear'] ?? 'all' ) );
$selectedDonorType    = sanitize_text_field( get_query_var( 'donor_type', $attributes['donorType'] ?? 'all' ) );
$search_label         = ( strpos( $table_type, 'donor' ) !== false ) ? __( 'Filter by specific donor' ) : __( 'Filter by specific think tank' );
$settings             = get_option( 'site_settings' );
$rows_per_page        = $settings['rows_per_page'] ?? 50;
$search               = get_search_query();

$args = array(
	'tableId'     => $table_id,
	'tableType'   => $table_type,
	'searchLabel' => $search_label,
	'pageLength'  => $rows_per_page,
	'donor'       => $selectedDonor,
	'thinkTank'   => $selectedThinkTank,
	'search'      => $search,
	'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
	'action'      => 'do_get_data_table',
	'nonce'       => wp_create_nonce( "{$app_namespace}_nonce" ),
	'elementId'   => 'data-table-container',
	'tableData'   => '',
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

$block_wrapper_attributes = array(
	'data-wp-interactive'       => $app_namespace,
	'data-wp-run'               => 'callbacks.renderTable',
	'data-wp-run--animate'      => 'callbacks.loadAnimation',
	'data-wp-bind--table_type'  => 'state.tableType',
	'data-wp-bind--think_tank'  => 'state.thinkTank',
	'data-wp-bind--donor'       => 'state.donor',
	'data-wp-bind--year'        => 'state.donationYear',
	'data-wp-bind--type'        => 'state.donorType',
	'data-wp-init'              => 'actions.initTable',
	'data-wp-init-set-context'  => 'actions.setContext',
	'data-wp-init--initLog'     => 'callbacks.initLog',
	'data-wp-watch--log'        => 'callbacks.initLog',
	'data-wp-class--is-loading' => 'state.isLoading,',
	'data-wp-class--is-loaded'  => '!state.isLoading',
);

ob_start();
?>

<div
	<?php echo get_block_wrapper_attributes( $block_wrapper_attributes ); ?>
	<?php echo wp_interactivity_data_wp_context( $context ); ?>
>
	<div data-wp-bind--id="state.elementId">
		<?php echo generate_data_table( $table_type, $args ); ?>
	</div>

</div>

<?php
$output = ob_get_clean();
echo wp_interactivity_process_directives( $output );
