<?php
/**
 * Personalización de la pantalla de acceso de WordPress.
 *
 * @package CIMNE_Child
 */

defined( 'ABSPATH' ) || exit;


/**
 * Carga la hoja de estilos personalizada del login.
 *
 * @return void
 */
function cimne_enqueue_login_styles(): void {
	$relative_path = '/css/login.css';
	$absolute_path = get_stylesheet_directory() . $relative_path;
	$version       = wp_get_theme()->get( 'Version' );

	if ( file_exists( $absolute_path ) ) {
		$version = (string) filemtime( $absolute_path );
	}

	wp_enqueue_style(
		'cimne-login',
		get_stylesheet_directory_uri() . $relative_path,
		array(),
		$version
	);
}

add_action(
	'login_enqueue_scripts',
	'cimne_enqueue_login_styles'
);


/**
 * Cambia el enlace del logo de la pantalla de acceso.
 *
 * @param string $login_header_url URL predeterminada.
 *
 * @return string
 */
function cimne_login_header_url( string $login_header_url ): string {
	unset( $login_header_url );

	return home_url( '/' );
}

add_filter(
	'login_headerurl',
	'cimne_login_header_url'
);


/**
 * Cambia el texto accesible del enlace del logo.
 *
 * Sustituye al filtro obsoleto login_headertitle.
 *
 * @param string $login_header_text Texto predeterminado.
 *
 * @return string
 */
function cimne_login_header_text( string $login_header_text ): string {
	unset( $login_header_text );

	$site_name        = get_bloginfo( 'name' );
	$site_description = get_bloginfo( 'description' );

	if ( '' === $site_description ) {
		return $site_name;
	}

	return sprintf(
		'%1$s | %2$s',
		$site_name,
		$site_description
	);
}

add_filter(
	'login_headertext',
	'cimne_login_header_text'
);


/**
 * Sustituye los errores detallados de acceso por un mensaje genérico.
 *
 * Evita revelar si el nombre de usuario o el correo existen.
 *
 * @param string $errors Mensaje original de WordPress.
 *
 * @return string
 */
function cimne_login_error_message( string $errors ): string {
	unset( $errors );

	return esc_html__( 'Incorrect login details.', 'cimne' );
}

add_filter(
	'login_errors',
	'cimne_login_error_message'
);


/**
 * Controla el destino después de iniciar sesión.
 *
 * - Administradores: conserva el destino solicitado.
 * - Otros usuarios: redirige a la portada.
 *
 * @param string           $redirect_to           Destino calculado por WordPress.
 * @param string           $requested_redirect_to Destino solicitado originalmente.
 * @param WP_User|WP_Error $user                  Usuario autenticado o error.
 *
 * @return string
 */
function cimne_login_redirect(
	string $redirect_to,
	string $requested_redirect_to,
	$user
): string {
	unset( $requested_redirect_to );

	if ( ! $user instanceof WP_User ) {
		return $redirect_to;
	}

	if ( in_array( 'administrator', $user->roles, true ) ) {
		return $redirect_to;
	}

	return home_url( '/' );
}

add_filter(
	'login_redirect',
	'cimne_login_redirect',
	10,
	3
);