document.addEventListener('DOMContentLoaded', () => {
    const priceInputs = Array.from(document.querySelectorAll('[data-m365lic-price-input]'));
    const priceFeedback = document.querySelector('[data-m365lic-copy-price-feedback]');
    const priceCopyButton = document.querySelector('[data-m365lic-copy-price]');
    const areaLabels = {
        public_price: 'Public',
        member_price: 'Member',
        group_price: 'Spezial',
    };

    const normalizePriceValue = (value) => value.trim().replace(',', '.');

    const getFirstFilledPriceField = (excludeField = '') => {
        for (const input of priceInputs) {
            const field = input.getAttribute('data-m365lic-price-input') ?? '';
            if (field === excludeField) {
                continue;
            }

            if (normalizePriceValue(input.value) !== '') {
                return field;
            }
        }

        return '';
    };

    const getStatusMeta = (field) => {
        const currentInput = priceInputs.find((input) => input.getAttribute('data-m365lic-price-input') === field);
        const currentValue = currentInput ? normalizePriceValue(currentInput.value) : '';

        if (currentValue !== '') {
            return {
                type: 'direct',
                label: 'direkt gepflegt',
                detail: `Eigener Preis für ${areaLabels[field] ?? 'diesen Bereich'}.`,
            };
        }

        const sourceField = getFirstFilledPriceField(field);
        if (sourceField !== '') {
            return {
                type: 'inherited',
                label: 'geerbt',
                detail: `Verwendet aktuell den Preis aus ${areaLabels[sourceField] ?? 'einem anderen Bereich'}.`,
            };
        }

        return {
            type: 'empty',
            label: 'kein Preis',
            detail: 'Noch kein Preis hinterlegt.',
        };
    };

    const updatePriceStatuses = () => {
        priceInputs.forEach((input) => {
            const field = input.getAttribute('data-m365lic-price-input') ?? '';
            if (field === '') {
                return;
            }

            const statusNode = document.querySelector(`[data-m365lic-price-status="${field}"]`);
            if (!statusNode) {
                return;
            }

            const badge = statusNode.querySelector('[data-m365lic-price-badge]');
            const text = statusNode.querySelector('[data-m365lic-price-text]');
            const status = getStatusMeta(field);

            if (badge) {
                badge.textContent = status.label;
                badge.classList.remove('m365lic-status-pill--direct', 'm365lic-status-pill--inherited', 'm365lic-status-pill--empty');
                badge.classList.add(`m365lic-status-pill--${status.type}`);
            }

            if (text) {
                text.textContent = status.detail;
            }
        });

        if (priceCopyButton instanceof HTMLButtonElement) {
            priceCopyButton.disabled = getFirstFilledPriceField() === '';
        }
    };

    const setPriceFeedback = (message) => {
        if (priceFeedback) {
            priceFeedback.textContent = message;
        }
    };

    if (priceInputs.length > 0) {
        priceInputs.forEach((input) => {
            input.addEventListener('input', () => {
                setPriceFeedback('');
                updatePriceStatuses();
            });
        });

        updatePriceStatuses();
    }

    if (priceCopyButton instanceof HTMLButtonElement) {
        priceCopyButton.addEventListener('click', () => {
            const activeField = document.activeElement instanceof HTMLElement
                ? document.activeElement.getAttribute('data-m365lic-price-input') ?? ''
                : '';
            const activeInput = activeField !== ''
                ? priceInputs.find((input) => input.getAttribute('data-m365lic-price-input') === activeField)
                : null;
            const sourceInput = activeInput && normalizePriceValue(activeInput.value) !== ''
                ? activeInput
                : priceInputs.find((input) => normalizePriceValue(input.value) !== '') ?? null;

            if (!sourceInput) {
                setPriceFeedback('Bitte zuerst einen Preis eintragen.');
                updatePriceStatuses();
                return;
            }

            const sourceField = sourceInput.getAttribute('data-m365lic-price-input') ?? '';
            const sourceValue = sourceInput.value;

            priceInputs.forEach((input) => {
                input.value = sourceValue;
            });

            setPriceFeedback(`Preis aus ${areaLabels[sourceField] ?? 'dem aktiven Bereich'} in alle Bereiche übernommen.`);
            updatePriceStatuses();
        });
    }

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

            if (modalId === 'm365licDeleteSpecialGroupModal') {
                const groupIdInput = document.getElementById('m365licDeleteSpecialGroupId');
                const groupNameLabel = document.getElementById('m365licDeleteSpecialGroupName');

                if (groupIdInput) {
                    groupIdInput.value = trigger.getAttribute('data-group-id') ?? '0';
                }

                if (groupNameLabel) {
                    groupNameLabel.textContent = trigger.getAttribute('data-group-name') ?? 'diese Gruppe';
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

    const alternativesTable = document.getElementById('m365licAlternativesTable');
    const addAlternativeButton = document.getElementById('m365licAddAlternativeRow');

    const reindexAlternativeRows = () => {
        if (!alternativesTable) {
            return;
        }

        const rows = alternativesTable.querySelectorAll('tbody tr[data-alternative-row]');
        rows.forEach((row, index) => {
            row.querySelectorAll('input').forEach((input) => {
                if (!input.name) {
                    return;
                }

                input.name = input.name.replace(/alternatives\[\d+\]/g, 'alternatives[' + index + ']');
            });
        });
    };

    const bindAlternativeRow = (row) => {
        const removeButton = row.querySelector('.m365lic-remove-alternative-row');
        if (!removeButton) {
            return;
        }

        removeButton.addEventListener('click', () => {
            const rows = alternativesTable ? alternativesTable.querySelectorAll('tbody tr[data-alternative-row]') : [];
            if (rows.length <= 1) {
                row.querySelectorAll('input[type="text"]').forEach((input) => {
                    input.value = '';
                });
                row.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                    input.checked = true;
                });
                return;
            }

            row.remove();
            reindexAlternativeRows();
        });
    };

    if (alternativesTable) {
        alternativesTable.querySelectorAll('tbody tr[data-alternative-row]').forEach(bindAlternativeRow);
    }

    if (alternativesTable && addAlternativeButton) {
        addAlternativeButton.addEventListener('click', () => {
            const tbody = alternativesTable.querySelector('tbody');
            const firstRow = tbody ? tbody.querySelector('tr[data-alternative-row]') : null;
            if (!tbody || !firstRow) {
                return;
            }

            const clone = firstRow.cloneNode(true);
            clone.querySelectorAll('input[type="text"]').forEach((input) => {
                input.value = '';
            });
            clone.querySelectorAll('input[type="checkbox"]').forEach((input) => {
                input.checked = true;
            });

            tbody.appendChild(clone);
            reindexAlternativeRows();
            bindAlternativeRow(clone);
        });
    }
});
