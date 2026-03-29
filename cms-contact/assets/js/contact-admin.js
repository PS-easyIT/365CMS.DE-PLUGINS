/**
 * CMS Contact – Admin JavaScript
 * Drag-Drop-Sortierung und sonstige Admin-Interaktionen.
 *
 * @package CMS_Contact
 * @version 1.0.0
 */
(function () {
    'use strict';

    var pendingDeleteForm = null;

    document.addEventListener('DOMContentLoaded', function () {
        initAutoSlug();
        initColorSync();
        initModals();
        initTabs();
        initTemplateCards();
        initDeleteModals();
        initSelectAllCheckboxes();
        initFieldEditor();
    });

    if (typeof window.openModal !== 'function') {
        window.openModal = function (id) {
            var modal = document.getElementById(id);
            if (!modal) return;

            modal.style.display = 'flex';
            var firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea, button');
            if (firstInput) {
                setTimeout(function () {
                    firstInput.focus();
                }, 50);
            }
        };
    }

    if (typeof window.closeModal !== 'function') {
        window.closeModal = function (id) {
            var modal = document.getElementById(id);
            if (modal) {
                modal.style.display = 'none';
            }
        };
    }

    window.switchTab = function (tabId, btn) {
        activateTab(tabId, btn);
    };

    /**
     * Automatische Slug-Generierung aus Titel-Eingabe.
     */
    function initAutoSlug() {
        var titleInput = document.getElementById('title');
        var slugInput = document.getElementById('slug');
        if (!titleInput || !slugInput) return;

        // Nur auf "Neu erstellen"-Seiten automatisch generieren
        if (slugInput.value.trim()) return;

        titleInput.addEventListener('input', function () {
            var slug = titleInput.value
                .toLowerCase()
                .replace(/ä/g, 'ae').replace(/ö/g, 'oe').replace(/ü/g, 'ue').replace(/ß/g, 'ss')
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .substring(0, 100);
            slugInput.value = slug;
        });
    }

    /**
     * Farb-Picker mit Hex-Eingabefeld synchronisieren.
     */
    function initColorSync() {
        document.querySelectorAll('input[type="color"]').forEach(function (picker) {
            var textInput = picker.parentElement.querySelector('input[type="text"]');
            if (!textInput) return;
            picker.addEventListener('input', function () {
                textInput.value = picker.value;
            });
            textInput.addEventListener('input', function () {
                if (/^#[0-9A-Fa-f]{6}$/.test(textInput.value)) {
                    picker.value = textInput.value;
                }
            });
        });
    }

    function initModals() {
        document.querySelectorAll('[data-close-modal]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeModal(button.getAttribute('data-close-modal'));
            });
        });

        window.addEventListener('click', function (event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        });

        window.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') return;

            document.querySelectorAll('.modal').forEach(function (modal) {
                if (modal.style.display && modal.style.display !== 'none') {
                    modal.style.display = 'none';
                }
            });
        });
    }

    function activateTab(tabId, btn) {
        var container = btn ? btn.closest('[data-tab-scope]') : null;
        var contentSelector = container && container.getAttribute('data-tab-content-selector')
            ? container.getAttribute('data-tab-content-selector')
            : '.tab-content';
        var buttonSelector = container && container.getAttribute('data-tab-button-selector')
            ? container.getAttribute('data-tab-button-selector')
            : '[data-contact-tab-target]';

        document.querySelectorAll(contentSelector).forEach(function (pane) {
            pane.classList.remove('active');
        });
        document.querySelectorAll(buttonSelector).forEach(function (button) {
            button.classList.remove('active');
        });

        var pane = document.getElementById(tabId);
        if (pane) {
            pane.classList.add('active');
        }
        if (btn) {
            btn.classList.add('active');
        }
    }

    function initTabs() {
        document.querySelectorAll('[data-contact-tab-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                activateTab(button.getAttribute('data-contact-tab-target'), button);
            });
        });
    }

    function syncTemplateCards(scope) {
        (scope || document).querySelectorAll('.contact-template-card').forEach(function (card) {
            var input = card.querySelector('input[type="radio"]');
            card.classList.toggle('is-selected', !!(input && input.checked));
        });
    }

    function initTemplateCards() {
        syncTemplateCards(document);
        document.querySelectorAll('.contact-template-card input[type="radio"]').forEach(function (input) {
            input.addEventListener('change', function () {
                syncTemplateCards(input.closest('.contact-template-grid') || document);
            });
        });
    }

    function initDeleteModals() {
        document.querySelectorAll('[data-contact-open-delete-modal]').forEach(function (button) {
            button.addEventListener('click', function () {
                var modalId = button.getAttribute('data-contact-open-delete-modal');
                var itemId = button.getAttribute('data-delete-id') || '';
                var itemName = button.getAttribute('data-delete-name') || '';
                var scope = document.getElementById(modalId);

                if (!scope) {
                    return;
                }

                var idInput = scope.querySelector('[data-delete-modal-id]');
                var nameTarget = scope.querySelector('[data-delete-modal-name]');
                if (idInput) {
                    idInput.value = itemId;
                }
                if (nameTarget) {
                    nameTarget.textContent = itemName;
                }

                openModal(modalId);
            });
        });
    }

    function initSelectAllCheckboxes() {
        document.querySelectorAll('[data-contact-select-all]').forEach(function (toggle) {
            toggle.addEventListener('change', function () {
                var targetName = toggle.getAttribute('data-contact-select-all');
                if (!targetName) {
                    return;
                }

                document.querySelectorAll('input[name="' + targetName + '"]').forEach(function (checkbox) {
                    checkbox.checked = toggle.checked;
                });
            });
        });
    }

    function toggleOptionsField() {
        var fieldType = document.getElementById('field_type');
        var optionsGroup = document.getElementById('optionsGroup');
        if (!fieldType || !optionsGroup) return;

        optionsGroup.style.display = ['select', 'radio'].includes(fieldType.value) ? 'block' : 'none';
    }

    function openFieldModal() {
        var form = document.getElementById('fieldForm');
        if (!form) return;

        document.getElementById('fieldModalTitle').textContent = '➕ Neues Feld';
        document.getElementById('fieldFormAction').value = 'save_field';
        document.getElementById('fieldFormId').value = '';
        form.reset();
        toggleOptionsField();
        openModal('fieldModal');
    }

    function editField(field) {
        var optionsValue = '';
        if (field.options_json) {
            try {
                var parsedOptions = typeof field.options_json === 'string'
                    ? JSON.parse(field.options_json)
                    : field.options_json;

                if (Array.isArray(parsedOptions)) {
                    optionsValue = parsedOptions.map(function (option) {
                        return option && (option.label || option.value) ? String(option.label || option.value) : '';
                    }).filter(function (option) {
                        return option !== '';
                    }).join('\n');
                }
            } catch (error) {
                optionsValue = '';
            }
        }

        document.getElementById('fieldModalTitle').textContent = '✏️ Feld bearbeiten';
        document.getElementById('fieldFormAction').value = 'save_field';
        document.getElementById('fieldFormId').value = field.id || '';
        document.getElementById('field_label').value = field.field_label || '';
        document.getElementById('field_name').value = field.field_name || '';
        document.getElementById('field_type').value = field.field_type || 'text';
        document.getElementById('field_width').value = field.field_width || 'full';
        document.getElementById('field_placeholder').value = field.placeholder || '';
        document.getElementById('field_options').value = optionsValue;
        document.getElementById('field_validation').value = field.validation || '';
        document.getElementById('field_required').checked = !!parseInt(field.is_required, 10);
        document.getElementById('field_system').checked = !!parseInt(field.is_system, 10);
        toggleOptionsField();
        openModal('fieldModal');
    }

    function confirmDeleteForm(form) {
        pendingDeleteForm = form;
        openModal('deleteConfirmModal');
    }

    function initFieldEditor() {
        window.openFieldModal = openFieldModal;
        window.editField = editField;
        window.toggleOptionsField = toggleOptionsField;
        window.confirmDelete = function () {
            return false;
        };

        var openFieldModalBtn = document.getElementById('openFieldModalBtn');
        if (openFieldModalBtn) {
            openFieldModalBtn.addEventListener('click', openFieldModal);
        }

        var fieldType = document.getElementById('field_type');
        if (fieldType) {
            fieldType.addEventListener('change', toggleOptionsField);
            toggleOptionsField();
        }

        document.querySelectorAll('.js-contact-edit-field').forEach(function (button) {
            button.addEventListener('click', function () {
                try {
                    editField(JSON.parse(button.getAttribute('data-field') || '{}'));
                } catch (error) {
                    editField({});
                }
            });
        });

        document.querySelectorAll('.js-contact-delete-field-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                confirmDeleteForm(form);
            });
        });

        var confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', function () {
                if (pendingDeleteForm) {
                    pendingDeleteForm.submit();
                    pendingDeleteForm = null;
                }
            });
        }

        initFieldDragDrop();
    }

    function initFieldDragDrop() {
        var container = document.getElementById('fieldsContainer');
        if (!container) return;

        var saveOrderBtn = document.getElementById('saveOrderBtn');
        var fieldOrderInput = document.getElementById('fieldOrderInput');
        var dragEl = null;

        container.addEventListener('dragstart', function (event) {
            dragEl = event.target.closest('.contact-field-row');
            if (!dragEl) return;

            dragEl.classList.add('dragging');
            event.dataTransfer.effectAllowed = 'move';
        });

        container.addEventListener('dragend', function () {
            if (dragEl) {
                dragEl.classList.remove('dragging');
            }
            dragEl = null;
        });

        container.addEventListener('dragover', function (event) {
            event.preventDefault();
            if (!dragEl) return;

            var target = event.target.closest('.contact-field-row');
            if (!target || target === dragEl) return;

            var rect = target.getBoundingClientRect();
            var midpoint = rect.top + rect.height / 2;
            if (event.clientY < midpoint) {
                container.insertBefore(dragEl, target);
            } else {
                container.insertBefore(dragEl, target.nextSibling);
            }
        });

        container.addEventListener('drop', function (event) {
            event.preventDefault();
            if (!fieldOrderInput || !saveOrderBtn) return;

            fieldOrderInput.value = Array.from(container.querySelectorAll('.contact-field-row')).map(function (row) {
                return row.dataset.id;
            }).join(',');
            saveOrderBtn.hidden = false;
        });
    }
})();
