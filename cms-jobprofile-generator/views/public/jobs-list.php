<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/**
 * View: Öffentliche Job-Übersicht (/jobs)
 *
 * @var array<object> $profiles        Liste der veröffentlichten Jobs
 * @var int           $totalCount      Gesamtanzahl passender Jobs
 * @var int           $pages           Gesamtanzahl Seiten
 * @var int           $page            Aktuelle Seite
 * @var string        $companyFilter   Aktiver Firmenfilter (GET ?company=...)
 * @var string        $typeFilter      Aktiver Typfilter (GET ?type=...)
 * @var string        $locationFilter  Aktiver Standortfilter (GET ?location=...)
 * @var array         $typeLabels      Mapping employment_type → Label
 * @var array         $remoteLabels    Mapping remote_option → Label
 *
 * @since   0.9.0
 * @package CMS_JobProfileGenerator
 */

$esc = fn(string|null $v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');

// Aktuelle Seiten-URL ohne page-Parameter für Filter-Links
$baseListUrl = '/jobs';

// Beschäftigungstyp-Optionen für Filter-Dropdown
$filterTypes = [
    ''            => 'Alle Arten',
    'fulltime'    => 'Vollzeit',
    'parttime'    => 'Teilzeit',
    'freelance'   => 'Freiberuflich',
    'internship'  => 'Praktikum',
    'mini'        => 'Minijob',
];

/**
 * Erzeugt eine Filter-URL mit beliebigen GET-Parametern.
 */
$filterUrl = function (array $overrides) use ($companyFilter, $typeFilter, $locationFilter, $categoryFilter, $remoteFilter, $salaryMin): string {
    $params = array_filter(array_merge([
        'company'    => $companyFilter,
        'type'       => $typeFilter,
        'location'   => $locationFilter,
        'category'   => $categoryFilter ?? '',
        'remote'     => $remoteFilter   ?? '',
        'salary_min' => ($salaryMin ?? 0) > 0 ? (string)$salaryMin : '',
    ], $overrides));
    return '/jobs' . ($params ? '?' . http_build_query($params) : '');
};

// Phase 14.3: neue Filtervariablen mit Fallback
$categoryFilter = $categoryFilter ?? '';
$remoteFilter   = $remoteFilter   ?? '';
$salaryMin      = (int)($salaryMin ?? 0);
$allCategories  = $allCategories  ?? [];
$hasFilter      = $companyFilter !== '' || $typeFilter !== '' || $locationFilter !== ''
               || $categoryFilter !== '' || $remoteFilter !== '' || $salaryMin > 0;
?>
<div class="jpg-public jpg-jobs-list">

    <!-- Page-Titel -->
    <div class="jpg-jobs-list__header">
        <div>
            <h1>💼 Offene Stellen</h1>
            <p class="jpg-jobs-list__subtitle">
                <?php if ($totalCount > 0): ?>
                    <?php echo $totalCount; ?> <?php echo $totalCount === 1 ? 'Stelle gefunden' : 'Stellen gefunden'; ?>
                <?php else: ?>
                    Keine Stellen gefunden
                <?php endif; ?>
            </p>
        </div>
    </div>

    <!-- Filter -->
    <form method="GET" action="/jobs" class="jpg-jobs-list__filters">
        <div class="jpg-jobs-list__filter-row">
            <input type="text" name="company" class="jpg-filter-input"
                   placeholder="🏢 Unternehmen …"
                   value="<?php echo $esc($companyFilter); ?>">

            <input type="text" name="location" class="jpg-filter-input"
                   placeholder="📍 Standort …"
                   value="<?php echo $esc($locationFilter); ?>">

            <select name="type" class="jpg-filter-select">
                <?php foreach ($filterTypes as $val => $lbl): ?>
                <option value="<?php echo $esc($val); ?>" <?php echo $typeFilter === $val ? 'selected' : ''; ?>>
                    <?php echo $esc($lbl); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <?php if (!empty($allCategories)): ?>
            <!-- Phase 14.3: Kategorie-Filter -->
            <select name="category" class="jpg-filter-select">
                <option value="">🗂️ Alle Kategorien</option>
                <?php foreach ($allCategories as $cat): ?>
                <option value="<?php echo $esc($cat->name); ?>"
                        <?php echo $categoryFilter === $cat->name ? 'selected' : ''; ?>>
                    <?php echo $esc($cat->name); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <!-- Phase 14.3: Remote-Filter -->
            <select name="remote" class="jpg-filter-select">
                <option value="">🌍 Alle Arbeitsmodelle</option>
                <?php foreach ($remoteLabels as $rVal => $rLbl): ?>
                <option value="<?php echo $esc($rVal); ?>" <?php echo $remoteFilter === $rVal ? 'selected' : ''; ?>>
                    <?php echo $esc($rLbl); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- Phase 14.3: Gehalt-Filter -->
            <input type="number" name="salary_min" class="jpg-filter-input"
                   placeholder="💰 Gehalt ab (€)" min="0" step="500"
                   style="max-width:160px;"
                   value="<?php echo $salaryMin > 0 ? $salaryMin : ''; ?>">

            <button type="submit" class="jpg-filter-btn">🔍 Filtern</button>

            <?php if ($hasFilter): ?>
            <a href="/jobs" class="jpg-filter-reset">✕ Zurücksetzen</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Job-Liste -->
    <?php if (empty($profiles)): ?>
    <div class="jpg-jobs-list__empty">
        <p style="font-size:2.5rem;margin:0;">📭</p>
        <p><strong>Keine passenden Stellen gefunden</strong></p>
        <p>Versuche es mit anderen Filtereinstellungen oder schau später wieder vorbei.</p>
        <?php if ($hasFilter): ?>
        <a href="/jobs" class="jpg-btn jpg-btn-secondary">Alle Stellen anzeigen</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="jpg-jobs-list__grid">
        <?php foreach ($profiles as $job): ?>
        <?php
            $jobUrl  = '/jobs/' . htmlspecialchars($job->slug ?? '', ENT_QUOTES);
            $typeLabel   = $typeLabels[$job->employment_type ?? ''] ?? ($job->employment_type ?? '');
            $remoteLabel = $remoteLabels[$job->remote_option ?? ''] ?? ($job->remote_option ?? '');
        ?>
        <article class="jpg-job-card-list">
            <div class="jpg-job-card-list__body">
                <h2 class="jpg-job-card-list__title">
                    <a href="<?php echo $esc($jobUrl); ?>"><?php echo $esc($job->title ?? ''); ?></a>
                </h2>

                <div class="jpg-job-card-list__meta">
                    <?php if (!empty($job->company_name)): ?>
                    <span class="jpg-meta-item">
                        🏢&nbsp;<?php echo $esc($job->company_name); ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($job->location)): ?>
                    <span class="jpg-meta-item">
                        📍&nbsp;<?php echo $esc($job->location); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($typeLabel !== ''): ?>
                    <span class="jpg-meta-item jpg-meta-type">
                        💼&nbsp;<?php echo $esc($typeLabel); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($remoteLabel !== ''): ?>
                    <span class="jpg-meta-item jpg-meta-remote">
                        🌐&nbsp;<?php echo $esc($remoteLabel); ?>
                    </span>
                    <?php endif; ?>
                    <?php if (!empty($job->salary_min) || !empty($job->salary_max)): ?>
                    <span class="jpg-meta-item jpg-meta-salary">
                        💰&nbsp;<?php
                            $salMin = (int) ($job->salary_min ?? 0);
                            $salMax = (int) ($job->salary_max ?? 0);
                            if ($salMin > 0 && $salMax > 0) {
                                echo number_format($salMin, 0, ',', '.') . ' – ' . number_format($salMax, 0, ',', '.') . ' €/Jahr';
                            } elseif ($salMin > 0) {
                                echo 'ab ' . number_format($salMin, 0, ',', '.') . ' €/Jahr';
                            } elseif ($salMax > 0) {
                                echo 'bis ' . number_format($salMax, 0, ',', '.') . ' €/Jahr';
                            }
                        ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if (!empty(trim($job->summary ?? ''))): ?>
                <p class="jpg-job-card-list__summary">
                    <?php echo $esc(mb_strimwidth(strip_tags($job->summary), 0, 200, '…')); ?>
                </p>
                <?php endif; ?>
            </div>

            <div class="jpg-job-card-list__action">
                <a href="<?php echo $esc($jobUrl); ?>" class="jpg-btn jpg-btn-primary">
                    Details &amp; Bewerben →
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <nav class="jpg-pagination" aria-label="Seitennavigation">
        <?php if ($page > 1): ?>
        <a href="<?php echo $esc($filterUrl(['page' => $page - 1])); ?>" class="jpg-pagination__btn">
            ← Zurück
        </a>
        <?php endif; ?>

        <span class="jpg-pagination__info">
            Seite <?php echo $page; ?> von <?php echo $pages; ?>
        </span>

        <?php if ($page < $pages): ?>
        <a href="<?php echo $esc($filterUrl(['page' => $page + 1])); ?>" class="jpg-pagination__btn">
            Weiter →
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php endif; ?>

