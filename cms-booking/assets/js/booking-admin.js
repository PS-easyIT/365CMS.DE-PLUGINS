/**
 * CMS Booking – Admin JavaScript
 * Modale, Bestätigungs-Dialoge, Interaktionen
 *
 * @package CMS_Booking
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', init);

    function init() {
        initDeleteConfirmation();
        initStatusActions();
        initDeclarativeUi();
    }

    /* =================================================================== */
    /*  CSP-konforme UI-Helfer (ersetzen Inline-Skripte/-Handler)          */
    /*    data-booking-modal-open / -close="id", data-booking-autosubmit,  */
    /*    data-booking-color-preview="previewId"                            */
    /* =================================================================== */

    function initDeclarativeUi() {
        document.addEventListener('click', function (event) {
            var target = event.target instanceof Element ? event.target : null;
            if (!target) {
                return;
            }

            var opener = target.closest('[data-booking-modal-open]');
            if (opener) {
                event.preventDefault();
                toggleModal(opener.getAttribute('data-booking-modal-open'), true);
                return;
            }

            var closer = target.closest('[data-booking-modal-close]');
            if (closer) {
                event.preventDefault();
                toggleModal(closer.getAttribute('data-booking-modal-close'), false);
                return;
            }

            if (target.classList.contains('modal') && target.id && target.hasAttribute('data-booking-modal')) {
                toggleModal(target.id, false);
            }
        });

        document.querySelectorAll('[data-booking-autosubmit]').forEach(function (field) {
            field.addEventListener('change', function () {
                if (field.form) {
                    field.form.submit();
                }
            });
        });

        document.querySelectorAll('[data-booking-color-preview]').forEach(function (input) {
            var preview = document.getElementById(input.getAttribute('data-booking-color-preview') || '');
            if (!preview) {
                return;
            }
            input.addEventListener('input', function () {
                preview.value = input.value;
            });
        });
    }

    function toggleModal(id, show) {
        var modal = id ? document.getElementById(id) : null;
        if (modal) {
            modal.style.display = show ? 'flex' : 'none';
        }
    }

    /* =================================================================== */
    /*  Lösch-Bestätigung (kein window.confirm!)                            */
    /* =================================================================== */

    function initDeleteConfirmation() {
        document.querySelectorAll('form').forEach(function (form) {
            var actionInput = form.querySelector('input[value="delete"]');
            if (!actionInput) return;

            form.addEventListener('submit', function (e) {
                e.preventDefault();
                showConfirmModal(
                    'Wirklich löschen?',
                    'Dieser Vorgang kann nicht rückgängig gemacht werden.',
                    function () { form.submit(); }
                );
            });
        });
    }

    /* =================================================================== */
    /*  Status-Aktionen (confirm, cancel, complete)                         */
    /* =================================================================== */

    function initStatusActions() {
        document.querySelectorAll('[data-booking-action]').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                var action = this.dataset.bookingAction;
                var form = this.closest('form');

                var messages = {
                    'confirm':  'Buchung bestätigen?',
                    'cancel':   'Buchung stornieren?',
                    'complete': 'Buchung als abgeschlossen markieren?',
                    'no_show':  'Als Nicht-Erschienen markieren?'
                };

                showConfirmModal(
                    messages[action] || 'Aktion ausführen?',
                    'Bitte bestätigen Sie die Aktion.',
                    function () { form.submit(); }
                );
            });
        });
    }

    /* =================================================================== */
    /*  Bestätigungs-Modal                                                  */
    /* =================================================================== */

    var modalEl = null;

    function showConfirmModal(title, message, onConfirm) {
        if (modalEl) {
            modalEl.remove();
        }

        modalEl = document.createElement('div');
        modalEl.className = 'modal';
        modalEl.classList.add('booking-confirm-modal');

        var content = document.createElement('div');
        content.className = 'modal-content booking-confirm-modal__content';

        var header = document.createElement('div');
        header.className = 'modal-header';
        var heading = document.createElement('h3');
        heading.textContent = title;
        var closeHeader = document.createElement('button');
        closeHeader.type = 'button';
        closeHeader.className = 'modal-close';
        closeHeader.dataset.modalClose = '';
        closeHeader.setAttribute('aria-label', 'Dialog schließen');
        closeHeader.textContent = '×';
        header.append(heading, closeHeader);

        var body = document.createElement('div');
        body.className = 'modal-body';
        var text = document.createElement('p');
        text.textContent = message;
        body.appendChild(text);

        var footer = document.createElement('div');
        footer.className = 'modal-footer';
        var cancelButton = document.createElement('button');
        cancelButton.type = 'button';
        cancelButton.className = 'btn btn-secondary';
        cancelButton.dataset.modalClose = '';
        cancelButton.textContent = 'Abbrechen';
        var confirmButton = document.createElement('button');
        confirmButton.type = 'button';
        confirmButton.className = 'btn btn-danger';
        confirmButton.dataset.modalConfirm = '';
        confirmButton.textContent = 'Bestätigen';
        footer.append(cancelButton, confirmButton);

        content.append(header, body, footer);
        modalEl.appendChild(content);

        document.body.appendChild(modalEl);

        modalEl.querySelectorAll('[data-modal-close]').forEach(function (btn) {
            btn.addEventListener('click', function () { closeConfirm(); });
        });

        modalEl.querySelector('[data-modal-confirm]').addEventListener('click', function () {
            closeConfirm();
            if (typeof onConfirm === 'function') onConfirm();
        });

        modalEl.addEventListener('click', function (e) {
            if (e.target === modalEl) closeConfirm();
        });
    }

    function closeConfirm() {
        if (modalEl) {
            modalEl.remove();
            modalEl = null;
        }
    }

})();
