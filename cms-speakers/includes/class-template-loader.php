<?php
/**
 * Template Loader für CMS Speakers
 *
 * @package CMS_Speakers
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Speakers_Template_Loader
{
    private static ?self $instance = null;
    private string $template_path;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->template_path = CMS_SPEAKERS_PLUGIN_DIR . 'templates/';
    }

    /**
     * Lädt ein Template
     */
    public function render_template(string $template_name, array $data = []): void
    {
        $template_file = $this->template_path . $template_name . '.php';

        if (!file_exists($template_file)) {
            if (defined('CMS_DEBUG') && CMS_DEBUG) {
                error_log("[CMS Speakers] Template nicht gefunden: {$template_file}");
            }
            echo '<p>Template nicht gefunden: ' . htmlspecialchars($template_name) . '</p>';
            return;
        }

        // Extrahiere Variablen
        extract($data, EXTR_SKIP);

        // Lade Template
        include $template_file;
    }

    /**
     * Rendert Speaker Card
     *
     * @param array|object $speaker  Speaker-Objekt/Array
     * @param array        $settings Design-Settings (aus get_settings())
     * @param array        $topics   Topics dieses Speakers
     */
    public function render_speaker_card(array|object $speaker, array $settings = [], array $topics = []): string
    {
        if (is_array($speaker)) {
            $speaker = (object) $speaker;
        }

        // Settings aus DB laden, falls nicht übergeben
        if (empty($settings)) {
            $settings = CMS_Speakers_Database::instance()->get_settings();
        }

        ob_start();
        include $this->template_path . 'speaker-card.php';
        return ob_get_clean();
    }

    /**
     * Gibt Template-Pfad zurück
     */
    public function get_template_path(): string
    {
        return $this->template_path;
    }
}
