<?php
/**
 * Bootstrap for Awards / Fellowships API components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the logical Fellowships resource in the shared CIMNE API client.
 *
 * @param array $resources Existing logical resource map.
 * @return array
 */
function cimne_awards_register_api_resources( array $resources ) {
    $resources['fellowships'] = 'becas';
    return $resources;
}
add_filter( 'cimne_api_resources', 'cimne_awards_register_api_resources' );

/**
 * Fellowships change less often than interactive frontend state, so 30 minutes
 * matches the current Projects cache policy.
 *
 * @param int    $ttl      Existing TTL.
 * @param string $resource Logical API resource.
 * @return int
 */
function cimne_awards_fellowships_cache_ttl( $ttl, $resource ) {
    return 'fellowships' === $resource ? 30 * MINUTE_IN_SECONDS : $ttl;
}
add_filter( 'cimne_api_cache_ttl', 'cimne_awards_fellowships_cache_ttl', 10, 2 );

require_once __DIR__ . '/normalizers.php';
require_once __DIR__ . '/fellowships.php';
require_once __DIR__ . '/filters.php';
require_once __DIR__ . '/rest.php';
require_once __DIR__ . '/assets.php';
