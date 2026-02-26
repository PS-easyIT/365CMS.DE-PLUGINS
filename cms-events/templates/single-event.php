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

declare(strict_types=1);

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
$s_detail_hdr_bg   = htmlspecialchars($settings['color_detail_hdr_bg']   ?? '#0f172a');
$s_detail_hdr_text = htmlspecialchars($settings['color_detail_hdr_text'] ?? '#ffffff');
$s_detail_accent   = htmlspecialchars($settings['color_detail_accent']   ?? '#3b82f6');
?>
<style>
:root {
  /* ── Primärfarben aus Einstellungen ──────── */
  --ev-primary:         <?= $primary ?>;
  --ev-primary-h:       <?= htmlspecialchars($settings['color_hdr_from'] ?? '#1d4ed8') ?>;
  --ev-accent:          <?= $accent ?>;
  --ev-light:           #eff6ff;
  --ev-border:          <?= $s_card_border ?>;
  --ev-border-l:        #f1f5f9;
  --ev-card-bg:         <?= htmlspecialchars($settings['color_card_bg'] ?? '#f0f7ff') ?>;
  --ev-featured-border: <?= $s_featured_border ?>;
  /* ── Archiv-Header-Gradient (für Referenz) ── */
  --ev-hdr-from:        <?= $hdr_from ?>;
  --ev-hdr-to:          <?= $hdr_to ?>;
  --ev-hdr-title:       <?= $hdr_title ?>;
  --ev-radius:          <?= $s_radius ?>;
  /* ── Detailseite-Header ───────────────────── */
  --ev-detail-hdr-bg:   <?= $s_detail_hdr_bg ?>;
  --ev-detail-hdr-text: <?= $s_detail_hdr_text ?>;
  --ev-detail-accent:   <?= $s_detail_accent ?>;
  /* ── CTA ─────────────────────────────────── */
  --ev-cta:             <?= $s_cta ?>;
  /* ── Badge-Farben ────────────────────────── */
  --ev-online-badge:    <?= $s_online_badge ?>;
  --ev-online-badge-bg: #d1fae5;
  --ev-cancelled-bg:    <?= $s_cancelled_bg ?>;
  --ev-cancelled-color: #991b1b;
  /* ── Neutrale Farben ─────────────────────── */
  --ev-text:            #1e293b;
  --ev-text-m:          #475569;
  --ev-text-l:          #94a3b8;
  --ev-bg:              #ffffff;
  --ev-shadow:          0 4px 16px rgba(0,0,0,.06);
  --ev-shadow-h:        0 12px 32px rgba(0,0,0,.12);
  --ev-ease:            all .2s cubic-bezier(.4,0,.2,1);
}
</style>

