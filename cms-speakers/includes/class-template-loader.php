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
    private string $theme_template_path = '';

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
        if (class_exists('CMS\\ThemeManager')) {
            $this->theme_template_path = \CMS\ThemeManager::instance()->getThemePath() . 'cms-speakers/';
        }
    }

    private function locate_template(string $template_name): ?string
    {
        $template_name = str_replace('.php', '', $template_name) . '.php';

        $theme_template = $this->theme_template_path !== '' ? $this->theme_template_path . $template_name : '';
        if ($theme_template !== '' && file_exists($theme_template)) {
            return $theme_template;
        }

        $legacy_theme_template = '';
        if (class_exists('CMS\\ThemeManager')) {
            $legacy_theme_template = \CMS\ThemeManager::instance()->getThemePath() . 'speakers/' . $template_name;
        }
        if ($legacy_theme_template !== '' && file_exists($legacy_theme_template)) {
            return $legacy_theme_template;
        }

        $plugin_template = $this->template_path . $template_name;
        return file_exists($plugin_template) ? $plugin_template : null;
    }

    /**
     * Lädt ein Template
     */
    public function render_template(string $template_name, array $data = []): void
    {
        $template_file = $this->locate_template($template_name);

        if ($template_file === null) {
            error_log("[CMS Speakers] Template nicht gefunden: {$template_name}");
            throw new \RuntimeException('Speaker-Template nicht gefunden: ' . $template_name);
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
        try {
            $this->render_template('speaker-card', ['speaker' => $speaker, 'settings' => $settings, 'topics' => $topics]);
            return (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            error_log('CMS Speakers card render: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Gibt Template-Pfad zurück
     */
    public function get_template_path(): string
    {
        return $this->template_path;
    }
}
