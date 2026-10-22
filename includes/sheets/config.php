<?php
/**
 * Configuration for Google Sheets sources used by the CIMNE child theme.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the registered Google Sheets sources.
 *
 * Spreadsheet IDs are public identifiers, not credentials. The API key is
 * deliberately kept out of the theme and must be provided separately through
 * CIMNE_GOOGLE_SHEETS_API_KEY (normally in wp-config.php).
 *
 * @return array
 */
function cimne_sheets_get_sources() {
    $phd_spreadsheet_id   = '1au3CsOYkxS2yLhYX-YTsc7XCGNzCBxsvGO5xO1tyxuM';
    $cenit_spreadsheet_id = '1sSgLMlmaWTPSyaVZxSh2OETP--O9NevswQgc1RwWxyQ';

    $sources = array(
        'phd-theses' => array(
            'spreadsheet_id' => $phd_spreadsheet_id,
            'sheet_name'     => 'phd-theses',
            'range'          => 'A2:G',
        ),

        'researcher-awards' => array(
            'spreadsheet_id' => $phd_spreadsheet_id,
            'sheet_name'     => 'awards',
            'range'          => 'A2:D',
        ),

        'oko-card' => array(
            'spreadsheet_id' => '1dElHzsKJ-0FD6ut_2oxKJOYLcUca3lWQ63--H2mlCxM',
            'sheet_name'     => 'oko-card',
            'range'          => 'A2:E',
        ),

        /*
         * CENIT contracts.
         *
         * One spreadsheet contains three tabs. Each tab is registered as an
         * independent source so its cache key and Google Sheets request remain
         * completely isolated from the other CENIT areas.
         */
        'cenit-urban-mobility' => array(
            'spreadsheet_id' => $cenit_spreadsheet_id,
            'sheet_name'     => 'urban-mobility',
            'range'          => 'A2:I',
        ),
        'cenit-logistics-and-maritime-transport' => array(
            'spreadsheet_id' => $cenit_spreadsheet_id,
            'sheet_name'     => 'logistics-and-maritime-transport',
            'range'          => 'A2:I',
        ),
        'cenit-infrastructure-management' => array(
            'spreadsheet_id' => $cenit_spreadsheet_id,
            'sheet_name'     => 'infrastructure-management',
            'range'          => 'A2:I',
        ),
    );

    return apply_filters( 'cimne_sheets_sources', $sources );
}

/**
 * Registers a request-local Google Sheets source.
 *
 * This is useful for integrations where a trusted shortcode attribute maps
 * directly to an existing rl-code / sheet tab. Runtime sources are available
 * only during the current WordPress request and are never persisted.
 *
 * @param string $source_code Unique source code.
 * @param array  $source      Source configuration.
 * @return bool
 */
function cimne_sheets_register_runtime_source( $source_code, array $source ) {
    $source_code = sanitize_key( (string) $source_code );

    if ( '' === $source_code ) {
        return false;
    }

    if ( ! isset( $GLOBALS['cimne_sheets_runtime_sources'] ) || ! is_array( $GLOBALS['cimne_sheets_runtime_sources'] ) ) {
        $GLOBALS['cimne_sheets_runtime_sources'] = array();
    }

    $GLOBALS['cimne_sheets_runtime_sources'][ $source_code ] = $source;

    return true;
}

/**
 * Appends request-local sources to the normal source registry.
 *
 * @param array $sources Registered sources.
 * @return array
 */
function cimne_sheets_append_runtime_sources( array $sources ) {
    if ( empty( $GLOBALS['cimne_sheets_runtime_sources'] ) || ! is_array( $GLOBALS['cimne_sheets_runtime_sources'] ) ) {
        return $sources;
    }

    foreach ( $GLOBALS['cimne_sheets_runtime_sources'] as $source_code => $source ) {
        if ( ! is_array( $source ) ) {
            continue;
        }

        $sources[ $source_code ] = $source;
    }

    return $sources;
}
add_filter( 'cimne_sheets_sources', 'cimne_sheets_append_runtime_sources', 20 );

/**
 * Returns the BEEGroup contracts spreadsheet ID.
 *
 * @return string
 */
function cimne_sheets_get_beegroup_spreadsheet_id() {
    return '10pMxXzD0-c_uuiIy788Tbm49imFE4x5YatJi_gYdjO4';
}

/**
 * Returns one registered source.
 *
 * @param string $source_code Source code.
 * @return array|WP_Error
 */
function cimne_sheets_get_source( $source_code ) {
    $source_code = sanitize_key( (string) $source_code );
    $sources     = cimne_sheets_get_sources();

    if ( ! isset( $sources[ $source_code ] ) || ! is_array( $sources[ $source_code ] ) ) {
        return new WP_Error(
            'cimne_sheets_unknown_source',
            __( 'Unknown Google Sheets source.', 'cimne' )
        );
    }

    $source = $sources[ $source_code ];

    if (
        empty( $source['spreadsheet_id'] ) ||
        empty( $source['sheet_name'] ) ||
        empty( $source['range'] )
    ) {
        return new WP_Error(
            'cimne_sheets_invalid_source',
            __( 'The Google Sheets source configuration is incomplete.', 'cimne' )
        );
    }

    return $source;
}

/**
 * Returns the server-side Google Sheets API key.
 *
 * The recommended definition is in wp-config.php:
 * define( 'CIMNE_GOOGLE_SHEETS_API_KEY', '...' );
 *
 * An environment variable with the same name is also supported.
 *
 * @return string
 */
function cimne_sheets_get_api_key() {
    $api_key = '';

    if ( defined( 'CIMNE_GOOGLE_SHEETS_API_KEY' ) ) {
        $api_key = (string) CIMNE_GOOGLE_SHEETS_API_KEY;
    } else {
        $environment_key = getenv( 'CIMNE_GOOGLE_SHEETS_API_KEY' );

        if ( false !== $environment_key ) {
            $api_key = (string) $environment_key;
        }
    }

    return trim( (string) apply_filters( 'cimne_sheets_api_key', $api_key ) );
}
