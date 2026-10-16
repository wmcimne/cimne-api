<?php
/**
 * Project list grouped by project nature/category.
 *
 * Expected $args:
 * - status
 * - groups: [ category => projects[] ]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$status = isset( $args['status'] ) ? (string) $args['status'] : 'ongoing';
$groups = isset( $args['groups'] ) && is_array( $args['groups'] ) ? $args['groups'] : array();
?>
<div class="cimne-project-list" data-list-status="<?php echo esc_attr( $status ); ?>">
    <?php if ( empty( $groups ) ) : ?>
        <p class="cimne-research-empty"><?php echo esc_html__( 'No projects found.', 'cimne' ); ?></p>
    <?php endif; ?>

    <?php foreach ( $groups as $category => $projects ) : ?>
        <section class="cimne-project-list__group">
            <h3 class="cimne-project-list__category"><?php echo esc_html( strtolower( (string) $category ) ); ?></h3>

            <?php foreach ( $projects as $project ) : ?>
                <?php
                get_template_part(
                    'template-parts/research/project-list-item',
                    null,
                    array( 'project' => $project )
                );
                ?>
            <?php endforeach; ?>
        </section>
    <?php endforeach; ?>
</div>
