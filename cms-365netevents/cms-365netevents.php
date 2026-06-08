<?php
/**
 * Plugin Name: 365 | Events & Speaker
 * Plugin URI: https://365network.de/cms-365netevents
 * Description: Modulares Event- und Speaker-Verzeichnis für 365CMS mit Seed-Daten, Admin-CRUD und Public-Views.
 * Version: 3.0.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_365NETEvents
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

defined('CMS_365NET_EVENTS_VERSION') || define('CMS_365NET_EVENTS_VERSION', '3.0.0');
defined('CMS_365NET_EVENTS_PLUGIN_DIR') || define('CMS_365NET_EVENTS_PLUGIN_DIR', dirname(__FILE__) . '/');
defined('CMS_365NET_EVENTS_PLUGIN_URL') || define('CMS_365NET_EVENTS_PLUGIN_URL', '/plugins/cms-365NETevents/');
defined('CMS_365NET_EVENTS_TEXT_DOMAIN') || define('CMS_365NET_EVENTS_TEXT_DOMAIN', 'cms-365netevents');

if (!class_exists('CMS_365NET_Events', false)) {
    /**
     * Bootstrap-Klasse des 365NET Event/Speaker-Plugins.
     *
     * Die Klasse lädt Abhängigkeiten, registriert CMS-Hooks und hält die
     * Initialisierung bewusst schlank. Fachlogik befindet sich in den
     * jeweiligen Include-Klassen.
     */
    final class CMS_365NET_Events
    {
        private static ?self $instance = null;
        private bool $componentsBootstrapped = false;
        private ?string $requestPathCache = null;

        private string $version = CMS_365NET_EVENTS_VERSION;
        private string $pluginDir = CMS_365NET_EVENTS_PLUGIN_DIR;
        private string $pluginUrl = CMS_365NET_EVENTS_PLUGIN_URL;

        public static function instance(): self
        {
            if (self::$instance === null) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function __construct()
        {
            $this->loadDependencies();
            $this->initHooks();
            $this->bootstrapHookComponents();

            if ($this->canBootstrapComponents()) {
                $this->bootstrapComponents();
            }
        }

        /**
         * Lädt ausschließlich Plugin-Dateien aus dem eigenen includes-Ordner.
         */
        private function loadDependencies(): void
        {
            $includesDir = $this->pluginDir . 'includes/';
            $includesReal = realpath($includesDir);
            if ($includesReal === false || !is_dir($includesReal)) {
                return;
            }

            foreach ([
                'class-database.php',
                'class-template-loader.php',
                'class-admin.php',
                'class-post-type.php',
            ] as $file) {
                $candidate = $includesReal . DIRECTORY_SEPARATOR . $file;
                $realPath = realpath($candidate);
                if ($realPath !== false && str_starts_with($realPath, $includesReal . DIRECTORY_SEPARATOR) && is_file($realPath)) {
                    require_once $realPath;
                }
            }
        }

        private function initHooks(): void
        {
            if (!class_exists('CMS\\Hooks')) {
                return;
            }

            CMS\Hooks::addAction('cms_init', [$this, 'initPlugin'], 10);
            CMS\Hooks::addAction('plugin_activated', [$this, 'onActivation'], 10);
            CMS\Hooks::addAction('plugin_deactivated', [$this, 'onDeactivation'], 10);
            CMS\Hooks::addAction('head', [$this, 'enqueueStyles'], 10);
            CMS\Hooks::addAction('body_end', [$this, 'enqueueScripts'], 10);
        }

        /**
         * Komponenten, die bereits vor DB-Verfügbarkeit Hooks registrieren können.
         */
        private function bootstrapHookComponents(): void
        {
            if (!class_exists('CMS\\Hooks')) {
                return;
            }

            foreach (['CMS_365NET_Events_Post_Type', 'CMS_365NET_Events_Admin', 'CMS_365NET_Events_Template_Loader'] as $class) {
                if (class_exists($class, false)) {
                    $class::instance();
                }
            }
        }

        private function canBootstrapComponents(): bool
        {
            return class_exists('CMS\\Hooks') && class_exists('CMS\\Database');
        }

        private function bootstrapComponents(): void
        {
            if ($this->componentsBootstrapped) {
                return;
            }

            $this->componentsBootstrapped = true;

            foreach (['CMS_365NET_Events_Database', 'CMS_365NET_Events_Template_Loader', 'CMS_365NET_Events_Admin', 'CMS_365NET_Events_Post_Type'] as $class) {
                if (class_exists($class)) {
                    $class::instance();
                }
            }
        }

        public function initPlugin(): void
        {
            if (!$this->canBootstrapComponents()) {
                return;
            }

            try {
                CMS_365NET_Events_Database::instance()->ensureSchema();
            } catch (Throwable $e) {
                $this->log('initPlugin', $e);
            }

            $this->bootstrapComponents();
        }

        public function onActivation(string $plugin): void
        {
            if (!$this->isCurrentPluginSlug($plugin)) {
                return;
            }

            if (class_exists('CMS\\Database') && class_exists('CMS_365NET_Events_Database')) {
                try {
                    CMS_365NET_Events_Database::instance()->ensureSchema();
                } catch (Throwable $e) {
                    $this->log('activation', $e);
                }
            }

            if (class_exists('CMS\\Hooks')) {
                CMS\Hooks::doAction('cms_365net_events_activated');
            }
        }

        public function onDeactivation(string $plugin): void
        {
            if (!$this->isCurrentPluginSlug($plugin)) {
                return;
            }

            if (class_exists('CMS\\Hooks')) {
                CMS\Hooks::doAction('cms_365net_events_deactivated');
            }
        }

        private function isCurrentPluginSlug(string $plugin): bool
        {
            $slug = $this->normalizePluginSlug($plugin);

            return $slug === 'cms-365netevents';
        }

        private function normalizePluginSlug(string $plugin): string
        {
            $plugin = str_replace('\\\\', '/', trim((string) $plugin));
            if ($plugin === '') {
                return '';
            }

            $parts = explode('/', trim($plugin, '/'));
            $last = (string) end($parts);
            if ($last !== '' && str_ends_with(strtolower($last), '.php')) {
                $last = substr($last, 0, -4);
            }

            return strtolower(trim($last));
        }

        public function enqueueStyles(): void
        {
            if ($this->isFrontendRoute()) {
                $this->enqueueStyleFile('public.css');
                $this->enqueuePublicSettingsStyle();
            }

            if ($this->isAdminRoute()) {
                $this->enqueueStyleFile('admin.css');
                $this->enqueueStyleFile('admin-enhancements.css');
            }
        }

        public function enqueueScripts(): void
        {
            if ($this->isFrontendRoute()) {
                $this->enqueueScriptFile('public.js');
            }

            if ($this->isAdminRoute()) {
                $this->enqueueScriptFile('admin.js');
            }
        }

        private function enqueueStyleFile(string $file): void
        {
            $path = $this->pluginDir . 'assets/css/' . $file;
            if (!is_file($path)) {
                return;
            }

            $href = $this->pluginUrl . 'assets/css/' . $file . '?v=' . (string) filemtime($path);
            echo '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        private function enqueuePublicSettingsStyle(): void
        {
            if (!class_exists('CMS_365NET_Events_Database')) {
                return;
            }

            $settings = CMS_365NET_Events_Database::instance()->getSettings();
            $color = static fn(string $key, string $fallback): string => preg_match('/^#[0-9a-f]{6}$/i', (string) ($settings[$key] ?? '')) === 1 ? (string) $settings[$key] : $fallback;
            $number = static fn(string $key, int $fallback, int $min = 0, int $max = 1800): int => max($min, min($max, (int) ($settings[$key] ?? $fallback)));
            echo '<style id="cms-365netevents-settings">.cms-events-public{--cms-events-primary:' . htmlspecialchars($color('layout_primary_color', '#1d4ed8'), ENT_QUOTES, 'UTF-8') . ';--cms-events-accent:' . htmlspecialchars($color('layout_accent_color', '#f59e0b'), ENT_QUOTES, 'UTF-8') . ';--cms-events-text:' . htmlspecialchars($color('layout_text_color', '#0f172a'), ENT_QUOTES, 'UTF-8') . ';--cms-events-card-bg:' . htmlspecialchars($color('layout_card_background', '#ffffff'), ENT_QUOTES, 'UTF-8') . ';--cms-events-card-border:' . htmlspecialchars($color('layout_card_border', '#e2e8f0'), ENT_QUOTES, 'UTF-8') . ';--cms-events-radius:' . $number('layout_radius', 24, 0, 96) . 'px;--cms-events-card-radius:' . $number('layout_card_radius', 20, 0, 96) . 'px;--cms-events-gap:' . $number('layout_gap', 18, 0, 120) . 'px;--cms-events-top:' . $number('layout_top_spacing', 32, 0, 240) . 'px;--cms-events-bottom:' . $number('layout_bottom_spacing', 56, 0, 240) . 'px;--cms-events-width:' . $number('layout_container_width', 1160, 320, 1800) . 'px;background:transparent!important;padding-top:var(--cms-events-top)!important;padding-bottom:var(--cms-events-bottom)!important;color:var(--cms-events-text)!important}.cms-events-container{max-width:var(--cms-events-width)!important}.cms-events-hero{border-radius:var(--cms-events-radius)!important;background:linear-gradient(135deg,var(--cms-events-primary),#172554)!important}.cms-events-card,.cms-events-detail-main,.cms-events-sidecard,.cms-events-search{background:var(--cms-events-card-bg)!important;border-color:var(--cms-events-card-border)!important;border-radius:var(--cms-events-card-radius)!important}.cms-events-grid{gap:var(--cms-events-gap)!important}.cms-events-search button,.cms-events-btn{background:var(--cms-events-primary)!important}.cms-events-kicker{color:var(--cms-events-accent)!important}.cms-events-card__more{color:var(--cms-events-primary)!important}</style>' . "\n";
        }

        private function enqueueScriptFile(string $file): void
        {
            $path = $this->pluginDir . 'assets/js/' . $file;
            if (!is_file($path)) {
                return;
            }

            $src = $this->pluginUrl . 'assets/js/' . $file . '?v=' . (string) filemtime($path);
            echo '<script src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
        }

        private function isFrontendRoute(): bool
        {
            $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if (!in_array($method, ['GET', 'HEAD'], true)) {
                return false;
            }

            $path = $this->requestPath();
            return $path === '/events'
                || str_starts_with($path, '/events/')
                || $path === '/speakers'
                || str_starts_with($path, '/speakers/')
                || $path === '/event-speakers'
                || str_starts_with($path, '/event-speakers/');
        }

        private function isAdminRoute(): bool
        {
            return str_starts_with($this->requestPath(), '/admin/365netevents');
        }

        private function requestPath(): string
        {
            if ($this->requestPathCache !== null) {
                return $this->requestPathCache;
            }

            $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
            $this->requestPathCache = '/' . trim($path, '/');
            return $this->requestPathCache === '/' ? '/' : $this->requestPathCache;
        }

        public function log(string $context, Throwable $error): void
        {
            $message = '[' . date('c') . '] ' . $context . ': ' . $error->getMessage() . PHP_EOL;
            $logDir = $this->pluginDir . 'logs/';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0755, true);
            }

            if (is_dir($logDir) && is_writable($logDir)) {
                @file_put_contents($logDir . 'cms-365netevents.log', $message, FILE_APPEND | LOCK_EX);
            }

            error_log('CMS 365NET Events [' . $context . ']: ' . $error->getMessage());
        }

        public function getVersion(): string
        {
            return $this->version;
        }

        public function getPluginDir(): string
        {
            return $this->pluginDir;
        }

        public function getPluginUrl(): string
        {
            return $this->pluginUrl;
        }
    }
}

CMS_365NET_Events::instance();
