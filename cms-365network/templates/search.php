<?php
/**
 * Public 365NETWORK scoped search template.
 *
 * @package CMS_365NETWORK
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$searchQuery = trim((string) ($searchQuery ?? ''));
$searchParam = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($searchParam ?? 'q'))) ?: 'q';
$searchUrl = trim((string) ($searchUrl ?? '/365network/search')) ?: '/365network/search';
$searchResults = is_array($searchResults ?? null) ? $searchResults : [];
$searchTotal = max(0, (int) ($searchTotal ?? 0));
$hasQuery = $searchQuery !== '';
$queryLength = function_exists('mb_strlen') ? mb_strlen($searchQuery, 'UTF-8') : strlen($searchQuery);
$queryTooShort = $hasQuery && $queryLength < 2;
?>
<main id="cms-365network-search" class="cms-network-hub-wrap n365-landing n365-search-page" aria-labelledby="n365-search-title">
    <header class="n365-search-hero">
        <p class="n365-search-overline">365NETWORK Suche</p>
        <h1 id="n365-search-title">Events, Speaker, Firmen und Experten finden</h1>
        <p>Diese Suche ist vom globalen 365CMS getrennt und durchsucht ausschließlich die vier Netzwerk-Bereiche.</p>

        <form class="n365-search-form" role="search" method="GET" action="<?php echo htmlspecialchars($searchUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="365NETWORK durchsuchen">
            <label for="n365-search-query">Suchbegriff</label>
            <div class="n365-search-form__row">
                <input id="n365-search-query" name="<?php echo htmlspecialchars($searchParam, ENT_QUOTES, 'UTF-8'); ?>" type="search" value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>" placeholder="z. B. Azure, Copilot, Workshop" autocomplete="off">
                <button type="submit">Suchen</button>
            </div>
        </form>
    </header>

    <?php if (!$hasQuery): ?>
    <section class="n365-search-empty" role="status" aria-live="polite">
        <p class="n365-search-empty__title">Suchbegriff eingeben</p>
        <p>Starte mit mindestens zwei Zeichen. Angezeigt werden nur Treffer aus Events, Speakern, Firmen und Experten.</p>
    </section>
    <?php elseif ($queryTooShort): ?>
    <section class="n365-search-empty" role="status" aria-live="polite">
        <p class="n365-search-empty__title">Suchbegriff zu kurz</p>
        <p>Bitte gib mindestens zwei Zeichen ein, damit die Netzwerk-Suche starten kann.</p>
    </section>
    <?php else: ?>
    <section class="n365-search-summary" aria-label="Suchzusammenfassung">
        <p><strong><?php echo (int) $searchTotal; ?></strong> Treffer für <strong><?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?></strong></p>
    </section>

    <?php if ($searchTotal === 0): ?>
    <section class="n365-search-empty" role="status" aria-live="polite">
        <p class="n365-search-empty__title">Keine Netzwerk-Treffer gefunden</p>
        <p>Versuche einen anderen Begriff oder suche allgemeiner, zum Beispiel nach Thema, Stadt, Firmenname oder Rolle.</p>
    </section>
    <?php else: ?>
    <section class="n365-search-results" aria-label="365NETWORK Suchergebnisse">
        <?php foreach ($searchResults as $group): ?>
            <?php
            $items = is_array($group['items'] ?? null) ? $group['items'] : [];
            if ($items === []) {
                continue;
            }
            $groupKey = preg_replace('/[^a-z0-9_-]+/i', '-', (string) ($group['key'] ?? 'gruppe')) ?: 'gruppe';
            ?>
            <section class="n365-search-group" aria-labelledby="n365-search-group-<?php echo htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8'); ?>">
                <h2 id="n365-search-group-<?php echo htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="ti <?php echo htmlspecialchars((string) ($group['icon'] ?? 'ti-list-search'), ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true"></i>
                    <?php echo htmlspecialchars((string) ($group['label'] ?? 'Treffer'), ENT_QUOTES, 'UTF-8'); ?>
                    <span><?php echo (int) count($items); ?></span>
                </h2>

                <div class="n365-search-list">
                    <?php foreach ($items as $item): ?>
                    <article class="n365-search-result">
                        <div class="n365-search-result__icon" aria-hidden="true">
                            <i class="ti <?php echo htmlspecialchars((string) ($item['icon'] ?? 'ti-link'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                        </div>
                        <div class="n365-search-result__body">
                            <p class="n365-search-result__type"><?php echo htmlspecialchars((string) ($item['type_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <h3><a href="<?php echo htmlspecialchars((string) ($item['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($item['title'] ?? 'Treffer'), ENT_QUOTES, 'UTF-8'); ?></a></h3>
                            <?php if (trim((string) ($item['meta'] ?? '')) !== ''): ?>
                            <p class="n365-search-result__meta"><?php echo htmlspecialchars((string) ($item['meta'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                            <?php if (trim((string) ($item['excerpt'] ?? '')) !== ''): ?>
                            <p class="n365-search-result__excerpt"><?php echo htmlspecialchars((string) ($item['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>
    <?php endif; ?>
</main>
