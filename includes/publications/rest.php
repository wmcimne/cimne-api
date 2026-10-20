<?php
/**
 * REST routes for Publications filtering and pagination.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers Publications REST route.
 *
 * @return void
 */
function cimne_publications_register_rest_routes() {
    register_rest_route(
        'cimne/v1',
        '/publications/list',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'cimne_publications_rest_list',
            'permission_callback' => '__return_true',
        )
    );
}
add_action( 'rest_api_init', 'cimne_publications_register_rest_routes' );

/**
 * Publications REST callback.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function cimne_publications_rest_list( WP_REST_Request $request ) {
    $context = 'research' === $request->get_param( 'context' ) ? 'research' : 'global';
    $page    = max( 1, absint( $request->get_param( 'page' ) ) );
    $unit    = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $request->get_param( 'unit' ) );
    $filters = cimne_publications_normalize_filters(
        array(
            'search'  => $request->get_param( 'search' ),
            'cluster' => $request->get_param( 'cluster' ),
            'subtype' => $request->get_param( 'subtype' ),
            'from'    => $request->get_param( 'from' ),
            'to'      => $request->get_param( 'to' ),
        )
    );

    if ( 'research' === $context && '' === $unit ) {
        return new WP_Error( 'cimne_publications_missing_unit', __( 'Research unit is required.', 'cimne' ), array( 'status' => 400 ) );
    }

    if ( 'research' === $context ) {
        $filters = array();
    }

    $payload = cimne_publications_get_page( $filters, $page, 'research' === $context ? $unit : '' );

    if ( is_wp_error( $payload ) ) {
        return $payload;
    }

    $items = isset( $payload['items'] ) && is_array( $payload['items'] ) ? $payload['items'] : array();
    $meta  = isset( $payload['meta'] ) && is_array( $payload['meta'] ) ? $payload['meta'] : array();
    $pages = (int) ( $meta['pages'] ?? 0 );

    return rest_ensure_response(
        array(
            'context'    => $context,
            'page'       => $page,
            'filters'    => $filters,
            'count'      => count( $items ),
            'total'      => (int) ( $meta['total'] ?? count( $items ) ),
            'meta'       => $meta,
            'html'       => cimne_publications_render_items( $items ),
            'pagination' => cimne_publications_render_pagination( $page, $pages ),
        )
    );
}
