<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait: DSGVO Art. 20 Datenexport + Art. 17 Account-Löschung
 *
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Member_Dsgvo_Trait
{
    /**
     * DSGVO Art. 20 – Datenexport (Hook: cms_member_data_export_requested).
     *
     * @param  array<string, mixed> $exportData
     * @param  int                  $userId
     * @return array<string, mixed>
     */
    public function handle_data_export(array $exportData, int $userId): array
    {
        try {
            $db = $this->db;
            $p  = $this->p;

            $profiles = $db->get_results(
                "SELECT id, title, slug, status, workflow_status, location,
                        employment_type, summary, created_at, updated_at
                 FROM {$p}jpg_profiles
                 WHERE created_by = ? AND status != 'trash'
                 ORDER BY created_at DESC",
                [$userId]
            ) ?: [];

            $exportProfiles = [];
            foreach ($profiles as $profile) {
                $tasks = $db->get_results(
                    "SELECT task_text, sort_order FROM {$p}jpg_profile_tasks
                     WHERE profile_id = ? ORDER BY sort_order",
                    [(int) $profile->id]
                ) ?: [];
                $requirements = $db->get_results(
                    "SELECT req_text, req_type FROM {$p}jpg_profile_requirements
                     WHERE profile_id = ? ORDER BY sort_order",
                    [(int) $profile->id]
                ) ?: [];
                $applications = $db->get_results(
                    "SELECT applicant_name, applicant_email, status, created_at
                     FROM {$p}jpg_applications WHERE job_id = ?
                     ORDER BY created_at DESC",
                    [(int) $profile->id]
                ) ?: [];

                $exportProfiles[] = [
                    'id'           => (int) $profile->id,
                    'title'        => $profile->title,
                    'slug'         => $profile->slug,
                    'status'       => $profile->status,
                    'location'     => $profile->location,
                    'summary'      => $profile->summary,
                    'created_at'   => $profile->created_at,
                    'updated_at'   => $profile->updated_at,
                    'tasks'        => array_map(fn($t): array => ['text' => $t->task_text], $tasks),
                    'requirements' => array_map(fn($r): array => ['text' => $r->req_text, 'type' => $r->req_type], $requirements),
                    'applications' => array_map(fn($a): array => [
                        'applicant_name'  => $a->applicant_name,
                        'applicant_email' => $a->applicant_email,
                        'status'          => $a->status,
                        'date'            => $a->created_at,
                    ], $applications),
                ];
            }

            $exportData['job_profiles'] = [
                '_info'     => 'Eigene Stellenanzeigen und eingegangene Bewerbungen',
                '_exported' => date('c'),
                'count'     => count($exportProfiles),
                'profiles'  => $exportProfiles,
            ];
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Member_Controller::handle_data_export() error: ' . $e->getMessage());
        }

        return $exportData;
    }

    /**
     * DSGVO Art. 17 – Account-Löschung (Hook: cms_member_account_deletion_requested).
     *
     * Setzt alle Job-Profile auf `trash`, anonymisiert Bewerbungsdaten und
     * löscht hochgeladene CV-Dateien physisch.
     *
     * @param int $userId
     */
    public function handle_account_deletion(int $userId): void
    {
        try {
            $db = $this->db;
            $p  = $this->p;

            $db->execute(
                "UPDATE {$p}jpg_profiles SET status = 'trash', updated_at = NOW() WHERE created_by = ?",
                [$userId]
            );

            $profileIds = $db->get_results(
                "SELECT id FROM {$p}jpg_profiles WHERE created_by = ?",
                [$userId]
            ) ?: [];

            foreach ($profileIds as $row) {
                $pid = (int) $row->id;

                $cvFiles = $db->get_results(
                    "SELECT cv_file_path FROM {$p}jpg_applications WHERE job_id = ? AND cv_file_path IS NOT NULL",
                    [$pid]
                ) ?: [];

                foreach ($cvFiles as $cv) {
                    $safePath = $this->resolve_cv_storage_path((string) ($cv->cv_file_path ?? ''));
                    if ($safePath !== '' && is_file($safePath) && !unlink($safePath)) {
                        error_log('CMS_JPG_Member_Controller::handle_account_deletion() failed to delete CV file.');
                    }
                }

                $db->execute(
                    "UPDATE {$p}jpg_applications
                     SET applicant_name  = '[gelöscht]',
                         applicant_email = '[gelöscht]',
                         applicant_phone = NULL,
                         cover_letter    = '[Inhalt gemäß DSGVO gelöscht]',
                         cv_file_path    = NULL,
                         cv_file_token   = NULL,
                         updated_at      = NOW()
                     WHERE job_id = ?",
                    [$pid]
                );
            }

            error_log("CMS_JPG_Member_Controller: DSGVO-Löschung für User {$userId} abgeschlossen.");
        } catch (\Throwable $e) {
            error_log('CMS_JPG_Member_Controller::handle_account_deletion() error: ' . $e->getMessage());
        }
    }

    private function resolve_cv_storage_path(string $storedPath): string
    {
        $storedPath = trim($storedPath);
        if ($storedPath === '') {
            return '';
        }

        $baseDir = defined('UPLOADS_PATH')
            ? rtrim((string) UPLOADS_PATH, '/\\') . '/'
            : rtrim((defined('ABSPATH') ? ABSPATH : dirname(__DIR__, 4)) . '/uploads/', '/\\') . '/';

        $baseReal = realpath($baseDir);
        if ($baseReal === false) {
            return '';
        }

        $candidate = $storedPath;
        $isAbsolute = preg_match('#^[A-Za-z]:[\\\\/]#', $candidate) === 1 || str_starts_with($candidate, '/');
        if (!$isAbsolute) {
            $candidate = $baseDir . ltrim($candidate, '/\\');
        }

        $realPath = realpath($candidate);
        if ($realPath === false) {
            return '';
        }

        $normalizedBase = rtrim(str_replace('\\', '/', $baseReal), '/') . '/';
        $normalizedReal = str_replace('\\', '/', $realPath);
        if (!str_starts_with($normalizedReal, $normalizedBase)) {
            return '';
        }

        return $realPath;
    }
}
