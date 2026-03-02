/**
 * CMS Forum – Notifications
 *
 * Polls for new forum notifications and shows an indicator badge.
 *
 * @package CMS_Forum
 * @version 1.0.0
 */
(function () {
    'use strict';

    const POLL_INTERVAL = 60000; // 60 seconds
    let pollTimer = null;

    /**
     * Check for unread notification count.
     */
    async function checkNotifications() {
        const badge = document.querySelector('.cmsforum-notification-badge');
        if (!badge) return;

        const url = badge.dataset.pollUrl;
        if (!url) return;

        try {
            const res = await fetch(url, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!res.ok) return;
            const data = await res.json();

            if (data.success && typeof data.unread === 'number') {
                if (data.unread > 0) {
                    badge.textContent = data.unread > 99 ? '99+' : String(data.unread);
                    badge.style.display = '';
                    badge.removeAttribute('hidden');
                } else {
                    badge.style.display = 'none';
                    badge.setAttribute('hidden', '');
                }
            }
        } catch {
            // Silently fail – user experience > error noise
        }
    }

    /**
     * Start polling if badge element exists.
     */
    function startPolling() {
        const badge = document.querySelector('.cmsforum-notification-badge');
        if (!badge) return;

        checkNotifications();
        pollTimer = setInterval(checkNotifications, POLL_INTERVAL);

        // Pause when tab is hidden
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                clearInterval(pollTimer);
                pollTimer = null;
            } else {
                checkNotifications();
                pollTimer = setInterval(checkNotifications, POLL_INTERVAL);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', startPolling);
})();
