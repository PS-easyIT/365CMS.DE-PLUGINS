<?php
/**
 * CMS Booking – Admin Leistungen / Services Trait
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_Booking_Page_Services_Trait
{
    public function render_services_page(): void
    {
        $servicesSvc = CMS_Booking_Services::instance();
        $error   = '';
        $success = '';

        // POST-Handler
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['service_action'])) {
            if (!class_exists('CMS\Security') || !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'booking_services')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = sanitize_text_field($_POST['service_action']);

                switch ($action) {
                    case 'create':
                        try {
                            $servicesSvc->create([
                                'provider_id'      => (int) ($_POST['provider_id'] ?? 0),
                                'title'            => sanitize_text_field($_POST['title'] ?? ''),
                                'description'      => strip_tags($_POST['description'] ?? '', '<p><a><strong><em><ul><ol><li><br>'),
                                'duration_min'     => (int) ($_POST['duration_min'] ?? 60),
                                'buffer_min'       => (int) ($_POST['buffer_min'] ?? 15),
                                'max_bookings'     => (int) ($_POST['max_bookings'] ?? 1),
                                'price_cents'      => (int) (((float) ($_POST['price'] ?? 0)) * 100),
                                'location_type'    => sanitize_text_field($_POST['location_type'] ?? 'online'),
                                'meeting_url'      => filter_var($_POST['meeting_url'] ?? '', FILTER_VALIDATE_URL) ?: '',
                                'booking_type'     => sanitize_text_field($_POST['booking_type'] ?? 'confirmation'),
                                'contact_template' => sanitize_text_field($_POST['contact_template'] ?? ''),
                            ]);
                            $success = 'Leistung erfolgreich erstellt.';
                        } catch (\Throwable $e) {
                            $error = 'Fehler: ' . htmlspecialchars($e->getMessage());
                        }
                        break;

                    case 'update':
                        $id = (int) ($_POST['service_id'] ?? 0);
                        $servicesSvc->update($id, [
                            'title'            => sanitize_text_field($_POST['title'] ?? ''),
                            'description'      => strip_tags($_POST['description'] ?? '', '<p><a><strong><em><ul><ol><li><br>'),
                            'duration_min'     => (int) ($_POST['duration_min'] ?? 60),
                            'buffer_min'       => (int) ($_POST['buffer_min'] ?? 15),
                            'max_bookings'     => (int) ($_POST['max_bookings'] ?? 1),
                            'price_cents'      => (int) (((float) ($_POST['price'] ?? 0)) * 100),
                            'location_type'    => sanitize_text_field($_POST['location_type'] ?? 'online'),
                            'meeting_url'      => filter_var($_POST['meeting_url'] ?? '', FILTER_VALIDATE_URL) ?: '',
                            'booking_type'     => sanitize_text_field($_POST['booking_type'] ?? 'confirmation'),
                            'contact_template' => sanitize_text_field($_POST['contact_template'] ?? ''),
                            'status'           => sanitize_text_field($_POST['status'] ?? 'active'),
                        ]);
                        $success = 'Leistung aktualisiert.';
                        break;

                    case 'delete':
                        $id = (int) ($_POST['service_id'] ?? 0);
                        $servicesSvc->delete($id);
                        $success = 'Leistung gelöscht.';
                        break;
                }
            }
        }

        $csrfToken = '';
        if (class_exists('CMS\Security')) {
            $csrfToken = \CMS\Security::instance()->generateToken('booking_services');
        }

        $page    = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset  = ($page - 1) * $perPage;
        $search  = sanitize_text_field($_GET['q'] ?? '');

        $items     = $servicesSvc->get_all($offset, $perPage, $search);
        $total     = $servicesSvc->count($search);
        $pages     = (int) ceil($total / $perPage);
        $providers = CMS_Booking_Providers::instance()->get_all(0, 200, 'active');

        // Einzelne Leistung zum Bearbeiten?
        $editService = null;
        if (isset($_GET['edit'])) {
            $editService = $servicesSvc->get((int) $_GET['edit']);
        }

        include CMS_BOOKING_PLUGIN_DIR . 'admin/views/services.php';
    }
}
