<?php
/**
 * CMS Booking – E-Mail-Benachrichtigungen
 *
 * @package CMS_Booking
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Booking_Notifications
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        if (class_exists('CMS\Hooks')) {
            \CMS\Hooks::addAction('booking_created',          [$this, 'on_booking_created'],    10);
            \CMS\Hooks::addAction('booking_status_confirmed', [$this, 'on_booking_confirmed'],  10);
            \CMS\Hooks::addAction('booking_status_cancelled', [$this, 'on_booking_cancelled'],  10);
        }
    }

    /* ================================================================== */
    /*  Hook-Callbacks                                                     */
    /* ================================================================== */

    public function on_booking_created(int $bookingId): void
    {
        $booking = CMS_Booking_Bookings::instance()->get($bookingId);
        if (!$booking) {
            return;
        }

        // Kundenbestätigung
        $this->send_customer_confirmation($booking);

        // Provider-Benachrichtigung
        $this->send_provider_notification($booking);

        // Admin-Info
        $this->send_admin_notification($booking);
    }

    public function on_booking_confirmed(int $bookingId): void
    {
        $booking = CMS_Booking_Bookings::instance()->get($bookingId);
        if (!$booking) {
            return;
        }
        $this->send_customer_status_update($booking, 'bestätigt');
    }

    public function on_booking_cancelled(int $bookingId): void
    {
        $booking = CMS_Booking_Bookings::instance()->get($bookingId);
        if (!$booking) {
            return;
        }
        $this->send_customer_status_update($booking, 'storniert');
        $this->send_provider_cancellation($booking);
    }

    /* ================================================================== */
    /*  Kunden-Mails                                                       */
    /* ================================================================== */

    private function send_customer_confirmation(array $booking): void
    {
        $subject = 'Buchungsbestätigung – ' . ($booking['service_title'] ?? 'Termin');
        $body    = $this->render_template('customer-confirmation', $booking);
        $this->send($booking['customer_email'], $subject, $body);
    }

    private function send_customer_status_update(array $booking, string $statusText): void
    {
        $subject = "Buchung {$statusText} – " . ($booking['service_title'] ?? 'Termin');
        $body    = $this->render_template('customer-status-update', [
            ...$booking,
            'status_text' => $statusText,
        ]);
        $this->send($booking['customer_email'], $subject, $body);
    }

    /* ================================================================== */
    /*  Provider-Mails                                                     */
    /* ================================================================== */

    private function send_provider_notification(array $booking): void
    {
        $email = $booking['provider_email'] ?? '';
        if ($email === '') {
            return;
        }

        $subject = 'Neue Buchung von ' . $booking['customer_name'];
        $body    = $this->render_template('provider-new-booking', $booking);
        $this->send($email, $subject, $body);
    }

    private function send_provider_cancellation(array $booking): void
    {
        $email = $booking['provider_email'] ?? '';
        if ($email === '') {
            return;
        }

        $subject = 'Stornierung – ' . $booking['customer_name'];
        $body    = $this->render_template('provider-cancellation', $booking);
        $this->send($email, $subject, $body);
    }

    /* ================================================================== */
    /*  Admin-Mail                                                         */
    /* ================================================================== */

    private function send_admin_notification(array $booking): void
    {
        $adminEmail = $this->get_setting('admin_email');
        if ($adminEmail === '') {
            return;
        }

        $subject = '[CMS Booking] Neue Buchung #' . $booking['id'];
        $body    = $this->render_template('admin-new-booking', $booking);
        $this->send($adminEmail, $subject, $body);
    }

    /* ================================================================== */
    /*  Template-Engine                                                    */
    /* ================================================================== */

    private function render_template(string $templateName, array $data): string
    {
        $siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';
        $siteUrl  = defined('SITE_URL')  ? SITE_URL  : '';
        $date     = isset($data['booking_date'])
            ? date('d.m.Y', strtotime($data['booking_date']))
            : '';
        $time = ($data['start_time'] ?? '') . ' – ' . ($data['end_time'] ?? '');

        $vars = [
            '{{site_name}}'      => htmlspecialchars($siteName),
            '{{site_url}}'       => htmlspecialchars($siteUrl),
            '{{booking_id}}'     => (string) ($data['id'] ?? ''),
            '{{customer_name}}'  => htmlspecialchars($data['customer_name'] ?? ''),
            '{{customer_email}}' => htmlspecialchars($data['customer_email'] ?? ''),
            '{{customer_phone}}' => htmlspecialchars($data['customer_phone'] ?? ''),
            '{{provider_name}}'  => htmlspecialchars($data['provider_name'] ?? ''),
            '{{service_title}}'  => htmlspecialchars($data['service_title'] ?? 'Termin'),
            '{{date}}'           => $date,
            '{{time}}'           => $time,
            '{{location_type}}'  => htmlspecialchars($data['location_type'] ?? ''),
            '{{meeting_url}}'    => htmlspecialchars($data['meeting_url'] ?? ''),
            '{{status}}'         => htmlspecialchars($data['status'] ?? ''),
            '{{status_text}}'    => htmlspecialchars($data['status_text'] ?? ''),
            '{{notes}}'          => nl2br(htmlspecialchars($data['notes'] ?? '')),
            '{{price}}'          => CMS_Booking_Services::format_price(
                (int) ($data['price_cents'] ?? 0),
                (string) ($data['currency'] ?? 'EUR')
            ),
        ];

        $html = $this->get_template_html($templateName);
        return str_replace(array_keys($vars), array_values($vars), $html);
    }

    private function get_template_html(string $name): string
    {
        $templates = [
            'customer-confirmation' => $this->tpl_customer_confirmation(),
            'customer-status-update' => $this->tpl_customer_status_update(),
            'provider-new-booking'  => $this->tpl_provider_new_booking(),
            'provider-cancellation' => $this->tpl_provider_cancellation(),
            'admin-new-booking'     => $this->tpl_admin_new_booking(),
        ];

        return $templates[$name] ?? '<p>{{customer_name}}, Ihre Buchung #{{booking_id}} wurde registriert.</p>';
    }

    /* ================================================================== */
    /*  HTML-Templates                                                     */
    /* ================================================================== */

    private function tpl_customer_confirmation(): string
    {
        return <<<'HTML'
<div style="max-width:600px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1e293b;">
    <div style="background:#3b82f6;color:#fff;padding:1.5rem;text-align:center;border-radius:8px 8px 0 0;">
        <h2 style="margin:0;font-size:1.25rem;">✅ Buchungsbestätigung</h2>
    </div>
    <div style="padding:1.5rem;background:#fff;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;">
        <p>Hallo {{customer_name}},</p>
        <p>Ihre Buchung wurde erfolgreich registriert.</p>
        <table style="width:100%;border-collapse:collapse;margin:1rem 0;">
            <tr><td style="padding:.5rem;color:#64748b;">Buchung:</td><td style="padding:.5rem;">#{{booking_id}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Leistung:</td><td style="padding:.5rem;">{{service_title}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Anbieter:</td><td style="padding:.5rem;">{{provider_name}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Datum:</td><td style="padding:.5rem;">{{date}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Uhrzeit:</td><td style="padding:.5rem;">{{time}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Preis:</td><td style="padding:.5rem;">{{price}}</td></tr>
        </table>
        <p style="color:#64748b;font-size:.875rem;">Sie erhalten eine weitere E-Mail, sobald Ihre Buchung bestätigt wird.</p>
        <p style="margin-top:1.5rem;color:#94a3b8;font-size:.8rem;">– {{site_name}}</p>
    </div>
</div>
HTML;
    }

    private function tpl_customer_status_update(): string
    {
        return <<<'HTML'
<div style="max-width:600px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1e293b;">
    <div style="background:#3b82f6;color:#fff;padding:1.5rem;text-align:center;border-radius:8px 8px 0 0;">
        <h2 style="margin:0;font-size:1.25rem;">📋 Buchungs-Update</h2>
    </div>
    <div style="padding:1.5rem;background:#fff;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;">
        <p>Hallo {{customer_name}},</p>
        <p>Der Status Ihrer Buchung <strong>#{{booking_id}}</strong> wurde auf <strong>{{status_text}}</strong> geändert.</p>
        <table style="width:100%;border-collapse:collapse;margin:1rem 0;">
            <tr><td style="padding:.5rem;color:#64748b;">Leistung:</td><td style="padding:.5rem;">{{service_title}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Datum:</td><td style="padding:.5rem;">{{date}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Uhrzeit:</td><td style="padding:.5rem;">{{time}}</td></tr>
        </table>
        <p style="margin-top:1.5rem;color:#94a3b8;font-size:.8rem;">– {{site_name}}</p>
    </div>
</div>
HTML;
    }

    private function tpl_provider_new_booking(): string
    {
        return <<<'HTML'
<div style="max-width:600px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1e293b;">
    <div style="background:#059669;color:#fff;padding:1.5rem;text-align:center;border-radius:8px 8px 0 0;">
        <h2 style="margin:0;font-size:1.25rem;">📥 Neue Buchungsanfrage</h2>
    </div>
    <div style="padding:1.5rem;background:#fff;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;">
        <p>Hallo {{provider_name}},</p>
        <p>Sie haben eine neue Buchungsanfrage erhalten:</p>
        <table style="width:100%;border-collapse:collapse;margin:1rem 0;">
            <tr><td style="padding:.5rem;color:#64748b;">Kunde:</td><td style="padding:.5rem;">{{customer_name}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">E-Mail:</td><td style="padding:.5rem;">{{customer_email}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Telefon:</td><td style="padding:.5rem;">{{customer_phone}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Leistung:</td><td style="padding:.5rem;">{{service_title}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Datum:</td><td style="padding:.5rem;">{{date}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Uhrzeit:</td><td style="padding:.5rem;">{{time}}</td></tr>
        </table>
        <p>Bitte bestätigen oder bearbeiten Sie die Buchung im Admin-Bereich.</p>
    </div>
</div>
HTML;
    }

    private function tpl_provider_cancellation(): string
    {
        return <<<'HTML'
<div style="max-width:600px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1e293b;">
    <div style="background:#ef4444;color:#fff;padding:1.5rem;text-align:center;border-radius:8px 8px 0 0;">
        <h2 style="margin:0;font-size:1.25rem;">❌ Stornierung</h2>
    </div>
    <div style="padding:1.5rem;background:#fff;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;">
        <p>Hallo {{provider_name}},</p>
        <p>Die folgende Buchung wurde storniert:</p>
        <table style="width:100%;border-collapse:collapse;margin:1rem 0;">
            <tr><td style="padding:.5rem;color:#64748b;">Buchung:</td><td style="padding:.5rem;">#{{booking_id}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Kunde:</td><td style="padding:.5rem;">{{customer_name}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Datum:</td><td style="padding:.5rem;">{{date}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Uhrzeit:</td><td style="padding:.5rem;">{{time}}</td></tr>
        </table>
    </div>
</div>
HTML;
    }

    private function tpl_admin_new_booking(): string
    {
        return <<<'HTML'
<div style="max-width:600px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1e293b;">
    <div style="background:#1e293b;color:#fff;padding:1.5rem;text-align:center;border-radius:8px 8px 0 0;">
        <h2 style="margin:0;font-size:1.25rem;">🔔 Neue Buchung</h2>
    </div>
    <div style="padding:1.5rem;background:#fff;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 8px 8px;">
        <table style="width:100%;border-collapse:collapse;">
            <tr><td style="padding:.5rem;color:#64748b;">Buchung:</td><td style="padding:.5rem;">#{{booking_id}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Kunde:</td><td style="padding:.5rem;">{{customer_name}} ({{customer_email}})</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Anbieter:</td><td style="padding:.5rem;">{{provider_name}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Leistung:</td><td style="padding:.5rem;">{{service_title}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Datum:</td><td style="padding:.5rem;">{{date}} {{time}}</td></tr>
            <tr><td style="padding:.5rem;color:#64748b;">Preis:</td><td style="padding:.5rem;">{{price}}</td></tr>
        </table>
    </div>
</div>
HTML;
    }

    /* ================================================================== */
    /*  Mail-Versand                                                       */
    /* ================================================================== */

    private function send(string $to, string $subject, string $htmlBody): bool
    {
        if ($to === '') {
            return false;
        }

        $fromName  = $this->get_setting('from_name')  ?: (defined('SITE_NAME') ? SITE_NAME : '365CMS');
        $fromEmail = $this->get_setting('from_email')  ?: (defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'noreply@localhost');

        $headers   = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = "From: {$fromName} <{$fromEmail}>";

        return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }

    private function get_setting(string $key): string
    {
        try {
            $db   = \CMS\Database::instance();
            $stmt = $db->prepare(
                "SELECT setting_value FROM {$db->getPrefix()}booking_settings WHERE setting_key = ?"
            );
            $stmt->execute([$key]);
            return (string) ($stmt->fetchColumn() ?: '');
        } catch (\Throwable $e) {
            return '';
        }
    }
}
