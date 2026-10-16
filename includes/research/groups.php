<?php
/**
 * Research Groups component for CIMNE Research Cluster pages.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Finds the WordPress page associated with one research_unit_code.
 *
 * WPML is allowed to filter the first query so the current-language page is
 * preferred. If no translated match is found, the original matching page is
 * used as a fallback.
 *
 * @param string $unit_code Research-unit code.
 * @return int
 */
function cimne_research_find_page_by_unit_code( $unit_code ) {
    $unit_code = trim( (string) $unit_code );

    if ( '' === $unit_code ) {
        return 0;
    }

    $query = new WP_Query(
        array(
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'posts_per_page'   => 1,
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => false,
            'meta_query'       => array(
                array(
                    'key'     => 'research_unit_code',
                    'value'   => $unit_code,
                    'compare' => '=',
                ),
            ),
        )
    );

    if ( ! empty( $query->posts ) ) {
        return (int) $query->posts[0];
    }

    $fallback = get_posts(
        array(
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'posts_per_page'   => 1,
            'fields'           => 'ids',
            'suppress_filters' => true,
            'meta_key'         => 'research_unit_code',
            'meta_value'       => $unit_code,
        )
    );

    if ( empty( $fallback ) ) {
        return 0;
    }

    $page_id = (int) $fallback[0];

    if ( has_filter( 'wpml_object_id' ) ) {
        $translated_id = apply_filters( 'wpml_object_id', $page_id, 'page', true );

        if ( $translated_id ) {
            $page_id = (int) $translated_id;
        }
    }

    return $page_id;
}

/**
 * Builds the Research Groups template context for one Cluster.
 *
 * @param string $code Research Cluster code.
 * @return array|WP_Error
 */
function cimne_research_get_groups_context( $code ) {
    $cluster = cimne_research_get_unit_complete( $code );

    if ( is_wp_error( $cluster ) ) {
        return $cluster;
    }

    if ( ! cimne_research_unit_is_cluster( $cluster ) ) {
        return new WP_Error(
            'cimne_research_groups_not_cluster',
            'Research Groups can only be rendered for a Research Cluster.'
        );
    }

    $children = cimne_research_get_cluster_children( $code );

    if ( is_wp_error( $children ) ) {
        return $children;
    }

    $groups = array();

    foreach ( $children as $unit ) {
        $unit_code = isset( $unit['code'] ) ? trim( (string) $unit['code'] ) : '';

        if ( '' === $unit_code ) {
            continue;
        }

        $page_id = cimne_research_find_page_by_unit_code( $unit_code );
        $leaders = cimne_research_get_unit_responsible_people( $unit );

        $title = $page_id ? trim( wp_strip_all_tags( get_the_title( $page_id ) ) ) : '';

        if ( '' === $title ) {
            $title = isset( $unit['name'] ) ? trim( (string) $unit['name'] ) : $unit_code;
        }

        $leader_names = array();

        foreach ( $leaders as $leader ) {
            $leader_name = isset( $leader['name'] ) ? trim( (string) $leader['name'] ) : '';

            if ( '' !== $leader_name ) {
                $leader_names[] = $leader_name;
            }
        }

        $leader_names = array_values( array_unique( $leader_names ) );

        $groups[] = array(
            'code'         => $unit_code,
            'title'        => $title,
            'url'          => $page_id ? get_permalink( $page_id ) : '',
            'leader_names' => $leader_names,
            // Backwards-compatible first leader for any custom code still
            // reading the previous singular field.
            'leader_name'  => ! empty( $leader_names ) ? $leader_names[0] : '',
        );
    }

    return array(
        'research_unit_code' => $code,
        'groups'             => $groups,
    );
}

/**
 * Renders the Research Groups component.
 *
 * @param array $context Template context.
 * @return string
 */
function cimne_research_render_groups( array $context ) {
    ob_start();

    get_template_part(
        'template-parts/research/research-groups',
        null,
        $context
    );

    return (string) ob_get_clean();
}

/**
 * Shortcode: [cimne_research_groups]
 *
 * @return string
 */
function cimne_research_groups_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $code = cimne_research_get_unit_code();

    if ( '' === $code ) {
        return '';
    }

    $context = cimne_research_get_groups_context( $code );

    if ( is_wp_error( $context ) ) {
        cimne_api_log(
            'Research Groups component could not be rendered.',
            array(
                'post_id'    => (int) get_queried_object_id(),
                'unit_code'  => $code,
                'error_code' => $context->get_error_code(),
            )
        );

        return '';
    }

    return cimne_research_render_groups( $context );
}
add_shortcode( 'cimne_research_groups', 'cimne_research_groups_shortcode' );
