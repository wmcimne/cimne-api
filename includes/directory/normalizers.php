<?php
/**
 * Normalizers for the CIMNE Directory web services.
 *
 * The Directory endpoint is currently undocumented. These normalizers keep a
 * strict allowlist of fields used by the current card design and by the next
 * Search/Location/Role filters (7B.2), while accepting a small set of defensive aliases.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the first existing key from an array.
 *
 * Uses the shared CIMNE helper when available and falls back locally so this
 * module stays isolated from the Research implementation.
 *
 * @param array $data    Source array.
 * @param array $keys    Candidate keys.
 * @param mixed $default Default value.
 * @return mixed
 */
function cimne_directory_value( array $data, array $keys, $default = null ) {
    if ( function_exists( 'cimne_api_value' ) ) {
        return cimne_api_value( $data, $keys, $default );
    }

    foreach ( $keys as $key ) {
        if ( array_key_exists( $key, $data ) ) {
            return $data[ $key ];
        }
    }

    return $default;
}

/**
 * Removes unit prefixes such as "[IU]" or "[RTD]" from a role label.
 *
 * @param string $label Raw role label.
 * @return string
 */
function cimne_directory_clean_role_label( $label ) {
    $label = trim( (string) $label );

    if ( '' === $label ) {
        return '';
    }

    $clean = preg_replace( '/^\s*\[[^\]]+\]\s*/u', '', $label );

    return is_string( $clean ) ? trim( $clean ) : $label;
}


/**
 * Returns the public-facing CIMNE role label.
 *
 * PSR staff use "Research Support Staff" instead of the generic API label
 * "Staff". When the relation has es_ip=1, the public Manager suffix is added.
 *
 * Examples:
 * - PSR + Staff + es_ip=0 => Research Support Staff.
 * - PSR + Staff + es_ip=1 => Research Support Staff Manager.
 * - non-PSR + Staff       => Staff.
 *
 * Any PSR role other than Staff / Staff Manager keeps its existing label.
 * The Manager suffix is still added when es_ip=1, preserving the current
 * Directory rule for PSR managers.
 *
 * @param string $role_description Raw or normalized role label.
 * @param bool   $is_psr           Whether the person belongs to PSR.
 * @param bool   $is_manager       Whether the relation has es_ip=1.
 * @return string
 */
function cimne_directory_public_role_label( $role_description, $is_psr = false, $is_manager = false ) {
    $role_description = cimne_directory_clean_role_label( $role_description );

    if ( '' === $role_description ) {
        return '';
    }

    if ( ! $is_psr ) {
        return $role_description;
    }

    if ( 0 === strcasecmp( $role_description, 'Staff' ) ) {
        $role_description = 'Research Support Staff';
    } elseif ( 0 === strcasecmp( $role_description, 'Staff Manager' ) ) {
        $role_description = 'Research Support Staff Manager';
    }

    if ( $is_manager && ! preg_match( '/\bmanager\s*$/iu', $role_description ) ) {
        $role_description .= ' Manager';
    }

    return $role_description;
}

/**
 * Normalizes public role display items from the curated override format.
 *
 * Each item may contain a political/management role (rol_pol), a normal CIMNE
 * role (rol_desc), or both. rol_pol is kept separate so templates can render it
 * in bold while rol_desc follows the existing prefix-cleaning rules.
 *
 * @param array $raw_roles Raw roles from the override data file.
 * @return array
 */
function cimne_directory_normalize_display_roles( array $raw_roles ) {
    $items = array();
    $seen = array();

    foreach ( $raw_roles as $role ) {
        if ( ! is_array( $role ) ) {
            continue;
        }

        $political_role = trim(
            (string) cimne_directory_value(
                $role,
                array( 'rol_pol', 'political_role', 'management_role' ),
                ''
            )
        );

        $raw_description = trim(
            (string) cimne_directory_value(
                $role,
                array( 'rol_desc', 'role_description', 'rol' ),
                ''
            )
        );

        // Preserve the 7C rule: cluster Academic Leader is not a public
        // Position in CIMNE. If a future override adds rol_pol to that row, the
        // political role can still be shown on its own.
        if ( 0 === strcasecmp( $raw_description, '[CL] Academic Leader' ) ) {
            $raw_description = '';
        }

        $role_description = cimne_directory_clean_role_label( $raw_description );

        if ( '' === $political_role && '' === $role_description ) {
            continue;
        }

        $key = strtolower( $political_role . '|' . $role_description );

        if ( isset( $seen[ $key ] ) ) {
            continue;
        }

        $seen[ $key ] = true;
        $items[] = array(
            'political_role'   => $political_role,
            'role_description' => $role_description,
        );
    }

    return $items;
}

