/**
 * CMS Feed – Admin JavaScript
 *
 * Modal-Funktionen, Tab-Switching, Color-Sync und Admin-Helfer.
 *
 * @package CMS_Feed
 * @since   1.0.0
 */

(function() {
    'use strict';

    // ══════════════════════════════════════════════════════════════════════
    // Modal-Funktionen (global bereitgestellt)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Modal öffnen (display: flex für zentriertes Layout).
     */
    window.openModal = function(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            // Focus auf erstes Inputfeld setzen
            var firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) {
                setTimeout(function() { firstInput.focus(); }, 100);
            }
        }
    };

    /**
     * Modal schließen.
     */
    window.closeModal = function(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
        }
    };

    // Klick außerhalb des Modal-Inhalts schließt das Modal
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });

    // Escape-Taste schließt offene Modals
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(function(m) {
                if (m.style.display !== 'none' && m.style.display !== '') {
                    m.style.display = 'none';
                }
            });
        }
    });

    // ══════════════════════════════════════════════════════════════════════
    // Settings Sub-Tab-Switching
    // ══════════════════════════════════════════════════════════════════════

    window.switchTab = function(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(function(t) {
            t.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(function(b) {
            b.classList.remove('active');
        });
        var el = document.getElementById(tabId);
        if (el) el.classList.add('active');
        if (btn) btn.classList.add('active');
    };

    // ══════════════════════════════════════════════════════════════════════
    // Color-Picker / Text-Input Synchronisation
    // ══════════════════════════════════════════════════════════════════════

    function initColorSync() {
        document.querySelectorAll('input[type="color"]').forEach(function(picker) {
            var textInput = picker.nextElementSibling;
            if (!textInput || textInput.type !== 'text') return;

            picker.addEventListener('input', function() {
                textInput.value = this.value;
            });

            textInput.addEventListener('input', function() {
                if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                    picker.value = this.value;
                }
            });
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    // Bulk-Actions: Kanäle
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Alle Kanal-Checkboxen (de)markieren.
     */
    window.toggleAllChannels = function(checked) {
        document.querySelectorAll('.channel-checkbox').forEach(function(cb) {
            cb.checked = checked;
        });
        updateChannelBulk();
    };

    /**
     * Bulk-Bar aktualisieren nach Checkbox-Änderung.
     */
    window.updateChannelBulk = function() {
        var checked = document.querySelectorAll('.channel-checkbox:checked');
        var bar = document.getElementById('channelBulkBar');
        var count = document.getElementById('channelBulkCount');
        var selectAll = document.getElementById('channelSelectAll');

        if (bar) {
            bar.style.display = checked.length > 0 ? 'flex' : 'none';
        }
        if (count) {
            count.textContent = checked.length;
        }
        // Sync "alle markieren" Checkbox
        if (selectAll) {
            var total = document.querySelectorAll('.channel-checkbox').length;
            selectAll.checked = checked.length === total && total > 0;
            selectAll.indeterminate = checked.length > 0 && checked.length < total;
        }
    };

    /**
     * Bulk-Action für Kanäle absenden.
     */
    window.submitChannelBulk = function(action) {
        var checked = document.querySelectorAll('.channel-checkbox:checked');
        if (checked.length === 0) return;

        var actionLabels = {
            'bulk_fetch_channels': 'abrufen (max. 5 sofort, Rest per Cron)',
            'bulk_activate_channels': 'aktivieren',
            'bulk_deactivate_channels': 'deaktivieren',
            'bulk_delete_channels': 'löschen (inkl. aller Beiträge)'
        };

        var label = actionLabels[action] || action;
        openBulkConfirmModal(
            checked.length + ' Kanal/Kanäle ' + label + '?',
            action === 'bulk_delete_channels',
            function() {
                var form = document.getElementById('channelBulkForm');
                var idContainer = document.getElementById('channelBulkIds');
                document.getElementById('channelBulkAction').value = action;
                idContainer.innerHTML = '';
                checked.forEach(function(cb) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'bulk_ids[]';
                    input.value = cb.value;
                    idContainer.appendChild(input);
                });
                form.submit();
            }
        );
    };

    // ══════════════════════════════════════════════════════════════════════
    // Bulk-Actions: Bereiche
    // ══════════════════════════════════════════════════════════════════════

    window.toggleAllCategories = function(checked) {
        document.querySelectorAll('.category-checkbox').forEach(function(cb) {
            cb.checked = checked;
        });
        updateCategoryBulk();
    };

    window.updateCategoryBulk = function() {
        var checked = document.querySelectorAll('.category-checkbox:checked');
        var bar = document.getElementById('categoryBulkBar');
        var count = document.getElementById('categoryBulkCount');
        var selectAll = document.getElementById('categorySelectAll');

        if (bar) {
            bar.style.display = checked.length > 0 ? 'flex' : 'none';
        }
        if (count) {
            count.textContent = checked.length;
        }
        if (selectAll) {
            var total = document.querySelectorAll('.category-checkbox').length;
            selectAll.checked = checked.length === total && total > 0;
            selectAll.indeterminate = checked.length > 0 && checked.length < total;
        }
    };

    window.submitCategoryBulk = function(action) {
        var checked = document.querySelectorAll('.category-checkbox:checked');
        if (checked.length === 0) return;

        openBulkConfirmModal(
            checked.length + ' Bereich/Bereiche löschen (inkl. aller Kanäle und Beiträge)?',
            true,
            function() {
                var form = document.getElementById('categoryBulkForm');
                var idContainer = document.getElementById('categoryBulkIds');
                document.getElementById('categoryBulkAction').value = action;
                idContainer.innerHTML = '';
                checked.forEach(function(cb) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'bulk_ids[]';
                    input.value = cb.value;
                    idContainer.appendChild(input);
                });
                form.submit();
            }
        );
    };

    // ══════════════════════════════════════════════════════════════════════
    // Bulk-Bestätigungsdialog (eigenes Modal statt window.confirm)
    // ══════════════════════════════════════════════════════════════════════

    var pendingBulkCallback = null;

    function openBulkConfirmModal(message, isDanger, onConfirm) {
        var modal = document.getElementById('bulkConfirmModal');
        if (!modal) {
            // Modal dynamisch erstellen, falls noch nicht vorhanden
            modal = document.createElement('div');
            modal.id = 'bulkConfirmModal';
            modal.className = 'modal';
            modal.style.display = 'none';
            modal.innerHTML = '<div class="modal-content" style="max-width:480px;">'
                + '<div class="modal-header"><h3 id="bulkConfirmTitle">⚠️ Bulk-Aktion bestätigen</h3>'
                + '<button class="modal-close" onclick="closeModal(\'bulkConfirmModal\')">&times;</button></div>'
                + '<div class="modal-body"><p id="bulkConfirmMessage"></p>'
                + '<p id="bulkConfirmWarning" style="color:#ef4444;font-size:.875rem;display:none;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p></div>'
                + '<div class="modal-footer">'
                + '<button type="button" class="btn btn-secondary" onclick="closeModal(\'bulkConfirmModal\')">Abbrechen</button>'
                + '<button type="button" id="bulkConfirmBtn" class="btn btn-primary" onclick="executeBulkAction()">✅ Bestätigen</button>'
                + '</div></div>';
            document.body.appendChild(modal);
        }

        document.getElementById('bulkConfirmMessage').textContent = message;
        document.getElementById('bulkConfirmWarning').style.display = isDanger ? 'block' : 'none';
        var btn = document.getElementById('bulkConfirmBtn');
        btn.className = isDanger ? 'btn btn-danger' : 'btn btn-primary';
        btn.textContent = isDanger ? '🗑️ Endgültig löschen' : '✅ Bestätigen';

        pendingBulkCallback = onConfirm;
        openModal('bulkConfirmModal');
    }

    window.executeBulkAction = function() {
        closeModal('bulkConfirmModal');
        if (typeof pendingBulkCallback === 'function') {
            pendingBulkCallback();
            pendingBulkCallback = null;
        }
    };

    // ══════════════════════════════════════════════════════════════════════
    // Init
    // ══════════════════════════════════════════════════════════════════════

    document.addEventListener('DOMContentLoaded', function() {
        initColorSync();
    });

})();
