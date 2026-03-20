<?php
/**
 * Template: Booking Service
 * Kontaktformular für Service-/Firmen-Buchungen – professionelles Business-Design.
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

$theme = CMS\ThemeManager::instance();
$theme->getHeader();
?>
    <link rel="stylesheet" href="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/css/contact-public.css?v=<?php echo CMS_CONTACT_VERSION; ?>">
    <style>
        .booking-service-wrap { max-width: 750px; margin: 2rem auto; }
        .booking-service-top { text-align: center; margin-bottom: 2rem; }
        .booking-service-top h1 { font-size: 1.6rem; margin: 0 0 .3rem; color: #1e293b; }
        .booking-service-top p { color: #64748b; margin: 0; }
        .booking-service-badge-bar { display: flex; justify-content: center; gap: .5rem; margin-bottom: 1rem; }
        .booking-service-badge { display: inline-flex; align-items: center; gap: .3rem; background: #eff6ff; color: #2563eb; padding: .3rem .8rem; border-radius: 20px; font-size: .8rem; font-weight: 600; }
        .booking-service-formcard { background: #fff; border-radius: 12px; box-shadow: 0 1px 8px rgba(0,0,0,.06); border: 1px solid #e2e8f0; padding: 2rem; }
        .booking-service-formcard h3 { font-size: 1.05rem; margin: 0 0 1.25rem; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: .75rem; }
        .booking-service-trust { display: flex; justify-content: center; gap: 2rem; margin-top: 1.5rem; color: #94a3b8; font-size: .8rem; }
        .booking-service-trust span { display: inline-flex; align-items: center; gap: .3rem; }
    </style>
    <?php if (!empty($form['custom_css'])): ?>
    <style><?php echo $form['custom_css']; ?></style>
    <?php endif; ?>

    <main class="contact-main">
        <div class="booking-service-wrap">

            <div class="booking-service-top">
                <div class="booking-service-badge-bar">
                    <span class="booking-service-badge">🏢 Dienstleistung</span>
                    <span class="booking-service-badge">📋 Buchung</span>
                </div>
                <h1><?php echo $e($form['title']); ?></h1>
                <?php if (!empty($form['description'])): ?>
                <p><?php echo $e($form['description']); ?></p>
                <?php endif; ?>
            </div>

            <div class="booking-service-formcard">
                <h3>📋 Leistung anfragen</h3>

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
                        <div class="contact-field contact-field-<?php echo $e($field['field_width'] ?? 'full'); ?>">
                            <?php echo CMS_Contact_Frontend::render_field($field, '', $old); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($form['enable_captcha'])): ?>
                    <div class="contact-field contact-field-full contact-captcha">
                        <?php $a = rand(1, 10); $b = rand(1, 10); $_SESSION['captcha_expected_' . $form['slug']] = $a + $b; ?>
                        <label class="contact-label">Spamschutz: <?php echo $a; ?> + <?php echo $b; ?> = ? <span class="contact-required">*</span></label>
                        <input type="number" name="captcha_answer" class="contact-input" required>
                    </div>
                    <?php endif; ?>

                    <?php echo CMS_Contact_Frontend::render_privacy_consent($form, $old); ?>

                    <div class="contact-submit">
                        <button type="submit" class="contact-btn contact-btn-primary">📋 Buchung anfragen</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>

            <div class="booking-service-trust">
                <span>🔒 SSL-gesichert</span>
                <span>📧 Sofortige Bestätigung</span>
                <span>🛡️ DSGVO-konform</span>
            </div>
        </div>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
    <script src="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/js/contact-public.js?v=<?php echo CMS_CONTACT_VERSION; ?>" defer></script>
