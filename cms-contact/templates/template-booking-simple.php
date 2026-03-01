<?php
/**
 * Template: Booking Simple
 * Schlichtes Buchungs-Kontaktformular – universell einsetzbar.
 * Wird von CMS-Booking bereitgestellt, ist aber in cms-contact eigenständig nutzbar.
 *
 * Variablen: $form, $fields, $csrfToken, $success, $error, $old
 *
 * @package CMS_Contact
 * @subpackage CMS_Booking
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';
$themeUrl = CMS\ThemeManager::instance()->getThemeUrl();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $e($form['title']); ?> – <?php echo $e($siteName); ?></title>
    <?php CMS\Hooks::doAction('head'); ?>
    <link rel="stylesheet" href="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/css/contact-public.css?v=<?php echo CMS_CONTACT_VERSION; ?>">
    <style>
        .booking-contact-card { max-width: 640px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,.08); overflow: hidden; }
        .booking-contact-header { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; padding: 2rem 2rem 1.5rem; }
        .booking-contact-header h1 { font-size: 1.5rem; margin: 0 0 .25rem; }
        .booking-contact-header p { opacity: .9; margin: 0; font-size: .95rem; }
        .booking-contact-body { padding: 2rem; }
        .booking-contact-badge { display: inline-block; background: rgba(255,255,255,.2); color: #fff; padding: .25rem .75rem; border-radius: 20px; font-size: .8rem; margin-bottom: .75rem; }
    </style>
    <?php if (!empty($form['custom_css'])): ?>
    <style><?php echo $form['custom_css']; ?></style>
    <?php endif; ?>
</head>
<body class="contact-page contact-template-booking-simple">
    <?php CMS\Hooks::doAction('body_start'); ?>
    <?php CMS\ThemeManager::instance()->render('header'); ?>
    <?php CMS\Hooks::doAction('after_header'); ?>

    <main class="contact-main">
        <div class="booking-contact-card">
            <div class="booking-contact-header">
                <span class="booking-contact-badge">📅 Buchungsanfrage</span>
                <h1><?php echo $e($form['title']); ?></h1>
                <?php if (!empty($form['description'])): ?>
                <p><?php echo $e($form['description']); ?></p>
                <?php endif; ?>
            </div>

            <div class="booking-contact-body">
                <?php if (!empty($success)): ?>
                <div class="contact-alert contact-alert-success">✅ <?php echo $e($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                <div class="contact-alert contact-alert-error">❌ <?php echo $e($error); ?></div>
                <?php endif; ?>

                <?php if (empty($success)): ?>
                <form method="POST" class="contact-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <?php if (!empty($form['enable_honeypot'])): ?>
                    <div style="position:absolute;left:-9999px;" aria-hidden="true">
                        <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                    </div>
                    <?php endif; ?>

                    <div class="contact-fields">
                        <?php foreach ($fields as $field): ?>
                        <div class="contact-field contact-field-<?php echo $e($field['width']); ?>">
                            <?php CMS_Contact_Frontend::render_field($field, $old, $e); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($form['enable_captcha'])): ?>
                    <div class="contact-field contact-field-full contact-captcha">
                        <?php $a = rand(1, 10); $b = rand(1, 10); ?>
                        <label class="contact-label">Spamschutz: <?php echo $a; ?> + <?php echo $b; ?> = ? <span class="contact-required">*</span></label>
                        <input type="number" name="captcha_answer" class="contact-input" required>
                        <input type="hidden" name="captcha_expected" value="<?php echo $a + $b; ?>">
                    </div>
                    <?php endif; ?>

                    <div class="contact-submit">
                        <button type="submit" class="contact-btn contact-btn-primary">📅 Anfrage absenden</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php CMS\Hooks::doAction('before_footer'); ?>
    <?php CMS\ThemeManager::instance()->render('footer'); ?>
    <script src="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/js/contact-public.js?v=<?php echo CMS_CONTACT_VERSION; ?>" defer></script>
    <?php CMS\Hooks::doAction('body_end'); ?>
</body>
</html>
