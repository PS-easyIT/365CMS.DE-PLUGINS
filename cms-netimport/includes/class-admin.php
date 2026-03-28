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
        CMS\Hooks::addFilter('admin_menu_items', [$this, 'add_menu_item'], 10);
    }

    private function load_admin_menu(): void
    {
        $menuFile = ABSPATH . 'admin/partials/admin-menu.php';
        if (file_exists($menuFile) && !function_exists('renderAdminLayoutStart')) {
            require_once $menuFile;
        }
    }

    public function register_routes($router): void
    {
        $router->addRoute('GET', '/admin/netimport', [$this, 'render_page']);
        $router->addRoute('POST', '/admin/netimport/run', [$this, 'handle_run']);
    }

    public function add_menu_item(array $menuItems): array
    {
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        $menuItems[] = [
            'type'   => 'item',
            'slug'   => 'netimport',
            'label'  => 'NetImport',
            'icon'   => '📥',
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

        $csrfToken = $_POST['csrf_token'] ?? '';
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
        try {
            CMS\Database::instance()->insert('login_attempts', [
                'username'   => 'admin-netimport',
                'ip_address' => CMS\Security::getClientIp(),
                'action'     => 'netimport_run',
            ]);
        } catch (\Throwable $e) {
            error_log('CMS NetImport rate-limit logging failed: ' . $e->getMessage());
        }
    }

    private function output_admin_assets(): void
    {
        $adminCss = CMS_NETIMPORT_PLUGIN_DIR . 'assets/css/netimport-admin.css';
        if (file_exists($adminCss)) {
            $version = (string) filemtime($adminCss);
            echo '<link rel="stylesheet" href="' . CMS_NETIMPORT_PLUGIN_URL . 'assets/css/netimport-admin.css?v=' . $version . '">' . "\n";
        }
    }

    public function render_page(?array $result = null, array $selectedOptions = []): void
    {
        if (!CMS\Auth::instance()->isAdmin()) {
            CMS\Router::instance()->redirect('/login');
            return;
        }

        $this->load_admin_menu();

        $security  = CMS\Security::instance();
        $csrfToken = $security->generateToken('netimport_run');
        $importer  = CMS_NetImport_Importer::instance();
        $sources   = $importer->get_sources();
        $history   = $importer->get_run_history(15);
        $historyStats = $importer->get_history_stats();
        $rowTotal  = array_sum(array_map(static fn(array $source): int => (int) ($source['rows'] ?? 0), $sources));
        $activeTargets = count(array_filter($sources, static fn(array $source): bool => !empty($source['plugin_ready'])));
        $selectedOptions = array_merge([
            'update_existing' => '1',
            'auto_create_companies' => '1',
            'link_relations' => '1',
            'auto_create_event_people' => '1',
            'dry_run' => '0',
        ], $selectedOptions);

        renderAdminLayoutStart('NetImport', 'netimport');
        $this->output_admin_assets();
        ?>
        <div class="admin-page-header">
            <div>
                <h2>📥 CMS NetImport</h2>
                <p>Importiert vorbereitete CSV-Daten in Events, Speaker, Companies und Experts.</p>
            </div>
            <div class="header-actions">
                <a href="<?= SITE_URL ?>/admin/netimport" class="btn btn-secondary">🔄 Ansicht aktualisieren</a>
            </div>
        </div>

        <?php if ($result !== null): ?>
            <div class="alert <?= !empty($result['errors']) ? 'alert-error' : 'alert-success' ?>">
                <?= !empty($result['errors']) ? '❌' : (!empty($result['dry_run']) ? '🧪' : '✅') ?>
                Import „<?= htmlspecialchars((string) ($result['type'] ?? 'unbekannt')) ?>“ abgeschlossen –
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
                <div class="stat-icon">🗂️</div>
                <div class="stat-number"><?= count($sources) ?></div>
                <div class="stat-label">Vorbereitete Quellen</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📄</div>
                <div class="stat-number"><?= (int) $rowTotal ?></div>
                <div class="stat-label">CSV-Zeilen gesamt</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🧩</div>
                <div class="stat-number"><?= (int) $activeTargets ?></div>
                <div class="stat-label">Aktive Ziel-Plugins</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🕘</div>
                <div class="stat-number"><?= (int) ($historyStats['total_runs'] ?? 0) ?></div>
                <div class="stat-label">Gespeicherte Läufe</div>
            </div>
        </div>

        <div class="admin-card ni-card-spacer">
            <h3>🚀 Import starten</h3>
            <form method="POST" action="<?= SITE_URL ?>/admin/netimport/run" class="admin-form ni-form-grid">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES) ?>">

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
                    <button type="submit" class="btn btn-primary"><?= $selectedOptions['dry_run'] === '1' ? '🧪 Vorschau ausführen' : '📥 Import ausführen' ?></button>
                </div>
            </form>
        </div>

        <div class="admin-card ni-card-spacer">
            <h3>🗃️ Import-Historie</h3>
            <?php if (empty($history)): ?>
                <div class="empty-state">
                    <p style="font-size:2.5rem;margin:0;">📝</p>
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
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($history as $entry): ?>
                            <tr>
                                <td><?= htmlspecialchars(substr((string) ($entry->started_at ?? ''), 0, 16)) ?></td>
                                <td>
                                    <strong><?= htmlspecialchars((string) ($entry->run_type ?? '')) ?></strong>
                                    <div><code><?= htmlspecialchars((string) ($entry->source_file ?? '')) ?></code></div>
                                </td>
                                <td>
                                    <span class="status-badge <?= !empty($entry->is_dry_run) ? 'pending' : 'active' ?>">
                                        <?= !empty($entry->is_dry_run) ? '🧪 Dry-Run' : '🚀 Live' ?>
                                    </span>
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
                                <td><?= htmlspecialchars((string) ($entry->admin_username ?? 'System')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted" style="margin-top:12px;">
                    Letzter Lauf: <?= !empty($historyStats['last_run_at']) ? htmlspecialchars(substr((string) $historyStats['last_run_at'], 0, 16)) : '—' ?> ·
                    Dry-Runs: <?= (int) ($historyStats['dry_runs'] ?? 0) ?> ·
                    Live-Läufe: <?= (int) ($historyStats['live_runs'] ?? 0) ?> ·
                    Summierte Fehler: <?= (int) ($historyStats['total_errors'] ?? 0) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="admin-card ni-card-spacer">
            <h3>🗃️ Vorbereitete CSV-Quellen</h3>
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
                            <td><strong><?= htmlspecialchars((string) $source['label']) ?></strong></td>
                            <td>
                                <code><?= htmlspecialchars((string) $source['relative_path']) ?></code>
                                <?php if (($source['mode'] ?? 'base') === 'update'): ?>
                                    <div><span class="status-badge pending">🆕 UPDATE erkannt</span></div>
                                <?php endif; ?>
                                <?php if (!empty($source['detected_date'])): ?>
                                    <div class="text-muted">Datei-Datum: <?= htmlspecialchars((string) $source['detected_date']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) $source['target_label']) ?></td>
                            <td><?= (int) ($source['rows'] ?? 0) ?></td>
                            <td>
                                <?php if (!empty($source['exists']) && !empty($source['plugin_ready'])): ?>
                                    <span class="status-badge active">✅ Bereit</span>
                                    <?php if (($source['mode'] ?? 'base') === 'update'): ?>
                                        <span class="status-badge pending">UPDATE</span>
                                    <?php endif; ?>
                                <?php elseif (empty($source['exists'])): ?>
                                    <span class="status-badge danger">❌ Datei fehlt</span>
                                <?php else: ?>
                                    <span class="status-badge pending">⏳ Plugin inaktiv</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string) $source['description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($result !== null && !empty($result['messages'])): ?>
            <div class="admin-card ni-card-spacer">
                <h3>📋 Import-Protokoll</h3>
                <ul class="ni-log-list">
                    <?php foreach ($result['messages'] as $message): ?>
                        <li class="ni-log-item ni-log-item--<?= htmlspecialchars((string) ($message['level'] ?? 'info'), ENT_QUOTES) ?>">
                            <span class="ni-log-level">
                                <?php
                                $level = $message['level'] ?? 'info';
                                echo match ($level) {
                                    'error'   => '❌',
                                    'warning' => '⚠️',
                                    'success' => '✅',
                                    default   => 'ℹ️',
                                };
                                ?>
                            </span>
                            <span><?= htmlspecialchars((string) ($message['text'] ?? '')) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php
        renderAdminLayoutEnd();
    }
}
