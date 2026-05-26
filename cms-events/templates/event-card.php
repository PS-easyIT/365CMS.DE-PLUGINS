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

$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$titleRaw = trim((string) ($e->title ?? ''));
$title = htmlspecialchars($titleRaw !== '' ? $titleRaw : 'Event', ENT_QUOTES, 'UTF-8');
$categoryRaw = trim((string) ($e->category ?? ''));
$category = htmlspecialchars($categoryRaw !== '' ? $categoryRaw : 'Event', ENT_QUOTES, 'UTF-8');
$locationRaw = trim((string) ($e->location ?? ''));
$cityRaw = trim((string) ($e->city ?? ''));
$locationText = trim($locationRaw . ($locationRaw !== '' && $cityRaw !== '' ? ', ' : '') . $cityRaw);
$eventUrl = function_exists('cms_event_url') ? cms_event_url($e) : $baseUrl . '/events/' . $id;
$imageUrl = cms_events_view_public_url($e->image_url ?? $e->banner_url ?? null);
$tags = !empty($e->tags) ? (json_decode((string) $e->tags, true) ?: []) : [];
if (!is_array($tags)) {
  $tags = [];
}
[$day, $monthShort, $displayDate, $machineDate, $year] = cms_events_view_date_parts((string) ($e->event_date ?? ''));
$eventMonth = $machineDate !== '' ? substr($machineDate, 5, 2) : '';
$eventTime = trim((string) ($e->event_time ?? ''));
$dateLine = $displayDate . ($eventTime !== '' ? ' · ' . substr($eventTime, 0, 5) . ' Uhr' : '');
$timestamp = $machineDate !== '' ? strtotime($machineDate) : 0;
$today = strtotime('today');
$isSoon = $timestamp && $today !== false && $timestamp >= $today && $timestamp <= ($today + 86400);
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

$filterText = cms_events_view_lowercase(trim($titleRaw . ' ' . $categoryRaw . ' ' . $locationText . ' ' . $speakerName . ' ' . implode(' ', array_map('strval', $tags))));
?>
<article class="phinit-card cms-events-card"
     data-cms-events-card
  data-category="<?= htmlspecialchars(cms_events_view_lowercase($categoryRaw), ENT_QUOTES, 'UTF-8') ?>"
     data-month="<?= htmlspecialchars($eventMonth, ENT_QUOTES, 'UTF-8') ?>"
     data-year="<?= htmlspecialchars($year, ENT_QUOTES, 'UTF-8') ?>"
     data-name="<?= htmlspecialchars($filterText, ENT_QUOTES, 'UTF-8') ?>">
  <div class="cms-events-card__media">
    <?php if ($imageUrl !== ''): ?>
      <img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= $title ?>" width="640" height="360" loading="lazy" decoding="async">
    <?php else: ?>
      <div class="cms-events-card__placeholder" aria-hidden="true"><i class="ti ti-photo"></i></div>
    <?php endif; ?>
    <span class="cms-events-card__badge"><?= $category ?></span>
    <?php if ($isSoon): ?>
      <span class="cms-events-card__badge cms-events-card__badge--soon">Bald</span>
    <?php endif; ?>
  </div>

  <div class="cms-events-card__body">
    <div class="cms-events-card__date-row">
      <?php if ($machineDate !== ''): ?>
        <time class="cms-events-card__date-block" datetime="<?= htmlspecialchars($machineDate, ENT_QUOTES, 'UTF-8') ?>">
          <span class="cms-events-card__day"><?= htmlspecialchars($day, ENT_QUOTES, 'UTF-8') ?></span>
        </time>
        <span class="cms-events-card__date-full"><?= htmlspecialchars($dateLine, ENT_QUOTES, 'UTF-8') ?></span>
      <?php endif; ?>
    </div>

    <h2 class="cms-events-card__title"><a href="<?= htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') ?>"><?= $title ?></a></h2>

    <?php if ($locationText !== ''): ?>
      <p class="cms-events-card__meta"><i class="ti ti-map-pin" aria-hidden="true"></i><?= htmlspecialchars($locationText, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php if ($speakerName !== ''): ?>
      <p class="cms-events-card__meta"><i class="ti ti-user" aria-hidden="true"></i>
        <?php if ($speakerUrl !== ''): ?>
          <a href="<?= htmlspecialchars($speakerUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($speakerName, ENT_QUOTES, 'UTF-8') ?></a>
        <?php else: ?>
          <?= htmlspecialchars($speakerName, ENT_QUOTES, 'UTF-8') ?>
        <?php endif; ?>
      </p>
    <?php endif; ?>

    <?php if (!empty($tags)): ?>
      <div class="cms-events-card__tags" aria-label="Event-Tags">
        <?php foreach (array_slice($tags, 0, 4) as $tag): ?>
          <span class="cms-events-tag"><?= htmlspecialchars((string) $tag, ENT_QUOTES, 'UTF-8') ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <footer class="cms-events-card__footer">
      <span class="cms-events-card__price"><?= cms_events_view_price_label($e) ?></span>
      <a href="<?= htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') ?>" class="phinit-btn phinit-btn--primary cms-events-card__button">Details</a>
    </footer>
  </div>
</article>
