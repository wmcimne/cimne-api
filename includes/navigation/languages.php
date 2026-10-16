<?php
/**
 * CIMNE navigation languages.
 *
 * Provides a normalized language-switcher data structure for the
 * desktop and mobile navigation templates.
 *
 * @package CIMNE
 */

defined( 'ABSPATH' ) || exit;


/**
 * Get the active WPML languages for the CIMNE navigation.
 *
 * The returned structure is intentionally independent from WPML's
 * original array so the templates do not need to know anything about
 * the plugin API.
 *
 * WPML's custom language order configured in the backend is preserved.
 * Languages without a translation for the current page are kept and
 * WPML links them to that language's home page.
 *
 * @return array<int, array<string, mixed>>
 */
function cimne_get_navigation_languages(): array {

	/*
	 * wpml_active_languages requires the main WordPress query to have
	 * been instantiated, so this function must be used after the `wp`
	 * action. In normal frontend template rendering this condition is met.
	 */
	if ( ! did_action( 'wp' ) ) {
		return array();
	}

	$languages = apply_filters(
		'wpml_active_languages',
		null,
		array(
			'skip_missing' => 0,
		)
	);

	if ( empty( $languages ) || ! is_array( $languages ) ) {
		return array();
	}

	$normalized = array();

	foreach ( $languages as $language ) {

		if ( ! is_array( $language ) ) {
			continue;
		}

		$code = isset( $language['language_code'] )
			? sanitize_key( (string) $language['language_code'] )
			: '';

		$url = isset( $language['url'] )
			? esc_url_raw( (string) $language['url'] )
			: '';

		if ( '' === $code || '' === $url ) {
			continue;
		}

		$normalized[] = array(
			'code'            => $code,
			'url'             => $url,
			'current'         => ! empty( $language['active'] ),
			'missing'         => ! empty( $language['missing'] ),
			'native_name'     => isset( $language['native_name'] )
				? sanitize_text_field( (string) $language['native_name'] )
				: '',
			'translated_name' => isset( $language['translated_name'] )
				? sanitize_text_field( (string) $language['translated_name'] )
				: '',
		);
	}

	return $normalized;
}
