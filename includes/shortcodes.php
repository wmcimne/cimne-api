<?php
/**
 * Shortcodes del tema hijo de CIMNE.
 *
 * @package CIMNE_Child
 */

defined( 'ABSPATH' ) || exit;


/**
 * Devuelve el título de la página padre del contenido actual.
 *
 * Shortcode:
 *
 * [titulo_padre]
 *
 * Si el contenido actual no tiene padre, devuelve una cadena vacía.
 *
 * @return string Título del contenido padre.
 */
function cimne_parent_title_shortcode(): string {
	$current_post = get_post();

	if ( ! $current_post instanceof WP_Post ) {
		return '';
	}

	$parent_id = wp_get_post_parent_id( $current_post );

	if ( ! $parent_id ) {
		return '';
	}

	$parent_title = get_the_title( $parent_id );

	if ( '' === $parent_title ) {
		return '';
	}

	return esc_html( $parent_title );
}

add_shortcode(
	'titulo_padre',
	'cimne_parent_title_shortcode'
);