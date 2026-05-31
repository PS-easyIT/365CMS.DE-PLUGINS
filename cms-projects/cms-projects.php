<?php
/**
 * Plugin Name: CMS Projects
 * Plugin URI: https://365network.de/cms-projects
 * Description: Projektmanagement mit Projekt-Dashboards, Boards und Widgets für Member- und Public-Bereiche.
 * Version: 3.0.2
 * Author: 365 Network
 * Author URI: https://365network.de
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (defined('CMS_PROJECTS_BOOTSTRAPPED')) {
    return;
}

define('CMS_PROJECTS_BOOTSTRAPPED', true);
defined('CMS_PROJECTS_VERSION') || define('CMS_PROJECTS_VERSION', '3.0.2');
defined('CMS_PROJECTS_PLUGIN_DIR') || define('CMS_PROJECTS_PLUGIN_DIR', __DIR__ . DIRECTORY_SEPARATOR);
defined('CMS_PROJECTS_PLUGIN_URL') || define('CMS_PROJECTS_PLUGIN_URL', '/plugins/cms-projects/');

require_once CMS_PROJECTS_PLUGIN_DIR . 'includes/class-projects-repository.php';
require_once CMS_PROJECTS_PLUGIN_DIR . 'includes/class-projects-service.php';
require_once CMS_PROJECTS_PLUGIN_DIR . 'includes/class-projects-admin.php';
require_once CMS_PROJECTS_PLUGIN_DIR . 'includes/class-projects-public.php';
require_once CMS_PROJECTS_PLUGIN_DIR . 'includes/class-projects-member-dashboard.php';
require_once CMS_PROJECTS_PLUGIN_DIR . 'includes/class-projects-shortcode.php';

final class CMS_Projects
{
    private static ?self $instance = null;
    private CMS_Projects_Service $service;
    private CMS_Projects_Admin $admin;
    private CMS_Projects_Public $public;
    private CMS_Projects_Member_Dashboard $memberDashboard;
    private CMS_Projects_Shortcode $shortcode;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $repository = new CMS_Projects_Repository();
        $this->service = new CMS_Projects_Service($repository);
        $this->admin = new CMS_Projects_Admin($this->service);
        $this->public = new CMS_Projects_Public($this->service);
        $this->memberDashboard = new CMS_Projects_Member_Dashboard($this->service);
        $this->shortcode = new CMS_Projects_Shortcode($this->service);
        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            $this->service->boot();
            return;
        }

        \CMS\Hooks::addAction('cms_init', [$this, 'boot'], 10);
        \CMS\Hooks::addAction('plugin_activated', [$this, 'handleActivation'], 10);
        \CMS\Hooks::addAction('register_routes', [$this->public, 'registerRoutes'], 10);
        \CMS\Hooks::addAction('cms_admin_menu', [$this->admin, 'registerPages'], 20);
        \CMS\Hooks::addAction('admin_head', [$this->admin, 'enqueueStyles'], 10);
        \CMS\Hooks::addAction('head', [$this, 'enqueuePublicStyles'], 10);
    }

    public function boot(): void
    {
        $this->service->boot();
    }

    public function handleActivation(string $plugin): void
    {
        if ($plugin !== 'cms-projects') {
            return;
        }

        $this->service->boot();
    }

    public function enqueuePublicStyles(): void
    {
        if (!$this->shouldLoadPublicStyles()) {
            return;
        }

        $cssFile = CMS_PROJECTS_PLUGIN_DIR . 'assets/css/style.css';
        if (!is_file($cssFile)) {
            return;
        }

        echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_PROJECTS_PLUGIN_URL . 'assets/css/style.css', ENT_QUOTES, 'UTF-8')
            . '?v=' . (int) filemtime($cssFile) . '">' . "\n";
    }

    private function isPublicProjectsRoute(): bool
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        $path = '/' . trim($path, '/');

        return $path === '/projects' || str_starts_with($path, '/projects/');
    }

    private function shouldLoadPublicStyles(): bool
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'GET') {
            return false;
        }

        if (!$this->isPublicProjectsRoute()) {
            return false;
        }

        return CMS_Projects_Public::isRenderingPublicProjectsPage();
    }
}

CMS_Projects::instance();
