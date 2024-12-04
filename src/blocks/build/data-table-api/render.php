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

namespace Ttft\Data_Tables\Blocks;

use Ttft\Data_Tables\Data as Data;
use Ttft\Data_Tables\Render as Render;
use Ttft\Data_Tables\API\API as API;
$data = new Data();
$render = new Render();

global $wp_query;

$app_namespace = TTFT_APP_NAMESPACE;

$unique_id = wp_unique_id( 'p-' );

$table_type           = sanitize_text_field( get_query_var( 'table_type', $attributes['tableType'] ?? '' ) );
$table_id             = Data::TABLE_ID . '-' . $table_type;
$selectedThinkTank    = sanitize_text_field( get_query_var( 'think_type', $attributes['thinkTank'] ?? '' ) );
$selectedDonor        = sanitize_text_field( get_query_var( 'donor', $attributes['donor'] ?? '' ) );
$selectedDonationYear = sanitize_text_field( get_query_var( 'donation_year', $attributes['donationYear'] ?? 'all' ) );
$selectedDonorType    = sanitize_text_field( get_query_var( 'donor_type', $attributes['donorType'] ?? 'all' ) );
$search_label         = ( strpos( $table_type, 'donor' ) !== false ) ? __( 'Filter by specific donor' ) : __( 'Filter by specific think tank' );
$settings             = get_option( 'site_settings' );
$rows_per_page        = $settings['rows_per_page'] ?? 50;

$args = array(
	'tableId'     => $table_id,
	'tableType'   => $table_type,
	'searchLabel' => $search_label,
	'pageLength'  => $rows_per_page,
	'donor'       => $selectedDonor,
	'thinkTank'   => $selectedThinkTank,
	'search'      => $search,
	'ajaxUrl'     => admin_url( 'admin-ajax.php' ),
	'restUrl'     => rest_url( 'data-tables/v1/table-data?' ),
	'action'      => 'do_get_data_table',
	'nonce'       => wp_create_nonce( "{$app_namespace}_nonce" ),
	'elementId'   => 'data-table-container',
);

if ( is_tax( 'donation_year' ) ) {
	$args['donationYear'] = get_queried_object()->slug;
} elseif ( is_tax( 'donor_type' ) ) {
	$args['donorType'] = get_queried_object()->slug;
} elseif ( is_search() ) {
	$args['search'] = sanitize_text_field( get_search_query() );
}

$data = $data->get_data( $args );

$args['columns'] = $data['columns'];
$args['data']    = $data['data'];

wp_interactivity_state(
	$app_namespace,
	$args,
);

$context = array(
	'isLoaded' => true,
);

$block_wrapper_attributes = array(
	'data-wp-interactive'       => $app_namespace,
	'data-wp-init'              => 'actions.init',
	'data-watch'                => 'actions.update',
	'data-wp-bind--table_type'  => 'state.tableType',
	'data-wp-bind--think_tank'  => 'state.thinkTank',
	'data-wp-bind--donor'       => 'state.donor',
	'data-wp-bind--year'        => 'state.donationYear',
	'data-wp-bind--type'        => 'state.donorType',
	'data-wp-class--is-loading' => 'state.isLoading,',
	'data-wp-class--is-loaded'  => '!state.isLoading',
	'class'                     => 'data-table display',
);

ob_start();
?>

<div
	<?php echo get_block_wrapper_attributes( $block_wrapper_attributes ); ?>
	<?php echo wp_interactivity_data_wp_context( $context ); ?>
>

	<div data-wp-bind--id="state.elementId">
		<?php echo $render->generate_data_table( $table_type, $args ); ?>
	</div>

</div>

<?php
$output = ob_get_clean();
echo wp_interactivity_process_directives( $output );
