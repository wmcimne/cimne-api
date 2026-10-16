<?php
/**
 * Directory filters: Search + Location + hierarchical Role selector (Step 7B.4).
 *
 * People always come from /directorio. research_units is used only to build
 * the PDI > Research Cluster / Innovation Unit > Research Group selector structure.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Directory-only exceptions: these units are shown as selectable parent units
 * but their Research Group children are intentionally not exposed as options.
 *
 * This affects only the Directory filter. Research Cluster / Innovation Unit
 * pages and the research_units data model remain untouched.
 *
 * @return string[]
 */
function cimne_directory_units_without_group_options() {
    return array( 'RC8', 'IU1', 'IU2', 'IU3' );
}


/**
 * Builds the Location selector options from the normalized /directorio dataset.
 *
 * centro_cod is used as the stable key and centro_desc as the visible label.
 * Empty/incomplete pairs are ignored. No location is hardcoded in the theme.
 *
 * @param array|null $people Optional normalized Directory dataset.
 * @return array<int,array{code:string,label:string}>
 */
function cimne_directory_get_locations( $people = null ) {
    if ( null === $people ) {
        $people = cimne_directory_get_people();
    }

    if ( is_wp_error( $people ) || ! is_array( $people ) ) {
        return array();
    }

    $by_code = array();

    foreach ( $people as $person ) {
        if ( ! is_array( $person ) ) {
            continue;
        }

        $code = isset( $person['centre_code'] ) ? trim( (string) $person['centre_code'] ) : '';
        $label = isset( $person['centre_name'] ) ? trim( (string) $person['centre_name'] ) : '';

        if ( '' === $code || '' === $label ) {
            continue;
        }

        if ( ! isset( $by_code[ $code ] ) ) {
            $by_code[ $code ] = array(
                'code'  => $code,
                'label' => $label,
            );
        }
    }

    $locations = array_values( $by_code );

    usort(
        $locations,
        static function ( $a, $b ) {
            return strcasecmp( (string) $a['label'], (string) $b['label'] );
        }
    );

    return $locations;
}

/**
 * Builds the PSR child departments from /directorio.
 *
 * Only people with esPSR=1 contribute options. departamento_cod is the stable
 * key and departamento_desc is the public label. The PSR parent itself remains
 * available to show all PSR staff regardless of department.
 *
 * @param array|null $people Optional normalized Directory dataset.
 * @return array<int,array{code:string,label:string}>
 */
function cimne_directory_get_psr_departments( $people = null ) {
    if ( null === $people ) {
        $people = cimne_directory_get_people();
    }

    if ( is_wp_error( $people ) || ! is_array( $people ) ) {
        return array();
    }

    $by_code = array();

    foreach ( $people as $person ) {
        if ( ! is_array( $person ) || empty( $person['is_psr'] ) ) {
            continue;
        }

        $code = isset( $person['department_code'] ) ? trim( (string) $person['department_code'] ) : '';
        $label = isset( $person['department_name'] ) ? trim( (string) $person['department_name'] ) : '';

        if ( '' === $code || '' === $label ) {
            continue;
        }

        if ( ! isset( $by_code[ $code ] ) ) {
            $by_code[ $code ] = array(
                'code'  => $code,
                'label' => $label,
            );
        }
    }

    $departments = array_values( $by_code );

    usort(
        $departments,
        static function ( $a, $b ) {
            return strcasecmp( (string) $a['label'], (string) $b['label'] );
        }
    );

    return $departments;
}

/**
 * Reduces research_units to the structural data needed by the Directory role
 * selector. Only Research Clusters (RC), Innovation Units (IU) and Research Groups (RTD) are kept.
 *
 * @param array  $payload  Raw research_units response.
 * @param string $resource API resource.
 * @param array  $params   Query params.
 * @return array
 */
