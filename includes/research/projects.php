<?php
/**
 * Interactive Projects components for:
 * - Cluster / Research Group pages: [cimne_research_projects]
 * - Global Projects page: [cimne_projects]
 *
 * The initial Ongoing list is server-rendered. Finished lists and project
 * details are loaded through the CIMNE WordPress REST routes defined in
 * rest.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Normalizes the Projects UI context.
 *
 * @param string $context research|global.
 * @return string
 */
function cimne_projects_normalize_context( $context ) {
    return 'global' === sanitize_key( (string) $context ) ? 'global' : 'research';
}

/**
 * Returns the public Projects-page URL in the current language.
 *
 * This is the single share/deep-link base used by all project contexts.
 * Cluster/Group pages still open details inline, but copied project URLs
 * always point to the global Projects page.
 *
 * @param string $context research|global.
 * @return string
 */
function cimne_projects_get_public_page_url( $context = 'research' ) {
    $context = cimne_projects_normalize_context( $context );
    $url     = '';

    /*
     * On the global Projects page itself, the current permalink is the most
     * reliable source because it already reflects the active WPML language.
     */
    if ( 'global' === $context ) {
        $post_id = (int) get_queried_object_id();

        if ( $post_id > 0 ) {
            $current_permalink = get_permalink( $post_id );

            if ( is_string( $current_permalink ) && '' !== $current_permalink ) {
                $url = $current_permalink;
            }
        }
    }

    /*
     * From Cluster/Group pages, resolve the canonical Projects page and then
     * ask WPML for the translation that matches the current language.
     */
    if ( '' === $url ) {
        $projects_page = get_page_by_path( 'research/projects', OBJECT, 'page' );

        if ( $projects_page instanceof WP_Post ) {
            $page_id          = (int) $projects_page->ID;
            $current_language = apply_filters( 'wpml_current_language', null );

            if ( is_string( $current_language ) && '' !== $current_language ) {
                $translated_id = apply_filters( 'wpml_object_id', $page_id, 'page', true, $current_language );

                if ( is_numeric( $translated_id ) && (int) $translated_id > 0 ) {
                    $page_id = (int) $translated_id;
                }
            }

            $resolved_permalink = get_permalink( $page_id );

            if ( is_string( $resolved_permalink ) && '' !== $resolved_permalink ) {
                $url = $resolved_permalink;
            }
        }
    }

    /*
     * Safe fallback. wpml_permalink can translate the URL when WPML is active;
     * without WPML, apply_filters simply returns the original value.
     */
    if ( '' === $url ) {
        $url              = home_url( '/research/projects/' );
        $current_language = apply_filters( 'wpml_current_language', null );

        if ( is_string( $current_language ) && '' !== $current_language ) {
            $translated_url = apply_filters( 'wpml_permalink', $url, $current_language, true );

            if ( is_string( $translated_url ) && '' !== $translated_url ) {
                $url = $translated_url;
            }
        }
    }

    return (string) apply_filters( 'cimne_projects_public_page_url', $url, $context );
}

/**
 * Returns the API scope for a normalized research unit.
 *
 * @param string $code Research-unit code.
 * @param array  $unit Normalized research unit.
 * @return array
 */
function cimne_research_project_scope_args( $code, array $unit ) {
    return cimne_research_unit_is_cluster( $unit )
        ? array( 'IU' => $code )
        : array( 'departamento_codigo' => $code );
}

/**
 * Maps the UI project status to API status codes.
 *
 * @param string $status ongoing|finished.
 * @return string
 */
function cimne_research_project_status_codes( $status ) {
    return 'finished' === $status ? 'C,F' : 'A';
}

/**
 * Formats project dates exactly as used by the approved design.
 *
 * @param string|null $date API date (normally YYYY-MM-DD).
 * @return string
 */
function cimne_research_format_project_date( $date ) {
    if ( ! is_scalar( $date ) || '' === trim( (string) $date ) ) {
        return '';
    }

    $date      = trim( (string) $date );
    $timestamp = strtotime( $date );

    if ( false === $timestamp ) {
        return $date;
    }

    return wp_date( 'd-m-Y', $timestamp );
}

/**
 * Builds the API arguments shared by list/detail requests.
 *
 * Global context intentionally has no IU/department restriction.
 * Research context is restricted to the current Cluster or Group.
 *
 * @param string $context research|global.
 * @param string $unit_code Research-unit code. Required in research context.
 * @return array|WP_Error
 */
function cimne_projects_scope_args( $context, $unit_code = '' ) {
    $context   = cimne_projects_normalize_context( $context );
    $unit_code = trim( (string) $unit_code );

    if ( 'global' === $context ) {
        return array();
    }

    if ( '' === $unit_code ) {
        return new WP_Error(
            'cimne_projects_missing_unit_code',
            'The CIMNE research unit code is required in research context.'
        );
    }

    $unit = cimne_research_get_unit_by_code( $unit_code );

    if ( is_wp_error( $unit ) ) {
        return $unit;
    }

    return cimne_research_project_scope_args( $unit_code, $unit );
}

