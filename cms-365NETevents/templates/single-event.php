<?php
/**
 * Single Event Template – PHINIT preview-aligned detail view.
 *
 * @package CMS_Events
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (empty($event) || !is_object($event)) {
    return;
}

if (!function_exists('cms_events_detail_t')) {
    function cms_events_detail_t(string $key, array $parameters = []): string
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
            $catalogValue = cms_events_detail_catalog_value($key);
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

if (!function_exists('cms_events_detail_catalog_value')) {
    function cms_events_detail_catalog_value(string $key): ?string
    {
        $catalog = cms_events_detail_load_catalog(cms_events_detail_locale());
        return $catalog['default'][$key] ?? null;
    }
}

if (!function_exists('cms_events_detail_load_catalog')) {
    function cms_events_detail_load_catalog(string $locale): array
    {
        static $cache = [];

        if (isset($cache[$locale])) {
            return $cache[$locale];
        }

        $file = cms_events_detail_lang_file($locale);
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

if (!function_exists('cms_events_detail_lang_file')) {
    function cms_events_detail_lang_file(string $locale): string
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

if (!function_exists('cms_events_detail_locale')) {
    function cms_events_detail_locale(): string
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

if (!function_exists('cms_events_detail_e')) {
    function cms_events_detail_e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cms_events_detail_plain')) {
    function cms_events_detail_plain(mixed $value): string
    {
        $value = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = strip_tags($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        return trim($value);
    }
}

if (!function_exists('cms_events_detail_lower')) {
    function cms_events_detail_lower(string $value): string
    {
        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }
}

if (!function_exists('cms_events_detail_trim_text')) {
    function cms_events_detail_trim_text(string $text, int $length): string
    {
        $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
        if ($text === '' || $length <= 0) {
            return '';
        }

        $currentLength = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
        if ($currentLength <= $length) {
            return $text;
        }

        $slice = function_exists('mb_substr') ? mb_substr($text, 0, max(0, $length - 1), 'UTF-8') : substr($text, 0, max(0, $length - 1));
        return rtrim((string) $slice, " \t\n\r\0\x0B.,;:-") . '…';
    }
}

if (!function_exists('cms_events_detail_public_url')) {
    function cms_events_detail_public_url(mixed $url): string
    {
        $url = str_replace('\\', '/', trim((string) $url));
        if ($url === '' || strlen($url) > 2048 || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
            return '';
        }

        if ((str_starts_with($url, '/') && !str_starts_with($url, '//')) || preg_match('#^(uploads|ASSETS|assets|plugins)/#i', $url) === 1) {
            if (str_contains($url, '..')) {
                return '';
            }

            $baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
            return $baseUrl . '/' . ltrim($url, '/');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host']) || !empty($parts['user']) || !empty($parts['pass'])) {
            return '';
        }

        return in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true) ? $url : '';
    }
}

if (!function_exists('cms_events_detail_initials')) {
    function cms_events_detail_initials(string $name, string $fallback = 'EV'): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $initials = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $initials .= function_exists('mb_substr') ? mb_substr($part, 0, 1, 'UTF-8') : substr($part, 0, 1);
            $length = function_exists('mb_strlen') ? mb_strlen($initials, 'UTF-8') : strlen($initials);
            if ($length >= 2) {
                break;
            }
        }

        $initials = $initials !== '' ? $initials : $fallback;
        return function_exists('mb_strtoupper') ? mb_strtoupper($initials, 'UTF-8') : strtoupper($initials);
    }
}

if (!function_exists('cms_events_detail_date_parts')) {
    /**
     * @return array{day:string,month_short:string,month_full:string,year:string,machine:string,display:string}
     */
    function cms_events_detail_date_parts(?string $date): array
    {
        $timestamp = $date ? strtotime($date) : 0;
        if (!$timestamp) {
            return ['day' => '', 'month_short' => '', 'month_full' => '', 'year' => '', 'machine' => '', 'display' => ''];
        }

        $month = (int) date('n', $timestamp);
        $monthShort = cms_events_detail_t('cms_events.detail.month_short.' . $month);
        $monthFull = cms_events_detail_t('cms_events.detail.month_full.' . $month);

        return [
            'day' => date('d', $timestamp),
            'month_short' => $monthShort,
            'month_full' => $monthFull,
            'year' => date('Y', $timestamp),
            'machine' => date('Y-m-d', $timestamp),
            'display' => date('d', $timestamp) . '. ' . $monthFull . ' ' . date('Y', $timestamp),
        ];
    }
}

