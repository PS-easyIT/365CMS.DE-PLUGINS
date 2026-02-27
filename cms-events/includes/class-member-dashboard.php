<?php
/**
 * CMS Events – Member Dashboard Integration
 *
 * Registriert den Events-Bereich im Member-Dashboard.
 * Wird geladen von cms-events.php (load_dependencies).
 *
 * URL: /member/plugin/events
 *
 * @package CMS_Events
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Events_Member_Dashboard
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
            \CMS\Hooks::addAction('member_plugin_section_head', [$this, 'enqueueEventStyles'], 10);
        }
    }

    public function enqueueEventStyles(string $slug): void
    {
        if ($slug !== 'events') {
            return;
        }
        $cssFile = defined('CMS_EVENTS_PLUGIN_DIR')
            ? CMS_EVENTS_PLUGIN_DIR . 'assets/css/style.css'
            : '';
        $cssUrl  = defined('CMS_EVENTS_PLUGIN_URL')
            ? CMS_EVENTS_PLUGIN_URL . 'assets/css/style.css'
            : '';

        if ($cssUrl !== '') {
            $v = $cssFile && file_exists($cssFile) ? filemtime($cssFile) : '1';
            echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl) . '?v=' . $v . '">' . "\n";
        }
    }

    public function register(\CMS\Member\PluginDashboardRegistry $registry): void
    {
        $registry->register([
            'plugin'    => 'cms-events',
            'slug'      => 'events',
            'label'     => 'EVENTS',
            'icon'      => '📅',
            'category'  => 'plugins',
            'priority'  => 40,
            'capability'=> null,
            'dashboard_widget' => [
                'title'          => 'EVENTS',
                'description'    => 'Veranstaltungen anlegen, verwalten und bewerben.',
                'color'          => '#dc2626',
                'stats_callback' => [$this, 'getDashboardStats'],
                'link_label'     => 'Zu den Events',
                'admin_url'      => '/admin/events',
                'admin_label'    => '⚙️ Admin',
            ],
            'render_callback' => [$this, 'renderPage'],
        ]);
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function getDashboardStats(object $user): array
    {
        if (!class_exists('CMS_Events_Database')) {
            return ['count' => 0, 'label' => 'Events'];
        }

        $isAdmin = \CMS\Auth::instance()->isAdmin();
        try {
            if ($isAdmin) {
                $count = CMS_Events_Database::instance()->count_events(['status' => 'published']);
                return ['count' => $count, 'label' => 'Events gesamt'];
            } else {
                $userId = (int) ($user->id ?? 0);
                $count  = CMS_Events_Database::instance()->count_events(['status' => 'published', 'user_id' => $userId]);
                return ['count' => $count, 'label' => 'Meine Events'];
            }
        } catch (\Throwable $e) {
            return ['count' => 0, 'label' => 'Events'];
        }
    }

    // ── Page Rendering ────────────────────────────────────────────────────────

    public function renderPage(object $user, array $params = []): void
    {
        // ── POST: neues Event speichern ───────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_create'])) {
            if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'member_event_create')) {
                $_SESSION['error'] = 'Sicherheitscheck fehlgeschlagen.';
                header('Location: /member/plugin/events?action=new');
                exit;
            }
            try {
                $isAdminSave = \CMS\Auth::instance()->isAdmin();
                // save_event() setzt user_id automatisch aus CMS\Auth
                $id = CMS_Events_Database::instance()->save_event([
                    'title'             => sanitize_text_field($_POST['title']      ?? ''),
                    'excerpt'           => strip_tags($_POST['excerpt']             ?? ''),
                    'event_date'        => sanitize_text_field($_POST['event_date'] ?? ''),
                    'event_time'        => sanitize_text_field($_POST['event_time'] ?? ''),
                    'end_date'          => sanitize_text_field($_POST['end_date']   ?? ''),
                    'end_time'          => sanitize_text_field($_POST['end_time']   ?? ''),
                    'location'          => sanitize_text_field($_POST['location']   ?? ''),
                    'address'           => sanitize_text_field($_POST['address']    ?? ''),
                    'city'              => sanitize_text_field($_POST['city']       ?? ''),
                    'zip'               => sanitize_text_field($_POST['zip']        ?? ''),
                    'country'           => sanitize_text_field($_POST['country']    ?? 'Deutschland'),
                    'description'       => strip_tags($_POST['description']         ?? ''),
                    'category'          => sanitize_text_field($_POST['category']   ?? ''),
                    'tags'              => sanitize_text_field($_POST['tags']        ?? '') ?: null,
                    'capacity'          => is_numeric($_POST['capacity'] ?? '') ? (int)$_POST['capacity'] : null,
                    'price_type'        => in_array($_POST['price_type'] ?? '', ['free', 'paid'], true) ? $_POST['price_type'] : 'free',
                    'price'             => is_numeric($_POST['price'] ?? '') ? (float)$_POST['price'] : 0.0,
                    'price_currency'    => sanitize_text_field($_POST['price_currency'] ?? 'EUR'),
                    'is_online'         => isset($_POST['is_online']) ? 1 : 0,
                    'online_url'        => filter_var($_POST['online_url']        ?? '', FILTER_SANITIZE_URL) ?: null,
                    'registration_url'  => filter_var($_POST['registration_url']  ?? '', FILTER_SANITIZE_URL) ?: null,
                    'organizer_name'    => sanitize_text_field($_POST['organizer_name']    ?? ''),
                    'organizer_email'   => filter_var($_POST['organizer_email']   ?? '', FILTER_SANITIZE_EMAIL),
                    'organizer_phone'   => sanitize_text_field($_POST['organizer_phone']   ?? ''),
                    'organizer_website' => filter_var($_POST['organizer_website'] ?? '', FILTER_SANITIZE_URL) ?: null,
                    'status'            => $isAdminSave ? 'published' : 'draft',
                ]);
                if ($isAdminSave) {
                    $_SESSION['success'] = 'Event wurde erfolgreich angelegt.';
                } else {
                    $_SESSION['success'] = 'Ihr Event wurde eingereicht und wird vom Admin geprüft.';
                }
                header('Location: /member/plugin/events');
                exit;
            } catch (\Throwable $e) {
                $_SESSION['error'] = 'Fehler beim Speichern: ' . $e->getMessage();
                header('Location: /member/plugin/events?action=new');
                exit;
            }
        }

        $action  = sanitize_text_field($_GET['action'] ?? '');
        $isAdmin = \CMS\Auth::instance()->isAdmin();

        // ── Formular: Neues Event ─────────────────────────────────────────────
        if ($action === 'new') {
            $this->renderCreateForm($user);
            return;
        }

        // ── Übersicht ─────────────────────────────────────────────────────────
        $events   = [];
        $error    = null;
        $settings = [];

        if (class_exists('CMS_Events_Database')) {
            try {
                $db       = CMS_Events_Database::instance();
                $settings = $db->get_settings();

                if ($isAdmin) {
                    $queryArgs = ['status' => 'published', 'limit' => 60];
                } else {
                    $queryArgs = ['user_id' => (int) ($user->id ?? 0), 'limit' => 60];
                }
                $events = $db->get_events($queryArgs) ?? [];
            } catch (\Throwable $e) {
                $error = 'Daten konnten nicht geladen werden.';
            }
        } else {
            $error = 'Das Events-Plugin ist nicht vollständig installiert.';
        }

        $settings = array_merge([
            'archive_slug'         => 'events',
            'show_price'           => '1',
            'show_tags'            => '1',
            'design_primary_color' => '#dc2626',
            'design_card_bg'       => '#fff',
        ], $settings);

        $cssVars = sprintf(
            ':root{--ev-primary:%s;--ev-card-bg:%s;}',
            htmlspecialchars($settings['design_primary_color'] ?? '#dc2626'),
            htmlspecialchars($settings['design_card_bg'] ?? '#fff')
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
                <?php echo count($events); ?> Event(s) verfügbar
            </p>
            <a href="/member/plugin/events?action=new"
               style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.25rem;
                      background:#dc2626;color:#fff;border-radius:8px;font-weight:600;
                      font-size:.875rem;text-decoration:none;">
                ➕ Neues Event
            </a>
        </div>

        <?php if (empty($events)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0 0 .75rem;">📅</p>
            <p><strong>Keine Events vorhanden</strong></p>
            <p style="color:#64748b;margin:.25rem 0 0;">Es sind noch keine Events vorhanden.</p>
            <a href="/member/plugin/events?action=new" class="btn btn-primary" style="margin-top:1rem;">
                ➕ Erstes Event anlegen
            </a>
        </div>
        <?php else: ?>
        <div class="ev-grid" style="grid-template-columns:repeat(3,1fr);">
            <?php foreach ($events as $event): ?>
                <?php
                if (class_exists('CMS_Events_Template_Loader')) {
                    CMS_Events_Template_Loader::instance()->render_template('event-card', [
                        'event'    => $event,
                        'settings' => $settings,
                        'db'       => CMS_Events_Database::instance(),
                    ]);
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
        $csrfToken = \CMS\Security::instance()->generateToken('member_event_create');
        $isAdmin   = \CMS\Auth::instance()->isAdmin();
        ?>
        <div style="margin-bottom:1rem;">
            <a href="/member/plugin/events" style="color:#dc2626;font-size:.875rem;text-decoration:none;">
                ← Zurück zur Übersicht
            </a>
        </div>

        <?php if (!$isAdmin): ?>
        <div style="background:#fef2f2;border-left:4px solid #dc2626;color:#991b1b;
                    padding:1rem 1.25rem;border-radius:6px;margin-bottom:1.25rem;font-size:.9rem;">
            <strong>ℹ️ Hinweis:</strong> Ihr Profil wird nach dem Einreichen vom Admin geprüft und dann freigeschaltet.
        </div>
        <?php endif; ?>

        <div class="admin-card">
            <h3>📅 Neues Event einreichen</h3>

            <form method="POST" action="/member/plugin/events?action=new">
                <input type="hidden" name="event_create" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <!-- Veranstaltung -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">📅 Veranstaltung</h4>

                <div class="form-group">
                    <label class="form-label">Titel <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Kurzbeschreibung</label>
                    <input type="text" name="excerpt" class="form-control"
                           placeholder="Kurzer Teaser-Text für Suchergebnisse und Karten"
                           value="<?php echo htmlspecialchars($_POST['excerpt'] ?? ''); ?>">
                </div>

                <!-- Datum & Zeit -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">🕐 Datum & Zeit</h4>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Startdatum <span style="color:#ef4444;">*</span></label>
                        <input type="date" name="event_date" class="form-control" required
                               value="<?php echo htmlspecialchars($_POST['event_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Startzeit</label>
                        <input type="time" name="event_time" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['event_time'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Enddatum</label>
                        <input type="date" name="end_date" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['end_date'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Endzeit</label>
                        <input type="time" name="end_time" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['end_time'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Ort -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">📍 Ort</h4>

                <div class="form-group">
                    <label class="form-label" style="display:flex;align-items:center;gap:.5rem;cursor:pointer;">
                        <input type="checkbox" name="is_online" value="1"
                               <?php echo isset($_POST['is_online']) ? 'checked' : ''; ?>
                               onchange="document.getElementById('ev-online-wrap').style.display=this.checked?'block':'none';
                                         document.getElementById('ev-location-wrap').style.display=this.checked?'none':'block';">
                        Online-Event (kein physischer Veranstaltungsort)
                    </label>
                </div>

                <div id="ev-online-wrap" class="form-group" style="display:<?php echo isset($_POST['is_online']) ? 'block' : 'none'; ?>;">
                    <label class="form-label">Online-Link</label>
                    <input type="url" name="online_url" class="form-control" placeholder="https://"
                           value="<?php echo htmlspecialchars($_POST['online_url'] ?? ''); ?>">
                </div>

                <div id="ev-location-wrap" style="display:<?php echo isset($_POST['is_online']) ? 'none' : 'block'; ?>;">
                    <div class="form-group">
                        <label class="form-label">Veranstaltungsort (Name)</label>
                        <input type="text" name="location" class="form-control"
                               placeholder="z. B. Messezentrum, Kongresshalle"
                               value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adresse (Straße & Hausnummer)</label>
                        <input type="text" name="address" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>">
                    </div>
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                        <div class="form-group">
                            <label class="form-label">Stadt</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">PLZ</label>
                            <input type="text" name="zip" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['zip'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Land</label>
                            <input type="text" name="country" class="form-control"
                                   value="<?php echo htmlspecialchars($_POST['country'] ?? 'Deutschland'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">ℹ️ Details</h4>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Kategorie</label>
                        <input type="text" name="category" class="form-control"
                               placeholder="Messe, Konferenz, Workshop …"
                               value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tags</label>
                        <input type="text" name="tags" class="form-control"
                               placeholder="Kommagetrennt: KI, Business, Marketing"
                               value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Kapazität (Plätze)</label>
                        <input type="number" name="capacity" class="form-control" min="0"
                               value="<?php echo htmlspecialchars($_POST['capacity'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Anmeldungslink</label>
                        <input type="url" name="registration_url" class="form-control" placeholder="https://"
                               value="<?php echo htmlspecialchars($_POST['registration_url'] ?? ''); ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Preis-Typ</label>
                        <select name="price_type" class="form-control" id="ev-price-type"
                                onchange="document.getElementById('ev-price-wrap').style.display=this.value==='paid'?'grid':'none'">
                            <option value="free" <?php echo ($_POST['price_type'] ?? 'free') === 'free' ? 'selected' : ''; ?>>Kostenlos</option>
                            <option value="paid" <?php echo ($_POST['price_type'] ?? '') === 'paid' ? 'selected' : ''; ?>>Kostenpflichtig</option>
                        </select>
                    </div>
                    <div id="ev-price-wrap" style="display:<?php echo ($_POST['price_type'] ?? 'free') === 'paid' ? 'grid' : 'none'; ?>;grid-column:span 2;grid-template-columns:1fr 1fr;gap:1.25rem;">
                        <div class="form-group">
                            <label class="form-label">Preis</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01"
                                   value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Währung</label>
                            <input type="text" name="price_currency" class="form-control"
                                   placeholder="EUR" maxlength="3"
                                   value="<?php echo htmlspecialchars($_POST['price_currency'] ?? 'EUR'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Veranstalter -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">👤 Veranstalter</h4>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="organizer_name" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['organizer_name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-Mail</label>
                        <input type="email" name="organizer_email" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['organizer_email'] ?? ''); ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="organizer_phone" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['organizer_phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website</label>
                        <input type="url" name="organizer_website" class="form-control" placeholder="https://"
                               value="<?php echo htmlspecialchars($_POST['organizer_website'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Beschreibung -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">📝 Beschreibung</h4>

                <div class="form-group">
                    <textarea name="description" class="form-control" rows="6"
                              style="resize:vertical;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div style="display:flex;gap:.75rem;margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary">💾 Event einreichen</button>
                    <a href="/member/plugin/events" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </div>
        <?php
    }
}

// Bootstrap
CMS_Events_Member_Dashboard::instance();
