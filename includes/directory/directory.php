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
            'cache_variant' => 'directory-list-v7',
            'transform'     => 'cimne_normalize_directory_payload',
        )
    );
}

/**
 * Builds the shared Staff card arguments for one Directory person.
 *
 * Directory-only filter metadata stays here; the shared card renderer only
 * receives the attributes it must print.
 *
 * @param array $person Normalized Directory person.
 * @return array
 */
function cimne_directory_person_card_args( array $person ) {
    $person_id = isset( $person['id'] ) ? trim( (string) $person['id'] ) : '';
    $full_name = isset( $person['full_name'] ) ? trim( (string) $person['full_name'] ) : '';
    $phone = isset( $person['phone'] ) ? trim( (string) $person['phone'] ) : '';
    $email = isset( $person['email'] ) ? trim( (string) $person['email'] ) : '';
    $category = isset( $person['category'] ) ? trim( (string) $person['category'] ) : '';
    $display_roles = isset( $person['display_roles'] ) && is_array( $person['display_roles'] )
        ? $person['display_roles']
        : array();
    $centre = isset( $person['centre_code'] ) ? trim( (string) $person['centre_code'] ) : '';
    $department_code = isset( $person['department_code'] ) ? trim( (string) $person['department_code'] ) : '';
    $department_name = isset( $person['department_name'] ) ? trim( (string) $person['department_name'] ) : '';
    $is_psr = ! empty( $person['is_psr'] );
    $is_pdi = ! empty( $person['is_pdi'] );
    $unit_codes = isset( $person['unit_codes'] ) && is_array( $person['unit_codes'] )
        ? array_values( array_filter( array_map( 'strval', $person['unit_codes'] ) ) )
        : array();
    $unit_roles = isset( $person['unit_roles'] ) && is_array( $person['unit_roles'] )
        ? $person['unit_roles']
        : array();

    $categories = array();
    $search_parts = array( $full_name, $email, $category, $department_name );

    foreach ( $display_roles as $display_role ) {
        if ( ! is_array( $display_role ) ) {
            continue;
        }

        $political_role = isset( $display_role['political_role'] )
            ? trim( (string) $display_role['political_role'] )
            : '';
        $role_description = isset( $display_role['role_description'] )
            ? trim( (string) $display_role['role_description'] )
            : '';

        if ( '' !== $political_role ) {
            $search_parts[] = $political_role;
        }

        if ( '' !== $role_description ) {
            $search_parts[] = $role_description;
        }

        if ( '' !== $political_role || '' !== $role_description ) {
            $categories[] = '' !== $political_role && '' !== $role_description
                ? $political_role . ', ' . $role_description
                : ( '' !== $political_role ? $political_role : $role_description );
        }
    }

    if ( empty( $categories ) && '' !== $category ) {
        $categories[] = $category;
    }

    foreach ( $unit_roles as $unit_role ) {
        if ( ! is_array( $unit_role ) ) {
            continue;
        }

        if ( ! empty( $unit_role['unit_name'] ) ) {
            $search_parts[] = (string) $unit_role['unit_name'];
        }

        if ( ! empty( $unit_role['role_description'] ) ) {
            $search_parts[] = (string) $unit_role['role_description'];
        }
    }

    return array(
        'person' => array(
            'person_id' => $person_id,
            'full_name' => $full_name,
            'phone'     => $phone,
            'email'     => $email,
        ),
        'categories' => $categories,
        'classes' => array( 'cimne-directory-person' ),
        'attributes' => array(
            'data-cimne-directory-person' => '',
            'data-person-id'              => $person_id,
            'data-search-text'            => trim( implode( ' ', array_filter( $search_parts ) ) ),
            'data-centre-code'            => $centre,
            'data-department-code'        => $department_code,
            'data-is-psr'                 => $is_psr ? '1' : '0',
            'data-is-pdi'                 => $is_pdi ? '1' : '0',
            'data-unit-codes'             => implode( ',', $unit_codes ),
        ),
    );
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
