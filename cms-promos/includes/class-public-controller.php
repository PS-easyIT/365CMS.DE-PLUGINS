<?php
/**
 * @package CMS_Promos
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Promos_Public_Controller
{
    private static ?self $instance = null;

    private function __construct()
    {
        $this->register_theme_hooks();
    }

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function register_theme_hooks(): void
    {
        if (!class_exists('CMS\\Hooks')) {
            return;
        }

        \CMS\Hooks::addAction('body_start', [$this, 'render_body_start_promos'], 20);
        \CMS\Hooks::addAction('after_header', [$this, 'render_after_header_promos'], 20);
        \CMS\Hooks::addAction('home_content', [$this, 'render_home_content_promos'], 20);
        \CMS\Hooks::addAction('before_footer', [$this, 'render_before_footer_promos'], 20);
    }

    public function register_routes($router): void
    {
        $router->addRoute('GET', '/promos', [$this, 'archive_page']);
        $router->addRoute('GET', '/promos/placement/:slug', [$this, 'archive_page']);
        $router->addRoute('GET', '/promo/click/:slug', [$this, 'redirect_promo']);
    }

    public function archive_page(string $slug = ''): void
    {
        $slug = $this->normalize_slug($slug);
        $repository = CMS_Promos_Repository::instance();
        $settings = $repository->get_settings();
        $placements = $repository->get_placements();
        $currentPlacement = $slug !== '' ? $repository->get_placement_by_slug($slug) : null;
        $promos = $repository->get_active_promos($slug !== '' ? $slug : null);
        foreach ($promos as $promo) {
            $repository->increment_impression((int) ($promo['id'] ?? 0));
        }

        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;
        if ($theme !== null) {
            $theme->getHeader();
        }

        include CMS_PROMOS_PLUGIN_DIR . 'templates/archive-promos.php';

        if ($theme !== null) {
            $theme->getFooter();
        }
    }

    public function redirect_promo(string $slug): void
    {
        $slug = $this->normalize_slug($slug);
        $promo = CMS_Promos_Repository::instance()->get_promo_by_slug($slug);
        $targetUrl = $promo !== null ? $this->sanitize_redirect_url((string) ($promo['target_url'] ?? '')) : '';
        if ($promo === null || $targetUrl === '') {
            http_response_code(404);
            echo '<h1>404 – Promo nicht gefunden</h1>';
            return;
        }

        CMS_Promos_Repository::instance()->increment_click((int) $promo['id']);
        header('Location: ' . $targetUrl, true, 302);
        exit;
    }

    public function render_body_start_promos(): void
    {
        $this->render_theme_hook('body_start');
    }

    public function render_after_header_promos(): void
    {
        $this->render_theme_hook('after_header');
    }

    public function render_home_content_promos(): void
    {
        $this->render_theme_hook('home_content');
    }

    public function render_before_footer_promos(): void
    {
        $this->render_theme_hook('before_footer');
    }

    private function render_theme_hook(string $themeHook): void
    {
        $repository = CMS_Promos_Repository::instance();
        $placements = $repository->get_hook_placements($themeHook);
        if ($placements === []) {
            return;
        }

        $settings = $repository->get_settings();

        foreach ($placements as $placement) {
            $placementId = (int) ($placement['id'] ?? 0);
            if ($placementId <= 0) {
                continue;
            }

            $promos = $repository->get_active_promos_for_placement($placementId, (int) ($placement['max_items'] ?? 0));
            if ($promos === []) {
                continue;
            }

            foreach ($promos as $promo) {
                $repository->increment_impression((int) ($promo['id'] ?? 0));
            }

            echo '<section class="promos-hook-zone promos-hook-zone--' . $this->escape_attr($themeHook) . '">';
            echo '<div class="promos-hook-zone__inner">';
            echo '<div class="promos-hook-zone__header">';
            echo '<span class="promos-kicker">Promo-Platzierung</span>';
            echo '<h2 class="promos-hook-zone__title">' . $this->escape_html((string) ($placement['name'] ?? 'Promo-Platzierung')) . '</h2>';
            if (!empty($placement['description'])) {
                echo '<p class="promos-hook-zone__lead">' . $this->escape_html((string) $placement['description']) . '</p>';
            }
            echo '</div>';
            echo '<div class="promos-hook-grid">';

            foreach ($promos as $promo) {
                $buttonLabel = trim((string) ($promo['button_label'] ?? ''));
                if ($buttonLabel === '') {
                    $buttonLabel = (string) ($settings['default_button_label'] ?? 'Mehr erfahren');
                }

                $targetBehavior = ((string) ($settings['default_target_behavior'] ?? 'same_tab') === 'new_tab') ? ' target="_blank" rel="noopener noreferrer"' : '';
                $redirectUrl = !empty($promo['target_url'])
                    ? '/promo/click/' . rawurlencode((string) ($promo['slug'] ?? ''))
                    : '/promos';

                echo '<article class="promos-hook-card' . (!empty($promo['is_featured']) ? ' promos-hook-card--featured' : '') . '">';
                if (!empty($promo['image_url'])) {
                    echo '<div class="promos-hook-card__media">';
                    echo '<img src="' . $this->escape_attr((string) $promo['image_url']) . '" alt="' . $this->escape_attr((string) ($promo['title'] ?? 'Promo')) . '" loading="lazy">';
                    echo '</div>';
                }
                echo '<div class="promos-hook-card__content">';
                echo '<span class="promos-card__eyebrow">' . $this->escape_html((string) ($promo['placement_name'] ?? 'Promo')) . '</span>';
                echo '<h3 class="promos-card__title">' . $this->escape_html((string) ($promo['title'] ?? 'Promo')) . '</h3>';
                if (!empty($promo['teaser'])) {
                    echo '<p>' . $this->escape_html((string) $promo['teaser']) . '</p>';
                }
                if (!empty($promo['content_html'])) {
                    echo '<div class="promos-hook-card__body">' . $this->sanitize_public_html((string) $promo['content_html']) . '</div>';
                }
                echo '<a class="promos-card__button" href="' . $this->escape_attr($redirectUrl) . '"' . $targetBehavior . '>' . $this->escape_html($buttonLabel) . '</a>';
                echo '</div>';
                echo '</article>';
            }

            echo '</div>';
            echo '</div>';
            echo '</section>';
        }
    }

    private function escape_html(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function escape_attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function normalize_slug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        return substr($slug, 0, 120);
    }

    private function sanitize_redirect_url(string $url): string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[[:cntrl:]]/', $url) === 1) {
            return '';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        if (($parts['user'] ?? '') !== '' || ($parts['pass'] ?? '') !== '') {
            return '';
        }

        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
        if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.local')) {
            return '';
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if ($ip !== false && !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return '';
        }

        return $url;
    }

    private function sanitize_public_html(string $html): string
    {
        $html = trim(strip_tags($html, '<p><a><strong><em><ul><ol><li><br><h2><h3><h4><span>'));
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s+style\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';

        return preg_replace_callback('/\s+href\s*=\s*("([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', function (array $matches): string {
            $href = html_entity_decode((string) ($matches[2] ?? $matches[3] ?? $matches[4] ?? ''), ENT_QUOTES, 'UTF-8');
            $safeHref = $this->sanitize_redirect_url($href);
            return $safeHref !== '' ? ' href="' . $this->escape_attr($safeHref) . '"' : '';
        }, $html) ?? '';
    }
}
