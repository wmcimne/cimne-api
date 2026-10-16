<?php
/**
 * Research Groups list for one Cluster.
 *
 * Expected $args:
 * - research_unit_code
 * - groups
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$groups = isset( $args['groups'] ) && is_array( $args['groups'] ) ? $args['groups'] : array();
?>
<?php if ( ! empty( $groups ) ) : ?>
    <ul id="cimne-cluster-groups">
        <?php foreach ( $groups as $group ) : ?>
            <?php
            $title        = isset( $group['title'] ) ? trim( (string) $group['title'] ) : '';
            $url          = isset( $group['url'] ) ? trim( (string) $group['url'] ) : '';
            $leader_names = isset( $group['leader_names'] ) && is_array( $group['leader_names'] )
                ? array_values( array_filter( array_map( 'trim', $group['leader_names'] ) ) )
                : array();

            if ( empty( $leader_names ) && ! empty( $group['leader_name'] ) ) {
                $leader_names[] = trim( (string) $group['leader_name'] );
            }

            if ( '' === $title ) {
                continue;
            }
            ?>
            <li class="item-cluster-group">
                <?php if ( '' !== $url ) : ?>
                    <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $title ); ?></a>
                <?php else : ?>
                    <span class="item-cluster-group-title"><?php echo esc_html( $title ); ?></span>
                <?php endif; ?>

                <?php if ( ! empty( $leader_names ) ) : ?>
                    <br>
                    <?php foreach ( $leader_names as $index => $leader_name ) : ?>
                        <span class="item-cluster-group-subtitle"><?php echo esc_html( $leader_name ); ?></span><?php if ( $index < count( $leader_names ) - 1 ) : ?><br><?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
