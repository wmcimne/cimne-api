<?php
/**
 * CIMNE navigation render entry point.
 *
 * Prepares the normalized data required by the custom navigation component,
 * renders the main header template and exposes the component to Divi through
 * the [cimne_navigation] shortcode.
 *
 * @package CIMNE
 */

defined( 'ABSPATH' ) || exit;


/**
 * Get all data required by the navigation templates.
 *
 * The primary and utility menus are managed from Appearance -> Menus.
 * The language switcher is provided by WPML through the normalized language
 * data layer.
 *
 * @return array{
 *     primary_navigation: array,
 *     utility_navigation: array,
 *     languages: array
 * }
 */
function cimne_get_navigation_template_data(): array {

	$data = array(
		'primary_navigation' => cimne_get_navigation_tree(
			'cimne_primary'
		),
		'utility_navigation' => cimne_get_navigation_tree(
			'cimne_utility'
		),
		'languages'          => cimne_get_navigation_languages(),
	);

	/**
	 * Filter the complete navigation data before rendering.
	 *
	 * Consumers receive normalized data rather than raw WordPress menu items
	 * or WPML structures.
	 *
	 * @param array $data Normalized navigation template data.
	 */
	return apply_filters(
		'cimne_navigation_template_data',
		$data
	);
}


/**
 * Render the complete CIMNE header/navigation component.
 *
 * The header template is the single visual entry point. It is responsible
 * for loading the desktop and mobile templates with the same normalized data.
 *
 * @return void
 */
function cimne_render_navigation(): void {

	$data = cimne_get_navigation_template_data();

	get_template_part(
		'template-parts/navigation/header',
		null,
		$data
	);
}


/**
 * Shortcode adapter used by the Divi Theme Builder.
 *
 * Shortcode callbacks must return their markup, so the normal template output
 * is captured and returned instead of being printed directly while Divi is
 * processing the shortcode.
 *
 * Usage:
 * [cimne_navigation]
 *
 * @return string
 */
function cimne_navigation_shortcode(): string {

	ob_start();

	cimne_render_navigation();

	return (string) ob_get_clean();
}

add_shortcode(
	'cimne_navigation',
	'cimne_navigation_shortcode'
);