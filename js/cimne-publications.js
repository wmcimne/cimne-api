(function () {
    'use strict';

    var I18N = window.cimnePublicationsI18n || {};

    function t(key, fallback) {
        return typeof I18N[key] === 'string' && I18N[key] !== '' ? I18N[key] : fallback;
    }

    function requestJson(url, params) {
        var requestUrl = new URL(url, window.location.origin);

        Object.keys(params).forEach(function (key) {
            if (params[key] !== '' && params[key] !== null && typeof params[key] !== 'undefined') {
                requestUrl.searchParams.set(key, params[key]);
            }
        });

        return fetch(requestUrl.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: { Accept: 'application/json' }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        });
    }

    function initDateInput(input) {
        if (!input) {
            return;
        }

        input.addEventListener('focus', function () {
            input.type = 'date';
        });

        input.addEventListener('click', function () {
            input.type = 'date';
        });

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

    function initPublications(component) {
        var listUrl = component.getAttribute('data-list-url') || '';
        var context = component.getAttribute('data-publications-context') === 'research' ? 'research' : 'global';
        var unit = component.getAttribute('data-unit-code') || '';
        var results = component.querySelector('[data-publications-results]');
        var pagination = component.querySelector('[data-publications-pagination]');
        var status = component.querySelector('[data-publications-status]');
        var form = context === 'global' ? document.querySelector('[data-cimne-publications-filters]') : null;
        var currentPage = 1;
        var appliedFilters = {
            search: '',
            cluster: '',
            subtype: '',
            from: '',
            to: ''
        };

        if (!listUrl || !results || !pagination) {
            return;
        }

        if (component.getAttribute('data-cimne-publications-initialized') === '1') {
            return;
        }
        component.setAttribute('data-cimne-publications-initialized', '1');

        function setStatus(message) {
            if (status) {
                status.textContent = message || '';
            }
        }

        function setLoading(loading) {
            component.classList.toggle('is-loading', !!loading);
            results.setAttribute('aria-busy', loading ? 'true' : 'false');
            pagination.classList.toggle('is-loading', !!loading);
        }

        function readFilters() {
            if (!form) {
                return {
                    search: '',
                    cluster: '',
                    subtype: '',
                    from: '',
                    to: ''
                };
            }

            var search = form.querySelector('[data-publication-search]');
            var cluster = form.querySelector('[data-publication-cluster]');
            var subtype = form.querySelector('[data-publication-type]');
            var from = form.querySelector('[data-publication-from]');
            var to = form.querySelector('[data-publication-to]');

            return {
                search: search ? search.value.trim() : '',
                cluster: cluster ? cluster.value : '',
                subtype: subtype ? subtype.value : '',
                from: from ? from.value : '',
                to: to ? to.value : ''
            };
        }

        function loadPage(page) {
            page = parseInt(page, 10) || 1;
            currentPage = Math.max(1, page);

            var params = {
                context: context,
                page: currentPage
            };

            if (context === 'research') {
                params.unit = unit;
            } else {
                params.search = appliedFilters.search;
                params.cluster = appliedFilters.cluster;
                params.subtype = appliedFilters.subtype;
                params.from = appliedFilters.from;
                params.to = appliedFilters.to;
            }

            setLoading(true);
            setStatus('');

            return requestJson(listUrl, params).then(function (data) {
                results.innerHTML = data.html || '';
                pagination.innerHTML = data.pagination || '';
                setStatus('');
                results.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }).catch(function () {
                setStatus(t('loadError', 'Unable to load publications.'));
            }).finally(function () {
                setLoading(false);
            });
        }

        if (form) {
            var apply = form.querySelector('[data-publications-apply]');
            var refresh = form.querySelector('[data-publications-refresh]');
            var fromInput = form.querySelector('[data-publication-from]');
            var toInput = form.querySelector('[data-publication-to]');
            var live = form.querySelector('[data-publications-filter-status]');

            initDateInput(fromInput);
            initDateInput(toInput);

            function applyFilters() {
                appliedFilters = readFilters();
                currentPage = 1;
                loadPage(1).then(function () {
                    if (live) {
                        live.textContent = t('filtersApplied', 'Filters applied.');
                    }
                });
            }

            function refreshFilters() {
                form.reset();
                if (fromInput) {
                    fromInput.type = 'text';
                }
                if (toInput) {
                    toInput.type = 'text';
                }
                appliedFilters = readFilters();
                currentPage = 1;
                loadPage(1).then(function () {
                    if (live) {
                        live.textContent = t('filtersCleared', 'Filters cleared.');
                    }
                });
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                applyFilters();
            });

            if (apply) {
                apply.addEventListener('click', function (event) {
                    event.preventDefault();
                    applyFilters();
                });
                keyboardActivate(apply, applyFilters);
            }

            if (refresh) {
                refresh.addEventListener('click', function (event) {
                    event.preventDefault();
                    refreshFilters();
                });
                keyboardActivate(refresh, refreshFilters);
            }
        }

        pagination.addEventListener('click', function (event) {
            var link = event.target.closest('a[data-page]');
            if (!link || !pagination.contains(link)) {
                return;
            }

            event.preventDefault();
            var page = parseInt(link.getAttribute('data-page'), 10);
            if (page && page !== currentPage) {
                loadPage(page);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        Array.prototype.forEach.call(
            document.querySelectorAll('[data-cimne-publications]'),
            initPublications
        );
    });
}());
