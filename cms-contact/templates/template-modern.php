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
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $e($form['title']); ?> – <?php echo $e($siteName); ?></title>
    <?php CMS\Hooks::doAction('head'); ?>
    <link rel="stylesheet" href="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/css/contact-public.css?v=<?php echo CMS_CONTACT_VERSION; ?>">
    <?php if (!empty($form['custom_css'])): ?>
    <style><?php echo $form['custom_css']; ?></style>
    <?php endif; ?>
</head>
<body class="contact-page contact-template-modern">
    <?php CMS\Hooks::doAction('body_start'); ?>
    <?php CMS\ThemeManager::instance()->render('header'); ?>
    <?php CMS\Hooks::doAction('after_header'); ?>

    <main class="contact-main">
        <div class="contact-container contact-modern">
            <!-- Dekorativer Hintergrund -->
            <div class="contact-modern-bg">
                <div class="contact-modern-shape contact-modern-shape-1"></div>
                <div class="contact-modern-shape contact-modern-shape-2"></div>
            </div>

            <div class="contact-card contact-card-elevated">
                <div class="contact-header contact-header-centered">
                    <span class="contact-icon-badge">✉️</span>
                    <h1><?php echo $e($form['title']); ?></h1>
                    <?php if (!empty($form['description'])): ?>
                    <p class="contact-description"><?php echo $e($form['description']); ?></p>
                    <?php endif; ?>
                </div>

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
                <div class="contact-alert contact-alert-error contact-alert-modern">
                    <span class="contact-alert-icon">⚠️</span>
                    <div><?php echo $e($error); ?></div>
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
                        <div class="contact-field contact-field-<?php echo $e($field['width']); ?> contact-field-modern">
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

                    <div class="contact-submit contact-submit-modern">
                        <button type="submit" class="contact-btn contact-btn-primary contact-btn-modern">
                            <span>Nachricht senden</span>
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>
                        </button>
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
