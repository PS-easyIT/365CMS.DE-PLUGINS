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
        CMS\Hooks::addAction('cms_admin_menu', [$this, 'register_admin_menu'], 10);
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }

    public function register_admin_menu(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Unternehmen',
            '365NET | Unternehmen',
            'manage_options',
            'companies',
            [self::class, 'render_plugin_page_bridge'],
            '🏢',
            42
        );
    }

    public static function render_plugin_page_bridge(): void
    {
        $targetUrl = htmlspecialchars(SITE_URL . '/admin/companies', ENT_QUOTES, 'UTF-8');

        echo '<div class="admin-card"><p>Weiterleitung zur Unternehmen-Verwaltung … <a href="' . $targetUrl . '">Falls nichts passiert, hier klicken</a>.</p></div>';
        echo '<script>window.location.replace(' . json_encode(SITE_URL . '/admin/companies', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');</script>';
    }

    private function loadAdminMenu(): void
    {
        $menu_file = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menu_file) && !function_exists('renderAdminLayoutStart')) {
            require_once $menu_file;
        }
    }

    private function start_admin_layout(string $title, string $activePage): void
    {
        $this->loadAdminMenu();

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $activePage);
            return;
        }

        $pageTitle = $title;
        require_once ABSPATH . 'admin/partials/header.php';
        require_once ABSPATH . 'admin/partials/sidebar.php';
    }

    private function end_admin_layout(): void
    {
        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
            return;
        }

        require_once ABSPATH . 'admin/partials/footer.php';
    }

    public function add_menu_item(array $menuItems): array
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $isActive    = strpos($currentPath, '/admin/companies') === 0;

        $menuItems[] = [
            'type'   => 'item',
            'slug'   => 'companies',
            'label'  => '365NET | Unternehmen',
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
        $this->start_admin_layout('Unternehmen', 'companies');

        // Admin-CSS laden
        $adminCss = CMS_COMPANIES_PLUGIN_DIR . 'assets/css/companies-admin.css';
        $adminCssVersion = file_exists($adminCss) ? (string) filemtime($adminCss) : '1';
        echo '<link rel="stylesheet" href="' . CMS_COMPANIES_PLUGIN_URL . 'assets/css/companies-admin.css?v=' . $adminCssVersion . '">' . "\n";

        $companies  = $data['companies']  ?? [];
        $tab        = $data['tab']        ?? 'overview';
        $filter     = $data['filter']     ?? 'all';
        $search     = $data['search']     ?? '';
        $industries = $data['industries'] ?? [];
        $presets    = $data['presets']    ?? ['general' => [], 'special' => [], 'quality' => []];
        $settings   = $data['settings']   ?? [];
        $csrf       = $data['csrf']       ?? '';
        $sec        = CMS\Security::instance();

        $s = array_merge([
            'archive_title'                 => 'Unternehmen & Partner',
            'archive_description'           => 'Entdecken Sie Unternehmen und IT-Partner in unserem Netzwerk.',
            'archive_per_page'              => '12',
            'show_nav_link'                 => '0',
            'nav_label'                     => 'Unternehmen',
            'archive_header_icon'           => '🏢',
            'archive_header_bg_from'        => '#e0f2fe',
            'archive_header_bg_to'          => '#bae6fd',
            'archive_header_title_color'    => '#0c4a6e',
            'design_primary_color'          => '#0891b2',
            'design_accent_color'           => '#0284c7',
            'design_show_industry'          => '1',
            'design_show_city'              => '1',
            'design_show_employees'         => '0',
            'design_show_website'           => '1',
            'design_border_radius'          => '12',
            'design_grid_columns'           => 'auto',
            'design_cta_label'              => 'Profil ansehen',
            'design_cta_color'              => '#0c4a6e',
            'design_card_bg'                => '#f0f9ff',
            'design_detail_header_bg'       => '#e0f2fe',
            'design_detail_header_bg_to'    => '#bae6fd',
            'design_detail_header_color'    => '#0c4a6e',
            'design_detail_accent'          => '#0891b2',
            'design_partner_color'          => '#9ca3af',
            'design_top_partner_color'      => '#d97706',
            'design_sponsor_color'          => '#7c3aed',
            // Badge-Sichtbarkeit
            'design_show_sponsor_badge'      => '1',
            'design_show_top_partner_badge'  => '1',
            'design_show_partner_badge'      => '1',
            'design_show_inactive_badge'     => '1',
            // Badge-Farben individuell
            'design_badge_sponsor_bg'        => '#f3e8ff',
            'design_badge_sponsor_color'     => '#6b21a8',
            'design_badge_top_bg'            => '#fef3c7',
            'design_badge_top_color'         => '#92400e',
            'design_badge_partner_bg'        => '#f1f5f9',
            'design_badge_partner_color'     => '#475569',
            'design_badge_inactive_bg'       => '#f1f5f9',
            'design_badge_inactive_color'    => '#64748b',
        ], $settings);

        $total      = count($companies);
        $sponsors   = count(array_filter($companies, fn($c) => (bool)$c->is_sponsor));
        $topPartner = count(array_filter($companies, fn($c) => !$c->is_sponsor && (bool)$c->is_top_partner));
        $partner    = count(array_filter($companies, fn($c) => !$c->is_sponsor && !$c->is_top_partner && (bool)$c->is_partner));
        $pending    = count(array_filter($companies, fn($c) => ($c->status ?? 'active') === 'pending'));
        ?>

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>🏢 Unternehmen</h2>
                <p>Unternehmensprofile, Branchen und Design verwalten</p>
            </div>
            <div class="header-actions">
                <a href="<?= SITE_URL ?>/companies" class="btn btn-secondary" target="_blank">🌐 Öffentlich</a>
                <a href="<?= SITE_URL ?>/admin/companies/new" class="btn btn-primary">➕ Unternehmen anlegen</a>
            </div>
        </div>

        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Einstellungen gespeichert.</div><?php endif; ?>
        <?php if (isset($_GET['approved'])): ?><div class="alert alert-success">✅ Unternehmen genehmigt und aktiviert.</div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✅ Eintrag gelöscht.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error">❌ Fehler: <?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

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
                    <?php if ($slug === 'overview' && $pending > 0): ?>
                        <span class="nav-badge" style="background:#f59e0b;color:#fff;font-size:.7rem;padding:1px 6px;border-radius:9px;margin-left:4px;"><?= $pending ?></span>
                    <?php endif; ?>
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
            elseif ($filter === 'pending') $filtered = array_values(array_filter($companies, fn($c) => ($c->status ?? 'active') === 'pending'));
        ?>

        <!-- Stats -->
        <div class="dashboard-grid">
            <?php
            $statItems = [
                ['🏢', 'Gesamt',      $total,      ''],
                ['⏳', 'Ausstehend',  $pending,    'color:#d97706'],
                ['💜', 'Sponsoren',   $sponsors,   'color:#7c3aed'],
                ['🥇', 'Top-Partner', $topPartner, 'color:#b45309'],
                ['🤝', 'Partner',     $partner,    'color:#6b7280'],
            ];
            foreach ($statItems as [$si_icon, $si_label, $si_value, $si_style]): ?>
            <div class="stat-card">
                <div class="stat-icon"><?= $si_icon ?></div>
                <div class="stat-number"<?= $si_style ? ' style="' . $si_style . '"' : '' ?>><?= (int)$si_value ?></div>
                <div class="stat-label"><?= $si_label ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Filter Bar -->
        <div class="admin-card" style="margin-bottom:1.25rem;">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;">
                <input type="hidden" name="tab" value="overview">
                <div class="form-group" style="margin:0;flex:2;min-width:220px;">
                    <label class="form-label">Name / Stichwort</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, Stadt, Beschreibung…" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:160px;">
                    <label class="form-label">Typ / Stufe</label>
                    <select name="filter" class="form-control">
                        <option value="all"     <?= $filter==='all'     ?'selected':'' ?>>Alle (<?= $total ?>)</option>
                        <option value="sponsor" <?= $filter==='sponsor' ?'selected':'' ?>>💜 Sponsoren (<?= $sponsors ?>)</option>
                        <option value="top"     <?= $filter==='top'     ?'selected':'' ?>>🥇 Top-Partner (<?= $topPartner ?>)</option>
                        <option value="pending" <?= $filter==='pending' ?'selected':'' ?>>⏳ Ausstehend (<?= $pending ?>)</option>
                        <option value="partner" <?= $filter==='partner' ?'selected':'' ?>>🤝 Partner (<?= $partner ?>)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">🔍 Filtern</button>
                <?php if ($search || $filter !== 'all'): ?><a href="?tab=overview" class="btn btn-secondary">✕ Reset</a><?php endif; ?>
            </form>
        </div>

        <?php if (empty($filtered)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">🏢</p>
            <p><strong>Keine Unternehmen<?= $filter !== 'all' ? ' in diesem Filter' : '' ?> gefunden</strong></p>
            <?php if ($search): ?><p class="text-muted">Keine Treffer für «<?= htmlspecialchars($search) ?>».</p><?php endif; ?>
            <?php if ($filter === 'all' && !$search): ?>
                <a href="<?= SITE_URL ?>/admin/companies/new" class="btn btn-primary" style="margin-top:1rem;">➕ Erstes Unternehmen anlegen</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="co-adm-grid">
        <?php foreach ($filtered as $co):
            $initials  = mb_strtoupper(mb_substr($co->name, 0, 1));
            $isSponsor  = (bool)$co->is_sponsor;
            $isTop      = !$isSponsor && (bool)$co->is_top_partner;
            $isPartner  = !$isSponsor && !$isTop && (bool)$co->is_partner;
            $isInactive = ($co->status ?? 'active') === 'inactive';
            $isPending  = ($co->status ?? 'active') === 'pending';
            $cardCls   = $isSponsor ? 'co-adm-card--sponsor' : ($isTop ? 'co-adm-card--top' : ($isPartner ? 'co-adm-card--partner' : ''));
            $colors    = [['#0891b2','#0284c7'],['#7c3aed','#a855f7'],['#059669','#34d399'],['#d97706','#f59e0b'],['#e11d48','#fb7185']];
            $cp        = $colors[abs(crc32($co->name)) % count($colors)];
            $bg        = "linear-gradient(135deg,{$cp[0]},{$cp[1]})";
            $showSponsorBadge  = ($s['design_show_sponsor_badge']     ?? '1') !== '0';
            $showTopBadge      = ($s['design_show_top_partner_badge']  ?? '1') !== '0';
            $showPartnerBadge  = ($s['design_show_partner_badge']      ?? '1') !== '0';
            $showInactiveBadge = ($s['design_show_inactive_badge']     ?? '1') !== '0';
            $showCity          = ($s['design_show_city']      ?? '1') !== '0';
            $showEmployees     = ($s['design_show_employees']  ?? '0') !== '0';
            $showWebsite       = ($s['design_show_website']    ?? '1') !== '0';
        ?>
            <div class="co-adm-card <?= $cardCls ?><?= $isPending ? ' co-adm-card--pending' : '' ?>">
                <?php if ($isPending): ?>
                    <div style="background:#fef3c7;color:#92400e;text-align:center;padding:.5rem;font-size:.85rem;font-weight:600;border-radius:10px 10px 0 0;">⏳ Wartet auf Genehmigung</div>
                <?php endif; ?>
                <div class="co-adm-top">
                    <div class="co-adm-avatar" style="background:<?= $bg ?>"><?= $sec->escape($initials) ?></div>
                    <div class="co-adm-ident">
                        <div class="co-adm-badges">
                            <?php if ($isSponsor && $showSponsorBadge): ?>
                                <span class="co-adm-badge" style="background:<?= htmlspecialchars($s['design_badge_sponsor_bg']) ?>;color:<?= htmlspecialchars($s['design_badge_sponsor_color']) ?>;border:1px solid <?= htmlspecialchars($s['design_badge_sponsor_bg']) ?>;">💜 Sponsor</span>
                            <?php elseif ($isTop && $showTopBadge): ?>
                                <span class="co-adm-badge" style="background:<?= htmlspecialchars($s['design_badge_top_bg']) ?>;color:<?= htmlspecialchars($s['design_badge_top_color']) ?>;border:1px solid <?= htmlspecialchars($s['design_badge_top_bg']) ?>;">🥇 Top-Partner</span>
                            <?php elseif ($isPartner && $showPartnerBadge): ?>
                                <span class="co-adm-badge" style="background:<?= htmlspecialchars($s['design_badge_partner_bg']) ?>;color:<?= htmlspecialchars($s['design_badge_partner_color']) ?>;border:1px solid <?= htmlspecialchars($s['design_badge_partner_bg']) ?>;">🤝 Partner</span>
                            <?php else: ?>
                                <span class="co-adm-badge co-adm-badge--default">🏢 Unternehmen</span>
                            <?php endif; ?>
                            <?php if ($isPending): ?>
                                <span class="co-adm-badge" style="background:#fef3c7;color:#92400e;">⏳ Zur Prüfung</span>
                            <?php elseif ($isInactive && $showInactiveBadge): ?>
                                <span class="co-adm-badge" style="background:<?= htmlspecialchars($s['design_badge_inactive_bg']) ?>;color:<?= htmlspecialchars($s['design_badge_inactive_color']) ?>;">🔒 Inaktiv</span>
                            <?php endif; ?>
                        </div>
                        <p class="co-adm-name"><?= $sec->escape($co->name) ?></p>
                        <?php if (!empty($co->industry)): ?>
                            <p class="co-adm-sub"><?= $sec->escape($co->industry) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                $pills = [];
                if ($showCity      && !empty($co->location_city)) $pills[] = ['📍', $sec->escape($co->location_city), ''];
                if ($showEmployees && !empty($co->employee_count)) $pills[] = ['👥', $co->employee_count . ' Mitarb.', ''];
                if ($showWebsite   && !empty($co->website))        $pills[] = ['🌐', parse_url($co->website, PHP_URL_HOST) ?: $sec->escape($co->website), 'accent'];
                if ($pills): ?>
                <div class="co-adm-pills">
                    <?php foreach ($pills as [$ico, $txt, $cls]): ?>
                        <span class="co-adm-pill<?= $cls ? ' co-adm-pill--accent' : '' ?>"><?= $ico ?> <?= $txt ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="co-adm-foot">
                    <?php if ($isPending): ?>
                        <form method="POST" action="<?= SITE_URL ?>/admin/companies/approve/<?= (int)$co->id ?>" style="display:contents;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="button" class="co-adm-btn co-adm-btn-primary" style="background:#16a34a;border-color:#16a34a;"
                                    onclick="openCoApproveModal(<?= (int)$co->id ?>, '<?= $sec->escape(addslashes($co->name)) ?>', this.closest('form'))">✓ Genehmigen</button>
                        </form>
                    <?php else: ?>
                        <a href="<?= cms_company_url($co) ?>" target="_blank"
                           class="co-adm-btn co-adm-btn-ghost">🌐</a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/admin/companies/edit/<?= (int)$co->id ?>"
                       class="co-adm-btn co-adm-btn-primary">✏️ Bearbeiten</a>
                    <button type="button" class="co-adm-btn co-adm-btn-danger"
                            onclick="openCoDeleteModal(<?= (int)$co->id ?>, '<?= $sec->escape(addslashes($co->name)) ?>')">
                        🗑️
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
                        'design_primary_color'       => ['Primärfarbe (Buttons, Akzente)',     '#0891b2'],
                        'design_accent_color'         => ['Akzentfarbe (Hover, Links)',          '#0284c7'],
                        'design_card_bg'              => ['Card-Hintergrund',                    '#f0f9ff'],
                        'design_cta_color'            => ['CTA-Button-Farbe',                    '#0c4a6e'],
                        'archive_header_bg_from'      => ['Archiv-Header Gradient Von',          '#e0f2fe'],
                        'archive_header_bg_to'        => ['Archiv-Header Gradient Bis',          '#bae6fd'],
                        'archive_header_title_color'  => ['Archiv-Header Titelfarbe',            '#0c4a6e'],
                        'design_detail_header_bg'     => ['Detailseite Header Gradient Von',     '#e0f2fe'],
                        'design_detail_header_bg_to'  => ['Detailseite Header Gradient Bis',     '#bae6fd'],
                        'design_detail_header_color'  => ['Detailseite Titelfarbe',              '#0c4a6e'],
                        'design_detail_accent'        => ['Detailseite Akzentfarbe',             '#0891b2'],
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
                <h3>🏅 Badge-Farben (Partner-Stufen)</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Hintergrund- und Textfarben der Status-Badges auf den Karten und der Detailseite.</p>
                <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
                    <?php
                    $badgeColorFields = [
                        'design_badge_sponsor_bg'     => ['💜 Sponsor – Hintergrund',       '#f3e8ff'],
                        'design_badge_sponsor_color'   => ['💜 Sponsor – Textfarbe',         '#6b21a8'],
                        'design_badge_top_bg'          => ['🥇 Top-Partner – Hintergrund',   '#fef3c7'],
                        'design_badge_top_color'       => ['🥇 Top-Partner – Textfarbe',     '#92400e'],
                        'design_badge_partner_bg'      => ['🤝 Partner – Hintergrund',       '#f1f5f9'],
                        'design_badge_partner_color'   => ['🤝 Partner – Textfarbe',         '#475569'],
                        'design_badge_inactive_bg'     => ['🔒 Inaktiv – Hintergrund',       '#f1f5f9'],
                        'design_badge_inactive_color'  => ['🔒 Inaktiv – Textfarbe',         '#64748b'],
                        'design_partner_color'         => ['🤝 Karten-Rahmen: Partner',      '#9ca3af'],
                        'design_top_partner_color'     => ['🥇 Karten-Rahmen: Top-Partner',  '#d97706'],
                        'design_sponsor_color'         => ['💜 Karten-Rahmen: Sponsor',      '#7c3aed'],
                    ];
                    foreach ($badgeColorFields as $key => [$label, $default]):
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
                <h3>📐 Layout &amp; Anzeige</h3>
                <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Header-Icon (Emoji)</label>
                        <input type="text" name="archive_header_icon" id="txt_archive_header_icon" class="form-control"
                               value="<?= htmlspecialchars(html_entity_decode($s['archive_header_icon'] ?? '🏢', ENT_HTML5, 'UTF-8')) ?>"
                               maxlength="8" style="font-size:1.2rem;text-align:center;" oninput="updateCoPreview()">
                        <small class="form-text">z.B. 🏢 🌐 💼</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">CTA-Button-Text</label>
                        <input type="text" name="design_cta_label" class="form-control" value="<?= htmlspecialchars($s['design_cta_label'] ?? 'Profil ansehen') ?>" placeholder="Profil ansehen" oninput="updateCoPreview()">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ecken-Radius (px)</label>
                        <input type="number" name="design_border_radius" class="form-control"
                               value="<?= (int)($s['design_border_radius'] ?? 12) ?>" min="0" max="32" oninput="updateCoPreview()">
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

                <p style="font-weight:600;font-size:.875rem;color:#374151;margin:.75rem 0 .5rem;">🏷️ Badges auf den Karten</p>
                <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:.75rem;">
                    <?php foreach ([
                        'design_show_sponsor_badge'     => '💜 Sponsor-Badge',
                        'design_show_top_partner_badge' => '🥇 Top-Partner-Badge',
                        'design_show_partner_badge'     => '🤝 Partner-Badge',
                        'design_show_inactive_badge'    => '🔒 Inaktiv-Badge',
                    ] as $key => $label): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="<?= $key ?>" value="1" <?= !empty($s[$key]) && $s[$key] !== '0' ? 'checked' : '' ?>>
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p style="font-weight:600;font-size:.875rem;color:#374151;margin:.75rem 0 .5rem;">💊 Pills auf den Karten</p>
                <div style="display:flex;flex-wrap:wrap;gap:1rem;">
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

            <div class="admin-card">
                <h3>👁️ Vorschau</h3>
                <div style="max-width:340px;">
                    <div id="prev-header" style="background:linear-gradient(135deg,<?= htmlspecialchars($s['archive_header_bg_from']) ?>,<?= htmlspecialchars($s['archive_header_bg_to']) ?>);padding:1.5rem;border-radius:<?= (int)$s['design_border_radius'] ?>px <?= (int)$s['design_border_radius'] ?>px 0 0;display:flex;align-items:center;gap:.75rem;">
                        <span id="prev-icon" style="font-size:2rem;"><?= htmlspecialchars(html_entity_decode($s['archive_header_icon'] ?? '🏢', ENT_HTML5, 'UTF-8')) ?></span>
                        <div>
                            <div id="prev-title" style="color:<?= htmlspecialchars($s['archive_header_title_color']) ?>;font-weight:800;font-size:1.1rem;"><?= htmlspecialchars($s['archive_title'] ?? 'Unternehmen') ?></div>
                            <div style="color:<?= htmlspecialchars($s['archive_header_title_color']) ?>;font-size:.8rem;opacity:.85;">Vorschau</div>
                        </div>
                    </div>
                    <div id="prev-body" style="background:<?= htmlspecialchars($s['design_card_bg']) ?>;padding:1rem;border:1px solid #bae6fd;border-top:none;border-radius:0 0 <?= (int)$s['design_border_radius'] ?>px <?= (int)$s['design_border_radius'] ?>px;">
                        <span id="prev-cta" style="display:inline-block;padding:.3rem .8rem;background:<?= htmlspecialchars($s['design_cta_color']) ?>;color:#fff;border-radius:6px;font-size:.8rem;font-weight:700;"><?= htmlspecialchars($s['design_cta_label'] ?? 'Profil ansehen') ?> →</span>
                    </div>
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
            function updateCoPreview(){
                var from  = (document.getElementById('txt_archive_header_bg_from')    ||{value:'#e0f2fe'}).value;
                var to    = (document.getElementById('txt_archive_header_bg_to')      ||{value:'#bae6fd'}).value;
                var color = (document.getElementById('txt_archive_header_title_color')||{value:'#0c4a6e'}).value;
                var cta   = (document.getElementById('txt_design_cta_color')          ||{value:'#0c4a6e'}).value;
                var icon  = (document.getElementById('txt_archive_header_icon')       ||{value:'🏢'}).value;
                var prev  = document.getElementById('prev-header');
                var body  = document.getElementById('prev-body');
                var icoEl = document.getElementById('prev-icon');
                var ttlEl = document.getElementById('prev-title');
                var ctaEl = document.getElementById('prev-cta');
                if(prev){ prev.style.background='linear-gradient(135deg,'+from+','+to+')'; }
                if(icoEl) icoEl.textContent = icon;
                if(ttlEl){ ttlEl.style.color=color; }
                if(body){ body.style.background=(document.getElementById('txt_design_card_bg')||{value:'#f0f9ff'}).value; }
                if(ctaEl){ ctaEl.style.background=cta; }
                var radEl = document.querySelector('[name="design_border_radius"]');
                if(radEl && prev){
                    var r=parseInt(radEl.value)||12;
                    prev.style.borderRadius=r+'px '+r+'px 0 0';
                    if(body) body.style.borderRadius='0 0 '+r+'px '+r+'px';
                }
                var ctaLblEl = document.querySelector('[name="design_cta_label"]');
                if(ctaEl && ctaLblEl) ctaEl.textContent = (ctaLblEl.value||'Profil ansehen')+' →';
            }
            window.updateCoPreview = updateCoPreview;
            // Alle txt_-Inputs triggern Preview
            document.querySelectorAll('[id^="txt_"]').forEach(function(el){
                el.addEventListener('input', updateCoPreview);
            });
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
            <div class="admin-card">
                <h3>🧭 Navigation</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Standardmäßig wird kein Link in der öffentlichen Hauptnavigation ausgegeben.</p>
                <div class="form-group" style="margin-top:.75rem;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="show_nav_link" value="1" <?= (string)($s['show_nav_link'] ?? '0') === '1' ? 'checked' : '' ?>>
                        Link in Hauptnavigation anzeigen
                    </label>
                    <small style="display:block;margin-top:.35rem;color:#64748b;">Wenn deaktiviert, bleibt die Seite erreichbar unter <code>/companies</code>, wird aber nicht im Hauptmenü verlinkt.</small>
                </div>
                <div class="form-group" style="margin-top:.75rem;">
                    <label class="form-label">Navigations-Label</label>
                    <input type="text" name="nav_label" class="form-control" value="<?= htmlspecialchars((string)($s['nav_label'] ?? 'Unternehmen'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Unternehmen">
                </div>
            </div>
            <div class="admin-card">
                <h3>ℹ️ Shortcode-Nutzung</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:.5rem;">Unternehmens-Liste per Shortcode in Seiteninhalte einbinden:</p>
                <div style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:.75rem 1rem;font-family:monospace;font-size:.875rem;color:#0c4a6e;">
                    [cms_companies limit="12" partner="1" sponsor="0"]
                </div>
                <div style="margin-top:.75rem;display:flex;flex-direction:column;gap:.35rem;">
                    <small style="color:#64748b;"><strong>limit</strong> – Anzahl Unternehmen (Standard: 12)</small>
                    <small style="color:#64748b;"><strong>partner</strong> – Nur Partner-Unternehmen anzeigen (1/0)</small>
                    <small style="color:#64748b;"><strong>sponsor</strong> – Nur Sponsoren anzeigen (1/0)</small>
                    <small style="color:#64748b;"><strong>industry</strong> – Nach Branche filtern (Branchenname)</small>
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

        let _coApproveForm = null;
        function openCoApproveModal(id, name, form) {
            document.getElementById('coApproveModalName').textContent = name;
            _coApproveForm = form;
            openModal('coApproveModal');
        }
        document.getElementById('coApproveModalConfirm')?.addEventListener('click', function() {
            if (_coApproveForm) _coApproveForm.submit();
        });
        </script>

        <!-- Approve Modal -->
        <div id="coApproveModal" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>✅ Unternehmen genehmigen</h3>
                    <button class="modal-close" onclick="closeModal('coApproveModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll <strong id="coApproveModalName"></strong> genehmigt und aktiviert werden?</p>
                    <p style="color:#166534;font-size:.875rem;">Das Profil wird sofort öffentlich sichtbar.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('coApproveModal')">Abbrechen</button>
                    <button type="button" class="btn btn-primary" id="coApproveModalConfirm">✅ Genehmigen</button>
                </div>
            </div>
        </div>
        <?php
        $this->end_admin_layout();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // render_form – Neu anlegen + Bearbeiten
    // ──────────────────────────────────────────────────────────────────────────

    public function render_form($company = null): void
    {
        $is_edit    = ($company !== null);
        $page_title = $is_edit ? 'Unternehmen bearbeiten' : 'Neues Unternehmen anlegen';
        $csrf_token = CMS\Security::instance()->generateToken('save_company');

        // Experten für Zuordnungs-Sektion laden
        $db       = CMS_Companies_Database::instance();
        $experts  = $db->get_available_experts();
        $assigned = $is_edit ? $db->get_company_experts((int)$company->id, false) : [];

        $this->start_admin_layout($page_title, 'companies');

        // Admin-CSS laden
        $adminCss = CMS_COMPANIES_PLUGIN_DIR . 'assets/css/companies-admin.css';
        $adminCssVersion = file_exists($adminCss) ? (string) filemtime($adminCss) : '1';
        echo '<link rel="stylesheet" href="' . CMS_COMPANIES_PLUGIN_URL . 'assets/css/companies-admin.css?v=' . $adminCssVersion . '">' . "\n";
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
        $this->end_admin_layout();
    }
}
