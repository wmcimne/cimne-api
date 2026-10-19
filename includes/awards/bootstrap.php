<?php
/**
 * Bootstrap for Awards / Fellowships API components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the Fellowships and Fellowships-options resources in the shared CIMNE API client.
 *
 * @param array $resources Existing logical resource map.
 * @return array
 */
function cimne_awards_register_api_resources( array $resources ) {
    $resources['fellowships'] = 'becas';
    $resources['opts_becas']   = 'opts_becas';
    return $resources;
}
add_filter( 'cimne_api_resources', 'cimne_awards_register_api_resources' );

/**
 * Sets cache TTLs for Fellowships data and its filter-options endpoint.
 *
 * @param int    $ttl      Existing TTL.
 * @param string $resource Logical API resource.
 * @return int
 */
function cimne_awards_fellowships_cache_ttl( $ttl, $resource ) {
    if ( 'fellowships' === $resource ) {
        return 30 * MINUTE_IN_SECONDS;
    }

    if ( 'opts_becas' === $resource ) {
        return HOUR_IN_SECONDS;
    }

    return $ttl;
}
add_filter( 'cimne_api_cache_ttl', 'cimne_awards_fellowships_cache_ttl', 10, 2 );

require_once __DIR__ . '/normalizers.php';
require_once __DIR__ . '/fellowships.php';
require_once __DIR__ . '/filters.php';
require_once __DIR__ . '/rest.php';
require_once __DIR__ . '/assets.php';
