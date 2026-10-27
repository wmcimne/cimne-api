<?php
/**
 * Shared encoded email copy control.
 *
 * Expected $args:
 * - email: public staff email address.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$email = isset( $args['email'] ) ? trim( (string) $args['email'] ) : '';
$email_id = cimne_staff_email_element_id( $email );

if ( '' === $email_id ) {
    return;
}
?>
<p
    id="<?php echo esc_attr( $email_id ); ?>"
    class="staff-email copy-email-to-clipboard"
    role="button"
    tabindex="0"
    aria-live="polite"
><?php echo esc_html__( 'Copy email', 'cimne' ); ?></p>
