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
$partnerCompanies = is_array($partnerCompanies ?? null) ? $partnerCompanies : [];
$partnerExperts = is_array($partnerExperts ?? null) ? $partnerExperts : [];
$toolboxTools = is_array($toolboxTools ?? null) ? $toolboxTools : [];
$latestPostsLimit = max(1, min(6, (int) ($hubSettings['hub_posts_limit'] ?? 6)));
$latestPosts = is_array($latestPosts ?? null) ? array_slice(array_values(array_filter($latestPosts, 'is_array')), 0, $latestPostsLimit) : [];
$stats = is_array($stats ?? null) ? $stats : [];

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
    $parts = array_filter(array_map(static fn(string $item): string => trim($item), explode(',', $value)), static fn(string $item): bool => $item !== '');
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
$safeImage = static function (mixed $value): string {
    $raw = trim(strip_tags((string) $value));
    $raw = str_replace('\\', '/', $raw);
    $raw = (string) preg_replace('/[\x00-\x1F\x7F]+/u', '', $raw);
    if ($raw === '') {
        return '';
    }
    if (str_starts_with($raw, './')) {
        $raw = substr($raw, 2);
    }
    if (preg_match('#^(?:uploads|media)(?:/|$)#i', $raw) === 1 || preg_match('#^media-file(?:\?|$)#i', $raw) === 1) {
        $raw = '/' . ltrim($raw, '/');
    }
    if (str_starts_with($raw, '/') && !str_starts_with($raw, '//') && !str_contains($raw, '..')) {
        return str_replace(' ', '%20', $raw);
    }
    $raw = str_replace(' ', '%20', $raw);
    if (filter_var($raw, FILTER_VALIDATE_URL) === false) {
        return '';
    }
    $parts = parse_url($raw);
    $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';
    return in_array($scheme, ['http', 'https'], true) ? $raw : '';
};
$cssColor = static function (mixed $value, string $default): string {
    $color = trim((string) $value);
    return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) === 1 ? $color : $default;
};
$icon = static function (string $name): string {
    $icons = [
        'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="m20 20-3.2-3.2"></path></svg>',
        'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14"></path><path d="m13 6 6 6-6 6"></path></svg>',
        'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 9h18"></path><path d="M8 3v4"></path><path d="M16 3v4"></path></svg>',
        'microphone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3Z"></path><path d="M5 11a7 7 0 0 0 14 0"></path><path d="M12 18v4"></path></svg>',
        'layers' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2 10 5-10 5L2 7l10-5Z"></path><path d="m2 12 10 5 10-5"></path><path d="m2 17 10 5 10-5"></path></svg>',
        'building' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18"></path><path d="M5 21V7l8-4v18"></path><path d="M19 21V11l-6-3"></path></svg>',
        'pin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
        'check' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m20 6-11 11-5-5"></path></svg>',
        'chevron-left' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>',
        'chevron-right' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>',
        'tool' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18v3h3l6.3-6.3a4 4 0 0 0 5.4-5.4l-2.8 2.8-2-2 2.8-2.8Z"></path></svg>',
    ];
    return $icons[$name] ?? $icons['arrow'];
};
$areaIconName = static function (string $iconClass, string $key): string {
    $iconClass = strtolower($iconClass);
    if (str_contains($iconClass, 'calendar')) {
        return 'calendar';
    }
    if (str_contains($iconClass, 'microphone')) {
        return 'microphone';
    }
    if (str_contains($iconClass, 'building')) {
        return 'building';
    }
    if (str_contains($iconClass, 'layer') || str_contains($iconClass, 'star') || str_contains($iconClass, 'user')) {
        return 'layers';
    }
    return match ($key) {
        'events' => 'calendar',
        'speakers' => 'microphone',
        'companies' => 'building',
        'experts' => 'layers',
        default => 'arrow',
    };
};
$initials = static function (string $name): string {
    $name = trim((string) preg_replace('/\s+/', ' ', $name));
    if ($name === '') {
        return 'N';
    }
    $parts = explode(' ', $name);
    $letters = '';
    foreach ($parts as $part) {
        $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
        if (strlen($letters) >= 2) {
            break;
        }
    }
    return strtoupper($letters ?: 'N');
};
$personName = static function (array $item, string $fallback): string {
    $name = trim((string) (($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')));
    return $name !== '' ? $name : $fallback;
};
$eventParts = static function (array $event): array {
    $months = ['JAN', 'FEB', 'MÄR', 'APR', 'MAI', 'JUN', 'JUL', 'AUG', 'SEP', 'OKT', 'NOV', 'DEZ'];
    $raw = trim((string) ($event['event_date'] ?? ''));
    $timestamp = $raw !== '' ? strtotime($raw) : false;
    if ($timestamp === false) {
        return ['day' => '–', 'month' => 'TBA', 'year' => '', 'iso' => '', 'label' => 'Termin folgt'];
    }
    return [
        'day' => date('d', $timestamp),
        'month' => $months[((int) date('n', $timestamp)) - 1],
        'year' => date('Y', $timestamp),
        'iso' => date('Y-m-d', $timestamp),
        'label' => date('d.m.Y', $timestamp),
    ];
};
$eventLocation = static fn(array $event): string => trim((string) (($event['city'] ?? '') ?: ($event['location'] ?? '')));
$chipList = static function (string $value, int $limit = 2): array {
    $parts = preg_split('/[,;|]+/', $value) ?: [];
    $chips = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part !== '' && !in_array($part, $chips, true)) {
            $chips[] = $part;
        }
        if (count($chips) >= $limit) {
            break;
        }
    }
    return $chips;
};
$highlightTitle = static function (string $title) use ($esc): string {
    $terms = ['Speaker', 'Experts', 'Unternehmen', 'Events'];
    $pattern = '/\b(' . implode('|', array_map(static fn(string $term): string => preg_quote($term, '/'), $terms)) . ')\b/u';
    $parts = preg_split($pattern, $title, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$title];
    $html = '';
    foreach ($parts as $part) {
        $html .= in_array($part, $terms, true) ? '<span class="n365-hl">' . $esc($part) . '</span>' : $esc($part);
    }
    return $html;
};
$searchUrl = $safeUrl($networkSearchUrl ?? '/365network/search');
$searchUrl = $searchUrl === '#' ? '/365network/search' : $searchUrl;
$blogUrl = $safeUrl($hubValue('hub_posts_all_url', 'https://phinit.de/blog'));
$blogUrl = $blogUrl === '#' ? 'https://phinit.de/blog' : $blogUrl;
$blogButtonLabel = $hubValue('hub_posts_hero_button_label', 'Blog Beiträge');
$searchParam = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '', $hubValue('hub_band_search_param', 'q')));
$searchParam = $searchParam !== '' ? $searchParam : 'q';

