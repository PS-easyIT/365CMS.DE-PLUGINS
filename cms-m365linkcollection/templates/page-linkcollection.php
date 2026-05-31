<?php
/**
 * CMS M365 Linkcollection – Public Übersicht.
 *
 * @package CMS_M365LINKCOLLECTION
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$settings = isset($settings) && is_array($settings) ? $settings : CMS_M365LINKCOLLECTION_Settings::all();
$categories = isset($categories) && is_array($categories) ? $categories : [];
$items = isset($items) && is_array($items) ? $items : [];
$repo = isset($repo) && $repo instanceof CMS_M365LINKCOLLECTION_Repository ? $repo : CMS_M365LINKCOLLECTION_Repository::instance();
$lang = isset($lang) && in_array($lang, ['de', 'en'], true) ? $lang : (function_exists('cms_plugin_public_language') ? cms_plugin_public_language() : 'de');
$mlcText = static function (string $key, string $fallback = '') use ($settings, $lang): string {
    if (function_exists('cms_plugin_public_i18n_value')) {
        return cms_plugin_public_i18n_value($settings, $key, $lang, $fallback);
    }
    if ($lang === 'en' && isset($settings[$key . '_en']) && $settings[$key . '_en'] !== '') {
        return (string) $settings[$key . '_en'];
    }
    if (isset($settings[$key]) && $settings[$key] !== '') {
        return (string) $settings[$key];
    }

    return $fallback;
};
$view = isset($view) ? (string) $view : 'cards';
$category = isset($category) ? (string) $category : '';
$q = isset($q) ? (string) $q : '';
$page = isset($page) ? (int) $page : 1;
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$total = isset($total) ? (int) $total : count($items);
$structuredDataJson = isset($structuredDataJson) ? (string) $structuredDataJson : '';
$showCards = !empty($settings['show_cards']) && $settings['show_cards'] !== '0' && in_array($view, ['cards', 'both'], true);
$showTable = !empty($settings['show_table']) && $settings['show_table'] !== '0' && in_array($view, ['table', 'both'], true);
$showImages = !empty($settings['show_images']) && $settings['show_images'] !== '0';
$accessibilityValidationMode = !empty($settings['accessibility_validation_mode']) && $settings['accessibility_validation_mode'] !== '0';
$visibleColumns = array_filter(array_map('trim', explode(',', (string) ($settings['visible_columns'] ?? 'image,title,subtitle,url,actions'))));
$hasColumn = static fn(string $column): bool => in_array($column, $visibleColumns, true);
$baseRoute = CMS_M365LINKCOLLECTION_Settings::route();
if (function_exists('cms_plugin_public_localized_path')) {
    $baseRoute = cms_plugin_public_localized_path(trim($baseRoute, '/'), $lang);
}
$siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$baseUrl = $siteUrl . $baseRoute;
$resultsCountTemplate = $mlcText('label_results_count', '%d links found');
$resultsCountText = str_replace('%d', (string) $total, $resultsCountTemplate);
$buildUrl = static function (array $overrides = []) use ($baseUrl, $category, $q, $page): string {
    $query = array_filter([
        'category' => $category,
        'q' => $q,
        'p' => $page > 1 ? (string) $page : '',
    ], static fn(string $value): bool => $value !== '');
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = (string) $value;
        }
    }
    return $baseUrl . ($query !== [] ? '?' . http_build_query($query) : '');
};
$safeUrl = static function (string $url): string {
    $url = trim($url);
    if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false || preg_match('#^https?://#i', $url) !== 1) {
        return '';
    }
    return $url;
};
$safeNavUrl = static function (string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (str_starts_with($url, '/')) {
        return $url;
    }
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return '';
    }

    return preg_match('#^https?://#i', $url) === 1 ? $url : '';
};
$safeMedia = static function (string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }
    if (function_exists('phinit_normalize_public_media_url')) {
        $url = (string) phinit_normalize_public_media_url($url, false, defined('SITE_URL') ? (string) SITE_URL : '');
    }
    if (str_starts_with($url, '/') || preg_match('#^https?://#i', $url) === 1) {
        return $url;
    }
    return '';
};
$initial = static function (string $title): string {
    $clean = trim(strip_tags($title));
    $text = $clean !== '' ? $clean : '?';

    return function_exists('mb_substr') ? (string) mb_substr($text, 0, 1) : substr($text, 0, 1);
};
$tableDensity = in_array((string) ($settings['table_density'] ?? 'comfortable'), ['comfortable', 'compact'], true)
    ? (string) ($settings['table_density'] ?? 'comfortable')
    : 'comfortable';
?>
<?php if ($structuredDataJson !== ''): ?>
<script type="application/ld+json"><?php echo htmlspecialchars($structuredDataJson, ENT_NOQUOTES, 'UTF-8'); ?></script>
<?php endif; ?>
<main class="phinit-plugin mlc-page" id="m365-linkcollection" data-mlc-a11y-validation="<?php echo $accessibilityValidationMode ? '1' : '0'; ?>" data-mlc-total-results="<?php echo (int) $total; ?>">
    <p class="mlc-visually-hidden" data-mlc-results-status role="status" aria-live="polite"><?php echo htmlspecialchars($resultsCountText, ENT_QUOTES, 'UTF-8'); ?></p>
    <header class="mlc-header">
        <p class="phinit-overline mlc-overline"><?php echo htmlspecialchars($mlcText('page_overline', 'MS365 Linkverzeichnis'), ENT_QUOTES, 'UTF-8'); ?></p>
        <h1><?php echo htmlspecialchars($mlcText('page_title', 'MS365 | SITES & BLOGS'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php $introText = $mlcText('page_intro', (string) ($settings['page_intro'] ?? '')); ?>
        <?php if ($introText !== ''): ?>
        <p class="mlc-intro"><?php echo htmlspecialchars($introText, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </header>

    <nav class="mlc-filter" aria-label="<?php echo htmlspecialchars($mlcText('label_filter_nav', 'Linkcollection filtern'), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="mlc-filter-grid">
        <?php if (!empty($settings['show_category_nav']) && $settings['show_category_nav'] !== '0'): ?>
        <section class="mlc-filter-card mlc-filter-card--categories" aria-label="<?php echo htmlspecialchars($mlcText('label_category_nav', 'Kategorien'), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="mlc-filter-card__label"><?php echo htmlspecialchars($mlcText('label_category_nav', 'Kategorien'), ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="mlc-category-nav" role="list">
                <a role="listitem" href="<?php echo htmlspecialchars($buildUrl(['category' => null, 'p' => null]), ENT_QUOTES, 'UTF-8'); ?>" class="mlc-chip mlc-chip--all<?php echo $category === '' ? ' is-active' : ''; ?>"><?php echo htmlspecialchars($mlcText('label_all_categories', 'Alle'), ENT_QUOTES, 'UTF-8'); ?></a>
                <div class="mlc-category-list">
            <?php foreach ($categories as $cat): ?>
            <?php $slug = (string) ($cat['slug'] ?? ''); ?>
            <a role="listitem" href="<?php echo htmlspecialchars($buildUrl(['category' => $slug, 'p' => null]), ENT_QUOTES, 'UTF-8'); ?>" class="mlc-chip<?php echo $category === $slug ? ' is-active' : ''; ?>"><?php echo htmlspecialchars((string) ($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        <form method="GET" action="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>" role="search" class="mlc-filter-card mlc-filter-card--search mlc-search-form">
            <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
            <label for="mlc-q"><?php echo htmlspecialchars($mlcText('label_search', 'Suchbegriff'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input type="search" id="mlc-q" name="q" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars($mlcText('label_search_placeholder', 'z. B. Intune, MVP, Security'), ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="phinit-btn phinit-btn--primary mlc-btn mlc-btn--primary"><?php echo htmlspecialchars($mlcText('label_search_button', 'Suchen'), ENT_QUOTES, 'UTF-8'); ?></button>
            <?php if ($q !== '' || $category !== ''): ?>
            <a href="<?php echo htmlspecialchars($buildUrl(['category' => null, 'q' => null, 'p' => null]), ENT_QUOTES, 'UTF-8'); ?>" class="phinit-btn phinit-btn--link mlc-btn mlc-btn--link"><?php echo htmlspecialchars($mlcText('label_reset_button', 'Zurücksetzen'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </form>
        </div>
    </nav>

    <?php if ($items === []): ?>
    <section class="phinit-empty-state mlc-empty" role="status" aria-live="polite">
        <p class="phinit-empty-state__title"><?php echo htmlspecialchars($mlcText('label_empty_title', 'Keine Links gefunden'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="phinit-empty-state__body"><?php echo htmlspecialchars($mlcText('label_empty_body', 'Bitte Filter anpassen oder die Suche zurücksetzen.'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>
    <?php endif; ?>

    <?php if ($showCards && $items !== []): ?>
    <section class="mlc-card-section" aria-labelledby="mlc-card-heading">
        <h2 id="mlc-card-heading"><?php echo htmlspecialchars($mlcText('label_cards_heading', 'Linkübersicht'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="mlc-grid">
            <?php foreach ($items as $item): ?>
            <?php
            $url = $safeUrl((string) ($item['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $title = (string) ($item['title'] ?? '');
            $image = $safeMedia((string) ($item['resolved_image_url'] ?? $item['image_url'] ?? ''));
            $imageAlt = (string) (($item['resolved_image_alt'] ?? '') !== '' ? $item['resolved_image_alt'] : (($item['image_alt'] ?? '') !== '' ? $item['image_alt'] : $title));
            $buttons = $repo->related_buttons($item);
            ?>
            <article class="phinit-card mlc-card">
                <?php if ($showImages): ?>
                <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" class="mlc-card__media" target="_blank" rel="noopener noreferrer">
                    <?php if ($image !== ''): ?>
                    <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" width="360" height="180">
                    <?php else: ?>
                    <span class="mlc-placeholder" aria-hidden="true"><?php echo htmlspecialchars($initial($title), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                <div class="mlc-card__body">
                    <p class="mlc-card__kicker"><?php echo htmlspecialchars((string) ($item['category_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <h3><a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                    <?php if (!empty($item['subtitle'])): ?><p class="mlc-card__subtitle"><?php echo htmlspecialchars((string) $item['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                    <footer class="mlc-card__actions">
                        <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" class="phinit-btn phinit-btn--secondary mlc-btn" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($mlcText('external_button_label', 'Site öffnen'), ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">→</span></a>
                        <?php foreach ($buttons as $button): ?>
                        <?php $buttonUrl = $safeNavUrl((string) ($button['url'] ?? '')); if ($buttonUrl === '') { continue; } ?>
                        <?php
                        $buttonType = (string) ($button['type'] ?? '');
                        $buttonLabel = (string) ($button['label'] ?? '');
                        if ($buttonType === 'company') {
                            $buttonLabel = $mlcText('company_button_label', $buttonLabel !== '' ? $buttonLabel : 'Company ansehen');
                        } elseif ($buttonType === 'speaker') {
                            $buttonLabel = $mlcText('speaker_button_label', $buttonLabel !== '' ? $buttonLabel : 'Speaker-Profil');
                        } elseif ($buttonType === 'expert') {
                            $buttonLabel = $mlcText('expert_button_label', $buttonLabel !== '' ? $buttonLabel : 'Expert-Profil');
                        }
                        ?>
                        <a href="<?php echo htmlspecialchars($buttonUrl, ENT_QUOTES, 'UTF-8'); ?>" class="phinit-btn phinit-btn--link mlc-btn mlc-btn--<?php echo htmlspecialchars($buttonType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php endforeach; ?>
                    </footer>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($showTable && $items !== []): ?>
    <section class="mlc-table-section" aria-labelledby="mlc-table-heading">
        <h2 id="mlc-table-heading"><?php echo htmlspecialchars($mlcText('label_table_heading', 'Tabellarische Übersicht'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="mlc-table-wrap mlc-table-wrap--<?php echo htmlspecialchars($tableDensity, ENT_QUOTES, 'UTF-8'); ?>">
            <table class="phinit-table mlc-table">
                <thead><tr>
                    <?php if ($showImages && $hasColumn('image')): ?><th><?php echo htmlspecialchars($mlcText('label_table_image', 'Bild'), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?>
                    <?php if ($hasColumn('title')): ?><th><?php echo htmlspecialchars($mlcText('label_table_title', 'Name'), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?>
                    <?php if ($hasColumn('subtitle')): ?><th><?php echo htmlspecialchars($mlcText('label_table_subtitle', 'Schwerpunkt'), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?>
                    <?php if ($hasColumn('url')): ?><th><?php echo htmlspecialchars($mlcText('label_table_url', 'URL'), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?>
                    <?php if ($hasColumn('actions')): ?><th><?php echo htmlspecialchars($mlcText('label_table_actions', 'Aktionen'), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <?php $url = $safeUrl((string) ($item['url'] ?? '')); if ($url === '') { continue; } $title = (string) ($item['title'] ?? ''); $image = $safeMedia((string) ($item['resolved_image_url'] ?? $item['image_url'] ?? '')); $imageAlt = (string) (($item['resolved_image_alt'] ?? '') !== '' ? $item['resolved_image_alt'] : (($item['image_alt'] ?? '') !== '' ? $item['image_alt'] : $title)); $buttons = $repo->related_buttons($item); ?>
                <tr>
                    <?php if ($showImages && $hasColumn('image')): ?><td><?php if ($image !== ''): ?><img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" width="96" height="64" class="mlc-table-image"><?php else: ?><span class="mlc-table-placeholder" aria-hidden="true"><?php echo htmlspecialchars($initial($title), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></td><?php endif; ?>
                    <?php if ($hasColumn('title')): ?><td><strong><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></strong><br><small><?php echo htmlspecialchars((string) ($item['category_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small></td><?php endif; ?>
                    <?php if ($hasColumn('subtitle')): ?><td><?php echo htmlspecialchars((string) ($item['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><?php endif; ?>
                    <?php if ($hasColumn('url')): ?><td><a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) parse_url($url, PHP_URL_HOST), ENT_QUOTES, 'UTF-8'); ?></a></td><?php endif; ?>
                    <?php if ($hasColumn('actions')): ?><td><div class="mlc-table-actions"><a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" class="mlc-table-action" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($mlcText('external_button_label', 'Site öffnen'), ENT_QUOTES, 'UTF-8'); ?></a><?php foreach ($buttons as $button): ?><?php $buttonUrl = $safeNavUrl((string) ($button['url'] ?? '')); if ($buttonUrl === '') { continue; } ?><?php $buttonType = (string) ($button['type'] ?? ''); $buttonLabel = (string) ($button['label'] ?? ''); if ($buttonType === 'company') { $buttonLabel = $mlcText('company_button_label', $buttonLabel !== '' ? $buttonLabel : 'Company ansehen'); } elseif ($buttonType === 'speaker') { $buttonLabel = $mlcText('speaker_button_label', $buttonLabel !== '' ? $buttonLabel : 'Speaker-Profil'); } elseif ($buttonType === 'expert') { $buttonLabel = $mlcText('expert_button_label', $buttonLabel !== '' ? $buttonLabel : 'Expert-Profil'); } ?><a href="<?php echo htmlspecialchars($buttonUrl, ENT_QUOTES, 'UTF-8'); ?>" class="mlc-table-action"><?php echo htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8'); ?></a><?php endforeach; ?></div></td><?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
    <nav class="phinit-pagination mlc-pagination" aria-label="<?php echo htmlspecialchars($mlcText('label_pagination_nav', 'Seitennavigation'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($page > 1): ?><a href="<?php echo htmlspecialchars($buildUrl(['p' => (string) ($page - 1)]), ENT_QUOTES, 'UTF-8'); ?>" rel="prev" class="phinit-btn phinit-btn--secondary mlc-btn"><?php echo htmlspecialchars($mlcText('label_pagination_prev', '← Zurück'), ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?>
        <span class="mlc-pagination__info"><?php echo htmlspecialchars($mlcText('label_pagination_page', 'Seite'), ENT_QUOTES, 'UTF-8'); ?> <?php echo (int) $page; ?> <?php echo htmlspecialchars($mlcText('label_pagination_of', 'von'), ENT_QUOTES, 'UTF-8'); ?> <?php echo (int) $totalPages; ?></span>
        <?php if ($page < $totalPages): ?><a href="<?php echo htmlspecialchars($buildUrl(['p' => (string) ($page + 1)]), ENT_QUOTES, 'UTF-8'); ?>" rel="next" class="phinit-btn phinit-btn--secondary mlc-btn"><?php echo htmlspecialchars($mlcText('label_pagination_next', 'Weiter →'), ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?>
    </nav>
    <?php endif; ?>
</main>
