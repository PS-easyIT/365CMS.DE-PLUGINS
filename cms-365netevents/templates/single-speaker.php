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
    return $fallback !== ''
        ? '<div class="cms-events-copy">' . nl2br(htmlspecialchars($fallback, ENT_QUOTES, 'UTF-8')) . '</div>'
        : '';
};

$badges = array_filter(array_map('trim', explode(',', (string) (($speaker->categories ?? '') ?: ($speaker->topic ?? '')))));
$tags = array_filter(array_map('trim', explode(',', (string) ($speaker->tags ?? ''))));

$speakerName = (string) ($speaker->display_name ?? '');
$speakerInitial = strtoupper(substr($speakerName !== '' ? $speakerName : 'S', 0, 1));
$speakerTopic = trim((string) ($speaker->topic ?? ''));
$speakerLead = $speakerTopic;
if ($speakerLead === '' && !empty($speaker->speaker_type)) {
    $speakerLead = (string) $speaker->speaker_type;
}
if ($speakerLead === '' && !empty($speaker->award)) {
    $speakerLead = (string) $speaker->award;
}

$hasHeaderImage = !empty($speaker->avatar_url);
$headerBadges = array_slice($badges, 0, 10);

$metaInfo = [];
if (!empty($speaker->speaker_type)) {
    $metaInfo[] = (string) $speaker->speaker_type;
}
if (!empty($speaker->languages)) {
    $metaInfo[] = (string) $speaker->languages;
}
if (!empty($speaker->speaking_formats)) {
    $metaInfo[] = (string) $speaker->speaking_formats;
}
if (!empty($speaker->availability)) {
    $metaInfo[] = (string) $speaker->availability;
}

$detailRows = [];
if ($speakerTopic !== '') {
    $detailRows[] = ['label' => 'Thema', 'value' => $speakerTopic];
}
if (!empty($speaker->award)) {
    $detailRows[] = ['label' => 'Auszeichnung', 'value' => (string) $speaker->award];
}
if (!empty($speaker->speaker_type)) {
    $detailRows[] = ['label' => 'Typ', 'value' => (string) $speaker->speaker_type];
}
if (!empty($speaker->languages)) {
    $detailRows[] = ['label' => 'Sprachen', 'value' => (string) $speaker->languages];
}
if (!empty($speaker->speaking_formats)) {
    $detailRows[] = ['label' => 'Formate', 'value' => (string) $speaker->speaking_formats];
}
if (!empty($speaker->price_class)) {
    $detailRows[] = ['label' => 'Preisklasse', 'value' => (string) $speaker->price_class];
}
if (!empty($speaker->availability)) {
    $detailRows[] = ['label' => 'Verfügbarkeit', 'value' => (string) $speaker->availability];
}

$sidebarPrimaryLink = trim((string) ($speaker->website ?? ''));
$sidebarPrimaryLabel = 'Zur Website';

$sidebarContactLink = '';
$sidebarContactIcon = '✉';
$sidebarContactEmail = trim((string) (($speaker->contact_email ?? '') ?: ($speaker->email ?? '')));
if ($sidebarContactEmail !== '' && filter_var($sidebarContactEmail, FILTER_VALIDATE_EMAIL)) {
    $sidebarContactLink = 'mailto:' . $sidebarContactEmail;
} else {
    $sidebarContactPhoneRaw = trim((string) (($speaker->contact_phone ?? '') ?: ($speaker->phone ?? '')));
    if ($sidebarContactPhoneRaw !== '') {
        $sidebarContactPhone = preg_replace('/[^\d+]/', '', $sidebarContactPhoneRaw);
        if (is_string($sidebarContactPhone) && $sidebarContactPhone !== '') {
            $sidebarContactLink = 'tel:' . $sidebarContactPhone;
            $sidebarContactIcon = '☎';
        }
    }
}

$sidebarSocialLinks = [];
$sidebarSocialMap = [
    ['field' => 'linkedin_url', 'label' => 'LinkedIn', 'icon' => 'in'],
    ['field' => 'x_url', 'label' => 'X', 'icon' => 'X'],
    ['field' => 'youtube_url', 'label' => 'YouTube', 'icon' => '▶'],
    ['field' => 'github_url', 'label' => 'GitHub', 'icon' => '⌘'],
    ['field' => 'facebook_url', 'label' => 'Facebook', 'icon' => 'f'],
    ['field' => 'instagram_url', 'label' => 'Instagram', 'icon' => '◎'],
];

