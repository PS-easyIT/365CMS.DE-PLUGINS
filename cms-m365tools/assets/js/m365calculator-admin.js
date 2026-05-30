(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function initColorMirrors() {
        document.querySelectorAll('[data-m365-color-copy]').forEach(function (input) {
            var targetId = input.getAttribute('data-m365-color-copy') || '';
            var color = document.getElementById(targetId);

            if (!color) {
                return;
            }

            input.addEventListener('input', function () {
                if (/^#[0-9A-Fa-f]{6}$/.test(input.value)) {
                    color.value = input.value;
                }
            });

            color.addEventListener('input', function () {
                input.value = color.value;
            });
        });
    }

    ready(function () {
        initColorMirrors();
    });
}());
