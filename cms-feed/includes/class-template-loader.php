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

if (class_exists('CMS_Feed_Template_Loader', false)) {
    return;
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
            $this->render_template_error('Feed-Template fehlt', 'Ein benötigtes Feed-Template konnte nicht geladen werden.', null, [
                'template' => $template_name,
            ]);
            return;
        }

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            extract($data, EXTR_SKIP);
            include $template_file;
            echo ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            $this->render_template_error('Feed-Template konnte nicht gerendert werden', 'Ein Feed-Template hat einen Fehler ausgelöst. Bitte versuche es später erneut.', $e, [
                'template' => $template_name,
            ]);
        }
    }

    public function locate_template(string $template_name): ?string
    {
        $template_name = $this->normalize_template_name($template_name);
        if ($template_name === null) {
            return null;
        }

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
                $this->render_template($template, $data);
                return;
            }
        }

        $this->render_template_error('Feed-Template-Part fehlt', 'Ein benötigter Feed-Template-Baustein konnte nicht geladen werden.', null, [
            'template_part' => $slug,
        ]);
    }

    public function buffer_template(string $template_name, array $data = []): string
    {
        ob_start();
        $this->render_template($template_name, $data);
        return ob_get_clean();
    }

    private function normalize_template_name(string $template_name): ?string
    {
        $template_name = basename(str_replace('.php', '', $template_name));
        if ($template_name === '' || preg_match('/^[a-zA-Z0-9_-]+$/', $template_name) !== 1) {
            return null;
        }

        return $template_name . '.php';
    }

    private function render_template_error(string $title, string $message, ?\Throwable $exception = null, array $context = []): void
    {
        CMS_Feed_Error_Handler::instance()->render_error_page(500, $title, $message, $exception, ['scope' => 'template'] + $context);
    }
}
