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

if (is_array($company)) {
    $company = (object) $company;
}

if (!function_exists('cms_companies_view_lowercase')) {
    function cms_companies_view_lowercase(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }
}

if (!function_exists('cms_companies_view_uppercase')) {
    function cms_companies_view_uppercase(string $value): string
    {
        return function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8')
            : strtoupper($value);
    }
}

if (!function_exists('cms_companies_view_substr')) {
    function cms_companies_view_substr(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, $start, $length, 'UTF-8')
            : substr($value, $start, $length);
    }
}

if (!function_exists('cms_companies_view_initials')) {
    function cms_companies_view_initials(string $name): string
    {
        $letters = '';
        $parts = array_values(array_filter(preg_split('/\s+/', trim($name)) ?: []));
        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= cms_companies_view_substr($part, 0, 1);
        }

        $letters = cms_companies_view_uppercase($letters);
        return $letters !== '' ? $letters : 'CO';
    }
}

if (!function_exists('cms_companies_view_trim_text')) {
    function cms_companies_view_trim_text(string $text, int $length): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        if ($text === '' || $length <= 0) {
            return '';
        }

        $currentLength = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        if ($currentLength <= $length) {
            return $text;
        }

        return rtrim(cms_companies_view_substr($text, 0, max(0, $length - 1)), " \t\n\r\0\x0B.,;:-") . '…';
    }
}

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
$companyNameRaw = trim((string) ($company->name ?? '')) ?: 'Unternehmen';
$companyName = htmlspecialchars($companyNameRaw, ENT_QUOTES, 'UTF-8');
$companyUrl = function_exists('cms_company_url') ? cms_company_url($company) : '#';
$initials = cms_companies_view_initials($companyNameRaw);

// Partner-Tier bestimmen
$is_sponsor     = (bool)($company->is_sponsor     ?? false);
$is_top_partner = (bool)($company->is_top_partner ?? false);
$is_partner     = (bool)($company->is_partner     ?? false);
$partnerLabel = '';
$partnerClass = '';
if ($is_sponsor) {
    $partnerLabel = 'Sponsor';
    $partnerClass = 'sponsor';
} elseif ($is_top_partner) {
    $partnerLabel = 'Top-Partner';
    $partnerClass = 'top';
} elseif ($is_partner) {
    $partnerLabel = 'Partner';
    $partnerClass = 'partner';
}

// Excerpt
$excerpt = cms_companies_view_trim_text((string) ($company->description ?? ''), 170);

// Industrie-Label (slug → lesbarer Name, Fallback = slug selbst)
$industry_label = trim((string) ($company->industry ?? ''));
$city = trim((string) ($company->location_city ?? ''));
$country = trim((string) ($company->location_country ?? ''));
$locationText = trim($city . (($city !== '' && $country !== '') ? ', ' : '') . $country);
$teamLabel = '';
if (!empty($company->employee_count)) {
    $teamLabel = number_format((int) $company->employee_count, 0, ',', '.') . ' Mitarbeitende';
} elseif (!empty($company->company_size)) {
    $teamLabel = trim((string) $company->company_size);
}
$foundedYear = (int) ($company->founded_year ?? 0);
$filterText = cms_companies_view_lowercase(trim($companyNameRaw . ' ' . $industry_label . ' ' . $locationText . ' ' . $teamLabel . ' ' . $partnerLabel . ' ' . $excerpt));
?>

<article class="phinit-card co-card company-overview-card<?= $partnerClass !== '' ? ' company-overview-card--' . htmlspecialchars($partnerClass, ENT_QUOTES, 'UTF-8') : '' ?>"
         data-company-card
         data-company-url="<?= htmlspecialchars($companyUrl, ENT_QUOTES, 'UTF-8') ?>"
         data-name="<?= htmlspecialchars($filterText, ENT_QUOTES, 'UTF-8') ?>"
         role="link"
         tabindex="0"
         aria-label="Details zu <?= $companyName ?> ansehen">
    <div class="company-card-body">
        <div class="company-card-badge-row">
            <div class="company-card-badge-group" aria-label="Unternehmensmerkmale">
                <?php if ($partnerLabel !== ''): ?>
                    <span class="company-badge company-badge--<?= htmlspecialchars($partnerClass, ENT_QUOTES, 'UTF-8') ?>"><i class="ti ti-rosette" aria-hidden="true"></i><?= htmlspecialchars($partnerLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if ($show_industry && $industry_label !== ''): ?>
                    <span class="company-badge company-badge--industry"><?= htmlspecialchars($industry_label, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <?php if ($foundedYear > 0): ?>
                <span class="company-founded"><i class="ti ti-calendar-stats" aria-hidden="true"></i>Seit <?= (int) $foundedYear ?></span>
            <?php endif; ?>
        </div>

        <div class="co-card-head company-card-main">
            <a class="company-card-logo-link" href="<?= htmlspecialchars($companyUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Details zu <?= $companyName ?> ansehen" tabindex="-1">
                <span class="co-card-avatar company-card-logo<?= $logo_url !== '' ? ' company-card-logo--image' : ' company-card-logo--placeholder' ?>">
                    <?php if ($logo_url !== ''): ?>
                        <img src="<?= htmlspecialchars($logo_url, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $companyName ?> Logo" width="72" height="72" loading="lazy" decoding="async">
                    <?php else: ?>
                        <span aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </span>
            </a>

            <div class="co-card-identity company-card-content">
                <h2 class="co-card-name company-card-title"><a href="<?= htmlspecialchars($companyUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $companyName ?></a></h2>
                <?php if (($show_city && $locationText !== '') || ($show_employees && $teamLabel !== '')): ?>
                    <p class="company-card-meta-line">
                        <?php if ($show_city && $locationText !== ''): ?>
                            <span><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if ($show_employees && $teamLabel !== ''): ?>
                            <span><i class="ti ti-users" aria-hidden="true"></i><?= htmlspecialchars($teamLabel, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($excerpt !== ''): ?>
            <p class="co-card-excerpt company-card-excerpt"><?= htmlspecialchars($excerpt, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <footer class="co-card-footer company-card-footer">
            <div class="company-card-footer-left">
                <?php if ($show_website && $website_url !== ''): ?>
                    <a href="<?= htmlspecialchars($website_url, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="company-footer-link" aria-label="Website von <?= $companyName ?> öffnen"><i class="ti ti-world" aria-hidden="true"></i>Website</a>
                <?php elseif ($industry_label !== ''): ?>
                    <span class="company-footer-info"><i class="ti ti-building" aria-hidden="true"></i><?= htmlspecialchars($industry_label, ENT_QUOTES, 'UTF-8') ?></span>
                <?php elseif ($locationText !== ''): ?>
                    <span class="company-footer-info"><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <a href="<?= htmlspecialchars($companyUrl, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--primary co-btn co-btn-primary company-card-button" tabindex="-1">Details</a>
        </footer>
    </div>
</article>

