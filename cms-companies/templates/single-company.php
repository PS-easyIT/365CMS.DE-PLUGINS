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
$headerBgFrom       = $settings['design_detail_header_bg']    ?? '#0c4a6e';
$headerTitleColor   = $settings['design_detail_header_color'] ?? '#ffffff';
$partnerSponsorColor= $settings['design_sponsor_color']       ?? '#7c3aed';
$partnerTopColor    = $settings['design_top_partner_color']   ?? '#d97706';
$partnerColor       = $settings['design_partner_color']       ?? '#9ca3af';
?>

<div class="co-single">

    <!-- ===== Hero-Header ===== -->
    <div class="co-single-header" style="background:<?= htmlspecialchars($headerBgFrom) ?>;">

        <!-- Partner-Ribbon oben rechts -->
        <?php if ($is_sponsor): ?>
            <span class="co-sh-ribbon" style="background:<?= htmlspecialchars($partnerSponsorColor) ?>;">★ Sponsor</span>
        <?php elseif ($is_top_partner): ?>
            <span class="co-sh-ribbon" style="background:<?= htmlspecialchars($partnerTopColor) ?>;">◆ Top-Partner</span>
        <?php elseif ($is_partner): ?>
            <span class="co-sh-ribbon" style="background:<?= htmlspecialchars($partnerColor) ?>;">● Partner</span>
        <?php endif; ?>

        <div class="co-sh-inner">
            <!-- Avatar / Logo -->
            <?php if (!empty($company->logo_url)): ?>
                <div class="co-sh-avatar co-sh-avatar--logo">
                    <img src="<?= $sec->escape($company->logo_url) ?>" alt="<?= $sec->escape($company->name) ?> Logo" loading="lazy">
                </div>
            <?php else: ?>
                <div class="co-sh-avatar" style="background:<?= $avatarBg ?>;"><?= $sec->escape($initials) ?></div>
            <?php endif; ?>

            <!-- Titel + Industry -->
            <div class="co-sh-text" style="color:<?= htmlspecialchars($headerTitleColor) ?>;">
                <h1><?= $sec->escape($company->name) ?></h1>
                <?php if (!empty($company->industry)): ?>
                    <p class="co-sh-subtitle"><?= $sec->escape($company->industry) ?></p>
                <?php endif; ?>
                <?php if (!empty($company->location_city)): ?>
                    <p class="co-sh-location">📍 <?= $sec->escape($company->location_city) ?><?= !empty($company->location_country) && $company->location_country !== 'Deutschland' ? ', ' . $sec->escape($company->location_country) : '' ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Meta-Chips Leiste -->
        <?php
        $chips = [];
        if (!empty($company->employee_count)) $chips[] = ['👥', number_format((int)$company->employee_count, 0, ',', '.') . ' Mitarbeiter'];
        if (!empty($company->company_size))   $chips[] = ['🏢', $sec->escape($company->company_size)];
        if (!empty($company->founded_year))   $chips[] = ['📅', 'Gegründet ' . $company->founded_year];
        if (!empty($company->website))        $chips[] = ['🌐', parse_url($company->website, PHP_URL_HOST) ?: $sec->escape($company->website)];
        if ($chips): ?>
        <div class="co-sh-chips">
            <?php foreach ($chips as [$ic, $txt]): ?>
                <span class="co-sh-chip"><?= $ic ?> <?= $txt ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===== Haupt-Layout: Content + Sidebar ===== -->
    <div class="co-single-body">

        <!-- Content -->
        <main class="co-single-main">

            <?php if (!empty($company->description) && trim($company->description) !== ''): ?>
            <section class="co-section">
                <h2>🏢 Über das Unternehmen</h2>
                <div class="co-section-body co-wysiwyg-content">
                    <?= $company->description ?>
                </div>
            </section>
            <?php endif; ?>

            <?php if (!empty($experts)): ?>
            <section class="co-section">
                <h2>👤 Unsere Experten <span class="co-section-count"><?= count($experts) ?></span></h2>
                <div class="co-experts-grid">
                    <?php foreach ($experts as $exp): ?>
                    <div class="co-expert-item">
                        <?php
                        $expFirstName = $exp->first_name ?? '';
                        $expLastName  = $exp->last_name  ?? '';
                        $expName      = $sec->escape(trim($expFirstName . ' ' . $expLastName) ?: 'Experte');
                        $expRole      = !empty($exp->role)     ? $sec->escape($exp->role)     : null;
                        $expPosition  = !empty($exp->position) ? $sec->escape($exp->position) : null;
                        $expCity      = !empty($exp->location_city) ? $sec->escape($exp->location_city) : null;
                        $expAvail     = $exp->availability ?? 'available';
                        $expPhoto     = !empty($exp->photo_url) ? $sec->escape($exp->photo_url) : null;
                        $expId        = (int)($exp->id ?? 0); // e.id = expert primary key
                        $letter       = mb_strtoupper(mb_substr($expFirstName ?: $expLastName, 0, 1) ?: 'E');
                        $expColors    = [['#5e72e4','#8965e0'],['#0891b2','#06b6d4'],['#16a34a','#22c55e'],['#7c3aed','#a855f7'],['#d97706','#f59e0b']];
                        $ec           = $expColors[abs(crc32($expFirstName . $expLastName)) % count($expColors)];
                        $expGradient  = "linear-gradient(135deg,{$ec[0]},{$ec[1]})";
                        $availMap     = ['available'=>['✅','Verfügbar','#065f46','#d1fae5'],'limited'=>['⏳','Begrenzt','#92400e','#fef3c7'],'booked'=>['🚫','Ausgebucht','#991b1b','#fee2e2']];
                        [$avIco,$avLbl,$avTxt,$avBg] = $availMap[$expAvail] ?? $availMap['available'];
                        ?>
                        <?php if ($expPhoto): ?>
                            <img class="co-expert-photo" src="<?= $expPhoto ?>" alt="<?= $expName ?>" loading="lazy">
                        <?php else: ?>
                            <div class="co-expert-avatar" style="background:<?= $expGradient ?>"><?= $letter ?></div>
                        <?php endif; ?>
                        <div class="co-expert-info">
                            <?php if ($expId > 0): ?>
                                <strong><a href="<?= SITE_URL ?>/experts/<?= $expId ?>" class="co-expert-link"><?= $expName ?></a></strong>
                            <?php else: ?>
                                <strong><?= $expName ?></strong>
                            <?php endif; ?>
                            <?php if ($expRole): ?><span class="co-expert-role">💼 <?= $expRole ?></span><?php endif; ?>
                            <?php if ($expPosition): ?><span class="co-expert-pos">🎯 <?= $expPosition ?></span><?php endif; ?>
                            <?php if ($expCity): ?><span class="co-expert-city">📍 <?= $expCity ?></span><?php endif; ?>
                            <span class="co-expert-avail" style="background:<?= $avBg ?>;color:<?= $avTxt ?>"><?= $avIco ?> <?= $avLbl ?></span>
                            <?php if ((bool)($exp->is_current ?? false)): ?><span class="co-expert-current">✓ Aktuell</span><?php endif; ?>
                        </div>
                        <?php if ($expId > 0): ?>
                        <a href="<?= SITE_URL ?>/experts/<?= $expId ?>" class="co-btn co-btn-primary co-btn-sm" style="margin-left:auto;flex-shrink:0;">Profil →</a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        </main>

        <!-- Sidebar -->
        <aside class="co-single-sidebar">

            <!-- Kontakt -->
            <?php if (!empty($company->email) || !empty($company->phone) || !empty($company->website)): ?>
            <div class="co-sidebar-card">
                <h3>📞 Kontakt</h3>
                <?php if (!empty($company->email)): ?>
                <div class="co-meta-row">
                    <span class="co-meta-lbl">E-Mail</span>
                    <a href="mailto:<?= $sec->escape($company->email) ?>" class="co-meta-val co-link">
                        <?= $sec->escape($company->email) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($company->phone)): ?>
                <div class="co-meta-row">
                    <span class="co-meta-lbl">Telefon</span>
                    <a href="tel:<?= $sec->escape($company->phone) ?>" class="co-meta-val co-link">
                        <?= $sec->escape($company->phone) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($company->website)): ?>
                <div class="co-meta-row">
                    <span class="co-meta-lbl">Website</span>
                    <a href="<?= $sec->escape($company->website) ?>" target="_blank" rel="noopener" class="co-meta-val co-link">
                        <?= $sec->escape(str_replace(['https://','http://'], '', $company->website)) ?>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($company->website)): ?>
                    <a href="<?= $sec->escape($company->website) ?>" target="_blank" rel="noopener"
                       class="co-btn co-btn-primary co-btn-block" style="margin-top:1rem;">
                        🌐 Website besuchen
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Firmendaten -->
            <?php
            $facts = [];
            if (!empty($company->industry))       $facts[] = ['🏭','Branche',          $sec->escape($company->industry)];
            if (!empty($company->company_size))   $facts[] = ['📊','Unternehmensgröße', $sec->escape($company->company_size)];
            if (!empty($company->employee_count)) $facts[] = ['👥','Mitarbeiter',       number_format((int)$company->employee_count, 0, ',', '.')];
            if (!empty($company->founded_year))   $facts[] = ['📅','Gegründet',         (int)$company->founded_year];
            if (!empty($company->location_zip) || !empty($company->location_city)):
                $loc = trim(($company->location_zip ?? '') . ' ' . ($company->location_city ?? ''));
                if (!empty($company->location_country) && $company->location_country !== 'Deutschland') {
                    $loc .= ', ' . $company->location_country;
                }
                $facts[] = ['📍','Standort', $sec->escape($loc)];
            endif;
            if ($facts): ?>
            <div class="co-sidebar-card">
                <h3>📋 Unternehmensdaten</h3>
                <?php foreach ($facts as [$ic, $lbl, $val]): ?>
                <div class="co-meta-row">
                    <span class="co-meta-lbl"><?= $ic ?> <?= $lbl ?></span>
                    <span class="co-meta-val"><?= $val ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Zurück -->
            <div class="co-sidebar-card co-sidebar-card--plain">
                <a href="<?= SITE_URL ?>/companies" class="co-btn co-btn-ghost co-btn-block">
                    ← Zur Unternehmens-Übersicht
                </a>
            </div>

        </aside>
    </div>
</div>


