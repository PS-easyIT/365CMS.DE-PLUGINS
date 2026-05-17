<?php
/**
 * CMS M365 Tools – Admin Pages.
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
        self::render_with_layout('M365 Tools', 'm365tools-dashboard', static function (): void {
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
        $notice = '';
        $error = '';
        $csrfToken = class_exists('CMS\\Security')
            ? \CMS\Security::instance()->generateToken('m365tools_admin_modules')
            : '';

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && ($_POST['action'] ?? '') === 'save_module_settings') {
            if (class_exists('CMS\\Security') && !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'm365tools_admin_modules')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                CMS_M365CALCULATOR_Settings::save_module_settings($_POST);
                $notice = 'Moduleinstellungen gespeichert.';
            }
        }

        $allTools = CMS_M365CALCULATOR_Tool_Registry::ordered_tools(false);
        $tools = CMS_M365CALCULATOR_Tool_Registry::ordered_tools(true);
        $moduleSettings = CMS_M365CALCULATOR_Settings::settings_for_admin(CMS_M365CALCULATOR_Tool_Registry::tools(false));
        $rules = CMS_M365CALCULATOR_Catalog::rules();
        $licenseMatrix = CMS_M365CALCULATOR_Catalog::license_matrix();
        ?>
        <div class="admin-page-header">
            <div>
                <h2>🧮 M365 Tools</h2>
                <p>Modulare Toolbox für Lizenz-, Kosten- und Governance-Rechner.</p>
            </div>
            <div class="header-actions">
                <a href="/m365-tools" class="btn btn-primary" target="_blank" rel="noopener noreferrer">👁️ Toolbox öffnen</a>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid m365calculator-admin-grid">
            <div class="stat-card">
                <div class="stat-icon">🧩</div>
                <div class="stat-number"><?php echo (int) count($allTools); ?></div>
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
            <p class="m365calculator-admin-muted">Steuere je Modul Sichtbarkeit, Status, Reihenfolge und öffentliche Texte. Leere Textfelder nutzen den Registry-Standard.</p>
            <form method="POST" class="admin-form">
                <input type="hidden" name="action" value="save_module_settings">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="users-table-container">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Modul</th>
                            <th>Aktiv</th>
                            <th>Status</th>
                            <th>Sortierung</th>
                            <th>URL</th>
                            <th>Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allTools as $tool): ?>
                        <?php
                        $moduleKey = (string) ($tool['key'] ?? '');
                        $settings = $moduleSettings[$moduleKey] ?? [];
                        $statusValue = (string) ($settings['status_override'] ?? ($tool['status'] ?? 'live'));
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars((string) ($tool['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <input type="hidden" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][module_key]" value="<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>">
                                <label class="m365calculator-admin-stack">
                                    Titel-Override
                                    <input class="m365calculator-admin-control" type="text" maxlength="90" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][title_override]" value="<?php echo htmlspecialchars((string) ($settings['title_override'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo htmlspecialchars((string) ($tool['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                </label>
                            </td>
                            <td>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][is_enabled]" value="1"<?php echo (int) ($settings['is_enabled'] ?? 1) === 1 ? ' checked' : ''; ?>>
                                    Sichtbar
                                </label>
                            </td>
                            <td>
                                <select class="m365calculator-admin-control" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][status_override]">
                                    <?php foreach (['live' => 'Live', 'beta' => 'Beta', 'soon' => 'Bald'] as $status => $label): ?>
                                    <option value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $statusValue === $status ? ' selected' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><input class="m365calculator-admin-control m365calculator-admin-priority" type="number" min="0" max="1000" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][priority_override]" value="<?php echo (int) ($settings['priority_override'] ?? ($tool['priority'] ?? 100)); ?>"></td>
                            <td><code><?php echo htmlspecialchars((string) ($tool['url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></code></td>
                            <td>
                                <textarea class="m365calculator-admin-control" rows="3" maxlength="140" name="modules[<?php echo htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8'); ?>][description_override]" placeholder="<?php echo htmlspecialchars((string) ($tool['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) ($settings['description_override'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
                <button type="submit" class="btn btn-primary">💾 Moduleinstellungen speichern</button>
            </form>
        </div>
        <?php
    }
}
