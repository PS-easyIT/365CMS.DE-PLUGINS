<?php
/**
 * Single Speaker Template – Plugin-Content only for CMS-PHINIT.
 *
 * @package CMS_Speakers
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (empty($speaker)) {
    return;
}

if (!function_exists('cms_speaker_view_date_parts')) {
    function cms_speaker_view_date_parts(?string $date): array
    {
        $timestamp = $date ? strtotime($date) : 0;
        if (!$timestamp) {
            return ['', '', ''];
        }

        $monthsShort = [1 => 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
        $monthsFull = [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
        $month = (int) date('n', $timestamp);

        return [
            date('d', $timestamp),
            $monthsShort[$month] ?? date('M', $timestamp),
            date('d', $timestamp) . '. ' . ($monthsFull[$month] ?? date('F', $timestamp)) . ' ' . date('Y', $timestamp),
        ];
    }
}

if (!function_exists('cms_speakers_view_public_url')) {
    function cms_speakers_view_public_url(mixed $url): string
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
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        return in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true) ? $url : '';
    }
}

if (!function_exists('cms_speakers_view_speaker_url')) {
    function cms_speakers_view_speaker_url(object $speaker): string
    {
        $baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $name = trim((string) (($speaker->first_name ?? '') . ' ' . ($speaker->last_name ?? '')));
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', strtr($name, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue'])));
        $slug = trim($slug, '-') ?: 'speaker';

        return $baseUrl . '/speakers/' . $slug . '-' . (int) ($speaker->id ?? 0);
    }
}

$s = $speaker;
$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$firstName = trim((string) ($s->first_name ?? ''));
$lastName = trim((string) ($s->last_name ?? ''));
$nameRaw = trim($firstName . ' ' . $lastName) ?: 'Speaker';
$name = htmlspecialchars($nameRaw, ENT_QUOTES, 'UTF-8');
$job = trim((string) ($s->position ?? $s->job_title ?? ''));
$company = trim((string) ($s->company_linked_name ?? $s->company_name ?? $s->company ?? ''));
$location = trim((string) ($s->location_city ?? ''));
$avatar = cms_speakers_view_public_url($s->photo_url ?? $s->avatar_url ?? null);
$email = filter_var((string) ($s->email ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
$website = cms_speakers_view_public_url($s->website ?? $s->website_url ?? null);
$linkedin = cms_speakers_view_public_url($s->linkedin ?? $s->linkedin_url ?? null);
$twitter = cms_speakers_view_public_url($s->twitter ?? null);
$bio = trim((string) ($s->bio ?? $s->short_bio ?? ''));
$topicList = [];

foreach ((array) ($topics ?? []) as $topic) {
    if (is_object($topic)) {
        $topicName = trim((string) ($topic->topic_name ?? $topic->name ?? ''));
    } else {
        $topicName = trim((string) $topic);
    }
    if ($topicName !== '') {
        $topicList[] = $topicName;
    }
}

if (empty($topicList) && !empty($s->topics)) {
    $decoded = json_decode((string) $s->topics, true);
    $topicList = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', (string) $s->topics)));
}

$topicList = array_values(array_unique(array_filter(array_map(static fn($topic): string => trim((string) $topic), $topicList))));
$eventList = array_values((array) ($events ?? []));
$relatedSpeakers = [];

if (class_exists('CMS_Speakers_Database') && method_exists('CMS_Speakers_Database', 'instance')) {
    $speakerDb = CMS_Speakers_Database::instance();
    if (method_exists($speakerDb, 'get_speakers')) {
        $relatedSpeakers = array_values(array_filter($speakerDb->get_speakers(['status' => 'active', 'limit' => 4]), static fn(object $item): bool => (int) ($item->id ?? 0) !== (int) ($s->id ?? 0)));
        $relatedSpeakers = array_slice($relatedSpeakers, 0, 3);
    }
}
?>
<main class="phinit-plugin cms-speaker-wrap cms-speaker-detail">
    <nav class="cms-speaker-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= htmlspecialchars($baseUrl . '/', ENT_QUOTES, 'UTF-8') ?>">Home</a>
        <span aria-hidden="true">›</span>
        <a href="<?= htmlspecialchars($baseUrl . '/speakers/', ENT_QUOTES, 'UTF-8') ?>">Speaker</a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= $name ?></span>
    </nav>

    <div class="cms-speaker-detail__grid">
        <article class="cms-speaker-detail__main">
            <header class="phinit-card cms-speaker-profile">
                <div class="cms-speaker-profile__avatar">
                    <?php if ($avatar !== ''): ?>
                        <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $name ?>" width="140" height="140" loading="eager" decoding="async">
                    <?php else: ?>
                        <i class="ti ti-user" aria-hidden="true"></i>
                    <?php endif; ?>
                </div>
                <div class="cms-speaker-profile__content">
                    <p class="phinit-overline">Speaker</p>
                    <h1><?= $name ?></h1>
                    <?php if ($job !== '' || $company !== ''): ?>
                        <p class="cms-speaker-profile__job"><?= htmlspecialchars($job, ENT_QUOTES, 'UTF-8') ?><?= $job !== '' && $company !== '' ? ' · ' : '' ?><?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if ($location !== ''): ?>
                        <p class="cms-speaker-profile__meta"><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                    <?php if (!empty($topicList)): ?>
                        <div class="cms-speaker-profile__topics" aria-label="Themen">
                            <?php foreach (array_slice($topicList, 0, 8) as $topic): ?>
                                <span class="cms-speaker-topic"><?= htmlspecialchars((string) $topic, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="cms-speaker-profile__social" aria-label="Social Links">
                        <?php if ($linkedin !== ''): ?><a href="<?= htmlspecialchars($linkedin, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="ti ti-brand-linkedin"></i></a><?php endif; ?>
                        <?php if ($website !== ''): ?><a href="<?= htmlspecialchars($website, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Website"><i class="ti ti-world"></i></a><?php endif; ?>
                        <?php if ($twitter !== ''): ?><a href="<?= htmlspecialchars($twitter, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="X"><i class="ti ti-brand-x"></i></a><?php endif; ?>
                    </div>
                </div>
            </header>

            <section class="phinit-card cms-speaker-detail__section">
                <h2>Über <?= htmlspecialchars($firstName !== '' ? $firstName : $nameRaw, ENT_QUOTES, 'UTF-8') ?></h2>
                <?php if ($bio !== ''): ?>
                    <div class="cms-speaker-detail__bio"><?= nl2br(htmlspecialchars($bio, ENT_QUOTES, 'UTF-8')) ?></div>
                <?php else: ?>
                    <p class="cms-speaker-muted">Eine ausführliche Bio wird in Kürze ergänzt.</p>
                <?php endif; ?>
            </section>

            <section class="phinit-card cms-speaker-sessions" aria-labelledby="cms-speaker-sessions-heading">
                <h2 id="cms-speaker-sessions-heading">Vorträge &amp; Sessions</h2>
                <?php if (empty($eventList)): ?>
                    <p class="cms-speaker-muted">Aktuell keine Events geplant.</p>
                <?php else: ?>
                    <div class="cms-speaker-sessions__list">
                        <?php foreach ($eventList as $speakerEvent): ?>
                            <?php
                            [$eventDay, $eventMonth, $eventDateLabel] = cms_speaker_view_date_parts((string) ($speakerEvent->event_date ?? ''));
                            $eventTitle = trim((string) ($speakerEvent->event_title ?? $speakerEvent->title ?? 'Event'));
                            $eventLocation = trim((string) ($speakerEvent->event_location ?? $speakerEvent->location ?? ''));
                            $eventLink = '';
                            if (!empty($speakerEvent->cms_event_id)) {
                                $eventLink = $baseUrl . '/events/' . (int) $speakerEvent->cms_event_id;
                            } elseif (!empty($speakerEvent->event_url)) {
                                $eventLink = cms_speakers_view_public_url($speakerEvent->event_url);
                            }
                            ?>
                            <article class="cms-speaker-session">
                                <time class="cms-speaker-session__date" datetime="<?= htmlspecialchars((string) ($speakerEvent->event_date ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <span><?= htmlspecialchars($eventDay, ENT_QUOTES, 'UTF-8') ?></span>
                                    <strong><?= htmlspecialchars($eventMonth, ENT_QUOTES, 'UTF-8') ?></strong>
                                </time>
                                <div class="cms-speaker-session__body">
                                    <h3><?= htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p><i class="ti ti-calendar-event" aria-hidden="true"></i><?= htmlspecialchars($eventDateLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php if ($eventLocation !== ''): ?>
                                        <p><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($eventLocation, ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                </div>
                                <?php if ($eventLink !== ''): ?>
                                    <a href="<?= htmlspecialchars($eventLink, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary cms-speaker-session__button">Zum Event</a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </article>

        <aside class="cms-speaker-detail__aside" aria-label="Speaker buchen">
            <div class="phinit-card cms-speaker-booking-card">
                <h2>Speaker anfragen</h2>
                <p><?= $name ?> für Keynote, Workshop oder Panel anfragen.</p>
                <?php if ($email !== ''): ?>
                    <a href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>?subject=<?= rawurlencode('Speaker-Anfrage: ' . $nameRaw) ?>" class="phinit-btn phinit-btn--primary cms-speaker-booking-card__button">Jetzt anfragen</a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($baseUrl . '/contact/', ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--primary cms-speaker-booking-card__button">Kontakt aufnehmen</a>
                <?php endif; ?>
                <?php if (!empty($s->speaking_fee_min) || !empty($s->speaking_fee_max)): ?>
                    <p class="cms-speaker-booking-card__note">Honorarrahmen auf Anfrage verfügbar.</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($relatedSpeakers)): ?>
                <section class="phinit-card cms-speaker-related" aria-labelledby="cms-related-speakers-heading">
                    <h2 id="cms-related-speakers-heading">Weitere Speaker</h2>
                    <?php foreach ($relatedSpeakers as $related): ?>
                        <?php
                        $relatedName = trim((string) (($related->first_name ?? '') . ' ' . ($related->last_name ?? ''))) ?: 'Speaker';
                        $relatedAvatar = cms_speakers_view_public_url($related->photo_url ?? null);
                        $relatedUrl = function_exists('cms_speaker_url') ? cms_speaker_url($related) : cms_speakers_view_speaker_url($related);
                        ?>
                        <a class="cms-speaker-related__item" href="<?= htmlspecialchars($relatedUrl, ENT_QUOTES, 'UTF-8') ?>">
                            <span class="cms-speaker-related__avatar">
                                <?php if ($relatedAvatar !== ''): ?>
                                    <img src="<?= htmlspecialchars($relatedAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($relatedName, ENT_QUOTES, 'UTF-8') ?>" width="52" height="52" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <i class="ti ti-user" aria-hidden="true"></i>
                                <?php endif; ?>
                            </span>
                            <span><strong><?= htmlspecialchars($relatedName, ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string) ($related->position ?? ''), ENT_QUOTES, 'UTF-8') ?></small></span>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</main>