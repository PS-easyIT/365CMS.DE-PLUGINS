<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Profile-Datenlogik – CRUD + Beziehungen (Tasks, Requirements, Benefits, Skills)
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_Profiles
{
    private static ?self $instance = null;
    private \CMS\Database $db;
    private string $p;

    public static function instance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->db = \CMS\Database::instance();
        $this->p  = $this->db->getPrefix();
    }

    // ── Profil CRUD ──────────────────────────────────────────────────────────

    public function get(int $id): ?object
    {
        return $this->db->get_row(
            "SELECT p.*, jc.name AS category_name
             FROM {$this->p}jpg_profiles p
             LEFT JOIN {$this->p}jpg_job_categories jc ON jc.id = p.job_category_id
             WHERE p.id = ?",
            [$id]
        );
    }

    /**
     * @return array<object>
     */
    public function get_list(array $args = []): array
    {
        $status      = $args['status'] ?? '';
        $search      = $args['search'] ?? '';
        $limit       = (int) ($args['limit'] ?? 20);
        $offset      = (int) ($args['offset'] ?? 0);
        $hidePrivate = !empty($args['hide_private']); // Phase 9: Privacy-Filter

        $where  = ['1=1'];
        $params = [];

        if ($status !== '') {
            $where[]  = 'p.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $where[]  = '(p.title LIKE ? OR p.summary LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($hidePrivate) {
            $where[] = '(p.is_private IS NULL OR p.is_private = 0)';
        }

        $sql = "SELECT p.*, jc.name AS category_name
                FROM {$this->p}jpg_profiles p
                LEFT JOIN {$this->p}jpg_job_categories jc ON jc.id = p.job_category_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $this->db->get_results($sql, $params);
    }

    public function count(array $args = []): int
    {
        $status      = $args['status'] ?? '';
        $search      = $args['search'] ?? '';
        $hidePrivate = !empty($args['hide_private']); // Phase 9: Privacy-Filter

        $where  = ['1=1'];
        $params = [];

        if ($status !== '') {
            $where[]  = 'p.status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $where[]  = '(p.title LIKE ? OR p.summary LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }
        if ($hidePrivate) {
            $where[] = '(p.is_private IS NULL OR p.is_private = 0)';
        }

        $sql = "SELECT COUNT(*) FROM {$this->p}jpg_profiles p WHERE " . implode(' AND ', $where);
        return (int) $this->db->get_var($sql, $params);
    }

    /**
     * Erstellt oder aktualisiert ein Profil.
     * Gibt die ID des Datensatzes zurück.
     */
    public function save(array $data, int $id = 0): int
    {
        $fields = [
            'title'           => trim($data['title'] ?? ''),
            'job_category_id' => !empty($data['job_category_id']) ? (int) $data['job_category_id'] : null,
            'status'          => $data['status'] ?? 'draft',
            'summary'         => trim($data['summary'] ?? ''),
            'description'     => $data['description'] ?? '',
            'location'        => trim($data['location'] ?? ''),
            'employment_type' => $data['employment_type'] ?? 'fulltime',
            'experience_level'=> $data['experience_level'] ?? 'mid',
            'salary_min'      => ($data['salary_min'] ?? '') !== '' ? (float) $data['salary_min'] : null,
            'salary_max'      => ($data['salary_max'] ?? '') !== '' ? (float) $data['salary_max'] : null,
            'remote_option'   => $data['remote_option'] ?? 'onsite',
            'updated_by'      => (int) ($data['updated_by'] ?? 0),
        ];

        // Status-Zeitstempel
        if ($fields['status'] === 'published') {
            $fields['published_at'] = date('Y-m-d H:i:s');
        } elseif ($fields['status'] === 'archived') {
            $fields['archived_at'] = date('Y-m-d H:i:s');
        }

        $isNew = ($id === 0);

        if ($id > 0) {
            // Update: Slug NICHT neu generieren – bestehende öffentliche URLs bleiben erhalten.
            // Slug kann via $data['slug'] explizit überschrieben werden.
            if (isset($data['slug']) && $data['slug'] !== '') {
                $fields['slug'] = sanitize_key($data['slug']);
            }
            $this->db->update('jpg_profiles', $fields, ['id' => $id]);
        } else {
            $fields['created_by'] = (int) ($data['created_by'] ?? 0);
            // Temp-Slug aus Titel (nur zur Insert-Zeit, da ID noch unbekannt)
            $fields['slug'] = $this->generate_slug($data['title'] ?? '', 0);

            $id = $this->db->insert('jpg_profiles', $fields);

            // Nach dem Insert: Slug auf Format {firma}-{cId}-{stelle}-{pId} upgraden
            if ($id > 0) {
                $finalSlug = $this->generate_slug_with_id(
                    $fields['title'],
                    $id,
                    (int) ($data['company_id'] ?? 0)
                );
                $this->db->update('jpg_profiles', ['slug' => $finalSlug], ['id' => $id]);
            }

            // Subscription-Nutzung aktualisieren
            $this->update_subscription_usage($fields['created_by']);
        }

        // Hooks feuern
        if ($id > 0 && class_exists('CMS\\Hooks')) {
            \CMS\Hooks::doAction('jpg_profile_saved', $id, $data, $isNew);
            if ($fields['status'] === 'published') {
                \CMS\Hooks::doAction('jpg_profile_published', $id);
            }
        }

        return $id;
    }

    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        // Ersteller ermitteln für Nutzungs-Update
        $profile = $this->get($id);
        $createdBy = $profile ? (int) ($profile->created_by ?? 0) : 0;

        $tables = [
            'jpg_profile_tasks',
            'jpg_profile_requirements',
            'jpg_profile_benefits',
            'jpg_profile_skills',
        ];
        foreach ($tables as $table) {
            $this->db->delete($table, ['profile_id' => $id]);
        }
        $this->db->delete('jpg_profiles', ['id' => $id]);

        // Subscription-Nutzung aktualisieren
        if ($createdBy > 0) {
            $this->update_subscription_usage($createdBy);
        }

        if (class_exists('CMS\\Hooks')) {
            \CMS\Hooks::doAction('jpg_profile_deleted', $id);
        }

        return true;
    }

    // ── Tasks ────────────────────────────────────────────────────────────────

    /** @return array<object> */
    public function get_tasks(int $profileId): array
    {
        return $this->db->get_results(
            "SELECT * FROM {$this->p}jpg_profile_tasks
             WHERE profile_id = ? ORDER BY sort_order ASC",
            [$profileId]
        );
    }

    /**
     * Ersetzt alle Tasks eines Profils.
     * @param array<string> $tasks
     */
    public function save_tasks(int $profileId, array $tasks): void
    {
        $this->db->delete('jpg_profile_tasks', ['profile_id' => $profileId]);
        foreach (array_values($tasks) as $i => $text) {
            $text = trim($text);
            if ($text === '') {
                continue;
            }
            $this->db->insert('jpg_profile_tasks', [
                'profile_id' => $profileId,
                'task_text'  => substr($text, 0, 500),
                'sort_order' => $i,
            ]);
        }
    }

    // ── Requirements ─────────────────────────────────────────────────────────

    /** @return array<object> */
    public function get_requirements(int $profileId): array
    {
        return $this->db->get_results(
            "SELECT * FROM {$this->p}jpg_profile_requirements
             WHERE profile_id = ? ORDER BY sort_order ASC",
            [$profileId]
        );
    }

    /**
     * @param array<array{text: string, type: string}> $reqs
     */
    public function save_requirements(int $profileId, array $reqs): void
    {
        $this->db->delete('jpg_profile_requirements', ['profile_id' => $profileId]);
        foreach (array_values($reqs) as $i => $req) {
            $text = trim($req['text'] ?? '');
            if ($text === '') {
                continue;
            }
            $type = in_array($req['type'] ?? '', ['must', 'nice']) ? $req['type'] : 'must';
            $this->db->insert('jpg_profile_requirements', [
                'profile_id' => $profileId,
                'req_text'   => substr($text, 0, 500),
                'req_type'   => $type,
                'sort_order' => $i,
            ]);
        }
    }

    // ── Benefits ─────────────────────────────────────────────────────────────

    /** @return array<int> */
    public function get_benefit_ids(int $profileId): array
    {
        $rows = $this->db->get_results(
            "SELECT benefit_id FROM {$this->p}jpg_profile_benefits
             WHERE profile_id = ? ORDER BY sort_order ASC",
            [$profileId]
        );
        return array_map(fn($r) => (int) $r->benefit_id, $rows);
    }

    /** @param array<int> $benefitIds */
    public function save_benefits(int $profileId, array $benefitIds): void
    {
        $this->db->delete('jpg_profile_benefits', ['profile_id' => $profileId]);
        foreach (array_values($benefitIds) as $i => $bid) {
            $bid = (int) $bid;
            if ($bid <= 0) {
                continue;
            }
            $this->db->insert('jpg_profile_benefits', [
                'profile_id' => $profileId,
                'benefit_id' => $bid,
                'sort_order' => $i,
            ]);
        }
    }

    /**
     * Cascading Benefit Logic (Strict Rule #1).
     *
     * Erbt Benefits über 3 Ebenen:
     *   1. Firma     → {prefix}company_meta  (meta_key = 'jpg_default_benefit_ids', JSON-Array)
     *   2. Kategorie → jpg_category_benefits
     *   3. Job       → jpg_profile_benefits  (überschreibt; höchste Priorität)
     *
     * Zusätzlich: excluded_benefit_ids (JSON in jpg_profiles) deaktiviert geerbte Benefits
     * für Sonderfälle (z.B. Werkstudenten). Job-eigene Benefits können nicht ausgeschlossen werden.
     *
     * Jedes Ergebnis-Objekt hat das Feld `source` ('company'|'category'|'job').
     *
     * @return array<object>  Finale, gemergte Benefit-Objekte (dedup, Exclusions angewendet)
     */
    public function getResolvedBenefits(int $profileId): array
    {
        // ── Profil laden ──────────────────────────────────────────────────────
        $profile = $this->db->get_row(
            "SELECT company_id, job_category_id, excluded_benefit_ids
             FROM {$this->p}jpg_profiles WHERE id = ?",
            [$profileId]
        );
        if (!$profile) {
            return [];
        }

        $companyId  = (int) ($profile->company_id ?? 0);
        $categoryId = (int) ($profile->job_category_id ?? 0);

        // Ausgeschlossene IDs (nur für geerbte Ebenen relevant)
        $excludedIds = [];
        if (!empty($profile->excluded_benefit_ids)) {
            $decoded = json_decode($profile->excluded_benefit_ids, true);
            if (is_array($decoded)) {
                $excludedIds = array_map('intval', $decoded);
            }
        }

        // ── Ebene 1: Firma (company_meta) ─────────────────────────────────────
        $companyBenefitIds = [];
        if ($companyId > 0) {
            try {
                $meta = $this->db->get_row(
                    "SELECT meta_value FROM {$this->p}company_meta
                     WHERE company_id = ? AND meta_key = 'jpg_default_benefit_ids' LIMIT 1",
                    [$companyId]
                );
                if ($meta && !empty($meta->meta_value)) {
                    $decoded = json_decode($meta->meta_value, true);
                    if (is_array($decoded)) {
                        $companyBenefitIds = array_map('intval', $decoded);
                    }
                }
            } catch (\Throwable $e) {
                // cms-companies nicht installiert oder Tabelle nicht vorhanden – ignorieren
            }
        }

        // ── Ebene 2: Kategorie ────────────────────────────────────────────────
        $categoryBenefitIds = [];
        if ($categoryId > 0) {
            try {
                $rows = $this->db->get_results(
                    "SELECT benefit_id FROM {$this->p}jpg_category_benefits
                     WHERE category_id = ? ORDER BY sort_order ASC",
                    [$categoryId]
                );
                $categoryBenefitIds = array_map(fn($r) => (int) $r->benefit_id, $rows);
            } catch (\Throwable $e) {
                // Tabelle existiert noch nicht (vor Migrations-Run) – ignorieren
            }
        }

        // ── Ebene 3: Job-spezifisch ───────────────────────────────────────────
        $jobBenefitIds = $this->get_benefit_ids($profileId);

        // ── Merge: Deduplizierung mit Quellen-Tracking ────────────────────────
        // Reihenfolge: company < category < job (höhere Priorität überschreibt Quelle)
        $merged = []; // benefit_id => source
        foreach ($companyBenefitIds as $bid) {
            $bid = (int) $bid;
            if ($bid > 0) {
                $merged[$bid] = 'company';
            }
        }
        foreach ($categoryBenefitIds as $bid) {
            $bid = (int) $bid;
            if ($bid > 0) {
                $merged[$bid] = 'category'; // überschreibt company-Quelle
            }
        }
        foreach ($jobBenefitIds as $bid) {
            $bid = (int) $bid;
            if ($bid > 0) {
                $merged[$bid] = 'job'; // überschreibt alle geerbten
            }
        }

        // ── Exclusions: nur für geerbte Benefits (company + category) ─────────
        foreach ($excludedIds as $exId) {
            if (isset($merged[$exId]) && $merged[$exId] !== 'job') {
                unset($merged[$exId]);
            }
        }

        if (empty($merged)) {
            return [];
        }

        // ── Benefit-Objekte laden ─────────────────────────────────────────────
        $ids          = array_keys($merged);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows         = $this->db->get_results(
            "SELECT * FROM {$this->p}jpg_benefits
             WHERE id IN ({$placeholders}) AND active = 1
             ORDER BY group_name ASC, sort_order ASC",
            $ids
        );

        // source-Eigenschaft anhängen; Reihenfolge nach Quellen-Merge beibehalten
        $indexed = [];
        foreach ($rows as $b) {
            $b->source    = $merged[(int) $b->id] ?? 'job';
            $indexed[(int) $b->id] = $b;
        }

        // Originale Merge-Reihenfolge beibehalten (company → category → job)
        $result = [];
        foreach (array_keys($merged) as $bid) {
            if (isset($indexed[$bid])) {
                $result[] = $indexed[$bid];
            }
        }

        return $result;
    }

    /**
     * Category-Benefits speichern (Ebene 2 der Cascading-Logik).
     *
     * @param array<int> $benefitIds
     */
    public function save_category_benefits(int $categoryId, array $benefitIds): void
    {
        $this->db->delete('jpg_category_benefits', ['category_id' => $categoryId]);
        foreach (array_values($benefitIds) as $i => $bid) {
            $bid = (int) $bid;
            if ($bid <= 0) {
                continue;
            }
            $this->db->insert('jpg_category_benefits', [
                'category_id' => $categoryId,
                'benefit_id'  => $bid,
                'sort_order'  => $i,
            ]);
        }
    }

    /**
     * Excluded-Benefit-IDs für einen Job speichern (Override-Logik).
     *
     * @param array<int> $excludedIds
     */
    public function save_excluded_benefits(int $profileId, array $excludedIds): void
    {
        $this->db->execute(
            "UPDATE {$this->p}jpg_profiles SET excluded_benefit_ids = ? WHERE id = ?",
            [empty($excludedIds) ? null : json_encode(array_map('intval', $excludedIds)), $profileId]
        );
    }

    // ── Skills ───────────────────────────────────────────────────────────────

    /** @return array<object> */
    public function get_skills(int $profileId): array
    {
        return $this->db->get_results(
            "SELECT ps.*, s.skill_name, s.group_name
             FROM {$this->p}jpg_profile_skills ps
             JOIN {$this->p}jpg_skills s ON s.id = ps.skill_id
             WHERE ps.profile_id = ? ORDER BY ps.sort_order ASC",
            [$profileId]
        );
    }

    /**
     * @param array<array{skill_id: int, level: string}> $skills
     */
    public function save_skills(int $profileId, array $skills): void
    {
        $this->db->delete('jpg_profile_skills', ['profile_id' => $profileId]);
        $levels = ['basic', 'intermediate', 'advanced', 'expert'];
        foreach (array_values($skills) as $i => $sk) {
            $sid   = (int) ($sk['skill_id'] ?? 0);
            $level = in_array($sk['level'] ?? '', $levels) ? $sk['level'] : 'intermediate';
            if ($sid <= 0) {
                continue;
            }
            $this->db->insert('jpg_profile_skills', [
                'profile_id' => $profileId,
                'skill_id'   => $sid,
                'level'      => $level,
                'sort_order' => $i,
            ]);
        }
    }

    // ── Stats ────────────────────────────────────────────────────────────────

    public function log_stat(int $profileId, string $action, int $userId = 0): void
    {
        $this->db->insert('jpg_stats', [
            'profile_id' => $profileId,
            'action'     => substr($action, 0, 50),
            'user_id'    => $userId > 0 ? $userId : null,
            'ip_hash'    => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''),
        ]);
    }

    /** @return array<object> */
    public function get_stats_summary(): array
    {
        return $this->db->get_results(
            "SELECT status, COUNT(*) AS cnt
             FROM {$this->p}jpg_profiles
             GROUP BY status",
            []
        );
    }

    // ── Hilfsmethoden ────────────────────────────────────────────────────────

    private function generate_slug(string $title, int $excludeId): string
    {
        $slug = $this->slugify($title);

        $base  = $slug;
        $count = 1;
        while (true) {
            $check = $excludeId > 0
                ? $this->db->get_var(
                    "SELECT id FROM {$this->p}jpg_profiles WHERE slug = ? AND id != ?",
                    [$slug, $excludeId]
                )
                : $this->db->get_var(
                    "SELECT id FROM {$this->p}jpg_profiles WHERE slug = ?",
                    [$slug]
                );
            if (!$check) {
                break;
            }
            $slug = $base . '-' . $count++;
        }
        return $slug;
    }

    /**
     * Generiert den finalen Slug nach dem INSERT (enthält Profil-ID für Eindeutigkeit).
     *
     * Format: {firmenkurz}-{companyId}-{stellekurz}-{profileId}
     * Ohne Firma: {stellekurz}-{profileId}
     *
     * @since 0.9.1
     */
    private function generate_slug_with_id(string $title, int $profileId, int $companyId = 0): string
    {
        $titleSlug  = $this->slugify($title);
        // Max. 30 Zeichen für den Stellentitel-Teil
        $titleShort = rtrim(substr($titleSlug, 0, 30), '-');

        if ($companyId > 0) {
            try {
                $company = $this->db->get_row(
                    "SELECT name FROM {$this->p}companies WHERE id = ?",
                    [$companyId]
                );
                if ($company && !empty($company->name)) {
                    $companySlug  = $this->slugify((string) $company->name);
                    $companyShort = rtrim(substr($companySlug, 0, 20), '-');
                    return $companyShort . '-' . $companyId . '-' . $titleShort . '-' . $profileId;
                }
            } catch (\Throwable $e) {
                // Kein Company-Plugin oder DB-Fehler → ohne Firmenpräfix
            }
        }

        return $titleShort . '-' . $profileId;
    }

    /**
     * Konvertiert beliebigen Text in einen URL-sicheren Slug (ohne Eindeutigkeitsprüfung).
     */
    private function slugify(string $str): string
    {
        $slug = strtolower(trim($str));
        $slug = preg_replace_callback('/[äöüÄÖÜ]/u', function ($m) {
            return match ($m[0]) {
                'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
                'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
                default => ''
            };
        }, $slug);
        $slug = preg_replace('/ß/', 'ss', $slug);
        $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    // ── Subscription-Integration ─────────────────────────────────────────────

    /**
     * Aktualisiert die Subscription-Nutzung für einen Benutzer.
     *
     * @since 0.0.1
     */
    private function update_subscription_usage(int $userId): void
    {
        if ($userId <= 0 || !function_exists('update_resource_usage')) {
            return;
        }

        try {
            $count = (int) $this->db->get_var(
                "SELECT COUNT(*) FROM {$this->p}jpg_profiles WHERE created_by = ?",
                [$userId]
            );
            update_resource_usage('job_profiles', $count, $userId);
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Profiles::update_subscription_usage() error: ' . $e->getMessage());
        }
    }

    /**
     * Prüft ob der Benutzer noch Profile erstellen darf.
     *
     * @since 0.0.1
     */
    public function can_create(int $userId): bool
    {
        if (!function_exists('user_can_create_resource')) {
            return true; // kein Subscription-System → kein Limit
        }
        return user_can_create_resource('job_profiles', $userId);
    }
}
