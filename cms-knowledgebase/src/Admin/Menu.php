<?php

declare(strict_types=1);

namespace CmsKnowledgebase\Admin;

use CmsKnowledgebase\Support\LoggerFactory;

if (!defined('ABSPATH')) {
    exit;
}

final class Menu
{
    public const ROOT_SLUG = 'knowledgebase-dashboard';

    private const DEFAULT_PAGE_SLUG = 'knowledgebase-dashboard';

    /**
     * @return array<string, array{string, string}>
     */
    private static function callbackDefinitions(): array
    {
        return [
            'knowledgebase-dashboard' => [Pages::class, 'renderDashboard'],
            'knowledgebase-entries' => [Pages::class, 'renderEntries'],
            'knowledgebase-categories' => [Pages::class, 'renderCategories'],
            'knowledgebase-entry-editor' => [Pages::class, 'renderEntryEditor'],
            'knowledgebase-settings' => [Pages::class, 'renderSettings'],
            'knowledgebase-settings-general' => [Pages::class, 'renderSettingsGeneral'],
            'knowledgebase-settings-design' => [Pages::class, 'renderSettingsDesign'],
            'knowledgebase-settings-import' => [Pages::class, 'renderSettingsImport'],
            'knowledgebase-settings-system' => [Pages::class, 'renderSettingsSystem'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function submenuLabels(): array
    {
        return [
            'knowledgebase-dashboard' => 'Dashboard',
            'knowledgebase-entries' => 'Einträge',
            'knowledgebase-categories' => 'Kategorien',
            'knowledgebase-entry-editor' => 'Neuer Eintrag',
            'knowledgebase-settings-general' => 'Einstellungen: Allgemein',
            'knowledgebase-settings-design' => 'Einstellungen: Design',
            'knowledgebase-settings-import' => 'Einstellungen: Import',
            'knowledgebase-settings-system' => 'Einstellungen: System',
            'knowledgebase-settings' => 'Einstellungen',
        ];
    }

    /**
     * @return array<string, callable|null>
     */
    public static function callbackMap(): array
    {
        $resolved = [];
        foreach (self::callbackDefinitions() as $slug => $callableDefinition) {
            $resolved[$slug] = is_callable($callableDefinition) ? $callableDefinition : null;
        }

        return $resolved;
    }

    /**
     * @return array<int, string>
     */
    public static function registeredPageSlugs(): array
    {
        return array_keys(self::callbackDefinitions());
    }

    public static function normalizePageSlug(string $slug): string
    {
        if (function_exists('cms_plugin_admin_normalize_slug')) {
            return cms_plugin_admin_normalize_slug($slug);
        }

        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9_-]+/', '-', $slug);

        return trim($slug, '-');
    }

    public static function register(): void
    {
        self::ensureAdminMenuFunctions();

        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Knowledgebase',
            '365CMS | KB',
            'manage_options',
            self::ROOT_SLUG,
            [self::class, 'dispatchDashboard'],
            '📚'
        );

        add_submenu_page(self::ROOT_SLUG, 'Dashboard', '📊 Dashboard', 'manage_options', 'knowledgebase-dashboard', [self::class, 'dispatchDashboard']);
        add_submenu_page(self::ROOT_SLUG, 'Einträge', '🧠 Einträge', 'manage_options', 'knowledgebase-entries', [self::class, 'dispatchEntries']);
        add_submenu_page(self::ROOT_SLUG, 'Kategorien', '🗂️ Kategorien', 'manage_options', 'knowledgebase-categories', [self::class, 'dispatchCategories']);
        add_submenu_page(self::ROOT_SLUG, 'Eintrag bearbeiten', '➕ Neuer Eintrag', 'manage_options', 'knowledgebase-entry-editor', [self::class, 'dispatchEntryEditor']);
        add_submenu_page(self::ROOT_SLUG, 'Einstellungen - Allgemein', '⚙️ Einstellungen: Allgemein', 'manage_options', 'knowledgebase-settings-general', [self::class, 'dispatchSettingsGeneral']);
        add_submenu_page(self::ROOT_SLUG, 'Einstellungen - Design', '🎨 Einstellungen: Design', 'manage_options', 'knowledgebase-settings-design', [self::class, 'dispatchSettingsDesign']);
        add_submenu_page(self::ROOT_SLUG, 'Einstellungen - Import', '📦 Einstellungen: Import', 'manage_options', 'knowledgebase-settings-import', [self::class, 'dispatchSettingsImport']);
        add_submenu_page(self::ROOT_SLUG, 'Einstellungen - System', '🖥️ Einstellungen: System', 'manage_options', 'knowledgebase-settings-system', [self::class, 'dispatchSettingsSystem']);
        add_submenu_page(self::ROOT_SLUG, 'Einstellungen', '⚙️ Einstellungen', 'manage_options', 'knowledgebase-settings', [self::class, 'dispatchSettings']);
    }

    /**
     * @param mixed $router
     */
    public static function registerAdminRoutes($router): void
    {
        if (!function_exists('cms_plugin_admin_register_routes')) {
            return;
        }

        cms_plugin_admin_register_routes($router, self::ROOT_SLUG, self::callbackMap());
    }

    /**
     * @param array<int, array<string, mixed>> $menuItems
     * @return array<int, array<string, mixed>>
     */
    public static function addMenuItems(array $menuItems): array
    {
        $adminBase = '/admin/plugins/' . self::ROOT_SLUG;
        $currentPath = function_exists('cms_plugin_admin_request_path')
            ? cms_plugin_admin_request_path()
            : '/' . trim((string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?? ''), '/');
        $isPluginPath = str_starts_with($currentPath, $adminBase);
        $activeSlug = function_exists('cms_plugin_admin_active_slug')
            ? cms_plugin_admin_active_slug(self::DEFAULT_PAGE_SLUG)
            : self::normalizePageSlug((string) ($_GET['page'] ?? self::DEFAULT_PAGE_SLUG));

        $menuItems[] = [
            'type' => 'item',
            'slug' => self::ROOT_SLUG,
            'label' => '365CMS | KB',
            'icon' => '📚',
            'url' => function_exists('cms_plugin_admin_page_path')
                ? cms_plugin_admin_page_path(self::ROOT_SLUG, self::DEFAULT_PAGE_SLUG)
                : $adminBase . '/' . rawurlencode(self::DEFAULT_PAGE_SLUG),
            'active' => $isPluginPath && $activeSlug === self::DEFAULT_PAGE_SLUG,
        ];

        foreach (self::submenuLabels() as $slug => $label) {
            if ($slug === self::DEFAULT_PAGE_SLUG) {
                continue;
            }

            $menuItems[] = [
                'type' => 'item',
                'slug' => $slug,
                'parent' => self::ROOT_SLUG,
                'label' => '↳ ' . $label,
                'icon' => '',
                'url' => function_exists('cms_plugin_admin_page_path')
                    ? cms_plugin_admin_page_path(self::ROOT_SLUG, $slug)
                    : $adminBase . '/' . rawurlencode($slug),
                'active' => $isPluginPath && $activeSlug === $slug,
            ];
        }

        return $menuItems;
    }

    public static function dispatch(): void
    {
        $callbackMap = self::callbackMap();

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, self::DEFAULT_PAGE_SLUG, self::ROOT_SLUG);
            return;
        }

