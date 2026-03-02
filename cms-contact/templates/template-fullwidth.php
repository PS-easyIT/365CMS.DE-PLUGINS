<?php
/**
 * Template: Fullwidth
 * Volle Breite mit Hero-Sektion und zentriertem Formular.
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
    <?php if (!empty($form['custom_css'])): ?>
    <style><?php echo $form['custom_css']; ?></style>
    <?php endif; ?>

    <main class="contact-main">
        <!-- Hero fullwidth -->
        <section class="contact-fullwidth-hero">
            <div class="contact-fullwidth-hero-overlay"></div>
            <div class="contact-fullwidth-hero-content">
                <h1><?php echo $e($form['title']); ?></h1>
                <?php if (!empty($form['description'])): ?>
                <p><?php echo $e($form['description']); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Formular-Bereich -->
        <section class="contact-fullwidth-body">
            <div class="contact-container">
                <div class="contact-card contact-card-elevated contact-fullwidth-card">
                    <?php if (!empty($success)): ?>
                    <div class="contact-alert contact-alert-success contact-alert-modern">
                        <span class="contact-alert-icon">🎉</span>
                        <div>
                            <strong>Vielen Dank!</strong><br>
                            <?php echo $e($success); ?>
                        </div>
                    </div>
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

                        <div class="contact-fields contact-fields-fullwidth">
                            <?php foreach ($fields as $field): ?>
                            <div class="contact-field contact-field-<?php echo $e($field['field_width'] ?? 'full'); ?>">
                                <?php echo CMS_Contact_Frontend::render_field($field, '', $old); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($form['enable_captcha'])): ?>
                        <div class="contact-field contact-field-full contact-captcha">
                            <?php $a = rand(1, 10); $b = rand(1, 10); $_SESSION['captcha_expected_' . $form['slug']] = $a + $b; ?>
                            <label class="contact-label"><?php echo $a; ?> + <?php echo $b; ?> = ? <span class="contact-required">*</span></label>
                            <input type="number" name="captcha_answer" class="contact-input" required>
                        </div>
                        <?php endif; ?>

                        <div class="contact-submit contact-submit-centered">
                            <button type="submit" class="contact-btn contact-btn-primary contact-btn-lg">
                                📧 Nachricht senden
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
    <script src="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/js/contact-public.js?v=<?php echo CMS_CONTACT_VERSION; ?>" defer></script>
