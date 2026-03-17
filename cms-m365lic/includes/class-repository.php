<?php
/**
 * CMS M365 License – Repository & Persistence
 *
 * @package CMS_M365LIC
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

final class CMS_M365LIC_Repository
{
    private static ?self $instance = null;
    private const PACKAGE_SELECT_COLUMNS = 'id, slug, name, kind, category, audience, pricing_basis, description, features_json, tags_json, prerequisite_tags_json, public_price, member_price, group_price, currency, pricing_note, source_note, is_active, sort_order';

    /** @var array<string,string>|null */
    private ?array $settingsCache = null;

    /** @var array<string,array<int,array<string,mixed>>> */
    private array $packagesCache = [];

    public static function instance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    private function db(): \CMS\Database
    {
        return \CMS\Database::instance();
    }

    private function pdo(): \PDO
    {
        return $this->db()->getPdo();
    }

    private function prefix(): string
    {
        return $this->db()->getPrefix();
    }

    /**
     * @return array<string,string>
     */
    public function get_settings(): array
    {
        if ($this->settingsCache !== null) {
            return $this->settingsCache;
        }

        $defaults = CMS_M365LIC_Catalog::default_settings();

        try {
            $stmt = $this->db()->prepare("SELECT setting_key, setting_value FROM {$this->prefix()}m365lic_settings");
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR) ?: [];
            foreach ($rows as $key => $value) {
                $defaults[(string) $key] = (string) $value;
            }
        } catch (\Throwable $e) {
            // ignore and use defaults
        }

        $defaults['default_currency'] = $this->normalize_currency($defaults['default_currency'] ?? 'EUR');

        $this->settingsCache = $defaults;

        return $defaults;
    }

    /**
     * @param array<string,string> $settings
     */
    public function save_settings(array $settings): void
    {
        $sql = "INSERT INTO {$this->prefix()}m365lic_settings (setting_key, setting_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        $stmt = $this->db()->prepare($sql);

        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        $this->settingsCache = null;
    }

    public function sync_special_groups(): void
    {
        $settings = $this->get_settings();
        $defaultGroup = $this->get_special_group_by_key((string) ($settings['default_group_key'] ?? 'partner'));

        if ($defaultGroup === null) {
            $this->save_special_group([
                'group_key' => (string) ($settings['default_group_key'] ?? 'partner'),
                'group_label' => (string) ($settings['default_group_label'] ?? 'Partner / Spezialgruppe'),
                'description' => 'Standardgruppe für Spezial-/Reseller-Zugänge.',
                'default_markup_percent' => 0,
                'report_title' => 'Microsoft 365 Reseller-Report',
                'report_intro' => 'Geschützter Report für die zugewiesene Reseller-/Spezialgruppe.',
                'is_active' => 1,
            ]);
        }

        try {
            $stmt = $this->db()->prepare(
                "SELECT DISTINCT group_key, group_label, special_markup_percent
                 FROM {$this->prefix()}m365lic_special_users
                 WHERE (group_id IS NULL OR group_id = 0) AND group_key <> ''"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return;
        }

        foreach ($rows as $row) {
            $groupKey = $this->normalize_group_key($row['group_key'] ?? 'special');
            $groupLabel = trim((string) ($row['group_label'] ?? 'Spezialzugang'));
            $group = $this->get_special_group_by_key($groupKey);

            if ($group === null) {
                $this->save_special_group([
                    'group_key' => $groupKey,
                    'group_label' => $groupLabel,
                    'description' => '',
                    'default_markup_percent' => $row['special_markup_percent'] ?? 0,
                    'report_title' => '',
                    'report_intro' => '',
                    'is_active' => 1,
                ]);
                $group = $this->get_special_group_by_key($groupKey);
            }

            if ($group === null) {
                continue;
            }

            try {
                $updateStmt = $this->db()->prepare(
                    "UPDATE {$this->prefix()}m365lic_special_users
                     SET group_id = ?
                     WHERE (group_id IS NULL OR group_id = 0) AND group_key = ?"
                );
                $updateStmt->execute([(int) ($group['id'] ?? 0), $groupKey]);
            } catch (\Throwable $e) {
                // ignore migration edge cases
            }
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function get_user_pricing_profile(int $userId): array
    {
        $profile = $this->default_user_pricing_profile($userId);
        if ($userId <= 0) {
            return $profile;
        }

        try {
            $stmt = $this->db()->prepare(
                "SELECT partner_name, partner_logo_path, whitelabel_title, whitelabel_intro,
                        base_markup_percent, addon_markup_percent, copilot_markup_percent
                 FROM {$this->prefix()}m365lic_user_profiles
                 WHERE user_id = ?
                 LIMIT 1"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC) ?: [];

            if (is_array($row) && $row !== []) {
                $profile['partner_name'] = trim((string) ($row['partner_name'] ?? ''));
                $profile['partner_logo_path'] = trim((string) ($row['partner_logo_path'] ?? ''));
                $profile['whitelabel_title'] = trim((string) ($row['whitelabel_title'] ?? ''));
                $profile['whitelabel_intro'] = trim((string) ($row['whitelabel_intro'] ?? ''));
                $profile['base_markup_percent'] = $this->normalize_percent($row['base_markup_percent'] ?? 0);
                $profile['addon_markup_percent'] = $this->normalize_percent($row['addon_markup_percent'] ?? 0);
                $profile['copilot_markup_percent'] = $this->normalize_percent($row['copilot_markup_percent'] ?? 0);
            }
        } catch (\Throwable $e) {
            // ignore and use defaults
        }

        $profile['cost_overrides'] = $this->get_user_package_cost_map($userId);

        return $profile;
    }

    /**
     * @param array<string,mixed> $profile
     */
    public function save_user_pricing_profile(int $userId, array $profile): void
    {
        if ($userId <= 0) {
            return;
        }

        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->prefix()}m365lic_user_profiles
                (user_id, partner_name, partner_logo_path, whitelabel_title, whitelabel_intro, base_markup_percent, addon_markup_percent, copilot_markup_percent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                partner_name = VALUES(partner_name),
                partner_logo_path = VALUES(partner_logo_path),
                whitelabel_title = VALUES(whitelabel_title),
                whitelabel_intro = VALUES(whitelabel_intro),
                base_markup_percent = VALUES(base_markup_percent),
                addon_markup_percent = VALUES(addon_markup_percent),
                copilot_markup_percent = VALUES(copilot_markup_percent)"
        );

        $stmt->execute([
            $userId,
            trim((string) ($profile['partner_name'] ?? '')),
            trim((string) ($profile['partner_logo_path'] ?? '')),
            trim((string) ($profile['whitelabel_title'] ?? '')),
            trim((string) ($profile['whitelabel_intro'] ?? '')),
            $this->normalize_percent($profile['base_markup_percent'] ?? 0),
            $this->normalize_percent($profile['addon_markup_percent'] ?? 0),
            $this->normalize_percent($profile['copilot_markup_percent'] ?? 0),
        ]);
    }

    /**
     * @return array<int,float>
     */
    public function get_user_package_cost_map(int $userId): array
    {
        if ($userId <= 0) {
            return [];
        }

        try {
            $stmt = $this->db()->prepare(
                "SELECT package_id, ek_price
                 FROM {$this->prefix()}m365lic_user_package_costs
                 WHERE user_id = ?"
            );
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        $costs = [];
        foreach ($rows as $row) {
            $packageId = (int) ($row['package_id'] ?? 0);
            $ekPrice = $row['ek_price'] !== null ? (float) $row['ek_price'] : null;
            if ($packageId > 0 && $ekPrice !== null) {
                $costs[$packageId] = $ekPrice;
            }
        }

        return $costs;
    }

    /**
     * @param array<int|string,mixed> $costs
     */
    public function save_user_package_costs(int $userId, array $costs): void
    {
        if ($userId <= 0) {
            return;
        }

        $normalizedCosts = [];
        foreach ($costs as $packageId => $value) {
            $id = (int) $packageId;
            $price = $this->normalize_price($value);
            if ($id > 0 && $price !== null) {
                $normalizedCosts[$id] = $price;
            }
        }

        $deleteStmt = $this->db()->prepare("DELETE FROM {$this->prefix()}m365lic_user_package_costs WHERE user_id = ?");
        $deleteStmt->execute([$userId]);

        if ($normalizedCosts === []) {
            return;
        }

        $insertStmt = $this->db()->prepare(
            "INSERT INTO {$this->prefix()}m365lic_user_package_costs (user_id, package_id, ek_price)
             VALUES (?, ?, ?)"
        );

        foreach ($normalizedCosts as $packageId => $price) {
            $insertStmt->execute([$userId, $packageId, $price]);
        }
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function get_packages(bool $includeInactive = true): array
    {
        $cacheKey = $includeInactive ? 'all' : 'active';
        if (isset($this->packagesCache[$cacheKey])) {
            return $this->packagesCache[$cacheKey];
        }

        $sql = 'SELECT ' . self::PACKAGE_SELECT_COLUMNS . " FROM {$this->prefix()}m365lic_packages";
        $params = [];

        if (!$includeInactive) {
            $sql .= ' WHERE is_active = ?';
            $params[] = 1;
        }

        $sql .= ' ORDER BY sort_order ASC, name ASC';
        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        $this->packagesCache[$cacheKey] = array_map([$this, 'hydrate_package'], $rows);

        return $this->packagesCache[$cacheKey];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function get_package(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT ' . self::PACKAGE_SELECT_COLUMNS . " FROM {$this->prefix()}m365lic_packages WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->hydrate_package($row) : null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function get_package_by_slug(string $slug): ?array
    {
        foreach ($this->packagesCache as $packages) {
            foreach ($packages as $package) {
                if (($package['slug'] ?? '') === $slug) {
                    return $package;
                }
            }
        }

        $stmt = $this->db()->prepare('SELECT ' . self::PACKAGE_SELECT_COLUMNS . " FROM {$this->prefix()}m365lic_packages WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ? $this->hydrate_package($row) : null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function save_package(array $data): void
    {
        $normalizedPrices = $this->normalize_package_prices([
            'public_price' => $this->normalize_price($data['public_price'] ?? null),
            'member_price' => $this->normalize_price($data['member_price'] ?? null),
            'group_price' => $this->normalize_price($data['group_price'] ?? null),
        ]);

        $record = [
            'slug' => (string) ($data['slug'] ?? ''),
            'name' => (string) ($data['name'] ?? ''),
            'kind' => (string) ($data['kind'] ?? 'base'),
            'category' => (string) ($data['category'] ?? 'general'),
            'audience' => (string) ($data['audience'] ?? 'knowledge'),
            'pricing_basis' => $this->normalize_pricing_basis($data['pricing_basis'] ?? 'per_user'),
            'description' => (string) ($data['description'] ?? ''),
            'features_json' => json_encode(array_values(array_unique($data['features'] ?? [])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'tags_json' => json_encode(array_values(array_unique($data['tags'] ?? [])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'prerequisite_tags_json' => json_encode(array_values(array_unique($data['prerequisite_tags'] ?? [])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'public_price' => $normalizedPrices['public_price'],
            'member_price' => $normalizedPrices['member_price'],
            'group_price' => $normalizedPrices['group_price'],
            'currency' => $this->normalize_currency($data['currency'] ?? 'EUR'),
            'pricing_note' => (string) ($data['pricing_note'] ?? ''),
            'source_note' => (string) ($data['source_note'] ?? ''),
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
        ];

        $id = (int) ($data['id'] ?? 0);

        if ($id > 0) {
            $sql = "UPDATE {$this->prefix()}m365lic_packages
                    SET slug = ?, name = ?, kind = ?, category = ?, audience = ?, pricing_basis = ?, description = ?,
                        features_json = ?, tags_json = ?, prerequisite_tags_json = ?,
                        public_price = ?, member_price = ?, group_price = ?, currency = ?,
                        pricing_note = ?, source_note = ?, is_active = ?, sort_order = ?
                    WHERE id = ?";
            $params = array_values($record);
            $params[] = $id;
            $this->db()->prepare($sql)->execute($params);
            $this->packagesCache = [];
            return;
        }

        $sql = "INSERT INTO {$this->prefix()}m365lic_packages
                (slug, name, kind, category, audience, pricing_basis, description, features_json, tags_json, prerequisite_tags_json,
                 public_price, member_price, group_price, currency, pricing_note, source_note, is_active, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db()->prepare($sql)->execute(array_values($record));
        $this->packagesCache = [];
    }

    public function reset_catalog_to_defaults(): void
    {
        $this->pdo()->exec("TRUNCATE TABLE {$this->prefix()}m365lic_packages");
        $this->packagesCache = [];
        $this->seed_defaults(true);
    }

    public function seed_defaults(bool $force): void
    {
        $settings = CMS_M365LIC_Catalog::default_settings();
        $sqlSetting = "INSERT INTO {$this->prefix()}m365lic_settings (setting_key, setting_value)
                       VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE setting_value = setting_value";
        $stmtSetting = $this->db()->prepare($sqlSetting);
        foreach ($settings as $key => $value) {
            $stmtSetting->execute([$key, $value]);
        }

        $this->seed_default_alternatives_if_needed($force);

        if ($force) {
            $this->pdo()->exec("TRUNCATE TABLE {$this->prefix()}m365lic_packages");
            $this->packagesCache = [];
        }

        foreach (CMS_M365LIC_Catalog::package_seeds() as $package) {
            if ($force) {
                $this->save_package($package);
                continue;
            }

            $this->sync_seed_package($package);
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function get_statistics(): array
    {
        $packages = $this->get_packages(true);
        $activePackages = array_filter($packages, static fn(array $package): bool => !empty($package['is_active']));
        $basePackages = array_filter($activePackages, static fn(array $package): bool => ($package['kind'] ?? '') === 'base');
        $addonPackages = array_filter($activePackages, static fn(array $package): bool => ($package['kind'] ?? '') === 'addon');
        $withPrices = array_filter($activePackages, function (array $package): bool {
            return $package['public_price'] !== null || $package['member_price'] !== null || $package['group_price'] !== null;
        });

        return [
            'packages_total' => count($packages),
            'packages_active' => count($activePackages),
            'packages_base' => count($basePackages),
            'packages_addon' => count($addonPackages),
            'packages_with_prices' => count($withPrices),
            'packages_without_prices' => max(0, count($activePackages) - count($withPrices)),
            'special_groups_total' => count($this->get_special_groups()),
            'special_users_total' => count($this->get_special_users()),
            'feature_total' => count(CMS_M365LIC_Catalog::feature_definitions()),
            'preset_total' => count(CMS_M365LIC_Catalog::presets()),
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function get_special_groups(bool $includeInactive = true): array
    {
        $params = [];
        $where = '';

        if (!$includeInactive) {
            $where = 'WHERE sg.is_active = ?';
            $params[] = 1;
        }

        try {
            $stmt = $this->db()->prepare(
                "SELECT sg.*, COUNT(su.user_id) AS assigned_users
                 FROM {$this->prefix()}m365lic_special_groups sg
                 LEFT JOIN {$this->prefix()}m365lic_special_users su ON su.group_id = sg.id AND su.is_active = 1
                 {$where}
                 GROUP BY sg.id
                 ORDER BY sg.is_active DESC, sg.group_label ASC"
            );
            $stmt->execute($params);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        return array_map([$this, 'hydrate_special_group'], $rows);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function get_special_group(int $groupId): ?array
    {
        if ($groupId <= 0) {
            return null;
        }

        try {
            $stmt = $this->db()->prepare(
                "SELECT sg.*, COUNT(su.user_id) AS assigned_users
                 FROM {$this->prefix()}m365lic_special_groups sg
                 LEFT JOIN {$this->prefix()}m365lic_special_users su ON su.group_id = sg.id AND su.is_active = 1
                 WHERE sg.id = ?
                 GROUP BY sg.id
                 LIMIT 1"
            );
            $stmt->execute([$groupId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return null;
        }

        return is_array($row) ? $this->hydrate_special_group($row) : null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function get_special_group_by_key(string $groupKey): ?array
    {
        $groupKey = $this->normalize_group_key($groupKey);
        if ($groupKey === '') {
            return null;
        }

        try {
            $stmt = $this->db()->prepare(
                "SELECT sg.*, COUNT(su.user_id) AS assigned_users
                 FROM {$this->prefix()}m365lic_special_groups sg
                 LEFT JOIN {$this->prefix()}m365lic_special_users su ON su.group_id = sg.id AND su.is_active = 1
                 WHERE sg.group_key = ?
                 GROUP BY sg.id
                 LIMIT 1"
            );
            $stmt->execute([$groupKey]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return null;
        }

        return is_array($row) ? $this->hydrate_special_group($row) : null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function save_special_group(array $data): void
    {
        $groupId = (int) ($data['id'] ?? 0);
        $groupKey = $this->normalize_group_key($data['group_key'] ?? 'special');
        $groupLabel = trim((string) ($data['group_label'] ?? 'Spezialgruppe'));
        $description = trim((string) ($data['description'] ?? ''));
        $defaultMarkup = $this->normalize_percent($data['default_markup_percent'] ?? 0);
        $reportTitle = trim((string) ($data['report_title'] ?? ''));
        $reportIntro = trim((string) ($data['report_intro'] ?? ''));
        $isActive = !empty($data['is_active']) ? 1 : 0;

        if ($groupKey === '') {
            $groupKey = 'special';
        }

        if ($groupLabel === '') {
            $groupLabel = 'Spezialgruppe';
        }

        if ($groupId > 0) {
            $stmt = $this->db()->prepare(
                "UPDATE {$this->prefix()}m365lic_special_groups
                 SET group_key = ?, group_label = ?, description = ?, default_markup_percent = ?, report_title = ?, report_intro = ?, is_active = ?
                 WHERE id = ?"
            );
            $stmt->execute([$groupKey, $groupLabel, $description, $defaultMarkup, $reportTitle, $reportIntro, $isActive, $groupId]);
        } else {
            $stmt = $this->db()->prepare(
                "INSERT INTO {$this->prefix()}m365lic_special_groups
                    (group_key, group_label, description, default_markup_percent, report_title, report_intro, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$groupKey, $groupLabel, $description, $defaultMarkup, $reportTitle, $reportIntro, $isActive]);
            $groupId = (int) $this->pdo()->lastInsertId();
        }

        if ($groupId > 0) {
            $this->sync_special_group_to_users($groupId);
        }
    }

    public function delete_special_group(int $groupId): bool
    {
        if ($groupId <= 0) {
            return false;
        }

        $group = $this->get_special_group($groupId);
        if ($group === null || (int) ($group['assigned_users'] ?? 0) > 0) {
            return false;
        }

        $stmt = $this->db()->prepare("DELETE FROM {$this->prefix()}m365lic_special_groups WHERE id = ?");
        $stmt->execute([$groupId]);

        return true;
    }

    /**
     * @return array<string,string>
     */
    public function resolve_pricing_context(?string $requestedTier, ?string $requestedGroupKey = null): array
    {
        $settings = $this->get_settings();
        $tier = in_array((string) $requestedTier, ['public', 'member', 'group'], true)
            ? (string) $requestedTier
            : 'public';

        $groupKey = trim((string) ($requestedGroupKey ?? $settings['default_group_key'] ?? ''));
        $label = match ($tier) {
            'member' => 'Mitglied',
            'group' => $settings['default_group_label'] ?? 'Spezialgruppe',
            default => 'Öffentlich',
        };

        return [
            'tier' => $tier,
            'group_key' => $groupKey,
            'label' => (string) $label,
        ];
    }

    /**
     * @param array<string,mixed> $package
     */
    public function get_price_for_package(array $package, string $tier, ?array $pricingProfile = null, bool $applyMarkup = true): ?float
    {
        $effectivePrices = $this->get_effective_price_map($package);
        $field = match ($tier) {
            'member' => 'member_price',
            'group' => 'group_price',
            default => 'public_price',
        };

        $price = $effectivePrices[$field]['value'];

        if ($pricingProfile === null || $tier === 'public') {
            return $price;
        }

        $overridePrice = $this->resolve_user_cost_override($package, $pricingProfile);
        $resolvedCost = $overridePrice ?? $price;
        if ($resolvedCost === null) {
            return null;
        }

        if (!$applyMarkup) {
            return $resolvedCost;
        }

        $markupPercent = $this->determine_user_markup_percent($package, $pricingProfile);

        return round($resolvedCost * (1 + ($markupPercent / 100)), 2);
    }

    /**
     * @param array<string,mixed> $package
     * @return array<string,array{value:?float,inherited:bool,source:string}>
     */
    public function get_effective_price_map(array $package): array
    {
        $rawPrices = [
            'public_price' => isset($package['public_price']) && $package['public_price'] !== '' ? ($package['public_price'] !== null ? (float) $package['public_price'] : null) : null,
            'member_price' => isset($package['member_price']) && $package['member_price'] !== '' ? ($package['member_price'] !== null ? (float) $package['member_price'] : null) : null,
            'group_price' => isset($package['group_price']) && $package['group_price'] !== '' ? ($package['group_price'] !== null ? (float) $package['group_price'] : null) : null,
        ];

        $effective = [];
        foreach (['public_price', 'member_price', 'group_price'] as $field) {
            if ($rawPrices[$field] !== null) {
                $effective[$field] = [
                    'value' => $rawPrices[$field],
                    'inherited' => false,
                    'source' => $field,
                ];
                continue;
            }

            $fallbackField = $this->find_first_available_price_field($rawPrices, [$field]);
            $effective[$field] = [
                'value' => $fallbackField !== null ? $rawPrices[$fallbackField] : null,
                'inherited' => $fallbackField !== null,
                'source' => $fallbackField ?? $field,
            ];
        }

        return $effective;
    }

    /**
     * @return array<string,mixed>
     */
    public function resolve_billing_cycle(?string $requestedCycle, string $tier, ?array $settings = null): array
    {
        $settings ??= $this->get_settings();
        $options = CMS_M365LIC_Catalog::billing_options();

        $defaultKey = match ($tier) {
            'member' => (string) ($settings['member_default_billing_cycle'] ?? 'annual_upfront'),
            'group' => (string) ($settings['group_default_billing_cycle'] ?? 'annual_monthly'),
            default => (string) ($settings['public_default_billing_cycle'] ?? 'annual_upfront'),
        };

        $key = (string) ($requestedCycle ?: $defaultKey);
        if (!isset($options[$key])) {
            $key = isset($options[$defaultKey]) ? $defaultKey : 'annual_upfront';
        }

        return $options[$key];
    }

    public function apply_billing_cycle(?float $basePrice, string $billingCycle): ?float
    {
        if ($basePrice === null) {
            return null;
        }

        $options = CMS_M365LIC_Catalog::billing_options();
        $multiplier = (float) ($options[$billingCycle]['multiplier'] ?? 1.0);

        return round($basePrice * $multiplier, 2);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function get_alternative_offers(): array
    {
        $settings = $this->get_settings();
        $raw = (string) ($settings['alternatives_json'] ?? '[]');
        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        $offers = [];
        foreach ($decoded as $offer) {
            if (!is_array($offer)) {
                continue;
            }

            $category = trim((string) ($offer['category'] ?? ''));
            $provider = trim((string) ($offer['provider'] ?? ''));
            $annualPrice = $this->normalize_price($offer['annual_price'] ?? null);
            $monthlyPrice = $this->normalize_price($offer['monthly_price'] ?? null);
            $isActive = !array_key_exists('is_active', $offer) || !empty($offer['is_active']);

            if (!$isActive || $category === '' || $provider === '') {
                continue;
            }

            $offers[] = [
                'category' => $category,
                'provider' => $provider,
                'annual_price' => $annualPrice,
                'monthly_price' => $monthlyPrice,
                'is_active' => 1,
            ];
        }

        usort($offers, static function (array $left, array $right): int {
            $categoryCompare = strcasecmp((string) ($left['category'] ?? ''), (string) ($right['category'] ?? ''));
            if ($categoryCompare !== 0) {
                return $categoryCompare;
            }

            return strcasecmp((string) ($left['provider'] ?? ''), (string) ($right['provider'] ?? ''));
        });

        return $offers;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function get_alternative_offers_for_billing(string $billingCycle): array
    {
        $isMonthlyRuntime = $billingCycle === 'monthly_flex';
        $offers = [];

        foreach ($this->get_alternative_offers() as $offer) {
            $price = $isMonthlyRuntime
                ? ($offer['monthly_price'] ?? null)
                : ($offer['annual_price'] ?? null);

            if ($price === null) {
                continue;
            }

            $offer['price'] = $price;
            $offer['billing_mode'] = $isMonthlyRuntime ? 'monthly_flex' : 'annual';
            $offers[] = $offer;
        }

        return $offers;
    }

    /**
     * @return array<string,mixed>
     */
    public function enforce_daily_limit(string $action, string $tier): array
    {
        $settings = $this->get_settings();
        $dateKey = date('Ymd');
        $actorHash = $this->resolve_actor_hash();
        $limit = $this->daily_limit_for_action($settings, $action, $tier);

        $stmt = $this->db()->prepare(
            "SELECT hits FROM {$this->prefix()}m365lic_usage_limits WHERE action_key = ? AND actor_hash = ? AND pricing_tier = ? AND date_key = ? LIMIT 1"
        );
        $stmt->execute([$action, $actorHash, $tier, $dateKey]);
        $hits = (int) ($stmt->fetchColumn() ?: 0);

        if ($hits >= $limit) {
            return [
                'allowed' => false,
                'used' => $hits,
                'limit' => $limit,
                'remaining' => 0,
                'message' => (string) ($settings['limit_notice'] ?? 'Tageslimit erreicht.'),
            ];
        }

        $sql = "INSERT INTO {$this->prefix()}m365lic_usage_limits (action_key, actor_hash, pricing_tier, date_key, hits)
                VALUES (?, ?, ?, ?, 1)
                ON DUPLICATE KEY UPDATE hits = hits + 1, updated_at = CURRENT_TIMESTAMP";
        $this->db()->prepare($sql)->execute([$action, $actorHash, $tier, $dateKey]);

        return [
            'allowed' => true,
            'used' => $hits + 1,
            'limit' => $limit,
            'remaining' => max(0, $limit - ($hits + 1)),
            'message' => '',
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function get_special_users(): array
    {
        try {
            $stmt = $this->db()->prepare(
                "SELECT su.*, u.username, u.email, u.display_name, u.role, u.status,
                        sg.group_key AS assigned_group_key,
                        sg.group_label AS assigned_group_label,
                        sg.description AS group_description,
                        sg.default_markup_percent,
                        sg.report_title AS group_report_title,
                        sg.report_intro AS group_report_intro,
                        sg.is_active AS group_is_active,
                        COALESCE(su.user_markup_override_percent, sg.default_markup_percent, su.special_markup_percent, 0) AS effective_markup_percent
                 FROM {$this->prefix()}m365lic_special_users su
                 INNER JOIN {$this->prefix()}users u ON u.id = su.user_id
                 LEFT JOIN {$this->prefix()}m365lic_special_groups sg ON sg.id = su.group_id
                 ORDER BY su.is_active DESC, COALESCE(NULLIF(u.display_name, ''), u.username) ASC"
            );
            $stmt->execute();
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }

        return array_map([$this, 'hydrate_special_user'], $rows);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function find_users_for_special_assignment(string $search = ''): array
    {
        $params = [];
        $where = ["u.status = 'active'"];

        if ($search !== '') {
            $where[] = '(u.username LIKE ? OR u.display_name LIKE ? OR u.email LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        try {
            $stmt = $this->db()->prepare(
                "SELECT u.id, u.username, u.email, u.display_name, u.role, u.status,
                        su.group_id, su.group_key, su.group_label, su.special_markup_percent, su.user_markup_override_percent,
                        su.note, su.is_active AS special_is_active,
                        sg.group_key AS assigned_group_key,
                        sg.group_label AS assigned_group_label,
                        sg.default_markup_percent,
                        sg.is_active AS group_is_active,
                        COALESCE(su.user_markup_override_percent, sg.default_markup_percent, su.special_markup_percent, 0) AS effective_markup_percent
                 FROM {$this->prefix()}users u
                 LEFT JOIN {$this->prefix()}m365lic_special_users su ON su.user_id = u.id
                 LEFT JOIN {$this->prefix()}m365lic_special_groups sg ON sg.id = su.group_id
                 WHERE " . implode(' AND ', $where) . "
                 ORDER BY COALESCE(NULLIF(u.display_name, ''), u.username) ASC
                 LIMIT 150"
            );
            $stmt->execute($params);
            return $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<string,mixed>|null
     */
    public function get_special_user_by_user_id(int $userId): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        try {
            $stmt = $this->db()->prepare(
                "SELECT su.*, u.username, u.email, u.display_name, u.role, u.status,
                        sg.group_key AS assigned_group_key,
                        sg.group_label AS assigned_group_label,
                        sg.description AS group_description,
                        sg.default_markup_percent,
                        sg.report_title AS group_report_title,
                        sg.report_intro AS group_report_intro,
                        sg.is_active AS group_is_active,
                        COALESCE(su.user_markup_override_percent, sg.default_markup_percent, su.special_markup_percent, 0) AS effective_markup_percent
                 FROM {$this->prefix()}m365lic_special_users su
                 INNER JOIN {$this->prefix()}users u ON u.id = su.user_id
                 LEFT JOIN {$this->prefix()}m365lic_special_groups sg ON sg.id = su.group_id
                 WHERE su.user_id = ?
                 LIMIT 1"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return null;
        }

        return is_array($row) ? $this->hydrate_special_user($row) : null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function save_special_user(int $userId, array $data): void
    {
        if ($userId <= 0) {
            return;
        }

        $groupId = max(0, (int) ($data['group_id'] ?? 0));
        $group = $this->get_special_group($groupId);
        if ($group === null) {
            return;
        }

        $groupKey = (string) ($group['group_key'] ?? 'special');
        $groupLabel = (string) ($group['group_label'] ?? 'Spezialzugang');
        $userMarkupOverride = $this->normalize_nullable_percent($data['user_markup_override_percent'] ?? null);
        $effectiveMarkupPercent = $userMarkupOverride ?? (float) ($group['default_markup_percent'] ?? 0);

        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->prefix()}m365lic_special_users (user_id, group_id, group_key, group_label, special_markup_percent, user_markup_override_percent, note, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                group_id = VALUES(group_id),
                group_key = VALUES(group_key),
                group_label = VALUES(group_label),
                special_markup_percent = VALUES(special_markup_percent),
                user_markup_override_percent = VALUES(user_markup_override_percent),
                note = VALUES(note),
                is_active = VALUES(is_active)"
        );

        $stmt->execute([
            $userId,
            $groupId,
            strtolower(preg_replace('/[^a-z0-9\-_]+/i', '-', $groupKey) ?: 'special'),
            $groupLabel,
            $effectiveMarkupPercent,
            $userMarkupOverride,
            trim((string) ($data['note'] ?? '')),
            !empty($data['is_active']) ? 1 : 0,
        ]);
    }

    public function remove_special_user(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $stmt = $this->db()->prepare("DELETE FROM {$this->prefix()}m365lic_special_users WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    public function current_user_id(): int
    {
        if (!class_exists('CMS\\Auth')) {
            return 0;
        }

        $auth = \CMS\Auth::instance();

        if (method_exists($auth, 'getUserId')) {
            return (int) $auth->getUserId();
        }

        if (method_exists($auth, 'getCurrentUser')) {
            $user = $auth->getCurrentUser();
            return is_object($user) ? (int) ($user->id ?? 0) : 0;
        }

        if (method_exists($auth, 'getUser')) {
            $user = $auth->getUser();
            return is_array($user) ? (int) ($user['id'] ?? 0) : (int) ($user->id ?? 0);
        }

        return 0;
    }

    /**
     * @return array<string,mixed>|null
     */
    public function get_current_special_user(): ?array
    {
        $userId = $this->current_user_id();
        if ($userId <= 0) {
            return null;
        }

        $record = $this->get_special_user_by_user_id($userId);
        if (!is_array($record) || empty($record['is_active'])) {
            return null;
        }

        if ((int) ($record['group_id'] ?? 0) > 0 && empty($record['group_is_active'])) {
            return null;
        }

        return $record;
    }

    public function current_user_has_special_access(): bool
    {
        return $this->get_current_special_user() !== null;
    }

    public function export_user_data(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        // Keine direkte Ausgabe nötig – nur Hook-Kompatibilität.
    }

    public function delete_user_data(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $userHash = hash('sha256', 'user:' . $userId);
        $stmt = $this->db()->prepare("DELETE FROM {$this->prefix()}m365lic_usage_limits WHERE actor_hash = ?");
        $stmt->execute([$userHash]);

        $stmt = $this->db()->prepare("DELETE FROM {$this->prefix()}m365lic_special_users WHERE user_id = ?");
        $stmt->execute([$userId]);

        $stmt = $this->db()->prepare("DELETE FROM {$this->prefix()}m365lic_user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);

        $stmt = $this->db()->prepare("DELETE FROM {$this->prefix()}m365lic_user_package_costs WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function hydrate_package(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['is_active'] = (int) ($row['is_active'] ?? 0);
        $row['sort_order'] = (int) ($row['sort_order'] ?? 0);
        $row['pricing_basis'] = $this->normalize_pricing_basis($row['pricing_basis'] ?? 'per_user');
        $row['public_price'] = $row['public_price'] !== null ? (float) $row['public_price'] : null;
        $row['member_price'] = $row['member_price'] !== null ? (float) $row['member_price'] : null;
        $row['group_price'] = $row['group_price'] !== null ? (float) $row['group_price'] : null;
        $row['currency'] = $this->normalize_currency($row['currency'] ?? 'EUR');
        $row['features'] = $this->decode_json_list($row['features_json'] ?? '[]');
        $row['tags'] = $this->decode_json_list($row['tags_json'] ?? '[]');
        $row['prerequisite_tags'] = $this->decode_json_list($row['prerequisite_tags_json'] ?? '[]');
        return $row;
    }

    /**
     * @return array<int,string>
     */
    private function decode_json_list(string $json): array
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $decoded)));
    }

    private function normalize_price(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $stringValue = str_replace(',', '.', trim((string) $value));
        return is_numeric($stringValue) ? round((float) $stringValue, 2) : null;
    }

    private function normalize_pricing_basis(mixed $value): string
    {
        return in_array((string) $value, ['per_user', 'flat_monthly'], true)
            ? (string) $value
            : 'per_user';
    }

    private function normalize_currency(mixed $value): string
    {
        $currency = strtoupper(trim((string) $value));

        return $currency === 'EUR' ? 'EUR' : 'EUR';
    }

    private function normalize_percent(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        $stringValue = str_replace(',', '.', trim((string) $value));
        if (!is_numeric($stringValue)) {
            return 0.0;
        }

        return round(max(0.0, min(999.0, (float) $stringValue)), 2);
    }

    private function normalize_nullable_percent(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        return $this->normalize_percent($value);
    }

    /**
     * @param array<string,?float> $prices
     * @return array<string,?float>
     */
    private function normalize_package_prices(array $prices): array
    {
        $firstField = $this->find_first_available_price_field($prices);
        if ($firstField === null) {
            return $prices;
        }

        $filledCount = count(array_filter($prices, static fn(?float $price): bool => $price !== null));
        if ($filledCount !== 1) {
            return $prices;
        }

        $fallbackValue = $prices[$firstField];
        foreach (array_keys($prices) as $field) {
            if ($prices[$field] === null) {
                $prices[$field] = $fallbackValue;
            }
        }

        return $prices;
    }

    /**
     * @param array<string,?float> $prices
     * @param array<int,string> $excludeFields
     */
    private function find_first_available_price_field(array $prices, array $excludeFields = []): ?string
    {
        foreach (['public_price', 'member_price', 'group_price'] as $field) {
            if (in_array($field, $excludeFields, true)) {
                continue;
            }

            if (($prices[$field] ?? null) !== null) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return array<string,mixed>
     */
    private function default_user_pricing_profile(int $userId): array
    {
        return [
            'user_id' => $userId,
            'partner_name' => '',
            'partner_logo_path' => '',
            'whitelabel_title' => '',
            'whitelabel_intro' => '',
            'base_markup_percent' => 0.0,
            'addon_markup_percent' => 0.0,
            'copilot_markup_percent' => 0.0,
            'cost_overrides' => [],
        ];
    }

    /**
     * @param array<string,mixed> $package
     * @param array<string,mixed> $pricingProfile
     */
    private function resolve_user_cost_override(array $package, array $pricingProfile): ?float
    {
        $packageId = (int) ($package['id'] ?? 0);
        $overrides = is_array($pricingProfile['cost_overrides'] ?? null)
            ? $pricingProfile['cost_overrides']
            : [];

        if ($packageId <= 0 || !isset($overrides[$packageId])) {
            return null;
        }

        return (float) $overrides[$packageId];
    }

    /**
     * @param array<string,mixed> $package
     * @param array<string,mixed> $pricingProfile
     */
    private function determine_user_markup_percent(array $package, array $pricingProfile): float
    {
        $category = (string) ($package['category'] ?? '');
        $slug = (string) ($package['slug'] ?? '');

        if ($category === 'copilot' || str_contains($slug, 'copilot')) {
            return $this->normalize_percent($pricingProfile['copilot_markup_percent'] ?? 0);
        }

        if (($package['kind'] ?? 'base') === 'addon') {
            return $this->normalize_percent($pricingProfile['addon_markup_percent'] ?? 0);
        }

        return $this->normalize_percent($pricingProfile['base_markup_percent'] ?? 0);
    }

    /**
     * @param array<string,mixed> $seed
     */
    private function sync_seed_package(array $seed): void
    {
        $existing = $this->get_package_by_slug((string) ($seed['slug'] ?? ''));
        if ($existing === null) {
            $this->save_package($seed);
            return;
        }

        $seed['id'] = (int) $existing['id'];
        $seed['public_price'] = $existing['public_price'] !== null
            ? $existing['public_price']
            : ($seed['public_price'] ?? null);
        $seed['member_price'] = $existing['member_price'] !== null
            ? $existing['member_price']
            : ($seed['member_price'] ?? null);
        $seed['group_price'] = $existing['group_price'] !== null
            ? $existing['group_price']
            : ($seed['group_price'] ?? null);
        $seed['currency'] = $this->normalize_currency($existing['currency'] ?? $seed['currency'] ?? 'EUR');
        $seed['pricing_note'] = trim((string) ($existing['pricing_note'] ?? '')) !== ''
            ? (string) $existing['pricing_note']
            : (string) ($seed['pricing_note'] ?? '');
        $seed['source_note'] = trim((string) ($existing['source_note'] ?? '')) !== ''
            ? (string) $existing['source_note']
            : (string) ($seed['source_note'] ?? '');
        $seed['description'] = trim((string) ($existing['description'] ?? '')) !== ''
            ? (string) $existing['description']
            : (string) ($seed['description'] ?? '');
        $seed['pricing_basis'] = $this->normalize_pricing_basis($existing['pricing_basis'] ?? ($seed['pricing_basis'] ?? 'per_user'));
        $seed['is_active'] = (int) ($existing['is_active'] ?? $seed['is_active'] ?? 1);
        $seed['sort_order'] = (int) ($existing['sort_order'] ?? $seed['sort_order'] ?? 0);

        $this->save_package($seed);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function hydrate_special_user(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['user_id'] = (int) ($row['user_id'] ?? 0);
        $row['group_id'] = (int) ($row['group_id'] ?? 0);
        $row['is_active'] = (int) ($row['is_active'] ?? 0);
        $row['group_key'] = trim((string) ($row['assigned_group_key'] ?? $row['group_key'] ?? 'special'));
        $row['group_label'] = trim((string) ($row['assigned_group_label'] ?? $row['group_label'] ?? 'Spezialzugang'));
        $row['group_description'] = trim((string) ($row['group_description'] ?? ''));
        $row['group_report_title'] = trim((string) ($row['group_report_title'] ?? ''));
        $row['group_report_intro'] = trim((string) ($row['group_report_intro'] ?? ''));
        $row['group_is_active'] = (int) ($row['group_is_active'] ?? 0);
        $row['default_markup_percent'] = $this->normalize_percent($row['default_markup_percent'] ?? 0);
        $row['user_markup_override_percent'] = $this->normalize_nullable_percent($row['user_markup_override_percent'] ?? null);
        $row['special_markup_percent'] = $this->normalize_percent($row['special_markup_percent'] ?? 0);
        $row['effective_markup_percent'] = $this->normalize_percent($row['effective_markup_percent'] ?? $row['special_markup_percent'] ?? 0);
        $row['note'] = trim((string) ($row['note'] ?? ''));
        $row['username'] = (string) ($row['username'] ?? '');
        $row['email'] = (string) ($row['email'] ?? '');
        $row['display_name'] = (string) ($row['display_name'] ?? '');
        $row['role'] = (string) ($row['role'] ?? 'member');
        $row['status'] = (string) ($row['status'] ?? 'active');
        return $row;
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function hydrate_special_group(array $row): array
    {
        $row['id'] = (int) ($row['id'] ?? 0);
        $row['group_key'] = $this->normalize_group_key($row['group_key'] ?? 'special');
        $row['group_label'] = trim((string) ($row['group_label'] ?? 'Spezialgruppe'));
        $row['description'] = trim((string) ($row['description'] ?? ''));
        $row['default_markup_percent'] = $this->normalize_percent($row['default_markup_percent'] ?? 0);
        $row['report_title'] = trim((string) ($row['report_title'] ?? ''));
        $row['report_intro'] = trim((string) ($row['report_intro'] ?? ''));
        $row['is_active'] = (int) ($row['is_active'] ?? 0);
        $row['assigned_users'] = (int) ($row['assigned_users'] ?? 0);
        return $row;
    }

    private function normalize_group_key(mixed $value): string
    {
        $groupKey = strtolower(trim((string) $value));
        $groupKey = preg_replace('/[^a-z0-9\-_]+/i', '-', $groupKey) ?: '';
        $groupKey = trim($groupKey, '-_');

        return $groupKey !== '' ? $groupKey : 'special';
    }

    private function sync_special_group_to_users(int $groupId): void
    {
        $group = $this->get_special_group($groupId);
        if ($group === null) {
            return;
        }

        try {
            $stmt = $this->db()->prepare(
                "UPDATE {$this->prefix()}m365lic_special_users
                 SET group_key = ?,
                     group_label = ?,
                     special_markup_percent = COALESCE(user_markup_override_percent, ?, special_markup_percent)
                 WHERE group_id = ?"
            );
            $stmt->execute([
                (string) ($group['group_key'] ?? 'special'),
                (string) ($group['group_label'] ?? 'Spezialgruppe'),
                (float) ($group['default_markup_percent'] ?? 0),
                $groupId,
            ]);
        } catch (\Throwable $e) {
            // ignore sync edge cases
        }
    }

    private function resolve_actor_hash(): string
    {
        if (class_exists('CMS\\Auth')) {
            $auth = \CMS\Auth::instance();
            if (method_exists($auth, 'isLoggedIn') && $auth->isLoggedIn()) {
                $userId = $this->current_user_id();
                if ($userId > 0) {
                    return hash('sha256', 'user:' . $userId);
                }
            }
        }

        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        return hash('sha256', 'ip:' . $ip);
    }

    private function seed_default_alternatives_if_needed(bool $force): void
    {
        $defaultJson = json_encode(CMS_M365LIC_Catalog::default_alternative_offers(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($defaultJson) || $defaultJson === '') {
            return;
        }

        $currentValue = null;

        try {
            $stmt = $this->db()->prepare("SELECT setting_value FROM {$this->prefix()}m365lic_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute(['alternatives_json']);
            $result = $stmt->fetchColumn();
            $currentValue = $result !== false ? (string) $result : null;
        } catch (\Throwable $e) {
            $currentValue = null;
        }

        $trimmedValue = trim((string) $currentValue);
        $shouldSeed = $force || $trimmedValue === '' || $trimmedValue === '[]' || strtolower($trimmedValue) === 'null';

        if (!$shouldSeed) {
            return;
        }

        $stmt = $this->db()->prepare(
            "INSERT INTO {$this->prefix()}m365lic_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        $stmt->execute(['alternatives_json', $defaultJson]);
        $this->settingsCache = null;
    }

    /**
     * @param array<string,string> $settings
     */
    private function daily_limit_for_action(array $settings, string $action, string $tier): int
    {
        $key = match ($tier) {
            'member' => $action === 'pdf_export' ? 'member_pdf_daily_limit' : 'member_daily_limit',
            'group' => $action === 'pdf_export' ? 'group_pdf_daily_limit' : 'group_daily_limit',
            default => $action === 'pdf_export' ? 'public_pdf_daily_limit' : 'public_daily_limit',
        };

        return max(1, (int) ($settings[$key] ?? 2));
    }
}
