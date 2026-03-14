<?php
/**
 * CMS Forum – Admin Reports Trait
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Forum_Page_Reports_Trait
{
    /**
     * Meldungen-Verwaltung rendern.
     */
    public static function render_reports(): void
    {
        self::check_access();
        self::render_admin_page('Forum-Meldungen', static function (): void {
            $error   = null;
            $success = null;

            // POST-Handler
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forum_action'])) {
                if (!self::verify_nonce('forum_reports')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    $moderator = \CMS_Forum\Controllers\ModeratorController::instance();

                    switch ($_POST['forum_action']) {
                        case 'resolve_report':
                            $result = $moderator->resolveReport(
                                (int) ($_POST['report_id'] ?? 0),
                                sanitize_text_field($_POST['resolution'] ?? 'dismissed')
                            );
                            $result['success'] ? $success = 'Meldung bearbeitet.' : $error = $result['error'];
                            break;

                        case 'delete_reported_post':
                            $reportId = (int) ($_POST['report_id'] ?? 0);
                            $postId   = (int) ($_POST['post_id'] ?? 0);
                            if ($postId > 0) {
                                $moderator->deletePost($postId);
                            }
                            if ($reportId > 0) {
                                $moderator->resolveReport($reportId, 'Post gelöscht');
                            }
                            $success = 'Beitrag gelöscht und Meldung geschlossen.';
                            break;
                    }
                }
            }

            // Filter
            $status = ($_GET['status'] ?? 'open');
            $db     = self::db();
            $p      = self::prefix();

            $where  = [];
            $params = [];

            if (in_array($status, ['open', 'resolved'], true)) {
                $where[]  = "r.status = ?";
                $params[] = $status;
            }

            $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = $db->prepare(
                "SELECT r.*, u.username AS reporter_name,
                        p.content AS post_content, p.thread_id,
                        t.title AS thread_title,
                        ru.username AS resolved_by_name
                 FROM {$p}cmsforum_reports r
                 LEFT JOIN {$p}users u ON u.id = r.user_id
                 LEFT JOIN {$p}cmsforum_posts p ON p.id = r.post_id
                 LEFT JOIN {$p}cmsforum_threads t ON t.id = p.thread_id
                 LEFT JOIN {$p}users ru ON ru.id = r.handled_by
                 {$whereSql}
                 ORDER BY r.created_at DESC
                 LIMIT 50"
            );
            $stmt->execute($params);
            $reports = $stmt->fetchAll(\PDO::FETCH_OBJ);

            $openCount = \CMS_Forum\Models\Report::instance()->countOpen();
            $csrfToken = self::generate_nonce('forum_reports');

            include CMS_FORUM_DIR . 'admin/views/page-reports.php';
        });
    }
}
