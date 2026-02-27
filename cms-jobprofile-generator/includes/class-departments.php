<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Abteilungsverwaltung (Departments)
 *
 * Schicht Firma → Abteilung → Stelle.
 * Benefits und Anforderungen sind von Firma nach Abteilung nach
 * Stelle vererbbar (additive Vererbung, override pro Ebene möglich).
 *
 * @since 1.4.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_Departments
{
    private static ?self $instance = null;
    private \CMS\Database $db;
    private string $p;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->db = \CMS\Database::instance();
        $this->p  = $this->db->getPrefix();
    }

    // ── Abteilungen CRUD ───────────────────────────────────────────────────

    /**
     * Alle Abteilungen einer Firma.
     *
     * @return array<object>
     */
    public function get_all(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }
        return $this->db->get_results(
            "SELECT * FROM {$this->p}jpg_departments
             WHERE company_id = ?
             ORDER BY sort_order ASC, name ASC",
            [$companyId]
        ) ?: [];
    }

    /** Eine Abteilung per ID. */
    public function get(int $id): ?object
    {
        if ($id <= 0) {
            return null;
        }
        return $this->db->get_row(
            "SELECT * FROM {$this->p}jpg_departments WHERE id = ?",
            [$id]
        );
    }

    /**
     * Abteilung anlegen oder aktualisieren.
     *
     * @param array<string, mixed> $data
     * @param int                  $id   0 = neu
     * @return int                       neue oder geänderte ID
     */
    public function save(array $data, int $id = 0): int
    {
        $fields = [
            'company_id'  => (int)    ($data['company_id']  ?? 0),
            'name'        => trim((string) ($data['name']       ?? '')),
            'description' => trim((string) ($data['description'] ?? '')),
            'sort_order'  => (int)    ($data['sort_order']  ?? 0),
            'created_by'  => (int)    ($data['created_by']  ?? 0),
        ];

        if (empty($fields['name'])) {
            return 0;
        }

        if ($id > 0) {
            $this->db->update('jpg_departments', $fields, ['id' => $id]);
            return $id;
        }
        return (int) $this->db->insert('jpg_departments', $fields);
    }

    /**
     * Abteilung + alle verknüpften Benefits/Anforderungen löschen.
     */
    public function delete(int $id): void
    {
        $this->db->delete('jpg_department_benefits',    ['department_id' => $id]);
        $this->db->delete('jpg_department_requirements',['department_id' => $id]);
        $this->db->delete('jpg_departments',            ['id'            => $id]);
    }

    // ── Benefits pro Abteilung ─────────────────────────────────────────────

    /** IDs der Benefits, die dieser Abteilung direkt zugewiesen sind. */
    public function get_benefit_ids(int $departmentId): array
    {
        $rows = $this->db->get_results(
            "SELECT benefit_id FROM {$this->p}jpg_department_benefits WHERE department_id = ?",
            [$departmentId]
        ) ?: [];
        return array_map(fn($r) => (int) $r->benefit_id, $rows);
    }

    /**
     * Benefits der Abteilung setzen (replace).
     *
     * @param array<int> $benefitIds
     */
    public function save_benefits(int $departmentId, array $benefitIds): void
    {
        $pdo = $this->db->getPdo();
        $p   = $this->p;

        $pdo->prepare("DELETE FROM {$p}jpg_department_benefits WHERE department_id = ?")
            ->execute([$departmentId]);

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO {$p}jpg_department_benefits (department_id, benefit_id) VALUES (?, ?)"
        );
        foreach (array_unique($benefitIds) as $bid) {
            $bid = (int) $bid;
            if ($bid > 0) {
                $stmt->execute([$departmentId, $bid]);
            }
        }
    }

    // ── Anforderungen pro Abteilung ────────────────────────────────────────

    /** IDs der Anforderungs-Items, die dieser Abteilung direkt zugewiesen sind. */
    public function get_requirement_ids(int $departmentId): array
    {
        $rows = $this->db->get_results(
            "SELECT req_item_id FROM {$this->p}jpg_department_requirements WHERE department_id = ?",
            [$departmentId]
        ) ?: [];
        return array_map(fn($r) => (int) $r->req_item_id, $rows);
    }

    /**
     * Anforderungen der Abteilung setzen (replace).
     *
     * @param array<int> $reqItemIds
     */
    public function save_requirements(int $departmentId, array $reqItemIds): void
    {
        $pdo = $this->db->getPdo();
        $p   = $this->p;

        $pdo->prepare("DELETE FROM {$p}jpg_department_requirements WHERE department_id = ?")
            ->execute([$departmentId]);

        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO {$p}jpg_department_requirements (department_id, req_item_id) VALUES (?, ?)"
        );
        foreach (array_unique($reqItemIds) as $rid) {
            $rid = (int) $rid;
            if ($rid > 0) {
                $stmt->execute([$departmentId, $rid]);
            }
        }
    }

    // ── Vererbung ─────────────────────────────────────────────────────────

    /**
     * Effektive Benefits für eine Abteilung (Firma-Standard + Abteilung, dedup).
     *
     * @return array<int>
     */
    public function get_effective_benefit_ids(int $departmentId): array
    {
        $dept = $this->get($departmentId);
        if (!$dept) {
            return [];
        }

        // 1. Firma-Standard-Benefits
        $companyBenefits = $this->db->get_results(
            "SELECT benefit_id FROM {$this->p}jpg_company_default_benefits WHERE company_id = ?",
            [(int) $dept->company_id]
        ) ?: [];
        $ids = array_map(fn($r) => (int) $r->benefit_id, $companyBenefits);

        // 2. Abteilungs-eigene Benefits (ergänzend)
        foreach ($this->get_benefit_ids($departmentId) as $bid) {
            if (!in_array($bid, $ids, true)) {
                $ids[] = $bid;
            }
        }
        return $ids;
    }

    /**
     * Effektive Anforderungen für eine Abteilung (Company-Ebene nicht vorgegeben → nur Abteilung).
     *
     * @return array<int>
     */
    public function get_effective_requirement_ids(int $departmentId): array
    {
        return $this->get_requirement_ids($departmentId);
    }

    // ── Firmen-Einstellungen (jobs_page_url) ──────────────────────────────

    /** URL zur öffentlichen Stellenanzeigen-Seite der Firma holen. */
    public function get_jobs_url(int $companyId): string
    {
        if ($companyId <= 0) {
            return '';
        }
        $row = $this->db->get_row(
            "SELECT jobs_page_url FROM {$this->db->getPrefix()}jpg_company_settings WHERE company_id = ?",
            [$companyId]
        );
        return $row ? (string) ($row->jobs_page_url ?? '') : '';
    }

    /** jobs_page_url speichern (UPSERT). */
    public function save_jobs_url(int $companyId, string $url): void
    {
        if ($companyId <= 0) {
            return;
        }
        $p   = $this->db->getPrefix();
        $pdo = $this->db->getPdo();
        $url = filter_var(trim($url), FILTER_SANITIZE_URL);

        $pdo->prepare(
            "INSERT INTO {$p}jpg_company_settings (company_id, jobs_page_url)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE jobs_page_url = VALUES(jobs_page_url)"
        )->execute([$companyId, $url]);
    }

    // ── Vollständige Firmen-Einstellungen (jobs_page_*) ──────────────────────

    /**
     * Alle Firmen-Einstellungen (incl. neue jobs_page_*-Felder) laden.
     *
     * Gibt ein Objekt mit Default-Werten zurück, wenn noch kein Eintrag existiert.
     *
     * @since 0.9.1
     */
    public function get_company_settings(int $companyId): object
    {
        if ($companyId <= 0) {
            return $this->default_company_settings($companyId);
        }
        $row = $this->db->get_row(
            "SELECT * FROM {$this->db->getPrefix()}jpg_company_settings WHERE company_id = ?",
            [$companyId]
        );
        if (!$row) {
            return $this->default_company_settings($companyId);
        }
        // Normalisierung: Array → Object (DB-Adapter-Unterschiede)
        return is_array($row) ? (object) $row : $row;
    }

    /** Alle Firmen-Einstellungen per UPSERT speichern. */
    public function save_company_settings(int $companyId, array $data): void
    {
        if ($companyId <= 0) {
            return;
        }
        $p   = $this->db->getPrefix();
        $pdo = $this->db->getPdo();

        $url          = filter_var(trim($data['jobs_page_url']          ?? ''), FILTER_SANITIZE_URL);
        $title        = mb_substr(trim($data['jobs_page_title']          ?? ''), 0, 200);
        $intro        = trim($data['jobs_page_intro']                    ?? '');
        $contactEmail = mb_substr(trim($data['jobs_page_contact_email']  ?? ''), 0, 200);
        $showSalary   = (int) (bool) ($data['jobs_page_show_salary']     ?? 1);
        $enabled      = (int) (bool) ($data['jobs_page_enabled']         ?? 1);

        // Phase 13.1: E-Mail-Template-Felder
        $tplAccSubject = mb_substr(trim($data['email_tpl_accepted_subject'] ?? ''), 0, 500) ?: null;
        $tplAccBody    = trim($data['email_tpl_accepted_body']              ?? '') ?: null;
        $tplRejSubject = mb_substr(trim($data['email_tpl_rejected_subject'] ?? ''), 0, 500) ?: null;
        $tplRejBody    = trim($data['email_tpl_rejected_body']              ?? '') ?: null;
        $senderName    = mb_substr(trim($data['email_sender_name']          ?? ''), 0, 255) ?: null;

        $pdo->prepare(
            "INSERT INTO {$p}jpg_company_settings
                (company_id, jobs_page_url, jobs_page_title, jobs_page_intro,
                 jobs_page_contact_email, jobs_page_show_salary, jobs_page_enabled,
                 email_tpl_accepted_subject, email_tpl_accepted_body,
                 email_tpl_rejected_subject, email_tpl_rejected_body,
                 email_sender_name)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                jobs_page_url               = VALUES(jobs_page_url),
                jobs_page_title             = VALUES(jobs_page_title),
                jobs_page_intro             = VALUES(jobs_page_intro),
                jobs_page_contact_email     = VALUES(jobs_page_contact_email),
                jobs_page_show_salary       = VALUES(jobs_page_show_salary),
                jobs_page_enabled           = VALUES(jobs_page_enabled),
                email_tpl_accepted_subject  = VALUES(email_tpl_accepted_subject),
                email_tpl_accepted_body     = VALUES(email_tpl_accepted_body),
                email_tpl_rejected_subject  = VALUES(email_tpl_rejected_subject),
                email_tpl_rejected_body     = VALUES(email_tpl_rejected_body),
                email_sender_name           = VALUES(email_sender_name)"
        )->execute([
            $companyId, $url, $title, $intro, $contactEmail, $showSalary, $enabled,
            $tplAccSubject, $tplAccBody, $tplRejSubject, $tplRejBody, $senderName,
        ]);
    }

    private function default_company_settings(int $companyId): object
    {
        return (object) [
            'company_id'                 => $companyId,
            'jobs_page_url'              => '',
            'jobs_page_title'            => '',
            'jobs_page_intro'            => '',
            'jobs_page_contact_email'    => '',
            'jobs_page_show_salary'      => 1,
            'jobs_page_enabled'          => 1,
            'email_tpl_accepted_subject' => null,
            'email_tpl_accepted_body'    => null,
            'email_tpl_rejected_subject' => null,
            'email_tpl_rejected_body'    => null,
            'email_sender_name'          => null,
        ];
    }
}
