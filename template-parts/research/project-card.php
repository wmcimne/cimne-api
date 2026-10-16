<?php
/**
 * Single project card used by the Research Projects component.
 *
 * This is deliberately a data-validation template, not the final visual
 * design. It exposes a useful subset of the fields currently documented by
 * the Projects API while keeping all remaining normalized data available for
 * later iterations.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$project = isset( $args['project'] ) && is_array( $args['project'] ) ? $args['project'] : array();

if ( empty( $project ) ) {
    return;
}

$code        = isset( $project['code'] ) ? (string) $project['code'] : '';
$title       = isset( $project['display_title'] ) ? (string) $project['display_title'] : '';
$short_title = isset( $project['short_title'] ) ? (string) $project['short_title'] : '';
$start_date  = isset( $project['start_date'] ) ? (string) $project['start_date'] : '';
$end_date    = isset( $project['end_date'] ) ? (string) $project['end_date'] : '';

$meta = array(
    'status' => array(
        'label' => __( 'Status', 'cimne' ),
        'value' => isset( $project['status_description'] ) ? $project['status_description'] : '',
    ),
    'type' => array(
        'label' => __( 'Type', 'cimne' ),
        'value' => isset( $project['type_description'] ) ? $project['type_description'] : '',
    ),
    'subtype' => array(
        'label' => __( 'Subtype', 'cimne' ),
        'value' => isset( $project['subtype_description'] ) ? $project['subtype_description'] : '',
    ),
    'funding' => array(
        'label' => __( 'Funding', 'cimne' ),
        'value' => isset( $project['funding_description'] ) ? $project['funding_description'] : '',
    ),
    'organisation' => array(
        'label' => __( 'Organisation', 'cimne' ),
        'value' => isset( $project['organisation_name'] ) ? $project['organisation_name'] : '',
    ),
    'department' => array(
        'label' => __( 'Department', 'cimne' ),
        'value' => isset( $project['department_description'] ) && '' !== $project['department_description']
            ? $project['department_description']
            : ( isset( $project['department_code'] ) ? $project['department_code'] : '' ),
    ),
);
?>
<article
    class="cimne-research-project"
    data-project-code="<?php echo esc_attr( $code ); ?>"
>
    <header class="cimne-research-project__header">
        <?php if ( '' !== $code ) : ?>
            <p class="cimne-research-project__code">
                <?php echo esc_html( $code ); ?>
            </p>
        <?php endif; ?>

        <?php if ( '' !== $title ) : ?>
            <h3 class="cimne-research-project__title">
                <?php echo esc_html( $title ); ?>
            </h3>
        <?php endif; ?>

        <?php if ( '' !== $short_title && 0 !== strcasecmp( $short_title, $title ) ) : ?>
            <p class="cimne-research-project__short-title">
                <?php echo esc_html( $short_title ); ?>
            </p>
        <?php endif; ?>
    </header>

    <?php if ( '' !== $start_date || '' !== $end_date ) : ?>
        <p class="cimne-research-project__dates">
            <?php if ( '' !== $start_date ) : ?>
                <span class="cimne-research-project__date cimne-research-project__date--start">
                    <span class="cimne-research-project__label"><?php echo esc_html__( 'Start', 'cimne' ); ?>:</span>
                    <time datetime="<?php echo esc_attr( $start_date ); ?>">
                        <?php echo esc_html( cimne_research_format_project_date( $start_date ) ); ?>
                    </time>
                </span>
            <?php endif; ?>

            <?php if ( '' !== $end_date ) : ?>
                <span class="cimne-research-project__date cimne-research-project__date--end">
                    <span class="cimne-research-project__label"><?php echo esc_html__( 'End', 'cimne' ); ?>:</span>
                    <time datetime="<?php echo esc_attr( $end_date ); ?>">
                        <?php echo esc_html( cimne_research_format_project_date( $end_date ) ); ?>
                    </time>
                </span>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <dl class="cimne-research-project__meta">
        <?php foreach ( $meta as $item ) : ?>
            <?php
            $value = isset( $item['value'] ) && is_scalar( $item['value'] )
                ? trim( (string) $item['value'] )
                : '';

            if ( '' === $value ) {
                continue;
            }
            ?>
            <div class="cimne-research-project__meta-item">
                <dt><?php echo esc_html( $item['label'] ); ?></dt>
                <dd><?php echo esc_html( $value ); ?></dd>
            </div>
        <?php endforeach; ?>
    </dl>
</article>
