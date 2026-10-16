<?php
/**
 * Conditional CSS/JS loading for Awards / Fellowships.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Asset version compatible with the existing child-theme helper.
 *
 * @param string $relative_path Path relative to child theme.
 * @return string
 */
function cimne_awards_asset_version( $relative_path ) {
    if ( function_exists( 'cimne_asset_version' ) ) {
        return cimne_asset_version( $relative_path );
    }

    $absolute = get_stylesheet_directory() . $relative_path;
    return file_exists( $absolute ) ? (string) filemtime( $absolute ) : (string) wp_get_theme()->get( 'Version' );
}

/**
 * Checks shortcodes even when nested in Divi modules.
 *
 * @param string $tag Shortcode.
 * @return bool
 */
function cimne_awards_current_page_has_shortcode( $tag ) {
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
 * Loads Fellowships assets only where either shortcode is present.
 *
 * @return void
 */
function cimne_awards_enqueue_fellowships_assets() {
    if ( ! cimne_awards_current_page_has_shortcode( 'cimne_fellowships' )
        && ! cimne_awards_current_page_has_shortcode( 'cimne_fellowships_filters' ) ) {
        return;
    }

    $theme_uri = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'cimne-fellowships-api',
        $theme_uri . '/css/fellowships-api.css',
        array(),
        cimne_awards_asset_version( '/css/fellowships-api.css' )
    );

    wp_enqueue_script(
        'cimne-fellowships-api',
        $theme_uri . '/js/cimne-fellowships.js',
        array(),
        cimne_awards_asset_version( '/js/cimne-fellowships.js' ),
        array(
            'strategy'  => 'defer',
            'in_footer' => true,
        )
    );

    wp_localize_script(
        'cimne-fellowships-api',
        'cimneFellowshipsI18n',
        array(
            'noFellowships'            => __( 'No fellowships found.', 'cimne' ),
            'ongoingFellowships'       => __( 'Ongoing fellowships', 'cimne' ),
            'finishedFellowships'      => __( 'Finished fellowships', 'cimne' ),
            'loadFellowshipsError'     => __( 'Unable to load fellowships.', 'cimne' ),
            'loadFellowshipDetailError'=> __( 'Unable to load fellowship details.', 'cimne' ),
            'filtersApplied'           => __( 'Filters applied.', 'cimne' ),
            'filtersCleared'           => __( 'Filters cleared.', 'cimne' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'cimne_awards_enqueue_fellowships_assets', 30 );
