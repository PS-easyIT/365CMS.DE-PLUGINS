<?php
/** @var object|null $speaker */
/** @var array<int, object> $relatedEvents */
/** @var object|null $linkedCompany */
/** @var object|null $linkedExpert */
/** @var array<string, string> $settings */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
$base = rtrim((string) SITE_URL, '/');
$renderEditor = static function (mixed $json, mixed $fallback): string {
    $json = (string) $json;
    if ($json !== '' && class_exists('CMS\\Services\\EditorJsRenderer')) {
        $rendered = (string) CMS\Services\EditorJsRenderer::getInstance()->render($json);
        $plain = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($rendered), ENT_QUOTES, 'UTF-8')));
        $hasMediaBlocks = preg_match('/<(img|video|audio|iframe|table|ul|ol|blockquote|pre|h[1-6])\b/i', $rendered) === 1;
        if ($plain !== '' || $hasMediaBlocks) {
            return $rendered;
        }
    }
    $fallback = trim((string) $fallback);
    return $fallback !== '' ? '<div class="cms-events-copy">' . nl2br(htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8')) . '</div>' : '';
};
$badges = array_filter(array_map('trim', explode(',', (string) (($speaker->categories ?? '') ?: ($speaker->topic ?? '')))));
$tags = array_filter(array_map('trim', explode(',', (string) ($speaker->tags ?? ''))));
?>
<main class="cms-events-public cms-speaker-detail">
    <div class="cms-events-container">
        <nav class="cms-events-breadcrumb"><a href="<?= htmlspecialchars($base . '/speakers', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($settings['speaker_archive_title'] ?? 'Speaker'), ENT_QUOTES, 'UTF-8') ?></a><span>/</span><span><?= htmlspecialchars((string) ($speaker->display_name ?? ''), ENT_QUOTES, 'UTF-8') ?></span></nav>
        <article class="cms-events-detail-layout">
            <section class="cms-events-detail-main">
                <?php if (!empty($speaker->avatar_url)): ?><img class="cms-speaker-photo" src="<?= htmlspecialchars((string) $speaker->avatar_url, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($speaker->avatar_alt ?? $speaker->display_name ?? ''), ENT_QUOTES, 'UTF-8') ?>" loading="eager"><?php else: ?><span class="cms-speaker-avatar cms-speaker-avatar--large"><?= htmlspecialchars(strtoupper(substr((string) ($speaker->display_name ?? 'S'), 0, 1)), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                <h1><?= htmlspecialchars((string) ($speaker->display_name ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if (!empty($speaker->topic)): ?><p class="cms-events-lead"><?= htmlspecialchars((string) $speaker->topic, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <?php if ($badges !== [] || $tags !== []): ?><div class="cms-events-badges"><?php foreach (array_slice(array_merge($badges, $tags), 0, 12) as $badge): ?><span><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></div><?php endif; ?>
                <?= $renderEditor($speaker->bio_json ?? '', $speaker->bio ?? '') ?>
                <section class="cms-events-section"><h2>Events</h2><?php if ($relatedEvents === []): ?><p>Noch keine öffentlichen Events verknüpft.</p><?php else: ?><div class="cms-speaker-list"><?php foreach ($relatedEvents as $event): ?><a class="cms-speaker-row" href="<?= htmlspecialchars($base . '/events/' . rawurlencode((string) $event->slug), ENT_QUOTES, 'UTF-8') ?>"><span>📅</span><span><strong><?= htmlspecialchars((string) $event->title, ENT_QUOTES, 'UTF-8') ?></strong><small><?= htmlspecialchars((string) ($event->date_label ?? ''), ENT_QUOTES, 'UTF-8') ?><?= !empty($event->location) ? ' · ' . htmlspecialchars((string) $event->location, ENT_QUOTES, 'UTF-8') : '' ?></small></span></a><?php endforeach; ?></div><?php endif; ?></section>
            </section>
            <aside class="cms-events-sidebar"><div class="cms-events-sidecard"><h2>Profil</h2><dl><?php if (!empty($speaker->topic)): ?><dt>Thema</dt><dd><?= htmlspecialchars((string) $speaker->topic, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?><?php if (!empty($speaker->award)): ?><dt>Auszeichnung</dt><dd><?= htmlspecialchars((string) $speaker->award, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?><?php if (!empty($speaker->speaker_type)): ?><dt>Typ</dt><dd><?= htmlspecialchars((string) $speaker->speaker_type, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?><?php if (!empty($speaker->languages)): ?><dt>Sprachen</dt><dd><?= htmlspecialchars((string) $speaker->languages, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?><?php if (!empty($speaker->speaking_formats)): ?><dt>Formate</dt><dd><?= htmlspecialchars((string) $speaker->speaking_formats, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?><?php if (!empty($speaker->price_class)): ?><dt>Preisklasse</dt><dd><?= htmlspecialchars((string) $speaker->price_class, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?><?php if (!empty($speaker->availability)): ?><dt>Verfügbarkeit</dt><dd><?= htmlspecialchars((string) $speaker->availability, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?></dl><div class="cms-events-socials"><?php foreach (['website' => 'Website', 'linkedin_url' => 'LinkedIn', 'x_url' => 'X', 'youtube_url' => 'YouTube', 'github_url' => 'GitHub'] as $field => $label): ?><?php if (!empty($speaker->{$field})): ?><a class="cms-events-btn" href="<?= htmlspecialchars((string) $speaker->{$field}, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?><?php endforeach; ?></div></div><?php if (!empty($linkedCompany) || !empty($linkedExpert)): ?><div class="cms-events-sidecard"><h2>365CMS-Verknüpfung</h2><div class="cms-events-socials"><?php if (!empty($linkedExpert)): ?><a class="cms-events-btn" href="<?= htmlspecialchars($base . '/experts/' . (int) $linkedExpert->id, ENT_QUOTES, 'UTF-8') ?>">👤 Expert-Profil öffnen</a><?php endif; ?><?php if (!empty($linkedCompany)): ?><a class="cms-events-btn" href="<?= htmlspecialchars($base . '/companies/' . (int) $linkedCompany->id, ENT_QUOTES, 'UTF-8') ?>">🏢 <?= htmlspecialchars((string) ($linkedCompany->name ?? 'Firma'), ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?></div></div><?php endif; ?></aside>
        </article>
    </div>
</main>
