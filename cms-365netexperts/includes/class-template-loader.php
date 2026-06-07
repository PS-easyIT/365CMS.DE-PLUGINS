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
        $template_name = preg_replace('/[^a-z0-9_-]+/i', '', str_replace('.php', '', $template_name));
        if ($template_name === null || $template_name === '') {
            return null;
        }
        $template_name .= '.php';

        $theme_template = $this->theme_template_dir !== '' ? $this->resolve_template_path($this->theme_template_dir, $template_name) : null;
        if ($theme_template !== null) {
            return $theme_template;
        }

        $legacy_theme_template = null;
        if (class_exists('CMS\\ThemeManager')) {
            $legacy_theme_template = $this->resolve_template_path(\CMS\ThemeManager::instance()->getThemePath() . 'experts/', $template_name);
        }
        if ($legacy_theme_template !== null) {
            return $legacy_theme_template;
        }

        return $this->resolve_template_path($this->template_dir, $template_name);
    }

    private function resolve_template_path(string $base_dir, string $template_name): ?string
    {
        $base_real = realpath($base_dir);
        if ($base_real === false || !is_dir($base_real)) {
            return null;
        }

        $candidate = $base_real . DIRECTORY_SEPARATOR . $template_name;
        if (!is_file($candidate)) {
            return null;
        }

        $candidate_real = realpath($candidate);
        if ($candidate_real === false) {
            return null;
        }

        $prefix = rtrim($base_real, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($candidate_real, $prefix) || pathinfo($candidate_real, PATHINFO_EXTENSION) !== 'php') {
            return null;
        }

        return $candidate_real;
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
