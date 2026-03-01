<?php
/**
 * Template: Split
 * Geteiltes Layout: Kontaktinformationen links, Formular rechts.
 *
 * @package CMS_Contact
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';

// Kontaktinfo aus Settings laden
$db = CMS\Database::instance();
$prefix = $db->prefix();
$getInfo = function(string $key) use ($db, $prefix): string {
    $stmt = $db->prepare("SELECT setting_value FROM {$prefix}contact_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    return (string)($stmt->fetchColumn() ?: '');
};
$companyName  = $getInfo('company_name')  ?: $siteName;
$companyEmail = $getInfo('admin_email')   ?: ($form['recipient'] ?? '');
$companyPhone = $getInfo('company_phone') ?: '';
$companyAddr  = $getInfo('company_address') ?: '';
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
<body class="contact-page contact-template-split">
    <?php CMS\Hooks::doAction('body_start'); ?>
    <?php CMS\ThemeManager::instance()->render('header'); ?>
    <?php CMS\Hooks::doAction('after_header'); ?>

    <main class="contact-main">
        <div class="contact-container contact-split">
            <!-- Linke Seite: Kontaktinformationen -->
            <div class="contact-split-info">
                <div class="contact-split-info-inner">
                    <h1><?php echo $e($form['title']); ?></h1>
                    <?php if (!empty($form['description'])): ?>
                    <p class="contact-description"><?php echo $e($form['description']); ?></p>
                    <?php endif; ?>

                    <div class="contact-info-list">
                        <?php if ($companyEmail): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-icon">📧</span>
                            <div>
                                <strong>E-Mail</strong>
                                <a href="mailto:<?php echo $e($companyEmail); ?>"><?php echo $e($companyEmail); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($companyPhone): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-icon">📞</span>
                            <div>
                                <strong>Telefon</strong>
                                <span><?php echo $e($companyPhone); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($companyAddr): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-icon">📍</span>
                            <div>
                                <strong>Adresse</strong>
                                <span><?php echo nl2br($e($companyAddr)); ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Rechte Seite: Formular -->
            <div class="contact-split-form">
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
                        <div class="contact-field contact-field-<?php echo $e($field['width']); ?>">
                            <?php CMS_Contact_Frontend::render_field($field, $old, $e); ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (!empty($form['enable_captcha'])): ?>
                    <div class="contact-field contact-field-full contact-captcha">
                        <?php $a = rand(1, 10); $b = rand(1, 10); ?>
                        <label class="contact-label">Was ist <?php echo $a; ?> + <?php echo $b; ?>? <span class="contact-required">*</span></label>
                        <input type="number" name="captcha_answer" class="contact-input" required>
                        <input type="hidden" name="captcha_expected" value="<?php echo $a + $b; ?>">
                    </div>
                    <?php endif; ?>

                    <div class="contact-submit">
                        <button type="submit" class="contact-btn contact-btn-primary">📧 Absenden</button>
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
