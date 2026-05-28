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

if (class_exists('CMS_Events_Member_Dashboard', false)) {
    return;
}

final class CMS_Events_Member_Dashboard
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
        if (class_exists('CMS\\Hooks')) {
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
            if (function_exists('cms_enqueue_style')) {
                cms_enqueue_style('cms-events-member', $cssUrl, [], (string) $v);
                return;
            }

            echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl . '?v=' . $v, ENT_QUOTES, 'UTF-8') . '">' . "\n";
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
                $count  = CMS_Events_Database::instance()->count_events(['user_id' => $userId]);
                return ['count' => $count, 'label' => 'Meine Events'];
            }
        } catch (\Throwable $e) {
            return ['count' => 0, 'label' => 'Events'];
        }
    }

    // ── Page Rendering ────────────────────────────────────────────────────────

    public function renderPage(object $user, array $params = []): void
    {
        if (class_exists('CMS\\Auth') && method_exists(\CMS\Auth::instance(), 'isLoggedIn') && !\CMS\Auth::instance()->isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        // ── POST: neues Event speichern ───────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_create'])) {
            if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'member_event_create')) {
                $_SESSION['error'] = 'Sicherheitscheck fehlgeschlagen.';
                $this->redirect('/member/plugin/events?action=new');
                return;
            }
            try {
                $isAdminSave = \CMS\Auth::instance()->isAdmin();
                $allowedPriceTypes = ['free', 'paid', 'donation'];
                $normalizedTags = isset($_POST['tags']) && is_array($_POST['tags'])
                    ? array_values(array_unique(array_filter(array_map(static fn($tag) => sanitize_text_field(trim((string) $tag)), $_POST['tags']))))
                    : [];
                $onlineUrl = function_exists('cms_events_public_url') ? (cms_events_public_url($_POST['online_url'] ?? '') ?: null) : null;
                $registrationUrl = function_exists('cms_events_public_url') ? (cms_events_public_url($_POST['registration_url'] ?? '') ?: null) : null;
                $organizerWebsite = function_exists('cms_events_public_url') ? (cms_events_public_url($_POST['organizer_website'] ?? '') ?: null) : null;
                $organizerEmail = filter_var(trim((string) ($_POST['organizer_email'] ?? '')), FILTER_VALIDATE_EMAIL) ?: '';
                $priceCurrency = strtoupper(substr(sanitize_text_field($_POST['price_currency'] ?? 'EUR'), 0, 10));
                if ($priceCurrency === '') {
                    $priceCurrency = 'EUR';
                }
                $title = sanitize_text_field($_POST['title'] ?? '');
                $eventDate = $this->sanitizeDate($_POST['event_date'] ?? '');
                if ($title === '' || $eventDate === null) {
                    $_SESSION['error'] = 'Bitte mindestens Titel und Startdatum ausfüllen.';
                    $this->redirect('/member/plugin/events?action=new');
                    return;
                }

                // save_event() setzt user_id automatisch aus CMS\Auth
                $id = CMS_Events_Database::instance()->save_event([
                    'title'             => $title,
                    'excerpt'           => strip_tags($_POST['excerpt']             ?? ''),
                    'event_date'        => $eventDate,
                    'event_time'        => $this->sanitizeTime($_POST['event_time'] ?? ''),
                    'end_date'          => $this->sanitizeDate($_POST['end_date']   ?? ''),
                    'end_time'          => $this->sanitizeTime($_POST['end_time']   ?? ''),
                    'location'          => sanitize_text_field($_POST['location']   ?? ''),
                    'address'           => sanitize_text_field($_POST['address']    ?? ''),
                    'city'              => sanitize_text_field($_POST['city']       ?? ''),
                    'zip'               => sanitize_text_field($_POST['zip']        ?? ''),
                    'country'           => sanitize_text_field($_POST['country']    ?? 'Deutschland'),
                    'description'       => strip_tags($_POST['description']         ?? ''),
                    'category'          => sanitize_text_field($_POST['category']   ?? ''),
                    'tags'              => $normalizedTags,
                    'capacity'          => is_numeric($_POST['capacity'] ?? '') ? (int)$_POST['capacity'] : null,
                    'price_type'        => in_array($_POST['price_type'] ?? '', $allowedPriceTypes, true) ? $_POST['price_type'] : 'free',
                    'price'             => is_numeric($_POST['price'] ?? '') ? (float)$_POST['price'] : null,
                    'price_currency'    => $priceCurrency,
                    'is_online'         => isset($_POST['is_online']) ? 1 : 0,
                    'online_url'        => $onlineUrl,
                    'registration_url'  => $registrationUrl,
                    'organizer_name'    => sanitize_text_field($_POST['organizer_name']    ?? ''),
                    'organizer_email'   => $organizerEmail,
                    'organizer_phone'   => sanitize_text_field($_POST['organizer_phone']   ?? ''),
                    'organizer_website' => $organizerWebsite,
                    'status'            => $isAdminSave ? 'published' : 'draft',
                ]);
                if ($id <= 0) {
                    $_SESSION['error'] = 'Das Event konnte nicht gespeichert werden. Bitte Eingaben prüfen.';
                    $this->redirect('/member/plugin/events?action=new');
                    return;
                }
                if ($isAdminSave) {
                    $_SESSION['success'] = 'Event wurde erfolgreich angelegt.';
                } else {
                    $_SESSION['success'] = 'Ihr Event wurde eingereicht und wird vom Admin geprüft.';
                }
                $this->redirect('/member/plugin/events');
                return;
            } catch (\Throwable $e) {
                error_log('CMS Events member create error: ' . $e->getMessage());
                $_SESSION['error'] = 'Fehler beim Speichern. Bitte später erneut versuchen.';
                $this->redirect('/member/plugin/events?action=new');
                return;
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
            'color_primary'        => '#dc2626',
            'color_card_bg'        => '#ffffff',
        ], $settings);

        $primaryColor = $this->sanitizeHexColor((string) ($settings['color_primary'] ?? '#dc2626'), '#dc2626');
        $cardColor = $this->sanitizeHexColor((string) ($settings['color_card_bg'] ?? '#ffffff'), '#ffffff');
        $cssVars = sprintf(
            ':root{--ev-primary:%s;--ev-card-bg:%s;}',
            htmlspecialchars($primaryColor, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($cardColor, ENT_QUOTES, 'UTF-8')
        );
        echo '<style>' . $cssVars . '</style>';
        ?>

        <?php if ($error): ?>
        <div class="member-alert member-alert-error">
            <span class="alert-icon">✕</span>
            <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>
        <?php else: ?>

        <div class="ev-member-toolbar">
            <p class="ev-member-toolbar-note">
                <?php echo count($events); ?> Event(s) verfügbar
            </p>
            <a href="/member/plugin/events?action=new"
               class="ev-member-new-link">
                Neues Event
            </a>
        </div>

        <?php if (empty($events)): ?>
        <div class="empty-state">
            <p><strong>Keine Events vorhanden</strong></p>
            <p class="ev-member-empty-text">Es sind noch keine Events vorhanden.</p>
            <a href="/member/plugin/events?action=new" class="btn btn-primary ev-member-empty-cta">
                Erstes Event anlegen
            </a>
        </div>
        <?php else: ?>
        <div class="ev-grid">
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
        $csrfToken  = \CMS\Security::instance()->generateToken('member_event_create');
        $isAdmin    = \CMS\Auth::instance()->isAdmin();
        $evDb       = CMS_Events_Database::instance();
        $categories = $evDb->get_event_categories();
        $tagGroups  = $evDb->get_event_tag_presets_grouped();
        ?>
        <div class="ev-member-back-wrap">
            <a href="/member/plugin/events" class="ev-member-back-link">
                ← Zurück zur Übersicht
            </a>
        </div>

        <?php if (!$isAdmin): ?>
        <div class="ev-member-warning-box">
            <strong>ℹ️ Hinweis:</strong> Ihr Profil wird nach dem Einreichen vom Admin geprüft und dann freigeschaltet.
        </div>
        <?php endif; ?>

        <div class="admin-card">
            <h3>📅 Neues Event einreichen</h3>

            <form method="POST" action="/member/plugin/events?action=new" novalidate>
                <input type="hidden" name="event_create" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                <!-- Veranstaltung -->
                <h4 class="ev-member-section-title">📅 Veranstaltung</h4>

                <div class="form-group">
                    <label class="form-label">Titel <span class="ev-member-required">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           value="<?php echo htmlspecialchars((string)($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Kurzbeschreibung</label>
                    <input type="text" name="excerpt" class="form-control"
                           placeholder="Kurzer Teaser-Text für Suchergebnisse und Karten"
                           value="<?php echo htmlspecialchars((string)($_POST['excerpt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <!-- Datum & Zeit -->
                <h4 class="ev-member-section-title">🕐 Datum & Zeit</h4>

                <div class="ev-member-grid-4">
                    <div class="form-group">
                        <label class="form-label">Startdatum <span class="ev-member-required">*</span></label>
                        <input type="date" name="event_date" class="form-control" required
                               value="<?php echo htmlspecialchars((string)($_POST['event_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Startzeit</label>
                        <input type="time" name="event_time" class="form-control"
                               value="<?php echo htmlspecialchars((string)($_POST['event_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Enddatum</label>
                        <input type="date" name="end_date" class="form-control"
                               value="<?php echo htmlspecialchars((string)($_POST['end_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Endzeit</label>
                        <input type="time" name="end_time" class="form-control"
                               value="<?php echo htmlspecialchars((string)($_POST['end_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <!-- Ort -->
                <h4 class="ev-member-section-title">📍 Ort</h4>

                <div class="form-group">
                    <label class="form-label ev-member-checkbox-label">
                        <input type="checkbox" name="is_online" value="1"
                               <?php echo isset($_POST['is_online']) ? 'checked' : ''; ?>
                               data-ev-member-online-toggle>
                        Online-Event (kein physischer Veranstaltungsort)
                    </label>
                </div>

                <div id="ev-online-wrap" class="form-group"<?php echo isset($_POST['is_online']) ? '' : ' hidden'; ?>>
                    <label class="form-label">Online-Link</label>
                    <input type="url" name="online_url" class="form-control" placeholder="https://"
                           value="<?php echo htmlspecialchars((string)($_POST['online_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div id="ev-location-wrap"<?php echo isset($_POST['is_online']) ? ' hidden' : ''; ?>>
                    <div class="form-group">
                        <label class="form-label">Veranstaltungsort (Name)</label>
                        <input type="text" name="location" class="form-control"
                               placeholder="z. B. Messezentrum, Kongresshalle"
                               value="<?php echo htmlspecialchars((string)($_POST['location'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Adresse (Straße & Hausnummer)</label>
                        <input type="text" name="address" class="form-control"
                               value="<?php echo htmlspecialchars((string)($_POST['address'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="ev-member-grid-3">
                        <div class="form-group">
                            <label class="form-label">Stadt</label>
                            <input type="text" name="city" class="form-control"
                                   value="<?php echo htmlspecialchars((string)($_POST['city'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">PLZ</label>
                            <input type="text" name="zip" class="form-control"
                                   value="<?php echo htmlspecialchars((string)($_POST['zip'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Land</label>
                            <input type="text" name="country" class="form-control"
                                   value="<?php echo htmlspecialchars((string)($_POST['country'] ?? 'Deutschland'), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <h4 class="ev-member-section-title">ℹ️ Details</h4>

                <div class="ev-member-grid-2">
                    <div class="form-group">
                        <label class="form-label">Kategorie</label>
                        <select name="category" class="form-control">
                            <option value="">– bitte wählen –</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars((string)$cat->name, ENT_QUOTES, 'UTF-8'); ?>"
                                    <?php echo (($_POST['category'] ?? '') === $cat->name) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)(($cat->icon ?? '') . ' ' . $cat->name), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Kapazität (Plätze)</label>
                        <input type="number" name="capacity" class="form-control" min="0"
                               value="<?php echo htmlspecialchars((string)($_POST['capacity'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <!-- Tags / Merkmale -->
                <?php
                $hasPresets = false;
                foreach ($tagGroups as $g) { if (!empty($g)) { $hasPresets = true; break; } }
                if ($hasPresets):
                    $typeLabels = ['general' => 'Allgemein', 'special' => 'Spezialisierung', 'format' => 'Format'];
                    $postedTags = $_POST['tags'] ?? [];
                ?>
                <h4 class="ev-member-section-title">🏷️ Tags / Merkmale</h4>
                <p class="ev-member-tag-note">Wähle passende Tags für dein Event aus den Vorlagen.</p>
                <?php foreach ($tagGroups as $type => $presets):
                    if (empty($presets)) continue; ?>
                    <div class="ev-member-tag-group">
                        <strong class="ev-member-tag-group-title"><?php echo htmlspecialchars((string)($typeLabels[$type] ?? ucfirst((string)$type)), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <div class="ev-member-tag-wrap">
                            <?php foreach ($presets as $preset): ?>
                                <label class="ev-member-tag-option">
                                    <input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars((string)$preset->tag_name, ENT_QUOTES, 'UTF-8'); ?>"
                                           <?php echo is_array($postedTags) && in_array($preset->tag_name, $postedTags) ? 'checked' : ''; ?>>
                                    <?php echo htmlspecialchars((string)$preset->tag_name, ENT_QUOTES, 'UTF-8'); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label">Anmeldungslink</label>
                    <input type="url" name="registration_url" class="form-control" placeholder="https://"
                           value="<?php echo htmlspecialchars((string)($_POST['registration_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="ev-member-grid-3">
                    <div class="form-group">
                        <label class="form-label">Preis-Typ</label>
                        <select name="price_type" class="form-control" id="ev-price-type" data-ev-member-price-type>
                            <option value="free" <?php echo ($_POST['price_type'] ?? 'free') === 'free' ? 'selected' : ''; ?>>Kostenlos</option>
                            <option value="paid" <?php echo ($_POST['price_type'] ?? '') === 'paid' ? 'selected' : ''; ?>>Kostenpflichtig</option>
                        </select>
                    </div>
                    <div id="ev-price-wrap" class="ev-member-price-wrap"<?php echo ($_POST['price_type'] ?? 'free') === 'paid' ? '' : ' hidden'; ?>>
                        <div class="form-group">
                            <label class="form-label">Preis</label>
                            <input type="number" name="price" class="form-control" min="0" step="0.01"
                                   value="<?php echo htmlspecialchars((string)($_POST['price'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Währung</label>
                            <input type="text" name="price_currency" class="form-control"
                                   placeholder="EUR" maxlength="3"
                                   value="<?php echo htmlspecialchars((string)($_POST['price_currency'] ?? 'EUR'), ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Veranstalter -->
                <h4 class="ev-member-section-title">👤 Veranstalter</h4>

                <div class="ev-member-grid-2">
                    <div class="form-group">
                        <label class="form-label">Name</label>
                        <input type="text" name="organizer_name" class="form-control"
                               value="<?php echo htmlspecialchars((string)($_POST['organizer_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">E-Mail</label>
                        <input type="email" name="organizer_email" class="form-control"
                               value="<?php echo htmlspecialchars((string)($_POST['organizer_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <div class="ev-member-grid-2">
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="organizer_phone" class="form-control"
                               value="<?php echo htmlspecialchars((string)($_POST['organizer_phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Website</label>
                        <input type="url" name="organizer_website" class="form-control" placeholder="https://"
                               value="<?php echo htmlspecialchars((string)($_POST['organizer_website'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                </div>

                <!-- Beschreibung -->
                <h4 class="ev-member-section-title">📝 Beschreibung</h4>

                <div class="form-group">
                    <textarea name="description" class="form-control ev-member-description" rows="6"><?php echo htmlspecialchars((string)($_POST['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="ev-member-actions">
                    <button type="submit" class="btn btn-primary">Event einreichen</button>
                    <a href="/member/plugin/events" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </div>
        <?php
    }

    private function sanitizeDate(mixed $value): ?string
    {
        $date = trim((string) $value);
        if ($date === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $date));
        return checkdate($month, $day, $year) ? $date : null;
    }

    private function sanitizeTime(mixed $value): ?string
    {
        $time = trim((string) $value);
        if ($time === '') {
            return null;
        }

        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::[0-5]\d)?$/', $time) !== 1) {
            return null;
        }

        return strlen($time) === 5 ? $time . ':00' : $time;
    }

    private function sanitizeHexColor(string $value, string $fallback): string
    {
        return preg_match('/^#[0-9a-fA-F]{3}(?:[0-9a-fA-F]{3})?$/', $value) === 1 ? $value : $fallback;
    }

    private function redirect(string $path): void
    {
        if (class_exists('CMS\\Router')) {
            \CMS\Router::instance()->redirect($path);
            return;
        }

        header('Location: ' . $path);
        exit;
    }
}
