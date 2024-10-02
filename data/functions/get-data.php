<?php
/**
 * Get Data Functions
 */
namespace TTFT\Data_Tables\Data;

/**
 * Get Raw Table Data
 *
 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
 * @param int    $number_of_items Optional. The number of items to return. Default 10.
 * @return array An array of transaction data including think_tank term and total amount.
 */
function get_top_ten_raw_data( $donor_type = '', $donation_year = '', $number_of_items = 10 ): array {
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

	$result = array();

	foreach ( $query->posts as $post ) {
		$think_tanks = wp_get_post_terms( $post->ID, 'think_tank' );
		if ( ! $think_tanks ) {
			continue;
		}

		$think_tank = $think_tanks[0];
		$amount     = get_post_meta( $post->ID, 'amount_calc', true );

		$result[] = array(
			'think_tank'   => $think_tank->name,
			'total_amount' => (int) $amount,
			'year'         => implode( ',', wp_get_post_terms( $post->ID, 'donation_year', array( 'fields' => 'names' ) ) ),
			'type'         => implode( ',', wp_get_post_terms( $post->ID, 'donor_type', array( 'fields' => 'names' ) ) ),
		);
	}

	usort(
		$result,
		function ( $a, $b ) {
			return ( strcmp( $a['think_tank'], $b['think_tank'] ) );
		}
	);

	return $result;
}

/**
 * Get raw donor data for think tank.
 *
 * @param string $think_tank    Optional. Slug of the think tank.
 * @param string $donation_year Optional. Slug of the donation year.
 * @param string $donor_type    Optional. Slug of the donor type.
 * @return array
 */
