<?php
/**
 * CMS M365 Azure – Public frontend.
 *
 * @package CMS_M365Azure
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Azure_Frontend
{
    private static ?self $instance = null;
    private ?string $requestPathCache = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->register_routes();
        }

        return self::$instance;
    }

    private function __construct()
    {
        if (class_exists('CMS\\Hooks')) {
            \CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 20);
            \CMS\Hooks::addAction('head', [$this, 'output_design_tokens'], 30);
        }
    }

    private function register_routes(): void
    {
        if (!class_exists('CMS\\Router')) {
            return;
        }

        $slug = $this->route_slug();
        \CMS\Router::instance()->addRoute('GET', '/' . $slug, function (): void {
            $this->render_archive();
        });
    }

    public function enqueue_styles(): void
    {
        if (!$this->is_request()) {
            return;
        }

        $css = CMS_M365AZURE_PLUGIN_DIR . 'assets/css/style.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
            . htmlspecialchars(CMS_M365AZURE_PLUGIN_URL . 'assets/css/style.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public function output_design_tokens(): void
    {
        if (!$this->is_request()) {
            return;
        }

        $settings = $this->repo()->settings();
        $primary = CMS_M365Azure_Repository::color((string) ($settings['design_primary_color'] ?? ''), '#2563eb');
        $accent = CMS_M365Azure_Repository::color((string) ($settings['design_accent_color'] ?? ''), '#f59e0b');
        $background = CMS_M365Azure_Repository::color((string) ($settings['design_background_color'] ?? ''), '#ffffff');
        $surface = CMS_M365Azure_Repository::color((string) ($settings['design_surface_color'] ?? ''), '#ffffff');
        $radius = max(0, min(24, (int) ($settings['design_border_radius'] ?? 10)));
        $maxWidth = max(720, min(1600, (int) ($settings['layout_max_width'] ?? 1180)));
        $imageWidth = max(180, min(520, (int) ($settings['card_image_width'] ?? 320)));

        echo '<style id="cms-m365azure-design">' . "\n";
        echo ':root {' . "\n";
        echo '    --azs-primary: ' . htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --azs-accent: ' . htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --azs-bg: ' . htmlspecialchars($background, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --azs-surface: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --azs-radius: ' . (int) $radius . 'px;' . "\n";
        echo '    --azs-max-width: ' . (int) $maxWidth . 'px;' . "\n";
        echo '    --azs-image-width: ' . (int) $imageWidth . 'px;' . "\n";
        echo '}' . "\n";
        echo '</style>' . "\n";
    }

    private function render_archive(): void
    {
        $repo = $this->repo();
        $settings = $repo->settings();
        $categories = $repo->categories(true);
        $services = $repo->services(null, true);
        $servicesByCategory = [];
        foreach ($services as $service) {
            $servicesByCategory[(int) $service['category_id']][] = $service;
        }

        $title = $this->setting($settings, 'seo_title', $this->setting($settings, 'page_title', 'Microsoft Azure Services'));
        $description = $this->setting($settings, 'seo_description', $this->setting($settings, 'page_intro', 'Übersicht der wichtigsten Microsoft Azure Services.'));
        $this->set_seo($title, $description);

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => $title, 'description' => $description]);
        }

        include CMS_M365AZURE_PLUGIN_DIR . 'templates/archive.php';

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }

        exit;
    }

    /** @param array<string,string> $settings */
    private function setting(array $settings, string $key, string $default = ''): string
    {
        $value = trim(strip_tags((string) ($settings[$key] ?? '')));

        return $value !== '' ? $value : $default;
    }

    private function route_slug(): string
    {
        try {
            $settings = $this->repo()->settings();
            $slug = CMS_M365Azure_Repository::slug((string) ($settings['route_slug'] ?? 'azure-services'));
            return $slug !== '' ? $slug : 'azure-services';
        } catch (\Throwable $e) {
            return 'azure-services';
        }
    }

    private function is_request(): bool
    {
        $path = $this->request_path();
        $slug = $this->route_slug();

        return $path === $slug || str_ends_with($path, '/' . $slug);
    }

    private function request_path(): string
    {
        if ($this->requestPathCache !== null) {
            return $this->requestPathCache;
        }

        $this->requestPathCache = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');

        return $this->requestPathCache;
    }

    private function repo(): CMS_M365Azure_Repository
    {
        if (class_exists('CMS_M365Azure_Installer')) {
            CMS_M365Azure_Installer::maybe_install();
        }

        return CMS_M365Azure_Repository::instance();
    }

    private function set_seo(string $title, string $description): void
    {
        if (!class_exists('CMS\\Services\\SEOService')) {
            return;
        }

        try {
            $seo = \CMS\Services\SEOService::instance();
            $seo->setTitle($title);
            $seo->setDescription($description);
        } catch (\Throwable $e) {
            // SEO darf die öffentliche Seite nicht blockieren.
        }
    }
}
