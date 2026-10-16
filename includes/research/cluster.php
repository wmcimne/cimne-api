<?php
/**
 * Shared Cluster/Research Group helpers for CIMNE Research pages.
 *
 * This layer centralizes research_units queries and the matching of each
 * unit's `responsable` value against its normalized people.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gets one research unit using view=complete.
 *
 * Used when the component needs enriched internal-person data such as phone
 * and email (Staff / Group Leaders).
 *
 * @param string $code Research-unit code.
 * @return array|WP_Error
 */
function cimne_research_get_unit_complete( $code ) {
    $code = trim( (string) $code );

    if ( '' === $code ) {
        return new WP_Error(
            'cimne_research_missing_unit_code',
            'The CIMNE research unit code is missing.'
        );
    }

    $response = cimne_api_get(
        'research_units',
        array(
            'codigo' => $code,
            'view'   => 'complete',
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    if ( empty( $response ) ) {
        return new WP_Error(
            'cimne_research_unit_not_found',
            sprintf( 'No CIMNE research unit was found for code %s.', $code )
        );
    }

    $units = cimne_normalize_research_units( $response );

    foreach ( $units as $unit ) {
        if ( isset( $unit['code'] ) && 0 === strcasecmp( (string) $unit['code'], $code ) ) {
            return $unit;
        }
    }

    return reset( $units );
}

/**
 * Gets the complete child units that belong to one Research Cluster.
 *
 * The same response is shared by Research Groups and Staff through the
 * existing API cache.
 *
 * @param string $cluster_code Research Cluster code.
 * @return array|WP_Error
 */
function cimne_research_get_cluster_children( $cluster_code ) {
    $cluster_code = trim( (string) $cluster_code );

    if ( '' === $cluster_code ) {
        return new WP_Error(
            'cimne_research_missing_cluster_code',
            'The CIMNE Research Cluster code is missing.'
        );
    }

    $response = cimne_api_get(
        'research_units',
        array(
            'parent' => $cluster_code,
            'view'   => 'complete',
        )
    );

    if ( is_wp_error( $response ) ) {
        return $response;
    }

    return cimne_normalize_research_units( $response );
}

/**
 * Converts a human name into normalized tokens.
 *
 * @param string $name Human name.
 * @return array
 */
function cimne_research_name_tokens( $name ) {
    $name = trim( wp_strip_all_tags( (string) $name ) );

    if ( '' === $name ) {
        return array();
    }

    $name = remove_accents( $name );
    $name = strtolower( $name );
    $name = preg_replace( '/[^a-z0-9]+/i', ' ', $name );
    $name = trim( preg_replace( '/\s+/', ' ', (string) $name ) );

    if ( '' === $name ) {
        return array();
    }

    return array_values( array_unique( array_filter( explode( ' ', $name ) ) ) );
}

/**
 * Converts a human name into an order-independent signature.
 *
 * This lets us match values such as "MARTINEZ GARCIA, XAVIER" against
 * "Xavier Martinez Garcia" without depending on capitalization, accents,
 * commas or name order.
 *
 * @param string $name Human name.
 * @return string
 */
function cimne_research_name_signature( $name ) {
    $parts = cimne_research_name_tokens( $name );

    if ( empty( $parts ) ) {
        return '';
    }

    sort( $parts, SORT_STRING );

    return implode( '|', $parts );
}

/**
 * Creates a stable identity for one normalized research person.
 *
 * @param array $person Normalized/fallback person.
 * @return string
 */
function cimne_research_person_identity( array $person ) {
    if ( isset( $person['person_id'] ) && null !== $person['person_id'] && '' !== trim( (string) $person['person_id'] ) ) {
        return 'id:' . trim( (string) $person['person_id'] );
    }

    $name = '';

    if ( ! empty( $person['full_name'] ) ) {
        $name = (string) $person['full_name'];
    } elseif ( ! empty( $person['name'] ) ) {
        $name = (string) $person['name'];
    }

    $signature = cimne_research_name_signature( $name );

    return '' !== $signature ? 'name:' . $signature : '';
}

/**
 * Splits one Fundanet `responsable` field into individual responsible names.
 *
 * Multiple Group Leaders are separated by `/`, for example:
 * `CERVERA RUIZ, LUIS MIGUEL / CHIUMENTI, MICHELE`.
 *
 * @param string $responsible Raw responsible field.
 * @return array
 */
function cimne_research_split_responsible_names( $responsible ) {
    $responsible = trim( (string) $responsible );

    if ( '' === $responsible ) {
        return array();
    }

    $names = preg_split( '/\s*\/\s*/u', $responsible, -1, PREG_SPLIT_NO_EMPTY );

    if ( ! is_array( $names ) ) {
        return array( $responsible );
    }

    return array_values(
        array_filter(
            array_map(
                static function ( $name ) {
                    return trim( (string) $name );
                },
                $names
            )
        )
    );
}

/**
 * Formats one responsible name for fallback display.
 *
 * If Fundanet returns `SURNAME, NAME`, the fallback becomes `NAME SURNAME`.
 * This function expects one individual name, not the complete `/`-separated
 * `responsable` field.
 *
 * @param string $responsible_name One responsible name.
 * @return string
 */
function cimne_research_format_responsible_name( $responsible_name ) {
    $responsible_name = trim( (string) $responsible_name );

    if ( '' === $responsible_name ) {
        return '';
    }

    if ( false !== strpos( $responsible_name, ',' ) ) {
        $parts = explode( ',', $responsible_name, 2 );
        $left  = isset( $parts[0] ) ? trim( $parts[0] ) : '';
        $right = isset( $parts[1] ) ? trim( $parts[1] ) : '';

        if ( '' !== $left && '' !== $right ) {
            return trim( $right . ' ' . $left );
        }
    }

    return $responsible_name;
}

/**
 * Builds the common public card data for one normalized person.
 *
 * @param array $person Normalized research person.
 * @return array
 */
function cimne_research_public_person_data( array $person ) {
    $full_name = isset( $person['full_name'] ) ? trim( (string) $person['full_name'] ) : '';

    return array(
        'person_id' => isset( $person['person_id'] ) ? $person['person_id'] : null,
        'name'      => $full_name,
        'full_name' => $full_name,
        'phone'     => isset( $person['phone'] ) ? trim( (string) $person['phone'] ) : '',
        'email'     => isset( $person['email'] ) ? trim( (string) $person['email'] ) : '',
    );
}

/**
 * Tries to resolve one responsible name against complete personnel.
 *
 * @param string $responsible_name One responsible name from Fundanet.
 * @param array  $people           Normalized complete personnel.
 * @param array  $used_identities  Identities already assigned to another leader.
 * @return array|null
 */
function cimne_research_match_responsible_person( $responsible_name, array $people, array $used_identities = array() ) {
    $responsible_name      = trim( (string) $responsible_name );
    $responsible_signature = cimne_research_name_signature( $responsible_name );
    $responsible_tokens    = cimne_research_name_tokens( $responsible_name );
    $subset_matches        = array();

    if ( '' === $responsible_name ) {
        return null;
    }

    foreach ( $people as $person ) {
        if ( ! is_array( $person ) ) {
            continue;
        }

        $full_name = isset( $person['full_name'] ) ? trim( (string) $person['full_name'] ) : '';

        if ( '' === $full_name ) {
            continue;
        }

        $identity = cimne_research_person_identity( $person );

        if ( '' !== $identity && isset( $used_identities[ $identity ] ) ) {
            continue;
        }

        if ( $responsible_signature === cimne_research_name_signature( $full_name ) ) {
            return cimne_research_public_person_data( $person );
        }

        $person_tokens = cimne_research_name_tokens( $full_name );

        if ( count( $responsible_tokens ) >= 2 && count( $person_tokens ) >= 2 ) {
            $responsible_is_subset = empty( array_diff( $responsible_tokens, $person_tokens ) );
            $person_is_subset      = empty( array_diff( $person_tokens, $responsible_tokens ) );

            if ( $responsible_is_subset || $person_is_subset ) {
                $subset_matches[] = $person;
            }
        }
    }

    // Only accept fuzzy matching when it identifies one unused person.
    if ( 1 === count( $subset_matches ) ) {
        return cimne_research_public_person_data( $subset_matches[0] );
    }

    return null;
}

/**
 * Resolves all responsible people for one Research Group.
 *
 * Fundanet can return more than one Group Leader in the `responsable` field,
 * separated by `/`. Every name is resolved independently against
 * `view=complete` personnel so phone/email remain available for Staff cards.
 *
 * Resolution order:
 * 1. exact normalized-name match;
 * 2. unique token-subset match;
 * 3. if exactly one name remains unresolved and exactly one unused `es_ip`
 *    relation remains, use that person;
 * 4. name-only fallback for every unresolved leader.
 *
 * @param array $unit Normalized research unit.
 * @return array
 */
function cimne_research_get_unit_responsible_people( array $unit ) {
    $responsible = isset( $unit['responsible'] ) ? trim( (string) $unit['responsible'] ) : '';
    $names       = cimne_research_split_responsible_names( $responsible );
    $people      = isset( $unit['people'] ) && is_array( $unit['people'] ) ? $unit['people'] : array();

    if ( empty( $names ) ) {
        return array();
    }

    $resolved        = array();
    $used_identities = array();
    $unresolved      = array();

    foreach ( $names as $responsible_name ) {
        $person = cimne_research_match_responsible_person( $responsible_name, $people, $used_identities );

        if ( null === $person ) {
            $unresolved[] = $responsible_name;
            continue;
        }

        $identity = cimne_research_person_identity( $person );

        if ( '' !== $identity ) {
            $used_identities[ $identity ] = true;
        }

        $resolved[] = $person;
    }

    // Preserve the Step 6.1 es_ip fallback only when the remaining relation is
    // unambiguous. With several unresolved names we never guess their mapping.
    if ( 1 === count( $unresolved ) ) {
        $remaining_ip = array();

        foreach ( $people as $person ) {
            if ( ! is_array( $person ) || empty( $person['is_pi'] ) ) {
                continue;
            }

            $identity = cimne_research_person_identity( $person );

            if ( '' !== $identity && isset( $used_identities[ $identity ] ) ) {
                continue;
            }

            $remaining_ip[] = $person;
        }

        if ( 1 === count( $remaining_ip ) ) {
            $person   = cimne_research_public_person_data( $remaining_ip[0] );
            $identity = cimne_research_person_identity( $person );

            if ( '' !== $identity ) {
                $used_identities[ $identity ] = true;
            }

            $resolved[] = $person;
            $unresolved = array();
        }
    }

    foreach ( $unresolved as $responsible_name ) {
        $fallback_name = cimne_research_format_responsible_name( $responsible_name );

        if ( '' === $fallback_name ) {
            continue;
        }

        $resolved[] = array(
            'person_id' => null,
            'name'      => $fallback_name,
            'full_name' => $fallback_name,
            'phone'     => '',
            'email'     => '',
        );
    }

    return $resolved;
}

/**
 * Backwards-compatible singular responsible helper.
 *
 * Components that expect one person (for example Cluster Contact Point) keep
 * using the first resolved responsible. Group-related components should use
 * cimne_research_get_unit_responsible_people() instead.
 *
 * @param array $unit Normalized research unit.
 * @return array|null
 */
function cimne_research_get_responsible_person( array $unit ) {
    $people = cimne_research_get_unit_responsible_people( $unit );

    return ! empty( $people ) ? reset( $people ) : null;
}

/**
 * Returns all unique responsible people for a list of units.
 *
 * @param array $units Normalized units.
 * @return array
 */
function cimne_research_get_responsible_people( array $units ) {
    $people = array();
    $seen   = array();

    foreach ( $units as $unit ) {
        if ( ! is_array( $unit ) ) {
            continue;
        }

        $unit_people = cimne_research_get_unit_responsible_people( $unit );

        foreach ( $unit_people as $person ) {
            if ( empty( $person ) || empty( $person['name'] ) ) {
                continue;
            }

            $identity = cimne_research_person_identity( $person );

            if ( '' !== $identity && isset( $seen[ $identity ] ) ) {
                continue;
            }

            if ( '' !== $identity ) {
                $seen[ $identity ] = true;
            }

            $people[] = $person;
        }
    }

    usort(
        $people,
        static function ( $a, $b ) {
            return strcasecmp( (string) $a['name'], (string) $b['name'] );
        }
    );

    return $people;
}

/**
 * Tests whether one normalized person matches a Cluster leadership role.
 *
 * Role codes are preferred, but role-description matching is kept as a
 * fallback because the normal research_units view is the authoritative source
 * for Cluster leadership and external people may not use the same code set as
 * internal complete-view relations.
 *
 * @param array $person            Normalized person.
 * @param array $role_codes        Accepted exact role codes.
 * @param array $description_terms Accepted normalized substrings.
 * @return bool
 */
function cimne_research_person_matches_role( array $person, array $role_codes, array $description_terms = array() ) {
    $current_role = isset( $person['role_code'] ) ? strtoupper( trim( (string) $person['role_code'] ) ) : '';
    $role_codes   = array_map(
        static function ( $code ) {
            return strtoupper( trim( (string) $code ) );
        },
        $role_codes
    );

    if ( '' !== $current_role && in_array( $current_role, $role_codes, true ) ) {
        return true;
    }

    $description = isset( $person['role_description'] ) ? (string) $person['role_description'] : '';
    $description = strtoupper( remove_accents( $description ) );
    $description = preg_replace( '/^\s*\[[^\]]+\]\s*/u', '', (string) $description );

    foreach ( $description_terms as $term ) {
        $term = strtoupper( remove_accents( trim( (string) $term ) ) );

        if ( '' !== $term && false !== strpos( $description, $term ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Returns unique names matching Cluster leadership roles.
 *
 * @param array $people            Normalized people.
 * @param array $role_codes        Accepted exact role codes.
 * @param array $description_terms Accepted description substrings.
 * @return array
 */
function cimne_research_people_names_by_role_match( array $people, array $role_codes, array $description_terms = array() ) {
    $names = array();
    $seen  = array();

    foreach ( $people as $person ) {
        if ( ! is_array( $person ) || ! cimne_research_person_matches_role( $person, $role_codes, $description_terms ) ) {
            continue;
        }

        $name = isset( $person['full_name'] ) ? trim( (string) $person['full_name'] ) : '';

        if ( '' === $name ) {
            continue;
        }

        $identity = cimne_research_person_identity( $person );

        if ( '' !== $identity && isset( $seen[ $identity ] ) ) {
            continue;
        }

        if ( '' !== $identity ) {
            $seen[ $identity ] = true;
        }

        $names[] = $name;
    }

    return $names;
}

/**
 * Backwards-compatible exact-role helper.
 *
 * @param array  $people    Normalized people.
 * @param string $role_code Role code.
 * @return array
 */
function cimne_research_people_names_by_role( array $people, $role_code ) {
    return cimne_research_people_names_by_role_match( $people, array( $role_code ) );
}
