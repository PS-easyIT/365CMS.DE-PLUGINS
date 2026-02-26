<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skill-Matrix Bibliothek
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_SkillMatrix
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
            "SELECT * FROM {$this->p}jpg_skills WHERE id = ?",
            [$id]
        );
    }

    /** @return array<object> */
    public function get_all(): array
    {
        return $this->db->get_results(
            "SELECT * FROM {$this->p}jpg_skills ORDER BY group_name ASC, sort_order ASC",
            []
        );
    }

    /** @return array<string, array<object>> Skills gruppiert nach group_name */
    public function get_grouped(): array
    {
        $skills  = $this->get_all();
        $grouped = [];
        foreach ($skills as $skill) {
            $grouped[$skill->group_name][] = $skill;
        }
        return $grouped;
    }

    public function save(array $data, int $id = 0): int
    {
        $fields = [
            'group_name'  => trim($data['group_name'] ?? ''),
            'skill_name'  => trim($data['skill_name'] ?? ''),
            'description' => trim($data['description'] ?? ''),
            'sort_order'  => (int) ($data['sort_order'] ?? 0),
        ];

        if ($id > 0) {
            $this->db->update('jpg_skills', $fields, ['id' => $id]);
            return $id;
        }
        return $this->db->insert('jpg_skills', $fields);
    }

    public function delete(int $id): void
    {
        $this->db->delete('jpg_skills', ['id' => $id]);
        $this->db->delete('jpg_profile_skills', ['skill_id' => $id]);
    }

    /** @return array<string> Alle Gruppen-Namen */
    public function get_groups(): array
    {
        $rows = $this->db->get_results(
            "SELECT DISTINCT group_name FROM {$this->p}jpg_skills ORDER BY group_name ASC",
            []
        );
        return array_map(fn($r) => (string) $r->group_name, $rows);
    }
}
