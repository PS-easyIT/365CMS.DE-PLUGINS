<?php
/**
 * Meta Boxes Handler für CMS Companies
 *
 * @package CMS_Companies
 * @since 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_Companies_Meta_Boxes
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {}

    /**
     * Haupteinstiegspunkt – rendert alle Formular-Sektionen.
     * Wird direkt aus CMS_Companies_Admin::render_form() aufgerufen.
     *
     * @param object|null $company  Vorhandenes Firmen-Objekt (Edit) oder null (Neu)
     * @param array       $experts  Verfügbare Experten für Zuweisung
     * @param array       $assigned Bereits zugewiesene Experten
     */
    public function render_company_form_fields($company = null, array $experts = [], array $assigned = []): void
    {
        $this->render_basic_fields($company);
        $this->render_contact_fields($company);
        $this->render_location_fields($company);
        $this->render_status_fields($company);
        // render_expert_fields wird absichtlich NICHT hier aufgerufen –
        // es enthält eigene <form>-Elemente und muss außerhalb des Haupt-Formulars
        // gerendert werden (verschachtelte Forms sind in HTML nicht erlaubt).
    }

    public function render_basic_fields($company = null): void
    {
        $sec  = CMS\Security::instance();
        $name           = $company->name           ?? '';
        $description    = $company->description    ?? '';
        $industry       = $company->industry       ?? '';
        $company_size   = $company->company_size   ?? '';
        $website        = $company->website        ?? '';
        $founded_year   = $company->founded_year   ?? '';
        $employee_count = $company->employee_count ?? '';
        ?>
        <div class="admin-card">
            <h3>🏢 Unternehmensinformationen</h3>

            <div class="form-group">
                <label class="form-label">Firmenname <span style="color:#ef4444;">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="<?= $sec->escape($name) ?>" required
                       placeholder="Name des Unternehmens">
            </div>

            <div class="form-group">
                <label class="form-label">Beschreibung</label>
                <?php if (class_exists('\CMS\Services\EditorService')): ?>
                    <?= \CMS\Services\EditorService::getInstance()->render('description', $description, ['height' => 300]) ?>
                <?php else: ?>
                    <textarea name="description" class="form-control" rows="7"
                              placeholder="Firmenvorstellung, Leistungen, Besonderheiten…"><?= $sec->escape($description) ?></textarea>
                <?php endif; ?>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Branche</label>
                    <select name="industry" class="form-control">
                        <option value="">– Bitte wählen –</option>
                        <?php foreach ($this->get_industries() as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= $industry === $val ? 'selected' : '' ?>>
                                <?= $sec->escape($lbl) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Unternehmensgröße</label>
                    <select name="company_size" class="form-control">
                        <option value="">– Bitte wählen –</option>
                        <?php foreach ($this->get_company_sizes() as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= $company_size === $val ? 'selected' : '' ?>>
                                <?= $sec->escape($lbl) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control"
                           value="<?= $sec->escape($website) ?>"
                           placeholder="https://www.beispiel.de">
                </div>
                <div class="form-group">
                    <label class="form-label">Gründungsjahr</label>
                    <input type="number" name="founded_year" class="form-control"
                           value="<?= $sec->escape((string)($founded_year ?? '')) ?>"
                           min="1800" max="<?= date('Y') ?>" placeholder="<?= date('Y') ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Mitarbeiterzahl</label>
                    <input type="number" name="employee_count" class="form-control"
                           value="<?= $sec->escape((string)($employee_count ?? '')) ?>"
                           min="1" placeholder="z.B. 50">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Logo-URL</label>
                <input type="url" name="logo_url" class="form-control"
                       value="<?= $sec->escape($company->logo_url ?? '') ?>"
                       placeholder="https://cdn.beispiel.de/logo.png"
                       id="logo_url_input">
                <small class="form-text">Direktlink zu einem Firmenlogo (PNG/SVG empfohlen, mind. 120×60 px).</small>
                <?php if (!empty($company->logo_url)): ?>
                <div style="margin-top:.6rem;">
                    <img src="<?= $sec->escape($company->logo_url) ?>" alt="Logo Vorschau" id="logo_preview"
                         style="max-height:60px;max-width:200px;border:1px solid #e2e8f0;border-radius:6px;padding:4px;background:#fff;">
                </div>
                <?php else: ?>
                <div style="margin-top:.6rem;display:none;" id="logo_preview_wrap">
                    <img src="" alt="Logo Vorschau" id="logo_preview"
                         style="max-height:60px;max-width:200px;border:1px solid #e2e8f0;border-radius:6px;padding:4px;background:#fff;">
                </div>
                <?php endif; ?>
            </div>
        </div>
        <script>
        (function(){
            const input = document.getElementById('logo_url_input');
            const img   = document.getElementById('logo_preview');
            const wrap  = document.getElementById('logo_preview_wrap');
            if (!input || !img) return;
            input.addEventListener('change', function(){
                const v = this.value.trim();
                if (v) { img.src = v; if (wrap) wrap.style.display = 'block'; }
            });
        })();
        </script>
        <?php
    }

    public function render_contact_fields($company = null): void
    {
        $sec   = CMS\Security::instance();
        $email = $company->email ?? '';
        $phone = $company->phone ?? '';
        ?>
        <div class="admin-card">
            <h3>📬 Kontaktinformationen</h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">E-Mail-Adresse <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="email" class="form-control"
                           value="<?= $sec->escape($email) ?>"
                           required placeholder="kontakt@beispiel.de">
                </div>
                <div class="form-group">
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="phone" class="form-control"
                           value="<?= $sec->escape($phone) ?>"
                           placeholder="+49 89 123456">
                </div>
            </div>
        </div>
        <?php
    }

    public function render_location_fields($company = null): void
    {
        $sec     = CMS\Security::instance();
        $city    = $company->location_city    ?? '';
        $zip     = $company->location_zip     ?? '';
        $country = $company->location_country ?? 'Deutschland';
        ?>
        <div class="admin-card">
            <h3>📍 Standort</h3>
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Stadt</label>
                    <input type="text" name="location_city" class="form-control"
                           value="<?= $sec->escape($city) ?>" placeholder="München">
                </div>
                <div class="form-group">
                    <label class="form-label">PLZ</label>
                    <input type="text" name="location_zip" class="form-control"
                           value="<?= $sec->escape($zip) ?>" placeholder="80333" maxlength="10">
                </div>
                <div class="form-group">
                    <label class="form-label">Land</label>
                    <input type="text" name="location_country" class="form-control"
                           value="<?= $sec->escape($country) ?>" placeholder="Deutschland">
                </div>
            </div>
        </div>
        <?php
    }

    public function render_status_fields($company = null): void
    {
        $is_partner     = (bool)($company->is_partner     ?? false);
        $is_top_partner = (bool)($company->is_top_partner ?? false);
        $is_sponsor     = (bool)($company->is_sponsor     ?? false);
        $status         = $company->status ?? 'active';
        ?>
        <div class="admin-card">
            <h3>🏅 Partner-Status &amp; Sichtbarkeit</h3>
            <p class="form-text" style="margin:-.5rem 0 1rem;">Nur eine Partnerstufe aktivieren — Sponsor überschreibt Top-Partner und Partner.</p>

            <!-- Aktiv / Inaktiv -->
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label">Unternehmens-Status</label>
                <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
                    <?php foreach (['active' => ['✅','Aktiv','Eintrag sichtbar auf der Website','#16a34a','#f0fdf4'], 'inactive' => ['🔒','Inaktiv','Eintrag versteckt (nur für Admins sichtbar)','#64748b','#f1f5f9']] as $val => [$ico, $lbl, $hint, $col, $bg]): ?>
                    <label style="display:flex;align-items:center;gap:.6rem;padding:.6rem 1rem;background:<?= $status===$val?$bg:'#f8fafc' ?>;border:2px solid <?= $status===$val?$col:'#e2e8f0' ?>;border-radius:8px;cursor:pointer;flex:1;min-width:180px;">
                        <input type="radio" name="company_status" value="<?= $val ?>"
                               <?= $status === $val ? 'checked' : '' ?>
                               style="accent-color:<?= $col ?>;width:16px;height:16px;">
                        <span style="font-size:1rem;"><?= $ico ?></span>
                        <div>
                            <strong style="display:block;font-size:.875rem;color:#1e293b;"><?= $lbl ?></strong>
                            <span style="font-size:.75rem;color:#64748b;"><?= $hint ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <hr style="border:0;border-top:1px solid #f1f5f9;margin:.25rem 0 1rem;">

            <div style="display:flex;flex-direction:column;gap:.65rem;">
                <?php
                // Checkboxes OHNE hidden-Felder – isset() in admin_save prüft ob gesetzt
                $statuses = [
                    'is_partner'     => ['🤝', 'Partner',     'Standard-Partnerschaft',      '#9ca3af', '#f8fafc', $is_partner],
                    'is_top_partner' => ['🥇', 'Top-Partner', 'Hervorgehobene Partnerschaft', '#d97706', '#fffbeb', $is_top_partner],
                    'is_sponsor'     => ['💜', 'Sponsor',     'Höchste Sichtbarkeit',         '#7c3aed', '#faf5ff', $is_sponsor],
                ];
                foreach ($statuses as $field => [$icon, $label, $hint, $color, $bgColor, $checked]):
                ?>
                <label class="checkbox-label"
                       style="display:flex;align-items:center;gap:.875rem;padding:.75rem 1rem;
                              background:<?= $checked ? $bgColor : '#f8fafc' ?>;
                              border:2px solid <?= $checked ? $color : '#e2e8f0' ?>;
                              border-radius:8px;cursor:pointer;">
                    <input type="checkbox" name="<?= $field ?>" value="1"
                           <?= $checked ? 'checked' : '' ?>
                           style="width:17px;height:17px;accent-color:<?= $color ?>;cursor:pointer;">
                    <span style="font-size:1.15rem;"><?= $icon ?></span>
                    <div>
                        <strong style="display:block;font-size:.875rem;color:#1e293b;"><?= $label ?></strong>
                        <span style="font-size:.78rem;color:#64748b;"><?= $hint ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }


    // ─────────────────────────────────────────────────────────────────────────
    // Experten-Zuordnung (nur beim Bearbeiten)
    // ─────────────────────────────────────────────────────────────────────────

    public function render_expert_fields($company, array $experts = [], array $assigned = []): void
    {
        $sec        = CMS\Security::instance();
        $company_id = (int)($company->id ?? 0);
        if ($company_id <= 0) { return; }

        $assignedIds = array_map(fn($e) => (int)$e->id, $assigned);
        $available   = array_filter($experts, fn($e) => !in_array((int)$e->id, $assignedIds, true));
        $csrf        = CMS\Security::instance()->generateToken('company_expert');
        ?>
        <div class="admin-card">
            <h3>👥 Experten-Zuordnung</h3>

            <?php if (!empty($assigned)): ?>
            <p class="form-text" style="margin:-.5rem 0 1rem;">
                <?= count($assigned) ?> zugeordnete<?= count($assigned) === 1 ? 'r' : '' ?> Experte<?= count($assigned) !== 1 ? 'n' : '' ?>
            </p>
            <div style="display:flex;flex-direction:column;gap:.5rem;margin-bottom:1.5rem;">
                <?php foreach ($assigned as $exp):
                    $expName = $sec->escape(trim(($exp->first_name ?? '') . ' ' . ($exp->last_name ?? '')) ?: 'Experte');
                    $expPos  = !empty($exp->position) ? $sec->escape($exp->position) : '';
                    $expRole = !empty($exp->role)     ? $sec->escape($exp->role)     : '';
                    $isCurr  = (bool)($exp->is_current ?? true);
                    $colors  = [['#5e72e4','#8965e0'],['#0891b2','#06b6d4'],['#16a34a','#22c55e'],['#7c3aed','#a855f7']];
                    $cp      = $colors[abs(crc32(($exp->first_name ?? '') . ($exp->last_name ?? ''))) % count($colors)];
                    $letter  = mb_strtoupper(mb_substr(($exp->first_name ?? 'E'), 0, 1));
                ?>
                <div style="display:flex;align-items:center;gap:.875rem;padding:.75rem 1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;">
                    <div style="width:36px;height:36px;border-radius:7px;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:.95rem;background:linear-gradient(135deg,<?= $cp[0] ?>,<?= $cp[1] ?>);">
                        <?= $letter ?>
                    </div>
                    <div style="flex:1;min-width:0;">
                        <strong style="display:block;font-size:.875rem;color:#1e293b;"><?= $expName ?></strong>
                        <?php if ($expPos): ?><span style="font-size:.75rem;color:#64748b;">🎯 <?= $expPos ?></span><?php endif; ?>
                        <?php if ($expRole): ?><span style="font-size:.75rem;color:#475569;margin-left:.5rem;">💼 <?= $expRole ?></span><?php endif; ?>
                        <?php if ($isCurr): ?><span style="display:inline-block;margin-left:.5rem;font-size:.68rem;background:#dcfce7;color:#166534;padding:.1rem .4rem;border-radius:4px;font-weight:700;">✓ Aktiv</span><?php endif; ?>
                    </div>
                    <form method="POST" action="<?= SITE_URL ?>/admin/companies/expert/remove" style="display:contents;">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="company_id" value="<?= $company_id ?>">
                        <input type="hidden" name="expert_id"  value="<?= (int)$exp->id ?>">
                        <button type="submit" class="btn btn-secondary"
                                style="padding:.3rem .7rem;font-size:.78rem;"
                                onclick="return confirm('Experten-Zuordnung entfernen?')">✕</button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state" style="padding:1.5rem;text-align:center;margin-bottom:1.25rem;">
                <p style="font-size:1.75rem;margin:0;">👥</p>
                <p><strong>Noch keine Experten zugeordnet</strong></p>
                <p class="form-text">Weise diesem Unternehmen unten einen Experten zu.</p>
            </div>
            <?php endif; ?>

            <?php if (!empty($available)): ?>
            <hr style="border:0;border-top:1px solid #f1f5f9;margin:1.25rem 0;">
            <h4 style="font-size:.875rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">➕ Experten zuordnen</h4>
            <form method="POST" action="<?= SITE_URL ?>/admin/companies/expert/assign">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="company_id" value="<?= $company_id ?>">
                <div style="display:grid;grid-template-columns:2fr 1fr auto;gap:.875rem;align-items:end;">
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Experte</label>
                        <select name="expert_id" class="form-control" required>
                            <option value="">– Experten wählen –</option>
                            <?php foreach ($available as $exp):
                                $expName = $sec->escape(trim(($exp->first_name ?? '') . ' ' . ($exp->last_name ?? '')) ?: 'Experte');
                                $expPos  = !empty($exp->position) ? ' – ' . $sec->escape($exp->position) : '';
                            ?>
                                <option value="<?= (int)$exp->id ?>"><?= $expName ?><?= $expPos ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label">Rolle (optional)</label>
                        <input type="text" name="role" class="form-control" placeholder="z.B. CTO, Consultant">
                    </div>
                    <button type="submit" class="btn btn-primary" style="height:42px;">➕ Zuordnen</button>
                </div>
                <label class="checkbox-label" style="display:flex;align-items:center;gap:.5rem;margin-top:.75rem;font-size:.875rem;cursor:pointer;font-weight:400;">
                    <input type="hidden" name="is_current" value="0">
                    <input type="checkbox" name="is_current" value="1" checked>
                    Als aktuell aktiv markieren
                </label>
            </form>
            <?php elseif (empty($experts)): ?>
            <p class="form-text" style="text-align:center;padding:.75rem;">
                Noch keine Experten im System.
                <a href="<?= SITE_URL ?>/admin/experts/new" style="color:var(--admin-primary);">Experten anlegen →</a>
            </p>
            <?php else: ?>
            <p class="form-text">Alle verfügbaren Experten sind bereits zugeordnet.</p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function get_industries(): array
    {
        return [
            'it' => 'IT & Software',
            'consulting' => 'Beratung',
            'finance' => 'Finanzdienstleistungen',
            'manufacturing' => 'Produktion & Fertigung',
            'healthcare' => 'Gesundheitswesen',
            'retail' => 'Einzelhandel',
            'logistics' => 'Logistik & Transport',
            'energy' => 'Energie & Versorgung',
            'education' => 'Bildung & Forschung',
            'media' => 'Medien & Kommunikation',
            'real_estate' => 'Immobilien',
            'automotive' => 'Automobil',
            'telecom' => 'Telekommunikation',
            'other' => 'Sonstiges',
        ];
    }

    private function get_company_sizes(): array
    {
        return [
            '1-10'      => '1–10 Mitarbeiter',
            '11-50'     => '11–50 Mitarbeiter',
            '51-200'    => '51–200 Mitarbeiter',
            '201-500'   => '201–500 Mitarbeiter',
            '501-1000'  => '501–1.000 Mitarbeiter',
            '1001-5000' => '1.001–5.000 Mitarbeiter',
            '5001+'     => '5.001+ Mitarbeiter',
        ];
    }
}
