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

