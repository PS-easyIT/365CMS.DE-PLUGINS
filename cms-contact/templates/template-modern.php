<?php
/**
 * Template: Modern
 * Modernes Kartendesign mit abgerundeten Ecken und Schatten-Effekten.
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
        <div class="contact-container contact-modern">
            <!-- Dekorativer Hintergrund -->
            <div class="contact-modern-bg" aria-hidden="true">
                <div class="contact-modern-shape contact-modern-shape-1"></div>
                <div class="contact-modern-shape contact-modern-shape-2"></div>
            </div>

            <div class="contact-card contact-card-elevated">
                <header class="contact-header contact-header-centered">
                    <h1 id="contact-form-title"><?php echo $e($form['title']); ?></h1>
                    <?php if (!empty($form['description'])): ?>
                    <p class="contact-description"><?php echo $e($form['description']); ?></p>
                    <?php endif; ?>
                </header>

                <?php if (!empty($success)): ?>
                <div class="contact-alert contact-alert-success contact-alert-modern" role="status" aria-live="polite" data-contact-message tabindex="-1">
                    <div>
                        <strong><?php echo $e($t('Vielen Dank!', 'Thank you!')); ?></strong><br>
                        <?php echo $e($success); ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                <div class="contact-alert contact-alert-error contact-alert-modern" role="alert" aria-live="assertive" data-contact-message tabindex="-1">
                    <div><?php echo $e($error); ?></div>
                </div>
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

                    <div class="contact-fields">
                        <?php foreach ($fields as $field): ?>
                        <div class="contact-field contact-field-<?php echo $e($field['field_width'] ?? 'full'); ?> contact-field-modern">
                            <?php echo CMS_Contact_Frontend::render_field($field, '', $old, $fieldErrors ?? []); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php echo CMS_Contact_Frontend::render_captcha_field($form); ?>

                    <?php echo CMS_Contact_Frontend::render_privacy_consent($form, $old); ?>

                    <div class="contact-submit contact-submit-modern">
                        <button type="submit" class="contact-btn contact-btn-primary contact-btn-modern">
                            <span><?php echo $e($t('Nachricht senden', 'Send message')); ?></span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
