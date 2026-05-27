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

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$settings = is_array($settings ?? null) ? $settings : [];
$areas = is_array($areas ?? null) ? $areas : [];
$events = is_array($events ?? null) ? $events : [];
$speakers = is_array($speakers ?? null) ? $speakers : [];
$companies = is_array($companies ?? null) ? $companies : [];
$experts = is_array($experts ?? null) ? $experts : [];
$stats = is_array($stats ?? null) ? $stats : [];

$choice = static function (string $key, string $default, array $allowed) use ($settings): string {
    $value = (string) ($settings[$key] ?? $default);
    return in_array($value, $allowed, true) ? $value : $default;
};
$enabled = static fn(string $key, string $default = '1'): bool => (string) ($settings[$key] ?? $default) === '1';
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
$safeImage = static function (mixed $value) use ($safeUrl): string {
    $url = $safeUrl($value);
    return $url !== '#' && !str_starts_with($url, '#') ? $url : '';
};
$previewName = static function (array $item, string $type): string {
    if ($type === 'company') {
        return trim((string) ($item['name'] ?? 'Firma')) ?: 'Firma';
    }

    $name = trim((string) (($item['first_name'] ?? '') . ' ' . ($item['last_name'] ?? '')));
    return $name !== '' ? $name : ($type === 'speaker' ? 'Speaker' : 'Expertise-Profil');
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
$mainClass = 'n365-landing n365-layout--' . $layout . ' n365-sidebar--' . $sidebarPosition . ($showSidebar ? ' n365-has-sidebar' : '');

$title = trim((string) ($settings['landing_title'] ?? '365NETWORK')) ?: '365NETWORK';
$subtitle = trim((string) ($settings['landing_subtitle'] ?? ''));
$eyebrow = trim((string) ($settings['hero_eyebrow'] ?? '365network Hub'));
$primaryUrl = $safeUrl($settings['primary_button_url'] ?? '#n365-areas');
$secondaryUrl = $safeUrl($settings['secondary_button_url'] ?? '/kontakt');
$featuredImage = $safeImage($settings['featured_image_url'] ?? '');
$featuredUrl = $safeUrl($settings['featured_url'] ?? '');
$belowPreviews = $previewPlacement === 'below';
?>
<main class="phinit-plugin <?php echo $esc($mainClass); ?>" id="cms-365network">
    <section class="n365-shell" aria-labelledby="n365-title">
        <header class="n365-hero">
            <div class="n365-hero__content">
                <?php if ($eyebrow !== ''): ?>
                <p class="phinit-overline n365-eyebrow"><?php echo $esc($eyebrow); ?></p>
                <?php endif; ?>
                <h1 id="n365-title"><?php echo $esc($title); ?></h1>
                <?php if ($subtitle !== ''): ?>
                <p class="n365-hero__intro"><?php echo $esc($subtitle); ?></p>
                <?php endif; ?>
                <nav class="n365-hero__actions" aria-label="365NETWORK Aktionen">
                    <?php if ($primaryUrl !== '#'): ?>
                    <a class="phinit-btn phinit-btn--primary n365-btn n365-btn--primary" href="<?php echo $esc($primaryUrl); ?>"><?php echo $esc($settings['primary_button_label'] ?? 'Bereiche entdecken'); ?></a>
                    <?php endif; ?>
                    <?php if ($secondaryUrl !== '#'): ?>
                    <a class="n365-link-action" href="<?php echo $esc($secondaryUrl); ?>"><?php echo $esc($settings['secondary_button_label'] ?? 'Kontakt aufnehmen'); ?></a>
                    <?php endif; ?>
                </nav>
                <dl class="n365-stats" aria-label="Netzwerk Kennzahlen">
                    <div><dt>Events</dt><dd><?php echo (int) ($stats['events'] ?? 0); ?></dd></div>
                    <div><dt>Speaker</dt><dd><?php echo (int) ($stats['speakers'] ?? 0); ?></dd></div>
                    <div><dt>Firmen</dt><dd><?php echo (int) ($stats['companies'] ?? 0); ?></dd></div>
                    <div><dt>Experten</dt><dd><?php echo (int) ($stats['experts'] ?? 0); ?></dd></div>
                </dl>
            </div>

            <?php if ($enabled('featured_enabled') && trim((string) ($settings['featured_title'] ?? '')) !== ''): ?>
            <aside class="n365-featured-card" aria-label="Empfohlen">
                <?php if ($featuredImage !== ''): ?>
                <img class="n365-featured-card__image" src="<?php echo $esc($featuredImage); ?>" alt="" loading="lazy">
                <?php else: ?>
                <div class="n365-featured-card__visual" aria-hidden="true"><i class="ti ti-world-star"></i></div>
                <?php endif; ?>
                <div class="n365-featured-card__body">
                    <p class="n365-kicker">Featured</p>
                    <h2><?php echo $esc($settings['featured_title'] ?? ''); ?></h2>
                    <?php if (trim((string) ($settings['featured_text'] ?? '')) !== ''): ?>
                    <p><?php echo $esc($settings['featured_text']); ?></p>
                    <?php endif; ?>
                    <?php if ($featuredUrl !== '#'): ?>
                    <a class="n365-card-link" href="<?php echo $esc($featuredUrl); ?>">Mehr erfahren <span aria-hidden="true">→</span></a>
                    <?php endif; ?>
                </div>
            </aside>
            <?php endif; ?>
        </header>

        <div class="n365-content-grid">
            <section class="n365-main" aria-labelledby="n365-areas-title">
                <div class="n365-section-head">
                    <p class="phinit-overline">Direkteinstieg</p>
                    <h2 id="n365-areas-title">Vier Bereiche, ein Netzwerk</h2>
                </div>

                <div class="n365-area-grid" id="n365-areas">
                    <?php foreach ($areas as $area): ?>
                    <?php
                    $area = is_array($area) ? $area : [];
                    $areaUrl = $safeUrl($area['url'] ?? '#');
                    $statKey = (string) ($area['stat'] ?? '');
                    $statValue = (int) ($stats[$statKey] ?? 0);
                    ?>
                    <article class="n365-area-card n365-area-card--<?php echo $esc($area['key'] ?? 'area'); ?>">
                        <div class="n365-area-card__icon" aria-hidden="true"><i class="ti ti-<?php echo $esc($area['icon'] ?? 'sparkles'); ?>"></i></div>
                        <div class="n365-area-card__body">
                            <p class="n365-area-card__count"><?php echo (int) $statValue; ?> Einträge</p>
                            <h3><a href="<?php echo $esc($areaUrl); ?>"><?php echo $esc($area['label'] ?? 'Bereich'); ?></a></h3>
                            <p><?php echo $esc($area['text'] ?? ''); ?></p>
                        </div>
                        <a class="n365-area-card__cta" href="<?php echo $esc($areaUrl); ?>" aria-label="<?php echo $esc(($area['label'] ?? 'Bereich') . ' öffnen'); ?>">Öffnen <span aria-hidden="true">→</span></a>
                    </article>
                    <?php endforeach; ?>
                </div>

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
