/**
 * CMS Contact – Admin JavaScript
 * Drag-Drop-Sortierung und sonstige Admin-Interaktionen.
 *
 * @package CMS_Contact
 * @version 1.0.0
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initAutoSlug();
        initColorSync();
    });

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
        });
    }
})();
