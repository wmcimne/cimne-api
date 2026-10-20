<?php
/**
 * Normalizers for /webcimne/publicaciones.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Reads the first existing key from an array.
 *
 * @param array $data Source array.
 * @param array $keys Candidate keys.
 * @param mixed $default Default value.
 * @return mixed
 */
function cimne_publications_value( array $data, array $keys, $default = null ) {
    foreach ( $keys as $key ) {
        if ( array_key_exists( $key, $data ) ) {
            return $data[ $key ];
        }
    }

    return $default;
}

/**
 * Normalizes one publication author.
 *
 * @param array $author Raw author.
 * @return array
 */
function cimne_publications_normalize_author( array $author ) {
    $aff_id = trim( (string) cimne_publications_value( $author, array( 'aff_id' ), '' ) );

    return array(
        'aff_id'       => $aff_id,
        'org_id'       => cimne_publications_value( $author, array( 'org_id' ) ),
        'scopus'       => cimne_publications_value( $author, array( 'scopus' ) ),
        'order'        => (int) cimne_publications_value( $author, array( 'auth_orden', 'order' ), 0 ),
        'indexed_name' => trim( (string) cimne_publications_value( $author, array( 'indexed_name', 'name' ), '' ) ),
        'is_cimne'     => '60104172' === $aff_id,
    );
}

/**
 * Normalizes one publication for the public list.
 * bib_html is intentionally not retained: it is used by the upstream WS for
 * searching, but it is not rendered on the website.
 *
 * @param array $publication Raw publication.
 * @return array
 */
function cimne_publications_normalize_item( array $publication ) {
    $authors = cimne_publications_value( $publication, array( 'authors' ), array() );
    $authors = is_array( $authors ) ? $authors : array();
    $authors = array_values(
        array_filter(
            array_map(
                static function ( $author ) {
                    return is_array( $author ) ? cimne_publications_normalize_author( $author ) : null;
                },
                $authors
            ),
            static function ( $author ) {
                return is_array( $author ) && '' !== $author['indexed_name'];
            }
        )
    );

    usort(
        $authors,
        static function ( $a, $b ) {
            return (int) $a['order'] <=> (int) $b['order'];
        }
    );

    return array(
        'eid'              => trim( (string) cimne_publications_value( $publication, array( 'eid' ), '' ) ),
        'doi'              => trim( (string) cimne_publications_value( $publication, array( 'doi' ), '' ) ),
        'title'            => trim( (string) cimne_publications_value( $publication, array( 'title' ), '' ) ),
        'date'             => trim( (string) cimne_publications_value( $publication, array( 'date' ), '' ) ),
        'year'             => trim( (string) cimne_publications_value( $publication, array( 'year' ), '' ) ),
        'subtype'          => strtolower( trim( (string) cimne_publications_value( $publication, array( 'subtype' ), '' ) ) ),
        'subtype_label'    => trim( (string) cimne_publications_value( $publication, array( 'subtype_description' ), '' ) ),
        'publication_name' => trim( (string) cimne_publications_value( $publication, array( 'publication_name' ), '' ) ),
        'volume'           => trim( (string) cimne_publications_value( $publication, array( 'volume' ), '' ) ),
        'issue'            => trim( (string) cimne_publications_value( $publication, array( 'issue_identifier', 'issue', 'issueIdentifier' ), '' ) ),
        'page_range'       => trim( (string) cimne_publications_value( $publication, array( 'page_range' ), '' ) ),
        'authors'          => $authors,
    );
}

/**
 * Extracts items and meta from the Publications payload.
 *
 * The live WS now exposes pagination meta. This extractor accepts the common
 * wrapper names defensively so a harmless naming difference does not break the
 * component.
 *
 * @param array $payload Raw API payload.
 * @return array{items:array,meta:array}
 */
