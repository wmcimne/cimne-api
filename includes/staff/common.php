<?php
/**
 * Shared Staff helpers used by Research, Directory and Staff Profile components.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the translated Staff Profile URL for one Fundanet person ID.
 *
 * @param int|string $person_id Fundanet person ID.
 * @return string
 */
function cimne_staff_profile_url( $person_id ) {
    $person_id = trim( (string) $person_id );

    if ( '' === $person_id ) {
        return '';
    }

    $base_url = '';
    $page = get_page_by_path( 'about/directory/staff-profile' );

    if ( $page instanceof WP_Post ) {
        $page_id = (int) $page->ID;

        if ( has_filter( 'wpml_object_id' ) ) {
            $translated_id = apply_filters(
                'wpml_object_id',
                $page_id,
                'page',
                true,
                apply_filters( 'wpml_current_language', null )
            );

            if ( $translated_id ) {
                $page_id = (int) $translated_id;
            }
        }

        $base_url = get_permalink( $page_id );
    }

    if ( ! is_string( $base_url ) || '' === $base_url ) {
        $base_url = home_url( '/about/directory/staff-profile/' );
    }

    return add_query_arg( 'id', rawurlencode( $person_id ), $base_url );
}

/**
 * Encodes an email address for the shared Staff copy-email control.
 *
 * The visible HTML stores base64(email) + "c" in the element ID. The actual
 * email is decoded only when the shared cimne-staff.js behavior needs it.
 *
 * @param string $email Public email address.
 * @return string
 */
function cimne_staff_email_element_id( $email ) {
    $email = trim( (string) $email );

    if ( '' === $email ) {
        return '';
    }

    return base64_encode( $email ) . 'c'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}
