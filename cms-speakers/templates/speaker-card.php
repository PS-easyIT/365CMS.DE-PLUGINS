<?php
/**
 * Speaker Card Template – PHINIT Publicsite Card.
 *
 * @package CMS_Speakers
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!isset($speaker)) {
    return;
}

if (!function_exists('cms_speakers_view_lowercase')) {
    function cms_speakers_view_lowercase(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }
}

if (!function_exists('cms_speakers_view_uppercase')) {
    function cms_speakers_view_uppercase(string $value): string
    {
        return function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8')
            : strtoupper($value);
    }
}

if (!function_exists('cms_speakers_view_public_url')) {
    function cms_speakers_view_public_url(mixed $url): string
    {
        $url = str_replace('\\', '/', trim((string) $url));
        if ($url === '' || strlen($url) > 1000) {
            return '';
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return '';
        }

        if (str_starts_with($url, '/') || preg_match('#^(uploads|ASSETS|assets|plugins)/#i', $url) === 1) {
            $baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
            $path = ltrim($url, '/');
            if ($path === '' || str_contains($path, '..')) {
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

if (!function_exists('cms_speakers_view_initials')) {
    function cms_speakers_view_initials(string $firstName, string $lastName, string $fallbackName = ''): string
    {
        $letters = '';
        foreach ([$firstName, $lastName] as $part) {
            $part = trim($part);
            if ($part !== '') {
                $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
            }
        }

        if ($letters === '' && $fallbackName !== '') {
            $words = array_values(array_filter(preg_split('/\s+/', trim($fallbackName)) ?: []));
            foreach (array_slice($words, 0, 2) as $word) {
                $letters .= function_exists('mb_substr') ? mb_substr($word, 0, 1, 'UTF-8') : substr($word, 0, 1);
            }
        }

        $letters = cms_speakers_view_uppercase($letters);
        return $letters !== '' ? $letters : 'SP';
    }
}

if (!function_exists('cms_speakers_view_list_values')) {
    function cms_speakers_view_list_values(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (is_object($item)) {
                $item = $item->topic_name ?? $item->name ?? $item->label ?? '';
            } elseif (is_array($item)) {
                $item = $item['topic_name'] ?? $item['name'] ?? $item['label'] ?? '';
            }

            $item = trim((string) $item);
            if ($item !== '') {
                $items[$item] = $item;
            }
        }

        return array_values($items);
    }
}

if (!function_exists('cms_speakers_view_format_label')) {
    function cms_speakers_view_format_label(string $format): string
    {
        $labels = [
            'keynote' => 'Keynote',
            'workshop' => 'Workshop',
            'panel' => 'Panel',
            'moderation' => 'Moderation',
            'training' => 'Training',
            'consulting' => 'Consulting',
            'interview' => 'Interview',
            'webinar' => 'Webinar',
            'conference' => 'Konferenz',
        ];

        $key = cms_speakers_view_lowercase(trim($format));
        return $labels[$key] ?? trim($format);
    }
}

$s = is_object($speaker) ? $speaker : (is_array($speaker) ? (object) $speaker : (object) []);
$settings = isset($settings) && is_array($settings) ? $settings : [];
$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$firstName = trim((string) ($s->first_name ?? ''));
$lastName = trim((string) ($s->last_name ?? ''));
$nameRaw = trim($firstName . ' ' . $lastName) ?: 'Speaker';
$name = htmlspecialchars($nameRaw, ENT_QUOTES, 'UTF-8');
$job = trim((string) ($s->position ?? $s->job_title ?? ''));
$company = trim((string) ($s->company_linked_name ?? $s->company_name ?? $s->company ?? ''));
$location = trim((string) ($s->location_city ?? $s->city ?? ''));
$bioRaw = trim(strip_tags((string) ($s->short_bio ?? $s->bio ?? $s->description ?? '')));
$avatar = cms_speakers_view_public_url($s->photo_url ?? $s->avatar_url ?? null);
$initials = cms_speakers_view_initials($firstName, $lastName, $nameRaw);
$speakerUrl = function_exists('cms_speaker_url') ? cms_speaker_url($s) : cms_speakers_view_speaker_url($s);
$eventCount = max(0, (int) ($s->event_count ?? $s->events_count ?? $s->presentation_count ?? 0));
$topics = isset($topics) && is_array($topics) ? cms_speakers_view_list_values($topics) : [];

if ($topics === [] && isset($s->_topics) && is_array($s->_topics)) {
    $topics = cms_speakers_view_list_values($s->_topics);
} elseif (!empty($s->topics)) {
    $topics = cms_speakers_view_list_values($s->topics);
} elseif (!empty($s->expertise)) {
    $topics = cms_speakers_view_list_values($s->expertise);
}

$formats = cms_speakers_view_list_values($s->formats ?? null);
$formatLabels = array_values(array_filter(array_map('cms_speakers_view_format_label', $formats)));
$availability = cms_speakers_view_lowercase(trim((string) ($s->availability ?? 'available')));
$availabilityLabels = [
    'available' => 'Verfügbar',
    'limited' => 'Eingeschränkt',
    'booked' => 'Ausgebucht',
];
$availabilityLabel = $availabilityLabels[$availability] ?? 'Auf Anfrage';
$availabilityClass = in_array($availability, ['available', 'limited', 'booked'], true) ? $availability : 'request';
$showAvailability = (string) ($settings['design_show_availability'] ?? '1') !== '0';
$showFormats = (string) ($settings['design_show_formats'] ?? '1') !== '0';
$showTopics = (string) ($settings['design_show_topics'] ?? '1') !== '0';
$isFeatured = !empty($s->is_featured);
$isVerified = !empty($s->is_verified);
$primaryTopic = $topics[0] ?? '';
$topicData = cms_speakers_view_lowercase(implode(' ', $topics));
$formatData = cms_speakers_view_lowercase(implode(' ', $formatLabels));
$searchData = cms_speakers_view_lowercase(trim($nameRaw . ' ' . $job . ' ' . $company . ' ' . $location . ' ' . $topicData . ' ' . $formatData . ' ' . $availabilityLabel . ' ' . $bioRaw));
?>
<article class="speaker-card speaker-overview-card phinit-card cms-speaker-card<?= $isFeatured ? ' speaker-overview-card--featured' : '' ?>"
         data-cms-speaker-card
         data-topic="<?= htmlspecialchars($topicData, ENT_QUOTES, 'UTF-8') ?>"
         data-category="<?= htmlspecialchars($topicData, ENT_QUOTES, 'UTF-8') ?>"
         data-name="<?= htmlspecialchars($searchData, ENT_QUOTES, 'UTF-8') ?>"
         data-speaker-url="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>"
         data-href="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>"
         role="link"
         tabindex="0"
         aria-label="Profil von <?= $name ?> ansehen">
    <div class="speaker-card-body cms-speaker-card__body">
        <div class="speaker-card-badge-row">
            <div class="speaker-card-badge-group" aria-label="Speaker-Merkmale">
                <?php if ($isFeatured): ?>
                    <span class="speaker-badge speaker-badge--featured"><i class="ti ti-star" aria-hidden="true"></i>Featured</span>
                <?php endif; ?>
                <?php if ($isVerified): ?>
                    <span class="speaker-badge speaker-badge--verified"><i class="ti ti-check" aria-hidden="true"></i>Verifiziert</span>
                <?php endif; ?>
                <?php if ($showTopics && $primaryTopic !== ''): ?>
                    <span class="speaker-badge speaker-badge--topic cms-speaker-topic" data-topic-value="<?= htmlspecialchars(cms_speakers_view_lowercase($primaryTopic), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($primaryTopic, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <?php if ($showAvailability): ?>
                <span class="speaker-availability speaker-availability--<?= htmlspecialchars($availabilityClass, ENT_QUOTES, 'UTF-8') ?>"><i class="ti ti-circle-filled" aria-hidden="true"></i><?= htmlspecialchars($availabilityLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <div class="speaker-card-main">
            <a class="speaker-card-avatar-link" href="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Profil von <?= $name ?> ansehen" tabindex="-1">
                <span class="sc-avatar speaker-card-avatar cms-speaker-card__avatar">
                    <?php if ($avatar !== ''): ?>
                        <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $name ?>" class="speaker-avatar-img" width="72" height="72" loading="lazy" decoding="async">
                    <?php else: ?>
                        <span class="sc-initials speaker-initials speaker-avatar-fallback" aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </span>
            </a>

            <div class="speaker-card-content">
                <h2 class="sc-name speaker-name"><a href="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $name ?></a></h2>
                <?php if ($job !== '' || $company !== ''): ?>
                    <p class="sc-role speaker-role cms-speaker-card__job">
                        <?php if ($job !== ''): ?><span><?= htmlspecialchars($job, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?><?php if ($company !== ''): ?><span class="sc-company speaker-company"><?= $job !== '' ? ' · ' : '' ?><?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if ($location !== '' || ($showFormats && !empty($formatLabels))): ?>
                    <p class="speaker-card-meta-line cms-speaker-card__job">
                        <?php if ($location !== ''): ?>
                            <span><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if ($showFormats && !empty($formatLabels)): ?>
                            <span><i class="ti ti-microphone-2" aria-hidden="true"></i><?= htmlspecialchars(implode(' · ', array_slice($formatLabels, 0, 2)), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($showTopics && count($topics) > 1): ?>
            <div class="sc-tag-row speaker-card-meta" aria-label="Weitere Themen">
                <?php foreach (array_slice($topics, 1, 3) as $topic): ?>
                    <?php $topicValue = cms_speakers_view_lowercase((string) $topic); ?>
                    <span class="sc-tag speaker-tag cms-speaker-topic" data-topic-value="<?= htmlspecialchars($topicValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $topic, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($bioRaw !== ''): ?>
            <p class="sc-bio speaker-bio cms-speaker-card__bio"><?= htmlspecialchars($bioRaw, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <footer class="speaker-card-footer cms-speaker-card__footer">
            <div class="speaker-card-footer-left">
                <?php if ($eventCount > 0): ?>
                    <span class="speaker-footer-info"><i class="ti ti-calendar-event" aria-hidden="true"></i><?= (int) $eventCount ?> Auftritt<?= $eventCount === 1 ? '' : 'e' ?></span>
                <?php elseif ($company !== ''): ?>
                    <span class="speaker-footer-info"><i class="ti ti-building" aria-hidden="true"></i><?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?></span>
                <?php elseif ($location !== ''): ?>
                    <span class="speaker-footer-info"><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <a href="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-speaker-profile phinit-btn phinit-btn--secondary cms-speaker-card__button" tabindex="-1">Profil</a>
        </footer>
    </div>
</article>