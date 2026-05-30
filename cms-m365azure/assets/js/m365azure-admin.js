(function () {
    'use strict';

    function closeDeleteModal() {
        var modal = document.getElementById('azsDeleteModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function openDeleteModal(button) {
        var modal = document.getElementById('azsDeleteModal');
        var actionInput = document.getElementById('azsDeleteAction');
        var idInput = document.getElementById('azsDeleteId');
        var nameNode = document.getElementById('azsDeleteName');
        if (!modal || !actionInput || !idInput || !nameNode) {
            return;
        }

        var entity = button.getAttribute('data-delete-entity') || '';
        var id = button.getAttribute('data-delete-id') || '';
        var name = button.getAttribute('data-delete-name') || 'Eintrag';
        actionInput.value = entity === 'category' ? 'delete_category' : 'delete_service';
        idInput.value = id;
        nameNode.textContent = name;
        modal.style.display = 'block';
    }

    function initMediaPickerModalFallback() {
        var modal = document.getElementById('settingsMediaPickerModal');
        var hasBootstrapModal = !!(window.bootstrap && window.bootstrap.Modal && typeof window.bootstrap.Modal.getOrCreateInstance === 'function');
        var backdrop = null;

        if (!modal || hasBootstrapModal) {
            return;
        }

        window.bootstrap = window.bootstrap || {};
        window.bootstrap.Modal = window.bootstrap.Modal || {};

        function ensureBackdrop() {
            if (backdrop) {
                return;
            }

            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.dataset.azsMediaPickerBackdrop = '1';
            document.body.appendChild(backdrop);
        }

        function showModal() {
            ensureBackdrop();
            modal.hidden = false;
            modal.removeAttribute('aria-hidden');
            modal.setAttribute('aria-modal', 'true');
            modal.setAttribute('role', 'dialog');
            modal.classList.add('show');
            modal.style.display = 'block';
            document.body.classList.add('modal-open');
        }

        function hideModal() {
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            modal.removeAttribute('aria-modal');
            modal.removeAttribute('role');
            document.body.classList.remove('modal-open');

            if (backdrop && backdrop.parentNode) {
                backdrop.parentNode.removeChild(backdrop);
            }
            backdrop = null;

            modal.dispatchEvent(new Event('hidden.bs.modal', { bubbles: true }));
        }

        window.bootstrap.Modal.getOrCreateInstance = function () {
            return {
                show: showModal,
                hide: hideModal
            };
        };

        document.querySelectorAll('[data-open-media-picker]').forEach(function (button) {
            button.addEventListener('click', function () {
                window.setTimeout(showModal, 0);
            });
        });

        modal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close').forEach(function (button) {
            button.addEventListener('click', hideModal);
        });

        modal.addEventListener('click', function (event) {
            if (event.target === modal) {
                hideModal();
            }
        });

        modal.addEventListener('click', function (event) {
            if (event.target.closest('[data-media-picker-select="1"]')) {
                window.setTimeout(hideModal, 0);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && modal.classList.contains('show')) {
                hideModal();
            }
        });
    }

    function updateGalleryPreview(input) {
        var preview = input && input.id ? document.querySelector('[data-media-preview][data-input-id="' + input.id + '"]') : null;
        var value = input ? String(input.value || '').trim() : '';
        var image;

        if (!preview) {
            return;
        }

        preview.className = 'azs-gallery-admin__preview';
        while (preview.firstChild) {
            preview.removeChild(preview.firstChild);
        }

        if (!value) {
            preview.hidden = true;
            return;
        }

        image = document.createElement('img');
        image.src = value;
        image.alt = 'Galerie-Vorschau';
        image.loading = 'lazy';
        preview.appendChild(image);
        preview.hidden = false;
    }

    function syncAllGalleryPreviews() {
        document.querySelectorAll('[data-azs-gallery-input]').forEach(updateGalleryPreview);
    }

    function initCategoryGalleryPreviews() {
        document.querySelectorAll('[data-azs-gallery-input]').forEach(function (input) {
            input.addEventListener('input', function () {
                updateGalleryPreview(input);
            });

            input.addEventListener('change', function () {
                updateGalleryPreview(input);
            });
        });

        document.querySelectorAll('[data-clear-media-input]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.targetInput || '');
                if (!input || !input.matches('[data-azs-gallery-input]')) {
                    return;
                }

                window.setTimeout(function () {
                    updateGalleryPreview(input);
                }, 0);
            });
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('[data-media-picker-select="1"]')) {
                return;
            }

            window.setTimeout(syncAllGalleryPreviews, 0);
        });

        window.setTimeout(syncAllGalleryPreviews, 0);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-delete-entity]').forEach(function (button) {
            button.addEventListener('click', function () {
                openDeleteModal(button);
            });
        });

        document.querySelectorAll('[data-azs-close]').forEach(function (button) {
            button.addEventListener('click', closeDeleteModal);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeDeleteModal();
            }
        });

        initMediaPickerModalFallback();
        initCategoryGalleryPreviews();
    });
}());
