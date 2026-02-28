<?php
/**
 * Admin Interface für CMS Companies
 *
 * @package CMS_Companies
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Companies_Admin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->loadAdminMenu();
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }

    private function loadAdminMenu(): void
    {
        $menu_file = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menu_file) && !function_exists('renderAdminLayoutStart')) {
            require_once $menu_file;
        }
    }

    public function add_menu_item(array $menuItems): array
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $isActive    = strpos($currentPath, '/admin/companies') === 0;

        $menuItems[] = [
            'type'   => 'item',
            'slug'   => 'companies',
            'label'  => 'Unternehmen',
            'icon'   => '🏢',
            'url'    => '/admin/companies',
            'active' => $isActive,
        ];

        return $menuItems;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // render_list – 5-Tab-Admin
    // ──────────────────────────────────────────────────────────────────────────

    public function render_list(array $data): void
    {
        $this->loadAdminMenu();
        renderAdminLayoutStart('Unternehmen', 'companies');

        // Admin-CSS laden
        $adminCss = CMS_COMPANIES_PLUGIN_DIR . 'assets/css/companies-admin.css';
        echo '<link rel="stylesheet" href="' . CMS_COMPANIES_PLUGIN_URL . 'assets/css/companies-admin.css?v=' . filemtime($adminCss) . '">' . "\n";

        $companies  = $data['companies']  ?? [];
        $tab        = $data['tab']        ?? 'overview';
        $filter     = $data['filter']     ?? 'all';
        $industries = $data['industries'] ?? [];
        $presets    = $data['presets']    ?? ['general' => [], 'special' => [], 'quality' => []];
        $settings   = $data['settings']   ?? [];
        $csrf       = $data['csrf']       ?? '';
        $sec        = CMS\Security::instance();

        $s = array_merge([
            'archive_title'              => 'Unternehmen & Partner',
            'archive_description'        => 'Entdecken Sie Unternehmen und IT-Partner in unserem Netzwerk.',
            'archive_per_page'           => '12',
            'archive_header_icon'        => '🏢',
            'archive_header_bg_from'     => '#e0f2fe',
            'archive_header_bg_to'       => '#bae6fd',
            'archive_header_title_color' => '#0c4a6e',
            'design_primary_color'       => '#0891b2',
            'design_accent_color'        => '#0284c7',
            'design_show_industry'       => '1',
            'design_show_city'           => '1',
            'design_show_employees'      => '0',
            'design_show_website'        => '1',
            'design_border_radius'       => '12',
            'design_grid_columns'        => 'auto',
            'design_cta_color'           => '#0c4a6e',
            'design_card_bg'             => '#f0f9ff',
            'design_detail_header_bg'    => '#e0f2fe',
            'design_detail_header_bg_to' => '#bae6fd',
            'design_detail_header_color' => '#0c4a6e',
            'design_detail_accent'       => '#0891b2',
            'design_partner_color'       => '#9ca3af',
            'design_top_partner_color'   => '#d97706',
            'design_sponsor_color'       => '#7c3aed',
        ], $settings);

        $total      = count($companies);
        $sponsors   = count(array_filter($companies, fn($c) => (bool)$c->is_sponsor));
        $topPartner = count(array_filter($companies, fn($c) => !$c->is_sponsor && (bool)$c->is_top_partner));
        $partner    = count(array_filter($companies, fn($c) => !$c->is_sponsor && !$c->is_top_partner && (bool)$c->is_partner));
        ?>

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>🏢 Unternehmen</h2>
                <p>Unternehmensprofile, Branchen und Design verwalten</p>
            </div>
            <div class="header-actions">
                <a href="<?= SITE_URL ?>/admin/companies/new" class="btn btn-primary">+ Unternehmen anlegen</a>
            </div>
        </div>

        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success" style="margin-bottom:1rem;">✓ Einstellungen gespeichert.</div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success" style="margin-bottom:1rem;">✓ Eintrag gelöscht.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error" style="margin-bottom:1rem;">✕ Fehler aufgetreten.</div><?php endif; ?>

        <!-- Tabs -->
        <div class="co-tabs">
            <?php
            $tabs = [
                'overview'   => ['🏢', 'Übersicht',    ''],
                'industries' => ['🏭', 'Branchen',      ''],
                'tags'       => ['🏷️', 'Merkmale',      ''],
                'design'     => ['🎨', 'Design',        ''],
                'settings'   => ['⚙️', 'Einstellungen', ''],
            ];
            foreach ($tabs as $slug => [$icon, $label, $badge]): ?>
                <a href="?tab=<?= $slug ?>" class="co-tab <?= $tab === $slug ? 'active' : '' ?>">
                    <?= $icon ?> <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════════════
        if ($tab === 'overview'):
            $filtered = $companies;
            if ($filter === 'sponsor')     $filtered = array_values(array_filter($companies, fn($c) => (bool)$c->is_sponsor));
            elseif ($filter === 'top')     $filtered = array_values(array_filter($companies, fn($c) => !$c->is_sponsor && (bool)$c->is_top_partner));
            elseif ($filter === 'partner') $filtered = array_values(array_filter($companies, fn($c) => !$c->is_sponsor && !$c->is_top_partner && (bool)$c->is_partner));
        ?>

        <?php
        $statItems = [
            ['icon' => '🏢', 'value' => $total,      'label' => 'Gesamt',      'color' => ''],
            ['icon' => '💜', 'value' => $sponsors,   'label' => 'Sponsoren',   'color' => 'color:#7c3aed'],
            ['icon' => '🥇', 'value' => $topPartner, 'label' => 'Top-Partner', 'color' => 'color:#b45309'],
            ['icon' => '🤝', 'value' => $partner,    'label' => 'Partner',     'color' => 'color:#6b7280'],
        ];
        ?>
        <div class="co-stats">
            <?php foreach ($statItems as $st): ?>
            <div class="co-stat">
                <span class="co-stat-icon"><?= $st['icon'] ?></span>
                <span class="co-stat-val"<?= $st['color'] ? ' style="' . $st['color'] . '"' : '' ?>><?= $st['value'] ?></span>
                <span class="co-stat-lbl"><?= $st['label'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="co-filter-bar">
            <?php
            $filterOptions = [
                'all'     => "Alle ({$total})",
                'sponsor' => "💜 Sponsoren ({$sponsors})",
                'top'     => "🥇 Top-Partner ({$topPartner})",
                'partner' => "🤝 Partner ({$partner})",
            ];
            foreach ($filterOptions as $fk => $fl): ?>
                <a href="?tab=overview&filter=<?= $fk ?>"
                   class="co-filter-btn <?= $filter === $fk ? 'co-filter-btn--active' : '' ?>">
                    <?= $fl ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($filtered)): ?>
            <div class="co-empty">
                <div style="font-size:3rem;margin-bottom:1rem;">🏢</div>
                <p>Keine Unternehmen<?= $filter !== 'all' ? ' in diesem Filter' : '' ?> gefunden.</p>
                <?php if ($filter === 'all'): ?>
                    <a href="<?= SITE_URL ?>/admin/companies/new" class="btn btn-primary" style="margin-top:1rem;">
                        Erstes Unternehmen anlegen
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="co-adm-grid">
        <?php foreach ($filtered as $co):
            $initials  = mb_strtoupper(mb_substr($co->name, 0, 1));
            $isSponsor = (bool)$co->is_sponsor;
            $isTop     = !$isSponsor && (bool)$co->is_top_partner;
            $isPartner = !$isSponsor && !$isTop && (bool)$co->is_partner;
            $isInactive = ($co->status ?? 'active') === 'inactive';
            $cardCls   = $isSponsor ? 'co-adm-card--sponsor' : ($isTop ? 'co-adm-card--top' : ($isPartner ? 'co-adm-card--partner' : ''));
            $colors    = [['#0891b2','#0284c7'],['#7c3aed','#a855f7'],['#059669','#34d399'],['#d97706','#f59e0b'],['#e11d48','#fb7185']];
            $cp        = $colors[abs(crc32($co->name)) % count($colors)];
            $bg        = "linear-gradient(135deg,{$cp[0]},{$cp[1]})";
        ?>
            <div class="co-adm-card <?= $cardCls ?>">
                <div class="co-adm-top">
                    <div class="co-adm-avatar" style="background:<?= $bg ?>"><?= $sec->escape($initials) ?></div>
                    <div class="co-adm-ident">
                        <?php if ($isSponsor): ?>
                            <span class="co-adm-badge co-adm-badge--sponsor">Sponsor</span>
                        <?php elseif ($isTop): ?>
                            <span class="co-adm-badge co-adm-badge--top">Top-Partner</span>
                        <?php elseif ($isPartner): ?>
                            <span class="co-adm-badge co-adm-badge--partner">Partner</span>
                        <?php else: ?>
                            <span class="co-adm-badge co-adm-badge--default">Unternehmen</span>
                        <?php endif; ?>
                        <?php if ($isInactive): ?>
                            <span class="co-adm-badge co-adm-badge--inactive" title="Inaktiv – nicht öffentlich sichtbar">🔒 Inaktiv</span>
                        <?php endif; ?>
                        <p class="co-adm-name"><?= $sec->escape($co->name) ?></p>
                        <?php if (!empty($co->industry)): ?>
                            <p class="co-adm-sub"><?= $sec->escape($co->industry) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                $pills = [];
                if (!empty($co->location_city)) $pills[] = ['📍', $sec->escape($co->location_city)];
                if (!empty($co->employee_count)) $pills[] = ['👥', $co->employee_count . ' Mitarb.'];
                if (!empty($co->website))       $pills[] = ['🌐', parse_url($co->website, PHP_URL_HOST) ?: $sec->escape($co->website)];
                if ($pills): ?>
                <div class="co-adm-pills">
                    <?php foreach ($pills as [$ico, $txt]): ?>
                        <span class="co-adm-pill"><?= $ico ?> <?= $txt ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="co-adm-foot">
                    <a href="<?= cms_company_url($co) ?>" target="_blank"
                       class="co-adm-btn co-adm-btn-ghost">👁 Ansehen</a>
                    <a href="<?= SITE_URL ?>/admin/companies/edit/<?= (int)$co->id ?>"
                       class="co-adm-btn co-adm-btn-primary">✏️ Bearbeiten</a>
                    <button type="button" class="co-adm-btn co-adm-btn-danger"
                            onclick="openCoDeleteModal(<?= (int)$co->id ?>, '<?= $sec->escape(addslashes($co->name)) ?>')">
                        🗑
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
        // ══════════════════════════════════════════════════════════════════════
        elseif ($tab === 'industries'):
        ?>
        <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">
            <div>
                <h3 style="margin:0 0 1rem;">Vorhandene Branchen (<?= count($industries) ?>)</h3>
                <?php if (empty($industries)): ?>
                    <p class="text-muted">Noch keine Branchen vorhanden.</p>
                <?php else: ?>
                    <div class="co-tax-list">
                    <?php foreach ($industries as $ind): ?>
                        <div class="co-tax-row">
                            <span class="co-tax-name">🏭 <?= $sec->escape($ind->name) ?></span>
                            <?php if ($ind->id > 0): ?>
                                <form method="POST" action="<?= SITE_URL ?>/admin/companies/industry/delete/<?= (int)$ind->id ?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="co-del-btn"
                                            onclick="return confirm('Branche «<?= $sec->escape(addslashes($ind->name)) ?>» löschen?')">×</button>
                                </form>
                            <?php else: ?>
                                <span class="co-tax-std">Standard</span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="co-side-card">
                <h3 style="margin:0 0 1rem;">+ Neue Branche</h3>
                <form method="POST" action="<?= SITE_URL ?>/admin/companies/industry/add">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="co-form-group">
                        <label>Branchen-Name *</label>
                        <input type="text" name="industry_name" required placeholder="z.B. Cybersecurity">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">+ Anlegen</button>
                </form>
            </div>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════════════
        elseif ($tab === 'tags'):
            $typeLabels = [
                'general' => ['🔷','Allgemein','Allgemeine Unternehmensmerkmale'],
                'special' => ['⭐','Spezialisierung','Spezielle Kompetenzen & Ausrichtungen'],
                'quality' => ['✅','Qualität & Zertifikate','Zertifizierungen & Auszeichnungen'],
            ];
        ?>
        <div style="display:grid;grid-template-columns:1fr 280px;gap:1.5rem;align-items:start;">
            <div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
                <?php foreach ($typeLabels as $type => [$icon, $label, $desc]): ?>
                    <div class="co-side-card">
                        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;">
                            <span style="font-size:1.25rem;"><?= $icon ?></span>
                            <div>
                                <strong style="font-size:.875rem;"><?= $label ?></strong>
                                <div style="font-size:.72rem;color:#64748b;"><?= $desc ?></div>
                            </div>
                        </div>
                        <div class="co-tag-list">
                            <?php foreach ($presets[$type] ?? [] as $tg): ?>
                                <span class="co-tag">
                                    <?= $sec->escape($tg->tag_name) ?>
                                    <form method="POST" action="<?= SITE_URL ?>/admin/companies/tagpreset/delete/<?= (int)$tg->id ?>" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="co-tag-del"
                                                onclick="return confirm('«<?= $sec->escape(addslashes($tg->tag_name)) ?>» löschen?')">×</button>
                                    </form>
                                </span>
                            <?php endforeach; ?>
                            <?php if (empty($presets[$type])): ?>
                                <span class="text-muted" style="font-size:.75rem;">Noch keine Eintr\u00e4ge.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <div class="co-side-card">
                <h3 style="margin:0 0 1rem;">+ Neues Merkmal</h3>
                <form method="POST" action="<?= SITE_URL ?>/admin/companies/tagpreset/add">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="co-form-group">
                        <label>Merkmal *</label>
                        <input type="text" name="tag_name" required placeholder="z.B. ISO 27001">
                    </div>
                    <div class="co-form-group">
                        <label>Kategorie *</label>
                        <select name="tag_type">
                            <option value="general">🔷 Allgemein</option>
                            <option value="special">⭐ Spezialisierung</option>
                            <option value="quality">✅ Qualität</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Hinzufügen</button>
                </form>
            </div>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════════════
        elseif ($tab === 'design'):
        ?>
        <!-- Design Tab -->
        <form method="POST" action="<?= SITE_URL ?>/admin/companies/settings/save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="_from_tab"  value="design">

            <div class="admin-card">
                <h3>🎨 Farbpalette</h3>
                <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
                    <?php
                    $colorFields = [
                        'design_primary_color'       => ['Primärfarbe (Buttons, Akzente)',       '#0891b2'],
                        'design_accent_color'        => ['Akzentfarbe (Hover, Links)',            '#0284c7'],
                        'design_card_bg'             => ['Karten-Hintergrund',                    '#f0f9ff'],
                        'design_cta_color'           => ['CTA-Button-Farbe',                      '#0c4a6e'],
                        'archive_header_bg_from'     => ['Archiv-Header Gradient Von',             '#e0f2fe'],
                        'archive_header_bg_to'       => ['Archiv-Header Gradient Bis',             '#bae6fd'],
                        'archive_header_title_color' => ['Archiv-Header Titelfarbe',               '#0c4a6e'],
                        'design_detail_header_bg'    => ['Detailseite Header Gradient Von',       '#e0f2fe'],
                        'design_detail_header_bg_to' => ['Detailseite Header Gradient Bis',       '#bae6fd'],
                        'design_detail_header_color' => ['Detailseite Titelfarbe',                 '#0c4a6e'],
                        'design_detail_accent'       => ['Detailseite Akzentfarbe (Links)',         '#0891b2'],
                    ];
                    foreach ($colorFields as $key => [$label, $default]):
                        $val = htmlspecialchars($s[$key] ?? $default);
                    ?>
                    <div class="form-group">
                        <label class="form-label"><?= $label ?></label>
                        <div style="display:flex;gap:.5rem;align-items:center;">
                            <input type="color" id="clr_<?= $key ?>" value="<?= $val ?>" style="width:48px;height:36px;border:2px solid #e2e8f0;border-radius:6px;padding:2px;cursor:pointer;" oninput="document.getElementById('txt_<?= $key ?>').value=this.value">
                            <input type="text" id="txt_<?= $key ?>" name="<?= $key ?>" class="form-control" value="<?= $val ?>" style="flex:1;font-family:monospace;font-size:.82rem;" oninput="document.getElementById('clr_<?= $key ?>').value=this.value">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-card">
                <h3>🏷️ Badge- &amp; Rahmenfarben</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Diese Farbe gilt jeweils für den Status-Ribbon <strong>und</strong> den Karten-Rahmen auf der öffentlichen Seite.</p>
                <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
                    <?php
                    $badgeFields = [
                        'design_partner_color'     => ['🤝 Partner',     '#9ca3af'],
                        'design_top_partner_color' => ['🥇 Top-Partner',  '#d97706'],
                        'design_sponsor_color'     => ['💜 Sponsor',      '#7c3aed'],
                    ];
                    foreach ($badgeFields as $key => [$label, $default]):
                        $val = htmlspecialchars($s[$key] ?? $default);
                    ?>
                    <div class="form-group">
                        <label class="form-label"><?= $label ?></label>
                        <div style="display:flex;gap:.5rem;align-items:center;">
                            <input type="color" id="clr_<?= $key ?>" value="<?= $val ?>" style="width:48px;height:36px;border:2px solid #e2e8f0;border-radius:6px;padding:2px;cursor:pointer;" oninput="document.getElementById('txt_<?= $key ?>').value=this.value">
                            <input type="text" id="txt_<?= $key ?>" name="<?= $key ?>" class="form-control" value="<?= $val ?>" style="flex:1;font-family:monospace;font-size:.82rem;" oninput="document.getElementById('clr_<?= $key ?>').value=this.value">
                        </div>
                        <small class="form-text">Ribbon-Badge &amp; Karten-Rahmen</small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-card">
                <h3>🖼️ Archiv-Header</h3>
                <div class="form-group" style="max-width:120px;">
                    <label class="form-label">Header-Icon (Emoji)</label>
                    <input type="text" name="archive_header_icon" id="txt_archive_header_icon" class="form-control"
                           value="<?= htmlspecialchars(html_entity_decode($s['archive_header_icon'] ?? '🏢', ENT_HTML5, 'UTF-8')) ?>"
                           maxlength="8" style="font-size:1.4rem;text-align:center;"
                           oninput="updateCoHdrPreview()">
                    <small class="form-text">z.B. 🏢 🌐 💼</small>
                </div>
                <div id="co_hdr_preview" style="margin-top:1rem;padding:1rem 1.5rem;border-radius:10px;display:inline-flex;align-items:center;gap:.75rem;font-weight:800;font-size:1rem;">
                    <span id="co_hdr_icon" style="font-size:2rem;"></span>
                    <div>
                        <div id="co_hdr_title" style="font-weight:800;font-size:1.1rem;"></div>
                        <div style="font-size:.8rem;opacity:.8;">Vorschau</div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h3>📐 Layout &amp; Anzeige</h3>
                <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Ecken-Radius (px)</label>
                        <input type="number" name="design_border_radius" class="form-control"
                               value="<?= (int)($s['design_border_radius'] ?? 12) ?>" min="0" max="32">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grid-Spalten</label>
                        <select name="design_grid_columns" class="form-control">
                            <?php foreach (['auto'=>'Automatisch (responsive)','2'=>'2 Spalten','3'=>'3 Spalten','4'=>'4 Spalten'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($s['design_grid_columns']??'auto')===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-top:.75rem;">
                    <?php foreach ([
                        'design_show_industry'  => '🏭 Branche',
                        'design_show_city'      => '📍 Stadt / Standort',
                        'design_show_employees' => '👥 Mitarbeiterzahl',
                        'design_show_website'   => '🌐 Website-Link',
                    ] as $key => $label): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="<?= $key ?>" value="1"
                               <?= !empty($s[$key]) && $s[$key] !== '0' ? 'checked' : '' ?>>
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-card form-actions-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Design speichern</button>
                </div>
            </div>
        </form>
        <script>
        (function(){
            function updateCoHdrPreview(){
                var from  = (document.getElementById('txt_archive_header_bg_from')    ||{value:'#e0f2fe'}).value;
                var to    = (document.getElementById('txt_archive_header_bg_to')      ||{value:'#bae6fd'}).value;
                var color = (document.getElementById('txt_archive_header_title_color')||{value:'#0c4a6e'}).value;
                var icon  = (document.getElementById('txt_archive_header_icon')       ||{value:'🏢'}).value;
                var prev  = document.getElementById('co_hdr_preview');
                var icoEl = document.getElementById('co_hdr_icon');
                var ttlEl = document.getElementById('co_hdr_title');
                if(prev){ prev.style.background='linear-gradient(135deg,'+from+','+to+')'; prev.style.color=color; }
                if(icoEl) icoEl.textContent = icon;
                if(ttlEl){ ttlEl.textContent='<?= addslashes(htmlspecialchars($s['archive_title'] ?? 'Unternehmen')) ?>'; ttlEl.style.color=color; }
            }
            window.updateCoHdrPreview = updateCoHdrPreview;
            updateCoHdrPreview();
        })();
        </script>

        <?php
        // ══════════════════════════════════════════════════════════════════════
        elseif ($tab === 'settings'):
        ?>
        <!-- Settings Tab -->
        <form method="POST" action="<?= SITE_URL ?>/admin/companies/settings/save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="_from_tab"  value="settings">

            <div class="admin-card">
                <h3>📋 Archiv-Seite (/companies)</h3>
                <div class="form-group">
                    <label class="form-label">Seitentitel</label>
                    <input type="text" name="archive_title" class="form-control"
                           value="<?= htmlspecialchars($s['archive_title']) ?>"
                           placeholder="Unternehmen &amp; Partner">
                </div>
                <div class="form-group">
                    <label class="form-label">Beschreibungstext</label>
                    <textarea name="archive_description" class="form-control" rows="3"
                              placeholder="Kurze Beschreibung für Besucher..."><?= htmlspecialchars($s['archive_description']) ?></textarea>
                    <small class="form-text">Einleitungstext auf der Übersichtsseite.</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Unternehmen pro Seite</label>
                    <input type="number" name="archive_per_page" class="form-control"
                           value="<?= (int)($s['archive_per_page'] ?? 12) ?>"
                           min="4" max="100" step="4" style="width:120px;">
                </div>
            </div>
            <div class="admin-card form-actions-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <!-- Dynamische Partner-Farben per CSS-Variable (aus DB) -->
        <style>
        :root {
            --co-sponsor-color: <?= htmlspecialchars($s['design_sponsor_color'] ?? '#a855f7') ?>;
            --co-top-color:     <?= htmlspecialchars($s['design_top_partner_color'] ?? '#f59e0b') ?>;
            --co-partner-color: <?= htmlspecialchars($s['design_partner_color'] ?? '#94a3b8') ?>;
        }
        </style>

        <!-- Delete Modal -->
        <div id="coDeleteModal" class="modal" style="display:none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>🗑️ Unternehmen löschen</h3>
                    <button class="modal-close" onclick="closeModal('coDeleteModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Möchten Sie das Unternehmen «<strong id="coDeleteName"></strong>» wirklich löschen?</p>
                    <p style="color:#991b1b;font-size:.875rem;">Dieser Vorgang kann nicht rückgängig gemacht werden.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('coDeleteModal')">Abbrechen</button>
                    <form id="coDeleteForm" method="POST" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= CMS\Security::instance()->generateToken('delete_company') ?>">
                        <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
                    </form>
                </div>
            </div>
        </div>
        <script>
        function openCoDeleteModal(id, name) {
            document.getElementById('coDeleteName').textContent = name;
            document.getElementById('coDeleteForm').action = '<?= SITE_URL ?>/admin/companies/delete/' + id;
            openModal('coDeleteModal');
        }
        </script>
        <?php
        renderAdminLayoutEnd();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // render_form – Neu anlegen + Bearbeiten
    // ──────────────────────────────────────────────────────────────────────────

    public function render_form($company = null): void
    {
        $this->loadAdminMenu();
        $is_edit    = ($company !== null);
        $page_title = $is_edit ? 'Unternehmen bearbeiten' : 'Neues Unternehmen anlegen';
        $csrf_token = CMS\Security::instance()->generateToken('save_company');

        // Experten für Zuordnungs-Sektion laden
        $db       = CMS_Companies_Database::instance();
        $experts  = $db->get_available_experts();
        $assigned = $is_edit ? $db->get_company_experts((int)$company->id, false) : [];

        renderAdminLayoutStart($page_title, 'companies');

        // Admin-CSS laden
        $adminCss = CMS_COMPANIES_PLUGIN_DIR . 'assets/css/companies-admin.css';
        echo '<link rel="stylesheet" href="' . CMS_COMPANIES_PLUGIN_URL . 'assets/css/companies-admin.css?v=' . filemtime($adminCss) . '">' . "\n";
        ?>
        <div class="admin-page-header">
            <div>
                <h2><?= $is_edit ? '✏️ Unternehmen bearbeiten' : '➕ Neues Unternehmen anlegen' ?></h2>
                <p><?= $is_edit
                    ? 'Firmendaten, Kontakt und Experten-Zuordnungen bearbeiten'
                    : 'Neues Unternehmen im Verzeichnis anlegen' ?></p>
            </div>
            <div class="header-actions">
                <?php if ($is_edit): ?>
                <a href="<?= SITE_URL ?>/companies/<?= (int)$company->id ?>" target="_blank"
                   class="btn btn-secondary">👁 Ansehen</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/admin/companies" class="btn btn-secondary">← Zurück</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✓ Unternehmen erfolgreich gespeichert.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">✕ Fehler beim Speichern
                <?php if ($_GET['error'] === 'csrf'): ?> &ndash; Sicherheitscheck fehlgeschlagen. Bitte Seite neu laden.
                <?php elseif ($_GET['error'] === 'save'): ?> &ndash; Datenbank-Fehler. Bitte Log prüfen.
                <?php else: ?> &ndash; Bitte Pflichtfelder prüfen.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div style="max-width:900px;">
            <form method="POST" action="<?= SITE_URL ?>/admin/companies/save" id="co-main-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="company_id" value="<?= $is_edit ? (int)$company->id : 0 ?>">

                <?php CMS_Companies_Meta_Boxes::instance()->render_company_form_fields($company, $experts, $assigned); ?>

                <div class="admin-card" style="margin-top:0;">
                    <div style="display:flex;align-items:center;gap:.75rem;justify-content:space-between;flex-wrap:wrap;">
                        <div style="display:flex;gap:.75rem;">
                            <button type="submit" class="btn btn-primary">
                                <?= $is_edit ? '💾 Änderungen speichern' : '➕ Unternehmen anlegen' ?>
                            </button>
                            <a href="<?= SITE_URL ?>/admin/companies" class="btn btn-secondary">Abbrechen</a>
                        </div>
                        <span class="form-text">Alle Pflichtfelder (*) müssen ausgefüllt sein.</span>
                    </div>
                </div>
            </form>

            <?php
            // Experten-Zuordnung AUSSERHALB des Hauptformulars rendern,
            // da sie eigene <form>-Elemente enthält (keine verschachtelten Forms).
            if ($is_edit):
                CMS_Companies_Meta_Boxes::instance()->render_expert_fields($company, $experts, $assigned);
            endif;
            ?>
        </div>
        <?php
        renderAdminLayoutEnd();
    }
}
