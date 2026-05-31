<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Genehmigungen + AJAX-Preview + JSON-Export
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Approvals_Trait
{
    // ── 7. GENEHMIGUNGEN ─────────────────────────────────────────────────────

    public static function render_approvals(): void
    {
        self::check_access();

        $notice = '';
        $error  = '';

        // POST-Handler (Approve / Reject / Reset)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_workflow_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_approvals_post();
            }
        }

        $pendingProfiles = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_all_pending() : [];
        $allRoles        = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::get_all_cms_roles() : ['admin' => 'Admin'];
        $wfSteps         = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_steps() : [];

        self::render_admin_view(
            'Genehmigungen',
            'jpg-approvals',
            JPG_DIR . 'admin/views/page-approvals.php',
            compact('notice', 'error', 'pendingProfiles', 'allRoles', 'wfSteps')
        );
    }

    /** @return array{string, string} [notice, error] */
    private static function handle_approvals_post(): array
    {
        $notice  = '';
        $error   = '';
        $action  = sanitize_key($_POST['_wf_action'] ?? '');
        $auth    = \CMS\Auth::instance();
        $actorId = method_exists($auth, 'getUserId') ? (int) $auth->getUserId() : 0;

        switch ($action) {
            case 'approve_profile':
                $profileId = (int) ($_POST['profile_id'] ?? 0);
                $note      = sanitize_text_field($_POST['note'] ?? '');
                if ($profileId > 0) {
                    $ok = CMS_JPG_Workflow::instance()->approve_step($profileId, $actorId, $note);
                    $notice = $ok ? 'Profil genehmigt / weitergeführt.' : 'Fehler beim Genehmigen.';
                }
                break;

            case 'reject_profile':
                $profileId = (int) ($_POST['profile_id'] ?? 0);
                $note      = sanitize_text_field($_POST['note'] ?? '');
                if ($profileId > 0) {
                    $ok = CMS_JPG_Workflow::instance()->reject_step($profileId, $actorId, $note);
                    $notice = $ok ? 'Profil abgelehnt.' : 'Fehler beim Ablehnen.';
                }
                break;

            case 'reset_profile':
                $profileId = (int) ($_POST['profile_id'] ?? 0);
                if ($profileId > 0) {
                    CMS_JPG_Workflow::instance()->reset_workflow($profileId, $actorId);
                    $notice = 'Profil zurückgesetzt auf Entwurf.';
                }
                break;
        }

        return [$notice, $error];
    }

    // ── AJAX: HTML-Preview ────────────────────────────────────────────────────

    public static function ajax_preview(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        self::check_access();
        if (!self::verify_nonce('jpg_generator_save')) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Sicherheitscheck fehlgeschlagen.']);
            exit;
        }

        $id = (int) ($_POST['profile_id'] ?? 0);
        if ($id <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Ungültige ID.']);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'html' => CMS_JPG_Export::instance()->render_html($id),
        ]);
        exit;
    }

    // ── AJAX: JSON-Export ─────────────────────────────────────────────────────

    public static function ajax_export_json(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        self::check_access();

        $nonceValue = $_GET[self::NONCE_FIELD] ?? $_POST[self::NONCE_FIELD] ?? '';
        $nonceOk    = false;
        if (!empty($nonceValue) && class_exists('CMS\Security')) {
            $nonceOk = \CMS\Security::instance()->verifyNonce($nonceValue, 'jpg_export')
                    || \CMS\Security::instance()->verifyNonce($nonceValue, 'jpg_libraries_save')
                    || \CMS\Security::instance()->verifyNonce($nonceValue, 'jpg_generator_save');
        } elseif (empty($nonceValue) && !class_exists('CMS\Security')) {
            $nonceOk = true;
        }
        if (!$nonceOk) {
            http_response_code(403);
            exit;
        }

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            wp_die('Ungültige ID.', 400);
        }

        $json    = CMS_JPG_Export::instance()->export_json($id);
        $profile = CMS_JPG_Profiles::instance()->get($id);
        $slug    = $profile ? $profile->slug : 'profil-' . $id;

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $slug . '.json"');
        echo $json;
        exit;
    }
}
