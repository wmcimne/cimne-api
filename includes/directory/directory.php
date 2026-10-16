<?php
/**
 * Base Directory component (Steps 7A-7B.2).
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the reduced Directory dataset.
 *
 * /directorio does not accept filtering parameters. The component therefore fetches
 * the list once, normalizes it before cache and renders the full initial HTML.
 *
 * @return array|WP_Error
 */
function cimne_directory_get_people() {
    return cimne_api_get(
        'directorio',
        array(),
        array(
            'cache_variant' => 'directory-list-v5',
            'transform'     => 'cimne_normalize_directory_payload',
        )
    );
}

/**
 * Returns the translated legacy staff-profile page URL for one person.
 *
 * The Directory keeps the dedicated staff-profile page architecture used by
 * Step 7C: /about/directory/staff-profile/?id=XXXX.
 *
 * @param int|string $person_id Fundanet person ID.
 * @return string
 */
function cimne_directory_person_profile_url( $person_id ) {
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

    return add_query_arg( 'id', rawurlencode( (string) $person_id ), $base_url );
}

/**
 * Creates the legacy email element ID used by the existing CIMNE copy-email
 * handler: base64(email) + "c".
 *
 * @param string $email Public email address.
 * @return string
 */
function cimne_directory_email_element_id( $email ) {
    $email = trim( (string) $email );

    if ( '' === $email ) {
        return '';
    }

    return base64_encode( $email ) . 'c'; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
}

/**
 * Renders the base Directory list.
 *
 * @return string
 */
function cimne_directory_shortcode() {
    $people = cimne_directory_get_people();

    if ( is_wp_error( $people ) ) {
        if ( function_exists( 'cimne_api_log' ) ) {
            cimne_api_log(
                'Directory component could not be rendered.',
                array( 'error_code' => $people->get_error_code() )
            );
        }

        return '';
    }

    ob_start();

    get_template_part(
        'template-parts/directory/directory',
        null,
        array(
            'people' => $people,
        )
    );

    return (string) ob_get_clean();
}
add_shortcode( 'cimne_directory', 'cimne_directory_shortcode' );
