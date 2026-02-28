/**
 * CMS Feed – Admin JavaScript
 *
 * Ergänzende Funktionen für die Admin-Oberfläche.
 * Globale Modal-Funktionen (openModal, closeModal) stammen aus admin.js.
 *
 * @package CMS_Feed
 */

(function() {
    'use strict';

    // ── Settings Sub-Tab-Switching ──────────────────────────────────────
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

    // ── Color-Picker / Text-Input Synchronisation ───────────────────────
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

    // ── Init ────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function() {
        initColorSync();
    });

})();
