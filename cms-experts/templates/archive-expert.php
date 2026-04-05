<?php
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
$expertSearchQuery = htmlspecialchars(sanitize_text_field((string) ($_GET['q'] ?? '')), ENT_QUOTES, 'UTF-8');
$expertCityFilter = htmlspecialchars(sanitize_text_field((string) $city), ENT_QUOTES, 'UTF-8');

// CSS-Variablen aus Settings
$css_primary      = htmlspecialchars($settings['design_primary_color']       ?? '#5e72e4');
$css_accent       = htmlspecialchars($settings['design_accent_color']         ?? '#8965e0');
$css_radius       = (int)($settings['design_border_radius']                   ?? 12);
$css_cta          = htmlspecialchars($settings['design_cta_color']            ?? '#c2410c');
$css_card_bg      = htmlspecialchars($settings['design_card_bg']              ?? '#fffdf4');
$css_hdr_from     = htmlspecialchars($settings['archive_header_bg_from']      ?? '#f5ecd5');
$css_hdr_to       = htmlspecialchars($settings['archive_header_bg_to']        ?? '#ebe0c8');
$css_hdr_title    = htmlspecialchars($settings['archive_header_title_color']  ?? '#7c4700');
$css_hdr_icon_raw = html_entity_decode($settings['archive_header_icon']       ?? '&#128100;', ENT_HTML5, 'UTF-8');
?>
<style>
:root {
    --expert-primary:    <?= $css_primary ?>;
    --expert-accent:     <?= $css_accent ?>;
    --expert-radius:     <?= $css_radius ?>px;
    --expert-cta-color:  <?= $css_cta ?>;
    --expert-card-bg:    <?= $css_card_bg ?>;
    --expert-hdr-bg:     linear-gradient(135deg, <?= $css_hdr_from ?> 0%, <?= $css_hdr_to ?> 100%);
    --expert-hdr-title:  <?= $css_hdr_title ?>;
}
</style>

<div class="experts-archive-wrapper">
    
    <!-- Title Area - WP Plugin Style: Title-Band + Description-Band -->
    <?php if (!empty($settings['archive_title'])): ?>
    <div class="expert-archive-header">
        <div class="header-title-band">
            <div class="header-icon"><?= $css_hdr_icon_raw ?></div>
            <h1 class="header-title"><?php echo CMS\Security::instance()->escape($settings['archive_title']); ?></h1>
        </div>
        <?php if (!empty($settings['archive_description'])): ?>
        <div class="header-description-area">
            <p class="header-description"><?php echo CMS\Security::instance()->escape($settings['archive_description']); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Filter Bar (Styled like IT Expert Cards) -->
    <form method="GET" action="<?php echo $expertsArchiveUrl; ?>" class="archive-filter-bar">
        
        <!-- Search Input -->
        <div class="filter-input-wrapper">
            <span class="icon">🔍</span>
            <input type="text" name="q" placeholder="Experten suchen..." value="<?php echo $expertSearchQuery; ?>">
        </div>

        <!-- City Filter -->
        <div class="filter-input-wrapper filter-input-wrapper--sm">
            <span class="icon">📍</span>
            <input type="text" name="city" placeholder="Stadt..." value="<?php echo $expertCityFilter; ?>">
        </div>

        <!-- Availability Filter -->
        <select name="availability" class="filter-select">
            <option value="">Alle Verfügbarkeiten</option>
            <option value="available" <?php echo $availability === 'available' ? 'selected' : ''; ?>>Verfügbar</option>
            <option value="limited" <?php echo $availability === 'limited' ? 'selected' : ''; ?>>Begrenzt</option>
        </select>

        <button type="submit" class="expert-btn">Suchen</button>
        <?php if (!empty($city) || !empty($availability)): ?>
            <a href="<?php echo $expertsArchiveUrl; ?>" class="expert-btn expert-btn-outline expert-btn--reset">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Grid Layout -->
    <?php if (!empty($experts)): ?>
        <div class="experts-grid">
            <?php foreach ($experts as $expert): ?>
                <?php 
                // Render card using the new template
                // Ensure $expert object is compatible
                include __DIR__ . '/expert-card.php'; 
                ?>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination (Basic) -->
        <?php if (isset($current_page) && $current_page > 1): ?>
        <div class="expert-pagination">
            <!-- Placeholder for pagination logic -->
            <span class="page-numbers current">1</span>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="no-results">
            <h3>Keine Experten gefunden</h3>
            <p>Bitte versuchen Sie andere Suchbegriffe.</p>
        </div>
    <?php endif; ?>

</div>
