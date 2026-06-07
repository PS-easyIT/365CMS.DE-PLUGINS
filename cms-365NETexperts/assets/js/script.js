/**
 * CMS Experts - Frontend JavaScript
 * Updated for New Design
 *
 * @package CMS_Experts
 * @version 3.0.4
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        
        // Auto-submit filters on change (optional, matches modern UX)
        const filterBar = document.querySelector('.archive-filter-bar');
        
        if (filterBar) {
            const selects = filterBar.querySelectorAll('select');
            selects.forEach(select => {
                select.addEventListener('change', () => {
                    filterBar.submit();
                });
            });
        }

        document.querySelectorAll('[data-expert-card], .expert-overview-card').forEach(card => {
            const navigate = () => {
                const url = card.getAttribute('data-expert-url');
                if (url) {
                    window.location.href = url;
                    return;
                }

                const link = card.querySelector('.expert-card-cta, .expert-card-title a, .expert-card-name a');
                if (link instanceof HTMLAnchorElement) {
                    window.location.href = link.href;
                }
            };

            card.addEventListener('click', event => {
                const target = event.target;
                if (target instanceof Element && target.closest('a, button, input, select, textarea')) return;
                navigate();
            });

            card.addEventListener('keydown', event => {
                if (event.key !== 'Enter' && event.key !== ' ') return;
                const target = event.target;
                if (target !== card && target instanceof Element && target.closest('a, button, input, select, textarea')) return;
                event.preventDefault();
                navigate();
            });

            card.style.cursor = 'pointer';
        });

    });

})();
