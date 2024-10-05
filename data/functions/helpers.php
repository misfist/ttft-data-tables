<?php
/**
 * Helper Functions
 */
namespace TTFT\Data_Tables\Data;

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
	<!-- wp:group {"metadata":{"name":"Transparency Stars"},"className":"star-group stars-<?php echo $score; ?>","layout":{"type":"default"}} -->
	<div class="wp-block-group star-group stars-<?php echo $score; ?>" aria-label="<?php echo $score; ?> stars">
		<?php
		for ( $x = 1; $x <= $score && $x <= $max; $x++ ) :
			?>
			<span class="icon material-symbols-outlined star" data-filled="true" role="img"><svg xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" height="24px" viewBox="0 0 24 24" width="24px" fill="currentColor"><g><path d="M0 0h24v24H0V0z" fill="none"/><path d="M0 0h24v24H0V0z" fill="none"/></g><g><path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21 12 17.27z"/></g></svg></span>
			<?php
		endfor;

		for ( $x = ( $score + 1 ); $x <= $max; $x++ ) :
			?>
			<span class="icon material-symbols-outlined star" data-filled="false" role="img"><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="currentColor"><path d="m354-287 126-76 126 77-33-144 111-96-146-13-58-136-58 135-146 13 111 97-33 143ZM233-120l65-281L80-590l288-25 112-265 112 265 288 25-218 189 65 281-247-149-247 149Zm247-350Z"/></svg></g></svg></span>
			<?php
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

	switch ( true ) {
		case strpos( $donor_type, 'foreign' ) !== false:
			return 'no_foreign_accepted';
		case ( strpos( $donor_type, 'pentagon' ) !== false ) || strpos( $donor_type, 'defense' ) !== false:
			return 'no_defense_accepted';
		case preg_match( $us_pattern, $donor_type ):
			return 'no_domestic_accepted';
		default:
			return '';
	}
}
