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
                                    $error = 'Fehler: ' . $e->getMessage();
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
                                    $error = 'Fehler: ' . $e->getMessage();
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
            $error .= ' Benutzer-Abfrage: ' . $e->getMessage();
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
                "SELECT id, name FROM {$p}companies
                 WHERE user_id IS NULL OR user_id = 0
                 ORDER BY name ASC",
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
