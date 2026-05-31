<?php
/**
 * Template: Buchungsbestätigung
 * Zeigt Zusammenfassung + Kalender-Links nach erfolgreicher Buchung.
 *
 * Variablen: $booking, $googleUrl, $siteUrl
 *
 * @package CMS_Booking
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';

$statusLabels = CMS_Booking_Bookings::status_labels();
$statusClass  = CMS_Booking_Bookings::status_badge_class((string) $booking['status']);
$meetingUrl   = '';
if (!empty($booking['meeting_url']) && filter_var($booking['meeting_url'], FILTER_VALIDATE_URL)) {
    $meetingScheme = strtolower((string) parse_url($booking['meeting_url'], PHP_URL_SCHEME));
    if (in_array($meetingScheme, ['http', 'https'], true)) {
        $meetingUrl = (string) $booking['meeting_url'];
    }
}
try {
    $bookingDate = new DateTime((string) $booking['booking_date']);
    $dayNames = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
    $formattedDate = $dayNames[(int) $bookingDate->format('w')] . ', ' . $bookingDate->format('d.m.Y');
} catch (\Throwable $e) {
    $formattedDate = (string) ($booking['booking_date'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buchung bestätigt – <?php echo $e($siteName); ?></title>
    <?php \CMS\Hooks::doAction('head'); ?>
</head>
<body class="booking-page booking-confirmation-page">
    <?php \CMS\Hooks::doAction('body_start'); ?>
    <?php \CMS\ThemeManager::instance()->render('header'); ?>
    <?php \CMS\Hooks::doAction('after_header'); ?>

    <main class="booking-main">
        <div class="booking-container booking-container--narrow">

            <div class="booking-confirmation-card">
                <h1>Vielen Dank für Ihre Buchung!</h1>
                <p class="booking-confirmation-subtitle">
                    <?php if ($booking['status'] === 'confirmed'): ?>
                        Ihr Termin wurde bestätigt.
                    <?php else: ?>
                        Ihre Buchung ist eingegangen und wird in Kürze bestätigt.
                    <?php endif; ?>
                </p>

                <!-- Zusammenfassung -->
                <div class="booking-confirmation-summary">
                    <table class="booking-summary-table">
                        <tr>
                            <th>Buchungs-Nr.</th>
                            <td>#<?php echo (int) $booking['id']; ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="booking-badge booking-badge--<?php echo $e($statusClass); ?>">
                                    <?php echo $e($statusLabels[$booking['status']] ?? $booking['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php if (!empty($booking['service_title'])): ?>
                        <tr>
                            <th>Leistung</th>
                            <td><?php echo $e($booking['service_title']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if (!empty($booking['provider_name'])): ?>
                        <tr>
                            <th>Anbieter</th>
                            <td><?php echo $e($booking['provider_name']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Datum</th>
                            <td>
                                <?php echo $e($formattedDate); ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Uhrzeit</th>
                            <td>
                                <?php echo $e(substr((string) $booking['start_time'], 0, 5)); ?> – <?php echo $e(substr((string) $booking['end_time'], 0, 5)); ?> Uhr
                                (<?php echo (int) $booking['duration_min']; ?> Min.)
                            </td>
                        </tr>
                        <?php if (!empty($booking['location_type'])): ?>
                        <tr>
                            <th>Ort</th>
                            <td>
                                <?php
                                $locLabels = ['online' => 'Online-Termin', 'onsite' => 'Vor Ort', 'hybrid' => 'Online / Vor Ort'];
                                echo $e($locLabels[$booking['location_type']] ?? $booking['location_type']);
                                ?>
                                <?php if ($meetingUrl !== ''): ?>
                                <br><a href="<?php echo $e($meetingUrl); ?>" target="_blank" rel="noopener noreferrer">
                                    Meeting-Link öffnen ↗
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((int) $booking['price_cents'] > 0): ?>
                        <tr>
                            <th>Preis</th>
                            <td><?php echo CMS_Booking_Services::format_price((int) $booking['price_cents']); ?></td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <!-- Kalender-Links -->
                <div class="booking-confirmation-actions">
                    <p class="booking-confirmation-actions-label">Zum Kalender hinzufügen:</p>
                    <div class="booking-confirmation-btns">
                        <a href="<?php echo $e($siteUrl); ?>/booking/ical/<?php echo (int) $booking['id']; ?>?token=<?php echo $e($accessToken ?? ''); ?>"
                           class="booking-btn booking-btn-secondary" download>
                            iCal / Outlook
                        </a>
                        <a href="<?php echo $e($googleUrl); ?>"
                           class="booking-btn booking-btn-secondary" target="_blank" rel="noopener noreferrer">
                            Google Kalender
                        </a>
                    </div>
                </div>

                <!-- Hinweise -->
                <div class="booking-confirmation-notes">
                    <?php if ($booking['status'] === 'pending'): ?>
                    <p>Sie erhalten eine E-Mail, sobald Ihre Buchung bestätigt wurde.</p>
                    <?php endif; ?>
                    <p>Eine Bestätigung wurde an <strong><?php echo $e($booking['customer_email']); ?></strong> gesendet.</p>
                </div>

                <!-- Zurück -->
                <div class="booking-confirmation-back">
                    <a href="<?php echo $e($siteUrl); ?>" class="booking-btn booking-btn-outline">← Zur Startseite</a>
                </div>
            </div>

        </div>
    </main>

    <?php \CMS\Hooks::doAction('before_footer'); ?>
    <?php \CMS\ThemeManager::instance()->render('footer'); ?>
    <?php \CMS\Hooks::doAction('body_end'); ?>
</body>
</html>
