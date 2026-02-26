<?php
/**
 * Admin Interface für CMS Experts
 *
 * @package CMS_Experts
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Experts_Admin
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
        $this->init_hooks();
    }

    /**
     * Lädt admin-menu.php einmalig, damit renderAdminLayoutStart/End verfügbar sind.
     */
    private function loadAdminMenu(): void
    {
        $menu_file = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menu_file) && !function_exists('renderAdminLayoutStart')) {
            require_once $menu_file;
        }
    }

    /**
     * Initialisiert Hooks
     */
    private function init_hooks(): void
    {
        // Admin Menu Filter
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }

    /**
     * Fügt Admin-Menü-Item hinzu
     *
     * @param array $menuItems Existing menu items
     * @return array Modified menu items
     */
    public function add_menu_item(array $menuItems): array
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $isActive = strpos($currentPath, '/admin/experts') === 0;

        $menuItems[] = [
            'type'   => 'item',
            'slug'   => 'experts',
            'label'  => 'Experten',
            'icon'   => '👨‍💻',
            'url'    => '/admin/experts',
            'active' => $isActive,
        ];

        return $menuItems;
    }

    /**
     * Rendert Experten-Verwaltung (5 Tabs)
     *
     * @param array $data {experts, tab, filter, specs, presets, settings, csrf}
     */
    public function render_list(array $data): void
    {
        $this->loadAdminMenu();
        renderAdminLayoutStart('Experten', 'experts');

        $experts  = $data['experts']  ?? [];
        $tab      = $data['tab']      ?? 'overview';
        $filter   = $data['filter']   ?? 'all';
        $specs    = $data['specs']    ?? [];
        $presets  = $data['presets']  ?? ['general' => [], 'tech' => [], 'soft' => []];
        $settings = $data['settings'] ?? [];
        $csrf     = $data['csrf']     ?? '';
        $sec      = CMS\Security::instance();

        // Default-Einstellungen
        $s = array_merge([
            'archive_title'                => 'IT-Experten Netzwerk',
            'archive_description'          => 'Finden Sie qualifizierte IT-Experten für Ihr Projekt.',
            'archive_per_page'             => '12',
            'archive_header_icon'          => '&#128100;',
            'archive_header_bg_from'       => '#f5ecd5',
            'archive_header_bg_to'         => '#ebe0c8',
            'archive_header_title_color'   => '#7c4700',
            'design_primary_color'         => '#5e72e4',
            'design_accent_color'          => '#8965e0',
            'design_card_style'            => 'default',
            'design_show_availability'     => '1',
            'design_show_rate'             => '0',
            'design_show_city'             => '1',
            'design_border_radius'         => '12',
            'design_grid_columns'          => 'auto',
            'design_cta_color'             => '#c2410c',
            'design_card_bg'               => '#fffdf4',
            'design_show_skills'           => '1',
            'design_show_specialization'   => '1',
        ], $settings);

        // Stats
        $total    = count($experts);
        $active   = count(array_filter($experts, fn($e) => ($e->status ?? '') === 'active'));
        $pending  = count(array_filter($experts, fn($e) => ($e->status ?? '') === 'pending'));
        $inactive = count(array_filter($experts, fn($e) => ($e->status ?? '') === 'inactive'));
        ?>

        <!-- Page Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.5rem;">
            <h2 style="margin:0;">👨‍💻 Experten</h2>
            <a href="<?= SITE_URL ?>/admin/experts/new" class="btn btn-primary">+ Experten anlegen</a>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_GET['approved'])): ?><div class="alert alert-success" style="margin-bottom:1rem;">✓ Experte genehmigt und aktiviert.</div><?php endif; ?>
        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success" style="margin-bottom:1rem;">✓ Änderungen gespeichert.</div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success" style="margin-bottom:1rem;">✓ Eintrag gelöscht.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error" style="margin-bottom:1rem;">✕ Fehler aufgetreten.</div><?php endif; ?>

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
                <a href="?tab=<?= $slug ?>" class="exp-tab <?= $tab === $slug ? 'exp-tab--active' : '' ?>">
                    <?= $icon ?> <?= $label ?><?= $badge ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════
        // TAB 1: ÜBERSICHT
        // ══════════════════════════════════════════════════════════════
        if ($tab === 'overview'):
        ?>

        <!-- Filter -->
        <div class="exp-filter-bar">
            <?php
            $filters = ['all' => "Alle ({$total})", 'pending' => "⏳ Ausstehend ({$pending})", 'active' => "✅ Aktiv ({$active})", 'inactive' => "⏸ Inaktiv ({$inactive})"];
            foreach ($filters as $fk => $fl): ?>
                <a href="?tab=overview&filter=<?= $fk ?>"
                   class="exp-filter-btn <?= $filter === $fk ? 'exp-filter-btn--active' : '' ?>">
                    <?= $fl ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($experts)): ?>
            <div class="member-card" style="text-align:center;padding:3rem;color:#64748b;">
                <div style="font-size:3rem;margin-bottom:1rem;">👨‍💻</div>
                <p>Keine Experten <?= $filter !== 'all' ? 'in diesem Status ' : '' ?>gefunden.</p>
                <?php if ($filter === 'all'): ?>
                    <a href="<?= SITE_URL ?>/admin/experts/new" class="btn btn-primary" style="margin-top:1rem;">Ersten Experten erstellen</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="exp-adm-grid">
        <?php foreach ($experts as $ex):
            $fn   = $ex->first_name ?? '';
            $ln   = $ex->last_name  ?? '';
            $name = trim($fn . ' ' . $ln) ?: 'Unbekannt';
            $colors = [
                ['#5e72e4','#8965e0'],['#0891b2','#06b6d4'],
                ['#16a34a','#22c55e'],['#7c3aed','#a855f7'],['#d97706','#f59e0b'],
            ];
            $cp = $colors[abs(crc32($name)) % count($colors)];
            $bg = "linear-gradient(135deg,{$cp[0]},{$cp[1]})";
            $parts    = preg_split('/\s+/', trim($name));
            $initials = mb_strtoupper(mb_substr($parts[0],0,1) . (isset($parts[1]) ? mb_substr($parts[1],0,1) : ''));
            $st = $ex->status ?? 'active';
            $stCfg = [
                'active'   => ['Aktiv',       '#065f46','#d1fae5'],
                'inactive' => ['Inaktiv',     '#374151','#f1f5f9'],
                'pending'  => ['Zur Prüfung', '#92400e','#fef3c7'],
            ];
            [$stLabel,$stColor,$stBg] = $stCfg[$st] ?? ['Unbekannt','#374151','#f3f4f6'];
            $isPending = $st === 'pending';
            $slug = method_exists('CMS_Experts_Database','generate_slug')
                ? CMS_Experts_Database::generate_slug($ex) : $ex->id;
        ?>
            <div class="exp-adm-card <?= $isPending ? 'exp-adm-card--pending' : '' ?>">
                <?php if ($isPending): ?>
                    <div class="exp-adm-pending-bar">⏳ Wartet auf Genehmigung</div>
                <?php endif; ?>
                <div class="exp-adm-top">
                    <div class="exp-adm-avatar" style="background:<?= $bg ?>"><?= $initials ?></div>
                    <div class="exp-adm-ident">
                        <span class="exp-adm-badge" style="color:<?= $stColor ?>;background:<?= $stBg ?>"><?= $stLabel ?></span>
                        <p class="exp-adm-name"><?= $sec->escape($name) ?></p>
                        <?php if (!empty($ex->position)): ?>
                            <p class="exp-adm-sub"><?= $sec->escape($ex->position) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                $pills = [];
                if (!empty($ex->email))         $pills[] = ['✉️', $sec->escape($ex->email)];
                if (!empty($ex->location_city)) $pills[] = ['📍', $sec->escape($ex->location_city)];
                if ($pills): ?>
                <div class="exp-adm-pills">
                    <?php foreach ($pills as [$ico,$txt]): ?>
                        <span class="exp-adm-pill"><?= $ico ?> <?= $txt ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="exp-adm-foot">
                    <?php if ($isPending): ?>
                        <form method="POST" action="<?= SITE_URL ?>/admin/experts/approve/<?= (int)$ex->id ?>" style="display:contents;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" class="exp-adm-btn exp-adm-btn-approve"
                                    onclick="return confirm('Experten-Profil genehmigen?')">✓ Genehmigen</button>
                        </form>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/experts/<?= $slug ?>" target="_blank" class="exp-adm-btn exp-adm-btn-ghost">Ansehen</a>
                    <?php endif; ?>
                    <a href="<?= SITE_URL ?>/admin/experts/edit/<?= (int)$ex->id ?>" class="exp-adm-btn exp-adm-btn-primary">✏️ Bearbeiten</a>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
        // ══════════════════════════════════════════════════════════════
        // TAB 2: FACHRICHTUNGEN
        // ══════════════════════════════════════════════════════════════
        elseif ($tab === 'taxonomies'):
            // Baum aufbauen
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

            <!-- Fachrichtungs-Liste -->
            <div>
                <h3 style="margin:0 0 1rem;">Vorhandene Fachrichtungen (<?= count($specs) ?>)</h3>
                <?php if (empty($specs)): ?>
                    <p style="color:#64748b;">Noch keine Fachrichtungen vorhanden.</p>
                <?php else: ?>
                    <div class="exp-tax-list">
                    <?php foreach ($roots as $root): ?>
                        <div class="exp-tax-group">
                            <div class="exp-tax-root">
                                <span>📂 <?= $sec->escape($root->name) ?></span>
                                <form method="POST" action="<?= SITE_URL ?>/admin/experts/taxonomy/delete/<?= (int)$root->id ?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="exp-tax-del-btn"
                                            onclick="return confirm('«<?= $sec->escape($root->name) ?>» und alle Unter-Einträge löschen?')">×</button>
                                </form>
                            </div>
                            <?php foreach ($children[(int)$root->id] ?? [] as $child): ?>
                            <div class="exp-tax-child">
                                <span>↳ <?= $sec->escape($child->name) ?></span>
                                <form method="POST" action="<?= SITE_URL ?>/admin/experts/taxonomy/delete/<?= (int)$child->id ?>" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="exp-tax-del-btn"
                                            onclick="return confirm('«<?= $sec->escape($child->name) ?>» löschen?')">×</button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Neue Fachrichtung -->
            <div class="exp-side-card">
                <h3 style="margin:0 0 1rem;">+ Neue Fachrichtung</h3>
                <form method="POST" action="<?= SITE_URL ?>/admin/experts/taxonomy/add">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="exp-form-group">
                        <label>Name *</label>
                        <input type="text" name="spec_name" required placeholder="z.B. iOS-Entwicklung">
                    </div>
                    <div class="exp-form-group">
                        <label>Übergeordnete Kategorie</label>
                        <select name="parent_id">
                            <option value="0">— Hauptkategorie —</option>
                            <?php foreach ($roots as $root): ?>
                                <option value="<?= (int)$root->id ?>"><?= $sec->escape($root->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Anlegen</button>
                </form>
            </div>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════
        // TAB 3: SKILLS VORLAGEN
        // ══════════════════════════════════════════════════════════════
        elseif ($tab === 'skills'):
            $typeLabels = [
                'general' => ['🔷','Allgemein (Sprachen)','Programmiersprachen & Grundlagen'],
                'tech'    => ['⚙️','Technisch (Tools)','Frameworks, Tools & Plattformen'],
                'soft'    => ['💬','Soft Skills','Persönliche & methodische Kompetenzen'],
            ];
        ?>
        <div style="display:grid;grid-template-columns:1fr 300px;gap:1.5rem;align-items:start;">
            <div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1.25rem;">
                <?php foreach ($typeLabels as $type => [$icon, $label, $desc]): ?>
                    <div class="exp-side-card">
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
                                    <?= $sec->escape($sk->skill_name) ?>
                                    <form method="POST" action="<?= SITE_URL ?>/admin/experts/skillpreset/delete/<?= (int)$sk->id ?>" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="exp-skill-del"
                                                onclick="return confirm('«<?= $sec->escape($sk->skill_name) ?>» löschen?')">×</button>
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

            <!-- Neue Skill hinzufügen -->
            <div class="exp-side-card">
                <h3 style="margin:0 0 1rem;">+ Neue Skill-Vorlage</h3>
                <form method="POST" action="<?= SITE_URL ?>/admin/experts/skillpreset/add">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="exp-form-group">
                        <label>Skill-Name *</label>
                        <input type="text" name="skill_name" required placeholder="z.B. Kubernetes">
                    </div>
                    <div class="exp-form-group">
                        <label>Typ *</label>
                        <select name="skill_type">
                            <option value="general">🔷 Allgemein</option>
                            <option value="tech">⚙️ Technisch</option>
                            <option value="soft">💬 Soft Skill</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Hinzufügen</button>
                </form>
            </div>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════
        // TAB 4: DESIGN
        // ══════════════════════════════════════════════════════════════
        elseif ($tab === 'design'):
        ?>
        <form method="POST" action="<?= SITE_URL ?>/admin/experts/settings/save">
            <input type="hidden" name="csrf_token"    value="<?= $csrf ?>">
            <input type="hidden" name="settings_tab"  value="design">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;max-width:860px;">

                <div class="exp-side-card">
                    <h3 style="margin:0 0 1.25rem;">🎨 Farben</h3>
                    <?php
                    $color_fields = [
                        'design_primary_color' => ['Primärfarbe (Buttons, Akzente)',    'exp_primary_prev'],
                        'design_accent_color'  => ['Akzentfarbe (Hover, Links)',         'exp_accent_prev'],
                        'design_cta_color'     => ['CTA-Button-Farbe',                  'exp_cta_prev'],
                        'design_card_bg'       => ['Karten-Hintergrundfarbe',           'exp_cardbg_prev'],
                    ];
                    foreach ($color_fields as $name => [$label, $previewId]):
                        $val = htmlspecialchars($s[$name] ?? '#5e72e4');
                    ?>
                    <div class="exp-form-group">
                        <label><?= $label ?></label>
                        <div style="display:flex;align-items:center;gap:.75rem;">
                            <input type="color" name="<?= $name ?>" value="<?= $val ?>" id="<?= $previewId ?>_pick"
                                   style="width:48px;height:36px;padding:2px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;">
                            <input type="text" id="<?= $previewId ?>"
                                   value="<?= $val ?>"
                                   style="width:90px;font-family:monospace;" readonly>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <div class="exp-form-group">
                        <label>Ecken-Radius (px)</label>
                        <input type="range" name="design_border_radius" min="0" max="24"
                               value="<?= (int)$s['design_border_radius'] ?>"
                               oninput="document.getElementById('exp_br_val').textContent=this.value+'px'">
                        <span id="exp_br_val" style="font-size:.8rem;color:#64748b;"><?= (int)$s['design_border_radius'] ?>px</span>
                    </div>
                </div>

                <div class="exp-side-card">
                    <h3 style="margin:0 0 1.25rem;">📐 Layout &amp; Sichtbarkeit</h3>
                    <div class="exp-form-group">
                        <label>Karten-Stil</label>
                        <?php foreach (['default'=>'Standard (groß)','compact'=>'Kompakt','horizontal'=>'Horizontal'] as $val=>$lbl): ?>
                            <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;margin:.3rem 0;">
                                <input type="radio" name="design_card_style" value="<?= $val ?>"
                                       <?= $s['design_card_style'] === $val ? 'checked' : '' ?>>
                                <?= $lbl ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="exp-form-group">
                        <label>Spalten (Archiv-Seite)</label>
                        <select name="design_grid_columns">
                            <?php foreach (['auto'=>'Automatisch','2'=>'2 Spalten','3'=>'3 Spalten','4'=>'4 Spalten'] as $val=>$lbl): ?>
                                <option value="<?= $val ?>" <?= $s['design_grid_columns'] === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="exp-form-group">
                        <label>Sichtbare Felder auf Karte</label>
                        <?php $checkboxes = [
                            'design_show_availability'   => 'Verfügbarkeit',
                            'design_show_rate'           => 'Stundensatz',
                            'design_show_city'           => 'Stadt',
                            'design_show_skills'         => 'Skills / Kenntnisse',
                            'design_show_specialization' => 'Fachrichtung / Spezialisierung',
                        ]; ?>
                        <?php foreach ($checkboxes as $ck => $cl): ?>
                            <label style="display:flex;align-items:center;gap:.5rem;font-weight:400;margin:.3rem 0;">
                                <input type="hidden" name="<?= $ck ?>" value="0">
                                <input type="checkbox" name="<?= $ck ?>" value="1"
                                       <?= ($s[$ck] ?? '0') === '1' ? 'checked' : '' ?>>
                                <?= $cl ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Header-Einstellungen (volle Breite) -->
            <div class="exp-side-card" style="margin-top:1.5rem;max-width:860px;">
                <h3 style="margin:0 0 1.25rem;">🖼️ Archiv-Header (Seite /experts)</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                    <div class="exp-form-group">
                        <label>Header-Icon (Emoji)</label>
                        <input type="text" name="archive_header_icon"
                               value="<?= htmlspecialchars(html_entity_decode($s['archive_header_icon'] ?? '👤', ENT_HTML5, 'UTF-8')) ?>"
                               placeholder="👤" maxlength="8" style="font-size:1.4rem;text-align:center;width:60px;">
                        <small style="color:#94a3b8;display:block;margin-top:.3rem;">z.B. 👨‍💻 🌐 💼</small>
                    </div>
                    <div class="exp-form-group">
                        <label>Hintergrund von (Farbe)</label>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <input type="color" name="archive_header_bg_from"
                                   value="<?= htmlspecialchars($s['archive_header_bg_from'] ?? '#f5ecd5') ?>"
                                   id="exp_hbg_from_pick"
                                   style="width:44px;height:34px;padding:2px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;">
                            <input type="text" id="exp_hbg_from_prev"
                                   value="<?= htmlspecialchars($s['archive_header_bg_from'] ?? '#f5ecd5') ?>"
                                   style="width:80px;font-family:monospace;" readonly>
                        </div>
                    </div>
                    <div class="exp-form-group">
                        <label>Hintergrund bis (Farbe)</label>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <input type="color" name="archive_header_bg_to"
                                   value="<?= htmlspecialchars($s['archive_header_bg_to'] ?? '#ebe0c8') ?>"
                                   id="exp_hbg_to_pick"
                                   style="width:44px;height:34px;padding:2px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;">
                            <input type="text" id="exp_hbg_to_prev"
                                   value="<?= htmlspecialchars($s['archive_header_bg_to'] ?? '#ebe0c8') ?>"
                                   style="width:80px;font-family:monospace;" readonly>
                        </div>
                    </div>
                    <div class="exp-form-group" style="grid-column:span 1;">
                        <label>Titelfarbe</label>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <input type="color" name="archive_header_title_color"
                                   value="<?= htmlspecialchars($s['archive_header_title_color'] ?? '#7c4700') ?>"
                                   id="exp_htitle_pick"
                                   style="width:44px;height:34px;padding:2px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;">
                            <input type="text" id="exp_htitle_prev"
                                   value="<?= htmlspecialchars($s['archive_header_title_color'] ?? '#7c4700') ?>"
                                   style="width:80px;font-family:monospace;" readonly>
                        </div>
                    </div>
                    <div class="exp-form-group" style="grid-column:span 2;display:flex;align-items:flex-end;padding-bottom:.1rem;">
                        <div id="exp_header_preview"
                             style="flex:1;padding:.6rem 1rem;border-radius:8px;display:flex;align-items:center;gap:.75rem;font-weight:700;font-size:.95rem;min-height:46px;">
                            <span id="exp_prev_icon" style="font-size:1.5rem;"></span>
                            <span id="exp_prev_title" style=""></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detail-Seite Einstellungen (volle Breite) -->
            <div class="exp-side-card" style="margin-top:1.5rem;max-width:860px;">
                <h3 style="margin:0 0 1.25rem;">📄 Experten-Detailseite (/experts/name)</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">

                    <?php
                    $detail_colors = [
                        'design_detail_header_bg'    => ['Header-Hintergrundfarbe',   'exp_dhbg_prev',  '#1e293b'],
                        'design_detail_header_color' => ['Header-Textfarbe',          'exp_dhcol_prev', '#ffffff'],
                        'design_detail_accent'       => ['Akzentfarbe (Links etc.)',  'exp_dhacc_prev', '#5e72e4'],
                    ];
                    foreach ($detail_colors as $name => [$label, $prevId, $default]):
                        $val = htmlspecialchars($s[$name] ?? $default);
                    ?>
                    <div class="exp-form-group">
                        <label><?= $label ?></label>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <input type="color" name="<?= $name ?>" value="<?= $val ?>" id="<?= $prevId ?>_pick"
                                   style="width:44px;height:34px;padding:2px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;">
                            <input type="text" id="<?= $prevId ?>"
                                   value="<?= $val ?>"
                                   style="width:80px;font-family:monospace;" readonly>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="exp-form-group" style="grid-column:span 3;"><hr style="border:0;border-top:1px solid #f1f5f9;margin:.5rem 0;"></div>
                    <div style="grid-column:span 3;font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:-.5rem;">Status-Badge Farben</div>

                    <?php
                    $status_colors = [
                        'design_status_available_color' => ['Verfügbar',         'exp_savail_prev', '#14532d'],
                        'design_status_limited_color'   => ['Begrenzt',          'exp_slimit_prev', '#7c4a03'],
                        'design_status_booked_color'    => ['Nicht verfügbar',   'exp_sbook_prev',  '#7f1d1d'],
                    ];
                    foreach ($status_colors as $name => [$label, $prevId, $default]):
                        $val = htmlspecialchars($s[$name] ?? $default);
                    ?>
                    <div class="exp-form-group">
                        <label><?= $label ?></label>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <input type="color" name="<?= $name ?>" value="<?= $val ?>" id="<?= $prevId ?>_pick"
                                   style="width:44px;height:34px;padding:2px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;">
                            <input type="text" id="<?= $prevId ?>" value="<?= $val ?>"
                                   style="width:80px;font-family:monospace;" readonly>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="exp-form-group" style="grid-column:span 3;"><hr style="border:0;border-top:1px solid #f1f5f9;margin:.5rem 0;"></div>
                    <div style="grid-column:span 3;font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;margin-bottom:-.5rem;">Partner-Badge Farben</div>

                    <?php
                    $partner_colors = [
                        'design_partner_color'     => ['Partner',    'exp_ppart_prev',  '#9ca3af'],
                        'design_top_partner_color' => ['Top-Partner','exp_ptop_prev',   '#d97706'],
                        'design_sponsor_color'     => ['Sponsor',    'exp_pspons_prev', '#7c3aed'],
                    ];
                    foreach ($partner_colors as $name => [$label, $prevId, $default]):
                        $val = htmlspecialchars($s[$name] ?? $default);
                    ?>
                    <div class="exp-form-group">
                        <label><?= $label ?></label>
                        <div style="display:flex;align-items:center;gap:.5rem;">
                            <input type="color" name="<?= $name ?>" value="<?= $val ?>" id="<?= $prevId ?>_pick"
                                   style="width:44px;height:34px;padding:2px;border-radius:6px;border:1px solid #e2e8f0;cursor:pointer;">
                            <input type="text" id="<?= $prevId ?>" value="<?= $val ?>"
                                   style="width:80px;font-family:monospace;" readonly>
                        </div>
                    </div>
                    <?php endforeach; ?>

                </div>
            </div>

            <div style="margin-top:1.5rem;">
                <button type="submit" class="btn btn-primary">💾 Design speichern</button>
                <small style="margin-left:1rem;color:#64748b;">Änderungen wirken sich auf die öffentliche Experten-Seite aus.</small>
            </div>
        </form>
        <script>
        // Live Color Sync
        const colorSyncs = [
            ['design_primary_color',          'exp_primary_prev'],
            ['design_accent_color',           'exp_accent_prev'],
            ['design_cta_color',              'exp_cta_prev'],
            ['design_card_bg',                'exp_cardbg_prev'],
            ['archive_header_bg_from',        'exp_hbg_from_prev'],
            ['archive_header_bg_to',          'exp_hbg_to_prev'],
            ['archive_header_title_color',    'exp_htitle_prev'],
            ['design_detail_header_bg',       'exp_dhbg_prev'],
            ['design_detail_header_color',    'exp_dhcol_prev'],
            ['design_detail_accent',          'exp_dhacc_prev'],
            ['design_status_available_color', 'exp_savail_prev'],
            ['design_status_limited_color',   'exp_slimit_prev'],
            ['design_status_booked_color',    'exp_sbook_prev'],
            ['design_partner_color',          'exp_ppart_prev'],
            ['design_top_partner_color',      'exp_ptop_prev'],
            ['design_sponsor_color',          'exp_pspons_prev'],
        ];
        colorSyncs.forEach(([name, previewId]) => {
            const pick = document.querySelector(`[name="${name}"]`);
            const prev = document.getElementById(previewId);
            if (pick && prev) pick.addEventListener('input', () => prev.value = pick.value);
        });

        // Header live preview
        function updateHeaderPreview() {
            const from  = document.querySelector('[name="archive_header_bg_from"]')?.value || '#f5ecd5';
            const to    = document.querySelector('[name="archive_header_bg_to"]')?.value   || '#ebe0c8';
            const color = document.querySelector('[name="archive_header_title_color"]')?.value || '#7c4700';
            const icon  = document.querySelector('[name="archive_header_icon"]')?.value || '👤';
            const title = document.querySelector('[name="archive_title"]')?.value || 'Experten';
            const prev  = document.getElementById('exp_header_preview');
            if (prev) {
                prev.style.background = `linear-gradient(135deg,${from} 0%,${to} 100%)`;
                prev.style.color = color;
            }
            const iconEl  = document.getElementById('exp_prev_icon');
            const titleEl = document.getElementById('exp_prev_title');
            if (iconEl)  iconEl.textContent  = icon;
            if (titleEl) titleEl.textContent = title;
            if (titleEl) titleEl.style.color = color;
        }
        ['archive_header_bg_from','archive_header_bg_to','archive_header_title_color','archive_header_icon'].forEach(n => {
            document.querySelector(`[name="${n}"]`)?.addEventListener('input', updateHeaderPreview);
        });
        updateHeaderPreview();
        </script>

        <?php
        // ══════════════════════════════════════════════════════════════
        // TAB 5: EINSTELLUNGEN
        // ══════════════════════════════════════════════════════════════
        elseif ($tab === 'settings'):
        ?>
        <form method="POST" action="<?= SITE_URL ?>/admin/experts/settings/save" style="max-width:680px;">
            <input type="hidden" name="csrf_token"   value="<?= $csrf ?>">
            <input type="hidden" name="settings_tab" value="settings">

            <div class="exp-side-card" style="margin-bottom:1.25rem;">
                <h3 style="margin:0 0 1.25rem;">📄 Archiv-Seite (/experts)</h3>
                <div class="exp-form-group">
                    <label>Seitentitel</label>
                    <input type="text" name="archive_title"
                           value="<?= htmlspecialchars($s['archive_title']) ?>"
                           placeholder="IT-Experten Netzwerk">
                </div>
                <div class="exp-form-group">
                    <label>Beschreibungstext</label>
                    <textarea name="archive_description" rows="3"
                              placeholder="Kurze Beschreibung für Besucher..."><?= htmlspecialchars($s['archive_description']) ?></textarea>
                    <small style="color:#94a3b8;">Wird als Einleitungstext auf der Übersichtsseite angezeigt.</small>
                </div>
                <div class="exp-form-group">
                    <label>Experten pro Seite</label>
                    <input type="number" name="archive_per_page" min="4" max="100" step="4"
                           value="<?= (int)($s['archive_per_page'] ?? 12) ?>" style="width:100px;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
        </form>
        <?php endif; ?>

        <style>
        /* ── Tabs ── */
        .exp-tabs{display:flex;gap:.4rem;flex-wrap:wrap;margin-bottom:1.75rem;border-bottom:2px solid #e2e8f0;}
        .exp-tab{display:inline-flex;align-items:center;gap:.35rem;padding:.55rem 1.1rem;border-radius:8px 8px 0 0;font-size:.8125rem;font-weight:600;color:#64748b;text-decoration:none;transition:all .15s;border-bottom:2px solid transparent;margin-bottom:-2px;}
        .exp-tab:hover{color:#5e72e4;background:#f8faff;}
        .exp-tab--active{color:#5e72e4;border-bottom-color:#5e72e4;background:#f8faff;}
        .exp-tab-badge{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 4px;background:#ef4444;color:#fff;border-radius:50px;font-size:.65rem;font-weight:800;}
        /* ── Filter ── */
        .exp-filter-bar{display:flex;flex-wrap:wrap;gap:.4rem;margin-bottom:1.5rem;}
        .exp-filter-btn{display:inline-block;padding:.35rem .9rem;border-radius:50px;font-size:.78rem;font-weight:600;color:#475569;background:#f1f5f9;text-decoration:none;border:1px solid #e2e8f0;transition:all .15s;}
        .exp-filter-btn:hover{background:#e2e8f0;}
        .exp-filter-btn--active{background:#5e72e4;color:#fff;border-color:#5e72e4;}
        /* ── Cards ── */
        .exp-adm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.25rem;}
        .exp-adm-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;display:flex;flex-direction:column;gap:.75rem;transition:box-shadow .2s;overflow:hidden;}
        .exp-adm-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.09);}
        .exp-adm-card--pending{border:2px solid #fca5a5;background:linear-gradient(135deg,#fff5f5 0%,#fff 70%);}
        .exp-adm-pending-bar{background:#fee2e2;color:#991b1b;font-size:.72rem;font-weight:700;padding:.3rem .75rem;border-radius:6px;text-align:center;letter-spacing:.03em;}
        .exp-adm-top{display:flex;gap:.75rem;align-items:flex-start;}
        .exp-adm-avatar{flex-shrink:0;width:48px;height:48px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1rem;font-weight:800;color:#fff;box-shadow:0 2px 8px rgba(0,0,0,.18);}
        .exp-adm-ident{flex:1;min-width:0;display:flex;flex-direction:column;gap:.2rem;}
        .exp-adm-badge{display:inline-block;padding:.15rem .5rem;border-radius:50px;font-size:.68rem;font-weight:700;width:fit-content;}
        .exp-adm-name{font-size:.95rem;font-weight:700;color:#1e293b;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .exp-adm-sub{font-size:.75rem;color:#64748b;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .exp-adm-pills{display:flex;flex-wrap:wrap;gap:.3rem;}
        .exp-adm-pill{display:inline-flex;align-items:center;gap:.2rem;padding:.2rem .5rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:50px;font-size:.72rem;color:#374151;white-space:nowrap;max-width:220px;overflow:hidden;text-overflow:ellipsis;}
        .exp-adm-foot{display:flex;gap:.5rem;padding-top:.75rem;border-top:1px solid #f1f5f9;margin-top:auto;flex-wrap:wrap;}
        .exp-adm-btn{display:inline-flex;align-items:center;gap:.25rem;padding:.35rem .8rem;border-radius:7px;font-size:.78rem;font-weight:600;text-decoration:none;white-space:nowrap;transition:opacity .15s;cursor:pointer;border:none;}
        .exp-adm-btn:hover{opacity:.8;}
        .exp-adm-btn-primary{background:#5e72e4;color:#fff;}
        .exp-adm-btn-ghost{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;}
        .exp-adm-btn-approve{background:#16a34a;color:#fff;}
        /* ── Taxonomies ── */
        .exp-tax-list{display:flex;flex-direction:column;gap:.5rem;}
        .exp-tax-group{background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:hidden;}
        .exp-tax-root{display:flex;align-items:center;justify-content:space-between;padding:.6rem .875rem;background:#f8fafc;font-weight:600;font-size:.875rem;color:#1e293b;}
        .exp-tax-child{display:flex;align-items:center;justify-content:space-between;padding:.45rem .875rem .45rem 1.75rem;font-size:.8125rem;color:#374151;border-top:1px solid #f1f5f9;}
        .exp-tax-del-btn{background:none;border:none;color:#dc2626;cursor:pointer;font-size:1rem;font-weight:700;padding:0 .3rem;opacity:.6;transition:opacity .15s;}
        .exp-tax-del-btn:hover{opacity:1;}
        /* ── Skills ── */
        .exp-skill-tags{display:flex;flex-wrap:wrap;gap:.3rem;}
        .exp-skill-tag{display:inline-flex;align-items:center;gap:.2rem;padding:.2rem .5rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:50px;font-size:.75rem;color:#374151;}
        .exp-skill-del{background:none;border:none;color:#dc2626;cursor:pointer;font-size:.85rem;font-weight:700;padding:0 .1rem;opacity:.6;transition:opacity .15s;line-height:1;}
        .exp-skill-del:hover{opacity:1;}
        /* ── Side Card & Forms ── */
        .exp-side-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;}
        .exp-form-group{margin-bottom:.875rem;}
        .exp-form-group label{display:block;font-size:.78rem;font-weight:600;color:#374151;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.35rem;}
        .exp-form-group input[type=text],.exp-form-group input[type=number],.exp-form-group select,.exp-form-group textarea{width:100%;padding:.45rem .7rem;border:1px solid #e2e8f0;border-radius:7px;font-size:.875rem;box-sizing:border-box;}
        .exp-form-group input:focus,.exp-form-group select:focus,.exp-form-group textarea:focus{outline:none;border-color:#5e72e4;box-shadow:0 0 0 3px rgba(94,114,228,.12);}
        .exp-form-group textarea{resize:vertical;}
        </style>
        <?php
        renderAdminLayoutEnd();
    }

    /**
     * Rendert Experten-Formular (Neu anlegen + Bearbeiten)
     *
     * @param object|null $expert  Expert-Objekt oder null bei Neuanlage
     * @param array       $extras  ['certifications','projects','education','meta','skills']
     */
    public function render_form($expert = null, array $extras = []): void
    {
        $is_edit     = $expert !== null;
        $csrf_token  = CMS\Security::instance()->generateToken('expert_form');
        $page_title  = $is_edit ? 'Experten bearbeiten' : 'Neuen Experten anlegen';

        $this->loadAdminMenu();
        renderAdminLayoutStart($page_title, 'experts');
        ?>
        <!-- Page Header -->
        <div class="admin-page-header">
            <h2><?php echo htmlspecialchars($page_title); ?></h2>
            <a href="<?php echo SITE_URL; ?>/admin/experts" class="btn">← Zur Übersicht</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✓ Experte wurde gespeichert.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">⚠️ Fehler beim Speichern. Bitte alle Pflichtfelder prüfen.</div>
        <?php endif; ?>

        <div class="admin-card">
            <form method="POST" action="<?php echo SITE_URL; ?>/admin/experts/save" class="expert-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <?php if ($is_edit): ?>
                <input type="hidden" name="expert_id" value="<?php echo (int)$expert->id; ?>">
                <?php endif; ?>

                <?php CMS_Experts_Meta_Boxes::instance()->render_expert_form_fields($expert, $extras); ?>

                <div class="form-actions" style="margin-top:2rem;display:flex;gap:1rem;">
                    <button type="submit" class="btn btn-primary">
                        <?php echo $is_edit ? '✓ Änderungen speichern' : '+ Experten anlegen'; ?>
                    </button>
                    <a href="<?php echo SITE_URL; ?>/admin/experts" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </div>
        <?php
        renderAdminLayoutEnd();
    }
}
