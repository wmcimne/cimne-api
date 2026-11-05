<?php
/**
 * Interactive Fellowships results shell.
 *
 * The complete Ongoing/Finished lists and detail HTML are preloaded by PHP so
 * every interaction after page load is handled in the browser.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$ongoing_html  = isset( $args['ongoing_html'] ) ? (string) $args['ongoing_html'] : '';
$finished_html = isset( $args['finished_html'] ) ? (string) $args['finished_html'] : '';
$detail_html   = isset( $args['detail_html'] ) && is_array( $args['detail_html'] ) ? $args['detail_html'] : array();
$json_flags    = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<div class="cimne-fellowships" data-cimne-fellowships>
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

    <script type="application/json" data-fellowship-preloaded-lists><?php
        echo wp_json_encode(
            array(
                'ongoing'  => $ongoing_html,
                'finished' => $finished_html,
            ),
            $json_flags
        ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded by WordPress.
    ?></script>
    <script type="application/json" data-fellowship-preloaded-details><?php
        echo wp_json_encode( $detail_html, $json_flags ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON encoded by WordPress.
    ?></script>
</div>
