/**
 * CMS 365NETWORK public interactions.
 *
 * @package CMS_365NETWORK
 */
(function () {
    'use strict';

    function setActive(rotator, nextIndex) {
        var slides = Array.prototype.slice.call(rotator.querySelectorAll('[data-n365-spotlight-slide]'));
        var current = slides.findIndex(function (slide) { return slide.classList.contains('is-active'); });

        if (slides.length === 0) {
            return;
        }

        if (current < 0) {
            current = 0;
        }

        nextIndex = (nextIndex + slides.length) % slides.length;
        slides[current].classList.remove('is-active');
        slides[current].setAttribute('hidden', 'hidden');
        slides[nextIndex].classList.add('is-active');
        slides[nextIndex].removeAttribute('hidden');
    }

    function activeIndex(rotator) {
        return Array.prototype.slice.call(rotator.querySelectorAll('[data-n365-spotlight-slide]')).findIndex(function (slide) {
            return slide.classList.contains('is-active');
        });
    }

    function initSpotlight(rotator) {
        var slides = Array.prototype.slice.call(rotator.querySelectorAll('[data-n365-spotlight-slide]'));
        var autoPlay = rotator.getAttribute('data-autoplay') === '1';
        var timer = null;

        if (slides.length <= 1) {
            return;
        }
        rotator.addEventListener('click', function (event) {
            var button = event.target.closest('[data-n365-spotlight-action]');
            var index;

            if (!button) {
                return;
            }

            stop();
            index = activeIndex(rotator);
            if (button.getAttribute('data-n365-spotlight-action') === 'prev') {
                setActive(rotator, index - 1);
            } else {
                setActive(rotator, index + 1);
            }
            start();
        });

        rotator.addEventListener('mouseenter', stop);
        rotator.addEventListener('mouseleave', start);
        rotator.addEventListener('focusin', stop);
        rotator.addEventListener('focusout', start);

        function next() {
            setActive(rotator, activeIndex(rotator) + 1);
        }

        function start() {
            if (!autoPlay || window.matchMedia('(prefers-reduced-motion: reduce)').matches || timer !== null) {
                return;
            }
            timer = window.setInterval(next, 5500);
        }

        function stop() {
            if (timer === null) {
                return;
            }
            window.clearInterval(timer);
            timer = null;
        }

        start();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-n365-spotlight]').forEach(initSpotlight);
    });
})();
