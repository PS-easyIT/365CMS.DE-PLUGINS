(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function hasMeaningfulQueryParams() {
        var params = new URLSearchParams(window.location.search || '');
        if (!params || params.toString() === '') {
            return false;
        }

        var ignored = {
            utm_source: true,
            utm_medium: true,
            utm_campaign: true,
            utm_term: true,
            utm_content: true,
            gclid: true,
            fbclid: true,
            msclkid: true,
            ref: true
        };

        var meaningful = false;
        params.forEach(function (value, key) {
            if (meaningful) {
                return;
            }

            var normalizedKey = String(key || '').trim().toLowerCase();
            if (!normalizedKey || ignored[normalizedKey]) {
                return;
            }

            meaningful = true;
        });

        return meaningful;
    }

    function initDetailInitialScrollPosition() {
        var root = document.querySelector('.m365calc-page');
        if (!root || window.location.hash || hasMeaningfulQueryParams()) {
            return;
        }

        if ('scrollRestoration' in window.history) {
            var originalScrollRestoration = window.history.scrollRestoration;
            window.history.scrollRestoration = 'manual';
            window.addEventListener('pagehide', function () {
                window.history.scrollRestoration = originalScrollRestoration;
            }, { once: true });
        }

        root.scrollIntoView({ behavior: 'auto', block: 'start' });
    }

    function initResultFocus() {
        if (document.querySelector('.m365calc-readonly-page, .m365calc-price-tracker-page')) {
            return;
        }

        if (!hasMeaningfulQueryParams() && window.location.hash !== '#m365calc-result') {
            return;
        }

        var result = document.querySelector('[data-m365calc-result]');
        if (!result) {
            return;
        }

        result.setAttribute('tabindex', '-1');
        result.focus({ preventScroll: true });
        result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function initReadonlyMatrixScrollPosition() {
        if (!document.querySelector('.m365calc-readonly-page') || window.location.hash) {
            return;
        }

        if ('scrollRestoration' in window.history) {
            var originalScrollRestoration = window.history.scrollRestoration;
            window.history.scrollRestoration = 'manual';
            window.addEventListener('pagehide', function () {
                window.history.scrollRestoration = originalScrollRestoration;
            }, { once: true });
        }

        window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    }

    function initPrintButtons() {
        document.querySelectorAll('[data-m365calc-print]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.print();
            });
        });
    }

    function initResetButtons() {
        document.querySelectorAll('[data-m365calc-reset]').forEach(function (button) {
            button.addEventListener('click', function () {
                var form = button.closest('form');
                if (!form) {
                    return;
                }

                form.reset();
            });
        });
    }

    function normalizePublicPath(value) {
        var path = String(value || '').trim();
        if (!path) {
            return '';
        }

        path = path.replace(/^[a-z]+:\/\/[^/]+/i, '');
        path = path.split('#')[0].split('?')[0].replace(/\/+/g, '/').replace(/^\/+|\/+$/g, '').toLowerCase();

        return path.indexOf('en/') === 0 ? path.slice(3) : path;
    }

    function isDisabledModulePath(path, disabledRoutes) {
        var normalized = normalizePublicPath(path);
        if (!normalized) {
            return false;
        }

        return disabledRoutes.some(function (route) {
            var disabled = normalizePublicPath(route);

            return disabled && (normalized === disabled || normalized.endsWith('/' + disabled));
        });
    }

    function initHiddenModuleActionLinks() {
        var config = window.M365ToolsPublicVisibility || {};
        var disabledRoutes = Array.isArray(config.disabledRoutes) ? config.disabledRoutes : [];
        if (disabledRoutes.length === 0) {
            return;
        }

        document.querySelectorAll('.m365calc-page .m365calc-actions a[href], .m365calc-provider-cta .m365calc-actions a[href]').forEach(function (link) {
            var url;
            try {
                url = new URL(link.getAttribute('href'), window.location.origin);
            } catch (error) {
                return;
            }

            if (!isDisabledModulePath(url.pathname, disabledRoutes)) {
                return;
            }

            var actions = link.closest('.m365calc-actions');
            link.remove();

            if (actions && !actions.querySelector('a, button, input, select, textarea')) {
                actions.remove();
            }
        });
    }

    function initContentHost() {
        var root = document.querySelector('.m365calc-page, #m365calculator-landing');
        if (!root) {
            return;
        }

        var host = root.closest('#content, .site-content');
        if (!host) {
            return;
        }

        host.classList.add('m365tools-content-host');

        var header = document.getElementById('masthead');
        if (!header || !header.parentElement || header.parentElement !== host.parentElement) {
            return;
        }

        var node = header.nextElementSibling;
        while (node && node !== host) {
            if (!node.matches('.mobile-menu-overlay, .mobile-menu-drawer, .search-overlay')) {
                node.classList.add('m365tools-header-interstitial');
                node.setAttribute('aria-hidden', 'true');
            }
            node = node.nextElementSibling;
        }
    }

    function initLicenseAuditChecklist() {
        document.querySelectorAll('[data-m365calc-audit]').forEach(function (root) {
            var key = root.getAttribute('data-m365calc-audit-key') || 'm365calc-license-audit';
            var items = Array.prototype.slice.call(root.querySelectorAll('[data-m365calc-audit-item]'));
            var total = items.length;
            var percentNode = root.querySelector('[data-m365calc-audit-percent]');
            var doneNode = root.querySelector('[data-m365calc-audit-done]');
            var totalNode = root.querySelector('[data-m365calc-audit-total]');
            var barNode = root.querySelector('[data-m365calc-audit-bar]');
            var openList = root.querySelector('[data-m365calc-audit-open-list]');
            var doneList = root.querySelector('[data-m365calc-audit-done-list]');
            var printPercentNode = root.querySelector('[data-m365calc-audit-print-percent]');
            var printDoneNode = root.querySelector('[data-m365calc-audit-print-done]');
            var resetButton = root.querySelector('[data-m365calc-audit-reset]');

            function loadState() {
                try {
                    return JSON.parse(window.localStorage.getItem(key) || '{}') || {};
                } catch (error) {
                    return {};
                }
            }

            function saveState(state) {
                try {
                    window.localStorage.setItem(key, JSON.stringify(state));
                } catch (error) {
                    return;
                }
            }

            function createListItem(text) {
                var item = document.createElement('li');
                item.textContent = text;

                return item;
            }

            function updateLists(doneItems, openItems) {
                if (doneList) {
                    doneList.replaceChildren();
                    if (doneItems.length === 0) {
                        doneList.appendChild(createListItem('Noch keine Punkte bestätigt.'));
                    } else {
                        doneItems.slice(0, 8).forEach(function (item) {
                            doneList.appendChild(createListItem(item.getAttribute('data-m365calc-audit-summary-done') || item.closest('label').textContent.trim()));
                        });
                    }
                }

                if (openList) {
                    openList.replaceChildren();
                    if (openItems.length === 0) {
                        openList.appendChild(createListItem('Alle sichtbaren Prüfpunkte sind bestätigt.'));
                    } else {
                        openItems.slice(0, 8).forEach(function (item) {
                            openList.appendChild(createListItem(item.getAttribute('data-m365calc-audit-summary-open') || item.closest('label').textContent.trim()));
                        });
                    }
                }
            }

            function updateProgress() {
                var doneItems = items.filter(function (item) {
                    return item.checked;
                });
                var openItems = items.filter(function (item) {
                    return !item.checked;
                });
                var done = doneItems.length;
                var percent = total > 0 ? Math.round((done / total) * 100) : 0;

                if (percentNode) {
                    percentNode.textContent = String(percent);
                }
                if (doneNode) {
                    doneNode.textContent = String(done);
                }
                if (totalNode) {
                    totalNode.textContent = String(total);
                }
                if (printPercentNode) {
                    printPercentNode.textContent = String(percent);
                }
                if (printDoneNode) {
                    printDoneNode.textContent = String(done);
                }
                if (barNode) {
                    barNode.style.setProperty('--m365calc-audit-progress', percent + '%');
                }

                updateLists(doneItems, openItems);
            }

            var state = loadState();
            items.forEach(function (item) {
                var id = item.getAttribute('data-m365calc-audit-id') || item.id;
                if (Object.prototype.hasOwnProperty.call(state, id)) {
                    item.checked = state[id] === true;
                }

                item.addEventListener('change', function () {
                    state[id] = item.checked;
                    saveState(state);
                    updateProgress();
                });
            });

            if (resetButton) {
                resetButton.addEventListener('click', function () {
                    state = {};
                    items.forEach(function (item) {
                        item.checked = false;
                    });
                    saveState(state);
                    updateProgress();
                });
            }

            updateProgress();
        });
    }

    function initLicenseComparisonColumns() {
        var root = document.querySelector('[data-m365calc-license-comparison]');
        if (!root) {
            return;
        }

        var dataNode = root.querySelector('[data-m365calc-license-comparison-data]');
        if (!dataNode) {
            return;
        }

        var data;
        try {
            data = JSON.parse(dataNode.textContent || '{}');
        } catch (error) {
            return;
        }

        var selects = Array.prototype.slice.call(root.querySelectorAll('[data-m365calc-license-select]'));
        var summary = root.querySelector('[data-m365calc-license-summary]');
        var empty = root.querySelector('[data-m365calc-license-empty]');
        var tableWrap = root.querySelector('[data-m365calc-license-table]');
        var tableHead = root.querySelector('[data-m365calc-license-head]');
        var tableBody = root.querySelector('[data-m365calc-license-body]');
        var countNode = root.querySelector('[data-m365calc-license-count]');
        var plans = Array.isArray(data.plans) ? data.plans : [];
        var groups = Array.isArray(data.groups) ? data.groups : [];
        var counts = data.counts || {};
        var planMap = Object.create(null);

        if (selects.length === 0 || plans.length === 0) {
            return;
        }

        plans.forEach(function (plan) {
            if (plan && plan.slug) {
                planMap[String(plan.slug)] = plan;
            }
        });

        function setHidden(node, hidden) {
            if (!node) {
                return;
            }

            node.hidden = hidden;
        }

        function textElement(tagName, className, text) {
            var node = document.createElement(tagName);
            if (className) {
                node.className = className;
            }
            node.textContent = String(text || '');

            return node;
        }

        function cleanStatusClass(status) {
            return String(status || 'unknown').replace(/[^a-z0-9_-]+/gi, '-').toLowerCase();
        }

        function releaseDuplicateSelection(activeSelect) {
            if (!activeSelect || !activeSelect.value) {
                return;
            }

            selects.forEach(function (select) {
                if (select !== activeSelect && select.value === activeSelect.value) {
                    select.value = '';
                }
            });
        }

        function selectedValues(removeDuplicates) {
            var seen = Object.create(null);
            var values = [];

            selects.forEach(function (select) {
                var value = String(select.value || '');
                if (!value) {
                    return;
                }

                if (seen[value]) {
                    if (removeDuplicates) {
                        select.value = '';
                    }
                    return;
                }

                if (planMap[value]) {
                    seen[value] = true;
                    values.push(value);
                }
            });

            return values;
        }

        function updateDisabledOptions(values) {
            selects.forEach(function (select) {
                Array.prototype.slice.call(select.options).forEach(function (option) {
                    option.disabled = false;
                });
            });
        }

        function renderCount(selectedCount) {
            if (!countNode) {
                return;
            }

            countNode.textContent = String(counts.filtered || 0)
                + ' von ' + String(counts.all || 0)
                + ' Plänen passen zu den aktuellen Filtern. '
                + String(selectedCount)
                + (selectedCount === 1 ? ' Spalte ist ausgewählt.' : ' Spalten sind ausgewählt.');
        }

        function renderSummary(selectedPlans) {
            if (!summary) {
                return;
            }

            summary.replaceChildren();
            if (selectedPlans.length === 0) {
                return;
            }

            var cheapest = selectedPlans.reduce(function (current, plan) {
                if (!current || Number(plan.price_month || 0) < Number(current.price_month || 0)) {
                    return plan;
                }

                return current;
            }, null);

            selectedPlans.forEach(function (plan) {
                var card = document.createElement('article');
                card.className = 'phinit-card m365calc-mini-card' + (cheapest && plan.slug === cheapest.slug ? ' phinit-card--success' : '');
                card.appendChild(textElement('span', '', cheapest && plan.slug === cheapest.slug ? 'Günstigster Einstieg' : plan.family));
                card.appendChild(textElement('strong', '', plan.name));
                card.appendChild(textElement('p', '', String(plan.price_text || '0,00 €') + ' / User / Monat'));
                summary.appendChild(card);
            });
        }

        function renderTable(selectedPlans) {
            if (!tableHead || !tableBody) {
                return;
            }

            var headRow = document.createElement('tr');
            var featureHead = document.createElement('th');
            featureHead.scope = 'col';
            featureHead.textContent = 'Funktion';
            headRow.appendChild(featureHead);

            selectedPlans.forEach(function (plan) {
                var headCell = document.createElement('th');
                headCell.scope = 'col';
                headCell.appendChild(textElement('span', 'm365calc-plan-heading', plan.name));
                headCell.appendChild(textElement('span', '', plan.price_text));
                headRow.appendChild(headCell);
            });

            tableHead.replaceChildren(headRow);
            tableBody.replaceChildren();

            groups.forEach(function (group) {
                var groupRow = document.createElement('tr');
                var groupHead = document.createElement('th');
                groupRow.className = 'm365calc-compare-table__group';
                groupHead.scope = 'row';
                groupHead.colSpan = selectedPlans.length + 1;
                groupHead.textContent = String(group.label || group.key || 'Features');
                groupRow.appendChild(groupHead);
                tableBody.appendChild(groupRow);

                (Array.isArray(group.features) ? group.features : []).forEach(function (feature) {
                    var row = document.createElement('tr');
                    var statuses = Object.create(null);
                    var rowHead = document.createElement('th');
                    rowHead.scope = 'row';
                    rowHead.appendChild(textElement('span', 'm365calc-feature-title', feature.label));
                    rowHead.appendChild(textElement('small', '', feature.description));
                    row.appendChild(rowHead);

                    selectedPlans.forEach(function (plan) {
                        var status = plan.statuses && plan.statuses[feature.key] ? plan.statuses[feature.key] : { status: 'unknown', label: '—', note: '' };
                        statuses[String(status.status || 'unknown')] = true;

                        var cell = document.createElement('td');
                        var statusNode = document.createElement('span');
                        statusNode.className = 'm365calc-status m365calc-status--' + cleanStatusClass(status.status);
                        statusNode.appendChild(textElement('strong', '', status.label));
                        statusNode.appendChild(textElement('small', '', status.note));
                        cell.appendChild(statusNode);
                        row.appendChild(cell);
                    });

                    if (Object.keys(statuses).length > 1) {
                        row.className = 'm365calc-compare-table__diff';
                    }

                    tableBody.appendChild(row);
                });
            });
        }

        function render(removeDuplicates) {
            var values = selectedValues(removeDuplicates);
            var selectedPlans = values.map(function (value) {
                return planMap[value];
            }).filter(Boolean);
            var hasSelection = selectedPlans.length > 0;

            updateDisabledOptions(values);
            renderCount(selectedPlans.length);
            setHidden(empty, hasSelection);
            setHidden(summary, !hasSelection);
            setHidden(tableWrap, !hasSelection);

            if (!hasSelection) {
                if (summary) {
                    summary.replaceChildren();
                }
                return;
            }

            renderSummary(selectedPlans);
            renderTable(selectedPlans);
        }

        selects.forEach(function (select) {
            select.addEventListener('change', function () {
                releaseDuplicateSelection(select);
                render(true);
            });
        });

        render(true);
    }

    function initMicrosoftPriceTrackerCharts() {
        var root = document.querySelector('[data-m365calc-price-tracker]');
        if (!root) {
            return;
        }

        var dataNode = root.querySelector('[data-m365calc-price-tracker-data]');
        if (!dataNode) {
            return;
        }

        var data;
        try {
            data = JSON.parse(dataNode.textContent || '{}');
        } catch (error) {
            return;
        }

        var licenses = Array.isArray(data.licenses) ? data.licenses : [];
        var dates = Array.isArray(data.dates) ? data.dates : [];
        var storageKey = String(data.storage_key || 'm365tools-price-tracker-entries-v1');
        var licenseMap = Object.create(null);
        var priceFilter = root.querySelector('[data-m365calc-price-history-filter]');
        var historyCanvas = root.querySelector('[data-m365calc-price-history-chart]');
        var historyHint = root.querySelector('[data-m365calc-price-history-hint]');
        var personalRoot = root.querySelector('[data-m365calc-personal-price-tracker]');
        var historyChart = null;
        var personalChart = null;
        var maxVisibleLicenses = 5;
        var defaultVisibleSlugs = Array.isArray(data.default_slugs) ? data.default_slugs.map(String) : [];
        var visibleHistorySlugs = [];
        var palette = ['#1f4e79', '#c07a1f', '#2f7d5a', '#7c3aed', '#b42318', '#2563eb', '#64748b', '#0f766e', '#9333ea', '#ea580c'];

        licenses.forEach(function (license) {
            if (license && license.slug) {
                licenseMap[String(license.slug)] = license;
            }
        });

        if (licenses.length === 0 || dates.length === 0) {
            return;
        }

        visibleHistorySlugs = defaultVisibleSlugs.filter(function (slug) {
            return licenseMap[slug];
        }).slice(0, maxVisibleLicenses);
        if (visibleHistorySlugs.length === 0) {
            visibleHistorySlugs = licenses.slice(0, Math.min(4, maxVisibleLicenses)).map(function (license) {
                return String(license.slug || '');
            }).filter(Boolean);
        }

        function chartReady() {
            if (window.Chart) {
                return Promise.resolve(window.Chart);
            }

            return Promise.reject(new Error('Chart.js not available locally'));
        }

        function formatMoney(value) {
            return new Intl.NumberFormat('de-DE', { style: 'currency', currency: 'EUR' }).format(Number(value || 0));
        }

        function formatPercent(value) {
            return new Intl.NumberFormat('de-DE', { minimumFractionDigits: 1, maximumFractionDigits: 1 }).format(Number(value || 0)) + ' %';
        }

        function pad2(value) {
            return String(value).padStart(2, '0');
        }

        function dateParts(value) {
            var match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (!match) {
                return null;
            }

            return { year: Number(match[1]), month: Number(match[2]), day: Number(match[3]) };
        }

        function quarterStartDate(value) {
            var parts = dateParts(value);
            if (!parts) {
                return '';
            }

            var month = Math.floor((parts.month - 1) / 3) * 3 + 1;

            return String(parts.year) + '-' + pad2(month) + '-01';
        }

        function addQuarter(value) {
            var parts = dateParts(value);
            if (!parts) {
                return '';
            }

            var monthIndex = parts.month - 1 + 3;
            var year = parts.year + Math.floor(monthIndex / 12);
            var month = (monthIndex % 12) + 1;

            return String(year) + '-' + pad2(month) + '-01';
        }

        function formatQuarter(value) {
            var parts = dateParts(value);
            if (!parts) {
                return String(value || '');
            }

            return 'Q' + String(Math.floor((parts.month - 1) / 3) + 1) + ' ' + String(parts.year);
        }

        function buildQuarterTimeline(sourceDates) {
            var normalized = (Array.isArray(sourceDates) ? sourceDates : []).map(quarterStartDate).filter(Boolean).sort();
            if (normalized.length === 0) {
                return [];
            }

            var start = normalized[0];
            var end = normalized[normalized.length - 1];
            var timeline = [];
            var cursor = start;
            var guard = 0;

            while (cursor && cursor <= end && guard < 120) {
                timeline.push(cursor);
                cursor = addQuarter(cursor);
                guard += 1;
            }

            return timeline;
        }

        function yScaleOptions(values) {
            var numericValues = (Array.isArray(values) ? values : []).filter(function (value) {
                return typeof value === 'number' && Number.isFinite(value);
            });
            if (numericValues.length === 0) {
                numericValues = [0, 1];
            }

            var min = Math.min.apply(null, numericValues);
            var max = Math.max.apply(null, numericValues);
            var range = Math.max(0.01, max - min);
            var padding = Math.max(0.5, range * 0.12);

            return {
                beginAtZero: false,
                suggestedMin: Math.max(0, min - padding),
                suggestedMax: max + padding,
                ticks: {
                    callback: function (value) {
                        return formatMoney(value);
                    }
                }
            };
        }

        function createText(tagName, className, text) {
            var node = document.createElement(tagName);
            if (className) {
                node.className = className;
            }
            node.textContent = String(text || '');

            return node;
        }

        function renderChartFallback(canvas, caption, headers, rows) {
            if (!canvas) {
                return;
            }

            var existing = canvas.parentNode ? canvas.parentNode.querySelector('[data-m365calc-chart-fallback]') : null;
            if (existing) {
                existing.remove();
            }

            canvas.hidden = true;

            var wrap = document.createElement('section');
            wrap.className = 'phinit-table-wrap m365calc-chart-fallback';
            wrap.setAttribute('data-m365calc-chart-fallback', 'true');
            wrap.setAttribute('aria-label', caption);

            var table = document.createElement('table');
            table.className = 'phinit-table';
            var thead = document.createElement('thead');
            var headRow = document.createElement('tr');
            headers.forEach(function (label) {
                var th = document.createElement('th');
                th.scope = 'col';
                th.textContent = String(label || '');
                headRow.appendChild(th);
            });
            thead.appendChild(headRow);
            table.appendChild(thead);

            var tbody = document.createElement('tbody');
            rows.forEach(function (rowValues) {
                var tr = document.createElement('tr');
                rowValues.forEach(function (value, index) {
                    var cell = document.createElement(index === 0 ? 'th' : 'td');
                    if (index === 0) {
                        cell.scope = 'row';
                    }
                    cell.textContent = String(value || '');
                    tr.appendChild(cell);
                });
                tbody.appendChild(tr);
            });
            table.appendChild(tbody);
            wrap.appendChild(table);
            canvas.parentNode.appendChild(wrap);
        }

        function priceAtDate(history, date) {
            var price = null;
            var firstKnown = null;
            (Array.isArray(history) ? history : []).forEach(function (entry) {
                var entryDate = String(entry.date || '');
                var entryPrice = Number(entry.price || 0);
                if (!entryDate || entryPrice <= 0) {
                    return;
                }
                if (firstKnown === null) {
                    firstKnown = entryPrice;
                }
                if (entryDate <= date) {
                    price = entryPrice;
                }
            });

            return price === null ? firstKnown : price;
        }

        function latestPrice(history) {
            var price = null;
            (Array.isArray(history) ? history : []).forEach(function (entry) {
                var value = Number(entry.price || 0);
                if (value > 0) {
                    price = value;
                }
            });

            return price;
        }

        function datasetForLicense(license, index, labels) {
            var isDefault = license.default_visible === true;
            var color = palette[index % palette.length];
            var slug = String(license.slug || '');

            return {
                slug: slug,
                label: String(license.name || license.slug || 'Lizenz'),
                data: labels.map(function (date) {
                    return priceAtDate(license.history, date);
                }),
                borderColor: color,
                backgroundColor: color,
                borderWidth: isDefault ? 3 : 1.5,
                pointRadius: isDefault ? 3 : 2,
                tension: 0.25,
                spanGaps: true,
                hidden: visibleHistorySlugs.indexOf(slug) === -1
            };
        }

        function selectedHistoryValues(datasets) {
            var values = [];
            datasets.forEach(function (dataset) {
                if (dataset.hidden) {
                    return;
                }
                values = values.concat(dataset.data.filter(function (value) {
                    return typeof value === 'number' && Number.isFinite(value);
                }));
            });

            return values;
        }

        function setHistoryHint(visible) {
            if (historyHint) {
                historyHint.hidden = !visible;
            }
        }

        function updatePriceFilterOptions() {
            if (!priceFilter) {
                return;
            }

            var capReached = visibleHistorySlugs.length >= maxVisibleLicenses;
            Array.prototype.slice.call(priceFilter.options).forEach(function (option) {
                var value = String(option.value || '');
                var selected = visibleHistorySlugs.indexOf(value) !== -1;
                option.selected = selected;
                option.disabled = value !== '' && capReached && !selected;
            });
        }

        function selectedHistorySlugsFromFilter() {
            if (!priceFilter) {
                return [];
            }

            return Array.prototype.slice.call(priceFilter.selectedOptions).map(function (option) {
                return String(option.value || '');
            }).filter(function (slug) {
                return Boolean(slug) && Boolean(licenseMap[slug]);
            });
        }

        function toggleHistoryLicense(slug) {
            slug = String(slug || '');
            if (!licenseMap[slug]) {
                return;
            }

            var index = visibleHistorySlugs.indexOf(slug);
            if (index !== -1) {
                visibleHistorySlugs.splice(index, 1);
                setHistoryHint(false);
                renderHistoryChart();
                return;
            }

            if (visibleHistorySlugs.length >= maxVisibleLicenses) {
                setHistoryHint(true);
                updatePriceFilterOptions();
                return;
            }

            visibleHistorySlugs.push(slug);
            setHistoryHint(visibleHistorySlugs.length >= maxVisibleLicenses);
            renderHistoryChart();
        }

        function resetHistoryLicenses() {
            visibleHistorySlugs = defaultVisibleSlugs.filter(function (slug) {
                return licenseMap[slug];
            }).slice(0, maxVisibleLicenses);
            if (visibleHistorySlugs.length === 0) {
                visibleHistorySlugs = licenses.slice(0, Math.min(4, maxVisibleLicenses)).map(function (license) {
                    return String(license.slug || '');
                }).filter(Boolean);
            }
            setHistoryHint(false);
            renderHistoryChart();
        }

        function renderHistoryChart() {
            if (!historyCanvas || !window.Chart) {
                renderHistoryFallback();
                return;
            }

            historyCanvas.hidden = false;

            var labels = buildQuarterTimeline(dates);
            var datasets = licenses.map(function (license, index) {
                return datasetForLicense(license, index, labels);
            });
            var historyScale = yScaleOptions(selectedHistoryValues(datasets));

            if (historyChart) {
                historyChart.destroy();
            }

            historyChart = new window.Chart(historyCanvas, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'nearest' },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: function (items) {
                                    return items.length > 0 ? formatQuarter(items[0].label) : '';
                                },
                                label: function (context) {
                                    return context.dataset.label + ': ' + formatMoney(context.parsed.y) + ' / User / Monat';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            offset: false,
                            ticks: {
                                autoSkip: false,
                                maxRotation: 0,
                                callback: function (value) {
                                    return formatQuarter(this.getLabelForValue(value));
                                }
                            }
                        },
                        y: {
                            beginAtZero: false,
                            suggestedMin: historyScale.suggestedMin,
                            suggestedMax: historyScale.suggestedMax,
                            ticks: historyScale.ticks
                        }
                    }
                }
            });
            updatePriceFilterOptions();
            setHistoryHint(visibleHistorySlugs.length >= maxVisibleLicenses);
        }

        function renderHistoryFallback() {
            var selectedLicenses = visibleHistorySlugs.map(function (slug) {
                return licenseMap[slug];
            }).filter(Boolean);
            var firstDate = dates[0] || '';
            var rows = selectedLicenses.map(function (license) {
                var firstPrice = priceAtDate(license.history, firstDate);
                var currentPrice = latestPrice(license.history);
                var delta = currentPrice !== null && firstPrice !== null ? currentPrice - firstPrice : 0;

                return [
                    String(license.name || license.slug || 'Lizenz'),
                    firstPrice === null ? 'k. A.' : formatMoney(firstPrice),
                    currentPrice === null ? 'k. A.' : formatMoney(currentPrice),
                    formatMoney(delta)
                ];
            });

            renderChartFallback(historyCanvas, 'Preisverlauf als kompakte Tabelle', ['Lizenz', 'Erster Wert', 'Aktuell', 'Delta'], rows);
            updatePriceFilterOptions();
            setHistoryHint(visibleHistorySlugs.length >= maxVisibleLicenses);
        }

        function readStoredRows() {
            try {
                var parsed = JSON.parse(window.localStorage.getItem(storageKey) || '[]');
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        }

        function writeStoredRows(rows) {
            try {
                window.localStorage.setItem(storageKey, JSON.stringify(rows));
            } catch (error) {
                return;
            }
        }

        function buildLicenseSelect(value) {
            var select = document.createElement('select');
            select.className = 'phinit-select';
            select.appendChild(new Option('Lizenz auswählen', ''));
            licenses.forEach(function (license) {
                select.appendChild(new Option(String(license.name || license.slug), String(license.slug || '')));
            });
            select.value = String(value || '');

            return select;
        }

        function initPersonalTracker() {
            if (!personalRoot) {
                return;
            }

            var rowsNode = personalRoot.querySelector('[data-m365calc-personal-rows]');
            var emptyNode = personalRoot.querySelector('[data-m365calc-personal-empty]');
            var addButton = personalRoot.querySelector('[data-m365calc-personal-add]');
            var evaluateButton = personalRoot.querySelector('[data-m365calc-personal-evaluate]');
            var resultsNode = personalRoot.querySelector('[data-m365calc-personal-results]');
            var summaryNode = personalRoot.querySelector('[data-m365calc-personal-summary]');
            var listNode = personalRoot.querySelector('[data-m365calc-personal-list]');
            var personalCanvas = personalRoot.querySelector('[data-m365calc-personal-chart]');
            var rows = readStoredRows();

            function saveRowsFromDom() {
                rows = Array.prototype.slice.call(rowsNode.querySelectorAll('[data-m365calc-personal-row]')).map(function (row) {
                    return {
                        id: row.getAttribute('data-row-id') || String(Date.now()),
                        slug: row.querySelector('[data-field="slug"]').value,
                        purchaseDate: row.querySelector('[data-field="purchaseDate"]').value,
                        quantity: Math.max(0, parseInt(row.querySelector('[data-field="quantity"]').value || '0', 10) || 0)
                    };
                });
                writeStoredRows(rows);
            }

            function renderRows() {
                rowsNode.replaceChildren();
                rows.forEach(function (item) {
                    var row = document.createElement('section');
                    row.className = 'm365calc-personal-row';
                    row.setAttribute('data-m365calc-personal-row', 'true');
                    row.setAttribute('data-row-id', String(item.id || Date.now()));

                    var licenseField = createText('label', 'phinit-field', 'Lizenz');
                    var select = buildLicenseSelect(item.slug);
                    select.setAttribute('data-field', 'slug');
                    licenseField.appendChild(select);

                    var dateField = createText('label', 'phinit-field', 'Kaufdatum');
                    var dateInput = document.createElement('input');
                    dateInput.className = 'phinit-input';
                    dateInput.type = 'date';
                    dateInput.value = String(item.purchaseDate || '');
                    dateInput.setAttribute('data-field', 'purchaseDate');
                    dateField.appendChild(dateInput);

                    var quantityField = createText('label', 'phinit-field', 'Menge');
                    var quantityInput = document.createElement('input');
                    quantityInput.className = 'phinit-input';
                    quantityInput.type = 'number';
                    quantityInput.min = '0';
                    quantityInput.step = '1';
                    quantityInput.value = String(item.quantity || 0);
                    quantityInput.setAttribute('data-field', 'quantity');
                    quantityField.appendChild(quantityInput);

                    var removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.className = 'phinit-btn phinit-btn--secondary m365calc-row-remove';
                    removeButton.textContent = 'Entfernen';
                    removeButton.addEventListener('click', function () {
                        rows = rows.filter(function (rowItem) {
                            return String(rowItem.id) !== String(item.id);
                        });
                        writeStoredRows(rows);
                        renderRows();
                    });

                    [select, dateInput, quantityInput].forEach(function (field) {
                        field.addEventListener('change', saveRowsFromDom);
                        field.addEventListener('input', saveRowsFromDom);
                    });

                    row.appendChild(licenseField);
                    row.appendChild(dateField);
                    row.appendChild(quantityField);
                    row.appendChild(removeButton);
                    rowsNode.appendChild(row);
                });

                if (emptyNode) {
                    emptyNode.hidden = rows.length > 0;
                }
            }

            function addRow() {
                saveRowsFromDom();
                rows.push({ id: String(Date.now()) + '-' + String(Math.round(Math.random() * 100000)), slug: '', purchaseDate: '', quantity: 1 });
                writeStoredRows(rows);
                renderRows();
            }

            function validRows() {
                saveRowsFromDom();
                return rows.map(function (item) {
                    var license = licenseMap[String(item.slug || '')];
                    return {
                        license: license,
                        slug: String(item.slug || ''),
                        purchaseDate: String(item.purchaseDate || ''),
                        quantity: Math.max(0, Number(item.quantity || 0))
                    };
                }).filter(function (item) {
                    return item.license && item.purchaseDate && item.quantity > 0;
                });
            }

            function evaluateRows() {
                var items = validRows();
                var purchaseAnnual = 0;
                var currentAnnual = 0;
                var tableRows = [];

                items.forEach(function (item) {
                    var purchasePrice = priceAtDate(item.license.history, item.purchaseDate);
                    var currentPrice = latestPrice(item.license.history);
                    if (purchasePrice === null || currentPrice === null) {
                        return;
                    }

                    var purchaseCost = purchasePrice * item.quantity * 12;
                    var currentCost = currentPrice * item.quantity * 12;
                    var delta = currentCost - purchaseCost;
                    purchaseAnnual += purchaseCost;
                    currentAnnual += currentCost;
                    tableRows.push({
                        license: item.license,
                        purchaseDate: item.purchaseDate,
                        quantity: item.quantity,
                        purchasePrice: purchasePrice,
                        currentPrice: currentPrice,
                        delta: delta,
                        percent: purchasePrice > 0 ? ((currentPrice - purchasePrice) / purchasePrice) * 100 : 0
                    });
                });

                return { items: items, rows: tableRows, purchaseAnnual: purchaseAnnual, currentAnnual: currentAnnual, delta: currentAnnual - purchaseAnnual };
            }

            function renderSummary(evaluation) {
                summaryNode.replaceChildren();
                [
                    ['Ausgangskosten / Jahr', evaluation.purchaseAnnual],
                    ['Aktuelle Kosten / Jahr', evaluation.currentAnnual],
                    ['Delta / Jahr', evaluation.delta],
                    ['Ausgewertete Zeilen', evaluation.rows.length]
                ].forEach(function (item) {
                    var card = document.createElement('article');
                    card.className = 'phinit-card m365calc-mini-card';
                    if (item[0] === 'Delta / Jahr') {
                        card.classList.add(evaluation.delta >= 0 ? 'phinit-card--warning' : 'phinit-card--success');
                    }
                    card.appendChild(createText('span', '', item[0]));
                    card.appendChild(createText('strong', '', typeof item[1] === 'number' && item[0] !== 'Ausgewertete Zeilen' ? formatMoney(item[1]) : String(item[1])));
                    summaryNode.appendChild(card);
                });
            }

            function renderList(rowsToRender) {
                listNode.replaceChildren();
                if (rowsToRender.length === 0) {
                    var emptyRow = document.createElement('tr');
                    var cell = document.createElement('td');
                    cell.colSpan = 7;
                    cell.textContent = 'Keine auswertbaren Zeilen. Bitte Lizenz, Kaufdatum und Menge erfassen.';
                    emptyRow.appendChild(cell);
                    listNode.appendChild(emptyRow);
                    return;
                }

                rowsToRender.forEach(function (item) {
                    var row = document.createElement('tr');
                    row.className = item.delta >= 0 ? 'm365calc-delta--positive' : 'm365calc-delta--negative';
                    [
                        item.license.name,
                        item.purchaseDate,
                        String(item.quantity),
                        formatMoney(item.purchasePrice),
                        formatMoney(item.currentPrice),
                        formatMoney(item.delta),
                        formatPercent(item.percent)
                    ].forEach(function (value, index) {
                        var cell = document.createElement(index === 0 ? 'th' : 'td');
                        if (index === 0) {
                            cell.scope = 'row';
                        }
                        cell.textContent = String(value || '');
                        row.appendChild(cell);
                    });
                    listNode.appendChild(row);
                });
            }

            function renderPersonalChart(evaluation) {
                if (!personalCanvas || !window.Chart) {
                    renderPersonalFallback(evaluation);
                    return;
                }

                personalCanvas.hidden = false;

                var dateMap = Object.create(null);
                evaluation.items.forEach(function (item) {
                    dateMap[item.purchaseDate] = true;
                    (item.license.history || []).forEach(function (entry) {
                        if (String(entry.date || '') >= item.purchaseDate) {
                            dateMap[String(entry.date)] = true;
                        }
                    });
                });

                var labels = buildQuarterTimeline(Object.keys(dateMap));
                var amounts = labels.map(function (date) {
                    return evaluation.items.reduce(function (sum, item) {
                        if (date < quarterStartDate(item.purchaseDate)) {
                            return sum;
                        }
                        var price = priceAtDate(item.license.history, date < item.purchaseDate ? item.purchaseDate : date);
                        return sum + (price || 0) * item.quantity * 12;
                    }, 0);
                });
                var personalScale = yScaleOptions(amounts);

                if (personalChart) {
                    personalChart.destroy();
                }

                personalChart = new window.Chart(personalCanvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Eigene Jahreskosten',
                            data: amounts,
                            borderColor: '#1f4e79',
                            backgroundColor: 'rgba(31, 78, 121, 0.12)',
                            fill: true,
                            borderWidth: 3,
                            tension: 0.25
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function (context) {
                                        return formatMoney(context.parsed.y) + ' / Jahr';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                ticks: {
                                    autoSkip: false,
                                    maxRotation: 0,
                                    callback: function (value) {
                                        return formatQuarter(this.getLabelForValue(value));
                                    }
                                }
                            },
                            y: {
                                beginAtZero: false,
                                suggestedMin: personalScale.suggestedMin,
                                suggestedMax: personalScale.suggestedMax,
                                ticks: personalScale.ticks
                            }
                        }
                    }
                });
            }

            function renderPersonalFallback(evaluation) {
                renderChartFallback(personalCanvas, 'Eigene Kostenentwicklung als kompakte Tabelle', ['Kennzahl', 'Wert'], [
                    ['Ausgangskosten / Jahr', formatMoney(evaluation.purchaseAnnual)],
                    ['Aktuelle Kosten / Jahr', formatMoney(evaluation.currentAnnual)],
                    ['Delta / Jahr', formatMoney(evaluation.delta)],
                    ['Ausgewertete Zeilen', String(evaluation.rows.length)]
                ]);
            }

            function evaluateAndRender() {
                var evaluation = evaluateRows();
                if (resultsNode) {
                    resultsNode.hidden = false;
                }
                renderSummary(evaluation);
                renderList(evaluation.rows);
                chartReady().then(function () {
                    renderPersonalChart(evaluation);
                }).catch(function () {
                    renderPersonalFallback(evaluation);
                });
            }

            if (addButton) {
                addButton.addEventListener('click', addRow);
            }
            if (evaluateButton) {
                evaluateButton.addEventListener('click', evaluateAndRender);
            }

            renderRows();
        }

        if (priceFilter) {
            priceFilter.addEventListener('change', function () {
                var selectedSlugs = selectedHistorySlugsFromFilter();
                if (selectedSlugs.length === 0) {
                    resetHistoryLicenses();
                    return;
                }

                visibleHistorySlugs = selectedSlugs.slice(0, maxVisibleLicenses);
                setHistoryHint(selectedSlugs.length > maxVisibleLicenses || visibleHistorySlugs.length >= maxVisibleLicenses);
                renderHistoryChart();
            });
        }

        chartReady().then(function () {
            renderHistoryChart();
        }).catch(function () {
            renderHistoryFallback();
        });

        initPersonalTracker();
    }

    function parsePercent(text) {
        var match = String(text || '').replace(',', '.').match(/(\d{1,3}(?:\.\d+)?)\s*%/);
        if (!match) {
            return null;
        }

        return Math.max(0, Math.min(100, Math.round(parseFloat(match[1]))));
    }

    function initDetailScoreRings(root) {
        root.querySelectorAll('.m365calc-score').forEach(function (score) {
            var strong = score.querySelector('strong');
            var percent = strong ? parsePercent(strong.textContent) : null;
            var bar = score.querySelector('.m365calc-score__bar span');

            if (percent === null && bar) {
                percent = parsePercent(bar.style.getPropertyValue('--m365calc-score-width'));
            }

            if (percent === null) {
                return;
            }

            score.classList.add('m365calc-score--ring');
            score.style.setProperty('--m365calc-score-value', percent + '%');
        });
    }

    function initDetailTableBars(root) {
        var resultTitle = root.querySelector('[data-m365calc-result] h2');
        var recommendedText = resultTitle ? resultTitle.textContent.trim().toLowerCase() : '';

        root.querySelectorAll('.phinit-table').forEach(function (table) {
            table.classList.add('m365calc-table--enhanced');

            table.querySelectorAll('tbody tr').forEach(function (row) {
                var rowHead = row.querySelector('th, td');
                var rowText = rowHead ? rowHead.textContent.trim().toLowerCase() : '';
                var fullRowText = row.textContent.trim().toLowerCase();

                if ((recommendedText && rowText && (recommendedText.indexOf(rowText) !== -1 || rowText.indexOf(recommendedText) !== -1)) || fullRowText.indexOf('empfohlen') !== -1) {
                    row.classList.add('m365calc-table-row--recommended');
                }

                row.querySelectorAll('td').forEach(function (cell) {
                    if (cell.querySelector('.m365calc-data-bar') || cell.querySelector('input, select, textarea, button')) {
                        return;
                    }

                    var percent = parsePercent(cell.textContent.trim());
                    if (percent === null) {
                        return;
                    }

                    var wrapper = document.createElement('span');
                    var value = document.createElement('span');
                    wrapper.className = 'm365calc-data-bar';
                    value.className = 'm365calc-data-bar__value';
                    wrapper.style.setProperty('--m365calc-bar-value', percent + '%');

                    while (cell.firstChild) {
                        value.appendChild(cell.firstChild);
                    }

                    wrapper.appendChild(value);
                    cell.appendChild(wrapper);
                });
            });
        });
    }

    function initDetailTimelines(root) {
        root.querySelectorAll('.m365calc-result-grid, .m365calc-result-card, .phinit-card').forEach(function (block) {
            var label = (block.getAttribute('aria-label') || '').toLowerCase();
            var heading = block.querySelector('h2');
            var overline = block.querySelector('.phinit-overline, .m365calc-eyebrow');
            var text = [label, heading ? heading.textContent : '', overline ? overline.textContent : ''].join(' ').toLowerCase();

            if (text.indexOf('zeitplan') === -1 && text.indexOf('timeline') === -1 && text.indexOf('rollout') === -1) {
                return;
            }

            var list = block.querySelector('ol.m365calc-note-list');
            if (list) {
                list.classList.add('m365calc-timeline');
            }
        });
    }

    function initDetailVisualEnhancements() {
        if (!document.body.classList.contains('m365tools-detail-enhanced')) {
            return;
        }

        var root = document.querySelector('.m365calc-page');
        if (!root) {
            return;
        }

        initDetailScoreRings(root);
        initDetailTableBars(root);
        initDetailTimelines(root);
    }

    ready(function () {
        initContentHost();
        initHiddenModuleActionLinks();
        initDetailInitialScrollPosition();
        initReadonlyMatrixScrollPosition();
        initResultFocus();
        initPrintButtons();
        initResetButtons();
        initLicenseAuditChecklist();
        initLicenseComparisonColumns();
        initMicrosoftPriceTrackerCharts();
        initDetailVisualEnhancements();
    });
}());
