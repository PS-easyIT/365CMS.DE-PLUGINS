<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Layout: Sidebar – Content links, Sticky-Sidebar rechts mit CTA
 *
 * Conversion-optimiertes Layout: Hauptcontent links, permanente
 * Bewerbungs-Sidebar rechts mit Sticky-Behaviour.
 *
 * @var object        $profile
 * @var array<object> $tasks
 * @var array<object> $requirements
 * @var array         $benefits
 * @var array<object> $skills
 * @var string        $company
 * @var string        $jsonld
 * @var string        $applyCsrf
 * @var array         $designSettings
 *
 * @since   0.9.7
 * @package CMS_JobProfileGenerator
 */

$esc = function (string $v): string {
    return htmlspecialchars($v, ENT_QUOTES | ENT_HTML5, 'UTF-8');
};
$ds = fn(string $k, string $d = '') => $designSettings['pd_' . $k] ?? $d;
$showSalary  = ($ds('single_show_salary', '1') === '1');
$showCompany = ($ds('single_show_company', '1') === '1');
$showTeam    = ($ds('single_show_team', '1') === '1');
$showSkills  = ($ds('single_show_skills', '1') === '1');
$showBenefits= ($ds('single_show_benefits', '1') === '1');
$showJsonld  = ($ds('single_show_jsonld', '1') === '1');
$applyText   = $ds('btn_apply_text', '📩 Jetzt bewerben');
?>
<div class="jpg-public jpg-layout-sidebar">

    <!-- Schmaler Header -->
    <div class="jpg-sidebar-header">
        <?php if ($showCompany && !empty($company)): ?>
        <span class="jpg-sidebar-header__company">🏢 <?php echo $esc($company); ?></span>
        <?php endif; ?>
        <h1><?php echo $esc($profile->title); ?></h1>
        <div class="jpg-job-meta">
            <?php if (!empty($profile->location)): ?>
            <span>📍 <?php echo $esc($profile->location); ?></span>
            <?php endif; ?>
            <?php if (!empty($profile->employment_type)): ?>
            <span>💼 <?php echo $esc(match ($profile->employment_type) {
                'fulltime'  => 'Vollzeit',
                'parttime'  => 'Teilzeit',
                'contract'  => 'Freelance',
                'temporary' => 'Befristet',
                'intern'    => 'Praktikum',
                'minijob'   => 'Minijob',
                default     => $profile->employment_type,
            }); ?></span>
            <?php endif; ?>
            <?php if (!empty($profile->experience_level)): ?>
            <span>⭐ <?php echo $esc(match ($profile->experience_level) {
                'junior' => 'Junior', 'mid' => 'Mid-Level', 'senior' => 'Senior', 'lead' => 'Lead',
                default  => $profile->experience_level,
            }); ?></span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Two-Column: Content + Sticky Sidebar -->
    <div class="jpg-sidebar-layout">

        <!-- Linke Spalte: Content -->
        <div class="jpg-sidebar-main">

            <?php if (!empty($profile->summary)): ?>
            <div class="jpg-section">
                <h2>Über die Stelle</h2>
                <p><?php echo nl2br($esc($profile->summary)); ?></p>
            </div>
            <?php endif; ?>

            <?php if (!empty($tasks)): ?>
            <div class="jpg-section">
                <h2>Ihre Aufgaben</h2>
                <ul>
                <?php foreach ($tasks as $task): ?>
                    <li><?php echo $esc($task->task_text); ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($requirements)): ?>
            <div class="jpg-section">
                <h2>Ihr Profil</h2>
                <?php
                $must = array_filter($requirements, fn($r) => ($r->type ?? 'must') === 'must');
                $nice = array_filter($requirements, fn($r) => ($r->type ?? 'must') === 'nice');
                ?>
                <?php if ($must): ?>
                <h3>Anforderungen</h3>
                <ul>
                <?php foreach ($must as $r): ?>
                    <li><?php echo $esc($r->description ?? $r->requirement_text ?? ''); ?></li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>
                <?php if ($nice): ?>
                <h3>Von Vorteil</h3>
                <ul>
                <?php foreach ($nice as $r): ?>
                    <li><?php echo $esc($r->description ?? $r->requirement_text ?? ''); ?></li>
                <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <?php if ($showBenefits && !empty($benefits)): ?>
            <div class="jpg-section">
                <h2>Das bieten wir</h2>
                <div class="jpg-sidebar-benefits-grid">
                <?php foreach ($benefits as $b): ?>
                    <div class="jpg-sidebar-benefit-item">
                        <span class="jpg-sidebar-benefit-icon"><?php echo !empty($b->icon) ? $b->icon : '✅'; ?></span>
                        <span><?php echo $esc($b->title ?? ''); ?></span>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($showTeam && !empty($experts)): ?>
            <div class="jpg-section">
                <h2>👥 Lerne dein Team kennen</h2>
                <div class="jpg-team-grid">
                    <?php foreach ($experts as $expert): ?>
                    <div class="jpg-team-card">
                        <?php if (!empty($expert->avatar_url)): ?>
                        <img src="<?php echo $esc($expert->avatar_url); ?>"
                             alt="<?php echo $esc($expert->name); ?>"
                             class="jpg-team-avatar__img" loading="lazy">
                        <?php else: ?>
                        <div class="jpg-team-avatar--placeholder">👤</div>
                        <?php endif; ?>
                        <div class="jpg-team-name"><?php echo $esc($expert->name); ?></div>
                        <?php if (!empty($expert->job_title)): ?>
                        <div class="jpg-team-title"><?php echo $esc($expert->job_title); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Rechte Spalte: Sticky Sidebar -->
        <aside class="jpg-sidebar-aside">
            <div class="jpg-sidebar-sticky">

                <!-- Quick Apply -->
                <div class="jpg-sidebar-cta-card">
                    <h3>Interesse?</h3>
                    <p>Bewirb dich direkt auf diese Stelle.</p>
                    <button type="button" class="jpg-btn-apply jpg-btn-apply--fullwidth" onclick="jpgOpenApplyModal()">
                        <?php echo $esc($applyText); ?>
                    </button>
                </div>

                <!-- Salary -->
                <?php if ($showSalary && (!empty($profile->salary_min) || !empty($profile->salary_max))): ?>
                <div class="jpg-sidebar-info-card">
                    <h4>💰 Gehalt</h4>
                    <div class="jpg-sidebar-salary">
                        <?php
                        if ($profile->salary_min && $profile->salary_max) {
                            echo number_format((float)$profile->salary_min, 0, ',', '.') . ' – '
                               . number_format((float)$profile->salary_max, 0, ',', '.') . ' €';
                        } elseif ($profile->salary_min) {
                            echo 'ab ' . number_format((float)$profile->salary_min, 0, ',', '.') . ' €';
                        } else {
                            echo 'bis ' . number_format((float)$profile->salary_max, 0, ',', '.') . ' €';
                        }
                        ?>
                        <small>pro Jahr</small>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Skills -->
                <?php if ($showSkills && !empty($skills)): ?>
                <div class="jpg-sidebar-info-card">
                    <h4>🧩 Gesuchte Skills</h4>
                    <div class="jpg-skill-tags">
                    <?php foreach ($skills as $s): ?>
                        <span class="jpg-skill-tag"><?php echo $esc($s->skill_name ?? ''); ?></span>
                    <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick-Facts -->
                <div class="jpg-sidebar-info-card">
                    <h4>📋 Auf einen Blick</h4>
                    <ul class="jpg-sidebar-facts">
                        <?php if (!empty($profile->location)): ?>
                        <li><strong>Standort:</strong> <?php echo $esc($profile->location); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($profile->employment_type)): ?>
                        <li><strong>Anstellung:</strong> <?php echo $esc(match ($profile->employment_type) {
                            'fulltime' => 'Vollzeit', 'parttime' => 'Teilzeit', 'contract' => 'Freelance',
                            'temporary'=> 'Befristet', 'intern' => 'Praktikum', 'minijob' => 'Minijob',
                            default => $profile->employment_type,
                        }); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($profile->remote_option) && $profile->remote_option !== 'onsite'): ?>
                        <li><strong>Remote:</strong> <?php echo $esc(match ($profile->remote_option) {
                            'remote' => 'Vollständig remote', 'hybrid' => 'Hybrid möglich',
                            default => $profile->remote_option,
                        }); ?></li>
                        <?php endif; ?>
                        <?php if (!empty($profile->experience_level)): ?>
                        <li><strong>Erfahrung:</strong> <?php echo $esc(match ($profile->experience_level) {
                            'junior' => 'Junior', 'mid' => 'Mid-Level', 'senior' => 'Senior', 'lead' => 'Lead',
                            default => $profile->experience_level,
                        }); ?></li>
                        <?php endif; ?>
                    </ul>
                </div>

            </div>
        </aside>

    </div>

    <?php if ($showJsonld && !empty($jsonld)): ?>
    <script type="application/ld+json"><?php echo $jsonld; ?></script>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/apply-modal.php'; ?>
