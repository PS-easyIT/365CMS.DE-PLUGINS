<?php
/**
 * CMS Experts – Member Dashboard Integration
 *
 * Registriert den Experten-Bereich im Member-Dashboard.
 * Wird geladen von cms-experts.php (load_dependencies).
 *
 * URL: /member/plugin/experts
 *
 * @package CMS_Experts
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Experts_Member_Dashboard
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        if (class_exists('\CMS\Hooks')) {
            \CMS\Hooks::addAction('member_dashboard_init', [$this, 'register'], 10);
            // Expert-CSS auf der Plugin-Section-Seite einbinden
            \CMS\Hooks::addAction('member_plugin_section_head', [$this, 'enqueueExpertStyles'], 10);
        }
    }

    /**
     * Gibt das Expert-Stylesheet im <head> der Plugin-Section-Seite aus.
     * Wird über den `member_plugin_section_head`-Hook nur für den experts-Slug aufgerufen.
     *
     * @param string $slug Aktueller Plugin-Slug
     */
    public function enqueueExpertStyles(string $slug): void
    {
        if ($slug !== 'experts') {
            return;
        }
        $cssFile = defined('CMS_EXPERTS_PLUGIN_DIR')
            ? CMS_EXPERTS_PLUGIN_DIR . 'assets/css/style.css'
            : '';
        $cssUrl  = defined('CMS_EXPERTS_PLUGIN_URL')
            ? CMS_EXPERTS_PLUGIN_URL . 'assets/css/style.css'
            : '';

        if ($cssUrl !== '') {
            $v = $cssFile && file_exists($cssFile) ? filemtime($cssFile) : '1';
            echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl) . '?v=' . $v . '">' . "\n";
        }
    }

    /**
     * Registriert den Experten-Bereich in der PluginDashboardRegistry.
     */
    public function register(\CMS\Member\PluginDashboardRegistry $registry): void
    {
        $registry->register([
            'plugin'    => 'cms-experts',
            'slug'      => 'experts',
            'label'     => 'EXPERTS',
            'icon'      => '🧑‍💼',
            'category'  => 'plugins',
            'priority'  => 10,
            'capability'=> null,
            'dashboard_widget' => [
                'title'          => 'EXPERTS',
                'description'    => 'Profile, Kompetenzen und Zuordnungen im Überblick.',
                'color'          => '#4f46e5',
                'stats_callback' => [$this, 'getDashboardStats'],
                'link_label'     => 'Zu den Experten',
                'admin_url'      => '/admin/experts',
                'admin_label'    => '⚙️ Admin',
            ],
            'render_callback' => [$this, 'renderPage'],
        ]);
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    /**
     * @param object $user
     * @return array{count: int, label: string}
     */
    public function getDashboardStats(object $user): array
    {
        if (!class_exists('CMS_Experts_Database')) {
            return ['count' => 0, 'label' => 'Experten'];
        }

        $isAdmin = \CMS\Auth::instance()->isAdmin();
        try {
            if ($isAdmin) {
                $count = (int) CMS_Experts_Database::instance()->countExperts();
                return ['count' => $count, 'label' => 'Experten gesamt'];
            } else {
                $userId = (int) ($user->id ?? 0);
                $rows   = CMS_Experts_Database::instance()->get_experts_all(['user_id' => $userId]);
                return ['count' => count($rows), 'label' => 'Meine Experten'];
            }
        } catch (\Throwable $e) {
            return ['count' => 0, 'label' => 'Experten'];
        }
    }

    // ── Page Rendering ────────────────────────────────────────────────────────

    /**
     * Rendert den Experten-Bereich im Member-Dashboard.
     * Verwendet die echten Expert-Card-Templates mit 3-spaltigem Grid.
     *
     * @param object $user
     * @param array  $params
     */
    public function renderPage(object $user, array $params = []): void
    {
        $setFlash = static function (string $key, string $message): void {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                @session_start();
            }
            $_SESSION[$key] = $message;
        };

        // ── POST: neuen Experten speichern ────────────────────────────────────
        $requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ($requestMethod === 'POST' && isset($_POST['expert_create'])) {
            if (!\CMS\Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), 'member_expert_create')) {
                $setFlash('error', 'Sicherheitscheck fehlgeschlagen.');
                header('Location: /member/plugin/experts?action=new');
                exit;
            }
            try {
                $isAdminSave = \CMS\Auth::instance()->isAdmin();
                $ownerUserId = (int) ($user->id ?? 0);
                if ($ownerUserId <= 0) {
                    $setFlash('error', 'Benutzerkonto konnte nicht zugeordnet werden.');
                    header('Location: /member/plugin/experts?action=new');
                    exit;
                }
                $validatedEmail = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
                $validatedPhotoUrl = cms_experts_public_url((string) ($_POST['photo_url'] ?? '')) ?: null;
                $availabilityMap = [
                    'available' => 'available',
                    'partially' => 'limited',
                    'limited' => 'limited',
                    'unavailable' => 'booked',
                    'booked' => 'booked',
                ];
                $availability = $availabilityMap[$_POST['availability'] ?? 'available'] ?? 'available';
                $id = CMS_Experts_Database::instance()->save_expert([
                    'user_id'          => $ownerUserId,
                    'first_name'       => sanitize_text_field($_POST['first_name'] ?? ''),
                    'last_name'        => sanitize_text_field($_POST['last_name']  ?? ''),
                    'email'            => $validatedEmail,
                    'phone'            => sanitize_text_field($_POST['phone']  ?? ''),
                    'mobile'           => sanitize_text_field($_POST['mobile'] ?? ''),
                    'position'         => sanitize_text_field($_POST['position'] ?? ''),
                    'company'          => sanitize_text_field($_POST['company']  ?? ''),
                    'photo_url'        => $validatedPhotoUrl,
                    'location_city'    => sanitize_text_field($_POST['location_city']    ?? ''),
                    'location_zip'     => sanitize_text_field($_POST['location_zip']     ?? ''),
                    'location_country' => sanitize_text_field($_POST['location_country'] ?? 'Deutschland'),
                    'biography'        => strip_tags($_POST['biography'] ?? ''),
                    'experience_years' => (int) ($_POST['experience_years'] ?? 0),
                    'availability'     => $availability,
                    'hourly_rate'      => is_numeric($_POST['hourly_rate'] ?? '') ? (float)$_POST['hourly_rate'] : null,
                    'daily_rate'       => is_numeric($_POST['daily_rate']  ?? '') ? (float)$_POST['daily_rate']  : null,
                    'status'           => $isAdminSave ? 'active' : 'pending',
                ]);

                // Skills speichern
                if ($id > 0) {
                    $parse_tags = static fn(string $raw): array =>
                        array_values(array_unique(array_filter(array_map(static fn(string $tag): string => sanitize_text_field(trim($tag)), explode(',', $raw)))));

                    CMS_Experts_Database::instance()->save_expert_skills($id, [
                        'general' => $parse_tags($_POST['skills_general'] ?? ''),
                        'tech'    => $parse_tags($_POST['skills_tech'] ?? ''),
                        'soft'    => $parse_tags($_POST['skills_soft'] ?? ''),
                    ]);

                    // Fachrichtungen speichern
                    $spec_ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['spec_ids'] ?? [])), static fn(int $specId): bool => $specId > 0)));
                    CMS_Experts_Database::instance()->save_expert_specializations($id, $spec_ids);

                    // Social-Links als Meta speichern
                    $social_keys = [
                        'social_linkedin', 'social_xing', 'social_github', 'social_twitter',
                        'social_website', 'social_gitlab', 'social_stackoverflow',
                        'social_youtube', 'social_blog_rss',
                    ];
                    foreach ($social_keys as $sKey) {
                        $sVal = cms_experts_public_url((string) ($_POST['meta'][$sKey] ?? ''));
                        if ($sVal !== '') {
                            CMS_Experts_Database::instance()->save_meta($id, $sKey, $sVal);
                        }
                    }
                }
                if ($isAdminSave) {
                    $setFlash('success', 'Experte wurde erfolgreich angelegt.');
                } else {
                    $setFlash('success', 'Ihr Experten-Profil wurde eingereicht und wird vom Admin geprüft.');
                }
                header('Location: /member/plugin/experts');
                exit;
            } catch (\Throwable $e) {
                error_log('CMS Experts member save failed: ' . $e->getMessage());
                $setFlash('error', 'Fehler beim Speichern. Bitte später erneut versuchen.');
                header('Location: /member/plugin/experts?action=new');
                exit;
            }
        }

        $action   = sanitize_text_field($_GET['action'] ?? '');
        $isAdmin  = \CMS\Auth::instance()->isAdmin();

        // ── Formular: Neuer Experte ───────────────────────────────────────────
        if ($action === 'new') {
            $this->renderCreateForm($user);
            return;
        }

        // ── Übersicht ─────────────────────────────────────────────────────────
        $experts  = [];
        $error    = null;
        $settings = [];

        if (class_exists('CMS_Experts_Database')) {
            try {
                $db      = CMS_Experts_Database::instance();
                $settings = $db->get_all_plugin_settings();

                if ($isAdmin) {
                    $queryArgs = ['status' => 'active', 'limit' => 60];
                } else {
                    // Eigene Einträge aller Status zeigen (inkl. pending)
                    $queryArgs = ['user_id' => (int) ($user->id ?? 0), 'limit' => 60];
                }
                $experts = $db->get_experts_all($queryArgs) ?? [];

                if (!empty($experts)) {
                    $this->bulkLoadExpertData($experts);
                }
            } catch (\Throwable $e) {
                $error = 'Daten konnten nicht geladen werden.';
            }
        } else {
            $error = 'Das Experten-Plugin ist nicht vollständig installiert.';
        }

        $settings = array_merge([
            'design_show_skills'         => '1',
            'design_show_specialization' => '1',
            'design_primary_color'       => '#5e72e4',
            'design_accent_color'        => '#8965e0',
            'design_border_radius'       => '12',
            'design_cta_color'           => '#c2410c',
            'design_card_bg'             => '#fffdf4',
        ], $settings);

        $cssVars = sprintf(
            ':root{--expert-primary:%s;--expert-accent:%s;--expert-radius:%dpx;--expert-cta-color:%s;--expert-card-bg:%s;}',
            htmlspecialchars((string) $settings['design_primary_color'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $settings['design_accent_color'], ENT_QUOTES, 'UTF-8'),
            (int) $settings['design_border_radius'],
            htmlspecialchars((string) $settings['design_cta_color'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) $settings['design_card_bg'], ENT_QUOTES, 'UTF-8')
        );
        echo '<style>' . $cssVars . '</style>';
        ?>

        <?php if ($error): ?>
        <div class="member-alert member-alert-error">
            <span class="alert-icon">✕</span>
            <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php else: ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
            <p style="color:#64748b;font-size:.875rem;margin:0;">
                <?php echo count($experts); ?> Experte(n) verfügbar
            </p>
            <a href="/member/plugin/experts?action=new"
               style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.25rem;
                      background:#4f46e5;color:#fff;border-radius:8px;font-weight:600;
                      font-size:.875rem;text-decoration:none;">
                ➕ Neuer Experte
            </a>
        </div>

        <?php if (empty($experts)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0 0 .75rem;">👤</p>
            <p><strong>Keine Experten vorhanden</strong></p>
            <p style="color:#64748b;margin:.25rem 0 0;">Es sind noch keine aktiven Experten-Profile vorhanden.</p>
            <a href="/member/plugin/experts?action=new" class="btn btn-primary" style="margin-top:1rem;">
                ➕ Ersten Experten anlegen
            </a>
        </div>
        <?php else: ?>
        <div class="experts-grid">
            <?php foreach ($experts as $expert): ?>
                <?php
                if (class_exists('CMS_Experts_Template_Loader')) {
                    echo CMS_Experts_Template_Loader::instance()->render_expert_card($expert, $settings);
                }
                ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
        <?php
    }

    // ── Create Form ───────────────────────────────────────────────────────────

    private function renderCreateForm(object $user): void
    {
        $csrfToken = \CMS\Security::instance()->generateToken('member_expert_create');
        $isAdmin   = \CMS\Auth::instance()->isAdmin();

        // Skill-Presets und Fachrichtungen laden
        $skillPresets    = [];
        $specializations = [];
        if (class_exists('CMS_Experts_Taxonomies')) {
            try {
                $skillPresets    = CMS_Experts_Taxonomies::instance()->get_skill_presets_grouped();
                $specializations = CMS_Experts_Taxonomies::instance()->get_specializations();
            } catch (\Throwable $e) {
                // Fallback: leere Arrays
            }
        }

        // Fachrichtungen nach Parent gruppieren
        $specRoots    = [];
        $specChildren = [];
        foreach ($specializations as $sp) {
            if (!$sp->parent_id) {
                $specRoots[] = $sp;
            } else {
                $specChildren[(int)$sp->parent_id][] = $sp;
            }
        }
        ?>
        <div style="margin-bottom:1rem;">
            <a href="/member/plugin/experts" style="color:#4f46e5;font-size:.875rem;text-decoration:none;">
                ← Zurück zur Übersicht
            </a>
        </div>

        <?php if (!$isAdmin): ?>
        <div style="background:#eff6ff;border-left:4px solid #3b82f6;padding:.875rem 1rem;
                    border-radius:6px;margin-bottom:1.25rem;font-size:.875rem;color:#1e40af;">
            ℹ️ Ihr Profil wird nach dem Einreichen vom Admin geprüft und dann freigeschaltet.
        </div>
        <?php endif; ?>

        <div class="admin-card">
            <h3>🧑‍💼 Neues Experten-Profil anlegen</h3>

            <form method="POST" action="/member/plugin/experts?action=new">
                <input type="hidden" name="expert_create" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <!-- Persönliche Daten -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">👤 Persönliche Daten</h4>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Vorname <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="first_name" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nachname <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="last_name" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Position / Rolle</label>
                        <input type="text" name="position" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['position'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unternehmen</label>
                        <input type="text" name="company" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Profilbild-URL</label>
                    <input type="url" name="photo_url" class="form-control" placeholder="https://..."
                           value="<?php echo htmlspecialchars($_POST['photo_url'] ?? ''); ?>">
                    <small class="form-text">Direktlink zu einem öffentlichen Profilbild (jpg/png).</small>
                </div>

                <!-- Kontakt -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">📞 Kontakt</h4>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">E-Mail</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mobil</label>
                        <input type="tel" name="mobile" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['mobile'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Standort -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">📍 Standort</h4>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Stadt</label>
                        <input type="text" name="location_city" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['location_city'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">PLZ</label>
                        <input type="text" name="location_zip" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['location_zip'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Land</label>
                        <input type="text" name="location_country" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['location_country'] ?? 'Deutschland'); ?>">
                    </div>
                </div>

                <!-- Expertise & Konditionen -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">💼 Expertise & Konditionen</h4>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Erfahrung (Jahre)</label>
                        <input type="number" name="experience_years" class="form-control" min="0" max="50"
                               value="<?php echo (int)($_POST['experience_years'] ?? 0); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Verfügbarkeit</label>
                        <select name="availability" class="form-control">
                            <option value="available"  <?php echo ($_POST['availability'] ?? 'available') === 'available'  ? 'selected' : ''; ?>>Verfügbar</option>
                            <option value="partially"  <?php echo ($_POST['availability'] ?? '') === 'partially'            ? 'selected' : ''; ?>>Teilweise</option>
                            <option value="unavailable" <?php echo ($_POST['availability'] ?? '') === 'unavailable'         ? 'selected' : ''; ?>>Nicht verfügbar</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stundensatz (€)</label>
                        <input type="number" name="hourly_rate" class="form-control" min="0" step="10"
                               value="<?php echo htmlspecialchars($_POST['hourly_rate'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tagessatz (€)</label>
                        <input type="number" name="daily_rate" class="form-control" min="0" step="50"
                               value="<?php echo htmlspecialchars($_POST['daily_rate'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Skills & Kompetenzen -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">🛠️ Skills & Kompetenzen</h4>
                <p style="color:#64748b;font-size:.8rem;margin:0 0 1rem;">
                    Klicke auf eine Vorlage, um sie hinzuzufügen, oder gib eigene Skills als Komma-getrennte Liste ein.
                </p>

                <?php
                $skillSections = [
                    'general' => ['label' => 'Programmierung', 'placeholder' => 'z.B. PHP, Python, JavaScript …', 'hint' => 'Programmiersprachen & Grundlagen'],
                    'tech'    => ['label' => 'Skills',         'placeholder' => 'z.B. Docker, React, AWS …',       'hint' => 'Frameworks, Tools & Plattformen'],
                    'soft'    => ['label' => 'Persönliche Stärken', 'placeholder' => 'z.B. Teamwork, Führung …',  'hint' => 'Soft Skills & Methoden'],
                ];
                foreach ($skillSections as $sType => $sCfg):
                    $typePresets = $skillPresets[$sType] ?? [];
                ?>
                <div class="form-group" <?php echo $sType !== 'general' ? 'style="margin-top:1rem;"' : ''; ?>>
                    <label class="form-label"><?php echo $sCfg['label']; ?> <small style="font-weight:400;color:#6b7280;">(<?php echo $sCfg['hint']; ?>)</small></label>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;padding:.5rem;border:2px solid #e2e8f0;border-radius:8px;min-height:42px;cursor:text;" 
                         id="tagWrap_<?php echo $sType; ?>" onclick="document.getElementById('tagInput_<?php echo $sType; ?>').focus()">
                        <input type="text" id="tagInput_<?php echo $sType; ?>" 
                               placeholder="<?php echo htmlspecialchars($sCfg['placeholder']); ?>"
                               style="border:none;outline:none;flex:1;min-width:160px;font-size:.875rem;padding:.2rem 0;"
                               onkeydown="handleSkillKeydown(event, '<?php echo $sType; ?>')">
                    </div>
                    <input type="hidden" name="skills_<?php echo $sType; ?>" id="skillsHidden_<?php echo $sType; ?>" value="">
                    <?php if (!empty($typePresets)): ?>
                    <div style="margin-top:.5rem;">
                        <small style="color:#64748b;font-weight:600;">📋 Vorlagen:</small>
                        <div style="display:flex;flex-wrap:wrap;gap:.3rem;margin-top:.25rem;">
                            <?php foreach ($typePresets as $preset): ?>
                            <button type="button" 
                                    style="background:#f1f5f9;border:1px solid #e2e8f0;border-radius:4px;padding:2px 8px;font-size:.75rem;color:#475569;cursor:pointer;"
                                    onclick="addSkillTag('<?php echo htmlspecialchars($preset->skill_name, ENT_QUOTES); ?>', '<?php echo $sType; ?>')">
                                + <?php echo htmlspecialchars($preset->skill_name); ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <!-- Fachrichtungen -->
                <?php if (!empty($specRoots)): ?>
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">🎯 Fachrichtungen</h4>
                <p style="color:#64748b;font-size:.8rem;margin:0 0 .75rem;">
                    Wähle deine Spezialisierungen aus. Die erste Auswahl wird als primäre Fachrichtung gesetzt.
                </p>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:.75rem;">
                    <?php foreach ($specRoots as $root):
                        $children = $specChildren[(int)$root->id] ?? [];
                    ?>
                    <div style="border:1px solid #e2e8f0;border-radius:6px;overflow:hidden;">
                        <div style="padding:.4rem .75rem;background:#f8fafc;font-size:.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.04em;border-bottom:1px solid #e2e8f0;">
                            <?php echo htmlspecialchars($root->name); ?>
                        </div>
                        <div style="padding:.25rem 0;">
                            <label style="display:flex;align-items:center;gap:.5rem;padding:.35rem .75rem;font-size:.875rem;cursor:pointer;font-weight:600;color:#374151;">
                                <input type="checkbox" name="spec_ids[]" value="<?php echo (int)$root->id; ?>" style="accent-color:#3b82f6;">
                                <?php echo htmlspecialchars($root->name); ?>
                            </label>
                            <?php foreach ($children as $child): ?>
                            <label style="display:flex;align-items:center;gap:.5rem;padding:.3rem .75rem .3rem 1.5rem;font-size:.85rem;cursor:pointer;color:#475569;">
                                <input type="checkbox" name="spec_ids[]" value="<?php echo (int)$child->id; ?>" style="accent-color:#3b82f6;">
                                <?php echo htmlspecialchars($child->name); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Social Links -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">🌐 Online-Präsenz</h4>
                <?php
                $socialFields = [
                    'social_linkedin'  => ['label' => 'LinkedIn',   'icon' => '🔗', 'ph' => 'https://linkedin.com/in/…'],
                    'social_xing'      => ['label' => 'Xing',       'icon' => '🔗', 'ph' => 'https://xing.com/profile/…'],
                    'social_github'    => ['label' => 'GitHub',     'icon' => '🐙', 'ph' => 'https://github.com/…'],
                    'social_twitter'   => ['label' => 'Twitter / X','icon' => '🐦', 'ph' => 'https://twitter.com/…'],
                    'social_website'   => ['label' => 'Website',    'icon' => '🌐', 'ph' => 'https://…'],
                    'social_youtube'   => ['label' => 'YouTube',    'icon' => '▶️', 'ph' => 'https://youtube.com/@…'],
                ];
                ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                    <?php foreach ($socialFields as $sKey => $sCfg): ?>
                    <div class="form-group">
                        <label class="form-label"><?php echo $sCfg['icon']; ?> <?php echo htmlspecialchars($sCfg['label']); ?></label>
                        <input type="url" name="meta[<?php echo $sKey; ?>]" class="form-control" 
                               placeholder="<?php echo htmlspecialchars($sCfg['ph']); ?>"
                               value="<?php echo htmlspecialchars($_POST['meta'][$sKey] ?? ''); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Über mich -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">📝 Über mich</h4>

                <div class="form-group">
                    <label class="form-label">Biografie</label>
                    <textarea name="biography" class="form-control" rows="6"><?php echo htmlspecialchars($_POST['biography'] ?? ''); ?></textarea>
                    <small class="form-text">Beschreiben Sie Ihren beruflichen Werdegang, Schwerpunkte und Expertise.</small>
                </div>

                <div style="display:flex;gap:.75rem;margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary">💾 Profil einreichen</button>
                    <a href="/member/plugin/experts" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </div>

        <script>
        // Skill-Tag-System für Member-Dashboard
        const _skillTags = {general: [], tech: [], soft: []};

        function syncSkillHidden(type) {
            const hidden = document.getElementById('skillsHidden_' + type);
            if (hidden) hidden.value = _skillTags[type].join(',');
        }

        function renderSkillTags(type) {
            const wrap = document.getElementById('tagWrap_' + type);
            if (!wrap) return;
            wrap.querySelectorAll('.mem-skill-pill').forEach(el => el.remove());
            const input = document.getElementById('tagInput_' + type);
            _skillTags[type].forEach(function(tag, idx) {
                const pill = document.createElement('span');
                pill.className = 'mem-skill-pill';
                pill.style.cssText = 'display:inline-flex;align-items:center;gap:.25rem;background:#eff6ff;color:#1e40af;border-radius:4px;padding:2px 8px;font-size:.8rem;';
                pill.textContent = tag;
                const x = document.createElement('span');
                x.textContent = '×';
                x.style.cssText = 'cursor:pointer;font-weight:700;color:#93c5fd;margin-left:2px;';
                x.onclick = function() { removeSkillTag(idx, type); };
                pill.appendChild(x);
                wrap.insertBefore(pill, input);
            });
            syncSkillHidden(type);
        }

        function addSkillTag(name, type) {
            name = name.trim();
            if (!name || _skillTags[type].includes(name)) return;
            _skillTags[type].push(name);
            renderSkillTags(type);
        }

        function removeSkillTag(idx, type) {
            _skillTags[type].splice(idx, 1);
            renderSkillTags(type);
        }

        function handleSkillKeydown(e, type) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                var val = e.target.value.replace(/,/g, '').trim();
                if (val) { addSkillTag(val, type); e.target.value = ''; }
            }
            if (e.key === 'Backspace' && e.target.value === '' && _skillTags[type].length) {
                _skillTags[type].pop();
                renderSkillTags(type);
            }
        }
        </script>
        <?php
    }

    // ── Bulk Data Loading ─────────────────────────────────────────────────────

    /**
     * Lädt Skills und Spezialisierungen für alle Experten in einem Query.
     * Die Daten werden als `_skills` und `_specializations` an die Objekte angehängt.
     *
     * @param object[] $experts  Array von Experten-Objekten (pass by reference via foreach)
     */
    private function bulkLoadExpertData(array &$experts): void
    {
        if (empty($experts)) {
            return;
        }

        try {
            $db          = \CMS\Database::instance();
            $prefix      = method_exists($db, 'prefix') ? $db->prefix() : $db->getPrefix();
            $expertIds   = array_map(static fn($e) => (int) $e->id, $experts);
            $placeholders = implode(',', array_fill(0, count($expertIds), '?'));

            // Skills
            $skillRows = $db->get_results(
                "SELECT expert_id, skill_name
                   FROM {$prefix}expert_skills
                  WHERE expert_id IN ({$placeholders})
                  ORDER BY skill_name ASC",
                $expertIds
            );
            $skillsMap = [];
            foreach ($skillRows as $row) {
                $skillsMap[(int)$row->expert_id][] = $row->skill_name;
            }

            // Spezialisierungen (via JOIN)
            $specRows = $db->get_results(
                "SELECT r.expert_id, s.name
                   FROM {$prefix}expert_specialization_rel r
                   JOIN {$prefix}expert_specializations s ON s.id = r.specialization_id
                  WHERE r.expert_id IN ({$placeholders})
                  ORDER BY r.is_primary DESC",
                $expertIds
            );
            $specsMap = [];
            foreach ($specRows as $row) {
                $specsMap[(int)$row->expert_id][] = $row->name;
            }

            // An die Experten-Objekte anhängen
            foreach ($experts as $expert) {
                $expert->_skills          = $skillsMap[(int)$expert->id] ?? [];
                $expert->_specializations = $specsMap[(int)$expert->id]  ?? [];
            }
        } catch (\Throwable $e) {
            // Kein Fatal – Cards zeigen sich ohne Skills
        }
    }
}

// Bootstrap
CMS_Experts_Member_Dashboard::instance();
