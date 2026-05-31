/**
 * CMS 365NETWORK public interactions.
 *
 * @package CMS_365NETWORK
 */
(function () {
    'use strict';

    function setActive(rotator, nextIndex) {
        var slides = Array.prototype.slice.call(rotator.querySelectorAll('[data-n365-spotlight-slide]'));
        var dots = Array.prototype.slice.call(rotator.querySelectorAll('[data-n365-spotlight-dot]'));
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

        dots.forEach(function (dot, index) {
            dot.classList.toggle('is-active', index === nextIndex);
            dot.setAttribute('aria-current', index === nextIndex ? 'true' : 'false');
        });
    }

    function activeIndex(rotator) {
        return Array.prototype.slice.call(rotator.querySelectorAll('[data-n365-spotlight-slide]')).findIndex(function (slide) {
            return slide.classList.contains('is-active');
        });
    }

    function initSpotlight(rotator) {
        var slides = Array.prototype.slice.call(rotator.querySelectorAll('[data-n365-spotlight-slide]'));
        var dotsWrap = rotator.querySelector('[data-n365-spotlight-dots]');
        var autoPlay = rotator.getAttribute('data-autoplay') === '1';
        var timer = null;

        if (slides.length <= 1) {
            return;
        }

        if (dotsWrap && dotsWrap.children.length === 0) {
            slides.forEach(function (_, index) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'n365-spotlight-dot' + (index === 0 ? ' is-active' : '');
                dot.setAttribute('data-n365-spotlight-dot', '');
                dot.setAttribute('aria-label', 'Fokus-Eintrag ' + (index + 1) + ' anzeigen');
                dot.setAttribute('aria-current', index === 0 ? 'true' : 'false');
                dot.addEventListener('click', function () {
                    stop();
                    setActive(rotator, index);
                    start();
                });
                dotsWrap.appendChild(dot);
            });
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
