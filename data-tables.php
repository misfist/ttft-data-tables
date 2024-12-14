<?php
/**
 * Plugin Name:       Data Tables - Think Tank Funding Tracker
 * Description:       Display data tables.
 * Version:           1.0.1
 * Requires at least: 6.6
 * Requires PHP:      7.2
 * Author:            Pea
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       data-tables
 * Update URI:        ttft-data-tables
 *
 * @package           ttft-data-tables
 */
namespace Ttft\Data_Tables;

use Ttft\Data_Tables\Data_Tables;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
const VERSION       = '1.0.1';
const APP_NAMESPACE = 'ttft/data-tables';

$autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

define( 'TTFT_BASENAME', plugin_basename( __FILE__ ) );
define( 'TTFT_PATH', plugin_dir_path( __FILE__ ) );
define( 'TTFT_URL', trailingslashit( plugins_url( plugin_basename( __DIR__ ) ) ) );
define( 'TTFT_VERSION', VERSION );
define( 'TTFT_APP_NAMESPACE', APP_NAMESPACE );
define( 'TTFT_TABLE_ID', 'funding-data');

$data_tables = new Data_Tables();
