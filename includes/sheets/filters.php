<?php
/**
 * Filters for Google Sheets-backed components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode: [cimne_phd_theses_filters]
 *
 * The filtering itself is client-side because the full thesis list is already
 * present in the SSR HTML. No REST request or admin-ajax.php call is needed.
 *
 * @return string
 */
function cimne_phd_theses_filters_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    ob_start();

    get_template_part(
        'template-parts/sheets/phd-theses-filters'
    );

    return (string) ob_get_clean();
}
add_shortcode( 'cimne_phd_theses_filters', 'cimne_phd_theses_filters_shortcode' );
