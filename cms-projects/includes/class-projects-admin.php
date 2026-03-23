<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Projects_Admin
{
    private const PAGE_OVERVIEW = 'overview';
    private const PAGE_PROJECTS = 'projects';

    public function __construct(private readonly CMS_Projects_Service $service)
    {
    }

    public function registerPages(): void
    {
        if (!function_exists('add_menu_page') || !function_exists('add_submenu_page')) {
            return;
        }

        add_menu_page(
            'CMS Projects',
            'Projects',
            'admin',
            'cms-projects',
            [$this, 'renderOverviewPage'],
            '📁',
            83
        );

        add_submenu_page(
            'cms-projects',
            'Projektübersicht',
            'Übersicht',
            'admin',
            'cms-projects',
            [$this, 'renderOverviewPage']
        );

        add_submenu_page(
            'cms-projects',
            'Projektverwaltung',
            'Projekte',
            'admin',
            'cms-projects-projects',
            [$this, 'renderProjectsPage']
        );
    }

    public function enqueueStyles(): void
    {
        if (!$this->isProjectsAdminRequest()) {
            return;
        }

        $cssFile = CMS_PROJECTS_PLUGIN_DIR . 'assets/css/style.css';
        if (!is_file($cssFile)) {
            return;
        }

        echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_PROJECTS_PLUGIN_URL . 'assets/css/style.css', ENT_QUOTES, 'UTF-8')
            . '?v=' . (int) filemtime($cssFile) . '">' . "\n";
    }

    public function renderOverviewPage(): void
    {
        $this->renderPage(self::PAGE_OVERVIEW);
    }

    public function renderProjectsPage(): void
    {
        $this->renderPage(self::PAGE_PROJECTS);
    }

    public function renderPage(?string $forcedSection = null): void
    {
        $message = null;
        $messageType = 'success';
        $section = $this->resolveSection($forcedSection);
        $selectedProjectId = max(0, (int) ($_GET['project_id'] ?? 0));
        $selectedTaskId = max(0, (int) ($_GET['task_id'] ?? 0));

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['cms_projects_action'])) {
            $postedAction = (string) ($_POST['cms_projects_action'] ?? '');
            [$message, $messageType, $selectedProjectId, $section] = $this->handlePost($section, $selectedProjectId);
            $selectedTaskId = $postedAction === 'save_task' ? max(0, (int) ($_POST['task_id'] ?? 0)) : 0;
        }

        $projects = $this->service->getProjects();
        if ($selectedProjectId <= 0 && $projects !== []) {
            $selectedProjectId = (int) ($projects[0]['id'] ?? 0);
        }

        $selectedProject = $selectedProjectId > 0 ? $this->service->findProject($selectedProjectId) : null;
        $projectBoards = $selectedProject !== null ? $this->service->getProjectBoards((int) $selectedProject['id'], 'admin') : [];
        $projectWidgets = $selectedProject !== null ? $this->service->getProjectWidgets((int) $selectedProject['id'], 'admin') : [];
        $projectTasks = $selectedProject !== null ? $this->service->getProjectTasks((int) $selectedProject['id'], 'admin') : [];
        $taskFormValues = $this->service->getTaskDefaults();
        if ($selectedProject !== null && $selectedTaskId > 0) {
            $selectedTask = $this->service->findTask($selectedTaskId);
            if ($selectedTask !== null && (int) ($selectedTask['project_id'] ?? 0) === (int) ($selectedProject['id'] ?? 0)) {
                $taskFormValues = array_merge($taskFormValues, $selectedTask);
            }
        }
        $summary = $this->service->getSummary();
        $projectFormValues = $selectedProject ?? $this->service->getProjectDefaults();
        $sectionConfig = $this->getSectionConfig($section);
        $csrfToken = class_exists('CMS\\Security') ? \CMS\Security::instance()->generateToken('cms_projects_admin') : '';
        $boardTypes = $this->service->getBoardTypes();
        $widgetTypes = $this->service->getWidgetTypes();
        $taskPriorities = $this->service->getTaskPriorities();
        $projectStatuses = $this->service->getProjectStatuses();
        $projectVisibilities = $this->service->getProjectVisibilities();
        $widgetScopes = $this->service->getWidgetScopes();
        $pageLinks = [
            'overview' => '?page=cms-projects',
            'projects' => '?page=cms-projects-projects',
        ];

        include CMS_PROJECTS_PLUGIN_DIR . 'admin/page.php';
    }

    private function handlePost(string $section, int $selectedProjectId): array
    {
        if (!class_exists('CMS\\Security') || !\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'cms_projects_admin')) {
            return ['Sicherheitscheck fehlgeschlagen.', 'error', $selectedProjectId, $section];
        }

        $action = (string) ($_POST['cms_projects_action'] ?? '');
        if ($action === '') {
            return ['Es wurde keine Aktion übergeben.', 'error', $selectedProjectId, $section];
        }

        if ($action === 'save_project') {
            $editId = max(0, (int) ($_POST['project_id'] ?? 0));
            $result = $this->service->saveProject($_POST, $editId > 0 ? $editId : null);
            $message = (string) ($result['message'] ?? 'Projektaktion abgeschlossen.');
            $messageType = !empty($result['success']) ? 'success' : 'error';
            $selectedProjectId = !empty($result['id']) ? (int) $result['id'] : $selectedProjectId;
            return [$message, $messageType, $selectedProjectId, self::PAGE_PROJECTS];
        }

        if ($action === 'save_board') {
            $result = $this->service->saveBoard($_POST);
            $message = (string) ($result['message'] ?? 'Board-Aktion abgeschlossen.');
            $messageType = !empty($result['success']) ? 'success' : 'error';
            $selectedProjectId = max(0, (int) ($_POST['project_id'] ?? $selectedProjectId));
            return [$message, $messageType, $selectedProjectId, self::PAGE_PROJECTS];
        }

        if ($action === 'save_widget') {
            $result = $this->service->saveWidget($_POST);
            $message = (string) ($result['message'] ?? 'Widget-Aktion abgeschlossen.');
            $messageType = !empty($result['success']) ? 'success' : 'error';
            $selectedProjectId = max(0, (int) ($_POST['project_id'] ?? $selectedProjectId));
            return [$message, $messageType, $selectedProjectId, self::PAGE_PROJECTS];
        }

        if ($action === 'save_task') {
            $result = $this->service->saveTask($_POST);
            $message = (string) ($result['message'] ?? 'Ticket-Aktion abgeschlossen.');
            $messageType = !empty($result['success']) ? 'success' : 'error';
            $selectedProjectId = max(0, (int) ($_POST['project_id'] ?? $selectedProjectId));
            return [$message, $messageType, $selectedProjectId, self::PAGE_PROJECTS];
        }

        if ($action === 'delete_task') {
            $taskId = max(0, (int) ($_POST['task_id'] ?? 0));
            $projectId = max(0, (int) ($_POST['project_id'] ?? $selectedProjectId));
            $result = $this->service->deleteTask($taskId, $projectId);
            $message = (string) ($result['message'] ?? 'Ticket-Löschung abgeschlossen.');
            $messageType = !empty($result['success']) ? 'success' : 'error';
            return [$message, $messageType, $projectId, self::PAGE_PROJECTS];
        }

        if ($action === 'move_task') {
            $taskId = max(0, (int) ($_POST['task_id'] ?? 0));
            $projectId = max(0, (int) ($_POST['project_id'] ?? $selectedProjectId));
            $targetBoardId = max(0, (int) ($_POST['target_board_id'] ?? 0));
            $targetColumnKey = (string) ($_POST['target_column_key'] ?? '');
            $orderedTaskIds = array_values(array_filter(
                array_map('intval', explode(',', (string) ($_POST['ordered_task_ids'] ?? ''))),
                static fn (int $id): bool => $id > 0
            ));
            $result = $this->service->moveTask($taskId, $projectId, $targetBoardId, $targetColumnKey, $orderedTaskIds);
            $message = (string) ($result['message'] ?? 'Ticket-Verschiebung abgeschlossen.');
            $messageType = !empty($result['success']) ? 'success' : 'error';
            return [$message, $messageType, $projectId, self::PAGE_PROJECTS];
        }

        return ['Unbekannte Aktion.', 'error', $selectedProjectId, $section];
    }

    private function resolveSection(?string $forcedSection = null): string
    {
        if ($forcedSection !== null && $forcedSection !== '') {
            return $forcedSection;
        }

        return match ((string) ($_GET['page'] ?? 'cms-projects')) {
            'cms-projects-projects' => self::PAGE_PROJECTS,
            default => self::PAGE_OVERVIEW,
        };
    }

    private function getSectionConfig(string $section): array
    {
        $pages = [
            self::PAGE_OVERVIEW => ['slug' => 'cms-projects', 'label' => 'Übersicht', 'title' => 'Projektübersicht', 'description' => 'JIRA-ähnliche Projektsteuerung mit Boards und Widgets.'],
            self::PAGE_PROJECTS => ['slug' => 'cms-projects-projects', 'label' => 'Projekte', 'title' => 'Projektverwaltung', 'description' => 'Lege Projekte, Boards und Widgets für Member- und Public-Dashboards an.'],
        ];

        return $pages[$section] ?? $pages[self::PAGE_OVERVIEW];
    }

    private function isProjectsAdminRequest(): bool
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        if ($requestUri === '') {
            return false;
        }

        $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');
        return $path !== '' && str_contains($path, '/admin/plugins/cms-projects');
    }
}
