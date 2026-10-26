<?php
/**
 * Conditional Publications assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Asset version helper.
 *
 * @param string $relative_path Theme-relative path.
 * @return string
 */
function cimne_publications_asset_version( $relative_path ) {
    if ( function_exists( 'cimne_asset_version' ) ) {
        return cimne_asset_version( $relative_path );
    }

    $absolute = get_stylesheet_directory() . $relative_path;
    return file_exists( $absolute ) ? (string) filemtime( $absolute ) : (string) wp_get_theme()->get( 'Version' );
}

/**
 * Checks a shortcode inside the current Divi page content.
 *
 * @param string $tag Shortcode tag.
 * @return bool
 */
function cimne_publications_page_has_shortcode( $tag ) {
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
 * Loads Publications CSS/JS only on pages using the module.
 *
 * @return void
 */
function cimne_publications_enqueue_assets() {
    $uses_publications = cimne_publications_page_has_shortcode( 'cimne_publications' )
        || cimne_publications_page_has_shortcode( 'cimne_publications_filters' )
        || cimne_publications_page_has_shortcode( 'cimne_research_publications' )
        || cimne_publications_page_has_shortcode( 'cimne_onate_publications' );

    if ( ! $uses_publications ) {
        return;
    }

    $theme_uri = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'cimne-publications-api',
        $theme_uri . '/css/publications-api.css',
        array(),
        cimne_publications_asset_version( '/css/publications-api.css' )
    );

    wp_enqueue_script(
        'cimne-publications-api',
        $theme_uri . '/js/cimne-publications.js',
        array(),
        cimne_publications_asset_version( '/js/cimne-publications.js' ),
        array(
            'strategy'  => 'defer',
            'in_footer' => true,
        )
    );

    wp_localize_script(
        'cimne-publications-api',
        'cimnePublicationsI18n',
        array(
            'loadError'      => __( 'Unable to load publications.', 'cimne' ),
            'filtersApplied' => __( 'Filters applied.', 'cimne' ),
            'filtersCleared' => __( 'Filters cleared.', 'cimne' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'cimne_publications_enqueue_assets', 30 );
