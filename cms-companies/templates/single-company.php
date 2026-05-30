<?php
declare(strict_types=1);

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

// Avatar-Initials
$name_parts = preg_split('/\s+/', trim($company->name));
$initials   = mb_strtoupper(mb_substr($name_parts[0], 0, 1) . (isset($name_parts[1]) ? mb_substr($name_parts[1], 0, 1) : ''));

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
$companyLogoUrl     = cms_companies_public_url((string) ($company->logo_url ?? ''));
$companyWebsiteUrl  = cms_companies_public_url((string) ($company->website ?? ''));
$companyEmail       = filter_var(trim((string) ($company->email ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
$companyPhoneHref   = preg_replace('/[^0-9+]/', '', trim((string) ($company->phone ?? ''))) ?: '';
$companyNameRaw     = trim((string) ($company->name ?? 'Unternehmen')) ?: 'Unternehmen';
$companyDescription = trim((string) ($company->description ?? ''));
$companyIndustry    = trim((string) ($company->industry ?? ''));
$companySize        = trim((string) ($company->company_size ?? ''));
$companyCity        = trim((string) ($company->location_city ?? ''));
$companyZip         = trim((string) ($company->location_zip ?? ''));
$companyCountry     = trim((string) ($company->location_country ?? ''));
$companyLocation    = trim($companyZip . ($companyZip !== '' && $companyCity !== '' ? ' ' : '') . $companyCity);
if ($companyCountry !== '' && $companyCountry !== 'Deutschland') {
    $companyLocation .= ($companyLocation !== '' ? ', ' : '') . $companyCountry;
}
$partnerLabel = $is_sponsor ? 'Sponsor' : ($is_top_partner ? 'Top-Partner' : ($is_partner ? 'Partner' : 'Unternehmen'));
$coCssColor = static function (mixed $value, string $fallback): string {
    $color = trim((string) $value);
    return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color) === 1 ? $color : $fallback;
};
$coPrimary = $coCssColor($settings['design_primary_color'] ?? null, '#0891b2');
$coAccent = $coCssColor($settings['design_accent_color'] ?? null, '#e0f2fe');
$coCta = $coCssColor($settings['design_cta_color'] ?? null, $coPrimary);
$coCardBg = $coCssColor($settings['design_card_bg'] ?? null, '#ffffff');
$coDetailBg = $coCssColor($settings['design_detail_header_bg'] ?? null, '#f8fafc');
$coDetailColor = $coCssColor($settings['design_detail_header_color'] ?? null, '#1e293b');
$coRadius = max(0, min(32, (int) ($settings['design_border_radius'] ?? 12)));
?>
<style>
:root {
    --co-primary: <?= htmlspecialchars($coPrimary, ENT_QUOTES, 'UTF-8') ?>;
    --co-primary-d: <?= htmlspecialchars($coCta, ENT_QUOTES, 'UTF-8') ?>;
    --co-primary-x: <?= htmlspecialchars($coCta, ENT_QUOTES, 'UTF-8') ?>;
    --co-accent: <?= htmlspecialchars($coAccent, ENT_QUOTES, 'UTF-8') ?>;
    --co-card-bg: <?= htmlspecialchars($coCardBg, ENT_QUOTES, 'UTF-8') ?>;
    --co-detail-hdr-bg: <?= htmlspecialchars($coDetailBg, ENT_QUOTES, 'UTF-8') ?>;
    --co-detail-hdr-color: <?= htmlspecialchars($coDetailColor, ENT_QUOTES, 'UTF-8') ?>;
    --co-radius: <?= (int) $coRadius ?>px;
}
</style>
<main class="phinit-plugin co-single-v2 co-single-v3 co-company-detail">
    <nav class="co-breadcrumb co-company-detail__breadcrumb" aria-label="Breadcrumb">
        <a href="<?= htmlspecialchars($base_url . '/companies', ENT_QUOTES, 'UTF-8') ?>">Unternehmen</a>
        <span class="co-breadcrumb__sep" aria-hidden="true">›</span>
        <span class="co-breadcrumb__cur" aria-current="page"><?= htmlspecialchars($companyNameRaw, ENT_QUOTES, 'UTF-8') ?></span>
    </nav>

    <div class="co-company-detail__grid">
        <article class="co-company-detail__main">
            <header class="co-company-detail__head phinit-card">
                <div class="co-company-detail__badges">
                    <span class="co-company-detail__badge co-company-detail__badge--<?= $is_sponsor ? 'sponsor' : ($is_top_partner ? 'top' : ($is_partner ? 'partner' : 'standard')) ?>"><?= htmlspecialchars($partnerLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php if ($companyIndustry !== ''): ?>
                        <span class="co-company-detail__badge co-company-detail__badge--industry"><?= htmlspecialchars($companyIndustry, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <div class="co-company-detail__title-row">
                    <div class="co-company-detail__logo-box">
                        <?php if ($companyLogoUrl !== ''): ?>
                            <img src="<?= htmlspecialchars($companyLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($companyNameRaw, ENT_QUOTES, 'UTF-8') ?> Logo" width="120" height="80" loading="eager" decoding="async">
                        <?php else: ?>
                            <span aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p class="phinit-overline">Unternehmensprofil</p>
                        <h1><?= htmlspecialchars($companyNameRaw, ENT_QUOTES, 'UTF-8') ?></h1>
                        <?php if ($companyLocation !== '' || $companySize !== ''): ?>
                            <p class="co-company-detail__subtitle"><?= htmlspecialchars(trim($companyLocation . ($companyLocation !== '' && $companySize !== '' ? ' · ' : '') . $companySize), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </header>

            <section class="phinit-card co-company-detail__section">
                <h2>Über das Unternehmen</h2>
                <?php if ($companyDescription !== ''): ?>
                    <div class="co-company-detail__content co-wysiwyg-content"><?= nl2br(htmlspecialchars($companyDescription, ENT_QUOTES, 'UTF-8')) ?></div>
                <?php else: ?>
                    <p class="co-empty-text">Für dieses Unternehmen ist noch keine Beschreibung hinterlegt.</p>
                <?php endif; ?>
            </section>

            <?php if (!empty($experts)): ?>
                <section class="phinit-card co-company-detail__section" aria-labelledby="co-company-experts-heading">
                    <h2 id="co-company-experts-heading">Experten <span class="co-section-count"><?= count($experts) ?></span></h2>
                    <div class="co-company-people-grid">
                        <?php foreach ($experts as $exp): ?>
                            <?php
                            $personFirst = trim((string) ($exp->first_name ?? ''));
                            $personLast = trim((string) ($exp->last_name ?? ''));
                            $personName = trim($personFirst . ' ' . $personLast) ?: 'Experte';
                            $personRole = trim((string) ($exp->role ?? $exp->position ?? ''));
                            $personCity = trim((string) ($exp->location_city ?? ''));
                            $personPhoto = cms_companies_public_url((string) ($exp->photo_url ?? ''));
                            $personId = (int) ($exp->id ?? 0);
                            $personInitial = mb_strtoupper(mb_substr($personFirst !== '' ? $personFirst : ($personLast !== '' ? $personLast : 'E'), 0, 1));
                            ?>
                            <article class="co-company-person-card">
                                <div class="co-company-person-card__avatar">
                                    <?php if ($personPhoto !== ''): ?>
                                        <img src="<?= htmlspecialchars($personPhoto, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($personName, ENT_QUOTES, 'UTF-8') ?>" width="56" height="56" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <span aria-hidden="true"><?= htmlspecialchars($personInitial, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="co-company-person-card__body">
                                    <h3><?= htmlspecialchars($personName, ENT_QUOTES, 'UTF-8') ?></h3>
                                    <?php if ($personRole !== ''): ?><p><?= htmlspecialchars($personRole, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                                    <?php if ($personCity !== ''): ?><p><?= htmlspecialchars($personCity, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                                </div>
                                <?php if ($personId > 0): ?>
                                    <a href="<?= htmlspecialchars($base_url . '/experts/' . $personId, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary">Profil</a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($speakers)): ?>
                <section class="phinit-card co-company-detail__section" aria-labelledby="co-company-speakers-heading">
                    <h2 id="co-company-speakers-heading">Speaker <span class="co-section-count"><?= count($speakers) ?></span></h2>
                    <div class="co-company-people-grid">
                        <?php foreach ($speakers as $spk): ?>
                            <?php
                            $personFirst = trim((string) ($spk->first_name ?? ''));
                            $personLast = trim((string) ($spk->last_name ?? ''));
                            $personName = trim($personFirst . ' ' . $personLast) ?: 'Speaker';
                            $personRole = trim((string) ($spk->position ?? ''));
                            $personCity = trim((string) ($spk->location_city ?? ''));
                            $personPhoto = cms_companies_public_url((string) ($spk->photo_url ?? ''));
                            $personId = (int) ($spk->id ?? 0);
                            $personInitial = mb_strtoupper(mb_substr($personFirst !== '' ? $personFirst : ($personLast !== '' ? $personLast : 'S'), 0, 1));
                            ?>
                            <article class="co-company-person-card">
                                <div class="co-company-person-card__avatar">
                                    <?php if ($personPhoto !== ''): ?>
                                        <img src="<?= htmlspecialchars($personPhoto, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($personName, ENT_QUOTES, 'UTF-8') ?>" width="56" height="56" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <span aria-hidden="true"><?= htmlspecialchars($personInitial, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="co-company-person-card__body">
                                    <h3><?= htmlspecialchars($personName, ENT_QUOTES, 'UTF-8') ?></h3>
                                    <?php if ($personRole !== ''): ?><p><?= htmlspecialchars($personRole, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                                    <?php if ($personCity !== ''): ?><p><?= htmlspecialchars($personCity, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                                </div>
                                <?php if ($personId > 0): ?>
                                    <a href="<?= htmlspecialchars($base_url . '/speakers/' . $personId, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary">Profil</a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </article>

        <aside class="co-company-detail__aside" aria-label="Unternehmensdaten">
            <section class="phinit-card co-company-profile-card" id="contact">
                <h2>Details &amp; Kontakt</h2>
                <dl class="co-company-profile-card__facts">
                    <?php if ($companyIndustry !== ''): ?><div><dt>Branche</dt><dd><?= htmlspecialchars($companyIndustry, ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                    <?php if ($companySize !== ''): ?><div><dt>Größe</dt><dd><?= htmlspecialchars($companySize, ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                    <?php if (!empty($company->employee_count)): ?><div><dt>Mitarbeiter</dt><dd><?= number_format((int) $company->employee_count, 0, ',', '.') ?></dd></div><?php endif; ?>
                    <?php if (!empty($company->founded_year)): ?><div><dt>Gegründet</dt><dd><?= (int) $company->founded_year ?></dd></div><?php endif; ?>
                    <?php if ($companyLocation !== ''): ?><div><dt>Standort</dt><dd><?= htmlspecialchars($companyLocation, ENT_QUOTES, 'UTF-8') ?></dd></div><?php endif; ?>
                    <div><dt>Status</dt><dd><?= htmlspecialchars($partnerLabel, ENT_QUOTES, 'UTF-8') ?></dd></div>
                </dl>

                <div class="co-company-profile-card__actions">
                    <?php if ($companyWebsiteUrl !== ''): ?>
                        <a href="<?= htmlspecialchars($companyWebsiteUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="phinit-btn phinit-btn--primary">Website besuchen</a>
                    <?php endif; ?>
                    <?php if ($companyEmail !== ''): ?>
                        <a href="mailto:<?= htmlspecialchars($companyEmail, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary">E-Mail</a>
                    <?php endif; ?>
                    <?php if ($companyPhoneHref !== ''): ?>
                        <a href="tel:<?= htmlspecialchars($companyPhoneHref, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary">Anrufen</a>
                    <?php endif; ?>
                </div>
            </section>

            <?php if (!(int) ($company->user_id ?? 0)): ?>
                <section class="phinit-card co-company-claim-card">
                    <h2>Profil beanspruchen</h2>
                    <p>Dieses Unternehmensprofil wurde redaktionell angelegt. Registrieren Sie sich, um die Verwaltung zu übernehmen.</p>
                    <a href="<?= htmlspecialchars($base_url . '/register', ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--secondary">Jetzt registrieren</a>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</main>