$countEvents = (int) ($stats['events'] ?? 0);
$countSpeakers = (int) ($stats['speakers'] ?? 0);
$countCompanies = (int) ($stats['companies'] ?? 0);
$countExperts = (int) ($stats['experts'] ?? 0);
$areaByKey = [];
foreach ($areas as $areaItem) {
    if (is_array($areaItem) && isset($areaItem['key'])) {
        $areaByKey[(string) $areaItem['key']] = $areaItem;
    }
}
$areaCounts = ['events' => $countEvents, 'speakers' => $countSpeakers, 'companies' => $countCompanies, 'experts' => $countExperts];
$areaOrderKeys = ['events', 'speakers', 'experts', 'companies'];
$areaOrder = $normalizeOrder($hubValue('hub_area_card_order', implode(',', $areaOrderKeys)), $areaOrderKeys);
$hubAreas = [];
foreach ($areaOrder as $key) {
    if (!$hubEnabled('hub_area_' . $key . '_visible')) {
        continue;
    }
    $fallback = $areaByKey[$key] ?? [];
    $hubAreas[] = [
        'key' => $key,
        'label' => $hubValue('hub_area_' . $key . '_label', (string) ($fallback['label'] ?? ucfirst($key))),
        'desc' => $hubValue('hub_area_' . $key . '_desc', (string) ($fallback['text'] ?? '')),
        'href' => $safeUrl($hubValue('hub_area_' . $key . '_url', (string) ($fallback['url'] ?? '/' . $key))),
        'icon' => $areaIconName($hubValue('hub_area_' . $key . '_icon', (string) ($fallback['icon'] ?? '')), $key),
        'count' => (int) ($areaCounts[$key] ?? 0),
    ];
}

$partnerLogoItems = array_slice($partnerCompanies !== [] ? $partnerCompanies : $companies, 0, max(1, (int) ($hubSettings['hub_partnerband_limit'] ?? 4)));
$nextEvents = array_slice(array_values(array_filter($events, 'is_array')), 0, max(1, (int) ($hubSettings['hub_next_events_limit'] ?? 3)));
$partnerColumnCompanies = array_slice($partnerCompanies !== [] ? $partnerCompanies : $companies, 0, max(1, (int) ($hubSettings['hub_partner_companies_limit'] ?? 3)));
$partnerColumnExperts = array_slice($partnerExperts !== [] ? $partnerExperts : $experts, 0, max(1, (int) ($hubSettings['hub_partner_experts_limit'] ?? 3)));

