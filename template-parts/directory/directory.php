<?php
/**
 * Directory list template.
 *
 * Expected $args:
 * - people: reduced directory dataset.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$people = isset( $args['people'] ) && is_array( $args['people'] )
    ? $args['people']
    : array();
?>

<div class="cimne-directory" data-cimne-directory>
    <?php if ( empty( $people ) ) : ?>
        <p class="cimne-directory__empty">
            <?php echo esc_html__( 'No people found.', 'cimne' ); ?>
        </p>
    <?php else : ?>
        <div class="cimne-directory-list" data-cimne-directory-list>
            <?php
            foreach ( $people as $person ) {
                get_template_part(
                    'template-parts/directory/person-card',
                    null,
                    array( 'person' => $person )
                );
            }
            ?>
        </div>

        <p class="cimne-directory__empty" data-directory-no-results hidden>
            <?php echo esc_html__( 'No people found.', 'cimne' ); ?>
        </p>

        <p class="screen-reader-text" data-directory-status aria-live="polite" aria-atomic="true"></p>
    <?php endif; ?>
</div>
