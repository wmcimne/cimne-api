<?php
/**
 * Publications components for the global Publications page and Research tabs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'CIMNE_PUBLICATIONS_PER_PAGE' ) ) {
    define( 'CIMNE_PUBLICATIONS_PER_PAGE', 12 );
}

/**
 * Returns the page size. Default 12 to match News and Events pagination.
 *
 * @return int
 */
function cimne_publications_per_page() {
    return max( 1, min( 100, (int) apply_filters( 'cimne_publications_per_page', CIMNE_PUBLICATIONS_PER_PAGE ) ) );
}

/**
 * Validates a YYYY-MM-DD date.
 *
 * @param string $date Raw date.
 * @return string
 */
function cimne_publications_sanitize_date( $date ) {
    $date = trim( sanitize_text_field( (string) $date ) );

    if ( '' === $date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
        return '';
    }

    $parts = array_map( 'intval', explode( '-', $date ) );

    return checkdate( $parts[1], $parts[2], $parts[0] ) ? $date : '';
}

/**
 * Sanitizes publication filters.
 *
 * @param array $filters Raw filters.
 * @return array
 */
function cimne_publications_normalize_filters( array $filters ) {
    $search  = isset( $filters['search'] ) ? trim( sanitize_text_field( (string) $filters['search'] ) ) : '';
    $cluster = isset( $filters['cluster'] ) ? preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $filters['cluster'] ) : '';
    $subtype = isset( $filters['subtype'] ) ? strtolower( preg_replace( '/[^A-Za-z0-9]/', '', (string) $filters['subtype'] ) ) : '';

    if ( ! in_array( $subtype, array( '', 'ar', 'ed', 're' ), true ) ) {
        $subtype = '';
    }

    return array(
        'search'  => function_exists( 'mb_substr' ) ? mb_substr( $search, 0, 200 ) : substr( $search, 0, 200 ),
        'cluster' => $cluster,
        'subtype' => $subtype,
        'from'    => cimne_publications_sanitize_date( $filters['from'] ?? '' ),
        'to'      => cimne_publications_sanitize_date( $filters['to'] ?? '' ),
    );
}

/**
 * Gets the Cluster options for the global Publications filter.
 *
 * @return array|WP_Error
 */
function cimne_publications_get_cluster_options() {
    return cimne_api_get(
        'research_units',
        array( 'tipo' => 'RC' ),
        array(
            'cache_variant' => 'publication-cluster-options-v1',
            'transform'     => 'cimne_publications_normalize_cluster_options',
        )
    );
}

/**
 * Resolves a Research Cluster or Research Group to the fnet IDs required by
 * publicaciones?auth_org_id=...
 *
 * Cluster: personnel from child units, matching the approved Cluster Staff rule.
 * Group: personnel from the exact group.
 *
 * @param string $unit_code Research-unit code.
 * @return array|WP_Error
 */
function cimne_publications_get_unit_author_ids( $unit_code ) {
    $unit_code = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $unit_code );

    if ( '' === $unit_code ) {
        return array();
    }

    if ( ! function_exists( 'cimne_research_get_unit_by_code' ) || ! function_exists( 'cimne_research_unit_is_cluster' ) ) {
        return new WP_Error( 'cimne_publications_research_helpers_missing', 'Research helpers are not available.' );
    }

    $unit = cimne_research_get_unit_by_code( $unit_code );

    if ( is_wp_error( $unit ) ) {
        return $unit;
    }

    $query = cimne_research_unit_is_cluster( $unit )
        ? array( 'parent' => $unit_code, 'view' => 'complete' )
        : array( 'codigo' => $unit_code, 'view' => 'complete' );

    return cimne_api_get(
        'research_units',
        $query,
        array(
            'cache_variant' => 'publication-author-ids-v1',
            'transform'     => 'cimne_publications_normalize_author_ids',
        )
    );
}

/**
 * Builds the external API query for one page.
 *
 * @param array  $filters   Normalized filters.
 * @param int    $page      One-based page.
 * @param string $unit_code Optional Research-page context unit.
 * @return array|WP_Error
 */
