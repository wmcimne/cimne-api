<?php
/**
 * Curated Managing Board role/department overrides.
 *
 * The API currently exposes rol_desc but not rol_pol. The local data file
 * data/role-overrides.json therefore contains only the approved Managing Board
 * members, their public political/management role and the department override.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Loads and indexes the approved overrides by Fundanet person ID.
 *
 * The JSON is read once per PHP request. Invalid rows are ignored so a bad
 * optional record cannot break the Directory.
 *
 * @return array<int,array>
 */
function cimne_directory_role_overrides() {
    static $overrides = null;

    if ( null !== $overrides ) {
        return $overrides;
    }

    $overrides = array();
    $path = __DIR__ . '/data/role-overrides.json';

    if ( ! is_readable( $path ) ) {
        return $overrides;
    }

    $contents = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

    if ( false === $contents || '' === trim( $contents ) ) {
        return $overrides;
    }

    $rows = json_decode( $contents, true );

    if ( ! is_array( $rows ) ) {
        return $overrides;
    }

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) || empty( $row['fnet_id'] ) ) {
            continue;
        }

        $person_id = absint( $row['fnet_id'] );

        if ( ! $person_id ) {
            continue;
        }

        $overrides[ $person_id ] = array(
            'fnet_id'             => $person_id,
            'departamento_desc'   => isset( $row['departamento_desc'] )
                ? trim( (string) $row['departamento_desc'] )
                : '',
            'roles_unidades_inv'  => isset( $row['roles_unidades_inv'] ) && is_array( $row['roles_unidades_inv'] )
                ? $row['roles_unidades_inv']
                : array(),
        );
    }

    return $overrides;
}

/**
 * Returns the approved override for one person, when present.
 *
 * @param int|string $person_id Fundanet person ID.
 * @return array|null
 */
function cimne_directory_get_role_override( $person_id ) {
    $person_id = absint( $person_id );

    if ( ! $person_id ) {
        return null;
    }

    $overrides = cimne_directory_role_overrides();

    return isset( $overrides[ $person_id ] ) ? $overrides[ $person_id ] : null;
}
