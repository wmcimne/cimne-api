(function () {
    'use strict';

    var I18N = window.cimneFellowshipsI18n || {};

    function t(key, fallback) {
        return Object.prototype.hasOwnProperty.call(I18N, key) ? I18N[key] : fallback;
    }

    function normalizeText(value) {
        value = String(value || '').toLowerCase();
        if (value.normalize) {
            value = value.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }
        return value.trim();
    }

    function requestJson(url, params) {
        var target = new URL(url, window.location.origin);
        Object.keys(params || {}).forEach(function (key) {
            var value = params[key];
            if (value !== undefined && value !== null && String(value) !== '') {
                target.searchParams.set(key, String(value));
            }
        });

        return fetch(target.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        });
    }

    function emptyFilters() {
        return { search: '', category: '', beneficiary: '' };
    }

    function cloneFilters(filters) {
        filters = filters || {};
        return {
            search: String(filters.search || ''),
            category: String(filters.category || ''),
            beneficiary: String(filters.beneficiary || '')
        };
    }

    function readFilters(form) {
        if (!form) {
            return emptyFilters();
        }
        var search = form.querySelector('[data-fellowship-filter-search]');
        var category = form.querySelector('[data-fellowship-filter-category]');
        var beneficiary = form.querySelector('[data-fellowship-filter-beneficiary]');

        return {
            search: search ? search.value : '',
            category: category ? category.value : '',
            beneficiary: beneficiary ? beneficiary.value : ''
        };
    }

    function writeFilters(form, filters) {
        filters = cloneFilters(filters);
        if (!form) {
            return;
        }
        var search = form.querySelector('[data-fellowship-filter-search]');
        var category = form.querySelector('[data-fellowship-filter-category]');
        var beneficiary = form.querySelector('[data-fellowship-filter-beneficiary]');
        if (search) { search.value = filters.search; }
        if (category) { category.value = filters.category; }
        if (beneficiary) { beneficiary.value = filters.beneficiary; }
    }

    function serverKey(status, filters) {
        filters = cloneFilters(filters);
        return [status, filters.category].join('|');
    }

    function setFilterStatus(form, message) {
        if (!form) { return; }
        var live = form.querySelector('[data-fellowship-filter-status]');
        if (live) { live.textContent = message || ''; }
    }

    function applyClientFilters(content, filters) {
        var list = content.querySelector('.cimne-fellowship-list');
        if (!list) { return; }

        filters = cloneFilters(filters);
        var oldEmpty = list.querySelector('[data-client-filter-empty]');
        if (oldEmpty) { oldEmpty.remove(); }

        var search = normalizeText(filters.search);
        var beneficiary = filters.beneficiary;
        var items = Array.prototype.slice.call(list.querySelectorAll('.cimne-fellowship-list__item'));
        var visible = 0;

        items.forEach(function (item) {
            var haystack = normalizeText(item.getAttribute('data-fellowship-search') || '');
            var ids = (item.getAttribute('data-fellowship-beneficiary-ids') || '')
                .split(',')
                .map(function (value) { return value.trim(); })
                .filter(Boolean);

            var matchesSearch = !search || haystack.indexOf(search) !== -1;
            var matchesBeneficiary = !beneficiary || ids.indexOf(beneficiary) !== -1;
            var show = matchesSearch && matchesBeneficiary;
            item.hidden = !show;
            if (show) { visible += 1; }
        });

        Array.prototype.slice.call(list.querySelectorAll('.cimne-fellowship-list__group')).forEach(function (group) {
            var anyVisible = Array.prototype.slice.call(group.querySelectorAll('.cimne-fellowship-list__item'))
                .some(function (item) { return !item.hidden; });
            group.hidden = !anyVisible;
        });

        if (items.length > 0 && visible === 0) {
            var empty = document.createElement('p');
            empty.className = 'cimne-fellowships-empty';
            empty.setAttribute('data-client-filter-empty', '');
            empty.textContent = t('noFellowships', 'No fellowships found.');
            list.prepend(empty);
        }
    }

    function init(component) {
        if (component.getAttribute('data-cimne-fellowships-initialized') === '1') { return; }
        component.setAttribute('data-cimne-fellowships-initialized', '1');

        var listUrl = component.getAttribute('data-list-url') || '';
        var detailUrl = component.getAttribute('data-detail-url') || '';
        var content = component.querySelector('[data-fellowship-content]');
        var notice = component.querySelector('[data-fellowship-notice]');
        var form = document.querySelector('[data-cimne-fellowship-filters]');
        var tabs = Array.prototype.slice.call(component.querySelectorAll('[data-fellowship-status]'));
        var listCache = {};
        var detailCache = {};
        var activeStatus = 'ongoing';
        var appliedFilters = emptyFilters();
        var returnStatus = activeStatus;
        var returnFilters = emptyFilters();

        if (!listUrl || !detailUrl || !content) { return; }

        listCache[serverKey('ongoing', emptyFilters())] = content.innerHTML;

        function setNotice(message, isError) {
            if (!notice) { return; }
            notice.textContent = message || '';
            notice.classList.toggle('is-error', !!isError);
        }

        function setLoading(loading) {
            component.classList.toggle('is-loading', !!loading);
            content.setAttribute('aria-busy', loading ? 'true' : 'false');
        }

        function setTabs(status) {
            tabs.forEach(function (tab) {
                var active = tab.getAttribute('data-fellowship-status') === status;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        function loadList(status, filters) {
            status = status === 'finished' ? 'finished' : 'ongoing';
            filters = cloneFilters(filters);
            var key = serverKey(status, filters);

            if (Object.prototype.hasOwnProperty.call(listCache, key)) {
                return Promise.resolve(listCache[key]);
            }

            setLoading(true);
            setNotice('');

            return requestJson(listUrl, {
                status: status,
                category: filters.category
            }).then(function (data) {
                listCache[key] = data.html || '';
                return listCache[key];
            }).finally(function () {
                setLoading(false);
            });
        }

        function showList(status, filters) {
            status = status === 'finished' ? 'finished' : 'ongoing';
            filters = cloneFilters(filters);

            return loadList(status, filters).then(function (html) {
                activeStatus = status;
                appliedFilters = cloneFilters(filters);
                returnStatus = status;
                returnFilters = cloneFilters(filters);
                content.innerHTML = html;
                applyClientFilters(content, appliedFilters);
                setTabs(status);
                setNotice('');
            }).catch(function () {
                setNotice(t('loadFellowshipsError', 'Unable to load fellowships.'), true);
            });
        }

        function showDetail(code) {
            code = String(code || '').trim();
            if (!code) { return Promise.resolve(); }

            returnStatus = activeStatus;
            returnFilters = cloneFilters(appliedFilters);

            if (Object.prototype.hasOwnProperty.call(detailCache, code)) {
                content.innerHTML = detailCache[code];
                return Promise.resolve();
            }

            setLoading(true);
            setNotice('');

            return requestJson(detailUrl, { fellowship: code }).then(function (data) {
                detailCache[code] = data.html || '';
                content.innerHTML = detailCache[code];
            }).catch(function () {
                setNotice(t('loadFellowshipDetailError', 'Unable to load fellowship details.'), true);
            }).finally(function () {
                setLoading(false);
            });
        }

        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                showList(tab.getAttribute('data-fellowship-status'), appliedFilters);
            });
        });

        content.addEventListener('click', function (event) {
            var open = event.target.closest('[data-open-fellowship]');
            if (open) {
                event.preventDefault();
                showDetail(open.getAttribute('data-open-fellowship'));
                return;
            }

            var back = event.target.closest('[data-fellowship-back]');
            if (back) {
                event.preventDefault();
                showList(returnStatus, returnFilters);
            }
        });

        if (form) {
            var apply = form.querySelector('[data-fellowship-filters-apply]');
            var refresh = form.querySelector('[data-fellowship-filters-refresh]');

            function applyForm() {
                var filters = readFilters(form);
                showList(activeStatus, filters).then(function () {
                    setFilterStatus(form, t('filtersApplied', 'Filters applied.'));
                });
            }

            function clearForm() {
                var filters = emptyFilters();
                writeFilters(form, filters);
                showList(activeStatus, filters).then(function () {
                    setFilterStatus(form, t('filtersCleared', 'Filters cleared.'));
                });
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                applyForm();
            });

            if (apply) {
                apply.addEventListener('click', applyForm);
                apply.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        applyForm();
                    }
                });
            }

            if (refresh) {
                refresh.addEventListener('click', clearForm);
                refresh.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        clearForm();
                    }
                });
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-cimne-fellowships]').forEach(init);
    });
}());