/**
 * Returns one public role item as plain text.
 *
 * Used for cached/searchable fallback text. Templates keep the two values
 * separate so rol_pol can be rendered in bold.
 *
 * @param array $item Normalized role display item.
 * @return string
 */
function cimne_directory_display_role_text( array $item ) {
    $political_role = isset( $item['political_role'] )
        ? trim( (string) $item['political_role'] )
        : '';
    $role_description = isset( $item['role_description'] )
        ? trim( (string) $item['role_description'] )
        : '';

    if ( '' !== $political_role && '' !== $role_description ) {
        return $political_role . ', ' . $role_description;
    }

    return '' !== $political_role ? $political_role : $role_description;
}

/**
 * Returns normalized curated role items for one person.
 *
 * @param int|string $person_id Fundanet person ID.
 * @return array
 */
function cimne_directory_override_display_roles( $person_id ) {
    if ( ! function_exists( 'cimne_directory_get_role_override' ) ) {
        return array();
    }

    $override = cimne_directory_get_role_override( $person_id );

    if ( ! is_array( $override ) || empty( $override['roles_unidades_inv'] ) || ! is_array( $override['roles_unidades_inv'] ) ) {
        return array();
    }

    return cimne_directory_normalize_display_roles( $override['roles_unidades_inv'] );
}

/**
 * Normalizes the research-unit relations attached to one directory person.
 *
 * Only unit code/name and role metadata needed by the current card and Search/Location/Role filters are retained.
 *
 * @param array $person Raw person.
 * @return array
 */
function cimne_directory_normalize_unit_roles( array $person ) {
    $raw_roles = cimne_directory_value(
        $person,
        array( 'roles', 'roles_unidades_inv', 'roles_unidades', 'research_units', 'unidades_investigacion' ),
        array()
    );

    if ( ! is_array( $raw_roles ) ) {
        return array();
    }

    $roles = array();

    foreach ( $raw_roles as $role ) {
        if ( ! is_array( $role ) ) {
            continue;
        }

        $unit_code = trim(
            (string) cimne_directory_value(
                $role,
                array( 'codigo_grupo', 'codigo_unidad', 'codigo', 'unit_code' ),
                ''
            )
        );

        $unit_name = trim(
            (string) cimne_directory_value(
                $role,
                array( 'nombre_grupo', 'nombre_unidad', 'nombre', 'unit_name' ),
                ''
            )
        );

        $role_code = trim(
            (string) cimne_directory_value(
                $role,
                array( 'rol_cod', 'role_code' ),
                ''
            )
        );

        $role_description = cimne_directory_clean_role_label(
            cimne_directory_value(
                $role,
                array( 'rol_desc', 'role_description', 'rol' ),
                ''
            )
        );

        if ( '' === $unit_code && '' === $unit_name && '' === $role_description ) {
            continue;
        }

        $roles[] = array(
            'unit_code'        => $unit_code,
            'unit_name'        => $unit_name,
            'role_code'        => $role_code,
            'role_description' => $role_description,
            'is_ip'            => ! empty( cimne_directory_value( $role, array( 'es_ip', 'is_ip', 'ip' ), false ) ),
        );
    }

    return $roles;
}

/**
 * Builds public role labels from API data using the Directory rules.
 *
 * Rules:
 * - PSR + Staff + es_ip=0: Research Support Staff.
 * - PSR + Staff + es_ip=1: Research Support Staff Manager.
 * - PSR roles other than Staff keep their role description; if es_ip=1,
 *   the Manager suffix is appended.
 * - non-PSR: rol_desc.
 * - [CL] Academic Leader remains excluded from the public position label.
 *
 * @param array $person     Raw person object.
 * @param array $unit_roles Normalized unit-role relations.
 * @return array
 */
