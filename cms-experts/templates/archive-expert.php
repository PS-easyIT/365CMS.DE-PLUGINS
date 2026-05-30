<?php
declare(strict_types=1);

/**
 * Expert Archive Template – Plugin-Content only for CMS-PHINIT.
 *
 * @package CMS_Experts
 */

if (!defined('ABSPATH')) {
    exit;
}

// Settings & Variables
$settings = array_merge([
    'archive_title'                => 'Experten Suche',
    'archive_description'          => '',
    'design_primary_color'         => '#5e72e4',
    'design_accent_color'          => '#8965e0',
    'design_border_radius'         => '12',
    'design_cta_color'             => '#c2410c',
    'design_card_bg'               => '#fffdf4',
    'design_show_skills'           => '1',
    'design_show_specialization'   => '1',
], $settings ?? []);

$experts = array_values(array_filter(array_map(
    static function ($item): ?object {
        if (is_object($item)) {
            return $item;
        }

        if (is_array($item)) {
            return (object) $item;
        }

        return null;
    },
    (array) ($experts ?? [])
)));

$city = $filters['city'] ?? '';
$availability = $filters['availability'] ?? '';
$expertsArchiveUrl = htmlspecialchars(rtrim((string) SITE_URL, '/') . '/experts', ENT_QUOTES, 'UTF-8');
$expertSearchQuery = htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$expertCityFilter = htmlspecialchars(sanitize_text_field((string) $city), ENT_QUOTES, 'UTF-8');
$currentPage = max(1, (int) ($current_page ?? 1));
$perPage = max(1, (int) ($per_page ?? 12));
$hasNextPage = count($experts) >= $perPage;
$activeParams = [];

if ($expertSearchQuery !== '') {
    $activeParams['q'] = (string) ($filters['q'] ?? '');
}

if ($expertCityFilter !== '') {
    $activeParams['city'] = (string) $city;
}

if ($availability !== '') {
    $activeParams['availability'] = (string) $availability;
}

$buildArchiveUrl = static function (int $page) use ($expertsArchiveUrl, $activeParams): string {
    $params = $activeParams;
    if ($page > 1) {
        $params['page'] = (string) $page;
    }

    return $expertsArchiveUrl . ($params !== [] ? '?' . http_build_query($params) : '');
};
?>
<main class="phinit-plugin experts-archive-wrapper">
    <nav class="expert-filter-nav" aria-label="Expertenfilter">
        <form method="GET" action="<?= $expertsArchiveUrl ?>" class="archive-filter-bar expert-card-filter" role="search">
            <div class="filter-input-wrapper phinit-field">
                <label for="expert-search">Experten suchen</label>
                <input id="expert-search" class="phinit-input" type="search" name="q" placeholder="Name, Firma, Skill..." value="<?= $expertSearchQuery ?>">
            </div>

            <div class="filter-input-wrapper filter-input-wrapper--sm phinit-field">
                <label for="expert-city">Stadt</label>
                <input id="expert-city" class="phinit-input" type="text" name="city" placeholder="Stadt..." value="<?= $expertCityFilter ?>">
            </div>

            <div class="phinit-field filter-input-wrapper filter-input-wrapper--sm">
                <label for="expert-availability">Verfügbarkeit</label>
                <select id="expert-availability" name="availability" class="filter-select phinit-select">
                    <option value="">Alle Verfügbarkeiten</option>
                    <option value="available"<?= $availability === 'available' ? ' selected' : '' ?>>Verfügbar</option>
                    <option value="limited"<?= $availability === 'limited' ? ' selected' : '' ?>>Begrenzt</option>
                    <option value="booked"<?= $availability === 'booked' ? ' selected' : '' ?>>Ausgebucht</option>
                </select>
            </div>

            <button type="submit" class="phinit-btn phinit-btn--primary expert-btn">Suchen</button>
            <?php if (!empty($city) || !empty($availability) || $expertSearchQuery !== ''): ?>
                <a href="<?= $expertsArchiveUrl ?>" class="phinit-btn phinit-btn--secondary expert-btn expert-btn-outline expert-btn--reset">Zurücksetzen</a>
            <?php endif; ?>
        </form>
    </nav>

    <?php if (!empty($experts)): ?>
        <section class="experts-grid expert-card-grid phinit-grid" aria-label="Expertenliste">
            <?php foreach ($experts as $expert): ?>
                <?php include __DIR__ . '/expert-card.php'; ?>
            <?php endforeach; ?>
        </section>
        
        <?php if ($currentPage > 1 || $hasNextPage): ?>
        <nav class="expert-pagination" aria-label="Seitennavigation">
            <?php if ($currentPage > 1): ?>
                <a class="expert-page" href="<?= htmlspecialchars($buildArchiveUrl($currentPage - 1), ENT_QUOTES, 'UTF-8') ?>" rel="prev">Zurück</a>
            <?php endif; ?>
            <span class="expert-page is-active" aria-current="page"><?= (int) $currentPage ?></span>
            <?php if ($hasNextPage): ?>
                <a class="expert-page" href="<?= htmlspecialchars($buildArchiveUrl($currentPage + 1), ENT_QUOTES, 'UTF-8') ?>" rel="next">Weiter</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-results phinit-empty-state" role="status" aria-live="polite">
            <i class="ti ti-user-off" aria-hidden="true"></i>
            <p class="no-results__title">Keine Experten gefunden.</p>
            <p>Bitte versuchen Sie andere Suchbegriffe oder setzen Sie den Filter zurück.</p>
        </div>
    <?php endif; ?>

</main>
