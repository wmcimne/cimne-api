<?php
/**
 * Inline fellowship detail.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$fellowship = isset( $args['fellowship'] ) && is_array( $args['fellowship'] ) ? $args['fellowship'] : array();
$code        = isset( $fellowship['code'] ) ? trim( (string) $fellowship['code'] ) : '';
$acronym     = isset( $fellowship['acronym'] ) ? trim( (string) $fellowship['acronym'] ) : '';
$title       = isset( $fellowship['title'] ) ? trim( (string) $fellowship['title'] ) : '';
$abstract    = isset( $fellowship['abstract'] ) ? trim( (string) $fellowship['abstract'] ) : '';
$funders     = isset( $fellowship['funding_bodies'] ) && is_array( $fellowship['funding_bodies'] ) ? $fellowship['funding_bodies'] : array();
$beneficiaries = isset( $fellowship['beneficiaries'] ) && is_array( $fellowship['beneficiaries'] ) ? $fellowship['beneficiaries'] : array();

$more_information = array(
    __( 'Reference', 'cimne' )         => isset( $fellowship['reference'] ) ? $fellowship['reference'] : '',
    __( 'Start date', 'cimne' )        => ! empty( $fellowship['start_date'] ) ? cimne_fellowships_format_date( $fellowship['start_date'] ) : '',
    __( 'End date', 'cimne' )          => ! empty( $fellowship['end_date'] ) ? cimne_fellowships_format_date( $fellowship['end_date'] ) : '',
    __( 'Programme', 'cimne' )         => isset( $fellowship['programme'] ) ? $fellowship['programme'] : '',
    __( 'Type', 'cimne' )              => isset( $fellowship['type'] ) ? $fellowship['type'] : '',
    __( 'Fellowship type', 'cimne' )   => isset( $fellowship['subtype'] ) ? $fellowship['subtype'] : '',
    __( 'Category', 'cimne' )          => isset( $fellowship['category'] ) ? $fellowship['category'] : '',
    __( 'Initiative', 'cimne' )        => isset( $fellowship['initiative'] ) ? $fellowship['initiative'] : '',
    __( 'Funding mode', 'cimne' )      => isset( $fellowship['funding_mode'] ) ? $fellowship['funding_mode'] : '',
    __( 'Managing unit', 'cimne' )     => isset( $fellowship['management_unit'] ) ? $fellowship['management_unit'] : '',
    __( 'Department', 'cimne' )        => isset( $fellowship['department'] ) ? $fellowship['department'] : '',
    __( 'Location', 'cimne' )          => isset( $fellowship['centre'] ) ? $fellowship['centre'] : '',
    __( 'Funding body(ies)', 'cimne' ) => ! empty( $funders ) ? implode( ', ', $funders ) : '',
);
$more_information = array_filter(
    $more_information,
    static function ( $value ) {
        return is_scalar( $value ) && '' !== trim( (string) $value );
    }
);
?>
<article class="cimne-fellowship-detail" data-fellowship-code="<?php echo esc_attr( $code ); ?>">
    <div class="cimne-fellowship-detail__toolbar">
        <button type="button" class="cimne-fellowship-detail__back" data-fellowship-back>
            <?php echo esc_html__( 'Back', 'cimne' ); ?> <span aria-hidden="true">↩</span>
        </button>
    </div>

    <header class="cimne-fellowship-detail__header">
        <?php if ( '' !== $acronym ) : ?>
            <h3 class="cimne-fellowship-detail__acronym"><?php echo esc_html( $acronym ); ?></h3>
        <?php endif; ?>
        <?php if ( '' !== $title ) : ?>
            <p class="cimne-fellowship-detail__title"><?php echo esc_html( $title ); ?></p>
        <?php endif; ?>
    </header>

    <?php if ( ! empty( $funders ) ) : ?>
        <section class="cimne-fellowship-detail__funding">
            <p class="cimne-fellowship-detail__funding-name"><?php echo esc_html( implode( ', ', $funders ) ); ?></p>
        </section>
    <?php endif; ?>

    <section class="cimne-fellowship-detail__main">
        <div class="cimne-fellowship-detail__summary">
            <?php if ( ! empty( $beneficiaries ) ) : ?>
                <div class="cimne-fellowship-detail__beneficiaries">
                    <span class="cimne-fellowship-detail__beneficiary-label"><?php echo esc_html( _n( 'Beneficiary', 'Beneficiaries', count( $beneficiaries ), 'cimne' ) ); ?></span>
                    <?php foreach ( $beneficiaries as $beneficiary ) : ?>
                        <?php if ( ! empty( $beneficiary['name'] ) ) : ?>
                            <span class="cimne-fellowship-detail__beneficiary-name"><?php echo esc_html( $beneficiary['name'] ); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ( '' !== $abstract ) : ?>
                <div class="cimne-fellowship-detail__abstract"><?php echo wp_kses_post( wpautop( esc_html( $abstract ) ) ); ?></div>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $more_information ) ) : ?>
            <details class="cimne-fellowship-detail__more">
                <summary><?php echo esc_html__( 'More information', 'cimne' ); ?></summary>
                <dl class="cimne-fellowship-detail__more-list">
                    <?php foreach ( $more_information as $label => $value ) : ?>
                        <div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </details>
        <?php endif; ?>
    </section>
</article>