$spotlightSources = [
    'event' => array_values(array_filter($events, 'is_array')),
    'company' => array_values(array_filter($partnerCompanies !== [] ? $partnerCompanies : $companies, 'is_array')),
    'speaker' => array_values(array_filter($speakers, 'is_array')),
    'expert' => array_values(array_filter($partnerExperts !== [] ? $partnerExperts : $experts, 'is_array')),
];
$spotlightItems = [];
$spotlightLimit = max(1, (int) ($hubSettings['hub_spotlight_limit'] ?? 7));
while (count($spotlightItems) < $spotlightLimit) {
    $added = false;
    foreach ($spotlightSources as $type => $sourceItems) {
        $item = array_shift($sourceItems);
        $spotlightSources[$type] = $sourceItems;
        if (!is_array($item)) {
            continue;
        }
        $spotlightItems[] = ['type' => $type, 'item' => $item];
        $added = true;
        if (count($spotlightItems) >= $spotlightLimit) {
            break;
        }
    }
    if (!$added) {
        break;
    }
}

$sectionOrderKeys = ['partnerband', 'hero', 'areas', 'next-events', 'spotlight', 'partner-columns', 'toolbox', 'featured', 'stats', 'band', 'posts'];
$sectionOrder = $normalizeOrder($hubValue('hub_section_order', implode(',', $sectionOrderKeys)), $sectionOrderKeys);
if (in_array('partnerband', $sectionOrder, true)) {
    $sectionOrder = array_values(array_diff($sectionOrder, ['partnerband']));
    array_unshift($sectionOrder, 'partnerband');
}
$mainClass = 'cms-network-hub-wrap n365-landing n365-preview-layout';
$heroTitle = $hubValue('hub_hero_title', 'Finde Speaker, Experts, Unternehmen und Events');
$heroSub = $hubValue('hub_hero_sub', 'Ein zentraler Hub, der die Microsoft-365-Community verbindet — gebündelt, durchsuchbar, an einem Ort.');
$heroLabel = trim((string) ($hubSettings['hub_hero_label'] ?? ''));
$heroImage = $safeImage($hubValue('hub_hero_image_url', ''));
$heroLayout = $hubChoice('hub_hero_layout', 'center', ['center', 'image-right', 'image-left']);
$heroImageMode = $hubChoice('hub_hero_image_mode', 'replace-title', ['replace-title', 'with-title']);
$heroImageWidth = max(80, min(1200, (int) ($hubSettings['hub_hero_image_width'] ?? 250)));
$heroImageHeight = max(80, min(800, (int) ($hubSettings['hub_hero_image_height'] ?? 200)));
$heroImageReplacesTitle = $heroImage !== '' && $heroImageMode === 'replace-title';
$heroShowsSeparateImage = $heroImage !== '' && $heroImageMode === 'with-title';
$heroUsesSideMedia = $heroImage !== '' && in_array($heroLayout, ['image-right', 'image-left'], true);
$heroSearchVisible = $hubEnabled('hub_hero_search_visible');
$heroUsesSearchBand = $heroImage !== '' && !$heroUsesSideMedia && $heroSearchVisible;
$heroMedia = static function (string $class) use ($esc, $heroImage, $heroImageWidth, $heroImageHeight): void {
    if ($heroImage === '') {
        return;
    }
    ?>
                <figure class="<?php echo $esc($class); ?>">
                    <img class="n365-hero-image" src="<?php echo $esc($heroImage); ?>" alt="" loading="eager" fetchpriority="high" width="<?php echo (int) $heroImageWidth; ?>" height="<?php echo (int) $heroImageHeight; ?>">
                </figure>
    <?php
};
$legacyFeaturedEnabled = (string) ($settings['featured_enabled'] ?? '1') === '1';
$featuredVisible = $hubEnabled('hub_featured_visible', $legacyFeaturedEnabled);
$featuredLabel = $hubValue('hub_featured_label', 'Featured');
$featuredTitle = $hubValue('hub_featured_title', trim((string) ($settings['featured_title'] ?? '')));
$featuredSub = $hubValue('hub_featured_sub', trim((string) ($settings['featured_text'] ?? '')));
$featuredButtonLabel = $hubValue('hub_featured_btn_label', 'Mehr erfahren');
$featuredButtonUrl = $safeUrl($hubValue('hub_featured_btn_url', trim((string) ($settings['featured_url'] ?? ''))));
$featuredImage = $safeImage($hubValue('hub_featured_image_url', trim((string) ($settings['featured_image_url'] ?? ''))));
$featuredStyle = $hubChoice('hub_featured_style', 'auto', ['auto', 'text', 'image']);
$featuredHasImage = $featuredImage !== '' && $featuredStyle !== 'text';
$featuredLayoutClass = $featuredHasImage ? 'hub-featured--image' : 'hub-featured--text';
$featuredWidthClass = $hubChoice('hub_featured_width', 'full', ['full', 'compact']) === 'compact' ? ' hub-featured--width-compact' : '';
$featuredHasContent = $featuredTitle !== '' || $featuredSub !== '' || $featuredHasImage;
$toolboxVisible = $hubEnabled('hub_toolbox_visible') && $toolboxTools !== [];
$postsStyle = $hubChoice('hub_posts_style', 'separated', ['separated', 'soft-band', 'full-band', 'accent-box']);
$postsSectionClass = 'n365-block n365-block--tight n365-latest-posts n365-latest-posts--' . $postsStyle;
$postsSectionStyle = '--n365-posts-bg: ' . $cssColor($hubSettings['hub_posts_bg_color'] ?? '#f7f9fc', '#f7f9fc')
    . '; --n365-posts-text: ' . $cssColor($hubSettings['hub_posts_text_color'] ?? '#16202e', '#16202e')
    . '; --n365-posts-accent: ' . $cssColor($hubSettings['hub_posts_accent_color'] ?? '#d6951a', '#d6951a') . ';';
