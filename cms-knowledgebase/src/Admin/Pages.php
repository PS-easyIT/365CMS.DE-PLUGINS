<?php

declare(strict_types=1);

namespace CmsKnowledgebase\Admin;

use CMS\Auth;
use CMS\Security;
use CmsKnowledgebase\Repository\EntryRepository;
use CmsKnowledgebase\Service\StandardPackages;

if (!defined('ABSPATH')) {
    exit;
}

final class Pages
{
    public static function renderDashboard(): void
    {
        self::renderWithLayout('Knowledgebase', static function (): void {
            $repository = EntryRepository::instance();
            $stats = $repository->getDashboardStats();
            $entries = array_slice($repository->getEntryList(), 0, 8);
            $settings = $repository->getSettings();
            $csrfToken = Security::instance()->generateToken('knowledgebase_admin');
            $notice = self::pullNotice();

            include CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'admin/views/page-dashboard.php';
        });
    }

    public static function renderEntries(): void
    {
        self::renderWithLayout('Knowledgebase-Einträge', static function (): void {
            $repository = EntryRepository::instance();
            $entries = $repository->getEntryList();
            $standardPackages = StandardPackages::instance()->getPackages();
            $entry = isset($_GET['edit']) ? $repository->getEntry((int) $_GET['edit']) : null;
            $csrfToken = Security::instance()->generateToken('knowledgebase_admin');
            $notice = self::pullNotice();

            include CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'admin/views/page-entries.php';
        });
    }

    public static function renderSettings(): void
    {
        self::renderWithLayout('Knowledgebase-Einstellungen', static function (): void {
            $repository = EntryRepository::instance();
            $settings = $repository->getSettings();
            $stats = $repository->getDashboardStats();
            $tab = (string) ($_GET['tab'] ?? 'general');
            $tabs = [
                'general' => '⚙️ Allgemein',
                'design' => '🎨 Design',
                'system' => '🖥️ System',
            ];
            if (!isset($tabs[$tab])) {
                $tab = 'general';
            }

            $designTokens = $repository->getPublicDesignTokens();
            $systemInfo = [
                'plugin_version' => CMS_KNOWLEDGEBASE_VERSION,
                'public_route' => '/kb',
                'content_max_width' => $settings['content_max_width'] ?? '1200',
                'sidebar_width' => $settings['sidebar_width'] ?? '300',
                'autolink_enabled' => ($settings['enable_autolink'] ?? '0') === '1' ? 'Ja' : 'Nein',
                'output_buffer' => ($settings['enable_output_buffer'] ?? '0') === '1' ? 'Ja' : 'Nein',
            ];
            $csrfToken = Security::instance()->generateToken('knowledgebase_admin');
            $notice = self::pullNotice();

            include CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'admin/views/page-settings.php';
        });
    }

    private static function renderWithLayout(string $title, callable $renderer): void
    {
        self::checkAccess();
        self::handlePost();
        self::loadAdminMenu();

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, 'knowledgebase-dashboard');
        }

        self::enqueueAdminAssets();
        $renderer();

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function handlePost(): void
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            return;
        }

        if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'knowledgebase_admin')) {
            self::storeNotice(false, 'Sicherheitscheck fehlgeschlagen.');
            self::redirectBack();
        }

        $repository = EntryRepository::instance();
        $action = (string) ($_POST['action'] ?? '');
        $redirectTab = (string) ($_POST['redirect_tab'] ?? $_GET['tab'] ?? '');
        $result = match ($action) {
            'save_entry' => $repository->saveEntry($_POST),
            'delete_entry' => $repository->deleteEntry((int) ($_POST['entry_id'] ?? 0)),
            'save_settings' => $repository->saveSettings($_POST),
            'create_standard_package' => StandardPackages::instance()->importPackage((string) ($_POST['package_key'] ?? '')),
            'create_all_standard_packages' => StandardPackages::instance()->importAllPackages(),
            default => ['success' => false, 'error' => 'Unbekannte Aktion.'],
        };

        self::storeNotice((bool) ($result['success'] ?? false), (string) ($result['message'] ?? $result['error'] ?? '')); 
        self::redirectBack((int) ($result['id'] ?? 0), $redirectTab);
    }

    private static function enqueueAdminAssets(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }
        $loaded = true;

        $css = CMS_KNOWLEDGEBASE_PLUGIN_DIR . 'assets/css/knowledgebase-admin.css';
        if (is_file($css)) {
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_KNOWLEDGEBASE_PLUGIN_URL . 'assets/css/knowledgebase-admin.css?v=' . filemtime($css), ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }
    }

    private static function loadAdminMenu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (is_file($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    private static function checkAccess(): void
    {
        if (!Auth::instance()->isAdmin()) {
            header('Location: ' . SITE_URL);
            exit;
        }
    }

    private static function storeNotice(bool $success, string $message): void
    {
        $_SESSION['knowledgebase_admin_notice'] = [
            'type' => $success ? 'success' : 'error',
            'message' => $message,
        ];
    }

    private static function pullNotice(): ?array
    {
        if (empty($_SESSION['knowledgebase_admin_notice']) || !is_array($_SESSION['knowledgebase_admin_notice'])) {
            return null;
        }

        $notice = $_SESSION['knowledgebase_admin_notice'];
        unset($_SESSION['knowledgebase_admin_notice']);

        return $notice;
    }

    private static function redirectBack(int $editId = 0, string $tab = ''): void
    {
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
        $url = SITE_URL . ($path !== false && $path !== null && $path !== '' ? $path : '/admin/plugins/knowledgebase-dashboard/knowledgebase-dashboard');

        $query = [];

        if ($tab !== '' && str_contains($url, 'knowledgebase-settings')) {
            $query['tab'] = $tab;
        }

        if ($editId > 0 && str_contains($url, 'knowledgebase-entries')) {
            $query['edit'] = (string) $editId;
        }

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        header('Location: ' . $url);
        exit;
    }
}
