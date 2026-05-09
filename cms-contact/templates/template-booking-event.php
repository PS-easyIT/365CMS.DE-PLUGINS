<?php
/**
 * Template: Booking Event
 * Kontaktformular für Event-/Speaker-Buchungen – zeigt Event-spezifische Infos.
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
        .booking-event-card { max-width: 700px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,.08); overflow: hidden; }
        .booking-event-header { background: linear-gradient(135deg, #7c3aed 0%, #4f46e5 50%, #3b82f6 100%); color: #fff; padding: 2rem 2rem 1.5rem; position: relative; }
        .booking-event-header::after { content: ''; position: absolute; bottom: -1px; left: 0; right: 0; height: 30px; background: #fff; border-radius: 16px 16px 0 0; }
        .booking-event-header h1 { font-size: 1.5rem; margin: 0 0 .25rem; position: relative; z-index: 1; }
        .booking-event-header p { opacity: .9; margin: 0; font-size: .95rem; position: relative; z-index: 1; }
        .booking-event-badges { display: flex; gap: .5rem; flex-wrap: wrap; margin-bottom: .75rem; position: relative; z-index: 1; }
        .booking-event-badge { display: inline-flex; align-items: center; gap: .3rem; background: rgba(255,255,255,.2); color: #fff; padding: .25rem .7rem; border-radius: 20px; font-size: .78rem; }
        .booking-event-body { padding: 1.5rem 2rem 2rem; }
        .booking-event-body h3 { font-size: 1.1rem; margin: 0 0 1rem; color: #1e293b; }
    </style>
    <?php echo CMS_Contact_Frontend::render_custom_css($form); ?>

    <main class="contact-main" aria-labelledby="contact-form-title">
        <article class="booking-event-card">
            <header class="booking-event-header">
                <div class="booking-event-badges" aria-hidden="true">
                    <span class="booking-event-badge">🎤 Event-Buchung</span>
                    <span class="booking-event-badge">🗓 Terminanfrage</span>
                </div>
                <h1 id="contact-form-title"><?php echo $e($form['title']); ?></h1>
                <?php if (!empty($form['description'])): ?>
                <p><?php echo $e($form['description']); ?></p>
                <?php endif; ?>
            </header>

            <section class="booking-event-body" aria-labelledby="booking-event-form-title">
                <h2 id="booking-event-form-title">📝 Veranstaltung anfragen</h2>

                <?php if (!empty($success)): ?>
                <div class="contact-alert contact-alert-success" role="status" aria-live="polite" data-contact-message tabindex="-1">✅ <?php echo $e($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                <div class="contact-alert contact-alert-error" role="alert" aria-live="assertive" data-contact-message tabindex="-1">❌ <?php echo $e($error); ?></div>
                <?php endif; ?>

                <?php if (empty($success)): ?>
                <form method="POST" class="contact-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="contact_started_at" value="<?php echo (int) time(); ?>">
                    <?php if (!empty($form['enable_honeypot'])): ?>
                    <div class="contact-honeypot" aria-hidden="true">
                        <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                    </div>
                    <?php endif; ?>

                    <div class="contact-fields">
                        <?php foreach ($fields as $field): ?>
                        <div class="contact-field contact-field-<?php echo $e($field['field_width'] ?? 'full'); ?>">
                            <?php echo CMS_Contact_Frontend::render_field($field, '', $old, $fieldErrors ?? []); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($form['enable_captcha'])): ?>
                    <div class="contact-field contact-field-full contact-captcha">
                        <?php $a = rand(1, 10); $b = rand(1, 10); $_SESSION['captcha_expected_' . $form['slug']] = $a + $b; ?>
                        <label class="contact-label" for="contact-captcha-answer">Spamschutz: <?php echo $a; ?> + <?php echo $b; ?> = ? <span class="contact-required">*</span></label>
                        <input type="number" id="contact-captcha-answer" name="captcha_answer" class="contact-input" inputmode="numeric" required>
                    </div>
                    <?php endif; ?>

                    <?php echo CMS_Contact_Frontend::render_privacy_consent($form, $old); ?>

                    <div class="contact-submit">
                        <button type="submit" class="contact-btn contact-btn-primary">🎤 Anfrage absenden</button>
                    </div>
                </form>
                <?php endif; ?>
            </section>
        </article>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
    <script src="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/js/contact-public.js?v=<?php echo CMS_CONTACT_VERSION; ?>" defer></script>
