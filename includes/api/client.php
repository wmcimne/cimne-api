<?php
/**
 * HTTP client for the new CIMNE public web services.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the API base URL.
 *
 * @return string
 */
function cimne_api_base_url() {
    return untrailingslashit(
        apply_filters( 'cimne_api_base_url', 'https://api.cimne.com/webcimne' )
    );
}

/**
 * Resources currently supported by the integration layer.
 *
 * @return array<string,string>
 */
function cimne_api_resources() {
    return apply_filters(
        'cimne_api_resources',
        array(
            'research_units' => 'research_units',
            'projects'       => 'projects',
            'opts_projects'  => 'opts_projects',
            'directorio'      => 'directorio',
            'persona'         => 'persona',
        )
    );
}

/**
 * Default fresh-cache TTL by resource.
 *
 * @param string $resource API resource.
 * @return int Seconds.
 */
function cimne_api_cache_ttl( $resource ) {
    $ttls = array(
        'research_units' => HOUR_IN_SECONDS,
        'projects'       => 30 * MINUTE_IN_SECONDS,
        'opts_projects'  => HOUR_IN_SECONDS,
        'directorio'      => HOUR_IN_SECONDS,
        'persona'         => HOUR_IN_SECONDS,
    );

    $ttl = isset( $ttls[ $resource ] ) ? $ttls[ $resource ] : 30 * MINUTE_IN_SECONDS;

    return max( 1, (int) apply_filters( 'cimne_api_cache_ttl', $ttl, $resource ) );
}

/**
 * Last-known-good cache lifetime.
 *
 * @param string $resource API resource.
 * @return int Seconds.
 */
function cimne_api_stale_ttl( $resource ) {
    return max(
        cimne_api_cache_ttl( $resource ),
        (int) apply_filters( 'cimne_api_stale_ttl', DAY_IN_SECONDS, $resource )
    );
}

/**
 * Normalizes query parameters before sending them to the API.
 *
 * Arrays are converted to comma-separated lists because the current services
 * use that convention for multi-value filters.
 *
 * @param array $params Raw request params.
 * @return array
 */
function cimne_api_normalize_query_params( array $params ) {
    $normalized = array();

    foreach ( $params as $key => $value ) {
        if ( null === $value || '' === $value ) {
            continue;
        }

        if ( is_array( $value ) ) {
            $value = array_values(
                array_filter(
                    $value,
                    static function ( $item ) {
                        return null !== $item && '' !== $item;
                    }
                )
            );

            if ( empty( $value ) ) {
                continue;
            }

            $value = implode( ',', array_map( 'strval', $value ) );
        } elseif ( is_bool( $value ) ) {
            $value = $value ? '1' : '0';
        } elseif ( ! is_scalar( $value ) ) {
            continue;
        } else {
            $value = (string) $value;
        }

        $normalized[ (string) $key ] = $value;
    }

    ksort( $normalized );

    return $normalized;
}

/**
 * Builds an endpoint URL.
 *
 * @param string $resource API resource.
 * @param array  $params   GET parameters.
 * @return string|WP_Error
 */
function cimne_api_build_url( $resource, array $params = array() ) {
    $resources = cimne_api_resources();

    if ( ! isset( $resources[ $resource ] ) ) {
        return new WP_Error(
            'cimne_api_unknown_resource',
            sprintf( 'Unknown CIMNE API resource: %s', $resource )
        );
    }

    $url    = cimne_api_base_url() . '/' . ltrim( $resources[ $resource ], '/' );
    $params = cimne_api_normalize_query_params( $params );

    return empty( $params ) ? $url : add_query_arg( $params, $url );
}

/**
 * Logs API diagnostics only when WordPress debugging is enabled.
 *
 * No response bodies are logged.
 *
 * @param string $message Log message.
 * @param array  $context Optional context.
 * @return void
 */
