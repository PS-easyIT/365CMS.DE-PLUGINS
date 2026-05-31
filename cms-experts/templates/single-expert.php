<?php
/**
 * Single Expert Template – PHINIT-style detail page.
 *
 * @package CMS_Experts
 * @var object $expert
 * @var array  $skills
 * @var array  $certifications
 * @var array  $projects
 * @var array  $education
 * @var array  $meta
 * @var array  $specializations
 * @var array  $events
 * @var array  $related_experts
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (empty($expert)) {
    return;
}

if (!function_exists('cms_experts_detail_t')) {
    function cms_experts_detail_t(string $key, array $parameters = []): string
    {
        $translated = null;

        if (class_exists('CMS\\Services\\TranslationService')) {
            try {
                $translated = \CMS\Services\TranslationService::getInstance()->translate($key, 'default', $parameters);
            } catch (\Throwable) {
                $translated = null;
            }
        }

        if (($translated === null || $translated === $key) && function_exists('__')) {
            try {
                $translated = __($key);
            } catch (\Throwable) {
                $translated = null;
            }
        }

        if ($translated === null || $translated === $key) {
            $translated = cms_experts_detail_catalog_value($key) ?? $key;
        }

        if ($parameters !== []) {
            $translated = strtr($translated, $parameters);
        }

        return $translated;
    }
}

if (!function_exists('cms_experts_detail_catalog_value')) {
    function cms_experts_detail_catalog_value(string $key): ?string
    {
        static $catalogs = [];

        $locale = cms_experts_detail_locale();
        if (!array_key_exists($locale, $catalogs)) {
            $catalogs[$locale] = cms_experts_detail_load_catalog($locale);
        }

        return $catalogs[$locale][$key] ?? null;
    }
}

if (!function_exists('cms_experts_detail_load_catalog')) {
    function cms_experts_detail_load_catalog(string $locale): array
    {
        $file = cms_experts_detail_lang_file($locale);
        if ($file === '') {
            return [];
        }

        $catalog = [];
        $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#') || $trimmed === 'default:') {
                continue;
            }

            if (preg_match('/^\s*["\'](.+?)["\']\s*:\s*["\'](.*)["\']\s*$/', $line, $matches) === 1) {
                $catalog[$matches[1]] = stripcslashes($matches[2]);
            }
        }

        return $catalog;
    }
}

if (!function_exists('cms_experts_detail_lang_file')) {
    function cms_experts_detail_lang_file(string $locale): string
    {
        $locale = str_starts_with($locale, 'en') ? 'en' : 'de';
        $candidates = [];

        if (defined('ABSPATH')) {
            $candidates[] = rtrim((string) ABSPATH, '/\\') . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.yaml';
        }
        if (defined('CMS_PATH')) {
            $candidates[] = rtrim((string) CMS_PATH, '/\\') . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.yaml';
        }

        $dir = __DIR__;
        for ($i = 0; $i < 8; $i++) {
            $candidates[] = $dir . DIRECTORY_SEPARATOR . 'CMS' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $locale . '.yaml';
            $parent = dirname($dir);
            if ($parent === $dir) {
                break;
            }
            $dir = $parent;
        }

        foreach (array_unique($candidates) as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return '';
    }
}

if (!function_exists('cms_experts_detail_locale')) {
    function cms_experts_detail_locale(): string
    {
        if (class_exists('CMS\\Services\\TranslationService')) {
            try {
                $locale = \CMS\Services\TranslationService::getInstance()->getLocale();
                return str_starts_with($locale, 'en') ? 'en' : 'de';
            } catch (\Throwable) {
                return 'de';
            }
        }

        return 'de';
    }
}

if (!function_exists('cms_experts_detail_e')) {
    function cms_experts_detail_e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cms_experts_detail_plain')) {
    function cms_experts_detail_plain(mixed $value): string
    {
        $text = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(strip_tags($text));
        return preg_replace('/[ \t]+/', ' ', $text) ?? $text;
    }
}

if (!function_exists('cms_experts_detail_public_url')) {
    function cms_experts_detail_public_url(mixed $url): string
    {
        $url = str_replace('\\', '/', trim((string) $url));
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return '';
        }

        if (str_starts_with($url, '/') || preg_match('#^(uploads|ASSETS|assets|plugins)/#i', $url) === 1) {
            $path = ltrim($url, '/');
            if ($path === '' || str_contains($path, '..')) {
                return '';
            }

            $baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
            return $baseUrl . '/' . $path;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || !empty($parts['user']) || !empty($parts['pass'])) {
            return '';
        }

        $scheme = strtolower((string) $parts['scheme']);
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $host = strtolower(trim((string) $parts['host'], '[]'));
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return '';
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return '';
        }

        return $url;
    }
}

if (!function_exists('cms_experts_detail_social_url')) {
    function cms_experts_detail_social_url(mixed $value, string $network): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        $url = cms_experts_detail_public_url($raw);
        if ($url !== '') {
            return $url;
        }

        $handle = ltrim($raw, '@');
        if (preg_match('/^[A-Za-z0-9_.-]{1,80}$/', $handle) !== 1) {
            return '';
        }

        return match ($network) {
            'x' => 'https://x.com/' . rawurlencode($handle),
            'github' => 'https://github.com/' . rawurlencode($handle),
            'gitlab' => 'https://gitlab.com/' . rawurlencode($handle),
            default => '',
        };
    }
}

if (!function_exists('cms_experts_detail_value')) {
    function cms_experts_detail_value(mixed $item, array $keys, mixed $default = ''): mixed
    {
        foreach ($keys as $key) {
            if (is_array($item) && array_key_exists($key, $item)) {
                return $item[$key];
            }
            if (is_object($item) && isset($item->{$key})) {
                return $item->{$key};
            }
        }

        return $default;
    }
}

if (!function_exists('cms_experts_detail_list')) {
    function cms_experts_detail_list(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $value);
        }

        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static function ($item): bool {
            if (is_array($item) || is_object($item)) {
                return trim((string) cms_experts_detail_value($item, ['name', 'title', 'skill_name', 'label', 'value'], '')) !== '';
            }

            return trim((string) $item) !== '';
        }));
    }
}

if (!function_exists('cms_experts_detail_label_list')) {
    function cms_experts_detail_label_list(mixed $value): array
    {
        $labels = [];
        foreach (cms_experts_detail_list($value) as $item) {
            $label = is_array($item) || is_object($item)
                ? (string) cms_experts_detail_value($item, ['name', 'title', 'skill_name', 'label', 'value'], '')
                : (string) $item;
            $label = trim($label);
            if ($label !== '') {
                $labels[$label] = $label;
            }
        }

        return array_values($labels);
    }
}

if (!function_exists('cms_experts_detail_initials')) {
    function cms_experts_detail_initials(string $firstName, string $lastName, string $fallbackName = ''): string
    {
        $letters = '';
        foreach ([$firstName, $lastName] as $part) {
            $part = trim($part);
            if ($part !== '') {
                $letters .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
            }
        }

        if ($letters === '' && $fallbackName !== '') {
            $words = array_values(array_filter(preg_split('/\s+/', trim($fallbackName)) ?: []));
            foreach (array_slice($words, 0, 2) as $word) {
                $letters .= function_exists('mb_substr') ? mb_substr($word, 0, 1, 'UTF-8') : substr($word, 0, 1);
            }
        }

        $letters = function_exists('mb_strtoupper') ? mb_strtoupper($letters, 'UTF-8') : strtoupper($letters);
        return $letters !== '' ? $letters : 'EX';
    }
}

if (!function_exists('cms_experts_detail_slug')) {
    function cms_experts_detail_slug(object $expert): string
    {
        if (class_exists('CMS_Experts_Database')) {
            return CMS_Experts_Database::generate_slug($expert);
        }

        $name = trim((string) (($expert->first_name ?? '') . ' ' . ($expert->last_name ?? '')));
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', strtr($name, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue'])));
        return (trim($slug, '-') ?: 'expert') . '-' . (int) ($expert->id ?? 0);
    }
}

if (!function_exists('cms_experts_detail_level_percent')) {
    function cms_experts_detail_level_percent(mixed $level): int
    {
        $raw = strtolower(trim((string) $level));
        if ($raw === '') {
            return 65;
        }

        if (is_numeric($raw)) {
            $number = (int) $raw;
            if ($number <= 5) {
                return [1 => 35, 2 => 50, 3 => 65, 4 => 80, 5 => 95][$number] ?? 65;
            }
            return max(35, min(95, $number));
        }

        return match ($raw) {
            'expert', 'senior', 'master', 'advanced expert', 'experte', 'expertin' => 95,
            'advanced', 'fortgeschritten', 'proficient' => 82,
            'intermediate', 'mittel', 'medium' => 65,
            'beginner', 'junior', 'basic', 'einsteiger' => 42,
            default => 72,
        };
    }
}

if (!function_exists('cms_experts_detail_level_class')) {
    function cms_experts_detail_level_class(int $percent): string
    {
        if ($percent >= 90) {
            return 'ex-detail-skill--level-95';
        }
        if ($percent >= 78) {
            return 'ex-detail-skill--level-82';
        }
        if ($percent >= 65) {
            return 'ex-detail-skill--level-72';
        }
        if ($percent >= 50) {
            return 'ex-detail-skill--level-58';
        }

        return 'ex-detail-skill--level-42';
    }
}

if (!function_exists('cms_experts_detail_level_label')) {
    function cms_experts_detail_level_label(int $percent): string
    {
        if ($percent >= 90) {
            return cms_experts_detail_t('cms_experts.detail.level.expert');
        }
        if ($percent >= 78) {
            return cms_experts_detail_t('cms_experts.detail.level.advanced');
        }
        if ($percent >= 58) {
            return cms_experts_detail_t('cms_experts.detail.level.intermediate');
        }

        return cms_experts_detail_t('cms_experts.detail.level.beginner');
    }
}

if (!function_exists('cms_experts_detail_year')) {
    function cms_experts_detail_year(mixed $date): string
    {
        $timestamp = strtotime((string) $date);
        return $timestamp ? date('Y', $timestamp) : '';
    }
}

if (!function_exists('cms_experts_detail_date')) {
    function cms_experts_detail_date(mixed $date): string
    {
        $timestamp = strtotime((string) $date);
        if (!$timestamp) {
            return '';
        }

        return date(cms_experts_detail_locale() === 'en' ? 'M j, Y' : 'd.m.Y', $timestamp);
    }
}

if (!function_exists('cms_experts_detail_icon')) {
    function cms_experts_detail_icon(string $icon): string
    {
        $stroke = static fn(string $paths): string => '<svg class="ex-detail-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths . '</svg>';
        $fill = static fn(string $paths): string => '<svg class="ex-detail-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false">' . $paths . '</svg>';

        return match ($icon) {
            'award' => $stroke('<circle cx="12" cy="9" r="6"/><path d="m9 14-1 8 4-2 4 2-1-8"/>'),
            'book' => $stroke('<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20V2H6.5A2.5 2.5 0 0 0 4 4.5z"/>'),
            'briefcase' => $stroke('<path d="M14 6V4a2 2 0 0 0-2-2h-1a2 2 0 0 0-2 2v2"/><path d="M3 6h18v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>'),
            'calendar' => $stroke('<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>'),
            'check' => $stroke('<path d="m9 11 3 3 8-8"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>'),
            'external' => $stroke('<path d="M9 18l6-6-6-6"/>'),
            'globe' => $stroke('<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/>'),
            'info' => $stroke('<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>'),
            'layers' => $stroke('<path d="M12 2 2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5M2 12l10 5 10-5"/>'),
            'list' => $stroke('<path d="M4 6h16M4 12h16M4 18h10"/>'),
            'mail' => $stroke('<path d="m4 6 8 6 8-6"/><path d="M4 6v12h16V6Z"/>'),
            'map' => $stroke('<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>'),
            'message' => $stroke('<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>'),
            'shield' => $stroke('<path d="M12 2 4 6v6c0 5 3.5 8 8 10 4.5-2 8-5 8-10V6z"/>'),
            'star' => $stroke('<path d="m12 2 2.4 6.9H22l-6 4.3 2.3 6.8-6.3-4.3-6.3 4.3L8 13.2 2 8.9h7.6z"/>'),
            'users' => $stroke('<path d="M16 19v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><path d="M9 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/><path d="M22 19v-2a4 4 0 0 0-3-3.87M16 3.13A4 4 0 0 1 16 11"/>'),
            'linkedin' => $fill('<path d="M4.98 3.5A2.5 2.5 0 1 1 0 3.5a2.5 2.5 0 0 1 4.98 0ZM0 8h5v16H0V8Zm7.5 0h4.8v2.2h.07c.67-1.2 2.3-2.46 4.73-2.46 5.06 0 6 3.33 6 7.66V24h-5v-7.2c0-1.72-.03-3.93-2.4-3.93-2.4 0-2.77 1.87-2.77 3.8V24h-5V8Z"/>'),
            'github' => $fill('<path d="M12 1a11 11 0 0 0-3.48 21.44c.55.1.75-.24.75-.53v-1.86c-3.06.67-3.7-1.47-3.7-1.47-.5-1.28-1.23-1.62-1.23-1.62-1-.69.08-.67.08-.67 1.1.08 1.69 1.14 1.69 1.14.98 1.69 2.58 1.2 3.21.92.1-.71.39-1.2.7-1.47-2.44-.28-5-1.22-5-5.44 0-1.2.43-2.18 1.13-2.95-.11-.28-.49-1.4.11-2.92 0 0 .93-.3 3.05 1.13a10.6 10.6 0 0 1 5.56 0c2.12-1.43 3.04-1.13 3.04-1.13.6 1.52.22 2.64.11 2.92.7.77 1.13 1.75 1.13 2.95 0 4.23-2.57 5.16-5.02 5.43.4.34.75 1.01.75 2.04v3.03c0 .29.2.64.76.53A11 11 0 0 0 12 1Z"/>'),
            'rss' => $fill('<circle cx="5" cy="19" r="2.5"/><path d="M3 9.5a11 11 0 0 1 11 11h3A14 14 0 0 0 3 6.5v3Z"/><path d="M3 4a17 17 0 0 1 17 17h3A20 20 0 0 0 3 1v3Z"/>'),
            'x', 'xing', 'gitlab', 'stackoverflow', 'youtube' => $stroke('<path d="M7 7h10v10H7z"/><path d="M9 15 15 9M9 9l6 6"/>'),
            default => $stroke('<circle cx="12" cy="12" r="9"/>'),
        };
    }
}

$ex = is_object($expert) ? $expert : (object) $expert;
$meta = is_array($meta ?? null) ? $meta : [];
$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$firstName = trim((string) ($ex->first_name ?? ''));
$lastName = trim((string) ($ex->last_name ?? ''));
$fullName = trim($firstName . ' ' . $lastName) ?: cms_experts_detail_t('cms_experts.detail.fallback_expert');
$firstNameLabel = $firstName !== '' ? $firstName : $fullName;
$initials = cms_experts_detail_initials($firstName, $lastName, $fullName);
$photo = cms_experts_detail_public_url($ex->photo_url ?? '');
$position = trim((string) ($ex->position ?? ''));
$company = trim((string) ($ex->company ?? ''));
$companyId = (int) ($meta['company_id'] ?? 0);
$companyUrl = '';
if ($companyId > 0 && $company !== '') {
    $companySlug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', strtr($company, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue'])), '-'));
    $companyUrl = $baseUrl . '/company/' . ($companySlug ?: 'company') . '-' . $companyId;
}
$locationParts = array_filter([trim((string) ($ex->location_city ?? '')), trim((string) ($ex->location_country ?? ''))]);
$location = implode(', ', $locationParts);
$experienceYears = (int) ($ex->experience_years ?? 0);
$biography = cms_experts_detail_plain($ex->biography ?? '');
$motto = cms_experts_detail_plain($meta['motto'] ?? '');
$email = filter_var(trim((string) ($ex->email ?? '')), FILTER_VALIDATE_EMAIL) ?: '';

$availability = trim((string) ($ex->availability ?? 'available'));
$availabilityClass = in_array($availability, ['available', 'limited', 'booked'], true) ? $availability : 'request';
$availabilityLabel = cms_experts_detail_t('cms_experts.detail.availability.' . $availabilityClass);
$remoteLabels = [
    'yes' => cms_experts_detail_t('cms_experts.detail.remote.yes'),
    'only' => cms_experts_detail_t('cms_experts.detail.remote.only'),
    'no' => cms_experts_detail_t('cms_experts.detail.remote.no'),
    'partial' => cms_experts_detail_t('cms_experts.detail.remote.partial'),
    'full' => cms_experts_detail_t('cms_experts.detail.remote.full'),
    'preferred' => cms_experts_detail_t('cms_experts.detail.remote.preferred'),
];
$workTypeLabels = [
    'freelancer' => cms_experts_detail_t('cms_experts.detail.work_type.freelancer'),
    'employed' => cms_experts_detail_t('cms_experts.detail.work_type.employed'),
    'agency' => cms_experts_detail_t('cms_experts.detail.work_type.agency'),
    'contractor' => cms_experts_detail_t('cms_experts.detail.work_type.contractor'),
];

$specializationNames = [];
foreach ((array) ($specializations ?? []) as $specialization) {
    $name = trim((string) cms_experts_detail_value($specialization, ['name'], ''));
    if ($name !== '') {
        $specializationNames[$name] = $name;
    }
}
$specializationNames = array_values($specializationNames);

$skillsByType = ['general' => [], 'tech' => [], 'soft' => []];
foreach ((array) ($skills ?? []) as $skill) {
    $type = (string) cms_experts_detail_value($skill, ['skill_type'], 'general');
    if (!array_key_exists($type, $skillsByType)) {
        $type = 'general';
    }
    $name = trim((string) cms_experts_detail_value($skill, ['skill_name', 'name'], ''));
    if ($name !== '') {
        $skillsByType[$type][] = [
            'name' => $name,
            'level' => cms_experts_detail_value($skill, ['skill_level', 'level'], 'intermediate'),
        ];
    }
}

$programmingLanguages = cms_experts_detail_list($meta['programming_languages'] ?? '');
$frameworks = cms_experts_detail_list($meta['frameworks'] ?? '');
$databases = cms_experts_detail_list($meta['databases'] ?? '');
$cloudPlatforms = cms_experts_detail_list($meta['cloud_platforms'] ?? '');
$industryExperience = cms_experts_detail_label_list($meta['industry_experience'] ?? '');
$toolsPreferred = cms_experts_detail_label_list($meta['tools_preferred'] ?? '');

$skillBars = [];
$addSkillBar = static function (string $name, mixed $level) use (&$skillBars): void {
    $name = trim($name);
    if ($name === '' || isset($skillBars[$name])) {
        return;
    }
    $percent = cms_experts_detail_level_percent($level);
    $skillBars[$name] = [
        'name' => $name,
        'percent' => $percent,
        'class' => cms_experts_detail_level_class($percent),
        'label' => cms_experts_detail_level_label($percent),
    ];
};
foreach (array_merge($programmingLanguages, $cloudPlatforms, $frameworks, $databases) as $item) {
    $addSkillBar((string) cms_experts_detail_value($item, ['name', 'title', 'value'], ''), cms_experts_detail_value($item, ['level', 'skill_level'], 'advanced'));
}
foreach (array_merge($skillsByType['general'], $skillsByType['tech']) as $skill) {
    $addSkillBar((string) $skill['name'], $skill['level']);
}
$skillBars = array_slice(array_values($skillBars), 0, 5);

$technologyChips = [];
foreach (array_merge($specializationNames, cms_experts_detail_label_list($programmingLanguages), cms_experts_detail_label_list($frameworks), cms_experts_detail_label_list($databases), cms_experts_detail_label_list($cloudPlatforms), $toolsPreferred) as $chip) {
    $chip = trim((string) $chip);
    if ($chip !== '') {
        $technologyChips[$chip] = $chip;
    }
}
$technologyChips = array_slice(array_values($technologyChips), 0, 16);

$social = [
    'website' => cms_experts_detail_public_url($meta['social_website'] ?? ''),
    'linkedin' => cms_experts_detail_public_url($meta['social_linkedin'] ?? ''),
    'xing' => cms_experts_detail_public_url($meta['social_xing'] ?? ''),
    'github' => cms_experts_detail_social_url($meta['social_github'] ?? '', 'github'),
    'gitlab' => cms_experts_detail_social_url($meta['social_gitlab'] ?? '', 'gitlab'),
    'stackoverflow' => cms_experts_detail_public_url($meta['social_stackoverflow'] ?? ''),
    'x' => cms_experts_detail_social_url($meta['social_twitter'] ?? '', 'x'),
    'youtube' => cms_experts_detail_public_url($meta['social_youtube'] ?? ''),
    'rss' => cms_experts_detail_public_url($meta['social_blog_rss'] ?? ''),
];
$socialLinks = [];
foreach ([
    ['key' => 'website', 'label' => cms_experts_detail_t('cms_experts.detail.social.website', ['{name}' => $fullName]), 'icon' => 'globe', 'class' => 'ex-detail-social__link--website'],
    ['key' => 'linkedin', 'label' => 'LinkedIn', 'icon' => 'linkedin', 'class' => ''],
    ['key' => 'github', 'label' => 'GitHub', 'icon' => 'github', 'class' => ''],
    ['key' => 'xing', 'label' => 'XING', 'icon' => 'xing', 'class' => ''],
    ['key' => 'gitlab', 'label' => 'GitLab', 'icon' => 'gitlab', 'class' => ''],
    ['key' => 'stackoverflow', 'label' => 'Stack Overflow', 'icon' => 'stackoverflow', 'class' => ''],
    ['key' => 'x', 'label' => 'X', 'icon' => 'x', 'class' => ''],
    ['key' => 'youtube', 'label' => 'YouTube', 'icon' => 'youtube', 'class' => ''],
    ['key' => 'rss', 'label' => cms_experts_detail_t('cms_experts.detail.social.rss', ['{name}' => $fullName]), 'icon' => 'rss', 'class' => ''],
] as $socialConfig) {
    $url = $social[$socialConfig['key']] ?? '';
    if ($url !== '') {
        $socialLinks[] = $socialConfig + ['url' => $url];
    }
}

$partnerStatus = trim((string) ($meta['partner_status'] ?? ''));
$heroBadges = [];
if (!empty($meta['is_mvp'])) {
    $heroBadges[] = cms_experts_detail_t('cms_experts.detail.badge.mvp');
}
if (!empty($meta['is_certified'])) {
    $heroBadges[] = cms_experts_detail_t('cms_experts.detail.badge.certified');
}
if (!empty($meta['is_premium'])) {
    $heroBadges[] = cms_experts_detail_t('cms_experts.detail.badge.premium');
}
if ($partnerStatus !== '') {
    $partnerKey = in_array($partnerStatus, ['partner', 'top_partner', 'sponsor'], true) ? $partnerStatus : 'partner';
    $heroBadges[] = cms_experts_detail_t('cms_experts.detail.badge.' . $partnerKey);
}
$customAward = trim((string) ($meta['custom_award'] ?? ''));
if ($customAward !== '') {
    $heroBadges[] = $customAward;
}
$heroBadges = array_slice(array_values(array_unique($heroBadges)), 0, 2);

$certifications = array_values((array) ($certifications ?? []));
$projects = array_values((array) ($projects ?? []));
$education = array_values((array) ($education ?? []));
$events = array_values((array) ($events ?? []));
$caseStudies = cms_experts_detail_list($meta['case_studies'] ?? '');
$conferenceTalks = cms_experts_detail_list($meta['conference_talks'] ?? '');
$testimonials = cms_experts_detail_list($meta['testimonials'] ?? '');
$careerStations = cms_experts_detail_list($meta['career_stations'] ?? '');

$serviceCards = [];
foreach ([
    ['flag' => 'services_consulting', 'title' => 'cms_experts.detail.service.consulting', 'text' => 'cms_experts.detail.service_text.consulting', 'icon' => 'message'],
    ['flag' => 'services_implementation', 'title' => 'cms_experts.detail.service.implementation', 'text' => 'cms_experts.detail.service_text.implementation', 'icon' => 'external'],
    ['flag' => 'services_training', 'title' => 'cms_experts.detail.service.training', 'text' => 'cms_experts.detail.service_text.training', 'icon' => 'users'],
    ['flag' => 'services_support', 'title' => 'cms_experts.detail.service.support', 'text' => 'cms_experts.detail.service_text.support', 'icon' => 'briefcase'],
    ['flag' => 'services_audit', 'title' => 'cms_experts.detail.service.audit', 'text' => 'cms_experts.detail.service_text.audit', 'icon' => 'shield'],
    ['flag' => 'emergency_support', 'title' => 'cms_experts.detail.service.emergency', 'text' => 'cms_experts.detail.service_text.emergency', 'icon' => 'calendar'],
    ['flag' => 'workshop_offerings', 'title' => 'cms_experts.detail.service.workshops', 'text' => 'cms_experts.detail.service_text.workshops', 'icon' => 'layers'],
] as $serviceConfig) {
    if (!empty($meta[$serviceConfig['flag']])) {
        $serviceCards[] = $serviceConfig;
    }
}

$workItems = [];
foreach ($projects as $project) {
    $title = trim((string) cms_experts_detail_value($project, ['project_name'], ''));
    if ($title === '') {
        continue;
    }
    $start = cms_experts_detail_date(cms_experts_detail_value($project, ['project_start'], ''));
    $end = cms_experts_detail_date(cms_experts_detail_value($project, ['project_end'], ''));
    $workItems[] = [
        'category' => cms_experts_detail_t('cms_experts.detail.item.project'),
        'title' => $title,
        'meta' => trim((string) cms_experts_detail_value($project, ['project_role'], '') . (($start || $end) ? ' · ' . trim($start . ' – ' . ($end ?: cms_experts_detail_t('cms_experts.detail.date_today'))) : '')),
        'url' => cms_experts_detail_public_url(cms_experts_detail_value($project, ['project_url'], '')),
    ];
}
foreach ($caseStudies as $caseStudy) {
    $title = trim((string) cms_experts_detail_value($caseStudy, ['title', 'name'], ''));
    if ($title === '') {
        continue;
    }
    $workItems[] = [
        'category' => cms_experts_detail_t('cms_experts.detail.item.case_study'),
        'title' => $title,
        'meta' => trim((string) cms_experts_detail_value($caseStudy, ['result', 'description'], '')),
        'url' => cms_experts_detail_public_url(cms_experts_detail_value($caseStudy, ['link', 'url'], '')),
    ];
}
foreach ($conferenceTalks as $talk) {
    $title = trim((string) cms_experts_detail_value($talk, ['title', 'talk'], ''));
    if ($title === '') {
        continue;
    }
    $eventName = trim((string) cms_experts_detail_value($talk, ['event'], ''));
    $year = trim((string) cms_experts_detail_value($talk, ['year'], ''));
    $workItems[] = [
        'category' => cms_experts_detail_t('cms_experts.detail.item.talk'),
        'title' => $title,
        'meta' => trim($eventName . ($eventName !== '' && $year !== '' ? ' · ' : '') . $year),
        'url' => cms_experts_detail_public_url(cms_experts_detail_value($talk, ['video_link', 'video_url', 'link', 'url'], '')),
    ];
}
foreach (array_slice($events, 0, 3) as $event) {
    $title = trim((string) cms_experts_detail_value($event, ['event_title', 'title'], ''));
    if ($title === '') {
        continue;
    }
    $workItems[] = [
        'category' => cms_experts_detail_t('cms_experts.detail.item.event'),
        'title' => $title,
        'meta' => trim(cms_experts_detail_date(cms_experts_detail_value($event, ['event_date'], '')) . ' · ' . (string) cms_experts_detail_value($event, ['event_location'], '')),
        'url' => '',
    ];
}
$workItems = array_slice($workItems, 0, 6);

$detailRows = array_filter([
    cms_experts_detail_t('cms_experts.detail.label.role') => $position,
    cms_experts_detail_t('cms_experts.detail.label.company') => $company,
    cms_experts_detail_t('cms_experts.detail.label.location') => $location,
    cms_experts_detail_t('cms_experts.detail.label.experience') => $experienceYears > 0 ? cms_experts_detail_t('cms_experts.detail.years', ['{count}' => (string) $experienceYears]) : '',
    cms_experts_detail_t('cms_experts.detail.label.languages') => trim((string) ($meta['languages'] ?? '')),
    cms_experts_detail_t('cms_experts.detail.label.availability') => $availabilityLabel,
    cms_experts_detail_t('cms_experts.detail.label.remote') => $remoteLabels[(string) ($meta['remote_work'] ?? '')] ?? '',
    cms_experts_detail_t('cms_experts.detail.label.work_type') => $workTypeLabels[(string) ($meta['work_type'] ?? '')] ?? '',
    cms_experts_detail_t('cms_experts.detail.label.travel') => trim((string) ($meta['travel_willingness'] ?? '')),
    cms_experts_detail_t('cms_experts.detail.label.certifications') => count($certifications) > 0 ? (string) count($certifications) : '',
], static fn($value): bool => trim((string) $value) !== '');

$contactUrl = $email !== ''
    ? 'mailto:' . $email . '?subject=' . rawurlencode(cms_experts_detail_t('cms_experts.detail.mail_subject', ['{name}' => $fullName]))
    : $baseUrl . '/contact?expert=' . (int) ($ex->id ?? 0);
$relatedExperts = array_slice((array) ($related_experts ?? []), 0, 3);
$expertUrlBase = $baseUrl . '/experts/';
?>
<main class="phinit-plugin cms-expert-detail ex-detail">
    <nav class="ex-detail-breadcrumb" aria-label="<?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.aria.breadcrumb')) ?>">
        <a href="<?= cms_experts_detail_e($baseUrl . '/') ?>"><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.breadcrumb.home')) ?></a>
        <span class="ex-detail-breadcrumb__sep" aria-hidden="true">›</span>
        <a href="<?= cms_experts_detail_e($expertUrlBase) ?>"><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.breadcrumb.experts')) ?></a>
        <span class="ex-detail-breadcrumb__sep" aria-hidden="true">›</span>
        <span aria-current="page"><?= cms_experts_detail_e($fullName) ?></span>
    </nav>

    <section class="ex-detail-hero" aria-labelledby="expert-detail-title">
        <span class="ex-detail-status ex-detail-status--<?= cms_experts_detail_e($availabilityClass) ?>"><span class="ex-detail-status__dot" aria-hidden="true"></span><?= cms_experts_detail_e($availabilityLabel) ?></span>
        <div class="ex-detail-hero__body">
            <div class="ex-detail-avatar<?= $photo === '' ? ' ex-detail-avatar--initials' : '' ?>">
                <?php if ($photo !== ''): ?>
                    <img src="<?= cms_experts_detail_e($photo) ?>" alt="<?= cms_experts_detail_e($fullName) ?>" width="116" height="116" loading="eager" decoding="async">
                <?php else: ?>
                    <span aria-hidden="true"><?= cms_experts_detail_e($initials) ?></span>
                <?php endif; ?>
            </div>

            <div class="ex-detail-hero__content">
                <div class="ex-detail-hero__name-row">
                    <h1 id="expert-detail-title"><?= cms_experts_detail_e($fullName) ?></h1>
                    <?php foreach ($heroBadges as $badge): ?>
                        <span class="ex-detail-pill"><?= cms_experts_detail_icon('star') ?><?= cms_experts_detail_e($badge) ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($position !== '' || $company !== '' || !empty($specializationNames)): ?>
                    <p class="ex-detail-hero__role">
                        <?php if ($position !== ''): ?><strong><?= cms_experts_detail_e($position) ?></strong><?php endif; ?>
                        <?php if ($position !== '' && ($company !== '' || !empty($specializationNames))): ?><span aria-hidden="true"> · </span><?php endif; ?>
                        <?php if ($company !== ''): ?><span><?= cms_experts_detail_e($company) ?></span><?php elseif (!empty($specializationNames)): ?><span><?= cms_experts_detail_e($specializationNames[0]) ?></span><?php endif; ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($socialLinks)): ?>
                    <div class="ex-detail-social ex-detail-social--hero" aria-label="<?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.aria.social_links')) ?>">
                        <?php foreach ($socialLinks as $socialLink): ?>
                            <a class="ex-detail-social__link <?= cms_experts_detail_e($socialLink['class']) ?>" href="<?= cms_experts_detail_e($socialLink['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= cms_experts_detail_e($socialLink['label']) ?>"><?= cms_experts_detail_icon($socialLink['icon']) ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($specializationNames) || !empty($technologyChips)): ?>
                    <div class="ex-detail-hero__tags" aria-label="<?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.aria.hero_tags')) ?>">
                        <?php foreach (array_slice(array_unique(array_merge($specializationNames, $technologyChips)), 0, 6) as $tag): ?>
                            <span class="ex-detail-tag-light"><?= cms_experts_detail_e($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="ex-detail-grid">
        <div class="ex-detail-main">
            <?php if ($biography !== '' || $motto !== ''): ?>
                <section class="ex-detail-card" aria-labelledby="expert-profile-heading">
                    <div class="ex-detail-card__pad">
                        <h2 class="ex-detail-card__title" id="expert-profile-heading"><?= cms_experts_detail_icon('info') ?><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.heading_profile')) ?></h2>
                        <?php if ($motto !== ''): ?><p class="ex-detail-lead ex-detail-lead--motto"><?= cms_experts_detail_e($motto) ?></p><?php endif; ?>
                        <?php if ($biography !== ''): ?><div class="ex-detail-prose"><?= nl2br(cms_experts_detail_e($biography)) ?></div><?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($skillBars) || !empty($technologyChips) || !empty($industryExperience) || !empty($skillsByType['soft'])): ?>
                <section class="ex-detail-card" aria-labelledby="expert-expertise-heading">
                    <div class="ex-detail-card__pad">
                        <h2 class="ex-detail-card__title" id="expert-expertise-heading"><?= cms_experts_detail_icon('layers') ?><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.heading_expertise')) ?></h2>
                        <?php if (!empty($skillBars)): ?>
                            <p class="ex-detail-sub-label"><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.subheading_core_skills')) ?></p>
                            <div class="ex-detail-skills">
                                <?php foreach ($skillBars as $skillBar): ?>
                                    <div class="ex-detail-skill <?= cms_experts_detail_e($skillBar['class']) ?>">
                                        <div class="ex-detail-skill__top"><strong><?= cms_experts_detail_e($skillBar['name']) ?></strong><span><?= cms_experts_detail_e($skillBar['label']) ?></span></div>
                                        <div class="ex-detail-skill__bar" aria-hidden="true"><span></span></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($technologyChips)): ?>
                            <p class="ex-detail-sub-label"><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.subheading_technologies')) ?></p>
                            <div class="ex-detail-chips">
                                <?php foreach ($technologyChips as $chip): ?><span class="ex-detail-chip ex-detail-chip--plain"><?= cms_experts_detail_e($chip) ?></span><?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($industryExperience)): ?>
                            <p class="ex-detail-sub-label"><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.subheading_industries')) ?></p>
                            <div class="ex-detail-chips">
                                <?php foreach ($industryExperience as $industry): ?><span class="ex-detail-chip"><span class="ex-detail-chip__dot" aria-hidden="true"></span><?= cms_experts_detail_e($industry) ?></span><?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($skillsByType['soft'])): ?>
                            <p class="ex-detail-sub-label"><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.subheading_soft_skills')) ?></p>
                            <div class="ex-detail-chips">
                                <?php foreach (array_slice($skillsByType['soft'], 0, 10) as $skill): ?><span class="ex-detail-chip ex-detail-chip--plain"><?= cms_experts_detail_e($skill['name']) ?></span><?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($certifications)): ?>
                <section class="ex-detail-card" aria-labelledby="expert-certifications-heading">
                    <div class="ex-detail-card__pad">
                        <h2 class="ex-detail-card__title" id="expert-certifications-heading"><?= cms_experts_detail_icon('award') ?><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.heading_certifications')) ?><span class="ex-detail-card__badge"><?= (int) count($certifications) ?></span></h2>
                        <div class="ex-detail-cert-list">
                            <?php foreach ($certifications as $certification): ?>
                                <?php
                                $certName = trim((string) cms_experts_detail_value($certification, ['cert_name'], ''));
                                $certIssuer = trim((string) cms_experts_detail_value($certification, ['cert_issuer'], ''));
                                $certYear = cms_experts_detail_year(cms_experts_detail_value($certification, ['cert_date'], ''));
                                $certUrl = cms_experts_detail_public_url(cms_experts_detail_value($certification, ['cert_url'], ''));
                                ?>
                                <?php if ($certName !== ''): ?>
                                    <article class="ex-detail-cert">
                                        <span class="ex-detail-cert__icon"><?= cms_experts_detail_icon('check') ?></span>
                                        <div class="ex-detail-cert__body">
                                            <?php if ($certUrl !== ''): ?><a class="ex-detail-cert__name" href="<?= cms_experts_detail_e($certUrl) ?>" target="_blank" rel="noopener noreferrer"><?= cms_experts_detail_e($certName) ?></a><?php else: ?><h3 class="ex-detail-cert__name"><?= cms_experts_detail_e($certName) ?></h3><?php endif; ?>
                                            <?php if ($certIssuer !== ''): ?><p><?= cms_experts_detail_e($certIssuer) ?></p><?php endif; ?>
                                        </div>
                                        <?php if ($certYear !== ''): ?><span class="ex-detail-cert__year"><?= cms_experts_detail_e($certYear) ?></span><?php endif; ?>
                                    </article>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($serviceCards)): ?>
                <section class="ex-detail-card" aria-labelledby="expert-services-heading">
                    <div class="ex-detail-card__pad">
                        <h2 class="ex-detail-card__title" id="expert-services-heading"><?= cms_experts_detail_icon('briefcase') ?><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.heading_services')) ?></h2>
                        <div class="ex-detail-services">
                            <?php foreach ($serviceCards as $serviceCard): ?>
                                <article class="ex-detail-service">
                                    <span class="ex-detail-service__icon"><?= cms_experts_detail_icon($serviceCard['icon']) ?></span>
                                    <h3><?= cms_experts_detail_e(cms_experts_detail_t($serviceCard['title'])) ?></h3>
                                    <p><?= cms_experts_detail_e(cms_experts_detail_t($serviceCard['text'])) ?></p>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($workItems)): ?>
                <section class="ex-detail-card" aria-labelledby="expert-work-heading">
                    <div class="ex-detail-card__pad">
                        <h2 class="ex-detail-card__title" id="expert-work-heading"><?= cms_experts_detail_icon('book') ?><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.heading_work_samples')) ?><span class="ex-detail-card__badge"><?= (int) count($workItems) ?></span></h2>
                        <div class="ex-detail-articles">
                            <?php foreach ($workItems as $workItem): ?>
                                <?php $tag = $workItem['url'] !== '' ? 'a' : 'article'; ?>
                                <<?= $tag ?> class="ex-detail-article"<?= $workItem['url'] !== '' ? ' href="' . cms_experts_detail_e($workItem['url']) . '" target="_blank" rel="noopener noreferrer"' : '' ?>>
                                    <span class="ex-detail-article__body"><span class="ex-detail-article__cat"><?= cms_experts_detail_e($workItem['category']) ?></span><strong><?= cms_experts_detail_e($workItem['title']) ?></strong><?php if ($workItem['meta'] !== ''): ?><small><?= cms_experts_detail_e($workItem['meta']) ?></small><?php endif; ?></span>
                                    <?php if ($workItem['url'] !== ''): ?><span class="ex-detail-article__go"><?= cms_experts_detail_icon('external') ?></span><?php endif; ?>
                                </<?= $tag ?>>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <aside class="ex-detail-sidebar" aria-label="<?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.aria.sidebar')) ?>">
            <section class="ex-detail-card ex-detail-card--cta" aria-labelledby="expert-contact-heading">
                <div class="ex-detail-card__pad">
                    <h2 class="ex-detail-card__title" id="expert-contact-heading"><?= cms_experts_detail_icon('mail') ?><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.heading_request')) ?></h2>
                    <h3><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.request_title', ['{name}' => $fullName])) ?></h3>
                    <p><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.request_text')) ?></p>
                    <a class="ex-detail-btn ex-detail-btn--primary ex-detail-btn--block" href="<?= cms_experts_detail_e($contactUrl) ?>"><?= cms_experts_detail_icon('mail') ?><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.button_contact')) ?></a>
                </div>
            </section>

            <?php if (!empty($socialLinks)): ?>
                <section class="ex-detail-card" aria-labelledby="expert-social-heading">
                    <div class="ex-detail-card__pad">
                        <h2 class="ex-detail-card__title" id="expert-social-heading"><?= cms_experts_detail_icon('globe') ?><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.heading_social')) ?></h2>
                        <div class="ex-detail-social" aria-label="<?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.aria.social_links')) ?>">
                            <?php foreach ($socialLinks as $socialLink): ?>
                                <a class="ex-detail-social__link <?= cms_experts_detail_e($socialLink['class']) ?>" href="<?= cms_experts_detail_e($socialLink['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= cms_experts_detail_e($socialLink['label']) ?>"><?= cms_experts_detail_icon($socialLink['icon']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($detailRows)): ?>
                <section class="ex-detail-card" aria-labelledby="expert-details-heading">
                    <div class="ex-detail-card__pad">
                        <h2 class="ex-detail-card__title" id="expert-details-heading"><?= cms_experts_detail_icon('list') ?><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.heading_details')) ?></h2>
                        <dl class="ex-detail-dl">
                            <?php foreach ($detailRows as $label => $value): ?>
                                <div class="ex-detail-dl__row"><dt><?= cms_experts_detail_e($label) ?></dt><dd><?php if ($label === cms_experts_detail_t('cms_experts.detail.label.company') && $companyUrl !== ''): ?><a href="<?= cms_experts_detail_e($companyUrl) ?>"><?= cms_experts_detail_e($value) ?></a><?php else: ?><?= cms_experts_detail_e($value) ?><?php endif; ?></dd></div>
                            <?php endforeach; ?>
                        </dl>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($relatedExperts)): ?>
                <section class="ex-detail-card" aria-labelledby="expert-related-heading">
                    <div class="ex-detail-card__pad">
                        <h2 class="ex-detail-card__title" id="expert-related-heading"><?= cms_experts_detail_icon('users') ?><?= cms_experts_detail_e(cms_experts_detail_t('cms_experts.detail.heading_related')) ?></h2>
                        <div class="ex-detail-related">
                            <?php foreach ($relatedExperts as $relatedExpert): ?>
                                <?php
                                $relatedName = trim((string) (($relatedExpert->first_name ?? '') . ' ' . ($relatedExpert->last_name ?? ''))) ?: cms_experts_detail_t('cms_experts.detail.fallback_expert');
                                $relatedUrl = $expertUrlBase . cms_experts_detail_slug($relatedExpert);
                                $relatedInitials = cms_experts_detail_initials((string) ($relatedExpert->first_name ?? ''), (string) ($relatedExpert->last_name ?? ''), $relatedName);
                                $relatedRole = trim((string) ($relatedExpert->position ?? ''));
                                ?>
                                <a class="ex-detail-related__item" href="<?= cms_experts_detail_e($relatedUrl) ?>">
                                    <span class="ex-detail-related__avatar" aria-hidden="true"><?= cms_experts_detail_e($relatedInitials) ?></span>
                                    <span class="ex-detail-related__body"><strong><?= cms_experts_detail_e($relatedName) ?></strong><?php if ($relatedRole !== ''): ?><small><?= cms_experts_detail_e($relatedRole) ?></small><?php endif; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</main>