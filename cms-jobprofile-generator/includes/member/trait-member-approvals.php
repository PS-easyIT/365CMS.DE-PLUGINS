<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member-Trait: Genehmigungs-Workflow
 *
 * render_approvals()        – Standalone Route /member/jobs/approvals
 * render_approvals_inline() – Inline für PluginDashboardRegistry
 *
 * Zugriff: Admins immer; Genehmiger wenn ihre Rolle einem Workflow-Schritt
 * zugewiesen ist oder offene Einträge für sie vorliegen.
 *
 * @since   0.1.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Member_Approvals_Trait
{
    /**
     * GET|POST /member/jobs/approvals – Genehmigungsbereich (standalone)
     */
    public function render_approvals(): void
    {
        $this->require_auth();
        $user = method_exists($this->auth, 'currentUser') ? $this->auth->currentUser() : null;
        if ($user) {
            $this->userId = (int) $user->id;
        }
        $isAdmin = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;
        $notice  = '';
        $error   = '';

        // Zugriff: Admins oder Workflow-Genehmiger
        if (!$isAdmin && class_exists('CMS_JPG_Workflow')) {
            $hasAccess = false;
            try {
                $wf       = CMS_JPG_Workflow::instance();
                $userRole = $user ? (string)($user->role ?? '') : '';
                foreach ($wf->get_steps() as $step) {
                    if (!empty($step->approver_role) && $step->approver_role === $userRole) {
                        $hasAccess = true;
                        break;
                    }
                }
                if (!$hasAccess) {
                    $hasAccess = !empty($wf->get_pending_for_user($this->userId));
                }
            } catch (\Throwable $e) { /* ignore */ }
            if (!$hasAccess) {
                http_response_code(403);
                echo '<p style="color:#ef4444;">Kein Zugriff auf den Genehmigungsbereich.</p>';
                return;
            }
        }

        // POST: Genehmigen / Ablehnen / Zurücksetzen
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verify_token('member_approvals')) {
            $action    = sanitize_key($_POST['approval_action'] ?? '');
            $profileId = (int) ($_POST['profile_id'] ?? 0);
            $note      = trim(strip_tags($_POST['note'] ?? ''));
            if ($profileId > 0 && class_exists('CMS_JPG_Workflow')) {
                $wf = CMS_JPG_Workflow::instance();
                try {
                    match ($action) {
                        'approve' => ($wf->can_approve($this->userId, $profileId)
                            ? $wf->approve_step($profileId, $this->userId, $note)
                            : throw new \RuntimeException('Kein Genehmiger-Zugriff.')),
                        'reject'  => ($wf->can_approve($this->userId, $profileId)
                            ? $wf->reject_step($profileId, $this->userId, $note)
                            : throw new \RuntimeException('Kein Genehmiger-Zugriff.')),
                        'reset'   => ($isAdmin
                            ? $wf->reset_workflow($profileId)
                            : throw new \RuntimeException('Nur Admins können zurücksetzen.')),
                        default   => throw new \RuntimeException('Unbekannte Aktion.'),
                    };
                    $notice = match ($action) {
                        'approve' => 'Schritt genehmigt.',
                        'reject'  => 'Stellenanzeige abgelehnt.',
                        'reset'   => 'Workflow zurückgesetzt.',
                        default   => 'Aktion ausgeführt.',
                    };
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        if (class_exists('CMS_JPG_Workflow')) {
            $wf = CMS_JPG_Workflow::instance();
            $pendingProfiles = $isAdmin
                ? $wf->get_all_pending()
                : $wf->get_pending_for_user($this->userId);
            $wfSteps = $wf->get_steps();
        } else {
            $pendingProfiles = [];
            $wfSteps         = [];
        }

        // Owner-Sicht: eigene Profile mit Workflow-Status
        try {
            $ownerProfiles = $this->db->get_results(
                "SELECT p.id, p.title, p.status, p.workflow_status, p.workflow_step,
                        p.created_at,
                        c.name AS company_name
                 FROM {$this->p}jpg_profiles p
                 LEFT JOIN {$this->p}companies c ON c.id = p.company_id
                 WHERE (
                     p.created_by = ?
                     OR p.company_id IN (SELECT id FROM {$this->p}companies WHERE user_id = ?)
                 )
                 AND p.workflow_status != 'none'
                 ORDER BY p.created_at DESC LIMIT 50",
                [$this->userId, $this->userId]
            ) ?: [];
        } catch (\Throwable $e) {
            $ownerProfiles = [];
        }

        $csrf    = $this->generate_token('member_approvals');
        $baseUrl = '/member/jobs';
        $this->render_with_layout('views/member/page-approvals.php', compact(
            'pendingProfiles', 'wfSteps', 'ownerProfiles', 'csrf', 'notice', 'error', 'baseUrl'
        ));
    }

    // ── Inline (PluginDashboardRegistry) ─────────────────────────────────────

    public function render_approvals_inline(object $user): void
    {
        $this->userId = (int) $user->id;
        $isAdmin      = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;
        $notice       = '';
        $error        = '';

        // POST: Genehmigen / Ablehnen / Zurücksetzen
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $this->verify_token('member_approvals')) {
            $action    = sanitize_key($_POST['approval_action'] ?? '');
            $profileId = (int) ($_POST['profile_id'] ?? 0);
            $note      = trim(strip_tags($_POST['note'] ?? ''));
            if ($profileId > 0 && class_exists('CMS_JPG_Workflow')) {
                $wf = CMS_JPG_Workflow::instance();
                try {
                    match ($action) {
                        'approve' => ($wf->can_approve($this->userId, $profileId)
                            ? $wf->approve_step($profileId, $this->userId, $note)
                            : throw new \RuntimeException('Kein Genehmiger-Zugriff.')),
                        'reject'  => ($wf->can_approve($this->userId, $profileId)
                            ? $wf->reject_step($profileId, $this->userId, $note)
                            : throw new \RuntimeException('Kein Genehmiger-Zugriff.')),
                        'reset'   => ($isAdmin
                            ? $wf->reset_workflow($profileId)
                            : throw new \RuntimeException('Nur Admins können zurücksetzen.')),
                        default   => throw new \RuntimeException('Unbekannte Aktion.'),
                    };
                    $notice = match ($action) {
                        'approve' => 'Schritt genehmigt.',
                        'reject'  => 'Stellenanzeige abgelehnt.',
                        'reset'   => 'Workflow zurückgesetzt.',
                        default   => 'Aktion ausgeführt.',
                    };
                } catch (\Throwable $e) {
                    $error = $e->getMessage();
                }
            }
        }

        if (class_exists('CMS_JPG_Workflow')) {
            $wf = CMS_JPG_Workflow::instance();
            // Admins sehen alle ausstehenden Profile, Genehmiger nur ihre eigenen
            $pendingProfiles = $isAdmin
                ? $wf->get_all_pending()
                : $wf->get_pending_for_user($this->userId);
            $wfSteps = $wf->get_steps();
        } else {
            $pendingProfiles = [];
            $wfSteps         = [];
        }

        // Owner-Sicht: eigene Profile mit Workflow-Status (nicht-none)
        try {
            $ownerProfiles = $this->db->get_results(
                "SELECT p.id, p.title, p.status, p.workflow_status, p.workflow_step,
                        p.created_at,
                        c.name AS company_name
                 FROM {$this->p}jpg_profiles p
                 LEFT JOIN {$this->p}companies c ON c.id = p.company_id
                 WHERE (
                     p.created_by = ?
                     OR p.company_id IN (SELECT id FROM {$this->p}companies WHERE user_id = ?)
                 )
                 AND p.workflow_status != 'none'
                 ORDER BY p.created_at DESC LIMIT 50",
                [$this->userId, $this->userId]
            ) ?: [];
        } catch (\Throwable $e) {
            $ownerProfiles = [];
        }

        $csrf    = $this->generate_token('member_approvals');
        $baseUrl = '/member/plugin/member-jobs';
        include JPG_DIR . 'views/member/page-approvals.php';
    }
}
