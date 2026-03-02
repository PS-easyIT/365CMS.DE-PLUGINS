<?php
/**
 * Plugin Name: CMS Forum
 * Plugin URI: https://365network.de/cms-forum
 * Description: Vollwertiges Community-Forum mit Kategorien, Subforen, Threads, BBCode-Editor, Berechtigungssystem, Moderationstools und Rang-System.
 * Version: 1.0.0
 * Author: 365 Network
 * Author URI: https://365network.de
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

define('CMS_FORUM_VERSION',    '1.0.0');
define('CMS_FORUM_DB_VERSION', '1.0.0');
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

        // Member-Dashboard
        \CMS\Hooks::addAction('cms_member_dashboard', [$this, 'render_member_widget'], 20);

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
        $cssPath = CMS_FORUM_DIR . 'assets/css/style.css';
        if (file_exists($cssPath)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_FORUM_URL . 'assets/css/style.css?v=' . filemtime($cssPath)) . '">' . "\n";
        }
    }

    /**
     * JS-Assets laden.
     */
    public function enqueue_scripts(): void
    {
        $jsPath = CMS_FORUM_DIR . 'assets/js/forum.js';
        if (file_exists($jsPath)) {
            echo '<script src="' . htmlspecialchars(CMS_FORUM_URL . 'assets/js/forum.js?v=' . filemtime($jsPath)) . '" defer></script>' . "\n";
        }
    }

    /**
     * Member-Dashboard-Widget rendern.
     */
    public function render_member_widget(): void
    {
        if (!\CMS\Auth::instance()->isLoggedIn()) {
            return;
        }
        include CMS_FORUM_DIR . 'views/member/page-overview.php';
    }

    /**
     * DSGVO: Benutzerdaten exportieren (Art. 20).
     */
    public function dsgvo_export(int $userId): array
    {
        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        $posts = $db->prepare("SELECT id, content, created_at FROM {$p}cmsforum_posts WHERE user_id = ? ORDER BY created_at DESC")
                     ->execute([$userId]);
        $posts = $posts ? $posts->fetchAll(\PDO::FETCH_ASSOC) : [];

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
