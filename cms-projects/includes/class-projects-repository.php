<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Projects_Repository
{
    private \CMS\Database $db;
    private string $projectsTable = 'projects_projects';
    private string $boardsTable = 'projects_boards';
    private string $widgetsTable = 'projects_widgets';
    private string $tasksTable = 'projects_tasks';
    private string $auditLogTable = 'projects_audit_logs';
    private string $projectsTableFull;
    private string $boardsTableFull;
    private string $widgetsTableFull;
    private string $tasksTableFull;
    private string $auditLogTableFull;

    public function __construct()
    {
        $this->db = \CMS\Database::instance();
        $prefix = $this->sanitizeSqlIdentifier((string) $this->db->getPrefix());
        $this->projectsTableFull = $this->sanitizeSqlIdentifier($prefix . $this->projectsTable);
        $this->boardsTableFull = $this->sanitizeSqlIdentifier($prefix . $this->boardsTable);
        $this->widgetsTableFull = $this->sanitizeSqlIdentifier($prefix . $this->widgetsTable);
        $this->tasksTableFull = $this->sanitizeSqlIdentifier($prefix . $this->tasksTable);
        $this->auditLogTableFull = $this->sanitizeSqlIdentifier($prefix . $this->auditLogTable);
    }

    public function ensureTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->projectsTableFull}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(190) NOT NULL,
            `slug` VARCHAR(120) NOT NULL,
            `summary` TEXT DEFAULT NULL,
            `description` LONGTEXT DEFAULT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'active',
            `visibility` VARCHAR(20) NOT NULL DEFAULT 'member',
            `owner_name` VARCHAR(190) NOT NULL DEFAULT '',
            `accent_color` VARCHAR(20) NOT NULL DEFAULT '#2563eb',
            `member_headline` VARCHAR(190) NOT NULL DEFAULT '',
            `public_headline` VARCHAR(190) NOT NULL DEFAULT '',
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_slug` (`slug`),
            KEY `idx_status_visibility` (`status`, `visibility`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->boardsTableFull}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `project_id` INT UNSIGNED NOT NULL,
            `board_type` VARCHAR(30) NOT NULL,
            `title` VARCHAR(190) NOT NULL,
            `description` TEXT DEFAULT NULL,
            `position` INT UNSIGNED NOT NULL DEFAULT 0,
            `is_public` TINYINT(1) NOT NULL DEFAULT 1,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `payload` LONGTEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_project_position` (`project_id`, `position`),
            KEY `idx_project_public` (`project_id`, `is_public`, `is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->widgetsTableFull}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `project_id` INT UNSIGNED NOT NULL,
            `scope` VARCHAR(20) NOT NULL DEFAULT 'both',
            `widget_type` VARCHAR(30) NOT NULL DEFAULT 'text',
            `title` VARCHAR(190) NOT NULL,
            `content` LONGTEXT DEFAULT NULL,
            `position` INT UNSIGNED NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `payload` LONGTEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_project_scope` (`project_id`, `scope`, `is_active`),
            KEY `idx_project_widget_position` (`project_id`, `position`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->tasksTableFull}` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `project_id` INT UNSIGNED NOT NULL,
            `board_id` INT UNSIGNED NOT NULL,
            `column_key` VARCHAR(80) NOT NULL,
            `title` VARCHAR(190) NOT NULL,
            `description` LONGTEXT DEFAULT NULL,
            `priority` VARCHAR(20) NOT NULL DEFAULT 'medium',
            `assignee_name` VARCHAR(190) NOT NULL DEFAULT '',
            `due_date` DATE DEFAULT NULL,
            `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
            `is_public` TINYINT(1) NOT NULL DEFAULT 0,
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `payload` LONGTEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_project_board_column` (`project_id`, `board_id`, `column_key`),
            KEY `idx_board_public_active` (`board_id`, `is_public`, `is_active`),
            KEY `idx_project_sort` (`project_id`, `sort_order`, `id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS `{$this->auditLogTableFull}` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `actor_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `actor_name` VARCHAR(190) NOT NULL DEFAULT '',
            `action` VARCHAR(80) NOT NULL,
            `entity_type` VARCHAR(50) NOT NULL,
            `entity_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `project_id` INT UNSIGNED NOT NULL DEFAULT 0,
            `result` VARCHAR(20) NOT NULL DEFAULT 'success',
            `context_json` LONGTEXT DEFAULT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_project_result` (`project_id`, `result`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function getProjects(): array
    {
        $rows = $this->db->get_results("SELECT * FROM `{$this->projectsTableFull}` ORDER BY updated_at DESC, id DESC") ?: [];
        return array_map([$this, 'mapProjectRow'], $rows);
    }

    public function getPublicProjects(): array
    {
        $rows = $this->db->get_results(
            "SELECT * FROM `{$this->projectsTableFull}` WHERE status = ? AND visibility = ? ORDER BY updated_at DESC, id DESC",
            ['active', 'public']
        ) ?: [];

        return array_map([$this, 'mapProjectRow'], $rows);
    }

    public function getMemberProjects(): array
    {
        $rows = $this->db->get_results(
            "SELECT * FROM `{$this->projectsTableFull}` WHERE status IN (?, ?) AND visibility IN (?, ?) ORDER BY updated_at DESC, id DESC",
            ['active', 'paused', 'member', 'public']
        ) ?: [];

        return array_map([$this, 'mapProjectRow'], $rows);
    }

    public function findProjectById(int $id): ?array
    {
        $row = $this->db->get_row("SELECT * FROM `{$this->projectsTableFull}` WHERE id = ? LIMIT 1", [$id]);
        return $row ? $this->mapProjectRow($row) : null;
    }

    public function findProjectBySlug(string $slug): ?array
    {
        $row = $this->db->get_row("SELECT * FROM `{$this->projectsTableFull}` WHERE slug = ? LIMIT 1", [$slug]);
        return $row ? $this->mapProjectRow($row) : null;
    }

    public function saveProject(array $data, ?int $id = null): int|false
    {
        $payload = [
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'summary' => (string) ($data['summary'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'status' => (string) ($data['status'] ?? 'active'),
            'visibility' => (string) ($data['visibility'] ?? 'member'),
            'owner_name' => (string) ($data['owner_name'] ?? ''),
            'accent_color' => (string) ($data['accent_color'] ?? '#2563eb'),
            'member_headline' => (string) ($data['member_headline'] ?? ''),
            'public_headline' => (string) ($data['public_headline'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id === null) {
            $payload['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert($this->projectsTable, $payload);
        }

        $updated = $this->db->update($this->projectsTable, $payload, ['id' => $id]);
        return $updated ? $id : false;
    }

    public function getBoardsByProject(int $projectId, bool $includeInactive = true, ?bool $publicOnly = null): array
    {
        $sql = "SELECT * FROM `{$this->boardsTableFull}` WHERE project_id = ?";
        $params = [$projectId];

        if (!$includeInactive) {
            $sql .= ' AND is_active = 1';
        }

        if ($publicOnly === true) {
            $sql .= ' AND is_public = 1';
        }

        $sql .= ' ORDER BY position ASC, id ASC';
        $rows = $this->db->get_results($sql, $params) ?: [];
        return array_map([$this, 'mapBoardRow'], $rows);
    }

    public function saveBoard(array $data, ?int $id = null): int|false
    {
        $payload = [
            'project_id' => (int) ($data['project_id'] ?? 0),
            'board_type' => (string) ($data['board_type'] ?? 'kanban'),
            'title' => (string) ($data['title'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'position' => (int) ($data['position'] ?? 0),
            'is_public' => !empty($data['is_public']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'payload' => (string) ($data['payload'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id === null) {
            $payload['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert($this->boardsTable, $payload);
        }

        $updated = $this->db->update($this->boardsTable, $payload, ['id' => $id]);
        return $updated ? $id : false;
    }

    public function getWidgetsByProject(int $projectId, string $scope = 'all', bool $includeInactive = true): array
    {
        $sql = "SELECT * FROM `{$this->widgetsTableFull}` WHERE project_id = ?";
        $params = [$projectId];

        if (!$includeInactive) {
            $sql .= ' AND is_active = 1';
        }

        if ($scope === 'public') {
            $sql .= " AND scope IN ('public', 'both')";
        } elseif ($scope === 'member') {
            $sql .= " AND scope IN ('member', 'both')";
        }

        $sql .= ' ORDER BY position ASC, id ASC';
        $rows = $this->db->get_results($sql, $params) ?: [];
        return array_map([$this, 'mapWidgetRow'], $rows);
    }

    public function saveWidget(array $data, ?int $id = null): int|false
    {
        $payload = [
            'project_id' => (int) ($data['project_id'] ?? 0),
            'scope' => (string) ($data['scope'] ?? 'both'),
            'widget_type' => (string) ($data['widget_type'] ?? 'text'),
            'title' => (string) ($data['title'] ?? ''),
            'content' => (string) ($data['content'] ?? ''),
            'position' => (int) ($data['position'] ?? 0),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'payload' => (string) ($data['payload'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id === null) {
            $payload['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert($this->widgetsTable, $payload);
        }

        $updated = $this->db->update($this->widgetsTable, $payload, ['id' => $id]);
        return $updated ? $id : false;
    }

    public function getTasksByProject(int $projectId, string $scope = 'admin', bool $includeInactive = true): array
    {
        $sql = "SELECT * FROM `{$this->tasksTableFull}` WHERE project_id = ?";
        $params = [$projectId];

        if (!$includeInactive) {
            $sql .= ' AND is_active = 1';
        }

        if ($scope === 'public') {
            $sql .= ' AND is_public = 1';
        }

        $sql .= ' ORDER BY board_id ASC, sort_order ASC, id ASC';
        $rows = $this->db->get_results($sql, $params) ?: [];
        return array_map([$this, 'mapTaskRow'], $rows);
    }

    public function getTasksByBoard(int $boardId, string $scope = 'admin', bool $includeInactive = true): array
    {
        $sql = "SELECT * FROM `{$this->tasksTableFull}` WHERE board_id = ?";
        $params = [$boardId];

        if (!$includeInactive) {
            $sql .= ' AND is_active = 1';
        }

        if ($scope === 'public') {
            $sql .= ' AND is_public = 1';
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';
        $rows = $this->db->get_results($sql, $params) ?: [];
        return array_map([$this, 'mapTaskRow'], $rows);
    }

    public function findTaskById(int $id): ?array
    {
        $row = $this->db->get_row("SELECT * FROM `{$this->tasksTableFull}` WHERE id = ? LIMIT 1", [$id]);
        return $row ? $this->mapTaskRow($row) : null;
    }

    public function saveTask(array $data, ?int $id = null): int|false
    {
        $payload = [
            'project_id' => (int) ($data['project_id'] ?? 0),
            'board_id' => (int) ($data['board_id'] ?? 0),
            'column_key' => (string) ($data['column_key'] ?? ''),
            'title' => (string) ($data['title'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'priority' => (string) ($data['priority'] ?? 'medium'),
            'assignee_name' => (string) ($data['assignee_name'] ?? ''),
            'due_date' => (string) ($data['due_date'] ?? '') !== '' ? (string) ($data['due_date'] ?? '') : null,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_public' => !empty($data['is_public']) ? 1 : 0,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'payload' => (string) ($data['payload'] ?? ''),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($id === null) {
            $payload['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert($this->tasksTable, $payload);
        }

        $updated = $this->db->update($this->tasksTable, $payload, ['id' => $id]);
        return $updated ? $id : false;
    }

    public function updateTaskPosition(int $id, int $boardId, string $columnKey, int $sortOrder): bool
    {
        $updated = $this->db->update($this->tasksTable, [
            'board_id' => $boardId,
            'column_key' => $columnKey,
            'sort_order' => max(0, $sortOrder),
            'updated_at' => date('Y-m-d H:i:s'),
        ], ['id' => $id]);

        return $updated !== false;
    }

    public function deleteTask(int $id): bool
    {
        return $this->db->delete($this->tasksTable, ['id' => $id]);
    }

    public function countProjects(): int
    {
        return (int) ($this->db->get_var("SELECT COUNT(*) FROM `{$this->projectsTableFull}`") ?? 0);
    }

    public function countBoards(): int
    {
        return (int) ($this->db->get_var("SELECT COUNT(*) FROM `{$this->boardsTableFull}`") ?? 0);
    }

    public function countWidgets(): int
    {
        return (int) ($this->db->get_var("SELECT COUNT(*) FROM `{$this->widgetsTableFull}`") ?? 0);
    }

    public function countTasks(): int
    {
        return (int) ($this->db->get_var("SELECT COUNT(*) FROM `{$this->tasksTableFull}`") ?? 0);
    }

    public function getProjectEntityCounts(array $projectIds): array
    {
        $projectIds = array_values(array_unique(array_filter(array_map(static fn (mixed $value): int => (int) $value, $projectIds), static fn (int $value): bool => $value > 0)));
        if ($projectIds === []) {
            return [];
        }

        $inPlaceholders = implode(', ', array_fill(0, count($projectIds), '?'));
        $counts = [];

        foreach ($projectIds as $projectId) {
            $counts[$projectId] = [
                'board_count' => 0,
                'widget_count' => 0,
                'task_count' => 0,
            ];
        }

        $boardRows = $this->db->get_results(
            "SELECT project_id, COUNT(*) AS aggregate_count
             FROM `{$this->boardsTableFull}`
             WHERE project_id IN ({$inPlaceholders})
             GROUP BY project_id",
            $projectIds
        ) ?: [];

        foreach ($boardRows as $row) {
            $projectId = (int) ($row->project_id ?? 0);
            if ($projectId > 0 && isset($counts[$projectId])) {
                $counts[$projectId]['board_count'] = (int) ($row->aggregate_count ?? 0);
            }
        }

        $widgetRows = $this->db->get_results(
            "SELECT project_id, COUNT(*) AS aggregate_count
             FROM `{$this->widgetsTableFull}`
             WHERE project_id IN ({$inPlaceholders})
             GROUP BY project_id",
            $projectIds
        ) ?: [];

        foreach ($widgetRows as $row) {
            $projectId = (int) ($row->project_id ?? 0);
            if ($projectId > 0 && isset($counts[$projectId])) {
                $counts[$projectId]['widget_count'] = (int) ($row->aggregate_count ?? 0);
            }
        }

        $taskRows = $this->db->get_results(
            "SELECT project_id, COUNT(*) AS aggregate_count
             FROM `{$this->tasksTableFull}`
             WHERE project_id IN ({$inPlaceholders})
             GROUP BY project_id",
            $projectIds
        ) ?: [];

        foreach ($taskRows as $row) {
            $projectId = (int) ($row->project_id ?? 0);
            if ($projectId > 0 && isset($counts[$projectId])) {
                $counts[$projectId]['task_count'] = (int) ($row->aggregate_count ?? 0);
            }
        }

        return $counts;
    }

    public function getSummaryCounts(): array
    {
        $row = $this->db->get_row(
            "SELECT
                (SELECT COUNT(*) FROM `{$this->projectsTableFull}`) AS projects,
                (SELECT COUNT(*) FROM `{$this->boardsTableFull}`) AS boards,
                (SELECT COUNT(*) FROM `{$this->widgetsTableFull}`) AS widgets,
                (SELECT COUNT(*) FROM `{$this->tasksTableFull}`) AS tasks,
                (SELECT COUNT(*) FROM `{$this->projectsTableFull}` WHERE status = ?) AS active_projects,
                (SELECT COUNT(*) FROM `{$this->projectsTableFull}` WHERE visibility = ?) AS public_projects",
            ['active', 'public']
        );

        if (!$row) {
            return [
                'projects' => 0,
                'boards' => 0,
                'widgets' => 0,
                'tasks' => 0,
                'active_projects' => 0,
                'public_projects' => 0,
            ];
        }

        return [
            'projects' => (int) ($row->projects ?? 0),
            'boards' => (int) ($row->boards ?? 0),
            'widgets' => (int) ($row->widgets ?? 0),
            'tasks' => (int) ($row->tasks ?? 0),
            'active_projects' => (int) ($row->active_projects ?? 0),
            'public_projects' => (int) ($row->public_projects ?? 0),
        ];
    }

    public function insertAuditLog(array $payload): int|false
    {
        return $this->db->insert($this->auditLogTable, [
            'actor_id' => max(0, (int) ($payload['actor_id'] ?? 0)),
            'actor_name' => (string) ($payload['actor_name'] ?? ''),
            'action' => (string) ($payload['action'] ?? ''),
            'entity_type' => (string) ($payload['entity_type'] ?? ''),
            'entity_id' => max(0, (int) ($payload['entity_id'] ?? 0)),
            'project_id' => max(0, (int) ($payload['project_id'] ?? 0)),
            'result' => (string) ($payload['result'] ?? 'success'),
            'context_json' => (string) ($payload['context_json'] ?? ''),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getAuditLogs(int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $rows = $this->db->get_results(
            "SELECT * FROM `{$this->auditLogTableFull}` ORDER BY id DESC LIMIT {$limit}"
        ) ?: [];

        return array_map([$this, 'mapAuditLogRow'], $rows);
    }

    private function mapProjectRow(object $row): array
    {
        return [
            'id' => (int) ($row->id ?? 0),
            'name' => (string) ($row->name ?? ''),
            'slug' => (string) ($row->slug ?? ''),
            'summary' => (string) ($row->summary ?? ''),
            'description' => (string) ($row->description ?? ''),
            'status' => (string) ($row->status ?? 'active'),
            'visibility' => (string) ($row->visibility ?? 'member'),
            'owner_name' => (string) ($row->owner_name ?? ''),
            'accent_color' => (string) ($row->accent_color ?? '#2563eb'),
            'member_headline' => (string) ($row->member_headline ?? ''),
            'public_headline' => (string) ($row->public_headline ?? ''),
            'created_at' => (string) ($row->created_at ?? ''),
            'updated_at' => (string) ($row->updated_at ?? ''),
        ];
    }

    private function mapBoardRow(object $row): array
    {
        return [
            'id' => (int) ($row->id ?? 0),
            'project_id' => (int) ($row->project_id ?? 0),
            'board_type' => (string) ($row->board_type ?? 'kanban'),
            'title' => (string) ($row->title ?? ''),
            'description' => (string) ($row->description ?? ''),
            'position' => (int) ($row->position ?? 0),
            'is_public' => (int) ($row->is_public ?? 0),
            'is_active' => (int) ($row->is_active ?? 0),
            'payload' => (string) ($row->payload ?? ''),
            'created_at' => (string) ($row->created_at ?? ''),
            'updated_at' => (string) ($row->updated_at ?? ''),
        ];
    }

    private function mapWidgetRow(object $row): array
    {
        return [
            'id' => (int) ($row->id ?? 0),
            'project_id' => (int) ($row->project_id ?? 0),
            'scope' => (string) ($row->scope ?? 'both'),
            'widget_type' => (string) ($row->widget_type ?? 'text'),
            'title' => (string) ($row->title ?? ''),
            'content' => (string) ($row->content ?? ''),
            'position' => (int) ($row->position ?? 0),
            'is_active' => (int) ($row->is_active ?? 0),
            'payload' => (string) ($row->payload ?? ''),
            'created_at' => (string) ($row->created_at ?? ''),
            'updated_at' => (string) ($row->updated_at ?? ''),
        ];
    }

    private function mapTaskRow(object $row): array
    {
        return [
            'id' => (int) ($row->id ?? 0),
            'project_id' => (int) ($row->project_id ?? 0),
            'board_id' => (int) ($row->board_id ?? 0),
            'column_key' => (string) ($row->column_key ?? ''),
            'title' => (string) ($row->title ?? ''),
            'description' => (string) ($row->description ?? ''),
            'priority' => (string) ($row->priority ?? 'medium'),
            'assignee_name' => (string) ($row->assignee_name ?? ''),
            'due_date' => (string) ($row->due_date ?? ''),
            'sort_order' => (int) ($row->sort_order ?? 0),
            'is_public' => (int) ($row->is_public ?? 0),
            'is_active' => (int) ($row->is_active ?? 0),
            'payload' => (string) ($row->payload ?? ''),
            'created_at' => (string) ($row->created_at ?? ''),
            'updated_at' => (string) ($row->updated_at ?? ''),
        ];
    }

    private function mapAuditLogRow(object $row): array
    {
        return [
            'id' => (int) ($row->id ?? 0),
            'actor_id' => (int) ($row->actor_id ?? 0),
            'actor_name' => (string) ($row->actor_name ?? ''),
            'action' => (string) ($row->action ?? ''),
            'entity_type' => (string) ($row->entity_type ?? ''),
            'entity_id' => (int) ($row->entity_id ?? 0),
            'project_id' => (int) ($row->project_id ?? 0),
            'result' => (string) ($row->result ?? 'success'),
            'context_json' => (string) ($row->context_json ?? ''),
            'created_at' => (string) ($row->created_at ?? ''),
        ];
    }

    private function sanitizeSqlIdentifier(string $identifier): string
    {
        $sanitized = preg_replace('/[^a-zA-Z0-9_]+/', '', $identifier) ?? '';
        return $sanitized !== '' ? $sanitized : 'cms_projects';
    }
}
