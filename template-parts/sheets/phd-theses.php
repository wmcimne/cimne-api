<?php
/**
 * PhD Theses list.
 *
 * Expected args:
 * - theses: normalized thesis records.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$theses = isset( $args['theses'] ) && is_array( $args['theses'] )
    ? $args['theses']
    : array();

if ( empty( $theses ) ) :
    ?>
    <p class="no-results"><?php echo esc_html__( 'No results found.', 'cimne' ); ?></p>
    <?php
    return;
endif;

foreach ( $theses as $thesis ) :
    $title            = isset( $thesis['title'] ) ? (string) $thesis['title'] : '';
    $reading_date     = isset( $thesis['reading_date'] ) ? (string) $thesis['reading_date'] : '';
    $reading_date_iso = isset( $thesis['reading_date_iso'] ) ? (string) $thesis['reading_date_iso'] : '';
    $author           = isset( $thesis['author'] ) ? (string) $thesis['author'] : '';
    $directors        = isset( $thesis['directors'] ) ? (string) $thesis['directors'] : '';
    $programme        = isset( $thesis['programme'] ) ? (string) $thesis['programme'] : '';
    $qualification    = isset( $thesis['qualification'] ) ? (string) $thesis['qualification'] : '';
    $url              = isset( $thesis['url'] ) ? (string) $thesis['url'] : '';
    ?>
    <div class="theses-list"<?php echo '' !== $reading_date_iso ? ' data-reading-date="' . esc_attr( $reading_date_iso ) . '"' : ''; ?>>
        <dl>
            <div class="list-item">
                <dt><?php echo esc_html__( 'Reading', 'cimne' ); ?>:</dt>
                <dd><?php echo esc_html( $reading_date ); ?></dd>
            </div>
            <div class="list-item">
                <dt><?php echo esc_html__( 'Author', 'cimne' ); ?>:</dt>
                <dd><?php echo esc_html( $author ); ?></dd>
            </div>
            <div class="list-item">
                <dt><?php echo esc_html__( 'Title', 'cimne' ); ?>:</dt>
                <dd>
                    <?php if ( '' !== $url ) : ?>
                        <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $title ); ?></a>
                    <?php else : ?>
                        <?php echo esc_html( $title ); ?>
                    <?php endif; ?>
                </dd>
            </div>
            <div class="list-item">
                <dt><?php echo esc_html__( 'Directors', 'cimne' ); ?>:</dt>
                <dd><?php echo esc_html( $directors ); ?></dd>
            </div>
            <div class="list-item">
                <dt><?php echo esc_html__( 'Programme', 'cimne' ); ?>:</dt>
                <dd><?php echo esc_html( $programme ); ?></dd>
            </div>
            <div class="list-item">
                <dt><?php echo esc_html__( 'Qualification', 'cimne' ); ?>:</dt>
                <dd><?php echo esc_html( $qualification ); ?></dd>
            </div>
        </dl>
    </div>
    <?php
endforeach;
