(function () {
    'use strict';

    let pendingForm = null;
    let lastFocusedElement = null;

    function getModalElements() {
        return {
            backdrop: document.getElementById('ni-confirm-backdrop'),
            title: document.getElementById('ni-confirm-title'),
            message: document.getElementById('ni-confirm-message'),
            submit: document.getElementById('ni-confirm-submit'),
            closers: document.querySelectorAll('[data-confirm-close]'),
        };
    }

    function closeModal() {
        const { backdrop, submit } = getModalElements();
        if (!backdrop) {
            return;
        }

        backdrop.hidden = true;
        document.body.style.overflow = '';
        pendingForm = null;
        submit?.removeAttribute('data-busy');

        if (lastFocusedElement instanceof HTMLElement) {
            lastFocusedElement.focus();
        }
    }

    function openModal(form) {
        const { backdrop, title, message, submit } = getModalElements();
        if (!backdrop || !title || !message || !submit) {
            form.submit();
            return;
        }

        pendingForm = form;
        lastFocusedElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
        title.textContent = form.getAttribute('data-confirm-title') || 'Aktion bestätigen';
        message.textContent = form.getAttribute('data-confirm-message') || 'Bitte bestätige diese Aktion.';
        submit.textContent = form.getAttribute('data-confirm-button') || 'Aktion ausführen';
        submit.removeAttribute('data-busy');
        backdrop.hidden = false;
        document.body.style.overflow = 'hidden';
        submit.focus();
    }

    function confirmPendingForm() {
        const { submit } = getModalElements();
        if (!pendingForm) {
            closeModal();
            return;
        }

        submit?.setAttribute('data-busy', 'true');
        pendingForm.submit();
    }

    function handleDocumentClick(event) {
        const target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        const form = target.closest('form[data-confirm-action="true"]');
        const isSubmitTrigger = target.matches('button[type="submit"], input[type="submit"]');
        if (form instanceof HTMLFormElement && isSubmitTrigger) {
            event.preventDefault();
            openModal(form);
            return;
        }

        if (target.hasAttribute('data-confirm-close')) {
            event.preventDefault();
            closeModal();
            return;
        }

        if (target.id === 'ni-confirm-submit') {
            event.preventDefault();
            confirmPendingForm();
            return;
        }

        if (target.id === 'ni-confirm-backdrop') {
            event.preventDefault();
            closeModal();
        }
    }

    function handleFormSubmit(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.getAttribute('data-confirm-action') !== 'true') {
            return;
        }

        if (form === pendingForm) {
            return;
        }

        event.preventDefault();
        openModal(form);
    }

    function handleKeydown(event) {
        const { backdrop } = getModalElements();
        if (!backdrop || backdrop.hidden) {
            return;
        }

        if (event.key === 'Escape') {
            event.preventDefault();
            closeModal();
        }
    }

    document.addEventListener('click', handleDocumentClick);
    document.addEventListener('submit', handleFormSubmit, true);
    document.addEventListener('keydown', handleKeydown);
})();
