<?php

declare(strict_types=1);

namespace CmsKnowledgebase\Http;

use CmsKnowledgebase\Repository\EntryRepository;

if (!defined('ABSPATH')) {
    exit;
}

final class PublicController
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    public function registerRoutes($router): void
    {
        $router->addRoute('GET', '/kb', [$this, 'archivePage']);
        $router->addRoute('GET', '/kb/:slug', [$this, 'singlePage']);
    }

    public function renderNavItem(string $context = 'desktop'): void
    {
        $settings = EntryRepository::instance()->getSettings();
        if (($settings['show_nav_link'] ?? '0') !== '1') {
            return;
        }

        $currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $active = str_starts_with($currentPath, '/kb') ? 'active' : '';
        $label = htmlspecialchars((string) ($settings['nav_label'] ?? 'Knowledgebase'), ENT_QUOTES, 'UTF-8');
        $href = htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8');

        if ($context === 'mobile') {
            echo '<a href="' . $href . '" class="mobile-menu__kb-link">' . $label . '</a>';
            return;
        }

        echo '<a href="' . $href . '" class="main-nav__link ' . htmlspecialchars($active, ENT_QUOTES, 'UTF-8') . '">' . $label . '</a>';
    }

    public function archivePage(): void
    {
        $repository = EntryRepository::instance();
        $settings = $repository->getSettings();
        $search = trim((string) ($_GET['q'] ?? ''));
        $category = trim((string) ($_GET['category'] ?? ''));
        $entries = $repository->getEntries([
            'status' => 'active',
            'search' => $search,
            'category' => $category,
        ]);
        $categories = $repository->getCategories();
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;

        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'templates/archive-knowledgebase.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }

    public function singlePage(string $slug): void
    {
        $repository = EntryRepository::instance();
        $entry = $repository->getEntryBySlug($slug);
        $settings = $repository->getSettings();
        if ($entry === null) {
            http_response_code(404);
            echo '<h1>404 – Knowledgebase-Eintrag nicht gefunden</h1>';
            return;
        }

        $relatedEntries = $repository->getRelatedEntries($entry);
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;

        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'templates/single-knowledgebase.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }
}
