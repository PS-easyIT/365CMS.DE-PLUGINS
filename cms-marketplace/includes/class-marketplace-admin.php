<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Marketplace_Admin
{
    private const REQUIRED_CAPABILITY = 'admin';
    private const ROOT_SLUG = 'cms-marketplace';
    private const SLUG_CMS = 'cms-marketplace-cms';
    private const SLUG_PLUGINS = 'cms-marketplace-plugins';
    private const SLUG_THEMES = 'cms-marketplace-themes';
    private const SLUG_DIRECTORY = 'cms-marketplace-directory';
    private const SLUG_SETTINGS = 'cms-marketplace-settings';

    private const PAGE_OVERVIEW = 'overview';
    private const PAGE_CMS = 'cms';
    private const PAGE_PLUGINS = 'plugins';
    private const PAGE_THEMES = 'themes';
    private const PAGE_DIRECTORY = 'directory';
    private const PAGE_SETTINGS = 'settings';

    public function __construct(private readonly CMS_Marketplace_Service $service)
    {
    }

    public function isCurrentRequest(): bool
    {
        return $this->isMarketplaceAdminRequest();
    }

    public function boot(): void
    {
        $this->service->boot();
    }

    public function registerPages(): void
    {
        if (!function_exists('add_menu_page') || !function_exists('add_submenu_page')) {
            return;
        }

        add_menu_page(
            '365CMS Marketplace',
            'Marketplace',
            'admin',
            self::ROOT_SLUG,
            [$this, 'renderRequestedPage'],
            '🛒',
            82
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'Marketplace Übersicht',
            'Übersicht',
            'admin',
            self::ROOT_SLUG,
            [$this, 'renderRequestedPage']
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'Marketplace CMS',
            'CMS',
            'admin',
            self::SLUG_CMS,
            [$this, 'renderRequestedPage']
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'Marketplace Plugins',
            'Plugins',
            'admin',
            self::SLUG_PLUGINS,
            [$this, 'renderRequestedPage']
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'Marketplace Themes',
            'Themes',
            'admin',
            self::SLUG_THEMES,
            [$this, 'renderRequestedPage']
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'Marketplace Verzeichnis',
            'Verzeichnis',
            'admin',
            self::SLUG_DIRECTORY,
            [$this, 'renderRequestedPage']
        );

        add_submenu_page(
            self::ROOT_SLUG,
            'Marketplace Einstellungen',
            'Einstellungen',
            'admin',
            self::SLUG_SETTINGS,
            [$this, 'renderRequestedPage']
        );
    }

    public function renderRequestedPage(): void
    {
        if (!$this->currentUserCanManage()) {
            cms_plugin_admin_emit_notice('Du hast keine Berechtigung für diesen Bereich.', 'error', 'permission denied on renderRequestedPage');
            return;
        }

        $callbackMap = [
            self::ROOT_SLUG => [$this, 'renderOverviewPage'],
            self::SLUG_CMS => [$this, 'renderCmsPage'],
            self::SLUG_PLUGINS => [$this, 'renderPluginsPage'],
            self::SLUG_THEMES => [$this, 'renderThemesPage'],
            self::SLUG_DIRECTORY => [$this, 'renderDirectoryPage'],
            self::SLUG_SETTINGS => [$this, 'renderSettingsPage'],
        ];

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, self::ROOT_SLUG, self::ROOT_SLUG);
            return;
        }

        $requestedSlug = strtolower(trim((string) ($_GET['page'] ?? self::ROOT_SLUG)));
        $callback = $callbackMap[$requestedSlug] ?? $callbackMap[self::ROOT_SLUG];
        if (is_callable($callback)) {
            call_user_func($callback);
            return;
        }

        $this->renderOverviewPage();
    }

    public function renderOverviewPage(): void
    {
        $this->renderPage(self::PAGE_OVERVIEW);
    }

    public function renderCmsPage(): void
    {
        $this->renderPage(self::PAGE_CMS);
    }

    public function renderPluginsPage(): void
    {
        $this->renderPage(self::PAGE_PLUGINS);
    }

    public function renderThemesPage(): void
    {
        $this->renderPage(self::PAGE_THEMES);
    }

    public function renderDirectoryPage(): void
    {
        $this->renderPage(self::PAGE_DIRECTORY);
    }

    public function renderSettingsPage(): void
    {
        $this->renderPage(self::PAGE_SETTINGS);
    }

    public function enqueueStyles(): void
    {
        if (!$this->isMarketplaceAdminRequest()) {
            return;
        }

        $cssFile = CMS_MARKETPLACE_PLUGIN_DIR . 'assets/css/admin.css';
        if (!is_file($cssFile)) {
            return;
        }

        echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_MARKETPLACE_PLUGIN_URL . 'assets/css/admin.css', ENT_QUOTES, 'UTF-8')
            . '?v=' . (int) filemtime($cssFile) . '">' . "\n";
    }

    public function renderPage(?string $forcedSection = null): void
    {
        if (!$this->currentUserCanManage()) {
            cms_plugin_admin_emit_notice('Du hast keine Berechtigung für diesen Bereich.', 'error', 'permission denied on renderPage');
            return;
        }

        $message = null;
        $messageType = 'success';
        $section = $this->resolveSection($forcedSection);
        $filterType = $this->resolveFilterTypeForSection($section, (string) ($_GET['type'] ?? ''));
        $editId = max(0, (int) ($_GET['edit'] ?? 0));
        $directoryScope = $this->resolveDirectoryScope((string) ($_GET['scope'] ?? 'all'));
        $inspectPath = trim((string) ($_GET['inspect'] ?? ''));

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            [$message, $messageType, $editId, $filterType, $section] = $this->handlePost($section, $filterType, $editId);
        }

        $editItem = $editId > 0 ? $this->service->findItem($editId) : null;
        if ($editId > 0 && $editItem === null) {
            $message = 'Der gewählte Eintrag wurde nicht gefunden.';
            $messageType = 'error';
            $editId = 0;
        }

        $entryType = $this->resolveEntryTypeForSection($section, $filterType);
        $items = $this->loadItemsForSection($section, $filterType);
        $summary = $this->service->getSummary();
        $publicUrls = $this->service->getPublicUrls();
        $publicRouteMap = $this->service->getPublicRouteMap();
        $settings = $this->service->getSettings();
        $directoryScope = $section === self::PAGE_PLUGINS ? 'plugin' : ($section === self::PAGE_THEMES ? 'theme' : ($section === self::PAGE_CMS ? 'cms' : $directoryScope));
        $directorySnapshot = $this->service->getDirectorySnapshot($directoryScope);
        $directoryEntryDetails = $this->service->getDirectoryEntryDetails($directoryScope, $inspectPath);
        $formDefaults = $this->service->getFormDefaults($entryType);
        $sectionConfig = $this->getSectionConfig($section);
        $csrfToken = class_exists('CMS\Security') ? \CMS\Security::instance()->generateToken('cms_marketplace_admin') : '';

        cms_plugin_admin_layout_start('365CMS Marketplace', self::ROOT_SLUG);

        $templatePath = CMS_MARKETPLACE_PLUGIN_DIR . 'admin/page.php';
        if (!is_file($templatePath)) {
            cms_plugin_admin_emit_notice(
                'Die Marketplace-Adminseite konnte nicht geladen werden.',
                'error',
                'renderPage missing template: ' . $templatePath
            );
            cms_plugin_admin_layout_end();
            return;
        }

        try {
            include $templatePath;
        } catch (\Throwable $e) {
            cms_plugin_admin_emit_notice(
                'Die Marketplace-Adminseite konnte nicht geladen werden.',
                'error',
                'renderPage failed: ' . $e->getMessage()
            );
        }

        cms_plugin_admin_layout_end();
    }

    private function handlePost(string $section, string $currentFilterType, int $currentEditId): array
    {
        $message = null;
        $messageType = 'success';
        $filterType = $this->resolveFilterTypeForSection($section, (string) ($_POST['filter_type'] ?? $currentFilterType));
        $editId = max(0, (int) ($_POST['edit_id'] ?? $currentEditId));

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return ['Ungültige Anfrage-Methode.', 'error', $editId, $filterType, $section];
        }

        if (!$this->currentUserCanManage()) {
            return ['Unzureichende Berechtigung für diese Aktion.', 'error', $editId, $filterType, $section];
        }

        if (!class_exists('CMS\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'cms_marketplace_admin')) {
            return ['Sicherheitscheck fehlgeschlagen.', 'error', $editId, $filterType, $section];
        }

        $action = strtolower(trim((string) ($_POST['cms_marketplace_action'] ?? '')));
        if (!in_array($action, ['save_item', 'toggle_publish', 'save_settings'], true)) {
            return ['Unbekannte Aktion.', 'error', $editId, $filterType, $section];
        }

        if ($action === 'save_item') {
            $result = $this->service->saveItem($_POST, $_FILES['package_zip'] ?? null, $editId > 0 ? $editId : null);
            $message = (string) ($result['message'] ?? 'Aktion abgeschlossen.');
            $messageType = !empty($result['success']) ? 'success' : 'error';
            $editId = !empty($result['success']) ? 0 : $editId;
            return [$message, $messageType, $editId, $filterType, $section];
        }

        if ($action === 'toggle_publish') {
            $itemId = max(0, (int) ($_POST['item_id'] ?? 0));
            $publish = !empty($_POST['publish']);
            $result = $this->service->togglePublished($itemId, $publish);
            $message = (string) ($result['message'] ?? 'Aktion abgeschlossen.');
            $messageType = !empty($result['success']) ? 'success' : 'error';
            return [$message, $messageType, $editId, $filterType, $section];
        }

        if ($action === 'save_settings') {
            $result = $this->service->saveSettings($_POST);
            $message = (string) ($result['message'] ?? 'Aktion abgeschlossen.');
            $messageType = !empty($result['success']) ? 'success' : 'error';
            return [$message, $messageType, $editId, $filterType, self::PAGE_SETTINGS];
        }

        return ['Unbekannte Aktion.', 'error', $editId, $filterType, $section];
    }

    private function resolveFilterType(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['cms', 'plugin', 'theme'], true) ? $value : '';
    }

    private function resolveFilterTypeForSection(string $section, string $value): string
    {
        return match ($section) {
            self::PAGE_CMS => 'cms',
            self::PAGE_PLUGINS => 'plugin',
            self::PAGE_THEMES => 'theme',
            default => $this->resolveFilterType($value),
        };
    }

    private function resolveEntryTypeForSection(string $section, string $filterType): string
    {
        return match ($section) {
            self::PAGE_CMS => 'cms',
            self::PAGE_PLUGINS => 'plugin',
            self::PAGE_THEMES => 'theme',
            default => $filterType !== '' ? $filterType : 'plugin',
        };
    }

    private function loadItemsForSection(string $section, string $filterType): array
    {
        if ($section === self::PAGE_CMS) {
            return $this->service->getItems('cms');
        }

        if ($section === self::PAGE_PLUGINS) {
            return $this->service->getItems('plugin');
        }

        if ($section === self::PAGE_THEMES) {
            return $this->service->getItems('theme');
        }

        $items = $this->service->getItems($filterType !== '' ? $filterType : null);
        if ($filterType !== '') {
            return $items;
        }

        return array_values(array_filter($items, static function (array $item): bool {
            return (string) ($item['type'] ?? '') !== 'cms';
        }));
    }

    private function resolveSection(?string $forcedSection = null): string
    {
        if ($forcedSection !== null && $forcedSection !== '') {
            return in_array($forcedSection, [
                self::PAGE_OVERVIEW,
                self::PAGE_CMS,
                self::PAGE_PLUGINS,
                self::PAGE_THEMES,
                self::PAGE_DIRECTORY,
                self::PAGE_SETTINGS,
            ], true) ? $forcedSection : self::PAGE_OVERVIEW;
        }

        $requestedSlug = function_exists('cms_plugin_admin_active_slug')
            ? cms_plugin_admin_active_slug(self::ROOT_SLUG)
            : strtolower(trim((string) ($_GET['page'] ?? self::ROOT_SLUG)));

        $sectionBySlug = [
            self::ROOT_SLUG => self::PAGE_OVERVIEW,
            self::SLUG_CMS => self::PAGE_CMS,
            self::SLUG_PLUGINS => self::PAGE_PLUGINS,
            self::SLUG_THEMES => self::PAGE_THEMES,
            self::SLUG_DIRECTORY => self::PAGE_DIRECTORY,
            self::SLUG_SETTINGS => self::PAGE_SETTINGS,
        ];

        return $sectionBySlug[$requestedSlug] ?? self::PAGE_OVERVIEW;
    }

    private function resolveDirectoryScope(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['all', 'cms', 'plugin', 'theme'], true) ? $value : 'all';
    }

    private function getSectionConfig(string $section): array
    {
        $pages = [
            self::PAGE_OVERVIEW => ['slug' => self::ROOT_SLUG, 'label' => 'Übersicht', 'title' => 'Marketplace Übersicht', 'description' => 'Zentraler Überblick über CMS-, Plugin- und Theme-Bereiche.'],
            self::PAGE_CMS => ['slug' => self::SLUG_CMS, 'label' => 'CMS', 'title' => '365CMS Bereich', 'description' => 'Vorbereitung und Verwaltung des späteren zentralen 365CMS-Update-Bereichs.'],
            self::PAGE_PLUGINS => ['slug' => self::SLUG_PLUGINS, 'label' => 'Plugins', 'title' => 'Plugin Marketplace', 'description' => 'Verwalte Plugin-Einträge, Versionen, ZIPs und öffentliche Feeds.'],
            self::PAGE_THEMES => ['slug' => self::SLUG_THEMES, 'label' => 'Themes', 'title' => 'Theme Marketplace', 'description' => 'Verwalte Theme-Einträge, Versionen, ZIPs und öffentliche Feeds.'],
            self::PAGE_DIRECTORY => ['slug' => self::SLUG_DIRECTORY, 'label' => 'Verzeichnis', 'title' => 'Marketplace Verzeichnis', 'description' => 'Datei- und Verzeichnisansicht der generierten Marketplace-Struktur.'],
            self::PAGE_SETTINGS => ['slug' => self::SLUG_SETTINGS, 'label' => 'Einstellungen', 'title' => 'Marketplace Einstellungen', 'description' => 'Steuere Public-Submission, Pfade, Währung und Directory-Ansicht.'],
        ];

        return $pages[$section] ?? $pages[self::PAGE_OVERVIEW];
    }

    private function isMarketplaceAdminRequest(): bool
    {
        $requestedPage = (string) ($_GET['page'] ?? '');
        if ($requestedPage !== '') {
            $normalizedPage = function_exists('cms_plugin_admin_normalize_slug')
                ? cms_plugin_admin_normalize_slug($requestedPage)
                : strtolower(trim($requestedPage));
            if (str_starts_with($normalizedPage, self::ROOT_SLUG)) {
                return true;
            }
        }

        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if ($requestUri === '') {
            return false;
        }

        $query = (string) (parse_url($requestUri, PHP_URL_QUERY) ?? '');
        if ($query !== '') {
            parse_str($query, $queryParams);
            $queryPage = is_array($queryParams) ? (string) ($queryParams['page'] ?? '') : '';
            $normalizedQueryPage = function_exists('cms_plugin_admin_normalize_slug')
                ? cms_plugin_admin_normalize_slug($queryPage)
                : strtolower(trim($queryPage));
            if ($normalizedQueryPage !== '' && str_starts_with($normalizedQueryPage, self::ROOT_SLUG)) {
                return true;
            }
        }

        $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');
        return $path !== '' && str_contains($path, '/admin/plugins/cms-marketplace');
    }

    private function currentUserCanManage(): bool
    {
        if (function_exists('current_user_can')) {
            return (bool) current_user_can(self::REQUIRED_CAPABILITY);
        }

        return true;
    }
}
