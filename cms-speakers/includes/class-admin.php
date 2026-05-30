<?php
/**
 * Admin Interface fuer CMS Speakers
 * @package CMS_Speakers
 * @since 2.0.0
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class CMS_Speakers_Admin
{
    private static ?self $instance = null;
    public static function instance(): self
    {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }
    private function __construct()
    {
        $this->load_admin_menu_helpers();
        CMS\Hooks::addAction('cms_admin_menu', [$this, 'register_admin_menu'], 10);
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }

    private function load_admin_menu_helpers(): void
    {
        if (function_exists('renderAdminLayoutStart') && function_exists('add_menu_page')) {
            return;
        }

        $candidates = [
            ABSPATH . 'includes/functions/admin-menu.php',
            ABSPATH . 'admin/partials/admin-menu.php',
        ];

        foreach ($candidates as $menu_file) {
            if (file_exists($menu_file)) {
                require_once $menu_file;
                if (function_exists('renderAdminLayoutStart') && function_exists('add_menu_page')) {
                    return;
                }
            }
        }
    }

    private function start_admin_layout(string $title, string $activePage): void
    {
        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $activePage);
            return;
        }

        $pageTitle = $title;
        $header = ABSPATH . 'admin/partials/header.php';
        $sidebar = ABSPATH . 'admin/partials/sidebar.php';
        if (file_exists($header)) {
            require_once $header;
        }
        if (file_exists($sidebar)) {
            require_once $sidebar;
        }
    }

    private function end_admin_layout(): void
    {
        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
            return;
        }

        $footer = ABSPATH . 'admin/partials/footer.php';
        if (file_exists($footer)) {
            require_once $footer;
        }
    }

    public function register_admin_menu(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Speaker',
            'Speaker',
            'manage_options',
            'speakers',
            [self::class, 'render_plugin_page_bridge'],
            'SP',
            45
        );
    }

    public static function render_plugin_page_bridge(): void
    {
        if (class_exists('CMS\\Auth') && !CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        if (class_exists('CMS_Speakers_Post_Type')) {
            try {
                CMS_Speakers_Post_Type::instance()->admin_list();
                return;
            } catch (\Throwable $e) {
                error_log('CMS Speakers admin bridge: ' . $e->getMessage());
            }
        }

        $targetUrl = htmlspecialchars(SITE_URL . '/admin/speakers', ENT_QUOTES, 'UTF-8');
        echo '<div class="admin-card"><p>Die Speaker-Verwaltung konnte nicht direkt geladen werden. <a href="' . $targetUrl . '">Zur Speaker-Verwaltung wechseln</a>.</p></div>';
    }

    public function add_menu_item(array $items): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $items[] = [
            'type'   => 'item',
            'slug'   => 'speakers',
            'label'  => 'Speaker',
            'icon'   => 'SP',
            'url'    => '/admin/speakers',
            'active' => str_starts_with($path, '/admin/speakers'),
        ];
        return $items;
    }

    // ─── LIST ────────────────────────────────────────────────
    public function render_list(array $data): void
    {
        $this->start_admin_layout('Speaker', 'speakers');

        // Admin-CSS einbinden
        $admin_css = CMS_SPEAKERS_PLUGIN_DIR . 'assets/css/speakers-admin.css';
        if (file_exists($admin_css)) {
            $adminCssVersion = (string) filemtime($admin_css);
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_SPEAKERS_PLUGIN_URL . 'assets/css/speakers-admin.css?v=' . $adminCssVersion, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        $speakers  = $data['speakers']  ?? [];
        $tab       = $data['tab']       ?? 'overview';
        $filter    = $data['filter']    ?? 'all';
        $search    = $data['search']    ?? '';
        $settings  = $data['settings']  ?? [];
        $csrf      = $data['csrf']      ?? '';
        $approveCsrf = $data['approve_csrf'] ?? '';
        $companies = $data['companies'] ?? [];

        $s = array_merge([
            'show_nav_link'              => '0',
            'show_main_nav_item'         => '0',
            'nav_label'                  => 'Speaker',
            'archive_title'              => 'Speaker Directory',
            'archive_description'        => 'Finden Sie den passenden Redner für Ihr Event',
            'archive_per_page'           => '12',
            'archive_header_icon'        => 'SP',
            'archive_header_bg_from'     => '#6d28d9',
            'archive_header_bg_to'       => '#a855f7',
            'archive_header_title_color' => '#ffffff',
            'design_primary_color'       => '#8b5cf6',
            'design_accent_color'        => '#7c3aed',
            'design_card_bg'             => '#faf5ff',
            'design_border_radius'       => '12',
            'design_cta_label'           => 'Profil ansehen',
            'design_show_availability'   => '1',
            'design_show_mvp_badge'      => '1',
            'design_show_formats'        => '1',
            'design_show_topics'         => '1',
            'design_grid_columns'        => 'auto',
            'detail_header_bg_from'      => '#4c1d95',
            'detail_header_bg_to'        => '#7c3aed',
            'detail_header_title_color'  => '#ffffff',
            'design_badge_avail_bg'      => '#d1fae5',
            'design_badge_avail_color'   => '#065f46',
            'design_badge_limited_bg'    => '#fef3c7',
            'design_badge_limited_color' => '#92400e',
            'design_badge_booked_bg'     => '#fee2e2',
            'design_badge_booked_color'  => '#991b1b',
            'design_badge_mvp_bg'        => 'rgba(251,191,36,0.2)',
            'design_badge_mvp_color'     => '#fbbf24',
            'design_badge_verified_bg'   => 'rgba(255,255,255,0.15)',
            'design_badge_verified_color'=> '#ffffff',
        ], $settings);

        // Stats
        $total    = count($speakers);
        $active   = count(array_filter($speakers, fn($e) => ($e->status ?? '') === 'active'));
        $pending  = count(array_filter($speakers, fn($e) => ($e->status ?? '') === 'pending'));
        $featured = count(array_filter($speakers, fn($e) => ($e->is_featured ?? 0)));
        $verified = count(array_filter($speakers, fn($e) => ($e->is_verified ?? 0)));
        $avail    = count(array_filter($speakers, fn($e) => ($e->availability ?? '') === 'available'));
        ?>
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>Speaker</h2>
                <p>Verwalte alle Speaker-Profile, Themen und Auftritte</p>
            </div>
            <div class="header-actions">
                <a href="<?= htmlspecialchars(SITE_URL . '/speakers', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">Öffentlich</a>
                <a href="<?= htmlspecialchars(SITE_URL . '/admin/speakers/new', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Neuer Speaker</a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Änderungen gespeichert.</div><?php endif; ?>
        <?php if (isset($_GET['approved'])): ?><div class="alert alert-success">Speaker genehmigt und aktiviert.</div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Speaker gelöscht.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error">Fehler: <?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <!-- Tabs -->
        <div class="spk-tabs">
            <?php
            $tabs = [
                'overview'  => ['OV', 'Übersicht'],
                'topics'    => ['TG', 'Themen'],
                'design'    => ['UI', 'Design'],
                'settings'  => ['CFG', 'Einstellungen'],
            ];
            foreach ($tabs as $slug => [$icon, $label]): ?>
                <a href="?tab=<?= $slug ?>" class="spk-tab <?= $tab === $slug ? 'active' : '' ?>">
                    <?= $icon ?> <?= $label ?>
                    <?php if ($slug === 'overview' && $pending > 0): ?>
                        <span class="nav-badge" style="background:#f59e0b;color:#fff;font-size:.7rem;padding:1px 6px;border-radius:9px;margin-left:4px;"><?= $pending ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($tab === 'overview'): ?>
        <!-- Stats -->
        <div class="dashboard-grid">
            <?php
            $stat_items = [
                ['ALL', 'Gesamt',     $total,    ''],
                ['ACT', 'Aktiv',      $active,   ''],
                ['AVL', 'Verfügbar',  $avail,    ''],
                ['TOP', 'Featured',   $featured, ''],
                ['✔',  'Verifiziert',$verified, ''],
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
                    <input type="text" name="search" class="form-control" placeholder="Name, Ort, Position, Thema…" value="<?= htmlspecialchars((string) $search, ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:160px;">
                    <label class="form-label">Status / Typ</label>
                    <select name="filter" class="form-control">
                        <option value="all"       <?= $filter==='all'       ?'selected':'' ?>>Alle (<?= $total ?>)</option>                        <option value="pending"   <?= $filter==='pending'   ?'selected':'' ?>>Ausstehend (<?= $pending ?>)</option>                        <option value="available" <?= $filter==='available' ?'selected':'' ?>>Verfügbar (<?= $avail ?>)</option>
                        <option value="featured"  <?= $filter==='featured'  ?'selected':'' ?>>Featured (<?= $featured ?>)</option>
                        <option value="verified"  <?= $filter==='verified'  ?'selected':'' ?>>Verifiziert (<?= $verified ?>)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filtern</button>
                <?php if ($search || $filter !== 'all'): ?><a href="?tab=overview" class="btn btn-secondary">✕ Reset</a><?php endif; ?>
            </form>
        </div>

        <!-- Speaker Grid -->
        <?php if (empty($speakers)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">Keine Daten</p>
            <p><strong>Noch keine Speaker vorhanden</strong></p>
            <p class="text-muted">Erstelle den ersten Speaker-Eintrag.</p>
            <a href="<?= htmlspecialchars(SITE_URL . '/admin/speakers/new', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary" style="margin-top:1rem;">Speaker anlegen</a>
        </div>
        <?php else: ?>
        <div class="spk-adm-grid">
            <?php foreach ($speakers as $sp):
                $fn  = htmlspecialchars((string) ($sp->first_name ?? ''), ENT_QUOTES, 'UTF-8');
                $ln  = htmlspecialchars((string) ($sp->last_name  ?? ''), ENT_QUOTES, 'UTF-8');
                $name = trim("$fn $ln") ?: 'Unbekannt';
                $parts = preg_split('/\s+/', $name);
                $initials = mb_strtoupper(mb_substr($parts[0],0,1) . (isset($parts[1]) ? mb_substr($parts[1],0,1) : ''));
                $spStatus  = $sp->status ?? 'active';
                $isPending = $spStatus === 'pending';
                $spAvail = $sp->availability ?? 'available';
                $availLabels = ['available'=>'Verfügbar','limited'=>'Begrenzt','booked'=>'Ausgebucht'];
                $availColors = ['available'=>'#065f46','limited'=>'#78350f','booked'=>'#7f1d1d'];
                $availBg     = ['available'=>'#d1fae5','limited'=>'#fef3c7','booked'=>'#fee2e2'];
                $travel = $sp->travel_radius ?? 'national';
                $travelLabel = ['local'=>'Lokal','regional'=>'Regional','national'=>'DACH','international'=>'International','worldwide'=>'Weltweit'][$travel] ?? $travel;
                $formats = json_decode($sp->formats ?? '[]', true) ?: [];
                $fmtLabels = ['keynote'=>'Keynote','workshop'=>'Workshop','panel'=>'Panel','moderation'=>'Moderation','training'=>'Training','consulting'=>'Beratung','interview'=>'Interview','webinar'=>'Webinar'];
                $company = htmlspecialchars((string) ($sp->company_linked_name ?? $sp->company ?? ''), ENT_QUOTES, 'UTF-8');
                $slug = CMS_Speakers_Database::generate_slug($sp);
            ?>
            <div class="spk-adm-card <?= $isPending ? 'spk-adm-card--pending' : '' ?>">
                <?php if ($isPending): ?>
                    <div class="spk-adm-pending-bar">Wartet auf Genehmigung</div>
                <?php endif; ?>
                <div class="spk-adm-top">
                    <?php if (!empty($sp->photo_url)): ?>
                        <div class="spk-adm-avatar spk-adm-avatar--photo">
                            <img src="<?= htmlspecialchars((string) $sp->photo_url, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy">
                        </div>
                    <?php else: ?>
                        <div class="spk-adm-avatar spk-adm-avatar--placeholder"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <div class="spk-adm-identity">
                        <div class="spk-adm-badges">
                            <?php if ($isPending): ?>
                                <span class="status-badge" style="background:#fef3c7;color:#92400e;">Zur Prüfung</span>
                            <?php else: ?>
                            <?php if ($sp->is_verified ?? 0): ?><span class="status-badge active">✔ Verifiziert</span><?php endif; ?>
                            <?php if ($sp->is_featured ?? 0): ?><span class="status-badge admin">Featured</span><?php endif; ?>
                            <span class="status-badge spk-status-<?= htmlspecialchars((string) $spAvail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($availLabels[$spAvail] ?? $spAvail), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <p class="spk-adm-name"><?= $name ?></p>
                        <?php if (!empty($sp->position)): ?><p class="spk-adm-sub"><?= htmlspecialchars((string) $sp->position, ENT_QUOTES, 'UTF-8') ?></p><?php endif; ?>
                        <?php if ($company): ?><p class="spk-adm-sub spk-adm-sub--muted"><?= $company ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="spk-adm-pills">
                    <?php if (!empty($sp->location_city)): ?>
                        <span class="spk-adm-pill"><?= htmlspecialchars((string) $sp->location_city, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                    <span class="spk-adm-pill"><?= $travelLabel ?></span>
                    <?php foreach (array_slice($formats, 0, 2) as $fmt): ?>
                        <span class="spk-adm-pill spk-adm-pill--accent"><?= htmlspecialchars((string) ($fmtLabels[$fmt] ?? $fmt), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                    <?php if (!empty($sp->email)): ?><span class="spk-adm-pill"><?= htmlspecialchars((string) $sp->email, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                    <?php if (!empty($sp->speaking_fee_min) || !empty($sp->speaking_fee_max)): ?>
                        <span class="spk-adm-pill spk-adm-pill--accent">💶 <?= $sp->speaking_fee_min ? number_format((float)$sp->speaking_fee_min,0,',','.') : '' ?><?= ($sp->speaking_fee_min && $sp->speaking_fee_max) ? '–' : '' ?><?= $sp->speaking_fee_max ? number_format((float)$sp->speaking_fee_max,0,',','.') . ' €' : '' ?></span>
                    <?php endif; ?>
                </div>
                <div class="spk-adm-footer">
                    <?php if ($isPending): ?>
                        <form method="POST" action="<?= htmlspecialchars(SITE_URL . '/admin/speakers/approve/' . (int)$sp->id, ENT_QUOTES, 'UTF-8') ?>" class="spk-contents-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string) $approveCsrf, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" class="spk-adm-btn spk-adm-btn-primary spk-adm-btn-approve" data-speaker-approve-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">Genehmigen</button>
                        </form>
                    <?php else: ?>
                        <a href="<?= htmlspecialchars(SITE_URL . '/speakers/' . $slug, ENT_QUOTES, 'UTF-8') ?>" class="spk-adm-btn spk-adm-btn-ghost" target="_blank" rel="noopener noreferrer">Ansehen</a>
                    <?php endif; ?>
                    <a href="<?= htmlspecialchars(SITE_URL . '/admin/speakers/edit/' . (int)$sp->id, ENT_QUOTES, 'UTF-8') ?>" class="spk-adm-btn spk-adm-btn-primary">Bearbeiten</a>
                        <button type="button" class="spk-adm-btn spk-adm-btn-danger" data-speaker-delete-id="<?= (int)$sp->id ?>" data-speaker-delete-name="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>">Löschen</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php elseif ($tab === 'topics'):
            // Alle Topics aller Speaker sammeln
            try {
                $stTopics = CMS\Database::instance()->prepare(
                    "SELECT t.*, CONCAT(s.first_name, ' ', s.last_name) AS speaker_name, s.id AS sid
                     FROM " . CMS\Database::instance()->prefix() . "speaker_topics t
                     LEFT JOIN " . CMS\Database::instance()->prefix() . "speakers s ON t.speaker_id = s.id
                     ORDER BY t.topic_name ASC"
                );
                $stTopics->execute([]);
                $allTopics = $stTopics->fetchAll();
            } catch (\Throwable $e) {
                $allTopics = [];
            }
            // Einzigartige Topics sammeln
            $topicGroups = [];
            foreach ($allTopics as $t) {
                $topicGroups[$t->topic_name][] = $t;
            }
        ?>
        <div class="admin-card">
            <h3>🏷️ Alle Themen-Tags (<?= count($topicGroups) ?> einzigartige Themen)</h3>
            <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Themen werden direkt beim Bearbeiten eines Speakers vergeben. Hier eine Übersicht aller aktiven Tags.</p>
            <?php if (empty($topicGroups)): ?>
                <p style="color:#94a3b8;font-style:italic;">Noch keine Themen vergeben.</p>
            <?php else: ?>
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                <?php foreach ($topicGroups as $topicName => $entries): ?>
                    <span class="spk-topic-tag" title="<?= count($entries) ?> Speaker">
                        <?= htmlspecialchars($topicName) ?>
                        <span class="spk-topic-tag__count"><?= count($entries) ?></span>
                    </span>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($allTopics)): ?>
        <div class="admin-card">
            <h3>📊 Themen-Zuordnung</h3>
            <div class="users-table-container">
                <table class="users-table">
                    <thead><tr><th>Thema</th><th>Speaker</th><th>Aktionen</th></tr></thead>
                    <tbody>
                    <?php foreach ($allTopics as $t): ?>
                    <tr>
                        <td><span class="spk-topic-tag"><?= htmlspecialchars($t->topic_name) ?></span></td>
                        <td><?= htmlspecialchars($t->speaker_name ?? '') ?></td>
                        <td><a href="<?= SITE_URL ?>/admin/speakers/edit/<?= (int)$t->sid ?>" class="btn btn-sm btn-secondary">✏️ Speaker bearbeiten</a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php elseif ($tab === 'design'): ?>
        <!-- Design Tab -->
        <form method="POST" action="<?= SITE_URL ?>/admin/speakers/settings/save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="_from_tab" value="design">
            <div class="admin-card">
                <h3>🎨 Farbpalette</h3>
                <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
                    <?php
                    $colorFields = [
                        'design_primary_color'       => ['Primärfarbe (Buttons, Links)',       '#8b5cf6'],
                        'design_accent_color'         => ['Akzentfarbe (Hover, Highlights)',    '#7c3aed'],
                        'design_card_bg'              => ['Card-Hintergrund',                   '#faf5ff'],
                        'archive_header_bg_from'      => ['Archiv-Header Gradient Von',         '#6d28d9'],
                        'archive_header_bg_to'        => ['Archiv-Header Gradient Bis',         '#a855f7'],
                        'archive_header_title_color'  => ['Archiv-Header Titelfarbe',           '#ffffff'],
                        'detail_header_bg_from'       => ['Detailseite Header Von',             '#4c1d95'],
                        'detail_header_bg_to'         => ['Detailseite Header Bis',             '#7c3aed'],
                        'detail_header_title_color'   => ['Detailseite Titelfarbe',             '#ffffff'],
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
                <h3>🏅 Badge-Farben (Detailseite)</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Hintergrund- und Textfarben der Status-Badges auf der Speaker-Detailseite.</p>
                <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
                    <?php
                    $badgeColorFields = [
                        'design_badge_avail_bg'       => ['Verfügbar – Hintergrund',     '#d1fae5'],
                        'design_badge_avail_color'    => ['Verfügbar – Textfarbe',        '#065f46'],
                        'design_badge_limited_bg'     => ['Begrenzt – Hintergrund',       '#fef3c7'],
                        'design_badge_limited_color'  => ['Begrenzt – Textfarbe',         '#92400e'],
                        'design_badge_booked_bg'      => ['Ausgebucht – Hintergrund',     '#fee2e2'],
                        'design_badge_booked_color'   => ['Ausgebucht – Textfarbe',       '#991b1b'],
                        'design_badge_mvp_bg'         => ['⭐ MVP – Hintergrund',         'rgba(251,191,36,0.2)'],
                        'design_badge_mvp_color'      => ['⭐ MVP – Textfarbe',           '#fbbf24'],
                        'design_badge_verified_bg'    => ['✔ Verifiziert – Hintergrund',  'rgba(255,255,255,0.15)'],
                        'design_badge_verified_color' => ['✔ Verifiziert – Textfarbe',    '#ffffff'],
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
                    ⚠️ MVP- und Verifiziert-Badges haben <strong>rgba-Werte</strong> als Standard. Das Farbwähler-Feld unterstützt keine Alpha-Werte – rgb()-Werte bitte direkt im Textfeld eingeben.
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
                        <label class="form-label">CTA-Button-Text</label>
                        <input type="text" name="design_cta_label" class="form-control" value="<?= htmlspecialchars($s['design_cta_label']) ?>" placeholder="Profil ansehen">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Header-Icon (Emoji)</label>
                        <input type="text" name="archive_header_icon" class="form-control" value="<?= htmlspecialchars($s['archive_header_icon']) ?>" placeholder="🎤">
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-top:.75rem;">
                    <?php foreach ([
                        'design_show_availability' => 'Verfügbarkeitsstatus',
                        'design_show_mvp_badge'    => '⭐ MVP-Badge',
                        'design_show_formats'      => 'Format-Badges',
                        'design_show_topics'       => 'Themen-Chips',
                    ] as $key => $label): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="<?= $key ?>" value="1" <?= !empty($s[$key]) && $s[$key] !== '0' ? 'checked' : '' ?>>
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <p style="margin-top:.75rem;padding:.5rem .75rem;background:#f0fdf4;border-left:3px solid #86efac;border-radius:4px;font-size:.8rem;color:#166534;">
                    ℹ️ <strong>Standort, Honorar und Reisebereitschaft</strong> werden nur auf der <strong>Detailseite</strong> angezeigt – nicht auf der Übersichtskarte.
                </p>
            </div>

            <div class="admin-card">
                <h3>👁️ Vorschau</h3>
                <div style="max-width:340px;">
                    <div id="prev-header" style="background:linear-gradient(135deg,<?= htmlspecialchars($s['archive_header_bg_from']) ?>,<?= htmlspecialchars($s['archive_header_bg_to']) ?>);padding:1.5rem;border-radius:<?= (int)$s['design_border_radius'] ?>px <?= (int)$s['design_border_radius'] ?>px 0 0;display:flex;align-items:center;gap:.75rem;">
                        <span id="prev-icon" style="font-size:2rem;"><?= htmlspecialchars($s['archive_header_icon']) ?></span>
                        <div>
                            <div id="prev-title" style="color:<?= htmlspecialchars($s['archive_header_title_color']) ?>;font-weight:800;font-size:1.1rem;"><?= htmlspecialchars($s['archive_title'] ?? 'Speaker') ?></div>
                            <div style="color:<?= htmlspecialchars($s['archive_header_title_color']) ?>;font-size:.8rem;opacity:.85;">Vorschau</div>
                        </div>
                    </div>
                    <div id="prev-body" style="background:<?= htmlspecialchars($s['design_card_bg']) ?>;padding:1rem;border:1px solid #ddd6fe;border-top:none;border-radius:0 0 <?= (int)$s['design_border_radius'] ?>px <?= (int)$s['design_border_radius'] ?>px;">
                        <span id="prev-cta" style="display:inline-block;padding:.3rem .8rem;background:<?= htmlspecialchars($s['design_primary_color']) ?>;color:#fff;border-radius:6px;font-size:.8rem;font-weight:700;"><?= htmlspecialchars($s['design_cta_label'] ?: 'Profil ansehen') ?> →</span>
                    </div>
                </div>
            </div>

            <div class="admin-card form-actions-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Design speichern</button>
                </div>
            </div>
        </form>

        <?php elseif ($tab === 'settings'): ?>
        <!-- Settings Tab -->
        <form method="POST" action="<?= SITE_URL ?>/admin/speakers/settings/save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="_from_tab" value="settings">
            <div class="admin-card">
                <h3>📋 Archive-Seite</h3>
                <div class="form-group">
                    <label class="form-label">Seitentitel</label>
                    <input type="text" name="archive_title" class="form-control" value="<?= htmlspecialchars($s['archive_title']) ?>" placeholder="Speaker Directory">
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
                <h3>🧭 Navigation</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Standardmäßig wird kein Link in der öffentlichen Hauptnavigation ausgegeben.</p>
                <div class="form-group" style="margin-top:.75rem;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="show_nav_link" value="1" <?= (string)($s['show_nav_link'] ?? '0') === '1' ? 'checked' : '' ?>>
                        Link in Hauptnavigation anzeigen
                    </label>
                    <small style="display:block;margin-top:.35rem;color:#64748b;">Wenn deaktiviert, bleibt die Seite erreichbar unter <code>/speakers</code>, wird aber nicht im Hauptmenü verlinkt.</small>
                </div>
                <div class="form-group" style="margin-top:.75rem;">
                    <label class="form-label">Navigations-Label</label>
                    <input type="text" name="nav_label" class="form-control" value="<?= htmlspecialchars((string)($s['nav_label'] ?? 'Speaker'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Speaker">
                </div>
            </div>
            <div class="admin-card">
                <h3>ℹ️ Shortcode-Nutzung</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:.5rem;">Speaker-Liste per Shortcode in Seiteninhalte einbinden:</p>
                <div style="background:#f5f3ff;border:1px solid #ddd6fe;border-radius:8px;padding:.75rem 1rem;font-family:monospace;font-size:.875rem;color:#6d28d9;">
                    [cms_speakers limit="12" featured="1" travel="national"]
                </div>
                <div style="margin-top:.75rem;display:flex;flex-direction:column;gap:.35rem;">
                    <small style="color:#64748b;"><strong>limit</strong> – Anzahl Speaker (Standard: 12)</small>
                    <small style="color:#64748b;"><strong>featured</strong> – Nur Featured-Speaker (1/0)</small>
                    <small style="color:#64748b;"><strong>travel</strong> – Filter: local | regional | national | international | worldwide</small>
                    <small style="color:#64748b;"><strong>availability</strong> – Filter: available | limited | booked</small>
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
                    <h3>Speaker löschen</h3>
                    <button class="modal-close" type="button" data-spk-modal-close="deleteModal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll <strong id="deleteModalName"></strong> wirklich gelöscht werden?</p>
                    <p style="color:#ef4444;font-size:.875rem;">Diese Aktion kann nicht rückgängig gemacht werden.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-spk-modal-close="deleteModal">Abbrechen</button>
                    <form method="POST" id="deleteModalForm" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CMS\Security::instance()->generateToken('delete_speaker'), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-danger">Endgültig löschen</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function openDeleteModal(id, name) {
            document.getElementById('deleteModalName').textContent = name;
            document.getElementById('deleteModalForm').action = '<?= SITE_URL ?>/admin/speakers/delete/' + id;
            openModal('deleteModal');
        }

        let _spkApproveForm = null;
        function openSpkApproveModal(id, name, form) {
            document.getElementById('approveSpkModalName').textContent = name;
            _spkApproveForm = form;
            openModal('approveSpkModal');
        }
        document.getElementById('approveSpkModalConfirm')?.addEventListener('click', function() {
            if (_spkApproveForm) _spkApproveForm.submit();
        });
        document.querySelectorAll('[data-speaker-delete-id]').forEach(function(button) {
            button.addEventListener('click', function() {
                openDeleteModal(button.dataset.speakerDeleteId || '0', button.dataset.speakerDeleteName || '');
            });
        });
        document.querySelectorAll('[data-speaker-approve-name]').forEach(function(button) {
            button.addEventListener('click', function() {
                openSpkApproveModal(0, button.dataset.speakerApproveName || '', button.closest('form'));
            });
        });
        document.addEventListener('click', function(event) {
            var closeButton = event.target.closest('[data-spk-modal-close]');
            if (closeButton) {
                closeModal(closeButton.dataset.spkModalClose || '');
            }
        });
        </script>

        <!-- Approve Modal -->
        <div id="approveSpkModal" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>Speaker genehmigen</h3>
                    <button class="modal-close" type="button" data-spk-modal-close="approveSpkModal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll <strong id="approveSpkModalName"></strong> genehmigt und aktiviert werden?</p>
                    <p style="color:#166534;font-size:.875rem;">Das Profil wird sofort öffentlich sichtbar.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-spk-modal-close="approveSpkModal">Abbrechen</button>
                    <button type="button" class="btn btn-primary" id="approveSpkModalConfirm">Genehmigen</button>
                </div>
            </div>
        </div>
        <?php
        $this->end_admin_layout();
    }

    // ─── FORM ────────────────────────────────────────────────
    public function render_form(?object $speaker, array $topics, array $events, array $companies): void
    {
        $is_edit   = $speaker !== null;
        $title     = $is_edit ? 'Speaker bearbeiten' : 'Neuer Speaker';
        $csrf      = CMS\Security::instance()->generateToken('save_speaker');
        $csrf_evt  = CMS\Security::instance()->generateToken('speaker_event');
        $sec       = CMS\Security::instance();

        $this->start_admin_layout($is_edit ? 'Speaker bearbeiten' : 'Neuer Speaker', 'speakers');

        // Admin-CSS einbinden
        $admin_css = CMS_SPEAKERS_PLUGIN_DIR . 'assets/css/speakers-admin.css';
        if (file_exists($admin_css)) {
            $adminCssVersion = (string) filemtime($admin_css);
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_SPEAKERS_PLUGIN_URL . 'assets/css/speakers-admin.css?v=' . $adminCssVersion, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        // Decode JSON fields
        $formats   = is_string($speaker->formats  ?? null) ? (json_decode($speaker->formats,  true) ?? []) : [];
        $langs     = is_string($speaker->languages ?? null) ? (json_decode($speaker->languages, true) ?? []) : [];
        $langs_str = implode(', ', $langs);

        $all_formats = [
            'keynote'     => 'Keynote',
            'workshop'    => 'Workshop',
            'panel'       => 'Podiumsdiskussion',
            'moderation'  => 'Moderation',
            'training'    => 'Training',
            'consulting'  => 'Beratung',
            'interview'   => 'Interview',
            'webinar'     => 'Webinar',
        ];
        $travel_options = [
            'local'         => 'Lokal (Umkreis 50 km)',
            'regional'      => 'Regional (Bundesland)',
            'national'      => 'National (DACH)',
            'international' => 'International (Europa)',
            'worldwide'     => 'Weltweit',
        ];
        $avail_options = [
            'available' => 'Verfügbar',
            'limited'   => 'Begrenzt verfügbar',
            'booked'    => 'Ausgebucht',
        ];
        $event_types = [
            'keynote'     => 'Keynote',
            'workshop'    => 'Workshop',
            'panel'       => 'Podiumsdiskussion',
            'moderation'  => 'Moderation',
            'interview'   => 'Interview',
            'webinar'     => 'Webinar',
            'conference'  => 'Konferenz',
            'other'       => 'Sonstiges',
        ];
        ?>
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2><?= $title ?></h2>
                <?php if ($is_edit): ?><p>ID #<?= (int)$speaker->id ?> · <?= (int)($speaker->profile_views ?? 0) ?> Profilaufrufe</p><?php endif; ?>
            </div>
            <div class="header-actions">
                <a href="<?= SITE_URL ?>/admin/speakers" class="btn btn-secondary">← Zurück</a>
                <?php if ($is_edit): ?>
                <a href="<?= htmlspecialchars(SITE_URL . '/speakers/' . CMS_Speakers_Database::generate_slug($speaker), ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">Ansehen</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">Gespeichert.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error">Fehler: <?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <form method="POST" action="<?= SITE_URL ?>/admin/speakers/save">
            <input type="hidden" name="speaker_id" value="<?= (int)($speaker->id ?? 0) ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

            <!-- 2-Spalten-Layout: linke + rechte Spalte -->
            <div class="spk-form-2col">
                <div class="spk-form-2col__left">
                    <?php CMS_Speakers_Meta_Boxes::instance()->render_personal_data($speaker); ?>
                    <?php CMS_Speakers_Meta_Boxes::instance()->render_contact($speaker); ?>
                    <?php CMS_Speakers_Meta_Boxes::instance()->render_location($speaker); ?>
                    <?php CMS_Speakers_Meta_Boxes::instance()->render_photo($speaker); ?>
                </div>
                <div class="spk-form-2col__right">
                    <?php CMS_Speakers_Meta_Boxes::instance()->render_position($speaker, $companies); ?>
                    <?php CMS_Speakers_Meta_Boxes::instance()->render_status($speaker); ?>
                    <?php CMS_Speakers_Meta_Boxes::instance()->render_profile($speaker, $all_formats, $travel_options, $avail_options, $formats, $langs_str); ?>
                    <?php CMS_Speakers_Meta_Boxes::instance()->render_topics($topics); ?>
                </div>
            </div>

            <!-- Volle Breite -->
            <?php CMS_Speakers_Meta_Boxes::instance()->render_bio($speaker); ?>
            <?php CMS_Speakers_Meta_Boxes::instance()->render_skills($speaker); ?>
            <?php CMS_Speakers_Meta_Boxes::instance()->render_recognitions($speaker); ?>

            <!-- Sticky Save Bar -->
            <div class="admin-card form-actions-card">
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Speaker speichern</button>
                    <a href="<?= SITE_URL ?>/admin/speakers" class="btn btn-secondary">Abbrechen</a>
                    <?php if ($is_edit): ?><span class="form-actions__hint">Zuletzt gespeichert: <?= htmlspecialchars(date('d.m.Y H:i', strtotime($speaker->updated_at ?? 'now')), ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                </div>
            </div>
        </form>

        <?php if ($is_edit): ?>
        <!-- Events Section (outside main form, own AJAX) -->
        <?php CMS_Speakers_Meta_Boxes::instance()->render_events($speaker->id, $events, $companies, $csrf_evt, $event_types); ?>
        <?php endif; ?>

        <?php
        $this->end_admin_layout();
    }
}
