(function () {
    'use strict';

    function updateLiveRegionStatus(root) {
        var statusNode = root.querySelector('[data-mlc-results-status]');
        if (!statusNode) {
            return;
        }

        var total = parseInt(root.getAttribute('data-mlc-total-results') || '0', 10);
        var template = statusNode.textContent || '%d links found';
        var text = template.indexOf('%d') >= 0
            ? template.replace('%d', String(Math.max(0, total)))
            : template;

        statusNode.textContent = text.trim();
    }

    function initAccessibilityValidation(root) {
        if (!root || root.getAttribute('data-mlc-a11y-validation') !== '1') {
            return;
        }

        updateLiveRegionStatus(root);

        var focusables = root.querySelectorAll('a[href], button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusables.length === 0) {
            console.warn('[mlc a11y] No focusable elements found in public view.');
        }

        var issues = [];
        if (!root.querySelector('.mlc-filter[aria-label]')) {
            issues.push('Filter container misses aria-label.');
        }
        if (root.querySelector('.mlc-table') && !root.querySelector('.mlc-table thead th')) {
            issues.push('Table headings are missing.');
        }
        if (!root.querySelector('[data-mlc-results-status]')) {
            issues.push('Live results status region is missing.');
        }

        if (issues.length > 0) {
            console.warn('[mlc a11y] Validation hints:', issues);
        } else {
            console.info('[mlc a11y] Validation checks passed.');
        }

        root.addEventListener('submit', function () {
            var statusNode = root.querySelector('[data-mlc-results-status]');
            if (statusNode) {
                statusNode.textContent = (statusNode.textContent || '').trim();
            }
        });
    }

    function initRotator(root) {
        var slides = Array.prototype.slice.call(root.querySelectorAll('[data-mlc-sidebar-slide]'));
        if (slides.length <= 1) {
            return;
        }

        var current = Math.max(0, slides.findIndex(function (slide) { return slide.classList.contains('is-active'); }));
        var interval = parseInt(root.getAttribute('data-rotate-interval') || '7000', 10);
        var timer = null;

        function activate(index) {
            current = (index + slides.length) % slides.length;
            slides.forEach(function (slide, slideIndex) {
                var active = slideIndex === current;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                var link = slide.querySelector('a');
                if (link) {
                    link.setAttribute('tabindex', active ? '0' : '-1');
                }
            });
        }

        function start() {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return;
            }
            stop();
            timer = window.setInterval(function () { activate(current + 1); }, Math.max(3000, interval));
        }

        function stop() {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        var prev = root.querySelector('[data-mlc-sidebar-prev]');
        var next = root.querySelector('[data-mlc-sidebar-next]');
        if (prev) {
            prev.addEventListener('click', function () { stop(); activate(current - 1); start(); });
        }
        if (next) {
            next.addEventListener('click', function () { stop(); activate(current + 1); start(); });
        }
        root.addEventListener('mouseenter', stop);
        root.addEventListener('mouseleave', start);
        root.addEventListener('focusin', stop);
        root.addEventListener('focusout', start);
        start();
    }

    document.addEventListener('DOMContentLoaded', function () {
        var pageRoot = document.getElementById('m365-linkcollection');
        if (pageRoot) {
            initAccessibilityValidation(pageRoot);
        }
        document.querySelectorAll('[data-mlc-sidebar-rotator]').forEach(initRotator);
    });
}());
