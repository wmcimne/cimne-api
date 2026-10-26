<?php
/**
 * Compact awards list for Eugenio Oñate.
 *
 * @var array $args Template arguments.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$awards = isset( $args['awards'] ) && is_array( $args['awards'] )
    ? $args['awards']
    : array();
?>
<div id="awards-to-scientists" class="cimne-onate-awards">
    <div class="awards-profile">
        <?php if ( empty( $awards ) ) : ?>
            <p class="no-results"><?php echo esc_html__( 'No awards found.', 'cimne' ); ?></p>
        <?php else : ?>
            <ul class="awards-list">
                <?php foreach ( $awards as $award ) : ?>
                    <li><?php echo esc_html( (string) $award ); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>
