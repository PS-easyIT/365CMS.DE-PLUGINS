<?php
/**
 * Expert Card Component - Design aligned with IT Expert Cards (WP Plugin)
 * Layout: Badges top, avatar left + text right, expertise banner, skills grid, social icons, CTA
 *
 * @package CMS_Experts
 * @var object $expert
 */

if (!defined('ABSPATH')) { exit; }

$sec = CMS\Security::instance();

// Data Extraction
// Slug entweder aus DB-Feld oder dynamisch generieren (Format: vorname-nachname-{id})
$slug = !empty($expert->slug)
    ? $expert->slug
    : CMS_Experts_Database::generate_slug($expert);
$url  = SITE_URL . '/experts/' . $slug;
$full_name        = trim(($expert->first_name ?? '') . ' ' . ($expert->last_name ?? ''));
$job_title        = $expert->position ?? '';
$company_name     = $expert->company_name ?? ($expert->company ?? ''); // company_name oder company-Feld
$photo            = $expert->photo_url ?? '';
$city             = $expert->location_city ?? '';
$availability     = $expert->availability ?? 'available';
$experience_years = $expert->experience_years ?? null;
$linkedin         = $expert->linkedin ?? '';
$website          = $expert->website  ?? '';
$email            = $expert->email ?? '';
$specialization  = '';
$show_specs      = ($settings['design_show_specialization'] ?? '1') === '1';
$show_skills     = ($settings['design_show_skills'] ?? '1') === '1';

// Spezialisierungen - bulk-geladen via _specializations (Array von Namen)
if ($show_specs && !empty($expert->_specializations)) {
    $specialization = implode(' · ', array_slice($expert->_specializations, 0, 2));
}

// Skills - bulk-geladen via _skills (Array von skill_name Strings)
$skills = [];
if ($show_skills) {
    if (!empty($expert->_skills) && is_array($expert->_skills)) {
        $skills = $expert->_skills;
    } elseif (!empty($expert->skills)) {
        // Fallback: Komma-separierter String im skills-Feld
        $skills = array_filter(array_map('trim', explode(',', (string)$expert->skills)));
    }
}

// Badges
$is_partner   = !empty($expert->is_partner);
$is_certified = !empty($expert->is_certified);

// Zertifikat-Anzahl (bulk-geladen)
$cert_count = $expert->_cert_count ?? 0;
$avail_labels = [
    'available' => 'Verf&#252;gbar',
    'limited'   => 'Begrenzt',
    'booked'    => 'Ausgebucht',
];
$avail_label = $avail_labels[$availability] ?? ucfirst($availability);

?>

