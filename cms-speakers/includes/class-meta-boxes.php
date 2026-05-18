<?php
/**
 * Meta Boxes fuer CMS Speakers
 * @package CMS_Speakers
 * @since 2.0.0
 */
declare(strict_types=1);
if (!defined('ABSPATH')) { exit; }

final class CMS_Speakers_Meta_Boxes
{
    private static ?self $instance = null;
    public static function instance(): self
    {
        if (self::$instance === null) { self::$instance = new self(); }
        return self::$instance;
    }
    private function __construct() {}

    /**
     * Flaches Key→Label Mapping aller Skills (wiederverwendbar in Templates).
     */
    public static function get_skill_labels(): array
    {
        static $labels = null;
        if ($labels !== null) {
            return $labels;
        }
        $temp = new self();
        $labels = [];
        // render_skills baut $groups – wir extrahieren die Items via Reflection
        // Alternativ: die Gruppen direkt als statische Methode exponieren
        $groups = self::get_skill_groups();
        foreach ($groups as $g) {
            foreach ($g['items'] as $key => $lbl) {
                $labels[$key] = $lbl;
            }
        }
        return $labels;
    }

    /**
     * Skill-Gruppen mit Label und Items (verwendet von render_skills + get_skill_labels).
     */
    public static function get_skill_groups(): array
    {
        return [
            'tech_general' => [
                'label' => '💻 Technologie & Digital (Themen)',
                'items' => [
                    'ai_ml'              => 'KI / Machine Learning',
                    'data_science'       => 'Data Science & Analytics',
                    'cloud'              => 'Cloud Computing',
                    'cybersecurity'      => 'Cybersecurity / InfoSec',
                    'blockchain'         => 'Blockchain / Web3',
                    'iot'                => 'IoT & Embedded Systems',
                    'automation'         => 'Automation & Robotik',
                    'devops'             => 'DevOps & Platform Engineering',
                    'digital_transform'  => 'Digitale Transformation',
                    'software_arch'      => 'Software-Architektur',
                    'low_code'           => 'Low-Code / No-Code',
                    'metaverse_ar_vr'    => 'Metaverse / AR & VR',
                    'quantum'            => 'Quantum Computing',
                    'open_source'        => 'Open Source',
                    'api_integration'    => 'API & System-Integration',
                    'data_engineering'   => 'Data Engineering & Pipelines',
                ],
            ],
            'tech_languages' => [
                'label' => '🐍 Programmiersprachen & Frameworks',
                'items' => [
                    'lang_php'           => 'PHP',
                    'lang_python'        => 'Python',
                    'lang_javascript'    => 'JavaScript / TypeScript',
                    'lang_java'          => 'Java',
                    'lang_go'            => 'Go (Golang)',
                    'lang_rust'          => 'Rust',
                    'lang_csharp'        => 'C# / .NET',
                    'lang_cpp'           => 'C / C++',
                    'lang_swift'         => 'Swift / Kotlin',
                    'lang_r'             => 'R (Data Science)',
                    'fw_react'           => 'React / Next.js',
                    'fw_vue'             => 'Vue.js / Nuxt',
                    'fw_angular'         => 'Angular',
                    'fw_nodejs'          => 'Node.js / Express',
                    'fw_laravel'         => 'Laravel / Symfony',
                    'fw_django'          => 'Django / FastAPI',
                    'fw_spring'          => 'Spring Boot',
                    'fw_flutter'         => 'Flutter / React Native',
                ],
            ],
            'tech_infra' => [
                'label' => '⚙️ Infrastruktur & DevOps Tools',
                'items' => [
                    'infra_docker'       => 'Docker',
                    'infra_k8s'          => 'Kubernetes',
                    'infra_terraform'    => 'Terraform / IaC',
                    'infra_ansible'      => 'Ansible / Puppet / Chef',
                    'infra_ci_cd'        => 'CI/CD (Jenkins, GitHub Actions, GitLab CI)',
                    'infra_git'          => 'Git / GitHub / GitLab',
                    'db_sql'             => 'MySQL / PostgreSQL / MariaDB',
                    'db_nosql'           => 'MongoDB / Redis / Cassandra',
                    'db_search'          => 'Elasticsearch / OpenSearch',
                    'db_dw'              => 'Data Warehouse (Snowflake, BigQuery, Redshift)',
                    'cloud_aws'          => 'AWS',
                    'cloud_azure'        => 'Microsoft Azure',
                    'cloud_gcp'          => 'Google Cloud Platform',
                    'ml_ops'             => 'MLOps / LLMOps',
                    'observability'      => 'Observability (Grafana, Prometheus, Datadog)',
                    'security_tools'     => 'Security Tools (SIEM, Pen Testing, SOC)',
                ],
            ],
            'microsoft' => [
                'label' => '📬 Microsoft 365 & Enterprise-Plattformen',
                'items' => [
                    'ms_exchange'        => 'Exchange Server / Exchange Online',
                    'ms_teams'           => 'Microsoft Teams',
                    'ms_sharepoint'      => 'SharePoint / SharePoint Online',
                    'ms_m365'            => 'Microsoft 365 / Office 365',
                    'ms_active_dir'      => 'Active Directory / Microsoft Entra ID',
                    'ms_intune'          => 'Microsoft Intune / Endpoint Manager',
                    'ms_power_platform'  => 'Power Platform (Power Apps, Power Automate)',
                    'ms_power_bi'        => 'Power BI',
                    'ms_dynamics'        => 'Dynamics 365 (CRM / ERP)',
                    'ms_copilot'         => 'Microsoft Copilot / Copilot for M365',
                    'ms_sql_server'      => 'SQL Server / SSRS / SSAS',
                    'ms_defender'        => 'Microsoft Defender / Sentinel',
                    'ms_onedrive'        => 'OneDrive / Teams Rooms',
                    'ms_azure_devops'    => 'Azure DevOps / ADO',
                    'ms_viva'            => 'Microsoft Viva / Employee Experience',
                ],
            ],
            'enterprise_infra' => [
                'label' => '🏛️ Enterprise Infrastructure & Virtualisierung',
                'items' => [
                    'vmware_vsphere'     => 'VMware vSphere / vCenter',
                    'vmware_nsx'         => 'VMware NSX / vSAN / HCX',
                    'vmware_horizon'     => 'VMware Horizon (VDI)',
                    'nutanix'            => 'Nutanix HCI / AOS / AHV',
                    'nutanix_nc2'        => 'Nutanix Cloud Clusters (NC2)',
                    'citrix'             => 'Citrix DaaS / Virtual Apps & Desktops',
                    'hyper_v'            => 'Hyper-V / Windows Server',
                    'proxmox'            => 'Proxmox VE',
                    'veeam'              => 'Veeam Backup & Replication',
                    'zerto'              => 'Zerto / Disaster Recovery',
                    'netapp'             => 'NetApp Storage (ONTAP, StorageGRID)',
                    'dell_emc'           => 'Dell EMC PowerStore / PowerEdge',
                    'hpe'                => 'HPE ProLiant / Synergy / SimpliVity',
                    'cisco_net'          => 'Cisco Networking (ISE, Catalyst, Nexus)',
                    'cisco_ucs'          => 'Cisco UCS / HyperFlex',
                    'palo_alto'          => 'Palo Alto Networks (NGFW, Prisma)',
                    'fortinet'           => 'Fortinet FortiGate / FortiSIEM',
                    'f5'                 => 'F5 BIG-IP / NGINX',
                    'juniper'            => 'Juniper Networks',
                    'aruba'              => 'HPE Aruba / Aruba ClearPass',
                    'sap'                => 'SAP (ERP, S/4HANA, BTP)',
                    'oracle_db'          => 'Oracle Database / Oracle Cloud',
                    'ibm_mainframe'      => 'IBM Z / Mainframe / AIX',
                    'servicenow'         => 'ServiceNow (ITSM / ITOM)',
                    'splunk'             => 'Splunk SIEM / SOAR',
                    'crowdstrike'        => 'CrowdStrike Falcon',
                    'zscaler'            => 'Zscaler / SASE / Zero Trust',
                ],
            ],
            'business' => [
                'label' => '📈 Business & Management',
                'items' => [
                    'leadership'         => 'Leadership & Führung',
                    'change_mgmt'        => 'Change Management',
                    'innovation'         => 'Innovationsmanagement',
                    'entrepreneurship'   => 'Entrepreneurship & Startups',
                    'digital_marketing'  => 'Digital Marketing & Growth',
                    'sales'              => 'Vertrieb & Business Development',
                    'agile_scrum'        => 'Agile / Scrum / OKR',
                    'new_work'           => 'New Work & Future of Work',
                    'hr_people'          => 'HR & People Management',
                    'finance_fintech'    => 'Finance & FinTech',
                    'esg'                => 'ESG & Nachhaltigkeit',
                    'strategy'           => 'Strategie & Corporate Development',
                ],
            ],
            'communication' => [
                'label' => '🎤 Kommunikation & Soft Skills',
                'items' => [
                    'public_speaking'    => 'Public Speaking & Rhetorik',
                    'storytelling'       => 'Storytelling',
                    'coaching'           => 'Coaching & Mentoring',
                    'moderation'         => 'Moderation & Facilitation',
                    'train_trainer'      => 'Train-the-Trainer',
                    'intercultural'      => 'Interkulturelle Kompetenz',
                    'crisis_comm'        => 'Krisenkommunikation',
                    'media_training'     => 'Medientraining / PR',
                ],
            ],
            'industry' => [
                'label' => '🏭 Branchen-Expertise',
                'items' => [
                    'healthcare'         => 'Gesundheitswesen & MedTech',
                    'edu_elearning'      => 'Bildung & E-Learning',
                    'real_estate'        => 'Immobilien & PropTech',
                    'energy_climate'     => 'Energie & Klimaschutz',
                    'automotive'         => 'Automobil & Mobilität',
                    'logistics'          => 'Logistik & Supply Chain',
                    'legal_regtech'      => 'Legal & RegTech',
                    'ngo_social'         => 'NGO & Social Impact',
                    'media_entertainment'=> 'Medien & Entertainment',
                    'retail_ecommerce'   => 'Handel & E-Commerce',
                ],
            ],
        ];
    }

