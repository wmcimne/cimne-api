<?php
/**
 * Research Cluster leadership component.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Builds Contact Point, Academic Leaders and External Advisors for one Cluster.
 *
 * IMPORTANT: Cluster leadership uses the NORMAL research_units view.
 * This is the dataset defined for Cluster-specific functions and includes
 * external roles. view=complete is reserved for enriched internal-person data
 * (Staff / Group Leaders) and must not replace this leadership source.
 *
 * @param string $code Research Cluster code.
 * @return array|WP_Error
 */
function cimne_research_get_cluster_leadership_context( $code ) {
    $cluster = cimne_research_get_unit_by_code( $code );

    if ( is_wp_error( $cluster ) ) {
        return $cluster;
    }

    if ( ! cimne_research_unit_is_cluster( $cluster ) ) {
        return new WP_Error(
            'cimne_research_leadership_not_cluster',
            'Cluster leadership can only be rendered for a Research Cluster.'
        );
    }

    $people  = isset( $cluster['people'] ) && is_array( $cluster['people'] ) ? $cluster['people'] : array();
    $contact = cimne_research_get_responsible_person( $cluster );

    return array(
        'research_unit_code' => $code,
        'contact_point'      => ! empty( $contact['name'] ) ? (string) $contact['name'] : '',
        'academic_leaders'   => cimne_research_people_names_by_role_match(
            $people,
            array( 'CAL' ),
            array( 'ACADEMIC LEADER' )
        ),
        'external_advisors'  => cimne_research_people_names_by_role_match(
            $people,
            array( 'CEA' ),
            array( 'EXTERNAL ADVISOR' )
        ),
    );
}

/**
 * Renders Cluster leadership.
 *
 * @param array $context Template context.
 * @return string
 */
function cimne_research_render_cluster_leadership( array $context ) {
    ob_start();

    get_template_part(
        'template-parts/research/cluster-leadership',
        null,
        $context
    );

    return (string) ob_get_clean();
}

/**
 * Shortcode: [cimne_cluster_leadership]
 *
 * @return string
 */
function cimne_cluster_leadership_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $code = cimne_research_get_unit_code();

    if ( '' === $code ) {
        return '';
    }

    $context = cimne_research_get_cluster_leadership_context( $code );

    if ( is_wp_error( $context ) ) {
        cimne_api_log(
            'Cluster leadership component could not be rendered.',
            array(
                'post_id'    => (int) get_queried_object_id(),
                'unit_code'  => $code,
                'error_code' => $context->get_error_code(),
            )
        );

        return '';
    }

    return cimne_research_render_cluster_leadership( $context );
}
add_shortcode( 'cimne_cluster_leadership', 'cimne_cluster_leadership_shortcode' );
