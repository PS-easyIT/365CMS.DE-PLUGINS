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
        ?>
        <div class="sb-widget sb-widget--linkcollection"<?php echo $orderStyle; ?> data-mlc-sidebar-rotator data-rotate-interval="<?php echo (int) $interval; ?>">
            <div class="sb-widget-title">
                <span class="sb-widget-title__icon" aria-hidden="true">🔗</span>
                <span class="sb-widget-title__text"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="mlc-sidebar-rotator" aria-live="polite">
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
                $category = trim((string) ($item['category_name'] ?? ''));
                $subtitle = trim((string) ($item['subtitle'] ?? ''));
                ?>
                <article class="mlc-sidebar-slide<?php echo $isActive ? ' is-active' : ''; ?>" data-mlc-sidebar-slide data-slide-index="<?php echo (int) $index; ?>" aria-hidden="<?php echo $isActive ? 'false' : 'true'; ?>">
                    <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" class="mlc-sidebar-card" target="_blank" rel="noopener noreferrer" tabindex="<?php echo $isActive ? '0' : '-1'; ?>">
                        <?php if ($image !== ''): ?>
                        <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8'); ?>" class="mlc-sidebar-card__image" loading="lazy" width="260" height="132">
                        <?php else: ?>
                        <span class="mlc-sidebar-card__placeholder" aria-hidden="true"><?php echo htmlspecialchars(mb_substr($titleText !== '' ? $titleText : '?', 0, 1), ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <span class="mlc-sidebar-card__body">
                            <?php if ($showCategory && $category !== ''): ?>
                            <span class="mlc-sidebar-card__kicker"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                            <strong class="mlc-sidebar-card__title"><?php echo htmlspecialchars($titleText, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <?php if ($subtitle !== ''): ?>
                            <span class="mlc-sidebar-card__subtitle"><?php echo htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            <?php if (count($items) > 1): ?>
            <div class="mlc-sidebar-controls" role="group" aria-label="Linkcollection steuern">
                <button type="button" class="mlc-sidebar-control" data-mlc-sidebar-prev aria-label="Vorherigen Link anzeigen">‹</button>
                <button type="button" class="mlc-sidebar-control" data-mlc-sidebar-next aria-label="Nächsten Link anzeigen">›</button>
            </div>
            <?php endif; ?>
            <a href="<?php echo htmlspecialchars($archiveUrl, ENT_QUOTES, 'UTF-8'); ?>" class="sb-widget-button">Alle Links ansehen</a>
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
}
