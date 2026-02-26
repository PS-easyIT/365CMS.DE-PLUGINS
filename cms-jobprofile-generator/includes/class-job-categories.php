<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Job-Kategorien
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_JobCategories
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
            "SELECT * FROM {$this->p}jpg_job_categories WHERE id = ?",
            [$id]
        );
    }

    /** @return array<object> */
    public function get_all(bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM {$this->p}jpg_job_categories";
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';
        return $this->db->get_results($sql, []);
    }

    public function save(array $data, int $id = 0): int
    {
        $fields = [
            'name'        => trim($data['name'] ?? ''),
            'slug'        => $this->generate_slug($data['name'] ?? '', $id),
            'description' => trim($data['description'] ?? ''),
            'color'       => preg_match('/^#[0-9a-fA-F]{6}$/', $data['color'] ?? '')
                                ? $data['color']
                                : '#3b82f6',
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'active'      => isset($data['active']) ? (int) $data['active'] : 1,
        ];

        if ($id > 0) {
            $this->db->update('jpg_job_categories', $fields, ['id' => $id]);
            return $id;
        }
        return $this->db->insert('jpg_job_categories', $fields);
    }

    public function delete(int $id): void
    {
        $this->db->delete('jpg_job_categories', ['id' => $id]);
    }

    private function generate_slug(string $name, int $excludeId): string
    {
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '-', $name)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        $base = $slug;
        $n    = 1;
        while (true) {
            $existing = $excludeId > 0
                ? $this->db->get_var(
                    "SELECT id FROM {$this->p}jpg_job_categories WHERE slug = ? AND id != ?",
                    [$slug, $excludeId]
                )
                : $this->db->get_var(
                    "SELECT id FROM {$this->p}jpg_job_categories WHERE slug = ?",
                    [$slug]
                );
            if (!$existing) {
                break;
            }
            $slug = $base . '-' . $n++;
        }
        return $slug;
    }
}
