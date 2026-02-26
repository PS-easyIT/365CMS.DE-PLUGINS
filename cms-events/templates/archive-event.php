<?php
/**
 * Event Archive Template – Struktur nach co-archive
 *
 * Verfügbare Variablen (via extract() aus post-type::archive_page()):
 *   $events, $settings, $current_page, $per_page, $pages, $total,
 *   $categories, $filter_category, $filter_city, $filter_month,
 *   $filter_online, $when_filter, $search
 *
 * @package CMS_Events
 */

declare(strict_types=1);

if (!isset($events, $settings)) {
    return;
}

/* ── CSS-Variablen aus Einstellungen ──────────────────────────── */
$primary   = htmlspecialchars($settings['color_primary']   ?? '#3b82f6');
$accent    = htmlspecialchars($settings['color_accent']    ?? '#60a5fa');
$hdr_from  = htmlspecialchars($settings['color_hdr_from']  ?? '#1d4ed8');
$hdr_to    = htmlspecialchars($settings['color_hdr_to']    ?? '#3b82f6');
$hdr_title = htmlspecialchars($settings['color_hdr_title'] ?? '#ffffff');
$card_bg   = htmlspecialchars($settings['color_card_bg']   ?? '#f0f7ff');
$radius_raw = $settings['border_radius'] ?? '12';
$radius     = htmlspecialchars(is_numeric($radius_raw) ? $radius_raw . 'px' : $radius_raw);
$hdr_icon   = html_entity_decode($settings['archive_header_icon'] ?? '📅', ENT_HTML5, 'UTF-8');

$archive_title = htmlspecialchars($settings['archive_title']       ?? 'Events');
$archive_desc  = htmlspecialchars($settings['archive_description'] ?? 'Aktuelle Veranstaltungen');
$grid_cols = max(1, (int)($settings['grid_columns'] ?? 3));
if ($grid_cols < 1) $grid_cols = 3;

/* ── Filter-Variablen normalisieren ───────────────────────────── */
$cur_search   = htmlspecialchars($search          ?? '');
$cur_city     = htmlspecialchars($filter_city     ?? '');
$cur_cat      = htmlspecialchars($filter_category ?? '');
$cur_when     = htmlspecialchars($when_filter     ?? '');
$cur_online   = $filter_online !== null && $filter_online !== '' ? (string)(int)$filter_online : '';

/* ── Pagination ──────────────────────────────────────────────── */
$cur_page   = max(1, (int)($current_page ?? 1));
$tot_pages  = max(1, (int)($pages ?? 1));
$tot_events = (int)($total ?? count($events));

/* ── Archive-URL ─────────────────────────────────────────────── */
$base_url    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$archive_url = $base_url . '/' . ($settings['archive_slug'] ?? 'events') . '/';

/* ── Zusätzliche CSS-Variablen aus Einstellungen ─────────────── */
$s_online_badge    = htmlspecialchars($settings['color_online_badge']    ?? '#059669');
$s_cancelled_bg    = htmlspecialchars($settings['color_cancelled_bg']    ?? '#fee2e2');
$s_cta             = htmlspecialchars($settings['color_cta']             ?? '#1e40af');
$s_card_border     = htmlspecialchars($settings['color_card_border']     ?? '#bfdbfe');
$s_featured_border = htmlspecialchars($settings['color_featured_border'] ?? '#f59e0b');
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
  --ev-card-bg:         <?= $card_bg ?>;
  --ev-featured-border: <?= $s_featured_border ?>;
  /* ── Archiv-Header ────────────────────────── */
  --ev-hdr-from:        <?= $hdr_from ?>;
  --ev-hdr-to:          <?= $hdr_to ?>;
  --ev-hdr-title:       <?= $hdr_title ?>;
  --ev-radius:          <?= $radius ?>;
  /* ── CTA ─────────────────────────────────── */
  --ev-cta:             <?= $s_cta ?>;
  /* ── Badge-Farben ────────────────────────── */
  --ev-online-badge:    <?= $s_online_badge ?>;
  --ev-online-badge-bg: #d1fae5;
  --ev-cancelled-bg:    <?= $s_cancelled_bg ?>;
  --ev-cancelled-color: #991b1b;
  /* ── Neutrale Farben (Design-Tokens) ─────── */
  --ev-text:            #1e293b;
  --ev-text-m:          #475569;
  --ev-text-l:          #94a3b8;
  --ev-bg:              #ffffff;
  --ev-shadow:          0 4px 16px rgba(0,0,0,.06);
  --ev-shadow-h:        0 12px 32px rgba(0,0,0,.12);
  --ev-ease:            all .2s cubic-bezier(.4,0,.2,1);
}
.ev-grid { grid-template-columns: repeat(<?= $grid_cols ?>,1fr); }
@media(max-width:1024px){ .ev-grid{ grid-template-columns:repeat(2,1fr) !important; } }
@media(max-width:640px){ .ev-grid{ grid-template-columns:1fr !important; } }
</style>

