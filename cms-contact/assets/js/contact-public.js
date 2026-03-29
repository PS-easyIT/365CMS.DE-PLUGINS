/**
 * CMS Contact – Public JavaScript
 * Clientseitige Formularvalidierung und UX-Verbesserungen.
 *
 * @package CMS_Contact
 * @version 1.0.0
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        initContactForms();
        focusStatusMessage();
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
                setError(input, 'Bitte gib eine gültige E-Mail-Adresse ein.');
                isValid = false;
            }
        });

        // URL-Felder
        form.querySelectorAll('input[type="url"]').forEach(function (input) {
            if (input.value.trim() && !isValidUrl(input.value.trim())) {
                setError(input, 'Bitte gib eine gültige URL ein.');
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            // Zum ersten Fehler scrollen
            var firstError = form.querySelector('.is-invalid');
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
                    setError(input, 'Dieses Feld ist erforderlich.');
                    return false;
                }
            } else if (!val) {
                setError(input, 'Dieses Feld ist erforderlich.');
                return false;
            }
        }

        // Type-specific
        if (input.value.trim()) {
            if (input.type === 'email' && !isValidEmail(input.value.trim())) {
                setError(input, 'Ungültige E-Mail-Adresse.');
                return false;
            }
            if (input.type === 'url' && !isValidUrl(input.value.trim())) {
                setError(input, 'Ungültige URL.');
                return false;
            }
            if (input.type === 'tel' && !/^[+\d\s\-()]{6,20}$/.test(input.value.trim())) {
                setError(input, 'Ungültige Telefonnummer.');
                return false;
            }
        }

        // Pattern attribute
        if (input.pattern && input.value.trim()) {
            var regex = new RegExp(input.pattern);
            if (!regex.test(input.value.trim())) {
                setError(input, input.title || 'Ungültiges Format.');
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
        errSpan.textContent = message;
        input.parentElement.appendChild(errSpan);
    }

    function clearError(input) {
        input.classList.remove('is-invalid');
        var existing = input.parentElement.querySelector('.contact-error-text');
        if (existing) existing.remove();
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

})();
