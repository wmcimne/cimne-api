<?php
/**
 * Final Staff design for both Cluster and Research Group pages.
 *
 * Expected $args:
 * - research_unit_code
 * - is_cluster
 * - role_groups
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$groups    = isset( $args['role_groups'] ) && is_array( $args['role_groups'] ) ? $args['role_groups'] : array();
$unit_code = isset( $args['research_unit_code'] ) ? (string) $args['research_unit_code'] : '';
?>
<div class="cimne-research-staff" data-unit-code="<?php echo esc_attr( $unit_code ); ?>">
    <?php if ( empty( $groups ) ) : ?>
        <p class="cimne-research-empty"><?php echo esc_html__( 'No staff found.', 'cimne' ); ?></p>
    <?php endif; ?>

    <?php foreach ( $groups as $group ) : ?>
        <?php
        $label  = isset( $group['label'] ) ? trim( (string) $group['label'] ) : '';
        $people = isset( $group['people'] ) && is_array( $group['people'] ) ? $group['people'] : array();

        if ( empty( $people ) ) {
            continue;
        }
        ?>
        <section class="cimne-research-staff__role">
            <?php if ( '' !== $label ) : ?>
                <h3 class="cimne-research-staff__role-title"><?php echo esc_html( $label ); ?></h3>
            <?php endif; ?>

            <div class="cimne-research-staff__grid">
                <?php foreach ( $people as $person ) : ?>
                    <?php
                    $name  = isset( $person['name'] ) ? trim( (string) $person['name'] ) : '';
                    $phone = isset( $person['phone'] ) ? trim( (string) $person['phone'] ) : '';
                    $email = isset( $person['email'] ) ? trim( (string) $person['email'] ) : '';

                    if ( '' === $name ) {
                        continue;
                    }
                    ?>
                    <article class="cimne-research-staff__card">
                        <h4 class="cimne-research-staff__name"><?php echo esc_html( $name ); ?></h4>

                        <div class="cimne-research-staff__contact">
                            <span class="cimne-research-staff__icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" focusable="false"><path d="M6.6 10.8c1.7 3.3 3.3 4.9 6.6 6.6l2.2-2.2c.3-.3.7-.4 1.1-.2 1.2.4 2.5.6 3.8.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.8 21 3 13.2 3 3.7c0-.6.4-1 1-1h3.4c.6 0 1 .4 1 1 0 1.3.2 2.6.6 3.8.1.4 0 .8-.3 1.1l-2.1 2.2Z"/></svg>
                            </span>
                            <span><?php echo esc_html( '' !== $phone ? $phone : __( 'No phone', 'cimne' ) ); ?></span>
                        </div>

                        <?php if ( '' !== $email ) : ?>
                            <button
                                type="button"
                                class="cimne-research-staff__copy-email"
                                data-copy-email="<?php echo esc_attr( $email ); ?>"
                            >
                                <span class="cimne-research-staff__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false"><path d="M3 5h18v14H3V5Zm2 2v.5l7 5 7-5V7H5Zm14 10V10l-7 5-7-5v7h14Z"/></svg>
                                </span>
                                <span data-copy-label><?php echo esc_html__( 'Copy email', 'cimne' ); ?></span>
                            </button>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

    <span class="screen-reader-text" aria-live="polite" data-cimne-copy-status></span>
</div>
