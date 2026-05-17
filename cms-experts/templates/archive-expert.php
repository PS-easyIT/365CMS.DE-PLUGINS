<?php
declare(strict_types=1);

/**
 * Archive Template: Experten-Übersicht (Refactored to match IT Expert Cards)
 * 
 * @package CMS_Experts
 */

if (!defined('ABSPATH')) { exit; }

// Settings & Variables
$settings = array_merge([
    'archive_title'                => 'Experten Suche',
    'archive_description'          => '',
    'archive_header_icon'          => '&#128100;',
    'archive_header_bg_from'       => '#f5ecd5',
    'archive_header_bg_to'         => '#ebe0c8',
    'archive_header_title_color'   => '#7c4700',
    'design_primary_color'         => '#5e72e4',
    'design_accent_color'          => '#8965e0',
    'design_border_radius'         => '12',
    'design_cta_color'             => '#c2410c',
    'design_card_bg'               => '#fffdf4',
    'design_show_skills'           => '1',
    'design_show_specialization'   => '1',
], $settings ?? []);

$city         = $filters['city'] ?? '';
$availability = $filters['availability'] ?? '';
$expertsArchiveUrl = htmlspecialchars(rtrim((string) SITE_URL, '/') . '/experts', ENT_QUOTES, 'UTF-8');
$expertSearchQuery = htmlspecialchars((string) ($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8');
$expertCityFilter = htmlspecialchars(sanitize_text_field((string) $city), ENT_QUOTES, 'UTF-8');
$css_hdr_icon_raw = htmlspecialchars(html_entity_decode($settings['archive_header_icon'] ?? '', ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8');
?>
<main class="phinit-plugin experts-archive-wrapper">
    
    <!-- Title Area - WP Plugin Style: Title-Band + Description-Band -->
    <?php if (!empty($settings['archive_title'])): ?>
    <header class="expert-archive-header phinit-card phinit-card--accent">
        <div class="header-title-band">
            <div class="header-icon"><?= $css_hdr_icon_raw ?></div>
            <h2 class="header-title"><?php echo CMS\Security::instance()->escape($settings['archive_title']); ?></h2>
        </div>
        <?php if (!empty($settings['archive_description'])): ?>
        <div class="header-description-area">
            <p class="header-description"><?php echo CMS\Security::instance()->escape($settings['archive_description']); ?></p>
        </div>
        <?php endif; ?>
    </header>
    <?php endif; ?>

    <!-- Filter Bar (Styled like IT Expert Cards) -->
    <form method="GET" action="<?php echo $expertsArchiveUrl; ?>" class="archive-filter-bar phinit-card" role="search">
        
        <!-- Search Input -->
        <div class="filter-input-wrapper phinit-field">
            <label for="expert-search">Experten suchen</label>
            <input id="expert-search" class="phinit-input" type="text" name="q" placeholder="Experten suchen..." value="<?php echo $expertSearchQuery; ?>">
        </div>

        <!-- City Filter -->
        <div class="filter-input-wrapper filter-input-wrapper--sm phinit-field">
            <label for="expert-city">Stadt</label>
            <input id="expert-city" class="phinit-input" type="text" name="city" placeholder="Stadt..." value="<?php echo $expertCityFilter; ?>">
        </div>

        <!-- Availability Filter -->
        <label class="phinit-field" for="expert-availability"><span>Verfügbarkeit</span>
        <select id="expert-availability" name="availability" class="filter-select phinit-select">
            <option value="">Alle Verfügbarkeiten</option>
            <option value="available" <?php echo $availability === 'available' ? 'selected' : ''; ?>>Verfügbar</option>
            <option value="limited" <?php echo $availability === 'limited' ? 'selected' : ''; ?>>Begrenzt</option>
        </select></label>

        <button type="submit" class="phinit-btn phinit-btn--primary expert-btn">Suchen</button>
        <?php if (!empty($city) || !empty($availability) || $expertSearchQuery !== ''): ?>
            <a href="<?php echo $expertsArchiveUrl; ?>" class="phinit-btn phinit-btn--secondary expert-btn expert-btn-outline expert-btn--reset">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Grid Layout -->
    <?php if (!empty($experts)): ?>
        <section class="experts-grid phinit-grid" aria-label="Expertenliste">
            <?php foreach ($experts as $expert): ?>
                <?php 
                // Render card using the new template
                // Ensure $expert object is compatible
                include __DIR__ . '/expert-card.php'; 
                ?>
            <?php endforeach; ?>
        </section>
        
        <!-- Pagination (Basic) -->
        <?php if (isset($current_page) && $current_page > 1): ?>
        <nav class="expert-pagination" aria-label="Seitennavigation">
            <!-- Placeholder for pagination logic -->
            <span class="page-numbers current">1</span>
        </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-results phinit-empty-state" role="status" aria-live="polite">
            <h3>Keine Experten gefunden</h3>
            <p>Bitte versuchen Sie andere Suchbegriffe.</p>
        </div>
    <?php endif; ?>

</main>
