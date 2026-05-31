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
$publicLang = $publicLang ?? (function_exists('jpg_public_lang') ? jpg_public_lang() : 'de');
$t = static fn(string $key, array $replace = []): string
    => function_exists('jpg_public_t') ? jpg_public_t($key, $replace, $publicLang) : $key;

// Aktuelle Seiten-URL ohne page-Parameter für Filter-Links
$baseListUrl = $jobsBasePath ?? (function_exists('jpg_public_path') ? jpg_public_path('jobs', $publicLang) : '/jobs');

// Phase 14.3: neue Filtervariablen mit Fallback
$categoryFilter = $categoryFilter ?? '';
$remoteFilter   = $remoteFilter   ?? '';
$salaryMin      = (int)($salaryMin ?? 0);
$allCategories  = $allCategories  ?? [];
$hasFilter      = $companyFilter !== '' || $typeFilter !== '' || $locationFilter !== ''
               || $categoryFilter !== '' || $remoteFilter !== '' || $salaryMin > 0;

// Beschäftigungstyp-Optionen für Filter-Dropdown
$filterTypes = [
    ''            => $t('jobs_filter_type_all'),
    'fulltime'    => $t('type_fulltime'),
    'parttime'    => $t('type_parttime'),
    'freelance'   => $t('type_freelance'),
    'internship'  => $t('type_internship'),
    'mini'        => $t('type_mini'),
];

/**
 * Erzeugt eine Filter-URL mit beliebigen GET-Parametern.
 */
