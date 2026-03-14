/**
 * CMS Feed – Public JavaScript
 *
 * @package CMS_Feed
 */

(function() {
    'use strict';

    // Lazy-Load für Bilder (Intersection Observer Fallback)
    function initLazyImages() {
        const images = document.querySelectorAll('.fd-card__image[loading="lazy"]');
        if (!images.length || 'loading' in HTMLImageElement.prototype) return;

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    if (img.dataset.src) {
                        img.src = img.dataset.src;
                    }
                    observer.unobserve(img);
                }
            });
        }, { rootMargin: '100px' });

        images.forEach(function(img) { observer.observe(img); });
    }

    // Fehlendes Bild ausblenden (Platzhalter verhindern)
    function initImageErrorHandler() {
        document.querySelectorAll('.fd-card__image').forEach(function(img) {
            img.addEventListener('error', function() {
                const link = this.closest('.fd-card__image-link');
                if (link) link.style.display = 'none';
            });
        });
    }

    // Suche: ESC zum Leeren
    function initSearchEsc() {
        const input = document.querySelector('.fd-search__input');
        if (!input) return;
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                this.focus();
            }
        });
    }

    function hasFeedConsent(detail) {
        if (!detail || typeof detail !== 'object') {
            return true;
        }

        const acceptedCategories = Array.isArray(detail.acceptedCategories) ? detail.acceptedCategories : [];
        const acceptedServicesMap = detail.acceptedServices && typeof detail.acceptedServices === 'object'
            ? detail.acceptedServices
            : {};
        const acceptedServices = Object.values(acceptedServicesMap).flatMap(function(services) {
            return Array.isArray(services) ? services : [];
        });

        return acceptedCategories.includes('external_media') || acceptedServices.includes('cms_feed');
    }

    function initConsentGuard() {
        window.addEventListener('cms-cookie-consent-change', function(event) {
            if (!hasFeedConsent(event.detail)) {
                window.location.reload();
            }
        });
    }

    // Init
    document.addEventListener('DOMContentLoaded', function() {
        initLazyImages();
        initImageErrorHandler();
        initSearchEsc();
        initConsentGuard();
    });

})();
