/**
 * CIMNE Navigation.
 *
 * Desktop:
 * - Main bar visual interaction state.
 * - Mega menus on hover/focus; top-level links remain navigable.
 * - Main bar becomes fixed on internal pages after Utility scrolls away.
 *
 * Mobile:
 * - Full-screen navigation overlay.
 * - Parent text navigates; arrow opens the submenu panel.
 * - Browser Back closes the overlay.
 *
 * Search:
 * - Replaces the navigation/actions inside the current desktop/mobile bar.
 *
 * No jQuery dependency.
 */

(() => {
	'use strict';

	const BREAKPOINT = 980;
	const MEGA_CLOSE_DELAY = 120;

	const init = () => {
		const header = document.querySelector('[data-cimne-header]');

		if (!header) {
			return;
		}

		const body = document.body;
		const isHome = header.classList.contains('cimne-header--home');
		const isMobileViewport = () => window.innerWidth <= BREAKPOINT;

		/* ------------------------------------------------------------------
		 * Desktop references.
		 * ------------------------------------------------------------------ */

		const mainBar = header.querySelector('.cimne-header__main');
		const desktopNav = header.querySelector('[data-desktop-nav]');
		const megaTriggers = desktopNav
			? Array.from(desktopNav.querySelectorAll('[data-mega-trigger]'))
			: [];
		const megaPanels = desktopNav
			? Array.from(desktopNav.querySelectorAll('[data-mega-panel]'))
			: [];

		const desktopMainInteractives = mainBar
			? Array.from(
				mainBar.querySelectorAll(
					'.cimne-header__logo--desktop, .cimne-nav__link, .cimne-search-toggle--desktop'
				)
			)
			: [];

		let activeMegaTrigger = null;
		let activeMegaPanel = null;
		let megaCloseTimer = null;
		let mainInteractionTimer = null;

		/* ------------------------------------------------------------------
		 * Mobile references.
		 * ------------------------------------------------------------------ */

		const mobileToggle = header.querySelector('[data-mobile-toggle]');
		const mobileNavigation = header.querySelector('[data-mobile-navigation]');
		const mobileOpenButtons = mobileNavigation
			? Array.from(mobileNavigation.querySelectorAll('[data-mobile-open]'))
			: [];
		const mobileBackButtons = mobileNavigation
			? Array.from(mobileNavigation.querySelectorAll('[data-mobile-back]'))
			: [];
		const mobilePanels = mobileNavigation
			? Array.from(mobileNavigation.querySelectorAll('[data-mobile-panel]'))
			: [];

		let mobileIsOpen = false;
		let currentMobilePanel = 'root';
		let mobileHistoryPushed = false;
		let suppressNextPopstate = false;
		let pendingMobileNavigationUrl = '';

		/* ------------------------------------------------------------------
		 * Search references.
		 * ------------------------------------------------------------------ */

		const searchToggles = Array.from(header.querySelectorAll('[data-search-toggle]'));
		const searchPanels = Array.from(header.querySelectorAll('[data-search-panel]'));
		const searchCloseButtons = Array.from(header.querySelectorAll('[data-search-close]'));

		let searchIsOpen = false;
		let activeSearchPanel = null;
		let activeSearchToggle = null;

		/* ------------------------------------------------------------------
		 * Sticky references.
		 * ------------------------------------------------------------------ */

		let mainFixed = false;
		let mainFixThreshold = 0;
		let mainPlaceholder = null;
		let lastViewportWasMobile = isMobileViewport();

		/* ==================================================================
		 * Desktop main visual interaction.
		 * ================================================================== */

		const clearMainInteractionTimer = () => {
			if (mainInteractionTimer !== null) {
				window.clearTimeout(mainInteractionTimer);
				mainInteractionTimer = null;
			}
		};

		const setMainInteracting = (interacting) => {
			if (!mainBar || isMobileViewport()) {
				return;
			}

			mainBar.classList.toggle('is-main-interacting', interacting);
		};

		const anyDesktopControlIsActive = () => desktopMainInteractives.some((element) => (
			element.matches(':hover') ||
			element === document.activeElement ||
			element.contains(document.activeElement)
		));

		const scheduleMainInteractionReset = () => {
			clearMainInteractionTimer();

			mainInteractionTimer = window.setTimeout(() => {
				if (
					searchIsOpen ||
					activeMegaPanel ||
					anyDesktopControlIsActive()
				) {
					return;
				}

				setMainInteracting(false);
			}, 0);
		};

		desktopMainInteractives.forEach((element) => {
			element.addEventListener('mouseenter', () => {
				clearMainInteractionTimer();
				setMainInteracting(true);
			});

			element.addEventListener('mouseleave', () => {
				scheduleMainInteractionReset();
			});

			element.addEventListener('focus', () => {
				clearMainInteractionTimer();
				setMainInteracting(true);
			});

			element.addEventListener('blur', () => {
				scheduleMainInteractionReset();
			});
		});

		/* ==================================================================
		 * Desktop mega menus.
		 * ================================================================== */

		const clearMegaCloseTimer = () => {
			if (megaCloseTimer !== null) {
				window.clearTimeout(megaCloseTimer);
				megaCloseTimer = null;
			}
		};

		const getPanelForTrigger = (trigger) => {
			if (!trigger) {
				return null;
			}

			const panelId = trigger.getAttribute('aria-controls');

			if (!panelId) {
				return null;
			}

			return document.getElementById(panelId);
		};

		const closeMegaMenu = ({ restoreFocus = false } = {}) => {
			clearMegaCloseTimer();

			const previousTrigger = activeMegaTrigger;

			megaTriggers.forEach((trigger) => {
				trigger.setAttribute('aria-expanded', 'false');
				trigger.closest('li')?.classList.remove('is-mega-active');
			});

			megaPanels.forEach((panel) => {
				panel.hidden = true;
			});

			activeMegaTrigger = null;
			activeMegaPanel = null;
			header.classList.remove('is-mega-open');

			if (restoreFocus && previousTrigger) {
				previousTrigger.focus();
			}

			scheduleMainInteractionReset();
		};

		const openMegaMenu = (trigger) => {
			if (!trigger || isMobileViewport() || searchIsOpen) {
				return;
			}

			const panel = getPanelForTrigger(trigger);

			if (!panel) {
				return;
			}

			clearMegaCloseTimer();
			setMainInteracting(true);

			if (activeMegaTrigger === trigger) {
				return;
			}

			megaTriggers.forEach((candidate) => {
				candidate.setAttribute('aria-expanded', 'false');
				candidate.closest('li')?.classList.remove('is-mega-active');
			});

			megaPanels.forEach((candidate) => {
				candidate.hidden = candidate !== panel;
			});

			activeMegaTrigger = trigger;
			activeMegaPanel = panel;

			trigger.setAttribute('aria-expanded', 'true');
			trigger.closest('li')?.classList.add('is-mega-active');
			panel.hidden = false;
			header.classList.add('is-mega-open');
		};

		const scheduleMegaClose = () => {
			clearMegaCloseTimer();

			megaCloseTimer = window.setTimeout(() => {
				if (!activeMegaTrigger) {
					return;
				}

				const item = activeMegaTrigger.closest('li');
				const focusInside = item && item.contains(document.activeElement);
				const hoverInside = item && item.matches(':hover');

				if (!focusInside && !hoverInside) {
					closeMegaMenu();
				}
			}, MEGA_CLOSE_DELAY);
		};

		megaTriggers.forEach((trigger) => {
			trigger.addEventListener('mouseenter', () => {
				openMegaMenu(trigger);
			});

			trigger.addEventListener('focus', () => {
				openMegaMenu(trigger);
			});

			const item = trigger.closest('li');

			if (!item) {
				return;
			}

			item.addEventListener('mouseenter', () => {
				clearMegaCloseTimer();
			});

			item.addEventListener('mouseleave', () => {
				scheduleMegaClose();
			});

			item.addEventListener('focusout', (event) => {
				if (event.relatedTarget && item.contains(event.relatedTarget)) {
					return;
				}

				scheduleMegaClose();
			});
		});

		/* ==================================================================
		 * Search.
		 * ================================================================== */

		const hideAllSearchPanels = () => {
			searchPanels.forEach((panel) => {
				panel.hidden = true;
			});
		};

		const getSearchPanelForToggle = (toggle) => {
			if (!toggle) {
				return null;
			}

			const panelId = toggle.getAttribute('aria-controls');

			if (!panelId) {
				return null;
			}

			return document.getElementById(panelId);
		};

		const closeSearch = ({ restoreFocus = false } = {}) => {
			if (!searchIsOpen) {
				return;
			}

			hideAllSearchPanels();

			searchToggles.forEach((toggle) => {
				toggle.setAttribute('aria-expanded', 'false');
			});

			header.classList.remove('is-search-open');
			body.classList.remove('cimne-search-open');

			const previousToggle = activeSearchToggle;

			searchIsOpen = false;
			activeSearchPanel = null;
			activeSearchToggle = null;

			if (restoreFocus && previousToggle) {
				previousToggle.focus();
			}

			scheduleMainInteractionReset();
		};

		const openSearch = (toggle) => {
			const panel = getSearchPanelForToggle(toggle);

			if (!panel) {
				return;
			}

			closeMegaMenu();

			if (mobileIsOpen) {
				closeMobileNavigation({
					restoreFocus: false,
					removeHistory: true,
				});
			}

			hideAllSearchPanels();
			panel.hidden = false;

			searchToggles.forEach((candidate) => {
				candidate.setAttribute(
					'aria-expanded',
					candidate === toggle ? 'true' : 'false'
				);
			});

			searchIsOpen = true;
			activeSearchPanel = panel;
			activeSearchToggle = toggle;

			header.classList.add('is-search-open');
			body.classList.add('cimne-search-open');

			if (!isMobileViewport()) {
				setMainInteracting(true);
			}

			const field = panel.querySelector('input[type="search"]');

			if (field) {
				window.requestAnimationFrame(() => {
					field.focus();
				});
			}
		};

		searchToggles.forEach((toggle) => {
			toggle.addEventListener('click', () => {
				if (searchIsOpen && activeSearchToggle === toggle) {
					closeSearch({ restoreFocus: true });
					return;
				}

				openSearch(toggle);
			});
		});

		searchCloseButtons.forEach((button) => {
			button.addEventListener('click', () => {
				closeSearch({ restoreFocus: true });
			});
		});

		/* ==================================================================
		 * Mobile navigation.
		 * ================================================================== */

		const getMobilePanel = (name) => mobilePanels.find(
			(panel) => panel.dataset.mobilePanel === String(name)
		) || null;

		const showMobilePanel = (name = 'root', { focus = true } = {}) => {
			if (!mobileNavigation) {
				return;
			}

			const targetPanel = getMobilePanel(name);

			if (!targetPanel) {
				return;
			}

			mobilePanels.forEach((panel) => {
				panel.hidden = panel !== targetPanel;
			});

			currentMobilePanel = String(name);

			header.classList.toggle(
				'is-mobile-submenu-open',
				String(name) !== 'root'
			);

			mobileOpenButtons.forEach((button) => {
				button.setAttribute(
					'aria-expanded',
					button.getAttribute('aria-controls') === targetPanel.id ? 'true' : 'false'
				);
			});

			mobileNavigation.scrollTop = 0;
			const scrollContainer = mobileNavigation.querySelector('.cimne-mobile-navigation__scroll');

			if (scrollContainer) {
				scrollContainer.scrollTop = 0;
			}

			if (focus && name !== 'root') {
				const focusTarget = targetPanel.querySelector(
					'[data-mobile-back], a[href], button:not([disabled])'
				);

				focusTarget?.focus();
			}
		};

		const pushMobileHistoryState = () => {
			if (mobileHistoryPushed) {
				return;
			}

			history.pushState(
				{
					...(history.state || {}),
					cimneMobileNavigation: true,
				},
				'',
				window.location.href
			);

			mobileHistoryPushed = true;
		};

		const openMobileNavigation = () => {
			if (!mobileNavigation || !mobileToggle || !isMobileViewport()) {
				return;
			}

			if (searchIsOpen) {
				closeSearch();
			}

			closeMegaMenu();
			mobileNavigation.hidden = false;
			mobileToggle.setAttribute('aria-expanded', 'true');
			header.classList.add('is-mobile-open');
			body.classList.add('cimne-navigation-open');
			mobileIsOpen = true;
			showMobilePanel('root', { focus: false });
			pushMobileHistoryState();
		};

		function closeMobileNavigation({
			restoreFocus = false,
			removeHistory = true,
		} = {}) {
			if (!mobileNavigation || !mobileToggle) {
				return;
			}

			mobileNavigation.hidden = true;
			mobileToggle.setAttribute('aria-expanded', 'false');
			header.classList.remove('is-mobile-open');
			header.classList.remove('is-mobile-submenu-open');
			body.classList.remove('cimne-navigation-open');
			mobileIsOpen = false;
			showMobilePanel('root', { focus: false });

			if (removeHistory && mobileHistoryPushed) {
				mobileHistoryPushed = false;
				suppressNextPopstate = true;
				history.back();
			} else if (!removeHistory) {
				mobileHistoryPushed = false;
			}

			if (restoreFocus) {
				mobileToggle.focus();
			}
		}

		mobileToggle?.addEventListener('click', () => {
			if (mobileIsOpen) {
				closeMobileNavigation({ restoreFocus: true });
			} else {
				openMobileNavigation();
			}
		});

		mobileOpenButtons.forEach((button) => {
			button.addEventListener('click', () => {
				const panelName = button.dataset.mobileOpen;

				if (panelName) {
					showMobilePanel(panelName);
				}
			});
		});

		mobileBackButtons.forEach((button) => {
			button.addEventListener('click', () => {
				const previousPanel = currentMobilePanel;
				showMobilePanel('root', { focus: false });

				const opener = mobileOpenButtons.find(
					(candidate) => candidate.dataset.mobileOpen === previousPanel
				);

				opener?.focus();
			});
		});

		/*
		 * Clean the artificial history entry before following an ordinary
		 * same-window mobile-menu link. This avoids returning to a duplicate
		 * copy of the current page when the user later presses Back.
		 */
		mobileNavigation?.addEventListener('click', (event) => {
			const link = event.target.closest('a[href]');

			if (
				!link ||
				event.defaultPrevented ||
				event.button !== 0 ||
				event.metaKey ||
				event.ctrlKey ||
				event.shiftKey ||
				event.altKey ||
				link.target === '_blank' ||
				!mobileHistoryPushed
			) {
				return;
			}

			event.preventDefault();
			pendingMobileNavigationUrl = link.href;
			history.back();
		});

		window.addEventListener('popstate', () => {
			if (suppressNextPopstate) {
				suppressNextPopstate = false;
				return;
			}

			if (pendingMobileNavigationUrl) {
				const url = pendingMobileNavigationUrl;
				pendingMobileNavigationUrl = '';
				mobileHistoryPushed = false;
				closeMobileNavigation({
					restoreFocus: false,
					removeHistory: false,
				});
				window.location.href = url;
				return;
			}

			if (mobileIsOpen) {
				closeMobileNavigation({
					restoreFocus: false,
					removeHistory: false,
				});
			}
		});

		/* ==================================================================
		 * Keyboard.
		 * ================================================================== */

		document.addEventListener('keydown', (event) => {
			if (event.key !== 'Escape') {
				return;
			}

			if (searchIsOpen) {
				event.preventDefault();
				closeSearch({ restoreFocus: true });
				return;
			}

			if (mobileIsOpen) {
				event.preventDefault();
				closeMobileNavigation({ restoreFocus: true });
				return;
			}

			if (activeMegaPanel) {
				event.preventDefault();
				closeMegaMenu({ restoreFocus: true });
			}
		});

		/* ==================================================================
		 * Sticky internal desktop main bar.
		 * ================================================================== */

		const ensureMainPlaceholder = () => {
			if (!mainBar || mainPlaceholder) {
				return;
			}

			mainPlaceholder = document.createElement('div');
			mainPlaceholder.className = 'cimne-header__main-placeholder';
			mainPlaceholder.setAttribute('aria-hidden', 'true');
			mainPlaceholder.hidden = true;
			mainBar.insertAdjacentElement('afterend', mainPlaceholder);
		};

		const calculateMainFixThreshold = () => {
			if (!mainBar || isHome || isMobileViewport()) {
				mainFixThreshold = 0;
				return;
			}

			const reference = mainFixed && mainPlaceholder
				? mainPlaceholder
				: mainBar;
			const rect = reference.getBoundingClientRect();

			mainFixThreshold = window.scrollY + rect.top;
		};

		const setMainFixed = (fixed) => {
			if (!mainBar || isHome || isMobileViewport()) {
				fixed = false;
			}

			if (mainFixed === fixed) {
				return;
			}

			ensureMainPlaceholder();
			mainFixed = fixed;
			header.classList.toggle('is-main-fixed', fixed);

			if (mainPlaceholder) {
				if (fixed) {
					mainPlaceholder.style.height = `${mainBar.offsetHeight}px`;
					mainPlaceholder.hidden = false;
				} else {
					mainPlaceholder.style.height = '0px';
					mainPlaceholder.hidden = true;
				}
			}
		};

		const updateStickyState = () => {
			if (!mainBar || isHome || isMobileViewport()) {
				setMainFixed(false);
				return;
			}

			setMainFixed(window.scrollY >= mainFixThreshold);
		};

		if (mainBar && !isHome) {
			ensureMainPlaceholder();
			calculateMainFixThreshold();
			updateStickyState();
		}

		/* ==================================================================
		 * Scroll / resize / orientation.
		 * ================================================================== */

		let scrollTicking = false;

		window.addEventListener(
			'scroll',
			() => {
				if (scrollTicking) {
					return;
				}

				scrollTicking = true;

				window.requestAnimationFrame(() => {
					closeMegaMenu();

					if (!isHome) {
						updateStickyState();
					}

					scrollTicking = false;
				});
			},
			{ passive: true }
		);

		let resizeTimer = null;

		const handleViewportChange = () => {
			const viewportIsMobile = isMobileViewport();

			if (viewportIsMobile !== lastViewportWasMobile) {
				closeMegaMenu();
				setMainInteracting(false);

				if (mobileIsOpen) {
					closeMobileNavigation({
						restoreFocus: false,
						removeHistory: true,
					});
				}

				if (searchIsOpen) {
					closeSearch();
				}

				lastViewportWasMobile = viewportIsMobile;
			}

			setMainFixed(false);

			window.requestAnimationFrame(() => {
				calculateMainFixThreshold();
				updateStickyState();
			});
		};

		window.addEventListener(
			'resize',
			() => {
				if (resizeTimer !== null) {
					window.clearTimeout(resizeTimer);
				}

				resizeTimer = window.setTimeout(handleViewportChange, 120);
			},
			{ passive: true }
		);

		window.addEventListener(
			'orientationchange',
			() => {
				window.setTimeout(handleViewportChange, 150);
			},
			{ passive: true }
		);

		window.addEventListener('pageshow', () => {
			if (!mobileIsOpen) {
				body.classList.remove('cimne-navigation-open');
			}

			if (!searchIsOpen) {
				body.classList.remove('cimne-search-open');
			}

			calculateMainFixThreshold();
			updateStickyState();
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();