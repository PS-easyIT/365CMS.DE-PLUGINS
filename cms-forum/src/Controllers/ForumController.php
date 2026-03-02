<?php
/**
 * CMS Forum – Forum Controller
 *
 * Steuert die Darstellung der Forum-Übersicht, Kategorien und Subforen.
 * Registriert alle Frontend-Routen.
 *
 * @package CMS_Forum\Controllers
 */

declare(strict_types=1);

namespace CMS_Forum\Controllers;

if (!defined('ABSPATH')) {
    exit;
}

use CMS_Forum\Models\Category;
use CMS_Forum\Models\Forum;
use CMS_Forum\Models\Thread;
use CMS_Forum\Models\UserMeta;
use CMS_Forum\Helpers\Pagination;
use CMS_Forum\Services\PermissionService;
use CMS_Forum\Services\ReadTracker;

final class ForumController
{
    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    /**
     * Alle Forum-Routen registrieren.
     */
    public function register_routes(): void
    {
        $router = \CMS\Router::instance();

        // Forum-Übersicht
        $router->addRoute('GET', '/forum', [$this, 'index']);

        // Forum anzeigen (Thread-Liste)
        $router->addRoute('GET', '/forum/:slug', [$this, 'showForum']);

        // Neuen Thread erstellen
        $router->addRoute('GET', '/forum/:slug/new-thread', [$this, 'newThread']);
        $router->addRoute('POST', '/forum/:slug/new-thread', [$this, 'newThread']);

        // Thread anzeigen
        $router->addRoute('GET', '/forum/thread/:id', [$this, 'showThread']);
        $router->addRoute('POST', '/forum/thread/:id', [$this, 'showThread']);

        // Beitrag bearbeiten
        $router->addRoute('GET', '/forum/post/:id/edit', [$this, 'editPost']);
        $router->addRoute('POST', '/forum/post/:id/edit', [$this, 'editPost']);

        // Suche
        $router->addRoute('GET', '/forum/search', [$this, 'search']);

        // Benutzerprofil
        $router->addRoute('GET', '/forum/user/:id', [$this, 'userProfile']);

        // AJAX-Endpunkte
        $router->addRoute('POST', '/forum/api/like', [$this, 'ajaxLike']);
        $router->addRoute('POST', '/forum/api/subscribe', [$this, 'ajaxSubscribe']);
        $router->addRoute('POST', '/forum/api/report', [$this, 'ajaxReport']);
        $router->addRoute('POST', '/forum/api/poll-vote', [$this, 'ajaxPollVote']);
    }

    /**
     * Forum-Übersicht: Alle Kategorien mit Foren.
     */
    public function index(): void
    {
        $categories = Category::instance()->findAllActive();
        $forums     = Forum::instance()->findAll();

        // Foren nach Kategorie gruppieren
        $grouped = [];
        foreach ($forums as $forum) {
            $catId = (int) $forum->category_id;
            if (!isset($grouped[$catId])) {
                $grouped[$catId] = [];
            }
            // Nur Top-Level-Foren in der Übersicht
            if ((int) $forum->parent_id === 0) {
                $grouped[$catId][] = $forum;
            }
        }

        // Statistiken
        $stats = $this->getForumStats();

        $viewData = [
            'categories' => $categories,
            'grouped'    => $grouped,
            'stats'      => $stats,
            'pageTitle'  => 'Forum',
        ];

        $this->render('forum-index', $viewData);
    }

    /**
     * Einzelnes Forum: Thread-Liste.
     */
    public function showForum(string $slug = ''): void
    {
        $forum = Forum::instance()->findBySlug($slug);
        if (!$forum) {
            $this->render404();
            return;
        }

        // Berechtigung prüfen
        if (!PermissionService::instance()->canRead((int) $forum->id)) {
            $this->renderForbidden();
            return;
        }

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = $this->getSetting('threads_per_page', 20);
        $total   = Thread::instance()->countByForum((int) $forum->id);
        $pag     = new Pagination($total, $page, $perPage);

        $threads = Thread::instance()->findByForum((int) $forum->id, $pag->offset, $pag->perPage);

        // Gelesen-Status
        if (\CMS\Auth::instance()->isLoggedIn()) {
            $threads = ReadTracker::instance()->enrichThreads(
                \CMS\Auth::instance()->getUserId(),
                $threads
            );
        }

        // Subforen
        $subforums = Forum::instance()->findSubforums((int) $forum->id);

        $viewData = [
            'forum'     => $forum,
            'threads'   => $threads,
            'subforums' => $subforums,
            'pagination' => $pag,
            'pageTitle' => htmlspecialchars($forum->name),
        ];

        $this->render('forum-show', $viewData);
    }

