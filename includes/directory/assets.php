<?php
/**
 * Conditional assets for the CIMNE Directory.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Whether the current singular page contains a Directory shortcode.
 *
 * @return bool
 */
function cimne_directory_page_uses_component() {
    if ( ! is_singular() ) {
        return false;
    }

    $post = get_queried_object();

    if ( ! $post instanceof WP_Post ) {
        return false;
    }

    $content = (string) $post->post_content;

    return has_shortcode( $content, 'cimne_directory' )
        || has_shortcode( $content, 'cimne_directory_filters' )
        || false !== strpos( $content, '[cimne_directory' )
        || false !== strpos( $content, '[cimne_directory_filters' );
}

/**
 * Returns a filemtime version for a child-theme asset.
 *
 * @param string $relative_path Path starting with /.
 * @return string
 */
function cimne_directory_asset_version( $relative_path ) {
    if ( function_exists( 'cimne_asset_version' ) ) {
        return (string) cimne_asset_version( $relative_path );
    }

    $absolute_path = get_stylesheet_directory() . $relative_path;

    if ( file_exists( $absolute_path ) ) {
        return (string) filemtime( $absolute_path );
    }

    return (string) wp_get_theme()->get( 'Version' );
}

/**
 * Enqueues Directory layout, filters and local filtering logic.
 *
 * directory-api.css is intentionally kept separate: Step 7B does not replace
 * the validated custom list layout. The new filter stylesheet only owns the
 * Search/Location/Role controls and hidden-state rules.
 *
 * @return void
 */
function cimne_directory_enqueue_assets() {
    if ( ! cimne_directory_page_uses_component() ) {
        return;
    }

    $base_css = '/css/directory-api.css';
    $filters_css = '/css/directory-filters.css';
    $script = '/js/cimne-directory.js';

    wp_enqueue_style(
        'cimne-directory-api',
        get_stylesheet_directory_uri() . $base_css,
        array(),
        cimne_directory_asset_version( $base_css )
    );

    wp_enqueue_style(
        'cimne-directory-filters',
        get_stylesheet_directory_uri() . $filters_css,
        array( 'cimne-directory-api' ),
        cimne_directory_asset_version( $filters_css )
    );

    wp_enqueue_script(
        'cimne-directory',
        get_stylesheet_directory_uri() . $script,
        array(),
        cimne_directory_asset_version( $script ),
        true
    );

    wp_localize_script(
        'cimne-directory',
        'cimneDirectoryI18n',
        array(
            'oneResult'   => __( '1 person found.', 'cimne' ),
            'manyResults' => __( '%d people found.', 'cimne' ),
            'noResults'   => __( 'No people found.', 'cimne' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'cimne_directory_enqueue_assets', 30 );
