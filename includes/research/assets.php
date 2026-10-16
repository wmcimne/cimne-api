<?php
/**
 * Conditional CSS/JS loading for Research API and global Projects components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Asset version compatible with the theme's existing filemtime helper.
 *
 * @param string $relative_path Path relative to the child theme.
 * @return string
 */
function cimne_research_asset_version( $relative_path ) {
    if ( function_exists( 'cimne_asset_version' ) ) {
        return cimne_asset_version( $relative_path );
    }

    $absolute = get_stylesheet_directory() . $relative_path;

    if ( file_exists( $absolute ) ) {
        return (string) filemtime( $absolute );
    }

    return (string) wp_get_theme()->get( 'Version' );
}

/**
 * Checks whether the current singular post contains a shortcode.
 *
 * Divi may nest the shortcode inside its own module shortcodes, therefore the
 * direct string check is kept as a fallback to has_shortcode().
 *
 * @param string $tag Shortcode tag.
 * @return bool
 */
function cimne_research_current_page_has_shortcode( $tag ) {
    if ( ! is_singular() ) {
        return false;
    }

    $post = get_queried_object();

    if ( ! $post instanceof WP_Post ) {
        return false;
    }

    $content = (string) $post->post_content;

    return has_shortcode( $content, $tag )
        || false !== strpos( $content, '[' . $tag );
}

/**
 * Tells whether the current page needs the shared Research/Projects assets.
 *
 * Research pages are detected by research_unit_code. The global Projects page
 * has no unit code, so it is detected through [cimne_projects].
 *
 * @return bool
 */
function cimne_research_page_uses_api_components() {
    if ( ! is_singular() ) {
        return false;
    }

    if ( '' !== cimne_research_get_unit_code() ) {
        return true;
    }

    return cimne_research_current_page_has_shortcode( 'cimne_projects' )
        || cimne_research_current_page_has_shortcode( 'cimne_projects_filters' );
}

/**
 * Loads the shared assets only where an API component is actually used.
 *
 * @return void
 */
function cimne_research_enqueue_assets() {
    if ( ! cimne_research_page_uses_api_components() ) {
        return;
    }

    $theme_uri = get_stylesheet_directory_uri();

    wp_enqueue_style(
        'cimne-research-api',
        $theme_uri . '/css/research-api.css',
        array(),
        cimne_research_asset_version( '/css/research-api.css' )
    );

    wp_enqueue_script(
        'cimne-research-api',
        $theme_uri . '/js/cimne-research.js',
        array(),
        cimne_research_asset_version( '/js/cimne-research.js' ),
        array(
            'strategy'  => 'defer',
            'in_footer' => true,
        )
    );

    /*
     * Static UI strings used directly by cimne-research.js.
     *
     * Keeping the source strings in PHP means they are exposed to the normal
     * WordPress gettext flow and can therefore be translated with WPML String
     * Translation under the "cimne" text domain. The JavaScript receives
     * the strings already translated for the current frontend language.
     */
    wp_localize_script(
        'cimne-research-api',
        'cimneResearchI18n',
        array(
            'noProjects'             => __( 'No projects found.', 'cimne' ),
            'ongoingProjects'        => __( 'Ongoing projects', 'cimne' ),
            'finishedProjects'       => __( 'Finished projects', 'cimne' ),
            'loadProjectsError'      => __( 'Unable to load projects.', 'cimne' ),
            'loadProjectDetailError' => __( 'Unable to load project details.', 'cimne' ),
            'filtersApplied'         => __( 'Filters applied.', 'cimne' ),
            'filtersCleared'         => __( 'Filters cleared.', 'cimne' ),
            'projectUrlCopied'       => __( 'Project URL copied.', 'cimne' ),
            'emailCopied'            => __( 'Email copied', 'cimne' ),
            'emailCopiedStatus'      => __( 'Email copied.', 'cimne' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'cimne_research_enqueue_assets', 30 );
