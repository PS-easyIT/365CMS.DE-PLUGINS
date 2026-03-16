<?php
/**
 * CMS M365 License – Berechnungslogik
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LIC_Calculator
{
    private const ADDON_FEATURES = [
        'intune',
        'phone_system',
        'audio_conf',
        'power_bi',
        'power_apps',
        'visio',
        'project',
        'planner',
        'automation',
        'teams_premium',
        'entra_id_p1',
        'entra_id_p2',
        'entra_governance',
        'entra_suite',
        'intune_device',
        'exchange_protection',
        'defender_business',
        'defender_office_p1',
        'defender_office_p2',
        'defender_endpoint_p1',
        'defender_endpoint_p2',
        'defender_identity',
        'defender_cloud_apps',
        'copilot_chat',
        'copilot_m365',
        'copilot_studio',
        'security_copilot',
    ];

    /**
     * @param array<int,array<string,mixed>> $requirements
     * @param array<int,array<string,mixed>> $packages
     * @return array<string,mixed>
     */
    public static function evaluate(array $requirements, array $packages, string $pricingTier, string $billingCycle): array
    {
        $basePackages = array_values(array_filter($packages, static fn(array $pkg): bool => !empty($pkg['is_active']) && ($pkg['kind'] ?? '') === 'base'));
        $addonPackages = array_values(array_filter($packages, static fn(array $pkg): bool => !empty($pkg['is_active']) && ($pkg['kind'] ?? '') === 'addon'));
        $featureDefinitions = CMS_M365LIC_Catalog::feature_definitions();
        $repo = CMS_M365LIC_Repository::instance();
        $billingContext = $repo->resolve_billing_cycle($billingCycle, $pricingTier);

        $rows = [];
        $totals = [];
        $grandTotal = 0.0;
        $hasMissingPrices = false;
        $missingPricePackages = [];

        foreach ($requirements as $index => $row) {
            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $label = trim((string) ($row['label'] ?? ('Bedarf ' . ($index + 1))));
            $features = array_values(array_unique(array_filter(array_map('strval', $row['features'] ?? []))));
            $audience = (string) ($row['audience'] ?? 'knowledge');

            $baseFeatureSet = array_values(array_filter($features, static fn(string $feature): bool => !self::is_addon_feature($feature, $featureDefinitions)));
            $addonFeatureSet = array_values(array_filter($features, static fn(string $feature): bool => self::is_addon_feature($feature, $featureDefinitions)));

            $bundle = self::select_recommendation_bundle(
                $basePackages,
                $addonPackages,
                $features,
                $baseFeatureSet,
                $addonFeatureSet,
                $audience,
                $quantity,
                $pricingTier,
                $billingCycle
            );
            $chosenBase = $bundle['base'];
            $addons = $bundle['addons'];
            $rowItems = [];
            $rowTotal = 0.0;
            $rowHasMissingPrice = false;

            if ($chosenBase !== null) {
                $basePrice = $repo->get_price_for_package($chosenBase, $pricingTier);
                $builtBase = self::build_item($chosenBase, $quantity, $basePrice, $pricingTier, $billingContext, 'Basislizenz');
                $rowItems[] = $builtBase;
                self::merge_total($totals, $chosenBase, $builtBase, $pricingTier, $billingContext, 'Basislizenz');
                if ($builtBase['line_total'] !== null) {
                    $rowTotal += (float) $builtBase['line_total'];
                } else {
                    $rowHasMissingPrice = true;
                    $hasMissingPrices = true;
                    $missingPricePackages[$chosenBase['slug']] = $chosenBase['name'];
                }
            }

            foreach ($addons as $addon) {
                $addonPrice = $repo->get_price_for_package($addon, $pricingTier);
                $builtAddon = self::build_item($addon, $quantity, $addonPrice, $pricingTier, $billingContext, 'Add-on');
                $rowItems[] = $builtAddon;
                self::merge_total($totals, $addon, $builtAddon, $pricingTier, $billingContext, 'Add-on');
                if ($builtAddon['line_total'] !== null) {
                    $rowTotal += (float) $builtAddon['line_total'];
                } else {
                    $rowHasMissingPrice = true;
                    $hasMissingPrices = true;
                    $missingPricePackages[$addon['slug']] = $addon['name'];
                }
            }

            if ($chosenBase === null && !empty($baseFeatureSet)) {
                $rowHasMissingPrice = true;
                $rows[] = [
                    'label' => $label,
                    'quantity' => $quantity,
                    'audience' => $audience,
                    'features' => $features,
                    'items' => [],
                    'row_total' => null,
                    'status' => 'warning',
                    'explanation' => 'Für diesen Bedarf wurde keine aktive Basislizenz mit allen gewünschten Funktionen gefunden. Bitte Pakete im Admin ergänzen oder die Feature-Matrix anpassen.',
                ];
                continue;
            }

            $grandTotal += $rowTotal;
            $rows[] = [
                'label' => $label,
                'quantity' => $quantity,
                'audience' => $audience,
                'features' => $features,
                'items' => $rowItems,
                'row_total' => $rowHasMissingPrice ? null : round($rowTotal, 2),
                'status' => 'ok',
                'explanation' => self::build_explanation($chosenBase, $addons, $baseFeatureSet, $addonFeatureSet),
            ];
        }

        uasort($totals, static function (array $left, array $right): int {
            return strcmp((string) $left['name'], (string) $right['name']);
        });

        return [
            'rows' => $rows,
            'totals' => array_values($totals),
            'grand_total' => $hasMissingPrices ? null : round($grandTotal, 2),
            'has_missing_prices' => $hasMissingPrices,
            'missing_price_packages' => array_values($missingPricePackages),
            'billing' => $billingContext,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $basePackages
     * @param array<int,array<string,mixed>> $addonPackages
     * @param array<int,string> $features
     * @param array<int,string> $baseFeatureSet
     * @param array<int,string> $addonFeatureSet
     * @return array{base: array<string,mixed>|null, addons: array<int,array<string,mixed>>}
     */
    private static function select_recommendation_bundle(
        array $basePackages,
        array $addonPackages,
        array $features,
        array $baseFeatureSet,
        array $addonFeatureSet,
        string $audience,
        int $quantity,
        string $pricingTier,
        string $billingCycle
    ): array {
        $candidates = [];

        foreach ($basePackages as $package) {
            $packageFeatures = array_values(array_map('strval', $package['features'] ?? []));
            if ($baseFeatureSet !== [] && array_diff($baseFeatureSet, $packageFeatures) !== []) {
                continue;
            }

            $addons = self::find_matching_addons($addonPackages, $addonFeatureSet, $package, $pricingTier, $billingCycle);
            if (!self::covers_requested_addon_features($addonFeatureSet, $package, $addons)) {
                continue;
            }

            $candidates[] = [
                'base' => $package,
                'addons' => $addons,
                'score' => self::score_bundle($package, $addons, $features, $audience, $quantity, $pricingTier, $billingCycle),
            ];
        }

        if ($baseFeatureSet === []) {
            $addonsWithoutBase = self::find_matching_addons($addonPackages, $addonFeatureSet, null, $pricingTier, $billingCycle);
            if (self::covers_requested_addon_features($addonFeatureSet, null, $addonsWithoutBase)) {
                $candidates[] = [
                    'base' => null,
                    'addons' => $addonsWithoutBase,
                    'score' => self::score_bundle(null, $addonsWithoutBase, $features, $audience, $quantity, $pricingTier, $billingCycle),
                ];
            }
        }

        if ($candidates === []) {
            return [
                'base' => null,
                'addons' => [],
            ];
        }

        usort($candidates, static fn(array $left, array $right): int => $left['score'] <=> $right['score']);

        return [
            'base' => $candidates[0]['base'],
            'addons' => $candidates[0]['addons'],
        ];
    }

    /**
     * @param array<int,string> $requestedAddonFeatures
     * @param array<int,array<string,mixed>> $addons
     */
    private static function covers_requested_addon_features(array $requestedAddonFeatures, ?array $basePackage, array $addons): bool
    {
        if ($requestedAddonFeatures === []) {
            return true;
        }

        $covered = [];

        if ($basePackage !== null) {
            foreach (array_values(array_map('strval', $basePackage['features'] ?? [])) as $feature) {
                if (in_array($feature, $requestedAddonFeatures, true)) {
                    $covered[$feature] = true;
                }
            }
        }

        foreach ($addons as $addon) {
            foreach (array_values(array_map('strval', $addon['features'] ?? [])) as $feature) {
                if (in_array($feature, $requestedAddonFeatures, true)) {
                    $covered[$feature] = true;
                }
            }
        }

        return array_diff($requestedAddonFeatures, array_keys($covered)) === [];
    }

    /**
     * @param array<int,array<string,mixed>> $addons
     * @param array<int,string> $requestedFeatures
     */
    private static function score_bundle(?array $basePackage, array $addons, array $requestedFeatures, string $audience, int $quantity, string $pricingTier, string $billingCycle): float
    {
        $repo = CMS_M365LIC_Repository::instance();
        $bundleCost = 0.0;
        $extraCount = 0;
        $sortOrder = 0;
        $audiencePenalty = 0;

        if ($basePackage !== null) {
            $bundleCost += self::estimate_package_total($basePackage, $quantity, $pricingTier, $billingCycle);
            $packageFeatures = array_values(array_map('strval', $basePackage['features'] ?? []));
            $extraCount += count(array_diff($packageFeatures, $requestedFeatures));
            $sortOrder += (int) ($basePackage['sort_order'] ?? 0);

            if ($audience === 'frontline' && !in_array((string) ($basePackage['audience'] ?? ''), ['frontline', 'all'], true)) {
                $audiencePenalty = 5000;
            }
            if ($audience === 'knowledge' && (string) ($basePackage['audience'] ?? '') === 'frontline') {
                $audiencePenalty = 10000;
            }
        }

        foreach ($addons as $addon) {
            $bundleCost += self::estimate_package_total($addon, $quantity, $pricingTier, $billingCycle);
            $addonFeatures = array_values(array_map('strval', $addon['features'] ?? []));
            $extraCount += count(array_diff($addonFeatures, $requestedFeatures));
            $sortOrder += (int) ($addon['sort_order'] ?? 0);
        }

        return ($bundleCost * 1000) + ($extraCount * 10) + $audiencePenalty + ($sortOrder / 1000);
    }

    /**
     * @param array<string,mixed> $package
     */
    private static function estimate_package_total(array $package, int $quantity, string $pricingTier, string $billingCycle): float
    {
        $repo = CMS_M365LIC_Repository::instance();
        $basePrice = $repo->get_price_for_package($package, $pricingTier);
        $adjustedPrice = $repo->apply_billing_cycle($basePrice, $billingCycle);
        if ($adjustedPrice === null) {
            return 999999.0;
        }

        return ((string) ($package['pricing_basis'] ?? 'per_user')) === 'flat_monthly'
            ? $adjustedPrice
            : ($adjustedPrice * $quantity);
    }

    /**
     * @param array<string,array<string,mixed>> $featureDefinitions
     */
    private static function is_addon_feature(string $feature, array $featureDefinitions): bool
    {
        if (isset($featureDefinitions[$feature]['base'])) {
            return empty($featureDefinitions[$feature]['base']);
        }

        return in_array($feature, self::ADDON_FEATURES, true);
    }

    /**
     * @param array<int,array<string,mixed>> $packages
     * @param array<int,string> $requiredFeatures
     * @return array<string,mixed>|null
     */
    private static function find_best_base_package(array $packages, array $requiredFeatures, string $audience, string $pricingTier, string $billingCycle): ?array
    {
        if (empty($requiredFeatures)) {
            return null;
        }

        $repo = CMS_M365LIC_Repository::instance();
        $candidates = [];

        foreach ($packages as $package) {
            $packageFeatures = array_values(array_map('strval', $package['features'] ?? []));
            if (array_diff($requiredFeatures, $packageFeatures) !== []) {
                continue;
            }

            $audiencePenalty = 0;
            if ($audience === 'frontline' && !in_array((string) ($package['audience'] ?? ''), ['frontline', 'all'], true)) {
                $audiencePenalty = 5;
            }
            if ($audience === 'knowledge' && (string) ($package['audience'] ?? '') === 'frontline') {
                $audiencePenalty = 10;
            }

            $extraCount = count(array_diff($packageFeatures, $requiredFeatures));
            $basePrice = $repo->get_price_for_package($package, $pricingTier);
            $price = $repo->apply_billing_cycle($basePrice, $billingCycle);
            $priceScore = $price !== null ? $price : 999999;

            $candidates[] = [
                'package' => $package,
                'score' => ($priceScore * 100) + ($extraCount * 3) + $audiencePenalty + ((int) ($package['sort_order'] ?? 0) / 1000),
            ];
        }

        if (empty($candidates)) {
            return null;
        }

        usort($candidates, static fn(array $a, array $b): int => $a['score'] <=> $b['score']);
        return $candidates[0]['package'];
    }

    /**
     * @param array<int,array<string,mixed>> $packages
     * @param array<int,string> $addonFeatures
     * @param array<string,mixed>|null $basePackage
     * @return array<int,array<string,mixed>>
     */
    private static function find_matching_addons(array $packages, array $addonFeatures, ?array $basePackage, string $pricingTier, string $billingCycle): array
    {
        $repo = CMS_M365LIC_Repository::instance();
        $selected = [];
        $basePackageFeatures = array_values(array_map('strval', $basePackage['features'] ?? []));

        foreach ($addonFeatures as $feature) {
            if ($basePackage !== null && in_array($feature, $basePackageFeatures, true)) {
                continue;
            }

            $matching = array_values(array_filter($packages, function (array $package) use ($feature, $basePackage): bool {
                $packageFeatures = array_values(array_map('strval', $package['features'] ?? []));
                if (!in_array($feature, $packageFeatures, true)) {
                    return false;
                }

                $requiredTags = array_values(array_map('strval', $package['prerequisite_tags'] ?? []));
                if (empty($requiredTags)) {
                    return true;
                }

                if ($basePackage === null) {
                    return false;
                }

                $baseTags = array_values(array_map('strval', $basePackage['tags'] ?? []));
                return array_intersect($requiredTags, $baseTags) !== [];
            }));

            if (empty($matching)) {
                continue;
            }

            usort($matching, function (array $left, array $right) use ($repo, $pricingTier, $billingCycle): int {
                $leftPrice = $repo->apply_billing_cycle($repo->get_price_for_package($left, $pricingTier), $billingCycle) ?? 999999.0;
                $rightPrice = $repo->apply_billing_cycle($repo->get_price_for_package($right, $pricingTier), $billingCycle) ?? 999999.0;
                if ($leftPrice === $rightPrice) {
                    return strcmp((string) $left['name'], (string) $right['name']);
                }
                return $leftPrice <=> $rightPrice;
            });

            $selected[$matching[0]['slug']] = $matching[0];
        }

        return array_values($selected);
    }

    /**
     * @param array<string,mixed> $package
     * @param array<string,mixed> $billingContext
     * @return array<string,mixed>
     */
    private static function build_item(array $package, int $quantity, ?float $basePrice, string $pricingTier, array $billingContext, string $typeLabel): array
    {
        $repo = CMS_M365LIC_Repository::instance();
        $featureDefinitions = CMS_M365LIC_Catalog::feature_definitions();
        $pricingBasis = (string) ($package['pricing_basis'] ?? 'per_user');
        $adjustedPrice = $repo->apply_billing_cycle($basePrice, (string) ($billingContext['key'] ?? 'annual_upfront'));
        $billingQuantity = $pricingBasis === 'flat_monthly' ? 1 : $quantity;
        $quantityLabel = $pricingBasis === 'flat_monthly'
            ? '1 Fixpreis / Monat'
            : $quantity . ' Benutzer';

        return [
            'slug' => $package['slug'],
            'name' => $package['name'],
            'type_label' => $typeLabel,
            'quantity' => $quantity,
            'quantity_label' => $quantityLabel,
            'billing_quantity' => $billingQuantity,
            'pricing_tier' => $pricingTier,
            'pricing_basis' => $pricingBasis,
            'pricing_basis_label' => $pricingBasis === 'flat_monthly' ? 'Fixpreis' : 'pro Benutzer',
            'billing_cycle_key' => (string) ($billingContext['key'] ?? 'annual_upfront'),
            'billing_cycle_label' => (string) ($billingContext['label'] ?? '1 Jahr · jährliche Zahlung'),
            'base_unit_price' => $basePrice,
            'unit_price' => $adjustedPrice,
            'line_total' => $adjustedPrice !== null ? round($adjustedPrice * $billingQuantity, 2) : null,
            'pricing_note' => (string) ($package['pricing_note'] ?? ''),
            'source_note' => (string) ($package['source_note'] ?? ''),
            'description' => (string) ($package['description'] ?? ''),
            'feature_details' => self::build_feature_details($package, $featureDefinitions),
        ];
    }

    /**
     * @param array<string,array<string,mixed>> $totals
     * @param array<string,mixed> $package
     * @param array<string,mixed> $item
     * @param array<string,mixed> $billingContext
     */
    private static function merge_total(array &$totals, array $package, array $item, string $pricingTier, array $billingContext, string $typeLabel): void
    {
        $slug = (string) $package['slug'];
        if (!isset($totals[$slug])) {
            $totals[$slug] = [
                'slug' => $slug,
                'name' => $package['name'],
                'type_label' => $typeLabel,
                'quantity' => 0,
                'quantity_label' => '',
                'billing_quantity' => 0,
                'unit_price' => $item['unit_price'],
                'line_total' => 0.0,
                'pricing_tier' => $pricingTier,
                'pricing_basis' => $item['pricing_basis'],
                'pricing_basis_label' => $item['pricing_basis_label'],
                'billing_cycle_label' => (string) ($billingContext['label'] ?? '1 Jahr · jährliche Zahlung'),
                'pricing_note' => (string) ($package['pricing_note'] ?? ''),
                'source_note' => (string) ($package['source_note'] ?? ''),
                'description' => (string) ($package['description'] ?? ''),
                'feature_details' => $item['feature_details'] ?? [],
            ];
        }

        $totals[$slug]['quantity'] += (int) ($item['quantity'] ?? 0);
        $totals[$slug]['billing_quantity'] += (int) ($item['billing_quantity'] ?? 0);
        $totals[$slug]['quantity_label'] = ($item['pricing_basis'] ?? 'per_user') === 'flat_monthly'
            ? ($totals[$slug]['billing_quantity'] . ' Fixpreis')
            : ($totals[$slug]['quantity'] . ' Benutzer');

        if ($item['line_total'] !== null) {
            $totals[$slug]['line_total'] = round(((float) $totals[$slug]['line_total']) + (float) $item['line_total'], 2);
        } else {
            $totals[$slug]['line_total'] = null;
        }
    }

    /**
     * @param array<string,mixed>|null $basePackage
     * @param array<int,array<string,mixed>> $addons
     * @param array<int,string> $baseFeatures
     * @param array<int,string> $addonFeatures
     */
    private static function build_explanation(?array $basePackage, array $addons, array $baseFeatures, array $addonFeatures): string
    {
        $featureDefinitions = CMS_M365LIC_Catalog::feature_definitions();
        $parts = [];
        $coveredAddonFeatures = [];

        if ($basePackage !== null) {
            $parts[] = 'Basis: ' . $basePackage['name'] . ' deckt ' . implode(', ', self::feature_labels($baseFeatures, $featureDefinitions)) . ' ab.';

            $basePackageFeatures = array_values(array_map('strval', $basePackage['features'] ?? []));
            $coveredAddonFeatures = array_values(array_intersect($addonFeatures, $basePackageFeatures));

            if (in_array('terminalserver', $baseFeatures, true)) {
                $baseTags = array_values(array_map('strval', $basePackage['tags'] ?? []));
                if (in_array('shared_computer_activation', $baseTags, true) || in_array('rds', $baseTags, true)) {
                    $parts[] = $basePackage['name'] . ' wurde speziell gewählt, weil für Terminalserver-/RDS-Szenarien Shared Computer Activation benötigt wird und viele Standardpläne ohne diese Berechtigung dafür nicht geeignet sind.';
                } else {
                    $parts[] = 'Terminalserver wurde angefragt; deshalb waren reine Web- oder Standardpläne ohne Shared-Activation-Recht keine passende Wahl.';
                }
            }
        }

        if (!empty($addons)) {
            $parts[] = 'Add-ons: ' . implode(', ', array_map(static fn(array $addon): string => (string) $addon['name'], $addons)) . '.';
        }

        if (!empty($coveredAddonFeatures)) {
            $parts[] = 'Bereits enthalten in der Basislizenz: ' . implode(', ', self::feature_labels($coveredAddonFeatures, $featureDefinitions)) . '.';
        }

        $addonNames = [];
        foreach ($addons as $addon) {
            foreach (array_values(array_map('strval', $addon['features'] ?? [])) as $feature) {
                if (in_array($feature, $addonFeatures, true)) {
                    $addonNames[$feature] = true;
                }
            }
        }

        $uncoveredAddonFeatures = array_values(array_diff($addonFeatures, $coveredAddonFeatures, array_keys($addonNames)));
        if (!empty($uncoveredAddonFeatures)) {
            $parts[] = 'Für einzelne Zusatzfunktionen (' . implode(', ', self::feature_labels($uncoveredAddonFeatures, $featureDefinitions)) . ') wurden keine kompatiblen aktiven Add-ons gefunden.';
        }

        if (empty($parts)) {
            return 'Keine Empfehlung möglich.';
        }

        return implode(' ', $parts);
    }

    /**
     * @param array<string,mixed> $package
     * @param array<string,array<string,mixed>> $definitions
     * @return array<int,array<string,string>>
     */
    private static function build_feature_details(array $package, array $definitions): array
    {
        $details = [];

        foreach (array_values(array_map('strval', $package['features'] ?? [])) as $featureKey) {
            $definition = $definitions[$featureKey] ?? [];
            $group = (string) ($definition['group'] ?? 'general');
            $details[] = [
                'key' => $featureKey,
                'label' => (string) ($definition['label'] ?? $featureKey),
                'description' => (string) ($definition['description'] ?? ''),
                'group' => $group,
                'group_label' => self::humanize_feature_group($group),
            ];
        }

        return $details;
    }

    private static function humanize_feature_group(string $group): string
    {
        return match ($group) {
            'core' => 'Basis',
            'collaboration' => 'Zusammenarbeit',
            'security' => 'Security',
            'security-addon' => 'Security Add-on',
            'addons' => 'Add-ons',
            'identity' => 'Identität',
            'worker' => 'Frontline',
            'copilot' => 'Copilot',
            'productivity' => 'Produktivität',
            'power-platform' => 'Power Platform',
            default => ucwords(str_replace(['-', '_'], ' ', $group)),
        };
    }

    /**
     * @param array<int,string> $features
     * @param array<string,array<string,mixed>> $definitions
     * @return array<int,string>
     */
    private static function feature_labels(array $features, array $definitions): array
    {
        return array_values(array_map(static function (string $feature) use ($definitions): string {
            return (string) ($definitions[$feature]['label'] ?? $feature);
        }, $features));
    }
}