function cimne_directory_api_display_roles( array $person, array $unit_roles ) {
    $is_psr = ! empty( cimne_directory_value( $person, array( 'esPSR', 'is_psr' ), false ) );
    $items = array();
    $seen = array();

    foreach ( $unit_roles as $role ) {
        if ( ! is_array( $role ) ) {
            continue;
        }

        $role_description = isset( $role['role_description'] )
            ? trim( (string) $role['role_description'] )
            : '';

        if ( '' === $role_description ) {
            continue;
        }

        if ( 0 === strcasecmp( $role_description, 'Academic Leader' ) ) {
            continue;
        }

        $role_description = cimne_directory_public_role_label(
            $role_description,
            $is_psr,
            ! empty( $role['is_ip'] )
        );

        if ( '' === $role_description ) {
            continue;
        }

        $key = function_exists( 'remove_accents' )
            ? strtolower( remove_accents( $role_description ) )
            : strtolower( $role_description );

        if ( isset( $seen[ $key ] ) ) {
            continue;
        }

        $seen[ $key ] = true;
        $items[] = array(
            'political_role'   => '',
            'role_description' => $role_description,
        );
    }

    return $items;
}

/**
 * Returns the display category/position for the current Directory card.
 *
 * Explicit position/category fields take priority. If the endpoint does not
 * expose one, the first research-unit role is used; this reproduces current
 * cards such as "Innovation Leader" without storing the full raw payload.
 *
 * @param array $person     Raw person.
 * @param array $unit_roles Normalized unit roles.
 * @return string
 */
function cimne_directory_get_display_category( array $person, array $unit_roles ) {
    $category = trim(
        (string) cimne_directory_value(
            $person,
            array(
                'categoria',
                'categoria_desc',
                'categoria_descripcion',
                'puesto',
                'puesto_desc',
                'puesto_descripcion',
                'cargo',
                'cargo_desc',
                'staff_category',
                'role_description',
                'rol_desc',
            ),
            ''
        )
    );

    $is_psr = ! empty(
        cimne_directory_value(
            $person,
            array( 'esPSR', 'is_psr' ),
            false
        )
    );

    $is_manager = false;

    foreach ( $unit_roles as $role ) {
        if ( is_array( $role ) && ! empty( $role['is_ip'] ) ) {
            $is_manager = true;
            break;
        }
    }

    $category = cimne_directory_public_role_label(
        $category,
        $is_psr,
        $is_manager
    );

    if ( '' !== $category ) {
        return $category;
    }

    foreach ( $unit_roles as $role ) {
        if ( empty( $role['role_description'] ) ) {
            continue;
        }

        return cimne_directory_public_role_label(
            $role['role_description'],
            $is_psr,
            ! empty( $role['is_ip'] )
        );
    }

    return '';
}

/**
 * Normalizes one person from /directorio.
 *
 * Fields retained:
 * - Current card: id, name, phone, email, category.
 * - 7B.2 filter metadata: centre, PSR/PDI flags and unit codes.
 *
 * @param array $person Raw person.
 * @return array|null
 */
