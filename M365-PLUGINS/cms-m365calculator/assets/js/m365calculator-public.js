(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function initResultFocus() {
        var result = document.querySelector('[data-m365calc-result]');
        if (!result || !window.location.pathname.includes('shared-mailbox-vs-lizenz')) {
            return;
        }

        result.setAttribute('tabindex', '-1');
        result.focus({ preventScroll: true });
        result.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function initPrintButtons() {
        document.querySelectorAll('[data-m365calc-print]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.print();
            });
        });
    }

    function initResetButtons() {
        document.querySelectorAll('[data-m365calc-reset]').forEach(function (button) {
            button.addEventListener('click', function () {
                var form = button.closest('form');
                if (!form) {
                    return;
                }

                form.reset();
            });
        });
    }

    ready(function () {
        initResultFocus();
        initPrintButtons();
        initResetButtons();
    });
}());
