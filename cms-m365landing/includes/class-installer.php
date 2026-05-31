<?php
/**
 * CMS M365 Landing – Installer.
 *
 * @package CMS_M365Landing
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Landing_Installer
{
    public static function maybe_install(): void
    {
        try {
            $repo = CMS_M365Landing_Repository::instance();
            $settings = $repo->settings();
            if (($settings['db_version'] ?? '') === CMS_M365LANDING_DB_VERSION) {
                return;
            }
        } catch (\Throwable $e) {
            self::log_exception('maybe_install_probe_failed', $e);
            // Tabellen fehlen vermutlich noch.
        }

        self::install();
    }

    public static function install(): void
    {
        $db = \CMS\Database::instance();
        $prefix = self::resolve_prefix($db);
        $pdo = method_exists($db, 'getPdo') ? $db->getPdo() : null;
        if (!$pdo instanceof \PDO) {
            return;
        }

        self::create_tables($pdo, $prefix);
        self::seed_settings($db, $prefix);
        self::upgrade_background_default($db, $prefix);
        self::seed_cards($db, $prefix);
    }

    private static function create_tables(\PDO $pdo, string $prefix): void
    {
        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}m365landing_settings (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(120) NOT NULL UNIQUE,
            setting_value TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}m365landing_cards (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            section VARCHAR(40) NOT NULL DEFAULT 'tools',
            slug VARCHAR(120) NOT NULL,
            title VARCHAR(190) NOT NULL,
            subtitle VARCHAR(255) NULL,
            description TEXT NULL,
            icon VARCHAR(80) NULL,
            image_url VARCHAR(500) NULL,
            image_alt VARCHAR(255) NULL,
            url VARCHAR(500) NULL,
            button_label VARCHAR(120) NULL,
            sort_order INT NOT NULL DEFAULT 100,
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_m365landing_card_slug (slug),
            INDEX idx_m365landing_section (section, is_active, sort_order),
            INDEX idx_m365landing_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private static function seed_settings(object $db, string $prefix): void
    {
        $defaults = [
            'db_version' => CMS_M365LANDING_DB_VERSION,
            'route_slug' => 'm365',
            'landing_domains' => '',
            'page_overline' => 'Microsoft 365 Hub',
            'page_title' => 'M365 im Überblick – Matrixen, Azure, Tutorials und Tools',
            'page_intro' => 'Die zentrale Einstiegsseite für Microsoft-365-Entscheidungen: Lizenzmatrixen, Add-ons, Copilot, Azure Services, Tutorials und praktische Rechner an einem Ort.',
            'hero_image_url' => '',
            'hero_image_alt' => '',
            'hero_image_height' => '150',
            'hero_primary_button_text' => 'M365 Lizenzmatrix öffnen',
            'hero_primary_button_url' => '/m365-lizenzmatrix',
            'hero_secondary_button_text' => 'Add-on-Matrix öffnen',
            'hero_secondary_button_url' => '/m365-addon-matrix',
            'matrix_section_overline' => 'Lizenz & Add-ons',
            'matrix_section_title' => 'Die wichtigsten Matrixen',
            'matrix_section_intro' => 'Schneller Einstieg in M365-Lizenzen, Add-ons und Copilot-Optionen – übersichtlich als eigene Bereichscards.',
            'areas_section_overline' => 'Weitere M365 Bereiche',
            'areas_section_title' => 'Azure, Tutorials und Fachbereiche',
            'areas_section_intro' => 'Ergänzende Einstiegspunkte für Services, Lerninhalte und kuratierte M365-Themen.',
            'tools_section_overline' => 'Rechner & Checklisten',
            'tools_section_title' => 'M365 Tools Sammlung',
            'tools_section_intro' => 'Praktische Rechner und Entscheidungshelfer, einzeln steuerbar und sauber in maximal drei Spalten dargestellt.',
            'posts_section_overline' => '',
            'posts_section_title' => '',
            'posts_section_intro' => '',
            'posts_section_mode' => 'category',
            'posts_section_category_id' => '0',
            'posts_section_limit' => '6',
            'show_posts_section' => '0',
            'posts_section_domain_only' => '1',
            'separator_label' => 'Weitere Microsoft-365-Bereiche',
            'empty_state_title' => 'Noch keine aktiven Karten vorhanden',
            'empty_state_text' => 'Aktiviere oder erstelle Karten im Adminbereich, damit die Landingpage Inhalte anzeigen kann.',
            'seo_title' => 'Microsoft 365 Hub',
            'seo_description' => 'Zentrale Landingpage für Microsoft 365 Lizenzmatrixen, Add-ons, Copilot, Azure Services, Tutorials und M365 Tools.',
            'show_hero' => '1',
            'show_hero_actions' => '1',
            'show_matrix_section' => '1',
            'show_separator' => '1',
            'show_areas_section' => '1',
            'show_tools_section' => '1',
            'design_primary_color' => '#2563eb',
            'design_accent_color' => '#0f766e',
            'design_background_color' => '#edf1f6',
            'design_surface_color' => '#ffffff',
            'design_surface_alt_color' => '#f8fafc',
            'design_text_color' => '#1e293b',
            'design_muted_color' => '#64748b',
            'design_border_color' => '#e2e8f0',
            'design_border_radius' => '10',
            'layout_variant' => 'balanced',
            'layout_max_width' => '1180',
            'layout_padding_x' => '0',
            'layout_padding_top' => '25',
            'layout_padding_bottom' => '64',
            'card_icon_size' => '42',
            'card_image_height' => '205',
            'card_image_width' => '120',
            'card_button_label_default' => 'Öffnen',
            'matrix_card_layout' => 'media',
            'areas_card_layout' => 'media',
            'tools_card_layout' => 'media',
            'matrix_card_hide_title' => '0',
            'areas_card_hide_title' => '0',
            'tools_card_hide_title' => '0',
        ];

        $exists = $db->prepare("SELECT id FROM {$prefix}m365landing_settings WHERE setting_key = ?");
        $insert = $db->prepare("INSERT INTO {$prefix}m365landing_settings (setting_key, setting_value) VALUES (?, ?)");
        $updateDbVersion = $db->prepare("UPDATE {$prefix}m365landing_settings SET setting_value = ? WHERE setting_key = 'db_version'");

        foreach ($defaults as $key => $value) {
            $exists->execute([$key]);
            if ($exists->fetch()) {
                continue;
            }
            $insert->execute([$key, $value]);
        }

        $updateDbVersion->execute([CMS_M365LANDING_DB_VERSION]);
    }

    private static function upgrade_background_default(object $db, string $prefix): void
    {
        $markerKey = 'content_design_background_default_version';
        $markerVersion = '2026-05-30-background-edf1f6-v1';

        $markerStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365landing_settings WHERE setting_key = ?");
        $markerStmt->execute([$markerKey]);
        if ((string) ($markerStmt->fetchColumn() ?: '') === $markerVersion) {
            return;
        }

        $currentStmt = $db->prepare("SELECT setting_value FROM {$prefix}m365landing_settings WHERE setting_key = 'design_background_color'");
        $currentStmt->execute();
        $current = strtolower(trim((string) ($currentStmt->fetchColumn() ?: '')));

        if ($current === '' || $current === '#ffffff') {
            $update = $db->prepare("UPDATE {$prefix}m365landing_settings SET setting_value = ? WHERE setting_key = 'design_background_color'");
            $update->execute(['#edf1f6']);
        }

        $exists = $db->prepare("SELECT id FROM {$prefix}m365landing_settings WHERE setting_key = ?");
        $exists->execute([$markerKey]);
        if ($exists->fetch()) {
            $markerUpdate = $db->prepare("UPDATE {$prefix}m365landing_settings SET setting_value = ? WHERE setting_key = ?");
            $markerUpdate->execute([$markerVersion, $markerKey]);
        } else {
            $markerInsert = $db->prepare("INSERT INTO {$prefix}m365landing_settings (setting_key, setting_value) VALUES (?, ?)");
            $markerInsert->execute([$markerKey, $markerVersion]);
        }
    }

    private static function seed_cards(object $db, string $prefix): void
    {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$prefix}m365landing_cards");
        $stmt->execute();
        if ((int) $stmt->fetchColumn() > 0) {
            return;
        }

        $cards = [
            ['matrix', 'm365-lizenzmatrix', 'M365 Lizenzmatrix', 'Microsoft 365 Pläne vergleichen', 'Vergleiche M365-Pläne, Kernfeatures und Lizenzoptionen in einer klaren Matrix.', '📊', '', '', '/m365-lizenzmatrix', 'Matrix öffnen', 10, 1, 1],
            ['matrix', 'm365-addon-matrix', "M365 AddOn's", 'Erweiterungen und Zusatzlizenzen', 'Prüfe Add-ons, Voraussetzungen und typische Erweiterungen für Microsoft 365.', '🧩', '', '', '/m365-addon-matrix', 'Add-ons ansehen', 20, 1, 1],
            ['matrix', 'm365-copilot-matrix', 'Copilot Matrix', 'Copilot-Lizenzen und AI-Angebote', 'Ordne Copilot-Varianten, Lizenzvoraussetzungen und Einsatzbereiche schnell ein.', '🤖', '', '', '/m365-copilot-matrix', 'Copilot prüfen', 30, 1, 1],
            ['areas', 'azure-services', 'Azure Services', 'Cloud-Dienste im Überblick', 'Strukturierte Übersicht wichtiger Azure Services mit Beschreibungen, Einsatzfällen und Links.', '☁️', '', '', '/azure-services', 'Azure öffnen', 10, 0, 1],
            ['areas', 'm365-tutorials', 'M365 Tutorials', 'Lerninhalte und Schritt-für-Schritt-Guides', 'Verlinke hier deine zentrale Tutorial-Seite oder einen externen Lernbereich.', '🎓', '', '', '/m365-tutorials', 'Tutorials ansehen', 20, 0, 1],
            ['tools', 'm365-tools', 'M365 Tools Übersicht', 'Alle Rechner und Checklisten', 'Die vollständige Toolbox mit Lizenz-, ROI-, Storage-, Backup- und Copilot-Rechnern.', '🧰', '', '', '/m365-tools', 'Tools öffnen', 10, 0, 1],
            ['tools', 'm365-lizenzberater', 'M365 Lizenz-Berater', 'Passende Lizenzen ermitteln', 'Empfiehlt Basislizenzen, Add-ons und Mischmodelle anhand konkreter Anforderungen.', '🎯', '', '', '/m365-lizenzberater', 'Berater starten', 20, 0, 1],
            ['tools', 'm365-add-on-konfigurator', 'Add-On-Konfigurator', 'Add-ons sauber prüfen', 'Bewertet Add-ons, Voraussetzungen, Redundanzen und Upgrade-Alternativen.', '🧩', '', '', '/m365-add-on-konfigurator', 'Konfigurator öffnen', 30, 0, 1],
            ['tools', 'copilot-roi', 'Copilot ROI-Rechner', 'Business Case berechnen', 'Berechnet Break-even, Zeitersparnis und Rollout-Empfehlungen für Microsoft 365 Copilot.', '🤖', '', '', '/copilot-roi-rechner', 'ROI berechnen', 40, 0, 1],
            ['tools', 'm365-storage-bedarfsrechner', 'Storage-Bedarfs-Rechner', 'Speicherbedarf planen', 'Berechnet SharePoint-, OneDrive-, Exchange- und Archivbedarf mit Wachstumsszenarien.', '💾', '', '', '/m365-storage-bedarfsrechner', 'Storage prüfen', 50, 0, 1],
            ['tools', 'teams-phone-lizenzberater', 'Teams Phone-Berater', 'Telefonie-Lizenzen einordnen', 'Vergleicht Calling Plan, Operator Connect, Direct Routing und Mischmodelle.', '☎️', '', '', '/teams-phone-lizenzberater', 'Teams Phone prüfen', 60, 0, 1],
        ];

        $insert = $db->prepare("INSERT INTO {$prefix}m365landing_cards (section, slug, title, subtitle, description, icon, image_url, image_alt, url, button_label, sort_order, is_featured, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($cards as $card) {
            $insert->execute($card);
        }
    }

    private static function resolve_prefix(object $db): string
    {
        $prefix = '';
        if (method_exists($db, 'getPrefix')) {
            $prefix = (string) $db->getPrefix();
        } elseif (method_exists($db, 'prefix')) {
            $prefix = (string) $db->prefix();
        }

        $prefix = trim($prefix);
        if ($prefix === '') {
            return 'cms_';
        }

        $prefix = (string) preg_replace('/[^A-Za-z0-9_]/', '', $prefix);

        return $prefix !== '' ? $prefix : 'cms_';
    }

    private static function log_exception(string $context, \Throwable $e): void
    {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CMS M365 Landing [' . $context . ']: ' . $e->getMessage());
        }
    }
}
