<?php
/**
 * CMS Forum – Admin Forums Trait
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Forum_Page_Forums_Trait
{
    /**
     * Foren-Verwaltung rendern.
     */
    public static function render_forums(): void
    {
        self::check_access();
        self::render_admin_page('Forum-Struktur', static function (): void {
            $error   = null;
            $success = null;

            // POST-Handler
            $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if ($requestMethod === 'POST' && isset($_POST['forum_action'])) {
                $forumAction = sanitize_key((string) ($_POST['forum_action'] ?? ''));
                if (!self::verify_nonce('forum_forums')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    switch ($forumAction) {
                        case 'create_forum':
                            $name = sanitize_text_field($_POST['name'] ?? '');
                            $desc = sanitize_text_field($_POST['description'] ?? '');
                            if (empty($name)) {
                                $error = 'Bitte gib einen Namen ein.';
                            } else {
                                \CMS_Forum\Models\Forum::instance()->create([
                                    'category_id' => (int) ($_POST['category_id'] ?? 0),
                                    'parent_id'   => (int) ($_POST['parent_id'] ?? 0),
                                    'name'        => $name,
                                    'slug'        => \CMS_Forum\Helpers\SlugHelper::unique($name, 'cmsforum_forums'),
                                    'description' => $desc,
                                    'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                                    'is_active'   => 1,
                                ]);
                                $success = 'Forum erstellt.';
                            }
                            break;

                        case 'update_forum':
                            $id   = (int) ($_POST['forum_id'] ?? 0);
                            $name = sanitize_text_field($_POST['name'] ?? '');
                            if ($id > 0 && !empty($name)) {
                                \CMS_Forum\Models\Forum::instance()->update($id, [
                                    'category_id' => (int) ($_POST['category_id'] ?? 0),
                                    'parent_id'   => (int) ($_POST['parent_id'] ?? 0),
                                    'name'        => $name,
                                    'slug'        => \CMS_Forum\Helpers\SlugHelper::unique($name, 'cmsforum_forums', 'slug', $id),
                                    'description' => sanitize_text_field($_POST['description'] ?? ''),
                                    'sort_order'  => (int) ($_POST['sort_order'] ?? 0),
                                    'is_active'   => isset($_POST['is_active']) ? 1 : 0,
                                ]);
                                $success = 'Forum aktualisiert.';
                            }
                            break;

                        case 'delete_forum':
                            $id = (int) ($_POST['forum_id'] ?? 0);
                            if ($id > 0) {
                                \CMS_Forum\Models\Forum::instance()->delete($id);
                                $success = 'Forum gelöscht.';
                            }
                            break;

                        default:
                            $error = 'Unbekannte Aktion.';
                            break;
                    }
                }
            }

            $categories = \CMS_Forum\Models\Category::instance()->findAll();
            $forums     = \CMS_Forum\Models\Forum::instance()->findAll();
            $csrfToken  = self::generate_nonce('forum_forums');

            include CMS_FORUM_DIR . 'admin/views/page-forums.php';
        });
    }
}
