<?php
/**
 * Helper Functions
 */
namespace TTFT\Data_Tables\Data;

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
		$score = get_post_meta( $think_tank[0], 'transparency_score', true );
		wp_reset_postdata();
		return intval( $score );
	}

	return 0;
}

/**
 * Get post that matches taxonomy term
 *
 * @param  string $slug
 * @param  string $type
 * @return array $post_id
 */
function get_post_from_term( $slug, $type ) {
	$args = array(
		'post_type'      => $type,
		'posts_per_page' => 1,
		'name'           => $slug,
		'fields'         => 'ids',
	);

	return get_posts( $args );
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

	return convert_start_rating( $score );
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
function convert_start_rating( $score ): string {
	$max = 5;
	ob_start();
	?>
	<!-- wp:group {"metadata":{"name":"Transparency Stars"},"className":"stars-<?php echo $score; ?>","layout":{"type":"default"}} -->
	<div class="wp-block-group stars-<?php echo $score; ?>" aria-label="<?php echo $score; ?> stars">
		<?php
		for ( $x = 1; $x <= $score && $x <= $max; $x++ ) :
			?>
			<span class="icon material-symbols-outlined rating-star" data-filled="true">star</span>
			<?php
		endfor;

		for ( $x = ( $score + 1 ); $x <= $max; $x++ ) :
			?>
			<span class="icon material-symbols-outlined rating-star" data-filled="false">star_outline</span>
			<?php
		endfor;
		?>
	</div>
	<!-- /wp:group -->

	<?php
	$stars = ob_get_clean();
	return $stars;
}

