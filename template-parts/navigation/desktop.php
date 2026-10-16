<?php
/**
 * Desktop navigation template.
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

/**
 * Build safe classes for a normalized menu item.
 *
 * @param array  $item        Normalized item.
 * @param string $base_class  Base BEM class.
 * @param bool   $is_desktop  Whether the item belongs to desktop navigation.
 *
 * @return string
 */
$build_item_classes = static function ( array $item, string $base_class, bool $is_desktop = true ): string {
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

	if ( $is_desktop && in_array( 'header-cta', $item_classes, true ) ) {
		$classes[] = $base_class . '--cta';
	}

	return implode( ' ', array_unique( $classes ) );
};

/**
 * Output common anchor attributes for a normalized menu item.
 *
 * @param array $item Normalized item.
 *
 * @return string
 */
$build_link_attributes = static function ( array $item ): string {
	$attributes = array();
	$url        = isset( $item['url'] ) ? (string) $item['url'] : '';
	$target     = isset( $item['target'] ) ? (string) $item['target'] : '';
	$rel        = isset( $item['rel'] ) ? (string) $item['rel'] : '';

	$attributes[] = 'href="' . esc_url( $url ) . '"';

	if ( '_blank' === $target ) {
		$attributes[] = 'target="_blank"';

		$rel_tokens = preg_split( '/\s+/', trim( $rel ) ) ?: array();
		$rel_tokens = array_filter( array_map( 'sanitize_html_class', $rel_tokens ) );
		$rel_tokens[] = 'noopener';
		$rel_tokens[] = 'noreferrer';
		$rel_tokens = array_unique( $rel_tokens );

		$attributes[] = 'rel="' . esc_attr( implode( ' ', $rel_tokens ) ) . '"';
	} elseif ( '' !== $rel ) {
		$attributes[] = 'rel="' . esc_attr( $rel ) . '"';
	}

	if ( ! empty( $item['is_current'] ) ) {
		$attributes[] = 'aria-current="page"';
	}

	return implode( ' ', $attributes );
};
?>

