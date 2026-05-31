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
$t = static fn(string $de, string $en): string => CMS_Contact_Frontend::t($de, $en);
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';

$theme = CMS\ThemeManager::instance();
$theme->getHeader();
?>
    <?php echo CMS_Contact_Frontend::render_custom_css($form); ?>

    <main class="contact-main" aria-labelledby="contact-form-title">
        <!-- Hero fullwidth -->
        <header class="contact-fullwidth-hero">
            <div class="contact-fullwidth-hero-overlay" aria-hidden="true"></div>
            <div class="contact-fullwidth-hero-content">
                <h1 id="contact-form-title"><?php echo $e($form['title']); ?></h1>
                <?php if (!empty($form['description'])): ?>
                <p><?php echo $e($form['description']); ?></p>
                <?php endif; ?>
            </div>
        </header>

        <!-- Formular-Bereich -->
        <section class="contact-fullwidth-body">
            <div class="contact-container">
                <div class="contact-card contact-card-elevated contact-fullwidth-card">
                    <?php if (!empty($success)): ?>
                    <div class="contact-alert contact-alert-success contact-alert-modern" role="status" aria-live="polite" data-contact-message tabindex="-1">
                        <div>
                            <strong><?php echo $e($t('Vielen Dank!', 'Thank you!')); ?></strong><br>
                            <?php echo $e($success); ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($error)): ?>
                    <div class="contact-alert contact-alert-error" role="alert" aria-live="assertive" data-contact-message tabindex="-1"><?php echo $e($error); ?></div>
                    <?php endif; ?>

                    <?php if (empty($success)): ?>
                    <form method="POST" class="contact-form" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo $e($csrfToken); ?>">
                        <input type="hidden" name="contact_started_at" value="<?php echo (int) time(); ?>">
                        <?php if (!empty($form['enable_honeypot'])): ?>
                        <div class="contact-honeypot" aria-hidden="true">
                            <input type="text" name="website_url" tabindex="-1" autocomplete="off">
                        </div>
                        <?php endif; ?>

                        <?php echo CMS_Contact_Frontend::render_error_summary($fieldErrors ?? [], 'contact-form-title'); ?>

                        <div class="contact-fields contact-fields-fullwidth">
                            <?php foreach ($fields as $field): ?>
                            <div class="contact-field contact-field-<?php echo $e($field['field_width'] ?? 'full'); ?>">
                                <?php echo CMS_Contact_Frontend::render_field($field, '', $old, $fieldErrors ?? []); ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php echo CMS_Contact_Frontend::render_captcha_field($form); ?>

                        <?php echo CMS_Contact_Frontend::render_privacy_consent($form, $old); ?>

                        <div class="contact-submit contact-submit-centered">
                            <button type="submit" class="contact-btn contact-btn-primary contact-btn-lg">
                                <?php echo $e($t('Nachricht senden', 'Send message')); ?>
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
