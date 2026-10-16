<?php
/**
 * Principal Investigator component for CIMNE Research Group pages.
 *
 * In the current Fundanet model, the Research Group's Principal Investigator
 * corresponds to the Group Leader(s) stored in the unit's `responsable`
 * field. The same resolver used by Staff is reused here, including support for
 * multiple leaders separated by `/`.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Builds the Principal Investigator context for one Research Group.
 *
 * @param string $code Research-unit code.
 * @return array|WP_Error
 */
function cimne_research_get_group_principal_investigator_context( $code ) {
    $unit = cimne_research_get_unit_complete( $code );

    if ( is_wp_error( $unit ) ) {
        return $unit;
    }

    if ( cimne_research_unit_is_cluster( $unit ) ) {
        return new WP_Error(
            'cimne_research_pi_not_group',
            'Principal Investigator can only be rendered for a Research Group.'
        );
    }

    $leaders = cimne_research_get_unit_responsible_people( $unit );
    $names   = array();

    foreach ( $leaders as $leader ) {
        $name = isset( $leader['name'] ) ? trim( (string) $leader['name'] ) : '';

        if ( '' !== $name ) {
            $names[] = $name;
        }
    }

    return array(
        'research_unit_code' => $code,
        'people'             => array_values( array_unique( $names ) ),
    );
}

/**
 * Renders the Research Group Principal Investigator component.
 *
 * @param array $context Template context.
 * @return string
 */
function cimne_research_render_group_principal_investigator( array $context ) {
    ob_start();

    get_template_part(
        'template-parts/research/group-principal-investigator',
        null,
        $context
    );

    return (string) ob_get_clean();
}

/**
 * Shortcode: [cimne_group_principal_investigator]
 *
 * @return string
 */
function cimne_group_principal_investigator_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '';
    }

    $code = cimne_research_get_unit_code();

    if ( '' === $code ) {
        return '';
    }

    $context = cimne_research_get_group_principal_investigator_context( $code );

    if ( is_wp_error( $context ) ) {
        cimne_api_log(
            'Research Group Principal Investigator component could not be rendered.',
            array(
                'post_id'    => (int) get_queried_object_id(),
                'unit_code'  => $code,
                'error_code' => $context->get_error_code(),
            )
        );

        return '';
    }

    return cimne_research_render_group_principal_investigator( $context );
}
add_shortcode( 'cimne_group_principal_investigator', 'cimne_group_principal_investigator_shortcode' );
