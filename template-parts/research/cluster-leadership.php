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
<div class="cimne-cluster-leadership" data-unit-code="<?php echo esc_attr( $unit_code ); ?>">
    <?php if ( '' !== $contact_point ) : ?>
        <section class="cimne-cluster-leadership__section cimne-cluster-leadership__section--contact">
            <h3 class="cimne-cluster-leadership__title"><?php echo esc_html__( 'Contact Point', 'cimne' ); ?></h3>
            <p class="cimne-cluster-leadership__people"><?php echo esc_html( $contact_point ); ?></p>
        </section>
    <?php endif; ?>

    <?php if ( ! empty( $academic_leaders ) ) : ?>
        <section class="cimne-cluster-leadership__section cimne-cluster-leadership__section--academic">
            <h3 class="cimne-cluster-leadership__title"><?php echo esc_html__( 'Academic Leaders', 'cimne' ); ?></h3>
            <p class="cimne-cluster-leadership__people"><?php echo esc_html( implode( ', ', $academic_leaders ) ); ?></p>
        </section>
    <?php endif; ?>

    <?php if ( ! empty( $external_advisors ) ) : ?>
        <section class="cimne-cluster-leadership__section cimne-cluster-leadership__section--external">
            <h3 class="cimne-cluster-leadership__title"><?php echo esc_html__( 'External Advisors', 'cimne' ); ?></h3>
            <p class="cimne-cluster-leadership__people"><?php echo esc_html( implode( ', ', $external_advisors ) ); ?></p>
        </section>
    <?php endif; ?>
</div>
