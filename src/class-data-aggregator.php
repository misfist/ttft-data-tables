<?php
/**
 * Get data for data tables.
 */
namespace Ttft\Data_Tables;

use function Ttft\Data_Tables\get_post_from_term;
use function Ttft\Data_Tables\get_transparency_score_from_slug;

class Data_Aggregator {

	/**
	 * Array of settings.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Transaction Table
	 *
	 * @var string
	 */
	protected static $transactions_table = 'transaction_aggregates';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'site_settings' );
	}

	/**
	 * Get aggregated data for all think tanks with donor type and disclosure status.
	 *
	 * @param string $donation_year Optional. The slug of the donation year to filter by.
	 * @return array List of think tanks with aggregated amounts and disclosure status.
	 */
	public static function get_all_think_tank_data( string $donation_year = '' ): array {
		$table_name = self::get_transactions_table_name();
		if ( ! $table_name || ! self::table_exists() ) {
			error_log( __METHOD__ . ": Aggregates table {$table_name} does not exist." );
			return array();
		}

		global $wpdb;

		$conditions = '';
		$params     = array();

		if ( ! empty( $donation_year ) ) {
			$donation_year_term_id = self::get_term_id_by_slug( $donation_year, 'donation_year' );
			if ( $donation_year_term_id ) {
				$conditions .= ' AND aggregates.donation_year_term_id = %d';
				$params[]    = $donation_year_term_id;
			}
		}

		$query = "
			SELECT 
				think_tank_terms.term_id AS think_tank_id,
				think_tank.name AS think_tank_name,
				think_tank.slug AS think_tank_slug,
				donor_type_terms.term_id AS donor_type_id,
				donor_type.name AS donor_type_name,
				donor_type.slug AS donor_type_slug,
				SUM(aggregates.amount_calc) AS amount_calc,
				CASE 
					WHEN SUM(aggregates.disclosed = 0) = COUNT(aggregates.disclosed) THEN 0
					ELSE 1
				END AS all_disclosed
			FROM {$table_name} aggregates
			LEFT JOIN {$wpdb->term_taxonomy} AS think_tank_taxonomy
				ON aggregates.think_tank_term_id = think_tank_taxonomy.term_taxonomy_id
			LEFT JOIN {$wpdb->terms} AS think_tank
				ON think_tank_taxonomy.term_id = think_tank.term_id
			LEFT JOIN {$wpdb->term_taxonomy} AS donor_type_taxonomy
				ON aggregates.donor_type_term_id = donor_type_taxonomy.term_taxonomy_id
			LEFT JOIN {$wpdb->terms} AS donor_type
				ON donor_type_taxonomy.term_id = donor_type.term_id
			WHERE 1=1
			{$conditions}
			GROUP BY think_tank_terms.term_id, donor_type_terms.term_id
		";

		$query   = $wpdb->prepare( $query, $params );
		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Get aggregated data for parent donors with amount and disclosure status.
	 *
	 * @param string $donation_year Optional. Slug of the donation year term.
	 * @param string $donor_type    Optional. Slug of the donor type term.
	 * @return array List of parent donors with aggregated amounts and disclosure status.
	 */
	public static function get_all_donor_data( string $donation_year = '', string $donor_type = '' ): array {
		$table_name = self::get_transactions_table_name();
		if ( ! $table_name || ! self::table_exists() ) {
			error_log( __METHOD__ . ": Aggregates table {$table_name} does not exist." );
			return array();
		}

		global $wpdb;

		$conditions = '';
		$params     = array();

		if ( ! empty( $donation_year ) ) {
			$donation_year_term_id = self::get_term_id_by_slug( $donation_year, 'donation_year' );
			if ( $donation_year_term_id ) {
				$conditions .= ' AND aggregates.donation_year_term_id = %d';
				$params[]    = $donation_year_term_id;
			}
		}

		if ( ! empty( $donor_type ) ) {
			$donor_type_term_id = self::get_term_id_by_slug( $donor_type, 'donor_type' );
			if ( $donor_type_term_id ) {
				$conditions .= ' AND aggregates.donor_type_term_id = %d';
				$params[]    = $donor_type_term_id;
			}
		}

		$query = "
			SELECT 
				donor_terms.term_id AS donor_id,
				donor_terms.name AS donor_name,
				donor_terms.slug AS donor_slug,
				SUM(aggregates.amount_calc) AS amount_calc,
				CASE 
					WHEN SUM(aggregates.disclosed) = COUNT(aggregates.disclosed) THEN 1
					ELSE 0
				END AS all_disclosed
			FROM {$table_name} aggregates
			INNER JOIN {$wpdb->terms} AS donor_terms
				ON aggregates.donor_term_id = donor_terms.term_id
			INNER JOIN {$wpdb->term_taxonomy} AS donor_taxonomy
				ON donor_terms.term_id = donor_taxonomy.term_id
			WHERE donor_taxonomy.parent = 0
			{$conditions}
			GROUP BY donor_terms.term_id
			ORDER BY amount_calc DESC
		";

		$query   = $wpdb->prepare( $query, $params );
		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Get aggregated data for a single think tank with associated donors and donor types.
	 *
	 * @param string $think_tank_slug The slug of the think tank term.
	 * @param string $donation_year   Optional. The slug of the donation year term.
	 * @param string $donor_type      Optional. The slug of the donor type term.
	 * @return array List of donors with aggregated amounts and disclosure status.
	 */
	public static function get_single_think_tank_data( string $think_tank_slug, string $donation_year = '', string $donor_type = '' ): array {
		$table_name = self::get_transactions_table_name();
		if ( ! $table_name || ! self::table_exists() ) {
			error_log( __METHOD__ . ": Aggregates table {$table_name} does not exist." );
			return array();
		}

		global $wpdb;

		$conditions = array();
		$params     = array();

		$think_tank_term_id = self::get_term_id_by_slug( $think_tank_slug, 'think_tank' );
		if ( $think_tank_term_id ) {
			$conditions[] = 'aggregates.think_tank_term_id = %d';
			$params[]     = $think_tank_term_id;
		} else {
			error_log( __METHOD__ . ": Think tank term {$think_tank_slug} not found." );
			return array();
		}

		if ( ! empty( $donation_year ) ) {
			$donation_year_term_id = self::get_term_id_by_slug( $donation_year, 'donation_year' );
			if ( $donation_year_term_id ) {
				$conditions[] = 'aggregates.donation_year_term_id = %d';
				$params[]     = $donation_year_term_id;
			}
		}

		if ( ! empty( $donor_type ) ) {
			$donor_type_term_id = self::get_term_id_by_slug( $donor_type, 'donor_type' );
			if ( $donor_type_term_id ) {
				$conditions[] = 'aggregates.donor_type_term_id = %d';
				$params[]     = $donor_type_term_id;
			}
		}

		$query = "
			SELECT 
				donor_terms.name AS donor_name,
				donor_terms.slug AS donor_slug,
				donor_type_terms.name AS donor_type_name,
				donor_type_terms.slug AS donor_type_slug,
				SUM(aggregates.amount_calc) AS amount_calc,
				CASE 
					WHEN SUM(aggregates.disclosed = 0) = COUNT(aggregates.disclosed) THEN 0
					ELSE 1
				END AS all_disclosed
			FROM {$table_name} aggregates
			INNER JOIN {$wpdb->term_taxonomy} donor_taxonomy
				ON aggregates.donor_term_id = donor_taxonomy.term_taxonomy_id
			INNER JOIN {$wpdb->terms} donor_terms
				ON donor_taxonomy.term_id = donor_terms.term_id
			LEFT JOIN {$wpdb->term_taxonomy} donor_type_taxonomy
				ON aggregates.donor_type_term_id = donor_type_taxonomy.term_taxonomy_id
			LEFT JOIN {$wpdb->terms} donor_type_terms
				ON donor_type_taxonomy.term_id = donor_type_terms.term_id
			WHERE " . implode( ' AND ', $conditions ) . '
			GROUP BY donor_terms.term_id, donor_type_terms.term_id
			ORDER BY donor_name ASC
		';

		$query   = $wpdb->prepare( $query, $params );
		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Get aggregated data for a single donor (parent donors only) with associated think tanks and disclosure status.
	 *
	 * @param string $donor_slug      The slug of the parent donor term.
	 * @param string $donation_year   Optional. The slug of the donation year term.
	 * @param string $donor_type      Optional. The slug of the donor type term.
	 * @return array List of think tanks and associated data.
	 */
	public static function get_single_donor_data( string $donor_slug, string $donation_year = '', string $donor_type = '' ): array {
		$table_name = self::get_transactions_table_name();
		if ( ! $table_name || ! self::table_exists() ) {
			error_log( __METHOD__ . ": Aggregates table {$table_name} does not exist." );
			return array();
		}

		global $wpdb;

		$conditions = array();
		$params     = array();

		$donor_term_id = self::get_term_id_by_slug( $donor_slug, 'donor' );
		if ( $donor_term_id ) {
			$conditions[] = '(aggregates.donor_term_id = %d OR donor_taxonomy.parent = %d)';
			$params[]     = $donor_term_id;
			$params[]     = $donor_term_id;
		} else {
			error_log( __METHOD__ . ": Donor term {$donor_slug} not found." );
			return array();
		}

		if ( ! empty( $donation_year ) ) {
			$donation_year_term_id = self::get_term_id_by_slug( $donation_year, 'donation_year' );
			if ( $donation_year_term_id ) {
				$conditions[] = 'aggregates.donation_year_term_id = %d';
				$params[]     = $donation_year_term_id;
			}
		}

		if ( ! empty( $donor_type ) ) {
			$donor_type_term_id = self::get_term_id_by_slug( $donor_type, 'donor_type' );
			if ( $donor_type_term_id ) {
				$conditions[] = 'aggregates.donor_type_term_id = %d';
				$params[]     = $donor_type_term_id;
			}
		}

		$query = "
			SELECT 
				think_tank_terms.name AS think_tank_name,
				think_tank_terms.slug AS think_tank_slug,
				donor_terms.name AS donor_name,
				donor_terms.slug AS donor_slug,
				SUM(aggregates.amount_calc) AS amount_calc,
				CASE 
					WHEN SUM(aggregates.disclosed = 0) = COUNT(aggregates.disclosed) THEN 0
					ELSE 1
				END AS all_disclosed
			FROM {$table_name} aggregates
			INNER JOIN {$wpdb->term_taxonomy} donor_taxonomy
				ON aggregates.donor_term_id = donor_taxonomy.term_taxonomy_id
			INNER JOIN {$wpdb->terms} donor_terms
				ON donor_taxonomy.term_id = donor_terms.term_id
			LEFT JOIN {$wpdb->term_taxonomy} think_tank_taxonomy
				ON aggregates.think_tank_term_id = think_tank_taxonomy.term_taxonomy_id
			LEFT JOIN {$wpdb->terms} think_tank_terms
				ON think_tank_taxonomy.term_id = think_tank_terms.term_id
			WHERE " . implode( ' AND ', $conditions ) . '
			GROUP BY think_tank_terms.term_id, donor_terms.term_id
			ORDER BY think_tank_name ASC
		';

		$query   = $wpdb->prepare( $query, $params );
		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Get the top X think tanks by donor type from the aggregates table.
	 *
	 * @param string $donor_type Optional. The slug of the donor type term.
	 * @param int    $limit      Optional. The number of top think tanks to return. Default is 10.
	 * @return array List of top think tanks with aggregated amounts.
	 */
	public static function get_top_think_tanks_by_donor_type( string $donor_type = '', int $limit = 10 ): array {
		$table_name = self::get_transactions_table_name();
		if ( ! $table_name || ! self::table_exists() ) {
			error_log( __METHOD__ . ": Aggregates table {$table_name} does not exist." );
			return array();
		}

		global $wpdb;

		$conditions = array();
		$params     = array();

		if ( ! empty( $donor_type ) ) {
			$donor_type_term_id = self::get_term_id_by_slug( $donor_type, 'donor_type' );
			if ( $donor_type_term_id ) {
				$conditions[] = 'aggregates.donor_type_term_id = %d';
				$params[]     = $donor_type_term_id;
			} else {
				error_log( __METHOD__ . ": Donor type term {$donor_type} not found." );
				return array();
			}
		}

		$query = "
			SELECT 
				think_tank_terms.name AS think_tank_name,
				think_tank_terms.slug AS think_tank_slug,
				SUM(aggregates.amount_calc) AS amount_calc
			FROM {$table_name} aggregates
			INNER JOIN {$wpdb->term_taxonomy} think_tank_taxonomy
				ON aggregates.think_tank_term_id = think_tank_taxonomy.term_taxonomy_id
			INNER JOIN {$wpdb->terms} think_tank_terms
				ON think_tank_taxonomy.term_id = think_tank_terms.term_id
			WHERE " . implode( ' AND ', $conditions ) . '
			GROUP BY think_tank_terms.term_id
			ORDER BY amount_calc DESC
			LIMIT %d
		';

		$params[] = $limit;

		$query   = $wpdb->prepare( $query, $params );
		$results = $wpdb->get_results( $query, ARRAY_A );

		return $results ?: array();
	}

	/**
	 * Get the full transaction aggregates table name.
	 *
	 * @return string|null Full table name or null if undefined.
	 */
	private static function get_transactions_table_name(): ?string {
		if ( empty( self::$transactions_table ) ) {
			error_log( __METHOD__ . ': Transaction aggregates table name is not defined.' );
			return null;
		}

		global $wpdb;

		return $wpdb->prefix . self::$transactions_table;
	}

	/**
	 * Check if the transaction aggregates table exists.
	 *
	 * @return bool True if the table exists, false otherwise.
	 */
	private static function table_exists(): bool {
		$table_name = self::get_transactions_table_name();
		if ( ! $table_name ) {
			error_log( __METHOD__ . ': Transaction aggregates table name is not defined.' );
			return false;
		}
		global $wpdb;

		$query = $wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		);

		return (bool) $wpdb->get_var( $query );
	}

	/**
	 * Get meta values for a set of post IDs.
	 *
	 * @param array  $post_ids Array of post IDs.
	 * @param string $meta_key Meta key to retrieve.
	 * @return array Array of meta values keyed by post ID.
	 */
	public function get_meta_values_for_records( array $post_ids, string $meta_key ): array {
		global $wpdb;

		if ( empty( $post_ids ) ) {
			return array();
		}

		$values = array();
		$chunks = array_chunk( $post_ids, 1000 );

		foreach ( $chunks as $chunk ) {
			$placeholders = implode( ',', array_fill( 0, count( $chunk ), '%d' ) );
			$query        = $wpdb->prepare(
				"
                SELECT post_id, meta_value
                FROM $wpdb->postmeta
                WHERE post_id IN ( $placeholders )
                AND meta_key = %s
            ",
				array_merge( $chunk, array( $meta_key ) )
			);

			$results = $wpdb->get_results( $query, OBJECT_K );
			foreach ( $results as $post_id => $result ) {
				$values[ $post_id ] = $result->meta_value;
			}
		}

		return $values;
	}

	/**
	 * Get term IDs that match a search term.
	 *
	 * @param  string $search
	 * @param  string $taxonomy
	 * @return array
	 */
	public function get_search_term_ids( $search, $taxonomy ) {
		$search = sanitize_text_field( $search );
		$args   = array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'search'     => $search,
			'fields'     => 'ids',
		);

		$terms = get_terms( $args );
		return ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? $terms : array();
	}

	/**
	 * Check if all transactions for the given post IDs are not disclosed.
	 *
	 * @param array $post_ids Array of transaction post IDs.
	 * @return bool True if any transaction is disclosed, false if all are undisclosed.
	 */
	public function is_disclosed( array $post_ids ): bool {
		$meta_values = $this->get_meta_values_for_records( $post_ids, 'disclosed' );

		$undisclosed = array_filter(
			$meta_values,
			function ( $value ) {
				return strtolower( $value ) !== 'no';
			}
		);

		// If all values are 'no', return false. Otherwise, return true.
		return ! empty( $undisclosed );
	}

}
