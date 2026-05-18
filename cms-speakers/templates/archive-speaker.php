<?php
/**
 * Speaker Archive Template – Struktur nach experts-archive-wrapper
 *
 * Verfügbare Variablen (via extract() aus class-post-type.php::archive_page()):
 *  $speakers           – array of objects
 *  $settings           – array (keys: archive_title, archive_description,
 *                        archive_per_page, archive_header_bg_from/to/title_color,
 *                        design_primary_color, design_accent_color,
 *                        design_card_bg, design_border_radius, design_grid_columns)
 *  $page, $per_page, $pages, $total
 *  $cities             – distinct city array
 *  $search, $filter_city, $filter_availability, $filter_format, $filter_travel
 *
 * @package CMS_Speakers
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

if (!isset($speakers, $settings)) {
    return;
}

// Rohwerte für Sichtbarkeits-Prüfung (vor htmlspecialchars)
$archive_title_raw = trim($settings['archive_title']       ?? '');
$archive_desc_raw  = trim($settings['archive_description'] ?? '');
$has_header        = $archive_title_raw !== '' && $archive_desc_raw !== '';
$archive_title     = htmlspecialchars($archive_title_raw, ENT_QUOTES, 'UTF-8');
$archive_desc      = htmlspecialchars($archive_desc_raw, ENT_QUOTES, 'UTF-8');
$archive_icon_raw  = trim((string) ($settings['archive_header_icon'] ?? ''));
$archive_icon      = $archive_icon_raw !== '' ? htmlspecialchars(mb_substr(strip_tags($archive_icon_raw), 0, 3), ENT_QUOTES, 'UTF-8') : 'SP';
$cta_label         = htmlspecialchars((string) ($settings['design_cta_label'] ?? 'Profil ansehen'), ENT_QUOTES, 'UTF-8');

/* ── Archive-URL ──────────────────────────────────────────────── */
$archive_url = (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/speakers/';
?>
<main class="phinit-plugin sp-archive">

  <?php if ($has_header): ?>
  <!-- Gradient-Header – nur wenn Titel UND Beschreibung gesetzt -->
  <header class="sp-archive-header phinit-card phinit-card--accent">
    <div class="sp-archive-header-inner">
      <span class="sp-archive-header-icon" aria-hidden="true"><?= $archive_icon ?></span>
      <div>
        <?php if ($archive_title !== ''): ?><h2 class="sp-archive-header-title"><?= $archive_title ?></h2><?php endif; ?>
        <?php if ($archive_desc !== ''): ?><p class="sp-archive-subtitle"><?= $archive_desc ?></p><?php endif; ?>
      </div>
      <?php if ($total > 0): ?>
      <div class="sp-archive-count">
        <span class="sp-archive-count-num"><?= (int)$total ?></span>
        <span class="sp-archive-count-lbl">Speaker</span>
      </div>
      <?php endif; ?>
    </div>
  </header>
  <?php endif; ?>

  <!-- Filter-Bar -->
  <form class="sp-filter-bar phinit-card" method="GET" action="<?= htmlspecialchars($archive_url, ENT_QUOTES, 'UTF-8') ?>" role="search">

    <div class="sp-filter-input phinit-field">
      <label for="sp-search">Name oder Thema</label>
      <input id="sp-search" class="phinit-input" type="search" name="search" placeholder="Name oder Thema suchen…"
             value="<?= htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="sp-filter-input phinit-field">
      <label for="sp-city">Ort / Stadt</label>
      <input id="sp-city" class="phinit-input" type="text" name="city" placeholder="Ort / Stadt…"
             value="<?= htmlspecialchars((string) ($filter_city ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <label class="phinit-field" for="sp-availability"><span>Verfügbarkeit</span>
    <select id="sp-availability" name="availability" class="sp-filter-select phinit-select">
      <option value="">Verfügbarkeit</option>
      <option value="available"<?= ($filter_availability ?? '') === 'available' ? ' selected' : '' ?>>Verfügbar</option>
      <option value="limited"<?= ($filter_availability ?? '') === 'limited'   ? ' selected' : '' ?>>Begrenzt</option>
      <option value="booked"<?= ($filter_availability ?? '') === 'booked'     ? ' selected' : '' ?>>Ausgebucht</option>
    </select></label>

    <label class="phinit-field" for="sp-format"><span>Format</span>
    <select id="sp-format" name="format" class="sp-filter-select phinit-select">
      <option value="">Format</option>
      <option value="keynote"<?=    ($filter_format ?? '') === 'keynote'    ? ' selected' : '' ?>>Keynote</option>
      <option value="workshop"<?=   ($filter_format ?? '') === 'workshop'   ? ' selected' : '' ?>>Workshop</option>
      <option value="panel"<?=      ($filter_format ?? '') === 'panel'      ? ' selected' : '' ?>>Panel</option>
      <option value="moderation"<?= ($filter_format ?? '') === 'moderation' ? ' selected' : '' ?>>Moderation</option>
      <option value="training"<?=   ($filter_format ?? '') === 'training'   ? ' selected' : '' ?>>Training</option>
      <option value="webinar"<?=    ($filter_format ?? '') === 'webinar'    ? ' selected' : '' ?>>Webinar</option>
    </select></label>

    <label class="phinit-field" for="sp-travel"><span>Reisebereitschaft</span>
    <select id="sp-travel" name="travel" class="sp-filter-select phinit-select">
      <option value="">Reisebereitschaft</option>
      <option value="local"<?=         ($filter_travel ?? '') === 'local'         ? ' selected' : '' ?>>Lokal</option>
      <option value="regional"<?=      ($filter_travel ?? '') === 'regional'      ? ' selected' : '' ?>>Regional</option>
      <option value="national"<?=      ($filter_travel ?? '') === 'national'      ? ' selected' : '' ?>>DACH</option>
      <option value="international"<?= ($filter_travel ?? '') === 'international' ? ' selected' : '' ?>>International</option>
      <option value="worldwide"<?=     ($filter_travel ?? '') === 'worldwide'     ? ' selected' : '' ?>>Weltweit</option>
    </select></label>

    <button type="submit" class="phinit-btn phinit-btn--primary sp-btn sp-btn-primary">Suchen</button>

    <?php if (!empty($search) || !empty($filter_city) || !empty($filter_availability) || !empty($filter_format) || !empty($filter_travel)): ?>
      <a href="<?= htmlspecialchars($archive_url, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary sp-btn sp-btn-ghost">Reset</a>
    <?php endif; ?>

  </form>

  <!-- Speaker-Grid -->
  <section class="sp-grid phinit-grid" aria-label="Speaker-Liste">
    <?php if (empty($speakers)): ?>
      <div class="sp-empty-state phinit-empty-state" role="status" aria-live="polite">
        <div class="sp-empty-icon">Keine Ergebnisse</div>
        <h3>Keine Speaker gefunden</h3>
        <p>Versuche es mit anderen Filterkriterien.</p>
        <?php if (!empty($search) || !empty($filter_city) || !empty($filter_availability)): ?>
          <a href="<?= htmlspecialchars($archive_url, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary sp-btn-ghost">Filter zurücksetzen</a>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <?php foreach ($speakers as $speaker): ?>
        <?php
        // Topics für diese Card vorladen (sofern nicht bereits an $speaker gehängt)
        $topics = [];
        if (!empty($speaker->_topics) && is_array($speaker->_topics)) {
            $topics = $speaker->_topics;
        }
        include __DIR__ . '/speaker-card.php';
        ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </section>

  <!-- Pagination -->
  <?php $pages_total = $pages ?? 1; $cur_page = $page ?? 1; ?>
  <?php if ($pages_total > 1): ?>
    <?php
    $pq = http_build_query(array_filter([
        'search'       => $search ?? '',
        'city'         => $filter_city ?? '',
        'availability' => $filter_availability ?? '',
        'format'       => $filter_format ?? '',
        'travel'       => $filter_travel ?? '',
    ]));
    $pq_sep = $pq ? '&' : '';
    ?>
    <nav class="sp-pagination" aria-label="Seitennavigation">
      <?php if ($cur_page > 1): ?>
          <a class="sp-page-btn phinit-btn phinit-btn--secondary"
            href="<?= htmlspecialchars($archive_url . '?' . $pq . $pq_sep . 'page=' . ($cur_page - 1), ENT_QUOTES, 'UTF-8') ?>">&larr; Zurück</a>
      <?php endif; ?>
      <span class="sp-page-info">Seite <?= $cur_page ?> von <?= $pages_total ?></span>
      <?php if ($cur_page < $pages_total): ?>
          <a class="sp-page-btn phinit-btn phinit-btn--secondary"
            href="<?= htmlspecialchars($archive_url . '?' . $pq . $pq_sep . 'page=' . ($cur_page + 1), ENT_QUOTES, 'UTF-8') ?>">Weiter &rarr;</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>

</main><!-- /.sp-archive -->
