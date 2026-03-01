<?php
/**
 * CMS Booking – Integration-API
 *
 * Ermöglicht anderen Plugins (cms-experts, cms-speakers, cms-events, cms-companies …)
 * sich als buchbare Anbieter zu registrieren und Buchungs-Buttons einzublenden.
 *
 * Verwendung durch andere Plugins:
 *
 *   CMS\Hooks::addAction('booking_register_providers', function () {
 *       CMS_Booking_Integration::register_provider_type('cms-experts', [
 *           'label'          => 'Experten',
 *           'icon'           => '👨‍💼',
 *           'source_table'   => 'experts',
 *           'name_column'    => 'name',
 *           'email_column'   => 'email',
 *           'user_id_column' => 'user_id',
 *           'single_route'   => '/experte/{slug}',
 *           'contact_template' => 'booking-expert',
 *       ]);
 *   });
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Booking_Integration
{
    /** @var array<string, array> Registrierte Anbieter-Typen */
    private static array $providerTypes = [];

    /** @var bool Wurde init() bereits aufgerufen? */
    private static bool $initialized = false;

    /**
     * Wird per cms_init (Priorität 50) aufgerufen.
     */
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        self::$initialized = true;

        // Standard-Typen vorab registrieren (sofern Plugins aktiv)
        self::register_builtin_types();

        // Hook für externe Plugins
        if (class_exists('CMS\Hooks')) {
            \CMS\Hooks::doAction('booking_register_providers');
        }
    }

    /* ================================================================== */
    /*  Provider-Type-Registry                                             */
    /* ================================================================== */

    /**
     * Neuen Anbieter-Typ registrieren.
     *
     * @param  string  $pluginSlug     Plugin-Slug (z. B. 'cms-experts')
     * @param  array   $config         Konfiguration
     *    - label           string  Anzeigename (z. B. "Experten")
     *    - icon            string  Emoji-Icon
     *    - source_table    string  DB-Tabellenname ohne Prefix (z. B. "experts")
     *    - name_column     string  Spalte für Anzeigename
     *    - email_column    string  Spalte für E-Mail
     *    - user_id_column  string  Spalte für User-ID (optional)
     *    - single_route    string  URL-Pattern der Single-Seite (optional)
     *    - contact_template string Bevorzugtes Kontakt-Template (optional)
     *    - fields_map      array   Zusätzliches Mapping (optional)
     */
    public static function register_provider_type(string $pluginSlug, array $config): void
    {
        $defaults = [
            'label'            => $pluginSlug,
            'icon'             => '📋',
            'source_table'     => '',
            'name_column'      => 'name',
            'email_column'     => 'email',
            'user_id_column'   => 'user_id',
            'single_route'     => '',
            'contact_template' => '',
            'fields_map'       => [],
        ];

        self::$providerTypes[$pluginSlug] = array_merge($defaults, $config);
    }

    /**
     * Alle registrierten Anbieter-Typen abrufen.
     */
    public static function get_provider_types(): array
    {
        return self::$providerTypes;
    }

    /**
     * Einzelnen Anbieter-Typ abrufen.
     */
    public static function get_provider_type(string $pluginSlug): ?array
    {
        return self::$providerTypes[$pluginSlug] ?? null;
    }

    /**
     * Prüfe ob ein Plugin als Anbieter registriert ist.
     */
    public static function is_registered(string $pluginSlug): bool
    {
        return isset(self::$providerTypes[$pluginSlug]);
    }

    /* ================================================================== */
    /*  Eingebaute Provider-Typen                                          */
    /* ================================================================== */

    private static function register_builtin_types(): void
    {
        if (!class_exists('CMS\PluginManager')) {
            return;
        }
        $pm = \CMS\PluginManager::instance();

        if ($pm->isPluginActive('cms-experts')) {
            self::register_provider_type('cms-experts', [
                'label'            => 'Experten',
                'icon'             => '👨‍💼',
                'source_table'     => 'experts',
                'name_column'      => 'name',
                'email_column'     => 'email',
                'user_id_column'   => 'user_id',
                'single_route'     => '/experte/{slug}',
                'contact_template' => 'booking-expert',
            ]);
        }

        if ($pm->isPluginActive('cms-speakers')) {
            self::register_provider_type('cms-speakers', [
                'label'            => 'Speaker',
                'icon'             => '🎤',
                'source_table'     => 'speakers',
                'name_column'      => 'name',
                'email_column'     => 'email',
                'user_id_column'   => 'user_id',
                'single_route'     => '/speaker/{slug}',
                'contact_template' => 'booking-event',
            ]);
        }

        if ($pm->isPluginActive('cms-events')) {
            self::register_provider_type('cms-events', [
                'label'            => 'Events',
                'icon'             => '📅',
                'source_table'     => 'events',
                'name_column'      => 'title',
                'email_column'     => 'contact_email',
                'user_id_column'   => 'organizer_id',
                'single_route'     => '/event/{slug}',
                'contact_template' => 'booking-event',
            ]);
        }

        if ($pm->isPluginActive('cms-companies')) {
            self::register_provider_type('cms-companies', [
                'label'            => 'Unternehmen',
                'icon'             => '🏢',
                'source_table'     => 'companies',
                'name_column'      => 'name',
                'email_column'     => 'email',
                'user_id_column'   => 'user_id',
                'single_route'     => '/unternehmen/{slug}',
                'contact_template' => 'booking-service',
            ]);
        }
    }

    /* ================================================================== */
    /*  Buchungs-Button rendern                                            */
    /* ================================================================== */

    /**
     * Buchungs-Button für eine Single-Seite rendern.
     *
     * Aufruf durch andere Plugins:
     *   CMS_Booking_Integration::render_booking_button('cms-experts', $expertId, [
     *       'label' => 'Experte buchen',
     *       'class' => 'btn btn-primary',
     *   ]);
     *
     * @param  string  $pluginSlug   Plugin-Slug
     * @param  int     $sourceId     Quell-ID (z. B. Expert-ID)
     * @param  array   $options      Optionale Anpassungen (label, class, icon)
     * @return string  HTML
     */
    public static function render_booking_button(string $pluginSlug, int $sourceId, array $options = []): string
    {
        $provider = CMS_Booking_Providers::instance()->get_by_source($pluginSlug, $sourceId);
        if (!$provider || $provider['status'] !== 'active') {
            return '';
        }

        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        $url     = $siteUrl . '/booking/' . htmlspecialchars($provider['slug']);
        $label   = htmlspecialchars($options['label'] ?? 'Termin buchen');
        $icon    = $options['icon'] ?? '📅';
        $class   = htmlspecialchars($options['class'] ?? 'btn btn-primary booking-btn');

        return <<<HTML
<a href="{$url}" class="{$class}">
    <span class="booking-btn__icon">{$icon}</span>
    <span class="booking-btn__label">{$label}</span>
</a>
HTML;
    }

    /**
     * Buchungs-Widget für Single-Seiten rendern (Button + Info-Box).
     */
    public static function render_booking_widget(string $pluginSlug, int $sourceId, array $options = []): string
    {
        $provider = CMS_Booking_Providers::instance()->get_by_source($pluginSlug, $sourceId);
        if (!$provider || $provider['status'] !== 'active') {
            return '';
        }

        $services = CMS_Booking_Services::instance()->get_by_provider((int) $provider['id'], 'active');
        if (empty($services)) {
            return self::render_booking_button($pluginSlug, $sourceId, $options);
        }

        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        $baseUrl = $siteUrl . '/booking/' . htmlspecialchars($provider['slug']);
        $title   = htmlspecialchars($options['title'] ?? 'Buchbare Leistungen');

        $html = '<div class="booking-widget">';
        $html .= '<h4 class="booking-widget__title">' . ($options['icon'] ?? '📅') . ' ' . $title . '</h4>';
        $html .= '<ul class="booking-widget__services">';

        foreach ($services as $service) {
            $url      = $baseUrl . '/' . htmlspecialchars($service['slug']);
            $sTitle   = htmlspecialchars($service['title']);
            $duration = CMS_Booking_Services::format_duration((int) $service['duration_min']);
            $price    = CMS_Booking_Services::format_price((int) $service['price_cents'], $provider['currency'] ?? 'EUR');

            $html .= <<<HTML
<li class="booking-widget__service">
    <div class="booking-widget__service-info">
        <strong>{$sTitle}</strong>
        <span class="booking-widget__meta">{$duration} · {$price}</span>
    </div>
    <a href="{$url}" class="btn btn-sm btn-primary">Buchen</a>
</li>
HTML;
        }

        $html .= '</ul></div>';
        return $html;
    }

    /* ================================================================== */
    /*  Auto-Sync: Quell-Datensatz → Booking-Provider                     */
    /* ================================================================== */

    /**
     * Synchronisiert einen Datensatz aus einem Quell-Plugin als Buchungsanbieter.
     *
     * Wird typischerweise aufgerufen, wenn ein Experte/Speaker/Event bearbeitet wird.
     *
     * @param  string  $pluginSlug  Plugin-Slug (z. B. 'cms-experts')
     * @param  int     $sourceId    PK im Quell-Plugin
     * @param  array   $overrides   Optionale Überschreibungen
     * @return int     Provider-ID
     */
    public static function sync_provider(string $pluginSlug, int $sourceId, array $overrides = []): int
    {
        $type = self::get_provider_type($pluginSlug);
        if (!$type || $type['source_table'] === '') {
            throw new \InvalidArgumentException("Unbekannter Provider-Typ: {$pluginSlug}");
        }

        $db   = \CMS\Database::instance();
        $p    = $db->getPrefix();
        $stmt = $db->prepare("SELECT * FROM {$p}{$type['source_table']} WHERE id = ?");
        $stmt->execute([$sourceId]);
        $source = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$source) {
            throw new \RuntimeException("Quell-Datensatz nicht gefunden: {$pluginSlug}#{$sourceId}");
        }

        $data = array_merge([
            'source_plugin' => $pluginSlug,
            'source_id'     => $sourceId,
            'display_name'  => $source[$type['name_column']]    ?? 'Unbekannt',
            'email'         => $source[$type['email_column']]   ?? '',
            'user_id'       => $source[$type['user_id_column']] ?? null,
        ], $overrides);

        return CMS_Booking_Providers::instance()->upsert($data);
    }

    /**
     * Buchungs-Button HTML für eine bestimmte Quell-Entität zurückgeben.
     * Erstellt den Provider bei Bedarf automatisch (Lazy Registration).
     */
    public static function get_or_create_button(string $pluginSlug, int $sourceId, array $options = []): string
    {
        $provider = CMS_Booking_Providers::instance()->get_by_source($pluginSlug, $sourceId);
        if (!$provider) {
            try {
                self::sync_provider($pluginSlug, $sourceId);
            } catch (\Throwable $e) {
                return '';
            }
        }
        return self::render_booking_button($pluginSlug, $sourceId, $options);
    }
}
