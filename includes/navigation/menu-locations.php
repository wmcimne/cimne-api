<?php
/**
 * CIMNE navigation menu locations.
 */

defined( 'ABSPATH' ) || exit;


/**
 * Register CIMNE navigation locations.
 *
 * @return void
 */
function cimne_register_navigation_menus(): void {

	register_nav_menus(
		array(
			'cimne_primary' => __( 'CIMNE Main Navigation', 'cimne' ),
			'cimne_utility' => __( 'CIMNE Utility Navigation', 'cimne' ),
		)
	);
}

add_action( 'after_setup_theme', 'cimne_register_navigation_menus' );