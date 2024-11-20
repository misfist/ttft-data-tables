<?php
/**
 * Get data for data tables.
 */
namespace Ttft\Data_Tables;

use function Ttft\Data_Tables\get_post_from_term;
use function Ttft\Data_Tables\get_transparency_score_from_slug;

class Data {

	/**
	 * Get Raw Table Data
	 *
	 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
	 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
	 * @param int    $number_of_items Optional. The number of items to return. Default 10.
	 * @return array An array of transaction data including think_tank term and total amount.
	 */
	public function get_top_ten_raw_data( $donor_type = '', $donation_year = '', $number_of_items = 10 ): array {
		$transient_key = 'top_ten_raw_data_' . md5( $donor_type . '_' . $donation_year . '_' . $number_of_items );
		$data          = get_transient( $transient_key );

		if ( false === $data ) {
			$args = array(
				'post_type'      => 'transaction',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			);

			$tax_query = array( 'relation' => 'AND' );
			if ( $donor_type ) {
				$tax_query[] = array(
					'taxonomy' => 'donor_type',
					'field'    => 'slug',
					'terms'    => $donor_type,
				);
			}

			if ( $donation_year ) {
				$tax_query[] = array(
					'taxonomy' => 'donation_year',
					'field'    => 'slug',
					'terms'    => $donation_year,
				);
			}

			if ( ! empty( $tax_query ) ) {
				$args['tax_query'] = $tax_query;
			}

			$query = new \WP_Query( $args );
			$data  = array();

			foreach ( $query->posts as $post ) {
				$think_tanks = wp_get_post_terms( $post->ID, 'think_tank' );
				if ( ! $think_tanks ) {
					continue;
				}

				$think_tank = $think_tanks[0];
				$amount     = get_post_meta( $post->ID, 'amount_calc', true );

				$data[] = array(
					'think_tank'      => $think_tank->name,
					'think_tank_slug' => $think_tank->slug,
					'total_amount'    => (int) $amount,
					'year'            => implode( ',', wp_get_post_terms( $post->ID, 'donation_year', array( 'fields' => 'names' ) ) ),
					'type'            => implode( ',', wp_get_post_terms( $post->ID, 'donor_type', array( 'fields' => 'names' ) ) ),
				);
			}

			usort(
				$data,
				function ( $a, $b ) {
					return ( strcmp( $a['think_tank'], $b['think_tank'] ) );
				}
			);

			set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Get raw donor data for think tank.
	 *
	 * @param string $think_tank    Optional. Slug of the think tank.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 * @return array
	 */
	public function get_single_think_tank_raw_data( $think_tank = '', $donation_year = '', $donor_type = '' ): array {
		$think_tank    = sanitize_text_field( $think_tank );
		$donation_year = sanitize_text_field( $donation_year );
		$donor_type    = sanitize_text_field( $donor_type );

		$transient_key = 'single_think_tank_' . md5( $think_tank . $donation_year . $donor_type );
		$data          = get_transient( $transient_key );
		if ( false !== $data ) {
			return $data;
		}

		$args = array(
			'post_type'      => 'transaction',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array(
				'relation' => 'AND',
			),
		);

		if ( $think_tank ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'think_tank',
				'field'    => 'slug',
				'terms'    => $think_tank,
			);
		}

		if ( $donation_year ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'donation_year',
				'field'    => 'slug',
				'terms'    => $donation_year,
			);
		}

		if ( $donor_type ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'donor_type',
				'field'    => 'slug',
				'terms'    => $donor_type,
			);
		}

		$query = new \WP_Query( $args );
		$data  = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$donors = wp_get_object_terms( $post_id, 'donor', array( 'orderby' => 'parent' ) );
				if ( empty( $donors ) || is_wp_error( $donors ) ) {
					continue;
				}

				$donor_names = wp_list_pluck( $donors, 'name' );
				$donor_slugs = wp_list_pluck( $donors, 'slug' );
				$donor_name  = implode( ' > ', $donor_names );
				$donor_slug  = implode( '-', $donor_slugs );

				$amount_calc = (int) get_post_meta( $post_id, 'amount_calc', true );
				if ( empty( $amount_calc ) ) {
					$amount_calc = 0;
				}

