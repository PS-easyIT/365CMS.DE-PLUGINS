<?php
/**
 * CMS M365 Tools – Admin module settings configuration.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_Admin_Module_Config
{
    /**
     * @return array<string,string>
     */
    public static function tabs_for(array $tool): array
    {
        return [
            'overview' => '📌 Übersicht',
            'display' => '🖥️ Anzeige',
            'pricing' => '💶 Preise & Annahmen',
            'workflow' => '🔁 Workflow',
            'data' => '🧾 Daten & Regeln',
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function fields_for(array $tool, string $tab): array
    {
        $key = (string) ($tool['key'] ?? '');
        $category = strtolower((string) ($tool['category'] ?? ''));

        return match ($tab) {
            'pricing' => self::pricing_fields($key, $category),
            'workflow' => self::workflow_fields($key, $category),
            'data' => self::data_fields($key, $category),
            default => [],
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function pricing_fields(string $key, string $category): array
    {
        $fields = [
            self::select('currency', 'Währung', 'EUR', ['EUR' => 'EUR', 'CHF' => 'CHF', 'USD' => 'USD', 'GBP' => 'GBP'], 'Währung für manuelle Preisannahmen.'),
            self::number('price_adjustment_percent', 'Globale Preisanpassung in %', '0', -50, 200, 0.1, 'Aufschlag oder Rabatt für dieses Modul gegenüber den Katalogwerten.'),
            self::number('monthly_admin_buffer_eur', 'Monatlicher Betriebspuffer', '0', 0, 100000, 0.01, 'Interner Zuschlag für Betrieb, Review oder Administration.'),
            self::number('annual_discount_percent', 'Jahresrabatt in %', '0', 0, 80, 0.1, 'Optionaler Rabatt bei Jahresbindung oder Rahmenvertrag.'),
            self::textarea('source_price_note', 'Interne Preisnotiz', '', 'Kurzer Hinweis zu Preisliste, Vertrag, CSP oder manueller Quelle.'),
        ];

        if (str_contains($key, 'copilot') || str_contains($category, 'copilot')) {
            $fields[] = self::number('copilot_license_monthly_eur', 'Copilot-Lizenz pro Nutzer/Monat', '28.10', 0, 1000, 0.01, 'Manuelle Copilot-Preisannahme für ROI, Pilot und Lizenzprüfung.');
            $fields[] = self::number('productivity_hourly_value_eur', 'Produktivitätswert pro Stunde', '65', 0, 1000, 0.01, 'Interner Stundensatz für ROI- und Business-Case-Berechnungen.');
        }

        if (str_contains($key, 'mailbox') || str_contains($key, 'exchange') || str_contains($category, 'exchange')) {
            $fields[] = self::number('exchange_plan_monthly_eur', 'Exchange-/Mailbox-Lizenz pro Monat', '3.70', 0, 1000, 0.01, 'Preisannahme für Exchange Online, Shared Mailboxes oder Archivpfade.');
            $fields[] = self::number('mailbox_storage_threshold_gb', 'Mailbox-Grenzwert in GB', '50', 1, 500, 1, 'Operativer Grenzwert für Lizenz- oder Archivprüfung.');
        }

        if (str_contains($key, 'teams-phone') || str_contains($category, 'teams')) {
            $fields[] = self::number('teams_phone_addon_monthly_eur', 'Teams Phone Add-on pro Nutzer', '7.50', 0, 1000, 0.01, 'Preisannahme für Teams Phone Standard.');
            $fields[] = self::number('calling_plan_monthly_eur', 'Calling Plan pro Nutzer', '11.20', 0, 1000, 0.01, 'Preisannahme für Calling Plan oder vergleichbare Telefonie-Bundles.');
        }

        if (str_contains($key, 'storage') || str_contains($category, 'speicher')) {
            $fields[] = self::number('storage_addon_per_gb_eur', 'Zusatzspeicher pro GB/Monat', '0.19', 0, 100, 0.001, 'Preisannahme für zusätzlichen Speicher.');
            $fields[] = self::number('growth_buffer_percent', 'Wachstumspuffer in %', '20', 0, 300, 0.1, 'Puffer für Forecasts und Kapazitätsplanung.');
        }

        if (str_contains($key, 'backup')) {
            $fields[] = self::number('backup_cost_per_gb_eur', 'Backup-Kosten pro GB/Monat', '0.15', 0, 100, 0.001, 'Interne oder Provider-Preisannahme für geschützte Daten.');
            $fields[] = self::number('restore_test_budget_eur', 'Restore-Testbudget pro Jahr', '1200', 0, 100000, 0.01, 'Budgetannahme für regelmäßige Wiederherstellungstests.');
        }

        if (str_contains($key, 'power-platform')) {
            $fields[] = self::number('premium_user_license_eur', 'Premium-Nutzerlizenz pro Monat', '18.70', 0, 1000, 0.01, 'Preisannahme für Premium-Power-Platform-Nutzer.');
            $fields[] = self::number('process_license_eur', 'Process-/Bot-Lizenz pro Monat', '140.40', 0, 5000, 0.01, 'Preisannahme für prozessbezogene Automatisierungen.');
            $fields[] = self::number('ai_credit_buffer_percent', 'AI-/Credit-Puffer in %', '15', 0, 300, 0.1, 'Puffer für Credits, Requests oder schwankende Nutzung.');
        }

        if (str_contains($key, 'workspace') || str_contains($category, 'migration')) {
            $fields[] = self::number('migration_day_rate_eur', 'Migrationstagessatz', '950', 0, 10000, 0.01, 'Interne oder externe Tagessatzannahme für Migrationen.');
            $fields[] = self::number('training_budget_per_user_eur', 'Schulungsbudget pro Nutzer', '45', 0, 10000, 0.01, 'Budgetannahme für Enablement und Change-Begleitung.');
        }

        if (str_contains($key, 'price') || str_contains($key, 'commitment')) {
            $fields[] = self::number('monthly_premium_percent', 'Monatslaufzeit-Aufschlag in %', '20', 0, 100, 0.1, 'Aufschlag für flexible Monatslaufzeiten.');
            $fields[] = self::number('renewal_buffer_percent', 'Renewal-Puffer in %', '10', 0, 100, 0.1, 'Budgetpuffer für Renewal- oder Packaging-Änderungen.');
        }

        return $fields;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function workflow_fields(string $key, string $category): array
    {
        $fields = [
            self::select('approval_mode', 'Freigabemodus', 'direct', [
                'direct' => 'Direkt veröffentlichen',
                'review' => 'Fachreview vor Nutzung',
                'locked' => 'Nur intern vorbereiten',
            ], 'Steuert den internen Veröffentlichungs- und Review-Prozess.'),
            self::select('owner_role', 'Fachlicher Owner', 'it_ops', [
                'admin' => 'CMS Admin',
                'license_manager' => 'Lizenzmanagement',
                'finance' => 'Finanzen/Einkauf',
                'it_ops' => 'IT Operations',
                'security' => 'Security/Compliance',
            ], 'Verantwortliche Rolle für Annahmen, Pflege und Freigaben.'),
            self::number('review_interval_days', 'Review-Intervall in Tagen', '90', 7, 730, 1, 'Regelmäßigkeit für fachliche Prüfung und Preisabgleich.'),
            self::checkbox('show_sources', 'Quellenbereich im Modul aktiv lassen', '1', 'Blendet gepflegte Quellen und Annahmen auf der öffentlichen Modulansicht ein.'),
            self::checkbox('enable_export', 'Export-/Druckpfad erlauben', '1', 'Erlaubt Zusammenfassungen, Druckansichten oder Export-Hinweise im Modul.'),
            self::textarea('workflow_note', 'Interne Workflow-Notiz', '', 'Hinweis für Admins zu Pflege, Freigabe oder Betrieb des Moduls.'),
        ];

        if (str_contains($key, 'audit')) {
            $fields[] = self::number('audit_review_cycle_days', 'Audit-Zyklus in Tagen', '180', 30, 1095, 1, 'Empfohlener Wiederholungszyklus für Lizenz- und Governance-Audits.');
            $fields[] = self::checkbox('require_evidence_notes', 'Nachweisnotizen im Auditprozess erwarten', '1', 'Markiert das Modul intern als prüfungsnahen Workflow.');
        }

        if (str_contains($key, 'power-platform')) {
            $fields[] = self::checkbox('require_governance_review', 'Governance-Review vor Ergebnisfreigabe', '1', 'Erzwingt intern einen Blick auf DLP, Umgebung und ALM.');
            $fields[] = self::select('environment_strategy', 'Umgebungsstrategie', 'managed', [
                'basic' => 'Basisumgebungen',
                'managed' => 'Managed Environments',
                'segmented' => 'Segmentierte Landing Zones',
            ], 'Standardannahme für Power-Platform-Workflows.');
        }

        if (str_contains($category, 'copilot') || str_contains($key, 'copilot')) {
            $fields[] = self::checkbox('require_readiness_review', 'Readiness-Review vor Rollout', '1', 'Prüft intern Datenzugriff, App-Bereitstellung und Adoption.');
        }

        return $fields;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private static function data_fields(string $key, string $category): array
    {
        $fields = [
            self::text('catalog_source_date', 'Quellenstand', date('Y-m-d'), 'Datum oder Label des letzten fachlichen Datenabgleichs.'),
            self::select('assumption_status', 'Annahmenstatus', 'managed_json', [
                'managed_json' => 'Katalogdaten aus JSON',
                'custom_review' => 'Manuelle Annahmen im Review',
                'locked' => 'Gesperrter geprüfter Stand',
            ], 'Kennzeichnet, wie belastbar die Datenbasis aktuell ist.'),
            self::select('official_source_profile', 'Offizielles Quellenprofil', 'microsoft_learn', [
                'microsoft_learn' => 'Microsoft Learn / Service Description',
                'microsoft_admin_center' => 'Admin Center / Usage Reports',
                'partner_contract' => 'Partner-, CSP- oder Vertragsdaten',
                'mixed' => 'Gemischte Quellenbasis',
            ], 'Ordnet die bevorzugte Datenquelle für fachliche Nachweise ein.'),
            self::checkbox('enable_custom_assumptions', 'Manuelle Annahmen für dieses Modul erlauben', '1', 'Erlaubt Admins, Preise und Annahmen modulbezogen zu übersteuern.'),
            self::checkbox('endpoint_readiness_reviewed', 'Endpoint-/Netzwerkpfad geprüft', '0', 'Markiert, dass Netzwerk-, Endpoint- oder Performance-Aspekte für dieses Modul fachlich bewertet wurden.'),
            self::checkbox('protection_reviewed', 'Schutz-/Datenzugriff geprüft', '0', 'Markiert, dass Zugriff, Mail-Schutz, Datenfreigaben oder Compliance-Aspekte für dieses Modul bewertet wurden.'),
            self::checkbox('capacity_limits_reviewed', 'Servicegrenzen/Kapazität geprüft', '0', 'Markiert, dass relevante Microsoft-365-Grenzen, Speicher- oder Request-Kapazitäten geprüft wurden.'),
            self::textarea('public_source_hint', 'Öffentlicher Quellenhinweis', '', 'Optionaler kurzer Quellen- oder Standhinweis für Redaktionspflege.'),
            self::textarea('internal_change_log', 'Interner Änderungsvermerk', '', 'Was wurde an Daten, Preisen, Regeln oder Workflow fachlich geändert?'),
        ];

        if (str_contains($key, 'copilot')) {
            $fields[] = self::checkbox('copilot_setup_reviewed', 'Copilot Setup-Readiness geprüft', '0', 'Markiert App-, OneDrive-, Teams-, Exchange-Online-, WSS- und Datenfreigabeprüfung für Copilot-Module.');
        }

        if (str_contains($key, 'power-platform')) {
            $fields[] = self::number('request_review_threshold_per_day', 'Request-Prüfschwelle pro Tag', '6000', 0, 1000000000, 1, 'Interne Schwelle, ab der tägliche Requests gesondert reviewed werden.');
            $fields[] = self::number('dataverse_capacity_warning_percent', 'Dataverse-Warnschwelle in %', '85', 0, 100, 0.1, 'Interne Warnschwelle für Database-, File- und Log-Kapazität.');
        }

        return $fields;
    }

    /**
     * @return array<string,mixed>
     */
    private static function text(string $key, string $label, string $default, string $help): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'text', 'default' => $default, 'help' => $help];
    }

    /**
     * @return array<string,mixed>
     */
    private static function textarea(string $key, string $label, string $default, string $help): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'textarea', 'default' => $default, 'help' => $help];
    }

    /**
     * @param array<string,string> $options
     * @return array<string,mixed>
     */
    private static function select(string $key, string $label, string $default, array $options, string $help): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'select', 'default' => $default, 'options' => $options, 'help' => $help];
    }

    /**
     * @return array<string,mixed>
     */
    private static function checkbox(string $key, string $label, string $default, string $help): array
    {
        return ['key' => $key, 'label' => $label, 'type' => 'checkbox', 'default' => $default, 'help' => $help];
    }

    /**
     * @return array<string,mixed>
     */
    private static function number(string $key, string $label, string $default, float $min, float $max, float $step, string $help): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => 'number',
            'default' => $default,
            'min' => $min,
            'max' => $max,
            'step' => $step,
            'help' => $help,
        ];
    }
}
