<?php

declare(strict_types=1);

namespace CmsKnowledgebase\Http;

use CmsKnowledgebase\Repository\EntryRepository;
use CmsKnowledgebase\Support\LoggerFactory;

if (!defined('ABSPATH')) {
    exit;
}

final class PublicController
{
    private static ?self $instance = null;

    private $logger;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->logger = LoggerFactory::create();
    }

    public function registerRoutes($router): void
    {
        if (!is_object($router) || !method_exists($router, 'addRoute')) {
            $this->logger->error('Knowledgebase-Router ohne addRoute in registerRoutes erhalten.');
            return;
        }

        $router->addRoute('GET', '/kb', [$this, 'archivePage']);
        $router->addRoute('GET', '/glossar', [$this, 'glossaryPage']);
        $router->addRoute('GET', '/glossar-sitemap.xml', [$this, 'glossarySitemap']);
        $router->addRoute('GET', '/kb/:slug', [$this, 'singlePage']);
    }

    public function renderNavItem(string $context = 'desktop'): void
    {
        $settings = EntryRepository::instance()->getSettings();
        if (($settings['show_nav_link'] ?? '0') !== '1') {
            return;
        }

        $currentPath = \CmsKnowledgebase\Support\RequestInspector::currentBasePath();
        $active = (str_starts_with($currentPath, '/kb') || $currentPath === '/glossar') ? 'active' : '';
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
        $search = $this->sanitizeSearchTerm($_GET['q'] ?? '');
        $category = $this->sanitizeCategoryFilter($_GET['category'] ?? '');
        $allowedPerPage = [25, 50, 100, 200];
        $requestedPerPage = (int) ($_GET['per_page'] ?? 25);
        $perPage = in_array($requestedPerPage, $allowedPerPage, true) ? $requestedPerPage : 25;
        $filters = [
            'status' => 'active',
            'search' => $search,
            'category' => $category,
        ];
        $totalEntries = $repository->countEntries($filters);
        $totalPages = max(1, (int) ceil($totalEntries / $perPage));
        $currentPage = $this->sanitizePageNumber($_GET['page'] ?? 1);
        $currentPage = min($currentPage, $totalPages);
        $entries = $repository->getEntries([
            ...$filters,
            'limit' => $perPage,
            'offset' => ($currentPage - 1) * $perPage,
        ]);
        $categories = $repository->getCategories();
        $archiveHeroEyebrow = 'Knowledgebase';
        $archiveIntro = (string) ($settings['archive_intro'] ?? '');
        $archiveResultsLabel = $search !== '' || $category !== ''
            ? 'Gefilterte Knowledgebase-Treffer'
            : 'Knowledgebase-Übersicht';
        $perPageOptions = $allowedPerPage;
        $pageBaseUrl = SITE_URL . '/kb';
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;

        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'templates/archive-knowledgebase.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }

    public function glossaryPage(): void
    {
        $repository = EntryRepository::instance();
        $settings = $repository->getSettings();
        $search = $this->sanitizeSearchTerm($_GET['q'] ?? '');
        $category = $this->sanitizeCategoryFilter($_GET['category'] ?? '');
        $allowedPerPage = [25, 50, 100, 200];
        $requestedPerPage = (int) ($_GET['per_page'] ?? 25);
        $perPage = in_array($requestedPerPage, $allowedPerPage, true) ? $requestedPerPage : 25;
        $filters = [
            'status' => 'active',
            'search' => $search,
            'category' => $category,
        ];
        $totalEntries = $repository->countEntries($filters);
        $totalPages = max(1, (int) ceil($totalEntries / $perPage));
        $currentPage = $this->sanitizePageNumber($_GET['page'] ?? 1);
        $currentPage = min($currentPage, $totalPages);
        $entries = $repository->getEntries([
            ...$filters,
            'limit' => $perPage,
            'offset' => ($currentPage - 1) * $perPage,
        ]);
        $categories = $repository->getCategories();
        $archiveVariant = 'glossary';
        $archiveBasePath = '/glossar';
        $pageBaseUrl = SITE_URL . '/glossar';
        $perPageOptions = $allowedPerPage;
        $archiveHeroEyebrow = 'Glossar';
        $archiveTitle = (string) ($settings['glossary_title'] ?? 'Glossar');
        $archiveIntro = (string) ($settings['glossary_intro'] ?? '');
        $archiveResultsLabel = $search !== '' || $category !== ''
            ? 'Gefilterte Glossar-Einträge'
            : 'Glossar-Übersicht';
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;

        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'templates/archive-knowledgebase.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }

    public function glossarySitemap(): void
    {
        $repository = EntryRepository::instance();
        $entries = $repository->getEntryList(['status' => 'active']);
        $generatedAt = function_exists('gmdate') ? gmdate('c') : date('c');
        $glossaryUrl = SITE_URL . '/glossar';

        $latestGlossaryTimestamp = null;
        foreach ($entries as $entry) {
            $timestamp = $this->resolveLastModified((string) ($entry['updated_at'] ?? ''), (string) ($entry['created_at'] ?? ''));
            if ($timestamp === null) {
                continue;
            }

            if ($latestGlossaryTimestamp === null || $timestamp > $latestGlossaryTimestamp) {
                $latestGlossaryTimestamp = $timestamp;
            }
        }

        $urls = [[
            'loc' => $glossaryUrl,
            'lastmod' => $latestGlossaryTimestamp !== null ? gmdate('c', $latestGlossaryTimestamp) : $generatedAt,
            'changefreq' => 'daily',
            'priority' => '0.9',
        ]];

        foreach ($entries as $entry) {
            $slug = trim((string) ($entry['slug'] ?? ''));
            if ($slug === '') {
                continue;
            }

            $timestamp = $this->resolveLastModified((string) ($entry['updated_at'] ?? ''), (string) ($entry['created_at'] ?? ''));
            $urls[] = [
                'loc' => SITE_URL . '/kb/' . rawurlencode($slug),
                'lastmod' => $timestamp !== null ? gmdate('c', $timestamp) : $generatedAt,
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        $this->sendXmlHeaders();

        echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $url) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars((string) $url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            echo '    <lastmod>' . htmlspecialchars((string) $url['lastmod'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</lastmod>\n";
            echo '    <changefreq>' . htmlspecialchars((string) $url['changefreq'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</changefreq>\n";
            echo '    <priority>' . htmlspecialchars((string) $url['priority'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</priority>\n";
            echo "  </url>\n";
        }

        echo "</urlset>";
        exit;
    }

    public function singlePage(string $slug): void
    {
        $slug = $this->sanitizePublicSlug($slug);
        if ($slug === '') {
            $this->renderNotFound();
            return;
        }

        $repository = EntryRepository::instance();
        $entry = $repository->getEntryBySlug($slug);
        $settings = $repository->getSettings();
        if ($entry === null) {
            $this->renderNotFound();
            return;
        }

        $requestPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/kb/' . $slug), PHP_URL_PATH);
        $requestContext = class_exists('CMS\\Services\\ContentLocalizationService')
            ? \CMS\Services\ContentLocalizationService::getInstance()->resolveRequestContext($requestPath)
            : ['locale' => 'de'];
        $contentLocale = (string) ($requestContext['locale'] ?? 'de');
        $relatedPostsLimit = max(3, min(6, (int) ($settings['related_posts_limit'] ?? 4)));
        $relatedPosts = $repository->getRelatedPosts($entry, $contentLocale, $relatedPostsLimit);
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;

        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'templates/single-knowledgebase.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }

    private function resolveLastModified(string $updatedAt, string $createdAt): ?int
    {
        foreach ([$updatedAt, $createdAt] as $value) {
            $value = trim($value);
            if ($value === '') {
                continue;
            }

            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return $timestamp;
            }
        }

        return null;
    }

    private function sanitizeSearchTerm(mixed $value): string
    {
        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return mb_substr(trim($value), 0, 120, 'UTF-8');
    }

    private function sanitizeCategoryFilter(mixed $value): string
    {
        $value = trim(strip_tags((string) $value));
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return mb_substr(trim($value), 0, 120, 'UTF-8');
    }

    private function sanitizePageNumber(mixed $value): int
    {
        return max(1, min(10000, (int) $value));
    }

    private function sanitizePublicSlug(string $slug): string
    {
        $slug = trim(rawurldecode($slug));
        $slug = mb_substr($slug, 0, 190, 'UTF-8');

        return preg_match('/^[\p{L}\p{N}-]+$/u', $slug) === 1 ? $slug : '';
    }

    private function sendXmlHeaders(): void
    {
        header('Content-Type: application/xml; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
    }

    private function renderNotFound(): void
    {
        http_response_code(404);
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;
        if ($theme !== null) {
            $theme->getHeader();
        }

        echo '<main class="cms-kb-content cms-kb-content--single"><article class="cms-kb-article"><h1>Knowledgebase-Eintrag nicht gefunden</h1><p>Der angeforderte Eintrag ist nicht verfügbar oder wurde verschoben.</p><p><a class="cms-kb-entry__cta" href="' . htmlspecialchars(SITE_URL . '/kb', ENT_QUOTES, 'UTF-8') . '">Zur Knowledgebase</a></p></article></main>';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }
}
