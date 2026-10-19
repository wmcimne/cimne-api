<?php
/**
 * Fellowships filters.
 *
 * Shortcode: [cimne_fellowships_filters]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gets cached options required by the Awards / Fellowships filter.
 *
 * Category and Beneficiary options come from /opts_becas.
 * Search remains a browser-side free-text filter.
 *
 * @return array
 */
function cimne_fellowships_get_filter_options() {
    $base = array(
        'nature'        => array(),
        'beneficiaries' => array(),
    );

    $options = cimne_api_get(
        'opts_becas',
        array(),
        array(
            'cache_variant' => 'fellowship-filter-options-v2',
            'transform'     => 'cimne_normalize_fellowship_filter_options_payload',
        )
    );

    if ( is_wp_error( $options ) ) {
        cimne_api_log(
            'Fellowship filter options unavailable.',
            array( 'error_code' => $options->get_error_code() )
        );
        return $base;
    }

    $base['nature'] = isset( $options['nature'] ) && is_array( $options['nature'] )
        ? $options['nature']
        : array();
    $base['beneficiaries'] = isset( $options['beneficiaries'] ) && is_array( $options['beneficiaries'] )
        ? $options['beneficiaries']
        : array();

    return $base;
}

/**
 * Renders the filters.
 *
 * @param array $options Normalized options.
 * @return string
 */
function cimne_fellowships_render_filters( array $options ) {
    ob_start();
    get_template_part( 'template-parts/awards/fellowships-filters', null, array( 'options' => $options ) );
    return (string) ob_get_clean();
}

/**
 * Filter shortcode for the Fellowships tab on Awards.
 *
 * @return string
 */
function cimne_fellowships_filters_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    return cimne_fellowships_render_filters( cimne_fellowships_get_filter_options() );
}
add_shortcode( 'cimne_fellowships_filters', 'cimne_fellowships_filters_shortcode' );
