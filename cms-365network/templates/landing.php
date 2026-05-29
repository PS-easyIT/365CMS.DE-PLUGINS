<?php
/**
 * Public 365NETWORK landing template.
 *
 * @package CMS_365NETWORK
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$settings = is_array($settings ?? null) ? $settings : [];
$hubSettings = is_array($hubSettings ?? null) ? $hubSettings : [];
$areas = is_array($areas ?? null) ? $areas : [];
$events = is_array($events ?? null) ? $events : [];
$speakers = is_array($speakers ?? null) ? $speakers : [];
$companies = is_array($companies ?? null) ? $companies : [];
$experts = is_array($experts ?? null) ? $experts : [];
$toolboxTools = is_array($toolboxTools ?? null) ? $toolboxTools : [];
$stats = is_array($stats ?? null) ? $stats : [];

$choice = static function (string $key, string $default, array $allowed) use ($settings): string {
    $value = (string) ($settings[$key] ?? $default);
    return in_array($value, $allowed, true) ? $value : $default;
};
$enabled = static fn(string $key, string $default = '1'): bool => (string) ($settings[$key] ?? $default) === '1';
$hubEnabled = static fn(string $key, bool $default = true): bool => array_key_exists($key, $hubSettings) ? (bool) $hubSettings[$key] : $default;
$hubValue = static function (string $key, string $default = '') use ($hubSettings): string {
    $value = trim((string) ($hubSettings[$key] ?? ''));
    return $value !== '' ? $value : $default;
};
$hubChoice = static function (string $key, string $default, array $allowed) use ($hubSettings): string {
    $value = trim((string) ($hubSettings[$key] ?? $default));
    return in_array($value, $allowed, true) ? $value : $default;
};
$normalizeOrder = static function (string $value, array $allowed): array {
    $parts = array_filter(array_map(
        static fn(string $item): string => trim($item),
        explode(',', $value)
    ), static fn(string $item): bool => $item !== '');

    $ordered = [];
    foreach ($parts as $item) {
        if (in_array($item, $allowed, true) && !in_array($item, $ordered, true)) {
            $ordered[] = $item;
        }
    }

    foreach ($allowed as $item) {
        if (!in_array($item, $ordered, true)) {
            $ordered[] = $item;
        }
    }

    return $ordered;
};
$safeUrl = static function (mixed $value): string {
    $url = trim((string) $value);
    if ($url === '') {
        return '#';
    }
    if (preg_match('/^#[A-Za-z][A-Za-z0-9_-]*$/', $url) === 1) {
        return $url;
    }
    if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
        return $url;
    }
    $parts = parse_url($url);
    $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
    return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '#';
};
$safeIcon = static function (mixed $value): string {
    $icon = strtolower(trim((string) $value));
    if ($icon === '') {
        return 'ti-link';
    }

    if (!str_starts_with($icon, 'ti-')) {
        $icon = 'ti-' . $icon;
    }

    return preg_match('/^ti-[a-z0-9-]+$/', $icon) === 1 ? $icon : 'ti-link';
};
$safeImage = static function (mixed $value) use ($safeUrl): string {
    $raw = trim((string) $value);
    if ($raw === '') {
        return '';
    }

    if (str_starts_with($raw, '/') && !str_starts_with($raw, '//') && !str_contains($raw, "\0")) {
        return $raw;
    }

    if (filter_var($raw, FILTER_VALIDATE_URL) === false) {
        return '';
    }

    $parts = parse_url($raw);
    $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
    return in_array($scheme, ['http', 'https'], true) ? $raw : '';
};
$previewName = static function (array $item, string $type): string {
    if ($type === 'company') {
        return trim((string) ($item['name'] ?? 'Firma')) ?: 'Firma';
    }

    $name = trim((string) (($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')));
    return $name !== '' ? $name : ($type === 'speaker' ? 'Speaker' : 'Expertise-Profil');
};
$previewInitial = static function (string $name): string {
    $name = trim($name);
    if ($name === '') {
        return 'N';
    }

    return function_exists('mb_substr') ? mb_substr($name, 0, 1, 'UTF-8') : substr($name, 0, 1);
};
$eventDate = static function (array $event): string {
    $rawDate = trim((string) ($event['event_date'] ?? ''));
    if ($rawDate === '') {
        return 'Termin folgt';
    }

    $timestamp = strtotime($rawDate);
    if ($timestamp === false) {
        return $rawDate;
    }

    $label = date('d.m.Y', $timestamp);
    $time = trim((string) ($event['event_time'] ?? ''));
    if ($time !== '') {
        $label .= ' · ' . substr($time, 0, 5) . ' Uhr';
    }

    return $label;
};

$layout = $choice('layout_variant', 'grid-2x2', ['grid-2x2', 'grid-4x1', 'auto']);
$sidebarPosition = $choice('sidebar_position', 'right', ['right', 'left']);
$previewPlacement = $choice('preview_placement', 'sidebar', ['sidebar', 'below', 'off']);
$showSidebar = $enabled('show_sidebar') && $previewPlacement === 'sidebar';
$mainClass = 'cms-network-hub-wrap n365-landing n365-layout--' . $layout . ' n365-sidebar--' . $sidebarPosition . ($showSidebar ? ' n365-has-sidebar' : '');

$heroVisible = $hubEnabled('hub_hero_visible');
$statsVisible = $hubEnabled('hub_stats_visible');
$bandVisible = $hubEnabled('hub_band_visible');
$eventBandVisible = $hubEnabled('hub_band_event_visible');
$searchBandVisible = $hubEnabled('hub_band_search_visible');
$areasVisible = $hubEnabled('hub_areas_visible');
$toolboxVisible = $hubEnabled('hub_toolbox_visible');
$sectionOrderKeys = ['featured', 'hero', 'stats', 'band', 'areas', 'toolbox'];
$sectionOrder = $normalizeOrder($hubValue('hub_section_order', implode(',', $sectionOrderKeys)), $sectionOrderKeys);
$sectionOrderMap = array_flip($sectionOrder);
$sectionOrderValue = static fn(string $key): int => ((int) ($sectionOrderMap[$key] ?? 99)) + 1;
$sectionOrderStyle = static fn(string $key): string => ' style="--n365-order:' . (int) $sectionOrderValue($key) . '"';
$areaOrderKeys = ['events', 'speakers', 'companies', 'experts'];
$areaCardOrder = $normalizeOrder($hubValue('hub_area_card_order', implode(',', $areaOrderKeys)), $areaOrderKeys);
$areaOrderMap = array_flip($areaCardOrder);

$title = $hubValue('hub_hero_title', trim((string) ($settings['landing_title'] ?? '365NETWORK')) ?: '365NETWORK');
$eyebrow = $hubValue('hub_hero_label', trim((string) ($settings['hero_eyebrow'] ?? '365network Hub')));
$heroSub = $hubValue('hub_hero_sub', 'Kuratierte Events, Speaker, Firmen und Experten für Ihr Netzwerk.');
$primaryLabel = $hubValue('hub_hero_btn1_label', (string) ($settings['primary_button_label'] ?? 'Netzwerk entdecken'));
$secondaryLabel = $hubValue('hub_hero_btn2_label', (string) ($settings['secondary_button_label'] ?? 'Kontakt aufnehmen'));
$primaryUrl = $safeUrl($hubValue('hub_hero_btn1_url', (string) ($settings['primary_button_url'] ?? '/network')));
if ($primaryUrl === '#n365-areas') {
    $primaryUrl = '/network';
}
$secondaryUrl = $safeUrl($hubValue('hub_hero_btn2_url', (string) ($settings['secondary_button_url'] ?? '/kontakt')));
$featuredVisible = $hubEnabled('hub_featured_visible');
$featuredStyleSetting = $hubValue('hub_featured_style', 'auto');
$featuredStyleSetting = in_array($featuredStyleSetting, ['auto', 'text', 'image'], true) ? $featuredStyleSetting : 'auto';
$featuredWidth = $hubChoice('hub_featured_width', 'full', ['full', 'compact']);
$featuredLabel = $hubValue('hub_featured_label', 'Featured');
$featuredTitle = $hubValue('hub_featured_title', (string) ($settings['featured_title'] ?? ''));
$featuredSub = $hubValue('hub_featured_sub', (string) ($settings['featured_text'] ?? ''));
$featuredBtnLabel = $hubValue('hub_featured_btn_label', 'Mehr erfahren');
$featuredBtnUrl = $safeUrl($hubSettings['hub_featured_btn_url'] ?? '');
$featuredImage = $safeImage($hubSettings['hub_featured_image_url'] ?? '');
$featuredHasImage = $featuredImage !== '' && $featuredStyleSetting !== 'text';
$featuredClass = 'hub-featured hub-featured--width-' . $featuredWidth . ' ' . ($featuredHasImage ? 'hub-featured--image' : 'hub-featured--text');
$featuredHasContent = $featuredVisible && ($featuredTitle !== '' || $featuredSub !== '');
$heroLayout = $hubChoice('hub_hero_layout', 'left', ['left', 'center']);
$statsLayout = $hubChoice('hub_stats_layout', 'grid', ['grid', 'compact', 'inline']);
$bandLayout = $hubChoice('hub_band_layout', 'split', ['split', 'stack']);
$areasLayout = $hubChoice('hub_areas_layout', $layout, ['grid-2x2', 'grid-4x1', 'auto']);
$areasCardStyle = $hubChoice('hub_areas_card_style', 'icon-corner', ['icon-corner', 'plain']);
$toolboxLayout = $hubChoice('hub_toolbox_layout', 'grid', ['grid', 'compact']);
$belowPreviews = $previewPlacement === 'below';
$countEvents = (int) ($stats['events'] ?? 0);
$countSpeakers = (int) ($stats['speakers'] ?? 0);
$countCompanies = (int) ($stats['companies'] ?? 0);
$countExperts = (int) ($stats['experts'] ?? 0);
$areaByKey = [];
foreach ($areas as $areaItem) {
    if (!is_array($areaItem)) {
        continue;
    }
    $key = (string) ($areaItem['key'] ?? '');
    if ($key !== '') {
        $areaByKey[$key] = $areaItem;
    }
}
$areaText = static function (string $key, string $default) use ($areaByKey): string {
    $text = trim((string) ($areaByKey[$key]['text'] ?? ''));
    return $text !== '' ? $text : $default;
};
$nextEvent = isset($events[0]) && is_array($events[0]) ? $events[0] : null;
$nextEventUrl = $nextEvent !== null ? $safeUrl($nextEvent['url'] ?? '/events') : '#';
$nextEventLocation = $nextEvent !== null ? trim((string) (($nextEvent['city'] ?? '') ?: ($nextEvent['location'] ?? ''))) : '';
$eventBandDate = static function (array $event) use ($eventDate): string {
    $rawDate = trim((string) ($event['event_date'] ?? ''));
    if ($rawDate === '') {
        return 'Termin folgt';
    }

    $timestamp = strtotime($rawDate);
    if ($timestamp === false) {
        return $eventDate($event);
    }

    $days = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
    $months = ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

    return $days[(int) date('w', $timestamp)] . ', ' . date('d', $timestamp) . '. ' . $months[((int) date('n', $timestamp)) - 1];
};
$searchUrl = $safeUrl($hubValue('hub_band_search_url', (string) ($settings['search_url'] ?? '/search')));
if ($searchUrl === '#' || in_array(trim($searchUrl, '/'), ['suche'], true)) {
    $searchUrl = '/search';
}
$searchParam = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '', $hubValue('hub_band_search_param', 'q')));
$searchParam = $searchParam !== '' ? $searchParam : 'q';
$bandEventLabel = $hubValue('hub_band_event_label', 'Nächstes Event');
$bandEventCta = $hubValue('hub_band_event_cta', 'Zum Event');
$bandSearchLabel = $hubValue('hub_band_search_label', 'Netzwerk durchsuchen');
$bandSearchTitle = $hubValue('hub_band_search_title', 'Events, Speaker, Firmen und Experten finden');
$bandSearchPlaceholder = $hubValue('hub_band_search_placeholder', 'Suchbegriff eingeben ...');
$hubStats = [
    ['key' => 'events', 'href' => $safeUrl($hubValue('hub_stats_events_url', (string) ($areaByKey['events']['url'] ?? '/events'))), 'icon' => 'calendar-event', 'count' => $countEvents, 'label' => $hubValue('hub_stats_events_label', 'Events')],
    ['key' => 'speakers', 'href' => $safeUrl($hubValue('hub_stats_speakers_url', (string) ($areaByKey['speakers']['url'] ?? '/speaker'))), 'icon' => 'microphone', 'count' => $countSpeakers, 'label' => $hubValue('hub_stats_speakers_label', 'Speaker')],
    ['key' => 'companies', 'href' => $safeUrl($hubValue('hub_stats_companies_url', (string) ($areaByKey['companies']['url'] ?? '/firmen'))), 'icon' => 'building', 'count' => $countCompanies, 'label' => $hubValue('hub_stats_companies_label', 'Firmen')],
    ['key' => 'experts', 'href' => $safeUrl($hubValue('hub_stats_experts_url', (string) ($areaByKey['experts']['url'] ?? '/experten'))), 'icon' => 'users', 'count' => $countExperts, 'label' => $hubValue('hub_stats_experts_label', 'Experten')],
];
$hubAreas = [
    [
        'key' => 'events',
        'label' => $hubValue('hub_area_events_label', (string) ($areaByKey['events']['label'] ?? 'Events')),
        'desc' => $hubValue('hub_area_events_desc', $areaText('events', 'Aktuelle Termine, Konferenzen und Community-Formate mit echtem Netzwerkfaktor.')),
        'href' => $safeUrl($hubValue('hub_area_events_url', (string) ($areaByKey['events']['url'] ?? '/events'))),
        'icon' => $safeIcon($hubValue('hub_area_events_icon', 'ti-calendar-event')),
        'available' => $countEvents > 0,
        'visible' => $hubEnabled('hub_area_events_visible'),
    ],
    [
        'key' => 'speakers',
        'label' => $hubValue('hub_area_speakers_label', (string) ($areaByKey['speakers']['label'] ?? 'Speaker')),
        'desc' => $hubValue('hub_area_speakers_desc', $areaText('speakers', 'Profile mit Themen, Bühnenpraxis und passenden Stimmen für Panels oder Keynotes.')),
        'href' => $safeUrl($hubValue('hub_area_speakers_url', (string) ($areaByKey['speakers']['url'] ?? '/speaker'))),
        'icon' => $safeIcon($hubValue('hub_area_speakers_icon', 'ti-microphone')),
        'available' => $countSpeakers > 0,
        'visible' => $hubEnabled('hub_area_speakers_visible'),
    ],
    [
        'key' => 'companies',
        'label' => $hubValue('hub_area_companies_label', (string) ($areaByKey['companies']['label'] ?? 'Firmen')),
        'desc' => $hubValue('hub_area_companies_desc', $areaText('companies', 'Unternehmen, Partner und Anbieter, die im Netzwerk sichtbar werden.')),
        'href' => $safeUrl($hubValue('hub_area_companies_url', (string) ($areaByKey['companies']['url'] ?? '/firmen'))),
        'icon' => $safeIcon($hubValue('hub_area_companies_icon', 'ti-building')),
        'available' => $countCompanies > 0,
        'visible' => $hubEnabled('hub_area_companies_visible'),
    ],
    [
        'key' => 'experts',
        'label' => $hubValue('hub_area_experts_label', (string) ($areaByKey['experts']['label'] ?? 'Experten')),
        'desc' => $hubValue('hub_area_experts_desc', $areaText('experts', 'Fachprofile für Beratung, Umsetzung und Projekt-Know-how aus der Praxis.')),
        'href' => $safeUrl($hubValue('hub_area_experts_url', (string) ($areaByKey['experts']['url'] ?? '/experten'))),
        'icon' => $safeIcon($hubValue('hub_area_experts_icon', 'ti-users')),
        'available' => $countExperts > 0,
        'visible' => $hubEnabled('hub_area_experts_visible'),
    ],
];
$hubAreas = array_values(array_filter($hubAreas, static fn(array $hubArea): bool => !empty($hubArea['visible'])));
usort($hubAreas, static function (array $left, array $right) use ($areaOrderMap): int {
    $leftOrder = (int) ($areaOrderMap[(string) ($left['key'] ?? '')] ?? 99);
    $rightOrder = (int) ($areaOrderMap[(string) ($right['key'] ?? '')] ?? 99);
    return $leftOrder <=> $rightOrder;
});
$renderBand = $bandVisible && (($eventBandVisible && $nextEvent !== null) || $searchBandVisible);
$toolboxAllUrl = $safeUrl($hubValue('hub_toolbox_all_url', '/m365-tools'));
if (in_array(trim($toolboxAllUrl, '/'), ['m365toolbox', 'cms-m365tools'], true)) {
    $toolboxAllUrl = '/m365-tools';
}
$toolboxAllLabel = $hubValue('hub_toolbox_all_label', 'Alle Tools ansehen');
$toolboxLabel = $hubValue('hub_toolbox_label', 'M365 Toolbox');
$toolboxTitle = $hubValue('hub_toolbox_title', 'Tools & Ressourcen');
?>
<main class="phinit-plugin <?php echo $esc($mainClass); ?>" id="cms-365network">
    <section class="n365-shell"<?php echo $heroVisible ? ' aria-labelledby="n365-title"' : ''; ?>>
        <div class="n365-content-grid">
            <section class="n365-main"<?php echo ($areasVisible && $hubAreas !== []) ? ' aria-labelledby="n365-areas-title"' : ''; ?>>
        <?php if ($featuredHasContent): ?>
        <div class="<?php echo $esc($featuredClass); ?>"<?php echo $sectionOrderStyle('featured'); ?>>
            <?php if ($featuredHasImage): ?>
            <div class="hub-featured-img-wrap">
                <img src="<?php echo $esc($featuredImage); ?>" alt="<?php echo $esc($featuredTitle); ?>" loading="lazy" width="560" height="320">
            </div>
            <?php else: ?>
            <div class="hub-featured-accent" aria-hidden="true"></div>
            <?php endif; ?>

            <div class="hub-featured-content">
                <?php if ($featuredLabel !== ''): ?>
                <span class="hub-featured-label"><?php echo $esc($featuredLabel); ?></span>
                <?php endif; ?>
                <?php if ($featuredTitle !== ''): ?>
                <h2 class="hub-featured-title"><?php echo $esc($featuredTitle); ?></h2>
                <?php endif; ?>
                <?php if ($featuredSub !== ''): ?>
                <p class="hub-featured-sub"><?php echo $esc($featuredSub); ?></p>
                <?php endif; ?>
                <?php if ($featuredBtnUrl !== '#' && $featuredBtnLabel !== ''): ?>
                <a href="<?php echo $esc($featuredBtnUrl); ?>" class="hub-featured-btn">
                    <?php echo $esc($featuredBtnLabel); ?>
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($heroVisible): ?>
        <header class="n365-hero n365-hero--layout-<?php echo $esc($heroLayout); ?>"<?php echo $sectionOrderStyle('hero'); ?>>
            <div class="n365-hero__content">
                <?php if ($eyebrow !== ''): ?>
                <p class="hub-label"><?php echo $esc($eyebrow); ?></p>
                <?php endif; ?>
                <h1 id="n365-title"><?php echo $esc($title); ?></h1>
                <p class="hub-sub"><?php echo $esc($heroSub); ?></p>
                <nav class="hub-cta-row" aria-label="365NETWORK Aktionen">
                    <?php if ($primaryUrl !== '#' && $primaryLabel !== ''): ?>
                    <a class="btn-hub-primary" href="<?php echo $esc($primaryUrl); ?>"><?php echo $esc($primaryLabel); ?></a>
                    <?php endif; ?>
                    <?php if ($secondaryUrl !== '#' && $secondaryLabel !== ''): ?>
                    <a class="btn-hub-secondary" href="<?php echo $esc($secondaryUrl); ?>"><?php echo $esc($secondaryLabel); ?></a>
                    <?php endif; ?>
                </nav>
            </div>
        </header>
        <?php endif; ?>

        <?php if ($statsVisible): ?>
        <section class="hub-stats-section"<?php echo $sectionOrderStyle('stats'); ?> aria-label="Netzwerk Kennzahlen">
            <div class="hub-stats-grid hub-stats-grid--<?php echo $esc($statsLayout); ?>">
                <?php foreach ($hubStats as $hubStat): ?>
                <a href="<?php echo $esc($hubStat['href']); ?>" class="hub-stat-card">
                    <i class="ti ti-<?php echo $esc($hubStat['icon']); ?>" aria-hidden="true"></i>
                    <span class="hub-stat-number"><?php echo (int) $hubStat['count']; ?></span>
                    <span class="hub-stat-label"><?php echo $esc($hubStat['label']); ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if ($renderBand): ?>
        <section class="hub-band hub-band--layout-<?php echo $esc($bandLayout); ?><?php echo (!$eventBandVisible || $nextEvent === null) ? ' hub-band--search-only' : ''; ?>"<?php echo $sectionOrderStyle('band'); ?> aria-label="Netzwerk Suche und aktueller Hinweis">
            <?php if ($eventBandVisible && $nextEvent !== null): ?>
            <a href="<?php echo $esc($nextEventUrl); ?>" class="hub-band-card hub-band-card--event">
                <span class="hub-band-label"><i class="ti ti-clock" aria-hidden="true"></i> <?php echo $esc($bandEventLabel); ?></span>
                <span class="hub-band-title"><?php echo $esc($nextEvent['title'] ?? 'Event ansehen'); ?></span>
                <span class="hub-band-meta"><?php echo $esc($eventBandDate($nextEvent)); ?><?php echo $nextEventLocation !== '' ? ' · ' . $esc($nextEventLocation) : ''; ?></span>
                <span class="hub-band-cta"><?php echo $esc($bandEventCta); ?> <i class="ti ti-arrow-right" aria-hidden="true"></i></span>
            </a>
            <?php endif; ?>
            <?php if ($searchBandVisible): ?>
            <form class="hub-band-card hub-band-card--search" role="search" method="GET" action="<?php echo $esc($searchUrl); ?>" aria-label="Netzwerk durchsuchen">
                <span class="hub-band-label"><i class="ti ti-search" aria-hidden="true"></i> <?php echo $esc($bandSearchLabel); ?></span>
                <span class="hub-band-title hub-band-title--search"><?php echo $esc($bandSearchTitle); ?></span>
                <div class="hub-search-input-row">
                    <input id="hub-search-input" name="<?php echo $esc($searchParam); ?>" type="search" placeholder="<?php echo $esc($bandSearchPlaceholder); ?>" autocomplete="off" aria-label="Suchbegriff">
                    <button type="submit" id="hub-search-btn" aria-label="Suche starten"><i class="ti ti-arrow-right" aria-hidden="true"></i></button>
                </div>
            </form>
            <?php endif; ?>
        </section>
        <?php endif; ?>

                <?php if ($areasVisible && $hubAreas !== []): ?>
                <section class="hub-areas-section"<?php echo $sectionOrderStyle('areas'); ?>>
                    <div class="n365-section-head">
                        <p class="hub-section-label"><?php echo $esc($hubValue('hub_areas_label', 'Direkteinstieg')); ?></p>
                        <h2 class="hub-section-title" id="n365-areas-title"><?php echo $esc($hubValue('hub_areas_title', 'Vier Bereiche, ein Netzwerk')); ?></h2>
                    </div>

                    <div class="hub-areas-grid hub-areas-grid--<?php echo $esc($areasLayout); ?> hub-areas-grid--style-<?php echo $esc($areasCardStyle); ?>" id="n365-areas">
                        <?php foreach ($hubAreas as $hubArea): ?>
                        <a href="<?php echo empty($hubArea['available']) ? '#' : $esc($hubArea['href']); ?>" class="hub-area-card hub-area-card--<?php echo $esc($hubArea['key']); ?><?php echo empty($hubArea['available']) ? ' hub-area-card--empty' : ''; ?>"<?php echo empty($hubArea['available']) ? ' aria-disabled="true" tabindex="-1"' : ''; ?>>
                            <div class="hub-area-icon-wrap" aria-hidden="true"><i class="ti <?php echo $esc($hubArea['icon']); ?>"></i></div>
                            <div class="hub-area-body">
                                <h3 class="hub-area-title"><?php echo $esc($hubArea['label']); ?></h3>
                                <p class="hub-area-desc"><?php echo $esc($hubArea['desc']); ?></p>
                                <?php if (empty($hubArea['available'])): ?>
                                <span class="hub-area-soon">Demnächst verfügbar</span>
                                <?php else: ?>
                                <span class="hub-area-link">Alle <?php echo $esc($hubArea['label']); ?> ansehen <i class="ti ti-arrow-right" aria-hidden="true"></i></span>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($toolboxVisible && $toolboxTools !== []): ?>
                <section class="hub-toolbox hub-toolbox--layout-<?php echo $esc($toolboxLayout); ?>"<?php echo $sectionOrderStyle('toolbox'); ?> aria-labelledby="n365-toolbox-title">
                    <div class="hub-toolbox-header">
                        <div>
                            <p class="hub-section-label"><i class="ti ti-tools" aria-hidden="true"></i> <?php echo $esc($toolboxLabel); ?></p>
                            <h2 class="hub-section-title" id="n365-toolbox-title"><?php echo $esc($toolboxTitle); ?></h2>
                        </div>
                        <?php if ($toolboxAllUrl !== '#' && $toolboxAllLabel !== ''): ?>
                        <a href="<?php echo $esc($toolboxAllUrl); ?>" class="hub-toolbox-all">
                            <?php echo $esc($toolboxAllLabel); ?>
                            <i class="ti ti-arrow-right" aria-hidden="true"></i>
                        </a>
                        <?php endif; ?>
                    </div>

                    <div class="hub-toolbox-grid hub-toolbox-grid--<?php echo $esc($toolboxLayout); ?>">
                        <?php foreach ($toolboxTools as $tool): ?>
                        <a href="<?php echo htmlspecialchars((string) ($tool['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer" class="hub-toolbox-card">
                            <span class="hub-toolbox-icon" aria-hidden="true">
                                <i class="ti <?php echo htmlspecialchars((string) ($tool['icon'] ?? 'ti-link'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                            </span>

                            <span class="hub-toolbox-body">
                                <span class="hub-toolbox-label"><?php echo htmlspecialchars((string) ($tool['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (trim((string) ($tool['description'] ?? '')) !== ''): ?>
                                <span class="hub-toolbox-desc"><?php echo htmlspecialchars((string) ($tool['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </span>

                            <i class="ti ti-external-link hub-toolbox-external" aria-hidden="true"></i>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if ($belowPreviews): ?>
                <section class="n365-preview-band" aria-labelledby="n365-preview-title">
                    <div class="n365-section-head n365-section-head--compact">
                        <p class="phinit-overline">Aus dem Netzwerk</p>
                        <h2 id="n365-preview-title">Aktuelle Empfehlungen</h2>
                    </div>
                    <?php include CMS_365NETWORK_PLUGIN_DIR . 'templates/partials/previews.php'; ?>
                </section>
                <?php endif; ?>
            </section>

            <?php if ($showSidebar): ?>
            <aside class="n365-sidebar" aria-label="Netzwerk Vorschau">
                <div class="n365-sidebar__sticky">
                    <?php include CMS_365NETWORK_PLUGIN_DIR . 'templates/partials/previews.php'; ?>
                </div>
            </aside>
            <?php endif; ?>
        </div>
    </section>
</main>
