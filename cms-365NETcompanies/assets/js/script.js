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
            const navigate = () => {
                const url = card.getAttribute('data-company-url');
                if (url) {
                    window.location.href = url;
                    return;
                }

                const link = card.querySelector('.company-card-button, .co-card-footer a, .co-card-head a, .company-card-title a');
                if (link instanceof HTMLAnchorElement) {
                    window.location.href = link.href;
                }
            };

            card.addEventListener('click', (e) => {
                if (e.target.closest('a, button, input, select, textarea')) return;
                navigate();
            });

            card.addEventListener('keydown', (e) => {
                if (e.key !== 'Enter' && e.key !== ' ') return;
                if (e.target !== card && e.target.closest('a, button, input, select, textarea')) return;
                e.preventDefault();
                navigate();
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
