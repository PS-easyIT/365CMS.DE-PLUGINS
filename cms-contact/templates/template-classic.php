<?php
/**
 * Template: Classic
 * Klassisches einspaliges Kontaktformular mit schlichtem Design.
 *
 * @package CMS_Contact
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';

// Theme Header (beinhaltet DOCTYPE, <html>, <head>, <body>, Navigation)
$theme = CMS\ThemeManager::instance();
$theme->getHeader();
?>
    <link rel="stylesheet" href="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/css/contact-public.css?v=<?php echo CMS_CONTACT_VERSION; ?>">
    <?php if (!empty($form['custom_css'])): ?>
    <style><?php echo $form['custom_css']; ?></style>
    <?php endif; ?>

    <main class="contact-main">
        <div class="contact-container contact-classic">
            <div class="contact-card">
                <div class="contact-header">
                    <h1><?php echo $e($form['title']); ?></h1>
                    <?php if (!empty($form['description'])): ?>
                    <p class="contact-description"><?php echo $e($form['description']); ?></p>
                    <?php endif; ?>
                </div>

                <?php if (!empty($success)): ?>
                <div class="contact-alert contact-alert-success">
                    ✅ <?php echo $e($success); ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                <div class="contact-alert contact-alert-error">
                    ❌ <?php echo $e($error); ?>
                </div>
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
                        <?php
                        $a = rand(1, 10); $b = rand(1, 10); $_SESSION['captcha_expected_' . $form['slug']] = $a + $b;
                        ?>
                        <label class="contact-label">Spamschutz: Was ist <?php echo $a; ?> + <?php echo $b; ?>? <span class="contact-required">*</span></label>
                        <input type="number" name="captcha_answer" class="contact-input" required>
                    </div>
                    <?php endif; ?>

                    <div class="contact-submit">
                        <button type="submit" class="contact-btn contact-btn-primary">📧 Nachricht senden</button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
    <script src="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/js/contact-public.js?v=<?php echo CMS_CONTACT_VERSION; ?>" defer></script>
