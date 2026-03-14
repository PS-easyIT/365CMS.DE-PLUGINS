<?php
/**
 * CMS Forum – Admin Permissions Trait
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Forum_Page_Permissions_Trait
{
    /**
     * Berechtigungen-Verwaltung rendern.
     */
    public static function render_permissions(): void
    {
        self::check_access();
        self::render_admin_page('Forum-Berechtigungen', static function (): void {
            $error   = null;
            $success = null;

            // POST-Handler
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forum_action'])) {
                if (!self::verify_nonce('forum_permissions')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    if ($_POST['forum_action'] === 'save_permissions') {
                        $perms = $_POST['permissions'] ?? [];
                        foreach ($perms as $forumId => $groups) {
                            foreach ($groups as $groupType => $flags) {
                                $permData = [
                                    'can_read'       => isset($flags['can_read']) ? 1 : 0,
                                    'can_post'       => isset($flags['can_post']) ? 1 : 0,
                                    'can_create'     => isset($flags['can_create']) ? 1 : 0,
                                    'can_edit_own'   => isset($flags['can_edit_own']) ? 1 : 0,
                                    'can_delete_own' => isset($flags['can_delete_own']) ? 1 : 0,
                                    'can_upload'     => isset($flags['can_upload']) ? 1 : 0,
                                    'can_vote'       => isset($flags['can_vote']) ? 1 : 0,
                                    'can_moderate'   => isset($flags['can_moderate']) ? 1 : 0,
                                ];
                                \CMS_Forum\Models\Permission::instance()->upsert(
                                    (int) $forumId,
                                    sanitize_text_field($groupType),
                                    $permData
                                );
                            }
                        }
                        $success = 'Berechtigungen gespeichert.';
                    }
                }
            }

            $forums     = \CMS_Forum\Models\Forum::instance()->findAll();
            $groupTypes = ['guest', 'member', 'moderator'];
            $permFlags  = ['can_read', 'can_post', 'can_create', 'can_edit_own', 'can_delete_own', 'can_upload', 'can_vote', 'can_moderate'];

            // Aktuelle Berechtigungen laden
            $currentPerms = [];
            foreach ($forums as $forum) {
                foreach ($groupTypes as $group) {
                    $perm = \CMS_Forum\Models\Permission::instance()->findByForumAndGroup((int) $forum->id, $group);
                    $currentPerms[(int) $forum->id][$group] = $perm;
                }
            }

            $csrfToken = self::generate_nonce('forum_permissions');

            include CMS_FORUM_DIR . 'admin/views/page-permissions.php';
        });
    }
}
