<?php
/**
 * CMS Contact – Admin Submissions Trait
 *
 * Übersicht und Verwaltung eingehender Nachrichten.
 *
 * @package CMS_Contact
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Contact_Page_Submissions_Trait
{
    /**
     * Nachrichten-Seite rendern
     */
    public static function render_submissions(): void
    {
        self::check_access();
        self::enqueue_admin_assets();

        $action = $_GET['action'] ?? 'list';
        $notice = '';
        $error  = '';

        // POST-Aktionen
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postResult = self::process_submissions_post();
            $notice = $postResult['notice'] ?? '';
            $error = $postResult['error'] ?? '';
        }

        // GET-Notices
        if (!empty($_GET['notice'])) {
            $notice = match ($_GET['notice']) {
                'deleted' => 'Nachricht wurde gelöscht.',
                default   => '',
            };
        }

        $csrfToken = self::generate_nonce('contact_submissions');

        if ($action === 'view') {
            $id = (int) ($_GET['id'] ?? 0);
            $submission = CMS_Contact_Submissions::instance()->get_by_id($id);

            if (!$submission) {
                $error = 'Nachricht nicht gefunden.';
                self::render_submissions_list($csrfToken, $notice, $error);
                return;
            }

            // Als gelesen markieren
            if ($submission['status'] === 'unread') {
                CMS_Contact_Submissions::instance()->mark_read($id);
                $submission['status'] = 'read';
            }

            $meta = CMS_Contact_Submissions::instance()->get_meta($id);
            $activeSection = 'submissions';
            include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-submission-view.php';
        } else {
            self::render_submissions_list($csrfToken, $notice, $error);
        }
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function process_submissions_post(): array
    {
        $postAction = (string) ($_POST['sub_action'] ?? '');
        $handlers = [
            'update_status' => 'handle_update_submission_status_post',
            'delete' => 'handle_delete_submission_post',
            'bulk_action' => 'handle_bulk_submission_post',
        ];

        if (!isset($handlers[$postAction])) {
            return [];
        }

        $handler = $handlers[$postAction];
        return self::{$handler}();
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function handle_update_submission_status_post(): array
    {
        if (!self::verify_nonce('contact_submissions')) {
            return ['error' => 'Sicherheitscheck fehlgeschlagen.'];
        }

        $id     = (int) ($_POST['id'] ?? 0);
        $status = sanitize_text_field($_POST['status'] ?? '');
        CMS_Contact_Submissions::instance()->update_status($id, $status);

        return ['notice' => 'Status aktualisiert.'];
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function handle_delete_submission_post(): array
    {
        if (!self::verify_nonce('contact_submissions')) {
            return ['error' => 'Sicherheitscheck fehlgeschlagen.'];
        }

        $id = (int) ($_POST['id'] ?? 0);
        CMS_Contact_Submissions::instance()->delete($id);
        self::redirect_to_admin('submissions', ['notice' => 'deleted']);
    }

    /**
     * @return array{notice?: string, error?: string}
     */
    private static function handle_bulk_submission_post(): array
    {
        if (!self::verify_nonce('contact_submissions')) {
            return ['error' => 'Sicherheitscheck fehlgeschlagen.'];
        }

        $ids = is_array($_POST['submission_ids'] ?? null) ? $_POST['submission_ids'] : [];
        $bulkAction = sanitize_text_field($_POST['bulk'] ?? '');
        $count = 0;

        foreach ($ids as $id) {
            $submissionId = (int) $id;
            if ($submissionId <= 0) {
                continue;
            }

            if (self::apply_bulk_submission_action($submissionId, $bulkAction)) {
                $count++;
            }
        }

        return ['notice' => $count . ' Nachricht(en) verarbeitet.'];
    }

    private static function apply_bulk_submission_action(int $submissionId, string $bulkAction): bool
    {
        $submissions = CMS_Contact_Submissions::instance();

        return match ($bulkAction) {
            'mark_read' => (static function () use ($submissions, $submissionId): bool {
                $submissions->update_status($submissionId, 'read');
                return true;
            })(),
            'mark_spam' => (static function () use ($submissions, $submissionId): bool {
                $submissions->update_status($submissionId, 'spam');
                return true;
            })(),
            'delete' => (static function () use ($submissions, $submissionId): bool {
                $submissions->delete($submissionId);
                return true;
            })(),
            default => false,
        };
    }

    private static function render_submissions_list(string $csrfToken, string $notice, string $error): void
    {
        $page    = max(1, (int) ($_GET['paged'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;

        $filters = [
            'form_id' => (int) ($_GET['form_id'] ?? 0) ?: null,
            'status'  => sanitize_text_field($_GET['status'] ?? ''),
            'search'  => sanitize_text_field($_GET['search'] ?? ''),
            'is_spam' => isset($_GET['spam']) ? (int) $_GET['spam'] : null,
        ];

        // Null-Werte entfernen
        $filters = array_filter($filters, fn($v) => $v !== null && $v !== '' && $v !== 0);

        $submissionsSvc = CMS_Contact_Submissions::instance();
        $submissions    = $submissionsSvc->get_all($filters, $offset, $perPage);
        $total          = $submissionsSvc->count($filters);
        $pages          = (int) ceil($total / $perPage);

        $forms          = CMS_Contact_Forms::instance()->get_all();

        // View-kompatible Filter-Variablen
        $filterFormId = (int) ($_GET['form_id'] ?? 0);
        $filterStatus = sanitize_text_field($_GET['status'] ?? '');
        $filterSearch = sanitize_text_field($_GET['search'] ?? '');

        $activeSection = 'submissions';
        include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-submissions-list.php';
    }
}
