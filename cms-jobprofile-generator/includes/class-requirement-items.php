<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Anforderungs-Liste – Eigener Katalog (Requirement Items)
 *
 * Verwaltet benutzerdefinierte Anforderungs-Einträge, die im
 * Stellenanzeigen-Generator und in der Bibliothek genutzt werden.
 *
 * @since 1.3.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_RequirementItems
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    private function db(): \CMS\Database
    {
        return \CMS\Database::instance();
    }

    private function p(): string
    {
        return $this->db()->getPrefix();
    }

    // ── Lesen ─────────────────────────────────────────────────────────────────

    /**
     * Alle Einträge flach, sortiert nach Gruppe + sort_order + Titel.
     *
     * @return array<object>
     */
    public function get_all(): array
    {
        return $this->db()->get_results(
            "SELECT * FROM {$this->p()}jpg_requirement_items
             ORDER BY group_name ASC, sort_order ASC, title ASC",
            []
        ) ?: [];
    }

    /**
     * Einträge gruppiert nach group_name.
     *
     * @return array<string, array<object>>
     */
    public function get_grouped(): array
    {
        $rows    = $this->get_all();
        $grouped = [];
        foreach ($rows as $row) {
            $group             = ($row->group_name !== '') ? $row->group_name : 'Allgemein';
            $grouped[$group][] = $row;
        }
        return $grouped;
    }

    // ── Schreiben ────────────────────────────────────────────────────────────

    /**
     * Einen Eintrag anlegen oder aktualisieren.
     *
     * @param array<string, mixed> $data
     * @param int                  $id  0 = neu
     * @return int                      ID des Eintrags
     */
    public function save(array $data, int $id = 0): int
    {
        $fields = [
            'group_name'  => (string) ($data['group_name']  ?? ''),
            'title'       => (string) ($data['title']       ?? ''),
            'req_type'    => in_array($data['req_type'] ?? '', ['must','nice','optional'])
                                ? $data['req_type'] : 'must',
            'description' => (string) ($data['description'] ?? ''),
            'icon'        => (string) ($data['icon']        ?? ''),
            'sort_order'  => (int)    ($data['sort_order']  ?? 0),
        ];

        if ($id > 0) {
            $this->db()->update('jpg_requirement_items', $fields, ['id' => $id]);
            return $id;
        }

        return (int) $this->db()->insert('jpg_requirement_items', $fields);
    }

    /**
     * Alle Einträge gefiltert nach req_type.
     *
     * @return array<object>
     */
    public function get_by_type(string $type): array
    {
        return $this->db()->get_results(
            "SELECT * FROM {$this->p()}jpg_requirement_items
             WHERE req_type = ?
             ORDER BY group_name ASC, sort_order ASC, title ASC",
            [$type]
        ) ?: [];
    }

    /**
     * Einträge für den Generator: gruppiert nach group_name, inkl. req_type.
     *
     * @return array<string, array<object>>
     */
    public function get_for_generator(): array
    {
        return $this->get_grouped();
    }

    /**
     * Eintrag löschen.
     */
    public function delete(int $id): void
    {
        $this->db()->delete('jpg_requirement_items', ['id' => $id]);
    }
}
