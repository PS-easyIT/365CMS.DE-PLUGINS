<?php
/**
 * CMS M365 Copilot – public frontend.
 *
 * @package CMS_M365Copilot
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365Copilot_Frontend
{
    private static ?self $instance = null;

    public static function instance(?\CMS\Router $router = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        self::$instance->register_routes($router);

        return self::$instance;
    }

    private function __construct()
    {
        if (class_exists('CMS\\Hooks')) {
            \CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 20);
            \CMS\Hooks::addFilter('body_class', [$this, 'filter_body_class'], 20);
        }
    }

    public function filter_body_class(mixed $bodyClass): string
    {
        $classes = trim((string) $bodyClass);
        if (!$this->is_request()) {
            return $classes;
        }

        $list = preg_split('/\s+/', $classes, -1, PREG_SPLIT_NO_EMPTY);
        if (!is_array($list)) {
            $list = [];
        }
        $list[] = 'm365cp-page';

        return implode(' ', array_values(array_unique($list)));
    }

    public function enqueue_styles(): void
    {
        if (!$this->is_request()) {
            return;
        }

        $css = CMS_M365COPILOT_PLUGIN_DIR . 'assets/css/style.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365COPILOT_PLUGIN_URL . 'assets/css/style.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    private function register_routes(?\CMS\Router $router = null): void
    {
        if (!class_exists('CMS\\Router')) {
            return;
        }

        $router = $router instanceof \CMS\Router ? $router : \CMS\Router::instance();
        $slug = trim((string) (CMS_M365Copilot_Settings::all()['route_slug'] ?? 'microsoft-365-copilot'), '/');
        if ($slug === '') {
            $slug = 'microsoft-365-copilot';
        }

        foreach (['/' . $slug, '/en/' . $slug] as $route) {
            $router->addRoute('GET', $route, function (): void {
                $this->render_page();
            });
        }
    }

    private function is_request(): bool
    {
        $slug = trim((string) (CMS_M365Copilot_Settings::all()['route_slug'] ?? 'microsoft-365-copilot'), '/');
        if ($slug === '') {
            $slug = 'microsoft-365-copilot';
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? rtrim($path, '/') : '';
        if ($path === '') {
            $path = '/';
        }

        $candidates = [
            '/' . $slug,
            '/en/' . $slug,
            '/de/' . $slug,
        ];

        if (in_array($path, $candidates, true)) {
            return true;
        }

        return preg_match('~^/[a-z]{2}(?:-[a-z]{2})?/' . preg_quote($slug, '~') . '$~i', $path) === 1;
    }

    private function render_page(): void
    {
        $settings = CMS_M365Copilot_Settings::all();
        $posts = $this->load_posts($settings);
        $publicCssUrl = '';
        $publicCssPath = CMS_M365COPILOT_PLUGIN_DIR . 'assets/css/style.css';
        if (is_file($publicCssPath)) {
            $publicCssUrl = CMS_M365COPILOT_PLUGIN_URL . 'assets/css/style.css?v=' . (string) filemtime($publicCssPath);
        }

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getHeader(['title' => (string) ($settings['header_title'] ?? 'Microsoft 365 Copilot')]);
        }

        include CMS_M365COPILOT_PLUGIN_DIR . 'templates/page-copilot-landing.php';

        if (class_exists('CMS\\ThemeManager')) {
            \CMS\ThemeManager::instance()->getFooter();
        }
        exit;
    }

    /** @param array<string,string> $settings @return array<int,array<string,mixed>> */
    private function load_posts(array $settings): array
    {
        if (($settings['posts_show'] ?? '1') !== '1' || !class_exists('CMS\\Database')) {
            return [];
        }

        $categoryInput = trim((string) ($settings['posts_category'] ?? 'Microsoft Copilot'));
        $limit = max(1, min(12, (int) ($settings['posts_count'] ?? '3')));
        $db = \CMS\Database::instance();
        $prefix = method_exists($db, 'getPrefix') ? $db->getPrefix() : (method_exists($db, 'prefix') ? $db->prefix() : 'cms_');
        $publicationWhere = function_exists('cms_post_publication_where') ? cms_post_publication_where('p') : "p.status = 'published'";

        $categoryIds = [];
        if ($categoryInput !== '') {
            $stmtCat = $db->prepare("SELECT id FROM {$prefix}post_categories WHERE name = ? OR slug = ? LIMIT 1");
            $stmtCat->execute([$categoryInput, strtolower(str_replace(' ', '-', $categoryInput))]);
            $row = $stmtCat->fetch(\PDO::FETCH_ASSOC);
            if (is_array($row)) {
                $categoryIds[] = (int) ($row['id'] ?? 0);
            }
        }

        if ($categoryIds === []) {
            $stmtLatest = $db->prepare("SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.featured_image, p.published_at, p.created_at, c.name AS category_name, c.slug AS category_slug
                FROM {$prefix}posts p
                LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
                WHERE {$publicationWhere}
                ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
                LIMIT {$limit}");
            $stmtLatest->execute();
            return $this->prepare_posts($stmtLatest->fetchAll(\PDO::FETCH_ASSOC) ?: []);
        }

        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $stmt = $db->prepare("SELECT p.id, p.title, p.slug, p.excerpt, p.content, p.featured_image, p.published_at, p.created_at, c.name AS category_name, c.slug AS category_slug
            FROM {$prefix}posts p
            LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id
            WHERE {$publicationWhere}
              AND (p.category_id IN ({$placeholders}) OR EXISTS (
                    SELECT 1
                    FROM {$prefix}post_category_rel pcr
                    WHERE pcr.post_id = p.id AND pcr.category_id IN ({$placeholders})
              ))
            ORDER BY COALESCE(p.published_at, p.created_at) DESC, p.id DESC
            LIMIT {$limit}");
        $stmt->execute(array_merge($categoryIds, $categoryIds));

        return $this->prepare_posts($stmt->fetchAll(\PDO::FETCH_ASSOC) ?: []);
    }

    /**
     * @param array<int,array<string,mixed>> $posts
     * @return array<int,array<string,mixed>>
     */
    private function prepare_posts(array $posts): array
    {
        foreach ($posts as &$post) {
            if (!is_array($post)) {
                continue;
            }

            $excerptSource = trim((string) ($post['excerpt'] ?? ''));
            $contentSource = trim((string) ($post['content'] ?? ''));
            $post['excerpt_plain'] = $this->plain_excerpt($excerptSource !== '' ? $excerptSource : $contentSource);
        }
        unset($post);

        return $posts;
    }

    private function plain_excerpt(string $content): string
    {
        $content = trim($content);
        if ($content === '') {
            return '';
        }

        $decoded = json_decode($content, true);
        if (is_array($decoded) && isset($decoded['blocks']) && is_array($decoded['blocks'])) {
            $parts = [];
            foreach ($decoded['blocks'] as $block) {
                if (!is_array($block)) {
                    continue;
                }
                $data = $block['data'] ?? null;
                if (!is_array($data)) {
                    continue;
                }
                foreach (['text', 'caption', 'message', 'title'] as $key) {
                    if (!empty($data[$key]) && is_string($data[$key])) {
                        $parts[] = $data[$key];
                    }
                }
                if (!empty($data['items']) && is_array($data['items'])) {
                    foreach ($data['items'] as $item) {
                        if (is_string($item) && trim($item) !== '') {
                            $parts[] = $item;
                        }
                    }
                }
            }
            $content = implode(' ', $parts);
        } elseif (str_contains($content, '"blocks"') && (str_starts_with($content, '{') || str_starts_with($content, '['))) {
            $recovered = $this->extract_malformed_editorjs_text($content);
            if ($recovered !== '') {
                $content = $recovered;
            }
        }

        $text = trim(html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }

    private function extract_malformed_editorjs_text(string $raw): string
    {
        $parts = [];
        foreach (['text', 'caption', 'message', 'title'] as $key) {
            if (preg_match_all('/"' . preg_quote($key, '/') . '"\s*:\s*"((?:\\\\.|[^"\\\\])*)"/u', $raw, $matches)) {
                foreach ($matches[1] as $value) {
                    $decoded = json_decode('"' . $value . '"');
                    if (is_string($decoded) && trim($decoded) !== '') {
                        $parts[] = $decoded;
                    }
                }
            }
        }

        if (preg_match_all('/"items"\s*:\s*\[(.*?)\]/us', $raw, $itemGroups)) {
            foreach ($itemGroups[1] as $group) {
                if (preg_match_all('/"((?:\\\\.|[^"\\\\])*)"/u', $group, $itemMatches)) {
                    foreach ($itemMatches[1] as $value) {
                        $decoded = json_decode('"' . $value . '"');
                        if (is_string($decoded) && trim($decoded) !== '') {
                            $parts[] = $decoded;
                        }
                    }
                }
            }
        }

        $text = trim(html_entity_decode(strip_tags(implode(' ', $parts)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        return preg_replace('/\s+/u', ' ', $text) ?? '';
    }
}
