<?php
/**
 * Admin Interface für CMS Feed
 * Tabs: Dashboard | Kanäle | Bereiche | Digests | Einstellungen
 *
 * @package CMS_Feed
 * @since   1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Feed_Admin
{
    private static ?self $instance = null;
    public const MENU_SLUG = 'feeds';

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->loadAdminMenu();
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addAction('cms_admin_menu', [$this, 'register_menu'], 10);
            CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
        }
    }

    public function register_menu(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'Feeds',
            'Feeds',
            'manage_options',
            self::MENU_SLUG,
            [self::class, 'render_dispatch'],
            '📡',
            38
        );
    }

    public static function render_dispatch(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        self::instance()->render_view(false);
    }

    // ══════════════════════════════════════════════════════════════════════
    // Router-Callback: /admin/feeds
    // ══════════════════════════════════════════════════════════════════════

    public function admin_page(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        $this->render_view(true);
    }

    private function loadAdminMenu(): void
    {
        if (function_exists('renderAdminLayoutStart') && function_exists('renderAdminLayoutEnd')) {
            return;
        }

        $menuFiles = [
            ABSPATH . 'includes/functions/admin-menu.php',
            ABSPATH . 'CMS/includes/functions/admin-menu.php',
            ABSPATH . 'admin/partials/admin-menu.php',
        ];

        foreach ($menuFiles as $menuFile) {
            if (file_exists($menuFile)) {
                require_once $menuFile;
                if (function_exists('renderAdminLayoutStart')) {
                    return;
                }
            }
        }
    }

    public function add_menu_item(array $menuItems): array
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $isActive    = str_starts_with($currentPath, '/admin/feeds');

        $menuItems[] = [
            'type'   => 'item',
            'slug'   => 'feeds',
            'label'  => 'Feeds',
            'icon'   => '📡',
            'url'    => '/admin/feeds',
            'active' => $isActive,
        ];

        return $menuItems;
    }

    // ══════════════════════════════════════════════════════════════════════
    // render_list – Admin-Oberfläche
    // ══════════════════════════════════════════════════════════════════════

    public function render_list(array $data): void
    {
        $this->render_view(true, $data);
    }

    private function render_view(bool $withLayout, array $data = []): void
    {
        $this->loadAdminMenu();

        try {
            $db   = CMS_Feed_Database::instance();
            $sec  = \CMS\Security::instance();
        } catch (\Throwable $e) {
            $this->render_admin_failure('Feed-Administration konnte nicht initialisiert werden', 'Die Feed-Administration konnte aktuell nicht initialisiert werden. Bitte versuche es später erneut.', $e, 'admin.bootstrap');
            return;
        }

        $schemaReady = true;

        $rawTab = $data['tab'] ?? ($_GET['tab'] ?? 'dashboard');
        $tab = is_scalar($rawTab) ? (string) $rawTab : 'dashboard';
        if (!in_array($tab, ['dashboard', 'channels', 'categories', 'catalog', 'items', 'digests', 'settings'], true)) {
            $tab = 'dashboard';
        }

        $notice = $data['notice'] ?? null;
        $error  = $data['error']  ?? null;
        $settingsSubTab = null;

        try {
            $db->ensure_schema();
        } catch (\Throwable $e) {
            $this->render_admin_failure('Feed-Datenbank konnte nicht vorbereitet werden', 'Die Feed-Datenbanktabellen konnten nicht vorbereitet werden. Bitte prüfe Migrationen und Datenbankrechte.', $e, 'admin.schema');
            return;
        }

        // ── POST-Verarbeitung (VOR Token-Generierung, damit das alte Token geprüft wird) ──
        if ($schemaReady && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            try {
                if (!$sec->verifyToken($this->post_string('csrf_token'), 'cms_feed_admin')) {
                    http_response_code(403);
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    $result = $this->handle_post($tab);
                    if (isset($result['status'])) {
                        http_response_code((int) $result['status']);
                    }
                    $notice         = $result['notice'] ?? null;
                    $error          = $result['error']  ?? null;
                    $settingsSubTab = $result['stab']   ?? null;
                }
            } catch (\Throwable $e) {
                $this->render_admin_failure('Feed-Aktion konnte nicht ausgeführt werden', 'Die angeforderte Feed-Aktion konnte nicht ausgeführt werden. Details wurden protokolliert.', $e, 'admin.post');
                return;
            }
        }

        // CSRF-Token für Formulare generieren (NACH der Verarbeitung)
        $csrf = $sec->generateToken('cms_feed_admin');

        // ── Daten laden ───────────────────────────────────────────────
        $settings = [];
        $categories = [];
        $channels = [];
        $digests = [];
        $stats = $this->get_empty_stats();
        $queueStats = $this->get_empty_queue_stats();
        $healthSummary = $this->get_empty_health_summary();
        $attentionChannels = [];

        if ($schemaReady) {
            try {
                $settings = $db->get_settings();
                $categories = $db->get_categories();
                $channels = $db->get_channels();
                $digests = $db->get_digests();
                $stats = array_merge($stats, $db->get_stats());
                $queueStats = array_merge($queueStats, $db->get_queue_stats());
                $healthSummary = array_merge($healthSummary, $db->get_channel_health_summary());
                $attentionChannels = $db->get_attention_channels(6);
            } catch (\Throwable $e) {
                $this->render_admin_failure('Feed-Daten konnten nicht geladen werden', 'Die Feed-Daten konnten aktuell nicht geladen werden. Bitte versuche es später erneut.', $e, 'admin.load_data');
                return;
            }
        }

        // ── Tabs definieren ───────────────────────────────────────────
        $tabs = [
            'dashboard'  => '📊 Dashboard',
            'channels'   => '📡 Kanäle',
            'categories' => '📁 Bereiche',
            'catalog'    => '📚 Katalog',
            'items'      => '📰 Beiträge',
            'digests'    => '📧 E-Mail-Digests',
            'settings'   => '⚙️ Einstellungen',
        ];

        $bufferLevel = ob_get_level();
        ob_start();
        try {
            if ($withLayout) {
                $this->render_layout_start();
            }

            $adminCss = CMS_FEED_PLUGIN_DIR . 'assets/css/feed-admin.css';
            if (file_exists($adminCss)) {
                echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_FEED_PLUGIN_URL . 'assets/css/feed-admin.css', ENT_QUOTES, 'UTF-8') . '?v=' . filemtime($adminCss) . '">' . "\n";
            }

            include CMS_FEED_PLUGIN_DIR . 'admin/views/page-admin.php';

            if ($withLayout) {
                $this->render_layout_end();
            }

            echo ob_get_clean();
        } catch (\Throwable $e) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            $this->render_admin_failure('Feed-Ansicht konnte nicht geladen werden', 'Die Feed-Ansicht konnte aktuell nicht geladen werden. Bitte versuche es später erneut.', $e, 'admin.render_view');
        }
    }

    private function render_layout_start(): void
    {
        try {
            if (function_exists('renderAdminLayoutStart')) {
                renderAdminLayoutStart('Feeds', self::MENU_SLUG);
                return;
            }
        } catch (\Throwable $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed Admin: Layout-Start fehlgeschlagen.', $e, 'warning', ['scope' => 'admin.layout_start']);
        }

        echo '<main class="admin-content">';
    }

    private function render_layout_end(): void
    {
        try {
            if (function_exists('renderAdminLayoutEnd')) {
                renderAdminLayoutEnd();
                return;
            }
        } catch (\Throwable $e) {
            CMS_Feed_Error_Handler::instance()->log_exception('CMS Feed Admin: Layout-Ende fehlgeschlagen.', $e, 'warning', ['scope' => 'admin.layout_end']);
        }

        echo '</main>';
    }

    private function render_admin_failure(string $title, string $message, \Throwable $exception, string $scope): void
    {
        CMS_Feed_Error_Handler::instance()->render_error_page(500, $title, $message, $exception, [
            'scope' => $scope,
        ]);
    }

    private function get_empty_stats(): array
    {
        return [
            'categories' => 0,
            'channels' => 0,
            'channels_active' => 0,
            'items' => 0,
            'items_today' => 0,
            'digests' => 0,
            'member_subscriptions' => 0,
            'channels_errors' => 0,
        ];
    }

    private function get_empty_queue_stats(): array
    {
        return [
            'pending' => 0,
            'processing' => 0,
            'done' => 0,
            'failed' => 0,
            'total' => 0,
        ];
    }

    private function get_empty_health_summary(): array
    {
        return [
            'active' => 0,
            'with_errors' => 0,
            'never_fetched' => 0,
            'overdue' => 0,
        ];
    }

    // ══════════════════════════════════════════════════════════════════════
    // POST-Handler
    // ══════════════════════════════════════════════════════════════════════

    private function handle_post(string $tab): array
    {
        unset($tab);

        $action = $this->post_string('action');
        $handlers = [
            'save_channel' => 'handle_save_channel_post',
            'delete_channel' => 'handle_delete_channel_post',
            'save_category' => 'handle_save_category_post',
            'delete_category' => 'handle_delete_category_post',
            'fetch_now' => 'handle_fetch_now_post',
            'toggle_hidden' => 'handle_toggle_hidden_post',
            'toggle_featured' => 'handle_toggle_featured_post',
            'delete_item' => 'handle_delete_item_post',
            'save_digest' => 'handle_save_digest_post',
            'delete_digest' => 'handle_delete_digest_post',
            'test_digest' => 'handle_test_digest_post',
            'save_settings' => 'handle_save_settings_post',
            'save_design' => 'handle_save_design_post',
            'save_digest_settings' => 'handle_save_digest_settings_post',
            'cleanup' => 'handle_cleanup_post',
            'import_catalog' => 'handle_import_catalog_post',
            'bulk_delete_channels' => 'handle_bulk_delete_channels_post',
            'bulk_activate_channels' => 'handle_bulk_activate_channels_post',
            'bulk_deactivate_channels' => 'handle_bulk_deactivate_channels_post',
            'bulk_fetch_channels' => 'handle_bulk_fetch_channels_post',
            'bulk_delete_categories' => 'handle_bulk_delete_categories_post',
        ];

        if (!isset($handlers[$action])) {
            return ['error' => 'Unbekannte Feed-Admin-Aktion.', 'status' => 400];
        }

        $handler = $handlers[$action];

        return $this->{$handler}();
    }

    private function handle_save_channel_post(): array
    {
        $name = $this->post_text('channel_name');
        $feedFetcher = CMS_Feed_RSS_Fetcher::instance();
        $feedUrlValidation = $feedFetcher->validate_feed_url($this->post_string('feed_url'));
        $catId = $this->post_int('category_id');

        if (empty($name) || $catId < 1) {
            return ['error' => 'Name, Feed-URL und Bereich sind erforderlich.'];
        }

        if (!$feedUrlValidation['success']) {
            return ['error' => $feedUrlValidation['error'] ?? 'Feed-URL ist nicht erlaubt.'];
        }

        CMS_Feed_Database::instance()->save_channel([
            'id'             => $this->post_int('channel_id') ?: null,
            'category_id'    => $catId,
            'name'           => $name,
            'feed_url'       => $feedUrlValidation['url'] ?? '',
            'site_url'       => $this->sanitize_public_url($this->post_string('site_url')) ?: null,
            'description'    => $this->post_text('channel_description'),
            'is_active'      => $this->post_bool('is_active') ? 1 : 0,
            'fetch_interval' => max(5, min(1440, $this->post_int('fetch_interval', 60))),
            'max_items'      => max(10, min(500, $this->post_int('max_items', 50))),
        ]);

        return ['notice' => 'Kanal gespeichert.'];
    }

    private function handle_delete_channel_post(): array
    {
        $id = $this->post_int('id');
        if ($id <= 0) {
            return ['error' => 'Ungültige Kanal-ID.'];
        }

        CMS_Feed_Database::instance()->delete_channel($id);
        return ['notice' => 'Kanal und zugehörige Beiträge gelöscht.'];
    }

    private function handle_save_category_post(): array
    {
        $name = $this->post_text('cat_name');
        $slug = $this->post_text('cat_slug');

        if (empty($name) || empty($slug)) {
            return ['error' => 'Name und Slug sind erforderlich.'];
        }

        $slug = $this->sanitize_slug($slug);
        if ($slug === '' || $slug === 'feed') {
            return ['error' => 'Bitte verwende einen gültigen, konfliktfreien Slug.'];
        }

        CMS_Feed_Database::instance()->save_category([
            'id'             => $this->post_int('cat_id') ?: null,
            'name'           => $name,
            'slug'           => $slug,
            'description'    => $this->post_text('cat_description'),
            'icon'           => cms_feed_substr(trim($this->post_string('cat_icon', '📰')), 0, 10),
            'is_public'      => $this->post_bool('cat_is_public') ? 1 : 0,
            'sort_order'     => $this->post_int('cat_sort_order'),
            'layout'         => $this->post_enum('cat_layout', ['grid', 'list', 'magazine'], 'grid'),
            'items_per_page' => max(4, min(100, $this->post_int('cat_items_per_page', 20))),
        ]);

        return ['notice' => 'Bereich gespeichert.'];
    }

    private function handle_delete_category_post(): array
    {
        $id = $this->post_int('id');
        if ($id <= 0) {
            return ['error' => 'Ungültige Bereich-ID.'];
        }

        CMS_Feed_Database::instance()->delete_category($id);
        return ['notice' => 'Bereich und alle zugehörigen Daten gelöscht.'];
    }

    private function handle_fetch_now_post(): array
    {
        $channelId = $this->post_int('channel_id');
        $fetcher = CMS_Feed_RSS_Fetcher::instance();

        if ($channelId > 0) {
            $result = $fetcher->fetch_channel($channelId);
            if ($result['success']) {
                return ['notice' => $result['new_items'] . ' neue Beiträge importiert.'];
            }

            return ['error' => 'Fehler: ' . ($result['error'] ?? 'Unbekannt')];
        }

        $summary = $fetcher->fetch_all_due();
        if (($summary['due'] ?? 0) === 0) {
            return ['notice' => 'Keine fälligen Kanäle gefunden.'];
        }

        $message = (int) ($summary['processed'] ?? 0) . ' Kanäle geprüft, ' . (int) ($summary['new_items'] ?? 0) . ' neue Beiträge.';
        if ((int) ($summary['queued'] ?? 0) > 0) {
            $message .= ' ' . (int) $summary['queued'] . ' weitere Kanäle in Warteschlange (Cron).';
        }

        return ['notice' => $message];
    }

    private function handle_toggle_hidden_post(): array
    {
        return $this->handle_item_toggle_post('hidden');
    }

    private function handle_toggle_featured_post(): array
    {
        return $this->handle_item_toggle_post('featured');
    }

    private function handle_delete_item_post(): array
    {
        $id = $this->post_int('id');
        if ($id <= 0) {
            return ['error' => 'Ungültige ID.'];
        }

        CMS_Feed_Database::instance()->delete_item($id);
        return ['notice' => 'Beitrag gelöscht.'];
    }

    private function handle_save_digest_post(): array
    {
        $name = $this->post_text('digest_name');
        $email = filter_var($this->post_string('digest_email'), FILTER_VALIDATE_EMAIL);

        if (empty($name) || !$email) {
            return ['error' => 'Name und gültige E-Mail sind erforderlich.'];
        }

        $catIds = array_values(array_filter(array_map('intval', $this->post_array('digest_categories')), static fn (int $catId): bool => $catId > 0));
        if (empty($catIds)) {
            return ['error' => 'Mindestens ein Bereich muss gewählt werden.'];
        }

        CMS_Feed_Database::instance()->save_digest([
            'id'           => $this->post_int('digest_id') ?: null,
            'name'         => $name,
            'email'        => $email,
            'category_ids' => $catIds,
            'frequency'    => max(1, min(4, $this->post_int('digest_frequency', 1))),
            'is_active'    => $this->post_bool('digest_is_active') ? 1 : 0,
        ]);

        return ['notice' => 'Digest gespeichert.'];
    }

    private function handle_delete_digest_post(): array
    {
        $id = $this->post_int('id');
        if ($id <= 0) {
            return ['error' => 'Ungültige ID.'];
        }

        CMS_Feed_Database::instance()->delete_digest($id);
        return ['notice' => 'Digest gelöscht.'];
    }

    private function handle_test_digest_post(): array
    {
        $id = $this->post_int('digest_id');
        if ($id <= 0) {
            return ['error' => 'Ungültige Digest-ID.'];
        }

        $mailer = CMS_Feed_Email_Digest::instance();
        if ($mailer->send_test_digest($id)) {
            return ['notice' => 'Test-Digest wurde gesendet.'];
        }

        return ['error' => 'Fehler beim Senden des Test-Digests.'];
    }

    private function handle_save_settings_post(): array
    {
        CMS_Feed_Database::instance()->update_settings([
            'archive_title'       => $this->post_text('archive_title'),
            'archive_description' => $this->post_text('archive_description'),
            'archive_slug'        => $this->sanitize_slug($this->post_string('archive_slug', 'feeds')) ?: 'feeds',
            'per_page'            => (string) max(4, min(100, $this->post_int('per_page', 20))),
            'open_in_new_tab'     => $this->post_bool('open_in_new_tab') ? '1' : '0',
            'show_source'         => $this->post_bool('show_source') ? '1' : '0',
            'show_date'           => $this->post_bool('show_date') ? '1' : '0',
            'show_image'          => $this->post_bool('show_image') ? '1' : '0',
            'show_excerpt'        => $this->post_bool('show_excerpt') ? '1' : '0',
            'excerpt_length'      => (string) max(50, min(500, $this->post_int('excerpt_length', 160))),
        ]);

        return ['notice' => 'Einstellungen gespeichert.', 'stab' => 'general'];
    }

    private function handle_save_design_post(): array
    {
        CMS_Feed_Database::instance()->update_settings([
            'color_primary'     => $this->post_color('color_primary', '#0891b2'),
            'color_accent'      => $this->post_color('color_accent', '#e0f2fe'),
            'color_hdr_from'    => $this->post_color('color_hdr_from', '#0c4a6e'),
            'color_hdr_to'      => $this->post_color('color_hdr_to', '#0891b2'),
            'color_hdr_title'   => $this->post_color('color_hdr_title', '#ffffff'),
            'color_card_bg'     => $this->post_color('color_card_bg', '#ffffff'),
            'color_card_border' => $this->post_color('color_card_border', '#e2e8f0'),
            'border_radius'     => (string) max(0, min(24, $this->post_int('border_radius', 10))),
            'grid_columns'      => $this->post_enum('grid_columns', ['auto', '2', '3', '4'], 'auto'),
        ]);

        return ['notice' => 'Design gespeichert.', 'stab' => 'design'];
    }

    private function handle_save_digest_settings_post(): array
    {
        CMS_Feed_Database::instance()->update_settings([
            'digest_from_name'  => $this->post_text('digest_from_name'),
            'digest_from_email' => filter_var($this->post_string('digest_from_email'), FILTER_VALIDATE_EMAIL) ?: '',
            'digest_subject'    => $this->post_text('digest_subject'),
            'digest_max_items'  => (string) max(5, min(100, $this->post_int('digest_max_items', 20))),
        ]);

        return ['notice' => 'Digest-Einstellungen gespeichert.'];
    }

    private function handle_cleanup_post(): array
    {
        $days = max(7, min(365, $this->post_int('cleanup_days', 7)));
        $deleted = CMS_Feed_Database::instance()->cleanup_old_items($days);

        return ['notice' => $deleted . ' alte Beiträge gelöscht.'];
    }

    private function handle_import_catalog_post(): array
    {
        $catalogKey = $this->post_text('catalog_key');
        $catalog = CMS_Feed_Catalog::instance();
        $catData = $catalog->get_category($catalogKey);
        $importMode = $this->post_string('catalog_import_mode', 'all') === 'selected' ? 'selected' : 'all';
        $selectedFeedKeys = array_values(array_unique(array_filter(
            array_map('intval', $this->post_array('catalog_feeds')),
            static fn (int $feedKey): bool => $feedKey >= 0
        )));

        if (!$catData) {
            return ['error' => 'Ungültige Katalog-Kategorie.'];
        }

        if ($importMode === 'selected' && $selectedFeedKeys === []) {
            return ['error' => 'Bitte wähle mindestens einen Feed aus dem Katalog aus.'];
        }

        $db = CMS_Feed_Database::instance();
        $targetCatId = $this->post_int('target_category_id');
        if ($targetCatId < 1) {
            $slug = $this->sanitize_slug((string) $catData['slug']);
            $targetCatId = $db->save_category([
                'id'             => null,
                'name'           => $catData['name'],
                'slug'           => $slug,
                'description'    => $catData['description'],
                'icon'           => $catData['icon'],
                'is_public'      => 1,
                'sort_order'     => 0,
                'layout'         => 'grid',
                'items_per_page' => 20,
            ]);
        }

        $result = $catalog->import_feeds(
            $catalogKey,
            $targetCatId,
            $importMode === 'selected' ? $selectedFeedKeys : []
        );

        $message = $result['imported'] . ' Kanäle importiert';
        if ($result['skipped'] > 0) {
            $message .= ', ' . $result['skipped'] . ' übersprungen (bereits vorhanden)';
        }
        if ($importMode === 'selected') {
            $message .= ' (Teilimport aus Auswahl)';
        }
        if (!empty($result['errors'])) {
            $message .= '. Fehler: ' . implode('; ', array_slice($result['errors'], 0, 3));
        }

        return ['notice' => $message . '.'];
    }

    private function handle_bulk_delete_channels_post(): array
    {
        $ids = $this->get_bulk_ids_from_post('Kanäle');
        if (isset($ids['error'])) {
            return $ids;
        }

        $count = CMS_Feed_Database::instance()->bulk_delete_channels($ids['ids']);
        return ['notice' => $count . ' Kanal/Kanäle und zugehörige Beiträge gelöscht.'];
    }

    private function handle_bulk_activate_channels_post(): array
    {
        return $this->handle_bulk_toggle_channels_post(true);
    }

    private function handle_bulk_deactivate_channels_post(): array
    {
        return $this->handle_bulk_toggle_channels_post(false);
    }

    private function handle_bulk_fetch_channels_post(): array
    {
        $ids = $this->get_bulk_ids_from_post('Kanäle');
        if (isset($ids['error'])) {
            return $ids;
        }

        $maxImmediate = 5;
        $immediate = array_slice($ids['ids'], 0, $maxImmediate);
        $queued = array_slice($ids['ids'], $maxImmediate);
        $fetcher = CMS_Feed_RSS_Fetcher::instance();
        $totalNew = 0;
        $errors = [];

        foreach ($immediate as $channelId) {
            $fetchResult = $fetcher->fetch_channel($channelId);
            if ($fetchResult['success']) {
                $totalNew += $fetchResult['new_items'] ?? 0;
                continue;
            }

            $errors[] = $fetchResult['error'] ?? 'Unbekannt';
        }

        $queuedCount = 0;
        if ($queued !== []) {
            $queuedCount = CMS_Feed_Database::instance()->add_to_fetch_queue($queued);
        }

        $message = count($immediate) . ' Kanäle sofort abgerufen, ' . $totalNew . ' neue Beiträge.';
        if ($queuedCount > 0) {
            $message .= ' ' . $queuedCount . ' weitere Kanäle in Warteschlange (Cron).';
        }
        if ($errors !== []) {
            $message .= ' Fehler: ' . implode('; ', array_slice($errors, 0, 3));
        }

        return ['notice' => $message];
    }

    private function handle_bulk_delete_categories_post(): array
    {
        $ids = $this->get_bulk_ids_from_post('Bereiche');
        if (isset($ids['error'])) {
            return $ids;
        }

        $count = CMS_Feed_Database::instance()->bulk_delete_categories($ids['ids']);
        return ['notice' => $count . ' Bereich/Bereiche und zugehörige Daten gelöscht.'];
    }

    private function handle_item_toggle_post(string $type): array
    {
        $id = $this->post_int('id');
        if ($id <= 0) {
            return ['error' => 'Ungültige ID.'];
        }

        $db = CMS_Feed_Database::instance();
        if ($type === 'hidden') {
            $db->toggle_item_hidden($id);
            return ['notice' => 'Sichtbarkeit geändert.'];
        }

        $db->toggle_item_featured($id);
        return ['notice' => 'Hervorhebung geändert.'];
    }

    private function handle_bulk_toggle_channels_post(bool $active): array
    {
        $ids = $this->get_bulk_ids_from_post('Kanäle');
        if (isset($ids['error'])) {
            return $ids;
        }

        $count = CMS_Feed_Database::instance()->bulk_toggle_channels($ids['ids'], $active);
        return ['notice' => $count . ' Kanal/Kanäle ' . ($active ? 'aktiviert' : 'deaktiviert') . '.'];
    }

    /**
     * @return array{ids?: array<int,int>, error?: string}
     */
    private function get_bulk_ids_from_post(string $label): array
    {
        $ids = array_values(array_filter(array_map('intval', $this->post_array('bulk_ids')), static fn (int $id): bool => $id > 0));
        if ($ids === []) {
            return ['error' => 'Keine ' . $label . ' ausgewählt.'];
        }

        return ['ids' => $ids];
    }

    private function post_string(string $key, string $default = ''): string
    {
        $value = $_POST[$key] ?? $default;

        return is_scalar($value) ? (string) $value : $default;
    }

    private function post_int(string $key, int $default = 0): int
    {
        $value = $_POST[$key] ?? $default;

        return is_scalar($value) ? (int) $value : $default;
    }

    private function post_bool(string $key): bool
    {
        $value = $_POST[$key] ?? null;
        if (!is_scalar($value)) {
            return false;
        }

        return !in_array(strtolower(trim((string) $value)), ['', '0', 'false', 'off', 'no'], true);
    }

    private function post_array(string $key): array
    {
        $value = $_POST[$key] ?? [];

        return is_array($value) ? $value : [];
    }

    private function post_text(string $key, string $default = ''): string
    {
        $value = $this->post_string($key, $default);

        return function_exists('sanitize_text_field')
            ? sanitize_text_field($value)
            : trim(strip_tags($value));
    }

    private function post_color(string $key, string $default): string
    {
        $value = $this->post_string($key, $default);

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1 ? $value : $default;
    }

    /**
     * @param array<int,string> $allowed
     */
    private function post_enum(string $key, array $allowed, string $default): string
    {
        $value = $this->post_string($key, $default);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function sanitize_slug(string $slug): string
    {
        $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(trim($slug, '/'))) ?: '';
        return $slug !== 'feed' ? $slug : 'feeds';
    }

    private function sanitize_public_url(string $url): string
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        if (!in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return '';
        }

        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return '';
        }

        $host = strtolower(trim((string) $parts['host'], '[]'));
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return '';
        }

        if (filter_var($host, FILTER_VALIDATE_IP) && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return '';
        }

        return $url;
    }
}