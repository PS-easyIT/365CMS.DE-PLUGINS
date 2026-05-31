<?php
/**
 * CMS M365 Linkcollection – PHINIT Sidebar Widget.
 *
 * @package CMS_M365LINKCOLLECTION
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LINKCOLLECTION_Widget
{
    public static function render_phinit_sidebar_widget(string $orderStyle = ''): void
    {
        if (!CMS_M365LINKCOLLECTION_Settings::bool('sidebar_enabled', true)) {
            return;
        }

        $repo = CMS_M365LINKCOLLECTION_Repository::instance();
        $limit = CMS_M365LINKCOLLECTION_Settings::int('sidebar_limit', 8, 1, 20);
        $payload = $repo->links(['public' => true, 'featured' => true], $limit, 0);
        $items = $payload['items'];
        if ($items === []) {
            $payload = $repo->links(['public' => true], $limit, 0);
            $items = $payload['items'];
        }
        if ($items === []) {
            return;
        }

        $title = CMS_M365LINKCOLLECTION_Settings::get('sidebar_title', 'M365 Sites & Blogs');
        $interval = CMS_M365LINKCOLLECTION_Settings::int('sidebar_rotate_seconds', 7, 3, 60) * 1000;
        $showCategory = CMS_M365LINKCOLLECTION_Settings::bool('sidebar_show_category', true);
        $route = CMS_M365LINKCOLLECTION_Settings::route();
        $siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $archiveUrl = $siteUrl . $route;
        $placeholder = trim(CMS_M365LINKCOLLECTION_Settings::get('sidebar_placeholder_image', ''));
        $buttonLabel = CMS_M365LINKCOLLECTION_Settings::get('sidebar_button_label', 'Alle Links ansehen');
        $controlsLabel = CMS_M365LINKCOLLECTION_Settings::get('sidebar_controls_label', 'Linkcollection steuern');
        $prevLabel = CMS_M365LINKCOLLECTION_Settings::get('sidebar_prev_label', 'Vorherigen Link anzeigen');
        $nextLabel = CMS_M365LINKCOLLECTION_Settings::get('sidebar_next_label', 'Nächsten Link anzeigen');
        $style = CMS_M365LINKCOLLECTION_Settings::get('sidebar_style', 'card');
        if (!in_array($style, ['card', 'compact', 'minimal'], true)) {
            $style = 'card';
        }
        $showImage = CMS_M365LINKCOLLECTION_Settings::bool('sidebar_show_image', true);
        $showSubtitle = CMS_M365LINKCOLLECTION_Settings::bool('sidebar_show_subtitle', true);
        $slides = array_values(array_filter($items, static function (array $item): bool {
            return self::safe_url((string) ($item['url'] ?? '')) !== '';
        }));
        if ($slides === []) {
            return;
        }

        $safeOrderStyle = self::safe_order_style($orderStyle);
        ?>
        <div class="sb-widget sb-widget--linkcollection sb-widget--linkcollection-<?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $safeOrderStyle; ?> data-mlc-sidebar-rotator data-rotate-interval="<?php echo (int) $interval; ?>">
            <div class="sb-widget-title">
                <span class="sb-widget-title__icon" aria-hidden="true">🔗</span>
                <span class="sb-widget-title__text"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="mlc-sidebar-rotator" aria-live="polite">
                <?php foreach ($slides as $index => $item): ?>
                <?php
                $isActive = (int) $index === 0;
                $url = self::safe_url((string) ($item['url'] ?? ''));
                $image = self::safe_media_url((string) ($item['resolved_image_url'] ?? $item['image_url'] ?? ''));
                if ($image === '' && $placeholder !== '') {
                    $image = self::safe_media_url($placeholder);
                }
                $titleText = trim((string) ($item['title'] ?? ''));
                $imageAlt = trim((string) (($item['resolved_image_alt'] ?? '') !== '' ? $item['resolved_image_alt'] : $titleText));
                $category = trim((string) ($item['category_name'] ?? ''));
                $subtitle = trim((string) ($item['subtitle'] ?? ''));
                ?>
                <article class="mlc-sidebar-slide<?php echo $isActive ? ' is-active' : ''; ?>" data-mlc-sidebar-slide data-slide-index="<?php echo (int) $index; ?>" aria-hidden="<?php echo $isActive ? 'false' : 'true'; ?>">
                    <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" class="mlc-sidebar-card" target="_blank" rel="noopener noreferrer" tabindex="<?php echo $isActive ? '0' : '-1'; ?>">
                        <?php if ($showImage): ?>
                        <?php if ($image !== ''): ?>
                        <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>" class="mlc-sidebar-card__image" loading="lazy" width="260" height="132">
                        <?php else: ?>
                        <span class="mlc-sidebar-card__placeholder" aria-hidden="true"><?php echo htmlspecialchars(self::first_character($titleText !== '' ? $titleText : '?'), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <?php endif; ?>
                        <span class="mlc-sidebar-card__body">
                            <?php if ($showCategory && $category !== ''): ?>
                            <span class="mlc-sidebar-card__kicker"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <strong class="mlc-sidebar-card__title"><?php echo htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if ($showSubtitle && $subtitle !== ''): ?>
                            <span class="mlc-sidebar-card__subtitle"><?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if (count($slides) > 1): ?>
            <div class="mlc-sidebar-controls" role="group" aria-label="<?php echo htmlspecialchars($controlsLabel, ENT_QUOTES, 'UTF-8'); ?>">
                <button type="button" class="mlc-sidebar-control" data-mlc-sidebar-prev aria-label="<?php echo htmlspecialchars($prevLabel, ENT_QUOTES, 'UTF-8'); ?>">‹</button>
                <button type="button" class="mlc-sidebar-control" data-mlc-sidebar-next aria-label="<?php echo htmlspecialchars($nextLabel, ENT_QUOTES, 'UTF-8'); ?>">›</button>
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

    private static function first_character(string $value): string
    {
        return function_exists('mb_substr') ? (string) mb_substr($value, 0, 1) : substr($value, 0, 1);
    }

    private static function safe_order_style(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/^\sstyle="[^"<>]{1,200}"\s*$/', $value) !== 1) {
            return '';
        }

        return $value;
    }
}
