<?php
/**
 * CIMNE navigation data layer.
 *
 * Converts native WordPress menu items into a normalized
 * hierarchical structure used by the navigation renderers.
 */

defined( 'ABSPATH' ) || exit;


/**
 * Get a normalized navigation tree from a registered menu location.
 *
 * @param string $location Registered WordPress menu location.
 *
 * @return array
 */
function cimne_get_navigation_tree( string $location ): array {

	$locations = get_nav_menu_locations();

	if (
		! isset( $locations[ $location ] ) ||
		! $locations[ $location ]
	) {
		return array();
	}

	$menu = wp_get_nav_menu_object(
		(int) $locations[ $location ]
	);

	if ( ! $menu ) {
		return array();
	}

	$items = wp_get_nav_menu_items(
		$menu->term_id,
		array(
			'update_post_term_cache' => false,
		)
	);

	if ( empty( $items ) || ! is_array( $items ) ) {
		return array();
	}

	$normalized_items = array();

	foreach ( $items as $item ) {

		$normalized_items[ (int) $item->ID ] =
			cimne_normalize_navigation_item( $item );
	}

	$tree = cimne_build_navigation_tree(
		$normalized_items
	);

	cimne_mark_navigation_ancestors( $tree );

	cimne_add_navigation_presentation( $tree );

	return $tree;
}


/**
 * Normalize a WordPress navigation menu item.
 *
 * @param WP_Post $item WordPress menu item.
 *
 * @return array
 */
function cimne_normalize_navigation_item( WP_Post $item ): array {

	return array(
		'id'        => (int) $item->ID,
		'parent_id' => (int) $item->menu_item_parent,

		'title' => (string) $item->title,
		'url'   => (string) $item->url,

		'target' => (string) $item->target,
		'rel'    => (string) $item->xfn,

		'classes' => array_values(
			array_filter(
				array_map(
					'sanitize_html_class',
					(array) $item->classes
				)
			)
		),

		'type'      => (string) $item->type,
		'object'    => (string) $item->object,
		'object_id' => (int) $item->object_id,

		'is_current' =>
			cimne_is_current_navigation_item( $item ),

		'is_current_ancestor' => false,

		'has_children' => false,
		'children'     => array(),

		/**
		 * Reserved for ACF/editorial data used by
		 * the desktop mega menus.
		 */
		'presentation' => array(),
	);
}


/**
 * Build a hierarchical navigation tree.
 *
 * @param array $items Flat normalized menu items.
 *
 * @return array
 */
function cimne_build_navigation_tree( array $items ): array {

	$tree = array();

	foreach ( array_keys( $items ) as $item_id ) {

		$parent_id = $items[ $item_id ]['parent_id'];

		if (
			$parent_id > 0 &&
			isset( $items[ $parent_id ] )
		) {

			$items[ $parent_id ]['children'][] =&
				$items[ $item_id ];

			continue;
		}

		$tree[] =& $items[ $item_id ];
	}

	unset( $item_id );

	cimne_set_navigation_children_state( $tree );

	return $tree;
}


/**
 * Populate has_children recursively.
 *
 * @param array $items Navigation tree.
 *
 * @return void
 */
function cimne_set_navigation_children_state(
	array &$items
): void {

	foreach ( $items as &$item ) {

		$item['has_children'] =
			! empty( $item['children'] );

		if ( $item['has_children'] ) {

			cimne_set_navigation_children_state(
				$item['children']
			);
		}
	}

	unset( $item );
}


/**
 * Determine whether a menu item is the current item.
 *
 * @param WP_Post $item WordPress menu item.
 *
 * @return bool
 */
function cimne_is_current_navigation_item(
	WP_Post $item
): bool {

	$object_id = (int) $item->object_id;

	switch ( $item->type ) {

		case 'post_type':

			return (
				$object_id > 0 &&
				get_queried_object_id() === $object_id
			);

		case 'taxonomy':

			$queried_object = get_queried_object();

			return (
				$queried_object instanceof WP_Term &&
				(int) $queried_object->term_id === $object_id &&
				$queried_object->taxonomy === $item->object
			);

		case 'post_type_archive':

			return is_post_type_archive(
				$item->object
			);

		case 'custom':

			return cimne_is_current_custom_menu_url(
				(string) $item->url
			);
	}

	return false;
}


/**
 * Determine whether a custom menu URL matches the current request.
 *
 * @param string $item_url Menu URL.
 *
 * @return bool
 */
function cimne_is_current_custom_menu_url(
	string $item_url
): bool {

	if ( '' === $item_url ) {
		return false;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] )
		? wp_unslash( $_SERVER['REQUEST_URI'] )
		: '/';

	$current_url = home_url( $request_uri );

	$current_parts = wp_parse_url(
		untrailingslashit( $current_url )
	);

	$item_parts = wp_parse_url(
		untrailingslashit( $item_url )
	);

	if ( ! $current_parts || ! $item_parts ) {
		return false;
	}

	$current_path = $current_parts['path'] ?? '/';
	$item_path    = $item_parts['path'] ?? '/';

	return untrailingslashit( $current_path ) ===
		untrailingslashit( $item_path );
}


/**
 * Mark items whose descendants contain the current page.
 *
 * @param array $items Navigation tree.
 *
 * @return bool True when the branch contains the current item.
 */
function cimne_mark_navigation_ancestors(
	array &$items
): bool {

	$branch_is_current = false;

	foreach ( $items as &$item ) {

		$child_is_current = false;

		if ( ! empty( $item['children'] ) ) {

			$child_is_current =
				cimne_mark_navigation_ancestors(
					$item['children']
				);
		}

		$item['is_current_ancestor'] =
			! $item['is_current'] &&
			$child_is_current;

		if (
			$item['is_current'] ||
			$child_is_current
		) {
			$branch_is_current = true;
		}
	}

	unset( $item );

	return $branch_is_current;
}