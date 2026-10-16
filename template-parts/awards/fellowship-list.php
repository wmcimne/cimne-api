<?php
/**
 * Fellowships list grouped by category/nature.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$status = isset( $args['status'] ) ? (string) $args['status'] : 'ongoing';
$groups = isset( $args['groups'] ) && is_array( $args['groups'] ) ? $args['groups'] : array();
?>
<div class="cimne-fellowship-list" data-list-status="<?php echo esc_attr( $status ); ?>">
    <?php if ( empty( $groups ) ) : ?>
        <p class="cimne-fellowships-empty"><?php echo esc_html__( 'No fellowships found.', 'cimne' ); ?></p>
    <?php endif; ?>

    <?php foreach ( $groups as $category => $items ) : ?>
        <section class="cimne-fellowship-list__group">
            <h3 class="cimne-fellowship-list__category"><?php echo esc_html( $category ); ?></h3>

            <?php foreach ( $items as $fellowship ) : ?>
                <?php get_template_part( 'template-parts/awards/fellowship-list-item', null, array( 'fellowship' => $fellowship ) ); ?>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
</div>
