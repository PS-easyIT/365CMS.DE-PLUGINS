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
        var result = document.querySelector('[data-m365calc-result]');
        if (!result) {
            return;
        }

        result.setAttribute('tabindex', '-1');
        result.focus({ preventScroll: true });
        result.scrollIntoView({ behavior: 'smooth', block: 'start' });
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

    ready(function () {
        initResultFocus();
        initPrintButtons();
        initResetButtons();
        initLicenseAuditChecklist();
    });
}());
