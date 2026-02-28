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

$company  = $company  ?? null;
$experts  = $experts  ?? [];
$speakers = $speakers ?? [];

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
// Wenn design_detail_header_bg_to noch nicht in der DB existiert (alte Einstellungen),
// beide Gradient-Farben auf helles Blau zurücksetzen statt den alten Dunkelwert zu nutzen.
$hasBgTo      = array_key_exists('design_detail_header_bg_to', $settings) && !empty($settings['design_detail_header_bg_to']);
$headerBgFrom = $hasBgTo ? ($settings['design_detail_header_bg'] ?? '#e0f2fe') : '#e6ffe1';
$headerBgTo   = $hasBgTo ? $settings['design_detail_header_bg_to']              : '#f7fff4';
// Auf hellem Gradient dunkle Titelfarbe erzwingen, wenn noch alte weiße Einstellung vorhanden
$headerTitleColor = $hasBgTo
    ? ($settings['design_detail_header_color'] ?? '#0c4a6e')
    : '#0c4a6e';
$partnerSponsorColor= $settings['design_sponsor_color']         ?? '#7c3aed';
$partnerTopColor    = $settings['design_top_partner_color']     ?? '#d97706';
$partnerColor       = $settings['design_partner_color']         ?? '#9ca3af';
$base_url           = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
?>
<style>
:root {
  --co-primary:          <?= htmlspecialchars($settings['design_primary_color'] ?? '#0891b2') ?>;
  --co-primary-d:        <?= htmlspecialchars($settings['design_accent_color']  ?? '#0284c7') ?>;
  --co-radius:           <?= (int)($settings['design_border_radius'] ?? 14) ?>px;
  --co-shadow:           0 1px 3px rgba(0,0,0,.04), 0 4px 14px rgba(0,0,0,.04);
  --co-shadow-h:         0 4px 20px rgba(0,0,0,.10);
  --co-detail-hdr-bg:    linear-gradient(135deg, <?= htmlspecialchars($headerBgFrom) ?> 0%, <?= htmlspecialchars($headerBgTo) ?> 100%);
  --co-detail-hdr-color: <?= htmlspecialchars($headerTitleColor) ?>;
}
</style>

