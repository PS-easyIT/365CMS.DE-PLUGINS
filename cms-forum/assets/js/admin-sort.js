/**
 * CMS Forum – Admin Sortierung (Drag & Drop)
 *
 * Enables reorder of categories / forums via drag-and-drop
 * in the admin backend. Uses native HTML5 drag & drop API.
 *
 * @package CMS_Forum
 * @version 1.0.0
 */
(function () {
    'use strict';

    let dragSrcEl = null;

    function handleDragStart(e) {
        dragSrcEl = this;
        this.style.opacity = '0.5';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', this.dataset.sortId || '');
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        this.classList.add('drag-over');
    }

    function handleDragLeave() {
        this.classList.remove('drag-over');
    }

    function handleDrop(e) {
        e.stopPropagation();
        e.preventDefault();
        this.classList.remove('drag-over');

        if (dragSrcEl === this) return;

        // Swap rows
        const parent = dragSrcEl.parentNode;
        const sibling = this.nextSibling === dragSrcEl ? this : this.nextSibling;
        parent.insertBefore(dragSrcEl, sibling === dragSrcEl ? this : sibling);

        updateSortOrder();
    }

    function handleDragEnd() {
        this.style.opacity = '1';
        document.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
    }

    function updateSortOrder() {
        const container = document.querySelector('[data-sortable]');
        if (!container) return;

        const items = [...container.querySelectorAll('[data-sort-id]')];
        const order = items.map((el, i) => ({
            id: parseInt(el.dataset.sortId, 10),
            position: i
        }));

        const csrfToken = container.dataset.csrf || '';
        const endpoint  = container.dataset.sortUrl || '';

        if (!endpoint) return;

        const fd = new FormData();
        fd.append('csrf_token', csrfToken);
        fd.append('order', JSON.stringify(order));
        fd.append('ajax', '1');

        fetch(endpoint, { method: 'POST', body: fd })
            .then((res) => res.json())
            .then((data) => {
                if (!data.success) {
                    console.error('Sort update failed:', data.error);
                }
            })
            .catch((err) => console.error('Sort network error:', err));
    }

    function initSortable() {
        const container = document.querySelector('[data-sortable]');
        if (!container) return;

        const items = container.querySelectorAll('[data-sort-id]');
        items.forEach((item) => {
            item.setAttribute('draggable', 'true');
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragover', handleDragOver);
            item.addEventListener('dragleave', handleDragLeave);
            item.addEventListener('drop', handleDrop);
            item.addEventListener('dragend', handleDragEnd);
        });
    }

    document.addEventListener('DOMContentLoaded', initSortable);
})();
