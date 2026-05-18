<?php
/**
 * Admin-Oberfläche für CMS NetImport.
 *
 * @package CMS_NetImport
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_NetImport_Admin
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->load_admin_menu();
        CMS\Hooks::addAction('register_routes', [$this, 'register_routes'], 10);
        CMS\Hooks::addAction('cms_admin_menu', [$this, 'register_admin_menu'], 10);
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }

    public function register_admin_menu(): void
    {
        if (!function_exists('add_menu_page')) {
            return;
        }

        add_menu_page(
            'NetImport',
            'NetImport',
            'manage_options',
            'netimport',
            [self::class, 'render_plugin_page_bridge'],
            'NI',
            46
        );
    }

    public static function render_plugin_page_bridge(): void
    {
        if (!headers_sent()) {
            header('Location: ' . SITE_URL . '/admin/netimport', true, 303);
            exit;
        }

        $targetUrl = htmlspecialchars(SITE_URL . '/admin/netimport', ENT_QUOTES, 'UTF-8');

        echo '<div class="admin-card"><p>Weiterleitung zur NetImport-Verwaltung … <a href="' . $targetUrl . '">Falls nichts passiert, hier klicken</a>.</p></div>';
    }

    private function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    private function start_admin_layout(string $title, string $activePage): void
    {
        $this->load_admin_menu();

        if (function_exists('renderAdminLayoutStart')) {
            renderAdminLayoutStart($title, $activePage);
            return;
        }

        $pageTitle = $title;
        require_once ABSPATH . 'admin/partials/header.php';
        require_once ABSPATH . 'admin/partials/sidebar.php';
    }

    private function end_admin_layout(): void
    {
        if (function_exists('renderAdminLayoutEnd')) {
            renderAdminLayoutEnd();
            return;
        }

        require_once ABSPATH . 'admin/partials/footer.php';
    }

    public function register_routes($router): void
    {
        $router->addRoute('GET', '/admin/netimport', [$this, 'render_page']);
        $router->addRoute('POST', '/admin/netimport/run', [$this, 'handle_run']);
        $router->addRoute('POST', '/admin/netimport/history-action', [$this, 'handle_history_action']);
    }

    public function add_menu_item(array $menuItems): array
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $menuItems[] = [
            'type'   => 'item',
            'slug'   => 'netimport',
            'label'  => 'NetImport',
            'icon'   => 'NI',
            'url'    => '/admin/netimport',
            'active' => str_starts_with((string) $currentPath, '/admin/netimport'),
        ];

        return $menuItems;
    }

    public function handle_run(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        $options = $this->read_options();

        $csrfToken = (string) ($_POST['csrf_token'] ?? '');
        if (!CMS\Security::instance()->verifyToken($csrfToken, 'netimport_run')) {
            $this->render_page([
                'errors'   => 1,
                'warnings' => 0,
                'created'  => 0,
                'updated'  => 0,
                'linked'   => 0,
                'skipped'  => 0,
                'dry_run'  => false,
                'messages' => [
                    ['level' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen. Bitte Formular erneut absenden.'],
                ],
                'type'     => 'csrf',
                'file'     => '',
            ], $options);
            return;
        }

        if (!$this->check_run_rate_limit()) {
            $this->render_page([
                'errors'   => 1,
                'warnings' => 0,
                'created'  => 0,
                'updated'  => 0,
                'linked'   => 0,
                'skipped'  => 0,
                'dry_run'  => false,
                'messages' => [
                    ['level' => 'error', 'text' => 'Zu viele Importversuche in kurzer Zeit. Bitte kurz warten und dann erneut starten.'],
                ],
                'type'     => 'rate_limit',
                'file'     => '',
            ], $options);
            return;
        }

        $this->log_run_attempt();

        $importType = trim((string) ($_POST['import_type'] ?? 'full'));
        $allowed = ['full', 'companies_example', 'experts_mvps', 'experts_example', 'speakers', 'events'];
        if (!in_array($importType, $allowed, true)) {
            $importType = 'full';
        }

        $result  = CMS_NetImport_Importer::instance()->run_import($importType, $options);

        $this->render_page($result, $options);
    }

    public function handle_history_action(): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        $filters = $this->read_history_filters();
        $csrfToken = (string) ($_POST['csrf_token'] ?? '');
        if (!CMS\Security::instance()->verifyToken($csrfToken, 'netimport_history_action')) {
            $this->render_page([
                'errors' => 1,
                'warnings' => 0,
                'created' => 0,
                'updated' => 0,
                'linked' => 0,
                'skipped' => 0,
                'dry_run' => false,
                'messages' => [
                    ['level' => 'error', 'text' => 'Sicherheitscheck für Historien-Aktion fehlgeschlagen.'],
                ],
                'type' => 'history_action',
                'file' => '',
            ], $this->read_options(), $filters);
            return;
        }

        if (!$this->check_history_rate_limit()) {
            $this->render_page([
                'errors' => 1,
                'warnings' => 0,
                'created' => 0,
                'updated' => 0,
                'linked' => 0,
                'skipped' => 0,
                'dry_run' => false,
                'messages' => [
                    ['level' => 'error', 'text' => 'Zu viele Historien-Aktionen in kurzer Zeit. Bitte kurz warten und erneut versuchen.'],
                ],
                'type' => 'history_action',
                'file' => '',
            ], $this->read_options(), $filters);
            return;
        }

        $this->log_history_action_attempt();

        $action = trim((string) ($_POST['history_action'] ?? ''));
        $importer = CMS_NetImport_Importer::instance();
        $result = [
            'errors' => 0,
            'warnings' => 0,
            'created' => 0,
            'updated' => 0,
            'linked' => 0,
            'skipped' => 0,
            'dry_run' => false,
            'messages' => [],
            'type' => 'history_action',
            'file' => '',
        ];

        if ($action === 'clear_history') {
            $deleted = $importer->clear_run_history();
            $result['messages'][] = ['level' => 'success', 'text' => 'Import-Historie gelöscht. Entfernte Einträge: ' . $deleted . '.'];
        } elseif ($action === 'reset_run') {
            $runId = max(0, (int) ($_POST['run_id'] ?? 0));
            $summary = $importer->reset_run($runId);
            $result['messages'][] = [
                'level' => !empty($summary['success']) ? 'success' : 'warning',
                'text' => (string) ($summary['message'] ?? 'Reset ausgeführt.'),
            ];
            if (empty($summary['success'])) {
                $result['warnings'] = 1;
            }
        } else {
            $result['warnings'] = 1;
            $result['messages'][] = ['level' => 'warning', 'text' => 'Unbekannte Historien-Aktion.'];
        }

        $this->render_page($result, $this->read_options(), $filters);
    }

    private function read_options(): array
    {
        return [
            'update_existing'      => isset($_POST['update_existing']) ? '1' : '0',
            'auto_create_companies' => isset($_POST['auto_create_companies']) ? '1' : '0',
            'link_relations'       => isset($_POST['link_relations']) ? '1' : '0',
            'auto_create_event_people' => isset($_POST['auto_create_event_people']) ? '1' : '0',
            'dry_run'              => isset($_POST['dry_run']) ? '1' : '0',
        ];
    }

    private function check_run_rate_limit(): bool
    {
        return CMS\Security::checkDbRateLimit(CMS\Security::getClientIp(), 'netimport_run', 6, 300);
    }

    private function log_run_attempt(): void
    {
        CMS\Security::recordDbRateLimitAttempt(CMS\Security::getClientIp(), 'netimport_run', 'admin-netimport');
    }

    private function check_history_rate_limit(): bool
    {
        return CMS\Security::checkDbRateLimit(CMS\Security::getClientIp(), 'netimport_history_action', 20, 300);
    }

    private function log_history_action_attempt(): void
    {
        CMS\Security::recordDbRateLimitAttempt(CMS\Security::getClientIp(), 'netimport_history_action', 'admin-netimport');
    }

    private function render_history_filter_inputs(array $historyFilters): void
    {
        ?>
        <input type="hidden" name="history_type" value="<?= htmlspecialchars((string) ($historyFilters['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="history_mode" value="<?= htmlspecialchars((string) ($historyFilters['mode'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="history_errors" value="<?= htmlspecialchars((string) ($historyFilters['errors'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <?php
    }

    private function output_admin_assets(): void
    {
        $adminCss = CMS_NETIMPORT_PLUGIN_DIR . 'assets/css/netimport-admin.css';
        if (file_exists($adminCss)) {
            $version = (string) filemtime($adminCss);
            echo '<link rel="stylesheet" href="' . htmlspecialchars(CMS_NETIMPORT_PLUGIN_URL . 'assets/css/netimport-admin.css?v=' . $version, ENT_QUOTES, 'UTF-8') . '">' . "\n";
        }

        $adminJs = CMS_NETIMPORT_PLUGIN_DIR . 'assets/js/netimport-admin.js';
        if (file_exists($adminJs)) {
            $version = (string) filemtime($adminJs);
            echo '<script src="' . htmlspecialchars(CMS_NETIMPORT_PLUGIN_URL . 'assets/js/netimport-admin.js?v=' . $version, ENT_QUOTES, 'UTF-8') . '" defer></script>' . "\n";
        }
    }

    public function render_page(?array $result = null, array $selectedOptions = [], array $historyFilters = []): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        $security  = CMS\Security::instance();
        $csrfToken = $security->generateToken('netimport_run');
        $historyCsrfToken = $security->generateToken('netimport_history_action');
        $adminNetimportUrl = htmlspecialchars(SITE_URL . '/admin/netimport', ENT_QUOTES, 'UTF-8');
        $adminRunUrl = htmlspecialchars(SITE_URL . '/admin/netimport/run', ENT_QUOTES, 'UTF-8');
        $adminHistoryActionUrl = htmlspecialchars(SITE_URL . '/admin/netimport/history-action', ENT_QUOTES, 'UTF-8');
        $importer  = CMS_NetImport_Importer::instance();
        $sources   = $importer->get_sources();
        $historyFilters = array_merge([
            'type' => '',
            'mode' => '',
            'errors' => '',
        ], $historyFilters === [] ? $this->read_history_filters() : $historyFilters);
        $history   = $importer->get_run_history(25, $historyFilters);
        $historyStats = $importer->get_history_stats($historyFilters);
        $rowTotal  = array_sum(array_map(static fn(array $source): int => (int) ($source['rows'] ?? 0), $sources));
        $activeTargets = count(array_filter($sources, static fn(array $source): bool => !empty($source['plugin_ready'])));
        $selectedOptions = array_merge([
            'update_existing' => '1',
            'auto_create_companies' => '1',
            'link_relations' => '1',
            'auto_create_event_people' => '1',
            'dry_run' => '0',
        ], $selectedOptions);

        $this->start_admin_layout('NetImport', 'netimport');
        $this->output_admin_assets();
        ?>
        <div class="admin-page-header">
            <div>
                <h2>CMS NetImport</h2>
                <p>Importiert vorbereitete CSV-Daten in Events, Speaker, Companies und Experts.</p>
            </div>
            <div class="header-actions">
                <a href="<?= $adminNetimportUrl ?>" class="btn btn-secondary">Ansicht aktualisieren</a>
            </div>
        </div>

        <?php if ($result !== null): ?>
            <div class="alert <?= !empty($result['errors']) ? 'alert-error' : 'alert-success' ?>">
                Import „<?= htmlspecialchars((string) ($result['type'] ?? 'unbekannt'), ENT_QUOTES, 'UTF-8') ?>“ abgeschlossen –
                Modus: <?= !empty($result['dry_run']) ? 'Dry-Run / Preview' : 'Live-Import' ?>,
                erstellt: <?= (int) ($result['created'] ?? 0) ?>,
                aktualisiert: <?= (int) ($result['updated'] ?? 0) ?>,
                verknüpft: <?= (int) ($result['linked'] ?? 0) ?>,
                übersprungen: <?= (int) ($result['skipped'] ?? 0) ?>,
                Fehler: <?= (int) ($result['errors'] ?? 0) ?>.
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true">CSV</div>
                <div class="stat-number"><?= count($sources) ?></div>
                <div class="stat-label">Vorbereitete Quellen</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true">Σ</div>
                <div class="stat-number"><?= (int) $rowTotal ?></div>
                <div class="stat-label">CSV-Zeilen gesamt</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true">PL</div>
                <div class="stat-number"><?= (int) $activeTargets ?></div>
                <div class="stat-label">Aktive Ziel-Plugins</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" aria-hidden="true">RUN</div>
                <div class="stat-number"><?= (int) ($historyStats['total_runs'] ?? 0) ?></div>
                <div class="stat-label">Gespeicherte Läufe</div>
            </div>
        </div>

        <div class="admin-card ni-card-spacer">
            <h3>Import starten</h3>
            <form method="POST" action="<?= $adminRunUrl ?>" class="admin-form ni-form-grid">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group ni-form-group-wide">
                    <label class="form-label" for="ni_import_type">Datensatz / Aufgabe</label>
                    <select name="import_type" id="ni_import_type" class="form-control">
                        <option value="full">Komplettimport (Companies → MVP Experts → Experts Beispiel → Speaker → Events)</option>
                        <option value="companies_example">Companies Beispiel-CSV</option>
                        <option value="experts_mvps">MVPs.csv → Experts</option>
                        <option value="experts_example">Experts_Beispiel.csv → Experts</option>
                        <option value="speakers">Speaker.csv → Speakers</option>
                        <option value="events">Events_mit_Speaker.csv → Events + Zuordnungen</option>
                    </select>
                </div>

                <div class="form-group ni-form-group-wide">
                    <label class="form-label">Optionen</label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="update_existing" value="1" <?= $selectedOptions['update_existing'] === '1' ? 'checked' : '' ?>>
                        Bestehende Datensätze aktualisieren (Upsert)
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="auto_create_companies" value="1" <?= $selectedOptions['auto_create_companies'] === '1' ? 'checked' : '' ?>>
                        Fehlende Unternehmen beim Import automatisch minimal anlegen
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="link_relations" value="1" <?= $selectedOptions['link_relations'] === '1' ? 'checked' : '' ?>>
                        Beziehungen zwischen Events, Speakern, Experts und Companies verknüpfen
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="auto_create_event_people" value="1" <?= $selectedOptions['auto_create_event_people'] === '1' ? 'checked' : '' ?>>
                        Fehlende Speaker/Experts aus Event-CSV minimal anlegen und direkt verknüpfen
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="dry_run" value="1" <?= $selectedOptions['dry_run'] === '1' ? 'checked' : '' ?>>
                        Nur Vorschau / Dry-Run ausführen (keine Schreibzugriffe)
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= $selectedOptions['dry_run'] === '1' ? 'Vorschau ausführen' : 'Import ausführen' ?></button>
                </div>
            </form>
        </div>

        <div class="admin-card ni-card-spacer">
            <h3>Import-Historie</h3>
            <form method="GET" action="<?= $adminNetimportUrl ?>" class="admin-form ni-form-grid ni-history-filter-form">
                <div class="form-group">
                    <label class="form-label" for="ni_history_type">Typ</label>
                    <select name="history_type" id="ni_history_type" class="form-control">
                        <option value="">Alle</option>
                        <option value="full" <?= $historyFilters['type'] === 'full' ? 'selected' : '' ?>>Komplettimport</option>
                        <option value="companies_example" <?= $historyFilters['type'] === 'companies_example' ? 'selected' : '' ?>>Companies</option>
                        <option value="experts_mvps" <?= $historyFilters['type'] === 'experts_mvps' ? 'selected' : '' ?>>Experts MVPs</option>
                        <option value="experts_example" <?= $historyFilters['type'] === 'experts_example' ? 'selected' : '' ?>>Experts Beispiel</option>
                        <option value="speakers" <?= $historyFilters['type'] === 'speakers' ? 'selected' : '' ?>>Speakers</option>
                        <option value="events" <?= $historyFilters['type'] === 'events' ? 'selected' : '' ?>>Events</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ni_history_mode">Modus</label>
                    <select name="history_mode" id="ni_history_mode" class="form-control">
                        <option value="">Alle</option>
                        <option value="live" <?= $historyFilters['mode'] === 'live' ? 'selected' : '' ?>>Nur Live</option>
                        <option value="dry" <?= $historyFilters['mode'] === 'dry' ? 'selected' : '' ?>>Nur Dry-Run</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="ni_history_errors">Fehler</label>
                    <select name="history_errors" id="ni_history_errors" class="form-control">
                        <option value="">Alle</option>
                        <option value="with_errors" <?= $historyFilters['errors'] === 'with_errors' ? 'selected' : '' ?>>Mit Fehlern</option>
                        <option value="without_errors" <?= $historyFilters['errors'] === 'without_errors' ? 'selected' : '' ?>>Ohne Fehler</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-secondary">Filter anwenden</button>
                    <a href="<?= $adminNetimportUrl ?>" class="btn btn-secondary">Filter zurücksetzen</a>
                </div>
            </form>

            <form method="POST" action="<?= $adminHistoryActionUrl ?>" class="ni-history-action-form"
                  data-confirm-action="true"
                  data-confirm-title="Historie löschen?"
                  data-confirm-message="Diese Aktion entfernt alle gespeicherten Importläufe dauerhaft. Bereits gespeicherte Reports gehen dabei verloren."
                  data-confirm-button="Historie endgültig löschen">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($historyCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="history_action" value="clear_history">
                <?php $this->render_history_filter_inputs($historyFilters); ?>
                <button type="submit" class="btn btn-secondary">Historie löschen</button>
            </form>

            <?php if (empty($history)): ?>
                <div class="empty-state">
                    <p><strong>Noch keine Import-Läufe gespeichert.</strong></p>
                </div>
            <?php else: ?>
                <div class="users-table-container">
                    <table class="users-table ni-table">
                        <thead>
                            <tr>
                                <th>Zeit</th>
                                <th>Quelle</th>
                                <th>Modus</th>
                                <th>Erstellt</th>
                                <th>Aktualisiert</th>
                                <th>Verknüpft</th>
                                <th>Übersprungen</th>
                                <th>Warnungen</th>
                                <th>Fehler</th>
                                <th>Dauer</th>
                                <th>Admin</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($history as $entry): ?>
                            <?php
                            $reportMessages = is_array($entry->report_messages ?? null) ? $entry->report_messages : [];
                            $reportSteps = is_array($entry->report_steps ?? null) ? $entry->report_steps : [];
                            $reportCleanup = is_array($entry->report_cleanup ?? null) ? $entry->report_cleanup : [];
                            $reportResetSummary = is_array($entry->report_reset_summary ?? null) ? $entry->report_reset_summary : [];
                            $createdRecords = is_array($reportCleanup['created_records'] ?? null) ? $reportCleanup['created_records'] : [];
                            $createdLinks = is_array($reportCleanup['created_links'] ?? null) ? $reportCleanup['created_links'] : [];
                            $cleanupRecordCount = 0;
                            foreach ($createdRecords as $recordIds) {
                                $cleanupRecordCount += is_array($recordIds) ? count($recordIds) : 0;
                            }
                            ?>
                            <tr>
                                <td><?= htmlspecialchars(substr((string) ($entry->started_at ?? ''), 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <strong><?= htmlspecialchars((string) ($entry->run_type ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <div><code><?= htmlspecialchars((string) ($entry->source_file ?? ''), ENT_QUOTES, 'UTF-8') ?></code></div>
                                </td>
                                <td>
                                    <span class="status-badge <?= !empty($entry->is_dry_run) ? 'pending' : 'active' ?>">
                                        <?= !empty($entry->is_dry_run) ? 'Dry-Run' : 'Live' ?>
                                    </span>
                                    <?php if (($entry->status ?? 'completed') === 'reset'): ?>
                                        <span class="status-badge pending">Reset</span>
                                    <?php elseif (($entry->status ?? 'completed') === 'completed_with_errors'): ?>
                                        <span class="status-badge danger">Mit Fehlern</span>
                                    <?php endif; ?>
                                    <?php if (($entry->source_mode ?? 'base') === 'update'): ?>
                                        <span class="status-badge pending">UPDATE</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) ($entry->created_count ?? 0) ?></td>
                                <td><?= (int) ($entry->updated_count ?? 0) ?></td>
                                <td><?= (int) ($entry->linked_count ?? 0) ?></td>
                                <td><?= (int) ($entry->skipped_count ?? 0) ?></td>
                                <td><?= (int) ($entry->warning_count ?? 0) ?></td>
                                <td><?= (int) ($entry->error_count ?? 0) ?></td>
                                <td><?= (int) ($entry->duration_ms ?? 0) ?> ms</td>
                                <td><?= htmlspecialchars((string) ($entry->admin_username ?? 'System'), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php if (!empty($entry->is_dry_run)): ?>
                                        <span class="text-muted">Nicht nötig</span>
                                    <?php elseif (($entry->status ?? 'completed') === 'reset'): ?>
                                        <span class="text-muted">Bereits zurückgesetzt</span>
                                    <?php else: ?>
                                            <form method="POST" action="<?= $adminHistoryActionUrl ?>" class="ni-inline-form"
                                                data-confirm-action="true"
                                                data-confirm-title="Importlauf zurücksetzen?"
                                                data-confirm-message="Es werden nur die für diesen Lauf gespeicherten, resetbaren Datensätze und Event-Verknüpfungen entfernt. Diese Aktion kann nicht automatisch rückgängig gemacht werden."
                                                data-confirm-button="Reset jetzt ausführen">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($historyCsrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="history_action" value="reset_run">
                                            <input type="hidden" name="run_id" value="<?= (int) ($entry->id ?? 0) ?>">
                                            <?php $this->render_history_filter_inputs($historyFilters); ?>
                                            <button type="submit" class="btn btn-secondary">Reset</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr class="ni-history-detail-row">
                                <td colspan="12">
                                    <details class="ni-history-details">
                                        <summary>
                                            🔍 Details anzeigen
                                            <span class="ni-history-summary-meta">
                                                Meldungen: <?= (int) ($entry->report_message_count ?? 0) ?> ·
                                                Steps: <?= (int) ($entry->report_step_count ?? 0) ?> ·
                                                Cleanup: <?= (int) $cleanupRecordCount ?> Datensätze / <?= count($createdLinks) ?> Links
                                            </span>
                                        </summary>

                                        <div class="ni-history-panels">
                                            <div class="ni-history-panel">
                                                <h4>Laufdetails</h4>
                                                <ul class="ni-kv-list">
                                                        <li><strong>Status:</strong> <?= htmlspecialchars((string) ($entry->status ?? 'completed'), ENT_QUOTES, 'UTF-8') ?></li>
                                                        <li><strong>Datei:</strong> <code><?= htmlspecialchars((string) ($entry->source_file ?? ''), ENT_QUOTES, 'UTF-8') ?></code></li>
                                                        <li><strong>Quelle:</strong> <?= htmlspecialchars((string) ($entry->source_mode ?? 'base'), ENT_QUOTES, 'UTF-8') ?></li>
                                                        <li><strong>Start:</strong> <?= htmlspecialchars((string) ($entry->started_at ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
                                                        <li><strong>Ende:</strong> <?= htmlspecialchars((string) ($entry->finished_at ?? ''), ENT_QUOTES, 'UTF-8') ?></li>
                                                </ul>
                                                <?php if ($reportResetSummary !== []): ?>
                                                    <div class="ni-inline-note">
                                                        Reset am <?= htmlspecialchars((string) ($reportResetSummary['reset_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?> ·
                                                        entfernte Datensätze: <?= (int) ($reportResetSummary['removed_records'] ?? 0) ?> ·
                                                        entfernte Links: <?= (int) ($reportResetSummary['removed_links'] ?? 0) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($reportSteps !== []): ?>
                                                <div class="ni-history-panel">
                                                    <h4>Teil-Schritte</h4>
                                                    <div class="users-table-container">
                                                        <table class="users-table ni-table ni-subtable">
                                                            <thead>
                                                                <tr>
                                                                    <th>Typ</th>
                                                                    <th>Datei</th>
                                                                    <th>Quelle</th>
                                                                    <th>Erstellt</th>
                                                                    <th>Aktualisiert</th>
                                                                    <th>Verknüpft</th>
                                                                    <th>Warnungen</th>
                                                                    <th>Fehler</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                            <?php foreach ($reportSteps as $step): ?>
                                                                <tr>
                                                                    <td><?= htmlspecialchars((string) ($step['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                                    <td><code><?= htmlspecialchars((string) ($step['file'] ?? ''), ENT_QUOTES, 'UTF-8') ?></code></td>
                                                                    <td><?= htmlspecialchars((string) ($step['source_mode'] ?? 'base'), ENT_QUOTES, 'UTF-8') ?></td>
                                                                    <td><?= (int) ($step['created'] ?? 0) ?></td>
                                                                    <td><?= (int) ($step['updated'] ?? 0) ?></td>
                                                                    <td><?= (int) ($step['linked'] ?? 0) ?></td>
                                                                    <td><?= (int) ($step['warnings'] ?? 0) ?></td>
                                                                    <td><?= (int) ($step['errors'] ?? 0) ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="ni-history-panel">
                                                <h4>Cleanup-Daten</h4>
                                                <ul class="ni-kv-list">
                                                    <li><strong>Companies:</strong> <?= count((array) ($createdRecords['companies'] ?? [])) ?></li>
                                                    <li><strong>Experts:</strong> <?= count((array) ($createdRecords['experts'] ?? [])) ?></li>
                                                    <li><strong>Speakers:</strong> <?= count((array) ($createdRecords['speakers'] ?? [])) ?></li>
                                                    <li><strong>Events:</strong> <?= count((array) ($createdRecords['events'] ?? [])) ?></li>
                                                    <li><strong>Event-Links:</strong> <?= count($createdLinks) ?></li>
                                                </ul>
                                            </div>
                                        </div>

                                        <?php if ($reportMessages !== []): ?>
                                            <div class="ni-history-panel">
                                                <h4>Gespeicherte Meldungen</h4>
                                                <ul class="ni-log-list ni-log-list-compact">
                                                    <?php foreach ($reportMessages as $message): ?>
                                                        <li class="ni-log-item ni-log-item--<?= htmlspecialchars((string) ($message['level'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>">
                                                            <span class="ni-log-level">
                                                                <?php
                                                                $level = $message['level'] ?? 'info';
                                                                echo match ($level) {
                                                                    'error'   => 'Fehler',
                                                                    'warning' => 'Warnung',
                                                                    'success' => 'OK',
                                                                    default   => 'Info',
                                                                };
                                                                ?>
                                                            </span>
                                                            <span><?= htmlspecialchars((string) ($message['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted ni-history-meta">
                    Letzter Lauf: <?= !empty($historyStats['last_run_at']) ? htmlspecialchars(substr((string) $historyStats['last_run_at'], 0, 16), ENT_QUOTES, 'UTF-8') : '—' ?> ·
                    Dry-Runs: <?= (int) ($historyStats['dry_runs'] ?? 0) ?> ·
                    Live-Läufe: <?= (int) ($historyStats['live_runs'] ?? 0) ?> ·
                    Summierte Fehler: <?= (int) ($historyStats['total_errors'] ?? 0) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="admin-card ni-card-spacer">
            <h3>Vorbereitete CSV-Quellen</h3>
            <div class="users-table-container">
                <table class="users-table ni-table">
                    <thead>
                        <tr>
                            <th>Quelle</th>
                            <th>Datei</th>
                            <th>Ziel</th>
                            <th>Zeilen</th>
                            <th>Status</th>
                            <th>Beschreibung</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sources as $source): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string) $source['label'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            <td>
                                <code><?= htmlspecialchars((string) $source['relative_path'], ENT_QUOTES, 'UTF-8') ?></code>
                                <?php if (($source['mode'] ?? 'base') === 'update'): ?>
                                    <div><span class="status-badge pending">Update erkannt</span></div>
                                <?php endif; ?>
                                <?php if (!empty($source['detected_date'])): ?>
                                    <div class="text-muted">Datei-Datum: <?= htmlspecialchars((string) $source['detected_date'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) $source['target_label'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) ($source['rows'] ?? 0) ?></td>
                            <td>
                                <?php if (!empty($source['exists']) && !empty($source['plugin_ready'])): ?>
                                    <span class="status-badge active">Bereit</span>
                                    <?php if (($source['mode'] ?? 'base') === 'update'): ?>
                                        <span class="status-badge pending">UPDATE</span>
                                    <?php endif; ?>
                                <?php elseif (empty($source['exists'])): ?>
                                    <span class="status-badge danger">Datei fehlt</span>
                                <?php else: ?>
                                    <span class="status-badge pending">Plugin inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) $source['description'], ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($result !== null && !empty($result['messages'])): ?>
            <div class="admin-card ni-card-spacer">
                <h3>Import-Protokoll</h3>
                <ul class="ni-log-list">
                    <?php foreach ($result['messages'] as $message): ?>
                        <li class="ni-log-item ni-log-item--<?= htmlspecialchars((string) ($message['level'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?>">
                            <span class="ni-log-level">
                                <?php
                                $level = $message['level'] ?? 'info';
                                echo match ($level) {
                                    'error'   => 'Fehler',
                                    'warning' => 'Warnung',
                                    'success' => 'OK',
                                    default   => 'Info',
                                };
                                ?>
                            </span>
                            <span><?= htmlspecialchars((string) ($message['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="ni-modal-backdrop" id="ni-confirm-backdrop" hidden>
            <div class="ni-modal" role="dialog" aria-modal="true" aria-labelledby="ni-confirm-title" aria-describedby="ni-confirm-message">
                <div class="ni-modal__header">
                    <h3 id="ni-confirm-title">Aktion bestätigen</h3>
                    <button type="button" class="ni-modal__close" data-confirm-close aria-label="Dialog schließen">×</button>
                </div>
                <div class="ni-modal__body">
                    <p id="ni-confirm-message">Bitte bestätige diese Aktion.</p>
                </div>
                <div class="ni-modal__footer">
                    <button type="button" class="btn btn-secondary" data-confirm-close>Abbrechen</button>
                    <button type="button" class="btn btn-primary ni-btn-danger" id="ni-confirm-submit">Aktion ausführen</button>
                </div>
            </div>
        </div>
        <?php
        $this->end_admin_layout();
    }

    private function read_history_filters(): array
    {
        $type = trim((string) ($_POST['history_type'] ?? $_GET['history_type'] ?? ''));
        $mode = trim((string) ($_POST['history_mode'] ?? $_GET['history_mode'] ?? ''));
        $errors = trim((string) ($_POST['history_errors'] ?? $_GET['history_errors'] ?? ''));

        $allowedTypes = array_merge([''], ['full', 'companies_example', 'experts_mvps', 'experts_example', 'speakers', 'events']);

        return [
            'type' => in_array($type, $allowedTypes, true) ? $type : '',
            'mode' => in_array($mode, ['', 'live', 'dry'], true) ? $mode : '',
            'errors' => in_array($errors, ['', 'with_errors', 'without_errors'], true) ? $errors : '',
        ];
    }
}
