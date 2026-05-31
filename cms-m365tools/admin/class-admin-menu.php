<?php
/**
 * CMS M365 Tools – Admin Menü.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Admin_Menu
{
    private const ROOT_PAGE_SLUG = 'm365tools-dashboard';

    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'M365 Tools',
            'M365 Tools',
            'manage_options',
            self::ROOT_PAGE_SLUG,
            [self::class, 'dispatch_current_page'],
            '🧮',
            57
        );

        add_submenu_page(
            self::ROOT_PAGE_SLUG,
            'M365 Tools – Übersicht',
            '📊 Übersicht',
            'manage_options',
            self::ROOT_PAGE_SLUG,
            [self::class, 'dispatch_current_page']
        );

        add_submenu_page(
            self::ROOT_PAGE_SLUG,
            'M365 Tools – Zentrale Einstellungen',
            '⚙️ Zentrale Einstellungen',
            'manage_options',
            'm365tools-settings',
            [self::class, 'dispatch_current_page']
        );

        add_submenu_page(
            self::ROOT_PAGE_SLUG,
            'M365 Tools – Landingpage Designer',
            '🎨 Landingpage Designer',
            'manage_options',
            'm365tools-landing-designer',
            [self::class, 'dispatch_current_page']
        );

        add_submenu_page(
            self::ROOT_PAGE_SLUG,
            'M365 Tools – Paketpreise',
            '💶 Paketpreise',
            'manage_options',
            'm365tools-package-prices',
            [self::class, 'dispatch_current_page']
        );

        add_submenu_page(
            self::ROOT_PAGE_SLUG,
            'M365 Tools – Abopreise & Laufzeiten',
            '🔁 Abopreise & Laufzeiten',
            'manage_options',
            'm365tools-subscription-prices',
            [self::class, 'dispatch_current_page']
        );

        foreach (self::ordered_admin_tools() as $tool) {
            $moduleKey = (string) ($tool['key'] ?? '');
            if ($moduleKey === '') {
                continue;
            }
            $title = (string) ($tool['title'] ?? $moduleKey);
            add_submenu_page(
                self::ROOT_PAGE_SLUG,
                $title . ' – Einstellungen',
                '🧩 ' . self::module_menu_label($moduleKey, $tool),
                'manage_options',
                'm365tools-module-' . $moduleKey,
                [self::class, 'dispatch_current_page']
            );
        }
    }

    public static function dispatch_current_page(): void
    {
        $callbacks = self::callback_map();
        $defaultSlug = self::ROOT_PAGE_SLUG;

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbacks, $defaultSlug, self::ROOT_PAGE_SLUG);
            return;
        }

        $requested = strtolower(trim((string) ($_GET['page'] ?? $defaultSlug)));
        $requested = (string) preg_replace('/[^a-z0-9_-]+/', '-', $requested);
        $requested = trim($requested, '-');
        $resolved = array_key_exists($requested, $callbacks) ? $requested : $defaultSlug;
        if ($requested !== '' && $requested !== $resolved && !isset($_GET['m365tools_dispatch_fallback'])) {
            $_GET['m365tools_dispatch_fallback'] = '1';
        }
        $callback = $callbacks[$resolved] ?? null;

        if (is_callable($callback)) {
            call_user_func($callback);
            return;
        }

        $fallbackCallback = $callbacks[$defaultSlug] ?? null;
        if (is_callable($fallbackCallback)) {
            call_user_func($fallbackCallback);
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function ordered_admin_tools(): array
    {
        $tools = CMS_M365CALCULATOR_Tool_Registry::ordered_tools(false);
        $categoryOrder = [
            'lizenzen' => 10,
            'copilot' => 20,
            'exchange' => 30,
            'teams' => 40,
            'speicher' => 50,
            'power platform' => 60,
            'migration' => 70,
        ];

        usort($tools, static function (array $left, array $right) use ($categoryOrder): int {
            $leftCategory = strtolower((string) ($left['category'] ?? ''));
            $rightCategory = strtolower((string) ($right['category'] ?? ''));
            $leftOrder = $categoryOrder[$leftCategory] ?? 999;
            $rightOrder = $categoryOrder[$rightCategory] ?? 999;

            if ($leftOrder !== $rightOrder) {
                return $leftOrder <=> $rightOrder;
            }

            $priority = ((int) ($left['priority'] ?? 100)) <=> ((int) ($right['priority'] ?? 100));
            if ($priority !== 0) {
                return $priority;
            }

            return strcmp((string) ($left['title'] ?? ''), (string) ($right['title'] ?? ''));
        });

        return $tools;
    }

    /**
     * @param array<string,mixed> $tool
     */
    private static function module_menu_label(string $moduleKey, array $tool): string
    {
        $label = match ($moduleKey) {
            'license-audit-checklist' => 'Audit',
            'm365-lizenzvergleich' => 'Vergleich',
            'm365-add-on-konfigurator' => 'Add-ons',
            'm365lic' => 'Lizenzberater',
            'm365-commitment-calculator' => 'Laufzeiten',
            'frontline-worker-license-check' => 'Frontline',
            'microsoft-price-tracker' => 'Preis-Tracker',
            'shared-mailbox' => 'Shared Mailbox',
            'copilot-license-check' => 'Copilot Check',
            'm365-archive-mailbox' => 'Archiv',
            'exchange-online-roi' => 'Exchange ROI',
            'teams-phone-advisor' => 'Teams Phone',
            'm365-storage-needs-calculator' => 'Storage',
            'm365-backup-cost-calculator' => 'Backup',
            'power-platform-cost-calculator' => 'Power Platform',
            'workspace-m365-tco-calculator' => 'Workspace TCO',
            'ai-pack-vs-copilot-pro' => 'AI Vergleich',
            'copilot-pilot-calculator' => 'Copilot Pilot',
            'copilot-roi' => 'Copilot ROI',
            default => (string) ($tool['title'] ?? $moduleKey),
        };

        return self::limit_label($label, 24);
    }

    private static function limit_label(string $label, int $length): string
    {
        $label = trim(strip_tags($label));
        if (function_exists('mb_strlen') && function_exists('mb_substr')) {
            return mb_strlen($label) > $length ? rtrim(mb_substr($label, 0, $length - 1)) . '…' : $label;
        }

        return strlen($label) > $length ? rtrim(substr($label, 0, $length - 1)) . '…' : $label;
    }

    /**
     * @return array<string,callable|null>
     */
    private static function callback_map(): array
    {
        $pages = CMS_M365CALCULATOR_Admin_Pages::class;
        $map = [
            self::ROOT_PAGE_SLUG => [$pages, 'render_dashboard'],
            'm365tools-settings' => [$pages, 'render_plugin_settings'],
            'm365tools-landing-designer' => [$pages, 'render_landing_designer'],
            'm365tools-package-prices' => [$pages, 'render_package_prices'],
            'm365tools-subscription-prices' => [$pages, 'render_subscription_prices'],
        ];

        foreach (self::ordered_admin_tools() as $tool) {
            $moduleKey = (string) ($tool['key'] ?? '');
            if ($moduleKey === '') {
                continue;
            }

            $map['m365tools-module-' . $moduleKey] = static function () use ($moduleKey): void {
                CMS_M365CALCULATOR_Admin_Pages::render_module_settings($moduleKey);
            };
        }

        return $map;
    }

}
