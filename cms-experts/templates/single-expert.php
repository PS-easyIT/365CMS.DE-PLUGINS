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
$detail_header_bg   = $settings['design_detail_header_bg']       ?? '#f8fafc';
$detail_header_col  = $settings['design_detail_header_color']    ?? '#1e293b';
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
// Avatar Gradient + Initialen
$_ex_first  = $expert->first_name ?? '';
$_ex_last   = $expert->last_name  ?? '';
$_ex_inits  = strtoupper(mb_substr($_ex_first, 0, 1) . mb_substr($_ex_last, 0, 1));
$_ex_pals   = [['#5e72e4','#8965e0'],['#0891b2','#06b6d4'],['#16a34a','#22c55e'],['#7c3aed','#a855f7'],['#d97706','#f59e0b']];
$_ex_cp     = $_ex_pals[abs(crc32($full_name)) % count($_ex_pals)];
$_ex_agrad  = "linear-gradient(135deg,{$_ex_cp[0]},{$_ex_cp[1]})";
$_base_url  = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
?>

<div class="ex-v2">

  <nav class="ex-bc">
    <a href="<?= $_base_url ?>/experts">← Experten</a>
    <span class="ex-bc__sep">/</span>
    <span class="ex-bc__cur"><?= $sec->escape(mb_strimwidth($full_name, 0, 60, '…')) ?></span>
  </nav>

  <header class="ex-hero">
    <div class="ex-hero__inner">
      <div class="ex-hero__badges">
        <span class="ex-hero__badge ex-hero__badge--avail-<?= $sec->escape($avail) ?>"><?= $sec->escape($avail_info['label']) ?></span>
        <?php if ($partner_status === 'sponsor'):    ?><span class="ex-hero__badge ex-hero__badge--partner">★ Sponsor</span><?php endif; ?>
        <?php if ($partner_status === 'top_partner'):?><span class="ex-hero__badge ex-hero__badge--partner">◆ Top-Partner</span><?php endif; ?>
        <?php if ($partner_status === 'partner'):    ?><span class="ex-hero__badge ex-hero__badge--partner">✓ Partner</span><?php endif; ?>
        <?php if ($is_mvp):       ?><span class="ex-hero__badge ex-hero__badge--mvp">⚡ MVP</span><?php endif; ?>
        <?php if ($is_certified): ?><span class="ex-hero__badge ex-hero__badge--cert">✅ Zertifiziert</span><?php endif; ?>
        <?php if ($is_premium):   ?><span class="ex-hero__badge ex-hero__badge--premium">⭐ Premium</span><?php endif; ?>
      </div>
      <?php if ($photo): ?>
        <div class="ex-hero__av"><img src="<?= $sec->escape($photo) ?>" alt="<?= $sec->escape($full_name) ?>"></div>
      <?php else: ?>
        <div class="ex-hero__av" style="background:<?= $_ex_agrad ?>;"><?= htmlspecialchars($_ex_inits ?: '?') ?></div>
      <?php endif; ?>
      <div class="ex-hero__meta">
        <?php if ($custom_award): ?><div class="ex-award">🏆 <?= $sec->escape($custom_award) ?></div><?php endif; ?>
        <h1 class="ex-hero__name"><?= $sec->escape($full_name) ?></h1>
        <?php if ($motto): ?><p class="ex-hero__motto">"<?= $sec->escape($motto) ?>"</p><?php endif; ?>
        <?php if ($position): ?><p class="ex-hero__pos"><?= $sec->escape($position) ?></p><?php endif; ?>
        <?php if ($company): ?>
          <p class="ex-hero__co">🏢
            <?php if (!empty($expert->company_id)): ?>
              <a href="<?= $_base_url ?>/companies/<?= (int)$expert->company_id ?>"><?= $sec->escape($company) ?></a>
            <?php elseif (!empty($social['website'])): ?>
              <a href="<?= $sec->escape($social['website']) ?>" target="_blank" rel="noopener"><?= $sec->escape($company) ?></a>
            <?php else: ?>
              <?= $sec->escape($company) ?>
            <?php endif; ?>
          </p>
        <?php endif; ?>
        <div class="ex-hero__chips">
          <?php if (!empty($expert->location_city)): ?>
            <span class="ex-hero__chip">📍 <?= $sec->escape($expert->location_city) ?><?= !empty($expert->location_country) ? ', '.$sec->escape($expert->location_country) : '' ?></span>
          <?php endif; ?>
          <?php if (!empty($expert->experience_years)): ?>
            <span class="ex-hero__chip">💼 <?= (int)$expert->experience_years ?> Jahre Erfahrung</span>
          <?php endif; ?>
          <?php if ($remote_work): ?>
            <?php $rm_chip = ['yes'=>'Remote möglich','only'=>'Nur Remote','no'=>'Vor Ort','partial'=>'Hybrid','full'=>'Vollständig Remote','preferred'=>'Remote bevorzugt']; ?>
            <span class="ex-hero__chip">🏠 <?= $sec->escape($rm_chip[$remote_work] ?? ucfirst($remote_work)) ?></span>
          <?php endif; ?>
          <?php if ($work_type): ?>
            <?php $wt_map = ['freelancer'=>'Freelancer','employed'=>'Angestellt','agency'=>'Agentur','contractor'=>'Contractor']; ?>
            <span class="ex-hero__chip">🎯 <?= $sec->escape($wt_map[$work_type] ?? ucfirst($work_type)) ?></span>
          <?php endif; ?>
          <?php if ($total_projects_cnt): ?><span class="ex-hero__chip">📁 <?= (int)$total_projects_cnt ?>+ Projekte</span><?php endif; ?>
          <?php if ($timezone): ?><span class="ex-hero__chip">🕐 <?= $sec->escape($timezone) ?></span><?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <div class="ex-body">
    <main class="ex-main">

      <?php if (!empty($expert->biography) && trim((string)$expert->biography) !== ''): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">👤 Über mich</h2>
          <div class="ex-wysiwyg-content">
            <?php
            $bio = (string)$expert->biography;
            echo (bool)preg_match('/<(p|ul|ol|h[1-6]|blockquote|div|br)[\s>]/i', $bio) ? $bio : nl2br(htmlspecialchars($bio));
            ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($specializations)): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">🎯 Spezialisierungen</h2>
          <div class="ex-pills">
            <?php foreach ($specializations as $sp): ?>
              <span class="ex-pill ex-pill--spec"><?= $sec->escape($sp->name ?? '') ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php
      $skill_section_cfg = [
          'general' => ['🌟 Allgemeine Skills', ''],
          'tech'    => ['⚙️ Technische Skills', 'ex-pill--tech'],
          'soft'    => ['💬 Soft Skills',        'ex-pill--soft'],
      ];
      $has_any_skill = !empty(array_filter($skills_by_type));
      if ($has_any_skill):
          $skill_level_map = ['beginner'=>15,'basic'=>30,'intermediate'=>55,'advanced'=>80,'expert'=>95,'master'=>100];
      ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">🛠️ Skills & Kompetenzen</h2>
          <?php foreach ($skill_section_cfg as $type => [$label, $cls]):
            if (empty($skills_by_type[$type])) continue;
          ?>
            <h4 class="ex-sub-heading"><?= $label ?></h4>
            <div class="ex-skills-grid">
              <?php foreach ($skills_by_type[$type] as $sk):
                $lvl_pct = $skill_level_map[$sk->skill_level ?? ''] ?? 0;
              ?>
                <div class="ex-skill-item">
                  <div class="ex-skill-item__top">
                    <span class="ex-skill-item__name"><?= $sec->escape($sk->skill_name ?? '') ?></span>
                    <?php if ($lvl_pct): ?><span class="ex-skill-item__lvl"><?= $sec->escape(ucfirst($sk->skill_level)) ?></span><?php endif; ?>
                  </div>
                  <?php if ($lvl_pct): ?>
                    <div class="ex-skill-bar"><div class="ex-skill-bar__fill" style="width:<?= $lvl_pct ?>%;"></div></div>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php $has_tech = !empty($prog_languages) || !empty($expert_frameworks) || !empty($expert_databases) || !empty($cloud_platforms) || !empty($tools_preferred) || !empty($industry_experience); ?>
      <?php if ($has_tech): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">💻 Technische Expertise</h2>
          <?php
          $tech_sections = [
              ['🖥️ Sprachen',      $prog_languages],
              ['📦 Frameworks',    $expert_frameworks],
              ['🗃️ Datenbanken',   $expert_databases],
              ['☁️ Cloud',         $cloud_platforms],
              ['🔧 Tools',         $tools_preferred],
              ['🏭 Branchen',      $industry_experience],
          ];
          foreach ($tech_sections as [$lbl, $items]):
            if (empty($items)) continue; ?>
            <h4 class="ex-sub-heading"><?= $lbl ?></h4>
            <div class="ex-pills ex-pills--mb">
              <?php foreach ((array)$items as $it): ?><span class="ex-pill ex-pill--tech"><?= $sec->escape(is_string($it) ? $it : ($it->name ?? (string)$it)) ?></span><?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($projects)): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">📁 Projekte <span class="ex-count-badge"><?= count($projects) ?></span></h2>
          <?php foreach ($projects as $pj):
            $p_start = (!empty($pj->project_start) && $pj->project_start !== '0000-00-00') ? date('m/Y', strtotime($pj->project_start)) : '';
            $p_end   = (!empty($pj->project_end)   && $pj->project_end   !== '0000-00-00') ? date('m/Y', strtotime($pj->project_end))   : 'aktuell';
            $p_techs = is_string($pj->technologies ?? '') ? array_filter(array_map('trim', explode(',', $pj->technologies ?? ''))) : [];
          ?>
            <div class="ex-proj-item">
              <div class="ex-proj-item__name"><?= $sec->escape($pj->project_name ?? '') ?></div>
              <div class="ex-proj-item__meta">
                <?= $sec->escape($pj->project_role ?? '') ?><?= (!empty($pj->project_role) && ($p_start || $p_end)) ? ' · ' : '' ?><?= $p_start ? $p_start . ' – ' . $p_end : '' ?>
                <?php if (!empty($pj->project_url)): ?> · <a href="<?= $sec->escape($pj->project_url) ?>" target="_blank" rel="noopener">↗</a><?php endif; ?>
              </div>
              <?php if (!empty($pj->project_description)): ?>
                <div class="ex-proj-item__desc"><?= nl2br($sec->escape($pj->project_description)) ?></div>
              <?php endif; ?>
              <?php if ($p_techs): ?>
                <div class="ex-proj-item__tags">
                  <?php foreach ($p_techs as $t): ?><span class="ex-proj-item__tag"><?= $sec->escape($t) ?></span><?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($education)): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">🎓 Ausbildung</h2>
          <?php foreach ($education as $edu):
            $e_from = !empty($edu->start_year) ? (int)$edu->start_year : null;
            $e_to   = !empty($edu->end_year)   ? (int)$edu->end_year   : null;
          ?>
            <div class="ex-edu-item">
              <div class="ex-edu-item__deg"><?= $sec->escape($edu->degree ?? '') ?><?= !empty($edu->field_of_study) ? ' – ' . $sec->escape($edu->field_of_study) : '' ?></div>
              <div class="ex-edu-item__inst"><?= $sec->escape($edu->institution ?? '') ?></div>
              <?php if ($e_from || $e_to): ?><div class="ex-edu-item__meta"><?= $e_from ?? '?' ?> – <?= $e_to ?? 'heute' ?></div><?php endif; ?>
              <?php if (!empty($edu->description)): ?><div class="ex-edu-desc"><?= $sec->escape($edu->description) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($certifications)): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">📜 Zertifizierungen</h2>
          <?php foreach ($certifications as $cert): ?>
            <div class="ex-cert-item">
              <div>
                <div class="ex-cert-item__name"><?= $sec->escape($cert->cert_name ?? '') ?></div>
                <?php if (!empty($cert->cert_issuer)): ?><div class="ex-cert-item__meta"><?= $sec->escape($cert->cert_issuer) ?></div><?php endif; ?>
              </div>
              <?php if (!empty($cert->cert_date)): ?>
                <div class="ex-cert-item__meta"><?= date('Y', strtotime($cert->cert_date)) ?><?= (!empty($cert->cert_expiry) && $cert->cert_expiry !== '0000-00-00') ? ' – ' . date('Y', strtotime($cert->cert_expiry)) : '' ?></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($career_stations_data)): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">📈 Karrierestationen</h2>
          <?php foreach ($career_stations_data as $cs):
            $cs_from = !empty($cs['from']) ? $cs['from'] : null;
            $cs_to   = !empty($cs['to'])   ? $cs['to']   : null;
          ?>
            <div class="ex-career-item">
              <div class="ex-career-item__role"><?= $sec->escape($cs['role'] ?? '') ?></div>
              <?php if (!empty($cs['company'])): ?><div class="ex-career-item__co"><?= $sec->escape($cs['company']) ?></div><?php endif; ?>
              <?php if ($cs_from || $cs_to): ?><div class="ex-career-item__meta"><?= htmlspecialchars(trim(($cs_from ?? '').' – '.($cs_to ?? 'heute'))) ?><?= !empty($cs['location']) ? ' · '.htmlspecialchars($cs['location']) : '' ?></div><?php endif; ?>
              <?php if (!empty($cs['description'])): ?><div class="ex-career-item__desc"><?= $sec->escape($cs['description']) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php $has_refs = !empty($testimonials_data) || !empty($case_studies_data) || !empty($conference_talks); ?>
      <?php if ($has_refs): ?>
        <div class="ex-sec">
          <h2 class="ex-sec__title">🏅 Referenzen & Auftritte</h2>
          <?php if (!empty($testimonials_data)): ?>
            <h4 class="ex-sub-heading">💬 Testimonials</h4>
            <?php foreach ($testimonials_data as $t): ?>
              <div class="ex-testi-item">
                <div class="ex-testi-item__text">"<?= $sec->escape($t['text'] ?? $t['quote'] ?? '') ?>"</div>
                <div class="ex-testi-item__by">— <?= $sec->escape(trim(($t['name'] ?? '') . (!empty($t['company']) ? ', '.$t['company'] : ''))) ?></div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
          <?php if (!empty($case_studies_data)): ?>
            <h4 class="ex-sub-heading">📊 Case Studies</h4>
            <?php foreach ($case_studies_data as $cs): ?>
              <div class="ex-proj-item">
                <div class="ex-proj-item__name"><?= $sec->escape($cs['title'] ?? '') ?></div>
                <?php if (!empty($cs['description'])): ?><div class="ex-proj-item__desc"><?= $sec->escape($cs['description']) ?></div><?php endif; ?>
                <?php if (!empty($cs['result'])): ?><div class="ex-cs-result">✅ <?= $sec->escape($cs['result']) ?></div><?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
          <?php if (!empty($conference_talks)): ?>
            <h4 class="ex-sub-heading">🎤 Konferenz-Vorträge</h4>
            <?php foreach ($conference_talks as $ct): ?>
              <div class="ex-conf-item">
                <div class="ex-conf-item__title"><?= $sec->escape($ct['title'] ?? $ct['talk'] ?? '') ?></div>
                <div class="ex-conf-item__meta">
                  <?= $sec->escape($ct['event'] ?? '') ?><?= (!empty($ct['event']) && !empty($ct['year'])) ? ' · ' : '' ?><?= $sec->escape($ct['year'] ?? '') ?><?= (!empty($ct['location'])) ? ' · '.htmlspecialchars($ct['location']) : '' ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </main>

    <aside class="ex-aside">

      <?php if (!empty($social['website']) || !empty($social['linkedin']) || !empty($social['xing'])): ?>
        <div class="ex-sc">
          <h3 class="ex-sc__title">📬 Kontakt</h3>
          <?php if (!empty($social['website'])): ?>
            <a href="<?= $sec->escape($social['website']) ?>" target="_blank" rel="noopener" class="ex-btn">🌐 Website besuchen</a>
          <?php endif; ?>
          <?php if (!empty($social['linkedin'])): ?>
            <a href="<?= $sec->escape($social['linkedin']) ?>" target="_blank" rel="noopener" class="ex-btn ex-btn--ghost">🔗 LinkedIn-Profil</a>
          <?php endif; ?>
          <?php if (!empty($social['xing'])): ?>
            <a href="<?= $sec->escape($social['xing']) ?>" target="_blank" rel="noopener" class="ex-btn ex-btn--ghost">✖ XING-Profil</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php
      $ex_facts = [];
      $rm_map = ['yes'=>'Ja','only'=>'Nur Remote','no'=>'Nein','partial'=>'Hybrid','full'=>'Vollständig','preferred'=>'Bevorzugt'];
      $wt_map2 = ['freelancer'=>'Freelancer','employed'=>'Angestellt','agency'=>'Agentur','contractor'=>'Contractor'];
      if ($avail):        $ex_facts[] = ['🟢','Status',        $sec->escape($avail_info['label'])]; endif;
      if ($next_avail_date): $ex_facts[] = ['📅','Verfügbar ab', date('d.m.Y', strtotime($next_avail_date))]; endif;
      if (!empty($expert->experience_years)): $ex_facts[] = ['💼','Erfahrung',    (int)$expert->experience_years.' Jahre']; endif;
      if ($languages):    $ex_facts[] = ['🗣️','Sprachen',      $sec->escape($languages)]; endif;
      if ($work_type):    $ex_facts[] = ['🎯','Work-Typ',      $sec->escape($wt_map2[$work_type] ?? ucfirst($work_type))]; endif;
      if ($remote_work):  $ex_facts[] = ['🏠','Remote',        $sec->escape($rm_map[$remote_work] ?? ucfirst($remote_work))]; endif;
      if ($travel_willingness): $ex_facts[] = ['✈️','Reise',  $sec->escape($travel_willingness)]; endif;
      if ($notice_period): $ex_facts[] = ['⏱️','Verfügbar',   $sec->escape($notice_period)]; endif;
      if ($timezone):     $ex_facts[] = ['🕐','Zeitzone',      $sec->escape($timezone)]; endif;
      if ($ex_facts): ?>
        <div class="ex-sc">
          <h3 class="ex-sc__title">⚙️ Verfügbarkeit & Arbeitsweise</h3>
          <div class="ex-info-rows">
            <?php foreach ($ex_facts as [$ic,$lbl,$val]): ?>
              <div class="ex-info-row">
                <span class="ex-info-row__lbl"><?= $ic ?> <?= $lbl ?></span>
                <span class="ex-info-row__val"><?= $val ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($hourly_rate || $daily_rate): ?>
        <div class="ex-sc">
          <h3 class="ex-sc__title">💶 Honorar</h3>
          <div class="ex-info-rows">
            <?php if ($hourly_rate): ?><div class="ex-info-row"><span class="ex-info-row__lbl">⏱ Stundensatz</span><span class="ex-info-row__val"><?= number_format($hourly_rate, 0, ',', '.') ?> €</span></div><?php endif; ?>
            <?php if ($daily_rate):  ?><div class="ex-info-row"><span class="ex-info-row__lbl">📅 Tagessatz</span><span class="ex-info-row__val"><?= number_format($daily_rate, 0, ',', '.') ?> €</span></div><?php endif; ?>
            <?php if ($weekly_hours): ?><div class="ex-info-row"><span class="ex-info-row__lbl">🕐 Std/Woche</span><span class="ex-info-row__val"><?= (int)$weekly_hours ?> h</span></div><?php endif; ?>
            <?php if ($min_proj_dur): ?><div class="ex-info-row"><span class="ex-info-row__lbl">📌 Min. Dauer</span><span class="ex-info-row__val"><?= $sec->escape($min_proj_dur) ?></span></div><?php endif; ?>
            <?php if ($payment_terms): ?><div class="ex-info-row"><span class="ex-info-row__lbl">📃 Zahlung</span><span class="ex-info-row__val"><?= $sec->escape($payment_terms) ?></span></div><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php $has_svc = $svc_consulting||$svc_impl||$svc_training||$svc_support||$svc_audit; ?>
      <?php if ($has_svc || $emergency_support || $workshop_offerings): ?>
        <div class="ex-sc">
          <h3 class="ex-sc__title">🔧 Services</h3>
          <div class="ex-svc-check">
            <?php if ($svc_consulting): ?><div class="ex-svc-row">Beratung</div><?php endif; ?>
            <?php if ($svc_impl):       ?><div class="ex-svc-row">Implementierung</div><?php endif; ?>
            <?php if ($svc_training):   ?><div class="ex-svc-row">Training</div><?php endif; ?>
            <?php if ($svc_support):    ?><div class="ex-svc-row">Support</div><?php endif; ?>
            <?php if ($svc_audit):      ?><div class="ex-svc-row">Audit</div><?php endif; ?>
            <?php if ($emergency_support): ?><div class="ex-svc-row">24/7 Notfall-Support</div><?php endif; ?>
            <?php if ($workshop_offerings): ?><div class="ex-svc-row">Workshops</div><?php endif; ?>
            <?php if ($subcontractors): ?><div class="ex-svc-row">Subunternehmer möglich</div><?php endif; ?>
            <?php if ($fixed_price):    ?><div class="ex-svc-row">Festpreisprojekte</div><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($partner_networks || $team_expansion || $max_team_size || $team_size_led): ?>
        <div class="ex-sc">
          <h3 class="ex-sc__title">🤝 Netzwerk & Team</h3>
          <div class="ex-info-rows">
            <?php if ($team_expansion): ?><div class="ex-info-row"><span class="ex-info-row__lbl">👥 Team-Ausbau</span><span class="ex-info-row__val">Möglich</span></div><?php endif; ?>
            <?php if ($max_team_size):  ?><div class="ex-info-row"><span class="ex-info-row__lbl">👥 Max. Team</span><span class="ex-info-row__val"><?= (int)$max_team_size ?> Pers.</span></div><?php endif; ?>
            <?php if ($team_size_led):  ?><div class="ex-info-row"><span class="ex-info-row__lbl">🎖 Geführt</span><span class="ex-info-row__val"><?= (int)$team_size_led ?> Pers.</span></div><?php endif; ?>
            <?php if ($partner_networks): ?><div class="ex-info-row"><span class="ex-info-row__lbl">🔗 Netzwerk</span><span class="ex-info-row__val"><?= $sec->escape($partner_networks) ?></span></div><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ($has_social): ?>
        <div class="ex-sc">
          <h3 class="ex-sc__title">🔗 Profile & Social</h3>
          <div class="ex-social-row">
            <?php foreach (['linkedin','xing','github','twitter','website','gitlab','stackoverflow','youtube','blog_rss'] as $sn):
              if (empty($social[$sn])) continue;
              $sn_labels = ['linkedin'=>'LinkedIn','xing'=>'XING','github'=>'GitHub','twitter'=>'X/Twitter','website'=>'Website','gitlab'=>'GitLab','stackoverflow'=>'StackOverflow','youtube'=>'YouTube','blog_rss'=>'RSS'];
            ?><a href="<?= $sec->escape($social[$sn]) ?>" target="_blank" rel="noopener" class="ex-si" title="<?= $sn_labels[$sn] ?? $sn ?>"><?= $sn_labels[$sn] ?? $sn ?></a><?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="ex-sc">
        <a href="<?= $_base_url ?>/experts" class="ex-btn ex-btn--ghost ex-btn--no-margin">← Zur Experten-Übersicht</a>
      </div>

    </aside>
  </div>
</div>