<div class="cimne-header__desktop" data-desktop-header>

	<div class="cimne-header__utility">
		<div class="cimne-header__utility-inner">

			<?php if ( ! empty( $utility_navigation ) ) : ?>
				<nav class="cimne-utility-nav cimne-utility-nav--desktop" aria-label="<?php echo esc_attr__( 'Additional navigation', 'cimne' ); ?>">
					<ul class="cimne-utility-nav__list">
						<?php foreach ( $utility_navigation as $item ) : ?>
							<li class="<?php echo esc_attr( $build_item_classes( $item, 'cimne-utility-nav__item', false ) ); ?>">
								<a class="cimne-utility-nav__link" <?php echo $build_link_attributes( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
									<?php echo esc_html( $item['title'] ?? '' ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endif; ?>

			<?php if ( ! empty( $languages ) ) : ?>
				<nav class="cimne-language-nav cimne-language-nav--desktop" aria-label="<?php echo esc_attr__( 'Language', 'cimne' ); ?>">
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
	</div>

	<div class="cimne-header__main">
		<div class="cimne-header__main-inner">

			<a
				class="cimne-header__logo cimne-header__logo--desktop"
				href="<?php echo esc_url( $home_url ); ?>"
				aria-label="<?php echo esc_attr( sprintf( __( '%s - Home', 'cimne' ), $site_name ) ); ?>"
			>
				<?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</a>

			<?php if ( ! empty( $primary_navigation ) ) : ?>
				<nav class="cimne-nav cimne-nav--desktop" aria-label="<?php echo esc_attr__( 'Primary navigation', 'cimne' ); ?>" data-desktop-nav>
					<ul class="cimne-nav__list">

						<?php foreach ( $primary_navigation as $item ) : ?>
							<?php
							$item_id       = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
							$has_children  = ! empty( $item['has_children'] );
							$trigger_id    = 'cimne-desktop-trigger-' . $item_id;
							$panel_id      = 'cimne-desktop-mega-' . $item_id;
							$presentation  = isset( $item['presentation'] ) && is_array( $item['presentation'] )
								? $item['presentation']
								: array();
							$description   = isset( $presentation['description'] ) ? (string) $presentation['description'] : '';
							$feature       = isset( $presentation['feature'] ) && is_array( $presentation['feature'] )
								? $presentation['feature']
								: array();
							$item_classes  = $build_item_classes( $item, 'cimne-nav__item' );
							$is_cta        = in_array( 'header-cta', $item['classes'] ?? array(), true );
							?>

							<li class="<?php echo esc_attr( $item_classes ); ?>" data-menu-item="<?php echo esc_attr( (string) $item_id ); ?>">

								<a
									<?php echo $has_children ? 'id="' . esc_attr( $trigger_id ) . '"' : ''; ?>
									class="cimne-nav__link<?php echo $is_cta ? ' cimne-nav__link--cta' : ''; ?>"
									<?php echo $build_link_attributes( $item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									<?php if ( $has_children ) : ?>
										aria-expanded="false"
										aria-controls="<?php echo esc_attr( $panel_id ); ?>"
										data-mega-trigger
									<?php endif; ?>
								>
									<?php echo esc_html( $item['title'] ?? '' ); ?>
								</a>

								<?php if ( $has_children ) : ?>
									<div
										id="<?php echo esc_attr( $panel_id ); ?>"
										class="cimne-mega"
										aria-labelledby="<?php echo esc_attr( $trigger_id ); ?>"
										data-mega-panel
										hidden
									>
										<div class="cimne-mega__inner">

											<div class="cimne-mega__intro">
												<h2 class="cimne-mega__title">
													<?php echo esc_html( $item['title'] ?? '' ); ?>
												</h2>

												<?php if ( '' !== $description ) : ?>
													<p class="cimne-mega__description">
														<?php echo esc_html( $description ); ?>
													</p>
												<?php endif; ?>
											</div>

											<div class="cimne-mega__navigation">
												<ul class="cimne-mega__list">
													<?php foreach ( $item['children'] as $child ) : ?>
														<li class="<?php echo esc_attr( $build_item_classes( $child, 'cimne-mega__item', false ) ); ?>">
															<a class="cimne-mega__link" <?php echo $build_link_attributes( $child ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
																<?php echo esc_html( $child['title'] ?? '' ); ?>
															</a>
														</li>
													<?php endforeach; ?>
												</ul>
											</div>

											<?php if ( ! empty( $feature ) && 'none' !== ( $feature['type'] ?? 'none' ) ) : ?>
												<?php
												$feature_url      = isset( $feature['url'] ) ? (string) $feature['url'] : '';
												$feature_title    = isset( $feature['title'] ) ? (string) $feature['title'] : '';
												$feature_eyebrow  = isset( $feature['eyebrow'] ) ? (string) $feature['eyebrow'] : '';
												$feature_date     = isset( $feature['date'] ) ? (string) $feature['date'] : '';
												$feature_image_id = isset( $feature['image_id'] ) ? absint( $feature['image_id'] ) : 0;
												$feature_target   = isset( $feature['target'] ) ? (string) $feature['target'] : '';
												?>

												<div class="cimne-mega__feature">
													<a
														class="cimne-mega-feature"
														href="<?php echo esc_url( $feature_url ); ?>"
														<?php echo '_blank' === $feature_target ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
													>

														<?php if ( $feature_image_id > 0 ) : ?>
															<figure class="cimne-mega-feature__media">
																<?php
																echo wp_get_attachment_image(
																	$feature_image_id,
																	'large',
																	false,
																	array(
																		'class'   => 'cimne-mega-feature__image',
																		'loading' => 'lazy',
																		'alt'     => '',
																	)
																); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
																?>
															</figure>
														<?php endif; ?>

														<div class="cimne-mega-feature__content">
															<?php if ( '' !== $feature_eyebrow ) : ?>
																<span class="cimne-mega-feature__eyebrow">
																	<?php echo esc_html( $feature_eyebrow ); ?>
																</span>
															<?php endif; ?>

															<?php if ( '' !== $feature_date ) : ?>
																<span class="cimne-mega-feature__date">
																	<?php echo esc_html( $feature_date ); ?>
																</span>
															<?php endif; ?>

															<?php if ( '' !== $feature_title ) : ?>
																<span class="cimne-mega-feature__title">
																	<?php echo esc_html( $feature_title ); ?>
																</span>
															<?php endif; ?>

															<span class="cimne-mega-feature__arrow" aria-hidden="true">&rarr;</span>
														</div>

													</a>
												</div>
											<?php endif; ?>

										</div>
									</div>
								<?php endif; ?>

							</li>
						<?php endforeach; ?>

					</ul>
				</nav>
			<?php endif; ?>


			<div
				id="cimne-search-desktop"
				class="cimne-search-slot cimne-search-slot--desktop"
				data-search-panel="desktop"
				hidden
			>
				<form
					class="cimne-search-form"
					role="search"
					method="get"
					action="<?php echo esc_url( $home_url ); ?>"
				>
					<label class="screen-reader-text" for="cimne-search-field-desktop">
						<?php echo esc_html__( 'Search CIMNE', 'cimne' ); ?>
					</label>

					<div class="cimne-search-form__field-wrapper">
						<input
							id="cimne-search-field-desktop"
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
					class="cimne-search-close cimne-search-close--desktop"
					type="button"
					aria-label="<?php echo esc_attr__( 'Close search', 'cimne' ); ?>"
					data-search-close
				>
					<svg class="cimne-icon cimne-icon--close" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
						<path d="M5 5L19 19M19 5L5 19" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
					</svg>
				</button>
			</div>

			<button
				class="cimne-search-toggle cimne-search-toggle--desktop"
				type="button"
				aria-expanded="false"
				aria-controls="cimne-search-desktop"
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

		</div>
	</div>

</div>
