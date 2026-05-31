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

$publicLang = isset($publicLang) && $publicLang === 'en' ? 'en' : 'de';
$publicI18n = is_array($publicI18n ?? null) ? $publicI18n : [];
$translate = static function (string $key, string $fallbackDe, string $fallbackEn = '') use ($publicI18n, $publicLang): string {
    $fallback = $publicLang === 'en' ? ($fallbackEn !== '' ? $fallbackEn : $fallbackDe) : $fallbackDe;
    if (function_exists('cms_plugin_public_i18n_value')) {
        return (string) cms_plugin_public_i18n_value($publicI18n, $key, $publicLang, $fallback);
    }

    return $fallback;
};

$searchQuery = trim((string) ($searchQuery ?? ''));
$searchParam = trim((string) preg_replace('/[^a-zA-Z0-9_-]+/', '', (string) ($searchParam ?? 'q'))) ?: 'q';
$searchUrl = trim((string) ($searchUrl ?? '/365network/search')) ?: '/365network/search';
$searchResults = is_array($searchResults ?? null) ? $searchResults : [];
$searchTotal = max(0, (int) ($searchTotal ?? 0));
$hasQuery = $searchQuery !== '';
$queryLength = function_exists('mb_strlen') ? mb_strlen($searchQuery, 'UTF-8') : strlen($searchQuery);
$queryTooShort = $hasQuery && $queryLength < 2;
?>
<main id="cms-365network-search" class="cms-network-hub-wrap n365-landing n365-search-page" aria-labelledby="n365-search-title" data-n365-search-page data-live-prefix="<?php echo htmlspecialchars($translate('search.live_prefix', 'Treffer', 'Result'), ENT_QUOTES, 'UTF-8'); ?>" data-live-of="<?php echo htmlspecialchars($translate('search.live_of', 'von', 'of'), ENT_QUOTES, 'UTF-8'); ?>">
    <p class="n365-sr-only" id="n365-search-keyboard-help"><?php echo htmlspecialchars($translate('search.keyboard_help', 'In den Ergebnissen mit Pfeil hoch/runter navigieren. Pos1 und Ende springen zum ersten bzw. letzten Ergebnis.', 'Navigate results with Arrow Up/Down. Home and End jump to first/last result.'), ENT_QUOTES, 'UTF-8'); ?></p>
    <p class="n365-sr-only" id="n365-search-live-status" role="status" aria-live="polite" data-n365-search-live-status></p>
    <header class="n365-search-hero">
        <p class="n365-search-overline"><?php echo htmlspecialchars($translate('search.overline', '365NETWORK Suche', '365NETWORK Search'), ENT_QUOTES, 'UTF-8'); ?></p>
        <h1 id="n365-search-title"><?php echo htmlspecialchars($translate('search.title', 'Events, Speaker, Firmen und Experten finden', 'Find events, speakers, companies and experts'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p><?php echo htmlspecialchars($translate('search.intro', 'Diese Suche ist vom globalen 365CMS getrennt und durchsucht ausschließlich die vier Netzwerk-Bereiche.', 'This search is separate from global 365CMS search and only scans the four network areas.'), ENT_QUOTES, 'UTF-8'); ?></p>

        <form class="n365-search-form" role="search" method="GET" action="<?php echo htmlspecialchars($searchUrl, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($translate('search.form_aria', '365NETWORK durchsuchen', 'Search 365NETWORK'), ENT_QUOTES, 'UTF-8'); ?>">
            <label for="n365-search-query"><?php echo htmlspecialchars($translate('search.label', 'Suchbegriff', 'Search term'), ENT_QUOTES, 'UTF-8'); ?></label>
            <div class="n365-search-form__row">
                <input id="n365-search-query" name="<?php echo htmlspecialchars($searchParam, ENT_QUOTES, 'UTF-8'); ?>" type="search" value="<?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars($translate('search.placeholder', 'z. B. Azure, Copilot, Workshop', 'e.g. Azure, Copilot, workshop'), ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" aria-describedby="n365-search-keyboard-help">
                <button type="submit"><?php echo htmlspecialchars($translate('search.submit', 'Suchen', 'Search'), ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
        </form>
    </header>

    <?php if (!$hasQuery): ?>
    <section class="n365-search-empty" role="status" aria-live="polite">
        <p class="n365-search-empty__title"><?php echo htmlspecialchars($translate('search.empty.title', 'Suchbegriff eingeben', 'Enter a search term'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p><?php echo htmlspecialchars($translate('search.empty.text', 'Starte mit mindestens zwei Zeichen. Angezeigt werden nur Treffer aus Events, Speakern, Firmen und Experten.', 'Start with at least two characters. Results are limited to events, speakers, companies and experts.'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>
    <?php elseif ($queryTooShort): ?>
    <section class="n365-search-empty" role="status" aria-live="polite">
        <p class="n365-search-empty__title"><?php echo htmlspecialchars($translate('search.too_short.title', 'Suchbegriff zu kurz', 'Search term too short'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p><?php echo htmlspecialchars($translate('search.too_short.text', 'Bitte gib mindestens zwei Zeichen ein, damit die Netzwerk-Suche starten kann.', 'Please enter at least two characters to start searching the network.'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>
    <?php else: ?>
    <section class="n365-search-summary" aria-label="<?php echo htmlspecialchars($translate('search.summary.aria', 'Suchzusammenfassung', 'Search summary'), ENT_QUOTES, 'UTF-8'); ?>">
        <p><strong><?php echo (int) $searchTotal; ?></strong> <?php echo htmlspecialchars($translate('search.summary.for', 'Treffer für', 'results for'), ENT_QUOTES, 'UTF-8'); ?> <strong><?php echo htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8'); ?></strong></p>
    </section>

    <?php if ($searchTotal === 0): ?>
    <section class="n365-search-empty" role="status" aria-live="polite">
        <p class="n365-search-empty__title"><?php echo htmlspecialchars($translate('search.no_results.title', 'Keine Netzwerk-Treffer gefunden', 'No network results found'), ENT_QUOTES, 'UTF-8'); ?></p>
        <p><?php echo htmlspecialchars($translate('search.no_results.text', 'Versuche einen anderen Begriff oder suche allgemeiner, zum Beispiel nach Thema, Stadt, Firmenname oder Rolle.', 'Try another term or search more broadly, e.g. by topic, city, company name or role.'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>
    <?php else: ?>
    <section class="n365-search-results" aria-label="<?php echo htmlspecialchars($translate('search.results.aria', '365NETWORK Suchergebnisse', '365NETWORK search results'), ENT_QUOTES, 'UTF-8'); ?>" data-n365-search-results>
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

                <div class="n365-search-list" role="list">
                    <?php foreach ($items as $itemIndex => $item): ?>
                    <article class="n365-search-result">
                        <div class="n365-search-result__icon" aria-hidden="true">
                            <i class="ti <?php echo htmlspecialchars((string) ($item['icon'] ?? 'ti-link'), ENT_QUOTES, 'UTF-8'); ?>"></i>
                        </div>
                        <div class="n365-search-result__body">
                            <p class="n365-search-result__type"><?php echo htmlspecialchars((string) ($item['type_label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            <h3><a href="<?php echo htmlspecialchars((string) ($item['url'] ?? '#'), ENT_QUOTES, 'UTF-8'); ?>" data-n365-search-result-link data-result-index="<?php echo (int) $itemIndex; ?>"><?php echo htmlspecialchars((string) ($item['title'] ?? 'Treffer'), ENT_QUOTES, 'UTF-8'); ?></a></h3>
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
