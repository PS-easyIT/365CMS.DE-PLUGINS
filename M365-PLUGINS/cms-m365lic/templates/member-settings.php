<?php
/**
 * Member Template: M365 Konditionen & Whitelabel
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$esc = static fn(?string $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
$repo = CMS_M365LIC_Repository::instance();
$costOverrides = is_array($profile['cost_overrides'] ?? null) ? $profile['cost_overrides'] : [];
$settingsUrl = '/member/plugin/m365-license-settings';
$defaultEkMap = [];
$specialUserContext = isset($specialUser) && is_array($specialUser) ? $specialUser : (is_array($profile['special_user'] ?? null) ? $profile['special_user'] : null);
$pricingTier = is_array($specialUserContext)
    ? (string) ($specialUserContext['group_pricing_tier'] ?? ($profile['group_pricing_tier'] ?? 'group'))
    : 'member';
$pricingTierLabel = $pricingTier === 'group' ? 'Spezialpreise' : 'Memberpreise';

foreach ($packages as $package) {
    $packageId = (int) ($package['id'] ?? 0);
    if ($packageId <= 0) {
        continue;
    }

    $defaultEkMap[$packageId] = $repo->get_price_for_package($package, $pricingTier === 'group' ? 'group' : 'member', null, false);
}
?>
<div class="m365lic-main m365lic-main--embedded">
    <section class="m365lic-section">
        <div class="m365lic-container">
            <?php if ($notice !== ''): ?>
            <div class="m365lic-alert m365lic-alert--success">✅ <?php echo $esc($notice); ?></div>
            <?php endif; ?>
            <?php if ($error !== ''): ?>
            <div class="m365lic-alert m365lic-alert--error">❌ <?php echo $esc($error); ?></div>
            <?php endif; ?>

            <div class="m365lic-layout m365lic-layout--single-column">
                <section class="m365lic-card m365lic-card--intro">
                    <div class="m365lic-card__head">
                        <div>
                            <h2>M365 Lizenzberater – Einstellungen</h2>
                            <p>Hinterlege eigene Preise je Paket, dein Logo sowie individuelle Report-Texte für persönliche Kunden- und Whitelabel-Auswertungen.</p>
                        </div>
                        <a href="/member/plugin/m365-license" class="m365lic-btn m365lic-btn--ghost">← Zur Auswertung</a>
                    </div>

                    <nav class="m365lic-local-nav" aria-label="M365 Lizenzberater Menü">
                        <a href="/member/plugin/m365-license" class="m365lic-local-nav__link">🧮 Auswertung</a>
                        <a href="<?php echo $esc($settingsUrl); ?>" class="m365lic-local-nav__link m365lic-local-nav__link--active" aria-current="page">⚙️ Einstellungen</a>
                    </nav>

                    <?php if ($specialUserContext !== null): ?>
                    <div class="m365lic-alert m365lic-alert--success">
                        ✅ Zugewiesene Gruppe: <strong><?php echo $esc((string) ($specialUserContext['group_label'] ?? 'Gruppe')); ?></strong> · Preisquelle: <strong><?php echo $esc($pricingTierLabel); ?></strong> · Standard-Aufschlag: <strong><?php echo $esc(number_format((float) ($specialUserContext['effective_markup_percent'] ?? 0), 2, ',', '.')); ?>%</strong>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo $esc($settingsUrl); ?>" class="m365lic-form">
                        <input type="hidden" name="member_settings_csrf_token" value="<?php echo $esc($csrfToken); ?>">

                        <section class="m365lic-subcard m365lic-subcard--pricing-profile">
                            <div class="m365lic-subcard__head">
                                <div>
                                    <h3>Report-Branding</h3>
                                    <p>Diese Angaben werden für den Whitelabel-Export verwendet. Für Logos sind aus Sicherheitsgründen nur lokale Pfade oder URLs derselben 365CMS-Domain vorgesehen.</p>
                                </div>
                            </div>

                            <div class="m365lic-form-grid m365lic-form-grid--2">
                                <div class="m365lic-field">
                                    <label for="partner_name">Partnername</label>
                                    <input id="partner_name" type="text" name="partner_name" value="<?php echo $esc((string) ($profile['partner_name'] ?? '')); ?>" placeholder="z. B. easyIT Consulting">
                                </div>
                                <div class="m365lic-field">
                                    <label for="partner_logo_path">Logo-Pfad / gleiche Domain-URL</label>
                                    <input id="partner_logo_path" type="text" name="partner_logo_path" value="<?php echo $esc((string) ($profile['partner_logo_path'] ?? '')); ?>" placeholder="/uploads/partner/logo.png oder /ASSETS/images/logo.png">
                                </div>
                                <div class="m365lic-field">
                                    <label for="whitelabel_title">Whitelabel-Reporttitel</label>
                                    <input id="whitelabel_title" type="text" name="whitelabel_title" value="<?php echo $esc((string) ($profile['whitelabel_title'] ?? '')); ?>" placeholder="z. B. Microsoft 365 Lizenzempfehlung 2026">
                                </div>
                                <div class="m365lic-field">
                                    <label for="whitelabel_intro">Whitelabel-Einleitung</label>
                                    <textarea id="whitelabel_intro" name="whitelabel_intro" rows="3" placeholder="Kurze Einleitung für Kunden-Reports..."><?php echo $esc((string) ($profile['whitelabel_intro'] ?? '')); ?></textarea>
                                </div>
                            </div>
                        </section>

                        <section class="m365lic-subcard m365lic-subcard--billing">
                            <div class="m365lic-subcard__head">
                                <div>
                                    <h3>Automatische Aufschläge auf EK</h3>
                                    <p>Diese Prozentsätze werden im Memberdashboard direkt auf deine hinterlegten EKs aufgeschlagen. Im Partnerreport bleiben die Aufschläge außen vor.</p>
                                </div>
                            </div>

                            <div class="m365lic-kpi-grid m365lic-kpi-grid--compact">
                                <div class="m365lic-field">
                                    <label for="base_markup_percent">Basislizenzen</label>
                                    <input id="base_markup_percent" type="number" step="0.01" min="0" name="base_markup_percent" value="<?php echo $esc(number_format((float) ($profile['base_markup_percent'] ?? 0), 2, '.', '')); ?>">
                                </div>
                                <div class="m365lic-field">
                                    <label for="addon_markup_percent">Add-ons</label>
                                    <input id="addon_markup_percent" type="number" step="0.01" min="0" name="addon_markup_percent" value="<?php echo $esc(number_format((float) ($profile['addon_markup_percent'] ?? 0), 2, '.', '')); ?>">
                                </div>
                                <div class="m365lic-field">
                                    <label for="copilot_markup_percent">Copilot</label>
                                    <input id="copilot_markup_percent" type="number" step="0.01" min="0" name="copilot_markup_percent" value="<?php echo $esc(number_format((float) ($profile['copilot_markup_percent'] ?? 0), 2, '.', '')); ?>">
                                </div>
                            </div>
                        </section>

                        <section class="m365lic-subcard m365lic-subcard--requirements">
                            <div class="m365lic-subcard__head">
                                <div>
                                    <h3>Eigene EK je Paket</h3>
                                    <p>Leer gelassene Felder verwenden automatisch den gepflegten <?php echo $esc($pricingTierLabel); ?> aus dem Paketkatalog. Eigene Werte gelten nur für deinen Benutzer.</p>
                                </div>
                            </div>

                            <div class="users-table-container">
                                <table class="m365lic-table m365lic-table--ek-settings">
                                    <thead>
                                        <tr>
                                            <th>Paket</th>
                                            <th>Typ</th>
                                            <th>Standard-EK (<?php echo $esc($pricingTierLabel); ?>)</th>
                                            <th>Eigener EK</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($packages as $package): ?>
                                        <?php $packageId = (int) ($package['id'] ?? 0); ?>
                                        <?php $defaultEk = $defaultEkMap[$packageId] ?? null; ?>
                                        <?php $overrideEk = $costOverrides[$packageId] ?? null; ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo $esc((string) ($package['name'] ?? '')); ?></strong>
                                                <div class="m365lic-muted"><?php echo $esc((string) ($package['description'] ?? '')); ?></div>
                                            </td>
                                            <td><?php echo $esc((string) (($package['kind'] ?? 'base') === 'addon' ? 'Add-on' : 'Basislizenz')); ?></td>
                                            <td><?php echo $defaultEk !== null ? $esc(number_format((float) $defaultEk, 2, ',', '.')) . ' €' : 'offen'; ?></td>
                                            <td>
                                                <input class="m365lic-ek-input" type="number" step="0.01" min="0" name="ek_prices[<?php echo $packageId; ?>]" value="<?php echo $overrideEk !== null ? $esc(number_format((float) $overrideEk, 2, '.', '')) : ''; ?>" placeholder="z. B. 11.50">
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </section>

                        <div class="m365lic-actions">
                            <button type="submit" class="m365lic-btn m365lic-btn--primary">💾 Einstellungen speichern</button>
                            <a href="/member/plugin/m365-license" class="m365lic-btn m365lic-btn--ghost">🧮 Zur Auswertung</a>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </section>
</div>
