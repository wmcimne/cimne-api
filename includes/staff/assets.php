<?php
/**
 * Shared Staff assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the shared Staff behavior.
 *
 * The script is not enqueued globally. It is pulled in as a dependency only
 * by Research Staff, Directory or Staff Profile components.
 *
 * @return void
 */
function cimne_staff_register_assets() {
    $script = '/js/cimne-staff.js';

    wp_register_script(
        'cimne-staff',
        get_stylesheet_directory_uri() . $script,
        array(),
        function_exists( 'cimne_asset_version' )
            ? cimne_asset_version( $script )
            : (string) wp_get_theme()->get( 'Version' ),
        array(
            'strategy'  => 'defer',
            'in_footer' => true,
        )
    );

    wp_localize_script(
        'cimne-staff',
        'cimneStaffI18n',
        array(
            'emailCopied' => __( 'Email copied', 'cimne' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'cimne_staff_register_assets', 20 );

/**
 * Enqueues the shared Staff behavior after it has been registered.
 *
 * @return void
 */
function cimne_staff_enqueue_asset() {
    wp_enqueue_script( 'cimne-staff' );
}
