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
$mapEmbed     = $getInfo('map_embed_url') ?: '';

$theme = CMS\ThemeManager::instance();
$theme->getHeader();
?>
    <link rel="stylesheet" href="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/css/contact-public.css?v=<?php echo CMS_CONTACT_VERSION; ?>">
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
            <section class="contact-business-cards" aria-label="Kontaktinformationen">
                <?php if ($companyEmail): ?>
                <article class="contact-business-card">
                    <span class="contact-business-card-icon" aria-hidden="true">📧</span>
                    <h4>E-Mail</h4>
                    <a href="mailto:<?php echo $e($companyEmail); ?>"><?php echo $e($companyEmail); ?></a>
                </article>
                <?php endif; ?>

                <?php if ($companyPhone): ?>
                <article class="contact-business-card">
                    <span class="contact-business-card-icon" aria-hidden="true">📞</span>
                    <h4>Telefon</h4>
                    <a href="tel:<?php echo $e(preg_replace('/[^+0-9]/', '', $companyPhone)); ?>"><?php echo $e($companyPhone); ?></a>
                </article>
                <?php endif; ?>

                <?php if ($companyAddr): ?>
                <article class="contact-business-card">
                    <span class="contact-business-card-icon" aria-hidden="true">📍</span>
                    <h4>Adresse</h4>
                    <address class="contact-address"><?php echo nl2br($e($companyAddr)); ?></address>
                </article>
                <?php endif; ?>
            </section>

            <!-- Formular -->
            <section class="contact-card contact-card-elevated" aria-labelledby="contact-business-form-title">
                <h2 class="contact-business-form-title" id="contact-business-form-title">Schreiben Sie uns</h2>

                <?php if (!empty($success)): ?>
                <div class="contact-alert contact-alert-success" role="status" aria-live="polite" data-contact-message tabindex="-1">✅ <?php echo $e($success); ?></div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                <div class="contact-alert contact-alert-error" role="alert" aria-live="assertive" data-contact-message tabindex="-1">❌ <?php echo $e($error); ?></div>
                <?php endif; ?>

                <?php if (empty($success)): ?>
                <form method="POST" class="contact-form" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
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
                        <label class="contact-label" for="contact-captcha-answer">Sicherheitsfrage: <?php echo $a; ?> + <?php echo $b; ?> = ? <span class="contact-required">*</span></label>
                        <input type="number" id="contact-captcha-answer" name="captcha_answer" class="contact-input" inputmode="numeric" required>
                    </div>
                    <?php endif; ?>

                    <?php echo CMS_Contact_Frontend::render_privacy_consent($form, $old); ?>

                    <div class="contact-submit">
                        <button type="submit" class="contact-btn contact-btn-primary">📧 Nachricht senden</button>
                    </div>
                </form>
                <?php endif; ?>
            </section>

            <!-- Kartenbereich -->
            <?php if ($mapEmbed): ?>
            <section class="contact-business-map" aria-label="Standortkarte">
                <iframe src="<?php echo $e($mapEmbed); ?>" width="100%" height="350" class="contact-embed-frame" title="Kartenansicht des Standorts" allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </section>
            <?php endif; ?>
        </div>
    </main>

    <?php CMS\ThemeManager::instance()->getFooter(); ?>
    <script src="<?php echo CMS_CONTACT_PLUGIN_URL; ?>assets/js/contact-public.js?v=<?php echo CMS_CONTACT_VERSION; ?>" defer></script>
