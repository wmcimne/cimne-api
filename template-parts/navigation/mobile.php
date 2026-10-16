<?php
/**
 * Mobile navigation template.
 *
 * Expected $args:
 * - primary_navigation: normalized primary navigation tree.
 * - utility_navigation: normalized utility navigation tree.
 * - languages: normalized language switcher array.
 * - home_url: site home URL.
 * - site_name: site name.
 * - logo_html: already prepared safe logo markup.
 *
 * @package CIMNE
 */

defined( 'ABSPATH' ) || exit;

$primary_navigation = isset( $args['primary_navigation'] ) && is_array( $args['primary_navigation'] )
	? $args['primary_navigation']
	: array();

$utility_navigation = isset( $args['utility_navigation'] ) && is_array( $args['utility_navigation'] )
	? $args['utility_navigation']
	: array();

$languages = isset( $args['languages'] ) && is_array( $args['languages'] )
	? $args['languages']
	: array();

$home_url = isset( $args['home_url'] )
	? (string) $args['home_url']
	: home_url( '/' );

$site_name = isset( $args['site_name'] )
	? (string) $args['site_name']
	: get_bloginfo( 'name' );

$logo_html = isset( $args['logo_html'] )
	? (string) $args['logo_html']
	: '';

$build_item_classes = static function ( array $item, string $base_class ): string {
	$classes = array( $base_class );

	if ( ! empty( $item['has_children'] ) ) {
		$classes[] = $base_class . '--has-children';
	}

	if ( ! empty( $item['is_current'] ) ) {
		$classes[] = 'is-current';
	}

	if ( ! empty( $item['is_current_ancestor'] ) ) {
		$classes[] = 'is-current-section';
	}

	$item_classes = isset( $item['classes'] ) && is_array( $item['classes'] )
		? $item['classes']
		: array();

	foreach ( $item_classes as $class ) {
		$class = sanitize_html_class( (string) $class );

		if ( '' !== $class ) {
			$classes[] = $class;
		}
	}

	if ( in_array( 'header-cta', $item_classes, true ) ) {
		$classes[] = $base_class . '--cta';
	}

	return implode( ' ', array_unique( $classes ) );
};

$build_link_attributes = static function ( array $item ): string {
	$attributes = array();
	$url        = isset( $item['url'] ) ? (string) $item['url'] : '';
	$target     = isset( $item['target'] ) ? (string) $item['target'] : '';
	$rel        = isset( $item['rel'] ) ? (string) $item['rel'] : '';

	$attributes[] = 'href="' . esc_url( $url ) . '"';

	if ( '_blank' === $target ) {
		$attributes[] = 'target="_blank"';
		$attributes[] = 'rel="noopener noreferrer"';
	} elseif ( '' !== $rel ) {
		$attributes[] = 'rel="' . esc_attr( $rel ) . '"';
	}

	if ( ! empty( $item['is_current'] ) ) {
		$attributes[] = 'aria-current="page"';
	}

	return implode( ' ', $attributes );
};
?>

