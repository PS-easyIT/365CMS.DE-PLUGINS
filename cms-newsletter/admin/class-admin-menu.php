<?php
/**
 * @package CMS_Newsletter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Newsletter_Admin_Menu
{
    private const MAIN_SLUG = 'newsletter-dashboard';

    /** @var array<string, array{parent: string, title: string, callback: string}> */
    private const MENU_DEFINITIONS = [
        'newsletter-dashboard' => ['parent' => 'newsletter-dashboard', 'title' => 'Dashboard', 'callback' => 'render_dashboard'],
        'newsletter-subscribers' => ['parent' => 'newsletter-dashboard', 'title' => 'Abonnenten', 'callback' => 'render_subscribers'],
        'newsletter-templates' => ['parent' => 'newsletter-dashboard', 'title' => 'Templates', 'callback' => 'render_templates'],
        'newsletter-campaigns' => ['parent' => 'newsletter-dashboard', 'title' => 'Kampagnen', 'callback' => 'render_campaigns'],
        'newsletter-settings-general' => ['parent' => 'newsletter-dashboard', 'title' => 'Einstellungen: Allgemein', 'callback' => 'render_settings_general'],
        'newsletter-settings-content' => ['parent' => 'newsletter-dashboard', 'title' => 'Einstellungen: Inhalte', 'callback' => 'render_settings_content'],
        'newsletter-settings-compliance' => ['parent' => 'newsletter-dashboard', 'title' => 'Einstellungen: Compliance', 'callback' => 'render_settings_compliance'],
    ];

    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page('Newsletter', '365CMS | Newsletter', 'manage_options', self::MAIN_SLUG, [self::class, 'render_current_page'], 'NL');

        foreach (self::MENU_DEFINITIONS as $slug => $definition) {
            add_submenu_page(
                $definition['parent'],
                $definition['title'],
                $definition['title'],
                'manage_options',
                $slug,
                [self::class, 'render_current_page']
            );
        }
    }

    public static function render_current_page(): void
    {
        self::load_shared_contract();

        $callbackMap = [];
        foreach (self::MENU_DEFINITIONS as $slug => $definition) {
            $callback = [CMS_Newsletter_Admin_Pages::class, $definition['callback']];
            $callbackMap[$slug] = is_callable($callback) ? $callback : null;
        }

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, self::MAIN_SLUG, self::MAIN_SLUG);
            return;
        }

        $requestedSlug = (string) ($_GET['page'] ?? self::MAIN_SLUG);
        $resolvedSlug = array_key_exists($requestedSlug, $callbackMap) ? $requestedSlug : self::MAIN_SLUG;
        $callback = $callbackMap[$resolvedSlug] ?? null;
        if (!is_callable($callback)) {
            echo '<div class="alert alert-error" role="alert">Die angeforderte Admin-Seite ist derzeit nicht verfuegbar.</div>';
            return;
        }

        call_user_func($callback);
    }

    private static function load_shared_contract(): void
    {
        $contractFile = dirname(__DIR__) . '/../shared/admin/plugin-admin-contract.php';
        $resolved = realpath($contractFile);
        if ($resolved !== false && is_file($resolved) && is_readable($resolved)) {
            require_once $resolved;
        }
    }
}
