<?php
/**
 * Event Card Template – PHINIT Publicsite Card.
 *
 * Scope-Variablen:
 *   $event    – object
 *   $settings – array (aus archive übergeben)
 *   $event_speakers_map – array<int,array<object>> optionaler Batch-Preload
 *
 * @package CMS_Events
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
  exit;
}

if (!isset($event)) {
    return;
}

if (!function_exists('cms_events_view_lowercase')) {
  function cms_events_view_lowercase(string $value): string
  {
    return function_exists('mb_strtolower')
      ? mb_strtolower($value, 'UTF-8')
      : strtolower($value);
  }
}

$e = is_object($event) ? $event : (is_array($event) ? (object) $event : (object) []);
$id = (int) ($e->id ?? 0);

if (!function_exists('cms_events_view_date_parts')) {
  function cms_events_view_date_parts(?string $date): array
  {
    $timestamp = $date ? strtotime($date) : 0;
    if (!$timestamp) {
      return ['', '', '', '', ''];
    }

    $weekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
    $monthsFull = [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    $monthsShort = [1 => 'Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
    $month = (int) date('n', $timestamp);

    return [
      date('d', $timestamp),
      $monthsShort[$month] ?? date('M', $timestamp),
      $weekdays[(int) date('w', $timestamp)] . ', ' . date('d', $timestamp) . '. ' . ($monthsFull[$month] ?? date('F', $timestamp)) . ' ' . date('Y', $timestamp),
      date('Y-m-d', $timestamp),
      date('Y', $timestamp),
    ];
  }
}

if (!function_exists('cms_events_view_price_label')) {
  /**
   * @param mixed $event
   */
  function cms_events_view_price_label($event): string
  {
    if (is_array($event)) {
      $event = (object) $event;
    }

    if (!is_object($event)) {
      return 'Kostenlos';
    }

    $priceType = (string) ($event->price_type ?? 'free');
    $price = (float) ($event->price ?? 0);
    $currency = trim((string) ($event->price_currency ?? 'EUR')) ?: 'EUR';

    if ($priceType === 'free' || $price <= 0) {
      return 'Kostenlos';
    }

    $currencyUpper = strtoupper($currency);
    if ($currencyUpper === 'EUR') {
      $currencySymbol = '€';
    } elseif ($currencyUpper === 'USD') {
      $currencySymbol = '$';
    } elseif ($currencyUpper === 'CHF') {
      $currencySymbol = 'CHF';
    } else {
      $currencySymbol = preg_replace('/[^A-Z]/i', '', $currency) ?: '€';
    }

    return $currencySymbol . ' ' . number_format($price, 2, ',', '.');
  }
}

