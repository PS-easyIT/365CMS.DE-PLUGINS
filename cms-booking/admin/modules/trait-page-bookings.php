<?php
/**
 * CMS Booking – Admin Buchungen Trait
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Booking_Page_Bookings_Trait
{
    public function render_bookings_page(): void
    {
        $bookings = CMS_Booking_Bookings::instance();
        $error    = '';
        $success  = '';

        // POST-Handler
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST' && isset($_POST['booking_action'])) {
            if (!self::can_manage_admin_actions()) {
                $error = 'Keine Berechtigung für diese Aktion.';
            } elseif (!class_exists('CMS\Security') || !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'booking_admin')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $id     = (int) ($_POST['booking_id'] ?? 0);
                $action = sanitize_text_field($_POST['booking_action']);
                $allowedActions = ['confirm', 'cancel', 'complete', 'no_show', 'delete'];

                if ($id <= 0 || !in_array($action, $allowedActions, true)) {
                    $error = 'Ungültige Aktion.';
                } else {
                    try {
                        switch ($action) {
                            case 'confirm':
                                $bookings->confirm($id);
                                $success = 'Buchung #' . $id . ' wurde bestätigt.';
                                break;
                            case 'cancel':
                                $bookings->cancel($id);
                                $success = 'Buchung #' . $id . ' wurde storniert.';
                                break;
                            case 'complete':
                                $bookings->complete($id);
                                $success = 'Buchung #' . $id . ' wurde abgeschlossen.';
                                break;
                            case 'no_show':
                                $bookings->no_show($id);
                                $success = 'Buchung #' . $id . ' als „Nicht erschienen" markiert.';
                                break;
                            case 'delete':
                                $bookings->delete($id);
                                $success = 'Buchung #' . $id . ' wurde gelöscht.';
                                break;
                        }
                    } catch (\Throwable $e) {
                        $error = 'Buchungsaktion konnte nicht ausgeführt werden.';
                    }
                }
            }
        }

        $csrfToken = '';
        if (class_exists('CMS\Security')) {
            $csrfToken = \CMS\Security::instance()->generateToken('booking_admin');
        }

        // Filter
        $page       = max(1, (int) ($_GET['paged'] ?? 1));
        $perPage    = 20;
        $offset     = ($page - 1) * $perPage;
        $status     = sanitize_text_field($_GET['status'] ?? '');
        $search     = sanitize_text_field($_GET['q'] ?? '');
        $providerId = (int) ($_GET['provider_id'] ?? 0);

        $items = $bookings->get_all($offset, $perPage, $status, $search, $providerId);
        $total = $bookings->count($status, $search, $providerId);
        $pages = (int) ceil($total / $perPage);

        $statusLabels = CMS_Booking_Bookings::status_labels();
        $providers    = CMS_Booking_Providers::instance()->get_all(0, 200);

        include CMS_BOOKING_PLUGIN_DIR . 'admin/views/bookings.php';
    }
}
