<?php
/**
 * Global Publications filters.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders [cimne_publications_filters].
 *
 * @return string
 */
function cimne_publications_filters_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $clusters = cimne_publications_get_cluster_options();

    if ( is_wp_error( $clusters ) ) {
        $clusters = array();
    }

    ob_start();
    get_template_part(
        'template-parts/publications/publications-filters',
        null,
        array( 'clusters' => $clusters )
    );
    return (string) ob_get_clean();
}
add_shortcode( 'cimne_publications_filters', 'cimne_publications_filters_shortcode' );
