<?php
/**
 * Event Card Template – kompakt wie co-card
 *
 * Scope-Variablen:
 *   $event    – object
 *   $settings – array (aus archive übergeben)
 *   $db       – CMS_Events_Database::instance() (aus archive-event.php)
 *
 * @package CMS_Events
 */

declare(strict_types=1);

if (!isset($event)) {
    return;
}

$e        = $event;
$id       = (int) $e->id;
$title    = htmlspecialchars((string) ($e->title ?? ''), ENT_QUOTES, 'UTF-8');
$category = htmlspecialchars((string) ($e->category ?? ''), ENT_QUOTES, 'UTF-8');
$city     = htmlspecialchars((string) ($e->city ?? ''), ENT_QUOTES, 'UTF-8');
$excerpt_raw = !empty($e->excerpt) ? $e->excerpt : strip_tags($e->description ?? '');
$desc        = htmlspecialchars(mb_substr((string) $excerpt_raw, 0, 120, 'UTF-8'), ENT_QUOTES, 'UTF-8');
$reg_url     = function_exists('cms_events_public_url') ? cms_events_public_url($e->registration_url ?? null) : '';
$price_type  = $e->price_type ?? 'free';
$price       = (float)($e->price ?? 0);
$price_cur   = htmlspecialchars((string) ($e->price_currency ?? 'EUR'), ENT_QUOTES, 'UTF-8');
$tags_raw    = !empty($e->tags) ? (json_decode($e->tags, true) ?? []) : [];
$show_price  = !empty($settings['show_price'])  && $settings['show_price'] !== '0';
$show_tags   = !empty($settings['show_tags'])   && $settings['show_tags'] !== '0';
$show_org    = !empty($settings['show_organizer']) && $settings['show_organizer'] !== '0';
$is_online   = !empty($e->is_online);
$is_featured = !empty($e->is_featured);
$capacity    = (int)($e->capacity ?? 0);
$status      = $e->status ?? 'published';
$org_name    = htmlspecialchars(trim((string)($e->organizer_name ?? '')), ENT_QUOTES, 'UTF-8');

$base_url  = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$event_url = function_exists('cms_event_url') ? cms_event_url($e)
           : $base_url . '/' . ($settings['archive_slug'] ?? 'events') . '/' . $id . '/';

/* ── Datum ──────────────────────────────────────────────────── */
$ev_date   = $e->event_date ?? '';
$ev_ts     = $ev_date ? strtotime($ev_date) : 0;
$today_ts  = strtotime('today');
$is_past   = $ev_ts && $ev_ts < $today_ts;
$is_today  = $ev_ts && $ev_ts >= $today_ts && $ev_ts < ($today_ts + 86400);

/* ── Speaker Count ──────────────────────────────────────────── */
$speaker_count = 0;
if (isset($db) && method_exists($db, 'get_event_speakers')) {
    $speaker_count = count($db->get_event_speakers($id) ?: []);
}

/* ── Card-Klassen ───────────────────────────────────────────── */
$card_class = 'ev-card';
if ($is_featured) $card_class .= ' ev-card--featured';
if ($is_past)     $card_class .= ' ev-card--past';
if ($is_today)    $card_class .= ' ev-card--today';

/* ── Ribbon ─────────────────────────────────────────────────── */
if ($status === 'cancelled') {
    $ribbon_text  = 'Abgesagt';
    $ribbon_class = 'ev-ribbon-cancelled';
} elseif ($is_featured) {
  $ribbon_text  = 'Featured';
    $ribbon_class = 'ev-ribbon-featured';
} elseif ($is_today) {
  $ribbon_text  = 'Heute';
    $ribbon_class = 'ev-ribbon-today';
} elseif ($is_past) {
    $ribbon_text  = 'Vergangen';
    $ribbon_class = 'ev-ribbon-past';
} else {
    $ribbon_text  = $category ?: 'Event';
    $ribbon_class = '';
}
?>
<article class="phinit-card phinit-card--accent <?= $card_class ?>">

  <!-- Ribbon -->
  <div class="ev-card-ribbon <?= $ribbon_class ?>">
    <?= htmlspecialchars($ribbon_text, ENT_QUOTES, 'UTF-8') ?>
  </div>

  <!-- Head: Datum-Block + Titel/Kategorie + Pills rechts -->
  <div class="ev-card-head">
    <?php if ($ev_ts): ?>
      <div class="ev-date-block">
        <span class="ev-date-day"><?= date('d', $ev_ts) ?></span>
        <span class="ev-date-mon"><?= date('M', $ev_ts) ?></span>
        <span class="ev-date-year"><?= date('Y', $ev_ts) ?></span>
      </div>
    <?php endif; ?>
    <div class="ev-card-identity">
      <h3 class="ev-card-title">
        <a href="<?= htmlspecialchars($event_url, ENT_QUOTES, 'UTF-8') ?>"><?= $title ?></a>
      </h3>
      <?php if (($show_tags && !empty($tags_raw)) || ($show_org && $org_name !== '')): ?>
      <div class="ev-card-meta-stack">
        <?php if ($show_tags && !empty($tags_raw)): ?>
        <div class="ev-card-topics" aria-label="Event-Themen">
          <?php foreach (array_slice($tags_raw, 0, 4) as $tag): ?>
            <span class="ev-tag-pill" title="<?= htmlspecialchars((string)$tag, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$tag, ENT_QUOTES, 'UTF-8') ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if ($show_org && $org_name !== ''): ?>
        <p class="ev-card-organizer" title="<?= $org_name ?>">Veranstalter: <?= $org_name ?></p>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Pills-Zeile (wie co-card-pills) -->
  <?php $has_pills = $is_online || $city || $speaker_count > 0 || $capacity > 0 || ($show_price && ($price_type === 'free' || $price > 0)); ?>
  <?php if ($has_pills): ?>
  <div class="ev-card-pills">
    <?php if ($is_online): ?>
      <span class="ev-pill ev-pill-online">Online</span>
    <?php elseif ($city): ?>
      <span class="ev-pill">Ort: <?= $city ?></span>
    <?php endif; ?>
    <?php if ($speaker_count > 0): ?>
      <span class="ev-pill ev-pill-speakers">Speaker: <?= $speaker_count ?></span>
    <?php endif; ?>
    <?php if ($capacity > 0): ?>
      <span class="ev-pill ev-pill-capacity">Plätze: <?= $capacity ?></span>
    <?php endif; ?>
    <?php if ($show_price): ?>
      <?php if ($price_type === 'free'): ?>
        <span class="ev-pill ev-pill-free">Kostenlos</span>
      <?php elseif ($price > 0): ?>
        <span class="ev-pill ev-pill-price"><?= number_format($price, 0, ',', '.') ?>&nbsp;<?= $price_cur ?></span>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <?php endif; ?>
  <!-- Excerpt -->
  <?php if ($desc): ?>
    <p class="ev-card-excerpt"><?= $desc ?>…</p>
  <?php endif; ?>

  <!-- Footer -->
  <div class="ev-card-footer">
    <a href="<?= htmlspecialchars($event_url, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--primary ev-btn ev-btn-primary">Details <span class="phinit-arrow">→</span></a>
    <?php if ($reg_url && !$is_past && $status !== 'cancelled'): ?>
      <a href="<?= htmlspecialchars($reg_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"
         class="phinit-btn phinit-btn--secondary ev-btn ev-btn-ghost">Anmelden</a>
    <?php endif; ?>
  </div>

</article>
