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

if ( empty( $people ) ) {
    return;
}
?>
<div
    class="cimne-group-principal-investigator"
    <?php if ( '' !== $unit_code ) : ?>data-unit-code="<?php echo esc_attr( $unit_code ); ?>"<?php endif; ?>
>
    <p class="cimne-group-principal-investigator__title">
        <?php echo esc_html__( 'Principal Investigator', 'cimne' ); ?>
    </p>

    <div class="cimne-group-principal-investigator__people">
        <?php foreach ( $people as $person_name ) : ?>
            <?php $person_name = trim( (string) $person_name ); ?>
            <?php if ( '' !== $person_name ) : ?>
                <div class="cimne-group-principal-investigator__person"><?php echo esc_html( $person_name ); ?></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
