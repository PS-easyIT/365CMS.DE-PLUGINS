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

    function initSearchKeyboard() {
        var page = document.querySelector('[data-n365-search-page]');
        var input = document.getElementById('n365-search-query');
        var liveStatus = document.querySelector('[data-n365-search-live-status]');
        var links;
        var livePrefix;
        var liveOf;

        if (!page || !input) {
            return;
        }

        links = Array.prototype.slice.call(document.querySelectorAll('[data-n365-search-result-link]')).filter(function (link) {
            return link && typeof link.focus === 'function';
        });
        livePrefix = page.getAttribute('data-live-prefix') || 'Treffer';
        liveOf = page.getAttribute('data-live-of') || 'von';

        function announce(link) {
            var label;
            var index;
            if (!liveStatus || !link) {
                return;
            }

            index = links.indexOf(link);
            if (index < 0) {
                return;
            }

            label = (link.textContent || '').trim();
            liveStatus.textContent = livePrefix + ' ' + (index + 1) + ' ' + liveOf + ' ' + links.length + ': ' + label;
        }

        function focusResult(index) {
            if (links.length === 0) {
                return;
            }
            index = (index + links.length) % links.length;
            links[index].focus();
            announce(links[index]);
        }

        input.addEventListener('keydown', function (event) {
            if (links.length === 0) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                focusResult(0);
            }
        });

        document.addEventListener('keydown', function (event) {
            var target = event.target;
            var index;
            if (target === input && event.key !== 'ArrowDown') {
                return;
            }
            if (!target || !target.matches || !target.matches('[data-n365-search-result-link]')) {
                return;
            }

            index = links.indexOf(target);
            if (index < 0) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                focusResult(index + 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                if (index === 0) {
                    input.focus();
                    return;
                }
                focusResult(index - 1);
            } else if (event.key === 'Home') {
                event.preventDefault();
                focusResult(0);
            } else if (event.key === 'End') {
                event.preventDefault();
                focusResult(links.length - 1);
            }
        });

        links.forEach(function (link) {
            link.addEventListener('focus', function () {
                announce(link);
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-n365-spotlight]').forEach(initSpotlight);
        initSearchKeyboard();
    });
})();