/**
 * Normalizes server-side filters supported by the Projects WS.
 *
 * Search and Principal Investigator are intentionally not included here:
 * those filters are applied in the browser because the current Projects WS
 * does not provide equivalent query parameters.
 *
 * @param array $filters Raw filters.
 * @return array
 */
function cimne_projects_normalize_list_filters( array $filters ) {
    return array(
        'category' => isset( $filters['category'] ) ? trim( sanitize_text_field( (string) $filters['category'] ) ) : '',
        'unit'     => isset( $filters['unit'] ) ? trim( sanitize_text_field( (string) $filters['unit'] ) ) : '',
    );
}

/**
 * Applies global Projects filters that the external WS supports natively.
 *
 * Category maps to naturaleza_proyecto_descripcion. Research Cluster / IU
 * maps to IU so the service automatically includes descendants.
 *
 * @param array  $args    Existing API args.
 * @param string $context research|global.
 * @param array  $filters Normalized filters.
 * @return array
 */
function cimne_projects_apply_server_filters( array $args, $context, array $filters ) {
    if ( 'global' !== cimne_projects_normalize_context( $context ) ) {
        return $args;
    }

    $filters = cimne_projects_normalize_list_filters( $filters );

    if ( '' !== $filters['category'] ) {
        $args['naturaleza_proyecto_descripcion'] = $filters['category'];
    }

    if ( '' !== $filters['unit'] ) {
        $args['IU'] = $filters['unit'];
    }

    return $args;
}

/**
 * Gets a reduced project list for one context/status.
 *
 * The API client transforms the payload before caching it, so fields not used
 * by the approved project-list design are not stored in these cache entries.
 *
 * @param string $context   research|global.
 * @param string $unit_code Research-unit code in research context.
 * @param string $status    ongoing|finished.
 * @return array|WP_Error
 */
function cimne_projects_get_project_list( $context, $unit_code = '', $status = 'ongoing', array $filters = array() ) {
    $context = cimne_projects_normalize_context( $context );
    $status  = 'finished' === $status ? 'finished' : 'ongoing';
    $args    = cimne_projects_scope_args( $context, $unit_code );

    if ( is_wp_error( $args ) ) {
        return $args;
    }

    $args['estado_actual_codigo'] = cimne_research_project_status_codes( $status );
    $args = cimne_projects_apply_server_filters( $args, $context, $filters );

    return cimne_api_get(
        'projects',
        $args,
        array(
            'cache_variant' => 'project-list-v4-' . $context,
            'transform'     => 'cimne_normalize_project_list_payload',
        )
    );
}

/**
 * Backward-compatible Research Cluster/Group list helper.
 *
 * @param string $code   Research-unit code.
 * @param string $status ongoing|finished.
 * @return array|WP_Error
 */
function cimne_research_get_project_list( $code, $status = 'ongoing' ) {
    return cimne_projects_get_project_list( 'research', $code, $status );
}

/**
 * Gets the global project list.
 *
 * @param string $status ongoing|finished.
 * @return array|WP_Error
 */
function cimne_global_get_project_list( $status = 'ongoing', array $filters = array() ) {
    return cimne_projects_get_project_list( 'global', '', $status, $filters );
}

/**
 * Gets one reduced project detail.
 *
 * Research context keeps the unit scope in the request. Global context only
 * filters by the exact project code.
 *
 * @param string $context      research|global.
 * @param string $unit_code    Research-unit code in research context.
 * @param string $project_code Project code.
 * @return array|WP_Error
 */
function cimne_projects_get_project_detail( $context, $unit_code, $project_code ) {
    $context      = cimne_projects_normalize_context( $context );
    $project_code = trim( (string) $project_code );

    if ( '' === $project_code ) {
        return new WP_Error(
            'cimne_project_missing_code',
            'Project code is required.'
        );
    }

    $args = cimne_projects_scope_args( $context, $unit_code );

    if ( is_wp_error( $args ) ) {
        return $args;
    }

    $args['codigo'] = $project_code;

    $projects = cimne_api_get(
        'projects',
        $args,
        array(
            'cache_variant' => 'project-detail-v3-' . $context,
            'transform'     => 'cimne_normalize_project_detail_payload',
        )
    );

    if ( is_wp_error( $projects ) ) {
        return $projects;
    }

    if ( empty( $projects ) ) {
        return new WP_Error(
            'cimne_project_not_found',
            'Project not found.'
        );
    }

    return reset( $projects );
}

/**
 * Backward-compatible Research Cluster/Group detail helper.
 *
 * @param string $code         Research-unit code.
 * @param string $project_code Project code.
 * @return array|WP_Error
 */
function cimne_research_get_project_detail( $code, $project_code ) {
    return cimne_projects_get_project_detail( 'research', $code, $project_code );
}

