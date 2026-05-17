<?php
/**
 * Template: Provider-Übersicht
 * Zeigt alle buchbaren Leistungen eines Anbieters.
 *
 * Variablen: $provider, $services, $siteUrl
 *
 * @package CMS_Booking
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termin buchen – <?php echo $e($provider['display_name']); ?> – <?php echo $e($siteName); ?></title>
    <?php \CMS\Hooks::doAction('head'); ?>
</head>
<body class="booking-page booking-provider-page">
    <?php \CMS\Hooks::doAction('body_start'); ?>
    <?php \CMS\ThemeManager::instance()->render('header'); ?>
    <?php \CMS\Hooks::doAction('after_header'); ?>

    <main class="booking-main">
        <div class="booking-container">

            <!-- Provider-Header -->
            <div class="booking-provider-header">
                <h1><?php echo $e($provider['display_name']); ?></h1>
                <?php if (!empty($provider['bio'])): ?>
                <p class="booking-provider-description"><?php echo $e($provider['bio']); ?></p>
                <?php endif; ?>
                <?php if (!empty($provider['email'])): ?>
                <p class="booking-provider-meta">
                    📧 <?php echo $e($provider['email']); ?>
                    <?php if (!empty($provider['phone'])): ?>
                    &nbsp;·&nbsp;📞 <?php echo $e($provider['phone']); ?>
                    <?php endif; ?>
                </p>
                <?php endif; ?>
            </div>

            <!-- Services -->
            <?php if (empty($services)): ?>
            <div class="booking-empty-state">
                <p style="font-size:2.5rem;">📭</p>
                <p><strong>Aktuell keine Terminart verfügbar</strong></p>
                <p class="text-muted">Bitte versuchen Sie es später erneut.</p>
            </div>
            <?php else: ?>
            <div class="booking-services-grid">
                <?php foreach ($services as $service): ?>
                     <a href="<?php echo $e($siteUrl); ?>/booking/<?php echo $e($provider['slug']); ?>/<?php echo $e($service['slug']); ?>"
                   class="booking-service-card">
                    <div class="booking-service-card__header">
                        <h3><?php echo $e($service['title']); ?></h3>
                        <?php
                        $typeIcons = ['online' => '💻', 'onsite' => '📍', 'hybrid' => '🔀'];
                        $typeLabels = ['online' => 'Online', 'onsite' => 'Vor Ort', 'hybrid' => 'Hybrid'];
                        $lt = $service['location_type'] ?? 'online';
                        ?>
                        <span class="booking-service-card__type">
                            <?php echo $typeIcons[$lt] ?? ''; ?> <?php echo $typeLabels[$lt] ?? $lt; ?>
                        </span>
                    </div>
                    <?php if (!empty($service['description'])): ?>
                    <p class="booking-service-card__desc"><?php echo $e($service['description']); ?></p>
                    <?php endif; ?>
                    <div class="booking-service-card__footer">
                        <span class="booking-service-card__duration">
                            🕑 <?php echo CMS_Booking_Services::format_duration((int) $service['duration_min']); ?>
                        </span>
                        <span class="booking-service-card__price">
                            <?php
                            $priceCents = (int) $service['price_cents'];
                            echo $priceCents > 0
                                ? CMS_Booking_Services::format_price($priceCents)
                                : 'Kostenlos';
                            ?>
                        </span>
                    </div>
                    <span class="booking-service-card__cta">Jetzt buchen →</span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <?php \CMS\Hooks::doAction('before_footer'); ?>
    <?php \CMS\ThemeManager::instance()->render('footer'); ?>
    <?php \CMS\Hooks::doAction('body_end'); ?>
</body>
</html>
