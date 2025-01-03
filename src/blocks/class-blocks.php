<?php
/**
 * Blocks class.
 */
namespace Ttft\Data_Tables\Blocks;

class Blocks {

    /**
     * Constructor to hook into WordPress actions.
     */
    public function __construct() {
        // add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'enqueue_block_assets', array( $this, 'enqueue_scripts' ) );
        add_action( 'init', array( $this, 'register_blocks' ) );
    }

    /**
     * Enqueue scripts and styles.
     *
     * @return void
     */
    public function enqueue_scripts(): void {
        wp_register_script(
            'data-tables',
            TTFT_URL . 'src/assets/DataTables/datatables.min.js',
            array( 'jquery' ),
            TTFT_VERSION,
            true
        );
    }

    /**
     * Registers blocks using metadata from `block.json`.
     *
     * @return void
     */
    public function register_blocks(): void {
        register_block_type_from_metadata( __DIR__ . '/build/data-table' );
        register_block_type_from_metadata( __DIR__ . '/build/data-test' );
        register_block_type_from_metadata( __DIR__ . '/build/data-filter-donation-year' );
        register_block_type_from_metadata( __DIR__ . '/build/data-filter-donor-type' );
        register_block_type_from_metadata( __DIR__ . '/build/data-filter-entity-type' );
        register_block_type_from_metadata( __DIR__ . '/build/top-ten' );
    }

    /**
     * Register block metadata collection.
     * 
     * @link https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/
     *
     * @return void
     */
    public function register_block_metadata_collection(): void {
        if ( ! function_exists( 'wp_register_block_metadata_collection' ) ) {
            return;
        }
        wp_register_block_metadata_collection(
            __DIR__ . '/build',
            __DIR__ . '/build/blocks-manifest.php'
        );
        
    }

}
