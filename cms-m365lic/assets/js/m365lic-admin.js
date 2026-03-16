document.addEventListener('DOMContentLoaded', () => {
    const openModal = (modalId) => {
        const modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }

        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    };

    const closeModal = (modalId) => {
        const modal = document.getElementById(modalId);
        if (!modal) {
            return;
        }

        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('[data-m365lic-open-modal]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const modalId = trigger.getAttribute('data-m365lic-open-modal');
            if (!modalId) {
                return;
            }

            if (modalId === 'm365licRemoveSpecialUserModal') {
                const userIdInput = document.getElementById('m365licRemoveSpecialUserId');
                const userNameLabel = document.getElementById('m365licRemoveSpecialUserName');

                if (userIdInput) {
                    userIdInput.value = trigger.getAttribute('data-user-id') ?? '0';
                }

                if (userNameLabel) {
                    userNameLabel.textContent = trigger.getAttribute('data-user-name') ?? 'diesen Benutzer';
                }
            }

            openModal(modalId);
        });
    });

    document.querySelectorAll('[data-m365lic-close-modal]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const modalId = trigger.getAttribute('data-m365lic-close-modal');
            if (modalId) {
                closeModal(modalId);
            }
        });
    });

    document.querySelectorAll('.modal').forEach((modal) => {
        modal.addEventListener('click', (event) => {
            if (event.target === modal && modal.id) {
                closeModal(modal.id);
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') {
            return;
        }

        document.querySelectorAll('.modal').forEach((modal) => {
            if (modal instanceof HTMLElement && modal.style.display === 'flex' && modal.id) {
                closeModal(modal.id);
            }
        });
    });
});