</div><!-- /.jpg-jobs-list -->

<style>
/* ── öffentliche Stellen-Liste (jobs-list.php) ──────────────────────────── */
.jpg-jobs-list { max-width: 900px; margin: 0 auto; padding: 2rem 1rem; }

.jpg-jobs-list__header { margin-bottom: 1.5rem; }
.jpg-jobs-list__header h1 { font-size: 1.75rem; font-weight: 700; margin: 0 0 .25rem; }
.jpg-jobs-list__subtitle  { color: #64748b; font-size: .95rem; margin: 0; }

/* Filter-Leiste */
.jpg-jobs-list__filters   { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1rem 1.25rem; margin-bottom: 1.5rem; }
.jpg-jobs-list__filter-row{ display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; }
.jpg-filter-input, .jpg-filter-select {
    padding: .5rem .875rem; border: 1.5px solid #e2e8f0; border-radius: 7px;
    font-size: .9rem; background: #fff; color: #1e293b; flex: 1; min-width: 140px;
}
.jpg-filter-input:focus, .jpg-filter-select:focus {
    outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15);
}
.jpg-filter-btn  { padding: .5rem 1.25rem; background: #3b82f6; color: #fff; border: none; border-radius: 7px; font-size: .9rem; cursor: pointer; white-space: nowrap; }
.jpg-filter-btn:hover { background: #2563eb; }
.jpg-filter-reset{ color: #64748b; font-size: .875rem; text-decoration: none; white-space: nowrap; }
.jpg-filter-reset:hover { color: #ef4444; }

/* Job-Karten */
.jpg-jobs-list__grid  { display: flex; flex-direction: column; gap: 1rem; }
.jpg-job-card-list    { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: flex-start; gap: 1.5rem; transition: box-shadow .15s; }
.jpg-job-card-list:hover { box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.jpg-job-card-list__body{ flex: 1; min-width: 0; }
.jpg-job-card-list__title { font-size: 1.1rem; font-weight: 700; margin: 0 0 .5rem; }
.jpg-job-card-list__title a { color: #1e293b; text-decoration: none; }
.jpg-job-card-list__title a:hover { color: #3b82f6; }
.jpg-job-card-list__meta { display: flex; flex-wrap: wrap; gap: .5rem .875rem; margin-bottom: .6rem; }
.jpg-meta-item   { font-size: .825rem; color: #475569; }
.jpg-meta-type   { background: #eff6ff; color: #1d4ed8; padding: .15rem .5rem; border-radius: 4px; }
.jpg-meta-remote { background: #f0fdf4; color: #166534; padding: .15rem .5rem; border-radius: 4px; }
.jpg-meta-salary { background: #fef9c3; color: #713f12; padding: .15rem .5rem; border-radius: 4px; }
.jpg-job-card-list__summary { color: #64748b; font-size: .875rem; margin: 0; line-height: 1.5; }
.jpg-job-card-list__action  { flex-shrink: 0; }

/* Buttons */
.jpg-btn { display: inline-block; padding: .6rem 1.25rem; border-radius: 7px; font-size: .875rem; font-weight: 600; text-decoration: none; cursor: pointer; }
.jpg-btn-primary   { background: #3b82f6; color: #fff; }
.jpg-btn-primary:hover { background: #2563eb; }
.jpg-btn-secondary { background: #f1f5f9; color: #475569; }
.jpg-btn-secondary:hover { background: #e2e8f0; }

/* Empty state */
.jpg-jobs-list__empty { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 3rem; text-align: center; color: #64748b; }

/* Pagination */
.jpg-pagination { display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 2rem; }
.jpg-pagination__btn  { padding: .5rem 1.125rem; background: #f1f5f9; color: #475569; border-radius: 7px; text-decoration: none; font-size: .875rem; }
.jpg-pagination__btn:hover { background: #e2e8f0; }
.jpg-pagination__info { color: #64748b; font-size: .875rem; }

/* Responsive */
@media (max-width: 640px) {
    .jpg-job-card-list { flex-direction: column; }
    .jpg-job-card-list__action { width: 100%; }
    .jpg-btn { display: block; text-align: center; }
    .jpg-filter-input, .jpg-filter-select { min-width: 100%; flex: none; }
}
</style>
