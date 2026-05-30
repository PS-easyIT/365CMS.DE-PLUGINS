<?php
/**
 * CMS M365 Matrixen – Public Routes.
 *
 * @package CMS_M365MATRICES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365MATRICES_Frontend
{
    private const SUITE_ROUTE = '/m365-lizenzmatrix';
    private const ADDON_ROUTE = '/m365-addon-matrix';

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
            \CMS\Hooks::addAction('head', [$this, 'enqueue_public_styles'], 20);
            \CMS\Hooks::addAction('head', [$this, 'output_public_design_tokens'], 30);
            \CMS\Hooks::addAction('body_end', [$this, 'enqueue_public_scripts'], 20);
        }
    }

    private function register_routes(): void
    {
        if (!class_exists('CMS\\Router')) {
            return;
        }

        $router = \CMS\Router::instance();
        $router->addRoute('GET', self::SUITE_ROUTE, function (): void {
            $this->render_suite_matrix();
        });
        $router->addRoute('GET', self::ADDON_ROUTE, function (): void {
            $this->render_addon_matrix();
        });
    }

    public function enqueue_public_styles(): void
    {
        if (!$this->is_matrix_request()) {
            return;
        }

        foreach (['css/plugin-base.css', 'css/style.css', 'css/m365calculator-public.css'] as $asset) {
            $path = CMS_M365MATRICES_Source::asset_file($asset);
            if ($path === '' || !file_exists($path)) {
                continue;
            }

            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365MATRICES_Source::asset_url($asset), ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($path) . '">' . "\n";
        }
    }

    public function output_public_design_tokens(): void
    {
        if (!$this->is_matrix_request()) {
            return;
        }

        $options = class_exists('CMS_M365MATRICES_Settings')
            ? array_merge(
                CMS_M365MATRICES_Settings::global_options('landing'),
                CMS_M365MATRICES_Settings::global_options('landing-layout'),
                CMS_M365MATRICES_Settings::global_options('landing-colors')
            )
            : [];

        $color = static function (array $values, string $key, string $default): string {
            $value = (string) ($values[$key] ?? $default);

            return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
        };
        $number = static fn(array $values, string $key, int $default, int $min, int $max): int => max($min, min($max, (int) ($values[$key] ?? $default)));

        $vars = [
            '--m365tools-card-radius' => $number($options, 'landing_card_radius', 2, 0, 2) . 'px',
            '--m365tools-ui-radius' => $number($options, 'landing_card_radius', 2, 0, 2) . 'px',
            '--m365tools-card-min' => $number($options, 'landing_cards_min_width', 320, 220, 520) . 'px',
            '--m365tools-section-gap' => $number($options, 'landing_section_gap', 32, 16, 96) . 'px',
            '--m365tools-primary' => $color($options, 'landing_color_primary', '#2563eb'),
            '--m365tools-accent' => $color($options, 'landing_color_accent', '#0f766e'),
            '--m365tools-bg' => $color($options, 'landing_color_background', '#ffffff'),
            '--m365tools-surface' => $color($options, 'landing_color_surface', '#ffffff'),
            '--m365tools-surface-alt' => $color($options, 'landing_color_surface_alt', '#f8fafc'),
            '--m365tools-header-bg' => $color($options, 'landing_color_header_background', '#f8fafc'),
            '--m365tools-header-text' => $color($options, 'landing_color_header_text', '#1e293b'),
            '--m365tools-header-muted' => $color($options, 'landing_color_header_muted', '#64748b'),
            '--m365tools-header-border' => $color($options, 'landing_color_header_border', '#e2e8f0'),
            '--m365tools-button-primary-bg' => $color($options, 'landing_color_button_primary_bg', '#2563eb'),
            '--m365tools-button-primary-text' => $color($options, 'landing_color_button_primary_text', '#ffffff'),
            '--m365tools-button-secondary-bg' => $color($options, 'landing_color_button_secondary_bg', '#ffffff'),
            '--m365tools-button-secondary-text' => $color($options, 'landing_color_button_secondary_text', '#1e293b'),
            '--m365tools-text' => $color($options, 'landing_color_text', '#1e293b'),
            '--m365tools-muted' => $color($options, 'landing_color_muted', '#64748b'),
            '--m365tools-border' => $color($options, 'landing_color_border', '#e2e8f0'),
        ];

        echo '<style id="cms-m365matrices-public-design">' . "\n";
        echo ':root, body.m365tools-theme-embed, body.m365calculator-theme-embed {' . "\n";
        foreach ($vars as $name => $value) {
            echo '    ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . ';' . "\n";
        }
        echo '}' . "\n";
        echo '</style>' . "\n";
    }

    public function enqueue_public_scripts(): void
    {
        if (!$this->is_matrix_request()) {
            return;
        }

        $path = CMS_M365MATRICES_Source::asset_file('js/m365calculator-public.js');
        if ($path === '' || !file_exists($path)) {
            return;
        }

        echo '<script src="'
            . htmlspecialchars(CMS_M365MATRICES_Source::asset_url('js/m365calculator-public.js'), ENT_QUOTES, 'UTF-8')
            . '?v=' . filemtime($path) . '" defer></script>' . "\n";
    }

    public function filter_body_class(mixed $bodyClass): string
    {
        $classes = preg_split('/\s+/', trim((string) $bodyClass), -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($classes)) {
            $classes = [];
        }

        if (!$this->is_matrix_request()) {
            return implode(' ', $classes);
        }

        $classes[] = 'm365tools-theme-embed';
        $classes[] = 'm365calculator-theme-embed';
        $classes[] = $this->path_matches(self::ADDON_ROUTE) ? 'm365tools-module-m365-addon-matrix' : 'm365tools-module-m365-lizenzmatrix';

        return implode(' ', array_values(array_unique($classes)));
    }

    private function render_suite_matrix(): void
    {
        if (!CMS_M365MATRICES_Source::load_runtime()) {
            $this->render_missing_dependency('Microsoft 365 Lizenzmatrix');
        }

        $matrix = CMS_M365MATRICES_ReadOnly_Matrices::suite_matrix();
        $seoOptions = class_exists('CMS_M365MATRICES_Settings') ? CMS_M365MATRICES_Settings::global_options('matrix-suite') : [];
        $seoTitle = self::public_text($seoOptions, 'matrix_suite_title', 'M365 Lizenzmatrix');
        $seoDescription = self::public_text($seoOptions, 'matrix_suite_intro', 'Gesamtübersicht der Microsoft-365-Vollpakete von Business Basic, Standard und Premium bis Microsoft 365 E3 und E5.');
        $this->set_seo($seoTitle, $seoDescription);
        include CMS_M365MATRICES_Source::template_path('page-readonly-suite-matrix.php');
        exit;
    }

    private function render_addon_matrix(): void
    {
        if (!CMS_M365MATRICES_Source::load_runtime()) {
            $this->render_missing_dependency('Microsoft 365 Add-on-Matrix');
        }

        $matrix = CMS_M365MATRICES_ReadOnly_Matrices::addon_matrix();
        $seoOptions = class_exists('CMS_M365MATRICES_Settings') ? CMS_M365MATRICES_Settings::global_options('matrix-addon') : [];
        $seoTitle = self::public_text($seoOptions, 'matrix_addon_title', 'M365 Add-on-Matrix');
        $seoDescription = self::public_text($seoOptions, 'matrix_addon_intro', 'Gesamtübersicht der Microsoft-365-Add-ons nach Exchange, SharePoint, OneDrive, Teams, Copilot, Intune, Entra ID, Defender, Purview und Power Platform.');
        $this->set_seo($seoTitle, $seoDescription);
        include CMS_M365MATRICES_Source::template_path('page-readonly-addon-matrix.php');
        exit;
    }

    /**
     * @param array<string,string> $options
     */
    private static function public_text(array $options, string $key, string $default): string
    {
        $value = trim(strip_tags((string) ($options[$key] ?? '')));

        return $value !== '' ? $value : $default;
    }

    private function render_missing_dependency(string $title): void
    {
        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => $title]);
        }

        echo '<main class="phinit-plugin m365calc-page"><section class="phinit-note phinit-note--warning">';
        echo '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '<p>Die Matrix-Datenquelle ist nicht verfügbar. Bitte prüfen, ob die lokalen Matrix-Dateien im Plugin vorhanden sind.</p>';
        echo '</section></main>';

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }

        exit;
    }

    private function is_matrix_request(): bool
    {
        return $this->path_matches(self::SUITE_ROUTE) || $this->path_matches(self::ADDON_ROUTE);
    }

    private function path_matches(string $route): bool
    {
        $path = $this->normalized_request_path();
        $routePath = trim($route, '/');

        return $path === $routePath || str_ends_with($path, '/' . $routePath);
    }

    private function normalized_request_path(): string
    {
        if ($this->requestPathCache !== null) {
            return $this->requestPathCache;
        }

        $this->requestPathCache = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');

        return $this->requestPathCache;
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
            // SEO darf die Matrix nicht blockieren.
        }
    }
}