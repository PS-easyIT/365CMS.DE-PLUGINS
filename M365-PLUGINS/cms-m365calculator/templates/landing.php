<?php
/**
 * Public Template: M365 Calculator Landingpage.
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

if (class_exists('CMS\\ThemeManager')) {
    \CMS\ThemeManager::instance()->getHeader(['title' => 'M365 Rechner']);
}
?>

<main class="phinit-plugin" id="m365calculator-landing">
    <header>
        <p class="phinit-overline">Rechner &amp; Tools</p>
        <h1>M365 Rechner</h1>
        <p class="phinit-prose">Diese Übersicht bündelt verfügbare Microsoft-365-Rechner des Plugins. Neue Module erscheinen automatisch, sobald sie in der Registry angemeldet sind.</p>
    </header>

    <?php if (empty($groupedTools)): ?>
    <section class="phinit-empty-state" role="status" aria-live="polite">
        <h2>Keine Rechner verfügbar</h2>
        <p>Aktuell sind noch keine öffentlichen Module registriert.</p>
    </section>
    <?php endif; ?>

    <?php foreach ($groupedTools as $category => $tools): ?>
    <?php $sectionId = $categoryId((string) $category); ?>
    <section aria-labelledby="<?php echo $esc($sectionId); ?>">
        <h2 id="<?php echo $esc($sectionId); ?>"><?php echo $esc($category); ?></h2>

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
            ?>
            <li>
                <article class="phinit-card phinit-card--accent<?php echo $status === 'soon' ? ' phinit-tool-card--disabled' : ''; ?>"<?php echo $status === 'soon' ? ' aria-disabled="true"' : ''; ?>>
                    <span class="phinit-tool-card__icon" aria-hidden="true">
                        <?php echo CMS_M365CALCULATOR_Icons::svg((string) ($tool['icon'] ?? 'calculator')); ?>
                    </span>
                    <section class="phinit-tool-card__body">
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
                        <p><?php echo $esc($tool['description'] ?? ''); ?></p>
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
