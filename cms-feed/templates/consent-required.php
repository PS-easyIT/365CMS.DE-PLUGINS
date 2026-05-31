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
$lang = ($lang ?? 'de') === 'en' ? 'en' : 'de';
$i18n = [
    'title' => $lang === 'en' ? 'Feed content requires consent' : 'Feed-Inhalte erfordern Einwilligung',
    'headline' => $lang === 'en' ? 'Feed content currently hidden' : 'Feed-Inhalte derzeit ausgeblendet',
    'description' => $lang === 'en'
        ? 'You currently have not enabled external feed content. For privacy reasons, feed data is not loaded or displayed.'
        : 'Du hast externe Feed-Inhalte aktuell nicht freigegeben. Deshalb werden aus Datenschutzgründen keine Feed-Daten geladen oder angezeigt.',
    'body' => $lang === 'en'
        ? 'Open your cookie settings and allow "External Media" to use CMS Feed.'
        : 'Öffne deine Cookie-Einstellungen und erlaube „Externe Medien“, wenn du CMS-Feed nutzen möchtest.',
    'open_preferences' => $lang === 'en' ? 'Open cookie settings' : 'Cookie-Einstellungen öffnen',
    'home' => $lang === 'en' ? 'Back to homepage' : 'Zur Startseite',
];

\CMS\ThemeManager::instance()->getHeader(['title' => $i18n['title']]);
?>

<header class="fd-header">
    <div class="fd-header__inner">
        <h1 class="fd-header__title"><?php echo htmlspecialchars($i18n['headline'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="fd-header__desc">
            <?php echo htmlspecialchars($i18n['description'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
    </div>
</header>

<main class="fd-main">
    <section class="fd-empty" aria-labelledby="fd-consent-required-title" data-feed-consent-required="1">
        <p class="fd-empty__icon" aria-hidden="true">🔒</p>
        <p id="fd-consent-required-title" class="fd-empty__text">
            <?php echo htmlspecialchars($i18n['body'], ENT_QUOTES, 'UTF-8'); ?>
        </p>
        <div class="fd-consent-actions">
            <a class="fd-pagination__btn" href="<?php echo htmlspecialchars($preferencesUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($i18n['open_preferences'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <a class="fd-pagination__btn" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars($i18n['home'], ENT_QUOTES, 'UTF-8'); ?>
            </a>
        </div>
    </section>
</main>

<?php \CMS\ThemeManager::instance()->getFooter(); ?>