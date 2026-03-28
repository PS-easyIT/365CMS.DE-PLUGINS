<?php
/**
 * CMS Speakers – Member Dashboard Integration
 *
 * Registriert den Speaker-Bereich im Member-Dashboard.
 * Wird geladen von cms-speakers.php (load_dependencies).
 *
 * URL: /member/plugin/speakers
 *
 * @package CMS_Speakers
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Speakers_Member_Dashboard
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
            // Speaker-CSS auf der Plugin-Section-Seite einbinden
            \CMS\Hooks::addAction('member_plugin_section_head', [$this, 'enqueueSpeakerStyles'], 10);
        }
    }

    /**
     * Gibt das Speaker-Stylesheet im <head> der Plugin-Section-Seite aus.
     * Wird über den `member_plugin_section_head`-Hook nur für den speakers-Slug aufgerufen.
     *
     * @param string $slug Aktueller Plugin-Slug
     */
    public function enqueueSpeakerStyles(string $slug): void
    {
        if ($slug !== 'speakers') {
            return;
        }
        $cssFile = defined('CMS_SPEAKERS_PLUGIN_DIR')
            ? CMS_SPEAKERS_PLUGIN_DIR . 'assets/css/style.css'
            : '';
        $cssUrl  = defined('CMS_SPEAKERS_PLUGIN_URL')
            ? CMS_SPEAKERS_PLUGIN_URL . 'assets/css/style.css'
            : '';

        if ($cssUrl !== '') {
            $v = $cssFile && file_exists($cssFile) ? filemtime($cssFile) : '1';
            echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl) . '?v=' . $v . '">' . "\n";
        }
    }

    /**
     * Registriert den Speaker-Bereich in der PluginDashboardRegistry.
     */
    public function register(\CMS\Member\PluginDashboardRegistry $registry): void
    {
        $registry->register([
            'plugin'    => 'cms-speakers',
            'slug'      => 'speakers',
            'label'     => 'SPEAKER',
            'icon'      => '🎤',
            'category'  => 'plugins',
            'priority'  => 20,
            'capability'=> null,
            'dashboard_widget' => [
                'title'          => 'SPEAKER',
                'description'    => 'Referenten, Vorträge und Verfügbarkeiten im Überblick.',
                'color'          => '#7c3aed',
                'stats_callback' => [$this, 'getDashboardStats'],
                'link_label'     => 'Zu den Speakern',
                'admin_url'      => '/admin/speakers',
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
        if (!class_exists('CMS_Speakers_Database')) {
            return ['count' => 0, 'label' => 'Speaker'];
        }

        $isAdmin = \CMS\Auth::instance()->isAdmin();
        try {
            if ($isAdmin) {
                $count = (int) CMS_Speakers_Database::instance()->count_speakers(['status' => null]);
                return ['count' => $count, 'label' => 'Speaker gesamt'];
            } else {
                $userId = (int) ($user->id ?? 0);
                $count  = (int) CMS_Speakers_Database::instance()->count_speakers(['status' => null, 'user_id' => $userId]);
                return ['count' => $count, 'label' => 'Meine Speaker'];
            }
        } catch (\Throwable $e) {
            return ['count' => 0, 'label' => 'Speaker'];
        }
    }

    // ── Page Rendering ────────────────────────────────────────────────────────

    /**
     * Rendert den Speaker-Bereich im Member-Dashboard.
     * Verwendet die echten Speaker-Card-Templates mit 3-spaltigem Grid.
     *
     * @param object $user
     * @param array  $params
     */
    public function renderPage(object $user, array $params = []): void
    {
        // ── POST: neuen Speaker speichern ─────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['speaker_create'])) {
            if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'member_speaker_create')) {
                $_SESSION['error'] = 'Sicherheitscheck fehlgeschlagen.';
                header('Location: /member/plugin/speakers?action=new');
                exit;
            }
            try {
                $isAdminSave = \CMS\Auth::instance()->isAdmin();
                $allowedGenders = ['', 'm', 'f', 'd'];
                $allowedFormats = ['keynote', 'workshop', 'panel', 'moderation', 'training', 'consulting', 'interview', 'webinar'];
                $allowedTravelRadii = ['local', 'regional', 'national', 'international', 'worldwide'];
                $allowedAvailability = ['available', 'limited', 'booked'];
                $validatedEmail = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
                $validatedPhotoUrl = filter_var(trim((string) ($_POST['photo_url'] ?? '')), FILTER_VALIDATE_URL) ?: null;
                $validatedWebsite = filter_var(trim((string) ($_POST['website'] ?? '')), FILTER_VALIDATE_URL) ?: null;
                $validatedLinkedin = filter_var(trim((string) ($_POST['linkedin'] ?? '')), FILTER_VALIDATE_URL) ?: null;
                $validatedTwitter = filter_var(trim((string) ($_POST['twitter'] ?? '')), FILTER_VALIDATE_URL) ?: null;
                $validatedXing = filter_var(trim((string) ($_POST['xing'] ?? '')), FILTER_VALIDATE_URL) ?: null;
                $normalizedFormats = array_values(array_unique(array_filter(array_map(static fn($format) => sanitize_text_field(trim((string) $format)), (array) ($_POST['formats'] ?? [])))));
                $normalizedFormats = array_values(array_filter($normalizedFormats, static fn(string $format): bool => in_array($format, $allowedFormats, true)));
                $normalizedSkills = array_values(array_unique(array_filter(array_map(static fn($skill) => sanitize_text_field(trim((string) $skill)), (array) ($_POST['skills'] ?? [])))));
                $normalizedRecognitions = array_values(array_unique(array_filter(array_map(static fn($recognition) => sanitize_text_field(trim((string) $recognition)), (array) ($_POST['recognitions'] ?? [])))));
                $id = CMS_Speakers_Database::instance()->save_speaker([
                    'user_id'           => (int) $user->id,
                    'first_name'        => sanitize_text_field($_POST['first_name']  ?? ''),
                    'last_name'         => sanitize_text_field($_POST['last_name']   ?? ''),
                    'title'             => sanitize_text_field($_POST['title']       ?? ''),
                    'gender'            => in_array($_POST['gender'] ?? '', $allowedGenders, true) ? (string) ($_POST['gender'] ?? '') : '',
                    'email'             => $validatedEmail,
                    'phone'             => sanitize_text_field($_POST['phone']       ?? ''),
                    'position'          => sanitize_text_field($_POST['position']    ?? ''),
                    'company'           => sanitize_text_field($_POST['company']     ?? ''),
                    'photo_url'         => $validatedPhotoUrl,
                    'location_city'     => sanitize_text_field($_POST['location_city']     ?? ''),
                    'location_zip'      => sanitize_text_field($_POST['location_zip']      ?? ''),
                    'location_country'  => sanitize_text_field($_POST['location_country']  ?? 'Deutschland'),
                    'website'           => $validatedWebsite,
                    'linkedin'          => $validatedLinkedin,
                    'twitter'           => $validatedTwitter,
                    'xing'              => $validatedXing,
                    'languages'         => sanitize_text_field($_POST['languages']   ?? ''),
                    'formats'           => json_encode($normalizedFormats),
                    'skills'            => json_encode($normalizedSkills),
                    'recognitions'      => json_encode($normalizedRecognitions),
                    'target_audience'   => sanitize_text_field($_POST['target_audience'] ?? ''),
                    'speaking_style'    => sanitize_text_field($_POST['speaking_style']  ?? ''),
                    'travel_radius'     => in_array($_POST['travel_radius'] ?? '', $allowedTravelRadii, true) ? (string) ($_POST['travel_radius'] ?? 'national') : 'national',
                    'max_audience_size' => is_numeric($_POST['max_audience'] ?? '')  ? (int)$_POST['max_audience']  : null,
                    'availability'      => in_array($_POST['availability'] ?? '', $allowedAvailability, true) ? (string) ($_POST['availability'] ?? 'available') : 'available',
                    'speaking_fee_min'  => is_numeric($_POST['fee_min'] ?? '') ? (float)$_POST['fee_min'] : null,
                    'speaking_fee_max'  => is_numeric($_POST['fee_max'] ?? '') ? (float)$_POST['fee_max'] : null,
                    'short_bio'         => strip_tags($_POST['short_bio'] ?? ''),
                    'bio'               => strip_tags($_POST['bio']       ?? ''),
                    'status'            => $isAdminSave ? 'active' : 'pending',
                ]);

                // Topics speichern
                if ($id > 0) {
                    $topicNames = array_values(array_unique(array_filter(array_map(static fn(string $topic): string => sanitize_text_field(trim($topic)), explode(',', $_POST['speaker_topics'] ?? '')))));
                    if (!empty($topicNames)) {
                        CMS_Speakers_Database::instance()->save_topics($id, $topicNames);
                    }
                }
                if ($isAdminSave) {
                    $_SESSION['success'] = 'Speaker-Profil wurde erfolgreich angelegt.';
                } else {
                    $_SESSION['success'] = 'Ihr Speaker-Profil wurde eingereicht und wird vom Admin geprüft.';
                }
                header('Location: /member/plugin/speakers');
                exit;
            } catch (\Throwable $e) {
                $_SESSION['error'] = 'Fehler beim Speichern: ' . $e->getMessage();
                header('Location: /member/plugin/speakers?action=new');
                exit;
            }
        }

        $action  = sanitize_text_field($_GET['action'] ?? '');
        $isAdmin = \CMS\Auth::instance()->isAdmin();

        // ── Formular: Neuer Speaker ───────────────────────────────────────────
        if ($action === 'new') {
            $this->renderCreateForm($user);
            return;
        }

        // ── Übersicht ─────────────────────────────────────────────────────────
        $speakers = [];
        $error    = null;
        $settings = [];

        if (class_exists('CMS_Speakers_Database')) {
            try {
                $db       = CMS_Speakers_Database::instance();
                $settings = $db->get_settings();

                if ($isAdmin) {
                    $queryArgs = ['status' => null, 'limit' => 60];
                } else {
                    // Eigene Einträge aller Status zeigen (inkl. pending)
                    $queryArgs = ['status' => null, 'user_id' => (int) ($user->id ?? 0), 'limit' => 60];
                }
                $speakers = $db->get_speakers($queryArgs) ?? [];

                if (!empty($speakers)) {
                    $this->bulkLoadTopicData($speakers);
                }
            } catch (\Throwable $e) {
                $error = 'Daten konnten nicht geladen werden.';
            }
        } else {
            $error = 'Das Speaker-Plugin ist nicht vollständig installiert.';
        }

        $settings = array_merge([
            'design_primary_color'   => '#8b5cf6',
            'design_accent_color'    => '#7c3aed',
            'design_card_bg'         => '#faf5ff',
            'design_border_radius'   => '12',
            'design_cta_label'       => 'Profil ansehen',
            'archive_header_bg_from' => '#6d28d9',
            'archive_header_bg_to'   => '#a855f7',
        ], $settings);

        $cssVars = sprintf(
            ':root{--sp-primary:%s;--sp-accent:%s;--sp-radius:%dpx;--sp-card-bg:%s;}',
            htmlspecialchars($settings['design_primary_color']),
            htmlspecialchars($settings['design_accent_color']),
            (int) $settings['design_border_radius'],
            htmlspecialchars($settings['design_card_bg'])
        );
        echo '<style>' . $cssVars . '</style>';
        ?>

        <?php if ($error): ?>
        <div class="member-alert member-alert-error">
            <span class="alert-icon">✕</span>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php else: ?>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
            <p style="color:#64748b;font-size:.875rem;margin:0;">
                <?php echo count($speakers); ?> Speaker verfügbar
            </p>
            <a href="/member/plugin/speakers?action=new" class="btn btn-primary">
                ➕ Neuer Speaker
            </a>
        </div>

        <?php if (empty($speakers)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0 0 .75rem;">🎤</p>
            <p><strong>Keine Speaker vorhanden</strong></p>
            <p style="color:#64748b;margin:.25rem 0 0;">Es sind noch keine aktiven Speaker-Profile vorhanden.</p>
            <a href="/member/plugin/speakers?action=new" class="btn btn-primary" style="margin-top:1rem;">
                ➕ Ersten Speaker anlegen
            </a>
        </div>
        <?php else: ?>
        <div class="sp-grid">
            <?php foreach ($speakers as $speaker): ?>
                <?php
                if (class_exists('CMS_Speakers_Template_Loader')) {
                    $topics = $speaker->_topics ?? [];
                    echo CMS_Speakers_Template_Loader::instance()->render_speaker_card($speaker, $settings, $topics);
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
        $csrfToken = \CMS\Security::instance()->generateToken('member_speaker_create');
        $isAdmin   = \CMS\Auth::instance()->isAdmin();
        ?>
        <div style="margin-bottom:1rem;">
            <a href="/member/plugin/speakers" class="btn btn-secondary btn-sm">
                ← Zurück zur Übersicht
            </a>
        </div>

        <?php if (!$isAdmin): ?>
        <div class="alert" style="background:#f5f3ff;color:#5b21b6;border-left:4px solid #7c3aed;">
            ℹ️ Ihr Profil wird nach dem Einreichen vom Admin geprüft und dann freigeschaltet.
        </div>
        <?php endif; ?>

        <div class="admin-card">
            <h3>🎤 Neues Speaker-Profil anlegen</h3>

            <form method="POST" action="/member/plugin/speakers?action=new">
                <input type="hidden" name="speaker_create" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <!-- Persönliche Daten -->
                <h4 style="color:#475569;font-size:.95rem;margin:0 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">👤 Persönliche Daten</h4>

                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Titel/Anrede</label>
                        <input type="text" name="title" class="form-control" placeholder="Dr., Prof., ..."
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Geschlecht</label>
                        <select name="gender" class="form-control">
                            <option value="">-- wählen --</option>
                            <option value="m" <?php echo ($_POST['gender'] ?? '') === 'm' ? 'selected' : ''; ?>>Männlich</option>
                            <option value="f" <?php echo ($_POST['gender'] ?? '') === 'f' ? 'selected' : ''; ?>>Weiblich</option>
                            <option value="d" <?php echo ($_POST['gender'] ?? '') === 'd' ? 'selected' : ''; ?>>Divers</option>
                        </select>
                    </div>
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

                <!-- Kontakt & Standort -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">📞 Kontakt & Standort</h4>

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
                        <label class="form-label">Stadt</label>
                        <input type="text" name="location_city" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['location_city'] ?? ''); ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
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
                    <div class="form-group">
                        <label class="form-label">Sprachen</label>
                        <input type="text" name="languages" class="form-control" placeholder="Deutsch, Englisch"
                               value="<?php echo htmlspecialchars($_POST['languages'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Social Media -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">🔗 Social Media & Website</h4>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Website</label>
                        <input type="url" name="website" class="form-control" placeholder="https://"
                               value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">LinkedIn</label>
                        <input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/..."
                               value="<?php echo htmlspecialchars($_POST['linkedin'] ?? ''); ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">XING</label>
                        <input type="url" name="xing" class="form-control" placeholder="https://xing.com/profile/..."
                               value="<?php echo htmlspecialchars($_POST['xing'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Twitter / X</label>
                        <input type="url" name="twitter" class="form-control" placeholder="https://twitter.com/..."
                               value="<?php echo htmlspecialchars($_POST['twitter'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Vortragsprofil -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">🎤 Vortragsprofil</h4>

                <!-- Vortragsformate als Checkboxen -->
                <div class="form-group">
                    <label class="form-label">Vortragsformate</label>
                    <div style="display:flex;flex-wrap:wrap;gap:.5rem;padding:.5rem;border:1px solid #e2e8f0;border-radius:8px;background:#fafcff;">
                        <?php
                        $spkFormats = [
                            'keynote'    => '🎤 Keynote',
                            'workshop'   => '🛠️ Workshop',
                            'panel'      => '💬 Podiumsdiskussion',
                            'moderation' => '🎤 Moderation',
                            'training'   => '📚 Training',
                            'consulting' => '🤝 Beratung',
                            'interview'  => '🎥 Interview',
                            'webinar'    => '💻 Webinar',
                        ];
                        $postedFmts = (array)($_POST['formats'] ?? []);
                        foreach ($spkFormats as $val => $lbl): ?>
                        <label style="display:inline-flex;align-items:center;gap:.35rem;padding:.3rem .7rem;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;font-size:.875rem;">
                            <input type="checkbox" name="formats[]" value="<?php echo $val; ?>"
                                   <?php echo in_array($val, $postedFmts) ? 'checked' : ''; ?> style="accent-color:#7c3aed;">
                            <?php echo $lbl; ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <small class="form-text">Mehrfachauswahl möglich.</small>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Vortragsstil</label>
                        <input type="text" name="speaking_style" class="form-control" placeholder="inspirierend, interaktiv, ..."
                               value="<?php echo htmlspecialchars($_POST['speaking_style'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Zielgruppe</label>
                        <input type="text" name="target_audience" class="form-control" placeholder="Führungskräfte, Entwickler, ..."
                               value="<?php echo htmlspecialchars($_POST['target_audience'] ?? ''); ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Reisebereitschaft</label>
                        <select name="travel_radius" class="form-control">
                            <?php
                            $travelOpts = [
                                'local'         => '📍 Lokal (50 km)',
                                'regional'      => '🗺️ Regional (Bundesland)',
                                'national'      => '🇪🇨 National (DACH)',
                                'international' => '🌍 International (Europa)',
                                'worldwide'     => '🌐 Weltweit',
                            ];
                            $postedTravel = $_POST['travel_radius'] ?? 'national';
                            foreach ($travelOpts as $tv => $tl): ?>
                                <option value="<?php echo $tv; ?>" <?php echo $postedTravel === $tv ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($tl); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max. Publikumsgröße</label>
                        <input type="number" name="max_audience" class="form-control" min="0" step="50"
                               value="<?php echo htmlspecialchars($_POST['max_audience'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Verfügbarkeit</label>
                        <select name="availability" class="form-control">
                            <option value="available" <?php echo ($_POST['availability'] ?? 'available') === 'available' ? 'selected' : ''; ?>>Verfügbar</option>
                            <option value="limited"   <?php echo ($_POST['availability'] ?? '') === 'limited'            ? 'selected' : ''; ?>>Begrenzt verfügbar</option>
                            <option value="booked"    <?php echo ($_POST['availability'] ?? '') === 'booked'             ? 'selected' : ''; ?>>Ausgebucht</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Honorar ab (€)</label>
                        <input type="number" name="fee_min" class="form-control" min="0" step="100"
                               value="<?php echo htmlspecialchars($_POST['fee_min'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Honorar bis (€)</label>
                        <input type="number" name="fee_max" class="form-control" min="0" step="100"
                               value="<?php echo htmlspecialchars($_POST['fee_max'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Skills -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">🛠️ Speaker Skills</h4>
                <p style="color:#64748b;font-size:.8rem;margin:0 0 .75rem;">Technische Schwerpunkte und Kompetenzen auswählen – erscheinen als Pills auf der Speaker-Card.</p>
                <?php
                $spkSkillGroups = [
                    'tech' => [
                        'label' => '💻 Technologie & Digitalisierung',
                        'items' => [
                            'ai_ml' => 'KI & Machine Learning', 'cloud' => 'Cloud Computing',
                            'cybersecurity' => 'Cybersecurity', 'blockchain' => 'Blockchain',
                            'iot' => 'Internet of Things', 'data_analytics' => 'Data Analytics',
                            'devops' => 'DevOps & Agile', 'software_dev' => 'Software-Entwicklung',
                        ],
                    ],
                    'business' => [
                        'label' => '💼 Business & Führung',
                        'items' => [
                            'leadership' => 'Führung & Leadership', 'strategy' => 'Strategie',
                            'innovation' => 'Innovation', 'transformation' => 'Digitale Transformation',
                            'entrepreneurship' => 'Entrepreneurship', 'sales' => 'Vertrieb & Marketing',
                            'hr' => 'HR & People Management', 'finance' => 'Finance & Controlling',
                        ],
                    ],
                    'personal' => [
                        'label' => '🌱 Persönlichkeit & Gesellschaft',
                        'items' => [
                            'communication' => 'Kommunikation', 'mindfulness' => 'Achtsamkeit',
                            'diversity' => 'Diversity & Inclusion', 'sustainability' => 'Nachhaltigkeit',
                            'future_work' => 'Future of Work', 'health' => 'Gesundheit & Work-Life-Balance',
                        ],
                    ],
                ];
                $postedSkills = (array)($_POST['skills'] ?? []);
                foreach ($spkSkillGroups as $sgKey => $sg):
                ?>
                <div style="margin-bottom:.875rem;">
                    <strong style="font-size:.82rem;color:#475569;text-transform:uppercase;letter-spacing:.04em;"><?php echo $sg['label']; ?></strong>
                    <div style="display:flex;flex-wrap:wrap;gap:.4rem .875rem;margin-top:.4rem;">
                        <?php foreach ($sg['items'] as $sVal => $sLbl): ?>
                        <label style="display:inline-flex;align-items:center;gap:.35rem;font-size:.875rem;cursor:pointer;min-width:180px;">
                            <input type="checkbox" name="skills[]" value="<?php echo $sVal; ?>"
                                   <?php echo in_array($sVal, $postedSkills) ? 'checked' : ''; ?> style="accent-color:#5e72e4;">
                            <?php echo htmlspecialchars($sLbl); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Auszeichnungen & Programme -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">🏅 Auszeichnungen & Programme</h4>
                <p style="color:#64748b;font-size:.8rem;margin:0 0 .75rem;">Offizielle Community-Programme und Ehrungen auswählen.</p>
                <?php
                $spkRecognitions = [
                    'microsoft_mvp'         => 'Microsoft MVP',
                    'google_gde'            => 'Google Developer Expert (GDE)',
                    'aws_community_hero'    => 'AWS Community Hero',
                    'aws_community_builder' => 'AWS Community Builder',
                    'docker_captain'        => 'Docker Captain',
                    'github_star'           => 'GitHub Star',
                    'cncf_ambassador'       => 'CNCF Ambassador',
                    'tedx_speaker'          => 'TEDx Speaker',
                    'ted_speaker'           => 'TED Speaker',
                    'speaker_of_year'       => 'Speaker of the Year',
                    'linkedin_top_voice'    => 'LinkedIn Top Voice',
                    'forbes_30u30'          => 'Forbes 30 under 30',
                    'forbes_40u40'          => 'Forbes 40 under 40',
                    'honorary_professor'    => 'Honorarprofessor/-in',
                ];
                $postedRec = (array)($_POST['recognitions'] ?? []);
                ?>
                <div style="display:flex;flex-wrap:wrap;gap:.4rem .875rem;">
                    <?php foreach ($spkRecognitions as $rVal => $rLbl): ?>
                    <label style="display:inline-flex;align-items:center;gap:.35rem;font-size:.875rem;cursor:pointer;min-width:220px;">
                        <input type="checkbox" name="recognitions[]" value="<?php echo $rVal; ?>"
                               <?php echo in_array($rVal, $postedRec) ? 'checked' : ''; ?> style="accent-color:#8b5cf6;">
                        <?php echo htmlspecialchars($rLbl); ?>
                    </label>
                    <?php endforeach; ?>
                </div>

                <!-- Themen / Topics -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">🏷️ Themen / Topics</h4>
                <p style="color:#64748b;font-size:.8rem;margin:0 0 .75rem;">
                    Gib deine Vortragsthemen als Komma-getrennte Liste ein oder nutze die Tag-Eingabe.
                </p>
                <div class="form-group">
                    <label class="form-label">Themen-Tags</label>
                    <div style="display:flex;flex-wrap:wrap;gap:.35rem;padding:.5rem;border:2px solid #e2e8f0;border-radius:8px;min-height:42px;cursor:text;"
                         id="spkTopicWrap" onclick="document.getElementById('spkTopicInput').focus()">
                        <input type="text" id="spkTopicInput" placeholder="z.B. Digitalisierung, KI, Führung …"
                               style="border:none;outline:none;flex:1;min-width:200px;font-size:.875rem;padding:.2rem 0;"
                               onkeydown="handleTopicKeydown(event)">
                    </div>
                    <input type="hidden" name="speaker_topics" id="spkTopicsHidden" value="">
                    <small class="form-text">Drücke Enter oder Komma, um ein Thema hinzuzufügen.</small>
                </div>

                <!-- Biografie -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">📝 Biografie</h4>

                <div class="form-group">
                    <label class="form-label">Kurzvorstellung <small style="color:#94a3b8;">(max. 2-3 Sätze)</small></label>
                    <textarea name="short_bio" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['short_bio'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Ausführliche Biografie</label>
                    <textarea name="bio" class="form-control" rows="6"><?php echo htmlspecialchars($_POST['bio'] ?? ''); ?></textarea>
                </div>

                <div style="display:flex;gap:.75rem;margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary">💾 Profil einreichen</button>
                    <a href="/member/plugin/speakers" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </div>

        <script>
        // Topic-Tag-System für Speaker Member-Dashboard
        const _spkTopics = [];

        function syncTopicHidden() {
            const hidden = document.getElementById('spkTopicsHidden');
            if (hidden) hidden.value = _spkTopics.join(',');
        }

        function renderTopicTags() {
            const wrap = document.getElementById('spkTopicWrap');
            if (!wrap) return;
            wrap.querySelectorAll('.spk-topic-pill').forEach(el => el.remove());
            const input = document.getElementById('spkTopicInput');
            _spkTopics.forEach(function(tag, idx) {
                const pill = document.createElement('span');
                pill.className = 'spk-topic-pill';
                pill.style.cssText = 'display:inline-flex;align-items:center;gap:.25rem;background:#f5f3ff;color:#6d28d9;border-radius:4px;padding:2px 8px;font-size:.8rem;';
                pill.textContent = tag;
                const x = document.createElement('span');
                x.textContent = '×';
                x.style.cssText = 'cursor:pointer;font-weight:700;color:#c4b5fd;margin-left:2px;';
                x.onclick = function() { _spkTopics.splice(idx, 1); renderTopicTags(); };
                pill.appendChild(x);
                wrap.insertBefore(pill, input);
            });
            syncTopicHidden();
        }

        function addTopicTag(name) {
            name = name.trim();
            if (!name || _spkTopics.includes(name)) return;
            _spkTopics.push(name);
            renderTopicTags();
        }

        function handleTopicKeydown(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                var val = e.target.value.replace(/,/g, '').trim();
                if (val) { addTopicTag(val); e.target.value = ''; }
            }
            if (e.key === 'Backspace' && e.target.value === '' && _spkTopics.length) {
                _spkTopics.pop();
                renderTopicTags();
            }
        }
        </script>
        <?php
    }

    // ── Bulk Data Loading ─────────────────────────────────────────────────────

    /**
     * Lädt Topics für alle Speaker in einem Query.
     * Die Daten werden als `_topics` an die Objekte angehängt.
     *
     * @param object[] $speakers  Array von Speaker-Objekten (pass by reference)
     */
    private function bulkLoadTopicData(array &$speakers): void
    {
        if (empty($speakers)) {
            return;
        }

        try {
            $db          = \CMS\Database::instance();
            $prefix      = $db->getPrefix();
            $speakerIds  = array_map(static fn($s) => (int) $s->id, $speakers);
            $placeholders = implode(',', array_fill(0, count($speakerIds), '?'));

            // Topics
            $topicRows = $db->get_results(
                "SELECT speaker_id, topic_name, topic_desc, sort_order
                   FROM {$prefix}speaker_topics
                  WHERE speaker_id IN ({$placeholders})
                  ORDER BY sort_order ASC, topic_name ASC",
                $speakerIds
            );
            $topicsMap = [];
            foreach ($topicRows as $row) {
                $topicsMap[(int)$row->speaker_id][] = $row;
            }

            // An die Speaker-Objekte anhängen
            foreach ($speakers as $speaker) {
                $speaker->_topics = $topicsMap[(int)$speaker->id] ?? [];
            }
        } catch (\Throwable $e) {
            // Kein Fatal – Cards zeigen sich ohne Topics
        }
    }
}

// Bootstrap
CMS_Speakers_Member_Dashboard::instance();
