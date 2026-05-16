<?php
/**
 * CMS M365 Calculator – Copilot Lizenz-Pflicht-Checker.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Copilot_License_Checker
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        $upgradePaths = CMS_M365CALCULATOR_Catalog::copilot_upgrade_paths();

        return [
            'tenant_segment' => 'commercial',
            'base_plan' => 'm365-business-standard',
            'desired_variant' => 'full_copilot',
            'target_user_count' => 25,
            'tenant_user_count' => 25,
            'has_entra_account' => true,
            'has_primary_exchange_mailbox' => true,
            'm365_apps_deployed' => true,
            'onedrive_enabled' => true,
            'teams_ready' => true,
            'privacy_controls_reviewed' => true,
            'network_ready' => true,
            'copilot_addon_monthly' => (float) ($upgradePaths['default_copilot_addon_monthly'] ?? 28.10),
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $defaults = self::default_input();
        $matrix = CMS_M365CALCULATOR_Catalog::copilot_eligibility_matrix();
        $segments = is_array($matrix['segments'] ?? null) ? $matrix['segments'] : [];
        $segment = (string) ($source['tenant_segment'] ?? $defaults['tenant_segment']);
        if (!isset($segments[$segment]) || !is_array($segments[$segment])) {
            $segment = (string) $defaults['tenant_segment'];
        }

        $plans = is_array($segments[$segment]['plans'] ?? null) ? $segments[$segment]['plans'] : [];
        $basePlan = self::clean_key((string) ($source['base_plan'] ?? $defaults['base_plan']));
        if (!isset($plans[$basePlan]) || !is_array($plans[$basePlan])) {
            $basePlan = 'none-or-unknown';
        }

        $desiredVariant = (string) ($source['desired_variant'] ?? $defaults['desired_variant']);
        if (!in_array($desiredVariant, ['full_copilot', 'copilot_chat'], true)) {
            $desiredVariant = (string) $defaults['desired_variant'];
        }

        $tenantUsers = max(1, min(100000, (int) ($source['tenant_user_count'] ?? $defaults['tenant_user_count'])));
        $targetUsers = max(1, min($tenantUsers, (int) ($source['target_user_count'] ?? $defaults['target_user_count'])));

        return [
            'tenant_segment' => $segment,
            'base_plan' => $basePlan,
            'desired_variant' => $desiredVariant,
            'target_user_count' => $targetUsers,
            'tenant_user_count' => $tenantUsers,
            'has_entra_account' => self::bool_value($source['has_entra_account'] ?? false),
            'has_primary_exchange_mailbox' => self::bool_value($source['has_primary_exchange_mailbox'] ?? false),
            'm365_apps_deployed' => self::bool_value($source['m365_apps_deployed'] ?? false),
            'onedrive_enabled' => self::bool_value($source['onedrive_enabled'] ?? false),
            'teams_ready' => self::bool_value($source['teams_ready'] ?? false),
            'privacy_controls_reviewed' => self::bool_value($source['privacy_controls_reviewed'] ?? false),
            'network_ready' => self::bool_value($source['network_ready'] ?? false),
            'copilot_addon_monthly' => self::float_value($source['copilot_addon_monthly'] ?? $defaults['copilot_addon_monthly'], 0.0, 100000.0),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public static function load_copilot_prerequisites(): array
    {
        return CMS_M365CALCULATOR_Catalog::copilot_prerequisites();
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function check_copilot_license_eligibility(array $input): array
    {
        $input = self::normalize_input($input);
        $matrix = CMS_M365CALCULATOR_Catalog::copilot_eligibility_matrix();
        $segments = is_array($matrix['segments'] ?? null) ? $matrix['segments'] : [];
        $segment = (string) $input['tenant_segment'];
        $plans = is_array($segments[$segment]['plans'] ?? null) ? $segments[$segment]['plans'] : [];
        $plan = is_array($plans[(string) $input['base_plan']] ?? null) ? $plans[(string) $input['base_plan']] : [];

        return [
            'segment' => $segment,
            'segment_label' => (string) ($segments[$segment]['label'] ?? $segment),
            'plan_key' => (string) $input['base_plan'],
            'plan_name' => (string) ($plan['name'] ?? 'Unbekannte Lizenz'),
            'eligible_full' => !empty($plan['eligible_full']),
            'eligible_business' => !empty($plan['eligible_business']),
            'eligible_chat' => !empty($plan['eligible_chat']),
            'monthly_price' => (float) ($plan['monthly_price'] ?? 0.0),
            'notes' => is_array($plan['notes'] ?? null) ? array_values($plan['notes']) : [],
            'source_checked_at' => (string) ($matrix['source_checked_at'] ?? $matrix['version'] ?? ''),
            'sources' => is_array($matrix['sources'] ?? null) ? array_values($matrix['sources']) : [],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function check_copilot_technical_readiness(array $input): array
    {
        $input = self::normalize_input($input);
        $prerequisites = self::load_copilot_prerequisites();
        $required = is_array($prerequisites['required'] ?? null) ? $prerequisites['required'] : [];
        $recommended = is_array($prerequisites['recommended'] ?? null) ? $prerequisites['recommended'] : [];
        $fieldMap = [
            'entra_account' => 'has_entra_account',
            'primary_exchange_mailbox' => 'has_primary_exchange_mailbox',
            'm365_apps_deployed' => 'm365_apps_deployed',
            'onedrive_enabled' => 'onedrive_enabled',
            'teams_ready' => 'teams_ready',
            'privacy_controls_reviewed' => 'privacy_controls_reviewed',
            'network_ready' => 'network_ready',
        ];
        $missingRequired = [];
        $missingRecommended = [];

        foreach ($required as $key => $meta) {
            $field = $fieldMap[(string) $key] ?? '';
            if ($field !== '' && empty($input[$field]) && is_array($meta)) {
                $missingRequired[] = self::missing_item((string) $key, $meta, 'required');
            }
        }

        foreach ($recommended as $key => $meta) {
            $field = $fieldMap[(string) $key] ?? '';
            if ($field !== '' && empty($input[$field]) && is_array($meta)) {
                $missingRecommended[] = self::missing_item((string) $key, $meta, 'recommended');
            }
        }

        return [
            'ready_required' => $missingRequired === [],
            'ready_recommended' => $missingRecommended === [],
            'missing_required' => $missingRequired,
            'missing_recommended' => $missingRecommended,
            'mailbox_exclusions' => is_array($prerequisites['mailbox_exclusions'] ?? null) ? array_values($prerequisites['mailbox_exclusions']) : [],
            'source_checked_at' => (string) ($prerequisites['source_checked_at'] ?? $prerequisites['version'] ?? ''),
            'sources' => is_array($prerequisites['sources'] ?? null) ? array_values($prerequisites['sources']) : [],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function build_copilot_upgrade_path(array $input): array
    {
        $input = self::normalize_input($input);
        $upgradePaths = CMS_M365CALCULATOR_Catalog::copilot_upgrade_paths();
        $paths = is_array($upgradePaths['paths'] ?? null) ? $upgradePaths['paths'] : [];
        $segmentPaths = is_array($paths[(string) $input['tenant_segment']] ?? null) ? $paths[(string) $input['tenant_segment']] : [];
        $path = is_array($segmentPaths[(string) $input['base_plan']] ?? null) ? $segmentPaths[(string) $input['base_plan']] : [];

        if ($path === [] && (string) $input['base_plan'] !== 'none-or-unknown') {
            $path = is_array($segmentPaths['none-or-unknown'] ?? null) ? $segmentPaths['none-or-unknown'] : [];
        }

        return [
            'target_plan' => (string) ($path['target_plan'] ?? ''),
            'target_label' => (string) ($path['target_label'] ?? ''),
            'target_monthly' => (float) ($path['target_monthly'] ?? 0.0),
            'reason' => (string) ($path['reason'] ?? 'Aktuelle Microsoft-Liste und Vertragsmodell prüfen.'),
            'currency' => (string) ($upgradePaths['currency'] ?? 'EUR'),
            'note' => (string) ($upgradePaths['note'] ?? 'Preise vor Bestellung prüfen.'),
            'default_copilot_addon_monthly' => (float) ($upgradePaths['default_copilot_addon_monthly'] ?? 28.10),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $license = self::check_copilot_license_eligibility($input);
        $technical = self::check_copilot_technical_readiness($input);
        $upgrade = self::build_copilot_upgrade_path($input);
        $isMixedTenant = (int) $input['target_user_count'] < (int) $input['tenant_user_count'];
        $desiredChatOnly = (string) $input['desired_variant'] === 'copilot_chat';
        $status = self::classify($input, $license, $technical, $isMixedTenant);

        if ($desiredChatOnly && !empty($license['eligible_chat']) && !empty($input['has_entra_account'])) {
            $status = 'chat_available';
        } elseif ($desiredChatOnly && empty($license['eligible_chat'])) {
            $status = 'special_review';
        }

        $costs = self::calculate_costs($input, $license, $upgrade, $desiredChatOnly);
        $nextSteps = self::build_next_steps($input, $license, $technical, $upgrade, $status, $isMixedTenant);

        return [
            'status' => $status,
            'status_label' => self::status_label($status),
            'status_tone' => self::status_tone($status),
            'summary' => self::summary($status, $license, $technical, $isMixedTenant),
            'input' => $input,
            'license' => $license,
            'technical' => $technical,
            'upgrade' => $upgrade,
            'costs' => $costs,
            'next_steps' => $nextSteps,
            'chat_notice' => self::chat_notice($license, $desiredChatOnly),
            'is_mixed_tenant' => $isMixedTenant,
            'sources' => array_values(array_unique(array_merge($license['sources'] ?? [], $technical['sources'] ?? []))),
            'source_checked_at' => (string) ($license['source_checked_at'] ?: ($technical['source_checked_at'] ?? '')),
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function plan_options(): array
    {
        $matrix = CMS_M365CALCULATOR_Catalog::copilot_eligibility_matrix();
        $segments = is_array($matrix['segments'] ?? null) ? $matrix['segments'] : [];
        $options = [];

        foreach ($segments as $segmentKey => $segment) {
            if (!is_array($segment)) {
                continue;
            }

            $plans = is_array($segment['plans'] ?? null) ? $segment['plans'] : [];
            $options[(string) $segmentKey] = [
                'label' => (string) ($segment['label'] ?? $segmentKey),
                'plans' => $plans,
            ];
        }

        return $options;
    }

    /**
     * @param array<string,mixed> $meta
     * @return array<string,string>
     */
    private static function missing_item(string $key, array $meta, string $severity): array
    {
        return [
            'key' => $key,
            'severity' => $severity,
            'label' => (string) ($meta['label'] ?? $key),
            'message' => (string) ($meta['missing'] ?? ''),
            'next_step' => (string) ($meta['next_step'] ?? ''),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $license
     * @param array<string,mixed> $technical
     */
    private static function classify(array $input, array $license, array $technical, bool $isMixedTenant): string
    {
        if (empty($license['eligible_full'])) {
            return in_array((string) $input['tenant_segment'], ['government', 'education'], true) ? 'special_review' : 'upgrade_required';
        }

        if (empty($technical['ready_required']) || empty($technical['ready_recommended'])) {
            return 'technical_gap';
        }

        if ($isMixedTenant) {
            return 'mixed_tenant';
        }

        return 'eligible';
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $license
     * @param array<string,mixed> $upgrade
     * @return array<string,mixed>
     */
    private static function calculate_costs(array $input, array $license, array $upgrade, bool $desiredChatOnly): array
    {
        $users = (int) $input['target_user_count'];
        $currentMonthly = (float) ($license['monthly_price'] ?? 0.0);
        $targetMonthly = (float) ($upgrade['target_monthly'] ?? 0.0);
        $upgradeDelta = empty($license['eligible_full']) ? max(0.0, $targetMonthly - $currentMonthly) : 0.0;
        $addonMonthly = $desiredChatOnly ? 0.0 : (float) $input['copilot_addon_monthly'];
        $baseUpgradeMonthly = round($users * $upgradeDelta, 2);
        $copilotAddonMonthly = round($users * $addonMonthly, 2);

        return [
            'target_users' => $users,
            'current_base_monthly' => round($users * $currentMonthly, 2),
            'base_upgrade_delta_per_user' => round($upgradeDelta, 2),
            'base_upgrade_monthly' => $baseUpgradeMonthly,
            'copilot_addon_per_user' => round($addonMonthly, 2),
            'copilot_addon_monthly' => $copilotAddonMonthly,
            'total_monthly_delta' => round($baseUpgradeMonthly + $copilotAddonMonthly, 2),
            'total_annual_delta' => round(($baseUpgradeMonthly + $copilotAddonMonthly) * 12, 2),
            'currency' => (string) ($upgrade['currency'] ?? 'EUR'),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,mixed> $license
     * @param array<string,mixed> $technical
     * @param array<string,mixed> $upgrade
     * @return array<int,string>
     */
    private static function build_next_steps(array $input, array $license, array $technical, array $upgrade, string $status, bool $isMixedTenant): array
    {
        $steps = [];

        if (empty($license['eligible_full']) && (string) ($upgrade['target_label'] ?? '') !== '') {
            $steps[] = 'Basislizenz auf ' . (string) $upgrade['target_label'] . ' prüfen: ' . (string) ($upgrade['reason'] ?? '');
        } elseif (!empty($license['eligible_full'])) {
            $steps[] = 'Microsoft 365 Copilot Add-on für die Zielgruppe vorbereiten und Lizenzzuweisung testen.';
        }

        foreach (($technical['missing_required'] ?? []) as $missing) {
            if (is_array($missing) && (string) ($missing['next_step'] ?? '') !== '') {
                $steps[] = (string) $missing['next_step'];
            }
        }

        foreach (($technical['missing_recommended'] ?? []) as $missing) {
            if (is_array($missing) && (string) ($missing['next_step'] ?? '') !== '') {
                $steps[] = (string) $missing['next_step'];
            }
        }

        if ($isMixedTenant) {
            $steps[] = 'Zielgruppen nach Lizenzbasis trennen und nur geeignete Gruppen für die erste Copilot-Zuweisung auswählen.';
        }

        if ((string) $input['tenant_segment'] === 'government') {
            $steps[] = 'Government-Cloud-Verfügbarkeit und Web-Grounding-/Feature-Einschränkungen separat prüfen.';
        }

        if ((string) $input['tenant_segment'] === 'education') {
            $steps[] = 'Education-Rollen, Bezugsberechtigung und Altersvorgaben für Copilot Chat bzw. Copilot-Lizenzen prüfen.';
        }

        if (!empty($license['eligible_chat']) && empty($license['eligible_full'])) {
            $steps[] = 'Copilot Chat kann ein Zwischenzustand sein; für Work-based Chat und tiefe App-Erlebnisse bleibt das Copilot Add-on relevant.';
        }

        if ($status === 'chat_available') {
            $steps[] = 'Copilot Chat als Web-grounded Einstieg bereitstellen und Admin-/App-Zugriff steuern.';
        }

        return array_values(array_unique(array_filter($steps)));
    }

    private static function status_label(string $status): string
    {
        return match ($status) {
            'technical_gap' => 'Geeignet, aber technische Voraussetzungen fehlen',
            'upgrade_required' => 'Upgrade der Basislizenz nötig',
            'mixed_tenant' => 'Gemischter Tenant – nur Teilgruppen geeignet',
            'special_review' => 'Nicht geeignet / Spezialpfad prüfen',
            'chat_available' => 'Copilot Chat verfügbar, volle Copilot-Lizenz separat prüfen',
            default => 'Lizenzseitig geeignet',
        };
    }

    private static function status_tone(string $status): string
    {
        return match ($status) {
            'eligible' => 'success',
            'technical_gap' => 'warning',
            'upgrade_required' => 'warning',
            'mixed_tenant' => 'info',
            'chat_available' => 'info',
            default => 'danger',
        };
    }

    /**
     * @param array<string,mixed> $license
     * @param array<string,mixed> $technical
     */
    private static function summary(string $status, array $license, array $technical, bool $isMixedTenant): string
    {
        return match ($status) {
            'technical_gap' => 'Der Basisplan ist geeignet, aber vor der produktiven Zuweisung fehlen technische Voraussetzungen oder wichtige App-/Netzwerk-Readiness-Punkte.',
            'upgrade_required' => 'Der ausgewählte Basisplan reicht für die volle Microsoft-365-Copilot-Zuweisung nicht aus. Ein Upgrade-Pfad sollte vor der Add-on-Beschaffung geklärt werden.',
            'mixed_tenant' => 'Die geprüfte Zielgruppe ist geeignet, aber nicht der gesamte Tenant. Die Rollout-Gruppe sollte getrennt von nicht geeigneten Gruppen geführt werden.',
            'special_review' => 'Dieses Segment oder diese Lizenz erfordert eine manuelle Prüfung gegen die aktuelle Microsoft-Liste und den Vertrags-/Cloud-Kontext.',
            'chat_available' => 'Für diese Lizenzbasis ist Copilot Chat realistisch. Work-based Chat und tiefe In-App-Erlebnisse benötigen weiterhin die volle Microsoft-365-Copilot-Lizenz.',
            default => 'Die gewählte Basislizenz ist geeignet und die angegebenen technischen Voraussetzungen sind erfüllt.',
        };
    }

    /**
     * @param array<string,mixed> $license
     */
    private static function chat_notice(array $license, bool $desiredChatOnly): string
    {
        if (!empty($license['eligible_chat']) && !$desiredChatOnly) {
            return 'Copilot Chat kann mit dieser Microsoft-365-Subscription bereits verfügbar sein. Die volle Microsoft-365-Copilot-Lizenz bleibt für Work-based Chat und tiefe In-App-Erlebnisse relevant.';
        }

        if ($desiredChatOnly && !empty($license['eligible_chat'])) {
            return 'Für Copilot Chat ist keine zusätzliche Microsoft-365-Copilot-Lizenz erforderlich, sofern Entra-Konto, Admin-Freigabe und Tenant-Konfiguration passen.';
        }

        return 'Copilot Chat und volle Copilot-Lizenz sollten getrennt bewertet werden; die Verfügbarkeit hängt von Lizenzbasis und Tenant-Konfiguration ab.';
    }

    private static function bool_value(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array((string) $value, ['1', 'true', 'yes', 'ja', 'on'], true);
    }

    private static function float_value(mixed $value, float $min, float $max): float
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return $min;
        }

        return max($min, min($max, round((float) $normalized, 2)));
    }

    private static function clean_key(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }
}
