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
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        $pages = CMS_M365CALCULATOR_Admin_Pages::class;

        add_menu_page(
            'M365 Tools',
            'M365 Tools',
            'manage_options',
            'm365tools-dashboard',
            [$pages, 'render_dashboard'],
            '🧮',
            57
        );

        add_submenu_page(
            'm365tools-dashboard',
            'M365 Tools – Übersicht',
            '📊 Übersicht',
            'manage_options',
            'm365tools-dashboard',
            [$pages, 'render_dashboard']
        );

        add_submenu_page(
            'm365tools-dashboard',
            'M365 Tools – Zentrale Einstellungen',
            '⚙️ Zentrale Einstellungen',
            'manage_options',
            'm365tools-settings',
            [$pages, 'render_plugin_settings']
        );

        add_submenu_page(
            'm365tools-dashboard',
            'M365 Tools – Paketpreise',
            '💶 Paketpreise',
            'manage_options',
            'm365tools-package-prices',
            [$pages, 'render_package_prices']
        );

        add_submenu_page(
            'm365tools-dashboard',
            'M365 Tools – Abopreise & Laufzeiten',
            '🔁 Abopreise & Laufzeiten',
            'manage_options',
            'm365tools-subscription-prices',
            [$pages, 'render_subscription_prices']
        );

        foreach (self::ordered_admin_tools() as $tool) {
            $moduleKey = (string) ($tool['key'] ?? '');
            if ($moduleKey === '') {
                continue;
            }

            $title = (string) ($tool['title'] ?? $moduleKey);
            $categoryLabel = self::category_label((string) ($tool['category'] ?? 'Weitere Tools'));
            add_submenu_page(
                'm365tools-dashboard',
                $title . ' – Einstellungen',
                '🧩 ' . $categoryLabel . ' · ' . $title,
                'manage_options',
                'm365tools-module-' . $moduleKey,
                static function () use ($moduleKey): void {
                    CMS_M365CALCULATOR_Admin_Pages::render_module_settings($moduleKey);
                }
            );
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

    private static function category_label(string $category): string
    {
        return match (strtolower($category)) {
            'lizenzen' => 'Lizenz',
            'copilot' => 'Copilot',
            'exchange' => 'Exchange',
            'teams' => 'Teams',
            'speicher' => 'Speicher',
            'power platform' => 'Power Platform',
            'migration' => 'Migration',
            default => 'Tool',
        };
    }
}
