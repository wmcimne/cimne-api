<?php
/**
 * Normalizers for CIMNE API data used by the Research pages.
 *
 * Only fields required by the current Research-page designs are retained.
 * Project payloads are reduced before they are written to the API cache.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Returns the first existing key from an array.
 *
 * @param array $data    Source data.
 * @param array $keys    Candidate keys in priority order.
 * @param mixed $default Default value.
 * @return mixed
 */
function cimne_api_value( array $data, array $keys, $default = null ) {
    foreach ( $keys as $key ) {
        if ( array_key_exists( $key, $data ) ) {
            return $data[ $key ];
        }
    }

    return $default;
}

/**
 * Normalizes one person from research_units normal or complete view.
 *
 * @param array $person Raw person data.
 * @return array
 */
function cimne_normalize_research_person( array $person ) {
    $first_name = (string) cimne_api_value( $person, array( 'nombre', 'Nombre' ), '' );
    $last_name1 = (string) cimne_api_value( $person, array( 'apellido1', 'Apellido1' ), '' );
    $last_name2 = (string) cimne_api_value( $person, array( 'apellido2', 'Apellido2' ), '' );
    $full_name  = trim( implode( ' ', array_filter( array( $first_name, $last_name1, $last_name2 ) ) ) );

    $api_full_name = cimne_api_value( $person, array( 'nombre_completo' ) );

    if ( is_string( $api_full_name ) && '' !== trim( $api_full_name ) ) {
        $full_name = trim( $api_full_name );
    }

    return array(
        'person_id'        => cimne_api_value( $person, array( 'fnet_id', 'IdInvestigador', 'id' ) ),
        'full_name'        => $full_name,
        'is_pi'            => (bool) cimne_api_value( $person, array( 'es_ip', 'EsIP' ), false ),
        'role_code'        => trim( (string) cimne_api_value( $person, array( 'rol_cod', 'RolCod' ), '' ) ),
        'role_description' => trim( (string) cimne_api_value( $person, array( 'rol_desc', 'RolDesc' ), '' ) ),
        'email'            => trim( (string) cimne_api_value( $person, array( 'email' ), '' ) ),
        'phone'            => trim( (string) cimne_api_value( $person, array( 'telefono' ), '' ) ),
    );
}

/**
 * Normalizes one research unit and its personnel.
 *
 * @param array $unit Raw research unit.
 * @return array
 */
function cimne_normalize_research_unit( array $unit ) {
    $people = cimne_api_value( $unit, array( 'personal' ), array() );
    $people = is_array( $people ) ? $people : array();

    $parent_codes = cimne_api_value( $unit, array( 'parent_codes' ), array() );
    $parent_codes = is_array( $parent_codes ) ? $parent_codes : array();

    return array(
        'code'             => trim( (string) cimne_api_value( $unit, array( 'codigo' ), '' ) ),
        'name'             => trim( (string) cimne_api_value( $unit, array( 'nombre' ), '' ) ),
        'responsible'      => trim( (string) cimne_api_value( $unit, array( 'responsable', 'responsible' ), '' ) ),
        'parent_codes'     => array_values( array_filter( array_map( 'strval', $parent_codes ) ) ),
        'type_code'        => trim( (string) cimne_api_value( $unit, array( 'tipo_unidad_cod' ), '' ) ),
        'type_description' => trim( (string) cimne_api_value( $unit, array( 'tipo_unidad_desc' ), '' ) ),
        'people'           => array_map( 'cimne_normalize_research_person', $people ),
    );
}

/**
 * Normalizes a list of research units.
 *
 * @param array $units Raw list.
 * @return array
 */
function cimne_normalize_research_units( array $units ) {
    return array_map( 'cimne_normalize_research_unit', $units );
}

/**
 * Returns stakeholder names matching one contribution-type code.
 *
 * @param array  $stakeholders Raw stakeholder array.
 * @param string $type_code    Contribution type code (e.g. FIN or C).
 * @return array
 */
function cimne_project_stakeholder_names_by_type( array $stakeholders, $type_code ) {
    $names     = array();
    $type_code = strtoupper( trim( (string) $type_code ) );

    foreach ( $stakeholders as $stakeholder ) {
        if ( ! is_array( $stakeholder ) ) {
            continue;
        }

        $current_type = strtoupper( trim( (string) cimne_api_value( $stakeholder, array( 'tipo_aportacion_codigo' ), '' ) ) );

        if ( $type_code !== $current_type ) {
            continue;
        }

        $name = trim( (string) cimne_api_value( $stakeholder, array( 'razon_social' ), '' ) );

        if ( '' !== $name ) {
            $names[] = $name;
        }
    }

    return array_values( array_unique( $names ) );
}

/**
 * Detects whether a project-person relation represents the Principal Investigator.
 *
 * @param array $person Raw project-person data.
 * @return bool
 */
