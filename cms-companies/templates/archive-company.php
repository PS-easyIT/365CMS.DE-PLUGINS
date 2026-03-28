<?php
/**
 * Template: Archive Company (Grid View)
 *
 * @package CMS_Companies
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$companies    = $companies    ?? [];
$total_count  = $total_count  ?? count($companies);
$current_page = $current_page ?? 1;
$per_page     = $per_page     ?? 12;
$filters      = $filters      ?? ['industry' => null, 'city' => null, 'partner' => null];

// Design-Einstellungen aus DB laden
$settings = CMS_Companies_Database::instance()->get_settings();
$s = array_merge([
    'archive_title'              => '',
    'archive_description'        => '',
    'archive_header_icon'        => '🏢',
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

// Grid-Template-Columns aus Setting
$grid_cols = match($s['design_grid_columns']) {
    '2'     => 'repeat(2, 1fr)',
    '3'     => 'repeat(3, 1fr)',
    '4'     => 'repeat(4, 1fr)',
    default => 'repeat(auto-fill, minmax(300px, 1fr))',
};

// Branchen für Filtermenü
$all_industries = CMS_Companies_Database::instance()->get_all_industries();
?>

<div class="co-archive">

<?php /* ── CSS Custom Properties aus DB-Settings injizieren ── */ ?>
<style>
:root {
  --co-primary:    <?= htmlspecialchars($s['design_primary_color']) ?>;
  --co-primary-d:  <?= htmlspecialchars($s['design_cta_color']) ?>;
  --co-accent:     <?= htmlspecialchars($s['design_accent_color']) ?>;
  --co-radius:     <?= (int)$s['design_border_radius'] ?>px;
  --co-card-bg:    <?= htmlspecialchars($s['design_card_bg']) ?>;
  --co-hdr-from:   <?= htmlspecialchars($s['archive_header_bg_from']) ?>;
  --co-hdr-to:     <?= htmlspecialchars($s['archive_header_bg_to']) ?>;
  --co-hdr-title:  <?= htmlspecialchars($s['archive_header_title_color']) ?>;
}
.co-grid { grid-template-columns: <?= $grid_cols ?>; }
</style>

    <?php
    $has_title = !empty(trim((string)$s['archive_title']));
    $has_desc  = !empty(trim((string)$s['archive_description']));
    if ($has_title || $has_desc):
    ?>
    <!-- Gradient Header -->
    <div class="co-archive-header">
        <div class="co-archive-header-inner">
            <div class="co-archive-header-icon"><?= htmlspecialchars($s['archive_header_icon']) ?></div>
            <div>
                <?php if ($has_title): ?>
                <h1 class="co-archive-header-title"><?= htmlspecialchars($s['archive_title']) ?></h1>
                <?php endif; ?>
                <?php if ($has_desc): ?>
                <p class="co-archive-subtitle">
                    <?= htmlspecialchars($s['archive_description']) ?>
                </p>
                <?php endif; ?>
            </div>
            <div class="co-archive-count">
                <span class="co-archive-count-num"><?= $total_count ?></span>
                <span class="co-archive-count-lbl">Einträge</span>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <form method="GET" action="<?= SITE_URL ?>/companies" class="co-filter-bar">

        <!-- Freitextsuche -->
        <div class="co-filter-input">
            <span class="co-filter-icon">🔍</span>
            <input type="text" name="q" placeholder="Unternehmen suchen…"
                   value="<?= CMS\Security::instance()->escape($_GET['q'] ?? '') ?>">
        </div>

        <!-- Stadt -->
        <div class="co-filter-input co-filter-input--sm">
            <span class="co-filter-icon">📍</span>
            <input type="text" name="city" placeholder="Stadt…"
                   value="<?= CMS\Security::instance()->escape($filters['city'] ?? '') ?>">
        </div>

        <!-- Branche -->
        <select name="industry" class="co-filter-select">
            <option value="">🏭 Alle Branchen</option>
            <?php foreach ($all_industries as $ind): ?>
                <option value="<?= htmlspecialchars($ind->slug) ?>"
                        <?= ($filters['industry'] ?? '') === $ind->slug ? 'selected' : '' ?>>
                    <?= CMS\Security::instance()->escape($ind->name) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Partnerstatus -->
        <select name="partner" class="co-filter-select">
            <option value="">🤝 Alle Partner</option>
            <option value="sponsor"     <?= ($filters['partner'] ?? '') === 'sponsor'     ? 'selected' : '' ?>>💜 Sponsor</option>
            <option value="top_partner" <?= ($filters['partner'] ?? '') === 'top_partner' ? 'selected' : '' ?>>🥇 Top-Partner</option>
            <option value="partner"     <?= ($filters['partner'] ?? '') === 'partner'     ? 'selected' : '' ?>>🤝 Partner</option>
        </select>

        <button type="submit" class="co-btn co-btn-primary">Suchen</button>
        <?php if (!empty($filters['industry']) || !empty($filters['city']) || !empty($filters['partner']) || !empty($_GET['q'])): ?>
            <a href="<?= htmlspecialchars(rtrim(SITE_URL, '/') . '/companies') ?>" class="co-btn co-btn-ghost">× Reset</a>
        <?php endif; ?>

    </form>

    <!-- Results -->
    <?php if (empty($companies)): ?>
        <div class="co-empty-state">
            <div class="co-empty-icon">🏢</div>
            <h3>Keine Unternehmen gefunden</h3>
            <p>Bitte passen Sie Ihre Filterkriterien an.</p>
            <a href="<?= htmlspecialchars(rtrim(SITE_URL, '/') . '/companies') ?>" class="co-btn co-btn-primary">Alle anzeigen</a>
        </div>
    <?php else: ?>
        <div class="co-grid">
            <?php
            $tpl = CMS_Companies_Template_Loader::instance();
            foreach ($companies as $company):
                $tpl->render_template('company-card', ['company' => $company, 's' => $s]);
            endforeach;
            ?>
        </div>

        <!-- Pagination -->
        <?php if ($current_page > 1 || count($companies) >= $per_page): ?>
        <?php $companyPaginationBase = '?industry=' . urlencode($filters['industry'] ?? '') . '&city=' . urlencode($filters['city'] ?? ''); ?>
        <div class="co-pagination">
            <?php if ($current_page > 1): ?>
                <a href="<?= htmlspecialchars($companyPaginationBase . '&page=' . ($current_page - 1)) ?>" class="co-page-btn">&larr; Zurück</a>
            <?php endif; ?>
            <span class="co-page-info">Seite <?= $current_page ?></span>
            <?php if (count($companies) >= $per_page): ?>
                <a href="<?= htmlspecialchars($companyPaginationBase . '&page=' . ($current_page + 1)) ?>" class="co-page-btn">Weiter &rarr;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>

</div>