$filterUrl = function (array $overrides) use ($companyFilter, $typeFilter, $locationFilter, $categoryFilter, $remoteFilter, $salaryMin, $baseListUrl): string {
    $params = array_filter(array_merge([
        'company'    => $companyFilter,
        'type'       => $typeFilter,
        'location'   => $locationFilter,
        'category'   => $categoryFilter ?? '',
        'remote'     => $remoteFilter   ?? '',
        'salary_min' => ($salaryMin ?? 0) > 0 ? (string)$salaryMin : '',
    ], $overrides));
    return $baseListUrl . ($params ? '?' . http_build_query($params) : '');
};
?>
<?php
// Design-Settings Helfer (Keys in DB mit pd_-Prefix gespeichert)
$ds = $designSettings ?? [];
$showTitle   = ($ds['pd_list_show_title']    ?? '1') === '1';
$showCount   = ($ds['pd_list_show_count']    ?? '1') === '1';
$showSalary  = ($ds['pd_list_show_salary']   ?? '1') === '1';
$showCompany = ($ds['pd_list_show_company']  ?? '1') === '1';
$showRemote  = ($ds['pd_list_show_remote']   ?? '1') === '1';
$showCategory= ($ds['pd_list_show_category'] ?? '1') === '1';
$showSummary = ($ds['pd_list_show_summary']  ?? '1') === '1';
$showFilters = ($ds['pd_list_show_filters']  ?? '1') === '1';
$cardStyle   = $ds['pd_list_card_style']     ?? 'horizontal';
?>
<div class="jpg-public jpg-jobs-list">

    <?php if ($showTitle || $showCount): ?>
    <!-- Page-Titel -->
    <div class="jpg-jobs-list__header">
        <div>
            <?php if ($showTitle): ?>
            <h1>💼 <?php echo $esc($t('jobs_open_positions')); ?></h1>
            <?php endif; ?>
            <?php if ($showCount): ?>
            <p class="jpg-jobs-list__subtitle">
                <?php if ($totalCount > 0): ?>
                    <?php echo $totalCount; ?> <?php echo $esc($totalCount === 1 ? $t('jobs_found_singular') : $t('jobs_found_plural')); ?>
                <?php else: ?>
                    <?php echo $esc($t('jobs_none_found')); ?>
                <?php endif; ?>
            </p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filter -->
    <?php if ($showFilters): ?>
    <form method="GET" action="<?php echo $esc($baseListUrl); ?>" class="jpg-jobs-list__filters">
        <div class="jpg-jobs-list__filter-row">
            <input type="text" name="company" class="jpg-filter-input"
                   placeholder="🏢 <?php echo $esc($t('jobs_filter_company')); ?>"
                   value="<?php echo $esc($companyFilter); ?>">

            <input type="text" name="location" class="jpg-filter-input"
                   placeholder="📍 <?php echo $esc($t('jobs_filter_location')); ?>"
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
                <option value="">🗂️ <?php echo $esc($t('jobs_filter_category_all')); ?></option>
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
                <option value="">🌍 <?php echo $esc($t('jobs_filter_remote_all')); ?></option>
                <?php foreach ($remoteLabels as $rVal => $rLbl): ?>
                <option value="<?php echo $esc($rVal); ?>" <?php echo $remoteFilter === $rVal ? 'selected' : ''; ?>>
                    <?php echo $esc($rLbl); ?>
                </option>
                <?php endforeach; ?>
            </select>

            <!-- Phase 14.3: Gehalt-Filter -->
            <input type="number" name="salary_min" class="jpg-filter-input jpg-filter-input--salary"
                   placeholder="💰 <?php echo $esc($t('jobs_filter_salary_min')); ?>" min="0" step="500"
                   value="<?php echo $salaryMin > 0 ? $salaryMin : ''; ?>">

            <button type="submit" class="jpg-filter-btn">🔍 <?php echo $esc($t('jobs_filter_button')); ?></button>

            <?php if ($hasFilter): ?>
            <a href="<?php echo $esc($baseListUrl); ?>" class="jpg-filter-reset">✕ <?php echo $esc($t('jobs_filter_reset')); ?></a>
            <?php endif; ?>
        </div>
    </form>
    <?php endif; ?>

    <!-- Job-Liste -->
    <?php if (empty($profiles)): ?>
    <div class="jpg-jobs-list__empty">
        <p class="jpg-empty-icon">📭</p>
        <p><strong><?php echo $esc($t('jobs_no_match_title')); ?></strong></p>
        <p><?php echo $esc($t('jobs_no_match_text')); ?></p>
        <?php if ($hasFilter): ?>
        <a href="<?php echo $esc($baseListUrl); ?>" class="jpg-btn jpg-btn-secondary"><?php echo $esc($t('jobs_show_all')); ?></a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="jpg-jobs-list__grid jpg-jobs-list__grid--<?php echo $esc($cardStyle); ?>">
        <?php foreach ($profiles as $job): ?>
        <?php
            $jobSlug = preg_replace('/[^a-z0-9\-_]/', '', strtolower((string) ($job->slug ?? ''))) ?? '';
            $jobUrl  = (function_exists('jpg_public_path')
                ? jpg_public_path('jobs/' . rawurlencode($jobSlug), $publicLang)
                : '/jobs/' . rawurlencode($jobSlug));
            $typeLabel   = $typeLabels[$job->employment_type ?? ''] ?? ($job->employment_type ?? '');
            $remoteLabel = $remoteLabels[$job->remote_option ?? ''] ?? ($job->remote_option ?? '');
        ?>
        <article class="jpg-job-card-list jpg-job-card-list--<?php echo $esc($cardStyle); ?>">
            <div class="jpg-job-card-list__body">
                <h2 class="jpg-job-card-list__title">
                    <a href="<?php echo $esc($jobUrl); ?>"><?php echo $esc($job->title ?? ''); ?></a>
                </h2>

                <div class="jpg-job-card-list__meta">
                    <?php if ($showCompany && !empty($job->company_name)): ?>
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
                    <?php if ($showRemote && $remoteLabel !== ''): ?>
                    <span class="jpg-meta-item jpg-meta-remote">
                        🌐&nbsp;<?php echo $esc($remoteLabel); ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($showSalary && (!empty($job->salary_min) || !empty($job->salary_max))): ?>
                    <span class="jpg-meta-item jpg-meta-salary">
                        💰&nbsp;<?php
                            $salMin = (int) ($job->salary_min ?? 0);
                            $salMax = (int) ($job->salary_max ?? 0);
                            if ($salMin > 0 && $salMax > 0) {
                                echo number_format($salMin, 0, ',', '.') . ' – ' . number_format($salMax, 0, ',', '.') . ' €/' . $t('salary_year');
                            } elseif ($salMin > 0) {
                                echo $t('salary_from') . ' ' . number_format($salMin, 0, ',', '.') . ' €/' . $t('salary_year');
                            } elseif ($salMax > 0) {
                                echo $t('salary_to') . ' ' . number_format($salMax, 0, ',', '.') . ' €/' . $t('salary_year');
                            }
                        ?>
                    </span>
                    <?php endif; ?>
                    <?php if ($showCategory && !empty($job->category_name)): ?>
                    <span class="jpg-meta-item jpg-meta-category">
                        🗂️&nbsp;<?php echo $esc($job->category_name); ?>
                    </span>
                    <?php endif; ?>
                </div>

                <?php if ($showSummary && !empty(trim($job->summary ?? ''))): ?>
                <p class="jpg-job-card-list__summary">
                    <?php echo $esc(mb_strimwidth(strip_tags($job->summary), 0, 200, '…')); ?>
                </p>
                <?php endif; ?>
            </div>

            <div class="jpg-job-card-list__action">
                <a href="<?php echo $esc($jobUrl); ?>" class="jpg-btn jpg-btn-primary">
                    <?php echo $esc($t('jobs_details_apply')); ?> →
                </a>
            </div>
        </article>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <nav class="jpg-pagination" aria-label="<?php echo $esc($t('jobs_pagination_page', ['page' => $page, 'pages' => $pages])); ?>">
        <?php if ($page > 1): ?>
        <a href="<?php echo $esc($filterUrl(['page' => $page - 1])); ?>" class="jpg-pagination__btn">
            ← <?php echo $esc($t('jobs_pagination_prev')); ?>
        </a>
        <?php endif; ?>

        <span class="jpg-pagination__info">
            <?php echo $esc($t('jobs_pagination_page', ['page' => $page, 'pages' => $pages])); ?>
        </span>

        <?php if ($page < $pages): ?>
        <a href="<?php echo $esc($filterUrl(['page' => $page + 1])); ?>" class="jpg-pagination__btn">
            <?php echo $esc($t('jobs_pagination_next')); ?> →
        </a>
        <?php endif; ?>
    </nav>
    <?php endif; ?>

    <?php endif; ?>

</div><!-- /.jpg-jobs-list -->
