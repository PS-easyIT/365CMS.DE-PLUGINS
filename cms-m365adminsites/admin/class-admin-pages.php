<?php
/**
 * CMS M365 Adminsites – Admin Pages.
 *
 * @package CMS_M365ADMINSITES
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365ADMINSITES_Admin_Pages
{
    private const PAGE_DASHBOARD = 'm365adminsites-dashboard';
    private const PAGE_CONTENT = 'm365adminsites-content';
    private const PAGE_SETTINGS = 'm365adminsites-settings';
    private const PAGE_HELP = 'm365adminsites-help';

    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public static function render_dispatcher(): void
    {
        self::check_access();
        self::load_admin_menu();

        $defaultSlug = self::PAGE_DASHBOARD;
        $callbackMap = [
            self::PAGE_DASHBOARD => [self::class, 'render_entries_screen'],
            self::PAGE_CONTENT => [self::class, 'render_content_screen'],
            self::PAGE_SETTINGS => [self::class, 'render_settings_screen'],
            self::PAGE_HELP => [self::class, 'render_help_screen'],
        ];

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, $defaultSlug, self::PAGE_DASHBOARD);
            return;
        }

        self::fallback_dispatch($callbackMap, $defaultSlug);
    }

    public static function render_dashboard(): void
    {
        self::render_dispatcher();
    }

    /**
     * @param array<string,callable|null> $callbackMap
     */
    private static function fallback_dispatch(array $callbackMap, string $defaultSlug): void
    {
        $requested = preg_replace('/[^a-z0-9_-]+/i', '', (string) ($_GET['page'] ?? $defaultSlug)) ?: $defaultSlug;
        $resolved = array_key_exists($requested, $callbackMap) ? $requested : $defaultSlug;
        $callback = $callbackMap[$resolved] ?? null;

        if (!is_callable($callback)) {
            self::render_with_layout('M365 Adminsites', self::PAGE_DASHBOARD, static function (): void {
                echo '<div class="alert alert-error">Die angeforderte Admin-Seite ist derzeit nicht verfügbar.</div>';
            });
            error_log(
                sprintf(
                    'CMS M365 Adminsites fallback dispatch failed requested=%s resolved=%s',
                    $requested,
                    $resolved
                )
            );
            return;
        }

        call_user_func($callback);
    }

    private static function render_with_layout(string $title, string $slug, callable $renderer): void
    {
        self::enqueue_admin_assets();

        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, $slug);
            echo '<div class="admin-content mas-admin-shell">';
            $renderer();
            echo '</div>';
            cms_plugin_admin_layout_end();
            return;
        }

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $slug);
        }
        echo '<div class="admin-content mas-admin-shell">';
        $renderer();
        echo '</div>';
        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function check_access(): void
    {
        $isAdmin = class_exists('CMS\\Auth') && \CMS\Auth::instance()->isAdmin();
        if (!$isAdmin || !self::has_manage_capability()) {
            header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/'));
            exit;
        }
    }

    private static function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (!file_exists($menuFile) || function_exists('renderAdminLayoutStart')) {
            return;
        }

        $resolved = realpath($menuFile);
        $allowedBase = realpath(ABSPATH . 'admin/partials');
        if (!is_string($resolved) || !is_string($allowedBase)) {
            return;
        }

        $allowedPrefix = rtrim($allowedBase, '\\/') . DIRECTORY_SEPARATOR;
        if (!str_starts_with($resolved, $allowedPrefix)) {
            error_log('CMS M365 Adminsites skipped admin menu include: ' . $resolved);
            return;
        }

        require_once $resolved;
    }

    private static function enqueue_admin_assets(): void
    {
        $css = CMS_M365ADMINSITES_PLUGIN_DIR . 'assets/css/m365adminsites-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365ADMINSITES_PLUGIN_URL . 'assets/css/m365adminsites-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public static function render_entries_screen(): void
    {
        self::render_with_layout('M365 Adminsites', self::PAGE_DASHBOARD, static function (): void {
            self::instance()->render_entries_page();
        });
    }

    public static function render_content_screen(): void
    {
        self::render_with_layout('Inhalte & Texte', self::PAGE_CONTENT, static function (): void {
            self::instance()->render_content_page();
        });
    }

    public static function render_settings_screen(): void
    {
        self::render_with_layout('Anzeige & Design', self::PAGE_SETTINGS, static function (): void {
            self::instance()->render_settings_page();
        });
    }

    public static function render_help_screen(): void
    {
        self::render_with_layout('Hinweise', self::PAGE_HELP, static function (): void {
            self::instance()->render_help_page();
        });
    }

    private function render_entries_page(): void
    {
        CMS_M365ADMINSITES_Installer::maybe_install();
        $repo = CMS_M365ADMINSITES_Repository::instance();
        $notice = '';
        $error = '';

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            [$notice, $error] = $this->handle_post();
        }

        $categories = $repo->categories(false);
        $editId = max(0, (int) ($_GET['edit'] ?? 0));
        $editItem = $editId > 0 ? $repo->find($editId) : null;
        $payload = $repo->sites([], 500, 0);
        $items = $payload['items'];
        $publicUrl = rtrim((string) (defined('SITE_URL') ? SITE_URL : ''), '/') . CMS_M365ADMINSITES_Settings::route();
        ?>
        <div class="admin-page-header">
            <div>
                <h2>🧭 M365 Adminsites</h2>
                <p>Microsoft-Portale zentral pflegen, filtern und im Public-Bereich anzeigen.</p>
            </div>
            <div class="header-actions">
                <a href="<?php echo self::esc_attr($publicUrl); ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">👁️ Public öffnen</a>
                <a href="<?php echo self::esc_attr(self::admin_page_url(self::PAGE_DASHBOARD)); ?>" class="btn btn-primary">➕ Portal anlegen</a>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo self::esc($notice); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo self::esc($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid mas-stats">
            <div class="stat-card"><div class="stat-icon">🧭</div><div class="stat-number"><?php echo (int) count($items); ?></div><div class="stat-label">Portale</div></div>
            <div class="stat-card"><div class="stat-icon">🏷️</div><div class="stat-number"><?php echo (int) count($categories); ?></div><div class="stat-label">Kategorien</div></div>
            <div class="stat-card"><div class="stat-icon">⭐</div><div class="stat-number"><?php echo (int) count(array_filter($items, static fn(array $item): bool => !empty($item['is_featured']))); ?></div><div class="stat-label">Widget-Pool</div></div>
        </div>

        <?php $this->render_entries($items, $categories, $editItem); ?>
        <?php
    }

    /**
     * @return array{0:string,1:string}
     */
    private function handle_post(): array
    {
        if (!self::has_manage_capability()) {
            return ['', 'Keine Berechtigung für diese Aktion.'];
        }

        $action = self::clean_action((string) ($_POST['action'] ?? ''));
        if ($action === '') {
            return ['', 'Ungültige Aktion.'];
        }

        $tokenAction = $action === 'save_settings' ? 'm365adminsites_settings' : 'm365adminsites_entries';
        if (!self::verify_nonce($tokenAction)) {
            return ['', 'Sicherheitscheck fehlgeschlagen.'];
        }

        try {
            if ($action === 'save_site') {
                $savedId = CMS_M365ADMINSITES_Repository::instance()->save($_POST);
                if ($savedId <= 0) {
                    return ['', 'Portal konnte nicht gespeichert werden.'];
                }

                return ['Portal gespeichert.', ''];
            }

            if ($action === 'delete_site') {
                $deleteId = max(0, (int) ($_POST['id'] ?? 0));
                if ($deleteId <= 0) {
                    return ['', 'Ungültige Portal-ID.'];
                }

                CMS_M365ADMINSITES_Repository::instance()->delete($deleteId);
                return ['Portal gelöscht.', ''];
            }

            if ($action === 'save_settings') {
                CMS_M365ADMINSITES_Settings::save(self::sanitize_settings($_POST));
                return ['Einstellungen gespeichert.', ''];
            }
        } catch (\Throwable $e) {
            error_log('CMS M365 Adminsites admin action failed (' . $action . '): ' . $e->getMessage());
            return ['', 'Aktion konnte nicht ausgeführt werden. Bitte Logs prüfen.'];
        }

        return ['', 'Unbekannte Aktion.'];
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @param array<int,array<string,mixed>> $categories
     * @param array<string,mixed>|null $editItem
     */
    private function render_entries(array $items, array $categories, ?array $editItem): void
    {
        $csrfToken = self::generate_nonce('m365adminsites_entries');
        $item = $editItem ?? [
            'id' => 0,
            'category_id' => (int) ($categories[0]['id'] ?? 0),
            'title' => '',
            'subtitle' => '',
            'url' => '',
            'image_url' => '',
            'image_alt' => '',
            'tags' => '',
            'status' => 'active',
            'is_featured' => 0,
            'sort_order' => 0,
        ];
        ?>
        <div class="admin-card mas-editor-card">
            <h3><?php echo ((int) ($item['id'] ?? 0) > 0) ? '✏️ Portal bearbeiten' : '➕ Neues Portal'; ?></h3>
            <form method="POST" class="admin-form mas-entry-form">
                <input type="hidden" name="action" value="save_site">
                <input type="hidden" name="csrf_token" value="<?php echo self::esc_attr($csrfToken); ?>">
                <input type="hidden" name="id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                <div class="mas-form-grid">
                    <label>Kategorie
                        <select name="category_id" class="form-control" required>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int) $category['id']; ?>"<?php echo (int) ($item['category_id'] ?? 0) === (int) $category['id'] ? ' selected' : ''; ?>><?php echo self::esc((string) $category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Titel
                        <input type="text" name="title" class="form-control" value="<?php echo self::esc_attr((string) ($item['title'] ?? '')); ?>" required maxlength="190">
                    </label>
                    <label>Untertitel / Bereich
                        <input type="text" name="subtitle" class="form-control" value="<?php echo self::esc_attr((string) ($item['subtitle'] ?? '')); ?>" maxlength="190">
                    </label>
                    <label>URL
                        <input type="url" name="url" class="form-control" value="<?php echo self::esc_attr((string) ($item['url'] ?? '')); ?>" required maxlength="500">
                    </label>
                    <label>Bild-URL
                        <input type="url" name="image_url" class="form-control" value="<?php echo self::esc_attr((string) ($item['image_url'] ?? '')); ?>" maxlength="500">
                    </label>
                    <label>Bild-Alt-Text
                        <input type="text" name="image_alt" class="form-control" value="<?php echo self::esc_attr((string) ($item['image_alt'] ?? '')); ?>" maxlength="190">
                    </label>
                    <label>Status
                        <select name="status" class="form-control">
                            <option value="active"<?php echo (string) ($item['status'] ?? 'active') === 'active' ? ' selected' : ''; ?>>Aktiv</option>
                            <option value="inactive"<?php echo (string) ($item['status'] ?? '') === 'inactive' ? ' selected' : ''; ?>>Inaktiv</option>
                        </select>
                    </label>
                    <label>Sortierung
                        <input type="number" name="sort_order" class="form-control" value="<?php echo (int) ($item['sort_order'] ?? 0); ?>" min="0" step="1">
                    </label>
                </div>
                <label>Tags
                    <input type="text" name="tags" class="form-control" value="<?php echo self::esc_attr((string) ($item['tags'] ?? '')); ?>" maxlength="500" placeholder="z. B. Entra, Security, Admin, Portal">
                </label>
                <div class="mas-check-row">
                    <label><input type="checkbox" name="is_featured" value="1"<?php echo !empty($item['is_featured']) ? ' checked' : ''; ?>> Im PHINIT-Widget rotieren</label>
                </div>
                <button type="submit" class="btn btn-primary">💾 Portal speichern</button>
                <?php if ((int) ($item['id'] ?? 0) > 0): ?>
                <a href="<?php echo self::esc_attr(self::admin_page_url(self::PAGE_DASHBOARD)); ?>" class="btn btn-secondary">Neu anlegen</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-card">
            <h3>📋 Gepflegte Portale</h3>
            <?php if ($items === []): ?>
            <div class="empty-state"><p><strong>Noch keine Portale vorhanden</strong></p><p>Beim Aktivieren werden Startdaten automatisch importiert.</p></div>
            <?php else: ?>
            <div class="users-table-container">
                <table class="users-table">
                    <thead><tr><th>Titel</th><th>Kategorie</th><th>URL</th><th>Status</th><th>Widget</th><th>Aktionen</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $row): ?>
                    <?php $externalUrl = self::safe_external_url((string) ($row['url'] ?? '')); ?>
                    <?php $rowStatus = (string) ($row['status'] ?? '') === 'active' ? 'active' : 'inactive'; ?>
                    <tr>
                        <td><a href="<?php echo self::esc_attr(self::admin_page_url(self::PAGE_DASHBOARD, ['edit' => (string) ((int) $row['id'])])); ?>" class="mas-table-title"><?php echo self::esc((string) $row['title']); ?></a><br><small><?php echo self::esc((string) ($row['subtitle'] ?? '')); ?></small></td>
                        <td><?php echo self::esc((string) ($row['category_name'] ?? '')); ?></td>
                        <td>
                            <?php if ($externalUrl !== ''): ?>
                            <a href="<?php echo self::esc_attr($externalUrl); ?>" target="_blank" rel="noopener noreferrer">öffnen</a>
                            <?php else: ?>
                            —
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge <?php echo $rowStatus; ?>"><?php echo $rowStatus === 'active' ? 'Aktiv' : 'Inaktiv'; ?></span></td>
                        <td><?php echo !empty($row['is_featured']) ? '⭐' : '—'; ?></td>
                        <td><div class="mas-action-row"><a href="<?php echo self::esc_attr(self::admin_page_url(self::PAGE_DASHBOARD, ['edit' => (string) ((int) $row['id'])])); ?>" class="btn btn-sm btn-secondary">✏️</a><button type="button" class="btn btn-sm btn-danger" onclick="openMasDeleteModal(<?php echo (int) $row['id']; ?>, <?php echo self::json((string) $row['title']); ?>)">🗑️</button></div></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div id="masDeleteModal" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header"><h3>🗑️ Portal löschen</h3><button class="modal-close" onclick="closeMasModal()" type="button">&times;</button></div>
                <div class="modal-body"><p>Soll <strong id="masDeleteName"></strong> wirklich gelöscht werden?</p><p class="mas-danger-note">Diese Aktion kann nicht rückgängig gemacht werden.</p></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeMasModal()">Abbrechen</button>
                    <form method="POST" id="masDeleteForm">
                        <input type="hidden" name="action" value="delete_site">
                        <input type="hidden" name="csrf_token" value="<?php echo self::esc_attr($csrfToken); ?>">
                        <input type="hidden" name="id" id="masDeleteId">
                        <button type="submit" class="btn btn-danger">Endgültig löschen</button>
                    </form>
                </div>
            </div>
        </div>
        <script>
        function openMasDeleteModal(id, name) {
            document.getElementById('masDeleteId').value = String(id);
            document.getElementById('masDeleteName').textContent = name;
            document.getElementById('masDeleteModal').style.display = 'block';
        }
        function closeMasModal() {
            document.getElementById('masDeleteModal').style.display = 'none';
        }
        </script>
        <?php
    }

    /** @param array<string,string> $settings */
    private function render_content_page(): void
    {
        CMS_M365ADMINSITES_Installer::maybe_install();
        $settings = CMS_M365ADMINSITES_Settings::all();
        $notice = '';
        $error = '';
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            [$notice, $error] = $this->handle_post();
        }
        $csrfToken = self::generate_nonce('m365adminsites_settings');
        ?>
        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo self::esc($notice); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo self::esc($error); ?></div>
        <?php endif; ?>
        <div class="admin-card mas-editor-card">
            <h3>✍️ Öffentliche Texte bearbeiten</h3>
            <p class="mas-admin-hint">Diese Inhalte erscheinen auf der Adminsites-Seite und im PHINIT-Sidebar-Widget.</p>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="settings_section" value="content">
                <input type="hidden" name="csrf_token" value="<?php echo self::esc_attr($csrfToken); ?>">

                <h4 class="mas-section-title">Seitenkopf</h4>
                <div class="mas-form-grid">
                    <?php self::input('page_route', 'Public Route', $settings); ?>
                    <?php self::bilingual_input('page_overline', 'Header-Overline', $settings); ?>
                    <?php self::bilingual_input('page_title', 'Seitentitel', $settings); ?>
                </div>
                <?php self::bilingual_textarea('page_intro', 'Introtext', $settings, 3); ?>

                <h4 class="mas-section-title">Filter, Ansichten und Leerzustand</h4>
                <div class="mas-form-grid">
                    <?php foreach ([
                        'label_all_categories' => 'Kategorie: Alle',
                        'label_filter_nav' => 'ARIA: Filterbereich',
                        'label_category_nav' => 'ARIA: Kategorien',
                        'label_search' => 'Suchfeld Label',
                        'label_search_placeholder' => 'Suchfeld Platzhalter',
                        'label_search_button' => 'Suchbutton',
                        'label_reset_button' => 'Reset-Link',
                        'label_empty_title' => 'Leerzustand Titel',
                    ] as $key => $label): ?>
                    <?php self::bilingual_input($key, $label, $settings); ?>
                    <?php endforeach; ?>
                </div>
                <?php self::bilingual_textarea('label_empty_body', 'Leerzustand Text', $settings, 2); ?>

                <h4 class="mas-section-title">Überschriften, Tabelle und Pagination</h4>
                <div class="mas-form-grid">
                    <?php foreach ([
                        'label_cards_heading' => 'Cards-Überschrift',
                        'label_table_heading' => 'Tabellen-Überschrift',
                        'label_table_image' => 'Spalte: Bild',
                        'label_table_title' => 'Spalte: Portal',
                        'label_table_subtitle' => 'Spalte: Bereich',
                        'label_table_url' => 'Spalte: URL',
                        'label_table_actions' => 'Spalte: Aktionen',
                        'label_pagination_page' => 'Pagination: Seite',
                        'label_pagination_of' => 'Pagination: von',
                        'label_pagination_nav' => 'ARIA: Seitennavigation',
                        'label_pagination_prev' => 'Pagination: Zurück',
                        'label_pagination_next' => 'Pagination: Weiter',
                    ] as $key => $label): ?>
                    <?php self::bilingual_input($key, $label, $settings); ?>
                    <?php endforeach; ?>
                </div>

                <h4 class="mas-section-title">Buttons und Sidebar-Widget</h4>
                <div class="mas-form-grid">
                    <?php foreach ([
                        'external_button_label' => 'Externer Button',
                        'sidebar_title' => 'Sidebar Widget Titel',
                        'sidebar_button_label' => 'Sidebar Button',
                        'sidebar_controls_label' => 'Sidebar Steuerung Label',
                        'sidebar_prev_label' => 'Sidebar Zurück ARIA',
                        'sidebar_next_label' => 'Sidebar Weiter ARIA',
                    ] as $key => $label): ?>
                    <?php self::bilingual_input($key, $label, $settings); ?>
                    <?php endforeach; ?>
                </div>

                <h4 class="mas-section-title">Feature: Conditional Access What-If Shortcuts</h4>
                <div class="mas-form-grid">
                    <?php foreach ([
                        'feature_ca_shortcuts_title' => 'Bereichstitel',
                        'feature_ca_shortcuts_intro' => 'Einleitung',
                        'feature_ca_shortcuts_primary_label' => 'Primärer Button',
                        'feature_ca_shortcuts_identity_label' => 'Preset Identität (Titel)',
                        'feature_ca_shortcuts_identity_hint' => 'Preset Identität (Hinweis)',
                        'feature_ca_shortcuts_app_label' => 'Preset Cloud-App (Titel)',
                        'feature_ca_shortcuts_app_hint' => 'Preset Cloud-App (Hinweis)',
                        'feature_ca_shortcuts_platform_label' => 'Preset Plattform (Titel)',
                        'feature_ca_shortcuts_platform_hint' => 'Preset Plattform (Hinweis)',
                    ] as $key => $label): ?>
                    <?php self::bilingual_input($key, $label, $settings); ?>
                    <?php endforeach; ?>
                </div>
                <div class="mas-check-row">
                    <label><input type="checkbox" name="feature_ca_shortcuts_enabled" value="1"<?php echo !empty($settings['feature_ca_shortcuts_enabled']) && $settings['feature_ca_shortcuts_enabled'] !== '0' ? ' checked' : ''; ?>> What-If-Shortcuts anzeigen</label>
                </div>

                <h4 class="mas-section-title">Feature: Message Center Highlights</h4>
                <div class="mas-form-grid">
                    <?php foreach ([
                        'feature_message_center_title' => 'Bereichstitel',
                        'feature_message_center_intro' => 'Einleitung',
                        'feature_message_center_filter_label' => 'Filter Label',
                        'feature_message_center_severity_label' => 'Filter Priorität',
                        'feature_message_center_workload_label' => 'Filter Workload',
                        'feature_message_center_filter_all' => 'Filter: Alle',
                        'feature_message_center_open_label' => 'Linktext',
                        'feature_message_center_empty' => 'Leerzustand',
                    ] as $key => $label): ?>
                    <?php self::bilingual_input($key, $label, $settings); ?>
                    <?php endforeach; ?>
                    <?php self::number('feature_message_center_max_items', 'Max. Highlights', $settings, 1, 12, 1); ?>
                </div>
                <?php self::textarea('feature_message_center_items', 'Highlights (Format: severity|workload|title|url, je Zeile)', $settings, 6); ?>
                <div class="mas-check-row">
                    <label><input type="checkbox" name="feature_message_center_enabled" value="1"<?php echo !empty($settings['feature_message_center_enabled']) && $settings['feature_message_center_enabled'] !== '0' ? ' checked' : ''; ?>> Message-Center-Highlights anzeigen</label>
                </div>

                <button type="submit" class="btn btn-primary">💾 Texte speichern</button>
            </form>
        </div>
        <?php
    }

    /** @param array<string,string> $settings */
    private function render_settings_page(): void
    {
        CMS_M365ADMINSITES_Installer::maybe_install();
        $settings = CMS_M365ADMINSITES_Settings::all();
        $notice = '';
        $error = '';
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            [$notice, $error] = $this->handle_post();
        }
        $csrfToken = self::generate_nonce('m365adminsites_settings');
        $columns = ['image' => 'Bild', 'title' => 'Portal', 'subtitle' => 'Bereich', 'url' => 'URL', 'actions' => 'Buttons'];
        $visible = array_filter(array_map('trim', explode(',', (string) ($settings['visible_columns'] ?? ''))));
        ?>
        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo self::esc($notice); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo self::esc($error); ?></div>
        <?php endif; ?>
        <div class="admin-card mas-editor-card">
            <h3>🎨 Anzeige, Design & Widget-Verhalten</h3>
            <p class="mas-admin-hint">Hier steuerst du Layout, Farben, Tabellenoptionen und die Sidebar-Rotation.</p>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="settings_section" value="design">
                <input type="hidden" name="csrf_token" value="<?php echo self::esc_attr($csrfToken); ?>">
                <h4 class="mas-section-title">Anzeige</h4>
                <div class="mas-form-grid">
                    <?php self::select('default_view', 'Standardansicht', $settings, ['cards' => 'Cards', 'table' => 'Tabelle', 'both' => 'Cards + Tabelle']); ?>
                    <?php self::select('table_density', 'Tabellendichte', $settings, ['comfortable' => 'Komfortabel', 'compact' => 'Kompakt']); ?>
                    <?php self::number('items_per_page', 'Einträge pro Seite', $settings, 12, 500, 1); ?>
                    <?php self::number('image_height', 'Tabellenbild Höhe (px)', $settings, 48, 240, 1); ?>
                    <?php self::number('card_image_height', 'Card-Bild Höhe (px)', $settings, 64, 260, 1); ?>
                    <?php self::number('content_spacing_top', 'Abstand Theme-Header (px)', $settings, 0, 160, 1); ?>
                    <?php self::number('content_spacing_bottom', 'Abstand Theme-Footer (px)', $settings, 0, 200, 1); ?>
                    <?php self::number('content_padding_y', 'Plugin Innenabstand oben/unten (px)', $settings, 0, 80, 1); ?>
                    <?php self::number('content_padding_x', 'Plugin Innenabstand links/rechts (px)', $settings, 0, 80, 1); ?>
                    <?php self::number('section_gap', 'Abstand zwischen Bereichen (px)', $settings, 0, 80, 1); ?>
                    <?php self::number('border_radius', 'Radius (px)', $settings, 0, 24, 1); ?>
                </div>
                <h4 class="mas-section-title">Farben</h4>
                <div class="mas-form-grid">
                    <?php foreach (['color_page_background' => 'Seitenhintergrund', 'color_surface' => 'Kartenfläche', 'color_text' => 'Text', 'color_muted' => 'Sekundärtext', 'color_border' => 'Rahmen', 'color_accent' => 'Akzent', 'color_button_bg' => 'Button Hintergrund', 'color_button_text' => 'Button Text'] as $key => $label): ?>
                    <?php self::input($key, $label, $settings, 'color'); ?>
                    <?php endforeach; ?>
                </div>
                <h4 class="mas-section-title">Sidebar-Widget</h4>
                <div class="mas-form-grid">
                    <?php self::select('sidebar_style', 'Widget-Aussehen', $settings, ['card' => 'Card mit Bild', 'compact' => 'Kompakt', 'minimal' => 'Minimal']); ?>
                    <?php self::number('sidebar_limit', 'Sidebar Portale', $settings, 1, 20, 1); ?>
                    <?php self::number('sidebar_rotate_seconds', 'Sidebar Wechsel (Sek.)', $settings, 3, 60, 1); ?>
                    <?php self::number('sidebar_min_height', 'Widget Mindesthöhe (px)', $settings, 120, 520, 1); ?>
                    <?php self::number('sidebar_image_height', 'Widget Bildhöhe (px)', $settings, 0, 320, 1); ?>
                    <?php self::input('sidebar_placeholder_image', 'Sidebar Platzhalter-Bild', $settings); ?>
                </div>
                <div class="mas-check-row">
                    <?php foreach (['page_enabled' => 'Public-Seite aktiv', 'show_category_nav' => 'Kategorienavigation', 'show_cards' => 'Cards anzeigen', 'show_table' => 'Tabelle anzeigen', 'show_images' => 'Bilder anzeigen', 'sidebar_enabled' => 'PHINIT-Sidebar-Widget aktiv', 'sidebar_show_image' => 'Bild im Widget', 'sidebar_show_category' => 'Kategorie im Widget', 'sidebar_show_subtitle' => 'Untertitel im Widget'] as $key => $label): ?>
                    <label><input type="checkbox" name="<?php echo self::esc_attr($key); ?>" value="1"<?php echo !empty($settings[$key]) && $settings[$key] !== '0' ? ' checked' : ''; ?>> <?php echo self::esc($label); ?></label>
                    <?php endforeach; ?>
                </div>
                <fieldset class="mas-fieldset">
                    <legend>Tabellenspalten</legend>
                    <div class="mas-check-row">
                        <?php foreach ($columns as $key => $label): ?>
                        <label><input type="checkbox" name="visible_columns[]" value="<?php echo self::esc_attr($key); ?>"<?php echo in_array($key, $visible, true) ? ' checked' : ''; ?>> <?php echo self::esc($label); ?></label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            </form>
        </div>
        <?php
    }

    private function render_help_page(): void
    {
        ?>
        <div class="admin-card">
            <h3>ℹ️ Hinweise zur Integration</h3>
            <ul class="mas-help-list">
                <li>Die Public-Seite ist standardmäßig unter <code>/m365-adminsites</code> erreichbar.</li>
                <li>Startdaten basieren auf deiner Portal-Liste, den PHINIT-KB-Seiten und `msportals.io` als zusätzlicher Portalquelle.</li>
                <li>Consumer-Web-Apps sind bewusst enthalten, damit DLP-, Browser-, Tenant-Restrictions- und Schulungskonzepte sichtbar bleiben.</li>
                <li>Das PHINIT-Sidebar-Widget nutzt bevorzugt Portale mit aktivem Widget-Stern.</li>
                <li>Es werden keine Public-Ausgaben in Company-, Expert- oder Speaker-Plugins injiziert.</li>
            </ul>
        </div>
        <?php
    }

    /** @param array<string,string> $settings */
    private static function input(string $key, string $label, array $settings, string $type = 'text'): void
    {
        echo '<label>' . self::esc($label) . '<input type="' . self::esc_attr($type) . '" name="' . self::esc_attr($key) . '" class="form-control" value="' . self::esc_attr((string) ($settings[$key] ?? '')) . '"></label>';
    }

    /** @param array<string,string> $settings */
    private static function textarea(string $key, string $label, array $settings, int $rows = 3): void
    {
        echo '<label>' . self::esc($label) . '<textarea name="' . self::esc_attr($key) . '" class="form-control" rows="' . max(1, $rows) . '">' . self::esc((string) ($settings[$key] ?? '')) . '</textarea></label>';
    }

    /** @param array<string,string> $settings */
    private static function bilingual_input(string $key, string $label, array $settings): void
    {
        self::input($key, $label . ' (DE)', $settings);
        self::input($key . '_en', $label . ' (EN)', $settings);
    }

    /** @param array<string,string> $settings */
    private static function bilingual_textarea(string $key, string $label, array $settings, int $rows = 3): void
    {
        self::textarea($key, $label . ' (DE)', $settings, $rows);
        self::textarea($key . '_en', $label . ' (EN)', $settings, $rows);
    }

    /** @param array<string,string> $settings */
    private static function number(string $key, string $label, array $settings, int $min, int $max, int $step): void
    {
        echo '<label>' . self::esc($label) . '<input type="number" name="' . self::esc_attr($key) . '" class="form-control" value="' . (int) ($settings[$key] ?? 0) . '" min="' . $min . '" max="' . $max . '" step="' . $step . '"></label>';
    }

    /** @param array<string,string> $settings @param array<string,string> $options */
    private static function select(string $key, string $label, array $settings, array $options): void
    {
        echo '<label>' . self::esc($label) . '<select name="' . self::esc_attr($key) . '" class="form-control">';
        foreach ($options as $value => $optionLabel) {
            $selected = (string) ($settings[$key] ?? '') === $value ? ' selected' : '';
            echo '<option value="' . self::esc_attr($value) . '"' . $selected . '>' . self::esc($optionLabel) . '</option>';
        }
        echo '</select></label>';
    }

    /** @param array<string,mixed> $post */
    private static function sanitize_settings(array $post): array
    {
        $defaults = CMS_M365ADMINSITES_Settings::defaults();
        $settings = [];
        $section = (string) ($post['settings_section'] ?? 'all');
        $booleanKeys = [
            'page_enabled',
            'show_category_nav',
            'show_cards',
            'show_table',
            'show_images',
            'sidebar_enabled',
            'sidebar_show_image',
            'sidebar_show_category',
            'sidebar_show_subtitle',
            'feature_ca_shortcuts_enabled',
            'feature_message_center_enabled',
        ];
        $contentBooleanKeys = [
            'feature_ca_shortcuts_enabled',
            'feature_message_center_enabled',
        ];
        $intRanges = [
            'items_per_page' => [12, 500],
            'image_height' => [48, 240],
            'card_image_height' => [64, 260],
            'content_spacing_top' => [0, 160],
            'content_spacing_bottom' => [0, 200],
            'content_padding_y' => [0, 80],
            'content_padding_x' => [0, 80],
            'section_gap' => [0, 80],
            'border_radius' => [0, 24],
            'sidebar_limit' => [1, 20],
            'sidebar_rotate_seconds' => [3, 60],
            'sidebar_min_height' => [120, 520],
            'sidebar_image_height' => [0, 320],
            'feature_message_center_max_items' => [1, 12],
        ];
        $enumOptions = [
            'default_view' => ['cards', 'table', 'both'],
            'table_density' => ['comfortable', 'compact'],
            'sidebar_style' => ['card', 'compact', 'minimal'],
        ];
        $colorKeys = [
            'color_page_background',
            'color_surface',
            'color_text',
            'color_muted',
            'color_border',
            'color_accent',
            'color_button_bg',
            'color_button_text',
        ];
        $designKeys = [
            'default_view',
            'table_density',
            'items_per_page',
            'image_height',
            'card_image_height',
            'content_spacing_top',
            'content_spacing_bottom',
            'content_padding_y',
            'content_padding_x',
            'section_gap',
            'border_radius',
            'color_page_background',
            'color_surface',
            'color_text',
            'color_muted',
            'color_border',
            'color_accent',
            'color_button_bg',
            'color_button_text',
            'sidebar_limit',
            'sidebar_rotate_seconds',
            'sidebar_min_height',
            'sidebar_image_height',
            'sidebar_style',
            'sidebar_placeholder_image',
            'visible_columns',
            'page_enabled',
            'show_category_nav',
            'show_cards',
            'show_table',
            'show_images',
            'sidebar_enabled',
            'sidebar_show_image',
            'sidebar_show_category',
            'sidebar_show_subtitle',
        ];
        foreach ($defaults as $key => $default) {
            if ($section === 'content' && !array_key_exists($key, $post) && !in_array($key, $contentBooleanKeys, true)) {
                continue;
            }
            if ($section === 'design' && !in_array($key, $designKeys, true)) {
                continue;
            }
            if (in_array($key, $booleanKeys, true)) {
                $settings[$key] = !empty($post[$key]) ? '1' : '0';
                continue;
            }
            if (array_key_exists($key, $intRanges)) {
                [$min, $max] = $intRanges[$key];
                $settings[$key] = (string) max((int) $min, min((int) $max, (int) ($post[$key] ?? $default)));
                continue;
            }
            if (array_key_exists($key, $enumOptions)) {
                $candidate = trim((string) ($post[$key] ?? $default));
                $settings[$key] = in_array($candidate, $enumOptions[$key], true) ? $candidate : (string) $default;
                continue;
            }
            if (in_array($key, $colorKeys, true)) {
                $candidate = strtolower(trim((string) ($post[$key] ?? $default)));
                $settings[$key] = preg_match('/^#[0-9a-f]{6}$/', $candidate) === 1 ? $candidate : (string) $default;
                continue;
            }
            if ($key === 'visible_columns') {
                $columns = array_values(array_intersect((array) ($post['visible_columns'] ?? []), ['image', 'title', 'subtitle', 'url', 'actions']));
                $settings[$key] = implode(',', $columns !== [] ? $columns : explode(',', $default));
                continue;
            }
            if ($key === 'page_route') {
                $settings[$key] = self::sanitize_route((string) ($post[$key] ?? $default), (string) $default);
                continue;
            }
            if ($key === 'sidebar_placeholder_image') {
                $settings[$key] = self::sanitize_media_url((string) ($post[$key] ?? $default));
                continue;
            }
            if ($key === 'feature_message_center_items') {
                $settings[$key] = self::sanitize_multiline_text((string) ($post[$key] ?? $default), 5000);
                continue;
            }

            $settings[$key] = self::sanitize_text((string) ($post[$key] ?? $default), 5000);
        }
        return $settings;
    }

    /**
     * @param array<string,string|null> $params
     */
    private static function admin_page_url(string $slug, array $params = []): string
    {
        $query = ['page' => preg_replace('/[^a-z0-9_-]+/i', '', $slug) ?: self::PAGE_DASHBOARD];
        foreach ($params as $key => $value) {
            $safeKey = preg_replace('/[^a-z0-9_-]+/i', '', (string) $key);
            if ($safeKey === '') {
                continue;
            }
            $query[$safeKey] = (string) ($value ?? '');
        }

        return '?' . http_build_query($query);
    }

    private static function generate_nonce(string $action): string
    {
        if (!class_exists('CMS\\Security')) {
            error_log('CMS M365 Adminsites admin security service missing for action: ' . $action);

            return '';
        }
        return (string) \CMS\Security::instance()->generateToken($action);
    }

    private static function verify_nonce(string $action): bool
    {
        if (!class_exists('CMS\\Security')) {
            error_log('CMS M365 Adminsites admin security service missing for action: ' . $action);

            return false;
        }
        return \CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), $action);
    }

    private static function json(string $value): string
    {
        $encoded = json_encode($value, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP);

        return is_string($encoded) ? $encoded : '""';
    }

    private static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function esc_attr(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private static function safe_external_url(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }
        return preg_match('#^https?://#i', $url) === 1 ? $url : '';
    }

    private static function has_manage_capability(): bool
    {
        if (function_exists('current_user_can')) {
            return (bool) current_user_can('manage_options');
        }

        if (class_exists('CMS\\Auth')) {
            $auth = \CMS\Auth::instance();
            foreach (['hasCapability', 'can', 'hasPermission'] as $method) {
                if (method_exists($auth, $method)) {
                    try {
                        return (bool) $auth->{$method}('manage_options');
                    } catch (\Throwable $e) {
                        error_log('CMS M365 Adminsites capability check failed: ' . $e->getMessage());
                        return false;
                    }
                }
            }

            if (method_exists($auth, 'isAdmin')) {
                return (bool) $auth->isAdmin();
            }
        }

        return false;
    }

    private static function clean_action(string $action): string
    {
        $action = preg_replace('/[^a-z_]+/i', '', strtolower(trim($action))) ?: '';
        return in_array($action, ['save_site', 'delete_site', 'save_settings'], true) ? $action : '';
    }

    private static function sanitize_text(string $value, int $maxLength): string
    {
        $value = strip_tags($value);
        $value = preg_replace('/\s+/u', ' ', trim($value)) ?? '';
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength);
        }

        return substr($value, 0, $maxLength);
    }

    private static function sanitize_multiline_text(string $value, int $maxLength): string
    {
        $value = strip_tags(str_replace(["\r\n", "\r"], "\n", $value));
        $lines = [];
        foreach (explode("\n", $value) as $line) {
            $line = trim((string) preg_replace('/\s+/u', ' ', $line));
            if ($line !== '') {
                $lines[] = $line;
            }
        }
        $normalized = implode("\n", $lines);
        if (function_exists('mb_substr')) {
            return mb_substr($normalized, 0, $maxLength);
        }

        return substr($normalized, 0, $maxLength);
    }

    private static function sanitize_route(string $value, string $fallback): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return $fallback;
        }

        $normalized = '/' . trim($trimmed, '/');
        if ($normalized === '/') {
            return $fallback;
        }

        if (preg_match('#^/[a-z0-9/_-]+$#i', $normalized) !== 1) {
            return $fallback;
        }

        return $normalized;
    }

    private static function sanitize_media_url(string $url): string
    {
        $url = trim((string) filter_var($url, FILTER_SANITIZE_URL));
        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        return preg_match('#^https?://#i', $url) === 1 ? $url : '';
    }
}
