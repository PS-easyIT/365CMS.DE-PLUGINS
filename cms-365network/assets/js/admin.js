/**
 * CMS 365NETWORK – Admin interactions.
 *
 * @package CMS_365NETWORK
 */
(function () {
    'use strict';

    function updateOrderInput(control) {
        var input = control.querySelector('[data-n365-order-input]');
        var list = control.querySelector('[data-n365-order-list]');

        if (!input || !list) {
            return;
        }

        input.value = Array.prototype.map.call(list.querySelectorAll('[data-order-key]'), function (item) {
            return item.getAttribute('data-order-key') || '';
        }).filter(Boolean).join(',');

        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function syncOrderControls(root) {
        root.querySelectorAll('[data-n365-order-control]').forEach(updateOrderInput);
    }

    function moveItem(item, direction) {
        var sibling = direction === 'up' ? item.previousElementSibling : item.nextElementSibling;

        if (!sibling || !item.parentElement) {
            return;
        }

        if (direction === 'up') {
            item.parentElement.insertBefore(item, sibling);
        } else {
            item.parentElement.insertBefore(sibling, item);
        }
    }

    function initOrderControls() {
        document.querySelectorAll('[data-n365-order-control]').forEach(function (control) {
            var list = control.querySelector('[data-n365-order-list]');
            var draggedItem = null;

            if (!list) {
                return;
            }

            list.addEventListener('click', function (event) {
                var button = event.target.closest('[data-order-move]');
                var item = button ? button.closest('[data-order-key]') : null;

                if (!button || !item) {
                    return;
                }

                moveItem(item, button.getAttribute('data-order-move') || '');
                updateOrderInput(control);
                item.focus({ preventScroll: true });
            });

            list.addEventListener('dragstart', function (event) {
                draggedItem = event.target.closest('[data-order-key]');
                if (!draggedItem) {
                    return;
                }

                draggedItem.classList.add('is-dragging');
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', draggedItem.getAttribute('data-order-key') || '');
                }
            });

            list.addEventListener('dragover', function (event) {
                var target = event.target.closest('[data-order-key]');
                var rect;

                if (!draggedItem || !target || target === draggedItem) {
                    return;
                }

                event.preventDefault();
                rect = target.getBoundingClientRect();
                if (event.clientY < rect.top + rect.height / 2) {
                    list.insertBefore(draggedItem, target);
                } else {
                    list.insertBefore(draggedItem, target.nextElementSibling);
                }
                updateOrderInput(control);
            });

            list.addEventListener('dragend', function () {
                if (draggedItem) {
                    draggedItem.classList.remove('is-dragging');
                    draggedItem = null;
                    updateOrderInput(control);
                }
            });

            updateOrderInput(control);
        });

        document.querySelectorAll('form').forEach(function (form) {
            if (form.dataset.n365OrderSubmitBound === '1') {
                return;
            }

            form.dataset.n365OrderSubmitBound = '1';
            form.addEventListener('submit', function () {
                syncOrderControls(form);
            });
        });
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
            backdrop.dataset.n365MediaPickerBackdrop = '1';
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

    function initColorControls() {
        document.querySelectorAll('.n365-color-control').forEach(function (control) {
            var picker = control.querySelector('[data-n365-color-picker]');
            var text = control.querySelector('[data-n365-color-text]');

            if (!picker || !text) {
                return;
            }

            picker.addEventListener('input', function () {
                text.value = picker.value;
            });

            text.addEventListener('input', function () {
                var value = String(text.value || '').trim();
                if (/^#[0-9A-Fa-f]{6}$/.test(value)) {
                    picker.value = value;
                    picker.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initOrderControls();
        initColorControls();
        initMediaPickerModalFallback();
    });
})();
