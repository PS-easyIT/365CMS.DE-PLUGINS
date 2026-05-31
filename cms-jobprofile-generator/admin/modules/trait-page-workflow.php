<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin-Seite: Workflow-Editor
 *
 * @since 1.0.0
 * @package CMS_JobProfileGenerator
 */
trait CMS_JPG_Page_Workflow_Trait
{
    // ── 6. WORKFLOW-EDITOR ────────────────────────────────────────────────────

    public static function render_workflow(): void
    {
        self::check_access();

        $notice = '';
        $error  = '';

        // POST-Handler
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('jpg_workflow_save')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                [$notice, $error] = self::handle_workflow_post();
            }
        }

        // Alle Workflow-Schritte laden
        $steps    = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::instance()->get_all_steps() : [];
        $allRoles = class_exists('CMS_JPG_Workflow') ? CMS_JPG_Workflow::get_all_cms_roles() : ['admin' => 'Admin'];

        self::render_admin_view(
            'Workflow-Editor',
            'jpg-workflow',
            JPG_DIR . 'admin/views/page-workflow-editor.php',
            compact('notice', 'error', 'steps', 'allRoles')
        );
    }

    /** @return array{string, string} [notice, error] */
    private static function handle_workflow_post(): array
    {
        $notice = '';
        $error  = '';
        $action = sanitize_key($_POST['_wf_action'] ?? '');

        switch ($action) {
            case 'save_step':
                $stepId = (int) ($_POST['step_id'] ?? 0);
                $data   = [
                    'step_name'          => sanitize_text_field($_POST['step_name'] ?? ''),
                    'approver_role'      => sanitize_key($_POST['approver_role'] ?? 'admin'),
                    'allow_self_approve' => isset($_POST['allow_self_approve']) ? 1 : 0,
                    'notification_email' => filter_var($_POST['notification_email'] ?? '', FILTER_VALIDATE_EMAIL) ?: '',
                    'sort_order'         => (int) ($_POST['sort_order'] ?? 10),
                    'active'             => isset($_POST['active']) ? 1 : 0,
                ];
                if (empty($data['step_name'])) {
                    $error = 'Stufenname ist erforderlich.';
                    break;
                }
                $id = CMS_JPG_Workflow::instance()->save_step($data, $stepId);
                $notice = $stepId > 0 ? 'Schritt aktualisiert.' : 'Neuer Schritt angelegt.';
                break;

            case 'delete_step':
                $stepId = (int) ($_POST['step_id'] ?? 0);
                if ($stepId > 0) {
                    CMS_JPG_Workflow::instance()->delete_step($stepId);
                    $notice = 'Schritt gelöscht.';
                }
                break;
        }

        return [$notice, $error];
    }
}
