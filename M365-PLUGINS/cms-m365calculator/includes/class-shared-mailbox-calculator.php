<?php
/**
 * CMS M365 Calculator – Shared-Mailbox-Entscheidungslogik.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Shared_Mailbox_Calculator
{
    private const MAX_UNLICENSED_STORAGE_GB = 50.0;
    private const PRACTICAL_USER_LIMIT = 25;

    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        return [
            'purpose' => 'info',
            'needs_direct_login' => false,
            'internal_users' => 5,
            'current_size_gb' => 12.0,
            'expected_size_gb' => 24.0,
            'external_direct_access' => false,
            'collaboration_scope' => 'internal_mail_calendar',
            'send_as_required' => true,
            'shared_calendar_required' => true,
            'deletion_protection_required' => false,
            'mobile_usage_required' => false,
            'automapping_required' => true,
            'hidden_from_gal' => false,
            'archive_required' => false,
            'litigation_hold_required' => false,
            'retention_required' => false,
            'defender_required' => false,
            'encryption_required' => false,
            'environment' => 'cloud',
            'convert_existing_user_mailbox' => false,
            'current_license_type' => 'm365-business-standard',
            'affected_mailboxes' => 1,
            'user_mailbox_monthly_price' => 10.80,
            'shared_license_monthly_price' => 6.90,
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $defaults = self::default_input();
        $validPurposes = array_keys(CMS_M365CALCULATOR_Catalog::scenarios());
        if ($validPurposes === []) {
            $validPurposes = ['info', 'support', 'sales', 'reception', 'former_employee', 'team_assistance', 'project_mailbox', 'personal_work_mailbox', 'other'];
        }

        $validScope = ['internal_mail_calendar', 'external_collaboration', 'workspace_required'];
        $validEnvironment = ['cloud', 'hybrid'];

        return [
            'purpose' => in_array((string) ($source['purpose'] ?? ''), $validPurposes, true) ? (string) $source['purpose'] : (string) $defaults['purpose'],
            'needs_direct_login' => self::bool_value($source['needs_direct_login'] ?? false),
            'internal_users' => max(0, min(10000, (int) ($source['internal_users'] ?? $defaults['internal_users']))),
            'current_size_gb' => self::float_value($source['current_size_gb'] ?? $defaults['current_size_gb'], 0.0, 100000.0),
            'expected_size_gb' => self::float_value($source['expected_size_gb'] ?? $defaults['expected_size_gb'], 0.0, 100000.0),
            'external_direct_access' => self::bool_value($source['external_direct_access'] ?? false),
            'collaboration_scope' => in_array((string) ($source['collaboration_scope'] ?? ''), $validScope, true) ? (string) $source['collaboration_scope'] : (string) $defaults['collaboration_scope'],
            'send_as_required' => self::bool_value($source['send_as_required'] ?? false),
            'shared_calendar_required' => self::bool_value($source['shared_calendar_required'] ?? false),
            'deletion_protection_required' => self::bool_value($source['deletion_protection_required'] ?? false),
            'mobile_usage_required' => self::bool_value($source['mobile_usage_required'] ?? false),
            'automapping_required' => self::bool_value($source['automapping_required'] ?? false),
            'hidden_from_gal' => self::bool_value($source['hidden_from_gal'] ?? false),
            'archive_required' => self::bool_value($source['archive_required'] ?? false),
            'litigation_hold_required' => self::bool_value($source['litigation_hold_required'] ?? false),
            'retention_required' => self::bool_value($source['retention_required'] ?? false),
            'defender_required' => self::bool_value($source['defender_required'] ?? false),
            'encryption_required' => self::bool_value($source['encryption_required'] ?? false),
            'environment' => in_array((string) ($source['environment'] ?? ''), $validEnvironment, true) ? (string) $source['environment'] : (string) $defaults['environment'],
            'convert_existing_user_mailbox' => self::bool_value($source['convert_existing_user_mailbox'] ?? false),
            'current_license_type' => mb_substr(trim(strip_tags((string) ($source['current_license_type'] ?? $defaults['current_license_type']))), 0, 80),
            'affected_mailboxes' => max(1, min(10000, (int) ($source['affected_mailboxes'] ?? $defaults['affected_mailboxes']))),
            'user_mailbox_monthly_price' => self::float_value($source['user_mailbox_monthly_price'] ?? $defaults['user_mailbox_monthly_price'], 0.0, 100000.0),
            'shared_license_monthly_price' => self::float_value($source['shared_license_monthly_price'] ?? $defaults['shared_license_monthly_price'], 0.0, 100000.0),
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $maxSize = max((float) $input['current_size_gb'], (float) $input['expected_size_gb']);
        $score = self::score($input, $maxSize);
        $rules = [];
        $warnings = [];
        $nextSteps = [];
        $recommendedProducts = [];

        $classicPurpose = in_array((string) $input['purpose'], ['info', 'support', 'sales', 'reception', 'former_employee', 'team_assistance', 'project_mailbox'], true);
        $broadCollaboration = in_array((string) $input['collaboration_scope'], ['external_collaboration', 'workspace_required'], true);
        $licenseRequired = $maxSize > self::MAX_UNLICENSED_STORAGE_GB
            || (bool) $input['archive_required']
            || (bool) $input['litigation_hold_required']
            || (bool) $input['retention_required']
            || (bool) $input['defender_required'];

        if ($classicPurpose) {
            $rules[] = 'Der Zweck entspricht einem klassischen Funktionspostfach-Szenario.';
        }
        if ((bool) $input['needs_direct_login']) {
            $rules[] = 'Direkter Login ist für Shared Mailboxes nicht vorgesehen.';
        }
        if ($maxSize > self::MAX_UNLICENSED_STORAGE_GB) {
            $rules[] = 'Die erwartete Größe überschreitet die 50-GB-Grenze für lizenzfreie Shared Mailboxes.';
            $recommendedProducts[] = 'Exchange Online Plan 2 für 100 GB Primärpostfach oder passende Suite-Lizenz';
        } elseif ($maxSize >= 45.0) {
            $warnings[] = 'Das Postfach liegt nah an der 50-GB-Grenze. Wachstum und Cleanup sollten aktiv überwacht werden.';
        }
        if ((bool) $input['archive_required']) {
            $rules[] = 'Archivpostfach bzw. Auto-Expanding Archive löst Lizenzbedarf aus.';
            $recommendedProducts[] = 'Exchange Online Plan 2 oder Exchange Online Plan 1 plus Exchange Online Archiving';
        }
        if ((bool) $input['litigation_hold_required']) {
            $rules[] = 'Litigation Hold / In-Place Hold benötigt eine passende Exchange-/Archiv-Lizenz.';
            $recommendedProducts[] = 'Exchange Online Plan 2 oder Exchange Online Plan 1 plus Exchange Online Archiving';
        }
        if ((bool) $input['retention_required']) {
            $rules[] = 'Purview-/Retention-Funktionen können eigene Feature- bzw. Suite-Lizenzen erfordern.';
            $recommendedProducts[] = 'Passende Microsoft Purview-/Compliance-Berechtigung für die Shared Mailbox';
        }
        if ((bool) $input['defender_required']) {
            $rules[] = 'Defender for Office 365 ist ein zusätzlicher Lizenztreiber für Schutzfunktionen.';
            $recommendedProducts[] = 'Defender for Office 365 Plan 1/2 oder enthaltende Suite';
        }
        if ((int) $input['internal_users'] > self::PRACTICAL_USER_LIMIT) {
            $warnings[] = 'Mehr als 25 aktive Nutzer ist ein Microsoft-Praxisgrenzwert: Verbindungsprobleme und doppelte Nachrichten werden wahrscheinlicher.';
        }
        if ((bool) $input['automapping_required'] && (int) $input['internal_users'] > 15) {
            $warnings[] = 'Automapping sollte bei vielen expliziten Vollzugriffen bewusst geplant werden; Gruppenberechtigungen mappen in Outlook nicht zuverlässig automatisch.';
        }
        if ((bool) $input['hidden_from_gal'] && (bool) $input['automapping_required']) {
            $warnings[] = 'Hidden GAL kann späteres Hinzufügen in Outlook erschweren und kollidiert mit einigen Send-As-/Outlook-Erwartungen.';
        }
        if ((string) $input['environment'] === 'hybrid') {
            $warnings[] = 'In Hybrid-Umgebungen sollte die Shared Mailbox im passenden Exchange-/Hybrid-Verwaltungspfad erstellt bzw. konvertiert werden.';
        }
        if ((bool) $input['convert_existing_user_mailbox']) {
            $warnings[] = 'Bei Konvertierung: Vorher muss die Benutzer-Mailbox lizenziert sein; danach darf das Benutzerkonto nicht gelöscht und Sign-in muss blockiert werden.';
        }

        $status = 'shared_unlicensed';
        if ((bool) $input['needs_direct_login'] || (string) $input['purpose'] === 'personal_work_mailbox' || (bool) $input['encryption_required']) {
            $status = 'regular_user';
            $rules[] = 'Dedizierte Identität, persönlicher Sicherheitskontext oder mailboxbezogene Verschlüsselung sprechen gegen Shared Mailbox.';
        } elseif ((bool) $input['external_direct_access'] || $broadCollaboration || ((bool) $input['deletion_protection_required'] && (int) $input['internal_users'] > 1)) {
            $status = 'm365_group';
            $rules[] = 'Externe Zusammenarbeit, Workspace-Funktionen oder Löschschutz passen häufig besser zu Microsoft 365 Groups als zu Shared Mailboxes.';
        } elseif ((int) $input['internal_users'] > self::PRACTICAL_USER_LIMIT || (bool) $input['deletion_protection_required']) {
            $status = 'borderline';
            $rules[] = 'Shared Mailbox ist technisch möglich, aber Governance und Betriebsmodell müssen geprüft werden.';
        } elseif ($licenseRequired) {
            $status = 'shared_licensed';
        }

        $costs = self::calculate_costs($input, $status, $licenseRequired);
        $nextSteps = self::build_next_steps($input, $status, $licenseRequired);

        return [
            'status' => $status,
            'status_label' => self::status_label($status),
            'status_tone' => self::status_tone($status),
            'score' => $score,
            'score_percent' => max(0, min(100, (int) round(($score + 12) / 24 * 100))),
            'license_required' => $licenseRequired,
            'license_state_label' => $licenseRequired ? 'lizenzpflichtig' : 'lizenzfrei möglich',
            'max_size_gb' => $maxSize,
            'rules' => array_values(array_unique($rules)),
            'warnings' => array_values(array_unique($warnings)),
            'recommended_products' => array_values(array_unique($recommendedProducts)),
            'costs' => $costs,
            'next_steps' => $nextSteps,
            'summary' => self::summary($status, $licenseRequired),
        ];
    }

    /**
     * @param array<string,mixed> $input
     */
    private static function score(array $input, float $maxSize): int
    {
        $score = 0;
        if (in_array((string) $input['purpose'], ['info', 'support', 'sales', 'reception', 'former_employee', 'team_assistance', 'project_mailbox'], true)) {
            $score += 3;
        }
        if ((int) $input['internal_users'] > 1) {
            $score += 2;
        }
        if ((bool) $input['shared_calendar_required']) {
            $score += 2;
        }
        if ((bool) $input['needs_direct_login']) {
            $score -= 4;
        }
        if ((bool) $input['external_direct_access']) {
            $score -= 4;
        }
        if ((int) $input['internal_users'] > self::PRACTICAL_USER_LIMIT) {
            $score -= 3;
        }
        if ((bool) $input['archive_required'] || (bool) $input['litigation_hold_required'] || (bool) $input['retention_required']) {
            $score -= 3;
        }
        if ((string) $input['collaboration_scope'] === 'workspace_required') {
            $score -= 2;
        }
        if ($maxSize > self::MAX_UNLICENSED_STORAGE_GB) {
            $score -= 2;
        }

        return $score;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private static function calculate_costs(array $input, string $status, bool $licenseRequired): array
    {
        $affected = (int) $input['affected_mailboxes'];
        $userPrice = (float) $input['user_mailbox_monthly_price'];
        $sharedLicensePrice = (float) $input['shared_license_monthly_price'];
        $referenceCost = round($affected * $userPrice, 2);
        $targetUnit = 0.0;

        if ($status === 'regular_user') {
            $targetUnit = $userPrice;
        } elseif ($status === 'shared_licensed' || ($status === 'borderline' && $licenseRequired)) {
            $targetUnit = $sharedLicensePrice;
        }

        if ($status === 'm365_group') {
            $targetUnit = $userPrice;
        }

        $targetCost = round($affected * $targetUnit, 2);

        return [
            'affected_mailboxes' => $affected,
            'reference_user_mailbox_monthly' => $referenceCost,
            'target_monthly' => $targetCost,
            'monthly_savings' => round(max(0.0, $referenceCost - $targetCost), 2),
            'annual_savings' => round(max(0.0, ($referenceCost - $targetCost) * 12), 2),
            'assumed_user_price' => $userPrice,
            'assumed_shared_license_price' => $sharedLicensePrice,
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<int,string>
     */
    private static function build_next_steps(array $input, string $status, bool $licenseRequired): array
    {
        $steps = [];

        if ($status === 'shared_unlicensed') {
            $steps[] = 'Shared Mailbox erstellen, Sign-in blockiert lassen und Zugriff nur an lizenzierte interne Benutzer vergeben.';
            $steps[] = 'Full Access und Send As bzw. Send on Behalf explizit dokumentieren.';
        } elseif ($status === 'shared_licensed' || ($status === 'borderline' && $licenseRequired)) {
            $steps[] = 'Passende Exchange-/Purview-/Defender-Lizenz vor produktiver Nutzung zuweisen.';
            $steps[] = 'Postfachgröße, Archiv, Hold und Retention im Tenant-Audit dokumentieren.';
        } elseif ($status === 'regular_user') {
            $steps[] = 'Reguläre Benutzer-Mailbox mit persönlichem Konto und passender Lizenz planen.';
            $steps[] = 'Wenn es eine ehemalige Mailbox ist: klären, ob Konvertierung oder Weiterbetrieb als Benutzeridentität gewünscht ist.';
        } elseif ($status === 'm365_group') {
            $steps[] = 'Microsoft 365 Group prüfen, wenn externe Zusammenarbeit, Dateien, Planner oder Workspace-Funktionen relevant sind.';
            $steps[] = 'Mitgliedschaft, Gästezugriff und Lösch-/Archivmodell getrennt vom Shared-Mailbox-Modell bewerten.';
        } else {
            $steps[] = 'Governance-Review durchführen: Benutzerzahl, Automapping, Löschrechte und Betriebsverantwortung klären.';
        }

        if ((bool) $input['convert_existing_user_mailbox']) {
            $steps[] = 'Vor der Konvertierung Lizenzstatus prüfen; nach der Konvertierung Sign-in blockieren und Konto als Anchor behalten.';
        }
        if ((string) $input['environment'] === 'hybrid') {
            $steps[] = 'Hybrid-Pfad mit Exchange Admin Center bzw. Exchange PowerShell abstimmen.';
        }

        return $steps;
    }

    private static function status_label(string $status): string
    {
        return match ($status) {
            'shared_licensed' => '🟡 Shared Mailbox geeignet, aber mit Zusatzlizenz / Add-on',
            'borderline' => '🟠 Grenzfall – Shared Mailbox technisch möglich, Governance prüfen',
            'regular_user' => '🔴 Reguläre Benutzer-Mailbox empfohlen',
            'm365_group' => '🔵 Microsoft 365 Group statt Shared Mailbox empfohlen',
            default => '✅ Shared Mailbox ohne Zusatzlizenz geeignet',
        };
    }

    private static function status_tone(string $status): string
    {
        return match ($status) {
            'shared_licensed' => 'warning',
            'borderline' => 'warning',
            'regular_user' => 'danger',
            'm365_group' => 'info',
            default => 'success',
        };
    }

    private static function summary(string $status, bool $licenseRequired): string
    {
        return match ($status) {
            'shared_licensed' => 'Der fachliche Use Case passt zur Shared Mailbox, aber mindestens ein Lizenztreiber ist aktiv.',
            'borderline' => 'Der Betrieb ist möglich, sollte aber wegen Nutzerzahl, Governance oder Zusatzanforderungen nicht ungeprüft ausgerollt werden.',
            'regular_user' => 'Der Bedarf verlangt eine eigene Benutzeridentität oder einen persönlichen Sicherheitskontext.',
            'm365_group' => 'Die Anforderungen gehen über gemeinsame Inbox und Kalender hinaus; ein Group-/Workspace-Modell ist meist tragfähiger.',
            default => $licenseRequired ? 'Shared Mailbox geeignet, aber Lizenzdetails prüfen.' : 'Der Bedarf passt zu einer lizenzfreien Shared Mailbox unterhalb der 50-GB-Grenze.',
        };
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
}
