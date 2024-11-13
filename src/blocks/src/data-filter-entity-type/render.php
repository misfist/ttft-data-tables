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

$entity_type = get_query_var( 'entity_type', 'think_tank' );

wp_interactivity_state(
	$app_namespace,
	array(
		$state_key          => $entity_type,
		'thinkTankSelected' => true,
		'donorSelected'     => false,
	),
);

$context = array(
	$state_key          => $entity_type,
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'               => 'transaction-results-tabs',
		'data-wp-interactive' => $app_namespace,
		'data-wp-watch'       => 'callbacks.logEntityType',
		'data-wp-bind--donor' => 'state.' . $state_key,
		'data-wp-context'     => wp_interactivity_data_wp_context( $context ),
	)
);

ob_start();
?>

<div <?php echo $wrapper_attributes; ?> >
	<ul class="wp-block-list tab-links">
		<li
			class="tab-link active"
			data-wp-context='{ "thinkTankSelected": <?php echo $entity_type === 'think_tank' ? 'true' : 'false'; ?> }'
			data-wp-on--click="actions.toggleThinkTank"
			data-wp-class--active="state.thinkTankSelected"
			data-entity-type="think_tank"
		 >
			<a href="<?php echo add_query_arg( 'entity_type', 'think_tank' ); ?>" data-entity-type="think_tank"><?php esc_html_e( 'Think Tanks', 'data-table' ); ?></a>
		</li>
		<li 
			class="tab-link"
			data-wp-context='{ "donorSelected": <?php echo $entity_type === 'donor' ? 'true' : 'false'; ?> }'
			data-wp-on--click="actions.toggleDonor"
			data-wp-class--active="state.donorSelected"
			data-entity-type="donor"
		>
			<a href="<?php echo add_query_arg( 'entity_type', 'donor' ); ?>" data-entity-type="donor"><?php esc_html_e( 'Donors', 'data-table' ); ?></a>
		</li>
	</ul>
	
</div>

<?php
$output = ob_get_clean();
echo wp_interactivity_process_directives( $output );
