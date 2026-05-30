<?php
/**
 * CMS M365 Tools – Lizenz-Audit-Checkliste.
 *
 * @package CMS_M365CALCULATOR
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365CALCULATOR_License_Audit_Checklist
{
    /**
     * @return array<string,mixed>
     */
    public static function default_input(): array
    {
        return [
            'tenant_size' => 'mid',
            'audit_depth' => 'basis',
            'uses_group_licensing' => false,
            'has_departed_users' => true,
            'uses_shared_mailboxes' => true,
            'copilot_interest' => false,
            'frontline_candidates' => false,
            'storage_pressure' => false,
            'backup_review' => false,
            'renewal_review' => true,
        ];
    }

    /**
     * @param array<string,mixed> $source
     * @return array<string,mixed>
     */
    public static function normalize_input(array $source): array
    {
        $defaults = self::default_input();
        $input = [
            'tenant_size' => self::choice((string) ($source['tenant_size'] ?? $defaults['tenant_size']), array_keys(self::tenant_size_options()), (string) $defaults['tenant_size']),
            'audit_depth' => self::choice((string) ($source['audit_depth'] ?? $defaults['audit_depth']), array_keys(self::audit_depth_options()), (string) $defaults['audit_depth']),
        ];

        foreach (self::context_options() as $key => $option) {
            $input[$key] = array_key_exists($key, $source)
                ? self::bool_value($source[$key])
                : (bool) ($defaults[$key] ?? false);
        }

        return $input;
    }

    /**
     * @return array<string,string>
     */
    public static function tenant_size_options(): array
    {
        return [
            'small' => 'Bis 100 Nutzer',
            'mid' => '101 bis 1.000 Nutzer',
            'enterprise' => 'Mehr als 1.000 Nutzer',
        ];
    }

    /**
     * @return array<string,string>
     */
    public static function audit_depth_options(): array
    {
        return [
            'basis' => 'Basis-Audit',
            'erweitert' => 'Erweitertes Audit',
            'tiefgehend' => 'Tiefgehendes Audit',
        ];
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public static function context_options(): array
    {
        return [
            'uses_group_licensing' => [
                'label' => 'Gruppenlizenzierung im Einsatz',
                'description' => 'Priorisiert Gruppen, Fehlerlisten, Standorte und große Änderungsfenster.',
                'triggers' => ['license_advisor', 'addon_configurator'],
            ],
            'has_departed_users' => [
                'label' => 'Ehemalige Nutzer im Prüfzeitraum',
                'description' => 'Priorisiert Offboarding, Mailbox, OneDrive und Datenübergabe.',
                'triggers' => ['shared_mailbox', 'archive_mailbox', 'backup_review'],
            ],
            'uses_shared_mailboxes' => [
                'label' => 'Shared Mailboxes vorhanden',
                'description' => 'Priorisiert 50-GB-Grenze, Archiv, Hold und Ankerkonto.',
                'triggers' => ['shared_mailbox', 'archive_mailbox'],
            ],
            'copilot_interest' => [
                'label' => 'Copilot geplant oder aktiv',
                'description' => 'Priorisiert Basislizenz, Postfach, Apps, Netzwerk und Pilotplanung.',
                'triggers' => ['copilot_license', 'copilot_pilot'],
            ],
            'frontline_candidates' => [
                'label' => 'Frontline- oder Schichtrollen vorhanden',
                'description' => 'Priorisiert F1/F3-Eignung, Geräte- und App-Bedarf.',
                'triggers' => ['frontline_check'],
            ],
            'storage_pressure' => [
                'label' => 'SharePoint-/OneDrive-Speicher knapp',
                'description' => 'Priorisiert Speicherpool, Site-Limits und Zusatzspeicher.',
                'triggers' => ['addon_configurator', 'backup_review'],
            ],
            'backup_review' => [
                'label' => 'Backup- und Wiederherstellung prüfen',
                'description' => 'Priorisiert Backup-Scope, Datenklassen und Verbrauchsschätzung.',
                'triggers' => ['backup_review', 'addon_configurator'],
            ],
            'renewal_review' => [
                'label' => 'Renewal oder Preisrunde steht an',
                'description' => 'Priorisiert Laufzeitmodell, Preisänderungen und Beschaffungskanal.',
                'triggers' => ['price_tracker', 'commitment_calculator'],
            ],
        ];
    }

    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public static function evaluate(array $input): array
    {
        $input = self::normalize_input($input);
        $checklist = self::load_license_audit_checklist();
        $deeplinks = CMS_M365CALCULATOR_Catalog::license_audit_deeplinks();
        $pdfTemplate = CMS_M365CALCULATOR_Catalog::audit_pdf_template();
        $categories = self::normalize_categories(is_array($checklist['categories'] ?? null) ? $checklist['categories'] : []);
        $summary = self::build_license_audit_summary($input, $categories, $deeplinks);
        $score = self::score_license_audit_findings($summary);
        $deepLinks = self::build_deep_links($summary, $deeplinks);

        return [
            'input' => $input,
            'categories' => $categories,
            'summary' => $summary,
            'score' => $score,
            'deep_links' => $deepLinks,
            'tenant_size_options' => self::tenant_size_options(),
            'audit_depth_options' => self::audit_depth_options(),
            'context_options' => self::context_options(),
            'pdf_template' => self::export_license_audit_pdf($pdfTemplate),
            'sources' => self::sources($checklist),
            'meta' => [
                'source_checked' => (string) ($checklist['meta']['source_checked'] ?? '2026-05-17'),
                'title' => (string) ($checklist['meta']['title'] ?? 'Lizenz-Audit-Checkliste'),
                'intro' => (string) ($checklist['meta']['intro'] ?? 'Microsoft-365-Auditliste für Lizenzbestand und Governance.'),
                'storage_key' => self::save_license_audit_progress_local($input),
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public static function load_license_audit_checklist(): array
    {
        return CMS_M365CALCULATOR_Catalog::license_audit_checklist();
    }

    /**
     * @param array<string,mixed> $input
     */
    public static function save_license_audit_progress_local(array $input): string
    {
        $scope = [
            'tenant_size' => (string) ($input['tenant_size'] ?? 'mid'),
            'audit_depth' => (string) ($input['audit_depth'] ?? 'basis'),
        ];

        foreach (self::context_options() as $key => $option) {
            if (!empty($input[$key])) {
                $scope[$key] = '1';
            }
        }

        return 'm365calc-license-audit-' . substr(hash('sha256', json_encode($scope) ?: 'license-audit'), 0, 12);
    }

    /**
     * @param array<string,mixed> $input
     * @param array<string,array<string,mixed>> $categories
     * @param array<string,mixed> $deeplinks
     * @return array<string,mixed>
     */
    public static function build_license_audit_summary(array $input, array $categories, array $deeplinks): array
    {
        $selectedTriggers = self::selected_triggers($input);
        $depth = (string) ($input['audit_depth'] ?? 'basis');
        $items = [];
        $priorityItems = [];
        $categorySummary = [];
        $triggerCounts = [];
        $totalWeight = 0;
        $priorityWeight = 0;

        foreach ($categories as $categoryKey => $category) {
            $categoryItems = is_array($category['items'] ?? null) ? $category['items'] : [];
            $categoryPriority = 0;
            $categoryWeight = 0;

            foreach ($categoryItems as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $weight = max(0, (int) ($item['weight'] ?? 0));
                $itemTriggers = array_values(array_filter(array_map('strval', (array) ($item['deeplink_triggers'] ?? []))));
                $isPriority = self::is_priority_item($item, $selectedTriggers, $depth);
                $normalizedItem = $item;
                $normalizedItem['category'] = (string) $categoryKey;
                $normalizedItem['category_label'] = (string) ($category['label'] ?? $categoryKey);
                $normalizedItem['is_priority'] = $isPriority;

                $items[] = $normalizedItem;
                $totalWeight += $weight;
                $categoryWeight += $weight;

                foreach ($itemTriggers as $trigger) {
                    $triggerCounts[$trigger] = ($triggerCounts[$trigger] ?? 0) + ($isPriority ? 2 : 1);
                }

                if ($isPriority) {
                    $priorityItems[] = $normalizedItem;
                    $priorityWeight += $weight;
                    $categoryPriority++;
                }
            }

            $categorySummary[(string) $categoryKey] = [
                'label' => (string) ($category['label'] ?? $categoryKey),
                'description' => (string) ($category['description'] ?? ''),
                'item_count' => count($categoryItems),
                'priority_count' => $categoryPriority,
                'weight' => $categoryWeight,
            ];
        }

        usort($priorityItems, static function (array $left, array $right): int {
            $weight = ((int) ($right['weight'] ?? 0)) <=> ((int) ($left['weight'] ?? 0));
            if ($weight !== 0) {
                return $weight;
            }

            return strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        });

        arsort($triggerCounts);

        return [
            'total_items' => count($items),
            'priority_items' => $priorityItems,
            'priority_count' => count($priorityItems),
            'total_weight' => $totalWeight,
            'priority_weight' => $priorityWeight,
            'categories' => $categorySummary,
            'selected_triggers' => $selectedTriggers,
            'trigger_counts' => $triggerCounts,
            'focus_label' => self::focus_label($input, $priorityItems),
            'depth_label' => self::audit_depth_options()[(string) $input['audit_depth']] ?? 'Audit',
            'tenant_label' => self::tenant_size_options()[(string) $input['tenant_size']] ?? 'Mandant',
        ];
    }

    /**
     * @param array<string,mixed> $summary
     * @return array<string,mixed>
     */
    public static function score_license_audit_findings(array $summary): array
    {
        $totalWeight = max(1, (int) ($summary['total_weight'] ?? 1));
        $priorityWeight = max(0, (int) ($summary['priority_weight'] ?? 0));
        $priorityCount = max(0, (int) ($summary['priority_count'] ?? 0));
        $pressure = max(10, min(100, (int) round(($priorityWeight / $totalWeight) * 100)));

        if ($pressure >= 70 || $priorityCount >= 12) {
            $tone = 'danger';
            $label = 'Hohe Audit-Priorität';
            $reason = 'Mehrere gewichtete Prüfpunkte sollten zuerst geklärt werden, bevor Lizenzen, Add-ons oder Laufzeiten angepasst werden.';
        } elseif ($pressure >= 45 || $priorityCount >= 7) {
            $tone = 'warning';
            $label = 'Strukturierte Prüfung sinnvoll';
            $reason = 'Die gewählten Rahmenbedingungen erzeugen mehrere relevante Prüfpfade für Lizenzbestand, Daten und Beschaffung.';
        } else {
            $tone = 'success';
            $label = 'Fokussiertes Audit möglich';
            $reason = 'Die Checkliste kann mit einem kompakten Prüfpfad gestartet und später erweitert werden.';
        }

        return [
            'pressure' => $pressure,
            'tone' => $tone,
            'label' => $label,
            'reason' => $reason,
        ];
    }

    public static function render_license_audit_page(): string
    {
        return CMS_M365CALCULATOR_PLUGIN_DIR . 'templates/page-license-audit-checklist.php';
    }

    /**
     * @param array<string,mixed> $template
     * @return array<string,mixed>
     */
    public static function export_license_audit_pdf(array $template): array
    {
        return [
            'sections' => is_array($template['sections'] ?? null) ? $template['sections'] : [],
            'labels' => is_array($template['labels'] ?? null) ? $template['labels'] : [],
            'meta' => is_array($template['meta'] ?? null) ? $template['meta'] : [],
        ];
    }

    /**
     * @param array<string,mixed> $summary
     * @param array<string,mixed> $deeplinks
     * @return array<int,array<string,mixed>>
     */
    private static function build_deep_links(array $summary, array $deeplinks): array
    {
        $links = is_array($deeplinks['links'] ?? null) ? $deeplinks['links'] : [];
        $order = array_values(array_filter(array_map('strval', (array) ($deeplinks['default_order'] ?? []))));
        $triggerCounts = is_array($summary['trigger_counts'] ?? null) ? $summary['trigger_counts'] : [];
        $keys = array_values(array_unique(array_merge(array_keys($triggerCounts), $order)));
        $result = [];

        foreach ($keys as $key) {
            if (!is_array($links[$key] ?? null)) {
                continue;
            }

            $link = $links[$key];
            $url = self::safe_public_url((string) ($link['url'] ?? ''));
            if ($url === '') {
                continue;
            }

            $result[] = [
                'key' => (string) $key,
                'label' => (string) ($link['label'] ?? $key),
                'description' => (string) ($link['description'] ?? ''),
                'url' => $url,
                'category' => (string) ($link['category'] ?? 'Tool'),
                'weight' => (int) ($triggerCounts[$key] ?? 0),
            ];
        }

        usort($result, static function (array $left, array $right): int {
            $weight = ((int) ($right['weight'] ?? 0)) <=> ((int) ($left['weight'] ?? 0));
            if ($weight !== 0) {
                return $weight;
            }

            return strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
        });

        return array_slice($result, 0, 8);
    }

    /**
     * @param array<string,mixed> $categories
     * @return array<string,array<string,mixed>>
     */
    private static function normalize_categories(array $categories): array
    {
        $normalized = [];
        foreach ($categories as $categoryKey => $category) {
            if (!is_array($category)) {
                continue;
            }

            $items = [];
            foreach ((array) ($category['items'] ?? []) as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $id = self::clean_id((string) ($item['id'] ?? ''));
                if ($id === '') {
                    continue;
                }

                $items[] = [
                    'id' => $id,
                    'label' => self::plain((string) ($item['label'] ?? $id)),
                    'description' => self::plain((string) ($item['description'] ?? '')),
                    'severity' => self::choice((string) ($item['severity'] ?? 'info'), ['info', 'savings', 'risk', 'compliance', 'critical'], 'info'),
                    'weight' => max(0, min(25, (int) ($item['weight'] ?? 5))),
                    'finding_type' => self::clean_id((string) ($item['finding_type'] ?? 'finding')),
                    'source_url' => self::safe_source_url((string) ($item['source_url'] ?? '')),
                    'deeplink_triggers' => array_values(array_filter(array_map([self::class, 'clean_id'], (array) ($item['deeplink_triggers'] ?? [])))),
                    'summary_if_open' => self::plain((string) ($item['summary_if_open'] ?? '')),
                    'summary_if_done' => self::plain((string) ($item['summary_if_done'] ?? '')),
                ];
            }

            $normalized[(string) $categoryKey] = [
                'label' => self::plain((string) ($category['label'] ?? $categoryKey)),
                'description' => self::plain((string) ($category['description'] ?? '')),
                'items' => $items,
            ];
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $item
     * @param array<int,string> $selectedTriggers
     */
    private static function is_priority_item(array $item, array $selectedTriggers, string $depth): bool
    {
        $weight = (int) ($item['weight'] ?? 0);
        $triggers = array_values(array_filter(array_map('strval', (array) ($item['deeplink_triggers'] ?? []))));
        $matchesContext = count(array_intersect($triggers, $selectedTriggers)) > 0;

        if ($depth === 'tiefgehend') {
            return true;
        }

        if ($depth === 'erweitert') {
            return $matchesContext || $weight >= 8;
        }

        return $matchesContext || $weight >= 10;
    }

    /**
     * @param array<string,mixed> $input
     * @return array<int,string>
     */
    private static function selected_triggers(array $input): array
    {
        $triggers = [];
        foreach (self::context_options() as $key => $option) {
            if (empty($input[$key])) {
                continue;
            }

            foreach ((array) ($option['triggers'] ?? []) as $trigger) {
                $triggers[] = (string) $trigger;
            }
        }

        if ((string) ($input['tenant_size'] ?? '') === 'enterprise') {
            $triggers[] = 'license_advisor';
            $triggers[] = 'addon_configurator';
        }

        return array_values(array_unique(array_filter($triggers)));
    }

    /**
     * @param array<string,mixed> $input
     * @param array<int,array<string,mixed>> $priorityItems
     */
    private static function focus_label(array $input, array $priorityItems): string
    {
        $selected = [];
        foreach (self::context_options() as $key => $option) {
            if (!empty($input[$key])) {
                $selected[] = (string) ($option['label'] ?? $key);
            }
        }

        if ($selected !== []) {
            return implode(', ', array_slice($selected, 0, 3));
        }

        if ($priorityItems !== []) {
            return (string) ($priorityItems[0]['category_label'] ?? 'Lizenzbestand');
        }

        return 'Lizenzbestand';
    }

    /**
     * @param array<string,mixed> $catalog
     * @return array<int,string>
     */
    private static function sources(array $catalog): array
    {
        return array_values(array_unique(array_filter(array_map('strval', (array) ($catalog['meta']['sources'] ?? [])))));
    }

    private static function plain(string $value): string
    {
        return trim(strip_tags($value));
    }

    private static function clean_id(string $value): string
    {
        return trim((string) preg_replace('/[^a-z0-9_-]+/i', '-', strtolower($value)), '-');
    }

    private static function safe_public_url(string $url): string
    {
        $url = trim($url);
        if (str_starts_with($url, '/') && !str_starts_with($url, '//') && !str_contains($url, "\0")) {
            return $url;
        }

        return '';
    }

    private static function safe_source_url(string $url): string
    {
        $url = trim($url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return '';
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        return in_array($scheme, ['http', 'https'], true) ? $url : '';
    }

    /**
     * @param array<int,string> $allowed
     */
    private static function choice(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    private static function bool_value(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on', 'ja'], true);
    }
}
