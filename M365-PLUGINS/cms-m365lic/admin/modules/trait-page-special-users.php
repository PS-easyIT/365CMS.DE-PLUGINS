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
        $search = sanitize_text_field((string) ($_GET['s'] ?? ''));
        $escapedSearch = htmlspecialchars($search, ENT_QUOTES, 'UTF-8');
        $groups = $this->normalize_special_user_groups(self::repo()->get_special_groups(false));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('m365lic_special_users')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = (string) ($_POST['action'] ?? '');
                $userId = max(0, (int) ($_POST['user_id'] ?? 0));

                if ($action === 'save_special_user' && $userId > 0) {
                    $groupId = max(0, (int) ($_POST['group_id'] ?? 0));

                    if ($groupId <= 0) {
                        $error = 'Bitte zuerst eine vorhandene Spezialgruppe auswählen.';
                    } else {
                        self::repo()->save_special_user($userId, [
                            'group_id' => $groupId,
                            'user_markup_override_percent' => $_POST['user_markup_override_percent'] ?? null,
                            'note' => trim((string) ($_POST['note'] ?? '')),
                            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
                        ]);
                        $notice = 'User-Zuweisung gespeichert.';
                    }
                } elseif ($action === 'remove_special_user' && $userId > 0) {
                    self::repo()->remove_special_user($userId);
                    $notice = 'User-Zuweisung entfernt.';
                } else {
                    $error = 'Ungültige User-Aktion.';
                }
            }
        }

        $assignedUsers = $this->normalize_special_user_assignments(self::repo()->get_special_users());
        $candidates = $this->normalize_special_user_candidates(self::repo()->find_users_for_special_assignment($search));
        $csrfToken = self::generate_nonce('m365lic_special_users');
        ?>
        <div class="admin-page-header">
            <div>
                <h2>🔐 User</h2>
                <p>Weise eingeloggten 365CMS-Benutzern eine Gruppe mit passender Preislogik zu.</p>
            </div>
            <div class="header-actions">
                <a href="?page=m365lic-special-groups" class="btn btn-secondary">👥 Gruppen verwalten</a>
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
                        <h3>Aktive User-Zuweisungen</h3>
                    </div>
                    <span class="status-badge active"><?php echo (int) count($assignedUsers); ?> Einträge</span>
                </div>

                <div class="m365lic-admin-note">Zugewiesene User erhalten zusätzlich zum Memberbereich einen Gruppen-Kontext mit eigener Preisquelle, Aufschlägen und Report-Defaults. Benutzer werden bestehenden Gruppen zugewiesen.</div>

                <?php if (empty($assignedUsers)): ?>
                    <div class="empty-state">
                        <p class="m365lic-empty-state__icon">🕵️</p>
                        <p><strong>Noch keine User zugewiesen</strong></p>
                        <p class="m365lic-empty-state__text">Sobald du unten einen Benutzer speicherst, erscheint hier die Gruppen-Zuweisung.</p>
                    </div>
                <?php else: ?>
                    <div class="users-table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>Benutzer</th>
                                    <th>Gruppe</th>
                                    <th>Preisquelle</th>
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
                                        <strong><?php echo self::esc((string) ($user['group_label'] ?? 'Gruppe')); ?></strong><br>
                                        <span class="m365lic-muted"><?php echo self::esc((string) ($user['group_key'] ?? 'special')); ?></span>
                                    </td>
                                    <td><?php echo self::esc(($user['group_pricing_tier'] ?? 'group') === 'member' ? 'Memberpreise' : 'Spezialpreise'); ?></td>
                                    <td>
                                        <strong><?php echo self::esc(number_format((float) ($user['effective_markup_percent'] ?? 0), 2, ',', '.')); ?>%</strong><br>
                                        <span class="m365lic-muted">
                                            <?php echo ($user['user_markup_override_percent'] ?? null) !== null ? 'User-Override' : 'Gruppenstandard'; ?>
                                        </span>
                                    </td>
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
                        <input class="form-control" type="search" name="s" value="<?php echo $escapedSearch; ?>" placeholder="Benutzer suchen …">
                    </form>
                </div>

                <p class="m365lic-help-text">Normale Mitglieder sehen nur die Member-Seite. Ein hier aktivierter Benutzer erhält zusätzlich genau die Gruppenseite seiner Gruppe. Dort wird – je Gruppe – entweder mit Memberpreisen oder mit Spezialpreisen gerechnet.</p>

                <?php if ($groups === []): ?>
                    <div class="alert alert-error">❌ Bitte zuerst unter <a href="?page=m365lic-special-groups">Gruppen</a> mindestens eine aktive Gruppe anlegen.</div>
                <?php endif; ?>

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
                        $isAssigned = (int) ($candidate['group_id'] ?? 0) > 0 || !empty($candidate['special_is_active']);
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
                                    <label class="form-label">Spezialgruppe</label>
                                    <select class="form-control" name="group_id" <?php echo $groups === [] ? 'disabled' : ''; ?>>
                                        <option value="0">— Gruppe wählen —</option>
                                        <?php foreach ($groups as $group): ?>
                                        <option value="<?php echo (int) ($group['id'] ?? 0); ?>" <?php echo (int) ($candidate['group_id'] ?? 0) === (int) ($group['id'] ?? 0) ? 'selected' : ''; ?>>
                                            <?php echo self::esc((string) ($group['group_label'] ?? 'Gruppe')); ?> · <?php echo self::esc((string) ($group['group_key'] ?? 'special')); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Aktiver Preisstandard</label>
                                    <div class="m365lic-admin-note m365lic-admin-note--compact">
                                        <?php if ((int) ($candidate['group_id'] ?? 0) > 0): ?>
                                            Gruppe: <strong><?php echo self::esc((string) ($candidate['assigned_group_label'] ?? $candidate['group_label'] ?? 'Gruppe')); ?></strong><br>
                                            Preisquelle: <strong><?php echo self::esc(($candidate['group_pricing_tier'] ?? 'group') === 'member' ? 'Memberpreise' : 'Spezialpreise'); ?></strong><br>
                                            Standard: <strong><?php echo self::esc(number_format((float) ($candidate['default_markup_percent'] ?? 0), 2, ',', '.')); ?>%</strong>
                                        <?php else: ?>
                                            Wähle links eine vorhandene Gruppe. Deren Preisquelle und Standardwerte gelten automatisch für den Benutzer.
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="m365lic-form-grid m365lic-form-grid--2">
                                <div class="form-group">
                                    <label class="form-label">Benutzer-Aufschlag Override in %</label>
                                    <input class="form-control" type="number" step="0.01" min="0" name="user_markup_override_percent" value="<?php echo ($candidate['user_markup_override_percent'] ?? null) !== null ? self::esc(number_format((float) $candidate['user_markup_override_percent'], 2, '.', '')) : ''; ?>" placeholder="leer = Gruppenstandard">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Preislogik</label>
                                    <div class="m365lic-admin-note m365lic-admin-note--compact">
                                        Preis = <strong><?php echo self::esc(((string) ($candidate['group_pricing_tier'] ?? 'group')) === 'member' ? 'Memberpreis' : 'Spezialpreis'); ?></strong> aus dem Paketkatalog + <strong>User-Override</strong> oder, wenn leer, <strong>Gruppenstandard-Aufschlag</strong>.
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Notiz</label>
                                <input class="form-control" type="text" name="note" value="<?php echo self::esc((string) ($candidate['note'] ?? '')); ?>" placeholder="Optionaler Hinweis für Vertrieb / interne Konditionen">
                            </div>

                            <div class="m365lic-inline-head">
                                <span class="m365lic-muted"><?php echo $isAssigned ? 'Bestehender Eintrag wird aktualisiert.' : 'Neue User-Zuweisung anlegen.'; ?></span>
                                <button type="submit" class="btn btn-primary btn-sm" <?php echo $groups === [] ? 'disabled' : ''; ?>>Speichern</button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="m365licRemoveSpecialUserModal" class="modal m365lic-modal" aria-hidden="true">
            <div class="modal-content m365lic-modal-content">
                <div class="modal-header">
                    <h3>🔐 User entfernen</h3>
                    <button type="button" class="modal-close" aria-label="Modal schließen" data-m365lic-close-modal="m365licRemoveSpecialUserModal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll die Gruppen-Zuweisung für <strong id="m365licRemoveSpecialUserName">diesen Benutzer</strong> wirklich entfernt werden?</p>
                    <p class="m365lic-danger-note">⚠️ Der Benutzer verliert damit sofort den zusätzlichen Gruppen-Kontext im geschützten Bereich.</p>
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

    /**
     * @param array<int,array<string,mixed>> $groups
     * @return array<int,array<string,mixed>>
     */
    private function normalize_special_user_groups(array $groups): array
    {
        $normalized = [];

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $normalized[] = [
                'id' => max(0, (int) ($group['id'] ?? 0)),
                'group_label' => sanitize_text_field((string) ($group['group_label'] ?? 'Gruppe')),
                'group_key' => sanitize_key((string) ($group['group_key'] ?? 'special')),
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int,array<string,mixed>> $assignedUsers
     * @return array<int,array<string,mixed>>
     */
    private function normalize_special_user_assignments(array $assignedUsers): array
    {
        $normalized = [];

        foreach ($assignedUsers as $user) {
            if (!is_array($user)) {
                continue;
            }

            $displayName = trim((string) ($user['display_name'] ?? ''));
            $username = sanitize_text_field((string) ($user['username'] ?? ''));

            $normalized[] = [
                'user_id' => max(0, (int) ($user['user_id'] ?? 0)),
                'display_name' => sanitize_text_field($displayName !== '' ? $displayName : $username),
                'username' => $username,
                'email' => sanitize_email((string) ($user['email'] ?? '')),
                'group_label' => sanitize_text_field((string) ($user['group_label'] ?? 'Gruppe')),
                'group_key' => sanitize_key((string) ($user['group_key'] ?? 'special')),
                'group_pricing_tier' => (string) (($user['group_pricing_tier'] ?? 'group') === 'member' ? 'member' : 'group'),
                'effective_markup_percent' => (float) ($user['effective_markup_percent'] ?? 0),
                'user_markup_override_percent' => isset($user['user_markup_override_percent']) && $user['user_markup_override_percent'] !== ''
                    ? (float) $user['user_markup_override_percent']
                    : null,
                'note' => sanitize_text_field((string) ($user['note'] ?? '')),
                'is_active' => !empty($user['is_active']) ? 1 : 0,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<int,array<string,mixed>> $candidates
     * @return array<int,array<string,mixed>>
     */
    private function normalize_special_user_candidates(array $candidates): array
    {
        $normalized = [];

        foreach ($candidates as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $displayName = trim((string) ($candidate['display_name'] ?? ''));
            $username = sanitize_text_field((string) ($candidate['username'] ?? ''));

            $normalized[] = [
                'id' => max(0, (int) ($candidate['id'] ?? 0)),
                'display_name' => sanitize_text_field($displayName !== '' ? $displayName : $username),
                'username' => $username,
                'email' => sanitize_email((string) ($candidate['email'] ?? '')),
                'role' => sanitize_key((string) ($candidate['role'] ?? 'member')),
                'special_is_active' => !empty($candidate['special_is_active']) ? 1 : 0,
                'group_id' => max(0, (int) ($candidate['group_id'] ?? 0)),
                'assigned_group_label' => sanitize_text_field((string) ($candidate['assigned_group_label'] ?? '')),
                'group_label' => sanitize_text_field((string) ($candidate['group_label'] ?? 'Gruppe')),
                'group_pricing_tier' => (string) (($candidate['group_pricing_tier'] ?? 'group') === 'member' ? 'member' : 'group'),
                'default_markup_percent' => (float) ($candidate['default_markup_percent'] ?? 0),
                'user_markup_override_percent' => isset($candidate['user_markup_override_percent']) && $candidate['user_markup_override_percent'] !== ''
                    ? (float) $candidate['user_markup_override_percent']
                    : null,
                'note' => sanitize_text_field((string) ($candidate['note'] ?? '')),
            ];
        }

        return $normalized;
    }
}
