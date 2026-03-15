(function () {
    'use strict';

    function reindexRows() {
        const rows = document.querySelectorAll('.m365lic-requirement');
        rows.forEach((row, index) => {
            row.dataset.index = String(index);
            const heading = row.querySelector('h3');
            if (heading) {
                heading.textContent = 'Bedarfsgruppe ' + (index + 1);
            }

            row.querySelectorAll('input, select, textarea, label').forEach((element) => {
                if (element.name) {
                    element.name = element.name.replace(/requirements\[\d+\]/g, 'requirements[' + index + ']');
                }
                if (element.id) {
                    element.id = element.id.replace(/_(\d+)$/g, '_' + index);
                }
                if (element.htmlFor) {
                    element.htmlFor = element.htmlFor.replace(/_(\d+)$/g, '_' + index);
                }
            });
        });
    }

    function clearRow(row) {
        row.querySelectorAll('input[type="text"], input[type="number"], textarea').forEach((input) => {
            if (input.name && input.name.indexOf('[quantity]') !== -1) {
                input.value = '1';
            } else {
                input.value = '';
            }
        });
        row.querySelectorAll('select').forEach((select) => {
            if (select.classList.contains('m365lic-preset-select')) {
                select.value = '';
            } else if (select.name && select.name.indexOf('[audience]') !== -1) {
                select.value = 'knowledge';
            } else {
                select.selectedIndex = 0;
            }
        });
        row.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
            checkbox.checked = false;
        });
    }

    function applyPreset(select) {
        const presetKey = select.value;
        const row = select.closest('.m365lic-requirement');
        if (!row) {
            return;
        }
        const presets = window.cmsM365LicPresets || {};
        if (!presetKey || !presets[presetKey]) {
            return;
        }

        const preset = presets[presetKey];
        const audienceSelect = row.querySelector('select[name$="[audience]"]');
        if (audienceSelect && preset.audience) {
            audienceSelect.value = preset.audience;
        }

        row.querySelectorAll('input[type="checkbox"][data-feature]').forEach((checkbox) => {
            checkbox.checked = Array.isArray(preset.features) && preset.features.indexOf(checkbox.dataset.feature) !== -1;
        });
    }

    function bindRow(row) {
        const removeBtn = row.querySelector('.m365lic-remove-row');
        if (removeBtn) {
            removeBtn.addEventListener('click', function () {
                const rows = document.querySelectorAll('.m365lic-requirement');
                if (rows.length <= 1) {
                    clearRow(row);
                    return;
                }
                row.remove();
                reindexRows();
            });
        }

        const presetSelect = row.querySelector('.m365lic-preset-select');
        if (presetSelect) {
            presetSelect.addEventListener('change', function () {
                applyPreset(presetSelect);
            });
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('m365licRequirements');
        const addButton = document.getElementById('m365licAddRow');
        if (!container || !addButton) {
            return;
        }

        container.querySelectorAll('.m365lic-requirement').forEach(bindRow);

        addButton.addEventListener('click', function () {
            const firstRow = container.querySelector('.m365lic-requirement');
            if (!firstRow) {
                return;
            }
            const clone = firstRow.cloneNode(true);
            clearRow(clone);
            container.appendChild(clone);
            reindexRows();
            bindRow(clone);
        });
    });
})();