if (!function_exists('cms_events_detail_time')) {
    function cms_events_detail_time(?string $time): string
    {
        $time = trim((string) $time);
        if ($time === '') {
            return '';
        }

        return substr($time, 0, 5);
    }
}

if (!function_exists('cms_events_detail_time_range')) {
    function cms_events_detail_time_range(?string $start, ?string $end): string
    {
        $startLabel = cms_events_detail_time($start);
        $endLabel = cms_events_detail_time($end);

        if ($startLabel === '') {
            return '';
        }

        return $endLabel !== '' ? $startLabel . '–' . $endLabel : $startLabel;
    }
}

if (!function_exists('cms_events_detail_location_text')) {
    function cms_events_detail_location_text(string $location, string $city): string
    {
        $parts = array_filter(array_map('trim', array_merge(
            preg_split('/\s*,\s*/', $location) ?: [],
            [$city]
        )), static fn(string $part): bool => $part !== '');

        $seen = [];
        $unique = [];
        foreach ($parts as $part) {
            $key = cms_events_detail_lower(preg_replace('/\s+/', ' ', $part) ?? $part);
            if ($key === '' || isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $unique[] = $part;
        }

        return implode(', ', $unique);
    }
}

if (!function_exists('cms_events_detail_list')) {
    /**
     * @return string[]
     */
    function cms_events_detail_list(mixed $value): array
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                if (is_array($item)) {
                    $item = $item['label'] ?? $item['name'] ?? $item['title'] ?? $item['value'] ?? '';
                }
                $items[] = cms_events_detail_trim_text((string) $item, 48);
            }

            return array_values(array_filter($items));
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return cms_events_detail_list($decoded);
        }

        return array_values(array_filter(array_map(
            static fn(string $item): string => cms_events_detail_trim_text($item, 48),
            preg_split('/[,;\n]+/', $raw) ?: []
        )));
    }
}

if (!function_exists('cms_events_detail_price_label')) {
    function cms_events_detail_price_label(object $event): string
    {
        $priceType = cms_events_detail_lower(trim((string) ($event->price_type ?? 'free')));
        $price = max(0.0, (float) ($event->price ?? 0));
        $currency = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($event->price_currency ?? 'EUR')) ?: 'EUR');

        if ($priceType === 'free') {
            return cms_events_detail_t('cms_events.detail.price.free');
        }

        if ($priceType === 'donation' && $price <= 0) {
            return cms_events_detail_t('cms_events.detail.price.donation');
        }

        if ($price <= 0) {
            return cms_events_detail_t('cms_events.detail.price.paid');
        }

        $symbol = match ($currency) {
            'EUR' => '€',
            'USD' => '$',
            'CHF' => 'CHF',
            default => $currency,
        };

        return $symbol . ' ' . number_format($price, 2, ',', '.');
    }
}

if (!function_exists('cms_events_detail_normalize_key')) {
    function cms_events_detail_normalize_key(string $value): string
    {
        $value = cms_events_detail_lower(trim($value));
        $value = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $value);
        return trim((string) preg_replace('/[^a-z0-9]+/', '_', $value), '_');
    }
}

