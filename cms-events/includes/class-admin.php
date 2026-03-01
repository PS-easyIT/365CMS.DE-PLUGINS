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

final class CMS_Events_Admin
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
        $isActive    = str_starts_with($currentPath, '/admin/events');

        $menuItems[] = [
            'type'   => 'item',
            'slug'   => 'events',
            'label'  => 'Events',
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
        $this->loadAdminMenu();
        renderAdminLayoutStart('Events', 'events');

        // Admin-CSS laden
        $admin_css = CMS_EVENTS_PLUGIN_DIR . 'assets/css/events-admin.css';
        if (file_exists($admin_css)) {
            echo '<link rel="stylesheet" href="' . CMS_EVENTS_PLUGIN_URL . 'assets/css/events-admin.css?v=' . filemtime($admin_css) . '">' . "\n";
        }

        // Daten aus dem assoziativen Array lesen
        $events      = $data['events']      ?? [];
        $tab         = $data['tab']         ?? 'overview';
        $filter      = $data['filter']      ?? 'all';
        $categories  = $data['categories']  ?? [];
        $tag_presets = $data['tag_presets'] ?? ['general' => [], 'special' => [], 'format' => []];
        $settings    = $data['settings']    ?? [];
        $csrf        = $data['csrf']        ?? '';
        $sec         = CMS\Security::instance();

        // Settings mit Defaults zusammenführen
        $s = array_merge([
            'archive_title'         => 'Events',
            'archive_description'   => 'Aktuelle Veranstaltungen entdecken',
            'archive_slug'          => 'events',
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
        $upcoming  = count(array_filter($events, fn($e) => !empty($e->event_date) && strtotime($e->event_date) >= strtotime('today')));
        ?>

        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>📅 Events</h2>
                <p>Veranstaltungen, Workshops und Webinare verwalten</p>
            </div>
            <div class="header-actions">
                <a href="<?= SITE_URL ?>/admin/events/new" class="btn btn-primary">➕ Neues Event</a>
            </div>
        </div>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">✅ Einstellungen gespeichert.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">✅ Event gelöscht.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                ❌ Fehler:
                <?php match($_GET['error']) {
                    'csrf'       => print 'Sicherheitscheck fehlgeschlagen.',
                    'save'       => print 'Datenbank-Fehler beim Speichern.',
                    'validation' => print 'Pflichtfelder prüfen.',
                    default      => print 'Unbekannter Fehler.',
                }; ?>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="ev-tabs">
            <?php
            $tabs = [
                'overview'   => ['📅', 'Übersicht'],
                'categories' => ['📂', 'Kategorien'],
                'tags'       => ['🏷️', 'Tags'],
                'design'     => ['🎨', 'Design'],
                'settings'   => ['⚙️', 'Einstellungen'],
            ];
            foreach ($tabs as $slug => [$icon, $label]): ?>
                <a href="?tab=<?= $slug ?>" class="ev-tab <?= $tab === $slug ? 'active' : '' ?>">
                    <?= $icon ?> <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

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
            if ($filter === 'upcoming') $filtered = array_values(array_filter($filtered, fn($e) => !empty($e->event_date) && strtotime($e->event_date) >= strtotime('today')));
            elseif ($filter === 'past') $filtered = array_values(array_filter($filtered, fn($e) => !empty($e->event_date) && strtotime($e->event_date) < strtotime('today')));
            elseif ($filter === 'featured') $filtered = array_values(array_filter($filtered, fn($e) => !empty($e->is_featured)));
            elseif ($filter === 'online') $filtered = array_values(array_filter($filtered, fn($e) => !empty($e->is_online)));
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
        <div class="admin-card" style="margin-bottom:1.25rem;">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;">
                <input type="hidden" name="tab" value="overview">
                <div class="form-group" style="margin:0;flex:2;min-width:220px;">
                    <label class="form-label">Titel / Stichwort</label>
                    <input type="text" name="search" class="form-control" placeholder="Titel, Ort, Kategorie…" value="<?= htmlspecialchars($data['search'] ?? '') ?>">
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:160px;">
                    <label class="form-label">Status / Typ</label>
                    <select name="filter" class="form-control">
                        <option value="all"      <?= $filter==='all'      ?'selected':'' ?>>Alle (<?= $total ?>)</option>
                        <option value="upcoming" <?= $filter==='upcoming' ?'selected':'' ?>>📆 Bevorstehend (<?= $upcoming ?>)</option>
                        <option value="past"     <?= $filter==='past'     ?'selected':'' ?>>⌛ Vergangen</option>
                        <option value="featured" <?= $filter==='featured' ?'selected':'' ?>>⭐ Featured (<?= $featured ?>)</option>
                        <option value="online"   <?= $filter==='online'   ?'selected':'' ?>>🌐 Online</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">🔍 Filtern</button>
                <?php if (($data['search'] ?? '') || $filter !== 'all'): ?><a href="?tab=overview" class="btn btn-secondary">✕ Reset</a><?php endif; ?>
            </form>
        </div>

        <?php if (empty($filtered)): ?>
            <div class="ev-empty">
                <div style="font-size:3rem;margin-bottom:1rem;">📅</div>
                <p>Keine Events <?= $filter !== 'all' ? 'in diesem Filter' : '' ?> gefunden.</p>
                <?php if ($filter === 'all'): ?>
                    <a href="<?= SITE_URL ?>/admin/events/new" class="btn btn-primary" style="margin-top:1rem;">
                        Erstes Event anlegen
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="ev-adm-grid">
        <?php foreach ($filtered as $ev):
            $id       = (int)($ev->id ?? 0);
            $title    = $sec->escape($ev->title ?? '');
            $category = $sec->escape($ev->category ?? '');
            $city     = $sec->escape($ev->city ?? '');
            $status   = $ev->status ?? 'draft';
            $isPast   = !empty($ev->event_date) && strtotime($ev->event_date) < strtotime('today');
            $isToday  = !empty($ev->event_date) && date('Y-m-d', strtotime($ev->event_date)) === date('Y-m-d');
            $dateTs   = !empty($ev->event_date) ? strtotime($ev->event_date) : 0;

            $statusCfg = [
                'draft'     => ['Entwurf',       '#92400e', '#fef3c7'],
                'published' => ['Veröffentlicht', '#065f46', '#d1fae5'],
                'cancelled' => ['Abgesagt',       '#991b1b', '#fee2e2'],
                'completed' => ['Abgeschlossen',  '#1e40af', '#dbeafe'],
            ];
            [$stLabel, $stColor, $stBg] = $statusCfg[$status] ?? ['Unbekannt', '#374151', '#f3f4f6'];
        ?>
            <div class="ev-adm-card<?= $isPast ? ' ev-adm-card--past' : '' ?><?= !empty($ev->is_featured) ? ' ev-adm-card--featured' : '' ?>">
                <div class="ev-adm-head">
                    <?php if ($dateTs): ?>
                    <div class="ev-adm-date">
                        <span class="ev-adm-day"><?= date('d', $dateTs) ?></span>
                        <span class="ev-adm-mo"><?= date('M', $dateTs) ?></span>
                        <span class="ev-adm-yr"><?= date('Y', $dateTs) ?></span>
                    </div>
                    <?php else: ?>
                    <div class="ev-adm-date ev-adm-date--nodate">
                        <span style="font-size:1.5rem;">📅</span>
                    </div>
                    <?php endif; ?>
                    <div class="ev-adm-ident">
                        <div class="ev-adm-badges">
                            <?php if (!empty($s['show_status_badge']) && $s['show_status_badge'] !== '0'): ?>
                            <span class="ev-adm-badge" style="color:<?= $stColor ?>;background:<?= $stBg ?>;"><?= $stLabel ?></span>
                            <?php endif; ?>
                            <?php if (!empty($ev->is_featured) && !empty($s['show_featured_badge']) && $s['show_featured_badge'] !== '0'): ?>
                                <span class="ev-adm-badge" style="color:<?= htmlspecialchars($s['color_badge_featured_color']) ?>;background:<?= htmlspecialchars($s['color_badge_featured_bg']) ?>;">⭐ Featured</span>
                            <?php endif; ?>
                            <?php if (!empty($ev->is_online) && !empty($s['show_online_badge']) && $s['show_online_badge'] !== '0'): ?>
                                <span class="ev-adm-badge" style="color:<?= htmlspecialchars($s['color_badge_online_color']) ?>;background:<?= htmlspecialchars($s['color_badge_online_bg']) ?>;">🌐 Online</span>
                            <?php endif; ?>
                            <?php if ($isToday): ?>
                                <span class="ev-adm-badge" style="color:#065f46;background:#bbf7d0;">🔴 Heute</span>
                            <?php endif; ?>
                        </div>
                        <p class="ev-adm-title" title="<?= $title ?>"><?= $title ?></p>
                        <?php if ($category): ?>
                            <p class="ev-adm-sub">📂 <?= $category ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php
                $pills = [];
                if (!empty($ev->is_online) && !empty($ev->online_url)) $pills[] = ['🔗', 'Online-Link vorhanden'];
                elseif ($city && !empty($s['show_city']) && $s['show_city'] !== '0') $pills[] = ['📍', $city];
                if (!empty($ev->event_time) && !empty($s['show_date_pill']) && $s['show_date_pill'] !== '0') $pills[] = ['🕐', substr($ev->event_time, 0, 5) . ' Uhr'];
                if (!empty($ev->capacity) && !empty($s['show_capacity']) && $s['show_capacity'] !== '0') $pills[] = ['👥', (int)$ev->capacity . ' Plätze'];
                if (!empty($ev->registration_url)) $pills[] = ['🎟', 'Anmeldung'];
                if (!empty($s['show_price']) && $s['show_price'] !== '0') {
                    if (!empty($ev->price) && (float)$ev->price > 0) $pills[] = ['💶', number_format((float)$ev->price, 2, ',', '.') . ' ' . ($ev->price_currency ?? 'EUR')];
                    elseif (($ev->price_type ?? 'free') === 'free') $pills[] = ['✅', 'Kostenlos'];
                }
                if (!empty($ev->end_date) && $ev->end_date !== $ev->event_date) $pills[] = ['📆', 'bis ' . date('d.m.Y', strtotime($ev->end_date))];
                if ($category && !empty($s['show_category']) && $s['show_category'] !== '0') $pills[] = ['📂', $category];
                ?>
                <?php if ($pills): ?>
                <div class="ev-adm-pills">
                    <?php foreach ($pills as [$ico, $txt]): ?>
                        <span class="ev-adm-pill"><?= $ico ?> <?= $sec->escape((string)$txt) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="ev-adm-foot">
                    <a href="<?= function_exists('cms_event_url') ? cms_event_url($ev) : SITE_URL . '/event/event-' . $id ?>"
                       target="_blank" class="ev-adm-btn ev-adm-btn-ghost">🌐</a>
                    <a href="<?= SITE_URL ?>/admin/events/edit/<?= $id ?>"
                       class="ev-adm-btn ev-adm-btn-primary">✏️ Bearbeiten</a>
                    <button type="button" class="ev-adm-btn ev-adm-btn-danger"
                            onclick="openEvDeleteModal(<?= $id ?>, '<?= $sec->escape(addslashes($ev->title ?? '')) ?>')">🗑️</button>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php
        // ══════════════════════════════════════════════════════════════════
        elseif ($tab === 'categories'):
        ?>
        <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">
            <div>
                <h3 style="margin:0 0 1rem;">Vorhandene Kategorien (<?= count($categories) ?>)</h3>
                <?php if (empty($categories)): ?>
                    <p style="color:#64748b;">Noch keine Kategorien vorhanden.</p>
                <?php else: ?>
                <div class="ev-tax-list">
                    <?php foreach ($categories as $cat): ?>
                    <div class="ev-tax-row">
                        <span class="ev-tax-name"><?= $sec->escape($cat->icon ?? '📂') ?> <?= $sec->escape($cat->name) ?></span>
                        <span style="font-size:.72rem;color:#94a3b8;padding:.1rem .4rem;background:#f8fafc;border-radius:4px;margin-right:auto;">
                            <?= $sec->escape($cat->slug ?? '') ?>
                        </span>
                        <?php if (($cat->id ?? 0) > 0): ?>
                            <form method="POST" action="<?= SITE_URL ?>/admin/events/category/delete/<?= (int)$cat->id ?>" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <button type="submit" class="ev-del-btn"
                                        onclick="return confirm('Kategorie «<?= $sec->escape(addslashes($cat->name)) ?>» löschen?')">×</button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="ev-side-card">
                <h3 style="margin:0 0 1rem;">➕ Neue Kategorie</h3>
                <form method="POST" action="<?= SITE_URL ?>/admin/events/category/add">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="ev-form-group">
                        <label>Icon (Emoji)</label>
                        <input type="text" name="category_icon" value="📂" maxlength="4"
                               style="font-size:1.4rem;text-align:center;width:60px;">
                    </div>
                    <div class="ev-form-group">
                        <label>Kategorie-Name *</label>
                        <input type="text" name="category_name" required placeholder="z.B. Konferenz">
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">➕ Anlegen</button>
                </form>
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
        <div style="display:grid;grid-template-columns:1fr 280px;gap:1.5rem;align-items:start;">
            <div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
                <?php foreach ($typeLabels as $type => [$icon, $label, $desc]): ?>
                    <div class="ev-side-card">
                        <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;">
                            <span style="font-size:1.25rem;"><?= $icon ?></span>
                            <div>
                                <strong style="font-size:.875rem;"><?= $label ?></strong>
                                <div style="font-size:.72rem;color:#64748b;"><?= $desc ?></div>
                            </div>
                        </div>
                        <div class="ev-tag-list">
                            <?php foreach ($tag_presets[$type] ?? [] as $tg): ?>
                                <span class="ev-tag">
                                    <?= $sec->escape($tg->tag_name) ?>
                                    <form method="POST" action="<?= SITE_URL ?>/admin/events/tagpreset/delete/<?= (int)$tg->id ?>" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="ev-tag-del"
                                                onclick="return confirm('«<?= $sec->escape(addslashes($tg->tag_name)) ?>» löschen?')">×</button>
                                    </form>
                                </span>
                            <?php endforeach; ?>
                            <?php if (empty($tag_presets[$type])): ?>
                                <span style="font-size:.75rem;color:#94a3b8;">Noch keine Einträge.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <div class="ev-side-card">
                <h3 style="margin:0 0 1rem;">➕ Neues Tag</h3>
                <form method="POST" action="<?= SITE_URL ?>/admin/events/tagpreset/add">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="ev-form-group">
                        <label>Tag-Name *</label>
                        <input type="text" name="tag_name" required placeholder="z.B. Einsteiger">
                    </div>
                    <div class="ev-form-group">
                        <label>Kategorie *</label>
                        <select name="tag_type">
                            <option value="general">🔷 Allgemein</option>
                            <option value="special">⭐ Speziell</option>
                            <option value="format">📋 Format & Niveau</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">Hinzufügen</button>
                </form>
            </div>
        </div>

        <?php
        // ══════════════════════════════════════════════════════════════════
        elseif ($tab === 'design'):
        ?>
        <form method="POST" action="<?= SITE_URL ?>/admin/events/settings/save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="_from_tab"  value="design">

            <div class="admin-card">
                <h3>🎨 Farbpalette</h3>
                <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
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
                        $val = htmlspecialchars($s[$key] ?? $default);
                    ?>
                    <div class="form-group">
                        <label class="form-label"><?= $label ?></label>
                        <div style="display:flex;gap:.5rem;align-items:center;">
                            <input type="color" id="clr_<?= $key ?>" value="<?= $val ?>"
                                   style="width:48px;height:36px;border:2px solid #e2e8f0;border-radius:6px;padding:2px;cursor:pointer;"
                                   oninput="document.getElementById('txt_<?= $key ?>').value=this.value">
                            <input type="text" id="txt_<?= $key ?>" name="<?= $key ?>" class="form-control"
                                   value="<?= $val ?>" style="flex:1;font-family:monospace;font-size:.82rem;"
                                   oninput="document.getElementById('clr_<?= $key ?>').value=this.value">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="admin-card">
                <h3>🖼️ Archiv-Header</h3>
                <div class="form-group" style="max-width:120px;">
                    <label class="form-label">Header-Icon (Emoji)</label>
                    <input type="text" name="archive_header_icon" id="txt_archive_header_icon"
                           class="form-control"
                           value="<?= htmlspecialchars(html_entity_decode($s['archive_header_icon'] ?? '📅', ENT_HTML5, 'UTF-8')) ?>"
                           maxlength="8" style="font-size:1.4rem;text-align:center;"
                           oninput="updateEvHdrPreview()">
                    <small class="form-text">z.B. 📅 🎉 🎤</small>
                </div>
                <div id="ev_hdr_preview" style="margin-top:1rem;padding:1rem 1.5rem;border-radius:10px;display:inline-flex;align-items:center;gap:.75rem;font-weight:800;font-size:1rem;">
                    <span id="ev_hdr_icon" style="font-size:2rem;"></span>
                    <div>
                        <div id="ev_hdr_title" style="font-weight:800;font-size:1.1rem;"></div>
                        <div style="font-size:.8rem;opacity:.8;">Vorschau</div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <h3>🏅 Badge-Farben (Status-Badges)</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Hintergrund- und Textfarben der Status-Badges auf der Event-Karte und Detailseite.</p>
                <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:1.25rem;">
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
                <h4 style="margin:1rem 0 .5rem;font-size:.9rem;color:#1e293b;">🏷️ Badges auf der Karte</h4>
                <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:.75rem;">
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
                <h4 style="margin:1rem 0 .5rem;font-size:.9rem;color:#1e293b;">💊 Pills auf der Karte</h4>
                <div style="display:flex;flex-wrap:wrap;gap:1rem;">
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
                <p style="margin-top:.75rem;padding:.5rem .75rem;background:#f0fdf4;border-left:3px solid #86efac;border-radius:4px;font-size:.8rem;color:#166534;">
                    ℹ️ <strong>Deaktivierte Badges/Pills</strong> werden auf der öffentlichen Übersichtskarte ausgeblendet.
                </p>
            </div>

            <div class="admin-card">
                <h3>👁️ Vorschau</h3>
                <div style="max-width:340px;">
                    <div id="prev-header" style="background:linear-gradient(135deg,<?= htmlspecialchars($s['color_hdr_from']) ?>,<?= htmlspecialchars($s['color_hdr_to']) ?>);padding:1.5rem;border-radius:<?= (int)$s['border_radius'] ?>px <?= (int)$s['border_radius'] ?>px 0 0;display:flex;align-items:center;gap:.75rem;">
                        <span id="prev-icon" style="font-size:2rem;"><?= htmlspecialchars($s['archive_header_icon']) ?></span>
                        <div>
                            <div id="prev-title" style="color:<?= htmlspecialchars($s['color_hdr_title']) ?>;font-weight:800;font-size:1.1rem;"><?= htmlspecialchars($s['archive_title'] ?? 'Events') ?></div>
                            <div style="color:<?= htmlspecialchars($s['color_hdr_title']) ?>;font-size:.8rem;opacity:.85;">Vorschau</div>
                        </div>
                    </div>
                    <div id="prev-body" style="background:<?= htmlspecialchars($s['color_card_bg']) ?>;padding:1rem;border:1px solid <?= htmlspecialchars($s['color_card_border'] ?? '#bfdbfe') ?>;border-top:none;border-radius:0 0 <?= (int)$s['border_radius'] ?>px <?= (int)$s['border_radius'] ?>px;">
                        <span id="prev-cta" style="display:inline-block;padding:.3rem .8rem;background:<?= htmlspecialchars($s['color_primary']) ?>;color:#fff;border-radius:6px;font-size:.8rem;font-weight:700;">Details ansehen →</span>
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
            function updateEvHdrPreview(){
                var from  = (document.getElementById('txt_color_hdr_from')  || {value:'#1d4ed8'}).value;
                var to    = (document.getElementById('txt_color_hdr_to')    || {value:'#3b82f6'}).value;
                var color = (document.getElementById('txt_color_hdr_title') || {value:'#ffffff'}).value;
                var icon  = (document.getElementById('txt_archive_header_icon') || {value:'📅'}).value;
                var prev  = document.getElementById('ev_hdr_preview');
                var icoEl = document.getElementById('ev_hdr_icon');
                var ttlEl = document.getElementById('ev_hdr_title');
                if (prev)  { prev.style.background = 'linear-gradient(135deg,'+from+','+to+')'; prev.style.color = color; }
                if (icoEl) { icoEl.textContent = icon; }
                if (ttlEl) { ttlEl.textContent = '<?= addslashes(htmlspecialchars($s['archive_title'] ?? 'Events')) ?>'; ttlEl.style.color = color; }
            }
            window.updateEvHdrPreview = updateEvHdrPreview;
            ['txt_color_hdr_from','txt_color_hdr_to','txt_color_hdr_title','txt_archive_header_icon'].forEach(function(id){
                var el = document.getElementById(id);
                if(el) el.addEventListener('input', updateEvHdrPreview);
            });
            updateEvHdrPreview();
        })();
        </script>

        <?php
        // ══════════════════════════════════════════════════════════════════
        elseif ($tab === 'settings'):
        ?>
        <form method="POST" action="<?= SITE_URL ?>/admin/events/settings/save">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <input type="hidden" name="_from_tab"  value="settings">

            <div class="admin-card">
                <h3>📋 Archiv-Seite</h3>
                <div class="form-group">
                    <label class="form-label">Seitentitel</label>
                    <input type="text" name="archive_title" class="form-control"
                           value="<?= htmlspecialchars($s['archive_title']) ?>"
                           placeholder="Events">
                </div>
                <div class="form-group">
                    <label class="form-label">Beschreibungstext</label>
                    <textarea name="archive_description" class="form-control" rows="3"
                              placeholder="Kurze Beschreibung für Besucher..."><?= htmlspecialchars($s['archive_description']) ?></textarea>
                    <small class="form-text">Einleitungstext auf der Übersichtsseite.</small>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:.5rem;">
                    <div class="form-group">
                        <label class="form-label">URL-Slug</label>
                        <input type="text" name="archive_slug" class="form-control"
                               value="<?= htmlspecialchars($s['archive_slug'] ?? 'events') ?>"
                               placeholder="events">
                        <small class="form-text">z.B. «events» → /events/</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Events pro Seite</label>
                        <input type="number" name="per_page" class="form-control"
                               value="<?= (int)($s['per_page'] ?? 12) ?>"
                               min="4" max="100" step="4" style="width:120px;">
                    </div>
                </div>
            </div>
            <div class="admin-card">
                <h3>ℹ️ Shortcode-Nutzung</h3>
                <p style="color:#64748b;font-size:.875rem;margin-bottom:.5rem;">Event-Liste per Shortcode in Seiteninhalte einbinden:</p>
                <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:.75rem 1rem;font-family:monospace;font-size:.875rem;color:#1d4ed8;">
                    [cms_events limit="12" featured="1" category="Konferenz"]
                </div>
                <div style="margin-top:.75rem;display:flex;flex-direction:column;gap:.35rem;">
                    <small style="color:#64748b;"><strong>limit</strong> – Anzahl Events (Standard: 12)</small>
                    <small style="color:#64748b;"><strong>featured</strong> – Nur Featured-Events (1/0)</small>
                    <small style="color:#64748b;"><strong>category</strong> – Filter nach Kategorie-Name</small>
                    <small style="color:#64748b;"><strong>upcoming</strong> – Nur zukünftige Events (1/0)</small>
                    <small style="color:#64748b;"><strong>online</strong> – Nur Online-Events (1/0)</small>
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
        <div id="evDeleteModal" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header">
                    <h3>🗑️ Event löschen</h3>
                    <button class="modal-close" onclick="closeModal('evDeleteModal')">&times;</button>
                </div>
                <div class="modal-body">
                    <p>Soll das Event <strong id="evDeleteName"></strong> wirklich gelöscht werden?</p>
                    <p style="color:#ef4444;font-size:.875rem;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('evDeleteModal')">Abbrechen</button>
                    <form method="POST" id="evDeleteForm" style="display:inline;">
                        <input type="hidden" name="csrf_token" value="<?= CMS\Security::instance()->generateToken('delete_event') ?>">
                        <button type="submit" class="btn btn-danger">🗑️ Endgültig löschen</button>
                    </form>
                </div>
            </div>
        </div>

        <script>
        function openEvDeleteModal(id, name) {
            document.getElementById('evDeleteName').textContent = name;
            document.getElementById('evDeleteForm').action = '<?= SITE_URL ?>/admin/events/delete/' + id;
            openModal('evDeleteModal');
        }
        </script>
        <?php
        renderAdminLayoutEnd();
    }

    // ══════════════════════════════════════════════════════════════════════════
    // render_form – Neu anlegen + Bearbeiten
    // ══════════════════════════════════════════════════════════════════════════

    public function render_form($event = null): void
    {
        $this->loadAdminMenu();
        $is_edit    = ($event !== null);
        $page_title = $is_edit ? 'Event bearbeiten' : 'Neues Event anlegen';
        $csrf_token = CMS\Security::instance()->generateToken('save_event');

        $db = CMS_Events_Database::instance();
        $categories_db = $db->get_event_categories();
        $tag_presets   = $db->get_event_tag_presets();

        // Aktuelle Tags des Events (JSON-gespeichert)
        $current_tags = [];
        if ($is_edit && !empty($event->tags)) {
            $decoded = json_decode($event->tags, true);
            if (is_array($decoded)) $current_tags = $decoded;
        }

        renderAdminLayoutStart($page_title, 'events');
        ?>
        <div class="admin-page-header">
            <div>
                <h2><?= $is_edit ? '✏️ Event bearbeiten' : '➕ Neues Event anlegen' ?></h2>
                <p><?= $is_edit
                    ? 'Event-Daten, Ort, Kapazität und Speaker bearbeiten'
                    : 'Neues Event, Workshop oder Webinar anlegen' ?></p>
            </div>
            <div class="header-actions">
                <?php if ($is_edit): ?>
                    <a href="<?= function_exists('cms_event_url') ? cms_event_url($event) : SITE_URL . '/event/event-' . (int)$event->id ?>"
                       target="_blank" class="btn btn-secondary">&#128065; Ansehen</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/admin/events" class="btn btn-secondary">← Zurück</a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✅ Event erfolgreich gespeichert.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                ❌ Fehler beim Speichern
                <?php match($_GET['error']) {
                    'csrf'       => print ' – Sicherheitscheck fehlgeschlagen.',
                    'save'       => print ' – Datenbank-Fehler.',
                    'validation' => print ' – Pflichtfeld "Titel" fehlt.',
                    default      => print '.',
                }; ?>
            </div>
        <?php endif; ?>

        <div style="max-width:940px;">
        <form method="POST" action="<?= SITE_URL ?>/admin/events/save" id="ev-main-form">
            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
            <input type="hidden" name="event_id"   value="<?= $is_edit ? (int)$event->id : 0 ?>">

            <!-- ── Block 1: Basis-Informationen ─────────────────────── -->
            <div class="admin-card">
                <h3>📅 Basis-Informationen</h3>

                <div class="form-group">
                    <label class="form-label" for="ev_title">
                        Titel <span style="color:#ef4444;">*</span>
                    </label>
                    <input type="text" id="ev_title" name="title" class="form-control"
                           value="<?= htmlspecialchars($event->title ?? '') ?>"
                           placeholder="z.B. Cloud Computing Summit 2026" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_excerpt">Kurzbeschreibung / Teaser</label>
                    <input type="text" id="ev_excerpt" name="excerpt" class="form-control"
                           value="<?= htmlspecialchars($event->excerpt ?? '') ?>"
                           placeholder="Kurze Zusammenfassung (wird auf Übersichtsseite angezeigt)"
                           maxlength="500">
                    <small class="form-text">Max. 500 Zeichen – erscheint auf der Event-Karte</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="ev_desc">Vollständige Beschreibung</label>
                    <?php
                    if (class_exists('\CMS\Services\EditorService')) {
                        echo \CMS\Services\EditorService::getInstance()->render(
                            'description',
                            $event->description ?? '',
                            ['height' => 300]
                        );
                    } else { ?>
                        <textarea id="ev_desc" name="description" class="form-control" rows="8"
                                  placeholder="Detaillierte Beschreibung, Agenda, Highlights…"><?= htmlspecialchars($event->description ?? '') ?></textarea>
                    <?php } ?>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Kategorie</label>
                        <?php if (!empty($categories_db)): ?>
                            <select name="category" class="form-control">
                                <option value="">-- Keine Kategorie --</option>
                                <?php foreach ($categories_db as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat->name) ?>"
                                            <?= ($event->category ?? '') === $cat->name ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat->icon ?? '📂') . ' ' . htmlspecialchars($cat->name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="category" class="form-control"
                                   value="<?= htmlspecialchars($event->category ?? '') ?>"
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
                    <label class="checkbox-label" style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;">
                        <input type="checkbox" name="is_featured" value="1"
                               <?= !empty($event->is_featured) ? 'checked' : '' ?>>
                        ⭐ Als Featured-Event markieren (erscheint prominent)
                    </label>
                </div>
            </div>

            <!-- ── Block 2: Datum & Uhrzeit ───────────────────────────── -->
            <div class="admin-card">
                <h3>🕐 Datum &amp; Uhrzeit</h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label" for="ev_date">
                            Startdatum <span style="color:#ef4444;">*</span>
                        </label>
                        <input type="date" id="ev_date" name="event_date" class="form-control"
                               value="<?= htmlspecialchars($event->event_date ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Startzeit</label>
                        <input type="time" name="event_time" class="form-control"
                               value="<?= htmlspecialchars($event->event_time ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Enddatum</label>
                        <input type="date" name="end_date" class="form-control"
                               value="<?= htmlspecialchars($event->end_date ?? '') ?>">
                        <small class="form-text">Leer = eintägig</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Endzeit</label>
                        <input type="time" name="end_time" class="form-control"
                               value="<?= htmlspecialchars($event->end_time ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- ── Block 3: Veranstaltungsort ─────────────────────────── -->
            <div class="admin-card">
                <h3>📍 Veranstaltungsort</h3>
                <div class="form-group">
                    <label class="checkbox-label" style="display:inline-flex;align-items:center;gap:.5rem;cursor:pointer;">
                        <input type="checkbox" id="ev_is_online" name="is_online" value="1"
                               <?= !empty($event->is_online) ? 'checked' : '' ?>
                               onchange="evToggleLocation()">
                        🌐 Online-Event (kein physischer Veranstaltungsort)
                    </label>
                </div>

                <div id="ev_online_fields" style="<?= !empty($event->is_online) ? '' : 'display:none;' ?>">
                    <div class="form-group">
                        <label class="form-label">Online-URL (Zoom, Teams, etc.)</label>
                        <input type="url" name="online_url" class="form-control"
                               value="<?= htmlspecialchars($event->online_url ?? '') ?>"
                               placeholder="https://zoom.us/j/123456789">
                    </div>
                </div>

                <div id="ev_location_fields" style="<?= !empty($event->is_online) ? 'display:none;' : '' ?>">
                    <div class="form-group">
                        <label class="form-label">Veranstaltungsort / Location-Name</label>
                        <input type="text" name="location" class="form-control"
                               value="<?= htmlspecialchars($event->location ?? '') ?>"
                               placeholder="z.B. Messe Berlin, Kongresszentrum">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="address" class="form-control"
                               value="<?= htmlspecialchars($event->address ?? '') ?>"
                               placeholder="z.B. Messedamm 22">
                    </div>
                    <div style="display:grid;grid-template-columns:120px 1fr 1fr;gap:1rem;">
                        <div class="form-group">
                            <label class="form-label">PLZ</label>
                            <input type="text" name="zip" class="form-control"
                                   value="<?= htmlspecialchars($event->zip ?? '') ?>"
                                   placeholder="10557">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Stadt</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?= htmlspecialchars($event->city ?? '') ?>"
                                   placeholder="Berlin">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Land</label>
                            <input type="text" name="country" class="form-control"
                                   value="<?= htmlspecialchars($event->country ?? 'Deutschland') ?>"
                                   placeholder="Deutschland">
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Block 4: Kapazität & Anmeldung ────────────────────── -->
            <div class="admin-card">
                <h3>🎟 Kapazität &amp; Anmeldung</h3>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Max. Teilnehmer</label>
                        <input type="number" name="capacity" class="form-control"
                               value="<?= (int)($event->capacity ?? 0) ?: '' ?>"
                               min="0" placeholder="0 = unbegrenzt">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Anmelde-URL</label>
                        <input type="url" name="registration_url" class="form-control"
                               value="<?= htmlspecialchars($event->registration_url ?? '') ?>"
                               placeholder="https://...">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 120px;gap:1rem;margin-top:.5rem;">
                    <div class="form-group">
                        <label class="form-label">Preis-Typ</label>
                        <select name="price_type" id="ev_price_type" class="form-control"
                                onchange="evTogglePrice()">
                            <?php foreach (['free' => '✅ Kostenlos', 'paid' => '💶 Kostenpflichtig', 'donation' => '💝 Spende'] as $v => $l): ?>
                                <option value="<?= $v ?>" <?= ($event->price_type ?? 'free') === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="ev_price_field" style="<?= ($event->price_type ?? 'free') === 'free' ? 'display:none;' : '' ?>">
                        <label class="form-label">Preis</label>
                        <input type="number" name="price" class="form-control"
                               value="<?= htmlspecialchars((string)($event->price ?? '')) ?>"
                               min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-group" id="ev_currency_field" style="<?= ($event->price_type ?? 'free') === 'free' ? 'display:none;' : '' ?>">
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
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Event-Bild (URL)</label>
                        <input type="url" name="image_url" class="form-control"
                               value="<?= htmlspecialchars($event->image_url ?? '') ?>"
                               placeholder="https://...">
                        <small class="form-text">Vorschaubild auf der Karte</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Banner-Bild (URL)</label>
                        <input type="url" name="banner_url" class="form-control"
                               value="<?= htmlspecialchars($event->banner_url ?? '') ?>"
                               placeholder="https://...">
                        <small class="form-text">Großes Bild auf der Event-Detailseite</small>
                    </div>
                </div>
            </div>

            <!-- ── Block 6: Veranstalter ───────────────────────────── -->
            <div class="admin-card">
                <h3>🏢 Veranstalter</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Name / Organisation</label>
                        <input type="text" name="organizer_name" class="form-control"
                               value="<?= htmlspecialchars($event->organizer_name ?? '') ?>"
                               placeholder="z.B. 365 Network GmbH">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website</label>
                        <input type="url" name="organizer_website" class="form-control"
                               value="<?= htmlspecialchars($event->organizer_website ?? '') ?>"
                               placeholder="https://...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-Mail</label>
                        <input type="email" name="organizer_email" class="form-control"
                               value="<?= htmlspecialchars($event->organizer_email ?? '') ?>"
                               placeholder="info@beispiel.de">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="text" name="organizer_phone" class="form-control"
                               value="<?= htmlspecialchars($event->organizer_phone ?? '') ?>"
                               placeholder="+49 30 ...">
                    </div>
                </div>
            </div>

            <!-- ── Block 7: Tags ─────────────────────────────────────── -->
            <?php if (!empty($tag_presets)): ?>
            <div class="admin-card">
                <h3>🏷️ Tags &amp; Merkmale</h3>
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
                    <?php foreach ($tag_presets as $tg): ?>
                        <label class="ev-tag-toggle"
                               style="display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .65rem;border:1.5px solid #bfdbfe;border-radius:50px;cursor:pointer;font-size:.8rem;background:#f8fafc;transition:all .15s;">
                            <input type="checkbox" name="tags[]" value="<?= htmlspecialchars($tg->tag_name) ?>"
                                   <?= in_array($tg->tag_name, $current_tags) ? 'checked' : '' ?>
                                   style="display:none;">
                            <span><?= htmlspecialchars($tg->tag_name) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <small class="form-text" style="margin-top:.5rem;display:block;">Tags klicken zum Auswählen</small>
            </div>
            <?php endif; ?>

            <!-- ── Speichern-Leiste ──────────────────────────────────── -->
            <div class="admin-card">
                <div style="display:flex;align-items:center;gap:.75rem;justify-content:space-between;flex-wrap:wrap;">
                    <div style="display:flex;gap:.75rem;">
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
            <div class="admin-card" style="background:#f0f9ff;border-color:#bae6fd;">
                <p style="margin:0;color:#0369a1;font-size:.875rem;">
                    💡 <strong>Speaker-Zuordnung</strong> ist nach dem ersten Speichern verfügbar.
                </p>
            </div>
        <?php endif; ?>

        </div><!-- /max-width -->

        <script>
        function evToggleLocation() {
            var online = document.getElementById('ev_is_online').checked;
            document.getElementById('ev_online_fields').style.display   = online ? '' : 'none';
            document.getElementById('ev_location_fields').style.display = online ? 'none' : '';
        }
        function evTogglePrice() {
            var t = document.getElementById('ev_price_type').value;
            var show = t !== 'free';
            document.getElementById('ev_price_field').style.display    = show ? '' : 'none';
            document.getElementById('ev_currency_field').style.display = show ? '' : 'none';
        }
        document.querySelectorAll('.ev-tag-toggle').forEach(function(lbl){
            var inp = lbl.querySelector('input');
            function sync(){ lbl.style.background = inp.checked ? '#eff6ff' : '#f8fafc'; lbl.style.borderColor = inp.checked ? '#3b82f6' : '#bfdbfe'; lbl.style.color = inp.checked ? '#1d4ed8' : ''; lbl.style.fontWeight = inp.checked ? '600' : ''; }
            sync(); lbl.addEventListener('change', sync);
        });
        </script>
        <?php
        renderAdminLayoutEnd();
    }
}
