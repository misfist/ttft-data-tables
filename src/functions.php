<?php
/**
 * Helper Functions
 */
namespace Ttft\Data_Tables;

/**
 * Get post that matches taxonomy term
 *
 * @param  string $slug
 * @param  string $type
 * @return array $post_id
 */
function get_post_from_term( $slug, $type ) {
	$args  = array(
		'post_type'      => $type,
		'posts_per_page' => 1,
		'name'           => $slug,
		'fields'         => 'ids',
	);
	$posts = get_posts( $args );

	return ( ! empty( $posts ) && ! is_wp_error( $posts ) ) ? $posts[0] : null;
}

/**
 * Get a post by its slug.
 *
 * @param string $slug      The post slug.
 * @param string $post_type The post type. Default is 'think_tank'.
 * @return int|null The post->ID, or null if not.
 */
function get_post_id_by_slug( $slug, $post_type = 'think_tank' ) {
	$args = array(
		'name'           => $slug,
		'post_type'      => $post_type,
		'posts_per_page' => 1,
		'fields'         => 'ids',
	);

	$posts = get_posts( $args );

	return ( ! empty( $posts ) && ! is_wp_error( $posts ) ) ? (int) $posts[0] : null;
}

/**
 * Retrieves the Transparency Score for a given think tank slug.
 *
 * @param string $think_tank_slug The think tank slug.
 * @return int The Transparency Score.
 */
function get_transparency_score_from_slug( $think_tank_slug ): int {
	$post_type = 'think_tank';
	$args      = array(
		'post_type'      => $post_type,
		'posts_per_page' => 1,
		'name'           => $think_tank_slug,
		'fields'         => 'ids',
	);

	$think_tank = get_post_from_term( $think_tank_slug, $post_type );

	if ( ! empty( $think_tank ) && ! is_wp_error( $think_tank ) ) {
		$score = get_post_meta( $think_tank, 'transparency_score', true );
		wp_reset_postdata();
		return intval( $score );
	}

	return 0;
}

/**
 * Get Post Transparency Score
 *
 * @param  integer $post_id
 * @return string
 */
function get_star_rating( $post_id = 0 ): string {
	$post_id = (int) $post_id ?? get_the_ID();
	$score   = get_post_meta( $post_id, 'transparency_score', true );
	if ( ! $score ) {
		return '';
	}

	return generate_star_rating( $score );
}

/**
 * Render Star Rating
 *
 * @param  integer $post_id
 * @return void
 */
function render_star_rating( $post_id = 0 ): void {
	echo get_star_rating( $post_id );
}

/**
 * Convert the Transparency Score to a star rating.
 *
 * @param int $score The Transparency Score.
 * @return string The star rating.
 */
function generate_star_rating( $score = 0 ): string {
	$score = (int) $score;
	$max   = 5;
	ob_start();
	?>
	
	<!-- wp:group {"metadata":{"name":"Transparency Stars"},"className":"star-group stars-<?php echo $score; ?> no-export noExport","layout":{"type":"default"}} -->
	<div class="wp-block-group star-group stars-<?php echo $score; ?> no-export noExport" aria-label="<?php echo $score; ?> stars">
		<?php
		$star_rating = '';

		for ( $i = 1; $i <= 5; $i++ ) :
			if ( $i <= $score ) :
				?>
				<span class="star filled">&#9733;</span>
				<?php
			else :
				?>
				<span class="star">&#9734;</span>
				<?php
			endif;
		endfor;
		?>
	</div>
	<!-- /wp:group -->

	<?php
	$stars = ob_get_clean();
	return $stars;
}
/**
 * Match donor type name with post meta key
 *
 * @param  string $donor_type
 * @return string
 */
function get_donation_accepted_key( $donor_type ): string {
	$donor_type = strtolower( $donor_type );
	$us_pattern = '/[uU][\.\-]?[sS]/';

	if ( $donor_type ) {
		if ( strpos( $donor_type, 'foreign' ) !== false ) {
			return 'no_foreign_accepted';
		} elseif ( ( strpos( $donor_type, 'pentagon' ) !== false ) || strpos( $donor_type, 'defense' ) !== false ) {
			return 'no_defense_accepted';
		} elseif ( preg_match( $us_pattern, $donor_type ) ) {
			return 'no_domestic_accepted';
		}
	}

	return '';
}

/**
 * Get label and class for amount display.
 *
 * @param array  $row        Data row for the think tank.
 * @param string $donor_type Donor type key.
 * @param array  $settings   Settings for default labels.
 * @return array Contains 'label' and 'class' keys.
 */
function get_label_and_class_archive_think_tank( $row, $donor_type, $settings ): array {
	$key   = get_donation_accepted_key( $donor_type );
	$label = '';
	$class = '';

	if ( ! empty( $row[ $key ] ) ) {
		$label = $settings['not_accepted'] ?? esc_attr__( 'Not Accepted', 'data-tables' );
		$class = 'not-accepted';
	} elseif ( ! empty( $row['limited_info'] ) && 0 == $row['donor_types'][ $donor_type ] ) {
		$label = $settings['no_data'] ?? esc_attr__( 'Not Available', 'data-tables' );
		$class = 'no-data';
	} elseif (
		isset( $row['disclosed'] ) &&
		is_array( $row['disclosed'] ) &&
		array_key_exists( $donor_type, $row['disclosed'] ) &&
		0 == $row['donor_types'][ $donor_type ]
	) {
		$label = $settings['unknown_amount'] ?? esc_attr__( 'Unknown Amt', 'data-tables' );
		$class = 'not-disclosed';
	}

	return array(
		'label' => $label,
		'class' => $class,
	);
}

/**
 * Get label and class for amount display.
 *
 * @param array  $row        Data row for the entity.
 * @param array  $settings   Settings for default labels.
 * @return array Contains 'label' and 'class' keys.
 */
function get_label_and_class_disclosed( $row, $settings ): array {
	$label = '';
	$class = '';

	if (
		isset( $row['disclosed'] ) &&
		'no' == $row['disclosed']
	) {
		$label = $settings['unknown_amount'] ?? esc_attr__( 'Unknown Amt', 'data-tables' );
		$class = 'not-disclosed';
	}

	return array(
		'label' => $label,
		'class' => $class,
	);
}

/**
 * Convert camelCase keys to lowercase separated by underscores.
 *
 * @param array $args The array with camelCase keys.
 * @return array The array with keys converted to lowercase separated by underscores.
 */
function convert_camel_to_snake_keys( array $args ): array {
	$converted_args = array();
	foreach ( $args as $key => $value ) {
		$new_key                    = strtolower( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $key ) );
		$converted_args[ $new_key ] = $value;
	}
	return $converted_args;
}

