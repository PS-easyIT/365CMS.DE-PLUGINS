/**
 * CMS Forum – Admin-Modals und Lösch-Bestätigungen
 *
 * CSP-konformer Ersatz für die früheren Inline-Skripte/onclick-Handler:
 *   data-forum-modal-open="modalId"     öffnet ein Modal
 *   data-forum-fill='{"feld-id": wert}' befüllt vor dem Öffnen Felder
 *                                        (Input → value, Checkbox → checked, sonst Text)
 *   data-forum-modal-close="modalId"    schließt ein Modal
 *   data-forum-delete-confirm="Text"    öffnet #deleteConfirmModal für das umgebende Formular
 *   data-forum-confirm-delete           sendet das vorgemerkte Formular ab
 */
(function () {
    'use strict';

    var pendingDeleteForm = null;

    function openModal(id) {
        var modal = id ? document.getElementById(id) : null;
        if (modal) {
            modal.style.display = 'flex';
        }
    }

    function closeModal(id) {
        var modal = id ? document.getElementById(id) : null;
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function fillFields(raw) {
        if (!raw) {
            return;
        }

        var values;
        try {
            values = JSON.parse(raw);
        } catch (error) {
            return;
        }

        Object.keys(values || {}).forEach(function (id) {
            var field = document.getElementById(id);
            if (!field) {
                return;
            }

            var value = values[id];
            if (field instanceof HTMLInputElement && field.type === 'checkbox') {
                field.checked = value === true || value === 1 || value === '1';
            } else if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
                field.value = value === null || value === undefined ? '' : String(value);
            } else {
                field.textContent = value === null || value === undefined ? '' : String(value);
            }
        });
    }

    document.addEventListener('click', function (event) {
        var target = event.target instanceof Element ? event.target : null;
        if (!target) {
            return;
        }

        var opener = target.closest('[data-forum-modal-open]');
        if (opener) {
            event.preventDefault();
            fillFields(opener.getAttribute('data-forum-fill'));
            openModal(opener.getAttribute('data-forum-modal-open'));
            return;
        }

        var closer = target.closest('[data-forum-modal-close]');
        if (closer) {
            event.preventDefault();
            closeModal(closer.getAttribute('data-forum-modal-close'));
            return;
        }

        var deleteTrigger = target.closest('[data-forum-delete-confirm]');
        if (deleteTrigger) {
            event.preventDefault();
            pendingDeleteForm = deleteTrigger.closest('form');
            var message = document.getElementById('deleteConfirmMsg');
            if (message) {
                message.textContent = deleteTrigger.getAttribute('data-forum-delete-confirm') || '';
            }
            openModal('deleteConfirmModal');
            return;
        }

        if (target.closest('[data-forum-confirm-delete]')) {
            event.preventDefault();
            if (pendingDeleteForm) {
                pendingDeleteForm.submit();
            }
            closeModal('deleteConfirmModal');
            return;
        }

        if (target.classList.contains('modal') && target.id) {
            closeModal(target.id);
        }
    });
})();
