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
<?php
// Design-Settings Helfer
$ds = $designSettings ?? [];
$showTitle = ($ds['list_show_title'] ?? '1') === '1';
$showCount = ($ds['list_show_count'] ?? '1') === '1';
?>
<div class="jpg-public jpg-jobs-list">

    <?php if ($showTitle || $showCount): ?>
    <!-- Page-Titel -->
    <div class="jpg-jobs-list__header">
        <div>
            <?php if ($showTitle): ?>
            <h1>💼 Offene Stellen</h1>
            <?php endif; ?>
            <?php if ($showCount): ?>
            <p class="jpg-jobs-list__subtitle">
                <?php if ($totalCount > 0): ?>
                    <?php echo $totalCount; ?> <?php echo $totalCount === 1 ? 'Stelle gefunden' : 'Stellen gefunden'; ?>
                <?php else: ?>
                    Keine Stellen gefunden
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

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
            <input type="number" name="salary_min" class="jpg-filter-input jpg-filter-input--salary"
                   placeholder="💰 Gehalt ab (€)" min="0" step="500"
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
        <p class="jpg-empty-icon">📭</p>
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
