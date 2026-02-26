<?php
/**
 * Single Expert Template: Detail-Ansicht – vollständige Daten
 *
 * @package CMS_Experts
 * @var object $expert
 * @var array  $skills          [{skill_name, skill_level, skill_type}]
 * @var array  $certifications  [{cert_name, cert_issuer, cert_date, cert_expiry, cert_url}]
 * @var array  $projects        [{project_name, project_description, project_role, project_start, project_end, project_url, technologies}]
 * @var array  $education       [{degree, institution, field_of_study, start_year, end_year, description}]
 * @var array  $meta            assoc: social_linkedin, social_xing, social_github, social_twitter, social_website, partner_status, languages, remote_work, notice_period, travel_willingness
 * @var array  $specializations [{id, name, slug, parent_id}]
 * @var array  $settings        Plugin-Design-Settings
 */

if (!defined('ABSPATH')) { exit; }

$sec      = CMS\Security::instance();
$settings = $settings ?? [];

// ── CSS-Variablen aus Settings ──────────────────────────────────────────────
$detail_header_bg   = $settings['design_detail_header_bg']       ?? '#1e293b';
$detail_header_col  = $settings['design_detail_header_color']    ?? '#ffffff';
$detail_accent      = $settings['design_detail_accent']          ?? ($settings['design_primary_color'] ?? '#5e72e4');
$status_avail_color = $settings['design_status_available_color'] ?? '#14532d';
$status_limit_color = $settings['design_status_limited_color']   ?? '#7c4a03';
$status_book_color  = $settings['design_status_booked_color']    ?? '#7f1d1d';
$partner_color      = $settings['design_partner_color']          ?? '#9ca3af';
$top_partner_color  = $settings['design_top_partner_color']      ?? '#d97706';
$sponsor_color      = $settings['design_sponsor_color']          ?? '#7c3aed';
$cta_color          = $settings['design_cta_color']              ?? '#c2410c';
$primary_color      = $settings['design_primary_color']          ?? '#5e72e4';
$accent_color       = $settings['design_accent_color']           ?? '#8965e0';
$card_bg_color      = $settings['design_card_bg']                ?? '#fffdf4';
$hdr_from           = $settings['archive_header_bg_from']        ?? '#f5ecd5';
$hdr_to             = $settings['archive_header_bg_to']          ?? '#ebe0c8';
$hdr_title          = $settings['archive_header_title_color']    ?? '#7c4700';
$border_radius      = (int)($settings['design_border_radius']    ?? 12);
?>
<style>
:root {
  --expert-primary:         <?php echo $sec->escape($primary_color); ?>;
  --expert-accent:          <?php echo $sec->escape($accent_color); ?>;
  --expert-cta-color:       <?php echo $sec->escape($cta_color); ?>;
  --expert-card-bg:         <?php echo $sec->escape($card_bg_color); ?>;
  --expert-hdr-bg:          linear-gradient(135deg, <?php echo $sec->escape($hdr_from); ?> 0%, <?php echo $sec->escape($hdr_to); ?> 100%);
  --expert-hdr-title:       <?php echo $sec->escape($hdr_title); ?>;
  --expert-radius:          <?php echo $border_radius; ?>px;
  --detail-header-bg:       <?php echo $sec->escape($detail_header_bg); ?>;
  --detail-header-color:    <?php echo $sec->escape($detail_header_col); ?>;
  --detail-accent:          <?php echo $sec->escape($detail_accent); ?>;
  --status-available-color: <?php echo $sec->escape($status_avail_color); ?>;
  --status-limited-color:   <?php echo $sec->escape($status_limit_color); ?>;
  --status-booked-color:    <?php echo $sec->escape($status_book_color); ?>;
  --partner-color:          <?php echo $sec->escape($partner_color); ?>;
  --top-partner-color:      <?php echo $sec->escape($top_partner_color); ?>;
  --sponsor-color:          <?php echo $sec->escape($sponsor_color); ?>;
  --expert-cta:             <?php echo $sec->escape($cta_color); ?>;
}
</style>

<?php
// ── Data Extraction ──────────────────────────────────────────────────────────
$full_name      = trim(($expert->first_name ?? '') . ' ' . ($expert->last_name ?? ''));
$photo          = $expert->photo_url ?? '';
$position       = $expert->position ?? '';
$company        = $expert->company  ?? '';
$avail          = $expert->availability ?? 'available';
$avail_map      = [
    'available' => ['label' => 'Verfügbar',          'css' => 'status-available'],
    'limited'   => ['label' => 'Begrenzt verfügbar', 'css' => 'status-limited'],
    'booked'    => ['label' => 'Nicht verfügbar',    'css' => 'status-booked'],
];
$avail_info     = $avail_map[$avail] ?? ['label' => ucfirst($avail), 'css' => 'status-available'];
$partner_status = $meta['partner_status'] ?? '';
$hourly_rate    = !empty($expert->hourly_rate) ? (float)$expert->hourly_rate : null;
$daily_rate     = !empty($expert->daily_rate)  ? (float)$expert->daily_rate  : null;

// Skills nach Typ gruppieren
$skills_by_type = ['general' => [], 'tech' => [], 'soft' => []];
if (!empty($skills) && is_array($skills)) {
    foreach ($skills as $s) {
        $type = !empty($s->skill_type) ? (string)$s->skill_type : 'general';
        if (!array_key_exists($type, $skills_by_type)) { $type = 'general'; }
        $skills_by_type[$type][] = $s;
    }
}

// Social Links
$social = [
    'linkedin' => $meta['social_linkedin'] ?? '',
    'xing'     => $meta['social_xing']     ?? '',
    'github'   => $meta['social_github']   ?? '',
    'twitter'  => $meta['social_twitter']  ?? '',
    'website'  => $meta['social_website']  ?? '',
];
$has_social = !empty(array_filter($social));

// Weitere Angaben
$languages          = $meta['languages']          ?? '';
$remote_work        = $meta['remote_work']         ?? '';
$notice_period      = $meta['notice_period']       ?? '';
$travel_willingness = $meta['travel_willingness']  ?? '';

// Erweiterte Meta-Felder (WP-kompatibel)
$motto              = $meta['motto']                   ?? '';
$timezone           = $meta['timezone']                ?? '';
$work_type          = $meta['work_type']               ?? '';
$contact_times      = $meta['contact_times']           ?? '';
$is_certified       = !empty($meta['is_certified']);
$is_premium         = !empty($meta['is_premium']);
$is_mvp             = !empty($meta['is_mvp']);
$custom_award       = $meta['custom_award']            ?? '';
$retainer           = !empty($meta['retainer']);
$weekly_hours       = $meta['weekly_hours']            ?? '';
$min_proj_dur       = $meta['min_project_duration']    ?? '';
$max_proj_dur       = $meta['max_project_duration']    ?? '';
$payment_terms      = $meta['payment_terms']           ?? '';
$min_booking        = $meta['min_booking_duration']    ?? '';
$fixed_price        = !empty($meta['fixed_price_projects']);
$time_material_flag = !empty($meta['time_material']);
$travel_cost_model  = $meta['travel_cost_model']       ?? '';
$max_travel_km      = $meta['max_travel_distance_km']  ?? '';
$pref_sizes_raw     = $meta['preferred_company_sizes'] ?? '';
$pref_sizes         = is_array($pref_sizes_raw) ? $pref_sizes_raw : (json_decode((string)$pref_sizes_raw, true) ?: []);
$next_avail_date    = $meta['avail_date']              ?? '';
$svc_consulting     = !empty($meta['services_consulting']);
$svc_impl           = !empty($meta['services_implementation']);
$svc_training       = !empty($meta['services_training']);
$svc_support        = !empty($meta['services_support']);
$svc_audit          = !empty($meta['services_audit']);
$emergency_support  = !empty($meta['emergency_support']);
$workshop_offerings = !empty($meta['workshop_offerings']);
$subcontractors     = !empty($meta['subcontractors_available']);
$team_expansion     = !empty($meta['team_expansion_possible']);
$max_team_size      = $meta['max_team_size']           ?? '';
$partner_networks   = $meta['partner_networks']        ?? '';
$team_size_led      = $meta['team_size_led']           ?? '';
$total_projects_cnt = $meta['total_projects']          ?? '';

