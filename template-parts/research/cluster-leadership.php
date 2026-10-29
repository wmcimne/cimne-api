<?php
/**
 * Contact Point, Academic Leaders and External Advisors for one Cluster.
 *
 * Expected $args:
 * - research_unit_code
 * - contact_point
 * - academic_leaders
 * - external_advisors
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$unit_code        = isset( $args['research_unit_code'] ) ? (string) $args['research_unit_code'] : '';
$contact_point    = isset( $args['contact_point'] ) ? trim( (string) $args['contact_point'] ) : '';
$academic_leaders = isset( $args['academic_leaders'] ) && is_array( $args['academic_leaders'] ) ? $args['academic_leaders'] : array();
$external_advisors = isset( $args['external_advisors'] ) && is_array( $args['external_advisors'] ) ? $args['external_advisors'] : array();

if ( '' === $contact_point && empty( $academic_leaders ) && empty( $external_advisors ) ) {
    return;
}
?>
<dl class="cluster-meta" data-unit-code="<?php echo esc_attr( $unit_code ); ?>">
    <?php if ( '' !== $contact_point ) : ?>
        <dt><?php echo esc_html__( 'Contact Point', 'cimne' ); ?></dt>
        <dd><?php echo esc_html( $contact_point ); ?></dd>
    <?php endif; ?>

    <?php if ( ! empty( $academic_leaders ) ) : ?>
        <dt><?php echo esc_html__( 'Academic Leaders', 'cimne' ); ?></dt>
        <dd><?php echo esc_html( implode( ', ', $academic_leaders ) ); ?></dd>
    <?php endif; ?>

    <?php if ( ! empty( $external_advisors ) ) : ?>
        <dt><?php echo esc_html__( 'External Advisors', 'cimne' ); ?></dt>
        <dd><?php echo esc_html( implode( ', ', $external_advisors ) ); ?></dd>
    <?php endif; ?>
</dl>
