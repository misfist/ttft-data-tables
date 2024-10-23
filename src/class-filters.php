<?php
/**
 * Class Filters handles all filter and tab generation logic.
 */
namespace Ttft\Data_Tables;

/**
 * Class Filters handles all filter and tab generation logic.
 */
class Filters {

    /**
     * Retrieve the most recent donation year term.
     *
     * @return string|false The name of the most recent donation year term, or false if none found.
     */
    public function get_most_recent_donation_year() {
        $taxonomy = 'donation_year';

        $args = array(
            'taxonomy'   => $taxonomy,
            'orderby'    => 'name',
            'order'      => 'DESC',
            'number'     => 1,
            'hide_empty' => true,
        );

        $terms = get_terms( $args );

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            return $terms[0]->name;
        }

        return false;
    }

    /**
     * Retrieves all donation_year terms associated with transaction posts for a specific donor.
     *
     * @param string $name The donor taxonomy term name.
     * @param string $type The donor taxonomy name. Default 'donor'.
     * @return array An array of donation_year term names.
     */
    public function get_years( string $name, string $type = 'donor' ): array {
        $post_type     = 'transaction';
        $year_taxonomy = 'donation_year';

        $donor_term = get_term_by( 'name', $name, $type );

        if ( ! $donor_term || is_wp_error( $donor_term ) ) {
            return array();
        }

        $tax_query = array(
            array(
                'taxonomy' => $type,
                'field'    => 'term_id',
                'terms'    => $donor_term->term_id,
            ),
        );

        $query_args = array(
            'post_type'      => $post_type,
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => $tax_query,
        );

        $post_ids = get_posts( $query_args );

        if ( empty( $post_ids ) ) {
            return array();
        }

        $term_args = array(
            'fields' => 'names',
            'order'  => 'DESC',
        );

        $terms = wp_get_object_terms( $post_ids, $year_taxonomy, $term_args );

        return ! is_wp_error( $terms ) ? array_unique( $terms ) : array();
    }

    /**
     * Generate filter markup for years.
     *
     * @param  array $years
     * @return string
     */
    public function generate_year_filters( array $years ): string {
        ob_start();

        if ( $years ) {
            wp_interactivity_state( APP_NAMESPACE, array( 'donationYear', 'all' ) );

            $all    = array( 'all' => sprintf( __( 'All <span class="mobile-only">%s</span>', 'ttft-data-tables' ), __( 'Years', 'ttft-data-tables' ) ) );
            $years  = $all + $years;
            $context = array(
                'donationYears' => array( $years )
            );
            ?>
            <div 
                class="filter-group year"
                data-wp-bind--selected="state.donationYear"
                data-wp-interactive="<?php echo APP_NAMESPACE; ?>"
                <?php echo wp_interactivity_data_wp_context( $context ); ?>
            >
                <?php foreach ( $years as $year ) : ?>
                    <input 
                        type="radio" 
                        id="filter-year-<?php echo esc_attr( $year ); ?>" 
                        name="filter-year" 
                        class="filter-checkbox" 
                        value="<?php echo esc_attr( $year ); ?>" 
                        data-query-var="donation_year=<?php echo esc_attr( $year ); ?>" 
                        data-wp-on--click="actions.updateYear"
                        data-wp-interactive-key="donationYear"
                        data-wp-interactive-value="<?php echo esc_attr( $year ); ?>"
                    />
                    <label 
                        for="filter-year-<?php echo esc_attr( $year ); ?>" 
                        class="option"
                        aria-label="<?php printf( esc_attr( 'Filter by %s', 'ttft-data-tables' ), esc_attr( wp_strip_all_tags( $year ) ) ); ?>"
                    >
                        <?php echo esc_html( $year ); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    /**
     * Generate filter markup for donor types.
     *
     * @param  array $types
     * @return string
     */
    public function generate_type_filters( array $types ): string {
        ob_start();

        if ( $types ) {
            wp_interactivity_state( APP_NAMESPACE, array( 'donorType', 'all' ) );
            $all    = array( 'all' => sprintf( __( 'All <span class="mobile-only">%s</span>', 'ttft-data-tables' ), __( 'Donor Types', 'ttft-data-tables' ) ) );
            $types  = $all + $types;
            $context = array(
                'donationTypes' => array( $types )
            );
            ?>
            <div 
                class="filter-group type"
                data-wp-interactive="<?php echo APP_NAMESPACE; ?>"
                <?php echo wp_interactivity_data_wp_context( $context ); ?>
                data-wp-bind--selected="state.donorType"
            >
                <?php foreach ( $types as $type ) : ?>
                    <input 
                        type="radio" 
                        id="filter-<?php echo esc_attr( $type->slug ); ?>" 
                        name="filter-type" 
                        class="filter-checkbox" 
                        value="<?php echo esc_attr( $type->slug ); ?>" 
                        data-query-var="donor_type=<?php echo esc_attr( $type->slug ); ?>" 
                        data-wp-on--click="actions.updateType"
                        data-wp-interactive-key="donorType"
                        data-wp-interactive-value="<?php echo esc_attr( $type->slug ); ?>"
                    />
                    <label 
                        for="filter-<?php echo esc_attr( $type->slug ); ?>" 
                        class="option"
                        aria-label="<?php printf( esc_attr( 'Filter by %s', 'ttft-data-tables' ), esc_attr( wp_strip_all_tags( $type->name ) ) ); ?>"
                    >
                        <?php echo esc_html( $type->name ); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    /**
     * Print year filters for a specific donor or term.
     *
     * @param  string  $name   The donor name.
     * @param  string  $type   The donor type.
     * @param  integer $column Column number (optional).
     * @return void
     */
    public function print_years( string $name = '', string $type = 'donor', int $column = 2 ): void {
        global $post;
        $post_id = $post->ID;
        $type    = $post->post_type;
        $name    = ( $name ) ? $name : $post->post_title;
        $years   = $this->get_years( $name, $type );

        echo $this->generate_year_filters( $years );
    }

    /**
     * Print year filters for the donation archive.
     *
     * @param  integer $column Column number (optional).
     * @return void
     */
    public function print_archive_years( int $column = 2 ): void {
        global $post;
        $post_id = $post->ID;
        $type    = $post->post_type;
        $args    = array(
            'taxonomy' => 'donation_year',
            'fields'   => 'slugs',
            'order'    => 'DESC',
        );
        $years = get_terms( $args );

        echo $this->generate_year_filters( $years );
    }

    /**
     * Print type filters for donors.
     *
     * @param  integer $column Column number (optional).
     * @return void
     */
    public function print_types( int $column = 3 ): void {
        global $post;
        $taxonomy = 'donor_type';
        $types    = get_terms(
            array(
                'taxonomy' => $taxonomy,
            )
        );

        echo $this->generate_type_filters( $types );
    }
}
