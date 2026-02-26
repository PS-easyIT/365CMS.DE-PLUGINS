<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Textbaustein-Bibliothek
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_TextModules
{
    private static ?self $instance = null;
    private \CMS\Database $db;
    private string $p;

    public static function instance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->db = \CMS\Database::instance();
        $this->p  = $this->db->getPrefix();
    }

    public function get(int $id): ?object
    {
        return $this->db->get_row(
            "SELECT * FROM {$this->p}jpg_text_modules WHERE id = ?",
            [$id]
        );
    }

    /** @return array<object> */
    public function get_list(array $args = []): array
    {
        $category = $args['category'] ?? '';
        $search   = $args['search'] ?? '';
        $limit    = (int) ($args['limit'] ?? 50);
        $offset   = (int) ($args['offset'] ?? 0);

        $where  = ['1=1'];
        $params = [];

        if ($category !== '') {
            $where[]  = 'category = ?';
            $params[] = $category;
        }
        if ($search !== '') {
            $where[]  = '(title LIKE ? OR content LIKE ? OR tags LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql = "SELECT * FROM {$this->p}jpg_text_modules
                WHERE " . implode(' AND ', $where) . "
                ORDER BY usage_count DESC, title ASC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->get_results($sql, $params);
    }

    public function count(array $args = []): int
    {
        $category = $args['category'] ?? '';
        $where    = ['1=1'];
        $params   = [];

        if ($category !== '') {
            $where[]  = 'category = ?';
            $params[] = $category;
        }

        return (int) $this->db->get_var(
            "SELECT COUNT(*) FROM {$this->p}jpg_text_modules WHERE " . implode(' AND ', $where),
            $params
        );
    }

    public function save(array $data, int $id = 0): int
    {
        $fields = [
            'category' => trim($data['category'] ?? 'general'),
            'title'    => trim($data['title'] ?? ''),
            'content'  => $data['content'] ?? '',
            'tags'     => trim($data['tags'] ?? ''),
        ];

        if ($id > 0) {
            $this->db->update('jpg_text_modules', $fields, ['id' => $id]);
            return $id;
        }
        return $this->db->insert('jpg_text_modules', $fields);
    }

    public function delete(int $id): void
    {
        $this->db->delete('jpg_text_modules', ['id' => $id]);
    }

    public function increment_usage(int $id): void
    {
        $this->db->getPdo()->exec(
            "UPDATE {$this->p}jpg_text_modules SET usage_count = usage_count + 1 WHERE id = {$id}"
        );
    }

    /** @return array<string> */
    public function get_categories(): array
    {
        $rows = $this->db->get_results(
            "SELECT DISTINCT category FROM {$this->p}jpg_text_modules ORDER BY category ASC",
            []
        );
        return array_map(fn($r) => (string) $r->category, $rows);
    }
}
