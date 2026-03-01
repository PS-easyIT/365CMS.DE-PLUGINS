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
        modalEl.style.display = 'flex';
        modalEl.innerHTML =
            '<div class="modal-content" style="max-width:420px;">' +
                '<div class="modal-header">' +
                    '<h3>' + escHtml(title) + '</h3>' +
                    '<button class="modal-close" data-modal-close>&times;</button>' +
                '</div>' +
                '<div class="modal-body">' +
                    '<p>' + escHtml(message) + '</p>' +
                '</div>' +
                '<div class="modal-footer">' +
                    '<button type="button" class="btn btn-secondary" data-modal-close>Abbrechen</button>' +
                    '<button type="button" class="btn btn-danger" data-modal-confirm>Bestätigen</button>' +
                '</div>' +
            '</div>';

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

    /* =================================================================== */
    /*  Helfer                                                              */
    /* =================================================================== */

    function escHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

})();
