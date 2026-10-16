<?php
/**
 * One fellowship row.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$fellowship = isset( $args['fellowship'] ) && is_array( $args['fellowship'] ) ? $args['fellowship'] : array();
$code        = isset( $fellowship['code'] ) ? trim( (string) $fellowship['code'] ) : '';
$acronym     = isset( $fellowship['acronym'] ) ? trim( (string) $fellowship['acronym'] ) : '';
$title       = isset( $fellowship['title'] ) ? trim( (string) $fellowship['title'] ) : '';
$start_date  = isset( $fellowship['start_date'] ) ? (string) $fellowship['start_date'] : '';
$end_date    = isset( $fellowship['end_date'] ) ? (string) $fellowship['end_date'] : '';
$funders     = isset( $fellowship['funding_bodies'] ) && is_array( $fellowship['funding_bodies'] ) ? $fellowship['funding_bodies'] : array();
$names       = isset( $fellowship['beneficiary_names'] ) && is_array( $fellowship['beneficiary_names'] ) ? $fellowship['beneficiary_names'] : array();
$ids         = isset( $fellowship['beneficiary_ids'] ) && is_array( $fellowship['beneficiary_ids'] ) ? $fellowship['beneficiary_ids'] : array();
$search_text = trim( $acronym . ' ' . $title . ' ' . implode( ' ', $names ) );
$ids_attr    = implode( ',', array_map( 'strval', $ids ) );

if ( '' === $code ) {
    return;
}
?>
<article class="cimne-fellowship-list__item" data-fellowship-search="<?php echo esc_attr( $search_text ); ?>" data-fellowship-beneficiary-ids="<?php echo esc_attr( $ids_attr ); ?>">
    <div class="cimne-fellowship-list__body">
        <button type="button" class="cimne-fellowship-list__open" data-open-fellowship="<?php echo esc_attr( $code ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Open fellowship %s', 'cimne' ), '' !== $acronym ? $acronym : $title ) ); ?>">
            <?php if ( '' !== $acronym ) : ?>
                <span class="cimne-fellowship-list__acronym"><?php echo esc_html( $acronym ); ?></span>
            <?php endif; ?>
            <?php if ( '' !== $title ) : ?>
                <span class="cimne-fellowship-list__title"><?php echo esc_html( $title ); ?></span>
            <?php endif; ?>
        </button>

        <dl class="cimne-fellowship-list__meta">
            <?php if ( '' !== $start_date ) : ?>
                <div><dt><?php echo esc_html__( 'Start', 'cimne' ); ?>:</dt><dd><time datetime="<?php echo esc_attr( $start_date ); ?>"><?php echo esc_html( cimne_fellowships_format_date( $start_date ) ); ?></time></dd></div>
            <?php endif; ?>
            <?php if ( '' !== $end_date ) : ?>
                <div><dt><?php echo esc_html__( 'End', 'cimne' ); ?>:</dt><dd><time datetime="<?php echo esc_attr( $end_date ); ?>"><?php echo esc_html( cimne_fellowships_format_date( $end_date ) ); ?></time></dd></div>
            <?php endif; ?>
            <?php if ( ! empty( $funders ) ) : ?>
                <div><dt><?php echo esc_html__( 'Funding body(ies)', 'cimne' ); ?>:</dt><dd><?php echo esc_html( implode( ', ', $funders ) ); ?></dd></div>
            <?php endif; ?>
            <?php if ( ! empty( $names ) ) : ?>
                <div><dt><?php echo esc_html( _n( 'Beneficiary', 'Beneficiaries', count( $names ), 'cimne' ) ); ?>:</dt><dd><?php echo esc_html( implode( ', ', $names ) ); ?></dd></div>
            <?php endif; ?>
        </dl>
    </div>
</article>
