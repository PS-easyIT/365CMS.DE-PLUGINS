<?php
/**
 * Template: Minimal
 * Minimalistisches, ablenkungsfreies Kontaktformular.
 *
 * @package CMS_Contact
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';

$theme = CMS\ThemeManager::instance();
$theme->getHeader();
?>
    <link rel="stylesheet" href="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/css/contact-public.css?v=<?php echo CMS_CONTACT_VERSION; ?>">
    <?php echo CMS_Contact_Frontend::render_custom_css($form); ?>

    <main class="contact-main" aria-labelledby="contact-form-title">
        <div class="contact-container contact-minimal">
            <div class="contact-minimal-wrapper">
                <header>
                <h1 class="contact-minimal-title" id="contact-form-title"><?php echo $e($form['title']); ?></h1>
                <?php if (!empty($form['description'])): ?>
                <p class="contact-minimal-desc"><?php echo $e($form['description']); ?></p>
                <?php endif; ?>
                </header>

                <?php if (!empty($success)): ?>
                <div class="contact-alert contact-alert-success" role="status" aria-live="polite" data-contact-message tabindex="-1">✅ <?php echo $e($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                <div class="contact-alert contact-alert-error" role="alert" aria-live="assertive" data-contact-message tabindex="-1">❌ <?php echo $e($error); ?></div>
                <?php endif; ?>

                <?php if (empty($success)): ?>
                <form method="POST" class="contact-form contact-form-minimal" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <?php if (!empty($form['enable_honeypot'])): ?>
                    <div class="contact-honeypot" aria-hidden="true">
                        <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                    </div>
                    <?php endif; ?>

                    <?php foreach ($fields as $field): ?>
                    <div class="contact-field contact-field-<?php echo $e($field['field_width'] ?? 'full'); ?> contact-field-minimal">
                        <?php echo CMS_Contact_Frontend::render_field($field, '', $old, $fieldErrors ?? []); ?>
                    </div>
                    <?php endforeach; ?>

                    <?php if (!empty($form['enable_captcha'])): ?>
                    <div class="contact-field contact-field-full contact-captcha">
                        <?php $a = rand(1, 10); $b = rand(1, 10); $_SESSION['captcha_expected_' . $form['slug']] = $a + $b; ?>
                        <label class="contact-label" for="contact-captcha-answer"><?php echo $a; ?> + <?php echo $b; ?> = ? <span class="contact-required">*</span></label>
                        <input type="number" id="contact-captcha-answer" name="captcha_answer" class="contact-input" inputmode="numeric" required>
                    </div>
                    <?php endif; ?>

                    <?php echo CMS_Contact_Frontend::render_privacy_consent($form, $old); ?>

                    <button type="submit" class="contact-btn contact-btn-minimal">Senden →</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
    <script src="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/js/contact-public.js?v=<?php echo CMS_CONTACT_VERSION; ?>" defer></script>