function cimne_project_person_is_principal_investigator( array $person ) {
    $capacity = trim( (string) cimne_api_value( $person, array( 'en_calidad_descripcion' ), '' ) );
    $capacity = strtoupper( remove_accents( $capacity ) );

    return false !== strpos( $capacity, 'INVESTIGADOR' )
        && false !== strpos( $capacity, 'PRINCIPAL' );
}

/**
 * Extracts the Principal Investigator used by the project-detail design.
 *
 * email and photo_url are intentionally reserved now so the template does not
 * need to be rebuilt when those fields are added to the WS later.
 *
 * @param array $people Raw project-person array.
 * @return array|null
 */
function cimne_project_extract_principal_investigator( array $people ) {
    foreach ( $people as $person ) {
        if ( ! is_array( $person ) || ! cimne_project_person_is_principal_investigator( $person ) ) {
            continue;
        }

        $name = trim(
            implode(
                ' ',
                array_filter(
                    array(
                        trim( (string) cimne_api_value( $person, array( 'nombre' ), '' ) ),
                        trim( (string) cimne_api_value( $person, array( 'apellido1' ), '' ) ),
                        trim( (string) cimne_api_value( $person, array( 'apellido2' ), '' ) ),
                    )
                )
            )
        );

        return array(
            'id'        => cimne_api_value( $person, array( 'idtercero' ) ),
            'name'      => $name,
            'email'     => null,
            'photo_url' => null,
        );
    }

    return null;
}

/**
 * Returns the Fundanet identifiers of all Principal Investigators attached to
 * one project. Only the identifiers required by the client-side PI filter are
 * retained; the rest of the project-person payload is discarded.
 *
 * @param array $people Raw project-person array.
 * @return array<int|string>
 */
function cimne_project_principal_investigator_ids( array $people ) {
    $ids = array();

    foreach ( $people as $person ) {
        if ( ! is_array( $person ) || ! cimne_project_person_is_principal_investigator( $person ) ) {
            continue;
        }

        $id = cimne_api_value( $person, array( 'idtercero' ) );

        if ( null === $id || '' === trim( (string) $id ) ) {
            continue;
        }

        $ids[] = is_numeric( $id ) ? (int) $id : trim( (string) $id );
    }

    return array_values( array_unique( $ids, SORT_REGULAR ) );
}

/**
 * Reduces one project to the fields required by the project-list design.
 *
 * Correct mapping:
 * - Project title: titulo_completo
 * - Acronym: titulo_abreviado
 *
 * @param array $project Raw project data.
 * @return array
 */
function cimne_normalize_project_list_item( array $project ) {
    $stakeholders = cimne_api_value( $project, array( 'stakeholders' ), array() );
    $people       = cimne_api_value( $project, array( 'personal' ), array() );

    $stakeholders = is_array( $stakeholders ) ? $stakeholders : array();
    $people       = is_array( $people ) ? $people : array();

    return array(
        'code'                       => trim( (string) cimne_api_value( $project, array( 'codigo' ), '' ) ),
        'acronym'                    => trim( (string) cimne_api_value( $project, array( 'titulo_abreviado' ), '' ) ),
        'title'                      => trim( (string) cimne_api_value( $project, array( 'titulo_completo' ), '' ) ),
        'start_date'                 => cimne_api_value( $project, array( 'fecha_comienzo' ) ),
        'end_date'                   => cimne_api_value( $project, array( 'fecha_fin' ) ),
        'category'                   => trim( (string) cimne_api_value( $project, array( 'naturaleza_proyecto_descripcion' ), '' ) ),
        'funding_bodies'             => cimne_project_stakeholder_names_by_type( $stakeholders, 'FIN' ),
        'principal_investigator_ids' => cimne_project_principal_investigator_ids( $people ),
        // Pending WS fields already present in the design.
        'program'                    => null,
        'website_url'                => null,
    );
}

/**
 * Reduces a Projects API list response before it is cached.
 *
 * @param array  $projects Raw Projects API response.
 * @param string $resource Resource name (unused, required by transform contract).
 * @param array  $params   Query params (unused, required by transform contract).
 * @return array
 */
function cimne_normalize_project_list_payload( array $projects, $resource = '', array $params = array() ) {
    return array_map( 'cimne_normalize_project_list_item', $projects );
}

/**
 * Reduces one project to the fields required by the project-detail design.
 *
 * Fields not yet defined in the WS remain null placeholders only when they are
 * already part of the approved design (photo, PI email, website, programme,
 * call, subprogramme, grant and funding visual assets).
 *
 * @param array $project Raw project data.
 * @return array
 */
