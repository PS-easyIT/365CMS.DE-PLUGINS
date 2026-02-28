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

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->loadAdminMenu();
        if (class_exists('CMS\Hooks')) {
            CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
        }
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

        $db   = CMS_Feed_Database::instance();
        $sec  = CMS\Security::instance();
        $csrf = $sec->generateToken('cms_feed_admin');
        $tab  = $_GET['tab'] ?? 'dashboard';

        $this->render_list([
            'csrf' => $csrf,
            'tab'  => $tab,
        ]);
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
        $this->loadAdminMenu();
        renderAdminLayoutStart('Feeds', 'feeds');

        $db   = CMS_Feed_Database::instance();
        $sec  = \CMS\Security::instance();
        $csrf = $data['csrf'] ?? $sec->generateToken('cms_feed_admin');

        $tab = $data['tab'] ?? ($_GET['tab'] ?? 'dashboard');

        $notice = $data['notice'] ?? null;
        $error  = $data['error']  ?? null;

        // ── POST-Verarbeitung ─────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            if (!$sec->verifyToken($_POST['csrf_token'] ?? '', 'cms_feed_admin')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $result = $this->handle_post($tab);
                $notice = $result['notice'] ?? null;
                $error  = $result['error']  ?? null;
                // CSRF-Token erneuern
                $csrf = $sec->generateToken('cms_feed_admin');
            }
        }

        // ── Daten laden ───────────────────────────────────────────────
        $settings   = $db->get_settings();
        $categories = $db->get_categories();
        $channels   = $db->get_channels();
        $digests    = $db->get_digests();
        $stats      = $db->get_stats();

        // ── Tabs definieren ───────────────────────────────────────────
        $tabs = [
            'dashboard'  => '📊 Dashboard',
            'channels'   => '📡 Kanäle',
            'categories' => '📁 Bereiche',
            'items'      => '📰 Beiträge',
            'digests'    => '📧 E-Mail-Digests',
            'settings'   => '⚙️ Einstellungen',
        ];

        include CMS_FEED_PLUGIN_DIR . 'admin/views/page-admin.php';

        renderAdminLayoutEnd();
    }

    // ══════════════════════════════════════════════════════════════════════
    // POST-Handler
    // ══════════════════════════════════════════════════════════════════════

    private function handle_post(string $tab): array
    {
        $db     = CMS_Feed_Database::instance();
        $action = $_POST['action'] ?? '';

        switch ($action) {

            // ── Kanal speichern ───────────────────────────────────────
            case 'save_channel':
                $name    = sanitize_text_field($_POST['channel_name'] ?? '');
                $feedUrl = filter_var($_POST['feed_url'] ?? '', FILTER_VALIDATE_URL);
                $catId   = (int) ($_POST['category_id'] ?? 0);

                if (empty($name) || !$feedUrl || $catId < 1) {
                    return ['error' => 'Name, Feed-URL und Bereich sind erforderlich.'];
                }

                $db->save_channel([
                    'id'             => (int) ($_POST['channel_id'] ?? 0) ?: null,
                    'category_id'    => $catId,
                    'name'           => $name,
                    'feed_url'       => $feedUrl,
                    'site_url'       => filter_var($_POST['site_url'] ?? '', FILTER_VALIDATE_URL) ?: null,
                    'description'    => sanitize_text_field($_POST['channel_description'] ?? ''),
                    'is_active'      => !empty($_POST['is_active']) ? 1 : 0,
                    'fetch_interval' => max(5, min(1440, (int) ($_POST['fetch_interval'] ?? 60))),
                    'max_items'      => max(10, min(500, (int) ($_POST['max_items'] ?? 50))),
                ]);
                return ['notice' => 'Kanal gespeichert.'];

            // ── Kanal löschen ─────────────────────────────────────────
            case 'delete_channel':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $db->delete_channel($id);
                    return ['notice' => 'Kanal und zugehörige Beiträge gelöscht.'];
                }
                return ['error' => 'Ungültige Kanal-ID.'];

            // ── Bereich speichern ─────────────────────────────────────
            case 'save_category':
                $name = sanitize_text_field($_POST['cat_name'] ?? '');
                $slug = sanitize_text_field($_POST['cat_slug'] ?? '');

                if (empty($name) || empty($slug)) {
                    return ['error' => 'Name und Slug sind erforderlich.'];
                }

                $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower($slug));

                $db->save_category([
                    'id'             => (int) ($_POST['cat_id'] ?? 0) ?: null,
                    'name'           => $name,
                    'slug'           => $slug,
                    'description'    => sanitize_text_field($_POST['cat_description'] ?? ''),
                    'icon'           => mb_substr(trim($_POST['cat_icon'] ?? '📰'), 0, 10),
                    'is_public'      => !empty($_POST['cat_is_public']) ? 1 : 0,
                    'sort_order'     => (int) ($_POST['cat_sort_order'] ?? 0),
                    'layout'         => in_array($_POST['cat_layout'] ?? '', ['grid', 'list', 'magazine'], true) ? $_POST['cat_layout'] : 'grid',
                    'items_per_page' => max(4, min(100, (int) ($_POST['cat_items_per_page'] ?? 20))),
                ]);
                return ['notice' => 'Bereich gespeichert.'];

            // ── Bereich löschen ───────────────────────────────────────
            case 'delete_category':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $db->delete_category($id);
                    return ['notice' => 'Bereich und alle zugehörigen Daten gelöscht.'];
                }
                return ['error' => 'Ungültige Bereich-ID.'];

            // ── Feeds jetzt abrufen ───────────────────────────────────
            case 'fetch_now':
                $channelId = (int) ($_POST['channel_id'] ?? 0);
                $fetcher   = CMS_Feed_RSS_Fetcher::instance();
                if ($channelId > 0) {
                    $result = $fetcher->fetch_channel($channelId);
                    if ($result['success']) {
                        return ['notice' => $result['new_items'] . ' neue Beiträge importiert.'];
                    }
                    return ['error' => 'Fehler: ' . ($result['error'] ?? 'Unbekannt')];
                }
                // Alle abrufen
                $results = $fetcher->fetch_all_due();
                $total   = array_sum(array_column($results, 'new_items'));
                return ['notice' => count($results) . ' Kanäle geprüft, ' . $total . ' neue Beiträge.'];

            // ── Beitrag ausblenden/einblenden ─────────────────────────
            case 'toggle_hidden':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $db->toggle_item_hidden($id);
                    return ['notice' => 'Sichtbarkeit geändert.'];
                }
                return ['error' => 'Ungültige ID.'];

            // ── Beitrag hervorheben ───────────────────────────────────
            case 'toggle_featured':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $db->toggle_item_featured($id);
                    return ['notice' => 'Hervorhebung geändert.'];
                }
                return ['error' => 'Ungültige ID.'];

            // ── Beitrag löschen ───────────────────────────────────────
            case 'delete_item':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $db->delete_item($id);
                    return ['notice' => 'Beitrag gelöscht.'];
                }
                return ['error' => 'Ungültige ID.'];

            // ── Digest speichern ──────────────────────────────────────
            case 'save_digest':
                $name  = sanitize_text_field($_POST['digest_name'] ?? '');
                $email = filter_var($_POST['digest_email'] ?? '', FILTER_VALIDATE_EMAIL);

                if (empty($name) || !$email) {
                    return ['error' => 'Name und gültige E-Mail sind erforderlich.'];
                }

                $catIds = array_map('intval', $_POST['digest_categories'] ?? []);
                if (empty($catIds)) {
                    return ['error' => 'Mindestens ein Bereich muss gewählt werden.'];
                }

                $db->save_digest([
                    'id'           => (int) ($_POST['digest_id'] ?? 0) ?: null,
                    'name'         => $name,
                    'email'        => $email,
                    'category_ids' => $catIds,
                    'frequency'    => max(1, min(4, (int) ($_POST['digest_frequency'] ?? 1))),
                    'is_active'    => !empty($_POST['digest_is_active']) ? 1 : 0,
                ]);
                return ['notice' => 'Digest gespeichert.'];

            // ── Digest löschen ────────────────────────────────────────
            case 'delete_digest':
                $id = (int) ($_POST['id'] ?? 0);
                if ($id > 0) {
                    $db->delete_digest($id);
                    return ['notice' => 'Digest gelöscht.'];
                }
                return ['error' => 'Ungültige ID.'];

            // ── Test-Digest senden ────────────────────────────────────
            case 'test_digest':
                $id = (int) ($_POST['digest_id'] ?? 0);
                if ($id > 0) {
                    $mailer = CMS_Feed_Email_Digest::instance();
                    if ($mailer->send_test_digest($id)) {
                        return ['notice' => 'Test-Digest wurde gesendet.'];
                    }
                    return ['error' => 'Fehler beim Senden des Test-Digests.'];
                }
                return ['error' => 'Ungültige Digest-ID.'];

            // ── Einstellungen speichern ────────────────────────────────
            case 'save_settings':
                $db->update_settings([
                    'archive_title'       => sanitize_text_field($_POST['archive_title'] ?? ''),
                    'archive_description' => sanitize_text_field($_POST['archive_description'] ?? ''),
                    'archive_slug'        => preg_replace('/[^a-z0-9\-]/', '', strtolower($_POST['archive_slug'] ?? 'feeds')),
                    'per_page'            => (string) max(4, min(100, (int) ($_POST['per_page'] ?? 20))),
                    'open_in_new_tab'     => !empty($_POST['open_in_new_tab']) ? '1' : '0',
                    'show_source'         => !empty($_POST['show_source']) ? '1' : '0',
                    'show_date'           => !empty($_POST['show_date']) ? '1' : '0',
                    'show_image'          => !empty($_POST['show_image']) ? '1' : '0',
                    'show_excerpt'        => !empty($_POST['show_excerpt']) ? '1' : '0',
                    'excerpt_length'      => (string) max(50, min(500, (int) ($_POST['excerpt_length'] ?? 160))),
                ]);
                return ['notice' => 'Einstellungen gespeichert.'];

            case 'save_design':
                $db->update_settings([
                    'color_primary'     => preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color_primary'] ?? '') ? $_POST['color_primary'] : '#0891b2',
                    'color_accent'      => preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color_accent'] ?? '') ? $_POST['color_accent'] : '#e0f2fe',
                    'color_hdr_from'    => preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color_hdr_from'] ?? '') ? $_POST['color_hdr_from'] : '#0c4a6e',
                    'color_hdr_to'      => preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color_hdr_to'] ?? '') ? $_POST['color_hdr_to'] : '#0891b2',
                    'color_hdr_title'   => preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color_hdr_title'] ?? '') ? $_POST['color_hdr_title'] : '#ffffff',
                    'color_card_bg'     => preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color_card_bg'] ?? '') ? $_POST['color_card_bg'] : '#ffffff',
                    'color_card_border' => preg_match('/^#[0-9A-Fa-f]{6}$/', $_POST['color_card_border'] ?? '') ? $_POST['color_card_border'] : '#e2e8f0',
                    'border_radius'     => (string) max(0, min(24, (int) ($_POST['border_radius'] ?? 10))),
                    'grid_columns'      => in_array($_POST['grid_columns'] ?? '', ['auto', '2', '3', '4'], true) ? $_POST['grid_columns'] : 'auto',
                ]);
                return ['notice' => 'Design gespeichert.'];

            case 'save_digest_settings':
                $db->update_settings([
                    'digest_from_name'  => sanitize_text_field($_POST['digest_from_name'] ?? ''),
                    'digest_from_email' => filter_var($_POST['digest_from_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
                    'digest_subject'    => sanitize_text_field($_POST['digest_subject'] ?? ''),
                    'digest_max_items'  => (string) max(5, min(100, (int) ($_POST['digest_max_items'] ?? 20))),
                ]);
                return ['notice' => 'Digest-Einstellungen gespeichert.'];

            // ── Alte Beiträge aufräumen ────────────────────────────────
            case 'cleanup':
                $days    = max(7, min(365, (int) ($_POST['cleanup_days'] ?? 90)));
                $deleted = $db->cleanup_old_items($days);
                return ['notice' => $deleted . ' alte Beiträge gelöscht.'];
        }

        return [];
    }
}
