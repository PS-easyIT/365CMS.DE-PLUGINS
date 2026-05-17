<?php
/**
 * Template Loader für CMS Events
 *
 * @package CMS_Events
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Events_Template_Loader
{
    private static ?self $instance = null;
    private string $plugin_template_dir;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->plugin_template_dir = CMS_EVENTS_PLUGIN_DIR . 'templates/';
    }

    /**
     * Liefert das Theme-Override-Verzeichnis zur Laufzeit.
     * Nutzt ThemeManager statt THEME_DIR, damit Plugins theme-unabhängig sind.
     */
    private function getThemeTemplateDir(): string
    {
        if (!class_exists('CMS\\ThemeManager')) {
            return '';
        }

        return \CMS\ThemeManager::instance()->getThemePath() . 'cms-events/';
    }

    public function render_template(string $template_name, array $data = []): void
    {
        $template_file = $this->locate_template($template_name);

        if (!$template_file) {
            error_log("CMS Events: Template '{$template_name}' not found");
            return;
        }

        extract($data, EXTR_SKIP);

        include $template_file;
    }

    private function locate_template(string $template_name): ?string
    {
        $template_name = str_replace('.php', '', $template_name) . '.php';

        // Theme-Override: Pfad wird zur Laufzeit vom ThemeManager ermittelt
        $theme_dir = $this->getThemeTemplateDir();
        $theme_template = $theme_dir !== '' ? $theme_dir . $template_name : '';
        if ($theme_template !== '' && file_exists($theme_template)) {
            return $theme_template;
        }

        // Plugin Template als Fallback
        $plugin_template = $this->plugin_template_dir . $template_name;
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }

        return null;
    }

    public function get_template_part(string $slug, string $name = '', array $data = []): void
    {
        $templates = [];
        
        if ($name) {
            $templates[] = "{$slug}-{$name}";
        }
        $templates[] = $slug;

        foreach ($templates as $template) {
            $located = $this->locate_template($template);
            if ($located) {
                extract($data, EXTR_SKIP);
                include $located;
                return;
            }
        }
    }

    public function buffer_template(string $template_name, array $data = []): string
    {
        ob_start();
        $this->render_template($template_name, $data);
        return ob_get_clean();
    }
}