// Erweiterte Social Links
$social['gitlab']        = $meta['social_gitlab']        ?? '';
$social['stackoverflow'] = $meta['social_stackoverflow'] ?? '';
$social['youtube']       = $meta['social_youtube']       ?? '';
$social['blog_rss']      = $meta['social_blog_rss']      ?? '';
$has_social = !empty(array_filter($social));

// Technische Expertise (JSON aus Meta)
$prog_languages_raw = $meta['programming_languages'] ?? '';
$prog_languages     = is_array($prog_languages_raw) ? $prog_languages_raw : (json_decode((string)$prog_languages_raw, true) ?: []);
$frameworks_raw     = $meta['frameworks'] ?? '';
$expert_frameworks  = is_array($frameworks_raw) ? $frameworks_raw : (json_decode((string)$frameworks_raw, true) ?: []);
$databases_raw      = $meta['databases'] ?? '';
$expert_databases   = is_array($databases_raw) ? $databases_raw : (json_decode((string)$databases_raw, true) ?: []);
$cloud_raw          = $meta['cloud_platforms'] ?? '';
$cloud_platforms    = is_array($cloud_raw) ? $cloud_raw : (json_decode((string)$cloud_raw, true) ?: []);
$industry_raw       = $meta['industry_experience'] ?? '';
$industry_experience= is_array($industry_raw) ? $industry_raw : (json_decode((string)$industry_raw, true) ?: []);
$tools_raw          = $meta['tools_preferred'] ?? '';
$tools_preferred    = is_array($tools_raw) ? $tools_raw : (json_decode((string)$tools_raw, true) ?: []);

// Karrierestationen & Referenzen (JSON)
$career_raw         = $meta['career_stations'] ?? '';
$career_stations_data = is_array($career_raw) ? $career_raw : (json_decode((string)$career_raw, true) ?: []);
$testimonials_raw   = $meta['testimonials'] ?? '';
$testimonials_data  = is_array($testimonials_raw) ? $testimonials_raw : (json_decode((string)$testimonials_raw, true) ?: []);
$case_studies_raw   = $meta['case_studies'] ?? '';
$case_studies_data  = is_array($case_studies_raw) ? $case_studies_raw : (json_decode((string)$case_studies_raw, true) ?: []);
$conf_talks_raw     = $meta['conference_talks'] ?? '';
$conference_talks   = is_array($conf_talks_raw) ? $conf_talks_raw : (json_decode((string)$conf_talks_raw, true) ?: []);

// Partner-Badge HTML
$partner_badge_html = match ($partner_status) {
    'sponsor'    => '<span class="detail-partner-badge badge-sponsor">&#11088; Sponsor</span>',
    'top_partner'=> '<span class="detail-partner-badge badge-top-partner">&#9733; Top-Partner</span>',
    'partner'    => '<span class="detail-partner-badge badge-partner">&#10003; Partner</span>',
    default      => '',
};
?>