function cimne_normalize_project_detail_item( array $project ) {
    $people       = cimne_api_value( $project, array( 'personal' ), array() );
    $stakeholders = cimne_api_value( $project, array( 'stakeholders' ), array() );

    $people       = is_array( $people ) ? $people : array();
    $stakeholders = is_array( $stakeholders ) ? $stakeholders : array();

    $funding_bodies = cimne_project_stakeholder_names_by_type( $stakeholders, 'FIN' );
    $coordinators   = cimne_project_stakeholder_names_by_type( $stakeholders, 'C' );

    return array(
        'code'                   => trim( (string) cimne_api_value( $project, array( 'codigo' ), '' ) ),
        'acronym'                => trim( (string) cimne_api_value( $project, array( 'titulo_abreviado' ), '' ) ),
        'title'                  => trim( (string) cimne_api_value( $project, array( 'titulo_completo' ), '' ) ),
        'abstract'               => trim( (string) cimne_api_value( $project, array( 'objetivos' ), '' ) ),
        'reference'              => trim( (string) cimne_api_value( $project, array( 'referencia_cliente' ), '' ) ),
        'start_date'             => cimne_api_value( $project, array( 'fecha_comienzo' ) ),
        'end_date'               => cimne_api_value( $project, array( 'fecha_fin' ) ),
        'category'               => trim( (string) cimne_api_value( $project, array( 'naturaleza_proyecto_descripcion' ), '' ) ),
        'funding_bodies'         => $funding_bodies,
        'coordinators'           => $coordinators,
        'principal_investigator' => cimne_project_extract_principal_investigator( $people ),

        // Pending WS fields already represented in the approved design.
        'principal_email'        => null,
        'principal_photo_url'    => null,
        'funding_logo_url'       => null,
        'funding_notice'         => null,
        'website_url'            => null,
        'program'                => null,
        'call'                   => null,
        'subprogram'             => null,
        'grant'                  => null,
    );
}

/**
 * Reduces a project-detail API response before it is cached.
 *
 * @param array  $projects Raw Projects API response.
 * @param string $resource Resource name.
 * @param array  $params   Query params.
 * @return array
 */
function cimne_normalize_project_detail_payload( array $projects, $resource = '', array $params = array() ) {
    return array_map( 'cimne_normalize_project_detail_item', $projects );
}

/**
 * Reduces opts_projects to the values required by the global Projects filter.
 *
 * - nature: all values supplied by the WS.
 * - units: only entries with has_children=true (Research Clusters / IUs).
 * - investigators: identifier + display name only.
 *
 * @param array  $payload  Raw opts_projects response.
 * @param string $resource Resource name.
 * @param array  $params   Query params.
 * @return array
 */
function cimne_normalize_project_filter_options_payload( array $payload, $resource = '', array $params = array() ) {
    $projects = cimne_api_value( $payload, array( 'proyectos' ), array() );
    $projects = is_array( $projects ) ? $projects : array();

    $raw_nature        = cimne_api_value( $projects, array( 'naturaleza' ), array() );
    $raw_units         = cimne_api_value( $projects, array( 'unidad' ), array() );
    $raw_investigators = cimne_api_value( $projects, array( 'investigador' ), array() );

    $nature        = array();
    $units         = array();
    $investigators = array();

    foreach ( is_array( $raw_nature ) ? $raw_nature : array() as $item ) {
        if ( ! is_array( $item ) ) {
            continue;
        }

        $description = trim( (string) cimne_api_value( $item, array( 'descripcion' ), '' ) );

        if ( '' === $description ) {
            continue;
        }

        $nature[] = array(
            'value' => $description,
            'label' => $description,
        );
    }

    foreach ( is_array( $raw_units ) ? $raw_units : array() as $item ) {
        if ( ! is_array( $item ) || true !== (bool) cimne_api_value( $item, array( 'has_children' ), false ) ) {
            continue;
        }

        $code = trim( (string) cimne_api_value( $item, array( 'codigo' ), '' ) );
        $name = trim( (string) cimne_api_value( $item, array( 'nombre' ), '' ) );

        if ( '' === $code || '' === $name ) {
            continue;
        }

        $units[] = array(
            'value' => $code,
            'label' => $name,
        );
    }

    foreach ( is_array( $raw_investigators ) ? $raw_investigators : array() as $item ) {
        if ( ! is_array( $item ) ) {
            continue;
        }

        $identifier = cimne_api_value( $item, array( 'identificador' ) );
        $first_name = trim( (string) cimne_api_value( $item, array( 'nombre' ), '' ) );
        $last_name  = trim( (string) cimne_api_value( $item, array( 'apellidos' ), '' ) );
        $name       = trim( $first_name . ' ' . $last_name );

        if ( null === $identifier || '' === trim( (string) $identifier ) || '' === $name ) {
            continue;
        }

        $investigators[] = array(
            'value' => (string) $identifier,
            'label' => $name,
        );
    }

    return array(
        'nature'        => $nature,
        'units'         => $units,
        'investigators' => $investigators,
    );
}

