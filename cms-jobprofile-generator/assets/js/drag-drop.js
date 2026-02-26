/**
 * Job Profile Generator – Drag & Drop Standalone Module
 * Reusable HTML5 native DnD for sortable lists.
 * Includes touch-event fallback for iOS/Android.
 *
 * @package CMS_JobProfileGenerator
 * @since   0.0.1  Initial
 * @since   0.4.0  Mobile touch-event support added
 *
 * Usage:
 *   JPGDragDrop.init('my-sortable-list');
 *   // or with callback:
 *   JPGDragDrop.init('my-sortable-list', { onReorder: function(items) { ... } });
 */

'use strict';

const JPGDragDrop = (function () {

    const instances = new Map();

    /**
     * Initialise DnD on a <ul> or <ol> element.
     *
     * @param {string} listId  DOM id of the list element
     * @param {Object} [opts]  Options
     * @param {string} [opts.itemSelector='li']       Selector for sortable children
     * @param {string} [opts.handleSelector='.jpg-drag-handle']  Drag handle selector (empty = whole item)
     * @param {Function} [opts.onReorder]             Callback after reorder, receives ordered HTMLElement[]
     */
    function init(listId, opts) {
        const list = document.getElementById(listId);
        if (!list) return;

        // Prevent double-init
        if (instances.has(listId)) destroy(listId);

        const options = Object.assign({
            itemSelector:   'li',
            handleSelector: '.jpg-drag-handle',
            onReorder:      null,
        }, opts || {});

        let dragging = null;

        function getItem(e) {
            return e.target.closest(options.itemSelector);
        }

        function onDragStart(e) {
            const item = getItem(e);
            if (!item) return;
            dragging = item;
            dragging.style.opacity = '.45';
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', ''); // required for Firefox
        }

        function onDragEnd() {
            if (dragging) dragging.style.opacity = '';
            list.querySelectorAll(options.itemSelector).forEach(function (el) {
                el.classList.remove('jpg-drag-over');
            });
            dragging = null;
            updateOrder(list, options);
        }

        function onDragOver(e) {
            e.preventDefault();
            const over = getItem(e);
            if (!over || over === dragging) return;

            const rect  = over.getBoundingClientRect();
            const after = e.clientY > rect.top + rect.height / 2;

            list.querySelectorAll(options.itemSelector).forEach(function (el) {
                el.classList.remove('jpg-drag-over');
            });
            over.classList.add('jpg-drag-over');

            if (after) {
                over.after(dragging);
            } else {
                over.before(dragging);
            }
        }

        function onDragLeave(e) {
            const over = getItem(e);
            if (over) over.classList.remove('jpg-drag-over');
        }

        function onDrop(e) {
            e.preventDefault();
        }

        // Enable draggable on items (or restrict to handle via mousedown)
        enableDraggable(list, options);

        list.addEventListener('dragstart', onDragStart);
        list.addEventListener('dragend',   onDragEnd);
        list.addEventListener('dragover',  onDragOver);
        list.addEventListener('dragleave', onDragLeave);
        list.addEventListener('drop',      onDrop);

        // ── Mobile Touch Support (iOS / Android) ─────────────────────────────
        initTouchDnd(list, options);

        // Store for cleanup
        instances.set(listId, {
            list:     list,
            handlers: { onDragStart, onDragEnd, onDragOver, onDragLeave, onDrop },
            options:  options,
        });
    }

    // ── Touch DnD ─────────────────────────────────────────────────────────────

    /**
     * Adds touch-event-based drag & drop for mobile browsers.
     * Uses a visual clone while dragging.
     */
    function initTouchDnd(list, options) {
        if (!('ontouchstart' in window)) return; // skip on non-touch devices

        let touchDragging = null;
        let clone         = null;
        let startY        = 0;
        let offsetY       = 0;

        function getItemAt(clientX, clientY) {
            const items = Array.from(list.querySelectorAll(options.itemSelector));
            return items.find(function (item) {
                if (item === touchDragging) return false;
                const r = item.getBoundingClientRect();
                return clientX >= r.left && clientX <= r.right
                    && clientY >= r.top  && clientY <= r.bottom;
            }) || null;
        }

        list.addEventListener('touchstart', function (e) {
            const item = e.target.closest(options.itemSelector);
            if (!item) return;

            // Only start drag if grabbing the handle (or if no handle configured)
            if (options.handleSelector && !e.target.closest(options.handleSelector)) return;

            touchDragging = item;
            startY  = e.touches[0].clientY;
            offsetY = e.touches[0].clientY - item.getBoundingClientRect().top;

            // Create visual clone
            clone = item.cloneNode(true);
            clone.style.cssText = [
                'position:fixed',
                'z-index:9999',
                'opacity:.85',
                'pointer-events:none',
                'width:' + item.offsetWidth + 'px',
                'background:#fff',
                'box-shadow:0 4px 16px rgba(0,0,0,.18)',
                'border-radius:6px',
            ].join(';');
            clone.style.left = item.getBoundingClientRect().left + 'px';
            clone.style.top  = (e.touches[0].clientY - offsetY) + 'px';
            document.body.appendChild(clone);

            touchDragging.style.opacity = '.35';
            e.preventDefault();
        }, { passive: false });

        list.addEventListener('touchmove', function (e) {
            if (!touchDragging || !clone) return;
            e.preventDefault();

            const touch = e.touches[0];
            clone.style.top = (touch.clientY - offsetY) + 'px';

            const over = getItemAt(touch.clientX, touch.clientY);
            if (over) {
                list.querySelectorAll(options.itemSelector).forEach(function (el) {
                    el.classList.remove('jpg-drag-over');
                });
                over.classList.add('jpg-drag-over');

                const rect  = over.getBoundingClientRect();
                const after = touch.clientY > rect.top + rect.height / 2;
                if (after) {
                    over.after(touchDragging);
                } else {
                    over.before(touchDragging);
                }
            }
        }, { passive: false });

        list.addEventListener('touchend', function () {
            if (!touchDragging) return;

            touchDragging.style.opacity = '';
            list.querySelectorAll(options.itemSelector).forEach(function (el) {
                el.classList.remove('jpg-drag-over');
            });

            if (clone) {
                clone.remove();
                clone = null;
            }
            touchDragging = null;
            updateOrder(list, options);
        });
    }

    /**
     * Enable draggable attribute on list items.
     */
    function enableDraggable(list, options) {
        list.querySelectorAll(options.itemSelector).forEach(function (item) {
            if (options.handleSelector) {
                // Draggable only when grabbing the handle
                const handle = item.querySelector(options.handleSelector);
                if (handle) {
                    handle.addEventListener('mousedown', function () {
                        item.setAttribute('draggable', 'true');
                    });
                    handle.addEventListener('mouseup', function () {
                        item.removeAttribute('draggable');
                    });
                }
            } else {
                item.setAttribute('draggable', 'true');
            }
        });
    }

    /**
     * After reorder: update hidden sort_order inputs + numbering.
     */
    function updateOrder(list, options) {
        const items = Array.from(list.querySelectorAll(options.itemSelector));

        items.forEach(function (item, idx) {
            // Update hidden sort_order input
            const orderInput = item.querySelector('input[name$="[sort_order]"]');
            if (orderInput) orderInput.value = idx + 1;

            // Update visible number
            const numEl = item.querySelector('.jpg-task-num');
            if (numEl) numEl.textContent = (idx + 1) + '.';
        });

        // Fire callback
        if (typeof options.onReorder === 'function') {
            options.onReorder(items);
        }
    }

    /**
     * Re-initialise DnD (e.g. after adding new items).
     * @param {string} listId
     */
    function refresh(listId) {
        const inst = instances.get(listId);
        if (!inst) return;
        enableDraggable(inst.list, inst.options);
    }

    /**
     * Destroy DnD on a list (remove all event listeners).
     * @param {string} listId
     */
    function destroy(listId) {
        const inst = instances.get(listId);
        if (!inst) return;

        const h = inst.handlers;
        inst.list.removeEventListener('dragstart', h.onDragStart);
        inst.list.removeEventListener('dragend',   h.onDragEnd);
        inst.list.removeEventListener('dragover',  h.onDragOver);
        inst.list.removeEventListener('dragleave', h.onDragLeave);
        inst.list.removeEventListener('drop',      h.onDrop);

        instances.delete(listId);
    }

    return {
        init:    init,
        refresh: refresh,
        destroy: destroy,
    };

})();

// Make available globally
window.JPGDragDrop = JPGDragDrop;
