<?php
/**
 * Researcher awards list.
 *
 * @var array $args Template arguments.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$researchers = isset( $args['researchers'] ) && is_array( $args['researchers'] )
    ? $args['researchers']
    : array();

$fallback_image = '/wp-content/uploads/2024/09/logo-color-cimne-web.png';
?>
<div id="awards-to-scientists">
    <?php if ( empty( $researchers ) ) : ?>
        <p class="no-results"><?php echo esc_html__( 'No researcher awards found.', 'cimne' ); ?></p>
    <?php else : ?>
        <?php foreach ( $researchers as $researcher ) : ?>
            <?php
            $name      = isset( $researcher['name'] ) ? (string) $researcher['name'] : '';
            $position  = isset( $researcher['position'] ) ? (string) $researcher['position'] : '';
            $image     = ! empty( $researcher['image'] ) ? (string) $researcher['image'] : $fallback_image;
            $awards    = isset( $researcher['awards'] ) && is_array( $researcher['awards'] )
                ? $researcher['awards']
                : array();
            ?>
            <div class="profile-in-awards">
                <div
                    class="profile-pic"
                    style="background-image: url('<?php echo esc_url( $image ); ?>');"
                    aria-hidden="true"
                ></div>

                <div class="awards-profile">
                    <?php if ( '' !== $name ) : ?>
                        <p class="name"><?php echo esc_html( $name ); ?></p>
                    <?php endif; ?>

                    <?php if ( '' !== $position ) : ?>
                        <p class="category"><?php echo esc_html( $position ); ?></p>
                    <?php endif; ?>

                    <?php if ( ! empty( $awards ) ) : ?>
                        <div class="awards">
                            <details>
                                <summary><?php echo esc_html__( 'Awards', 'cimne' ); ?></summary>
                                <ul class="awards-list">
                                    <?php foreach ( $awards as $award ) : ?>
                                        <li><?php echo esc_html( (string) $award ); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </details>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