<div class="ev-archive">

  <!-- Gradient-Header (nur wenn Titel oder Beschreibung in Einstellungen gesetzt) -->
  <?php
  $has_title = !empty(trim((string)($settings['archive_title']       ?? '')));
  $has_desc  = !empty(trim((string)($settings['archive_description'] ?? '')));
  if ($has_title || $has_desc):
  ?>
  <div class="ev-archive-header">
    <div class="ev-archive-header-inner">
      <span class="ev-archive-icon"><?= htmlspecialchars($hdr_icon) ?></span>
      <div>
        <h1><?= $archive_title ?></h1>
        <p class="ev-archive-subtitle"><?= $archive_desc ?></p>
      </div>
      <div class="ev-archive-count">
        <span class="ev-archive-count-num"><?= $tot_events ?></span>
        <span class="ev-archive-count-lbl">Events</span>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filter-Bar -->
  <form class="ev-filter-bar" method="GET" action="<?= $archive_url ?>">

    <div class="ev-filter-input">
      <span class="ev-filter-icon">🔍</span>
      <input type="search" name="search" placeholder="Event suchen…"
             value="<?= $cur_search ?>">
    </div>

    <div class="ev-filter-input">
      <span class="ev-filter-icon">📍</span>
      <input type="text" name="city" placeholder="Ort / Stadt…"
             value="<?= $cur_city ?>">
    </div>

    <?php if (!empty($categories)): ?>
      <select name="category" class="ev-filter-select">
        <option value="">Kategorie</option>
        <?php foreach ((array)$categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>"<?= $cur_cat === htmlspecialchars($cat) ? ' selected' : '' ?>>
            <?= htmlspecialchars($cat) ?>
          </option>
        <?php endforeach; ?>
      </select>
    <?php endif; ?>

    <select name="when" class="ev-filter-select">
      <option value="">Zeitraum</option>
      <option value="upcoming"<?= $cur_when === 'upcoming' ? ' selected' : '' ?>>Bevorstehend</option>
      <option value="past"<?= $cur_when === 'past' ? ' selected' : '' ?>>Vergangen</option>
    </select>

    <select name="online" class="ev-filter-select">
      <option value="">Alle Formate</option>
      <option value="0"<?= $cur_online === '0' ? ' selected' : '' ?>>Präsenz</option>
      <option value="1"<?= $cur_online === '1' ? ' selected' : '' ?>>Online</option>
    </select>

    <button type="submit" class="ev-btn ev-btn-primary">🔍 Suchen</button>

    <?php if ($cur_search || $cur_city || $cur_cat || $cur_when || $cur_online !== ''): ?>
      <a href="<?= $archive_url ?>" class="ev-btn ev-btn-ghost">✕ Reset</a>
    <?php endif; ?>

  </form>

  <!-- Event-Grid -->
  <?php
  // $db wird von event-card.php intern benötigt, wenn speaker count per query:
  $db = CMS_Events_Database::instance();
  ?>
  <div class="ev-grid">
    <?php if (empty($events)): ?>
      <div class="ev-empty">
        <span class="ev-empty-icon">📭</span>
        <p><strong>Keine Events gefunden.</strong></p>
        <?php if ($cur_search || $cur_city || $cur_cat || $cur_when): ?>
          <p><a href="<?= $archive_url ?>" class="ev-btn ev-btn-ghost"
               style="display:inline-flex;margin-top:.5rem;">Filter zurücksetzen</a></p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php foreach ((array)$events as $event): ?>
        <?php include __DIR__ . '/event-card.php'; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Pagination -->
  <?php if ($tot_pages > 1): ?>
    <nav class="ev-pagination" aria-label="Seitennavigation">
      <?php if ($cur_page > 1): ?>
        <a class="ev-page-btn"
           href="<?= $archive_url ?>?page=<?= $cur_page - 1 ?>&search=<?= urlencode($cur_search) ?>&city=<?= urlencode($cur_city) ?>&category=<?= urlencode($cur_cat) ?>">← Zurück</a>
      <?php endif; ?>
      <?php for ($i = max(1, $cur_page - 2); $i <= min($tot_pages, $cur_page + 2); $i++): ?>
        <a class="ev-page-btn<?= $i === $cur_page ? ' active' : '' ?>"
           href="<?= $archive_url ?>?page=<?= $i ?>&search=<?= urlencode($cur_search) ?>&city=<?= urlencode($cur_city) ?>&category=<?= urlencode($cur_cat) ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($cur_page < $tot_pages): ?>
        <a class="ev-page-btn"
           href="<?= $archive_url ?>?page=<?= $cur_page + 1 ?>&search=<?= urlencode($cur_search) ?>&city=<?= urlencode($cur_city) ?>&category=<?= urlencode($cur_cat) ?>">Weiter →</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>

</div><!-- /.ev-archive -->
