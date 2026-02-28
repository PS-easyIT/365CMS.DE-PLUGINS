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

if (!isset($speakers, $settings)) {
    return;
}

/* ── CSS-Farb-Hilfsfunktionen ────────────────────────────────── */
if (!function_exists('sp_hex_mix')) {
    function sp_hex_mix(string $hex, float $frac, bool $to_white = true): string {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) { $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) { return '#' . $hex; }
        $target = $to_white ? 255 : 0;
        return sprintf('#%02x%02x%02x',
            max(0, min(255, (int)(hexdec(substr($hex,0,2)) + $frac * ($target - hexdec(substr($hex,0,2)))))),
            max(0, min(255, (int)(hexdec(substr($hex,2,2)) + $frac * ($target - hexdec(substr($hex,2,2)))))),
            max(0, min(255, (int)(hexdec(substr($hex,4,2)) + $frac * ($target - hexdec(substr($hex,4,2))))))
        );
    }
}

/* ── CSS-Variablen aus Einstellungen ──────────────────────────── */
$primary_raw  = $settings['design_primary_color']       ?? '#8b5cf6';
$accent_raw   = $settings['design_accent_color']        ?? '#7c3aed';
$primary      = htmlspecialchars($primary_raw);
$accent       = htmlspecialchars($accent_raw);
$hdr_from     = htmlspecialchars($settings['archive_header_bg_from']     ?? '#6d28d9');
$hdr_to       = htmlspecialchars($settings['archive_header_bg_to']       ?? '#a855f7');
$hdr_title    = htmlspecialchars($settings['archive_header_title_color'] ?? '#ffffff');
$card_bg      = htmlspecialchars($settings['design_card_bg']             ?? '#faf5ff');
$radius       = (int)($settings['design_border_radius'] ?? 12) . 'px';

// Abgeleitete Farben – automatisch aus Primär/Akzent berechnet
$primary_h     = htmlspecialchars(sp_hex_mix($primary_raw, 0.18, false)); // dunkler Hover
$secondary     = htmlspecialchars(sp_hex_mix($accent_raw,  0.20, false)); // etwas dunkler als Akzent
$card_top_bg   = htmlspecialchars(sp_hex_mix($accent_raw,  0.76, true));  // sehr heller Tint
$border_color  = htmlspecialchars(sp_hex_mix($accent_raw,  0.55, true));  // mittlerer Tint
$border_l_color = htmlspecialchars(sp_hex_mix($accent_raw, 0.79, true));  // heller Tint

// Rohwerte für Sichtbarkeits-Prüfung (vor htmlspecialchars)
$archive_title_raw = trim($settings['archive_title']       ?? '');
$archive_desc_raw  = trim($settings['archive_description'] ?? '');
$has_header        = $archive_title_raw !== '' && $archive_desc_raw !== '';
$archive_title     = htmlspecialchars($archive_title_raw);
$archive_desc      = htmlspecialchars($archive_desc_raw);
$archive_icon  = htmlspecialchars($settings['archive_header_icon']  ?? '🎤');
$cta_label     = htmlspecialchars($settings['design_cta_label']     ?? 'Profil ansehen');

// Grid immer 3 Spalten – unabhängig von der Admin-Einstellung
// (responsive Breakpoints sind im CSS definiert)
$grid_css = 'repeat(3, 1fr)';

/* ── Archive-URL ──────────────────────────────────────────────── */
$archive_url = (defined('SITE_URL') ? rtrim(SITE_URL, '/') : '') . '/speakers/';
?>
<style>
:root {
  --sp-primary:       <?= $primary ?>;
  --sp-primary-h:     <?= $primary_h ?>;
  --sp-accent:        <?= $accent ?>;
  --sp-secondary:     <?= $secondary ?>;
  --sp-hdr-from:      <?= $hdr_from ?>;
  --sp-hdr-to:        <?= $hdr_to ?>;
  --sp-hdr-title:     <?= $hdr_title ?>;
  --sp-card-bg:       <?= $card_bg ?>;
  --sp-card-top-bg:   <?= $card_top_bg ?>;
  --sp-border:        <?= $border_color ?>;
  --sp-border-l:      <?= $border_l_color ?>;
  --sp-radius:        <?= $radius ?>;
}
.sp-grid { grid-template-columns: <?= $grid_css ?>; }
</style>

