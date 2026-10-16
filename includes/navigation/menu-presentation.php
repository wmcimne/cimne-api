<?php
/**
 * CIMNE navigation presentation data.
 *
 * Reads the ACF fields associated with top-level menu pages and converts
 * them into a normalized structure that can be consumed by the desktop
 * and mobile navigation renderers.
 *
 * Expected ACF fields:
 *
 * - menu_description (Textarea)
 * - menu_feature (Group)
 *   - type          : none | manual | content
 *
 *   Manual:
 *   - image         : Image
 *   - eyebrow       : Text
 *   - title         : Text
 *   - link          : Link
 *
 *   Selected content:
 *   - content       : Post Object
 *   - content_image : Image
 *   - content_title : Text
 *   - show_date     : True / False
 */

defined( 'ABSPATH' ) || exit;


/**
 * Add presentation data to top-level navigation items.
 *
 * Presentation data is only loaded for top-level items that have children.
 * This means direct links such as "Jobs & Fellowships" do not require any
 * special handling.
 *
 * @param array $items Normalized navigation tree.
 *
 * @return void
 */
function cimne_add_navigation_presentation( array &$items ): void {

	foreach ( $items as &$item ) {

		$item['presentation'] = cimne_get_default_navigation_presentation();

		if (
			empty( $item['has_children'] ) ||
			empty( $item['object_id'] )
		) {
			continue;
		}

		$item['presentation'] = cimne_get_navigation_presentation(
			(int) $item['object_id']
		);
	}

	unset( $item );
}


/**
 * Get the normalized presentation data associated with a page/post.
 *
 * If ACF is unavailable or the object has no configured values, a stable
 * empty structure is returned so renderers never need to check for ACF.
 *
 * @param int $object_id WordPress object ID linked to the menu item.
 *
 * @return array
 */
function cimne_get_navigation_presentation( int $object_id ): array {

	$presentation = cimne_get_default_navigation_presentation();

	if (
		$object_id <= 0 ||
		! function_exists( 'get_field' )
	) {
		return $presentation;
	}

	$description = get_field(
		'menu_description',
		$object_id
	);

	if ( is_string( $description ) ) {
		$presentation['description'] =
			sanitize_textarea_field( $description );
	}

	$feature = get_field(
		'menu_feature',
		$object_id
	);

	if ( ! is_array( $feature ) ) {
		return $presentation;
	}

	$type = isset( $feature['type'] )
		? sanitize_key( (string) $feature['type'] )
		: 'none';

	switch ( $type ) {

		case 'manual':
			$presentation['feature'] =
				cimne_normalize_manual_menu_feature( $feature );
			break;

		case 'content':
			$presentation['feature'] =
				cimne_normalize_content_menu_feature( $feature );
			break;

		case 'none':
		default:
			break;
	}

	return $presentation;
}


/**
 * Get the default presentation structure.
 *
 * @return array
 */
function cimne_get_default_navigation_presentation(): array {

	return array(
		'description' => '',
		'feature'     => cimne_get_default_navigation_feature(),
	);
}


/**
 * Get the default featured-content structure.
 *
 * Renderers receive the same keys regardless of whether the source was
 * a manual ACF CTA or a selected WordPress post.
 *
 * @return array
 */
function cimne_get_default_navigation_feature(): array {

	return array(
		'type'     => 'none',
		'image_id' => 0,
		'eyebrow'  => '',
		'date'     => '',
		'title'    => '',
		'url'      => '',
		'target'   => '',
	);
}


/**
 * Normalize a manual featured CTA.
 *
 * @param array $feature Raw ACF group value.
 *
 * @return array
 */
