<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Benefit-Katalog Bibliothek
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_BenefitsCatalog
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
            "SELECT * FROM {$this->p}jpg_benefits WHERE id = ?",
            [$id]
        );
    }

    /** @return array<object> */
    public function get_all(bool $activeOnly = true): array
    {
        $sql    = "SELECT * FROM {$this->p}jpg_benefits";
        $params = [];
        if ($activeOnly) {
            $sql .= ' WHERE active = 1';
        }
        $sql .= ' ORDER BY group_name ASC, sort_order ASC';
        return $this->db->get_results($sql, $params);
    }

    /** @return array<string, array<object>> */
    public function get_grouped(bool $activeOnly = true): array
    {
        $benefits = $this->get_all($activeOnly);
        $grouped  = [];
        foreach ($benefits as $b) {
            $grouped[$b->group_name][] = $b;
        }
        return $grouped;
    }

    public function save(array $data, int $id = 0): int
    {
        $fields = [
            'group_name'  => trim($data['group_name'] ?? ''),
            'title'       => trim($data['title'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'icon'        => trim($data['icon'] ?? '✓'),
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
            'active'      => isset($data['active']) ? (int) $data['active'] : 1,
        ];

        if ($id > 0) {
            $this->db->update('jpg_benefits', $fields, ['id' => $id]);
            return $id;
        }
        return $this->db->insert('jpg_benefits', $fields);
    }

    public function delete(int $id): void
    {
        $this->db->delete('jpg_benefits', ['id' => $id]);
        $this->db->delete('jpg_profile_benefits', ['benefit_id' => $id]);
    }

    /** @return array<string> */
    public function get_groups(): array
    {
        $rows = $this->db->get_results(
            "SELECT DISTINCT group_name FROM {$this->p}jpg_benefits ORDER BY group_name ASC",
            []
        );
        return array_map(fn($r) => (string) $r->group_name, $rows);
    }
}
