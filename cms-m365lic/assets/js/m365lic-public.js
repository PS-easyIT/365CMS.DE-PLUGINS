(function () {
    'use strict';

    const MAX_REQUIREMENT_ROWS = 25;

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
        row.dataset.step = '1';
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

        setStep(row, 1);
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

    function setStep(row, step) {
        row.dataset.step = String(step);

        row.querySelectorAll('[data-step-panel]').forEach((panel) => {
            panel.classList.toggle('is-active', panel.dataset.stepPanel === String(step));
        });

        row.querySelectorAll('[data-step-target]').forEach((trigger) => {
            trigger.classList.toggle('is-active', trigger.dataset.stepTarget === String(step));
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

        row.querySelectorAll('[data-step-target]').forEach((trigger) => {
            trigger.addEventListener('click', function () {
                setStep(row, Number(trigger.dataset.stepTarget || '1'));
            });
        });

        row.querySelectorAll('[data-step-next]').forEach((button) => {
            button.addEventListener('click', function () {
                setStep(row, Number(button.dataset.stepNext || '1'));
            });
        });

        row.querySelectorAll('[data-step-prev]').forEach((button) => {
            button.addEventListener('click', function () {
                setStep(row, Number(button.dataset.stepPrev || '1'));
            });
        });

        setStep(row, Number(row.dataset.step || '1'));
    }

    function collectRequirements(container) {
        return Array.from(container.querySelectorAll('.m365lic-requirement')).map((row) => {
            const checkedFeatures = Array.from(row.querySelectorAll('input[type="checkbox"][data-feature]:checked')).map((checkbox) => checkbox.dataset.feature);
            const labelInput = row.querySelector('input[name$="[label]"]');
            const quantityInput = row.querySelector('input[name$="[quantity]"]');
            const audienceSelect = row.querySelector('select[name$="[audience]"]');
            const presetSelect = row.querySelector('select[name$="[preset]"]');
            const parsedQuantity = quantityInput ? Number.parseInt(quantityInput.value, 10) : 1;
            const quantity = Number.isFinite(parsedQuantity) && parsedQuantity > 0 ? parsedQuantity : 1;

            if (quantityInput) {
                quantityInput.value = String(quantity);
            }

            return {
                label: labelInput ? labelInput.value : '',
                quantity: quantity,
                audience: audienceSelect ? audienceSelect.value : 'knowledge',
                preset: presetSelect ? presetSelect.value : '',
                features: checkedFeatures
            };
        });
    }

    function compactRequirementInputs(container) {
        container.querySelectorAll('[name]').forEach((element) => {
            if (element.name && element.name.indexOf('requirements[') === 0) {
                element.dataset.originalName = element.name;
                element.removeAttribute('name');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('m365licRequirements');
        const addButton = document.getElementById('m365licAddRow');
        const form = document.getElementById('m365licForm');
        const payloadField = document.getElementById('m365licRequirementsPayload');

        if (!container || !addButton || !form || !payloadField) {
            return;
        }

        container.querySelectorAll('.m365lic-requirement').forEach(bindRow);

        addButton.addEventListener('click', function () {
            const firstRow = container.querySelector('.m365lic-requirement');
            if (!firstRow) {
                return;
            }

            const existingRows = container.querySelectorAll('.m365lic-requirement').length;
            if (existingRows >= MAX_REQUIREMENT_ROWS) {
                alert('Bitte maximal ' + MAX_REQUIREMENT_ROWS + ' Bedarfsgruppen gleichzeitig anlegen.');
                return;
            }

            const clone = firstRow.cloneNode(true);
            clearRow(clone);
            container.appendChild(clone);
            reindexRows();
            bindRow(clone);
        });

        form.addEventListener('submit', function (event) {
            const requirements = collectRequirements(container);
            if (requirements.length > MAX_REQUIREMENT_ROWS) {
                event.preventDefault();
                alert('Bitte maximal ' + MAX_REQUIREMENT_ROWS + ' Bedarfsgruppen gleichzeitig auswerten.');
                return;
            }

            payloadField.value = JSON.stringify(requirements);
            compactRequirementInputs(container);
        });
    });
})();
