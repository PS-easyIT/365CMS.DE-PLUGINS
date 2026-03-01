<?php
/**
 * CMS Forum – Search Service
 *
 * Volltextsuche mit Filteroptionen über Threads und Beiträge.
 *
 * @package CMS_Forum\Services
 */

declare(strict_types=1);

namespace CMS_Forum\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class SearchService
{
    private const int MAX_RESULTS = 100;

    private static ?self $instance = null;

    public static function instance(): static
    {
        return static::$instance ??= new static();
    }

    private function __construct() {}

    private function db(): \CMS\Database
    {
        return \CMS\Database::instance();
    }

    /**
     * Suche in Threads und Beiträgen.
     *
     * @param array{forum_id?: int, user_id?: int, date_from?: string, date_to?: string} $filters
     * @return array{threads: array, total: int}
     */
    public function search(string $query, array $filters = [], int $offset = 0, int $limit = 20): array
    {
        $p    = $this->db()->prefix();
        $term = '%' . trim($query) . '%';

        $where  = ["t.status != 'deleted'", "po.is_deleted = 0"];
        $params = [];

        // Suche in Titel und Inhalt
        $where[]  = "(t.title LIKE ? OR po.content LIKE ?)";
        $params[] = $term;
        $params[] = $term;

        // Filter: Forum
        if (!empty($filters['forum_id'])) {
            $where[]  = "t.forum_id = ?";
            $params[] = (int) $filters['forum_id'];
        }

        // Filter: Benutzer
        if (!empty($filters['user_id'])) {
            $where[]  = "po.user_id = ?";
            $params[] = (int) $filters['user_id'];
        }

        // Filter: Datum von
        if (!empty($filters['date_from'])) {
            $where[]  = "po.created_at >= ?";
            $params[] = $filters['date_from'] . ' 00:00:00';
        }

        // Filter: Datum bis
        if (!empty($filters['date_to'])) {
            $where[]  = "po.created_at <= ?";
            $params[] = $filters['date_to'] . ' 23:59:59';
        }

        $whereSql = implode(' AND ', $where);

        // Gesamtanzahl ermitteln
        $countSql = "SELECT COUNT(DISTINCT t.id)
                     FROM {$p}cmsforum_threads t
                     JOIN {$p}cmsforum_posts po ON po.thread_id = t.id
                     WHERE {$whereSql}";
        $stmt = $this->db()->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // Ergebnisse laden
        $limit = min($limit, self::MAX_RESULTS);
        $sql = "SELECT DISTINCT t.*, u.username, f.name AS forum_name, f.slug AS forum_slug,
                       (SELECT COUNT(*) FROM {$p}cmsforum_posts WHERE thread_id = t.id AND is_deleted = 0) AS post_count
                FROM {$p}cmsforum_threads t
                JOIN {$p}cmsforum_posts po ON po.thread_id = t.id
                LEFT JOIN {$p}users u ON u.id = t.user_id
                LEFT JOIN {$p}cmsforum_forums f ON f.id = t.forum_id
                WHERE {$whereSql}
                ORDER BY t.last_post_at DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return [
            'threads' => $stmt->fetchAll(\PDO::FETCH_OBJ),
            'total'   => $total,
        ];
    }
}
