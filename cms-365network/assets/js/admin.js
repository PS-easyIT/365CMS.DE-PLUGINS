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
    }

    document.addEventListener('DOMContentLoaded', initOrderControls);
})();
