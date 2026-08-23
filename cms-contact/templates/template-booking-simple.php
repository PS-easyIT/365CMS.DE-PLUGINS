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
$t = static fn(string $de, string $en): string => CMS_Contact_Frontend::t($de, $en);
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';
$descriptionHtml = CMS_Contact_Frontend::render_description($form['description'] ?? '');
$footerDescriptionHtml = CMS_Contact_Frontend::render_description($form['footer_description'] ?? '');

$theme = CMS\ThemeManager::instance();
$theme->getHeader();
?>
    <?php echo CMS_Contact_Frontend::render_custom_css($form); ?>

    <main class="contact-main" aria-labelledby="contact-form-title">
        <article class="booking-contact-card">
            <header class="booking-contact-header">
                <span class="booking-contact-badge" aria-hidden="true"><?php echo $e($t('Buchungsanfrage', 'Booking request')); ?></span>
                <h1 id="contact-form-title"><?php echo $e($form['title']); ?></h1>
                <?php if ($descriptionHtml !== ''): ?>
                <div class="contact-description editorjs-content"><?php echo $descriptionHtml; ?></div>
                <?php endif; ?>
            </header>

            <section class="booking-contact-body" aria-labelledby="contact-form-title">
                <?php if (!empty($success)): ?>
                <div class="contact-alert contact-alert-success" role="status" aria-live="polite" data-contact-message tabindex="-1"><?php echo $e($success); ?></div>
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

                    <div class="contact-fields">
                        <?php foreach ($fields as $field): ?>
                        <div class="contact-field contact-field-<?php echo $e($field['field_width'] ?? 'full'); ?>">
                            <?php echo CMS_Contact_Frontend::render_field($field, '', $old, $fieldErrors ?? []); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php echo CMS_Contact_Frontend::render_captcha_field($form); ?>

                    <?php echo CMS_Contact_Frontend::render_privacy_consent($form, $old); ?>

                    <div class="contact-submit">
                        <button type="submit" class="contact-btn contact-btn-primary"><?php echo $e($t('Anfrage absenden', 'Submit request')); ?></button>
                    </div>
                </form>
                <?php endif; ?>

                <?php if ($footerDescriptionHtml !== ''): ?>
                <footer class="contact-footer-description editorjs-content"><?php echo $footerDescriptionHtml; ?></footer>
                <?php endif; ?>
            </section>
        </article>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