<div class="sp-archive">

  <?php if ($has_header): ?>
  <!-- Gradient-Header – nur wenn Titel UND Beschreibung gesetzt -->
  <div class="sp-archive-header">
    <div class="sp-archive-header-inner">
      <span class="sp-archive-header-icon"><?= $archive_icon ?></span>
      <div>
        <?php if ($archive_title !== ''): ?><h1 class="sp-archive-header-title"><?= $archive_title ?></h1><?php endif; ?>
        <?php if ($archive_desc !== ''): ?><p class="sp-archive-subtitle"><?= $archive_desc ?></p><?php endif; ?>
      </div>
      <?php if ($total > 0): ?>
      <div class="sp-archive-count">
        <span class="sp-archive-count-num"><?= (int)$total ?></span>
        <span class="sp-archive-count-lbl">Speaker</span>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Filter-Bar -->
  <form class="sp-filter-bar" method="GET" action="<?= $archive_url ?>">

    <div class="sp-filter-input">
      <span class="sp-filter-icon">🔍</span>
      <input type="search" name="search" placeholder="Name oder Thema suchen…"
             value="<?= htmlspecialchars($search ?? '') ?>">
    </div>

    <div class="sp-filter-input">
      <span class="sp-filter-icon">📍</span>
      <input type="text" name="city" placeholder="Ort / Stadt…"
             value="<?= htmlspecialchars($filter_city ?? '') ?>">
    </div>

    <select name="availability" class="sp-filter-select">
      <option value="">Verfügbarkeit</option>
      <option value="available"<?= ($filter_availability ?? '') === 'available' ? ' selected' : '' ?>>Verfügbar</option>
      <option value="limited"<?= ($filter_availability ?? '') === 'limited'   ? ' selected' : '' ?>>Begrenzt</option>
      <option value="booked"<?= ($filter_availability ?? '') === 'booked'     ? ' selected' : '' ?>>Ausgebucht</option>
    </select>

    <select name="format" class="sp-filter-select">
      <option value="">Format</option>
      <option value="keynote"<?=    ($filter_format ?? '') === 'keynote'    ? ' selected' : '' ?>>🎤 Keynote</option>
      <option value="workshop"<?=   ($filter_format ?? '') === 'workshop'   ? ' selected' : '' ?>>🛠️ Workshop</option>
      <option value="panel"<?=      ($filter_format ?? '') === 'panel'      ? ' selected' : '' ?>>💬 Panel</option>
      <option value="moderation"<?= ($filter_format ?? '') === 'moderation' ? ' selected' : '' ?>>🎙️ Moderation</option>
      <option value="training"<?=   ($filter_format ?? '') === 'training'   ? ' selected' : '' ?>>📚 Training</option>
      <option value="webinar"<?=    ($filter_format ?? '') === 'webinar'    ? ' selected' : '' ?>>💻 Webinar</option>
    </select>

    <select name="travel" class="sp-filter-select">
      <option value="">Reisebereitschaft</option>
      <option value="local"<?=         ($filter_travel ?? '') === 'local'         ? ' selected' : '' ?>>📍 Lokal</option>
      <option value="regional"<?=      ($filter_travel ?? '') === 'regional'      ? ' selected' : '' ?>>🗺️ Regional</option>
      <option value="national"<?=      ($filter_travel ?? '') === 'national'      ? ' selected' : '' ?>>🇩🇪 DACH</option>
      <option value="international"<?= ($filter_travel ?? '') === 'international' ? ' selected' : '' ?>>🌍 International</option>
      <option value="worldwide"<?=     ($filter_travel ?? '') === 'worldwide'     ? ' selected' : '' ?>>🌐 Weltweit</option>
    </select>

    <button type="submit" class="sp-btn sp-btn-primary">🔍 Suchen</button>

    <?php if (!empty($search) || !empty($filter_city) || !empty($filter_availability) || !empty($filter_format) || !empty($filter_travel)): ?>
      <a href="<?= $archive_url ?>" class="sp-btn sp-btn-ghost">✕ Reset</a>
    <?php endif; ?>

  </form>

  <!-- Speaker-Grid -->
  <div class="sp-grid">
    <?php if (empty($speakers)): ?>
      <div class="sp-empty-state" style="grid-column:1/-1">
        <div class="sp-empty-icon">🔍</div>
        <h3>Keine Speaker gefunden</h3>
        <p>Versuche es mit anderen Filterkriterien.</p>
        <?php if (!empty($search) || !empty($filter_city) || !empty($filter_availability)): ?>
          <a href="<?= $archive_url ?>" class="sp-btn-ghost">✕ Filter zurücksetzen</a>
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
  </div>

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
    <div class="sp-pagination">
      <?php if ($cur_page > 1): ?>
        <a class="sp-page-btn"
           href="<?= $archive_url ?>?<?= $pq ?><?= $pq_sep ?>page=<?= $cur_page - 1 ?>">&larr; Zurück</a>
      <?php endif; ?>
      <span class="sp-page-info">Seite <?= $cur_page ?> von <?= $pages_total ?></span>
      <?php if ($cur_page < $pages_total): ?>
        <a class="sp-page-btn"
           href="<?= $archive_url ?>?<?= $pq ?><?= $pq_sep ?>page=<?= $cur_page + 1 ?>">Weiter &rarr;</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</div><!-- /.sp-archive -->
