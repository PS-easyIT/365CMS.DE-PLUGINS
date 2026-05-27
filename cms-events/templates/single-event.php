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
        $priceType = strtolower(trim((string) ($event->price_type ?? 'free')));
        $price = (float) ($event->price ?? 0);
        $currency = trim((string) ($event->price_currency ?? 'EUR')) ?: 'EUR';

        if ($priceType === 'free') {
            return 'Kostenlos';
        }

        if ($price <= 0) {
            return $priceType === 'donation' ? 'Spendenbasis' : 'Kostenpflichtig';
        }

        $currencyUpper = strtoupper($currency);
        if ($currencyUpper === 'EUR') {
            $currencySymbol = '€';
        } elseif ($currencyUpper === 'USD') {
            $currencySymbol = '$';
        } elseif ($currencyUpper === 'CHF') {
            $currencySymbol = 'CHF';
        } else {
            $currencySymbol = preg_replace('/[^A-Z]/i', '', $currency) ?: '€';
        }

        return $currencySymbol . ' ' . number_format($price, 2, ',', '.');
    }
}

if (!function_exists('cms_events_view_lowercase')) {
    function cms_events_view_lowercase(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }
}

if (!function_exists('cms_events_view_trim_text')) {
    function cms_events_view_trim_text(string $text, int $length): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        if ($text === '' || $length <= 0) {
            return '';
        }

        $currentLength = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        if ($currentLength <= $length) {
            return $text;
        }

        $slice = function_exists('mb_substr') ? mb_substr($text, 0, max(0, $length - 1), 'UTF-8') : substr($text, 0, max(0, $length - 1));
        return rtrim((string) $slice, " \t\n\r\0\x0B.,;:-") . '…';
    }
}

