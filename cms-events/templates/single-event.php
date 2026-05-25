<?php
/**
 * Single Event Template – Plugin-Content only for CMS-PHINIT.
 *
 * @package CMS_Events
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (empty($event)) {
    return;
}

if (!function_exists('cms_events_view_date_parts')) {
    function cms_events_view_date_parts(?string $date): array
    {
        $timestamp = $date ? strtotime($date) : 0;
        if (!$timestamp) {
            return ['', '', '', '', ''];
        }

        $weekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
        $monthsFull = [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
        $monthsShort = [1 => 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
        $month = (int) date('n', $timestamp);

        return [
            date('d', $timestamp),
            $monthsShort[$month] ?? date('M', $timestamp),
            $weekdays[(int) date('w', $timestamp)] . ', ' . date('d', $timestamp) . '. ' . ($monthsFull[$month] ?? date('F', $timestamp)) . ' ' . date('Y', $timestamp),
            date('Y-m-d', $timestamp),
            date('Y', $timestamp),
        ];
    }
}

if (!function_exists('cms_events_view_price_label')) {
    function cms_events_view_price_label(object $event): string
    {
        $priceType = (string) ($event->price_type ?? 'free');
        $price = (float) ($event->price ?? 0);
        $currency = trim((string) ($event->price_currency ?? 'EUR')) ?: 'EUR';

        if ($priceType === 'free' || $price <= 0) {
            return 'Kostenlos';
        }

        return number_format($price, 2, ',', '.') . ' ' . htmlspecialchars($currency, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cms_events_view_public_url')) {
    function cms_events_view_public_url(mixed $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || strlen($url) > 1000) {
            return '';
        }

        if (str_starts_with($url, '/')) {
            $baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
            return $baseUrl . '/' . ltrim($url, '/');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || !empty($parts['user']) || !empty($parts['pass'])) {
            return '';
        }

        return in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true) ? $url : '';
    }
}

$e = $event;
$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$titleRaw = trim((string) ($e->title ?? ''));
$title = htmlspecialchars($titleRaw !== '' ? $titleRaw : 'Event', ENT_QUOTES, 'UTF-8');
$categoryRaw = trim((string) ($e->category ?? ''));
$category = htmlspecialchars($categoryRaw !== '' ? $categoryRaw : 'Event', ENT_QUOTES, 'UTF-8');
$locationRaw = trim((string) ($e->location ?? ''));
$cityRaw = trim((string) ($e->city ?? ''));
$locationText = trim($locationRaw . ($locationRaw !== '' && $cityRaw !== '' ? ', ' : '') . $cityRaw);
$imageUrl = cms_events_view_public_url($e->banner_url ?? $e->image_url ?? null);
$registrationUrl = cms_events_view_public_url($e->registration_url ?? null);
$onlineUrl = cms_events_view_public_url($e->online_url ?? null);
[$day, $monthShort, $displayDate, $machineDate] = cms_events_view_date_parts((string) ($e->event_date ?? ''));
$eventTime = trim((string) ($e->event_time ?? ''));
$endTime = trim((string) ($e->end_time ?? ''));
$timeLabel = $eventTime !== '' ? substr($eventTime, 0, 5) . ' Uhr' . ($endTime !== '' ? ' – ' . substr($endTime, 0, 5) . ' Uhr' : '') : '';
$speakerList = (array) ($speakers ?? []);
$primarySpeaker = $speakerList[0] ?? null;
$capacity = max(0, (int) ($e->capacity ?? 0));
$registered = max(0, (int) ($e->registered_count ?? $e->registrations_count ?? 0));
$seatsLeft = $capacity > 0 ? max(0, $capacity - $registered) : 0;
$progress = $capacity > 0 ? min(100, (int) round(($registered / $capacity) * 100)) : 0;
$isFullyBooked = $capacity > 0 && $seatsLeft <= 0;
$eventUrl = function_exists('cms_event_url') ? cms_event_url($e) : $baseUrl . '/events/' . (int) ($e->id ?? 0);
$shareUrl = $eventUrl;
$description = trim((string) ($e->description ?? $e->excerpt ?? ''));
$tags = !empty($e->tags) ? (json_decode((string) $e->tags, true) ?: []) : [];
$relatedEvents = [];

if (class_exists('CMS_Events_Database') && method_exists('CMS_Events_Database', 'instance')) {
    $database = CMS_Events_Database::instance();
    if (method_exists($database, 'get_events')) {
        $relatedArgs = ['status' => 'published', 'upcoming' => true, 'limit' => 4];
        if ($categoryRaw !== '') {
            $relatedArgs['category'] = $categoryRaw;
        }
        $relatedEvents = array_values(array_filter($database->get_events($relatedArgs), static fn(object $item): bool => (int) ($item->id ?? 0) !== (int) ($e->id ?? 0)));
        $relatedEvents = array_slice($relatedEvents, 0, 3);
    }
}
?>
<main class="phinit-plugin cms-events-wrap cms-events-detail">
    <nav class="cms-events-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= htmlspecialchars($baseUrl . '/', ENT_QUOTES, 'UTF-8') ?>">Home</a>
        <span aria-hidden="true">›</span>
        <a href="<?= htmlspecialchars($baseUrl . '/events/', ENT_QUOTES, 'UTF-8') ?>">Veranstaltungen</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= $title ?></span>
    </nav>

    <div class="cms-events-detail__grid">
        <article class="cms-events-detail__main">
            <header class="cms-events-detail__head">
                <div class="cms-events-detail__badges">
                    <span class="cms-events-badge"><?= $category ?></span>
                    <?php if ($isFullyBooked): ?>
                        <span class="cms-events-badge cms-events-badge--muted">Ausgebucht</span>
                    <?php endif; ?>
                </div>
                <h1><?= $title ?></h1>
                <div class="cms-events-detail__meta" aria-label="Event-Metadaten">
                    <?php if ($machineDate !== ''): ?>
                        <span><i class="ti ti-calendar-event" aria-hidden="true"></i><?= htmlspecialchars($displayDate, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <?php if ($timeLabel !== ''): ?>
                        <span><i class="ti ti-clock" aria-hidden="true"></i><?= htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <?php if ($locationText !== ''): ?>
                        <span><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </header>

            <figure class="cms-events-detail__hero">
                <?php if ($imageUrl !== ''): ?>
                    <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $title ?>" width="1200" height="675" loading="eager" decoding="async">
                <?php else: ?>
                    <div class="cms-events-detail__hero-placeholder" aria-hidden="true"><i class="ti ti-calendar-event"></i></div>
                <?php endif; ?>
            </figure>

            <section class="phinit-card cms-events-detail__section">
                <h2>Über dieses Event</h2>
                <?php if ($description !== ''): ?>
                    <div class="cms-events-detail__content"><?= nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) ?></div>
                <?php else: ?>
                    <p class="cms-events-muted">Weitere Details zu diesem Event folgen in Kürze.</p>
                <?php endif; ?>
            </section>

            <?php if ($primarySpeaker): ?>
                <?php
                $speakerName = trim((string) ($primarySpeaker->speaker_name ?? (($primarySpeaker->first_name ?? '') . ' ' . ($primarySpeaker->last_name ?? ''))));
                $speakerImage = cms_events_view_public_url($primarySpeaker->photo_url ?? null);
                $speakerType = in_array((string) ($primarySpeaker->speaker_type ?? 'speaker'), ['speaker', 'expert'], true) ? (string) $primarySpeaker->speaker_type : 'speaker';
                $speakerLink = $baseUrl . ($speakerType === 'expert' ? '/experts/' : '/speakers/') . (int) ($primarySpeaker->speaker_id ?? 0);
                ?>
                <section class="phinit-card cms-events-speaker-teaser" aria-labelledby="cms-event-speaker-heading">
                    <div class="cms-events-speaker-teaser__image">
                        <?php if ($speakerImage !== ''): ?>
                            <img src="<?= htmlspecialchars($speakerImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($speakerName, ENT_QUOTES, 'UTF-8') ?>" width="96" height="96" loading="lazy" decoding="async">
                        <?php else: ?>
                            <i class="ti ti-user" aria-hidden="true"></i>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="phinit-overline">Speaker</p>
                        <h2 id="cms-event-speaker-heading"><?= htmlspecialchars($speakerName !== '' ? $speakerName : 'Speaker', ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if (!empty($primarySpeaker->position)): ?>
                            <p><?= htmlspecialchars((string) $primarySpeaker->position, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <a href="<?= htmlspecialchars($speakerLink, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary">Speaker ansehen</a>
                    </div>
                </section>
            <?php endif; ?>

            <section class="cms-events-share" id="event-share" aria-label="Event teilen">
                <span>Teilen</span>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= rawurlencode($shareUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="Auf LinkedIn teilen"><i class="ti ti-brand-linkedin"></i></a>
                <a href="https://twitter.com/intent/tweet?url=<?= rawurlencode($shareUrl) ?>&text=<?= rawurlencode($titleRaw) ?>" target="_blank" rel="noopener noreferrer" aria-label="Auf X teilen"><i class="ti ti-brand-x"></i></a>
                <a href="mailto:?subject=<?= rawurlencode($titleRaw) ?>&body=<?= rawurlencode($shareUrl) ?>" aria-label="Per E-Mail teilen"><i class="ti ti-mail"></i></a>
            </section>
        </article>

        <aside class="cms-events-detail__aside" aria-label="Anmeldung">
            <div class="phinit-card cms-events-registration-card">
                <div class="cms-events-registration-card__date">
                    <span><?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?></span>
                    <strong><?= htmlspecialchars($monthShort, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <h2>Anmeldung</h2>
                <dl>
                    <div><dt>Preis</dt><dd><?= cms_events_view_price_label($e) ?></dd></div>
                    <?php if ($displayDate !== ''): ?><div><dt>Datum</dt><dd><?= htmlspecialchars($displayDate, ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                    <?php if ($timeLabel !== ''): ?><div><dt>Zeit</dt><dd><?= htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                    <?php if ($locationText !== ''): ?><div><dt>Ort</dt><dd><?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                </dl>

                <?php if ($capacity > 0): ?>
                    <div class="cms-events-capacity" aria-label="Verfügbare Plätze">
                        <span><?= (int) $seatsLeft ?> von <?= (int) $capacity ?> Plätzen frei</span>
                        <div class="cms-events-capacity__bar"><span style="--cms-event-progress: <?= (int) $progress ?>%;"></span></div>
                    </div>
                <?php endif; ?>

                <?php if (!$isFullyBooked && $registrationUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($registrationUrl, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--primary cms-events-registration-card__button" target="_blank" rel="noopener noreferrer">Jetzt anmelden</a>
                <?php elseif (!$isFullyBooked && $onlineUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($onlineUrl, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--primary cms-events-registration-card__button" target="_blank" rel="noopener noreferrer">Online teilnehmen</a>
                <?php else: ?>
                    <button class="phinit-btn phinit-btn--primary cms-events-registration-card__button" type="button" disabled><?= $isFullyBooked ? 'Ausgebucht' : 'Anmeldung folgt' ?></button>
                <?php endif; ?>
            </div>

            <?php if (!empty($relatedEvents)): ?>
                <section class="phinit-card cms-events-related" aria-labelledby="cms-related-events-heading">
                    <h2 id="cms-related-events-heading">Weitere Events</h2>
                    <?php foreach ($relatedEvents as $related): ?>
                        <?php [$relatedDay, $relatedMonth, $relatedDate] = cms_events_view_date_parts((string) ($related->event_date ?? '')); ?>
                        <a class="cms-events-related__item" href="<?= htmlspecialchars(function_exists('cms_event_url') ? cms_event_url($related) : $baseUrl . '/events/' . (int) ($related->id ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                            <span><strong><?= htmlspecialchars($relatedDay, ENT_QUOTES, 'UTF-8') ?></strong><?= htmlspecialchars($relatedMonth, ENT_QUOTES, 'UTF-8') ?></span>
                            <span><?= htmlspecialchars((string) ($related->title ?? 'Event'), ENT_QUOTES, 'UTF-8') ?><small><?= htmlspecialchars($relatedDate, ENT_QUOTES, 'UTF-8') ?></small></span>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</main>