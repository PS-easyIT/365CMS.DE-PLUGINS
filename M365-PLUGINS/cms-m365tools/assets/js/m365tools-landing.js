(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
            return;
        }

        callback();
    }

    function normalize(value) {
        return String(value || '').trim().toLowerCase();
    }

    function slugify(value) {
        return normalize(value)
            .replace(/ä/g, 'ae')
            .replace(/ö/g, 'oe')
            .replace(/ü/g, 'ue')
            .replace(/ß/g, 'ss')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    function scrollBehavior() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
    }

    function isTypingTarget(element) {
        if (!element) {
            return false;
        }

        var tag = String(element.tagName || '').toUpperCase();
        return tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || element.isContentEditable;
    }

    function initContentHost(root) {
        var host = root.closest('#content, .site-content');
        if (!host) {
            return;
        }

        host.classList.add('m365tools-content-host');

        var header = document.getElementById('masthead');
        if (!header || !header.parentElement || header.parentElement !== host.parentElement) {
            return;
        }

        var node = header.nextElementSibling;
        while (node && node !== host) {
            if (!node.matches('.mobile-menu-overlay, .mobile-menu-drawer, .search-overlay')) {
                node.classList.add('m365tools-header-interstitial');
                node.setAttribute('aria-hidden', 'true');
            }
            node = node.nextElementSibling;
        }
    }

    function initFinder(root) {
        var search = root.querySelector('[data-m365tools-search]');
        var chips = Array.prototype.slice.call(root.querySelectorAll('[data-m365tools-tag]'));
        var cards = Array.prototype.slice.call(root.querySelectorAll('[data-m365tools-card]'));
        var sections = Array.prototype.slice.call(root.querySelectorAll('[data-m365tools-section]'));
        var sectionCards = sections.map(function (section) {
            return {
                section: section,
                cards: Array.prototype.slice.call(section.querySelectorAll('[data-m365tools-card]'))
            };
        });
        var count = root.querySelector('[data-m365tools-result-count]');
        var empty = root.querySelector('[data-m365tools-empty]');
        var group = root.querySelector('[data-m365tools-chip-group]');
        var activeTag = 'all';

        function tagFromHash() {
            var hash = slugify(window.location.hash.replace(/^#/, '').replace(/^cat-/, ''));
            var match = chips.find(function (chip) {
                return slugify(chip.getAttribute('data-m365tools-tag')) === hash;
            });

            return match ? normalize(match.getAttribute('data-m365tools-tag')) : '';
        }

        function writeHash(tag) {
            if (!window.history || !window.history.replaceState) {
                return;
            }

            var slug = slugify(tag || 'all') || 'all';
            window.history.replaceState(null, '', '#' + slug);
        }

        function setChipState() {
            chips.forEach(function (chip) {
                var checked = normalize(chip.getAttribute('data-m365tools-tag')) === activeTag;
                chip.classList.toggle('is-active', checked);
                chip.setAttribute('aria-checked', checked ? 'true' : 'false');
                chip.setAttribute('tabindex', checked ? '0' : '-1');
            });
        }

        function update() {
            var query = normalize(search ? search.value : '');
            var visibleCards = 0;

            cards.forEach(function (card) {
                var category = normalize(card.getAttribute('data-m365tools-category'));
                var text = normalize(card.getAttribute('data-m365tools-search-value'));
                var matchesTag = activeTag === 'all' || category === activeTag;
                var matchesQuery = query === '' || text.indexOf(query) !== -1;
                var visible = matchesTag && matchesQuery;

                card.hidden = !visible;
                if (visible) {
                    visibleCards += 1;
                }
            });

            sectionCards.forEach(function (entry) {
                var visibleInSection = entry.cards.some(function (card) {
                    return !card.hidden;
                });
                entry.section.hidden = !visibleInSection;
            });

            if (count) {
                count.textContent = visibleCards + (visibleCards === 1 ? ' Tool sichtbar' : ' Tools sichtbar');
            }
            if (empty) {
                empty.hidden = visibleCards !== 0;
            }
        }

        function setActiveTag(tag, writeLocation) {
            activeTag = normalize(tag) || 'all';
            setChipState();
            update();
            if (writeLocation) {
                writeHash(activeTag);
            }
        }

        if (search) {
            search.addEventListener('input', update);
        }

        chips.forEach(function (chip) {
            chip.addEventListener('click', function () {
                setActiveTag(chip.getAttribute('data-m365tools-tag'), true);
            });
        });

        if (group && chips.length > 0) {
            group.addEventListener('keydown', function (event) {
                var keys = ['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp', 'Home', 'End'];
                if (keys.indexOf(event.key) === -1) {
                    return;
                }

                event.preventDefault();
                var currentIndex = Math.max(0, chips.indexOf(document.activeElement));
                var nextIndex = currentIndex;

                if (event.key === 'ArrowRight' || event.key === 'ArrowDown') {
                    nextIndex = (currentIndex + 1) % chips.length;
                } else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') {
                    nextIndex = (currentIndex - 1 + chips.length) % chips.length;
                } else if (event.key === 'Home') {
                    nextIndex = 0;
                } else if (event.key === 'End') {
                    nextIndex = chips.length - 1;
                }

                chips[nextIndex].focus();
                setActiveTag(chips[nextIndex].getAttribute('data-m365tools-tag'), true);
            });
        }

        setActiveTag(tagFromHash() || 'all', false);
    }

    function initCardLinks(root) {
        function safeCardUrl(value) {
            var raw = String(value || '').trim();
            if (raw === '' || /[\u0000-\u001F\u007F]/.test(raw)) {
                return '';
            }

            try {
                var parsed = new URL(raw, window.location.origin);
                if ((parsed.protocol !== 'http:' && parsed.protocol !== 'https:') || parsed.origin !== window.location.origin) {
                    return '';
                }

                return parsed.pathname + parsed.search + parsed.hash;
            } catch (error) {
                return '';
            }
        }

        root.querySelectorAll('[data-m365tools-card-url]').forEach(function (card) {
            card.addEventListener('click', function (event) {
                if (event.target.closest('a, button, input, textarea, select')) {
                    return;
                }

                var destination = safeCardUrl(card.getAttribute('data-m365tools-card-url'));
                var link = card.querySelector('a[href]');
                if (destination !== '' && link && safeCardUrl(link.getAttribute('href')) === destination) {
                    link.click();
                }
            });
        });
    }

    function initToc(root) {
        var links = Array.prototype.slice.call(root.querySelectorAll('[data-m365tools-toc-link]'));
        var sections = Array.prototype.slice.call(root.querySelectorAll('[data-m365tools-section]'));

        if (links.length === 0 || sections.length === 0 || !('IntersectionObserver' in window)) {
            return;
        }

        var byId = new Map();
        links.forEach(function (link) {
            byId.set(link.getAttribute('data-m365tools-toc-link'), link);
        });

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                var heading = entry.target.querySelector('h2[id]');
                var id = heading ? heading.id : '';
                links.forEach(function (link) {
                    link.classList.toggle('is-active', link === byId.get(id));
                });
            });
        }, { threshold: 0.3 });

        sections.forEach(function (section) {
            observer.observe(section);
        });
    }

    function initBackToTop(root) {
        var button = root.querySelector('[data-m365tools-top]');
        if (!button) {
            return;
        }

        function update() {
            button.classList.toggle('is-visible', window.scrollY > 400);
        }

        button.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: scrollBehavior() });
        });
        window.addEventListener('scroll', update, { passive: true });
        update();
    }

    function initHeroSearch(root) {
        var button = root.querySelector('[data-m365tools-primary-search]');
        var target = document.getElementById('direkteinstieg');
        var search = document.getElementById('tool-search');

        if (!button || !target || !search) {
            return;
        }

        button.addEventListener('click', function (event) {
            event.preventDefault();
            var behavior = scrollBehavior();
            target.scrollIntoView({ behavior: behavior, block: 'start' });
            window.setTimeout(function () {
                search.focus({ preventScroll: true });
            }, behavior === 'smooth' ? 260 : 0);
        });
    }

    function initSearchShortcut() {
        var search = document.getElementById('tool-search');
        if (!search) {
            return;
        }

        document.addEventListener('keydown', function (event) {
            if (event.key !== '/' || isTypingTarget(document.activeElement)) {
                return;
            }

            event.preventDefault();
            search.focus();
        });
    }

    ready(function () {
        var root = document.getElementById('m365tools-landing');
        if (!root) {
            return;
        }

        initContentHost(root);
        initFinder(root);
        initHeroSearch(root);
        initSearchShortcut();
        initCardLinks(root);
        initToc(root);
        initBackToTop(root);
    });
}());
