<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Projects_Admin
{
    private const PAGE_OVERVIEW = 'overview';
    private const PAGE_PROJECTS = 'projects';
    private const PAGE_SLUG_OVERVIEW = 'cms-projects';
    private const PAGE_SLUG_PROJECTS = 'cms-projects-projects';

    private ?string $dispatchFallbackNotice = null;

    public function __construct(private readonly CMS_Projects_Service $service)
    {
        $this->requireSharedAdminContract();
    }

    public function registerPages(): void
    {
        if (!function_exists('add_menu_page') || !function_exists('add_submenu_page')) {
            return;
        }

        add_menu_page(
            'CMS Projects',
            '365CMS | Projects',
            'admin',
            self::PAGE_SLUG_OVERVIEW,
            [$this, 'dispatchPage'],
            'PRJ',
            83
        );

        add_submenu_page(
            self::PAGE_SLUG_OVERVIEW,
            'Projektübersicht',
            'Übersicht',
            'admin',
            self::PAGE_SLUG_OVERVIEW,
            [$this, 'dispatchPage']
        );

        add_submenu_page(
            self::PAGE_SLUG_OVERVIEW,
            'Projektverwaltung',
            'Projekte',
            'admin',
            self::PAGE_SLUG_PROJECTS,
            [$this, 'dispatchPage']
        );
    }

    public function dispatchPage(): void
    {
        $callbackMap = $this->getAdminCallbackMap();
        $defaultSlug = self::PAGE_SLUG_OVERVIEW;
        $requestedSlug = $this->resolveRequestedAdminSlug($defaultSlug);
        $resolvedSlug = array_key_exists($requestedSlug, $callbackMap) ? $requestedSlug : $defaultSlug;

        if ($requestedSlug !== $resolvedSlug) {
            $this->dispatchFallbackNotice = 'Die angeforderte Admin-Seite wurde nicht gefunden. Es wird die Übersicht angezeigt.';
            error_log(sprintf(
                '[cms-projects] admin fallback requested=%s resolved=%s default=%s',
                $requestedSlug,
                $resolvedSlug,
                $defaultSlug
            ));
        } else {
            $this->dispatchFallbackNotice = null;
        }

        if (function_exists('cms_plugin_admin_dispatch_page')) {
            cms_plugin_admin_dispatch_page($callbackMap, $defaultSlug, self::PAGE_SLUG_OVERVIEW);
            return;
        }

        $callback = $callbackMap[$resolvedSlug] ?? null;
        if (!is_callable($callback)) {
            $this->renderDispatchError($requestedSlug, $resolvedSlug, $defaultSlug);
            return;
        }

        call_user_func($callback);
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
        if (!$this->currentUserCanManageProjects()) {
            header('Location: ' . (defined('SITE_URL') ? (string) SITE_URL : '/'), true, 303);
            exit;
        }

        $message = null;
        $messageType = 'success';
        $section = $this->resolveSection($forcedSection);
        $selectedProjectId = max(0, (int) ($_GET['project_id'] ?? 0));
        $selectedTaskId = max(0, (int) ($_GET['task_id'] ?? 0));

        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST' && isset($_POST['cms_projects_action'])) {
            $postedAction = (string) ($_POST['cms_projects_action'] ?? '');
            [$message, $messageType, $selectedProjectId, $section] = $this->handlePost($section, $selectedProjectId);
            $selectedTaskId = $postedAction === 'save_task' ? max(0, (int) ($_POST['task_id'] ?? 0)) : 0;
        }

        if ($message === null && $this->dispatchFallbackNotice !== null) {
            $message = $this->dispatchFallbackNotice;
            $messageType = 'error';
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

        $title = (string) ($sectionConfig['title'] ?? 'CMS Projects');
        if (function_exists('cms_plugin_admin_layout_start') && function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_start($title, self::PAGE_SLUG_OVERVIEW);
            include CMS_PROJECTS_PLUGIN_DIR . 'admin/page.php';
            cms_plugin_admin_layout_end();
            return;
        }

        include CMS_PROJECTS_PLUGIN_DIR . 'admin/page.php';
    }

    private function handlePost(string $section, int $selectedProjectId): array
    {
        if (!$this->currentUserCanManageProjects()) {
            return ['Keine Berechtigung für diese Aktion.', 'error', $selectedProjectId, $section];
        }

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
            self::PAGE_SLUG_PROJECTS => self::PAGE_PROJECTS,
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
        $page = $this->normalizeAdminSlug((string) ($_GET['page'] ?? ''));
        if ($page === '') {
            return false;
        }

        return isset($this->getAdminCallbackMap()[$page]);
    }

    private function currentUserCanManageProjects(): bool
    {
        if (class_exists('CMS\\Auth')) {
            return \CMS\Auth::instance()->isAdmin();
        }

        return false;
    }

    private function getAdminCallbackMap(): array
    {
        return [
            self::PAGE_SLUG_OVERVIEW => [$this, 'renderOverviewPage'],
            self::PAGE_SLUG_PROJECTS => [$this, 'renderProjectsPage'],
        ];
    }

    private function resolveRequestedAdminSlug(string $fallback): string
    {
        if (function_exists('cms_plugin_admin_active_slug')) {
            return (string) cms_plugin_admin_active_slug($fallback);
        }

        $requested = (string) ($_GET['page'] ?? $fallback);
        $requested = $this->normalizeAdminSlug($requested);
        $fallback = $this->normalizeAdminSlug($fallback);

        return $requested !== '' ? $requested : $fallback;
    }

    private function normalizeAdminSlug(string $slug): string
    {
        if (function_exists('cms_plugin_admin_normalize_slug')) {
            return (string) cms_plugin_admin_normalize_slug($slug);
        }

        $slug = strtolower(trim($slug));
        $slug = (string) preg_replace('/[^a-z0-9_-]+/', '-', $slug);
        return trim($slug, '-');
    }

    private function renderDispatchError(string $requestedSlug, string $resolvedSlug, string $defaultSlug): void
    {
        $message = 'Die angeforderte Admin-Seite ist derzeit nicht verfügbar. Bitte prüfen Sie die Plugin-Konfiguration.';
        error_log(sprintf(
            '[cms-projects] missing admin callback requested=%s resolved=%s default=%s',
            $requestedSlug,
            $resolvedSlug,
            $defaultSlug
        ));

        if (function_exists('cms_plugin_admin_layout_start') && function_exists('cms_plugin_admin_layout_end')) {
            cms_plugin_admin_layout_start('CMS Projects', self::PAGE_SLUG_OVERVIEW);
            if (function_exists('cms_plugin_admin_emit_notice')) {
                cms_plugin_admin_emit_notice($message, 'error');
            } else {
                echo '<div class="alert alert-error" role="alert">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
            }
            cms_plugin_admin_layout_end();
            return;
        }

        echo '<div class="wrap"><div class="notice notice-error"><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></div></div>';
    }

    private function requireSharedAdminContract(): void
    {
        $pluginsRoot = realpath(dirname(CMS_PROJECTS_PLUGIN_DIR));
        if ($pluginsRoot === false) {
            return;
        }

        $sharedContractFile = $pluginsRoot . DIRECTORY_SEPARATOR . 'shared' . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'plugin-admin-contract.php';
        $resolvedSharedFile = realpath($sharedContractFile);
        if ($resolvedSharedFile === false || !is_file($resolvedSharedFile)) {
            return;
        }

        $normalizedPluginsRoot = rtrim(str_replace('\\', '/', $pluginsRoot), '/');
        $normalizedSharedFile = str_replace('\\', '/', $resolvedSharedFile);

        if (!str_starts_with($normalizedSharedFile, $normalizedPluginsRoot . '/')) {
            error_log('[cms-projects] blocked shared admin contract include outside plugin root');
            return;
        }

        require_once $resolvedSharedFile;
    }
}
