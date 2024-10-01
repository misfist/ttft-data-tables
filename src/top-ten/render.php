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

use function TTFT\Data_Tables\Data\render_top_ten_table;

$donor_type = sanitize_text_field( $attributes['donorType'] ) ?? '';
$donation_year = sanitize_text_field( $attributes['donationYear'] ) ?? '';
$number_of_items = sanitize_text_field( $attributes['number'] ) ?? 10;
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php render_top_ten_table( $donor_type = '', $donation_year = '', $number_of_items = 10 ); ?>
</div>
