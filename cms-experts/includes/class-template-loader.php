<?php
/**
 * Template Loader für CMS Experts
 *
 * @package CMS_Experts
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Experts_Template_Loader
{
    private static ?self $instance = null;
    private string $template_dir;
    private string $theme_template_dir = '';

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->template_dir = CMS_EXPERTS_PLUGIN_DIR . 'templates/';
        if (class_exists('CMS\\ThemeManager')) {
            $this->theme_template_dir = \CMS\ThemeManager::instance()->getThemePath() . 'cms-experts/';
        }
    }

    private function locate_template(string $template_name): ?string
    {
        $template_name = str_replace('.php', '', $template_name) . '.php';

        $theme_template = $this->theme_template_dir !== '' ? $this->theme_template_dir . $template_name : '';
        if ($theme_template !== '' && file_exists($theme_template)) {
            return $theme_template;
        }

        $legacy_theme_template = '';
        if (class_exists('CMS\\ThemeManager')) {
            $legacy_theme_template = \CMS\ThemeManager::instance()->getThemePath() . 'experts/' . $template_name;
        }
        if ($legacy_theme_template !== '' && file_exists($legacy_theme_template)) {
            return $legacy_theme_template;
        }

        $plugin_template = $this->template_dir . $template_name;
        return file_exists($plugin_template) ? $plugin_template : null;
    }

    /**
     * Rendert ein Template
     */
    public function render_template(string $template_name, array $data = []): void
    {
        $template_file = $this->locate_template($template_name);

        if ($template_file === null) {
            echo '<!-- Template not found: ' . htmlspecialchars($template_name, ENT_QUOTES, 'UTF-8') . ' -->';
            return;
        }

        // Extrahiere Daten in lokale Variablen
        extract($data, EXTR_SKIP);

        // Include Template
        include $template_file;
    }

    /**
     * Holt Template-Content als String
     */
    public function get_template(string $template_name, array $data = []): string
    {
        ob_start();
        $this->render_template($template_name, $data);
        return ob_get_clean();
    }

    /**
     * Rendert Expert Card
     *
     * @param object $expert
     * @param array  $settings  Design-/Plugin-Settings (optional)
     */
    public function render_expert_card($expert, array $settings = []): string
    {
        return $this->get_template('expert-card', ['expert' => $expert, 'settings' => $settings]);
    }
}
