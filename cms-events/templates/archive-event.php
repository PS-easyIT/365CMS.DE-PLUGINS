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

$events = (array) $events;
$categories = (array) ($categories ?? []);
$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$archiveSlug = preg_replace('/[^a-z0-9-]+/i', '-', (string) ($settings['archive_slug'] ?? 'events')) ?: 'events';
$archiveUrl = $baseUrl . '/' . trim($archiveSlug, '-') . '/';
$curPage = max(1, (int) ($current_page ?? 1));
$totalPages = max(1, (int) ($pages ?? 1));
$today = strtotime('today');
$upcomingCount = count(array_filter($events, static function (object $event) use ($today): bool {
    $timestamp = !empty($event->event_date) ? strtotime((string) $event->event_date) : 0;
    return $timestamp && $today !== false && $timestamp >= $today;
}));
$currentYear = (int) date('Y');
$selectedCategory = mb_strtolower((string) ($filter_category ?? ''), 'UTF-8');
$selectedMonth = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) ($filter_month ?? '')) === 1 ? substr((string) $filter_month, 5, 2) : '';
$selectedYear = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) ($filter_month ?? '')) === 1 ? substr((string) $filter_month, 0, 4) : '';
$selectedSearch = htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8');
$monthLabels = [
    '01' => 'Januar', '02' => 'Februar', '03' => 'März', '04' => 'April',
    '05' => 'Mai', '06' => 'Juni', '07' => 'Juli', '08' => 'August',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Dezember',
];
?>
<main class="phinit-plugin cms-events-wrap" data-cms-events-filter-root>
    <header class="cms-events-head">
        <p class="phinit-overline">Events</p>
        <h1>Veranstaltungen</h1>
        <p class="cms-events-head__subtitle"><?= (int) $upcomingCount ?> bevorstehende Events</p>
    </header>

    <nav class="cms-events-filter" aria-label="Eventfilter">
        <div class="phinit-field cms-events-filter__field">
            <label for="cms-event-category">Kategorie</label>
            <select id="cms-event-category" class="phinit-select" data-cms-events-filter="category">
                <option value="">Alle Kategorien</option>
                <?php foreach ($categories as $category): ?>
                    <?php $categoryValue = mb_strtolower((string) $category, 'UTF-8'); ?>
                    <option value="<?= htmlspecialchars($categoryValue, ENT_QUOTES, 'UTF-8') ?>"<?= $selectedCategory === $categoryValue ? ' selected' : '' ?>>
                        <?= htmlspecialchars((string) $category, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="phinit-field cms-events-filter__field">
            <label for="cms-event-month">Monat</label>
            <select id="cms-event-month" class="phinit-select" data-cms-events-filter="month">
                <option value="">Alle Monate</option>
                <?php foreach ($monthLabels as $monthValue => $monthLabel): ?>
                    <option value="<?= htmlspecialchars($monthValue, ENT_QUOTES, 'UTF-8') ?>"<?= $selectedMonth === $monthValue ? ' selected' : '' ?>>
                        <?= htmlspecialchars($monthLabel, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="phinit-field cms-events-filter__field">
            <label for="cms-event-year">Jahr</label>
            <select id="cms-event-year" class="phinit-select" data-cms-events-filter="year">
                <option value="">Alle Jahre</option>
                <?php for ($year = $currentYear; $year <= $currentYear + 1; $year++): ?>
                    <option value="<?= (int) $year ?>"<?= $selectedYear === (string) $year ? ' selected' : '' ?>><?= (int) $year ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <div class="phinit-field cms-events-filter__field cms-events-filter__field--search">
            <label for="cms-event-search">Suche</label>
            <input id="cms-event-search" class="phinit-input" type="search" placeholder="Suche..." value="<?= $selectedSearch ?>" data-cms-events-filter="search">
        </div>

        <button type="button" class="phinit-btn phinit-btn--secondary cms-events-filter__reset" data-cms-events-reset>Filter zurücksetzen</button>
    </nav>

    <section class="cms-events-grid" aria-label="Event-Liste">
        <?php if (empty($events)): ?>
            <div class="cms-events-empty phinit-empty-state" role="status" aria-live="polite">
                <i class="ti ti-calendar-off" aria-hidden="true"></i>
                <p class="cms-events-empty__title">Keine Events gefunden.</p>
            </div>
        <?php else: ?>
            <?php $db = CMS_Events_Database::instance(); ?>
            <?php foreach ($events as $event): ?>
                <?php include __DIR__ . '/event-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if (!empty($events)): ?>
        <div class="cms-events-empty cms-events-empty--js phinit-empty-state" role="status" aria-live="polite" hidden data-cms-events-empty>
            <i class="ti ti-calendar-off" aria-hidden="true"></i>
            <p class="cms-events-empty__title">Keine Events gefunden.</p>
            <button type="button" class="phinit-btn phinit-btn--link" data-cms-events-reset>Filter zurücksetzen</button>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="cms-events-pagination" aria-label="Seitennavigation">
            <?php if ($curPage > 1): ?>
                <a class="cms-events-page" href="<?= htmlspecialchars($archiveUrl . '?page=' . ($curPage - 1), ENT_QUOTES, 'UTF-8') ?>" rel="prev">Zurück</a>
            <?php endif; ?>
            <?php for ($pageNumber = max(1, $curPage - 2); $pageNumber <= min($totalPages, $curPage + 2); $pageNumber++): ?>
                <a class="cms-events-page<?= $pageNumber === $curPage ? ' is-active' : '' ?>" href="<?= htmlspecialchars($archiveUrl . '?page=' . $pageNumber, ENT_QUOTES, 'UTF-8') ?>"<?= $pageNumber === $curPage ? ' aria-current="page"' : '' ?>><?= (int) $pageNumber ?></a>
            <?php endfor; ?>
            <?php if ($curPage < $totalPages): ?>
                <a class="cms-events-page" href="<?= htmlspecialchars($archiveUrl . '?page=' . ($curPage + 1), ENT_QUOTES, 'UTF-8') ?>" rel="next">Weiter</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>