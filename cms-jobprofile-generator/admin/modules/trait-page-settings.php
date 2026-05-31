<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Einstellungen + System-Health-Monitor
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Settings_Trait
{
    // ── 5. EINSTELLUNGEN ─────────────────────────────────────────────────────

    public static function render_settings(): void
    {
        self::check_access();

        $tab    = sanitize_key($_GET['tab'] ?? 'general');
        $notice = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab !== 'health') {
            if (!self::verify_nonce('jpg_settings_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_settings_post($tab);
            }
        }

        $tabs = [
            'general'       => 'Allgemein',
            'permissions'   => 'Berechtigungen',
            'workflow'      => 'Workflow',
            'notifications' => 'Benachrichtigungen',
            'system'        => 'System-Info',
            'audit-log'     => '🔍 Audit-Log',
            'health'        => '🏥 System-Health',
        ];

        $settings = self::get_all_plugin_settings();

        // Phase 9: Audit-Log-Daten laden
        $auditLog      = [];
        $filterCompany = 0;
        $filterOptions = [];
        if ($tab === 'audit-log' && class_exists('CMS_JPG_Workflow')) {
            $filterCompany = (int) ($_GET['filter_company'] ?? 0);
            $auditLog      = CMS_JPG_Workflow::get_audit_log(100, $filterCompany);
            $db2 = \CMS\Database::instance();
            $p2  = $db2->getPrefix();
            $filterOptions = $db2->get_results(
                "SELECT DISTINCT c.id, c.name AS company_name
                 FROM {$p2}jpg_admin_access_log l
                 JOIN {$p2}companies c ON c.id = l.target_company_id
                 ORDER BY c.name ASC LIMIT 200",
                []
            );
        }

        // Phase 14.1: Health-Monitor Daten laden
        $healthData = [];
        if ($tab === 'health') {
            $healthData = self::get_health_data();
        }

        // Phase 14.1: POST-Handler für Health-Aktionen (Bereinigung)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'health') {
            if (self::verify_nonce('jpg_settings_save')) {
                $healthAction = sanitize_key($_POST['health_action'] ?? '');
                if ($healthAction === 'cleanup_orphans') {
                    [$notice, $error] = self::cleanup_orphan_files();
                } elseif ($healthAction === 'cleanup_expired') {
                    $days = max(30, (int)($_POST['retention_days'] ?? 90));
                    [$notice, $error] = self::cleanup_expired_applications($days);
                }
                $healthData = self::get_health_data();
            } else {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            }
        }

        self::render_admin_view(
            'Einstellungen',
            'jpg-settings',
            JPG_DIR . 'admin/views/page-settings.php',
            compact(
                'tab',
                'notice',
                'error',
                'tabs',
                'settings',
                'auditLog',
                'filterCompany',
                'filterOptions',
                'healthData'
            )
        );
    }

    /** @return array{string, string} */
    private static function handle_settings_post(string $tab): array
    {
        $notice = '';
        $error  = '';
        $db     = \CMS\Database::instance();
        $p      = $db->getPrefix();

        $allowed = [
            'general'       => ['default_status', 'profiles_per_page', 'slug_prefix'],
            'permissions'   => ['role_create', 'role_edit', 'role_delete', 'role_publish'],
            'workflow'      => ['review_required', 'approval_required', 'notify_on_publish'],
            'notifications' => ['notify_email', 'notify_on_create', 'notify_on_delete'],
        ];

        $keys = $allowed[$tab] ?? [];
        $stmt = $db->prepare(
            "INSERT INTO {$p}jpg_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        foreach ($keys as $key) {
            $value = sanitize_text_field($_POST[$key] ?? '');
            $sKey  = 'jpg_' . $key;
            $stmt->execute([$sKey, $value]);
        }

        $notice = 'Einstellungen gespeichert.';
        return [$notice, $error];
    }

    /** @return array<string, string> */
    private static function get_all_plugin_settings(): array
    {
        $db   = \CMS\Database::instance();
        $p    = $db->getPrefix();
        $rows = $db->get_results(
            "SELECT setting_key, setting_value FROM {$p}jpg_settings",
            []
        );
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->setting_key] = (string) $row->setting_value;
        }
        return $settings;
    }

    // ── Phase 14.1: Health-Monitor ────────────────────────────────────────────

    /**
     * Sammelt Health-Daten: verwaiste Dateien + abgelaufene Bewerbungen.
     * @return array<string, mixed>
     */
    private static function get_health_data(): array
    {
        $db      = \CMS\Database::instance();
        $p       = $db->getPrefix();
        $uploads = self::get_cv_upload_dir();

        // Verwaiste CV-Dateien
        $orphanFiles = [];
        if ($uploads !== '' && is_dir($uploads)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($uploads, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if ($file->isFile() && in_array(strtolower($file->getExtension()), ['pdf','doc','docx'], true)) {
                    $token = $file->getBasename('.' . $file->getExtension());
                    try {
                        $exists = $db->get_var(
                            "SELECT id FROM {$p}jpg_applications WHERE cv_file_token = ?", [$token]
                        );
                    } catch (\Throwable $e) {
                        $exists = true; // Im Zweifel nicht als verwaist markieren
                    }
                    if (!$exists) {
                        $orphanFiles[] = [
                            'path' => $file->getPathname(),
                            'size' => $file->getSize(),
                        ];
                    }
                }
            }
        }

        // Abgelaufene Bewerbungen (älter als 90 Tage)
        $expiredCount = 0;
        try {
            $expiredCount = (int)$db->get_var(
                "SELECT COUNT(*) FROM {$p}jpg_applications
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
                   AND status IN ('rejected', 'accepted')",
                []
            );
        } catch (\Throwable $e) { /* ignore */ }

        // Gesamtzahl Bewerbungen
        $totalApps = 0;
        try {
            $totalApps = (int)$db->get_var("SELECT COUNT(*) FROM {$p}jpg_applications", []);
        } catch (\Throwable $e) { /* ignore */ }

        // DB-Tabellen-Größen
        $tableSizes = [];
        try {
            $rows = $db->get_results(
                "SELECT table_name AS tbl, ROUND((data_length + index_length) / 1024, 1) AS kb
                 FROM information_schema.TABLES
                 WHERE table_schema = DATABASE() AND table_name LIKE ?
                 ORDER BY kb DESC",
                [$p . 'jpg_%']
            );
            foreach ($rows as $r) {
                $tableSizes[$r->tbl] = (float)$r->kb;
            }
        } catch (\Throwable $e) { /* ignore */ }

        return compact('orphanFiles', 'expiredCount', 'totalApps', 'tableSizes', 'uploads');
    }

    /**
     * Löscht verwaiste CV-Dateien ohne DB-Eintrag.
     * @return array{string, string}
     */
    private static function cleanup_orphan_files(): array
    {
        $data    = self::get_health_data();
        $deleted = 0;
        foreach ($data['orphanFiles'] ?? [] as $f) {
            $realPath = self::resolve_upload_path((string) ($f['path'] ?? ''), self::get_cv_upload_base_dir());
            if ($realPath !== '' && is_file($realPath) && unlink($realPath)) {
                $deleted++;
            }
        }
        return ["✅ {$deleted} verwaiste Datei(en) gelöscht.", ''];
    }

    /**
     * Löscht abgelaufene Bewerber-Daten (DSGVO-Bereinigung).
     * @return array{string, string}
     */
    private static function cleanup_expired_applications(int $days): array
    {
        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();
        $uploadBaseDir = self::get_cv_upload_base_dir();

        try {
            $expired = $db->get_results(
                "SELECT cv_file_path FROM {$p}jpg_applications
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                   AND status IN ('rejected', 'accepted')
                   AND cv_file_path IS NOT NULL",
                [$days]
            ) ?: [];
            foreach ($expired as $row) {
                $storedPath = trim((string) ($row->cv_file_path ?? ''));
                if ($storedPath === '') {
                    continue;
                }

                $candidatePath = $storedPath;
                $isAbsolute = preg_match('#^[A-Za-z]:[\\\\/]#', $candidatePath) === 1
                    || str_starts_with($candidatePath, '/');
                if (!$isAbsolute) {
                    $candidatePath = $uploadBaseDir . ltrim($candidatePath, '/\\');
                }

                $realPath = self::resolve_upload_path($candidatePath, $uploadBaseDir);
                if ($realPath === '' || !is_file($realPath)) {
                    continue;
                }

                unlink($realPath);
            }

            $deletedRows = (int) $db->query(
                "DELETE FROM {$p}jpg_applications
                 WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                   AND status IN ('rejected', 'accepted')",
                [$days]
            );

            if ($deletedRows <= 0) {
                return ['Keine abgelaufenen Bewerbungen gefunden.', ''];
            }

            return ["✅ {$deletedRows} abgelaufene Bewerbung(en) nach {$days} Tagen gelöscht (DSGVO).", ''];
        } catch (\Throwable $e) {
            error_log('CMS_JPG cleanup_expired_applications: ' . $e->getMessage());
            return ['', 'Die Bereinigung konnte nicht abgeschlossen werden. Bitte Logs prüfen.'];
        }
    }

    private static function get_cv_upload_dir(): string
    {
        return self::get_cv_upload_base_dir() . 'applications/';
    }

    private static function get_cv_upload_base_dir(): string
    {
        if (defined('UPLOADS_PATH')) {
            return rtrim((string) UPLOADS_PATH, '/\\') . '/';
        }

        $basePath = (defined('ABSPATH') ? ABSPATH : dirname(__DIR__, 4)) . '/uploads/';
        return rtrim($basePath, '/\\') . '/';
    }

    private static function resolve_upload_path(string $path, string $baseDir): string
    {
        $baseReal = realpath($baseDir);
        if ($baseReal === false) {
            return '';
        }

        $candidate = trim($path);
        if ($candidate === '') {
            return '';
        }

        $isAbsolute = preg_match('#^[A-Za-z]:[\\\\/]#', $candidate) === 1
            || str_starts_with($candidate, '/');
        if (!$isAbsolute) {
            $candidate = $baseDir . ltrim($candidate, '/\\');
        }

        $realPath = realpath($candidate);
        if ($realPath === false) {
            return '';
        }

        $normalizedReal = str_replace('\\', '/', $realPath);
        $normalizedBase = rtrim(str_replace('\\', '/', $baseReal), '/') . '/';
        if (!str_starts_with($normalizedReal, $normalizedBase)) {
            return '';
        }

        return $realPath;
    }
}
