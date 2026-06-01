<?php
/**
 * CMS M365 Linkcollection – Admin Pages.
 *
 * @package CMS_M365LINKCOLLECTION
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LINKCOLLECTION_Admin_Pages
{
    public const PLUGIN_SLUG = 'cms-m365linkcollection';
    public const SLUG_DASHBOARD = 'cms-m365linkcollection-dashboard';
    public const SLUG_ENTRIES = 'cms-m365linkcollection-entries';
    public const SLUG_CONTENT = 'cms-m365linkcollection-content';
    public const SLUG_SETTINGS = 'cms-m365linkcollection-settings';
    public const SLUG_HELP = 'cms-m365linkcollection-help';

    private static ?self $instance = null;
    private static bool $contractLoaded = false;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public static function render_dispatch(): void
    {
        self::check_access();
        self::load_admin_contract();

        if (function_exists('cms_plugin_admin_sync_page_from_request')) {
            cms_plugin_admin_sync_page_from_request(self::PLUGIN_SLUG, ['m365linkcollection-dashboard']);
        }

        $callbackMap = self::admin_callback_map();

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, self::SLUG_DASHBOARD, self::PLUGIN_SLUG);
            return;
        }

        self::render_dashboard();
    }

    /**
     * @return array<string, callable>
     */
    public static function admin_callback_map(): array
    {
        return [
            self::SLUG_DASHBOARD => [self::class, 'render_dashboard'],
            self::SLUG_ENTRIES => [self::class, 'render_entries_page'],
            self::SLUG_CONTENT => [self::class, 'render_content_page'],
            self::SLUG_SETTINGS => [self::class, 'render_settings_page'],
            self::SLUG_HELP => [self::class, 'render_help_page'],
            'm365linkcollection-dashboard' => [self::class, 'render_dashboard'],
        ];
    }

    /**
     * @param mixed $router
     */
    public static function register_admin_routes($router): void
    {
        if (!function_exists('cms_plugin_admin_register_routes')) {
            return;
        }

        cms_plugin_admin_register_routes(
            $router,
            self::PLUGIN_SLUG,
            self::admin_callback_map(),
            ['m365linkcollection-dashboard']
        );
    }

    public static function render_dashboard(): void
    {
        self::render_with_layout('M365 Linkcollection - Uebersicht', self::SLUG_DASHBOARD, static function (): void {
            self::instance()->render_page('dashboard');
        });
    }

    public static function render_entries_page(): void
    {
        self::render_with_layout('M365 Linkcollection - Eintraege', self::SLUG_ENTRIES, static function (): void {
            self::instance()->render_page('entries');
        });
    }

    public static function render_content_page(): void
    {
        self::render_with_layout('M365 Linkcollection - Inhalte', self::SLUG_CONTENT, static function (): void {
            self::instance()->render_page('content');
        });
    }

    public static function render_settings_page(): void
    {
        self::render_with_layout('M365 Linkcollection - Anzeige', self::SLUG_SETTINGS, static function (): void {
            self::instance()->render_page('settings');
        });
    }

    public static function render_help_page(): void
    {
        self::render_with_layout('M365 Linkcollection - Hinweise', self::SLUG_HELP, static function (): void {
            self::instance()->render_page('help');
        });
    }

    private static function render_with_layout(string $title, string $slug, callable $renderer): void
    {
        self::check_access();
        self::load_admin_contract();

        if (function_exists('cms_plugin_admin_layout_start')) {
            cms_plugin_admin_layout_start($title, $slug);
        } elseif (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $slug);
            echo '<div class="cms-plugin-admin-layout"><div class="cms-plugin-admin-layout__content">';
        }

        self::enqueue_admin_assets();
        echo '<div class="admin-content mlc-admin-shell">';
        $renderer();
        echo '</div>';

        if (function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_end();
        } elseif (function_exists('renderAdminLayoutEnd')) {
            echo '</div></div>';
            renderAdminLayoutEnd();
        }
    }

    private static function check_access(): void
    {
        $isAdmin = class_exists('CMS\\Auth') && \CMS\Auth::instance()->isAdmin();
        if (!$isAdmin) {
            self::deny_access();
        }

        if (function_exists('current_user_can') && !current_user_can('manage_options')) {
            self::deny_access();
        }
    }

    private static function load_admin_contract(): void
    {
        if (self::$contractLoaded) {
            return;
        }

        $contract = dirname(CMS_M365LINKCOLLECTION_PLUGIN_DIR) . '/shared/admin/plugin-admin-contract.php';
        if (is_file($contract)) {
            require_once $contract;
        }

        self::$contractLoaded = true;
    }

    private static function enqueue_admin_assets(): void
    {
        $css = CMS_M365LINKCOLLECTION_PLUGIN_DIR . 'assets/css/m365linkcollection-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365LINKCOLLECTION_PLUGIN_URL . 'assets/css/m365linkcollection-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    private function render_page(string $section): void
    {
        CMS_M365LINKCOLLECTION_Installer::maybe_install();
        $repo = CMS_M365LINKCOLLECTION_Repository::instance();
        $section = self::normalize_section($section);
        $notice = '';
        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            [$notice, $error] = $this->handle_post();
        }

        $settings = CMS_M365LINKCOLLECTION_Settings::all();
        $categories = $repo->categories(false);
        $editId = max(0, (int) ($_GET['edit'] ?? 0));
        $editItem = $editId > 0 ? $repo->find($editId) : null;
        $payload = $repo->links([], 500, 0);
        $items = $payload['items'];
        $companyOptions = $repo->company_options();
        $speakerOptions = $repo->speaker_options();
        $expertOptions = $repo->expert_options();
        $publicUrl = rtrim((string) (defined('SITE_URL') ? SITE_URL : ''), '/') . CMS_M365LINKCOLLECTION_Settings::route();
        ?>
        <div class="admin-page-header">
            <div>
                <h2>🔗 M365 Linkcollection</h2>
                <p>Blogs, MVP-Sites, Newsquellen und Tools zentral pflegen.</p>
            </div>
            <div class="header-actions">
                <a href="<?php echo self::esc_attr($publicUrl); ?>" class="btn btn-secondary btn-sm" target="_blank" rel="noopener noreferrer">👁️ Public öffnen</a>
                <a href="<?php echo self::esc_attr(self::menu_url(self::SLUG_ENTRIES)); ?>" class="btn btn-primary">➕ Eintrag anlegen</a>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo self::esc($notice); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo self::esc($error); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid mlc-stats">
            <div class="stat-card"><div class="stat-icon">🔗</div><div class="stat-number"><?php echo (int) count($items); ?></div><div class="stat-label">Links</div></div>
            <div class="stat-card"><div class="stat-icon">🏷️</div><div class="stat-number"><?php echo (int) count($categories); ?></div><div class="stat-label">Kategorien</div></div>
            <div class="stat-card"><div class="stat-icon">⭐</div><div class="stat-number"><?php echo (int) count(array_filter($items, static fn(array $item): bool => !empty($item['is_featured']))); ?></div><div class="stat-label">Widget-Pool</div></div>
        </div>

        <?php if ($section === 'dashboard'): ?>
            <?php $this->render_dashboard_quicklinks(); ?>
        <?php elseif ($section === 'settings'): ?>
            <?php $this->render_settings($settings); ?>
        <?php elseif ($section === 'content'): ?>
            <?php $this->render_content($settings); ?>
        <?php elseif ($section === 'help'): ?>
            <?php $this->render_help(); ?>
        <?php else: ?>
            <?php $this->render_entries($items, $categories, $editItem, $companyOptions, $speakerOptions, $expertOptions); ?>
        <?php endif; ?>
        <?php
    }

    /**
     * @return array{0:string,1:string}
     */
    private function handle_post(): array
    {
        $action = (string) ($_POST['action'] ?? '');
        if (!in_array($action, ['save_link', 'delete_link', 'save_settings'], true)) {
            return ['', 'Unbekannte Aktion.'];
        }

        self::check_access();

        $tokenAction = $action === 'save_settings' ? 'm365linkcollection_settings' : 'm365linkcollection_entries';
        if (!self::verify_nonce($tokenAction)) {
            return ['', 'Sicherheitscheck fehlgeschlagen.'];
        }

        try {
            if ($action === 'save_link') {
                CMS_M365LINKCOLLECTION_Repository::instance()->save((array) $_POST);
                return ['Eintrag gespeichert.', ''];
            }

            if ($action === 'delete_link') {
                CMS_M365LINKCOLLECTION_Repository::instance()->delete((int) ($_POST['id'] ?? 0));
                return ['Eintrag gelöscht.', ''];
            }

            if ($action === 'save_settings') {
                CMS_M365LINKCOLLECTION_Settings::save(self::sanitize_settings($_POST));
                return ['Einstellungen gespeichert.', ''];
            }
        } catch (\Throwable $e) {
            self::log_error('Admin action failed (' . $action . '): ' . $e->getMessage());
            return ['', 'Aktion konnte nicht ausgefuehrt werden. Details wurden protokolliert.'];
        }

        return ['', 'Unbekannte Aktion.'];
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @param array<int,array<string,mixed>> $categories
     * @param array<string,mixed>|null $editItem
     * @param array<int,array{id:int,label:string}> $companyOptions
     * @param array<int,array{id:int,label:string,slug:string}> $speakerOptions
     * @param array<int,array{id:int,label:string,slug:string}> $expertOptions
     */
    private function render_entries(array $items, array $categories, ?array $editItem, array $companyOptions, array $speakerOptions, array $expertOptions): void
    {
        $csrfToken = self::generate_nonce('m365linkcollection_entries');
        $item = $editItem ?? [
            'id' => 0,
            'category_id' => (int) ($categories[0]['id'] ?? 0),
            'title' => '',
            'subtitle' => '',
            'url' => '',
            'image_url' => '',
            'image_alt' => '',
            'tags' => '',
            'company_id' => 0,
            'speaker_id' => 0,
            'expert_id' => 0,
            'show_company_button' => 0,
            'show_speaker_button' => 0,
            'show_expert_button' => 0,
            'status' => 'active',
            'is_featured' => 0,
            'sort_order' => 0,
        ];
        ?>
        <div class="admin-card mlc-editor-card">
            <h3><?php echo ((int) ($item['id'] ?? 0) > 0) ? '✏️ Eintrag bearbeiten' : '➕ Neuer Eintrag'; ?></h3>
            <form method="POST" class="admin-form mlc-entry-form">
                <input type="hidden" name="action" value="save_link">
                <input type="hidden" name="csrf_token" value="<?php echo self::esc_attr($csrfToken); ?>">
                <input type="hidden" name="id" value="<?php echo (int) ($item['id'] ?? 0); ?>">
                <div class="mlc-form-grid">
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
                    <label>Untertitel / Schwerpunkt
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
                    <label>Company-Verknüpfung
                        <select name="company_id" class="form-control">
                            <option value="0">Keine Company</option>
                            <?php foreach ($companyOptions as $company): ?>
                            <option value="<?php echo (int) $company['id']; ?>"<?php echo (int) ($item['company_id'] ?? 0) === (int) $company['id'] ? ' selected' : ''; ?>><?php echo self::esc($company['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Speaker-Verknüpfung
                        <select name="speaker_id" class="form-control">
                            <option value="0">Kein Speaker-Profil</option>
                            <?php foreach ($speakerOptions as $speaker): ?>
                            <option value="<?php echo (int) $speaker['id']; ?>"<?php echo (int) ($item['speaker_id'] ?? 0) === (int) $speaker['id'] ? ' selected' : ''; ?>><?php echo self::esc($speaker['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Expert-Verknüpfung
                        <select name="expert_id" class="form-control">
                            <option value="0">Kein Expert-Profil</option>
                            <?php foreach ($expertOptions as $expert): ?>
                            <option value="<?php echo (int) $expert['id']; ?>"<?php echo (int) ($item['expert_id'] ?? 0) === (int) $expert['id'] ? ' selected' : ''; ?>><?php echo self::esc($expert['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
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
                    <input type="text" name="tags" class="form-control" value="<?php echo self::esc_attr((string) ($item['tags'] ?? '')); ?>" maxlength="500">
                </label>
                <div class="mlc-check-row">
                    <label><input type="checkbox" name="is_featured" value="1"<?php echo !empty($item['is_featured']) ? ' checked' : ''; ?>> Im PHINIT-Widget rotieren</label>
                    <label><input type="checkbox" name="show_company_button" value="1"<?php echo !empty($item['show_company_button']) ? ' checked' : ''; ?>> Company-Button anzeigen</label>
                    <label><input type="checkbox" name="show_speaker_button" value="1"<?php echo !empty($item['show_speaker_button']) ? ' checked' : ''; ?>> Speaker-Button anzeigen</label>
                    <label><input type="checkbox" name="show_expert_button" value="1"<?php echo !empty($item['show_expert_button']) ? ' checked' : ''; ?>> Expert-Button anzeigen</label>
                </div>
                <button type="submit" class="btn btn-primary">💾 Eintrag speichern</button>
                <?php if ((int) ($item['id'] ?? 0) > 0): ?>
                <a href="<?php echo self::esc_attr(self::menu_url(self::SLUG_ENTRIES)); ?>" class="btn btn-secondary">Neu anlegen</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="admin-card">
            <h3>📋 Gepflegte Links</h3>
            <?php if ($items === []): ?>
            <div class="empty-state"><p><strong>Noch keine Einträge vorhanden</strong></p><p>Beim Aktivieren werden Startdaten automatisch importiert.</p></div>
            <?php else: ?>
            <div class="users-table-container">
                <table class="users-table">
                    <thead><tr><th>Titel</th><th>Kategorie</th><th>URL</th><th>Status</th><th>Widget</th><th>Aktionen</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $row): ?>
                    <tr>
                        <td><a href="<?php echo self::esc_attr(self::menu_url(self::SLUG_ENTRIES) . '&edit=' . (int) $row['id']); ?>" class="mlc-table-title"><?php echo self::esc((string) $row['title']); ?></a><br><small><?php echo self::esc((string) ($row['subtitle'] ?? '')); ?></small></td>
                        <td><?php echo self::esc((string) ($row['category_name'] ?? '')); ?></td>
                        <td><a href="<?php echo self::esc_attr((string) $row['url']); ?>" target="_blank" rel="noopener noreferrer">öffnen</a></td>
                        <td><span class="status-badge <?php echo (string) $row['status'] === 'active' ? 'active' : 'inactive'; ?>"><?php echo (string) $row['status'] === 'active' ? 'Aktiv' : 'Inaktiv'; ?></span></td>
                        <td><?php echo !empty($row['is_featured']) ? '⭐' : '—'; ?></td>
                        <td><div class="mlc-action-row"><a href="<?php echo self::esc_attr(self::menu_url(self::SLUG_ENTRIES) . '&edit=' . (int) $row['id']); ?>" class="btn btn-sm btn-secondary">✏️</a><button type="button" class="btn btn-sm btn-danger" onclick="openMlcDeleteModal(<?php echo (int) $row['id']; ?>, <?php echo self::json((string) $row['title']); ?>)">🗑️</button></div></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <div id="mlcDeleteModal" class="modal" style="display:none;">
            <div class="modal-content" style="max-width:480px;">
                <div class="modal-header"><h3>🗑️ Eintrag löschen</h3><button class="modal-close" onclick="closeMlcModal()" type="button">&times;</button></div>
                <div class="modal-body"><p>Soll <strong id="mlcDeleteName"></strong> wirklich gelöscht werden?</p><p class="mlc-danger-note">Diese Aktion kann nicht rückgängig gemacht werden.</p></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeMlcModal()">Abbrechen</button>
                    <form method="POST" id="mlcDeleteForm">
                        <input type="hidden" name="action" value="delete_link">
                        <input type="hidden" name="csrf_token" value="<?php echo self::esc_attr($csrfToken); ?>">
                        <input type="hidden" name="id" id="mlcDeleteId">
                        <button type="submit" class="btn btn-danger">Endgültig löschen</button>
                    </form>
                </div>
            </div>
        </div>
        <script>
        function openMlcDeleteModal(id, name) {
            document.getElementById('mlcDeleteId').value = String(id);
            document.getElementById('mlcDeleteName').textContent = name;
            document.getElementById('mlcDeleteModal').style.display = 'block';
        }
        function closeMlcModal() {
            document.getElementById('mlcDeleteModal').style.display = 'none';
        }
        </script>
        <?php
    }

    /** @param array<string,string> $settings */
    private function render_content(array $settings): void
    {
        $csrfToken = self::generate_nonce('m365linkcollection_settings');
        ?>
        <div class="admin-card mlc-editor-card">
            <h3>✍️ Öffentliche Texte bearbeiten</h3>
            <p class="mlc-admin-hint">Diese Inhalte erscheinen auf der Linkcollection-Seite und im PHINIT-Sidebar-Widget.</p>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="settings_section" value="content">
                <input type="hidden" name="csrf_token" value="<?php echo self::esc_attr($csrfToken); ?>">

                <h4 class="mlc-section-title">Seitenkopf</h4>
                <div class="mlc-form-grid">
                    <?php self::input('page_route', 'Public Route', $settings); ?>
                    <?php self::input('page_overline', 'Header-Overline', $settings); ?>
                    <?php self::input('page_title', 'Seitentitel', $settings); ?>
                </div>
                <?php self::textarea('page_intro', 'Introtext', $settings, 3); ?>

                <h4 class="mlc-section-title">Filter, Ansichten und Leerzustand</h4>
                <div class="mlc-form-grid">
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
                    <?php self::input($key, $label, $settings); ?>
                    <?php endforeach; ?>
                </div>
                <?php self::textarea('label_empty_body', 'Leerzustand Text', $settings, 2); ?>

                <h4 class="mlc-section-title">Überschriften, Tabelle und Pagination</h4>
                <div class="mlc-form-grid">
                    <?php foreach ([
                        'label_cards_heading' => 'Cards-Überschrift',
                        'label_table_heading' => 'Tabellen-Überschrift',
                        'label_table_image' => 'Spalte: Bild',
                        'label_table_title' => 'Spalte: Name',
                        'label_table_subtitle' => 'Spalte: Schwerpunkt',
                        'label_table_url' => 'Spalte: URL',
                        'label_table_actions' => 'Spalte: Aktionen',
                        'label_pagination_page' => 'Pagination: Seite',
                        'label_pagination_of' => 'Pagination: von',
                        'label_pagination_nav' => 'ARIA: Seitennavigation',
                        'label_pagination_prev' => 'Pagination: Zurück',
                        'label_pagination_next' => 'Pagination: Weiter',
                    ] as $key => $label): ?>
                    <?php self::input($key, $label, $settings); ?>
                    <?php endforeach; ?>
                </div>

                <h4 class="mlc-section-title">Buttons und Sidebar-Widget</h4>
                <div class="mlc-form-grid">
                    <?php foreach ([
                        'external_button_label' => 'Externer Button',
                        'company_button_label' => 'Company Button',
                        'speaker_button_label' => 'Speaker Button',
                        'expert_button_label' => 'Expert Button',
                        'sidebar_title' => 'Sidebar Widget Titel',
                        'sidebar_button_label' => 'Sidebar Button',
                        'sidebar_controls_label' => 'Sidebar Steuerung Label',
                        'sidebar_prev_label' => 'Sidebar Zurück ARIA',
                        'sidebar_next_label' => 'Sidebar Weiter ARIA',
                    ] as $key => $label): ?>
                    <?php self::input($key, $label, $settings); ?>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn btn-primary">💾 Texte speichern</button>
            </form>
        </div>
        <?php
    }

    /** @param array<string,string> $settings */
    private function render_settings(array $settings): void
    {
        $csrfToken = self::generate_nonce('m365linkcollection_settings');
        $columns = ['image' => 'Bild', 'title' => 'Titel', 'subtitle' => 'Schwerpunkt', 'url' => 'URL', 'actions' => 'Buttons'];
        $visible = array_filter(array_map('trim', explode(',', (string) ($settings['visible_columns'] ?? ''))));
        ?>
        <div class="admin-card mlc-editor-card">
            <h3>🎨 Anzeige, Design & Widget-Verhalten</h3>
            <p class="mlc-admin-hint">Hier steuerst du Layout, Farben, Tabellenoptionen und die Sidebar-Rotation.</p>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_settings">
                <input type="hidden" name="settings_section" value="design">
                <input type="hidden" name="csrf_token" value="<?php echo self::esc_attr($csrfToken); ?>">
                <h4 class="mlc-section-title">Anzeige</h4>
                <div class="mlc-form-grid">
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
                <h4 class="mlc-section-title">Farben</h4>
                <div class="mlc-form-grid">
                    <?php foreach (['color_page_background' => 'Seitenhintergrund', 'color_surface' => 'Kartenfläche', 'color_text' => 'Text', 'color_muted' => 'Sekundärtext', 'color_border' => 'Rahmen', 'color_accent' => 'Akzent', 'color_button_bg' => 'Button Hintergrund', 'color_button_text' => 'Button Text'] as $key => $label): ?>
                    <?php self::input($key, $label, $settings, 'color'); ?>
                    <?php endforeach; ?>
                </div>
                <h4 class="mlc-section-title">Sidebar-Widget</h4>
                <div class="mlc-form-grid">
                    <?php self::select('sidebar_style', 'Widget-Aussehen', $settings, ['card' => 'Card mit Bild', 'compact' => 'Kompakt', 'minimal' => 'Minimal']); ?>
                    <?php self::number('sidebar_limit', 'Sidebar Links', $settings, 1, 20, 1); ?>
                    <?php self::number('sidebar_rotate_seconds', 'Sidebar Wechsel (Sek.)', $settings, 3, 60, 1); ?>
                    <?php self::number('sidebar_min_height', 'Widget Mindesthöhe (px)', $settings, 120, 520, 1); ?>
                    <?php self::number('sidebar_image_height', 'Widget Bildhöhe (px)', $settings, 0, 320, 1); ?>
                    <?php self::input('sidebar_placeholder_image', 'Sidebar Platzhalter-Bild', $settings); ?>
                </div>
                <div class="mlc-check-row">
                    <?php foreach (['page_enabled' => 'Public-Seite aktiv', 'show_category_nav' => 'Kategorienavigation', 'show_cards' => 'Cards anzeigen', 'show_table' => 'Tabelle anzeigen', 'show_images' => 'Bilder anzeigen', 'show_structured_data' => 'Structured Data (JSON-LD) ausgeben', 'accessibility_validation_mode' => 'Accessibility Validation Mode (Public Diagnostics)', 'show_company_buttons' => 'Company-Buttons', 'show_speaker_buttons' => 'Speaker-Buttons', 'show_expert_buttons' => 'Expert-Buttons', 'sidebar_enabled' => 'PHINIT-Sidebar-Widget aktiv', 'sidebar_show_image' => 'Bild im Widget', 'sidebar_show_category' => 'Kategorie im Widget', 'sidebar_show_subtitle' => 'Untertitel im Widget'] as $key => $label): ?>
                    <label><input type="checkbox" name="<?php echo self::esc_attr($key); ?>" value="1"<?php echo !empty($settings[$key]) && $settings[$key] !== '0' ? ' checked' : ''; ?>> <?php echo self::esc($label); ?></label>
                    <?php endforeach; ?>
                </div>
                <fieldset class="mlc-fieldset">
                    <legend>Tabellenspalten</legend>
                    <div class="mlc-check-row">
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

    private function render_help(): void
    {
        ?>
        <div class="admin-card">
            <h3>ℹ️ Hinweise zur Integration</h3>
            <ul class="mlc-help-list">
                <li>Die Public-Seite ist standardmäßig unter <code>/m365-sites-blogs</code> erreichbar.</li>
                <li>Company-/Expert-Buttons werden nur auf dieser Übersicht ausgegeben und nur, wenn das jeweilige Plugin aktiv ist.</li>
                <li>Das PHINIT-Startseiten-Sidebar-Widget nutzt bevorzugt Einträge mit aktivem Widget-Stern.</li>
                <li>Bilder können je Eintrag per URL gepflegt werden; ohne Bild wird ein ruhiger Initialen-Platzhalter angezeigt.</li>
            </ul>
        </div>
        <?php
    }

    private function render_dashboard_quicklinks(): void
    {
        ?>
        <div class="admin-card mlc-dashboard-links">
            <h3>🧭 Schnellzugriffe</h3>
            <div class="mlc-quicklink-grid">
                <a class="mlc-quicklink" href="<?php echo self::esc_attr(self::menu_url(self::SLUG_ENTRIES)); ?>">
                    <strong>🔗 Eintraege</strong>
                    <span>Links anlegen, bearbeiten und loeschen.</span>
                </a>
                <a class="mlc-quicklink" href="<?php echo self::esc_attr(self::menu_url(self::SLUG_CONTENT)); ?>">
                    <strong>✍️ Inhalte</strong>
                    <span>Texte und Labels der Public-Seite pflegen.</span>
                </a>
                <a class="mlc-quicklink" href="<?php echo self::esc_attr(self::menu_url(self::SLUG_SETTINGS)); ?>">
                    <strong>🎨 Anzeige</strong>
                    <span>Layout, Farben, Tabellenoptionen und Widget konfigurieren.</span>
                </a>
                <a class="mlc-quicklink" href="<?php echo self::esc_attr(self::menu_url(self::SLUG_HELP)); ?>">
                    <strong>ℹ️ Hinweise</strong>
                    <span>Routing- und Integrationshinweise einsehen.</span>
                </a>
            </div>
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
        $defaults = CMS_M365LINKCOLLECTION_Settings::defaults();
        $settings = [];
        $section = (string) ($post['settings_section'] ?? 'all');
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
            'show_structured_data',
            'accessibility_validation_mode',
            'show_company_buttons',
            'show_speaker_buttons',
            'show_expert_buttons',
            'sidebar_enabled',
            'sidebar_show_image',
            'sidebar_show_category',
            'sidebar_show_subtitle',
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
        foreach ($defaults as $key => $default) {
            if ($section === 'content' && !array_key_exists($key, $post)) {
                continue;
            }
            if ($section === 'design' && !in_array($key, $designKeys, true)) {
                continue;
            }
            if (str_starts_with($key, 'show_') || in_array($key, ['page_enabled', 'sidebar_enabled', 'sidebar_show_image', 'sidebar_show_category', 'sidebar_show_subtitle', 'accessibility_validation_mode'], true)) {
                $settings[$key] = !empty($post[$key]) ? '1' : '0';
                continue;
            }

            if ($key === 'page_route') {
                $settings[$key] = self::sanitize_route((string) ($post[$key] ?? $default), (string) $default);
                continue;
            }

            if ($key === 'default_view') {
                $candidate = (string) ($post[$key] ?? $default);
                $settings[$key] = in_array($candidate, ['cards', 'table', 'both'], true) ? $candidate : (string) $default;
                continue;
            }

            if ($key === 'table_density') {
                $candidate = (string) ($post[$key] ?? $default);
                $settings[$key] = in_array($candidate, ['comfortable', 'compact'], true) ? $candidate : (string) $default;
                continue;
            }

            if ($key === 'sidebar_style') {
                $candidate = (string) ($post[$key] ?? $default);
                $settings[$key] = in_array($candidate, ['card', 'compact', 'minimal'], true) ? $candidate : (string) $default;
                continue;
            }

            if (isset($intRanges[$key])) {
                [$min, $max] = $intRanges[$key];
                $settings[$key] = (string) self::clamp_int((int) ($post[$key] ?? $default), $min, $max);
                continue;
            }

            if (in_array($key, $colorKeys, true)) {
                $settings[$key] = self::sanitize_color((string) ($post[$key] ?? $default), (string) $default);
                continue;
            }

            if ($key === 'sidebar_placeholder_image') {
                $settings[$key] = self::sanitize_optional_url((string) ($post[$key] ?? $default), (string) $default);
                continue;
            }

            if ($key === 'visible_columns') {
                $columns = array_values(array_intersect((array) ($post['visible_columns'] ?? []), ['image', 'title', 'subtitle', 'url', 'actions']));
                $settings[$key] = implode(',', $columns !== [] ? $columns : explode(',', $default));
                continue;
            }
            $settings[$key] = self::sanitize_text((string) ($post[$key] ?? $default), 5000);
        }
        return $settings;
    }

    private static function normalize_section(string $section): string
    {
        $map = [
            'dashboard' => 'dashboard',
            'entries' => 'entries',
            'content' => 'content',
            'settings' => 'settings',
            'help' => 'help',
        ];

        // Legacy deep-links: ?tab=... should still work.
        $legacyTab = preg_replace('/[^a-z0-9_-]+/i', '', (string) ($_GET['tab'] ?? ''));
        if (is_string($legacyTab) && isset($map[$legacyTab])) {
            return $map[$legacyTab];
        }

        return $map[$section] ?? 'entries';
    }

    private static function menu_url(string $slug): string
    {
        if (function_exists('cms_plugin_admin_page_path')) {
            return cms_plugin_admin_page_path(self::PLUGIN_SLUG, $slug);
        }

        return '/admin/plugins/' . rawurlencode(self::PLUGIN_SLUG) . '/' . rawurlencode($slug);
    }

    private static function generate_nonce(string $action): string
    {
        if (!class_exists('CMS\\Security')) {
            self::log_error('Admin security service missing for nonce generation: ' . $action);

            return '';
        }
        return (string) \CMS\Security::instance()->generateToken($action);
    }

    private static function verify_nonce(string $action): bool
    {
        if (!class_exists('CMS\\Security')) {
            self::log_error('Admin security service missing for nonce verification: ' . $action);

            return false;
        }
        $token = (string) ($_POST['csrf_token'] ?? '');
        if ($token === '') {
            return false;
        }

        return \CMS\Security::instance()->verifyToken($token, $action);
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

    private static function deny_access(): void
    {
        if (function_exists('http_response_code')) {
            http_response_code(403);
        }

        $fallback = '/';
        if (defined('SITE_URL')) {
            $candidate = filter_var((string) SITE_URL, FILTER_VALIDATE_URL);
            if (is_string($candidate) && $candidate !== '') {
                $fallback = $candidate;
            }
        }

        header('Location: ' . $fallback);
        exit;
    }

    private static function clamp_int(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    private static function sanitize_text(string $value, int $maxLength): string
    {
        $clean = trim(strip_tags($value));
        if (function_exists('mb_substr')) {
            return mb_substr($clean, 0, $maxLength);
        }

        return substr($clean, 0, $maxLength);
    }

    private static function sanitize_route(string $value, string $fallback): string
    {
        $route = '/' . trim($value, '/');
        $route = preg_replace('#/+#', '/', $route);
        if (!is_string($route) || preg_match('#^/[a-z0-9/_-]{1,200}$#i', $route) !== 1) {
            return $fallback;
        }

        return $route;
    }

    private static function sanitize_color(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));
        if (preg_match('/^#[0-9a-f]{6}$/', $value) !== 1) {
            return $fallback;
        }

        return $value;
    }

    private static function sanitize_optional_url(string $value, string $fallback): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (filter_var($value, FILTER_VALIDATE_URL) !== false || str_starts_with($value, '/')) {
            return $value;
        }

        return $fallback;
    }

    private static function log_error(string $message): void
    {
        error_log('CMS M365 Linkcollection: ' . $message);
    }
}
