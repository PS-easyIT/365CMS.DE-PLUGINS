<?php declare(strict_types=1);

/**
 * Single Event Detail – Hero + Bridge + Speaker-Section
 * Layout analog zur Company-Detailseite.
 *
 * Verfügbare Variablen (via extract()):
 *   $event    – object
 *   $speakers – array
 *   $settings – array
 *
 * @package CMS_Events
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!isset($event)) {
    return;
}

$settings = is_array($settings ?? null) ? $settings : [];

$e  = $event;
$id = (int) $e->id;

$title       = htmlspecialchars($e->title ?? '', ENT_QUOTES, 'UTF-8');
$category    = htmlspecialchars($e->category ?? '', ENT_QUOTES, 'UTF-8');
$desc        = $e->description ?? '';
$city        = htmlspecialchars($e->city ?? '', ENT_QUOTES, 'UTF-8');
$address     = htmlspecialchars($e->address ?? '', ENT_QUOTES, 'UTF-8');
$zip         = htmlspecialchars($e->zip ?? '', ENT_QUOTES, 'UTF-8');
$country     = htmlspecialchars($e->country ?? '', ENT_QUOTES, 'UTF-8');
$ev_date     = $e->event_date ?? '';
$ev_time     = $e->event_time ?? '';
$end_date    = $e->end_date ?? '';
$end_time    = $e->end_time ?? '';
$is_online   = !empty($e->is_online);
$online_url  = function_exists('cms_events_public_url') ? cms_events_public_url($e->online_url ?? null) : '';
$reg_url     = function_exists('cms_events_public_url') ? cms_events_public_url($e->registration_url ?? null) : '';
$capacity    = (int)($e->capacity ?? 0);
$status      = $e->status ?? 'published';
$banner_url  = function_exists('cms_events_public_url') ? cms_events_public_url($e->banner_url ?? null) : '';
$image_url   = function_exists('cms_events_public_url') ? cms_events_public_url($e->image_url ?? null) : '';
$price_type  = $e->price_type ?? 'free';
$price       = (float)($e->price ?? 0);
$price_cur   = htmlspecialchars($e->price_currency ?? 'EUR', ENT_QUOTES, 'UTF-8');
$tags_raw    = !empty($e->tags) ? (json_decode($e->tags, true) ?? []) : [];
$org_name    = htmlspecialchars($e->organizer_name ?? '', ENT_QUOTES, 'UTF-8');
$org_email   = filter_var(trim((string) ($e->organizer_email ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
$org_phone   = preg_replace('/[^0-9+]/', '', trim((string) ($e->organizer_phone ?? ''))) ?: '';
$org_website = function_exists('cms_events_public_url') ? cms_events_public_url($e->organizer_website ?? null) : '';

$show_price = !empty($settings['show_price']) && $settings['show_price'] !== '0';
$show_tags  = !empty($settings['show_tags']) && $settings['show_tags'] !== '0';

$base_url    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$archive_slug = preg_replace('/[^a-z0-9-]+/i', '-', (string) ($settings['archive_slug'] ?? 'events')) ?: 'events';
$archive_url = $base_url . '/' . trim($archive_slug, '-') . '/';

$ev_ts  = $ev_date ? strtotime($ev_date) : 0;
$end_ts = $end_date ? strtotime($end_date) : 0;

$speakers = $speakers ?? [];
?>
<main class="phinit-plugin ev-single-v2">

  <nav class="ev-breadcrumb" aria-label="Breadcrumb">
    <a href="<?= htmlspecialchars($archive_url, ENT_QUOTES, 'UTF-8') ?>">← Events</a>
    <span class="ev-breadcrumb__sep" aria-hidden="true">/</span>
    <span class="ev-breadcrumb__cur"><?= mb_strimwidth($title, 0, 60, '…') ?></span>
  </nav>

  <header class="ev-hero-v2 phinit-card phinit-card--accent">
    <?php if ($banner_url): ?>
      <div class="ev-hero-v2__bg" aria-hidden="true">
        <img src="<?= htmlspecialchars($banner_url, ENT_QUOTES, 'UTF-8') ?>" alt="" width="1200" height="675" loading="eager" decoding="async">
      </div>
    <?php endif; ?>
    <?php if ($image_url): ?>
      <div class="ev-hero-v2__thumb" aria-hidden="true">
        <img src="<?= htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $title ?>" width="160" height="160" loading="eager" decoding="async">
      </div>
    <?php endif; ?>
    <div class="ev-hero-v2__inner">
      <?php if ($ev_ts): ?>
        <time class="ev-hero-v2__datebadge" datetime="<?= htmlspecialchars((string) $ev_date, ENT_QUOTES, 'UTF-8') ?>">
          <span class="ev-hero-v2__date-day"><?= date('d', $ev_ts) ?></span>
          <span class="ev-hero-v2__date-mon"><?= date('M', $ev_ts) ?></span>
          <span class="ev-hero-v2__date-year"><?= date('Y', $ev_ts) ?></span>
        </time>
      <?php endif; ?>
      <div class="ev-hero-v2__meta">
        <h1 class="ev-hero-v2__title"><?= $title ?></h1>
      </div>
    </div>
  </header>

  <div class="ev-bridge-v2">
    <section class="ev-bridge-v2__about phinit-card">
      <h2 class="ev-bridge-v2__title">Über diesen Event</h2>
      <?php $eventDescription = trim((string) $desc); ?>
      <?php if ($eventDescription !== ''): ?>
        <div class="ev-bridge-v2__text ev-wysiwyg-content"><?= nl2br(htmlspecialchars($eventDescription, ENT_QUOTES, 'UTF-8')) ?></div>
      <?php else: ?>
        <p class="ev-bridge-v2__text ev-empty-text">Noch keine Beschreibung hinterlegt.</p>
      <?php endif; ?>
    </section>

    <section class="ev-bridge-v2__contact phinit-card">
      <h2 class="ev-bridge-v2__title">Details &amp; Anmeldung</h2>
      <div class="ev-bridge-v2__contact-body">
        <?php if ($reg_url && $status !== 'cancelled'): ?>
          <a href="<?= htmlspecialchars($reg_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="phinit-btn phinit-btn--primary ev-bridge-v2__book-btn">Jetzt anmelden</a>
        <?php elseif ($is_online && $online_url): ?>
          <a href="<?= htmlspecialchars($online_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="phinit-btn phinit-btn--primary ev-bridge-v2__book-btn">Online-Link aufrufen</a>
        <?php endif; ?>

        <?php if ($org_email || $org_phone || $org_website): ?>
        <div class="ev-bridge-v2__icon-row">
          <?php if ($org_website): ?>
            <a href="<?= htmlspecialchars($org_website, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="ev-bridge-v2__icon-btn">Web</a>
          <?php endif; ?>
          <?php if ($org_email): ?>
            <a href="mailto:<?= htmlspecialchars($org_email, ENT_QUOTES, 'UTF-8') ?>" class="ev-bridge-v2__icon-btn">Mail</a>
          <?php endif; ?>
          <?php if ($org_phone): ?>
            <a href="tel:<?= htmlspecialchars($org_phone, ENT_QUOTES, 'UTF-8') ?>" class="ev-bridge-v2__icon-btn">Anruf</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php
        $facts = [];
        if ($ev_ts) {
            $datum = date('d.m.Y', $ev_ts);
            if ($ev_time) {
                $datum .= ' · ' . htmlspecialchars((string) $ev_time, ENT_QUOTES, 'UTF-8') . ' Uhr';
            }
            $facts[] = ['Datum', $datum];
        }
        if ($end_ts && $end_ts !== $ev_ts) {
            $end_str = date('d.m.Y', $end_ts);
            if ($end_time) {
                $end_str .= ' · ' . htmlspecialchars((string) $end_time, ENT_QUOTES, 'UTF-8') . ' Uhr';
            }
            $facts[] = ['Ende', $end_str];
        }
        if ($category) {
            $facts[] = ['Kategorie', $category];
        }
        if ($is_online) {
            $facts[] = ['Format', 'Online-Event'];
        } elseif ($city || $address) {
            $loc = trim(($address ? $address . ', ' : '') . ($zip ? $zip . ' ' : '') . $city);
            if ($country && $country !== 'Deutschland') {
                $loc .= ', ' . $country;
            }
            $facts[] = ['Ort', htmlspecialchars($loc, ENT_QUOTES, 'UTF-8')];
        }
        if ($capacity) {
            $facts[] = ['Kapazität', $capacity . ' Plätze'];
        }
        if ($org_name) {
            $facts[] = ['Veranstalter', $org_name];
        }
        if ($show_price) {
            if ($price_type === 'free') {
                $facts[] = ['Preis', 'Kostenlos'];
            } elseif ($price_type === 'donation') {
                $facts[] = ['Preis', 'Spendenbasis'];
            } elseif ($price > 0) {
                $facts[] = ['Preis', number_format($price, 2, ',', '.') . ' ' . $price_cur];
            }
        }
        ?>
        <?php if (!empty($facts)): ?>
        <div class="ev-bridge-v2__facts">
          <?php foreach ($facts as [$lbl, $val]): ?>
            <div class="ev-bridge-v2__fact">
              <span class="ev-bridge-v2__fact-lbl"><?= $lbl ?></span>
              <span class="ev-bridge-v2__fact-val"><?= $val ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($show_tags && !empty($tags_raw)): ?>
          <div class="ev-bridge-v2__tags" aria-label="Tags">
            <?php foreach ($tags_raw as $tag): ?>
              <span class="ev-bridge-v2__tag"><?= htmlspecialchars((string) $tag, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </section>
  </div>

  <?php if (!empty($speakers)): ?>
  <section class="ev-people-v2">
    <div class="ev-sec-v2">
      <h2 class="ev-sec-v2__title">
        Speaker &amp; Referenten
        <span class="ev-section-count"><?= count($speakers) ?></span>
      </h2>
      <div class="ev-speaker-grid-v2">
        <?php foreach ((array)$speakers as $sp):
          $spFirst = $sp->first_name ?? '';
          $spLast  = $sp->last_name ?? '';
          $spType  = in_array(($sp->speaker_type ?? ''), ['speaker', 'expert'], true) ? (string) $sp->speaker_type : 'speaker';
          $spName  = htmlspecialchars(trim($spFirst . ' ' . $spLast) ?: 'Speaker', ENT_QUOTES, 'UTF-8');
          $spCity  = !empty($sp->location_city) ? htmlspecialchars($sp->location_city, ENT_QUOTES, 'UTF-8') : null;
          $spPhotoUrl = function_exists('cms_events_public_url') ? cms_events_public_url($sp->photo_url ?? null) : '';
          $spId    = (int)($sp->id ?? 0);
          $spProfileUrl = $spId > 0 ? $base_url . ($spType === 'expert' ? '/experts/' : '/speakers/') . $spId : '';
          $letter = mb_strtoupper(mb_substr($spFirst ?: $spLast, 0, 1) ?: 'S');
        ?>
          <article class="ev-spk-row phinit-card">
            <div class="ev-spk-row__top">
              <?php if ($spPhotoUrl !== ''): ?>
                <div class="ev-spk-row__av"><img src="<?= htmlspecialchars($spPhotoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $spName ?>" width="56" height="56" loading="lazy" decoding="async"></div>
              <?php else: ?>
                <div class="ev-spk-row__av ev-spk-row__av--placeholder"><?= htmlspecialchars($letter, ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
              <div class="ev-spk-row__info">
                <div class="ev-spk-row__name">
                  <?php if ($spProfileUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($spProfileUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $spName ?></a>
                  <?php else: ?>
                    <?= $spName ?>
                  <?php endif; ?>
                </div>
                <?php if ($spCity): ?><div class="ev-spk-row__sub"><?= $spCity ?></div><?php endif; ?>
              </div>
            </div>
            <?php if ($spProfileUrl !== ''): ?>
              <a href="<?= htmlspecialchars($spProfileUrl, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary ev-spk-row__btn">Profil →</a>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!(int)($e->user_id ?? 0)): ?>
  <aside class="ev-claim-banner phinit-note phinit-note--info">
    <div class="ev-claim-banner__text">
      <strong>Dieser Event wurde von der Redaktion eingetragen.</strong>
      Sind Sie der Veranstalter? Registrieren Sie sich kostenlos und verwalten Sie Ihren Event selbst.
    </div>
    <a href="<?= htmlspecialchars(rtrim(SITE_URL, '/') . '/register', ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--primary ev-claim-banner__btn">Jetzt registrieren &amp; Event übernehmen →</a>
  </aside>
  <?php endif; ?>

</main>