function cimne_api_log( $message, array $context = array() ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return;
    }

    $suffix = empty( $context ) ? '' : ' ' . wp_json_encode( $context );
    error_log( '[CIMNE API] ' . $message . $suffix ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

/**
 * Builds cache parameters for a request.
 *
 * cache_variant never reaches the external API. It only prevents a reduced,
 * transformed payload from colliding with a raw cache entry for the same URL.
 *
 * @param array  $params        Normalized API params.
 * @param string $cache_variant Optional cache variant.
 * @return array
 */
function cimne_api_cache_params( array $params, $cache_variant = '' ) {
    $cache_params = $params;
    $cache_variant = sanitize_key( (string) $cache_variant );

    if ( '' !== $cache_variant ) {
        $cache_params['__cache_variant'] = $cache_variant;
    }

    return $cache_params;
}

/**
 * Returns stale data if available; otherwise returns the supplied error.
 *
 * @param string   $resource     API resource.
 * @param array    $cache_params Cache-key parameters.
 * @param WP_Error $error        Request error.
 * @param string   $url          Requested URL.
 * @param float    $started      Request start timestamp.
 * @return array|WP_Error
 */
function cimne_api_stale_or_error( $resource, array $cache_params, WP_Error $error, $url, $started ) {
    $stale = cimne_api_cache_get( 'stale', $resource, $cache_params );

    if ( false !== $stale ) {
        cimne_api_log(
            'API request failed; serving stale cache.',
            array(
                'resource'    => $resource,
                'error_code'  => $error->get_error_code(),
                'duration_ms' => (int) round( ( microtime( true ) - $started ) * 1000 ),
            )
        );

        return array(
            'data' => $stale,
            'meta' => array(
                'source'          => 'stale',
                'resource'        => $resource,
                'url'             => $url,
                'fallback_reason' => $error->get_error_code(),
            ),
        );
    }

    cimne_api_log(
        'API request failed and no stale cache is available.',
        array(
            'resource'    => $resource,
            'error_code'  => $error->get_error_code(),
            'duration_ms' => (int) round( ( microtime( true ) - $started ) * 1000 ),
        )
    );

    return $error;
}

/**
 * Applies an optional transformation before data is cached.
 *
 * This is used by Projects to discard API fields that are not part of the
 * current web design. The cache therefore stores only the reduced payload.
 *
 * @param array         $data      Decoded API data.
 * @param callable|null $transform Transform callback.
 * @param string        $resource  API resource.
 * @param array         $params    API params.
 * @return array|WP_Error
 */
function cimne_api_transform_response( array $data, $transform, $resource, array $params ) {
    if ( ! is_callable( $transform ) ) {
        return $data;
    }

    try {
        $transformed = call_user_func( $transform, $data, $resource, $params );
    } catch ( Throwable $exception ) {
        return new WP_Error(
            'cimne_api_transform_error',
            'CIMNE API response transformation failed.'
        );
    }

    if ( ! is_array( $transformed ) ) {
        return new WP_Error(
            'cimne_api_invalid_transformed_payload',
            'CIMNE API response transformation returned an unexpected payload.'
        );
    }

    return $transformed;
}

/**
 * Performs one GET request against the CIMNE API.
 *
 * Result shape on success:
 * [
 *   'data' => array(...),
 *   'meta' => [ 'source' => 'fresh|api|stale', ... ],
 * ]
 *
 * Supported options:
 * - force_refresh (bool)
 * - timeout (int)
 * - cache_variant (string): only affects the transient key.
 * - transform (callable): applied before fresh/stale cache is written.
 *
 * @param string $resource API resource.
 * @param array  $params   GET parameters.
 * @param array  $options  Request options.
 * @return array|WP_Error
 */
function cimne_api_request( $resource, array $params = array(), array $options = array() ) {
    $params        = cimne_api_normalize_query_params( $params );
    $force_refresh = ! empty( $options['force_refresh'] );
    $cache_variant = isset( $options['cache_variant'] ) ? (string) $options['cache_variant'] : '';
    $transform     = isset( $options['transform'] ) ? $options['transform'] : null;
    $cache_params  = cimne_api_cache_params( $params, $cache_variant );

    if ( ! $force_refresh ) {
        $fresh = cimne_api_cache_get( 'fresh', $resource, $cache_params );

        if ( false !== $fresh ) {
            return array(
                'data' => $fresh,
                'meta' => array(
                    'source'   => 'fresh',
                    'resource' => $resource,
                ),
            );
        }
    }

    $url = cimne_api_build_url( $resource, $params );

    if ( is_wp_error( $url ) ) {
        return $url;
    }

    $timeout = isset( $options['timeout'] ) ? (int) $options['timeout'] : 8;
    $timeout = max( 1, min( 30, $timeout ) );
    $started = microtime( true );

    $response = wp_remote_get(
        $url,
        array(
            'timeout'     => $timeout,
            'redirection' => 3,
            'sslverify'   => true,
            'headers'     => array(
                'Accept' => 'application/json',
            ),
            'user-agent'  => 'CIMNE-WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url( '/' ),
        )
    );

    if ( is_wp_error( $response ) ) {
        return cimne_api_stale_or_error( $resource, $cache_params, $response, $url, $started );
    }

    $status = (int) wp_remote_retrieve_response_code( $response );

    if ( 200 !== $status ) {
        $error = new WP_Error(
            'cimne_api_http_error',
            sprintf( 'CIMNE API returned HTTP %d.', $status ),
            array( 'status' => $status )
        );

        return cimne_api_stale_or_error( $resource, $cache_params, $error, $url, $started );
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( JSON_ERROR_NONE !== json_last_error() ) {
        $error = new WP_Error(
            'cimne_api_invalid_json',
            'CIMNE API returned invalid JSON.',
            array( 'json_error' => json_last_error_msg() )
        );

        return cimne_api_stale_or_error( $resource, $cache_params, $error, $url, $started );
    }

    if ( ! is_array( $data ) ) {
        $error = new WP_Error(
            'cimne_api_invalid_payload',
            'CIMNE API returned an unexpected payload.'
        );

        return cimne_api_stale_or_error( $resource, $cache_params, $error, $url, $started );
    }

    $data = cimne_api_transform_response( $data, $transform, $resource, $params );

    if ( is_wp_error( $data ) ) {
        return cimne_api_stale_or_error( $resource, $cache_params, $data, $url, $started );
    }

    cimne_api_cache_set( 'fresh', $resource, $cache_params, $data, cimne_api_cache_ttl( $resource ) );
    cimne_api_cache_set( 'stale', $resource, $cache_params, $data, cimne_api_stale_ttl( $resource ) );

    cimne_api_log(
        'API request completed.',
        array(
            'resource'    => $resource,
            'items'       => count( $data ),
            'duration_ms' => (int) round( ( microtime( true ) - $started ) * 1000 ),
            'variant'     => $cache_variant,
        )
    );

    return array(
        'data' => $data,
        'meta' => array(
            'source'      => 'api',
            'resource'    => $resource,
            'url'         => $url,
            'duration_ms' => (int) round( ( microtime( true ) - $started ) * 1000 ),
        ),
    );
}

/**
 * Convenience wrapper returning only decoded/transformed API data.
 *
 * @param string $resource API resource.
 * @param array  $params   GET parameters.
 * @param array  $options  Request options.
 * @return array|WP_Error
 */
function cimne_api_get( $resource, array $params = array(), array $options = array() ) {
    $result = cimne_api_request( $resource, $params, $options );

    if ( is_wp_error( $result ) ) {
        return $result;
    }

    return $result['data'];
}
