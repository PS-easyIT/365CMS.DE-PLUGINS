<?php
/**
 * Speaker Card Template – analog expert-card (fixed 375 px, Lila-Schema)
 *
 * Erwartet im Scope:
 *   $speaker  – object (DB-Spalten)
 *   $settings – array  (Design-Settings)
 *   $topics   – array  (optional: vorgeladene Topics dieses Speakers)
 *
 * @package CMS_Speakers
 */

declare(strict_types=1);

if (!isset($speaker)) {
    return;
}

$s = $speaker;

// ── Basis-Daten ────────────────────────────────────────────
$id         = (int)$s->id;
$first      = $s->first_name ?? '';
$last       = $s->last_name  ?? '';
$full_name  = htmlspecialchars(trim("$first $last"));
$position   = htmlspecialchars($s->position ?? '');
$company    = htmlspecialchars($s->company_linked_name ?? $s->company ?? '');
$city       = htmlspecialchars($s->location_city ?? '');
$photo      = $s->photo_url ?? '';
$linkedin   = $s->linkedin   ?? '';
$xing       = $s->xing       ?? '';
$twitter    = $s->twitter    ?? '';
$website    = $s->website    ?? '';
$email      = $s->email      ?? '';
$github     = $s->github     ?? '';
$gitlab     = $s->gitlab     ?? '';
$is_featured = !empty($s->is_featured);
$is_verified = !empty($s->is_verified);
$avail       = $s->availability ?? 'available';
$fee_min     = $s->speaking_fee_min ?? '';
$fee_max     = $s->speaking_fee_max ?? '';
$travel      = $s->travel_radius    ?? 'national';

// ── URL via Slug ───────────────────────────────────────────
$base_url    = defined('SITE_URL') ? rtrim(SITE_URL, '/') : '';
$slug        = CMS_Speakers_Database::generate_slug($s);
$speaker_url = $base_url . '/speakers/' . $slug;

// ── Topics (vorgeladen oder aus übergebenen $topics) ────────
$card_topics = [];
if (!empty($topics) && is_array($topics)) {
    foreach ($topics as $t) {
        $card_topics[] = is_object($t) ? ($t->topic_name ?? '') : (is_array($t) ? ($t['topic_name'] ?? '') : (string)$t);
    }
} elseif (!empty($s->_topics) && is_array($s->_topics)) {
    $card_topics = $s->_topics;
}
$card_topics = array_values(array_filter($card_topics));

// ── Formate ────────────────────────────────────────────────
$formats_raw = $s->formats ?? '';
$formats = [];
if (is_string($formats_raw) && $formats_raw) {
    $decoded = json_decode($formats_raw, true);
    $formats = is_array($decoded) ? $decoded : array_filter(array_map('trim', explode(',', $formats_raw)));
}

