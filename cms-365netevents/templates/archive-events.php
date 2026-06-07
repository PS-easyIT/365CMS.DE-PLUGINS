<?php
/** @var array<int, object> $events */
/** @var array<string, string> $settings */
/** @var array<string, string> $filters */
/** @var array{current:int,total:int,per_page:int,total_pages:int} $pagination */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
$base = rtrim((string) SITE_URL, '/');
$q = htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$showPast = (string) ($filters['past'] ?? '0') === '1';
$toggleUrl = $base . '/events' . ($showPast ? '' : '?past=1');
$futureLabel = trim((string) ($settings['archive_current_month_label'] ?? ''));
$futureLabel = ($futureLabel === '' || $futureLabel === 'Aktueller Monat') ? 'Zukünftige Events' : $futureLabel;
$futureButton = trim((string) ($settings['archive_current_button'] ?? ''));
$futureButton = ($futureButton === '' || $futureButton === 'Zurück zum aktuellen Monat') ? 'Zurück zu zukünftigen Events' : $futureButton;
$futureEmpty = trim((string) ($settings['archive_empty_current'] ?? ''));
$futureEmpty = ($futureEmpty === '' || $futureEmpty === 'Für den aktuellen Monat wurden keine Events gefunden.') ? 'Es wurden keine zukünftigen Events gefunden.' : $futureEmpty;
$pagination = is_array($pagination ?? null) ? $pagination : ['current' => 1, 'total' => count($events), 'per_page' => 15, 'total_pages' => 1];
$currentPage = max(1, (int) ($pagination['current'] ?? 1));
$totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$paginationUrl = static function (int $page) use ($base, $filters): string {
    $query = [];
    if ((string) ($filters['q'] ?? '') !== '') {
        $query['q'] = (string) $filters['q'];
    }
    if ((string) ($filters['past'] ?? '0') === '1') {
        $query['past'] = '1';
    }
    if ($page > 1) {
        $query['page'] = (string) $page;
    }

    return $base . '/events' . ($query !== [] ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
};

$eventCardExcerpt = static function (object $event): string {
    $raw = (string) ($event->description ?? '');
    if ($raw === '') {
        return '';
    }

    $text = trim((string) preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    if ($text === '') {
        return '';
    }

    return function_exists('mb_substr')
        ? (string) mb_substr($text, 0, 360, 'UTF-8')
        : substr($text, 0, 360);
};
?>
<main class="cms-events-public cms-events-archive">
    <div class="cms-events-container">
        <section class="cms-events-hero">
            <span class="cms-events-kicker"><?= htmlspecialchars((string) ($settings['archive_kicker'] ?? '365NET Event Directory'), ENT_QUOTES, 'UTF-8') ?></span>
            <h1><?= htmlspecialchars((string) ($settings['archive_title'] ?? 'Events & Messen'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p><?= htmlspecialchars((string) ($settings['archive_description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <p class="cms-events-current-label"><?= htmlspecialchars($showPast ? (string) ($settings['archive_past_button'] ?? 'Vergangene Events') : $futureLabel, ENT_QUOTES, 'UTF-8') ?></p>
        </section>

        <form method="GET" class="cms-events-search" role="search">
            <?php if ($showPast): ?><input type="hidden" name="past" value="1"><?php endif; ?>
            <input type="search" name="q" value="<?= $q ?>" placeholder="<?= htmlspecialchars((string) ($settings['archive_search_placeholder'] ?? 'Event, Ort, Thema oder Veranstalter suchen …'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Events suchen">
            <button type="submit"><?= htmlspecialchars((string) ($settings['archive_search_button'] ?? 'Suchen'), ENT_QUOTES, 'UTF-8') ?></button>
            <a href="<?= htmlspecialchars($toggleUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($showPast ? $futureButton : (string) ($settings['archive_past_button'] ?? 'Vergangene Events anzeigen'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php if ($q !== ''): ?><a href="<?= htmlspecialchars($base . '/events' . ($showPast ? '?past=1' : ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($settings['archive_reset_label'] ?? 'Zurücksetzen'), ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
        </form>

        <?php if ($events === []): ?>
            <div class="cms-events-empty"><?= htmlspecialchars($showPast ? (string) ($settings['archive_empty_past'] ?? 'Keine vergangenen Events gefunden.') : $futureEmpty, ENT_QUOTES, 'UTF-8') ?></div>
        <?php else: ?>
            <div class="cms-events-grid">
                <?php foreach ($events as $event): ?>
                    <?php $eventUrl = $base . '/events/' . rawurlencode((string) $event->slug); ?>
                    <?php $cardExcerpt = $eventCardExcerpt($event); ?>
                    <article class="cms-events-card">
                        <h2><a href="<?= htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $event->title, ENT_QUOTES, 'UTF-8') ?></a></h2>
                        <div class="cms-events-card__meta">
                            <span><?= htmlspecialchars((string) ($event->date_label ?? 'Termin offen'), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (!empty($event->location)): ?><span><?= htmlspecialchars((string) $event->location, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                            <?php if (!empty($event->price_class)): ?><span><?= htmlspecialchars((string) $event->price_class, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                            <span class="cms-events-card__speaker-count"><?= (int) ($event->speaker_count ?? 0) ?> Speaker</span>
                        </div>
                        <?php if ($cardExcerpt !== ''): ?><p class="cms-events-card__excerpt"><?= htmlspecialchars($cardExcerpt, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                        <?php $cardTags = array_filter(array_map('trim', explode(',', (string) (($event->tags ?? '') ?: ($event->categories ?? ''))))); if ($cardTags !== []): ?><div class="cms-events-mini-tags"><?php foreach (array_slice($cardTags, 0, 4) as $tag): ?><span><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></div><?php endif; ?>
                        <footer>
                            <?php if (!empty($event->event_format)): ?><span><?= htmlspecialchars((string) $event->event_format, ENT_QUOTES, 'UTF-8') ?></span><?php elseif (!empty($event->organizer)): ?><span><?= htmlspecialchars((string) $event->organizer, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                            <a class="cms-events-card__more" href="<?= htmlspecialchars($eventUrl, ENT_QUOTES, 'UTF-8') ?>">Mehr Infos …</a>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($totalPages > 1): ?>
            <nav class="cms-events-pagination" aria-label="Event-Seiten">
                <a class="cms-events-pagination__link<?= $currentPage <= 1 ? ' is-disabled' : '' ?>" href="<?= htmlspecialchars($paginationUrl(max(1, $currentPage - 1)), ENT_QUOTES, 'UTF-8') ?>" aria-disabled="<?= $currentPage <= 1 ? 'true' : 'false' ?>">Zurück</a>
                <div class="cms-events-pagination__pages">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === 1 || $i === $totalPages || abs($i - $currentPage) <= 2): ?>
                            <a class="cms-events-pagination__page<?= $i === $currentPage ? ' is-active' : '' ?>" href="<?= htmlspecialchars($paginationUrl($i), ENT_QUOTES, 'UTF-8') ?>"<?= $i === $currentPage ? ' aria-current="page"' : '' ?>><?= $i ?></a>
                        <?php elseif ($i === $currentPage - 3 || $i === $currentPage + 3): ?>
                            <span class="cms-events-pagination__ellipsis" aria-hidden="true">…</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <a class="cms-events-pagination__link<?= $currentPage >= $totalPages ? ' is-disabled' : '' ?>" href="<?= htmlspecialchars($paginationUrl(min($totalPages, $currentPage + 1)), ENT_QUOTES, 'UTF-8') ?>" aria-disabled="<?= $currentPage >= $totalPages ? 'true' : 'false' ?>">Weiter</a>
            </nav>
        <?php endif; ?>
    </div>
</main>
