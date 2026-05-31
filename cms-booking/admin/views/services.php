<?php declare(strict_types=1); if (!defined('ABSPATH')) exit; ?>

<!-- Page Header -->
<div class="admin-page-header">
    <div>
        <h2>🛠️ Leistungen verwalten</h2>
        <p>Buchbare Leistungen je Anbieter konfigurieren</p>
    </div>
    <div class="header-actions">
        <button class="btn btn-primary" onclick="openModal('serviceModal')">➕ Neue Leistung</button>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($success)): ?>
<div class="alert alert-success">✅ <?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Tabelle -->
<div class="admin-card">
    <?php if (empty($items)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">🛠️</p>
            <p><strong>Keine Leistungen vorhanden</strong></p>
            <p class="text-muted">Erstellen Sie Ihre erste buchbare Leistung über den Button oben.</p>
        </div>
    <?php else: ?>
        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Leistung</th>
                        <th>Anbieter</th>
                        <th>Dauer</th>
                        <th>Preis</th>
                        <th>Typ</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                            <?php if (!empty($item['description'])): ?>
                            <div style="color:#64748b;font-size:.8rem;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo htmlspecialchars(strip_tags($item['description'])); ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['provider_name'] ?? '—'); ?></td>
                        <td><?php echo CMS_Booking_Services::format_duration((int) $item['duration_min']); ?></td>
                        <td><?php echo CMS_Booking_Services::format_price((int) $item['price_cents']); ?></td>
                        <td>
                            <?php
                            $typeLabels = [
                                'online' => '💻 Online',
                                'onsite' => '📍 Vor Ort',
                                'hybrid' => '🔀 Hybrid',
                            ];
                            echo htmlspecialchars((string) ($typeLabels[$item['location_type']] ?? $item['location_type']), ENT_QUOTES, 'UTF-8');
                            ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo htmlspecialchars($item['status'] === 'active' ? 'active' : 'inactive', ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo $item['status'] === 'active' ? 'Aktiv' : 'Inaktiv'; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display:flex;gap:.4rem;">
                                <a href="<?php echo htmlspecialchars(CMS_Booking_Admin_Pages::admin_url('services', ['edit' => (int) $item['id']]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                                <form method="POST" style="margin:0;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="service_id" value="<?php echo (int) $item['id']; ?>">
                                    <input type="hidden" name="service_action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Löschen">🗑️</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
        <div class="pagination" style="display:flex;gap:.5rem;justify-content:center;margin-top:1.5rem;">
            <?php if ($page > 1): ?>
                <a href="<?php echo htmlspecialchars(CMS_Booking_Admin_Pages::admin_url('services', ['paged' => (int) ($page - 1), 'q' => $search]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm">← Zurück</a>
            <?php endif; ?>
            <span style="padding:.375rem .875rem;color:#64748b;font-size:.875rem;">Seite <?php echo (int) $page; ?> von <?php echo (int) $pages; ?></span>
            <?php if ($page < $pages): ?>
                <a href="<?php echo htmlspecialchars(CMS_Booking_Admin_Pages::admin_url('services', ['paged' => (int) ($page + 1), 'q' => $search]), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary btn-sm">Weiter →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Bearbeiten-Card -->
<?php if ($editService): ?>
<div class="admin-card">
    <h3>✏️ Leistung bearbeiten: <?php echo htmlspecialchars($editService['title']); ?></h3>
    <form method="POST" class="admin-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="service_action" value="update">
        <input type="hidden" name="service_id" value="<?php echo (int) $editService['id']; ?>">

        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
            <div class="form-group">
                <label class="form-label">Titel <span style="color:#ef4444;">*</span></label>
                <input type="text" name="title" class="form-control"
                       value="<?php echo htmlspecialchars($editService['title']); ?>" required>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="active"   <?php echo $editService['status'] === 'active'   ? 'selected' : ''; ?>>Aktiv</option>
                    <option value="inactive" <?php echo $editService['status'] === 'inactive' ? 'selected' : ''; ?>>Inaktiv</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Beschreibung</label>
            <textarea name="description" class="form-control" style="min-height:80px;"><?php echo htmlspecialchars($editService['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
            <div class="form-group">
                <label class="form-label">Dauer (Min.)</label>
                <input type="number" name="duration_min" class="form-control" min="5" step="5"
                       value="<?php echo (int) $editService['duration_min']; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Puffer (Min.)</label>
                <input type="number" name="buffer_min" class="form-control" min="0" step="5"
                       value="<?php echo (int) $editService['buffer_min']; ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Preis (€)</label>
                <input type="number" name="price" class="form-control" min="0" step="0.01"
                       value="<?php echo number_format(((int) $editService['price_cents']) / 100, 2, '.', ''); ?>">
            </div>
        </div>

        <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
            <div class="form-group">
                <label class="form-label">Ort-Typ</label>
                <select name="location_type" class="form-control">
                    <option value="online" <?php echo ($editService['location_type'] ?? '') === 'online' ? 'selected' : ''; ?>>Online</option>
                    <option value="onsite" <?php echo ($editService['location_type'] ?? '') === 'onsite' ? 'selected' : ''; ?>>Vor Ort</option>
                    <option value="hybrid" <?php echo ($editService['location_type'] ?? '') === 'hybrid' ? 'selected' : ''; ?>>Hybrid</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Buchungstyp</label>
                <select name="booking_type" class="form-control">
                    <option value="confirmation" <?php echo ($editService['booking_type'] ?? '') === 'confirmation' ? 'selected' : ''; ?>>Bestätigung erforderlich</option>
                    <option value="instant"      <?php echo ($editService['booking_type'] ?? '') === 'instant'      ? 'selected' : ''; ?>>Sofortbuchung</option>
                    <option value="request"      <?php echo ($editService['booking_type'] ?? '') === 'request'      ? 'selected' : ''; ?>>Anfrage</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Meeting-URL</label>
            <input type="url" name="meeting_url" class="form-control"
                   value="<?php echo htmlspecialchars($editService['meeting_url'] ?? ''); ?>"
                   placeholder="https://zoom.us/j/...">
        </div>

        <div class="admin-card form-actions-card">
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">💾 Speichern</button>
                <a href="<?php echo htmlspecialchars(CMS_Booking_Admin_Pages::admin_url('services'), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary">↩️ Abbrechen</a>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Erstellen-Modal -->
<div id="serviceModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:700px;">
        <div class="modal-header">
            <h3>➕ Neue Leistung</h3>
            <button class="modal-close" onclick="closeModal('serviceModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="createServiceForm" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="service_action" value="create">

                <div class="form-group">
                    <label class="form-label">Anbieter <span style="color:#ef4444;">*</span></label>
                    <select name="provider_id" class="form-control" required>
                        <option value="">– Bitte wählen –</option>
                        <?php foreach ($providers as $prov): ?>
                        <option value="<?php echo (int) $prov['id']; ?>"><?php echo htmlspecialchars($prov['display_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Titel <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="z. B. Erstgespräch, Beratung 60 Min.">
                </div>

                <div class="form-group">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="description" class="form-control" style="min-height:80px;" placeholder="Was beinhaltet diese Leistung?"></textarea>
                </div>

                <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Dauer (Min.)</label>
                        <input type="number" name="duration_min" class="form-control" min="5" step="5" value="60">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Puffer (Min.)</label>
                        <input type="number" name="buffer_min" class="form-control" min="0" step="5" value="15">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Preis (€)</label>
                        <input type="number" name="price" class="form-control" min="0" step="0.01" value="0">
                        <small class="form-text">0 = Kostenlos</small>
                    </div>
                </div>

                <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Ort-Typ</label>
                        <select name="location_type" class="form-control">
                            <option value="online">Online</option>
                            <option value="onsite">Vor Ort</option>
                            <option value="hybrid">Hybrid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Buchungstyp</label>
                        <select name="booking_type" class="form-control">
                            <option value="confirmation">Bestätigung erforderlich</option>
                            <option value="instant">Sofortbuchung</option>
                            <option value="request">Anfrage</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Meeting-URL</label>
                    <input type="url" name="meeting_url" class="form-control" placeholder="https://zoom.us/j/...">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('serviceModal')">Abbrechen</button>
            <button type="submit" form="createServiceForm" class="btn btn-primary">💾 Erstellen</button>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).style.display = 'flex'; }
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
window.addEventListener('click', function(e) {
    document.querySelectorAll('.modal').forEach(function(m) {
        if (e.target === m) closeModal(m.id);
    });
});
</script>
