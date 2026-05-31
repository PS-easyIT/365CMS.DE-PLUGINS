<?php
/**
 * Meta Boxes für CMS Experts (Admin-Formulare)
 * Vergleichbar mit it-expert-cards/includes/class-meta-boxes.php aus dem WP-Theme.
 *
 * Abschnitte:
 *  1. Basis-Informationen (Vor-/Nachname, Foto-URL, Status)
 *  2. Kontakt-Informationen (E-Mail, Telefon, Mobil)
 *  3. Standort (PLZ, Stadt, Land)
 *  4. Berufliche Informationen (Position, Unternehmen, Erfahrung)
 *  5. Verfügbarkeit & Konditionen
 *  6. Biografie
 *  7. Fachrichtungen/Spezialisierungen (Checkboxen – kein Limit!)
 *  8. Skills: Allgemeine · Tech · Soft (Tag-Eingabe)
 *  9. Online-Präsenz (LinkedIn, Xing, GitHub, Twitter/X, Website)
 * 10. Partner-Status (Partner · Top-Partner · Sponsor)
 *
 * @package CMS_Experts
 * @since 1.1.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (class_exists('CMS_Experts_Meta_Boxes', false)) {
    return;
}

final class CMS_Experts_Meta_Boxes
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

    // ─── Orchester ────────────────────────────────────────────────────────────

    /**
     * Rendert alle Abschnitte des Experten-Formulars
     *
     * @param object|null $expert  Expert-Objekt (Edit) oder null (Neu)
     * @param array       $extras  Vorgeladene Daten: certifications, projects, education, meta, skills
     */
    public function render_expert_form_fields($expert = null, array $extras = []): void
    {
        $is_edit = $expert !== null;
        $eid     = $is_edit ? (int) $expert->id : 0;

        // Lazy-Load: Meta, Skills, Spezialisierungen nur im Edit-Fall
        // Falls $extras bereits Daten enthält, werden diese bevorzugt
        $meta      = $extras['meta']   ?? ($is_edit ? CMS_Experts_Database::instance()->get_all_meta($eid) : []);
        $skills = ['general' => [], 'tech' => [], 'soft' => []];
        if (isset($extras['skills']) && is_array($extras['skills']) && array_key_exists('general', $extras['skills'])) {
            $skills = array_merge($skills, $extras['skills']);
        } elseif ($is_edit) {
            $skills = CMS_Experts_Database::instance()->get_expert_skills_grouped($eid);
        }
        $spec_ids  = $is_edit ? CMS_Experts_Database::instance()->get_expert_specialization_ids($eid) : [];
        $all_specs = class_exists('CMS_Experts_Taxonomies') ? CMS_Experts_Taxonomies::instance()->get_specializations() : [];
        $skill_presets = class_exists('CMS_Experts_Taxonomies') ? CMS_Experts_Taxonomies::instance()->get_skill_presets_grouped() : ['general' => [], 'tech' => [], 'soft' => []];

        $certifications = $extras['certifications'] ?? [];
        $projects       = $extras['projects']       ?? [];
        $education      = $extras['education']      ?? [];

        $this->render_basic_info($expert, $meta);
        $this->render_contact_info($expert);
        $this->render_location($expert);
        $this->render_professional_info($expert, $meta);
        $this->render_availability_rates($expert, $meta);
        $this->render_biography($expert);
        $this->render_additional_info($meta);
        $this->render_fachrichtungen($all_specs, $spec_ids);
        $this->render_skills($skills, $skill_presets);
        $this->render_social_links($meta);
        $this->render_partner_status($meta);
        $this->render_tech_expertise($meta);

        // Nur im Edit-Modus: Zertifikate, Projekte, Ausbildung, Karriere, Referenzen
        if ($is_edit) {
            $this->render_certifications($certifications);
            $this->render_projects($projects);
            $this->render_education($education);
            $this->render_career_stations_form($meta);
            $this->render_references($meta);
            $this->render_services($meta);
            $this->render_network_scale($meta);
        }

        $this->render_form_styles();
    }

    // ─── Sektionen ────────────────────────────────────────────────────────────

    private function render_basic_info($expert, array $meta = []): void
    {
        $esc = CMS\Security::instance();
        ?>
        <div class="form-section">
            <h3>📝 Basis-Informationen</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">Vorname *</label>
                    <input type="text" id="first_name" name="first_name"
                           value="<?php echo $esc->escape($expert->first_name ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="last_name">Nachname *</label>
                    <input type="text" id="last_name" name="last_name"
                           value="<?php echo $esc->escape($expert->last_name ?? ''); ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="photo_url">Foto-URL</label>
                    <input type="url" id="photo_url" name="photo_url"
                           placeholder="https://…/bild.jpg"
                           value="<?php echo $esc->escape($expert->photo_url ?? ''); ?>">
                    <small class="field-hint">Direkte Bild-URL (JPG/PNG). Wird als Profilbild verwendet.</small>
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <?php
                        $cur = $expert->status ?? 'active';
                        foreach (['active' => '✅ Aktiv', 'inactive' => '⛔ Inaktiv', 'pending' => '⏳ Ausstehend'] as $val => $label) :
                            ?>
                            <option value="<?php echo $val; ?>" <?php echo $cur === $val ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="grid-column:1/-1;">
                    <label for="meta_motto">💬 Persönliches Motto / Zitat</label>
                    <input type="text" id="meta_motto" name="meta[motto]"
                           placeholder='z.B. "Code is poetry."'
                           value="<?php echo $esc->escape($meta['motto'] ?? ''); ?>">
                    <small class="field-hint">Wird als Zitat unter dem Namen angezeigt (optional).</small>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_contact_info($expert): void
    {
        $esc = CMS\Security::instance();
        ?>
        <div class="form-section">
            <h3>📞 Kontakt-Informationen</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="email">E-Mail *</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo $esc->escape($expert->email ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Telefon</label>
                    <input type="tel" id="phone" name="phone"
                           value="<?php echo $esc->escape($expert->phone ?? ''); ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="mobile">Mobil</label>
                    <input type="tel" id="mobile" name="mobile"
                           value="<?php echo $esc->escape($expert->mobile ?? ''); ?>">
                </div>
            </div>
        </div>
        <?php
    }

    private function render_location($expert): void
    {
        $esc = CMS\Security::instance();
        ?>
        <div class="form-section">
            <h3>📍 Standort</h3>
            <div class="form-row">
                <div class="form-group" style="max-width:140px;">
                    <label for="location_zip">PLZ</label>
                    <input type="text" id="location_zip" name="location_zip" maxlength="10"
                           value="<?php echo $esc->escape($expert->location_zip ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="location_city">Stadt</label>
                    <input type="text" id="location_city" name="location_city"
                           value="<?php echo $esc->escape($expert->location_city ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="location_country">Land</label>
                    <input type="text" id="location_country" name="location_country"
                           placeholder="Deutschland"
                           value="<?php echo $esc->escape($expert->location_country ?? ''); ?>">
                </div>
            </div>
        </div>
        <?php
    }

    private function render_professional_info($expert, array $meta = []): void
    {
        $esc = CMS\Security::instance();

        // Gespeicherte company_id aus Meta (-1 = Freitext, 0 = Selbstständig, >0 = Firma)
        $saved_cid = isset($meta['company_id']) ? (int)$meta['company_id'] : null;

        // Firmen aus cms-companies laden (wenn Plugin aktiv)
        $has_companies_plugin = class_exists('CMS_Companies_Database');
        $companies = $has_companies_plugin
            ? CMS_Companies_Database::instance()->get_companies(['status' => 'active', 'limit' => 500])
            : [];
        ?>
        <div class="form-section">
            <h3>💼 Berufliche Informationen</h3>
            <div class="form-row">
                <div class="form-group">
                    <label for="position">Position / Berufsbezeichnung</label>
                    <input type="text" id="position" name="position"
                           placeholder="z.B. Senior PHP-Entwickler"
                           value="<?php echo $esc->escape($expert->position ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Firma / Auftraggeber</label>
                    <?php if ($has_companies_plugin): ?>
                    <div class="company-select-wrap">
                        <select id="company_id_select" name="company_id" class="company-id-select">
                            <option value="">— bitte wählen —</option>
                            <option value="__freelance__"
                                <?php echo ($saved_cid === 0) ? 'selected' : ''; ?>>
                                🧑‍💻 Selbstständig / Freiberufler
                            </option>
                            <?php if (!empty($companies)): ?>
                            <optgroup label="Unternehmen">
                                <?php foreach ($companies as $co): ?>
                                <option value="<?php echo (int)$co->id; ?>"
                                    <?php echo ($saved_cid === (int)$co->id) ? 'selected' : ''; ?>>
                                    <?php echo $esc->escape($co->name); ?>
                                    <?php if (!empty($co->location_city)): ?>
                                        (<?php echo $esc->escape($co->location_city); ?>)
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                        <a href="<?php echo SITE_URL; ?>/admin/companies/new"
                           target="_blank"
                           class="btn-company-new"
                           title="Neue Firma im cms-companies Plugin anlegen">
                            ➕ Neue Firma anlegen
                        </a>
                    </div>
                    <?php if ($saved_cid === null && !empty($expert->company)): ?>
                    <p class="company-legacy-hint">
                        Bisher gespeichert: <strong><?php echo $esc->escape($expert->company); ?></strong>
                        — bitte oben neu verknüpfen.
                    </p>
                    <?php endif; ?>
                    <?php else: ?><!-- Fallback: cms-companies nicht aktiv -->
                    <input type="text" id="company" name="company"
                           placeholder="Firmenname oder 'Selbstständig'"
                           value="<?php echo $esc->escape($expert->company ?? ''); ?>">
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="max-width:180px;">
                    <label for="experience_years">Berufserfahrung (Jahre)</label>
                    <input type="number" id="experience_years" name="experience_years"
                           value="<?php echo (int)($expert->experience_years ?? 0); ?>"
                           min="0" max="60">
                </div>
                <div class="form-group">
                    <label for="meta_work_type">💼 Arbeitsform</label>
                    <select id="meta_work_type" name="meta[work_type]">
                        <?php foreach (['' => '— nicht angegeben —', 'freelancer' => 'Freelancer', 'employed' => 'Angestellt', 'agency' => 'Agentur', 'contractor' => 'Contractor'] as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo ($meta['work_type'] ?? '') === $val ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="meta_timezone">🕐 Zeitzone</label>
                    <input type="text" id="meta_timezone" name="meta[timezone]"
                           placeholder="z.B. Europe/Berlin, UTC+1"
                           value="<?php echo $esc->escape($meta['timezone'] ?? ''); ?>">
                </div>
            </div>
        </div>
        <?php
    }

    private function render_availability_rates($expert, array $meta = []): void
    {
        $esc          = CMS\Security::instance();
        $availability = $expert->availability ?? 'available';
        $pref_sizes   = is_array($meta['preferred_company_sizes'] ?? null)
                            ? $meta['preferred_company_sizes']
                            : (json_decode((string)($meta['preferred_company_sizes'] ?? ''), true) ?: []);
        $size_options = ['Startup' => 'Startup (1–50)', 'SMB' => 'KMU (50–500)', 'Enterprise' => 'Konzern (500+)', 'Public' => 'Öffentlicher Sektor'];
        $pay_options  = ['' => '— nicht angegeben —', 'netto_7' => 'Netto 7 Tage', 'netto_14' => 'Netto 14 Tage', 'netto_30' => 'Netto 30 Tage', 'netto_60' => 'Netto 60 Tage', 'vorkasse' => 'Vorkasse'];
        $travel_options = ['' => '— nicht angegeben —', 'included' => 'Im Tagessatz inkl.', 'flat_rate' => 'Pauschale', 'actual_cost' => 'Selbstkosten', 'negotiable' => 'Verhandelbar'];
        ?>
        <div class="form-section">
            <h3>📅 Verfügbarkeit & Konditionen</h3>

            <!-- Verfügbarkeit -->
            <div class="form-row">
                <div class="form-group">
                    <label for="availability">Verfügbarkeit</label>
                    <select id="availability" name="availability">
                        <option value="available"   <?php echo $availability === 'available'   ? 'selected' : ''; ?>>✅ Verfügbar</option>
                        <option value="limited"     <?php echo $availability === 'limited'     ? 'selected' : ''; ?>>🟨 Begrenzt verfügbar</option>
                        <option value="booked" <?php echo $availability === 'booked' ? 'selected' : ''; ?>>🔴 Nicht verfügbar</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="meta_avail_date">Verfügbar ab</label>
                    <input type="date" id="meta_avail_date" name="meta[avail_date]"
                           value="<?php echo $esc->escape($meta['avail_date'] ?? ''); ?>">
                </div>
            </div>

            <!-- Honorare -->
            <div class="form-row">
                <div class="form-group">
                    <label for="hourly_rate">Stundensatz (€)</label>
                    <input type="number" id="hourly_rate" name="hourly_rate"
                           value="<?php echo $expert->hourly_rate ?? ''; ?>"
                           step="5" min="0" placeholder="0">
                </div>
                <div class="form-group">
                    <label for="daily_rate">Tagessatz (€)</label>
                    <input type="number" id="daily_rate" name="daily_rate"
                           value="<?php echo $expert->daily_rate ?? ''; ?>"
                           step="50" min="0" placeholder="0">
                </div>
                <div class="form-group">
                    <label for="meta_weekly_hours">Verfügbare Std./Woche</label>
                    <input type="number" id="meta_weekly_hours" name="meta[weekly_hours]"
                           value="<?php echo $esc->escape($meta['weekly_hours'] ?? ''); ?>"
                           min="1" max="60" placeholder="z.B. 40">
                </div>
            </div>

            <!-- Projektdauer -->
            <div class="form-row">
                <div class="form-group">
                    <label for="meta_min_proj_dur">Min. Projektdauer</label>
                    <input type="text" id="meta_min_proj_dur" name="meta[min_project_duration]"
                           value="<?php echo $esc->escape($meta['min_project_duration'] ?? ''); ?>"
                           placeholder="z.B. 1 Monat">
                </div>
                <div class="form-group">
                    <label for="meta_max_proj_dur">Max. Projektdauer</label>
                    <input type="text" id="meta_max_proj_dur" name="meta[max_project_duration]"
                           value="<?php echo $esc->escape($meta['max_project_duration'] ?? ''); ?>"
                           placeholder="z.B. 12 Monate">
                </div>
                <div class="form-group">
                    <label for="meta_min_booking">Mindestbuchungsdauer</label>
                    <input type="text" id="meta_min_booking" name="meta[min_booking_duration]"
                           value="<?php echo $esc->escape($meta['min_booking_duration'] ?? ''); ?>"
                           placeholder="z.B. 1 Woche">
                </div>
            </div>

            <!-- Zahlungskonditionen -->
            <div class="form-row">
                <div class="form-group">
                    <label for="meta_payment_terms">Zahlungsziel</label>
                    <select id="meta_payment_terms" name="meta[payment_terms]">
                        <?php foreach ($pay_options as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo ($meta['payment_terms'] ?? '') === $val ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="align-self:flex-end;padding-bottom:.5rem;">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                        <input type="checkbox" name="meta[fixed_price_projects]" value="1"
                               <?php echo !empty($meta['fixed_price_projects']) ? 'checked' : ''; ?>>
                        Festpreis-Projekte möglich
                    </label>
                    <label style="display:flex;align-items:center;gap:.5rem;margin-top:.5rem;cursor:pointer;">
                        <input type="checkbox" name="meta[time_material]" value="1"
                               <?php echo !empty($meta['time_material']) ? 'checked' : ''; ?>>
                        Time &amp; Material möglich
                    </label>
                </div>
            </div>

            <!-- Reisekosten -->
            <div class="form-row">
                <div class="form-group">
                    <label for="meta_travel_cost_model">Reisekosten-Modell</label>
                    <select id="meta_travel_cost_model" name="meta[travel_cost_model]">
                        <?php foreach ($travel_options as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo ($meta['travel_cost_model'] ?? '') === $val ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="meta_max_travel_km">Max. Reisedistanz (km)</label>
                    <input type="number" id="meta_max_travel_km" name="meta[max_travel_distance_km]"
                           value="<?php echo $esc->escape($meta['max_travel_distance_km'] ?? ''); ?>"
                           min="0" placeholder="z.B. 150">
                </div>
            </div>

            <!-- Bevorzugte Unternehmensgrößen -->
            <div class="form-group">
                <label>Bevorzugte Unternehmensgrößen</label>
                <div style="display:flex;flex-wrap:wrap;gap:.75rem;margin-top:.4rem;">
                    <?php foreach ($size_options as $val => $label): ?>
                        <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;background:#f8fafc;padding:.35rem .75rem;border-radius:6px;border:1px solid #e2e8f0;">
                            <input type="checkbox" name="meta[preferred_company_sizes][]" value="<?php echo $val; ?>"
                                   <?php echo in_array($val, $pref_sizes, true) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_biography($expert): void
    {
        ?>
        <div class="form-section">
            <h3>📄 Über mich</h3>
            <div class="form-group">
                <label>Biografie / Kurzbeschreibung</label>
                <?php
                if (class_exists('\CMS\Services\EditorService')) {
                    echo \CMS\Services\EditorService::getInstance()->render(
                        'biography',
                        $expert->biography ?? '',
                        ['height' => 280]
                    );
                } else {
                    echo '<textarea id="biography" name="biography" rows="7" placeholder="Beschreiben Sie den Experten in wenigen Sätzen…">'
                        . CMS\Security::instance()->escape($expert->biography ?? '')
                        . '</textarea>';
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Fachrichtungen / Spezialisierungen – Checkboxen, kein Limit
     */
    private function render_fachrichtungen(array $all_specs, array $spec_ids): void
    {
        if (empty($all_specs)) {
            ?>
            <div class="form-section">
                <h3>🎯 Fachrichtungen / Spezialisierungen</h3>
                <p style="color:#9ca3af;font-size:0.875rem;">
                    Es sind noch keine Fachrichtungen angelegt. Das Plugin befüllt diese beim ersten Aufruf automatisch.
                </p>
            </div>
            <?php
            return;
        }

        // Gruppierung: Top-Level → Children
        $top_level = [];
        $children  = [];
        foreach ($all_specs as $spec) {
            if (empty($spec->parent_id)) {
                $top_level[$spec->id] = $spec;
            } else {
                $children[(int)$spec->parent_id][] = $spec;
            }
        }
        ?>
        <div class="form-section">
            <h3>🎯 Fachrichtungen / Spezialisierungen</h3>
            <p class="field-hint" style="margin:0 0 1rem;">
                Alle zutreffenden Fachrichtungen wählen – beliebig viele.
            </p>
            <div class="spec-grid">
                <?php foreach ($top_level as $top): ?>
                    <div class="spec-group">
                        <div class="spec-group-label"><?php echo htmlspecialchars((string)$top->name); ?></div>
                        <?php
                        // Top-Level selbst ist auch wählbar
                        $topChecked = in_array((int)$top->id, $spec_ids, true);
                        ?>
                        <label class="spec-item spec-item--parent">
                            <input type="checkbox" name="spec_ids[]"
                                   value="<?php echo (int)$top->id; ?>"
                                   <?php echo $topChecked ? 'checked' : ''; ?>>
                            <span><?php echo htmlspecialchars((string)$top->name); ?></span>
                        </label>
                        <?php if (!empty($children[(int)$top->id])): ?>
                            <div class="spec-children">
                                <?php foreach ($children[(int)$top->id] as $child): ?>
                                    <?php $chk = in_array((int)$child->id, $spec_ids, true); ?>
                                    <label class="spec-item">
                                        <input type="checkbox" name="spec_ids[]"
                                               value="<?php echo (int)$child->id; ?>"
                                               <?php echo $chk ? 'checked' : ''; ?>>
                                        <span><?php echo htmlspecialchars((string)$child->name); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Skills – drei getrennte Tag-Eingaben mit Vorlagen-Auswahl
     */
    private function render_skills(array $skills, array $presets = []): void
    {
        $esc = CMS\Security::instance();

        $sections = [
            'general' => [
                'label'       => 'Programmierung',
                'placeholder' => 'z.B. PHP, Python, JavaScript, C# …',
                'hint'        => 'Programmiersprachen & Grundlagen',
            ],
            'tech' => [
                'label'       => 'Skills',
                'placeholder' => 'z.B. Docker, React, AWS, VMware …',
                'hint'        => 'Frameworks, Tools & Plattformen',
            ],
            'soft' => [
                'label'       => 'Persönliche Stärken',
                'placeholder' => 'z.B. Teamwork, Kommunikation, Führung …',
                'hint'        => 'Soft Skills & methodische Kompetenzen',
            ],
        ];
        ?>
        <div class="form-section">
            <h3>🛠️ Skills & Kompetenzen</h3>
            <p class="field-hint" style="margin:0 0 1rem;">
                Klicke auf eine Vorlage, um sie hinzuzufügen, oder gib eigene Skills als Komma-getrennte Liste ein.
            </p>

            <?php foreach ($sections as $type => $cfg):
                $currentSkills = $skills[$type] ?? [];
                $typePresets   = $presets[$type] ?? [];
            ?>
            <div class="form-group" <?php echo $type !== 'general' ? 'style="margin-top:1.25rem;"' : ''; ?>>
                <label><?php echo $cfg['label']; ?> <small style="font-weight:400;color:#6b7280;">(<?php echo $cfg['hint']; ?>)</small></label>
                <div class="tag-input-wrapper" data-target="skills_<?php echo $type; ?>_hidden" data-skill-type="<?php echo $type; ?>">
                    <div class="tag-pills" id="tags_<?php echo $type; ?>"></div>
                    <input type="text" class="tag-text-input" placeholder="<?php echo htmlspecialchars($cfg['placeholder']); ?>">
                </div>
                <input type="hidden" name="skills_<?php echo $type; ?>" id="skills_<?php echo $type; ?>_hidden"
                       value="<?php echo $esc->escape(implode(',', $currentSkills)); ?>">
                <?php if (!empty($typePresets)): ?>
                <div class="skill-presets-box" data-preset-type="<?php echo $type; ?>">
                    <small style="color:#64748b;font-weight:600;display:block;margin-bottom:.35rem;">📋 Vorlagen:</small>
                    <div class="skill-presets-list">
                        <?php foreach ($typePresets as $preset): ?>
                        <button type="button" class="skill-preset-btn" data-preset-name="<?php echo htmlspecialchars($preset->skill_name, ENT_QUOTES); ?>" data-preset-target="skills_<?php echo $type; ?>_hidden">
                            + <?php echo htmlspecialchars($preset->skill_name); ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Online-Präsenz / Social Links
     */
    private function render_social_links(array $meta): void
    {
        $esc   = CMS\Security::instance();
        $links = [
            'social_linkedin'      => ['label' => 'LinkedIn',            'icon' => '🔗', 'ph' => 'https://linkedin.com/in/…'],
            'social_xing'          => ['label' => 'Xing',                'icon' => '🔗', 'ph' => 'https://xing.com/profile/…'],
            'social_github'        => ['label' => 'GitHub',              'icon' => '🐙', 'ph' => 'https://github.com/…'],
            'social_gitlab'        => ['label' => 'GitLab',              'icon' => '🦊', 'ph' => 'https://gitlab.com/…'],
            'social_stackoverflow' => ['label' => 'Stack Overflow',      'icon' => '📚', 'ph' => 'https://stackoverflow.com/users/…'],
            'social_twitter'       => ['label' => 'Twitter / X',         'icon' => '🐦', 'ph' => 'https://twitter.com/…'],
            'social_youtube'       => ['label' => 'YouTube-Kanal',       'icon' => '▶️', 'ph' => 'https://youtube.com/@…'],
            'social_blog_rss'      => ['label' => 'Blog / RSS-Feed',     'icon' => '📰', 'ph' => 'https://meinblog.de/feed'],
            'social_website'       => ['label' => 'Persönliche Website', 'icon' => '🌐', 'ph' => 'https://…'],
        ];
        ?>
        <div class="form-section">
            <h3>🌐 Online-Präsenz</h3>
            <div class="social-links-grid">
                <?php foreach ($links as $key => $cfg): ?>
                    <div class="form-group">
                        <label for="meta_<?php echo $key; ?>">
                            <?php echo $cfg['icon']; ?> <?php echo htmlspecialchars($cfg['label']); ?>
                        </label>
                        <input type="url" id="meta_<?php echo $key; ?>"
                               name="meta[<?php echo $key; ?>]"
                               placeholder="<?php echo htmlspecialchars($cfg['ph']); ?>"
                               value="<?php echo $esc->escape($meta[$key] ?? ''); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-group" style="margin-top:1rem;">
                <label for="meta_contact_times">🕐 Erreichbarkeitszeiten</label>
                <input type="text" id="meta_contact_times" name="meta[contact_times]"
                       placeholder="z.B. Mo–Fr 9–17 Uhr, bevorzugt E-Mail"
                       value="<?php echo $esc->escape($meta['contact_times'] ?? ''); ?>">
            </div>
        </div>
        <?php
    }

    /**
     * Partner-Status (Radio-Auswahl)
     */
    private function render_partner_status(array $meta): void
    {
        $current  = $meta['partner_status'] ?? 'none';
        $statuses = [
            'none'        => ['icon' => '—',  'label' => 'Kein Status',  'color' => '#9ca3af'],
            'partner'     => ['icon' => '🥈', 'label' => 'Partner',      'color' => '#9ca3af'],
            'top_partner' => ['icon' => '🥇', 'label' => 'Top-Partner',  'color' => '#d97706'],
            'sponsor'     => ['icon' => '🏆', 'label' => 'Sponsor',      'color' => '#7c3aed'],
        ];
        $esc2 = CMS\Security::instance();
        ?>
        <div class="form-section">
            <h3>🏅 Partner-Status & Zertifizierungen</h3>
            <p class="field-hint" style="margin:0 0 1rem;">
                Legt die visuelle Hervorhebung der Experten-Karte fest (Gold-/Silber-/Platin-Rahmen).
            </p>
            <div class="partner-status-grid">
                <?php foreach ($statuses as $val => $cfg): ?>
                    <label class="partner-status-option <?php echo $current === $val ? 'is-active' : ''; ?>">
                        <input type="radio" name="meta[partner_status]" value="<?php echo $val; ?>"
                               <?php echo $current === $val ? 'checked' : ''; ?>>
                        <span class="partner-status-label" style="color:<?php echo $cfg['color']; ?>;">
                            <?php echo $cfg['icon']; ?> <?php echo htmlspecialchars($cfg['label']); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Zertifizierungs-Badges -->
            <div style="margin-top:1.25rem;">
                <label style="display:block;font-weight:600;margin-bottom:.6rem;">🏆 Profil-Badges</label>
                <div style="display:flex;flex-wrap:wrap;gap:.75rem;">
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;background:#fef3c7;border:1px solid #f59e0b;padding:.35rem .85rem;border-radius:6px;">
                        <input type="checkbox" name="meta[is_mvp]" value="1"
                               <?php echo !empty($meta['is_mvp']) ? 'checked' : ''; ?>>
                        ⭐ MVP
                    </label>
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;background:#dbeafe;border:1px solid #3b82f6;padding:.35rem .85rem;border-radius:6px;">
                        <input type="checkbox" name="meta[is_certified]" value="1"
                               <?php echo !empty($meta['is_certified']) ? 'checked' : ''; ?>>
                        ✅ Zertifiziert
                    </label>
                    <label style="display:flex;align-items:center;gap:.4rem;cursor:pointer;background:#f3e8ff;border:1px solid #a855f7;padding:.35rem .85rem;border-radius:6px;">
                        <input type="checkbox" name="meta[is_premium]" value="1"
                               <?php echo !empty($meta['is_premium']) ? 'checked' : ''; ?>>
                        💎 Premium
                    </label>
                </div>
            </div>

            <!-- Custom Award -->
            <div class="form-group" style="margin-top:1rem;max-width:360px;">
                <label for="meta_custom_award">🎖️ Eigener Badge-Text <small style="font-weight:400;color:#6b7280;">(optional)</small></label>
                <input type="text" id="meta_custom_award" name="meta[custom_award]"
                       placeholder="z.B. Top-Berater 2024"
                       value="<?php echo $esc2->escape($meta['custom_award'] ?? ''); ?>">
            </div>
        </div>
        <?php
    }

    // ─── Weitere Angaben ──────────────────────────────────────────────────────

    /**
     * Sprachen, Remote-Arbeit, Reisebereitschaft, Kündigungsfrist
     */
    private function render_additional_info(array $meta): void
    {
        $esc              = CMS\Security::instance();
        $languages        = $meta['languages']          ?? '';
        $remote_work      = $meta['remote_work']        ?? '';
        $notice_period    = $meta['notice_period']      ?? '';
        $travel           = $meta['travel_willingness'] ?? '';
        ?>
        <div class="form-section">
            <h3>🌍 Weitere Angaben</h3>

            <div class="form-row">
                <!-- Sprachen -->
                <div class="form-group">
                    <label>Sprachen <small style="font-weight:400;color:#6b7280;">(Komma-getrennt)</small></label>
                    <div class="tag-input-wrapper" data-target="languages_hidden">
                        <div class="tag-pills" id="tags_languages"></div>
                        <input type="text" class="tag-text-input"
                               placeholder="z.B. Deutsch, Englisch, Französisch …">
                    </div>
                    <input type="hidden" name="meta[languages]" id="languages_hidden"
                           value="<?php echo $esc->escape($languages); ?>">
                </div>

                <!-- Kündigungsfrist / Verfügbar ab -->
                <div class="form-group">
                    <label for="meta_notice_period">Verfügbar ab / Kündigungsfrist</label>
                    <select id="meta_notice_period" name="meta[notice_period]">
                        <?php
                        foreach ([
                            ''               => '— nicht angegeben —',
                            'sofort'         => '✅ Sofort verfügbar',
                            '2_wochen'       => '📅 2 Wochen',
                            '4_wochen'       => '📅 4 Wochen',
                            '3_monate'       => '📅 3 Monate',
                            'nach_absprache' => '💬 Nach Absprache',
                        ] as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo $notice_period === $val ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <!-- Remote-Arbeit -->
                <div class="form-group">
                    <label>Remote-Arbeit</label>
                    <div class="radio-group" style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:0.25rem;">
                        <?php
                        foreach ([
                            'no'        => '🏢 Kein Remote',
                            'partial'   => '🔀 Teilweise',
                            'full'      => '🏠 Vollständig',
                            'preferred' => '⭐ Bevorzugt',
                        ] as $val => $label): ?>
                        <label style="display:flex;align-items:center;gap:0.35rem;font-weight:400;cursor:pointer;">
                            <input type="radio" name="meta[remote_work]" value="<?php echo $val; ?>"
                                   <?php echo $remote_work === $val ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Reisebereitschaft -->
                <div class="form-group">
                    <label for="meta_travel">Reisebereitschaft</label>
                    <select id="meta_travel" name="meta[travel_willingness]">
                        <?php
                        foreach ([
                            ''          => '— nicht angegeben —',
                            'local'     => '📍 Nur lokal (< 50 km)',
                            'regional'  => '🚗 Regional (50–200 km)',
                            'national'  => '✈️ National',
                            'europe'    => '🌍 Europa',
                            'worldwide' => '🌐 Weltweit',
                        ] as $val => $label): ?>
                            <option value="<?php echo $val; ?>" <?php echo $travel === $val ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php
    }

    // ─── Zertifikate (nur Edit-Mode) ──────────────────────────────────────────

    private function render_certifications(array $items): void
    {
        ?>
        <div class="form-section" id="section-certifications">
            <h3>🏆 Zertifikate</h3>
            <p class="field-hint" style="margin-bottom:1rem;">Fachliche Zertifizierungen und Nachweise.</p>
            <div id="cert-list">
                <?php foreach ($items as $i => $c): ?>
                <div class="sub-item-row" data-cert-row>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Bezeichnung *</label>
                            <input type="text" name="certifications[<?= $i ?>][cert_name]"
                                   value="<?= htmlspecialchars($c->cert_name ?? '') ?>" placeholder="z.B. AWS Certified Developer">
                        </div>
                        <div class="form-group">
                            <label>Aussteller</label>
                            <input type="text" name="certifications[<?= $i ?>][cert_issuer]"
                                   value="<?= htmlspecialchars($c->cert_issuer ?? '') ?>" placeholder="z.B. Amazon Web Services">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Ausstellungsdatum</label>
                            <input type="date" name="certifications[<?= $i ?>][cert_date]"
                                   value="<?= htmlspecialchars($c->cert_date ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Ablaufdatum</label>
                            <input type="date" name="certifications[<?= $i ?>][cert_expiry]"
                                   value="<?= htmlspecialchars($c->cert_expiry ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Zertifikat-URL</label>
                            <input type="url" name="certifications[<?= $i ?>][cert_url]"
                                   value="<?= htmlspecialchars($c->cert_url ?? '') ?>" placeholder="https://…">
                        </div>
                    </div>
                    <button type="button" class="sub-item-remove-btn" onclick="this.closest('[data-cert-row]').remove()">✕ Entfernen</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="sub-item-add-btn" id="add-cert-btn">+ Zertifikat hinzufügen</button>
        </div>
        <script>
        (function(){
            let certIdx = <?= count($items) ?>;
            document.getElementById('add-cert-btn').addEventListener('click', function(){
                const tpl = `<div class="sub-item-row" data-cert-row>
                    <div class="form-row">
                        <div class="form-group"><label>Bezeichnung *</label>
                            <input type="text" name="certifications[${certIdx}][cert_name]" placeholder="z.B. AWS Certified Developer">
                        </div>
                        <div class="form-group"><label>Aussteller</label>
                            <input type="text" name="certifications[${certIdx}][cert_issuer]" placeholder="z.B. Amazon Web Services">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Ausstellungsdatum</label>
                            <input type="date" name="certifications[${certIdx}][cert_date]">
                        </div>
                        <div class="form-group"><label>Ablaufdatum</label>
                            <input type="date" name="certifications[${certIdx}][cert_expiry]">
                        </div>
                        <div class="form-group"><label>Zertifikat-URL</label>
                            <input type="url" name="certifications[${certIdx}][cert_url]" placeholder="https://…">
                        </div>
                    </div>
                    <button type="button" class="sub-item-remove-btn" onclick="this.closest('[data-cert-row]').remove()">✕ Entfernen</button>
                </div>`;
                document.getElementById('cert-list').insertAdjacentHTML('beforeend', tpl);
                certIdx++;
            });
        })();
        </script>
        <?php
    }

    // ─── Projekte (nur Edit-Mode) ─────────────────────────────────────────────

    private function render_projects(array $items): void
    {
        ?>
        <div class="form-section" id="section-projects">
            <h3>📁 Projekte</h3>
            <p class="field-hint" style="margin-bottom:1rem;">Referenzprojekte und Erfahrungen.</p>
            <div id="project-list">
                <?php foreach ($items as $i => $p): ?>
                <div class="sub-item-row" data-project-row>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Projektname *</label>
                            <input type="text" name="projects[<?= $i ?>][project_name]"
                                   value="<?= htmlspecialchars($p->project_name ?? '') ?>" placeholder="z.B. E-Commerce Relaunch">
                        </div>
                        <div class="form-group">
                            <label>Rolle</label>
                            <input type="text" name="projects[<?= $i ?>][project_role]"
                                   value="<?= htmlspecialchars($p->project_role ?? '') ?>" placeholder="z.B. Backend-Entwickler">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Start</label>
                            <input type="date" name="projects[<?= $i ?>][project_start]"
                                   value="<?= htmlspecialchars($p->project_start ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Ende</label>
                            <input type="date" name="projects[<?= $i ?>][project_end]"
                                   value="<?= htmlspecialchars($p->project_end ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Projekt-URL</label>
                            <input type="url" name="projects[<?= $i ?>][project_url]"
                                   value="<?= htmlspecialchars($p->project_url ?? '') ?>" placeholder="https://…">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Beschreibung</label>
                        <textarea name="projects[<?= $i ?>][project_description]" rows="3"
                                  placeholder="Kurze Projektbeschreibung..."><?= htmlspecialchars($p->project_description ?? '') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Technologien (kommagetrennt)</label>
                        <input type="text" name="projects[<?= $i ?>][technologies]"
                               value="<?= htmlspecialchars($p->technologies ?? '') ?>" placeholder="z.B. PHP, Laravel, MySQL">
                    </div>
                    <button type="button" class="sub-item-remove-btn" onclick="this.closest('[data-project-row]').remove()">✕ Entfernen</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="sub-item-add-btn" id="add-project-btn">+ Projekt hinzufügen</button>
        </div>
        <script>
        (function(){
            let projIdx = <?= count($items) ?>;
            document.getElementById('add-project-btn').addEventListener('click', function(){
                const tpl = `<div class="sub-item-row" data-project-row>
                    <div class="form-row">
                        <div class="form-group"><label>Projektname *</label>
                            <input type="text" name="projects[${projIdx}][project_name]" placeholder="z.B. E-Commerce Relaunch">
                        </div>
                        <div class="form-group"><label>Rolle</label>
                            <input type="text" name="projects[${projIdx}][project_role]" placeholder="z.B. Backend-Entwickler">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Start</label>
                            <input type="date" name="projects[${projIdx}][project_start]">
                        </div>
                        <div class="form-group"><label>Ende</label>
                            <input type="date" name="projects[${projIdx}][project_end]">
                        </div>
                        <div class="form-group"><label>Projekt-URL</label>
                            <input type="url" name="projects[${projIdx}][project_url]" placeholder="https://…">
                        </div>
                    </div>
                    <div class="form-group"><label>Beschreibung</label>
                        <textarea name="projects[${projIdx}][project_description]" rows="3" placeholder="Kurze Projektbeschreibung..."></textarea>
                    </div>
                    <div class="form-group"><label>Technologien (kommagetrennt)</label>
                        <input type="text" name="projects[${projIdx}][technologies]" placeholder="z.B. PHP, Laravel, MySQL">
                    </div>
                    <button type="button" class="sub-item-remove-btn" onclick="this.closest('[data-project-row]').remove()">✕ Entfernen</button>
                </div>`;
                document.getElementById('project-list').insertAdjacentHTML('beforeend', tpl);
                projIdx++;
            });
        })();
        </script>
        <?php
    }

    // ─── Ausbildung (nur Edit-Mode) ───────────────────────────────────────────

    private function render_education(array $items): void
    {
        ?>
        <div class="form-section" id="section-education">
            <h3>🎓 Ausbildung</h3>
            <p class="field-hint" style="margin-bottom:1rem;">Abschlüsse, Studium und Weiterbildungen.</p>
            <div id="edu-list">
                <?php foreach ($items as $i => $e): ?>
                <div class="sub-item-row" data-edu-row>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Abschluss / Titel *</label>
                            <input type="text" name="education[<?= $i ?>][degree]"
                                   value="<?= htmlspecialchars($e->degree ?? '') ?>" placeholder="z.B. Bachelor of Science">
                        </div>
                        <div class="form-group">
                            <label>Institution</label>
                            <input type="text" name="education[<?= $i ?>][institution]"
                                   value="<?= htmlspecialchars($e->institution ?? '') ?>" placeholder="z.B. TU München">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Studiengang / Fach</label>
                            <input type="text" name="education[<?= $i ?>][field_of_study]"
                                   value="<?= htmlspecialchars($e->field_of_study ?? '') ?>" placeholder="z.B. Informatik">
                        </div>
                        <div class="form-group">
                            <label>Von (Jahr)</label>
                            <input type="number" name="education[<?= $i ?>][start_year]" min="1950" max="2099" step="1"
                                   value="<?= !empty($e->start_year) ? (int)$e->start_year : '' ?>" placeholder="2018">
                        </div>
                        <div class="form-group">
                            <label>Bis (Jahr)</label>
                            <input type="number" name="education[<?= $i ?>][end_year]" min="1950" max="2099" step="1"
                                   value="<?= !empty($e->end_year) ? (int)$e->end_year : '' ?>" placeholder="2022">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Anmerkungen</label>
                        <textarea name="education[<?= $i ?>][description]" rows="2"
                                  placeholder="Optional: Schwerpunkte, Auszeichnungen..."><?= htmlspecialchars($e->description ?? '') ?></textarea>
                    </div>
                    <button type="button" class="sub-item-remove-btn" onclick="this.closest('[data-edu-row]').remove()">✕ Entfernen</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="sub-item-add-btn" id="add-edu-btn">+ Ausbildung hinzufügen</button>
        </div>
        <script>
        (function(){
            let eduIdx = <?= count($items) ?>;
            document.getElementById('add-edu-btn').addEventListener('click', function(){
                const tpl = `<div class="sub-item-row" data-edu-row>
                    <div class="form-row">
                        <div class="form-group"><label>Abschluss / Titel *</label>
                            <input type="text" name="education[${eduIdx}][degree]" placeholder="z.B. Bachelor of Science">
                        </div>
                        <div class="form-group"><label>Institution</label>
                            <input type="text" name="education[${eduIdx}][institution]" placeholder="z.B. TU München">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Studiengang / Fach</label>
                            <input type="text" name="education[${eduIdx}][field_of_study]" placeholder="z.B. Informatik">
                        </div>
                        <div class="form-group"><label>Von (Jahr)</label>
                            <input type="number" name="education[${eduIdx}][start_year]" min="1950" max="2099" step="1" placeholder="2018">
                        </div>
                        <div class="form-group"><label>Bis (Jahr)</label>
                            <input type="number" name="education[${eduIdx}][end_year]" min="1950" max="2099" step="1" placeholder="2022">
                        </div>
                    </div>
                    <div class="form-group"><label>Anmerkungen</label>
                        <textarea name="education[${eduIdx}][description]" rows="2" placeholder="Optional: ..."></textarea>
                    </div>
                    <button type="button" class="sub-item-remove-btn" onclick="this.closest('[data-edu-row]').remove()">✕ Entfernen</button>
                </div>`;
                document.getElementById('edu-list').insertAdjacentHTML('beforeend', tpl);
                eduIdx++;
            });
        })();
        </script>
        <?php
    }

    // ─── Validierung ──────────────────────────────────────────────────────────

    public function validate_expert_data(array $data): array
    {
        $errors = [];
        if (empty(trim($data['first_name'] ?? ''))) {
            $errors[] = 'Vorname ist erforderlich.';
        }
        if (empty(trim($data['last_name'] ?? ''))) {
            $errors[] = 'Nachname ist erforderlich.';
        }
        if (empty($data['email'])) {
            $errors[] = 'E-Mail-Adresse ist erforderlich.';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-Mail-Adresse ist ungültig.';
        }
        return $errors;
    }

    // ─── CSS & JS (inline im Admin-Formular) ─────────────────────────────────

    private function render_form_styles(): void
    {
        ?>
        <style>
        .form-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-section h3 {
            margin: 0 0 1.25rem;
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-row:last-child { margin-bottom: 0; }
        .form-group { display: flex; flex-direction: column; gap: 0.375rem; }
        /* Company-Selector */
        .company-select-wrap { display: flex; flex-direction: column; gap: 0.5rem; }
        .company-select-wrap select { width: 100%; }
        .btn-company-new {
            display: inline-flex; align-items: center; gap: 0.375rem;
            font-size: 0.8125rem; font-weight: 600;
            color: #2563eb; text-decoration: none;
            padding: 0.35rem 0.75rem;
            border: 1px dashed #93c5fd;
            border-radius: 6px;
            background: #eff6ff;
            transition: background 0.15s, border-color 0.15s;
            align-self: flex-start;
        }
        .btn-company-new:hover { background: #dbeafe; border-color: #3b82f6; color: #1d4ed8; }
        .company-legacy-hint {
            font-size: 0.75rem; color: #6b7280;
            background: #fef9c3; border: 1px solid #fde68a;
            border-radius: 4px; padding: 0.35rem 0.625rem;
            margin-top: 0.25rem;
        }
        .form-group label { font-size: 0.8125rem; font-weight: 600; color: #374151; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.875rem;
            font-family: inherit;
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
            box-sizing: border-box;
            width: 100%;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }
        .form-group textarea { resize: vertical; }
        .field-hint { font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem; display: block; }

        /* Fachrichtungen */
        .spec-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 0.875rem;
        }
        .spec-group { border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
        .spec-group-label {
            padding: 0.45rem 0.75rem;
            background: #f8fafc;
            font-size: 0.72rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid #e2e8f0;
        }
        .spec-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            cursor: pointer;
            transition: background 0.1s;
        }
        .spec-item:hover { background: #f8fafc; }
        .spec-item input[type="checkbox"] { width: 14px; height: 14px; accent-color: #3b82f6; flex-shrink: 0; }
        .spec-item--parent { font-weight: 600; color: #374151; border-bottom: 1px solid #f1f5f9; }
        .spec-children { padding-left: 0.75rem; background: #fafafa; }

        /* Tag-Input */
        .tag-input-wrapper {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.375rem;
            padding: 0.4rem 0.625rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            min-height: 42px;
            background: #fff;
            cursor: text;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .tag-input-wrapper:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
        }
        .tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.45rem 0.2rem 0.6rem;
            background: #dbeafe;
            color: #1d4ed8;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 500;
            white-space: nowrap;
        }
        .tag-pill-remove {
            width: 14px; height: 14px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%; background: #93c5fd; color: #1e40af;
            font-size: 0.6rem; cursor: pointer; font-weight: 700;
            transition: background 0.1s;
        }
        .tag-pill-remove:hover { background: #3b82f6; color: #fff; }
        .tag-text-input {
            border: none !important; outline: none !important;
            padding: 0.1rem 0.25rem !important; min-width: 100px;
            flex: 1; font-size: 0.875rem !important;
            background: transparent !important; box-shadow: none !important;
        }

        /* Skill-Preset-Vorlagen */
        .skill-presets-box {
            margin-top: 0.5rem;
            padding: 0.625rem 0.75rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
        }
        .skill-presets-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.375rem;
        }
        .skill-preset-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            padding: 0.22rem 0.6rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: #4338ca;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.15s;
            font-family: inherit;
        }
        .skill-preset-btn:hover {
            background: #dbeafe;
            border-color: #818cf8;
            color: #3730a3;
        }
        .skill-preset-btn.is-added {
            background: #d1fae5;
            border-color: #86efac;
            color: #065f46;
            opacity: 0.7;
            cursor: default;
            pointer-events: none;
        }

        /* Social Links */
        .social-links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
            gap: 1rem;
        }

        /* Partner-Status */
        .partner-status-grid { display: flex; flex-wrap: wrap; gap: 0.625rem; }
        .partner-status-option {
            display: flex; align-items: center; gap: 0.5rem;
            padding: 0.5rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 7px; cursor: pointer;
            background: #fff; transition: all 0.15s;
        }
        .partner-status-option:hover { border-color: #93c5fd; background: #eff6ff; }
        .partner-status-option.is-active { border-color: #3b82f6; background: #eff6ff; }
        .partner-status-option input[type="radio"] { display: none; }
        .partner-status-label { font-size: 0.875rem; font-weight: 600; }

        /* Sub-Item Rows (Zertifikate, Projekte, Ausbildung) */
        .sub-item-row {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            position: relative;
        }
        .sub-item-row:last-child { margin-bottom: 0; }
        .sub-item-remove-btn {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .3rem .7rem;
            font-size: .75rem; font-weight: 600;
            color: #dc2626; background: #fef2f2;
            border: 1px solid #fca5a5; border-radius: 5px;
            cursor: pointer; margin-top: .5rem;
            transition: background .15s, border-color .15s;
        }
        .sub-item-remove-btn:hover { background: #fee2e2; border-color: #f87171; }
        .sub-item-add-btn {
            display: inline-flex; align-items: center; gap: .3rem;
            padding: .4rem .85rem;
            font-size: .8125rem; font-weight: 600;
            color: #2563eb; background: #eff6ff;
            border: 1px dashed #93c5fd; border-radius: 6px;
            cursor: pointer; margin-top: .75rem;
            transition: background .15s, border-color .15s;
        }
        .sub-item-add-btn:hover { background: #dbeafe; border-color: #3b82f6; }
        </style>

        <script>
        (function () {
            /* Tag-Input ─────────────────────────────────────────────── */
            function initTagInput(wrapper) {
                const pillsEl   = wrapper.querySelector('.tag-pills');
                const textEl    = wrapper.querySelector('.tag-text-input');
                const hiddenEl  = document.getElementById(wrapper.dataset.target);
                if (!pillsEl || !textEl || !hiddenEl) return;

                let tags = hiddenEl.value
                    ? hiddenEl.value.split(',').map(t => t.trim()).filter(Boolean)
                    : [];

                function sync() {
                    hiddenEl.value = tags.join(',');
                    syncPresetButtons(wrapper.dataset.target);
                }

                function renderPills() {
                    pillsEl.replaceChildren();
                    tags.forEach((tag, i) => {
                        const pill = document.createElement('span');
                        pill.className = 'tag-pill';
                        const txt = document.createElement('span');
                        txt.textContent = tag;
                        const rm = document.createElement('span');
                        rm.className = 'tag-pill-remove';
                        rm.textContent = '✕';
                        rm.addEventListener('click', () => { tags.splice(i, 1); renderPills(); });
                        pill.appendChild(txt);
                        pill.appendChild(rm);
                        pillsEl.appendChild(pill);
                    });
                    sync();
                }

                function addTag(raw) {
                    raw.split(',').forEach(v => {
                        v = v.trim();
                        if (v && !tags.includes(v)) tags.push(v);
                    });
                    renderPills();
                    textEl.value = '';
                }

                // Expose addTag on the wrapper for preset buttons
                wrapper._addTag = addTag;
                wrapper._getTags = () => tags;

                textEl.addEventListener('keydown', e => {
                    if (e.key === 'Enter' || e.key === ',') {
                        e.preventDefault();
                        const v = textEl.value.replace(/,$/, '').trim();
                        if (v) addTag(v);
                    } else if (e.key === 'Backspace' && textEl.value === '' && tags.length) {
                        tags.pop(); renderPills();
                    }
                });
                textEl.addEventListener('blur', () => {
                    const v = textEl.value.replace(/,$/, '').trim();
                    if (v) addTag(v);
                });
                wrapper.addEventListener('click', () => textEl.focus());
                renderPills();
            }

            document.querySelectorAll('.tag-input-wrapper').forEach(initTagInput);

            /* Skill-Preset-Buttons (Vorlagen anklicken) ─────────────── */
            function syncPresetButtons(targetId) {
                const wrapper = document.querySelector('.tag-input-wrapper[data-target="' + targetId + '"]');
                if (!wrapper || !wrapper._getTags) return;
                const currentTags = wrapper._getTags().map(t => t.toLowerCase());
                // Finde passende Preset-Box
                const type = targetId.replace('skills_', '').replace('_hidden', '');
                const presetBox = document.querySelector('.skill-presets-box[data-preset-type="' + type + '"]');
                if (!presetBox) return;
                presetBox.querySelectorAll('.skill-preset-btn').forEach(btn => {
                    const name = btn.dataset.presetName;
                    if (currentTags.includes(name.toLowerCase())) {
                        btn.classList.add('is-added');
                        btn.textContent = '✓ ' + name;
                    } else {
                        btn.classList.remove('is-added');
                        btn.textContent = '+ ' + name;
                    }
                });
            }

            document.querySelectorAll('.skill-preset-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.classList.contains('is-added')) return;
                    const targetId = this.dataset.presetTarget;
                    const wrapper = document.querySelector('.tag-input-wrapper[data-target="' + targetId + '"]');
                    if (wrapper && wrapper._addTag) {
                        wrapper._addTag(this.dataset.presetName);
                    }
                });
            });

            // Initial sync of preset button states
            document.querySelectorAll('.skill-presets-box').forEach(box => {
                const type = box.dataset.presetType;
                syncPresetButtons('skills_' + type + '_hidden');
            });

            /* Partner-Status hervorheben ────────────────────────────── */
            document.querySelectorAll('.partner-status-option input[type="radio"]').forEach(r => {
                r.addEventListener('change', () => {
                    document.querySelectorAll('.partner-status-option').forEach(el => el.classList.remove('is-active'));
                    r.closest('.partner-status-option').classList.add('is-active');
                });
            });
        })();
        </script>
        <?php
    }

    // ─── Technische Expertise ─────────────────────────────────────────────────

    private function render_tech_expertise(array $meta): void
    {
        $esc = CMS\Security::instance();

        // JSON-Felder entpacken
        $decode = static function(string $key) use ($meta): array {
            $raw = $meta[$key] ?? '';
            if (is_array($raw)) return $raw;
            return json_decode((string)$raw, true) ?: [];
        };

        $prog_langs  = $decode('programming_languages');
        $frameworks  = $decode('frameworks');
        $databases   = $decode('databases');
        $cloud       = $decode('cloud_platforms');
        $tools       = $meta['tools_preferred']     ?? '';
        $industry    = $meta['industry_experience'] ?? '';

        $level_opts = ['' => '—', 'beginner' => 'Einsteiger', 'intermediate' => 'Fortgeschritten', 'advanced' => 'Erfahren', 'expert' => 'Experte'];

        // Hilfsfunktion: Repeater-Section für [{name, level}]
        $renderRepeater = function(string $fieldKey, string $jsId, array $items, string $namePh) use ($level_opts) {
            $json = htmlspecialchars(json_encode($items), ENT_QUOTES);
            ?>
            <div class="tech-repeater" id="<?php echo $jsId; ?>-wrapper" style="margin-bottom:1.25rem;">
                <input type="hidden" name="meta[<?php echo $fieldKey; ?>]" id="<?php echo $jsId; ?>-hidden" value="<?php echo $json; ?>">
                <div class="tech-repeater-rows" id="<?php echo $jsId; ?>-rows">
                    <?php foreach ($items as $i => $item): ?>
                    <div class="tech-row" style="display:flex;gap:.75rem;margin-bottom:.5rem;align-items:center;">
                        <input type="text" placeholder="<?php echo htmlspecialchars($namePh); ?>"
                               value="<?php echo htmlspecialchars($item['name'] ?? ''); ?>"
                               style="flex:1;" data-field="name">
                        <select style="width:150px;" data-field="level">
                            <?php foreach ($level_opts as $lv => $ll): ?>
                                <option value="<?php echo $lv; ?>" <?php echo ($item['level'] ?? '') === $lv ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ll); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" onclick="techRepeaterRemove(this, '<?php echo $jsId; ?>')" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1.1rem;">✕</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" onclick="techRepeaterAdd('<?php echo $jsId; ?>', '<?php echo htmlspecialchars($namePh, ENT_QUOTES); ?>')"
                        class="btn btn-sm btn-secondary" style="margin-top:.4rem;">+ Hinzufügen</button>
            </div>
            <?php
        };
        ?>
        <div class="form-section">
            <h3>💻 Technische Expertise</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                <div>
                    <label style="font-weight:600;display:block;margin-bottom:.5rem;">Programmiersprachen</label>
                    <?php $renderRepeater('programming_languages', 'prog-langs', $prog_langs, 'z.B. Python'); ?>
                </div>
                <div>
                    <label style="font-weight:600;display:block;margin-bottom:.5rem;">Frameworks & Bibliotheken</label>
                    <?php $renderRepeater('frameworks', 'tech-frameworks', $frameworks, 'z.B. React'); ?>
                </div>
                <div>
                    <label style="font-weight:600;display:block;margin-bottom:.5rem;">Datenbanken</label>
                    <?php $renderRepeater('databases', 'tech-databases', $databases, 'z.B. PostgreSQL'); ?>
                </div>
                <div>
                    <label style="font-weight:600;display:block;margin-bottom:.5rem;">Cloud-Plattformen</label>
                    <?php $renderRepeater('cloud_platforms', 'cloud-plats', $cloud, 'z.B. AWS'); ?>
                </div>
            </div>

            <div class="form-row" style="margin-top:1rem;">
                <div class="form-group">
                    <label>🔧 Bevorzugte Tools <small style="font-weight:400;color:#6b7280;">(Komma-getrennt)</small></label>
                    <div class="tag-input-wrapper" data-target="tools_preferred_hidden">
                        <div class="tag-pills" id="tags_tools_pref"></div>
                        <input type="text" class="tag-text-input" placeholder="z.B. Docker, Jira, Figma …">
                    </div>
                    <input type="hidden" name="meta[tools_preferred]" id="tools_preferred_hidden"
                           value="<?php echo $esc->escape($tools); ?>">
                </div>
                <div class="form-group">
                    <label>🏭 Branchenerfahrung <small style="font-weight:400;color:#6b7280;">(Komma-getrennt)</small></label>
                    <div class="tag-input-wrapper" data-target="industry_exp_hidden">
                        <div class="tag-pills" id="tags_industry_exp"></div>
                        <input type="text" class="tag-text-input" placeholder="z.B. FinTech, Healthcare, E-Commerce …">
                    </div>
                    <input type="hidden" name="meta[industry_experience]" id="industry_exp_hidden"
                           value="<?php echo $esc->escape($industry); ?>">
                </div>
            </div>
        </div>
        <?php
    }

    // ─── Berufliche Stationen ─────────────────────────────────────────────────

    private function render_career_stations_form(array $meta): void
    {
        $raw      = $meta['career_stations'] ?? '';
        $stations = is_array($raw) ? $raw : (json_decode((string)$raw, true) ?: []);
        $json_val = htmlspecialchars(json_encode($stations), ENT_QUOTES);
        ?>
        <div class="form-section">
            <h3>🏢 Karrierestationen</h3>
            <p class="field-hint" style="margin-bottom:1rem;">Lebenslaufartige Auflistung bisheriger Positionen.</p>
            <input type="hidden" name="meta[career_stations]" id="career-stations-hidden" value="<?php echo $json_val; ?>">
            <div id="career-stations-list">
                <?php foreach ($stations as $i => $s): ?>
                <div class="sub-item-row" data-station-row style="border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:.75rem;background:#fafafa;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Unternehmen *</label>
                            <input type="text" placeholder="Firmenname" value="<?php echo htmlspecialchars($s['company'] ?? ''); ?>" data-cs-field="company" data-cs-index="<?php echo $i; ?>">
                        </div>
                        <div class="form-group">
                            <label>Position / Rolle *</label>
                            <input type="text" placeholder="z.B. Senior Developer" value="<?php echo htmlspecialchars($s['position'] ?? ''); ?>" data-cs-field="position" data-cs-index="<?php echo $i; ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Von <small style="font-weight:400;color:#6b7280;">(YYYY-MM)</small></label>
                            <input type="text" placeholder="2020-01" value="<?php echo htmlspecialchars($s['from_date'] ?? ''); ?>" data-cs-field="from_date" data-cs-index="<?php echo $i; ?>">
                        </div>
                        <div class="form-group">
                            <label>Bis <small style="font-weight:400;color:#6b7280;">(leer = heute)</small></label>
                            <input type="text" placeholder="2022-12 oder leer" value="<?php echo htmlspecialchars($s['to_date'] ?? ''); ?>" data-cs-field="to_date" data-cs-index="<?php echo $i; ?>">
                        </div>
                        <div class="form-group">
                            <label>Ort / Remote</label>
                            <input type="text" placeholder="z.B. Berlin oder Remote" value="<?php echo htmlspecialchars($s['location'] ?? ''); ?>" data-cs-field="location" data-cs-index="<?php echo $i; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Beschreibung / Erfolge</label>
                        <textarea rows="3" placeholder="Aufgaben, Technologien, Erfolge …" data-cs-field="achievements" data-cs-index="<?php echo $i; ?>"><?php echo htmlspecialchars($s['achievements'] ?? ''); ?></textarea>
                    </div>
                    <button type="button" class="btn btn-sm btn-danger" onclick="csRemoveStation(this)">✕ Station entfernen</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-secondary" onclick="csAddStation()" style="margin-top:.5rem;">+ Station hinzufügen</button>
        </div>

        <script>
        (function(){
            function csSync() {
                const rows = document.querySelectorAll('[data-station-row]');
                const data = [];
                rows.forEach((row, i) => {
                    const entry = {};
                    row.querySelectorAll('[data-cs-field]').forEach(el => {
                        entry[el.dataset.csField] = el.value;
                    });
                    data.push(entry);
                });
                document.getElementById('career-stations-hidden').value = JSON.stringify(data);
            }
            document.getElementById('career-stations-list').addEventListener('input', csSync);
            window.csRemoveStation = function(btn) { btn.closest('[data-station-row]').remove(); csSync(); };
            window.csAddStation = function() {
                const idx = document.querySelectorAll('[data-station-row]').length;
                const tpl = `<div class="sub-item-row" data-station-row style="border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:.75rem;background:#fafafa;">
                    <div class="form-row">
                        <div class="form-group"><label>Unternehmen *</label><input type="text" placeholder="Firmenname" data-cs-field="company"></div>
                        <div class="form-group"><label>Position / Rolle *</label><input type="text" placeholder="z.B. Senior Developer" data-cs-field="position"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Von</label><input type="text" placeholder="2020-01" data-cs-field="from_date"></div>
                        <div class="form-group"><label>Bis</label><input type="text" placeholder="2022-12" data-cs-field="to_date"></div>
                        <div class="form-group"><label>Ort / Remote</label><input type="text" placeholder="Berlin oder Remote" data-cs-field="location"></div>
                    </div>
                    <div class="form-group"><label>Beschreibung / Erfolge</label><textarea rows="3" data-cs-field="achievements"></textarea></div>
                    <button type="button" class="btn btn-sm btn-danger" onclick="csRemoveStation(this)">✕ Station entfernen</button>
                </div>`;
                document.getElementById('career-stations-list').insertAdjacentHTML('beforeend', tpl);
                document.getElementById('career-stations-list').addEventListener('input', csSync);
            };
        })();
        </script>
        <?php
    }

    // ─── Referenzen & Portfolio ───────────────────────────────────────────────

    private function render_references(array $meta): void
    {
        $decode = static function(string $key) use ($meta): array {
            $raw = $meta[$key] ?? '';
            if (is_array($raw)) return $raw;
            return json_decode((string)$raw, true) ?: [];
        };

        $testimonials = $decode('testimonials');
        $case_studies = $decode('case_studies');
        $conf_talks   = $decode('conference_talks');

        // Helper: einfacher JSON-Repeater (label => field_key)
        $repeater = function(string $hiddenId, array $items, array $fields, string $btnLabel) {
            $jsonVal = htmlspecialchars(json_encode($items), ENT_QUOTES);
            $fieldsJson = htmlspecialchars(json_encode($fields), ENT_QUOTES);
            ?>
            <input type="hidden" id="<?php echo $hiddenId; ?>-hidden" value="<?php echo $jsonVal; ?>">
            <div id="<?php echo $hiddenId; ?>-list">
                <?php foreach ($items as $i => $item): ?>
                <div data-ref-row="<?php echo $hiddenId; ?>" style="border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:.75rem;background:#fafafa;">
                    <div class="form-row" style="flex-wrap:wrap;">
                        <?php foreach ($fields as $fieldKey => $label): ?>
                            <div class="form-group" <?php echo ($fieldKey === 'text' || $fieldKey === 'description') ? 'style="flex:100%;"' : ''; ?>>
                                <label><?php echo htmlspecialchars($label); ?></label>
                                <?php if ($fieldKey === 'text' || $fieldKey === 'description'): ?>
                                    <textarea rows="3" data-ref-field="<?php echo $fieldKey; ?>"><?php echo htmlspecialchars($item[$fieldKey] ?? ''); ?></textarea>
                                <?php elseif ($fieldKey === 'rating'): ?>
                                    <select data-ref-field="rating">
                                        <?php foreach (range(1,5) as $r): ?>
                                            <option value="<?php echo $r; ?>" <?php echo ($item['rating'] ?? 5) == $r ? 'selected' : ''; ?>>
                                                <?php echo str_repeat('⭐', $r); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="text" placeholder="<?php echo htmlspecialchars($label); ?>"
                                           value="<?php echo htmlspecialchars($item[$fieldKey] ?? ''); ?>"
                                           data-ref-field="<?php echo $fieldKey; ?>">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <div style="flex:100%;text-align:right;">
                            <button type="button" class="btn btn-sm btn-danger"
                                    onclick="refRemove(this,'<?php echo $hiddenId; ?>')">✕ Entfernen</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="btn btn-secondary btn-sm"
                    onclick="refAdd('<?php echo $hiddenId; ?>',<?php echo $fieldsJson; ?>)"
                    style="margin-top:.25rem;"><?php echo htmlspecialchars($btnLabel); ?></button>
            <?php
        };
        ?>
        <div class="form-section">
            <h3>🌟 Referenzen & Auftritte</h3>

            <!-- Testimonials -->
            <div style="margin-bottom:1.5rem;">
                <h4 style="margin:0 0 .75rem;">💬 Referenz-Stimmen</h4>
                <?php
                $tFields = ['text' => 'Zitat *', 'client_name' => 'Name des Kunden', 'position' => 'Position', 'company' => 'Unternehmen', 'rating' => 'Bewertung'];
                $repeater('testimonials', $testimonials, $tFields, '+ Referenz hinzufügen');
                ?>
            </div>

            <!-- Case Studies -->
            <div style="margin-bottom:1.5rem;">
                <h4 style="margin:0 0 .75rem;">📁 Case Studies / Projekte</h4>
                <?php
                $cFields = ['title' => 'Projekttitel *', 'description' => 'Beschreibung', 'link' => 'Link (URL)'];
                $repeater('case_studies', $case_studies, $cFields, '+ Case Study hinzufügen');
                ?>
            </div>

            <!-- Conference Talks -->
            <div>
                <h4 style="margin:0 0 .75rem;">🎤 Vorträge & Konferenzen</h4>
                <?php
                $cfFields = ['title' => 'Vortragstitel *', 'event' => 'Veranstaltung', 'year' => 'Jahr', 'video_link' => 'Video-Link (URL)'];
                $repeater('conference_talks', $conf_talks, $cfFields, '+ Vortrag hinzufügen');
                ?>
            </div>
        </div>

        <input type="hidden" name="meta[testimonials]"      id="refHidden_testimonials"     value="<?php echo htmlspecialchars(json_encode($testimonials), ENT_QUOTES); ?>">
        <input type="hidden" name="meta[case_studies]"      id="refHidden_case_studies"     value="<?php echo htmlspecialchars(json_encode($case_studies), ENT_QUOTES); ?>">
        <input type="hidden" name="meta[conference_talks]"  id="refHidden_conference_talks" value="<?php echo htmlspecialchars(json_encode($conf_talks), ENT_QUOTES); ?>">

        <script>
        (function(){
            function syncRef(sectionId) {
                const rows = document.querySelectorAll('[data-ref-row="'+sectionId+'"]');
                const data = [];
                rows.forEach(row => {
                    const entry = {};
                    row.querySelectorAll('[data-ref-field]').forEach(el => {
                        entry[el.dataset.refField] = el.tagName === 'TEXTAREA' ? el.value : el.value;
                    });
                    data.push(entry);
                });
                const hidden = document.getElementById(sectionId+'-hidden');
                if(hidden) hidden.value = JSON.stringify(data);
                // also sync the named hidden
                const named = document.getElementById('refHidden_'+sectionId);
                if(named) named.value = JSON.stringify(data);
            }
            ['testimonials','case_studies','conference_talks'].forEach(id => {
                const list = document.getElementById(id+'-list');
                if(list) list.addEventListener('input', () => syncRef(id));
            });
            window.refRemove = function(btn, sectionId) {
                btn.closest('[data-ref-row]').remove();
                syncRef(sectionId);
            };
            window.refAdd = function(sectionId, fields) {
                const list = document.getElementById(sectionId+'-list');
                if(!list) return;
                let html = '<div data-ref-row="'+sectionId+'" style="border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:.75rem;background:#fafafa;"><div class="form-row" style="flex-wrap:wrap;">';
                Object.entries(fields).forEach(([k,l]) => {
                    const isTextarea = (k === 'text' || k === 'description');
                    const isRating = k === 'rating';
                    const fullWidth = isTextarea ? 'style="flex:100%;"' : '';
                    html += '<div class="form-group" '+fullWidth+'><label>'+l+'</label>';
                    if(isTextarea) html += '<textarea rows="3" data-ref-field="'+k+'"></textarea>';
                    else if(isRating) {
                        html += '<select data-ref-field="rating">';
                        for(let r=1;r<=5;r++) html += '<option value="'+r+'">'+'⭐'.repeat(r)+'</option>';
                        html += '</select>';
                    } else html += '<input type="text" placeholder="'+l+'" data-ref-field="'+k+'">';
                    html += '</div>';
                });
                html += '<div style="flex:100%;text-align:right;"><button type="button" class="btn btn-sm btn-danger" onclick="refRemove(this,\''+sectionId+'\')">✕ Entfernen</button></div></div></div>';
                list.insertAdjacentHTML('beforeend', html);
                list.addEventListener('input', () => syncRef(sectionId));
            };
        })();

        /* Tech Repeater JS */
        window.techRepeaterAdd = function(jsId, ph) {
            const rows = document.getElementById(jsId+'-rows');
            if(!rows) return;
            const levelOpts = ['','beginner','intermediate','advanced','expert'];
            const levelLabels = {'':'—','beginner':'Einsteiger','intermediate':'Fortgeschritten','advanced':'Erfahren','expert':'Experte'};
            let sel = '<select style="width:150px;" data-field="level">';
            levelOpts.forEach(v => { sel += '<option value="'+v+'">'+levelLabels[v]+'</option>'; });
            sel += '</select>';
            rows.insertAdjacentHTML('beforeend','<div class="tech-row" style="display:flex;gap:.75rem;margin-bottom:.5rem;align-items:center;"><input type="text" placeholder="'+ph+'" style="flex:1;" data-field="name">'+sel+'<button type="button" onclick="techRepeaterRemove(this,\''+jsId+'\')" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:1.1rem;">✕</button></div>');
            techRepeaterSync(jsId);
        };
        window.techRepeaterRemove = function(btn, jsId) {
            btn.closest('.tech-row').remove();
            techRepeaterSync(jsId);
        };
        window.techRepeaterSync = function(jsId) {
            const rows = document.querySelectorAll('#'+jsId+'-rows .tech-row');
            const data = [];
            rows.forEach(r => {
                const n = r.querySelector('[data-field="name"]');
                const l = r.querySelector('[data-field="level"]');
                if(n && n.value) data.push({name: n.value, level: l ? l.value : ''});
            });
            const h = document.getElementById(jsId+'-hidden');
            if(h) h.value = JSON.stringify(data);
        };
        ['prog-langs','tech-frameworks','tech-databases','cloud-plats'].forEach(id => {
            const rows = document.getElementById(id+'-rows');
            if(rows) rows.addEventListener('input', () => techRepeaterSync(id));
        });
        </script>
        <?php
    }

    // ─── Services ─────────────────────────────────────────────────────────────

    private function render_services(array $meta): void
    {
        $services = [
            'services_consulting'    => ['icon' => '🧠', 'label' => 'Beratung / Consulting'],
            'services_implementation'=> ['icon' => '⚙️', 'label' => 'Umsetzung / Implementierung'],
            'services_training'      => ['icon' => '🎓', 'label' => 'Training & Schulung'],
            'services_support'       => ['icon' => '🛠️', 'label' => 'Support & Wartung'],
            'services_audit'         => ['icon' => '🔍', 'label' => 'Audit & Review'],
            'emergency_support'      => ['icon' => '🚨', 'label' => 'Notfall-Support (24/7)'],
            'workshop_offerings'     => ['icon' => '🎯', 'label' => 'Workshops & Intensiv-Sessions'],
        ];
        ?>
        <div class="form-section">
            <h3>🛎️ Service-Angebot</h3>
            <p class="field-hint" style="margin-bottom:1rem;">Welche Dienstleistungen bietet der Experte an?</p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:.75rem;">
                <?php foreach ($services as $key => $cfg): ?>
                <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;padding:.75rem;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;">
                    <input type="checkbox" name="meta[<?php echo $key; ?>]" value="1"
                           <?php echo !empty($meta[$key]) ? 'checked' : ''; ?>>
                    <?php echo $cfg['icon']; ?> <?php echo htmlspecialchars($cfg['label']); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    // ─── Netzwerk & Skalierung ────────────────────────────────────────────────

    private function render_network_scale(array $meta): void
    {
        $esc = CMS\Security::instance();
        ?>
        <div class="form-section">
            <h3>🌐 Netzwerk & Skalierung</h3>

            <div style="display:flex;flex-wrap:wrap;gap:1rem;margin-bottom:1.25rem;">
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.6rem 1rem;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;">
                    <input type="checkbox" name="meta[subcontractors_available]" value="1"
                           <?php echo !empty($meta['subcontractors_available']) ? 'checked' : ''; ?>>
                    👥 Subunternehmer verfügbar
                </label>
                <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer;padding:.6rem 1rem;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;">
                    <input type="checkbox" name="meta[team_expansion_possible]" value="1"
                           <?php echo !empty($meta['team_expansion_possible']) ? 'checked' : ''; ?>>
                    📈 Team-Erweiterung möglich
                </label>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="meta_max_team_size">Max. Teamgröße</label>
                    <input type="number" id="meta_max_team_size" name="meta[max_team_size]"
                           value="<?php echo $esc->escape($meta['max_team_size'] ?? ''); ?>"
                           min="1" placeholder="z.B. 10">
                </div>
                <div class="form-group">
                    <label for="meta_team_size_led">Max. geführte Teamgröße</label>
                    <input type="number" id="meta_team_size_led" name="meta[team_size_led]"
                           value="<?php echo $esc->escape($meta['team_size_led'] ?? ''); ?>"
                           min="0" placeholder="z.B. 15">
                </div>
                <div class="form-group">
                    <label for="meta_total_projects">Anzahl abgeschlossener Projekte</label>
                    <input type="number" id="meta_total_projects" name="meta[total_projects]"
                           value="<?php echo $esc->escape($meta['total_projects'] ?? ''); ?>"
                           min="0" placeholder="z.B. 42">
                </div>
            </div>

            <div class="form-group">
                <label for="meta_partner_networks">Partner-Netzwerke & Kooperationen</label>
                <textarea id="meta_partner_networks" name="meta[partner_networks]" rows="3"
                          placeholder="z.B. AWS Partner Network, Microsoft Gold Partner …"><?php echo $esc->escape($meta['partner_networks'] ?? ''); ?></textarea>
            </div>
        </div>
        <?php
    }
}