<div class="cimne-header__mobile" data-mobile-header>

	<div class="cimne-header__mobile-bar">

		<a
			class="cimne-header__logo cimne-header__logo--mobile"
			href="<?php echo esc_url( $home_url ); ?>"
			aria-label="<?php echo esc_attr( sprintf( __( '%s - Home', 'cimne' ), $site_name ) ); ?>"
		>
			<?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>


		<div
			id="cimne-search-mobile"
			class="cimne-search-slot cimne-search-slot--mobile"
			data-search-panel="mobile"
			hidden
		>
			<form
				class="cimne-search-form cimne-search-form--mobile"
				role="search"
				method="get"
				action="<?php echo esc_url( $home_url ); ?>"
			>
				<label class="screen-reader-text" for="cimne-search-field-mobile">
					<?php echo esc_html__( 'Search CIMNE', 'cimne' ); ?>
				</label>

				<div class="cimne-search-form__field-wrapper">
					<input
						id="cimne-search-field-mobile"
						class="cimne-search-form__field"
						type="search"
						name="s"
						value="<?php echo esc_attr( get_search_query() ); ?>"
						placeholder="<?php echo esc_attr__( 'Search', 'cimne' ); ?>"
						autocomplete="off"
					>

					<button
						class="cimne-search-form__submit"
						type="submit"
						aria-label="<?php echo esc_attr__( 'Submit search', 'cimne' ); ?>"
					>
						<svg class="cimne-icon cimne-icon--search" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
							<circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="2" />
							<path d="M16 16L21 21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
						</svg>
					</button>
				</div>
			</form>

			<button
				class="cimne-search-close cimne-search-close--mobile"
				type="button"
				aria-label="<?php echo esc_attr__( 'Close search', 'cimne' ); ?>"
				data-search-close
			>
				<svg class="cimne-icon cimne-icon--close" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
					<path d="M5 5L19 19M19 5L5 19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
				</svg>
			</button>
		</div>

		<div class="cimne-header__mobile-actions">

			<button
				class="cimne-search-toggle cimne-search-toggle--mobile"
				type="button"
				aria-expanded="false"
				aria-controls="cimne-search-mobile"
				aria-label="<?php echo esc_attr__( 'Open search', 'cimne' ); ?>"
				data-search-toggle
			>
				<svg
					class="cimne-icon cimne-icon--search"
					viewBox="0 0 24 24"
					width="24"
					height="24"
					aria-hidden="true"
					focusable="false"
				>
					<circle cx="11" cy="11" r="6.5" fill="none" stroke="currentColor" stroke-width="2" />
					<path d="M16 16L21 21" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
				</svg>
			</button>

			<button
				class="cimne-mobile-toggle"
				type="button"
				aria-expanded="false"
				aria-controls="cimne-mobile-navigation"
				aria-label="<?php echo esc_attr__( 'Open navigation', 'cimne' ); ?>"
				data-mobile-toggle
			>
				<span class="cimne-mobile-toggle__icon cimne-mobile-toggle__icon--open" aria-hidden="true">
					<svg
						class="cimne-icon cimne-icon--menu"
						viewBox="0 0 24 24"
						width="24"
						height="24"
						focusable="false"
					>
						<path d="M4 6H20M4 12H20M4 18H20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
					</svg>
				</span>

				<span class="cimne-mobile-toggle__icon cimne-mobile-toggle__icon--close" aria-hidden="true">
					<svg
						class="cimne-icon cimne-icon--close"
						viewBox="0 0 24 24"
						width="24"
						height="24"
						focusable="false"
					>
						<path d="M5 5L19 19M19 5L5 19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
					</svg>
				</span>
			</button>

		</div>
	</div>

	<div
		id="cimne-mobile-navigation"
		class="cimne-mobile-navigation"
		data-mobile-navigation
		hidden
	>
		<div class="cimne-mobile-navigation__scroll">

			<div class="cimne-mobile-panel cimne-mobile-panel--root" data-mobile-panel="root">

				<?php if ( ! empty( $primary_navigation ) ) : ?>
					<nav class="cimne-mobile-nav" aria-label="<?php echo esc_attr__( 'Primary navigation', 'cimne' ); ?>">
						<ul class="cimne-mobile-nav__list">
							<?php foreach ( $primary_navigation as $item ) : ?>
								<?php
								$item_id      = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
								$has_children = ! empty( $item['has_children'] );
								$panel_id     = 'cimne-mobile-panel-' . $item_id;
								$is_cta       = in_array( 'header-cta', $item['classes'] ?? array(), true );
								?>

								<li class="<?php echo esc_attr( $build_item_classes( $item, 'cimne-mobile-nav__item' ) ); ?>">
									<div class="cimne-mobile-nav__row">
										<a
											class="cimne-mobile-nav__link<?php echo $is_cta ? ' cimne-mobile-nav__link--cta' : ''; ?>"
											<?php echo $build_link_attributes( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										>
											<?php echo esc_html( $item['title'] ?? '' ); ?>
										</a>

										<?php if ( $has_children ) : ?>
											<button
												class="cimne-mobile-nav__submenu-toggle"
												type="button"
												aria-expanded="false"
												aria-controls="<?php echo esc_attr( $panel_id ); ?>"
												aria-label="<?php echo esc_attr( sprintf( __( 'Open %s submenu', 'cimne' ), $item['title'] ?? '' ) ); ?>"
												data-mobile-open="<?php echo esc_attr( (string) $item_id ); ?>"
											>
												<span aria-hidden="true">&rarr;</span>
											</button>
										<?php endif; ?>
									</div>
								</li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>

				<?php if ( ! empty( $utility_navigation ) ) : ?>
					<nav class="cimne-utility-nav cimne-utility-nav--mobile" aria-label="<?php echo esc_attr__( 'Additional navigation', 'cimne' ); ?>">
						<ul class="cimne-utility-nav__list">
							<?php foreach ( $utility_navigation as $item ) : ?>
								<li class="<?php echo esc_attr( $build_item_classes( $item, 'cimne-utility-nav__item' ) ); ?>">
									<a class="cimne-utility-nav__link" <?php echo $build_link_attributes( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
										<?php echo esc_html( $item['title'] ?? '' ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>

				<?php if ( ! empty( $languages ) ) : ?>
					<nav class="cimne-language-nav cimne-language-nav--mobile" aria-label="<?php echo esc_attr__( 'Language', 'cimne' ); ?>">
						<ul class="cimne-language-nav__list">
							<?php foreach ( $languages as $language ) : ?>
								<?php
								$language_code = isset( $language['code'] ) ? strtolower( (string) $language['code'] ) : '';
								$language_url  = isset( $language['url'] ) ? (string) $language['url'] : '';
								$is_current    = ! empty( $language['current'] );
								?>
								<li class="cimne-language-nav__item<?php echo $is_current ? ' is-current' : ''; ?>">
									<a
										class="cimne-language-nav__link"
										href="<?php echo esc_url( $language_url ); ?>"
										lang="<?php echo esc_attr( $language_code ); ?>"
										hreflang="<?php echo esc_attr( $language_code ); ?>"
										<?php echo $is_current ? 'aria-current="page"' : ''; ?>
									>
										<?php echo esc_html( strtoupper( $language_code ) ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>

			</div>

			<?php foreach ( $primary_navigation as $item ) : ?>
				<?php if ( empty( $item['has_children'] ) ) : ?>
					<?php continue; ?>
				<?php endif; ?>

				<?php
				$item_id      = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
				$panel_id     = 'cimne-mobile-panel-' . $item_id;
				$title_id     = $panel_id . '-title';
				$presentation = isset( $item['presentation'] ) && is_array( $item['presentation'] )
					? $item['presentation']
					: array();
				$description  = isset( $presentation['description'] ) ? (string) $presentation['description'] : '';
				?>

				<section
					id="<?php echo esc_attr( $panel_id ); ?>"
					class="cimne-mobile-panel cimne-mobile-panel--section"
					aria-labelledby="<?php echo esc_attr( $title_id ); ?>"
					data-mobile-panel="<?php echo esc_attr( (string) $item_id ); ?>"
					hidden
				>
					<header class="cimne-mobile-panel__header">
						<h2 id="<?php echo esc_attr( $title_id ); ?>" class="cimne-mobile-panel__title">
							<?php echo esc_html( $item['title'] ?? '' ); ?>
						</h2>

						<button
							class="cimne-mobile-panel__back"
							type="button"
							aria-label="<?php echo esc_attr__( 'Back to main navigation', 'cimne' ); ?>"
							data-mobile-back
						>
							<span aria-hidden="true">&larr;</span>
						</button>
					</header>

					<?php if ( '' !== $description ) : ?>
						<p class="cimne-mobile-panel__description">
							<?php echo esc_html( $description ); ?>
						</p>
					<?php endif; ?>

					<ul class="cimne-mobile-panel__list">
						<?php foreach ( $item['children'] as $child ) : ?>
							<li class="<?php echo esc_attr( $build_item_classes( $child, 'cimne-mobile-panel__item' ) ); ?>">
								<a class="cimne-mobile-panel__link" <?php echo $build_link_attributes( $child ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
									<?php echo esc_html( $child['title'] ?? '' ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>

		</div>
	</div>

</div>