function cimne_normalize_directory_person( array $person ) {
    $id = cimne_directory_value(
        $person,
        array( 'fnet_id', 'idtercero', 'id', 'person_id' )
    );

    $first_name = trim(
        (string) cimne_directory_value(
            $person,
            array( 'first_name', 'nombre', 'Nombre' ),
            ''
        )
    );

    $last_name = trim(
        (string) cimne_directory_value(
            $person,
            array( 'last_name', 'apellido1', 'Apellido1' ),
            ''
        )
    );

    $last_name2 = trim(
        (string) cimne_directory_value(
            $person,
            array( 'last_name2', 'apellido2', 'Apellido2' ),
            ''
        )
    );

    $full_name = trim(
        (string) cimne_directory_value(
            $person,
            array( 'full_name', 'nombre_completo' ),
            ''
        )
    );

    if ( '' === $full_name ) {
        $full_name = trim(
            implode(
                ' ',
                array_filter( array( $first_name, $last_name, $last_name2 ) )
            )
        );
    }

    if ( ( null === $id || '' === trim( (string) $id ) ) && '' === $full_name ) {
        return null;
    }

    $email = trim(
        (string) cimne_directory_value(
            $person,
            array( 'email', 'email1', 'correo', 'mail' ),
            ''
        )
    );

    $phone = trim(
        (string) cimne_directory_value(
            $person,
            array( 'dt_telefono', 'telefono', 'phone' ),
            ''
        )
    );

    $centre_code = trim(
        (string) cimne_directory_value(
            $person,
            array( 'centro_cod', 'centro_codigo', 'centre_code' ),
            ''
        )
    );

    $centre_name = trim(
        (string) cimne_directory_value(
            $person,
            array( 'centro_desc', 'centro_descripcion', 'centre_name' ),
            ''
        )
    );

    $department_code = trim(
        (string) cimne_directory_value(
            $person,
            array( 'departamento_cod', 'departamento_codigo', 'department_code' ),
            ''
        )
    );

    $department_name = trim(
        (string) cimne_directory_value(
            $person,
            array( 'departamento_desc', 'departamento_descripcion', 'department_name' ),
            ''
        )
    );

    $unit_roles = cimne_directory_normalize_unit_roles( $person );
    $unit_codes = array();

    foreach ( $unit_roles as $role ) {
        if ( ! empty( $role['unit_code'] ) ) {
            $unit_codes[] = (string) $role['unit_code'];
        }
    }

    $unit_codes = array_values( array_unique( $unit_codes ) );

    $category = cimne_directory_get_display_category( $person, $unit_roles );
    $display_roles = cimne_directory_override_display_roles( $id );

    // Managing Board members use the curated rol_pol/rol_desc pair. Everyone
    // else derives the public label from API rol_desc + PSR/es_ip rules.
    if ( empty( $display_roles ) ) {
        $display_roles = cimne_directory_api_display_roles( $person, $unit_roles );
    }

    if ( empty( $display_roles ) && '' !== $category ) {
        $display_roles = array(
            array(
                'political_role'   => '',
                'role_description' => $category,
            ),
        );
    }

    if ( ! empty( $display_roles ) ) {
        $category_parts = array();

        foreach ( $display_roles as $display_role ) {
            $display_text = cimne_directory_display_role_text( $display_role );

            if ( '' !== $display_text ) {
                $category_parts[] = $display_text;
            }
        }

        $category = implode( ' ', array_unique( $category_parts ) );
    }

    return array(
        'id'           => is_numeric( $id ) ? (int) $id : trim( (string) $id ),
        'first_name'   => $first_name,
        'last_name'    => $last_name,
        'last_name2'   => $last_name2,
        'full_name'    => $full_name,
        'email'        => $email,
        'phone'        => $phone,
        'category'     => $category,
        'display_roles'=> $display_roles,
        'centre_code'    => $centre_code,
        'centre_name'    => $centre_name,
        'department_code'=> $department_code,
        'department_name'=> $department_name,
        'is_psr'         => (bool) cimne_directory_value( $person, array( 'esPSR', 'is_psr' ), false ),
        'is_pdi'       => (bool) cimne_directory_value( $person, array( 'esPDI', 'is_pdi' ), false ),
        'unit_codes'   => $unit_codes,
        'unit_roles'   => $unit_roles,
    );
}

/**
 * Extracts the person list from the current /directorio payload.
 *
 * The service is not documented yet, so the transformer accepts either a top-
 * level list or a small set of likely wrapper keys without leaking the raw JSON
 * to templates or cache.
 *
 * @param array $payload Raw decoded JSON.
 * @return array
 */
