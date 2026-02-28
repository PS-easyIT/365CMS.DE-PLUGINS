/**
 * CMS Speakers – Frontend JS v2.1.0
 *
 * Vanilla ES2020+, kein jQuery.
 * Nur Frontend-Logik – Admin-Code gehört in die jeweiligen Admin-Views.
 */
(() => {
    'use strict';

    // -- Archive: Filter Auto-Submit ------------------------------------
    document.querySelectorAll('.sp-filter-select').forEach(sel => {
        sel.addEventListener('change', () => sel.form?.submit());
    });

    // -- Archive: Search Reset ------------------------------------------
    const resetBtn = document.querySelector('.sp-filter-reset');
    resetBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        const form = resetBtn.closest('form') ?? document.querySelector('.sp-filter-bar form');
        if (!form) return;
        form.querySelectorAll('input[type="text"], input[type="search"]').forEach(i => { i.value = ''; });
        form.querySelectorAll('select').forEach(s => { s.selectedIndex = 0; });
        form.submit();
    });

    // -- Smooth scroll for anchor links ---------------------------------
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', (e) => {
            const target = document.querySelector(anchor.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // -- Card click: make entire card clickable -------------------------
    document.querySelectorAll('.sp-card').forEach(card => {
        const link = card.querySelector('.sp-card-cta a, a.sp-card-link');
        if (!link) return;
        card.style.cursor = 'pointer';
        card.addEventListener('click', (e) => {
            if (e.target.closest('button, a, input')) return;
            window.location.href = link.href;
        });
    });

})();
