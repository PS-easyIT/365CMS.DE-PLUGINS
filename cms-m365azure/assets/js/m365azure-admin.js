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
    });
}());
