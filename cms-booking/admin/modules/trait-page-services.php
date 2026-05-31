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
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST' && isset($_POST['service_action'])) {
            if (!self::can_manage_admin_actions()) {
                $error = 'Keine Berechtigung für diese Aktion.';
            } elseif (!class_exists('CMS\Security') || !\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'booking_services')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $action = sanitize_text_field($_POST['service_action']);
                $allowedActions = ['create', 'update', 'delete'];

                if (!in_array($action, $allowedActions, true)) {
                    $error = 'Ungültige Aktion.';
                } else {
                    $serviceDailyLimit = max(0, (int) ($_POST['daily_booking_limit'] ?? 0));
                    $locationType = sanitize_text_field($_POST['location_type'] ?? 'online');
                    if (!in_array($locationType, ['online', 'onsite', 'hybrid'], true)) {
                        $locationType = 'online';
                    }

                    $bookingType = sanitize_text_field($_POST['booking_type'] ?? 'confirmation');
                    if (!in_array($bookingType, ['confirmation', 'instant', 'request'], true)) {
                        $bookingType = 'confirmation';
                    }

                    $buildSettingsJson = static function (?string $existingJson = null) use ($serviceDailyLimit): ?string {
                        $settings = [];
                        if (is_string($existingJson) && trim($existingJson) !== '') {
                            $decoded = json_decode($existingJson, true);
                            if (is_array($decoded)) {
                                $settings = $decoded;
                            }
                        }
                        $settings['daily_booking_limit'] = $serviceDailyLimit;

                        return (string) json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    };

                    try {
                        switch ($action) {
                            case 'create':
                                $providerId = (int) ($_POST['provider_id'] ?? 0);
                                if ($providerId <= 0) {
                                    $error = 'Bitte einen gueltigen Anbieter wählen.';
                                    break;
                                }
                                $servicesSvc->create([
                                    'provider_id'      => $providerId,
                                    'title'            => sanitize_text_field($_POST['title'] ?? ''),
                                    'description'      => strip_tags($_POST['description'] ?? '', '<p><a><strong><em><ul><ol><li><br>'),
                                    'duration_min'     => max(5, (int) ($_POST['duration_min'] ?? 60)),
                                    'buffer_min'       => max(0, (int) ($_POST['buffer_min'] ?? 15)),
                                    'max_bookings'     => max(1, (int) ($_POST['max_bookings'] ?? 1)),
                                    'price_cents'      => max(0, (int) round(((float) ($_POST['price'] ?? 0)) * 100)),
                                    'location_type'    => $locationType,
                                    'meeting_url'      => filter_var($_POST['meeting_url'] ?? '', FILTER_VALIDATE_URL) ?: '',
                                    'booking_type'     => $bookingType,
                                    'contact_template' => sanitize_text_field($_POST['contact_template'] ?? ''),
                                    'settings_json'    => $buildSettingsJson(),
                                ]);
                                $success = 'Leistung erfolgreich erstellt.';
                                break;

                            case 'update':
                                $id = (int) ($_POST['service_id'] ?? 0);
                                if ($id <= 0) {
                                    $error = 'Ungültige Leistung.';
                                    break;
                                }
                                $status = sanitize_text_field($_POST['status'] ?? 'active');
                                if (!in_array($status, ['active', 'inactive'], true)) {
                                    $status = 'active';
                                }
                                $existingService = $servicesSvc->get($id);
                                if (!$existingService) {
                                    $error = 'Ungültige Leistung.';
                                    break;
                                }
                                $servicesSvc->update($id, [
                                    'title'            => sanitize_text_field($_POST['title'] ?? ''),
                                    'description'      => strip_tags($_POST['description'] ?? '', '<p><a><strong><em><ul><ol><li><br>'),
                                    'duration_min'     => max(5, (int) ($_POST['duration_min'] ?? 60)),
                                    'buffer_min'       => max(0, (int) ($_POST['buffer_min'] ?? 15)),
                                    'max_bookings'     => max(1, (int) ($_POST['max_bookings'] ?? 1)),
                                    'price_cents'      => max(0, (int) round(((float) ($_POST['price'] ?? 0)) * 100)),
                                    'location_type'    => $locationType,
                                    'meeting_url'      => filter_var($_POST['meeting_url'] ?? '', FILTER_VALIDATE_URL) ?: '',
                                    'booking_type'     => $bookingType,
                                    'contact_template' => sanitize_text_field($_POST['contact_template'] ?? ''),
                                    'settings_json'    => $buildSettingsJson($existingService['settings_json'] ?? null),
                                    'status'           => $status,
                                ]);
                                $success = 'Leistung aktualisiert.';
                                break;

                            case 'delete':
                                $id = (int) ($_POST['service_id'] ?? 0);
                                if ($id <= 0) {
                                    $error = 'Ungültige Leistung.';
                                    break;
                                }
                                $servicesSvc->delete($id);
                                $success = 'Leistung gelöscht.';
                                break;
                        }
                    } catch (\Throwable $e) {
                        $error = 'Leistungsaktion konnte nicht ausgeführt werden.';
                    }
                }
            }
        }

        $csrfToken = '';
        if (class_exists('CMS\Security')) {
            $csrfToken = \CMS\Security::instance()->generateToken('booking_services');
        }

        $page    = max(1, (int) ($_GET['paged'] ?? 1));
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