function cimne_directory_extract_people( array $payload ) {
    foreach ( array( 'directorio', 'personas', 'personal', 'people', 'items', 'data' ) as $key ) {
        if ( isset( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
            return $payload[ $key ];
        }
    }

    // A numeric-key array is treated as the person list.
    if ( empty( $payload ) ) {
        return array();
    }

    $keys = array_keys( $payload );
    $all_numeric = true;

    foreach ( $keys as $key ) {
        if ( ! is_int( $key ) && ! ctype_digit( (string) $key ) ) {
            $all_numeric = false;
            break;
        }
    }

    if ( $all_numeric ) {
        return array_values( $payload );
    }

    // Defensive fallback: one person object instead of a list.
    if (
        array_key_exists( 'fnet_id', $payload )
        || array_key_exists( 'first_name', $payload )
        || array_key_exists( 'nombre', $payload )
    ) {
        return array( $payload );
    }

    return array();
}

/**
 * Reduces /directorio before it is written to fresh/stale cache.
 *
 * @param array  $payload  Raw endpoint response.
 * @param string $resource API resource.
 * @param array  $params   Query params.
 * @return array
 */
function cimne_normalize_directory_payload( array $payload, $resource = '', array $params = array() ) {
    $people = cimne_directory_extract_people( $payload );
    $normalized = array();

    foreach ( $people as $person ) {
        if ( ! is_array( $person ) ) {
            continue;
        }

        $item = cimne_normalize_directory_person( $person );

        if ( null !== $item ) {
            $normalized[] = $item;
        }
    }

    return $normalized;
}

/**
 * Extracts one person object from the /persona payload.
 *
 * The current endpoint returns the person directly, but a small defensive set
 * of wrapper keys is accepted so the template is not coupled to the transport
 * shape.
 *
 * @param array $payload Raw /persona payload.
 * @return array
 */
function cimne_directory_extract_profile_person( array $payload ) {
    foreach ( array( 'persona', 'person', 'data', 'item' ) as $key ) {
        if ( isset( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
            return $payload[ $key ];
        }
    }

    if (
        array_key_exists( 'fnet_id', $payload )
        || array_key_exists( 'id', $payload )
        || array_key_exists( 'first_name', $payload )
    ) {
        return $payload;
    }

    return array();
}

/**
 * Normalizes a single-line text value used by the profile header.
 *
 * @param mixed $value Raw value.
 * @return string
 */
function cimne_directory_profile_clean_text( $value ) {
    $value = trim( (string) $value );

    if ( '' === $value ) {
        return '';
    }

    $clean = preg_replace( '/\s+/u', ' ', $value );

    return is_string( $clean ) ? trim( $clean ) : $value;
}

/**
 * Normalizes rich text used by the public staff profile.
 *
 * observaciones_tercero is not consistent in the API: some records contain
 * plain text while others already contain HTML. This helper converts both
 * forms to one safe HTML representation before the value is cached.
 *
 * - Plain text/new lines are converted to paragraphs and <br> tags by wpautop().
 * - Existing WordPress-safe HTML is preserved.
 * - Encoded HTML such as &lt;p&gt;...&lt;/p&gt; is decoded first.
 * - Unsafe tags/attributes are removed with wp_kses_post().
 *
 * @param mixed $value Raw biography/CV value.
 * @return string Safe, normalized HTML.
 */
function cimne_directory_profile_normalize_rich_text( $value ) {
    $value = trim( (string) $value );

    if ( '' === $value ) {
        return '';
    }

    // Some legacy records may contain HTML entities instead of literal tags.
    $decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

    // First remove anything that would not be allowed in normal WordPress post
    // content. Then normalize paragraph/line-break handling for both plain text
    // and already-marked-up HTML.
    $safe = wp_kses_post( $decoded );
    $safe = wpautop( $safe );

    // Defence in depth: sanitize the final HTML after wpautop() as well.
    return trim( wp_kses_post( $safe ) );
}

/**
 * Returns the public position labels for a staff profile.
 *
 * A person may have several roles. They are shown once each and in API order.
 * The Directory profile deliberately excludes the cluster-level relation
 * "[CL] Academic Leader" from Position in CIMNE.
 *
 * @param array $person Raw /persona object.
 * @return array
 */
function cimne_directory_profile_positions( array $person ) {
    $raw_roles = cimne_directory_value(
        $person,
        array( 'roles_unidades_inv', 'roles', 'roles_unidades', 'research_units' ),
        array()
    );

    if ( ! is_array( $raw_roles ) ) {
        return array();
    }

    $is_psr = ! empty(
        cimne_directory_value(
            $person,
            array( 'esPSR', 'is_psr' ),
            false
        )
    );

    $positions = array();
    $seen = array();

    foreach ( $raw_roles as $role ) {
        if ( ! is_array( $role ) ) {
            continue;
        }

        $raw_description = trim(
            (string) cimne_directory_value(
                $role,
                array( 'rol_desc', 'role_description', 'rol' ),
                ''
            )
        );

        if ( '' === $raw_description ) {
            continue;
        }

        $comparison = preg_replace( '/\s+/u', ' ', $raw_description );
        $comparison = is_string( $comparison ) ? trim( $comparison ) : $raw_description;

        if ( 0 === strcasecmp( $comparison, '[CL] Academic Leader' ) ) {
            continue;
        }

        $position = cimne_directory_public_role_label(
            $raw_description,
            $is_psr,
            ! empty(
                cimne_directory_value(
                    $role,
                    array( 'es_ip', 'is_ip', 'ip' ),
                    false
                )
            )
        );

        if ( '' === $position ) {
            continue;
        }

        $key = function_exists( 'remove_accents' )
            ? strtolower( remove_accents( $position ) )
            : strtolower( $position );

        if ( isset( $seen[ $key ] ) ) {
            continue;
        }

        $seen[ $key ] = true;
        $positions[] = $position;
    }

    return $positions;
}

/**
 * Returns structured Position in CIMNE items.
 *
 * Managing Board IDs use the local override file so rol_pol can be combined
 * with rol_desc. Other staff derive their public position from API rol_desc,
 * with the PSR + es_ip manager rule applied when required.
 *
 * @param array      $person    Raw /persona object.
 * @param int|string $person_id Fundanet person ID.
 * @return array
 */
function cimne_directory_profile_position_items( array $person, $person_id ) {
    $override_roles = cimne_directory_override_display_roles( $person_id );

    // Membership in role-overrides.json means Managing Board. These curated
    // values take priority because rol_pol is not currently exposed by the WS.
    if ( ! empty( $override_roles ) ) {
        return $override_roles;
    }

    $unit_roles = cimne_directory_normalize_unit_roles( $person );
    $api_roles = cimne_directory_api_display_roles( $person, $unit_roles );

    if ( ! empty( $api_roles ) ) {
        return $api_roles;
    }

    // Defensive fallback for a future /persona payload whose roles cannot be
    // normalized by cimne_directory_normalize_unit_roles().
    $items = array();

    foreach ( cimne_directory_profile_positions( $person ) as $position ) {
        $items[] = array(
            'political_role'   => '',
            'role_description' => $position,
        );
    }

    return $items;
}

/**
 * Reduces one /persona response to the fields explicitly used by the public
 * staff profile.
 *
 * Office is intentionally omitted. Images are currently supplied by a local
 * placeholder and are not read from the API.
 *
 * @param array $person Raw person object.
 * @return array
 */
function cimne_normalize_directory_profile_person( array $person, $requested_person_id = 0 ) {
    $id = cimne_directory_value(
        $person,
        array( 'fnet_id', 'idtercero', 'id', 'person_id' ),
        ''
    );

    if ( ( null === $id || '' === trim( (string) $id ) ) && $requested_person_id ) {
        $id = absint( $requested_person_id );
    }

    $override_person_id = $requested_person_id ? absint( $requested_person_id ) : $id;

    $first_name = cimne_directory_profile_clean_text(
        cimne_directory_value( $person, array( 'first_name', 'nombre' ), '' )
    );
    $last_name = cimne_directory_profile_clean_text(
        cimne_directory_value( $person, array( 'last_name', 'apellido1' ), '' )
    );
    $last_name2 = cimne_directory_profile_clean_text(
        cimne_directory_value( $person, array( 'last_name2', 'apellido2' ), '' )
    );

    $full_name = cimne_directory_profile_clean_text(
        cimne_directory_value( $person, array( 'full_name', 'nombre_completo' ), '' )
    );

    if ( '' === $full_name ) {
        $full_name = trim(
            implode(
                ' ',
                array_filter( array( $first_name, $last_name, $last_name2 ) )
            )
        );
    }

    $address_line = cimne_directory_profile_clean_text(
        cimne_directory_value( $person, array( 'dt_direccion', 'direccion', 'address' ), '' )
    );
    $postal_code = cimne_directory_profile_clean_text(
        cimne_directory_value( $person, array( 'dt_codigo_postal', 'codigo_postal', 'postal_code' ), '' )
    );
    $locality = cimne_directory_profile_clean_text(
        cimne_directory_value( $person, array( 'dt_localidad', 'localidad', 'city' ), '' )
    );
    $country = cimne_directory_profile_clean_text(
        cimne_directory_value( $person, array( 'dt_pais', 'pais', 'country' ), '' )
    );

    $address_locality = trim(
        implode(
            ', ',
            array_filter( array( $postal_code, $locality, $country ) )
        )
    );

    $location = cimne_directory_profile_clean_text(
        cimne_directory_value( $person, array( 'centro_desc', 'centre_name' ), '' )
    );

    if ( '' === $location ) {
        $location = $locality;
    }

    $department = cimne_directory_profile_clean_text(
        cimne_directory_value( $person, array( 'departamento_desc', 'department_name' ), '' )
    );
    $position_items = cimne_directory_profile_position_items( $person, $override_person_id );
    $positions = array();

    if ( function_exists( 'cimne_directory_get_role_override' ) ) {
        $override = cimne_directory_get_role_override( $override_person_id );

        if ( is_array( $override ) && ! empty( $override['departamento_desc'] ) ) {
            $department = cimne_directory_profile_clean_text( $override['departamento_desc'] );
        }
    }

    foreach ( $position_items as $position_item ) {
        $position_text = cimne_directory_display_role_text( $position_item );

        if ( '' !== $position_text ) {
            $positions[] = $position_text;
        }
    }

    return array(
        'id'              => is_numeric( $id ) ? (int) $id : trim( (string) $id ),
        'full_name'       => $full_name,
        'email'           => cimne_directory_profile_clean_text(
            cimne_directory_value( $person, array( 'email', 'email1', 'correo', 'mail' ), '' )
        ),
        'phone'           => cimne_directory_profile_clean_text(
            cimne_directory_value( $person, array( 'dt_telefono', 'telefono', 'phone' ), '' )
        ),
        'location'        => $location,
        'address_line'    => $address_line,
        'address_locality'=> $address_locality,
        'department'      => $department,
        'positions'       => $positions,
        'position_items'  => $position_items,
        'scopus_id'       => cimne_directory_profile_clean_text(
            cimne_directory_value( $person, array( 'scopus_id' ), '' )
        ),
        'orcid'           => cimne_directory_profile_clean_text(
            cimne_directory_value( $person, array( 'orcid' ), '' )
        ),
        'researcher_id'   => cimne_directory_profile_clean_text(
            cimne_directory_value( $person, array( 'rid', 'researcher_id' ), '' )
        ),
        'google_scholar'  => cimne_directory_profile_clean_text(
            cimne_directory_value( $person, array( 'google_id', 'google_scholar' ), '' )
        ),
        'cv'              => cimne_directory_profile_normalize_rich_text(
            cimne_directory_value(
                $person,
                array( 'observaciones_tercero', 'cv', 'biography', 'bio' ),
                ''
            )
        ),
    );
}

/**
 * Reduces /persona before it is written to fresh/stale cache.
 *
 * @param array  $payload  Raw endpoint response.
 * @param string $resource API resource.
 * @param array  $params   Query params.
 * @return array
 */
function cimne_normalize_directory_profile_payload( array $payload, $resource = '', array $params = array() ) {
    $person = cimne_directory_extract_profile_person( $payload );

    if ( empty( $person ) ) {
        return array();
    }

    $requested_person_id = isset( $params['id'] ) ? absint( $params['id'] ) : 0;

    return cimne_normalize_directory_profile_person( $person, $requested_person_id );
}
