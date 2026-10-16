<?php
/**
 * CIMNE navigation header template.
 *
 * Main visual entry point for the custom CIMNE header/navigation component.
 * It prepares shared header data, loads the independent desktop/mobile
 * renderers. Search fields live inside the desktop/mobile bars so they can
 * replace the navigation without creating a second panel below the header.
 *
 * Expected $args:
 * - primary_navigation: normalized primary navigation tree.
 * - utility_navigation: normalized utility navigation tree.
 * - languages: normalized WPML language array.
 *
 * @package CIMNE
 */

defined( 'ABSPATH' ) || exit;

$navigation_args = is_array( $args ?? null )
	? $args
	: array();

$home_url = home_url( '/' );
$site_name = get_bloginfo( 'name' );
$logo_html = '';

/*
 * Prefer the native WordPress Custom Logo when available.
 */
$custom_logo_id = (int) get_theme_mod( 'custom_logo' );

if ( $custom_logo_id > 0 ) {
	$logo_alt = get_post_meta(
		$custom_logo_id,
		'_wp_attachment_image_alt',
		true
	);

	if ( '' === trim( (string) $logo_alt ) ) {
		$logo_alt = $site_name;
	}

	$logo_html = wp_get_attachment_image(
		$custom_logo_id,
		'full',
		false,
		array(
			'class'    => 'cimne-header__logo-image',
			'alt'      => $logo_alt,
			'decoding' => 'async',
		)
	);
}

/*
 * Divi commonly stores its logo as a URL in the theme options rather than
 * using the native WordPress Custom Logo. Keep this fallback so the custom
 * header can reuse the existing site logo without hardcoding its URL.
 */
if (
	'' === $logo_html &&
	function_exists( 'et_get_option' )
) {
	$divi_logo_url = (string) et_get_option( 'divi_logo' );

	if ( '' !== $divi_logo_url ) {
		$logo_html = sprintf(
			'<img class="cimne-header__logo-image" src="%1$s" alt="%2$s">',
			esc_url( $divi_logo_url ),
			esc_attr( $site_name )
		);
	}
}

/*
 * Last-resort accessible fallback when no image logo is configured.
 */
if ( '' === $logo_html ) {
	$logo_html = sprintf(
		'<span class="cimne-header__logo-text">%s</span>',
		esc_html( $site_name )
	);
}

$is_front_page = is_front_page();

$component_args = array_merge(
	$navigation_args,
	array(
		'home_url'     => $home_url,
		'site_name'    => $site_name,
		'logo_html'    => $logo_html,
		'is_front_page' => $is_front_page,
	)
);
?>

<header
	class="cimne-header<?php echo $is_front_page ? ' cimne-header--home' : ''; ?>"
	data-cimne-header
>
	<?php
	get_template_part(
		'template-parts/navigation/desktop',
		null,
		$component_args
	);

	get_template_part(
		'template-parts/navigation/mobile',
		null,
		$component_args
	);
	?>


</header>
