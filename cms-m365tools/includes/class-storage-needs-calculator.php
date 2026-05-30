<?php
/**
 * CMS M365 Tools – Storage-Bedarfs-Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Storage_Needs_Calculator
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        $assumptions = CMS_M365CALCULATOR_Catalog::storage_growth_assumptions();
        $defaults = is_array($assumptions['defaults'] ?? null) ? $assumptions['defaults'] : [];

        return [
            'qualified_licenses' => (int) ($defaults['qualified_licenses'] ?? 250),
            'users' => (int) ($defaults['users'] ?? 250),
            'sharepoint_current_gb' => (float) ($defaults['sharepoint_current_gb'] ?? 1800),
            'sharepoint_largest_site_gb' => (float) ($defaults['sharepoint_largest_site_gb'] ?? 450),
            'sharepoint_sites' => (int) ($defaults['sharepoint_sites'] ?? 35),
            'synced_items' => (int) ($defaults['synced_items'] ?? 180000),
            'largest_file_gb' => (float) ($defaults['largest_file_gb'] ?? 18),
            'onedrive_users' => (int) ($defaults['onedrive_users'] ?? 230),
            'onedrive_avg_gb' => (float) ($defaults['onedrive_avg_gb'] ?? 42),
            'onedrive_quota_gb' => (float) ($defaults['onedrive_quota_gb'] ?? 1024),
            'mailboxes' => (int) ($defaults['mailboxes'] ?? 240),
            'avg_mailbox_gb' => (float) ($defaults['avg_mailbox_gb'] ?? 28),
            'largest_mailbox_gb' => (float) ($defaults['largest_mailbox_gb'] ?? 45),
            'mailbox_plan_limit_gb' => (float) ($defaults['mailbox_plan_limit_gb'] ?? 50),
            'archive_users_percent' => (int) ($defaults['archive_users_percent'] ?? 20),
            'avg_archive_gb' => (float) ($defaults['avg_archive_gb'] ?? 35),
            'archive_model' => (string) ($defaults['archive_model'] ?? 'standard_50'),
            'annual_growth_percent' => (float) ($defaults['annual_growth_percent'] ?? 18),
            'planning_months' => (int) ($defaults['planning_months'] ?? 12),
            'buffer_percent' => (float) ($defaults['buffer_percent'] ?? 15),
            'cleanup_potential_percent' => (float) ($defaults['cleanup_potential_percent'] ?? 8),
            'extra_storage_price_month' => (float) ($defaults['extra_storage_price_month'] ?? 0.2),
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $input = self::default_input();
        $assumptions = CMS_M365CALCULATOR_Catalog::storage_growth_assumptions();
        $limits = is_array($assumptions['limits'] ?? null) ? $assumptions['limits'] : [];
        $planningMonths = array_map('intval', is_array($limits['planning_month_options'] ?? null) ? $limits['planning_month_options'] : [12, 24, 36]);
        $selectedMonths = (int) ($source['planning_months'] ?? $input['planning_months']);

        $input['qualified_licenses'] = self::int_value($source['qualified_licenses'] ?? $input['qualified_licenses'], 1, (int) ($limits['users_max'] ?? 500000));
        $input['users'] = self::int_value($source['users'] ?? $input['users'], 1, (int) ($limits['users_max'] ?? 500000));
        $input['sharepoint_current_gb'] = self::float_value($source['sharepoint_current_gb'] ?? $input['sharepoint_current_gb'], 0, (float) ($limits['gb_max'] ?? 50000000));
        $input['sharepoint_largest_site_gb'] = self::float_value($source['sharepoint_largest_site_gb'] ?? $input['sharepoint_largest_site_gb'], 0, (float) ($limits['gb_max'] ?? 50000000));
        $input['sharepoint_sites'] = self::int_value($source['sharepoint_sites'] ?? $input['sharepoint_sites'], 0, (int) ($limits['sites_max'] ?? 2000000));
        $input['synced_items'] = self::int_value($source['synced_items'] ?? $input['synced_items'], 0, (int) ($limits['items_max'] ?? 50000000));
        $input['largest_file_gb'] = self::float_value($source['largest_file_gb'] ?? $input['largest_file_gb'], 0, (float) ($limits['gb_max'] ?? 50000000));
        $input['onedrive_users'] = self::int_value($source['onedrive_users'] ?? $input['onedrive_users'], 1, (int) ($limits['users_max'] ?? 500000));
        $input['onedrive_avg_gb'] = self::float_value($source['onedrive_avg_gb'] ?? $input['onedrive_avg_gb'], 0, (float) ($limits['gb_max'] ?? 50000000));
        $input['onedrive_quota_gb'] = self::float_value($source['onedrive_quota_gb'] ?? $input['onedrive_quota_gb'], 1, (float) ($limits['gb_max'] ?? 50000000));
        $input['mailboxes'] = self::int_value($source['mailboxes'] ?? $input['mailboxes'], 1, (int) ($limits['users_max'] ?? 500000));
        $input['avg_mailbox_gb'] = self::float_value($source['avg_mailbox_gb'] ?? $input['avg_mailbox_gb'], 0, (float) ($limits['gb_max'] ?? 50000000));
        $input['largest_mailbox_gb'] = self::float_value($source['largest_mailbox_gb'] ?? $input['largest_mailbox_gb'], 0, (float) ($limits['gb_max'] ?? 50000000));
        $input['mailbox_plan_limit_gb'] = self::enum_float($source['mailbox_plan_limit_gb'] ?? $input['mailbox_plan_limit_gb'], [50.0, 100.0], 50.0);
        $input['archive_users_percent'] = self::int_value($source['archive_users_percent'] ?? $input['archive_users_percent'], 0, 100);
        $input['avg_archive_gb'] = self::float_value($source['avg_archive_gb'] ?? $input['avg_archive_gb'], 0, (float) ($limits['gb_max'] ?? 50000000));
        $input['archive_model'] = self::enum((string) ($source['archive_model'] ?? $input['archive_model']), ['none', 'standard_50', 'standard_100', 'auto_expand'], 'standard_50');
        $input['annual_growth_percent'] = self::float_value($source['annual_growth_percent'] ?? $input['annual_growth_percent'], 0, (float) ($limits['growth_percent_max'] ?? 500));
        $input['planning_months'] = in_array($selectedMonths, $planningMonths, true) ? $selectedMonths : 12;
        $input['buffer_percent'] = self::float_value($source['buffer_percent'] ?? $input['buffer_percent'], 0, (float) ($limits['buffer_percent_max'] ?? 200));
        $input['cleanup_potential_percent'] = self::float_value($source['cleanup_potential_percent'] ?? $input['cleanup_potential_percent'], 0, (float) ($limits['cleanup_percent_max'] ?? 90));
        $input['extra_storage_price_month'] = self::float_value($source['extra_storage_price_month'] ?? $input['extra_storage_price_month'], 0, 1000);

        return $input;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $sharepointRules = CMS_M365CALCULATOR_Catalog::sharepoint_storage_rules();
        $onedriveRules = CMS_M365CALCULATOR_Catalog::onedrive_quota_presets();
        $exchangeRules = CMS_M365CALCULATOR_Catalog::exchange_storage_rules();
        $growthRules = CMS_M365CALCULATOR_Catalog::storage_growth_assumptions();
        $requirements = self::calculate_storage_requirements($input, $sharepointRules, $onedriveRules, $exchangeRules);
        $costs = self::calculate_storage_overage_costs($input, $requirements, $sharepointRules);
        $status = self::build_storage_capacity_status($input, $requirements, $sharepointRules, $onedriveRules, $exchangeRules, $growthRules);

        return [
            'input' => $input,
            'requirements' => $requirements,
            'areas' => self::build_area_cards($requirements),
            'costs' => $costs,
            'recommendation' => $status['recommendation'],
            'warnings' => $status['warnings'],
            'next_steps' => $status['next_steps'],
            'limits' => self::build_limit_rows($requirements, $sharepointRules, $onedriveRules, $exchangeRules),
            'mailbox_plan_options' => self::mailbox_plan_options($exchangeRules),
            'archive_model_options' => self::archive_model_options($exchangeRules),
            'planning_month_options' => self::planning_month_options($growthRules),
            'meta' => [
                'source_checked' => self::source_checked([$sharepointRules, $onedriveRules, $exchangeRules, $growthRules]),
                'price_basis' => (string) ($sharepointRules['meta']['price_basis'] ?? $growthRules['meta']['display_text'] ?? ''),
            ],
            'sources' => self::sources([$sharepointRules, $onedriveRules, $exchangeRules]),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $sharepointRules
     * @param array<string,mixed> $onedriveRules
     * @param array<string,mixed> $exchangeRules
     * @return array<string,mixed>
     */
    public static function calculate_storage_requirements(array $input, array $sharepointRules, array $onedriveRules, array $exchangeRules): array
    {
        $growthFactor = self::growth_factor((float) $input['annual_growth_percent'], (int) $input['planning_months']);
        $bufferFactor = 1 + ((float) $input['buffer_percent'] / 100);
        $cleanupFactor = max(0.0, 1 - ((float) $input['cleanup_potential_percent'] / 100));
        $tenantPool = is_array($sharepointRules['tenant_pool'] ?? null) ? $sharepointRules['tenant_pool'] : [];
        $siteLimits = is_array($sharepointRules['site_limits'] ?? null) ? $sharepointRules['site_limits'] : [];
        $fileLimits = is_array($sharepointRules['file_and_sync_limits'] ?? null) ? $sharepointRules['file_and_sync_limits'] : [];
        $onedriveLimits = is_array($onedriveRules['limits'] ?? null) ? $onedriveRules['limits'] : [];
        $mailboxLimits = is_array($exchangeRules['primary_mailbox_limits'] ?? null) ? $exchangeRules['primary_mailbox_limits'] : [];
        $archiveLimits = is_array($exchangeRules['archive_limits'] ?? null) ? $exchangeRules['archive_limits'] : [];

        $sharepointIncluded = (float) ($tenantPool['base_gb'] ?? 1024) + ((int) $input['qualified_licenses'] * (float) ($tenantPool['gb_per_qualified_license'] ?? 10));
        $sharepointForecast = (float) $input['sharepoint_current_gb'] * $growthFactor * $bufferFactor;
        $sharepointAfterCleanup = $sharepointForecast * $cleanupFactor;
        $largestSiteForecast = (float) $input['sharepoint_largest_site_gb'] * $growthFactor * $bufferFactor;
        $maxSiteGb = (float) ($siteLimits['max_site_gb'] ?? 25600);

        $onedriveForecastAvg = (float) $input['onedrive_avg_gb'] * $growthFactor * $bufferFactor;
        $onedriveForecastTotal = (int) $input['onedrive_users'] * $onedriveForecastAvg;
        $onedriveCapacity = (int) $input['onedrive_users'] * (float) $input['onedrive_quota_gb'];
        $onedriveAfterCleanup = $onedriveForecastTotal * $cleanupFactor;

        $mailboxForecastAvg = (float) $input['avg_mailbox_gb'] * $growthFactor * $bufferFactor;
        $exchangeForecastTotal = (int) $input['mailboxes'] * $mailboxForecastAvg;
        $exchangeCapacity = (int) $input['mailboxes'] * (float) $input['mailbox_plan_limit_gb'];
        $largestMailboxForecast = (float) $input['largest_mailbox_gb'] * $growthFactor * $bufferFactor;
        $exchangeAfterCleanup = $exchangeForecastTotal * $cleanupFactor;
        $archive = self::calculate_exchange_archive_need($input, $growthFactor, $bufferFactor, $exchangeRules);

        return [
            'growth' => [
                'factor' => $growthFactor,
                'buffer_factor' => $bufferFactor,
                'cleanup_factor' => $cleanupFactor,
                'planning_months' => (int) $input['planning_months'],
            ],
            'sharepoint' => [
                'current_gb' => (float) $input['sharepoint_current_gb'],
                'forecast_gb' => $sharepointForecast,
                'after_cleanup_gb' => $sharepointAfterCleanup,
                'included_gb' => $sharepointIncluded,
                'overage_gb' => max(0.0, $sharepointForecast - $sharepointIncluded),
                'overage_after_cleanup_gb' => max(0.0, $sharepointAfterCleanup - $sharepointIncluded),
                'usage_ratio' => self::ratio($sharepointForecast, $sharepointIncluded),
                'cleanup_ratio' => self::ratio($sharepointAfterCleanup, $sharepointIncluded),
                'largest_site_gb' => (float) $input['sharepoint_largest_site_gb'],
                'largest_site_forecast_gb' => $largestSiteForecast,
                'site_limit_gb' => $maxSiteGb,
                'site_ratio' => self::ratio($largestSiteForecast, $maxSiteGb),
                'sites' => (int) $input['sharepoint_sites'],
                'reporting_lag_hours_min' => (int) ($tenantPool['reporting_lag_hours_min'] ?? 24),
                'reporting_lag_hours_max' => (int) ($tenantPool['reporting_lag_hours_max'] ?? 48),
            ],
            'onedrive' => [
                'users' => (int) $input['onedrive_users'],
                'current_total_gb' => (int) $input['onedrive_users'] * (float) $input['onedrive_avg_gb'],
                'forecast_total_gb' => $onedriveForecastTotal,
                'after_cleanup_gb' => $onedriveAfterCleanup,
                'quota_total_gb' => $onedriveCapacity,
                'quota_per_user_gb' => (float) $input['onedrive_quota_gb'],
                'forecast_avg_gb' => $onedriveForecastAvg,
                'overage_gb' => max(0.0, $onedriveForecastTotal - $onedriveCapacity),
                'overage_after_cleanup_gb' => max(0.0, $onedriveAfterCleanup - $onedriveCapacity),
                'usage_ratio' => self::ratio($onedriveForecastTotal, $onedriveCapacity),
                'cleanup_ratio' => self::ratio($onedriveAfterCleanup, $onedriveCapacity),
                'max_common_user_quota_gb' => (float) ($onedriveLimits['max_common_user_quota_gb'] ?? 5120),
                'files_restore_days' => (int) ($onedriveLimits['files_restore_days'] ?? 30),
                'recycle_bin_days_work_school' => (int) ($onedriveLimits['recycle_bin_days_work_school'] ?? 93),
            ],
            'exchange' => [
                'mailboxes' => (int) $input['mailboxes'],
                'current_total_gb' => (int) $input['mailboxes'] * (float) $input['avg_mailbox_gb'],
                'forecast_total_gb' => $exchangeForecastTotal,
                'after_cleanup_gb' => $exchangeAfterCleanup,
                'capacity_gb' => $exchangeCapacity,
                'plan_limit_gb' => (float) $input['mailbox_plan_limit_gb'],
                'forecast_avg_gb' => $mailboxForecastAvg,
                'largest_mailbox_forecast_gb' => $largestMailboxForecast,
                'overage_gb' => max(0.0, $exchangeForecastTotal - $exchangeCapacity),
                'overage_after_cleanup_gb' => max(0.0, $exchangeAfterCleanup - $exchangeCapacity),
                'usage_ratio' => self::ratio($exchangeForecastTotal, $exchangeCapacity),
                'largest_ratio' => self::ratio($largestMailboxForecast, (float) $input['mailbox_plan_limit_gb']),
                'cleanup_ratio' => self::ratio($exchangeAfterCleanup, $exchangeCapacity),
                'recoverable_items_default_gb' => (float) ($mailboxLimits['recoverable_items_default_gb'] ?? 30),
                'recoverable_items_hold_gb' => (float) ($mailboxLimits['recoverable_items_hold_gb'] ?? 100),
            ],
            'archive' => $archive,
            'operations' => [
                'synced_items' => (int) $input['synced_items'],
                'recommended_sync_items' => (int) ($fileLimits['recommended_sync_items'] ?? 300000),
                'preview_sync_items' => (int) ($fileLimits['preview_sync_items'] ?? 1000000),
                'largest_file_gb' => (float) $input['largest_file_gb'],
                'max_file_upload_gb' => (float) ($fileLimits['max_file_upload_gb'] ?? 250),
                'max_decoded_path_characters' => (int) ($fileLimits['max_decoded_path_characters'] ?? 400),
                'max_items_per_library' => (int) ($fileLimits['max_items_per_library'] ?? 30000000),
                'unique_permissions_recommended' => (int) ($fileLimits['unique_permissions_recommended'] ?? 5000),
                'unique_permissions_supported' => (int) ($fileLimits['unique_permissions_supported'] ?? 50000),
            ],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $requirements
     * @param array<string,mixed> $sharepointRules
     * @return array<string,mixed>
     */
    public static function calculate_storage_overage_costs(array $input, array $requirements, array $sharepointRules): array
    {
        $tenantPool = is_array($sharepointRules['tenant_pool'] ?? null) ? $sharepointRules['tenant_pool'] : [];
        $increment = max(1.0, (float) ($tenantPool['extra_storage_increment_gb'] ?? 1));
        $sharepointOverage = (float) ($requirements['sharepoint']['overage_gb'] ?? 0);
        $neededExtra = $sharepointOverage > 0 ? ceil($sharepointOverage / $increment) * $increment : 0.0;
        $price = max(0.0, (float) $input['extra_storage_price_month']);
        $monthly = $neededExtra * $price;

        return [
            'needed_extra_gb' => $neededExtra,
            'monthly' => $monthly,
            'yearly' => $monthly * 12,
            'unit_price_month' => $price,
            'currency' => (string) ($tenantPool['currency'] ?? 'EUR'),
            'increment_gb' => $increment,
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $exchangeRules
     * @return array<string,mixed>
     */
    public static function calculate_exchange_archive_need(array $input, float $growthFactor, float $bufferFactor, array $exchangeRules): array
    {
        $archiveLimits = is_array($exchangeRules['archive_limits'] ?? null) ? $exchangeRules['archive_limits'] : [];
        $archiveUsers = (int) round((int) $input['mailboxes'] * ((int) $input['archive_users_percent'] / 100));
        $model = (string) $input['archive_model'];
        $capacityPerMailbox = match ($model) {
            'standard_50' => (float) ($archiveLimits['standard_50_gb'] ?? 50),
            'standard_100' => (float) ($archiveLimits['standard_100_gb'] ?? 100),
            'auto_expand' => (float) ($archiveLimits['auto_expanding_gb'] ?? 1500),
            default => 0.0,
        };
        $current = $archiveUsers * (float) $input['avg_archive_gb'];
        $forecast = $current * $growthFactor * $bufferFactor;
        $capacity = $archiveUsers * $capacityPerMailbox;

        return [
            'archive_users' => $archiveUsers,
            'model' => $model,
            'current_total_gb' => $current,
            'forecast_total_gb' => $forecast,
            'capacity_gb' => $capacity,
            'capacity_per_mailbox_gb' => $capacityPerMailbox,
            'overage_gb' => max(0.0, $forecast - $capacity),
            'usage_ratio' => self::ratio($forecast, $capacity),
            'auto_expanding_trigger_gb' => (float) ($archiveLimits['auto_expanding_trigger_gb'] ?? 90),
            'auto_expanding_limit_gb' => (float) ($archiveLimits['auto_expanding_gb'] ?? 1500),
            'provisioning_days' => (int) ($archiveLimits['additional_storage_provisioning_days'] ?? 30),
            'daily_growth_warning_gb' => (float) ($archiveLimits['daily_growth_warning_gb'] ?? 1),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $requirements
     * @param array<string,mixed> $sharepointRules
     * @param array<string,mixed> $onedriveRules
     * @param array<string,mixed> $exchangeRules
     * @param array<string,mixed> $growthRules
     * @return array<string,mixed>
     */
    public static function build_storage_capacity_status(array $input, array $requirements, array $sharepointRules, array $onedriveRules, array $exchangeRules, array $growthRules): array
    {
        $labels = is_array($growthRules['status_labels'] ?? null) ? $growthRules['status_labels'] : [];
        $warnings = [];
        $nextSteps = [];
        $score = 100;
        $key = 'success';

        $sharepointRatio = (float) ($requirements['sharepoint']['usage_ratio'] ?? 0);
        $sharepointCleanupRatio = (float) ($requirements['sharepoint']['cleanup_ratio'] ?? 0);
        $siteRatio = (float) ($requirements['sharepoint']['site_ratio'] ?? 0);
        $onedriveRatio = (float) ($requirements['onedrive']['usage_ratio'] ?? 0);
        $exchangeRatio = (float) ($requirements['exchange']['usage_ratio'] ?? 0);
        $largestMailboxRatio = (float) ($requirements['exchange']['largest_ratio'] ?? 0);
        $archiveRatio = (float) ($requirements['archive']['usage_ratio'] ?? 0);
        $syncedItems = (int) ($requirements['operations']['synced_items'] ?? 0);
        $recommendedSync = (int) ($requirements['operations']['recommended_sync_items'] ?? 300000);
        $previewSync = (int) ($requirements['operations']['preview_sync_items'] ?? 1000000);
        $largestFile = (float) ($requirements['operations']['largest_file_gb'] ?? 0);
        $maxFile = (float) ($requirements['operations']['max_file_upload_gb'] ?? 250);
        $cleanupPotential = (float) $input['cleanup_potential_percent'];

        foreach ([
            'SharePoint-Pool' => $sharepointRatio,
            'OneDrive-Quotas' => $onedriveRatio,
            'Exchange-Postfächer' => $exchangeRatio,
            'Exchange-Archiv' => $archiveRatio,
        ] as $label => $ratio) {
            if ($ratio >= 1.0) {
                $key = self::max_key($key, 'capacity');
                $score -= 24;
                $warnings[] = $label . ' überschreitet im Planungszeitraum die modellierte Kapazität.';
            } elseif ($ratio >= 0.95) {
                $key = self::max_key($key, 'danger');
                $score -= 18;
                $warnings[] = $label . ' liegt im kritischen Pufferbereich.';
            } elseif ($ratio >= 0.8) {
                $key = self::max_key($key, 'watch');
                $score -= 8;
                $warnings[] = $label . ' sollte aktiv beobachtet werden.';
            }
        }

        if ($siteRatio >= 0.95) {
            $key = self::max_key($key, 'danger');
            $score -= 20;
            $warnings[] = 'Die größte Site nähert sich der Microsoft-Grenze von 25 TB.';
            $nextSteps[] = 'Große Site in Hub-/Site-Architektur, Archivbereiche oder Datenlebenszyklus aufteilen.';
        } elseif ($siteRatio >= 0.8) {
            $key = self::max_key($key, 'watch');
            $warnings[] = 'Die größte Site wird zum Engpass, obwohl der Tenant-Pool noch reichen kann.';
        }

        if ($largestMailboxRatio >= 1.0) {
            $key = self::max_key($key, 'capacity');
            $score -= 18;
            $warnings[] = 'Die größte Mailbox überschreitet die gewählte Primärpostfachgröße.';
            $nextSteps[] = 'Mailbox-Archivierung, Plan 2, E3/E5 oder passende Add-on-Strategie prüfen.';
        } elseif ($largestMailboxRatio >= 0.9) {
            $key = self::max_key($key, 'watch');
            $warnings[] = 'Die größte Mailbox liegt nah an der gewählten Primärpostfachgröße.';
        }

        if ($syncedItems > $previewSync) {
            $key = self::max_key($key, 'danger');
            $score -= 20;
            $warnings[] = 'Die synchronisierten Elemente liegen über dem Public-Preview-Richtwert von 1.000.000 Elementen je Sync-Instanz.';
            $nextSteps[] = 'Sync-Strategie mit Shortcuts, Files On-Demand, weniger Bibliotheken und Sync Reports neu planen.';
        } elseif ($syncedItems > $recommendedSync) {
            $key = self::max_key($key, 'watch');
            $score -= 8;
            $warnings[] = 'Die synchronisierten Elemente liegen über dem empfohlenen Performance-Richtwert von 300.000 Elementen.';
            $nextSteps[] = 'OneDrive-Sync-Bereiche verkleinern und große Bibliotheken nicht vollständig synchronisieren.';
        }

        if ($largestFile > $maxFile) {
            $key = self::max_key($key, 'danger');
            $score -= 20;
            $warnings[] = 'Die größte Datei überschreitet die Microsoft-Grenze für einzelne Dateien.';
        } elseif ($largestFile >= $maxFile * 0.8) {
            $key = self::max_key($key, 'watch');
            $warnings[] = 'Sehr große Einzeldateien sollten vor Migration und Sync gesondert betrachtet werden.';
        }

        if ($cleanupPotential >= 15 && (($sharepointRatio >= 0.8 && $sharepointCleanupRatio < $sharepointRatio) || (float) ($requirements['sharepoint']['overage_after_cleanup_gb'] ?? 0) < (float) ($requirements['sharepoint']['overage_gb'] ?? 0))) {
            $key = self::max_key($key, 'governance');
            $nextSteps[] = 'Cleanup, Versionierung und Lifecycle-Regeln vor reinem Zusatzspeicher bewerten.';
        }

        if ((float) $input['annual_growth_percent'] >= 30 && $cleanupPotential < 10) {
            $key = self::max_key($key, 'watch');
            $warnings[] = 'Hohes Wachstum ohne nennenswerten Cleanup-Anteil macht die Kapazitätsplanung schnell instabil.';
            $nextSteps[] = 'Wachstum monatlich messen und Datenklassen mit Aufbewahrung, Archiv und Löschkonzept versehen.';
        }

        if ($nextSteps === []) {
            $nextSteps[] = 'Kapazitäten quartalsweise gegen Verbrauch, Wachstum und größte Sites prüfen.';
            $nextSteps[] = 'OneDrive Files On-Demand, Known Folder Move und Sync Reports als Betriebsstandard halten.';
        }

        $score = max(0, min(100, $score));
        $tone = match ($key) {
            'success' => 'success',
            'watch', 'governance', 'capacity' => 'warning',
            'danger' => 'danger',
            default => 'info',
        };

        return [
            'recommendation' => [
                'key' => $key,
                'tone' => $tone,
                'label' => (string) ($labels[$key] ?? 'Storage-Bedarf prüfen'),
                'summary' => self::summary_text($key, $requirements),
                'score' => $score,
            ],
            'warnings' => array_values(array_unique($warnings)),
            'next_steps' => array_values(array_unique($nextSteps)),
        ];
    }

    public static function render_storage_calculator_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-storage-needs-calculator.php';
    }

    /** @return array<string,mixed> */
    public static function load_sharepoint_storage_rules(): array
    {
        return CMS_M365CALCULATOR_Catalog::sharepoint_storage_rules();
    }

    /** @return array<string,mixed> */
    public static function load_onedrive_quota_presets(): array
    {
        return CMS_M365CALCULATOR_Catalog::onedrive_quota_presets();
    }

    /** @return array<string,mixed> */
    public static function load_exchange_storage_rules(): array
    {
        return CMS_M365CALCULATOR_Catalog::exchange_storage_rules();
    }

    /** @return array<string,mixed> */
    public static function load_storage_growth_assumptions(): array
    {
        return CMS_M365CALCULATOR_Catalog::storage_growth_assumptions();
    }

    /**
     * @param array<string,mixed> $requirements
     * @return array<int,array<string,mixed>>
     */
    private static function build_area_cards(array $requirements): array
    {
        return [
            self::area('sharepoint', 'SharePoint-Dateispeicher', $requirements['sharepoint'] ?? [], 'included_gb', 'forecast_gb', 'overage_gb'),
            self::area('onedrive', 'OneDrive-Benutzerspeicher', $requirements['onedrive'] ?? [], 'quota_total_gb', 'forecast_total_gb', 'overage_gb'),
            self::area('exchange', 'Exchange-Postfächer', $requirements['exchange'] ?? [], 'capacity_gb', 'forecast_total_gb', 'overage_gb'),
            self::area('archive', 'Exchange-Archiv', $requirements['archive'] ?? [], 'capacity_gb', 'forecast_total_gb', 'overage_gb'),
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private static function area(string $key, string $label, array $data, string $capacityKey, string $forecastKey, string $overageKey): array
    {
        $ratio = (float) ($data['usage_ratio'] ?? 0);

        return [
            'key' => $key,
            'label' => $label,
            'forecast_gb' => (float) ($data[$forecastKey] ?? 0),
            'capacity_gb' => (float) ($data[$capacityKey] ?? 0),
            'overage_gb' => (float) ($data[$overageKey] ?? 0),
            'ratio' => $ratio,
            'tone' => $ratio >= 1.0 ? 'danger' : ($ratio >= 0.8 ? 'warning' : 'success'),
        ];
    }

    /**
     * @param array<string,mixed> $requirements
     * @param array<string,mixed> $sharepointRules
     * @param array<string,mixed> $onedriveRules
     * @param array<string,mixed> $exchangeRules
     * @return array<int,array<string,string>>
     */
    private static function build_limit_rows(array $requirements, array $sharepointRules, array $onedriveRules, array $exchangeRules): array
    {
        $ops = is_array($requirements['operations'] ?? null) ? $requirements['operations'] : [];
        $sharepoint = is_array($requirements['sharepoint'] ?? null) ? $requirements['sharepoint'] : [];
        $onedrive = is_array($requirements['onedrive'] ?? null) ? $requirements['onedrive'] : [];
        $archive = is_array($requirements['archive'] ?? null) ? $requirements['archive'] : [];

        return [
            [
                'label' => 'SharePoint-Pool',
                'value' => self::format_gb((float) ($sharepoint['included_gb'] ?? 0)),
                'note' => '1 TB plus 10 GB je qualifizierter Lizenz; Auswertungen können 24–48 Stunden nachlaufen.',
            ],
            [
                'label' => 'Größte Site',
                'value' => self::format_gb((float) ($sharepoint['largest_site_forecast_gb'] ?? 0)) . ' von ' . self::format_gb((float) ($sharepoint['site_limit_gb'] ?? 25600)),
                'note' => 'Eine einzelne Site kann sehr groß werden, löst aber kein Pool-Problem.',
            ],
            [
                'label' => 'Sync-Elemente',
                'value' => number_format((int) ($ops['synced_items'] ?? 0), 0, ',', '.'),
                'note' => 'Microsoft nennt 300.000 Elemente als wichtigen Performance-Richtwert.',
            ],
            [
                'label' => 'Einzeldatei',
                'value' => self::format_gb((float) ($ops['largest_file_gb'] ?? 0)) . ' von ' . self::format_gb((float) ($ops['max_file_upload_gb'] ?? 250)),
                'note' => 'Der Dateipfad darf dekodiert höchstens 400 Zeichen erreichen.',
            ],
            [
                'label' => 'OneDrive-Quota',
                'value' => self::format_gb((float) ($onedrive['quota_per_user_gb'] ?? 0)) . ' je Nutzer',
                'note' => 'Viele Pläne starten bei 1 TB; geeignete Umgebungen können bis 5 TB je Nutzer planen.',
            ],
            [
                'label' => 'Archivgrenze',
                'value' => self::format_gb((float) ($archive['capacity_per_mailbox_gb'] ?? 0)) . ' je Archiv',
                'note' => 'Auto-expanding Archive ist als bis zu 1,5 TB je berechtigtem Archiv modelliert.',
            ],
        ];
    }

    /**
     * @param array<string,mixed> $exchangeRules
     * @return array<string,string>
     */
    private static function mailbox_plan_options(array $exchangeRules): array
    {
        $options = is_array($exchangeRules['plan_options'] ?? null) ? $exchangeRules['plan_options'] : [];
        $normalized = [];
        foreach ($options as $key => $label) {
            $normalized[(string) $key] = (string) $label;
        }

        return $normalized ?: ['50' => '50 GB Primärpostfach', '100' => '100 GB Primärpostfach'];
    }

    /**
     * @param array<string,mixed> $exchangeRules
     * @return array<string,string>
     */
    private static function archive_model_options(array $exchangeRules): array
    {
        $options = is_array($exchangeRules['archive_options'] ?? null) ? $exchangeRules['archive_options'] : [];
        $normalized = [];
        foreach ($options as $key => $label) {
            $normalized[(string) $key] = (string) $label;
        }

        return $normalized ?: ['none' => 'Kein Archiv geplant', 'standard_50' => '50 GB Archiv', 'standard_100' => '100 GB Archiv', 'auto_expand' => 'Auto-expanding bis 1,5 TB'];
    }

    /**
     * @param array<string,mixed> $growthRules
     * @return array<int,int>
     */
    private static function planning_month_options(array $growthRules): array
    {
        $limits = is_array($growthRules['limits'] ?? null) ? $growthRules['limits'] : [];
        $options = array_map('intval', is_array($limits['planning_month_options'] ?? null) ? $limits['planning_month_options'] : [12, 24, 36]);

        return $options !== [] ? $options : [12, 24, 36];
    }

    /**
     * @param array<int,array<string,mixed>> $catalogs
     * @return array<int,string>
     */
    private static function sources(array $catalogs): array
    {
        $sources = [];
        foreach ($catalogs as $catalog) {
            foreach ((array) ($catalog['meta']['sources'] ?? []) as $source) {
                $sources[] = (string) $source;
            }
        }

        return array_values(array_unique(array_filter($sources)));
    }

    /**
     * @param array<int,array<string,mixed>> $catalogs
     */
    private static function source_checked(array $catalogs): string
    {
        $dates = [];
        foreach ($catalogs as $catalog) {
            $date = (string) ($catalog['meta']['source_checked'] ?? '');
            if ($date !== '') {
                $dates[] = $date;
            }
        }
        rsort($dates);

        return $dates[0] ?? '2026-05-17';
    }

    /**
     * @param array<string,mixed> $requirements
     */
    private static function summary_text(string $key, array $requirements): string
    {
        $sharepointOverage = (float) ($requirements['sharepoint']['overage_gb'] ?? 0);
        $exchangeOverage = (float) ($requirements['exchange']['overage_gb'] ?? 0);
        $archiveOverage = (float) ($requirements['archive']['overage_gb'] ?? 0);

        return match ($key) {
            'success' => 'Die modellierten Kapazitäten reichen im gewählten Zeitraum mit Puffer aus.',
            'watch' => 'Mindestens ein Bereich nähert sich einer relevanten Kapazitäts- oder Betriebsgrenze.',
            'capacity' => 'Mindestens ein Bereich überschreitet die modellierte Kapazität; Zusatzspeicher, Archiv oder Plananpassung sind einzuplanen.',
            'danger' => 'Ein operativer Grenzwert ist kritisch nah oder bereits überschritten; vor Wachstum oder Migration zuerst entschärfen.',
            'governance' => 'Cleanup und Lifecycle-Regeln können den Bedarf spürbar senken, bevor Zusatzspeicher gekauft wird.',
            default => 'SharePoint, OneDrive, Exchange und Archiv wurden getrennt bewertet.',
        } . ' SharePoint-Überhang: ' . self::format_gb($sharepointOverage) . ', Exchange-Überhang: ' . self::format_gb($exchangeOverage + $archiveOverage) . '.';
    }

    private static function max_key(string $current, string $candidate): string
    {
        $order = ['success' => 0, 'governance' => 1, 'watch' => 2, 'capacity' => 3, 'danger' => 4];
        return ($order[$candidate] ?? 0) > ($order[$current] ?? 0) ? $candidate : $current;
    }

    private static function growth_factor(float $annualGrowthPercent, int $months): float
    {
        return (1 + max(0.0, $annualGrowthPercent) / 100) ** (max(0, $months) / 12);
    }

    private static function ratio(float $value, float $capacity): float
    {
        return $capacity > 0 ? max(0.0, $value / $capacity) : ($value > 0 ? 1.0 : 0.0);
    }

    private static function int_value(mixed $value, int $min, int $max): int
    {
        $number = is_numeric($value) ? (int) $value : $min;
        return max($min, min($max, $number));
    }

    private static function float_value(mixed $value, float $min, float $max): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }
        $number = is_numeric($value) ? (float) $value : $min;

        return max($min, min($max, $number));
    }

    /** @param array<int,float> $allowed */
    private static function enum_float(mixed $value, array $allowed, float $fallback): float
    {
        $number = self::float_value($value, 0, 1000000);
        foreach ($allowed as $allowedValue) {
            if (abs($number - $allowedValue) < 0.001) {
                return $allowedValue;
            }
        }

        return $fallback;
    }

    /** @param array<int,string> $allowed */
    private static function enum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function format_gb(float $value): string
    {
        if ($value >= 1024) {
            return number_format($value / 1024, 2, ',', '.') . ' TB';
        }

        return number_format($value, 1, ',', '.') . ' GB';
    }
}