function cimne_publications_extract_payload( array $payload ) {
    $meta  = isset( $payload['meta'] ) && is_array( $payload['meta'] ) ? $payload['meta'] : array();
    $items = null;

    foreach ( array( 'data', 'items', 'results', 'publications', 'publicaciones' ) as $key ) {
        if ( isset( $payload[ $key ] ) && is_array( $payload[ $key ] ) ) {
            $items = $payload[ $key ];
            break;
        }
    }

    if ( null === $items ) {
        $is_list = function_exists( 'array_is_list' ) ? array_is_list( $payload ) : array_keys( $payload ) === range( 0, count( $payload ) - 1 );
        $items   = $is_list ? $payload : array();
    }

    return array(
        'items' => $items,
        'meta'  => $meta,
    );
}

/**
 * Reduces one Publications API page before it is cached.
 *
 * @param array  $payload  Raw payload.
 * @param string $resource Resource name.
 * @param array  $params   Query params.
 * @return array
 */
function cimne_publications_normalize_payload( array $payload, $resource = '', array $params = array() ) {
    $extracted = cimne_publications_extract_payload( $payload );
    $items     = array();

    foreach ( $extracted['items'] as $publication ) {
        if ( ! is_array( $publication ) ) {
            continue;
        }

        $item = cimne_publications_normalize_item( $publication );

        if ( '' === $item['eid'] && '' === $item['title'] ) {
            continue;
        }

        $items[] = $item;
    }

    $limit    = isset( $extracted['meta']['limit'] ) ? max( 1, (int) $extracted['meta']['limit'] ) : max( 1, (int) ( $params['limit'] ?? 12 ) );
    $offset   = isset( $extracted['meta']['offset'] ) ? max( 0, (int) $extracted['meta']['offset'] ) : max( 0, (int) ( $params['offset'] ?? 0 ) );
    $returned = isset( $extracted['meta']['returned'] ) ? max( 0, (int) $extracted['meta']['returned'] ) : count( $items );
    $total    = isset( $extracted['meta']['total'] ) ? max( 0, (int) $extracted['meta']['total'] ) : $returned;
    $pages    = isset( $extracted['meta']['pages'] ) ? max( 0, (int) $extracted['meta']['pages'] ) : ( $limit > 0 ? (int) ceil( $total / $limit ) : 0 );

    return array(
        'items' => $items,
        'meta'  => array(
            'limit'    => $limit,
            'offset'   => $offset,
            'returned' => $returned,
            'total'    => $total,
            'pages'    => $pages,
        ),
    );
}

/**
 * Reduces Research Cluster options to code/name only.
 *
 * @param array $payload Raw research_units response.
 * @return array
 */
function cimne_publications_normalize_cluster_options( array $payload ) {
    $options = array();
    $units   = function_exists( 'cimne_normalize_research_units' ) ? cimne_normalize_research_units( $payload ) : array();

    foreach ( $units as $unit ) {
        $code = isset( $unit['code'] ) ? trim( (string) $unit['code'] ) : '';
        $name = isset( $unit['name'] ) ? trim( (string) $unit['name'] ) : '';

        if ( '' === $code || '' === $name ) {
            continue;
        }

        $options[] = array(
            'code' => $code,
            'name' => $name,
        );
    }

    usort(
        $options,
        static function ( $a, $b ) {
            return strcasecmp( $a['name'], $b['name'] );
        }
    );

    return $options;
}

/**
 * Reduces research-unit personnel to unique fnet IDs for auth_org_id.
 *
 * @param array $payload Raw research_units response.
 * @return array
 */
function cimne_publications_normalize_author_ids( array $payload ) {
    $ids   = array();
    $units = function_exists( 'cimne_normalize_research_units' ) ? cimne_normalize_research_units( $payload ) : array();

    foreach ( $units as $unit ) {
        $people = isset( $unit['people'] ) && is_array( $unit['people'] ) ? $unit['people'] : array();

        foreach ( $people as $person ) {
            $id = isset( $person['person_id'] ) ? absint( $person['person_id'] ) : 0;

            if ( $id ) {
                $ids[ $id ] = $id;
            }
        }
    }

    return array_values( $ids );
}