if (!function_exists('cms_events_view_public_url')) {
    /**
     * @param mixed $url
     */
    function cms_events_view_public_url($url): string
    {
        $url = str_replace('\\', '/', trim((string) $url));
        if ($url === '' || strlen($url) > 1000) {
            return '';
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return '';
        }

        if ((strpos($url, '/') === 0) || preg_match('#^(uploads|ASSETS|assets|plugins)/#i', $url) === 1) {
            $baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
            $path = ltrim($url, '/');
            if ($path === '' || strpos($path, '..') !== false) {
                return '';
            }

            return $baseUrl . '/' . $path;
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

if (!function_exists('cms_events_view_initials')) {
    function cms_events_view_initials(string $name): string
    {
        $words = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));
        if ($words === []) {
            return 'EV';
        }

        $letters = '';
        foreach (array_slice($words, 0, 2) as $word) {
            $letters .= function_exists('mb_substr') ? mb_substr($word, 0, 1, 'UTF-8') : substr($word, 0, 1);
        }

        $letters = function_exists('mb_strtoupper') ? mb_strtoupper($letters, 'UTF-8') : strtoupper($letters);
        return $letters !== '' ? $letters : 'EV';
    }
}

if (!function_exists('cms_events_view_location_text')) {
    function cms_events_view_location_text(string $location, string $city): string
    {
        $parts = array_filter(array_map('trim', array_merge(
            preg_split('/\s*,\s*/', $location) ?: [],
            [$city]
        )), static fn(string $part): bool => $part !== '');

        $seen = [];
        $unique = [];
        foreach ($parts as $part) {
            $key = trim(cms_events_view_lowercase(preg_replace('/\s+/', ' ', $part) ?? $part));
            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $part;
        }

        return implode(', ', $unique);
    }
}

if (!function_exists('cms_events_view_tag_labels')) {
    /**
     * @param mixed $rawTags
     * @return string[]
     */
    function cms_events_view_tag_labels($rawTags): array
    {
        if (is_array($rawTags)) {
            $tags = $rawTags;
        } else {
            $raw = trim((string) $rawTags);
            if ($raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
            $tags = is_array($decoded) ? $decoded : (preg_split('/[,;\n]+/', $raw) ?: []);
        }

        $labels = [];
        $seen = [];
        foreach ($tags as $tag) {
            if (is_array($tag)) {
                $tag = $tag['label'] ?? $tag['name'] ?? $tag['title'] ?? '';
            }

            $label = cms_events_view_trim_text((string) $tag, 48);
            if ($label === '') {
                continue;
            }

            $key = cms_events_view_lowercase($label);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $labels[] = $label;
        }

        return $labels;
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
$locationText = cms_events_view_location_text($locationRaw, $cityRaw);
$addressRaw = trim((string) ($e->address ?? ''));
$zipRaw = trim((string) ($e->zip ?? ''));
$countryRaw = trim((string) ($e->country ?? ''));
$cityLine = trim($zipRaw . ($zipRaw !== '' && $cityRaw !== '' ? ' ' : '') . $cityRaw);
$addressParts = array_values(array_filter([$addressRaw, $cityLine, $countryRaw], static fn(string $part): bool => trim($part) !== ''));
$fullAddressText = implode(', ', $addressParts);
$imageUrl = cms_events_view_public_url($e->banner_url ?? $e->image_url ?? null);
$registrationUrl = cms_events_view_public_url($e->registration_url ?? null);
$onlineUrl = cms_events_view_public_url($e->online_url ?? null);
$websiteUrl = cms_events_view_public_url($e->website_url ?? $e->organizer_website ?? $e->website ?? null);
[$day, $monthShort, $displayDate, $machineDate] = cms_events_view_date_parts((string) ($e->event_date ?? ''));
[, , $displayEndDate, $endMachineDate] = cms_events_view_date_parts((string) ($e->end_date ?? ''));
$eventTime = trim((string) ($e->event_time ?? ''));
$endTime = trim((string) ($e->end_time ?? ''));
$startTimeLabel = $eventTime !== '' ? substr($eventTime, 0, 5) . ' Uhr' : '';
$endTimeLabel = $endTime !== '' ? substr($endTime, 0, 5) . ' Uhr' : '';
$isMultiDay = $displayDate !== '' && $displayEndDate !== '' && $endMachineDate !== '' && $endMachineDate !== $machineDate;
$dateTimeStartLabel = trim($displayDate . ($startTimeLabel !== '' ? ' · ' . $startTimeLabel : ''));
$dateTimeEndLabel = $isMultiDay ? trim($displayEndDate . ($endTimeLabel !== '' ? ' · ' . $endTimeLabel : '')) : '';
$timeLabel = $startTimeLabel !== '' ? $startTimeLabel . (!$isMultiDay && $endTimeLabel !== '' ? ' – ' . $endTimeLabel : '') : '';
$dateTimeLabel = $isMultiDay && $dateTimeEndLabel !== '' ? $dateTimeStartLabel . ' – ' . $dateTimeEndLabel : $dateTimeStartLabel;
$speakerList = (array) ($speakers ?? []);
$primarySpeaker = $speakerList[0] ?? null;
$eventPrice = max(0.0, (float) ($e->price ?? 0));
$capacity = max(0, (int) ($e->seats_total ?? $e->capacity ?? 0));
$registered = max(0, (int) ($e->seats_booked ?? $e->registered_count ?? $e->registrations_count ?? 0));
$seatsLeft = $capacity > 0 ? max(0, $capacity - $registered) : 0;
$progress = $capacity > 0 ? min(100, (int) round(($registered / $capacity) * 100)) : 0;
$isFullyBooked = $capacity > 0 && $seatsLeft <= 0;
$eventUrl = function_exists('cms_event_url') ? cms_event_url($e) : $baseUrl . '/events/' . (int) ($e->id ?? 0);
$requestScheme = (!empty($_SERVER['HTTPS']) && (string) $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$fallbackHost = parse_url($baseUrl, PHP_URL_HOST);
$requestHost = preg_replace('/[^a-z0-9.:-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? ($fallbackHost ?: '')));
$fallbackPath = parse_url($eventUrl, PHP_URL_PATH);
$requestUri = str_replace(["\r", "\n"], '', (string) ($_SERVER['REQUEST_URI'] ?? ($fallbackPath ?: '')));
$currentUrl = $requestHost !== '' && $requestUri !== '' ? $requestScheme . '://' . $requestHost . $requestUri : $eventUrl;
$current_url = $currentUrl;
$share_linkedin = 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode($current_url);
$share_twitter = 'https://twitter.com/intent/tweet?url=' . urlencode($current_url) . '&text=' . urlencode($titleRaw);
$share_mail = 'mailto:?subject=' . rawurlencode($titleRaw) . '&body=' . rawurlencode($current_url);
$description = trim((string) ($e->description ?? ''));
$tagLabels = cms_events_view_tag_labels($e->tags ?? '');
$relatedEvents = array_slice((array) ($related_events ?? []), 0, 3);
$priceLabel = cms_events_view_price_label($e);
$isFreeEvent = $priceLabel === 'Kostenlos';
$isFeatured = !empty($e->is_featured);
$isOnline = !empty($e->is_online);
$eventModeLabel = $isOnline ? ($locationText !== '' ? 'Hybrid' : 'Online') : 'Präsenz';
$eventModeClass = $isOnline ? ($locationText !== '' ? 'cms-events-badge--hybrid' : 'cms-events-badge--online') : 'cms-events-badge--onsite';
$organizerName = trim((string) ($e->organizer_name ?? ''));
$organizerEmailRaw = trim((string) ($e->organizer_email ?? ''));
$organizerEmail = filter_var($organizerEmailRaw, FILTER_VALIDATE_EMAIL) ? $organizerEmailRaw : '';
$organizerPhone = trim((string) ($e->organizer_phone ?? ''));
$hasOrganizerDetails = $organizerName !== '' || $websiteUrl !== '' || $organizerEmail !== '' || $organizerPhone !== '';
$primarySpeakerName = '';
$primarySpeakerInitials = 'EV';
$primarySpeakerLink = '';
$primarySpeakerImage = '';
$primarySpeakerPosition = '';
$primarySpeakerBio = '';

if ($primarySpeaker) {
    $primarySpeakerName = trim((string) ($primarySpeaker->speaker_name ?? (($primarySpeaker->first_name ?? '') . ' ' . ($primarySpeaker->last_name ?? ''))));
    $primarySpeakerInitials = cms_events_view_initials($primarySpeakerName);
    $primarySpeakerImage = cms_events_view_public_url($primarySpeaker->photo_url ?? null);
    $primarySpeakerType = in_array((string) ($primarySpeaker->speaker_type ?? 'speaker'), ['speaker', 'expert'], true) ? (string) $primarySpeaker->speaker_type : 'speaker';
    $primarySpeakerId = (int) ($primarySpeaker->speaker_id ?? 0);
    $primarySpeakerLink = $primarySpeakerId > 0 ? $baseUrl . ($primarySpeakerType === 'expert' ? '/experts/' : '/speakers/') . $primarySpeakerId : '';
    $primarySpeakerPosition = trim((string) ($primarySpeaker->position ?? $primarySpeaker->company ?? ''));
    $primarySpeakerBio = trim(strip_tags((string) ($primarySpeaker->short_bio ?? '')));
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
                    <?php if ($isFeatured): ?>
                        <span class="cms-events-badge cms-events-badge--featured">Featured</span>
                    <?php endif; ?>
                    <span class="cms-events-badge"><?= $category ?></span>
                    <span class="cms-events-badge <?= htmlspecialchars($eventModeClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($eventModeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($isFullyBooked): ?>
                        <span class="cms-events-badge cms-events-badge--danger">Ausgebucht</span>
                    <?php endif; ?>
                </div>
                <h1><?= $title ?></h1>
            </header>

            <?php if ($imageUrl !== ''): ?>
            <figure class="cms-events-detail__hero">
                    <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $title ?>" width="1200" height="675" loading="eager" decoding="async">
            </figure>
            <?php endif; ?>

            <section class="phinit-card event-description-card cms-events-detail__section">
                <h2>Über dieses Event</h2>
                <?php if ($description !== ''): ?>
                    <div class="event-body cms-events-detail__content"><?= nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) ?></div>
                <?php else: ?>
                    <p class="cms-events-muted">Weitere Details zu diesem Event folgen in Kürze.</p>
                <?php endif; ?>

                <?php if (!empty($tagLabels)): ?>
                    <section class="cms-events-detail__topics" aria-labelledby="cms-event-topics-heading">
                        <h3 id="cms-event-topics-heading">Themen</h3>
                        <ul class="cms-events-topic-list" role="list">
                            <?php foreach ($tagLabels as $tagLabel): ?>
                                <li><span class="cms-events-topic-badge"><?= htmlspecialchars($tagLabel, ENT_QUOTES, 'UTF-8') ?></span></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endif; ?>
            </section>

            <?php if ($primarySpeakerName !== ''): ?>
                <section class="phinit-card cms-events-speaker-teaser" aria-labelledby="cms-event-speaker-heading">
                    <div class="cms-events-speaker-teaser__image">
                        <?php if ($primarySpeakerImage !== ''): ?>
                            <img src="<?= htmlspecialchars($primarySpeakerImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($primarySpeakerName, ENT_QUOTES, 'UTF-8') ?>" width="96" height="96" loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="speaker-avatar-fallback" aria-hidden="true"><?= htmlspecialchars($primarySpeakerInitials, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="phinit-overline">Speaker</p>
                        <h2 id="cms-event-speaker-heading"><?= htmlspecialchars($primarySpeakerName, ENT_QUOTES, 'UTF-8') ?></h2>
                        <?php if ($primarySpeakerPosition !== ''): ?>
                            <p class="cms-events-speaker-teaser__position"><?= htmlspecialchars($primarySpeakerPosition, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($primarySpeakerBio !== ''): ?>
                            <p class="cms-events-speaker-teaser__bio"><?= htmlspecialchars($primarySpeakerBio, ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                        <?php if ($primarySpeakerLink !== ''): ?>
                            <a href="<?= htmlspecialchars($primarySpeakerLink, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary speaker-link-button">Zum Speaker</a>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <div class="event-share-bar cms-events-share" id="event-share" aria-label="Event teilen">
                <span class="event-share-label">Teilen:</span>
                <a href="<?= htmlspecialchars($share_linkedin, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="event-share-btn" aria-label="Auf LinkedIn teilen"><i class="ti ti-brand-linkedin" aria-hidden="true"></i></a>
                <a href="<?= htmlspecialchars($share_twitter, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="event-share-btn" aria-label="Auf X / Twitter teilen"><i class="ti ti-brand-x" aria-hidden="true"></i></a>
                <a href="<?= htmlspecialchars($share_mail, ENT_QUOTES, 'UTF-8') ?>" class="event-share-btn" aria-label="Per E-Mail teilen"><i class="ti ti-mail" aria-hidden="true"></i></a>
                <?php if ($websiteUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($websiteUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="event-share-btn" aria-label="Event-Website"><i class="ti ti-world" aria-hidden="true"></i></a>
                <?php endif; ?>
            </div>
        </article>

        <aside class="cms-events-detail__aside" aria-label="Anmeldung">
            <div class="phinit-card cms-events-registration-card">
                <?php if (!$isFreeEvent): ?>
                    <span class="event-price-large cms-events-registration-card__price"><?= htmlspecialchars($priceLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php else: ?>
                    <p class="cms-events-registration-card__price is-free">Kostenlos</p>
                <?php endif; ?>
                <h2>Anmeldung</h2>

                <dl>
                    <?php if ($dateTimeLabel !== ''): ?><div><dt><i class="ti ti-calendar" aria-hidden="true"></i>Termin</dt><dd><?php if ($isMultiDay && $dateTimeEndLabel !== ''): ?><span class="cms-events-date-stack cms-events-date-stack--sidebar"><span><strong>Start</strong><?= htmlspecialchars($dateTimeStartLabel, ENT_QUOTES, 'UTF-8') ?></span><span><strong>Ende</strong><?= htmlspecialchars($dateTimeEndLabel, ENT_QUOTES, 'UTF-8') ?></span></span><?php else: ?><?= htmlspecialchars($dateTimeLabel, ENT_QUOTES, 'UTF-8') ?><?php endif; ?></dd></div><?php endif; ?>
                    <div><dt><i class="ti ti-video" aria-hidden="true"></i>Format</dt><dd><?= htmlspecialchars($eventModeLabel, ENT_QUOTES, 'UTF-8') ?></dd></div>
                    <?php if ($locationText !== ''): ?><div><dt><i class="ti ti-map-pin" aria-hidden="true"></i>Ort</dt><dd><?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                    <?php if ($fullAddressText !== ''): ?><div><dt><i class="ti ti-address-book" aria-hidden="true"></i>Adresse</dt><dd><?= htmlspecialchars($fullAddressText, ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                    <?php if ($organizerName !== ''): ?><div><dt><i class="ti ti-building" aria-hidden="true"></i>Veranstalter</dt><dd><?= htmlspecialchars($organizerName, ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                    <?php if ($capacity > 0): ?><div><dt><i class="ti ti-users" aria-hidden="true"></i>Kapazität</dt><dd><?= (int) $capacity ?> Plätze</dd></div><?php endif; ?>
                </dl>

                <?php if ($capacity > 0): ?>
                    <div class="cms-events-capacity" aria-label="Verfügbare Plätze">
                        <div class="seats-bar cms-events-capacity__bar" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) $progress ?>" style="--cms-event-progress: <?= (int) $progress ?>%;"><div class="seats-fill"></div></div>
                        <p class="seats-label"><?= (int) $seatsLeft ?> Plätze verfügbar</p>
                    </div>
                <?php endif; ?>

                <?php if (!$isFullyBooked && $registrationUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($registrationUrl, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--primary cms-events-registration-card__button" target="_blank" rel="noopener noreferrer">Jetzt anmelden</a>
                <?php elseif (!$isFullyBooked && $onlineUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($onlineUrl, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--primary cms-events-registration-card__button" target="_blank" rel="noopener noreferrer">Online teilnehmen</a>
                <?php else: ?>
                    <button class="phinit-btn phinit-btn--primary cms-events-registration-card__button" type="button" disabled><?= $isFullyBooked ? 'Ausgebucht' : 'Anmeldung folgt' ?></button>
                    <?php if ($isFullyBooked): ?>
                        <a href="?waitlist=1" class="waitlist-link cms-events-registration-card__waitlist">Auf Warteliste setzen</a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if ($onlineUrl !== '' && $registrationUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($onlineUrl, ENT_QUOTES, 'UTF-8') ?>" class="cms-events-registration-card__secondary-link" target="_blank" rel="noopener noreferrer"><i class="ti ti-video" aria-hidden="true"></i>Online-Zugang</a>
                <?php endif; ?>

                <div class="sidebar-share">
                    <p class="sidebar-share-label">Event teilen</p>
                    <div class="event-share-bar event-share-bar--sidebar cms-events-share cms-events-share--sidebar" aria-label="Event teilen">
                        <a href="<?= htmlspecialchars($share_linkedin, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="event-share-btn" aria-label="LinkedIn"><i class="ti ti-brand-linkedin" aria-hidden="true"></i></a>
                        <a href="<?= htmlspecialchars($share_twitter, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="event-share-btn" aria-label="X / Twitter"><i class="ti ti-brand-x" aria-hidden="true"></i></a>
                        <a href="<?= htmlspecialchars($share_mail, ENT_QUOTES, 'UTF-8') ?>" class="event-share-btn" aria-label="E-Mail"><i class="ti ti-mail" aria-hidden="true"></i></a>
                        <?php if ($websiteUrl !== ''): ?>
                            <a href="<?= htmlspecialchars($websiteUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="event-share-btn" aria-label="Website"><i class="ti ti-world" aria-hidden="true"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($relatedEvents)): ?>
                <section class="phinit-card cms-events-related" aria-labelledby="cms-related-events-heading">
                    <h2 id="cms-related-events-heading">Ähnliche Events</h2>
                    <?php foreach ($relatedEvents as $related): ?>
                        <?php
                        if (is_array($related)) {
                            $related = (object) $related;
                        }
                        if (!is_object($related)) {
                            continue;
                        }
                        [, , $relatedDate] = cms_events_view_date_parts((string) ($related->event_date ?? ''));
                        $relatedImage = cms_events_view_public_url($related->image_url ?? $related->banner_url ?? null);
                        ?>
                        <a class="cms-events-related__item" href="<?= htmlspecialchars(function_exists('cms_event_url') ? cms_event_url($related) : $baseUrl . '/events/' . (int) ($related->id ?? 0), ENT_QUOTES, 'UTF-8') ?>">
                            <span class="cms-events-related__image">
                                <?php if ($relatedImage !== ''): ?>
                                    <img src="<?= htmlspecialchars($relatedImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($related->title ?? 'Event'), ENT_QUOTES, 'UTF-8') ?>" class="related-event-thumb related-event-img" width="72" height="54" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <div class="related-event-thumb related-event-thumb--placeholder related-event-img-placeholder" aria-hidden="true"><i class="ti ti-calendar-event"></i></div>
                                <?php endif; ?>
                            </span>
                            <span><?= htmlspecialchars((string) ($related->title ?? 'Event'), ENT_QUOTES, 'UTF-8') ?><small><?= htmlspecialchars($relatedDate, ENT_QUOTES, 'UTF-8') ?></small></span>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</main>