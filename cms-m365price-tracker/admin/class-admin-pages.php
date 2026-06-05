<?php
/**
 * CMS M365 Price Tracker – Admin pages.
 *
 * @package CMS_M365PRICETRACKER
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365PRICETRACKER_Admin_Pages
{
    private const CSRF_ACTION = 'm365price_tracker_settings';

    public static function render_dashboard(): void
    {
        self::check_access();

        $notice = '';
        $error = '';
        $tabs = [
            'status' => '📊 Status',
            'layout' => '📐 Layout & Abstände',
            'colors' => '🎨 Farben',
            'sections' => '🧩 Bereiche',
            'cards' => '🔗 Karten',
        ];
        $tab = self::clean_tab((string) ($_GET['tab'] ?? 'status'), array_keys($tabs));

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
            if (!self::verify_request()) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } elseif ((string) ($_POST['action'] ?? '') === 'save_settings') {
                try {
                    CMS_M365PRICETRACKER_Settings::save(CMS_M365PRICETRACKER_Settings::sanitize_from_post($_POST));
                    $notice = 'Einstellungen gespeichert.';
                } catch (\Throwable $e) {
                    $error = 'Einstellungen konnten nicht gespeichert werden: ' . $e->getMessage();
                }
            }
        }

        $stats = self::status_stats();
        $dataFiles = self::data_file_status();
        $publicUrl = '/microsoft-preiserhoehung-tracker';
        $pluginVersion = CMS_M365PRICETRACKER_VERSION;
        $settings = CMS_M365PRICETRACKER_Settings::all();
        $csrfToken = class_exists('CMS\\Security') ? (string) \CMS\Security::instance()->generateToken(self::CSRF_ACTION) : '';

        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start('M365 Price Tracker', CMS_M365PRICETRACKER_Admin_Menu::PAGE_SLUG);
        } elseif (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart('M365 Price Tracker', CMS_M365PRICETRACKER_Admin_Menu::PAGE_SLUG);
        }

        self::enqueue_admin_assets();

        include CMS_M365PRICETRACKER_PLUGIN_DIR . 'admin/views/page-dashboard.php';

        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
        } elseif (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    public static function enqueue_admin_assets(): void
    {
        $css = CMS_M365PRICETRACKER_PLUGIN_DIR . 'assets/css/price-tracker-admin.css';
        if (is_file($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365PRICETRACKER_PLUGIN_URL . 'assets/css/price-tracker-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    private static function check_access(): void
    {
        $hasCapability = !function_exists('current_user_can') || current_user_can('manage_options');
        $isAdmin = class_exists('CMS\\Auth') && \CMS\Auth::instance()->isAdmin();

        if ($hasCapability && $isAdmin) {
            return;
        }

        $url = defined('SITE_URL') ? (string) SITE_URL : '/';
        header('Location: ' . self::safe_redirect_url($url), true, 302);
        exit;
    }

    private static function verify_request(): bool
    {
        if (!class_exists('CMS\\Security')) {
            return false;
        }

        return \CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), self::CSRF_ACTION);
    }

    /** @param array<int,string> $allowed */
    private static function clean_tab(string $tab, array $allowed): string
    {
        $tab = strtolower(trim((string) preg_replace('/[^a-z0-9-]+/i', '-', $tab), '-'));
        if ($tab === '' || !in_array($tab, $allowed, true)) {
            return $allowed[0] ?? 'status';
        }

        return $tab;
    }

    /**
     * @return array<int,array{icon:string,label:string,value:int|string}>
     */
    private static function status_stats(): array
    {
        $history = CMS_M365PRICETRACKER_Microsoft_Price_Tracker::price_history_dataset();
        $events = CMS_M365PRICETRACKER_Catalog::microsoft_price_events();
        $changes = CMS_M365PRICETRACKER_Catalog::microsoft_price_changes();
        $mapping = CMS_M365PRICETRACKER_Catalog::microsoft_inventory_mapping();

        return [
            ['icon' => '📦', 'label' => 'SKU-Zeitreihen', 'value' => count($history['licenses'] ?? [])],
            ['icon' => '📅', 'label' => 'Preisanker', 'value' => count($history['dates'] ?? [])],
            ['icon' => '🧭', 'label' => 'Ereignisse', 'value' => self::count_collection($events, 'events')],
            ['icon' => '🔁', 'label' => 'Preisänderungen', 'value' => self::count_collection($changes, 'changes')],
            ['icon' => '🗂️', 'label' => 'Mapping-Regeln', 'value' => self::count_collection($mapping, 'mappings')],
            ['icon' => '✅', 'label' => 'Chart.js', 'value' => 'aktiv'],
        ];
    }

    /**
     * @return array<int,array{name:string,exists:bool,size:int,modified:string}>
     */
    private static function data_file_status(): array
    {
        $files = [
            'package_price_catalog.json',
            'microsoft_price_events.json',
            'microsoft_price_changes.json',
            'microsoft_inventory_mapping.json',
            'microsoft_price_forecast_rules.json',
        ];

        $result = [];
        foreach ($files as $file) {
            $path = CMS_M365PRICETRACKER_PLUGIN_DIR . 'data/' . $file;
            $modified = is_file($path) ? (int) filemtime($path) : 0;
            $size = is_file($path) ? (int) filesize($path) : 0;
            $result[] = [
                'name' => $file,
                'exists' => is_file($path) && is_readable($path),
                'size' => $size,
                'modified' => $modified > 0 ? date('d.m.Y H:i', $modified) : '—',
            ];
        }

        return $result;
    }

    /**
     * @param array<string,mixed> $source
     */
    private static function count_collection(array $source, string $preferredKey): int
    {
        if (is_array($source[$preferredKey] ?? null)) {
            return count($source[$preferredKey]);
        }

        return count($source);
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
}
