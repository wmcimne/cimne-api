<?php
/**
 * Shared helpers for CIMNE Research page components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Reads and sanitizes the CIMNE research-unit code assigned to a WordPress page.
 *
 * @param int $post_id WordPress post/page ID.
 * @return string
 */
function cimne_research_get_unit_code( $post_id = 0 ) {
    $post_id = (int) $post_id;

    if ( ! $post_id ) {
        $post_id = (int) get_queried_object_id();
    }

    if ( ! $post_id ) {
        $post_id = (int) get_the_ID();
    }

    if ( ! $post_id ) {
        return '';
    }

    if ( function_exists( 'get_field' ) ) {
        $code = get_field( 'research_unit_code', $post_id );
    } else {
        $code = get_post_meta( $post_id, 'research_unit_code', true );
    }

    if ( ! is_scalar( $code ) ) {
        return '';
    }

    $code = trim( (string) $code );

    return (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $code );
}

/**
 * Gets one normalized research unit by its exact CIMNE code.
 *
 * @param string $code Research-unit code.
 * @return array|WP_Error
 */
function cimne_research_get_unit_by_code( $code ) {
    $code = trim( (string) $code );

    if ( '' === $code ) {
        return new WP_Error(
            'cimne_research_missing_unit_code',
            'The CIMNE research unit code is missing.'
        );
    }

    $response = cimne_api_get(
        'research_units',
        array(
            'codigo' => $code,
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    if ( empty( $response ) ) {
        return new WP_Error(
            'cimne_research_unit_not_found',
            sprintf( 'No CIMNE research unit was found for code %s.', $code )
        );
    }

    $units = cimne_normalize_research_units( $response );

    foreach ( $units as $unit ) {
        if ( isset( $unit['code'] ) && 0 === strcasecmp( (string) $unit['code'], $code ) ) {
            return $unit;
        }
    }

    return reset( $units );
}

/**
 * Tells whether a normalized research unit is a Research Cluster.
 *
 * @param array $unit Normalized research unit.
 * @return bool
 */
// function cimne_research_unit_is_cluster( array $unit ) {
//     return isset( $unit['type_code'] )
//         && 'RC' === strtoupper( trim( (string) $unit['type_code'] ) );
// }
function cimne_research_unit_is_cluster( array $unit ) {
    $type_code = strtoupper( trim( (string) ( $unit['type_code'] ?? '' ) ) );

    return in_array( $type_code, array( 'RC', 'IU' ), true );
}