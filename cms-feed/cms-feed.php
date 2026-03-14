<?php
/**
 * Plugin Name: CMS Feed
 * Plugin URI: https://365network.de/cms-feed
 * Description: RSS-Feed-Aggregator mit Kategorie-Bereichen, Public Pages, Design-Einstellungen, Member-Feed-Abos und E-Mail-Digest
 * Version: 1.3.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Feed
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_FEED_VERSION',    '1.3.0');
define('CMS_FEED_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_FEED_PLUGIN_URL', '/plugins/cms-feed/');

final class CMS_Feed
{
    private static ?self $instance = null;
    private string $version = '1.3.0';
    private string $plugin_dir;
    private string $plugin_url;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->plugin_dir = CMS_FEED_PLUGIN_DIR;
        $this->plugin_url = CMS_FEED_PLUGIN_URL;
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies(): void
    {
        $includes = $this->plugin_dir . 'includes/';
        $files = [
            'class-database.php',
            'class-rss-fetcher.php',
            'class-feed-catalog.php',
            'class-feed-cron.php',
            'class-template-loader.php',
            'class-public-controller.php',
            'class-email-digest.php',
            'class-admin.php',
        ];
        foreach ($files as $file) {
            if (file_exists($includes . $file)) {
                require_once $includes . $file;
            }
        }
    }

    private function init_hooks(): void
    {
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
            CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
            CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);
            CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 10);
            CMS\Hooks::addAction('admin_head', [$this, 'enqueue_styles'], 10);
            CMS\Hooks::addAction('body_end', [$this, 'enqueue_scripts'], 10);
        }
    }

    /**
     * Alle Plugin-Routen am Router registrieren.
     */
    public function register_routes($router): void
    {
        // Admin-Routen
        $admin = CMS_Feed_Admin::instance();
        $router->addRoute('GET',  '/admin/feeds', [$admin, 'admin_page']);
        $router->addRoute('POST', '/admin/feeds', [$admin, 'admin_page']);

        // Public-Routen
        $public = CMS_Feed_Public_Controller::instance();
        $public->register_routes($router);
    }

    public function on_activation(string $plugin): void
    {
        if ($plugin === 'cms-feed' && class_exists('CMS_Feed_Database')) {
            CMS_Feed_Database::instance()->create_tables();
            CMS_Feed_Database::instance()->seed_defaults();
        }
    }

    public function init_plugin(): void
    {
        if (class_exists('CMS_Feed_Database')) {
            CMS_Feed_Database::instance()->ensure_schema();
        }

        $classes = [
            'CMS_Feed_Database',
            'CMS_Feed_RSS_Fetcher',
            'CMS_Feed_Template_Loader',
            'CMS_Feed_Public_Controller',
            'CMS_Feed_Cron',
            'CMS_Feed_Email_Digest',
            'CMS_Feed_Admin',
        ];
        foreach ($classes as $class) {
            if (class_exists($class)) {
                $class::instance();
            }
        }
    }

    public function enqueue_styles(): void
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $isAdmin     = str_starts_with($currentPath, '/admin/feeds')
            || str_starts_with($currentPath, '/admin/plugins/feeds');
        $isFeedRoute = $this->is_feed_public_route($currentPath);

        if ($isAdmin) {
            $adminCss = $this->plugin_dir . 'assets/css/feed-admin.css';
            if (file_exists($adminCss)) {
                echo '<link rel="stylesheet" href="' . $this->plugin_url . 'assets/css/feed-admin.css?v=' . filemtime($adminCss) . '">' . "\n";
            }
        } elseif ($isFeedRoute) {
            $css = $this->plugin_dir . 'assets/css/style.css';
            if (file_exists($css)) {
                echo '<link rel="stylesheet" href="' . $this->plugin_url . 'assets/css/style.css?v=' . filemtime($css) . '">' . "\n";
            }
            // Design-Tokens als CSS Custom Properties injizieren
            $this->inject_design_tokens();
        }
    }

    public function enqueue_scripts(): void
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $isAdmin     = str_starts_with($currentPath, '/admin/feeds')
            || str_starts_with($currentPath, '/admin/plugins/feeds');
        $isFeedRoute = $this->is_feed_public_route($currentPath);

        if ($isAdmin) {
            $js = $this->plugin_dir . 'assets/js/admin.js';
            if (file_exists($js)) {
                echo '<script src="' . $this->plugin_url . 'assets/js/admin.js?v=' . filemtime($js) . '" defer></script>' . "\n";
            }
        } elseif ($isFeedRoute && $this->has_public_feed_consent()) {
            $js = $this->plugin_dir . 'assets/js/script.js';
            if (file_exists($js)) {
                echo '<script src="' . $this->plugin_url . 'assets/js/script.js?v=' . filemtime($js) . '" defer></script>' . "\n";
            }
        }
    }

    /**
     * Design-Tokens als :root CSS Custom Properties für Public-Seiten injizieren.
     */
    private function inject_design_tokens(): void
    {
        if (!class_exists('CMS_Feed_Database')) return;
        $s = CMS_Feed_Database::instance()->get_settings();

        echo '<style>:root{'
            . '--fd-primary:'       . htmlspecialchars($s['color_primary']     ?? '#0891b2') . ';'
            . '--fd-accent:'        . htmlspecialchars($s['color_accent']      ?? '#e0f2fe') . ';'
            . '--fd-hdr-from:'      . htmlspecialchars($s['color_hdr_from']    ?? '#0c4a6e') . ';'
            . '--fd-hdr-to:'        . htmlspecialchars($s['color_hdr_to']      ?? '#0891b2') . ';'
            . '--fd-hdr-title:'     . htmlspecialchars($s['color_hdr_title']   ?? '#ffffff') . ';'
            . '--fd-card-bg:'       . htmlspecialchars($s['color_card_bg']     ?? '#ffffff') . ';'
            . '--fd-card-border:'   . htmlspecialchars($s['color_card_border'] ?? '#e2e8f0') . ';'
            . '--fd-radius:'        . ((int)($s['border_radius'] ?? 10)) . 'px;'
            . '}</style>' . "\n";
    }

    private function is_feed_public_route(string $currentPath): bool
    {
        if ($currentPath === '') {
            return false;
        }

        $archiveSlug = 'feeds';
        if (class_exists('CMS_Feed_Database')) {
            $archiveSlug = CMS_Feed_Database::instance()->get_setting('archive_slug', 'feeds') ?: 'feeds';
        }

        $archiveSlug = trim($archiveSlug, '/');
        if ($archiveSlug === '' || $archiveSlug === 'feed') {
            $archiveSlug = 'feeds';
        }

        $archivePath = '/' . ltrim($archiveSlug, '/');

        if (str_starts_with($currentPath, '/feed/')) {
            return true;
        }

        return $currentPath === $archivePath || str_starts_with($currentPath, $archivePath . '/');
    }

    public function has_public_feed_consent(): bool
    {
        if (!class_exists('\\CMS\\Services\\CookieConsentService')) {
            return true;
        }

        return \CMS\Services\CookieConsentService::getInstance()->hasConsentForService('cms_feed', 'external_media', true);
    }

    public function get_version(): string
    {
        return $this->version;
    }

    public function get_plugin_dir(): string
    {
        return $this->plugin_dir;
    }

    public function get_plugin_url(): string
    {
        return $this->plugin_url;
    }
}

CMS_Feed::instance();