function cimne_publications_build_api_args( array $filters, $page = 1, $unit_code = '' ) {
    $filters  = cimne_publications_normalize_filters( $filters );
    $page     = max( 1, (int) $page );
    $per_page = cimne_publications_per_page();
    $args     = array(
        'limit'  => $per_page,
        'offset' => ( $page - 1 ) * $per_page,
    );

    if ( '' !== $filters['search'] ) {
        /*
         * bib_html contains the full bibliographic citation, including author
         * names and the publication title. Using this single upstream filter
         * therefore provides the intended "title OR bibliography" search
         * without combining two independent API filters.
         */
        $args['bib_html_text'] = $filters['search'];
    }

    if ( '' !== $filters['subtype'] ) {
        $args['subtype'] = $filters['subtype'];
    }

    if ( '' !== $filters['from'] ) {
        $args['date_from'] = $filters['from'];
    }

    if ( '' !== $filters['to'] ) {
        $args['date_to'] = $filters['to'];
    }

    $author_unit = '' !== $unit_code ? $unit_code : $filters['cluster'];

    if ( '' !== $author_unit ) {
        $author_ids = cimne_publications_get_unit_author_ids( $author_unit );

        if ( is_wp_error( $author_ids ) ) {
            return $author_ids;
        }

        if ( empty( $author_ids ) ) {
            return new WP_Error( 'cimne_publications_no_unit_authors', 'No authors were found for this research unit.' );
        }

        $args['auth_org_id'] = $author_ids;
    }

    return $args;
}

/**
 * Returns one reduced Publications API page.
 *
 * @param array  $filters   Filters.
 * @param int    $page      One-based page.
 * @param string $unit_code Optional Research unit context.
 * @return array|WP_Error
 */
function cimne_publications_get_page( array $filters = array(), $page = 1, $unit_code = '' ) {
    $page = max( 1, (int) $page );
    $args = cimne_publications_build_api_args( $filters, $page, $unit_code );

    if ( is_wp_error( $args ) ) {
        if ( 'cimne_publications_no_unit_authors' === $args->get_error_code() ) {
            return array(
                'items' => array(),
                'meta'  => array(
                    'limit'    => cimne_publications_per_page(),
                    'offset'   => ( $page - 1 ) * cimne_publications_per_page(),
                    'returned' => 0,
                    'total'    => 0,
                    'pages'    => 0,
                ),
            );
        }

        return $args;
    }

    return cimne_api_get(
        'publications',
        $args,
        array(
            'cache_variant' => 'publication-list-v2',
            'transform'     => 'cimne_publications_normalize_payload',
        )
    );
}

/**
 * Renders the publication list items.
 *
 * @param array $items Normalized items.
 * @return string
 */
function cimne_publications_render_items( array $items ) {
    ob_start();
    get_template_part( 'template-parts/publications/publication-list', null, array( 'items' => $items ) );
    return (string) ob_get_clean();
}

/**
 * Renders pagination using the same global CSS classes as News and Events.
 *
 * @param int $current Current page.
 * @param int $pages   Total pages.
 * @return string
 */
function cimne_publications_render_pagination( $current, $pages ) {
    $current = max( 1, (int) $current );
    $pages   = max( 0, (int) $pages );

    if ( $pages <= 1 ) {
        return '';
    }

    $numbers = array( 1, $pages );

    for ( $i = max( 1, $current - 2 ); $i <= min( $pages, $current + 2 ); $i++ ) {
        $numbers[] = $i;
    }

    $numbers = array_values( array_unique( $numbers ) );
    sort( $numbers, SORT_NUMERIC );

    ob_start();
    ?>
    <div class="cimne-ajax-pagination cimne-publications-pagination">
        <nav class="cimne-ajax-pagination-nav cimne-publications-pagination-nav" aria-label="<?php echo esc_attr__( 'Publications pagination', 'cimne' ); ?>">
            <?php if ( $current > 1 ) : ?>
                <a href="#" class="page-numbers pagination-arrow pagination-prev" data-page="<?php echo esc_attr( $current - 1 ); ?>" aria-label="<?php echo esc_attr__( 'Previous page', 'cimne' ); ?>"></a>
            <?php endif; ?>

            <?php
            $previous = 0;
            foreach ( $numbers as $number ) :
                if ( $previous && $number > $previous + 1 ) :
                    ?>
                    <span class="page-numbers dots" aria-hidden="true">…</span>
                    <?php
                endif;

                if ( $number === $current ) :
                    ?>
                    <span class="page-numbers current" aria-current="page"><?php echo esc_html( $number ); ?></span>
                    <?php
                else :
                    ?>
                    <a href="#" class="page-numbers" data-page="<?php echo esc_attr( $number ); ?>"><?php echo esc_html( $number ); ?></a>
                    <?php
                endif;

                $previous = $number;
            endforeach;
            ?>

            <?php if ( $current < $pages ) : ?>
                <a href="#" class="page-numbers pagination-arrow pagination-next" data-page="<?php echo esc_attr( $current + 1 ); ?>" aria-label="<?php echo esc_attr__( 'Next page', 'cimne' ); ?>"></a>
            <?php endif; ?>
        </nav>
    </div>
    <?php
    return (string) ob_get_clean();
}

