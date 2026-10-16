<?php
/**
 * CIMNE navigation assets.
 *
 * @package CIMNE
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueue the custom navigation stylesheet and vanilla JavaScript controller.
 *
 * @return void
 */
function cimne_enqueue_navigation_assets(): void {
	$stylesheet_path = get_stylesheet_directory() . '/css/navigation.css';
	$stylesheet_uri  = get_stylesheet_directory_uri() . '/css/navigation.css';
	$script_path     = get_stylesheet_directory() . '/js/navigation.js';
	$script_uri      = get_stylesheet_directory_uri() . '/js/navigation.js';

	if ( file_exists( $stylesheet_path ) ) {
		wp_enqueue_style(
			'cimne-navigation',
			$stylesheet_uri,
			array(),
			(string) filemtime( $stylesheet_path )
		);
	}

	if ( file_exists( $script_path ) ) {
		wp_enqueue_script(
			'cimne-navigation',
			$script_uri,
			array(),
			(string) filemtime( $script_path ),
			true
		);
	}
}

add_action( 'wp_enqueue_scripts', 'cimne_enqueue_navigation_assets', 30 );