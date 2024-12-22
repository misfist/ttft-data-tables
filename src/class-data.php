<?php
/**
 * Get data for data tables.
 */
namespace Ttft\Data_Tables;

use function Ttft\Data_Tables\get_post_from_term;
use function Ttft\Data_Tables\get_transparency_score_from_slug;

class Data {

	/**
	 * Array of parsed arguments.
	 *
	 * @var array
	 */
	private $args = array();

	/**
	 * Array of settings.
	 *
	 * @var array
	 */
	public $settings = array();

	/**
	 * Time for cache expiration.
	 *
	 * @var string
	 */
	public $cache_expiration = 12 * HOUR_IN_SECONDS;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = get_option( 'site_settings' );

		if ( 'local' === wp_get_environment_type() ) {
			$this->cache_expiration = 0;
		}
	}

	/**
	 * Set the args for the class.
	 *
	 * @param array $args Arguments to parse and sanitize.
	 * @return void
	 */
	public function set_args( array $args = array() ): void {
		$this->args = $this->sanitize_args( $args );
	}

	/**
	 * Build cache key for the query.
	 *
	 * @param  array  $args
	 * @return string
	 */
	public function get_cache_key( array $args = array() ): string {
		ksort( $args );

		$params = http_build_query( $args, '', '&' );

		$transient_key = 'query_' . md5( $params );

		return $transient_key;
	}

	/**
	 * Sanitize and parse the args.
	 *
	 * @param array $args Arguments to parse and sanitize.
	 * @return array Sanitized and parsed arguments.
	 */
	private function sanitize_args( array $args = array() ): array {
		$defaults = array(
			'think_tank'    => null,
			'donor'         => null,
			'donation_year' => null,
			'donor_type'    => null,
			'search'        => null,
			'limit'         => null, // Do not apply a limit in the query.
		);

		$args = wp_parse_args( $args, $defaults );

		return array(
			'think_tank'    => isset( $args['think_tank'] ) ? sanitize_text_field( $args['think_tank'] ) : null,
			'donor'         => isset( $args['donor'] ) ? sanitize_text_field( $args['donor'] ) : null,
			'donation_year' => isset( $args['donation_year'] ) ? sanitize_text_field( $args['donation_year'] ) : null,
			'donor_type'    => isset( $args['donor_type'] ) ? sanitize_text_field( $args['donor_type'] ) : null,
			'search'        => isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : null,
			'limit'         => isset( $args['limit'] ) ? absint( $args['limit'] ) : null,
		);
	}

	/**
	 * Generate the query arguments based on the class args.
	 *
	 * @return array The generated query arguments.
	 */
	private function generate_query_args(): array {
		$post_type = 'transaction';

		// If search is provided, prioritize it and apply `taxonomy` and `terms` if available.
		if ( ! empty( $this->args['search'] ) ) {
			$args = array(
				'post_type'      => $post_type,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				's'              => $this->args['search'],
				'search_columns' => array( 'post_title' ),
			);

			if ( ! empty( $this->args['taxonomy'] ) && ! empty( $this->args['terms'] ) ) {
				$args['tax_query'] = array(
					array(
						'taxonomy'         => $this->args['taxonomy'],
						'field'            => 'id',
						'terms'            => $this->args['terms'],
						'include_children' => true,
					),
				);
			}

			return $args;
		}

		// Default query for non-search filters.
		$args = array(
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array( 'relation' => 'AND' ),
		);

		$taxonomies = array(
			'think_tank',
			'donor',
			'donation_year',
			'donor_type',
		);

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! empty( $this->args[ $taxonomy ] ) ) {
				$args['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $this->args[ $taxonomy ],
				);
			}
		}

		return $args;
	}

	/**
	 * Generate and execute a query based on the parsed args.
	 *
	 * @return \WP_Query The generated query object.
	 */
	public function generate_query(): \WP_Query {
		$query_args = $this->generate_query_args();

		$cache_key = $this->get_cache_key( $query_args );

		$cached_query = get_transient( $cache_key );

		if ( false !== $cached_query ) {
			return $cached_query;
		}

		$query = new \WP_Query( $query_args );
		set_transient( $cache_key, $query, $this->cache_expiration );

		return $query;
	}

	/**
	 * Get Raw Table Data
	 *
	 * @param string $donor_type Optional. The slug of the donor_type taxonomy term. Default empty.
	 * @param string $donation_year Optional. The slug of the donation_year taxonomy term. Default empty.
	 * @param int    $number_of_items Optional. The number of items to return. Default 10.
	 * @return array An array of transaction data including think_tank term and total amount.
	 */
	public function get_top_ten_raw_data( $donor_type = '', $donation_year = '', $number_of_items = 10 ): array {
		$this->set_args(
			array(
				'donor_type'    => $donor_type,
				'donation_year' => $donation_year,
				'limit'         => $number_of_items,
			)
		);

		$query = $this->generate_query();

		if ( empty( $query->posts ) ) {
			return array();
		}

		$data = array();

		foreach ( $query->posts as $post_id ) {
			$think_tanks = wp_get_post_terms( $post_id, 'think_tank' );
			if ( ! $think_tanks ) {
				continue;
			}

			$think_tank = $think_tanks[0];
			$amount     = get_post_meta( $post_id, 'amount_calc', true );

			$data[] = array(
				'think_tank'      => $think_tank->name,
				'think_tank_slug' => $think_tank->slug,
				'total_amount'    => (int) $amount,
				'year'            => implode( ',', wp_get_post_terms( $post_id, 'donation_year', array( 'fields' => 'names' ) ) ),
				'type'            => implode( ',', wp_get_post_terms( $post_id, 'donor_type', array( 'fields' => 'names' ) ) ),
			);
		}

		usort(
			$data,
			function ( $a, $b ) {
				return ( strcmp( $a['think_tank'], $b['think_tank'] ) );
			}
		);

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
		$this->set_args(
			array(
				'think_tank'    => $think_tank,
				'donation_year' => $donation_year,
				'donor_type'    => $donor_type,
			)
		);

		$query = $this->generate_query();

		if ( empty( $query->posts ) ) {
			return array();
		}

		$data = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$donors = wp_get_post_terms( $post_id, 'donor', array( 'orderby' => 'parent' ) );

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
					'donor'           => $donor_name,
					'amount_calc'     => $amount_calc,
					'donor_type'      => get_the_term_list( $post_id, 'donor_type' ),
					'donor_link'      => get_term_link( $donor_slugs[0], 'donor' ),
					'donor_slug'      => $donor_slug,
					'disclosed'       => get_post_meta( $post_id, 'disclosed', true ),
					'source'          => get_post_meta( $post_id, 'source', true ),
					'think_tank_slug' => $think_tank,
				);
			}
		}

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
		$this->set_args(
			array(
				'donor'         => $donor,
				'donation_year' => $donation_year,
				'donor_type'    => $donor_type,
			)
		);

		$query = $this->generate_query();

		if ( empty( $query->posts ) ) {
			return array();
		}

		$data = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$think_tanks = get_the_terms( $post_id, 'think_tank' );

				if ( ! $think_tanks ) {
					continue;
				}

				$think_tank      = $think_tanks[0];
				$think_tank_slug = $think_tank->slug;
				$source          = get_post_meta( $post_id, 'source', true );
				$disclosed       = strtolower( get_post_meta( $post_id, 'disclosed', true ) );

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
					'disclosed'       => $disclosed,
					'think_tank_slug' => $think_tank_slug,
					'transaction_id'  => $post_id,
				);
			}
			wp_reset_postdata();
		}

		return $data;
	}

	/**
	 * Retrieve donor data, optionally filtered by donation year, donor type, or search query.
	 *
	 * - For "All Donors" (no search), this function aggregates amounts by top-level donors.
	 * - For "Search," it delegates to `get_donor_search_raw_data` to handle term-specific matches.
	 *
	 * @param string $donation_year The slug of the donation year to filter transactions by (optional).
	 * @param string $donor_type    The slug of the donor type to filter transactions by (optional).
	 * @param string $search        A search keyword to match donor terms (optional).
	 * @return array The donor data, structured as an associative array keyed by donor slug.
	 */
	public function get_donor_archive_raw_data( $donation_year = '', $donor_type = '', $search = '' ): array {
		if ( ! empty( $search ) ) {
			return $this->get_donor_search_raw_data( $search );
			// return $this->get_parent_donor_search_raw_data( $search );
		}

		$this->set_args(
			array(
				'donation_year' => $donation_year,
				'donor_type'    => $donor_type,
			)
		);

		$query = $this->generate_query();

		if ( empty( $query->posts ) ) {
			return array();
		}

		$data = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {

				/**
				 * Limit to "top level" donors
				 */
				$tax_args = array(
					'orderby' => 'term_id',
					'parent'  => 0,
				);

				$donors = wp_get_object_terms( $post_id, 'donor', $tax_args );
				if ( empty( $donors ) || is_wp_error( $donors ) ) {
					continue;
				}

				$donor_names = wp_list_pluck( $donors, 'name' );
				$donor_slugs = wp_list_pluck( $donors, 'slug' );
				$donor_name  = implode( ' > ', $donor_names );
				$donor_slug  = is_array( $donor_slugs ) ? $donor_slugs[0] : $donor_slugs;

				$amount        = intval( get_post_meta( $post_id, 'amount_calc', true ) );
				$disclosed     = strtolower( get_post_meta( $post_id, 'disclosed', true ) );
				$donor_post_id = get_post_from_term( $donor_slug, 'donor' ) ?? $post_id;

				if ( ! isset( $data[ $donor_slug ] ) ) {
					$data[ $donor_slug ] = array(
						'donor'       => $donor_name,
						'amount_calc' => 0,
						'disclosed'   => array(),
						'donor_type'  => get_the_term_list( $donor_post_id, 'donor_type' ),
						'donor_slug'  => $donor_slug,
						'donor_link'  => get_term_link( $donor_slug, 'donor' ),
						'year'        => get_the_term_list( $post_id, 'donation_year' ),
					);
				}

				$data[ $donor_slug ]['amount_calc'] += $amount;
				$data[ $donor_slug ]['disclosed'][]  = $disclosed;
			}

			wp_reset_postdata();
		}

		return apply_filters( 'donor_archive_raw_data', $data, $donation_year, $donor_type );
	}

	/**
	 * Retrieve donor data for search query.
	 * This function is used to show all donors that match the search term.
	 *
	 * @param string $donation_year The donation year to filter by.
	 * @param string $donor_type    The donor type to filter by.
	 * @param string $search        The search term.
	 * @return array An array of donor data.
	 */
	public function get_donor_search_raw_data( $search = '' ): array {
		if ( empty( $search ) ) {
			return array();
		}

		$taxonomy = 'donor';
		$terms    = $this->get_search_term_ids( $search, $taxonomy );
		$args     = array(
			'search' => $search,
		);
		if ( ! empty( $terms ) ) {
			$args['terms']    = $terms;
			$args['taxonomy'] = $taxonomy;
		}

		$this->set_args( $args );

		$query = $this->generate_query();

		if ( empty( $query->posts ) ) {
			return array();
		}

		$data = array();

		$parent_contributions = array();
		$child_terms          = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {

				$donors = wp_get_object_terms( $post_id, $taxonomy, array( 'orderby' => 'term_id' ) );
				if ( empty( $donors ) || is_wp_error( $donors ) ) {
					continue;
				}

				foreach ( $donors as $donor ) {
					$donor_name  = $donor->name;
					$donor_slug  = $donor->slug;
					$parent_term = get_term( $donor->parent, $taxonomy );

					$donor_post_id = get_post_from_term( $donor_slug, 'donor' ) ?? $post_id;

					$is_parent = ( $donor->parent === 0 );

					// Track child terms and their parent relationships.
					if ( $parent_term && ! is_wp_error( $parent_term ) ) {
						$parent_slug                 = $parent_term->slug;
						$child_terms[ $parent_slug ] = true; // Mark parent as having child terms.
						$donor_name                  = $parent_term->name . ' > ' . $donor_name;
					} else {
						$parent_slug = $donor_slug;
					}

					if ( ! isset( $data[ $donor_slug ] ) ) {
						$data[ $donor_slug ] = array(
							'donor'       => $donor_name,
							'amount_calc' => 0,
							'disclosed'   => array(),
							'donor_slug'  => $donor_slug,
							'donor_type'  => get_the_term_list( $donor_post_id, 'donor_type' ),
							'donor_link'  => get_term_link( $parent_slug, 'donor' ),
							'year'        => get_the_term_list( $post_id, 'donation_year' ),
						);
					}

					$amount    = intval( get_post_meta( $post_id, 'amount_calc', true ) );
					$disclosed = strtolower( get_post_meta( $post_id, 'disclosed', true ) );

					$data[ $donor_slug ]['amount_calc'] += $amount;
					$data[ $donor_slug ]['disclosed'][]  = $disclosed;

					// Track contributions to the parent.
					if ( $is_parent && ! isset( $parent_contributions[ $parent_slug ] ) ) {
						$parent_contributions[ $parent_slug ] = array(
							'amount_calc' => 0,
						);
					}
					if ( isset( $parent_contributions[ $parent_slug ] ) ) {
						$parent_contributions[ $parent_slug ]['amount_calc'] += $amount;
					}
				}
			}

			wp_reset_postdata();
		}

		// Remove parent donors that have child terms unless they are direct contributors.
		foreach ( $parent_contributions as $parent_slug => $parent_data ) {
			if ( isset( $child_terms[ $parent_slug ] ) && isset( $data[ $parent_slug ] ) ) {
				unset( $data[ $parent_slug ] );
			}
		}

		return apply_filters( 'donor_search_raw_data', $data, $donation_year, $donor_type );
	}

	/**
	 * Search for donor posts and retrieve parent donor data.
	 * This function is used to show top-level donors in the search results.
	 *
	 * @param string $donation_year The donation year to filter by (not used here).
	 * @param string $donor_type    The donor type to filter by (not used here).
	 * @param string $search        The search term.
	 * @return array An array of parent donor data.
	 */
	public function get_parent_donor_search_raw_data( $search = '' ): array {
		if ( empty( $search ) ) {
			return array();
		}

		$taxonomy = 'donor';
		$terms    = $this->get_search_term_ids( $search, $taxonomy );
		$args     = array(
			'search' => $search,
		);
		if ( ! empty( $terms ) ) {
			$args['terms']    = $terms;
			$args['taxonomy'] = $taxonomy;
		}

		$this->set_args( $args );

		$query = $this->generate_query();

		if ( empty( $query->posts ) ) {
			return array();
		}

		$data = array();

		if ( $query->have_posts() ) {
			foreach ( $query->posts as $post_id ) {
				$post = get_post( $post_id );

				if ( $post->post_parent ) {
					$post_id = $post->post_parent;
					$post    = get_post( $post_id );
				}

				$taxonomy    = 'donor';
				$donor_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );

				if ( empty( $donor_terms ) || is_wp_error( $donor_terms ) ) {
					continue;
				}

				$valid_terms = array_intersect( $donor_terms, $transaction_terms );
				if ( empty( $valid_terms ) ) {
					continue;
				}

				$amount_calc = intval( get_post_meta( $post_id, 'amount_calc', true ) );
				$disclosed   = strtolower( get_post_meta( $post_id, 'disclosed', true ) );
				$post_slug   = $post->post_name;

				$data[ $post_slug ] = array(
					'donor'       => $post->post_title,
					'amount_calc' => $amount_calc,
					'disclosed'   => array( $disclosed ),
					'donor_type'  => get_the_term_list( $donor_post_id, 'donor_type' ),
					'donor_slug'  => $post_slug,
					'donor_link'  => get_permalink( $post_id ),
					'year'        => get_the_term_list( $post_id, 'donation_year' ),
				);
			}

			wp_reset_postdata();
		}

		return apply_filters( 'parent_donor_search_raw_data', array_values( $data ), $search );
	}

	/**
	 * Retrieve parent terms from search.
	 *
	 * @param string $search   The search term.
	 * @param string $taxonomy The taxonomy to search in.
	 * @return array An array of parent terms.
	 */
	private function get_parent_terms_from_search( string $search, string $taxonomy ): array {
		$term_ids = $this->get_search_term_ids( $search, $taxonomy );

		if ( empty( $term_ids ) ) {
			return array();
		}

		$parent_terms = array();

		foreach ( $term_ids as $term_id ) {
			$term = get_term( $term_id, $taxonomy );
			if ( is_wp_error( $term ) || ! $term ) {
				continue;
			}

			if ( $term->parent === 0 ) {
				// Keep parent terms directly.
				$parent_terms[ $term->term_id ] = $term;
			} else {
				// If child, find and add the parent term.
				$parent_term = get_term( $term->parent, $taxonomy );
				if ( $parent_term && ! is_wp_error( $parent_term ) ) {
					$parent_terms[ $parent_term->term_id ] = $parent_term;
				}
			}
		}

		return $parent_terms;
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
		$data          = get_transient( $transient_key );

		if ( false !== $data ) {
			return $data;
		}

		// Get all donor types in the correct order.
		$donor_types = get_terms(
			array(
				'taxonomy'   => 'donor_type',
				'orderby'    => 'term_id',
				'order'      => 'ASC',
				'hide_empty' => false,
			)
		);

		// Initialize donor types with default values in the correct order.
		$default_donor_types = array();
		foreach ( $donor_types as $term ) {
			$default_donor_types[ $term->name ] = 0;
		}

		// Query think tanks.
		$think_tank_args = array(
			'post_type'      => 'think_tank',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);

		if ( ! empty( $search ) ) {
			$think_tank_args['s']              = $search;
			$think_tank_args['search_columns'] = array( 'post_title' );
		}

		$think_tank_query = new \WP_Query( $think_tank_args );
		$data             = array();

		if ( $think_tank_query->have_posts() ) {
			foreach ( $think_tank_query->posts as $think_tank_id ) {
				$slug          = get_post_field( 'post_name', $think_tank_id );
				$data[ $slug ] = array(
					'think_tank'           => get_the_title( $think_tank_id ),
					'donor_types'          => $default_donor_types, // Use ordered donor types.
					'transparency_score'   => get_transparency_score_from_slug( $slug ),
					'no_defense_accepted'  => get_post_meta( $think_tank_id, 'no_defense_accepted', true ),
					'no_domestic_accepted' => get_post_meta( $think_tank_id, 'no_domestic_accepted', true ),
					'no_foreign_accepted'  => get_post_meta( $think_tank_id, 'no_foreign_accepted', true ),
					'limited_info'         => get_post_meta( $think_tank_id, 'limited_info', true ),
					'disclosed'            => array(),
				);

				// Query transactions associated with this think tank.
				$transaction_args = array(
					'post_type'      => 'transaction',
					'posts_per_page' => -1,
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'tax_query'      => array(
						array(
							'taxonomy' => 'think_tank',
							'field'    => 'slug',
							'terms'    => $slug,
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

				if ( $transaction_query->have_posts() ) {
					foreach ( $transaction_query->posts as $transaction_id ) {
						foreach ( wp_get_post_terms( $transaction_id, 'donor_type' ) as $donor_type_term ) {
							$donor_type = $donor_type_term->name;

							$amount_calc                                  = floatval( get_post_meta( $transaction_id, 'amount_calc', true ) );
							$data[ $slug ]['donor_types'][ $donor_type ] += $amount_calc;

							$disclosed_value                             = strtolower( get_post_meta( $transaction_id, 'disclosed', true ) );
							$data[ $slug ]['disclosed'][ $donor_type ][] = $disclosed_value;
						}
					}
				}
				wp_reset_postdata();
			}
		}
		wp_reset_postdata();

		// Normalize disclosed values.
		foreach ( $data as &$think_tank_data ) {
			foreach ( $think_tank_data['disclosed'] as $donor_type => $disclosed_values ) {
				$disclosed_values                            = array_unique( $disclosed_values );
				$think_tank_data['disclosed'][ $donor_type ] =
					( count( $disclosed_values ) === 1 && $disclosed_values[0] === 'no' ) ? 'no' : 'yes';
			}

			// Ensure donor types remain in the correct order.
			$think_tank_data['donor_types'] = array_merge( $default_donor_types, $think_tank_data['donor_types'] );
		}

		ksort( $data );

		// Cache the result.
		set_transient( $transient_key, $data, 12 * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Retrieves donor archive data with aggregated amounts.
	 *
	 * @param string $donation_year The donation year.
	 * @param string $donor_type    The donor type.
	 * @param string $search        Search term.
	 *
	 * @return array The aggregated donor archive data.
	 */
	public function get_donor_archive_data( string $donation_year = '', string $donor_type = '', string $search = '' ): array {
		$raw_data = $this->get_donor_archive_raw_data( $donation_year, $donor_type, $search );

		$data = array_reduce(
			$raw_data,
			function( $carry, $item ) {
				$slug        = $item['donor_slug'];
				$amount_calc = $item['amount_calc'];
				$year        = $item['year'];

				if ( ! isset( $carry[ $slug ] ) ) {
					$carry[ $slug ] = array(
						'donor'       => $item['donor'],
						'amount_calc' => $amount_calc,
						'donor_type'  => $item['donor_type'],
						'donor_slug'  => $slug,
						'donor_link'  => $item['donor_link'],
						'year'        => $year,
						'disclosed'   => $item['disclosed'],
					);
				} else {
					$carry[ $slug ]['amount_calc'] += $amount_calc;

					// Aggregate disclosed values
					$carry[ $slug ]['disclosed'] = array_merge(
						$carry[ $slug ]['disclosed'],
						$item['disclosed']
					);

					// Handle year aggregation
					$years = explode( ', ', $carry[ $slug ]['year'] );
					if ( ! in_array( $year, $years ) ) {
						$years[]                = $year;
						$carry[ $slug ]['year'] = implode( ', ', $years );
					}
				}
				return $carry;
			},
			array()
		);

		// Normalize disclosed values for each donor.
		foreach ( $data as &$donor_data ) {
			$disclosed_values        = array_unique( $donor_data['disclosed'] );
			$donor_data['disclosed'] = ( count( $disclosed_values ) === 1 && $disclosed_values[0] === 'no' ) ? 'no' : 'yes';
		}

		ksort( $data );
		return $data;
	}

	/**
	 * Retrieves single think tank data with aggregated amounts.
	 *
	 * @param string $think_tank    The think tank slug.
	 * @param string $donation_year The donation year.
	 * @param string $donor_type    The donor type.
	 *
	 * @return array The aggregated think tank data.
	 */
	public function get_single_think_tank_data( string $think_tank = '', string $donation_year = '', string $donor_type = '' ): array {
		$raw_data = $this->get_single_think_tank_raw_data( $think_tank, $donation_year, $donor_type );

		$data = array_reduce(
			$raw_data,
			function( $carry, $item ) {
				$slug = $item['donor_slug'];

				if ( ! isset( $carry[ $slug ] ) ) {
					$carry[ $slug ] = array(
						'donor'         => $item['donor'],
						'amount_calc'   => 0,
						'donor_type'    => $item['donor_type'],
						'donor_slug'    => $slug,
						'donor_link'    => $item['donor_link'],
						'source'        => $item['source'],
						'disclosed'     => array(), // Collect disclosed values for reference.
						'think_tank'    => $item['think_tank_slug'],
						'think_tank_id' => get_post_id_by_slug( $item['think_tank_slug'] ),
					);
				}

				$carry[ $slug ]['amount_calc'] += $item['amount_calc'];
				$carry[ $slug ]['disclosed'][]  = strtolower( $item['disclosed'] );

				return $carry;
			},
			array()
		);

		// Normalize disclosed values for each donor.
		foreach ( $data as &$donor_data ) {
			// $donor_data['disclosed'] = array_unique( $donor_data['disclosed'] );
			$donor_data['disclosed'] = ( count( array_unique( $donor_data['disclosed'] ) ) === 1 && $donor_data['disclosed'][0] === 'no' ) ? 'no' : 'yes';
		}

		ksort( $data );
		return $data;
	}

	/**
	 * Retrieves single donor data with aggregated amounts.
	 *
	 * @param string $donor        The donor slug.
	 * @param string $donation_year The donation year.
	 * @param string $donor_type    The donor type.
	 *
	 * @return array The aggregated donor data.
	 */
	public function get_single_donor_data( string $donor = '', string $donation_year = '', string $donor_type = '' ): array {
		$raw_data = $this->get_single_donor_raw_data( $donor, $donation_year, $donor_type );

		$data = array();
		foreach ( $raw_data as $item ) {
			// Use think_tank_slug and donor as the unique key.
			$key = $item['think_tank_slug'] . '|' . $item['donor'];

			if ( ! isset( $data[ $key ] ) ) {
				$data[ $key ] = array(
					'think_tank'      => $item['think_tank'],
					'think_tank_slug' => $item['think_tank_slug'],
					'donor'           => $item['donor'],
					'donor_parent'    => $item['parent_donor'] ?? '',
					'slug'            => $donor,
					'amount_calc'     => $item['amount_calc'],
					'donor_type'      => $item['donor_type'],
					'source'          => $item['source'],
					'disclosed'       => strtolower( $item['disclosed'] ),
				);
			} else {
				$data[ $key ]['amount_calc'] += $item['amount_calc'];

				if ( $data[ $key ]['disclosed'] !== 'yes' && strtolower( $item['disclosed'] ) === 'yes' ) {
					$data[ $key ]['disclosed'] = 'yes';
				}
			}
		}

		$return_data = array_values( $data );

		return $return_data;
	}

	/**
	 * Fetch transactions by taxonomy terms and return post IDs.
	 *
	 * @param string $think_tank The slug of the think_tank taxonomy term.
	 * @param string $donor_type The slug of the donor_type taxonomy term.
	 * @return array Array of post IDs.
	 */
	public static function get_think_tank_post_ids( string $think_tank = '', string $donation_year = '', string $donor_type = '' ): array {
		$this->set_args(
			array(
				'think_tank'    => $think_tank,
				'donation_year' => $donation_year,
				'donor_type'    => $donor_type,
			)
		);

		$query = $this->generate_query();

		return $query->have_posts() ? $query->posts : array();
	}

	/**
	 * Fetch transactions by taxonomy terms and return post IDs.
	 *
	 * @param string $donor The slug of the donor taxonomy term.
	 * @return array Array of post IDs.
	 */
	public static function get_donor_post_ids( string $donor = '', string $donation_year = '' ): array {
		$this->set_args(
			array(
				'donor'         => $donor,
				'donation_year' => $donation_year,
				'donor_type'    => $donor_type,
			)
		);

		$query = $this->generate_query();

		return $query->have_posts() ? $query->posts : array();
	}

	/**
	 * Get the total amount for a single think tank, excluding undisclosed amounts.
	 *
	 * @param string $think_tank    The think tank slug or identifier.
	 * @param string $donation_year The donation year.
	 * @param string $donor_type    The donor type.
	 * @return int The total calculated amount.
	 */
	public static function get_single_think_tank_total( string $think_tank = '', string $donation_year = '', string $donor_type = '' ) {
		$raw_data = ( new self() )->get_single_think_tank_raw_data( $think_tank, $donation_year, $donor_type );

		$total = 0;
		foreach ( $raw_data as $item ) {
			$total += $item['amount_calc'];
		}

		return $total;
	}

	/**
	 * Get the total amount for a single donor, excluding undisclosed amounts.
	 *
	 * @param string $donor         The donor slug or identifier.
	 * @param string $donation_year The donation year.
	 * @param string $donor_type    The donor type.
	 * @return int The total calculated amount.
	 */
	public static function get_single_donor_total( string $donor = '', string $donation_year = '', string $donor_type = '' ) {
		$raw_data = ( new self() )->get_single_donor_raw_data( $donor, $donation_year, $donor_type );

		$total = 0;
		foreach ( $raw_data as $item ) {
			$total += $item['amount_calc'];
		}

		return $total;
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
	public function get_search_term_ids( string $search, string $taxonomy ): array {
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

	/**
	 * Check if all transactions for the given post IDs are undisclosed.
	 *
	 * @param array $post_ids Array of transaction post IDs.
	 * @return bool True if all transactions are undisclosed, false otherwise.
	 */
	public static function is_undisclosed( array $post_ids ): bool {
		$meta_values = ( new self() )->get_meta_values_for_records( $post_ids, 'disclosed' );

		$all_undisclosed = array_filter(
			$meta_values,
			function ( $disclosed ) {
				return strtolower( $disclosed ) !== 'no';
			}
		);

		return empty( $all_undisclosed );
	}

	/**
	 * Get the sum of `amount_calc` for a given array of post IDs.
	 *
	 * @param array $post_ids Array of post IDs.
	 * @return int The summed value of `amount_calc`.
	 */
	public static function get_total( array $post_ids ): int {
		if ( empty( $post_ids ) ) {
			return 0;
		}

		$total_amount = 0;

		foreach ( $post_ids as $post_id ) {
			$amount_calc   = (int) get_post_meta( $post_id, 'amount_calc', true );
			$total_amount += $amount_calc;
		}

		return $total_amount;
	}

	/**
	 * Get the total `amount_calc` and check if all transactions are undisclosed by terms.
	 *
	 * @param string $think_tank The slug of the think_tank taxonomy term.
	 * @param string $donation_year The slug of the donation_year taxonomy term.
	 * @param string $donor_type The slug of the donor_type taxonomy term.
	 * @return array {
	 *     @type int  $amount_calc The summed value of `amount_calc`.
	 *     @type bool $undisclosed True if all transactions are undisclosed, false otherwise.
	 * }
	 */
	public static function get_think_tank_sums( string $think_tank = '', $donation_year = '', string $donor_type = '' ): array {
		$post_ids = self::get_think_tank_post_ids( $think_tank, $donation_year, $donor_type );

		return array(
			'amount_calc' => self::get_total( $post_ids ),
			'undisclosed' => self::is_undisclosed( $post_ids ),
		);
	}

	/**
	 * Get the total `amount_calc` and check if all transactions are undisclosed by terms.
	 *
	 * @param string $donor The slug of the donor taxonomy term.
	 * @return array {
	 *     @type int  $amount_calc The summed value of `amount_calc`.
	 *     @type bool $undisclosed True if all transactions are undisclosed, false otherwise.
	 * }
	 */
	public static function get_donor_sums( string $donor = '', string $donation_year = '' ): array {
		$post_ids = self::get_donor_post_ids( $donor, $donation_year );

		return array(
			'amount_calc' => self::get_total( $post_ids ),
			'undisclosed' => self::is_undisclosed( $post_ids ),
		);
	}

	/**
	 * Get donor terms associated with any transaction post.
	 *
	 * @return array Array of donor term slugs.
	 */
	public function get_transaction_donor_terms(): array {
		add_filter( 'terms_clauses', array( $this, 'filter_terms_clauses_for_transactions' ), 10, 3 );

		$taxonomy = 'donor';
		$args     = array(
			'taxonomy'   => $taxonomy,
			'fields'     => 'slugs',
			'hide_empty' => true,
		);

		$terms = get_terms( $args );

		remove_filter( 'terms_clauses', array( $this, 'filter_terms_clauses_for_transactions' ), 10 );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return array();
		}

		return $terms;
	}

	/**
	 * Modify terms_clauses to filter donor terms associated with transactions.
	 *
	 * @param array $clauses The terms query SQL clauses.
	 * @param array $taxonomies The taxonomies being queried.
	 * @param array $args Query arguments.
	 * @return array Modified terms query clauses.
	 */
	public function filter_terms_clauses_for_transactions( $clauses, $taxonomies, $args ): array {
		global $wpdb;

		// var_dump( $clauses, $taxonomies, $args );

		$taxonomy = 'donor';

		if ( ! in_array( $taxonomy, $taxonomies, true ) ) {
			return $clauses;
		}

		$post_type = 'transaction';

		$clauses['where'] .= "
			AND EXISTS (
				SELECT 1
				FROM {$wpdb->term_relationships} term_relationships
				JOIN {$wpdb->posts} posts
					ON term_relationships.object_id = posts.ID
				WHERE term_relationships.term_taxonomy_id = tt.term_taxonomy_id
				AND posts.post_type = '{$post_type}'
				AND posts.post_status = 'publish'
			)
		";

		// Debug the modified clauses.
		error_log( 'Modified Clauses: ' . print_r( $clauses, true ) );

		return $clauses;
	}


}