<div class="co-single-v2">

  <nav class="co-breadcrumb">
    <a href="<?= $base_url ?>/companies">← Unternehmen</a>
    <span class="co-breadcrumb__sep">/</span>
    <span class="co-breadcrumb__cur"><?= $sec->escape(mb_strimwidth($company->name ?? '', 0, 60, '…')) ?></span>
  </nav>

  <header class="co-hero-v2" style="background:linear-gradient(135deg,<?= htmlspecialchars($headerBgFrom) ?> 0%,<?= htmlspecialchars($headerBgTo) ?> 100%);">
    <div class="co-hero-v2__badges">
      <?php if ($is_sponsor): ?>
        <span class="co-hero-v2__badge co-hero-v2__badge--sponsor">★ Sponsor</span>
      <?php elseif ($is_top_partner): ?>
        <span class="co-hero-v2__badge co-hero-v2__badge--top-partner">◆ Top-Partner</span>
      <?php elseif ($is_partner): ?>
        <span class="co-hero-v2__badge co-hero-v2__badge--partner">● Partner</span>
      <?php endif; ?>
    </div>
    <div class="co-hero-v2__inner">
      <?php if (!empty($company->logo_url)): ?>
        <div class="co-hero-v2__avatar"><img src="<?= $sec->escape($company->logo_url) ?>" alt="<?= $sec->escape($company->name) ?>"></div>
      <?php else: ?>
        <div class="co-hero-v2__avatar" style="background:<?= $avatarBg ?>;"><?= $sec->escape($initials) ?></div>
      <?php endif; ?>
      <div class="co-hero-v2__meta">
        <h1 class="co-hero-v2__title"><?= $sec->escape($company->name) ?></h1>
      </div>
    </div>
  </header>

  <!-- Bridge Cards (überlappen Hero, wie Expert-Single) -->
  <div class="co-bridge-v2">
    <div class="co-bridge-v2__about">
      <h2 class="co-bridge-v2__title">🏢 Über das Unternehmen</h2>
      <?php if (!empty($company->description) && trim($company->description) !== ''): ?>
        <div class="co-bridge-v2__text co-wysiwyg-content"><?= $company->description ?></div>
      <?php else: ?>
        <p class="co-bridge-v2__text" style="color:#94a3b8;font-style:italic;">Noch keine Beschreibung hinterlegt.</p>
      <?php endif; ?>
    </div>
    <div class="co-bridge-v2__contact">
      <h2 class="co-bridge-v2__title">&#128203; Details &amp; Kontakt</h2>
      <div class="co-bridge-v2__contact-body">

        <!-- Buchungs-Button -->
        <a href="#contact" class="co-bridge-v2__book-btn">✉️ Nachricht / Buchen</a>

        <!-- Kontakt-Icons in einer Reihe -->
        <?php if (!empty($company->website) || !empty($company->email) || !empty($company->phone)): ?>
        <div class="co-bridge-v2__icon-row">
          <?php if (!empty($company->website)): ?>
            <a href="<?= $sec->escape($company->website) ?>" target="_blank" rel="noopener" class="co-bridge-v2__icon-btn">🌐 Web</a>
          <?php endif; ?>
          <?php if (!empty($company->email)): ?>
            <a href="mailto:<?= $sec->escape($company->email) ?>" class="co-bridge-v2__icon-btn">✉️ Mail</a>
          <?php endif; ?>
          <?php if (!empty($company->phone)): ?>
            <a href="tel:<?= $sec->escape($company->phone) ?>" class="co-bridge-v2__icon-btn">📞 Anruf</a>
          <?php endif; ?>
        </div>
        <?php endif; ?>

        <hr class="co-bridge-v2__divider">

        <!-- Unternehmensdaten -->
        <?php
        $facts = [];
        if (!empty($company->industry))       $facts[] = ['🏭 Branche',    $sec->escape($company->industry)];
        if (!empty($company->company_size))   $facts[] = ['📊 Größe',      $sec->escape($company->company_size)];
        if (!empty($company->employee_count)) $facts[] = ['👥 Mitarbeiter', number_format((int)$company->employee_count, 0, ',', '.')];
        if (!empty($company->founded_year))   $facts[] = ['📅 Gegründet',  (string)(int)$company->founded_year];
        if (!empty($company->location_city)) {
            $loc = trim(($company->location_zip ?? '') . ' ' . $company->location_city);
            if (!empty($company->location_country) && $company->location_country !== 'Deutschland') {
                $loc .= ', ' . $company->location_country;
            }
            $facts[] = ['📍 Standort', $sec->escape($loc)];
        }
        if (!empty($facts)): ?>
        <div class="co-bridge-v2__facts">
          <?php foreach ($facts as [$lbl, $val]): ?>
            <div class="co-bridge-v2__fact">
              <span class="co-bridge-v2__fact-lbl"><?= $lbl ?></span>
              <span class="co-bridge-v2__fact-val"><?= $val ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <div class="co-people-v2">

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
            ?>
              <div class="co-exp-row">
                <div class="co-exp-row__top">
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
                  </div>
                </div>
                <?php if ($expId > 0): ?>
                  <a href="<?= SITE_URL ?>/experts/<?= $expId ?>" class="co-btn-v2 co-btn-v2--ghost co-btn-v2--sm" style="width:100%;text-align:center;">Profil →</a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($speakers)): ?>
        <div class="co-sec-v2">
          <h2 class="co-sec-v2__title">
            🎤 Unsere Speaker
            <span class="co-section-count"><?= count($speakers) ?></span>
          </h2>
          <div class="co-expert-grid-v2">
            <?php foreach ($speakers as $spk):
              $spkFirst  = $spk->first_name ?? '';
              $spkLast   = $spk->last_name  ?? '';
              $spkName   = $sec->escape(trim($spkFirst . ' ' . $spkLast) ?: 'Speaker');
              $spkRole   = !empty($spk->position)      ? $sec->escape($spk->position)      : null;
              $spkCity   = !empty($spk->location_city) ? $sec->escape($spk->location_city) : null;
              $spkAvail  = $spk->availability ?? 'available';
              $spkPhoto  = !empty($spk->photo_url) ? $sec->escape($spk->photo_url) : null;
              $spkId     = (int)($spk->id ?? 0);
              $letter    = mb_strtoupper(mb_substr($spkFirst ?: $spkLast, 0, 1) ?: 'S');
              $spkColors = [['#5e72e4','#8965e0'],['#0891b2','#06b6d4'],['#16a34a','#22c55e'],['#7c3aed','#a855f7'],['#d97706','#f59e0b']];
              $sc        = $spkColors[abs(crc32($spkFirst . $spkLast)) % count($spkColors)];
              $spkGrad   = "linear-gradient(135deg,{$sc[0]},{$sc[1]})";
            ?>
              <div class="co-exp-row">
                <div class="co-exp-row__top">
                  <?php if ($spkPhoto): ?>
                    <div class="co-exp-row__av"><img src="<?= $spkPhoto ?>" alt="<?= $spkName ?>"></div>
                  <?php else: ?>
                    <div class="co-exp-row__av" style="background:<?= $spkGrad ?>"><?= $letter ?></div>
                  <?php endif; ?>
                  <div class="co-exp-row__info">
                    <div class="co-exp-row__name">
                      <?php if ($spkId > 0): ?>
                        <a href="<?= SITE_URL ?>/speakers/<?= $spkId ?>"><?= $spkName ?></a>
                      <?php else: ?>
                        <?= $spkName ?>
                      <?php endif; ?>
                    </div>
                    <?php if ($spkRole): ?><div class="co-exp-row__sub">🎤 <?= $spkRole ?></div><?php endif; ?>
                    <?php if ($spkCity): ?><div class="co-exp-row__sub">📍 <?= $spkCity ?></div><?php endif; ?>
                  </div>
                </div>
                <?php if ($spkId > 0): ?>
                  <a href="<?= SITE_URL ?>/speakers/<?= $spkId ?>" class="co-btn-v2 co-btn-v2--ghost co-btn-v2--sm" style="width:100%;text-align:center;">Profil →</a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

  </div>

  <?php if (!(int)($company->user_id ?? 0)): ?>
  <div class="co-claim-banner">
    <div class="co-claim-banner__text">
      <strong>Dieses Unternehmensprofil wurde von der Redaktion angelegt.</strong>
      Gehört es Ihnen? Registrieren Sie sich kostenlos und übernehmen Sie die Verwaltung.
    </div>
    <a href="<?= rtrim(SITE_URL, '/') ?>/register" class="co-claim-banner__btn">Jetzt registrieren &amp; Profil beanspruchen →</a>
  </div>
  <?php endif; ?>
</div>
