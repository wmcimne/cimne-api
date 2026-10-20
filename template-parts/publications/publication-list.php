<?php
/**
 * Publications list.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : array();

if ( empty( $items ) ) :
    ?>
    <p class="no-publications"><?php echo esc_html__( 'No publications found.', 'cimne' ); ?></p>
    <?php
    return;
endif;

foreach ( $items as $publication ) {
    get_template_part( 'template-parts/publications/publication-item', null, array( 'publication' => $publication ) );
}