/**
 * Gets one global project detail.
 *
 * @param string $project_code Project code.
 * @return array|WP_Error
 */
function cimne_global_get_project_detail( $project_code ) {
    return cimne_projects_get_project_detail( 'global', '', $project_code );
}

/**
 * Groups projects by naturaleza_proyecto_descripcion.
 *
 * Category and item order preserve the first order returned by the WS.
 *
 * @param array $projects Reduced project-list items.
 * @return array
 */
function cimne_research_group_projects_by_category( array $projects ) {
    $groups = array();

    foreach ( $projects as $project ) {
        $category = isset( $project['category'] ) ? trim( (string) $project['category'] ) : '';

        if ( '' === $category ) {
            $category = __( 'Other', 'cimne' );
        }

        if ( ! isset( $groups[ $category ] ) ) {
            $groups[ $category ] = array();
        }

        $groups[ $category ][] = $project;
    }

    return $groups;
}

/**
 * Renders only a project list. Used by SSR and REST.
 *
 * @param array  $projects Reduced project-list items.
 * @param string $status   ongoing|finished.
 * @return string
 */
function cimne_research_render_project_list( array $projects, $status ) {
    ob_start();

    get_template_part(
        'template-parts/research/project-list',
        null,
        array(
            'status' => $status,
            'groups' => cimne_research_group_projects_by_category( $projects ),
        )
    );

    return (string) ob_get_clean();
}

/**
 * Renders one project detail. Used by REST.
 *
 * @param array $project Reduced project detail.
 * @return string
 */
function cimne_research_render_project_detail( array $project ) {
    ob_start();

    get_template_part(
        'template-parts/research/project-detail',
        null,
        array( 'project' => $project )
    );

    return (string) ob_get_clean();
}

/**
 * Builds the initial Projects component context for Cluster/Group pages.
 *
 * @param string $code Research-unit code.
 * @return array|WP_Error
 */
function cimne_research_get_projects_context( $code ) {
    $unit = cimne_research_get_unit_by_code( $code );

    if ( is_wp_error( $unit ) ) {
        return $unit;
    }

    $projects = cimne_research_get_project_list( $code, 'ongoing' );

    if ( is_wp_error( $projects ) ) {
        return $projects;
    }

    return array(
        'project_context'    => 'research',
        'research_unit_code' => $code,
        'is_cluster'         => cimne_research_unit_is_cluster( $unit ),
        'projects_page_url'  => cimne_projects_get_public_page_url( 'research' ),
        'ongoing_html'       => cimne_research_render_project_list( $projects, 'ongoing' ),
    );
}

/**
 * Builds the initial Projects component context for the global Projects page.
 *
 * @return array|WP_Error
 */
function cimne_global_get_projects_context() {
    $projects = cimne_global_get_project_list( 'ongoing' );

    if ( is_wp_error( $projects ) ) {
        return $projects;
    }

    return array(
        'project_context'    => 'global',
        'research_unit_code' => '',
        'is_cluster'         => false,
        'projects_page_url'  => cimne_projects_get_public_page_url( 'global' ),
        'ongoing_html'       => cimne_research_render_project_list( $projects, 'ongoing' ),
    );
}

/**
 * Renders the main Projects shell shared by research and global contexts.
 *
 * @param array $context Projects context.
 * @return string
 */
function cimne_research_render_projects( array $context ) {
    ob_start();

    get_template_part(
        'template-parts/research/projects',
        null,
        $context
    );

    return (string) ob_get_clean();
}

/**
 * Shortcode used inside Cluster/Research Group Divi pages:
 * [cimne_research_projects]
 *
 * @return string
 */
function cimne_research_projects_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $code = cimne_research_get_unit_code();

    if ( '' === $code ) {
        cimne_api_log(
            'Research Projects shortcode has no research_unit_code.',
            array( 'post_id' => (int) get_queried_object_id() )
        );

        return '';
    }

    $context = cimne_research_get_projects_context( $code );

    if ( is_wp_error( $context ) ) {
        cimne_api_log(
            'Research Projects component could not be rendered.',
            array(
                'post_id'    => (int) get_queried_object_id(),
                'unit_code'  => $code,
                'error_code' => $context->get_error_code(),
            )
        );

        return '';
    }

    return cimne_research_render_projects( $context );
}
add_shortcode( 'cimne_research_projects', 'cimne_research_projects_shortcode' );

/**
 * Shortcode used on the global Projects page:
 * [cimne_projects]
 *
 * No research_unit_code is required and no IU/department filter is added.
 *
 * @return string
 */
function cimne_projects_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $context = cimne_global_get_projects_context();

    if ( is_wp_error( $context ) ) {
        cimne_api_log(
            'Global Projects component could not be rendered.',
            array(
                'post_id'    => (int) get_queried_object_id(),
                'error_code' => $context->get_error_code(),
            )
        );

        return '';
    }

    return cimne_research_render_projects( $context );
}
add_shortcode( 'cimne_projects', 'cimne_projects_shortcode' );
