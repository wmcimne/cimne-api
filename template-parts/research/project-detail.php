<?php
/**
 * Project detail shown inside the same Projects block.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$project          = isset( $args['project'] ) && is_array( $args['project'] ) ? $args['project'] : array();
$standalone       = ! empty( $args['standalone'] );
$back_url         = isset( $args['back_url'] ) ? trim( (string) $args['back_url'] ) : '';
$project_card_url = isset( $args['project_card_url'] ) ? trim( (string) $args['project_card_url'] ) : '';

$code        = isset( $project['code'] ) ? trim( (string) $project['code'] ) : '';
$acronym     = isset( $project['acronym'] ) ? trim( (string) $project['acronym'] ) : '';
$title       = isset( $project['title'] ) ? trim( (string) $project['title'] ) : '';
$abstract    = isset( $project['abstract'] ) ? trim( (string) $project['abstract'] ) : '';
$website_url = isset( $project['website_url'] ) ? trim( (string) $project['website_url'] ) : '';
$pi          = isset( $project['principal_investigator'] ) && is_array( $project['principal_investigator'] ) ? $project['principal_investigator'] : null;
$funders     = isset( $project['funding_bodies'] ) && is_array( $project['funding_bodies'] ) ? $project['funding_bodies'] : array();
$coordinators= isset( $project['coordinators'] ) && is_array( $project['coordinators'] ) ? $project['coordinators'] : array();

if ( $pi ) {
    if ( ! empty( $project['principal_email'] ) ) {
        $pi['email'] = $project['principal_email'];
    }

    if ( ! empty( $project['principal_photo_url'] ) ) {
        $pi['photo_url'] = $project['principal_photo_url'];
    }
}

$more_information = array(
    __( 'Reference', 'cimne' )         => isset( $project['reference'] ) ? $project['reference'] : '',
    __( 'Start date', 'cimne' )        => ! empty( $project['start_date'] ) ? cimne_research_format_project_date( $project['start_date'] ) : '',
    __( 'End date', 'cimne' )          => ! empty( $project['end_date'] ) ? cimne_research_format_project_date( $project['end_date'] ) : '',
    __( 'Coordinator', 'cimne' )       => ! empty( $coordinators ) ? implode( ', ', $coordinators ) : '',
    __( 'Program', 'cimne' )           => isset( $project['program'] ) ? $project['program'] : '',
    __( 'Call', 'cimne' )              => isset( $project['call'] ) ? $project['call'] : '',
    __( 'Subprogram', 'cimne' )        => isset( $project['subprogram'] ) ? $project['subprogram'] : '',
    __( 'Category', 'cimne' )          => isset( $project['category'] ) ? $project['category'] : '',
    __( 'Funding body(ies)', 'cimne' ) => ! empty( $funders ) ? implode( ', ', $funders ) : '',
    __( 'Grant', 'cimne' )             => isset( $project['grant'] ) ? $project['grant'] : '',
);

$more_information = array_filter(
    $more_information,
    static function ( $value ) {
        return is_scalar( $value ) && '' !== trim( (string) $value );
    }
);
?>
<article class="cimne-project-detail" data-project-code="<?php echo esc_attr( $code ); ?>">
    <div class="cimne-project-detail__toolbar">
        <?php if ( $standalone ) : ?>
            <button type="button" class="cimne-project-detail__copy-url" data-copy-project-card-url="<?php echo esc_url( $project_card_url ); ?>">
                <?php echo esc_html__( 'Copy project URL', 'cimne' ); ?> <span aria-hidden="true">⌁</span>
            </button>

            <button type="button" class="cimne-project-detail__back" data-project-card-back-url="<?php echo esc_url( $back_url ); ?>">
                <?php echo esc_html__( 'Back', 'cimne' ); ?> <span aria-hidden="true">↩</span>
            </button>
        <?php else : ?>
            <button type="button" class="cimne-project-detail__copy-url" data-copy-project-url="<?php echo esc_attr( $code ); ?>">
                <?php echo esc_html__( 'Copy project URL', 'cimne' ); ?> <span aria-hidden="true">⌁</span>
            </button>

            <button type="button" class="cimne-project-detail__back" data-project-back>
                <?php echo esc_html__( 'Back', 'cimne' ); ?> <span aria-hidden="true">↩</span>
            </button>
        <?php endif; ?>
    </div>

    <header class="cimne-project-detail__header">
        <?php if ( '' !== $acronym ) : ?>
            <h3 class="cimne-project-detail__acronym"><?php echo esc_html( $acronym ); ?></h3>
        <?php endif; ?>

        <?php if ( '' !== $title ) : ?>
            <p class="cimne-project-detail__title"><?php echo esc_html( $title ); ?></p>
        <?php endif; ?>
    </header>

    <?php if ( ! empty( $project['funding_logo_url'] ) || ! empty( $project['funding_notice'] ) || ! empty( $funders ) ) : ?>
        <section class="cimne-project-detail__funding">
            <?php if ( ! empty( $project['funding_logo_url'] ) ) : ?>
                <img class="cimne-project-detail__funding-logo" src="<?php echo esc_url( $project['funding_logo_url'] ); ?>" alt="">
            <?php endif; ?>

            <?php if ( ! empty( $funders ) ) : ?>
                <p class="cimne-project-detail__funding-name"><?php echo esc_html( implode( ', ', $funders ) ); ?></p>
            <?php endif; ?>

            <?php if ( ! empty( $project['funding_notice'] ) ) : ?>
                <div class="cimne-project-detail__funding-notice"><?php echo wpautop( esc_html( $project['funding_notice'] ) ); ?></div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <section class="cimne-project-detail__main">
        <div class="cimne-project-detail__summary">
            <?php if ( $pi && ! empty( $pi['name'] ) ) : ?>
                <?php $has_photo = ! empty( $pi['photo_url'] ); ?>
                <div class="cimne-project-detail__pi <?php echo $has_photo ? 'cimne-project-detail__pi--has-photo' : 'cimne-project-detail__pi--no-photo'; ?>">
                    <?php if ( $has_photo ) : ?>
                        <div class="cimne-project-detail__pi-photo-wrap">
                            <img class="cimne-project-detail__pi-photo" src="<?php echo esc_url( $pi['photo_url'] ); ?>" alt="<?php echo esc_attr( $pi['name'] ); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="cimne-project-detail__pi-text">
                        <span class="cimne-project-detail__pi-label"><?php echo esc_html__( 'Principal investigator', 'cimne' ); ?></span>
                        <span class="cimne-project-detail__pi-name"><?php echo esc_html( $pi['name'] ); ?></span>

                        <?php if ( ! empty( $pi['email'] ) ) : ?>
                            <a class="cimne-project-detail__pi-email" href="mailto:<?php echo esc_attr( antispambot( $pi['email'] ) ); ?>"><?php echo esc_html( antispambot( $pi['email'] ) ); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( '' !== $abstract ) : ?>
                <div class="cimne-project-detail__abstract"><?php echo wpautop( esc_html( $abstract ) ); ?></div>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $more_information ) ) : ?>
            <details class="cimne-project-detail__more">
                <summary><?php echo esc_html__( 'More information', 'cimne' ); ?></summary>
                <dl class="cimne-project-detail__more-list">
                    <?php foreach ( $more_information as $label => $value ) : ?>
                        <div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $value ); ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </details>
        <?php endif; ?>
    </section>

    <?php if ( '' !== $website_url ) : ?>
        <footer class="cimne-project-detail__footer">
            <a href="<?php echo esc_url( $website_url ); ?>" target="_blank" rel="noopener">
                <span aria-hidden="true">—</span> <?php echo esc_html__( 'WEBSITE', 'cimne' ); ?>
            </a>
        </footer>
    <?php endif; ?>

    <span class="screen-reader-text" aria-live="polite" data-cimne-project-copy-status></span>
</article>
