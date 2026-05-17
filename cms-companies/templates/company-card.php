<?php
declare(strict_types=1);

/**
 * Template: Company Card Component
 *
 * @package CMS_Companies
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$company = $company ?? null;
if (!$company) { return; }

$sec = CMS\Security::instance();

// Design-Einstellungen: übergeben oder aus DB laden
if (!isset($s) || !is_array($s)) {
    $s = array_merge([
        'design_show_industry'  => '1',
        'design_show_city'      => '1',
        'design_show_employees' => '1',
        'design_show_website'   => '1',
        'design_partner_color'  => '#9ca3af',
        'design_top_partner_color' => '#d97706',
        'design_sponsor_color'  => '#7c3aed',
    ], CMS_Companies_Database::instance()->get_settings());
}
$show_industry  = ($s['design_show_industry']  ?? '1') === '1';
$show_city      = ($s['design_show_city']      ?? '1') === '1';
$show_employees = ($s['design_show_employees'] ?? '1') === '1';
$show_website   = ($s['design_show_website']   ?? '1') === '1';
$logo_url       = cms_companies_public_url((string) ($company->logo_url ?? ''));
$website_url    = cms_companies_public_url((string) ($company->website ?? ''));

// Initials (bis zu 2 Zeichen)
$name_parts = preg_split('/\s+/', trim($company->name));
$initials    = mb_strtoupper(mb_substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? mb_substr($name_parts[1], 0, 1) : ''));

// Partner-Tier bestimmen
$is_sponsor     = (bool)($company->is_sponsor     ?? false);
$is_top_partner = (bool)($company->is_top_partner ?? false);
$is_partner     = (bool)($company->is_partner     ?? false);

// Excerpt
$excerpt = '';
if ($company->description) {
    $excerpt = mb_substr(strip_tags($company->description), 0, 155);
}

// Industrie-Label (slug → lesbarer Name, Fallback = slug selbst)
$industry_label = $company->industry ?? '';

// CSS-Variable für Rahmenfarbe = Badge-Farbe (aus Admin-Design-Einstellungen)
?>

<article class="phinit-card phinit-card--accent co-card<?= $is_sponsor ? ' co-card--sponsor' : ($is_top_partner ? ' co-card--top' : ($is_partner ? ' co-card--partner' : '')) ?>">

    <!-- Status-Ribbon (Sponsor / Top-Partner / Partner) -->
    <?php if ($is_sponsor): ?>
        <div class="co-card-ribbon co-card-ribbon--sponsor">★ Sponsor</div>
    <?php elseif ($is_top_partner): ?>
        <div class="co-card-ribbon co-card-ribbon--top">◆ Top-Partner</div>
    <?php elseif ($is_partner): ?>
        <div class="co-card-ribbon co-card-ribbon--partner">● Partner</div>
    <?php endif; ?>

    <!-- Header: Avatar + Name + Branche -->
    <div class="co-card-head">
        <?php if ($logo_url !== ''): ?>
            <div class="co-card-avatar co-card-avatar--logo">
                <img src="<?= $sec->escape($logo_url) ?>" alt="<?= $sec->escape($company->name) ?>" width="72" height="72" loading="lazy" decoding="async">
            </div>
        <?php else: ?>
            <div class="co-card-avatar co-card-avatar--placeholder"><?= $sec->escape($initials) ?></div>
        <?php endif; ?>
        <div class="co-card-identity">
            <h3 class="co-card-name">
                <a href="<?= $sec->escape(cms_company_url($company)) ?>"><?= $sec->escape($company->name) ?></a>
            </h3>
        </div>
    </div>

    <!-- Info-Pills -->
    <?php
    $pills = [];
    if ($show_city     && !empty($company->location_city))   $pills[] = ['Standort', $sec->escape($company->location_city)];
    if ($show_employees && !empty($company->employee_count)) $pills[] = ['Team', number_format((int)$company->employee_count, 0, ',', '.') . ' Mitarb.'];
    if ($show_employees && !empty($company->company_size))   $pills[] = ['Größe', $sec->escape($company->company_size)];
    if (!empty($company->founded_year))                      $pills[] = ['Seit', (string) (int) $company->founded_year];
    if ($pills): ?>
    <div class="co-card-pills">
        <?php foreach ($pills as [$label, $txt]): ?>
            <span class="co-card-pill"><span class="co-card-pill__label"><?= $label ?></span> <?= $txt ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Excerpt -->
    <?php if ($excerpt && trim($excerpt) !== ''): ?>
        <p class="co-card-excerpt"><?= $sec->escape($excerpt) ?>…</p>
    <?php endif; ?>

    <!-- Footer: Actions -->
    <div class="co-card-footer">
        <a href="<?= $sec->escape(cms_company_url($company)) ?>" class="phinit-btn phinit-btn--primary co-btn co-btn-primary co-btn-block">
            Details ansehen
        </a>
        <?php if ($show_website && $website_url !== ''): ?>
            <a href="<?= $sec->escape($website_url) ?>" target="_blank" rel="noopener noreferrer" class="phinit-btn phinit-btn--secondary co-btn co-btn-ghost" aria-label="Website öffnen">
                Website
            </a>
        <?php endif; ?>
    </div>

</article>

