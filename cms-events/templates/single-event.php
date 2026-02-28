<?php
/**
 * Single Event Detail – Struktur nach co-single / single-expert
 *
 * Verfügbare Variablen (via extract()):
 *   $event    – object
 *   $speakers – array
 *   $settings – array
 *
 * @package CMS_Events
 */

if (!isset($event)) {
    return;
}

/* ── CSS-Variablen ─────────────────────────────────────────────── */
$primary   = htmlspecialchars($settings['color_primary']   ?? '#3b82f6');
$accent    = htmlspecialchars($settings['color_accent']    ?? '#60a5fa');
$hdr_from  = htmlspecialchars($settings['color_hdr_from']  ?? '#1d4ed8');
$hdr_to    = htmlspecialchars($settings['color_hdr_to']    ?? '#3b82f6');
$hdr_title = htmlspecialchars($settings['color_hdr_title'] ?? '#ffffff');

$e           = $event;
$id          = (int) $e->id;
$title       = htmlspecialchars($e->title ?? '');
$category    = htmlspecialchars($e->category ?? '');
$desc        = $e->description ?? '';
$city        = htmlspecialchars($e->city ?? '');
$address     = htmlspecialchars($e->address ?? '');
$zip         = htmlspecialchars($e->zip ?? '');
$country     = htmlspecialchars($e->country ?? '');
$ev_date     = $e->event_date ?? '';
$ev_time     = $e->event_time ?? '';
$end_date    = $e->end_date ?? '';
$end_time    = $e->end_time ?? '';
$is_online   = !empty($e->is_online);
$online_url  = htmlspecialchars($e->online_url ?? '');
$reg_url     = htmlspecialchars($e->registration_url ?? '');
$capacity    = (int)($e->capacity ?? 0);
$image_url   = htmlspecialchars($e->image_url ?? '');
$status      = $e->status ?? 'published';
$is_featured = !empty($e->is_featured);
$org_name    = htmlspecialchars($e->organizer_name    ?? '');
$org_email   = htmlspecialchars($e->organizer_email   ?? '');
$org_phone   = htmlspecialchars($e->organizer_phone   ?? '');
$org_website = htmlspecialchars($e->organizer_website ?? '');
$banner_url  = htmlspecialchars($e->banner_url ?? '');
$price_type  = $e->price_type ?? 'free';
$price       = (float)($e->price ?? 0);
$price_cur   = htmlspecialchars($e->price_currency ?? 'EUR');
$tags_raw    = !empty($e->tags) ? (json_decode($e->tags, true) ?? []) : [];
$show_price  = !empty($settings['show_price'])  && $settings['show_price'] !== '0';
$show_tags   = !empty($settings['show_tags'])   && $settings['show_tags'] !== '0';
$show_org    = !empty($settings['show_organizer']) && $settings['show_organizer'] !== '0';

$base_url    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$archive_url = $base_url . '/' . ($settings['archive_slug'] ?? 'events') . '/';

$ev_ts  = $ev_date  ? strtotime($ev_date)  : 0;
$end_ts = $end_date ? strtotime($end_date) : 0;

$speakers = $speakers ?? [];

// CSS-Variablen aus Einstellungen vorbereiten
$s_radius          = htmlspecialchars(is_numeric($settings['border_radius'] ?? '') ? ($settings['border_radius'] . 'px') : ($settings['border_radius'] ?? '12px'));
$s_card_border     = htmlspecialchars($settings['color_card_border']      ?? '#bfdbfe');
$s_featured_border = htmlspecialchars($settings['color_featured_border']  ?? '#f59e0b');
$s_cta             = htmlspecialchars($settings['color_cta']              ?? '#1e40af');
$s_online_badge    = htmlspecialchars($settings['color_online_badge']     ?? '#059669');
$s_cancelled_bg    = htmlspecialchars($settings['color_cancelled_bg']     ?? '#fee2e2');
$s_detail_hdr_bg   = htmlspecialchars($settings['color_detail_hdr_bg']   ?? '#f0f7ff');
$s_detail_hdr_text = htmlspecialchars($settings['color_detail_hdr_text'] ?? '#1e293b');
$s_detail_accent   = htmlspecialchars($settings['color_detail_accent']   ?? '#3b82f6');

