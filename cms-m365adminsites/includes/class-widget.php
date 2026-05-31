<?php
/**
 * CMS M365 Adminsites – PHINIT Sidebar Widget.
 *
 * @package CMS_M365ADMINSITES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365ADMINSITES_Widget
{
    public static function render_phinit_sidebar_widget(string $orderStyle = ''): void
    {
        if (!CMS_M365ADMINSITES_Settings::bool('sidebar_enabled', true)) {
            return;
        }

        $repo = CMS_M365ADMINSITES_Repository::instance();
        $limit = CMS_M365ADMINSITES_Settings::int('sidebar_limit', 8, 1, 20);
        $payload = $repo->sites(['public' => true, 'featured' => true], $limit, 0);
        $items = $payload['items'];
        if ($items === []) {
            $payload = $repo->sites(['public' => true], $limit, 0);
            $items = $payload['items'];
        }
        if ($items === []) {
            return;
        }

        $lang = function_exists('cms_plugin_public_language')
            ? (string) cms_plugin_public_language()
            : 'de';
        $lang = $lang === 'en' ? 'en' : 'de';

        $settings = CMS_M365ADMINSITES_Settings::all();
        $i18n = static function (string $key, string $fallback = '') use ($settings, $lang): string {
            if (function_exists('cms_plugin_public_i18n_value')) {
                return (string) cms_plugin_public_i18n_value($settings, $key, $lang, $fallback);
            }
            if ($lang === 'en' && isset($settings[$key . '_en']) && $settings[$key . '_en'] !== '') {
                return (string) $settings[$key . '_en'];
            }
            if (isset($settings[$key]) && $settings[$key] !== '') {
                return (string) $settings[$key];
            }
            return $fallback;
        };

        $title = $i18n('sidebar_title', 'M365 Adminsites');
        $interval = CMS_M365ADMINSITES_Settings::int('sidebar_rotate_seconds', 7, 3, 60) * 1000;
        $showCategory = CMS_M365ADMINSITES_Settings::bool('sidebar_show_category', true);
        $route = CMS_M365ADMINSITES_Settings::route();
        $localizedRoute = function_exists('cms_plugin_public_localized_path')
            ? (string) cms_plugin_public_localized_path($route, $lang)
            : $route;
        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $archiveUrl = $siteUrl . $localizedRoute;
        $placeholder = trim(CMS_M365ADMINSITES_Settings::get('sidebar_placeholder_image', ''));
        $buttonLabel = $i18n('sidebar_button_label', 'Alle Portale ansehen');
        $controlsLabel = $i18n('sidebar_controls_label', 'Adminsites steuern');
        $prevLabel = $i18n('sidebar_prev_label', 'Vorheriges Portal anzeigen');
        $nextLabel = $i18n('sidebar_next_label', 'Nächstes Portal anzeigen');
        $style = CMS_M365ADMINSITES_Settings::get('sidebar_style', 'card');
        if (!in_array($style, ['card', 'compact', 'minimal'], true)) {
            $style = 'card';
        }
        $showImage = CMS_M365ADMINSITES_Settings::bool('sidebar_show_image', true);
        $showSubtitle = CMS_M365ADMINSITES_Settings::bool('sidebar_show_subtitle', true);
        ?>
        <div class="sb-widget sb-widget--adminsites sb-widget--adminsites-<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $orderStyle; ?> data-mas-sidebar-rotator data-rotate-interval="<?php echo (int) $interval; ?>">
            <div class="sb-widget-title">
                <span class="sb-widget-title__icon" aria-hidden="true">🧭</span>
                <span class="sb-widget-title__text"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="mas-sidebar-rotator" aria-live="polite">
                <?php foreach (array_values($items) as $index => $item): ?>
                <?php
                $isActive = (int) $index === 0;
                $url = self::safe_url((string) ($item['url'] ?? ''));
                if ($url === '') {
                    continue;
                }
                $image = self::safe_media_url((string) ($item['image_url'] ?? ''));
                if ($image === '' && $placeholder !== '') {
                    $image = self::safe_media_url($placeholder);
                }
                $titleText = trim((string) ($item['title'] ?? ''));
                $imageAlt = trim((string) (($item['image_alt'] ?? '') !== '' ? $item['image_alt'] : $titleText));
                $category = trim((string) ($item['category_name'] ?? ''));
                $subtitle = trim((string) ($item['subtitle'] ?? ''));
                ?>
                <article class="mas-sidebar-slide<?php echo $isActive ? ' is-active' : ''; ?>" data-mas-sidebar-slide data-slide-index="<?php echo (int) $index; ?>" aria-hidden="<?php echo $isActive ? 'false' : 'true'; ?>">
                    <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" class="mas-sidebar-card" target="_blank" rel="noopener noreferrer" tabindex="<?php echo $isActive ? '0' : '-1'; ?>">
                        <?php if ($showImage): ?>
                        <?php if ($image !== ''): ?>
                        <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>" class="mas-sidebar-card__image" loading="lazy" width="260" height="118">
                        <?php else: ?>
                        <span class="mas-sidebar-card__placeholder" aria-hidden="true"><?php echo htmlspecialchars(self::initial($titleText), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <?php endif; ?>
                        <span class="mas-sidebar-card__body">
                            <?php if ($showCategory && $category !== ''): ?>
                            <span class="mas-sidebar-card__kicker"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <strong class="mas-sidebar-card__title"><?php echo htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if ($showSubtitle && $subtitle !== ''): ?>
                            <span class="mas-sidebar-card__subtitle"><?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if (count($items) > 1): ?>
            <div class="mas-sidebar-controls" role="group" aria-label="<?php echo htmlspecialchars($controlsLabel, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="button" class="mas-sidebar-control" data-mas-sidebar-prev aria-label="<?php echo htmlspecialchars($prevLabel, ENT_QUOTES, 'UTF-8'); ?>">‹</button>
                <button type="button" class="mas-sidebar-control" data-mas-sidebar-next aria-label="<?php echo htmlspecialchars($nextLabel, ENT_QUOTES, 'UTF-8'); ?>">›</button>
            </div>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($archiveUrl, ENT_QUOTES, 'UTF-8'); ?>" class="sb-widget-button"><?php echo htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
        <?php
    }

    private static function safe_url(string $url): string
    {
        $url = trim($url);
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }
        return preg_match('#^https?://#i', $url) === 1 ? $url : '';
    }

    private static function safe_media_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (function_exists('phinit_normalize_public_media_url')) {
            $siteUrl = defined('SITE_URL') ? (string) SITE_URL : '';
            $url = (string) phinit_normalize_public_media_url($url, false, $siteUrl);
        }

        if (preg_match('#^(https?:)?//#i', $url) === 1 || str_starts_with($url, '/')) {
            return $url;
        }

        return '';
    }

    private static function initial(string $title): string
    {
        $clean = trim(strip_tags($title));
        if (function_exists('mb_substr')) {
            return mb_substr($clean !== '' ? $clean : '?', 0, 1);
        }
        return substr($clean !== '' ? $clean : '?', 0, 1);
    }
}
