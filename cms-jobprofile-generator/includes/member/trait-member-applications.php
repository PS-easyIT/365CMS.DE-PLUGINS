<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Member-Trait: Bewerbungs-Verwaltung
 *
 * render_applications()        – Standalone Route /member/jobs/applications
 * ajax_update_status()         – AJAX POST: Status ändern (owner-only, Data-Silo)
 * render_applications_inline() – Inline für PluginDashboardRegistry
 * download_file()              – Sicherer CV-Download via Token
 *
 * @since   0.1.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Member_Applications_Trait
{
    /**
     * GET /member/jobs/applications – Bewerbungs-Postfach (standalone)
     */
    public function render_applications(): void
    {
        $this->require_auth();

        $jobId   = (int) ($_GET['job_id'] ?? 0);
        $isAdmin = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;

        try {
            // Admins sehen alle Bewerbungen; Member nur Bewerbungen zu ihren eigenen Jobs (data silo)
            $sql = "SELECT a.id, a.applicant_name, a.applicant_email, a.applicant_phone, a.cover_letter,
                           a.cv_file_token, a.status, a.created_at,
                           p.title AS job_title, p.id AS job_id
                    FROM {$this->p}jpg_applications a
                    INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id";
            $params = [];
            if (!$isAdmin) {
                $sql     .= ' WHERE p.created_by = ?';
                $params[] = $this->userId;
            }
            if ($jobId > 0) {
                $sql     .= ($isAdmin ? ' WHERE' : ' AND') . ' a.job_id = ?';
                $params[] = $jobId;
            }
            $sql .= ' ORDER BY a.created_at DESC';
            $applications = $this->db->get_results($sql, $params) ?: [];
        } catch (\Throwable $e) {
            $applications = [];
        }

        // Jobs für Filter-Dropdown (Admins: alle; Member: eigene)
        try {
            $myJobs = $isAdmin
                ? ($this->db->get_results(
                    "SELECT id, title FROM {$this->p}jpg_profiles
                     WHERE status = 'published' ORDER BY title ASC"
                  ) ?: [])
                : ($this->db->get_results(
                    "SELECT id, title FROM {$this->p}jpg_profiles
                     WHERE created_by = ? AND status = 'published'
                     ORDER BY title ASC",
                    [$this->userId]
                  ) ?: []);
        } catch (\Throwable $e) {
            $myJobs = [];
        }

        $csrf = $this->generate_token('app_status');
        $this->render_with_layout('views/member/page-jobs-applications.php',
            compact('applications', 'myJobs', 'jobId', 'csrf'));
    }

    /**
     * POST /member/jobs/applications/status – AJAX Status-Update
     */
    public function ajax_update_status(): void
    {
        $this->require_auth();

        header('Content-Type: application/json');

        if (!$this->verify_token('app_status')) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        $appId     = (int) ($_POST['application_id'] ?? 0);
        $newStatus = sanitize_key($_POST['status'] ?? '');

        if ($appId <= 0 || !in_array($newStatus, ['new', 'reviewing', 'rejected', 'accepted'], true)) {
            echo json_encode(['success' => false, 'error' => 'Ungültige Parameter.']);
            exit;
        }

        $isAdmin = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;

        // Data-Silo: Bewerbung muss zu eigenem Job gehören (Admins bypassen diesen Check)
        if (!$isAdmin) {
            try {
                $ownerCheck = $this->db->get_var(
                    "SELECT a.id FROM {$this->p}jpg_applications a
                     INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                     WHERE a.id = ? AND p.created_by = ?",
                    [$appId, $this->userId]
                );
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'error' => 'Datenbankfehler.']);
                exit;
            }
            if (!$ownerCheck) {
                http_response_code(403);
                echo json_encode(['success' => false, 'error' => 'Kein Zugriff.']);
                exit;
            }
        }

        try {
            $this->db->update(
                'jpg_applications',
                ['status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')],
                ['id' => $appId]
            );

            // Phase 13.1: Status-Mailer – Bewerber informieren bei Annahme/Ablehnung
            if (in_array($newStatus, ['accepted', 'rejected'], true)) {
                try {
                    $app = $this->db->get_row(
                        "SELECT a.applicant_name, a.applicant_email,
                                p.title AS job_title, p.created_by,
                                u.email AS owner_email
                         FROM {$this->p}jpg_applications a
                         INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                         LEFT JOIN {$this->p}users u ON u.id = p.created_by
                         WHERE a.id = ?",
                        [$appId]
                    );
                    if ($app && filter_var($app->applicant_email ?? '', FILTER_VALIDATE_EMAIL)) {
                        $fromEmail = filter_var($app->owner_email ?? '', FILTER_VALIDATE_EMAIL)
                            ? $app->owner_email
                            : 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

                        // Firmenspezifische E-Mail-Templates laden (Phase 13.1)
                        $settings            = null;
                        $senderName          = '';
                        $companyNameFallback = '';
                        if (class_exists('CMS_JPG_Departments')) {
                            try {
                                $companyRow = $this->db->get_row(
                                    "SELECT id, name FROM {$this->p}jpg_companies WHERE user_id = ?",
                                    [(int)($app->created_by ?? 0)]
                                );
                                if ($companyRow) {
                                    $settings            = CMS_JPG_Departments::instance()->get_company_settings((int)$companyRow->id);
                                    $senderName          = $settings->email_sender_name ?? '';
                                    $companyNameFallback = $companyRow->name ?? '';
                                }
                            } catch (\Throwable $e) { /* ignore */ }
                        }

                        $jobTitle     = $app->job_title ?? '';
                        $appName      = $app->applicant_name ?? 'Bewerber(in)';
                        $placeholders = ['{name}' => $appName, '{stelle}' => $jobTitle, '{firma}' => $companyNameFallback];

                        $defaultSubject = 'Update zu Ihrer Bewerbung: ' . $jobTitle;
                        if ($newStatus === 'accepted') {
                            $rawSubject = $settings->email_tpl_accepted_subject ?? '';
                            $rawBody    = $settings->email_tpl_accepted_body
                                ?? "Sehr geehrte(r) {name},\r\n\r\nwir freuen uns, Ihnen mitteilen zu können, dass Ihre Bewerbung auf die Stelle \"{stelle}\" bei {firma} erfolgreich war.\r\n\r\nWir werden uns in Kürze mit Ihnen in Verbindung setzen.\r\n\r\nMit freundlichen Grüßen\r\n{firma}";
                        } else {
                            $rawSubject = $settings->email_tpl_rejected_subject ?? '';
                            $rawBody    = $settings->email_tpl_rejected_body
                                ?? "Sehr geehrte(r) {name},\r\n\r\nvielen Dank für Ihre Bewerbung auf die Stelle \"{stelle}\" bei {firma}.\r\n\r\nNach sorgfältiger Prüfung müssen wir Ihnen leider mitteilen, dass wir Ihre Bewerbung nicht weiter verfolgen werden.\r\n\r\nWir wünschen Ihnen viel Erfolg.\r\n\r\nMit freundlichen Grüßen\r\n{firma}";
                        }
                        $subject  = str_replace(array_keys($placeholders), array_values($placeholders), $rawSubject !== '' ? $rawSubject : $defaultSubject);
                        $body     = str_replace(array_keys($placeholders), array_values($placeholders), $rawBody);
                        $fromName = $senderName !== '' ? $senderName : $companyNameFallback;
                        $headers  = ($fromName !== '' ? 'From: ' . $fromName . ' <' . $fromEmail . '>' : 'From: ' . $fromEmail)
                                  . "\r\nContent-Type: text/plain; charset=UTF-8";
                        @mail($app->applicant_email, $subject, $body, $headers);
                    }
                } catch (\Throwable $e) { /* Mailer ist nicht kritisch */ }
            }

            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * GET /member/jobs/download/:token – Sicherer CV-Download
     *
     * Gibt eine Bewerber-Datei zurück, nachdem geprüft wurde,
     * dass der eingeloggte User der Besitzer des zugehörigen Jobs ist.
     */
    public function download_file(string $token): void
    {
        $this->require_auth();

        $token = preg_replace('/[^a-f0-9]/', '', strtolower($token));
        if (strlen($token) < 32) {
            http_response_code(400);
            exit;
        }

        try {
            $application = $this->db->get_row(
                "SELECT a.cv_file_path, a.applicant_name, p.created_by
                 FROM {$this->p}jpg_applications a
                 INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id
                 WHERE a.cv_file_token = ?",
                [$token]
            );
        } catch (\Throwable $e) {
            http_response_code(500);
            exit;
        }

        // Data-Silo: nur eigene Jobs – Admins überspringen diesen Check
        $isAdmin = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;
        if (!$application || (!$isAdmin && (int) $application->created_by !== $this->userId)) {
            http_response_code(403);
            echo 'Kein Zugriff.';
            exit;
        }

        $filePath = $application->cv_file_path;

        // Sicherstellen, dass der Pfad nicht außerhalb uploads/ liegt
        $uploadsBase = defined('UPLOADS_PATH') ? UPLOADS_PATH : (ABSPATH . 'uploads/');
        $realFile    = realpath($uploadsBase . ltrim($filePath, '/'));
        $realBase    = realpath($uploadsBase);

        if (!$realFile || !str_starts_with($realFile, (string) $realBase) || !is_file($realFile)) {
            http_response_code(404);
            exit;
        }

        $mime = mime_content_type($realFile) ?: 'application/octet-stream';
        // Nur PDF und gängige Dokument-Typen erlauben
        if (!in_array($mime, ['application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true)) {
            http_response_code(403);
            exit;
        }

        $ext      = pathinfo($realFile, PATHINFO_EXTENSION);
        $filename = 'CV-' . preg_replace('/[^a-z0-9\-_]/i', '_', $application->applicant_name) . '.' . $ext;

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($realFile));
        header('X-Content-Type-Options: nosniff');
        readfile($realFile);
        exit;
    }

    // ── Inline (PluginDashboardRegistry) ─────────────────────────────────────

    public function render_applications_inline(object $user): void
    {
        $this->userId = (int) $user->id;
        $jobId        = (int) ($_GET['job_id'] ?? 0);
        $isAdmin      = method_exists($this->auth, 'isAdmin') ? $this->auth->isAdmin() : false;
        try {
            // Admins sehen alle Bewerbungen; Member nur eigene
            $sql    = "SELECT a.id, a.applicant_name, a.applicant_email, a.applicant_phone, a.cover_letter,
                              a.cv_file_token, a.status, a.created_at,
                              p.title AS job_title, p.id AS job_id
                       FROM {$this->p}jpg_applications a
                       INNER JOIN {$this->p}jpg_profiles p ON p.id = a.job_id";
            $params = [];
            if (!$isAdmin) {
                $sql    .= ' WHERE p.created_by = ?';
                $params[] = $this->userId;
            }
            if ($jobId > 0) {
                $sql    .= ($isAdmin ? ' WHERE' : ' AND') . ' a.job_id = ?';
                $params[] = $jobId;
            }
            $sql .= ' ORDER BY a.created_at DESC';
            $applications = $this->db->get_results($sql, $params) ?: [];
            $myJobs = $isAdmin
                ? ($this->db->get_results(
                    "SELECT id, title FROM {$this->p}jpg_profiles
                     WHERE status = 'published' ORDER BY title ASC"
                  ) ?: [])
                : ($this->db->get_results(
                    "SELECT id, title FROM {$this->p}jpg_profiles
                     WHERE created_by = ? AND status = 'published' ORDER BY title ASC",
                    [$this->userId]
                  ) ?: []);
        } catch (\Throwable $e) {
            $applications = [];
            $myJobs       = [];
        }
        $csrf    = $this->generate_token('app_status');
        $baseUrl = '/member/plugin/member-jobs';
        include JPG_DIR . 'views/member/page-jobs-applications.php';
    }
}
