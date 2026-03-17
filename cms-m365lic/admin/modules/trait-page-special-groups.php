<?php
/**
 * CMS M365 License – Special Groups Page
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_M365LIC_Page_Special_Groups_Trait
{
    public function render_special_groups_page(): void
    {
        $notice = '';
        $error = '';
        $editId = max(0, (int) ($_GET['edit'] ?? 0));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('m365lic_special_groups')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = (string) ($_POST['action'] ?? '');
                $groupId = max(0, (int) ($_POST['group_id'] ?? 0));

                if ($action === 'save_special_group') {
                    $groupKey = trim((string) ($_POST['group_key'] ?? 'special'));
                    $existingGroup = self::repo()->get_special_group_by_key($groupKey);

                    if ($existingGroup !== null && (int) ($existingGroup['id'] ?? 0) !== $groupId) {
                        $error = 'Der Gruppen-Key ist bereits vergeben. Bitte einen eindeutigen Key verwenden.';
                    } else {
                        self::repo()->save_special_group([
                            'id' => $groupId,
                            'group_key' => $groupKey,
                            'group_label' => trim((string) ($_POST['group_label'] ?? 'Gruppe')),
                            'description' => trim((string) ($_POST['description'] ?? '')),
                            'pricing_tier' => (string) ($_POST['pricing_tier'] ?? 'group'),
                            'default_markup_percent' => $_POST['default_markup_percent'] ?? 0,
                            'report_title' => trim((string) ($_POST['report_title'] ?? '')),
                            'report_intro' => trim((string) ($_POST['report_intro'] ?? '')),
                            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                        ]);
                        $notice = $groupId > 0 ? 'Gruppe aktualisiert.' : 'Gruppe angelegt.';
                        $editId = 0;
                    }
                } elseif ($action === 'delete_special_group' && $groupId > 0) {
                    if (self::repo()->delete_special_group($groupId)) {
                        $notice = 'Gruppe gelöscht.';
                        $editId = 0;
                    } else {
                        $error = 'Die Gruppe konnte nicht gelöscht werden. Bitte zuerst zugewiesene Benutzer entfernen oder umhängen.';
                    }
                } else {
                    $error = 'Ungültige Gruppen-Aktion.';
                }
            }
        }

        $groups = self::repo()->get_special_groups();
        $editingGroup = $editId > 0 ? self::repo()->get_special_group($editId) : null;
        $csrfToken = self::generate_nonce('m365lic_special_groups');
        ?>
        <div class="admin-page-header">
            <div>
                <h2>👥 Gruppen</h2>
                <p>Lege Gruppen mit Preisquelle, Standardwerten und Aufschlägen zentral an.</p>
            </div>
            <div class="header-actions">
                <a href="?page=m365lic-special-users" class="btn btn-secondary">🔐 User-Zuweisungen</a>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo self::esc($notice); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo self::esc($error); ?></div>
        <?php endif; ?>

        <div class="m365lic-admin-grid m365lic-admin-grid--wide">
            <div class="admin-card">
                <div class="m365lic-inline-head">
                    <div>
                        <p class="m365lic-section-kicker">Übersicht</p>
                        <h3>Vorhandene Gruppen</h3>
                    </div>
                    <span class="status-badge active"><?php echo (int) count($groups); ?> Gruppen</span>
                </div>

                <div class="m365lic-admin-note">Jede Gruppe bündelt Preisquelle, Standard-Aufschlag und optionale Report-Defaults. Benutzer können später genau einer vorhandenen Gruppe zugewiesen werden.</div>

                <?php if ($groups === []): ?>
                    <div class="empty-state">
                        <p class="m365lic-empty-state__icon">👥</p>
                        <p><strong>Noch keine Gruppe vorhanden</strong></p>
                        <p class="m365lic-empty-state__text">Lege rechts die erste Gruppe an, bevor du Benutzer zuweist.</p>
                    </div>
                <?php else: ?>
                    <div class="users-table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Gruppe</th>
                                    <th>Beschreibung</th>
                                    <th>Preisquelle</th>
                                    <th>Standard-Aufschlag</th>
                                    <th>Zugewiesene User</th>
                                    <th>Status</th>
                                    <th>Aktionen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groups as $group): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo self::esc((string) ($group['group_label'] ?? 'Gruppe')); ?></strong><br>
                                        <span class="m365lic-muted"><?php echo self::esc((string) ($group['group_key'] ?? 'special')); ?></span>
                                    </td>
                                    <td><?php echo self::esc((string) (($group['description'] ?? '') !== '' ? $group['description'] : '—')); ?></td>
                                    <td>
                                        <strong><?php echo self::esc(($group['pricing_tier'] ?? 'group') === 'member' ? 'Memberpreise' : 'Spezialpreise'); ?></strong>
                                    </td>
                                    <td><strong><?php echo self::esc(number_format((float) ($group['default_markup_percent'] ?? 0), 2, ',', '.')); ?>%</strong></td>
                                    <td><?php echo (int) ($group['assigned_users'] ?? 0); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo !empty($group['is_active']) ? 'active' : 'inactive'; ?>">
                                            <?php echo !empty($group['is_active']) ? 'Aktiv' : 'Inaktiv'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="m365lic-action-row">
                                            <a href="?page=m365lic-special-groups&amp;edit=<?php echo (int) ($group['id'] ?? 0); ?>" class="btn btn-secondary btn-sm">Bearbeiten</a>
                                            <button
                                                type="button"
                                                class="btn btn-danger btn-sm"
                                                data-m365lic-open-modal="m365licDeleteSpecialGroupModal"
                                                data-group-id="<?php echo (int) ($group['id'] ?? 0); ?>"
                                                data-group-name="<?php echo self::esc((string) ($group['group_label'] ?? 'Gruppe')); ?>"
                                            >Löschen</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <div class="admin-card">
                <div class="m365lic-inline-head">
                    <div>
                        <p class="m365lic-section-kicker">Pflege</p>
                        <h3><?php echo $editingGroup !== null ? 'Gruppe bearbeiten' : 'Neue Gruppe anlegen'; ?></h3>
                    </div>
                </div>

                <form method="POST" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="action" value="save_special_group">
                    <input type="hidden" name="group_id" value="<?php echo (int) ($editingGroup['id'] ?? 0); ?>">

                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="group_key">Gruppen-Key</label>
                            <input class="form-control" id="group_key" type="text" name="group_key" value="<?php echo self::esc((string) ($editingGroup['group_key'] ?? 'special')); ?>" maxlength="120" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="group_label">Gruppen-Label</label>
                            <input class="form-control" id="group_label" type="text" name="group_label" value="<?php echo self::esc((string) ($editingGroup['group_label'] ?? 'Gruppe')); ?>" maxlength="190" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Beschreibung</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Wofür ist diese Gruppe gedacht?"><?php echo self::esc((string) ($editingGroup['description'] ?? '')); ?></textarea>
                    </div>

                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="pricing_tier">Preisquelle für diese Gruppe</label>
                            <select class="form-control" id="pricing_tier" name="pricing_tier">
                                <option value="group" <?php echo (($editingGroup['pricing_tier'] ?? 'group') === 'group') ? 'selected' : ''; ?>>Spezialpreise aus dem Paketkatalog</option>
                                <option value="member" <?php echo (($editingGroup['pricing_tier'] ?? '') === 'member') ? 'selected' : ''; ?>>Memberpreise aus dem Paketkatalog</option>
                            </select>
                            <small class="m365lic-help-text">Damit legst du fest, ob User dieser Gruppe mit `group_price` oder mit `member_price` rechnen.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="default_markup_percent">Gruppenstandard-Aufschlag in %</label>
                            <input class="form-control" id="default_markup_percent" type="number" step="0.01" min="0" name="default_markup_percent" value="<?php echo self::esc(number_format((float) ($editingGroup['default_markup_percent'] ?? 0), 2, '.', '')); ?>">
                        </div>
                    </div>

                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="1" <?php echo !array_key_exists('is_active', (array) $editingGroup) || !empty($editingGroup['is_active']) ? 'checked' : ''; ?>>
                                Gruppe aktiv nutzbar
                            </label>
                        </div>
                    </div>

                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="report_title">Standard-Reporttitel</label>
                            <input class="form-control" id="report_title" type="text" name="report_title" value="<?php echo self::esc((string) ($editingGroup['report_title'] ?? '')); ?>" maxlength="190" placeholder="z. B. M365 Reseller-Report">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="report_intro">Standard-Reporteinleitung</label>
                            <textarea class="form-control" id="report_intro" name="report_intro" rows="3" placeholder="Einleitung für Reports dieser Gruppe"><?php echo self::esc((string) ($editingGroup['report_intro'] ?? '')); ?></textarea>
                        </div>
                    </div>

                    <div class="m365lic-inline-head">
                        <span class="m365lic-muted">Benutzer können später optional einen eigenen Aufschlag als Override erhalten.</span>
                        <div class="m365lic-action-row">
                            <?php if ($editingGroup !== null): ?>
                            <a href="?page=m365lic-special-groups" class="btn btn-secondary btn-sm">Abbrechen</a>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">💾 Gruppe speichern</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="m365licDeleteSpecialGroupModal" class="modal m365lic-modal" aria-hidden="true">
            <div class="modal-content m365lic-modal-content">
                <div class="modal-header">
                    <h3>👥 Gruppe löschen</h3>
                    <button type="button" class="modal-close" aria-label="Modal schließen" data-m365lic-close-modal="m365licDeleteSpecialGroupModal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll die Gruppe <strong id="m365licDeleteSpecialGroupName">diese Gruppe</strong> wirklich gelöscht werden?</p>
                    <p class="m365lic-danger-note">⚠️ Löschen ist nur möglich, wenn kein Benutzer mehr dieser Gruppe zugewiesen ist.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-m365lic-close-modal="m365licDeleteSpecialGroupModal">Abbrechen</button>
                    <form method="POST" class="m365lic-inline-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="delete_special_group">
                        <input type="hidden" name="group_id" id="m365licDeleteSpecialGroupId" value="0">
                        <button type="submit" class="btn btn-danger">Löschen</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}