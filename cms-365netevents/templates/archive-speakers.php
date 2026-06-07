<?php
/** @var array<int, object> $speakers */
/** @var array<string, string> $filters */
/** @var array<string, string> $settings */
/** @var array{current:int,total:int,per_page:int,total_pages:int} $pagination */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }
$base = rtrim((string) SITE_URL, '/');
$q = htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$pagination = is_array($pagination ?? null) ? $pagination : ['current' => 1, 'total' => count($speakers), 'per_page' => 15, 'total_pages' => 1];
$currentPage = max(1, (int) ($pagination['current'] ?? 1));
$totalPages = max(1, (int) ($pagination['total_pages'] ?? 1));
$paginationUrl = static function (int $page) use ($base, $filters): string {
    $query = [];
    if ((string) ($filters['q'] ?? '') !== '') {
        $query['q'] = (string) $filters['q'];
    }
    if ($page > 1) {
        $query['page'] = (string) $page;
    }

    return $base . '/event-speakers' . ($query !== [] ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
};

$speakerCardExcerpt = static function (object $speaker): string {
    $raw = (string) ($speaker->bio ?? '');
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
<main class="cms-events-public cms-speakers-archive">
    <div class="cms-events-container">
        <section class="cms-events-hero"><span class="cms-events-kicker"><?= htmlspecialchars((string) ($settings['speaker_archive_kicker'] ?? '365NET Speaker Directory'), ENT_QUOTES, 'UTF-8') ?></span><h1><?= htmlspecialchars((string) ($settings['speaker_archive_title'] ?? 'Event-Speaker'), ENT_QUOTES, 'UTF-8') ?></h1><p><?= htmlspecialchars((string) ($settings['speaker_archive_description'] ?? 'Echte Personen mit Bühne, Erfahrung und starken Themen aus dem Event-Datensatz.'), ENT_QUOTES, 'UTF-8') ?></p></section>
        <form method="GET" class="cms-events-search" role="search"><input type="search" name="q" value="<?= $q ?>" placeholder="<?= htmlspecialchars((string) ($settings['speaker_search_placeholder'] ?? 'Speaker, Thema oder Tag suchen …'), ENT_QUOTES, 'UTF-8') ?>"><button type="submit"><?= htmlspecialchars((string) ($settings['archive_search_button'] ?? 'Suchen'), ENT_QUOTES, 'UTF-8') ?></button></form>
        <div class="cms-events-grid cms-speakers-grid">
            <?php foreach ($speakers as $speaker): ?>
                <?php $speakerExcerpt = $speakerCardExcerpt($speaker); ?>
                <article class="cms-events-card cms-speaker-card"><?php if (!empty($speaker->avatar_url)): ?><img class="cms-speaker-photo cms-speaker-photo--card" src="<?= htmlspecialchars((string) $speaker->avatar_url, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars((string) ($speaker->avatar_alt ?? $speaker->display_name ?? ''), ENT_QUOTES, 'UTF-8') ?>" loading="lazy"><?php else: ?><span class="cms-speaker-avatar"><?= htmlspecialchars(strtoupper(substr((string) ($speaker->display_name ?? 'S'), 0, 1)), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?><h2><a href="<?= htmlspecialchars($base . '/event-speakers/' . rawurlencode((string) $speaker->slug), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $speaker->display_name, ENT_QUOTES, 'UTF-8') ?></a></h2><?php if ($speakerExcerpt !== ''): ?><p class="cms-speaker-card__excerpt"><?= htmlspecialchars($speakerExcerpt, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?><?php $speakerTags = array_filter(array_map('trim', explode(',', (string) (($speaker->tags ?? '') ?: ($speaker->specializations ?? ''))))); if ($speakerTags !== []): ?><div class="cms-events-mini-tags"><?php foreach (array_slice($speakerTags, 0, 4) as $tag): ?><span><?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?></span><?php endforeach; ?></div><?php endif; ?><?php if (!empty($speaker->price_class) || (int) ($speaker->event_count ?? 0) > 0): ?><footer><?php if (!empty($speaker->price_class)): ?><span><?= htmlspecialchars((string) $speaker->price_class, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?><?php if ((int) ($speaker->event_count ?? 0) > 0): ?><span class="cms-speaker-card__events"><?= (int) ($speaker->event_count ?? 0) ?> Events</span><?php endif; ?></footer><?php endif; ?></article>
            <?php endforeach; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="cms-events-pagination" aria-label="Speaker-Seiten">
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
