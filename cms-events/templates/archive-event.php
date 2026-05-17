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

$archive_title = htmlspecialchars((string) ($settings['archive_title'] ?? 'Events'), ENT_QUOTES, 'UTF-8');
$archive_desc  = htmlspecialchars((string) ($settings['archive_description'] ?? 'Aktuelle Veranstaltungen'), ENT_QUOTES, 'UTF-8');
$grid_cols = max(1, (int)($settings['grid_columns'] ?? 3));
if ($grid_cols < 1) $grid_cols = 3;

/* ── Filter-Variablen normalisieren ───────────────────────────── */
$cur_search   = htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8');
$cur_city     = htmlspecialchars((string) ($filter_city ?? ''), ENT_QUOTES, 'UTF-8');
$cur_cat      = htmlspecialchars((string) ($filter_category ?? ''), ENT_QUOTES, 'UTF-8');
$cur_when     = htmlspecialchars((string) ($when_filter ?? ''), ENT_QUOTES, 'UTF-8');
$cur_online   = $filter_online !== null && $filter_online !== '' ? (string)(int)$filter_online : '';

/* ── Pagination ──────────────────────────────────────────────── */
$cur_page   = max(1, (int)($current_page ?? 1));
$tot_pages  = max(1, (int)($pages ?? 1));
$tot_events = (int)($total ?? count($events));

/* ── Archive-URL ─────────────────────────────────────────────── */
$base_url    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$archive_slug = preg_replace('/[^a-z0-9-]+/i', '-', (string) ($settings['archive_slug'] ?? 'events')) ?: 'events';
$archive_url = $base_url . '/' . trim($archive_slug, '-') . '/';

?>
<main class="phinit-plugin ev-archive">

  <!-- Gradient-Header (nur wenn Titel oder Beschreibung in Einstellungen gesetzt) -->
  <?php
  $has_title = !empty(trim((string)($settings['archive_title']       ?? '')));
  $has_desc  = !empty(trim((string)($settings['archive_description'] ?? '')));
  if ($has_title || $has_desc):
  ?>
  <header class="ev-archive-header phinit-card phinit-card--accent">
    <div class="ev-archive-header-inner">
      <div>
        <h2><?= $archive_title ?></h2>
        <p class="ev-archive-subtitle"><?= $archive_desc ?></p>
      </div>
      <div class="ev-archive-count">
        <span class="ev-archive-count-num"><?= $tot_events ?></span>
        <span class="ev-archive-count-lbl">Events</span>
      </div>
    </div>
  </header>
  <?php endif; ?>

  <!-- Filter-Bar -->
  <form class="ev-filter-bar phinit-card" method="GET" action="<?= htmlspecialchars($archive_url, ENT_QUOTES, 'UTF-8') ?>" role="search">

    <div class="ev-filter-input phinit-field">
      <label for="ev-search">Event suchen</label>
      <input id="ev-search" class="phinit-input" type="search" name="search" placeholder="Event suchen…"
             value="<?= $cur_search ?>">
    </div>

    <div class="ev-filter-input phinit-field">
      <label for="ev-city">Ort / Stadt</label>
      <input id="ev-city" class="phinit-input" type="text" name="city" placeholder="Ort / Stadt…"
             value="<?= $cur_city ?>">
    </div>

    <?php if (!empty($categories)): ?>
      <label class="phinit-field" for="ev-category"><span>Kategorie</span>
      <select id="ev-category" name="category" class="ev-filter-select phinit-select">
        <option value="">Kategorie</option>
        <?php foreach ((array)$categories as $cat): ?>
          <?php $catEsc = htmlspecialchars((string) $cat, ENT_QUOTES, 'UTF-8'); ?>
          <option value="<?= $catEsc ?>"<?= $cur_cat === $catEsc ? ' selected' : '' ?>>
            <?= $catEsc ?>
          </option>
        <?php endforeach; ?>
      </select></label>
    <?php endif; ?>

    <label class="phinit-field" for="ev-when"><span>Zeitraum</span>
    <select id="ev-when" name="when" class="ev-filter-select phinit-select">
      <option value="">Zeitraum</option>
      <option value="upcoming"<?= $cur_when === 'upcoming' ? ' selected' : '' ?>>Bevorstehend</option>
      <option value="past"<?= $cur_when === 'past' ? ' selected' : '' ?>>Vergangen</option>
    </select></label>

    <label class="phinit-field" for="ev-online"><span>Format</span>
    <select id="ev-online" name="online" class="ev-filter-select phinit-select">
      <option value="">Alle Formate</option>
      <option value="0"<?= $cur_online === '0' ? ' selected' : '' ?>>Präsenz</option>
      <option value="1"<?= $cur_online === '1' ? ' selected' : '' ?>>Online</option>
    </select></label>

    <button type="submit" class="phinit-btn phinit-btn--primary ev-btn ev-btn-primary">Suchen</button>

    <?php if ($cur_search || $cur_city || $cur_cat || $cur_when || $cur_online !== ''): ?>
      <a href="<?= htmlspecialchars($archive_url, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary ev-btn ev-btn-ghost">Reset</a>
    <?php endif; ?>

  </form>

  <!-- Event-Grid -->
  <?php
  // $db wird von event-card.php intern benötigt, wenn speaker count per query:
  $db = CMS_Events_Database::instance();
  ?>
  <section class="ev-grid phinit-grid" aria-label="Event-Liste">
    <?php if (empty($events)): ?>
      <div class="ev-empty phinit-empty-state" role="status" aria-live="polite">
        <p><strong>Keine Events gefunden.</strong></p>
        <?php if ($cur_search || $cur_city || $cur_cat || $cur_when): ?>
          <p><a href="<?= htmlspecialchars($archive_url, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary ev-btn ev-btn-ghost ev-btn--inline">Filter zurücksetzen</a></p>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php foreach ((array)$events as $event): ?>
        <?php include __DIR__ . '/event-card.php'; ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <!-- Pagination -->
  <?php if ($tot_pages > 1): ?>
    <?php $eventPaginationBase = $archive_url . '?' . http_build_query(['search' => html_entity_decode($cur_search, ENT_QUOTES, 'UTF-8'), 'city' => html_entity_decode($cur_city, ENT_QUOTES, 'UTF-8'), 'category' => html_entity_decode($cur_cat, ENT_QUOTES, 'UTF-8')]); ?>
    <nav class="ev-pagination" aria-label="Seitennavigation">
      <?php if ($cur_page > 1): ?>
        <a class="ev-page-btn phinit-btn phinit-btn--secondary"
           href="<?= htmlspecialchars($eventPaginationBase . '&page=' . ($cur_page - 1), ENT_QUOTES, 'UTF-8') ?>">Zurück</a>
      <?php endif; ?>
      <?php for ($i = max(1, $cur_page - 2); $i <= min($tot_pages, $cur_page + 2); $i++): ?>
        <a class="ev-page-btn phinit-btn phinit-btn--secondary<?= $i === $cur_page ? ' active' : '' ?>"
           href="<?= htmlspecialchars($eventPaginationBase . '&page=' . $i, ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
      <?php endfor; ?>
      <?php if ($cur_page < $tot_pages): ?>
        <a class="ev-page-btn phinit-btn phinit-btn--secondary"
           href="<?= htmlspecialchars($eventPaginationBase . '&page=' . ($cur_page + 1), ENT_QUOTES, 'UTF-8') ?>">Weiter</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>

</main><!-- /.ev-archive -->
