<?php
/**
 * Staff component for CIMNE Research Cluster and Research Group pages.
 *
 * Final presentation rules:
 * - Cluster: research_units?parent={CODE}&view=complete
 * - Group:   research_units?codigo={CODE}&view=complete
 * - The responsible person of each displayed unit is shown first under
 *   "Group Leaders".
 * - Group Leaders are excluded from every other role section.
 * - Remaining people are grouped by role.
 * - Duplicate person+role combinations are removed.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Removes the technical prefix from role descriptions, e.g.
 * "[RTD] Full Research Professor" -> "Full Research Professor".
 *
 * @param string $label Role description.
 * @return string
 */
function cimne_research_clean_role_label( $label ) {
    $label = trim( (string) $label );
    $label = preg_replace( '/^\s*\[[^\]]+\]\s*/u', '', $label );

    return trim( (string) $label );
}

/**
 * Converts known API role labels to the approved section heading.
 * Unknown labels are preserved after removing their technical prefix.
 *
 * @param string $role_description API role description.
 * @param string $role_code        API role code.
 * @return string
 */
function cimne_research_staff_role_heading( $role_description, $role_code = '' ) {
    $label = cimne_research_clean_role_label( $role_description );
    $key   = strtoupper( remove_accents( $label ) );

    if ( false !== strpos( $key, 'GROUP LEADER' ) ) {
        return __( 'Group Leaders', 'cimne' );
    }

    if ( '' !== $label ) {
        return $label;
    }

    $role_code = trim( (string) $role_code );

    return '' !== $role_code ? $role_code : __( 'Staff', 'cimne' );
}

/**
 * Groups personnel by role, excluding selected people completely.
 *
 * @param array $units               Normalized research units with complete personnel.
 * @param array $excluded_identities Map of person identities to exclude.
 * @return array
 */
function cimne_research_group_staff_by_role( array $units, array $excluded_identities = array() ) {
    $groups = array();
    $seen   = array();
    $order  = 0;

    foreach ( $units as $unit ) {
        $people = isset( $unit['people'] ) && is_array( $unit['people'] ) ? $unit['people'] : array();

        foreach ( $people as $person ) {
            $name = isset( $person['full_name'] ) ? trim( (string) $person['full_name'] ) : '';

            if ( '' === $name ) {
                continue;
            }

            $person_identity = cimne_research_person_identity( $person );

            if ( '' !== $person_identity && isset( $excluded_identities[ $person_identity ] ) ) {
                continue;
            }

            $role_code = isset( $person['role_code'] ) ? trim( (string) $person['role_code'] ) : '';
            $role_desc = isset( $person['role_description'] ) ? trim( (string) $person['role_description'] ) : '';

            // `responsable` is the authoritative source for Group Leaders.
            // Do not create a second Group Leaders section from a generic role.
            $clean_role_key = strtoupper( remove_accents( cimne_research_clean_role_label( $role_desc ) ) );

            if ( false !== strpos( $clean_role_key, 'GROUP LEADER' ) ) {
                continue;
            }

            $heading = cimne_research_staff_role_heading( $role_desc, $role_code );

            $role_identity = '' !== $role_code
                ? strtoupper( $role_code )
                : strtoupper( remove_accents( $heading ) );

            $dedupe_person = '' !== $person_identity
                ? $person_identity
                : 'name:' . cimne_research_name_signature( $name );

            $dedupe_key = $dedupe_person . '|' . $role_identity;

            if ( isset( $seen[ $dedupe_key ] ) ) {
                continue;
            }

            $seen[ $dedupe_key ] = true;
            $group_key           = strtolower( md5( $role_identity ) );

            if ( ! isset( $groups[ $group_key ] ) ) {
                $groups[ $group_key ] = array(
                    'label'     => $heading,
                    'role_code' => $role_code,
                    'priority'  => ! empty( $person['is_pi'] ) ? 0 : 10,
                    'order'     => $order++,
                    'people'    => array(),
                );
            } elseif ( ! empty( $person['is_pi'] ) ) {
                $groups[ $group_key ]['priority'] = 0;
            }

            $groups[ $group_key ]['people'][] = array(
                'person_id' => isset( $person['person_id'] ) ? $person['person_id'] : null,
                'name'      => $name,
                'phone'     => isset( $person['phone'] ) ? trim( (string) $person['phone'] ) : '',
                'email'     => isset( $person['email'] ) ? trim( (string) $person['email'] ) : '',
            );
        }
    }

    $groups = array_values( $groups );

    foreach ( $groups as &$group ) {
        usort(
            $group['people'],
            static function ( $a, $b ) {
                return strcasecmp( (string) $a['name'], (string) $b['name'] );
            }
        );
    }
    unset( $group );

    usort(
        $groups,
        static function ( $a, $b ) {
            if ( (int) $a['priority'] !== (int) $b['priority'] ) {
                return (int) $a['priority'] <=> (int) $b['priority'];
            }

            return (int) $a['order'] <=> (int) $b['order'];
        }
    );

    return $groups;
}

