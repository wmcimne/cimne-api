<?php
/**
 * Filter form for the Fellowships tab on Awards.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$options       = isset( $args['options'] ) && is_array( $args['options'] ) ? $args['options'] : array();
$nature        = isset( $options['nature'] ) && is_array( $options['nature'] ) ? $options['nature'] : array();
$units         = isset( $options['units'] ) && is_array( $options['units'] ) ? $options['units'] : array();
$beneficiaries = isset( $options['beneficiaries'] ) && is_array( $options['beneficiaries'] ) ? $options['beneficiaries'] : array();
?>
<form class="cimne-fellowships-filters" data-cimne-fellowship-filters>
    <div class="form-group cimne-fellowships-filters__field cimne-fellowships-filters__field--search">
        <label class="cimne-fellowships-filters__search-label" for="cimne-fellowship-filter-search">
            <?php echo esc_html__( 'Search', 'cimne' ); ?>
        </label>
        <input
            id="cimne-fellowship-filter-search"
            type="search"
            name="search"
            placeholder="<?php echo esc_attr__( 'Type something', 'cimne' ); ?>"
            autocomplete="off"
            data-fellowship-filter-search
        >
    </div>

    <div class="form-group cimne-fellowships-filters__field">
        <label class="screen-reader-text" for="cimne-fellowship-filter-category"><?php echo esc_html__( 'Category', 'cimne' ); ?></label>
        <select id="cimne-fellowship-filter-category" name="category" aria-label="<?php echo esc_attr__( 'Category', 'cimne' ); ?>" data-fellowship-filter-category>
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

    <div class="form-group cimne-fellowships-filters__field">
        <label class="screen-reader-text" for="cimne-fellowship-filter-unit"><?php echo esc_html__( 'Research cluster / Innovation Unit', 'cimne' ); ?></label>
        <select id="cimne-fellowship-filter-unit" name="unit" aria-label="<?php echo esc_attr__( 'Research cluster / Innovation Unit', 'cimne' ); ?>" data-fellowship-filter-unit>
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

    <div class="form-group cimne-fellowships-filters__field">
        <label class="screen-reader-text" for="cimne-fellowship-filter-beneficiary"><?php echo esc_html__( 'Beneficiary', 'cimne' ); ?></label>
        <select id="cimne-fellowship-filter-beneficiary" name="beneficiary" aria-label="<?php echo esc_attr__( 'Beneficiary', 'cimne' ); ?>" data-fellowship-filter-beneficiary>
            <option value=""><?php echo esc_html__( 'Beneficiary', 'cimne' ); ?></option>
            <?php foreach ( $beneficiaries as $option ) : ?>
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

    <div class="form-group form-buttons clickable-cta cimne-fellowships-filters__actions">
        <div class="flecha-container flecha-container-position-center" role="button" tabindex="0" aria-label="<?php echo esc_attr__( 'Apply filters', 'cimne' ); ?>" data-fellowship-filters-apply>
            <span class="cuerpo-flecha filter-data"></span><br>
            <span class="punta-flecha filter-data"></span><br>
            <span class="text-flecha filter-data"><?php echo esc_html__( 'Apply', 'cimne' ); ?></span>
        </div>

        <div class="refresh" role="button" tabindex="0" aria-label="<?php echo esc_attr__( 'Refresh filters', 'cimne' ); ?>" data-fellowship-filters-refresh>
            <span class="refresh-icon refresh-filter"></span>
            <span class="refresh-button refresh-filter">&nbsp;<?php echo esc_html__( 'Refresh', 'cimne' ); ?></span>
        </div>
    </div>

    <p class="cimne-fellowships-filters__status screen-reader-text" aria-live="polite" data-fellowship-filter-status></p>
</form>
