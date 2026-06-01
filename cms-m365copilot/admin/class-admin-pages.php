<?php
/**
 * CMS M365 Copilot – Admin settings page.
 *
 * @package CMS_M365Copilot
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Copilot_Admin_Pages
{
    private const PAGE_SLUG = 'm365copilot-settings';
    private const CSRF_ACTION = 'm365copilot_settings';

    public static function render_settings(): void
    {
        self::check_access();
        $notice = '';
        $error = '';
        $tabs = [
            'content-header' => '🖼️ Content Header',
            'service-band' => '🤝 Dienstleistungsband',
            'cards' => '🃏 Bereichscards',
            'posts' => '📰 Beiträge',
            'layout' => '📐 Layout & Spacing',
            'texts' => '✍️ Texte & Labels',
        ];
        $tab = self::clean_tab((string) ($_GET['tab'] ?? 'content-header'), array_keys($tabs));

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!self::verify_request()) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } elseif ((string) ($_POST['action'] ?? '') === 'save_settings') {
                try {
                    CMS_M365Copilot_Installer::ensure_for_admin_save();
                    CMS_M365Copilot_Settings::save(CMS_M365Copilot_Settings::sanitize_from_post($_POST));
                    $notice = 'Einstellungen gespeichert.';
                } catch (\Throwable $e) {
                    $error = 'Einstellungen konnten nicht gespeichert werden: ' . $e->getMessage();
                }
            }
        }

        $settings = CMS_M365Copilot_Settings::all();
        $categories = CMS_M365Copilot_Settings::post_categories();
        $csrfToken = class_exists('CMS\\Security') ? (string) \CMS\Security::instance()->generateToken(self::CSRF_ACTION) : '';
        $publicUrl = '/' . trim((string) ($settings['route_slug'] ?? 'microsoft-365-copilot'), '/');

        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start('M365 Copilot Landing', self::PAGE_SLUG);
        } elseif (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart('M365 Copilot Landing', self::PAGE_SLUG);
        }

        include CMS_M365COPILOT_PLUGIN_DIR . 'admin/views/page-settings.php';

        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
        } elseif (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function check_access(): void
    {
        $hasCapability = !function_exists('current_user_can') || current_user_can('manage_options');
        $isAdmin = class_exists('CMS\\Auth') && \CMS\Auth::instance()->isAdmin();
        if (!$hasCapability || !$isAdmin) {
            $url = defined('SITE_URL') ? (string) SITE_URL : '/';
            header('Location: ' . self::safe_redirect_url($url), true, 302);
            exit;
        }
    }

    private static function verify_request(): bool
    {
        if (!class_exists('CMS\\Security')) {
            return false;
        }

        return \CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), self::CSRF_ACTION);
    }

    public static function enqueue_admin_assets(): void
    {
        if (!self::is_admin_request()) {
            return;
        }

        $css = CMS_M365COPILOT_PLUGIN_DIR . 'assets/css/cms-m365copilot-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365COPILOT_PLUGIN_URL . 'assets/css/cms-m365copilot-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    private static function is_admin_request(): bool
    {
        $page = trim((string) ($_GET['page'] ?? ''));
        if ($page === self::PAGE_SLUG) {
            return true;
        }

        $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
        if (str_contains($uri, self::PAGE_SLUG)) {
            return true;
        }

        return str_contains($uri, '/admin/')
            && (str_contains($uri, 'cms-m365copilot') || str_contains($uri, 'm365copilot'));
    }

    private static function safe_redirect_url(string $url): string
    {
        $url = trim($url);
        if ($url === '' || str_contains($url, "\0") || preg_match('/[\r\n]/', $url) === 1) {
            return '/';
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }
        return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '/';
    }

    /** @param array<int,string> $allowed */
    private static function clean_tab(string $tab, array $allowed): string
    {
        $tab = strtolower(trim((string) preg_replace('/[^a-z0-9-]+/i', '-', $tab), '-'));
        if ($tab === '' || !in_array($tab, $allowed, true)) {
            return $allowed[0] ?? 'content-header';
        }

        return $tab;
    }
}