        if (function_exists('cms_plugin_admin_sync_page_from_request')) {
            cms_plugin_admin_sync_page_from_request(self::ROOT_SLUG);
        }

        $requestedPage = self::normalizePageSlug((string) ($_GET['page'] ?? self::DEFAULT_PAGE_SLUG));
        $resolvedPage = array_key_exists($requestedPage, $callbackMap) ? $requestedPage : self::DEFAULT_PAGE_SLUG;
        $callback = $callbackMap[$resolvedPage] ?? null;

        if (!is_callable($callback)) {
            LoggerFactory::create()->error('Knowledgebase-Admin-Callback konnte nicht aufgelöst werden.', [
                'requested_page' => $requestedPage,
                'resolved_page' => $resolvedPage,
            ]);
            echo '<div class="alert alert-error" role="alert">Die angeforderte Admin-Seite ist derzeit nicht verfuegbar.</div>';
            return;
        }

        call_user_func($callback);
    }

    public static function dispatchDashboard(): void
    {
        $_GET['page'] = 'knowledgebase-dashboard';
        self::dispatch();
    }

    public static function dispatchEntries(): void
    {
        $_GET['page'] = 'knowledgebase-entries';
        self::dispatch();
    }

    public static function dispatchCategories(): void
    {
        $_GET['page'] = 'knowledgebase-categories';
        self::dispatch();
    }

    public static function dispatchEntryEditor(): void
    {
        $_GET['page'] = 'knowledgebase-entry-editor';
        self::dispatch();
    }

    public static function dispatchSettings(): void
    {
        $_GET['page'] = 'knowledgebase-settings';
        self::dispatch();
    }

    public static function dispatchSettingsGeneral(): void
    {
        $_GET['page'] = 'knowledgebase-settings-general';
        self::dispatch();
    }

    public static function dispatchSettingsDesign(): void
    {
        $_GET['page'] = 'knowledgebase-settings-design';
        self::dispatch();
    }

    public static function dispatchSettingsImport(): void
    {
        $_GET['page'] = 'knowledgebase-settings-import';
        self::dispatch();
    }

    public static function dispatchSettingsSystem(): void
    {
        $_GET['page'] = 'knowledgebase-settings-system';
        self::dispatch();
    }

    private static function ensureAdminMenuFunctions(): void
    {
        if (function_exists('add_menu_page') && function_exists('renderAdminLayoutStart')) {
            return;
        }

        $candidates = [
            ABSPATH . 'admin/partials/admin-menu.php',
            ABSPATH . 'includes/functions/admin-menu.php',
            ABSPATH . 'CMS/includes/functions/admin-menu.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) {
                require_once $file;
            }

            if (function_exists('add_menu_page') && function_exists('renderAdminLayoutStart')) {
                return;
            }
        }
    }
}
