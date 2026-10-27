<?php
/**
 * Shared person card used by Directory, Research Cluster and Research Group.
 *
 * Expected $args:
 * - person: person_id, full_name, phone, email.
 * - categories: optional list of visible category/role lines.
 * - classes: optional additional wrapper classes.
 * - attributes: optional wrapper HTML attributes, normally data-* filters.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$person = isset( $args['person'] ) && is_array( $args['person'] )
    ? $args['person']
    : array();

$person_id = isset( $person['person_id'] ) ? trim( (string) $person['person_id'] ) : '';
$full_name = isset( $person['full_name'] ) ? trim( (string) $person['full_name'] ) : '';
$phone = isset( $person['phone'] ) ? trim( (string) $person['phone'] ) : '';
$email = isset( $person['email'] ) ? trim( (string) $person['email'] ) : '';

if ( '' === $full_name ) {
    return;
}

$categories = isset( $args['categories'] ) && is_array( $args['categories'] )
    ? array_values( array_filter( array_map( 'strval', $args['categories'] ) ) )
    : array();

$classes = array( 'staff-item' );

if ( ! empty( $args['classes'] ) && is_array( $args['classes'] ) ) {
    foreach ( $args['classes'] as $class_name ) {
        $class_name = sanitize_html_class( (string) $class_name );

        if ( '' !== $class_name ) {
            $classes[] = $class_name;
        }
    }
}

$classes = array_values( array_unique( $classes ) );
$attributes = isset( $args['attributes'] ) && is_array( $args['attributes'] )
    ? $args['attributes']
    : array();

$profile_url = '' !== $person_id ? cimne_staff_profile_url( $person_id ) : '';
?>
<div
    class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
    <?php foreach ( $attributes as $attribute_name => $attribute_value ) : ?>
        <?php
        $attribute_name = strtolower( trim( (string) $attribute_name ) );

        if ( ! preg_match( '/^(data|aria)-[a-z0-9_-]+$/', $attribute_name ) ) {
            continue;
        }
        ?>
        <?php echo esc_attr( $attribute_name ); ?>="<?php echo esc_attr( (string) $attribute_value ); ?>"
    <?php endforeach; ?>
>
    <p<?php echo '' !== $person_id ? ' id="' . esc_attr( $person_id ) . '"' : ''; ?> class="staff-name open-profile">
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

    <?php if ( ! empty( $categories ) ) : ?>
        <p class="staff-category">
            <?php foreach ( $categories as $category_index => $category ) : ?>
                <?php if ( $category_index > 0 ) : ?><br><?php endif; ?>
                <?php echo esc_html( $category ); ?>
            <?php endforeach; ?>
        </p>
    <?php endif; ?>

    <?php
    get_template_part(
        'template-parts/staff/copy-email',
        null,
        array( 'email' => $email )
    );
    ?>
</div>
