<?php

declare(strict_types=1);

namespace CmsKnowledgebase\Admin;

if (!defined('ABSPATH')) {
    exit;
}

final class Menu
{
    public static function register(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Knowledgebase',
            'Knowledgebase',
            'manage_options',
            'knowledgebase-dashboard',
            [Pages::class, 'renderDashboard'],
            '📚',
            58
        );

        add_submenu_page('knowledgebase-dashboard', 'Dashboard', '📊 Dashboard', 'manage_options', 'knowledgebase-dashboard', [Pages::class, 'renderDashboard']);
        add_submenu_page('knowledgebase-dashboard', 'Einträge', '🧠 Einträge', 'manage_options', 'knowledgebase-entries', [Pages::class, 'renderEntries']);
        add_submenu_page('knowledgebase-dashboard', 'Kategorien', '🗂️ Kategorien', 'manage_options', 'knowledgebase-categories', [Pages::class, 'renderCategories']);
        add_submenu_page('knowledgebase-dashboard', 'Eintrag bearbeiten', '➕ Neuer Eintrag', 'manage_options', 'knowledgebase-entry-editor', [Pages::class, 'renderEntryEditor']);
        add_submenu_page('knowledgebase-dashboard', 'Einstellungen', '⚙️ Einstellungen', 'manage_options', 'knowledgebase-settings', [Pages::class, 'renderSettings']);
    }
}
