<?php
/**
 * Import-Engine für CMS NetImport.
 *
 * @package CMS_NetImport
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_NetImport_Importer', false)) {
    return;
}

final class CMS_NetImport_Importer
{
    private static ?self $instance = null;

    private const MAX_FILE_SIZE = 10485760; // 10 MB
    private const MAX_PREVIEW_MESSAGES = 200;
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

    /** @var array<string, array<string, mixed>> */
    private array $sourceDefinitions = [
        'companies_example' => [
            'label' => 'Companies Beispiel',
            'canonical_file' => 'Companies_Beispiel.csv',
            'target_plugin' => 'cms-companies',
            'target_class' => 'CMS_Companies_Database',
            'target_label' => 'CMS Companies',
            'description' => 'Kuratiertes Firmen-Beispiel mit realen Unternehmen aus Netzwerkdaten.',
            'required_headers' => ['name'],
        ],
        'experts_mvps' => [
            'label' => 'MVPs → Experts',
            'canonical_file' => 'MVPs.csv',
            'target_plugin' => 'cms-experts',
            'target_class' => 'CMS_Experts_Database',
            'target_label' => 'CMS Experts',
            'description' => 'Importiert MVP-/Award-Träger in das Experten-Plugin.',
            'required_headers' => ['vorname', 'nachname'],
        ],
        'experts_example' => [
            'label' => 'Experts Beispiel',
            'canonical_file' => 'Experts_Beispiel.csv',
            'target_plugin' => 'cms-experts',
            'target_class' => 'CMS_Experts_Database',
            'target_label' => 'CMS Experts',
            'description' => 'Importiert zusätzliche Nicht-MVP-Experten.',
            'required_headers' => ['first_name', 'last_name'],
        ],
        'speakers' => [
            'label' => 'Speaker-Import',
            'canonical_file' => 'Speaker.csv',
            'target_plugin' => 'cms-speakers',
            'target_class' => 'CMS_Speakers_Database',
            'target_label' => 'CMS Speakers',
            'description' => 'Importiert Speaker-Profile inklusive Topics.',
            'required_headers' => ['vorname', 'nachname'],
        ],
        'events' => [
            'label' => 'Events mit Speaker-Zuordnung',
            'canonical_file' => 'Events_mit_Speaker.csv',
            'target_plugin' => 'cms-events',
            'target_class' => 'CMS_Events_Database',
            'target_label' => 'CMS Events',
            'description' => 'Importiert Events und verknüpft vorhandene Speaker/Experts.',
            'required_headers' => ['event_name', 'wann'],
        ],
    ];

    /** @var array<string, array<string, mixed>> */
    private array $csvCache = [];

    private ?int $adminUserIdCache = null;

    private function __construct()
    {
        $this->ensure_storage();
    }

    /** @var array<string, int> */
    private array $companyCache = [];
    /** @var array<string, int> */
    private array $expertCache = [];
    /** @var array<string, int> */
    private array $speakerCache = [];
    /** @var array<string, int> */
    private array $eventCache = [];
    /** @var array<string, bool> */
    private array $assignmentCache = [];
    /** @var array<string, int> */
    private array $simulatedIdCache = [];
    private int $simulatedIdSequence = 1000000000;
    /** @var list<string> */
    private const ALLOWED_TABLES = [
        'netimport_runs',
        'users',
        'companies',
        'experts',
        'speakers',
        'events',
        'event_speakers',
    ];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function log_error(string $message, ?\Throwable $exception = null): void
    {
        $safeMessage = preg_replace('/\s+/', ' ', trim($message)) ?? 'Unknown error';
        if ($exception !== null) {
            $exceptionMessage = preg_replace('/\s+/', ' ', trim($exception->getMessage())) ?? 'Unknown exception';
            $safeMessage .= ' | ' . $exceptionMessage;
        }

        error_log('[cms-netimport] ' . $safeMessage);
    }

    private function validated_prefix($db): string
    {
        $prefix = (string) $db->prefix();
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
            throw new \RuntimeException('Invalid database table prefix');
        }

        return $prefix;
    }

    private function table(string $table): string
    {
        if (!in_array($table, self::ALLOWED_TABLES, true)) {
            throw new \InvalidArgumentException('Unsupported table requested: ' . $table);
        }

        $db = CMS\Database::instance();
        return $this->validated_prefix($db) . $table;
    }

    public function ensure_storage(): void
    {
        if (!class_exists('CMS\\Database')) {
            return;
        }

        try {
            $db = CMS\Database::instance();
            $pdo = $db->getPdo();
            $prefix = $this->validated_prefix($db);
            $pdo->exec("CREATE TABLE IF NOT EXISTS {$prefix}netimport_runs (
                id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id          INT UNSIGNED DEFAULT NULL,
                run_type         VARCHAR(50) NOT NULL,
                source_file      VARCHAR(255) DEFAULT NULL,
                source_mode      VARCHAR(20) NOT NULL DEFAULT 'base',
                is_dry_run       TINYINT(1) NOT NULL DEFAULT 0,
                status           VARCHAR(20) NOT NULL DEFAULT 'completed',
                created_count    INT UNSIGNED NOT NULL DEFAULT 0,
                updated_count    INT UNSIGNED NOT NULL DEFAULT 0,
                linked_count     INT UNSIGNED NOT NULL DEFAULT 0,
                skipped_count    INT UNSIGNED NOT NULL DEFAULT 0,
                warning_count    INT UNSIGNED NOT NULL DEFAULT 0,
                error_count      INT UNSIGNED NOT NULL DEFAULT 0,
                started_at       DATETIME NOT NULL,
                finished_at      DATETIME NOT NULL,
                duration_ms      INT UNSIGNED NOT NULL DEFAULT 0,
                options_json     LONGTEXT DEFAULT NULL,
                report_json      LONGTEXT DEFAULT NULL,
                created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_type (run_type),
                INDEX idx_started (started_at),
                INDEX idx_user (user_id),
                INDEX idx_status (status),
                INDEX idx_dry_run (is_dry_run)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        } catch (\Throwable $e) {
            $this->log_error('Storage initialization failed', $e);
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function get_sources(): array
    {
        $sources = [];
        foreach ($this->sourceDefinitions as $key => $definition) {
            $resolved = $this->resolve_source_file((string) $definition['canonical_file']);
            $sources[$key] = array_merge($definition, $resolved, [
                'plugin_ready' => $this->is_plugin_ready((string) $definition['target_plugin'], (string) $definition['target_class']),
                'rows' => !empty($resolved['exists']) ? $this->count_csv_rows((string) $resolved['path']) : 0,
            ]);
        }

        return $sources;
    }

    /**
     * @param array<string, string> $options
     * @return array<string, mixed>
     */
    public function run_import(string $type, array $options = []): array
    {
        $this->reset_caches();
        $startedAt = microtime(true);
        $startedAtSql = date('Y-m-d H:i:s');

        $options = array_merge([
            'update_existing' => '1',
            'auto_create_companies' => '1',
            'link_relations' => '1',
            'auto_create_event_people' => '1',
            'dry_run' => '0',
            '_internal' => '0',
        ], $options);

        $sourceForReport = [
            'selected_file' => '',
            'mode' => 'base',
        ];

        if ($type === 'full') {
            $result = $this->run_full_import($options);
            $sourceForReport = [
                'selected_file' => (string) ($result['file'] ?? 'multiple'),
                'mode' => (string) ($result['source_mode'] ?? 'base'),
            ];
            if ($options['_internal'] !== '1') {
                $this->persist_run_report($result, $options, $sourceForReport, $startedAt, $startedAtSql);
            }
            return $result;
        }

        if (!isset($this->sourceDefinitions[$type])) {
            $result = $this->create_result($type, '');
            $this->add_message($result, 'error', 'Unbekannter Import-Typ: ' . $type);
            $result['errors']++;
            if ($options['_internal'] !== '1') {
                $this->persist_run_report($result, $options, $sourceForReport, $startedAt, $startedAtSql);
            }
            return $result;
        }

        $definition = $this->sourceDefinitions[$type];
        $source = $this->resolve_source_file((string) $definition['canonical_file']);
        $sourceForReport = $source;
        $result = $this->create_result($type, (string) ($source['selected_file'] ?? ''));
        $result['dry_run'] = $options['dry_run'] === '1';
        $result['source_mode'] = $source['mode'] ?? 'base';

        if (empty($source['exists'])) {
            $result['errors']++;
            $this->add_message($result, 'error', 'Quelldatei nicht gefunden: ' . (string) $definition['canonical_file']);
            if ($options['_internal'] !== '1') {
                $this->persist_run_report($result, $options, $sourceForReport, $startedAt, $startedAtSql);
            }
            return $result;
        }

        if (!$this->is_plugin_ready((string) $definition['target_plugin'], (string) $definition['target_class'])) {
            $result['errors']++;
            $this->add_message($result, 'error', 'Ziel-Plugin nicht aktiv oder nicht geladen: ' . (string) $definition['target_label']);
            if ($options['_internal'] !== '1') {
                $this->persist_run_report($result, $options, $sourceForReport, $startedAt, $startedAtSql);
            }
            return $result;
        }

        $validationError = $this->validate_source_file((string) $source['path']);
        if ($validationError !== null) {
            $result['errors']++;
            $this->add_message($result, 'error', $validationError);
            if ($options['_internal'] !== '1') {
                $this->persist_run_report($result, $options, $sourceForReport, $startedAt, $startedAtSql);
            }
            return $result;
        }

        $csvPayload = $this->get_csv_payload((string) $source['path']);
        if (($csvPayload['validation_error'] ?? null) !== null) {
            $result['errors']++;
            $this->add_message($result, 'error', (string) $csvPayload['validation_error']);
            if ($options['_internal'] !== '1') {
                $this->persist_run_report($result, $options, $sourceForReport, $startedAt, $startedAtSql);
            }
            return $result;
        }

        $headerError = $this->validate_required_headers(
            (array) ($csvPayload['headers'] ?? []),
            (array) ($definition['required_headers'] ?? [])
        );
        if ($headerError !== null) {
            $result['errors']++;
            $this->add_message($result, 'error', $headerError);
            if ($options['_internal'] !== '1') {
                $this->persist_run_report($result, $options, $sourceForReport, $startedAt, $startedAtSql);
            }
            return $result;
        }

        if (($source['mode'] ?? 'base') === 'update') {
            $this->add_message($result, 'info', 'Neuere CSV-Datei erkannt: ' . (string) $source['selected_file'] . ' wird als UPDATE-Quelle verwendet.');
        }

        $executor = function () use ($type, $options, $source, &$result): void {
            switch ($type) {
                case 'companies_example':
                    $this->import_companies_example((string) $source['path'], $options, $result);
                    break;
                case 'experts_mvps':
                    $this->import_experts_mvp_csv((string) $source['path'], $options, $result);
                    break;
                case 'experts_example':
                    $this->import_experts_example_csv((string) $source['path'], $options, $result);
                    break;
                case 'speakers':
                    $this->import_speakers_csv((string) $source['path'], $options, $result);
                    break;
                case 'events':
                    $this->import_events_csv((string) $source['path'], $options, $result);
                    break;
            }
        };

        if ($options['dry_run'] === '1') {
            $executor();
        } else {
            $this->run_in_transaction($executor, $result);
        }

        if ($options['_internal'] !== '1') {
            $this->persist_run_report($result, $options, $sourceForReport, $startedAt, $startedAtSql);
        }

        return $result;
    }

    /**
     * @param array<string, string> $options
     * @return array<string, mixed>
     */
    private function run_full_import(array $options): array
    {
        $aggregate = $this->create_result('full', 'multiple');
        $aggregate['dry_run'] = $options['dry_run'] === '1';
        $aggregate['steps'] = [];

        foreach (['companies_example', 'experts_mvps', 'experts_example', 'speakers', 'events'] as $step) {
            $stepOptions = $options;
            $stepOptions['_internal'] = '1';
            $result = $this->run_import($step, $stepOptions);
            $this->merge_result($aggregate, $result);
            $aggregate['steps'][] = [
                'type' => $result['type'] ?? $step,
                'file' => $result['file'] ?? '',
                'created' => (int) ($result['created'] ?? 0),
                'updated' => (int) ($result['updated'] ?? 0),
                'linked' => (int) ($result['linked'] ?? 0),
                'skipped' => (int) ($result['skipped'] ?? 0),
                'warnings' => (int) ($result['warnings'] ?? 0),
                'errors' => (int) ($result['errors'] ?? 0),
                'source_mode' => $result['source_mode'] ?? 'base',
            ];

            if (($result['source_mode'] ?? 'base') === 'update') {
                $aggregate['source_mode'] = 'update';
            }
        }

        return $aggregate;
    }

    /**
     * @return array<int, object>
     */
    public function get_run_history(int $limit = 20, array $filters = []): array
    {
        $this->ensure_storage();
        $limit = max(1, min(100, $limit));
        try {
            $db = CMS\Database::instance();
            $filterData = $this->build_history_filters($filters);
            $runsTable = $this->table('netimport_runs');
            $usersTable = $this->table('users');
            $stmt = $db->prepare(
                "SELECT nr.*, u.username AS admin_username
                 FROM {$runsTable} nr
                 LEFT JOIN {$usersTable} u ON nr.user_id = u.id
                 {$filterData['whereSql']}
                 ORDER BY nr.started_at DESC, nr.id DESC
                 LIMIT {$limit}"
            );
            $stmt->execute($filterData['params']);
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            $this->log_error('Fetching import history failed', $e);
            return [];
        }

        $normalizedRows = [];
        foreach ($rows as $entry) {
            $entryData = is_object($entry) ? get_object_vars($entry) : (is_array($entry) ? $entry : []);
            $entryObject = (object) $entryData;
            $reportData = $this->safe_json_decode((string) ($entryData['report_json'] ?? ''));
            $entryObject->report_data = $reportData;
            $entryObject->report_messages = is_array($reportData['messages'] ?? null) ? $reportData['messages'] : [];
            $entryObject->report_steps = is_array($reportData['steps'] ?? null) ? $reportData['steps'] : [];
            $entryObject->report_cleanup = is_array($reportData['cleanup_data'] ?? null) ? $reportData['cleanup_data'] : [];
            $entryObject->report_reset_summary = is_array($reportData['reset_summary'] ?? null) ? $reportData['reset_summary'] : [];
            $entryObject->report_message_count = count($entryObject->report_messages);
            $entryObject->report_step_count = count($entryObject->report_steps);
            $normalizedRows[] = $entryObject;
        }

        return $normalizedRows;
    }

    /**
     * @return array<string, int|string|null>
     */
    public function get_history_stats(array $filters = []): array
    {
        $this->ensure_storage();
        try {
            $db = CMS\Database::instance();
            $filterData = $this->build_history_filters($filters);
            $runsTable = $this->table('netimport_runs');
            $stmt = $db->prepare(
                "SELECT COUNT(*) AS total_runs,
                        SUM(CASE WHEN is_dry_run = 1 THEN 1 ELSE 0 END) AS dry_runs,
                        SUM(CASE WHEN is_dry_run = 0 THEN 1 ELSE 0 END) AS live_runs,
                        SUM(error_count) AS total_errors,
                        MAX(started_at) AS last_run_at
                 FROM {$runsTable} nr
                 {$filterData['whereSql']}"
            );
            $stmt->execute($filterData['params']);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            $this->log_error('Fetching history stats failed', $e);
            $row = [];
        }

        return [
            'total_runs' => (int) ($row['total_runs'] ?? 0),
            'dry_runs' => (int) ($row['dry_runs'] ?? 0),
            'live_runs' => (int) ($row['live_runs'] ?? 0),
            'total_errors' => (int) ($row['total_errors'] ?? 0),
            'last_run_at' => $row['last_run_at'] ?? null,
        ];
    }

    /**
     * @return array{whereSql:string,params:array<int,mixed>}
     */
    private function build_history_filters(array $filters): array
    {
        $where = [];
        $params = [];

        $type = (string) ($filters['type'] ?? '');
        $allowedTypes = array_merge(array_keys($this->sourceDefinitions), ['full']);
        if ($type !== '' && in_array($type, $allowedTypes, true)) {
            $where[] = 'nr.run_type = ?';
            $params[] = $type;
        }

        $mode = (string) ($filters['mode'] ?? '');
        if ($mode === 'dry') {
            $where[] = 'nr.is_dry_run = 1';
        } elseif ($mode === 'live') {
            $where[] = 'nr.is_dry_run = 0';
        }

        $errors = (string) ($filters['errors'] ?? '');
        if ($errors === 'with_errors') {
            $where[] = 'nr.error_count > 0';
        } elseif ($errors === 'without_errors') {
            $where[] = 'nr.error_count = 0';
        }

        return [
            'whereSql' => $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '',
            'params' => $params,
        ];
    }

    public function clear_run_history(): int
    {
        $this->ensure_storage();

        try {
            $db = CMS\Database::instance();
            $runsTable = $this->table('netimport_runs');
            $stmt = $db->prepare("DELETE FROM {$runsTable}");
            $stmt->execute();
            return (int) $stmt->rowCount();
        } catch (\Throwable $e) {
            $this->log_error('Clearing history failed', $e);
            return 0;
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    public function get_run_entry(int $runId): ?array
    {
        $this->ensure_storage();
        try {
            $db = CMS\Database::instance();
            $runsTable = $this->table('netimport_runs');
            $stmt = $db->prepare("SELECT * FROM {$runsTable} WHERE id = ? LIMIT 1");
            $stmt->execute([$runId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (\Throwable $e) {
            $this->log_error('Reading run entry failed', $e);
            return null;
        }
    }

    public function reset_run(int $runId): array
    {
        $this->ensure_storage();
        $entry = $this->get_run_entry($runId);
        $summary = [
            'success' => false,
            'removed_records' => 0,
            'removed_links' => 0,
            'message' => 'Reset konnte nicht durchgeführt werden.',
        ];

        if ($entry === null) {
            $summary['message'] = 'Import-Lauf nicht gefunden.';
            return $summary;
        }

        if (($entry['status'] ?? '') === 'reset') {
            $summary['message'] = 'Import-Lauf wurde bereits zurückgesetzt.';
            return $summary;
        }

        $report = $this->safe_json_decode((string) ($entry['report_json'] ?? ''));
        $cleanupData = is_array($report['cleanup_data'] ?? null) ? $report['cleanup_data'] : [];
        $createdRecords = is_array($cleanupData['created_records'] ?? null) ? $cleanupData['created_records'] : [];
        $createdLinks = is_array($cleanupData['created_links'] ?? null) ? $cleanupData['created_links'] : [];

        if ($createdRecords === [] && $createdLinks === []) {
            $summary['message'] = 'Für diesen Lauf sind keine resetbaren Datensätze gespeichert.';
            return $summary;
        }

        $db = CMS\Database::instance();
        $pdo = $db->getPdo();

        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }

            foreach ($createdLinks as $link) {
                if ($this->remove_event_link(
                    (int) ($link['event_id'] ?? 0),
                    (int) ($link['speaker_id'] ?? 0),
                    (string) ($link['speaker_type'] ?? 'speaker')
                )) {
                    $summary['removed_links']++;
                }
            }

            foreach (['events', 'speakers', 'experts', 'companies'] as $type) {
                foreach (($createdRecords[$type] ?? []) as $recordId) {
                    if ($this->remove_created_record($type, (int) $recordId)) {
                        $summary['removed_records']++;
                    }
                }
            }

            $updatedReport = is_array($report) ? $report : [];
            $updatedReport['reset_summary'] = [
                'reset_at' => date('Y-m-d H:i:s'),
                'removed_records' => $summary['removed_records'],
                'removed_links' => $summary['removed_links'],
            ];
            $updatedReport['cleanup_data'] = [
                'created_records' => [],
                'created_links' => [],
            ];

            $db->update('netimport_runs', [
                'status' => 'reset',
                'report_json' => $this->safe_json_encode($updatedReport),
            ], ['id' => $runId]);

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            $summary['success'] = true;
            $summary['message'] = 'Import-Lauf wurde zurückgesetzt. Entfernte Datensätze: ' . $summary['removed_records'] . ', entfernte Links: ' . $summary['removed_links'] . '.';
            return $summary;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $this->log_error('Resetting import run failed', $e);
            $summary['message'] = 'Reset fehlgeschlagen. Details wurden im Server-Log protokolliert.';
            return $summary;
        }
    }

    private function remove_event_link(int $eventId, int $speakerId, string $speakerType): bool
    {
        if ($eventId <= 0 || $speakerId <= 0) {
            return false;
        }

        try {
            $db = CMS\Database::instance();
            $eventSpeakersTable = $this->table('event_speakers');
            $stmt = $db->prepare("DELETE FROM {$eventSpeakersTable} WHERE event_id = ? AND speaker_id = ? AND speaker_type = ?");
            $stmt->execute([$eventId, $speakerId, $speakerType]);
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            $this->log_error('Removing event link failed', $e);
            return false;
        }
    }

    private function remove_created_record(string $type, int $recordId): bool
    {
        if ($recordId <= 0) {
            return false;
        }

        return match ($type) {
            'events' => $this->is_plugin_ready('cms-events', 'CMS_Events_Database') ? CMS_Events_Database::instance()->delete_event($recordId) : false,
            'speakers' => $this->is_plugin_ready('cms-speakers', 'CMS_Speakers_Database') ? CMS_Speakers_Database::instance()->delete_speaker($recordId) : false,
            'experts' => $this->is_plugin_ready('cms-experts', 'CMS_Experts_Database') ? CMS_Experts_Database::instance()->delete_expert($recordId) : false,
            'companies' => $this->is_plugin_ready('cms-companies', 'CMS_Companies_Database') ? CMS_Companies_Database::instance()->set_company_status($recordId, 'deleted') : false,
            default => false,
        };
    }

    /**
     * @param array<string, string> $options
     * @param array<string, mixed> $result
     */
    private function import_companies_example(string $path, array $options, array &$result): void
    {
        $rows = $this->read_csv($path);
        $db = CMS_Companies_Database::instance();
        $updateExisting = $options['update_existing'] === '1';
        $dryRun = $options['dry_run'] === '1';

        foreach ($rows as $row) {
            $name = $this->value($row, ['name', 'firma', 'company']);
            if ($name === '') {
                $result['skipped']++;
                continue;
            }

            $website = $this->sanitize_url($this->value($row, ['website', 'url']));
            $existingId = $this->find_company_id($name, $website);
            if ($existingId > 0 && !$updateExisting) {
                $result['skipped']++;
                $this->add_message($result, 'info', 'Unternehmen übersprungen: ' . $name);
                continue;
            }

            $payload = [
                'user_id' => $this->get_admin_user_id(),
                'name' => $name,
                'email' => $this->value($row, ['email']),
                'phone' => $this->value($row, ['phone']),
                'industry' => $this->value($row, ['industry']),
                'company_size' => $this->value($row, ['company_size']),
                'description' => $this->value($row, ['description']),
                'website' => $website,
                'location_city' => $this->value($row, ['city', 'location_city']),
                'location_zip' => $this->value($row, ['zip', 'location_zip']),
                'location_country' => $this->value($row, ['country', 'location_country']) ?: 'Deutschland',
                'founded_year' => $this->normalize_int($this->value($row, ['founded_year'])) ?: null,
                'employee_count' => $this->normalize_int($this->value($row, ['employee_count'])) ?: null,
                'is_partner' => $this->normalize_bool($this->value($row, ['is_partner'])) ? 1 : 0,
                'is_top_partner' => $this->normalize_bool($this->value($row, ['is_top_partner'])) ? 1 : 0,
                'is_sponsor' => $this->normalize_bool($this->value($row, ['is_sponsor'])) ? 1 : 0,
                'status' => $this->normalize_status($this->value($row, ['status']), ['active', 'inactive', 'pending', 'deleted'], 'active'),
            ];

            if ($dryRun) {
                if ($existingId > 0) {
                    $result['updated']++;
                    $this->add_message($result, 'success', 'DRY-RUN: Unternehmen würde aktualisiert: ' . $name);
                } else {
                    $cacheKey = $this->company_cache_key($name, $website);
                    $simulated = $this->claim_simulated_id('company', $cacheKey);
                    $this->companyCache[$cacheKey] = $simulated['id'];
                    if ($simulated['is_new']) {
                        $result['created']++;
                        $this->add_message($result, 'success', 'DRY-RUN: Unternehmen würde angelegt: ' . $name);
                    } else {
                        $result['updated']++;
                        $this->add_message($result, 'info', 'DRY-RUN: Unternehmen würde innerhalb dieses Laufs erneut referenziert: ' . $name);
                    }
                }
                continue;
            }

            if ($existingId > 0) {
                $payload['id'] = $existingId;
            }

            $savedId = (int) $db->save_company($payload);
            if ($savedId > 0) {
                $this->assign_admin_ownership('companies', $savedId);
                $this->companyCache[$this->company_cache_key($name, $website)] = $savedId;
                if ($existingId > 0) {
                    $result['updated']++;
                    $this->add_message($result, 'success', 'Unternehmen aktualisiert: ' . $name);
                } else {
                    $result['created']++;
                    $this->track_created_record($result, 'companies', $savedId);
                    $this->add_message($result, 'success', 'Unternehmen importiert: ' . $name);
                }
            } else {
                $result['errors']++;
                $this->add_message($result, 'error', 'Unternehmen konnte nicht gespeichert werden: ' . $name);
            }
        }
    }

    /**
     * @param array<string, string> $options
     * @param array<string, mixed> $result
     */
    private function import_experts_mvp_csv(string $path, array $options, array &$result): void
    {
        $rows = $this->read_csv($path);
        $db = CMS_Experts_Database::instance();
        $updateExisting = $options['update_existing'] === '1';
        $autoCreateCompanies = $options['auto_create_companies'] === '1';
        $linkRelations = $options['link_relations'] === '1';
        $dryRun = $options['dry_run'] === '1';

        foreach ($rows as $row) {
            $firstName = $this->value($row, ['vorname', 'first_name']);
            $lastName = $this->value($row, ['nachname', 'last_name']);
            if ($firstName === '' || $lastName === '') {
                $result['skipped']++;
                continue;
            }

            $existingId = $this->find_expert_id($firstName, $lastName);
            if ($existingId > 0 && !$updateExisting) {
                $result['skipped']++;
                $this->add_message($result, 'info', 'Expert übersprungen: ' . $firstName . ' ' . $lastName);
                continue;
            }

            $companyName = $this->value($row, ['firma', 'company']);
            $website = $this->sanitize_url($this->value($row, ['website']));
            $topInWas = $this->value($row, ['top_in_was']);
            $awardType = $this->value($row, ['award_typ', 'award_type']);
            $category = $this->value($row, ['kategorie', 'category']);
            $certifications = $this->value($row, ['zertifikate', 'certifications']);
            $events = $this->value($row, ['typische_events', 'events']);
            $companyId = $this->ensure_company_reference($companyName, $website, $autoCreateCompanies, $dryRun, $result);
            $skillGroups = $this->group_skill_terms(array_merge(
                $this->split_topics($topInWas),
                $this->split_topics($category)
            ));

            if ($dryRun) {
                if ($existingId > 0) {
                    $result['updated']++;
                    $this->add_message($result, 'success', 'DRY-RUN: MVP-Expert würde aktualisiert: ' . $firstName . ' ' . $lastName);
                } else {
                    $cacheKey = $this->person_cache_key($firstName, $lastName);
                    $simulated = $this->claim_simulated_id('expert', $cacheKey);
                    $this->expertCache[$cacheKey] = $simulated['id'];
                    if ($simulated['is_new']) {
                        $result['created']++;
                        $this->add_message($result, 'success', 'DRY-RUN: MVP-Expert würde angelegt: ' . $firstName . ' ' . $lastName);
                    } else {
                        $result['updated']++;
                        $this->add_message($result, 'info', 'DRY-RUN: MVP-Expert würde innerhalb dieses Laufs erneut referenziert: ' . $firstName . ' ' . $lastName);
                    }
                }
                if ($linkRelations && $companyId > 0) {
                    $result['linked']++;
                    $this->add_message($result, 'info', 'DRY-RUN: Company↔Expert-Verknüpfung würde gesetzt: ' . $companyName);
                }
                continue;
            }

            $payload = [
                'user_id' => $this->get_admin_user_id(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => '',
                'phone' => '',
                'mobile' => '',
                'position' => $topInWas,
                'company' => $companyName,
                'biography' => $this->truncate_text($this->build_expert_bio($firstName, $lastName, $topInWas, $awardType, $events), 4000),
                'photo_url' => '',
                'location_country' => 'Deutschland',
                'availability' => 'available',
                'experience_years' => 0,
                'status' => 'active',
            ];
            if ($existingId > 0) {
                $payload['id'] = $existingId;
            }

            $savedId = (int) $db->save_expert($payload);
            if ($savedId <= 0) {
                $result['errors']++;
                $this->add_message($result, 'error', 'Expert konnte nicht gespeichert werden: ' . $firstName . ' ' . $lastName);
                continue;
            }

            $this->assign_admin_ownership('experts', $savedId);
            $this->expertCache[$this->person_cache_key($firstName, $lastName)] = $savedId;
            $db->save_expert_skills($savedId, $skillGroups);
            $db->save_meta($savedId, 'is_mvp', stripos($awardType, 'mvp') !== false ? '1' : '0');
            $db->save_meta($savedId, 'custom_award', $awardType);
            $db->save_meta($savedId, 'mvp_category', $category);
            $db->save_meta($savedId, 'import_typical_events', $events);
            if ($website !== '') {
                $db->save_meta($savedId, 'social_website', $website);
            }
            if ($certifications !== '') {
                $db->save_meta($savedId, 'import_certifications', $certifications);
            }
            if ($companyId > 0) {
                $db->save_meta($savedId, 'company_id', (string) $companyId);
                if ($linkRelations && $this->is_plugin_ready('cms-companies', 'CMS_Companies_Database')) {
                    CMS_Companies_Database::instance()->assign_expert($companyId, $savedId, $topInWas ?: null, true);
                    $result['linked']++;
                }
            }

            if ($existingId > 0) {
                $result['updated']++;
                $this->add_message($result, 'success', 'MVP-Expert aktualisiert: ' . $firstName . ' ' . $lastName);
            } else {
                $result['created']++;
                $this->track_created_record($result, 'experts', $savedId);
                $this->add_message($result, 'success', 'MVP-Expert importiert: ' . $firstName . ' ' . $lastName);
            }
        }
    }

    /**
     * @param array<string, string> $options
     * @param array<string, mixed> $result
     */
    private function import_experts_example_csv(string $path, array $options, array &$result): void
    {
        $rows = $this->read_csv($path);
        $db = CMS_Experts_Database::instance();
        $updateExisting = $options['update_existing'] === '1';
        $autoCreateCompanies = $options['auto_create_companies'] === '1';
        $linkRelations = $options['link_relations'] === '1';
        $dryRun = $options['dry_run'] === '1';

        foreach ($rows as $row) {
            $firstName = $this->value($row, ['first_name', 'vorname']);
            $lastName = $this->value($row, ['last_name', 'nachname']);
            if ($firstName === '' || $lastName === '') {
                $result['skipped']++;
                continue;
            }

            $existingId = $this->find_expert_id($firstName, $lastName);
            if ($existingId > 0 && !$updateExisting) {
                $result['skipped']++;
                $this->add_message($result, 'info', 'Expert übersprungen: ' . $firstName . ' ' . $lastName);
                continue;
            }

            $companyName = $this->value($row, ['company', 'firma']);
            $website = $this->sanitize_url($this->value($row, ['website']));
            $position = $this->value($row, ['position']);
            $availability = $this->normalize_status($this->value($row, ['availability']), ['available', 'limited', 'booked'], 'available');
            $status = $this->normalize_status($this->value($row, ['status']), ['active', 'inactive', 'pending', 'deleted'], 'active');
            $companyId = $this->ensure_company_reference($companyName, $website, $autoCreateCompanies, $dryRun, $result);
            $skillGroups = [
                'general' => $this->split_topics($this->value($row, ['skills_general'])),
                'tech' => $this->split_topics($this->value($row, ['skills_tech'])),
                'soft' => $this->split_topics($this->value($row, ['skills_soft'])),
            ];
            $awards = $this->value($row, ['awards']);
            $certifications = $this->value($row, ['certifications']);

            if ($dryRun) {
                if ($existingId > 0) {
                    $result['updated']++;
                    $this->add_message($result, 'success', 'DRY-RUN: Expert würde aktualisiert: ' . $firstName . ' ' . $lastName);
                } else {
                    $cacheKey = $this->person_cache_key($firstName, $lastName);
                    $simulated = $this->claim_simulated_id('expert', $cacheKey);
                    $this->expertCache[$cacheKey] = $simulated['id'];
                    if ($simulated['is_new']) {
                        $result['created']++;
                        $this->add_message($result, 'success', 'DRY-RUN: Expert würde angelegt: ' . $firstName . ' ' . $lastName);
                    } else {
                        $result['updated']++;
                        $this->add_message($result, 'info', 'DRY-RUN: Expert würde innerhalb dieses Laufs erneut referenziert: ' . $firstName . ' ' . $lastName);
                    }
                }
                if ($linkRelations && $companyId > 0) {
                    $result['linked']++;
                    $this->add_message($result, 'info', 'DRY-RUN: Company↔Expert-Verknüpfung würde gesetzt: ' . $companyName);
                }
                continue;
            }

            $payload = [
                'user_id' => $this->get_admin_user_id(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => '',
                'phone' => '',
                'mobile' => '',
                'position' => $position,
                'company' => $companyName,
                'biography' => $this->truncate_text($this->value($row, ['biography']) ?: $this->build_expert_bio($firstName, $lastName, $position, $awards, ''), 4000),
                'photo_url' => '',
                'location_city' => $this->value($row, ['city', 'location_city']),
                'location_country' => $this->value($row, ['country', 'location_country']) ?: 'Deutschland',
                'hourly_rate' => $this->normalize_float($this->value($row, ['hourly_rate'])) ?: null,
                'daily_rate' => $this->normalize_float($this->value($row, ['daily_rate'])) ?: null,
                'availability' => $availability,
                'experience_years' => $this->normalize_int($this->value($row, ['experience_years'])) ?: 0,
                'status' => $status,
            ];
            if ($existingId > 0) {
                $payload['id'] = $existingId;
            }

            $savedId = (int) $db->save_expert($payload);
            if ($savedId <= 0) {
                $result['errors']++;
                $this->add_message($result, 'error', 'Expert konnte nicht gespeichert werden: ' . $firstName . ' ' . $lastName);
                continue;
            }

            $this->assign_admin_ownership('experts', $savedId);
            $this->expertCache[$this->person_cache_key($firstName, $lastName)] = $savedId;
            $db->save_expert_skills($savedId, $skillGroups);
            if ($website !== '') {
                $db->save_meta($savedId, 'social_website', $website);
            }
            if ($awards !== '') {
                $db->save_meta($savedId, 'custom_award', $awards);
                if (stripos($awards, 'mvp') !== false) {
                    $db->save_meta($savedId, 'is_mvp', '1');
                }
            }
            if ($certifications !== '') {
                $db->save_meta($savedId, 'import_certifications', $certifications);
            }
            if ($companyId > 0) {
                $db->save_meta($savedId, 'company_id', (string) $companyId);
                if ($linkRelations && $this->is_plugin_ready('cms-companies', 'CMS_Companies_Database')) {
                    CMS_Companies_Database::instance()->assign_expert($companyId, $savedId, $position ?: null, true);
                    $result['linked']++;
                }
            }

            if ($existingId > 0) {
                $result['updated']++;
                $this->add_message($result, 'success', 'Expert aktualisiert: ' . $firstName . ' ' . $lastName);
            } else {
                $result['created']++;
                $this->track_created_record($result, 'experts', $savedId);
                $this->add_message($result, 'success', 'Expert importiert: ' . $firstName . ' ' . $lastName);
            }
        }
    }

    /**
     * @param array<string, string> $options
     * @param array<string, mixed> $result
     */
    private function import_speakers_csv(string $path, array $options, array &$result): void
    {
        $rows = $this->read_csv($path);
        $db = CMS_Speakers_Database::instance();
        $updateExisting = $options['update_existing'] === '1';
        $autoCreateCompanies = $options['auto_create_companies'] === '1';
        $dryRun = $options['dry_run'] === '1';

        foreach ($rows as $row) {
            $firstName = $this->value($row, ['vorname', 'first_name']);
            $lastName = $this->value($row, ['nachname', 'last_name']);
            if ($firstName === '' || $lastName === '' || $firstName === '---' || $lastName === '---') {
                $result['skipped']++;
                continue;
            }

            $existingId = $this->find_speaker_id($firstName, $lastName);
            if ($existingId > 0 && !$updateExisting) {
                $result['skipped']++;
                $this->add_message($result, 'info', 'Speaker übersprungen: ' . $firstName . ' ' . $lastName);
                continue;
            }

            $companyName = $this->value($row, ['firma', 'company']);
            $website = $this->sanitize_url($this->value($row, ['website']));
            $topics = $this->split_topics($this->value($row, ['top_in_was']));
            $awards = $this->combine_non_empty([
                $this->value($row, ['mvp_auszeichnung', 'award']),
                $this->value($row, ['zertifikate', 'certifications']),
            ]);
            $events = $this->value($row, ['event_s', 'events']);
            $companyId = $this->ensure_company_reference($companyName, $website, $autoCreateCompanies, $dryRun, $result);

            if ($dryRun) {
                if ($existingId > 0) {
                    $result['updated']++;
                    $this->add_message($result, 'success', 'DRY-RUN: Speaker würde aktualisiert: ' . $firstName . ' ' . $lastName);
                } else {
                    $cacheKey = $this->person_cache_key($firstName, $lastName);
                    $simulated = $this->claim_simulated_id('speaker', $cacheKey);
                    $this->speakerCache[$cacheKey] = $simulated['id'];
                    if ($simulated['is_new']) {
                        $result['created']++;
                        $this->add_message($result, 'success', 'DRY-RUN: Speaker würde angelegt: ' . $firstName . ' ' . $lastName);
                    } else {
                        $result['updated']++;
                        $this->add_message($result, 'info', 'DRY-RUN: Speaker würde innerhalb dieses Laufs erneut referenziert: ' . $firstName . ' ' . $lastName);
                    }
                }
                continue;
            }

            $payload = [
                'user_id' => $this->get_admin_user_id(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'position' => implode(' / ', $topics),
                'company' => $companyName,
                'company_id' => $companyId > 0 ? $companyId : null,
                'email' => '',
                'phone' => '',
                'bio' => $this->truncate_text($this->build_speaker_bio($firstName, $lastName, implode(', ', $topics), $events), 4000),
                'short_bio' => $this->truncate_text($this->build_speaker_bio($firstName, $lastName, implode(', ', $topics), $events), 580),
                'website' => $website,
                'location_country' => 'Deutschland',
                'formats' => $this->safe_json_encode($this->infer_speaker_formats($events), '[]'),
                'recognitions' => $this->safe_json_encode($awards, '[]'),
                'skills' => $this->safe_json_encode($topics, '[]'),
                'availability' => 'available',
                'status' => 'active',
                'travel_radius' => 'national',
                'is_verified' => $awards !== [] ? 1 : 0,
            ];

            $savedId = $db->save_speaker($payload, $existingId);
            if ($savedId === false || (int) $savedId <= 0) {
                $result['errors']++;
                $this->add_message($result, 'error', 'Speaker konnte nicht gespeichert werden: ' . $firstName . ' ' . $lastName);
                continue;
            }

            $savedId = (int) $savedId;
            $this->assign_admin_ownership('speakers', $savedId);
            $this->speakerCache[$this->person_cache_key($firstName, $lastName)] = $savedId;
            $db->save_topics($savedId, $topics);

            if ($existingId > 0) {
                $result['updated']++;
                $this->add_message($result, 'success', 'Speaker aktualisiert: ' . $firstName . ' ' . $lastName);
            } else {
                $result['created']++;
                $this->track_created_record($result, 'speakers', $savedId);
                $this->add_message($result, 'success', 'Speaker importiert: ' . $firstName . ' ' . $lastName);
            }
        }
    }

    /**
     * @param array<string, string> $options
     * @param array<string, mixed> $result
     */
    private function import_events_csv(string $path, array $options, array &$result): void
    {
        $rows = $this->read_csv($path);
        $grouped = [];
        $unparsedDateSamples = [];
        foreach ($rows as $row) {
            $title = $this->value($row, ['event_name', 'title']);
            $eventDate = $this->parse_german_date($this->value($row, ['wann', 'event_date']));
            if ($title === '' || $eventDate === null) {
                $result['skipped']++;
                $rawDate = $this->value($row, ['wann', 'event_date']);
                if ($rawDate !== '' && count($unparsedDateSamples) < 8) {
                    $unparsedDateSamples[] = $rawDate;
                }
                continue;
            }

            $location = $this->value($row, ['ort', 'location']);
            $groupKey = hash('sha256', $this->normalize_lookup_value($title) . '|' . $eventDate . '|' . $this->normalize_lookup_value($location));
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'title' => $title,
                    'event_date' => $eventDate,
                    'end_date' => $this->parse_german_date($this->value($row, ['bis_wann', 'end_date'])),
                    'location' => $location,
                    'city' => $this->infer_city($location),
                    'organizer_name' => $this->value($row, ['veranstalter', 'organizer']),
                    'website' => $this->sanitize_url($this->value($row, ['website'])),
                    'event_art' => $this->value($row, ['event_art']),
                    'price_raw' => $this->value($row, ['preis']),
                    'topics' => [],
                    'participants' => [],
                ];
            }

            $topic = $this->value($row, ['thema_kategorie', 'category']);
            if ($topic !== '') {
                $grouped[$groupKey]['topics'][] = $topic;
            }

            $firstName = $this->value($row, ['vorname', 'first_name']);
            $lastName = $this->value($row, ['nachname', 'last_name']);
            if ($firstName !== '' && $lastName !== '' && $firstName !== '---' && $lastName !== '---') {
                $grouped[$groupKey]['participants'][] = [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'topic' => $topic,
                    'award' => $this->value($row, ['mvp_auszeichnung', 'award']),
                    'company' => $this->value($row, ['firma', 'company']),
                ];
            }
        }

        $db = CMS_Events_Database::instance();
        $updateExisting = $options['update_existing'] === '1';
        $linkRelations = $options['link_relations'] === '1';
        $dryRun = $options['dry_run'] === '1';

        if ($unparsedDateSamples !== []) {
            $this->add_message(
                $result,
                'warning',
                'Einige Datumsangaben konnten nicht eindeutig aufgelöst werden und wurden übersprungen: '
                . implode(', ', array_unique($unparsedDateSamples))
            );
        }

        foreach ($grouped as $eventData) {
            try {
                $eventTitle = $this->truncate_text((string) ($eventData['title'] ?? ''), 240);
                if ($eventTitle === '') {
                    $result['skipped']++;
                    continue;
                }

                $existingId = $this->find_event_id($eventTitle, (string) $eventData['event_date']);
                if ($existingId > 0 && !$updateExisting) {
                    $result['skipped']++;
                    $this->add_message($result, 'info', 'Event übersprungen: ' . $eventTitle);
                    continue;
                }

                [$priceType, $priceValue] = $this->parse_price_fields((string) $eventData['price_raw']);
                $payload = [
                    'title' => $eventTitle,
                    'excerpt' => $this->truncate_text($this->build_event_excerpt((string) $eventData['organizer_name'], (string) $eventData['location'], (array) $eventData['topics']), 480),
                    'description' => $this->truncate_text($this->build_event_description($eventData), 4000),
                    'event_date' => $eventData['event_date'],
                    'end_date' => $eventData['end_date'],
                    'location' => $this->truncate_text((string) ($eventData['location'] ?? ''), 240),
                    'city' => $this->truncate_text((string) ($eventData['city'] ?? ''), 100),
                    'country' => 'Deutschland',
                    'category' => $this->truncate_text($this->infer_event_category((string) $eventData['event_art'], $eventTitle, (array) $eventData['topics']), 100),
                    'tags' => $this->limit_tags(array_merge((array) $eventData['topics'], [$eventData['event_art']])),
                    'registration_url' => $eventData['website'],
                    'organizer_name' => $this->truncate_text((string) ($eventData['organizer_name'] ?? ''), 240),
                    'organizer_website' => $eventData['website'],
                    'is_online' => $this->is_online_location((string) $eventData['location']) ? 1 : 0,
                    'online_url' => $this->is_online_location((string) $eventData['location']) ? $eventData['website'] : '',
                    'price_type' => $priceType,
                    'price' => $priceValue,
                    'status' => 'published',
                ];

                $savedId = $existingId;
                if ($dryRun) {
                    if ($existingId > 0) {
                        $result['updated']++;
                        $this->add_message($result, 'success', 'DRY-RUN: Event würde aktualisiert: ' . $eventTitle);
                    } else {
                        $cacheKey = $this->event_cache_key($eventTitle, (string) $eventData['event_date']);
                        $simulated = $this->claim_simulated_id('event', $cacheKey);
                        $this->eventCache[$cacheKey] = $simulated['id'];
                        $savedId = $simulated['id'];
                        if ($simulated['is_new']) {
                            $result['created']++;
                            $this->add_message($result, 'success', 'DRY-RUN: Event würde angelegt: ' . $eventTitle);
                        } else {
                            $result['updated']++;
                            $this->add_message($result, 'info', 'DRY-RUN: Event würde innerhalb dieses Laufs erneut referenziert: ' . $eventTitle);
                        }
                    }
                } else {
                    if ($existingId > 0) {
                        $payload['id'] = $existingId;
                    }
                    $savedId = (int) $db->save_event($payload);
                    if ($savedId <= 0) {
                        $result['errors']++;
                        $this->add_message($result, 'error', 'Event konnte nicht gespeichert werden: ' . $eventTitle);
                        continue;
                    }

                    $this->assign_admin_ownership('events', $savedId);
                    $this->eventCache[$this->event_cache_key($eventTitle, (string) $eventData['event_date'])] = $savedId;
                    if ($existingId > 0) {
                        $result['updated']++;
                        $this->add_message($result, 'success', 'Event aktualisiert: ' . $eventTitle);
                    } else {
                        $result['created']++;
                        $this->track_created_record($result, 'events', $savedId);
                        $this->add_message($result, 'success', 'Event importiert: ' . $eventTitle);
                    }
                }

                if ($linkRelations && $savedId > 0) {
                    foreach ((array) $eventData['participants'] as $participant) {
                        $this->link_event_participant((int) $savedId, $participant, $dryRun, $result, $options);
                    }
                }
            } catch (\Throwable $e) {
                $result['errors']++;
                $this->add_message(
                    $result,
                    'error',
                    'Event-Importfehler bei "' . (string) ($eventData['title'] ?? 'unbekannt') . '". Details wurden im Server-Log protokolliert.'
                );
                $this->log_error('Event row import failed', $e);
            }
        }
    }

    /**
     * @param array<string, string> $participant
     * @param array<string, mixed> $result
     */
    private function link_event_participant(int $eventId, array $participant, bool $dryRun, array &$result, array $options = []): void
    {
        $firstName = $participant['first_name'] ?? '';
        $lastName = $participant['last_name'] ?? '';
        if ($firstName === '' || $lastName === '') {
            return;
        }

        try {
            $preferExpert = stripos((string) ($participant['award'] ?? ''), 'mvp') !== false
                || stripos((string) ($participant['award'] ?? ''), 'microsoft') !== false;

            $expertId = $this->find_expert_id($firstName, $lastName);
            $speakerId = $this->find_speaker_id($firstName, $lastName);
            $entityId = 0;
            $entityType = 'speaker';

            if ($preferExpert && $expertId > 0) {
                $entityId = $expertId;
                $entityType = 'expert';
            } elseif ($speakerId > 0) {
                $entityId = $speakerId;
            } elseif ($expertId > 0) {
                $entityId = $expertId;
                $entityType = 'expert';
            }

            if ($entityId <= 0 && (($options['auto_create_event_people'] ?? '1') === '1')) {
                $resolved = $this->create_missing_event_participant($participant, $preferExpert, $dryRun, $result, $options);
                $entityId = (int) ($resolved['id'] ?? 0);
                $entityType = (string) ($resolved['type'] ?? $entityType);
            }

            if ($entityId <= 0) {
                $result['warnings']++;
                $this->add_message($result, 'warning', 'Keine verknüpfbare Person gefunden: ' . $firstName . ' ' . $lastName);
                return;
            }

            $assignmentKey = $eventId . '|' . $entityId . '|' . $entityType;
            if ($this->assignment_exists($assignmentKey)) {
                return;
            }

            if ($dryRun) {
                $this->assignmentCache[$assignmentKey] = true;
                $result['linked']++;
                $this->add_message($result, 'info', 'DRY-RUN: Event-Verknüpfung würde erstellt: ' . $firstName . ' ' . $lastName . ' → Event #' . $eventId);
                return;
            }

            $linked = CMS_Events_Database::instance()->assign_speaker($eventId, $entityId, $entityType, [
                'presentation_title' => !empty($participant['topic']) ? $participant['topic'] : null,
                'role' => 'Speaker',
            ]);
            if ($linked) {
                $this->assignmentCache[$assignmentKey] = true;
                $result['linked']++;
                $this->track_created_link($result, $eventId, $entityId, $entityType);
                $this->add_message($result, 'success', 'Event-Verknüpfung erstellt: ' . $firstName . ' ' . $lastName . ' → Event #' . $eventId);
            } else {
                $result['warnings']++;
                $this->add_message($result, 'warning', 'Event-Verknüpfung fehlgeschlagen: ' . $firstName . ' ' . $lastName);
            }
        } catch (\Throwable $e) {
            $result['warnings']++;
            $this->add_message($result, 'warning', 'Event-Verknüpfung übersprungen (' . $firstName . ' ' . $lastName . '). Details wurden im Server-Log protokolliert.');
            $this->log_error('Participant link skipped', $e);
        }
    }

    /**
     * @param array<string, string> $participant
     * @param array<string, mixed> $result
     * @param array<string, string> $options
     * @return array{id:int,type:string}
     */
    private function create_missing_event_participant(array $participant, bool $preferExpert, bool $dryRun, array &$result, array $options): array
    {
        $firstName = trim((string) ($participant['first_name'] ?? ''));
        $lastName = trim((string) ($participant['last_name'] ?? ''));
        $topic = trim((string) ($participant['topic'] ?? ''));
        $companyName = trim((string) ($participant['company'] ?? ''));

        if ($firstName === '' || $lastName === '') {
            return ['id' => 0, 'type' => $preferExpert ? 'expert' : 'speaker'];
        }

        $expertsReady = $this->is_plugin_ready('cms-experts', 'CMS_Experts_Database');
        $speakersReady = $this->is_plugin_ready('cms-speakers', 'CMS_Speakers_Database');

        if ($preferExpert && !$expertsReady && $speakersReady) {
            $preferExpert = false;
        }

        if (!$expertsReady && !$speakersReady) {
            return ['id' => 0, 'type' => $preferExpert ? 'expert' : 'speaker'];
        }

        $companyId = 0;
        if ($companyName !== '' && (($options['auto_create_companies'] ?? '1') === '1')) {
            $companyId = $this->ensure_company_reference($companyName, '', true, $dryRun, $result);
        }

        if ($preferExpert) {
            if (!$expertsReady) {
                return ['id' => 0, 'type' => 'expert'];
            }

            if ($dryRun) {
                $cacheKey = $this->person_cache_key($firstName, $lastName);
                $simulated = $this->claim_simulated_id('expert', $cacheKey);
                $this->expertCache[$cacheKey] = $simulated['id'];
                if ($simulated['is_new']) {
                    $result['created']++;
                    $this->add_message($result, 'info', 'DRY-RUN: Fehlender Event-Expert würde minimal angelegt: ' . $firstName . ' ' . $lastName);
                }
                return ['id' => $simulated['id'], 'type' => 'expert'];
            }

            $expertId = (int) CMS_Experts_Database::instance()->save_expert([
                'user_id' => $this->get_admin_user_id(),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => '',
                'phone' => '',
                'mobile' => '',
                'position' => $topic !== '' ? $topic : 'Speaker',
                'company' => $companyName,
                'biography' => $this->truncate_text($this->build_expert_bio($firstName, $lastName, $topic, (string) ($participant['award'] ?? ''), ''), 4000),
                'location_country' => 'Deutschland',
                'availability' => 'available',
                'experience_years' => 0,
                'status' => 'active',
            ]);

            if ($expertId > 0) {
                $this->assign_admin_ownership('experts', $expertId);
                $this->expertCache[$this->person_cache_key($firstName, $lastName)] = $expertId;
                if ($topic !== '') {
                    CMS_Experts_Database::instance()->save_expert_skills($expertId, $this->group_skill_terms([$topic]));
                }
                if ($companyId > 0 && $this->is_plugin_ready('cms-companies', 'CMS_Companies_Database')) {
                    CMS_Experts_Database::instance()->save_meta($expertId, 'company_id', (string) $companyId);
                    CMS_Companies_Database::instance()->assign_expert($companyId, $expertId, $topic !== '' ? $topic : null, true);
                }
                $result['created']++;
                $this->track_created_record($result, 'experts', $expertId);
                $this->add_message($result, 'success', 'Event-Expert automatisch angelegt: ' . $firstName . ' ' . $lastName);
                return ['id' => $expertId, 'type' => 'expert'];
            }

            return ['id' => 0, 'type' => 'expert'];
        }

        if (!$speakersReady) {
            return ['id' => 0, 'type' => 'speaker'];
        }

        if ($dryRun) {
            $cacheKey = $this->person_cache_key($firstName, $lastName);
            $simulated = $this->claim_simulated_id('speaker', $cacheKey);
            $this->speakerCache[$cacheKey] = $simulated['id'];
            if ($simulated['is_new']) {
                $result['created']++;
                $this->add_message($result, 'info', 'DRY-RUN: Fehlender Event-Speaker würde minimal angelegt: ' . $firstName . ' ' . $lastName);
            }
            return ['id' => $simulated['id'], 'type' => 'speaker'];
        }

        $speakerId = CMS_Speakers_Database::instance()->save_speaker([
            'user_id' => $this->get_admin_user_id(),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'position' => $topic,
            'company' => $companyName,
            'company_id' => $companyId > 0 ? $companyId : null,
            'email' => '',
            'phone' => '',
            'bio' => $this->truncate_text($this->build_speaker_bio($firstName, $lastName, $topic, ''), 4000),
            'short_bio' => $this->truncate_text($this->build_speaker_bio($firstName, $lastName, $topic, ''), 580),
            'location_country' => 'Deutschland',
            'availability' => 'available',
            'status' => 'active',
        ]);

        $speakerId = (int) ($speakerId ?: 0);
        if ($speakerId > 0) {
            $this->assign_admin_ownership('speakers', $speakerId);
            $this->speakerCache[$this->person_cache_key($firstName, $lastName)] = $speakerId;
            if ($topic !== '') {
                CMS_Speakers_Database::instance()->save_topics($speakerId, [$topic]);
            }
            $result['created']++;
            $this->track_created_record($result, 'speakers', $speakerId);
            $this->add_message($result, 'success', 'Event-Speaker automatisch angelegt: ' . $firstName . ' ' . $lastName);
        }

        return ['id' => $speakerId, 'type' => 'speaker'];
    }

    /**
     * @param array<string, mixed> $eventData
     */
    private function build_event_description(array $eventData): string
    {
        $parts = [];
        $parts[] = (string) $eventData['title'] . ' findet am ' . date('d.m.Y', strtotime((string) $eventData['event_date'])) . '.';
        if (!empty($eventData['end_date']) && $eventData['end_date'] !== $eventData['event_date']) {
            $parts[] = 'Das Event läuft bis zum ' . date('d.m.Y', strtotime((string) $eventData['end_date'])) . '.';
        }
        if (!empty($eventData['location'])) {
            $parts[] = 'Ort: ' . $eventData['location'] . '.';
        }
        if (!empty($eventData['organizer_name'])) {
            $parts[] = 'Veranstalter: ' . $eventData['organizer_name'] . '.';
        }
        if (!empty($eventData['topics'])) {
            $parts[] = 'Themenschwerpunkte: ' . implode(', ', $this->limit_tags((array) $eventData['topics'], 6)) . '.';
        }
        return implode(' ', $parts);
    }

    /**
     * @param list<string> $topics
     */
    private function build_event_excerpt(string $organizer, string $location, array $topics): string
    {
        $parts = [];
        if ($organizer !== '') {
            $parts[] = 'Veranstalter: ' . $organizer;
        }
        if ($location !== '') {
            $parts[] = 'Ort: ' . $location;
        }
        if ($topics !== []) {
            $parts[] = 'Themen: ' . implode(', ', $this->limit_tags($topics, 3));
        }
        return implode(' · ', $parts);
    }

    private function build_speaker_bio(string $firstName, string $lastName, string $topic, string $events): string
    {
        $parts = [trim($firstName . ' ' . $lastName)];
        if ($topic !== '') {
            $parts[] = 'spricht über ' . $topic;
        }
        if ($events !== '') {
            $parts[] = 'und ist im Quelldatensatz mit folgenden Events verknüpft: ' . $events;
        }
        return rtrim(implode(' ', $parts), '.') . '.';
    }

    private function build_expert_bio(string $firstName, string $lastName, string $topic, string $award, string $events): string
    {
        $parts = [trim($firstName . ' ' . $lastName)];
        if ($topic !== '') {
            $parts[] = 'fokussiert sich auf ' . $topic;
        }
        if ($award !== '') {
            $parts[] = 'und ist im Quelldatensatz mit „' . $award . '“ markiert';
        }
        if ($events !== '') {
            $parts[] = '– typische Event-Kontexte: ' . $events;
        }
        return rtrim(implode(' ', $parts), '.') . '.';
    }

    /**
     * @return array{0:string,1:float|null}
     */
    private function parse_price_fields(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['free', null];
        }
        $lower = mb_strtolower($value, 'UTF-8');
        if (str_contains($lower, 'spende') || str_contains($lower, 'donation')) {
            return ['donation', null];
        }
        if (str_contains($lower, 'kostenlos') || str_contains($lower, 'free')) {
            return ['free', null];
        }
        if (preg_match('/(\d+[\.,]?\d*)/', $value, $matches) === 1) {
            return ['paid', $this->normalize_float($matches[1])];
        }
        return ['paid', null];
    }

    /**
     * @param list<string> $topics
     */
    private function infer_event_category(string $eventArt, string $title, array $topics): string
    {
        if (trim($eventArt) !== '') {
            return trim($eventArt);
        }
        $haystack = mb_strtolower($title . ' ' . implode(' ', $topics), 'UTF-8');
        if (str_contains($haystack, 'webinar')) {
            return 'Webinar';
        }
        if (str_contains($haystack, 'workshop') || str_contains($haystack, 'bootcamp')) {
            return 'Workshop';
        }
        if (str_contains($haystack, 'summit') || str_contains($haystack, 'conference') || str_contains($haystack, 'konferenz') || str_contains($haystack, 'kongress')) {
            return 'Konferenz';
        }
        if (str_contains($haystack, 'messe') || str_contains($haystack, 'expo')) {
            return 'Messe';
        }
        return 'Event';
    }

    /**
     * @return list<string>
     */
    private function infer_speaker_formats(string $events): array
    {
        $formats = ['keynote'];
        $haystack = mb_strtolower($events, 'UTF-8');
        if (str_contains($haystack, 'workshop')) {
            $formats[] = 'workshop';
        }
        if (str_contains($haystack, 'webinar') || str_contains($haystack, 'online') || str_contains($haystack, 'virtuell')) {
            $formats[] = 'webinar';
        }
        if (str_contains($haystack, 'panel')) {
            $formats[] = 'panel';
        }
        return array_values(array_unique($formats));
    }

    private function is_online_location(string $location): bool
    {
        $location = mb_strtolower($location, 'UTF-8');
        return str_contains($location, 'virtuell') || str_contains($location, 'online');
    }

    private function infer_city(string $location): string
    {
        $location = trim($location);
        if ($location === '' || $this->is_online_location($location)) {
            return '';
        }
        if (str_contains($location, ',')) {
            $parts = array_values(array_filter(array_map('trim', explode(',', $location))));
            return (string) end($parts);
        }
        if (preg_match('/^([^\(\/]+)/u', $location, $matches) === 1) {
            return trim($matches[1]);
        }
        return $location;
    }

    private function run_in_transaction(callable $callback, array &$result): void
    {
        $db = CMS\Database::instance();
        $pdo = $db->getPdo();
        try {
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }
            $callback();
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $result['errors']++;
            $this->add_message($result, 'error', 'Import abgebrochen. Details wurden im Server-Log protokolliert.');
            $this->log_error('Transaction failed', $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function create_result(string $type, string $file): array
    {
        return [
            'type' => $type,
            'file' => $file,
            'created' => 0,
            'updated' => 0,
            'linked' => 0,
            'skipped' => 0,
            'errors' => 0,
            'warnings' => 0,
            'dry_run' => false,
            'source_mode' => 'base',
            'steps' => [],
            'cleanup_data' => [
                'created_records' => [],
                'created_links' => [],
            ],
            'messages' => [],
        ];
    }

    /**
     * @param array<string,mixed> $result
     */
    private function track_created_record(array &$result, string $type, int $recordId): void
    {
        if ($recordId <= 0) {
            return;
        }
        $result['cleanup_data']['created_records'][$type] ??= [];
        if (!in_array($recordId, $result['cleanup_data']['created_records'][$type], true)) {
            $result['cleanup_data']['created_records'][$type][] = $recordId;
        }
    }

    /**
     * @param array<string,mixed> $result
     */
    private function track_created_link(array &$result, int $eventId, int $speakerId, string $speakerType): void
    {
        if ($eventId <= 0 || $speakerId <= 0) {
            return;
        }

        $result['cleanup_data']['created_links'] ??= [];
        foreach ($result['cleanup_data']['created_links'] as $link) {
            if (
                (int) ($link['event_id'] ?? 0) === $eventId
                && (int) ($link['speaker_id'] ?? 0) === $speakerId
                && (string) ($link['speaker_type'] ?? 'speaker') === $speakerType
            ) {
                return;
            }
        }

        $result['cleanup_data']['created_links'][] = [
            'event_id' => $eventId,
            'speaker_id' => $speakerId,
            'speaker_type' => $speakerType,
        ];
    }

    /**
     * @param array<string, mixed> $result
     */
    private function add_message(array &$result, string $level, string $text): void
    {
        $result['messages'] = is_array($result['messages'] ?? null) ? $result['messages'] : [];
        if (count($result['messages']) >= self::MAX_PREVIEW_MESSAGES) {
            return;
        }

        $allowedLevels = ['info', 'success', 'warning', 'error'];
        if (!in_array($level, $allowedLevels, true)) {
            $level = 'info';
        }

        $text = $this->truncate_text($text, 2000);
        $result['messages'][] = [
            'level' => $level,
            'text' => $text,
        ];
    }

    /**
     * @param array<string, mixed> $aggregate
     * @param array<string, mixed> $incoming
     */
    private function merge_result(array &$aggregate, array $incoming): void
    {
        foreach (['created', 'updated', 'linked', 'skipped', 'errors', 'warnings'] as $key) {
            $aggregate[$key] = (int) $aggregate[$key] + (int) ($incoming[$key] ?? 0);
        }
        foreach (($incoming['cleanup_data']['created_records'] ?? []) as $type => $recordIds) {
            $aggregate['cleanup_data']['created_records'][$type] ??= [];
            foreach ((array) $recordIds as $recordId) {
                $recordId = (int) $recordId;
                if ($recordId > 0 && !in_array($recordId, $aggregate['cleanup_data']['created_records'][$type], true)) {
                    $aggregate['cleanup_data']['created_records'][$type][] = $recordId;
                }
            }
        }
        foreach (($incoming['cleanup_data']['created_links'] ?? []) as $link) {
            $eventId = (int) ($link['event_id'] ?? 0);
            $speakerId = (int) ($link['speaker_id'] ?? 0);
            $speakerType = (string) ($link['speaker_type'] ?? 'speaker');
            if ($eventId <= 0 || $speakerId <= 0) {
                continue;
            }
            $this->track_created_link($aggregate, $eventId, $speakerId, $speakerType);
        }
        foreach ($incoming['messages'] ?? [] as $message) {
            $this->add_message($aggregate, (string) ($message['level'] ?? 'info'), (string) ($message['text'] ?? ''));
        }
    }

    /**
     * @param array<string, mixed> $result
     * @param array<string, string> $options
     * @param array<string, mixed> $source
     */
    private function persist_run_report(array $result, array $options, array $source, float $startedAt, string $startedAtSql): void
    {
        $this->ensure_storage();

        try {
            $db = CMS\Database::instance();
            $finishedAtSql = date('Y-m-d H:i:s');
            $durationMs = max(0, (int) round((microtime(true) - $startedAt) * 1000));
            $optionsForStorage = $options;
            unset($optionsForStorage['_internal']);

            $reportPayload = [
                'messages' => $result['messages'] ?? [],
                'steps' => $result['steps'] ?? [],
                'file' => $result['file'] ?? '',
                'cleanup_data' => $result['cleanup_data'] ?? ['created_records' => [], 'created_links' => []],
            ];

            $db->insert('netimport_runs', [
                'user_id' => $this->get_admin_user_id(),
                'run_type' => $result['type'] ?? 'unknown',
                'source_file' => (string) ($source['selected_file'] ?? ($result['file'] ?? '')),
                'source_mode' => (string) ($result['source_mode'] ?? ($source['mode'] ?? 'base')),
                'is_dry_run' => !empty($result['dry_run']) ? 1 : 0,
                'status' => !empty($result['errors']) ? 'completed_with_errors' : 'completed',
                'created_count' => (int) ($result['created'] ?? 0),
                'updated_count' => (int) ($result['updated'] ?? 0),
                'linked_count' => (int) ($result['linked'] ?? 0),
                'skipped_count' => (int) ($result['skipped'] ?? 0),
                'warning_count' => (int) ($result['warnings'] ?? 0),
                'error_count' => (int) ($result['errors'] ?? 0),
                'started_at' => $startedAtSql,
                'finished_at' => $finishedAtSql,
                'duration_ms' => $durationMs,
                'options_json' => $this->safe_json_encode($optionsForStorage, '{}'),
                'report_json' => $this->safe_json_encode($reportPayload, '{}'),
            ]);
        } catch (\Throwable $e) {
            $this->log_error('Persisting run report failed', $e);
        }
    }

    private function reset_caches(): void
    {
        $this->companyCache = [];
        $this->expertCache = [];
        $this->speakerCache = [];
        $this->eventCache = [];
        $this->assignmentCache = [];
        $this->simulatedIdCache = [];
        $this->simulatedIdSequence = 1000000000;
        $this->csvCache = [];
        $this->adminUserIdCache = null;
    }

    /**
     * @return array<string, mixed>
     */
    private function resolve_source_file(string $canonicalFile): array
    {
        $directory = CMS_NETIMPORT_PLUGIN_DIR . 'files_import';
        $canonicalPath = $directory . DIRECTORY_SEPARATOR . $canonicalFile;
        $canonicalExists = is_file($canonicalPath);
        $canonicalFamily = $this->file_family_key(pathinfo($canonicalFile, PATHINFO_FILENAME));
        $selected = [
            'path' => $canonicalPath,
            'relative_path' => 'files_import/' . $canonicalFile,
            'selected_file' => $canonicalFile,
            'canonical_file' => $canonicalFile,
            'exists' => $canonicalExists,
            'mode' => 'base',
            'detected_date' => null,
            'file_size' => $canonicalExists ? (int) filesize($canonicalPath) : 0,
        ];

        $candidates = [];
        if (is_dir($directory)) {
            foreach (glob($directory . DIRECTORY_SEPARATOR . '*.csv') ?: [] as $filePath) {
                if (!is_file($filePath) || !is_readable($filePath)) {
                    continue;
                }
                $fileName = basename($filePath);
                $family = $this->file_family_key(pathinfo($fileName, PATHINFO_FILENAME));
                if ($family !== $canonicalFamily) {
                    continue;
                }
                $date = $this->extract_date_from_filename($fileName);
                $candidates[] = [
                    'path' => $filePath,
                    'file' => $fileName,
                    'date' => $date,
                    'mtime' => (int) filemtime($filePath),
                    'size' => (int) filesize($filePath),
                ];
            }
        }

        if ($candidates === []) {
            return $selected;
        }

        usort($candidates, function (array $a, array $b): int {
            $aScore = $a['date'] instanceof \DateTimeImmutable ? (int) $a['date']->format('Ymd') : 0;
            $bScore = $b['date'] instanceof \DateTimeImmutable ? (int) $b['date']->format('Ymd') : 0;
            if ($aScore !== $bScore) {
                return $bScore <=> $aScore;
            }
            if (($a['file'] === basename((string) $a['file'])) && ($b['file'] === basename((string) $b['file']))) {
                return ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0);
            }
            return ($b['mtime'] ?? 0) <=> ($a['mtime'] ?? 0);
        });

        $best = $candidates[0];
        $selected['path'] = $best['path'];
        $selected['selected_file'] = $best['file'];
        $selected['relative_path'] = 'files_import/' . $best['file'];
        $selected['exists'] = true;
        $selected['detected_date'] = $best['date'] instanceof \DateTimeImmutable ? $best['date']->format('Y-m-d') : null;
        $selected['file_size'] = $best['size'];
        if ($best['file'] !== $canonicalFile) {
            $selected['mode'] = 'update';
        }

        return $selected;
    }

    private function validate_source_file(string $path): ?string
    {
        if (!file_exists($path)) {
            return 'Quelldatei fehlt.';
        }
        $real = realpath($path);
        $base = realpath(CMS_NETIMPORT_PLUGIN_DIR . 'files_import');
        $basePath = $base !== false ? rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : false;
        if ($real === false || $basePath === false || !str_starts_with($real, $basePath)) {
            return 'Unsicherer Dateipfad erkannt.';
        }
        if (strtolower((string) pathinfo($real, PATHINFO_EXTENSION)) !== 'csv') {
            return 'Nur CSV-Dateien sind erlaubt.';
        }
        if (!is_readable($real)) {
            return 'Quelldatei ist nicht lesbar.';
        }
        $size = (int) filesize($real);
        if ($size <= 0) {
            return 'Quelldatei ist leer.';
        }
        if ($size > self::MAX_FILE_SIZE) {
            return 'Quelldatei ist größer als 10 MB und wird aus Sicherheitsgründen nicht verarbeitet.';
        }
        return null;
    }

    private function count_csv_rows(string $path): int
    {
        $payload = $this->get_csv_payload($path);
        return (int) ($payload['row_count'] ?? 0);
    }

    /**
     * @return list<array<string, string>>
     */
    private function read_csv(string $path): array
    {
        $payload = $this->get_csv_payload($path);
        return (array) ($payload['rows'] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function get_csv_payload(string $path): array
    {
        if (isset($this->csvCache[$path])) {
            return $this->csvCache[$path];
        }

        $payload = [
            'headers' => [],
            'rows' => [],
            'row_count' => 0,
            'validation_error' => null,
        ];

        $validationError = $this->validate_source_file($path);
        if ($validationError !== null) {
            $payload['validation_error'] = $validationError;
            return $this->csvCache[$path] = $payload;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) {
            $payload['validation_error'] = 'Quelldatei konnte nicht geöffnet werden.';
            return $this->csvCache[$path] = $payload;
        }

        $headers = null;
        while (($rawRow = fgetcsv($handle, 0, ';', '"', '\\')) !== false) {
            if ($headers === null) {
                $headers = array_map([$this, 'normalize_header'], $rawRow);
                $headers = array_values(array_filter($headers, static fn(string $header): bool => $header !== ''));
                if ($headers === []) {
                    $payload['validation_error'] = 'CSV enthält keine verwertbaren Header.';
                    break;
                }
                if (count($headers) !== count(array_unique($headers))) {
                    $payload['validation_error'] = 'CSV enthält doppelte Header und wird daher nicht importiert.';
                    break;
                }
                $payload['headers'] = $headers;
                continue;
            }

            if ($this->row_is_empty($rawRow)) {
                continue;
            }

            $normalizedRow = [];
            foreach ((array) $headers as $index => $header) {
                $normalizedRow[$header] = $this->normalize_cell((string) ($rawRow[$index] ?? ''));
            }
            $payload['rows'][] = $normalizedRow;
            $payload['row_count']++;
        }

        fclose($handle);
        return $this->csvCache[$path] = $payload;
    }

    /**
     * @param list<string> $headers
     * @param list<string> $requiredHeaders
     */
    private function validate_required_headers(array $headers, array $requiredHeaders): ?string
    {
        if ($requiredHeaders === []) {
            return null;
        }

        $missing = array_values(array_diff($requiredHeaders, $headers));
        if ($missing === []) {
            return null;
        }

        return 'CSV-Struktur ungültig. Pflichtspalten fehlen: ' . implode(', ', $missing);
    }

    private function normalize_header(string $header): string
    {
        $header = trim($header);
        $header = preg_replace('/^\xEF\xBB\xBF/u', '', $header) ?? $header;
        $header = str_replace(['Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß', '/', '-', '(', ')'], ['Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', 'ss', '_', '_', '', ''], $header);
        $header = mb_strtolower($header, 'UTF-8');
        $header = preg_replace('/[^a-z0-9]+/u', '_', $header) ?? $header;
        return trim($header, '_');
    }

    private function normalize_cell(string $value): string
    {
        $value = preg_replace('/^\xEF\xBB\xBF/u', '', $value) ?? $value;
        return trim($value);
    }

    private function row_is_empty(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<string, string> $row
     * @param list<string> $keys
     */
    private function value(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return trim((string) $row[$key]);
            }
        }
        return '';
    }

    /**
     * @param string|list<string> $value
     * @return list<string>
     */
    private function split_topics(string|array $value): array
    {
        $items = is_array($value)
            ? $value
            : (preg_split('/\s*(?:,|\||;|\/|·)\s*/u', $value) ?: []);
        $clean = [];
        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item === '' || $item === '---') {
                continue;
            }
            if (!in_array($item, $clean, true)) {
                $clean[] = $item;
            }
        }
        return $clean;
    }

    /**
     * @param list<string> $items
     * @return list<string>
     */
    private function limit_tags(array $items, int $limit = 8): array
    {
        return array_slice($this->split_topics($items), 0, $limit);
    }

    /**
     * @param list<string> $values
     * @return list<string>
     */
    private function combine_non_empty(array $values): array
    {
        $combined = [];
        foreach ($values as $value) {
            foreach ($this->split_topics($value) as $item) {
                if (!in_array($item, $combined, true)) {
                    $combined[] = $item;
                }
            }
        }
        return $combined;
    }

    /**
     * @param list<string> $terms
     * @return array<string, list<string>>
     */
    private function group_skill_terms(array $terms): array
    {
        $grouped = ['general' => [], 'tech' => [], 'soft' => []];
        foreach ($terms as $term) {
            $term = trim($term);
            if ($term === '') {
                continue;
            }
            $normalized = mb_strtolower($term, 'UTF-8');
            if (preg_match('/(azure|aws|gcp|cloud|docker|kubernetes|m365|microsoft|power|teams|sharepoint|exchange|copilot|ai|security|java|python|sql|fabric|server|network|iot|embedded|cryptography|devops|rest)/u', $normalized) === 1) {
                $grouped['tech'][] = $term;
            } elseif (preg_match('/(kommunikation|change|management|strategie|governance|leadership|präsentation|beratung|team|analytisches denken)/u', $normalized) === 1) {
                $grouped['soft'][] = $term;
            } else {
                $grouped['general'][] = $term;
            }
        }
        foreach ($grouped as $key => $values) {
            $grouped[$key] = array_values(array_unique($values));
        }
        return $grouped;
    }

    private function normalize_bool(string $value): bool
    {
        return in_array(mb_strtolower(trim($value), 'UTF-8'), ['1', 'true', 'yes', 'ja', 'y'], true);
    }

    /**
     * @param list<string> $allowed
     */
    private function normalize_status(string $value, array $allowed, string $default): string
    {
        $value = trim($value);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function normalize_int(string $value): int
    {
        return (int) preg_replace('/[^0-9-]+/', '', $value);
    }

    private function normalize_float(string $value): float
    {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return (float) preg_replace('/[^0-9\.-]+/', '', $value);
    }

    private function sanitize_url(string $value): string
    {
        $url = trim($value);
        if ($url === '' || strlen($url) > 2048 || preg_match('/[[:cntrl:]]/', $url) === 1 || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        if (!in_array(strtolower((string) $parts['scheme']), ['http', 'https'], true)) {
            return '';
        }

        if (($parts['user'] ?? '') !== '' || ($parts['pass'] ?? '') !== '') {
            return '';
        }

        $host = strtolower(trim((string) $parts['host'], '[]'));
        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            return '';
        }

        if (filter_var($host, FILTER_VALIDATE_IP)
            && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return '';
        }

        return mb_substr($url, 0, 2048);
    }

    private function truncate_text(string $value, int $maxLength): string
    {
        $value = trim($value);
        if (mb_strlen($value, 'UTF-8') <= $maxLength) {
            return $value;
        }
        return rtrim(mb_substr($value, 0, $maxLength - 1, 'UTF-8')) . '…';
    }

    private function parse_german_date(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $normalized = trim($normalized);

        // Erstes explizites Datum aus Freitext extrahieren (z. B. "Feb/März 2026", "14.01.2026 bis ...")
        if (preg_match('/(\d{1,2}[\.\/-]\d{1,2}[\.\/-]\d{2,4})/u', $normalized, $matches) === 1) {
            $normalized = (string) ($matches[1] ?? $normalized);
        }

        // Deutsche Saison-/Monatsangaben robust auf Startdatum abbilden
        if (preg_match('/\b(20\d{2})\b/u', $normalized, $yearMatch) === 1) {
            $year = (int) ($yearMatch[1] ?? 0);
            $lower = function_exists('mb_strtolower') ? mb_strtolower($normalized, 'UTF-8') : strtolower($normalized);

            $monthMap = [
                'januar' => 1, 'jan' => 1,
                'februar' => 2, 'feb' => 2,
                'märz' => 3, 'maerz' => 3, 'marz' => 3, 'mrz' => 3,
                'april' => 4, 'apr' => 4,
                'mai' => 5,
                'juni' => 6, 'jun' => 6,
                'juli' => 7, 'jul' => 7,
                'august' => 8, 'aug' => 8,
                'september' => 9, 'sep' => 9,
                'oktober' => 10, 'okt' => 10,
                'november' => 11, 'nov' => 11,
                'dezember' => 12, 'dez' => 12,
            ];

            foreach ($monthMap as $needle => $month) {
                if (str_contains($lower, $needle)) {
                    return sprintf('%04d-%02d-01', $year, $month);
                }
            }

            if (str_contains($lower, 'frühjahr') || str_contains($lower, 'fruehjahr') || str_contains($lower, 'spring')) {
                return sprintf('%04d-03-01', $year);
            }
            if (str_contains($lower, 'sommer') || str_contains($lower, 'summer')) {
                return sprintf('%04d-06-01', $year);
            }
            if (str_contains($lower, 'herbst') || str_contains($lower, 'autumn') || str_contains($lower, 'fall')) {
                return sprintf('%04d-09-01', $year);
            }
            if (str_contains($lower, 'winter')) {
                return sprintf('%04d-12-01', $year);
            }
            if (str_contains($lower, 'diverse') || str_contains($lower, 'tba') || str_contains($lower, 'n/a')) {
                return sprintf('%04d-01-01', $year);
            }

            // Reine Jahresangabe (z. B. "2026")
            if (preg_match('/^\s*20\d{2}\s*$/u', $normalized) === 1) {
                return sprintf('%04d-01-01', $year);
            }
        }

        foreach (['d.m.Y', 'd.m.y', 'Y-m-d', 'd/m/Y', 'd/m/y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('Y-m-d');
            }

            $date = \DateTimeImmutable::createFromFormat($format, $normalized);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('Y-m-d');
            }
        }

        // Letzter Fallback: DateTime-Parser (nur wenn eindeutig)
        try {
            $parsed = new \DateTimeImmutable($normalized);
            return $parsed->format('Y-m-d');
        } catch (\Throwable) {
        }

        return null;
    }

    private function extract_date_from_filename(string $fileName): ?\DateTimeImmutable
    {
        $stem = pathinfo($fileName, PATHINFO_FILENAME);
        if (preg_match('/(20\d{2})[-_]?([01]\d)[-_]?([0-3]\d)$/', $stem, $matches) === 1) {
            return \DateTimeImmutable::createFromFormat('Y-m-d', $matches[1] . '-' . $matches[2] . '-' . $matches[3]) ?: null;
        }
        if (preg_match('/([0-3]\d)[-_]([01]\d)[-_](20\d{2})$/', $stem, $matches) === 1) {
            return \DateTimeImmutable::createFromFormat('Y-m-d', $matches[3] . '-' . $matches[2] . '-' . $matches[1]) ?: null;
        }
        return null;
    }

    private function file_family_key(string $stem): string
    {
        $stem = mb_strtolower($stem, 'UTF-8');
        $stem = str_replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'], $stem);
        $stem = preg_replace('/(?:[_\-\s\(]*(?:update|neu|new))?[_\-\s\(]*(20\d{2}[-_]?\d{2}[-_]?\d{2}|\d{2}[-_]\d{2}[-_]20\d{2})\)?$/u', '', $stem) ?? $stem;
        $stem = preg_replace('/[^a-z0-9]+/u', '_', $stem) ?? $stem;
        return trim($stem, '_');
    }

    private function normalize_lookup_value(string $value): string
    {
        $value = str_replace(['Ä', 'Ö', 'Ü', 'ä', 'ö', 'ü', 'ß'], ['Ae', 'Oe', 'Ue', 'ae', 'oe', 'ue', 'ss'], $value);
        $value = mb_strtolower(trim($value), 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        return $value;
    }

    private function person_cache_key(string $firstName, string $lastName): string
    {
        return $this->normalize_lookup_value($firstName) . '|' . $this->normalize_lookup_value($lastName);
    }

    private function company_cache_key(string $name, string $website = ''): string
    {
        return $this->normalize_lookup_value($name) . '|' . $this->normalize_lookup_value($website);
    }

    private function event_cache_key(string $title, string $eventDate): string
    {
        return $this->normalize_lookup_value($title) . '|' . $eventDate;
    }

    private function find_company_id(string $name, string $website = ''): int
    {
        $cacheKey = $this->company_cache_key($name, $website);
        if (isset($this->companyCache[$cacheKey])) {
            return $this->companyCache[$cacheKey];
        }
        if (!$this->is_plugin_ready('cms-companies')) {
            return 0;
        }
        try {
            $db = CMS\Database::instance();
            $companiesTable = $this->table('companies');
            if ($website !== '') {
                $stmt = $db->prepare("SELECT id FROM {$companiesTable} WHERE website = ? LIMIT 1");
                $stmt->execute([$website]);
                $id = (int) ($stmt->fetchColumn() ?: 0);
                if ($id > 0) {
                    return $this->companyCache[$cacheKey] = $id;
                }
            }
            if ($name === '') {
                return 0;
            }
            $stmt = $db->prepare("SELECT id FROM {$companiesTable} WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $stmt->execute([$name]);
            return $this->companyCache[$cacheKey] = (int) ($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            $this->log_error('Company lookup failed', $e);
            return 0;
        }
    }

    /**
     * @param array<string, mixed> $result
     */
    private function ensure_company_reference(string $name, string $website, bool $autoCreate, bool $dryRun, array &$result): int
    {
        if (!$this->is_plugin_ready('cms-companies', 'CMS_Companies_Database')) {
            return 0;
        }
        $existingId = $this->find_company_id($name, $website);
        if ($existingId > 0 || !$autoCreate || $name === '' || $name === '---') {
            return $existingId;
        }
        if ($dryRun) {
            $cacheKey = $this->company_cache_key($name, $website);
            $simulated = $this->claim_simulated_id('company', $cacheKey);
            $this->companyCache[$cacheKey] = $simulated['id'];
            if ($simulated['is_new']) {
                $result['created']++;
                $this->add_message($result, 'info', 'DRY-RUN: Fehlende Company würde minimal angelegt: ' . $name);
            }
            return $simulated['id'];
        }
        $savedId = (int) CMS_Companies_Database::instance()->save_company([
            'user_id' => $this->get_admin_user_id(),
            'name' => $name,
            'email' => '',
            'website' => $website !== '' ? $website : null,
            'status' => 'active',
        ]);
        if ($savedId > 0) {
            $this->assign_admin_ownership('companies', $savedId);
            $this->companyCache[$this->company_cache_key($name, $website)] = $savedId;
            $result['created']++;
            $this->add_message($result, 'success', 'Fehlende Company automatisch angelegt: ' . $name);
        }
        return $savedId;
    }

    private function find_expert_id(string $firstName, string $lastName): int
    {
        $cacheKey = $this->person_cache_key($firstName, $lastName);
        if (isset($this->expertCache[$cacheKey])) {
            return $this->expertCache[$cacheKey];
        }
        if (!$this->is_plugin_ready('cms-experts')) {
            return 0;
        }
        try {
            $db = CMS\Database::instance();
            $expertsTable = $this->table('experts');
            $stmt = $db->prepare("SELECT id FROM {$expertsTable} WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) LIMIT 1");
            $stmt->execute([$firstName, $lastName]);
            return $this->expertCache[$cacheKey] = (int) ($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            $this->log_error('Expert lookup failed', $e);
            return 0;
        }
    }

    private function find_speaker_id(string $firstName, string $lastName): int
    {
        $cacheKey = $this->person_cache_key($firstName, $lastName);
        if (isset($this->speakerCache[$cacheKey])) {
            return $this->speakerCache[$cacheKey];
        }
        if (!$this->is_plugin_ready('cms-speakers')) {
            return 0;
        }
        try {
            $db = CMS\Database::instance();
            $speakersTable = $this->table('speakers');
            $stmt = $db->prepare("SELECT id FROM {$speakersTable} WHERE LOWER(first_name) = LOWER(?) AND LOWER(last_name) = LOWER(?) LIMIT 1");
            $stmt->execute([$firstName, $lastName]);
            return $this->speakerCache[$cacheKey] = (int) ($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            $this->log_error('Speaker lookup failed', $e);
            return 0;
        }
    }

    private function find_event_id(string $title, string $eventDate): int
    {
        $cacheKey = $this->event_cache_key($title, $eventDate);
        if (isset($this->eventCache[$cacheKey])) {
            return $this->eventCache[$cacheKey];
        }
        if (!$this->is_plugin_ready('cms-events')) {
            return 0;
        }
        try {
            $db = CMS\Database::instance();
            $eventsTable = $this->table('events');
            $stmt = $db->prepare("SELECT id FROM {$eventsTable} WHERE LOWER(title) = LOWER(?) AND event_date = ? LIMIT 1");
            $stmt->execute([$title, $eventDate]);
            return $this->eventCache[$cacheKey] = (int) ($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            $this->log_error('Event lookup failed', $e);
            return 0;
        }
    }

    private function assignment_exists(string $assignmentKey): bool
    {
        if (isset($this->assignmentCache[$assignmentKey])) {
            return $this->assignmentCache[$assignmentKey];
        }
        [$eventId, $entityId, $entityType] = explode('|', $assignmentKey, 3);
        if ((int) $eventId >= 1000000000 || (int) $entityId >= 1000000000) {
            return $this->assignmentCache[$assignmentKey] = false;
        }
        try {
            $db = CMS\Database::instance();
            $eventSpeakersTable = $this->table('event_speakers');
            $stmt = $db->prepare("SELECT id FROM {$eventSpeakersTable} WHERE event_id = ? AND speaker_id = ? AND speaker_type = ? LIMIT 1");
            $stmt->execute([(int) $eventId, (int) $entityId, $entityType]);
            return $this->assignmentCache[$assignmentKey] = ((int) ($stmt->fetchColumn() ?: 0) > 0);
        } catch (\Throwable $e) {
            $this->log_error('Assignment lookup failed', $e);
            return $this->assignmentCache[$assignmentKey] = false;
        }
    }

    /**
     * @return array{id:int,is_new:bool}
     */
    private function claim_simulated_id(string $scope, string $lookupKey): array
    {
        $cacheKey = $scope . '|' . $lookupKey;
        if (isset($this->simulatedIdCache[$cacheKey])) {
            return [
                'id' => $this->simulatedIdCache[$cacheKey],
                'is_new' => false,
            ];
        }

        $id = $this->simulatedIdSequence++;
        $this->simulatedIdCache[$cacheKey] = $id;

        return [
            'id' => $id,
            'is_new' => true,
        ];
    }

    private function safe_json_encode(mixed $value, string $fallback = '[]'): string
    {
        $json = json_encode($value, self::JSON_FLAGS);
        if ($json !== false) {
            return $json;
        }

        $this->log_error('json_encode failed: ' . json_last_error_msg());
        return $fallback;
    }

    /**
     * @return array<string, mixed>
     */
    private function safe_json_decode(string $json): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $this->log_error('json_decode failed: ' . json_last_error_msg());
        return [];
    }

    private function is_plugin_ready(string $slug, string $requiredClass = ''): bool
    {
        if (!class_exists('CMS\\PluginManager')) {
            return false;
        }
        if (!CMS\PluginManager::instance()->isPluginActive($slug)) {
            return false;
        }
        return $requiredClass === '' || class_exists($requiredClass);
    }

    private function get_admin_user_id(): ?int
    {
        if ($this->adminUserIdCache !== null) {
            return $this->adminUserIdCache > 0 ? $this->adminUserIdCache : null;
        }

        $currentUserId = (int) (CMS\Auth::instance()->currentUser()?->id ?? 0);
        if ($currentUserId > 0 && CMS\Auth::instance()->isAdmin()) {
            $this->adminUserIdCache = $currentUserId;
            return $currentUserId;
        }

        try {
            $db = CMS\Database::instance();
            $usersTable = $this->table('users');
            $stmt = $db->prepare("SELECT id FROM {$usersTable} WHERE role = ? AND status = ? ORDER BY id ASC LIMIT 1");
            $stmt->execute(['admin', 'active']);
            $this->adminUserIdCache = (int) ($stmt->fetchColumn() ?: 0);
        } catch (\Throwable $e) {
            $this->log_error('Admin user lookup failed', $e);
            $this->adminUserIdCache = 0;
        }

        return $this->adminUserIdCache > 0 ? $this->adminUserIdCache : null;
    }

    private function assign_admin_ownership(string $table, int $recordId): void
    {
        $adminUserId = $this->get_admin_user_id();
        if ($adminUserId === null || $recordId <= 0) {
            return;
        }

        try {
            CMS\Database::instance()->update($table, ['user_id' => $adminUserId], ['id' => $recordId]);
        } catch (\Throwable $e) {
            $this->log_error('Ownership update failed for ' . $table . '#' . $recordId, $e);
        }
    }
}
