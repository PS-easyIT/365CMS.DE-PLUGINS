<?php
/**
 * CMS M365 Calculator – Admin Pages.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Admin_Pages
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    public static function render_dashboard(): void
    {
        self::render_with_layout('M365 Rechner', 'm365calculator-dashboard', static function (): void {
            self::instance()->render_dashboard_page();
        });
    }

    private static function render_with_layout(string $title, string $slug, callable $renderer): void
    {
        self::check_access();
        self::load_admin_menu();

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $slug);
        }

        self::enqueue_admin_assets();
        echo '<div class="m365calculator-admin-shell">';
        $renderer();
        echo '</div>';

        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
        }
    }

    private static function check_access(): void
    {
        if (!class_exists('CMS\\Auth') || !\CMS\Auth::instance()->isAdmin()) {
            header('Location: ' . (defined('SITE_URL') ? SITE_URL : '/'));
            exit;
        }
    }

    private static function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    private static function enqueue_admin_assets(): void
    {
        $css = CMS_M365CALCULATOR_PLUGIN_DIR . 'assets/css/m365calculator-admin.css';
        if (file_exists($css)) {
            echo '<link rel="stylesheet" href="'
                . htmlspecialchars(CMS_M365CALCULATOR_PLUGIN_URL . 'assets/css/m365calculator-admin.css', ENT_QUOTES, 'UTF-8')
                . '?v=' . filemtime($css) . '">' . "\n";
        }
    }

    public function render_dashboard_page(): void
    {
        $tools = CMS_M365CALCULATOR_Tool_Registry::ordered_tools();
        $rules = CMS_M365CALCULATOR_Catalog::rules();
        $licenseMatrix = CMS_M365CALCULATOR_Catalog::license_matrix();
        ?>
        <div class="admin-page-header">
            <div>
                <h2>🧮 M365 Rechner</h2>
                <p>Modulare Toolbox für Lizenz-, Kosten- und Governance-Rechner.</p>
            </div>
            <div class="header-actions">
                <a href="/m365-tools" class="btn btn-primary" target="_blank" rel="noopener noreferrer">👁️ Toolbox öffnen</a>
            </div>
        </div>

        <div class="dashboard-grid m365calculator-admin-grid">
            <div class="stat-card">
                <div class="stat-icon">🧩</div>
                <div class="stat-number"><?php echo (int) count($tools); ?></div>
                <div class="stat-label">Module</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-number"><?php echo (int) count(array_filter($tools, static fn(array $tool): bool => ($tool['status'] ?? '') === 'live')); ?></div>
                <div class="stat-label">Aktiv</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📏</div>
                <div class="stat-number"><?php echo (int) ($rules['limits']['max_unlicensed_storage_gb'] ?? 50); ?> GB</div>
                <div class="stat-label">Lizenzfrei</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-number"><?php echo (int) count($licenseMatrix); ?></div>
                <div class="stat-label">Lizenzprofile</div>
            </div>
        </div>

        <div class="admin-card">
            <h3>📋 Modulübersicht</h3>
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Modul</th>
                            <th>Status</th>
                            <th>Route</th>
                            <th>Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tools as $tool): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars((string) ($tool['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td><span class="status-badge <?php echo ($tool['status'] ?? '') === 'live' ? 'active' : 'inactive'; ?>"><?php echo htmlspecialchars((string) ($tool['status'] ?? 'soon'), ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><code><?php echo htmlspecialchars((string) ($tool['route'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td><?php echo htmlspecialchars((string) ($tool['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}
