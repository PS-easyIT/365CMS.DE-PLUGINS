<?php
/**
 * CMS Booking – Frontend Controller
 *
 * Routen:
 *   GET  /booking/{provider-slug}                  → Provider-Übersicht
 *   GET  /booking/{provider-slug}/{service-slug}    → Buchungsformular
 *   POST /booking/{provider-slug}/{service-slug}    → Buchung absenden
 *   GET  /booking/confirm/{booking-id}              → Bestätigungsseite
 *   GET  /booking/ical/{booking-id}                 → ICS-Download
 *   GET  /api/booking/slots/{provider-id}/{date}    → Verfügbare Slots (JSON)
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Booking_Frontend
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::$instance->register_routes();
        }
        return self::$instance;
    }

    private function __construct() {}

    /* ================================================================== */
    /*  Routen                                                             */
    /* ================================================================== */

    private function register_routes(): void
    {
        if (!class_exists('CMS\Router')) {
            return;
        }

        $router = \CMS\Router::instance();

        // Slots-API (AJAX)
        $router->get('/api/booking/slots/{providerId}/{date}', function ($providerId, $date) {
            $this->api_get_slots((int) $providerId, $date);
        });

        // ICS-Download
        $router->get('/booking/ical/{bookingId}', function ($bookingId) {
            $this->serve_ical((int) $bookingId);
        });

        // Bestätigungsseite
        $router->get('/booking/confirm/{bookingId}', function ($bookingId) {
            $this->render_confirmation((int) $bookingId);
        });

        // Service-Buchungsformular
        $router->get('/booking/{providerSlug}/{serviceSlug}', function ($providerSlug, $serviceSlug) {
            $this->render_service_booking($providerSlug, $serviceSlug);
        });
        $router->post('/booking/{providerSlug}/{serviceSlug}', function ($providerSlug, $serviceSlug) {
            $this->process_booking($providerSlug, $serviceSlug);
        });

        // Provider-Übersicht (Services-Liste)
        $router->get('/booking/{providerSlug}', function ($providerSlug) {
            $this->render_provider_page($providerSlug);
        });
    }

    /* ================================================================== */
    /*  Provider-Übersicht                                                 */
    /* ================================================================== */

    private function render_provider_page(string $providerSlug): void
    {
        $provider = CMS_Booking_Providers::instance()->get_by_slug($providerSlug);
        if (!$provider || $provider['status'] !== 'active') {
            $this->render_404();
            return;
        }

        $services = CMS_Booking_Services::instance()->get_by_provider((int) $provider['id'], 'active');

        // SEO
        $this->set_seo(
            'Termin buchen – ' . $provider['display_name'],
            'Buchen Sie einen Termin bei ' . $provider['display_name']
        );

        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        include CMS_BOOKING_PLUGIN_DIR . 'templates/page-provider.php';
        exit;
    }

    /* ================================================================== */
    /*  Service-Buchung                                                    */
    /* ================================================================== */

    private function render_service_booking(string $providerSlug, string $serviceSlug): void
    {
        $provider = CMS_Booking_Providers::instance()->get_by_slug($providerSlug);
        if (!$provider || $provider['status'] !== 'active') {
            $this->render_404();
            return;
        }

        $service = CMS_Booking_Services::instance()->get_by_slug((int) $provider['id'], $serviceSlug);
        if (!$service || $service['status'] !== 'active') {
            $this->render_404();
            return;
        }

        // Kontakt-Template aus cms-contact laden?
        $useContactForm = $this->should_use_contact_form($service, $provider);

        $csrfToken = '';
        if (class_exists('CMS\Security')) {
            $csrfToken = \CMS\Security::instance()->generateToken('booking_submit');
        }

        // Verfügbare Tage
        $availability = CMS_Booking_Availability::instance();
        $availDates   = $availability->get_available_dates(
            (int) $provider['id'],
            (int) $service['duration_min'],
            90
        );

        $error   = '';
        $success = '';
        $old     = [];

        $this->set_seo(
            $service['title'] . ' – ' . $provider['display_name'],
            $service['description'] ?? ''
        );

        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        include CMS_BOOKING_PLUGIN_DIR . 'templates/page-booking.php';
        exit;
    }

    private function process_booking(string $providerSlug, string $serviceSlug): void
    {
        $provider = CMS_Booking_Providers::instance()->get_by_slug($providerSlug);
        $service  = $provider ? CMS_Booking_Services::instance()->get_by_slug((int) $provider['id'], $serviceSlug) : null;

        if (!$provider || !$service) {
            $this->render_404();
            return;
        }

        $error   = '';
        $success = '';
        $old     = $_POST;

        // CSRF
        if (class_exists('CMS\Security')) {
            if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'booking_submit')) {
                $error = 'Sicherheitscheck fehlgeschlagen. Bitte versuchen Sie es erneut.';
            }
        }

        // Honeypot
        if ($error === '' && !empty($_POST['website_url'])) {
            $error = 'Spam erkannt.';
        }

        // Validierung
        if ($error === '') {
            $customerName  = sanitize_text_field($_POST['customer_name'] ?? '');
            $customerEmail = filter_var($_POST['customer_email'] ?? '', FILTER_VALIDATE_EMAIL);
            $customerPhone = sanitize_text_field($_POST['customer_phone'] ?? '');
            $bookingDate   = sanitize_text_field($_POST['booking_date'] ?? '');
            $startTime     = sanitize_text_field($_POST['start_time'] ?? '');
            $notes         = strip_tags($_POST['notes'] ?? '');

            if ($customerName === '') {
                $error = 'Bitte geben Sie Ihren Namen an.';
            } elseif (!$customerEmail) {
                $error = 'Bitte geben Sie eine gültige E-Mail-Adresse an.';
            } elseif ($bookingDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $bookingDate)) {
                $error = 'Bitte wählen Sie ein gültiges Datum.';
            } elseif ($startTime === '' || !preg_match('/^\d{2}:\d{2}$/', $startTime)) {
                $error = 'Bitte wählen Sie eine Uhrzeit.';
            }
        }

        // Verfügbarkeit prüfen
        if ($error === '') {
            $durationMin = (int) $service['duration_min'];
            $bufferMin   = (int) $service['buffer_min'];

            $isAvailable = CMS_Booking_Availability::instance()->is_slot_available(
                (int) $provider['id'],
                $bookingDate,
                $startTime,
                $durationMin,
                $bufferMin
            );

            if (!$isAvailable) {
                $error = 'Der gewählte Zeitpunkt ist leider nicht mehr verfügbar.';
            }
        }

        // Buchung anlegen
        if ($error === '') {
            $endTs  = strtotime("{$bookingDate} {$startTime}") + ($durationMin * 60);
            $endTime = date('H:i', $endTs);

            try {
                $bookingId = CMS_Booking_Bookings::instance()->create([
                    'provider_id'   => (int) $provider['id'],
                    'service_id'    => (int) $service['id'],
                    'user_id'       => $this->get_current_user_id(),
                    'customer_name'  => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'booking_date'   => $bookingDate,
                    'start_time'     => $startTime,
                    'end_time'       => $endTime,
                    'duration_min'   => $durationMin,
                    'location_type'  => $service['location_type'] ?? 'online',
                    'meeting_url'    => $service['meeting_url'] ?? null,
                    'price_cents'    => (int) $service['price_cents'],
                    'currency'       => $provider['currency'] ?? 'EUR',
                    'notes'          => $notes,
                ]);

                // Weiterleitung zur Bestätigungsseite
                $confirmUrl = (defined('SITE_URL') ? SITE_URL : '') . "/booking/confirm/{$bookingId}";
                header("Location: {$confirmUrl}");
                exit;
            } catch (\Throwable $e) {
                $error = 'Beim Speichern der Buchung ist ein Fehler aufgetreten.';
            }
        }

        // Bei Fehler: Formular erneut rendern
        $csrfToken = '';
        if (class_exists('CMS\Security')) {
            $csrfToken = \CMS\Security::instance()->generateToken('booking_submit');
        }
        $availability  = CMS_Booking_Availability::instance();
        $availDates    = $availability->get_available_dates((int) $provider['id'], (int) $service['duration_min'], 90);
        $useContactForm = $this->should_use_contact_form($service, $provider);

        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        include CMS_BOOKING_PLUGIN_DIR . 'templates/page-booking.php';
        exit;
    }

    /* ================================================================== */
    /*  Bestätigungsseite                                                  */
    /* ================================================================== */

    private function render_confirmation(int $bookingId): void
    {
        $booking = CMS_Booking_Bookings::instance()->get($bookingId);
        if (!$booking) {
            $this->render_404();
            return;
        }

        $calExport = CMS_Booking_Calendar_Export::instance();
        $googleUrl = $calExport->google_calendar_url($booking);

        $this->set_seo('Buchung bestätigt', 'Ihre Buchung wurde erfolgreich registriert.');

        $siteUrl = defined('SITE_URL') ? SITE_URL : '';
        include CMS_BOOKING_PLUGIN_DIR . 'templates/page-confirmation.php';
        exit;
    }

    /* ================================================================== */
    /*  ICS-Download                                                       */
    /* ================================================================== */

    private function serve_ical(int $bookingId): void
    {
        $booking = CMS_Booking_Bookings::instance()->get($bookingId);
        if (!$booking) {
            http_response_code(404);
            echo 'Buchung nicht gefunden.';
            exit;
        }
        CMS_Booking_Calendar_Export::instance()->serve_ics($booking);
    }

    /* ================================================================== */
    /*  Slots-API                                                          */
    /* ================================================================== */

    private function api_get_slots(int $providerId, string $date): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            http_response_code(400);
            echo json_encode(['error' => 'Ungültiges Datum']);
            exit;
        }

        // Service-ID optional per GET
        $serviceId   = (int) ($_GET['service_id'] ?? 0);
        $durationMin = 60;
        $bufferMin   = 15;

        if ($serviceId > 0) {
            $service = CMS_Booking_Services::instance()->get($serviceId);
            if ($service) {
                $durationMin = (int) $service['duration_min'];
                $bufferMin   = (int) $service['buffer_min'];
            }
        }

        $slots = CMS_Booking_Availability::instance()->get_available_slots(
            $providerId,
            $date,
            $durationMin,
            $bufferMin
        );

        echo json_encode(['success' => true, 'slots' => $slots]);
        exit;
    }

    /* ================================================================== */
    /*  Helfer                                                             */
    /* ================================================================== */

    private function should_use_contact_form(array $service, array $provider): bool
    {
        if (!class_exists('CMS\PluginManager')) {
            return false;
        }
        if (!\CMS\PluginManager::instance()->isPluginActive('cms-contact')) {
            return false;
        }
        // Wenn der Service ein contact_template hat → ja
        return !empty($service['contact_template']) || !empty($provider['contact_form_id']);
    }

    private function set_seo(string $title, string $description): void
    {
        if (class_exists('CMS\Services\SEOService')) {
            $seo = \CMS\Services\SEOService::instance();
            $seo->setTitle($title);
            $seo->setDescription($description);
        }
    }

    private function render_404(): void
    {
        http_response_code(404);
        if (class_exists('CMS\ThemeManager')) {
            \CMS\ThemeManager::instance()->render('404');
        } else {
            echo '<h1>404 – Nicht gefunden</h1>';
        }
        exit;
    }

    private function get_current_user_id(): ?int
    {
        if (class_exists('CMS\Auth')) {
            $auth = \CMS\Auth::instance();
            if ($auth->isLoggedIn()) {
                $user = $auth->getUser();
                return isset($user['id']) ? (int) $user['id'] : null;
            }
        }
        return null;
    }
}
