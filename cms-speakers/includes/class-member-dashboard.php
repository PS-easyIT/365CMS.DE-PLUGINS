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
                $count = (int) CMS_Speakers_Database::instance()->count_speakers(['status' => 'active']);
                return ['count' => $count, 'label' => 'Speaker gesamt'];
            } else {
                $userId = (int) ($user->id ?? 0);
                $count  = (int) CMS_Speakers_Database::instance()->count_speakers(['status' => 'active', 'user_id' => $userId]);
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
                $id = CMS_Speakers_Database::instance()->save_speaker([
                    'user_id'           => (int) $user->id,
                    'first_name'        => sanitize_text_field($_POST['first_name']  ?? ''),
                    'last_name'         => sanitize_text_field($_POST['last_name']   ?? ''),
                    'title'             => sanitize_text_field($_POST['title']       ?? ''),
                    'gender'            => sanitize_text_field($_POST['gender']      ?? ''),
                    'email'             => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
                    'phone'             => sanitize_text_field($_POST['phone']       ?? ''),
                    'position'          => sanitize_text_field($_POST['position']    ?? ''),
                    'company'           => sanitize_text_field($_POST['company']     ?? ''),
                    'photo_url'         => filter_var($_POST['photo_url'] ?? '', FILTER_SANITIZE_URL) ?: null,
                    'location_city'     => sanitize_text_field($_POST['location_city']     ?? ''),
                    'location_zip'      => sanitize_text_field($_POST['location_zip']      ?? ''),
                    'location_country'  => sanitize_text_field($_POST['location_country']  ?? 'Deutschland'),
                    'website'           => filter_var($_POST['website']   ?? '', FILTER_SANITIZE_URL) ?: null,
                    'linkedin'          => filter_var($_POST['linkedin']  ?? '', FILTER_SANITIZE_URL) ?: null,
                    'twitter'           => filter_var($_POST['twitter']   ?? '', FILTER_SANITIZE_URL) ?: null,
                    'xing'              => filter_var($_POST['xing']      ?? '', FILTER_SANITIZE_URL) ?: null,
                    'languages'         => sanitize_text_field($_POST['languages']   ?? ''),
                    'formats'           => sanitize_text_field($_POST['formats']     ?? ''),
                    'target_audience'   => sanitize_text_field($_POST['target_audience'] ?? ''),
                    'speaking_style'    => sanitize_text_field($_POST['speaking_style']  ?? ''),
                    'travel_radius'     => is_numeric($_POST['travel_radius'] ?? '') ? (int)$_POST['travel_radius'] : null,
                    'max_audience_size' => is_numeric($_POST['max_audience'] ?? '')  ? (int)$_POST['max_audience']  : null,
                    'availability'      => sanitize_text_field($_POST['availability'] ?? 'available'),
                    'speaking_fee_min'  => is_numeric($_POST['fee_min'] ?? '') ? (float)$_POST['fee_min'] : null,
                    'speaking_fee_max'  => is_numeric($_POST['fee_max'] ?? '') ? (float)$_POST['fee_max'] : null,
                    'short_bio'         => strip_tags($_POST['short_bio'] ?? ''),
                    'bio'               => strip_tags($_POST['bio']       ?? ''),
                    'status'            => $isAdminSave ? 'active' : 'pending',
                ]);
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
                    $queryArgs = ['status' => 'active', 'limit' => 60];
                } else {
                    // Eigene Einträge aller Status zeigen (inkl. pending)
                    $queryArgs = ['user_id' => (int) ($user->id ?? 0), 'limit' => 60];
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

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Vortragsformate <small style="color:#94a3b8;">(Keynote, Workshop, ...)</small></label>
                        <input type="text" name="formats" class="form-control" placeholder="Keynote, Workshop, Moderation"
                               value="<?php echo htmlspecialchars($_POST['formats'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vortragsstil</label>
                        <input type="text" name="speaking_style" class="form-control" placeholder="inspirierend, interaktiv, ..."
                               value="<?php echo htmlspecialchars($_POST['speaking_style'] ?? ''); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Zielgruppe</label>
                    <input type="text" name="target_audience" class="form-control" placeholder="Führungskräfte, Entwickler, ..."
                           value="<?php echo htmlspecialchars($_POST['target_audience'] ?? ''); ?>">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Reiseradius (km)</label>
                        <input type="number" name="travel_radius" class="form-control" min="0" step="50"
                               value="<?php echo htmlspecialchars($_POST['travel_radius'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Max. Publikumsgröße</label>
                        <input type="number" name="max_audience" class="form-control" min="0" step="50"
                               value="<?php echo htmlspecialchars($_POST['max_audience'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Verfügbarkeit</label>
                        <select name="availability" class="form-control">
                            <option value="available"  <?php echo ($_POST['availability'] ?? 'available') === 'available'  ? 'selected' : ''; ?>>Verfügbar</option>
                            <option value="partially"  <?php echo ($_POST['availability'] ?? '') === 'partially'            ? 'selected' : ''; ?>>Teilweise</option>
                            <option value="unavailable" <?php echo ($_POST['availability'] ?? '') === 'unavailable'         ? 'selected' : ''; ?>>Nicht verfügbar</option>
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
