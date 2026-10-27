(function () {
    'use strict';


    function normalise(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function splitCodes(value) {
        if (!value) {
            return [];
        }

        return String(value)
            .split(',')
            .map(function (code) {
                return code.trim().toUpperCase();
            })
            .filter(Boolean);
    }

    function hasIntersection(a, b) {
        if (!a.length || !b.length) {
            return false;
        }

        var lookup = new Set(a);

        return b.some(function (value) {
            return lookup.has(value);
        });
    }

    function searchMatches(card, query) {
        if (!query) {
            return true;
        }

        var haystack = normalise(card.dataset.searchText || card.textContent || '');
        var terms = normalise(query).split(' ').filter(Boolean);

        return terms.every(function (term) {
            return haystack.indexOf(term) !== -1;
        });
    }

    function locationMatches(card, select) {
        var value = String(select.value || 'all').trim();

        if ('all' === value) {
            return true;
        }

        return String(card.dataset.centreCode || '').trim() === value;
    }

    function roleMatches(card, select) {
        var value = select.value || 'all';

        if ('all' === value) {
            return true;
        }

        if ('psr' === value) {
            return '1' === card.dataset.isPsr;
        }

        if ('pdi' === value) {
            return '1' === card.dataset.isPdi;
        }

        var selectedOption = select.options[select.selectedIndex];
        var filterType = selectedOption ? selectedOption.dataset.filterType : '';

        if ('psr-department' === filterType) {
            var departmentCode = selectedOption
                ? String(selectedOption.dataset.departmentCode || '').trim()
                : '';

            return '1' === card.dataset.isPsr
                && departmentCode !== ''
                && String(card.dataset.departmentCode || '').trim() === departmentCode;
        }

        var filterCodes = splitCodes(selectedOption ? selectedOption.dataset.unitCodes : '');
        var personCodes = splitCodes(card.dataset.unitCodes || '');

        // Cluster, Innovation Unit and Group live below PDI in the selector, so both the PDI
        // flag and the unit relation must match.
        return '1' === card.dataset.isPdi && hasIntersection(filterCodes, personCodes);
    }

    function updateStatus(status, visible) {
        if (!status) {
            return;
        }

        var strings = window.cimneDirectoryI18n || {};

        if (0 === visible) {
            status.textContent = strings.noResults || 'No people found.';
            return;
        }

        if (1 === visible) {
            status.textContent = strings.oneResult || '1 person found.';
            return;
        }

        var template = strings.manyResults || '%d people found.';
        status.textContent = template.replace('%d', String(visible));
    }

    function initDirectoryFilters(filters) {
        var directory = document.querySelector('[data-cimne-directory]');

        if (!directory) {
            return;
        }

        var search = filters.querySelector('[data-directory-search]');
        var location = filters.querySelector('[data-directory-location]');
        var role = filters.querySelector('[data-directory-role]');
        var cards = Array.prototype.slice.call(
            directory.querySelectorAll('[data-cimne-directory-person]')
        );
        var noResults = directory.querySelector('[data-directory-no-results]');
        var status = directory.querySelector('[data-directory-status]');

        if (!search || !location || !role || !cards.length) {
            return;
        }

        function applyFilters() {
            var query = search.value || '';
            var visible = 0;

            cards.forEach(function (card) {
                var matches = searchMatches(card, query)
                    && locationMatches(card, location)
                    && roleMatches(card, role);

                card.classList.toggle('is-directory-filter-hidden', !matches);
                card.hidden = !matches;

                if (matches) {
                    visible += 1;
                }
            });

            if (noResults) {
                noResults.hidden = visible !== 0;
            }

            updateStatus(status, visible);
        }

        search.addEventListener('input', applyFilters);
        location.addEventListener('change', applyFilters);
        role.addEventListener('change', applyFilters);

        applyFilters();
    }

    function init() {
        var filters = document.querySelectorAll('[data-cimne-directory-filters]');
        filters.forEach(initDirectoryFilters);
    }

    if ('loading' === document.readyState) {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
