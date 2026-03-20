<?php
/**
 * Template: Booking Expert
 * Kontaktformular für Experten-/Berater-Buchungen – zeigt Expertise-Infos.
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
        .booking-expert-layout { display: grid; grid-template-columns: 340px 1fr; gap: 0; min-height: 70vh; max-width: 1100px; margin: 2rem auto; background: #fff; border-radius: 12px; box-shadow: 0 4px 16px rgba(0,0,0,.08); overflow: hidden; }
        @media (max-width: 768px) { .booking-expert-layout { grid-template-columns: 1fr; } }
        .booking-expert-sidebar { background: linear-gradient(180deg, #1e3a5f 0%, #0f2240 100%); color: #fff; padding: 2.5rem 2rem; display: flex; flex-direction: column; }
        .booking-expert-sidebar h2 { font-size: 1.3rem; margin: 0 0 .5rem; }
        .booking-expert-sidebar p { opacity: .85; font-size: .9rem; line-height: 1.6; }
        .booking-expert-badge { display: inline-flex; align-items: center; gap: .4rem; background: rgba(255,255,255,.15); padding: .3rem .8rem; border-radius: 20px; font-size: .8rem; margin-bottom: 1rem; }
        .booking-expert-features { list-style: none; padding: 0; margin: 1.5rem 0 0; }
        .booking-expert-features li { padding: .4rem 0; font-size: .9rem; opacity: .9; }
        .booking-expert-features li::before { content: '✓ '; color: #4ade80; }
        .booking-expert-form { padding: 2.5rem 2rem; }
        .booking-expert-form h3 { font-size: 1.15rem; margin: 0 0 1rem; color: #1e293b; }
    </style>
    <?php if (!empty($form['custom_css'])): ?>
    <style><?php echo $form['custom_css']; ?></style>
    <?php endif; ?>

    <main class="contact-main">
        <div class="booking-expert-layout">

            <!-- Linke Seite: Experten-Info -->
            <div class="booking-expert-sidebar">
                <span class="booking-expert-badge">🎓 Experten-Beratung</span>
                <h2><?php echo $e($form['title']); ?></h2>
                <?php if (!empty($form['description'])): ?>
                <p><?php echo $e($form['description']); ?></p>
                <?php endif; ?>
                <ul class="booking-expert-features">
                    <li>Individuelle Beratung</li>
                    <li>Persönlicher Ansprechpartner</li>
                    <li>Flexible Terminwahl</li>
                    <li>Online oder vor Ort</li>
                </ul>
            </div>

            <!-- Rechte Seite: Formular -->
            <div class="booking-expert-form">
                <h3>📝 Beratungstermin anfragen</h3>

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
                        <button type="submit" class="contact-btn contact-btn-primary">🎓 Beratung anfragen</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
    <script src="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/js/contact-public.js?v=<?php echo CMS_CONTACT_VERSION; ?>" defer></script>