// ── Skills ────────────────────────────────────────────────
$skills_raw = $s->skills ?? '';
$skills = [];
if (is_string($skills_raw) && $skills_raw) {
    $decoded = json_decode($skills_raw, true);
    $skills = is_array($decoded) ? $decoded : [];
}
// Lesbarer Label-Map (Schlüssel → Anzeigename)
$skill_labels = [
    // Tech (Themen)
    'ai_ml'              => 'KI / ML',
    'data_science'       => 'Data Science',
    'cloud'              => 'Cloud',
    'cybersecurity'      => 'Cybersecurity',
    'blockchain'         => 'Blockchain',
    'iot'                => 'IoT',
    'automation'         => 'Automation',
    'devops'             => 'DevOps',
    'digital_transform'  => 'Digital Transformation',
    'software_arch'      => 'Software-Architektur',
    'low_code'           => 'Low-Code',
    'metaverse_ar_vr'    => 'AR / VR',
    'quantum'            => 'Quantum Computing',
    'open_source'        => 'Open Source',
    'api_integration'    => 'API & Integration',
    'data_engineering'   => 'Data Engineering',
    // Programmiersprachen & Frameworks
    'lang_php'           => 'PHP',
    'lang_python'        => 'Python',
    'lang_javascript'    => 'JavaScript / TS',
    'lang_java'          => 'Java',
    'lang_go'            => 'Go',
    'lang_rust'          => 'Rust',
    'lang_csharp'        => 'C# / .NET',
    'lang_cpp'           => 'C / C++',
    'lang_swift'         => 'Swift / Kotlin',
    'lang_r'             => 'R',
    'fw_react'           => 'React / Next.js',
    'fw_vue'             => 'Vue.js',
    'fw_angular'         => 'Angular',
    'fw_nodejs'          => 'Node.js',
    'fw_laravel'         => 'Laravel',
    'fw_django'          => 'Django',
    'fw_spring'          => 'Spring Boot',
    'fw_flutter'         => 'Flutter',
    // Infrastruktur & Tools
    'infra_docker'       => 'Docker',
    'infra_k8s'          => 'Kubernetes',
    'infra_terraform'    => 'Terraform',
    'infra_ansible'      => 'Ansible',
    'infra_ci_cd'        => 'CI/CD',
    'infra_git'          => 'Git',
    'db_sql'             => 'SQL',
    'db_nosql'           => 'NoSQL',
    'db_search'          => 'Elasticsearch',
    'db_dw'              => 'Data Warehouse',
    'cloud_aws'          => 'AWS',
    'cloud_azure'        => 'Azure',
    'cloud_gcp'          => 'GCP',
    'ml_ops'             => 'MLOps',
    'observability'      => 'Observability',
    'security_tools'     => 'Security Tools',
    // Microsoft 365 & Enterprise
    'ms_exchange'        => 'Exchange',
    'ms_teams'           => 'MS Teams',
    'ms_sharepoint'      => 'SharePoint',
    'ms_m365'            => 'Microsoft 365',
    'ms_active_dir'      => 'Active Directory',
    'ms_intune'          => 'Intune',
    'ms_power_platform'  => 'Power Platform',
    'ms_power_bi'        => 'Power BI',
    'ms_dynamics'        => 'Dynamics 365',
    'ms_copilot'         => 'MS Copilot',
    'ms_sql_server'      => 'SQL Server',
    'ms_defender'        => 'MS Defender',
    'ms_onedrive'        => 'OneDrive',
    'ms_azure_devops'    => 'Azure DevOps',
    'ms_viva'            => 'Microsoft Viva',
    // Enterprise Infra & Virtualisierung
    'vmware_vsphere'     => 'VMware vSphere',
    'vmware_nsx'         => 'VMware NSX',
    'vmware_horizon'     => 'Horizon VDI',
    'nutanix'            => 'Nutanix HCI',
    'nutanix_nc2'        => 'Nutanix NC2',
    'citrix'             => 'Citrix DaaS',
    'hyper_v'            => 'Hyper-V',
    'proxmox'            => 'Proxmox',
    'veeam'              => 'Veeam',
    'zerto'              => 'Zerto',
    'netapp'             => 'NetApp',
    'dell_emc'           => 'Dell EMC',
    'hpe'                => 'HPE',
    'cisco_net'          => 'Cisco Networking',
    'cisco_ucs'          => 'Cisco UCS',
    'palo_alto'          => 'Palo Alto',
    'fortinet'           => 'Fortinet',
    'f5'                 => 'F5 / NGINX',
    'juniper'            => 'Juniper',
    'aruba'              => 'Aruba',
    'sap'                => 'SAP',
    'oracle_db'          => 'Oracle DB',
    'ibm_mainframe'      => 'IBM Z / AIX',
    'servicenow'         => 'ServiceNow',
    'splunk'             => 'Splunk',
    'crowdstrike'        => 'CrowdStrike',
    'zscaler'            => 'Zscaler',
    'leadership'         => 'Leadership',
    'change_mgmt'        => 'Change Management',
    'innovation'         => 'Innovation',
    'entrepreneurship'   => 'Entrepreneurship',
    'digital_marketing'  => 'Digital Marketing',
    'sales'              => 'Sales & BD',
    'agile_scrum'        => 'Agile / Scrum',
    'new_work'           => 'New Work',
    'hr_people'          => 'HR & People',
    'finance_fintech'    => 'FinTech',
    'esg'                => 'ESG',
    'strategy'           => 'Strategie',
    'public_speaking'    => 'Public Speaking',
    'storytelling'       => 'Storytelling',
    'coaching'           => 'Coaching',
    'moderation'         => 'Moderation',
    'train_trainer'      => 'Train-the-Trainer',
    'intercultural'      => 'Interkulturell',
    'crisis_comm'        => 'Krisenkommunikation',
    'media_training'     => 'Medientraining',
    'healthcare'         => 'Healthcare',
    'edu_elearning'      => 'E-Learning',
    'real_estate'        => 'Immobilien',
    'energy_climate'     => 'Energie & Klima',
    'automotive'         => 'Automotive',
    'logistics'          => 'Logistik',
    'legal_regtech'      => 'Legal Tech',
    'ngo_social'         => 'Social Impact',
    'media_entertainment'=> 'Medien',
    'retail_ecommerce'   => 'E-Commerce',
];
$fmt_labels = [
    'keynote'    => '🎤 Keynote',
    'workshop'   => '🛠️ Workshop',
    'panel'      => '💬 Panel',
    'moderation' => '🎙️ Moderation',
    'training'   => '📚 Training',
    'consulting' => '🤝 Beratung',
    'interview'  => '🎥 Interview',
    'webinar'    => '💻 Webinar',
];

