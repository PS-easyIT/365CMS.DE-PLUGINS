/**
 * CMS Experts - Frontend JavaScript
 * Updated for New Design
 *
 * @package CMS_Experts
 * @version 2.0.0
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

    });

})();
