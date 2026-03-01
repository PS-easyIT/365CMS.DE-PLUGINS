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

        $action = $_GET['action'] ?? 'list';
        $notice = '';
        $error  = '';

        // POST-Aktionen
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $postAction = $_POST['sub_action'] ?? '';

            switch ($postAction) {
                case 'update_status':
                    if (self::verify_nonce('contact_submissions')) {
                        $id     = (int) ($_POST['id'] ?? 0);
                        $status = sanitize_text_field($_POST['status'] ?? '');
                        CMS_Contact_Submissions::instance()->update_status($id, $status);
                        $notice = 'Status aktualisiert.';
                    } else {
                        $error = 'Sicherheitscheck fehlgeschlagen.';
                    }
                    break;

                case 'delete':
                    if (self::verify_nonce('contact_submissions')) {
                        $id = (int) ($_POST['id'] ?? 0);
                        CMS_Contact_Submissions::instance()->delete($id);
                        header('Location: ?page=contact-submissions&notice=deleted');
                        exit;
                    }
                    break;

                case 'bulk_action':
                    if (self::verify_nonce('contact_submissions')) {
                        $ids        = $_POST['submission_ids'] ?? [];
                        $bulkAction = sanitize_text_field($_POST['bulk'] ?? '');
                        $count      = 0;

                        foreach ($ids as $id) {
                            $id = (int) $id;
                            if ($id <= 0) continue;

                            switch ($bulkAction) {
                                case 'mark_read':
                                    CMS_Contact_Submissions::instance()->update_status($id, 'read');
                                    $count++;
                                    break;
                                case 'mark_spam':
                                    CMS_Contact_Submissions::instance()->update_status($id, 'spam');
                                    $count++;
                                    break;
                                case 'delete':
                                    CMS_Contact_Submissions::instance()->delete($id);
                                    $count++;
                                    break;
                            }
                        }
                        $notice = "{$count} Nachricht(en) verarbeitet.";
                    }
                    break;
            }
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
            include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-submission-view.php';
        } else {
            self::render_submissions_list($csrfToken, $notice, $error);
        }
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

        $submissions = CMS_Contact_Submissions::instance();
        $items       = $submissions->get_all($filters, $offset, $perPage);
        $total       = $submissions->count($filters);
        $pages       = (int) ceil($total / $perPage);

        $allForms = CMS_Contact_Forms::instance()->get_all();

        include CMS_CONTACT_PLUGIN_DIR . 'admin/views/page-submissions-list.php';
    }
}
