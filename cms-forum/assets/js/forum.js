/**
 * CMS Forum – Haupt-JavaScript
 *
 * Handles: Like-Toggle, Subscribe-Toggle, Report-Modal,
 * Spoiler-Reveal, Anchor-Scrolling, Flash-Messages.
 *
 * @package CMS_Forum
 * @version 1.0.0
 */
(function () {
    'use strict';

    /* ── Helpers ────────────────────────────────────────────── */

    const $ = (sel, ctx = document) => ctx.querySelector(sel);
    const $$ = (sel, ctx = document) => [...ctx.querySelectorAll(sel)];

    function showAlert(container, type, msg) {
        if (!container) return;
        const el = document.createElement('div');
        el.className = `cmsforum-alert cmsforum-alert--${type}`;
        el.textContent = msg;
        container.prepend(el);
        setTimeout(() => el.remove(), 6000);
    }

    async function postJSON(url, body) {
        const fd = new FormData();
        if (body instanceof FormData) {
            for (const [k, v] of body.entries()) fd.append(k, String(v));
        } else {
            for (const [k, v] of Object.entries(body)) fd.append(k, String(v));
        }
        const res = await fetch(url, { method: 'POST', body: fd });
        const data = await res.json().catch(() => ({ success: false, error: `HTTP ${res.status}` }));
        if (!res.ok) throw new Error(data.error || `HTTP ${res.status}`);
        return data;
    }

    /* ── Like / Unlike ─────────────────────────────────────── */

    function initLikes() {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-action="like"]');
            if (!btn) return;
            e.preventDefault();

            const postId = btn.dataset.postId || btn.dataset.post;
            const csrfToken = btn.dataset.csrf;
            if (!postId || !csrfToken) return;

            btn.disabled = true;
            try {
                const data = await postJSON('/forum/api/like', {
                    post_id: postId,
                    csrf_token: csrfToken,
                    ajax: '1'
                });
                if (data.success) {
                    const countEl = btn.querySelector('.cmsforum-like-count, .js-like-count');
                    if (countEl) countEl.textContent = data.count ?? data.likes ?? '';
                    btn.classList.toggle('cmsforum-post__action-btn--active', data.liked);
                    btn.title = data.liked ? 'Gefällt mir nicht mehr' : 'Gefällt mir';
                }
            } catch (err) {
                console.error('Like error:', err);
            } finally {
                btn.disabled = false;
            }
        });
    }

    /* ── Subscribe / Unsubscribe ───────────────────────────── */

    function initSubscribe() {
        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-action="subscribe"]');
            if (!btn) return;
            e.preventDefault();

            const itemId = btn.dataset.itemId || btn.dataset.threadId || btn.dataset.id;
            const type = btn.dataset.type || 'thread';
            const csrfToken = btn.dataset.csrf;
            if (!itemId || !csrfToken) return;

            btn.disabled = true;
            try {
                const data = await postJSON('/forum/api/subscribe', {
                    type,
                    item_id: itemId,
                    csrf_token: csrfToken,
                    ajax: '1'
                });
                if (data.success) {
                    btn.classList.toggle('cmsforum-post__action-btn--active', data.subscribed);
                    const label = btn.querySelector('.cmsforum-subscribe-label') || btn;
                    label.textContent = data.subscribed ? 'Abonniert' : 'Abonnieren';
                }
            } catch (err) {
                console.error('Subscribe error:', err);
            } finally {
                btn.disabled = false;
            }
        });
    }

    /* ── Report Modal ──────────────────────────────────────── */

    function initReportModal() {
        const modal = $('#reportModal');
        if (!modal) return;

        const postIdField  = $('#reportPostId', modal);
        const reasonSelect = $('#reportReason', modal);
        const detailField  = $('#reportDetail', modal);
        const form         = $('#reportForm', modal);
        const alertBox     = $('.cmsforum-modal__body', modal);

        // Open
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="report"]');
            if (!btn) return;
            e.preventDefault();
            if (postIdField) postIdField.value = btn.dataset.postId || btn.dataset.post || '';
            if (reasonSelect) reasonSelect.selectedIndex = 0;
            if (detailField) detailField.value = '';
            modal.hidden = false;
            modal.style.display = 'flex';
        });

        // Close
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="close-modal"]') || e.target === modal) {
                modal.style.display = 'none';
                modal.hidden = true;
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                modal.style.display = 'none';
                modal.hidden = true;
            }
        });

        // Submit
        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                fd.append('ajax', '1');

                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;

                try {
                    const data = await postJSON(form.action, fd);
                    if (data.success) {
                        showAlert(alertBox, 'success', 'Meldung wurde gesendet.');
                        setTimeout(() => { modal.style.display = 'none'; modal.hidden = true; }, 1500);
                    } else {
                        showAlert(alertBox, 'error', data.error || 'Fehler beim Senden.');
                    }
                } catch (err) {
                    showAlert(alertBox, 'error', 'Netzwerkfehler: ' + err.message);
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        }
    }

    /* ── Spoiler Reveal ────────────────────────────────────── */

    function initSpoilers() {
        document.addEventListener('click', (e) => {
            const spoiler = e.target.closest('.cmsforum-spoiler');
            if (spoiler) spoiler.classList.toggle('cmsforum-spoiler--revealed');
        });
    }

    /* ── Anchor Scroll (Post-Links) ────────────────────────── */

    function initAnchorScroll() {
        if (!location.hash) return;
        const target = document.getElementById(location.hash.slice(1));
        if (target) {
            requestAnimationFrame(() => {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                target.style.outline = '2px solid var(--cmsforum-primary)';
                setTimeout(() => { target.style.outline = ''; }, 2000);
            });
        }
    }

    /* ── Auto-dismiss Flash Messages ───────────────────────── */

    function initFlashMessages() {
        $$('.cmsforum-alert[data-auto-dismiss]').forEach((el) => {
            const ms = parseInt(el.dataset.autoDismiss, 10) || 5000;
            setTimeout(() => {
                el.style.transition = 'opacity .3s ease';
                el.style.opacity = '0';
                setTimeout(() => el.remove(), 300);
            }, ms);
        });
    }

    /* ── Poll Voting ───────────────────────────────────────── */

    function initPolls() {
        $$('.cmsforum-poll form').forEach((form) => {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                const fd = new FormData(form);
                fd.append('ajax', '1');
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) submitBtn.disabled = true;

                try {
                    const res = await fetch(form.action, { method: 'POST', body: fd });
                    if (res.ok) {
                        // Reload to show results
                        location.reload();
                    }
                } catch (err) {
                    console.error('Poll vote error:', err);
                } finally {
                    if (submitBtn) submitBtn.disabled = false;
                }
            });
        });

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.js-poll-vote');
            if (!btn) return;
            e.preventDefault();

            const pollId = btn.dataset.poll;
            const optionId = btn.dataset.option;
            const csrfToken = btn.dataset.csrf;
            if (!pollId || !optionId || !csrfToken) return;

            btn.disabled = true;
            try {
                const data = await postJSON('/forum/api/poll-vote', {
                    poll_id: pollId,
                    option_ids: optionId,
                    csrf_token: csrfToken,
                    ajax: '1'
                });
                if (data.success) {
                    location.reload();
                }
            } catch (err) {
                console.error('Poll vote error:', err);
            } finally {
                btn.disabled = false;
            }
        });
    }

    /* ── Quote Button ──────────────────────────────────────── */

    function initQuote() {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action="quote"]');
            if (!btn) return;
            e.preventDefault();

            const postEl  = btn.closest('.cmsforum-post');
            if (!postEl) return;

            const author  = postEl.querySelector('.cmsforum-post__author-name')?.textContent?.trim() || '';
            const bodyEl  = postEl.querySelector('.cmsforum-post__body');
            if (!bodyEl) return;

            // Get selected text if any, otherwise first 200 chars
            const sel     = window.getSelection()?.toString()?.trim();
            let text      = sel || bodyEl.textContent.trim().slice(0, 200);
            if (!sel && bodyEl.textContent.trim().length > 200) text += '…';

            const quoteTag = `[quote="${author}"]${text}[/quote]\n\n`;

            const textarea = $('.cmsforum-editor__textarea');
            if (textarea) {
                textarea.value += quoteTag;
                textarea.focus();
                textarea.scrollTop = textarea.scrollHeight;
            }
        });
    }

    /* ── Init ──────────────────────────────────────────────── */

    document.addEventListener('DOMContentLoaded', () => {
        initLikes();
        initSubscribe();
        initReportModal();
        initSpoilers();
        initAnchorScroll();
        initFlashMessages();
        initPolls();
        initQuote();
    });
})();