function cimne_directory_normalize_role_units_payload( array $payload, $resource = '', array $params = array() ) {
    if ( ! function_exists( 'cimne_normalize_research_units' ) ) {
        return array();
    }

    $units = cimne_normalize_research_units( $payload );
    $result = array();

    foreach ( $units as $unit ) {
        if ( ! is_array( $unit ) ) {
            continue;
        }

        $type = isset( $unit['type_code'] )
            ? strtoupper( trim( (string) $unit['type_code'] ) )
            : '';

        if ( ! in_array( $type, array( 'RC', 'IU', 'RTD' ), true ) ) {
            continue;
        }

        $code = isset( $unit['code'] ) ? trim( (string) $unit['code'] ) : '';

        if ( '' === $code ) {
            continue;
        }

        $parent_codes = isset( $unit['parent_codes'] ) && is_array( $unit['parent_codes'] )
            ? array_values( array_filter( array_map( 'strval', $unit['parent_codes'] ) ) )
            : array();

        $result[] = array(
            'code'         => $code,
            'name'         => isset( $unit['name'] ) ? trim( (string) $unit['name'] ) : $code,
            'type'         => $type,
            'parent_codes' => $parent_codes,
        );
    }

    return $result;
}

/**
 * Returns the reduced active RC/IU/RTD unit set used by the selector.
 *
 * The external WS is called without filters. Filtering and tree construction
 * are performed locally so Directory never depends on Projects options.
 *
 * @return array|WP_Error
 */
function cimne_directory_get_role_units() {
    return cimne_api_get(
        'research_units',
        array(),
        array(
            'cache_variant' => 'directory-role-units-v2',
            'transform'     => 'cimne_directory_normalize_role_units_payload',
        )
    );
}

/**
 * Returns current-language WordPress titles keyed by research_unit_code.
 *
 * A single WP_Query is used instead of one query per unit. WPML is allowed to
 * filter the query. API unit names remain the fallback when no matching page
 * exists in the current language.
 *
 * @return array<string,string>
 */
function cimne_directory_get_unit_title_map() {
    static $map = null;

    if ( null !== $map ) {
        return $map;
    }

    $map = array();

    $query = new WP_Query(
        array(
            'post_type'        => 'page',
            'post_status'      => 'publish',
            'posts_per_page'   => -1,
            'fields'           => 'ids',
            'no_found_rows'    => true,
            'suppress_filters' => false,
            'meta_query'       => array(
                array(
                    'key'     => 'research_unit_code',
                    'compare' => 'EXISTS',
                ),
            ),
        )
    );

    foreach ( $query->posts as $page_id ) {
        $code = trim( (string) get_post_meta( (int) $page_id, 'research_unit_code', true ) );
        $title = trim( wp_strip_all_tags( get_the_title( (int) $page_id ) ) );

        if ( '' !== $code && '' !== $title ) {
            $map[ strtoupper( $code ) ] = $title;
        }
    }

    return $map;
}

/**
 * Gets the display label for one unit, preferring the current-language WP page
 * title and falling back to the API name.
 *
 * @param array $unit Reduced unit.
 * @param array $title_map Current-language code => title map.
 * @return string
 */
function cimne_directory_role_unit_label( array $unit, array $title_map ) {
    $code = isset( $unit['code'] ) ? strtoupper( trim( (string) $unit['code'] ) ) : '';

    if ( '' !== $code && isset( $title_map[ $code ] ) ) {
        return $title_map[ $code ];
    }

    if ( ! empty( $unit['name'] ) ) {
        return trim( (string) $unit['name'] );
    }

    return $code;
}

/**
 * Builds the selector tree:
 *
 * PDI
 *   Research Cluster / Innovation Unit
 *     Research Group
 *
 * Cluster and Innovation Unit options carry their own code plus all RTD
 * descendants. Group options carry only their own code. People are never
 * sourced from this tree;
 * it only supplies filter metadata.
 *
 * @return array|WP_Error
 */