function cimne_normalize_manual_menu_feature(
	array $feature
): array {

	$normalized = cimne_get_default_navigation_feature();

	$normalized['type'] = 'manual';

	$normalized['image_id'] =
		cimne_get_acf_image_id(
			$feature['image'] ?? null
		);

	$normalized['eyebrow'] = isset( $feature['eyebrow'] )
		? sanitize_text_field(
			(string) $feature['eyebrow']
		)
		: '';

	$normalized['title'] = isset( $feature['title'] )
		? sanitize_text_field(
			(string) $feature['title']
		)
		: '';

	$link = $feature['link'] ?? null;

	if ( is_array( $link ) ) {

		$normalized['url'] = isset( $link['url'] )
			? esc_url_raw( (string) $link['url'] )
			: '';

		$normalized['target'] =
			cimne_normalize_link_target(
				$link['target'] ?? ''
			);

		/*
		 * Fallback to the ACF Link title when the dedicated feature title
		 * has intentionally been left empty.
		 */
		if (
			'' === $normalized['title'] &&
			! empty( $link['title'] )
		) {
			$normalized['title'] =
				sanitize_text_field(
					(string) $link['title']
				);
		}

	} elseif ( is_string( $link ) ) {

		/*
		 * Defensive fallback in case the ACF Link field is configured
		 * to return only a URL instead of a Link Array.
		 */
		$normalized['url'] = esc_url_raw( $link );
	}

	if ( ! cimne_navigation_feature_has_content( $normalized ) ) {
		return cimne_get_default_navigation_feature();
	}

	return $normalized;
}


/**
 * Normalize a featured WordPress content item.
 *
 * @param array $feature Raw ACF group value.
 *
 * @return array
 */
function cimne_normalize_content_menu_feature(
	array $feature
): array {

	$normalized = cimne_get_default_navigation_feature();

	$content_id = cimne_get_acf_post_id(
		$feature['content'] ?? null
	);

	if ( $content_id <= 0 ) {
		return $normalized;
	}

	$post = get_post( $content_id );

	if (
		! $post instanceof WP_Post ||
		! is_post_publicly_viewable( $post )
	) {
		return $normalized;
	}

	$normalized['type'] = 'content';

	$custom_image_id = cimne_get_acf_image_id(
		$feature['content_image'] ?? null
	);

	$normalized['image_id'] = $custom_image_id > 0
		? $custom_image_id
		: (int) get_post_thumbnail_id( $content_id );

	$custom_title = isset( $feature['content_title'] )
		? sanitize_text_field(
			(string) $feature['content_title']
		)
		: '';

	$normalized['title'] = '' !== $custom_title
		? $custom_title
		: get_the_title( $content_id );

	$normalized['url'] = esc_url_raw(
		(string) get_permalink( $content_id )
	);

	$normalized['target'] = '';

	$show_date = ! empty(
		$feature['show_date']
	);

	if ( $show_date ) {
		$normalized['date'] =
			get_the_date( '', $content_id );
	}

	if ( ! cimne_navigation_feature_has_content( $normalized ) ) {
		return cimne_get_default_navigation_feature();
	}

	return $normalized;
}


/**
 * Get an attachment ID from the possible ACF Image return formats.
 *
 * Supports:
 * - Attachment ID.
 * - Attachment array.
 * - WP_Post attachment object.
 *
 * A raw image URL is intentionally not converted to an attachment ID.
 * For this component ACF Image fields should preferably return an ID.
 *
 * @param mixed $image ACF Image value.
 *
 * @return int
 */
function cimne_get_acf_image_id( $image ): int {

	if ( is_numeric( $image ) ) {
		return absint( $image );
	}

	if (
		is_array( $image ) &&
		isset( $image['ID'] )
	) {
		return absint( $image['ID'] );
	}

	if ( $image instanceof WP_Post ) {
		return absint( $image->ID );
	}

	return 0;
}


/**
 * Get a post ID from the possible ACF Post Object return formats.
 *
 * Supports:
 * - Post ID.
 * - WP_Post object.
 *
 * @param mixed $content ACF Post Object value.
 *
 * @return int
 */
function cimne_get_acf_post_id( $content ): int {

	if ( is_numeric( $content ) ) {
		return absint( $content );
	}

	if ( $content instanceof WP_Post ) {
		return absint( $content->ID );
	}

	return 0;
}


/**
 * Normalize the target returned by an ACF Link field.
 *
 * @param mixed $target Raw link target.
 *
 * @return string
 */
function cimne_normalize_link_target( $target ): string {

	$target = sanitize_key( (string) $target );

	return '_blank' === $target
		? '_blank'
		: '';
}


/**
 * Determine whether a normalized feature contains anything renderable.
 *
 * @param array $feature Normalized feature.
 *
 * @return bool
 */
function cimne_navigation_feature_has_content(
	array $feature
): bool {

	return (
		! empty( $feature['image_id'] ) ||
		! empty( $feature['eyebrow'] ) ||
		! empty( $feature['date'] ) ||
		! empty( $feature['title'] ) ||
		! empty( $feature['url'] )
	);
}