if (!function_exists('cms_events_detail_category_label')) {
    function cms_events_detail_category_label(string $category): string
    {
        $category = trim($category);
        if ($category === '') {
            return cms_events_detail_t('cms_events.detail.fallback_event');
        }

        $key = 'cms_events.detail.category.' . cms_events_detail_normalize_key($category);
        $translated = cms_events_detail_t($key);

        return $translated !== $key ? $translated : $category;
    }
}

if (!function_exists('cms_events_detail_mode')) {
    /**
     * @return array{label:string,key:string}
     */
    function cms_events_detail_mode(bool $isOnline, string $locationText): array
    {
        if ($isOnline && $locationText !== '') {
            return ['label' => cms_events_detail_t('cms_events.detail.mode.hybrid'), 'key' => 'hybrid'];
        }

        if ($isOnline) {
            return ['label' => cms_events_detail_t('cms_events.detail.mode.online'), 'key' => 'online'];
        }

        return ['label' => cms_events_detail_t('cms_events.detail.mode.onsite'), 'key' => 'onsite'];
    }
}

if (!function_exists('cms_events_detail_status')) {
    /**
     * @return array{label:string,key:string}
     */
    function cms_events_detail_status(object $event, bool $fullyBooked, string $registrationUrl, string $onlineUrl): array
    {
        $status = cms_events_detail_lower((string) ($event->status ?? ''));
        $machineDate = cms_events_detail_date_parts((string) ($event->event_date ?? ''))['machine'];
        $eventTs = $machineDate !== '' ? strtotime($machineDate) : 0;
        $todayTs = strtotime('today') ?: 0;

        if ($status === 'cancelled') {
            return ['label' => cms_events_detail_t('cms_events.detail.status.cancelled'), 'key' => 'cancelled'];
        }

        if ($status === 'completed' || ($eventTs && $todayTs && $eventTs < $todayTs)) {
            return ['label' => cms_events_detail_t('cms_events.detail.status.completed'), 'key' => 'completed'];
        }

        if ($fullyBooked) {
            return ['label' => cms_events_detail_t('cms_events.detail.status.sold_out'), 'key' => 'sold-out'];
        }

        if ($registrationUrl !== '') {
            return ['label' => cms_events_detail_t('cms_events.detail.status.registration_open'), 'key' => 'open'];
        }

        if ($onlineUrl !== '') {
            return ['label' => cms_events_detail_t('cms_events.detail.status.online_available'), 'key' => 'online'];
        }

        return ['label' => cms_events_detail_t('cms_events.detail.status.registration_soon'), 'key' => 'soon'];
    }
}