/**
 * Converts responsible people to the same card structure used by Staff.
 *
 * @param array $leaders Responsible people.
 * @return array
 */
function cimne_research_staff_leader_cards( array $leaders ) {
    $cards = array();

    foreach ( $leaders as $leader ) {
        $name = isset( $leader['name'] ) ? trim( (string) $leader['name'] ) : '';

        if ( '' === $name ) {
            continue;
        }

        $cards[] = array(
            'person_id' => isset( $leader['person_id'] ) ? $leader['person_id'] : null,
            'name'      => $name,
            'phone'     => isset( $leader['phone'] ) ? trim( (string) $leader['phone'] ) : '',
            'email'     => isset( $leader['email'] ) ? trim( (string) $leader['email'] ) : '',
        );
    }

    return $cards;
}

/**
 * Builds the Staff template context.
 *
 * @param string $code Research-unit code.
 * @return array|WP_Error
 */
function cimne_research_get_staff_context( $code ) {
    $current_unit = cimne_research_get_unit_complete( $code );

    if ( is_wp_error( $current_unit ) ) {
        return $current_unit;
    }

    $is_cluster = cimne_research_unit_is_cluster( $current_unit );

    if ( $is_cluster ) {
        $staff_units = cimne_research_get_cluster_children( $code );

        if ( is_wp_error( $staff_units ) ) {
            return $staff_units;
        }
    } else {
        $staff_units = array( $current_unit );
    }

    $leaders             = cimne_research_get_responsible_people( $staff_units );
    $excluded_identities = array();

    foreach ( $leaders as $leader ) {
        $identity = cimne_research_person_identity( $leader );

        if ( '' !== $identity ) {
            $excluded_identities[ $identity ] = true;
        }
    }

    $role_groups = cimne_research_group_staff_by_role( $staff_units, $excluded_identities );
    $leader_cards = cimne_research_staff_leader_cards( $leaders );

    if ( ! empty( $leader_cards ) ) {
        array_unshift(
            $role_groups,
            array(
                'label'     => __( 'Group Leaders', 'cimne' ),
                'role_code' => 'RESPONSIBLE',
                'priority'  => -100,
                'order'     => -100,
                'people'    => $leader_cards,
            )
        );
    }

    return array(
        'research_unit_code' => $code,
        'is_cluster'         => $is_cluster,
        'role_groups'        => $role_groups,
    );
}

/**
 * Renders the Staff component.
 *
 * @param array $context Staff context.
 * @return string
 */
function cimne_research_render_staff( array $context ) {
    ob_start();

    get_template_part(
        'template-parts/research/staff',
        null,
        $context
    );

    return (string) ob_get_clean();
}

/**
 * Shortcode used inside Divi: [cimne_research_staff]
 *
 * @return string
 */
function cimne_research_staff_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $code = cimne_research_get_unit_code();

    if ( '' === $code ) {
        cimne_api_log(
            'Research Staff shortcode has no research_unit_code.',
            array( 'post_id' => (int) get_queried_object_id() )
        );

        return '';
    }

    $context = cimne_research_get_staff_context( $code );

    if ( is_wp_error( $context ) ) {
        cimne_api_log(
            'Research Staff component could not be rendered.',
            array(
                'post_id'    => (int) get_queried_object_id(),
                'unit_code'  => $code,
                'error_code' => $context->get_error_code(),
            )
        );

        return '';
    }

    return cimne_research_render_staff( $context );
}
add_shortcode( 'cimne_research_staff', 'cimne_research_staff_shortcode' );
