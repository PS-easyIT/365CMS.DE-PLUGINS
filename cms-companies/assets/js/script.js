/**
 * CMS Companies – Frontend JavaScript
 *
 * @package CMS_Companies
 * @version 2.1.0
 */
(() => {
    'use strict';

    document.addEventListener('DOMContentLoaded', () => {

        /* ── Filter auto-submit ──────────────────────── */
        document.querySelectorAll('.co-filter-select').forEach(sel => {
            sel.addEventListener('change', () => sel.form?.submit());
        });

        /* ── Filter reset ────────────────────────────── */
        const resetBtn = document.querySelector('.co-filter-reset');
        if (resetBtn) {
            resetBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const form = resetBtn.closest('form');
                if (!form) { window.location.href = window.location.pathname; return; }
                form.querySelectorAll('input[type="text"], input[type="search"]').forEach(i => { i.value = ''; });
                form.querySelectorAll('select').forEach(s => { s.selectedIndex = 0; });
                form.submit();
            });
        }

        /* ── Card click → detail link ────────────────── */
        document.querySelectorAll('.co-card').forEach(card => {
            card.addEventListener('click', (e) => {
                if (e.target.closest('a, button')) return;
                const link = card.querySelector('.co-card-footer a, .co-card-head a');
                if (link) window.location.href = link.href;
            });
            card.style.cursor = 'pointer';
        });

        /* ── Smooth scroll for anchor links ──────────── */
        document.querySelectorAll('a[href^="#"]').forEach(a => {
            a.addEventListener('click', (e) => {
                const target = document.querySelector(a.getAttribute('href'));
                if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
            });
        });
    });
})();
