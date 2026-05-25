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
$speakerUrl = function_exists('cms_speaker_url') ? cms_speaker_url($s) : cms_speakers_view_speaker_url($s);
$linkedin = cms_speakers_view_public_url($s->linkedin ?? $s->linkedin_url ?? null);
$website = cms_speakers_view_public_url($s->website ?? $s->website_url ?? null);
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
<article class="phinit-card cms-speaker-card"
         data-cms-speaker-card
         data-topic="<?= htmlspecialchars($topicData, ENT_QUOTES, 'UTF-8') ?>"
         data-name="<?= htmlspecialchars($searchData, ENT_QUOTES, 'UTF-8') ?>">
    <a class="cms-speaker-card__avatar" href="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Profil von <?= $name ?> ansehen">
        <?php if ($avatar !== ''): ?>
            <img src="<?= htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $name ?>" width="100" height="100" loading="lazy" decoding="async">
        <?php else: ?>
            <i class="ti ti-user" aria-hidden="true"></i>
        <?php endif; ?>
    </a>

    <div class="cms-speaker-card__body">
        <h2><a href="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $name ?></a></h2>
        <?php if ($job !== '' || $company !== ''): ?>
            <p class="cms-speaker-card__job">
                <?= htmlspecialchars($job, ENT_QUOTES, 'UTF-8') ?><?= $job !== '' && $company !== '' ? ' · ' : '' ?><?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>

        <?php if (!empty($topics)): ?>
            <div class="cms-speaker-card__topics" aria-label="Themen">
                <?php foreach (array_slice($topics, 0, 4) as $topic): ?>
                    <span class="cms-speaker-topic"><?= htmlspecialchars((string) $topic, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($bioRaw !== ''): ?>
            <p class="cms-speaker-card__bio"><?= htmlspecialchars($bioRaw, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
    </div>

    <footer class="cms-speaker-card__footer">
        <a href="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary cms-speaker-card__button">Profil ansehen</a>
        <?php if ($linkedin !== '' || $website !== ''): ?>
            <div class="cms-speaker-card__social" aria-label="Social Links">
                <?php if ($linkedin !== ''): ?>
                    <a href="<?= htmlspecialchars($linkedin, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn Profil von <?= $name ?>"><i class="ti ti-brand-linkedin"></i></a>
                <?php endif; ?>
                <?php if ($website !== ''): ?>
                    <a href="<?= htmlspecialchars($website, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="Website von <?= $name ?>"><i class="ti ti-world"></i></a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </footer>
</article>