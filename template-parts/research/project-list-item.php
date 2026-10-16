<?php
/**
 * One project row in Ongoing/Finished lists.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$project = isset( $args['project'] ) && is_array( $args['project'] ) ? $args['project'] : array();

$code        = isset( $project['code'] ) ? trim( (string) $project['code'] ) : '';
$acronym     = isset( $project['acronym'] ) ? trim( (string) $project['acronym'] ) : '';
$title       = isset( $project['title'] ) ? trim( (string) $project['title'] ) : '';
$start_date  = isset( $project['start_date'] ) ? (string) $project['start_date'] : '';
$end_date    = isset( $project['end_date'] ) ? (string) $project['end_date'] : '';
$funders     = isset( $project['funding_bodies'] ) && is_array( $project['funding_bodies'] ) ? $project['funding_bodies'] : array();
$program     = isset( $project['program'] ) ? trim( (string) $project['program'] ) : '';
$website_url = isset( $project['website_url'] ) ? trim( (string) $project['website_url'] ) : '';
$pi_ids      = isset( $project['principal_investigator_ids'] ) && is_array( $project['principal_investigator_ids'] ) ? $project['principal_investigator_ids'] : array();
$search_text = trim( $acronym . ' ' . $title );
$pi_ids_attr = implode( ',', array_map( 'strval', $pi_ids ) );

if ( '' === $code ) {
    return;
}
?>
<article
    class="cimne-project-list__item"
    data-project-search="<?php echo esc_attr( $search_text ); ?>"
    data-project-pi-ids="<?php echo esc_attr( $pi_ids_attr ); ?>"
>
    <div class="cimne-project-list__body">
        <button
            type="button"
            class="cimne-project-list__open"
            data-open-project="<?php echo esc_attr( $code ); ?>"
            aria-label="<?php echo esc_attr( sprintf( __( 'Open project %s', 'cimne' ), '' !== $acronym ? $acronym : $title ) ); ?>"
        >
            <?php if ( '' !== $acronym ) : ?>
                <span class="cimne-project-list__acronym"><?php echo esc_html( $acronym ); ?></span>
            <?php endif; ?>

            <?php if ( '' !== $title ) : ?>
                <span class="cimne-project-list__title"><?php echo esc_html( $title ); ?></span>
            <?php endif; ?>
        </button>

        <dl class="cimne-project-list__meta">
            <?php if ( '' !== $start_date ) : ?>
                <div><dt><?php echo esc_html__( 'Start', 'cimne' ); ?>:</dt><dd><time datetime="<?php echo esc_attr( $start_date ); ?>"><?php echo esc_html( cimne_research_format_project_date( $start_date ) ); ?></time></dd></div>
            <?php endif; ?>

            <?php if ( '' !== $end_date ) : ?>
                <div><dt><?php echo esc_html__( 'End', 'cimne' ); ?>:</dt><dd><time datetime="<?php echo esc_attr( $end_date ); ?>"><?php echo esc_html( cimne_research_format_project_date( $end_date ) ); ?></time></dd></div>
            <?php endif; ?>

            <?php if ( ! empty( $funders ) ) : ?>
                <div><dt><?php echo esc_html__( 'Funding body(ies)', 'cimne' ); ?>:</dt><dd><?php echo esc_html( implode( ', ', $funders ) ); ?></dd></div>
            <?php endif; ?>

            <?php if ( '' !== $program ) : ?>
                <div><dt><?php echo esc_html__( 'Program', 'cimne' ); ?>:</dt><dd><?php echo esc_html( $program ); ?></dd></div>
            <?php endif; ?>
        </dl>
    </div>

    <?php if ( '' !== $website_url ) : ?>
        <a class="cimne-project-list__website" href="<?php echo esc_url( $website_url ); ?>" target="_blank" rel="noopener">
            <span aria-hidden="true">—</span> <?php echo esc_html__( 'WEBSITE', 'cimne' ); ?>
        </a>
    <?php endif; ?>
</article>
