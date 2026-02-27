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
  --co-primary:   <?= htmlspecialchars($settings['design_primary_color'] ?? '#0891b2') ?>;
  --co-primary-d: <?= htmlspecialchars($settings['design_accent_color']  ?? '#0284c7') ?>;
  --co-radius:    <?= (int)($settings['design_border_radius'] ?? 14) ?>px;
  --co-shadow:    0 1px 3px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.04);
  --co-shadow-h:  0 4px 20px rgba(0,0,0,.10);
}
.co-single-v2 {
  max-width: var(--max, 1140px); margin: 0 auto;
  border-left: 1px solid var(--post-column-border,#e2e0d8);
  border-right: 1px solid var(--post-column-border,#e2e0d8);
  background: #f8fafc;
}
.co-breadcrumb {
  display: flex; align-items: center; gap: .5rem;
  padding: .7rem 2rem; background: #fff; border-bottom: 1px solid #f1f5f9; font-size: .8rem;
}
.co-breadcrumb a { color: var(--co-primary); text-decoration: none; font-weight: 600; }
.co-breadcrumb a:hover { opacity: .7; }
.co-breadcrumb__sep { color: #cbd5e1; }
.co-breadcrumb__cur { color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 340px; }
.co-hero-v2 {
  position: relative; overflow: hidden;
  min-height: 200px; max-height: 300px;
  background: <?= htmlspecialchars($headerBgFrom) ?>;
  border-bottom: 3px solid var(--co-primary);
}
.co-hero-v2__inner {
  display: flex; gap: 1.75rem; align-items: center;
  padding: 1.5rem 2rem 1.75rem; flex-wrap: wrap; position: relative; z-index: 1;
  min-height: 200px;
}
.co-hero-v2__avatar {
  flex-shrink: 0; width: 88px; height: 88px; border-radius: 16px; overflow: hidden;
  box-shadow: 0 6px 20px rgba(0,0,0,.12), 0 0 0 4px rgba(255,255,255,.75);
  display: flex; align-items: center; justify-content: center;
  font-size: 2rem; font-weight: 900; color: #fff; letter-spacing: -.02em;
}
.co-hero-v2__avatar img { width: 100%; height: 100%; object-fit: contain; padding: 8px; background: #fff; box-sizing: border-box; border-radius: 12px; }
.co-hero-v2__meta { flex: 1; min-width: 0; }
.co-hero-v2__title {
  margin: 0 0 .35rem;
  font-size: clamp(1.4rem, 3vw, 1.875rem); font-weight: 800; line-height: 1.2;
  color: <?= htmlspecialchars($headerTitleColor) ?>;
}
.co-hero-v2__sub { font-size: .9rem; color: <?= htmlspecialchars($headerTitleColor) ?>; opacity: .7; margin: 0 0 .875rem; }
.co-hero-v2__chips { display: flex; flex-wrap: wrap; gap: .4rem; }
.co-hero-v2__chip {
  display: inline-flex; align-items: center; gap: .3rem;
  padding: .3rem .8rem; border-radius: 50px; font-size: .76rem; font-weight: 600;
  background: rgba(255,255,255,.8); color: #334155; border: 1.5px solid rgba(255,255,255,.5);
  backdrop-filter: blur(3px); box-shadow: 0 1px 3px rgba(0,0,0,.04);
}
.co-hero-v2__ribbon {
  position: absolute; top: 1.25rem; right: 0;
  padding: .25rem .875rem .25rem .625rem; border-radius: 6px 0 0 6px;
  font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em;
  color: #fff; box-shadow: -1px 1px 4px rgba(0,0,0,.18);
}
.co-body-v2 {
  display: grid; grid-template-columns: 1fr 320px;
  gap: 1.75rem; padding: 1.75rem 2rem 3rem; align-items: start;
}
.co-main-v2 { min-width: 0; }
.co-sidebar-v2 { position: sticky; top: 1.5rem; display: flex; flex-direction: column; gap: .875rem; }
.co-sec-v2 {
  background: #fff; border-radius: var(--co-radius,14px);
  padding: 1.5rem 1.75rem; margin-bottom: 1.125rem;
  box-shadow: var(--co-shadow); transition: box-shadow .2s;
}
.co-sec-v2:hover { box-shadow: var(--co-shadow-h); }
.co-sec-v2:last-child { margin-bottom: 0; }
.co-sec-v2__title {
  font-size: .92rem; font-weight: 700; color: #1e293b;
  margin: 0 0 1.125rem; padding-bottom: .6rem; padding-left: .75rem;
  display: flex; align-items: center; gap: .4rem;
  border-left: 3px solid var(--co-primary); border-bottom: 1.5px solid #f1f5f9;
}
.co-section-count {
  display: inline-flex; align-items: center; justify-content: center;
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--co-primary); color: #fff; font-size: .73rem; font-weight: 700; margin-left: .35rem;
}
.co-expert-grid-v2 { display: flex; flex-direction: column; gap: .5rem; }
.co-exp-row {
  display: flex; gap: .875rem; align-items: center;
  padding: .75rem .875rem; border-radius: 10px;
  background: #fafbfc; border: 1px solid #f1f5f9;
  transition: background .15s, border-color .15s;
}
.co-exp-row:hover { background: #f0f9ff; border-color: #bae6fd; }
.co-exp-row__av {
  width: 44px; height: 44px; border-radius: 10px; overflow: hidden; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-weight: 800; color: #fff; font-size: .9rem;
}
.co-exp-row__av img { width: 100%; height: 100%; object-fit: cover; }
.co-exp-row__info { flex: 1; min-width: 0; }
.co-exp-row__name { font-size: .9rem; font-weight: 700; color: #1e293b; }
.co-exp-row__name a { color: inherit; text-decoration: none; }
.co-exp-row__name a:hover { color: var(--co-primary); }
.co-exp-row__sub  { font-size: .77rem; color: #94a3b8; margin-top: 2px; }
.co-exp-row__avail{
  font-size: .72rem; font-weight: 600; padding: .15rem .5rem;
  border-radius: 50px; white-space: nowrap; margin-top: 3px; display: inline-block;
}
.co-sc-v2 { background: #fff; border-radius: var(--co-radius,14px); padding: 1.25rem 1.375rem; box-shadow: var(--co-shadow); }
.co-sc-v2__title { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #94a3b8; margin: 0 0 .875rem; }
.co-info-rows { display: flex; flex-direction: column; }
.co-info-row {
  display: flex; justify-content: space-between; align-items: baseline;
  gap: .75rem; padding: .45rem 0; border-bottom: 1px solid #f8fafc;
}
.co-info-row:last-child { border-bottom: none; }
.co-info-row__lbl { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #94a3b8; flex-shrink: 0; }
.co-info-row__val { font-size: .85rem; font-weight: 500; color: #1e293b; text-align: right; word-break: break-word; }
.co-info-row__val a { color: var(--co-primary); text-decoration: none; }
.co-info-row__val a:hover { text-decoration: underline; }
.co-btn-v2 {
  display: flex; align-items: center; justify-content: center; gap: .5rem;
  padding: .875rem 1.5rem; width: 100%; box-sizing: border-box;
  font-size: .9rem; font-weight: 700; border-radius: 10px; border: none;
  cursor: pointer; text-decoration: none; color: #fff; margin-bottom: .5rem;
  background: linear-gradient(135deg, var(--co-primary-d,var(--co-primary)), var(--co-primary));
  box-shadow: 0 4px 16px color-mix(in srgb, var(--co-primary) 30%, transparent);
  transition: all .2s cubic-bezier(.4,0,.2,1);
}
.co-btn-v2:last-child { margin-bottom: 0; }
.co-btn-v2:hover { transform: translateY(-2px); box-shadow: 0 8px 24px color-mix(in srgb, var(--co-primary) 38%, transparent); }
.co-btn-v2--ghost {
  background: #f0f9ff; color: var(--co-primary); box-shadow: none; border: 1.5px solid #bae6fd;
}
.co-btn-v2--ghost:hover { background: #e0f2fe; box-shadow: none; }
@media (max-width: 768px) {
  .co-body-v2 { grid-template-columns: 1fr; padding: 1.25rem 1rem 2rem; gap: 1.25rem; }
  .co-sidebar-v2 { position: static; }
  .co-hero-v2 { min-height: auto; max-height: none; }
  .co-hero-v2__inner { padding: 1.25rem 1rem; gap: 1.25rem; min-height: auto; }
  .co-hero-v2__avatar { width: 72px; height: 72px; }
  .co-hero-v2__title { font-size: 1.3rem; }
  .co-breadcrumb { padding: .6rem 1rem; }
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
      <span class="co-hero-v2__ribbon" style="background:<?= htmlspecialchars($partnerSponsorColor) ?>;">★ Sponsor</span>
    <?php elseif ($is_top_partner): ?>
      <span class="co-hero-v2__ribbon" style="background:<?= htmlspecialchars($partnerTopColor) ?>;">◆ Top-Partner</span>
    <?php elseif ($is_partner): ?>
      <span class="co-hero-v2__ribbon" style="background:<?= htmlspecialchars($partnerColor) ?>;">● Partner</span>
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
          <div class="co-wysiwyg-content" style="line-height:1.75;color:#334155;font-size:.95rem;">
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
                  <span class="co-exp-row__avail" style="background:<?= $avBg ?>;color:<?= $avTxt ?>;"><?= $avIco ?> <?= $avLbl ?></span>
                </div>
                <?php if ($expId > 0): ?>
                  <a href="<?= SITE_URL ?>/experts/<?= $expId ?>" class="co-btn-v2 co-btn-v2--ghost" style="width:auto;padding:.4rem .875rem;font-size:.8rem;margin:0;">Profil →</a>
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
        <a href="<?= $base_url ?>/companies" class="co-btn-v2 co-btn-v2--ghost" style="margin:0;">← Zur Unternehmensübersicht</a>
      </div>

    </aside>
  </div>
</div>
