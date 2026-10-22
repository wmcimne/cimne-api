<?php
/**
 * OKO Card social links sourced from Google Sheets.
 *
 * Google Sheets is queried server-side. The shortcode renders only a small
 * hidden data carrier; the existing Divi social buttons keep their markup and
 * styling. A dedicated page-specific JS file copies the sanitized links into
 * those existing anchors.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Converts raw Google Sheets rows into the stable OKO Card structure.
 *
 * Legacy column mapping:
 * A id
 * B email
 * C link
 * D linkedin
 * E x
 *
 * @param array $rows Raw Google Sheets rows.
 * @return array
 */
function cimne_sheets_normalize_oko_card_rows( array $rows ) {
    $cards = array();

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $row = array_pad( array_values( $row ), 5, '' );

        $id           = trim( sanitize_text_field( (string) $row[0] ) );
        $raw_email    = trim( (string) $row[1] );
        $raw_link     = trim( (string) $row[2] );
        $raw_linkedin = trim( (string) $row[3] );
        $raw_x        = trim( (string) $row[4] );

        if (
            '' === $id &&
            '' === $raw_email &&
            '' === $raw_link &&
            '' === $raw_linkedin &&
            '' === $raw_x
        ) {
            continue;
        }

        // Rows without an ID cannot be addressed from ?id=... and are ignored.
        if ( '' === $id ) {
            continue;
        }

        $email = sanitize_email( $raw_email );

        if ( '' !== $email && ! is_email( $email ) ) {
            $email = '';
        }

        $cards[] = array(
            'id'       => $id,
            'email'    => $email,
            'link'     => cimne_sheets_normalize_oko_url( $raw_link ),
            'linkedin' => cimne_sheets_normalize_oko_url( $raw_linkedin ),
            'x'        => cimne_sheets_normalize_oko_url( $raw_x ),
        );
    }

    return $cards;
}

/**
 * Validates one public OKO Card URL.
 *
 * @param string $url Raw URL.
 * @return string
 */
function cimne_sheets_normalize_oko_url( $url ) {
    $url = trim( (string) $url );

    if ( '' === $url || ! wp_http_validate_url( $url ) ) {
        return '';
    }

    return esc_url_raw( $url, array( 'http', 'https' ) );
}

/**
 * Returns all normalized OKO cards.
 *
 * @return array|WP_Error
 */
function cimne_sheets_get_oko_cards() {
    return cimne_sheets_get(
        'oko-card',
        array(
            'cache_variant' => 'oko-card-v1',
            'transform'     => 'cimne_sheets_normalize_oko_card_rows',
            'fresh_ttl'     => HOUR_IN_SECONDS,
            'stale_ttl'     => DAY_IN_SECONDS,
        )
    );
}

/**
 * Returns one card by its public query-string ID.
 *
 * @param string $requested_id Requested ID.
 * @return array|WP_Error|null
 */
function cimne_sheets_get_oko_card_by_id( $requested_id ) {
    $requested_id = trim( sanitize_text_field( (string) $requested_id ) );

    if ( '' === $requested_id || strlen( $requested_id ) > 120 ) {
        return null;
    }

    $cards = cimne_sheets_get_oko_cards();

    if ( is_wp_error( $cards ) ) {
        return $cards;
    }

    foreach ( $cards as $card ) {
        if ( ! is_array( $card ) || ! isset( $card['id'] ) ) {
            continue;
        }

        if ( (string) $card['id'] === $requested_id ) {
            return $card;
        }
    }

    return null;
}

/**
 * Shortcode: [cimne_oko_card_social]
 *
 * The public card ID continues to come from the existing URL contract:
 * /oko-card/?id=VALUE
 *
 * The shortcode intentionally does not rebuild the social buttons. It renders
 * a hidden, sanitized data carrier consumed by cimne-oko-card.js, preserving
 * the existing Divi design in #oko-social-container.
 *
 * @return string
 */
function cimne_oko_card_social_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $requested_id = isset( $_GET['id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        ? trim( sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        : '';

    if ( '' === $requested_id ) {
        cimne_sheets_log( 'OKO Card shortcode rendered without an id parameter.' );

        return '<span class="cimne-oko-card-data" data-cimne-oko-card-links hidden></span>';
    }

    $card = cimne_sheets_get_oko_card_by_id( $requested_id );

    if ( is_wp_error( $card ) ) {
        cimne_sheets_log(
            'OKO Card component could not be rendered.',
            array(
                'id'         => $requested_id,
                'error_code' => $card->get_error_code(),
            )
        );

        return '<span class="cimne-oko-card-data" data-cimne-oko-card-links hidden></span>';
    }

    if ( null === $card ) {
        cimne_sheets_log(
            'OKO Card id was not found in Google Sheets.',
            array( 'id' => $requested_id )
        );

        return '<span class="cimne-oko-card-data" data-cimne-oko-card-links hidden></span>';
    }

    return sprintf(
        '<span class="cimne-oko-card-data" data-cimne-oko-card-links data-email="%1$s" data-web="%2$s" data-linkedin="%3$s" data-x="%4$s" hidden></span>',
        esc_attr( (string) $card['email'] ),
        esc_url( (string) $card['link'] ),
        esc_url( (string) $card['linkedin'] ),
        esc_url( (string) $card['x'] )
    );
}
add_shortcode( 'cimne_oko_card_social', 'cimne_oko_card_social_shortcode' );
