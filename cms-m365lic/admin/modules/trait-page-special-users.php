<?php
/**
 * CMS M365 License – Special Users Page
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_M365LIC_Page_Special_Users_Trait
{
    public function render_special_users_page(): void
    {
        $notice = '';
        $error = '';
        $search = trim((string) ($_GET['s'] ?? ''));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('m365lic_special_users')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = (string) ($_POST['action'] ?? '');
                $userId = max(0, (int) ($_POST['user_id'] ?? 0));

                if ($action === 'save_special_user' && $userId > 0) {
                    self::repo()->save_special_user($userId, [
                        'group_key' => trim((string) ($_POST['group_key'] ?? 'special')),
                        'group_label' => trim((string) ($_POST['group_label'] ?? 'Spezialzugang')),
                        'special_markup_percent' => $_POST['special_markup_percent'] ?? 0,
                        'note' => trim((string) ($_POST['note'] ?? '')),
                        'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                    ]);
                    $notice = 'Spezialzugang gespeichert.';
                } elseif ($action === 'remove_special_user' && $userId > 0) {
                    self::repo()->remove_special_user($userId);
                    $notice = 'Spezialzugang entfernt.';
                } else {
                    $error = 'Ungültige Spezial-User-Aktion.';
                }
            }
        }

        $assignedUsers = self::repo()->get_special_users();
        $candidates = self::repo()->find_users_for_special_assignment($search);
        $csrfToken = self::generate_nonce('m365lic_special_users');
        ?>
        <div class="admin-page-header">
            <div>
                <h2>🔐 Spezial-User</h2>
                <p>Weise eingeloggten 365CMS-Benutzern einen geschützten Spezialbereich mit eigenem Preis-Kontext zu.</p>
            </div>
            <div class="header-actions">
                <a href="?page=m365lic-settings" class="btn btn-secondary">⚙️ Bereichs-Defaults</a>
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
                        <p class="m365lic-section-kicker">Zugriffe</p>
                        <h3>Aktive Spezialzugänge</h3>
                    </div>
                    <span class="status-badge active"><?php echo (int) count($assignedUsers); ?> Einträge</span>
                </div>

                <div class="m365lic-admin-note">Spezial-User erhalten zusätzlich zum Memberbereich einen geschützten Spezialbereich mit eigenem Preis-Kontext und individuellen Konditionshinweisen. Neue Gruppen legst du direkt an, indem du bei einem Benutzer einen neuen <strong>Gruppen-Key</strong> und ein neues <strong>Gruppen-Label</strong> speicherst.</div>

                <?php if (empty($assignedUsers)): ?>
                    <div class="empty-state">
                        <p class="m365lic-empty-state__icon">🕵️</p>
                        <p><strong>Noch keine Spezial-User zugewiesen</strong></p>
                        <p class="m365lic-empty-state__text">Sobald du unten einen Benutzer speicherst, erscheint hier der geschützte Spezialzugang.</p>
                    </div>
                <?php else: ?>
                    <div class="users-table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Benutzer</th>
                                    <th>Gruppe</th>
                                    <th>Aufschlag</th>
                                    <th>Hinweis</th>
                                    <th>Status</th>
                                    <th>Aktion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($assignedUsers as $user): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo self::esc((string) (($user['display_name'] ?? '') !== '' ? $user['display_name'] : $user['username'])); ?></strong><br>
                                        <span class="m365lic-muted"><?php echo self::esc((string) ($user['email'] ?? '')); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo self::esc((string) ($user['group_label'] ?? 'Spezialzugang')); ?></strong><br>
                                        <span class="m365lic-muted"><?php echo self::esc((string) ($user['group_key'] ?? 'special')); ?></span>
                                    </td>
                                    <td><strong><?php echo self::esc(number_format((float) ($user['special_markup_percent'] ?? 0), 2, ',', '.')); ?>%</strong></td>
                                    <td><?php echo self::esc((string) (($user['note'] ?? '') !== '' ? $user['note'] : '—')); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo !empty($user['is_active']) ? 'active' : 'inactive'; ?>">
                                            <?php echo !empty($user['is_active']) ? 'Aktiv' : 'Inaktiv'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button
                                            type="button"
                                            class="btn btn-danger btn-sm"
                                            data-m365lic-open-modal="m365licRemoveSpecialUserModal"
                                            data-user-id="<?php echo (int) ($user['user_id'] ?? 0); ?>"
                                            data-user-name="<?php echo self::esc((string) (($user['display_name'] ?? '') !== '' ? $user['display_name'] : $user['username'])); ?>"
                                        >Entfernen</button>
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
                        <h3>Benutzer zuweisen</h3>
                    </div>
                    <form method="GET" class="m365lic-search-form">
                        <input type="hidden" name="page" value="m365lic-special-users">
                        <input class="form-control" type="search" name="s" value="<?php echo self::esc($search); ?>" placeholder="Benutzer suchen …">
                    </form>
                </div>

                <p class="m365lic-help-text">Normale Mitglieder sehen nur die Member-Seite. Ein hier aktivierter Benutzer erhält zusätzlich genau die Spezial-Seite seiner Gruppe. Dort wird ausschließlich der Special-Preis plus der hier gepflegte Benutzer-Aufschlag angezeigt.</p>

                <div class="m365lic-special-users-stack">
                    <?php if (empty($candidates)): ?>
                        <div class="empty-state">
                            <p class="m365lic-empty-state__icon">🔎</p>
                            <p><strong>Keine passenden Benutzer gefunden</strong></p>
                            <p class="m365lic-empty-state__text">Passe die Suche an oder lege zuerst einen aktiven 365CMS-Benutzer an.</p>
                        </div>
                    <?php endif; ?>
                    <?php foreach ($candidates as $candidate): ?>
                        <?php
                        $displayName = trim((string) ($candidate['display_name'] ?? ''));
                        $displayName = $displayName !== '' ? $displayName : (string) ($candidate['username'] ?? '');
                        $isAssigned = !empty($candidate['group_label']) || !empty($candidate['special_is_active']);
                        ?>
                        <form method="POST" class="m365lic-special-user-card">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="save_special_user">
                            <input type="hidden" name="user_id" value="<?php echo (int) ($candidate['id'] ?? 0); ?>">

                            <div class="m365lic-special-user-card__head">
                                <div>
                                    <h4><?php echo self::esc($displayName); ?></h4>
                                    <p><?php echo self::esc((string) ($candidate['email'] ?? '')); ?> · Rolle: <?php echo self::esc((string) ($candidate['role'] ?? 'member')); ?></p>
                                </div>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="is_active" value="1" <?php echo !empty($candidate['special_is_active']) ? 'checked' : ''; ?>>
                                    Spezialzugang aktiv
                                </label>
                            </div>

                            <div class="m365lic-form-grid m365lic-form-grid--2">
                                <div class="form-group">
                                    <label class="form-label">Gruppen-Key</label>
                                    <input class="form-control" type="text" name="group_key" value="<?php echo self::esc((string) ($candidate['group_key'] ?? 'special')); ?>" placeholder="special, partner, vip">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gruppen-Label</label>
                                    <input class="form-control" type="text" name="group_label" value="<?php echo self::esc((string) ($candidate['group_label'] ?? 'Spezialzugang')); ?>" placeholder="z. B. Partner Plus">
                                </div>
                            </div>

                            <div class="m365lic-form-grid m365lic-form-grid--2">
                                <div class="form-group">
                                    <label class="form-label">Aufschlag auf Special-Preis in %</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="special_markup_percent" value="<?php echo self::esc(number_format((float) ($candidate['special_markup_percent'] ?? 0), 2, '.', '')); ?>" placeholder="z. B. 12.50">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Preislogik</label>
                                    <div class="m365lic-admin-note m365lic-admin-note--compact">
                                        Spezialpreis = <strong>Group/Special Preis</strong> aus dem Paketkatalog + <strong>dieser Benutzer-Aufschlag</strong>.
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Notiz</label>
                                <input class="form-control" type="text" name="note" value="<?php echo self::esc((string) ($candidate['note'] ?? '')); ?>" placeholder="Optionaler Hinweis für Vertrieb / interne Konditionen">
                            </div>

                            <div class="m365lic-inline-head">
                                <span class="m365lic-muted"><?php echo $isAssigned ? 'Bestehender Eintrag wird aktualisiert.' : 'Neuen Spezialzugang anlegen.'; ?></span>
                                <button type="submit" class="btn btn-primary btn-sm">Speichern</button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="m365licRemoveSpecialUserModal" class="modal" style="display:none;">
            <div class="modal-content m365lic-modal-content">
                <div class="modal-header">
                    <h3>🔐 Spezial-User entfernen</h3>
                    <button type="button" class="modal-close" aria-label="Modal schließen" data-m365lic-close-modal="m365licRemoveSpecialUserModal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll der Spezialzugang für <strong id="m365licRemoveSpecialUserName">diesen Benutzer</strong> wirklich entfernt werden?</p>
                    <p class="m365lic-danger-note">⚠️ Der Benutzer verliert damit sofort den Spezial-Preis-Kontext im geschützten Bereich.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-m365lic-close-modal="m365licRemoveSpecialUserModal">Abbrechen</button>
                    <form method="POST" id="m365licRemoveSpecialUserForm" class="m365lic-inline-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="remove_special_user">
                        <input type="hidden" name="user_id" id="m365licRemoveSpecialUserId" value="0">
                        <button type="submit" class="btn btn-danger">Entfernen</button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
