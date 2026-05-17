<?php
/**
 * Public Template: M365 Tools Landingpage.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$categoryId = static function (string $category): string {
    $normalized = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $category), '-'));
    return $normalized !== '' ? 'cat-' . $normalized : 'cat-tools';
};
$safeUrl = static function (mixed $value): string {
    $url = trim((string) $value);
    if ($url === '') {
        return '';
    }

    if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
        return $url;
    }

    $parts = parse_url($url);
    $scheme = is_array($parts) ? strtolower((string) ($parts['scheme'] ?? '')) : '';

    return in_array($scheme, ['http', 'https'], true) && filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : '';
};
$statusLabel = static fn(string $status): string => match ($status) {
    'beta' => 'Beta',
    'soon' => 'bald',
    default => '',
};
$reviewLabels = static function (array $domainKeys, array $domains): array {
    $labels = [];
    foreach ($domainKeys as $domainKey) {
        $domainKey = (string) $domainKey;
        if ($domainKey === '' || !isset($domains[$domainKey]) || !is_array($domains[$domainKey])) {
            continue;
        }
        $label = trim((string) ($domains[$domainKey]['label'] ?? ''));
        if ($label !== '') {
            $labels[] = $label;
        }
    }

    return array_values(array_unique($labels));
};
$reviewChecks = static function (string $toolKey, array $checkMap): array {
    $checks = isset($checkMap[$toolKey]) && is_array($checkMap[$toolKey]) ? $checkMap[$toolKey] : [];

    return array_values(array_filter(array_map(static fn(mixed $value): string => trim((string) $value), $checks)));
};
$toolCount = 0;
$liveCount = 0;
$categoryCounts = [];
foreach (($groupedTools ?? []) as $category => $tools) {
    $tools = is_array($tools) ? $tools : [];
    $categoryCounts[(string) $category] = count($tools);
    $toolCount += count($tools);

    foreach ($tools as $tool) {
        if (is_array($tool) && ($tool['status'] ?? '') === 'live') {
            $liveCount++;
        }
    }
}
$reviewDomainCount = is_array($bestPracticeDomains ?? null) ? count($bestPracticeDomains) : 0;

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'M365 Tools']);
}
?>

<main class="phinit-plugin" id="m365tools-landing">
    <header class="m365tools-landing__header">
        <section class="m365tools-landing__intro" aria-labelledby="m365tools-title">
            <p class="phinit-overline">Rechner &amp; Tools</p>
            <h1 id="m365tools-title">M365 Tools</h1>
            <p class="phinit-prose">Eine kuratierte Sammlung für Microsoft-365-Lizenzierung, Kosten, Speicher, Backup, Copilot, Telefonie, Migration und Betrieb. Ruhig aufgebaut, schnell erfassbar und bewusst ohne Effekt-Show.</p>
        </section>
        <dl class="m365tools-landing__facts" aria-label="Übersicht Kennzahlen">
            <div>
                <dt>Module</dt>
                <dd><?php echo (int) $toolCount; ?></dd>
            </div>
            <div>
                <dt>Live</dt>
                <dd><?php echo (int) $liveCount; ?></dd>
            </div>
            <div>
                <dt>Review-Bereiche</dt>
                <dd><?php echo (int) $reviewDomainCount; ?></dd>
            </div>
        </dl>
    </header>

    <?php if (!empty($categoryCounts)): ?>
    <nav class="m365tools-category-nav" aria-label="Modulkategorien">
        <ol>
            <?php foreach ($categoryCounts as $category => $count): ?>
            <?php $sectionId = $categoryId((string) $category); ?>
            <li>
                <a href="#<?php echo $esc($sectionId); ?>">
                    <span><?php echo $esc($category); ?></span>
                    <small><?php echo (int) $count; ?></small>
                </a>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>

    <?php if (!empty($bestPracticeDomains) && is_array($bestPracticeDomains)): ?>
    <section class="phinit-card m365tools-review-panel" aria-labelledby="m365tools-review-title">
        <header class="m365tools-section-head">
            <section>
                <p class="phinit-overline">Querschnittsreview</p>
                <h2 id="m365tools-review-title"><?php echo $esc($bestPracticeMeta['title'] ?? 'Microsoft 365 Best-Practice-Kompass'); ?></h2>
                <?php if (!empty($bestPracticeMeta['summary'])): ?>
                <p class="phinit-prose"><?php echo $esc($bestPracticeMeta['summary']); ?></p>
                <?php endif; ?>
            </section>
        </header>
        <ul class="m365tools-review-domain-grid" role="list">
            <?php foreach ($bestPracticeDomains as $domain): ?>
            <?php if (!is_array($domain)): ?>
            <?php continue; ?>
            <?php endif; ?>
            <li class="m365tools-review-domain">
                <strong><?php echo $esc($domain['label'] ?? 'Review'); ?></strong>
                <span><?php echo $esc($domain['summary'] ?? ''); ?></span>
            </li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <?php if (empty($groupedTools)): ?>
    <section class="phinit-empty-state" role="status" aria-live="polite">
        <h2>Keine Rechner verfügbar</h2>
        <p>Aktuell sind noch keine öffentlichen Module registriert.</p>
    </section>
    <?php endif; ?>

    <?php foreach ($groupedTools as $category => $tools): ?>
    <?php $sectionId = $categoryId((string) $category); ?>
    <section aria-labelledby="<?php echo $esc($sectionId); ?>">
        <header class="m365tools-section-head">
            <section>
                <p class="phinit-overline">Kategorie</p>
                <h2 id="<?php echo $esc($sectionId); ?>"><?php echo $esc($category); ?></h2>
            </section>
            <span class="m365tools-section-count"><?php echo (int) ($categoryCounts[(string) $category] ?? count((array) $tools)); ?> Module</span>
        </header>

        <?php if (empty($tools)): ?>
        <section class="phinit-empty-state" role="status" aria-live="polite">
            <h3>Keine Module in dieser Kategorie</h3>
            <p>Für diese Kategorie sind aktuell keine Rechner registriert.</p>
        </section>
        <?php else: ?>
        <ul class="phinit-tool-grid" role="list">
            <?php foreach ($tools as $tool): ?>
            <?php
            $status = (string) ($tool['status'] ?? 'soon');
            $url = $safeUrl($tool['url'] ?? '');
            $isLinked = $url !== '' && in_array($status, ['live', 'beta'], true);
            $label = $statusLabel($status);
            $toolKey = (string) ($tool['key'] ?? '');
            $domainKeys = isset($toolReviewMap[$toolKey]) && is_array($toolReviewMap[$toolKey]) ? $toolReviewMap[$toolKey] : [];
            $toolReviewLabels = $reviewLabels($domainKeys, is_array($bestPracticeDomains ?? null) ? $bestPracticeDomains : []);
            $toolReviewChecks = $reviewChecks($toolKey, is_array($toolCheckMap ?? null) ? $toolCheckMap : []);
            ?>
            <li>
                <article class="phinit-card phinit-card--accent<?php echo $status === 'soon' ? ' phinit-tool-card--disabled' : ''; ?>"<?php echo $status === 'soon' ? ' aria-disabled="true"' : ''; ?>>
                    <header class="phinit-tool-card__head">
                        <span class="phinit-tool-card__icon" aria-hidden="true">
                            <?php echo CMS_M365CALCULATOR_Icons::svg((string) ($tool['icon'] ?? 'calculator')); ?>
                        </span>
                        <h3>
                            <?php if ($isLinked): ?>
                            <a href="<?php echo $esc($url); ?>"><?php echo $esc($tool['title'] ?? ''); ?></a>
                            <?php else: ?>
                            <span><?php echo $esc($tool['title'] ?? ''); ?></span>
                            <?php endif; ?>
                            <?php if ($label !== ''): ?>
                            <span class="phinit-status-label"><?php echo $esc($label); ?></span>
                            <?php endif; ?>
                        </h3>
                    </header>
                    <section class="phinit-tool-card__body">
                        <p><?php echo $esc($tool['description'] ?? ''); ?></p>
                        <?php if (!empty($toolReviewLabels)): ?>
                        <ul class="m365tools-review-chip-list" role="list" aria-label="Review-Schwerpunkte">
                            <?php foreach (array_slice($toolReviewLabels, 0, 4) as $reviewLabel): ?>
                            <li><?php echo $esc($reviewLabel); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        <?php if (!empty($toolReviewChecks)): ?>
                        <ul class="m365tools-review-check-list" role="list" aria-label="Aktuelle Prüfpunkte">
                            <?php foreach (array_slice($toolReviewChecks, 0, 2) as $reviewCheck): ?>
                            <li><?php echo $esc($reviewCheck); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                        <?php if ($isLinked): ?>
                        <a href="<?php echo $esc($url); ?>" class="phinit-btn phinit-btn--link">
                            Öffnen <span class="phinit-arrow" aria-hidden="true">→</span>
                        </a>
                        <?php else: ?>
                        <span class="phinit-tool-card__disabled-note" aria-disabled="true">Nicht verfügbar</span>
                        <?php endif; ?>
                    </section>
                </article>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </section>
    <?php endforeach; ?>
</main>

<?php
if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getFooter();
}
