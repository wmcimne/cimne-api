<?php
/**
 * Filter form for the global Projects page.
 *
 * The visual structure intentionally reuses the existing CIMNE Projects
 * filter markup/classes so the controls inherit their established styling
 * from the child theme styles.css.
 *
 * Expected $args:
 * - options[nature][]        => [ value, label ]
 * - options[units][]         => [ value, label ]
 * - options[investigators][] => [ value, label ]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options       = isset( $args['options'] ) && is_array( $args['options'] ) ? $args['options'] : array();
$nature        = isset( $options['nature'] ) && is_array( $options['nature'] ) ? $options['nature'] : array();
$units         = isset( $options['units'] ) && is_array( $options['units'] ) ? $options['units'] : array();
$investigators = isset( $options['investigators'] ) && is_array( $options['investigators'] ) ? $options['investigators'] : array();
?>
<form class="cimne-projects-filters" data-cimne-project-filters>
    <div class="form-group cimne-projects-filters__field cimne-projects-filters__field--search">

        <input
            id="cimne-project-filter-search"
            class="form-control"
            type="search"
            name="search"
            placeholder="<?php echo esc_attr__( 'Type something', 'cimne' ); ?>"
            autocomplete="off"
            data-project-filter-search
        >
    </div>

    <div class="form-group cimne-projects-filters__field">
        <label class="screen-reader-text" for="cimne-project-filter-category">
            <?php echo esc_html__( 'Category', 'cimne' ); ?>
        </label>
        <select
            id="cimne-project-filter-category"
            class="form-control"
            name="category"
            aria-label="<?php echo esc_attr__( 'Category', 'cimne' ); ?>"
            data-project-filter-category
        >
            <option value=""><?php echo esc_html__( 'Category', 'cimne' ); ?></option>
            <?php foreach ( $nature as $option ) : ?>
                <?php
                $value = isset( $option['value'] ) ? (string) $option['value'] : '';
                $label = isset( $option['label'] ) ? (string) $option['label'] : '';
                if ( '' === $value || '' === $label ) {
                    continue;
                }
                ?>
                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group cimne-projects-filters__field">
        <label class="screen-reader-text" for="cimne-project-filter-unit">
            <?php echo esc_html__( 'Research cluster / Innovation Unit', 'cimne' ); ?>
        </label>
        <select
            id="cimne-project-filter-unit"
            class="form-control"
            name="unit"
            aria-label="<?php echo esc_attr__( 'Research cluster / Innovation Unit', 'cimne' ); ?>"
            data-project-filter-unit
        >
            <option value=""><?php echo esc_html__( 'Research cluster / Innovation Unit', 'cimne' ); ?></option>
            <?php foreach ( $units as $option ) : ?>
                <?php
                $value = isset( $option['value'] ) ? (string) $option['value'] : '';
                $label = isset( $option['label'] ) ? (string) $option['label'] : '';
                if ( '' === $value || '' === $label ) {
                    continue;
                }
                ?>
                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group cimne-projects-filters__field">
        <label class="screen-reader-text" for="cimne-project-filter-pi">
            <?php echo esc_html__( 'Principal investigator', 'cimne' ); ?>
        </label>
        <select
            id="cimne-project-filter-pi"
            class="form-control"
            name="principal_investigator"
            aria-label="<?php echo esc_attr__( 'Principal investigator', 'cimne' ); ?>"
            data-project-filter-pi
        >
            <option value=""><?php echo esc_html__( 'Principal investigator', 'cimne' ); ?></option>
            <?php foreach ( $investigators as $option ) : ?>
                <?php
                $value = isset( $option['value'] ) ? (string) $option['value'] : '';
                $label = isset( $option['label'] ) ? (string) $option['label'] : '';
                if ( '' === $value || '' === $label ) {
                    continue;
                }
                ?>
                <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group form-buttons clickable-cta cimne-projects-filters__actions">
        <div
            class="flecha-container flecha-container-position-center"
            role="button"
            tabindex="0"
            aria-label="<?php echo esc_attr__( 'Apply filters', 'cimne' ); ?>"
            data-project-filters-apply
        >
            <span class="cuerpo-flecha filter-data"></span><br>
            <span class="punta-flecha filter-data"></span><br>
            <span class="text-flecha filter-data"><?php echo esc_html__( 'Apply', 'cimne' ); ?></span>
        </div>

        <div
            class="refresh"
            role="button"
            tabindex="0"
            aria-label="<?php echo esc_attr__( 'Refresh filters', 'cimne' ); ?>"
            data-project-filters-refresh
        >
            <span class="refresh-icon refresh-filter"></span>
            <span class="refresh-button refresh-filter">&nbsp;<?php echo esc_html__( 'Refresh', 'cimne' ); ?></span>
        </div>
    </div>

    <p class="cimne-projects-filters__status screen-reader-text" aria-live="polite" data-project-filter-status></p>
</form>
