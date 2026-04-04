<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Benutzer & Mandanten-Verwaltung
 *
 * Beinhaltet Firma=Mandant-Automatik:
 * Wird einem Benutzer eine Firma zugewiesen oder neu angelegt, wird die
 * Plugin-Rolle automatisch auf „mandant" gesetzt, sofern noch keine
 * höherwertige Rolle hinterlegt ist.
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Users_Trait
{
    // ── 10. BENUTZER & MANDANTEN ──────────────────────────────────────────────

    public static function render_users(): void
    {
        self::check_access();

        $db     = \CMS\Database::instance();
        $p      = $db->getPrefix();
        $notice = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['users_action'])) {
            if (!self::verify_nonce('jpg_users_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = sanitize_key($_POST['users_action'] ?? '');
                $uid    = (int) ($_POST['target_user_id'] ?? 0);

                if ($uid > 0) {
                    switch ($action) {
                        case 'set_role':
                            $role    = sanitize_key($_POST['jpg_role'] ?? '');
                            $allowed = ['mandant', 'editor', 'viewer', 'admin', 'blocked', ''];
                            if (in_array($role, $allowed, true)) {
                                self::set_user_meta($uid, 'jpg_plugin_role', $role);
                                $notice = 'Rolle gespeichert.';
                            }
                            break;

                        case 'assign_company':
                            $companyId = (int) ($_POST['company_id'] ?? 0);
                            if ($companyId > 0) {
                                try {
                                    $db->execute(
                                        "UPDATE {$p}companies SET user_id = ? WHERE id = ?",
                                        [$uid, $companyId]
                                    );
                                    // Firma = Mandant: Plugin-Rolle automatisch auf 'mandant' setzen,
                                    // sofern noch keine höherwertige Rolle vergeben wurde
                                    $existingRole = $db->get_var(
                                        "SELECT meta_value FROM {$p}user_meta
                                         WHERE user_id = ? AND meta_key = 'jpg_plugin_role' LIMIT 1",
                                        [$uid]
                                    );
                                    if (empty($existingRole) || $existingRole === '') {
                                        self::set_user_meta($uid, 'jpg_plugin_role', 'mandant');
                                    }
                                    $notice = 'Unternehmen zugewiesen. Rolle automatisch auf Mandant gesetzt.';
                                } catch (\Throwable $e) {
                                    self::log_user_admin_exception('assign_company', $e);
                                    $error = 'Das Unternehmen konnte nicht zugewiesen werden.';
                                }
                            }
                            break;

                        case 'create_company':
                            $companyName = sanitize_text_field($_POST['new_company_name'] ?? '');
                            if ($companyName !== '') {
                                try {
                                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $companyName));
                                    $db->execute(
                                        "INSERT INTO {$p}companies (user_id, name, slug, status, created_at)
                                         VALUES (?, ?, ?, 'active', NOW())
                                         ON DUPLICATE KEY UPDATE user_id = VALUES(user_id)",
                                        [$uid, $companyName, $slug]
                                    );
                                    // Firma = Mandant: Plugin-Rolle automatisch auf 'mandant' setzen
                                    $existingRole = $db->get_var(
                                        "SELECT meta_value FROM {$p}user_meta
                                         WHERE user_id = ? AND meta_key = 'jpg_plugin_role' LIMIT 1",
                                        [$uid]
                                    );
                                    if (empty($existingRole) || $existingRole === '') {
                                        self::set_user_meta($uid, 'jpg_plugin_role', 'mandant');
                                    }
                                    $notice = 'Unternehmen angelegt, zugewiesen und Rolle auf Mandant gesetzt.';
                                } catch (\Throwable $e) {
                                    self::log_user_admin_exception('create_company', $e);
                                    $error = 'Das Unternehmen konnte nicht angelegt werden.';
                                }
                            }
                            break;
                    }
                }
            }
        }

        // Nonce erst NACH dem POST-Handler generieren (verhindert Token-Überschreibung durch generateToken)
        $nonce = self::nonce('jpg_users_save');

        $users = [];
        try {
            $users = $db->get_results(
                "SELECT u.id, u.username, u.email, u.display_name, u.role, u.created_at,
                        c.id AS company_id, c.name AS company_name,
                        (SELECT COUNT(*) FROM {$p}jpg_profiles jp WHERE jp.created_by = u.id) AS job_count
                 FROM {$p}users u
                 LEFT JOIN {$p}companies c ON c.user_id = u.id
                 ORDER BY u.created_at DESC",
                []
            ) ?: [];
        } catch (\Throwable $e) {
            self::log_user_admin_exception('load_users', $e);
            $error .= ' Benutzer konnten aktuell nicht vollständig geladen werden.';
        }

        $userMeta = [];
        if (!empty($users)) {
            try {
                $ids   = implode(',', array_map(fn($u) => (int)$u->id, $users));
                $metas = $db->get_results(
                    "SELECT user_id, meta_key, meta_value FROM {$p}user_meta
                     WHERE user_id IN ({$ids}) AND meta_key = 'jpg_plugin_role'",
                    []
                ) ?: [];
                foreach ($metas as $m) {
                    $userMeta[(int)$m->user_id] = $m->meta_value;
                }
            } catch (\Throwable $e) { /* user_meta optional */ }
        }

        $unassignedCompanies = [];
        try {
            $unassignedCompanies = $db->get_results(
                "SELECT c.id, c.name, c.user_id, u.display_name AS assigned_user
                 FROM {$p}companies c
                 LEFT JOIN {$p}users u ON u.id = c.user_id
                 ORDER BY c.name ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) { /* ignore */ }

        $pluginRoles = self::get_plugin_roles();

        $cmsRoles = [];
        try {
            $cmsRoles = $db->get_results(
                "SELECT DISTINCT role FROM {$p}users WHERE role IS NOT NULL AND role != '' ORDER BY role ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) { /* ignore */ }

        require JPG_DIR . 'admin/views/page-users.php';
    }

    /**
     * AJAX-Endpoint: Benutzer-Aktionen (POST /api/jpg/admin/users-action)
     * Gibt JSON zurück – wird VOR dem HTML-Layout aufgerufen, Header sauber möglich.
     *
     * @since 0.9.4
     */
    public static function handle_users_ajax(): void
    {
        // Ausgabepuffer leeren, damit JSON-Header sauber gesendet werden können
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');

        // Admin-Zugriff prüfen
        if (!class_exists('CMS\Auth') || !\CMS\Auth::instance()->isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Zugriff verweigert.']);
            exit;
        }

        // CSRF-Token verifizieren
        if (!self::verify_nonce('jpg_users_save')) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen. Bitte Seite neu laden.']);
            exit;
        }

        $db     = \CMS\Database::instance();
        $p      = $db->getPrefix();
        $action = sanitize_key($_POST['users_action'] ?? '');
        $uid    = (int) ($_POST['target_user_id'] ?? 0);

        if ($uid <= 0) {
            echo json_encode(['success' => false, 'error' => 'Ungültige Benutzer-ID.']);
            exit;
        }

        $roleLabels = [
            ''        => 'Standard',
            'mandant' => 'Mandant',
            'editor'  => 'Redakteur',
            'viewer'  => 'Betrachter',
            'admin'   => 'Plugin-Admin',
            'blocked' => 'Gesperrt',
        ];
        $roleBadges = [
            ''        => 'inactive',
            'mandant' => 'member',
            'editor'  => 'inactive',
            'viewer'  => 'inactive',
            'admin'   => 'admin',
            'blocked' => 'danger',
        ];

        try {
            switch ($action) {

                case 'set_role':
                    $role    = sanitize_key($_POST['jpg_role'] ?? '');
                    $allowed = ['mandant', 'editor', 'viewer', 'admin', 'blocked', ''];
                    if (!in_array($role, $allowed, true)) {
                        echo json_encode(['success' => false, 'error' => 'Ungültige Rolle.']);
                        exit;
                    }
                    self::set_user_meta($uid, 'jpg_plugin_role', $role);
                    echo json_encode([
                        'success'    => true,
                        'message'    => 'Plugin-Rolle erfolgreich gespeichert.',
                        'role'       => $role,
                        'role_label' => $roleLabels[$role] ?? $role,
                        'role_badge' => $roleBadges[$role] ?? 'inactive',
                    ]);
                    break;

                case 'assign_company':
                    $companyId = (int) ($_POST['company_id'] ?? 0);
                    if ($companyId <= 0) {
                        echo json_encode(['success' => false, 'error' => 'Kein Unternehmen ausgewählt.']);
                        exit;
                    }
                    $db->execute(
                        "UPDATE {$p}companies SET user_id = ? WHERE id = ?",
                        [$uid, $companyId]
                    );
                    $autoRole = false;
                    $existingRole = $db->get_var(
                        "SELECT meta_value FROM {$p}user_meta
                         WHERE user_id = ? AND meta_key = 'jpg_plugin_role' LIMIT 1",
                        [$uid]
                    );
                    if (empty($existingRole)) {
                        self::set_user_meta($uid, 'jpg_plugin_role', 'mandant');
                        $autoRole = true;
                    }
                    $company = $db->get_row(
                        "SELECT name FROM {$p}companies WHERE id = ? LIMIT 1",
                        [$companyId]
                    );
                    echo json_encode([
                        'success'      => true,
                        'message'      => 'Unternehmen zugewiesen.' . ($autoRole ? ' Rolle auf Mandant gesetzt.' : ''),
                        'company_name' => $company ? $company->name : '',
                        'company_id'   => $companyId,
                        'auto_role'    => $autoRole,
                    ]);
                    break;

                case 'create_company':
                    $companyName = sanitize_text_field($_POST['new_company_name'] ?? '');
                    if ($companyName === '') {
                        echo json_encode(['success' => false, 'error' => 'Unternehmensname darf nicht leer sein.']);
                        exit;
                    }
                    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', trim($companyName)));
                    if ($slug === '') {
                        $slug = 'unternehmen-' . time();
                    }
                    $db->execute(
                        "INSERT INTO {$p}companies (user_id, name, slug, status, created_at)
                         VALUES (?, ?, ?, 'active', NOW())
                         ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), name = VALUES(name)",
                        [$uid, $companyName, $slug]
                    );
                    $existingRole = $db->get_var(
                        "SELECT meta_value FROM {$p}user_meta
                         WHERE user_id = ? AND meta_key = 'jpg_plugin_role' LIMIT 1",
                        [$uid]
                    );
                    if (empty($existingRole)) {
                        self::set_user_meta($uid, 'jpg_plugin_role', 'mandant');
                    }
                    $newCompany = $db->get_row(
                        "SELECT id FROM {$p}companies WHERE slug = ? LIMIT 1",
                        [$slug]
                    );
                    echo json_encode([
                        'success'      => true,
                        'message'      => 'Unternehmen "' . htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') . '" angelegt und zugewiesen.',
                        'company_name' => $companyName,
                        'company_id'   => $newCompany ? (int)$newCompany->id : 0,
                    ]);
                    break;

                default:
                    echo json_encode(['success' => false, 'error' => 'Unbekannte Aktion.']);
            }
        } catch (\Throwable $e) {
            self::log_user_admin_exception('handle_users_ajax', $e);
            echo json_encode(['success' => false, 'error' => 'Die Benutzeraktion konnte nicht gespeichert werden.']);
        }
        exit;
    }

    private static function log_user_admin_exception(string $context, \Throwable $e): void
    {
        error_log('CMS_JPG users [' . $context . ']: ' . $e->getMessage());
    }

    /**
     * Schreibt einen user_meta-Wert für das Plugin.
     */
    private static function set_user_meta(int $userId, string $key, string $value): void
    {
        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();
        try {
            $db->execute(
                "INSERT INTO {$p}user_meta (user_id, meta_key, meta_value)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)",
                [$userId, $key, $value]
            );
        } catch (\Throwable $e) {
            try {
                $existing = $db->get_var(
                    "SELECT id FROM {$p}user_meta WHERE user_id = ? AND meta_key = ?",
                    [$userId, $key]
                );
                if ($existing) {
                    $db->execute(
                        "UPDATE {$p}user_meta SET meta_value = ? WHERE user_id = ? AND meta_key = ?",
                        [$value, $userId, $key]
                    );
                } else {
                    $db->execute(
                        "INSERT INTO {$p}user_meta (user_id, meta_key, meta_value) VALUES (?, ?, ?)",
                        [$userId, $key, $value]
                    );
                }
            } catch (\Throwable $e2) { /* silent */ }
        }
    }
}