<article class="expert-card expert-card--overview">

    <!-- Status Badges Links Oben -->
    <div class="expert-card-status-badges">
        <?php if ($is_partner): ?>
            <span class="expert-card-corner-badge badge-partner"><span>Partner</span></span>
        <?php endif; ?>
        <?php if ($is_certified): ?>
            <span class="expert-card-corner-badge badge-certified"><span>Zertifiziert</span></span>
        <?php endif; ?>
    </div>

    <!-- Verf&#252;gbarkeits-Badge Rechts Oben -->
    <span class="expert-card-corner-badge expert-card-availability-badge expert-card-availability--<?php echo $sec->escape($availability); ?>">
        <span><?php echo $avail_label; ?></span>
    </span>

    <!-- TOP: Avatar links, Text rechts -->
    <header class="expert-card-top">
        <div class="expert-card-avatar">
            <?php if ($photo): ?>
                <img src="<?php echo $sec->escape($photo); ?>" alt="<?php echo $sec->escape($full_name); ?>">
            <?php else: ?>
                <div class="expert-avatar-placeholder">
                    <span><?php echo strtoupper(substr($expert->first_name ?? 'E', 0, 1)); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="expert-card-header-text">
            <h3 class="expert-card-name">
                <a href="<?php echo $url; ?>"><?php echo $sec->escape($full_name); ?></a>
            </h3>

            <div class="expert-card-title-row">
                <?php if ($job_title): ?>
                    <p class="expert-card-title"><?php echo $sec->escape($job_title); ?></p>
                <?php endif; ?>
                <div class="expert-card-badges-row">
                    <?php if ($experience_years): ?>
                        <div class="expert-badge-mini" title="<?php echo (int)$experience_years; ?> Jahre Erfahrung">
                            <span class="badge-icon">&#128188;</span>
                            <span class="badge-val"><?php echo (int)$experience_years; ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="expert-badge-mini" title="<?php echo $cert_count; ?> Zertifikat<?php echo $cert_count !== 1 ? 'e' : ''; ?>">
                        <span class="badge-icon">&#127941;</span>
                        <span class="badge-val"><?php echo $cert_count; ?></span>
                    </div>
                </div>
            </div>

            <?php if ($company_name): ?>
                <div class="expert-card-company-row">
                    <span class="expert-card-company"><?php echo $sec->escape($company_name); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($city): ?>
                <div class="expert-card-location-row">
                    <span class="expert-card-location">
                        <span class="loc-icon">&#128205;</span>
                        <span><?php echo $sec->escape($city); ?></span>
                    </span>
                </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Spezialisierung / Fachrichtung Banner -->
    <?php if ($specialization): ?>
        <div class="expert-card-expertise-section">
            <span class="expertise-label">FACHRICHTUNG:</span>
            <span class="expertise-value"><?php echo $sec->escape($specialization); ?></span>
        </div>
    <?php endif; ?>

    <!-- Body: Skills + Social Icons -->
    <div class="expert-card-body">
        <div class="expert-card-competencies">
            <?php if (!empty($skills)): ?>
                <div class="expert-card-skills">
                    <?php foreach (array_slice($skills, 0, 8) as $skill): ?>
                        <span class="skill-pill"><?php echo $sec->escape(is_array($skill) ? ($skill['name'] ?? '') : $skill); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="expert-card-social">
            <a href="<?php echo $linkedin ? $sec->escape($linkedin) : '#'; ?>"
               <?php echo $linkedin ? 'target="_blank" rel="noopener"' : 'class="social-disabled"'; ?>
               title="LinkedIn">
                <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>
            </a>
            <a href="<?php echo $website ? $sec->escape($website) : '#'; ?>"
               <?php echo $website ? 'target="_blank" rel="noopener"' : 'class="social-disabled"'; ?>
               title="Website">
                <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M16.36 14c.08-.66.14-1.32.14-2 0-.68-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2m-5.15 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 0 1-4.33 3.56M14.34 14H9.66c-.1-.66-.16-1.32-.16-2 0-.68.06-1.35.16-2h4.68c.09.65.16 1.32.16 2 0 .68-.07 1.34-.16 2M12 19.96c-.83-1.2-1.5-2.53-1.91-3.96h3.82c-.41 1.43-1.08 2.76-1.91 3.96M8 8H5.08A7.923 7.923 0 0 1 9.4 4.44C8.8 5.55 8.35 6.75 8 8m-2.92 8H8c.35 1.25.8 2.45 1.4 3.56A8.008 8.008 0 0 1 5.08 16m-.82-2C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2M12 4.03c.83 1.2 1.5 2.54 1.91 3.97h-3.82c.41-1.43 1.08-2.77 1.91-3.97M18.92 8h-2.95a15.65 15.65 0 0 0-1.38-3.56c1.84.63 3.37 1.9 4.33 3.56M12 2C6.47 2 2 6.5 2 12a10 10 0 0 0 10 10A10 10 0 0 0 22 12 10 10 0 0 0 12 2z"/></svg>
            </a>
            <a href="<?php echo $email ? 'mailto:' . $sec->escape($email) : '#'; ?>"
               <?php echo !$email ? 'class="social-disabled"' : ''; ?>
               title="E-Mail">
                <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </a>
        </div>
    </div>

    <!-- CTA Button -->
    <a href="<?php echo $url; ?>" class="expert-card-cta">
        Profil ansehen <span class="cta-arrow">&#8250;</span>
    </a>

</article>
