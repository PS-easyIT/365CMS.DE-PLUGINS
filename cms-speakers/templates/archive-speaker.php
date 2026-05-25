<?php
/**
 * Speaker Archive Template – Plugin-Content only for CMS-PHINIT.
 *
 * @package CMS_Speakers
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!isset($speakers, $settings)) {
    return;
}

$speakers = (array) $speakers;
$baseUrl = defined('SITE_URL') ? rtrim((string) SITE_URL, '/') : '';
$archiveSlug = preg_replace('/[^a-z0-9-]+/i', '-', (string) ($settings['archive_slug'] ?? 'speakers')) ?: 'speakers';
$archiveUrl = $baseUrl . '/' . trim($archiveSlug, '-') . '/';
$curPage = max(1, (int) ($page ?? 1));
$totalPages = max(1, (int) ($pages ?? 1));
$topicOptions = [];

foreach ($speakers as $speaker) {
    $topics = [];
    if (isset($speaker->_topics) && is_array($speaker->_topics)) {
        $topics = $speaker->_topics;
    } elseif (!empty($speaker->topics)) {
        $decoded = json_decode((string) $speaker->topics, true);
        $topics = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', (string) $speaker->topics)));
    } elseif (!empty($speaker->expertise)) {
        $topics = array_filter(array_map('trim', explode(',', (string) $speaker->expertise)));
    }

    foreach ($topics as $topic) {
        $topicText = trim((string) $topic);
        if ($topicText !== '') {
            $topicOptions[$topicText] = $topicText;
        }
    }
}

ksort($topicOptions, SORT_NATURAL | SORT_FLAG_CASE);
?>
<main class="phinit-plugin cms-speaker-wrap" data-cms-speaker-filter-root>
    <header class="cms-speaker-head">
        <p class="phinit-overline">Speaker</p>
        <h1>Speaker</h1>
        <p class="cms-speaker-head__subtitle">Unsere Referenten und Experten</p>
    </header>

    <nav class="cms-speaker-filter" aria-label="Speakerfilter">
        <div class="phinit-field cms-speaker-filter__field">
            <label for="cms-speaker-topic">Thema</label>
            <select id="cms-speaker-topic" class="phinit-select" data-cms-speaker-filter="topic">
                <option value="">Alle Themen</option>
                <?php foreach ($topicOptions as $topic): ?>
                    <?php $topicValue = mb_strtolower((string) $topic, 'UTF-8'); ?>
                    <option value="<?= htmlspecialchars($topicValue, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $topic, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="phinit-field cms-speaker-filter__field cms-speaker-filter__field--search">
            <label for="cms-speaker-search">Suche</label>
            <input id="cms-speaker-search" class="phinit-input" type="search" placeholder="Name, Unternehmen, Thema..." data-cms-speaker-filter="search">
        </div>

        <button type="button" class="phinit-btn phinit-btn--secondary cms-speaker-filter__reset" data-cms-speaker-reset>Filter zurücksetzen</button>
    </nav>

    <section class="cms-speaker-grid" aria-label="Speaker-Liste">
        <?php if (empty($speakers)): ?>
            <div class="cms-speaker-empty phinit-empty-state" role="status" aria-live="polite">
                <i class="ti ti-users" aria-hidden="true"></i>
                <p class="cms-speaker-empty__title">Keine Speaker gefunden.</p>
            </div>
        <?php else: ?>
            <?php foreach ($speakers as $speaker): ?>
                <?php include __DIR__ . '/speaker-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if (!empty($speakers)): ?>
        <div class="cms-speaker-empty cms-speaker-empty--js phinit-empty-state" role="status" aria-live="polite" hidden data-cms-speaker-empty>
            <i class="ti ti-users" aria-hidden="true"></i>
            <p class="cms-speaker-empty__title">Keine Speaker gefunden.</p>
            <button type="button" class="phinit-btn phinit-btn--link" data-cms-speaker-reset>Filter zurücksetzen</button>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="cms-speaker-pagination" aria-label="Seitennavigation">
            <?php if ($curPage > 1): ?>
                <a class="cms-speaker-page" href="<?= htmlspecialchars($archiveUrl . '?page=' . ($curPage - 1), ENT_QUOTES, 'UTF-8') ?>" rel="prev">Zurück</a>
            <?php endif; ?>
            <?php for ($pageNumber = max(1, $curPage - 2); $pageNumber <= min($totalPages, $curPage + 2); $pageNumber++): ?>
                <a class="cms-speaker-page<?= $pageNumber === $curPage ? ' is-active' : '' ?>" href="<?= htmlspecialchars($archiveUrl . '?page=' . $pageNumber, ENT_QUOTES, 'UTF-8') ?>"<?= $pageNumber === $curPage ? ' aria-current="page"' : '' ?>><?= (int) $pageNumber ?></a>
            <?php endfor; ?>
            <?php if ($curPage < $totalPages): ?>
                <a class="cms-speaker-page" href="<?= htmlspecialchars($archiveUrl . '?page=' . ($curPage + 1), ENT_QUOTES, 'UTF-8') ?>" rel="next">Weiter</a>
            <?php endif; ?>
        </nav>
    <?php endif; ?>
</main>