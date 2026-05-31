<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Abosystem – Plugin-Rollen & Plan-Limits
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Subscription_Trait
{
    // ── 9. ABOSYSTEM ─────────────────────────────────────────────────────────

    public static function render_subscription(): void
    {
        self::check_access();

        $db     = \CMS\Database::instance();
        $p      = $db->getPrefix();
        $notice = '';
        $error  = '';

        $pluginRoles = self::get_plugin_roles();

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sub_action'])) {
            if (!self::verify_nonce('jpg_subscription_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = sanitize_key($_POST['sub_action'] ?? '');

                if ($action === 'save_plan_limits') {
                    $planId = (int) ($_POST['plan_id'] ?? 0);
                    if ($planId > 0) {
                        $limits = [
                            'max_profiles'        => (int) ($_POST['max_profiles'] ?? -1),
                            'max_active_profiles' => (int) ($_POST['max_active_profiles'] ?? -1),
                            'feature_workflow'    => !empty($_POST['feature_workflow']) ? 1 : 0,
                            'feature_export'      => !empty($_POST['feature_export']) ? 1 : 0,
                            'feature_analytics'   => !empty($_POST['feature_analytics']) ? 1 : 0,
                            'feature_branding'    => !empty($_POST['feature_branding']) ? 1 : 0,
                            'feature_api'         => !empty($_POST['feature_api']) ? 1 : 0,
                        ];
                        $all = self::get_plan_limits_all();
                        $all[$planId] = $limits;
                        self::save_jpg_setting('jpg_plan_limits', json_encode($all), $db, $p);
                        $notice = 'Plugin-Limits für Paket gespeichert.';
                    }
                } elseif ($action === 'save_role') {
                    $roleKey = sanitize_key($_POST['role_key'] ?? '');
                    $desc    = strip_tags($_POST['role_desc'] ?? '');
                    $custom  = self::get_custom_role_descriptions();
                    if ($roleKey && array_key_exists($roleKey, $pluginRoles)) {
                        $custom[$roleKey] = $desc;
                        self::save_jpg_setting('jpg_role_descriptions', json_encode($custom), $db, $p);
                        $notice = 'Rollenbeschreibung aktualisiert.';
                    }
                }
            }
        }

        // Nonce erst NACH dem POST-Handler generieren (verhindert Token-Überschreibung durch generateToken)
        $nonce = self::nonce('jpg_subscription_save');

        $plans = [];
        try {
            $plans = $db->get_results(
                "SELECT * FROM {$p}subscription_plans ORDER BY sort_order ASC, price_monthly ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) { /* ignore */ }

        $planLimitsAll = self::get_plan_limits_all();

        $cmsRoles = [];
        try {
            $cmsRoles = $db->get_results(
                "SELECT DISTINCT role FROM {$p}users WHERE role IS NOT NULL AND role != '' ORDER BY role ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) { /* ignore */ }

        $customRoleDescs = self::get_custom_role_descriptions();
        foreach ($pluginRoles as $key => &$roleData) {
            if (isset($customRoleDescs[$key])) {
                $roleData['description'] = $customRoleDescs[$key];
            }
        }
        unset($roleData);

        self::render_admin_view(
            'Abosystem',
            'jpg-subscription',
            JPG_DIR . 'admin/views/page-subscription.php',
            compact('notice', 'error', 'pluginRoles', 'nonce', 'plans', 'planLimitsAll', 'cmsRoles')
        );
    }

    /**
     * Liefert die 5 Standard-Plugin-Rollen-Definitionen.
     */
    public static function get_plugin_roles(): array
    {
        return [
            'mandant' => [
                'label'       => 'Mandant',
                'icon'        => '🏢',
                'color'       => 'member',
                'description' => 'Unternehmen-Zugang: kann eigene Stellen erstellen, bearbeiten und Bewerbungen verwalten.',
                'caps'        => ['create_profiles', 'edit_own_profiles', 'view_applications', 'manage_company'],
            ],
            'editor' => [
                'label'       => 'Redakteur',
                'icon'        => '✏️',
                'color'       => 'info',
                'description' => 'Kann Stellen erstellen und bearbeiten, aber nicht veröffentlichen oder löschen.',
                'caps'        => ['create_profiles', 'edit_own_profiles'],
            ],
            'viewer' => [
                'label'       => 'Betrachter',
                'icon'        => '👁️',
                'color'       => 'inactive',
                'description' => 'Nur-Lese-Zugriff auf eigene Profile und Bewerbungen.',
                'caps'        => ['view_own_profiles', 'view_applications'],
            ],
            'admin' => [
                'label'       => 'Plugin-Admin',
                'icon'        => '🔑',
                'color'       => 'admin',
                'description' => 'Vollzugriff auf alle Plugin-Bereiche inkl. Admin-Dashboard.',
                'caps'        => ['create_profiles', 'edit_all_profiles', 'delete_profiles', 'view_all_applications', 'manage_company', 'plugin_admin'],
            ],
            'blocked' => [
                'label'       => 'Gesperrt',
                'icon'        => '🚫',
                'color'       => 'danger',
                'description' => 'Kein Zugriff auf Plugin-Bereiche. Für temporäre oder dauerhafte Sperrung.',
                'caps'        => [],
            ],
        ];
    }

    private static function get_plan_limits_all(): array
    {
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $raw = $db->get_var(
                "SELECT setting_value FROM {$p}jpg_settings WHERE setting_key = 'jpg_plan_limits' LIMIT 1",
                []
            );
            $decoded = json_decode((string)($raw ?? '{}'), true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function get_custom_role_descriptions(): array
    {
        try {
            $db  = \CMS\Database::instance();
            $p   = $db->getPrefix();
            $raw = $db->get_var(
                "SELECT setting_value FROM {$p}jpg_settings WHERE setting_key = 'jpg_role_descriptions' LIMIT 1",
                []
            );
            $decoded = json_decode((string)($raw ?? '{}'), true);
            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private static function save_jpg_setting(string $key, string $value, \CMS\Database $db, string $p): void
    {
        try {
            $db->execute(
                "INSERT INTO {$p}jpg_settings (setting_key, setting_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                [$key, $value]
            );
        } catch (\Throwable $e) {
            error_log('CMS_JPG save_jpg_setting error: ' . $e->getMessage());
        }
    }
}
