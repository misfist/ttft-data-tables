<?php
/**
 * Plugin Name:       Data Tables - Think Tank Funding Tracker
 * Description:       Display data tables.
 * Version:           1.0.0
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

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
const VERSION       = '1.0.0';
const APP_NAMESPACE = 'ttft/data-tables';

define( 'TTFT_BASENAME', plugin_basename( __FILE__ ) );
define( 'TTFT_PATH', plugin_dir_path( __FILE__ ) );
define( 'TTFT_URL', trailingslashit( plugins_url( plugin_basename( __DIR__ ) ) ) );

/**
 * Enqueue scripts and styles.
 *
 * @author Quincy
 *
 * @return void
 */
function scripts(): void {
	// wp_enqueue_style(
	// 	'ttft-data-tables',
	// 	TTFT_URL . 'src/assets/DataTables/datatables.min.css',
	// 	array(),
	// 	VERSION
	// );
	wp_enqueue_script(
		'ttft-data-tables',
		TTFT_URL . 'src/assets/DataTables/datatables.min.js',
		array( 'jquery' ),
		VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\scripts' );


$file = TTFT_PATH . '/data/class-data-handler.php';
if ( file_exists( $file ) ) {
	require_once $file;
}

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function init() {
	register_block_type_from_metadata( __DIR__ . '/build/data-table' );
	register_block_type_from_metadata( __DIR__ . '/build/data-test' );
	register_block_type_from_metadata( __DIR__ . '/build/data-filter-donation-year' );
	register_block_type_from_metadata( __DIR__ . '/build/data-filter-donor-type' );
}
add_action( 'init', __NAMESPACE__ . '\init' );

/**
 * Register query vars
 *
 * @link https://developer.wordpress.org/reference/hooks/query_vars/
 *
 * @param  array $query_vars
 * @return array
 */
function register_query_vars( array $query_vars ) : array {
	$query_vars[] = 'table_type';
	return $query_vars;
}
\add_filter( 'query_vars', __NAMESPACE__ . '\register_query_vars' );


