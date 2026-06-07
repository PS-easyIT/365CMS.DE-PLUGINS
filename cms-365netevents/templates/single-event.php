<?php
/** @var object|null $event */
/** @var array<int, object> $speakers */
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
$badges = array_filter(array_map('trim', explode(',', (string) (($event->categories ?? '') ?: ($event->category ?? '')))));
$tags = array_filter(array_map('trim', explode(',', (string) ($event->tags ?? ''))));
?>
<main class="cms-events-public cms-events-detail">
    <div class="cms-events-container">
        <nav class="cms-events-breadcrumb"><a href="<?= htmlspecialchars($base . '/events', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($settings['detail_back_events_label'] ?? 'Events'), ENT_QUOTES, 'UTF-8') ?></a><span>/</span><span><?= htmlspecialchars((string) ($event->title ?? ''), ENT_QUOTES, 'UTF-8') ?></span></nav>
        <?php if (!empty($event->image_url)): ?><figure class="cms-events-hero-image"><img src="<?= htmlspecialchars((string) $event->image_url, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($event->image_alt ?? $event->title ?? ''), ENT_QUOTES, 'UTF-8') ?>" loading="eager"></figure><?php endif; ?>
        <article class="cms-events-detail-layout">
            <section class="cms-events-detail-main">
                <span class="cms-events-kicker"><?= htmlspecialchars((string) ($event->date_label ?? ''), ENT_QUOTES, 'UTF-8') ?><?= !empty($event->end_date_label) ? ' – ' . htmlspecialchars((string) $event->end_date_label, ENT_QUOTES, 'UTF-8') : '' ?></span>
                <h1><?= htmlspecialchars((string) ($event->title ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
                <?php if (!empty($event->excerpt)): ?><p class="cms-events-lead"><?= htmlspecialchars((string) $event->excerpt, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                <?php if ($badges !== [] || $tags !== []): ?><div class="cms-events-badges"><?php foreach (array_slice(array_merge($badges, $tags), 0, 12) as $badge): ?><span><?= htmlspecialchars($badge, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></div><?php endif; ?>
                <?= $renderEditor($event->description_json ?? '', $event->description ?? '') ?>
                <?php if (!empty($event->category)): ?><p class="cms-events-lead"><?= htmlspecialchars((string) $event->category, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>

                <section class="cms-events-section">
                    <h2><?= htmlspecialchars((string) ($settings['detail_speakers_heading'] ?? 'Speaker & Themen'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <?php if ($speakers === []): ?>
                        <p><?= htmlspecialchars((string) ($settings['detail_no_speakers_text'] ?? 'Für dieses Event sind noch keine Speaker verknüpft.'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <div class="cms-speaker-list">
                            <?php foreach ($speakers as $speaker): ?>
                                <a class="cms-speaker-row" href="<?= htmlspecialchars($base . '/speakers/' . rawurlencode((string) $speaker->slug), ENT_QUOTES, 'UTF-8') ?>">
                                    <span class="cms-speaker-avatar"><?= htmlspecialchars(strtoupper(substr((string) ($speaker->display_name ?? 'S'), 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                                    <span><strong><?= htmlspecialchars((string) $speaker->display_name, ENT_QUOTES, 'UTF-8') ?></strong><?php if (!empty($speaker->relation_topic) || !empty($speaker->topic)): ?><small><?= htmlspecialchars((string) ($speaker->relation_topic ?: $speaker->topic), ENT_QUOTES, 'UTF-8') ?></small><?php endif; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </section>
            <aside class="cms-events-sidebar">
                <div class="cms-events-sidecard"><h2>Details</h2><dl>
                    <?php if (!empty($event->location)): ?><dt>Ort</dt><dd><?= htmlspecialchars((string) $event->location, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->venue_name)): ?><dt>Venue</dt><dd><?= htmlspecialchars((string) $event->venue_name, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->city) || !empty($event->country)): ?><dt>Region</dt><dd><?= htmlspecialchars(trim((string) ($event->postal_code ?? '') . ' ' . (string) ($event->city ?? '') . ', ' . (string) ($event->country ?? ''), ' ,'), ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->attendance_mode)): ?><dt>Durchführung</dt><dd><?= htmlspecialchars((string) $event->attendance_mode, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->organizer)): ?><dt>Veranstalter</dt><dd><?= htmlspecialchars((string) $event->organizer, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->event_format) || !empty($event->event_type)): ?><dt>Format</dt><dd><?= htmlspecialchars((string) ($event->event_format ?: $event->event_type), ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->difficulty_level)): ?><dt>Level</dt><dd><?= htmlspecialchars((string) $event->difficulty_level, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->language)): ?><dt>Sprache</dt><dd><?= htmlspecialchars((string) $event->language, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->target_audience)): ?><dt>Zielgruppe</dt><dd><?= htmlspecialchars((string) $event->target_audience, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->price_class)): ?><dt>Preisklasse</dt><dd><?= htmlspecialchars((string) $event->price_class, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->price)): ?><dt>Preis</dt><dd><?= htmlspecialchars((string) $event->price, ENT_QUOTES, 'UTF-8') ?></dd><?php endif; ?>
                    <?php if (!empty($event->capacity)): ?><dt>Kapazität</dt><dd><?= (int) $event->capacity ?> Personen</dd><?php endif; ?>
                </dl><?php if (!empty($event->registration_url)): ?><a class="cms-events-btn" href="<?= htmlspecialchars((string) $event->registration_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars((string) ($settings['detail_register_label'] ?? 'Registrieren'), ENT_QUOTES, 'UTF-8') ?></a><?php elseif (!empty($event->website)): ?><a class="cms-events-btn" href="<?= htmlspecialchars((string) $event->website, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars((string) ($settings['detail_website_label'] ?? 'Website öffnen'), ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?></div>
                <?php if (!empty($linkedCompany) || !empty($linkedExpert)): ?><div class="cms-events-sidecard"><h2>365CMS-Verknüpfung</h2><div class="cms-events-socials">
                    <?php if (!empty($linkedCompany)): ?><a class="cms-events-btn" href="<?= htmlspecialchars($base . '/companies/' . (int) $linkedCompany->id, ENT_QUOTES, 'UTF-8') ?>">🏢 <?= htmlspecialchars((string) ($linkedCompany->name ?? 'Firma'), ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
                    <?php if (!empty($linkedExpert)): ?><a class="cms-events-btn" href="<?= htmlspecialchars($base . '/experts/' . (int) $linkedExpert->id, ENT_QUOTES, 'UTF-8') ?>">👤 <?= htmlspecialchars(trim((string) ($linkedExpert->first_name ?? '') . ' ' . (string) ($linkedExpert->last_name ?? '')) ?: 'Expert', ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
                </div></div><?php endif; ?>
            </aside>
        </article>
    </div>
</main>