<main class="single-expert-view">

    <!-- ═══════════════════════════════════════════
         HERO HEADER
    ═══════════════════════════════════════════ -->
    <div class="single-expert-header">
        <div class="expert-header-inner">

            <!-- Avatar -->
            <div class="expert-header-avatar-wrap<?php if ($partner_status) echo ' partner-border-' . $sec->escape($partner_status); ?>">
                <?php if ($photo): ?>
                    <img class="expert-header-avatar-img"
                         src="<?php echo $sec->escape($photo); ?>"
                         alt="<?php echo $sec->escape($full_name); ?>">
                <?php else: ?>
                    <div class="expert-avatar-initials">
                        <?php echo $sec->escape(
                            strtoupper(mb_substr($expert->first_name ?? 'E', 0, 1))
                            . strtoupper(mb_substr($expert->last_name  ?? '', 0, 1))
                        ); ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Kopf-Inhalt -->
            <div class="expert-header-content">
                <div class="expert-header-badges">
                    <?php echo $partner_badge_html; ?>
                    <?php if ($is_mvp): ?>
                        <span class="detail-partner-badge badge-mvp">&#9889; MVP</span>
                    <?php endif; ?>
                    <?php if ($is_certified): ?>
                        <span class="detail-partner-badge badge-certified">&#10003; Zertifiziert</span>
                    <?php endif; ?>
                    <?php if ($is_premium): ?>
                        <span class="detail-partner-badge badge-premium">&#11088; Premium</span>
                    <?php endif; ?>
                    <span class="detail-avail-badge <?php echo $avail_info['css']; ?>">
                        <?php echo $sec->escape($avail_info['label']); ?>
                    </span>
                </div>
                <?php if ($custom_award): ?>
                    <div class="expert-custom-award">&#127942; <?php echo $sec->escape($custom_award); ?></div>
                <?php endif; ?>

                <h1 class="expert-detail-name"><?php echo $sec->escape($full_name); ?></h1>
                <?php if ($motto): ?>
                    <p class="expert-detail-motto"><em>"<?php echo $sec->escape($motto); ?>"</em></p>
                <?php endif; ?>
                <?php if ($position): ?>
                    <div class="expert-detail-position"><?php echo $sec->escape($position); ?></div>
                <?php endif; ?>
                <?php if ($company): ?>
                    <div class="expert-detail-company">&#127970; <?php echo $sec->escape($company); ?></div>
                <?php endif; ?>

                <div class="expert-header-chips">
                    <?php if (!empty($expert->location_city)): ?>
                        <span class="detail-chip">&#128205; <?php echo $sec->escape($expert->location_city); ?><?php if (!empty($expert->location_country)): ?>, <?php echo $sec->escape($expert->location_country); ?><?php endif; ?></span>
                    <?php endif; ?>
                    <?php if (!empty($expert->experience_years)): ?>
                        <span class="detail-chip">&#128188; <?php echo (int)$expert->experience_years; ?> Jahre Erfahrung</span>
                    <?php endif; ?>
                    <?php if ($remote_work): ?>
                        <?php $rm_chip = ['yes' => 'Remote möglich', 'only' => 'Nur Remote', 'no' => 'Vor Ort', 'partial' => 'Hybrid', 'full' => 'Vollständig Remote', 'preferred' => 'Remote bevorzugt']; ?>
                        <span class="detail-chip">&#127968; <?php echo $sec->escape($rm_chip[$remote_work] ?? ucfirst($remote_work)); ?></span>
                    <?php endif; ?>
                    <?php if ($work_type): ?>
                        <?php $wt_map = ['freelancer' => 'Freelancer', 'employed' => 'Angestellt', 'agency' => 'Agentur', 'contractor' => 'Contractor']; ?>
                        <span class="detail-chip">&#128084; <?php echo $sec->escape($wt_map[$work_type] ?? ucfirst($work_type)); ?></span>
                    <?php endif; ?>
                    <?php if ($timezone): ?>
                        <span class="detail-chip">&#128347; <?php echo $sec->escape($timezone); ?></span>
                    <?php endif; ?>
                    <?php if ($total_projects_cnt): ?>
                        <span class="detail-chip">&#128196; <?php echo (int)$total_projects_cnt; ?>+ Projekte</span>
                    <?php endif; ?>
                </div>

                <?php if ($has_social): ?>
                <div class="expert-header-social">
                    <?php if ($social['linkedin']): ?>
                        <a href="<?php echo $sec->escape($social['linkedin']); ?>" target="_blank" rel="noopener" class="detail-social-icon" aria-label="LinkedIn">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($social['xing']): ?>
                        <a href="<?php echo $sec->escape($social['xing']); ?>" target="_blank" rel="noopener" class="detail-social-icon" aria-label="XING">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.188 0c-.517 0-.741.325-.927.66 0 0-7.455 13.224-7.702 13.657.015.024 4.919 9.023 4.919 9.023.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916c-.004-.006-.004-.016 0-.022L22.139.756c.097-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zM3.648 4.74c-.211 0-.385.074-.473.216-.09.149-.078.339.02.531l2.34 4.05c.004.01.004.016 0 .021L1.86 16.051c-.099.188-.093.381 0 .529.085.142.247.22.455.22h3.514c.518 0 .731-.405.92-.73l3.671-6.471-2.342-4.052c-.17-.309-.436-.807-.978-.807H3.648z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($social['github']): ?>
                        <a href="<?php echo $sec->escape($social['github']); ?>" target="_blank" rel="noopener" class="detail-social-icon" aria-label="GitHub">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($social['twitter']): ?>
                        <a href="<?php echo $sec->escape($social['twitter']); ?>" target="_blank" rel="noopener" class="detail-social-icon" aria-label="Twitter/X">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622L18.244 2.25zm-1.161 17.52h1.833L7.084 4.126H5.117L17.083 19.77z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($social['website']): ?>
                        <a href="<?php echo $sec->escape($social['website']); ?>" target="_blank" rel="noopener" class="detail-social-icon" aria-label="Website">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($social['gitlab']): ?>
                        <a href="<?php echo $sec->escape($social['gitlab']); ?>" target="_blank" rel="noopener" class="detail-social-icon" aria-label="GitLab">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 01-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 014.82 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.49h8.1l2.44-7.49a.42.42 0 01.11-.18.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.51L23 13.45a.84.84 0 01-.35.94z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($social['stackoverflow']): ?>
                        <a href="<?php echo $sec->escape($social['stackoverflow']); ?>" target="_blank" rel="noopener" class="detail-social-icon" aria-label="Stack Overflow">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.986 21.865v-6.404h2.134V24H1.844v-8.539h2.13v6.404h15.012zM6.111 19.731H16.85v-2.137H6.111v2.137zm.259-4.852l10.48 2.189.451-2.07-10.478-2.187-.453 2.068zm1.359-5.056l9.705 4.53.903-1.95-9.706-4.53-.902 1.95zm2.715-4.785l8.217 6.855 1.359-1.62-8.216-6.853-1.36 1.618zM15.751 0l-1.746 1.294 6.405 8.604 1.746-1.294L15.751 0z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($social['youtube']): ?>
                        <a href="<?php echo $sec->escape($social['youtube']); ?>" target="_blank" rel="noopener" class="detail-social-icon" aria-label="YouTube">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M23.495 6.205a3.007 3.007 0 00-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 00.527 6.205a31.247 31.247 0 00-.522 5.805 31.247 31.247 0 00.522 5.783 3.007 3.007 0 002.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 002.088-2.088 31.247 31.247 0 00.5-5.783 31.247 31.247 0 00-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($social['blog_rss']): ?>
                        <a href="<?php echo $sec->escape($social['blog_rss']); ?>" target="_blank" rel="noopener" class="detail-social-icon" aria-label="Blog/RSS">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M6.18 15.64a2.18 2.18 0 012.18 2.18C8.36 19.01 7.38 20 6.18 20C4.98 20 4 19.01 4 17.82a2.18 2.18 0 012.18-2.18M4 4.44A15.56 15.56 0 0119.56 20h-2.83A12.73 12.73 0 004 7.27V4.44m0 5.66a9.9 9.9 0 019.9 9.9h-2.83A7.07 7.07 0 004 12.93V10.1z"/></svg>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            </div><!-- /.expert-header-content -->
        </div><!-- /.expert-header-inner -->
    </div><!-- /.single-expert-header -->

    <!-- ═══════════════════════════════════════════
         ZWEI-SPALTEN-LAYOUT
    ═══════════════════════════════════════════ -->
    <div class="expert-detail-layout">

        <!-- ── MAIN CONTENT ──────────────────── -->
        <div class="expert-detail-main">

            <?php /* BIOGRAFIE */ ?>
            <?php if (!empty($expert->biography) && trim((string)$expert->biography) !== ''): ?>
            <section class="expert-detail-section">
                <h2 class="expert-section-title">&#128100; Über mich</h2>
                <div class="expert-detail-bio">
                    <?php
                    $bio = (string)$expert->biography;
                    // SunEditor-HTML oder Plain-Text?
                    $has_html_tags = (bool)preg_match('/<(p|ul|ol|h[1-6]|blockquote|table|div|br)[\s>]/i', $bio);
                    if ($has_html_tags):
                    ?>
                    <div class="sun-editor-editable"><?php echo $bio; ?></div>
                    <?php else:
                        $paras = preg_split('/\n{2,}/', trim($bio));
                        $paras = $paras ?: [$bio];
                        foreach ($paras as $para) {
                            echo '<p>' . nl2br($sec->escape(trim($para))) . '</p>';
                        }
                    endif; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php /* FACHRICHTUNGEN */ ?>
            <?php if (!empty($specializations)): ?>
            <section class="expert-detail-section">
                <h2 class="expert-section-title">&#127891; Fachrichtungen</h2>
                <div class="expert-specializations-grid">
                    <?php foreach ($specializations as $spec): ?>
                        <div class="expert-spec-item">
                            <span class="expert-spec-check">&#10003;</span>
                            <?php echo $sec->escape($spec->name ?? ''); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php /* KOMPETENZEN – nach Typ */ ?>
            <?php $any_skills = !empty($skills_by_type['general']) || !empty($skills_by_type['tech']) || !empty($skills_by_type['soft']); ?>
            <?php if ($any_skills): ?>
            <section class="expert-detail-section">
                <h2 class="expert-section-title">&#128295; Kompetenzen</h2>
                <?php foreach (['general' => ['&#9728; Allgemein', 'skill-general'], 'tech' => ['&#128187; Technologie', 'skill-tech'], 'soft' => ['&#129309; Soft Skills', 'skill-soft']] as $type => [$label, $cls]): ?>
                    <?php if (!empty($skills_by_type[$type])): ?>
                    <div class="expert-skill-group">
                        <h4 class="expert-skill-group-label"><?php echo $label; ?></h4>
                        <div class="expert-skills-pills">
                            <?php foreach ($skills_by_type[$type] as $sk): ?>
                                <span class="expert-skill-pill <?php echo $cls; ?>"><?php echo $sec->escape($sk->skill_name ?? ''); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
            <?php endif; ?>

            <?php /* PROJEKTE */ ?>
            <?php if (!empty($projects)): ?>
            <section class="expert-detail-section">
                <h2 class="expert-section-title">&#128196; Projekte</h2>
                <div class="expert-projects-list">
                    <?php foreach ($projects as $proj): ?>
                    <div class="expert-project-item">
                        <div class="expert-project-header">
                            <div>
                                <div class="expert-project-name">
                                    <?php if (!empty($proj->project_url)): ?>
                                        <a href="<?php echo $sec->escape($proj->project_url); ?>" target="_blank" rel="noopener"><?php echo $sec->escape($proj->project_name ?? ''); ?></a>
                                    <?php else: ?>
                                        <?php echo $sec->escape($proj->project_name ?? ''); ?>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($proj->project_role)): ?>
                                    <div class="expert-project-role"><?php echo $sec->escape($proj->project_role); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($proj->project_start) || !empty($proj->project_end)): ?>
                            <div class="expert-project-period">
                                <?php
                                $ps = !empty($proj->project_start) ? date('m/Y', strtotime($proj->project_start)) : '';
                                $pe = !empty($proj->project_end)   ? date('m/Y', strtotime($proj->project_end))   : 'heute';
                                echo $sec->escape($ps ? "$ps – $pe" : $pe);
                                ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($proj->project_description)): ?>
                            <p class="expert-project-desc"><?php echo nl2br($sec->escape($proj->project_description)); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($proj->technologies)): ?>
                            <div class="expert-project-techs">
                                <?php foreach (array_filter(array_map('trim', explode(',', $proj->technologies))) as $tech): ?>
                                    <span class="expert-tech-tag"><?php echo $sec->escape($tech); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php /* AUSBILDUNG */ ?>
            <?php if (!empty($education)): ?>
            <section class="expert-detail-section">
                <h2 class="expert-section-title">&#127979; Ausbildung</h2>
                <div class="expert-education-list">
                    <?php foreach ($education as $edu): ?>
                    <div class="expert-edu-item">
                        <div class="expert-edu-icon">&#127979;</div>
                        <div class="expert-edu-content">
                            <div class="expert-edu-degree"><?php echo $sec->escape($edu->degree ?? ''); ?></div>
                            <div class="expert-edu-institution"><?php echo $sec->escape($edu->institution ?? ''); ?></div>
                            <?php if (!empty($edu->field_of_study)): ?>
                                <div class="expert-edu-field"><?php echo $sec->escape($edu->field_of_study); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($edu->start_year) || !empty($edu->end_year)): ?>
                                <div class="expert-edu-period"><?php echo $sec->escape(($edu->start_year ?? '') . ($edu->end_year ? ' – ' . $edu->end_year : '')); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($edu->description)): ?>
                                <p class="expert-edu-desc"><?php echo nl2br($sec->escape($edu->description)); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <?php /* ZERTIFIKATE */ ?>
            <?php if (!empty($certifications)): ?>
            <section class="expert-detail-section">
                <h2 class="expert-section-title">&#127942; Zertifikate</h2>
                <div class="expert-certs-list">
                    <?php foreach ($certifications as $cert): ?>
                    <div class="expert-cert-item">
                        <div class="expert-cert-icon">&#127942;</div>
                        <div class="expert-cert-content">
                            <div class="expert-cert-name">
                                <?php if (!empty($cert->cert_url)): ?>
                                    <a href="<?php echo $sec->escape($cert->cert_url); ?>" target="_blank" rel="noopener"><?php echo $sec->escape($cert->cert_name ?? ''); ?></a>
                                <?php else: ?>
                                    <?php echo $sec->escape($cert->cert_name ?? ''); ?>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($cert->cert_issuer)): ?>
                                <div class="expert-cert-issuer"><?php echo $sec->escape($cert->cert_issuer); ?></div>
                            <?php endif; ?>
                            <?php if (!empty($cert->cert_date) || !empty($cert->cert_expiry)): ?>
                                <div class="expert-cert-dates">
                                    <?php if (!empty($cert->cert_date)):   ?><span>Ausgestellt: <?php echo $sec->escape(date('m/Y', strtotime($cert->cert_date))); ?></span><?php endif; ?>
                                    <?php if (!empty($cert->cert_expiry)): ?><span>Gültig bis: <?php echo $sec->escape(date('m/Y', strtotime($cert->cert_expiry))); ?></span><?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

        </div><!-- /.expert-detail-main -->

        <?php /* TECHNISCHE EXPERTISE */ ?>
        <?php
        $has_tech_detail = !empty($prog_languages) || !empty($expert_frameworks) || !empty($expert_databases) || !empty($cloud_platforms) || !empty($industry_experience);
        ?>
        <?php if ($has_tech_detail || !empty($tools_preferred)): ?>
        <section class="expert-detail-section expert-tech-expertise">
            <h2 class="expert-section-title">&#128187; Technische Expertise</h2>
            <?php if ($has_tech_detail): ?>
            <div class="expert-tech-grid">
                <?php
                $level_labels = ['expert' => 'Experte', 'advanced' => 'Fortgeschritten', 'intermediate' => 'Mittel', 'beginner' => 'Einsteiger'];
                $tech_cats = [
                    ['icon' => '&#128187;', 'title' => 'Programmiersprachen', 'items' => $prog_languages,    'key' => 'language'],
                    ['icon' => '&#9881;',   'title' => 'Frameworks',           'items' => $expert_frameworks, 'key' => 'name'],
                    ['icon' => '&#128451;', 'title' => 'Datenbanken',          'items' => $expert_databases,  'key' => 'name'],
                    ['icon' => '&#9729;',   'title' => 'Cloud-Expertise',      'items' => $cloud_platforms,   'key' => 'platform'],
                ];
                foreach ($tech_cats as $cat): ?>
                <div class="expert-tech-cat-card">
                    <div class="expert-tech-cat-header">
                        <span class="expert-tech-cat-icon"><?php echo $cat['icon']; ?></span>
                        <h5 class="expert-tech-cat-title"><?php echo $sec->escape($cat['title']); ?></h5>
                    </div>
                    <div class="expert-tech-skills-list">
                        <?php if (!empty($cat['items']) && is_array($cat['items'])): ?>
                            <?php foreach ($cat['items'] as $item):
                                if (!is_array($item) || empty($item[$cat['key']])) continue;
                                $lvl = $item['level'] ?? 'intermediate';
                                $pct = ['expert' => 95, 'advanced' => 75, 'intermediate' => 50, 'beginner' => 25][$lvl] ?? 50;
                            ?>
                            <div class="expert-tech-skill-item expert-tech-level-<?php echo $sec->escape($lvl); ?>">
                                <span class="expert-tech-skill-name"><?php echo $sec->escape($item[$cat['key']]); ?></span>
                                <span class="expert-tech-skill-level"><?php echo $sec->escape($level_labels[$lvl] ?? ucfirst($lvl)); ?></span>
                                <div class="expert-tech-skill-bar"><div class="expert-tech-skill-fill" style="width:<?php echo $pct; ?>%"></div></div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="expert-tech-empty">Keine Angaben</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($tools_preferred)): ?>
            <div class="expert-tools-section">
                <h5 class="expert-tools-title">&#128736; Bevorzugte Tools</h5>
                <div class="expert-tools-tags">
                    <?php foreach ($tools_preferred as $tool): ?>
                        <span class="expert-tool-tag"><?php echo $sec->escape(is_string($tool) ? $tool : ($tool['name'] ?? '')); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($industry_experience)): ?>
            <div class="expert-industry-section">
                <h5 class="expert-industry-title">&#127970; Branchenerfahrung</h5>
                <div class="expert-industry-tags">
                    <?php foreach ($industry_experience as $ind): ?>
                        <span class="expert-industry-tag"><?php echo $sec->escape(is_string($ind) ? $ind : ($ind['name'] ?? '')); ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <?php /* KARRIERESTATIONEN */ ?>
        <?php if (!empty($career_stations_data)): ?>
        <section class="expert-detail-section">
            <h2 class="expert-section-title">&#128188; Berufliche Stationen</h2>
            <div class="expert-career-list">
                <?php foreach ($career_stations_data as $station):
                    if (!is_array($station) || empty($station['company'])) continue;
                ?>
                <div class="expert-career-item">
                    <div class="expert-career-header">
                        <div>
                            <div class="expert-career-company"><?php echo $sec->escape($station['company']); ?></div>
                            <?php if (!empty($station['position'])): ?>
                                <div class="expert-career-position"><?php echo $sec->escape($station['position']); ?></div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($station['from_date']) || !empty($station['location'])): ?>
                        <div class="expert-career-meta">
                            <?php if (!empty($station['from_date'])): ?>
                                <span class="expert-career-period">
                                    <?php echo $sec->escape($station['from_date']); ?>
                                    <?php echo !empty($station['to_date']) ? ' – ' . $sec->escape($station['to_date']) : ' – heute'; ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($station['location'])): ?>
                                <span class="expert-career-location">&#128205; <?php echo $sec->escape($station['location']); ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($station['achievements'])): ?>
                        <div class="expert-career-achievements"><?php echo nl2br($sec->escape($station['achievements'])); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php /* REFERENZEN & PORTFOLIO */ ?>
        <?php
        $has_references = !empty($testimonials_data) || !empty($case_studies_data) || !empty($conference_talks);
        ?>
        <?php if ($has_references): ?>
        <section class="expert-detail-section">
            <h2 class="expert-section-title">&#11088; Referenzen & Portfolio</h2>

            <?php if (!empty($testimonials_data)): ?>
            <div class="expert-references-block">
                <h4 class="expert-ref-subtitle">&#128172; Kundenstimmen</h4>
                <div class="expert-testimonials-grid">
                    <?php foreach ($testimonials_data as $t):
                        if (!is_array($t) || (empty($t['text']) && empty($t['client_name']))) continue;
                        $rating = (int)($t['rating'] ?? 5);
                    ?>
                    <div class="expert-testimonial-card">
                        <div class="expert-testimonial-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php echo $i <= $rating ? '&#9733;' : '&#9734;'; ?>
                            <?php endfor; ?>
                        </div>
                        <?php if (!empty($t['text'])): ?>
                            <blockquote class="expert-testimonial-text">"<?php echo $sec->escape($t['text']); ?>"</blockquote>
                        <?php endif; ?>
                        <div class="expert-testimonial-author">
                            <strong><?php echo $sec->escape($t['client_name'] ?? ''); ?></strong>
                            <?php if (!empty($t['position']) || !empty($t['company'])): ?>
                                <span class="expert-testimonial-role"><?php echo $sec->escape($t['position'] ?? ''); ?><?php if (!empty($t['company'])) echo ', ' . $sec->escape($t['company']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($case_studies_data)): ?>
            <div class="expert-references-block">
                <h4 class="expert-ref-subtitle">&#128196; Case Studies</h4>
                <ul class="expert-case-studies-list">
                    <?php foreach ($case_studies_data as $cs):
                        if (!is_array($cs) || empty($cs['title'])) continue;
                    ?>
                    <li class="expert-case-study-item">
                        <div class="expert-case-study-title">
                            <?php if (!empty($cs['link'])): ?>
                                <a href="<?php echo $sec->escape($cs['link']); ?>" target="_blank" rel="noopener">
                                    &#128209; <?php echo $sec->escape($cs['title']); ?> &#8599;
                                </a>
                            <?php else: ?>
                                &#128209; <?php echo $sec->escape($cs['title']); ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($cs['description'])): ?>
                            <p class="expert-case-study-desc"><?php echo $sec->escape($cs['description']); ?></p>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($conference_talks)): ?>
            <div class="expert-references-block">
                <h4 class="expert-ref-subtitle">&#127908; Vorträge & Konferenzen</h4>
                <ul class="expert-talks-list">
                    <?php foreach ($conference_talks as $talk):
                        if (!is_array($talk) || (empty($talk['event']) && empty($talk['title']))) continue;
                    ?>
                    <li class="expert-talk-item">
                        <strong><?php echo $sec->escape($talk['title'] ?? $talk['event']); ?></strong>
                        <?php if (!empty($talk['event']) && !empty($talk['title'])): ?>
                            <span class="expert-talk-event"> @ <?php echo $sec->escape($talk['event']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($talk['year'])): ?>
                            <span class="expert-talk-year"><?php echo $sec->escape((string)$talk['year']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($talk['video_link'])): ?>
                            <a href="<?php echo $sec->escape($talk['video_link']); ?>" target="_blank" rel="noopener" class="expert-talk-video">&#127916; Video</a>
                        <?php endif; ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- ── SIDEBAR ──────────────────────── -->
        <aside class="expert-detail-sidebar">

            <?php /* KONTAKT */ ?>
            <div class="expert-sidebar-card expert-contact-card" id="contact">
                <h3 class="expert-sidebar-card-title">&#129309; Kontakt aufnehmen</h3>
                <p class="expert-sidebar-card-sub">Interessiert an einer Zusammenarbeit mit <?php echo $sec->escape($expert->first_name ?? 'dem Experten'); ?>?</p>
                <?php if (!empty($expert->email)): ?>
                    <a href="mailto:<?php echo $sec->escape($expert->email); ?>" class="expert-sidebar-cta">&#9993; Nachricht senden</a>
                <?php endif; ?>
                <?php if (!empty($expert->phone)): ?>
                    <a href="tel:<?php echo $sec->escape($expert->phone); ?>" class="expert-sidebar-cta expert-sidebar-cta--secondary">&#128222; <?php echo $sec->escape($expert->phone); ?></a>
                <?php endif; ?>
                <?php if (!empty($expert->mobile)): ?>
                    <a href="tel:<?php echo $sec->escape($expert->mobile); ?>" class="expert-sidebar-cta expert-sidebar-cta--secondary">&#128241; <?php echo $sec->escape($expert->mobile); ?></a>
                <?php endif; ?>
                <?php if ($contact_times): ?>
                    <p class="expert-sidebar-contact-times">&#128336; <?php echo $sec->escape($contact_times); ?></p>
                <?php endif; ?>
            </div>

            <?php /* VERFÜGBARKEIT & ARBEITSWEISE */ ?>
            <?php if ($languages || $remote_work || $travel_willingness || $notice_period || $retainer || $team_size_led || $total_projects_cnt): ?>
            <div class="expert-sidebar-card expert-avail-card">
                <h3 class="expert-sidebar-card-title">&#128197; Profil & Arbeitsweise</h3>

                <?php if ($remote_work): ?>
                    <?php
                    $rm_map = ['yes' => 'Remote möglich', 'only' => 'Nur Remote', 'no' => 'Vor Ort', 'partial' => 'Hybrid', 'full' => 'Vollständig Remote', 'preferred' => 'Remote bevorzugt', 'no' => 'Kein Remote'];
                    $rm_icon = in_array($remote_work, ['full', 'only']) ? '&#9729;' : (($remote_work === 'partial' ? '&#128260;' : '&#127968;'));
                    ?>
                    <div class="expert-avail-row">
                        <span class="expert-avail-icon"><?php echo $rm_icon; ?></span>
                        <div><div class="expert-avail-label">Remote-Arbeit</div><div class="expert-avail-value"><?php echo $sec->escape($rm_map[$remote_work] ?? ucfirst($remote_work)); ?></div></div>
                    </div>
                <?php endif; ?>
                <?php if ($travel_willingness): ?>
                    <?php $tw_map = ['none' => 'Keine', 'regional' => 'Regional', 'national' => 'National', 'international' => 'International', 'local' => 'Nur lokal', 'europe' => 'Europa', 'worldwide' => 'Weltweit']; ?>
                    <div class="expert-avail-row"><span class="expert-avail-icon">&#9992;</span><div><div class="expert-avail-label">Reisebereitschaft</div><div class="expert-avail-value"><?php echo $sec->escape($tw_map[$travel_willingness] ?? ucfirst($travel_willingness)); ?></div></div></div>
                <?php endif; ?>
                <?php if ($notice_period): ?>
                    <?php $np_map = ['sofort' => 'Sofort', '2_wochen' => '2 Wochen', '4_wochen' => '4 Wochen', '3_monate' => '3 Monate', 'nach_absprache' => 'Nach Absprache']; ?>
                    <div class="expert-avail-row"><span class="expert-avail-icon">&#128203;</span><div><div class="expert-avail-label">Verfügbar ab</div><div class="expert-avail-value"><?php echo $sec->escape($np_map[$notice_period] ?? $notice_period); ?></div></div></div>
                <?php endif; ?>
                <?php if ($next_avail_date && strtotime((string)$next_avail_date)): ?>
                    <div class="expert-avail-row"><span class="expert-avail-icon">&#128197;</span><div><div class="expert-avail-label">Frei ab</div><div class="expert-avail-value"><?php echo $sec->escape(date('d.m.Y', strtotime((string)$next_avail_date))); ?></div></div></div>
                <?php endif; ?>
                <?php if ($retainer): ?>
                    <div class="expert-avail-badge-wrap">
                        <span class="expert-avail-badge-pill badge-retainer">&#10003; Retainer möglich</span>
                    </div>
                <?php endif; ?>
                <?php if ($team_size_led || $total_projects_cnt): ?>
                    <div class="expert-avail-stats">
                        <?php if ($team_size_led): ?><span class="expert-avail-stat">&#128101; Team bis <?php echo (int)$team_size_led; ?> Pers.</span><?php endif; ?>
                        <?php if ($total_projects_cnt): ?><span class="expert-avail-stat">&#128186; <?php echo (int)$total_projects_cnt; ?>+ Projekte</span><?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($languages): ?>
                    <?php
                    $lang_colors = [
                        'deutsch' => ['bg' => '#fef3c7', 'color' => '#92400e'], 'german'  => ['bg' => '#fef3c7', 'color' => '#92400e'],
                        'englisch'=> ['bg' => '#dbeafe', 'color' => '#1e40af'], 'english' => ['bg' => '#dbeafe', 'color' => '#1e40af'],
                        'französisch' => ['bg' => '#fce7f3', 'color' => '#9d174d'],
                        'spanisch' => ['bg' => '#fee2e2', 'color' => '#991b1b'],
                    ];
                    $langs_arr = array_filter(array_map('trim', explode(',', (string)$languages)));
                    ?>
                    <div class="expert-languages-block">
                        <div class="expert-avail-label" style="margin-bottom:.4rem;">&#127760; Sprachen</div>
                        <div class="expert-languages-badges">
                            <?php foreach ($langs_arr as $lang):
                                $lclr = $lang_colors[strtolower($lang)] ?? ['bg' => '#f1f5f9', 'color' => '#475569'];
                            ?>
                                <span class="expert-language-badge-v2" style="background:<?php echo $lclr['bg']; ?>;color:<?php echo $lclr['color']; ?>;">
                                    <?php echo $sec->escape($lang); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php /* ERWEITERTE KONDITIONEN */ ?>
            <?php
            $has_ext_cond = $weekly_hours || $min_proj_dur || $max_proj_dur || $payment_terms || $min_booking || $fixed_price || $time_material_flag || $travel_cost_model || $max_travel_km || !empty($pref_sizes);
            ?>
            <?php if (($hourly_rate || $daily_rate) || $has_ext_cond): ?>
            <div class="expert-sidebar-card expert-cond-card">
                <h3 class="expert-sidebar-card-title">&#128178; Konditionen</h3>
                <div class="expert-cond-grid">
                    <?php if ($hourly_rate): ?>
                    <div class="expert-cond-item">
                        <span class="expert-cond-value"><?php echo number_format((float)$hourly_rate, 0, ',', '.'); ?> €</span>
                        <span class="expert-cond-label">Stundensatz</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($daily_rate): ?>
                    <div class="expert-cond-item">
                        <span class="expert-cond-value"><?php echo number_format((float)$daily_rate, 0, ',', '.'); ?> €</span>
                        <span class="expert-cond-label">Tagessatz</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($weekly_hours): ?>
                    <div class="expert-cond-item">
                        <span class="expert-cond-value"><?php echo (int)$weekly_hours; ?>h</span>
                        <span class="expert-cond-label">pro Woche</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($min_proj_dur || $max_proj_dur): ?>
                    <div class="expert-cond-item">
                        <span class="expert-cond-value">
                            <?php
                            if ($min_proj_dur && $max_proj_dur) echo $sec->escape($min_proj_dur) . '–' . $sec->escape($max_proj_dur);
                            elseif ($min_proj_dur) echo 'ab ' . $sec->escape($min_proj_dur);
                            else echo 'bis ' . $sec->escape($max_proj_dur);
                            ?>
                        </span>
                        <span class="expert-cond-label">Projektdauer</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($min_booking): ?>
                    <div class="expert-cond-item">
                        <span class="expert-cond-value"><?php echo $sec->escape($min_booking); ?></span>
                        <span class="expert-cond-label">Min. Buchung</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($payment_terms): ?>
                        <?php $pt_map = ['netto_7' => '7 Tage', 'netto_14' => '14 Tage', 'netto_30' => '30 Tage', 'netto_60' => '60 Tage', 'vorkasse' => 'Vorkasse']; ?>
                    <div class="expert-cond-item">
                        <span class="expert-cond-value"><?php echo $sec->escape($pt_map[$payment_terms] ?? $payment_terms); ?></span>
                        <span class="expert-cond-label">Zahlungsziel</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($max_travel_km): ?>
                    <div class="expert-cond-item">
                        <span class="expert-cond-value"><?php echo (int)$max_travel_km; ?> km</span>
                        <span class="expert-cond-label">Max. Reise</span>
                    </div>
                    <?php endif; ?>
                    <?php if ($travel_cost_model): ?>
                        <?php $tcm_map = ['included' => 'Inklusive', 'flat_rate' => 'Pauschale', 'actual_cost' => 'Nach Aufwand', 'negotiable' => 'Verhandelbar']; ?>
                    <div class="expert-cond-item">
                        <span class="expert-cond-value"><?php echo $sec->escape($tcm_map[$travel_cost_model] ?? $travel_cost_model); ?></span>
                        <span class="expert-cond-label">Reisekosten</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($fixed_price || $time_material_flag): ?>
                <div class="expert-cond-badges">
                    <?php if ($fixed_price): ?><span class="expert-cond-badge badge-fixed">&#10003; Fixed-Price</span><?php endif; ?>
                    <?php if ($time_material_flag): ?><span class="expert-cond-badge badge-time">&#9200; Time & Material</span><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($pref_sizes)): ?>
                <div class="expert-cond-sizes">
                    <div class="expert-cond-sizes-label">Bevorzugte Kunden:</div>
                    <?php $size_map = ['Startup' => 'Startup', 'SMB' => 'KMU/Mittelstand', 'Enterprise' => 'Enterprise']; ?>
                    <?php foreach ($pref_sizes as $sz): ?>
                        <span class="expert-size-badge"><?php echo $sec->escape($size_map[$sz] ?? $sz); ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php /* SERVICE-ANGEBOT */ ?>
            <?php if ($svc_consulting || $svc_impl || $svc_training || $svc_support || $svc_audit || $emergency_support || $workshop_offerings): ?>
            <div class="expert-sidebar-card expert-services-card">
                <h3 class="expert-sidebar-card-title">&#128736; Serviceangebot</h3>
                <div class="expert-services-grid">
                    <?php if ($svc_consulting): ?><span class="expert-service-item">&#128172; Beratung</span><?php endif; ?>
                    <?php if ($svc_impl): ?><span class="expert-service-item">&#128296; Umsetzung</span><?php endif; ?>
                    <?php if ($svc_training): ?><span class="expert-service-item">&#127891; Training</span><?php endif; ?>
                    <?php if ($svc_support): ?><span class="expert-service-item">&#128506; Support</span><?php endif; ?>
                    <?php if ($svc_audit): ?><span class="expert-service-item">&#128269; Audit</span><?php endif; ?>
                    <?php if ($emergency_support): ?><span class="expert-service-item badge-emergency">&#9888; 24/7 Notfall</span><?php endif; ?>
                    <?php if ($workshop_offerings): ?><span class="expert-service-item">&#127979; Workshops</span><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php /* NETZWERK & SKALIERUNG */ ?>
            <?php if ($subcontractors || $team_expansion || $max_team_size || $partner_networks): ?>
            <div class="expert-sidebar-card expert-network-card">
                <h3 class="expert-sidebar-card-title">&#127757; Netzwerk & Skalierung</h3>
                <div class="expert-network-grid">
                    <div class="expert-network-item <?php echo $subcontractors ? 'active' : 'inactive'; ?>">
                        <span class="expert-network-icon"><?php echo $subcontractors ? '&#10003;' : '&#8722;'; ?></span>
                        <span class="expert-network-label">Subunternehmer</span>
                    </div>
                    <div class="expert-network-item <?php echo $team_expansion ? 'active' : 'inactive'; ?>">
                        <span class="expert-network-icon"><?php echo $team_expansion ? '&#10003;' : '&#8722;'; ?></span>
                        <span class="expert-network-label">Team-Erweiterung</span>
                    </div>
                    <?php if ($max_team_size): ?>
                    <div class="expert-network-item highlight">
                        <span class="expert-network-icon">&#128101;</span>
                        <span class="expert-network-label">Max. <?php echo (int)$max_team_size; ?> Personen</span>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($partner_networks): ?>
                <div class="expert-partner-networks">
                    <div class="expert-avail-label">Partner-Netzwerke</div>
                    <p class="expert-partner-networks-text"><?php echo nl2br($sec->escape($partner_networks)); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php /* SOCIAL LINKS */ ?>
            <?php if ($has_social): ?>
            <div class="expert-sidebar-card expert-social-card">
                <h3 class="expert-sidebar-card-title">&#128279; Online-Profile</h3>
                <?php if ($social['linkedin']): ?><a href="<?php echo $sec->escape($social['linkedin']); ?>" target="_blank" rel="noopener" class="expert-social-link"><span class="social-link-icon social-linkedin">in</span> LinkedIn</a><?php endif; ?>
                <?php if ($social['xing']):    ?><a href="<?php echo $sec->escape($social['xing']); ?>"    target="_blank" rel="noopener" class="expert-social-link"><span class="social-link-icon social-xing">X</span> XING</a><?php endif; ?>
                <?php if ($social['github']):  ?><a href="<?php echo $sec->escape($social['github']); ?>"  target="_blank" rel="noopener" class="expert-social-link"><span class="social-link-icon social-github">GH</span> GitHub</a><?php endif; ?>
                <?php if ($social['gitlab']):  ?><a href="<?php echo $sec->escape($social['gitlab']); ?>"  target="_blank" rel="noopener" class="expert-social-link"><span class="social-link-icon social-gitlab">GL</span> GitLab</a><?php endif; ?>
                <?php if ($social['stackoverflow']): ?><a href="<?php echo $sec->escape($social['stackoverflow']); ?>" target="_blank" rel="noopener" class="expert-social-link"><span class="social-link-icon social-so">SO</span> Stack Overflow</a><?php endif; ?>
                <?php if ($social['twitter']): ?><a href="<?php echo $sec->escape($social['twitter']); ?>" target="_blank" rel="noopener" class="expert-social-link"><span class="social-link-icon social-twitter">X</span> Twitter / X</a><?php endif; ?>
                <?php if ($social['youtube']): ?><a href="<?php echo $sec->escape($social['youtube']); ?>"  target="_blank" rel="noopener" class="expert-social-link"><span class="social-link-icon social-youtube">&#128249;</span> YouTube</a><?php endif; ?>
                <?php if ($social['website']): ?><a href="<?php echo $sec->escape($social['website']); ?>" target="_blank" rel="noopener" class="expert-social-link"><span class="social-link-icon social-website">&#127760;</span> Website</a><?php endif; ?>
                <?php if ($social['blog_rss']): ?><a href="<?php echo $sec->escape($social['blog_rss']); ?>" target="_blank" rel="noopener" class="expert-social-link"><span class="social-link-icon social-rss">&#128240;</span> Blog</a><?php endif; ?>
            </div>
            <?php endif; ?>

        </aside><!-- /.expert-detail-sidebar -->

    </div><!-- /.expert-detail-layout -->

