<?php
/**
 * Table Data Functions
 */
namespace Ttft\Data_Tables\Data;

// const TTFT_TABLE_ID = 'funding-data';
// const APP_ID   = 'think-tank-funding';
// const TTFT_APP_NAMESPACE = 'ttft/data-tables';

/**
 * Get table ID
 *
 * @return string
 */
function get_table_id(): string {
	return TTFT_TABLE_ID;
}

/**
 * Get interactivity API namespace
 *
 * @return string
 */
function get_app_id(): string {
	return APP_ID;
}
