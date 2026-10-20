<?php
/**
 * Bootstrap for Publications API components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers Publications in the shared CIMNE API client.
 *
 * @param array $resources Existing resource map.
 * @return array
 */
function cimne_publications_register_api_resource( array $resources ) {
    $resources['publications'] = 'publicaciones';
    return $resources;
}
add_filter( 'cimne_api_resources', 'cimne_publications_register_api_resource' );

/**
 * Publications fresh-cache lifetime.
 *
 * @param int    $ttl      Existing TTL.
 * @param string $resource Resource name.
 * @return int
 */
function cimne_publications_cache_ttl( $ttl, $resource ) {
    if ( 'publications' === $resource ) {
        return 30 * MINUTE_IN_SECONDS;
    }

    return $ttl;
}
add_filter( 'cimne_api_cache_ttl', 'cimne_publications_cache_ttl', 10, 2 );

require_once __DIR__ . '/normalizers.php';
require_once __DIR__ . '/publications.php';
require_once __DIR__ . '/filters.php';
require_once __DIR__ . '/rest.php';
require_once __DIR__ . '/assets.php';