				$data[] = array(
					'donor'       => $donor_name,
					'amount_calc' => $amount_calc,
					'donor_type'  => get_the_term_list( $post_id, 'donor_type' ),
					'donor_link'  => get_term_link( $donor_slugs[0], 'donor' ),
					'donor_slug'  => $donor_slug,
					'source'      => get_post_meta( $post_id, 'source', true ),
				);
			}
		}

		set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );
		return $data;
	}

	/**
	 * Retrieve think tank data for individual donor
	 *
	 * @param string $donor Optional. Slug of the donor taxonomy term to filter by.
	 * @param string $donation_year Optional. Slug of the donation_year taxonomy term to filter by.
	 * @return array Array of transaction data.
	 */
	public function get_single_donor_raw_data( $donor = '', $donation_year = '', $donor_type = '' ): array {
		$donor         = sanitize_text_field( $donor );
		$donation_year = sanitize_text_field( $donation_year );
		$donor_type    = sanitize_text_field( $donor_type );

		$transient_key = 'single_donor_' . md5( $donor . $donation_year . $donor_type );
		$data          = get_transient( $transient_key );
		if ( false !== $data || ! empty( $data ) ) {
			return $data;
		}

		$args = array(
			'post_type'      => 'transaction',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

		$tax_query = array( 'relation' => 'AND' );

		if ( ! empty( $donor ) ) {
			$tax_query[] = array(
				'taxonomy' => 'donor',
				'field'    => 'slug',
				'terms'    => $donor,
			);
		}

		if ( ! empty( $donation_year ) ) {
			$tax_query[] = array(
				'taxonomy' => 'donation_year',
				'field'    => 'slug',
				'terms'    => $donation_year,
			);
		}

		if ( ! empty( $donor_type ) ) {
			$tax_query[] = array(
				'taxonomy' => 'donor_type',
				'field'    => 'slug',
				'terms'    => $donor_type,
			);
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		$query = new \WP_Query( $args );

		$data = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id     = get_the_ID();
				$think_tanks = get_the_terms( $post_id, 'think_tank' );

				if ( ! $think_tanks ) {
					continue;
				}

				$think_tank      = $think_tanks[0];
				$think_tank_slug = $think_tank->slug;
				$source          = get_post_meta( $post_id, 'source', true );

				$amount_calc = get_post_meta( $post_id, 'amount_calc', true );
				if ( empty( $amount_calc ) ) {
					$amount_calc = 0;
				}

				$donor_type = get_the_term_list( $post_id, 'donor_type', '', ', ', '' );

				$donors = wp_get_object_terms( $post_id, 'donor', array( 'orderby' => 'parent' ) );
				if ( empty( $donors ) || is_wp_error( $donors ) ) {
					continue;
				}

				$donor_names = wp_list_pluck( $donors, 'name' );
				$donor       = end( $donor_names );

				$data[] = array(
					'think_tank'      => $think_tank->name,
					'donor'           => $donor,
					'amount_calc'     => (int) $amount_calc,
					'donor_type'      => $donor_type,
					'source'          => $source,
					'think_tank_slug' => $think_tank_slug,
				);
			}
			wp_reset_postdata();
		}

		set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Retrieve donor data, optionally filtered by donation year.
	 *
	 * @param string $donation_year The slug of the donation year to filter transactions by (optional).
	 * @return array
	 */
	public function get_donor_archive_raw_data( $donation_year = '', $donor_type = '', $search = '' ): array {
		$donation_year = sanitize_text_field( $donation_year );
		$donor_type    = sanitize_text_field( $donor_type );
		$search        = sanitize_text_field( $search );

		$transient_key = 'donor_archive_raw_' . md5( $donation_year . $donor_type . $search );
		$data          = get_transient( $transient_key );
		if ( false !== $data || ! empty( $data ) ) {
			return $data;
		}

		$args = array(
			'post_type'      => 'transaction',
			'posts_per_page' => -1,
		);

		if ( $donation_year ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'donation_year',
					'field'    => 'slug',
					'terms'    => sanitize_title( $donation_year ),
				),
			);
		}

		if ( $donor_type ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'donor_type',
					'field'    => 'slug',
					'terms'    => sanitize_title( $donor_type ),
				),
			);
		}

		if ( ! empty( $search ) ) {
			$taxonomy = 'donor';
			$terms    = $this->get_search_term_ids( $search, $taxonomy );
			if ( $terms ) {
				$args['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'id',
					'terms'    => (array) $terms,
				);
			}
		}

		$query = new \WP_Query( $args );

		$data = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$post_id = get_the_ID();

				/**
				* Limit to "top level" donors
				*/
				$tax_args = array(
					'parent'  => 0,
					'orderby' => 'slug',
				);

				$donors = wp_get_object_terms( $post_id, 'donor', $tax_args );
				if ( empty( $donors ) || is_wp_error( $donors ) ) {
					continue;
				}

				$donor_names = wp_list_pluck( $donors, 'name' );
				$donor_slugs = wp_list_pluck( $donors, 'slug' );
				$donor_name  = implode( ' > ', $donor_names );
				$donor_slug  = implode( '-', $donor_slugs );

				$amount = get_post_meta( $post_id, 'amount_calc', true );
				$amount = intval( $amount );

				$donor_post_id = get_post_from_term( $donor_slug, 'donor' ) ?? $post_id;

				$data[] = array(
					'donor'       => $donor_name,
					'amount_calc' => $amount,
					'donor_type'  => get_the_term_list( $donor_post_id, 'donor_type' ),
					'donor_slug'  => $donor_slug,
					'donor_link'  => get_term_link( $donor_slug, 'donor' ),
					'year'        => get_the_term_list( $post_id, 'donation_year' ),
				);

				wp_reset_postdata();
			}
		}

		set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Get data for top ten
	 *
	 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
	 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
	 * @param int    $number_of_items Optional. The number of items to return. Default 10.
	 * @return array An array of transaction data including think_tank term and total amount.
	 */
	public function get_top_ten_data( $donor_type = '', $donation_year = '', $number_of_items = 10 ): array {
		$raw_data = $this->get_top_ten_raw_data( $donor_type, $donation_year, $number_of_items );

		if ( empty( $raw_data ) ) {
			return array();
		}

		$data = array();
		foreach ( $raw_data as $item ) {
			if ( ! isset( $data[ $item['think_tank_slug'] ] ) ) {
				$data[ $item['think_tank_slug'] ] = array(
					'think_tank'   => $item['think_tank'],
					'total_amount' => 0,
				);
			}
			$data[ $item['think_tank_slug'] ]['total_amount'] += (int) $item['total_amount'];
		}

		usort(
			$data,
			function( $a, $b ) {
				return $b['total_amount'] - $a['total_amount'];
			}
		);

		return array_slice( $data, 0, $number_of_items );
	}

	/**
	 * Get data for think tanks
	 *
	 * @param string $donation_year The donation year to filter by.
	 * @param string $search Optional search term to filter by think tank.
	 * @return array Array of think tank data.
	 */
	public function get_think_tank_archive_data( $donation_year = '', $search = '' ): array {
		$donation_year = sanitize_text_field( $donation_year );
		$search        = sanitize_text_field( $search );
	
		$transient_key = 'think_tank_archive_data_' . md5( $donation_year . '_' . $search );
		$data = get_transient( $transient_key );
	
		if ( false !== $data ) {
			return $data;
		}
	
		// Get all donor types.
		$donor_type_terms = get_terms(
			array(
				'taxonomy'   => 'donor_type',
				'orderby'    => 'term_id',
				'order'      => 'ASC',
				'hide_empty' => false,
			)
		);
	
		// Initialize donor_types with default values.
		$default_donor_types = array();
		foreach ( $donor_type_terms as $term ) {
			$default_donor_types[ $term->name ] = 0;
		}
	
		$think_tank_args = array(
			'post_type'      => 'think_tank',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);
	
		if ( ! empty( $search ) ) {
			$think_tank_args['s'] = $search;
		}
	
		$think_tank_query = new \WP_Query( $think_tank_args );
		$data = array();
	
		if ( $think_tank_query->have_posts() ) {
			foreach ( $think_tank_query->posts as $think_tank_id ) {
				$think_tank_slug = get_post_field( 'post_name', $think_tank_id );
				$data[ $think_tank_slug ] = array(
					'think_tank'           => get_the_title( $think_tank_id ),
					'donor_types'          => $default_donor_types, // Initialize donor_types with default values.
					'transparency_score'   => get_transparency_score_from_slug( $think_tank_slug ),
					'no_defense_accepted'  => get_post_meta( $think_tank_id, 'no_defense_accepted', true ),
					'no_domestic_accepted' => get_post_meta( $think_tank_id, 'no_domestic_accepted', true ),
					'no_foreign_accepted'  => get_post_meta( $think_tank_id, 'no_foreign_accepted', true ),
					'limited_info'         => get_post_meta( $think_tank_id, 'limited_info', true ),
				);
			}
		}
		wp_reset_postdata();
	
		$transaction_args = array(
			'post_type'      => 'transaction',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);
	
		if ( ! empty( $donation_year ) ) {
			$transaction_args['tax_query'][] = array(
				'taxonomy' => 'donation_year',
				'field'    => 'slug',
				'terms'    => $donation_year,
			);
		}
	
		$transaction_query = new \WP_Query( $transaction_args );
	
		if ( $transaction_query->have_posts() ) {
			while ( $transaction_query->have_posts() ) {
				$transaction_query->the_post();
				$think_tank_terms = wp_get_post_terms( get_the_ID(), 'think_tank' );
	
				if ( ! $think_tank_terms ) {
					continue;
				}
	
				$think_tank_slug = $think_tank_terms[0]->slug;
	
				foreach ( wp_get_post_terms( get_the_ID(), 'donor_type' ) as $donor_type_term ) {
					$donor_type = $donor_type_term->name;
	
					$disclosed = get_post_meta( get_the_ID(), 'disclosed', true );
					$amount_calc = get_post_meta( get_the_ID(), 'amount_calc', true );
	
					if ( strcasecmp( $disclosed, 'no' ) === 0 ) {
						$data[ $think_tank_slug ]['donor_types'][ $donor_type ] = esc_attr__( 'unknown', 'ttft-data-tables' );
					} else {
						$amount_calc = floatval( $amount_calc );
						if ( is_numeric( $data[ $think_tank_slug ]['donor_types'][ $donor_type ] ) ) {
							$data[ $think_tank_slug ]['donor_types'][ $donor_type ] += $amount_calc;
						}
					}
				}
			}
		}
		wp_reset_postdata();
	
		ksort( $data );
	
		set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );
	
		return $data;
	}
	
	
	/**
	 * Get data for donors
	 *
	 * @param string $donation_year
	 * @param string $donor_type
	 * @param string $search
	 * @return array
	 */
	public function get_donor_archive_data( $donation_year = '', $donor_type = '', $search = '' ): array {
		$donor_data = $this->get_donor_archive_raw_data( $donation_year, $donor_type, $search );

		$data = array_reduce(
			$donor_data,
			function( $carry, $item ) {
				$donor_slug  = $item['donor_slug'];
				$amount_calc = $item['amount_calc'];
				$year        = $item['year'];

				if ( ! isset( $carry[ $donor_slug ] ) ) {
					$carry[ $donor_slug ] = array(
						'donor'       => $item['donor'],
						'amount_calc' => $amount_calc,
						'donor_type'  => $item['donor_type'],
						'donor_slug'  => $donor_slug,
						'donor_link'  => $item['donor_link'],
						'year'        => $year,
					);
				} else {
					$carry[ $donor_slug ]['amount_calc'] += $amount_calc;

					$years = explode( ', ', $carry[ $donor_slug ]['year'] );
					if ( ! in_array( $year, $years ) ) {
						$years[]                      = $year;
						$carry[ $donor_slug ]['year'] = implode( ', ', $years );
					}
				}
				return $carry;
			},
			array()
		);

		ksort( $data );
		return $data;
	}

	/**
	 * Aggregates donor data for think tank.
	 *
	 * @param string $think_tank    Optional. Slug of the think tank.
	 * @param string $donation_year Optional. Slug of the donation year.
	 * @param string $donor_type    Optional. Slug of the donor type.
	 * @return array
	 */
	public function get_single_think_tank_data( $think_tank = '', $donation_year = '', $donor_type = '' ): array {
		$raw_data = $this->get_single_think_tank_raw_data( $think_tank, $donation_year, $donor_type );

		$data = array_reduce(
			$raw_data,
			function( $carry, $item ) {
				$donor_slug = $item['donor_slug'];

				if ( ! isset( $carry[ $donor_slug ] ) ) {
					$carry[ $donor_slug ] = array(
						'donor'       => $item['donor'],
						'amount_calc' => 0,
						'donor_type'  => $item['donor_type'],
						'donor_slug'  => $donor_slug,
						'donor_link'  => $item['donor_link'],
						'source'      => $item['source'],
					);
				}

				$carry[ $donor_slug ]['amount_calc'] += $item['amount_calc'];
				return $carry;
			},
			array()
		);

		ksort( $data );
		return $data;
	}

	/**
	 * Aggregate 'amount_calc' values for individual donor
	 *
	 * @param string $donor Optional. Slug of the donor taxonomy term to filter by.
	 * @param string $donation_year Optional. Slug of the donation_year taxonomy term to filter by.
	 * @return array Aggregated data with summed 'amount_calc' values.
	 */
	public function get_single_donor_data( $donor = '', $donation_year = '', $donor_type = '' ): array {
		$raw_data = $this->get_single_donor_raw_data( $donor, $donation_year, $donor_type );

		$data = array_reduce(
			$raw_data,
			function( $carry, $item ) {
				$think_tank_slug = $item['think_tank_slug'];
				if ( ! isset( $carry[ $think_tank_slug ] ) ) {
					$carry[ $think_tank_slug ] = array(
						'think_tank'      => $item['think_tank'],
						'donor'           => $item['donor'],
						'amount_calc'     => 0,
						'donor_type'      => $item['donor_type'],
						'source'          => $item['source'],
						'think_tank_slug' => $think_tank_slug,
					);
				}
				$carry[ $think_tank_slug ]['amount_calc'] += $item['amount_calc'];
				return $carry;
			},
			array()
		);

		ksort( $data );
		return $data;
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