foreach ($sidebarSocialMap as $sidebarSocial) {
    $field = (string) ($sidebarSocial['field'] ?? '');
    if ($field === '' || empty($speaker->{$field})) {
        continue;
    }

    $sidebarSocialLinks[] = [
        'url' => (string) $speaker->{$field},
        'label' => (string) ($sidebarSocial['label'] ?? $field),
        'icon' => (string) ($sidebarSocial['icon'] ?? '↗'),
    ];
}

$hasSidebarPrimaryActions = $sidebarContactLink !== '' || $sidebarPrimaryLink !== '';
$hasSidebarLinks = $hasSidebarPrimaryActions || $sidebarSocialLinks !== [];
?>
<main class="cms-events-public cms-events-detail cms-speaker-detail">
    <div class="cms-events-container">
        <nav class="cms-events-breadcrumb">
            <a href="<?= htmlspecialchars($base . '/speakers', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($settings['speaker_archive_title'] ?? 'Speaker'), ENT_QUOTES, 'UTF-8') ?></a>
            <span>/</span>
            <span><?= htmlspecialchars($speakerName, ENT_QUOTES, 'UTF-8') ?></span>
        </nav>

        <article class="cms-events-detail-layout">
            <header class="cms-events-detail-header cms-speaker-detail-header<?= $hasHeaderImage ? ' cms-events-detail-header--has-image' : ' cms-events-detail-header--no-image' ?>">
                <div class="cms-events-detail-header__content">
                    <div class="cms-events-detail-header__intro">
                        <div class="cms-speaker-detail-badge" aria-label="Speakerprofil">
                            <?php if ($hasHeaderImage): ?>
                                <img src="<?= htmlspecialchars((string) $speaker->avatar_url, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($speaker->avatar_alt ?? $speakerName), ENT_QUOTES, 'UTF-8') ?>" loading="eager">
                            <?php else: ?>
                                <span class="cms-speaker-detail-badge__initial"><?= htmlspecialchars($speakerInitial, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="cms-events-detail-header__text">
                            <h1><?= htmlspecialchars($speakerName, ENT_QUOTES, 'UTF-8') ?></h1>
                            <?php if ($metaInfo !== []): ?>
                                <div class="cms-events-detail-header__meta">
                                    <?php foreach (array_slice($metaInfo, 0, 5) as $meta): ?>
                                        <span><?= htmlspecialchars((string) $meta, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($speakerLead !== ''): ?>
                        <p class="cms-events-detail-header__lead"><?= htmlspecialchars($speakerLead, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($hasHeaderImage): ?>
                    <figure class="cms-events-detail-header__media cms-speaker-detail-header__media">
                        <img src="<?= htmlspecialchars((string) $speaker->avatar_url, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($speaker->avatar_alt ?? $speakerName), ENT_QUOTES, 'UTF-8') ?>" loading="eager">
                    </figure>
                <?php endif; ?>
            </header>

            <div class="cms-events-detail-layout__body">
                <section class="cms-events-detail-main">
                    <?php if ($tags !== []): ?>
                        <div class="cms-events-mini-tags cms-events-detail-tags">
                            <?php foreach (array_slice($tags, 0, 12) as $tag): ?>
                                <span><?= htmlspecialchars((string) $tag, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?= $renderEditor($speaker->bio_json ?? '', $speaker->bio ?? '') ?>
                </section>

                <aside class="cms-events-sidebar">
                    <div class="cms-events-sidebar-stack">
                        <?php if ($headerBadges !== []): ?>
                            <div class="cms-events-sidecard cms-events-sidecard--categories">
                                <div class="cms-events-sidecard__badges">
                                    <?php foreach ($headerBadges as $badge): ?>
                                        <span><?= htmlspecialchars((string) $badge, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($detailRows !== []): ?>
                            <div class="cms-events-sidecard cms-events-sidecard--details<?= $hasSidebarLinks ? ' cms-events-sidecard--details-has-links' : '' ?>">
                                <dl class="cms-events-sidecard__meta">
                                    <?php foreach ($detailRows as $detailRow): ?>
                                        <div class="cms-events-sidecard__meta-row">
                                            <dt><?= htmlspecialchars((string) ($detailRow['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dt>
                                            <dd><?= htmlspecialchars((string) ($detailRow['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
                                        </div>
                                    <?php endforeach; ?>
                                </dl>
                            </div>
                        <?php endif; ?>

                        <?php if ($hasSidebarLinks): ?>
                            <div class="cms-events-sidecard cms-events-sidecard--links">
                                <?php if ($hasSidebarPrimaryActions): ?>
                                    <div class="cms-events-side-actions cms-events-side-actions--primary-row">
                                        <?php if ($sidebarContactLink !== ''): ?>
                                            <a class="cms-events-side-action cms-events-side-action--primary" href="<?= htmlspecialchars($sidebarContactLink, ENT_QUOTES, 'UTF-8') ?>" aria-label="Kontakt" title="Kontakt">
                                                <span class="cms-events-side-action__icon"><?= htmlspecialchars($sidebarContactIcon, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="cms-events-side-action__label">Kontakt</span>
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($sidebarPrimaryLink !== ''): ?>
                                            <a class="cms-events-side-action cms-events-side-action--primary" href="<?= htmlspecialchars($sidebarPrimaryLink, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= htmlspecialchars($sidebarPrimaryLabel, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($sidebarPrimaryLabel, ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="cms-events-side-action__icon">🌐</span>
                                                <span class="cms-events-side-action__label"><?= htmlspecialchars($sidebarPrimaryLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($sidebarSocialLinks !== []): ?>
                                    <div class="cms-events-side-actions cms-events-side-actions--social">
                                        <?php foreach ($sidebarSocialLinks as $sidebarSocialLink): ?>
                                            <a class="cms-events-side-action cms-events-side-action--icon-only" href="<?= htmlspecialchars((string) $sidebarSocialLink['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= htmlspecialchars((string) $sidebarSocialLink['label'], ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars((string) $sidebarSocialLink['label'], ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="cms-events-side-action__icon"><?= htmlspecialchars((string) $sidebarSocialLink['icon'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($linkedCompany) || !empty($linkedExpert)): ?>
                        <div class="cms-events-sidecard">
                            <h2>365CMS-Verknüpfung</h2>
                            <div class="cms-events-socials">
                                <?php if (!empty($linkedExpert)): ?>
                                    <a class="cms-events-btn" href="<?= htmlspecialchars($base . '/experts/' . (int) $linkedExpert->id, ENT_QUOTES, 'UTF-8') ?>">👤 Expert-Profil öffnen</a>
                                <?php endif; ?>
                                <?php if (!empty($linkedCompany)): ?>
                                    <a class="cms-events-btn" href="<?= htmlspecialchars($base . '/companies/' . (int) $linkedCompany->id, ENT_QUOTES, 'UTF-8') ?>">🏢 <?= htmlspecialchars((string) ($linkedCompany->name ?? 'Firma'), ENT_QUOTES, 'UTF-8') ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </aside>
            </div>

            <section class="cms-events-detail-main cms-events-detail-main--fullwidth">
                <section class="cms-events-section cms-events-section--fullwidth">
                    <h2><?= htmlspecialchars((string) ($settings['speaker_events_heading'] ?? 'Events'), ENT_QUOTES, 'UTF-8') ?></h2>
                    <?php if ($relatedEvents === []): ?>
                        <p><?= htmlspecialchars((string) ($settings['speaker_no_events_text'] ?? 'Noch keine öffentlichen Events verknüpft.'), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php else: ?>
                        <div class="cms-speaker-list">
                            <?php foreach ($relatedEvents as $event): ?>
                                <a class="cms-speaker-row" href="<?= htmlspecialchars($base . '/events/' . rawurlencode((string) $event->slug), ENT_QUOTES, 'UTF-8') ?>">
                                    <span>📅</span>
                                    <span>
                                        <strong><?= htmlspecialchars((string) $event->title, ENT_QUOTES, 'UTF-8') ?></strong>
                                        <small>
                                            <?= htmlspecialchars((string) ($event->date_label ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            <?= !empty($event->location) ? ' · ' . htmlspecialchars((string) $event->location, ENT_QUOTES, 'UTF-8') : '' ?>
                                        </small>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </section>
        </article>
    </div>
</main>
