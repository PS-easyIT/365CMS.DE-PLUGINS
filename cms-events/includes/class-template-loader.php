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

if (class_exists('CMS_Events_Template_Loader', false)) {
    return;
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
        $template_name = str_replace('.php', '', $template_name) . '.php';

        $theme_dir = $this->getThemeTemplateDir();
        $theme_template = $theme_dir !== '' ? $theme_dir . $template_name : '';
        $plugin_template = $this->plugin_template_dir . $template_name;

        $template_candidates = [];
        if ($theme_template !== '' && file_exists($theme_template)) {
            $template_candidates[] = $theme_template;
        }
        if (file_exists($plugin_template)) {
            $template_candidates[] = $plugin_template;
        }

        if ($template_candidates === []) {
            error_log("CMS Events: Template '{$template_name}' not found");
            $this->render_inline_template_fallback($template_name);
            return;
        }

        extract($data, EXTR_SKIP);

        $lastException = null;
        foreach ($template_candidates as $index => $template_file) {
            $bufferLevel = ob_get_level();
            ob_start();

            try {
                include $template_file;
                $html = ob_get_clean();
                if ($html !== false) {
                    echo $html;
                }
                return;
            } catch (\Throwable $e) {
                while (ob_get_level() > $bufferLevel) {
                    ob_end_clean();
                }

                $lastException = $e;
                $template_role = $index === 0 && $template_file === $theme_template ? 'theme-override' : 'plugin-fallback';
                error_log(
                    sprintf(
                        "CMS Events: Template '%s' (%s) failed in %s:%d – %s",
                        $template_name,
                        $template_role,
                        (string) $e->getFile(),
                        (int) $e->getLine(),
                        (string) $e->getMessage()
                    )
                );
            }
        }

        if ($lastException instanceof \Throwable) {
            error_log("CMS Events: Template '{$template_name}' exhausted all candidates.");
        }

        $this->render_inline_template_fallback($template_name);
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
                $this->render_template($template, $data);
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

    private function render_template_error(string $title, string $message): void
    {
        http_response_code(500);
        try {
            if (class_exists('CMS\\ThemeManager')) {
                \CMS\ThemeManager::instance()->render('error', [
                    'error_code'    => 500,
                    'error_title'   => $title,
                    'error_message' => $message,
                ]);
                return;
            }
        } catch (\Throwable $e) {
            error_log('CMS Events template error fallback failed: ' . $e->getMessage());
        }

        echo '<section class="cms-error"><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></section>';
    }

    private function render_inline_template_fallback(string $template_name): void
    {
        if (!headers_sent()) {
            http_response_code(200);
        }

        $safeTemplate = htmlspecialchars($template_name, ENT_QUOTES, 'UTF-8');
        echo '<section class="phinit-plugin cms-events-wrap">'
            . '<div class="cms-events-empty phinit-empty-state" role="status" aria-live="polite">'
            . '<i class="ti ti-alert-circle" aria-hidden="true"></i>'
            . '<p class="cms-events-empty__title">Events konnten aktuell nicht dargestellt werden.</p>'
            . '<p class="cms-events-muted">Template: ' . $safeTemplate . '</p>'
            . '</div>'
            . '</section>';
    }
}
