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

$app_namespace = TTFT_APP_NAMESPACE;
$state_key     = 'entityType';

$entity_type = get_query_var( 'entity_type', sanitize_text_field( $_GET['entity_type'] ?? 'think_tank' ) );
if ( ! $entity_type ) {
	$entity_type = 'think_tank';

}

wp_interactivity_state(
	$app_namespace,
	array(
		$state_key          => $entity_type,
		'thinkTankSelected' => 'think_tank' === $entity_type,
		'donorSelected'     => 'donor' === $entity_type,
		'entityType'        => $entity_type,
	),
);

$context = array(
	$state_key          => $entity_type,
	'thinkTankSelected' => 'think_tank' === $entity_type || ! $entity_type,
	'donorSelected'     => 'donor' === $entity_type,
);

$block_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'                             => 'transaction-results-tabs',
		'data-wp-interactive'               => $app_namespace,
		'data-wp-bind--type'                => 'state.entityType',
		'data-wp-bind--think-tank-selected' => 'think_tank' === $entity_type || ! $entity_type,
		'data-wp-bind--donor-selected'      => 'donor' === $entity_type,
	)
);

ob_start();
?>

<div 
<?php echo $block_wrapper_attributes; ?>
<?php echo wp_interactivity_data_wp_context( $context ); ?>
>
	<ul class="wp-block-list tab-links">
		<li
			class="tab-link active"
			data-wp-on--click="actions.toggleThinkTank"
			data-wp-class--active="state.thinkTankSelected"
			data-entity-type="think_tank"
		 >
			<a href="<?php echo add_query_arg( 'entity_type', 'think_tank' ); ?>" data-entity-type="think_tank"><?php esc_html_e( 'Think Tanks', 'data-table' ); ?>
			<span class="found-records" data-wp-text="state.foundRecords_think-tank-archive">
			</a>
			
		</li>
		<li 
			class="tab-link"
			data-wp-on--click="actions.toggleDonor"
			data-wp-class--active="state.donorSelected"
			data-entity-type="donor"
		>
			<a href="<?php echo add_query_arg( 'entity_type', 'donor' ); ?>" data-entity-type="donor"><?php esc_html_e( 'Donors', 'data-table' ); ?>
			<span class="found-records" data-wp-text="state.foundRecords_donor-archive">
			</a>
		</li>
	</ul>
	
</div>

<?php
$output = ob_get_clean();
echo wp_interactivity_process_directives( $output );
