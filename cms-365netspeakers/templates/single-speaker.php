<?php
/**
 * Single Speaker Template – PHINIT-style detail page.
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

if (!function_exists('cms_speakers_detail_t')) {
    function cms_speakers_detail_t(string $key, array $parameters = []): string
    {
        $translated = null;

        if (class_exists('CMS\\Services\\TranslationService')) {
            try {
                $translated = \CMS\Services\TranslationService::getInstance()->translate($key, 'default', $parameters);
            } catch (\Throwable) {
                $translated = null;
            }
        }

        if (($translated === null || $translated === $key) && function_exists('__')) {
            try {
                $translated = __($key);
            } catch (\Throwable) {
                $translated = null;
            }
        }

        if ($translated === null || $translated === $key) {
            $translated = cms_speakers_detail_catalog_value($key) ?? $key;
        }

        if ($parameters !== []) {
            $translated = strtr($translated, $parameters);
        }

        return $translated;
    }
}

if (!function_exists('cms_speakers_detail_catalog_value')) {
    function cms_speakers_detail_catalog_value(string $key): ?string
    {
        static $catalogs = [];

        $locale = cms_speakers_detail_locale();
        if (!array_key_exists($locale, $catalogs)) {
            $catalogs[$locale] = cms_speakers_detail_load_catalog($locale);
        }

        return $catalogs[$locale][$key] ?? null;
    }
}

if (!function_exists('cms_speakers_detail_load_catalog')) {
    function cms_speakers_detail_load_catalog(string $locale): array
    {
        $file = cms_speakers_detail_lang_file($locale);
        if ($file === '') {
            return [];
        }

        $catalog = [];
        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || $trimmed === 'default:') {
                continue;
            }

            if (preg_match('/^\s*["\'](.+?)["\']\s*:\s*["\'](.*)["\']\s*$/', $line, $matches) === 1) {
                $catalog[$matches[1]] = stripcslashes($matches[2]);
            }
        }

        return $catalog;
    }
}

if (!function_exists('cms_speakers_detail_lang_file')) {
    function cms_speakers_detail_lang_file(string $locale): string
    {
        $locale = str_starts_with($locale, 'en') ? 'en' : 'de';
        $candidates = [];

        if (defined('ABSPATH')) {
            $candidates[] = rtrim((string) ABSPATH, '/\\') . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.yaml';
        }
        if (defined('CMS_PATH')) {
            $candidates[] = rtrim((string) CMS_PATH, '/\\') . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.yaml';
        }

        $dir = __DIR__;
        for ($i = 0; $i < 8; $i++) {
            $candidates[] = $dir . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.yaml';
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }
}

if (!function_exists('cms_speakers_detail_locale')) {
    function cms_speakers_detail_locale(): string
    {
        if (class_exists('CMS\\Services\\TranslationService')) {
            try {
                $locale = \CMS\Services\TranslationService::getInstance()->getLocale();
                return str_starts_with($locale, 'en') ? 'en' : 'de';
            } catch (\Throwable) {
                return 'de';
            }
        }

        return 'de';
    }
}

if (!function_exists('cms_speakers_detail_e')) {
    function cms_speakers_detail_e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cms_speakers_detail_public_url')) {
    function cms_speakers_detail_public_url(mixed $url): string
    {
        $url = str_replace('\\', '/', trim((string) $url));
        if ($url === '' || strlen($url) > 1000 || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
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

if (!function_exists('cms_speakers_detail_social_url')) {
    function cms_speakers_detail_social_url(mixed $value, string $network): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        $url = cms_speakers_detail_public_url($raw);
        if ($url !== '') {
            return $url;
        }

        $handle = ltrim($raw, '@');
        if (preg_match('/^[A-Za-z0-9_.-]{1,80}$/', $handle) !== 1) {
            return '';
        }

        return match ($network) {
            'x' => 'https://x.com/' . rawurlencode($handle),
            'instagram' => 'https://instagram.com/' . rawurlencode($handle),
            'github' => 'https://github.com/' . rawurlencode($handle),
            'gitlab' => 'https://gitlab.com/' . rawurlencode($handle),
            default => '',
        };
    }
}

if (!function_exists('cms_speakers_detail_speaker_url')) {
    function cms_speakers_detail_speaker_url(object $speaker): string
    {
        $baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $name = trim((string) (($speaker->first_name ?? '') . ' ' . ($speaker->last_name ?? '')));
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', strtr($name, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue'])));
        $slug = trim($slug, '-') ?: 'speaker';

        return $baseUrl . '/speakers/' . $slug . '-' . (int) ($speaker->id ?? 0);
    }
}

if (!function_exists('cms_speakers_detail_initials')) {
    function cms_speakers_detail_initials(string $firstName, string $lastName, string $fallbackName = ''): string
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

        $letters = function_exists('mb_strtoupper') ? mb_strtoupper($letters, 'UTF-8') : strtoupper($letters);
        return $letters !== '' ? $letters : 'SP';
    }
}

if (!function_exists('cms_speakers_detail_list_values')) {
    function cms_speakers_detail_list_values(mixed $value): array
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

if (!function_exists('cms_speakers_detail_format_label')) {
    function cms_speakers_detail_format_label(string $format): string
    {
        $key = strtolower(trim($format));
        $labels = [
            'keynote' => 'cms_speakers.detail.format.keynote',
            'workshop' => 'cms_speakers.detail.format.workshop',
            'panel' => 'cms_speakers.detail.format.panel',
            'moderation' => 'cms_speakers.detail.format.moderation',
            'training' => 'cms_speakers.detail.format.training',
            'consulting' => 'cms_speakers.detail.format.consulting',
            'interview' => 'cms_speakers.detail.format.interview',
            'webinar' => 'cms_speakers.detail.format.webinar',
            'conference' => 'cms_speakers.detail.format.conference',
            'other' => 'cms_speakers.detail.format.other',
        ];

        return isset($labels[$key]) ? cms_speakers_detail_t($labels[$key]) : trim($format);
    }
}

if (!function_exists('cms_speakers_detail_date')) {
    function cms_speakers_detail_date(?string $date): string
    {
        $timestamp = $date ? strtotime($date) : 0;
        if (!$timestamp) {
            return '';
        }

        $locale = cms_speakers_detail_locale();
        $months = $locale === 'en'
            ? [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
            : [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
        $month = (int) date('n', $timestamp);

        if ($locale === 'en') {
            return ($months[$month] ?? date('F', $timestamp)) . ' ' . date('j, Y', $timestamp);
        }

        return date('d', $timestamp) . '. ' . ($months[$month] ?? date('F', $timestamp)) . ' ' . date('Y', $timestamp);
    }
}

if (!function_exists('cms_speakers_detail_icon')) {
    function cms_speakers_detail_icon(string $icon): string
    {
        $stroke = static fn(string $paths): string => '<svg class="sp-detail-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
        $fill = static fn(string $paths): string => '<svg class="sp-detail-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $paths . '</svg>';

        return match ($icon) {
            'calendar' => $stroke('<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>'),
            'external' => $stroke('<path d="M7 17 17 7M8 7h9v9"/><path d="M5 5h6M5 5v14h14v-6"/>'),
            'globe' => $stroke('<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>'),
            'info' => $stroke('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>'),
            'list' => $stroke('<path d="M4 6h16M4 12h16M4 18h10"/>'),
            'mail' => $stroke('<path d="m4 6 8 6 8-6M4 6v12h16V6Z"/>'),
            'map' => $stroke('<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>'),
            'microphone' => $stroke('<path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3zM5 11a7 7 0 0 0 14 0M12 18v4"/>'),
            'megaphone' => $stroke('<path d="M3 11l18-5v12L3 14v-3zM7 19v-5"/>'),
            'tag' => $stroke('<path d="M20.6 12.6 12 21l-9-9V3h9l8.6 8.6a1.4 1.4 0 0 1 0 2zM7.5 7.5h.01"/>'),
            'users' => $stroke('<path d="M16 19v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM22 19v-2a4 4 0 0 0-3-3.87M16 3.13A4 4 0 0 1 16 11"/>'),
            'linkedin' => $fill('<path d="M4.98 3.5A2.5 2.5 0 1 1 0 3.5a2.5 2.5 0 0 1 4.98 0ZM0 8h5v16H0V8Zm7.5 0h4.8v2.2h.07c.67-1.2 2.3-2.46 4.73-2.46 5.06 0 6 3.33 6 7.66V24h-5v-7.2c0-1.72-.03-3.93-2.4-3.93-2.4 0-2.77 1.87-2.77 3.8V24h-5V8Z"/>'),
            'github' => $fill('<path d="M12 1a11 11 0 0 0-3.48 21.44c.55.1.75-.24.75-.53v-1.86c-3.06.67-3.7-1.47-3.7-1.47-.5-1.28-1.23-1.62-1.23-1.62-1-.69.08-.67.08-.67 1.1.08 1.69 1.14 1.69 1.14.98 1.69 2.58 1.2 3.21.92.1-.71.39-1.2.7-1.47-2.44-.28-5-1.22-5-5.44 0-1.2.43-2.18 1.13-2.95-.11-.28-.49-1.4.11-2.92 0 0 .93-.3 3.05 1.13a10.6 10.6 0 0 1 5.56 0c2.12-1.43 3.04-1.13 3.04-1.13.6 1.52.22 2.64.11 2.92.7.77 1.13 1.75 1.13 2.95 0 4.23-2.57 5.16-5.02 5.43.4.34.75 1.01.75 2.04v3.03c0 .29.2.64.76.53A11 11 0 0 0 12 1Z"/>'),
            'x', 'xing', 'instagram', 'youtube', 'gitlab' => $stroke('<path d="M7 7h10v10H7z"/><path d="M9 15 15 9M9 9l6 6"/>'),
            default => $stroke('<circle cx="12" cy="12" r="9"/>'),
        };
    }
}

$s = is_object($speaker) ? $speaker : (object) $speaker;
$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$firstName = trim((string) ($s->first_name ?? ''));
$lastName = trim((string) ($s->last_name ?? ''));
$nameRaw = trim($firstName . ' ' . $lastName) ?: cms_speakers_detail_t('cms_speakers.detail.fallback_speaker');
$job = trim((string) ($s->position ?? $s->job_title ?? ''));
$company = trim((string) ($s->company_linked_name ?? $s->company_name ?? $s->company ?? ''));
$avatar = cms_speakers_detail_public_url($s->photo_url ?? $s->avatar_url ?? null);
$initials = cms_speakers_detail_initials($firstName, $lastName, $nameRaw);
$email = filter_var((string) ($s->email ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
$website = cms_speakers_detail_public_url($s->website ?? $s->website_url ?? null);
$linkedin = cms_speakers_detail_public_url($s->linkedin ?? $s->linkedin_url ?? null);
$xing = cms_speakers_detail_public_url($s->xing ?? null);
$twitter = cms_speakers_detail_social_url($s->twitter ?? null, 'x');
$instagram = cms_speakers_detail_social_url($s->instagram ?? null, 'instagram');
$youtube = cms_speakers_detail_public_url($s->youtube ?? null);
$github = cms_speakers_detail_social_url($s->github ?? null, 'github');
$gitlab = cms_speakers_detail_social_url($s->gitlab ?? null, 'gitlab');
$bio = trim((string) ($s->bio ?? $s->short_bio ?? ''));
$topicList = cms_speakers_detail_list_values($topics ?? []);
if (empty($topicList) && !empty($s->topics)) {
    $topicList = cms_speakers_detail_list_values($s->topics);
}
$topicList = array_values(array_unique(array_filter(array_map(static fn($topic): string => trim((string) $topic), $topicList))));
$formatList = array_map('cms_speakers_detail_format_label', cms_speakers_detail_list_values($s->formats ?? null));
$skillLabels = class_exists('CMS_Speakers_Meta_Boxes') ? CMS_Speakers_Meta_Boxes::get_skill_labels() : [];
$skillList = [];
foreach (cms_speakers_detail_list_values($s->skills ?? null) as $skill) {
    $skillKey = trim((string) $skill);
    $skillList[] = $skillLabels[$skillKey] ?? ucwords(str_replace('_', ' ', $skillKey));
}
$travelRadius = trim((string) ($s->travel_radius ?? ''));
$availability = trim((string) ($s->availability ?? 'available'));
$availabilityLabels = [
    'available' => cms_speakers_detail_t('cms_speakers.detail.availability.available'),
    'limited' => cms_speakers_detail_t('cms_speakers.detail.availability.limited'),
    'booked' => cms_speakers_detail_t('cms_speakers.detail.availability.booked'),
    'request' => cms_speakers_detail_t('cms_speakers.detail.availability.request'),
];
$availabilityClass = in_array($availability, ['available', 'limited', 'booked'], true) ? $availability : 'request';
$availabilityLabel = $availabilityLabels[$availabilityClass];
$travelLabels = [
    'local' => cms_speakers_detail_t('cms_speakers.detail.travel.local'),
    'regional' => cms_speakers_detail_t('cms_speakers.detail.travel.regional'),
    'national' => cms_speakers_detail_t('cms_speakers.detail.travel.national'),
    'international' => cms_speakers_detail_t('cms_speakers.detail.travel.international'),
    'worldwide' => cms_speakers_detail_t('cms_speakers.detail.travel.worldwide'),
];
$presenceLabels = [
    'presence' => cms_speakers_detail_t('cms_speakers.detail.presence.presence'),
    'online' => cms_speakers_detail_t('cms_speakers.detail.presence.online'),
    'hybrid' => cms_speakers_detail_t('cms_speakers.detail.presence.hybrid'),
];
$eventTypeLabels = [
    'keynote' => cms_speakers_detail_t('cms_speakers.detail.format.keynote'),
    'workshop' => cms_speakers_detail_t('cms_speakers.detail.format.workshop'),
    'panel' => cms_speakers_detail_t('cms_speakers.detail.format.panel'),
    'moderation' => cms_speakers_detail_t('cms_speakers.detail.format.moderation'),
    'interview' => cms_speakers_detail_t('cms_speakers.detail.format.interview'),
    'webinar' => cms_speakers_detail_t('cms_speakers.detail.format.webinar'),
    'conference' => cms_speakers_detail_t('cms_speakers.detail.format.conference'),
    'training' => cms_speakers_detail_t('cms_speakers.detail.format.training'),
    'other' => cms_speakers_detail_t('cms_speakers.detail.event_type.event'),
];
$eventList = array_values((array) ($events ?? []));
$futureEvents = [];
$pastEvents = [];
$today = date('Y-m-d');
foreach ($eventList as $speakerEvent) {
    $eventDateRaw = trim((string) ($speakerEvent->event_date ?? ''));
    if ($eventDateRaw !== '' && $eventDateRaw >= $today) {
        $futureEvents[] = $speakerEvent;
    } else {
        $pastEvents[] = $speakerEvent;
    }
}
$relatedSpeakers = array_slice((array) ($related_speakers ?? []), 0, 3);
$socialLinks = [];
foreach ([
    ['url' => $website, 'label' => cms_speakers_detail_t('cms_speakers.detail.social.website', ['{name}' => $nameRaw]), 'icon' => 'globe', 'class' => 'sp-detail-social__link--website'],
    ['url' => $linkedin, 'label' => 'LinkedIn', 'icon' => 'linkedin', 'class' => ''],
    ['url' => $xing, 'label' => 'Xing', 'icon' => 'xing', 'class' => ''],
    ['url' => $twitter, 'label' => 'X', 'icon' => 'x', 'class' => ''],
    ['url' => $instagram, 'label' => 'Instagram', 'icon' => 'instagram', 'class' => ''],
    ['url' => $youtube, 'label' => 'YouTube', 'icon' => 'youtube', 'class' => ''],
    ['url' => $github, 'label' => 'GitHub', 'icon' => 'github', 'class' => ''],
    ['url' => $gitlab, 'label' => 'GitLab', 'icon' => 'gitlab', 'class' => ''],
] as $socialLink) {
    if ($socialLink['url'] !== '') {
        $socialLinks[] = $socialLink;
    }
}
$detailRows = array_filter([
    cms_speakers_detail_t('cms_speakers.detail.label.position') => $job,
    cms_speakers_detail_t('cms_speakers.detail.label.company') => $company,
    cms_speakers_detail_t('cms_speakers.detail.label.availability') => $availabilityLabel,
    cms_speakers_detail_t('cms_speakers.detail.label.travel') => $travelLabels[$travelRadius] ?? '',
    cms_speakers_detail_t('cms_speakers.detail.label.topics') => !empty($topicList) ? (string) count($topicList) : '',
    cms_speakers_detail_t('cms_speakers.detail.label.appearances') => !empty($eventList) ? (string) count($eventList) : '',
], static fn($value): bool => trim((string) $value) !== '');
$contactUrl = $email !== '' ? 'mailto:' . $email . '?subject=' . rawurlencode(cms_speakers_detail_t('cms_speakers.detail.mail_subject', ['{name}' => $nameRaw])) : $baseUrl . '/contact/';
$appearanceWord = count($eventList) === 1 ? cms_speakers_detail_t('cms_speakers.detail.appearance_singular') : cms_speakers_detail_t('cms_speakers.detail.appearance_plural');
$firstNameLabel = $firstName !== '' ? $firstName : $nameRaw;
?>
<main class="phinit-plugin cms-speaker-wrap cms-speaker-detail sp-detail">
    <nav class="sp-detail__breadcrumb" aria-label="<?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.aria.breadcrumb')) ?>">
        <a href="<?= cms_speakers_detail_e($baseUrl . '/') ?>"><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.breadcrumb.home')) ?></a>
        <span class="sp-detail__breadcrumb-sep" aria-hidden="true">›</span>
        <a href="<?= cms_speakers_detail_e($baseUrl . '/speakers/') ?>"><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.breadcrumb.speakers')) ?></a>
        <span class="sp-detail__breadcrumb-sep" aria-hidden="true">›</span>
        <span aria-current="page"><?= cms_speakers_detail_e($nameRaw) ?></span>
    </nav>

    <section class="sp-detail-hero" aria-labelledby="speaker-detail-title">
        <span class="sp-detail-status sp-detail-status--<?= cms_speakers_detail_e($availabilityClass) ?>"><span class="sp-detail-status__dot" aria-hidden="true"></span><?= cms_speakers_detail_e($availabilityLabel) ?></span>
        <div class="sp-detail-hero__body">
            <div class="sp-detail-avatar<?= $avatar === '' ? ' sp-detail-avatar--initials' : '' ?>">
                <?php if ($avatar !== ''): ?>
                    <img src="<?= cms_speakers_detail_e($avatar) ?>" alt="<?= cms_speakers_detail_e($nameRaw) ?>" width="116" height="116" loading="eager" decoding="async">
                <?php else: ?>
                    <span aria-hidden="true"><?= cms_speakers_detail_e($initials) ?></span>
                <?php endif; ?>
            </div>
            <div class="sp-detail-hero__content">
                <div class="sp-detail-hero__name-row">
                    <h1 id="speaker-detail-title"><?= cms_speakers_detail_e($nameRaw) ?></h1>
                    <?php if (!empty($eventList)): ?>
                        <span class="sp-detail-pill"><?= cms_speakers_detail_icon('megaphone') ?><?= (int) count($eventList) ?> <?= cms_speakers_detail_e($appearanceWord) ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($job !== '' || $company !== ''): ?>
                    <p class="sp-detail-hero__role">
                        <?php if ($job !== ''): ?><strong><?= cms_speakers_detail_e($job) ?></strong><?php endif; ?>
                        <?php if ($job !== '' && $company !== ''): ?><span aria-hidden="true"> · </span><?php endif; ?>
                        <?php if ($company !== ''): ?><span><?= cms_speakers_detail_e($company) ?></span><?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($socialLinks)): ?>
                    <div class="sp-detail-social sp-detail-social--hero" aria-label="<?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.aria.social_links')) ?>">
                        <?php foreach ($socialLinks as $socialLink): ?>
                            <a class="sp-detail-social__link <?= cms_speakers_detail_e($socialLink['class']) ?>" href="<?= cms_speakers_detail_e($socialLink['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= cms_speakers_detail_e($socialLink['label']) ?>"><?= cms_speakers_detail_icon($socialLink['icon']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($topicList) || !empty($formatList)): ?>
                    <div class="sp-detail-hero__tags" aria-label="<?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.aria.hero_tags')) ?>">
                        <?php foreach (array_slice($topicList, 0, 4) as $topic): ?>
                            <span class="sp-detail-tag-light"><?= cms_speakers_detail_e((string) $topic) ?></span>
                        <?php endforeach; ?>
                        <?php foreach (array_slice($formatList, 0, max(0, 6 - count($topicList))) as $format): ?>
                            <span class="sp-detail-tag-light sp-detail-tag-light--icon"><?= cms_speakers_detail_icon('microphone') ?><?= cms_speakers_detail_e((string) $format) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="sp-detail-grid">
        <div class="sp-detail-main">
            <?php if ($bio !== ''): ?>
                <section class="sp-detail-card" aria-labelledby="speaker-about-heading">
                    <div class="sp-detail-card__pad">
                        <h2 class="sp-detail-card__title" id="speaker-about-heading"><?= cms_speakers_detail_icon('info') ?><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.heading_about', ['{name}' => $firstNameLabel])) ?></h2>
                        <div class="sp-detail-prose"><?= nl2br(cms_speakers_detail_e($bio)) ?></div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($topicList) || !empty($formatList) || !empty($skillList)): ?>
                <section class="sp-detail-card" aria-labelledby="speaker-topics-heading">
                    <div class="sp-detail-card__pad">
                        <h2 class="sp-detail-card__title" id="speaker-topics-heading"><?= cms_speakers_detail_icon('tag') ?><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.heading_topics_formats')) ?></h2>
                        <?php if (!empty($topicList)): ?>
                            <p class="sp-detail-sub-label"><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.subheading_topics')) ?></p>
                            <div class="sp-detail-chips">
                                <?php foreach ($topicList as $topic): ?>
                                    <span class="sp-detail-chip"><span class="sp-detail-chip__dot" aria-hidden="true"></span><?= cms_speakers_detail_e((string) $topic) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($formatList)): ?>
                            <p class="sp-detail-sub-label"><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.subheading_formats')) ?></p>
                            <div class="sp-detail-chips">
                                <?php foreach ($formatList as $format): ?>
                                    <span class="sp-detail-chip sp-detail-chip--plain"><?= cms_speakers_detail_e((string) $format) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($skillList)): ?>
                            <p class="sp-detail-sub-label"><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.subheading_skills')) ?></p>
                            <div class="sp-detail-chips">
                                <?php foreach (array_slice($skillList, 0, 14) as $skill): ?>
                                    <span class="sp-detail-chip sp-detail-chip--plain"><?= cms_speakers_detail_e((string) $skill) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="sp-detail-card" aria-labelledby="speaker-events-heading">
                <div class="sp-detail-card__pad">
                    <h2 class="sp-detail-card__title" id="speaker-events-heading">
                        <?= cms_speakers_detail_icon('calendar') ?><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.heading_events')) ?>
                        <?php if (!empty($eventList)): ?><span class="sp-detail-card__badge"><?= (int) count($eventList) ?></span><?php endif; ?>
                    </h2>
                    <?php if (empty($eventList)): ?>
                        <p class="sp-detail-muted"><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.empty_events')) ?></p>
                    <?php else: ?>
                        <?php foreach ([
                            ['title' => cms_speakers_detail_t('cms_speakers.detail.subheading_future_events'), 'items' => $futureEvents],
                            ['title' => cms_speakers_detail_t('cms_speakers.detail.subheading_past_events'), 'items' => $pastEvents],
                        ] as $eventGroup): ?>
                            <?php if (empty($eventGroup['items'])) { continue; } ?>
                            <p class="sp-detail-sub-label"><?= cms_speakers_detail_e($eventGroup['title']) ?></p>
                            <div class="sp-detail-events">
                                <?php foreach ($eventGroup['items'] as $speakerEvent): ?>
                                    <?php
                                    $eventDateLabel = cms_speakers_detail_date((string) ($speakerEvent->event_date ?? ''));
                                    $eventTitle = trim((string) ($speakerEvent->event_title ?? $speakerEvent->title ?? cms_speakers_detail_t('cms_speakers.detail.event_type.event')));
                                    $eventLocation = trim((string) ($speakerEvent->event_location ?? $speakerEvent->location ?? ''));
                                    $eventType = trim((string) ($speakerEvent->event_type ?? ''));
                                    $presenceType = trim((string) ($speakerEvent->presence_type ?? ''));
                                    $eventLink = '';
                                    if (!empty($speakerEvent->cms_event_id)) {
                                        $eventLink = $baseUrl . '/events/' . (int) $speakerEvent->cms_event_id;
                                    } elseif (!empty($speakerEvent->event_url)) {
                                        $eventLink = cms_speakers_detail_public_url($speakerEvent->event_url);
                                    }
                                    ?>
                                    <article class="sp-detail-event">
                                        <p class="sp-detail-event__date"><?= cms_speakers_detail_e($eventDateLabel !== '' ? $eventDateLabel : cms_speakers_detail_t('cms_speakers.detail.date_on_request')) ?></p>
                                        <h3><?= cms_speakers_detail_e($eventTitle) ?></h3>
                                        <?php if ($eventLocation !== ''): ?>
                                            <p class="sp-detail-event__location"><?= cms_speakers_detail_icon('map') ?><?= cms_speakers_detail_e($eventLocation) ?></p>
                                        <?php endif; ?>
                                        <?php if ($eventType !== '' || $presenceType !== ''): ?>
                                            <div class="sp-detail-event__tags">
                                                <?php if ($eventType !== ''): ?><span class="sp-detail-event__tag"><?= cms_speakers_detail_e($eventTypeLabels[$eventType] ?? cms_speakers_detail_format_label($eventType)) ?></span><?php endif; ?>
                                                <?php if ($presenceType !== ''): ?><span class="sp-detail-event__tag"><?= cms_speakers_detail_e($presenceLabels[$presenceType] ?? $presenceType) ?></span><?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($eventLink !== ''): ?>
                                            <a class="sp-detail-btn sp-detail-btn--ghost sp-detail-btn--block" href="<?= cms_speakers_detail_e($eventLink) ?>"><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.button_event')) ?></a>
                                        <?php endif; ?>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>

        <aside class="sp-detail-sidebar" aria-label="<?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.aria.sidebar')) ?>">
            <section class="sp-detail-card sp-detail-card--cta" aria-labelledby="speaker-booking-heading">
                <div class="sp-detail-card__pad">
                    <h2 class="sp-detail-card__title" id="speaker-booking-heading"><?= cms_speakers_detail_icon('mail') ?><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.heading_request_talk')) ?></h2>
                    <h3><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.booking_title', ['{name}' => $nameRaw])) ?></h3>
                    <p><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.booking_text')) ?></p>
                    <a class="sp-detail-btn sp-detail-btn--primary sp-detail-btn--block" href="<?= cms_speakers_detail_e($contactUrl) ?>"><?= cms_speakers_detail_icon('mail') ?><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.button_contact')) ?></a>
                </div>
            </section>

            <?php if (!empty($socialLinks)): ?>
                <section class="sp-detail-card" aria-labelledby="speaker-social-heading">
                    <div class="sp-detail-card__pad">
                        <h2 class="sp-detail-card__title" id="speaker-social-heading"><?= cms_speakers_detail_icon('globe') ?><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.heading_social')) ?></h2>
                        <div class="sp-detail-social" aria-label="<?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.aria.social_links')) ?>">
                            <?php foreach ($socialLinks as $socialLink): ?>
                                <a class="sp-detail-social__link <?= cms_speakers_detail_e($socialLink['class']) ?>" href="<?= cms_speakers_detail_e($socialLink['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= cms_speakers_detail_e($socialLink['label']) ?>"><?= cms_speakers_detail_icon($socialLink['icon']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($detailRows)): ?>
                <section class="sp-detail-card" aria-labelledby="speaker-details-heading">
                    <div class="sp-detail-card__pad">
                        <h2 class="sp-detail-card__title" id="speaker-details-heading"><?= cms_speakers_detail_icon('list') ?><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.heading_details')) ?></h2>
                        <dl class="sp-detail-dl">
                            <?php foreach ($detailRows as $label => $value): ?>
                                <div class="sp-detail-dl__row"><dt><?= cms_speakers_detail_e((string) $label) ?></dt><dd><?= cms_speakers_detail_e((string) $value) ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($relatedSpeakers)): ?>
                <section class="sp-detail-card" aria-labelledby="speaker-related-heading">
                    <div class="sp-detail-card__pad">
                        <h2 class="sp-detail-card__title" id="speaker-related-heading"><?= cms_speakers_detail_icon('users') ?><?= cms_speakers_detail_e(cms_speakers_detail_t('cms_speakers.detail.heading_related')) ?></h2>
                        <div class="sp-detail-related">
                            <?php foreach ($relatedSpeakers as $related): ?>
                                <?php
                                $relatedName = trim((string) (($related->first_name ?? '') . ' ' . ($related->last_name ?? ''))) ?: cms_speakers_detail_t('cms_speakers.detail.fallback_speaker');
                                $relatedUrl = function_exists('cms_speaker_url') ? cms_speaker_url($related) : cms_speakers_detail_speaker_url($related);
                                $relatedInitials = cms_speakers_detail_initials((string) ($related->first_name ?? ''), (string) ($related->last_name ?? ''), $relatedName);
                                $relatedRole = trim((string) ($related->position ?? ''));
                                ?>
                                <a class="sp-detail-related__item" href="<?= cms_speakers_detail_e($relatedUrl) ?>">
                                    <span class="sp-detail-related__avatar" aria-hidden="true"><?= cms_speakers_detail_e($relatedInitials) ?></span>
                                    <span class="sp-detail-related__body"><strong><?= cms_speakers_detail_e($relatedName) ?></strong><?php if ($relatedRole !== ''): ?><small><?= cms_speakers_detail_e($relatedRole) ?></small><?php endif; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</main>
