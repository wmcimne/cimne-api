<?php
/**
 * One publication row, matching the existing Publications HTML/classes.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$publication = isset( $args['publication'] ) && is_array( $args['publication'] ) ? $args['publication'] : array();
$title       = isset( $publication['title'] ) ? trim( (string) $publication['title'] ) : '';
$authors     = isset( $publication['authors'] ) && is_array( $publication['authors'] ) ? $publication['authors'] : array();
$name        = isset( $publication['publication_name'] ) ? trim( (string) $publication['publication_name'] ) : '';
$volume      = isset( $publication['volume'] ) ? trim( (string) $publication['volume'] ) : '';
$issue       = isset( $publication['issue'] ) ? trim( (string) $publication['issue'] ) : '';
$page_range  = isset( $publication['page_range'] ) ? trim( (string) $publication['page_range'] ) : '';
$year        = isset( $publication['year'] ) ? trim( (string) $publication['year'] ) : '';
$doi         = isset( $publication['doi'] ) ? trim( (string) $publication['doi'] ) : '';
$doi_url     = '' !== $doi ? 'https://doi.org/' . ltrim( preg_replace( '#^https?://(?:dx\.)?doi\.org/#i', '', $doi ), '/' ) : '';
?>
<div class="list-block">
    <div class="list-item">
        <p class="publication-authors">
            <?php foreach ( $authors as $index => $author ) : ?>
                <?php
                $author_name = isset( $author['indexed_name'] ) ? trim( (string) $author['indexed_name'] ) : '';
                if ( '' === $author_name ) {
                    continue;
                }
                $is_cimne = ! empty( $author['is_cimne'] );
                $count    = count( $authors );
                ?>
                <span<?php echo $is_cimne ? ' class="cimne-affiliated"' : ''; ?>><?php echo esc_html( $author_name ); ?></span><?php
                if ( $index < $count - 2 ) {
                    echo ', ';
                } elseif ( $index === $count - 2 ) {
                    echo ' ' . esc_html__( 'and', 'cimne' ) . ' ';
                }
                ?>
            <?php endforeach; ?>
        </p>
    </div>

    <div class="list-item">
        <p class="publication-title"><?php echo esc_html( $title ); ?></p>
    </div>

    <div class="list-item">
        <p class="publication-details">
            <?php if ( '' !== $name ) : ?><span class="publication-name"><?php echo esc_html( $name ); ?>, </span><?php endif; ?>
            <?php if ( '' !== $volume ) : ?><span class="publication-volume"><?php echo esc_html( sprintf( 'vol (%s), ', $volume ) ); ?></span><?php endif; ?>
            <?php if ( '' !== $issue ) : ?><span class="publication-issue"><?php echo esc_html( sprintf( 'issue (%s), ', $issue ) ); ?></span><?php endif; ?>
            <?php if ( '' !== $page_range ) : ?><span class="publication-page-range"><?php echo esc_html( sprintf( 'pp (%s), ', $page_range ) ); ?></span><?php endif; ?>
            <?php if ( '' !== $year ) : ?><span class="publication-year"><?php echo esc_html( $year ); ?><?php echo '' !== $doi ? ',' : ''; ?></span><?php endif; ?>
            <?php if ( '' !== $doi ) : ?>
                <span class="publication-doi"> <a href="<?php echo esc_url( $doi_url ); ?>" rel="noopener noreferrer" title="<?php echo esc_attr__( 'DOI hyperlink', 'cimne' ); ?>" target="_blank"><?php echo esc_html( $doi ); ?></a></span>
            <?php endif; ?>
        </p>
    </div>
</div>
