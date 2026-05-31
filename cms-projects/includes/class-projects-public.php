<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Projects_Public
{
    private static bool $renderingPublicProjectsPage = false;

    public function __construct(private readonly CMS_Projects_Service $service)
    {
    }

    public static function isRenderingPublicProjectsPage(): bool
    {
        return self::$renderingPublicProjectsPage;
    }

    public function registerRoutes(object $router): void
    {
        if (!method_exists($router, 'addRoute')) {
            return;
        }

        $router->addRoute('GET', '/projects', [$this, 'archivePage']);
        $router->addRoute('GET', '/projects/:slug', [$this, 'singlePage']);
    }

    public function archivePage(): void
    {
        $projects = $this->service->getPublicProjects();
        $currentProject = null;
        $dashboard = null;
        $this->renderTemplate($projects, $currentProject, $dashboard, 'archive');
    }

    public function singlePage(string $slug = ''): void
    {
        $slug = trim($slug) !== '' ? $slug : (string) ($_GET['slug'] ?? '');
        $slug = preg_replace('/[^a-z0-9\-_]/i', '', $slug) ?? '';
        $slug = substr($slug, 0, 120);
        $project = $this->service->findProjectBySlug($slug);
        if ($project === null || ($project['visibility'] ?? '') !== 'public') {
            http_response_code(404);
            echo '<h1>404 – Projekt nicht gefunden</h1>';
            return;
        }

        $projects = $this->service->getPublicProjects();
        $dashboard = $this->service->getProjectDashboardPayload((int) $project['id'], 'public');
        if ($dashboard === null) {
            http_response_code(404);
            echo '<h1>404 – Projekt nicht gefunden</h1>';
            return;
        }

        $this->renderTemplate($projects, $project, $dashboard, 'single');
    }

    private function renderTemplate(array $projects, ?array $currentProject, ?array $dashboard, string $view): void
    {
        self::$renderingPublicProjectsPage = true;
        $theme = class_exists('CMS\\ThemeManager') ? \CMS\ThemeManager::instance() : null;
        try {
            if ($theme !== null) {
                $theme->getHeader();
            }

            include CMS_PROJECTS_PLUGIN_DIR . 'templates/public-projects.php';

            if ($theme !== null) {
                $theme->getFooter();
            }
        } finally {
            self::$renderingPublicProjectsPage = false;
        }
    }
}
