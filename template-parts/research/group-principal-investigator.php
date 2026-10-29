<?php
/**
 * Principal Investigator block for a Research Group header.
 *
 * Expected $args:
 * - research_unit_code
 * - people
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$unit_code = isset( $args['research_unit_code'] ) ? trim( (string) $args['research_unit_code'] ) : '';
$people    = isset( $args['people'] ) && is_array( $args['people'] ) ? $args['people'] : array();

$people = array_values(
    array_filter(
        array_map(
            static function ( $person_name ) {
                return trim( (string) $person_name );
            },
            $people
        ),
        static function ( $person_name ) {
            return '' !== $person_name;
        }
    )
);

if ( empty( $people ) ) {
    return;
}
?>
<dl class="cluster-meta" data-unit-code="<?php echo esc_attr( $unit_code ); ?>">
    <dt><?php echo esc_html__( 'Principal Investigator', 'cimne' ); ?></dt>
    <dd><?php echo esc_html( implode( ', ', $people ) ); ?></dd>
</dl>
