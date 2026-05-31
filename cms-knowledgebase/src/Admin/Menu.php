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
            [self::class, 'dispatch'],
            '📚'
        );

        add_submenu_page(self::ROOT_SLUG, 'Dashboard', '📊 Dashboard', 'manage_options', 'knowledgebase-dashboard', [self::class, 'dispatch']);
        add_submenu_page(self::ROOT_SLUG, 'Einträge', '🧠 Einträge', 'manage_options', 'knowledgebase-entries', [self::class, 'dispatch']);
        add_submenu_page(self::ROOT_SLUG, 'Kategorien', '🗂️ Kategorien', 'manage_options', 'knowledgebase-categories', [self::class, 'dispatch']);
        add_submenu_page(self::ROOT_SLUG, 'Eintrag bearbeiten', '➕ Neuer Eintrag', 'manage_options', 'knowledgebase-entry-editor', [self::class, 'dispatch']);
        add_submenu_page(self::ROOT_SLUG, 'Einstellungen - Allgemein', '⚙️ Einstellungen: Allgemein', 'manage_options', 'knowledgebase-settings-general', [self::class, 'dispatch']);
        add_submenu_page(self::ROOT_SLUG, 'Einstellungen - Design', '🎨 Einstellungen: Design', 'manage_options', 'knowledgebase-settings-design', [self::class, 'dispatch']);
        add_submenu_page(self::ROOT_SLUG, 'Einstellungen - Import', '📦 Einstellungen: Import', 'manage_options', 'knowledgebase-settings-import', [self::class, 'dispatch']);
        add_submenu_page(self::ROOT_SLUG, 'Einstellungen - System', '🖥️ Einstellungen: System', 'manage_options', 'knowledgebase-settings-system', [self::class, 'dispatch']);
        add_submenu_page(self::ROOT_SLUG, 'Einstellungen', '⚙️ Einstellungen', 'manage_options', 'knowledgebase-settings', [self::class, 'dispatch']);
    }

    public static function dispatch(): void
    {
        $callbackMap = self::callbackMap();

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, self::DEFAULT_PAGE_SLUG, self::ROOT_SLUG);
            return;
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
