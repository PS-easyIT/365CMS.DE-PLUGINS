<?php
/**
 * Database and settings handling for CMS 365NETWORK.
 *
 * @package CMS_365NETWORK
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_365NETWORK_Database
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct() {}

    public function create_tables(): void
    {
        try {
            $db     = CMS\Database::instance();
            $pdo    = $db->getPdo();
            $prefix = $db->prefix();

            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}network365_settings (
                id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key   VARCHAR(191) NOT NULL UNIQUE,
                setting_value LONGTEXT DEFAULT NULL,
                updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

            $this->seed_default_settings();
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK create_tables failed: ' . $e->getMessage());
        }
    }

    public function default_settings(): array
    {
        return [
            'settings_version' => CMS_365NETWORK_VERSION,
            'landing_enabled' => '1',
            'hub_domains' => '',
            'route_slug' => '365network',
            'hero_eyebrow' => '365network Hub',
            'landing_title' => 'Events, Speaker, Firmen & Experten an einem Ort',
            'landing_subtitle' => 'Die zentrale Landingpage für Ihr Netzwerk: kuratierte Veranstaltungen, inspirierende Speaker, passende Firmen und Expertinnen und Experten.',
            'primary_button_label' => 'Bereiche entdecken',
            'primary_button_url' => '#n365-areas',
            'secondary_button_label' => 'Kontakt aufnehmen',
            'secondary_button_url' => '/kontakt',
            'featured_enabled' => '1',
            'featured_title' => '365network verbindet Menschen, Wissen und Business.',
            'featured_text' => 'Nutzen Sie eine moderne HubSite als Einstiegspunkt für Community, Partner und Fachthemen – responsiv, schnell und flexibel steuerbar.',
            'featured_image_url' => '',
            'featured_url' => '',
            'layout_variant' => 'grid-2x2',
            'content_width' => '1180',
            'card_radius' => '24',
            'section_gap' => '28',
            'primary_color' => '#2563eb',
            'accent_color' => '#0f766e',
            'background_color' => '#f8fafc',
            'surface_color' => '#ffffff',
            'text_color' => '#0f172a',
            'muted_color' => '#64748b',
            'border_color' => '#e2e8f0',
            'show_sidebar' => '1',
            'sidebar_position' => 'right',
            'sidebar_events_count' => '3',
            'random_speakers_count' => '1',
            'random_companies_count' => '1',
            'random_experts_count' => '1',
            'preview_placement' => 'sidebar',
            'show_events_preview' => '1',
            'show_speakers_preview' => '1',
            'show_companies_preview' => '1',
            'show_experts_preview' => '1',
            'events_card_title' => 'Events',
            'events_card_text' => 'Kommende Veranstaltungen, Formate und Community-Termine.',
            'events_card_url' => '/events',
            'speakers_card_title' => 'Speaker',
            'speakers_card_text' => 'Menschen mit Bühne, Erfahrung und starken Themen.',
            'speakers_card_url' => '/speakers',
            'companies_card_title' => 'Firmen',
            'companies_card_text' => 'Partner, Unternehmen und Organisationen im Netzwerk.',
            'companies_card_url' => '/companies',
            'experts_card_title' => 'Experten',
            'experts_card_text' => 'Fachleute für Beratung, Projekte und Umsetzung.',
            'experts_card_url' => '/experts',
            'analytics_enabled' => '0',
            'analytics_position' => 'head',
            'analytics_code' => '',
        ];
    }

    public function get_settings(): array
    {
        $defaults = $this->default_settings();
        $rows = [];

        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$db->prefix()}network365_settings");
            $stmt->execute([]);
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
        } catch (\Throwable $e) {
            try {
                $this->create_tables();
                $db = CMS\Database::instance();
                $stmt = $db->prepare("SELECT setting_key, setting_value FROM {$db->prefix()}network365_settings");
                $stmt->execute([]);
                $rows = $stmt->fetchAll(\PDO::FETCH_OBJ) ?: [];
            } catch (\Throwable $ignored) {
                return $defaults;
            }
        }

        $settings = [];
        foreach ($rows as $row) {
            $settings[(string) $row->setting_key] = (string) ($row->setting_value ?? '');
        }

        return array_merge($defaults, $settings);
    }

    public function save_settings(array $settings): void
    {
        $this->persist_settings($settings, true);
    }

    private function persist_settings(array $settings, bool $retryOnMissingTable): void
    {
        $allowed = array_keys($this->default_settings());
        $payload = array_intersect_key($settings, array_flip($allowed));
        $payload['settings_version'] = CMS_365NETWORK_VERSION;

        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare(
                "INSERT INTO {$db->prefix()}network365_settings (setting_key, setting_value)
                 VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
            );

            foreach ($payload as $key => $value) {
                $stmt->execute([(string) $key, (string) $value]);
            }
        } catch (\Throwable $e) {
            if ($retryOnMissingTable) {
                try {
                    $this->create_tables();
                    $this->persist_settings($payload, false);
                    return;
                } catch (\Throwable $nested) {
                    error_log('CMS 365NETWORK save_settings failed: ' . $nested->getMessage());
                    return;
                }
            }

            error_log('CMS 365NETWORK save_settings failed: ' . $e->getMessage());
        }
    }

    private function seed_default_settings(): void
    {
        try {
            $db = CMS\Database::instance();
            $stmt = $db->prepare(
                "INSERT IGNORE INTO {$db->prefix()}network365_settings (setting_key, setting_value) VALUES (?, ?)"
            );

            foreach ($this->default_settings() as $key => $value) {
                $stmt->execute([(string) $key, (string) $value]);
            }
        } catch (\Throwable $e) {
            error_log('CMS 365NETWORK seed settings failed: ' . $e->getMessage());
        }
    }
}
