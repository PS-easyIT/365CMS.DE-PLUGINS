<?php
/**
 * Admin Interface für CMS Experts
 *
 * Design & Layout aligned with CMS Speakers Admin.
 *
 * @package CMS_Experts
 * @since 2.0.0
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class CMS_Experts_Admin
{
    private static ?self $instance = null;
    public static function instance(): self
    {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }
    private function __construct()
    {
        $menu_file = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menu_file) && !function_exists('renderAdminLayoutStart')) {
            require_once $menu_file;
        }
        CMS\Hooks::addAction('cms_admin_menu', [$this, 'register_admin_menu'], 10);
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }

    private function start_admin_layout(string $title, string $activePage): void
    {
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

    public function register_admin_menu(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Experten',
            'Experten',
            'manage_options',
            'experts',
            [self::class, 'render_plugin_page_bridge'],
            '👨‍💻',
            44
        );
    }

    public static function render_plugin_page_bridge(): void
    {
        $targetUrl = htmlspecialchars(SITE_URL . '/admin/experts', ENT_QUOTES, 'UTF-8');

        echo '<div class="admin-card"><p>Weiterleitung zur Experten-Verwaltung … <a href="' . $targetUrl . '">Falls nichts passiert, hier klicken</a>.</p></div>';
        echo '<script>window.location.replace(' . json_encode(SITE_URL . '/admin/experts', JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');</script>';
    }

    public function add_menu_item(array $items): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $items[] = [
            'type'   => 'item',
            'slug'   => 'experts',
            'label'  => 'Experten',
            'icon'   => '👨‍💻',
            'url'    => '/admin/experts',
            'active' => str_starts_with($path, '/admin/experts'),
        ];
        return $items;
    }

    // ─── LIST ────────────────────────────────────────────────
    public function render_list(array $data): void
    {
        $this->start_admin_layout('Experten', 'experts');

        // Admin-CSS einbinden
        $admin_css = CMS_EXPERTS_PLUGIN_DIR . 'assets/css/experts-admin.css';
        if (file_exists($admin_css)) {
            $adminCssVersion = (string) filemtime($admin_css);
            echo '<link rel="stylesheet" href="' . CMS_EXPERTS_PLUGIN_URL . 'assets/css/experts-admin.css?v=' . $adminCssVersion . '">' . "\n";
        }

        $experts   = $data['experts']  ?? [];
        $tab       = $data['tab']      ?? 'overview';
        $filter    = $data['filter']   ?? 'all';
        $search    = $data['search']   ?? '';
        $specs     = $data['specs']    ?? [];
        $presets   = $data['presets']  ?? ['general' => [], 'tech' => [], 'soft' => []];
        $settings  = $data['settings'] ?? [];
        $csrf      = $data['csrf']     ?? '';
        $companies = $data['companies'] ?? [];

        $s = array_merge([
            'archive_title'                => 'IT-Experten Netzwerk',
            'archive_description'          => 'Finden Sie qualifizierte IT-Experten für Ihr Projekt.',
            'archive_per_page'             => '12',
            'archive_header_icon'          => '👨‍💻',
            'archive_header_bg_from'       => '#f5ecd5',
            'archive_header_bg_to'         => '#ebe0c8',
            'archive_header_title_color'   => '#7c4700',
            'design_primary_color'         => '#5e72e4',
            'design_accent_color'          => '#8965e0',
            'design_card_bg'               => '#fffdf4',
            'design_border_radius'         => '12',
            'design_cta_color'             => '#c2410c',
            'design_card_style'            => 'default',
            'design_grid_columns'          => 'auto',
            'design_show_availability'     => '1',
            'design_show_rate'             => '0',
            'design_show_city'             => '1',
            'design_show_skills'           => '1',
            'design_show_specialization'   => '1',
            'detail_header_bg_from'        => '#1e293b',
            'detail_header_bg_to'          => '#334155',
            'detail_header_title_color'    => '#ffffff',
            'design_badge_avail_bg'        => '#d1fae5',
            'design_badge_avail_color'     => '#065f46',
            'design_badge_limited_bg'      => '#fef3c7',
            'design_badge_limited_color'   => '#92400e',
            'design_badge_booked_bg'       => '#fee2e2',
            'design_badge_booked_color'    => '#991b1b',
            'design_badge_partner_bg'      => 'rgba(156,163,175,0.15)',
            'design_badge_partner_color'   => '#6b7280',
            'design_badge_top_partner_bg'  => 'rgba(217,119,6,0.15)',
            'design_badge_top_partner_color'=> '#d97706',
            'design_badge_mvp_bg'          => 'rgba(251,191,36,0.2)',
            'design_badge_mvp_color'       => '#fbbf24',
        ], $settings);

        // Stats
        $total    = count($experts);
        $active   = count(array_filter($experts, fn($e) => ($e->status ?? '') === 'active'));
        $pending  = count(array_filter($experts, fn($e) => ($e->status ?? '') === 'pending'));
        $inactive = count(array_filter($experts, fn($e) => ($e->status ?? '') === 'inactive'));
        $avail    = count(array_filter($experts, fn($e) => ($e->availability ?? '') === 'available'));
        ?>
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>👨‍💻 Experten</h2>
                <p>Verwalte alle Experten-Profile, Fachrichtungen und Skills</p>
            </div>
            <div class="header-actions">
                <a href="<?= SITE_URL ?>/experts" class="btn btn-secondary" target="_blank">🌐 Öffentlich</a>
                <a href="<?= SITE_URL ?>/admin/experts/new" class="btn btn-primary">➕ Neuer Experte</a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Änderungen gespeichert.</div><?php endif; ?>
        <?php if (isset($_GET['approved'])): ?><div class="alert alert-success">✅ Experte genehmigt und aktiviert.</div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✅ Eintrag gelöscht.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error">❌ Fehler: <?= htmlspecialchars($_GET['error'] ?? '') ?></div><?php endif; ?>

        <!-- Tabs -->
        <div class="exp-tabs">
            <?php
            $tabs = [
                'overview'   => ['👀', 'Übersicht',      $pending > 0 ? " <span class='exp-tab-badge'>{$pending}</span>" : ''],
                'taxonomies' => ['📋', 'Fachrichtungen', ''],
                'skills'     => ['🔧', 'Skills Vorlagen', ''],
                'design'     => ['🎨', 'Design',         ''],
                'settings'   => ['⚙️', 'Einstellungen',  ''],
            ];
            foreach ($tabs as $slug => [$icon, $label, $badge]): ?>
                <a href="?tab=<?= $slug ?>" class="exp-tab <?= $tab === $slug ? 'active' : '' ?>">
                    <?= $icon ?> <?= $label ?><?= $badge ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($tab === 'overview'): ?>
        <!-- Stats -->
        <div class="dashboard-grid">
            <?php
            $stat_items = [
                ['👨‍💻', 'Gesamt',     $total],
                ['✅', 'Aktiv',      $active],
                ['⏳', 'Ausstehend', $pending],
                ['💜', 'Verfügbar',  $avail],
                ['⏸', 'Inaktiv',    $inactive],
            ];
            foreach ($stat_items as [$si_icon, $si_label, $si_value]): ?>
            <div class="stat-card">
                <div class="stat-icon"><?= $si_icon ?></div>
                <div class="stat-number"><?= (int)$si_value ?></div>
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
                    <input type="text" name="search" class="form-control" placeholder="Name, Ort, Position, Skill…" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:160px;">
                    <label class="form-label">Status</label>
                    <select name="filter" class="form-control">
                        <option value="all"      <?= $filter==='all'      ?'selected':'' ?>>Alle (<?= $total ?>)</option>
                        <option value="active"   <?= $filter==='active'   ?'selected':'' ?>>Aktiv (<?= $active ?>)</option>
                        <option value="pending"  <?= $filter==='pending'  ?'selected':'' ?>>Ausstehend (<?= $pending ?>)</option>
                        <option value="inactive" <?= $filter==='inactive' ?'selected':'' ?>>Inaktiv (<?= $inactive ?>)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">🔍 Filtern</button>
                <?php if ($search || $filter !== 'all'): ?><a href="?tab=overview" class="btn btn-secondary">✕ Reset</a><?php endif; ?>
            </form>
        </div>

        <!-- Expert Grid -->
        <?php if (empty($experts)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">👨‍💻</p>
            <p><strong>Noch keine Experten vorhanden</strong></p>
            <p class="text-muted">Erstelle den ersten Experten-Eintrag.</p>
            <a href="<?= SITE_URL ?>/admin/experts/new" class="btn btn-primary" style="margin-top:1rem;">➕ Experten anlegen</a>
        </div>
        <?php else: ?>
        <div class="exp-adm-grid">
            <?php foreach ($experts as $ex):
                $fn  = htmlspecialchars($ex->first_name ?? '');
                $ln  = htmlspecialchars($ex->last_name  ?? '');
                $name = trim("$fn $ln") ?: 'Unbekannt';
                $parts = preg_split('/\s+/', $name);
                $initials = mb_strtoupper(mb_substr($parts[0],0,1) . (isset($parts[1]) ? mb_substr($parts[1],0,1) : ''));
                $pcolors  = [['#5e72e4','#8965e0'],['#0891b2','#06b6d4'],['#16a34a','#22c55e'],['#7c3aed','#a855f7'],['#d97706','#f59e0b']];
                $cp  = $pcolors[abs(crc32($name)) % count($pcolors)];
                $bg  = "linear-gradient(135deg,{$cp[0]},{$cp[1]})";
                $st  = $ex->status ?? 'active';
                $stCfg = [
                    'active'   => ['✅ Aktiv',        '#065f46', '#d1fae5'],
                    'inactive' => ['⏸ Inaktiv',       '#374151', '#f1f5f9'],
                    'pending'  => ['⏳ Zur Prüfung',   '#92400e', '#fef3c7'],
                ];
                [$stLabel, $stColor, $stBg] = $stCfg[$st] ?? ['Unbekannt','#374151','#f3f4f6'];
                $isPending = $st === 'pending';
                $exAvail   = $ex->availability ?? 'available';
                $availLabels = ['available'=>'✅ Verfügbar','limited'=>'⚠️ Begrenzt','booked'=>'🔴 Nicht verfügbar'];
                $availColors = ['available'=>'#065f46','limited'=>'#78350f','booked'=>'#7f1d1d'];
                $availBg     = ['available'=>'#d1fae5','limited'=>'#fef3c7','booked'=>'#fee2e2'];
                $slug = method_exists('CMS_Experts_Database','generate_slug')
                    ? CMS_Experts_Database::generate_slug($ex) : $ex->id;
            ?>
            <div class="exp-adm-card <?= $isPending ? 'exp-adm-card--pending' : '' ?>">
                <?php if ($isPending): ?>
                    <div class="exp-adm-pending-bar">⏳ Wartet auf Genehmigung</div>
                <?php endif; ?>
                <div class="exp-adm-top">
                    <?php if (!empty($ex->photo_url)): ?>
                        <div class="exp-adm-avatar" style="background:#e0e7ff;">
                            <img src="<?= htmlspecialchars($ex->photo_url) ?>" alt="">
                        </div>
                    <?php else: ?>
                        <div class="exp-adm-avatar" style="background:<?= $bg ?>"><?= $initials ?></div>
                    <?php endif; ?>
                    <div class="exp-adm-identity">
                        <div class="exp-adm-badges">
                            <span class="status-badge" style="background:<?= $stBg ?>;color:<?= $stColor ?>;"><?= $stLabel ?></span>
                            <?php if (!$isPending): ?>
                            <span class="status-badge" style="background:<?= $availBg[$exAvail]??'#f1f5f9' ?>;color:<?= $availColors[$exAvail]??'#374151' ?>;"><?= $availLabels[$exAvail]??$exAvail ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="exp-adm-name"><?= $name ?></p>
                        <?php if (!empty($ex->position)): ?><p class="exp-adm-sub"><?= htmlspecialchars($ex->position) ?></p><?php endif; ?>
                        <?php if (!empty($ex->company)): ?><p class="exp-adm-sub exp-adm-sub--muted"><?= htmlspecialchars($ex->company) ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="exp-adm-pills">
                    <?php if (!empty($ex->location_city)): ?>
                        <span class="exp-adm-pill">📍 <?= htmlspecialchars($ex->location_city) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($ex->email)): ?>
                        <span class="exp-adm-pill">✉ <?= htmlspecialchars($ex->email) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($ex->experience_years) && (int)$ex->experience_years > 0): ?>
                        <span class="exp-adm-pill exp-adm-pill--accent">📅 <?= (int)$ex->experience_years ?> Jahre</span>
                    <?php endif; ?>
                    <?php if (!empty($ex->hourly_rate)): ?>
                        <span class="exp-adm-pill exp-adm-pill--accent">💶 <?= number_format((float)$ex->hourly_rate,0,',','.') ?> €/h</span>
                    <?php endif; ?>
                </div>
                <div class="exp-adm-footer">
                    <?php if ($isPending): ?>
                        <button type="button" class="exp-adm-btn exp-adm-btn-approve"
                                onclick="openApproveModal(<?= (int)$ex->id ?>, '<?= htmlspecialchars($name, ENT_QUOTES) ?>')">✓ Genehmigen</button>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/experts/<?= $slug ?>" class="exp-adm-btn exp-adm-btn-ghost" target="_blank">🌐</a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/admin/experts/edit/<?= (int)$ex->id ?>" class="exp-adm-btn exp-adm-btn-primary">✏️ Bearbeiten</a>
                    <button type="button" class="exp-adm-btn exp-adm-btn-danger"
                            onclick="openDeleteModal(<?= (int)$ex->id ?>, '<?= htmlspecialchars($name, ENT_QUOTES) ?>')">🗑️</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
        // ══════════════════════════════════════════════════════════════
        // TAB: FACHRICHTUNGEN
        // ══════════════════════════════════════════════════════════════
        elseif ($tab === 'taxonomies'):
            $roots    = [];
            $children = [];
            foreach ($specs as $sp) {
                if (!$sp->parent_id) {
                    $roots[] = $sp;
                } else {
                    $children[(int)$sp->parent_id][] = $sp;
                }
            }
        ?>
        <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start;">
            <div>
                <div class="admin-card">
                    <h3>📋 Vorhandene Fachrichtungen (<?= count($specs) ?>)</h3>
                    <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Hierarchische Kategorisierung der Experten-Fachgebiete.</p>
                    <?php if (empty($specs)): ?>
                        <p style="color:#94a3b8;font-style:italic;">Noch keine Fachrichtungen vorhanden.</p>
                    <?php else: ?>
                        <div class="exp-tax-list">
                        <?php foreach ($roots as $root): ?>
                            <div class="exp-tax-group">
                                <div class="exp-tax-root">
                                    <span>📂 <?= htmlspecialchars($root->name) ?></span>
                                    <form method="POST" action="<?= SITE_URL ?>/admin/experts/taxonomy/delete/<?= (int)$root->id ?>" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="exp-tax-del-btn"
                                                onclick="return confirm('«<?= htmlspecialchars($root->name, ENT_QUOTES) ?>» und alle Unter-Einträge löschen?')">×</button>
                                    </form>
                                </div>
                                <?php foreach ($children[(int)$root->id] ?? [] as $child): ?>
                                <div class="exp-tax-child">
                                    <span>↳ <?= htmlspecialchars($child->name) ?></span>
                                    <form method="POST" action="<?= SITE_URL ?>/admin/experts/taxonomy/delete/<?= (int)$child->id ?>" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="exp-tax-del-btn"
                                                onclick="return confirm('«<?= htmlspecialchars($child->name, ENT_QUOTES) ?>» löschen?')">×</button>
                                    </form>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="admin-card">
                <h3>➕ Neue Fachrichtung</h3>
                <form method="POST" action="<?= SITE_URL ?>/admin/experts/taxonomy/add">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="form-group">
                        <label class="form-label">Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="spec_name" class="form-control" required placeholder="z.B. iOS-Entwicklung">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Übergeordnete Kategorie</label>
                        <select name="parent_id" class="form-control">
                            <option value="0">— Hauptkategorie —</option>
                            <?php foreach ($roots as $root): ?>
                                <option value="<?= (int)$root->id ?>"><?= htmlspecialchars($root->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">📂 Anlegen</button>
                </form>
            </div>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════
        // TAB: SKILLS VORLAGEN
        // ══════════════════════════════════════════════════════════════
        elseif ($tab === 'skills'):
            $typeLabels = [
                'general' => ['🔷','Programmierung','Programmiersprachen & Grundlagen'],
                'tech'    => ['⚙️','Skills','Frameworks, Tools & Plattformen'],
                'soft'    => ['💬','Persönliche Stärken','Soft Skills & methodische Kompetenzen'],
            ];
        ?>
        <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start;">
            <div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.25rem;">
                <?php foreach ($typeLabels as $type => [$icon, $label, $desc]): ?>
                    <div class="admin-card">
                        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;">
                            <span style="font-size:1.25rem;"><?= $icon ?></span>
                            <div>
                                <strong style="font-size:.875rem;"><?= $label ?></strong>
                                <div style="font-size:.72rem;color:#64748b;"><?= $desc ?></div>
                            </div>
                        </div>
                        <div class="exp-skill-tags">
                            <?php foreach ($presets[$type] ?? [] as $sk): ?>
                                <span class="exp-skill-tag">
                                    <?= htmlspecialchars($sk->skill_name) ?>
                                    <form method="POST" action="<?= SITE_URL ?>/admin/experts/skillpreset/delete/<?= (int)$sk->id ?>" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="exp-skill-del"
                                                onclick="return confirm('«<?= htmlspecialchars($sk->skill_name, ENT_QUOTES) ?>» löschen?')">×</button>
                                    </form>
                                </span>
                            <?php endforeach; ?>
                            <?php if (empty($presets[$type])): ?>
                                <span style="font-size:.75rem;color:#94a3b8;">Noch keine Einträge.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <div class="admin-card">
                <h3>➕ Neue Skill-Vorlage</h3>
                <form method="POST" action="<?= SITE_URL ?>/admin/experts/skillpreset/add">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="form-group">
                        <label class="form-label">Skill-Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="skill_name" class="form-control" required placeholder="z.B. Kubernetes">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Typ <span style="color:#ef4444;">*</span></label>
                        <select name="skill_type" class="form-control">
                            <option value="general">🔷 Programmierung</option>
                            <option value="tech">⚙️ Skills</option>
                            <option value="soft">💬 Persönliche Stärken</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">🔧 Hinzufügen</button>
                </form>
            </div>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════
        // TAB: DESIGN
        // ══════════════════════════════════════════════════════════════
        elseif ($tab === 'design'):
        ?>
        <form method="POST" action="<?= SITE_URL ?>/admin/experts/settings/save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="settings_tab" value="design">

            <div class="admin-card">
                <h3>🎨 Farbpalette</h3>
                <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
                    <?php
                    $colorFields = [
                        'design_primary_color'       => ['Primärfarbe (Buttons, Links)',       '#5e72e4'],
                        'design_accent_color'        => ['Akzentfarbe (Hover, Highlights)',    '#8965e0'],
                        'design_cta_color'           => ['CTA-Button-Farbe',                   '#c2410c'],
                        'design_card_bg'             => ['Card-Hintergrund',                   '#fffdf4'],
                        'archive_header_bg_from'     => ['Archiv-Header Gradient Von',         '#f5ecd5'],
                        'archive_header_bg_to'       => ['Archiv-Header Gradient Bis',         '#ebe0c8'],
                        'archive_header_title_color' => ['Archiv-Header Titelfarbe',           '#7c4700'],
                        'detail_header_bg_from'      => ['Detailseite Header Von',             '#1e293b'],
                        'detail_header_bg_to'        => ['Detailseite Header Bis',             '#334155'],
                        'detail_header_title_color'  => ['Detailseite Titelfarbe',             '#ffffff'],
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
                <h3>🏅 Badge-Farben</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Hintergrund- und Textfarben der Status-Badges auf der Experten-Detail- und Übersichtsseite.</p>
                <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
                    <?php
                    $badgeColorFields = [
                        'design_badge_avail_bg'          => ['Verfügbar – Hintergrund',     '#d1fae5'],
                        'design_badge_avail_color'       => ['Verfügbar – Textfarbe',        '#065f46'],
                        'design_badge_limited_bg'        => ['Begrenzt – Hintergrund',       '#fef3c7'],
                        'design_badge_limited_color'     => ['Begrenzt – Textfarbe',         '#92400e'],
                        'design_badge_booked_bg'         => ['Nicht verfügbar – Hintergrund','#fee2e2'],
                        'design_badge_booked_color'      => ['Nicht verfügbar – Textfarbe',  '#991b1b'],
                        'design_badge_partner_bg'        => ['Partner – Hintergrund',        'rgba(156,163,175,0.15)'],
                        'design_badge_partner_color'     => ['Partner – Textfarbe',          '#6b7280'],
                        'design_badge_top_partner_bg'    => ['Top-Partner – Hintergrund',    'rgba(217,119,6,0.15)'],
                        'design_badge_top_partner_color' => ['Top-Partner – Textfarbe',      '#d97706'],
                        'design_badge_mvp_bg'            => ['⭐ MVP – Hintergrund',         'rgba(251,191,36,0.2)'],
                        'design_badge_mvp_color'         => ['⭐ MVP – Textfarbe',           '#fbbf24'],
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
                <p style="margin-top:.75rem;padding:.5rem .75rem;background:#fffbeb;border-left:3px solid #fbbf24;border-radius:4px;font-size:.8rem;color:#92400e;">
                    ⚠️ Partner-, Top-Partner- und MVP-Badges haben <strong>rgba-Werte</strong> als Standard. Das Farbwähler-Feld unterstützt keine Alpha-Werte – rgba()-Werte bitte direkt im Textfeld eingeben.
                </p>
            </div>

            <div class="admin-card">
                <h3>📐 Layout & Anzeige</h3>
                <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Border-Radius (px)</label>
                        <input type="number" name="design_border_radius" class="form-control" value="<?= htmlspecialchars($s['design_border_radius']) ?>" min="0" max="32">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grid-Spalten</label>
                        <select name="design_grid_columns" class="form-control">
                            <?php foreach (['auto'=>'Automatisch (responsive)','2'=>'2 Spalten','3'=>'3 Spalten','4'=>'4 Spalten'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($s['design_grid_columns']??'auto')===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Karten-Stil</label>
                        <select name="design_card_style" class="form-control">
                            <?php foreach (['default'=>'Standard','compact'=>'Kompakt','horizontal'=>'Horizontal'] as $v=>$l): ?>
                            <option value="<?= $v ?>" <?= ($s['design_card_style']??'default')===$v?'selected':'' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Header-Icon (Emoji)</label>
                        <input type="text" name="archive_header_icon" class="form-control" value="<?= htmlspecialchars(html_entity_decode($s['archive_header_icon'] ?? '👨‍💻', ENT_HTML5, 'UTF-8')) ?>" placeholder="👨‍💻">
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-top:.75rem;">
                    <?php foreach ([
                        'design_show_availability'   => '✅ Verfügbarkeitsstatus',
                        'design_show_rate'           => '💶 Stundensatz',
                        'design_show_city'           => '📍 Stadt / Ort',
                        'design_show_skills'         => '🔧 Skills / Kenntnisse',
                        'design_show_specialization' => '📋 Fachrichtung',
                    ] as $key => $label): ?>
                    <label class="checkbox-label">
                        <input type="hidden" name="<?= $key ?>" value="0">
                        <input type="checkbox" name="<?= $key ?>" value="1" <?= !empty($s[$key]) && $s[$key] !== '0' ? 'checked' : '' ?>>
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top:.75rem;padding:.5rem .75rem;background:#f0fdf4;border-left:3px solid #86efac;border-radius:4px;font-size:.8rem;color:#166534;">
                    ℹ️ <strong>Deaktivierte Elemente</strong> werden sowohl in der <strong>Card-Ansicht</strong> als auch auf der <strong>Detailseite</strong> ausgeblendet.
                </p>
            </div>

            <div class="admin-card">
                <h3>👁️ Vorschau</h3>
                <div style="max-width:340px;">
                    <div id="prev-header" style="background:linear-gradient(135deg,<?= htmlspecialchars($s['archive_header_bg_from']) ?>,<?= htmlspecialchars($s['archive_header_bg_to']) ?>);padding:1.5rem;border-radius:<?= (int)$s['design_border_radius'] ?>px <?= (int)$s['design_border_radius'] ?>px 0 0;display:flex;align-items:center;gap:.75rem;">
                        <span id="prev-icon" style="font-size:2rem;"><?= htmlspecialchars(html_entity_decode($s['archive_header_icon'] ?? '👨‍💻', ENT_HTML5, 'UTF-8')) ?></span>
                        <div>
                            <div id="prev-title" style="color:<?= htmlspecialchars($s['archive_header_title_color']) ?>;font-weight:800;font-size:1.1rem;"><?= htmlspecialchars($s['archive_title'] ?? 'IT-Experten') ?></div>
                            <div style="color:<?= htmlspecialchars($s['archive_header_title_color']) ?>;font-size:.8rem;opacity:.85;">Vorschau</div>
                        </div>
                    </div>
                    <div id="prev-body" style="background:<?= htmlspecialchars($s['design_card_bg']) ?>;padding:1rem;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 <?= (int)$s['design_border_radius'] ?>px <?= (int)$s['design_border_radius'] ?>px;">
                        <div style="display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:.5rem;">
                            <span style="display:inline-block;padding:.15rem .5rem;background:<?= htmlspecialchars($s['design_badge_avail_bg']) ?>;color:<?= htmlspecialchars($s['design_badge_avail_color']) ?>;border-radius:50px;font-size:.7rem;font-weight:600;">✅ Verfügbar</span>
                            <span style="display:inline-block;padding:.15rem .5rem;background:<?= htmlspecialchars($s['design_badge_mvp_bg']) ?>;color:<?= htmlspecialchars($s['design_badge_mvp_color']) ?>;border-radius:50px;font-size:.7rem;font-weight:600;">⭐ MVP</span>
                        </div>
                        <span id="prev-cta" style="display:inline-block;padding:.3rem .8rem;background:<?= htmlspecialchars($s['design_cta_color']) ?>;color:#fff;border-radius:6px;font-size:.8rem;font-weight:700;">Profil ansehen →</span>
                    </div>
                </div>
            </div>

            <div class="admin-card form-actions-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Design speichern</button>
                </div>
            </div>
        </form>

        <?php
        // ══════════════════════════════════════════════════════════════
        // TAB: EINSTELLUNGEN
        // ══════════════════════════════════════════════════════════════
        elseif ($tab === 'settings'):
        ?>
        <form method="POST" action="<?= SITE_URL ?>/admin/experts/settings/save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="settings_tab" value="settings">

            <div class="admin-card">
                <h3>📋 Archive-Seite</h3>
                <div class="form-group">
                    <label class="form-label">Seitentitel</label>
                    <input type="text" name="archive_title" class="form-control" value="<?= htmlspecialchars($s['archive_title']) ?>" placeholder="IT-Experten Netzwerk">
                </div>
                <div class="form-group">
                    <label class="form-label">Untertitel / Beschreibung</label>
                    <input type="text" name="archive_description" class="form-control" value="<?= htmlspecialchars($s['archive_description']) ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Einträge pro Seite</label>
                    <input type="number" name="archive_per_page" class="form-control" value="<?= htmlspecialchars($s['archive_per_page']) ?>" min="3" max="100">
                </div>
            </div>

            <div class="admin-card">
                <h3>ℹ️ Shortcode-Nutzung</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:.5rem;">Experten-Liste per Shortcode in Seiteninhalte einbinden:</p>
                <div style="background:#eef2ff;border:1px solid #c7d2fe;border-radius:8px;padding:.75rem 1rem;font-family:monospace;font-size:.875rem;color:#4338ca;">
                    [cms_experts limit="12" availability="available" city="Berlin"]
                </div>
                <div style="margin-top:.75rem;display:flex;flex-direction:column;gap:.35rem;">
                    <small style="color:#64748b;"><strong>limit</strong> – Anzahl Experten (Standard: 12)</small>
                    <small style="color:#64748b;"><strong>availability</strong> – Filter: available | limited | booked</small>
                    <small style="color:#64748b;"><strong>city</strong> – Filter nach Stadt (z.B. Berlin, München)</small>
                    <small style="color:#64748b;"><strong>status</strong> – Filter: active | pending | inactive</small>
                </div>
            </div>

            <div class="admin-card form-actions-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <!-- Delete Modal -->
        <div id="deleteModal" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>🗑️ Experten löschen</h3>
                    <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll <strong id="deleteModalName"></strong> wirklich gelöscht werden?</p>
                    <p style="color:#ef4444;font-size:.875rem;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('deleteModal')">Abbrechen</button>
                    <form method="POST" id="deleteModalForm" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div id="approveModal" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>✅ Experte genehmigen</h3>
                    <button class="modal-close" onclick="closeModal('approveModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll <strong id="approveModalName"></strong> genehmigt und aktiviert werden?</p>
                    <p style="color:#166534;font-size:.875rem;">Das Profil wird sofort öffentlich sichtbar.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('approveModal')">Abbrechen</button>
                    <form method="POST" id="approveModalForm" action="" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-primary">✅ Genehmigen</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function openDeleteModal(id, name) {
            document.getElementById('deleteModalName').textContent = name;
            document.getElementById('deleteModalForm').action = '<?= SITE_URL ?>/admin/experts/delete/' + id;
            openModal('deleteModal');
        }

        function openApproveModal(id, name) {
            document.getElementById('approveModalName').textContent = name;
            document.getElementById('approveModalForm').action = '<?= SITE_URL ?>/admin/experts/approve/' + id;
            openModal('approveModal');
        }

        // Live-Vorschau für Design-Tab
        (function() {
            const prevHeader = document.getElementById('prev-header');
            const prevIcon   = document.getElementById('prev-icon');
            const prevTitle  = document.getElementById('prev-title');
            const prevBody   = document.getElementById('prev-body');
            const prevCta    = document.getElementById('prev-cta');
            if (!prevHeader) return;

            function syncColor(name) {
                const clr = document.getElementById('clr_' + name);
                const txt = document.getElementById('txt_' + name);
                if (clr && txt) {
                    clr.addEventListener('input', () => { txt.value = clr.value; updatePreview(); });
                    txt.addEventListener('input', () => { if (/^#[0-9a-f]{6}$/i.test(txt.value)) clr.value = txt.value; updatePreview(); });
                }
            }

            <?php
            $allColorKeys = array_merge(array_keys($colorFields), array_keys($badgeColorFields));
            ?>
            const allColorKeys = <?= json_encode($allColorKeys) ?>;
            allColorKeys.forEach(k => syncColor(k));

            const iconInput   = document.querySelector('[name="archive_header_icon"]');
            const radiusInput = document.querySelector('[name="design_border_radius"]');
            if (iconInput)   iconInput.addEventListener('input', updatePreview);
            if (radiusInput) radiusInput.addEventListener('input', updatePreview);

            function updatePreview() {
                const getVal = (name, def) => document.getElementById('txt_' + name)?.value || def;
                const from   = getVal('archive_header_bg_from', '#f5ecd5');
                const to     = getVal('archive_header_bg_to', '#ebe0c8');
                const tColor = getVal('archive_header_title_color', '#7c4700');
                const cardBg = getVal('design_card_bg', '#fffdf4');
                const ctaClr = getVal('design_cta_color', '#c2410c');
                const radius = radiusInput?.value || '12';
                const icon   = iconInput?.value || '👨‍💻';

                prevHeader.style.background = `linear-gradient(135deg,${from},${to})`;
                prevHeader.style.borderRadius = `${radius}px ${radius}px 0 0`;
                if (prevIcon)  prevIcon.textContent = icon;
                if (prevTitle) prevTitle.style.color = tColor;
                prevTitle?.parentElement?.querySelectorAll('div').forEach(d => d.style.color = tColor);
                if (prevBody) {
                    prevBody.style.background = cardBg;
                    prevBody.style.borderRadius = `0 0 ${radius}px ${radius}px`;
                }
                if (prevCta) prevCta.style.background = ctaClr;
            }
        })();
        </script>
        <?php
        $this->end_admin_layout();
    }

    // ─── FORM ────────────────────────────────────────────────
    public function render_form($expert = null, array $extras = []): void
    {
        $is_edit     = $expert !== null;
        $csrf_token  = CMS\Security::instance()->generateToken('expert_form');
        $page_title  = $is_edit ? '✏️ Experte bearbeiten' : '👨‍💻 Neuer Experte';

        $this->start_admin_layout($is_edit ? 'Experte bearbeiten' : 'Neuer Experte', 'experts');

        // Admin-CSS einbinden
        $admin_css = CMS_EXPERTS_PLUGIN_DIR . 'assets/css/experts-admin.css';
        if (file_exists($admin_css)) {
            $adminCssVersion = (string) filemtime($admin_css);
            echo '<link rel="stylesheet" href="' . CMS_EXPERTS_PLUGIN_URL . 'assets/css/experts-admin.css?v=' . $adminCssVersion . '">' . "\n";
        }

        $slug = '';
        if ($is_edit && method_exists('CMS_Experts_Database', 'generate_slug')) {
            $slug = CMS_Experts_Database::generate_slug($expert);
        }
        ?>
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2><?= $page_title ?></h2>
                <?php if ($is_edit): ?><p>ID #<?= (int)$expert->id ?></p><?php endif; ?>
            </div>
            <div class="header-actions">
                <a href="<?= SITE_URL ?>/admin/experts" class="btn btn-secondary">← Zurück</a>
                <?php if ($is_edit && $slug): ?>
                <a href="<?= SITE_URL ?>/experts/<?= htmlspecialchars($slug) ?>" class="btn btn-secondary" target="_blank">🌐 Ansehen</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_GET['success']) || isset($_GET['saved'])): ?><div class="alert alert-success">✅ Gespeichert.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error">❌ Fehler: <?= htmlspecialchars($_GET['error'] ?? '') ?></div><?php endif; ?>

        <form method="POST" action="<?= SITE_URL ?>/admin/experts/save" class="expert-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <?php if ($is_edit): ?>
            <input type="hidden" name="expert_id" value="<?= (int)$expert->id ?>">
            <?php endif; ?>

            <?php CMS_Experts_Meta_Boxes::instance()->render_expert_form_fields($expert, $extras); ?>

            <div class="admin-card form-actions-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 <?= $is_edit ? 'Änderungen speichern' : 'Experten anlegen' ?></button>
                    <a href="<?= SITE_URL ?>/admin/experts" class="btn btn-secondary">Abbrechen</a>
                    <?php if ($is_edit): ?><span class="form-actions__hint">Zuletzt gespeichert: <?= htmlspecialchars(date('d.m.Y H:i', strtotime($expert->updated_at ?? 'now'))) ?></span><?php endif; ?>
                </div>
            </div>
        </form>
        <?php
        $this->end_admin_layout();
    }
}
