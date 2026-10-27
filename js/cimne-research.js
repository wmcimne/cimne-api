(function () {
    'use strict';

    var ONGOING_SELECTOR = '#ongoing-projects-item';
    var FINISHED_SELECTOR = '#finished-projects-item';
    var APP_SELECTOR = '#cimne-projects-app, .cimne-projects-app';

    var I18N = window.cimneResearchI18n || {};

    function translate(key, fallback) {
        if (typeof I18N[key] === 'string' && I18N[key] !== '') {
            return I18N[key];
        }

        return fallback;
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text);
        }

        return new Promise(function (resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                resolve();
            } catch (error) {
                reject(error);
            } finally {
                document.body.removeChild(textarea);
            }
        });
    }

    function cleanProjectHash(url, context) {
        var next = new URL(url || window.location.href, window.location.origin);

        if (next.hash.indexOf('#project=') === 0) {
            next.hash = '';
        }

        if (context === 'global' && next.searchParams.get('tab') === 'projects') {
            next.searchParams.delete('tab');
        }

        return next.pathname + next.search + next.hash;
    }

    function buildInlineProjectUrl(code, projectsPageUrl) {
        var base = projectsPageUrl || window.location.href;
        var next = new URL(base, window.location.origin);

        next.searchParams.delete('tab');
        next.hash = '#project=' + encodeURIComponent(code);

        return next;
    }

    function buildPublicProjectUrl(code, context, projectsPageUrl, projectCardUrl) {
        if (context === 'global' && projectCardUrl) {
            var card = new URL(projectCardUrl, window.location.origin);
            card.searchParams.set('project_code', code);
            card.hash = '';
            return card;
        }

        return buildInlineProjectUrl(code, projectsPageUrl);
    }

    function projectFromHash() {
        if (window.location.hash.indexOf('#project=') !== 0) {
            return '';
        }

        try {
            return decodeURIComponent(window.location.hash.substring(9));
        } catch (error) {
            return '';
        }
    }

    function requestJson(url, params) {
        var requestUrl = new URL(url, window.location.origin);

        Object.keys(params).forEach(function (key) {
            if (params[key] !== '') {
                requestUrl.searchParams.set(key, params[key]);
            }
        });

        return fetch(requestUrl.toString(), {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json'
            }
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }

            return response.json();
        });
    }

    function getExternalProjectControls() {
        return {
            ongoing: document.querySelector(ONGOING_SELECTOR),
            finished: document.querySelector(FINISHED_SELECTOR)
        };
    }

    function prepareExternalControl(control, label) {
        if (!control) {
            return;
        }

        control.setAttribute('role', 'button');
        control.setAttribute('tabindex', '0');

        if (!control.getAttribute('aria-label')) {
            control.setAttribute('aria-label', label);
        }
    }

    function setExternalControlsAccessibility(controls, status) {
        if (controls.ongoing) {
            controls.ongoing.setAttribute('aria-pressed', status === 'ongoing' ? 'true' : 'false');
        }

        if (controls.finished) {
            controls.finished.setAttribute('aria-pressed', status === 'finished' ? 'true' : 'false');
        }
    }

    function initialStatusFromExternalControls(controls) {
        if (
            controls.finished &&
            controls.finished.classList.contains('nav-item-active') &&
            (!controls.ongoing || !controls.ongoing.classList.contains('nav-item-active'))
        ) {
            return 'finished';
        }

        return 'ongoing';
    }

    function addKeyboardActivation(control) {
        if (!control || control.getAttribute('data-cimne-keyboard-ready') === '1') {
            return;
        }

        control.setAttribute('data-cimne-keyboard-ready', '1');

        control.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            event.preventDefault();
            control.click();
        });
    }

    function emptyFilters() {
        return {
            search: '',
            category: '',
            unit: '',
            pi: ''
        };
    }

    function normalizeFilters(filters) {
        filters = filters || {};

        return {
            search: String(filters.search || '').trim(),
            category: String(filters.category || '').trim(),
            unit: String(filters.unit || '').trim(),
            pi: String(filters.pi || '').trim()
        };
    }

    function cloneFilters(filters) {
        var normalized = normalizeFilters(filters);

        return {
            search: normalized.search,
            category: normalized.category,
            unit: normalized.unit,
            pi: normalized.pi
        };
    }

    function serverListCacheKey(status, filters) {
        filters = normalizeFilters(filters);

        return [
            status === 'finished' ? 'finished' : 'ongoing',
            filters.category,
            filters.unit
        ].join('|');
    }

    function normalizeSearchText(value) {
        var text = String(value || '').toLocaleLowerCase();

        if (typeof text.normalize === 'function') {
            text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        }

        return text;
    }

    function getFilterForm(component, context) {
        if (context !== 'global') {
            return null;
        }

        var app = component.closest(APP_SELECTOR);

        if (!app) {
            return null;
        }

        return app.querySelector('[data-cimne-project-filters]');
    }

    function readFilterForm(form) {
        if (!form) {
            return emptyFilters();
        }

        var search = form.querySelector('[data-project-filter-search]');
        var category = form.querySelector('[data-project-filter-category]');
        var unit = form.querySelector('[data-project-filter-unit]');
        var pi = form.querySelector('[data-project-filter-pi]');

        return normalizeFilters({
            search: search ? search.value : '',
            category: category ? category.value : '',
            unit: unit ? unit.value : '',
            pi: pi ? pi.value : ''
        });
    }

    function writeFilterForm(form, filters) {
        if (!form) {
            return;
        }

        filters = normalizeFilters(filters);

        var search = form.querySelector('[data-project-filter-search]');
        var category = form.querySelector('[data-project-filter-category]');
        var unit = form.querySelector('[data-project-filter-unit]');
        var pi = form.querySelector('[data-project-filter-pi]');

        if (search) {
            search.value = filters.search;
        }
        if (category) {
            category.value = filters.category;
        }
        if (unit) {
            unit.value = filters.unit;
        }
        if (pi) {
            pi.value = filters.pi;
        }
    }

    function setFilterStatus(form, message) {
        if (!form) {
            return;
        }

        var live = form.querySelector('[data-project-filter-status]');

        if (live) {
            live.textContent = message || '';
        }
    }

    function applyClientFilters(content, filters) {
        var list = content.querySelector('.cimne-project-list');

        if (!list) {
            return;
        }

        filters = normalizeFilters(filters);

        var existingClientEmpty = list.querySelector('[data-client-filter-empty]');
        if (existingClientEmpty) {
            existingClientEmpty.remove();
        }

        var items = Array.prototype.slice.call(list.querySelectorAll('.cimne-project-list__item'));
        var searchNeedle = normalizeSearchText(filters.search);
        var selectedPi = filters.pi;
        var visibleCount = 0;

        items.forEach(function (item) {
            var searchable = normalizeSearchText(item.getAttribute('data-project-search') || '');
            var piIds = (item.getAttribute('data-project-pi-ids') || '')
                .split(',')
                .map(function (value) { return value.trim(); })
                .filter(Boolean);

            var matchesSearch = !searchNeedle || searchable.indexOf(searchNeedle) !== -1;
            var matchesPi = !selectedPi || piIds.indexOf(selectedPi) !== -1;
            var visible = matchesSearch && matchesPi;

            /*
             * Do not rely on the HTML hidden attribute here. Project cards have
             * display:grid in the theme stylesheet, and author CSS can override
             * the browser's default rendering of [hidden]. A dedicated class
             * gives the client-side filters an explicit, reliable display rule.
             */
            item.classList.toggle('is-client-filter-hidden', !visible);
            item.removeAttribute('hidden');
            item.setAttribute('aria-hidden', visible ? 'false' : 'true');

            if (visible) {
                visibleCount += 1;
            }
        });

        Array.prototype.slice.call(list.querySelectorAll('.cimne-project-list__group')).forEach(function (group) {
            var hasVisibleItems = Array.prototype.slice
                .call(group.querySelectorAll('.cimne-project-list__item'))
                .some(function (item) {
                    return !item.classList.contains('is-client-filter-hidden');
                });

            group.classList.toggle('is-client-filter-hidden', !hasVisibleItems);
            group.removeAttribute('hidden');
            group.setAttribute('aria-hidden', hasVisibleItems ? 'false' : 'true');
        });

        if (items.length > 0 && visibleCount === 0) {
            var empty = document.createElement('p');
            empty.className = 'cimne-research-empty';
            empty.setAttribute('data-client-filter-empty', '');
            empty.textContent = translate('noProjects', 'No projects found.');
            list.prepend(empty);
        }
    }

    function initProjects(component) {
        var context = component.getAttribute('data-project-context') === 'global' ? 'global' : 'research';
        var unit = component.getAttribute('data-unit-code') || '';
        var listUrl = component.getAttribute('data-list-url') || '';
        var detailUrl = component.getAttribute('data-detail-url') || '';
        var projectsPageUrl = component.getAttribute('data-projects-page-url') || '';
        var projectCardUrl = component.getAttribute('data-project-card-url') || '';
        var content = component.querySelector('[data-project-content]');
        var notice = component.querySelector('[data-project-notice]');
        var controls = getExternalProjectControls();
        var filterForm = getFilterForm(component, context);
        var appliedFilters = emptyFilters();
        var returnFilters = emptyFilters();
        var initialStatus = initialStatusFromExternalControls(controls);
        var listCache = {};
        var detailCache = {};
        var activeStatus = initialStatus;
        var returnStatus = activeStatus;

        if (!listUrl || !detailUrl || !projectsPageUrl || !content || (context === 'research' && !unit)) {
            return;
        }

        if (component.getAttribute('data-cimne-projects-initialized') === '1') {
            return;
        }

        component.setAttribute('data-cimne-projects-initialized', '1');
        listCache[serverListCacheKey('ongoing', emptyFilters())] = content.innerHTML;

        prepareExternalControl(controls.ongoing, translate('ongoingProjects', 'Ongoing projects'));
        prepareExternalControl(controls.finished, translate('finishedProjects', 'Finished projects'));
        addKeyboardActivation(controls.ongoing);
        addKeyboardActivation(controls.finished);
        setExternalControlsAccessibility(controls, activeStatus);

        function setNotice(message, isError) {
            if (!notice) {
                return;
            }

            notice.textContent = message || '';
            notice.classList.toggle('is-error', !!isError);
        }

        function setLoading(loading) {
            component.classList.toggle('is-loading', !!loading);
            content.setAttribute('aria-busy', loading ? 'true' : 'false');
        }

        function loadList(status, filters) {
            status = status === 'finished' ? 'finished' : 'ongoing';
            filters = context === 'global' ? normalizeFilters(filters) : emptyFilters();

            var cacheKey = serverListCacheKey(status, filters);

            if (Object.prototype.hasOwnProperty.call(listCache, cacheKey)) {
                return Promise.resolve(listCache[cacheKey]);
            }

            setLoading(true);
            setNotice('');

            var params = {
                context: context,
                status: status
            };

            if (context === 'research') {
                params.unit = unit;
            } else {
                params.category = filters.category;
                params.filter_unit = filters.unit;
            }

            return requestJson(listUrl, params).then(function (data) {
                listCache[cacheKey] = data.html || '';
                return listCache[cacheKey];
            }).finally(function () {
                setLoading(false);
            });
        }

        function showList(status, historyMode, filters, syncForm) {
            status = status === 'finished' ? 'finished' : 'ongoing';
            filters = context === 'global' ? normalizeFilters(filters) : emptyFilters();

            return loadList(status, filters).then(function (html) {
                activeStatus = status;
                returnStatus = status;
                appliedFilters = cloneFilters(filters);
                returnFilters = cloneFilters(filters);
                content.innerHTML = html;
                applyClientFilters(content, appliedFilters);
                setNotice('');
                setExternalControlsAccessibility(controls, status);

                if (syncForm && filterForm) {
                    writeFilterForm(filterForm, appliedFilters);
                }

                if (historyMode === 'replace') {
                    window.history.replaceState(
                        {
                            cimneResearchProjects: true,
                            view: 'list',
                            status: status,
                            filters: cloneFilters(appliedFilters)
                        },
                        '',
                        cleanProjectHash(window.location.href, context)
                    );
                }
            }).catch(function () {
                setNotice(translate('loadProjectsError', 'Unable to load projects.'), true);
            });
        }

        function loadDetail(code) {
            if (Object.prototype.hasOwnProperty.call(detailCache, code)) {
                return Promise.resolve(detailCache[code]);
            }

            setLoading(true);
            setNotice('');

            var params = {
                context: context,
                project: code
            };

            if (context === 'research') {
                params.unit = unit;
            }

            return requestJson(detailUrl, params).then(function (data) {
                detailCache[code] = data.html || '';
                return detailCache[code];
            }).finally(function () {
                setLoading(false);
            });
        }

        function showDetail(code, pushHistory, restoredReturnStatus, restoredFilters) {
            if (!code) {
                return;
            }

            if (restoredReturnStatus) {
                returnStatus = restoredReturnStatus;
            } else {
                returnStatus = activeStatus;
            }

            if (restoredFilters) {
                returnFilters = cloneFilters(restoredFilters);
            } else {
                returnFilters = cloneFilters(appliedFilters);
            }

            loadDetail(code).then(function (html) {
                content.innerHTML = html;
                setNotice('');
                setExternalControlsAccessibility(controls, returnStatus);

                if (pushHistory) {
                    var historyUrl;

                    if (context === 'global') {
                        var projectUrl = buildInlineProjectUrl(code, projectsPageUrl);
                        historyUrl = projectUrl.pathname + projectUrl.search + projectUrl.hash;
                    } else {
                        historyUrl = cleanProjectHash(window.location.href, context);
                    }

                    window.history.pushState(
                        {
                            cimneResearchProjects: true,
                            view: 'detail',
                            project: code,
                            returnStatus: returnStatus,
                            returnFilters: cloneFilters(returnFilters)
                        },
                        '',
                        historyUrl
                    );
                }
            }).catch(function () {
                setNotice(translate('loadProjectDetailError', 'Unable to load project details.'), true);
            });
        }

        function onOngoingClick() {
            showList('ongoing', 'replace', appliedFilters, false);
        }

        function onFinishedClick() {
            showList('finished', 'replace', appliedFilters, false);
        }

        if (controls.ongoing) {
            controls.ongoing.addEventListener('click', onOngoingClick);
        }

        if (controls.finished) {
            controls.finished.addEventListener('click', onFinishedClick);
        }

        if (filterForm) {
            var applyButton = filterForm.querySelector('[data-project-filters-apply]');
            var refreshButton = filterForm.querySelector('[data-project-filters-refresh]');

            function applyFiltersFromForm() {
                var nextFilters = readFilterForm(filterForm);

                return showList(activeStatus, 'replace', nextFilters, false).then(function () {
                    setFilterStatus(filterForm, translate('filtersApplied', 'Filters applied.'));
                });
            }

            function refreshFilters() {
                filterForm.reset();

                var resetFilters = emptyFilters();

                return showList(activeStatus, 'replace', resetFilters, true).then(function () {
                    setFilterStatus(filterForm, translate('filtersCleared', 'Filters cleared.'));
                });
            }

            filterForm.addEventListener('submit', function (event) {
                event.preventDefault();
                applyFiltersFromForm();
            });

            if (applyButton) {
                addKeyboardActivation(applyButton);
                applyButton.addEventListener('click', function () {
                    applyFiltersFromForm();
                });
            }

            if (refreshButton) {
                addKeyboardActivation(refreshButton);
                refreshButton.addEventListener('click', function () {
                    refreshFilters();
                });
            }
        }

        component.addEventListener('click', function (event) {
            var openButton = event.target.closest('[data-open-project]');
            var backButton = event.target.closest('[data-project-back]');
            var copyUrlButton = event.target.closest('[data-copy-project-url]');

            if (openButton && component.contains(openButton)) {
                event.preventDefault();
                showDetail(openButton.getAttribute('data-open-project'), true);
                return;
            }

            if (backButton && component.contains(backButton)) {
                event.preventDefault();
                showList(returnStatus, 'replace', returnFilters, true);
                return;
            }

            if (copyUrlButton && component.contains(copyUrlButton)) {
                event.preventDefault();

                var code = copyUrlButton.getAttribute('data-copy-project-url') || '';
                var shareUrl = buildPublicProjectUrl(code, context, projectsPageUrl, projectCardUrl);

                copyText(shareUrl.toString()).then(function () {
                    var status = component.querySelector('[data-cimne-project-copy-status]');
                    if (status) {
                        status.textContent = translate('projectUrlCopied', 'Project URL copied.');
                    }
                });
            }
        });

        var initialProject = projectFromHash();

        if (initialProject) {
            window.history.replaceState(
                {
                    cimneResearchProjects: true,
                    view: 'detail',
                    project: initialProject,
                    returnStatus: activeStatus,
                    returnFilters: cloneFilters(appliedFilters)
                },
                '',
                window.location.href
            );
            showDetail(initialProject, false, activeStatus, appliedFilters);
        } else if (activeStatus === 'finished') {
            showList('finished', 'replace', appliedFilters, false);
        } else {
            applyClientFilters(content, appliedFilters);
            window.history.replaceState(
                {
                    cimneResearchProjects: true,
                    view: 'list',
                    status: 'ongoing',
                    filters: cloneFilters(appliedFilters)
                },
                '',
                window.location.href
            );
        }

        window.addEventListener('popstate', function (event) {
            var state = event.state || {};
            var code = projectFromHash();

            if (state.view === 'detail' && state.project) {
                if (filterForm && state.returnFilters) {
                    writeFilterForm(filterForm, state.returnFilters);
                }

                showDetail(
                    state.project,
                    false,
                    state.returnStatus || returnStatus,
                    state.returnFilters || returnFilters
                );
                return;
            }

            if (code) {
                showDetail(
                    code,
                    false,
                    state.returnStatus || returnStatus,
                    state.returnFilters || returnFilters
                );
                return;
            }

            var stateFilters = state.filters || emptyFilters();

            if (filterForm) {
                writeFilterForm(filterForm, stateFilters);
            }

            showList(state.status || 'ongoing', null, stateFilters, false);
        });
    }

    document.addEventListener('click', function (event) {
        var cardCopyButton = event.target.closest('[data-copy-project-card-url]');
        var cardBackButton = event.target.closest('[data-project-card-back-url]');

        if (cardCopyButton) {
            event.preventDefault();

            var cardUrl = cardCopyButton.getAttribute('data-copy-project-card-url') || window.location.href;

            copyText(cardUrl).then(function () {
                var detail = cardCopyButton.closest('.cimne-project-detail');
                var status = detail ? detail.querySelector('[data-cimne-project-copy-status]') : null;

                if (status) {
                    status.textContent = translate('projectUrlCopied', 'Project URL copied.');
                }
            });

            return;
        }

        if (cardBackButton) {
            event.preventDefault();

            var backUrl = cardBackButton.getAttribute('data-project-card-back-url') || '';

            if (backUrl) {
                window.location.assign(backUrl);
            }

            return;
        }


    });

    document.querySelectorAll('[data-cimne-projects]').forEach(initProjects);
})();
