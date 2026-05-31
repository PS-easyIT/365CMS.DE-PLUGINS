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
use CMS_Forum\Helpers\PublicI18n;
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
        $this->addLocalizedRoute($router, 'GET', '/forum', [$this, 'index']);

        // Forum anzeigen (Thread-Liste)
        $this->addLocalizedRoute($router, 'GET', '/forum/:slug', [$this, 'showForum']);

        // Neuen Thread erstellen
        $this->addLocalizedRoute($router, 'GET', '/forum/:slug/new-thread', [$this, 'newThread']);
        $this->addLocalizedRoute($router, 'POST', '/forum/:slug/new-thread', [$this, 'newThread']);

        // Thread anzeigen
        $this->addLocalizedRoute($router, 'GET', '/forum/thread/:id', [$this, 'showThread']);
        $this->addLocalizedRoute($router, 'POST', '/forum/thread/:id', [$this, 'showThread']);

        // Beitrag bearbeiten
        $this->addLocalizedRoute($router, 'GET', '/forum/post/:id/edit', [$this, 'editPost']);
        $this->addLocalizedRoute($router, 'POST', '/forum/post/:id/edit', [$this, 'editPost']);

        // Suche
        $this->addLocalizedRoute($router, 'GET', '/forum/search', [$this, 'search']);

        // Benutzerprofil
        $this->addLocalizedRoute($router, 'GET', '/forum/user/:id', [$this, 'userProfile']);

        // AJAX-Endpunkte
        $this->addLocalizedRoute($router, 'POST', '/forum/api/like', [$this, 'ajaxLike']);
        $this->addLocalizedRoute($router, 'POST', '/forum/api/subscribe', [$this, 'ajaxSubscribe']);
        $this->addLocalizedRoute($router, 'POST', '/forum/api/report', [$this, 'ajaxReport']);
        $this->addLocalizedRoute($router, 'POST', '/forum/api/poll-vote', [$this, 'ajaxPollVote']);
        $this->addLocalizedRoute($router, 'GET', '/forum/api/similar-threads', [$this, 'ajaxSimilarThreads']);
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
            'pageTitle'  => PublicI18n::t('forum', 'Forum'),
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

        $page    = max(1, min(999, (int) ($_GET['page'] ?? 1)));
        $perPage = max(1, min(100, $this->getSetting('threads_per_page', 20)));
        $total   = Thread::instance()->countByForum((int) $forum->id);
        $pag     = new Pagination($total, $page, $perPage);

        $threads = Thread::instance()->findByForum((int) $forum->id, $pag->offset, $pag->perPage);

        // Gelesen-Status
        if (\CMS\Auth::instance()->isLoggedIn()) {
            $threads = ReadTracker::instance()->enrichThreads(
                (int)\CMS\Auth::instance()->currentUser()->id,
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
            'pageTitle' => htmlspecialchars((string) $forum->name, ENT_QUOTES, 'UTF-8'),
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
            header('Location: ' . rtrim((string) SITE_URL, '/') . PublicI18n::loginPath(), true, 303);
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
        $query = mb_substr(trim(strip_tags((string) ($_GET['q'] ?? ''))), 0, 120);
        $filters = [
            'forum_id'  => (int) ($_GET['forum'] ?? 0) ?: null,
            'user_id'   => (int) ($_GET['user'] ?? 0) ?: null,
            'date_from' => $this->sanitizeDate((string) ($_GET['from'] ?? '')),
            'date_to'   => $this->sanitizeDate((string) ($_GET['to'] ?? '')),
        ];
        $filters = array_filter($filters);

        $page    = max(1, min(999, (int) ($_GET['page'] ?? 1)));
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
            'pageTitle'  => PublicI18n::t('search', 'Suche') . ($query ? ': ' . htmlspecialchars($query, ENT_QUOTES, 'UTF-8') : ''),
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
            'pageTitle' => htmlspecialchars((string) $user->username, ENT_QUOTES, 'UTF-8'),
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

    /**
     * Ähnliche Threads für den Composer (AJAX).
     */
    public function ajaxSimilarThreads(): void
    {
        ThreadController::instance()->similarThreads();
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
     * View rendern – in Theme-Header/-Footer eingebettet.
     */
    private function render(string $viewName, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = CMS_FORUM_DIR . "views/frontend/{$viewName}.php";

        if (file_exists($viewFile)) {
            \CMS\ThemeManager::instance()->getHeader();
            include $viewFile;
            \CMS\ThemeManager::instance()->getFooter();
        } else {
            \CMS\ThemeManager::instance()->getHeader();
            echo '<!-- Forum View not found: ' . htmlspecialchars($viewName) . ' -->';
            \CMS\ThemeManager::instance()->getFooter();
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
        \CMS\ThemeManager::instance()->getHeader();
        echo '<main class="cmsforum"><div class="cmsforum-error"><h2>'
            . htmlspecialchars(PublicI18n::t('error.forbidden', 'Zugriff verweigert.'), ENT_QUOTES, 'UTF-8')
            . '</h2><p>'
            . htmlspecialchars(PublicI18n::t('error.no_permission', 'Du hast keine Berechtigung für diese Aktion.'), ENT_QUOTES, 'UTF-8')
            . '</p></div></main>';
        \CMS\ThemeManager::instance()->getFooter();
    }

    private function addLocalizedRoute(object $router, string $method, string $path, array $handler): void
    {
        $router->addRoute($method, $path, $handler);
        if (!str_starts_with($path, '/en/')) {
            $router->addRoute($method, '/en' . $path, $handler);
        }
    }

    private function sanitizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));
        return checkdate($month, $day, $year) ? sprintf('%04d-%02d-%02d', $year, $month, $day) : null;
    }
}
