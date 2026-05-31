<?php
/**
 * CMS M365 Landing – Public frontend.
 *
 * @package CMS_M365Landing
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Landing_Frontend
{
    private static ?self $instance = null;
    private ?string $requestPathCache = null;
    private ?bool $domainLandingRequestCache = null;

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

        \CMS\Router::instance()->addRoute('GET', '/' . $this->route_slug(), function (): void {
            $this->render_landing();
        });

        if ($this->is_domain_landing_request()) {
            \CMS\Router::instance()->addRoute('GET', '/', function (): void {
                $this->render_landing();
            });
        }
    }

    public function enqueue_styles(): void
    {
        if (!$this->is_request()) {
            return;
        }

        foreach ($this->m365_style_assets() as $asset) {
            if (!file_exists($asset['path'])) {
                continue;
            }

            echo '<link rel="stylesheet" href="'
                . htmlspecialchars($asset['url'], ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($asset['path']) . '">' . "\n";
        }

        $css = CMS_M365LANDING_PLUGIN_DIR . 'assets/css/style.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365LANDING_PLUGIN_URL . 'assets/css/style.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public function output_design_tokens(): void
    {
        if (!$this->is_request()) {
            return;
        }

        $settings = $this->repo()->settings();
        $primary = CMS_M365Landing_Repository::color((string) ($settings['design_primary_color'] ?? ''), '#2563eb');
        $accent = CMS_M365Landing_Repository::color((string) ($settings['design_accent_color'] ?? ''), '#0f766e');
        $background = CMS_M365Landing_Repository::color((string) ($settings['design_background_color'] ?? ''), '#edf1f6');
        $surface = CMS_M365Landing_Repository::color((string) ($settings['design_surface_color'] ?? ''), '#ffffff');
        $surfaceAlt = CMS_M365Landing_Repository::color((string) ($settings['design_surface_alt_color'] ?? ''), '#f8fafc');
        $textColor = CMS_M365Landing_Repository::color((string) ($settings['design_text_color'] ?? ''), '#1e293b');
        $mutedColor = CMS_M365Landing_Repository::color((string) ($settings['design_muted_color'] ?? ''), '#64748b');
        $borderColor = CMS_M365Landing_Repository::color((string) ($settings['design_border_color'] ?? ''), '#e2e8f0');
        $number = static function (string $key, int $default, int $min, int $max) use ($settings): int {
            $rawValue = trim((string) ($settings[$key] ?? ''));
            $value = $rawValue !== '' ? (int) $rawValue : $default;

            return max($min, min($max, $value));
        };
        $radius = $number('design_border_radius', 10, 0, 32);
        $maxWidth = $number('layout_max_width', 1180, 720, 1800);
        $paddingX = $number('layout_padding_x', 0, 0, 80);
        $paddingTop = $number('layout_padding_top', 25, 0, 120);
        $paddingBottom = $number('layout_padding_bottom', 64, 0, 160);
        $heroImageHeight = $number('hero_image_height', 150, 80, 320);
        $iconSize = $number('card_icon_size', 42, 24, 80);
        $imageHeight = $number('card_image_height', 205, 90, 205);

        echo '<style id="cms-m365landing-design">' . "\n";
        echo ':root {' . "\n";
        echo '    --m365landing-bg: ' . htmlspecialchars($background, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '}' . "\n";
        echo ':root, body.m365tools-theme-embed, body.m365calculator-theme-embed {' . "\n";
        echo '    --m365tools-card-radius: ' . (int) $radius . 'px;' . "\n";
        echo '    --m365tools-ui-radius: ' . (int) $radius . 'px;' . "\n";
        echo '    --m365tools-primary: ' . htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-accent: ' . htmlspecialchars($accent, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-bg: var(--m365landing-bg);' . "\n";
        echo '    --m365tools-surface: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-surface-alt: ' . htmlspecialchars($surfaceAlt, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-header-bg: ' . htmlspecialchars($surfaceAlt, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-header-text: ' . htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-header-muted: ' . htmlspecialchars($mutedColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-header-border: ' . htmlspecialchars($borderColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-button-primary-bg: ' . htmlspecialchars($primary, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-button-primary-text: #ffffff;' . "\n";
        echo '    --m365tools-button-secondary-bg: ' . htmlspecialchars($surface, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-button-secondary-text: ' . htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-text: ' . htmlspecialchars($textColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-muted: ' . htmlspecialchars($mutedColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365tools-border: ' . htmlspecialchars($borderColor, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        echo '    --m365landing-page-max-width: ' . (int) $maxWidth . 'px;' . "\n";
        echo '    --m365landing-padding-x: ' . (int) $paddingX . 'px;' . "\n";
        echo '    --m365landing-padding-top: ' . (int) $paddingTop . 'px;' . "\n";
        echo '    --m365landing-padding-bottom: ' . (int) $paddingBottom . 'px;' . "\n";
        echo '    --m365landing-hero-image-height: ' . (int) $heroImageHeight . 'px;' . "\n";
        echo '    --m365landing-icon-size: ' . (int) $iconSize . 'px;' . "\n";
        echo '    --m365landing-image-height: ' . (int) $imageHeight . 'px;' . "\n";
        echo '}' . "\n";
        echo 'body.m365tools-module-m365landing, body.m365tools-module-m365landing #page.site, body.m365tools-module-m365landing #content.site-content, body.m365tools-module-m365landing .site-content {' . "\n";
        echo '    --m365tools-bg: var(--m365landing-bg) !important;' . "\n";
        echo '    background-color: var(--m365landing-bg) !important;' . "\n";
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
        $classes[] = 'm365tools-module-m365landing';

        return implode(' ', array_values(array_unique($classes)));
    }

    private function render_landing(): void
    {
        $repo = $this->repo();
        $settings = $repo->settings();
        $cardsBySection = $repo->cards_by_section(true);

        $title = $this->setting($settings, 'seo_title', $this->setting($settings, 'page_title', 'Microsoft 365 Hub'));
        $description = $this->setting($settings, 'seo_description', $this->setting($settings, 'page_intro', 'Zentrale Übersicht für Microsoft 365 Inhalte und Tools.'));
        $this->set_seo($title, $description);

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => $title, 'description' => $description]);
        }

        include CMS_M365LANDING_PLUGIN_DIR . 'templates/landing.php';

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
            $slug = CMS_M365Landing_Repository::slug((string) ($settings['route_slug'] ?? 'm365'));
            return $slug !== '' ? $slug : 'm365';
        } catch (\Throwable $e) {
            return 'm365';
        }
    }

    private function is_request(): bool
    {
        $path = $this->request_path();
        $slug = $this->route_slug();

        return $path === $slug || str_ends_with($path, '/' . $slug) || ($path === '' && $this->is_domain_landing_request());
    }

    private function is_domain_landing_request(): bool
    {
        if ($this->domainLandingRequestCache !== null) {
            return $this->domainLandingRequestCache;
        }

        $settings = $this->repo()->settings();
        $host = CMS_M365Landing_Repository::normalize_host((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $mainHost = CMS_M365Landing_Repository::normalize_host((string) (parse_url((string) SITE_URL, PHP_URL_HOST) ?: ''));
        if ($host === '' || $host === $mainHost) {
            $this->domainLandingRequestCache = false;

            return $this->domainLandingRequestCache;
        }

        $domains = CMS_M365Landing_Repository::normalize_domain_list((string) ($settings['landing_domains'] ?? ''));
        $this->domainLandingRequestCache = in_array($host, $domains, true);

        return $this->domainLandingRequestCache;
    }

    private function request_path(): string
    {
        if ($this->requestPathCache !== null) {
            return $this->requestPathCache;
        }

        $this->requestPathCache = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');

        return $this->requestPathCache;
    }

    private function repo(): CMS_M365Landing_Repository
    {
        if (class_exists('CMS_M365Landing_Installer')) {
            CMS_M365Landing_Installer::maybe_install();
        }

        return CMS_M365Landing_Repository::instance();
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

    /** @return array<int,array{path:string,url:string}> */
    private function m365_style_assets(): array
    {
        $assets = [];
        $toolsDir = defined('CMS_M365TOOLS_PLUGIN_DIR')
            ? rtrim((string) CMS_M365TOOLS_PLUGIN_DIR, '/\\') . '/'
            : dirname(rtrim(CMS_M365LANDING_PLUGIN_DIR, '/\\')) . '/cms-m365tools/';
        $toolsUrl = defined('CMS_M365TOOLS_PLUGIN_URL')
            ? rtrim((string) CMS_M365TOOLS_PLUGIN_URL, '/') . '/'
            : '/plugins/cms-m365tools/';

        foreach (['plugin-base.css', 'style.css'] as $file) {
            $assets[] = [
                'path' => $toolsDir . 'assets/css/' . $file,
                'url' => $toolsUrl . 'assets/css/' . $file,
            ];
        }

        $matrixDir = defined('CMS_M365MATRICES_PLUGIN_DIR')
            ? rtrim((string) CMS_M365MATRICES_PLUGIN_DIR, '/\\') . '/'
            : dirname(rtrim(CMS_M365LANDING_PLUGIN_DIR, '/\\')) . '/cms-m365matrices/';
        $matrixUrl = defined('CMS_M365MATRICES_PLUGIN_URL')
            ? rtrim((string) CMS_M365MATRICES_PLUGIN_URL, '/') . '/'
            : '/plugins/cms-m365matrices/';

        foreach (['m365calculator-public.css'] as $file) {
            $assets[] = [
                'path' => $matrixDir . 'assets/css/' . $file,
                'url' => $matrixUrl . 'assets/css/' . $file,
            ];
        }

        return $assets;
    }
}
