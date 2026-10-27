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
                    <?php
                    get_template_part(
                        'template-parts/staff/person-card',
                        null,
                        array(
                            'person' => array(
                                'person_id' => isset( $person['person_id'] ) ? $person['person_id'] : '',
                                'full_name' => $name,
                                'phone'     => $phone,
                                'email'     => $email,
                            ),
                        )
                    );
                    ?>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>

</div>
