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
        $this->plugin_template_dir = defined('CMS_EVENTS_PLUGIN_DIR')
            ? rtrim((string) CMS_EVENTS_PLUGIN_DIR, '/\\') . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR
            : dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
    }

    /**
     * @return array<int, string>
     */
    private function getPluginTemplateDirs(): array
    {
        $dirs = [];

        if ($this->plugin_template_dir !== '') {
            $dirs[] = $this->plugin_template_dir;
        }

        $runtimeDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR;
        $dirs[] = $runtimeDir;

        $unique = [];
        foreach ($dirs as $dir) {
            $normalized = str_replace('\\', '/', rtrim($dir, '/\\')) . '/';
            if (in_array($normalized, $unique, true)) {
                continue;
            }

            if (is_dir($dir)) {
                $unique[] = $normalized;
            }
        }

        return $unique;
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

        $template_candidates = [];
        if ($theme_template !== '' && file_exists($theme_template)) {
            $template_candidates[] = $theme_template;
        }

        foreach ($this->getPluginTemplateDirs() as $pluginDir) {
            $candidate = $pluginDir . $template_name;
            if (file_exists($candidate) && !in_array($candidate, $template_candidates, true)) {
                $template_candidates[] = $candidate;
            }
        }

        if ($template_candidates === []) {
            error_log("CMS Events: Template '{$template_name}' not found");
            $this->render_inline_template_fallback($template_name, $data);
            return;
        }

        extract($data, EXTR_SKIP);

        $lastException = null;
        foreach ($template_candidates as $template_file) {
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
                $template_role = ($theme_template !== '' && $template_file === $theme_template) ? 'theme-override' : 'plugin-fallback';
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

        $this->render_inline_template_fallback($template_name, $data);
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

        foreach ($this->getPluginTemplateDirs() as $pluginDir) {
            $plugin_template = $pluginDir . $template_name;
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
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

    private function render_inline_template_fallback(string $template_name, array $data = []): void
    {
        if (!headers_sent()) {
            http_response_code(200);
        }

        if ($template_name === 'archive-event.php') {
            $settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
            $archiveTitle = trim((string) ($settings['archive_title'] ?? 'Veranstaltungen'));
            if ($archiveTitle === '') {
                $archiveTitle = 'Veranstaltungen';
            }

            $events = is_array($data['events'] ?? null) ? $data['events'] : [];
            $baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';

            echo '<section class="phinit-plugin cms-events-wrap">';
            echo '<header class="cms-events-head">';
            echo '<p class="phinit-overline">Events</p>';
            echo '<h1>' . htmlspecialchars($archiveTitle, ENT_QUOTES, 'UTF-8') . '</h1>';
            echo '</header>';
            echo '<section class="cms-events-grid" aria-label="Event-Liste">';

            if ($events === []) {
                echo '<div class="cms-events-empty phinit-empty-state" role="status" aria-live="polite">';
                echo '<i class="ti ti-calendar-off" aria-hidden="true"></i>';
                echo '<p class="cms-events-empty__title">Keine Events gefunden.</p>';
                echo '</div>';
            } else {
                foreach ($events as $event) {
                    $eventObject = is_object($event) ? $event : (is_array($event) ? (object) $event : (object) []);
                    $eventId = (int) ($eventObject->id ?? 0);
                    $title = trim((string) ($eventObject->title ?? 'Event'));
                    if ($title === '') {
                        $title = 'Event';
                    }
                    $eventUrl = function_exists('cms_event_url')
                        ? cms_event_url($eventObject)
                        : ($baseUrl . '/events/' . $eventId);

                    echo '<article class="phinit-card cms-events-card cms-events-card--fallback">';
                    echo '<div class="cms-events-card__body">';
                    echo '<h2 class="cms-events-card__title"><a href="' . htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</a></h2>';
                    echo '<footer class="cms-events-card__footer">';
                    echo '<a href="' . htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') . '" class="phinit-btn phinit-btn--primary cms-events-card__button">Details</a>';
                    echo '</footer>';
                    echo '</div>';
                    echo '</article>';
                }
            }

            echo '</section>';
            echo '</section>';
            return;
        }

        echo '<section class="phinit-plugin cms-events-wrap">'
            . '<div class="cms-events-empty phinit-empty-state" role="status" aria-live="polite">'
            . '<i class="ti ti-alert-circle" aria-hidden="true"></i>'
            . '<p class="cms-events-empty__title">Events konnten aktuell nicht dargestellt werden.</p>'
            . '</div>'
            . '</section>';
    }
}
