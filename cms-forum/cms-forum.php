<?php
/**
 * Plugin Name: CMS Forum
 * Plugin URI: https://365network.de/cms-forum
 * Description: Vollwertiges Community-Forum mit Kategorien, Subforen, Threads, BBCode-Editor, Berechtigungssystem, Moderationstools und Rang-System.
 * Version: 3.0.2
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_FORUM_VERSION',    '3.0.2');
define('CMS_FORUM_DB_VERSION', '1.0.1');
define('CMS_FORUM_DIR',        dirname(__FILE__) . '/');
define('CMS_FORUM_URL',        '/plugins/cms-forum/');

/**
 * Hauptklasse des Forum-Plugins.
 *
 * Verantwortlich für das Laden aller Abhängigkeiten, das Registrieren
 * der Hooks und das Einbinden der Assets.
 */
final class CMS_Forum
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Alle Plugin-Klassen laden.
     */
    private function load_dependencies(): void
    {
        // Konfiguration
        require_once CMS_FORUM_DIR . 'config/config.php';

        // Datenbank / Installer
        require_once CMS_FORUM_DIR . 'includes/class-database.php';

        // Helpers
        require_once CMS_FORUM_DIR . 'src/Helpers/Pagination.php';
        require_once CMS_FORUM_DIR . 'src/Helpers/TimeHelper.php';
        require_once CMS_FORUM_DIR . 'src/Helpers/AvatarHelper.php';
        require_once CMS_FORUM_DIR . 'src/Helpers/SlugHelper.php';

        // Models
        require_once CMS_FORUM_DIR . 'src/Models/Category.php';
        require_once CMS_FORUM_DIR . 'src/Models/Forum.php';
        require_once CMS_FORUM_DIR . 'src/Models/Thread.php';
        require_once CMS_FORUM_DIR . 'src/Models/Post.php';
        require_once CMS_FORUM_DIR . 'src/Models/UserMeta.php';
        require_once CMS_FORUM_DIR . 'src/Models/Rank.php';
        require_once CMS_FORUM_DIR . 'src/Models/Permission.php';
        require_once CMS_FORUM_DIR . 'src/Models/Subscription.php';
        require_once CMS_FORUM_DIR . 'src/Models/Attachment.php';
        require_once CMS_FORUM_DIR . 'src/Models/Poll.php';
        require_once CMS_FORUM_DIR . 'src/Models/Like.php';
        require_once CMS_FORUM_DIR . 'src/Models/Report.php';
        require_once CMS_FORUM_DIR . 'src/Models/ModLog.php';

        // Services
        require_once CMS_FORUM_DIR . 'src/Services/BBCodeParser.php';
        require_once CMS_FORUM_DIR . 'src/Services/NotificationService.php';
        require_once CMS_FORUM_DIR . 'src/Services/SearchService.php';
        require_once CMS_FORUM_DIR . 'src/Services/PermissionService.php';
        require_once CMS_FORUM_DIR . 'src/Services/FloodControl.php';
        require_once CMS_FORUM_DIR . 'src/Services/ReadTracker.php';

        // Controllers
        require_once CMS_FORUM_DIR . 'src/Controllers/ForumController.php';
        require_once CMS_FORUM_DIR . 'src/Controllers/ThreadController.php';
        require_once CMS_FORUM_DIR . 'src/Controllers/PostController.php';
        require_once CMS_FORUM_DIR . 'src/Controllers/ModeratorController.php';
        require_once CMS_FORUM_DIR . 'src/Controllers/AdminController.php';

        // Sprache
        require_once CMS_FORUM_DIR . 'lang/de_DE.php';

        // Admin-Bereich
        require_once CMS_FORUM_DIR . 'admin/class-admin-menu.php';
        require_once CMS_FORUM_DIR . 'admin/class-admin-pages.php';
    }

    /**
     * Hooks registrieren.
     */
    private function init_hooks(): void
    {
        // System-Hooks
        \CMS\Hooks::addAction('cms_init', [$this, 'init_plugin'], 10);
        \CMS\Hooks::addAction('plugin_activated', [$this, 'on_activation'], 10);
        \CMS\Hooks::addAction('plugin_uninstalled', [$this, 'on_uninstall'], 10);

        // Admin-Menü
        \CMS\Hooks::addAction('cms_admin_menu', [$this, 'register_admin_menu'], 10);

        // Frontend-Routing
        \CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);

        // Assets
        \CMS\Hooks::addAction('head', [$this, 'enqueue_styles'], 10);
        \CMS\Hooks::addAction('body_end', [$this, 'enqueue_scripts'], 10);

        // Member-Dashboard (PluginDashboardRegistry)
        \CMS\Hooks::addAction('member_dashboard_init', [$this, 'register_member_section'], 20);

        // DSGVO
        \CMS\Hooks::addAction('dsgvo_export_data', [$this, 'dsgvo_export'], 10);
        \CMS\Hooks::addAction('dsgvo_delete_data', [$this, 'dsgvo_delete'], 10);
    }

    /**
     * Plugin-Aktivierung: Tabellen erstellen.
     */
    public function on_activation(string $slug): void
    {
        if ($slug !== 'cms-forum') {
            return;
        }
        CMS_Forum_Database::instance()->install();
    }

    /**
     * Plugin-Deinstallation: Tabellen entfernen.
     */
    public function on_uninstall(string $slug): void
    {
        if ($slug !== 'cms-forum') {
            return;
        }
        CMS_Forum_Database::instance()->uninstall();
    }

    /**
     * Initialisierung nach dem Laden aller Plugins.
     */
    public function init_plugin(): void
    {
        CMS_Forum_Database::instance()->maybe_upgrade();
    }

    /**
     * Admin-Menü registrieren.
     */
    public function register_admin_menu(): void
    {
        CMS_Forum_Admin_Menu::register();
    }

    /**
     * Frontend-Routen registrieren.
     */
    public function register_routes(): void
    {
        // Routing wird über den ForumController abgewickelt
        \CMS_Forum\Controllers\ForumController::instance()->register_routes();
    }

    /**
     * CSS-Assets laden.
     */
    public function enqueue_styles(): void
    {
        if (!$this->is_forum_public_route()) {
            return;
        }

        $cssPath = CMS_FORUM_DIR . 'assets/css/style.css';
        if (file_exists($cssPath)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_FORUM_URL . 'assets/css/style.css?v=' . filemtime($cssPath), ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
    }

    /**
     * JS-Assets laden.
     */
    public function enqueue_scripts(): void
    {
        if (!$this->is_forum_public_route()) {
            return;
        }

        $jsPath = CMS_FORUM_DIR . 'assets/js/forum.js';
        if (file_exists($jsPath)) {
            echo '<script src="' . htmlspecialchars(CMS_FORUM_URL . 'assets/js/forum.js?v=' . filemtime($jsPath), ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
        }
    }

    private function is_forum_public_route(): bool
    {
        $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH) ?: '');
        $path = '/' . trim($path, '/');

        return $path === '/forum' || str_starts_with($path, '/forum/');
    }

    /**
     * Forum-Bereich im Member-Dashboard registrieren.
     */
    public function register_member_section(\CMS\Member\PluginDashboardRegistry $registry): void
    {
        $registry->register([
            'plugin'           => 'cms-forum',
            'slug'             => 'forum',
            'label'            => 'Forum',
            'icon'             => '\xf0\x9f\x92\xac',
            'category'         => 'plugins',
            'priority'         => 50,
            'capability'       => null,
            'dashboard_widget' => [
                'title'       => 'Forum',
                'description' => 'Deine Forumaktivit\u00e4ten, Threads und Beitr\u00e4ge.',
                'color'       => '#7c3aed',
                'link_label'  => 'Zum Forum',
                'admin_url'   => '/admin/plugins/forum/dashboard',
                'admin_label' => '\xe2\x9a\x99\xef\xb8\x8f Admin',
            ],
            'render_callback' => [$this, 'render_member_widget'],
        ]);
    }

    /**
     * Forum-Inhalt im Member-Dashboard rendern.
     */
    public function render_member_widget(object $user, array $params = []): void
    {
        $userId      = (int)$user->id;
        $meta        = \CMS_Forum\Models\UserMeta::instance()->findOrCreate($userId);
        $recentPosts = \CMS_Forum\Models\Post::instance()->findByUser($userId, 0, 5);
        $unreadCount = 0; // ReadTracker hat keine gesamt-unread-Methode – Fallback
        include CMS_FORUM_DIR . 'views/member/page-overview.php';
    }

    /**
     * DSGVO: Benutzerdaten exportieren (Art. 20).
     */
    public function dsgvo_export(int $userId): array
    {
        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        $stmt = $db->prepare("SELECT id, content, created_at FROM {$p}cmsforum_posts WHERE user_id = ? ORDER BY created_at DESC");
        $posts = $stmt->execute([$userId]) ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

        return [
            'cms-forum' => [
                'label'  => 'Forum-Beiträge',
                'data'   => $posts,
            ],
        ];
    }

    /**
     * DSGVO: Benutzerdaten löschen (Art. 17).
     */
    public function dsgvo_delete(int $userId): void
    {
        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        // Beiträge anonymisieren statt löschen (Diskussionskontext erhalten)
        $stmt = $db->prepare("UPDATE {$p}cmsforum_posts SET user_id = 0, content = '[Gelöscht]' WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Threads anonymisieren
        $stmt = $db->prepare("UPDATE {$p}cmsforum_threads SET user_id = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);

        // User-Meta löschen
        $stmt = $db->prepare("DELETE FROM {$p}cmsforum_user_meta WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Abos löschen
        $stmt = $db->prepare("DELETE FROM {$p}cmsforum_subscriptions WHERE user_id = ?");
        $stmt->execute([$userId]);

        // Likes löschen
        $stmt = $db->prepare("DELETE FROM {$p}cmsforum_likes WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    public function getVersion(): string
    {
        return CMS_FORUM_VERSION;
    }

    public function getPluginDir(): string
    {
        return CMS_FORUM_DIR;
    }

    public function getPluginUrl(): string
    {
        return CMS_FORUM_URL;
    }
}

// Plugin initialisieren
CMS_Forum::instance();