// Abgeleiteter heller Hero-Hintergrund
if (!function_exists('ev_hex_lighten')) {
    function ev_hex_lighten(string $hex, float $amount = 0.9): string {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        [$r,$g,$b] = [hexdec(substr($hex,0,2)), hexdec(substr($hex,2,2)), hexdec(substr($hex,4,2))];
        return sprintf('#%02x%02x%02x', (int)($r+$amount*(255-$r)), (int)($g+$amount*(255-$g)), (int)($b+$amount*(255-$b)));
    }
}
$_p_raw        = $settings['color_primary'] ?? $primary;
$hero_bg_light = ev_hex_lighten($_p_raw, 0.92);
$hero_bg_mid   = ev_hex_lighten($_p_raw, 0.96);
?>
<style>
:root {
  --ev-primary:         <?= $primary ?>;
  --ev-primary-h:       <?= htmlspecialchars($settings['color_hdr_from'] ?? '#1d4ed8') ?>;
  --ev-accent:          <?= $accent ?>;
  --ev-light:           <?= $hero_bg_mid ?>;
  --ev-border:          <?= $s_card_border ?>;
  --ev-border-l:        #f1f5f9;
  --ev-card-bg:         <?= htmlspecialchars($settings['color_card_bg'] ?? '#f8fbff') ?>;
  --ev-featured-border: <?= $s_featured_border ?>;
  --ev-hdr-from:        <?= $hdr_from ?>;
  --ev-hdr-to:          <?= $hdr_to ?>;
  --ev-hdr-title:       <?= $hdr_title ?>;
  --ev-radius:          <?= $s_radius ?>;
  --ev-detail-hdr-bg:   <?= $hero_bg_light ?>;
  --ev-detail-hdr-text: <?= $s_detail_hdr_text ?>;
  --ev-detail-accent:   <?= $s_detail_accent ?>;
  --ev-cta:             <?= $s_cta ?>;
  --ev-online-badge:    <?= $s_online_badge ?>;
  --ev-online-badge-bg: #d1fae5;
  --ev-cancelled-bg:    <?= $s_cancelled_bg ?>;
  --ev-cancelled-color: #991b1b;
  --ev-text:            #1e293b;
  --ev-text-m:          #475569;
  --ev-text-l:          #94a3b8;
  --ev-bg:              #ffffff;
  --ev-shadow:          0 1px 3px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.04);
  --ev-shadow-h:        0 4px 20px rgba(0,0,0,.10);
  --ev-ease:            all .2s cubic-bezier(.4,0,.2,1);
}

</style>

