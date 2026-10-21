<?php
/**
 * Innovation Unit contracts/projects list.
 *
 * Expected args:
 * - contracts: normalized contract records.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$contracts = isset( $args['contracts'] ) && is_array( $args['contracts'] )
    ? $args['contracts']
    : array();

if ( empty( $contracts ) ) :
    ?>
    <p class="no-results"><?php echo esc_html__( 'No results found.', 'cimne' ); ?></p>
    <?php
    return;
endif;

foreach ( $contracts as $contract ) :
    $name           = isset( $contract['name'] ) ? (string) $contract['name'] : '';
    $description    = isset( $contract['description'] ) ? (string) $contract['description'] : '';
    $client         = isset( $contract['client'] ) ? (string) $contract['client'] : '';
    $period         = isset( $contract['period'] ) ? (string) $contract['period'] : '';
    $partners       = isset( $contract['partners'] ) ? (string) $contract['partners'] : '';
    $abstract_lines = isset( $contract['abstract_lines'] ) && is_array( $contract['abstract_lines'] )
        ? $contract['abstract_lines']
        : array();
    $website        = isset( $contract['website'] ) ? (string) $contract['website'] : '';
    $image          = isset( $contract['image'] ) ? (string) $contract['image'] : '';
    $files          = isset( $contract['files'] ) && is_array( $contract['files'] )
        ? $contract['files']
        : array();
    ?>
    <details class="iu-details" name="acordeon">
        <summary>
            <span class="summary-title"><?php echo esc_html( $name ); ?></span><br>
            <span class="summary-subtitle"><?php echo esc_html( $description ); ?></span>
        </summary>
        <dl>
            <div class="contract-list-container">
                <div class="left">
                    <?php if ( '' !== $image ) : ?>
                        <div class="list-item">
                            <div class="list-img">
                                <img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $name ); ?>" loading="lazy">
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( '' !== $website ) : ?>
                        <div class="list-item list-website">
                            <div class="flecha-container flecha-container-position-center">
                                <span class="cuerpo-flecha"> </span>
                                <span class="punta-flecha"> </span>
                                <span class="text-flecha"><a href="<?php echo esc_url( $website ); ?>"><?php echo esc_html__( 'Website', 'cimne' ); ?></a></span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="right">
                    <div class="list-item">
                        <dt><?php echo esc_html__( 'Client', 'cimne' ); ?>:</dt>
                        <dd><?php echo esc_html( $client ); ?></dd>
                    </div>

                    <div class="list-item">
                        <dt><?php echo esc_html__( 'Dates', 'cimne' ); ?>:</dt>
                        <dd><?php echo esc_html( $period ); ?></dd>
                    </div>

                    <?php if ( '' !== $partners ) : ?>
                        <div class="list-item">
                            <dt><?php echo esc_html__( 'Participants', 'cimne' ); ?>:</dt>
                            <dd><?php echo esc_html( $partners ); ?></dd>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $abstract_lines ) ) : ?>
                        <div class="list-item abstract-item">
                            <h5><?php echo esc_html__( 'Abstract', 'cimne' ); ?>:</h5>
                            <?php foreach ( $abstract_lines as $abstract_line ) : ?>
                                <p><?php echo wp_kses_post( $abstract_line ); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $files ) ) : ?>
                        <div class="list-item">
                            <dt><?php echo esc_html__( 'Files', 'cimne' ); ?>:</dt>
                            <dd>
                                <?php foreach ( $files as $file ) :
                                    $file_url   = isset( $file['url'] ) ? (string) $file['url'] : '';
                                    $file_label = isset( $file['label'] ) ? (string) $file['label'] : '';

                                    if ( '' === $file_url ) {
                                        continue;
                                    }
                                    ?>
                                    <a href="<?php echo esc_url( $file_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $file_label ); ?></a><br>
                                <?php endforeach; ?>
                            </dd>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </dl>
    </details>
    <?php
endforeach;
