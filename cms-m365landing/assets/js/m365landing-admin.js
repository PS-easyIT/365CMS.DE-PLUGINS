(function () {
    'use strict';

    function openModal(id) {
        var modal = document.getElementById(id);
        if (!modal) {
            return;
        }
        modal.style.display = 'block';
        modal.classList.add('show');
    }

    function closeModal(id) {
        var modal = document.getElementById(id);
        if (!modal) {
            return;
        }
        modal.classList.remove('show');
        modal.style.display = 'none';
    }

    function bindDeleteModal() {
        document.querySelectorAll('[data-delete-card]').forEach(function (button) {
            button.addEventListener('click', function () {
                var id = button.getAttribute('data-delete-id') || '';
                var name = button.getAttribute('data-delete-name') || 'diese Karte';
                var idField = document.getElementById('m365landingDeleteId');
                var nameField = document.getElementById('m365landingDeleteName');
                if (idField) {
                    idField.value = id;
                }
                if (nameField) {
                    nameField.textContent = name;
                }
                openModal('m365landingDeleteModal');
            });
        });

        document.querySelectorAll('[data-m365landing-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                closeModal('m365landingDeleteModal');
            });
        });
    }

    function bindColorMirrors() {
        document.querySelectorAll('.m365landing-color-row input[type="color"]').forEach(function (picker) {
            var text = picker.parentElement ? picker.parentElement.querySelector('input[type="text"]') : null;
            if (!text) {
                return;
            }
            picker.addEventListener('input', function () {
                text.value = picker.value;
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        bindDeleteModal();
        bindColorMirrors();
    });
})();
