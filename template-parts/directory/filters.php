<?php
/**
 * Directory filters template (Step 7B.4).
 *
 * Visual field styling mirrors the established /research/projects/ filters.
 *
 * Expected $args:
 * - role_tree: Cluster > Group structure generated from research_units.
 * - locations: unique centro_cod/centro_desc options derived from /directorio.
 * - psr_departments: unique departamento_cod/departamento_desc options for PSR staff.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$role_tree = isset( $args['role_tree'] ) && is_array( $args['role_tree'] )
    ? $args['role_tree']
    : array();

$locations = isset( $args['locations'] ) && is_array( $args['locations'] )
    ? $args['locations']
    : array();

$psr_departments = isset( $args['psr_departments'] ) && is_array( $args['psr_departments'] )
    ? $args['psr_departments']
    : array();
?>

<form class="cimne-directory-filters" data-cimne-directory-filters autocomplete="off">
    <div class="form-group cimne-directory-filters__field cimne-directory-filters__field--search">
        <!--<label class="cimne-directory-filters__search-label" for="cimne-directory-search">
            <?php echo esc_html__( 'Search', 'cimne' ); ?>
        </label>-->
        <input
            id="cimne-directory-search"
            type="search"
            value=""
            placeholder="<?php echo esc_attr__( 'Type something', 'cimne' ); ?>"
            data-directory-search
        >
    </div>

    <div class="form-group cimne-directory-filters__field cimne-directory-filters__field--location">
        <label class="screen-reader-text" for="cimne-directory-location">
            <?php echo esc_html__( 'Location', 'cimne' ); ?>
        </label>
        <select
            id="cimne-directory-location"
            aria-label="<?php echo esc_attr__( 'Location', 'cimne' ); ?>"
            data-directory-location
        >
            <option
                class="cimne-directory-location-option cimne-directory-location-option--all"
                value="all"
            ><?php echo esc_html__( 'All locations', 'cimne' ); ?></option>

            <?php foreach ( $locations as $location ) : ?>
                <?php
                $location_code = isset( $location['code'] ) ? trim( (string) $location['code'] ) : '';
                $location_label = isset( $location['label'] ) ? trim( (string) $location['label'] ) : '';

                if ( '' === $location_code || '' === $location_label ) {
                    continue;
                }
                ?>
                <option
                    class="cimne-directory-location-option cimne-directory-location-option--code-<?php echo esc_attr( sanitize_html_class( $location_code ) ); ?>"
                    value="<?php echo esc_attr( $location_code ); ?>"
                    data-centre-code="<?php echo esc_attr( $location_code ); ?>"
                ><?php echo esc_html( $location_label ); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group cimne-directory-filters__field cimne-directory-filters__field--role">
        <label class="screen-reader-text" for="cimne-directory-role">
            <?php echo esc_html__( 'Role', 'cimne' ); ?>
        </label>
        <select
            id="cimne-directory-role"
            aria-label="<?php echo esc_attr__( 'Role', 'cimne' ); ?>"
            data-directory-role
        >
            <option
                class="cimne-directory-role-option cimne-directory-role-option--all"
                value="all"
                data-role-level="all"
            ><?php echo esc_html__( 'All departaments', 'cimne' ); ?></option>

            <option
                class="cimne-directory-role-option cimne-directory-role-option--level-primary cimne-directory-role-option--psr"
                value="psr"
                data-role-level="psr-pdi"
                data-filter-type="psr"
            ><?php echo esc_html__( 'PSR', 'cimne' ); ?></option>

            <?php foreach ( $psr_departments as $department ) : ?>
                <?php
                $department_code = isset( $department['code'] ) ? trim( (string) $department['code'] ) : '';
                $department_label = isset( $department['label'] ) ? trim( (string) $department['label'] ) : '';

                if ( '' === $department_code || '' === $department_label ) {
                    continue;
                }
                ?>
                <option
                    class="cimne-directory-role-option cimne-directory-role-option--level-department cimne-directory-role-option--psr-department"
                    value="psr-department:<?php echo esc_attr( $department_code ); ?>"
                    data-role-level="psr-department"
                    data-filter-type="psr-department"
                    data-department-code="<?php echo esc_attr( $department_code ); ?>"
                ><?php echo esc_html( '— ' . $department_label ); ?></option>
            <?php endforeach; ?>

            <option
                class="cimne-directory-role-option cimne-directory-role-option--level-primary cimne-directory-role-option--pdi"
                value="pdi"
                data-role-level="psr-pdi"
                data-filter-type="pdi"
            ><?php echo esc_html__( 'PDI', 'cimne' ); ?></option>

            <?php foreach ( $role_tree as $unit ) : ?>
                <?php
                $unit_code = isset( $unit['code'] ) ? (string) $unit['code'] : '';
                $unit_label = isset( $unit['label'] ) ? (string) $unit['label'] : $unit_code;
                $unit_type = isset( $unit['type'] ) ? strtoupper( (string) $unit['type'] ) : 'RC';
                $unit_codes = isset( $unit['unit_codes'] ) && is_array( $unit['unit_codes'] )
                    ? $unit['unit_codes']
                    : array( $unit_code );
                $groups = isset( $unit['groups'] ) && is_array( $unit['groups'] )
                    ? $unit['groups']
                    : array();
                $show_groups = ! isset( $unit['show_groups'] ) || ! empty( $unit['show_groups'] );

                $is_innovation_unit = 'IU' === $unit_type;
                $unit_filter_type = $is_innovation_unit ? 'innovation-unit' : 'cluster';
                $unit_value_prefix = $is_innovation_unit ? 'iu' : 'cluster';
                $unit_specific_class = $is_innovation_unit
                    ? 'cimne-directory-role-option--innovation-unit'
                    : 'cimne-directory-role-option--cluster';
                ?>

                <option
                    class="cimne-directory-role-option cimne-directory-role-option--level-unit cimne-directory-role-option--cluster-iu <?php echo esc_attr( $unit_specific_class ); ?>"
                    value="<?php echo esc_attr( $unit_value_prefix . ':' . $unit_code ); ?>"
                    data-role-level="cluster-iu"
                    data-filter-type="<?php echo esc_attr( $unit_filter_type ); ?>"
                    data-unit-codes="<?php echo esc_attr( implode( ',', $unit_codes ) ); ?>"
                ><?php echo esc_html( '— ' . $unit_label ); ?></option>

                <?php if ( $show_groups ) : ?>
                <?php foreach ( $groups as $group ) : ?>
                    <?php
                    $group_code = isset( $group['code'] ) ? (string) $group['code'] : '';
                    $group_label = isset( $group['label'] ) ? (string) $group['label'] : $group_code;
                    ?>
                    <option
                        class="cimne-directory-role-option cimne-directory-role-option--level-group cimne-directory-role-option--group"
                        value="group:<?php echo esc_attr( $group_code ); ?>"
                        data-role-level="group"
                        data-filter-type="group"
                        data-unit-codes="<?php echo esc_attr( $group_code ); ?>"
                    ><?php echo esc_html( '—— ' . $group_label ); ?></option>
                <?php endforeach; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
</form>
