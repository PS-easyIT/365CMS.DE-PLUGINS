<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Vorlagen & Corporate Design
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Design_Trait
{
    // ── 4. VORLAGEN & DESIGN ─────────────────────────────────────────────────

    public static function render_design(): void
    {
        self::check_access();

        $tab    = sanitize_key($_GET['tab'] ?? 'pdf-templates');
        $notice = '';
        $error  = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_design_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_design_post($tab);
            }
        }

        $tabs = [
            'pdf-templates'   => 'PDF-Templates',
            'web-templates'   => 'Web-Templates',
            'corporate'       => 'Corporate Design',
            'typography'      => 'Typografie',
            'email-templates' => 'E-Mail-Templates',
        ];

        $db = \CMS\Database::instance();
        $p  = $db->getPrefix();

        $type = match ($tab) {
            'pdf-templates'   => 'pdf',
            'web-templates'   => 'web',
            'email-templates' => 'email',
            default           => null,
        };

        $templates = $type
            ? $db->get_results(
                "SELECT * FROM {$p}jpg_templates WHERE type = ? ORDER BY is_default DESC, name ASC",
                [$type]
            )
            : [];

        $cd_settings = self::get_cd_settings();

        include JPG_DIR . 'admin/views/page-design.php';
    }

    /** @return array{string, string} */
    private static function handle_design_post(string $tab): array
    {
        $notice = '';
        $error  = '';
        $db     = \CMS\Database::instance();
        $p      = $db->getPrefix();
        $id     = (int) ($_POST['template_id'] ?? 0);
        $del    = (int) ($_POST['delete_id'] ?? 0);

        if (in_array($tab, ['pdf-templates', 'web-templates', 'email-templates'])) {
            if ($del > 0) {
                $db->delete('jpg_templates', ['id' => $del]);
                $notice = 'Template gelöscht.';
                return [$notice, $error];
            }
            $type = match ($tab) {
                'pdf-templates'   => 'pdf',
                'web-templates'   => 'web',
                'email-templates' => 'email',
                default           => 'web',
            };
            if (empty(trim($_POST['name'] ?? ''))) {
                $error = 'Template-Name ist erforderlich.';
                return [$notice, $error];
            }
            $fields = [
                'type'       => $type,
                'name'       => sanitize_text_field($_POST['name'] ?? ''),
                'content'    => $_POST['content'] ?? '',
                'css'        => $_POST['css'] ?? '',
                'is_default' => isset($_POST['is_default']) ? 1 : 0,
                'active'     => 1,
            ];
            if ($id > 0) {
                $db->update('jpg_templates', $fields, ['id' => $id]);
            } else {
                $db->insert('jpg_templates', $fields);
            }
            $notice = 'Template gespeichert.';
        } elseif ($tab === 'corporate') {
            self::save_cd_settings([
                'primary_color'   => sanitize_text_field($_POST['primary_color']   ?? '#3b82f6'),
                'secondary_color' => sanitize_text_field($_POST['secondary_color'] ?? '#1e293b'),
                'font_body'       => sanitize_text_field($_POST['font_body']       ?? 'Inter, sans-serif'),
                'font_heading'    => sanitize_text_field($_POST['font_heading']    ?? 'Inter, sans-serif'),
                'logo_url'        => sanitize_text_field($_POST['logo_url']        ?? ''),
                'company_name'    => sanitize_text_field($_POST['company_name']    ?? ''),
                'company_tagline' => sanitize_text_field($_POST['company_tagline'] ?? ''),
                'footer_text'     => sanitize_text_field($_POST['footer_text']     ?? ''),
            ]);
            $notice = 'Corporate Design gespeichert.';
        }

        return [$notice, $error];
    }

    /** @return array<string, string> */
    private static function get_cd_settings(): array
    {
        $db   = \CMS\Database::instance();
        $p    = $db->getPrefix();
        $rows = $db->get_results(
            "SELECT setting_key, setting_value FROM {$p}jpg_settings WHERE setting_key LIKE 'cd_%'",
            []
        );
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row->setting_key] = $row->setting_value;
        }
        return $settings;
    }

    /** @param array<string, string> $data */
    private static function save_cd_settings(array $data): void
    {
        $db  = \CMS\Database::instance();
        $p   = $db->getPrefix();
        $pdo = $db->getPdo();

        foreach ($data as $key => $value) {
            $key = 'cd_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($key));
            $pdo->exec(
                "INSERT INTO {$p}jpg_settings (setting_key, setting_value)
                 VALUES ('{$key}', " . $pdo->quote((string) $value) . ")
                 ON DUPLICATE KEY UPDATE setting_value = " . $pdo->quote((string) $value)
            );
        }
    }
}
