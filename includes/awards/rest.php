<?php
/**
 * Public REST routes for the Fellowships component.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sanitizes an Awards REST identifier.
 *
 * @param mixed $value Input.
 * @return string
 */
function cimne_fellowships_rest_identifier( $value ) {
    return trim( sanitize_text_field( is_scalar( $value ) ? (string) $value : '' ) );
}

/**
 * Registers list/detail routes.
 *
 * @return void
 */
function cimne_fellowships_register_rest_routes() {
    register_rest_route(
        'cimne/v1',
        '/fellowships/list',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback'            => 'cimne_fellowships_rest_list',
            'args'                => array(
                'status' => array(
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => static function ( $value ) {
                        return in_array( sanitize_key( (string) $value ), array( 'ongoing', 'finished' ), true );
                    },
                ),
                'category' => array(
                    'required'          => false,
                    'default'           => '',
                    'sanitize_callback' => 'cimne_fellowships_rest_identifier',
                ),
            ),
        )
    );

    register_rest_route(
        'cimne/v1',
        '/fellowships/detail',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => '__return_true',
            'callback'            => 'cimne_fellowships_rest_detail',
            'args'                => array(
                'fellowship' => array(
                    'required'          => true,
                    'sanitize_callback' => 'cimne_fellowships_rest_identifier',
                ),
            ),
        )
    );
}
add_action( 'rest_api_init', 'cimne_fellowships_register_rest_routes' );

/**
 * REST callback for an Ongoing/Finished list.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function cimne_fellowships_rest_list( WP_REST_Request $request ) {
    $status   = sanitize_key( (string) $request->get_param( 'status' ) );
    $category = cimne_fellowships_rest_identifier( $request->get_param( 'category' ) );
    $filters  = array(
        'category' => $category,
    );

    $items = cimne_fellowships_get_list( $status, $filters );

    if ( is_wp_error( $items ) ) {
        return $items;
    }

    return rest_ensure_response(
        array(
            'status'  => $status,
            'filters' => $filters,
            'count'   => count( $items ),
            'html'    => cimne_fellowships_render_list( $items, $status ),
        )
    );
}

/**
 * REST callback for one fellowship detail.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function cimne_fellowships_rest_detail( WP_REST_Request $request ) {
    $code = cimne_fellowships_rest_identifier( $request->get_param( 'fellowship' ) );
    $item = cimne_fellowships_get_detail( $code );

    if ( is_wp_error( $item ) ) {
        return $item;
    }

    return rest_ensure_response(
        array(
            'fellowship' => $code,
            'html'       => cimne_fellowships_render_detail( $item ),
        )
    );
}
