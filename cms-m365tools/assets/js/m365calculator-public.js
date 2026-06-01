(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function initResultFocus() {
        if (document.querySelector('.m365calc-readonly-page')) {
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
        initReadonlyMatrixScrollPosition();
        initResultFocus();
        initPrintButtons();
        initResetButtons();
        initLicenseAuditChecklist();
        initDetailVisualEnhancements();
    });
}());
