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
 * There is no assumed opts_becas endpoint:
 * - Category + Beneficiary options come from a reduced /becas request.
 * - Cluster / IU options come from /research_units.
 *
 * @return array
 */
function cimne_fellowships_get_filter_options() {
    $base = array(
        'nature'        => array(),
        'units'         => array(),
        'beneficiaries' => array(),
    );

    $fellowship_options = cimne_api_get(
        'fellowships',
        array(),
        array(
            'cache_variant' => 'fellowship-filter-options-v1',
            'transform'     => 'cimne_normalize_fellowship_filter_options_payload',
        )
    );

    if ( ! is_wp_error( $fellowship_options ) ) {
        $base['nature']        = isset( $fellowship_options['nature'] ) && is_array( $fellowship_options['nature'] ) ? $fellowship_options['nature'] : array();
        $base['beneficiaries'] = isset( $fellowship_options['beneficiaries'] ) && is_array( $fellowship_options['beneficiaries'] ) ? $fellowship_options['beneficiaries'] : array();
    } else {
        cimne_api_log( 'Fellowship Category/Beneficiary options unavailable.', array( 'error_code' => $fellowship_options->get_error_code() ) );
    }

    $units = cimne_api_get(
        'research_units',
        array(),
        array(
            'cache_variant' => 'fellowship-unit-options-v1',
            'transform'     => 'cimne_normalize_fellowship_unit_options_payload',
        )
    );

    if ( ! is_wp_error( $units ) ) {
        $base['units'] = $units;
    } else {
        cimne_api_log( 'Fellowship Research Cluster / IU options unavailable.', array( 'error_code' => $units->get_error_code() ) );
    }

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
