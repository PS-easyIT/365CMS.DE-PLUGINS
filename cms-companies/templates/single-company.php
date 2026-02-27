<?php
/**
 * Template: Single Company (Detail View)
 *
 * @package CMS_Companies
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$company = $company ?? null;
$experts = $experts  ?? [];

if (!$company) {
    echo '<div class="co-single-error">Unternehmen nicht gefunden.</div>';
    return;
}

$sec = CMS\Security::instance();

// Partner-Status
$is_sponsor     = (bool)($company->is_sponsor     ?? false);
$is_top_partner = (bool)($company->is_top_partner ?? false);
$is_partner     = (bool)($company->is_partner     ?? false);

// Avatar-Initials + Gradient (deterministisch)
$name_parts = preg_split('/\s+/', trim($company->name));
$initials   = mb_strtoupper(mb_substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? mb_substr($name_parts[1], 0, 1) : ''));
$palettes   = [
    ['#0891b2','#0284c7'], ['#7c3aed','#a855f7'], ['#059669','#34d399'],
    ['#d97706','#f59e0b'], ['#1d4ed8','#3b82f6'],
];
$cp         = $palettes[abs(crc32($company->name)) % count($palettes)];
$avatarBg   = "linear-gradient(135deg,{$cp[0]},{$cp[1]})";

// Header-Design-Einstellungen aus DB
$settings           = CMS_Companies_Database::instance()->get_settings();
$headerBgFrom       = $settings['design_detail_header_bg']    ?? '#f8fafc';
$headerTitleColor   = $settings['design_detail_header_color'] ?? '#1e293b';
$partnerSponsorColor= $settings['design_sponsor_color']       ?? '#7c3aed';
$partnerTopColor    = $settings['design_top_partner_color']   ?? '#d97706';
$partnerColor       = $settings['design_partner_color']       ?? '#9ca3af';
$base_url           = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
?>
<style>
:root {
  --co-primary:          <?= htmlspecialchars($settings['design_primary_color'] ?? '#0891b2') ?>;
  --co-primary-d:        <?= htmlspecialchars($settings['design_accent_color']  ?? '#0284c7') ?>;
  --co-radius:           <?= (int)($settings['design_border_radius'] ?? 14) ?>px;
  --co-shadow:           0 1px 3px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.04);
  --co-shadow-h:         0 4px 20px rgba(0,0,0,.10);
  --co-detail-hdr-bg:    <?= htmlspecialchars($headerBgFrom) ?>;
  --co-detail-hdr-color: <?= htmlspecialchars($headerTitleColor) ?>;
}
</style>

<div class="co-single-v2">

  <nav class="co-breadcrumb">
    <a href="<?= $base_url ?>/companies">← Unternehmen</a>
    <span class="co-breadcrumb__sep">/</span>
    <span class="co-breadcrumb__cur"><?= $sec->escape(mb_strimwidth($company->name ?? '', 0, 60, '…')) ?></span>
  </nav>

  <header class="co-hero-v2">
    <?php if ($is_sponsor): ?>
      <span class="co-hero-v2__ribbon" style="--co-ribbon-bg:<?= htmlspecialchars($partnerSponsorColor) ?>;">★ Sponsor</span>
    <?php elseif ($is_top_partner): ?>
      <span class="co-hero-v2__ribbon" style="--co-ribbon-bg:<?= htmlspecialchars($partnerTopColor) ?>;">◆ Top-Partner</span>
    <?php elseif ($is_partner): ?>
      <span class="co-hero-v2__ribbon" style="--co-ribbon-bg:<?= htmlspecialchars($partnerColor) ?>;">● Partner</span>
    <?php endif; ?>
    <div class="co-hero-v2__inner">
      <?php if (!empty($company->logo_url)): ?>
        <div class="co-hero-v2__avatar"><img src="<?= $sec->escape($company->logo_url) ?>" alt="<?= $sec->escape($company->name) ?>"></div>
      <?php else: ?>
        <div class="co-hero-v2__avatar" style="background:<?= $avatarBg ?>;"><?= $sec->escape($initials) ?></div>
      <?php endif; ?>
      <div class="co-hero-v2__meta">
        <h1 class="co-hero-v2__title"><?= $sec->escape($company->name) ?></h1>
        <?php if (!empty($company->industry)): ?>
          <p class="co-hero-v2__sub"><?= $sec->escape($company->industry) ?></p>
        <?php endif; ?>
        <div class="co-hero-v2__chips">
          <?php if (!empty($company->location_city)): ?>
            <span class="co-hero-v2__chip">📍 <?= $sec->escape($company->location_city) ?><?= !empty($company->location_country) && $company->location_country !== 'Deutschland' ? ', ' . $sec->escape($company->location_country) : '' ?></span>
          <?php endif; ?>
          <?php if (!empty($company->employee_count)): ?>
            <span class="co-hero-v2__chip">👥 <?= number_format((int)$company->employee_count, 0, ',', '.') ?> Mitarbeiter</span>
          <?php endif; ?>
          <?php if (!empty($company->founded_year)): ?>
            <span class="co-hero-v2__chip">📅 Seit <?= (int)$company->founded_year ?></span>
          <?php endif; ?>
          <?php if (!empty($company->website)): ?>
            <span class="co-hero-v2__chip">🌐 <?= htmlspecialchars(parse_url($company->website, PHP_URL_HOST) ?: $company->website) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <div class="co-body-v2">
    <main class="co-main-v2">

      <?php if (!empty($company->description) && trim($company->description) !== ''): ?>
        <div class="co-sec-v2">
          <h2 class="co-sec-v2__title">🏢 Über das Unternehmen</h2>
          <div class="co-wysiwyg-content">
            <?= $company->description ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($experts)): ?>
        <div class="co-sec-v2">
          <h2 class="co-sec-v2__title">
            👤 Unsere Experten
            <span class="co-section-count"><?= count($experts) ?></span>
          </h2>
          <div class="co-expert-grid-v2">
            <?php foreach ($experts as $exp):
              $expFirstName = $exp->first_name ?? '';
              $expLastName  = $exp->last_name  ?? '';
              $expName      = $sec->escape(trim($expFirstName . ' ' . $expLastName) ?: 'Experte');
              $expRole      = !empty($exp->role)     ? $sec->escape($exp->role)     : null;
              $expCity      = !empty($exp->location_city) ? $sec->escape($exp->location_city) : null;
              $expAvail     = $exp->availability ?? 'available';
              $expPhoto     = !empty($exp->photo_url) ? $sec->escape($exp->photo_url) : null;
              $expId        = (int)($exp->id ?? 0);
              $letter       = mb_strtoupper(mb_substr($expFirstName ?: $expLastName, 0, 1) ?: 'E');
              $expColors    = [['#5e72e4','#8965e0'],['#0891b2','#06b6d4'],['#16a34a','#22c55e'],['#7c3aed','#a855f7'],['#d97706','#f59e0b']];
              $ec           = $expColors[abs(crc32($expFirstName . $expLastName)) % count($expColors)];
              $expGradient  = "linear-gradient(135deg,{$ec[0]},{$ec[1]})";
              $availMap     = ['available'=>['✅','Verfügbar','#065f46','#d1fae5'],'limited'=>['⏳','Begrenzt','#92400e','#fef3c7'],'booked'=>['🚫','Ausgebucht','#991b1b','#fee2e2']];
              [$avIco,$avLbl,$avTxt,$avBg] = $availMap[$expAvail] ?? $availMap['available'];
            ?>
              <div class="co-exp-row">
                <?php if ($expPhoto): ?>
                  <div class="co-exp-row__av"><img src="<?= $expPhoto ?>" alt="<?= $expName ?>"></div>
                <?php else: ?>
                  <div class="co-exp-row__av" style="background:<?= $expGradient ?>"><?= $letter ?></div>
                <?php endif; ?>
                <div class="co-exp-row__info">
                  <div class="co-exp-row__name">
                    <?php if ($expId > 0): ?>
                      <a href="<?= SITE_URL ?>/experts/<?= $expId ?>"><?= $expName ?></a>
                    <?php else: ?>
                      <?= $expName ?>
                    <?php endif; ?>
                  </div>
                  <?php if ($expRole): ?><div class="co-exp-row__sub">💼 <?= $expRole ?></div><?php endif; ?>
                  <?php if ($expCity): ?><div class="co-exp-row__sub">📍 <?= $expCity ?></div><?php endif; ?>
                  <span class="co-exp-row__avail" style="--avail-bg:<?= $avBg ?>;--avail-txt:<?= $avTxt ?>"><?= $avIco ?> <?= $avLbl ?></span>
                </div>
                <?php if ($expId > 0): ?>
                  <a href="<?= SITE_URL ?>/experts/<?= $expId ?>" class="co-btn-v2 co-btn-v2--ghost co-btn-v2--sm">Profil →</a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

    </main>

    <aside class="co-sidebar-v2">

      <?php if (!empty($company->email) || !empty($company->phone) || !empty($company->website)): ?>
        <div class="co-sc-v2">
          <h3 class="co-sc-v2__title">📞 Kontakt</h3>
          <?php if (!empty($company->website)): ?>
            <a href="<?= $sec->escape($company->website) ?>" target="_blank" rel="noopener" class="co-btn-v2">🌐 Website besuchen</a>
          <?php endif; ?>
          <?php if (!empty($company->email)): ?>
            <a href="mailto:<?= $sec->escape($company->email) ?>" class="co-btn-v2 co-btn-v2--ghost">✉️ E-Mail schreiben</a>
          <?php endif; ?>
          <?php if (!empty($company->phone)): ?>
            <a href="tel:<?= $sec->escape($company->phone) ?>" class="co-btn-v2 co-btn-v2--ghost">📞 <?= $sec->escape($company->phone) ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php
      $facts = [];
      if (!empty($company->industry))       $facts[] = ['🏭','Branche',          $sec->escape($company->industry)];
      if (!empty($company->company_size))   $facts[] = ['📊','Größe',            $sec->escape($company->company_size)];
      if (!empty($company->employee_count)) $facts[] = ['👥','Mitarbeiter',       number_format((int)$company->employee_count, 0, ',', '.')];
      if (!empty($company->founded_year))   $facts[] = ['📅','Gegründet',         (string)(int)$company->founded_year];
      if (!empty($company->location_zip) || !empty($company->location_city)):
          $loc = trim(($company->location_zip ?? '') . ' ' . ($company->location_city ?? ''));
          if (!empty($company->location_country) && $company->location_country !== 'Deutschland') {
              $loc .= ', ' . $company->location_country;
          }
          $facts[] = ['📍','Standort', $sec->escape($loc)];
      endif;
      if ($facts): ?>
        <div class="co-sc-v2">
          <h3 class="co-sc-v2__title">📋 Unternehmensdaten</h3>
          <div class="co-info-rows">
            <?php foreach ($facts as [$ic, $lbl, $val]): ?>
              <div class="co-info-row">
                <span class="co-info-row__lbl"><?= $ic ?> <?= $lbl ?></span>
                <span class="co-info-row__val"><?= $val ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="co-sc-v2">
        <a href="<?= $base_url ?>/companies" class="co-btn-v2 co-btn-v2--ghost co-btn-v2--sm">← Zur Unternehmensübersicht</a>
      </div>

    </aside>
  </div>
</div>
