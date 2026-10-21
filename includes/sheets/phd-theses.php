<?php
/**
 * PhD Theses component sourced from Google Sheets.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Converts a Google Sheets row into the stable internal thesis structure.
 *
 * Column mapping:
 * A title
 * B reading date
 * C author
 * D directors
 * E programme
 * F qualification
 * G URL
 *
 * @param array $rows Raw Google Sheets rows.
 * @return array
 */
function cimne_sheets_normalize_phd_theses_rows( array $rows ) {
    $theses = array();

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $row = array_pad( array_values( $row ), 7, '' );

        $title         = trim( (string) $row[0] );
        $reading_date  = trim( (string) $row[1] );
        $author        = trim( (string) $row[2] );
        $directors     = trim( (string) $row[3] );
        $programme     = trim( (string) $row[4] );
        $qualification = trim( (string) $row[5] );
        $raw_url       = trim( (string) $row[6] );

        if (
            '' === $title &&
            '' === $reading_date &&
            '' === $author &&
            '' === $directors &&
            '' === $programme &&
            '' === $qualification &&
            '' === $raw_url
        ) {
            continue;
        }

        $url = '';

        if ( '' !== $raw_url && '-' !== $raw_url && wp_http_validate_url( $raw_url ) ) {
            $url = esc_url_raw( $raw_url, array( 'http', 'https' ) );
        }

        $reading_date_iso = '';

        if ( preg_match( '/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $reading_date, $matches ) ) {
            $day   = (int) $matches[1];
            $month = (int) $matches[2];
            $year  = (int) $matches[3];

            if ( checkdate( $month, $day, $year ) ) {
                $reading_date_iso = sprintf( '%04d-%02d-%02d', $year, $month, $day );
            }
        }

        $theses[] = array(
            'title'            => $title,
            'reading_date'     => $reading_date,
            'reading_date_iso' => $reading_date_iso,
            'author'           => $author,
            'directors'        => $directors,
            'programme'        => $programme,
            'qualification'    => $qualification,
            'url'              => $url,
        );
    }

    return $theses;
}

/**
 * Returns the normalized PhD thesis dataset.
 *
 * @return array|WP_Error
 */
function cimne_sheets_get_phd_theses() {
    return cimne_sheets_get(
        'phd-theses',
        array(
            'cache_variant' => 'phd-theses-v1',
            'transform'     => 'cimne_sheets_normalize_phd_theses_rows',
            'fresh_ttl'     => HOUR_IN_SECONDS,
            'stale_ttl'     => DAY_IN_SECONDS,
        )
    );
}

/**
 * Renders PhD theses through a template part.
 *
 * @param array $theses Normalized theses.
 * @return string
 */
function cimne_sheets_render_phd_theses( array $theses ) {
    ob_start();

    get_template_part(
        'template-parts/sheets/phd-theses',
        null,
        array(
            'theses' => $theses,
        )
    );

    return (string) ob_get_clean();
}

/**
 * Shortcode: [cimne_phd_theses]
 *
 * The surrounding Divi module may keep the existing CSS ID
 * "theses-container". The shortcode deliberately does not render a second
 * element with that ID.
 *
 * @return string
 */
function cimne_phd_theses_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $theses = cimne_sheets_get_phd_theses();

    if ( is_wp_error( $theses ) ) {
        cimne_sheets_log(
            'PhD Theses component could not be rendered.',
            array(
                'error_code' => $theses->get_error_code(),
            )
        );

        return '<p class="no-results">' . esc_html__( 'Unable to load PhD theses at this time.', 'cimne' ) . '</p>';
    }

    return cimne_sheets_render_phd_theses( $theses );
}
add_shortcode( 'cimne_phd_theses', 'cimne_phd_theses_shortcode' );
