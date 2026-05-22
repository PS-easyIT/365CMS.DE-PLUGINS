<?php
/**
 * Plugin Name: CMS Feed
 * Plugin URI: https://365network.de/cms-feed
 * Description: RSS-Feed-Aggregator mit Kategorie-Bereichen, Public Pages, Design-Einstellungen, Member-Feed-Abos und E-Mail-Digest
 * Version: 3.0.1
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Feed
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_FEED_VERSION',    '3.0.1');
define('CMS_FEED_PLUGIN_DIR', dirname(__FILE__) . '/');
define('CMS_FEED_PLUGIN_URL', '/plugins/cms-feed/');

if (!function_exists('cms_feed_strlen')) {
    function cms_feed_strlen(string $text): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($text, 'UTF-8');
        }

        if (preg_match_all('/./us', $text, $matches) !== false) {
            return count($matches[0]);
        }

        return strlen($text);
    }
}

if (!function_exists('cms_feed_substr')) {
    function cms_feed_substr(string $text, int $start, ?int $length = null): string
    {
        if (function_exists('mb_substr')) {
            return $length === null
                ? mb_substr($text, $start, null, 'UTF-8')
                : mb_substr($text, $start, $length, 'UTF-8');
        }

        if (preg_match_all('/./us', $text, $matches) !== false) {
            $chars = $matches[0];
            $slice = $length === null
                ? array_slice($chars, $start)
                : array_slice($chars, $start, $length);

            return implode('', $slice);
        }

        return $length === null ? substr($text, $start) : substr($text, $start, $length);
    }
}

final class CMS_Feed
{
    private static ?self $instance = null;
    private string $version = '3.0.1';
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
            try {
                CMS_Feed_Database::instance()->create_tables();
                CMS_Feed_Database::instance()->seed_defaults();
            } catch (\Throwable $e) {
                error_log('CMS Feed: Activation schema setup failed – ' . $e->getMessage());
            }
        }
    }

    public function init_plugin(): void
    {
        if (class_exists('CMS_Feed_Database')) {
            try {
                CMS_Feed_Database::instance()->ensure_schema();
            } catch (\Throwable $e) {
                error_log('CMS Feed: Schema setup during init failed – ' . $e->getMessage());
            }
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
                try {
                    $class::instance();
                } catch (\Throwable $e) {
                    error_log('CMS Feed: Initializing ' . $class . ' failed – ' . $e->getMessage());
                }
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
                echo '<link rel="stylesheet" href="' . htmlspecialchars($this->plugin_url . 'assets/css/feed-admin.css?v=' . filemtime($adminCss), ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
        } elseif ($isFeedRoute) {
            $css = $this->plugin_dir . 'assets/css/style.css';
            if (file_exists($css)) {
                echo '<link rel="stylesheet" href="' . htmlspecialchars($this->plugin_url . 'assets/css/style.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8') . '">' . "\n";
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
                echo '<script src="' . htmlspecialchars($this->plugin_url . 'assets/js/admin.js?v=' . filemtime($js), ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
            }
        } elseif ($isFeedRoute) {
            $js = $this->plugin_dir . 'assets/js/script.js';
            if (file_exists($js)) {
                echo '<script src="' . htmlspecialchars($this->plugin_url . 'assets/js/script.js?v=' . filemtime($js), ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
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
            . '--fd-primary:'       . htmlspecialchars($this->normalize_css_color((string) ($s['color_primary'] ?? ''), '#0891b2'), ENT_QUOTES, 'UTF-8') . ';'
            . '--fd-accent:'        . htmlspecialchars($this->normalize_css_color((string) ($s['color_accent'] ?? ''), '#e0f2fe'), ENT_QUOTES, 'UTF-8') . ';'
            . '--fd-hdr-from:'      . htmlspecialchars($this->normalize_css_color((string) ($s['color_hdr_from'] ?? ''), '#0c4a6e'), ENT_QUOTES, 'UTF-8') . ';'
            . '--fd-hdr-to:'        . htmlspecialchars($this->normalize_css_color((string) ($s['color_hdr_to'] ?? ''), '#0891b2'), ENT_QUOTES, 'UTF-8') . ';'
            . '--fd-hdr-title:'     . htmlspecialchars($this->normalize_css_color((string) ($s['color_hdr_title'] ?? ''), '#ffffff'), ENT_QUOTES, 'UTF-8') . ';'
            . '--fd-card-bg:'       . htmlspecialchars($this->normalize_css_color((string) ($s['color_card_bg'] ?? ''), '#ffffff'), ENT_QUOTES, 'UTF-8') . ';'
            . '--fd-card-border:'   . htmlspecialchars($this->normalize_css_color((string) ($s['color_card_border'] ?? ''), '#e2e8f0'), ENT_QUOTES, 'UTF-8') . ';'
            . '--fd-radius:'        . ((int)($s['border_radius'] ?? 10)) . 'px;'
            . '}</style>' . "\n";
    }

    private function normalize_css_color(string $value, string $fallback): string
    {
        $value = trim($value);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : $fallback;
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

        $archiveSlug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim((string) $archiveSlug, '/')));
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