<div class="ev-single-v2">

  <!-- Breadcrumb -->
  <nav class="ev-breadcrumb">
    <a href="<?= $archive_url ?>">← Events</a>
    <span class="ev-breadcrumb__sep">/</span>
    <span class="ev-breadcrumb__cur"><?= mb_strimwidth($title, 0, 60, '…') ?></span>
  </nav>

  <!-- Hero -->
  <header class="ev-hero">
    <?php if ($banner_url): ?>
      <div class="ev-hero__cover"><img src="<?= $banner_url ?>" alt="<?= $title ?>" loading="eager"></div>
    <?php endif; ?>
    <div class="ev-hero__inner">
      <?php if ($ev_ts): ?>
        <div class="ev-hero__date">
          <span class="ev-hero__date-day"><?= date('d', $ev_ts) ?></span>
          <span class="ev-hero__date-mon"><?= date('M', $ev_ts) ?></span>
          <span class="ev-hero__date-year"><?= date('Y', $ev_ts) ?></span>
        </div>
      <?php endif; ?>
      <div class="ev-hero__meta">
        <?php if ($category): ?>
          <div class="ev-hero__cat">📂 <?= $category ?></div>
        <?php endif; ?>
        <h1 class="ev-hero__title"><?= $title ?></h1>
        <div class="ev-hero__badges">
          <?php if ($is_online): ?>
            <span class="ev-hero__badge ev-hero__badge--online">🌐 Online</span>
          <?php elseif ($city): ?>
            <span class="ev-hero__badge">📍 <?= $city ?></span>
          <?php endif; ?>
          <?php if ($ev_ts && $ev_time): ?>
            <span class="ev-hero__badge ev-hero__badge--time">🕐 <?= htmlspecialchars($ev_time) ?> Uhr</span>
          <?php endif; ?>
          <?php if ($is_featured): ?>
            <span class="ev-hero__badge ev-hero__badge--featured">⭐ Featured</span>
          <?php endif; ?>
          <?php if ($status === 'cancelled'): ?>
            <span class="ev-hero__badge ev-hero__badge--cancelled">🚫 Abgesagt</span>
          <?php endif; ?>
          <?php if (!empty($speakers)): ?>
            <span class="ev-hero__badge ev-hero__badge--speakers">🎤 <?= count($speakers) ?> Speaker</span>
          <?php endif; ?>
          <?php if ($show_price && $price_type === 'free'): ?>
            <span class="ev-hero__badge ev-hero__badge--online">✓ Kostenlos</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <!-- Body: Content + Sidebar -->
  <div class="ev-single-body">

    <div class="ev-main-col">

      <?php if (!$banner_url && $image_url): ?>
        <div class="ev-section ev-section--cover">
          <img src="<?= $image_url ?>" alt="<?= $title ?>" loading="lazy">
        </div>
      <?php endif; ?>

      <?php if ($desc): ?>
        <div class="ev-section">
          <h2 class="ev-section__title">Beschreibung</h2>
          <div class="ev-wysiwyg-content">
            <?= $desc ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($speakers)): ?>
        <div class="ev-section">
          <h2 class="ev-section__title">🎤 Speaker & Referenten</h2>
          <?php foreach ((array)$speakers as $sp):
            $sp_name  = htmlspecialchars(trim(($sp->first_name ?? '') . ' ' . ($sp->last_name ?? '')));
            $sp_role  = htmlspecialchars($sp->role ?? 'Speaker');
            $sp_photo = $sp->photo_url ?? '';
            $sp_init  = strtoupper(mb_substr($sp->first_name ?? '', 0, 1) . mb_substr($sp->last_name ?? '', 0, 1));
          ?>
            <div class="ev-speaker-v2">
              <div class="ev-speaker-v2__avatar">
                <?php if ($sp_photo): ?>
                  <img src="<?= htmlspecialchars($sp_photo) ?>" alt="<?= $sp_name ?>">
                <?php else: ?>
                  <?= htmlspecialchars($sp_init ?: '🎤') ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="ev-speaker-v2__name"><?= $sp_name ?></div>
                <div class="ev-speaker-v2__role"><?= $sp_role ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div><!-- /.ev-main-col -->

    <aside class="ev-sidebar-col">

      <?php if ($reg_url && $status !== 'cancelled'): ?>
        <div class="ev-sc">
          <a href="<?= $reg_url ?>" target="_blank" rel="noopener" class="ev-cta-v2">🎟 Jetzt anmelden</a>
        </div>
      <?php elseif ($is_online && $online_url): ?>
        <div class="ev-sc">
          <a href="<?= $online_url ?>" target="_blank" rel="noopener" class="ev-cta-v2">🔗 Online-Link aufrufen</a>
        </div>
      <?php endif; ?>

      <div class="ev-sc">
        <h3 class="ev-sc__title">📅 Termin</h3>
        <div class="ev-info-v2">
          <?php if ($ev_ts): ?>
            <div class="ev-info-v2__row">
              <span class="ev-info-v2__label">Datum</span>
              <span class="ev-info-v2__value"><?= date('d.m.Y', $ev_ts) ?></span>
            </div>
            <?php if ($ev_time): ?>
              <div class="ev-info-v2__row">
                <span class="ev-info-v2__label">Uhrzeit</span>
                <span class="ev-info-v2__value"><?= htmlspecialchars($ev_time) ?> Uhr</span>
              </div>
            <?php endif; ?>
          <?php endif; ?>
          <?php if ($end_ts && $end_ts !== $ev_ts): ?>
            <div class="ev-info-v2__row">
              <span class="ev-info-v2__label">Ende</span>
              <span class="ev-info-v2__value"><?= date('d.m.Y', $end_ts) ?><?= $end_time ? ', ' . htmlspecialchars($end_time) . ' Uhr' : '' ?></span>
            </div>
          <?php endif; ?>
          <?php if ($capacity): ?>
            <div class="ev-info-v2__row">
              <span class="ev-info-v2__label">Kapazität</span>
              <span class="ev-info-v2__value"><?= $capacity ?> Plätze</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!$is_online && ($city || $address)): ?>
        <div class="ev-sc">
          <h3 class="ev-sc__title">📍 Veranstaltungsort</h3>
          <?php if ($address): ?><div class="ev-sc__address"><?= $address ?></div><?php endif; ?>
          <div class="ev-sc__address-city"><?= $zip ? $zip . ' ' : '' ?><?= $city ?><?= $country ? ', ' . $country : '' ?></div>
        </div>
      <?php endif; ?>

      <?php if ($show_price): ?>
        <div class="ev-sc">
          <h3 class="ev-sc__title">💶 Preis</h3>
          <?php if ($price_type === 'free'): ?>
            <span class="ev-price-v2 ev-price-v2--free">✅ Kostenlos</span>
          <?php elseif ($price_type === 'donation'): ?>
            <span class="ev-price-v2 ev-price-v2--donation">💝 Spendenbasis</span>
          <?php elseif ($price > 0): ?>
            <span class="ev-price-v2 ev-price-v2--paid"><?= number_format($price, 2, ',', '.') ?> <?= $price_cur ?></span>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($show_tags && !empty($tags_raw)): ?>
        <div class="ev-sc">
          <h3 class="ev-sc__title">🏷️ Tags</h3>
          <div class="ev-tags-v2">
            <?php foreach ($tags_raw as $tag): ?>
              <span class="ev-tag-v2"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($show_org && $org_name): ?>
        <div class="ev-sc">
          <h3 class="ev-sc__title">🏢 Veranstalter</h3>
          <div class="ev-org__name"><?= $org_name ?></div>
          <?php if ($org_email): ?><div class="ev-org__row">📧 <a href="mailto:<?= $org_email ?>"><?= $org_email ?></a></div><?php endif; ?>
          <?php if ($org_phone): ?><div class="ev-org__row">📞 <?= $org_phone ?></div><?php endif; ?>
          <?php if ($org_website): ?><div class="ev-org__row">🔗 <a href="<?= $org_website ?>" target="_blank" rel="noopener">Website besuchen</a></div><?php endif; ?>
        </div>
      <?php endif; ?>

    </aside>

  </div><!-- /.ev-single-body -->
</div><!-- /.ev-single-v2 -->
