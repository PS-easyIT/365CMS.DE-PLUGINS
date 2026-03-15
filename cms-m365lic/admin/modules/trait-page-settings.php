<?php
/**
 * CMS M365 License – Settings Page
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

trait CMS_M365LIC_Page_Settings_Trait
{
    public function render_settings_page(): void
    {
        $notice = '';
        $error = '';
        $tab = sanitize_text_field($_GET['tab'] ?? 'general');
        $billingOptions = CMS_M365LIC_Catalog::billing_options();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_nonce('m365lic_settings')) {
                $error = 'Sicherheitscheck fehlgeschlagen.';
            } else {
                $tab = sanitize_text_field($_POST['tab'] ?? $tab);
                self::repo()->save_settings([
                    'page_title' => trim((string) ($_POST['page_title'] ?? 'Microsoft 365 Lizenzberater')),
                    'page_intro' => trim((string) ($_POST['page_intro'] ?? '')),
                    'route_slug' => trim((string) ($_POST['route_slug'] ?? 'm365-lizenzberater')),
                    'default_currency' => trim((string) ($_POST['default_currency'] ?? 'USD')),
                    'default_group_key' => trim((string) ($_POST['default_group_key'] ?? 'partner')),
                    'default_group_label' => trim((string) ($_POST['default_group_label'] ?? 'Partner / Spezialgruppe')),
                    'public_default_billing_cycle' => array_key_exists((string) ($_POST['public_default_billing_cycle'] ?? ''), $billingOptions) ? (string) $_POST['public_default_billing_cycle'] : 'annual_upfront',
                    'member_default_billing_cycle' => array_key_exists((string) ($_POST['member_default_billing_cycle'] ?? ''), $billingOptions) ? (string) $_POST['member_default_billing_cycle'] : 'annual_monthly',
                    'group_default_billing_cycle' => array_key_exists((string) ($_POST['group_default_billing_cycle'] ?? ''), $billingOptions) ? (string) $_POST['group_default_billing_cycle'] : 'annual_monthly',
                    'public_daily_limit' => (string) max(1, (int) ($_POST['public_daily_limit'] ?? 2)),
                    'member_daily_limit' => (string) max(1, (int) ($_POST['member_daily_limit'] ?? 10)),
                    'group_daily_limit' => (string) max(1, (int) ($_POST['group_daily_limit'] ?? 25)),
                    'public_pdf_daily_limit' => (string) max(1, (int) ($_POST['public_pdf_daily_limit'] ?? 2)),
                    'member_pdf_daily_limit' => (string) max(1, (int) ($_POST['member_pdf_daily_limit'] ?? 10)),
                    'group_pdf_daily_limit' => (string) max(1, (int) ($_POST['group_pdf_daily_limit'] ?? 25)),
                    'upgrade_url' => trim((string) ($_POST['upgrade_url'] ?? '/kontakt')),
                    'limit_notice' => trim((string) ($_POST['limit_notice'] ?? '')),
                    'pdf_footer' => trim((string) ($_POST['pdf_footer'] ?? '')),
                    'legal_note' => trim((string) ($_POST['legal_note'] ?? '')),
                    'show_missing_price_hint' => !empty($_POST['show_missing_price_hint']) ? '1' : '0',
                    'show_source_notes' => !empty($_POST['show_source_notes']) ? '1' : '0',
                ]);
                $notice = 'Einstellungen gespeichert.';
            }
        }

        $settings = self::repo()->get_settings();
        $csrfToken = self::generate_nonce('m365lic_settings');
        $tabs = [
            'general' => '⚙️ Allgemein',
            'limits' => '🚦 Limits',
            'export' => '📄 Export & Recht',
        ];
        ?>
        <div class="admin-page-header">
            <div>
                <h2>⚙️ Einstellungen</h2>
                <p>Publicsite, Tageslimits, Bereichs-Defaults und PDF-Ausgabe konfigurieren.</p>
            </div>
        </div>

        <?php if ($notice !== ''): ?>
        <div class="alert alert-success">✅ <?php echo self::esc($notice); ?></div>
        <?php endif; ?>
        <?php if ($error !== ''): ?>
        <div class="alert alert-error">❌ <?php echo self::esc($error); ?></div>
        <?php endif; ?>

        <div class="m365lic-tabs">
            <?php foreach ($tabs as $key => $label): ?>
            <a href="?page=m365lic-settings&tab=<?php echo self::esc($key); ?>" class="m365lic-tab<?php echo $tab === $key ? ' active' : ''; ?>"><?php echo self::esc($label); ?></a>
            <?php endforeach; ?>
        </div>

        <div class="admin-card" style="border-radius:0 10px 10px 10px;margin-top:0;max-width:980px;">
            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="tab" value="<?php echo self::esc($tab); ?>">

                <?php if ($tab === 'general'): ?>
                    <h3>⚙️ Allgemeine Einstellungen</h3>
                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="page_title">Seitentitel</label>
                            <input class="form-control" id="page_title" type="text" name="page_title" value="<?php echo self::esc((string) ($settings['page_title'] ?? '')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="route_slug">Route-Slug</label>
                            <input class="form-control" id="route_slug" type="text" name="route_slug" value="<?php echo self::esc((string) ($settings['route_slug'] ?? '')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="default_currency">Standardwährung</label>
                            <input class="form-control" id="default_currency" type="text" name="default_currency" value="<?php echo self::esc((string) ($settings['default_currency'] ?? 'USD')); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="default_group_key">Default Spezialgruppen-Key</label>
                            <input class="form-control" id="default_group_key" type="text" name="default_group_key" value="<?php echo self::esc((string) ($settings['default_group_key'] ?? 'partner')); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="page_intro">Einleitungstext</label>
                        <textarea class="form-control" id="page_intro" name="page_intro" rows="3"><?php echo self::esc((string) ($settings['page_intro'] ?? '')); ?></textarea>
                    </div>

                    <div class="m365lic-form-grid m365lic-form-grid--2">
                        <div class="form-group">
                            <label class="form-label" for="default_group_label">Default Spezialgruppen-Label</label>
                            <input class="form-control" id="default_group_label" type="text" name="default_group_label" value="<?php echo self::esc((string) ($settings['default_group_label'] ?? 'Partner / Spezialgruppe')); ?>">
                        </div>
                    </div>

                    <h3>💳 Default Abrechnung je Bereich</h3>
                    <div class="m365lic-form-grid m365lic-form-grid--3">
                        <div class="form-group">
                            <label class="form-label" for="public_default_billing_cycle">Public</label>
                            <select class="form-control" id="public_default_billing_cycle" name="public_default_billing_cycle">
                                <?php foreach ($billingOptions as $key => $option): ?>
                                <option value="<?php echo self::esc($key); ?>" <?php echo (($settings['public_default_billing_cycle'] ?? 'annual_upfront') === $key) ? 'selected' : ''; ?>><?php echo self::esc((string) $option['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="member_default_billing_cycle">Member</label>
                            <select class="form-control" id="member_default_billing_cycle" name="member_default_billing_cycle">
                                <?php foreach ($billingOptions as $key => $option): ?>
                                <option value="<?php echo self::esc($key); ?>" <?php echo (($settings['member_default_billing_cycle'] ?? 'annual_monthly') === $key) ? 'selected' : ''; ?>><?php echo self::esc((string) $option['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="group_default_billing_cycle">Spezial</label>
                            <select class="form-control" id="group_default_billing_cycle" name="group_default_billing_cycle">
                                <?php foreach ($billingOptions as $key => $option): ?>
                                <option value="<?php echo self::esc($key); ?>" <?php echo (($settings['group_default_billing_cycle'] ?? 'annual_monthly') === $key) ? 'selected' : ''; ?>><?php echo self::esc((string) $option['label']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                <?php elseif ($tab === 'limits'): ?>
                    <h3>🚦 Limits & Upsell</h3>
                    <div class="alert" style="background:#dbeafe;color:#1e40af;border-left:4px solid #3b82f6;margin-bottom:1.25rem;">
                        ℹ️ Standardmäßig sind öffentliche Auswertungen pro Tag bewusst eng begrenzt. Mitglieder und Spezialgruppen können höhere Limits erhalten.
                    </div>
                    <div class="m365lic-form-grid m365lic-form-grid--3">
                        <div class="form-group">
                            <label class="form-label">Public Auswertungen/Tag</label>
                            <input class="form-control" type="number" min="1" name="public_daily_limit" value="<?php echo (int) ($settings['public_daily_limit'] ?? 2); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Member Auswertungen/Tag</label>
                            <input class="form-control" type="number" min="1" name="member_daily_limit" value="<?php echo (int) ($settings['member_daily_limit'] ?? 10); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Spezial Auswertungen/Tag</label>
                            <input class="form-control" type="number" min="1" name="group_daily_limit" value="<?php echo (int) ($settings['group_daily_limit'] ?? 25); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Public PDF/Tag</label>
                            <input class="form-control" type="number" min="1" name="public_pdf_daily_limit" value="<?php echo (int) ($settings['public_pdf_daily_limit'] ?? 2); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Member PDF/Tag</label>
                            <input class="form-control" type="number" min="1" name="member_pdf_daily_limit" value="<?php echo (int) ($settings['member_pdf_daily_limit'] ?? 10); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Spezial PDF/Tag</label>
                            <input class="form-control" type="number" min="1" name="group_pdf_daily_limit" value="<?php echo (int) ($settings['group_pdf_daily_limit'] ?? 25); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="upgrade_url">Upgrade-/Kontakt-URL</label>
                        <input class="form-control" id="upgrade_url" type="text" name="upgrade_url" value="<?php echo self::esc((string) ($settings['upgrade_url'] ?? '/kontakt')); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="limit_notice">Limit-Hinweistext</label>
                        <textarea class="form-control" id="limit_notice" name="limit_notice" rows="3"><?php echo self::esc((string) ($settings['limit_notice'] ?? '')); ?></textarea>
                    </div>
                <?php else: ?>
                    <h3>📄 Export & Recht</h3>
                    <div class="form-group">
                        <label class="form-label" for="legal_note">Rechtlicher Hinweis</label>
                        <textarea class="form-control" id="legal_note" name="legal_note" rows="4"><?php echo self::esc((string) ($settings['legal_note'] ?? '')); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="pdf_footer">PDF-Footer</label>
                        <textarea class="form-control" id="pdf_footer" name="pdf_footer" rows="3"><?php echo self::esc((string) ($settings['pdf_footer'] ?? '')); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_missing_price_hint" value="1" <?php echo !empty($settings['show_missing_price_hint']) ? 'checked' : ''; ?>>
                            Hinweis anzeigen, wenn Preise im Paketkatalog fehlen
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="show_source_notes" value="1" <?php echo !empty($settings['show_source_notes']) ? 'checked' : ''; ?>>
                            Source-/Pricing-Notizen in der Auswertung anzeigen
                        </label>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-primary">💾 Einstellungen speichern</button>
            </form>
        </div>
        <?php
    }
}
