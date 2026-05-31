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

if (!function_exists('cms_speakers_view_css_color')) {
    function cms_speakers_view_css_color(mixed $value, string $fallback): string
    {
        $color = trim((string) $value);
        return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color) === 1 ? $color : $fallback;
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

        $letters = function_exists('mb_strtoupper') ? mb_strtoupper($letters, 'UTF-8') : strtoupper($letters);
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
            'other' => 'Sonstiges',
        ];

        $key = strtolower(trim($format));
        return $labels[$key] ?? trim($format);
    }
}

if (!function_exists('cms_speakers_view_social_url')) {
    function cms_speakers_view_social_url(mixed $value, string $network): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        $url = cms_speakers_view_public_url($raw);
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

if (!function_exists('cms_speakers_view_fee_range')) {
    function cms_speakers_view_fee_range(mixed $min, mixed $max): string
    {
        $minValue = is_numeric($min) ? (float) $min : 0.0;
        $maxValue = is_numeric($max) ? (float) $max : 0.0;

        if ($minValue <= 0 && $maxValue <= 0) {
            return '';
        }

        if ($minValue > 0 && $maxValue > 0) {
            return number_format($minValue, 0, ',', '.') . '–' . number_format($maxValue, 0, ',', '.') . ' €';
        }

        if ($minValue > 0) {
            return 'ab ' . number_format($minValue, 0, ',', '.') . ' €';
        }

        return 'bis ' . number_format($maxValue, 0, ',', '.') . ' €';
    }
}

$s = is_object($speaker) ? $speaker : (object) $speaker;
$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$firstName = trim((string) ($s->first_name ?? ''));
$lastName = trim((string) ($s->last_name ?? ''));
$nameRaw = trim($firstName . ' ' . $lastName) ?: 'Speaker';
$name = htmlspecialchars($nameRaw, ENT_QUOTES, 'UTF-8');
$job = trim((string) ($s->position ?? $s->job_title ?? ''));
$company = trim((string) ($s->company_linked_name ?? $s->company_name ?? $s->company ?? ''));
$location = trim((string) ($s->location_city ?? ''));
$avatar = cms_speakers_view_public_url($s->photo_url ?? $s->avatar_url ?? null);
$initials = cms_speakers_view_initials($firstName, $lastName, $nameRaw);
$email = filter_var((string) ($s->email ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
$website = cms_speakers_view_public_url($s->website ?? $s->website_url ?? null);
$linkedin = cms_speakers_view_public_url($s->linkedin ?? $s->linkedin_url ?? null);
$twitter = cms_speakers_view_social_url($s->twitter ?? null, 'x');
$xing = cms_speakers_view_public_url($s->xing ?? null);
$instagram = cms_speakers_view_social_url($s->instagram ?? null, 'instagram');
$youtube = cms_speakers_view_public_url($s->youtube ?? null);
$github = cms_speakers_view_social_url($s->github ?? null, 'github');
$gitlab = cms_speakers_view_social_url($s->gitlab ?? null, 'gitlab');
$bio = trim((string) ($s->bio ?? $s->short_bio ?? ''));
$topicList = cms_speakers_view_list_values($topics ?? []);
if (empty($topicList) && !empty($s->topics)) {
    $topicList = cms_speakers_view_list_values($s->topics);
}
$topicList = array_values(array_unique(array_filter(array_map(static fn($topic): string => trim((string) $topic), $topicList))));
$formatList = array_map('cms_speakers_view_format_label', cms_speakers_view_list_values($s->formats ?? null));
$languageList = cms_speakers_view_list_values($s->languages ?? null);
$skillLabels = class_exists('CMS_Speakers_Meta_Boxes') ? CMS_Speakers_Meta_Boxes::get_skill_labels() : [];
$skillList = [];
foreach (cms_speakers_view_list_values($s->skills ?? null) as $skill) {
    $skillKey = trim((string) $skill);
    $skillList[] = $skillLabels[$skillKey] ?? ucwords(str_replace('_', ' ', $skillKey));
}
$recognitionList = [];
foreach (cms_speakers_view_list_values($s->recognitions ?? null) as $recognition) {
    $recognitionList[] = ucwords(str_replace('_', ' ', (string) $recognition));
}
$awardList = array_values(array_filter(array_map('trim', preg_split('/\R+/', (string) ($s->awards ?? '')) ?: [])));
$targetAudience = trim((string) ($s->target_audience ?? ''));
$speakingStyle = trim((string) ($s->speaking_style ?? ''));
$travelRadius = trim((string) ($s->travel_radius ?? ''));
$maxAudience = (int) ($s->max_audience_size ?? 0);
$availability = trim((string) ($s->availability ?? 'available'));
$availabilityLabels = [
    'available' => 'Verfügbar',
    'limited' => 'Begrenzt verfügbar',
    'booked' => 'Ausgebucht',
];
$availabilityClass = in_array($availability, ['available', 'limited', 'booked'], true) ? $availability : 'request';
$availabilityLabel = $availabilityLabels[$availabilityClass] ?? 'Auf Anfrage';
$travelLabels = [
    'local' => 'Lokal',
    'regional' => 'Regional',
    'national' => 'National',
    'international' => 'International',
    'worldwide' => 'Weltweit',
];
$presenceLabels = [
    'presence' => 'Präsenz',
    'online' => 'Online',
    'hybrid' => 'Hybrid',
];
$eventTypeLabels = [
    'keynote' => 'Keynote',
    'workshop' => 'Workshop',
    'panel' => 'Panel',
    'moderation' => 'Moderation',
    'interview' => 'Interview',
    'webinar' => 'Webinar',
    'conference' => 'Konferenz',
    'training' => 'Training',
    'other' => 'Auftritt',
];
$feeRange = cms_speakers_view_fee_range($s->speaking_fee_min ?? null, $s->speaking_fee_max ?? null);
$settings = is_array($settings ?? null) ? $settings : [];
$speakerPrimary = cms_speakers_view_css_color($settings['design_primary_color'] ?? null, '#8b5cf6');
$speakerAccent = cms_speakers_view_css_color($settings['design_accent_color'] ?? null, '#7c3aed');
$speakerCardBg = cms_speakers_view_css_color($settings['design_card_bg'] ?? null, '#faf5ff');
$speakerHeaderFrom = cms_speakers_view_css_color($settings['detail_header_bg_from'] ?? ($settings['archive_header_bg_from'] ?? null), '#4c1d95');
$speakerHeaderTo = cms_speakers_view_css_color($settings['detail_header_bg_to'] ?? ($settings['archive_header_bg_to'] ?? null), '#7c3aed');
$speakerHeaderTitle = cms_speakers_view_css_color($settings['detail_header_title_color'] ?? ($settings['archive_header_title_color'] ?? null), '#ffffff');
$speakerRadius = max(0, min(32, (int) ($settings['design_border_radius'] ?? 12)));
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
    ['url' => $linkedin, 'label' => 'LinkedIn', 'icon' => 'ti ti-brand-linkedin'],
    ['url' => $xing, 'label' => 'Xing', 'icon' => 'ti ti-brand-xing'],
    ['url' => $twitter, 'label' => 'X', 'icon' => 'ti ti-brand-x'],
    ['url' => $instagram, 'label' => 'Instagram', 'icon' => 'ti ti-brand-instagram'],
    ['url' => $youtube, 'label' => 'YouTube', 'icon' => 'ti ti-brand-youtube'],
    ['url' => $github, 'label' => 'GitHub', 'icon' => 'ti ti-brand-github'],
    ['url' => $gitlab, 'label' => 'GitLab', 'icon' => 'ti ti-brand-gitlab'],
] as $socialLink) {
    if ($socialLink['url'] !== '') {
        $socialLinks[] = $socialLink;
    }
}
$profileRows = array_filter([
    'Position' => $job,
    'Unternehmen' => $company,
    'Standort' => $location,
    'Verfügbarkeit' => $availabilityLabel,
    'Reisebereitschaft' => $travelLabels[$travelRadius] ?? '',
    'Themen' => !empty($topicList) ? (string) count($topicList) : '',
    'Auftritte' => !empty($eventList) ? (string) count($eventList) : '',
], static fn($value): bool => trim((string) $value) !== '');
?>
<style>
:root {
    --sp-primary: <?= htmlspecialchars($speakerPrimary, ENT_QUOTES, 'UTF-8') ?>;
    --sp-primary-h: <?= htmlspecialchars($speakerAccent, ENT_QUOTES, 'UTF-8') ?>;
    --sp-accent: <?= htmlspecialchars($speakerAccent, ENT_QUOTES, 'UTF-8') ?>;
    --sp-secondary: <?= htmlspecialchars($speakerAccent, ENT_QUOTES, 'UTF-8') ?>;
    --sp-card-bg: <?= htmlspecialchars($speakerCardBg, ENT_QUOTES, 'UTF-8') ?>;
    --sp-card-top-bg: <?= htmlspecialchars($speakerCardBg, ENT_QUOTES, 'UTF-8') ?>;
    --sp-hdr-from: <?= htmlspecialchars($speakerHeaderFrom, ENT_QUOTES, 'UTF-8') ?>;
    --sp-hdr-to: <?= htmlspecialchars($speakerHeaderTo, ENT_QUOTES, 'UTF-8') ?>;
    --sp-hdr-title: <?= htmlspecialchars($speakerHeaderTitle, ENT_QUOTES, 'UTF-8') ?>;
    --sp-border: <?= htmlspecialchars($speakerAccent, ENT_QUOTES, 'UTF-8') ?>;
    --sp-radius: <?= (int) $speakerRadius ?>px;
}
</style>
<main class="phinit-plugin cms-speaker-wrap cms-speaker-detail sp-single-v2">
    <nav class="cms-speaker-breadcrumb sp-breadcrumb" aria-label="Breadcrumb">
        <a href="<?= htmlspecialchars($baseUrl . '/', ENT_QUOTES, 'UTF-8') ?>">Home</a>
        <span class="sp-breadcrumb__sep" aria-hidden="true">›</span>
        <a href="<?= htmlspecialchars($baseUrl . '/speakers/', ENT_QUOTES, 'UTF-8') ?>">Speaker</a>
        <span class="sp-breadcrumb__sep" aria-hidden="true">›</span>
        <span class="sp-breadcrumb__cur" aria-current="page"><?= $name ?></span>
    </nav>

    <header class="sp-hero-v2" aria-labelledby="speaker-detail-title">
        <div class="sp-hero-v2__inner">
            <div class="sp-hero-v2__badges" aria-label="Speaker-Status">
                <span class="sp-hero-v2__badge sp-hero-v2__badge--avail-<?= htmlspecialchars($availabilityClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($availabilityLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($s->is_verified)): ?>
                    <span class="sp-hero-v2__badge sp-hero-v2__badge--verified"><i class="ti ti-circle-check" aria-hidden="true"></i>Verifiziert</span>
                <?php endif; ?>
                <?php if (!empty($s->is_featured)): ?>
                    <span class="sp-hero-v2__badge sp-hero-v2__badge--mvp"><i class="ti ti-star" aria-hidden="true"></i>Featured</span>
                <?php endif; ?>
            </div>

            <div class="sp-hero-v2__av<?= $avatar === '' ? ' sp-hero-v2__av--placeholder' : '' ?>">
                <?php if ($avatar !== ''): ?>
                    <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $name ?>" width="125" height="125" loading="eager" decoding="async">
                <?php else: ?>
                    <span aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>

            <div class="sp-hero-v2__meta">
                <div class="sp-hero-v2__name-row">
                    <h1 class="sp-hero-v2__name" id="speaker-detail-title"><?= $name ?></h1>
                    <?php if (!empty($eventList)): ?>
                        <span class="sp-hero-v2__ev-count"><i class="ti ti-microphone-2" aria-hidden="true"></i><?= (int) count($eventList) ?> Auftritt<?= count($eventList) === 1 ? '' : 'e' ?></span>
                    <?php endif; ?>
                </div>

                <?php if ($job !== ''): ?>
                    <p class="sp-hero-v2__pos"><?= htmlspecialchars($job, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>
                <?php if ($company !== '' || $location !== ''): ?>
                    <p class="sp-hero-v2__co">
                        <?php if ($company !== ''): ?><span><?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                        <?php if ($company !== '' && $location !== ''): ?><span aria-hidden="true"> · </span><?php endif; ?>
                        <?php if ($location !== ''): ?><span><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($topicList)): ?>
                    <div class="sp-hero-v2__spec-pills" aria-label="Themen">
                        <?php foreach (array_slice($topicList, 0, 6) as $topic): ?>
                            <span class="sp-hero-v2__spec-pill"><?= htmlspecialchars((string) $topic, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($formatList)): ?>
                    <div class="sp-hero-v2__chips" aria-label="Formate">
                        <?php foreach (array_slice($formatList, 0, 5) as $format): ?>
                            <span class="sp-hero-v2__chip"><i class="ti ti-presentation" aria-hidden="true"></i><?= htmlspecialchars((string) $format, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="sp-bridge">
        <section class="sp-bridge__about" aria-labelledby="speaker-about-heading">
            <h2 class="sp-bridge__title" id="speaker-about-heading">Über <?= htmlspecialchars($firstName !== '' ? $firstName : $nameRaw, ENT_QUOTES, 'UTF-8') ?></h2>
            <?php if ($bio !== ''): ?>
                <div class="sp-bridge__text"><?= nl2br(htmlspecialchars($bio, ENT_QUOTES, 'UTF-8')) ?></div>
            <?php else: ?>
                <p class="sp-prose-muted">Noch keine Biografie hinterlegt.</p>
            <?php endif; ?>
        </section>

        <section class="sp-bridge__contact" aria-labelledby="speaker-contact-heading">
            <h2 class="sp-bridge__title" id="speaker-contact-heading">Kontakt &amp; Links</h2>
            <div class="sp-bridge__contact-body">
                <div class="sp-bridge__row">
                    <?php if ($email !== ''): ?>
                        <a class="sp-btn-v2 sp-btn-v2--sm sp-btn-v2--block sp-btn-v2--no-margin" href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>?subject=<?= rawurlencode('Speaker-Anfrage: ' . $nameRaw) ?>"><i class="ti ti-mail" aria-hidden="true"></i>Speaker anfragen</a>
                    <?php else: ?>
                        <a class="sp-btn-v2 sp-btn-v2--sm sp-btn-v2--block sp-btn-v2--no-margin" href="<?= htmlspecialchars($baseUrl . '/contact/', ENT_QUOTES, 'UTF-8') ?>"><i class="ti ti-mail" aria-hidden="true"></i>Kontakt aufnehmen</a>
                    <?php endif; ?>
                </div>
                <?php if ($website !== '' || $linkedin !== '' || $email !== ''): ?>
                    <div class="sp-bridge__links" aria-label="Kontaktlinks">
                        <?php if ($website !== ''): ?><a class="sp-bridge__link" href="<?= htmlspecialchars($website, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="ti ti-world" aria-hidden="true"></i>Website</a><?php endif; ?>
                        <?php if ($linkedin !== ''): ?><a class="sp-bridge__link" href="<?= htmlspecialchars($linkedin, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="ti ti-brand-linkedin" aria-hidden="true"></i>LinkedIn</a><?php endif; ?>
                        <?php if ($email !== ''): ?><a class="sp-bridge__link" href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"><i class="ti ti-mail" aria-hidden="true"></i>E-Mail</a><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($socialLinks)): ?>
                    <div class="sp-bridge__socials" aria-label="Social Links">
                        <?php foreach (array_slice($socialLinks, 0, 5) as $socialLink): ?>
                            <a class="sp-si" href="<?= htmlspecialchars($socialLink['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= htmlspecialchars($socialLink['label'], ENT_QUOTES, 'UTF-8') ?>"><i class="<?= htmlspecialchars($socialLink['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="sp-body-v2">
        <main class="sp-main-v2" aria-label="Speaker-Profilinhalt">
            <?php if (!empty($topicList) || !empty($formatList) || !empty($skillList)): ?>
                <section class="sp-sec-v2" aria-labelledby="speaker-topics-heading">
                    <h2 class="sp-sec-v2__title" id="speaker-topics-heading"><i class="ti ti-tags" aria-hidden="true"></i>Themen &amp; Formate</h2>
                    <?php if (!empty($topicList)): ?>
                        <div class="sp-pills-v2" aria-label="Themen">
                            <?php foreach ($topicList as $topic): ?>
                                <span class="sp-pill sp-pill--skill"><?= htmlspecialchars((string) $topic, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($formatList)): ?>
                        <p class="sp-ev-subhead sp-ev-subhead--gap">Vortragsformate</p>
                        <div class="sp-skills-grid">
                            <?php foreach ($formatList as $format): ?>
                                <div class="sp-skill-item sp-skill-item--fmt"><span class="sp-skill-item__name"><?= htmlspecialchars((string) $format, ENT_QUOTES, 'UTF-8') ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($skillList)): ?>
                        <p class="sp-ev-subhead sp-ev-subhead--gap">Speaker-Skills</p>
                        <div class="sp-skills-grid">
                            <?php foreach (array_slice($skillList, 0, 12) as $skill): ?>
                                <div class="sp-skill-item"><span class="sp-skill-item__name"><?= htmlspecialchars((string) $skill, ENT_QUOTES, 'UTF-8') ?></span></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($targetAudience !== '' || $speakingStyle !== '' || !empty($languageList) || $travelRadius !== '' || $maxAudience > 0): ?>
                <section class="sp-sec-v2" aria-labelledby="speaker-profile-heading">
                    <h2 class="sp-sec-v2__title" id="speaker-profile-heading"><i class="ti ti-presentation" aria-hidden="true"></i>Vortragsprofil</h2>
                    <div class="sp-info-rows-v2">
                        <?php if ($targetAudience !== ''): ?><div class="sp-info-row-v2"><span class="sp-info-row-v2__lbl">Zielgruppe</span><span class="sp-info-row-v2__val"><?= htmlspecialchars($targetAudience, ENT_QUOTES, 'UTF-8') ?></span></div><?php endif; ?>
                        <?php if ($speakingStyle !== ''): ?><div class="sp-info-row-v2"><span class="sp-info-row-v2__lbl">Stil</span><span class="sp-info-row-v2__val"><?= htmlspecialchars($speakingStyle, ENT_QUOTES, 'UTF-8') ?></span></div><?php endif; ?>
                        <?php if (!empty($languageList)): ?><div class="sp-info-row-v2"><span class="sp-info-row-v2__lbl">Sprachen</span><span class="sp-info-row-v2__val"><?= htmlspecialchars(implode(', ', $languageList), ENT_QUOTES, 'UTF-8') ?></span></div><?php endif; ?>
                        <?php if ($travelRadius !== ''): ?><div class="sp-info-row-v2"><span class="sp-info-row-v2__lbl">Reise</span><span class="sp-info-row-v2__val"><?= htmlspecialchars($travelLabels[$travelRadius] ?? $travelRadius, ENT_QUOTES, 'UTF-8') ?></span></div><?php endif; ?>
                        <?php if ($maxAudience > 0): ?><div class="sp-info-row-v2"><span class="sp-info-row-v2__lbl">Publikum</span><span class="sp-info-row-v2__val">bis <?= htmlspecialchars(number_format($maxAudience, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?> Personen</span></div><?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($recognitionList) || !empty($awardList)): ?>
                <section class="sp-sec-v2" aria-labelledby="speaker-recognition-heading">
                    <h2 class="sp-sec-v2__title" id="speaker-recognition-heading"><i class="ti ti-award" aria-hidden="true"></i>Auszeichnungen &amp; Anerkennung</h2>
                    <?php if (!empty($recognitionList)): ?>
                        <div class="sp-pills-v2">
                            <?php foreach ($recognitionList as $recognition): ?>
                                <span class="sp-pill sp-pill--fmt"><?= htmlspecialchars((string) $recognition, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($awardList)): ?>
                        <div class="sp-text-body sp-text-body--mb">
                            <?php foreach ($awardList as $award): ?>
                                <p><?= htmlspecialchars((string) $award, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section class="sp-sec-v2" aria-labelledby="speaker-events-heading">
                <h2 class="sp-sec-v2__title" id="speaker-events-heading"><i class="ti ti-calendar-event" aria-hidden="true"></i>Vorträge &amp; Sessions<?php if (!empty($eventList)): ?><span class="sp-count-badge"><?= (int) count($eventList) ?></span><?php endif; ?></h2>
                <?php if (empty($eventList)): ?>
                    <p class="sp-prose-muted">Aktuell sind keine öffentlichen Auftritte hinterlegt.</p>
                <?php else: ?>
                    <?php foreach ([['title' => 'Kommende Auftritte', 'items' => $futureEvents, 'state' => 'future'], ['title' => 'Vergangene Auftritte', 'items' => $pastEvents, 'state' => 'past']] as $eventGroup): ?>
                        <?php if (empty($eventGroup['items'])) { continue; } ?>
                        <p class="sp-ev-subhead<?= $eventGroup['state'] === 'past' && !empty($futureEvents) ? ' sp-ev-subhead--gap' : '' ?>"><?= htmlspecialchars($eventGroup['title'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="sp-ev-grid-v2">
                            <?php foreach ($eventGroup['items'] as $speakerEvent): ?>
                                <?php
                                [, , $eventDateLabel] = cms_speaker_view_date_parts((string) ($speakerEvent->event_date ?? ''));
                                $eventTitle = trim((string) ($speakerEvent->event_title ?? $speakerEvent->title ?? 'Event'));
                                $talkTitle = trim((string) ($speakerEvent->topic ?? ''));
                                $eventLocation = trim((string) ($speakerEvent->event_location ?? $speakerEvent->location ?? ''));
                                $eventType = trim((string) ($speakerEvent->event_type ?? ''));
                                $presenceType = trim((string) ($speakerEvent->presence_type ?? ''));
                                $organizer = trim((string) ($speakerEvent->company_name ?? $speakerEvent->organizer_name ?? ''));
                                $audienceSize = (int) ($speakerEvent->audience_size ?? 0);
                                $eventLink = '';
                                if (!empty($speakerEvent->cms_event_id)) {
                                    $eventLink = $baseUrl . '/events/' . (int) $speakerEvent->cms_event_id;
                                } elseif (!empty($speakerEvent->event_url)) {
                                    $eventLink = cms_speakers_view_public_url($speakerEvent->event_url);
                                }
                                ?>
                                <article class="sp-ev-v2 sp-ev-v2--<?= htmlspecialchars($eventGroup['state'], ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="sp-ev-v2__date"><?= htmlspecialchars($eventDateLabel !== '' ? $eventDateLabel : 'Termin auf Anfrage', ENT_QUOTES, 'UTF-8') ?></div>
                                    <h3 class="sp-ev-v2__title"><?= htmlspecialchars($eventTitle, ENT_QUOTES, 'UTF-8') ?></h3>
                                    <?php if ($talkTitle !== '' && strcasecmp($talkTitle, $eventTitle) !== 0): ?>
                                        <p class="sp-ev-v2__talk"><?= htmlspecialchars($talkTitle, ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                    <p class="sp-ev-v2__meta">
                                        <?php if ($eventLocation !== ''): ?><span><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($eventLocation, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                        <?php if ($organizer !== ''): ?><span><i class="ti ti-building" aria-hidden="true"></i><?= htmlspecialchars($organizer, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                        <?php if ($audienceSize > 0): ?><span><i class="ti ti-users" aria-hidden="true"></i><?= htmlspecialchars(number_format($audienceSize, 0, ',', '.'), ENT_QUOTES, 'UTF-8') ?> Teilnehmende</span><?php endif; ?>
                                    </p>
                                    <?php if ($eventType !== '' || $presenceType !== ''): ?>
                                        <div class="sp-ev-v2__badges">
                                            <?php if ($eventType !== ''): ?><span class="sp-ev-v2__badge"><?= htmlspecialchars($eventTypeLabels[$eventType] ?? cms_speakers_view_format_label($eventType), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                            <?php if ($presenceType !== ''): ?><span class="sp-ev-v2__badge sp-ev-v2__badge--online"><?= htmlspecialchars($presenceLabels[$presenceType] ?? $presenceType, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($eventLink !== ''): ?>
                                        <a class="sp-btn-v2 sp-btn-v2--ghost sp-btn-v2--sm" href="<?= htmlspecialchars($eventLink, ENT_QUOTES, 'UTF-8') ?>">Zum Event</a>
                                    <?php endif; ?>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>
        </main>

        <aside class="sp-sidebar-v2" aria-label="Speaker-Details und Buchung">
            <?php if (!empty($profileRows)): ?>
                <section class="sp-sc-v2" aria-labelledby="speaker-details-heading">
                    <h2 class="sp-sc-v2__title" id="speaker-details-heading">Details</h2>
                    <div class="sp-info-rows-v2">
                        <?php foreach ($profileRows as $label => $value): ?>
                            <div class="sp-info-row-v2"><span class="sp-info-row-v2__lbl"><?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?></span><span class="sp-info-row-v2__val"><?= htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') ?></span></div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="sp-sc-v2" aria-labelledby="speaker-booking-heading">
                <h2 class="sp-sc-v2__title" id="speaker-booking-heading">Buchung</h2>
                <p class="sp-prose-muted sp-prose-muted--spaced"><?= $name ?> für Keynote, Workshop, Panel oder Moderation anfragen.</p>
                <?php if ($feeRange !== ''): ?>
                    <div class="sp-info-row-v2"><span class="sp-info-row-v2__lbl">Honorar</span><span class="sp-info-row-v2__val"><?= htmlspecialchars($feeRange, ENT_QUOTES, 'UTF-8') ?></span></div>
                <?php endif; ?>
                <?php if ($email !== ''): ?>
                    <a class="sp-btn-v2" href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>?subject=<?= rawurlencode('Speaker-Anfrage: ' . $nameRaw) ?>"><i class="ti ti-mail" aria-hidden="true"></i>Kontakt aufnehmen</a>
                <?php else: ?>
                    <a class="sp-btn-v2" href="<?= htmlspecialchars($baseUrl . '/contact/', ENT_QUOTES, 'UTF-8') ?>"><i class="ti ti-mail" aria-hidden="true"></i>Kontakt aufnehmen</a>
                <?php endif; ?>
            </section>

            <?php if (!empty($socialLinks) || $website !== '' || $email !== ''): ?>
                <section class="sp-sc-v2" aria-labelledby="speaker-links-heading">
                    <h2 class="sp-sc-v2__title" id="speaker-links-heading">Links</h2>
                    <div class="sp-social-row-v2" aria-label="Profil- und Social-Links">
                        <?php if ($website !== ''): ?><a class="sp-si-v2" href="<?= htmlspecialchars($website, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Website"><i class="ti ti-world" aria-hidden="true"></i></a><?php endif; ?>
                        <?php if ($email !== ''): ?><a class="sp-si-v2" href="mailto:<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" aria-label="E-Mail"><i class="ti ti-mail" aria-hidden="true"></i></a><?php endif; ?>
                        <?php foreach ($socialLinks as $socialLink): ?>
                            <a class="sp-si-v2" href="<?= htmlspecialchars($socialLink['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= htmlspecialchars($socialLink['label'], ENT_QUOTES, 'UTF-8') ?>"><i class="<?= htmlspecialchars($socialLink['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i></a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($relatedSpeakers)): ?>
                <section class="sp-sc-v2 cms-speaker-related" aria-labelledby="cms-related-speakers-heading">
                    <h2 class="sp-sc-v2__title" id="cms-related-speakers-heading">Weitere Speaker</h2>
                    <?php foreach ($relatedSpeakers as $related): ?>
                        <?php
                        $relatedName = trim((string) (($related->first_name ?? '') . ' ' . ($related->last_name ?? ''))) ?: 'Speaker';
                        $relatedAvatar = cms_speakers_view_public_url($related->photo_url ?? null);
                        $relatedUrl = function_exists('cms_speaker_url') ? cms_speaker_url($related) : cms_speakers_view_speaker_url($related);
                        $relatedInitials = cms_speakers_view_initials((string) ($related->first_name ?? ''), (string) ($related->last_name ?? ''), $relatedName);
                        ?>
                        <a class="cms-speaker-related__item" href="<?= htmlspecialchars($relatedUrl, ENT_QUOTES, 'UTF-8') ?>" data-speaker-url="<?= htmlspecialchars($relatedUrl, ENT_QUOTES, 'UTF-8') ?>">
                            <span class="cms-speaker-related__avatar">
                                <?php if ($relatedAvatar !== ''): ?>
                                    <img src="<?= htmlspecialchars($relatedAvatar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($relatedName, ENT_QUOTES, 'UTF-8') ?>" width="52" height="52" loading="lazy" decoding="async">
                                <?php else: ?>
                                    <span class="speaker-avatar-fallback speaker-avatar-fallback--small" aria-hidden="true"><?= htmlspecialchars($relatedInitials, ENT_QUOTES, 'UTF-8') ?></span>
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
