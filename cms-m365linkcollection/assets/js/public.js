(function () {
    'use strict';

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
        document.querySelectorAll('[data-mlc-sidebar-rotator]').forEach(initRotator);
    });
}());
