<?php
/**
 * CMS Forum – Admin Dashboard Trait
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Forum_Page_Dashboard_Trait
{
    /**
     * Dashboard-Seite rendern.
     */
    public static function render_dashboard(): void
    {
        self::check_access();
        self::render_admin_page('Forum', static function (): void {
            $db = self::db();
            $p  = self::prefix();

            // Statistiken
            $stats = $db->getPdo()->query(
                "SELECT
                    (SELECT COUNT(*) FROM {$p}cmsforum_categories WHERE is_active = 1) AS categories,
                    (SELECT COUNT(*) FROM {$p}cmsforum_forums WHERE is_active = 1) AS forums,
                    (SELECT COUNT(*) FROM {$p}cmsforum_threads WHERE status != 'deleted') AS threads,
                    (SELECT COUNT(*) FROM {$p}cmsforum_posts WHERE is_deleted = 0) AS posts,
                    (SELECT COUNT(DISTINCT user_id) FROM {$p}cmsforum_user_meta) AS users,
                    (SELECT COUNT(*) FROM {$p}cmsforum_reports WHERE status = 'open') AS open_reports"
            )->fetch(\PDO::FETCH_OBJ);

            // Neueste Threads
            $recentThreads = $db->prepare(
                "SELECT t.*, u.username
                 FROM {$p}cmsforum_threads t
                 LEFT JOIN {$p}users u ON u.id = t.user_id
                 WHERE t.status != 'deleted'
                 ORDER BY t.created_at DESC
                 LIMIT 10"
            );
            $recentThreads->execute();
            $recentThreads = $recentThreads->fetchAll(\PDO::FETCH_OBJ);

            // Aktivste Benutzer (letzte 30 Tage)
            $topUsers = $db->prepare(
                "SELECT u.username, COUNT(p.id) AS post_count
                 FROM {$p}cmsforum_posts p
                 JOIN {$p}users u ON u.id = p.user_id
                 WHERE p.is_deleted = 0 AND p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                 GROUP BY p.user_id
                 ORDER BY post_count DESC
                 LIMIT 5"
            );
            $topUsers->execute();
            $topUsers = $topUsers->fetchAll(\PDO::FETCH_OBJ);

            $csrfToken = self::generate_nonce('forum_dashboard');

            include CMS_FORUM_DIR . 'admin/views/page-dashboard.php';
        });
    }
}
