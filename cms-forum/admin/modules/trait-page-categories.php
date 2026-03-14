<?php
/**
 * CMS Forum – Admin Categories Trait
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Forum_Page_Categories_Trait
{
    /**
     * Kategorien-Verwaltung rendern.
     */
    public static function render_categories(): void
    {
        self::check_access();
        self::render_admin_page('Forum-Kategorien', static function (): void {
            $error   = null;
            $success = null;

            // POST-Handler
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forum_action'])) {
                if (!self::verify_nonce('forum_categories')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    switch ($_POST['forum_action']) {
                        case 'create_category':
                            $name = sanitize_text_field($_POST['name'] ?? '');
                            $slug = \CMS_Forum\Helpers\SlugHelper::unique($name, 'cmsforum_categories');
                            if (empty($name)) {
                                $error = 'Bitte gib einen Namen ein.';
                            } else {
                                \CMS_Forum\Models\Category::instance()->create([
                                    'name'       => $name,
                                    'slug'       => $slug,
                                    'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                                    'is_active'  => 1,
                                ]);
                                $success = 'Kategorie erstellt.';
                            }
                            break;

                        case 'update_category':
                            $id   = (int) ($_POST['category_id'] ?? 0);
                            $name = sanitize_text_field($_POST['name'] ?? '');
                            if ($id > 0 && !empty($name)) {
                                \CMS_Forum\Models\Category::instance()->update($id, [
                                    'name'       => $name,
                                    'slug'       => \CMS_Forum\Helpers\SlugHelper::unique($name, 'cmsforum_categories', 'slug', $id),
                                    'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                                    'is_active'  => isset($_POST['is_active']) ? 1 : 0,
                                ]);
                                $success = 'Kategorie aktualisiert.';
                            }
                            break;

                        case 'delete_category':
                            $id = (int) ($_POST['category_id'] ?? 0);
                            if ($id > 0) {
                                \CMS_Forum\Models\Category::instance()->delete($id);
                                $success = 'Kategorie gelöscht.';
                            }
                            break;
                    }
                }
            }

            $categories = \CMS_Forum\Models\Category::instance()->findAll();
            $csrfToken  = self::generate_nonce('forum_categories');

            include CMS_FORUM_DIR . 'admin/views/page-categories.php';
        });
    }
}
