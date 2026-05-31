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
    private const ADMIN_SECTIONS = [
        'overview' => 'Uebersicht',
        'taxonomies' => 'Fachrichtungen',
        'skills' => 'Skills Vorlagen',
        'design' => 'Design',
        'settings' => 'Einstellungen',
    ];
    public static function instance(): self
    {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }
    private function __construct()
    {
        $this->load_shared_admin_contract();
        CMS\Hooks::addAction('cms_admin_menu', [$this, 'register_admin_menu'], 10);
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }

    private function load_shared_admin_contract(): void
    {
        $contractFile = dirname(__DIR__, 2) . '/shared/admin/plugin-admin-contract.php';
        if (is_file($contractFile)) {
            require_once $contractFile;
        }

        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    private static function normalize_section(string $section): string
    {
        $section = strtolower(trim($section));
        return array_key_exists($section, self::ADMIN_SECTIONS) ? $section : 'overview';
    }

    private static function section_base_url(string $section): string
    {
        return rtrim((string) SITE_URL, '/') . '/admin/experts?section=' . rawurlencode(self::normalize_section($section));
    }

    private static function output_bridge_notice(string $targetUrl): void
    {
        $safeTarget = htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8');
        echo '<div class="admin-card"><p>Weiterleitung zur Experten-Verwaltung … <a href="' . $safeTarget . '">Falls nichts passiert, hier klicken</a>.</p></div>';
        echo '<script>window.location.replace(' . json_encode($targetUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');</script>';
    }

    private static function bridge_to_section(string $section): void
    {
        $section = self::normalize_section($section);
        $targetPath = '/admin/experts?section=' . rawurlencode($section);

        if (class_exists('CMS\\Router')) {
            CMS\Router::instance()->redirect($targetPath);
            return;
        }

        self::output_bridge_notice(self::section_base_url($section));
    }

    private function start_admin_layout(string $title, string $activePage): void
    {
        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, $activePage);
            return;
        }

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $activePage);
            echo '<div class="cms-plugin-admin-layout"><div class="cms-plugin-admin-layout__content">';
            return;
        }

        $pageTitle = $title;
        require_once ABSPATH . 'admin/partials/header.php';
        require_once ABSPATH . 'admin/partials/sidebar.php';
    }

    private function end_admin_layout(): void
    {
        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
            return;
        }

        echo '</div></div>';

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
            return;
        }

        require_once ABSPATH . 'admin/partials/footer.php';
    }

    private function output_admin_assets(): void
    {
        static $printed = false;
        if ($printed) {
            return;
        }
        $printed = true;

        $admin_css = CMS_EXPERTS_PLUGIN_DIR . 'assets/css/experts-admin.css';
        if (!file_exists($admin_css)) {
            return;
        }

        $version = (string) filemtime($admin_css);
        $href = CMS_EXPERTS_PLUGIN_URL . 'assets/css/experts-admin.css';

        if (function_exists('cms_enqueue_style')) {
            cms_enqueue_style('cms-experts-admin', $href, [], $version);
            return;
        }

        echo '<link rel="stylesheet" href="' . htmlspecialchars($href . '?v=' . $version, ENT_QUOTES, 'UTF-8') . '">' . "\n";
    }

    public function register_admin_menu(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Experten',
            '365NET | Experten',
            'manage_options',
            'experts',
            [self::class, 'render_plugin_page_bridge'],
            '👨‍💻',
            44
        );

        if (!function_exists('add_submenu_page')) {
            return;
        }

        add_submenu_page('experts', 'Experten Übersicht', 'Übersicht', 'manage_options', 'experts', [self::class, 'render_overview_bridge']);
        add_submenu_page('experts', 'Fachrichtungen', 'Fachrichtungen', 'manage_options', 'experts-taxonomies', [self::class, 'render_taxonomies_bridge']);
        add_submenu_page('experts', 'Skills Vorlagen', 'Skills Vorlagen', 'manage_options', 'experts-skills', [self::class, 'render_skills_bridge']);
        add_submenu_page('experts', 'Design', 'Design', 'manage_options', 'experts-design', [self::class, 'render_design_bridge']);
        add_submenu_page('experts', 'Einstellungen', 'Einstellungen', 'manage_options', 'experts-settings', [self::class, 'render_settings_bridge']);
    }

    public static function render_overview_bridge(): void
    {
        self::bridge_to_section('overview');
    }

    public static function render_taxonomies_bridge(): void
    {
        self::bridge_to_section('taxonomies');
    }

    public static function render_skills_bridge(): void
    {
        self::bridge_to_section('skills');
    }

    public static function render_design_bridge(): void
    {
        self::bridge_to_section('design');
    }

    public static function render_settings_bridge(): void
    {
        self::bridge_to_section('settings');
    }

    public static function render_plugin_page_bridge(): void
    {
        $callbackMap = [
            'experts' => [self::class, 'render_overview_bridge'],
            'experts-overview' => [self::class, 'render_overview_bridge'],
            'experts-taxonomies' => [self::class, 'render_taxonomies_bridge'],
            'experts-skills' => [self::class, 'render_skills_bridge'],
            'experts-design' => [self::class, 'render_design_bridge'],
            'experts-settings' => [self::class, 'render_settings_bridge'],
        ];

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, 'experts-overview', 'experts');
            return;
        }

        $requested = strtolower(trim((string) ($_GET['page'] ?? 'experts')));
        $requested = array_key_exists($requested, $callbackMap) ? $requested : 'experts-overview';
        $callback = $callbackMap[$requested] ?? null;
        if (is_callable($callback)) {
            call_user_func($callback);
            return;
        }

        self::output_bridge_notice(self::section_base_url('overview'));
    }

    public function add_menu_item(array $items): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $items[] = [
            'type'   => 'item',
            'slug'   => 'experts',
            'label'  => '365NET | Experten',
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
        $this->output_admin_assets();

        $experts   = $data['experts']  ?? [];
        $section   = self::normalize_section((string) ($data['section'] ?? ($data['tab'] ?? 'overview')));
        $filter    = $data['filter']   ?? 'all';
        $search    = $data['search']   ?? '';
        $specs     = $data['specs']    ?? [];
        $presets   = $data['presets']  ?? ['general' => [], 'tech' => [], 'soft' => []];
        $settings  = $data['settings'] ?? [];
        $csrf      = $data['csrf']     ?? '';
        $sort      = $data['sort']     ?? 'updated_desc';
        $companies = $data['companies'] ?? [];
        $sectionLabel = self::ADMIN_SECTIONS[$section] ?? self::ADMIN_SECTIONS['overview'];
        $baseAdminUrl = htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts', ENT_QUOTES, 'UTF-8');

        $s = array_merge([
            'archive_title'                => 'IT-Experten Netzwerk',
            'archive_description'          => 'Finden Sie qualifizierte IT-Experten für Ihr Projekt.',
            'archive_per_page'             => '12',
            'show_nav_link'                => '0',
            'nav_label'                    => 'Experten',
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
                <a href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/experts', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-secondary" target="_blank" rel="noopener noreferrer">Öffentlich</a>
                <a href="<?= htmlspecialchars(rtrim((string) SITE_URL, '/') . '/admin/experts/new', ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary">Neuer Experte</a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success"><strong>✅ Gespeichert.</strong> Die Änderungen wurden erfolgreich übernommen.</div>
        <?php endif; ?>
        <?php if (isset($_GET['approved'])): ?>
            <div class="alert alert-success"><strong>✅ Experte freigegeben.</strong> Das Profil ist jetzt aktiv und öffentlich sichtbar.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success"><strong>🗑️ Experte gelöscht.</strong> Der Eintrag wurde erfolgreich entfernt.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])):
            $errorCode = (string)($_GET['error'] ?? '');
            $errorMessages = [
                'csrf' => 'Sicherheitsprüfung fehlgeschlagen. Bitte Seite neu laden und erneut versuchen.',
                'invalid_id' => 'Der ausgewählte Experten-Eintrag konnte nicht eindeutig zugeordnet werden.',
                'delete_failed' => 'Der Experten-Eintrag konnte nicht gelöscht werden.',
            ];
            $errorMessage = $errorMessages[$errorCode] ?? $errorCode;
        ?>
            <div class="alert alert-error"><strong>❌ Aktion fehlgeschlagen.</strong> <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="exp-section-indicator">
            <strong>Bereich:</strong> <?= htmlspecialchars($sectionLabel, ENT_QUOTES, 'UTF-8') ?>
        </div>

        <?php if ($section === 'overview'): ?>
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
                <input type="hidden" name="section" value="overview">
                <div class="form-group" style="margin:0;flex:2;min-width:220px;">
                    <label class="form-label">Name / Stichwort</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, Ort, Position, Skill…" value="<?= htmlspecialchars((string) $search, ENT_QUOTES, 'UTF-8') ?>">
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
                <div class="form-group" style="margin:0;flex:1;min-width:220px;">
                    <label class="form-label">Sortierung</label>
                    <select name="sort" class="form-control">
                        <option value="updated_desc" <?= $sort==='updated_desc' ? 'selected' : '' ?>>Zuletzt aktualisiert (neu zuerst)</option>
                        <option value="updated_asc" <?= $sort==='updated_asc' ? 'selected' : '' ?>>Zuletzt aktualisiert (alt zuerst)</option>
                        <option value="created_desc" <?= $sort==='created_desc' ? 'selected' : '' ?>>Erstellt (neu zuerst)</option>
                        <option value="created_asc" <?= $sort==='created_asc' ? 'selected' : '' ?>>Erstellt (alt zuerst)</option>
                        <option value="name_asc" <?= $sort==='name_asc' ? 'selected' : '' ?>>Name (A–Z)</option>
                        <option value="name_desc" <?= $sort==='name_desc' ? 'selected' : '' ?>>Name (Z–A)</option>
                        <option value="status_asc" <?= $sort==='status_asc' ? 'selected' : '' ?>>Status</option>
                        <option value="availability_asc" <?= $sort==='availability_asc' ? 'selected' : '' ?>>Verfügbarkeit</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">🔍 Filtern</button>
                <?php if ($search || $filter !== 'all' || $sort !== 'updated_desc'): ?><a href="<?= $baseAdminUrl ?>?section=overview" class="btn btn-secondary">✕ Reset</a><?php endif; ?>
            </form>
        </div>

        <!-- Expert List -->
        <?php if (empty($experts)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">👨‍💻</p>
            <p><strong>Noch keine Experten vorhanden</strong></p>
            <p class="text-muted">Erstelle den ersten Experten-Eintrag.</p>
            <a href="<?= SITE_URL ?>/admin/experts/new" class="btn btn-primary" style="margin-top:1rem;">➕ Experten anlegen</a>
        </div>
        <?php else: ?>
        <div class="admin-card">
            <h3>📋 Expertenliste</h3>
            <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Alle Experten mit schnellen Aktionen für Freigabe, Bearbeitung und Löschung – inklusive steuerbarer Sortierung.</p>

            <div class="users-table-container exp-list-table-container">
                <table class="users-table exp-list-table">
                    <thead>
                        <tr>
                            <th>Experte</th>
                            <th>Status</th>
                            <th>Verfügbarkeit</th>
                            <th>Details</th>
                            <th>Aktualisiert</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($experts as $ex):
                        $firstName = trim((string)($ex->first_name ?? ''));
                        $lastName  = trim((string)($ex->last_name ?? ''));
                        $name = trim($firstName . ' ' . $lastName);
                        if ($name === '') {
                            $name = 'Unbekannt';
                        }

                        $safeName   = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                        $position   = trim((string)($ex->position ?? ''));
                        $company    = trim((string)($ex->company ?? ''));
                        $city       = trim((string)($ex->location_city ?? ''));
                        $email      = trim((string)($ex->email ?? ''));
                        $statusKey  = (string)($ex->status ?? 'active');
                        $isPending  = $statusKey === 'pending';
                        $statusMap  = [
                            'active'   => ['✅ Aktiv', 'active'],
                            'inactive' => ['⏸️ Inaktiv', 'inactive'],
                            'pending'  => ['⏳ Zur Prüfung', 'pending'],
                            'deleted'  => ['🗑️ Gelöscht', 'danger'],
                        ];
                        [$statusLabel, $statusClass] = $statusMap[$statusKey] ?? ['ℹ️ Unbekannt', 'inactive'];

                        $availabilityKey = (string)($ex->availability ?? 'available');
                        $availabilityMap = [
                            'available' => ['✅ Verfügbar', 'active'],
                            'limited'   => ['⚠️ Begrenzt', 'pending'],
                            'booked'    => ['🔴 Nicht verfügbar', 'danger'],
                        ];
                        [$availabilityLabel, $availabilityClass] = $availabilityMap[$availabilityKey] ?? ['—', 'inactive'];

                        $updatedAt = !empty($ex->updated_at) ? strtotime((string)$ex->updated_at) : false;
                        $createdAt = !empty($ex->created_at) ? strtotime((string)$ex->created_at) : false;
                        $dateLabel = $updatedAt ? date('d.m.Y', $updatedAt) : '—';
                        $timeLabel = $updatedAt ? date('H:i', $updatedAt) : '';
                        $createdLabel = $createdAt ? date('d.m.Y', $createdAt) : '—';
                        $slug = method_exists('CMS_Experts_Database', 'generate_slug')
                            ? CMS_Experts_Database::generate_slug($ex)
                            : (string)($ex->id ?? '');
                        $publicUrl = SITE_URL . '/experts/' . rawurlencode($slug);
                        $editUrl   = SITE_URL . '/admin/experts/edit/' . (int)$ex->id;
                    ?>
                        <tr<?= $isPending ? ' class="exp-list-row-pending"' : '' ?>>
                            <td>
                                <div class="exp-list-primary">
                                    <a href="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>" class="exp-list-name">
                                        <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <div class="exp-list-meta">
                                        <?php if ($position !== ''): ?>
                                            <span><?= htmlspecialchars($position, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                        <?php if ($company !== ''): ?>
                                            <span><?= htmlspecialchars($company, ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?= htmlspecialchars($availabilityClass, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($availabilityLabel, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <div class="exp-list-details">
                                    <?php if ($city !== ''): ?>
                                        <span>📍 <?= htmlspecialchars($city, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if ($email !== ''): ?>
                                        <span>✉️ <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($ex->experience_years) && (int)$ex->experience_years > 0): ?>
                                        <span>📅 <?= (int)$ex->experience_years ?> Jahre</span>
                                    <?php endif; ?>
                                    <?php if (!empty($ex->hourly_rate)): ?>
                                        <span>💶 <?= number_format((float)$ex->hourly_rate, 0, ',', '.') ?> €/h</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="exp-list-date">
                                    <strong><?= htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if ($timeLabel !== ''): ?>
                                        <span><?= htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') ?> Uhr</span>
                                    <?php endif; ?>
                                    <small>Erstellt: <?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></small>
                                </div>
                            </td>
                            <td>
                                <div class="exp-list-actions">
                                    <?php if ($isPending): ?>
                                        <form method="POST"
                                              action="<?= SITE_URL ?>/admin/experts/approve/<?= (int)$ex->id ?>"
                                              style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit"
                                                    class="btn btn-sm btn-primary"
                                                    onclick="cmsConfirm({title:'Expertenprofil genehmigen?',message:'Soll &quot;<?= $safeName ?>&quot; genehmigt und sofort aktiviert werden?',confirmText:'Genehmigen',confirmClass:'btn-primary',statusClass:'bg-success',onConfirm:()=>this.closest('form').submit()}); return false;"
                                                    title="Genehmigen">
                                                ✅
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>"
                                       class="btn btn-sm btn-secondary"
                                       title="Bearbeiten">
                                        ✏️
                                    </a>
                                    <a href="<?= htmlspecialchars($publicUrl, ENT_QUOTES, 'UTF-8') ?>"
                                       class="btn btn-sm btn-secondary"
                                       target="_blank"
                                       rel="noopener noreferrer"
                                       title="Öffentlich ansehen">
                                        🌐
                                    </a>
                                    <form method="POST"
                                          action="<?= SITE_URL ?>/admin/experts/delete/<?= (int)$ex->id ?>"
                                          style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit"
                                                class="btn btn-sm btn-danger"
                                                onclick="cmsConfirm({title:'Expertenprofil löschen?',message:'Soll &quot;<?= $safeName ?>&quot; wirklich gelöscht werden? Diese Aktion kann nicht rückgängig gemacht werden.',confirmText:'Löschen',confirmClass:'btn-danger',statusClass:'bg-danger',onConfirm:()=>this.closest('form').submit()}); return false;"
                                                title="Löschen">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php
        // ══════════════════════════════════════════════════════════════
        // TAB: FACHRICHTUNGEN
        // ══════════════════════════════════════════════════════════════
        elseif ($section === 'taxonomies'):
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
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" class="exp-tax-del-btn"
                                                onclick="return confirm('«<?= htmlspecialchars($root->name, ENT_QUOTES) ?>» und alle Unter-Einträge löschen?')">×</button>
                                    </form>
                                </div>
                                <?php foreach ($children[(int)$root->id] ?? [] as $child): ?>
                                <div class="exp-tax-child">
                                    <span>↳ <?= htmlspecialchars($child->name) ?></span>
                                    <form method="POST" action="<?= SITE_URL ?>/admin/experts/taxonomy/delete/<?= (int)$child->id ?>" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
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
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
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
        elseif ($section === 'skills'):
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
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
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
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
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
        elseif ($section === 'design'):
        ?>
        <form method="POST" action="<?= SITE_URL ?>/admin/experts/settings/save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
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
                        $val = htmlspecialchars((string)($s[$key] ?? $default), ENT_QUOTES, 'UTF-8');
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
                        $val = htmlspecialchars((string)($s[$key] ?? $default), ENT_QUOTES, 'UTF-8');
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
                        <input type="number" name="design_border_radius" class="form-control" value="<?= htmlspecialchars((string)($s['design_border_radius'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" min="0" max="32">
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
                        <input type="text" name="archive_header_icon" class="form-control" value="<?= htmlspecialchars(html_entity_decode((string)($s['archive_header_icon'] ?? '👨‍💻'), ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>" placeholder="👨‍💻">
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
        elseif ($section === 'settings'):
        ?>
        <form method="POST" action="<?= SITE_URL ?>/admin/experts/settings/save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
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
                <h3>🧭 Navigation</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Standardmäßig wird kein Link in der öffentlichen Hauptnavigation ausgegeben.</p>
                <div class="form-group" style="margin-top:.75rem;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="show_nav_link" value="1" <?= (string)($s['show_nav_link'] ?? '0') === '1' ? 'checked' : '' ?>>
                        Link in Hauptnavigation anzeigen
                    </label>
                    <small style="display:block;margin-top:.35rem;color:#64748b;">Wenn deaktiviert, bleibt die Seite erreichbar unter <code>/experts</code>, wird aber nicht im Hauptmenü verlinkt.</small>
                </div>
                <div class="form-group" style="margin-top:.75rem;">
                    <label class="form-label">Navigations-Label</label>
                    <input type="text" name="nav_label" class="form-control" value="<?= htmlspecialchars((string)($s['nav_label'] ?? 'Experten'), ENT_QUOTES, 'UTF-8') ?>" placeholder="Experten">
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

        <script>
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
            $allColorKeys = [];
            if ($section === 'design') {
                $allColorKeys = array_merge(array_keys($colorFields), array_keys($badgeColorFields));
            }
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
        $this->output_admin_assets();

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
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
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
