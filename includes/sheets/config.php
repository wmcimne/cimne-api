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
    $sources = array(
        'phd-theses' => array(
            'spreadsheet_id' => '1au3CsOYkxS2yLhYX-YTsc7XCGNzCBxsvGO5xO1tyxuM',
            'sheet_name'     => 'phd-theses',
            'range'          => 'A2:G',
        ),
    );

    return apply_filters( 'cimne_sheets_sources', $sources );
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
