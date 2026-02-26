<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Workflow Engine – Job Profile Generator
 *
 * Verwaltet den dynamischen n-stufigen Genehmigungsprozess.
 * Anzahl der Stufen ist vollständig konfigurierbar (0-n).
 *
 * Status-Modell:
 *   none      – kein Workflow, direkt als Entwurf
 *   pending   – eingereicht, wartet auf Genehmigung in Stufe workflow_step
 *   approved  – alle Stufen abgeschlossen → Status 'published' gesetzt
 *   rejected  – in einer Stufe abgelehnt
 *
 * @since   0.2.0
 * @package CMS_JobProfileGenerator
 */
class CMS_JPG_Workflow
{
    private static ?self $instance = null;

    /** @var \CMS\Database */
    private \CMS\Database $db;

    /** @var string */
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

    // ── Steps CRUD ────────────────────────────────────────────────────────────

    /**
     * Alle aktiven Schritte, sortiert nach sort_order.
     * @return array<object>
     */
    public function get_steps(): array
    {
        try {
            return $this->db->get_results(
                "SELECT * FROM {$this->p}jpg_workflow_steps
                 WHERE active = 1
                 ORDER BY sort_order ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Alle Schritte (inkl. inaktive), sortiert nach sort_order.
     * @return array<object>
     */
    public function get_all_steps(): array
    {
        try {
            return $this->db->get_results(
                "SELECT * FROM {$this->p}jpg_workflow_steps ORDER BY sort_order ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Speichert einen Workflow-Schritt (Insert oder Update).
     */
    public function save_step(array $data, int $id = 0): int
    {
        try {
            if ($id > 0) {
                $this->db->update('jpg_workflow_steps', $data, ['id' => $id]);
                return $id;
            }
            $newId = $this->db->insert('jpg_workflow_steps', $data);
            return (int) $newId;
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Workflow::save_step() error: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Löscht einen Workflow-Schritt.
     */
    public function delete_step(int $id): void
    {
        try {
            $this->db->delete('jpg_workflow_steps', ['id' => $id]);
        } catch (\Throwable $e) {
            // unkritisch
        }
    }

    /**
     * Reihenfolge aller Schritte per ID aktualisieren.
     * @param array<int, int> $orderedIds ordered list of step IDs
     */
    public function reorder_steps(array $orderedIds): void
    {
        foreach ($orderedIds as $order => $id) {
            try {
                $this->db->update(
                    'jpg_workflow_steps',
                    ['sort_order' => ($order + 1) * 10],
                    ['id' => (int) $id]
                );
            } catch (\Throwable $e) {
                // continue
            }
        }
    }

    // ── Workflow-Aktionen ─────────────────────────────────────────────────────

    /**
     * Profil zur Genehmigung einreichen.
     * Setzt workflow_status = 'pending' und legt den ersten Schritt fest.
     */
    public function submit_for_approval(int $profileId, int $userId): bool
    {
        $profile = $this->get_profile_workflow($profileId);
        if (!$profile) {
            return false;
        }

        // Data-Silo: nur eigenes Profil
        if ((int) $profile->created_by !== $userId) {
            return false;
        }

        $steps = $this->get_steps();

        if (empty($steps)) {
            // Kein Workflow konfiguriert → Admin muss manuell freigeben
            try {
                $this->db->update(
                    'jpg_profiles',
                    ['workflow_status' => 'pending', 'workflow_step' => 0],
                    ['id' => $profileId]
                );
                $this->log_history($profileId, 0, 'submitted', $userId, 'Kein Workflow-Schritt konfiguriert.');
                return true;
            } catch (\Throwable $e) {
                return false;
            }
        }

        $firstStep = (int) $steps[0]->sort_order;

        try {
            $this->db->update(
                'jpg_profiles',
                ['workflow_status' => 'pending', 'workflow_step' => $firstStep],
                ['id' => $profileId]
            );
            $this->log_history($profileId, $firstStep, 'submitted', $userId, '');
            return true;
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Workflow::submit_for_approval() error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Aktuellen Workflow-Schritt genehmigen.
     * Wenn letzter Schritt → Profil wird veröffentlicht.
     */
    public function approve_step(int $profileId, int $actorId, string $note = ''): bool
    {
        $profile = $this->get_profile_workflow($profileId);
        if (!$profile || $profile->workflow_status !== 'pending') {
            return false;
        }

        $steps      = $this->get_steps();
        $stepOrders = array_map(fn($s) => (int) $s->sort_order, $steps);
        $currentStep = (int) $profile->workflow_step;
        $currentIdx  = array_search($currentStep, $stepOrders, true);

        // Schritt als genehmigt loggen
        $this->log_history($profileId, $currentStep, 'approved', $actorId, $note);

        // Letzter Schritt oder kein nächster?
        if ($currentIdx === false || $currentIdx >= (count($stepOrders) - 1)) {
            // Alle Schritte abgeschlossen → veröffentlichen
            try {
                $this->db->update(
                    'jpg_profiles',
                    [
                        'workflow_status' => 'approved',
                        'workflow_step'   => 0,
                        'status'          => 'published',
                        'published_at'    => date('Y-m-d H:i:s'),
                    ],
                    ['id' => $profileId]
                );
                return true;
            } catch (\Throwable $e) {
                error_log('CMS_JPG_Workflow::approve_step() publish error: ' . $e->getMessage());
                return false;
            }
        }

        // Nächsten Schritt aktivieren
        $nextStep = $stepOrders[$currentIdx + 1];
        try {
            $this->db->update(
                'jpg_profiles',
                ['workflow_step' => $nextStep],
                ['id' => $profileId]
            );
            return true;
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Workflow::approve_step() next-step error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Aktuellen Workflow-Schritt ablehnen.
     * Profil erhält workflow_status = 'rejected'.
     */
    public function reject_step(int $profileId, int $actorId, string $note = ''): bool
    {
        $profile = $this->get_profile_workflow($profileId);
        if (!$profile || $profile->workflow_status !== 'pending') {
            return false;
        }

        $this->log_history($profileId, (int) $profile->workflow_step, 'rejected', $actorId, $note);

        try {
            $this->db->update(
                'jpg_profiles',
                ['workflow_status' => 'rejected'],
                ['id' => $profileId]
            );
            return true;
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Workflow::reject_step() error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Workflow zurücksetzen → workflow_status = 'none', Profil bleibt Entwurf.
     */
    public function reset_workflow(int $profileId, int $actorId, string $note = ''): void
    {
        $this->log_history($profileId, 0, 'reset', $actorId, $note ?: 'Zurückgesetzt auf Entwurf.');
        try {
            $this->db->update(
                'jpg_profiles',
                ['workflow_status' => 'none', 'workflow_step' => 0, 'status' => 'draft'],
                ['id' => $profileId]
            );
        } catch (\Throwable $e) {
            // unkritisch
        }
    }

    // ── Berechtigungsprüfung ──────────────────────────────────────────────────

    /**
     * Kann der User das aktuelle Profil im aktuellen Schritt genehmigen?
     * Strikte Prüfung per direkter DB-Abfrage (Robust Role-Handling).
     * Phase 9: Zusätzlich Team-Genehmiger aus jpg_team_approvers berücksichtigen.
     */
    public function can_approve(int $userId, int $profileId): bool
    {
        $profile = $this->get_profile_workflow($profileId);
        if (!$profile || $profile->workflow_status !== 'pending') {
            return false;
        }

        $stepOrder = (int) $profile->workflow_step;
        if ($stepOrder === 0) {
            // Kein Schritt konfiguriert → Admin oder Team-Genehmiger der Firma
            return $this->user_is_admin($userId)
                || $this->user_is_team_approver_for_profile($userId, $profileId);
        }

        $step = $this->get_step_by_order($stepOrder);
        if (!$step) {
            return $this->user_is_admin($userId)
                || $this->user_is_team_approver_for_profile($userId, $profileId);
        }

        // Standard-Rollenprüfung ODER Team-Genehmiger
        return $this->user_has_role($userId, $step->approver_role)
            || $this->user_is_team_approver_for_profile($userId, $profileId);
    }

    /**
     * Prüft ob der User als Team-Genehmiger für die Firma des Profils eingetragen ist.
     */
    private function user_is_team_approver_for_profile(int $userId, int $profileId): bool
    {
        try {
            $result = $this->db->get_var(
                "SELECT ta.id
                 FROM {$this->p}jpg_team_approvers ta
                 INNER JOIN {$this->p}jpg_profiles p ON p.company_id = ta.company_id
                 WHERE p.id = ? AND ta.approver_user_id = ?
                 LIMIT 1",
                [$profileId, $userId]
            );
            return !empty($result);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Prüft ob User eine bestimmte Rolle hat.
     * Direkter DB-Zugriff als robuster Fallback (nicht nur Helper-Funktionen).
     */
    public function user_has_role(int $userId, string $role): bool
    {
        if ($this->user_is_admin($userId)) {
            return true; // Admin darf immer
        }

        // Direkte Abfrage auf cms_users.role
        try {
            $userRole = (string) $this->db->get_var(
                "SELECT `role` FROM {$this->p}users WHERE id = ?",
                [$userId]
            );

            if ($userRole === $role) {
                return true;
            }

            // Fallback: Capabilities aus cms_roles prüfen
            $caps = $this->db->get_var(
                "SELECT r.capabilities
                 FROM {$this->p}users u
                 INNER JOIN {$this->p}roles r ON r.name = u.role
                 WHERE u.id = ?",
                [$userId]
            );

            if ($caps) {
                $capArray = json_decode((string) $caps, true) ?: [];
                if (in_array('approve_job_profiles', $capArray, true)
                    || in_array($role, $capArray, true)) {
                    return true;
                }
            }
        } catch (\Throwable $e) {
            // Fehler → kein Zugriff als Fallback
        }

        return false;
    }

    // ── Pending-Abfragen ──────────────────────────────────────────────────────

    /**
     * Alle ausstehenden Profile für einen bestimmten Genehmiger (nach Rolle).
     * Phase 9: Berücksichtigt auch Team-Genehmiger aus jpg_team_approvers.
     * @return array<object>
     */
    public function get_pending_for_user(int $userId): array
    {
        if ($this->user_is_admin($userId)) {
            return $this->get_all_pending();
        }

        try {
            // Eigene Rolle ermitteln
            $userRole = (string) $this->db->get_var(
                "SELECT `role` FROM {$this->p}users WHERE id = ?",
                [$userId]
            );

            // Profile über Workflow-Rolle
            $byRole = $this->db->get_results(
                "SELECT p.id, p.title, p.workflow_step, p.created_at,
                        u.display_name AS author_name, ws.step_name
                 FROM {$this->p}jpg_profiles p
                 LEFT JOIN {$this->p}jpg_workflow_steps ws ON ws.sort_order = p.workflow_step AND ws.active = 1
                 LEFT JOIN {$this->p}users u ON u.id = p.created_by
                 WHERE p.workflow_status = 'pending'
                   AND ws.approver_role = ?
                 ORDER BY p.created_at ASC",
                [$userRole]
            ) ?: [];

            // Profile über Team-Genehmiger-Eintrag (Phase 9)
            $byTeam = $this->db->get_results(
                "SELECT p.id, p.title, p.workflow_step, p.created_at,
                        u.display_name AS author_name, ws.step_name
                 FROM {$this->p}jpg_profiles p
                 INNER JOIN {$this->p}jpg_team_approvers ta ON ta.company_id = p.company_id
                 LEFT JOIN {$this->p}jpg_workflow_steps ws ON ws.sort_order = p.workflow_step AND ws.active = 1
                 LEFT JOIN {$this->p}users u ON u.id = p.created_by
                 WHERE p.workflow_status = 'pending'
                   AND ta.approver_user_id = ?
                 ORDER BY p.created_at ASC",
                [$userId]
            ) ?: [];

            // Deduplizieren (UNION per PHP)
            $seen   = [];
            $result = [];
            foreach (array_merge($byRole, $byTeam) as $row) {
                if (!isset($seen[(int) $row->id])) {
                    $seen[(int) $row->id] = true;
                    $result[] = $row;
                }
            }
            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Alle Profile mit workflow_status = 'pending' (für Admin-Übersicht).
     * @return array<object>
     */
    public function get_all_pending(): array
    {
        try {
            return $this->db->get_results(
                "SELECT p.id, p.title, p.workflow_step, p.workflow_status,
                        p.created_at, u.display_name AS author_name,
                        ws.step_name, ws.approver_role
                 FROM {$this->p}jpg_profiles p
                 LEFT JOIN {$this->p}jpg_workflow_steps ws ON ws.sort_order = p.workflow_step
                 LEFT JOIN {$this->p}users u ON u.id = p.created_by
                 WHERE p.workflow_status = 'pending'
                 ORDER BY p.created_at ASC",
                []
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Abgelehnte Profile (für Member-Ansicht).
     * @return array<object>
     */
    public function get_rejected_for_user(int $userId): array
    {
        try {
            return $this->db->get_results(
                "SELECT p.id, p.title, p.workflow_step, p.created_at
                 FROM {$this->p}jpg_profiles p
                 WHERE p.created_by = ? AND p.workflow_status = 'rejected'
                 ORDER BY p.created_at DESC",
                [$userId]
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── History ───────────────────────────────────────────────────────────────

    /**
     * Vollständige Workflow-History für ein Profil.
     * @return array<object>
     */
    public function get_history(int $profileId): array
    {
        try {
            return $this->db->get_results(
                "SELECT h.*, u.display_name AS actor_name
                 FROM {$this->p}jpg_workflow_history h
                 LEFT JOIN {$this->p}users u ON u.id = h.actor_id
                 WHERE h.profile_id = ?
                 ORDER BY h.created_at ASC",
                [$profileId]
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── Hilfsmethoden ─────────────────────────────────────────────────────────

    /**
     * Workflow-Daten eines Profils laden.
     */
    public function get_profile_workflow(int $profileId): ?object
    {
        try {
            return $this->db->get_row(
                "SELECT id, workflow_status, workflow_step, status, created_by
                 FROM {$this->p}jpg_profiles WHERE id = ?",
                [$profileId]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Schritt per sort_order laden.
     */
    private function get_step_by_order(int $sortOrder): ?object
    {
        try {
            return $this->db->get_row(
                "SELECT * FROM {$this->p}jpg_workflow_steps
                 WHERE sort_order = ? AND active = 1",
                [$sortOrder]
            );
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Prüft ob User Admin ist (direkter DB-Zugriff als Fallback).
     */
    private function user_is_admin(int $userId): bool
    {
        try {
            // Primär: CMS\Auth verwenden
            if (class_exists('CMS\\Auth') && \CMS\Auth::instance()->isAdmin()) {
                return true;
            }
            // Fallback: direkte Datenbankabfrage
            $role = (string) $this->db->get_var(
                "SELECT `role` FROM {$this->p}users WHERE id = ?",
                [$userId]
            );
            return in_array($role, ['admin', 'superadmin'], true);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Workflow-Ereignis loggen.
     */
    private function log_history(
        int    $profileId,
        int    $stepOrder,
        string $action,
        int    $actorId,
        string $note
    ): void {
        try {
            $this->db->insert('jpg_workflow_history', [
                'profile_id' => $profileId,
                'step_order' => $stepOrder,
                'action'     => $action,
                'actor_id'   => $actorId,
                'note'       => $note,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Workflow::log_history() error: ' . $e->getMessage());
        }
    }

    // ── Phase 9: Team-Genehmiger CRUD ────────────────────────────────────────

    /**
     * Alle Team-Genehmiger einer Firma laden.
     * @return array<object> [{approver_user_id, username, email, firstname, lastname, created_at}]
     */
    public function get_team_approvers(int $companyId): array
    {
        try {
            return $this->db->get_results(
                "SELECT ta.id, ta.approver_user_id, ta.created_at,
                        u.username, u.email,
                        u.firstname, u.lastname
                 FROM {$this->p}jpg_team_approvers ta
                 LEFT JOIN {$this->p}users u ON u.id = ta.approver_user_id
                 WHERE ta.company_id = ?
                 ORDER BY ta.created_at ASC",
                [$companyId]
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Fügt einen Team-Genehmiger für eine Firma hinzu.
     * Prüft, dass die Firma dem aufrufenden User gehört (data-silo).
     *
     * @param int $companyId         Firma-ID aus cms_companies
     * @param int $approverUserId    CMS-User-ID der genehmigenden Person
     * @param int $createdBy         User-ID des Erstellers (muss Firmen-Owner sein)
     * @return bool true on success
     */
    public function add_team_approver(int $companyId, int $approverUserId, int $createdBy): bool
    {
        if ($companyId <= 0 || $approverUserId <= 0) {
            return false;
        }
        // Sicherheitsprüfung: Firma muss dem createdBy-User gehören
        try {
            $ownerCheck = $this->db->get_var(
                "SELECT id FROM {$this->p}companies WHERE id = ? AND user_id = ? LIMIT 1",
                [$companyId, $createdBy]
            );
            if (empty($ownerCheck)) {
                return false; // Firma gehört nicht diesem User
            }
            $this->db->insert('jpg_team_approvers', [
                'company_id'       => $companyId,
                'approver_user_id' => $approverUserId,
                'created_by'       => $createdBy,
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
            return true;
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Workflow::add_team_approver() error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Entfernt einen Team-Genehmiger.
     * Prüft, dass die Firma dem aufrufenden User gehört (data-silo).
     */
    public function remove_team_approver(int $companyId, int $approverUserId, int $requestedBy): void
    {
        try {
            // Sicherheitsprüfung: Firma muss dem requestedBy-User gehören
            $ownerCheck = $this->db->get_var(
                "SELECT id FROM {$this->p}companies WHERE id = ? AND user_id = ? LIMIT 1",
                [$companyId, $requestedBy]
            );
            if (empty($ownerCheck) && !$this->user_is_admin($requestedBy)) {
                return; // Kein Zugriff
            }
            $this->db->delete('jpg_team_approvers', [
                'company_id'       => $companyId,
                'approver_user_id' => $approverUserId,
            ]);
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Workflow::remove_team_approver() error: ' . $e->getMessage());
        }
    }

    // ── Phase 9: Admin-Zugriffs-Log ───────────────────────────────────────────

    /**
     * Schreibt einen DSGVO-Audit-Log-Eintrag für Admin-Zugriffe auf Mandanten-Daten.
     *
     * @param int    $adminUserId      ID des Admins
     * @param int    $targetCompanyId  Betrachtete Firma (0 wenn keiner)
     * @param int    $targetProfileId  Betrachtetes Profil (0 wenn keines)
     * @param string $action           z. B. 'view_company', 'view_profile', 'edit_company'
     */
    public static function log_admin_access(
        int    $adminUserId,
        int    $targetCompanyId = 0,
        int    $targetProfileId = 0,
        string $action = 'view_company'
    ): void {
        try {
            $db = \CMS\Database::instance();
            $p  = $db->getPrefix();
            $ip = self::get_client_ip();
            $db->insert('jpg_admin_access_log', [
                'admin_user_id'     => $adminUserId,
                'target_company_id' => $targetCompanyId > 0 ? $targetCompanyId : null,
                'target_profile_id' => $targetProfileId > 0 ? $targetProfileId : null,
                'action'            => $action,
                'ip_addr'           => $ip,
                'created_at'        => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Workflow::log_admin_access() error: ' . $e->getMessage());
        }
    }

    /** Client-IP ermitteln (sicherer Fallback-Chain) */
    private static function get_client_ip(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            $val = $_SERVER[$key] ?? '';
            if (!empty($val)) {
                // Bei X-Forwarded-For nur erste IP nehmen
                $ip = trim(explode(',', $val)[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '';
    }

    // ── Audit-Log Abfragen (für Admin-Ansicht) ────────────────────────────────

    /**
     * Letzte N Audit-Log-Einträge (für Admin-Dashboard-Anzeige).
     * @return array<object>
     */
    public static function get_audit_log(int $limit = 50, int $companyId = 0): array
    {
        try {
            $db     = \CMS\Database::instance();
            $p      = $db->getPrefix();
            $params = [];
            $where  = '';
            if ($companyId > 0) {
                $where    = 'WHERE l.target_company_id = ?';
                $params[] = $companyId;
            }
            return $db->get_results(
                "SELECT l.id, l.action, l.ip_addr, l.created_at,
                        u.username AS admin_name, u.email AS admin_email,
                        c.name AS company_name,
                        p.title AS profile_title
                 FROM {$p}jpg_admin_access_log l
                 LEFT JOIN {$p}users u ON u.id = l.admin_user_id
                 LEFT JOIN {$p}companies c ON c.id = l.target_company_id
                 LEFT JOIN {$p}jpg_profiles p ON p.id = l.target_profile_id
                 {$where}
                 ORDER BY l.created_at DESC
                 LIMIT " . max(1, (int) $limit),
                $params
            ) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    // ── Statische Helfer ──────────────────────────────────────────────────────

    /**
     * Lädt ALLE Rollen aus der Datenbank (inkl. benutzerdefinierter Rollen).
     * Direkter DB-Zugriff – nicht nur Fallback auf globale Helper.
     *
     * @return array<string, string> [name => display_name]
     */
    public static function get_all_cms_roles(): array
    {
        try {
            $db   = \CMS\Database::instance();
            $p    = $db->getPrefix();
            $rows = $db->get_results(
                "SELECT name, display_name
                 FROM {$p}roles
                 ORDER BY sort_order ASC, display_name ASC",
                []
            ) ?: [];

            $result = [];
            foreach ($rows as $row) {
                if (!empty($row->name)) {
                    $result[$row->name] = $row->display_name ?: $row->name;
                }
            }

            return $result ?: ['admin' => 'Admin'];
        } catch (\Throwable $e) {
            // Fallback auf Basis-Rollen
            return [
                'admin'  => 'Admin',
                'editor' => 'Redakteur',
                'author' => 'Autor',
                'member' => 'Mitglied',
            ];
        }
    }

    /**
     * Workflow-Status als lesbares Label.
     */
    public static function status_label(string $status): string
    {
        return match ($status) {
            'none'     => '📝 Entwurf',
            'pending'  => '⏳ Ausstehend',
            'approved' => '✅ Genehmigt',
            'rejected' => '❌ Abgelehnt',
            default    => htmlspecialchars($status),
        };
    }

    /**
     * Workflow-Status-Badge-Klasse für CSS.
     */
    public static function status_badge_class(string $status): string
    {
        return match ($status) {
            'approved' => 'active',
            'rejected' => 'danger',
            'pending'  => 'warning',
            default    => 'inactive',
        };
    }
}
