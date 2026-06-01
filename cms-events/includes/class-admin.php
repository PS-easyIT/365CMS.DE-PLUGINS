<?php
/**
 * Admin Interface für CMS Events
 * 5-Tab-Layout: Übersicht | Kategorien | Tags | Design | Einstellungen
 *
 * @package CMS_Events
 * @since   1.1.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_Events_Admin', false)) {
    return;
}

final class CMS_Events_Admin
{
    private const MENU_PARENT_SLUG = 'events';
    private const MENU_SECTIONS = [
        'events'            => 'overview',
        'events-categories' => 'categories',
        'events-tags'       => 'tags',
        'events-design'     => 'design',
        'events-settings'   => 'settings',
    ];

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
        $this->load_shared_admin_contract();
        CMS\Hooks::addAction('cms_admin_menu', [$this, 'register_admin_menu'], 10);
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }

    public function register_admin_menu(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Events',
            '365NET | Events',
            'manage_options',
            self::MENU_PARENT_SLUG,
            [self::class, 'render_plugin_page_bridge'],
            '📅',
            43
        );

        if (!function_exists('add_submenu_page')) {
            return;
        }

        add_submenu_page(self::MENU_PARENT_SLUG, 'Event Uebersicht', '📅 Uebersicht', 'manage_options', 'events', [self::class, 'render_plugin_page_bridge']);
        add_submenu_page(self::MENU_PARENT_SLUG, 'Event Kategorien', '📂 Kategorien', 'manage_options', 'events-categories', [self::class, 'render_plugin_page_bridge']);
        add_submenu_page(self::MENU_PARENT_SLUG, 'Event Tags', '🏷️ Tags', 'manage_options', 'events-tags', [self::class, 'render_plugin_page_bridge']);
        add_submenu_page(self::MENU_PARENT_SLUG, 'Event Design', '🎨 Design', 'manage_options', 'events-design', [self::class, 'render_plugin_page_bridge']);
        add_submenu_page(self::MENU_PARENT_SLUG, 'Event Einstellungen', '⚙️ Einstellungen', 'manage_options', 'events-settings', [self::class, 'render_plugin_page_bridge']);
    }

    public static function render_plugin_page_bridge(): void
    {
        $callbackMap = [
            'events'            => [self::class, 'render_overview_bridge'],
            'events-categories' => [self::class, 'render_categories_bridge'],
            'events-tags'       => [self::class, 'render_tags_bridge'],
            'events-design'     => [self::class, 'render_design_bridge'],
            'events-settings'   => [self::class, 'render_settings_bridge'],
        ];

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, 'events', self::MENU_PARENT_SLUG);
            return;
        }

        $requestedSlug = self::requested_admin_slug();
        $callback = $callbackMap[$requestedSlug] ?? $callbackMap['events'];
        if (is_callable($callback)) {
            call_user_func($callback);
            return;
        }

        self::render_admin_bridge_fallback_notice();
    }

    private static function requested_admin_slug(): string
    {
        $requested = (string) ($_GET['page'] ?? self::MENU_PARENT_SLUG);
        if (function_exists('cms_plugin_admin_normalize_slug')) {
            return cms_plugin_admin_normalize_slug($requested);
        }

        $normalized = strtolower(trim($requested));
        $normalized = (string) preg_replace('/[^a-z0-9_-]+/', '-', $normalized);
        return trim($normalized, '-');
    }

    private function load_shared_admin_contract(): void
    {
        $sharedContract = dirname(rtrim(CMS_EVENTS_PLUGIN_DIR, '/\\')) . '/shared/admin/plugin-admin-contract.php';
        if (is_file($sharedContract)) {
            require_once $sharedContract;
        }
    }

    private function start_admin_layout(string $title, string $activePage): void
    {
        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, $activePage);
            return;
        }

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
        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
            return;
        }

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
            return;
        }

        $footer = ABSPATH . 'admin/partials/footer.php';
        if (file_exists($footer)) {
            require_once $footer;
        }
    }

    private function outputAdminAssets(): void
    {
        $adminCss = CMS_EVENTS_PLUGIN_DIR . 'assets/css/events-admin.css';
        if (file_exists($adminCss)) {
            $adminCssVersion = (string) filemtime($adminCss);
            if (function_exists('cms_enqueue_style')) {
                cms_enqueue_style('cms-events-admin', CMS_EVENTS_PLUGIN_URL . 'assets/css/events-admin.css', [], $adminCssVersion);
            } else {
                echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_EVENTS_PLUGIN_URL . 'assets/css/events-admin.css?v=' . $adminCssVersion, ENT_QUOTES, 'UTF-8') . '">' . "\n";
            }
        }

        $adminJs = CMS_EVENTS_PLUGIN_DIR . 'assets/js/admin.js';
        if (file_exists($adminJs)) {
            $adminJsVersion = (string) filemtime($adminJs);
            if (function_exists('cms_enqueue_script')) {
                cms_enqueue_script('cms-events-admin', CMS_EVENTS_PLUGIN_URL . 'assets/js/admin.js', [], $adminJsVersion, ['defer' => true]);
            } else {
                echo '<script src="' . htmlspecialchars(CMS_EVENTS_PLUGIN_URL . 'assets/js/admin.js?v=' . $adminJsVersion, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
            }
        }
    }

    public function add_menu_item(array $menuItems): array
    {
        $currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $isActive    = str_starts_with($currentPath, '/admin/events');

        $menuItems[] = [
            'type'   => 'item',
            'slug'   => self::MENU_PARENT_SLUG,
            'label'  => '365NET | Events',
            'icon'   => '📅',
            'url'    => '/admin/events',
            'active' => $isActive,
        ];

        return $menuItems;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // render_list – 5-Tab-Admin
    // ══════════════════════════════════════════════════════════════════════════

    public function render_list(array $data): void
    {
        $this->start_admin_layout('Events', 'events');
        $this->outputAdminAssets();

        // Daten aus dem assoziativen Array lesen
        $events      = $data['events']      ?? [];
        $tab         = in_array((string) ($data['tab'] ?? 'overview'), ['overview', 'categories', 'tags', 'design', 'settings'], true)
            ? (string) $data['tab']
            : 'overview';
        $filter      = $data['filter']      ?? 'all';
        $categories  = $data['categories']  ?? [];
        $tag_presets = $data['tag_presets'] ?? ['general' => [], 'special' => [], 'format' => []];
        $settings    = $data['settings']    ?? [];
        $csrf        = (string) ($data['csrf'] ?? '');
        $csrfEsc     = htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8');
        $eventsAdminBaseUrl = htmlspecialchars((string) SITE_URL . '/admin/events', ENT_QUOTES, 'UTF-8');
        $approveCsrf = htmlspecialchars((string) ($data['approve_csrf'] ?? ''), ENT_QUOTES, 'UTF-8');
        $sec         = CMS\Security::instance();
        $listErrorCode = self::query_param_string('error', 40);

        // Settings mit Defaults zusammenführen
        $s = array_merge([
            'archive_title'         => 'Veranstaltungen',
            'archive_description'   => 'Aktuelle Veranstaltungen entdecken',
            'archive_slug'          => 'events',
            'show_nav_link'         => '0',
            'nav_label'             => 'Veranstaltungen',
            'per_page'              => '12',
            'grid_columns'          => 'auto',
            'archive_header_icon'   => '📅',
            'color_primary'         => '#3b82f6',
            'color_accent'          => '#60a5fa',
            'color_hdr_from'        => '#1d4ed8',
            'color_hdr_to'          => '#3b82f6',
            'color_hdr_title'       => '#ffffff',
            'color_card_bg'         => '#f0f7ff',
            'color_card_border'     => '#bfdbfe',
            'color_cta'             => '#1e40af',
            'color_detail_hdr_bg'   => '#0f172a',
            'color_detail_hdr_text' => '#ffffff',
            'color_detail_accent'   => '#3b82f6',
            'color_featured_border' => '#f59e0b',
            'color_cancelled_bg'    => '#fee2e2',
            'color_online_badge'    => '#059669',
            'border_radius'         => '12',
            'show_category'         => '1',
            'show_city'             => '1',
            'show_capacity'         => '1',
            'show_speakers'         => '1',
            'show_price'            => '1',
            'show_organizer'        => '1',
            'show_tags'             => '1',
            'show_status_badge'     => '1',
            'show_featured_badge'   => '1',
            'show_online_badge'     => '1',
            'show_date_pill'        => '1',
            'show_time_pill'        => '1',
            'color_badge_published_bg'    => '#d1fae5',
            'color_badge_published_color' => '#065f46',
            'color_badge_draft_bg'        => '#fef3c7',
            'color_badge_draft_color'     => '#92400e',
            'color_badge_cancelled_bg'    => '#fee2e2',
            'color_badge_cancelled_color' => '#991b1b',
            'color_badge_completed_bg'    => '#dbeafe',
            'color_badge_completed_color' => '#1e40af',
            'color_badge_featured_bg'     => '#fef3c7',
            'color_badge_featured_color'  => '#92400e',
            'color_badge_online_bg'       => '#d1fae5',
            'color_badge_online_color'    => '#065f46',
        ], $settings);

        // Statistiken
        $total     = count($events);
        $published = count(array_filter($events, fn($e) => ($e->status ?? '') === 'published'));
        $draft     = count(array_filter($events, fn($e) => ($e->status ?? '') === 'draft'));
        $cancelled = count(array_filter($events, fn($e) => ($e->status ?? '') === 'cancelled'));
        $featured  = count(array_filter($events, fn($e) => !empty($e->is_featured)));
        $upcoming  = count(array_filter($events, fn($e) => $this->is_upcoming_event($e)));
        ?>

        <div class="ev-admin-shell">

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>📅 Events</h2>
                <p>Veranstaltungen, Workshops und Webinare zentral verwalten.</p>
            </div>
            <div class="header-actions">
                <a href="<?= SITE_URL ?>/events" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">👁️ Öffentlich</a>
                <a href="<?= SITE_URL ?>/admin/events/new" class="btn btn-primary">➕ Neues Event</a>
            </div>
        </div>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">✅ Einstellungen gespeichert.</div>
        <?php endif; ?>
        <?php if (isset($_GET['approved'])): ?>
            <div class="alert alert-success">✅ Event genehmigt und veröffentlicht.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">✅ Event gelöscht.</div>
        <?php endif; ?>
        <?php if ($listErrorCode !== ''): ?>
            <div class="alert alert-error">
                ❌ Fehler:
                <?php match($listErrorCode) {
                    'csrf'       => print 'Sicherheitscheck fehlgeschlagen.',
                    'save'       => print 'Datenbank-Fehler beim Speichern.',
                    'validation' => print 'Pflichtfelder prüfen.',
                    default      => print 'Unbekannter Fehler.',
                }; ?>
            </div>
        <?php endif; ?>

        <?php
        // ══════════════════════════════════════════════════════════════════
        if ($tab === 'overview'):

            $filtered = $events;
            $search = trim($data['search'] ?? '');
            if ($search) {
                $q = mb_strtolower($search);
                $filtered = array_values(array_filter($filtered, fn($e) =>
                    str_contains(mb_strtolower($e->title ?? ''), $q) ||
                    str_contains(mb_strtolower($e->city ?? ''), $q) ||
                    str_contains(mb_strtolower($e->category ?? ''), $q) ||
                    str_contains(mb_strtolower($e->organizer_name ?? ''), $q)
                ));
            }
            if ($filter === 'upcoming') $filtered = array_values(array_filter($filtered, fn($e) => $this->is_upcoming_event($e)));
            elseif ($filter === 'past') $filtered = array_values(array_filter($filtered, fn($e) => $this->is_past_event($e)));
            elseif ($filter === 'featured') $filtered = array_values(array_filter($filtered, fn($e) => !empty($e->is_featured)));
            elseif ($filter === 'online') $filtered = array_values(array_filter($filtered, fn($e) => !empty($e->is_online)));
            elseif ($filter === 'draft') $filtered = array_values(array_filter($filtered, fn($e) => ($e->status ?? 'draft') === 'draft'));
        ?>

        <!-- Stats -->
        <div class="dashboard-grid">
            <?php
            $stat_items = [
                ['📅', 'Gesamt',          $total,     ''],
                ['✅', 'Veröffentlicht',  $published, ''],
                ['📆', 'Bevorstehend',    $upcoming,  ''],
                ['📝', 'Entwürfe',        $draft,     ''],
                ['⭐', 'Featured',        $featured,  ''],
                ['❌', 'Abgesagt',        $cancelled, ''],
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
        <div class="admin-card ev-filter-card">
            <h3>🔎 Events filtern</h3>
            <form method="GET" class="admin-form ev-admin-filter-form" novalidate>
                <input type="hidden" name="tab" value="overview">
                <div class="form-group ev-form-group--inline-reset ev-form-group--grow-2">
                    <label class="form-label">Titel / Stichwort</label>
                    <input type="text" name="search" class="form-control" placeholder="Titel, Ort, Kategorie…" value="<?= htmlspecialchars((string)($data['search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                </div>
                <div class="form-group ev-form-group--inline-reset ev-form-group--grow-1">
                    <label class="form-label">Status / Typ</label>
                    <select name="filter" class="form-control">
                        <option value="all"      <?= $filter==='all'      ?'selected':'' ?>>Alle (<?= $total ?>)</option>
                        <option value="upcoming" <?= $filter==='upcoming' ?'selected':'' ?>>📆 Bevorstehend (<?= $upcoming ?>)</option>
                        <option value="past"     <?= $filter==='past'     ?'selected':'' ?>>⌛ Vergangen</option>
                        <option value="featured" <?= $filter==='featured' ?'selected':'' ?>>⭐ Featured (<?= $featured ?>)</option>
                        <option value="draft"    <?= $filter==='draft'    ?'selected':'' ?>>📝 Entwürfe (<?= $draft ?>)</option>
                        <option value="online"   <?= $filter==='online'   ?'selected':'' ?>>🌐 Online</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">🔍 Filtern</button>
                <?php if (($data['search'] ?? '') || $filter !== 'all'): ?><a href="<?= $eventsAdminBaseUrl ?>?tab=overview" class="btn btn-secondary">✕ Reset</a><?php endif; ?>
            </form>
        </div>

        <?php if (empty($filtered)): ?>
            <div class="empty-state">
                <p class="ev-empty-icon">📅</p>
                <p><strong>Keine Events <?= $filter !== 'all' ? 'in diesem Filter' : '' ?> gefunden.</strong></p>
                <p class="text-muted">Passe die Filter an oder lege direkt ein neues Event an.</p>
                <?php if ($filter === 'all'): ?>
                    <a href="<?= SITE_URL ?>/admin/events/new" class="btn btn-primary">➕ Erstes Event anlegen</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="admin-card ev-tab-panel">
            <div class="ev-panel-header">
                <div>
                    <h3>📋 Event-Übersicht</h3>
                    <p>Alle Events mit Datum, Status und schnellen Aktionen.</p>
                </div>
                <span class="ev-result-count"><?= (int)count($filtered) ?> Einträge</span>
            </div>
            <div class="users-table-container ev-events-table-wrap">
                <table class="users-table ev-events-table">
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Datum</th>
                            <th>Ort / Typ</th>
                            <th>Status</th>
                            <th>Merkmale</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filtered as $ev):
                        $id       = (int)($ev->id ?? 0);
                        $titleRaw = (string)($ev->title ?? '');
                        $title    = htmlspecialchars($titleRaw, ENT_QUOTES, 'UTF-8');
                        $category = htmlspecialchars((string)($ev->category ?? ''), ENT_QUOTES, 'UTF-8');
                        $cityRaw  = trim((string)($ev->city ?? ''));
                        $city     = htmlspecialchars($cityRaw, ENT_QUOTES, 'UTF-8');
                        $status   = (string)($ev->status ?? 'draft');
                        $dateTs   = !empty($ev->event_date) ? strtotime((string)$ev->event_date) : false;
                        $endTs    = !empty($ev->end_date) ? strtotime((string)$ev->end_date) : false;
                        $isPast   = $dateTs !== false && $dateTs < strtotime('today');
                        $isToday  = $dateTs !== false && date('Y-m-d', $dateTs) === date('Y-m-d');
                        $isDraft  = $status === 'draft';
                        $statusCfg = [
                            'draft'     => ['⏳ Entwurf', 'pending'],
                            'published' => ['✅ Veröffentlicht', 'active'],
                            'cancelled' => ['❌ Abgesagt', 'danger'],
                            'completed' => ['📦 Abgeschlossen', 'inactive'],
                        ];
                        [$stLabel, $stClass] = $statusCfg[$status] ?? ['ℹ️ Unbekannt', 'inactive'];
                        $dateLabel = $dateTs ? date('d.m.Y', $dateTs) : '—';
                        $timeLabel = !empty($ev->event_time) ? substr((string)$ev->event_time, 0, 5) . ' Uhr' : '';
                        $endLabel  = ($endTs && $dateTs && date('Y-m-d', $endTs) !== date('Y-m-d', $dateTs)) ? 'bis ' . date('d.m.Y', $endTs) : '';
                        $publicUrl = function_exists('cms_event_url') ? cms_event_url($ev) : SITE_URL . '/event/event-' . $id;
                        $websiteUrl = $this->normalize_external_url($ev->organizer_website ?? '');
                    ?>
                        <tr<?= $isDraft ? ' class="ev-row-pending"' : ($isPast ? ' class="ev-row-muted"' : '') ?>>
                            <td>
                                <div class="ev-table-primary">
                                    <a href="<?= SITE_URL ?>/admin/events/edit/<?= $id ?>" class="ev-table-title"><?= $title !== '' ? $title : 'Unbenanntes Event' ?></a>
                                    <div class="ev-table-meta">
                                        <?php if ($category !== ''): ?><span>📂 <?= $category ?></span><?php endif; ?>
                                        <?php if (!empty($ev->organizer_name)): ?><span>🏢 <?= htmlspecialchars((string)$ev->organizer_name, ENT_QUOTES, 'UTF-8') ?></span><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($dateLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if ($timeLabel !== ''): ?><div class="ev-table-muted">🕐 <?= htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                                <?php if ($endLabel !== ''): ?><div class="ev-table-muted"><?= htmlspecialchars($endLabel, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($ev->is_online)): ?>
                                    <span class="status-badge active">🌐 Online</span>
                                <?php elseif ($city !== ''): ?>
                                    <span><?= $city ?></span>
                                <?php else: ?>
                                    <span class="ev-table-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?= htmlspecialchars($stClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($stLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($isToday): ?><div class="ev-table-muted">🔴 Heute</div><?php endif; ?>
                            </td>
                            <td>
                                <div class="ev-soft-badge-stack">
                                    <?php if (!empty($ev->is_featured)): ?><span class="ev-soft-badge">⭐ Featured</span><?php endif; ?>
                                    <?php if (!empty($ev->capacity)): ?><span class="ev-soft-badge">👥 <?= (int)$ev->capacity ?></span><?php endif; ?>
                                    <?php if (!empty($ev->registration_url)): ?><span class="ev-soft-badge">🎟 Anmeldung</span><?php endif; ?>
                                    <?php if (($ev->price_type ?? 'free') === 'free'): ?><span class="ev-soft-badge">✅ Kostenlos</span><?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="ev-row-actions">
                                    <?php if ($isDraft): ?>
                                        <form method="POST" action="<?= SITE_URL ?>/admin/events/approve/<?= $id ?>" id="ev-approve-form-<?= $id ?>" class="ev-inline-form-compact">
                                            <input type="hidden" name="csrf_token" value="<?= $approveCsrf ?>">
                                            <button type="button" class="btn btn-sm btn-primary"
                                                    data-ev-approve-event
                                                    data-ev-event-name="<?= htmlspecialchars($titleRaw, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-ev-submit-target="ev-approve-form-<?= $id ?>">✓</button>
                                        </form>
                                    <?php else: ?>
                                        <a href="<?= htmlspecialchars((string)$publicUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary" title="Öffentlich ansehen">👁️</a>
                                    <?php endif; ?>
                                    <?php if ($websiteUrl !== ''): ?>
                                        <a href="<?= htmlspecialchars($websiteUrl, ENT_QUOTES, 'UTF-8') ?>"
                                           target="_blank"
                                           rel="noopener noreferrer"
                                           class="btn btn-sm btn-secondary"
                                           title="Website öffnen"
                                           aria-label="Website von <?= htmlspecialchars($titleRaw !== '' ? $titleRaw : 'Event', ENT_QUOTES, 'UTF-8') ?> öffnen">🌐</a>
                                    <?php endif; ?>
                                    <a href="<?= SITE_URL ?>/admin/events/edit/<?= $id ?>" class="btn btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                                    <button type="button" class="btn btn-sm btn-danger"
                                            data-ev-delete-event
                                            data-ev-event-name="<?= htmlspecialchars($titleRaw, ENT_QUOTES, 'UTF-8') ?>"
                                            data-ev-delete-action="<?= SITE_URL ?>/admin/events/delete/<?= $id ?>"
                                            title="Löschen">🗑️</button>
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
        // ══════════════════════════════════════════════════════════════════
        elseif ($tab === 'categories'):
        ?>
        <div class="admin-card ev-tab-panel">
            <div class="ev-panel-header">
                <div>
                    <h3>📂 Kategorien</h3>
                    <p>Öffentliche Event-Kategorien mit Icon und Slug verwalten.</p>
                </div>
                <span class="ev-result-count"><?= (int)count($categories) ?> Kategorien</span>
            </div>

            <div class="ev-layout-split-320">
            <div>
                <?php if (empty($categories)): ?>
                    <div class="empty-state ev-empty-state-compact">
                        <p class="ev-empty-icon">📂</p>
                        <p><strong>Noch keine Kategorien vorhanden</strong></p>
                        <p class="text-muted">Lege rechts die erste Kategorie für dein Event-Archiv an.</p>
                    </div>
                <?php else: ?>
                    <div class="users-table-container">
                        <table class="users-table">
                            <thead><tr><th>Kategorie</th><th>Slug</th><th>Aktionen</th></tr></thead>
                            <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?= $sec->escape($cat->icon ?? '📂') ?> <?= $sec->escape($cat->name) ?></strong></td>
                                <td><code><?= $sec->escape($cat->slug ?? '') ?></code></td>
                                <td>
                                    <?php if (($cat->id ?? 0) > 0): ?>
                                        <form method="POST" action="<?= SITE_URL ?>/admin/events/category/delete/<?= (int)$cat->id ?>" class="ev-inline-form-compact"
                                              data-ev-confirm-title="Kategorie löschen?"
                                              data-ev-confirm-message="Kategorie „<?= htmlspecialchars((string)($cat->name ?? ''), ENT_QUOTES, 'UTF-8') ?>” wirklich löschen?"
                                              data-ev-confirm-button="Löschen"
                                              data-ev-confirm-class="btn-danger">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfEsc ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">🗑️</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="ev-table-muted">System</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="ev-side-panel">
                <h3>➕ Neue Kategorie</h3>
                <form method="POST" action="<?= SITE_URL ?>/admin/events/category/add" class="admin-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $csrfEsc ?>">
                    <div class="form-group">
                        <label class="form-label">Icon (Emoji)</label>
                        <input type="text" name="category_icon" value="📂" maxlength="4" class="form-control ev-emoji-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategorie-Name <span class="ev-required">*</span></label>
                        <input type="text" name="category_name" required placeholder="z.B. Konferenz" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary ev-btn-block">➕ Anlegen</button>
                </form>
            </div>
            </div>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════════
        elseif ($tab === 'tags'):
            $typeLabels = [
                'general' => ['🔷', 'Allgemein',      'Allgemeine Event-Merkmale'],
                'special' => ['⭐', 'Speziell',        'Besondere Eigenschaften'],
                'format'  => ['📋', 'Format & Niveau', 'Zielgruppe und Format'],
            ];
        ?>
        <div class="admin-card ev-tab-panel">
            <div class="ev-panel-header">
                <div>
                    <h3>🏷️ Tag-Vorlagen</h3>
                    <p>Vordefinierte Merkmale für Event-Karten und Filter pflegen.</p>
                </div>
            </div>
        <div class="ev-layout-split-280">
            <div>
                <div class="ev-layout-card-grid">
                <?php foreach ($typeLabels as $type => [$icon, $label, $desc]): ?>
                    <div class="ev-mini-panel">
                        <div class="ev-inline-stack">
                            <span class="ev-mini-panel-icon"><?= $icon ?></span>
                            <div>
                                <strong><?= $label ?></strong>
                                <div class="ev-table-muted"><?= $desc ?></div>
                            </div>
                        </div>
                        <div class="ev-tag-list">
                            <?php foreach ($tag_presets[$type] ?? [] as $tg): ?>
                                <span class="ev-tag">
                                    <?= $sec->escape($tg->tag_name) ?>
                                    <form method="POST" action="<?= SITE_URL ?>/admin/events/tagpreset/delete/<?= (int)$tg->id ?>" class="ev-inline-form-compact"
                                          data-ev-confirm-title="Tag löschen?"
                                          data-ev-confirm-message="Tag „<?= htmlspecialchars((string)($tg->tag_name ?? ''), ENT_QUOTES, 'UTF-8') ?>” wirklich löschen?"
                                          data-ev-confirm-button="Löschen"
                                          data-ev-confirm-class="btn-danger">
                                        <input type="hidden" name="csrf_token" value="<?= $csrfEsc ?>">
                                        <button type="submit" class="ev-tag-del" aria-label="Tag löschen">×</button>
                                    </form>
                                </span>
                            <?php endforeach; ?>
                            <?php if (empty($tag_presets[$type])): ?>
                                <span class="ev-table-muted">Noch keine Einträge.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <div class="ev-side-panel">
                <h3>➕ Neues Tag</h3>
                <form method="POST" action="<?= SITE_URL ?>/admin/events/tagpreset/add" class="admin-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= $csrfEsc ?>">
                    <div class="form-group">
                        <label class="form-label">Tag-Name <span class="ev-required">*</span></label>
                        <input type="text" name="tag_name" required placeholder="z.B. Einsteiger" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kategorie <span class="ev-required">*</span></label>
                        <select name="tag_type" class="form-control">
                            <option value="general">🔷 Allgemein</option>
                            <option value="special">⭐ Speziell</option>
                            <option value="format">📋 Format & Niveau</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary ev-btn-block">Hinzufügen</button>
                </form>
            </div>
        </div>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════════
        elseif ($tab === 'design'):
        ?>
        <form method="POST" action="<?= SITE_URL ?>/admin/events/settings/save" class="admin-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrfEsc ?>">
            <input type="hidden" name="_from_tab"  value="design">

            <div class="admin-card ev-tab-panel ev-tab-panel--wide">
                <div class="ev-panel-header">
                    <div>
                        <h3>🎨 Design-Einstellungen</h3>
                        <p>Farben, Badges, Layout und Vorschau der öffentlichen Event-Ansicht.</p>
                    </div>
                </div>

                <section class="ev-settings-section">
                <h4>🎨 Farbpalette</h4>
                <div class="form-grid ev-color-grid">
                    <?php
                    $colorFields = [
                        'color_primary'         => ['Primärfarbe (Buttons, Akzente)',      '#3b82f6'],
                        'color_accent'          => ['Akzentfarbe (Hover, Links)',           '#60a5fa'],
                        'color_card_bg'         => ['Karten-Hintergrund',                  '#f0f7ff'],
                        'color_card_border'     => ['Karten-Rahmenfarbe',                  '#bfdbfe'],
                        'color_cta'             => ['CTA-Button-Farbe',                    '#1e40af'],
                        'color_hdr_from'        => ['Archiv-Header Gradient Von',           '#1d4ed8'],
                        'color_hdr_to'          => ['Archiv-Header Gradient Bis',           '#3b82f6'],
                        'color_hdr_title'       => ['Archiv-Header Titelfarbe',             '#ffffff'],
                        'color_detail_hdr_bg'   => ['Detailseite Header-Hintergrund',       '#0f172a'],
                        'color_detail_hdr_text' => ['Detailseite Titelfarbe',               '#ffffff'],
                        'color_detail_accent'   => ['Detailseite Akzentfarbe',              '#3b82f6'],
                        'color_featured_border' => ['Featured-Karte Rahmen',               '#f59e0b'],
                        'color_online_badge'    => ['Online-Badge Farbe',                  '#059669'],
                        'color_cancelled_bg'    => ['Abgesagt-Badge Hintergrund',          '#fee2e2'],
                    ];
                    foreach ($colorFields as $key => [$label, $default]):
                        $val = htmlspecialchars((string) ($s[$key] ?? $default), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="form-group">
                        <label class="form-label"><?= $label ?></label>
                           <div class="ev-color-row">
                            <input type="color" id="clr_<?= $key ?>" value="<?= $val ?>"
                                class="ev-color-picker"
                                data-ev-color-picker
                                data-ev-color-text="txt_<?= $key ?>">
                            <input type="text" id="txt_<?= $key ?>" name="<?= $key ?>" class="form-control ev-color-text"
                                value="<?= $val ?>"
                                data-ev-color-text
                                data-ev-color-picker="clr_<?= $key ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                </section>

                <section class="ev-settings-section">
                    <h4>🖼️ Archiv-Header</h4>
                    <div class="ev-grid-two">
                        <div class="form-group ev-header-icon-field">
                            <label class="form-label">Header-Icon (Emoji)</label>
                            <input type="text" name="archive_header_icon" id="txt_archive_header_icon"
                                   class="form-control ev-header-icon-input"
                                   value="<?= htmlspecialchars(html_entity_decode((string)($s['archive_header_icon'] ?? '📅'), ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                                   maxlength="8">
                            <small class="form-text">z.B. 📅 🎉 🎤</small>
                        </div>
                        <div>
                            <label class="form-label">Live-Vorschau</label>
                            <div id="ev_hdr_preview" class="ev-header-preview" data-ev-preview-title="<?= htmlspecialchars((string)($s['archive_title'] ?? 'Events'), ENT_QUOTES, 'UTF-8') ?>">
                                <span id="ev_hdr_icon" class="ev-header-preview-icon"></span>
                                <div>
                                    <div id="ev_hdr_title" class="ev-header-preview-title"></div>
                                    <div class="ev-header-preview-note">Archiv-Header</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="ev-settings-section">
                <h4>🏅 Badge-Farben</h4>
                <p class="ev-note">Hintergrund- und Textfarben der Status-Badges auf Event-Karte und Detailseite.</p>
                <div class="form-grid ev-color-grid">
                    <?php
                    $badgeColorFields = [
                        'color_badge_published_bg'    => ['Veröffentlicht – Hintergrund', '#d1fae5'],
                        'color_badge_published_color' => ['Veröffentlicht – Textfarbe',   '#065f46'],
                        'color_badge_draft_bg'        => ['Entwurf – Hintergrund',        '#fef3c7'],
                        'color_badge_draft_color'     => ['Entwurf – Textfarbe',          '#92400e'],
                        'color_badge_cancelled_bg'    => ['Abgesagt – Hintergrund',       '#fee2e2'],
                        'color_badge_cancelled_color' => ['Abgesagt – Textfarbe',         '#991b1b'],
                        'color_badge_completed_bg'    => ['Abgeschlossen – Hintergrund',  '#dbeafe'],
                        'color_badge_completed_color' => ['Abgeschlossen – Textfarbe',    '#1e40af'],
                        'color_badge_featured_bg'     => ['⭐ Featured – Hintergrund',    '#fef3c7'],
                        'color_badge_featured_color'  => ['⭐ Featured – Textfarbe',      '#92400e'],
                        'color_badge_online_bg'       => ['🌐 Online – Hintergrund',      '#d1fae5'],
                        'color_badge_online_color'    => ['🌐 Online – Textfarbe',        '#065f46'],
                    ];
                    foreach ($badgeColorFields as $key => [$label, $default]):
                        $val = htmlspecialchars((string) ($s[$key] ?? $default), ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="form-group">
                        <label class="form-label"><?= $label ?></label>
                        <div class="ev-color-row">
                            <input type="color" id="clr_<?= $key ?>" value="<?= $val ?>" class="ev-color-picker" data-ev-color-picker data-ev-color-text="txt_<?= $key ?>">
                            <input type="text" id="txt_<?= $key ?>" name="<?= $key ?>" class="form-control ev-color-text" value="<?= $val ?>" data-ev-color-text data-ev-color-picker="clr_<?= $key ?>">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                </section>

                <section class="ev-settings-section">
                <h4>📐 Layout &amp; Anzeige</h4>
                <div class="form-grid ev-grid-two">
                    <div class="form-group">
                        <label class="form-label">Ecken-Radius (px)</label>
                        <input type="number" name="border_radius" class="form-control"
                               value="<?= (int)($s['border_radius'] ?? 12) ?>" min="0" max="32">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grid-Spalten</label>
                        <select name="grid_columns" class="form-control">
                            <?php foreach (['auto' => 'Automatisch (responsive)', '2' => '2 Spalten', '3' => '3 Spalten', '4' => '4 Spalten'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($s['grid_columns'] ?? 'auto') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <h5 class="ev-subsection-title">🏷️ Badges auf der Karte</h5>
                <div class="ev-stack-gap ev-admin-assets-gap">
                    <?php foreach ([
                        'show_status_badge'   => '📋 Status-Badge',
                        'show_featured_badge' => '⭐ Featured-Badge',
                        'show_online_badge'   => '🌐 Online-Badge',
                    ] as $key => $label): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="<?= $key ?>" value="1"
                               <?= !empty($s[$key]) && $s[$key] !== '0' ? 'checked' : '' ?>>
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <h5 class="ev-subsection-title">💊 Pills auf der Karte</h5>
                <div class="ev-stack-gap">
                    <?php foreach ([
                        'show_category'  => '📂 Kategorie',
                        'show_city'      => '📍 Ort / Stadt',
                        'show_capacity'  => '👥 Kapazität',
                        'show_date_pill' => '🕐 Uhrzeit',
                        'show_price'     => '💶 Preis',
                        'show_speakers'  => '🎤 Speaker-Anzahl',
                        'show_organizer' => '🏢 Veranstalter',
                        'show_tags'      => '🏷️ Tags',
                    ] as $key => $label): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="<?= $key ?>" value="1"
                               <?= !empty($s[$key]) && $s[$key] !== '0' ? 'checked' : '' ?>>
                        <?= $label ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <p class="ev-note-highlight">
                    ℹ️ <strong>Deaktivierte Badges/Pills</strong> werden auf der öffentlichen Übersichtskarte ausgeblendet.
                </p>
                </section>

                <section class="ev-settings-section ev-settings-section--last">
                    <h4>👁️ Vorschau</h4>
                    <div class="ev-preview-layout">
                        <div class="ev-preview-shell" id="ev_design_preview">
                            <div id="prev-header" class="ev-preview-header">
                                <span id="prev-icon" class="ev-preview-icon-live"><?= htmlspecialchars((string)$s['archive_header_icon'], ENT_QUOTES, 'UTF-8') ?></span>
                                <div>
                                    <div id="prev-title" class="ev-preview-title"><?= htmlspecialchars((string)($s['archive_title'] ?? 'Events'), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="ev-preview-subtitle ev-preview-subtitle-light">Öffentliche Archivkarte</div>
                                </div>
                            </div>
                            <div id="prev-body" class="ev-preview-body">
                                <span id="prev-cta" class="ev-preview-cta">Details ansehen →</span>
                            </div>
                        </div>
                        <div class="ev-note-card">
                            <strong>Hinweis</strong>
                            <span>Die Vorschau zeigt Farben und Radius live. Inhaltliche Felder steuerst du im Bereich „Layout & Anzeige“.</span>
                        </div>
                    </div>
                </section>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Design speichern</button>
                </div>
            </div>
        </form>

        <?php
        // ══════════════════════════════════════════════════════════════════
        elseif ($tab === 'settings'):
        ?>
        <form method="POST" action="<?= SITE_URL ?>/admin/events/settings/save" class="admin-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrfEsc ?>">
            <input type="hidden" name="_from_tab"  value="settings">

            <div class="admin-card ev-tab-panel">
                <div class="ev-panel-header">
                    <div>
                        <h3>⚙️ Einstellungen</h3>
                        <p>Archivseite, Navigation und Shortcode-Nutzung konfigurieren.</p>
                    </div>
                </div>

                <section class="ev-settings-section">
                <h4>📋 Archiv-Seite</h4>
                <div class="form-group">
                    <label class="form-label">Seitentitel</label>
                          <input type="text" id="archive_title" name="archive_title" class="form-control"
                              value="<?= htmlspecialchars((string)$s['archive_title'], ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Events">
                </div>
                <div class="form-group">
                    <label class="form-label">Beschreibungstext</label>
                    <textarea name="archive_description" class="form-control" rows="3"
                              placeholder="Kurze Beschreibung für Besucher..."><?= htmlspecialchars((string)$s['archive_description'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    <small class="form-text">Einleitungstext auf der Übersichtsseite.</small>
                </div>
                <div class="ev-grid-two ev-settings-grid">
                    <div class="form-group">
                        <label class="form-label">URL-Slug</label>
                        <input type="text" name="archive_slug" class="form-control"
                               value="<?= htmlspecialchars((string)($s['archive_slug'] ?? 'events'), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="events">
                        <small class="form-text">z.B. «events» → /events/</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Events pro Seite</label>
                           <input type="number" name="per_page" class="form-control ev-width-120"
                               value="<?= (int)($s['per_page'] ?? 12) ?>"
                               min="4" max="100" step="4">
                    </div>
                </div>
                </section>

                <section class="ev-settings-section">
                <h4>🧭 Navigation</h4>
                <p class="ev-note">Standardmäßig wird kein Link in der öffentlichen Hauptnavigation ausgegeben. Aktiviere diese Option nur, wenn Events dort erscheinen sollen.</p>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="show_nav_link" value="1"
                               <?= (string)($s['show_nav_link'] ?? '0') === '1' ? 'checked' : '' ?>>
                        Link in Hauptnavigation anzeigen
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">Navigations-Label</label>
                    <input type="text" name="nav_label" class="form-control"
                           value="<?= htmlspecialchars((string)($s['nav_label'] ?? 'Veranstaltungen'), ENT_QUOTES, 'UTF-8') ?>"
                           maxlength="40" placeholder="Veranstaltungen">
                    <small class="form-text">Standard: Veranstaltungen. Die Route bleibt <code>/events</code>.</small>
                </div>
                </section>

                <section class="ev-settings-section ev-settings-section--last">
                <h4>ℹ️ Shortcode-Nutzung</h4>
                <p class="ev-note">Event-Liste per Shortcode in Seiteninhalte einbinden:</p>
                <div class="ev-shortcode-box">
                    [cms_events limit="12" featured="1" category="Konferenz"]
                </div>
                <div class="ev-stack-gap--column ev-shortcode-meta">
                    <small><strong>limit</strong> – Anzahl Events (Standard: 12)</small>
                    <small><strong>featured</strong> – Nur Featured-Events (1/0)</small>
                    <small><strong>category</strong> – Filter nach Kategorie-Name</small>
                    <small><strong>upcoming</strong> – Nur zukünftige Events (1/0)</small>
                    <small><strong>online</strong> – Nur Online-Events (1/0)</small>
                </div>
                </section>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
                </div>
            </div>
        </form>
        <?php endif; ?>

        <!-- Delete Modal -->
        <div id="evDeleteModal" class="modal" hidden data-ev-managed-modal aria-hidden="true">
            <div class="modal-content ev-modal-dialog-sm">
                <div class="modal-header">
                    <h3>🗑️ Event löschen</h3>
                    <button class="modal-close" type="button" data-ev-modal-close="evDeleteModal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll das Event <strong id="evDeleteName"></strong> wirklich gelöscht werden?</p>
                    <p class="ev-danger-note">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-ev-modal-close="evDeleteModal">Abbrechen</button>
                    <form method="POST" id="evDeleteForm" class="ev-inline-form-compact">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(CMS\Security::instance()->generateToken('delete_event'), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Approve Modal -->
        <div id="evApproveModal" class="modal" hidden data-ev-managed-modal aria-hidden="true">
            <div class="modal-content ev-modal-dialog-md">
                <div class="modal-header">
                    <h3>✅ Event genehmigen</h3>
                    <button class="modal-close" type="button" data-ev-modal-close="evApproveModal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll das Event <strong id="evApproveName"></strong> genehmigt und veröffentlicht werden?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-ev-modal-close="evApproveModal">Abbrechen</button>
                    <button type="button" id="evApproveConfirm" class="btn btn-primary">✅ Genehmigen</button>
                </div>
            </div>
        </div>

        </div>
        <?php
        $this->end_admin_layout();
    }

    private function normalize_external_url(mixed $value): string
    {
        $url = trim((string) $value);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[[:cntrl:]]/', $url) === 1) {
            return '';
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || ($parts['user'] ?? '') !== '' || ($parts['pass'] ?? '') !== '') {
            return '';
        }

        $host = strtolower(trim((string) ($parts['host'] ?? ''), '[]'));
        if ($host === '' || in_array($host, ['localhost', 'localhost.localdomain'], true) || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return '';
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return '';
        }

        return $url;
    }

    public static function admin_section_for_slug(string $slug): string
    {
        $normalized = function_exists('cms_plugin_admin_normalize_slug')
            ? cms_plugin_admin_normalize_slug($slug)
            : trim((string) preg_replace('/[^a-z0-9_-]+/', '-', strtolower(trim($slug))), '-');

        return self::MENU_SECTIONS[$normalized] ?? 'overview';
    }

    public static function render_overview_bridge(): void
    {
        self::redirect_to_admin_section('overview');
    }

    public static function render_categories_bridge(): void
    {
        self::redirect_to_admin_section('categories');
    }

    public static function render_tags_bridge(): void
    {
        self::redirect_to_admin_section('tags');
    }

    public static function render_design_bridge(): void
    {
        self::redirect_to_admin_section('design');
    }

    public static function render_settings_bridge(): void
    {
        self::redirect_to_admin_section('settings');
    }

    private static function redirect_to_admin_section(string $section): void
    {
        $url = '/admin/events' . ($section === 'overview' ? '' : '?tab=' . rawurlencode($section));

        if (class_exists('CMS\\Router')) {
            CMS\Router::instance()->redirect($url);
            return;
        }

        $safeTarget = htmlspecialchars((string) SITE_URL . $url, ENT_QUOTES, 'UTF-8');
        echo '<div class="admin-card"><p>Weiterleitung zur Event-Verwaltung... <a href="' . $safeTarget . '">Falls nichts passiert, hier klicken</a>.</p></div>';
        echo '<script>window.location.replace(' . json_encode((string) SITE_URL . $url, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . ');</script>';
    }

    private static function render_admin_bridge_fallback_notice(): void
    {
        if (function_exists('cms_plugin_admin_layout_start') && function_exists('cms_plugin_admin_emit_notice') && function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_start('Events', self::MENU_PARENT_SLUG);
            cms_plugin_admin_emit_notice(
                'Die angeforderte Event-Admin-Seite ist derzeit nicht verfuegbar. Bitte Plugin-Setup pruefen.',
                'error',
                'cms-events admin bridge fallback without valid callback'
            );
            cms_plugin_admin_layout_end();
            return;
        }

        echo '<div class="alert alert-error" role="alert">Die angeforderte Event-Admin-Seite ist derzeit nicht verfuegbar.</div>';
    }

    // ══════════════════════════════════════════════════════════════════════════
    // render_form – Neu anlegen + Bearbeiten
    // ══════════════════════════════════════════════════════════════════════════

    public function render_form($event = null): void
    {
        $is_edit    = ($event !== null);
        $page_title = $is_edit ? 'Event bearbeiten' : 'Neues Event anlegen';
        $csrf_token = CMS\Security::instance()->generateToken('save_event');
        $formErrorCode = self::query_param_string('error', 40);

        $db = CMS_Events_Database::instance();
        $categories_db = $db->get_event_categories();
        $tag_presets   = $db->get_event_tag_presets();

        // Aktuelle Tags des Events (JSON-gespeichert)
        $current_tags = [];
        if ($is_edit && !empty($event->tags)) {
            $decoded = json_decode($event->tags, true);
            if (is_array($decoded)) $current_tags = $decoded;
        }

        $this->start_admin_layout($page_title, 'events');
        $this->outputAdminAssets();
        ?>
        <div class="admin-page-header">
            <div>
                <h2><?= $is_edit ? '✏️ Event bearbeiten' : '➕ Neues Event anlegen' ?></h2>
                <p><?= $is_edit
                    ? 'Event-Daten, Ort, Kapazität und Speaker bearbeiten.'
                    : 'Neues Event, Workshop oder Webinar anlegen.' ?></p>
            </div>
            <div class="header-actions">
                <?php if ($is_edit): ?>
                          <a href="<?= function_exists('cms_event_url') ? cms_event_url($event) : SITE_URL . '/event/event-' . (int)$event->id ?>"
                              target="_blank" rel="noopener noreferrer" class="btn btn-secondary">&#128065; Ansehen</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/admin/events" class="btn btn-secondary">← Zurück</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Event erfolgreich gespeichert.</div>
        <?php endif; ?>
        <?php if ($formErrorCode !== ''): ?>
            <div class="alert alert-error">
                ❌ Fehler beim Speichern
                <?php match($formErrorCode) {
                    'csrf'       => print ' – Sicherheitscheck fehlgeschlagen.',
                    'save'       => print ' – Datenbank-Fehler.',
                    'validation' => print ' – Pflichtfeld "Titel" fehlt.',
                    default      => print '.',
                }; ?>
            </div>
        <?php endif; ?>

        <div class="ev-content-max">
        <form method="POST" action="<?= SITE_URL ?>/admin/events/save" id="ev-main-form" class="admin-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="event_id"   value="<?= $is_edit ? (int)$event->id : 0 ?>">

            <!-- ── Block 1: Basis-Informationen ─────────────────────── -->
            <div class="admin-card">
                <h3>📅 Basis-Informationen</h3>

                <div class="form-group">
                    <label class="form-label" for="ev_title">
                        Titel <span class="ev-required">*</span>
                    </label>
                    <input type="text" id="ev_title" name="title" class="form-control"
                           value="<?= htmlspecialchars((string)($event->title ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="z.B. Cloud Computing Summit 2026" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_excerpt">Kurzbeschreibung / Teaser</label>
                    <input type="text" id="ev_excerpt" name="excerpt" class="form-control"
                           value="<?= htmlspecialchars((string)($event->excerpt ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Kurze Zusammenfassung (wird auf Übersichtsseite angezeigt)"
                           maxlength="500">
                    <small class="form-text">Max. 500 Zeichen – erscheint auf der Event-Karte</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_desc">Vollständige Beschreibung</label>
                    <?php
                    if (class_exists('CMS\\Services\\EditorService')) {
                        echo \CMS\Services\EditorService::getInstance()->render(
                            'description',
                            $event->description ?? '',
                            ['height' => 300]
                        );
                    } else { ?>
                        <textarea id="ev_desc" name="description" class="form-control" rows="8"
                                  placeholder="Detaillierte Beschreibung, Agenda, Highlights…"><?= htmlspecialchars((string)($event->description ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                    <?php } ?>
                </div>

                <div class="ev-grid-two">
                    <div class="form-group">
                        <label class="form-label">Kategorie</label>
                        <?php if (!empty($categories_db)): ?>
                            <select name="category" class="form-control">
                                <option value="">-- Keine Kategorie --</option>
                                <?php foreach ($categories_db as $cat): ?>
                                    <option value="<?= htmlspecialchars((string)$cat->name, ENT_QUOTES, 'UTF-8') ?>"
                                            <?= ($event->category ?? '') === $cat->name ? 'selected' : '' ?>>
                                        <?= htmlspecialchars((string)($cat->icon ?? '📂'), ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars((string)$cat->name, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="category" class="form-control"
                                   value="<?= htmlspecialchars((string)($event->category ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="z.B. Konferenz">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <?php foreach ([
                                'published' => 'Veröffentlicht',
                                'draft'     => 'Entwurf',
                                'cancelled' => 'Abgesagt',
                                'completed' => 'Abgeschlossen',
                            ] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($event->status ?? 'published') === $v ? 'selected' : '' ?>>
                                    <?= $l ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label ev-checkbox-inline">
                        <input type="checkbox" name="is_featured" value="1"
                               <?= !empty($event->is_featured) ? 'checked' : '' ?>>
                        ⭐ Als Featured-Event markieren (erscheint prominent)
                    </label>
                </div>
            </div>

            <!-- ── Block 2: Datum & Uhrzeit ───────────────────────────── -->
            <div class="admin-card">
                <h3>🕐 Datum &amp; Uhrzeit</h3>
                <div class="ev-grid-auto-date">
                    <div class="form-group">
                        <label class="form-label" for="ev_date">
                            Startdatum <span class="ev-required">*</span>
                        </label>
                        <input type="date" id="ev_date" name="event_date" class="form-control"
                               value="<?= htmlspecialchars((string)($event->event_date ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Startzeit</label>
                        <input type="time" name="event_time" class="form-control"
                               value="<?= htmlspecialchars((string)($event->event_time ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Enddatum</label>
                        <input type="date" name="end_date" class="form-control"
                               value="<?= htmlspecialchars((string)($event->end_date ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <small class="form-text">Leer = eintägig</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Endzeit</label>
                        <input type="time" name="end_time" class="form-control"
                               value="<?= htmlspecialchars((string)($event->end_time ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>

            <!-- ── Block 3: Veranstaltungsort ─────────────────────────── -->
            <div class="admin-card">
                <h3>📍 Veranstaltungsort</h3>
                <div class="form-group">
                    <label class="checkbox-label ev-checkbox-inline">
                        <input type="checkbox" id="ev_is_online" name="is_online" value="1"
                               <?= !empty($event->is_online) ? 'checked' : '' ?>>
                        🌐 Online-Event (kein physischer Veranstaltungsort)
                    </label>
                </div>

                <div id="ev_online_fields"<?= !empty($event->is_online) ? '' : ' hidden' ?>>
                    <div class="form-group">
                        <label class="form-label">Online-URL (Zoom, Teams, etc.)</label>
                        <input type="url" name="online_url" class="form-control"
                               value="<?= htmlspecialchars((string)($event->online_url ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="https://zoom.us/j/123456789">
                    </div>
                </div>

                <div id="ev_location_fields"<?= !empty($event->is_online) ? ' hidden' : '' ?>>
                    <div class="form-group">
                        <label class="form-label">Veranstaltungsort / Location-Name</label>
                        <input type="text" name="location" class="form-control"
                               value="<?= htmlspecialchars((string)($event->location ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="z.B. Messe Berlin, Kongresszentrum">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="address" class="form-control"
                               value="<?= htmlspecialchars((string)($event->address ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="z.B. Messedamm 22">
                    </div>
                    <div class="ev-grid-three-location">
                        <div class="form-group">
                            <label class="form-label">PLZ</label>
                            <input type="text" name="zip" class="form-control"
                                   value="<?= htmlspecialchars((string)($event->zip ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="10557">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stadt</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?= htmlspecialchars((string)($event->city ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="Berlin">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Land</label>
                            <input type="text" name="country" class="form-control"
                                   value="<?= htmlspecialchars((string)($event->country ?? 'Deutschland'), ENT_QUOTES, 'UTF-8') ?>"
                                   placeholder="Deutschland">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Block 4: Kapazität & Anmeldung ────────────────────── -->
            <div class="admin-card">
                <h3>🎟 Kapazität &amp; Anmeldung</h3>
                <div class="ev-grid-auto-capacity">
                    <div class="form-group">
                        <label class="form-label">Max. Teilnehmer</label>
                        <input type="number" name="capacity" class="form-control"
                               value="<?= (int)($event->capacity ?? 0) ?: '' ?>"
                               min="0" placeholder="0 = unbegrenzt">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Anmelde-URL</label>
                        <input type="url" name="registration_url" class="form-control"
                               value="<?= htmlspecialchars((string)($event->registration_url ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="https://...">
                    </div>
                </div>

                <div class="ev-price-grid">
                    <div class="form-group">
                        <label class="form-label">Preis-Typ</label>
                        <select name="price_type" id="ev_price_type" class="form-control">
                            <?php foreach (['free' => '✅ Kostenlos', 'paid' => '💶 Kostenpflichtig', 'donation' => '💝 Spende'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($event->price_type ?? 'free') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="ev_price_field"<?= ($event->price_type ?? 'free') === 'free' ? ' hidden' : '' ?>>
                        <label class="form-label">Preis</label>
                        <input type="number" name="price" class="form-control"
                               value="<?= htmlspecialchars((string)($event->price ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group" id="ev_currency_field"<?= ($event->price_type ?? 'free') === 'free' ? ' hidden' : '' ?>>
                        <label class="form-label">Währung</label>
                        <select name="price_currency" class="form-control">
                            <?php foreach (['EUR' => '€ EUR', 'USD' => '$ USD', 'CHF' => 'CHF'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($event->price_currency ?? 'EUR') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- ── Block 5: Medien ───────────────────────────────────── -->
            <div class="admin-card">
                <h3>🖼️ Medien</h3>
                <div class="ev-grid-two">
                    <div class="form-group">
                        <label class="form-label">Event-Bild (URL)</label>
                        <input type="url" name="image_url" class="form-control"
                               value="<?= htmlspecialchars((string)($event->image_url ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="https://...">
                        <small class="form-text">Vorschaubild auf der Karte</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Banner-Bild (URL)</label>
                        <input type="url" name="banner_url" class="form-control"
                               value="<?= htmlspecialchars((string)($event->banner_url ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="https://...">
                        <small class="form-text">Großes Bild auf der Event-Detailseite</small>
                    </div>
                </div>
            </div>

            <!-- ── Block 6: Veranstalter ───────────────────────────── -->
            <div class="admin-card">
                <h3>🏢 Veranstalter</h3>
                <div class="ev-grid-two">
                    <div class="form-group">
                        <label class="form-label">Name / Organisation</label>
                        <input type="text" name="organizer_name" class="form-control"
                               value="<?= htmlspecialchars((string)($event->organizer_name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="z.B. 365 Network GmbH">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website</label>
                        <input type="url" name="organizer_website" class="form-control"
                               value="<?= htmlspecialchars((string)($event->organizer_website ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-Mail</label>
                        <input type="email" name="organizer_email" class="form-control"
                               value="<?= htmlspecialchars((string)($event->organizer_email ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="info@beispiel.de">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="organizer_phone" class="form-control"
                               value="<?= htmlspecialchars((string)($event->organizer_phone ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="+49 30 ...">
                    </div>
                </div>
            </div>

            <!-- ── Block 7: Tags ─────────────────────────────────────── -->
            <?php if (!empty($tag_presets)): ?>
            <div class="admin-card">
                <h3>🏷️ Tags &amp; Merkmale</h3>
                <div class="ev-tag-toggle-list">
                    <?php foreach ($tag_presets as $tg): ?>
                        <label class="ev-tag-toggle">
                            <input type="checkbox" name="tags[]" value="<?= htmlspecialchars((string)$tg->tag_name, ENT_QUOTES, 'UTF-8') ?>"
                                   <?= in_array($tg->tag_name, $current_tags) ? 'checked' : '' ?>
                                   class="ev-tag-toggle-input">
                            <span><?= htmlspecialchars((string)$tg->tag_name, ENT_QUOTES, 'UTF-8') ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <small class="form-text ev-form-help">Tags klicken zum Auswählen</small>
            </div>
            <?php endif; ?>

            <!-- ── Speichern-Leiste ──────────────────────────────────── -->
            <div class="admin-card form-actions-card">
                <div class="ev-form-actions-row">
                    <div class="ev-form-actions-buttons">
                        <button type="submit" class="btn btn-primary">
                            <?= $is_edit ? '💾 Änderungen speichern' : '➕ Event anlegen' ?>
                        </button>
                        <a href="<?= SITE_URL ?>/admin/events" class="btn btn-secondary">Abbrechen</a>
                    </div>
                    <span class="form-text">Pflichtfelder (*) müssen ausgefüllt sein.</span>
                </div>
            </div>

        </form>

        <!-- ── Speaker-Zuordnung (außerhalb des Hauptformulars) ─────── -->
        <?php if ($is_edit): ?>
            <?php CMS_Events_Meta_Boxes::instance()->render_speaker_assignment($event); ?>
        <?php else: ?>
            <div class="admin-card ev-info-box">
                <p>
                    💡 <strong>Speaker-Zuordnung</strong> ist nach dem ersten Speichern verfügbar.
                </p>
            </div>
        <?php endif; ?>

        </div><!-- /max-width -->
        <?php
        $this->end_admin_layout();
    }

    private function event_timestamp(mixed $event): ?int
    {
        $date = trim((string) (is_object($event) ? ($event->event_date ?? '') : ''));
        if ($date === '') {
            return null;
        }

        $timestamp = strtotime($date);
        return $timestamp !== false ? $timestamp : null;
    }

    private function is_upcoming_event(mixed $event): bool
    {
        $timestamp = $this->event_timestamp($event);
        $today = strtotime('today');

        return $timestamp !== null && $today !== false && $timestamp >= $today;
    }

    private function is_past_event(mixed $event): bool
    {
        $timestamp = $this->event_timestamp($event);
        $today = strtotime('today');

        return $timestamp !== null && $today !== false && $timestamp < $today;
    }

    private static function query_param_string(string $key, int $maxLength = 64): string
    {
        $value = $_GET[$key] ?? '';
        if (!is_scalar($value)) {
            return '';
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($normalized, 0, $maxLength, 'UTF-8');
        }

        return substr($normalized, 0, $maxLength);
    }
}
