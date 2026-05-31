<?php
declare(strict_types=1);

/**
 * Template: Single Company (Preview-aligned Detail View)
 *
 * @package CMS_Companies
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('cms_companies_detail_t')) {
    function cms_companies_detail_t(string $key, array $parameters = []): string
    {
        $translated = $key;

        if (class_exists('CMS\\Services\\TranslationService')) {
            try {
                $translated = \CMS\Services\TranslationService::getInstance()->translate($key, 'default', $parameters);
            } catch (\Throwable) {
                $translated = $key;
            }
        }

        if ($translated === $key && function_exists('__')) {
            try {
                $translated = __($key, 'default');
            } catch (\Throwable) {
                $translated = $key;
            }
        }

        if ($translated === $key) {
            $catalogValue = cms_companies_detail_catalog_value($key);
            if ($catalogValue !== null) {
                $translated = $catalogValue;
            }
        }

        if ($parameters !== []) {
            $replace = [];
            foreach ($parameters as $name => $value) {
                $replace['{' . (string) $name . '}'] = (string) $value;
                $replace['%' . (string) $name . '%'] = (string) $value;
            }
            $translated = strtr($translated, $replace);
        }

        return $translated;
    }
}

if (!function_exists('cms_companies_detail_catalog_value')) {
    function cms_companies_detail_catalog_value(string $key): ?string
    {
        $catalog = cms_companies_detail_load_catalog(cms_companies_detail_locale());
        return $catalog['default'][$key] ?? null;
    }
}

if (!function_exists('cms_companies_detail_load_catalog')) {
    function cms_companies_detail_load_catalog(string $locale): array
    {
        static $cache = [];

        if (isset($cache[$locale])) {
            return $cache[$locale];
        }

        $file = cms_companies_detail_lang_file($locale);
        if ($file === '') {
            return $cache[$locale] = [];
        }

        $catalog = [];
        $domain = 'default';
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (preg_match('/^([A-Za-z0-9_.-]+):\s*$/', $trimmed, $matches) === 1) {
                $domain = $matches[1];
                $catalog[$domain] ??= [];
                continue;
            }

            if (preg_match('/^"([^"]+)":\s*"(.*)"\s*$/', $trimmed, $matches) === 1) {
                $catalog[$domain][$matches[1]] = stripcslashes($matches[2]);
            }
        }

        return $cache[$locale] = $catalog;
    }
}

if (!function_exists('cms_companies_detail_lang_file')) {
    function cms_companies_detail_lang_file(string $locale): string
    {
        $candidates = [];

        if (defined('CMS_PATH')) {
            $candidates[] = rtrim((string) CMS_PATH, '\\/') . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.yaml';
        }

        if (defined('ABSPATH')) {
            $base = rtrim((string) ABSPATH, '\\/');
            $candidates[] = $base . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.yaml';
            $candidates[] = $base . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.yaml';
        }

        $candidates[] = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '365CMS.DE' . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.yaml';

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }
}

if (!function_exists('cms_companies_detail_locale')) {
    function cms_companies_detail_locale(): string
    {
        if (class_exists('CMS\\Services\\TranslationService')) {
            try {
                return \CMS\Services\TranslationService::getInstance()->getLocale();
            } catch (\Throwable) {
                // Fallback below.
            }
        }

        $lang = preg_replace('/[^a-zA-Z_]/', '', (string) ($_GET['lang'] ?? ''));
        return $lang !== '' ? $lang : 'de';
    }
}

if (!function_exists('cms_companies_detail_e')) {
    function cms_companies_detail_e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cms_companies_detail_plain')) {
    function cms_companies_detail_plain(mixed $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        return trim($value);
    }
}

if (!function_exists('cms_companies_detail_public_url')) {
    function cms_companies_detail_public_url(?string $url): string
    {
        $url = trim((string) $url);
        if ($url === '' || strlen($url) > 2048) {
            return '';
        }

        if (preg_match('#^https?://#i', $url) === 1) {
            return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
        }

        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return $url;
        }

        return '';
    }
}

if (!function_exists('cms_companies_detail_mailto')) {
    function cms_companies_detail_mailto(string $email, string $subject): string
    {
        $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL) ?: '';
        if ($email === '') {
            return '';
        }

        return 'mailto:' . $email . '?subject=' . rawurlencode($subject);
    }
}

if (!function_exists('cms_companies_detail_initials')) {
    function cms_companies_detail_initials(string $name, string $fallback = 'CO'): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part !== '') {
                $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
            }
            $initialLength = function_exists('mb_strlen') ? mb_strlen($initials) : strlen($initials);
            if ($initialLength >= 2) {
                break;
            }
        }

        $initials = $initials !== '' ? $initials : $fallback;
        return function_exists('mb_strtoupper') ? mb_strtoupper($initials) : strtoupper($initials);
    }
}

if (!function_exists('cms_companies_detail_meta')) {
    function cms_companies_detail_meta(int $companyId, array $keys, mixed $default = null): mixed
    {
        if ($companyId <= 0 || !class_exists('CMS_Companies_Database')) {
            return $default;
        }

        foreach ($keys as $key) {
            try {
                $value = CMS_Companies_Database::instance()->get_meta($companyId, (string) $key, null);
            } catch (\Throwable) {
                $value = null;
            }

            if ($value !== null && $value !== '' && $value !== []) {
                return $value;
            }
        }

        return $default;
    }
}

if (!function_exists('cms_companies_detail_list')) {
    function cms_companies_detail_list(mixed $value): array
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                if (is_array($item)) {
                    $item = $item['title'] ?? $item['name'] ?? $item['label'] ?? $item['value'] ?? '';
                }
                $items[] = cms_companies_detail_plain($item);
            }

            return array_values(array_filter($items));
        }

        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            return cms_companies_detail_list($decoded);
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,|;/', $value) ?: [])));
    }
}

if (!function_exists('cms_companies_detail_company_url')) {
    function cms_companies_detail_company_url(object $company): string
    {
        if (function_exists('cms_company_url')) {
            return cms_company_url($company);
        }

        $base = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        return $base . '/companies/' . (int) ($company->id ?? 0);
    }
}

if (!function_exists('cms_companies_detail_person_url')) {
    function cms_companies_detail_person_url(object $person, string $type): string
    {
        $base = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
        $id = (int) ($person->id ?? 0);
        if ($id <= 0) {
            return '';
        }

        return $base . '/' . ($type === 'speaker' ? 'speakers' : 'experts') . '/' . $id;
    }
}

if (!function_exists('cms_companies_detail_icon')) {
    function cms_companies_detail_icon(string $name): string
    {
        $icons = [
            'arrow' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>',
            'briefcase' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6V5a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v1"/><path d="M4 7h16v12H4z"/><path d="M4 12h16"/></svg>',
            'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 3v4M17 3v4M4 9h16M5 5h14v16H5z"/></svg>',
            'certificate' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.2 2.1 3-.4.6 3 2.6 1.6-1.4 2.7 1.4 2.7-2.6 1.6-.6 3-3-.4L12 21l-2.2-2.1-3 .4-.6-3-2.6-1.6L5 12 3.6 9.3l2.6-1.6.6-3 3 .4z"/><path d="M9 12l2 2 4-4"/></svg>',
            'globe' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z"/><path d="M3 12h18M12 3c2.2 2.5 3.3 5.5 3.3 9S14.2 18.5 12 21c-2.2-2.5-3.3-5.5-3.3-9S9.8 5.5 12 3z"/></svg>',
            'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16v12H4z"/><path d="m4 7 8 6 8-6"/></svg>',
            'map' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s7-5.1 7-11a7 7 0 0 0-14 0c0 5.9 7 11 7 11z"/><circle cx="12" cy="10" r="2.5"/></svg>',
            'phone' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 4l2 5-2 1c1.4 2.9 3.1 4.6 6 6l1-2 5 2-1 4c-8.3-.3-14.7-6.7-15-15z"/></svg>',
            'star' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 2.7 5.5 6.1.9-4.4 4.3 1 6.1L12 17l-5.4 2.8 1-6.1-4.4-4.3 6.1-.9z"/></svg>',
            'tag' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 13 13 20 4 11V4h7z"/><circle cx="8.5" cy="8.5" r="1.5"/></svg>',
            'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 11a4 4 0 1 0-8 0 4 4 0 0 0 8 0z"/><path d="M4 21a8 8 0 0 1 16 0"/><path d="M18 7a3 3 0 0 1 0 6"/></svg>',
        ];

        return $icons[$name] ?? $icons['tag'];
    }
}

$company = $company ?? null;
$experts = is_array($experts ?? null) ? $experts : [];
$speakers = is_array($speakers ?? null) ? $speakers : [];
$related_companies = is_array($related_companies ?? null) ? $related_companies : [];

if (!$company) {
    echo '<div class="co-detail-error">' . cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.empty_company')) . '</div>';
    return;
}

$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$companyId = (int) ($company->id ?? 0);
$companyName = cms_companies_detail_plain($company->name ?? '') ?: cms_companies_detail_t('cms_companies.detail.fallback_company');
$companyDescription = cms_companies_detail_plain($company->description ?? '');
$companyIndustry = cms_companies_detail_plain($company->industry ?? '');
$companySize = cms_companies_detail_plain($company->company_size ?? '');
$companyCity = cms_companies_detail_plain($company->location_city ?? '');
$companyZip = cms_companies_detail_plain($company->location_zip ?? '');
$companyCountry = cms_companies_detail_plain($company->location_country ?? '');
$companyLocation = trim($companyZip . ($companyZip !== '' && $companyCity !== '' ? ' ' : '') . $companyCity);
if ($companyCountry !== '') {
    $companyLocation .= ($companyLocation !== '' ? ', ' : '') . $companyCountry;
}

$logoUrl = cms_companies_detail_public_url((string) ($company->logo_url ?? ''));
$websiteUrl = cms_companies_detail_public_url((string) ($company->website ?? ''));
$companyEmail = filter_var(trim((string) ($company->email ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
$companyPhoneRaw = cms_companies_detail_plain($company->phone ?? '');
$companyPhoneHref = preg_replace('/[^0-9+]/', '', $companyPhoneRaw) ?: '';

$isSponsor = (bool) ($company->is_sponsor ?? false);
$isTopPartner = (bool) ($company->is_top_partner ?? false);
$isPartner = (bool) ($company->is_partner ?? false);
$partnerTier = $isSponsor ? 'sponsor' : ($isTopPartner ? 'top' : ($isPartner ? 'partner' : 'company'));
$partnerLabel = cms_companies_detail_t('cms_companies.detail.status.' . $partnerTier);

$certifications = cms_companies_detail_list(cms_companies_detail_meta($companyId, ['certifications', 'awards'], []));
$serviceMeta = cms_companies_detail_list(cms_companies_detail_meta($companyId, ['services', 'solutions', 'offerings'], []));
$socialLinks = [];
foreach ([
    'linkedin' => ['social_linkedin', 'linkedin'],
    'xing' => ['social_xing', 'xing'],
    'x' => ['social_twitter', 'social_x', 'twitter'],
    'github' => ['social_github', 'github'],
    'mastodon' => ['social_mastodon', 'mastodon'],
] as $network => $keys) {
    $url = cms_companies_detail_public_url((string) cms_companies_detail_meta($companyId, $keys, ''));
    if ($url !== '') {
        $socialLinks[$network] = $url;
    }
}

$heroTags = array_values(array_filter([
    $companyIndustry,
    $companySize,
    $companyLocation,
    $isSponsor || $isTopPartner || $isPartner ? $partnerLabel : '',
]));

$services = [];
foreach ($serviceMeta as $service) {
    $services[] = ['title' => $service, 'text' => cms_companies_detail_t('cms_companies.detail.service_text.custom')];
}
if ($services === [] && $companyIndustry !== '') {
    $services[] = ['title' => cms_companies_detail_t('cms_companies.detail.service.industry'), 'text' => cms_companies_detail_t('cms_companies.detail.service_text.industry', ['industry' => $companyIndustry])];
}
if ($services === [] || $experts !== [] || $speakers !== []) {
    $services[] = ['title' => cms_companies_detail_t('cms_companies.detail.service.network'), 'text' => cms_companies_detail_t('cms_companies.detail.service_text.network', ['count' => (string) (count($experts) + count($speakers))])];
}
if ($services === [] && $websiteUrl !== '') {
    $services[] = ['title' => cms_companies_detail_t('cms_companies.detail.service.presence'), 'text' => cms_companies_detail_t('cms_companies.detail.service_text.presence')];
}

$facts = [];
if ($companyIndustry !== '') {
    $facts[] = [cms_companies_detail_t('cms_companies.detail.label.industry'), $companyIndustry];
}
if ($companySize !== '') {
    $facts[] = [cms_companies_detail_t('cms_companies.detail.label.company_size'), $companySize];
}
if (!empty($company->employee_count)) {
    $facts[] = [cms_companies_detail_t('cms_companies.detail.label.employees'), number_format((int) $company->employee_count, 0, ',', '.')];
}
if (!empty($company->founded_year)) {
    $facts[] = [cms_companies_detail_t('cms_companies.detail.label.founded'), (string) (int) $company->founded_year];
}
if ($companyLocation !== '') {
    $facts[] = [cms_companies_detail_t('cms_companies.detail.label.headquarters'), $companyLocation];
}
$facts[] = [cms_companies_detail_t('cms_companies.detail.label.status'), $partnerLabel];
if ($experts !== [] || $speakers !== []) {
    $facts[] = [cms_companies_detail_t('cms_companies.detail.label.network'), (string) (count($experts) + count($speakers))];
}

$contactHref = $companyEmail !== ''
    ? cms_companies_detail_mailto($companyEmail, cms_companies_detail_t('cms_companies.detail.mail_subject', ['name' => $companyName]))
    : ($baseUrl . '/contact');
?>
<main class="phinit-plugin co-detail" aria-labelledby="co-detail-title">
    <nav class="co-detail-breadcrumb" aria-label="<?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.aria.breadcrumb')) ?>">
        <a href="<?= cms_companies_detail_e($baseUrl !== '' ? $baseUrl : '/') ?>"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.breadcrumb.home')) ?></a>
        <span aria-hidden="true">›</span>
        <a href="<?= cms_companies_detail_e($baseUrl . '/companies') ?>"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.breadcrumb.companies')) ?></a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= cms_companies_detail_e($companyName) ?></span>
    </nav>

    <header class="co-detail-hero">
        <div class="co-detail-status co-detail-status--<?= cms_companies_detail_e($partnerTier) ?>">
            <?= cms_companies_detail_icon('star') ?>
            <span><?= cms_companies_detail_e($partnerLabel) ?></span>
        </div>
        <div class="co-detail-hero__body">
            <div class="co-detail-logo<?= $logoUrl !== '' ? ' co-detail-logo--image' : '' ?>">
                <?php if ($logoUrl !== ''): ?>
                    <img src="<?= cms_companies_detail_e($logoUrl) ?>" alt="<?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.logo_alt', ['name' => $companyName])) ?>" width="112" height="112" loading="eager" decoding="async">
                <?php else: ?>
                    <span aria-hidden="true"><?= cms_companies_detail_e(cms_companies_detail_initials($companyName)) ?></span>
                <?php endif; ?>
            </div>
            <div class="co-detail-hero__copy">
                <p class="co-detail-kicker"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.kicker')) ?></p>
                <div class="co-detail-name-row">
                    <h1 id="co-detail-title"><?= cms_companies_detail_e($companyName) ?></h1>
                    <?php if ($isSponsor || $isTopPartner || $isPartner): ?>
                        <span class="co-detail-pill co-detail-pill--tier"><?= cms_companies_detail_e($partnerLabel) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($companyIndustry !== '' || $companyLocation !== ''): ?>
                    <p class="co-detail-role"><?= cms_companies_detail_e(trim($companyIndustry . ($companyIndustry !== '' && $companyLocation !== '' ? ' · ' : '') . $companyLocation)) ?></p>
                <?php endif; ?>
                <?php if ($heroTags !== []): ?>
                    <ul class="co-detail-tags" aria-label="<?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.aria.hero_tags')) ?>">
                        <?php foreach ($heroTags as $tag): ?>
                            <li><?= cms_companies_detail_e($tag) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="co-detail-grid">
        <article class="co-detail-main">
            <section class="co-detail-card" aria-labelledby="co-about-heading">
                <p class="co-detail-section-label"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.section.profile')) ?></p>
                <h2 id="co-about-heading"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.heading_about')) ?></h2>
                <?php if ($companyDescription !== ''): ?>
                    <div class="co-detail-copy">
                        <p><?= cms_companies_detail_e($companyDescription) ?></p>
                    </div>
                <?php else: ?>
                    <p class="co-detail-empty"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.empty_description')) ?></p>
                <?php endif; ?>
            </section>

            <section class="co-detail-card" aria-labelledby="co-services-heading">
                <p class="co-detail-section-label"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.section.capabilities')) ?></p>
                <h2 id="co-services-heading"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.heading_services')) ?></h2>
                <?php if ($services !== []): ?>
                    <div class="co-detail-services">
                        <?php foreach (array_slice($services, 0, 4) as $service): ?>
                            <article class="co-detail-service">
                                <div class="co-detail-service__icon"><?= cms_companies_detail_icon('briefcase') ?></div>
                                <h3><?= cms_companies_detail_e((string) $service['title']) ?></h3>
                                <p><?= cms_companies_detail_e((string) $service['text']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="co-detail-empty"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.empty_services')) ?></p>
                <?php endif; ?>
            </section>

            <section class="co-detail-card" aria-labelledby="co-team-heading">
                <p class="co-detail-section-label"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.section.network')) ?></p>
                <h2 id="co-team-heading"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.heading_team')) ?></h2>
                <?php if ($experts !== [] || $speakers !== []): ?>
                    <div class="co-detail-team">
                        <?php foreach ($experts as $expert): ?>
                            <?php
                            $firstName = cms_companies_detail_plain($expert->first_name ?? '');
                            $lastName = cms_companies_detail_plain($expert->last_name ?? '');
                            $personName = trim($firstName . ' ' . $lastName) ?: cms_companies_detail_t('cms_companies.detail.fallback_expert');
                            $personRole = cms_companies_detail_plain($expert->role ?? $expert->position ?? '');
                            $personPhoto = cms_companies_detail_public_url((string) ($expert->photo_url ?? $expert->avatar_url ?? ''));
                            $personUrl = cms_companies_detail_person_url($expert, 'expert');
                            ?>
                            <article class="co-detail-person">
                                <div class="co-detail-person__avatar<?= $personPhoto !== '' ? ' co-detail-person__avatar--image' : '' ?>">
                                    <?php if ($personPhoto !== ''): ?>
                                        <img src="<?= cms_companies_detail_e($personPhoto) ?>" alt="<?= cms_companies_detail_e($personName) ?>" width="64" height="64" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <span aria-hidden="true"><?= cms_companies_detail_e(cms_companies_detail_initials($personName, 'EX')) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3><?= cms_companies_detail_e($personName) ?></h3>
                                    <?php if ($personRole !== ''): ?><p><?= cms_companies_detail_e($personRole) ?></p><?php endif; ?>
                                    <a href="<?= cms_companies_detail_e($personUrl) ?>"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.button_profile')) ?></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                        <?php foreach ($speakers as $speaker): ?>
                            <?php
                            $firstName = cms_companies_detail_plain($speaker->first_name ?? '');
                            $lastName = cms_companies_detail_plain($speaker->last_name ?? '');
                            $personName = trim($firstName . ' ' . $lastName) ?: cms_companies_detail_t('cms_companies.detail.fallback_speaker');
                            $personRole = cms_companies_detail_plain($speaker->position ?? '');
                            $personPhoto = cms_companies_detail_public_url((string) ($speaker->photo_url ?? $speaker->avatar_url ?? ''));
                            $personUrl = cms_companies_detail_person_url($speaker, 'speaker');
                            ?>
                            <article class="co-detail-person co-detail-person--speaker">
                                <div class="co-detail-person__avatar<?= $personPhoto !== '' ? ' co-detail-person__avatar--image' : '' ?>">
                                    <?php if ($personPhoto !== ''): ?>
                                        <img src="<?= cms_companies_detail_e($personPhoto) ?>" alt="<?= cms_companies_detail_e($personName) ?>" width="64" height="64" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <span aria-hidden="true"><?= cms_companies_detail_e(cms_companies_detail_initials($personName, 'SP')) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h3><?= cms_companies_detail_e($personName) ?></h3>
                                    <?php if ($personRole !== ''): ?><p><?= cms_companies_detail_e($personRole) ?></p><?php endif; ?>
                                    <a href="<?= cms_companies_detail_e($personUrl) ?>"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.button_profile')) ?></a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="co-detail-empty"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.empty_team')) ?></p>
                <?php endif; ?>
            </section>

            <section class="co-detail-card" aria-labelledby="co-partners-heading">
                <p class="co-detail-section-label"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.section.trust')) ?></p>
                <h2 id="co-partners-heading"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.heading_partnerships')) ?></h2>
                <div class="co-detail-partners">
                    <div class="co-detail-partner">
                        <div><?= cms_companies_detail_icon('star') ?></div>
                        <strong><?= cms_companies_detail_e($partnerLabel) ?></strong>
                        <span><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.partner_text.' . $partnerTier)) ?></span>
                    </div>
                    <?php foreach ($certifications as $certification): ?>
                        <div class="co-detail-partner">
                            <div><?= cms_companies_detail_icon('certificate') ?></div>
                            <strong><?= cms_companies_detail_e($certification) ?></strong>
                            <span><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.certification_hint')) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </article>

        <aside class="co-detail-sidebar" aria-label="<?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.aria.sidebar')) ?>">
            <section class="co-detail-card co-detail-cta" id="contact">
                <h2><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.request_title', ['name' => $companyName])) ?></h2>
                <p><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.request_text')) ?></p>
                <a class="co-detail-button co-detail-button--primary" href="<?= cms_companies_detail_e($contactHref) ?>">
                    <?= cms_companies_detail_icon('mail') ?>
                    <span><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.button_contact')) ?></span>
                </a>
                <?php if ($websiteUrl !== ''): ?>
                    <a class="co-detail-button co-detail-button--ghost" href="<?= cms_companies_detail_e($websiteUrl) ?>" target="_blank" rel="noopener noreferrer">
                        <?= cms_companies_detail_icon('globe') ?>
                        <span><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.button_website')) ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($companyPhoneHref !== ''): ?>
                    <a class="co-detail-button co-detail-button--ghost" href="tel:<?= cms_companies_detail_e($companyPhoneHref) ?>">
                        <?= cms_companies_detail_icon('phone') ?>
                        <span><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.button_phone')) ?></span>
                    </a>
                <?php endif; ?>
            </section>

            <?php if ($websiteUrl !== '' || $socialLinks !== []): ?>
                <section class="co-detail-card" aria-labelledby="co-social-heading">
                    <h2 id="co-social-heading"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.heading_social')) ?></h2>
                    <div class="co-detail-social" aria-label="<?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.aria.social_links')) ?>">
                        <?php if ($websiteUrl !== ''): ?>
                            <a href="<?= cms_companies_detail_e($websiteUrl) ?>" target="_blank" rel="noopener noreferrer"><?= cms_companies_detail_icon('globe') ?><span><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.social.website')) ?></span></a>
                        <?php endif; ?>
                        <?php foreach ($socialLinks as $network => $url): ?>
                            <a href="<?= cms_companies_detail_e($url) ?>" target="_blank" rel="noopener noreferrer"><?= cms_companies_detail_icon('globe') ?><span><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.social.' . $network)) ?></span></a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section class="co-detail-card" aria-labelledby="co-facts-heading">
                <h2 id="co-facts-heading"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.heading_details')) ?></h2>
                <dl class="co-detail-facts">
                    <?php foreach ($facts as [$label, $value]): ?>
                        <div>
                            <dt><?= cms_companies_detail_e($label) ?></dt>
                            <dd><?= cms_companies_detail_e($value) ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </section>

            <?php if ($companyLocation !== ''): ?>
                <section class="co-detail-card" aria-labelledby="co-locations-heading">
                    <h2 id="co-locations-heading"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.heading_locations')) ?></h2>
                    <div class="co-detail-location">
                        <div><?= cms_companies_detail_icon('map') ?></div>
                        <div>
                            <strong><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.location_headquarters')) ?></strong>
                            <span><?= cms_companies_detail_e($companyLocation) ?></span>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($related_companies !== []): ?>
                <section class="co-detail-card" aria-labelledby="co-related-heading">
                    <h2 id="co-related-heading"><?= cms_companies_detail_e(cms_companies_detail_t('cms_companies.detail.heading_related')) ?></h2>
                    <div class="co-detail-related">
                        <?php foreach ($related_companies as $related): ?>
                            <?php
                            $relatedName = cms_companies_detail_plain($related->name ?? '') ?: cms_companies_detail_t('cms_companies.detail.fallback_company');
                            $relatedIndustry = cms_companies_detail_plain($related->industry ?? '');
                            ?>
                            <a href="<?= cms_companies_detail_e(cms_companies_detail_company_url($related)) ?>">
                                <span><?= cms_companies_detail_e(cms_companies_detail_initials($relatedName)) ?></span>
                                <strong><?= cms_companies_detail_e($relatedName) ?></strong>
                                <?php if ($relatedIndustry !== ''): ?><em><?= cms_companies_detail_e($relatedIndustry) ?></em><?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</main>