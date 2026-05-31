<?php
/**
 * CMS Forum – Admin Threads Trait
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Forum_Page_Threads_Trait
{
    /**
     * Thread-Verwaltung rendern.
     */
    public static function render_threads(): void
    {
        self::check_access();
        self::render_admin_page('Forum-Threads', static function (): void {
            $error   = null;
            $success = null;

            // POST-Handler
            $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if ($requestMethod === 'POST' && isset($_POST['forum_action'])) {
                $forumAction = sanitize_key((string) ($_POST['forum_action'] ?? ''));
                if (!self::verify_nonce('forum_threads')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    $moderator = \CMS_Forum\Controllers\ModeratorController::instance();

                    switch ($forumAction) {
                        case 'delete_thread':
                            $result = $moderator->deleteThread((int) ($_POST['thread_id'] ?? 0));
                            $result['success'] ? $success = 'Thread gelöscht.' : $error = $result['error'];
                            break;

                        case 'lock_thread':
                            $result = $moderator->toggleThreadLock((int) ($_POST['thread_id'] ?? 0));
                            $result['success'] ? $success = 'Thread-Status geändert.' : $error = $result['error'];
                            break;

                        case 'pin_thread':
                            $result = $moderator->toggleThreadPin((int) ($_POST['thread_id'] ?? 0));
                            $result['success'] ? $success = 'Thread-Typ geändert.' : $error = $result['error'];
                            break;

                        case 'move_thread':
                            $result = $moderator->moveThread(
                                (int) ($_POST['thread_id'] ?? 0),
                                (int) ($_POST['target_forum_id'] ?? 0)
                            );
                            $result['success'] ? $success = 'Thread verschoben.' : $error = $result['error'];
                            break;

                        default:
                            $error = 'Unbekannte Aktion.';
                            break;
                    }
                }
            }

            // Filter
            $filterForum  = (int) ($_GET['forum'] ?? 0);
            $filterStatus = $_GET['status'] ?? '';
            $search       = sanitize_text_field($_GET['q'] ?? '');

            $db = self::db();
            $p  = self::prefix();

            $where  = [];
            $params = [];

            if ($filterForum > 0) {
                $where[]  = "t.forum_id = ?";
                $params[] = $filterForum;
            }

            if (in_array($filterStatus, ['open', 'closed', 'deleted'], true)) {
                $where[]  = "t.status = ?";
                $params[] = $filterStatus;
            }

            if ($search !== '') {
                $where[]  = "t.title LIKE ?";
                $params[] = '%' . $search . '%';
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            // Paginierung
            $pageParam = $_GET['paged'] ?? $_GET['page_num'] ?? 1;
            $page    = max(1, (int) $pageParam);
            $perPage = 25;
            $offset  = ($page - 1) * $perPage;

            $countStmt = $db->prepare("SELECT COUNT(*) FROM {$p}cmsforum_threads t {$whereSql}");
            $countStmt->execute($params);
            $total = (int) $countStmt->fetchColumn();
            $pages = max(1, (int) ceil($total / $perPage));

            $stmt = $db->prepare(
                "SELECT t.*, u.username, f.name AS forum_name
                 FROM {$p}cmsforum_threads t
                 LEFT JOIN {$p}users u ON u.id = t.user_id
                 LEFT JOIN {$p}cmsforum_forums f ON f.id = t.forum_id
                 {$whereSql}
                 ORDER BY t.created_at DESC
                 LIMIT ? OFFSET ?"
            );
            $stmt->execute([...$params, $perPage, $offset]);
            $threads = $stmt->fetchAll(\PDO::FETCH_OBJ);

            $forums    = \CMS_Forum\Models\Forum::instance()->findAll();
            $csrfToken = self::generate_nonce('forum_threads');

            include CMS_FORUM_DIR . 'admin/views/page-threads.php';
        });
    }
}
