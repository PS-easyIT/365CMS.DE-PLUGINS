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
        $menu_file = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menu_file) && !function_exists('renderAdminLayoutStart')) {
            require_once $menu_file;
        }
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }
    public function add_menu_item(array $items): array
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $items[] = [
            'type'   => 'item',
            'slug'   => 'speakers',
            'label'  => 'Speaker',
            'icon'   => '🎤',
            'url'    => '/admin/speakers',
            'active' => str_starts_with($path, '/admin/speakers'),
        ];
        return $items;
    }

    // ─── LIST ────────────────────────────────────────────────
    public function render_list(array $data): void
    {
        $menu_file = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menu_file) && !function_exists('renderAdminLayoutStart')) {
            require_once $menu_file;
        }
        renderAdminLayoutStart('Speaker', 'speakers');

        $speakers  = $data['speakers']  ?? [];
        $tab       = $data['tab']       ?? 'overview';
        $filter    = $data['filter']    ?? 'all';
        $search    = $data['search']    ?? '';
        $settings  = $data['settings']  ?? [];
        $csrf      = $data['csrf']      ?? '';
        $companies = $data['companies'] ?? [];

        $s = array_merge([
            'archive_title'              => 'Speaker Directory',
            'archive_description'        => 'Finden Sie den passenden Redner für Ihr Event',
            'archive_per_page'           => '12',
            'archive_header_icon'        => '🎤',
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
        $featured = count(array_filter($speakers, fn($e) => ($e->is_featured ?? 0)));
        $verified = count(array_filter($speakers, fn($e) => ($e->is_verified ?? 0)));
        $avail    = count(array_filter($speakers, fn($e) => ($e->availability ?? '') === 'available'));
        ?>
        <!-- Page Header -->
        <div class="admin-page-header">
            <div>
                <h2>🎤 Speaker</h2>
                <p>Verwalte alle Speaker-Profile, Themen und Auftritte</p>
            </div>
            <div class="header-actions">
                <a href="<?= SITE_URL ?>/speakers" class="btn btn-secondary" target="_blank">🌐 Öffentlich</a>
                <a href="<?= SITE_URL ?>/admin/speakers/new" class="btn btn-primary">➕ Neuer Speaker</a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Änderungen gespeichert.</div><?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">✅ Speaker gelöscht.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error">❌ Fehler: <?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

        <!-- Tabs -->
        <div class="spk-tabs">
            <?php
            $tabs = [
                'overview'  => ['👀', 'Übersicht'],
                'topics'    => ['🏷️', 'Themen'],
                'design'    => ['🎨', 'Design'],
                'settings'  => ['⚙️', 'Einstellungen'],
            ];
            foreach ($tabs as $slug => [$icon, $label]): ?>
                <a href="?tab=<?= $slug ?>" class="spk-tab <?= $tab === $slug ? 'spk-tab--active' : '' ?>">
                    <?= $icon ?> <?= $label ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($tab === 'overview'): ?>
        <!-- Stats -->
        <div class="dashboard-grid" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin-bottom:1.5rem;">
            <div class="stat-card"><h3 style="font-size:.8rem;color:#64748b;margin:0 0 .25rem;">Gesamt</h3><div class="stat-number"><?= $total ?></div></div>
            <div class="stat-card"><h3 style="font-size:.8rem;color:#64748b;margin:0 0 .25rem;">Aktiv</h3><div class="stat-number" style="color:#16a34a;"><?= $active ?></div></div>
            <div class="stat-card"><h3 style="font-size:.8rem;color:#64748b;margin:0 0 .25rem;">Verfügbar</h3><div class="stat-number" style="color:#8b5cf6;"><?= $avail ?></div></div>
            <div class="stat-card"><h3 style="font-size:.8rem;color:#64748b;margin:0 0 .25rem;">Featured</h3><div class="stat-number" style="color:#d97706;"><?= $featured ?></div></div>
            <div class="stat-card"><h3 style="font-size:.8rem;color:#64748b;margin:0 0 .25rem;">Verifiziert</h3><div class="stat-number" style="color:#7c3aed;"><?= $verified ?></div></div>
        </div>

        <!-- Filter Bar -->
        <div class="admin-card" style="margin-bottom:1.25rem;">
            <form method="GET" style="display:flex;flex-wrap:wrap;gap:.75rem;align-items:flex-end;">
                <input type="hidden" name="tab" value="overview">
                <div class="form-group" style="margin:0;flex:2;min-width:220px;">
                    <label class="form-label">Name / Stichwort</label>
                    <input type="text" name="search" class="form-control" placeholder="Name, Ort, Position, Thema…" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="form-group" style="margin:0;flex:1;min-width:160px;">
                    <label class="form-label">Status / Typ</label>
                    <select name="filter" class="form-control">
                        <option value="all"       <?= $filter==='all'       ?'selected':'' ?>>Alle (<?= $total ?>)</option>
                        <option value="available" <?= $filter==='available' ?'selected':'' ?>>Verfügbar (<?= $avail ?>)</option>
                        <option value="featured"  <?= $filter==='featured'  ?'selected':'' ?>>Featured (<?= $featured ?>)</option>
                        <option value="verified"  <?= $filter==='verified'  ?'selected':'' ?>>Verifiziert (<?= $verified ?>)</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">🔍 Filtern</button>
                <?php if ($search || $filter !== 'all'): ?><a href="?tab=overview" class="btn btn-secondary">✕ Reset</a><?php endif; ?>
            </form>
        </div>

        <!-- Speaker Grid -->
        <?php if (empty($speakers)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0;">🎤</p>
            <p><strong>Noch keine Speaker vorhanden</strong></p>
            <p class="text-muted">Erstelle den ersten Speaker-Eintrag.</p>
            <a href="<?= SITE_URL ?>/admin/speakers/new" class="btn btn-primary" style="margin-top:1rem;">➕ Speaker anlegen</a>
        </div>
        <?php else: ?>
        <div class="spk-adm-grid">
            <?php foreach ($speakers as $sp):
                $fn  = htmlspecialchars($sp->first_name ?? '');
                $ln  = htmlspecialchars($sp->last_name  ?? '');
                $name = trim("$fn $ln") ?: 'Unbekannt';
                $parts = preg_split('/\s+/', $name);
                $initials = mb_strtoupper(mb_substr($parts[0],0,1) . (isset($parts[1]) ? mb_substr($parts[1],0,1) : ''));
                $pcolors  = [['#8b5cf6','#a855f7'],['#7c3aed','#8b5cf6'],['#a855f7','#c084fc'],['#6d28d9','#8b5cf6'],['#9333ea','#a855f7']];
                $cp  = $pcolors[abs(crc32($name)) % count($pcolors)];
                $bg  = "linear-gradient(135deg,{$cp[0]},{$cp[1]})";
                $spAvail = $sp->availability ?? 'available';
                $availLabels = ['available'=>'✅ Verfügbar','limited'=>'⚠️ Begrenzt','booked'=>'🔴 Ausgebucht'];
                $availColors = ['available'=>'#065f46','limited'=>'#78350f','booked'=>'#7f1d1d'];
                $availBg     = ['available'=>'#d1fae5','limited'=>'#fef3c7','booked'=>'#fee2e2'];
                $travel = $sp->travel_radius ?? 'national';
                $travelLabel = ['local'=>'📍 Lokal','regional'=>'🗺️ Regional','national'=>'🇩🇪 DACH','international'=>'🌍 International','worldwide'=>'🌐 Weltweit'][$travel] ?? $travel;
                $formats = json_decode($sp->formats ?? '[]', true) ?: [];
                $fmtLabels = ['keynote'=>'Keynote','workshop'=>'Workshop','panel'=>'Panel','moderation'=>'Moderation','training'=>'Training','consulting'=>'Beratung','interview'=>'Interview','webinar'=>'Webinar'];
                $company = htmlspecialchars($sp->company_linked_name ?? $sp->company ?? '');
                $slug = CMS_Speakers_Database::generate_slug($sp);
            ?>
            <div class="spk-adm-card">
                <div class="spk-adm-top">
                    <?php if (!empty($sp->photo_url)): ?>
                        <div class="spk-adm-avatar" style="background:#ede9fe;padding:0;overflow:hidden;">
                            <img src="<?= htmlspecialchars($sp->photo_url) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                    <?php else: ?>
                        <div class="spk-adm-avatar" style="background:<?= $bg ?>"><?= $initials ?></div>
                    <?php endif; ?>
                    <div class="spk-adm-identity">
                        <div style="display:flex;gap:.3rem;flex-wrap:wrap;margin-bottom:.2rem;">
                            <?php if ($sp->is_verified ?? 0): ?><span class="status-badge active" style="font-size:.62rem;">✔ Verifiziert</span><?php endif; ?>
                            <?php if ($sp->is_featured ?? 0): ?><span class="status-badge admin" style="font-size:.62rem;">⭐ Featured</span><?php endif; ?>
                            <span style="font-size:.62rem;padding:.1rem .4rem;border-radius:4px;background:<?= $availBg[$spAvail]??'#f1f5f9' ?>;color:<?= $availColors[$spAvail]??'#374151' ?>;"><?= $availLabels[$spAvail]??$spAvail ?></span>
                        </div>
                        <p class="spk-adm-name"><?= $name ?></p>
                        <?php if (!empty($sp->position)): ?><p class="spk-adm-sub"><?= htmlspecialchars($sp->position) ?></p><?php endif; ?>
                        <?php if ($company): ?><p class="spk-adm-sub" style="color:#94a3b8;"><?= $company ?></p><?php endif; ?>
                    </div>
                </div>
                <div class="spk-adm-pills">
                    <?php if (!empty($sp->location_city)): ?>
                        <span class="spk-adm-pill">📍 <?= htmlspecialchars($sp->location_city) ?></span>
                    <?php endif; ?>
                    <span class="spk-adm-pill"><?= $travelLabel ?></span>
                    <?php foreach (array_slice($formats, 0, 2) as $fmt): ?>
                        <span class="spk-adm-pill" style="background:#f5f3ff;color:#7c3aed;border-color:#ddd6fe;"><?= htmlspecialchars($fmtLabels[$fmt] ?? $fmt) ?></span>
                    <?php endforeach; ?>
                    <?php if (!empty($sp->email)): ?><span class="spk-adm-pill">✉ <?= htmlspecialchars($sp->email) ?></span><?php endif; ?>
                    <?php if (!empty($sp->speaking_fee_min) || !empty($sp->speaking_fee_max)): ?>
                        <span class="spk-adm-pill" style="background:#faf5ff;color:#6d28d9;border-color:#ddd6fe;">💶 <?= $sp->speaking_fee_min ? number_format((float)$sp->speaking_fee_min,0,',','.') : '' ?><?= ($sp->speaking_fee_min && $sp->speaking_fee_max) ? '–' : '' ?><?= $sp->speaking_fee_max ? number_format((float)$sp->speaking_fee_max,0,',','.') . ' €' : '' ?></span>
                    <?php endif; ?>
                </div>
                <div class="spk-adm-footer">
                    <a href="<?= SITE_URL ?>/speakers/<?= $slug ?>" class="spk-adm-btn spk-adm-btn-ghost" target="_blank">🌐</a>
                    <a href="<?= SITE_URL ?>/admin/speakers/edit/<?= (int)$sp->id ?>" class="spk-adm-btn spk-adm-btn-primary">✏️ Bearbeiten</a>
                    <form method="POST" action="<?= SITE_URL ?>/admin/speakers/delete/<?= (int)$sp->id ?>" style="margin:0;" onsubmit="return confirm('Speaker wirklich löschen?');">
                        <input type="hidden" name="csrf_token" value="<?= CMS\Security::instance()->generateToken('delete_speaker') ?>">
                        <button type="submit" class="spk-adm-btn spk-adm-btn-danger">🗑️</button>
                    </form>
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
                        <span style="background:rgba(139,92,246,.15);border-radius:10px;padding:.05rem .35rem;font-size:.68rem;margin-left:.3rem;"><?= count($entries) ?></span>
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

        <style>
        .spk-tabs{display:flex;gap:.25rem;border-bottom:2px solid #e2e8f0;margin-bottom:1.5rem;overflow-x:auto;}
        .spk-tab{padding:.6rem 1.1rem;border-radius:8px 8px 0 0;font-size:.875rem;font-weight:600;text-decoration:none;color:#475569;white-space:nowrap;transition:all .15s;}
        .spk-tab:hover{background:#f5f3ff;color:#7c3aed;}
        .spk-tab--active{background:#f5f3ff;color:#7c3aed;border-bottom:2px solid #8b5cf6;margin-bottom:-2px;}
        .spk-adm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(310px,1fr));gap:1.25rem;}
        .spk-adm-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;display:flex;flex-direction:column;gap:.75rem;transition:box-shadow .2s;}
        .spk-adm-card:hover{box-shadow:0 6px 20px rgba(139,92,246,.12);border-color:#ddd6fe;}
        .spk-adm-top{display:flex;gap:.75rem;align-items:flex-start;}
        .spk-adm-avatar{flex-shrink:0;width:52px;height:52px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:800;color:#fff;box-shadow:0 2px 8px rgba(139,92,246,.3);}
        .spk-adm-identity{flex:1;min-width:0;display:flex;flex-direction:column;gap:.15rem;}
        .spk-adm-name{font-size:.95rem;font-weight:700;color:#1e293b;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .spk-adm-sub{font-size:.75rem;color:#64748b;margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        .spk-adm-pills{display:flex;flex-wrap:wrap;gap:.3rem;}
        .spk-adm-pill{display:inline-flex;align-items:center;gap:.2rem;padding:.2rem .5rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:50px;font-size:.72rem;color:#374151;white-space:nowrap;max-width:210px;overflow:hidden;text-overflow:ellipsis;}
        .spk-adm-footer{display:flex;gap:.4rem;padding-top:.75rem;border-top:1px solid #f1f5f9;margin-top:auto;}
        .spk-adm-btn{display:inline-flex;align-items:center;gap:.25rem;padding:.35rem .75rem;border-radius:7px;font-size:.78rem;font-weight:600;text-decoration:none;white-space:nowrap;cursor:pointer;border:none;transition:opacity .15s;}
        .spk-adm-btn:hover{opacity:.82;}
        .spk-adm-btn-primary{background:#8b5cf6;color:#fff;}
        .spk-adm-btn-ghost{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;}
        .spk-adm-btn-danger{background:#fee2e2;color:#991b1b;}
        .spk-topic-tag{display:inline-flex;align-items:center;gap:4px;padding:.25rem .65rem;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:50px;font-size:.8rem;color:#7c3aed;font-weight:600;cursor:default;}
        /* 2-Spalten Formular-Grid */
        .spk-form-2col{display:grid;grid-template-columns:1fr 1fr;gap:0 1.5rem;align-items:start;}
        .spk-form-2col__left,.spk-form-2col__right{display:flex;flex-direction:column;gap:0;}
        @media(max-width:1100px){.spk-form-2col{grid-template-columns:1fr;}}
        </style>
        <?php
        renderAdminLayoutEnd();
    }

    // ─── FORM ────────────────────────────────────────────────
    public function render_form(?object $speaker, array $topics, array $events, array $companies): void
    {
        $menu_file = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menu_file) && !function_exists('renderAdminLayoutStart')) {
            require_once $menu_file;
        }
        $is_edit   = $speaker !== null;
        $title     = $is_edit ? '✏️ Speaker bearbeiten' : '🎤 Neuer Speaker';
        $csrf      = CMS\Security::instance()->generateToken('save_speaker');
        $csrf_evt  = CMS\Security::instance()->generateToken('speaker_event');
        $sec       = CMS\Security::instance();

        renderAdminLayoutStart($is_edit ? 'Speaker bearbeiten' : 'Neuer Speaker', 'speakers');

        // Decode JSON fields
        $formats   = is_string($speaker->formats  ?? null) ? (json_decode($speaker->formats,  true) ?? []) : [];
        $langs     = is_string($speaker->languages ?? null) ? (json_decode($speaker->languages, true) ?? []) : [];
        $langs_str = implode(', ', $langs);

        $all_formats = [
            'keynote'     => '🎤 Keynote',
            'workshop'    => '🛠️ Workshop',
            'panel'       => '💬 Podiumsdiskussion',
            'moderation'  => '🎙️ Moderation',
            'training'    => '📚 Training',
            'consulting'  => '🤝 Beratung',
            'interview'   => '🎥 Interview',
            'webinar'     => '💻 Webinar',
        ];
        $travel_options = [
            'local'         => '📍 Lokal (Umkreis 50 km)',
            'regional'      => '🗺️ Regional (Bundesland)',
            'national'      => '🇩🇪 National (DACH)',
            'international' => '🌍 International (Europa)',
            'worldwide'     => '🌐 Weltweit',
        ];
        $avail_options = [
            'available' => '✅ Verfügbar',
            'limited'   => '⚠️ Begrenzt verfügbar',
            'booked'    => '🔴 Ausgebucht',
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
                <a href="<?= SITE_URL ?>/speakers/<?= htmlspecialchars(CMS_Speakers_Database::generate_slug($speaker)) ?>" class="btn btn-secondary" target="_blank">🌐 Ansehen</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_GET['saved'])): ?><div class="alert alert-success">✅ Gespeichert.</div><?php endif; ?>
        <?php if (isset($_GET['error'])): ?><div class="alert alert-error">❌ Fehler: <?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

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
                    <button type="submit" class="btn btn-primary">💾 Speaker speichern</button>
                    <a href="<?= SITE_URL ?>/admin/speakers" class="btn btn-secondary">Abbrechen</a>
                    <?php if ($is_edit): ?><span class="form-actions__hint">Zuletzt gespeichert: <?= htmlspecialchars(date('d.m.Y H:i', strtotime($speaker->updated_at ?? 'now'))) ?></span><?php endif; ?>
                </div>
            </div>
        </form>

        <?php if ($is_edit): ?>
        <!-- Events Section (outside main form, own AJAX) -->
        <?php CMS_Speakers_Meta_Boxes::instance()->render_events($speaker->id, $events, $companies, $csrf_evt, $event_types); ?>
        <?php endif; ?>

        <?php
        renderAdminLayoutEnd();
    }
}
