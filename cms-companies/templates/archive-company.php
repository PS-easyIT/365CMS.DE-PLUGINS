<?php
/**
 * Template: Archive Company (Grid View)
 *
 * @package CMS_Companies
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$companies    = array_values(array_filter(array_map(
    static function ($item): ?object {
        if (is_object($item)) {
            return $item;
        }

        if (is_array($item)) {
            return (object) $item;
        }

        return null;
    },
    (array) ($companies ?? [])
)));
$total_count  = $total_count  ?? count($companies);
$current_page = $current_page ?? 1;
$per_page     = $per_page     ?? 12;
$filters      = $filters      ?? ['industry' => null, 'city' => null, 'partner' => null];

// Design-Einstellungen aus DB laden
$settings = CMS_Companies_Database::instance()->get_settings();
$s = array_merge([
    'archive_title'              => '',
    'archive_description'        => '',
    'archive_header_icon'        => '',
    'archive_header_bg_from'     => '#e0f2fe',
    'archive_header_bg_to'       => '#bae6fd',
    'archive_header_title_color' => '#0c4a6e',
    'design_primary_color'       => '#0891b2',
    'design_accent_color'        => '#e0f2fe',
    'design_cta_color'           => '#0891b2',
    'design_card_bg'             => '#ffffff',
    'design_border_radius'       => '12',
    'design_card_style'          => 'default',
    'design_grid_columns'        => 'auto',
    'design_show_industry'       => '1',
    'design_show_city'           => '1',
    'design_show_employees'      => '1',
    'design_show_website'        => '1',
    'design_partner_color'       => '#9ca3af',
    'design_top_partner_color'   => '#d97706',
    'design_sponsor_color'       => '#7c3aed',
], $settings);

// Branchen für Filtermenü
$all_industries = CMS_Companies_Database::instance()->get_all_industries();
$companiesArchiveUrl = htmlspecialchars(rtrim((string) SITE_URL, '/') . '/companies', ENT_QUOTES, 'UTF-8');
$searchQuery = htmlspecialchars(sanitize_text_field((string) ($_GET['q'] ?? '')), ENT_QUOTES, 'UTF-8');
$cityFilter = htmlspecialchars(sanitize_text_field((string) ($filters['city'] ?? '')), ENT_QUOTES, 'UTF-8');
$coCssColor = static function (mixed $value, string $fallback): string {
    $color = trim((string) $value);
    return preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $color) === 1 ? $color : $fallback;
};
$coPrimary = $coCssColor($s['design_primary_color'] ?? null, '#0891b2');
$coAccent = $coCssColor($s['design_accent_color'] ?? null, '#e0f2fe');
$coCta = $coCssColor($s['design_cta_color'] ?? null, $coPrimary);
$coCardBg = $coCssColor($s['design_card_bg'] ?? null, '#ffffff');
$coHeaderFrom = $coCssColor($s['archive_header_bg_from'] ?? null, '#e0f2fe');
$coHeaderTo = $coCssColor($s['archive_header_bg_to'] ?? null, '#bae6fd');
$coHeaderTitle = $coCssColor($s['archive_header_title_color'] ?? null, '#0c4a6e');
$coRadius = max(0, min(32, (int) ($s['design_border_radius'] ?? 12)));
?>

<style>
:root {
    --co-primary: <?= htmlspecialchars($coPrimary, ENT_QUOTES, 'UTF-8') ?>;
    --co-primary-d: <?= htmlspecialchars($coCta, ENT_QUOTES, 'UTF-8') ?>;
    --co-primary-x: <?= htmlspecialchars($coCta, ENT_QUOTES, 'UTF-8') ?>;
    --co-accent: <?= htmlspecialchars($coAccent, ENT_QUOTES, 'UTF-8') ?>;
    --co-card-bg: <?= htmlspecialchars($coCardBg, ENT_QUOTES, 'UTF-8') ?>;
    --co-hdr-from: <?= htmlspecialchars($coHeaderFrom, ENT_QUOTES, 'UTF-8') ?>;
    --co-hdr-to: <?= htmlspecialchars($coHeaderTo, ENT_QUOTES, 'UTF-8') ?>;
    --co-hdr-title: <?= htmlspecialchars($coHeaderTitle, ENT_QUOTES, 'UTF-8') ?>;
    --co-border: <?= htmlspecialchars($coAccent, ENT_QUOTES, 'UTF-8') ?>;
    --co-radius: <?= (int) $coRadius ?>px;
}
</style>
<main class="phinit-plugin co-archive">
    <!-- Filter Bar -->
    <nav class="co-filter-nav" aria-label="Unternehmensfilter">
        <form method="GET" action="<?= $companiesArchiveUrl ?>" class="co-filter-bar co-company-filter" role="search">

            <!-- Freitextsuche -->
            <div class="co-filter-input co-filter__field co-filter__field--search phinit-field">
                <label for="co-search">Unternehmen suchen</label>
                <input id="co-search" class="phinit-input" type="text" name="q" placeholder="Unternehmen suchen…"
                       value="<?= $searchQuery ?>">
            </div>

            <!-- Stadt -->
            <div class="co-filter-input co-filter__field co-filter__field--city phinit-field">
                <label for="co-city">Stadt</label>
                <input id="co-city" class="phinit-input" type="text" name="city" placeholder="Stadt…"
                       value="<?= $cityFilter ?>">
            </div>

            <!-- Branche -->
            <div class="co-filter__field phinit-field">
                <label for="co-industry">Branche</label>
                <select id="co-industry" name="industry" class="co-filter-select phinit-select">
                    <option value="">Alle Branchen</option>
                    <?php foreach ($all_industries as $ind): ?>
                        <option value="<?= htmlspecialchars((string) $ind->slug, ENT_QUOTES, 'UTF-8') ?>"
                                <?= ($filters['industry'] ?? '') === $ind->slug ? 'selected' : '' ?>>
                            <?= CMS\Security::instance()->escape($ind->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Partnerstatus -->
            <div class="co-filter__field phinit-field">
                <label for="co-partner">Partnerstatus</label>
                <select id="co-partner" name="partner" class="co-filter-select phinit-select">
                    <option value="">Alle Partner</option>
                    <option value="sponsor"     <?= ($filters['partner'] ?? '') === 'sponsor'     ? 'selected' : '' ?>>Sponsor</option>
                    <option value="top_partner" <?= ($filters['partner'] ?? '') === 'top_partner' ? 'selected' : '' ?>>Top-Partner</option>
                    <option value="partner"     <?= ($filters['partner'] ?? '') === 'partner'     ? 'selected' : '' ?>>Partner</option>
                </select>
            </div>

            <div class="co-filter__actions">
                <button type="submit" class="phinit-btn phinit-btn--primary co-btn co-btn-primary">Suchen</button>
                <?php if (!empty($filters['industry']) || !empty($filters['city']) || !empty($filters['partner']) || !empty($_GET['q'])): ?>
                    <a href="<?= $companiesArchiveUrl ?>" class="phinit-btn phinit-btn--secondary co-btn co-btn-ghost co-filter-reset">Filter zurücksetzen</a>
                <?php endif; ?>
            </div>

        </form>
    </nav>

    <!-- Results -->
    <?php if (empty($companies)): ?>
        <div class="co-empty-state phinit-empty-state" role="status" aria-live="polite">
            <h3>Keine Unternehmen gefunden</h3>
            <p>Bitte passen Sie Ihre Filterkriterien an.</p>
            <a href="<?= $companiesArchiveUrl ?>" class="phinit-btn phinit-btn--primary co-btn co-btn-primary">Alle anzeigen</a>
        </div>
    <?php else: ?>
        <section class="co-grid company-card-grid phinit-grid" aria-label="Unternehmensliste">
            <?php
            $tpl = CMS_Companies_Template_Loader::instance();
            foreach ($companies as $company):
                $tpl->render_template('company-card', ['company' => $company, 's' => $s]);
            endforeach;
            ?>
        </section>

        <!-- Pagination -->
        <?php if ($current_page > 1 || count($companies) >= $per_page): ?>
        <?php $companyPaginationBase = '?industry=' . rawurlencode((string) ($filters['industry'] ?? '')) . '&city=' . rawurlencode((string) ($filters['city'] ?? '')) . '&partner=' . rawurlencode((string) ($filters['partner'] ?? '')) . '&q=' . rawurlencode((string) ($_GET['q'] ?? '')); ?>
        <nav class="co-pagination" aria-label="Seitennavigation">
            <?php if ($current_page > 1): ?>
                <a href="<?= htmlspecialchars($companyPaginationBase . '&page=' . ($current_page - 1), ENT_QUOTES, 'UTF-8') ?>" class="co-page-btn phinit-btn phinit-btn--secondary">&larr; Zurück</a>
            <?php endif; ?>
            <span class="co-page-info" aria-current="page">Seite <?= (int) $current_page ?></span>
            <?php if (count($companies) >= $per_page): ?>
                <a href="<?= htmlspecialchars($companyPaginationBase . '&page=' . ($current_page + 1), ENT_QUOTES, 'UTF-8') ?>" class="co-page-btn phinit-btn phinit-btn--secondary">Weiter &rarr;</a>
            <?php endif; ?>
        </nav>
        <?php endif; ?>
    <?php endif; ?>

</main>
