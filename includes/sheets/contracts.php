<?php
/**
 * Contract/project listings sourced from Google Sheets.
 *
 * This module contains the shared data model used by the Innovation Unit
 * contract lists. CENIT uses one spreadsheet with three tabs; the shortcode
 * selects the corresponding source explicitly through its area attribute.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Converts Google Sheets rows into the stable internal contract structure.
 *
 * Column mapping used by the legacy integration:
 * A name
 * B description
 * C client
 * D period
 * E partners
 * F abstract
 * G website
 * H image
 * I files (one URL per line)
 *
 * @param array $rows Raw Google Sheets rows.
 * @return array
 */
function cimne_sheets_normalize_contract_rows( array $rows ) {
    $contracts = array();

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $row = array_pad( array_values( $row ), 9, '' );

        $name        = trim( (string) $row[0] );
        $description = trim( (string) $row[1] );
        $client      = trim( (string) $row[2] );
        $period      = trim( (string) $row[3] );
        $partners    = trim( (string) $row[4] );
        $abstract    = trim( (string) $row[5] );
        $raw_website = trim( (string) $row[6] );
        $raw_image   = trim( (string) $row[7] );
        $raw_files   = trim( (string) $row[8] );

        if (
            '' === $name &&
            '' === $description &&
            '' === $client &&
            '' === $period &&
            '' === $partners &&
            '' === $abstract &&
            '' === $raw_website &&
            '' === $raw_image &&
            '' === $raw_files
        ) {
            continue;
        }

        $website = '';
        if ( '' !== $raw_website && wp_http_validate_url( $raw_website ) ) {
            $website = esc_url_raw( $raw_website, array( 'http', 'https' ) );
        }

        $image = '';
        if ( '' !== $raw_image && wp_http_validate_url( $raw_image ) ) {
            $image = esc_url_raw( $raw_image, array( 'http', 'https' ) );
        }

        $abstract_lines = array();
        if ( '' !== $abstract ) {
            $abstract_lines = preg_split( '/\r\n|\r|\n/', $abstract );
            $abstract_lines = array_values(
                array_filter(
                    array_map( 'trim', $abstract_lines ),
                    static function ( $line ) {
                        return '' !== $line;
                    }
                )
            );
        }

        $files = array();
        if ( '' !== $raw_files ) {
            $file_lines = preg_split( '/\r\n|\r|\n/', $raw_files );

            foreach ( $file_lines as $file_line ) {
                $file_url = trim( (string) $file_line );

                if ( '' === $file_url || ! wp_http_validate_url( $file_url ) ) {
                    continue;
                }

                $file_url = esc_url_raw( $file_url, array( 'http', 'https' ) );
                $path     = (string) wp_parse_url( $file_url, PHP_URL_PATH );
                $label    = basename( untrailingslashit( $path ) );

                if ( '' === $label || '/' === $label || '.' === $label ) {
                    $label = $file_url;
                }

                $files[] = array(
                    'url'   => $file_url,
                    'label' => rawurldecode( $label ),
                );
            }
        }

        $contracts[] = array(
            'name'           => $name,
            'description'    => $description,
            'client'         => $client,
            'period'         => $period,
            'partners'       => $partners,
            'abstract_lines' => $abstract_lines,
            'website'        => $website,
            'image'          => $image,
            'files'          => $files,
        );
    }

    return $contracts;
}

/**
 * Returns normalized contracts for one configured Sheets source.
 *
 * @param string $source_code Sheets source code.
 * @return array|WP_Error
 */
function cimne_sheets_get_contracts( $source_code ) {
    return cimne_sheets_get(
        $source_code,
        array(
            'cache_variant' => 'contracts-v2',
            'transform'     => 'cimne_sheets_normalize_contract_rows',
            'fresh_ttl'     => HOUR_IN_SECONDS,
            'stale_ttl'     => DAY_IN_SECONDS,
        )
    );
}

/**
 * Renders a normalized contracts dataset.
 *
 * @param array $contracts Normalized contracts.
 * @return string
 */
function cimne_sheets_render_contracts( array $contracts ) {
    ob_start();

    get_template_part(
        'template-parts/sheets/contracts',
        null,
        array(
            'contracts' => $contracts,
        )
    );

    return (string) ob_get_clean();
}

/**
 * Maps a public CENIT area slug to the internal Google Sheets source.
 *
 * Keeping this allowlist explicit prevents arbitrary sheet names from being
 * requested through shortcode attributes.
 *
 * @param string $area CENIT area slug.
 * @return string
 */
function cimne_sheets_get_cenit_source_for_area( $area ) {
    $area = sanitize_key( (string) $area );

    $sources = array(
        'urban-mobility'                    => 'cenit-urban-mobility',
        'logistics-and-maritime-transport' => 'cenit-logistics-and-maritime-transport',
        'infrastructure-management'        => 'cenit-infrastructure-management',
    );

    return isset( $sources[ $area ] ) ? $sources[ $area ] : '';
}

