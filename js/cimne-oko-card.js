/**
 * OKO Card social-link mapper.
 *
 * Data is rendered server-side by [cimne_oko_card_social]. This script only
 * applies those already-sanitized values to the existing Divi social buttons.
 */
(() => {
    'use strict';

    const initOkoCardLinks = () => {
        const data = document.querySelector('[data-cimne-oko-card-links]');
        const container = document.querySelector('#oko-social-container');

        if (!data || !container) {
            return;
        }

        const links = {
            '.oko-email': data.dataset.email ? `mailto:${data.dataset.email}` : '#',
            '.oko-web': data.dataset.web || '#',
            '.oko-lnkdn': data.dataset.linkedin || '#',
            '.oko-x': data.dataset.x || '#'
        };

        Object.entries(links).forEach(([selector, href]) => {
            container.querySelectorAll(`.oko-social-button${selector}`).forEach((button) => {
                const anchor = button.matches('a') ? button : button.querySelector('a');

                if (!anchor) {
                    return;
                }

                anchor.setAttribute('href', href);
            });
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initOkoCardLinks, { once: true });
        return;
    }

    initOkoCardLinks();
})();
