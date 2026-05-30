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
            \CMS\Hooks::addFilter('body_class', [$this, 'filter_body_class'], 20);
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

        foreach ($this->matrix_style_assets() as $asset) {
            if (!file_exists($asset['path'])) {
                continue;
            }

            echo '<link rel="stylesheet" href="'
                . htmlspecialchars($asset['url'], ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($asset['path']) . '">' . "\n";
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
        $textColor = CMS_M365Azure_Repository::color((string) ($settings['design_text_color'] ?? ''), '#1e293b');
        $mutedColor = CMS_M365Azure_Repository::color((string) ($settings['design_muted_color'] ?? ''), '#64748b');
        $borderColor = CMS_M365Azure_Repository::color((string) ($settings['design_border_color'] ?? ''), '#e2e8f0');
        $radius = max(0, min(32, (int) ($settings['design_border_radius'] ?? 10)));
        $maxWidth = max(720, min(1800, (int) ($settings['layout_max_width'] ?? 1180)));
        $paddingX = max(0, min(80, (int) ($settings['layout_padding_x'] ?? 0)));
        $paddingTop = max(0, min(120, (int) ($settings['layout_padding_top'] ?? 25)));
        $tocFontSize = max(10, min(18, (int) ($settings['design_toc_font_size'] ?? 13)));
        $tableFontSize = max(11, min(18, (int) ($settings['design_table_font_size'] ?? 14)));
        $linkFontSize = max(10, min(16, (int) ($settings['design_link_font_size'] ?? 12)));
        $imageWidthRaw = (int) ($settings['card_image_width'] ?? 72);
        $imageWidth = $imageWidthRaw > 160 ? 72 : max(40, min(160, $imageWidthRaw));

        echo '<style id="cms-m365azure-design">' . "\n";
        echo ':root, body.m365tools-theme-embed, body.m365calculator-theme-embed {' . "\n";
        echo '    --m365tools-card-radius: ' . (int) $radius . 'px;' . "\n";
        echo '    --m365tools-ui-radius: ' . (int) $radius . 'px;' . "\n";
        echo '    --m365tools-primary: ' . htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-accent: ' . htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-bg: ' . htmlspecialchars($background, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-surface: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-surface-alt: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-text: ' . htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-muted: ' . htmlspecialchars($mutedColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-border: ' . htmlspecialchars($borderColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-header-bg: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-button-primary-bg: ' . htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-button-primary-text: #ffffff;' . "\n";
        echo '    --m365tools-button-secondary-bg: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365matrix-page-max-width: ' . (int) $maxWidth . 'px;' . "\n";
        echo '    --m365matrix-padding-x: ' . (int) $paddingX . 'px;' . "\n";
        echo '    --m365matrix-padding-top: ' . (int) $paddingTop . 'px;' . "\n";
        echo '    --m365matrix-page-bg: ' . htmlspecialchars($background, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365matrix-surface-bg: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365matrix-header-bg: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365matrix-header-border: ' . htmlspecialchars($borderColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365matrix-muted: ' . htmlspecialchars($mutedColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365matrix-header-radius: ' . (int) $radius . 'px;' . "\n";
        echo '    --m365matrix-primary-button-bg: ' . htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365matrix-primary-button-text: #ffffff;' . "\n";
        echo '    --m365matrix-secondary-button-bg: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365matrix-toc-font-size: ' . (int) $tocFontSize . 'px;' . "\n";
        echo '    --azs-service-icon-width: ' . (int) $imageWidth . 'px;' . "\n";
        echo '    --azs-table-font-size: ' . (int) $tableFontSize . 'px;' . "\n";
        echo '    --azs-link-font-size: ' . (int) $linkFontSize . 'px;' . "\n";
        echo '}' . "\n";
        echo '</style>' . "\n";
    }

    public function filter_body_class(mixed $bodyClass): string
    {
        $classes = preg_split('/\s+/', trim((string) $bodyClass), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($classes)) {
            $classes = [];
        }

        if (!$this->is_request()) {
            return implode(' ', $classes);
        }

        $classes[] = 'm365tools-theme-embed';
        $classes[] = 'm365calculator-theme-embed';
        $classes[] = 'm365tools-module-m365azure';

        return implode(' ', array_values(array_unique($classes)));
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

    /**
     * @return array<int,array{path:string,url:string}>
     */
    private function matrix_style_assets(): array
    {
        $matrixDir = defined('CMS_M365MATRICES_PLUGIN_DIR')
            ? rtrim((string) CMS_M365MATRICES_PLUGIN_DIR, '/\\') . '/'
            : dirname(rtrim(CMS_M365AZURE_PLUGIN_DIR, '/\\')) . '/cms-m365matrices/';
        $matrixUrl = defined('CMS_M365MATRICES_PLUGIN_URL')
            ? rtrim((string) CMS_M365MATRICES_PLUGIN_URL, '/') . '/'
            : '/plugins/cms-m365matrices/';

        $assets = [];
        foreach (['plugin-base.css', 'style.css', 'm365calculator-public.css'] as $file) {
            $assets[] = [
                'path' => $matrixDir . 'assets/css/' . $file,
                'url' => $matrixUrl . 'assets/css/' . $file,
            ];
        }

        return $assets;
    }
}
