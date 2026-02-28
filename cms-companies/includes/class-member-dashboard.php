<?php
/**
 * CMS Companies – Member Dashboard Integration
 *
 * Registriert den Unternehmens-Bereich im Member-Dashboard.
 * Wird geladen von cms-companies.php (load_dependencies).
 *
 * URL: /member/plugin/companies
 *
 * @package CMS_Companies
 * @version 1.0.0
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class CMS_Companies_Member_Dashboard
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
            \CMS\Hooks::addAction('member_plugin_section_head', [$this, 'enqueueCompanyStyles'], 10);
        }
    }

    public function enqueueCompanyStyles(string $slug): void
    {
        if ($slug !== 'companies') {
            return;
        }
        $cssFile = defined('CMS_COMPANIES_PLUGIN_DIR')
            ? CMS_COMPANIES_PLUGIN_DIR . 'assets/css/style.css'
            : '';
        $cssUrl  = defined('CMS_COMPANIES_PLUGIN_URL')
            ? CMS_COMPANIES_PLUGIN_URL . 'assets/css/style.css'
            : '';

        if ($cssUrl !== '') {
            $v = $cssFile && file_exists($cssFile) ? filemtime($cssFile) : '1';
            echo '<link rel="stylesheet" href="' . htmlspecialchars($cssUrl) . '?v=' . $v . '">' . "\n";
        }
    }

    public function register(\CMS\Member\PluginDashboardRegistry $registry): void
    {
        $registry->register([
            'plugin'    => 'cms-companies',
            'slug'      => 'companies',
            'label'     => 'COMPANYS',
            'icon'      => '🏢',
            'category'  => 'plugins',
            'priority'  => 30,
            'capability'=> null,
            'dashboard_widget' => [
                'title'          => 'COMPANYS',
                'description'    => 'Firmen im Netzwerk entdecken und verwalten.',
                'color'          => '#0891b2',
                'stats_callback' => [$this, 'getDashboardStats'],
                'link_label'     => 'Zu den Unternehmen',
                'admin_url'      => '/admin/companies',
                'admin_label'    => '⚙️ Admin',
            ],
            'render_callback' => [$this, 'renderPage'],
        ]);
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public function getDashboardStats(object $user): array
    {
        if (!class_exists('CMS_Companies_Database')) {
            return ['count' => 0, 'label' => 'Unternehmen'];
        }

        $isAdmin = \CMS\Auth::instance()->isAdmin();
        try {
            if ($isAdmin) {
                $count = CMS_Companies_Database::instance()->get_companies_count(['status' => 'active']);
                return ['count' => $count, 'label' => 'Unternehmen gesamt'];
            } else {
                $userId = (int) ($user->id ?? 0);
                $count  = CMS_Companies_Database::instance()->get_companies_count(['status' => 'active', 'user_id' => $userId]);
                return ['count' => $count, 'label' => 'Meine Unternehmen'];
            }
        } catch (\Throwable $e) {
            return ['count' => 0, 'label' => 'Unternehmen'];
        }
    }

    // ── Page Rendering ────────────────────────────────────────────────────────

    public function renderPage(object $user, array $params = []): void
    {
        // ── POST: neues Unternehmen speichern ─────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['company_create'])) {
            if (!\CMS\Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'member_company_create')) {
                $_SESSION['error'] = 'Sicherheitscheck fehlgeschlagen.';
                header('Location: /member/plugin/companies?action=new');
                exit;
            }
            try {
                $isAdminSave = \CMS\Auth::instance()->isAdmin();
                $id = CMS_Companies_Database::instance()->save_company([
                    'user_id'          => (int) $user->id,
                    'name'             => sanitize_text_field($_POST['name']         ?? ''),
                    'email'            => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
                    'phone'            => sanitize_text_field($_POST['phone']         ?? ''),
                    'website'          => filter_var($_POST['website'] ?? '', FILTER_SANITIZE_URL) ?: null,
                    'logo_url'         => filter_var($_POST['logo_url'] ?? '', FILTER_SANITIZE_URL) ?: null,
                    'industry'         => sanitize_text_field($_POST['industry']      ?? ''),
                    'company_size'     => sanitize_text_field($_POST['company_size']  ?? ''),
                    'description'      => strip_tags($_POST['description']            ?? ''),
                    'location_city'    => sanitize_text_field($_POST['location_city'] ?? ''),
                    'location_zip'     => sanitize_text_field($_POST['location_zip']  ?? ''),
                    'location_country' => sanitize_text_field($_POST['location_country'] ?? 'Deutschland'),
                    'founded_year'     => (int)($_POST['founded_year']   ?? 0) ?: null,
                    'employee_count'   => (int)($_POST['employee_count'] ?? 0) ?: null,
                    'status'           => $isAdminSave ? 'active' : 'pending',
                ]);
                if ($isAdminSave) {
                    $_SESSION['success'] = 'Unternehmen wurde erfolgreich angelegt.';
                } else {
                    $_SESSION['success'] = 'Ihr Unternehmen wurde eingereicht und wird vom Admin geprüft.';
                }
                header('Location: /member/plugin/companies');
                exit;
            } catch (\Throwable $e) {
                $_SESSION['error'] = 'Fehler beim Speichern: ' . $e->getMessage();
                header('Location: /member/plugin/companies?action=new');
                exit;
            }
        }

        $action  = sanitize_text_field($_GET['action'] ?? '');
        $isAdmin = \CMS\Auth::instance()->isAdmin();

        // ── Formular: Neues Unternehmen ───────────────────────────────────────
        if ($action === 'new') {
            $this->renderCreateForm($user);
            return;
        }

        // ── Übersicht ─────────────────────────────────────────────────────────
        $companies = [];
        $error     = null;
        $settings  = [];

        if (class_exists('CMS_Companies_Database')) {
            try {
                $db       = CMS_Companies_Database::instance();
                $settings = $db->get_settings();

                if ($isAdmin) {
                    $queryArgs = ['status' => 'active', 'limit' => 60];
                } else {
                    $queryArgs = ['user_id' => (int) ($user->id ?? 0), 'limit' => 60];
                }
                $companies = $db->get_companies($queryArgs) ?? [];
            } catch (\Throwable $e) {
                $error = 'Daten konnten nicht geladen werden.';
            }
        } else {
            $error = 'Das Unternehmens-Plugin ist nicht vollständig installiert.';
        }

        $s = array_merge([
            'design_primary_color'     => '#0891b2',
            'design_accent_color'      => '#e0f2fe',
            'design_cta_color'         => '#0891b2',
            'design_card_bg'           => '#ffffff',
            'design_border_radius'     => '12',
            'design_show_industry'     => '1',
            'design_show_city'         => '1',
            'design_show_employees'    => '1',
            'design_show_website'      => '1',
            'design_partner_color'     => '#9ca3af',
            'design_top_partner_color' => '#d97706',
            'design_sponsor_color'     => '#7c3aed',
        ], $settings);

        $cssVars = sprintf(
            ':root{--co-primary:%s;--co-primary-d:%s;--co-accent:%s;--co-radius:%dpx;--co-card-bg:%s;}',
            htmlspecialchars($s['design_primary_color']),
            htmlspecialchars($s['design_cta_color']),
            htmlspecialchars($s['design_accent_color']),
            (int) $s['design_border_radius'],
            htmlspecialchars($s['design_card_bg'])
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
                <?php echo count($companies); ?> Unternehmen verfügbar
            </p>
            <a href="/member/plugin/companies?action=new" class="btn btn-primary">
                ➕ Neues Unternehmen
            </a>
        </div>

        <?php if (empty($companies)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0 0 .75rem;">🏢</p>
            <p><strong>Keine Unternehmen vorhanden</strong></p>
            <p style="color:#64748b;margin:.25rem 0 0;">Es sind noch keine aktiven Unternehmens-Profile vorhanden.</p>
            <a href="/member/plugin/companies?action=new" class="btn btn-primary" style="margin-top:1rem;">
                ➕ Erstes Unternehmen anlegen
            </a>
        </div>
        <?php else: ?>
        <div class="co-grid" style="grid-template-columns:repeat(3,1fr);">
            <?php foreach ($companies as $company): ?>
                <?php
                if (class_exists('CMS_Companies_Template_Loader')) {
                    CMS_Companies_Template_Loader::instance()->render_template('company-card', [
                        'company'  => $company,
                        's'        => $s,
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
        $csrfToken = \CMS\Security::instance()->generateToken('member_company_create');
        $isAdmin   = \CMS\Auth::instance()->isAdmin();
        ?>
        <div style="margin-bottom:1rem;">
            <a href="/member/plugin/companies" class="btn btn-secondary btn-sm">
                ← Zurück zur Übersicht
            </a>
        </div>

        <?php if (!$isAdmin): ?>
        <div class="alert" style="background:#f0fdfa;border-left:4px solid #0891b2;color:#155e75;">
            <strong>ℹ️ Hinweis:</strong> Ihr Profil wird nach dem Einreichen vom Admin geprüft und dann freigeschaltet.
        </div>
        <?php endif; ?>

        <div class="admin-card">
            <h3>🏢 Neues Unternehmen einreichen</h3>

            <form method="POST" action="/member/plugin/companies?action=new">
                <input type="hidden" name="company_create" value="1">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <!-- Unternehmensdaten -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">🏢 Unternehmensdaten</h4>

                <div class="form-group">
                    <label class="form-label">Unternehmensname <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" class="form-control" required
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Branche</label>
                        <input type="text" name="industry" class="form-control"
                               placeholder="z. B. IT, Beratung, Handel"
                               value="<?php echo htmlspecialchars($_POST['industry'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unternehmensgröße</label>
                        <select name="company_size" class="form-control">
                            <option value="">– bitte wählen –</option>
                            <?php foreach (['1-10','11-50','51-200','201-500','501-1000','1000+'] as $sz): ?>
                                <option value="<?php echo $sz; ?>"
                                    <?php echo (($_POST['company_size'] ?? '') === $sz) ? 'selected' : ''; ?>>
                                    <?php echo $sz; ?> Mitarbeiter
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Logo-URL</label>
                    <input type="url" name="logo_url" class="form-control"
                           placeholder="https://beispiel.de/logo.png"
                           value="<?php echo htmlspecialchars($_POST['logo_url'] ?? ''); ?>">
                    <small class="form-text">Direktlink zum Firmenlogo (PNG, JPG oder SVG empfohlen).</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="description" class="form-control" rows="4"
                              style="resize:vertical;"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <!-- Kontakt -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">📞 Kontakt</h4>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
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
                </div>

                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="url" name="website" class="form-control"
                           placeholder="https://"
                           value="<?php echo htmlspecialchars($_POST['website'] ?? ''); ?>">
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

                <!-- Weiteres -->
                <h4 style="color:#475569;font-size:.95rem;margin:1.25rem 0 .75rem;
                           padding-bottom:.5rem;border-bottom:1px solid #f1f5f9;">📊 Weiteres</h4>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
                    <div class="form-group">
                        <label class="form-label">Gegründet (Jahr)</label>
                        <input type="number" name="founded_year" class="form-control"
                               min="1800" max="<?php echo date('Y'); ?>"
                               placeholder="<?php echo date('Y'); ?>"
                               value="<?php echo htmlspecialchars($_POST['founded_year'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mitarbeiteranzahl (Zahl)</label>
                        <input type="number" name="employee_count" class="form-control" min="0"
                               value="<?php echo htmlspecialchars($_POST['employee_count'] ?? ''); ?>">
                        <small class="form-text">Exakte Zahl, falls bekannt.</small>
                    </div>
                </div>

                <div style="display:flex;gap:.75rem;margin-top:1.5rem;">
                    <button type="submit" class="btn btn-primary">💾 Unternehmen einreichen</button>
                    <a href="/member/plugin/companies" class="btn btn-secondary">Abbrechen</a>
                </div>
            </form>
        </div>
        <?php
    }
}

// Bootstrap
CMS_Companies_Member_Dashboard::instance();
