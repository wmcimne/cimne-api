<?php
/**
 * Conditional assets for Google Sheets-backed components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Checks whether the current singular page contains a shortcode.
 *
 * The strpos fallback is useful with Divi content where shortcode parsing can
 * occasionally be affected by builder wrappers.
 *
 * @param string $tag Shortcode tag.
 * @return bool
 */
function cimne_sheets_page_has_shortcode( $tag ) {
    if ( ! is_singular() ) {
        return false;
    }

    $post = get_queried_object();

    if ( ! $post instanceof WP_Post ) {
        return false;
    }

    $content = (string) $post->post_content;

    return has_shortcode( $content, $tag ) || false !== strpos( $content, '[' . $tag );
}

/**
 * Returns a cache-busting version for a theme asset.
 *
 * @param string $relative_path Theme-relative path.
 * @return string
 */
function cimne_sheets_asset_version( $relative_path ) {
    if ( function_exists( 'cimne_asset_version' ) ) {
        return cimne_asset_version( $relative_path );
    }

    $absolute_path = get_stylesheet_directory() . $relative_path;

    if ( file_exists( $absolute_path ) ) {
        return (string) filemtime( $absolute_path );
    }

    return (string) wp_get_theme()->get( 'Version' );
}

/**
 * Loads the dedicated PhD Theses filtering assets only where needed.
 *
 * @return void
 */
function cimne_phd_theses_enqueue_assets() {
    $uses_phd_theses = cimne_sheets_page_has_shortcode( 'cimne_phd_theses' )
        || cimne_sheets_page_has_shortcode( 'cimne_phd_theses_filters' );

    if ( ! $uses_phd_theses ) {
        return;
    }

    $theme_uri = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'cimne-phd-theses',
        $theme_uri . '/css/phd-theses-api.css',
        array(),
        cimne_sheets_asset_version( '/css/phd-theses-api.css' )
    );

    wp_enqueue_script(
        'cimne-phd-theses',
        $theme_uri . '/js/cimne-phd-theses.js',
        array(),
        cimne_sheets_asset_version( '/js/cimne-phd-theses.js' ),
        array(
            'strategy'  => 'defer',
            'in_footer' => true,
        )
    );

    wp_localize_script(
        'cimne-phd-theses',
        'cimnePhdThesesI18n',
        array(
            'filtersApplied' => __( 'Filters applied.', 'cimne' ),
            'filtersCleared' => __( 'Filters cleared.', 'cimne' ),
            'noResults'      => __( 'No results found.', 'cimne' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'cimne_phd_theses_enqueue_assets', 30 );

/**
 * Loads the dedicated OKO Card link mapper only on the OKO Card page.
 *
 * Google Sheets is never called from this JavaScript. PHP has already fetched,
 * normalized and sanitized the matching row before the browser receives it.
 *
 * @return void
 */
function cimne_oko_card_enqueue_assets() {
    if ( ! cimne_sheets_page_has_shortcode( 'cimne_oko_card_social' ) ) {
        return;
    }

    wp_enqueue_script(
        'cimne-oko-card',
        get_stylesheet_directory_uri() . '/js/cimne-oko-card.js',
        array(),
        cimne_sheets_asset_version( '/js/cimne-oko-card.js' ),
        array(
            'strategy'  => 'defer',
            'in_footer' => true,
        )
    );
}
add_action( 'wp_enqueue_scripts', 'cimne_oko_card_enqueue_assets', 30 );
