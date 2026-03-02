<?php
/**
 * CMS Forum – Admin Users Trait
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Forum_Page_Users_Trait
{
    /**
     * Benutzer-Verwaltung rendern.
     */
    public static function render_users(): void
    {
        self::check_access();

        $error   = null;
        $success = null;

        // POST-Handler
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forum_action'])) {
            if (!self::verify_nonce('forum_users')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $admin = \CMS_Forum\Controllers\AdminController::instance();

                switch ($_POST['forum_action']) {
                    case 'ban_user':
                        $result = $admin->banUser(
                            (int) ($_POST['user_id'] ?? 0),
                            sanitize_text_field($_POST['ban_reason'] ?? ''),
                            !empty($_POST['ban_expires']) ? $_POST['ban_expires'] : null
                        );
                        $result['success'] ? $success = 'Benutzer gesperrt.' : $error = $result['error'];
                        break;

                    case 'unban_user':
                        $result = $admin->unbanUser((int) ($_POST['user_id'] ?? 0));
                        $result['success'] ? $success = 'Benutzer entsperrt.' : $error = $result['error'];
                        break;
                }
            }
        }

        // Benutzer-Liste mit Forum-Meta
        $db = self::db();
        $p  = self::prefix();

        $search = sanitize_text_field($_GET['q'] ?? '');
        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 25;
        $offset  = ($page - 1) * $perPage;

        $where  = [];
        $params = [];

        if ($search !== '') {
            $where[]  = "u.username LIKE ?";
            $params[] = '%' . $search . '%';
        }

        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM {$p}cmsforum_user_meta um JOIN {$p}users u ON u.id = um.user_id {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));

        $stmt = $db->prepare(
            "SELECT um.*, u.username, u.email, r.name AS rank_title
             FROM {$p}cmsforum_user_meta um
             JOIN {$p}users u ON u.id = um.user_id
             LEFT JOIN {$p}cmsforum_ranks r ON r.id = um.rank_id
             {$whereSql}
             ORDER BY um.post_count DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute([...$params, $perPage, $offset]);
        $users = $stmt->fetchAll(\PDO::FETCH_OBJ);

        $csrfToken = self::generate_nonce('forum_users');

        include CMS_FORUM_DIR . 'admin/views/page-users.php';
    }
}
