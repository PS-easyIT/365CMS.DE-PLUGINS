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

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->template_dir = CMS_Experts::instance()->get_plugin_dir() . 'templates/';
    }

    /**
     * Rendert ein Template
     */
    public function render_template(string $template_name, array $data = []): void
    {
        // Theme-Override: Pfad wird zur Laufzeit vom ThemeManager ermittelt (kein THEME_DIR)
        $theme_template = \CMS\ThemeManager::instance()->getThemePath() . 'experts/' . $template_name . '.php';
        $plugin_template = $this->template_dir . $template_name . '.php';

        $template_file = file_exists($theme_template) ? $theme_template : $plugin_template;

        if (!file_exists($template_file)) {
            echo '<!-- Template not found: ' . $template_name . ' -->';
            return;
        }

        // Extrahiere Daten in lokale Variablen
        extract($data);

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
