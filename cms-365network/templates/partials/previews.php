<?php
/**
 * Shared preview cards for CMS 365NETWORK landing.
 *
 * @package CMS_365NETWORK
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$renderEmpty = static function (string $label) use ($esc): void {
    echo '<div class="n365-preview-empty"><i class="ti ti-info-circle" aria-hidden="true"></i><span>' . $esc($label) . '</span></div>';
};
?>
<section class="n365-preview-stack" aria-label="Dynamische Netzwerk-Inhalte">
    <?php if ($enabled('show_events_preview') && (int) ($settings['sidebar_events_count'] ?? 0) > 0): ?>
    <article class="n365-preview-panel">
        <header class="n365-preview-panel__head">
            <span class="n365-preview-panel__icon" aria-hidden="true"><i class="ti ti-calendar-event"></i></span>
            <div><p class="n365-kicker">Kommende Events</p><h3>Demnächst</h3></div>
        </header>
        <?php if ($events === []): ?>
            <?php $renderEmpty('Noch keine kommenden Events gefunden.'); ?>
        <?php else: ?>
            <ul class="n365-event-list">
                <?php foreach ($events as $event): ?>
                <?php
                $event = is_array($event) ? $event : [];
                $eventId = (int) ($event['id'] ?? 0);
                $eventTitle = trim((string) ($event['title'] ?? 'Event')) ?: 'Event';
                $eventUrl = $eventId > 0 ? '/events/' . $eventId : '/events';
                $eventPlace = trim((string) ($event['city'] ?? ($event['location'] ?? '')));
                ?>
                <li>
                    <a href="<?php echo $esc($eventUrl); ?>">
                        <strong><?php echo $esc($eventTitle); ?></strong>
                        <span><?php echo $esc($eventDate($event)); ?><?php echo $eventPlace !== '' ? ' · ' . $esc($eventPlace) : ''; ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </article>
    <?php endif; ?>

    <?php if ($enabled('show_speakers_preview') && (int) ($settings['random_speakers_count'] ?? 0) > 0): ?>
    <article class="n365-preview-panel">
        <header class="n365-preview-panel__head">
            <span class="n365-preview-panel__icon" aria-hidden="true"><i class="ti ti-microphone-2"></i></span>
            <div><p class="n365-kicker">Speaker</p><h3>Zufällig ausgewählt</h3></div>
        </header>
        <?php if ($speakers === []): ?>
            <?php $renderEmpty('Keine Speaker-Vorschau verfügbar.'); ?>
        <?php else: ?>
            <div class="n365-mini-grid">
                <?php foreach ($speakers as $speaker): ?>
                <?php
                $speaker = is_array($speaker) ? $speaker : [];
                $speakerId = (int) ($speaker['id'] ?? 0);
                $speakerName = $previewName($speaker, 'speaker');
                $speakerUrl = $speakerId > 0 ? '/speakers/' . $speakerId : '/speakers';
                $speakerImage = $safeImage($speaker['photo_url'] ?? '');
                $speakerMeta = trim((string) ($speaker['position'] ?? ($speaker['company'] ?? '')));
                ?>
                <a class="n365-person-card" href="<?php echo $esc($speakerUrl); ?>">
                    <?php if ($speakerImage !== ''): ?>
                    <img src="<?php echo $esc($speakerImage); ?>" alt="" loading="lazy">
                    <?php else: ?>
                    <span class="n365-avatar" aria-hidden="true"><?php echo $esc(substr($speakerName, 0, 1)); ?></span>
                    <?php endif; ?>
                    <span><strong><?php echo $esc($speakerName); ?></strong><?php if ($speakerMeta !== ''): ?><small><?php echo $esc($speakerMeta); ?></small><?php endif; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php endif; ?>

    <?php if ($enabled('show_companies_preview') && (int) ($settings['random_companies_count'] ?? 0) > 0): ?>
    <article class="n365-preview-panel">
        <header class="n365-preview-panel__head">
            <span class="n365-preview-panel__icon" aria-hidden="true"><i class="ti ti-building-community"></i></span>
            <div><p class="n365-kicker">Firmen</p><h3>Aus dem Netzwerk</h3></div>
        </header>
        <?php if ($companies === []): ?>
            <?php $renderEmpty('Keine Firmen-Vorschau verfügbar.'); ?>
        <?php else: ?>
            <div class="n365-mini-grid">
                <?php foreach ($companies as $company): ?>
                <?php
                $company = is_array($company) ? $company : [];
                $companyId = (int) ($company['id'] ?? 0);
                $companyName = $previewName($company, 'company');
                $companyUrl = $companyId > 0 ? '/companies/' . $companyId : '/companies';
                $companyImage = $safeImage($company['logo_url'] ?? '');
                $companyMeta = trim((string) ($company['industry'] ?? ($company['location_city'] ?? '')));
                ?>
                <a class="n365-person-card" href="<?php echo $esc($companyUrl); ?>">
                    <?php if ($companyImage !== ''): ?>
                    <img src="<?php echo $esc($companyImage); ?>" alt="" loading="lazy">
                    <?php else: ?>
                    <span class="n365-avatar" aria-hidden="true"><?php echo $esc(substr($companyName, 0, 1)); ?></span>
                    <?php endif; ?>
                    <span><strong><?php echo $esc($companyName); ?></strong><?php if ($companyMeta !== ''): ?><small><?php echo $esc($companyMeta); ?></small><?php endif; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php endif; ?>

    <?php if ($enabled('show_experts_preview') && (int) ($settings['random_experts_count'] ?? 0) > 0): ?>
    <article class="n365-preview-panel">
        <header class="n365-preview-panel__head">
            <span class="n365-preview-panel__icon" aria-hidden="true"><i class="ti ti-user-star"></i></span>
            <div><p class="n365-kicker">Experten</p><h3>Fachprofile</h3></div>
        </header>
        <?php if ($experts === []): ?>
            <?php $renderEmpty('Keine Experten-Vorschau verfügbar.'); ?>
        <?php else: ?>
            <div class="n365-mini-grid">
                <?php foreach ($experts as $expert): ?>
                <?php
                $expert = is_array($expert) ? $expert : [];
                $expertId = (int) ($expert['id'] ?? 0);
                $expertName = $previewName($expert, 'expert');
                $expertUrl = $expertId > 0 ? '/experts/' . $expertId : '/experts';
                $expertImage = $safeImage($expert['photo_url'] ?? '');
                $expertMeta = trim((string) ($expert['position'] ?? ($expert['company'] ?? '')));
                ?>
                <a class="n365-person-card" href="<?php echo $esc($expertUrl); ?>">
                    <?php if ($expertImage !== ''): ?>
                    <img src="<?php echo $esc($expertImage); ?>" alt="" loading="lazy">
                    <?php else: ?>
                    <span class="n365-avatar" aria-hidden="true"><?php echo $esc(substr($expertName, 0, 1)); ?></span>
                    <?php endif; ?>
                    <span><strong><?php echo $esc($expertName); ?></strong><?php if ($expertMeta !== ''): ?><small><?php echo $esc($expertMeta); ?></small><?php endif; ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
    <?php endif; ?>
</section>
