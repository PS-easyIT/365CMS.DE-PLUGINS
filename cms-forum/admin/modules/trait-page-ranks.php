<?php
/**
 * CMS Forum – Admin Ranks Trait
 *
 * @package CMS_Forum
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Forum_Page_Ranks_Trait
{
    /**
     * Rang-Verwaltung rendern.
     */
    public static function render_ranks(): void
    {
        self::check_access();
        self::render_admin_page('Forum-Ränge', static function (): void {
            $error   = null;
            $success = null;

            // POST-Handler
            $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
            if ($requestMethod === 'POST' && isset($_POST['forum_action'])) {
                $forumAction = sanitize_key((string) ($_POST['forum_action'] ?? ''));
                if (!self::verify_nonce('forum_ranks')) {
                    $error = 'Sicherheitscheck fehlgeschlagen.';
                } else {
                    switch ($forumAction) {
                        case 'create_rank':
                            $title = sanitize_text_field($_POST['title'] ?? '');
                            if (empty($title)) {
                                $error = 'Bitte gib einen Titel ein.';
                            } else {
                                \CMS_Forum\Models\Rank::instance()->create([
                                    'name'           => $title,
                                    'min_posts'      => (int) ($_POST['min_posts'] ?? 0),
                                    'color'          => sanitize_text_field($_POST['css_class'] ?? ''),
                                    'icon'           => sanitize_text_field($_POST['icon'] ?? ''),
                                    'is_special'     => isset($_POST['is_special']) ? 1 : 0,
                                ]);
                                $success = 'Rang erstellt.';
                            }
                            break;

                        case 'update_rank':
                            $id    = (int) ($_POST['rank_id'] ?? 0);
                            $title = sanitize_text_field($_POST['title'] ?? '');
                            if ($id > 0 && !empty($title)) {
                                \CMS_Forum\Models\Rank::instance()->update($id, [
                                    'name'       => $title,
                                    'min_posts'  => (int) ($_POST['min_posts'] ?? 0),
                                    'color'      => sanitize_text_field($_POST['css_class'] ?? ''),
                                    'icon'       => sanitize_text_field($_POST['icon'] ?? ''),
                                    'is_special' => isset($_POST['is_special']) ? 1 : 0,
                                ]);
                                $success = 'Rang aktualisiert.';
                            }
                            break;

                        case 'delete_rank':
                            $id = (int) ($_POST['rank_id'] ?? 0);
                            if ($id > 0) {
                                \CMS_Forum\Models\Rank::instance()->delete($id);
                                $success = 'Rang gelöscht.';
                            }
                            break;

                        case 'recalculate_ranks':
                            $result = \CMS_Forum\Controllers\AdminController::instance()->recalculateRanks();
                            $success = $result['message'] ?? 'Ränge aktualisiert.';
                            break;

                        default:
                            $error = 'Unbekannte Aktion.';
                            break;
                    }
                }
            }

            $ranks     = \CMS_Forum\Models\Rank::instance()->findAll();
            $csrfToken = self::generate_nonce('forum_ranks');

            include CMS_FORUM_DIR . 'admin/views/page-ranks.php';
        });
    }
}
