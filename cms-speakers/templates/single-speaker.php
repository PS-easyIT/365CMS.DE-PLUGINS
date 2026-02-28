<?php
/**
 * Single Speaker Detail – Struktur nach single-expert.php
 *
 * Verfügbare Variablen (via extract() aus post-type::single_page_by_slug()):
 *   $speaker  – object
 *   $topics   – array (aus get_topics())
 *   $events   – array (aus get_events())
 *   $settings – array
 *
 * @package CMS_Speakers
 */

if (!isset($speaker)) {
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

/* ── CSS-Variablen aus Settings ─────────────────────────────── */
$primary_raw  = $settings['design_primary_color']       ?? '#8b5cf6';
$accent_raw   = $settings['design_accent_color']        ?? '#7c3aed';
$primary      = htmlspecialchars($primary_raw);
$accent       = htmlspecialchars($accent_raw);
$card_bg      = htmlspecialchars($settings['design_card_bg']              ?? '#faf5ff');
$hdr_from     = htmlspecialchars($settings['detail_header_bg_from']       ?? '#f9f6ff');
$hdr_to       = htmlspecialchars($settings['detail_header_bg_to']         ?? '#f1ecfd');
$hdr_title    = htmlspecialchars($settings['detail_header_title_color']   ?? '#1e293b');

// Abgeleitete Farben
$primary_h      = htmlspecialchars(sp_hex_mix($primary_raw, 0.18, false));
$secondary      = htmlspecialchars(sp_hex_mix($accent_raw,  0.20, false));
$card_top_bg    = htmlspecialchars(sp_hex_mix($accent_raw,  0.76, true));
$border_color   = htmlspecialchars(sp_hex_mix($accent_raw,  0.55, true));
$border_l_color = htmlspecialchars(sp_hex_mix($accent_raw,  0.79, true));

/* ── Basis-Daten ─────────────────────────────────────────────── */
$s          = $speaker;
$id         = (int)$s->id;
$first      = $s->first_name ?? '';
$last       = $s->last_name  ?? '';
$full_name  = htmlspecialchars(trim("$first $last"));
$position   = htmlspecialchars($s->position ?? '');
$company    = htmlspecialchars($s->company_linked_name ?? $s->company ?? '');
$city       = htmlspecialchars($s->location_city ?? '');
$country    = htmlspecialchars($s->country ?? '');
$photo      = $s->photo_url ?? '';
$bio        = $s->bio ?? '';
$linkedin   = $s->linkedin   ?? '';
$xing       = $s->xing       ?? '';
$twitter    = $s->twitter    ?? '';
$instagram  = $s->instagram  ?? '';
$youtube    = $s->youtube    ?? '';
$website    = $s->website    ?? '';
$github     = $s->github     ?? '';
$gitlab     = $s->gitlab     ?? '';
$email      = $s->email      ?? '';
$phone      = $s->phone      ?? '';
$is_featured  = !empty($s->is_featured);
$is_verified  = !empty($s->is_verified);
$avail        = $s->availability  ?? 'available';
$fee_min      = $s->speaking_fee_min ?? '';
$fee_max      = $s->speaking_fee_max ?? '';
$travel       = $s->travel_radius   ?? 'national';
$languages_raw = $s->languages ?? '';
if (is_string($languages_raw) && $languages_raw) {
    $_lang_dec = json_decode($languages_raw, true);
    if (is_array($_lang_dec)) {
        $languages = implode(', ', array_filter(array_map('trim', $_lang_dec)));
    } else {
        // Komma- oder Semikolon-getrennte Werte bereinigen
        $languages = implode(', ', array_filter(array_map('trim', preg_split('/[,;]+/', $languages_raw))));
    }
} else {
    $languages = '';
}
$speaking_style   = $s->speaking_style   ?? '';
$target_audience  = $s->target_audience  ?? '';
$awards           = $s->awards           ?? '';
$max_audience     = $s->max_audience_size ?? '';
$gender           = $s->gender           ?? '';
$acad_title       = $s->title            ?? '';

/* ── URL ─────────────────────────────────────────────────────── */
$base_url    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$archive_url = $base_url . '/speakers/';
$slug        = CMS_Speakers_Database::generate_slug($s);
$speaker_url = $base_url . '/speakers/' . $slug;

/* ── Formate ─────────────────────────────────────────────────── */
$formats_raw = $s->formats ?? '';
$formats = [];
if (is_string($formats_raw) && $formats_raw) {
    $d = json_decode($formats_raw, true);
    $formats = is_array($d) ? $d : array_filter(array_map('trim', explode(',', $formats_raw)));
}
$fmt_labels = [
    'keynote'    => '🎤 Keynote',
    'workshop'   => '🛠️ Workshop',
    'panel'      => '💬 Panel',
    'moderation' => '🎙️ Moderation',
    'training'   => '📚 Training',
    'consulting' => '🤝 Beratung',
    'interview'  => '🎥 Interview',
    'webinar'    => '💻 Webinar',
];
/* ── Skills ───────────────────────────────────────────────────────────────── */
$_sk_raw = $s->skills ?? '';
$_skills = is_string($_sk_raw) && $_sk_raw ? (json_decode($_sk_raw, true) ?: []) : [];
$_skill_labels = CMS_Speakers_Meta_Boxes::get_skill_labels();

/* ── Recognitions ────────────────────────────────────────────────────── */
$_rec_raw = $s->recognitions ?? '';
$_recognitions = is_string($_rec_raw) && $_rec_raw ? (json_decode($_rec_raw, true) ?: []) : [];
$_rec_group_labels = [
    'community_programmes' => '🤝 Community Programme',
    'speaker_awards'       => '🏆 Speaker Awards',
    'rankings'             => '📊 Rankings & Listen',
    'academic'             => '🎓 Akademische Auszeichnungen',
    'other'                => '📌 Weitere Auszeichnungen',
];
/* ── Initialen / Avatar ──────────────────────────────────────── */
$initials = mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
$pcolors  = [['#8b5cf6','#a855f7'],['#7c3aed','#8b5cf6'],['#a855f7','#c084fc'],['#6d28d9','#8b5cf6'],['#9333ea','#a855f7']];
$cp       = $pcolors[abs(crc32($full_name)) % count($pcolors)];
$av_grad  = "linear-gradient(135deg,{$cp[0]},{$cp[1]})";

/* ── Availability / Travel Labels ───────────────────────────── */
$avail_labels = ['available' => 'Verfügbar', 'limited' => 'Begrenzt', 'booked' => 'Ausgebucht'];
$avail_label  = $avail_labels[$avail] ?? 'Verfügbar';
$avail_map    = ['available' => '#d1fae5', 'limited' => '#fef3c7', 'booked' => '#fee2e2'];
$avail_color  = $avail_map[$avail] ?? '#ede9fe';
$travel_labels = ['local'=>'📍 Lokal','regional'=>'🗺️ Regional','national'=>'🇩🇪 DACH','international'=>'🌍 International','worldwide'=>'🌐 Weltweit'];
$travel_label  = $travel_labels[$travel] ?? $travel;

/* ── Presence-Type Labels ────────────────────────────────────── */
$presence_labels = ['presence' => '🏛️ Präsenz', 'online' => '💻 Online', 'hybrid' => '🔀 Hybrid'];

/* ── Topics + Events ─────────────────────────────────────────── */
$topics = $topics ?? [];
$events = $events ?? [];
?>
<?php
$badge_avail_bg       = htmlspecialchars($settings['design_badge_avail_bg']       ?? '#d1fae5');
$badge_avail_color    = htmlspecialchars($settings['design_badge_avail_color']    ?? '#065f46');
$badge_limited_bg     = htmlspecialchars($settings['design_badge_limited_bg']     ?? '#fef3c7');
$badge_limited_color  = htmlspecialchars($settings['design_badge_limited_color']  ?? '#92400e');
$badge_booked_bg      = htmlspecialchars($settings['design_badge_booked_bg']      ?? '#fee2e2');
$badge_booked_color   = htmlspecialchars($settings['design_badge_booked_color']   ?? '#991b1b');
$badge_mvp_bg         = htmlspecialchars($settings['design_badge_mvp_bg']         ?? 'rgba(251,191,36,0.2)');
$badge_mvp_color      = htmlspecialchars($settings['design_badge_mvp_color']      ?? '#fbbf24');
$badge_verified_bg    = htmlspecialchars($settings['design_badge_verified_bg']    ?? '#ede9fe');
$badge_verified_color = htmlspecialchars($settings['design_badge_verified_color'] ?? '#5b21b6');
?>
<style>
:root {
  --sp-primary:     <?= $primary ?>;
  --sp-primary-h:   <?= $primary_h ?>;
  --sp-accent:      <?= $accent ?>;
  --sp-secondary:   <?= $secondary ?>;
  --sp-hdr-from:    <?= $hdr_from ?>;
  --sp-hdr-to:      <?= $hdr_to ?>;
  --sp-hdr-title:   <?= $hdr_title ?>;
  --sp-card-bg:     <?= $card_bg ?>;
  --sp-card-top-bg: <?= $card_top_bg ?>;
  --sp-border:      <?= $border_color ?>;
  --sp-border-l:    <?= $border_l_color ?>;
  --sp-radius:      <?= (int)($settings['design_border_radius'] ?? 12) ?>px;
  /* Badge-Farben (konfigurierbar) */
  --sp-avail-available-bg:    <?= $badge_avail_bg ?>;
  --sp-avail-available-color: <?= $badge_avail_color ?>;
  --sp-avail-limited-bg:      <?= $badge_limited_bg ?>;
  --sp-avail-limited-color:   <?= $badge_limited_color ?>;
  --sp-avail-booked-bg:       <?= $badge_booked_bg ?>;
  --sp-avail-booked-color:    <?= $badge_booked_color ?>;
  --sp-badge-mvp-bg:          <?= $badge_mvp_bg ?>;
  --sp-badge-mvp-color:       <?= $badge_mvp_color ?>;
  --sp-badge-verified-bg:     <?= $badge_verified_bg ?>;
  --sp-badge-verified-color:  <?= $badge_verified_color ?>;
}
</style>

<div class="sp-single-v2">

  <nav class="sp-breadcrumb">
    <a href="<?= $archive_url ?>">← Speaker</a>
    <span class="sp-breadcrumb__sep">/</span>
    <span class="sp-breadcrumb__cur"><?= $full_name ?></span>
  </nav>

  <header class="sp-hero-v2">
    <div class="sp-hero-v2__inner">
      <div class="sp-hero-v2__badges">
        <span class="sp-hero-v2__badge sp-hero-v2__badge--avail-<?= htmlspecialchars($avail) ?>"><?= htmlspecialchars($avail_label) ?></span>
        <?php if ($is_verified): ?><span class="sp-hero-v2__badge sp-hero-v2__badge--verified">✔ Verifiziert</span><?php endif; ?>
        <?php if (!empty($settings['design_show_mvp_badge'] ?? '1') && $is_featured): ?>
          <span class="sp-hero-v2__badge sp-hero-v2__badge--mvp">⭐ MVP</span>
        <?php endif; ?>
      </div>
      <?php if ($photo): ?>
        <div class="sp-hero-v2__av"><img src="<?= htmlspecialchars($photo) ?>" alt="<?= $full_name ?>"></div>
      <?php else: ?>
        <div class="sp-hero-v2__av" style="background:<?= $av_grad ?>;"><?= htmlspecialchars($initials ?: '🎤') ?></div>
      <?php endif; ?>
      <div class="sp-hero-v2__meta">
        <div class="sp-hero-v2__name-row">
          <h1 class="sp-hero-v2__name"><?= $full_name ?></h1>
          <?php if (!empty($topics)): ?>
            <div class="sp-hero-v2__spec-pills">
              <?php foreach ((array)$topics as $t): $tname = is_object($t) ? ($t->topic_name ?? '') : (string)$t; if (!$tname) continue; ?><span class="sp-hero-v2__spec-pill"><?= htmlspecialchars($tname) ?></span><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($events)): ?>
            <span class="sp-hero-v2__ev-count" title="<?= count($events) ?> zugewiesene Events">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
              <?= count($events) ?>
            </span>
          <?php endif; ?>
        </div>
        <?php if ($position): ?><p class="sp-hero-v2__pos"><?= $position ?></p><?php endif; ?>
        <?php if ($company): ?>
          <p class="sp-hero-v2__co">🏢
            <?php if (!empty($s->company_id)): ?>
              <a href="<?= $base_url ?>/companies/<?= (int)$s->company_id ?>"><?= $company ?></a>
            <?php elseif ($website): ?>
              <a href="<?= htmlspecialchars($website) ?>" target="_blank" rel="noopener"><?= $company ?></a>
            <?php else: echo $company; endif; ?>
          </p>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- ── Bridge Cards (overlap hero) ──────────────────────────── -->
  <div class="sp-bridge">
    <?php if ($bio): ?>
      <div class="sp-bridge__about">
        <h2 class="sp-bridge__title">Über <?= htmlspecialchars($first ?: 'den Speaker') ?></h2>
        <div class="sp-bridge__text"><?= $bio ?></div>
      </div>
    <?php else: ?>
      <div class="sp-bridge__about">
        <h2 class="sp-bridge__title">Über <?= htmlspecialchars($first ?: 'den Speaker') ?></h2>
        <p class="sp-bridge__text" style="color:#94a3b8;font-style:italic;">Noch keine Beschreibung hinterlegt.</p>
      </div>
    <?php endif; ?>

    <div class="sp-bridge__contact">
      <h2 class="sp-bridge__title">Kontakt & Social</h2>
      <div class="sp-bridge__contact-body">
        
        <!-- Reihe 1: Kontakt / Buchung -->
        <div class="sp-bridge__row">
          <a href="<?= $base_url ?>/contact?speaker=<?= (int)$s->id ?>" class="sp-btn-v2 sp-btn-v2--sm sp-btn-v2--block">Kontakt / Buchung</a>
        </div>

        <!-- Reihe 2: Website · E-Mail · Telefon -->
        <div class="sp-bridge__row sp-bridge__links">
          <?php if (!empty($website)): ?>
            <a href="<?= htmlspecialchars($website) ?>" target="_blank" rel="noopener" class="sp-bridge__link" title="Website ansehen">Website</a>
          <?php else: ?>
            <span class="sp-bridge__link sp-bridge__link--empty">Website</span>
          <?php endif; ?>
          
          <?php if (!empty($email)): ?>
            <a href="mailto:<?= htmlspecialchars($email) ?>" class="sp-bridge__link" title="E-Mail schreiben">E-Mail</a>
          <?php else: ?>
            <span class="sp-bridge__link sp-bridge__link--empty">E-Mail</span>
          <?php endif; ?>
          
          <?php if (!empty($phone)): ?>
            <a href="tel:<?= htmlspecialchars($phone) ?>" class="sp-bridge__link" title="Anrufen">Telefon</a>
          <?php else: ?>
            <span class="sp-bridge__link sp-bridge__link--empty">Telefon</span>
          <?php endif; ?>
        </div>

        <!-- Reihe 3: Social Media Icons -->
        <div class="sp-bridge__row sp-bridge__socials">
          <?php
          $speaker_social = [
            'linkedin' => $linkedin ?? '',
            'xing'     => $xing ?? '',
            'twitter'  => $twitter ? "https://twitter.com/" . ltrim($twitter, '@') : '',
            'github'   => $github ?? '',
            'gitlab'   => $gitlab ?? '',
            'youtube'  => $youtube ?? '',
          ];
          $social_icons = [
            'linkedin' => ['label' => 'LinkedIn', 'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S.02 4.88.02 3.5C.02 2.12 1.13 1 2.5 1s2.48 1.12 2.48 2.5zM.02 8.5H5V24H.02V8.5zm7.97 0h4.8v2.1h.07C13.7 9 15.44 8 17.6 8c5.2 0 6.16 3.43 6.16 7.88V24H19v-7.2c0-1.72-.03-3.93-2.4-3.93-2.4 0-2.78 1.87-2.78 3.81V24H8z"/></svg>'],
            'xing'     => ['label' => 'XING',     'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M18.188 0c-.517 0-.741.325-.927.66 0 0-7.455 13.224-7.702 13.657.015.024 4.919 9.023 4.919 9.023.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916c-.004-.006-.004-.016 0-.022L22.139.756c.095-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zM3.648 4.74c-.211 0-.385.074-.473.216-.09.149-.078.339.02.531l2.34 4.05c.004.01.004.016 0 .021L3.17 13.694c-.09.191-.097.383-.006.535.09.142.25.22.46.22h3.454c.521 0 .739-.322.928-.66l2.44-4.237c-.016-.025-2.395-4.14-2.395-4.14-.164-.308-.44-.672-.962-.672H3.648z"/></svg>'],
            'twitter'  => ['label' => 'X',        'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="15" height="15"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'],
            'youtube'  => ['label' => 'YouTube',  'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg>'],
            'github'   => ['label' => 'GitHub',   'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>'],
            'gitlab'   => ['label' => 'GitLab',   'svg' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16"><path d="M23.955 13.587l-1.342-4.135-2.664-8.189a.455.455 0 0 0-.867 0L16.418 9.45H7.582L4.918 1.263a.455.455 0 0 0-.867 0L1.386 9.452.044 13.587a.924.924 0 0 0 .331 1.023L12 23.054l11.625-8.443a.92.92 0 0 0 .33-1.024"/></svg>'],
          ];
          foreach ($social_icons as $sn => $icfg):
            if (!empty($speaker_social[$sn])): ?>
              <a href="<?= htmlspecialchars($speaker_social[$sn]) ?>" target="_blank" rel="noopener" class="sp-si" title="<?= $icfg['label'] ?>"><?= $icfg['svg'] ?></a>
            <?php else: ?>
              <span class="sp-si sp-si--empty" title="<?= $icfg['label'] ?>"><?= $icfg['svg'] ?></span>
            <?php endif;
          endforeach; ?>
        </div>

      </div>
    </div>
  </div>

  <div class="ex-body">
    <main class="ex-main">

      <?php if ($speaking_style): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">Vortragsstil</h2>
          <p style="line-height:1.7;color:#475569;font-size:.92rem;"><?= nl2br(htmlspecialchars($speaking_style)) ?></p>
        </div>
      <?php endif; ?>

      <?php if (!empty($_skills)):
        // Alphabetisch sortieren
        $__skills_sorted = $_skills;
        usort($__skills_sorted, fn($a, $b) => strcasecmp($_skill_labels[$a] ?? $a, $_skill_labels[$b] ?? $b));
      ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">Skills & Technologien</h2>
          <div class="sp-skills-grid">
            <?php foreach ($__skills_sorted as $sk): ?>
              <div class="sp-skill-item"><span class="sp-skill-item__name"><?= htmlspecialchars($_skill_labels[$sk] ?? $sk) ?></span></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($formats)):
        // Alphabetisch sortieren
        $__formats_sorted = (array)$formats;
        usort($__formats_sorted, fn($a, $b) => strcasecmp($fmt_labels[$a] ?? $a, $fmt_labels[$b] ?? $b));
      ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">Vortragsformate</h2>
          <div class="sp-skills-grid">
            <?php foreach ($__formats_sorted as $f): ?>
              <div class="sp-skill-item sp-skill-item--fmt"><span class="sp-skill-item__name"><?= htmlspecialchars($fmt_labels[$f] ?? $f) ?></span></div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($target_audience): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">Zielgruppe</h2>
          <p style="line-height:1.7;color:#475569;font-size:.92rem;"><?= nl2br(htmlspecialchars($target_audience)) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($awards || !empty($_recognitions)): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">Auszeichnungen & Rankings</h2>
          <?php if ($awards): ?>
            <p style="line-height:1.7;color:#475569;font-size:.92rem;margin-bottom:1rem;"><?= nl2br(htmlspecialchars($awards)) ?></p>
          <?php endif; ?>
          <?php foreach ($_recognitions as $grp => $items):
            if (empty($items) || !is_array($items)) continue; ?>
            <h4 class="ex-sub-heading"><?= htmlspecialchars($_rec_group_labels[$grp] ?? $grp) ?></h4>
            <div class="ex-pills ex-pills--mb">
              <?php foreach ($items as $item): ?><span class="ex-pill"><?= htmlspecialchars($item) ?></span><?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($events)): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">Events & Auftritte <span class="ex-count-badge"><?= count($events) ?></span></h2>
          <div class="sp-ev-grid-v2">
            <?php foreach ((array)$events as $ev):
              if (!is_object($ev)) continue;
              $ev_title    = htmlspecialchars($ev->event_title    ?? '');
              $ev_date_raw = $ev->event_date   ?? '';
              $ev_location = htmlspecialchars($ev->event_location ?? '');
              $ev_type     = htmlspecialchars($ev->event_type     ?? '');
              $ev_presence = $ev->presence_type ?? 'presence';
              $ev_ts       = $ev_date_raw ? strtotime($ev_date_raw) : 0;
              $ev_date_fmt = $ev_ts ? date('d.m.Y', $ev_ts) : '';
            ?>
              <div class="sp-ev-v2">
                <?php if ($ev_date_fmt): ?><div class="sp-ev-v2__date"><?= $ev_date_fmt ?></div><?php endif; ?>
                <div class="sp-ev-v2__title"><?= $ev_title ?: 'Event' ?></div>
                <div class="sp-ev-v2__meta">
                  <?php if ($ev_location): ?><span>📍 <?= $ev_location ?></span><?php endif; ?>
                  <?php if ($ev_type && $ev_location): ?><span>·</span><?php endif; ?>
                  <?php if ($ev_type): ?><span><?= $ev_type ?></span><?php endif; ?>
                </div>
                <?php if ($ev_presence !== 'presence'): ?>
                  <span class="sp-ev-v2__badge sp-ev-v2__badge--online"><?= htmlspecialchars($presence_labels[$ev_presence] ?? $ev_presence) ?></span>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </main>

    <aside class="ex-aside">

      <?php
      $sp_facts = [];
      if ($company):   $sp_facts[] = ['Unternehmen',$company]; endif;
      if ($city||$country): $sp_facts[] = ['Standort',trim("$city".($city&&$country?', ':'')."$country")]; endif;
      if ($travel):    $sp_facts[] = ['Reichweite',htmlspecialchars($travel_label)]; endif;
      if ($fee_min||$fee_max): $sp_facts[] = ['Honorar',($fee_min?number_format((float)$fee_min,0,',','.'):'').($fee_min&&$fee_max?'–':'').($fee_max?number_format((float)$fee_max,0,',','.').' €':'')]; endif;
      if ($languages): $sp_facts[] = ['Sprachen',htmlspecialchars($languages)]; endif;
      if ($max_audience): $sp_facts[] = ['Max. Audience',(int)$max_audience.' Pers.']; endif;
      if ($sp_facts): ?>
        <div class="ex-sc">
          <h3 class="ex-sc__title">Details</h3>
          <div class="ex-info-rows">
            <?php foreach ($sp_facts as [$lbl,$val]): ?>
              <div class="ex-info-row">
                <span class="ex-info-row__lbl"><?= $lbl ?></span>
                <span class="ex-info-row__val"><?= $val ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </aside>
  </div>

  <?php if (!(int)($speaker->user_id ?? 0)): ?>
  <div class="sp-claim-banner">
    <div class="sp-claim-banner__text">
      <strong>Dieses Profil wurde von der Redaktion angelegt.</strong>
      Gehört es Ihnen? Registrieren Sie sich kostenlos und übernehmen Sie die Verwaltung Ihres Speaker-Profils.
    </div>
    <a href="<?= rtrim(SITE_URL, '/') ?>/register" class="sp-claim-banner__btn">Jetzt registrieren &amp; Profil beanspruchen →</a>
  </div>
  <?php endif; ?>
</div>
<script>
(function(){
  function checkBioOverflow(){
    document.querySelectorAll('.sp-bridge__text').forEach(function(el){
      el.classList.toggle('is-overflow', el.scrollHeight > el.clientHeight);
    });
  }
  checkBioOverflow();
  window.addEventListener('resize', checkBioOverflow);
})();
</script>