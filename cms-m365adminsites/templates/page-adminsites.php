<?php
/**
 * CMS M365 Adminsites – Public Übersicht.
 *
 * @package CMS_M365ADMINSITES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$settings = isset($settings) && is_array($settings) ? $settings : CMS_M365ADMINSITES_Settings::all();
$categories = isset($categories) && is_array($categories) ? $categories : [];
$items = isset($items) && is_array($items) ? $items : [];
$lang = (isset($lang) && (string) $lang === 'en') ? 'en' : 'de';
$view = isset($view) ? (string) $view : 'cards';
$category = isset($category) ? (string) $category : '';
$q = isset($q) ? (string) $q : '';
$page = isset($page) ? (int) $page : 1;
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$showCards = !empty($settings['show_cards']) && $settings['show_cards'] !== '0' && in_array($view, ['cards', 'both'], true);
$showTable = !empty($settings['show_table']) && $settings['show_table'] !== '0' && in_array($view, ['table', 'both'], true);
$showImages = !empty($settings['show_images']) && $settings['show_images'] !== '0';
$visibleColumns = array_filter(array_map('trim', explode(',', (string) ($settings['visible_columns'] ?? 'image,title,subtitle,url,actions'))));
$hasColumn = static fn(string $column): bool => in_array($column, $visibleColumns, true);
$baseRoute = CMS_M365ADMINSITES_Settings::route();
$siteUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$localizedRoute = function_exists('cms_plugin_public_localized_path')
    ? (string) cms_plugin_public_localized_path($baseRoute, $lang)
    : $baseRoute;
$baseUrl = $siteUrl . $localizedRoute;
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
    if (function_exists('mb_substr')) {
        return mb_substr($clean !== '' ? $clean : '?', 0, 1);
    }
    return substr($clean !== '' ? $clean : '?', 0, 1);
};
$parseMessageCenter = static function (string $raw) use ($safeUrl): array {
    $rows = preg_split('/\R/u', trim($raw)) ?: [];
    $items = [];
    foreach ($rows as $line) {
        $parts = array_map('trim', explode('|', (string) $line, 4));
        if (count($parts) < 4) {
            continue;
        }
        [$severity, $workload, $title, $url] = $parts;
        $severity = strtolower($severity);
        if (!in_array($severity, ['high', 'medium', 'low'], true)) {
            $severity = 'medium';
        }
        if ($workload === '' || $title === '' || $safeUrl($url) === '') {
            continue;
        }
        $items[] = [
            'severity' => $severity,
            'workload' => $workload,
            'title' => $title,
            'url' => $url,
        ];
    }
    return $items;
};
$messageCenterEnabled = !empty($settings['feature_message_center_enabled']) && $settings['feature_message_center_enabled'] !== '0';
$messageCenterSeverity = strtolower(trim(strip_tags((string) ($_GET['mc_severity'] ?? ''))));
$messageCenterWorkload = trim(strip_tags((string) ($_GET['mc_workload'] ?? '')));
$messageCenterAllLabel = $i18n('feature_message_center_filter_all', 'Alle');
$messageCenterItems = $parseMessageCenter((string) ($settings['feature_message_center_items'] ?? ''));
$messageCenterWorkloads = array_values(array_unique(array_map(static fn(array $row): string => (string) $row['workload'], $messageCenterItems)));
$messageCenterMax = max(1, min(12, (int) ($settings['feature_message_center_max_items'] ?? 6)));
if ($messageCenterSeverity !== '' && !in_array($messageCenterSeverity, ['high', 'medium', 'low'], true)) {
    $messageCenterSeverity = '';
}
if ($messageCenterWorkload !== '' && !in_array($messageCenterWorkload, $messageCenterWorkloads, true)) {
    $messageCenterWorkload = '';
}
$messageCenterItems = array_values(array_filter($messageCenterItems, static function (array $item) use ($messageCenterSeverity, $messageCenterWorkload): bool {
    if ($messageCenterSeverity !== '' && $item['severity'] !== $messageCenterSeverity) {
        return false;
    }
    if ($messageCenterWorkload !== '' && $item['workload'] !== $messageCenterWorkload) {
        return false;
    }
    return true;
}));
$messageCenterItems = array_slice($messageCenterItems, 0, $messageCenterMax);
$messageCenterBuildUrl = static function (array $overrides = []) use ($buildUrl, $messageCenterSeverity, $messageCenterWorkload): string {
    $params = array_filter([
        'mc_severity' => $messageCenterSeverity,
        'mc_workload' => $messageCenterWorkload,
    ], static fn(string $value): bool => $value !== '');
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        } else {
            $params[$key] = (string) $value;
        }
    }
    return $buildUrl($params);
};
$caShortcutsEnabled = !empty($settings['feature_ca_shortcuts_enabled']) && $settings['feature_ca_shortcuts_enabled'] !== '0';
$caShortcuts = [
    [
        'label' => $i18n('feature_ca_shortcuts_identity_label', 'Preset: Identität'),
        'hint' => $i18n('feature_ca_shortcuts_identity_hint', 'Für Benutzeranmeldung, Rollenwechsel oder MFA-Fragen.'),
    ],
    [
        'label' => $i18n('feature_ca_shortcuts_app_label', 'Preset: Cloud-App'),
        'hint' => $i18n('feature_ca_shortcuts_app_hint', 'Für app-spezifische Richtlinien, z. B. Exchange Online oder SharePoint.'),
    ],
    [
        'label' => $i18n('feature_ca_shortcuts_platform_label', 'Preset: Plattform/Gerät'),
        'hint' => $i18n('feature_ca_shortcuts_platform_hint', 'Für iOS, Android, Windows und Compliance-abhängige Zugriffe.'),
    ],
];
?>
<main class="phinit-plugin mas-page" id="m365-adminsites">
    <header class="mas-header">
        <p class="phinit-overline mas-overline"><?php echo htmlspecialchars($i18n('page_overline', 'Microsoft Admin Portale'), ENT_QUOTES, 'UTF-8'); ?></p>
        <h1><?php echo htmlspecialchars($i18n('page_title', 'MS365 | Admin Sites & Portale'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php $pageIntro = $i18n('page_intro', ''); ?>
        <?php if ($pageIntro !== ''): ?>
        <p class="mas-intro"><?php echo htmlspecialchars($pageIntro, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
    </header>

    <?php if ($caShortcutsEnabled): ?>
    <section class="mas-card-section" aria-labelledby="mas-ca-shortcuts-heading">
        <h2 id="mas-ca-shortcuts-heading"><?php echo htmlspecialchars($i18n('feature_ca_shortcuts_title', 'Conditional Access What-If'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="mas-intro"><?php echo htmlspecialchars($i18n('feature_ca_shortcuts_intro', 'Schnellzugriff auf What-If-Simulationen inklusive häufiger Admin-Szenarien.'), ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="mas-grid">
            <?php foreach ($caShortcuts as $shortcut): ?>
            <article class="phinit-card mas-card">
                <div class="mas-card__body">
                    <h3><?php echo htmlspecialchars((string) $shortcut['label'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p class="mas-card__subtitle"><?php echo htmlspecialchars((string) $shortcut['hint'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <footer class="mas-card__actions">
                        <a href="https://learn.microsoft.com/en-us/entra/identity/conditional-access/what-if-tool" class="phinit-btn phinit-btn--secondary mas-btn" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($i18n('feature_ca_shortcuts_primary_label', 'What-If Tool öffnen'), ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">→</span></a>
                    </footer>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($messageCenterEnabled): ?>
    <section class="mas-card-section" aria-labelledby="mas-message-center-heading">
        <h2 id="mas-message-center-heading"><?php echo htmlspecialchars($i18n('feature_message_center_title', 'Message Center Highlights'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="mas-intro"><?php echo htmlspecialchars($i18n('feature_message_center_intro', 'Wichtige Hinweise aus dem Message Center als kuratierte Schnellübersicht.'), ENT_QUOTES, 'UTF-8'); ?></p>
        <nav class="mas-filter" aria-label="<?php echo htmlspecialchars($i18n('feature_message_center_filter_label', 'Filter'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="mas-filter-grid">
                <section class="mas-filter-card mas-filter-card--categories">
                    <span class="mas-filter-card__label"><?php echo htmlspecialchars($i18n('feature_message_center_severity_label', 'Priorität'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="mas-category-nav" role="list">
                        <a role="listitem" href="<?php echo htmlspecialchars($messageCenterBuildUrl(['mc_severity' => null]), ENT_QUOTES, 'UTF-8'); ?>" class="mas-chip mas-chip--all<?php echo $messageCenterSeverity === '' ? ' is-active' : ''; ?>"><?php echo htmlspecialchars($messageCenterAllLabel, ENT_QUOTES, 'UTF-8'); ?></a>
                        <div class="mas-category-list">
                            <?php foreach (['high', 'medium', 'low'] as $severity): ?>
                            <a role="listitem" href="<?php echo htmlspecialchars($messageCenterBuildUrl(['mc_severity' => $severity]), ENT_QUOTES, 'UTF-8'); ?>" class="mas-chip<?php echo $messageCenterSeverity === $severity ? ' is-active' : ''; ?>"><?php echo htmlspecialchars(strtoupper($severity), ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
                <section class="mas-filter-card mas-filter-card--search">
                    <span class="mas-filter-card__label"><?php echo htmlspecialchars($i18n('feature_message_center_workload_label', 'Workload'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="mas-category-nav" role="list">
                        <a role="listitem" href="<?php echo htmlspecialchars($messageCenterBuildUrl(['mc_workload' => null]), ENT_QUOTES, 'UTF-8'); ?>" class="mas-chip mas-chip--all<?php echo $messageCenterWorkload === '' ? ' is-active' : ''; ?>"><?php echo htmlspecialchars($messageCenterAllLabel, ENT_QUOTES, 'UTF-8'); ?></a>
                        <div class="mas-category-list">
                            <?php foreach ($messageCenterWorkloads as $workload): ?>
                            <a role="listitem" href="<?php echo htmlspecialchars($messageCenterBuildUrl(['mc_workload' => $workload]), ENT_QUOTES, 'UTF-8'); ?>" class="mas-chip<?php echo $messageCenterWorkload === $workload ? ' is-active' : ''; ?>"><?php echo htmlspecialchars($workload, ENT_QUOTES, 'UTF-8'); ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </div>
        </nav>
        <?php if ($messageCenterItems === []): ?>
        <section class="phinit-empty-state mas-empty" role="status" aria-live="polite">
            <p class="phinit-empty-state__title"><?php echo htmlspecialchars($i18n('feature_message_center_empty', 'Keine Highlights für den gewählten Filter.'), ENT_QUOTES, 'UTF-8'); ?></p>
        </section>
        <?php else: ?>
        <div class="mas-grid">
            <?php foreach ($messageCenterItems as $messageItem): ?>
            <article class="phinit-card mas-card">
                <div class="mas-card__body">
                    <p class="mas-card__kicker"><?php echo htmlspecialchars(strtoupper((string) $messageItem['severity']) . ' · ' . (string) $messageItem['workload'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <h3><a href="<?php echo htmlspecialchars((string) $messageItem['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) $messageItem['title'], ENT_QUOTES, 'UTF-8'); ?></a></h3>
                    <footer class="mas-card__actions">
                        <a href="<?php echo htmlspecialchars((string) $messageItem['url'], ENT_QUOTES, 'UTF-8'); ?>" class="mas-table-action" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($i18n('feature_message_center_open_label', 'Im Message Center öffnen'), ENT_QUOTES, 'UTF-8'); ?></a>
                    </footer>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
    <?php endif; ?>

    <nav class="mas-filter" aria-label="<?php echo htmlspecialchars($i18n('label_filter_nav', 'Adminsites filtern'), ENT_QUOTES, 'UTF-8'); ?>">
        <div class="mas-filter-grid">
        <?php if (!empty($settings['show_category_nav']) && $settings['show_category_nav'] !== '0'): ?>
        <section class="mas-filter-card mas-filter-card--categories" aria-label="<?php echo htmlspecialchars($i18n('label_category_nav', 'Kategorien'), ENT_QUOTES, 'UTF-8'); ?>">
            <span class="mas-filter-card__label"><?php echo htmlspecialchars($i18n('label_category_nav', 'Kategorien'), ENT_QUOTES, 'UTF-8'); ?></span>
            <div class="mas-category-nav" role="list">
                <a role="listitem" href="<?php echo htmlspecialchars($buildUrl(['category' => null, 'p' => null]), ENT_QUOTES, 'UTF-8'); ?>" class="mas-chip mas-chip--all<?php echo $category === '' ? ' is-active' : ''; ?>"><?php echo htmlspecialchars($i18n('label_all_categories', 'Alle'), ENT_QUOTES, 'UTF-8'); ?></a>
                <div class="mas-category-list">
            <?php foreach ($categories as $cat): ?>
            <?php $slug = (string) ($cat['slug'] ?? ''); ?>
            <a role="listitem" href="<?php echo htmlspecialchars($buildUrl(['category' => $slug, 'p' => null]), ENT_QUOTES, 'UTF-8'); ?>" class="mas-chip<?php echo $category === $slug ? ' is-active' : ''; ?>"><?php echo htmlspecialchars((string) ($cat['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>
        <form method="GET" action="<?php echo htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8'); ?>" role="search" class="mas-filter-card mas-filter-card--search mas-search-form">
            <?php if ($category !== ''): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>"><?php endif; ?>
            <label for="mas-q"><?php echo htmlspecialchars($i18n('label_search', 'Suchbegriff'), ENT_QUOTES, 'UTF-8'); ?></label>
            <input type="search" id="mas-q" name="q" value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars($i18n('label_search_placeholder', 'z. B. Entra, Defender, Intune, Lizenzierung'), ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="phinit-btn phinit-btn--primary mas-btn mas-btn--primary"><?php echo htmlspecialchars($i18n('label_search_button', 'Suchen'), ENT_QUOTES, 'UTF-8'); ?></button>
            <?php if ($q !== '' || $category !== ''): ?>
            <a href="<?php echo htmlspecialchars($buildUrl(['category' => null, 'q' => null, 'p' => null]), ENT_QUOTES, 'UTF-8'); ?>" class="phinit-btn phinit-btn--link mas-btn mas-btn--link"><?php echo htmlspecialchars($i18n('label_reset_button', 'Zurücksetzen'), ENT_QUOTES, 'UTF-8'); ?></a>
            <?php endif; ?>
        </form>
        </div>
    </nav>

    <?php if ($items === []): ?>
    <section class="phinit-empty-state mas-empty" role="status" aria-live="polite">
        <p class="phinit-empty-state__title"><?php echo htmlspecialchars($i18n('label_empty_title', 'Keine Portale gefunden'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="phinit-empty-state__body"><?php echo htmlspecialchars($i18n('label_empty_body', 'Bitte Filter anpassen oder die Suche zurücksetzen.'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>
    <?php endif; ?>

    <?php if ($showCards && $items !== []): ?>
    <section class="mas-card-section" aria-labelledby="mas-card-heading">
        <h2 id="mas-card-heading"><?php echo htmlspecialchars($i18n('label_cards_heading', 'Portalübersicht'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="mas-grid">
            <?php foreach ($items as $item): ?>
            <?php
            $url = $safeUrl((string) ($item['url'] ?? ''));
            if ($url === '') {
                continue;
            }
            $title = (string) ($item['title'] ?? '');
            $image = $safeMedia((string) ($item['image_url'] ?? ''));
            $imageAlt = (string) (($item['image_alt'] ?? '') !== '' ? $item['image_alt'] : $title);
            ?>
            <article class="phinit-card mas-card">
                <?php if ($showImages): ?>
                <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" class="mas-card__media" target="_blank" rel="noopener noreferrer">
                    <?php if ($image !== ''): ?>
                    <img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" width="360" height="180">
                    <?php else: ?>
                    <span class="mas-placeholder" aria-hidden="true"><?php echo htmlspecialchars($initial($title), ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
                <div class="mas-card__body">
                    <p class="mas-card__kicker"><?php echo htmlspecialchars((string) ($item['category_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                    <h3><a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a></h3>
                    <?php if (!empty($item['subtitle'])): ?><p class="mas-card__subtitle"><?php echo htmlspecialchars((string) $item['subtitle'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                    <footer class="mas-card__actions">
                        <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" class="phinit-btn phinit-btn--secondary mas-btn" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($i18n('external_button_label', 'Portal öffnen'), ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">→</span></a>
                    </footer>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($showTable && $items !== []): ?>
    <section class="mas-table-section" aria-labelledby="mas-table-heading">
        <h2 id="mas-table-heading"><?php echo htmlspecialchars($i18n('label_table_heading', 'Tabellarische Übersicht'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <div class="mas-table-wrap mas-table-wrap--<?php echo htmlspecialchars((string) ($settings['table_density'] ?? 'comfortable'), ENT_QUOTES, 'UTF-8'); ?>">
            <table class="phinit-table mas-table">
                <thead><tr>
                    <?php if ($showImages && $hasColumn('image')): ?><th><?php echo htmlspecialchars($i18n('label_table_image', 'Bild'), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?>
                    <?php if ($hasColumn('title')): ?><th><?php echo htmlspecialchars($i18n('label_table_title', 'Portal'), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?>
                    <?php if ($hasColumn('subtitle')): ?><th><?php echo htmlspecialchars($i18n('label_table_subtitle', 'Bereich'), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?>
                    <?php if ($hasColumn('url')): ?><th><?php echo htmlspecialchars($i18n('label_table_url', 'URL'), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?>
                    <?php if ($hasColumn('actions')): ?><th><?php echo htmlspecialchars($i18n('label_table_actions', 'Aktionen'), ENT_QUOTES, 'UTF-8'); ?></th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ($items as $item): ?>
                <?php $url = $safeUrl((string) ($item['url'] ?? '')); if ($url === '') { continue; } $title = (string) ($item['title'] ?? ''); $image = $safeMedia((string) ($item['image_url'] ?? '')); $imageAlt = (string) (($item['image_alt'] ?? '') !== '' ? $item['image_alt'] : $title); ?>
                <tr>
                    <?php if ($showImages && $hasColumn('image')): ?><td><?php if ($image !== ''): ?><img src="<?php echo htmlspecialchars($image, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($imageAlt, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" width="96" height="64" class="mas-table-image"><?php else: ?><span class="mas-table-placeholder" aria-hidden="true"><?php echo htmlspecialchars($initial($title), ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?></td><?php endif; ?>
                    <?php if ($hasColumn('title')): ?><td><strong><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></strong><br><small><?php echo htmlspecialchars((string) ($item['category_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></small></td><?php endif; ?>
                    <?php if ($hasColumn('subtitle')): ?><td><?php echo htmlspecialchars((string) ($item['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td><?php endif; ?>
                    <?php if ($hasColumn('url')): ?><td><a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string) parse_url($url, PHP_URL_HOST), ENT_QUOTES, 'UTF-8'); ?></a></td><?php endif; ?>
                    <?php if ($hasColumn('actions')): ?><td><div class="mas-table-actions"><a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>" class="mas-table-action" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($i18n('external_button_label', 'Portal öffnen'), ENT_QUOTES, 'UTF-8'); ?></a></div></td><?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
    <nav class="phinit-pagination mas-pagination" aria-label="<?php echo htmlspecialchars($i18n('label_pagination_nav', 'Seitennavigation'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if ($page > 1): ?><a href="<?php echo htmlspecialchars($buildUrl(['p' => (string) ($page - 1)]), ENT_QUOTES, 'UTF-8'); ?>" rel="prev" class="phinit-btn phinit-btn--secondary mas-btn"><?php echo htmlspecialchars($i18n('label_pagination_prev', '← Zurück'), ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?>
        <span class="mas-pagination__info"><?php echo htmlspecialchars($i18n('label_pagination_page', 'Seite'), ENT_QUOTES, 'UTF-8'); ?> <?php echo (int) $page; ?> <?php echo htmlspecialchars($i18n('label_pagination_of', 'von'), ENT_QUOTES, 'UTF-8'); ?> <?php echo (int) $totalPages; ?></span>
        <?php if ($page < $totalPages): ?><a href="<?php echo htmlspecialchars($buildUrl(['p' => (string) ($page + 1)]), ENT_QUOTES, 'UTF-8'); ?>" rel="next" class="phinit-btn phinit-btn--secondary mas-btn"><?php echo htmlspecialchars($i18n('label_pagination_next', 'Weiter →'), ENT_QUOTES, 'UTF-8'); ?></a><?php endif; ?>
    </nav>
    <?php endif; ?>
</main>
