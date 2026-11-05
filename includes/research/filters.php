<?php
/**
 * Global Projects filters powered by opts_projects.
 *
 * The complete A/C/F project collection is still preloaded for client-side
 * filtering, but the filter option catalogue must come from opts_projects,
 * which is the authoritative source for Category, Research Cluster / IU and
 * Principal Investigator values.
 *
 * Shortcode: [cimne_projects_filters]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gets reduced/cached filter options from opts_projects.
 *
 * opts_projects remains independent from the A/C/F project collection used by
 * the results component. This avoids deriving filter catalogues from project
 * records whose option metadata is not guaranteed to be complete/reliable.
 *
 * @return array|WP_Error
 */
function cimne_projects_get_filter_options() {
    return cimne_api_get(
        'opts_projects',
        array(),
        array(
            'cache_variant' => 'project-filter-options-v2',
            'transform'     => 'cimne_normalize_project_filter_options_payload',
        )
    );
}

/**
 * Renders the global Projects filter form.
 *
 * @param array $options Normalized filter options.
 * @return string
 */
function cimne_projects_render_filters( array $options ) {
    ob_start();

    get_template_part(
        'template-parts/research/projects-filters',
        null,
        array( 'options' => $options )
    );

    return (string) ob_get_clean();
}

/**
 * Shortcode for the left-hand filter column on /research/projects/.
 *
 * @return string
 */
function cimne_projects_filters_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $options = cimne_projects_get_filter_options();

    if ( is_wp_error( $options ) ) {
        cimne_api_log(
            'Global Projects filters could not be rendered.',
            array(
                'post_id'    => (int) get_queried_object_id(),
                'error_code' => $options->get_error_code(),
            )
        );

        return '';
    }

    return cimne_projects_render_filters( $options );
}
add_shortcode( 'cimne_projects_filters', 'cimne_projects_filters_shortcode' );
