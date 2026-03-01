<?php
/**
 * Template: Buchungsformular
 * Datum-/Zeitwahl + Kundendaten für eine Leistung.
 *
 * Variablen: $provider, $service, $csrfToken, $availDates, $useContactForm,
 *            $error, $success, $old, $siteUrl
 *
 * @package CMS_Booking
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';
$old      = $old ?? [];
$val      = fn(string $key, string $default = ''): string => $e($old[$key] ?? $default);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $e($service['title']); ?> – <?php echo $e($provider['display_name']); ?> – <?php echo $e($siteName); ?></title>
    <?php \CMS\Hooks::doAction('head'); ?>
    <link rel="stylesheet" href="<?php echo CMS_BOOKING_PLUGIN_URL; ?>assets/css/booking-public.css?v=<?php echo CMS_BOOKING_VERSION; ?>">
</head>
<body class="booking-page booking-form-page">
    <?php \CMS\Hooks::doAction('body_start'); ?>
    <?php \CMS\ThemeManager::instance()->render('header'); ?>
    <?php \CMS\Hooks::doAction('after_header'); ?>

    <main class="booking-main">
        <div class="booking-container">

            <!-- Breadcrumb -->
            <nav class="booking-breadcrumb">
                <a href="<?php echo $siteUrl; ?>/booking/<?php echo $e($provider['slug']); ?>">← <?php echo $e($provider['display_name']); ?></a>
            </nav>

            <div class="booking-form-layout">

                <!-- Linke Seite: Leistungsinfo -->
                <div class="booking-info-panel">
                    <div class="booking-info-card">
                        <h2><?php echo $e($service['title']); ?></h2>
                        <?php if (!empty($service['description'])): ?>
                        <p><?php echo $e($service['description']); ?></p>
                        <?php endif; ?>
                        <ul class="booking-info-list">
                            <li>🕑 <?php echo CMS_Booking_Services::format_duration((int) $service['duration_min']); ?></li>
                            <li>
                                <?php
                                $priceCents = (int) $service['price_cents'];
                                echo $priceCents > 0
                                    ? '💰 ' . CMS_Booking_Services::format_price($priceCents)
                                    : '✅ Kostenlos';
                                ?>
                            </li>
                            <?php
                            $typeLabels = ['online' => '💻 Online-Termin', 'onsite' => '📍 Vor Ort', 'hybrid' => '🔀 Online oder vor Ort'];
                            $lt = $service['location_type'] ?? 'online';
                            ?>
                            <li><?php echo $typeLabels[$lt] ?? $lt; ?></li>
                        </ul>
                        <div class="booking-info-provider">
                            <strong><?php echo $e($provider['display_name']); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Rechte Seite: Formular -->
                <div class="booking-form-panel">

                    <?php if (!empty($success)): ?>
                    <div class="booking-alert booking-alert-success">✅ <?php echo $e($success); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                    <div class="booking-alert booking-alert-error">❌ <?php echo $e($error); ?></div>
                    <?php endif; ?>

                    <?php if (empty($success)): ?>
                    <form method="POST" class="booking-form" id="bookingForm" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <!-- Honeypot -->
                        <div style="position:absolute;left:-9999px;" aria-hidden="true">
                            <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                        </div>

                        <!-- Schritt 1: Datum -->
                        <div class="booking-step" id="step-date">
                            <h3>📅 Datum wählen</h3>
                            <div class="booking-calendar" id="bookingCalendar"
                                 data-provider-id="<?php echo (int) $provider['id']; ?>"
                                 data-service-id="<?php echo (int) $service['id']; ?>"
                                 data-available-dates='<?php echo json_encode($availDates); ?>'
                                 data-api-url="<?php echo $siteUrl; ?>/api/booking/slots">
                                <!-- JS-gesteuerter Kalender -->
                            </div>
                            <input type="hidden" name="booking_date" id="bookingDate" value="<?php echo $val('booking_date'); ?>" required>
                        </div>

                        <!-- Schritt 2: Uhrzeit -->
                        <div class="booking-step" id="step-time" style="display:none;">
                            <h3>🕑 Uhrzeit wählen</h3>
                            <div class="booking-slots" id="bookingSlots">
                                <p class="text-muted">Bitte zuerst ein Datum wählen.</p>
                            </div>
                            <input type="hidden" name="start_time" id="startTime" value="<?php echo $val('start_time'); ?>" required>
                        </div>

                        <!-- Schritt 3: Kontaktdaten -->
                        <div class="booking-step" id="step-contact" style="display:none;">
                            <h3>👤 Ihre Daten</h3>

                            <div class="booking-field">
                                <label for="customer_name">Name <span class="required">*</span></label>
                                <input type="text" id="customer_name" name="customer_name" class="booking-input"
                                       value="<?php echo $val('customer_name'); ?>" required
                                       placeholder="Max Mustermann">
                            </div>

                            <div class="booking-field">
                                <label for="customer_email">E-Mail <span class="required">*</span></label>
                                <input type="email" id="customer_email" name="customer_email" class="booking-input"
                                       value="<?php echo $val('customer_email'); ?>" required
                                       placeholder="max@muster.de">
                            </div>

                            <div class="booking-field">
                                <label for="customer_phone">Telefon</label>
                                <input type="tel" id="customer_phone" name="customer_phone" class="booking-input"
                                       value="<?php echo $val('customer_phone'); ?>"
                                       placeholder="+49 123 456789">
                            </div>

                            <div class="booking-field">
                                <label for="notes">Nachricht / Anmerkungen</label>
                                <textarea id="notes" name="notes" class="booking-input booking-textarea"
                                          placeholder="Haben Sie besondere Wünsche?"><?php echo $val('notes'); ?></textarea>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="booking-actions" id="step-submit" style="display:none;">
                            <div class="booking-summary" id="bookingSummary"></div>
                            <button type="submit" class="booking-btn booking-btn-primary">
                                📅 Verbindlich buchen
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </main>

    <?php \CMS\Hooks::doAction('before_footer'); ?>
    <?php \CMS\ThemeManager::instance()->render('footer'); ?>
    <script src="<?php echo CMS_BOOKING_PLUGIN_URL; ?>assets/js/booking-public.js?v=<?php echo CMS_BOOKING_VERSION; ?>" defer></script>
    <?php \CMS\Hooks::doAction('body_end'); ?>
</body>
</html>