if (!function_exists('cms_events_view_public_url')) {
  /**
   * @param mixed $url
   */
  function cms_events_view_public_url($url): string
  {
    $url = str_replace('\\', '/', trim((string) $url));
    if ($url === '' || strlen($url) > 1000) {
      return '';
    }

    if (preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
      return '';
    }

    if ((strpos($url, '/') === 0) || preg_match('#^(uploads|ASSETS|assets|plugins)/#i', $url) === 1) {
      $baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
      $path = ltrim($url, '/');
      if ($path === '' || strpos($path, '..') !== false) {
        return '';
      }

      return $baseUrl . '/' . $path;
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

if (!function_exists('cms_events_view_location_text')) {
  function cms_events_view_location_text(string $location, string $city): string
  {
    $parts = array_filter(array_map('trim', array_merge(
      preg_split('/\s*,\s*/', $location) ?: [],
      [$city]
    )), static fn(string $part): bool => $part !== '');

    $seen = [];
    $unique = [];
    foreach ($parts as $part) {
      $key = trim(cms_events_view_lowercase(preg_replace('/\s+/', ' ', $part) ?? $part));
      if ($key === '' || isset($seen[$key])) {
        continue;
      }

      $seen[$key] = true;
      $unique[] = $part;
    }

    return implode(', ', $unique);
  }
}

if (!function_exists('cms_events_view_trim_text')) {
  function cms_events_view_trim_text(string $text, int $length): string
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

$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$titleRaw = trim((string) ($e->title ?? ''));
$title = htmlspecialchars($titleRaw !== '' ? $titleRaw : 'Event', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$categoryRaw = trim((string) ($e->category ?? ''));
$category = htmlspecialchars($categoryRaw !== '' ? $categoryRaw : 'Event', ENT_QUOTES, 'UTF-8');
$locationRaw = trim((string) ($e->location ?? ''));
$cityRaw = trim((string) ($e->city ?? ''));
$locationText = cms_events_view_location_text($locationRaw, $cityRaw);
$priceTypeRaw = cms_events_view_lowercase(trim((string) ($e->price_type ?? 'free')));
$isPaidEvent = !in_array($priceTypeRaw, ['', 'free'], true);
$priceLabel = cms_events_view_price_label($e);
if ($isPaidEvent && $priceLabel === 'Kostenlos') {
  $priceLabel = $priceTypeRaw === 'donation' ? 'Spendenbasis' : 'Kostenpflichtig';
}
$priceClass = $isPaidEvent ? 'event-card-price--paid' : 'event-card-price--free';
$eventUrl = function_exists('cms_event_url') ? cms_event_url($e) : $baseUrl . '/events/' . $id;
$imageUrl = cms_events_view_public_url($e->image_url ?? $e->banner_url ?? null);
$isFeatured = !empty($e->is_featured);
$isOnline = !empty($e->is_online);
$eventModeLabel = $isOnline ? ($locationText !== '' ? 'Hybrid' : 'Online') : 'Präsenz';
$eventModeClass = $isOnline ? ($locationText !== '' ? 'event-badge--hybrid' : 'event-badge--online') : 'event-badge--onsite';
$eventModeIcon = $isOnline ? ($locationText !== '' ? 'world' : 'video') : 'map-pin';
$organizerName = trim((string) ($e->organizer_name ?? ''));
$tags = !empty($e->tags) ? (json_decode((string) $e->tags, true) ?: []) : [];
if (!is_array($tags)) {
  $tags = [];
}
[$day, $monthShort, $displayDate, $machineDate, $year] = cms_events_view_date_parts((string) ($e->event_date ?? ''));
$eventMonth = $machineDate !== '' ? (string) ((int) substr($machineDate, 5, 2)) : '';
$eventTimestamp = $machineDate !== '' ? strtotime($machineDate) : 0;
$currentMonthStart = strtotime(date('Y-m-01'));
$isPastEvent = $eventTimestamp > 0 && $currentMonthStart !== false && $eventTimestamp < $currentMonthStart;
$eventTime = trim((string) ($e->event_time ?? ''));
$dateLine = $displayDate !== '' ? $displayDate . ($eventTime !== '' ? ' · ' . substr($eventTime, 0, 5) . ' Uhr' : '') : '';
$speakerName = '';
$speakerUrl = '';
$eventSpeakers = [];

if (isset($event_speakers_map) && is_array($event_speakers_map)) {
  $eventSpeakers = $event_speakers_map[$id] ?? [];
}

if (!is_array($eventSpeakers)) {
  $eventSpeakers = [];
}

if (!empty($eventSpeakers)) {
  $speaker = $eventSpeakers[0];
  if (is_array($speaker)) {
    $speaker = (object) $speaker;
  }
  if (!is_object($speaker)) {
    $speaker = (object) [];
  }
  $speakerName = trim((string) ($speaker->speaker_name ?? (($speaker->first_name ?? '') . ' ' . ($speaker->last_name ?? ''))));
  $speakerType = in_array((string) ($speaker->speaker_type ?? 'speaker'), ['speaker', 'expert'], true) ? (string) $speaker->speaker_type : 'speaker';
  $speakerId = (int) ($speaker->speaker_id ?? 0);
  if ($speakerId > 0) {
    $speakerUrl = $baseUrl . ($speakerType === 'expert' ? '/experts/' : '/speakers/') . $speakerId;
  }
}

$filterText = cms_events_view_lowercase(trim($titleRaw . ' ' . $categoryRaw . ' ' . $locationText . ' ' . $priceLabel . ' ' . $eventModeLabel . ' ' . $organizerName . ' ' . $speakerName . ' ' . implode(' ', array_map('strval', $tags))));
?>
<article class="phinit-card cms-events-card<?= $isPastEvent ? ' cms-events-card--past' : '' ?><?= $isFeatured ? ' cms-events-card--featured' : '' ?>"
     data-cms-events-card
     data-category="<?= htmlspecialchars($categoryRaw, ENT_QUOTES, 'UTF-8') ?>"
     data-month="<?= htmlspecialchars($eventMonth, ENT_QUOTES, 'UTF-8') ?>"
     data-year="<?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?>"
     data-title="<?= htmlspecialchars(cms_events_view_lowercase($titleRaw), ENT_QUOTES, 'UTF-8') ?>"
    data-location="<?= htmlspecialchars(cms_events_view_lowercase($locationText), ENT_QUOTES, 'UTF-8') ?>"
    data-name="<?= htmlspecialchars($filterText, ENT_QUOTES, 'UTF-8') ?>"
    data-event-url="<?= htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') ?>"
    role="link"
    tabindex="0"
    aria-label="Details zu <?= $title ?> ansehen">
  <div class="event-card-body cms-events-card__body">
    <?php if ($imageUrl !== ''): ?>
      <a class="event-card-image" href="<?= htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Details zu <?= $title ?> ansehen">
        <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $title ?>" width="640" height="360" loading="lazy" decoding="async">
      </a>
    <?php endif; ?>

    <div class="event-card-badge-row">
      <div class="event-card-badge-group">
        <?php if ($isFeatured): ?>
          <span class="event-badge event-badge--featured cms-events-card__badge">Featured</span>
        <?php endif; ?>

        <?php if ($isPastEvent): ?>
          <span class="event-badge event-badge--past cms-events-card__badge">Vergangen</span>
        <?php endif; ?>

        <?php if ($categoryRaw !== ''): ?>
          <span class="event-badge cms-events-card__badge"><?= $category ?></span>
        <?php endif; ?>
      </div>
      <span class="event-card-price <?= htmlspecialchars($priceClass, ENT_QUOTES, 'UTF-8') ?>"><i class="ti ti-ticket" aria-hidden="true"></i><?= htmlspecialchars($priceLabel, ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <div class="event-card-main">
      <?php if ($displayDate !== ''): ?>
        <time class="event-date-block" datetime="<?= htmlspecialchars($machineDate, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($displayDate, ENT_QUOTES, 'UTF-8') ?>">
          <span class="event-date-day"><?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?></span>
          <span class="event-date-month"><?= htmlspecialchars($monthShort, ENT_QUOTES, 'UTF-8') ?></span>
        </time>
      <?php endif; ?>

      <div class="event-card-content">
        <h2 class="event-card-title cms-events-card__title"><a href="<?= htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $title ?></a></h2>

        <?php if ($locationText !== '' || $eventModeLabel !== ''): ?>
          <p class="event-card-location event-meta-location cms-events-card__meta">
            <span class="event-badge event-card-mode-badge <?= htmlspecialchars($eventModeClass, ENT_QUOTES, 'UTF-8') ?> cms-events-card__badge"><i class="ti ti-<?= htmlspecialchars($eventModeIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?= htmlspecialchars($eventModeLabel, ENT_QUOTES, 'UTF-8') ?></span>
            <?php if ($locationText !== ''): ?>
              <span class="event-card-location-text"><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
          </p>
        <?php endif; ?>

        <?php if ($eventTime !== ''): ?>
          <p class="event-card-time cms-events-card__meta"><i class="ti ti-clock" aria-hidden="true"></i><?= htmlspecialchars(substr($eventTime, 0, 5) . ' Uhr', ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
      </div>
    </div>

    <footer class="event-card-footer cms-events-card__footer">
      <div class="event-card-footer-left">
        <?php if ($speakerName !== ''): ?>
          <span class="event-footer-info"><i class="ti ti-user" aria-hidden="true"></i><?= htmlspecialchars($speakerName, ENT_QUOTES, 'UTF-8') ?></span>
        <?php elseif ($organizerName !== ''): ?>
          <span class="event-footer-info"><i class="ti ti-building" aria-hidden="true"></i><?= htmlspecialchars($organizerName, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endif; ?>
      </div>

      <a href="<?= htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn-event-details cms-events-card__button">Details</a>
    </footer>
  </div>
</article>
