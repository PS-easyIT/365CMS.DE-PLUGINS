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
        $promo = CMS_Promos_Repository::instance()->get_promo_by_slug($slug);
        if ($promo === null || empty($promo['target_url'])) {
            http_response_code(404);
            echo '<h1>404 – Promo nicht gefunden</h1>';
            return;
        }

        CMS_Promos_Repository::instance()->increment_click((int) $promo['id']);
        header('Location: ' . (string) $promo['target_url'], true, 302);
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
                    echo '<div class="promos-hook-card__body">' . (string) $promo['content_html'] . '</div>';
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
}