// ── Initialen für Avatar-Platzhalter ───────────────────────
$initials = mb_strtoupper(mb_substr($first, 0, 1) . mb_substr($last, 0, 1));
$pcolors  = [['#8b5cf6','#a855f7'],['#7c3aed','#8b5cf6'],['#a855f7','#c084fc'],['#6d28d9','#8b5cf6'],['#9333ea','#a855f7']];
$cp       = $pcolors[abs(crc32($full_name)) % count($pcolors)];
$av_grad  = "linear-gradient(135deg,{$cp[0]},{$cp[1]})";

// ── Settings-Defaults ──────────────────────────────────────
$set = array_merge([
    'design_primary_color'     => '#8b5cf6',
    'design_accent_color'      => '#7c3aed',
    'design_card_bg'           => '#faf5ff',
    'design_border_radius'     => '12',
    'design_cta_label'         => 'Profil ansehen',
    'design_show_availability' => '1',
    'design_show_mvp_badge'    => '1',
    'design_show_formats'      => '1',
    'design_show_topics'       => '1',
], $settings ?? []);

$show_avail   = $set['design_show_availability'] !== '0';
$show_mvp     = $set['design_show_mvp_badge']    !== '0';
$show_formats = $set['design_show_formats']      !== '0';
$show_topics  = $set['design_show_topics']       !== '0';
$radius       = (int)$set['design_border_radius'];
$cta          = htmlspecialchars($set['design_cta_label'] ?: 'Profil ansehen');

