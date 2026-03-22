<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Projects_Member_Dashboard
{
    public function __construct(private readonly CMS_Projects_Service $service)
    {
        if (class_exists('CMS\\Hooks')) {
            \CMS\Hooks::addAction('member_dashboard_init', [$this, 'register'], 10);
            \CMS\Hooks::addAction('member_plugin_section_head', [$this, 'enqueueSectionStyles'], 10);
        }
    }

    public function register(\CMS\Member\PluginDashboardRegistry $registry): void
    {
        $registry->register([
            'plugin' => 'cms-projects',
            'slug' => 'projects',
            'label' => 'PROJECTS',
            'icon' => '📁',
            'category' => 'plugins',
            'priority' => 30,
            'capability' => null,
            'dashboard_widget' => [
                'title' => 'PROJECTS',
                'description' => 'Projektboards, Widgets und Statusübersichten verwalten.',
                'color' => '#2563eb',
                'stats_callback' => [$this, 'getDashboardStats'],
                'link_label' => 'Zu den Projekten',
                'admin_url' => '/admin/plugins/cms-projects',
                'admin_label' => '⚙️ Admin',
            ],
            'render_callback' => [$this, 'renderPage'],
        ]);
    }

    public function enqueueSectionStyles(string $slug): void
    {
        if ($slug !== 'projects') {
            return;
        }

        $cssFile = CMS_PROJECTS_PLUGIN_DIR . 'assets/css/style.css';
        if (!is_file($cssFile)) {
            return;
        }

        echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_PROJECTS_PLUGIN_URL . 'assets/css/style.css', ENT_QUOTES, 'UTF-8')
            . '?v=' . (int) filemtime($cssFile) . '">' . "\n";
    }

    public function getDashboardStats(object $user): array
    {
        $projects = $this->service->getMemberProjects();
        return [
            'count' => count($projects),
            'label' => 'Projekte',
        ];
    }

    public function renderPage(object $user, array $params = []): void
    {
        $projects = $this->service->getMemberProjects();
        $selectedSlug = trim((string) ($_GET['project'] ?? ''));
        $currentProject = null;

        if ($selectedSlug !== '') {
            $candidate = $this->service->findProjectBySlug($selectedSlug);
            if ($candidate !== null && ($candidate['visibility'] ?? '') !== 'private') {
                $currentProject = $candidate;
            }
        }

        if ($currentProject === null && $projects !== []) {
            $currentProject = $projects[0];
        }

        $dashboard = $currentProject !== null
            ? $this->service->getProjectDashboardPayload((int) $currentProject['id'], 'member')
            : null;

        include CMS_PROJECTS_PLUGIN_DIR . 'templates/member-projects.php';
    }
}