</main><!-- /.single-expert-view -->

<?php /* RSS-FEED (Vollbreite) */ ?>
<?php if (!empty($social['blog_rss'])): ?>
<div class="single-expert-view">
    <div class="expert-rss-section">
        <div class="expert-rss-card">
            <div class="expert-rss-header">
                <span class="expert-rss-icon">&#128240;</span>
                <h3 class="expert-rss-title">Aktuelle Blog-Beiträge</h3>
            </div>
            <div class="expert-rss-content">
                <?php
                $rss_url = (string)$social['blog_rss'];
                $ctx     = stream_context_create(['http' => ['timeout' => 5]]);
                $raw     = @file_get_contents($rss_url, false, $ctx);
                $xml     = $raw ? @simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NOCDATA) : false;
                if ($xml && isset($xml->channel->item)):
                    $items = (array)$xml->channel->item;
                    $count = 0;
                ?>
                <ul class="expert-rss-list">
                    <?php foreach ($items as $item):
                        if ($count >= 5) break; $count++;
                        $title = (string)($item->title ?? '');
                        $link  = (string)($item->link  ?? '');
                        $pub   = (string)($item->pubDate ?? '');
                        if (!$title || !$link) continue;
                    ?>
                    <li class="expert-rss-item">
                        <a href="<?php echo $sec->escape($link); ?>" target="_blank" rel="noopener">
                            <span class="expert-rss-item-title"><?php echo $sec->escape($title); ?></span>
                            <?php if ($pub): ?><span class="expert-rss-item-date"><?php echo $sec->escape(date('d.m.Y', strtotime($pub))); ?></span><?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="expert-rss-footer">
                    <a href="<?php echo $sec->escape(preg_replace('#/(feed|rss|atom)/?$#i', '', rtrim($rss_url, '/'))); ?>" target="_blank" rel="noopener" class="expert-rss-all-btn">Alle Beiträge anzeigen &#8599;</a>
                </div>
                <?php else: ?>
                    <p class="expert-rss-empty">Blog-Feed konnte nicht geladen werden.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* ─── Erweiterte Styles: WP-kompatible neue Elemente ────────────────────── */

