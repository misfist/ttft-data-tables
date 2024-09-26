<?php
/**
 * Plugin Name:       Data Tables - Think Tank Funding Tracker
 * Description:       Display data tables.
 * Version:           0.1.0
 * Requires at least: 6.6
 * Requires PHP:      7.2
 * Author:            Pea
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       data-tables
 *
 * @package           data-tables
 */
namespace TTFT\Data_Tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

const APP_NAMESPACE = 'ttft/data-tables';

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type_from_metadata( __DIR__ . '/build/data-table' );
	register_block_type_from_metadata( __DIR__ . '/build/data-filter-donation-year' );
	register_block_type_from_metadata( __DIR__ . '/build/data-filter-donor-type' );
}
add_action( 'init', __NAMESPACE__ . '\init' );