if (!function_exists('cms_events_detail_speaker_name')) {
    function cms_events_detail_speaker_name(object $speaker): string
    {
        $name = trim((string) ($speaker->speaker_name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim((string) ($speaker->first_name ?? '') . ' ' . (string) ($speaker->last_name ?? ''));
    }
}

if (!function_exists('cms_events_detail_speaker_link')) {
    function cms_events_detail_speaker_link(object $speaker, string $baseUrl): string
    {
        $id = (int) ($speaker->speaker_id ?? 0);
        if ($id <= 0) {
            return '';
        }

        $type = (string) ($speaker->speaker_type ?? 'speaker');
        return $baseUrl . ($type === 'expert' ? '/experts/' : '/speakers/') . $id;
    }
}

if (!function_exists('cms_events_detail_icon')) {
    function cms_events_detail_icon(string $name): string
    {
        $icons = [
            'calendar' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M3 9h18M8 3v4M16 3v4"></path></svg>',
            'clock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 2"></path></svg>',
            'details' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10"></path></svg>',
            'globe' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"></path></svg>',
            'info' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4M12 8h.01"></path></svg>',
            'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg>',
            'pin' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="3"></circle></svg>',
            'ticket' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v3a2 2 0 0 0 0 6v3a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-6z"></path><path d="M12 4v16" stroke-dasharray="2 3"></path></svg>',
            'users' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
            'linkedin' => '<svg viewBox="0 0 24 24" aria-hidden="true" class="ev-detail-fill"><path d="M4.98 3.5A2.5 2.5 0 1 1 0 3.5a2.5 2.5 0 0 1 4.98 0ZM0 8h5v16H0V8Zm7.5 0h4.8v2.2h.07c.67-1.2 2.3-2.46 4.73-2.46 5.06 0 6 3.33 6 7.66V24h-5v-7.2c0-1.72-.03-3.93-2.4-3.93-2.4 0-2.77 1.87-2.77 3.8V24h-5V8Z"></path></svg>',
            'x' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4l16 16M20 4 4 20"></path></svg>',
        ];

        return $icons[$name] ?? $icons['info'];
    }
}

$e = $event;
$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$eventId = (int) ($e->id ?? 0);
$titleRaw = trim((string) ($e->title ?? '')) ?: cms_events_detail_t('cms_events.detail.fallback_event');
$categoryRaw = trim((string) ($e->category ?? ''));
$categoryLabel = cms_events_detail_category_label($categoryRaw);
$description = trim((string) ($e->description ?? ''));
$locationRaw = trim((string) ($e->location ?? ''));
$cityRaw = trim((string) ($e->city ?? ''));
$locationText = cms_events_detail_location_text($locationRaw, $cityRaw);
$addressRaw = trim((string) ($e->address ?? ''));
$zipRaw = trim((string) ($e->zip ?? ''));
$countryRaw = trim((string) ($e->country ?? ''));
$cityLine = trim($zipRaw . ($zipRaw !== '' && $cityRaw !== '' ? ' ' : '') . $cityRaw);
$addressParts = array_values(array_filter([$addressRaw, $cityLine, $countryRaw], static fn(string $part): bool => trim($part) !== ''));
$fullAddressText = implode(', ', $addressParts);
$registrationUrl = cms_events_detail_public_url($e->registration_url ?? null);
$onlineUrl = cms_events_detail_public_url($e->online_url ?? null);
$websiteUrl = cms_events_detail_public_url($e->website_url ?? $e->organizer_website ?? $e->website ?? null);
$dateParts = cms_events_detail_date_parts((string) ($e->event_date ?? ''));
$endDateParts = cms_events_detail_date_parts((string) ($e->end_date ?? ''));
$timeLabel = cms_events_detail_time_range((string) ($e->event_time ?? ''), (string) ($e->end_time ?? ''));
$isMultiDay = $dateParts['machine'] !== '' && $endDateParts['machine'] !== '' && $endDateParts['machine'] !== $dateParts['machine'];
$dateLabel = $dateParts['display'];
if ($isMultiDay && $endDateParts['display'] !== '') {
    $dateLabel .= ' – ' . $endDateParts['display'];
}
$dateTimeLabel = trim($dateLabel . ($timeLabel !== '' ? ', ' . $timeLabel : ''));
$speakerList = array_values(array_filter((array) ($speakers ?? []), 'is_object'));
$capacity = max(0, (int) ($e->seats_total ?? $e->capacity ?? 0));
$registered = max(0, (int) ($e->seats_booked ?? $e->registered_count ?? $e->registrations_count ?? 0));
$seatsLeft = $capacity > 0 ? max(0, $capacity - $registered) : 0;
$isFullyBooked = $capacity > 0 && $seatsLeft <= 0;
$priceLabel = cms_events_detail_price_label($e);
$mode = cms_events_detail_mode(!empty($e->is_online), $locationText);
$status = cms_events_detail_status($e, $isFullyBooked, $registrationUrl, $onlineUrl);
$language = trim((string) ($e->language ?? $e->event_language ?? '')) ?: cms_events_detail_t('cms_events.detail.language.default');
$organizerName = trim((string) ($e->organizer_name ?? ''));
$tags = cms_events_detail_list($e->tags ?? '');
$eventUrl = function_exists('cms_event_url') ? cms_event_url($e) : $baseUrl . '/events/' . $eventId;
$calendarUrl = $eventId > 0 ? $baseUrl . '/events/' . $eventId . '/ical' : '';
$requestScheme = (!empty($_SERVER['HTTPS']) && (string) $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$fallbackHost = parse_url($baseUrl, PHP_URL_HOST);
$requestHost = preg_replace('/[^a-z0-9.:-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? ($fallbackHost ?: '')));
$fallbackPath = parse_url($eventUrl, PHP_URL_PATH);
$requestUri = str_replace(["\r", "\n"], '', (string) ($_SERVER['REQUEST_URI'] ?? ($fallbackPath ?: '')));
$currentUrl = $requestHost !== '' && $requestUri !== '' ? $requestScheme . '://' . $requestHost . $requestUri : $eventUrl;
$shareLinkedin = 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode($currentUrl);
$shareX = 'https://twitter.com/intent/tweet?url=' . rawurlencode($currentUrl) . '&text=' . rawurlencode($titleRaw);
$shareMail = 'mailto:?subject=' . rawurlencode($titleRaw) . '&body=' . rawurlencode($currentUrl);
$relatedEvents = array_slice(array_values(array_filter((array) ($related_events ?? []), static fn($item): bool => is_object($item) || is_array($item))), 0, 3);

$agendaItems = [];
foreach ($speakerList as $speaker) {
    $sessionTime = cms_events_detail_time((string) ($speaker->session_time ?? ''));
    $presentation = cms_events_detail_plain($speaker->presentation_title ?? '');
    $role = cms_events_detail_plain($speaker->role ?? '');
    if ($sessionTime === '' && $presentation === '') {
        continue;
    }

    $speakerName = cms_events_detail_speaker_name($speaker);
    $speakerContext = trim(implode(' · ', array_filter([
        $speakerName,
        cms_events_detail_plain($speaker->position ?? $speaker->company ?? ''),
    ])));
    $agendaItems[] = [
        'time' => $sessionTime !== '' ? $sessionTime : cms_events_detail_t('cms_events.detail.time_on_request'),
        'label' => $role !== '' ? $role : cms_events_detail_t('cms_events.detail.session'),
        'title' => $presentation !== '' ? $presentation : ($role !== '' ? $role : $speakerName),
        'who' => $speakerContext,
    ];
}

$heroTags = array_values(array_filter(array_merge([$mode['label']], array_slice($tags, 0, 4))));
$facts = array_filter([
    cms_events_detail_t('cms_events.detail.label.date') => $dateLabel,
    cms_events_detail_t('cms_events.detail.label.time') => $timeLabel,
    cms_events_detail_t('cms_events.detail.label.format') => $mode['label'],
    cms_events_detail_t('cms_events.detail.label.language') => $language,
    cms_events_detail_t('cms_events.detail.label.organizer') => $organizerName,
    cms_events_detail_t('cms_events.detail.label.participants') => $capacity > 0 ? cms_events_detail_t('cms_events.detail.capacity_count', ['count' => (string) $capacity]) : '',
    cms_events_detail_t('cms_events.detail.label.price') => $priceLabel,
], static fn(string $value): bool => $value !== '');
?>
<main class="phinit-plugin ev-detail" aria-labelledby="ev-detail-title">
    <nav class="ev-detail-breadcrumb" aria-label="<?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.aria.breadcrumb')) ?>">
        <a href="<?= cms_events_detail_e($baseUrl . '/') ?>"><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.breadcrumb.home')) ?></a>
        <span aria-hidden="true">›</span>
        <a href="<?= cms_events_detail_e($baseUrl . '/events') ?>"><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.breadcrumb.events')) ?></a>
        <span aria-hidden="true">›</span>
        <span aria-current="page"><?= cms_events_detail_e($titleRaw) ?></span>
    </nav>

    <section class="ev-detail-hero" aria-labelledby="ev-detail-title">
        <span class="ev-detail-status ev-detail-status--<?= cms_events_detail_e($status['key']) ?>"><span aria-hidden="true"></span><?= cms_events_detail_e($status['label']) ?></span>
        <div class="ev-detail-hero__body">
            <time class="ev-detail-datebox" datetime="<?= cms_events_detail_e($dateParts['machine']) ?>" aria-label="<?= cms_events_detail_e($dateParts['display']) ?>">
                <span class="ev-detail-datebox__month"><?= cms_events_detail_e($dateParts['month_short']) ?></span>
                <span class="ev-detail-datebox__day"><?= cms_events_detail_e($dateParts['day']) ?></span>
                <span class="ev-detail-datebox__year"><?= cms_events_detail_e($dateParts['year']) ?></span>
            </time>
            <div class="ev-detail-hero__content">
                <p class="ev-detail-eyebrow"><?= cms_events_detail_e($categoryLabel) ?> · <?= cms_events_detail_e($mode['label']) ?></p>
                <h1 id="ev-detail-title"><?= cms_events_detail_e($titleRaw) ?></h1>
                <div class="ev-detail-hero-meta">
                    <?php if ($dateTimeLabel !== ''): ?>
                        <span><?= cms_events_detail_icon('calendar') ?><?= cms_events_detail_e($dateTimeLabel) ?></span>
                    <?php endif; ?>
                    <?php if ($locationText !== ''): ?>
                        <span><?= cms_events_detail_icon('pin') ?><?= cms_events_detail_e($locationText) ?></span>
                    <?php elseif (!empty($e->is_online)): ?>
                        <span><?= cms_events_detail_icon('globe') ?><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.mode.online')) ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($heroTags !== []): ?>
                    <ul class="ev-detail-tags ev-detail-tags--hero" aria-label="<?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.aria.hero_tags')) ?>">
                        <?php foreach ($heroTags as $tag): ?>
                            <li><?= cms_events_detail_e($tag) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <div class="ev-detail-grid">
        <div class="ev-detail-main">
            <section class="ev-detail-card" aria-labelledby="ev-detail-about-heading">
                <h2 id="ev-detail-about-heading" class="ev-detail-card-title"><?= cms_events_detail_icon('info') ?><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.heading_about')) ?></h2>
                <?php if ($description !== ''): ?>
                    <div class="ev-detail-copy"><?= nl2br(cms_events_detail_e(cms_events_detail_plain($description))) ?></div>
                <?php else: ?>
                    <p class="ev-detail-empty"><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.empty_description')) ?></p>
                <?php endif; ?>
                <?php if ($tags !== []): ?>
                    <p class="ev-detail-sub-label"><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.subheading_tracks')) ?></p>
                    <ul class="ev-detail-tags" role="list">
                        <?php foreach ($tags as $tag): ?>
                            <li><?= cms_events_detail_e($tag) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <?php if ($agendaItems !== []): ?>
                <section class="ev-detail-card" aria-labelledby="ev-detail-agenda-heading">
                    <h2 id="ev-detail-agenda-heading" class="ev-detail-card-title"><?= cms_events_detail_icon('clock') ?><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.heading_agenda')) ?></h2>
                    <ol class="ev-detail-agenda">
                        <?php foreach ($agendaItems as $item): ?>
                            <li>
                                <p><?= cms_events_detail_e($item['time']) ?> · <?= cms_events_detail_e($item['label']) ?></p>
                                <h3><?= cms_events_detail_e($item['title']) ?></h3>
                                <?php if ($item['who'] !== ''): ?>
                                    <span><?= cms_events_detail_e($item['who']) ?></span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                </section>
            <?php endif; ?>

            <?php if ($speakerList !== []): ?>
                <section class="ev-detail-card" aria-labelledby="ev-detail-speakers-heading">
                    <h2 id="ev-detail-speakers-heading" class="ev-detail-card-title">
                        <?= cms_events_detail_icon('users') ?><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.heading_speakers')) ?>
                        <span><?= (int) count($speakerList) ?></span>
                    </h2>
                    <div class="ev-detail-team">
                        <?php foreach ($speakerList as $speaker): ?>
                            <?php
                            $speakerName = cms_events_detail_speaker_name($speaker) ?: cms_events_detail_t('cms_events.detail.fallback_speaker');
                            $speakerSubtitle = cms_events_detail_plain($speaker->presentation_title ?? $speaker->role ?? $speaker->position ?? $speaker->company ?? '');
                            $speakerLink = cms_events_detail_speaker_link($speaker, $baseUrl);
                            ?>
                            <?php if ($speakerLink !== ''): ?>
                                <a class="ev-detail-speaker" href="<?= cms_events_detail_e($speakerLink) ?>">
                            <?php else: ?>
                                <article class="ev-detail-speaker">
                            <?php endif; ?>
                                    <span class="ev-detail-speaker__avatar" aria-hidden="true"><?= cms_events_detail_e(cms_events_detail_initials($speakerName, 'SP')) ?></span>
                                    <span>
                                        <strong><?= cms_events_detail_e($speakerName) ?></strong>
                                        <?php if ($speakerSubtitle !== ''): ?><small><?= cms_events_detail_e($speakerSubtitle) ?></small><?php endif; ?>
                                    </span>
                            <?php if ($speakerLink !== ''): ?>
                                </a>
                            <?php else: ?>
                                </article>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <aside class="ev-detail-sidebar" aria-label="<?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.aria.sidebar')) ?>">
            <section class="ev-detail-card ev-detail-cta" aria-labelledby="ev-detail-cta-heading">
                <h2 id="ev-detail-cta-heading" class="ev-detail-card-title"><?= cms_events_detail_icon('ticket') ?><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.heading_participation')) ?></h2>
                <p class="ev-detail-price"><?= cms_events_detail_e($priceLabel) ?> <small><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.registration_required')) ?></small></p>
                <?php if ($capacity > 0): ?>
                    <p><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.seats_left', ['count' => (string) $seatsLeft])) ?></p>
                <?php else: ?>
                    <p><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.capacity_unlimited')) ?></p>
                <?php endif; ?>
                <div class="ev-detail-actions">
                    <?php if (!$isFullyBooked && $registrationUrl !== ''): ?>
                        <a class="ev-detail-button ev-detail-button--primary" href="<?= cms_events_detail_e($registrationUrl) ?>" target="_blank" rel="noopener noreferrer"><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.button_register')) ?></a>
                    <?php elseif (!$isFullyBooked && $onlineUrl !== ''): ?>
                        <a class="ev-detail-button ev-detail-button--primary" href="<?= cms_events_detail_e($onlineUrl) ?>" target="_blank" rel="noopener noreferrer"><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.button_join_online')) ?></a>
                    <?php else: ?>
                        <button class="ev-detail-button ev-detail-button--primary" type="button" disabled><?= cms_events_detail_e($isFullyBooked ? cms_events_detail_t('cms_events.detail.status.sold_out') : cms_events_detail_t('cms_events.detail.status.registration_soon')) ?></button>
                    <?php endif; ?>
                    <?php if ($calendarUrl !== ''): ?>
                        <a class="ev-detail-button ev-detail-button--ghost" href="<?= cms_events_detail_e($calendarUrl) ?>"><?= cms_events_detail_icon('calendar') ?><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.button_calendar')) ?></a>
                    <?php endif; ?>
                </div>
            </section>

            <section class="ev-detail-card" aria-labelledby="ev-detail-social-heading">
                <h2 id="ev-detail-social-heading" class="ev-detail-card-title"><?= cms_events_detail_icon('globe') ?><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.heading_social')) ?></h2>
                <div class="ev-detail-social" aria-label="<?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.aria.social_links')) ?>">
                    <?php if ($websiteUrl !== ''): ?>
                        <a class="ev-detail-social__website" href="<?= cms_events_detail_e($websiteUrl) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.social.website')) ?>"><?= cms_events_detail_icon('globe') ?></a>
                    <?php endif; ?>
                    <a href="<?= cms_events_detail_e($shareLinkedin) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.social.linkedin')) ?>"><?= cms_events_detail_icon('linkedin') ?></a>
                    <a href="<?= cms_events_detail_e($shareX) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.social.x')) ?>"><?= cms_events_detail_icon('x') ?></a>
                    <a href="<?= cms_events_detail_e($shareMail) ?>" aria-label="<?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.social.mail')) ?>"><?= cms_events_detail_icon('mail') ?></a>
                </div>
            </section>

            <section class="ev-detail-card" aria-labelledby="ev-detail-facts-heading">
                <h2 id="ev-detail-facts-heading" class="ev-detail-card-title"><?= cms_events_detail_icon('details') ?><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.heading_details')) ?></h2>
                <dl class="ev-detail-facts">
                    <?php foreach ($facts as $label => $value): ?>
                        <div><dt><?= cms_events_detail_e((string) $label) ?></dt><dd><?= cms_events_detail_e($value) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            </section>

            <?php if ($locationText !== '' || $fullAddressText !== ''): ?>
                <section class="ev-detail-card" aria-labelledby="ev-detail-location-heading">
                    <h2 id="ev-detail-location-heading" class="ev-detail-card-title"><?= cms_events_detail_icon('pin') ?><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.heading_venue')) ?></h2>
                    <div class="ev-detail-map" role="img" aria-label="<?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.map_aria', ['location' => $locationText !== '' ? $locationText : $fullAddressText])) ?>"><span><?= cms_events_detail_icon('pin') ?></span></div>
                    <?php if ($locationText !== ''): ?><p class="ev-detail-location-name"><?= cms_events_detail_e($locationText) ?></p><?php endif; ?>
                    <?php if ($fullAddressText !== ''): ?><p class="ev-detail-location-address"><?= cms_events_detail_e($fullAddressText) ?></p><?php endif; ?>
                </section>
            <?php endif; ?>

            <?php if ($relatedEvents !== []): ?>
                <section class="ev-detail-card" aria-labelledby="ev-detail-related-heading">
                    <h2 id="ev-detail-related-heading" class="ev-detail-card-title"><?= cms_events_detail_icon('calendar') ?><?= cms_events_detail_e(cms_events_detail_t('cms_events.detail.heading_related')) ?></h2>
                    <div class="ev-detail-related">
                        <?php foreach ($relatedEvents as $related): ?>
                            <?php
                            $related = is_array($related) ? (object) $related : $related;
                            if (!is_object($related)) {
                                continue;
                            }
                            $relatedParts = cms_events_detail_date_parts((string) ($related->event_date ?? ''));
                            $relatedUrl = function_exists('cms_event_url') ? cms_event_url($related) : $baseUrl . '/events/' . (int) ($related->id ?? 0);
                            $relatedPlace = cms_events_detail_location_text((string) ($related->location ?? ''), (string) ($related->city ?? ''));
                            ?>
                            <a class="ev-detail-related__item" href="<?= cms_events_detail_e($relatedUrl) ?>">
                                <span class="ev-detail-related__date" aria-hidden="true"><strong><?= cms_events_detail_e($relatedParts['day']) ?></strong><small><?= cms_events_detail_e($relatedParts['month_short']) ?></small></span>
                                <span><strong><?= cms_events_detail_e(trim((string) ($related->title ?? '')) ?: cms_events_detail_t('cms_events.detail.fallback_event')) ?></strong><?php if ($relatedPlace !== ''): ?><small><?= cms_events_detail_e($relatedPlace) ?></small><?php endif; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </aside>
    </div>
</main>
