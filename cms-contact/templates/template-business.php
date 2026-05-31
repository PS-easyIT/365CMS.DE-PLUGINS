<?php
/**
 * Template: Business
 * Professionelles Business-Layout mit Karten-Bereich für Standort/Kontaktdaten.
 *
 * @package CMS_Contact
 */
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$e = fn(?string $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
$t = static fn(string $de, string $en): string => CMS_Contact_Frontend::t($de, $en);
$siteName = defined('SITE_NAME') ? SITE_NAME : '365CMS';

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
$mapEmbed     = CMS_Contact_Frontend::sanitize_map_embed_url($getInfo('map_embed_url') ?: '');

$theme = CMS\ThemeManager::instance();
$theme->getHeader();
?>
    <?php echo CMS_Contact_Frontend::render_custom_css($form); ?>

    <main class="contact-main" aria-labelledby="contact-form-title">
        <!-- Hero-Bereich -->
        <header class="contact-business-hero">
            <div class="contact-business-hero-inner">
                <h1 id="contact-form-title"><?php echo $e($form['title']); ?></h1>
                <?php if (!empty($form['description'])): ?>
                <p><?php echo $e($form['description']); ?></p>
                <?php endif; ?>
            </div>
        </header>

        <div class="contact-container contact-business">
            <!-- Info-Karten -->
            <section class="contact-business-cards" aria-label="<?php echo $e($t('Kontaktinformationen', 'Contact information')); ?>">
                <?php if ($companyEmail): ?>
                <article class="contact-business-card">
                    <span class="contact-business-card-icon" aria-hidden="true">Mail</span>
                    <h4><?php echo $e($t('E-Mail', 'Email')); ?></h4>
                    <a href="mailto:<?php echo $e($companyEmail); ?>"><?php echo $e($companyEmail); ?></a>
                </article>
                <?php endif; ?>

                <?php if ($companyPhone): ?>
                <article class="contact-business-card">
                    <span class="contact-business-card-icon" aria-hidden="true">Tel</span>
                    <h4><?php echo $e($t('Telefon', 'Phone')); ?></h4>
                    <a href="tel:<?php echo $e(preg_replace('/[^+0-9]/', '', $companyPhone)); ?>"><?php echo $e($companyPhone); ?></a>
                </article>
                <?php endif; ?>

                <?php if ($companyAddr): ?>
                <article class="contact-business-card">
                    <span class="contact-business-card-icon" aria-hidden="true">Ort</span>
                    <h4><?php echo $e($t('Adresse', 'Address')); ?></h4>
                    <address class="contact-address"><?php echo nl2br($e($companyAddr)); ?></address>
                </article>
                <?php endif; ?>
            </section>

            <!-- Formular -->
            <section class="contact-card contact-card-elevated" aria-labelledby="contact-business-form-title">
                <h2 class="contact-business-form-title" id="contact-business-form-title"><?php echo $e($t('Schreiben Sie uns', 'Write to us')); ?></h2>

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

                    <?php echo CMS_Contact_Frontend::render_error_summary($fieldErrors ?? [], 'contact-business-form-title'); ?>

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
                        <button type="submit" class="contact-btn contact-btn-primary"><?php echo $e($t('Nachricht senden', 'Send message')); ?></button>
                    </div>
                </form>
                <?php endif; ?>
            </section>

            <!-- Kartenbereich -->
            <?php if ($mapEmbed): ?>
            <section class="contact-business-map" aria-label="<?php echo $e($t('Standortkarte', 'Location map')); ?>">
                <iframe src="<?php echo $e($mapEmbed); ?>" width="100%" height="350" class="contact-embed-frame" title="<?php echo $e($t('Kartenansicht des Standorts', 'Map view of the location')); ?>" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </section>
            <?php endif; ?>
        </div>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
