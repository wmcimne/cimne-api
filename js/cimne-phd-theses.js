(function () {
    'use strict';

    var I18N = window.cimnePhdThesesI18n || {};
    function t(key, fallback) {
        return typeof I18N[key] === 'string' && I18N[key] !== ''
            ? I18N[key]
            : fallback;
    }

    function normalizeSearchText(text) {
        return String(text || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function initDateInput(input) {
        if (!input) {
            return;
        }

        function useDateType() {
            input.type = 'date';
        }

        input.addEventListener('focus', useDateType);
        input.addEventListener('click', useDateType);

        input.addEventListener('blur', function () {
            if (!input.value) {
                input.type = 'text';
            }
        });
    }

    function keyboardActivate(element, callback) {
        if (!element) {
            return;
        }

        element.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                callback();
            }
        });
    }

    function initPhdThesesFilters(form) {
        var container = document.getElementById('theses-container');
        var searchInput = form.querySelector('[data-phd-theses-search]');
        var fromInput = form.querySelector('[data-phd-theses-from]');
        var toInput = form.querySelector('[data-phd-theses-to]');
        var applyButton = form.querySelector('[data-phd-theses-apply]');
        var refreshButton = form.querySelector('[data-phd-theses-refresh]');
        var liveStatus = form.querySelector('[data-phd-theses-filter-status]');
        var appliedFrom = '';
        var appliedTo = '';
        var emptyMessage = null;

        if (!container) {
            return;
        }

        if (form.getAttribute('data-cimne-phd-theses-initialized') === '1') {
            return;
        }
        form.setAttribute('data-cimne-phd-theses-initialized', '1');

        initDateInput(fromInput);
        initDateInput(toInput);

        function getItems() {
            return Array.prototype.slice.call(
                container.querySelectorAll('.theses-list')
            );
        }

        function getEmptyMessage() {
            if (emptyMessage && emptyMessage.isConnected) {
                return emptyMessage;
            }

            emptyMessage = container.querySelector('[data-phd-theses-filter-empty]');

            if (!emptyMessage) {
                emptyMessage = document.createElement('p');
                emptyMessage.className = 'no-results cimne-phd-theses-filter-empty';
                emptyMessage.setAttribute('data-phd-theses-filter-empty', '');
                emptyMessage.setAttribute('hidden', 'hidden');
                emptyMessage.textContent = t('noResults', 'No results found.');
                container.prepend(emptyMessage);
            }

            return emptyMessage;
        }

        function itemMatchesDate(item) {
            if (!appliedFrom && !appliedTo) {
                return true;
            }

            var itemDate = item.getAttribute('data-reading-date') || '';

            if (!/^\d{4}-\d{2}-\d{2}$/.test(itemDate)) {
                return false;
            }

            if (appliedFrom && itemDate < appliedFrom) {
                return false;
            }

            if (appliedTo && itemDate > appliedTo) {
                return false;
            }

            return true;
        }

        function applyFilters() {
            var items = getItems();
            var searchNeedle = normalizeSearchText(searchInput ? searchInput.value.trim() : '');
            var visibleCount = 0;

            items.forEach(function (item) {
                var itemText = normalizeSearchText(item.textContent);
                var matchesSearch = !searchNeedle || itemText.indexOf(searchNeedle) !== -1;
                var visible = matchesSearch && itemMatchesDate(item);

                item.classList.toggle('is-phd-theses-filter-hidden', !visible);
                item.setAttribute('aria-hidden', visible ? 'false' : 'true');

                if (visible) {
                    visibleCount += 1;
                }
            });

            var message = getEmptyMessage();

            if (visibleCount === 0 && items.length > 0) {
                message.removeAttribute('hidden');
            } else {
                message.setAttribute('hidden', 'hidden');
            }
        }

        function applyAllFilters() {
            appliedFrom = fromInput ? fromInput.value : '';
            appliedTo = toInput ? toInput.value : '';
            applyFilters();

            if (liveStatus) {
                liveStatus.textContent = t('filtersApplied', 'Filters applied.');
            }
        }

        function refreshFilters() {
            form.reset();
            appliedFrom = '';
            appliedTo = '';

            if (fromInput) {
                fromInput.type = 'text';
            }

            if (toInput) {
                toInput.type = 'text';
            }

            applyFilters();

            if (liveStatus) {
                liveStatus.textContent = t('filtersCleared', 'Filters cleared.');
            }
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            applyAllFilters();
        });

        if (applyButton) {
            applyButton.addEventListener('click', function (event) {
                event.preventDefault();
                applyAllFilters();
            });
            keyboardActivate(applyButton, applyAllFilters);
        }

        if (refreshButton) {
            refreshButton.addEventListener('click', function (event) {
                event.preventDefault();
                refreshFilters();
            });
            keyboardActivate(refreshButton, refreshFilters);
        }

    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-cimne-phd-theses-filters]'),
            initPhdThesesFilters
        );
    });
}());
