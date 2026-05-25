<?php
/**
 * Shortcode/Widget Handler für CMS Speakers
 *
 * Da dieses CMS kein WordPress-Shortcode-System hat, wird das CMS-eigene
 * Hooks-System verwendet. Shortcode-Tags ([cms_speakers ...]) werden über
 * den 'cms_content'-Filter im Seiteninhalt ersetzt.
 *
 * Direkte Template-Einbindung: CMS_Speakers_Shortcode::instance()->render([...])
 *
 * @package CMS_Speakers
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Speakers_Shortcode
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->register_hooks();
    }

    /**
     * Hooks registrieren – kein WordPress add_shortcode(), sondern CMS-Filter
     */
    private function register_hooks(): void
    {
        if (class_exists('CMS\Hooks')) {
            // Seiteninhalt-Filter: [cms_speakers ...]-Tags ersetzen
            \CMS\Hooks::addFilter('cms_content', [$this, 'process_content_tags'], 20);
        }
    }

    /**
     * Verarbeitet [cms_speakers ...]-Tags im Seiten-Content-String.
     *
     * @param string $content Rohinhalt der Seite
     * @return string Verarbeiteter Inhalt
     */
    public function process_content_tags(string $content): string
    {
        return preg_replace_callback(
            '/\[cms_speakers([^\]]*)\]/',
            function (array $matches): string {
                $atts = $this->parse_atts($matches[1]);
                return $this->render($atts);
            },
            $content
        );
    }

    /**
     * Parst Attribut-String aus Tag wie: limit="6" travel="national" featured="1"
     *
     * @param string $atts_string Rohattribute aus dem Tag
     * @return array<string, mixed>
     */
    private function parse_atts(string $atts_string): array
    {
        $defaults = [
            'limit'    => 12,
            'travel'   => null,
            'featured' => false,
        ];

        $parsed = [];
        // Matcht key="value" oder key='value' oder key=value
        preg_match_all('/(\w+)\s*=\s*["\']?([^"\'>\s]*)["\']?/', $atts_string, $pairs, PREG_SET_ORDER);
        foreach ($pairs as $pair) {
            $parsed[$pair[1]] = $pair[2];
        }

        return array_merge($defaults, $parsed);
    }

    /**
     * Rendert die Speaker-Liste und gibt HTML zurück.
     *
     * @param array<string, mixed> $atts
     * @return string
     */
    public function render(array $atts = []): string
    {
        $defaults = [
            'limit'    => 12,
            'travel'   => null,
            'featured' => false,
        ];
        $atts = array_merge($defaults, $atts);

        if (!class_exists('CMS_Speakers_Database')) {
            return '';
        }

        $db_manager = CMS_Speakers_Database::instance();
        $allowedTravel = ['local', 'regional', 'national', 'international', 'worldwide'];
        $limit = max(1, min(200, (int) $atts['limit']));

        $args = [
            'limit'  => $limit,
            'offset' => 0,
        ];

        if (!empty($atts['travel']) && in_array((string) $atts['travel'], $allowedTravel, true)) {
            $args['travel_radius'] = (string) $atts['travel'];
        }

        if (!empty($atts['featured'])) {
            $args['is_featured'] = 1;
        }

        $speakers = $db_manager->get_speakers($args);

        if (empty($speakers)) {
            return '<div class="speakers-shortcode"><p class="no-speakers">Keine Speakers gefunden.</p></div>';
        }

        if (!class_exists('CMS_Speakers_Template_Loader')) {
            return '';
        }

        $template_loader = CMS_Speakers_Template_Loader::instance();
        $settings         = $db_manager->get_settings();

        ob_start();
        ?>
        <div class="speakers-shortcode">
            <div class="sp-grid">
                <?php foreach ($speakers as $speaker): ?>
                    <?php echo $template_loader->render_speaker_card($speaker, $settings); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }
}
