<?php
/**
 * Interactive Fellowships results shell.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$ongoing_html = isset( $args['ongoing_html'] ) ? (string) $args['ongoing_html'] : '';
$list_url     = rest_url( 'cimne/v1/fellowships/list' );
$detail_url   = rest_url( 'cimne/v1/fellowships/detail' );
?>
<div class="cimne-fellowships" data-cimne-fellowships data-list-url="<?php echo esc_url( $list_url ); ?>" data-detail-url="<?php echo esc_url( $detail_url ); ?>">
    <nav class="cimne-fellowships__tabs" aria-label="<?php echo esc_attr__( 'Fellowships status', 'cimne' ); ?>">
        <button type="button" class="cimne-fellowships__tab is-active" aria-pressed="true" data-fellowship-status="ongoing">
            <?php echo esc_html__( 'Ongoing', 'cimne' ); ?>
        </button>
        <button type="button" class="cimne-fellowships__tab" aria-pressed="false" data-fellowship-status="finished">
            <?php echo esc_html__( 'Finished', 'cimne' ); ?>
        </button>
    </nav>

    <div class="cimne-fellowships__notice" aria-live="polite" data-fellowship-notice></div>
    <div class="cimne-fellowships__content" data-fellowship-content>
        <?php echo $ongoing_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Rendered by trusted template. ?>
    </div>
</div>
