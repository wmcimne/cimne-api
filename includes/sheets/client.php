<?php
/**
 * Small server-side client for public Google Sheets data.
 *
 * The client deliberately lives outside includes/api/: the latter is reserved
 * for the corporate CIMNE web services. Google Sheets is a separate source.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Writes a Sheets integration message to the WordPress debug log.
 *
 * @param string $message Message.
 * @param array  $context Optional context.
 * @return void
 */
function cimne_sheets_log( $message, array $context = array() ) {
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return;
    }

    $suffix = '';

    if ( ! empty( $context ) ) {
        $encoded = wp_json_encode( $context );
        $suffix  = false !== $encoded ? ' ' . $encoded : '';
    }

    error_log( '[CIMNE Sheets] ' . $message . $suffix ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
}

/**
 * Builds the Google Sheets Values API URL for one configured source.
 *
 * FORMATTED_VALUE is intentional: the PhD thesis Reading column is displayed
 * exactly as formatted in the Sheet (DD/MM/YYYY).
 *
 * @param array  $source  Source configuration.
 * @param string $api_key API key.
 * @return string
 */
function cimne_sheets_build_values_url( array $source, $api_key ) {
    $sheet_name  = str_replace( "'", "''", $source['sheet_name'] );
    $sheet_range = "'" . $sheet_name . "'!" . $source['range'];
    $base_url    = sprintf(
        'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s',
        rawurlencode( $source['spreadsheet_id'] ),
        rawurlencode( $sheet_range )
    );

    return add_query_arg(
        array(
            'key'               => $api_key,
            'majorDimension'    => 'ROWS',
            'valueRenderOption' => 'FORMATTED_VALUE',
        ),
        $base_url
    );
}

/**
 * Builds cache keys for a Sheets source.
 *
 * @param string $source_code   Source code.
 * @param array  $source        Source config.
 * @param string $cache_variant Cache variant.
 * @return array
 */
function cimne_sheets_get_cache_keys( $source_code, array $source, $cache_variant ) {
    $fingerprint = md5(
        wp_json_encode(
            array(
                'source'  => $source_code,
                'sheet'   => $source['spreadsheet_id'],
                'name'    => $source['sheet_name'],
                'range'   => $source['range'],
                'variant' => $cache_variant,
            )
        )
    );

    return array(
        'fresh' => 'cimne_sh_f_' . $fingerprint,
        'stale' => 'cimne_sh_s_' . $fingerprint,
    );
}

/**
 * Reads and optionally transforms a Google Sheets Values API response.
 *
 * The transformed payload is cached, rather than the raw Google response.
 * This keeps the cache small and gives templates a stable internal structure.
 *
 * Options:
 * - cache_variant (string)
 * - transform (callable receiving array $rows, array $source)
 * - fresh_ttl (seconds, default 1 hour)
 * - stale_ttl (seconds, default 24 hours)
 * - force_refresh (bool)
 *
 * @param string $source_code Source code.
 * @param array  $options     Options.
 * @return mixed|WP_Error
 */
function cimne_sheets_get( $source_code, array $options = array() ) {
    $source = cimne_sheets_get_source( $source_code );

    if ( is_wp_error( $source ) ) {
        return $source;
    }

    $defaults = array(
        'cache_variant' => 'v1',
        'transform'     => null,
        'fresh_ttl'     => HOUR_IN_SECONDS,
        'stale_ttl'     => DAY_IN_SECONDS,
        'force_refresh' => false,
    );

    $options = wp_parse_args( $options, $defaults );

    $fresh_ttl = max(
        60,
        (int) apply_filters(
            'cimne_sheets_fresh_ttl',
            (int) $options['fresh_ttl'],
            $source_code
        )
    );
    $stale_ttl = max(
        $fresh_ttl,
        (int) apply_filters(
            'cimne_sheets_stale_ttl',
            (int) $options['stale_ttl'],
            $source_code
        )
    );

    $cache_keys = cimne_sheets_get_cache_keys(
        $source_code,
        $source,
        sanitize_key( (string) $options['cache_variant'] )
    );

    if ( empty( $options['force_refresh'] ) ) {
        $cached = get_transient( $cache_keys['fresh'] );

        if ( false !== $cached ) {
            return $cached;
        }
    }

    $stale = get_transient( $cache_keys['stale'] );
    $api_key = cimne_sheets_get_api_key();

    if ( '' === $api_key ) {
        cimne_sheets_log(
            'Google Sheets API key is not configured.',
            array( 'source' => $source_code )
        );

        if ( false !== $stale ) {
            return $stale;
        }

        return new WP_Error(
            'cimne_sheets_missing_api_key',
            __( 'Google Sheets API key is not configured.', 'cimne' )
        );
    }

    $url = cimne_sheets_build_values_url( $source, $api_key );
    $response = wp_remote_get(
        $url,
        array(
            'timeout'     => 12,
            'redirection' => 3,
            'headers'     => array(
                'Accept' => 'application/json',
            ),
        )
    );

    if ( is_wp_error( $response ) ) {
        cimne_sheets_log(
            'Google Sheets request failed.',
            array(
                'source' => $source_code,
                'error'  => $response->get_error_code(),
            )
        );

        if ( false !== $stale ) {
            return $stale;
        }

        return $response;
    }

    $status_code = (int) wp_remote_retrieve_response_code( $response );
    $body        = (string) wp_remote_retrieve_body( $response );

    if ( 200 !== $status_code ) {
        $google_error = '';
        $error_payload = json_decode( $body, true );

        if (
            is_array( $error_payload ) &&
            isset( $error_payload['error']['message'] )
        ) {
            $google_error = sanitize_text_field(
                (string) $error_payload['error']['message']
            );
        }

        cimne_sheets_log(
            'Google Sheets returned a non-200 response.',
            array(
                'source'       => $source_code,
                'status'       => $status_code,
                'google_error' => $google_error,
            )
        );

        if ( false !== $stale ) {
            return $stale;
        }

        return new WP_Error(
            'cimne_sheets_http_error',
            __( 'Google Sheets returned an unexpected response.', 'cimne' ),
            array( 'status' => $status_code )
        );
    }

    $decoded = json_decode( $body, true );

    if ( ! is_array( $decoded ) || ! isset( $decoded['values'] ) || ! is_array( $decoded['values'] ) ) {
        cimne_sheets_log(
            'Google Sheets returned an invalid payload.',
            array( 'source' => $source_code )
        );

        if ( false !== $stale ) {
            return $stale;
        }

        return new WP_Error(
            'cimne_sheets_invalid_payload',
            __( 'Google Sheets returned invalid data.', 'cimne' )
        );
    }

    $data = $decoded['values'];

    if ( is_callable( $options['transform'] ) ) {
        $data = call_user_func( $options['transform'], $data, $source );

        if ( is_wp_error( $data ) ) {
            if ( false !== $stale ) {
                return $stale;
            }

            return $data;
        }
    }

    set_transient( $cache_keys['fresh'], $data, $fresh_ttl );
    set_transient( $cache_keys['stale'], $data, $stale_ttl );

    return $data;
}
