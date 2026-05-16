<?php
/**
 * CMS M365 Calculator – Frontend Controller.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Frontend
{
    private const TOOLBOX_ROUTE = '/m365-tools';
    private const TOOLBOX_ROUTE_ALIAS = '/m365-rechner';
    private const SHARED_MAILBOX_ROUTE = '/shared-mailbox-vs-lizenz';

    private static ?self $instance = null;

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
            \CMS\Hooks::addAction('body_end', [$this, 'enqueue_public_scripts'], 20);
        }
    }

    private function register_routes(): void
    {
        if (!class_exists('CMS\\Router')) {
            return;
        }

        $router = \CMS\Router::instance();

        $router->addRoute('GET', self::TOOLBOX_ROUTE, function (): void {
            $this->render_toolbox();
        });

        $router->addRoute('GET', self::TOOLBOX_ROUTE_ALIAS, function (): void {
            $this->render_toolbox();
        });

        $router->addRoute('GET', self::SHARED_MAILBOX_ROUTE, function (): void {
            $this->render_shared_mailbox('GET');
        });

        $router->addRoute('POST', self::SHARED_MAILBOX_ROUTE, function (): void {
            $this->render_shared_mailbox('POST');
        });
    }

    public function enqueue_public_styles(): void
    {
        if (!$this->is_calculator_request()) {
            return;
        }

        $cssFiles = ['plugin-base.css', 'style.css'];
        if (!$this->is_toolbox_request()) {
            $cssFiles[] = 'm365calculator-public.css';
        }

        foreach ($cssFiles as $cssFile) {
            $path = CMS_M365CALCULATOR_PLUGIN_DIR . 'assets/css/' . $cssFile;
            if (!file_exists($path)) {
                continue;
            }

            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365CALCULATOR_PLUGIN_URL . 'assets/css/' . $cssFile, ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($path) . '">' . "\n";
        }
    }

    public function enqueue_public_scripts(): void
    {
        if (!$this->is_calculator_request()) {
            return;
        }

        $js = CMS_M365CALCULATOR_PLUGIN_DIR . 'assets/js/m365calculator-public.js';
        if (!file_exists($js)) {
            return;
        }

        echo '<script src="'
            . htmlspecialchars(CMS_M365CALCULATOR_PLUGIN_URL . 'assets/js/m365calculator-public.js', ENT_QUOTES, 'UTF-8')
            . '?v=' . filemtime($js) . '" defer></script>' . "\n";
    }

    public function filter_body_class(mixed $bodyClass): string
    {
        $classes = trim((string) $bodyClass);
        if (!$this->is_calculator_request()) {
            return $classes;
        }

        $classList = preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($classList)) {
            $classList = [];
        }
        $classList[] = 'm365calculator-theme-embed';

        return implode(' ', array_values(array_unique($classList)));
    }

    private function render_toolbox(): void
    {
        $groupedTools = CMS_M365CALCULATOR_Tool_Registry::grouped_by_category();
        $this->set_seo('M365 Rechner', 'Übersicht verfügbarer Microsoft-365-Rechner und Berechnungstools.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/landing.php';
        exit;
    }

    private function render_shared_mailbox(string $method): void
    {
        $input = CMS_M365CALCULATOR_Shared_Mailbox_Calculator::default_input();
        $result = null;
        $error = '';
        $notice = '';
        $scenarios = CMS_M365CALCULATOR_Catalog::scenarios();
        $rules = CMS_M365CALCULATOR_Catalog::rules();
        $licenseMatrix = CMS_M365CALCULATOR_Catalog::license_matrix();
        $pricing = CMS_M365CALCULATOR_Catalog::pricing();

        if ($method === 'POST') {
            if (class_exists('CMS\\Security') && !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'm365calculator_shared_mailbox')) {
                $error = 'Sicherheitscheck fehlgeschlagen. Bitte die Seite neu laden.';
            } else {
                $input = CMS_M365CALCULATOR_Shared_Mailbox_Calculator::normalize_input($_POST);
                $result = CMS_M365CALCULATOR_Shared_Mailbox_Calculator::evaluate($input);
                $notice = 'Die Shared-Mailbox-Auswertung wurde erstellt.';
            }
        }

        $csrfToken = class_exists('CMS\\Security')
            ? \CMS\Security::instance()->generateToken('m365calculator_shared_mailbox')
            : bin2hex(random_bytes(16));

        $this->set_seo('Shared-Mailbox vs. Lizenz-Rechner', 'Prüft, ob eine Shared Mailbox ohne Lizenz reicht, eine Zusatzlizenz nötig ist oder eine User-Mailbox bzw. Microsoft 365 Group besser passt.');
        include CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-shared-mailbox.php';
        exit;
    }

    private function is_calculator_request(): bool
    {
        $requestPath = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');
        if ($requestPath === '') {
            return false;
        }

        return $requestPath === trim(self::TOOLBOX_ROUTE, '/')
            || $requestPath === trim(self::TOOLBOX_ROUTE_ALIAS, '/')
            || $requestPath === trim(self::SHARED_MAILBOX_ROUTE, '/')
            || str_ends_with($requestPath, self::TOOLBOX_ROUTE)
            || str_ends_with($requestPath, self::TOOLBOX_ROUTE_ALIAS)
            || str_ends_with($requestPath, self::SHARED_MAILBOX_ROUTE);
    }

    private function is_toolbox_request(): bool
    {
        $requestPath = trim((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH), '/');

        return $requestPath === trim(self::TOOLBOX_ROUTE, '/')
            || $requestPath === trim(self::TOOLBOX_ROUTE_ALIAS, '/')
            || str_ends_with($requestPath, self::TOOLBOX_ROUTE)
            || str_ends_with($requestPath, self::TOOLBOX_ROUTE_ALIAS);
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
            // SEO darf den Rechner nicht blockieren.
        }
    }
}