    private function v(?object $sp, string $f, string $d = ''): string
    {
        return htmlspecialchars((string)($sp->$f ?? $d));
    }

    // ── 1. Persoenliche Daten ───────────────────────────────
    public function render_personal_data(?object $sp): void { ?>
        <div class="admin-card">
            <h3>👤 Persönliche Daten</h3>
            <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Vorname <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="first_name" class="form-control" value="<?= $this->v($sp,'first_name') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nachname <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="last_name" class="form-control" value="<?= $this->v($sp,'last_name') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Akadem. Titel (Dr., Prof. …)</label>
                    <input type="text" name="academic_title" class="form-control" value="<?= $this->v($sp,'academic_title') ?>" placeholder="Dr.">
                </div>
                <div class="form-group">
                    <label class="form-label">Anrede</label>
                    <select name="gender" class="form-control">
                        <?php foreach ([''=>'Keine Angabe','m'=>'Herr','f'=>'Frau','d'=>'Divers'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($sp->gender ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    <?php }

    // ── 2. Position & Unternehmen ────────────────────────────
    public function render_position(?object $sp, array $companies): void { ?>
        <div class="admin-card">
            <h3>💼 Position & Unternehmen</h3>
            <div class="form-group">
                <label class="form-label">Position / Titel</label>
                <input type="text" name="position" class="form-control" value="<?= $this->v($sp,'position') ?>" placeholder="CEO, Head of AI, Keynote Speaker …">
            </div>
            <div class="form-group">
                <label class="form-label">Unternehmen (Freitext)</label>
                <input type="text" name="company" class="form-control" value="<?= $this->v($sp,'company') ?>" placeholder="Unternehmensname eingeben …">
                <small class="form-text">Alternativ: unten aus dem Unternehmensverzeichnis wählen</small>
            </div>
            <?php if (!empty($companies)): ?>
            <div class="form-group">
                <label class="form-label">Oder aus Unternehmensverzeichnis wählen</label>
                <select name="company_id" class="form-control" onchange="if(this.value){document.querySelector('[name=company]').value=this.options[this.selectedIndex].text}">
                    <option value="">— Kein Unternehmen —</option>
                    <?php foreach ($companies as $co): ?>
                    <option value="<?= (int)$co->id ?>" <?= ((int)($sp->company_id ?? 0)) === (int)$co->id ? 'selected' : '' ?>>
                        <?= htmlspecialchars($co->name) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </div>
    <?php }

    // ── 3. Kontakt ──────────────────────────────────────────
    public function render_contact(?object $sp): void { ?>
        <div class="admin-card">
            <h3>📞 Kontakt & Social Media</h3>
            <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">E-Mail <span style="color:#ef4444;">*</span></label>
                    <input type="email" name="email" class="form-control" value="<?= $this->v($sp,'email') ?>" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="phone" class="form-control" value="<?= $this->v($sp,'phone') ?>" placeholder="+49 …">
                </div>
                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control" value="<?= $this->v($sp,'website') ?>" placeholder="https://…">
                </div>
                <div class="form-group">
                    <label class="form-label">LinkedIn</label>
                    <input type="url" name="linkedin" class="form-control" value="<?= $this->v($sp,'linkedin') ?>" placeholder="https://linkedin.com/in/…">
                </div>
                <div class="form-group">
                    <label class="form-label">Twitter / X</label>
                    <input type="text" name="twitter" class="form-control" value="<?= $this->v($sp,'twitter') ?>" placeholder="@handle">
                </div>
                <div class="form-group">
                    <label class="form-label">Xing</label>
                    <input type="url" name="xing" class="form-control" value="<?= $this->v($sp,'xing') ?>" placeholder="https://xing.com/…">
                </div>
                <div class="form-group">
                    <label class="form-label">Instagram</label>
                    <input type="text" name="instagram" class="form-control" value="<?= $this->v($sp,'instagram') ?>" placeholder="@handle">
                </div>
                <div class="form-group">
                    <label class="form-label">YouTube</label>
                    <input type="url" name="youtube" class="form-control" value="<?= $this->v($sp,'youtube') ?>" placeholder="https://youtube.com/@…">
                </div>
                <div class="form-group">
                    <label class="form-label">GitHub</label>
                    <input type="url" name="github" class="form-control" value="<?= $this->v($sp,'github') ?>" placeholder="https://github.com/…">
                </div>
                <div class="form-group">
                    <label class="form-label">GitLab</label>
                    <input type="url" name="gitlab" class="form-control" value="<?= $this->v($sp,'gitlab') ?>" placeholder="https://gitlab.com/…">
                </div>
            </div>
        </div>
    <?php }

    // ── 4. Standort ─────────────────────────────────────────
    public function render_location(?object $sp): void { ?>
        <div class="admin-card">
            <h3>📍 Standort</h3>
            <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">Stadt</label>
                    <input type="text" name="location_city" class="form-control" value="<?= $this->v($sp,'location_city') ?>" placeholder="Berlin">
                </div>
                <div class="form-group">
                    <label class="form-label">PLZ</label>
                    <input type="text" name="location_zip" class="form-control" value="<?= $this->v($sp,'location_zip') ?>" placeholder="10115">
                </div>
                <div class="form-group" style="grid-column:span 3;">
                    <label class="form-label">Land</label>
                    <input type="text" name="location_country" class="form-control" value="<?= $this->v($sp,'location_country','Deutschland') ?>">
                </div>
            </div>
        </div>
    <?php }

    // ── 5. Vortragsprofil ───────────────────────────────────
    public function render_profile(?object $sp, array $all_formats, array $travel_options, array $avail_options, array $formats, string $langs_str): void {
        $travel = $sp->travel_radius ?? 'national';
        $avail  = $sp->availability  ?? 'available';
        ?>
        <div class="admin-card">
            <h3>🎤 Vortragsprofil</h3>
            <div class="form-group">
                <label class="form-label">Formate</label>
                <div style="display:flex;flex-wrap:wrap;gap:.6rem;margin-top:.25rem;">
                    <?php foreach ($all_formats as $val => $lbl): ?>
                    <label class="checkbox-label">
                        <input type="checkbox" name="formats[]" value="<?= $val ?>" <?= in_array($val, $formats, true) ? 'checked' : '' ?>>
                        <?= $lbl ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-top:.75rem;">
                <div class="form-group">
                    <label class="form-label">Reisebereitschaft</label>
                    <select name="travel_radius" class="form-control">
                        <?php foreach ($travel_options as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $travel === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Verfügbarkeit</label>
                    <select name="availability" class="form-control">
                        <?php foreach ($avail_options as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $avail === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Honorar Min (€)</label>
                    <input type="number" name="speaking_fee_min" class="form-control" value="<?= (int)($sp->speaking_fee_min ?? 0) ?: '' ?>" min="0" step="100" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Honorar Max (€)</label>
                    <input type="number" name="speaking_fee_max" class="form-control" value="<?= (int)($sp->speaking_fee_max ?? 0) ?: '' ?>" min="0" step="100" placeholder="0">
                </div>
                <div class="form-group">
                    <label class="form-label">Max. Zuhörer</label>
                    <input type="number" name="max_audience_size" class="form-control" value="<?= (int)($sp->max_audience_size ?? 0) ?: '' ?>" min="0" placeholder="unbegrenzt">
                </div>
                <div class="form-group">
                    <label class="form-label">Sprachen (kommagetrennt)</label>
                    <input type="text" name="languages" class="form-control" value="<?= htmlspecialchars($langs_str) ?>" placeholder="Deutsch, Englisch">
                </div>
                <div class="form-group">
                    <label class="form-label">Vortragsstil</label>
                    <input type="text" name="speaking_style" class="form-control" value="<?= $this->v($sp,'speaking_style') ?>" placeholder="z.B. Inspirierend, Analytisch, Interaktiv …">
                    <small class="form-text">Kurze Beschreibung des persönlichen Präsentationsstils</small>
                </div>
                <div class="form-group">
                    <label class="form-label">Zielgruppe</label>
                    <input type="text" name="target_audience" class="form-control" value="<?= $this->v($sp,'target_audience') ?>" placeholder="z.B. Führungskräfte, IT-Fachleute, Startups …">
                    <small class="form-text">Welche Zielgruppen werden hauptsächlich adressiert?</small>
                </div>
            </div>
        </div>
    <?php }

    // ── 6. Bio & Kurzprofil ──────────────────────────────────
    public function render_bio(?object $sp): void { ?>
        <div class="admin-card">
            <h3>📝 Bio & Kurzprofil</h3>
            <div class="form-group">
                <label class="form-label">Kurzprofil <small style="color:#94a3b8;">(max. 300 Zeichen, keine HTML-Tags)</small></label>
                <textarea name="short_bio" class="form-control" rows="3" maxlength="300" placeholder="Kurze Beschreibung für Vorschaukarten …"><?= $this->v($sp,'short_bio') ?></textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Ausführliche Bio</label>
                <?php if (defined('SUNEDITOR_URL') || file_exists(ABSPATH . 'assets/suneditor-2.47.8/src/suneditor.js')): ?>
                <?php $spBioJs = json_encode($sp->bio ?? '', JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
                <textarea id="speaker_bio" name="bio" class="form-control" rows="10" style="display:none;"></textarea>
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    var _bioContent = <?= $spBioJs ?>;
                    if (typeof SUNEDITOR !== 'undefined') {
                        var spEditor = SUNEDITOR.create(document.getElementById('speaker_bio'), {
                            height: 280, lang: SUNEDITOR_LANG && SUNEDITOR_LANG.de ? SUNEDITOR_LANG.de : 'en',
                            buttonList: [['bold','italic','underline','strike'],['list'],['link'],['image'],['fullScreen']]
                        });
                        spEditor.setContents(_bioContent);
                        spEditor.onChange = function(contents) {
                            document.getElementById('speaker_bio').value = contents;
                        };
                        var spForm = document.getElementById('speaker_bio').closest('form');
                        if (spForm) {
                            spForm.addEventListener('submit', function() {
                                document.getElementById('speaker_bio').value = spEditor.getContents();
                            });
                        }
                    } else {
                        var el = document.getElementById('speaker_bio');
                        el.value = _bioContent;
                        el.style.display = 'block';
                    }
                });
                </script>
                <?php else: ?>
                <textarea name="bio" class="form-control" rows="10"><?= htmlspecialchars($sp->bio ?? '') ?></textarea>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label class="form-label">Auszeichnungen & Awards</label>
                <textarea name="awards" class="form-control" rows="3" placeholder="z.B. Speaker of the Year 2023, Forbes 40 under 40, Innovationspreis …"><?= $this->v($sp,'awards') ?></textarea>
                <small class="form-text">Preise, Auszeichnungen und besondere Anerkennungen (eine pro Zeile empfohlen)</small>
            </div>
        </div>
    <?php }

    // ── 7. Foto ─────────────────────────────────────────────
    public function render_photo(?object $sp): void {
        $photo = $sp->photo_url ?? '';
        ?>
        <div class="admin-card">
            <h3>📸 Profilfoto</h3>
            <div style="display:flex;gap:1.5rem;align-items:flex-start;flex-wrap:wrap;">
                <div id="spk-photo-preview" style="width:90px;height:90px;border-radius:50%;overflow:hidden;border:3px solid #ddd6fe;background:linear-gradient(135deg,#8b5cf6,#a855f7);display:flex;align-items:center;justify-content:center;color:#fff;font-size:1.5rem;font-weight:800;flex-shrink:0;">
                    <?php if ($photo): ?><img src="<?= htmlspecialchars($photo) ?>" style="width:100%;height:100%;object-fit:cover;" id="spk-photo-img"><?php else: ?>📷<?php endif; ?>
                </div>
                <div style="flex:1;min-width:220px;">
                    <div class="form-group" style="margin-bottom:.5rem;">
                        <label class="form-label">Foto-URL</label>
                        <input type="url" name="photo_url" id="spk-photo-url" class="form-control" value="<?= htmlspecialchars($photo) ?>" placeholder="https://…" oninput="spkUpdatePhoto(this.value)">
                        <small class="form-text">Direkte URL zu einem Bild (JPG, PNG, WebP)</small>
                    </div>
                </div>
            </div>
            <script>
            function spkUpdatePhoto(url) {
                var prev = document.getElementById('spk-photo-preview');
                var img  = document.getElementById('spk-photo-img');
                if (!url) { prev.replaceChildren(document.createTextNode('📷')); return; }
                if (!img) { img = document.createElement('img'); img.id = 'spk-photo-img'; img.style.cssText='width:100%;height:100%;object-fit:cover;'; prev.replaceChildren(img); }
                img.src = url;
            }
            </script>
        </div>
    <?php }

    // ── 8. Themen / Topics ──────────────────────────────────
    public function render_topics(array $topics): void {
        $topics_json = htmlspecialchars(json_encode(array_map(fn($t) => is_object($t) ? $t->topic_name : ($t['topic_name'] ?? $t), $topics)));
        ?>
        <div class="admin-card">
            <h3>🏷️ Themen & Schwerpunkte</h3>
            <input type="hidden" name="topics_json" id="spk-topics-json" value="<?= $topics_json ?>">
            <div id="spk-topics-wrap" style="display:flex;flex-wrap:wrap;gap:.4rem;min-height:44px;padding:.5rem;border:2px solid #e2e8f0;border-radius:8px;background:#fafcff;cursor:text;" data-spk-focus-input="spk-topic-input">
                <?php foreach ($topics as $t):
                    $name = is_object($t) ? $t->topic_name : ($t['topic_name'] ?? $t);
                ?>
                <span class="spk-topic-tag" data-tag="<?= htmlspecialchars($name) ?>">
                    <?= htmlspecialchars($name) ?>
                    <button type="button" data-spk-remove-topic="1" style="background:none;border:none;cursor:pointer;padding:0 0 0 4px;color:inherit;font-size:1rem;">&times;</button>
                </span>
                <?php endforeach; ?>
                <input id="spk-topic-input" type="text" placeholder="Thema eingeben + Enter …" style="border:none;outline:none;background:transparent;font-size:.85rem;min-width:160px;padding:.1rem .3rem;">
            </div>
            <small class="form-text">Enter oder Komma drücken, um ein Thema hinzuzufügen</small>
            <script>
            (function(){
                var input = document.getElementById('spk-topic-input');
                var wrap  = document.getElementById('spk-topics-wrap');
                var hidden= document.getElementById('spk-topics-json');
                function getTags(){
                    return Array.from(wrap.querySelectorAll('.spk-topic-tag')).map(function(el){ return el.dataset.tag; });
                }
                function updateHidden(){ hidden.value = JSON.stringify(getTags()); }
                function addTag(val){
                    val = val.trim().replace(/,+$/,'').trim();
                    if (!val) return;
                    var exists = getTags().map(function(s){ return s.toLowerCase(); }).indexOf(val.toLowerCase()) >= 0;
                    if (exists) return;
                    var span = document.createElement('span');
                    span.className = 'spk-topic-tag';
                    span.dataset.tag = val;
                    span.appendChild(document.createTextNode(val));
                    var removeButton = document.createElement('button');
                    removeButton.type = 'button';
                    removeButton.style.cssText = 'background:none;border:none;cursor:pointer;padding:0 0 0 4px;color:inherit;font-size:1rem;';
                    removeButton.textContent = '×';
                    removeButton.addEventListener('click', function(){ spkRemoveTopic(span); });
                    span.appendChild(removeButton);
                    wrap.insertBefore(span, input);
                    updateHidden();
                }
                window.spkRemoveTopic = function(el){ el.remove(); updateHidden(); };
                wrap.addEventListener('click', function(e){
                    var removeButton = e.target.closest('[data-spk-remove-topic]');
                    if (removeButton) {
                        e.preventDefault();
                        spkRemoveTopic(removeButton.parentElement);
                        return;
                    }
                    input.focus();
                });
                input.addEventListener('keydown', function(e){
                    if (e.key === 'Enter' || e.key === ',' || e.key === 'Tab') {
                        e.preventDefault();
                        addTag(input.value);
                        input.value = '';
                    } else if (e.key === 'Backspace' && input.value === '') {
                        var tags = wrap.querySelectorAll('.spk-topic-tag');
                        if (tags.length) tags[tags.length-1].remove();
                        updateHidden();
                    }
                });
                input.addEventListener('blur', function(){ if(input.value) { addTag(input.value); input.value=''; } });
            })();
            </script>
        </div>
    <?php }

    // ── 9. Skills ──────────────────────────────────────────
    public function render_skills(?object $sp): void {
        $current = is_string($sp->skills ?? null)
            ? (json_decode($sp->skills, true) ?? [])
            : [];

        $groups = self::get_skill_groups();
        ?>
        <div class="admin-card">
            <h3>🛠️ Speaker Skills</h3>
            <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Thematische Schwerpunkte und Kernkompetenzen auswählen – erscheinen als Pills auf der Speaker-Card.</p>
            <?php foreach ($groups as $groupKey => $group): ?>
            <div style="margin-bottom:1rem;">
                <div style="font-size:.82rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.45rem;"><?= $group['label'] ?></div>
                <div style="display:flex;flex-wrap:wrap;gap:.5rem 1rem;">
                    <?php foreach ($group['items'] as $val => $lbl): ?>
                    <label class="checkbox-label" style="min-width:200px;">
                        <input type="checkbox" name="skills[]" value="<?= $val ?>" <?= in_array($val, $current, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($lbl) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php }

    // ── 10. Auszeichnungen & Programme ────────────────────────
    public function render_recognitions(?object $sp): void {
        $current = is_string($sp->recognitions ?? null)
            ? (json_decode($sp->recognitions, true) ?? [])
            : [];

        $options = [
            'community' => [
                'label' => '🏅 Community-Programme',
                'items' => [
                    'microsoft_mvp'         => 'Microsoft MVP',
                    'google_gde'            => 'Google Developer Expert (GDE)',
                    'aws_community_hero'    => 'AWS Community Hero',
                    'aws_community_builder' => 'AWS Community Builder',
                    'oracle_ace'            => 'Oracle ACE',
                    'salesforce_mvp'        => 'Salesforce MVP',
                    'hashicorp_ambassador'  => 'HashiCorp Ambassador',
                    'vmware_vexpert'        => 'VMware vExpert',
                    'docker_captain'        => 'Docker Captain',
                    'github_star'           => 'GitHub Star',
                    'ibm_champion'          => 'IBM Champion',
                    'cncf_ambassador'       => 'CNCF Ambassador',
                ],
            ],
            'speaker' => [
                'label' => '🎤 Speaker-Auszeichnungen',
                'items' => [
                    'tedx_speaker'          => 'TEDx Speaker',
                    'ted_speaker'           => 'TED Speaker',
                    'speaker_of_year'       => 'Speaker of the Year',
                    'top_speaker'           => 'Top Speaker Award',
                    'keynote_speaker'       => 'Professional Keynote Speaker',
                ],
            ],
            'ranking' => [
                'label' => '📊 Rankings & Listen',
                'items' => [
                    'forbes_30u30'          => 'Forbes 30 under 30',
                    'forbes_40u40'          => 'Forbes 40 under 40',
                    'linkedin_top_voice'    => 'LinkedIn Top Voice',
                    'wef_young_global_leader' => 'WEF Young Global Leader',
                    'top100_innovators'     => 'Top 100 Innovatoren',
                    'top100_leaders'        => 'Top 100 Leaders',
                ],
            ],
            'academic' => [
                'label' => '🎓 Akademisch & Wissenschaftlich',
                'items' => [
                    'honorary_professor'    => 'Honorarprofessor/-in',
                    'honorary_doctor'       => 'Doctor honoris causa',
                    'research_fellow'       => 'Research Fellow',
                    'habilitation'          => 'Habilitation',
                ],
            ],
            'other' => [
                'label' => '🏆 Weitere Auszeichnungen',
                'items' => [
                    'innovationspreis'      => 'Innovationspreis',
                    'bundesverdienstkreuz'  => 'Bundesverdienstkreuz',
                    'best_of_show'          => 'Best of Show',
                    'startup_mentor'        => 'Start-up Mentor (State recognised)',
                    'gartner_analyst'       => 'Gartner Analyst',
                ],
            ],
        ];
        ?>
        <div class="admin-card">
            <h3>🏅 Auszeichnungen & Programme</h3>
            <p style="color:#64748b;font-size:.875rem;margin-bottom:1rem;">Offizielle Programme und Ehrungen auswählen. Hinweise zu weiteren Auszeichnungen im Feld "Auszeichnungen (Freitext)" unten eintragen.</p>
            <?php foreach ($options as $groupKey => $group): ?>
            <div style="margin-bottom:1rem;">
                <div style="font-size:.82rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.45rem;"><?= $group['label'] ?></div>
                <div style="display:flex;flex-wrap:wrap;gap:.5rem 1rem;">
                    <?php foreach ($group['items'] as $val => $lbl): ?>
                    <label class="checkbox-label" style="min-width:220px;">
                        <input type="checkbox" name="recognitions[]" value="<?= $val ?>" <?= in_array($val, $current, true) ? 'checked' : '' ?>>
                        <?= htmlspecialchars($lbl) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php }

    // ── 10. Status ────────────────────────────────────────────
    public function render_status(?object $sp): void { ?>
        <div class="admin-card">
            <h3>⚙️ Status & Sichtbarkeit</h3>
            <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <?php foreach (['active'=>'✅ Aktiv','inactive'=>'⏸️ Inaktiv','draft'=>'📝 Entwurf'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= ($sp->status ?? 'active') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="display:flex;flex-direction:column;gap:.5rem;justify-content:flex-end;">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1" <?= ($sp->is_featured ?? 0) ? 'checked' : '' ?>>
                        ⭐ Als Featured markieren
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_verified" value="1" <?= ($sp->is_verified ?? 0) ? 'checked' : '' ?>>
                        ✔ Verifiziert
                    </label>
                </div>
            </div>
        </div>
    <?php }

    // ── 10. Events ──────────────────────────────────────────
    public function render_events(int $speaker_id, array $events, array $companies, string $csrf, array $event_types): void { ?>
        <div class="admin-card" id="spk-events-section">
            <h3>🗓️ Auftritte & Events</h3>
            <div id="spk-events-list">
                <?php if (empty($events)): ?>
                <p style="color:#64748b;font-style:italic;">Noch keine Auftritte eingetragen.</p>
                <?php else: foreach ($events as $ev):
                    $et = is_object($ev) ? $ev : (object)$ev;
                    $org = $et->organizer_type === 'company' ? ($et->company_name ?? $et->organizer_name ?? '') : ($et->organizer_name ?? '');
                ?>
                <div class="spk-event-row" data-id="<?= (int)$et->id ?>">
                    <div class="spk-event-info">
                        <span class="spk-event-type-badge"><?= htmlspecialchars($event_types[$et->event_type ?? ''] ?? ($et->event_type ?? '')) ?></span>
                        <?php
                        $presenceLabels = ['presence'=>'🏛️ Präsenz','online'=>'💻 Online','hybrid'=>'🔀 Hybrid'];
                        $pType = $et->presence_type ?? 'presence';
                        if ($pType !== 'presence'):
                        ?><span class="spk-event-type-badge" style="background:#e0f2fe;color:#0369a1;border-color:#bae6fd;"><?= $presenceLabels[$pType] ?? $pType ?></span><?php endif; ?>
                        <strong><?= htmlspecialchars($et->event_title ?? '') ?></strong>
                        <?php if ($et->event_date ?? ''): ?><span class="spk-ev-date">📅 <?= htmlspecialchars(date('d.m.Y', strtotime($et->event_date))) ?></span><?php endif; ?>
                        <?php if ($et->event_location ?? ''): ?><span class="spk-ev-loc">📍 <?= htmlspecialchars($et->event_location) ?></span><?php endif; ?>
                        <?php if ($org): ?><span class="spk-ev-org">🏢 <?= htmlspecialchars($org) ?></span><?php endif; ?>
                        <?php if ($et->audience_size ?? ''): ?><span class="spk-ev-loc">👥 <?= number_format((int)$et->audience_size) ?></span><?php endif; ?>
                    </div>
                    <button type="button" data-spk-delete-event="<?= (int)$et->id ?>" class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border:none;cursor:pointer;border-radius:6px;padding:.2rem .5rem;">Löschen</button>
                </div>
                <?php endforeach; endif; ?>
            </div>
            <hr style="margin:1rem 0;border-color:#e2e8f0;">
            <h4 style="margin:0 0 .75rem;font-size:.95rem;color:#1e293b;">➕ Neuen Auftritt hinzufügen</h4>
            <div class="spk-add-event-form">
                <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div class="form-group">
                        <label class="form-label">Event-Titel <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="nev_title" class="form-control" placeholder="z.B. Digital Transformation Summit">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Event-Typ</label>
                        <select id="nev_type" class="form-control">
                            <?php foreach ($event_types as $val => $lbl): ?><option value="<?= $val ?>"><?= $lbl ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Datum Von</label>
                        <input type="date" id="nev_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Datum Bis</label>
                        <input type="date" id="nev_date_end" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Ort</label>
                        <input type="text" id="nev_location" class="form-control" placeholder="Berlin, Online, …">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Anzahl Zuhörer</label>
                        <input type="number" id="nev_audience" class="form-control" min="0" placeholder="500">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Thema / Titel des Vortrags</label>
                        <input type="text" id="nev_topic" class="form-control" placeholder="Vortragstitel …">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Event-URL</label>
                        <input type="url" id="nev_eventurl" class="form-control" placeholder="https://…">
                    </div>

                    <!-- Veranstalter -->
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">Veranstalter</label>
                        <div style="display:flex;gap:.75rem;margin-bottom:.5rem;">
                            <label class="radio-label">
                                <input type="radio" name="nev_org_type" value="manual" checked onchange="spkToggleOrg(this.value)"> Manuell eingeben
                            </label>
                            <?php if (!empty($companies)): ?>
                            <label class="radio-label">
                                <input type="radio" name="nev_org_type" value="company" onchange="spkToggleOrg(this.value)"> Aus Unternehmensverzeichnis
                            </label>
                            <?php endif; ?>
                        </div>
                        <div id="nev_org_manual">
                            <input type="text" id="nev_org_name" class="form-control" placeholder="Veranstaltername …">
                        </div>
                        <?php if (!empty($companies)): ?>
                        <div id="nev_org_company" style="display:none;">
                            <select id="nev_company_id" class="form-control">
                                <option value="">— Unternehmen wählen —</option>
                                <?php foreach ($companies as $co): ?>
                                <option value="<?= (int)$co->id ?>"><?= htmlspecialchars($co->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Video-URL</label>
                        <input type="url" id="nev_video" class="form-control" placeholder="https://youtube.com/…">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Slides-URL</label>
                        <input type="url" id="nev_slides" class="form-control" placeholder="https://slideshare.net/…">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Präsenzformat</label>
                        <select id="nev_presence" class="form-control">
                            <option value="presence">🏛️ Vor Ort (Präsenz)</option>
                            <option value="online">💻 Online / Remote</option>
                            <option value="hybrid">🔀 Hybrid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Verknüpftes CMS-Event (optional)</label>
                        <input type="number" id="nev_cms_event_id" class="form-control" min="0" placeholder="CMS-Event-ID (leer lassen wenn nicht vorhanden)">
                        <small class="form-text">Nur ausfüllen, wenn dieses Event in cms-events verwaltet wird.</small>
                    </div>

                    <div class="form-group" style="grid-column:span 2;">
                        <label class="form-label">Kurzbeschreibung</label>
                        <textarea id="nev_desc" class="form-control" rows="2" placeholder="Optional …"></textarea>
                    </div>
                    <div class="form-group" style="grid-column:span 2;">
                        <label class="checkbox-label">
                            <input type="checkbox" id="nev_public" checked> Im öffentlichen Profil anzeigen
                        </label>
                    </div>
                </div>
                <button type="button" data-spk-add-event="1" class="btn btn-primary" style="margin-top:.25rem;">Auftritt speichern</button>
            </div>
        </div>

        <script>
        var SPK_ID    = <?= (int)$speaker_id ?>;
        var SPK_URL   = '<?= SITE_URL ?>';
        var SPK_CSRF_EVT = '<?= htmlspecialchars($csrf) ?>';

        function spkToggleOrg(val) {
            document.getElementById('nev_org_manual').style.display   = val === 'manual'  ? 'block' : 'none';
            var cDiv = document.getElementById('nev_org_company');
            if (cDiv) cDiv.style.display = val === 'company' ? 'block' : 'none';
        }

        function spkAddEvent() {
            var orgType = document.querySelector('[name=nev_org_type]:checked')?.value || 'manual';
            var fd = new FormData();
            fd.append('csrf_token',    SPK_CSRF_EVT);
            fd.append('speaker_id',    SPK_ID);
            fd.append('event_title',   document.getElementById('nev_title').value);
            fd.append('event_type',    document.getElementById('nev_type').value);
            fd.append('event_date',    document.getElementById('nev_date').value);
            fd.append('event_date_end',document.getElementById('nev_date_end').value);
            fd.append('event_location',document.getElementById('nev_location').value);
            fd.append('organizer_type',orgType);
            fd.append('organizer_name',document.getElementById('nev_org_name')?.value || '');
            fd.append('company_id',    document.getElementById('nev_company_id')?.value || '');
            fd.append('topic',         document.getElementById('nev_topic').value);
            fd.append('description',   document.getElementById('nev_desc').value);
            fd.append('audience_size', document.getElementById('nev_audience').value);
            fd.append('video_url',     document.getElementById('nev_video').value);
            fd.append('slides_url',    document.getElementById('nev_slides').value);
            fd.append('event_url',     document.getElementById('nev_eventurl').value);
            fd.append('presence_type', document.getElementById('nev_presence').value);
            var cmsEvId = document.getElementById('nev_cms_event_id').value;
            if (cmsEvId) fd.append('cms_event_id', cmsEvId);
            if (document.getElementById('nev_public').checked) fd.append('is_public','1');

            fetch(SPK_URL + '/admin/speakers/event/add', { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if (d.success) { window.location.reload(); }
                    else { alert('Fehler: ' + (d.error || 'Unbekannt')); }
                }).catch(function(e){ alert('Netzwerkfehler: ' + e.message); });
        }

        function spkDeleteEvent(id) {
            if (!confirm('Auftritt wirklich löschen?')) return;
            var fd = new FormData();
            fd.append('csrf_token', SPK_CSRF_EVT);
            fetch(SPK_URL + '/admin/speakers/event/delete/' + id, { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(d){ if (d.success) { var row = document.querySelector('[data-id="'+id+'"]'); if(row) row.remove(); } })
                .catch(function(e){ alert('Fehler: ' + e.message); });
        }
        document.querySelectorAll('[data-spk-delete-event]').forEach(function(button) {
            button.addEventListener('click', function() {
                spkDeleteEvent(button.dataset.spkDeleteEvent || '0');
            });
        });
        document.querySelectorAll('[data-spk-add-event]').forEach(function(button) {
            button.addEventListener('click', spkAddEvent);
        });
        </script>

        <style>
        .spk-event-row{display:flex;align-items:center;gap:.75rem;padding:.6rem .75rem;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:.5rem;background:#fafcff;}
        .spk-event-info{flex:1;display:flex;flex-wrap:wrap;gap:.4rem;align-items:center;}
        .spk-event-type-badge{background:#f5f3ff;color:#7c3aed;border:1px solid #ddd6fe;border-radius:50px;padding:.15rem .5rem;font-size:.72rem;font-weight:700;}
        .spk-ev-date,.spk-ev-loc,.spk-ev-org{font-size:.78rem;color:#64748b;white-space:nowrap;}
        .spk-topic-tag{display:inline-flex;align-items:center;gap:4px;padding:.25rem .6rem;background:#f5f3ff;border:1px solid #ddd6fe;border-radius:50px;font-size:.8rem;color:#7c3aed;font-weight:600;}
        </style>
    <?php }
}
