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
            'label'     => 'IT-Experten',
            'icon'      => '🧑‍💼',
            'category'  => 'plugins',
            'priority'  => 10,
            'capability'=> null,
            'dashboard_widget' => [
                'title'          => 'IT-Experten',
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

        try {
            $count = (int) CMS_Experts_Database::instance()->countExperts();
            return ['count' => $count, 'label' => 'Experten gesamt'];
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
        $isAdmin  = \CMS\Auth::instance()->isAdmin();
        $experts  = [];
        $error    = null;
        $settings = [];

        if (class_exists('CMS_Experts_Database')) {
            try {
                $db       = CMS_Experts_Database::instance();
                $settings = $db->get_all_plugin_settings();
                $experts  = $db->get_experts_all(['status' => 'active', 'limit' => 60]) ?? [];

                // Skills & Spezialisierungen bulk-laden (ein Query statt N)
                if (!empty($experts)) {
                    $this->bulkLoadExpertData($experts);
                }
            } catch (\Throwable $e) {
                $error = 'Daten konnten nicht geladen werden.';
            }
        } else {
            $error = 'Das Experten-Plugin ist nicht vollständig installiert.';
        }

        // Settings mit Defaults zusammenführen (identisch zu archive-expert.php)
        $settings = array_merge([
            'design_show_skills'         => '1',
            'design_show_specialization' => '1',
            'design_primary_color'       => '#5e72e4',
            'design_accent_color'        => '#8965e0',
            'design_border_radius'       => '12',
            'design_cta_color'           => '#c2410c',
            'design_card_bg'             => '#fffdf4',
        ], $settings);

        // CSS-Variablen für die Card-Templates bereitstellen
        $cssVars = sprintf(
            ':root{--expert-primary:%s;--expert-accent:%s;--expert-radius:%dpx;--expert-cta-color:%s;--expert-card-bg:%s;}',
            htmlspecialchars($settings['design_primary_color']),
            htmlspecialchars($settings['design_accent_color']),
            (int) $settings['design_border_radius'],
            htmlspecialchars($settings['design_cta_color']),
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

        <!-- Aktions-Leiste -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem;flex-wrap:wrap;gap:.75rem;">
            <p style="color:#64748b;font-size:.875rem;margin:0;">
                <?php echo count($experts); ?> Experte(n) verfügbar
            </p>
            <?php if ($isAdmin): ?>
            <a href="/admin/experts?action=new"
               style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.25rem;
                      background:#4f46e5;color:#fff;border-radius:8px;font-weight:600;
                      font-size:.875rem;text-decoration:none;">
                ➕ Neuer Experte
            </a>
            <?php endif; ?>
        </div>

        <!-- Experten-Karten (3-spaltiges Grid via .experts-grid CSS-Klasse) -->
        <?php if (empty($experts)): ?>
        <div class="empty-state">
            <p style="font-size:2.5rem;margin:0 0 .75rem;">👤</p>
            <p><strong>Keine Experten vorhanden</strong></p>
            <p style="color:#64748b;margin:.25rem 0 0;">
                Es sind noch keine aktiven Experten-Profile vorhanden.
            </p>
            <?php if ($isAdmin): ?>
            <a href="/admin/experts?action=new" class="btn btn-primary" style="margin-top:1rem;">
                ➕ Ersten Experten anlegen
            </a>
            <?php endif; ?>
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
            $prefix      = $db->getPrefix();
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
