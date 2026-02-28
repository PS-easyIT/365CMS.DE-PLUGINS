<?php
/**
 * Template Loader für CMS Feed
 *
 * @package CMS_Feed
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Feed_Template_Loader
{
    private static ?self $instance = null;
    private string $plugin_template_dir;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->plugin_template_dir = CMS_FEED_PLUGIN_DIR . 'templates/';
    }

    private function getThemeTemplateDir(): string
    {
        return \CMS\ThemeManager::instance()->getThemePath() . 'cms-feed/';
    }

    public function render_template(string $template_name, array $data = []): void
    {
        $template_file = $this->locate_template($template_name);

        if (!$template_file) {
            error_log("CMS Feed: Template '{$template_name}' not found");
            return;
        }

        extract($data, EXTR_SKIP);
        include $template_file;
    }

    public function locate_template(string $template_name): ?string
    {
        $template_name = str_replace('.php', '', $template_name) . '.php';

        // Theme-Override
        $theme_template = $this->getThemeTemplateDir() . $template_name;
        if (file_exists($theme_template)) {
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
