<?php
/**
 * Event Calendar Template.
 *
 * @package CMS_Events
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$events = is_array($events ?? null) ? $events : [];
$settings = is_array($settings ?? null) ? $settings : [];
$month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) ($month ?? '')) === 1
    ? (string) $month
    : date('Y-m');
$view = in_array((string) ($view ?? 'month'), ['month', 'week'], true) ? (string) $view : 'month';

$monthStart = DateTimeImmutable::createFromFormat('!Y-m-d', $month . '-01') ?: new DateTimeImmutable('first day of this month');
$previousMonth = $monthStart->modify('-1 month')->format('Y-m');
$nextMonth = $monthStart->modify('+1 month')->format('Y-m');
$daysInMonth = (int) $monthStart->format('t');
$startWeekDay = (int) $monthStart->format('N');

$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$archiveSlug = preg_replace('/[^a-z0-9-]+/i', '-', (string) ($settings['archive_slug'] ?? 'events')) ?: 'events';
$calendarUrl = $baseUrl . '/' . trim($archiveSlug, '-') . '/calendar';
$archiveUrl = $baseUrl . '/' . trim($archiveSlug, '-') . '/';

$eventsByDate = [];
foreach ($events as $event) {
    $date = (string) ($event->event_date ?? '');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
        continue;
    }
    $eventsByDate[$date][] = $event;
}

$buildUrl = static function (string $targetMonth, string $targetView) use ($calendarUrl): string {
    return $calendarUrl . '?' . http_build_query([
        'month' => $targetMonth,
        'view' => $targetView,
    ]);
};
?>
<main class="phinit-plugin ev-calendar-page">
    <nav class="ev-breadcrumb" aria-label="Breadcrumb">
        <a href="<?php echo htmlspecialchars($archiveUrl, ENT_QUOTES, 'UTF-8'); ?>">Events</a>
        <span class="ev-breadcrumb__sep" aria-hidden="true">/</span>
        <span class="ev-breadcrumb__cur" aria-current="page">Kalender</span>
    </nav>

    <header class="ev-archive-header phinit-card phinit-card--accent">
        <div class="ev-archive-header-inner">
            <div>
                <h1>Event-Kalender</h1>
                <p class="ev-archive-subtitle"><?php echo htmlspecialchars($monthStart->format('m.Y'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <nav class="ev-calendar-nav" aria-label="Kalendernavigation">
                <a class="phinit-btn phinit-btn--secondary" href="<?php echo htmlspecialchars($buildUrl($previousMonth, $view), ENT_QUOTES, 'UTF-8'); ?>" rel="prev">Vorheriger Monat</a>
                <a class="phinit-btn phinit-btn--secondary" href="<?php echo htmlspecialchars($buildUrl($nextMonth, $view), ENT_QUOTES, 'UTF-8'); ?>" rel="next">Nächster Monat</a>
            </nav>
        </div>
    </header>

    <section class="phinit-card ev-calendar-shell" aria-label="Monatskalender">
        <div class="calendar-grid">
            <div class="calendar-weekdays" aria-hidden="true">
                <?php foreach (['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'] as $weekday): ?>
                    <div class="weekday"><?php echo htmlspecialchars($weekday, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            </div>

            <div class="calendar-days">
                <?php for ($i = 1; $i < $startWeekDay; $i++): ?>
                    <div class="calendar-day empty" aria-hidden="true"></div>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                    <?php
                    $date = $monthStart->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
                    $dayEvents = $eventsByDate[$date] ?? [];
                    ?>
                    <article class="calendar-day<?php echo !empty($dayEvents) ? ' has-events' : ''; ?>" data-date="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>">
                        <time class="day-number" datetime="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>"><?php echo (int) $day; ?></time>
                        <?php if (!empty($dayEvents)): ?>
                            <ul class="ev-calendar-events" role="list">
                                <?php foreach (array_slice($dayEvents, 0, 3) as $event): ?>
                                    <?php
                                    $eventUrl = function_exists('cms_event_url') ? cms_event_url($event) : $archiveUrl . (int) ($event->id ?? 0);
                                    $eventTitle = (string) ($event->title ?? 'Event');
                                    ?>
                                    <li>
                                        <a href="<?php echo htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (count($dayEvents) > 3): ?>
                                    <li class="ev-calendar-more"><?php echo (int) (count($dayEvents) - 3); ?> weitere</li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>
                    </article>
                <?php endfor; ?>
            </div>
        </div>
    </section>
</main>
