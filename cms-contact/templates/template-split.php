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

$theme = CMS\ThemeManager::instance();
$theme->getHeader();
?>
    <?php echo CMS_Contact_Frontend::render_custom_css($form); ?>

    <main class="contact-main" aria-labelledby="contact-form-title">
        <div class="contact-container contact-split">
            <!-- Linke Seite: Kontaktinformationen -->
            <aside class="contact-split-info" aria-labelledby="contact-form-title">
                <div class="contact-split-info-inner">
                    <h1 id="contact-form-title"><?php echo $e($form['title']); ?></h1>
                    <?php if (!empty($form['description'])): ?>
                    <p class="contact-description"><?php echo $e($form['description']); ?></p>
                    <?php endif; ?>

                    <div class="contact-info-list">
                        <?php if ($companyEmail): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-icon" aria-hidden="true">Mail</span>
                            <div>
                                <strong>E-Mail</strong>
                                <a href="mailto:<?php echo $e($companyEmail); ?>"><?php echo $e($companyEmail); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($companyPhone): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-icon" aria-hidden="true">Tel</span>
                            <div>
                                <strong>Telefon</strong>
                                <a href="tel:<?php echo $e(preg_replace('/[^+0-9]/', '', $companyPhone)); ?>"><?php echo $e($companyPhone); ?></a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($companyAddr): ?>
                        <div class="contact-info-item">
                            <span class="contact-info-icon" aria-hidden="true">Ort</span>
                            <div>
                                <strong>Adresse</strong>
                                <address class="contact-address"><?php echo nl2br($e($companyAddr)); ?></address>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>

            <!-- Rechte Seite: Formular -->
            <section class="contact-split-form" aria-labelledby="contact-split-form-title">
                <h2 class="contact-visually-hidden" id="contact-split-form-title">Kontaktformular</h2>
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
                        <label class="contact-label" for="contact-captcha-answer">Was ist <?php echo $a; ?> + <?php echo $b; ?>? <span class="contact-required">*</span></label>
                        <input type="number" id="contact-captcha-answer" name="captcha_answer" class="contact-input" inputmode="numeric" required>
                    </div>
                    <?php endif; ?>

                    <?php echo CMS_Contact_Frontend::render_privacy_consent($form, $old); ?>

                    <div class="contact-submit">
                        <button type="submit" class="contact-btn contact-btn-primary">Absenden</button>
                    </div>
                </form>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
