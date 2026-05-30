<?php
/**
 * Event Archive Template – Plugin-Content only for CMS-PHINIT.
 *
 * Verfügbare Variablen:
 *   $events, $settings, $current_page, $per_page, $pages, $total,
 *   $categories, $filter_category, $filter_city, $filter_month,
 *   $filter_online, $when_filter, $search
 *
 * @package CMS_Events
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!isset($events, $settings)) {
    return;
}

if (!function_exists('cms_events_view_lowercase')) {
    function cms_events_view_lowercase(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }
}

if (!function_exists('cms_events_view_css_color')) {
    function cms_events_view_css_color(mixed $value, string $fallback): string
    {
        $color = trim((string) $value);
        return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color) === 1 ? $color : $fallback;
    }
}

$events = array_values(array_filter(array_map(
    static function ($item): ?object {
        if (is_object($item)) {
            return $item;
        }

        if (is_array($item)) {
            return (object) $item;
        }

        return null;
    },
    (array) $events
)));

$categories = array_values(array_filter(array_map(
    static function ($category): string {
        if (is_string($category) || is_numeric($category)) {
            return trim((string) $category);
        }

        if (is_object($category) && isset($category->name)) {
            return trim((string) $category->name);
        }

        if (is_array($category) && isset($category['name'])) {
            return trim((string) $category['name']);
        }

        return '';
    },
    (array) ($categories ?? [])
), static fn(string $category): bool => $category !== ''));
$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$archiveSlug = preg_replace('/[^a-z0-9-]+/i', '-', (string) ($settings['archive_slug'] ?? 'events')) ?: 'events';
$archiveUrl = $baseUrl . '/' . trim($archiveSlug, '-') . '/';
$curPage = max(1, (int) ($current_page ?? 1));
$totalPages = max(1, (int) ($pages ?? 1));
$totalEvents = max(0, (int) ($total ?? count($events)));
$perPage = max(1, (int) ($per_page ?? 12));
$currentMonth = (int) date('n');
$currentYear = (int) date('Y');
$selectedCategory = (string) ($filter_category ?? '');
$dateFilterExplicit = !empty($date_filter_explicit);
$selectedMonthNumber = isset($filter_month_number) ? (int) $filter_month_number : 0;
$selectedYearNumber = isset($filter_year) ? (int) $filter_year : 0;
$selectedMonth = $selectedMonthNumber > 0 ? (string) $selectedMonthNumber : ($dateFilterExplicit ? '0' : (string) $currentMonth);
$selectedYear = $selectedYearNumber > 0 ? (string) $selectedYearNumber : ($dateFilterExplicit ? '0' : (string) $currentYear);
$selectedSearch = htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8');
$activeFilterParams = is_array($active_filter_params ?? null) ? $active_filter_params : [];
$buildArchiveUrl = static function (int $page) use ($archiveUrl, $activeFilterParams): string {
    $params = $activeFilterParams;
    if ($page > 1) {
        $params['page'] = (string) $page;
    }

    return $archiveUrl . ($params !== [] ? '?' . http_build_query($params) : '');
};
$yearStart = $selectedYearNumber > 0 ? min($currentYear - 2, $selectedYearNumber) : $currentYear - 2;
$yearEnd = $selectedYearNumber > 0 ? max($currentYear + 2, $selectedYearNumber) : $currentYear + 2;
$monthLabels = [
    1 => 'Januar', 2 => 'Februar', 3 => 'März', 4 => 'April',
    5 => 'Mai', 6 => 'Juni', 7 => 'Juli', 8 => 'August',
    9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
];
$designPrimary = cms_events_view_css_color($settings['design_primary_color'] ?? null, '#3b82f6');
$designAccent = cms_events_view_css_color($settings['design_accent_color'] ?? null, '#1d4ed8');
$designCardBg = cms_events_view_css_color($settings['design_card_bg'] ?? null, '#f0f7ff');
$archiveHeaderFrom = cms_events_view_css_color($settings['archive_header_bg_from'] ?? null, '#1d4ed8');
$archiveHeaderTo = cms_events_view_css_color($settings['archive_header_bg_to'] ?? null, '#3b82f6');
$archiveHeaderTitle = cms_events_view_css_color($settings['archive_header_title_color'] ?? null, '#ffffff');
$designCta = cms_events_view_css_color($settings['design_cta_color'] ?? null, $designPrimary);
$designRadius = max(0, min(32, (int) ($settings['design_border_radius'] ?? 12)));
?>
<style>
:root {
    --ev-primary: <?= htmlspecialchars($designPrimary, ENT_QUOTES, 'UTF-8') ?>;
    --ev-primary-h: <?= htmlspecialchars($designAccent, ENT_QUOTES, 'UTF-8') ?>;
    --ev-accent: <?= htmlspecialchars($designAccent, ENT_QUOTES, 'UTF-8') ?>;
    --ev-card-bg: <?= htmlspecialchars($designCardBg, ENT_QUOTES, 'UTF-8') ?>;
    --ev-hdr-from: <?= htmlspecialchars($archiveHeaderFrom, ENT_QUOTES, 'UTF-8') ?>;
    --ev-hdr-to: <?= htmlspecialchars($archiveHeaderTo, ENT_QUOTES, 'UTF-8') ?>;
    --ev-hdr-title: <?= htmlspecialchars($archiveHeaderTitle, ENT_QUOTES, 'UTF-8') ?>;
    --ev-cta: <?= htmlspecialchars($designCta, ENT_QUOTES, 'UTF-8') ?>;
    --ev-radius: <?= (int) $designRadius ?>px;
}
</style>
<main class="phinit-plugin cms-events-wrap" data-cms-events-filter-root data-cms-events-archive-url="<?= htmlspecialchars($archiveUrl, ENT_QUOTES, 'UTF-8') ?>" data-cms-events-date-filter-active="<?= $dateFilterExplicit ? '1' : '0' ?>" data-cms-events-current-month="<?= (int) $currentMonth ?>" data-cms-events-current-year="<?= (int) $currentYear ?>">
    <nav class="cms-events-filter-nav" aria-label="Eventfilter">
        <form class="cms-events-filter" method="get" action="<?= htmlspecialchars($archiveUrl, ENT_QUOTES, 'UTF-8') ?>" data-cms-events-filter-form>
        <div class="phinit-field cms-events-filter__field">
            <label for="filter-category">Kategorie</label>
            <select id="filter-category" name="category" class="phinit-select" data-cms-events-filter="category">
                <option value="0">Alle Kategorien</option>
                <?php foreach ($categories as $category): ?>
                    <?php $categoryValue = (string) $category; ?>
                    <option value="<?= htmlspecialchars($categoryValue, ENT_QUOTES, 'UTF-8') ?>"<?= $selectedCategory === $categoryValue ? ' selected' : '' ?>>
                        <?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="phinit-field cms-events-filter__field">
            <label for="filter-month">Monat</label>
            <select id="filter-month" name="month" class="phinit-select" data-cms-events-filter="month">
                <option value="0">Alle Monate</option>
                <?php foreach ($monthLabels as $monthValue => $monthLabel): ?>
                    <option value="<?= (int) $monthValue ?>"<?= $selectedMonth === (string) $monthValue ? ' selected' : '' ?>>
                        <?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="phinit-field cms-events-filter__field">
            <label for="filter-year">Jahr</label>
            <select id="filter-year" name="year" class="phinit-select" data-cms-events-filter="year">
                <option value="0">Alle Jahre</option>
                <?php for ($year = $yearStart; $year <= $yearEnd; $year++): ?>
                    <option value="<?= (int) $year ?>"<?= $selectedYear === (string) $year ? ' selected' : '' ?>><?= (int) $year ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="phinit-field cms-events-filter__field cms-events-filter__field--search">
            <label for="filter-search">Suche</label>
            <input id="filter-search" name="search" class="phinit-input" type="search" placeholder="Event suchen..." value="<?= $selectedSearch ?>" data-cms-events-filter="search">
        </div>

        <button type="button" class="phinit-btn phinit-btn--secondary cms-events-filter__reset" data-cms-events-reset>Filter zurücksetzen</button>
        </form>
    </nav>

    <section class="cms-events-grid" aria-label="Event-Liste">
        <?php if (empty($events)): ?>
            <div class="cms-events-empty phinit-empty-state" role="status" aria-live="polite">
                <i class="ti ti-calendar-off" aria-hidden="true"></i>
                <p class="cms-events-empty__title">Keine Events gefunden.</p>
            </div>
        <?php else: ?>
            <?php
            $renderedCards = 0;
            foreach ($events as $event):
                try {
                    include __DIR__ . '/event-card.php';
                    $renderedCards++;
                } catch (\Throwable $e) {
                    error_log(sprintf(
                        'CMS Events: event-card render skipped for event #%d in %s:%d – %s',
                        is_object($event) ? (int) ($event->id ?? 0) : 0,
                        (string) $e->getFile(),
                        (int) $e->getLine(),
                        (string) $e->getMessage()
                    ));

                    $fallbackEvent = is_object($event) ? $event : (object) [];
                    $fallbackId = (int) ($fallbackEvent->id ?? 0);
                    $fallbackTitleRaw = trim((string) ($fallbackEvent->title ?? 'Event'));
                    $fallbackTitle = htmlspecialchars($fallbackTitleRaw !== '' ? $fallbackTitleRaw : 'Event', ENT_QUOTES, 'UTF-8');
                    $fallbackDateRaw = trim((string) ($fallbackEvent->event_date ?? ''));
                    $fallbackDateTs = $fallbackDateRaw !== '' ? strtotime($fallbackDateRaw) : 0;
                    $fallbackDate = $fallbackDateTs ? htmlspecialchars(date('d.m.Y', $fallbackDateTs), ENT_QUOTES, 'UTF-8') : '';
                    $fallbackUrl = htmlspecialchars($baseUrl . '/events/' . $fallbackId, ENT_QUOTES, 'UTF-8');

                    echo '<article class="phinit-card cms-events-card cms-events-card--fallback">';
                    echo '<div class="cms-events-card__body">';
                    if ($fallbackDate !== '') {
                        echo '<p class="cms-events-card__meta"><i class="ti ti-calendar" aria-hidden="true"></i>' . $fallbackDate . '</p>';
                    }
                    echo '<h2 class="cms-events-card__title"><a href="' . $fallbackUrl . '">' . $fallbackTitle . '</a></h2>';
                    echo '<footer class="cms-events-card__footer">';
                    echo '<a href="' . $fallbackUrl . '" class="phinit-btn phinit-btn--primary cms-events-card__button">Details</a>';
                    echo '</footer>';
                    echo '</div>';
                    echo '</article>';
                    $renderedCards++;
                }
            endforeach;
            ?>

            <?php if ($renderedCards === 0): ?>
                <div class="cms-events-empty phinit-empty-state" role="status" aria-live="polite">
                    <i class="ti ti-alert-circle" aria-hidden="true"></i>
                    <p class="cms-events-empty__title">Events konnten aktuell nicht dargestellt werden.</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <?php if (!empty($events)): ?>
        <div id="events-empty" class="cms-events-empty cms-events-empty--js phinit-empty-state" role="status" aria-live="polite" hidden data-cms-events-empty>
            <i class="ti ti-calendar-off" aria-hidden="true"></i>
            <p class="cms-events-empty__title">Keine Events gefunden.</p>
            <button type="button" class="phinit-btn phinit-btn--link" data-cms-events-reset>Filter zurücksetzen</button>
        </div>
    <?php endif; ?>

    <?php if ($totalEvents > $perPage): ?>
        <nav class="cms-events-pagination events-pagination" aria-label="Seitennavigation">
            <?php if ($curPage > 1): ?>
                <a class="cms-events-page" href="<?= htmlspecialchars($buildArchiveUrl($curPage - 1), ENT_QUOTES, 'UTF-8') ?>" rel="prev">Zurück</a>
            <?php endif; ?>
            <?php for ($pageNumber = max(1, $curPage - 2); $pageNumber <= min($totalPages, $curPage + 2); $pageNumber++): ?>
                <a class="cms-events-page<?= $pageNumber === $curPage ? ' is-active' : '' ?>" href="<?= htmlspecialchars($buildArchiveUrl($pageNumber), ENT_QUOTES, 'UTF-8') ?>"<?= $pageNumber === $curPage ? ' aria-current="page"' : '' ?>><?= (int) $pageNumber ?></a>
            <?php endfor; ?>
            <?php if ($curPage < $totalPages): ?>
                <a class="cms-events-page" href="<?= htmlspecialchars($buildArchiveUrl($curPage + 1), ENT_QUOTES, 'UTF-8') ?>" rel="next">Weiter</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>