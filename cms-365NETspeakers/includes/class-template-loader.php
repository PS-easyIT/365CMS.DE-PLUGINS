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
        $template_name = $this->sanitize_template_name($template_name);
        if ($template_name === '') {
            return null;
        }

        $theme_template = $this->theme_template_path !== '' ? $this->theme_template_path . $template_name : '';
        if ($theme_template !== '' && $this->is_allowed_template_path($theme_template)) {
            return $theme_template;
        }

        $legacy_theme_template = '';
        if (class_exists('CMS\\ThemeManager')) {
            $legacy_theme_template = \CMS\ThemeManager::instance()->getThemePath() . 'speakers/' . $template_name;
        }
        if ($legacy_theme_template !== '' && $this->is_allowed_template_path($legacy_theme_template)) {
            return $legacy_theme_template;
        }

        $plugin_template = $this->template_path . $template_name;
        return $this->is_allowed_template_path($plugin_template) ? $plugin_template : null;
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

    private function sanitize_template_name(string $template_name): string
    {
        $template_name = trim(str_replace('\\', '/', $template_name));
        $template_name = preg_replace('/\.php$/i', '', $template_name) ?? '';
        if ($template_name === '' || str_contains($template_name, '..')) {
            return '';
        }
        if (preg_match('/^[a-z0-9_-]+$/i', $template_name) !== 1) {
            return '';
        }

        return $template_name . '.php';
    }

    private function is_allowed_template_path(string $path): bool
    {
        $realPath = realpath($path);
        if ($realPath === false || !is_file($realPath)) {
            return false;
        }

        $allowedRoots = [realpath($this->template_path)];
        if ($this->theme_template_path !== '') {
            $allowedRoots[] = realpath($this->theme_template_path);
        }
        if (class_exists('CMS\\ThemeManager')) {
            $legacyRoot = realpath(\CMS\ThemeManager::instance()->getThemePath() . 'speakers/');
            if ($legacyRoot !== false) {
                $allowedRoots[] = $legacyRoot;
            }
        }

        foreach ($allowedRoots as $root) {
            if ($root !== false && str_starts_with($realPath, $root . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
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
