<?php
/**
 * CMS M365 Calculator – Archive Mailbox Rechner.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Archive_Mailbox_Calculator
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        $assumptions = CMS_M365CALCULATOR_Catalog::archive_mailbox_assumptions();
        $defaults = is_array($assumptions['defaults'] ?? null) ? $assumptions['defaults'] : [];

        return [
            'base_plan' => (string) ($defaults['base_plan'] ?? 'm365-business-standard'),
            'mailbox_type' => (string) ($defaults['mailbox_type'] ?? 'user'),
            'mailbox_count' => (int) ($defaults['mailbox_count'] ?? 25),
            'current_primary_gb' => (float) ($defaults['current_primary_gb'] ?? 35),
            'current_archive_gb' => (float) ($defaults['current_archive_gb'] ?? 20),
            'monthly_growth_gb' => (float) ($defaults['monthly_growth_gb'] ?? 3),
            'daily_archive_growth_gb' => (float) ($defaults['daily_archive_growth_gb'] ?? 0.2),
            'retention_years' => (int) ($defaults['retention_years'] ?? 7),
            'archive_goal' => (string) ($defaults['archive_goal'] ?? 'auto_expand'),
            'needs_hold' => !empty($defaults['needs_hold']),
            'needs_purview_premium' => !empty($defaults['needs_purview_premium']),
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $input = self::default_input();
        $input['base_plan'] = self::clean_key((string) ($source['base_plan'] ?? $input['base_plan']));
        $input['mailbox_type'] = self::enum((string) ($source['mailbox_type'] ?? $input['mailbox_type']), ['user', 'shared', 'resource'], 'user');
        $input['mailbox_count'] = max(1, min(500000, (int) ($source['mailbox_count'] ?? $input['mailbox_count'])));
        $input['current_primary_gb'] = self::bounded_float($source['current_primary_gb'] ?? $input['current_primary_gb'], 0, 100000);
        $input['current_archive_gb'] = self::bounded_float($source['current_archive_gb'] ?? $input['current_archive_gb'], 0, 1500000);
        $input['monthly_growth_gb'] = self::bounded_float($source['monthly_growth_gb'] ?? $input['monthly_growth_gb'], 0, 100000);
        $input['daily_archive_growth_gb'] = self::bounded_float($source['daily_archive_growth_gb'] ?? $input['daily_archive_growth_gb'], 0, 1000);
        $input['retention_years'] = max(0, min(100, (int) ($source['retention_years'] ?? $input['retention_years'])));
        $input['archive_goal'] = self::enum((string) ($source['archive_goal'] ?? $input['archive_goal']), ['none', 'standard', 'auto_expand'], 'auto_expand');
        $input['needs_hold'] = self::truthy($source['needs_hold'] ?? $input['needs_hold']);
        $input['needs_purview_premium'] = self::truthy($source['needs_purview_premium'] ?? $input['needs_purview_premium']);

        return $input;
    }

    /**
     * @return array<string,string>
     */
    public static function plan_options(): array
    {
        $options = [];
        foreach (self::plans_index() as $slug => $plan) {
            $options[$slug] = (string) ($plan['name'] ?? $slug);
        }

        return $options;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $catalog = CMS_M365CALCULATOR_Catalog::archive_mailbox_plans();
        $assumptions = CMS_M365CALCULATOR_Catalog::archive_mailbox_assumptions();
        $limits = is_array($assumptions['limits'] ?? null) ? $assumptions['limits'] : [];
        $thresholds = is_array($assumptions['recommendation_thresholds'] ?? null) ? $assumptions['recommendation_thresholds'] : [];
        $messages = is_array($assumptions['messages'] ?? null) ? $assumptions['messages'] : [];
        $plans = self::plans_index($catalog);
        $addons = self::addons_index($catalog);
        $plan = is_array($plans[(string) ($input['base_plan'] ?? '')] ?? null) ? $plans[(string) $input['base_plan']] : reset($plans);
        $plan = is_array($plan) ? $plan : [];
        $addon = is_array($addons['exchange-online-archiving'] ?? null) ? $addons['exchange-online-archiving'] : [];

        $horizonMonths = max(1, (int) ($thresholds['horizon_months'] ?? 12));
        $primaryCapacity = self::primary_capacity($input, $plan, $limits);
        $archiveCapacity = self::archive_capacity($input, $plan, $addon, $limits);
        $projectedPrimaryGb = (float) $input['current_primary_gb'] + ((float) $input['monthly_growth_gb'] * $horizonMonths);
        $projectedArchiveGb = (float) $input['current_archive_gb'] + ((float) $input['monthly_growth_gb'] * $horizonMonths);
        $needsAutoExpansion = (string) ($input['archive_goal'] ?? '') === 'auto_expand' || $projectedArchiveGb > (float) ($plan['archive_mailbox_gb'] ?? 0);
        $hasAutoExpansion = !empty($plan['auto_expanding_included']);
        $warnings = [];
        $actions = [];
        $status = 'ok';

        if ($projectedPrimaryGb > $primaryCapacity) {
            $status = 'danger';
            $actions[] = 'Primärmailbox-Kapazität erhöhen: Exchange Online Plan 2, Microsoft 365 E3/E5 oder passende Shared-Mailbox-Lizenz prüfen.';
        } elseif ($projectedPrimaryGb >= $primaryCapacity * (float) ($thresholds['primary_warning_ratio'] ?? 0.9)) {
            $status = self::max_status($status, 'warning');
            $warnings[] = 'Die Primärmailbox nähert sich im 12-Monats-Horizont der gepflegten Kapazitätsgrenze.';
        }

        if ((string) ($input['archive_goal'] ?? '') !== 'none' && $projectedArchiveGb > $archiveCapacity) {
            $status = 'danger';
            $actions[] = $hasAutoExpansion
                ? 'Archivwachstum reduzieren oder Microsoft-Support-/Compliance-Design prüfen; 1,5 TB sind als Obergrenze modelliert.'
                : 'Exchange Online Archiving Add-on oder Upgrade auf Business Premium, Exchange Online Plan 2, Microsoft 365 E3/E5 prüfen.';
        } elseif ((string) ($input['archive_goal'] ?? '') !== 'none' && $archiveCapacity > 0 && $projectedArchiveGb >= $archiveCapacity * (float) ($thresholds['archive_warning_ratio'] ?? 0.85)) {
            $status = self::max_status($status, 'warning');
            $warnings[] = 'Das Archiv nähert sich der modellierten Kapazitätsgrenze.';
        }

        if ($needsAutoExpansion && !$hasAutoExpansion) {
            $status = self::max_status($status, 'warning');
            $actions[] = 'Auto-expanding Archive wird benötigt, ist im gewählten Basisplan aber nicht enthalten; Exchange Online Archiving Add-on einplanen.';
        }

        if (!empty($input['needs_hold']) && empty($plan['hold_supported'])) {
            $status = self::max_status($status, 'warning');
            $actions[] = 'Für Litigation Hold/In-Place Hold einen Plan mit Hold-Rechten oder Exchange Online Archiving Add-on prüfen.';
        }

        if ((float) ($input['daily_archive_growth_gb'] ?? 0) > (float) ($limits['auto_expanding_daily_growth_warning_gb'] ?? 1)) {
            $status = self::max_status($status, 'warning');
            $warnings[] = 'Das tägliche Archivwachstum liegt über 1 GB/Tag; Microsoft nennt das als kritische Grenze für Auto-expanding Archive.';
        }

        if ((string) ($input['mailbox_type'] ?? 'user') === 'shared') {
            $warnings[] = 'Shared Mailboxes benötigen für 100 GB, Archiv, Hold, Defender-/Purview- oder Premium-Compliance-Szenarien eine passende Lizenz.';
        }

        if (!empty($input['needs_purview_premium'])) {
            $warnings[] = 'Premium-Purview-Funktionen wie eDiscovery Premium, Audit Premium, automatische Sensitivity Labels oder Insider Risk separat lizenzieren.';
        }

        foreach (['journal_warning', 'cannot_disable_autoexpanding', 'inactive_mailbox_warning'] as $messageKey) {
            if (!empty($messages[$messageKey])) {
                $warnings[] = (string) $messages[$messageKey];
            }
        }

        $recommendedAddonMonthly = (!$hasAutoExpansion && $needsAutoExpansion && is_numeric($addon['price_month'] ?? null))
            ? (float) $addon['price_month'] * (int) $input['mailbox_count']
            : 0.0;
        $recommendation = self::build_recommendation($status, $needsAutoExpansion, $hasAutoExpansion, $plan, $addon);

        return [
            'input' => $input,
            'plan' => $plan,
            'addon' => $addon,
            'plan_options' => self::plan_options(),
            'capacity' => [
                'primary_gb' => $primaryCapacity,
                'archive_gb' => $archiveCapacity,
                'projected_primary_gb' => $projectedPrimaryGb,
                'projected_archive_gb' => $projectedArchiveGb,
                'horizon_months' => $horizonMonths,
                'auto_expanding_included' => $hasAutoExpansion,
                'auto_expanding_needed' => $needsAutoExpansion,
                'auto_expanding_trigger_gb' => (float) ($limits['auto_expanding_archive_trigger_gb'] ?? 90),
                'provisioning_days' => (int) ($limits['additional_storage_provisioning_days'] ?? 30),
            ],
            'recommendation' => $recommendation,
            'status' => $status,
            'actions' => array_values(array_unique($actions)),
            'warnings' => array_values(array_unique($warnings)),
            'estimated_addon_monthly' => $recommendedAddonMonthly,
            'sources' => is_array($catalog['meta']['sources'] ?? null) ? array_values(array_map('strval', $catalog['meta']['sources'])) : [],
            'messages' => $messages,
        ];
    }

    public static function render_archive_mailbox_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-archive-mailbox-calculator.php';
    }

    /**
     * @param array<string,mixed>|null $catalog
     * @return array<string,array<string,mixed>>
     */
    private static function plans_index(?array $catalog = null): array
    {
        $catalog ??= CMS_M365CALCULATOR_Catalog::archive_mailbox_plans();
        $plans = is_array($catalog['plans'] ?? null) ? $catalog['plans'] : [];
        $indexed = [];
        foreach ($plans as $plan) {
            if (!is_array($plan) || empty($plan['slug'])) {
                continue;
            }
            $indexed[(string) $plan['slug']] = $plan;
        }

        return $indexed;
    }

    /**
     * @param array<string,mixed> $catalog
     * @return array<string,array<string,mixed>>
     */
    private static function addons_index(array $catalog): array
    {
        $addons = is_array($catalog['addons'] ?? null) ? $catalog['addons'] : [];
        $indexed = [];
        foreach ($addons as $addon) {
            if (!is_array($addon) || empty($addon['slug'])) {
                continue;
            }
            $indexed[(string) $addon['slug']] = $addon;
        }

        return $indexed;
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $limits
     */
    private static function primary_capacity(array $input, array $plan, array $limits): float
    {
        $mailboxType = (string) ($input['mailbox_type'] ?? 'user');
        if ($mailboxType === 'shared') {
            return ((float) ($plan['primary_mailbox_gb'] ?? 50) >= 100)
                ? (float) ($limits['licensed_shared_primary_gb'] ?? 100)
                : (float) ($limits['unlicensed_shared_primary_gb'] ?? 50);
        }

        if ($mailboxType === 'resource') {
            return ((float) ($plan['primary_mailbox_gb'] ?? 50) >= 100)
                ? (float) ($limits['resource_licensed_primary_gb'] ?? 100)
                : (float) ($limits['resource_unlicensed_primary_gb'] ?? 50);
        }

        return (float) ($plan['primary_mailbox_gb'] ?? 50);
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $addon
     * @param array<string,mixed> $limits
     */
    private static function archive_capacity(array $input, array $plan, array $addon, array $limits): float
    {
        if ((string) ($input['archive_goal'] ?? '') === 'none') {
            return 0.0;
        }

        if (!empty($plan['auto_expanding_included'])) {
            return (float) ($plan['auto_expanding_gb'] ?? $limits['auto_expanding_archive_gb'] ?? 1500);
        }

        if ((string) ($input['archive_goal'] ?? '') === 'auto_expand' && is_numeric($addon['auto_expanding_gb'] ?? null)) {
            return (float) $addon['auto_expanding_gb'];
        }

        return (float) ($plan['archive_mailbox_gb'] ?? 50);
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $addon
     * @return array<string,string>
     */
    private static function build_recommendation(string $status, bool $needsAutoExpansion, bool $hasAutoExpansion, array $plan, array $addon): array
    {
        if ($status === 'danger') {
            return [
                'title' => 'Lizenz- oder Archivdesign anpassen',
                'text' => 'Die aktuelle Planung überschreitet mindestens eine gepflegte Kapazitätsgrenze. Upgrade, Archiving Add-on oder Datenlebenszyklus prüfen.',
            ];
        }

        if ($needsAutoExpansion && !$hasAutoExpansion) {
            return [
                'title' => 'Exchange Online Archiving einplanen',
                'text' => 'Der gewählte Plan benötigt für das gewünschte Archivziel das Add-on ' . (string) ($addon['name'] ?? 'Exchange Online Archiving') . '.',
            ];
        }

        if ($status === 'warning') {
            return [
                'title' => 'Planung mit Hinweisen möglich',
                'text' => 'Der Plan ist grundsätzlich nutzbar, aber Kapazitäts-, Hold- oder Governance-Hinweise sollten vor Aktivierung geprüft werden.',
            ];
        }

        return [
            'title' => 'Plan passt zur Archivplanung',
            'text' => 'Die gepflegten Kapazitätswerte des Plans ' . (string) ($plan['name'] ?? 'Basisplan') . ' reichen im 12-Monats-Horizont aus.',
        ];
    }

    private static function max_status(string $current, string $candidate): string
    {
        $weight = ['ok' => 0, 'warning' => 1, 'danger' => 2];
        return ($weight[$candidate] ?? 0) > ($weight[$current] ?? 0) ? $candidate : $current;
    }

    private static function bounded_float(mixed $value, float $min, float $max): float
    {
        $number = is_numeric($value) ? (float) $value : $min;
        return max($min, min($max, $number));
    }

    private static function enum(string $value, array $allowed, string $fallback): string
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function truthy(mixed $value): bool
    {
        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }
}