?>
<main class="phinit-plugin <?php echo $esc($mainClass); ?>" id="cms-365network" aria-labelledby="n365-title">
    <?php foreach ($sectionOrder as $sectionKey): ?>
        <?php if ($sectionKey === 'hero' && $hubEnabled('hub_hero_visible')): ?>
        <header class="n365-hero n365-hero--<?php echo $esc($hubChoice('hub_hero_height', 'normal', ['compact', 'normal', 'large'])); ?> n365-hero--layout-<?php echo $esc($heroLayout); ?><?php echo ($heroShowsSeparateImage || $heroUsesSideMedia) ? ' n365-hero--has-media' : ''; ?><?php echo $heroImage !== '' ? ' n365-hero--has-image' : ''; ?><?php echo $heroLabel === '' ? ' n365-hero--no-label' : ''; ?><?php echo $heroUsesSearchBand ? ' n365-hero--search-band' : ''; ?>">
            <?php if ($hubEnabled('hub_posts_hero_button_visible') && $blogButtonLabel !== ''): ?><a class="n365-hero-blog-link" href="<?php echo $esc($blogUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo $esc($blogButtonLabel); ?> <?php echo $icon('arrow'); ?></a><?php endif; ?>
            <div class="n365-wrap n365-hero__inner">
            <?php if ($heroUsesSideMedia && $heroLayout === 'image-left'): ?><?php $heroMedia('n365-hero__media'); ?><?php endif; ?>
                <div class="n365-hero__copy">
                <?php if ($heroLabel !== ''): ?><p class="n365-kick"><span aria-hidden="true"></span><?php echo $esc($heroLabel); ?></p><?php endif; ?>
                <?php if ($heroImageReplacesTitle): ?>
                <h1 id="n365-title" class="n365-sr-only"><?php echo $esc($heroTitle); ?></h1>
            <?php if (!$heroUsesSideMedia): ?>
                <figure class="n365-hero__title-media"><img class="n365-hero-title-image" src="<?php echo $esc($heroImage); ?>" alt="" loading="eager" fetchpriority="high" width="<?php echo (int) $heroImageWidth; ?>" height="<?php echo (int) $heroImageHeight; ?>"></figure>
            <?php endif; ?>
                <?php else: ?>
                <h1 id="n365-title"><?php echo $highlightTitle($heroTitle); ?></h1>
                <?php endif; ?>
                <?php if ($heroSub !== ''): ?><p class="n365-hero__sub"><?php echo $esc($heroSub); ?></p><?php endif; ?>
                <?php if ($heroSearchVisible): ?>
                <?php if ($heroUsesSearchBand): ?><div class="n365-hero-search-band"><?php endif; ?>
                <form class="n365-hero-search" method="GET" action="<?php echo $esc($searchUrl); ?>" role="search" aria-label="Hub-Suche">
                    <div class="n365-hero-search__box"><?php echo $icon('search'); ?><label class="n365-sr-only" for="n365-hero-search-input">Hub-Suche</label><input id="n365-hero-search-input" type="search" name="<?php echo $esc($searchParam); ?>" placeholder="<?php echo $esc($hubValue('hub_hero_search_placeholder', 'Name, Thema oder Unternehmen suchen …')); ?>"></div>
                    <button class="n365-btn n365-btn--primary" type="submit"><?php echo $esc($hubValue('hub_hero_btn1_label', 'Suchen')); ?></button>
                </form>
                <?php if ($heroUsesSearchBand): ?></div><?php endif; ?>
                <?php endif; ?>
                </div>
                <?php if ($heroUsesSideMedia && $heroLayout !== 'image-left'): ?><?php $heroMedia('n365-hero__media'); ?><?php elseif ($heroShowsSeparateImage && !$heroUsesSideMedia): ?><?php $heroMedia('n365-hero__media'); ?><?php endif; ?>
            </div>
        </header>
        <?php elseif ($sectionKey === 'partnerband' && $hubEnabled('hub_partnerband_visible') && $partnerLogoItems !== []): ?>
        <section class="n365-partnerband" aria-label="<?php echo $esc($hubValue('hub_partnerband_label', 'Partner im Netzwerk')); ?>">
            <div class="n365-wrap n365-partnerband__inner">
                <p><?php echo $esc($hubValue('hub_partnerband_label', 'Partner im Netzwerk')); ?></p>
                <ul class="n365-partner-logos" role="list">
                    <?php foreach ($partnerLogoItems as $company): ?>
                    <?php $company = is_array($company) ? $company : []; $name = trim((string) ($company['name'] ?? 'Partner')); ?>
                    <li><a href="<?php echo $esc($safeUrl($company['url'] ?? '#')); ?>"><span><?php echo $esc($initials($name)); ?></span><?php echo $esc($name); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>
        <?php elseif ($sectionKey === 'areas' && $hubEnabled('hub_areas_visible') && $hubAreas !== []): ?>
        <section class="n365-block" id="areas" aria-labelledby="n365-areas-title">
            <div class="n365-wrap">
                <div class="n365-section-head"><div><h2 id="n365-areas-title"><?php echo $esc($hubValue('hub_areas_title', 'Vier Bereiche, ein Netzwerk')); ?></h2><p><?php echo $esc($hubValue('hub_areas_label', 'Direkter Einstieg in die Verzeichnisse.')); ?></p></div></div>
                <div class="n365-areas-grid">
                    <?php foreach ($hubAreas as $area): ?>
                    <a class="n365-area-card n365-area-card--<?php echo $esc($area['key']); ?>" href="<?php echo $esc($area['href']); ?>">
                        <span class="n365-area-card__icon" aria-hidden="true"><?php echo $icon($area['icon']); ?></span>
                        <h3><?php echo $esc($area['label']); ?> <span><?php echo (int) $area['count']; ?></span></h3>
                        <?php if ($area['desc'] !== ''): ?><p><?php echo $esc($area['desc']); ?></p><?php endif; ?>
                        <strong><?php echo $esc($area['label']); ?> entdecken <?php echo $icon('arrow'); ?></strong>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php elseif ($sectionKey === 'next-events' && $hubEnabled('hub_next_events_visible') && $nextEvents !== []): ?>
        <section class="n365-block n365-block--tight" aria-labelledby="n365-next-events-title">
            <div class="n365-wrap">
                <div class="n365-section-head"><div><h2 id="n365-next-events-title"><?php echo $esc($hubValue('hub_next_events_title', 'Nächste Events')); ?></h2><p><?php echo $esc($hubValue('hub_next_events_sub', 'Die nächsten Termine im Netzwerk.')); ?></p></div><a class="n365-more" href="<?php echo $esc($safeUrl($hubValue('hub_next_events_all_url', '/events'))); ?>"><?php echo $esc($hubValue('hub_next_events_all_label', 'Alle Events')); ?> <?php echo $icon('arrow'); ?></a></div>
                <div class="n365-next-grid">
                    <?php foreach ($nextEvents as $event): ?>
                    <?php $parts = $eventParts($event); $place = $eventLocation($event); ?>
                    <a class="n365-next-event" href="<?php echo $esc($safeUrl($event['url'] ?? '/events')); ?>">
                        <time class="n365-datebox" datetime="<?php echo $esc($parts['iso']); ?>"><span><?php echo $esc($parts['month']); ?></span><strong><?php echo $esc($parts['day']); ?></strong><small><?php echo $esc($parts['year']); ?></small></time>
                        <span><strong><?php echo $esc(trim((string) ($event['title'] ?? 'Event')) ?: 'Event'); ?></strong><?php if ($place !== ''): ?><small><?php echo $icon('pin'); ?><?php echo $esc($place); ?></small><?php endif; ?><em><?php echo $esc(trim((string) ($event['category'] ?? 'Event')) ?: 'Event'); ?></em></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php elseif ($sectionKey === 'spotlight' && $hubEnabled('hub_spotlight_visible') && $spotlightItems !== []): ?>
        <section class="n365-block n365-block--tight" aria-labelledby="n365-spotlight-title">
            <div class="n365-wrap">
                <div class="n365-section-head"><div><h2 id="n365-spotlight-title"><?php echo $esc($hubValue('hub_spotlight_title', 'Im Fokus')); ?></h2><p><?php echo $esc($hubValue('hub_spotlight_sub', 'Rotierend aus Events, Speakern, Experts und Unternehmen.')); ?></p></div></div>
                <div class="n365-spotlight" data-n365-spotlight data-autoplay="<?php echo $hubEnabled('hub_spotlight_autoplay', false) ? '1' : '0'; ?>">
                    <button class="n365-spotlight-nav n365-spotlight-nav--prev" type="button" data-n365-spotlight-action="prev" aria-label="Zurück"><?php echo $icon('chevron-left'); ?></button>
                    <button class="n365-spotlight-nav n365-spotlight-nav--next" type="button" data-n365-spotlight-action="next" aria-label="Weiter"><?php echo $icon('chevron-right'); ?></button>
                    <div class="n365-spotlight-slides">
                        <?php foreach ($spotlightItems as $index => $spotlight): ?>
                        <?php
                        $type = (string) ($spotlight['type'] ?? 'event');
                        $item = is_array($spotlight['item'] ?? null) ? $spotlight['item'] : [];
                        $isEvent = $type === 'event';
                        $title = $isEvent ? (trim((string) ($item['title'] ?? 'Event')) ?: 'Event') : ($type === 'company' ? (trim((string) ($item['name'] ?? 'Unternehmen')) ?: 'Unternehmen') : $personName($item, $type === 'speaker' ? 'Speaker' : 'Expert'));
                        $role = $isEvent ? $eventLocation($item) : trim((string) (($item['position'] ?? '') ?: ($item['industry'] ?? '') ?: ($item['company'] ?? '')));
                        $url = $safeUrl($item['url'] ?? '#');
                        $parts = $isEvent ? $eventParts($item) : [];
                        ?>
                        <article class="n365-spotlight-slide<?php echo $index === 0 ? ' is-active' : ''; ?>" data-n365-spotlight-slide<?php echo $index === 0 ? '' : ' hidden'; ?>>
                            <div class="n365-spotlight-visual" aria-hidden="true"><?php if ($isEvent): ?><span class="n365-spotlight-date"><strong><?php echo $esc($parts['day'] ?? '–'); ?></strong><small><?php echo $esc(trim((string) (($parts['month'] ?? '') . ' ' . ($parts['year'] ?? '')))); ?></small></span><?php else: ?><span class="n365-spotlight-mark<?php echo $type !== 'company' ? ' n365-spotlight-mark--round' : ''; ?>"><?php echo $esc($initials($title)); ?></span><?php endif; ?></div>
                            <div class="n365-spotlight-content"><p><span></span><?php echo $esc(ucfirst($type)); ?></p><h3><?php echo $esc($title); ?></h3><?php if ($role !== ''): ?><small><?php echo $esc($role); ?></small><?php endif; ?><?php if ($url !== '#'): ?><a class="n365-btn n365-btn--primary n365-btn--small" href="<?php echo $esc($url); ?>"><?php echo $esc($isEvent ? 'Zum Event' : 'Profil ansehen'); ?></a><?php endif; ?></div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php elseif ($sectionKey === 'partner-columns' && $hubEnabled('hub_partner_columns_visible') && ($partnerColumnCompanies !== [] || $partnerColumnExperts !== [])): ?>
        <section class="n365-block n365-block--tight" aria-label="Partner im Netzwerk">
            <div class="n365-wrap n365-partner-cols">
                <?php foreach ([['companies', $partnerColumnCompanies, $hubValue('hub_partner_companies_title', 'Unternehmen im Netzwerk'), $hubValue('hub_partner_companies_all_label', 'Alle'), $safeUrl($hubValue('hub_partner_companies_all_url', '/companies'))], ['experts', $partnerColumnExperts, $hubValue('hub_partner_experts_title', 'Experts im Netzwerk'), $hubValue('hub_partner_experts_all_label', 'Alle'), $safeUrl($hubValue('hub_partner_experts_all_url', '/experts'))]] as $column): ?>
                <?php [$columnType, $columnItems, $columnTitle, $columnAllLabel, $columnAllUrl] = $column; ?>
                <?php if ($columnItems === []): continue; endif; ?>
                <section class="n365-partner-col" aria-labelledby="n365-partner-<?php echo $esc($columnType); ?>">
                    <div class="n365-section-head n365-section-head--small"><div><h2 id="n365-partner-<?php echo $esc($columnType); ?>"><?php echo $esc($columnTitle); ?></h2></div><?php if ($columnAllUrl !== '#'): ?><a class="n365-more" href="<?php echo $esc($columnAllUrl); ?>"><?php echo $esc($columnAllLabel); ?> <?php echo $icon('arrow'); ?></a><?php endif; ?></div>
                    <div class="n365-partner-stack">
                        <?php foreach ($columnItems as $item): ?>
                        <?php $item = is_array($item) ? $item : []; $name = $columnType === 'companies' ? (trim((string) ($item['name'] ?? 'Unternehmen')) ?: 'Unternehmen') : $personName($item, 'Expert'); $meta = trim((string) (($item['industry'] ?? '') ?: ($item['position'] ?? '') ?: ($item['company'] ?? '') ?: ($item['location_city'] ?? ''))); $chips = $chipList(trim((string) (($item['industry'] ?? '') ?: ($item['company'] ?? '')))); ?>
                        <a class="n365-partner-card" href="<?php echo $esc($safeUrl($item['url'] ?? '#')); ?>"><span class="n365-partner-initials<?php echo $columnType === 'experts' ? ' n365-partner-initials--round' : ''; ?>"><?php echo $esc($initials($name)); ?></span><span><strong><?php echo $esc($name); ?> <em><?php echo $icon('check'); ?><?php echo $esc($hubValue('hub_partner_badge_label', 'Partner')); ?></em></strong><?php if ($meta !== ''): ?><small><?php echo $esc($meta); ?></small><?php endif; ?><?php if ($chips !== []): ?><span class="n365-chips"><?php foreach ($chips as $chip): ?><b><?php echo $esc($chip); ?></b><?php endforeach; ?></span><?php endif; ?></span></a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endforeach; ?>
            </div>
        </section>
        <?php elseif ($sectionKey === 'featured' && $featuredVisible && $featuredHasContent): ?>
        <section class="n365-block n365-block--tight n365-featured-section" <?php echo $featuredTitle !== '' ? 'aria-labelledby="n365-featured-title"' : 'aria-label="' . $esc($featuredLabel !== '' ? $featuredLabel : 'Featured') . '"'; ?>>
            <div class="n365-wrap">
                <article class="hub-featured <?php echo $esc($featuredLayoutClass . $featuredWidthClass); ?>">
                    <?php if ($featuredHasImage): ?>
                    <div class="hub-featured-img-wrap"><img src="<?php echo $esc($featuredImage); ?>" alt="<?php echo $esc($featuredTitle !== '' ? $featuredTitle : $featuredLabel); ?>" loading="lazy"></div>
                    <?php else: ?>
                    <span class="hub-featured-accent" aria-hidden="true"></span>
                    <?php endif; ?>
                    <div class="hub-featured-content">
                        <?php if ($featuredLabel !== ''): ?><span class="hub-featured-label"><?php echo $esc($featuredLabel); ?></span><?php endif; ?>
                        <?php if ($featuredTitle !== ''): ?><h2 class="hub-featured-title" id="n365-featured-title"><?php echo $esc($featuredTitle); ?></h2><?php endif; ?>
                        <?php if ($featuredSub !== ''): ?><p class="hub-featured-sub"><?php echo $esc($featuredSub); ?></p><?php endif; ?>
                        <?php if ($featuredButtonUrl !== '#' && $featuredButtonLabel !== ''): ?><a class="hub-featured-btn" href="<?php echo $esc($featuredButtonUrl); ?>"><?php echo $esc($featuredButtonLabel); ?> <?php echo $icon('arrow'); ?></a><?php endif; ?>
                    </div>
                </article>
            </div>
        </section>
        <?php elseif ($sectionKey === 'toolbox' && $toolboxVisible): ?>
        <section class="n365-block n365-block--tight" aria-labelledby="n365-toolbox-title">
            <div class="n365-wrap">
                <div class="n365-section-head"><div><h2 id="n365-toolbox-title"><?php echo $esc($hubValue('hub_toolbox_title', 'Tools & Ressourcen')); ?></h2><p><?php echo $esc($hubValue('hub_toolbox_label', 'M365 Toolbox')); ?></p></div><a class="n365-more" href="<?php echo $esc($safeUrl($hubValue('hub_toolbox_all_url', '/m365-tools'))); ?>"><?php echo $esc($hubValue('hub_toolbox_all_label', 'Alle Tools ansehen')); ?> <?php echo $icon('arrow'); ?></a></div>
                <div class="n365-tool-grid">
                    <?php foreach ($toolboxTools as $tool): ?>
                    <a class="n365-tool-card" href="<?php echo $esc($safeUrl($tool['url'] ?? '#')); ?>" target="_blank" rel="noopener noreferrer"><span aria-hidden="true"><?php echo $icon('tool'); ?></span><strong><?php echo $esc($tool['label'] ?? 'Tool'); ?></strong><?php if (trim((string) ($tool['description'] ?? '')) !== ''): ?><small><?php echo $esc($tool['description']); ?></small><?php endif; ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php elseif ($sectionKey === 'posts' && $hubEnabled('hub_posts_visible') && $latestPosts !== []): ?>
    <section id="n365-latest-posts" class="<?php echo $esc($postsSectionClass); ?>" style="<?php echo $esc($postsSectionStyle); ?>" aria-labelledby="n365-latest-posts-title">
        <div class="n365-wrap">
            <div class="n365-section-head">
                <div>
                    <h2 id="n365-latest-posts-title"><?php echo $esc($hubValue('hub_posts_title', 'Letzte Beiträge')); ?></h2>
                    <?php if ($hubValue('hub_posts_sub', 'Aktuelle Artikel und Einblicke aus dem PHINIT Blog.') !== ''): ?><p><?php echo $esc($hubValue('hub_posts_sub', 'Aktuelle Artikel und Einblicke aus dem PHINIT Blog.')); ?></p><?php endif; ?>
                </div>
                <?php if ($hubValue('hub_posts_all_label', 'Alle Beiträge') !== ''): ?><a class="n365-more" href="<?php echo $esc($blogUrl); ?>" target="_blank" rel="noopener noreferrer"><?php echo $esc($hubValue('hub_posts_all_label', 'Alle Beiträge')); ?> <?php echo $icon('arrow'); ?></a><?php endif; ?>
            </div>
            <div class="n365-post-grid" role="list">
                <?php foreach ($latestPosts as $post): ?>
                <?php
                $postTitle = trim((string) ($post['title'] ?? ''));
                $postUrl = $safeUrl($post['url'] ?? '#');
                if ($postTitle === '' || $postUrl === '#') {
                    continue;
                }
                $dateLabel = trim((string) ($post['date_label'] ?? ''));
                $dateIso = trim((string) ($post['date_iso'] ?? ''));
                $readTime = max(0, (int) ($post['read_time'] ?? 0));
                $categoryName = trim((string) ($post['category_name'] ?? ''));
                $categoryUrl = $safeUrl($post['category_url'] ?? '#');
                $excerpt = trim((string) ($post['excerpt'] ?? ''));
                ?>
                <article class="n365-post-card" role="listitem">
                    <div class="n365-post-card__body">
                        <h3 class="n365-post-card__title"><a href="<?php echo $esc($postUrl); ?>"><?php echo $esc($postTitle); ?></a></h3>
                        <?php if (($dateLabel !== '' && $dateIso !== '') || $readTime > 0): ?>
                        <p class="n365-post-card__meta">
                            <?php if ($dateLabel !== '' && $dateIso !== ''): ?><time datetime="<?php echo $esc($dateIso); ?>"><?php echo $esc($dateLabel); ?></time><?php endif; ?>
                            <?php if ($readTime > 0): ?><span><?php echo (int) $readTime; ?> Min.</span><?php endif; ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($excerpt !== ''): ?><p class="n365-post-card__excerpt"><?php echo $esc($excerpt); ?></p><?php endif; ?>
                        <div class="n365-post-card__foot">
                            <?php if ($categoryName !== '' && $categoryUrl !== '#'): ?>
                            <a class="n365-post-card__cat" href="<?php echo $esc($categoryUrl); ?>"><?php echo $esc($categoryName); ?></a>
                            <?php elseif ($categoryName !== ''): ?>
                            <span class="n365-post-card__cat"><?php echo $esc($categoryName); ?></span>
                            <?php endif; ?>
                            <a class="n365-post-card__more" href="<?php echo $esc($postUrl); ?>" aria-label="<?php echo $esc('Beitrag lesen: ' . $postTitle); ?>">Weiterlesen <?php echo $icon('arrow'); ?></a>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
        <?php endif; ?>
    <?php endforeach; ?>
</main>
