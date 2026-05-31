/**
 * CMS Speakers – Frontend JS v2.1.0
 *
 * Vanilla ES2020+, kein jQuery.
 * Nur Frontend-Logik – Admin-Code gehört in die jeweiligen Admin-Views.
 */
(() => {
    'use strict';

    const normalize = (value) => String(value || '').trim().toLocaleLowerCase();

    function bindPublicSpeakerFilters() {
        const root = document.querySelector('[data-cms-speaker-filter-root]');
        if (!root) {
            return;
        }

        const cards = Array.from(root.querySelectorAll('[data-cms-speaker-card]'));
        const empty = root.querySelector('[data-cms-speaker-empty]');
        const topicSelect = root.querySelector('[data-cms-speaker-filter="topic"]');
        const searchInput = root.querySelector('[data-cms-speaker-filter="search"]');
        const applyButtons = root.querySelectorAll('[data-cms-speaker-apply]');

        function applyFilters() {
            const selectedTopic = normalize(topicSelect?.value);
            const searchTerm = normalize(searchInput?.value);
            let visibleCount = 0;

            cards.forEach((card) => {
                const cardCategory = card.dataset.category || card.dataset.topic;
                const matchesTopic = !selectedTopic || normalize(cardCategory).includes(selectedTopic);
                const matchesSearch = !searchTerm || normalize(card.dataset.name).includes(searchTerm);
                const isVisible = matchesTopic && matchesSearch;

                card.classList.toggle('hidden', !isVisible);
                card.style.display = isVisible ? 'flex' : 'none';
                card.querySelectorAll('[data-topic-value]').forEach((tag) => {
                    tag.classList.toggle('is-active', selectedTopic !== '' && normalize(tag.dataset.topicValue) === selectedTopic);
                });
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (empty) {
                empty.hidden = visibleCount > 0;
                empty.style.display = visibleCount > 0 ? 'none' : 'block';
            }
        }

        function resetFilters() {
            if (topicSelect) {
                topicSelect.value = '';
            }
            if (searchInput) {
                searchInput.value = '';
            }
            applyFilters();
        }

        [topicSelect, searchInput].forEach((control) => {
            control?.addEventListener('input', applyFilters);
            control?.addEventListener('change', applyFilters);
        });
        root.querySelectorAll('[data-cms-speaker-reset]').forEach((button) => {
            button.addEventListener('click', resetFilters);
        });
        applyButtons.forEach((button) => {
            button.addEventListener('click', applyFilters);
        });

        applyFilters();
    }

    function bindClickableSpeakerCards() {
        document.querySelectorAll('[data-cms-speaker-card][data-speaker-url], .speaker-card[data-href]').forEach((card) => {
            const navigate = () => {
                const url = card.getAttribute('data-href') || card.getAttribute('data-speaker-url');
                if (url) {
                    window.location.href = url;
                }
            };

            card.addEventListener('click', (event) => {
                if (event.target instanceof Element && event.target.closest('a, button, input, select, textarea')) {
                    return;
                }

                navigate();
            });

            card.addEventListener('keydown', (event) => {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    navigate();
                }
            });
        });
    }

    function bindLegacyInteractions() {
        document.querySelectorAll('.sp-filter-select').forEach((select) => {
            select.addEventListener('change', () => select.form?.submit());
        });

        const resetBtn = document.querySelector('.sp-filter-reset');
        resetBtn?.addEventListener('click', (event) => {
            event.preventDefault();
            const form = resetBtn.closest('form') ?? document.querySelector('.sp-filter-bar form');
            if (!form) {
                return;
            }
            form.querySelectorAll('input[type="text"], input[type="search"]').forEach((input) => { input.value = ''; });
            form.querySelectorAll('select').forEach((select) => { select.selectedIndex = 0; });
            form.submit();
        });

        document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
            anchor.addEventListener('click', (event) => {
                const selector = anchor.getAttribute('href');
                if (!selector || selector === '#') {
                    return;
                }
                const target = document.querySelector(selector);
                if (target) {
                    event.preventDefault();
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    }

    function init() {
        bindPublicSpeakerFilters();
        bindClickableSpeakerCards();
        bindLegacyInteractions();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
