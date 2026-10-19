<?php
/**
 * Normalizers for /webcimne/becas.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Determines whether one Fundanet person relation is a fellowship beneficiary.
 *
 * Example accepted value: INVESTIGADOR/A BENEFICIARIO/A.
 *
 * @param array $person Raw person relation.
 * @return bool
 */
function cimne_fellowship_person_is_beneficiary( array $person ) {
    $capacity = trim( (string) cimne_api_value( $person, array( 'en_calidad_descripcion' ), '' ) );
    $capacity = strtoupper( remove_accents( $capacity ) );

    return false !== strpos( $capacity, 'BENEFICIARIO' );
}

/**
 * Builds one display name from a Fundanet person relation.
 *
 * @param array $person Raw person relation.
 * @return string
 */
function cimne_fellowship_person_name( array $person ) {
    return trim(
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
}

/**
 * Extracts unique beneficiaries from a fellowship.
 *
 * @param array $people Raw personal array.
 * @return array<int,array{id:int|string|null,name:string}>
 */
function cimne_fellowship_extract_beneficiaries( array $people ) {
    $beneficiaries = array();
    $seen          = array();

    foreach ( $people as $person ) {
        if ( ! is_array( $person ) || ! cimne_fellowship_person_is_beneficiary( $person ) ) {
            continue;
        }

        $id   = cimne_api_value( $person, array( 'idtercero' ) );
        $name = cimne_fellowship_person_name( $person );

        if ( '' === $name ) {
            continue;
        }

        $key = null !== $id && '' !== trim( (string) $id )
            ? 'id:' . (string) $id
            : 'name:' . strtolower( remove_accents( $name ) );

        if ( isset( $seen[ $key ] ) ) {
            continue;
        }

        $seen[ $key ] = true;
        $beneficiaries[] = array(
            'id'   => is_numeric( $id ) ? (int) $id : $id,
            'name' => $name,
        );
    }

    return $beneficiaries;
}

/**
 * Returns stakeholder names matching one contribution type.
 *
 * @param array  $stakeholders Raw stakeholders array.
 * @param string $type_code    FIN, C, etc.
 * @return array<int,string>
 */
function cimne_fellowship_stakeholder_names_by_type( array $stakeholders, $type_code ) {
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
 * Tells whether one fellowship can be shown on the public website.
 *
 * @param array $fellowship Raw API item.
 * @return bool
 */
function cimne_fellowship_is_public( array $fellowship ) {
    return 1 !== (int) cimne_api_value( $fellowship, array( 'oculto_web_publica' ), 0 );
}

/**
 * Reduces one fellowship for list rendering and browser filters.
 *
 * @param array $fellowship Raw API item.
 * @return array
 */
function cimne_normalize_fellowship_list_item( array $fellowship ) {
    $people       = cimne_api_value( $fellowship, array( 'personal' ), array() );
    $stakeholders = cimne_api_value( $fellowship, array( 'stakeholders' ), array() );
    $people       = is_array( $people ) ? $people : array();
    $stakeholders = is_array( $stakeholders ) ? $stakeholders : array();
    $beneficiaries = cimne_fellowship_extract_beneficiaries( $people );

    return array(
        'code'              => trim( (string) cimne_api_value( $fellowship, array( 'codigo' ), '' ) ),
        'acronym'           => trim( (string) cimne_api_value( $fellowship, array( 'titulo_abreviado' ), '' ) ),
        'title'             => trim( (string) cimne_api_value( $fellowship, array( 'titulo_completo' ), '' ) ),
        'start_date'        => cimne_api_value( $fellowship, array( 'fecha_comienzo' ) ),
        'end_date'          => cimne_api_value( $fellowship, array( 'fecha_fin', 'fecha_finalizacion' ) ),
        'category'          => trim( (string) cimne_api_value( $fellowship, array( 'naturaleza_proyecto_descripcion' ), '' ) ),
        'funding_bodies'    => cimne_fellowship_stakeholder_names_by_type( $stakeholders, 'FIN' ),
        'beneficiaries'     => $beneficiaries,
        'beneficiary_ids'   => array_values(
            array_filter(
                array_map(
                    static function ( $beneficiary ) {
                        return isset( $beneficiary['id'] ) ? $beneficiary['id'] : null;
                    },
                    $beneficiaries
                ),
                static function ( $id ) {
                    return null !== $id && '' !== trim( (string) $id );
                }
            )
        ),
        'beneficiary_names' => array_values(
            array_map(
                static function ( $beneficiary ) {
                    return isset( $beneficiary['name'] ) ? (string) $beneficiary['name'] : '';
                },
                $beneficiaries
            )
        ),
    );
}

/**
 * Reduces a fellowship list response before caching.
 * Hidden public-web records are discarded here and never reach the template.
 *
 * @param array  $payload  Raw API list.
 * @param string $resource Resource name.
 * @param array  $params   Query params.
 * @return array
 */
function cimne_normalize_fellowship_list_payload( array $payload, $resource = '', array $params = array() ) {
    $normalized = array();

    foreach ( $payload as $fellowship ) {
        if ( ! is_array( $fellowship ) || ! cimne_fellowship_is_public( $fellowship ) ) {
            continue;
        }

        $item = cimne_normalize_fellowship_list_item( $fellowship );

        if ( '' !== $item['code'] ) {
            $normalized[] = $item;
        }
    }

    return $normalized;
}

/**
 * Reduces one fellowship for the inline detail view.
 *
 * @param array $fellowship Raw API item.
 * @return array
 */
function cimne_normalize_fellowship_detail_item( array $fellowship ) {
    $people       = cimne_api_value( $fellowship, array( 'personal' ), array() );
    $stakeholders = cimne_api_value( $fellowship, array( 'stakeholders' ), array() );
    $people       = is_array( $people ) ? $people : array();
    $stakeholders = is_array( $stakeholders ) ? $stakeholders : array();

    return array(
        'code'             => trim( (string) cimne_api_value( $fellowship, array( 'codigo' ), '' ) ),
        'acronym'          => trim( (string) cimne_api_value( $fellowship, array( 'titulo_abreviado' ), '' ) ),
        'title'            => trim( (string) cimne_api_value( $fellowship, array( 'titulo_completo' ), '' ) ),
        'abstract'         => trim( (string) cimne_api_value( $fellowship, array( 'objetivos' ), '' ) ),
        'reference'        => trim( (string) cimne_api_value( $fellowship, array( 'referencia_cliente' ), '' ) ),
        'start_date'       => cimne_api_value( $fellowship, array( 'fecha_comienzo' ) ),
        'end_date'         => cimne_api_value( $fellowship, array( 'fecha_fin', 'fecha_finalizacion' ) ),
        'category'         => trim( (string) cimne_api_value( $fellowship, array( 'naturaleza_proyecto_descripcion' ), '' ) ),
        'programme'        => trim( (string) cimne_api_value( $fellowship, array( 'titulo_presupuesto' ), '' ) ),
        'type'             => trim( (string) cimne_api_value( $fellowship, array( 'tipo_proyecto_descripcion' ), '' ) ),
        'subtype'          => trim( (string) cimne_api_value( $fellowship, array( 'subtipo_proyecto_descripcion' ), '' ) ),
        'initiative'       => trim( (string) cimne_api_value( $fellowship, array( 'iniciativa_proyecto_descripcion' ), '' ) ),
        'funding_mode'     => trim( (string) cimne_api_value( $fellowship, array( 'modo_financiacion_proyecto_descripcion' ), '' ) ),
        'management_unit'  => trim( (string) cimne_api_value( $fellowship, array( 'unidad_gestion_descripcion' ), '' ) ),
        'department'       => trim( (string) cimne_api_value( $fellowship, array( 'departamento_descripcion' ), '' ) ),
        'centre'           => trim( (string) cimne_api_value( $fellowship, array( 'centro_descripcion' ), '' ) ),
        'funding_bodies'   => cimne_fellowship_stakeholder_names_by_type( $stakeholders, 'FIN' ),
        'beneficiaries'    => cimne_fellowship_extract_beneficiaries( $people ),
    );
}

/**
 * Reduces a fellowship-detail response before caching.
 *
 * @param array  $payload  Raw API list.
 * @param string $resource Resource name.
 * @param array  $params   Query params.
 * @return array
 */
function cimne_normalize_fellowship_detail_payload( array $payload, $resource = '', array $params = array() ) {
    $normalized = array();

    foreach ( $payload as $fellowship ) {
        if ( ! is_array( $fellowship ) || ! cimne_fellowship_is_public( $fellowship ) ) {
            continue;
        }

        $item = cimne_normalize_fellowship_detail_item( $fellowship );

        if ( '' !== $item['code'] ) {
            $normalized[] = $item;
        }
    }

    return $normalized;
}

/**
 * Reduces /opts_becas to the values required by the Awards / Fellowships filter.
 *
 * The endpoint follows the same envelope as opts_projects and exposes the
 * relevant data below proyectos.naturaleza and proyectos.beneficiario.
 * Research-unit and investigator options are intentionally ignored because
 * the Awards filter does not expose those controls.
 *
 * @param array  $payload  Raw /opts_becas response.
 * @param string $resource Resource name.
 * @param array  $params   Query params.
 * @return array
 */
function cimne_normalize_fellowship_filter_options_payload( array $payload, $resource = '', array $params = array() ) {
    $projects = cimne_api_value( $payload, array( 'proyectos' ), array() );
    $projects = is_array( $projects ) ? $projects : array();

    $raw_nature        = cimne_api_value( $projects, array( 'naturaleza' ), array() );
    $raw_beneficiaries = cimne_api_value( $projects, array( 'beneficiario' ), array() );

    $nature        = array();
    $beneficiaries = array();

    foreach ( is_array( $raw_nature ) ? $raw_nature : array() as $item ) {
        if ( ! is_array( $item ) ) {
            continue;
        }

        $description = trim( (string) cimne_api_value( $item, array( 'descripcion' ), '' ) );

        if ( '' === $description ) {
            continue;
        }

        $nature[ strtolower( remove_accents( $description ) ) ] = array(
            'value' => $description,
            'label' => $description,
        );
    }

    foreach ( is_array( $raw_beneficiaries ) ? $raw_beneficiaries : array() as $item ) {
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

        $beneficiaries[ (string) $identifier ] = array(
            'value' => (string) $identifier,
            'label' => $name,
        );
    }

    $nature        = array_values( $nature );
    $beneficiaries = array_values( $beneficiaries );

    usort(
        $nature,
        static function ( $a, $b ) {
            return strcasecmp( (string) $a['label'], (string) $b['label'] );
        }
    );
    usort(
        $beneficiaries,
        static function ( $a, $b ) {
            return strcasecmp( (string) $a['label'], (string) $b['label'] );
        }
    );

    return array(
        'nature'        => $nature,
        'beneficiaries' => $beneficiaries,
    );
}
