<?php
/**
 * Fellowships component for /about/awards/.
 *
 * Shortcode: [cimne_fellowships]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Maps the UI status to the same API state codes used by Projects.
 *
 * @param string $status ongoing|finished.
 * @return string
 */
function cimne_fellowships_status_codes( $status ) {
    return 'finished' === $status ? 'C,F' : 'A';
}

/**
 * Formats a fellowship date like the approved Projects design.
 *
 * @param mixed $date API date.
 * @return string
 */
function cimne_fellowships_format_date( $date ) {
    if ( ! is_scalar( $date ) || '' === trim( (string) $date ) ) {
        return '';
    }

    $date      = trim( (string) $date );
    $timestamp = strtotime( $date );

    return false === $timestamp ? $date : wp_date( 'd-m-Y', $timestamp );
}

/**
 * Normalizes server-side list filters.
 * Search and Beneficiary are intentionally browser-side, just like Search/PI
 * on the Projects page.
 *
 * @param array $filters Raw filters.
 * @return array
 */
function cimne_fellowships_normalize_list_filters( array $filters ) {
    return array(
        'category' => isset( $filters['category'] ) ? trim( sanitize_text_field( (string) $filters['category'] ) ) : '',
        'unit'     => isset( $filters['unit'] ) ? trim( sanitize_text_field( (string) $filters['unit'] ) ) : '',
    );
}

/**
 * Applies filters supported natively by /becas.
 * The WS is documented by the project as behaving like /projects.
 *
 * @param array $args    Existing API args.
 * @param array $filters Normalized filters.
 * @return array
 */
function cimne_fellowships_apply_server_filters( array $args, array $filters ) {
    $filters = cimne_fellowships_normalize_list_filters( $filters );

    if ( '' !== $filters['category'] ) {
        $args['naturaleza_proyecto_descripcion'] = $filters['category'];
    }

    if ( '' !== $filters['unit'] ) {
        $args['IU'] = $filters['unit'];
    }

    return $args;
}

/**
 * Gets one reduced fellowship list.
 *
 * @param string $status  ongoing|finished.
 * @param array  $filters Category/unit filters.
 * @return array|WP_Error
 */
function cimne_fellowships_get_list( $status = 'ongoing', array $filters = array() ) {
    $status = 'finished' === $status ? 'finished' : 'ongoing';
    $args   = array(
        'estado_actual_codigo' => cimne_fellowships_status_codes( $status ),
    );
    $args = cimne_fellowships_apply_server_filters( $args, $filters );

    return cimne_api_get(
        'fellowships',
        $args,
        array(
            'cache_variant' => 'fellowship-list-v1',
            'transform'     => 'cimne_normalize_fellowship_list_payload',
        )
    );
}

/**
 * Gets one fellowship by exact public code.
 *
 * @param string $code Bxxxxx code.
 * @return array|WP_Error
 */
function cimne_fellowships_get_detail( $code ) {
    $code = trim( sanitize_text_field( (string) $code ) );

    if ( '' === $code ) {
        return new WP_Error( 'cimne_fellowship_missing_code', 'Fellowship code is required.' );
    }

    $items = cimne_api_get(
        'fellowships',
        array( 'codigo' => $code ),
        array(
            'cache_variant' => 'fellowship-detail-v1',
            'transform'     => 'cimne_normalize_fellowship_detail_payload',
        )
    );

    if ( is_wp_error( $items ) ) {
        return $items;
    }

    if ( empty( $items ) ) {
        return new WP_Error( 'cimne_fellowship_not_found', 'Fellowship not found.' );
    }

    return reset( $items );
}

/**
 * Groups fellowships by nature/category, matching Projects.
 *
 * @param array $items Reduced list items.
 * @return array
 */
function cimne_fellowships_group_by_category( array $items ) {
    $groups = array();

    foreach ( $items as $item ) {
        $category = isset( $item['category'] ) ? trim( (string) $item['category'] ) : '';
        $category = '' !== $category ? $category : __( 'Other', 'cimne' );

        if ( ! isset( $groups[ $category ] ) ) {
            $groups[ $category ] = array();
        }

        $groups[ $category ][] = $item;
    }

    return $groups;
}

/**
 * Renders a fellowship list for SSR or REST.
 *
 * @param array  $items  Reduced items.
 * @param string $status ongoing|finished.
 * @return string
 */
function cimne_fellowships_render_list( array $items, $status ) {
    ob_start();

    get_template_part(
        'template-parts/awards/fellowship-list',
        null,
        array(
            'status' => $status,
            'groups' => cimne_fellowships_group_by_category( $items ),
        )
    );

    return (string) ob_get_clean();
}

/**
 * Renders one fellowship detail.
 *
 * @param array $item Reduced detail.
 * @return string
 */
function cimne_fellowships_render_detail( array $item ) {
    ob_start();
    get_template_part( 'template-parts/awards/fellowship-detail', null, array( 'fellowship' => $item ) );
    return (string) ob_get_clean();
}

/**
 * Builds the initial SSR component context.
 *
 * @return array|WP_Error
 */
function cimne_fellowships_get_context() {
    $items = cimne_fellowships_get_list( 'ongoing' );

    if ( is_wp_error( $items ) ) {
        return $items;
    }

    return array(
        'ongoing_html' => cimne_fellowships_render_list( $items, 'ongoing' ),
    );
}

/**
 * Renders the interactive results shell.
 *
 * @param array $context Component context.
 * @return string
 */
function cimne_fellowships_render( array $context ) {
    ob_start();
    get_template_part( 'template-parts/awards/fellowships', null, $context );
    return (string) ob_get_clean();
}

/**
 * Results shortcode for the Fellowships tab on Awards.
 *
 * @return string
 */
function cimne_fellowships_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $context = cimne_fellowships_get_context();

    if ( is_wp_error( $context ) ) {
        cimne_api_log(
            'Fellowships component could not be rendered.',
            array(
                'post_id'    => (int) get_queried_object_id(),
                'error_code' => $context->get_error_code(),
            )
        );
        return '';
    }

    return cimne_fellowships_render( $context );
}
add_shortcode( 'cimne_fellowships', 'cimne_fellowships_shortcode' );