    /**
     * Neuen Thread erstellen.
     */
    public function newThread(string $slug = ''): void
    {
        $forum = Forum::instance()->findBySlug($slug);
        if (!$forum) {
            $this->render404();
            return;
        }

        $auth = \CMS\Auth::instance();
        if (!$auth->isLoggedIn()) {
            header('Location: ' . SITE_URL . '/login');
            exit;
        }

        if (!PermissionService::instance()->canCreateThread((int) $forum->id)) {
            $this->renderForbidden();
            return;
        }

        // Delegiere an ThreadController
        ThreadController::instance()->create((int) $forum->id, $forum);
    }

    /**
     * Thread anzeigen.
     */
    public function showThread(string $id = ''): void
    {
        ThreadController::instance()->show((int) $id);
    }

    /**
     * Beitrag bearbeiten.
     */
    public function editPost(string $id = ''): void
    {
        PostController::instance()->edit((int) $id);
    }

    /**
     * Suche.
     */
    public function search(): void
    {
        $query = trim($_GET['q'] ?? '');
        $filters = [
            'forum_id'  => (int) ($_GET['forum'] ?? 0) ?: null,
            'user_id'   => (int) ($_GET['user'] ?? 0) ?: null,
            'date_from' => $_GET['from'] ?? null,
            'date_to'   => $_GET['to'] ?? null,
        ];
        $filters = array_filter($filters);

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $results = ['threads' => [], 'total' => 0];
        if ($query !== '') {
            $results = \CMS_Forum\Services\SearchService::instance()->search($query, $filters, $offset, $perPage);
        }

        $pag = new Pagination($results['total'], $page, $perPage);

        $viewData = [
            'query'      => $query,
            'filters'    => $filters,
            'threads'    => $results['threads'],
            'pagination' => $pag,
            'pageTitle'  => 'Suche' . ($query ? ': ' . htmlspecialchars($query) : ''),
        ];

        $this->render('forum-search', $viewData);
    }

    /**
     * Benutzerprofil.
     */
    public function userProfile(string $id = ''): void
    {
        $userId = (int) $id;
        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        $stmt = $db->prepare("SELECT id, username, created_at FROM {$p}users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_OBJ);

        if (!$user) {
            $this->render404();
            return;
        }

        $meta = UserMeta::instance()->findOrCreate($userId);

        $viewData = [
            'user'      => $user,
            'meta'      => $meta,
            'pageTitle' => htmlspecialchars($user->username),
        ];

        $this->render('forum-profile', $viewData);
    }

    // ── AJAX-Endpunkte ──────────────────────────────────────────────

    /**
     * Like togglen (AJAX).
     */
    public function ajaxLike(): void
    {
        PostController::instance()->toggleLike();
    }

    /**
     * Abo togglen (AJAX).
     */
    public function ajaxSubscribe(): void
    {
        ThreadController::instance()->toggleSubscription();
    }

    /**
     * Beitrag melden (AJAX).
     */
    public function ajaxReport(): void
    {
        PostController::instance()->report();
    }

    /**
     * Umfrage abstimmen (AJAX).
     */
    public function ajaxPollVote(): void
    {
        ThreadController::instance()->pollVote();
    }

    // ── Private Helfer ──────────────────────────────────────────────

    /**
     * Globale Forumstatistiken.
     */
    private function getForumStats(): object
    {
        $db = \CMS\Database::instance();
        $p  = $db->prefix();

        $stmt = $db->getPdo()->query(
            "SELECT
                (SELECT COUNT(*) FROM {$p}cmsforum_threads WHERE status != 'deleted') AS total_threads,
                (SELECT COUNT(*) FROM {$p}cmsforum_posts WHERE is_deleted = 0) AS total_posts,
                (SELECT COUNT(DISTINCT user_id) FROM {$p}cmsforum_posts WHERE is_deleted = 0) AS total_users"
        );

        return $stmt->fetch(\PDO::FETCH_OBJ) ?: (object) ['total_threads' => 0, 'total_posts' => 0, 'total_users' => 0];
    }

    /**
     * Plugin-Einstellung auslesen.
     */
    private function getSetting(string $key, int $default): int
    {
        try {
            $db = \CMS\Database::instance();
            $p  = $db->prefix();
            $stmt = $db->prepare("SELECT setting_value FROM {$p}cmsforum_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? (int) $val : $default;
        } catch (\PDOException) {
            return $default;
        }
    }

    /**
     * View rendern.
     */
    private function render(string $viewName, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = CMS_FORUM_DIR . "views/frontend/{$viewName}.php";

        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            echo '<!-- Forum View not found: ' . htmlspecialchars($viewName) . ' -->';
        }
    }

    /**
     * 404-Fehlerseite.
     */
    private function render404(): void
    {
        http_response_code(404);
        \CMS\ThemeManager::instance()->render('404');
    }

    /**
     * 403-Fehlerseite.
     */
    private function renderForbidden(): void
    {
        http_response_code(403);
        echo '<div class="cmsforum-error"><h2>Zugriff verweigert</h2><p>Du hast keine Berechtigung, dieses Forum zu sehen.</p></div>';
    }
}
