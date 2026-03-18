/**
 * CMS Feed – Admin JavaScript
 *
 * Modal-Funktionen, Tab-Switching, Color-Sync und Admin-Helfer.
 *
 * @package CMS_Feed
 * @since   1.0.0
 */

(function() {
    'use strict';

    var feedAdminData = {
        channels: [],
        categories: [],
        digests: []
    };

    // ══════════════════════════════════════════════════════════════════════
    // Modal-Funktionen (global bereitgestellt)
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Modal öffnen (display: flex für zentriertes Layout).
     */
    window.openModal = function(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'flex';
            // Focus auf erstes Inputfeld setzen
            var firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) {
                setTimeout(function() { firstInput.focus(); }, 100);
            }
        }
    };

    /**
     * Modal schließen.
     */
    window.closeModal = function(id) {
        var modal = document.getElementById(id);
        if (modal) {
            modal.style.display = 'none';
        }
    };

    // Klick außerhalb des Modal-Inhalts schließt das Modal
    window.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
        }
    });

    // Escape-Taste schließt offene Modals
    window.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal').forEach(function(m) {
                if (m.style.display !== 'none' && m.style.display !== '') {
                    m.style.display = 'none';
                }
            });
        }
    });

    // ══════════════════════════════════════════════════════════════════════
    // Settings Sub-Tab-Switching
    // ══════════════════════════════════════════════════════════════════════

    window.switchTab = function(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(function(t) {
            t.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(function(b) {
            b.classList.remove('active');
        });
        var el = document.getElementById(tabId);
        if (el) el.classList.add('active');
        if (btn) btn.classList.add('active');
    };

    // ══════════════════════════════════════════════════════════════════════
    // Color-Picker / Text-Input Synchronisation
    // ══════════════════════════════════════════════════════════════════════

    function initColorSync() {
        document.querySelectorAll('input[type="color"]').forEach(function(picker) {
            var textInput = picker.nextElementSibling;
            if (!textInput || textInput.type !== 'text') return;

            picker.addEventListener('input', function() {
                textInput.value = this.value;
            });

            textInput.addEventListener('input', function() {
                if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
                    picker.value = this.value;
                }
            });
        });
    }

    // ══════════════════════════════════════════════════════════════════════
    // Bulk-Actions: Kanäle
    // ══════════════════════════════════════════════════════════════════════

    /**
     * Alle Kanal-Checkboxen (de)markieren.
     */
    window.toggleAllChannels = function(checked) {
        document.querySelectorAll('.channel-checkbox').forEach(function(cb) {
            cb.checked = checked;
        });
        updateChannelBulk();
    };

    /**
     * Bulk-Bar aktualisieren nach Checkbox-Änderung.
     */
    window.updateChannelBulk = function() {
        var checked = document.querySelectorAll('.channel-checkbox:checked');
        var bar = document.getElementById('channelBulkBar');
        var count = document.getElementById('channelBulkCount');
        var selectAll = document.getElementById('channelSelectAll');

        if (bar) {
            bar.style.display = checked.length > 0 ? 'flex' : 'none';
        }
        if (count) {
            count.textContent = checked.length;
        }
        // Sync "alle markieren" Checkbox
        if (selectAll) {
            var total = document.querySelectorAll('.channel-checkbox').length;
            selectAll.checked = checked.length === total && total > 0;
            selectAll.indeterminate = checked.length > 0 && checked.length < total;
        }
    };

    /**
     * Bulk-Action für Kanäle absenden.
     */
    window.submitChannelBulk = function(action) {
        var checked = document.querySelectorAll('.channel-checkbox:checked');
        if (checked.length === 0) return;

        var actionLabels = {
            'bulk_fetch_channels': 'abrufen (max. 5 sofort, Rest per Cron)',
            'bulk_activate_channels': 'aktivieren',
            'bulk_deactivate_channels': 'deaktivieren',
            'bulk_delete_channels': 'löschen (inkl. aller Beiträge)'
        };

        var label = actionLabels[action] || action;
        openBulkConfirmModal(
            checked.length + ' Kanal/Kanäle ' + label + '?',
            action === 'bulk_delete_channels',
            function() {
                var form = document.getElementById('channelBulkForm');
                var idContainer = document.getElementById('channelBulkIds');
                document.getElementById('channelBulkAction').value = action;
                idContainer.innerHTML = '';
                checked.forEach(function(cb) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'bulk_ids[]';
                    input.value = cb.value;
                    idContainer.appendChild(input);
                });
                form.submit();
            }
        );
    };

    // ══════════════════════════════════════════════════════════════════════
    // Bulk-Actions: Bereiche
    // ══════════════════════════════════════════════════════════════════════

    window.toggleAllCategories = function(checked) {
        document.querySelectorAll('.category-checkbox').forEach(function(cb) {
            cb.checked = checked;
        });
        updateCategoryBulk();
    };

    window.updateCategoryBulk = function() {
        var checked = document.querySelectorAll('.category-checkbox:checked');
        var bar = document.getElementById('categoryBulkBar');
        var count = document.getElementById('categoryBulkCount');
        var selectAll = document.getElementById('categorySelectAll');

        if (bar) {
            bar.style.display = checked.length > 0 ? 'flex' : 'none';
        }
        if (count) {
            count.textContent = checked.length;
        }
        if (selectAll) {
            var total = document.querySelectorAll('.category-checkbox').length;
            selectAll.checked = checked.length === total && total > 0;
            selectAll.indeterminate = checked.length > 0 && checked.length < total;
        }
    };

    window.submitCategoryBulk = function(action) {
        var checked = document.querySelectorAll('.category-checkbox:checked');
        if (checked.length === 0) return;

        openBulkConfirmModal(
            checked.length + ' Bereich/Bereiche löschen (inkl. aller Kanäle und Beiträge)?',
            true,
            function() {
                var form = document.getElementById('categoryBulkForm');
                var idContainer = document.getElementById('categoryBulkIds');
                document.getElementById('categoryBulkAction').value = action;
                idContainer.innerHTML = '';
                checked.forEach(function(cb) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'bulk_ids[]';
                    input.value = cb.value;
                    idContainer.appendChild(input);
                });
                form.submit();
            }
        );
    };

    // ══════════════════════════════════════════════════════════════════════
    // Bulk-Bestätigungsdialog (eigenes Modal statt window.confirm)
    // ══════════════════════════════════════════════════════════════════════

    var pendingBulkCallback = null;

    function ensureBulkConfirmModal() {
        var modal = document.getElementById('bulkConfirmModal');
        if (modal) {
            return modal;
        }

        modal = document.createElement('div');
        modal.id = 'bulkConfirmModal';
        modal.className = 'modal feed-modal';
        modal.innerHTML = [
            '<div class="modal-content feed-modal-content--compact">',
            '  <div class="modal-header">',
            '    <h3 id="bulkConfirmTitle">⚠️ Aktion bestätigen</h3>',
            '    <button class="modal-close" type="button" data-feed-close-modal="bulkConfirmModal">&times;</button>',
            '  </div>',
            '  <div class="modal-body">',
            '    <p id="bulkConfirmMessage"></p>',
            '    <p id="bulkConfirmWarning" class="feed-warning-text" hidden>⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>',
            '  </div>',
            '  <div class="modal-footer">',
            '    <button type="button" class="btn btn-secondary" data-feed-close-modal="bulkConfirmModal">Abbrechen</button>',
            '    <button type="button" id="bulkConfirmBtn" class="btn btn-primary">✅ Bestätigen</button>',
            '  </div>',
            '</div>'
        ].join('');
        document.body.appendChild(modal);

        modal.querySelector('#bulkConfirmBtn').addEventListener('click', function() {
            closeModal('bulkConfirmModal');
            if (typeof pendingBulkCallback === 'function') {
                var callback = pendingBulkCallback;
                pendingBulkCallback = null;
                callback();
            }
        });

        modal.querySelectorAll('[data-feed-close-modal]').forEach(function(button) {
            button.addEventListener('click', function() {
                closeModal(button.getAttribute('data-feed-close-modal'));
            });
        });

        return modal;
    }

    function openBulkConfirmModal(message, isDanger, onConfirm) {
        var modal = ensureBulkConfirmModal();
        document.getElementById('bulkConfirmMessage').textContent = message;
        document.getElementById('bulkConfirmWarning').hidden = !isDanger;

        var btn = document.getElementById('bulkConfirmBtn');
        btn.className = isDanger ? 'btn btn-danger' : 'btn btn-primary';
        btn.textContent = isDanger ? '🗑️ Endgültig löschen' : '✅ Bestätigen';

        pendingBulkCallback = onConfirm;
        openModal(modal.id);
    }

    function readJsonPayload(id) {
        var element = document.getElementById(id);
        if (!element) {
            return [];
        }

        try {
            return JSON.parse(element.value || '[]');
        } catch (error) {
            return [];
        }
    }

    function resetChannelForm() {
        var form = document.getElementById('channelForm');
        if (!form) return;
        form.reset();
        document.getElementById('channelModalTitle').textContent = '📡 Neuer Kanal';
        document.getElementById('channel_id').value = '';
        document.getElementById('channel_is_active').checked = true;
    }

    function resetCategoryForm() {
        var form = document.getElementById('categoryForm');
        if (!form) return;
        form.reset();
        document.getElementById('categoryModalTitle').textContent = '📁 Neuer Bereich';
        document.getElementById('cat_id').value = '';
        document.getElementById('cat_is_public').checked = true;
        document.getElementById('cat_icon').value = '📰';
        updateSlugPreview();
    }

    function resetDigestForm() {
        var form = document.getElementById('digestForm');
        if (!form) return;
        form.reset();
        document.getElementById('digestModalTitle').textContent = '📧 Neuer Digest';
        document.getElementById('digest_id').value = '';
        document.getElementById('digest_is_active').checked = true;
    }

    function updateSlugPreview() {
        var slugInput = document.getElementById('cat_slug');
        var preview = document.getElementById('slugPreview');
        if (slugInput && preview) {
            preview.textContent = slugInput.value || '…';
        }
    }

    function bindConfirmForms() {
        document.querySelectorAll('form[data-feed-confirm-message]').forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (form.dataset.feedConfirmAccepted === '1') {
                    form.dataset.feedConfirmAccepted = '0';
                    return;
                }

                event.preventDefault();
                openBulkConfirmModal(
                    form.getAttribute('data-feed-confirm-message') || 'Aktion wirklich ausführen?',
                    form.getAttribute('data-feed-confirm-danger') === '0' ? false : true,
                    function() {
                        form.dataset.feedConfirmAccepted = '1';
                        form.requestSubmit();
                    }
                );
            });
        });
    }

    function bindModalCloseButtons() {
        document.querySelectorAll('[data-feed-close-modal]').forEach(function(button) {
            button.addEventListener('click', function() {
                closeModal(button.getAttribute('data-feed-close-modal'));
            });
        });
    }

    function bindActionButtons() {
        document.querySelectorAll('[data-feed-bulk-channel-action]').forEach(function(button) {
            button.addEventListener('click', function() {
                submitChannelBulk(button.getAttribute('data-feed-bulk-channel-action') || '');
            });
        });

        document.querySelectorAll('[data-feed-bulk-category-action]').forEach(function(button) {
            button.addEventListener('click', function() {
                submitCategoryBulk(button.getAttribute('data-feed-bulk-category-action') || '');
            });
        });

        document.querySelectorAll('[data-feed-edit-channel]').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                editChannel(parseInt(button.getAttribute('data-feed-edit-channel') || '0', 10));
            });
        });

        document.querySelectorAll('[data-feed-edit-category]').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                editCategory(parseInt(button.getAttribute('data-feed-edit-category') || '0', 10));
            });
        });

        document.querySelectorAll('[data-feed-edit-digest]').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                editDigest(parseInt(button.getAttribute('data-feed-edit-digest') || '0', 10));
            });
        });

        document.querySelectorAll('[data-feed-delete-id][data-feed-delete-action]').forEach(function(button) {
            button.addEventListener('click', function() {
                openDeleteModal(
                    parseInt(button.getAttribute('data-feed-delete-id') || '0', 10),
                    button.getAttribute('data-feed-delete-name') || '',
                    button.getAttribute('data-feed-delete-action') || ''
                );
            });
        });

        document.querySelectorAll('[data-feed-toggle-catalog-selection]').forEach(function(button) {
            button.addEventListener('click', function() {
                toggleCatalogSelection(
                    button.getAttribute('data-feed-toggle-catalog-selection') || '',
                    button.getAttribute('data-feed-toggle-catalog-state') === '1'
                );
            });
        });

        document.querySelectorAll('[data-feed-tab-target]').forEach(function(button) {
            button.addEventListener('click', function() {
                switchTab(button.getAttribute('data-feed-tab-target') || '', button);
            });
        });
    }

    function initFeedAdminView() {
        feedAdminData.channels = readJsonPayload('feed-channels-data');
        feedAdminData.categories = readJsonPayload('feed-categories-data');
        feedAdminData.digests = readJsonPayload('feed-digests-data');

        var slugInput = document.getElementById('cat_slug');
        var nameInput = document.getElementById('cat_name');
        var categoryIdInput = document.getElementById('cat_id');

        if (slugInput) {
            slugInput.addEventListener('input', updateSlugPreview);
            updateSlugPreview();
        }

        if (nameInput && slugInput && categoryIdInput) {
            nameInput.addEventListener('input', function() {
                if (categoryIdInput.value) {
                    return;
                }

                slugInput.value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                updateSlugPreview();
            });
        }

        var radiusInput = document.querySelector('input[name="border_radius"]');
        var radiusPreview = document.getElementById('radiusPreview');
        if (radiusInput && radiusPreview) {
            radiusInput.addEventListener('input', function() {
                radiusPreview.textContent = radiusInput.value + 'px';
            });
        }

        var channelSelectAll = document.getElementById('channelSelectAll');
        if (channelSelectAll) {
            channelSelectAll.addEventListener('change', function() {
                toggleAllChannels(channelSelectAll.checked);
            });
        }

        document.querySelectorAll('.channel-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', updateChannelBulk);
        });

        var categorySelectAll = document.getElementById('categorySelectAll');
        if (categorySelectAll) {
            categorySelectAll.addEventListener('change', function() {
                toggleAllCategories(categorySelectAll.checked);
            });
        }

        document.querySelectorAll('.category-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', updateCategoryBulk);
        });

        var openChannelModalBtn = document.getElementById('openChannelModalBtn');
        if (openChannelModalBtn) {
            openChannelModalBtn.addEventListener('click', function() {
                resetChannelForm();
                openModal('channelModal');
            });
        }

        var openCategoryModalBtn = document.getElementById('openCategoryModalBtn');
        if (openCategoryModalBtn) {
            openCategoryModalBtn.addEventListener('click', function() {
                resetCategoryForm();
                openModal('categoryModal');
            });
        }

        var openDigestModalBtn = document.getElementById('openDigestModalBtn');
        if (openDigestModalBtn) {
            openDigestModalBtn.addEventListener('click', function() {
                resetDigestForm();
                openModal('digestModal');
            });
        }

        bindModalCloseButtons();
        bindActionButtons();
        bindConfirmForms();
    }

    window.editChannel = function(id) {
        var channel = feedAdminData.channels.find(function(entry) {
            return entry.id === id;
        });

        if (!channel) return;

        document.getElementById('channelModalTitle').textContent = '📡 Kanal bearbeiten';
        document.getElementById('channel_id').value = channel.id;
        document.getElementById('channel_name').value = channel.name;
        document.getElementById('channel_feed_url').value = channel.feed_url;
        document.getElementById('channel_site_url').value = channel.site_url;
        document.getElementById('channel_category_id').value = channel.category_id;
        document.getElementById('channel_description').value = channel.description;
        document.getElementById('channel_fetch_interval').value = channel.fetch_interval;
        document.getElementById('channel_max_items').value = channel.max_items;
        document.getElementById('channel_is_active').checked = !!channel.is_active;
        openModal('channelModal');
    };

    window.editCategory = function(id) {
        var category = feedAdminData.categories.find(function(entry) {
            return entry.id === id;
        });

        if (!category) return;

        document.getElementById('categoryModalTitle').textContent = '📁 Bereich bearbeiten';
        document.getElementById('cat_id').value = category.id;
        document.getElementById('cat_name').value = category.name;
        document.getElementById('cat_slug').value = category.slug;
        document.getElementById('cat_description').value = category.description;
        document.getElementById('cat_icon').value = category.icon;
        document.getElementById('cat_is_public').checked = !!category.is_public;
        document.getElementById('cat_sort_order').value = category.sort_order;
        document.getElementById('cat_layout').value = category.layout;
        document.getElementById('cat_items_per_page').value = category.items_per_page;
        updateSlugPreview();
        openModal('categoryModal');
    };

    window.editDigest = function(id) {
        var digest = feedAdminData.digests.find(function(entry) {
            return entry.id === id;
        });

        if (!digest) return;

        document.getElementById('digestModalTitle').textContent = '📧 Digest bearbeiten';
        document.getElementById('digest_id').value = digest.id;
        document.getElementById('digest_name').value = digest.name;
        document.getElementById('digest_email').value = digest.email;
        document.getElementById('digest_frequency').value = digest.frequency;
        document.getElementById('digest_is_active').checked = !!digest.is_active;
        document.querySelectorAll('.digest-cat-checkbox').forEach(function(checkbox) {
            checkbox.checked = digest.category_ids.includes(parseInt(checkbox.value, 10));
        });
        openModal('digestModal');
    };

    window.openDeleteModal = function(id, name, action) {
        document.getElementById('deleteModalId').value = id;
        document.getElementById('deleteModalName').textContent = name;
        document.getElementById('deleteModalAction').value = action;
        openModal('deleteModal');
    };

    window.toggleCatalogSelection = function(catalogKey, checked) {
        var wrapper = document.getElementById('catalog-list-' + catalogKey);
        if (!wrapper) return;

        wrapper.querySelectorAll('input[type="checkbox"]').forEach(function(checkbox) {
            checkbox.checked = checked;
        });
    };

    // ══════════════════════════════════════════════════════════════════════
    // Init
    // ══════════════════════════════════════════════════════════════════════

    document.addEventListener('DOMContentLoaded', function() {
        initColorSync();
        initFeedAdminView();
    });

})();
