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
     * @param object $user
     * @param array  $params
     */
    public function renderPage(object $user, array $params = []): void
    {
        $isAdmin = \CMS\Auth::instance()->isAdmin();

        $experts = [];
        $error   = null;

        if (class_exists('CMS_Experts_Database')) {
            try {
                $experts = CMS_Experts_Database::instance()->get_experts_all(['limit' => 50]) ?? [];
            } catch (\Throwable $e) {
                $error = 'Daten konnten nicht geladen werden.';
            }
        } else {
            $error = 'Das Experten-Plugin ist nicht vollständig installiert.';
        }
        ?>
        <?php if ($error): ?>
        <div class="member-alert member-alert-error">
            <span class="alert-icon">✕</span>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
        <?php else: ?>

        <!-- Aktions-Leiste -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem;">
            <p style="color:#64748b;font-size:.875rem;margin:0;">
                <?php echo count($experts); ?> Experte(n) gefunden
            </p>
            <?php if ($isAdmin): ?>
            <a href="/admin/experts?action=new"
               style="display:inline-flex;align-items:center;gap:.4rem;padding:.5rem 1.125rem;
                      background:#4f46e5;color:#fff;border-radius:8px;font-weight:600;
                      font-size:.875rem;text-decoration:none;">
                ➕ Neuer Experte
            </a>
            <?php endif; ?>
        </div>

        <!-- Experten-Karten -->
        <?php if (empty($experts)): ?>
        <div style="text-align:center;padding:3rem;background:#fff;border-radius:12px;border:1px solid #e2e8f0;">
            <div style="font-size:2.5rem;margin-bottom:.75rem;">👤</div>
            <h3 style="color:#374151;margin:0 0 .5rem;">Keine Experten</h3>
            <p style="color:#64748b;margin:0;">Es sind noch keine Experten-Profile vorhanden.</p>
        </div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;">
            <?php foreach ($experts as $expert): ?>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.25rem;
                        display:flex;flex-direction:column;gap:.5rem;">
                <div style="display:flex;align-items:center;gap:.75rem;">
                    <div style="width:44px;height:44px;border-radius:50%;background:#4f46e518;
                                display:flex;align-items:center;justify-content:center;
                                font-size:1.25rem;color:#4f46e5;font-weight:700;flex-shrink:0;">
                        <?php
                            $initials = strtoupper(
                                substr(trim($expert->first_name ?? ''), 0, 1) .
                                substr(trim($expert->last_name  ?? ''), 0, 1)
                            );
                            echo $initials ?: '?';
                        ?>
                    </div>
                    <div>
                        <p style="font-weight:700;color:#1e293b;margin:0;font-size:.9375rem;">
                            <?php
                                $full = trim(($expert->first_name ?? '') . ' ' . ($expert->last_name ?? ''));
                                echo htmlspecialchars($full ?: '–');
                            ?>
                        </p>
                        <?php if (!empty($expert->position)): ?>
                        <p style="color:#64748b;font-size:.8rem;margin:.125rem 0 0;">
                            <?php echo htmlspecialchars($expert->position); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($isAdmin && !empty($expert->id)): ?>
                <a href="/admin/experts?action=edit&id=<?php echo (int)$expert->id; ?>"
                   style="display:inline-flex;justify-content:center;padding:.375rem;
                          background:#f1f5f9;border-radius:7px;font-size:.8rem;
                          color:#4f46e5;font-weight:600;text-decoration:none;margin-top:.25rem;">
                    Bearbeiten
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
        <?php
    }
}

// Bootstrap
CMS_Experts_Member_Dashboard::instance();
