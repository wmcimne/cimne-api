<?php
/**
 * Researcher awards component sourced from Google Sheets.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Converts Google Sheets rows into the stable internal researcher-awards model.
 *
 * Column mapping from the "awards" tab:
 * A Name
 * B Position
 * C Img URL
 * D Awards (one award per line)
 *
 * @param array $rows Raw Google Sheets rows.
 * @return array
 */
function cimne_sheets_normalize_researcher_awards_rows( array $rows ) {
    $researchers = array();

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $row = array_pad( array_values( $row ), 4, '' );

        $name       = trim( wp_strip_all_tags( (string) $row[0] ) );
        $position   = trim( wp_strip_all_tags( (string) $row[1] ) );
        $raw_image  = trim( (string) $row[2] );
        $raw_awards = trim( (string) $row[3] );

        if ( '' === $name && '' === $position && '' === $raw_image && '' === $raw_awards ) {
            continue;
        }

        /*
         * The legacy sheet contains root-relative image values as well as
         * potentially absolute URLs. Preserve both forms, but reject traversal
         * sequences and control characters in relative paths.
         */
        $image = '';

        if ( '' !== $raw_image && wp_http_validate_url( $raw_image ) ) {
            $image = esc_url_raw( $raw_image, array( 'http', 'https' ) );
        } elseif (
            '' !== $raw_image &&
            '/' === substr( $raw_image, 0, 1 ) &&
            false === strpos( $raw_image, '..' ) &&
            ! preg_match( '/[\x00-\x1F\x7F]/', $raw_image )
        ) {
            $image = $raw_image;
        }

        $awards = array();

        if ( '' !== $raw_awards ) {
            $award_lines = preg_split( '/\r\n|\r|\n/', $raw_awards );

            foreach ( $award_lines as $award_line ) {
                $award_line = trim( wp_strip_all_tags( (string) $award_line ) );

                if ( '' !== $award_line ) {
                    $awards[] = $award_line;
                }
            }
        }

        $researchers[] = array(
            'name'     => $name,
            'position' => $position,
            'image'    => $image,
            'awards'   => $awards,
        );
    }

    return $researchers;
}

/**
 * Returns the normalized researcher-awards dataset.
 *
 * @return array|WP_Error
 */
function cimne_sheets_get_researcher_awards() {
    return cimne_sheets_get(
        'researcher-awards',
        array(
            'cache_variant' => 'researcher-awards-v1',
            'transform'     => 'cimne_sheets_normalize_researcher_awards_rows',
            'fresh_ttl'     => HOUR_IN_SECONDS,
            'stale_ttl'     => DAY_IN_SECONDS,
        )
    );
}

/**
 * Renders the researcher-awards dataset through a template part.
 *
 * @param array $researchers Normalized researchers.
 * @return string
 */
function cimne_sheets_render_researcher_awards( array $researchers ) {
    ob_start();

    get_template_part(
        'template-parts/sheets/researcher-awards',
        null,
        array(
            'researchers' => $researchers,
        )
    );

    return (string) ob_get_clean();
}

/**
 * Shortcode: [cimne_researcher_awards]
 *
 * Intended for the "Awards to Researchers" tab on /about/awards/.
 * The surrounding Divi module may keep the existing CSS ID
 * "awards-container"; this shortcode deliberately does not duplicate it.
 *
 * @return string
 */
function cimne_researcher_awards_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $researchers = cimne_sheets_get_researcher_awards();

    if ( is_wp_error( $researchers ) ) {
        cimne_sheets_log(
            'Researcher Awards component could not be rendered.',
            array(
                'error_code' => $researchers->get_error_code(),
            )
        );

        return '<p class="no-results">' . esc_html__( 'Unable to load researcher awards at this time.', 'cimne' ) . '</p>';
    }

    return cimne_sheets_render_researcher_awards( $researchers );
}
add_shortcode( 'cimne_researcher_awards', 'cimne_researcher_awards_shortcode' );

/**
 * Returns Eugenio Oñate's awards from the normalized researcher-awards data.
 *
 * The same cached Google Sheets dataset used by [cimne_researcher_awards] is
 * reused; no second Sheets request is made for this shortcode.
 *
 * @return array|WP_Error
 */
function cimne_sheets_get_onate_awards() {
    $researchers = cimne_sheets_get_researcher_awards();

    if ( is_wp_error( $researchers ) ) {
        return $researchers;
    }

    foreach ( $researchers as $researcher ) {
        $name = isset( $researcher['name'] ) ? (string) $researcher['name'] : '';
        $key  = strtolower( remove_accents( $name ) );

        if ( false !== strpos( $key, 'onate' ) ) {
            return isset( $researcher['awards'] ) && is_array( $researcher['awards'] )
                ? $researcher['awards']
                : array();
        }
    }

    return array();
}

/**
 * Renders Eugenio Oñate's awards using the compact legacy-compatible layout.
 *
 * @param array $awards Award strings.
 * @return string
 */
function cimne_sheets_render_onate_awards( array $awards ) {
    ob_start();

    get_template_part(
        'template-parts/sheets/onate-awards',
        null,
        array(
            'awards' => $awards,
        )
    );

    return (string) ob_get_clean();
}

/**
 * Shortcode: [cimne_onate_awards]
 *
 * Intended for the Eugenio Oñate founder page. It reproduces the compact
 * legacy mode that showed only the founder's award list, without duplicating
 * the full researcher card used on /about/awards/.
 *
 * @return string
 */
function cimne_onate_awards_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $awards = cimne_sheets_get_onate_awards();

    if ( is_wp_error( $awards ) ) {
        cimne_sheets_log(
            'Eugenio Oñate Awards component could not be rendered.',
            array(
                'error_code' => $awards->get_error_code(),
            )
        );

        return '<p class="no-results">' . esc_html__( 'Unable to load awards at this time.', 'cimne' ) . '</p>';
    }

    return cimne_sheets_render_onate_awards( $awards );
}
add_shortcode( 'cimne_onate_awards', 'cimne_onate_awards_shortcode' );

