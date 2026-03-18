(function () {
    'use strict';

    function openManagedModal(modal) {
        if (!modal) {
            return;
        }

        modal.hidden = false;
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    }

    function closeManagedModal(modal) {
        if (!modal) {
            return;
        }

        modal.style.display = 'none';
        modal.hidden = true;
        modal.setAttribute('aria-hidden', 'true');
    }

    function bindBadgeStyles() {
        document.querySelectorAll('[data-ev-badge-fg], [data-ev-badge-bg]').forEach(function (badge) {
            if (badge.dataset.evBadgeFg) {
                badge.style.color = badge.dataset.evBadgeFg;
            }
            if (badge.dataset.evBadgeBg) {
                badge.style.background = badge.dataset.evBadgeBg;
            }
        });
    }

    function bindConfirmForms() {
        document.querySelectorAll('form[data-ev-confirm-message]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (form.dataset.evConfirmed === '1') {
                    delete form.dataset.evConfirmed;
                    return;
                }

                event.preventDefault();

                if (typeof window.cmsConfirm === 'function') {
                    window.cmsConfirm({
                        title: form.dataset.evConfirmTitle || 'Aktion bestätigen',
                        message: form.dataset.evConfirmMessage || '',
                        confirmText: form.dataset.evConfirmButton || 'Bestätigen',
                        confirmClass: form.dataset.evConfirmClass || 'btn-danger',
                        onConfirm: function () {
                            form.dataset.evConfirmed = '1';
                            form.requestSubmit();
                        }
                    });
                    return;
                }

                form.dataset.evConfirmed = '1';
                form.requestSubmit();
            });
        });
    }

    function bindOverviewModals() {
        var approveModal = document.getElementById('evApproveModal');
        var deleteModal = document.getElementById('evDeleteModal');
        var approveName = document.getElementById('evApproveName');
        var deleteName = document.getElementById('evDeleteName');
        var deleteForm = document.getElementById('evDeleteForm');
        var approveConfirm = document.getElementById('evApproveConfirm');
        var pendingApproveForm = null;

        document.querySelectorAll('[data-ev-approve-event]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (approveName) {
                    approveName.textContent = button.dataset.evEventName || '';
                }

                var submitTargetId = button.dataset.evSubmitTarget || '';
                pendingApproveForm = submitTargetId ? document.getElementById(submitTargetId) : button.closest('form');
                openManagedModal(approveModal);
            });
        });

        approveConfirm?.addEventListener('click', function () {
            closeManagedModal(approveModal);
            pendingApproveForm?.requestSubmit();
        });

        document.querySelectorAll('[data-ev-delete-event]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (deleteName) {
                    deleteName.textContent = button.dataset.evEventName || '';
                }
                if (deleteForm) {
                    deleteForm.action = button.dataset.evDeleteAction || deleteForm.action;
                }
                openManagedModal(deleteModal);
            });
        });

        document.querySelectorAll('[data-ev-modal-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                var modalId = button.dataset.evModalClose || '';
                if (!modalId) {
                    return;
                }
                closeManagedModal(document.getElementById(modalId));
            });
        });

        document.querySelectorAll('[data-ev-managed-modal]').forEach(function (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeManagedModal(modal);
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-ev-managed-modal]').forEach(function (modal) {
                if (modal.style.display === 'flex') {
                    closeManagedModal(modal);
                }
            });
        });
    }

    function bindColorFields() {
        document.querySelectorAll('[data-ev-color-picker]').forEach(function (picker) {
            picker.addEventListener('input', function () {
                var textId = picker.dataset.evColorText || '';
                var textInput = textId ? document.getElementById(textId) : null;
                if (textInput) {
                    textInput.value = picker.value;
                    textInput.dispatchEvent(new CustomEvent('ev:color-updated', { bubbles: true }));
                }
            });
        });

        document.querySelectorAll('[data-ev-color-text]').forEach(function (textInput) {
            var syncPicker = function () {
                var pickerId = textInput.dataset.evColorPicker || '';
                var picker = pickerId ? document.getElementById(pickerId) : null;
                var value = textInput.value.trim();
                if (picker && /^#[0-9a-fA-F]{6}$/.test(value)) {
                    picker.value = value;
                }
                textInput.dispatchEvent(new CustomEvent('ev:color-updated', { bubbles: true }));
            };

            textInput.addEventListener('input', syncPicker);
            textInput.addEventListener('change', syncPicker);
        });
    }

    function bindHeaderPreview() {
        var preview = document.getElementById('ev_hdr_preview');
        if (!preview) {
            return;
        }

        var icon = document.getElementById('ev_hdr_icon');
        var title = document.getElementById('ev_hdr_title');
        var titleText = preview.dataset.evPreviewTitle || 'Events';

        function getValue(id, fallback) {
            var element = document.getElementById(id);
            return element ? element.value : fallback;
        }

        function updatePreview() {
            var from = getValue('txt_color_hdr_from', '#1d4ed8');
            var to = getValue('txt_color_hdr_to', '#3b82f6');
            var color = getValue('txt_color_hdr_title', '#ffffff');
            var emoji = getValue('txt_archive_header_icon', '📅');

            preview.style.background = 'linear-gradient(135deg,' + from + ',' + to + ')';
            preview.style.color = color;
            if (icon) {
                icon.textContent = emoji;
            }
            if (title) {
                title.textContent = titleText;
                title.style.color = color;
            }
        }

        ['txt_color_hdr_from', 'txt_color_hdr_to', 'txt_color_hdr_title', 'txt_archive_header_icon'].forEach(function (id) {
            var element = document.getElementById(id);
            element?.addEventListener('input', updatePreview);
            element?.addEventListener('ev:color-updated', updatePreview);
        });

        updatePreview();
    }

    function bindDesignPreviewCard() {
        var previewShell = document.getElementById('ev_design_preview');
        var previewIcon = document.getElementById('prev-icon');
        var previewTitle = document.getElementById('prev-title');

        if (!previewShell) {
            return;
        }

        function getValue(id, fallback) {
            var element = document.getElementById(id);
            return element ? element.value : fallback;
        }

        function updateCardPreview() {
            previewShell.style.setProperty('--ev-preview-from', getValue('txt_color_hdr_from', '#1d4ed8'));
            previewShell.style.setProperty('--ev-preview-to', getValue('txt_color_hdr_to', '#3b82f6'));
            previewShell.style.setProperty('--ev-preview-title-color', getValue('txt_color_hdr_title', '#ffffff'));
            previewShell.style.setProperty('--ev-preview-card-bg', getValue('txt_color_card_bg', '#f0f7ff'));
            previewShell.style.setProperty('--ev-preview-border', getValue('txt_color_card_border', '#bfdbfe'));
            previewShell.style.setProperty('--ev-preview-primary', getValue('txt_color_primary', '#3b82f6'));
            previewShell.style.setProperty('--ev-preview-radius', getValue('border_radius', '12') + 'px');

            if (previewIcon) {
                previewIcon.textContent = getValue('txt_archive_header_icon', '📅');
            }

            if (previewTitle) {
                previewTitle.textContent = getValue('archive_title', 'Events');
            }
        }

        [
            'txt_color_hdr_from',
            'txt_color_hdr_to',
            'txt_color_hdr_title',
            'txt_color_card_bg',
            'txt_color_card_border',
            'txt_color_primary',
            'txt_archive_header_icon',
            'archive_title',
            'border_radius'
        ].forEach(function (id) {
            var element = document.getElementById(id);
            element?.addEventListener('input', updateCardPreview);
            element?.addEventListener('change', updateCardPreview);
            element?.addEventListener('ev:color-updated', updateCardPreview);
        });

        updateCardPreview();
    }

    function bindEventFormToggles() {
        var onlineCheckbox = document.getElementById('ev_is_online');
        var onlineFields = document.getElementById('ev_online_fields');
        var locationFields = document.getElementById('ev_location_fields');
        var priceType = document.getElementById('ev_price_type');
        var priceField = document.getElementById('ev_price_field');
        var currencyField = document.getElementById('ev_currency_field');

        function syncOnlineMode() {
            if (!onlineCheckbox || !onlineFields || !locationFields) {
                return;
            }
            onlineFields.hidden = !onlineCheckbox.checked;
            locationFields.hidden = onlineCheckbox.checked;
        }

        function syncPriceMode() {
            if (!priceType || !priceField || !currencyField) {
                return;
            }
            var showPrice = priceType.value !== 'free';
            priceField.hidden = !showPrice;
            currencyField.hidden = !showPrice;
        }

        onlineCheckbox?.addEventListener('change', syncOnlineMode);
        priceType?.addEventListener('change', syncPriceMode);
        syncOnlineMode();
        syncPriceMode();
    }

    function bindMetaBoxLocationToggles() {
        var onlineCheckbox = document.querySelector('[data-ev-meta-toggle="location"]');
        var onlineFields = document.getElementById('online-fields');
        var physicalFields = document.getElementById('physical-fields');

        if (!onlineCheckbox || !onlineFields || !physicalFields) {
            return;
        }

        var sync = function () {
            onlineFields.hidden = !onlineCheckbox.checked;
            physicalFields.hidden = onlineCheckbox.checked;
        };

        onlineCheckbox.addEventListener('change', sync);
        sync();
    }

    function bindSpeakerAssignment() {
        var speakerBox = document.getElementById('ev-speaker-box');
        if (!speakerBox) {
            return;
        }

        var typeSelect = speakerBox.querySelector('[data-ev-speaker-type]');
        var personSelect = speakerBox.querySelector('[data-ev-speaker-person]');
        var titleInput = speakerBox.querySelector('[data-ev-speaker-title]');
        var timeInput = speakerBox.querySelector('[data-ev-speaker-time]');
        var addButton = speakerBox.querySelector('[data-ev-speaker-add]');
        var assignedList = document.getElementById('ev-assigned-speakers');

        function syncPersonOptions() {
            if (!typeSelect || !personSelect) {
                return;
            }

            var activeType = typeSelect.value;
            var firstVisible = '';

            Array.from(personSelect.options).forEach(function (option, index) {
                if (index === 0) {
                    option.hidden = false;
                    return;
                }

                var matches = option.dataset.evSpeakerOption === activeType;
                option.hidden = !matches;
                if (matches && !firstVisible) {
                    firstVisible = option.value;
                }
            });

            if (personSelect.selectedOptions[0]?.hidden) {
                personSelect.value = '';
            }
        }

        function ensureEmptyMessage() {
            if (!assignedList) {
                return;
            }

            if (assignedList.querySelector('.ev-sp-row')) {
                return;
            }

            assignedList.innerHTML = '<p class="form-text ev-text-muted">' + (speakerBox.dataset.evSpeakerEmptyMessage || 'Noch keine Person zugeordnet.') + '</p>';
        }

        async function removeSpeaker(assignmentId) {
            var fd = new FormData();
            fd.append('csrf_token', speakerBox.dataset.evSpeakerCsrf || '');

            try {
                var response = await fetch((speakerBox.dataset.evSpeakerEndpointRemoveBase || '') + assignmentId, {
                    method: 'POST',
                    body: fd
                });
                var payload = await response.json();
                if (!payload.success) {
                    window.alert('Fehler beim Entfernen');
                    return;
                }

                document.getElementById('ev-sp-row-' + assignmentId)?.remove();
                ensureEmptyMessage();
            } catch (error) {
                window.alert('Netzwerkfehler');
            }
        }

        typeSelect?.addEventListener('change', syncPersonOptions);
        syncPersonOptions();

        addButton?.addEventListener('click', async function () {
            var speakerId = personSelect?.value || '';
            if (!speakerId) {
                window.alert('Bitte eine Person wählen.');
                return;
            }

            var fd = new FormData();
            fd.append('csrf_token', speakerBox.dataset.evSpeakerCsrf || '');
            fd.append('event_id', speakerBox.dataset.evSpeakerEventId || '0');
            fd.append('speaker_id', speakerId);
            fd.append('speaker_type', typeSelect?.value || 'speaker');
            fd.append('presentation_title', titleInput?.value || '');
            fd.append('session_time', timeInput?.value || '');

            try {
                var response = await fetch(speakerBox.dataset.evSpeakerEndpointAdd || '', {
                    method: 'POST',
                    body: fd
                });
                var payload = await response.json();
                if (payload.success) {
                    window.location.reload();
                    return;
                }

                window.alert('Fehler: ' + (payload.error || 'Unbekannt'));
            } catch (error) {
                window.alert('Netzwerkfehler: ' + error.message);
            }
        });

        speakerBox.querySelectorAll('[data-ev-speaker-remove]').forEach(function (button) {
            button.addEventListener('click', function () {
                var assignmentId = button.dataset.evSpeakerRemove || '';
                if (!assignmentId) {
                    return;
                }

                if (typeof window.cmsConfirm === 'function') {
                    window.cmsConfirm({
                        title: 'Speaker entfernen?',
                        message: 'Soll die Zuordnung dieser Person wirklich entfernt werden?',
                        confirmText: 'Entfernen',
                        confirmClass: 'btn-danger',
                        onConfirm: function () {
                            removeSpeaker(assignmentId);
                        }
                    });
                    return;
                }

                removeSpeaker(assignmentId);
            });
        });
    }

    function bindTagToggles() {
        document.querySelectorAll('.ev-tag-toggle').forEach(function (label) {
            var input = label.querySelector('input[type="checkbox"]');
            if (!input) {
                return;
            }

            var sync = function () {
                label.classList.toggle('is-selected', input.checked);
            };

            input.addEventListener('change', sync);
            sync();
        });
    }

    function initEventsAdmin() {
        bindConfirmForms();
        bindBadgeStyles();
        bindOverviewModals();
        bindColorFields();
        bindHeaderPreview();
        bindDesignPreviewCard();
        bindEventFormToggles();
        bindMetaBoxLocationToggles();
        bindSpeakerAssignment();
        bindTagToggles();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initEventsAdmin, { once: true });
    } else {
        initEventsAdmin();
    }
})();
