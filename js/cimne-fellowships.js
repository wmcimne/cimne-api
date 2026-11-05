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

    function parseJsonScript(component, selector) {
        var node = component.querySelector(selector);

        if (!node) {
            return {};
        }

        try {
            var parsed = JSON.parse(node.textContent || '{}');
            return parsed && typeof parsed === 'object' ? parsed : {};
        } catch (error) {
            return {};
        }
    }

    function emptyFilters() {
        return { search: '', category: '', beneficiary: '' };
    }

    function cloneFilters(filters) {
        filters = filters || {};

        return {
            search: String(filters.search || '').trim(),
            category: String(filters.category || '').trim(),
            beneficiary: String(filters.beneficiary || '').trim()
        };
    }

    function readFilters(form) {
        if (!form) {
            return emptyFilters();
        }

        var search = form.querySelector('[data-fellowship-filter-search]');
        var category = form.querySelector('[data-fellowship-filter-category]');
        var beneficiary = form.querySelector('[data-fellowship-filter-beneficiary]');

        return cloneFilters({
            search: search ? search.value : '',
            category: category ? category.value : '',
            beneficiary: beneficiary ? beneficiary.value : ''
        });
    }

    function writeFilters(form, filters) {
        if (!form) {
            return;
        }

        filters = cloneFilters(filters);

        var search = form.querySelector('[data-fellowship-filter-search]');
        var category = form.querySelector('[data-fellowship-filter-category]');
        var beneficiary = form.querySelector('[data-fellowship-filter-beneficiary]');

        if (search) { search.value = filters.search; }
        if (category) { category.value = filters.category; }
        if (beneficiary) { beneficiary.value = filters.beneficiary; }
    }

    function setFilterStatus(form, message) {
        if (!form) {
            return;
        }

        var live = form.querySelector('[data-fellowship-filter-status]');

        if (live) {
            live.textContent = message || '';
        }
    }

    function applyClientFilters(content, filters) {
        var list = content.querySelector('.cimne-fellowship-list');

        if (!list) {
            return;
        }

        filters = cloneFilters(filters);

        var oldEmpty = list.querySelector('[data-client-filter-empty]');
        if (oldEmpty) {
            oldEmpty.remove();
        }

        var search = normalizeText(filters.search);
        var category = filters.category;
        var beneficiary = filters.beneficiary;
        var items = Array.prototype.slice.call(list.querySelectorAll('.cimne-fellowship-list__item'));
        var visible = 0;

        items.forEach(function (item) {
            var haystack = normalizeText(item.getAttribute('data-fellowship-search') || '');
            var itemCategory = item.getAttribute('data-fellowship-category') || '';
            var ids = (item.getAttribute('data-fellowship-beneficiary-ids') || '')
                .split(',')
                .map(function (value) { return value.trim(); })
                .filter(Boolean);

            var matchesSearch = !search || haystack.indexOf(search) !== -1;
            var matchesCategory = !category || itemCategory === category;
            var matchesBeneficiary = !beneficiary || ids.indexOf(beneficiary) !== -1;
            var show = matchesSearch && matchesCategory && matchesBeneficiary;

            item.hidden = !show;

            if (show) {
                visible += 1;
            }
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

    function addKeyboardActivation(control, callback) {
        if (!control) {
            return;
        }

        control.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            callback();
        });
    }

    function init(component) {
        if (component.getAttribute('data-cimne-fellowships-initialized') === '1') {
            return;
        }

        var content = component.querySelector('[data-fellowship-content]');
        var notice = component.querySelector('[data-fellowship-notice]');
        var form = document.querySelector('[data-cimne-fellowship-filters]');
        var tabs = Array.prototype.slice.call(component.querySelectorAll('[data-fellowship-status]'));
        var lists = parseJsonScript(component, '[data-fellowship-preloaded-lists]');
        var details = parseJsonScript(component, '[data-fellowship-preloaded-details]');
        var activeStatus = 'ongoing';
        var appliedFilters = emptyFilters();
        var returnStatus = activeStatus;
        var returnFilters = emptyFilters();

        if (
            !content
            || !Object.prototype.hasOwnProperty.call(lists, 'ongoing')
            || !Object.prototype.hasOwnProperty.call(lists, 'finished')
        ) {
            return;
        }

        component.setAttribute('data-cimne-fellowships-initialized', '1');

        function setNotice(message, isError) {
            if (!notice) {
                return;
            }

            notice.textContent = message || '';
            notice.classList.toggle('is-error', !!isError);
        }

        function setTabs(status) {
            tabs.forEach(function (tab) {
                var active = tab.getAttribute('data-fellowship-status') === status;
                tab.classList.toggle('is-active', active);
                tab.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        function showList(status, filters) {
            status = status === 'finished' ? 'finished' : 'ongoing';
            filters = cloneFilters(filters);

            activeStatus = status;
            appliedFilters = cloneFilters(filters);
            returnStatus = status;
            returnFilters = cloneFilters(filters);
            content.innerHTML = lists[status] || '';
            applyClientFilters(content, appliedFilters);
            setTabs(status);
            setNotice('');

            return Promise.resolve();
        }

        function showDetail(code) {
            code = String(code || '').trim();

            if (!code) {
                return;
            }

            returnStatus = activeStatus;
            returnFilters = cloneFilters(appliedFilters);

            if (!Object.prototype.hasOwnProperty.call(details, code)) {
                setNotice(t('loadFellowshipDetailError', 'Unable to load fellowship details.'), true);
                return;
            }

            content.innerHTML = details[code];
            setNotice('');
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
                addKeyboardActivation(apply, applyForm);
            }

            if (refresh) {
                refresh.addEventListener('click', clearForm);
                addKeyboardActivation(refresh, clearForm);
            }
        }

        applyClientFilters(content, appliedFilters);
        setTabs(activeStatus);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-cimne-fellowships]').forEach(init);
    });
}());