/**
 * Builds the SSR context for the global Publications page.
 *
 * @return array|WP_Error
 */
function cimne_publications_get_global_context() {
    $payload = cimne_publications_get_page( array(), 1 );

    if ( is_wp_error( $payload ) ) {
        return $payload;
    }

    $items = isset( $payload['items'] ) && is_array( $payload['items'] ) ? $payload['items'] : array();
    $meta  = isset( $payload['meta'] ) && is_array( $payload['meta'] ) ? $payload['meta'] : array();

    return array(
        'context'         => 'global',
        'unit_code'       => '',
        'initial_html'    => cimne_publications_render_items( $items ),
        'pagination_html' => cimne_publications_render_pagination( 1, (int) ( $meta['pages'] ?? 0 ) ),
        'total'           => (int) ( $meta['total'] ?? count( $items ) ),
    );
}

/**
 * Builds the SSR context for Cluster/Research Group Publications tabs.
 *
 * @param string $unit_code Research unit code.
 * @return array|WP_Error
 */
function cimne_publications_get_research_context( $unit_code ) {
    $payload = cimne_publications_get_page( array(), 1, $unit_code );

    if ( is_wp_error( $payload ) ) {
        return $payload;
    }

    $items = isset( $payload['items'] ) && is_array( $payload['items'] ) ? $payload['items'] : array();
    $meta  = isset( $payload['meta'] ) && is_array( $payload['meta'] ) ? $payload['meta'] : array();

    return array(
        'context'         => 'research',
        'unit_code'       => $unit_code,
        'initial_html'    => cimne_publications_render_items( $items ),
        'pagination_html' => cimne_publications_render_pagination( 1, (int) ( $meta['pages'] ?? 0 ) ),
        'total'           => (int) ( $meta['total'] ?? count( $items ) ),
    );
}

/**
 * Renders the Publications shell.
 *
 * @param array $context Context.
 * @return string
 */
function cimne_publications_render( array $context ) {
    ob_start();
    get_template_part( 'template-parts/publications/publications', null, $context );
    return (string) ob_get_clean();
}

/**
 * Global Publications results shortcode.
 *
 * @return string
 */
function cimne_publications_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $context = cimne_publications_get_global_context();

    if ( is_wp_error( $context ) ) {
        cimne_api_log( 'Publications component could not be rendered.', array( 'error_code' => $context->get_error_code() ) );
        return '<p class="no-publications">' . esc_html__( 'Unable to load publications.', 'cimne' ) . '</p>';
    }

    return cimne_publications_render( $context );
}
add_shortcode( 'cimne_publications', 'cimne_publications_shortcode' );

/**
 * Cluster/Research Group Publications shortcode.
 *
 * @return string
 */
function cimne_research_publications_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    if ( ! function_exists( 'cimne_research_get_unit_code' ) ) {
        return '';
    }

    $unit_code = cimne_research_get_unit_code();

    if ( '' === $unit_code ) {
        return '';
    }

    $context = cimne_publications_get_research_context( $unit_code );

    if ( is_wp_error( $context ) ) {
        cimne_api_log(
            'Research Publications component could not be rendered.',
            array(
                'unit_code'  => $unit_code,
                'error_code' => $context->get_error_code(),
            )
        );
        return '<p class="no-publications">' . esc_html__( 'Unable to load publications.', 'cimne' ) . '</p>';
    }

    return cimne_publications_render( $context );
}
add_shortcode( 'cimne_research_publications', 'cimne_research_publications_shortcode' );
