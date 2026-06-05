(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function chartReady() {
        if (window.Chart) {
            return Promise.resolve(window.Chart);
        }

        return Promise.reject(new Error('Chart.js not available'));
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
        if (!canvas || !canvas.parentNode) {
            return;
        }

        var existing = canvas.parentNode.querySelector('[data-m365price-chart-fallback]');
        if (existing) {
            existing.remove();
        }

        canvas.hidden = true;

        var wrap = document.createElement('section');
        wrap.className = 'phinit-table-wrap m365price-chart-fallback';
        wrap.setAttribute('data-m365price-chart-fallback', 'true');
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

    function initPrintButtons(root) {
        root.querySelectorAll('[data-m365price-print]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.print();
            });
        });
    }

    function initPriceTracker() {
        var root = document.querySelector('[data-m365price-tracker]');
        if (!root) {
            return;
        }

        var dataNode = root.querySelector('[data-m365price-tracker-data]');
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
        var storageKey = String(data.storage_key || 'm365price-tracker-entries-v1');
        var licenseMap = Object.create(null);
        var priceFilter = root.querySelector('[data-m365price-history-filter]');
        var historyCanvas = root.querySelector('[data-m365price-history-chart]');
        var historyHint = root.querySelector('[data-m365price-history-hint]');
        var personalRoot = root.querySelector('[data-m365price-personal-tracker]');
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

        function selectedHistoryValues(datasets) {
            var values = [];
            datasets.forEach(function (dataset) {
                if (!dataset.hidden) {
                    values = values.concat(dataset.data.filter(function (value) {
                        return typeof value === 'number' && Number.isFinite(value);
                    }));
                }
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

        function renderHistoryChart() {
            if (!historyCanvas || !window.Chart) {
                renderHistoryFallback();
                return;
            }

            historyCanvas.hidden = false;
            if (historyCanvas.parentNode) {
                var fallback = historyCanvas.parentNode.querySelector('[data-m365price-chart-fallback]');
                if (fallback) {
                    fallback.remove();
                }
            }

            var labels = buildQuarterTimeline(dates);
            var datasets = licenses.map(function (license, index) {
                var slug = String(license.slug || '');
                var color = palette[index % palette.length];

                return {
                    slug: slug,
                    label: String(license.name || license.slug || 'Lizenz'),
                    data: labels.map(function (date) {
                        return priceAtDate(license.history, date);
                    }),
                    borderColor: color,
                    backgroundColor: color,
                    borderWidth: license.default_visible === true ? 3 : 1.5,
                    pointRadius: license.default_visible === true ? 3 : 2,
                    tension: 0.25,
                    spanGaps: true,
                    hidden: visibleHistorySlugs.indexOf(slug) === -1
                };
            });
            var historyScale = yScaleOptions(selectedHistoryValues(datasets));

            if (historyChart) {
                historyChart.destroy();
            }

            historyChart = new window.Chart(historyCanvas, {
                type: 'line',
                data: { labels: labels, datasets: datasets },
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

            var rowsNode = personalRoot.querySelector('[data-m365price-personal-rows]');
            var emptyNode = personalRoot.querySelector('[data-m365price-personal-empty]');
            var addButton = personalRoot.querySelector('[data-m365price-personal-add]');
            var evaluateButton = personalRoot.querySelector('[data-m365price-personal-evaluate]');
            var resultsNode = personalRoot.querySelector('[data-m365price-personal-results]');
            var summaryNode = personalRoot.querySelector('[data-m365price-personal-summary]');
            var listNode = personalRoot.querySelector('[data-m365price-personal-list]');
            var personalCanvas = personalRoot.querySelector('[data-m365price-personal-chart]');
            var rows = readStoredRows();

            function saveRowsFromDom() {
                rows = Array.prototype.slice.call(rowsNode.querySelectorAll('[data-m365price-personal-row]')).map(function (row) {
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
                    row.className = 'm365price-personal-row';
                    row.setAttribute('data-m365price-personal-row', 'true');
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
                    removeButton.className = 'phinit-btn phinit-btn--secondary m365price-row-remove';
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
                    card.className = 'phinit-card m365price-mini-card';
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

            function renderPersonalFallback(evaluation) {
                renderChartFallback(personalCanvas, 'Eigene Kostenentwicklung als kompakte Tabelle', ['Kennzahl', 'Wert'], [
                    ['Ausgangskosten / Jahr', formatMoney(evaluation.purchaseAnnual)],
                    ['Aktuelle Kosten / Jahr', formatMoney(evaluation.currentAnnual)],
                    ['Delta / Jahr', formatMoney(evaluation.delta)],
                    ['Ausgewertete Zeilen', String(evaluation.rows.length)]
                ]);
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
                var selectedSlugs = Array.prototype.slice.call(priceFilter.selectedOptions).map(function (option) {
                    return String(option.value || '');
                }).filter(function (slug) {
                    return Boolean(slug) && Boolean(licenseMap[slug]);
                });

                if (selectedSlugs.length === 0) {
                    visibleHistorySlugs = defaultVisibleSlugs.filter(function (slug) {
                        return licenseMap[slug];
                    }).slice(0, maxVisibleLicenses);
                } else {
                    visibleHistorySlugs = selectedSlugs.slice(0, maxVisibleLicenses);
                }

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
        initPrintButtons(root);
    }

    ready(initPriceTracker);
}());
