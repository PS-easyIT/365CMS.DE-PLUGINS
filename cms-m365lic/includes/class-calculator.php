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
        'phone_system',
        'audio_conf',
        'power_bi',
        'visio',
        'project',
        'planner',
        'automation',
        'teams_premium',
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
    public static function evaluate(array $requirements, array $packages, string $pricingTier): array
    {
        $basePackages = array_values(array_filter($packages, static fn(array $pkg): bool => !empty($pkg['is_active']) && ($pkg['kind'] ?? '') === 'base'));
        $addonPackages = array_values(array_filter($packages, static fn(array $pkg): bool => !empty($pkg['is_active']) && ($pkg['kind'] ?? '') === 'addon'));
        $repo = CMS_M365LIC_Repository::instance();

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

            $baseFeatureSet = array_values(array_filter($features, static fn(string $feature): bool => !in_array($feature, self::ADDON_FEATURES, true)));
            $addonFeatureSet = array_values(array_filter($features, static fn(string $feature): bool => in_array($feature, self::ADDON_FEATURES, true)));

            $chosenBase = self::find_best_base_package($basePackages, $baseFeatureSet, $audience, $pricingTier);
            $addons = self::find_matching_addons($addonPackages, $addonFeatureSet, $chosenBase, $pricingTier);
            $rowItems = [];
            $rowTotal = 0.0;
            $rowHasMissingPrice = false;

            if ($chosenBase !== null) {
                $basePrice = $repo->get_price_for_package($chosenBase, $pricingTier);
                $rowItems[] = self::build_item($chosenBase, $quantity, $basePrice, $pricingTier, 'Basislizenz');
                self::merge_total($totals, $chosenBase, $quantity, $basePrice, $pricingTier, 'Basislizenz');
                if ($basePrice !== null) {
                    $rowTotal += $basePrice * $quantity;
                } else {
                    $rowHasMissingPrice = true;
                    $hasMissingPrices = true;
                    $missingPricePackages[$chosenBase['slug']] = $chosenBase['name'];
                }
            }

            foreach ($addons as $addon) {
                $addonPrice = $repo->get_price_for_package($addon, $pricingTier);
                $rowItems[] = self::build_item($addon, $quantity, $addonPrice, $pricingTier, 'Add-on');
                self::merge_total($totals, $addon, $quantity, $addonPrice, $pricingTier, 'Add-on');
                if ($addonPrice !== null) {
                    $rowTotal += $addonPrice * $quantity;
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
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $packages
     * @param array<int,string> $requiredFeatures
     * @return array<string,mixed>|null
     */
    private static function find_best_base_package(array $packages, array $requiredFeatures, string $audience, string $pricingTier): ?array
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
            $price = $repo->get_price_for_package($package, $pricingTier);
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
    private static function find_matching_addons(array $packages, array $addonFeatures, ?array $basePackage, string $pricingTier): array
    {
        $repo = CMS_M365LIC_Repository::instance();
        $selected = [];

        foreach ($addonFeatures as $feature) {
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

            usort($matching, function (array $left, array $right) use ($repo, $pricingTier): int {
                $leftPrice = $repo->get_price_for_package($left, $pricingTier) ?? 999999.0;
                $rightPrice = $repo->get_price_for_package($right, $pricingTier) ?? 999999.0;
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
     * @return array<string,mixed>
     */
    private static function build_item(array $package, int $quantity, ?float $price, string $pricingTier, string $typeLabel): array
    {
        return [
            'slug' => $package['slug'],
            'name' => $package['name'],
            'type_label' => $typeLabel,
            'quantity' => $quantity,
            'pricing_tier' => $pricingTier,
            'unit_price' => $price,
            'line_total' => $price !== null ? round($price * $quantity, 2) : null,
            'pricing_note' => (string) ($package['pricing_note'] ?? ''),
            'source_note' => (string) ($package['source_note'] ?? ''),
            'description' => (string) ($package['description'] ?? ''),
        ];
    }

    /**
     * @param array<string,array<string,mixed>> $totals
     * @param array<string,mixed> $package
     */
    private static function merge_total(array &$totals, array $package, int $quantity, ?float $price, string $pricingTier, string $typeLabel): void
    {
        $slug = (string) $package['slug'];
        if (!isset($totals[$slug])) {
            $totals[$slug] = [
                'slug' => $slug,
                'name' => $package['name'],
                'type_label' => $typeLabel,
                'quantity' => 0,
                'unit_price' => $price,
                'line_total' => 0.0,
                'pricing_tier' => $pricingTier,
                'pricing_note' => (string) ($package['pricing_note'] ?? ''),
                'source_note' => (string) ($package['source_note'] ?? ''),
            ];
        }

        $totals[$slug]['quantity'] += $quantity;
        if ($price !== null) {
            $totals[$slug]['line_total'] = round(((float) $totals[$slug]['line_total']) + ($price * $quantity), 2);
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
        $parts = [];

        if ($basePackage !== null) {
            $parts[] = 'Basis: ' . $basePackage['name'] . ' deckt ' . implode(', ', $baseFeatures) . ' ab.';
        }

        if (!empty($addons)) {
            $parts[] = 'Add-ons: ' . implode(', ', array_map(static fn(array $addon): string => (string) $addon['name'], $addons)) . '.';
        } elseif (!empty($addonFeatures)) {
            $parts[] = 'Für einzelne Zusatzfunktionen wurden keine kompatiblen aktiven Add-ons gefunden.';
        }

        if (empty($parts)) {
            return 'Keine Empfehlung möglich.';
        }

        return implode(' ', $parts);
    }
}
