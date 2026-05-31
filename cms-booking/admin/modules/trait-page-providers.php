<?php
/**
 * CMS Booking – Admin Anbieter Trait
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Booking_Page_Providers_Trait
{
    public function render_providers_page(): void
    {
        $providersSvc = CMS_Booking_Providers::instance();
        $error   = '';
        $success = '';

        // POST-Handler
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST' && isset($_POST['provider_action'])) {
            if (!self::can_manage_admin_actions()) {
                $error = 'Keine Berechtigung für diese Aktion.';
            } elseif (!class_exists('CMS\Security') || !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'booking_providers')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = sanitize_text_field($_POST['provider_action']);
                $id     = (int) ($_POST['provider_id'] ?? 0);
                $allowedActions = ['activate', 'deactivate', 'delete', 'sync'];

                if ($id <= 0 || !in_array($action, $allowedActions, true)) {
                    $error = 'Ungültige Aktion.';
                } else {
                    switch ($action) {
                        case 'activate':
                            $providersSvc->set_status($id, 'active');
                            $success = 'Anbieter aktiviert.';
                            break;
                        case 'deactivate':
                            $providersSvc->set_status($id, 'inactive');
                            $success = 'Anbieter deaktiviert.';
                            break;
                        case 'delete':
                            $providersSvc->delete($id);
                            $success = 'Anbieter gelöscht.';
                            break;
                        case 'sync':
                            try {
                                $provider = $providersSvc->get($id);
                                if ($provider) {
                                    CMS_Booking_Integration::sync_provider(
                                        $provider['source_plugin'],
                                        (int) $provider['source_id']
                                    );
                                    $success = 'Anbieter synchronisiert.';
                                } else {
                                    $error = 'Anbieter nicht gefunden.';
                                }
                            } catch (\Throwable $e) {
                                $error = 'Synchronisation fehlgeschlagen.';
                            }
                            break;
                    }
                }
            }
        }

        $csrfToken = '';
        if (class_exists('CMS\Security')) {
            $csrfToken = \CMS\Security::instance()->generateToken('booking_providers');
        }

        $page    = max(1, (int) ($_GET['paged'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;
        $status  = sanitize_text_field($_GET['status'] ?? '');
        $search  = sanitize_text_field($_GET['q'] ?? '');

        $items = $providersSvc->get_all($offset, $perPage, $status, $search);
        $total = $providersSvc->count($status, $search);
        $pages = (int) ceil($total / $perPage);

        $types = CMS_Booking_Integration::get_provider_types();

        include CMS_BOOKING_PLUGIN_DIR . 'admin/views/providers.php';
    }
}