/**
 * Shortcode: [cimne_cenit_contracts area="..."]
 *
 * Supported values:
 * - urban-mobility
 * - logistics-and-maritime-transport
 * - infrastructure-management
 *
 * The surrounding Divi module may keep the existing CSS ID
 * "contracts-container". The shortcode does not render a duplicate ID.
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function cimne_cenit_contracts_shortcode( $atts = array() ) {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $atts = shortcode_atts(
        array(
            'area' => '',
        ),
        $atts,
        'cimne_cenit_contracts'
    );

    $area        = sanitize_key( (string) $atts['area'] );
    $source_code = cimne_sheets_get_cenit_source_for_area( $area );

    if ( '' === $source_code ) {
        cimne_sheets_log(
            'CENIT contracts shortcode has an invalid or missing area.',
            array(
                'area' => $area,
            )
        );

        return '<p class="no-results">' . esc_html__( 'Unable to load CENIT projects at this time.', 'cimne' ) . '</p>';
    }

    $contracts = cimne_sheets_get_contracts( $source_code );

    if ( is_wp_error( $contracts ) ) {
        cimne_sheets_log(
            'CENIT contracts component could not be rendered.',
            array(
                'area'       => $area,
                'source'     => $source_code,
                'error_code' => $contracts->get_error_code(),
            )
        );

        return '<p class="no-results">' . esc_html__( 'Unable to load CENIT projects at this time.', 'cimne' ) . '</p>';
    }

    return cimne_sheets_render_contracts( $contracts );
}
add_shortcode( 'cimne_cenit_contracts', 'cimne_cenit_contracts_shortcode' );

/**
 * Registers and returns the request-local source for one BEEGroup tab.
 *
 * The legacy integration already stores the exact Google Sheets tab in the
 * Divi marker's rl-code attribute. Instead of hard-coding a second allowlist,
 * the new shortcode reuses that trusted editor-controlled value directly.
 *
 * @param string $area Existing rl-code / Google Sheets tab name.
 * @return string Empty string when invalid.
 */
function cimne_sheets_get_beegroup_source_for_area( $area ) {
    $area = trim( sanitize_text_field( (string) $area ) );

    if ( '' === $area || strlen( $area ) > 120 ) {
        return '';
    }

    // Do not allow A1 range separators or control characters in a tab name.
    if ( false !== strpos( $area, '!' ) || preg_match( '/[\x00-\x1F\x7F]/', $area ) ) {
        return '';
    }

    $source_code = 'beegroup-' . substr( md5( $area ), 0, 16 );

    $registered = cimne_sheets_register_runtime_source(
        $source_code,
        array(
            'spreadsheet_id' => cimne_sheets_get_beegroup_spreadsheet_id(),
            'sheet_name'     => $area,
            'range'          => 'A2:I',
        )
    );

    return $registered ? $source_code : '';
}

/**
 * Shortcode: [cimne_beegroup_contracts area="..."]
 *
 * The area value must match the configured contract area code exactly.
 * This keeps BEEGroup pages independent from WordPress page slugs and WPML
 * translations.
 *
 * Example:
 * [cimne_beegroup_contracts area="my-area"]
 *
 * @param array $atts Shortcode attributes.
 * @return string
 */
function cimne_beegroup_contracts_shortcode( $atts = array() ) {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $atts = shortcode_atts(
        array(
            'area' => '',
        ),
        $atts,
        'cimne_beegroup_contracts'
    );

    $area        = trim( sanitize_text_field( (string) $atts['area'] ) );
    $source_code = cimne_sheets_get_beegroup_source_for_area( $area );

    if ( '' === $source_code ) {
        cimne_sheets_log(
            'BEEGroup contracts shortcode has an invalid or missing area.',
            array(
                'area' => $area,
            )
        );

        return '<p class="no-results">' . esc_html__( 'Unable to load BEEGroup projects at this time.', 'cimne' ) . '</p>';
    }

    $contracts = cimne_sheets_get_contracts( $source_code );

    if ( is_wp_error( $contracts ) ) {
        cimne_sheets_log(
            'BEEGroup contracts component could not be rendered.',
            array(
                'area'       => $area,
                'source'     => $source_code,
                'error_code' => $contracts->get_error_code(),
            )
        );

        return '<p class="no-results">' . esc_html__( 'Unable to load BEEGroup projects at this time.', 'cimne' ) . '</p>';
    }

    return cimne_sheets_render_contracts( $contracts );
}
add_shortcode( 'cimne_beegroup_contracts', 'cimne_beegroup_contracts_shortcode' );