function cimne_directory_get_role_tree() {
    $units = cimne_directory_get_role_units();

    if ( is_wp_error( $units ) ) {
        return $units;
    }

    $title_map = cimne_directory_get_unit_title_map();
    $parent_units = array();
    $groups = array();

    foreach ( $units as $unit ) {
        if ( ! is_array( $unit ) ) {
            continue;
        }

        if ( in_array( $unit['type'], array( 'RC', 'IU' ), true ) ) {
            $parent_units[] = $unit;
        } elseif ( 'RTD' === $unit['type'] ) {
            $groups[] = $unit;
        }
    }

    $tree = array();

    foreach ( $parent_units as $parent_unit ) {
        $parent_unit_code = trim( (string) $parent_unit['code'] );
        $parent_unit_type = isset( $parent_unit['type'] )
            ? strtoupper( trim( (string) $parent_unit['type'] ) )
            : '';
        $children = array();

        foreach ( $groups as $group ) {
            $parents = isset( $group['parent_codes'] ) && is_array( $group['parent_codes'] )
                ? array_map( 'strval', $group['parent_codes'] )
                : array();

            $belongs = false;

            foreach ( $parents as $group_parent_code ) {
                if ( 0 === strcasecmp( trim( $group_parent_code ), $parent_unit_code ) ) {
                    $belongs = true;
                    break;
                }
            }

            if ( ! $belongs ) {
                continue;
            }

            $children[] = array(
                'code'  => trim( (string) $group['code'] ),
                'label' => cimne_directory_role_unit_label( $group, $title_map ),
            );
        }

        usort(
            $children,
            static function ( $a, $b ) {
                return strcasecmp( (string) $a['label'], (string) $b['label'] );
            }
        );

        $descendant_codes = array( $parent_unit_code );

        foreach ( $children as $child ) {
            $descendant_codes[] = (string) $child['code'];
        }

        $hide_group_options = in_array(
            strtoupper( $parent_unit_code ),
            cimne_directory_units_without_group_options(),
            true
        );

        $tree[] = array(
            'code'        => $parent_unit_code,
            'label'       => cimne_directory_role_unit_label( $parent_unit, $title_map ),
            'type'        => $parent_unit_type,
            'unit_codes'  => array_values( array_unique( $descendant_codes ) ),
            'groups'      => $children,
            'show_groups' => ! $hide_group_options,
        );
    }

    usort(
        $tree,
        static function ( $a, $b ) {
            return strcasecmp( (string) $a['label'], (string) $b['label'] );
        }
    );

    return $tree;
}

/**
 * Shortcode: [cimne_directory_filters]
 *
 * Search, Location and Role are applied entirely in JavaScript to the SSR
 * Directory cards. Location uses the centro_cod value exposed by /directorio.
 *
 * @return string
 */
function cimne_directory_filters_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $role_tree = cimne_directory_get_role_tree();
    $people = cimne_directory_get_people();

    if ( is_wp_error( $role_tree ) ) {
        if ( function_exists( 'cimne_api_log' ) ) {
            cimne_api_log(
                'Directory role filter could not be rendered.',
                array( 'error_code' => $role_tree->get_error_code() )
            );
        }

        $role_tree = array();
    }

    if ( is_wp_error( $people ) ) {
        if ( function_exists( 'cimne_api_log' ) ) {
            cimne_api_log(
                'Directory filter metadata could not be built from /directorio.',
                array( 'error_code' => $people->get_error_code() )
            );
        }

        $people = array();
    }

    ob_start();

    get_template_part(
        'template-parts/directory/filters',
        null,
        array(
            'role_tree'       => $role_tree,
            'locations'       => cimne_directory_get_locations( $people ),
            'psr_departments' => cimne_directory_get_psr_departments( $people ),
        )
    );

    return (string) ob_get_clean();
}
add_shortcode( 'cimne_directory_filters', 'cimne_directory_filters_shortcode' );