// ── Labels ─────────────────────────────────────────────────
$avail_labels  = ['available' => 'Verfügbar', 'limited' => 'Begrenzt', 'booked' => 'Ausgebucht'];
$avail_classes = ['available' => 'sp-avail--available', 'limited' => 'sp-avail--limited', 'booked' => 'sp-avail--booked'];
$avail_label   = $avail_labels[$avail]  ?? 'Verfügbar';
$avail_cls     = $avail_classes[$avail] ?? 'sp-avail--available';
$travel_labels = ['local'=>'📍 Lokal','regional'=>'🗺️ Regional','national'=>'🇩🇪 DACH','international'=>'🌍 International','worldwide'=>'🌐 Weltweit'];
$travel_label  = $travel_labels[$travel] ?? $travel;
?>
<?php
// Abgeleitete Farben (Fallback, falls nicht aus Parent-Template im Scope)
if (!function_exists('sp_hex_mix')) {
    function sp_hex_mix(string $hex, float $frac, bool $to_white = true): string {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) { $hex=$hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2]; }
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) { return '#' . $hex; }
        $target = $to_white ? 255 : 0;
        return sprintf('#%02x%02x%02x',
            max(0, min(255, (int)(hexdec(substr($hex,0,2)) + $frac * ($target - hexdec(substr($hex,0,2)))))),
            max(0, min(255, (int)(hexdec(substr($hex,2,2)) + $frac * ($target - hexdec(substr($hex,2,2)))))),
            max(0, min(255, (int)(hexdec(substr($hex,4,2)) + $frac * ($target - hexdec(substr($hex,4,2))))))
        );
    }
}
$_card_top = $card_top_bg ?? sp_hex_mix($set['design_accent_color'] ?? '#7c3aed', 0.76, true);
$_border   = $border_color ?? sp_hex_mix($set['design_accent_color'] ?? '#7c3aed', 0.55, true);
?>
<article class="sp-card" style="--sp-primary:<?= htmlspecialchars($set['design_primary_color']) ?>;--sp-accent:<?= htmlspecialchars($set['design_accent_color']) ?>;--sp-card-bg:<?= htmlspecialchars($set['design_card_bg']) ?>;--sp-card-top-bg:<?= htmlspecialchars($_card_top) ?>;--sp-border:<?= htmlspecialchars($_border) ?>;--sp-radius:<?= $radius ?>px;">

  <!-- Badges oben links -->
  <div class="sp-card-badges">
    <?php if ($show_mvp && $is_featured): ?><span class="sp-badge sp-badge-featured">⭐ MVP</span><?php endif; ?>
    <?php if ($is_verified): ?><span class="sp-badge sp-badge-verified">✔ Verifiziert</span><?php endif; ?>
  </div>

  <!-- Verfügbarkeit oben rechts -->
  <?php if ($show_avail): ?>
    <span class="sp-avail-badge <?= $avail_cls ?>"><?= htmlspecialchars($avail_label) ?></span>
  <?php endif; ?>

  <!-- Header: Avatar + Identität -->
  <header class="sp-card-top">
    <div class="sp-card-avatar">
      <?php if ($photo): ?>
        <img src="<?= htmlspecialchars($photo) ?>" alt="<?= $full_name ?>" loading="lazy">
      <?php else: ?>
        <div class="sp-av-placeholder" style="background:<?= $av_grad ?>;"><?= htmlspecialchars($initials ?: '🎤') ?></div>
      <?php endif; ?>
    </div>
    <div class="sp-card-identity">
      <h3 class="sp-card-name">
        <a href="<?= htmlspecialchars($speaker_url) ?>"><?= $full_name ?: 'Speaker' ?></a>
      </h3>
      <?php if ($position): ?><p class="sp-card-pos"><?= $position ?></p><?php endif; ?>
      <?php if ($company): ?>
        <div class="sp-card-co">🏢
          <?php if (!empty($s->company_id)): ?>
            <a href="<?= $base_url ?>/companies/<?= (int)$s->company_id ?>"><?= $company ?></a>
          <?php elseif ($website): ?>
            <a href="<?= htmlspecialchars($website) ?>" target="_blank" rel="noopener noreferrer"><?= $company ?></a>
          <?php else: ?>
            <?= $company ?>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <!-- Expertise-Banner -->
  <div class="sp-card-exp-banner">
    <?php if ($show_topics && !empty($card_topics)): ?>
      <span class="sp-exp-lbl">Themen:</span>
      <span class="sp-exp-val"><?= htmlspecialchars(implode(' · ', array_slice($card_topics, 0, 3))) ?></span>
    <?php else: ?>
      <span class="sp-exp-lbl">🎤</span><span class="sp-exp-val">Speaker</span>
    <?php endif; ?>
  </div>

  <!-- Format-Badges -->
  <?php if ($show_formats && !empty($formats)): ?>
    <div class="sp-fmt-row">
      <?php foreach (array_slice($formats, 0, 4) as $f): ?>
        <span class="sp-format-pill"><?= htmlspecialchars($fmt_labels[$f] ?? $f) ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Skills: 4 pro Zeile, max 2 Zeilen -->
  <?php if (!empty($skills)): ?>
    <div class="sp-skills-grid">
      <?php foreach (array_slice($skills, 0, 8) as $sk): ?>
        <span class="sp-skill-pill"><?= htmlspecialchars($skill_labels[$sk] ?? $sk) ?></span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Social Band: immer alle Icons, ausgegraut wenn nicht hinterlegt -->
  <div class="sp-social-band">
    <?php
    $social_items = [
      'linkedin' => [
        'url'    => $linkedin,
        'title'  => 'LinkedIn',
        'target' => '_blank',
        'svg'    => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M4.98 3.5C4.98 4.88 3.87 6 2.5 6S.02 4.88.02 3.5C.02 2.12 1.13 1 2.5 1s2.48 1.12 2.48 2.5zM.02 8.5H5V24H.02V8.5zm7.97 0h4.8v2.1h.07C13.7 9 15.44 8 17.6 8c5.2 0 6.16 3.43 6.16 7.88V24H19v-7.2c0-1.72-.03-3.93-2.4-3.93-2.4 0-2.78 1.87-2.78 3.81V24H8z"/></svg>',
      ],
      'xing' => [
        'url'    => $xing,
        'title'  => 'XING',
        'target' => '_blank',
        'svg'    => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M18.188 0c-.517 0-.741.325-.927.66l-7.702 13.657 4.919 9.023c.17.308.436.66.967.66h3.454c.211 0 .375-.078.463-.22.089-.151.089-.346-.009-.536l-4.879-8.916L22.139.756c.095-.191.097-.387.006-.535C22.056.078 21.894 0 21.686 0h-3.498zm-9.945 5.237c-.208 0-.37.093-.458.233-.088.14-.09.316-.004.494l2.214 3.836-3.466 5.239c-.098.198-.1.388-.007.522.09.13.252.201.461.201h3.5c.513 0 .739-.326.926-.661l3.457-5.276-2.2-3.807c-.178-.308-.411-.663-.944-.663z"/></svg>',
      ],
      'twitter' => [
        'url'    => $twitter ? 'https://twitter.com/' . ltrim($twitter, '@') : '',
        'title'  => 'X / Twitter',
        'target' => '_blank',
        'svg'    => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
      ],
      'website' => [
        'url'    => $website,
        'title'  => 'Website',
        'target' => '_blank',
        'svg'    => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm0 2c.34 0 .92.37 1.54 1.51.45.82.82 1.93 1.03 3.24H9.43c.21-1.31.58-2.42 1.03-3.24C11.08 4.37 11.66 4 12 4zm-2.82.55C8.6 5.54 8 6.96 7.6 8.75H5.08A8.05 8.05 0 0 1 9.18 4.55zM4.3 10.75h2.97c-.07.72-.1 1.46-.1 2.25s.03 1.53.1 2.25H4.3a8 8 0 0 1 0-4.5zm.78 6.5h2.52c.4 1.79 1 3.21 1.58 4.2A8.05 8.05 0 0 1 5.08 17.25zm4.35 0h5.14c-.21 1.31-.58 2.42-1.03 3.24C12.92 21.63 12.34 22 12 22s-.92-.37-1.54-1.51c-.45-.82-.82-1.93-1.03-3.24zm5.99 0h2.52a8.05 8.05 0 0 1-4.1 4.2c.58-.99 1.18-2.41 1.58-4.2zm2.98-2H15.73c.07-.72.1-1.46.1-2.25s-.03-1.53-.1-2.25h2.97a8 8 0 0 1 0 4.5zm-4.49-4.5c.07.7.1 1.44.1 2.25s-.03 1.55-.1 2.25H10.1c-.07-.7-.1-1.44-.1-2.25s.03-1.55.1-2.25h3.82zm2.71-2H14.4c-.4-1.79-1-3.21-1.58-4.2a8.05 8.05 0 0 1 4.1 4.2z"/></svg>',
      ],
      'email' => [
        'url'    => $email ? 'mailto:' . $email : '',
        'title'  => 'E-Mail',
        'target' => '_self',
        'svg'    => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4-8 5-8-5V6l8 5 8-5v2z"/></svg>',
      ],
      'github' => [
        'url'    => $github,
        'title'  => 'GitHub',
        'target' => '_blank',
        'svg'    => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>',
      ],
      'gitlab' => [
        'url'    => $gitlab,
        'title'  => 'GitLab',
        'target' => '_blank',
        'svg'    => '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M22.65 14.39L12 22.13 1.35 14.39a.84.84 0 01-.3-.94l1.22-3.78 2.44-7.51A.42.42 0 014.82 2a.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.49h8.1l2.44-7.49a.42.42 0 01.11-.18.43.43 0 01.58 0 .42.42 0 01.11.18l2.44 7.51L23 13.45a.84.84 0 01-.35.94z"/></svg>',
      ],
    ];
    foreach ($social_items as $skey => $si):
      $active = !empty($si['url']);
    ?>
      <?php if ($active): ?>
        <a href="<?= htmlspecialchars($si['url']) ?>" class="sp-soc-ico sp-soc-ico--on"
           target="<?= htmlspecialchars($si['target']) ?>" rel="noopener"
           title="<?= htmlspecialchars($si['title']) ?>"><?= $si['svg'] ?></a>
      <?php else: ?>
        <span class="sp-soc-ico sp-soc-ico--off"
              title="<?= htmlspecialchars($si['title']) ?> (nicht hinterlegt)"><?= $si['svg'] ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
  </div><!-- /.sp-social-band -->

  <!-- Footer / CTA -->
  <div class="sp-card-footer">
    <a href="<?= htmlspecialchars($speaker_url) ?>" class="sp-btn-cta"><?= $cta ?> →</a>
  </div>

</article>