/* Motto & Custom Award */
.expert-detail-motto { font-size:.95rem; color:rgba(255,255,255,.75); font-style:italic; margin:.3rem 0 .5rem; }
.expert-custom-award { font-size:.78rem; font-weight:700; color:#fbbf24; margin-bottom:.5rem; }

/* Badges */
.badge-mvp       { background:linear-gradient(135deg,#7c3aed,#5b21b6)!important; color:#fff!important; }
.badge-certified { background:#d1fae5!important; color:#065f46!important; }
.badge-premium   { background:linear-gradient(135deg,#d97706,#b45309)!important; color:#fff!important; }

/* Tech Grid */
.expert-tech-expertise { margin-top:1.5rem; }
.expert-tech-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:1rem; margin-bottom:1rem; }
.expert-tech-cat-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:1rem; }
.expert-tech-cat-header { display:flex; align-items:center; gap:.5rem; margin-bottom:.75rem; }
.expert-tech-cat-icon { font-size:1.1rem; }
.expert-tech-cat-title { font-size:.85rem; font-weight:700; color:#475569; margin:0; }
.expert-tech-skills-list { display:flex; flex-direction:column; gap:.5rem; }
.expert-tech-skill-item { display:flex; flex-direction:column; gap:.2rem; }
.expert-tech-skill-name { font-size:.875rem; font-weight:600; color:#1e293b; }
.expert-tech-skill-level { font-size:.72rem; color:#6b7280; }
.expert-tech-skill-bar { height:4px; background:#e2e8f0; border-radius:4px; overflow:hidden; }
.expert-tech-skill-fill { height:100%; background:var(--detail-accent,#5e72e4); border-radius:4px; transition:width .3s; }
.expert-tech-level-expert   .expert-tech-skill-fill { background:#10b981; }
.expert-tech-level-advanced .expert-tech-skill-fill { background:#3b82f6; }
.expert-tech-level-intermediate .expert-tech-skill-fill { background:#f59e0b; }
.expert-tech-level-beginner .expert-tech-skill-fill { background:#94a3b8; }
.expert-tech-empty { font-size:.8rem; color:#9ca3af; font-style:italic; }
.expert-tools-section, .expert-industry-section { margin-top:.75rem; }
.expert-tools-title, .expert-industry-title { font-size:.875rem; font-weight:700; color:#475569; margin:0 0 .5rem; }
.expert-tools-tags, .expert-industry-tags { display:flex; flex-wrap:wrap; gap:.375rem; }
.expert-tool-tag, .expert-industry-tag { display:inline-block; padding:.25rem .625rem; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:20px; font-size:.78rem; font-weight:500; }

/* Karrierestationen */
.expert-career-list { display:flex; flex-direction:column; gap:1rem; }
.expert-career-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:1rem; }
.expert-career-header { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:.5rem; margin-bottom:.5rem; }
.expert-career-company { font-size:1rem; font-weight:700; color:#1e293b; }
.expert-career-position { font-size:.875rem; color:#475569; margin-top:.2rem; }
.expert-career-meta { display:flex; flex-direction:column; align-items:flex-end; gap:.25rem; }
.expert-career-period { font-size:.8rem; color:#64748b; }
.expert-career-location { font-size:.8rem; color:#64748b; }
.expert-career-achievements { font-size:.875rem; color:#475569; line-height:1.6; }

/* Referenzen */
.expert-references-block { margin-bottom:1.5rem; }
.expert-ref-subtitle { font-size:.95rem; font-weight:700; color:#475569; margin:0 0 .75rem; }
.expert-testimonials-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:1rem; }
.expert-testimonial-card { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:1rem; }
.expert-testimonial-stars { color:#f59e0b; font-size:1rem; margin-bottom:.5rem; }
.expert-testimonial-text { font-size:.875rem; color:#374151; font-style:italic; margin:.5rem 0; border-left:3px solid var(--detail-accent,#5e72e4); padding-left:.75rem; }
.expert-testimonial-author strong { font-size:.875rem; color:#1e293b; display:block; }
.expert-testimonial-role { font-size:.78rem; color:#64748b; }
.expert-case-studies-list, .expert-talks-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:.625rem; }
.expert-case-study-item, .expert-talk-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:.75rem 1rem; }
.expert-case-study-title a { color:#2563eb; text-decoration:none; font-weight:600; }
.expert-case-study-title a:hover { text-decoration:underline; }
.expert-case-study-desc { font-size:.8rem; color:#64748b; margin:.25rem 0 0; }
.expert-talk-item { font-size:.875rem; }
.expert-talk-event { color:#64748b; }
.expert-talk-year { background:#f1f5f9; color:#64748b; font-size:.75rem; padding:.15rem .4rem; border-radius:4px; margin-left:.5rem; }
.expert-talk-video { color:#ef4444; margin-left:.5rem; font-size:.8rem; text-decoration:none; }

/* Sidebar: Kontaktzeiten */
.expert-sidebar-contact-times { font-size:.8rem; color:#64748b; margin:.5rem 0 0; }

/* Sidebar: Profil & Arbeitsweise */
.expert-avail-badge-wrap { margin:.5rem 0; }
.expert-avail-badge-pill { display:inline-block; padding:.25rem .625rem; border-radius:20px; font-size:.78rem; font-weight:600; }
.badge-retainer { background:#d1fae5; color:#065f46; }
.expert-avail-stats { display:flex; flex-wrap:wrap; gap:.5rem; margin:.5rem 0; }
.expert-avail-stat { font-size:.78rem; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; border-radius:20px; padding:.2rem .5rem; }
.expert-languages-block { margin-top:.75rem; }
.expert-languages-badges { display:flex; flex-wrap:wrap; gap:.375rem; margin-top:.375rem; }
.expert-language-badge-v2 { display:inline-block; padding:.2rem .5rem; border-radius:20px; font-size:.78rem; font-weight:600; border:1px solid currentColor; }

/* Sidebar: Konditionen */
.expert-cond-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:.5rem; margin-bottom:.75rem; }
.expert-cond-item { background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:.5rem .625rem; text-align:center; }
.expert-cond-value { display:block; font-size:1rem; font-weight:700; color:#1e293b; }
.expert-cond-label { display:block; font-size:.72rem; color:#64748b; }
.expert-cond-badges { display:flex; flex-wrap:wrap; gap:.375rem; margin-bottom:.5rem; }
.expert-cond-badge { display:inline-block; padding:.2rem .5rem; border-radius:4px; font-size:.78rem; font-weight:600; }
.badge-fixed { background:#d1fae5; color:#065f46; }
.badge-time  { background:#dbeafe; color:#1e40af; }
.expert-cond-sizes { margin-top:.5rem; }
.expert-cond-sizes-label { font-size:.75rem; color:#64748b; margin-bottom:.3rem; }
.expert-size-badge { display:inline-block; margin:.15rem .2rem; padding:.2rem .5rem; background:#f1f5f9; color:#475569; border-radius:4px; font-size:.75rem; }

/* Sidebar: Services */
.expert-services-grid { display:flex; flex-wrap:wrap; gap:.375rem; }
.expert-service-item { display:inline-flex; align-items:center; gap:.35rem; padding:.35rem .625rem; background:#f0fdf4; color:#166534; border:1px solid #bbf7d0; border-radius:6px; font-size:.8rem; font-weight:500; }
.expert-service-item.badge-emergency { background:#fff1f2; color:#9f1239; border-color:#fecdd3; }

/* Sidebar: Netzwerk */
.expert-network-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:.5rem; margin-bottom:.5rem; }
.expert-network-item { display:flex; align-items:center; gap:.5rem; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:.5rem .625rem; }
.expert-network-item.active .expert-network-icon { color:#16a34a; }
.expert-network-item.inactive .expert-network-icon { color:#94a3b8; }
.expert-network-item.highlight { background:#eff6ff; border-color:#bfdbfe; }
.expert-network-icon { font-size:1rem; }
.expert-network-label { font-size:.8rem; color:#475569; }
.expert-partner-networks { margin-top:.5rem; }
.expert-partner-networks-text { font-size:.8rem; color:#475569; margin:.25rem 0 0; }

/* RSS Feed */
.expert-rss-section { padding:1.5rem; }
.expert-rss-card { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:1.5rem; }
.expert-rss-header { display:flex; align-items:center; gap:.75rem; margin-bottom:1rem; }
.expert-rss-icon { font-size:1.5rem; }
.expert-rss-title { font-size:1.1rem; font-weight:700; color:#1e293b; margin:0; }
.expert-rss-list { list-style:none; padding:0; margin:0; }
.expert-rss-item { border-bottom:1px solid #f1f5f9; padding:.625rem 0; }
.expert-rss-item:last-child { border-bottom:none; }
.expert-rss-item a { display:flex; justify-content:space-between; align-items:baseline; gap:1rem; text-decoration:none; }
.expert-rss-item-title { color:#1e293b; font-weight:500; font-size:.9rem; flex:1; }
.expert-rss-item-date { font-size:.78rem; color:#94a3b8; white-space:nowrap; }
.expert-rss-item a:hover .expert-rss-item-title { color:var(--detail-accent,#5e72e4); }
.expert-rss-footer { margin-top:.75rem; text-align:center; }
.expert-rss-all-btn { display:inline-block; padding:.45rem 1rem; background:#eff6ff; color:#2563eb; border:1px solid #bfdbfe; border-radius:6px; font-size:.8rem; font-weight:600; text-decoration:none; }
.expert-rss-all-btn:hover { background:#dbeafe; }
.expert-rss-empty { color:#94a3b8; font-size:.875rem; text-align:center; padding:1rem; }

/* Social Link Icons: neue Plattformen */
.social-gitlab { background:#fc6d26; }
.social-so     { background:#f48024; }
.social-youtube{ background:#ff0000; color:#fff!important; }
.social-rss    { background:#ee802f; color:#fff!important; }
</style>