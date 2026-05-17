<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * Layout: Kompakt – Minimalistisch, zeitungsartig, einschlank
 *
 * Reduziertes einspaltenayoutiges Design ohne Sidebar.
 * Ideal für schnelle Informationsaufnahme.
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
$applyText   = $ds('btn_apply_text', 'Jetzt bewerben');
?>
<div class="jpg-public jpg-layout-compact">

    <article class="jpg-compact-article">

        <!-- Kopfzeile -->
        <header class="jpg-compact-header">
            <?php if ($showCompany && !empty($company)): ?>
            <div class="jpg-compact-company"><?php echo $esc($company); ?></div>
            <?php endif; ?>
            <h1 class="jpg-compact-title"><?php echo $esc($profile->title); ?></h1>
            <div class="jpg-compact-meta">
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
                <?php if ($showSalary && (!empty($profile->salary_min) || !empty($profile->salary_max))): ?>
                <span>💰 <?php
                if ($profile->salary_min && $profile->salary_max) {
                    echo number_format((float)$profile->salary_min, 0, ',', '.') . '–'
                       . number_format((float)$profile->salary_max, 0, ',', '.') . ' €';
                } elseif ($profile->salary_min) {
                    echo 'ab ' . number_format((float)$profile->salary_min, 0, ',', '.') . ' €';
                } else {
                    echo 'bis ' . number_format((float)$profile->salary_max, 0, ',', '.') . ' €';
                }
                ?></span>
                <?php endif; ?>
            </div>
            <div class="jpg-compact-apply-inline">
                <button type="button" class="jpg-btn-apply" data-jpg-apply-open>
                    <?php echo $esc($applyText); ?>
                </button>
            </div>
        </header>

        <hr class="jpg-compact-divider">

        <!-- Zusammenfassung -->
        <?php if (!empty($profile->summary)): ?>
        <section class="jpg-compact-section">
            <p class="jpg-compact-lead"><?php echo nl2br($esc($profile->summary)); ?></p>
        </section>
        <?php endif; ?>

        <!-- Aufgaben -->
        <?php if (!empty($tasks)): ?>
        <section class="jpg-compact-section">
            <h2>Aufgaben</h2>
            <ul class="jpg-compact-list">
            <?php foreach ($tasks as $task): ?>
                <li><?php echo $esc($task->task_text); ?></li>
            <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <!-- Anforderungen -->
        <?php if (!empty($requirements)): ?>
        <section class="jpg-compact-section">
            <h2>Profil</h2>
            <?php
            $must = array_filter($requirements, fn($r) => ($r->req_type ?? 'must') === 'must');
            $nice = array_filter($requirements, fn($r) => ($r->req_type ?? 'must') === 'nice');
            ?>
            <?php if ($must): ?>
            <ul class="jpg-compact-list">
            <?php foreach ($must as $r): ?>
                <li><?php echo $esc($r->req_text ?? ''); ?></li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <?php if ($nice): ?>
            <h3>Wünschenswert</h3>
            <ul class="jpg-compact-list jpg-compact-list--light">
            <?php foreach ($nice as $r): ?>
                <li><?php echo $esc($r->req_text ?? ''); ?></li>
            <?php endforeach; ?>
            </ul>
            <?php endif; ?>
        </section>
        <?php endif; ?>

        <!-- Skills -->
        <?php if ($showSkills && !empty($skills)): ?>
        <section class="jpg-compact-section">
            <h2>Skills</h2>
            <div class="jpg-skill-tags">
            <?php foreach ($skills as $s): ?>
                <span class="jpg-skill-tag"><?php echo $esc($s->skill_name ?? ''); ?></span>
            <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Benefits -->
        <?php if ($showBenefits && !empty($benefits)): ?>
        <section class="jpg-compact-section">
            <h2>Benefits</h2>
            <div class="jpg-compact-benefits">
            <?php foreach ($benefits as $b): ?>
                <span class="jpg-compact-benefit-chip"><?php echo (!empty($b->icon) ? $esc($b->icon) . ' ' : '✅ '); echo $esc($b->title ?? ''); ?></span>
            <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- Team -->
        <?php if ($showTeam && !empty($experts)): ?>
        <section class="jpg-compact-section">
            <h2>Team</h2>
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
        </section>
        <?php endif; ?>

        <hr class="jpg-compact-divider">

        <!-- Bewerbung -->
        <section class="jpg-compact-section jpg-compact-apply">
            <button type="button" class="jpg-btn-apply jpg-btn-apply--fullwidth" data-jpg-apply-open>
                <?php echo $esc($applyText); ?>
            </button>
        </section>

    </article>

    <?php if ($showJsonld && !empty($jsonld)): ?>
    <script type="application/ld+json"><?php echo $jsonld; ?></script>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/apply-modal.php'; ?>