function get_single_think_tank_raw_data( $think_tank = '', $donation_year = '', $donor_type = '' ): array {
	$think_tank    = sanitize_text_field( $think_tank );
	$donation_year = sanitize_text_field( $donation_year );
	$donor_type    = sanitize_text_field( $donor_type );

	$transient_key = 'single_think_tank_' . md5( $think_tank . $donation_year . $donor_type );

	$data = get_transient( $transient_key );
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
		'relevanssi'     => true,
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

	$query   = new \WP_Query( $args );
	$data = array();

	if ( $query->have_posts() ) {
		foreach( $query->posts as $post_id ) {
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

	// wp_reset_postdata();

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
function get_single_donor_raw_data( $donor = '', $donation_year = '', $donor_type = '' ) {
	$donor         = sanitize_text_field( $donor );
	$donation_year = sanitize_text_field( $donation_year );
	$donor_type    = sanitize_text_field( $donor_type );

	$transient_key = 'single_donor_' . md5( $donor . $donation_year . $donor_type );

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
function get_donor_archive_raw_data( $donation_year = '', $donor_type = '', $search = '' ): array {
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
		$args['s']                      = sanitize_text_field( $search );
		$transaction_args['relevanssi'] = true;
	}

	if ( function_exists( 'relevanssi_do_query' ) ) {
		$query = new \WP_Query();
		$query->parse_query( $args );

		relevanssi_do_query( $query );
	} else {
		$query = new \WP_Query( $args );
	}

	$data = array();

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();

			$post_id = get_the_ID();

			$donors = wp_get_object_terms( $post_id, 'donor', array( 'orderby' => 'parent' ) );
			if ( empty( $donors ) || is_wp_error( $donors ) ) {
				continue;
			}

			$donor_names = wp_list_pluck( $donors, 'name' );
			$donor_slugs = wp_list_pluck( $donors, 'slug' );
			$donor_name  = implode( ' > ', $donor_names );
			$donor_slug  = implode( '-', $donor_slugs );

			$amount = get_post_meta( $post_id, 'amount_calc', true );
			$amount = intval( $amount );

			$data[] = array(
				'donor'       => $donor_name,
				'amount_calc' => $amount,
				'donor_type'  => get_the_term_list( $post_id, 'donor_type' ),
				'donor_slug'  => $donor_slug,
				'donor_link'  => get_term_link( $donor_slugs[0], 'donor' ),
				'year'        => get_the_term_list( $post_id, 'donation_year' ),
			);
		}

		wp_reset_postdata();
	}

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
function get_top_ten_data( $donor_type = '', $donation_year = '', $number_of_items = 10 ): array {
	$raw_data = get_top_ten_raw_data( $donor_type, $donation_year, $number_of_items );

	if ( empty( $raw_data ) ) {
		return array();
	}

	$data = array();

	foreach ( $raw_data as $item ) {
		$data[ $item['think_tank'] ] += (int) $item['total_amount'];
	}

	arsort( $data );

	$data = array_slice( $data, 0, $number_of_items, true );

	$result = array();
	foreach ( $data as $think_tank => $total_amount ) {
		$result[] = array(
			'think_tank'   => $think_tank,
			'total_amount' => $total_amount,
		);
	}

	return $result;
}

/**
 * Get data for think tanks
 *
 * @param string $donation_year The donation year to filter by.
 * @return array Array of think tank data.
 */
function get_think_tank_archive_data( $donation_year = '', $search = '' ): array {
	$args = array(
		'post_type'      => 'transaction',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
	);

	if ( ! empty( $donation_year ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'donation_year',
			'field'    => 'slug',
			'terms'    => $donation_year,
		);
	}
	if ( ! empty( $search ) ) {
		$args['s']                      = sanitize_text_field( $search );
		$transaction_args['relevanssi'] = true;
	}

	$query = new \WP_Query( $args );

	$data            = array();
	$all_donor_types = array();

	if ( $query->have_posts() ) {

		while ( $query->have_posts() ) {
			$query->the_post();

			$think_tank_terms = wp_get_post_terms( get_the_ID(), 'think_tank' );
			if ( ! $think_tank_terms ) {
				continue;
			}

			$think_tank      = $think_tank_terms[0]->name;
			$think_tank_slug = $think_tank_terms[0]->slug;

			if ( ! isset( $data[ $think_tank_slug ] ) ) {

				$post_id                  = get_post_from_term( $think_tank_slug, 'think_tank' );
				$data[ $think_tank_slug ] = array(
					'think_tank'         => $think_tank,
					'donor_types'        => array(),
					'transparency_score' => get_transparency_score( $think_tank_slug ),
				);
			}

			$donor_type_terms = wp_get_post_terms( get_the_ID(), 'donor_type' );
			foreach ( $donor_type_terms as $donor_type_term ) {
				$donor_type = $donor_type_term->name;

				if ( ! isset( $data[ $think_tank_slug ]['donor_types'][ $donor_type ] ) ) {
					$data[ $think_tank_slug ]['donor_types'][ $donor_type ] = 0;
				}

				$amount_calc = get_post_meta( get_the_ID(), 'amount_calc', true );
				$amount_calc = floatval( $amount_calc );

				$data[ $think_tank_slug ]['donor_types'][ $donor_type ] += $amount_calc;

				$all_donor_types[ $donor_type ] = true;
			}
		}
	}

	wp_reset_postdata();

	foreach ( $data as &$think_tank_data ) {
		foreach ( $all_donor_types as $donor_type => $value ) {
			if ( ! isset( $think_tank_data['donor_types'][ $donor_type ] ) ) {
				$think_tank_data['donor_types'][ $donor_type ] = 0;
			}
		}
	}

	ksort( $data );

	// echo '<pre>';

	// var_dump( $data );

	// echo '</pre>';

	return $data;
}

/**
 * Get data for think tanks
 *
 * @param string $donation_year The donation year to filter by.
 * @param string $search The search term to filter by.
 * @return array Array of think tank data.
 */
function get_think_tank_archive_data_by_post_type( $donation_year = '', $search = '' ): array {
	$think_tank_args = array(
		'post_type'      => 'think_tank',
		'posts_per_page' => -1,
		'relevanssi'     => true,
	);

	if ( ! empty( $donation_year ) ) {
		$args['tax_query'][] = array(
			'taxonomy' => 'donation_year',
			'field'    => 'slug',
			'terms'    => $donation_year,
		);
	}
	if ( ! empty( $search ) ) {
		$args['s'] = sanitize_text_field( $search );
	}

	$think_tank_query = new \WP_Query( $think_tank_args );

	$data = array();

	if ( $think_tank_query->have_posts() ) {
		while ( $think_tank_query->have_posts() ) {
			$think_tank_query->the_post();
			$think_tank_name  = get_post_field( 'post_name', get_the_ID() );
			$think_tank_title = get_the_title();

			// Initialize donor_types with all possible donor_type terms
			$donor_type_terms = get_terms(
				array(
					'taxonomy'   => 'donor_type',
					'hide_empty' => false,
				)
			);

			$donor_types = array();
			foreach ( $donor_type_terms as $term ) {
				$donor_types[ $term->slug ] = 0;
			}

			$transaction_args = array(
				'post_type'      => 'transaction',
				'posts_per_page' => -1,
				'tax_query'      => array(
					array(
						'taxonomy' => 'think_tank',
						'field'    => 'slug',
						'terms'    => $think_tank_name,
					),
				),
			);

			if ( ! empty( $donation_year ) ) {
				$transaction_args['tax_query'][] = array(
					'taxonomy' => 'donation_year',
					'field'    => 'slug',
					'terms'    => $donation_year,
				);
			}

			$transaction_query = new \WP_Query( $transaction_args );

			if ( ! isset( $data[ $think_tank_name ] ) ) {
				$data[ $think_tank_name ] = array(
					'think_tank'         => $think_tank_title,
					'donor_types'        => $donor_types,
					'transparency_score' => get_transparency_score( $think_tank_name ),
				);
			}

			if ( $transaction_query->have_posts() ) {
				while ( $transaction_query->have_posts() ) {
					$transaction_query->the_post();
					$amount_calc                  = get_post_meta( get_the_ID(), 'amount_calc', true );
					$amount_calc                  = ! empty( $amount_calc ) ? floatval( $amount_calc ) : 0;
					$transaction_donor_type_terms = wp_get_post_terms( get_the_ID(), 'donor_type' );

					foreach ( $transaction_donor_type_terms as $term ) {
						$data[ $think_tank_name ]['donor_types'][ $term->slug ] += $amount_calc;
					}
				}
				wp_reset_postdata();
			}
		}
		wp_reset_postdata();
	}

	// echo '<pre>';
	// var_dump( $data );
	// echo '</pre>';

	return $data;
}


/**
 * Get data for donors
 *
 * @param  string $donation_year
 * @return array
 */
function get_donor_archive_data( $donation_year = '', $donor_type = '', $search = '' ): array {
	$donor_data = get_donor_archive_raw_data( $donation_year, $donor_type, $search );
	$data       = array_reduce(
		$donor_data,
		function ( $carry, $item ) {
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
function get_single_think_tank_data( $think_tank = '', $donation_year = '', $donor_type = '' ): array {
	$raw_data = get_single_think_tank_raw_data( $think_tank, $donation_year, $donor_type );

	if ( empty( $raw_data ) ) {
		return array();
	}

	$data = array_reduce(
		$raw_data,
		function ( $carry, $item ) {
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
function get_single_donor_data( $donor = '', $donation_year = '', $donor_type = '' ) {
	$raw_data = get_single_donor_raw_data( $donor, $donation_year, $donor_type );

	$data = array_reduce(
		$raw_data,
		function ( $carry, $item ) {
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
function get_meta_values_for_records( array $post_ids, string $meta_key ): array {
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
