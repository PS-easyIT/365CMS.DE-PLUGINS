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

        function applyFilters() {
            const selectedTopic = normalize(topicSelect?.value);
            const searchTerm = normalize(searchInput?.value);
            let visibleCount = 0;

            cards.forEach((card) => {
                const matchesTopic = !selectedTopic || normalize(card.dataset.topic).includes(selectedTopic);
                const matchesSearch = !searchTerm || normalize(card.dataset.name).includes(searchTerm);
                const isVisible = matchesTopic && matchesSearch;

                card.classList.toggle('hidden', !isVisible);
                if (isVisible) {
                    visibleCount += 1;
                }
            });

            if (empty) {
                empty.hidden = visibleCount > 0;
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

        topicSelect?.addEventListener('change', applyFilters);
        searchInput?.addEventListener('input', applyFilters);
        root.querySelectorAll('[data-cms-speaker-reset]').forEach((button) => {
            button.addEventListener('click', resetFilters);
        });

        applyFilters();
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
        bindLegacyInteractions();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init, { once: true });
    } else {
        init();
    }
})();
