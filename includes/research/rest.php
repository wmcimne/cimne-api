<?php
/**
 * Public REST routes used by the interactive Projects components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitizes a public API identifier received from REST.
 *
 * @param mixed $value Value.
 * @return string
 */
function cimne_research_rest_identifier( $value ) {
    return trim( sanitize_text_field( is_scalar( $value ) ? (string) $value : '' ) );
}

/**
 * Sanitizes/normalizes the Projects context received from REST.
 *
 * @param mixed $value Value.
 * @return string
 */
function cimne_projects_rest_context( $value ) {
    return cimne_projects_normalize_context( $value );
}

/**
 * Registers project-list and project-detail routes.
 *
 * The same routes serve both contexts:
 * - research: unit is required and the API request is scoped to that unit.
 * - global: unit is not required and the API request covers all projects.
 *
 * @return void
 */
function cimne_research_register_rest_routes() {
    register_rest_route(
        'cimne/v1',
        '/research-projects/list',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback'            => 'cimne_research_rest_project_list',
            'args'                => array(
                'context' => array(
                    'required'          => false,
                    'default'           => 'research',
                    'sanitize_callback' => 'cimne_projects_rest_context',
                    'validate_callback' => static function ( $value ) {
                        return in_array( sanitize_key( (string) $value ), array( 'research', 'global' ), true );
                    },
                ),
                'unit' => array(
                    'required'          => false,
                    'default'           => '',
                    'sanitize_callback' => 'cimne_research_rest_identifier',
                ),
                'status' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => static function ( $value ) {
                        return in_array( $value, array( 'ongoing', 'finished' ), true );
                    },
                ),
                'category' => array(
                    'required'          => false,
                    'default'           => '',
                    'sanitize_callback' => 'cimne_research_rest_identifier',
                ),
                'filter_unit' => array(
                    'required'          => false,
                    'default'           => '',
                    'sanitize_callback' => 'cimne_research_rest_identifier',
                ),
            ),
        )
    );

    register_rest_route(
        'cimne/v1',
        '/research-projects/detail',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback'            => 'cimne_research_rest_project_detail',
            'args'                => array(
                'context' => array(
                    'required'          => false,
                    'default'           => 'research',
                    'sanitize_callback' => 'cimne_projects_rest_context',
                    'validate_callback' => static function ( $value ) {
                        return in_array( sanitize_key( (string) $value ), array( 'research', 'global' ), true );
                    },
                ),
                'unit' => array(
                    'required'          => false,
                    'default'           => '',
                    'sanitize_callback' => 'cimne_research_rest_identifier',
                ),
                'project' => array(
                    'required'          => true,
                    'sanitize_callback' => 'cimne_research_rest_identifier',
                ),
            ),
        )
    );
}
add_action( 'rest_api_init', 'cimne_research_register_rest_routes' );

/**
 * Validates the unit requirement for the selected context.
 *
 * @param string $context research|global.
 * @param string $unit    Research-unit code.
 * @return true|WP_Error
 */
function cimne_projects_rest_validate_scope( $context, $unit ) {
    if ( 'research' === $context && '' === trim( (string) $unit ) ) {
        return new WP_Error(
            'cimne_projects_rest_missing_unit',
            'A research unit is required in research context.',
            array( 'status' => 400 )
        );
    }

    return true;
}

/**
 * REST callback: rendered Ongoing/Finished project list.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response|WP_Error
 */
function cimne_research_rest_project_list( WP_REST_Request $request ) {
    $context     = cimne_projects_rest_context( $request->get_param( 'context' ) );
    $unit        = cimne_research_rest_identifier( $request->get_param( 'unit' ) );
    $status      = sanitize_key( (string) $request->get_param( 'status' ) );
    $category    = cimne_research_rest_identifier( $request->get_param( 'category' ) );
    $filter_unit = cimne_research_rest_identifier( $request->get_param( 'filter_unit' ) );

    $scope_validation = cimne_projects_rest_validate_scope( $context, $unit );

    if ( is_wp_error( $scope_validation ) ) {
        return $scope_validation;
    }

    $filters = 'global' === $context
        ? array(
            'category' => $category,
            'unit'     => $filter_unit,
        )
        : array();

    $projects = cimne_projects_get_project_list( $context, $unit, $status, $filters );

    if ( is_wp_error( $projects ) ) {
        return $projects;
    }

    return rest_ensure_response(
        array(
            'context' => $context,
            'status'  => $status,
            'filters' => $filters,
            'count'   => count( $projects ),
            'html'    => cimne_research_render_project_list( $projects, $status ),
        )
    );
}

/**
 * REST callback: rendered project detail.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_REST_Response|WP_Error
 */
function cimne_research_rest_project_detail( WP_REST_Request $request ) {
    $context      = cimne_projects_rest_context( $request->get_param( 'context' ) );
    $unit         = cimne_research_rest_identifier( $request->get_param( 'unit' ) );
    $project_code = cimne_research_rest_identifier( $request->get_param( 'project' ) );

    $scope_validation = cimne_projects_rest_validate_scope( $context, $unit );

    if ( is_wp_error( $scope_validation ) ) {
        return $scope_validation;
    }

    $project = cimne_projects_get_project_detail( $context, $unit, $project_code );

    if ( is_wp_error( $project ) ) {
        return $project;
    }

    return rest_ensure_response(
        array(
            'context' => $context,
            'project' => $project_code,
            'html'    => cimne_research_render_project_detail( $project ),
        )
    );
}
