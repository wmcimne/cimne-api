<?php
/**
 * One Directory card.
 *
 * Reuses the current production classes so the existing card styles and copy-
 * email behaviour remain valid. Step 7B only adds data attributes used by the
 * client-side filters.
 *
 * Expected $args:
 * - person: normalized directory person.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$person = isset( $args['person'] ) && is_array( $args['person'] )
    ? $args['person']
    : array();

$person_id  = isset( $person['id'] ) ? $person['id'] : '';
$full_name  = isset( $person['full_name'] ) ? trim( (string) $person['full_name'] ) : '';
$phone      = isset( $person['phone'] ) ? trim( (string) $person['phone'] ) : '';
$email      = isset( $person['email'] ) ? trim( (string) $person['email'] ) : '';
$category   = isset( $person['category'] ) ? trim( (string) $person['category'] ) : '';
$display_roles = isset( $person['display_roles'] ) && is_array( $person['display_roles'] )
    ? $person['display_roles']
    : array();
$centre     = isset( $person['centre_code'] ) ? trim( (string) $person['centre_code'] ) : '';
$department_code = isset( $person['department_code'] ) ? trim( (string) $person['department_code'] ) : '';
$department_name = isset( $person['department_name'] ) ? trim( (string) $person['department_name'] ) : '';
$is_psr     = ! empty( $person['is_psr'] );
$is_pdi     = ! empty( $person['is_pdi'] );
$unit_codes = isset( $person['unit_codes'] ) && is_array( $person['unit_codes'] )
    ? array_values( array_filter( array_map( 'strval', $person['unit_codes'] ) ) )
    : array();
$unit_roles = isset( $person['unit_roles'] ) && is_array( $person['unit_roles'] )
    ? $person['unit_roles']
    : array();

if ( '' === $full_name ) {
    return;
}

$search_parts = array( $full_name, $email, $category, $department_name );

foreach ( $display_roles as $display_role ) {
    if ( ! is_array( $display_role ) ) {
        continue;
    }

    if ( ! empty( $display_role['political_role'] ) ) {
        $search_parts[] = (string) $display_role['political_role'];
    }

    if ( ! empty( $display_role['role_description'] ) ) {
        $search_parts[] = (string) $display_role['role_description'];
    }
}

foreach ( $unit_roles as $unit_role ) {
    if ( ! is_array( $unit_role ) ) {
        continue;
    }

    if ( ! empty( $unit_role['unit_name'] ) ) {
        $search_parts[] = (string) $unit_role['unit_name'];
    }

    if ( ! empty( $unit_role['role_description'] ) ) {
        $search_parts[] = (string) $unit_role['role_description'];
    }
}

$search_text = trim( implode( ' ', array_filter( $search_parts ) ) );
$profile_url = '' !== (string) $person_id
    ? cimne_directory_person_profile_url( $person_id )
    : '';
$email_id = cimne_directory_email_element_id( $email );
?>

<div
    class="staff-item cimne-directory-person"
    data-cimne-directory-person
    data-person-id="<?php echo esc_attr( (string) $person_id ); ?>"
    data-search-text="<?php echo esc_attr( $search_text ); ?>"
    data-centre-code="<?php echo esc_attr( $centre ); ?>"
    data-department-code="<?php echo esc_attr( $department_code ); ?>"
    data-is-psr="<?php echo $is_psr ? '1' : '0'; ?>"
    data-is-pdi="<?php echo $is_pdi ? '1' : '0'; ?>"
    data-unit-codes="<?php echo esc_attr( implode( ',', $unit_codes ) ); ?>"
>
    <p id="<?php echo esc_attr( (string) $person_id ); ?>" class="staff-name open-profile">
        <?php if ( '' !== $profile_url ) : ?>
            <a href="<?php echo esc_url( $profile_url ); ?>">
                <?php echo esc_html( $full_name ); ?>
            </a>
        <?php else : ?>
            <?php echo esc_html( $full_name ); ?>
        <?php endif; ?>
    </p>

    <p class="staff-phone">
        <?php echo esc_html( '' !== $phone ? $phone : __( 'No phone', 'cimne' ) ); ?>
    </p>

    <?php if ( ! empty( $display_roles ) ) : ?>
        <p class="staff-category">
            <?php foreach ( $display_roles as $role_index => $display_role ) : ?>
                <?php
                if ( ! is_array( $display_role ) ) {
                    continue;
                }

                $political_role = isset( $display_role['political_role'] )
                    ? trim( (string) $display_role['political_role'] )
                    : '';
                $role_description = isset( $display_role['role_description'] )
                    ? trim( (string) $display_role['role_description'] )
                    : '';

                if ( '' === $political_role && '' === $role_description ) {
                    continue;
                }
                ?>
                <?php if ( $role_index > 0 ) : ?><br><?php endif; ?>
                <?php if ( '' !== $political_role ) : ?>
                    <?php echo esc_html( $political_role ); ?><?php if ( '' !== $role_description ) : ?>, <?php endif; ?>
                <?php endif; ?>
                <?php if ( '' !== $role_description ) : ?><?php echo esc_html( $role_description ); ?><?php endif; ?>
            <?php endforeach; ?>
        </p>
    <?php elseif ( '' !== $category ) : ?>
        <p class="staff-category"><?php echo esc_html( $category ); ?></p>
    <?php endif; ?>

    <?php if ( '' !== $email && '' !== $email_id ) : ?>
        <p
            id="<?php echo esc_attr( $email_id ); ?>"
            class="staff-email copy-email-to-clipboard"
        ><?php echo esc_html__( 'Copy email', 'cimne' ); ?></p>
    <?php endif; ?>
</div>
