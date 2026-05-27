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

        $letters = function_exists('mb_strtoupper') ? mb_strtoupper($letters, 'UTF-8') : strtoupper($letters);
        return $letters !== '' ? $letters : 'SP';
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
$bioRaw = trim(strip_tags((string) ($s->short_bio ?? $s->bio ?? $s->description ?? '')));
$avatar = cms_speakers_view_public_url($s->photo_url ?? $s->avatar_url ?? null);
$initials = cms_speakers_view_initials($firstName, $lastName, $nameRaw);
$speakerUrl = function_exists('cms_speaker_url') ? cms_speaker_url($s) : cms_speakers_view_speaker_url($s);
$eventCount = max(0, (int) ($s->event_count ?? $s->events_count ?? $s->presentation_count ?? 0));
$topics = [];

if (isset($s->_topics) && is_array($s->_topics)) {
    $topics = $s->_topics;
} elseif (!empty($s->topics)) {
    $decoded = json_decode((string) $s->topics, true);
    $topics = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', (string) $s->topics)));
} elseif (!empty($s->expertise)) {
    $topics = array_filter(array_map('trim', explode(',', (string) $s->expertise)));
}

$topics = array_values(array_unique(array_filter(array_map(static fn($topic): string => trim((string) $topic), $topics))));
$topicData = mb_strtolower(implode(' ', $topics), 'UTF-8');
$searchData = mb_strtolower(trim($nameRaw . ' ' . $job . ' ' . $company . ' ' . $topicData . ' ' . $bioRaw), 'UTF-8');
?>
<article class="speaker-card phinit-card cms-speaker-card"
         data-cms-speaker-card
         data-topic="<?= htmlspecialchars($topicData, ENT_QUOTES, 'UTF-8') ?>"
         data-category="<?= htmlspecialchars($topicData, ENT_QUOTES, 'UTF-8') ?>"
         data-name="<?= htmlspecialchars($searchData, ENT_QUOTES, 'UTF-8') ?>"
         data-speaker-url="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>"
         data-href="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>"
         role="link"
         tabindex="0"
         aria-label="Profil von <?= $name ?> ansehen">
    <div class="sc-avatar speaker-card-avatar cms-speaker-card__avatar">
        <?php if ($avatar !== ''): ?>
            <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $name ?>" class="speaker-avatar-img" width="56" height="56" loading="lazy" decoding="async">
        <?php else: ?>
            <div class="sc-initials speaker-initials speaker-avatar-fallback" aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </div>

    <div class="sc-body speaker-card-content cms-speaker-card__body">
        <div class="sc-name-row speaker-card-heading">
            <h2 class="sc-name speaker-name"><?= $name ?></h2>
            <?php if ($job !== '' || $company !== ''): ?>
                <p class="sc-role speaker-role cms-speaker-card__job">
                    <?= htmlspecialchars($job, ENT_QUOTES, 'UTF-8') ?><?php if ($company !== ''): ?><span class="sc-company speaker-company"><?= $job !== '' ? ' · ' : '' ?><?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if (!empty($topics)): ?>
            <div class="sc-tag-row speaker-card-meta">
                <?php foreach (array_slice($topics, 0, 4) as $topic): ?>
                    <?php $topicValue = mb_strtolower((string) $topic, 'UTF-8'); ?>
                    <span class="sc-tag speaker-tag cms-speaker-topic" data-topic-value="<?= htmlspecialchars($topicValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $topic, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($bioRaw !== ''): ?>
            <p class="sc-bio speaker-bio cms-speaker-card__bio"><?= htmlspecialchars($bioRaw, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <div class="sc-action speaker-card-action">
        <a href="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-speaker-profile phinit-btn phinit-btn--secondary cms-speaker-card__button" tabindex="-1">Profil ansehen</a>
    </div>
</article>