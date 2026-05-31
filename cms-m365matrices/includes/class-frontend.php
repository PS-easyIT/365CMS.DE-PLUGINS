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
    private const COPILOT_ROUTE = '/m365-copilot-matrix';

    private static ?self $instance = null;
    private ?string $requestPathCache = null;
    private ?string $siteBasePathCache = null;
    private ?string $activeRouteCache = null;
    private ?string $currentLangCache = null;

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
        $this->register_public_route($router, self::SUITE_ROUTE, function (): void {
            $this->render_suite_matrix();
        });
        $this->register_public_route($router, self::ADDON_ROUTE, function (): void {
            $this->render_addon_matrix();
        });
        $this->register_public_route($router, self::COPILOT_ROUTE, function (): void {
            $this->render_copilot_matrix();
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

        $isCopilotRoute = $this->active_matrix_route() === self::COPILOT_ROUTE;
        $options = class_exists('CMS_M365MATRICES_Settings')
            ? CMS_M365MATRICES_Settings::global_options('matrix-design')
            : [];
        if (class_exists('CMS_M365MATRICES_Settings') && $isCopilotRoute) {
            $options = array_merge($options, CMS_M365MATRICES_Settings::global_options('matrix-copilot'));
        }

        $color = static function (array $values, string $key, string $default): string {
            $value = (string) ($values[$key] ?? $default);

            return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? strtolower($value) : $default;
        };
        $number = static fn(array $values, string $key, int $default, int $min, int $max): int => max($min, min($max, (int) ($values[$key] ?? $default)));

        $pageBackground = $color($options, 'matrix_color_page_background', '#edf1f6');
        if ($isCopilotRoute) {
            $pageBackground = $color($options, 'matrix_copilot_color_page_background', $pageBackground);
        }

        $surfaceBackground = $isCopilotRoute
            ? $color($options, 'matrix_copilot_color_surface_background', $color($options, 'matrix_color_surface_background', '#f8fafc'))
            : $color($options, 'matrix_color_surface_background', '#f8fafc');
        $textColor = $isCopilotRoute
            ? $color($options, 'matrix_copilot_color_text', $color($options, 'matrix_color_text', '#1e293b'))
            : $color($options, 'matrix_color_text', '#1e293b');
        $mutedColor = $isCopilotRoute
            ? $color($options, 'matrix_copilot_color_muted', $color($options, 'matrix_color_muted', '#64748b'))
            : $color($options, 'matrix_color_muted', '#64748b');
        $borderColor = $isCopilotRoute
            ? $color($options, 'matrix_copilot_color_header_border', $color($options, 'matrix_color_header_border', '#e2e8f0'))
            : $color($options, 'matrix_color_header_border', '#e2e8f0');
        $primaryButtonBackground = $isCopilotRoute
            ? $color($options, 'matrix_copilot_color_primary_button_bg', $color($options, 'matrix_color_primary_button_bg', '#2563eb'))
            : $color($options, 'matrix_color_primary_button_bg', '#2563eb');
        $primaryButtonText = $isCopilotRoute
            ? $color($options, 'matrix_copilot_color_primary_button_text', $color($options, 'matrix_color_primary_button_text', '#ffffff'))
            : $color($options, 'matrix_color_primary_button_text', '#ffffff');
        $secondaryButtonBackground = $isCopilotRoute
            ? $color($options, 'matrix_copilot_color_secondary_button_bg', $color($options, 'matrix_color_secondary_button_bg', '#ffffff'))
            : $color($options, 'matrix_color_secondary_button_bg', '#ffffff');
        $secondaryButtonText = $isCopilotRoute
            ? $color($options, 'matrix_copilot_color_secondary_button_text', $color($options, 'matrix_color_secondary_button_text', '#1e293b'))
            : $color($options, 'matrix_color_secondary_button_text', '#1e293b');
        $headerRadius = $isCopilotRoute
            ? $number($options, 'matrix_copilot_header_radius', $number($options, 'matrix_header_radius', 2, 0, 2), 0, 2)
            : $number($options, 'matrix_header_radius', 2, 0, 2);

        $vars = [
            '--m365tools-card-radius' => $headerRadius . 'px',
            '--m365tools-ui-radius' => $headerRadius . 'px',
            '--m365tools-section-gap' => $number($options, 'matrix_section_gap', 32, 12, 96) . 'px',
            '--m365tools-primary' => $primaryButtonBackground,
            '--m365tools-accent' => $primaryButtonBackground,
            '--m365tools-bg' => $pageBackground,
            '--m365tools-surface' => $surfaceBackground,
            '--m365tools-surface-alt' => $surfaceBackground,
            '--m365tools-header-bg' => $surfaceBackground,
            '--m365tools-header-text' => $textColor,
            '--m365tools-header-muted' => $mutedColor,
            '--m365tools-header-border' => $borderColor,
            '--m365tools-button-primary-bg' => $primaryButtonBackground,
            '--m365tools-button-primary-text' => $primaryButtonText,
            '--m365tools-button-secondary-bg' => $secondaryButtonBackground,
            '--m365tools-button-secondary-text' => $secondaryButtonText,
            '--m365tools-text' => $textColor,
            '--m365tools-muted' => $mutedColor,
            '--m365tools-border' => $borderColor,
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
        $classes[] = $this->current_module_body_class();

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
        $template = CMS_M365MATRICES_Source::template_path('page-readonly-suite-matrix.php');
        if ($template === '' || !is_readable($template)) {
            error_log('CMS M365 Matrixen template missing: page-readonly-suite-matrix.php');
            $this->render_missing_dependency('Microsoft 365 Lizenzmatrix');
        }

        include $template;
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
        $template = CMS_M365MATRICES_Source::template_path('page-readonly-addon-matrix.php');
        if ($template === '' || !is_readable($template)) {
            error_log('CMS M365 Matrixen template missing: page-readonly-addon-matrix.php');
            $this->render_missing_dependency('Microsoft 365 Add-on-Matrix');
        }

        include $template;
        exit;
    }

    private function render_copilot_matrix(): void
    {
        if (!CMS_M365MATRICES_Source::load_runtime()) {
            $this->render_missing_dependency('Microsoft Copilot Lizenzmatrix');
        }

        $matrix = CMS_M365MATRICES_ReadOnly_Matrices::copilot_matrix();
        $seoOptions = class_exists('CMS_M365MATRICES_Settings') ? CMS_M365MATRICES_Settings::global_options('matrix-copilot') : [];
        $seoTitle = self::public_text($seoOptions, 'matrix_copilot_title', 'Microsoft Copilot Lizenzmatrix');
        $seoDescription = self::public_text($seoOptions, 'matrix_copilot_intro', 'Umfangreiche Vergleichsmatrix für Microsoft Copilot, Microsoft 365 Copilot Chat, Microsoft 365 Copilot und Copilot Studio.');
        $this->set_seo($seoTitle, $seoDescription);
        $template = CMS_M365MATRICES_Source::template_path('page-readonly-copilot-matrix.php');
        if ($template === '' || !is_readable($template)) {
            error_log('CMS M365 Matrixen template missing: page-readonly-copilot-matrix.php');
            $this->render_missing_dependency('Microsoft Copilot Lizenzmatrix');
        }

        include $template;
        exit;
    }

    /**
     * @param array<string,string> $options
     */
    private static function public_text(array $options, string $key, string $default): string
    {
        $lang = self::current_lang();
        $value = function_exists('cms_plugin_public_i18n_value')
            ? (string) cms_plugin_public_i18n_value($options, $key, $lang, $default)
            : (string) ($options[$key] ?? $default);
        $value = trim(strip_tags($value));

        return $value !== '' ? $value : $default;
    }

    private function render_missing_dependency(string $title): void
    {
        $lang = self::current_lang();
        $message = $lang === 'en'
            ? 'The matrix data source is not available. Please verify that local matrix files exist in this plugin.'
            : 'Die Matrix-Datenquelle ist nicht verfügbar. Bitte prüfen, ob die lokalen Matrix-Dateien im Plugin vorhanden sind.';
        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => $title]);
        }

        echo '<main class="phinit-plugin m365calc-page"><section class="phinit-note phinit-note--warning">';
        echo '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        echo '</section></main>';

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }

        exit;
    }

    private function is_matrix_request(): bool
    {
        return $this->active_matrix_route() !== null;
    }

    private function current_module_body_class(): string
    {
        $activeRoute = $this->active_matrix_route();
        if ($activeRoute === self::ADDON_ROUTE) {
            return 'm365tools-module-m365-addon-matrix';
        }

        if ($activeRoute === self::COPILOT_ROUTE) {
            return 'm365tools-module-m365-copilot-matrix';
        }

        return 'm365tools-module-m365-lizenzmatrix';
    }

    private function path_matches(string $route): bool
    {
        $routePath = trim($route, '/');
        if ($routePath === '') {
            return false;
        }

        if (function_exists('cms_plugin_public_path_without_lang')) {
            return cms_plugin_public_path_without_lang($this->normalized_request_path()) === $routePath;
        }

        $path = $this->normalized_request_path();
        if ($path === $routePath || $path === 'en/' . $routePath) {
            return true;
        }

        $basePath = $this->normalized_site_base_path();
        if ($basePath === '') {
            return false;
        }

        return $path === $basePath . '/' . $routePath;
    }

    private function active_matrix_route(): ?string
    {
        if ($this->activeRouteCache !== null) {
            return $this->activeRouteCache !== '' ? $this->activeRouteCache : null;
        }

        foreach ([self::SUITE_ROUTE, self::ADDON_ROUTE, self::COPILOT_ROUTE] as $route) {
            if ($this->path_matches($route)) {
                $this->activeRouteCache = $route;
                return $route;
            }
        }

        $this->activeRouteCache = '';
        return null;
    }

    private function normalized_request_path(): string
    {
        if ($this->requestPathCache !== null) {
            return $this->requestPathCache;
        }

        if (function_exists('cms_plugin_public_request_path')) {
            $this->requestPathCache = (string) cms_plugin_public_request_path();

            return $this->requestPathCache;
        }

        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($requestUri, PHP_URL_PATH);
        $this->requestPathCache = trim(is_string($path) ? $path : '/', '/');

        return $this->requestPathCache;
    }

    private function normalized_site_base_path(): string
    {
        if ($this->siteBasePathCache !== null) {
            return $this->siteBasePathCache;
        }

        $siteUrl = defined('SITE_URL') ? trim((string) SITE_URL) : '';
        $basePath = trim((string) (parse_url($siteUrl, PHP_URL_PATH) ?: ''), '/');
        $this->siteBasePathCache = $basePath;

        return $this->siteBasePathCache;
    }

    public static function current_lang(): string
    {
        $instance = self::instance();
        if ($instance->currentLangCache !== null) {
            return $instance->currentLangCache;
        }

        if (function_exists('cms_plugin_public_language')) {
            $instance->currentLangCache = cms_plugin_public_language();
            return $instance->currentLangCache;
        }

        $instance->currentLangCache = 'de';
        return $instance->currentLangCache;
    }

    private function register_public_route(\CMS\Router $router, string $route, callable $handler): void
    {
        $paths = [$route, '/en' . $route];
        if (function_exists('cms_plugin_public_localized_path')) {
            $paths[] = cms_plugin_public_localized_path($route, 'de');
            $paths[] = cms_plugin_public_localized_path($route, 'en');
        }

        foreach (array_values(array_unique(array_filter($paths, static fn(string $path): bool => $path !== ''))) as $path) {
            $router->addRoute('GET', $path, $handler);
        }
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