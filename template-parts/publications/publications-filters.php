<?php
/**
 * Global Publications filters.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$clusters = isset( $args['clusters'] ) && is_array( $args['clusters'] ) ? $args['clusters'] : array();
?>
<form id="publications-form" class="cimne-publications-filters" data-cimne-publications-filters>
    <div class="form-group">
        <input type="search" id="publication-search" class="form-control" placeholder="<?php echo esc_attr__( 'Search', 'cimne' ); ?>" autocomplete="off" data-publication-search>
    </div>

    <div class="form-group">
        <select id="publication-cluster" class="form-control" data-publication-cluster>
            <option value=""><?php echo esc_html__( 'Cluster', 'cimne' ); ?></option>
            <?php foreach ( $clusters as $cluster ) : ?>
                <option value="<?php echo esc_attr( $cluster['code'] ); ?>"><?php echo esc_html( $cluster['name'] ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <select id="publication-type" class="form-control" data-publication-type>
            <option value="" class="dropdown-header"><?php echo esc_html__( 'Publication type', 'cimne' ); ?></option>
            <option value="ar"><?php echo esc_html__( 'Article', 'cimne' ); ?></option>
            <option value="ed"><?php echo esc_html__( 'Editorial', 'cimne' ); ?></option>
            <option value="re"><?php echo esc_html__( 'Review', 'cimne' ); ?></option>
        </select>
    </div>

    <div class="form-group form-flex-fields">
        <div class="from"><input type="text" id="start-date" placeholder="<?php echo esc_attr__( 'From', 'cimne' ); ?>" data-publication-from></div>
        <div class="to"><input type="text" id="end-date" placeholder="<?php echo esc_attr__( 'To', 'cimne' ); ?>" data-publication-to></div>
    </div>

    <div class="form-group form-buttons clickable-cta">
        <div class="flecha-container flecha-container-position-center" role="button" tabindex="0" data-publications-apply>
            <span class="cuerpo-flecha filter-data"></span><br>
            <span class="punta-flecha filter-data"></span><br>
            <span class="text-flecha filter-data"><?php echo esc_html__( 'Apply', 'cimne' ); ?></span>
        </div>
        <div class="refresh" role="button" tabindex="0" data-publications-refresh>
            <span class="refresh-icon refresh-filter"></span>
            <span class="refresh-button refresh-filter">&nbsp;<?php echo esc_html__( 'Refresh', 'cimne' ); ?></span>
        </div>
    </div>

    <div class="screen-reader-text" aria-live="polite" data-publications-filter-status></div>
</form>
