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

/* ── Single Event v2 – Modernes Layout ─────────────────────── */
.ev-single-v2 {
  max-width: var(--max, 1140px); margin: 0 auto;
  border-left: 1px solid var(--post-column-border,#e2e0d8);
  border-right: 1px solid var(--post-column-border,#e2e0d8);
  background: #f8fafc;
}
.ev-breadcrumb {
  display: flex; align-items: center; gap: .5rem;
  padding: .7rem 2rem;
  background: #fff; border-bottom: 1px solid #f1f5f9;
  font-size: .8rem;
}
.ev-breadcrumb a { color: var(--ev-primary); text-decoration: none; font-weight: 600; transition: opacity .15s; }
.ev-breadcrumb a:hover { opacity: .7; }
.ev-breadcrumb__sep { color: #cbd5e1; }
.ev-breadcrumb__cur { color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 340px; }

/* Hero */
.ev-hero {
  position: relative; overflow: hidden;
  min-height: 220px; max-height: 300px;
  display: flex; flex-direction: column; justify-content: flex-end;
  background: linear-gradient(150deg, <?= $hero_bg_light ?> 0%, #ffffff 55%);
  border-bottom: 3px solid var(--ev-primary);
}
.ev-hero::after {
  content:''; position: absolute; inset: 0; pointer-events: none; z-index: 0;
  background: radial-gradient(circle at 75% 50%, <?= $hero_bg_mid ?> 0%, transparent 65%);
}
/* Cover-Bild als Hintergrund – layoutneutral */
.ev-hero__cover { position: absolute; inset: 0; z-index: 0; }
.ev-hero__cover::after { content:''; position: absolute; inset: 0; background: linear-gradient(to top, rgba(0,0,0,.58) 0%, rgba(0,0,0,.15) 100%); }
.ev-hero__cover img { width: 100%; height: 100%; object-fit: cover; display: block; filter: brightness(.55); }
/* Wenn Cover vorhanden: Text auf dunklem Grund = weiß */
.ev-hero:has(.ev-hero__cover) .ev-hero__title { color: #fff; text-shadow: 0 1px 6px rgba(0,0,0,.5); }
.ev-hero:has(.ev-hero__cover) .ev-hero__cat   { background: rgba(255,255,255,.15); border-color: rgba(255,255,255,.2); color: rgba(255,255,255,.92); }
.ev-hero:has(.ev-hero__cover) .ev-hero__badge { background: rgba(0,0,0,.38); border-color: rgba(255,255,255,.18); color: #fff; }
.ev-hero:has(.ev-hero__cover) .ev-hero__date  { background: rgba(255,255,255,.12); box-shadow: 0 0 0 1.5px rgba(255,255,255,.25); color: #fff; }
.ev-hero__inner {
  position: relative; z-index: 1;
  display: flex; gap: 1.5rem; align-items: center;
  padding: 1.5rem 2rem 1.75rem; flex-wrap: wrap;
  min-height: 220px;
}
.ev-hero__date {
  flex-shrink: 0;
  background: linear-gradient(135deg, var(--ev-primary-h, var(--ev-primary)), var(--ev-primary));
  color: #fff; border-radius: 16px; padding: 1rem 1.25rem;
  text-align: center; min-width: 68px;
  box-shadow: 0 6px 20px rgba(0,0,0,.12), 0 0 0 4px rgba(255,255,255,.65);
}
.ev-hero__date-day  { display: block; font-size: 2.4rem; font-weight: 900; line-height: 1; }
.ev-hero__date-mon  { display: block; font-size: .78rem; text-transform: uppercase; letter-spacing: .06em; opacity: .85; margin-top: 3px; }
.ev-hero__date-year { display: block; font-size: .7rem; opacity: .65; margin-top: 1px; }
.ev-hero__meta { flex: 1; min-width: 0; }
.ev-hero__cat {
  display: inline-block; font-size: .72rem; font-weight: 700;
  text-transform: uppercase; letter-spacing: .08em;
  color: var(--ev-primary); background: rgba(255,255,255,.8);
  border: 1px solid var(--ev-border); padding: .2rem .7rem; border-radius: 50px;
  margin-bottom: .6rem; backdrop-filter: blur(4px);
}
.ev-hero__title {
  margin: 0 0 .875rem;
  font-size: clamp(1.375rem, 3vw, 2rem); font-weight: 800;
  line-height: 1.2; color: #0f172a;
}
.ev-hero__badges { display: flex; flex-wrap: wrap; gap: .4rem; }
.ev-hero__badge {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .3rem .8rem; border-radius: 50px;
  font-size: .76rem; font-weight: 600;
  background: rgba(255,255,255,.85); color: #475569;
  border: 1.5px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0,0,0,.04); backdrop-filter: blur(3px);
}
.ev-hero__badge--online   { background: #d1fae5; color: #065f46; border-color: #a7f3d0; }
.ev-hero__badge--featured { background: #fef3c7; color: #92400e; border-color: #fde68a; }
.ev-hero__badge--cancelled{ background: #fee2e2; color: #991b1b; border-color: #fca5a5; }
.ev-hero__badge--speakers { background: #ede9fe; color: #5b21b6; border-color: #ddd6fe; }
.ev-hero__badge--time { background: var(--ev-light); color: var(--ev-primary); border-color: var(--ev-border); }

/* Body */
.ev-single-v2 .ev-single-body {
  display: grid; grid-template-columns: 1fr 320px;
  gap: 1.75rem; padding: 1.75rem 2rem 3rem; align-items: start;
}
.ev-single-v2 .ev-main-col  { min-width: 0; }
.ev-single-v2 .ev-sidebar-col {
  position: sticky; top: 1.5rem;
  display: flex; flex-direction: column; gap: .875rem;
}

/* Content Sections */
.ev-section {
  background: #fff; border-radius: var(--ev-radius, 14px);
  padding: 1.5rem 1.75rem; margin-bottom: 1.125rem;
  box-shadow: var(--ev-shadow); transition: box-shadow .2s;
}
.ev-section:hover { box-shadow: var(--ev-shadow-h); }
.ev-section:last-child { margin-bottom: 0; }
.ev-section__title {
  font-size: .92rem; font-weight: 700; color: #1e293b;
  margin: 0 0 1.125rem; padding-bottom: .6rem; padding-left: .75rem;
  display: flex; align-items: center; gap: .4rem;
  border-left: 3px solid var(--ev-primary);
  border-bottom: 1.5px solid #f1f5f9;
}
.ev-section--cover { padding: 0; border-radius: var(--ev-radius, 14px); overflow: hidden; }
.ev-section--cover img { width: 100%; max-height: 360px; object-fit: cover; display: block; }

/* Sidebar Cards */
.ev-sc {
  background: #fff; border-radius: var(--ev-radius, 14px);
  padding: 1.25rem 1.375rem; box-shadow: var(--ev-shadow);
}
.ev-sc__title {
  font-size: .7rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .07em; color: #94a3b8; margin: 0 0 .875rem;
}
.ev-info-v2 { display: flex; flex-direction: column; }
.ev-info-v2__row {
  display: flex; justify-content: space-between; align-items: baseline;
  gap: .75rem; padding: .45rem 0; border-bottom: 1px solid #f8fafc;
}
.ev-info-v2__row:last-child { border-bottom: none; }
.ev-info-v2__label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; flex-shrink: 0; }
.ev-info-v2__value { font-size: .85rem; font-weight: 500; color: #1e293b; text-align: right; }
.ev-info-v2__value a { color: var(--ev-primary); text-decoration: none; }

/* CTA */
.ev-cta-v2 {
  display: flex; align-items: center; justify-content: center; gap: .5rem;
  padding: .9rem 1.5rem; width: 100%; box-sizing: border-box;
  font-size: .9rem; font-weight: 700; border-radius: 10px; border: none;
  cursor: pointer; text-decoration: none; color: #fff; margin-bottom: .5rem;
  background: linear-gradient(135deg, var(--ev-primary-h, var(--ev-primary)), var(--ev-primary));
  box-shadow: 0 4px 16px color-mix(in srgb, var(--ev-primary) 30%, transparent);
  transition: var(--ev-ease);
}
.ev-cta-v2:last-child { margin-bottom: 0; }
.ev-cta-v2:hover { transform: translateY(-2px); box-shadow: 0 8px 24px color-mix(in srgb, var(--ev-primary) 38%, transparent); }
.ev-cta-v2--ghost {
  background: var(--ev-light); color: var(--ev-primary);
  box-shadow: none; border: 1.5px solid var(--ev-border);
}
.ev-cta-v2--ghost:hover { background: var(--ev-card-bg); box-shadow: none; }

/* Price Badge */
.ev-price-v2 {
  display: inline-flex; align-items: center; gap: .4rem;
  padding: .6rem 1rem; border-radius: 10px;
  font-size: 1rem; font-weight: 800;
}
.ev-price-v2--free     { background: #d1fae5; color: #065f46; }
.ev-price-v2--donation { background: #fdf4ff; color: #7e22ce; }
.ev-price-v2--paid     { background: var(--ev-light); color: var(--ev-primary); }

/* Tags */
.ev-tags-v2 { display: flex; flex-wrap: wrap; gap: .3rem; }
.ev-tag-v2 {
  display: inline-block; padding: .2rem .65rem;
  background: var(--ev-light); color: var(--ev-primary);
  border: 1px solid var(--ev-border); border-radius: 50px;
  font-size: .72rem; font-weight: 600;
}

/* Speaker Rows */
.ev-speaker-v2 {
  display: flex; gap: .875rem; align-items: center;
  padding: .75rem .875rem; border-radius: 10px;
  background: #fafbfc; border: 1px solid #f1f5f9;
  margin-bottom: .5rem; transition: background .15s, border-color .15s;
}
.ev-speaker-v2:last-child { margin-bottom: 0; }
.ev-speaker-v2:hover { background: var(--ev-light); border-color: var(--ev-border); }
.ev-speaker-v2__avatar {
  width: 44px; height: 44px; border-radius: 10px; overflow: hidden; flex-shrink: 0;
  background: var(--ev-light); display: flex; align-items: center; justify-content: center;
  font-weight: 800; color: var(--ev-primary); font-size: .9rem; border: 1px solid var(--ev-border);
}
.ev-speaker-v2__avatar img { width: 100%; height: 100%; object-fit: cover; }
.ev-speaker-v2__name { font-size: .9rem; font-weight: 700; color: #1e293b; }
.ev-speaker-v2__role { font-size: .775rem; color: #94a3b8; margin-top: 2px; }

/* Org */
.ev-sc__address { font-size: .875rem; color: #1e293b; line-height: 1.6; }
.ev-sc__address-city { font-size: .8rem; color: #64748b; margin-top: .2rem; }
.ev-org__name { font-size: .875rem; font-weight: 600; color: #1e293b; margin-bottom: .5rem; }
.ev-org__row  { font-size: .8rem; color: #64748b; margin-top: .25rem; }
.ev-org__row a { color: var(--ev-primary); text-decoration: none; }

@media (max-width: 768px) {
  .ev-single-v2 .ev-single-body { grid-template-columns: 1fr; padding: 1.25rem 1rem 2rem; gap: 1.25rem; }
  .ev-single-v2 .ev-sidebar-col { position: static; }
  .ev-hero { min-height: 200px; max-height: none; overflow: visible; }
  .ev-hero__inner { padding: 1rem 1rem 1.25rem; gap: 1rem; min-height: 200px; }
  .ev-hero__title { font-size: 1.3rem; }
  .ev-breadcrumb  { padding: .6rem 1rem; }
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
          <div class="ev-wysiwyg-content" style="line-height:1.75;color:#334155;font-size:.95rem;">
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
