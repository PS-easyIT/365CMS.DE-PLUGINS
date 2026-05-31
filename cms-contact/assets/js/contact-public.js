/**
 * CMS Contact – Public JavaScript
 * Clientseitige Formularvalidierung und UX-Verbesserungen.
 *
 * @package CMS_Contact
 * @version 1.0.0
 */
(function () {
    'use strict';

    var currentLang = getCurrentLang();

    document.addEventListener('DOMContentLoaded', function () {
        initContactForms();
        focusStatusMessage();
        initErrorSummaryLinks();
    });

    function initContactForms() {
        document.querySelectorAll('.contact-form').forEach(function (form) {
            form.addEventListener('submit', handleSubmit);

            // Live-Validierung bei Blur
            form.querySelectorAll('.contact-input, .contact-textarea, .contact-select').forEach(function (input) {
                input.addEventListener('blur', function () {
                    validateField(input);
                });
                input.addEventListener('input', function () {
                    if (input.classList.contains('is-invalid')) {
                        validateField(input);
                    }
                });
            });
        });
    }

    function handleSubmit(e) {
        var form = e.target;
        var isValid = true;

        // Alle Felder validieren
        form.querySelectorAll('[required]').forEach(function (input) {
            if (!validateField(input)) {
                isValid = false;
            }
        });

        // E-Mail-Felder
        form.querySelectorAll('input[type="email"]').forEach(function (input) {
            if (input.value.trim() && !isValidEmail(input.value.trim())) {
                setError(input, t('Bitte gib eine gültige E-Mail-Adresse ein.', 'Please enter a valid email address.'));
                isValid = false;
            }
        });

        // URL-Felder
        form.querySelectorAll('input[type="url"]').forEach(function (input) {
            if (input.value.trim() && !isValidUrl(input.value.trim())) {
                setError(input, t('Bitte gib eine gültige URL ein.', 'Please enter a valid URL.'));
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            // Zum ersten Fehler scrollen
            var summary = form.querySelector('[data-contact-error-summary]');
            if (summary) {
                summary.focus({ preventScroll: true });
                summary.scrollIntoView({ behavior: 'smooth', block: 'center' });
                return;
            }

            var firstError = form.querySelector('.is-invalid, [aria-invalid="true"]');
            if (firstError) {
                firstError.focus();
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
            return;
        }

        // Loading-State
        form.classList.add('is-loading');
        var btn = form.querySelector('[type="submit"]');
        if (btn) {
            btn.disabled = true;
        }
    }

    function validateField(input) {
        clearError(input);

        // Required check
        if (input.hasAttribute('required')) {
            var val = input.value.trim();
            if (input.type === 'checkbox') {
                if (!input.checked) {
                    setError(input, t('Dieses Feld ist erforderlich.', 'This field is required.'));
                    return false;
                }
            } else if (!val) {
                setError(input, t('Dieses Feld ist erforderlich.', 'This field is required.'));
                return false;
            }
        }

        // Type-specific
        if (input.value.trim()) {
            if (input.type === 'email' && !isValidEmail(input.value.trim())) {
                setError(input, t('Ungültige E-Mail-Adresse.', 'Invalid email address.'));
                return false;
            }
            if (input.type === 'url' && !isValidUrl(input.value.trim())) {
                setError(input, t('Ungültige URL.', 'Invalid URL.'));
                return false;
            }
            if (input.type === 'tel' && !/^[+\d\s\-()]{6,20}$/.test(input.value.trim())) {
                setError(input, t('Ungültige Telefonnummer.', 'Invalid phone number.'));
                return false;
            }
        }

        // Pattern attribute
        if (input.pattern && input.value.trim()) {
            var regex = new RegExp(input.pattern);
            if (!regex.test(input.value.trim())) {
                setError(input, input.title || t('Ungültiges Format.', 'Invalid format.'));
                return false;
            }
        }

        return true;
    }

    function setError(input, message) {
        input.classList.add('is-invalid');
        // Bestehende Fehlermeldung entfernen
        var existing = input.parentElement.querySelector('.contact-error-text');
        if (existing) existing.remove();

        var errSpan = document.createElement('span');
        errSpan.className = 'contact-error-text';
        errSpan.id = input.id ? input.id + '-client-error' : '';
        errSpan.setAttribute('role', 'alert');
        errSpan.textContent = message;
        input.parentElement.appendChild(errSpan);

        input.setAttribute('aria-invalid', 'true');
        if (errSpan.id) {
            var existingDescribedBy = (input.getAttribute('aria-describedby') || '').trim();
            var describedByParts = existingDescribedBy ? existingDescribedBy.split(/\s+/) : [];
            if (describedByParts.indexOf(errSpan.id) === -1) {
                describedByParts.push(errSpan.id);
            }
            input.setAttribute('aria-describedby', describedByParts.join(' ').trim());
        }
    }

    function clearError(input) {
        input.classList.remove('is-invalid');
        var existing = input.parentElement.querySelector('.contact-error-text');
        if (existing) {
            var existingId = existing.id || '';
            existing.remove();
            if (existingId) {
                var describedByParts = (input.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
                describedByParts = describedByParts.filter(function (part) { return part !== existingId; });
                if (describedByParts.length > 0) {
                    input.setAttribute('aria-describedby', describedByParts.join(' '));
                } else {
                    input.removeAttribute('aria-describedby');
                }
            }
        }
        input.removeAttribute('aria-invalid');
    }

    function isValidEmail(val) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(val);
    }

    function isValidUrl(val) {
        try { new URL(val); return true; } catch { return false; }
    }

    function focusStatusMessage() {
        var message = document.querySelector('[data-contact-message]');
        if (!message) {
            return;
        }

        if (!message.hasAttribute('tabindex')) {
            message.setAttribute('tabindex', '-1');
        }

        message.focus({ preventScroll: true });
    }

    function initErrorSummaryLinks() {
        document.querySelectorAll('[data-contact-error-link]').forEach(function (link) {
            link.addEventListener('click', function (event) {
                var targetId = link.getAttribute('data-contact-error-link');
                if (!targetId) {
                    return;
                }

                var target = document.getElementById(targetId);
                if (!target) {
                    return;
                }

                event.preventDefault();
                target.focus({ preventScroll: true });
                target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
    }

    function getCurrentLang() {
        var path = (window.location.pathname || '').toLowerCase();
        return path === '/en' || path.indexOf('/en/') === 0 ? 'en' : 'de';
    }

    function t(de, en) {
        return currentLang === 'en' ? en : de;
    }

})();
