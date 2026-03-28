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
$company_id       = (int)($expert->_social['company_id'] ?? 0);
$company_url      = '';
if ($company_id > 0 && function_exists('cms_company_url')) {
    $company_url = SITE_URL . '/company/' . strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', str_replace(['ä','ö','ü','ß','Ä','Ö','Ü'], ['ae','oe','ue','ss','ae','oe','ue'], $company_name)), '-')) . '-' . $company_id;
}
$photo            = $expert->photo_url ?? '';
$city             = $expert->location_city ?? '';
$availability     = $expert->availability ?? 'available';
$experience_years = $expert->experience_years ?? null;
$linkedin         = $expert->_social['social_linkedin'] ?? '';
$website          = $expert->_social['social_website']  ?? '';
$xing             = $expert->_social['social_xing']     ?? '';
$twitter          = $expert->_social['social_twitter']   ?? '';
$github           = $expert->_social['social_github']    ?? '';
$gitlab           = $expert->_social['social_gitlab']    ?? '';
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
$is_mvp       = !empty($expert->_social['is_mvp']);
$is_premium   = !empty($expert->_social['is_premium']);
$custom_award = trim($expert->_social['custom_award'] ?? '');

// Priorität: MVP > Premium > Custom Award (immer nur einer)
$highlight_badge = '';
$highlight_class = '';
if ($is_mvp) {
    $highlight_badge = '⭐ MVP';
    $highlight_class = 'badge-mvp';
} elseif ($is_premium) {
    $highlight_badge = '💎 Premium';
    $highlight_class = 'badge-premium';
} elseif ($custom_award !== '') {
    $highlight_badge = '🎖️ ' . $custom_award;
    $highlight_class = 'badge-award';
}

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

    <!-- Auszeichnungs-Badge Links Oben (nur einer, nach Priorität) -->
    <?php if ($highlight_badge): ?>
        <span class="expert-card-corner-badge expert-card-highlight-badge <?php echo $highlight_class; ?>">
            <span><?php echo $sec->escape($highlight_badge); ?></span>
        </span>
    <?php endif; ?>

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
            <div class="expert-card-name-row">
                <h3 class="expert-card-name">
                    <a href="<?php echo $sec->escape($url); ?>"><?php echo $sec->escape($full_name); ?></a>
                </h3>
                <?php if ($experience_years): ?>
                    <div class="expert-badge-mini" title="<?php echo (int)$experience_years; ?> Jahre Erfahrung">
                        <span class="badge-icon">&#128188;</span>
                        <span class="badge-val"><?php echo (int)$experience_years; ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="expert-card-title-row">
                <?php if ($job_title): ?>
                    <p class="expert-card-title"><?php echo $sec->escape($job_title); ?></p>
                <?php endif; ?>
                <div class="expert-badge-mini" title="<?php echo $cert_count; ?> Zertifikat<?php echo $cert_count !== 1 ? 'e' : ''; ?>">
                    <span class="badge-icon">&#127941;</span>
                    <span class="badge-val"><?php echo $cert_count; ?></span>
                </div>
            </div>

            <?php if ($company_name): ?>
                <div class="expert-card-company-row">
                    <?php if ($company_url): ?>
                        <a href="<?php echo $sec->escape($company_url); ?>" class="expert-card-company expert-card-company--linked"><?php echo $sec->escape($company_name); ?></a>
                    <?php else: ?>
                        <span class="expert-card-company"><?php echo $sec->escape($company_name); ?></span>
                    <?php endif; ?>
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

    <!-- Body: Skills -->
    <div class="expert-card-body">
        <div class="expert-card-competencies">
            <?php if (!empty($skills)): ?>
                <div class="expert-card-skills">
                    <?php foreach (array_slice($skills, 0, 4) as $skill): ?>
                        <span class="skill-pill"><?php echo $sec->escape(is_array($skill) ? ($skill['name'] ?? '') : $skill); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Social Band -->
    <div class="expert-card-social">
        <?php
        $social_items = [
                ['url' => $website,  'title' => 'Website',     'svg' => '<svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M16.36 14c.08-.66.14-1.32.14-2 0-.68-.06-1.34-.14-2h3.38c.16.64.26 1.31.26 2s-.1 1.36-.26 2m-5.15 5.56c.6-1.11 1.06-2.31 1.38-3.56h2.95a8.03 8.03 0 0 1-4.33 3.56M14.34 14H9.66c-.1-.66-.16-1.32-.16-2 0-.68.06-1.35.16-2h4.68c.09.65.16 1.32.16 2 0 .68-.07 1.34-.16 2M12 19.96c-.83-1.2-1.5-2.53-1.91-3.96h3.82c-.41 1.43-1.08 2.76-1.91 3.96M8 8H5.08A7.923 7.923 0 0 1 9.4 4.44C8.8 5.55 8.35 6.75 8 8m-2.92 8H8c.35 1.25.8 2.45 1.4 3.56A8.008 8.008 0 0 1 5.08 16m-.82-2C4.1 13.36 4 12.69 4 12s.1-1.36.26-2h3.38c-.08.66-.14 1.32-.14 2 0 .68.06 1.34.14 2M12 4.03c.83 1.2 1.5 2.54 1.91 3.97h-3.82c.41-1.43 1.08-2.77 1.91-3.97M18.92 8h-2.95a15.65 15.65 0 0 0-1.38-3.56c1.84.63 3.37 1.9 4.33 3.56M12 2C6.47 2 2 6.5 2 12a10 10 0 0 0 10 10A10 10 0 0 0 22 12 10 10 0 0 0 12 2z"/></svg>'],
                ['url' => $email ? 'mailto:' . $email : '', 'title' => 'E-Mail', 'svg' => '<svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>'],
                ['url' => $linkedin, 'title' => 'LinkedIn',    'svg' => '<svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.3v-1.11h-2.79v8.37h2.79v-4.93c0-.77.62-1.4 1.39-1.4a1.4 1.4 0 0 1 1.4 1.4v4.93h2.79M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.37H5.5v8.37h2.77z"/></svg>'],
                ['url' => $xing,     'title' => 'XING',        'svg' => '<svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18.188 0c-.517 0-.741.325-.927.66l-7.702 13.657 4.919 9.023c.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916L22.139.756c.095-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zm-9.945 5.237c-.208 0-.37.093-.458.233-.088.14-.09.316-.004.494l2.214 3.836-3.466 5.239c-.098.198-.1.388-.007.522.09.13.252.201.461.201h3.5c.513 0 .739-.326.926-.661l3.457-5.276-2.2-3.807c-.178-.308-.411-.663-.944-.663z"/></svg>'],
                ['url' => $twitter ? 'https://twitter.com/' . ltrim($twitter, '@') : '', 'title' => 'X / Twitter', 'svg' => '<svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'],
                ['url' => $github,   'title' => 'GitHub',      'svg' => '<svg class="social-icon" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>'],
            ];
            foreach ($social_items as $si):
                $active = !empty($si['url']);
            ?>
                <a href="<?php echo $active ? $sec->escape($si['url']) : '#'; ?>"
                   <?php echo $active ? 'target="_blank" rel="noopener"' : 'class="social-disabled"'; ?>
                   title="<?php echo $sec->escape($si['title']); ?>">
                    <?php echo $si['svg']; ?>
                </a>
            <?php endforeach; ?>
    </div>

    <!-- CTA Button -->
    <a href="<?php echo $sec->escape($url); ?>" class="expert-card-cta">
        Profil ansehen <span class="cta-arrow">&#8250;</span>
    </a>

</article>
