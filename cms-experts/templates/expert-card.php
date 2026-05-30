<?php
declare(strict_types=1);

/**
 * Expert Card Component – Event-style PHINIT public card.
 *
 * @package CMS_Experts
 * @var object|array $expert
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!isset($expert)) {
    return;
}

$expert = is_array($expert) ? (object) $expert : (is_object($expert) ? $expert : (object) []);

if (!function_exists('cms_experts_view_lowercase')) {
    function cms_experts_view_lowercase(string $value): string
    {
        return function_exists('mb_strtolower')
            ? mb_strtolower($value, 'UTF-8')
            : strtolower($value);
    }
}

if (!function_exists('cms_experts_view_uppercase')) {
    function cms_experts_view_uppercase(string $value): string
    {
        return function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8')
            : strtoupper($value);
    }
}

if (!function_exists('cms_experts_view_substr')) {
    function cms_experts_view_substr(string $value, int $start, int $length): string
    {
        return function_exists('mb_substr')
            ? mb_substr($value, $start, $length, 'UTF-8')
            : substr($value, $start, $length);
    }
}

if (!function_exists('cms_experts_view_initials')) {
    function cms_experts_view_initials(string $firstName, string $lastName, string $fallbackName): string
    {
        $letters = '';
        foreach ([$firstName, $lastName] as $part) {
            $part = trim($part);
            if ($part !== '') {
                $letters .= cms_experts_view_substr($part, 0, 1);
            }
        }

        if ($letters === '') {
            foreach (array_slice(array_values(array_filter(preg_split('/\s+/', trim($fallbackName)) ?: [])), 0, 2) as $part) {
                $letters .= cms_experts_view_substr((string) $part, 0, 1);
            }
        }

        $letters = cms_experts_view_uppercase($letters);
        return $letters !== '' ? $letters : 'EX';
    }
}

if (!function_exists('cms_experts_view_trim_text')) {
    function cms_experts_view_trim_text(string $text, int $length): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        if ($text === '' || $length <= 0) {
            return '';
        }

        $currentLength = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        if ($currentLength <= $length) {
            return $text;
        }

        return rtrim(cms_experts_view_substr($text, 0, max(0, $length - 1)), " \t\n\r\0\x0B.,;:-") . '…';
    }
}

if (!function_exists('cms_experts_view_array_values')) {
    function cms_experts_view_array_values(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                static function ($item): string {
                    if (is_array($item)) {
                        return trim((string) ($item['name'] ?? $item['skill_name'] ?? ''));
                    }

                    if (is_object($item)) {
                        return trim((string) ($item->name ?? $item->skill_name ?? ''));
                    }

                    return trim((string) $item);
                },
                $value
            ), static fn(string $item): bool => $item !== ''));
        }

        $raw = trim((string) $value);
        return $raw !== '' ? array_values(array_filter(array_map('trim', explode(',', $raw)))) : [];
    }
}

if (!isset($settings) || !is_array($settings)) {
    $settings = [];
}

$id = (int) ($expert->id ?? 0);
$slug = !empty($expert->slug) ? (string) $expert->slug : CMS_Experts_Database::generate_slug($expert);
$url = rtrim((string) SITE_URL, '/') . '/experts/' . rawurlencode($slug);
$firstNameRaw = trim((string) ($expert->first_name ?? ''));
$lastNameRaw = trim((string) ($expert->last_name ?? ''));
$fullNameRaw = trim($firstNameRaw . ' ' . $lastNameRaw) ?: 'Expertin oder Experte';
$fullName = htmlspecialchars($fullNameRaw, ENT_QUOTES, 'UTF-8');
$jobTitleRaw = trim((string) ($expert->position ?? ''));
$companyNameRaw = trim((string) ($expert->company_name ?? $expert->company ?? ''));
$social = isset($expert->_social) && is_array($expert->_social) ? $expert->_social : [];
$companyId = (int) ($social['company_id'] ?? 0);
$companyUrl = '';
if ($companyId > 0 && $companyNameRaw !== '') {
    $companySlug = cms_experts_view_lowercase(str_replace(['ä','ö','ü','ß','Ä','Ö','Ü'], ['ae','oe','ue','ss','ae','oe','ue'], $companyNameRaw));
    $companySlug = trim((string) preg_replace('/[^a-z0-9]+/i', '-', $companySlug), '-');
    $companyUrl = rtrim((string) SITE_URL, '/') . '/company/' . $companySlug . '-' . $companyId;
}

$photo = cms_experts_public_url((string) ($expert->photo_url ?? ''));
$cityRaw = trim((string) ($expert->location_city ?? ''));
$countryRaw = trim((string) ($expert->location_country ?? ''));
$locationText = trim($cityRaw . (($cityRaw !== '' && $countryRaw !== '') ? ', ' : '') . $countryRaw);
$availability = (string) ($expert->availability ?? 'available');
$availabilityLabels = [
    'available' => 'Verfügbar',
    'limited'   => 'Begrenzt',
    'booked'    => 'Ausgebucht',
];
$availabilityLabel = $availabilityLabels[$availability] ?? ucfirst($availability);
$availabilityClass = in_array($availability, ['available', 'limited', 'booked'], true) ? $availability : 'unknown';
$experienceYears = max(0, (int) ($expert->experience_years ?? 0));
$certCount = max(0, (int) ($expert->_cert_count ?? 0));
$website = cms_experts_public_url((string) ($social['social_website'] ?? ''));
$showSpecs = ($settings['design_show_specialization'] ?? '1') === '1';
$showSkills = ($settings['design_show_skills'] ?? '1') === '1';
$showCity = ($settings['design_show_city'] ?? '1') === '1';
$specializations = $showSpecs ? cms_experts_view_array_values($expert->_specializations ?? []) : [];
$skills = $showSkills ? cms_experts_view_array_values($expert->_skills ?? ($expert->skills ?? [])) : [];
$specializationLabel = implode(' · ', array_slice($specializations, 0, 2));
$bioExcerpt = cms_experts_view_trim_text((string) ($expert->biography ?? ''), 150);
$initials = cms_experts_view_initials($firstNameRaw, $lastNameRaw, $fullNameRaw);

$highlightLabel = '';
$highlightClass = '';
if (!empty($social['is_mvp'])) {
    $highlightLabel = 'MVP';
    $highlightClass = 'mvp';
} elseif (!empty($social['is_premium'])) {
    $highlightLabel = 'Premium';
    $highlightClass = 'premium';
} elseif (trim((string) ($social['custom_award'] ?? '')) !== '') {
    $highlightLabel = trim((string) $social['custom_award']);
    $highlightClass = 'award';
} elseif (!empty($expert->is_certified) || $certCount > 0) {
    $highlightLabel = 'Zertifiziert';
    $highlightClass = 'certified';
}

$filterText = cms_experts_view_lowercase(trim(implode(' ', array_filter([
    $fullNameRaw,
    $jobTitleRaw,
    $companyNameRaw,
    $locationText,
    $availabilityLabel,
    $highlightLabel,
    $specializationLabel,
    implode(' ', $skills),
]))));
?>
<article class="phinit-card expert-card expert-card--overview expert-overview-card<?= $highlightClass !== '' ? ' expert-overview-card--' . htmlspecialchars($highlightClass, ENT_QUOTES, 'UTF-8') : '' ?>"
         data-expert-card
         data-expert-url="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
         data-name="<?= htmlspecialchars($filterText, ENT_QUOTES, 'UTF-8') ?>"
         role="link"
         tabindex="0"
         aria-label="Profil von <?= $fullName ?> ansehen">
    <div class="expert-card-body expert-overview-card__body">
        <div class="expert-card-badge-row">
            <div class="expert-card-badge-group" aria-label="Expertenmerkmale">
                <?php if ($highlightLabel !== ''): ?>
                    <span class="expert-badge expert-badge--<?= htmlspecialchars($highlightClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($highlightLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if ($specializationLabel !== ''): ?>
                    <span class="expert-badge expert-badge--specialization"><?= htmlspecialchars($specializationLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <span class="expert-availability expert-availability--<?= htmlspecialchars($availabilityClass, ENT_QUOTES, 'UTF-8') ?>"><i class="ti ti-calendar-check" aria-hidden="true"></i><?= htmlspecialchars($availabilityLabel, ENT_QUOTES, 'UTF-8') ?></span>
        </div>

        <header class="expert-card-top expert-card-main">
            <a class="expert-card-avatar-link" href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" aria-label="Profil von <?= $fullName ?> ansehen" tabindex="-1">
                <span class="expert-card-avatar<?= $photo !== '' ? ' expert-card-avatar--image' : ' expert-card-avatar--placeholder' ?>">
                    <?php if ($photo !== ''): ?>
                        <img src="<?= htmlspecialchars($photo, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $fullName ?>" width="72" height="72" loading="lazy" decoding="async">
                    <?php else: ?>
                        <span aria-hidden="true"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </span>
            </a>

            <div class="expert-card-header-text expert-card-content">
                <h2 class="expert-card-name expert-card-title"><a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"><?= $fullName ?></a></h2>
                <?php if ($jobTitleRaw !== ''): ?>
                    <p class="expert-card-title-text"><?= htmlspecialchars($jobTitleRaw, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <?php if (($showCity && $locationText !== '') || $companyNameRaw !== ''): ?>
                    <p class="expert-card-meta-line">
                        <?php if ($showCity && $locationText !== ''): ?>
                            <span><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if ($companyNameRaw !== ''): ?>
                            <span><i class="ti ti-building" aria-hidden="true"></i><?php if ($companyUrl !== ''): ?><a href="<?= htmlspecialchars($companyUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($companyNameRaw, ENT_QUOTES, 'UTF-8') ?></a><?php else: ?><?= htmlspecialchars($companyNameRaw, ENT_QUOTES, 'UTF-8') ?><?php endif; ?></span>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
        </header>

        <div class="expert-card-stat-row" aria-label="Expertenkennzahlen">
            <?php if ($experienceYears > 0): ?>
                <span><strong><?= (int) $experienceYears ?></strong> Jahre Erfahrung</span>
            <?php endif; ?>
            <?php if ($certCount > 0): ?>
                <span><strong><?= (int) $certCount ?></strong> Zertifikat<?= $certCount === 1 ? '' : 'e' ?></span>
            <?php endif; ?>
            <?php if ($experienceYears <= 0 && $certCount <= 0 && $specializationLabel !== ''): ?>
                <span><strong>Fokus</strong> <?= htmlspecialchars($specializationLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($skills)): ?>
            <div class="expert-card-skills" aria-label="Skills">
                <?php foreach (array_slice($skills, 0, 4) as $skill): ?>
                    <span class="skill-pill"><?= htmlspecialchars((string) $skill, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
            </div>
        <?php elseif ($bioExcerpt !== ''): ?>
            <p class="expert-card-excerpt"><?= htmlspecialchars($bioExcerpt, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>

        <footer class="expert-card-footer">
            <div class="expert-card-footer-left">
                <?php if ($website !== ''): ?>
                    <a href="<?= htmlspecialchars($website, ENT_QUOTES, 'UTF-8') ?>" class="expert-footer-link" target="_blank" rel="noopener noreferrer" aria-label="Website von <?= $fullName ?> öffnen"><i class="ti ti-world" aria-hidden="true"></i>Website</a>
                <?php elseif ($specializationLabel !== ''): ?>
                    <span class="expert-footer-info"><i class="ti ti-tag" aria-hidden="true"></i><?= htmlspecialchars($specializationLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php elseif ($companyNameRaw !== ''): ?>
                    <span class="expert-footer-info"><i class="ti ti-building" aria-hidden="true"></i><?= htmlspecialchars($companyNameRaw, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
            </div>
            <a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--primary expert-card-cta" tabindex="-1">Profil</a>
        </footer>
    </div>
</article>
