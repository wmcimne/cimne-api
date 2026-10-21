<?php
/**
 * PhD Theses filters.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<form class="cimne-phd-theses-filters" data-cimne-phd-theses-filters>
    <div class="form-group">
        <input
            type="search"
            id="phd-theses-search"
            class="form-control"
            placeholder="<?php echo esc_attr__( 'Search', 'cimne' ); ?>"
            autocomplete="off"
            data-phd-theses-search
        >
    </div>

    <div class="form-group form-flex-fields">
        <div class="from">
            <input
                type="text"
                id="phd-theses-date-from"
                placeholder="<?php echo esc_attr__( 'From', 'cimne' ); ?>"
                autocomplete="off"
                data-phd-theses-from
            >
        </div>
        <div class="to">
            <input
                type="text"
                id="phd-theses-date-to"
                placeholder="<?php echo esc_attr__( 'To', 'cimne' ); ?>"
                autocomplete="off"
                data-phd-theses-to
            >
        </div>
    </div>

    <div class="form-group form-buttons clickable-cta">
        <div
            class="flecha-container flecha-container-position-center"
            role="button"
            tabindex="0"
            data-phd-theses-apply
        >
            <span class="cuerpo-flecha filter-data"></span><br>
            <span class="punta-flecha filter-data"></span><br>
            <span class="text-flecha filter-data"><?php echo esc_html__( 'Apply', 'cimne' ); ?></span>
        </div>

        <div
            class="refresh"
            role="button"
            tabindex="0"
            data-phd-theses-refresh
        >
            <span class="refresh-icon refresh-filter"></span>
            <span class="refresh-button refresh-filter">&nbsp;<?php echo esc_html__( 'Refresh', 'cimne' ); ?></span>
        </div>
    </div>

    <div class="screen-reader-text" aria-live="polite" data-phd-theses-filter-status></div>
</form>
