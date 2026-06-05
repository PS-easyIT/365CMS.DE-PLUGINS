<?php
/**
 * CMS M365 Message Center – Public archive.
 *
 * @package CMS_M365MessageCenter
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$settings = is_array($settings ?? null) ? $settings : [];
$result = is_array($result ?? null) ? $result : ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'sort' => 'last_modified', 'direction' => 'desc', 'search' => '', 'service' => '', 'category' => ''];
$services = is_array($services ?? null) ? $services : [];
$categories = is_array($categories ?? null) ? $categories : [];
$baseUrl = trim((string) ($baseUrl ?? '/m365-messagecenter'));
$layout = in_array((string) ($settings['public_layout'] ?? 'standard'), ['standard', 'compact', 'list'], true) ? (string) $settings['public_layout'] : 'standard';
$showDetailPages = (string) ($settings['show_detail_pages'] ?? '1') === '1';
$showStatusPanel = (string) ($settings['show_status_panel'] ?? '1') === '1';
$showFilters = (string) ($settings['show_filters'] ?? '1') === '1';
$showExcerpt = (string) ($settings['show_body_excerpt'] ?? '1') === '1';
$showExternalLinks = (string) ($settings['show_external_links'] ?? '1') === '1';
$publicMaxWidth = max(900, min(1600, (int) ($settings['public_max_width'] ?? 1160)));
$activeFilterCount = 0;
foreach (['search', 'service', 'category'] as $filterKey) {
    if (trim((string) ($result[$filterKey] ?? '')) !== '') {
        $activeFilterCount++;
    }
}

$sortOptions = [
    'last_modified' => 'Zuletzt geändert',
    'action_required' => 'Handlungsdatum',
    'category' => 'Kategorie',
    'service' => 'Service',
    'title' => 'Titel',
];
$directionOptions = ['desc' => 'Absteigend', 'asc' => 'Aufsteigend'];

$buildUrl = static function (array $params = []) use ($baseUrl, $result): string {
    $current = [
        'q' => (string) ($result['search'] ?? ''),
        'service' => (string) ($result['service'] ?? ''),
        'category' => (string) ($result['category'] ?? ''),
        'sort' => (string) ($result['sort'] ?? 'last_modified'),
        'direction' => (string) ($result['direction'] ?? 'desc'),
        'mc_page' => (string) ($result['page'] ?? 1),
    ];
    $query = array_filter(array_merge($current, $params), static fn($value): bool => trim((string) $value) !== '' && (string) $value !== '1');

    return $baseUrl . ($query !== [] ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
};

$formatDate = static function (string $value): string {
    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d.m.Y', $timestamp) : '';
};

$formatDateTime = static function (string $value): string {
    $timestamp = strtotime($value);
    return $timestamp !== false ? date('d.m.Y H:i', $timestamp) : 'Noch kein Abruf';
};

$excerptWords = static function (string $value, int $limit = 200): string {
    $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    if ($value === '') {
        return '';
    }

    $words = preg_split('/\s+/u', $value) ?: [];
    if (count($words) <= $limit) {
        return $value;
    }

    return implode(' ', array_slice($words, 0, $limit)) . ' ...';
};

$originalAdminUrl = static function (string $graphId, string $externalUrl): string {
    $externalUrl = trim($externalUrl);
    if ($externalUrl !== '') {
        $parts = parse_url($externalUrl);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (in_array($scheme, ['https', 'http'], true)) {
            return $externalUrl;
        }
    }

    $graphId = trim($graphId);
    if ($graphId === '') {
        return '';
    }

    return 'https://admin.microsoft.com/Adminportal/Home#/MessageCenter/:/messages/' . rawurlencode($graphId);
};
?>
<main class="phinit-plugin m365mc-archive m365mc-archive--layout-<?php echo $esc($layout); ?>" id="m365-messagecenter" style="--m365mc-public-max-width: <?php echo $publicMaxWidth; ?>px;">
    <header class="m365mc-hero<?php echo !$showStatusPanel ? ' m365mc-hero--single' : ''; ?>" aria-labelledby="m365mc-title">
        <div class="m365mc-hero__content">
            <?php if (trim((string) ($settings['page_overline'] ?? '')) !== ''): ?>
            <p class="phinit-overline m365mc-overline"><?php echo $esc((string) $settings['page_overline']); ?></p>
            <?php endif; ?>
            <h1 id="m365mc-title"><?php echo $esc((string) ($settings['page_title'] ?? 'M365 Message Center')); ?></h1>
            <?php if (trim((string) ($settings['page_intro'] ?? '')) !== ''): ?>
            <p class="m365mc-hero__intro"><?php echo $esc((string) $settings['page_intro']); ?></p>
            <?php endif; ?>
        </div>
        <?php if ($showStatusPanel): ?>
        <aside class="m365mc-hero__panel" aria-label="Message-Center-Status">
            <span class="m365mc-pulse" aria-hidden="true"></span>
            <dl>
                <div><dt>Cache</dt><dd><?php echo (int) ($result['total'] ?? 0); ?> Meldungen</dd></div>
                <div><dt>Aktualisiert</dt><dd><?php echo $esc($formatDateTime((string) ($settings['last_fetch_at'] ?? ''))); ?></dd></div>
                <div><dt>Sprache</dt><dd><?php echo $esc((string) ($settings['graph_language'] ?? 'de-DE')); ?></dd></div>
            </dl>
        </aside>
        <?php endif; ?>
    </header>

    <?php if ($showFilters): ?>
    <nav class="m365mc-filters phinit-card" aria-label="Message-Center-Filter">
        <div class="m365mc-filters__head">
            <div>
                <p class="m365mc-kicker">Suchen, filtern, sortieren</p>
                <h2>Relevante Änderungen schneller finden</h2>
            </div>
            <span class="m365mc-filter-count"><?php echo $activeFilterCount; ?> aktive Filter</span>
        </div>
        <form method="GET" action="<?php echo $esc($baseUrl); ?>" class="m365mc-filter-form" role="search">
            <div class="m365mc-field">
                <label for="m365mc-q">Suche</label>
                <input id="m365mc-q" name="q" type="search" value="<?php echo $esc((string) ($result['search'] ?? '')); ?>" placeholder="Titel, ID oder Inhalt">
            </div>
            <div class="m365mc-field">
                <label for="m365mc-service">Service</label>
                <select id="m365mc-service" name="service">
                    <option value="">Alle Services</option>
                    <?php foreach ($services as $service): ?>
                    <?php $service = trim((string) $service); if ($service === '') { continue; } ?>
                    <option value="<?php echo $esc($service); ?>"<?php echo (string) ($result['service'] ?? '') === $service ? ' selected' : ''; ?>><?php echo $esc($service); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="m365mc-field">
                <label for="m365mc-category">Kategorie</label>
                <select id="m365mc-category" name="category">
                    <option value="">Alle Kategorien</option>
                    <?php foreach ($categories as $category): ?>
                    <?php $category = trim((string) $category); if ($category === '') { continue; } ?>
                    <option value="<?php echo $esc($category); ?>"<?php echo (string) ($result['category'] ?? '') === $category ? ' selected' : ''; ?>><?php echo $esc($category); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="m365mc-field">
                <label for="m365mc-sort">Sortieren nach</label>
                <select id="m365mc-sort" name="sort">
                    <?php foreach ($sortOptions as $value => $label): ?>
                    <option value="<?php echo $esc($value); ?>"<?php echo (string) ($result['sort'] ?? '') === $value ? ' selected' : ''; ?>><?php echo $esc($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="m365mc-field">
                <label for="m365mc-direction">Richtung</label>
                <select id="m365mc-direction" name="direction">
                    <?php foreach ($directionOptions as $value => $label): ?>
                    <option value="<?php echo $esc($value); ?>"<?php echo (string) ($result['direction'] ?? '') === $value ? ' selected' : ''; ?>><?php echo $esc($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="m365mc-filter-actions">
                <button type="submit" class="phinit-btn phinit-btn--primary">Anwenden</button>
                <a href="<?php echo $esc($baseUrl); ?>" class="phinit-btn phinit-btn--secondary">Zurücksetzen</a>
            </div>
        </form>
    </nav>
    <?php endif; ?>

    <section class="m365mc-results" aria-labelledby="m365mc-results-title">
        <div class="m365mc-results__head">
            <div>
                <p class="m365mc-kicker">Aktualisierung täglich um 12:00 Uhr</p>
            </div>
            <p><?php echo (int) ($result['total'] ?? 0); ?> Einträge · Seite <?php echo (int) ($result['page'] ?? 1); ?> von <?php echo (int) ($result['pages'] ?? 1); ?></p>
        </div>

        <?php if (($result['items'] ?? []) !== []): ?>
        <div class="m365mc-list" role="list">
            <?php foreach ((array) $result['items'] as $item): ?>
            <?php
            $title = trim((string) ($item['title'] ?? '')) ?: 'Ohne Titel';
            $graphId = trim((string) ($item['graph_id'] ?? ''));
            $category = trim((string) ($item['category'] ?? ''));
            $severity = trim((string) ($item['severity'] ?? ''));
            $servicesList = array_values(array_filter(array_map('strval', (array) ($item['services'] ?? []))));
            $tags = array_values(array_filter(array_map('strval', (array) ($item['tags'] ?? []))));
            $lastModified = $formatDate((string) ($item['last_modified_at'] ?? ''));
            $actionRequired = $formatDate((string) ($item['action_required_at'] ?? ''));
            $externalUrl = trim((string) ($item['external_url'] ?? ''));
            $messageCenterUrl = $originalAdminUrl($graphId, $externalUrl);
            $detailUrl = $showDetailPages && $graphId !== '' ? $baseUrl . '/' . rawurlencode($graphId) : '';
            $excerpt = $excerptWords((string) ($item['body_excerpt'] ?? ''), 200);
            ?>
            <article class="m365mc-card<?php echo $detailUrl !== '' ? ' m365mc-card--linked' : ''; ?> phinit-card" role="listitem">
                <header class="m365mc-card__header">
                    <div>
                        <div class="m365mc-card__eyebrow">
                            <?php if ($category !== ''): ?><span><?php echo $esc($category); ?></span><?php endif; ?>
                            <?php if ($severity !== ''): ?><span><?php echo $esc($severity); ?></span><?php endif; ?>
                            <?php if ($graphId !== ''): ?><code><?php echo $esc($graphId); ?></code><?php endif; ?>
                        </div>
                        <h3><?php if ($detailUrl !== ''): ?><a href="<?php echo $esc($detailUrl); ?>"><?php echo $esc($title); ?></a><?php else: ?><?php echo $esc($title); ?><?php endif; ?></h3>
                    </div>
                    <?php if (!empty($item['is_major_change'])): ?>
                    <span class="m365mc-badge m365mc-badge--major">Major Change</span>
                    <?php endif; ?>
                </header>
                <dl class="m365mc-meta">
                    <?php if ($lastModified !== ''): ?><div><dt>Geändert</dt><dd><time datetime="<?php echo $esc((string) ($item['last_modified_at'] ?? '')); ?>"><?php echo $esc($lastModified); ?></time></dd></div><?php endif; ?>
                    <?php if ($actionRequired !== ''): ?><div><dt>Handlung bis</dt><dd><time datetime="<?php echo $esc((string) ($item['action_required_at'] ?? '')); ?>"><?php echo $esc($actionRequired); ?></time></dd></div><?php endif; ?>
                    <?php if ($servicesList !== []): ?><div><dt>Services</dt><dd><?php echo $esc(implode(' · ', array_slice($servicesList, 0, 3))); ?></dd></div><?php endif; ?>
                </dl>
                <?php if ($showExcerpt && $excerpt !== ''): ?>
                <p class="m365mc-excerpt"><?php echo $esc($excerpt); ?></p>
                <?php endif; ?>
                <?php if ($tags !== [] || $detailUrl !== '' || ($showExternalLinks && $messageCenterUrl !== '')): ?>
                <footer class="m365mc-card__footer">
                    <div class="m365mc-card__footer-tags">
                        <?php if ($tags !== []): ?>
                        <ul class="m365mc-tags" aria-label="Tags">
                            <?php foreach ($tags as $tag): ?><li><?php echo $esc($tag); ?></li><?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <?php if ($detailUrl !== '' || ($showExternalLinks && $messageCenterUrl !== '')): ?>
                    <div class="m365mc-card__footer-actions">
                        <?php if ($detailUrl !== ''): ?>
                        <a href="<?php echo $esc($detailUrl); ?>" class="phinit-btn phinit-btn--secondary">Details öffnen</a>
                        <?php endif; ?>
                        <?php if ($showExternalLinks && $messageCenterUrl !== ''): ?>
                        <a href="<?php echo $esc($messageCenterUrl); ?>" class="phinit-btn phinit-btn--primary m365mc-admin-link" target="_blank" rel="noopener noreferrer">Original im M365 Admin Center <span aria-hidden="true">→</span></a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </footer>
                <?php endif; ?>
            </article>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="m365mc-empty phinit-card">
            <h2>Keine Meldungen gefunden</h2>
            <p><?php echo $esc((string) ($settings['empty_text'] ?? 'Es sind noch keine Message-Center-Meldungen im lokalen Cache vorhanden.')); ?></p>
        </div>
        <?php endif; ?>
    </section>

    <?php if ((int) ($result['pages'] ?? 1) > 1): ?>
    <nav class="m365mc-pagination" aria-label="Seitennavigation">
        <?php $page = (int) ($result['page'] ?? 1); $pages = (int) ($result['pages'] ?? 1); ?>
        <?php if ($page > 1): ?><a href="<?php echo $esc($buildUrl(['mc_page' => $page - 1])); ?>" class="phinit-btn phinit-btn--secondary">← Zurück</a><?php endif; ?>
        <span>Seite <?php echo $page; ?> von <?php echo $pages; ?></span>
        <?php if ($page < $pages): ?><a href="<?php echo $esc($buildUrl(['mc_page' => $page + 1])); ?>" class="phinit-btn phinit-btn--secondary">Weiter →</a><?php endif; ?>
    </nav>
    <?php endif; ?>
</main>
