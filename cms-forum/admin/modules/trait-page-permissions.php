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
            $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if ($requestMethod === 'POST' && isset($_POST['forum_action'])) {
                $forumAction = sanitize_key((string) ($_POST['forum_action'] ?? ''));
                if (!self::verify_nonce('forum_permissions')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    if ($forumAction === 'save_permissions') {
                        $forums = \CMS_Forum\Models\Forum::instance()->findAll();
                        $validForumIds = array_map(static fn(object $forum): int => (int) $forum->id, $forums);
                        $groupTypes = ['guest', 'member', 'moderator'];

                        $perms = is_array($_POST['permissions'] ?? null) ? $_POST['permissions'] : [];
                        foreach ($perms as $forumId => $groups) {
                            $forumId = (int) $forumId;
                            if (!in_array($forumId, $validForumIds, true) || !is_array($groups)) {
                                continue;
                            }

                            foreach ($groups as $groupType => $flags) {
                                $groupType = sanitize_key((string) $groupType);
                                if (!in_array($groupType, $groupTypes, true) || !is_array($flags)) {
                                    continue;
                                }

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
                                    $forumId,
                                    $groupType,
                                    $permData
                                );
                            }
                        }
                        $success = 'Berechtigungen gespeichert.';
                    } else {
                        $error = 'Unbekannte Aktion.';
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
