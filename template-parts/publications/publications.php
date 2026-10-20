<?php
/**
 * Publications SSR/REST shell.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$context         = isset( $args['context'] ) && 'research' === $args['context'] ? 'research' : 'global';
$unit_code       = isset( $args['unit_code'] ) ? (string) $args['unit_code'] : '';
$initial_html    = isset( $args['initial_html'] ) ? (string) $args['initial_html'] : '';
$pagination_html = isset( $args['pagination_html'] ) ? (string) $args['pagination_html'] : '';
$total           = isset( $args['total'] ) ? (int) $args['total'] : 0;
?>
<div
    class="cimne-publications"
    data-cimne-publications
    data-publications-context="<?php echo esc_attr( $context ); ?>"
    data-unit-code="<?php echo esc_attr( $unit_code ); ?>"
    data-list-url="<?php echo esc_url( rest_url( 'cimne/v1/publications/list' ) ); ?>"
>
    <div class="cimne-publications__status screen-reader-text" aria-live="polite" data-publications-status></div>

    <div id="publications-container" data-publications-results aria-busy="false">
        <?php echo $initial_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted local template output. ?>
    </div>

    <div id="publications-pagination" data-publications-pagination>
        <?php echo $pagination_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted local template output. ?>
    </div>

    <span class="screen-reader-text" data-publications-total><?php echo esc_html( $total ); ?></span>
</div>
