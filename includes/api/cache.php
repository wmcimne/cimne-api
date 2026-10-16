<?php
/**
 * CIMNE API cache helpers.
 *
 * Two cache layers are kept for every request:
 * - fresh: normal cache used while the API data is considered current.
 * - stale: longer-lived last known good response, used only if the API fails.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the current cache namespace version.
 *
 * Bumping the version invalidates all CIMNE API cache keys without needing
 * to scan/delete individual transient rows.
 *
 * @return int
 */
function cimne_api_cache_version() {
    $version = (int) get_option( 'cimne_api_cache_version', 1 );

    return max( 1, $version );
}

/**
 * Normalizes request params so equivalent requests generate the same key.
 *
 * @param array $params Request parameters.
 * @return array
 */
function cimne_api_cache_normalize_params( array $params ) {
    foreach ( $params as $key => $value ) {
        if ( is_array( $value ) ) {
            $params[ $key ] = cimne_api_cache_normalize_params( $value );
        }
    }

    ksort( $params );

    return $params;
}

/**
 * Builds a compact transient key.
 *
 * @param string $bucket   fresh|stale.
 * @param string $resource API resource name.
 * @param array  $params   Request parameters.
 * @return string
 */
function cimne_api_cache_key( $bucket, $resource, array $params = array() ) {
    $payload = array(
        'version'  => cimne_api_cache_version(),
        'resource' => (string) $resource,
        'params'   => cimne_api_cache_normalize_params( $params ),
    );

    return sprintf(
        'cimne_api_%s_%s',
        'stale' === $bucket ? 's' : 'f',
        md5( wp_json_encode( $payload ) )
    );
}

/**
 * Gets cached API data.
 *
 * @param string $bucket   fresh|stale.
 * @param string $resource API resource name.
 * @param array  $params   Request parameters.
 * @return mixed|false
 */
function cimne_api_cache_get( $bucket, $resource, array $params = array() ) {
    return get_transient( cimne_api_cache_key( $bucket, $resource, $params ) );
}

/**
 * Stores API data in cache.
 *
 * @param string $bucket     fresh|stale.
 * @param string $resource   API resource name.
 * @param array  $params     Request parameters.
 * @param mixed  $data       Data to cache.
 * @param int    $expiration Expiration in seconds.
 * @return bool
 */
function cimne_api_cache_set( $bucket, $resource, array $params, $data, $expiration ) {
    return set_transient(
        cimne_api_cache_key( $bucket, $resource, $params ),
        $data,
        max( 1, (int) $expiration )
    );
}

/**
 * Deletes fresh and stale cache for one exact request.
 *
 * @param string $resource API resource name.
 * @param array  $params   Request parameters.
 * @return void
 */
function cimne_api_cache_delete( $resource, array $params = array() ) {
    delete_transient( cimne_api_cache_key( 'fresh', $resource, $params ) );
    delete_transient( cimne_api_cache_key( 'stale', $resource, $params ) );
}

/**
 * Invalidates all CIMNE API cache keys logically.
 *
 * Existing transient rows are allowed to expire naturally, but are no longer
 * read because subsequent requests use a new namespace version.
 *
 * @return int New cache version.
 */
function cimne_api_cache_flush_all() {
    $version = cimne_api_cache_version() + 1;
    update_option( 'cimne_api_cache_version', $version, false );

    return $version;
}
