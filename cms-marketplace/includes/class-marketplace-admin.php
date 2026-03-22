<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Marketplace_Admin
{
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
            'cms-marketplace',
            [$this, 'renderPage'],
            '🛒',
            82
        );

        add_submenu_page(
            'cms-marketplace',
            'Marketplace Übersicht',
            'Übersicht',
            'admin',
            'cms-marketplace',
            [$this, 'renderOverviewPage']
        );

        add_submenu_page(
            'cms-marketplace',
            'Marketplace CMS',
            'CMS',
            'admin',
            'cms-marketplace-cms',
            [$this, 'renderCmsPage']
        );

        add_submenu_page(
            'cms-marketplace',
            'Marketplace Plugins',
            'Plugins',
            'admin',
            'cms-marketplace-plugins',
            [$this, 'renderPluginsPage']
        );

        add_submenu_page(
            'cms-marketplace',
            'Marketplace Themes',
            'Themes',
            'admin',
            'cms-marketplace-themes',
            [$this, 'renderThemesPage']
        );

        add_submenu_page(
            'cms-marketplace',
            'Marketplace Verzeichnis',
            'Verzeichnis',
            'admin',
            'cms-marketplace-directory',
            [$this, 'renderDirectoryPage']
        );

        add_submenu_page(
            'cms-marketplace',
            'Marketplace Einstellungen',
            'Einstellungen',
            'admin',
            'cms-marketplace-settings',
            [$this, 'renderSettingsPage']
        );
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

        include CMS_MARKETPLACE_PLUGIN_DIR . 'admin/page.php';
    }

    private function handlePost(string $section, string $currentFilterType, int $currentEditId): array
    {
        $message = null;
        $messageType = 'success';
        $filterType = $this->resolveFilterTypeForSection($section, (string) ($_POST['filter_type'] ?? $currentFilterType));
        $editId = max(0, (int) ($_POST['edit_id'] ?? $currentEditId));

        if (!class_exists('CMS\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'cms_marketplace_admin')) {
            return ['Sicherheitscheck fehlgeschlagen.', 'error', $editId, $filterType, $section];
        }

        $action = (string) ($_POST['cms_marketplace_action'] ?? '');

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
            return $forcedSection;
        }

        return match ((string) ($_GET['page'] ?? 'cms-marketplace')) {
            'cms-marketplace-cms' => self::PAGE_CMS,
            'cms-marketplace-plugins' => self::PAGE_PLUGINS,
            'cms-marketplace-themes' => self::PAGE_THEMES,
            'cms-marketplace-directory' => self::PAGE_DIRECTORY,
            'cms-marketplace-settings' => self::PAGE_SETTINGS,
            default => self::PAGE_OVERVIEW,
        };
    }

    private function resolveDirectoryScope(string $value): string
    {
        $value = strtolower(trim($value));
        return in_array($value, ['all', 'cms', 'plugin', 'theme'], true) ? $value : 'all';
    }

    private function getSectionConfig(string $section): array
    {
        $pages = [
            self::PAGE_OVERVIEW => ['slug' => 'cms-marketplace', 'label' => 'Übersicht', 'title' => 'Marketplace Übersicht', 'description' => 'Zentraler Überblick über CMS-, Plugin- und Theme-Bereiche.'],
            self::PAGE_CMS => ['slug' => 'cms-marketplace-cms', 'label' => 'CMS', 'title' => '365CMS Bereich', 'description' => 'Vorbereitung und Verwaltung des späteren zentralen 365CMS-Update-Bereichs.'],
            self::PAGE_PLUGINS => ['slug' => 'cms-marketplace-plugins', 'label' => 'Plugins', 'title' => 'Plugin Marketplace', 'description' => 'Verwalte Plugin-Einträge, Versionen, ZIPs und öffentliche Feeds.'],
            self::PAGE_THEMES => ['slug' => 'cms-marketplace-themes', 'label' => 'Themes', 'title' => 'Theme Marketplace', 'description' => 'Verwalte Theme-Einträge, Versionen, ZIPs und öffentliche Feeds.'],
            self::PAGE_DIRECTORY => ['slug' => 'cms-marketplace-directory', 'label' => 'Verzeichnis', 'title' => 'Marketplace Verzeichnis', 'description' => 'Datei- und Verzeichnisansicht der generierten Marketplace-Struktur.'],
            self::PAGE_SETTINGS => ['slug' => 'cms-marketplace-settings', 'label' => 'Einstellungen', 'title' => 'Marketplace Einstellungen', 'description' => 'Steuere Public-Submission, Pfade, Währung und Directory-Ansicht.'],
        ];

        return $pages[$section] ?? $pages[self::PAGE_OVERVIEW];
    }

    private function isMarketplaceAdminRequest(): bool
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if ($requestUri === '') {
            return false;
        }

        $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');
        if ($path === '') {
            return false;
        }

        return str_contains($path, '/admin/plugins/cms-marketplace');
    }
}
