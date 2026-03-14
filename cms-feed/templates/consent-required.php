<?php
/**
 * Template: Consent erforderlich für öffentliche Feed-Inhalte
 *
 * @package CMS_Feed
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$preferencesUrl = (string)($preferencesUrl ?? (SITE_URL . '/cookie-einstellungen'));
$homeUrl = (string)($homeUrl ?? SITE_URL);

\CMS\ThemeManager::instance()->getHeader(['title' => 'Feed-Inhalte erfordern Einwilligung']);
?>

<header class="fd-header">
    <div class="fd-header__inner">
        <h1 class="fd-header__title">Feed-Inhalte derzeit ausgeblendet</h1>
        <p class="fd-header__desc">
            Du hast externe Feed-Inhalte aktuell nicht freigegeben. Deshalb werden aus Datenschutzgründen keine
            Feed-Daten geladen oder angezeigt.
        </p>
    </div>
</header>

<main class="fd-main">
    <section class="fd-empty" aria-labelledby="fd-consent-required-title">
        <p class="fd-empty__icon" aria-hidden="true">🔒</p>
        <p id="fd-consent-required-title" class="fd-empty__text">
            Öffne deine Cookie-Einstellungen und erlaube „Externe Medien“, wenn du CMS-Feed nutzen möchtest.
        </p>
        <div style="display:flex; justify-content:center; gap:.75rem; flex-wrap:wrap; margin-top:1rem;">
            <a class="fd-pagination__btn" href="<?php echo htmlspecialchars($preferencesUrl, ENT_QUOTES, 'UTF-8'); ?>">
                Cookie-Einstellungen öffnen
            </a>
            <a class="fd-pagination__btn" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">
                Zur Startseite
            </a>
        </div>
    </section>
</main>

<script>
(function () {
    'use strict';

    function hasFeedConsent(detail) {
        if (!detail || typeof detail !== 'object') {
            return false;
        }

        var acceptedCategories = Array.isArray(detail.acceptedCategories) ? detail.acceptedCategories : [];
        var acceptedServicesMap = detail.acceptedServices && typeof detail.acceptedServices === 'object'
            ? detail.acceptedServices
            : {};
        var acceptedServices = Object.values(acceptedServicesMap).flatMap(function (services) {
            return Array.isArray(services) ? services : [];
        });

        return acceptedCategories.indexOf('external_media') !== -1 || acceptedServices.indexOf('cms_feed') !== -1;
    }

    window.addEventListener('cms-cookie-consent-change', function (event) {
        if (hasFeedConsent(event.detail)) {
            window.location.reload();
        }
    });
})();
</script>

<?php \CMS\ThemeManager::instance()->getFooter(); ?>