<div class="ev-single">

  <!-- Back Link -->
  <a class="ev-back-link" href="<?= $archive_url ?>">← Alle Events</a>

  <!-- Hero Header -->
  <div class="ev-single-header">
    <div class="ev-sh-inner">
      <?php if ($ev_ts): ?>
        <div class="ev-sh-date-block">
          <span class="ev-sh-date-day"><?= date('d', $ev_ts) ?></span>
          <span class="ev-sh-date-mon"><?= date('M', $ev_ts) ?></span>
          <span class="ev-sh-date-year"><?= date('Y', $ev_ts) ?></span>
        </div>
      <?php endif; ?>
      <div class="ev-sh-text">
        <?php if ($category): ?>
          <div class="ev-sh-cat">📂 <?= $category ?></div>
        <?php endif; ?>
        <h1><?= $title ?></h1>
        <div class="ev-sh-badges">
          <?php if ($is_online): ?>
            <span class="ev-sh-badge ev-sh-badge--online">🌐 Online</span>
          <?php elseif ($city): ?>
            <span class="ev-sh-badge">📍 <?= $city ?></span>
          <?php endif; ?>
          <?php if ($is_featured): ?>
            <span class="ev-sh-badge ev-sh-badge--featured">⭐ Featured</span>
          <?php endif; ?>
          <?php if ($status === 'cancelled'): ?>
            <span class="ev-sh-badge ev-sh-badge--cancelled">🚫 Abgesagt</span>
          <?php endif; ?>
          <?php if (!empty($speakers)): ?>
            <span class="ev-sh-badge ev-sh-badge--speakers">🎤 <?= count($speakers) ?> Speaker</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div><!-- /.ev-single-header -->

  <!-- Two-Column Body -->
  <div class="ev-single-body">

    <!-- Hauptinhalt (links) -->
    <div class="ev-main-col">

      <?php if ($banner_url): ?>
        <div class="ev-detail-section" style="padding:0;overflow:hidden;border-radius:var(--ev-radius,12px);">
          <img src="<?= $banner_url ?>" alt="<?= $title ?>"
               style="width:100%;max-height:340px;object-fit:cover;display:block;" loading="lazy">
        </div>
      <?php elseif ($image_url): ?>
        <div class="ev-detail-section" style="padding:0;overflow:hidden;border-radius:var(--ev-radius,12px);">
          <img src="<?= $image_url ?>" alt="<?= $title ?>"
               style="width:100%;height:240px;object-fit:cover;display:block;" loading="lazy">
        </div>
      <?php endif; ?>

      <?php if ($desc): ?>
        <div class="ev-detail-section">
          <h2 class="ev-section-title">Beschreibung</h2>
          <div class="ev-wysiwyg-content" style="line-height:1.7;color:var(--ev-text);">
            <?= $desc ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($speakers)): ?>
        <div class="ev-detail-section">
          <h2 class="ev-section-title">Speaker & Referenten</h2>
          <?php foreach ((array)$speakers as $sp):
            $sp_name  = htmlspecialchars(trim(($sp->first_name ?? '') . ' ' . ($sp->last_name ?? '')));
            $sp_role  = htmlspecialchars($sp->role ?? 'Speaker');
            $sp_photo = $sp->photo_url ?? '';
            $sp_init  = strtoupper(mb_substr($sp->first_name ?? '', 0, 1) . mb_substr($sp->last_name ?? '', 0, 1));
          ?>
            <div class="ev-speaker-row">
              <div class="ev-speaker-avatar">
                <?php if ($sp_photo): ?>
                  <img src="<?= htmlspecialchars($sp_photo) ?>" alt="<?= $sp_name ?>">
                <?php else: ?>
                  <?= htmlspecialchars($sp_init ?: '👤') ?>
                <?php endif; ?>
              </div>
              <div>
                <div class="ev-speaker-info-name"><?= $sp_name ?></div>
                <div class="ev-speaker-info-role"><?= $sp_role ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div><!-- /.ev-main-col -->

    <!-- Sidebar (rechts) -->
    <aside class="ev-sidebar-col">

      <!-- Anmelde-CTA -->
      <?php if ($reg_url && $status !== 'cancelled'): ?>
        <div class="ev-sidebar-card">
          <a href="<?= $reg_url ?>" target="_blank" rel="noopener" class="ev-cta-btn">
            🎟 Jetzt anmelden
          </a>
        </div>
      <?php elseif ($is_online && $online_url): ?>
        <div class="ev-sidebar-card">
          <a href="<?= $online_url ?>" target="_blank" rel="noopener" class="ev-cta-btn">
            🔗 Online-Link aufrufen
          </a>
        </div>
      <?php endif; ?>

      <!-- Termin -->
      <div class="ev-sidebar-card">
        <h3 class="ev-sidebar-title">Termin</h3>
        <div class="ev-info-grid">
          <?php if ($ev_ts): ?>
            <div class="ev-info-item">
              <span class="ev-info-label">Datum</span>
              <span class="ev-info-value"><?= date('d.m.Y', $ev_ts) ?></span>
            </div>
            <?php if ($ev_time): ?>
              <div class="ev-info-item">
                <span class="ev-info-label">Uhrzeit</span>
                <span class="ev-info-value"><?= htmlspecialchars($ev_time) ?> Uhr</span>
              </div>
            <?php endif; ?>
          <?php endif; ?>
          <?php if ($end_ts && $end_ts !== $ev_ts): ?>
            <div class="ev-info-item">
              <span class="ev-info-label">Ende</span>
              <span class="ev-info-value"><?= date('d.m.Y', $end_ts) ?></span>
            </div>
            <?php if ($end_time): ?>
              <div class="ev-info-item">
                <span class="ev-info-label">Endzeit</span>
                <span class="ev-info-value"><?= htmlspecialchars($end_time) ?> Uhr</span>
              </div>
            <?php endif; ?>
          <?php endif; ?>
          <?php if ($capacity): ?>
            <div class="ev-info-item">
              <span class="ev-info-label">Kapazität</span>
              <span class="ev-info-value"><?= $capacity ?> Plätze</span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Ort -->
      <?php if (!$is_online && ($city || $address)): ?>
        <div class="ev-sidebar-card">
          <h3 class="ev-sidebar-title">Veranstaltungsort</h3>
          <?php if ($address): ?>
            <div style="font-size:.875rem;color:var(--ev-text);margin-bottom:.25rem;"><?= $address ?></div>
          <?php endif; ?>
          <div style="font-size:.875rem;color:var(--ev-text-m);">
            <?= $zip ? $zip . ' ' : '' ?><?= $city ?><?= $country ? ', ' . $country : '' ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Preis -->
      <?php if ($show_price): ?>
        <div class="ev-sidebar-card">
          <h3 class="ev-sidebar-title">Preis</h3>
          <?php if ($price_type === 'free'): ?>
            <div class="ev-price-badge ev-price-free">✅ Kostenlos / Kostenfrei</div>
          <?php elseif ($price_type === 'donation'): ?>
            <div class="ev-price-badge" style="background:#fdf4ff;color:#7e22ce;">💝 Spendenbasis</div>
          <?php elseif ($price > 0): ?>
            <div class="ev-price-badge" style="background:#eff6ff;color:#1d4ed8;font-size:1.25rem;font-weight:800;">
              <?= number_format($price, 2, ',', '.') ?> <?= $price_cur ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Tags -->
      <?php if ($show_tags && !empty($tags_raw)): ?>
        <div class="ev-sidebar-card">
          <h3 class="ev-sidebar-title">🏷️ Tags</h3>
          <div style="display:flex;flex-wrap:wrap;gap:.35rem;">
            <?php foreach ($tags_raw as $tag): ?>
              <span class="ev-tag-pill"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Veranstalter -->
      <?php if ($show_org && $org_name): ?>
        <div class="ev-sidebar-card">
          <h3 class="ev-sidebar-title">🏢 Veranstalter</h3>
          <div style="font-size:.875rem;color:var(--ev-text);font-weight:600;margin-bottom:.5rem;"><?= $org_name ?></div>
          <?php if ($org_email): ?>
            <div style="font-size:.8rem;color:#64748b;">📧 <a href="mailto:<?= $org_email ?>" style="color:var(--ev-primary);"><?= $org_email ?></a></div>
          <?php endif; ?>
          <?php if ($org_phone): ?>
            <div style="font-size:.8rem;color:#64748b;margin-top:.25rem;">📞 <?= $org_phone ?></div>
          <?php endif; ?>
          <?php if ($org_website): ?>
            <div style="font-size:.8rem;margin-top:.25rem;">🔗 <a href="<?= $org_website ?>" target="_blank" rel="noopener" style="color:var(--ev-primary);">Website besuchen</a></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </aside><!-- /.ev-sidebar-col -->

  </div><!-- /.ev-single-body -->
</div><!-- /.ev-single -->
