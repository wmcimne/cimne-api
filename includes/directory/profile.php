<?php
/**
 * Staff profile component for /about/directory/staff-profile/?id=XXXX.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns one reduced staff profile from /persona?id=X.
 *
 * @param int $person_id Fundanet person ID.
 * @return array|WP_Error
 */
function cimne_directory_get_person_profile( $person_id ) {
    $person_id = absint( $person_id );

    if ( ! $person_id ) {
        return new WP_Error(
            'cimne_directory_invalid_person_id',
            'Invalid Directory person ID.'
        );
    }

    return cimne_api_get(
        'persona',
        array( 'id' => $person_id ),
        array(
            'cache_variant' => 'directory-person-profile-v4',
            'transform'     => 'cimne_normalize_directory_profile_payload',
        )
    );
}

/**
 * Resolves the translated Directory page URL for the Back link.
 *
 * @return string
 */
function cimne_directory_index_url() {
    $base_url = '';
    $page = get_page_by_path( 'about/directory' );

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
        $base_url = home_url( '/about/directory/' );
    }

    return $base_url;
}

/**
 * Placeholder used until the people service exposes public profile images.
 *
 * @return string
 */
function cimne_directory_profile_placeholder_image_url() {
    return (string) apply_filters(
        'cimne_directory_profile_placeholder_image_url',
        content_url( 'uploads/2024/12/Hidden_profile_pic.png' )
    );
}

/**
 * Creates an external profile URL from one public identifier.
 *
 * @param string $type  scopus|orcid|researcher_id|google_scholar.
 * @param string $value Identifier or already-complete URL.
 * @return string
 */
function cimne_directory_profile_external_url( $type, $value ) {
    $value = trim( (string) $value );

    if ( '' === $value ) {
        return '';
    }

    if ( preg_match( '#^https?://#i', $value ) ) {
        return $value;
    }

    switch ( $type ) {
        case 'scopus':
            return 'https://www.scopus.com/authid/detail.uri?authorId=' . rawurlencode( $value );

        case 'orcid':
            return 'https://orcid.org/' . rawurlencode( $value );

        case 'researcher_id':
            return 'https://www.researcherid.com/rid/' . rawurlencode( $value );

        case 'google_scholar':
            return 'https://scholar.google.com/citations?user=' . rawurlencode( $value );
    }

    return '';
}

/**
 * Reads ?id= from the current request.
 *
 * @return int
 */
function cimne_directory_requested_person_id() {
    if ( ! isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return 0;
    }

    return absint( wp_unslash( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Renders one staff profile on the dedicated WordPress page.
 *
 * Usage: [cimne_staff_profile]
 * URL: /about/directory/staff-profile/?id=1500
 *
 * @return string
 */
function cimne_staff_profile_shortcode() {
    $person_id = cimne_directory_requested_person_id();

    if ( ! $person_id ) {
        return '<p class="cimne-directory-profile-message">' . esc_html__( 'No staff profile selected.', 'cimne' ) . '</p>';
    }

    $person = cimne_directory_get_person_profile( $person_id );

    if ( is_wp_error( $person ) || empty( $person ) || empty( $person['full_name'] ) ) {
        if ( is_wp_error( $person ) && function_exists( 'cimne_api_log' ) ) {
            cimne_api_log(
                'Directory staff profile could not be rendered.',
                array(
                    'person_id'  => $person_id,
                    'error_code' => $person->get_error_code(),
                )
            );
        }

        return '<p class="cimne-directory-profile-message">' . esc_html__( 'Staff profile not available.', 'cimne' ) . '</p>';
    }

    /*
     * Load the profile behavior from the shortcode that actually renders the
     * component. This is reliable inside Divi/global layouts because it does
     * not depend on finding the shortcode in raw post_content.
     */
    if ( function_exists( 'cimne_directory_enqueue_staff_profile_asset' ) ) {
        cimne_directory_enqueue_staff_profile_asset();
    }

    ob_start();

    get_template_part(
        'template-parts/directory/staff-profile',
        null,
        array(
            'person'            => $person,
            'back_url'          => cimne_directory_index_url(),
            'placeholder_image' => cimne_directory_profile_placeholder_image_url(),
        )
    );

    return (string) ob_get_clean();
}
add_shortcode( 'cimne_staff_profile', 'cimne_staff_profile_shortcode' );
